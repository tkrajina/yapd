<?php

/**
 * PHP command line "debugger": start with:
 * require_once 'oneapi/dbg.php';
 * __dbg(get_defined_vars());
 */

if(!defined('DBG_TEMP_FILE'))
    define('DBG_TEMP_FILE', '/' . sys_get_temp_dir() . '/phpdbg.tmp');

define('DBG_SLEEP_PERIOD', 1);
define('DBG_OUTPUT_PREFIX', '    ');

class DbgContext {

    public $fileName;
    public $lineNumber;
    public $variables;
    public $backTrace;
    public $saved = 0;

    public function __construct($fileName, $lineNumber, $variables, $backTrace) {
        $this->fileName = $fileName;
        $this->lineNumber = $lineNumber;
        $this->variables = $variables;
        $this->backTrace = $backTrace;
    }

    public function save() {
        $this->saved = mktime(false);

        $serialized = serialize($this);
        file_put_contents(DBG_TEMP_FILE, $serialized);
    }

    /**
     * Refresh the "last changed" time of the file.
     */
    public function reload() {
        $this->save();
    }

    public function isValid() {
        if(!$this->exists())
            return false;

        if(mktime(false) - $this->saved > 5 * DBG_SLEEP_PERIOD)
            return false;

        return true;
    }

    public function exists() {
        return (boolean) @file_get_contents(DBG_TEMP_FILE);
    }

    public static function load() {
        $serialized = @file_get_contents(DBG_TEMP_FILE);

        if(!$serialized)
            return null;

        return unserialize($serialized);
    }

    public function remove() {
        unlink(DBG_TEMP_FILE);
    }

}

function __dbg_list_code($dbgContext, $args) {
    echo "\n", DBG_OUTPUT_PREFIX, "File:", $dbgContext->fileName, ":\n\n";
    $lines = file($dbgContext->fileName);
    $linesAround = (int) $args;
    if(! $linesAround)
        $linesAround = 7;
    for($i = 0; $i < sizeof($lines); $i++) {
        if($dbgContext->lineNumber - $linesAround <= $i && $i < $dbgContext->lineNumber + $linesAround) {
            echo DBG_OUTPUT_PREFIX, trim($i . ':' . $lines[$i]);
            if($i == $dbgContext->lineNumber - 1)
                echo "\n", DBG_OUTPUT_PREFIX, "\t^-------------------------------------------------------------------------------- HERE YOU ARE !\n";
            else
                echo "\n";
        }
    }
    echo "\n";
}

function __dbg_print_expression($dbgContext, $args) {
    try {
        $evalResult = null;
        $evalExpression = preg_replace('/(\$)(\w+)/', '\$dbgContext->variables[\'$2\']', $args);
        //echo DBG_OUTPUT_PREFIX, "Evaluating expression:", $evalExpression, "\n";
        eval('$evalResult = ' . $evalExpression . ';');
        echo DBG_OUTPUT_PREFIX, "= ", print_r($evalResult, true), "\n";
    } catch(Exception $e) {
        echo DBG_OUTPUT_PREFIX, "Invalid expression:", $args, "\n";
    }
}

function __dbg_print_variables($dbgContext, $args) {
    foreach($dbgContext->variables as $key => $value) {
        $value = str_replace("\n", ' ', print_r($value, true));
        $value = preg_replace('/\s+/', ' ', $value);
        if(strlen($value) > 80)
            $value = substr($value, 0, 80) . '...';
        echo DBG_OUTPUT_PREFIX, $key, ' = ', $value, "\n";
    }
}

function __dbg_print_backtrace($dbgContext, $args) {
    $backTrace = $dbgContext->backTrace;
    for($i = 1; $i < sizeof($backTrace); $i++) {
        $backTraceElement = $backTrace[$i];
        $file = @$backTraceElement['file'];
        $lineNumber = @$backTraceElement['line'];
        $function = @$backTraceElement['function'];
        $class = @$backTraceElement['class'];
        $functionArgs = @$backTraceElement['args'];
        echo DBG_OUTPUT_PREFIX, $file . '(' . $lineNumber . "):\n";
        if($function)
            echo DBG_OUTPUT_PREFIX, "function: ", $function, "\n";
        if($class)
            echo DBG_OUTPUT_PREFIX, "class: ", $class, "\n";
        if($functionArgs) {
            $functionArgs = str_replace("\n", ' ', print_r($functionArgs, true));
            $functionArgs = preg_replace('/\s+/', ' ', $functionArgs);
            echo DBG_OUTPUT_PREFIX, "args: ", $functionArgs, "\n";
        }
        echo DBG_OUTPUT_PREFIX, $file . '(' . $lineNumber . "):\n";
    }
}

function __dbg_continue($dbgContext, $args) {
    $dbgContext->remove();
}

/**
 * Starts the "inspect" shell.
 */
function __inspect() {
    $run = true;

    $dbgContext = DbgContext::load();

    if(! $dbgContext) {
        return false;
    }

    if(!$dbgContext->isValid()) {
        echo "Found old dbg file, ignoring it\n";
        $dbgContext->remove();
        return false;
    }

    $commands = array(
        array('l', '__dbg_list_code',           ' [n]          -> shows n lines of code around the current line'),
        array('p', '__dbg_print_expression',    ' <expression> -> evalates a simple php expression'),
        array('v', '__dbg_print_variables',     '              -> print all variables'),
        array('s', '__dbg_print_backtrace',     '              -> print back trace'),
        array('c', '__dbg_continue',            '              -> continues the execution'),
    );

    while($dbgContext->exists()) {
        $line = readline('? ');
        $line = @trim($line);
        $startOfCommand = trim(@substr($line, 0, 2));
        $args = trim(@substr($line, 2));

        if($line) {
            foreach($commands as $commandItem) {
                list($command, $function, $help) = $commandItem;
                if($startOfCommand == $command)
                    $function($dbgContext, $args);
            }
        } else {
            foreach($commands as $commandItem) {
                list($command, $function, $help) = $commandItem;
                echo DBG_OUTPUT_PREFIX, $command, $help, "\n";
            }
        }
    }

    return true;
}

/**
 * Sets a debug point and stops executing the script. Another php script 
 * (executed from command line) should be invoked with __inspect (see 
 * inspect.php).
 */
function __dbg($vars, $extended=null) {
    if($extended && is_array($extended))
        $vars = array_merge($vars, $extended);

    $backTrace = debug_backtrace();
    $backTraceElement = $backTrace[0];
    $file = $backTraceElement['file'];
    $lineNumber = $backTraceElement['line'];

    $dbgContext = new DbgContext($file, $lineNumber, $vars, $backTrace);

    $dbgContext->save();

    while($dbgContext->exists()) {
        sleep(DBG_SLEEP_PERIOD);
    }
}

/**
 * Intended for php scripts executed in command line. The script will stop 
 * here and the "inspect" shell will be started.
 */
function __dbg_and_inspect($vars) {
    $backTrace = debug_backtrace();
    $backTraceElement = $backTrace[0];
    $file = $backTraceElement['file'];
    $lineNumber = $backTraceElement['line'];

    $dbgContext = new DbgContext($file, $lineNumber, $vars, $backTrace);

    $dbgContext->save();

    __inspect();
}
