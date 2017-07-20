<?php
include_once(__DIR__.'/../../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$subscriptoinId = (int)$_SERVER['argv'][1];
$request = [
	'subscription_id' => $subscriptoinId,
];
$result = $ppaConnector->getUsers($request);
echo preg_replace("/$\s*array\s+\(/msiU", "array(", var_export($result, TRUE));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
