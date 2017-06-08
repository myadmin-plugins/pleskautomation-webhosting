<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_pleskautomation define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Pleskautomation Webhosting',
	'description' => 'Allows selling of Pleskautomation Server and VPS License Types.  More info at https://www.netenberg.com/pleskautomation.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a pleskautomation license. Allow 10 minutes for activation.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-pleskautomation-webhosting',
	'repo' => 'https://github.com/detain/myadmin-pleskautomation-webhosting',
	'version' => '1.0.0',
	'type' => 'licenses',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminPleskautomation\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminPleskautomation\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminPleskautomation\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminPleskautomation\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminPleskautomation\Plugin', 'Menu'] */
	],
];
