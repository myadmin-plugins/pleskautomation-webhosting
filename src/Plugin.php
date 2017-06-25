<?php

namespace Detain\MyAdminPleskautomation;

use Detain\Pleskautomation\Pleskautomation;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Pleskautomation Webhosting';
	public static $description = 'Allows selling of Pleskautomation Server and VPS License Types.  More info at https://www.netenberg.com/pleskautomation.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a pleskautomation license. Allow 10 minutes for activation.';
	public static $module = 'webhosting';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_WEB_PPA) {
			myadmin_log(self::$module, 'info', 'Pleskautomation Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	public static function getReactivate(GenericEvent $event) {
		$service = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_WEB_PPA) {
			$serviceInfo = $service->getServiceInfo();
			$settings = get_module_settings(self::$module);
			$serverdata = get_service_master($serviceInfo[$settings['PREFIX'].'_server'], self::$module);
			$hash = $serverdata[$settings['PREFIX'].'_key'];
			$ip = $serverdata[$settings['PREFIX'].'_ip'];
			$success = true;
			$extra = run_event('parse_service_extra', $serviceInfo[$settings['PREFIX'] . '_extra'], self::$module);
			if (sizeof($extra) == 0)
				function_requirements('get_plesk_info_from_domain');
				$extra = get_plesk_info_from_domain($serviceInfo[$settings['PREFIX'].'_hostname']);
			if (sizeof($extra) == 0) {
				$msg = 'Blank/Empty Plesk Subscription Info, Email support@interserver.net about this';
				dialog('Error', $msg);
				myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__);
				$success = FALSE;
			} else {
				list($account_id, $user_id, $subscription_id, $webspace_id) = $extra;
				require_once(INCLUDE_ROOT . '/webhosting/class.pleskautomation.php');
				function_requirements('get_webhosting_ppa_instance');
				$ppaConnector = get_webhosting_ppa_instance($serverdata);
				$request = ['subscription_id' => $subscription_id ];
				$result = $ppaConnector->enableSubscription($request);
				try {
					\PPAConnector::checkResponse($result);
				} catch (\Exception $e) {
					echo 'Caught exception: ' . $e->getMessage() . "\n";
				}
				myadmin_log(self::$module, 'info', 'enableSubscription Called got ' . json_encode($result), __LINE__, __FILE__);
			}
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_WEB_PPA) {
			$license = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$pleskautomation = new Pleskautomation(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $pleskautomation->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Pleskautomation editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_pleskautomation', 'icons/database_warning_48.png', 'ReUsable Pleskautomation Licenses');
			$menu->add_link(self::$module, 'choice=none.pleskautomation_list', 'icons/database_warning_48.png', 'Pleskautomation Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.pleskautomation_licenses_list', 'whm/createacct.gif', 'List all Pleskautomation Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_pleskautomation_list', '/../vendor/detain/crud/src/crud/crud_pleskautomation_list.php');
		$loader->add_requirement('crud_reusable_pleskautomation', '/../vendor/detain/crud/src/crud/crud_reusable_pleskautomation.php');
		$loader->add_requirement('get_pleskautomation_licenses', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation.inc.php');
		$loader->add_requirement('get_pleskautomation_list', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation.inc.php');
		$loader->add_requirement('pleskautomation_licenses_list', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation_licenses_list.php');
		$loader->add_requirement('pleskautomation_list', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation_list.php');
		$loader->add_requirement('get_available_pleskautomation', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation.inc.php');
		$loader->add_requirement('activate_pleskautomation', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation.inc.php');
		$loader->add_requirement('get_reusable_pleskautomation', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/pleskautomation.inc.php');
		$loader->add_requirement('reusable_pleskautomation', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/reusable_pleskautomation.php');
		$loader->add_requirement('class.Pleskautomation', '/../vendor/detain/pleskautomation-webhosting/src/Pleskautomation.php');
		$loader->add_requirement('vps_add_pleskautomation', '/vps/addons/vps_add_pleskautomation.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_website_ppa_server', 'Default Plesk Automation Setup Server', NEW_WEBSITE_PPA_SERVER, SERVICE_TYPES_WEB_PPA);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_webhosting_ppa', 'Out Of Stock Plesk Automation Webhosting', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_WEBHOSTING_PPA'), array('0', '1'), array('No', 'Yes',));
	}

}
