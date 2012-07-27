<?

require_once 'yapd/dbg.php';

echo "Waiting for debug...\n";

while(true) {
    $executed = __inspect();
    if($executed)
        echo "Waiting for next debug...\n";
    sleep(1);
}
