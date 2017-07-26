<?php

namespace Detain\MyAdminPleskAutomation;

use Detain\MyAdminPleskAutomation\PPAFailedRequestException;
use Detain\MyAdminPleskAutomation\PPAMalformedRequestException;
use Detain\MyAdminPleskAutomation\PPADomainDoesNotExistException;
use XML_RPC2_Client;

/**
 * Parallels Plesk Automation connector class provicding xml/rpc2 access to the service
 */
class PPAConnector {
	static protected $xmlrpcProxy;

	/**
	 * PPAConnector constructor.
	 */
	public function __construct() {
		/* this stuff was up top */
		if (!isset($GLOBALS['HTTP_RAW_POST_DATA']))
			$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
		//require_once('Zend/XmlRpc/Client.php');			// http://framework.zend.com/manual/1.12/en/zend.xmlrpc.client.html
		//require('XML_RPC.php');							// http://gggeek.github.io/phpxmlrpc/
	}

	/**
	 * @param $ipAddress
	 * @param $login
	 * @param $password
	 * @return mixed
	 */
	public static function getInstance($ipAddress, $login, $password) {
		$password = str_replace('?', '%3F', $password);
		if (!self::$xmlrpcProxy) {
			// Here go communication parameters for our management node
/*
			// Zend/XmlRpc
			$xmlrpcClient = new Zend_XmlRpc_Client("https://{$ipAddress}:8440/RPC2");
			$httpClient = $xmlrpcClient->getHttpClient();
			$httpClient->setAuth($login, $password, Zend_Http_Client::AUTH_BASIC);
			$httpClient->setConfig(array('timeout' => 45));
			self::$xmlrpcProxy = $xmlrpcClient->getProxy('pem'); //The pem prefix for API method names
*/
/*
			// XML_RPC
			$url = "https://{$login}:{$password}@{$ipAddress}:8440/RPC2";
			$options = array(
				'prefix' => 'pem.',
				'debug' => FALSE,
				'sslverify' => FALSE,
			);
			$xmlrpcClient = new xmlrpc_client($url, $options);
*/
			if (!isset($GLOBALS['HTTP_RAW_POST_DATA']))
				$GLOBALS['HTTP_RAW_POST_DATA'] = file_get_contents('php://input');
			// XML/RPC2
			$url = "https://{$login}:{$password}@{$ipAddress}:8440/RPC2";
			//echo "$url\n";exit;
			$options = [
				'prefix' => 'pem.',
				'debug' => FALSE,
				'sslverify' => FALSE
			];
			$xmlrpcClient = \XML_RPC2_Client::create($url, $options);
			self::$xmlrpcProxy = $xmlrpcClient;
		}
		return self::$xmlrpcProxy;
	}

	/**
	 * processing the response
	 *
	 * @param $response
	 * @return bool
	 * @throws \Detain\MyAdminPleskAutomation\PPAFailedRequestException
	 * @throws \Detain\MyAdminPleskAutomation\PPAMalformedRequestException
	 */
	public static function checkResponse($response) {
		if (isset($response['status'])) {
			if ($response['status'] != 0) {
				// Here should go some error handling
				throw new PPAFailedRequestException($response['error_message']);
			} else {
				return TRUE;
			}
		} else {
			throw new PPAMalformedRequestException('Malformed answer from POA');
		}
	}

}
