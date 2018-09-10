<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
$subscriptoinId = (int) $_SERVER['argv'][1];
$request = [
	'subscription_id' => $subscriptoinId
];
$result = $ppaConnector->getDomainList($request);
echo preg_replace("/$\s*array\s+\(/msiU", 'array(', var_export($result, true));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (xception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
