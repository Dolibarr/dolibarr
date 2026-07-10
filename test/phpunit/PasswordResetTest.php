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

	/**
	 * Full arm -> verify -> confirm cycle on a real user.
	 */
	public function testRequestPasswordResetCycle()
	{
		global $conf, $user, $db;
		$conf = $this->savconf;

		// Create a throwaway user (rolled back by CommonClassTest transaction)
		$tmp = new User($db);
		$tmp->login = 'phpunit_pwdreset_'.dol_print_date(dol_now(), 'dayhourlog');
		$tmp->lastname = 'PwdResetTest';
		$tmp->email = 'phpunit_pwdreset@example.com';
		$idcreated = $tmp->create($user);
		$this->assertGreaterThan(0, $idcreated, 'user created');

		// Arm the reset
		$armed = $tmp->requestPasswordReset(3600);
		$this->assertIsString($armed);
		$this->assertStringStartsWith('r:', $armed);

		// Re-read pass_temp from DB
		$reloaded = new User($db);
		$reloaded->fetch($idcreated);
		$this->assertSame($armed, $reloaded->pass_temp, 'pass_temp stored verbatim');

		// Verify possession
		$hash = dolGetPasswordResetHash($reloaded->pass_temp, $reloaded->id);
		$this->assertSame(1, dolVerifyPasswordResetHash($reloaded->pass_temp, $reloaded->id, $hash));

		// Confirm: set a chosen password, pass_temp must be cleared
		$res = $reloaded->setPassword($user, 'Chosen-Passw0rd!2026', 0);
		$this->assertFalse(is_int($res) && $res < 0, 'setPassword accepted the chosen password: '.$reloaded->error);

		$after = new User($db);
		$after->fetch($idcreated);
		$this->assertEmpty($after->pass_temp, 'pass_temp cleared after confirm');
	}

	/**
	 * The reset email body carries the link and never a cleartext password.
	 */
	public function testResetEmailBodyIsLinkOnly()
	{
		global $conf, $langs, $db;
		$conf = $this->savconf;

		$u = new User($db);
		$u->id = 1;
		$u->login = 'admin';

		$link = 'https://portal.example.com/user/passwordforgotten.php?setnewpassword=1&username=admin&passworduidhash=DEADBEEF';
		$body = $u->getPasswordResetEmailContent($langs, $link);

		$this->assertStringContainsString($link, $body, 'body contains the reset link');
		$this->assertStringContainsString('passworduidhash=DEADBEEF', $body, 'link hash present');
		$this->assertStringNotContainsString('Password = ', $body, 'no cleartext password label');
	}
}
