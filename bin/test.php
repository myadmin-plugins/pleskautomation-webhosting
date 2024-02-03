<?php

include_once __DIR__.'/../../../../include/functions.inc.php';
require_once INCLUDE_ROOT.'/webhosting/class.pleskautomation.php';
$server = NEW_WEBSITE_PPA_SERVER;
$serverdata = get_service_master($server, $module);
$hash = $serverdata['website_key'];
$ip = $serverdata['website_ip'];
[$pleskUser, $pleskPass] = explode(':', $hash);
$url = "https://{$pleskUser}:{$pleskPass}@{$ip}:8440/RPC2";
$options = [
                'prefix' => 'system.',
                'debug' => false,
                'sslverify' => false
];
            $xmlrpcClient = XML_RPC2_Client::create($url, $options);
$result = $xmlrpcClient->listMethods();
echo 'Result:';
print_r($result);
echo "\n";
exit;
