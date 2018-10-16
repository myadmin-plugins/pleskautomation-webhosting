<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
// let us form an array with account data
$service_id = 126;
$resource_id = 100;
$request = [
	'st_id' => $service_id,
	'rt_id' => $resource_id
];
$result = $ppaConnector->removeResourceTypeFromServiceTemplate($request);
echo 'Result:';
var_dump($result);
echo "\n";
// Parse the response
try {
	PPAConnector::checkResponse($result);
} catch (\Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
