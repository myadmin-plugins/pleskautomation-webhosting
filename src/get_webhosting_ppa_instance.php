<?php
/**
 * Gets a Plesk Automation Class instance for the given server.
 *
 * @param array|bool|false|int|string $server the server to get a Plesk Automation instance for, can be an array like from get_service or a server id, or false for default
 * @return \Detain\MyAdminPleskAutomation\PPAConnector the pleskautomation instance
 */
function get_webhosting_ppa_instance($server = false)
{
    $module = 'webhosting';
    $settings = \get_module_settings($module);
    if (is_array($server)) {
        $serverData = $server;
    } else {
        if ($server === false) {
            $server = NEW_WEBSITE_PPA_SERVER;
        }
        $serverData = get_service_master($server, $module);
    }
    $hash = $serverData[$settings['PREFIX'].'_key'];
    $ip = $serverData[$settings['PREFIX'].'_ip'];
    [$pleskUser, $pleskPass] = explode(':', html_entity_decode($hash));
    return \Detain\MyAdminPleskAutomation\PPAConnector::getInstance($ip, $pleskUser, $pleskPass);
}
