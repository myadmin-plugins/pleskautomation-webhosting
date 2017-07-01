<?php
include_once(__DIR__.'/../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$userId = intval($_SERVER['argv'][1]);
$request = array(
	'user_id' => $userId,
);
$result = $ppaConnector->getUserFullInfo($request);
echo preg_replace("/$\s*array\s+\(/msiU", "array(", var_export($result, true));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
