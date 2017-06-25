<?php
include_once(__DIR__.'/../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
// let us form an array with account data
$password = _randomstring(10);
$data = $GLOBALS['tf']->accounts->read(2773);
$owner_id = 126;
$name = 'My Service';
$service_template = 100;
$resource_type = 100;
$request = array(
	'owner_id' => $owner_id,
	'st_id' => $service_template,
	'rt_id' => $resource_type,
);
// Make the pem.addAccount call.
// The PPAConnector instance will form a proper XML-RPC request by itself.
// Note that the method is called without the pem prefix as it will be added by the PPAConnector instance.
$result = $ppaConnector->addResourceTypeToServiceTemplate($request);
echo "Result:";
var_dump($result);
echo "\n";
// Parse the response
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage()."\n";
}
echo "Success.\nGot Account ID: {$result['result']['account_id']}\n";
