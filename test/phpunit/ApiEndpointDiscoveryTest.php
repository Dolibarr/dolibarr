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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/ApiEndpointDiscoveryTest.php
 *      \ingroup    test
 *      \brief      The REST API endpoint discovery is built once and shared, and
 *                  it reproduces exactly the class names htdocs/api/index.php
 *                  computes, because those names reach the routing table.
 */

// htdocs/api/class/api_documents.class.php loads main.inc.php when it is included.
// Declare what htdocs/api/index.php declares, so the web bootstrap does not try to
// start a session or emit headers from a command line test run.
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/api.lib.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class ApiEndpointDiscoveryTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ApiEndpointDiscoveryTest extends CommonClassTest
{
	/**
	 * api.lib.php is a pre-existing core library, not a new file: the endpoint
	 * discovery was added to it. Its original content must still be there, or the
	 * API setup pages fatal.
	 *
	 * @return void
	 */
	public function testPreExistingLibraryFunctionsAreStillThere()
	{
		$this->assertTrue(function_exists('api_admin_prepare_head'), 'api_admin_prepare_head() is called by htdocs/api/admin/index.php and token_list.php');
	}

	/**
	 * The discovery never returns the shared DolibarrApiAccess class, which is not
	 * an endpoint, and never returns an external module when it is not asked for.
	 *
	 * @return void
	 */
	public function testDiscoveryExcludesCustomModulesAndAccessClass()
	{
		$endpoints = dolGetApiEndpoints(false);

		$this->assertNotEmpty($endpoints);
		$this->assertArrayNotHasKey('access', $endpoints);
		foreach ($endpoints as $key => $endpoint) {
			$this->assertStringNotContainsString('/custom/', $endpoint['path'], 'endpoint '.$key.' comes from custom/');
		}
	}

	/**
	 * htdocs/api/index.php derives the class name from the file name with
	 * str_replace('_', '', ucwords($key)) and relies on class_exists() being
	 * case insensitive. That computed spelling is what reaches addAPIClass(),
	 * so the discovery must return it rather than the declared spelling.
	 *
	 * @return void
	 */
	public function testDiscoveryComputesClassNamesLikeTheApiEntryPoint()
	{
		$endpoints = dolGetApiEndpoints(false);

		$this->assertArrayHasKey('supplierinvoices', $endpoints);
		$this->assertSame('Supplierinvoices', $endpoints['supplierinvoices']['class']);
		$this->assertArrayHasKey('supplierorders', $endpoints);
		$this->assertSame('Supplierorders', $endpoints['supplierorders']['class']);
		$this->assertArrayHasKey('agendaevents', $endpoints);
		$this->assertSame('Agendaevents', $endpoints['agendaevents']['class']);
	}

	/**
	 * The order must not depend on the file system, or the recorded parity
	 * fixture drifts without any behaviour having changed.
	 *
	 * @return void
	 */
	public function testDiscoveryIsSortedAndStable()
	{
		$keys = array_keys(dolGetApiEndpoints(false));
		$sorted = $keys;
		sort($sorted);

		$this->assertSame($sorted, $keys);
		$this->assertSame($keys, array_keys(dolGetApiEndpoints(false)));
	}
}
