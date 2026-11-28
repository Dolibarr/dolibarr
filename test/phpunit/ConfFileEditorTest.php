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
 *      \file       test/phpunit/ConfFileEditorTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ConfFileEditor class (in place edition of conf.php).
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/conffileeditor.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of ConfFileEditor
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ConfFileEditorTest extends CommonClassTest
{
	/**
	 * A conf.php with the statements an administrator may add around the assignments.
	 */
	const TEMPLATE = <<<'PHP'
<?php
// Instance template
@include_once '/etc/dolibarr/secrets.php';
$_SERVER['DOLIBARR_MAIN_SECURITY_FORCECSP'] = "default-src 'self'";
$dolibarr_main_url_root = 'http://erp.test';   // public URL
$dolibarr_main_db_pass = getenv('DB_PASS');
$dolibarr_main_db_port=3306;
$dolibarr_main_stream_to_disable = array(
	'ftp',
	'glob',
);
if (getenv('DEV')) {
	$dolibarr_main_prod = '0';
}
$dolibarr_main_instance_unique_id = "0123abcd";

PHP;

	/**
	 * Execute a conf.php content and return the variables it defines.
	 *
	 * @param	string	$content	conf.php content.
	 * @return	array<string,mixed>
	 */
	private function runConf($content)
	{
		$tmpfile = tempnam(sys_get_temp_dir(), 'dolconfeditor');
		file_put_contents($tmpfile, $content);
		$vars = (static function ($file) {
			include $file;
			unset($file);
			return get_defined_vars();
		})($tmpfile);
		unlink($tmpfile);

		return $vars;
	}

	/**
	 * Only top-level assignments are read, with their literal value or null for an expression.
	 *
	 * @return	void
	 */
	public function testParseReadsTopLevelAssignments()
	{
		$editor = new ConfFileEditor();
		$parsed = $editor->parse(self::TEMPLATE);

		$this->assertSame('http://erp.test', $parsed['dolibarr_main_url_root']['literal']);
		$this->assertSame('3306', $parsed['dolibarr_main_db_port']['literal']);
		$this->assertSame('0123abcd', $parsed['dolibarr_main_instance_unique_id']['literal']);
		$this->assertSame("getenv('DB_PASS')", $parsed['dolibarr_main_db_pass']['raw']);
		$this->assertNull($parsed['dolibarr_main_db_pass']['literal'], 'An expression has no literal value');
		$this->assertStringContainsString("'glob'", $parsed['dolibarr_main_stream_to_disable']['raw']);
		$this->assertArrayNotHasKey('dolibarr_main_prod', $parsed, 'An assignment inside a block is not top level');
	}

	/**
	 * Assignments that are not plain top-level '=' statements are ignored.
	 *
	 * @return	void
	 */
	public function testParseIgnoresOtherStatements()
	{
		$editor = new ConfFileEditor();
		$content = <<<'PHP'
<?php
// $dolibarr_main_db_host='commented';
if (getenv('X')) $dolibarr_main_db_name = 'conditional';
$dolibarr_main_db_user .= 'suffix';
$dolibarr_main_db_type == 'mysqli';

PHP;

		$this->assertSame([], $editor->parse($content));
	}

	/**
	 * When a variable is assigned twice, the last assignment is the one returned, as PHP applies it.
	 *
	 * @return	void
	 */
	public function testParseReturnsLastAssignment()
	{
		$editor = new ConfFileEditor();
		$parsed = $editor->parse("<?php\n\$dolibarr_main_db_host='first';\n\$dolibarr_main_db_host='second';\n");

		$this->assertSame('second', $parsed['dolibarr_main_db_host']['literal']);
	}

	/**
	 * Replacing a value changes only that value: the rest of the file is kept byte for byte.
	 *
	 * @return	void
	 */
	public function testSetValueReplacesOnlyTheValue()
	{
		$editor = new ConfFileEditor();
		$result = $editor->setValue(self::TEMPLATE, 'dolibarr_main_instance_unique_id', 'ffff9999');

		$this->assertSame(
			str_replace('"0123abcd"', "'ffff9999'", self::TEMPLATE),
			$result
		);
		$this->assertSame('ffff9999', $this->runConf($result)['dolibarr_main_instance_unique_id']);
	}

	/**
	 * A value written in the middle of a line keeps the comment that follows it.
	 *
	 * @return	void
	 */
	public function testSetValueKeepsTrailingComment()
	{
		$editor = new ConfFileEditor();
		$result = $editor->setValue(self::TEMPLATE, 'dolibarr_main_url_root', 'https://new.test');

		$this->assertStringContainsString("\$dolibarr_main_url_root = 'https://new.test';   // public URL\n", $result);
	}

	/**
	 * A missing variable is added at the end of the file.
	 *
	 * @return	void
	 */
	public function testSetValueAddsMissingVariable()
	{
		$editor = new ConfFileEditor();
		$result = $editor->setValue(self::TEMPLATE, 'dolibarr_main_dolcrypt_key', 'abc');

		$this->assertStringStartsWith(self::TEMPLATE, $result);
		$this->assertSame('abc', $this->runConf($result)['dolibarr_main_dolcrypt_key']);
	}

	/**
	 * A missing variable is added before a closing tag, so it is still PHP code.
	 *
	 * @return	void
	 */
	public function testSetValueAddsBeforeClosingTag()
	{
		$editor = new ConfFileEditor();
		$result = $editor->setValue("<?php\n\$dolibarr_main_db_host='localhost';\n?>\n", 'dolibarr_main_db_name', 'erp');

		$this->assertSame("<?php\n\$dolibarr_main_db_host='localhost';\n\$dolibarr_main_db_name='erp';\n?>\n", $result);
	}

	/**
	 * Quotes and backslashes are escaped, so the value read back by PHP is the one given.
	 *
	 * @return	void
	 */
	public function testSetValueEscapesSpecialChars()
	{
		$editor = new ConfFileEditor();
		$value = "a'b\\c\"d\$e";
		$result = $editor->setValue(self::TEMPLATE, 'dolibarr_main_db_pass', $value);

		$this->assertSame($value, $this->runConf($result)['dolibarr_main_db_pass']);
	}

	/**
	 * A commented assignment no longer applies and gets a note line above it.
	 *
	 * @return	void
	 */
	public function testCommentOutDisablesTheAssignment()
	{
		$editor = new ConfFileEditor();
		$result = $editor->commentOut(self::TEMPLATE, 'dolibarr_main_stream_to_disable', 'deprecated');

		$this->assertStringContainsString("// deprecated\n//\$dolibarr_main_stream_to_disable = array(\n//\t'ftp',\n//\t'glob',\n//);\n", $result);
		$vars = $this->runConf($result);
		$this->assertArrayNotHasKey('dolibarr_main_stream_to_disable', $vars);
		$this->assertSame('http://erp.test', $vars['dolibarr_main_url_root']);
	}

	/**
	 * Commenting an assignment that shares its line with other code does not comment that code.
	 *
	 * @return	void
	 */
	public function testCommentOutKeepsCodeOnTheSameLine()
	{
		$editor = new ConfFileEditor();
		$content = "<?php\n\$dolibarr_main_db_host='a'; \$dolibarr_main_cookie_cryptkey='old'; \$dolibarr_main_db_name='b';\n";
		$result = $editor->commentOut($content, 'dolibarr_main_cookie_cryptkey', 'deprecated');

		$vars = $this->runConf($result);
		$this->assertArrayNotHasKey('dolibarr_main_cookie_cryptkey', $vars);
		$this->assertSame('a', $vars['dolibarr_main_db_host']);
		$this->assertSame('b', $vars['dolibarr_main_db_name']);
	}

	/**
	 * Every edition keeps a valid PHP file.
	 *
	 * @return	void
	 */
	public function testEditionsKeepValidPhp()
	{
		$editor = new ConfFileEditor();

		$result = $editor->setValue(self::TEMPLATE, 'dolibarr_main_instance_unique_id', 'x');
		$result = $editor->setValue($result, 'dolibarr_main_dolcrypt_key', 'y');
		$result = $editor->commentOut($result, 'dolibarr_main_stream_to_disable', 'deprecated');
		$result = $editor->commentOut($result, 'dolibarr_main_db_port', 'deprecated');

		$this->assertNotEmpty(token_get_all($result, TOKEN_PARSE), 'token_get_all() throws a ParseError on invalid PHP');
	}
}
