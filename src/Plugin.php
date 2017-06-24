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
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log(self::$module, 'info', 'Pleskautomation Activation', __LINE__, __FILE__);
			function_requirements('activate_pleskautomation');
			activate_pleskautomation($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
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
		$settings->add_text_setting(self::$module, 'Pleskautomation', 'pleskautomation_username', 'Pleskautomation Username:', 'Pleskautomation Username', $settings->get_setting('FANTASTICO_USERNAME'));
		$settings->add_text_setting(self::$module, 'Pleskautomation', 'pleskautomation_password', 'Pleskautomation Password:', 'Pleskautomation Password', $settings->get_setting('FANTASTICO_PASSWORD'));
		$settings->add_dropdown_setting(self::$module, 'Pleskautomation', 'outofstock_licenses_pleskautomation', 'Out Of Stock Pleskautomation Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_FANTASTICO'), array('0', '1'), array('No', 'Yes',));
	}

}
