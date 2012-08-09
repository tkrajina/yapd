Yet Another PHP debugger
========================

YAPD is a simple PHP debugger-like tool. It can be used for web applications or CLI PHP scripts.

Installation
------------

Clone from the github repo. 

Edit your **/etc/php/php5/php.ini** and/or **/etc/php5/cli/php.ini** files and add the yamd folder to the **include_path** variable.

Debugging CLI scripts:
----------------------

Go to the cloned folder, start **./inspect.php**. YAMD will start and wait for a debug event.

Create a new PHP script. Include YAPD:

    require_once 'yapd/dbg.php';

Insert your debug points with:

    __dbg(get_defined_vars();

Start the script, the **inspect.php** (we have started it earlier) process should now look like:

    $ ./inspect.php 
    Waiting for debug...
    ? 

With CLI scripts you can debug without running **inspect.php**. Just enter the following code:

    require_once 'yapd/dbg.php';
    __dbg_and_inspect(get_defined_vars());

Debugging web applications:
---------------------------

Start **inspect.php**.

Include YAPD in your PHP scripts:

    require_once 'yapd/dbg.php';

Insert your debug points with:

    __dbg(get_defined_vars();

Save them. Trigger a http request from the browser, and debug with **inspect.php**...

How to use the debugger:
------------------------

Type **enter** to have the list of commands:

    ? 
        l [n]          -> shows n lines of code around the current line
        p <expression> -> evalates a simple php expression
        v              -> print all variables
        s              -> print back trace
        c              -> continues the execution

Type **l** to list the actual code where the debugger stopped:

    ? l

        File:/home/puzz/projects/yapd/test_dbg.php:

        6:    $b = 'bbb';
        7:    for($i = 0; $i < 3; $i++) {
        8:        __dbg(get_defined_vars());
        9:    }
        10:}
        11:
        12:__dbg(get_defined_vars());
            ^------------------------------ HERE YOU ARE !
        13:
        14:test();
        15:
        16:echo 'OK';

type **v** to have a quick list of all defined variables:

    ? v
        GLOBALS = Array ( [GLOBALS] => Array ( [GLOBALS] => Array *RECURSION* [argv] => Array ( [0...
        argv = Array ( [0] => test_dbg.php ) 
        argc = 1
        _POST = Array ( ) 
        _GET = Array ( ) 
        _COOKIE = Array ( ) 
        _FILES = Array ( ) 
        _SERVER = Array ( [SSH_AGENT_PID] => 1687 [GPG_AGENT_INFO] => /tmp/keyring-RiA5Mc/gpg:0:1 ...
        a = 1

Type **p** if you want to evaluate variables or simple PHP expressions:

    ? p $a
        = 1
    ? p $a * 10
        = 10
    ? p sqrt($a)
        = 1

Type **b** to see the actual backtrace of this debug point:

    ? s
        /home/puzz/projects/yapd/test_dbg.php(15):
        function: test
        /home/puzz/projects/yapd/test_dbg.php(15):

Type **s** to change the variable value. In order for this to work you must set the debug with **eval(__dbg(get_defined_vars()))**. The value change will take place after you continue the execution with **c**. For example to set the variable $i to 100, type:

    ? s $i 100

Or just **c** to continue to the next debug point.

License:
--------

YAPD is licensed under the [Apache License, Version 2.0](http://www.apache.org/licenses/LICENSE-2.0)
