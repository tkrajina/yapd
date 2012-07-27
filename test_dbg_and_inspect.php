<?

require_once 'psi/dbg.php';

$a = 1;
function test() {
    $b = 'bbb';
    for($i = 0; $i < 3; $i++) {
        __dbg_and_inspect(get_defined_vars());
    }
}

__dbg_and_inspect(get_defined_vars());

test();

echo 'OK';
