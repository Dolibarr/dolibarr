<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2023		Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/GetUrlLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/geturl.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class GetUrlLibTest extends CommonClassTest
{
	/**
	 * testResolveDNS
	 *
	 * @return	int
	 */
	public function testResolveDNS()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$modes = array('default', 'MAIN_DISABLE_DNS_GET_RECORD_FOR_IP_RESOLUTION');

		foreach ($modes as $mode) {
			if ($mode == 'MAIN_DISABLE_DNS_GET_RECORD_FOR_IP_RESOLUTION') {
				$conf->global->MAIN_DISABLE_DNS_GET_RECORD_FOR_IP_RESOLUTION = 1;
			} else {	// default
				unset($conf->global->MAIN_DISABLE_DNS_GET_RECORD_FOR_IP_RESOLUTION);
			}

			$result = resolveDns('192.16.0.1');
			print __METHOD__." 192.16.0.1 mode ".$mode." result=".$result."\n";
			$this->assertEquals('192.16.0.1', $result, 'Test resolveDNS 1 mode '.$mode);

			$result = resolveDns('::1');
			print __METHOD__." ::1 mode ".$mode." result=".$result."\n";
			$this->assertEquals('::1', $result, 'Test resolveDNS 2 mode '.$mode);

			$result = resolveDns('anamethatdoesnotexist123really');
			print __METHOD__." anamethatdoesnotexist123really mode ".$mode." result=".$result."\n";
			$this->assertTrue(in_array($result, array('anamethatdoesnotexist123really')), 'Test resolveDNS 4 mode '.$mode);

			// name if ip that resolve both on ipv4and ipv6

			$result = resolveDns('www.dolimed.com');
			print __METHOD__." www.dolimed.com mode ".$mode." result=".$result."\n";
			$this->assertTrue(in_array($result, array('104.21.7.66', '172.67.187.137')), 'Test resolveDNS 3 mode '.$mode);

			// name if ipv4 resolution only

			$result = resolveDns('ipv4.dolicloud.com');
			print __METHOD__." ipv4.dolicloud.com ".$mode." result=".$result."\n";
			$this->assertTrue(in_array($result, array('147.135.135.36')), 'Test resolveDNS 5 mode '.$mode);

			// name if ipv6 resolution only

			$result = resolveDns('ipv6.dolicloud.com');
			print __METHOD__." ipv6.dolicloud.com ".$mode." result=".$result."\n";
			if ($mode == 'MAIN_DISABLE_DNS_GET_RECORD_FOR_IP_RESOLUTION') {
				$this->assertTrue(in_array($result, array('ipv6.dolicloud.com')), 'Test resolveDNS 6 mode '.$mode);
			} else {
				$this->assertTrue(in_array($result, array('2001:41d0:302:1000::4:1437')), 'Test resolveDNS 6 mode '.$mode);
			}
		}
	}


	/**
	 * testGetRootURLFromURL
	 *
	 * @depends	testResolveDNS
	 * @return	int
	 */
	public function testGetRootURLFromURL()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = getRootURLFromURL('http://www.dolimed.com/screenshots/afile');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('http://www.dolimed.com', $result, 'Test 1');

		$result = getRootURLFromURL('https://www.dolimed.com/screenshots/afile');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('https://www.dolimed.com', $result, 'Test 2');

		$result = getRootURLFromURL('http://www.dolimed.com/screenshots');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('http://www.dolimed.com', $result);

		$result = getRootURLFromURL('https://www.dolimed.com/screenshots');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('https://www.dolimed.com', $result);

		$result = getRootURLFromURL('http://www.dolimed.com/');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('http://www.dolimed.com', $result);

		$result = getRootURLFromURL('https://www.dolimed.com/');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('https://www.dolimed.com', $result);

		$result = getRootURLFromURL('http://www.dolimed.com');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('http://www.dolimed.com', $result);

		$result = getRootURLFromURL('https://www.dolimed.com');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('https://www.dolimed.com', $result);

		return 1;
	}

	/**
	 * testGetDomainFromURL
	 *
	 * @depends	testGetRootURLFromURL
	 * @return	int
	 */
	public function testGetDomainFromURL()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Tests with param 0

		$result = getDomainFromURL('http://localhost');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('localhost', $result, 'Test localhost 0');

		$result = getDomainFromURL('http://localhost', 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('localhost', $result, 'Test localhost 1');

		$result = getDomainFromURL('https://dolimed.com');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed', $result, 'Test dolimed.com 0');

		$result = getDomainFromURL('http://www.dolimed.com/screenshots/afile');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed', $result, 'Test dolimed.com/... 0');

		$result = getDomainFromURL('http://www.with.dolimed.com/screenshots/afile');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed', $result, 'Test ...dolimed.com/ 0');

		// Tests with param 1

		$result = getDomainFromURL('https://dolimed.com', 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed.com', $result, 'Test dolimed.com 1');

		$result = getDomainFromURL('http://www.dolimed.com/screenshots/afile', 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed.com', $result, 'Test dolimed.com/... 1');

		$result = getDomainFromURL('http://www.with.dolimed.com/screenshots/afile', 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed.com', $result, 'Test .../dolimed.com 1');

		// Tests with param 2

		$result = getDomainFromURL('http://www.with.dolimed.com/screenshots/afile', 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('with.dolimed.com', $result, 'Test .../dolimed.com 2');

		// For domains with top domain on 2 levels

		$result = getDomainFromURL('https://www.with.dolimed.com.mx', 0);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed', $result, 'Test dolimed.com.mx 0');

		$result = getDomainFromURL('https://www.with.dolimed.com.mx', 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dolimed.com.mx', $result, 'Test dolimed.com.mx 1');

		$result = getDomainFromURL('https://www.with.dolimed.com.mx', 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('with.dolimed.com.mx', $result, 'Test dolimed.com.mx 2');


		// Test with url with login/pass

		$result = getDomainFromURL('https://mylogin:mypass@aaa.abc.mydomain.com', 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abc.mydomain.com', $result, 'Test https://mylogin:mypass@mydomain.com');


		// Test with email

		$result = getDomainFromURL('myemail@mydomain.com', 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('mydomain.com', $result, 'Test myemail@mydomain.com');


		return 1;
	}

	/**
	 * testRemoveHtmlComment
	 *
	 * @depends	testGetDomainFromURL
	 * @return	int
	 */
	public function testRemoveHtmlComment()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$result = removeHtmlComment('abc<!--[if lt IE 8]>aaaa<![endif]-->def');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abcdef', $result, 'Test 1');

		$result = removeHtmlComment('abc<!--[if lt IE 8]>aa-->bb<!--aa<![endif]-->def');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abcbbdef', $result, 'Test 1');

		return 1;
	}

	/**
	 * testGetURLContent
	 *
	 * @return int
	 */
	public function testGetURLContent()
	{
		global $conf;
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$url = 'ftp://dolibarr.org';
		$tmp = getURLContent($url);
		print __METHOD__." url=".$url." ".$tmp['curl_error_msg']."\n";

		$tmpvar = preg_match('/not supported|disabled/', $tmp['curl_error_msg']);
		$this->assertEquals(1, $tmpvar, "Did not find the /not supported|disabled/ in getURLContent error message. We should.");

		$DISABLEREMOTEACCESSTODOLIBARRFR = 1;

		if (empty($DISABLEREMOTEACCESSTODOLIBARRFR)) {
			$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
			$tmp = getURLContent($url, 'GET', '', 0);	// We do NOT follow
			print __METHOD__." url=".$url."\n";
			$this->assertEquals(301, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Test getURLContent '.$url.' - Should GET url 301 response');

			$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
			$tmp = getURLContent($url);		// We DO follow a page with return 300 so result should be 200
			print __METHOD__." url=".$url."\n";
			$this->assertEquals(200, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url 301 with a follow -> 200 but we get '.(empty($tmp['http_code']) ? 0 : $tmp['http_code']));
		}

		$url = 'http://localhost';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL

		$url = 'http://127.0.0.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 127.0.0.1 is not an external URL

		$url = 'http://127.0.2.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 127.0.2.1 is not an external URL

		$url = 'https://169.254.0.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 169.254.0.1 is not an external URL

		$url = 'http://[::1]';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because [::1] is not an external URL

		/*$url = 'localtest.me';
		 $tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		 print __METHOD__." url=".$url."\n";
		 $this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL
		 */

		$url = 'http://192.0.0.192';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL but on an IP in blacklist
		print __METHOD__." url=".$url." tmp['http_code'] = ".(empty($tmp['http_code']) ? 0 : $tmp['http_code'])."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Access should be refused and was not');	// Test we receive an error because ip is in blacklist

		$url = 'https://2130706433';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals("Host is a numeric address that is not allowed", (empty($tmp['curl_error_msg']) ? "" : $tmp['curl_error_msg']), 'Should GET error Not a valid ip address');	// Test we receive an error because 169.254.0.1 is not an external URL

		$url = 'https://0x7f000001';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals("Host is a numeric address that is not allowed", (empty($tmp['curl_error_msg']) ? "" : $tmp['curl_error_msg']), 'Should GET error Not a valid ip address');	// Test we receive an error because 169.254.0.1 is not an external URL

		$url = 'https://017700000001';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals("Host is a numeric address that is not allowed", (empty($tmp['curl_error_msg']) ? "" : $tmp['curl_error_msg']), 'Should GET error Not a valid ip address');	// Test we receive an error because 169.254.0.1 is not an external URL

		return 0;
	}

	/**
	 * An IPv6 address that carries a private IPv4 address (IPv4-mapped, NAT64, 6to4...) must be refused like this IPv4.
	 *
	 * @return void
	 */
	public function testIsIPAllowedEmbeddedIPv4()
	{
		$this->assertEquals('127.0.0.1', getIPv4EmbeddedInIPv6('::ffff:127.0.0.1'));
		$this->assertEquals('127.0.0.1', getIPv4EmbeddedInIPv6('::ffff:7f00:1'));
		$this->assertEquals('127.0.0.1', getIPv4EmbeddedInIPv6('::127.0.0.1'));
		$this->assertEquals('127.0.0.1', getIPv4EmbeddedInIPv6('64:ff9b::7f00:1'));
		$this->assertEquals('127.0.0.1', getIPv4EmbeddedInIPv6('2002:7f00:1::'));
		$this->assertEquals('', getIPv4EmbeddedInIPv6('127.0.0.1'));
		$this->assertEquals('', getIPv4EmbeddedInIPv6('::1'));
		$this->assertEquals('', getIPv4EmbeddedInIPv6('::'));
		$this->assertEquals('', getIPv4EmbeddedInIPv6('2606:4700:4700::1111'));
		$this->assertEquals('', getIPv4EmbeddedInIPv6('not an ip'));

		// External URL only: refused
		$refused = array(
			'127.0.0.1', '10.1.2.3', '169.254.169.254', '::1', 'fe80::1',
			'::ffff:127.0.0.1', '::ffff:7f00:1', '::ffff:10.0.0.1', '::ffff:192.168.1.1', '::ffff:169.254.169.254',
			'::127.0.0.1', '64:ff9b::7f00:1', '64:ff9b::a00:1', '2002:7f00:1::', '2002:a00:1::',
			'100.64.0.1', '100.127.255.254', '198.18.0.1', '198.19.255.254', '::ffff:100.64.0.1',
		);
		foreach ($refused as $ip) {
			$this->assertNotEmpty(isIPAllowed($ip, 0), $ip.' must be refused when only external URLs are allowed');
		}

		// External URL only: accepted
		$accepted = array('8.8.8.8', '100.63.255.255', '100.128.0.1', '198.17.255.255', '198.20.0.1', '2606:4700:4700::1111', '::ffff:8.8.8.8', '64:ff9b::808:808', '2002:808:808::');
		foreach ($accepted as $ip) {
			$this->assertEmpty(isIPAllowed($ip, 0), $ip.' must be accepted when only external URLs are allowed');
		}

		// Local URL only: a mapped private IPv4 is local, a mapped public IPv4 is not
		$this->assertEmpty(isIPAllowed('::ffff:127.0.0.1', 1));
		$this->assertEmpty(isIPAllowed('::1', 1));
		$this->assertNotEmpty(isIPAllowed('::ffff:8.8.8.8', 1));
		// The assertion on a metadata server reached with $localurl = 1 is not kept here: it relies on 169.254.169.254
		// being in $arrayofmetadataserver, which was added by #40763 and is not on this branch.
	}
}
