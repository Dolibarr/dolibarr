<?php
/* Copyright (C) 2026 Morgan Demoulin <morgan.demoulin@gmail.com>
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
 *      \file       test/phpunit/ApiListStatusFilterTest.php
 *      \ingroup    test
 *      \brief      The status filter of the REST list endpoints refuses a value
 *                  it does not know, instead of ignoring it and returning the
 *                  whole list as if it were the filtered one.
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
			require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/api_invoices.class.php';
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_invoices.class.php';
			require_once DOL_DOCUMENT_ROOT.'/fourn/class/api_supplier_orders.class.php';
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
 * Class ApiListStatusFilterTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ApiListStatusFilterTest extends CommonClassTest
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
	 * One list call per endpoint, with only the status set.
	 *
	 * @return array<string, array{0: callable(string): mixed, 1: string[]}>
	 */
	private function endpoints()
	{
		return array(
			'invoices' => array(
				static function ($status) {
					return (new Invoices())->index('t.rowid', 'ASC', 1, 0, '', $status, '', '', false, 0, false);
				},
				array('draft', 'unpaid', 'paid', 'cancelled'),
			),
			'invoices/templates' => array(
				static function ($status) {
					return (new Invoices())->indexTemplateInvoices('t.rowid', 'ASC', 1, 0, '', $status, '', '', false, 0, false);
				},
				array('draft', 'active', 'suspended'),
			),
			'supplierinvoices' => array(
				static function ($status) {
					return (new SupplierInvoices())->index('t.rowid', 'ASC', 1, 0, '', $status);
				},
				array('draft', 'unpaid', 'paid', 'cancelled'),
			),
			'supplierorders' => array(
				static function ($status) {
					return (new SupplierOrders())->index('t.rowid', 'ASC', 1, 0, '', '', $status);
				},
				array('draft', 'validated', 'approved', 'running', 'received_start', 'received_end', 'cancelled', 'refused'),
			),
		);
	}

	/**
	 * A value the endpoint does not know is a 400 that names the accepted
	 * ones. It used to be ignored, and the unfiltered list came back.
	 *
	 * @return void
	 */
	public function testUnknownStatusIsRefused()
	{
		foreach ($this->endpoints() as $name => list($call, $accepted)) {
			foreach (array('bogus', 'open', strtoupper($accepted[0])) as $value) {
				try {
					$call($value);
					$this->fail($name.': status='.$value.' should be refused');
				} catch (RestException $e) {
					$this->assertSame(400, $e->getCode(), $name.': status='.$value);
					$this->assertStringContainsString(implode(', ', $accepted), $e->getMessage(), $name.': the error lists the accepted values');
				}
			}
		}
	}

	/**
	 * Every documented value, and no value at all, still runs the query.
	 *
	 * @return void
	 */
	public function testKnownStatusAndNoStatusAreAccepted()
	{
		foreach ($this->endpoints() as $name => list($call, $accepted)) {
			foreach (array_merge(array(''), $accepted) as $value) {
				try {
					$result = $call($value);
					$this->assertIsArray($result, $name.': status='.$value);
				} catch (RestException $e) {
					$this->assertNotSame(400, $e->getCode(), $name.': status='.$value.' is valid: '.$e->getMessage());
				}
			}
		}
	}
}
