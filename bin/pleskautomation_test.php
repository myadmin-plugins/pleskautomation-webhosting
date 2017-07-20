<?php
include_once __DIR__.'/../../../../include/functions.inc.php';
require_once INCLUDE_ROOT.'/webhosting/class.pleskautomation.php';
$server = NEW_WEBSITE_PPA_SERVER;
$serverdata = get_service_master($server, $module);
$hash = $serverdata['website_key'];
$ip = $serverdata['website_ip'];
list($plesk_user, $plesk_pass) = explode(':', $hash);
$url = "https://{$plesk_user}:{$plesk_pass}@{$ip}:8440/RPC2";
$options = [
				'prefix' => 'system.',
				'debug' => FALSE,
				'sslverify' => FALSE
];
			$xmlrpcClient = XML_RPC2_Client::create($url, $options);
$result = $xmlrpcClient->listMethods();
echo 'Result:';
print_r($result);
echo "\n";
exit;
