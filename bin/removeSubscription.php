<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
$ppaConnector = get_webhosting_ppa_instance();
// let us form an array with account data
$subscriptoinId = 215;
$request = [
    'subscription_id' => $subscriptoinId
];
// Make the pem.addAccount call.
// The PPAConnector instance will form a proper XML-RPC request by itself.
// Note that the method is called without the pem prefix as it will be added by the PPAConnector instance.
$result = $ppaConnector->removeSubscription($request);
echo 'Result:';
var_dump($result);
echo "\n";
// Parse the response
try {
    PPAConnector::checkResponse($result);
} catch (\Exception $e) {
    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
}
echo "Success.\nGot Account ID: {$result['result']['account_id']}\n";
