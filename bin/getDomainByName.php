<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
$result = $ppaConnector->getDomainByName($request);
echo 'Result:';
var_dump($result);
echo "\n";
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
