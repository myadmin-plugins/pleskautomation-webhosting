<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
$accountId = (int) $_SERVER['argv'][1];
$subscriptoinId = (int) $_SERVER['argv'][1];
$request = [
	'account_id' => $accountId,
	'subscription_id' => $subscriptoinId
];
$result = $ppaConnector->getSubscriptionWebspaces($request);
echo preg_replace("/$\s*array\s+\(/msiU", 'array(', var_export($result, true));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (\Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
