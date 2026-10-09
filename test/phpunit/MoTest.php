<?php
/* Copyright (C) 2026 Nathan Pixodeo <nathan@pixodeo.net>
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
 * \file       test/phpunit/MoTest.php
 * \ingroup    mrp
 * \brief      PHPUnit test
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/mrp/class/mo.class.php';
require_once dirname(__FILE__).'/../../htdocs/categories/class/categorie.class.php';
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
 */
class MoTest extends CommonClassTest
{
	/**
	 * Deleting an MO must also delete its category links.
	 *
	 * @return void
	 */
	public function testMoDeleteRemovesCategoryLinks()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$mo = new Mo($db);
		$mo->initAsSpecimen();
		$mo->ref = 'MoTestCategoryDelete';
		$mo->fk_product = 1;
		$mo->mrptype = 0;
		$mo->qty = 1;
		$moid = $mo->create($user, 1);
		$this->assertGreaterThan(0, $moid, $mo->errorsToString());

		$category = new Categorie($db);
		$category->label = 'MoTestCategoryDelete';
		$category->type = Categorie::TYPE_MO;
		$categoryid = $category->create($user, 1);
		$this->assertGreaterThan(0, $categoryid, $category->errorsToString());

		$result = $mo->setCategories(array($categoryid));
		$this->assertGreaterThan(0, $result, $mo->errorsToString());

		$sql = "SELECT COUNT(*) AS nb FROM ".MAIN_DB_PREFIX."categorie_mo WHERE fk_mo = ".((int) $moid);
		$resql = $db->query($sql);
		$this->assertNotFalse($resql, $db->lasterror());
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertSame(1, (int) $obj->nb);

		$result = $mo->delete($user, 1);
		$this->assertGreaterThan(0, $result, $mo->errorsToString());

		$resql = $db->query($sql);
		$this->assertNotFalse($resql, $db->lasterror());
		$obj = $db->fetch_object($resql);
		$db->free($resql);
		$this->assertSame(0, (int) $obj->nb);
	}
}
