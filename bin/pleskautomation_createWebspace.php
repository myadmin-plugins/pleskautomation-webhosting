<?php
include_once(__DIR__.'/../../../include/functions.inc.php');
$ppaConnector = get_webhosting_ppa_instance();
$password = _randomstring(10);
$data = $GLOBALS['tf']->accounts->read(2773);
list($first, $last) = explode(' ', $data['name']);
$account_id = 127;
$service_template_id = 24;
$subscription_id = 214;
$domain = 'pleskapitest.d.interserver.net';
$request = array(
	'new_webspace' => array(
		'sub_id' => $subscription_id,
		'domain' => $domain,
		'resources' => array(
			array('rt_id' => 1000084),
			//array('rt_id' => 1000115),
		),
	),
);
$result = $ppaConnector->{'pleskintegration.createWebspace'}($request);
echo "Result:";
var_dump($result);
echo "\n";
try {
	PPAConnector::checkResponse($result);
} catch (Exception $e) {
	echo 'Caught exception: '.$e->getMessage()."\n";
}
echo "Got Subscription ID: {$result['result']['webspace_id']}\n";