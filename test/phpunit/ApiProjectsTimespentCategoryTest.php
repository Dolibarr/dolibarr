<?php
/* Copyright (C) 2026 Dolibarr contributors
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
 *      \file       test/phpunit/ApiProjectsTimespentCategoryTest.php
 *      \ingroup    test
 *      \brief      GET /projects/alltimespent with a category filter must build
 *                  valid SQL (the categorie_project table has to be joined).
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';
// Restler unregisters existing loaders; keep PHPUnit's Composer loader available.
call_user_func(
	/** @return void */
	static function () {
		$autoloaders = spl_autoload_functions();
		try {
			require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
			require_once DOL_DOCUMENT_ROOT.'/projet/class/api_projects.class.php';
		} finally {
			foreach (array_reverse($autoloaders) as $autoloader) {
				if (!in_array($autoloader, spl_autoload_functions(), true)) {
					spl_autoload_register($autoloader, true, true);
				}
			}
		}
	}
);

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}

/**
 * Class ApiProjectsTimespentCategoryTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ApiProjectsTimespentCategoryTest extends CommonClassTest
{
	/**
	 * @var User|null API user before the test
	 */
	private $previousUser;

	/**
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->previousUser = DolibarrApiAccess::$user;
		$actingUser = $this->createMock(User::class);
		$actingUser->method('hasRight')->willReturn(true);
		$actingUser->id = 1;
		$actingUser->socid = 0;
		DolibarrApiAccess::$user = $actingUser;
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void
	{
		DolibarrApiAccess::$user = $this->previousUser;

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testListTimespentFilteredByCategory()
	{
		// Before the fix the query referenced alias "c" without joining llx_categorie_project (HTTP 503, Unknown column).
		$result = (new Projects())->listTimespent('t.rowid', 'ASC', 10, 0, '', 999999);
		$this->assertIsArray($result);
		$this->assertSame(array(), $result);
	}
}
