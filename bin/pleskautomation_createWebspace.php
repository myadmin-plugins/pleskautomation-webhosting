<?php
include_once(__DIR__.'/../../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$password = _randomstring(10);
$data = $GLOBALS['tf']->accounts->read(2773);
list($first, $last) = explode(' ', $data['name']);
$accountId = 127;
$serviceTemplateId = 24;
$subscriptoinId = 214;
$domain = 'pleskapitest.d.interserver.net';
$request = [
	'new_webspace' => [
		'sub_id' => $subscriptoinId,
		'domain' => $domain,
		'resources' => [
			['rt_id' => 1000084],
			//array('rt_id' => 1000115),
		]
	]
];
$result = $ppaConnector->{'pleskintegration.createWebspace'}($request);
echo 'Result:';
var_dump($result);
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Got Subscription ID: {$result['result']['webspace_id']}\n";
