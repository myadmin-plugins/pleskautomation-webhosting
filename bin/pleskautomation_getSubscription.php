<?php
include_once(__DIR__.'/../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$subscriptoinId = intval($_SERVER['argv'][1]);
$request = array(
	'subscription_id' => $subscriptoinId,
	'get_resources' => true,
);
$result = $ppaConnector->getSubscription($request);
echo preg_replace("/$\s*array\s+\(/msiU", "array(", var_export($result, true));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Got Account ID: {$result['result']['owner_id']}\n";
