<?php
include_once(__DIR__.'/../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$user_id = intval($_SERVER['argv'][1]);
$request = array(
	'user_id' => $user_id,
);
$result = $ppaConnector->getUserFullInfo($request);
echo preg_replace("/$\s*array\s+\(/msiU", "array(", var_export($result, true));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage()."\n";
}
