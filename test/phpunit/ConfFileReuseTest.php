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
 *      \file       test/phpunit/ConfFileReuseTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the ConfFileReuse class (reuse of a conf.php at install time).
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/conffilereuse.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests of ConfFileReuse
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ConfFileReuseTest extends CommonClassTest
{
	const OLD_ID = 'template-instance-id';

	/**
	 * Build a conf.php template with a given database password line.
	 *
	 * @param	string	$passLine	Right-hand side of $dolibarr_main_db_pass (PHP code).
	 * @param	string	$extra		Extra lines added at the end.
	 * @return	string
	 */
	private function template($passLine, $extra = '')
	{
		return "<?php\n// Hosting template\n\$dolibarr_main_db_name='erp';\n\$dolibarr_main_db_pass=".$passLine.";\n\$dolibarr_main_instance_unique_id='".self::OLD_ID."';\n".$extra;
	}

	/**
	 * Execute a conf.php content and return the variables it defines.
	 *
	 * @param	string	$content	conf.php content.
	 * @return	array<string,mixed>
	 */
	private function runConf($content)
	{
		$tmpfile = tempnam(sys_get_temp_dir(), 'dolconfreuse');
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
	 * New database: the instance id is regenerated, nothing else changes.
	 *
	 * @return	void
	 */
	public function testNewDatabaseRegeneratesInstanceId()
	{
		$reuse = new ConfFileReuse();
		$template = $this->template("'secret'");
		$result = $reuse->apply($template, ['dolibarr_main_instance_unique_id' => self::OLD_ID], false, false);

		$newId = $this->runConf($result['content'])['dolibarr_main_instance_unique_id'];
		$this->assertSame(1, preg_match('/^[0-9a-f]{64}$/', $newId), 'New id is 64 hex chars');
		$this->assertSame(str_replace(self::OLD_ID, $newId, $template), $result['content']);
		$this->assertSame([ConfFileReuse::ACTION_INSTANCE_ID_REGENERATED], $result['actions']);
	}

	/**
	 * Database already installed: the instance id is kept so data encrypted in database stay readable.
	 *
	 * @return	void
	 */
	public function testExistingDatabaseKeepsInstanceId()
	{
		$reuse = new ConfFileReuse();
		$template = $this->template("'secret'");
		$result = $reuse->apply($template, ['dolibarr_main_instance_unique_id' => self::OLD_ID], true, false);

		$this->assertSame($template, $result['content']);
		$this->assertSame([ConfFileReuse::ACTION_INSTANCE_ID_KEPT_DATABASE], $result['actions']);
	}

	/**
	 * An instance id written as an expression is never changed.
	 *
	 * @return	void
	 */
	public function testInstanceIdExpressionIsKept()
	{
		$reuse = new ConfFileReuse();
		$template = "<?php\n\$dolibarr_main_instance_unique_id=getenv('DOLI_ID');\n";
		$result = $reuse->apply($template, ['dolibarr_main_instance_unique_id' => 'fromenv'], false, false);

		$this->assertSame($template, $result['content']);
		$this->assertSame([ConfFileReuse::ACTION_INSTANCE_ID_KEPT_EXPRESSION], $result['actions']);
	}

	/**
	 * A dolcrypt: password is re-encrypted with the new instance id: readable with it, no more with the old one.
	 *
	 * @return	void
	 */
	public function testDolcryptPasswordIsReencryptedWithNewId()
	{
		$reuse = new ConfFileReuse();
		$template = $this->template("'".dolEncrypt('secret', self::OLD_ID)."'");
		$result = $reuse->apply($template, ['dolibarr_main_instance_unique_id' => self::OLD_ID], false, false);

		$vars = $this->runConf($result['content']);
		$this->assertSame('secret', dolDecrypt($vars['dolibarr_main_db_pass'], $vars['dolibarr_main_instance_unique_id']));
		$this->assertNotSame('secret', dolDecrypt($vars['dolibarr_main_db_pass'], self::OLD_ID));
		$this->assertContains(ConfFileReuse::ACTION_DB_PASS_REENCRYPTED, $result['actions']);
	}

	/**
	 * When dolcrypt_key is set, it decrypts the password before and after: the password is left as written.
	 *
	 * @return	void
	 */
	public function testDolcryptKeyKeepsPasswordUnchanged()
	{
		$reuse = new ConfFileReuse();
		$encrypted = dolEncrypt('secret', 'shared-key');
		$template = $this->template("'".$encrypted."'", "\$dolibarr_main_dolcrypt_key='shared-key';\n");
		$result = $reuse->apply($template, ['dolibarr_main_instance_unique_id' => self::OLD_ID, 'dolibarr_main_dolcrypt_key' => 'shared-key'], false, false);

		$vars = $this->runConf($result['content']);
		$this->assertSame($encrypted, $vars['dolibarr_main_db_pass']);
		$this->assertSame('shared-key', $vars['dolibarr_main_dolcrypt_key']);
		$this->assertNotContains(ConfFileReuse::ACTION_DB_PASS_REENCRYPTED, $result['actions']);
	}

	/**
	 * With the encryption box checked, a clear password is written as dolcrypt: with the new instance id.
	 *
	 * @return	void
	 */
	public function testClearPasswordIsEncryptedWhenAsked()
	{
		$reuse = new ConfFileReuse();
		$result = $reuse->apply($this->template("'secret'"), ['dolibarr_main_instance_unique_id' => self::OLD_ID], false, true);

		$vars = $this->runConf($result['content']);
		$this->assertStringStartsWith('dolcrypt:', $vars['dolibarr_main_db_pass']);
		$this->assertSame('secret', dolDecrypt($vars['dolibarr_main_db_pass'], $vars['dolibarr_main_instance_unique_id']));
		$this->assertContains(ConfFileReuse::ACTION_DB_PASS_ENCRYPTED, $result['actions']);
	}

	/**
	 * A clear password stays in clear when the box is not checked, a crypted: one is never changed.
	 *
	 * @return	void
	 */
	public function testClearAndCryptedPasswordsAreKept()
	{
		$reuse = new ConfFileReuse();

		$this->assertNull($reuse->reencryptDbPass('secret', 'old', 'new', false));
		$this->assertNull($reuse->reencryptDbPass('crypted:'.dol_encode('secret'), 'old', 'new', true));
		$this->assertNull($reuse->reencryptDbPass('', 'old', 'new', true));
	}

	/**
	 * A password written as an expression is never changed, even with the encryption box checked.
	 *
	 * @return	void
	 */
	public function testPasswordExpressionIsKept()
	{
		$reuse = new ConfFileReuse();
		$template = $this->template("getenv('DB_PASS')");
		$result = $reuse->apply($template, ['dolibarr_main_instance_unique_id' => self::OLD_ID], true, true);

		$this->assertSame($template, $result['content']);
		$this->assertContains(ConfFileReuse::ACTION_DB_PASS_EXPRESSION, $result['actions']);
	}

	/**
	 * A dolcrypt: password that the current key can not decrypt stops the reuse instead of writing a wrong value.
	 *
	 * @return	void
	 */
	public function testUndecryptablePasswordThrows()
	{
		$reuse = new ConfFileReuse();
		$template = $this->template("'".dolEncrypt('secret', 'another-key')."'");

		$this->expectException(Exception::class);
		$reuse->apply($template, ['dolibarr_main_instance_unique_id' => self::OLD_ID], false, false);
	}
}
