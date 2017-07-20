<?php
include_once(__DIR__.'/../../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$member_id = (int)$_SERVER['argv'][1];
$request = [
	'member_id' => $member_id
];
$result = $ppaConnector->getMemberFullInfo($request);
echo preg_replace("/$\s*array\s+\(/msiU", 'array(', var_export($result, TRUE));
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
