<?php
include_once(__DIR__.'/../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$account_id = intval($_SERVER['argv'][1]);
$subscriptoinId = intval($_SERVER['argv'][1]);
$request = array(
	'account_id' => $account_id,
	'subscription_id' => $subscriptoinId,
);
$result = $ppaConnector->getSubscriptionWebspaces($request);
echo preg_replace("/$\s*array\s+\(/msiU", "array(", var_export($result, true));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
