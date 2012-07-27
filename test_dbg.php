<?

require_once 'yapd/dbg.php';

$a = 1;
function test() {
    $b = 'bbb';
    for($i = 0; $i < 3; $i++) {
        __dbg(get_defined_vars());
    }
}

__dbg(get_defined_vars());

test();

echo 'OK';
