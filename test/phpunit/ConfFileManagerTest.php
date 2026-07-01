<?php
/* Copyright (C) 2026 ATM Consulting <contact@atm-consulting.fr>
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
 *      \file       test/phpunit/ConfFileManagerTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ConfFileManager class (conf.php build/parse/reuse).
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/conffilemanager.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of ConfFileManager
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ConfFileManagerTest extends CommonClassTest
{
	/**
	 * Evaluate a generated conf.php content and return the variables it defines.
	 *
	 * The content is written to a temporary file and included in an isolated scope, so the
	 * generated assignments are really executed by PHP (the strongest validity check).
	 *
	 * @param	string	$content	conf.php content to evaluate.
	 * @return	array<string,mixed>	The variables defined by the content.
	 */
	private function evalConfContent($content)
	{
		$tmpfile = tempnam(sys_get_temp_dir(), 'dolconftest');
		file_put_contents($tmpfile, $content);
		include $tmpfile;
		unlink($tmpfile);

		$vars = get_defined_vars();
		unset($vars['tmpfile'], $vars['content']);

		return $vars;
	}

	/**
	 * Test that the canvas exposes a coherent, non-empty and de-duplicated list of variables.
	 *
	 * @return	void
	 */
	public function testGetCanvasKeys()
	{
		$confmanager = new ConfFileManager();
		$keys = $confmanager->getCanvasKeys();

		$this->assertNotEmpty($keys);
		$this->assertSame($keys, array_unique($keys), 'Canvas keys must be unique');
		$this->assertContains('dolibarr_main_db_host', $keys);
		$this->assertContains('dolibarr_main_instance_unique_id', $keys);
		$this->assertContains('dolibarr_main_csrf_with_token', $keys);
	}

	/**
	 * Test that build() generates an exhaustive, valid conf.php with all the expected variables.
	 *
	 * @return	void
	 */
	public function testBuildExhaustiveAndValid()
	{
		$confmanager = new ConfFileManager();

		$values = array(
			'dolibarr_main_url_root' => 'http://localhost/dolibarr',
			'dolibarr_main_document_root' => '/var/www/dolibarr/htdocs',
			'dolibarr_main_db_host' => 'localhost',
			'dolibarr_main_db_name' => 'mydb',
			'dolibarr_main_db_user' => 'myuser',
			'dolibarr_main_instance_unique_id' => str_repeat('a', 64),
		);

		$content = $confmanager->build($values);

		// The whole generated file must be valid PHP.
		$this->assertTrue($confmanager->validateSyntax($content), 'Generated conf.php must be valid PHP');
		$this->assertStringStartsWith('<?php', $content);

		// Every canvas variable must appear (either as an active or a commented assignment).
		foreach ($confmanager->getCanvasKeys() as $key) {
			$this->assertMatchesRegularExpressionCompat('/^\s*(\/\/)?\$'.preg_quote($key, '/').'\s*=/m', $content, 'Variable '.$key.' missing from generated conf.php');
		}

		// Section headers are present.
		$this->assertStringContainsString('MAIN PARAMETERS', $content);
		$this->assertStringContainsString('SECURITY', $content);

		// Provided values are actually applied once the file is executed.
		$vars = $this->evalConfContent($content);
		$this->assertSame('localhost', $vars['dolibarr_main_db_host']);
		$this->assertSame('mydb', $vars['dolibarr_main_db_name']);
		// A value not provided falls back to the canvas default.
		$this->assertSame('llx_', $vars['dolibarr_main_db_prefix']);
		// A "comment if empty" variable left empty must NOT be defined (so the Dolibarr default applies).
		$this->assertArrayNotHasKey('dolibarr_auto_user', $vars, 'Empty comment-if-empty variable must stay commented');
	}

	/**
	 * Test that the secure default of dolibarr_login_badcharunauthorized round-trips as valid PHP.
	 *
	 * @return	void
	 */
	public function testBuildSecurityRawDefault()
	{
		$confmanager = new ConfFileManager();
		$content = $confmanager->build(array());

		$this->assertTrue($confmanager->validateSyntax($content));

		$vars = $this->evalConfContent($content);
		$this->assertSame(',@<>"\'', $vars['dolibarr_login_badcharunauthorized']);
		$this->assertIsArray($vars['dolibarr_main_stream_to_disable']);
	}

	/**
	 * Test that mandatory variables missing from the values fall back to the fresh-install defaults.
	 *
	 * When reusing a template, a variable that is commented out or absent is not parsed, so build() must
	 * fill it with the same default as a brand new install (the values pre-filled in the fileconf.php form).
	 *
	 * @return	void
	 */
	public function testMandatoryDefaultsMatchFreshInstall()
	{
		$confmanager = new ConfFileManager();

		$vars = $this->evalConfContent($confmanager->build(array()));

		$this->assertSame('mysqli', $vars['dolibarr_main_db_type'], 'Missing db_type must default to mysqli, not empty');
		$this->assertSame('localhost', $vars['dolibarr_main_db_host'], 'Missing db_host must default to localhost');
		$this->assertSame('dolibarr', $vars['dolibarr_main_db_name'], 'Missing db_name must default to dolibarr');
		$this->assertSame('llx_', $vars['dolibarr_main_db_prefix'], 'Missing db_prefix must default to llx_');
		$this->assertSame('utf8', $vars['dolibarr_main_db_character_set'], 'Missing character set must default to utf8');
		$this->assertSame('utf8_unicode_ci', $vars['dolibarr_main_db_collation'], 'Missing collation must default to utf8_unicode_ci');
		$this->assertSame('dolibarr', $vars['dolibarr_main_authentication'], 'Missing authentication must default to dolibarr');
	}

	/**
	 * Test that an empty dolibarr_main_csrf_with_token stays unset (commented), and is NOT turned into 0.
	 *
	 * The empty default must remain "absent" (no forcing) and must never be cast to the real value 0
	 * (which would force CSRF protection off). A value of '0' or '3' on the other hand is a real, active value.
	 *
	 * @return	void
	 */
	public function testCsrfWithTokenEmptyIsNotZero()
	{
		$confmanager = new ConfFileManager();

		// Empty default => commented assignment => variable not defined once the file is executed.
		$varsEmpty = $this->evalConfContent($confmanager->build(array()));
		$this->assertArrayNotHasKey('dolibarr_main_csrf_with_token', $varsEmpty, 'Empty value must stay unset, not become 0');

		// Explicit 0 (force CSRF off) is a real, active value kept as a string.
		$varsZero = $this->evalConfContent($confmanager->build(array('dolibarr_main_csrf_with_token' => '0')));
		$this->assertArrayHasKey('dolibarr_main_csrf_with_token', $varsZero);
		$this->assertSame('0', $varsZero['dolibarr_main_csrf_with_token']);

		// Explicit 3 (strongest) is kept as a string too.
		$varsThree = $this->evalConfContent($confmanager->build(array('dolibarr_main_csrf_with_token' => '3')));
		$this->assertSame('3', $varsThree['dolibarr_main_csrf_with_token']);
	}

	/**
	 * Test parse(): active assignments are read, commented lines ignored, unknown/deprecated/missing classified.
	 *
	 * @return	void
	 */
	public function testParse()
	{
		$confmanager = new ConfFileManager();

		$content = "<?php\n";
		$content .= "\$dolibarr_main_db_host='localhost';\n";
		$content .= "\$dolibarr_main_db_name='mydb';\n";
		$content .= "//\$dolibarr_main_db_user='ignored';\n";	// commented => not read
		$content .= "\$dolibarr_mycustom='42';\n";			// unknown / custom
		$content .= "\$dolibarr_main_cookie_cryptkey='legacy';\n";	// deprecated

		$parsed = $confmanager->parse($content);

		$this->assertArrayHasKey('dolibarr_main_db_host', $parsed['values']);
		$this->assertSame("'localhost'", $parsed['values']['dolibarr_main_db_host']);
		$this->assertArrayNotHasKey('dolibarr_main_db_user', $parsed['values'], 'Commented assignment must not be parsed');
		$this->assertContains('dolibarr_mycustom', $parsed['unknown']);
		$this->assertContains('dolibarr_main_cookie_cryptkey', $parsed['deprecated']);
		// A canvas variable absent from the content is reported as missing.
		$this->assertContains('dolibarr_main_instance_unique_id', $parsed['missing']);
	}

	/**
	 * Test the documented limitation: a multi-line assignment is not parsed (no false partial capture).
	 *
	 * @return	void
	 */
	public function testParseMultiLineLimitation()
	{
		$confmanager = new ConfFileManager();

		$content = "<?php\n\$dolibarr_main_disabled_modules=array(\n  'dav',\n);\n";
		$parsed = $confmanager->parse($content);

		$this->assertArrayNotHasKey('dolibarr_main_disabled_modules', $parsed['values'], 'Multi-line assignment must not be partially parsed');
	}

	/**
	 * Test buildFromTemplate(): values kept, instance id regenerated, deprecated commented, custom preserved.
	 *
	 * @return	void
	 */
	public function testBuildFromTemplate()
	{
		$confmanager = new ConfFileManager();

		$oldid = str_repeat('b', 64);
		$template = "<?php\n";
		$template .= "\$dolibarr_main_db_host='myhost';\n";
		$template .= "\$dolibarr_main_prod='1';\n";
		$template .= "\$dolibarr_main_instance_unique_id='".$oldid."';\n";
		$template .= "\$dolibarr_mycustom='custom-value';\n";
		$template .= "\$dolibarr_main_cookie_cryptkey='legacykey';\n";

		$result = $confmanager->buildFromTemplate($template);
		$content = $result['content'];

		// Generated content is valid PHP.
		$this->assertTrue($confmanager->validateSyntax($content));

		// Reuse the values executed in an isolated scope.
		$vars = $this->evalConfContent($content);
		$this->assertSame('myhost', $vars['dolibarr_main_db_host'], 'Template value must be kept');
		$this->assertSame('1', $vars['dolibarr_main_prod'], 'Template value must be kept');

		// The instance unique id is always regenerated (64 hex chars) and differs from the old one.
		$this->assertMatchesRegularExpressionCompat('/^[0-9a-f]{64}$/', $vars['dolibarr_main_instance_unique_id']);
		$this->assertNotSame($oldid, $vars['dolibarr_main_instance_unique_id'], 'Instance id must be regenerated');

		// Custom variable preserved verbatim.
		$this->assertSame('custom-value', $vars['dolibarr_mycustom']);
		$this->assertContains('dolibarr_mycustom', $result['unknown']);

		// Deprecated variable kept commented (so not defined after include) and reported.
		$this->assertArrayNotHasKey('dolibarr_main_cookie_cryptkey', $vars, 'Deprecated variable must stay commented');
		$this->assertStringContainsString("//\$dolibarr_main_cookie_cryptkey='legacykey';", $content);
		$this->assertContains('dolibarr_main_cookie_cryptkey', $result['deprecated']);
	}

	/**
	 * Test that an old key is pinned into dolcrypt_key when the database password is dolcrypt-encrypted.
	 *
	 * @return	void
	 */
	public function testBuildFromTemplatePinsOldKey()
	{
		$confmanager = new ConfFileManager();

		$oldid = str_repeat('c', 64);
		$template = "<?php\n";
		$template .= "\$dolibarr_main_instance_unique_id='".$oldid."';\n";
		$template .= "\$dolibarr_main_db_pass='dolcrypt:somethingencrypted';\n";

		$result = $confmanager->buildFromTemplate($template);
		$vars = $this->evalConfContent($result['content']);

		// The previous key must be pinned so dolcrypt-encrypted data stays decryptable after id regeneration.
		$this->assertSame($oldid, $vars['dolibarr_main_dolcrypt_key']);
	}

	/**
	 * Test that reusing a partial template fills empty/commented/absent mandatory variables.
	 *
	 * A variable commented out, absent or present-but-empty must fall back to the provided fallback value,
	 * then to the canvas (fresh-install) default, so a reused template never yields an empty mandatory value.
	 *
	 * @return	void
	 */
	public function testBuildFromTemplateFillsEmptyMandatory()
	{
		$confmanager = new ConfFileManager();

		$template = "<?php\n";
		$template .= "\$dolibarr_main_document_root='/var/www/htdocs';\n";	// active => kept
		$template .= "//\$dolibarr_main_data_root='/old/documents';\n";	// commented => must fall back
		$template .= "//\$dolibarr_main_db_host='';\n";			// commented => must fall back
		$template .= "\$dolibarr_main_db_type='';\n";			// present but empty => must fall back
		$template .= "\$dolibarr_main_db_name='mydb';\n";			// active => kept
		$template .= "\$dolibarr_mycustom='keepme';\n";			// custom => preserved

		$fallback = array('dolibarr_main_data_root' => '/detected/documents');

		$result = $confmanager->buildFromTemplate($template, $fallback);
		$vars = $this->evalConfContent($result['content']);

		// Active values kept.
		$this->assertSame('mydb', $vars['dolibarr_main_db_name']);
		$this->assertSame('/var/www/htdocs', $vars['dolibarr_main_document_root']);
		// Commented variable filled from the provided fallback.
		$this->assertSame('/detected/documents', $vars['dolibarr_main_data_root']);
		// Commented / empty variables without a fallback filled from the canvas (fresh-install) default.
		$this->assertSame('localhost', $vars['dolibarr_main_db_host']);
		$this->assertSame('mysqli', $vars['dolibarr_main_db_type']);
		// Custom variable preserved.
		$this->assertSame('keepme', $vars['dolibarr_mycustom']);
	}

	/**
	 * Test getConnectionParams(): paths/db read from a template, password decrypted (crypted: branch).
	 *
	 * @return	void
	 */
	public function testGetConnectionParams()
	{
		$confmanager = new ConfFileManager();

		$cryptedpass = 'crypted:'.dol_encode('secretpass');
		$template = "<?php\n";
		$template .= "\$dolibarr_main_document_root='/var/www/htdocs';\n";
		$template .= "\$dolibarr_main_url_root='http://localhost';\n";
		$template .= "\$dolibarr_main_db_host='dbhost';\n";
		$template .= "\$dolibarr_main_db_name='dbname';\n";
		$template .= "\$dolibarr_main_db_user='dbuser';\n";
		$template .= "\$dolibarr_main_db_prefix='llx_';\n";
		$template .= "\$dolibarr_main_db_pass='".$cryptedpass."';\n";

		$params = $confmanager->getConnectionParams($template);

		$this->assertSame('/var/www/htdocs', $params['document_root']);
		$this->assertSame('http://localhost', $params['url_root']);
		$this->assertSame('dbhost', $params['db_host']);
		$this->assertSame('dbname', $params['db_name']);
		$this->assertSame('dbuser', $params['db_user']);
		$this->assertSame('llx_', $params['db_prefix']);
		$this->assertSame('secretpass', $params['db_pass'], 'crypted: password must be decoded');
	}

	/**
	 * Test diff(): canvas variables absent from the parsed template are reported as new.
	 *
	 * @return	void
	 */
	public function testDiff()
	{
		$confmanager = new ConfFileManager();

		$values = array('dolibarr_main_db_host' => "'localhost'");
		$diff = $confmanager->diff($values);

		$this->assertArrayHasKey('newVars', $diff);
		$this->assertContains('dolibarr_main_instance_unique_id', $diff['newVars']);
		$this->assertNotContains('dolibarr_main_db_host', $diff['newVars']);
	}

	/**
	 * Test validateSyntax(): valid content (incl. multi-line) accepted, broken content rejected.
	 *
	 * @return	void
	 */
	public function testValidateSyntax()
	{
		$confmanager = new ConfFileManager();

		$valid = "<?php\n\$dolibarr_main_db_host='localhost';\n\$dolibarr_x=array(\n  'a' => 1,\n);\n";
		$broken = "<?php\n\$dolibarr_x='unterminated;\n";

		$this->assertTrue($confmanager->validateSyntax($valid), 'Valid multi-line content must pass');
		$this->assertFalse($confmanager->validateSyntax($broken), 'Unterminated string must fail');
	}

	/**
	 * Test resolveInstanceKey(): a fresh id is always produced and legacy keys flagged for commenting.
	 *
	 * @return	void
	 */
	public function testResolveInstanceKey()
	{
		$confmanager = new ConfFileManager();

		$res = $confmanager->resolveInstanceKey(array('dolibarr_main_cookie_cryptkey' => "'legacykey'"));

		$this->assertMatchesRegularExpressionCompat('/^[0-9a-f]{64}$/', $res['instanceKey']);
		$this->assertSame('legacykey', $res['oldKey']);
		$this->assertContains('dolibarr_main_cookie_cryptkey', $res['commentOut']);
	}

	/**
	 * Wrapper around assertMatchesRegularExpression to stay compatible with older PHPUnit.
	 *
	 * @param	string	$pattern	Regular expression.
	 * @param	string	$string		Subject string.
	 * @param	string	$message	Failure message.
	 * @return	void
	 */
	private function assertMatchesRegularExpressionCompat($pattern, $string, $message = '')
	{
		if (method_exists($this, 'assertMatchesRegularExpression')) {
			$this->assertMatchesRegularExpression($pattern, $string, $message);
		} else {
			// @phpstan-ignore method.notFound
			$this->assertRegExp($pattern, $string, $message);
		}
	}
}
