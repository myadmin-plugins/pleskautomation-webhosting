<?php

/**
 * maps a domain name to plesk info
 *
 * ok finally got basic logic figured out that will let me lookup a webspace by domain name (plesk function not ppa, but can still be used nearly the same)
 * from there it gets the webspace id, then w/ that it loads the webspace.  w/ that result it parse the account id, subscription id, and webspace status
 * then it loads the subscription data parsing the subscription status from response, account info data parsing the email from the response
 * then it loads the account members and from that result gets the member_id and then loads the full member info, from that result it parses out the username
 * and user_id thats everything needed then
 *
 * @param string $hostname the website/domain name to lookup
 * @return array an array respectively containing $account_id, $member_id, $subscriptoinId, and $webspaceId
 */
function get_pleskautomation_info_from_domain($hostname) {
	$module = 'webhosting';
	$settings = get_module_settings($module);
	$db = get_module_db($module);
	$db->query("select * from {$settings['PREFIX']}_masters where {$settings['PREFIX']}_type=" . SERVICE_TYPES_WEB_PPA . "  order by {$settings['PREFIX']}_available desc limit 1", __LINE__, __FILE__);
	$db->next_record(MYSQL_ASSOC);
	$serverData = $db->Record;
	$ppaConnector = get_webhosting_ppa_instance($serverData);
	try {
		$result = $ppaConnector->__call('pleskintegration.getWebspaceIDByPrimaryDomain', array('domain' => $hostname));
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (Exception $e) {
		echo 'Caught exception: '.$e->getMessage() . "\n";
		return false;
	}
	$webspaceId = $result['result']['webspace_id'];
	try {
		$result = $ppaConnector->__call('pleskintegration.getWebspace', array('webspace_id' => $webspaceId));
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (Exception $e) {
		echo 'Caught exception: '.$e->getMessage() . "\n";
		return false;
	}
	$account_id = $result['result']['owner_id'];
	$subscriptoinId = $result['result']['sub_id'];
	$webspace_status = $result['result']['status'];
	try {
		$result = $ppaConnector->__call('getSubscription', array('subscription_id' => $subscriptoinId, 'get_resources' => true));
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (Exception $e) {
		echo 'Caught exception: '.$e->getMessage() . "\n";
		return false;
	}
	$subscription_status = $result['result']['is_active'];
	try {
		$result = $ppaConnector->__call('getAccountInfo', array('account_id' => $account_id));
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (Exception $e) {
		echo 'Caught exception: '.$e->getMessage() . "\n";
		return false;
	}
	$email = $result['result']['email'];
	try {
		$result = $ppaConnector->__call('getAccountMembers', array('account_id' => $account_id));
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (Exception $e) {
		echo 'Caught exception: '.$e->getMessage() . "\n";
		return false;
	}
	$member_id = $result['result'][0];
	try {
		$result = $ppaConnector->__call('getMemberFullInfo', array('member_id' => $member_id));
		\Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
	} catch (Exception $e) {
		echo 'Caught exception: '.$e->getMessage() . "\n";
		return false;
	}
	$username = $result['result']['auth_info']['login'];
	$userId = $result['result']['user_id'];
	myadmin_log('webhosting', 'info', "Plesk Lookup for {$hostname} returned array({$account_id}, {$member_id}, {$subscriptoinId}, {$webspaceId})", __LINE__, __FILE__);
	$extra = array($account_id, $member_id, $subscriptoinId, $webspaceId);
	return $extra;
}

