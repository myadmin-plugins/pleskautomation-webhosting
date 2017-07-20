<?php
include_once(__DIR__.'/../../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
// let us form an array with account data
$data = $GLOBALS['tf']->accounts->read(2773);
list($first, $last) = explode(' ', $data['name']);
$request = [
	'person' => [
		'first_name' => $first,
		'last_name' => $last,
		'company_name' => $data['company'],
	],
	'address' => [
		'street_name' => $data['address'],
		'address2' => $data['address2'],
		'zipcode' => $data['zip'],
		'city' => $data['city'],
		'country' => $data['country'],
		'state' => $data['state'],
	],
	'phone' => [
		'country_code' => '1',
		'area_code' => '',
		'phone_num' => $data['phone'],
		'ext_num' => '',
	],
	'email' => $data['account_lid'],
];
// Make the pem.addAccount call.
// The PPAConnector instance will form a proper XML-RPC request by itself.
// Note that the method is called without the pem prefix as it will be added by the PPAConnector instance.
$result = $ppaConnector->addAccount($request);
echo "Result:";
var_dump($result);
echo "\n";
// Parse the response
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\nGot Account ID: {$result['result']['account_id']}\n";
