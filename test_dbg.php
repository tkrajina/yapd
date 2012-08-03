<?

require_once 'yapd/dbg.php';

$a = 1;
function test() {
    $b = 'bbb';
    for($i = 0; $i < 3; $i++) {
        eval(__dbg(get_defined_vars()));
    }
}

eval(__dbg(get_defined_vars()));

test();

echo 'OK';
