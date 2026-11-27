<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/ApiListCountTest.php
 *      \ingroup    test
 *      \brief      The parameter count=1 of the REST list endpoints returns the
 *                  number of records the same call returns as a list.
 */

use Luracast\Restler\RestException;

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
			require_once DOL_DOCUMENT_ROOT.'/societe/class/api_thirdparties.class.php';
			require_once DOL_DOCUMENT_ROOT.'/societe/class/api_contacts.class.php';
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/api_invoices.class.php';
			require_once DOL_DOCUMENT_ROOT.'/product/class/api_products.class.php';
			require_once DOL_DOCUMENT_ROOT.'/projet/class/api_projects.class.php';
			require_once DOL_DOCUMENT_ROOT.'/cron/class/api_cronjobs.class.php';
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
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class ApiListCountTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ApiListCountTest extends CommonClassTest
{
	/**
	 * @var User|null API user before the test
	 */
	private $previousUser;

	/**
	 * Run the endpoints as a user allowed to read everything, whatever the
	 * modules enabled on the test database.
	 *
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
	 * List methods to test: name => array(API class, method, arguments other than the defaults)
	 *
	 * @return array<string, array{0: string, 1: string, 2: array<string, mixed>}>
	 */
	private function endpoints()
	{
		return array(
			'thirdparties' => array('Thirdparties', 'index', array()),
			'thirdparties customers' => array('Thirdparties', 'index', array('mode' => 1, 'sqlfilters' => "(t.nom:like:'%a%')")),
			'contacts' => array('Contacts', 'index', array()),
			'invoices unpaid' => array('Invoices', 'index', array('status' => 'unpaid')),
			'products' => array('Products', 'index', array()),
			'projects timespent' => array('Projects', 'listTimespent', array()),
			'cronjobs' => array('Cronjobs', 'index', array()),
		);
	}

	/**
	 * Call a list method of the REST API with named arguments, the other ones keeping their default value.
	 *
	 * @param	string					$class		API class
	 * @param	string					$method		API method
	 * @param	array<string, mixed>	$named		Arguments by name
	 * @return	mixed								What the API method returns
	 */
	private function callList($class, $method, array $named)
	{
		$api = new $class();
		$rm = new ReflectionMethod($class, $method);
		$args = array();
		foreach ($rm->getParameters() as $p) {
			$args[] = array_key_exists($p->getName(), $named) ? $named[$p->getName()] : $p->getDefaultValue();
		}

		return $rm->invokeArgs($api, $args);
	}

	/**
	 * count=1 returns, into the key 'count', the number of records of the same call without count.
	 *
	 * @return void
	 */
	public function testCountIsNumberOfRecordsOfList()
	{
		foreach ($this->endpoints() as $name => list($class, $method, $named)) {
			$result = $this->callList($class, $method, array_merge($named, array('count' => 1)));
			$this->assertIsArray($result, $name);
			$this->assertSame(array('count'), array_keys($result), $name.': only the key count is returned');
			$this->assertIsInt($result['count'], $name);

			try {
				$expected = count($this->callList($class, $method, array_merge($named, array('limit' => 0))));
			} catch (RestException $e) {
				// A list with no record answers 404
				$this->assertSame(404, $e->getCode(), $name.': '.$e->getMessage());
				$expected = 0;
			}
			$this->assertSame($expected, $result['count'], $name);
		}
	}

	/**
	 * pagination_data=true returns, into pagination.total, the number of records of the full list of timespent.
	 *
	 * @return void
	 */
	public function testPaginationTotalOfTimespent()
	{
		$expected = count($this->callList('Projects', 'listTimespent', array('limit' => 0)));
		$result = $this->callList('Projects', 'listTimespent', array('limit' => 1, 'pagination_data' => true));
		$this->assertSame($expected, $result['pagination']['total']);
	}

	/**
	 * A filter that matches nothing is a count of 0, not the 404 of the list.
	 *
	 * @return void
	 */
	public function testCountOfNothingIsZero()
	{
		$result = $this->callList('Thirdparties', 'index', array('sqlfilters' => "(t.rowid:<:0)", 'count' => 1));
		$this->assertSame(array('count' => 0), $result);
	}

	/**
	 * A bad filter is still refused with count=1.
	 *
	 * @return void
	 */
	public function testCountWithBadFilterIsRefused()
	{
		try {
			$this->callList('Thirdparties', 'index', array('sqlfilters' => "bad filter", 'count' => 1));
			$this->fail('A bad sqlfilters should be refused');
		} catch (RestException $e) {
			$this->assertSame(400, $e->getCode());
		}
	}
}
