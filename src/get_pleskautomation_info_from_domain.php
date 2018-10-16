<?php
/**
 * maps a domain name to plesk info
 * ok finally got basic logic figured out that will let me lookup a webspace by domain name (plesk function not ppa, but can still be used nearly the same)
 * from there it gets the webspace id, then w/ that it loads the webspace.  w/ that result it parse the account id, subscription id, and webspace status
 * then it loads the subscription data parsing the subscription status from response, account info data parsing the email from the response
 * then it loads the account members and from that result gets the member_id and then loads the full member info, from that result it parses out the username
 * and user_id thats everything needed then
 *
 * @param string $hostname the website/domain name to lookup
 * @return array|bool
 * @throws \Detain\MyAdminPleskAutomation\Detain\MyAdminPleskAutomation\PPAFailedRequestException
 * @throws \Detain\MyAdminPleskAutomation\Detain\MyAdminPleskAutomation\PPAMalformedRequestException
 */
function get_pleskautomation_info_from_domain($hostname)
{
	$module = 'webhosting';
	$settings = \get_module_settings($module);
	$db = get_module_db($module);
	$db->query("select * from {$settings['PREFIX']}_masters where {$settings['PREFIX']}_type=".get_service_define('WEB_PPA')."  order by {$settings['PREFIX']}_available desc limit 1", __LINE__, __FILE__);
	$db->next_record(MYSQL_ASSOC);
	$serverData = $db->Record;
	include_once __DIR__.'/get_webhosting_ppa_instance.php';
	$ppaConnector = get_webhosting_ppa_instance($serverData);
	try {
		$result = $ppaConnector->__call('pleskintegration.getWebspaceIDByPrimaryDomain', ['domain' => $hostname]);
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (\Exception $e) {
		echo 'Caught exception: '.$e->getMessage().PHP_EOL;
		return false;
	}
	$webspaceId = $result['result']['webspace_id'];
	try {
		$result = $ppaConnector->__call('pleskintegration.getWebspace', ['webspace_id' => $webspaceId]);
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (\Exception $e) {
		echo 'Caught exception: '.$e->getMessage().PHP_EOL;
		return false;
	}
	$accountId = $result['result']['owner_id'];
	$subscriptoinId = $result['result']['sub_id'];
	try {
		$result = $ppaConnector->__call('getSubscription', ['subscription_id' => $subscriptoinId, 'get_resources' => true]);
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (\Exception $e) {
		echo 'Caught exception: '.$e->getMessage().PHP_EOL;
		return false;
	}
	try {
		$result = $ppaConnector->__call('getAccountInfo', ['account_id' => $accountId]);
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (\Exception $e) {
		echo 'Caught exception: '.$e->getMessage().PHP_EOL;
		return false;
	}
	try {
		$result = $ppaConnector->__call('getAccountMembers', ['account_id' => $accountId]);
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (\Exception $e) {
		echo 'Caught exception: '.$e->getMessage().PHP_EOL;
		return false;
	}
	$memberId = $result['result'][0];
	try {
		$result = $ppaConnector->__call('getMemberFullInfo', ['member_id' => $memberId]);
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (\Exception $e) {
		echo 'Caught exception: '.$e->getMessage().PHP_EOL;
		return false;
	}
	myadmin_log('webhosting', 'info', "Plesk Lookup for {$hostname} returned array({$accountId}, {$memberId}, {$subscriptoinId}, {$webspaceId})", __LINE__, __FILE__);
	return [$accountId, $memberId, $subscriptoinId, $webspaceId];
}
