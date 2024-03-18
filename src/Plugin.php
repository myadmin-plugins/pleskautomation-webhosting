<?php

namespace Detain\MyAdminPleskAutomation;

use Detain\MyAdminPleskAutomation\PPAConnector;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminPleskAutomation
 */
class Plugin
{
    public static $name = 'PleskAutomation Webhosting';
    public static $description = 'Plesk Automation is a scalable, multi-server automation solution for shared hosting, giving growing hosters the power, performance, and scale previously only available to hosting giants.  More info at http://www.odin.com/support/automation-suite/ppa/';
    public static $help = '';
    public static $module = 'webhosting';
    public static $type = 'service';

    /**
     * Plugin constructor.
     */
    public function __construct()
    {
    }

    /**
     * @return array
     */
    public static function getHooks()
    {
        return [
            self::$module.'.settings' => [__CLASS__, 'getSettings'],
            self::$module.'.activate' => [__CLASS__, 'getActivate'],
            self::$module.'.reactivate' => [__CLASS__, 'getReactivate'],
            self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
            self::$module.'.terminate' => [__CLASS__, 'getTerminate'],
            'function.requirements' => [__CLASS__, 'getRequirements']
        ];
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Detain\MyAdminPleskAutomation\PPAFailedRequestException
     * @throws \Detain\MyAdminPleskAutomation\PPAMalformedRequestException
     * @throws \Exception
     * @throws \SmartyException
     */
    public static function getActivate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_PPA')) {
            $serviceClass = $event->getSubject();
            myadmin_log(self::$module, 'info', 'PleskAutomation Activation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $settings = get_module_settings(self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $data = $GLOBALS['tf']->accounts->read($serviceClass->getCustid());
            $ip = $serverdata[$settings['PREFIX'].'_ip'];
            $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
            $hostname = $serviceClass->getHostname();
            if (trim($hostname) == '') {
                $hostname = $serviceClass->getId().'.server.com';
            }
            $password = website_get_password($serviceClass->getId(), $serviceClass->getCustid());
            $username = get_new_webhosting_username($serviceClass->getId(), $hostname, $serviceClass->getServer());
            include_once __DIR__.'/get_webhosting_ppa_instance.php';
            $ppaConnector = get_webhosting_ppa_instance($serverdata);
            $serviceTemplateId = 46;
            if (!isset($data['name']) || trim($data['name']) == '') {
                $data['name'] = str_replace('@', ' ', $data['account_lid']);
            }
            [$first, $last] = explode(' ', $data['name']);
            $requestPerson = [
                'first_name' => $first,
                'last_name' => $last,
                'company_name' => $data['company'] ?? ''
            ];
            $requestAddress = [
                'street_name' => $data['address'] ?? '',
                'address2' => $data['address2'] ?? '',
                'zipcode' => $data['zip'] ?? '',
                'city' => $data['city'] ?? '',
                'country' => convert_country_iso2($data['country']),
                'state' => $data['state'] ?? ''
            ];
            $requestPhone = [
                'country_code' => '1',
                'area_code' => '',
                'phone_num' => $data['phone'] ?? '',
                'ext_num' => ''
            ];
            $request = [
                'person' => $requestPerson,
                'address' => $requestAddress,
                'phone' => $requestPhone,
                'email' => $data['account_lid']
            ];
            try {
                $result = $ppaConnector->addAccount($request);
                //echo "Result:";var_dump($result);echo "\n";
                PPAConnector::checkResponse($result);
            } catch (\Exception $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'addAccount Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'ppa', 'addAccount', $request, $result, $serviceClass->getId());
            $accountId = $result['result']['account_id'];
            if (!is_array($extra)) {
                $extra = [];
            }
            $extra[0] = $accountId;
            $db = get_module_db(self::$module);
            $serExtra = $db->real_escape(myadmin_stringify($extra));
            $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='{$ip}', {$settings['PREFIX']}_extra='{$serExtra}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
            myadmin_log(self::$module, 'info', "addAccount Got Account ID: {$accountId}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $request = [
                'account_id' => $accountId,
                'auth' => [
                    'login' => $username,
                    'password' => $password
                ],
                'person' => $requestPerson,
                'address' => $requestAddress,
                'phone' => $requestPhone,
                'email' => $data['account_lid']
            ];
            try {
                $result = $ppaConnector->addAccountMember($request);
                //echo "Result:";var_dump($result);echo "\n";
                PPAConnector::checkResponse($result);
            } catch (\Exception $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'addAccountMember Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'ppa', 'addAccountMember', $request, $result, $serviceClass->getId());
            $userId = $result['result']['user_id'];
            $username = $db->real_escape($username);
            $extra[1] = $userId;
            $serExtra = $db->real_escape(myadmin_stringify($extra));
            $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='{$ip}', {$settings['PREFIX']}_extra='{$serExtra}', {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
            myadmin_log(self::$module, 'info', "addAccountMember Got Account ID: {$userId}  Username: {$username}  Password: {$password}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $request = [
                'account_id' => $accountId,
                'service_template_id' => $serviceTemplateId
            ];
            try {
                $result = $ppaConnector->activateSubscription($request);
                //echo "Result:";var_dump($result);echo "\n";
                PPAConnector::checkResponse($result);
            } catch (\Exception $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'activatesubscription Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'ppa', 'activateSubscription', $request, $result, $serviceClass->getId());
            $subscriptoinId = $result['result']['subscription_id'];
            $extra[2] = $subscriptoinId;
            $serExtra = $db->real_escape(myadmin_stringify($extra));
            $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='{$ip}', {$settings['PREFIX']}_extra='{$serExtra}', {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
            myadmin_log(self::$module, 'info', "activateSubscription Got Subscription ID: {$subscriptoinId}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            /*
              $request = array(
              'subscription_id' => $subscriptoinId,
              'get_resources' => TRUE,
              );
              $result = $ppaConnector->getSubscription($request);
              echo "Result:";var_dump($result);echo "\n";
              try {
                PPAConnector::checkResponse($result);
              } catch (\Exception $e) {
              echo 'Caught exception: '.$e->getMessage().PHP_EOL;
              }
              print_r($result);
             */
            $request = [
                'new_webspace' => [
                    'sub_id' => $subscriptoinId,
                    'domain' => $hostname,
                    'resources' => [
                        ['rt_id' => 1000084], // plesk_integration Subscription
                        ['rt_id' => 1000115], // pleskwebiis_hosting IIS Webspace
                        ['rt_id' => 1000087], // plesk_db_hosting MySQL database
                        //array('rt_id' => 1000091), // plesk_db_hosting Microsoft SQL database
                        ['rt_id' => 1000152], // plesk_db_hosting Microsoft SQL database
                        ['rt_id' => 1000132], // plesk__mail PostFix Mail
                    ]
                ]
            ];
            try {
                $result = $ppaConnector->{'pleskintegration.createWebspace'}($request);
                //echo "Result:";var_dump($result);echo "\n";
                PPAConnector::checkResponse($result);
            } catch (\Exception $e) {
                echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                myadmin_log(self::$module, 'info', 'createWebspace Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            request_log(self::$module, $serviceClass->getCustid(), __FUNCTION__, 'ppa', 'createWebspace', $request, $result, $serviceClass->getId());
            $webspaceId = $result['result']['webspace_id'];
            $extra[3] = $webspaceId;
            $serExtra = $db->real_escape(myadmin_stringify($extra));
            $db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_ip='{$ip}', {$settings['PREFIX']}_extra='{$serExtra}', {$settings['PREFIX']}_username='{$username}' where {$settings['PREFIX']}_id='{$serviceClass->getId()}'", __LINE__, __FILE__);
            myadmin_log(self::$module, 'info', "Got Website ID: {$webspaceId}", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            if (is_numeric($webspaceId)) {
                //myadmin_log(self::$module, 'info', "Success, Response: " . var_export($vesta->response, TRUE), __LINE__, __FILE__, self::$module, $serviceClass->getId());;
                website_welcome_email($serviceClass->getId());
                $event['success'] = true;
            } else {
                add_output('Error Creating Website');
                myadmin_log(self::$module, 'info', 'Failure, Response: '.var_export($result, true), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            }
            /*
              $request = array(
              'subscription_id' => $subscriptoinId,
              );
              $result = $ppaConnector->removeSubscription($request);
              //echo "Result:";var_dump($result);echo "\n";
              try {
                PPAConnector::checkResponse($result);
              } catch (\Exception $e) {
              echo 'Caught exception: '.$e->getMessage().PHP_EOL;
              }
              echo "Success Removing Subscription\n";
              $request = array(
              'account_id' => $accountId,
              );
              $result = $ppaConnector->removeAccount($request);
              //echo "Result:";var_dump($result);echo "\n";
              try {
                PPAConnector::checkResponse($result);
              } catch (\Exception $e) {
              echo 'Caught exception: '.$e->getMessage().PHP_EOL;
              }
              echo "Success Removing Account.\n";
             */
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Detain\MyAdminPleskAutomation\Detain\MyAdminPleskAutomation\PPAFailedRequestException
     * @throws \Detain\MyAdminPleskAutomation\Detain\MyAdminPleskAutomation\PPAMalformedRequestException
     */
    public static function getReactivate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_PPA')) {
            $serviceClass = $event->getSubject();
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
            if (count($extra) == 0) {
                function_requirements('get_pleskautomation_info_from_domain');
                include_once __DIR__.'/get_pleskautomation_info_from_domain.php';
                $extra = \get_pleskautomation_info_from_domain($serviceClass->getHostname());
            }
            if (count($extra) == 0) {
                $msg = 'Blank/Empty Plesk Subscription Info, Email support@interserver.net about this';
                dialog('Error', $msg);
                myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['success'] = false;
            } else {
                //list($accountId, $userId, $subscriptoinId, $webspaceId) = $extra;
                $subscriptoinId = $extra[2];
                function_requirements('get_webhosting_ppa_instance');
                include_once __DIR__.'/get_webhosting_ppa_instance.php';
                $ppaConnector = get_webhosting_ppa_instance($serverdata);
                $request = ['subscription_id' => $subscriptoinId];
                $result = $ppaConnector->enableSubscription($request);
                try {
                    PPAConnector::checkResponse($result);
                } catch (\Exception $e) {
                    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                }
                myadmin_log(self::$module, 'info', 'enableSubscription Called got '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @throws \Detain\MyAdminPleskAutomation\PPAMalformedRequestException
     */
    public static function getDeactivate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_PPA')) {
            $serviceClass = $event->getSubject();
            myadmin_log(self::$module, 'info', 'PleskAutomation Deactivation', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            if (count($extra) == 0) {
                $msg = 'Blank/Empty Plesk Subscription Info, so either dont know what to remove or nothing to remove';
                dialog('Error', $msg);
                myadmin_log(self::$module, 'info', $msg, __LINE__, __FILE__, self::$module, $serviceClass->getId());
            } else {
                //list($accountId, $userId, $subscriptoinId, $webspaceId) = $extra;
                $subscriptoinId = $extra[2];
                include_once __DIR__.'/get_webhosting_ppa_instance.php';
                $ppaConnector = get_webhosting_ppa_instance($serverdata);
                $request = [
                    'subscription_id' => $subscriptoinId
                ];
                $result = $ppaConnector->disableSubscription($request);
                //echo "Result:";var_dump($result);echo "\n";
                try {
                    \Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
                    $event['success'] = true;
                } catch (PPAFailedRequestException $e) {
                    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                    $event['success'] = false;
                } catch (\Exception $e) {
                    echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                    $event['success'] = false;
                }
                myadmin_log(self::$module, 'info', 'disableSubscription Called got '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     * @return boolean|null
     * @throws \Detain\MyAdminPleskAutomation\PPAFailedRequestException
     * @throws \Detain\MyAdminPleskAutomation\PPAMalformedRequestException
     */
    public static function getTerminate(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_PPA')) {
            $event->stopPropagation();
            $serviceClass = $event->getSubject();
            myadmin_log(self::$module, 'info', 'PleskAutomation Termination', __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $extra = run_event('parse_service_extra', $serviceClass->getExtra(), self::$module);
            $serverdata = get_service_master($serviceClass->getServer(), self::$module);
            if (count($extra) == 0) {
                /**
                 * @TODO Double Check The Server To Ensure The Site Isnt There And Our Information Is Correct, If Not Then Update It With Correect Info And Use That To Terminate Instead.
                 */
                //$msg = 'Blank/Empty Plesk Subscription Info, so either dont know what to remove or nothing to remove';
                //dialog('Error', $msg);
                myadmin_log(self::$module, 'info', 'Blank/Empty Plesk Subscription Info, so either dont know what to remove or nothing to remove', __LINE__, __FILE__, self::$module, $serviceClass->getId());
                return true;
            } else {
                //list($accountId, $userId, $subscriptoinId, $webspaceId) = $extra;
                $subscriptoinId = $extra[2];
                try {
                    include_once __DIR__.'/get_webhosting_ppa_instance.php';
                    $ppaConnector = get_webhosting_ppa_instance($serverdata);
                } catch (\Exception $e) {
                    myadmin_log(self::$module, 'info', 'PPAConnector::getInstance Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $event['success'] = false;
                    return false;
                }
                $request = [
                    'subscription_id' => $subscriptoinId
                ];
                try {
                    $result = $ppaConnector->disableSubscription($request);
                } catch (\Exception $e) {
                    myadmin_log(self::$module, 'info', 'ppaConnector->disableSubscription Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $event['success'] = false;
                    return false;
                }
                //echo "Result:";var_dump($result);echo "\n";
                try {
                    \Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
                } catch (\Exception $e) {
                    myadmin_log(self::$module, 'info', 'PPAConnector::checkResponse Caught exception: '.$e->getMessage(), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                    $event['success'] = false;
                    return false;
                }
                /*
                  $request = array(
                  'subscription_id' => $subscriptoinId,
                  );
                  $result = $ppaConnector->removeSubscription($request);
                  //echo "Result:";var_dump($result);echo "\n";
                  try {
                    \Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
                  } catch (\Exception $e) {
                  echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                  }
                  echo "Success Removing Subscription\n";
                  $request = array(
                  'account_id' => $accountId,
                  );
                  $result = $ppaConnector->removeAccount($request);
                  //echo "Result:";var_dump($result);echo "\n";
                  try {
                    \Detain\MyAdminPleskAutomation\PPAConnector::checkResponse($result);
                  } catch (\Exception $e) {
                      echo 'Caught exception: '.$e->getMessage().PHP_EOL;
                  }
                  echo "Success Removing Account.\n";
                 */
                myadmin_log(self::$module, 'info', 'disableSubscription Called got '.json_encode($result), __LINE__, __FILE__, self::$module, $serviceClass->getId());
                return true;
            }
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getChangeIp(GenericEvent $event)
    {
        if ($event['category'] == get_service_define('WEB_PPA')) {
            $serviceClass = $event->getSubject();
            $settings = get_module_settings(self::$module);
            $pleskautomation = new PleskAutomation(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
            myadmin_log(self::$module, 'info', 'IP Change - (OLD:'.$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__, self::$module, $serviceClass->getId());
            $result = $pleskautomation->editIp($serviceClass->getIp(), $event['newip']);
            if (isset($result['faultcode'])) {
                myadmin_log(self::$module, 'error', 'PleskAutomation editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__, self::$module, $serviceClass->getId());
                $event['status'] = 'error';
                $event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
            } else {
                $GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getId(), $serviceClass->getCustid());
                $serviceClass->set_ip($event['newip'])->save();
                $event['status'] = 'ok';
                $event['status_text'] = 'The IP Address has been changed.';
            }
            $event->stopPropagation();
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getMenu(GenericEvent $event)
    {
        $menu = $event->getSubject();
        if ($GLOBALS['tf']->ima == 'admin') {
            $menu->add_link(self::$module, 'choice=none.reusable_pleskautomation', '/images/myadmin/to-do.png', _('ReUsable PleskAutomation Licenses'));
            $menu->add_link(self::$module, 'choice=none.pleskautomation_list', '/images/myadmin/to-do.png', _('PleskAutomation Licenses Breakdown'));
            $menu->add_link(self::$module.'api', 'choice=none.pleskautomation_licenses_list', '/images/whm/createacct.gif', _('List all PleskAutomation Licenses'));
        }
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Plugins\Loader $this->loader
         */
        $loader = $event->getSubject();
        $loader->add_requirement('get_pleskautomation_info_from_domain', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/get_pleskautomation_info_from_domain.php');
        $loader->add_requirement('get_webhosting_ppa_instance', '/../vendor/detain/myadmin-pleskautomation-webhosting/src/get_webhosting_ppa_instance.php');
    }

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
        $settings->setTarget('module');
        $settings->add_select_master(_(self::$module), _('Default Servers'), self::$module, 'new_website_ppa_server', _('Default Plesk Automation Setup Server'), defined('NEW_WEBSITE_PPA_SERVER') ? NEW_WEBSITE_PPA_SERVER : 0, get_service_define('WEB_PPA'));
        $settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_webhosting_ppa', _('Out Of Stock Plesk Automation Webhosting'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_WEBHOSTING_PPA'), ['0', '1'], ['No', 'Yes']);
        $settings->setTarget('global');
    }
}
