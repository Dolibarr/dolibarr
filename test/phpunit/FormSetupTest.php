<?php
/* Copyright (C) 2026 Umar Farooq <umarpak995@gmail.com>
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
 */

/**
 * \file       test/phpunit/FormSetupTest.php
 * \ingroup    test
 * \brief      PHPUnit test for FormSetup and FormSetupItem password encryption (Issue #40492).
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/admin.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.formsetup.class.php';

if (empty($user->id)) {
	$user->fetch(1);
	$user->loadRights();
}

/**
 * Class FormSetupTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class FormSetupTest extends CommonClassTest
{
	/**
	 * Clean up test constants.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $db;
		dolibarr_del_const($db, 'TEST_FORMSETUP_PASS_KEY', 1);
		dolibarr_del_const($db, 'TEST_FORMSETUP_GENERIC_KEY', 1);
		parent::tearDown();
	}

	/**
	 * Test password encryption on save, decryption on load, and masking on output.
	 *
	 * @return void
	 */
	public function testPasswordEncryptionAndDecryption()
	{
		global $db, $conf;

		$testKey = 'TEST_FORMSETUP_PASS_KEY';
		$testSecret = 'SecretPassword!#123';

		dolibarr_del_const($db, $testKey, 1);

		$item = new FormSetupItem($testKey);
		$item->setAsPassword();
		$item->fieldValue = $testSecret;
		$saveRes = $item->saveConfValue();

		$this->assertEquals(1, $saveRes);

		// Check raw stored value is encrypted with dolcrypt: prefix
		$sql = "SELECT value FROM ".MAIN_DB_PREFIX."const WHERE name = '".$db->escape($testKey)."'";
		$resql = $db->query($sql);
		$row = $db->fetch_object($resql);
		$this->assertNotEmpty($row);
		$this->assertStringStartsWith('dolcrypt:', $row->value);

		// Reload from conf into a new item
		unset($conf->global->{$testKey});
		$conf->setValues($db);

		$item2 = new FormSetupItem($testKey);
		$item2->setAsPassword();
		$loadRes = $item2->loadValueFromConf();

		$this->assertTrue($loadRes);
		$this->assertEquals($testSecret, $item2->fieldValue);

		// Masked output field check
		$masked = $item2->generateOutputField();
		$this->assertEquals(str_repeat('*', dol_strlen($testSecret)), $masked);

		// Input field check
		$inputHtml = $item2->generateInputField();
		$this->assertStringContainsString('type="password"', $inputHtml);
		$this->assertStringContainsString('value="'.dol_escape_htmltag($testSecret).'"', $inputHtml);
	}

	/**
	 * Test generic password encryption on save, decryption on load, and masking on output.
	 *
	 * @return void
	 */
	public function testGenericPasswordEncryptionAndDecryption()
	{
		global $db, $conf;

		$testKey = 'TEST_FORMSETUP_GENERIC_KEY';
		$testSecret = 'GenericSecret456$%^';

		dolibarr_del_const($db, $testKey, 1);

		$item = new FormSetupItem($testKey);
		$item->setAsGenericPassword();
		$item->fieldValue = $testSecret;
		$saveRes = $item->saveConfValue();

		$this->assertEquals(1, $saveRes);

		$sql = "SELECT value FROM ".MAIN_DB_PREFIX."const WHERE name = '".$db->escape($testKey)."'";
		$resql = $db->query($sql);
		$row = $db->fetch_object($resql);
		$this->assertNotEmpty($row);
		$this->assertStringStartsWith('dolcrypt:', $row->value);

		unset($conf->global->{$testKey});
		$conf->setValues($db);

		$item2 = new FormSetupItem($testKey);
		$item2->setAsGenericPassword();
		$loadRes = $item2->loadValueFromConf();

		$this->assertTrue($loadRes);
		$this->assertEquals($testSecret, $item2->fieldValue);

		$masked = $item2->generateOutputField();
		$this->assertEquals(str_repeat('*', dol_strlen($testSecret)), $masked);
	}
}
