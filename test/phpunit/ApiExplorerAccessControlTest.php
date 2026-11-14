<?php
/* Copyright (C) 2026       Frédéric France             <frederic.france@free.fr>
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
 *      \file       test/phpunit/ApiExplorerAccessControlTest.php
 *      \ingroup    test
 *      \brief      The published API documentation must list only the routes the
 *                  authenticated caller is allowed to call.
 */

use Luracast\Restler\Defaults;
use Luracast\Restler\Resources;
use Luracast\Restler\Routes;
use Luracast\Restler\Scope;

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
 * Class ApiExplorerAccessControlTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ApiExplorerAccessControlTest extends CommonClassTest
{
	/**
	 * @var callable|string|null	Value of Defaults::$accessControlFunction before the test
	 */
	private $previousDefaultsFunction;

	/**
	 * @var callable|string|null	Value of Resources::$accessControlFunction before the test
	 */
	private $previousResourcesFunction;

	/**
	 * @var string					Value of DolibarrApiAccess::$role before the test
	 */
	private $previousRole;

	/**
	 * @var array<string,object>	Content of Scope::$instances before the test
	 */
	private $previousScopeInstances = array();

	/**
	 * Save the static properties the engine shares between requests.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		$this->previousDefaultsFunction = Defaults::$accessControlFunction;
		$this->previousResourcesFunction = Resources::$accessControlFunction;
		$this->previousRole = DolibarrApiAccess::$role;
		$this->previousScopeInstances = $this->scopeInstances();

		Defaults::$accessControlFunction = null;
		Resources::$accessControlFunction = null;
	}

	/**
	 * Put them back, so that the rest of the suite sees the engine it expects.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		Defaults::$accessControlFunction = $this->previousDefaultsFunction;
		Resources::$accessControlFunction = $this->previousResourcesFunction;
		DolibarrApiAccess::$role = $this->previousRole;
		$this->scopeInstances($this->previousScopeInstances);

		parent::tearDown();
	}

	/**
	 * Read, and optionally replace, the instances Restler's dependency container holds.
	 *
	 * @param	array<string,object>|null	$replacement	New content, or null to only read
	 * @return	array<string,object>						Content before the call
	 */
	private function scopeInstances($replacement = null)
	{
		$property = new ReflectionProperty('Luracast\Restler\Scope', 'instances');
		if (PHP_VERSION_ID < 80100) {
			$property->setAccessible(true);
		}
		$before = $property->getValue();
		if (is_array($replacement)) {
			$property->setValue(null, $replacement);
		}
		return is_array($before) ? $before : array();
	}

	/**
	 * Build the route description the engine hands to the access control function.
	 *
	 * @param	string[]|string|false	$requires	Roles the route is restricted to, false when it is open
	 * @return	array{accessLevel:int,metadata:array<string,mixed>}	Route as Restler describes it
	 */
	private function protectedRoute($requires)
	{
		$metadata = array('description' => 'A protected route');
		if ($requires !== false) {
			$metadata['class'] = array('DolibarrApiAccess' => array('properties' => array('requires' => $requires)));
		}

		return array('accessLevel' => 2, 'metadata' => $metadata);
	}

	/**
	 * The authentication class must tell the engine which function filters the documentation.
	 *
	 * Restler reads Defaults::$accessControlFunction from Routes::verifyAccess(), the function the
	 * API explorer goes through. Resources::$accessControlFunction belongs to the Swagger 1.x
	 * listing, which Dolibarr does not register, so setting that one alone filters nothing.
	 *
	 * @return void
	 */
	public function testTheAuthenticationClassWiresTheDocumentationFilter()
	{
		new DolibarrApiAccess();

		$this->assertSame('DolibarrApiAccess::verifyAccess', Defaults::$accessControlFunction, 'The documentation filter the API explorer reads must be wired');
		$this->assertSame('DolibarrApiAccess::verifyAccess', Resources::$accessControlFunction, 'The documentation filter of the Swagger 1.x listing must stay wired');
	}

	/**
	 * A route the caller's role may not call must not appear in the published documentation.
	 *
	 * @return void
	 */
	public function testTheDocumentationHidesARouteTheRoleCannotCall()
	{
		new DolibarrApiAccess();
		Scope::set('Restler', (object) array('_authenticated' => true));

		DolibarrApiAccess::$role = 'external';

		$this->assertFalse(Routes::verifyAccess($this->protectedRoute(array('user'))), 'An external user must not see a route reserved to internal users');
		$this->assertTrue(Routes::verifyAccess($this->protectedRoute(array('user', 'external'))), 'An external user must still see a route open to external users');
		$this->assertTrue(Routes::verifyAccess($this->protectedRoute(false)), 'A route that restricts nothing must stay visible');

		DolibarrApiAccess::$role = 'admin';

		$this->assertTrue(Routes::verifyAccess($this->protectedRoute(array('user'))), 'An admin must see every route');
	}

	/**
	 * The engine hands the roles over as the comment parser read them, that is one string.
	 *
	 * `@class DolibarrApiAccess {@requires user,external}`, the form 54 of the 55 core API classes
	 * use, is parsed as the string 'user,external', not as a list. Reading it as a single role
	 * matches nobody, so every one of those classes would disappear from the documentation of any
	 * caller who is not an admin.
	 *
	 * @return void
	 */
	public function testTheRolesAreReadTheWayTheCommentParserHandsThemOver()
	{
		new DolibarrApiAccess();
		Scope::set('Restler', (object) array('_authenticated' => true));

		DolibarrApiAccess::$role = 'user';

		$this->assertTrue(Routes::verifyAccess($this->protectedRoute('user,external')), 'An internal user must see a route open to internal users');
		$this->assertTrue(Routes::verifyAccess($this->protectedRoute('user')), 'An internal user must see a route reserved to internal users');

		DolibarrApiAccess::$role = 'external';

		$this->assertTrue(Routes::verifyAccess($this->protectedRoute('user,external')), 'An external user must see a route open to external users');
		$this->assertFalse(Routes::verifyAccess($this->protectedRoute('user')), 'An external user must not see a route reserved to internal users');
	}

	/**
	 * The filter must not be consulted for a caller the engine has not authenticated.
	 *
	 * Such a caller is refused earlier, on the access level alone, and static::$role would still
	 * hold the value left by whoever was served before.
	 *
	 * @return void
	 */
	public function testAnAnonymousCallerIsRefusedOnTheAccessLevelAlone()
	{
		new DolibarrApiAccess();
		Scope::set('Restler', (object) array('_authenticated' => false));

		DolibarrApiAccess::$role = 'admin';

		$this->assertFalse(Routes::verifyAccess($this->protectedRoute(array('user'))), 'An anonymous caller must not see a protected route');
		$this->assertTrue(Routes::verifyAccess(array('accessLevel' => 0, 'metadata' => array())), 'An anonymous caller must still see an open route');
	}
}
