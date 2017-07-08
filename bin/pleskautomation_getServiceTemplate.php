<?php
include_once(__DIR__.'/../../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
// let us form an array with account data
$st_id = (isset($_SERVER['argv'][1]) ? (int) $_SERVER['argv'][1] : 12);
$request = array(
	'st_id' => $st_id,
	'get_resources' => TRUE,
	'get_full_info' => FALSE,
);
$result = $ppaConnector->getServiceTemplate($request);
echo "Result:";
var_dump($result);
echo "\n";
// Parse the response
try {
var_export($result);
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
