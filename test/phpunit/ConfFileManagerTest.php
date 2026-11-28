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
 *      \brief      PHPUnit test for the ConfFileManager class (conf.php generation).
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
	}

	/**
	 * Test that the canvas documents every variable of conf.php.example.
	 *
	 * @return	void
	 */
	public function testCanvasCoversConfExample()
	{
		// testBuildExhaustiveAndValid() only walks canvas -> output, so a variable added to
		// conf.php.example alone would go unnoticed. This walks example -> canvas.
		$confmanager = new ConfFileManager();
		$keys = $confmanager->getCanvasKeys();

		$example = file_get_contents(DOL_DOCUMENT_ROOT.'/conf/conf.php.example');
		$this->assertNotFalse($example, 'conf.php.example must be readable');

		$nbfound = preg_match_all('/^\s*(?:\/\/\s*)?\$(dolibarr_\w+)\s*=/m', $example, $matches);
		$this->assertGreaterThan(0, $nbfound, 'No variable found in conf.php.example');

		foreach (array_unique($matches[1]) as $key) {
			$this->assertContains($key, $keys, 'Variable '.$key.' is documented in conf.php.example but absent from the canvas');
		}
	}

	/**
	 * Test that build() generates an exhaustive, valid conf.php with all the expected variables.
	 *
	 * @return	void
	 */
	public function testBuildExhaustiveAndValid()
	{
		$confmanager = new ConfFileManager();

		$values = [
			'dolibarr_main_url_root' => 'http://localhost/dolibarr',
			'dolibarr_main_document_root' => '/var/www/dolibarr/htdocs',
			'dolibarr_main_db_host' => 'localhost',
			'dolibarr_main_db_name' => 'mydb',
			'dolibarr_main_db_user' => 'myuser',
			'dolibarr_main_instance_unique_id' => str_repeat('a', 64),
		];

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
		$content = $confmanager->build([]);

		$this->assertTrue($confmanager->validateSyntax($content));

		$vars = $this->evalConfContent($content);
		$this->assertSame(',@<>"\'', $vars['dolibarr_login_badcharunauthorized']);
		$this->assertIsArray($vars['dolibarr_main_stream_to_disable']);
	}

	/**
	 * Test that the generated stream list keeps the zip stream enabled, like master.inc.php does when the variable is absent.
	 *
	 * @return	void
	 */
	public function testStreamToDisableKeepsZip()
	{
		$confmanager = new ConfFileManager();
		$vars = $this->evalConfContent($confmanager->build([]));

		$this->assertNotContains('zip', $vars['dolibarr_main_stream_to_disable'], 'The zip stream is required by the Excel import');
	}

	/**
	 * Test that a Windows path keeps its backslashes, as written by the installer before the canvas.
	 *
	 * @return	void
	 */
	public function testBuildKeepsWindowsPath()
	{
		$confmanager = new ConfFileManager();
		$vars = $this->evalConfContent($confmanager->build(['dolibarr_main_document_root' => 'C:\\dolibarr\\htdocs']));

		$this->assertSame('C:\\dolibarr\\htdocs', $vars['dolibarr_main_document_root']);
	}

	/**
	 * Test that mandatory variables missing from the values fall back to the fresh-install defaults.
	 *
	 * @return	void
	 */
	public function testMandatoryDefaultsMatchFreshInstall()
	{
		$confmanager = new ConfFileManager();

		$vars = $this->evalConfContent($confmanager->build([]));

		$this->assertSame('mysqli', $vars['dolibarr_main_db_type'], 'Missing db_type must default to mysqli, not empty');
		$this->assertSame('localhost', $vars['dolibarr_main_db_host'], 'Missing db_host must default to localhost');
		$this->assertSame('dolibarr', $vars['dolibarr_main_db_name'], 'Missing db_name must default to dolibarr');
		$this->assertSame('llx_', $vars['dolibarr_main_db_prefix'], 'Missing db_prefix must default to llx_');
		$this->assertSame('utf8', $vars['dolibarr_main_db_character_set'], 'Missing character set must default to utf8');
		$this->assertSame('utf8_unicode_ci', $vars['dolibarr_main_db_collation'], 'Missing collation must default to utf8_unicode_ci');
		$this->assertSame('dolibarr', $vars['dolibarr_main_authentication'], 'Missing authentication must default to dolibarr');
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
