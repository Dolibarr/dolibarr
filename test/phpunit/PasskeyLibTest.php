<?php
/* Copyright (C) 2026		Guenter Lukas			<gl@gl.co.at>
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
 *      \file       test/phpunit/PasskeyLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the passkey (WebAuthn) authentication mode
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/passkey.lib.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/user.class.php';
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
class PasskeyLibTest extends CommonClassTest
{
	/**
	 * @var array<string,mixed>	Values of $conf->file and $conf->global modified by the tests
	 */
	private $savedconf = array();

	/**
	 * Save the setup modified by the tests
	 *
	 * @return  void
	 */
	protected function setUp(): void
	{
		global $conf;

		parent::setUp();

		$this->savedconf = array(
			'main_authentication' => $conf->file->main_authentication,
			'dol_main_url_root' => $conf->file->dol_main_url_root,
			'main_force_https' => $conf->file->main_force_https,
			'MAIN_AUTHENTICATION_PASSKEY_RPID' => getDolGlobalString('MAIN_AUTHENTICATION_PASSKEY_RPID'),
			'MAIN_AUTHENTICATION_PASSKEY_REQUIRED' => getDolGlobalString('MAIN_AUTHENTICATION_PASSKEY_REQUIRED'),
		);
	}

	/**
	 * Restore the setup modified by the tests
	 *
	 * @return  void
	 */
	protected function tearDown(): void
	{
		global $conf;

		$conf->file->main_authentication = $this->savedconf['main_authentication'];
		$conf->file->dol_main_url_root = $this->savedconf['dol_main_url_root'];
		$conf->file->main_force_https = $this->savedconf['main_force_https'];
		$conf->global->MAIN_AUTHENTICATION_PASSKEY_RPID = $this->savedconf['MAIN_AUTHENTICATION_PASSKEY_RPID'];
		$conf->global->MAIN_AUTHENTICATION_PASSKEY_REQUIRED = $this->savedconf['MAIN_AUTHENTICATION_PASSKEY_REQUIRED'];

		parent::tearDown();
	}

	/**
	 * testPasskeyGetOriginAndRpId
	 *
	 * @return  void
	 */
	public function testPasskeyGetOriginAndRpId()
	{
		global $conf;

		$conf->global->MAIN_AUTHENTICATION_PASSKEY_RPID = '';
		$conf->file->main_force_https = '';

		$conf->file->dol_main_url_root = 'https://ERP.example.com/dolibarr/htdocs';
		$this->assertEquals('https://erp.example.com', passkeyGetOrigin(), 'Origin must not contain the path and must be lower case');
		$this->assertEquals('erp.example.com', passkeyGetRpId());

		$conf->file->dol_main_url_root = 'http://localhost:8000 ';
		$this->assertEquals('http://localhost:8000', passkeyGetOrigin(), 'Origin must keep the port and ignore spaces');
		$this->assertEquals('localhost', passkeyGetRpId());

		$conf->file->dol_main_url_root = 'http://erp.example.com';
		$conf->file->main_force_https = '1';
		$this->assertEquals('https://erp.example.com', passkeyGetOrigin(), 'Origin must use https when https is forced');

		$conf->global->MAIN_AUTHENTICATION_PASSKEY_RPID = 'Example.com';
		$this->assertEquals('example.com', passkeyGetRpId(), 'RP ID must use the setup when defined');

		$conf->file->dol_main_url_root = 'auto';
		$this->assertEquals('', passkeyGetOrigin(), 'Origin must be empty when the URL root is not a full URL');
	}

	/**
	 * testPasskeyCheckClientOrigin
	 *
	 * @return  void
	 */
	public function testPasskeyCheckClientOrigin()
	{
		global $conf;

		$conf->file->main_force_https = '';
		$conf->file->dol_main_url_root = 'https://erp.example.com';

		$this->assertTrue(passkeyCheckClientOrigin('{"origin":"https://erp.example.com"}'));
		$this->assertFalse(passkeyCheckClientOrigin('{"origin":"https://evil.erp.example.com"}'), 'A subdomain must be refused');
		$this->assertFalse(passkeyCheckClientOrigin('{"origin":"https://evil-erp.example.com"}'), 'A similar domain must be refused');
		$this->assertFalse(passkeyCheckClientOrigin('{"origin":"http://erp.example.com"}'), 'Another scheme must be refused');
		$this->assertFalse(passkeyCheckClientOrigin('{"origin":["https://erp.example.com"]}'), 'A non string origin must be refused');
		$this->assertFalse(passkeyCheckClientOrigin('not a json'));
	}

	/**
	 * testPasskeyBase64UrlDecode
	 *
	 * @return  void
	 */
	public function testPasskeyBase64UrlDecode()
	{
		$this->assertEquals("\xfb\xff\xbf", passkeyBase64UrlDecode('-_-_'));
		$this->assertEquals('abc', passkeyBase64UrlDecode('YWJj'));
		$this->assertEquals('', passkeyBase64UrlDecode(''));
		$this->assertEquals('', passkeyBase64UrlDecode('YW+j'), 'Standard base64 characters must be refused');
		$this->assertEquals('', passkeyBase64UrlDecode('YW J'));
	}

	/**
	 * testPasskeyIsRequiredForUser
	 *
	 * @return  void
	 */
	public function testPasskeyIsRequiredForUser()
	{
		global $conf, $db;

		$tmpuser = new User($db);
		$tmpuser->admin = 0;
		$tmpuser->passkey_required = null;

		$conf->file->main_authentication = 'dolibarr';
		$conf->global->MAIN_AUTHENTICATION_PASSKEY_REQUIRED = '2';
		$this->assertFalse(passkeyIsRequiredForUser($tmpuser), 'Never required when the mode passkey is not enabled');

		if (!passkeyIsAvailable()) {
			$this->markTestSkipped('The WebAuthn library needs PHP 8.0 or higher with openssl');
		}

		$conf->file->main_authentication = 'dolibarr,passkey';

		$conf->global->MAIN_AUTHENTICATION_PASSKEY_REQUIRED = '0';
		$this->assertFalse(passkeyIsRequiredForUser($tmpuser));

		$conf->global->MAIN_AUTHENTICATION_PASSKEY_REQUIRED = '1';
		$this->assertFalse(passkeyIsRequiredForUser($tmpuser), 'Not required for a user when required for administrators only');
		$tmpuser->admin = 1;
		$this->assertTrue(passkeyIsRequiredForUser($tmpuser), 'Required for an administrator');

		$conf->global->MAIN_AUTHENTICATION_PASSKEY_REQUIRED = '2';
		$tmpuser->admin = 0;
		$this->assertTrue(passkeyIsRequiredForUser($tmpuser), 'Required for all users');

		$tmpuser->passkey_required = 0;
		$this->assertFalse(passkeyIsRequiredForUser($tmpuser), 'The setup of the user has priority over the global setup');

		$conf->global->MAIN_AUTHENTICATION_PASSKEY_REQUIRED = '0';
		$tmpuser->passkey_required = 1;
		$this->assertTrue(passkeyIsRequiredForUser($tmpuser), 'The setup of the user has priority over the global setup');
	}
}
