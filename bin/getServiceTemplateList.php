<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
// let us form an array with account data
$owner_id = 0;
$request = [
	'owner_id' => $owner_id,
	'active' => TRUE
];
$result = $ppaConnector->getServiceTemplateList($request);
echo 'Result:';
var_dump($result);
echo "\n";
// Parse the response
try {
	PPAConnector::checkResponse($result);
} catch (xception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\n";
