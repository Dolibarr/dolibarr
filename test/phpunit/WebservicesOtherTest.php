<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/WebservicesOtherTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once NUSOAP_PATH.'/nusoap.php';        // Include SOAP
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$conf->global->MAIN_UMASK = '0666';


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class WebservicesOtherTest extends CommonClassTest
{
	/**
	 * testWSOtherGetVersions
	 *
	 * @return int
	 */
	public function testWSOtherGetVersions()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$WS_DOL_URL = DOL_MAIN_URL_ROOT.'/webservices/server_other.php';
		$WS_METHOD  = 'getVersions';
		$ns = 'http://www.dolibarr.org/ns/';

		// Set the WebService URL
		print __METHOD__." create nusoap_client for URL=".$WS_DOL_URL."\n";
		$soapclient = new nusoap_client($WS_DOL_URL);
		if ($soapclient) {
			$soapclient->soap_defencoding = 'UTF-8';
			$soapclient->decodeUTF8(false);
		}

		// Call the WebService method and store its result in $result.
		$authentication = array(
			'dolibarrkey' => getDolGlobalString('WEBSERVICES_KEY'),
			'sourceapplication' => 'DEMO',
			'login' => 'admin',
			'password' => 'admin',
			'entity' => ''
		);

		// Test URL
		$result = '';
		$parameters = array('authentication' => $authentication);
		print __METHOD__." call method ".$WS_METHOD."\n";
		try {
			$result = $soapclient->call($WS_METHOD, $parameters, $ns, '');
		} catch (SoapFault $exception) {
			echo $exception;
			$result = 0;
		}
		if (!empty($result['faultstring'])) {
			print $result['faultstring']."\n";
			$result = 0;
		}
		if (! $result) {
			//var_dump($soapclient);
			print 'Error: '.$soapclient->error_str;
			print "\n<br>\n";
			print $soapclient->request;
			print "\n<br>\n";
			print $soapclient->response;
			print "\n";
		}

		print __METHOD__." count(result)=".count($result)."\n";
		$this->assertEquals('OK', $result['result']['result_code']);

		// Test method that does not exists
		$WS_METHOD = 'methodthatdoesnotexists';
		$result = '';
		$parameters = array('authentication' => $authentication);
		print __METHOD__." call method ".$WS_METHOD."\n";
		try {
			$result = $soapclient->call($WS_METHOD, $parameters, $ns, '');
		} catch (SoapFault $exception) {
			echo $exception;
			$result = 0;
		}
		if (! $result || !empty($result['faultstring'])) {
			//var_dump($soapclient);
			print $soapclient->error_str;
			print "\n<br>\n";
			print $soapclient->request;
			print "\n<br>\n";
			print $soapclient->response;
			print "\n";
		}

		print __METHOD__." count(result)=".(is_array($result) ? count($result) : 0)."\n";
		$this->assertEquals("SOAP-ENV:Client: Operation 'methodthatdoesnotexists' is not defined in the WSDL for this service", $soapclient->error_str);

		return $result;
	}
}
