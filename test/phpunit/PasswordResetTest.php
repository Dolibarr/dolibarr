<?php
/* Copyright (C) 2026 ATM Consulting
 * License GPL-3.0-or-later (see header of other test files).
 */

global $conf, $user, $langs, $db;

if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC', '1');
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX', '1');
if (! defined("NOLOGIN"))         define("NOLOGIN", '1');
if (! defined("NOSESSION"))       define("NOSESSION", '1');

require_once dirname(__FILE__).'/../../htdocs/main.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
require_once dirname(__FILE__).'/../../htdocs/user/class/user.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class PasswordResetTest extends CommonClassTest
{
	/**
	 * Hash round-trip: possession OK, wrong hash and wrong user rejected.
	 */
	public function testResetHashRoundtrip()
	{
		global $conf;
		$conf = $this->savconf;

		$secret = 'r:20990101000000:abcDEF123456';   // far-future expiry
		$hash = dolGetPasswordResetHash($secret, 1);

		$this->assertNotEmpty($hash);
		$this->assertSame(1, dolVerifyPasswordResetHash($secret, 1, $hash), 'valid possession');
		$this->assertSame(0, dolVerifyPasswordResetHash($secret, 1, 'garbagehash'), 'bad hash');
		$this->assertSame(0, dolVerifyPasswordResetHash($secret, 2, $hash), 'wrong user id');
		$this->assertSame(0, dolVerifyPasswordResetHash('', 1, $hash), 'empty secret');
	}

	/**
	 * Expired token is rejected with -1.
	 */
	public function testResetHashExpired()
	{
		global $conf;
		$conf = $this->savconf;

		$secret = 'r:20000101000000:abcDEF123456';   // past expiry
		$hash = dolGetPasswordResetHash($secret, 1);
		$this->assertSame(-1, dolVerifyPasswordResetHash($secret, 1, $hash));
	}

	/**
	 * Legacy pass_temp (no r: prefix) never expires (backward-compat).
	 */
	public function testResetHashLegacyNoTtl()
	{
		global $conf;
		$conf = $this->savconf;

		$secret = 'legacyplaintemp';
		$hash = dolGetPasswordResetHash($secret, 1);
		$this->assertSame(1, dolVerifyPasswordResetHash($secret, 1, $hash));
	}
}
