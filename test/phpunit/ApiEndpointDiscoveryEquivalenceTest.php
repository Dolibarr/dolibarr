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
 *      \file       test/phpunit/ApiEndpointDiscoveryEquivalenceTest.php
 *      \ingroup    test
 *      \brief      dolGetApiEndpoints() replaces an algorithm that used to live inline
 *                  in htdocs/api/index.php. This test keeps a copy of that algorithm
 *                  and proves the two still agree, so that the extraction can be
 *                  checked by anyone rather than taken on trust.
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/CommonClassTest.class.php';

// What htdocs/api/index.php declares, so that main.inc.php starts no session and
// emits no header or markup when there is no request: the copy of the old scan below
// loads every api_*.class.php, and two of them (api_documents, api_setup) require
// main.inc.php when they are loaded.
foreach (['NOCSRFCHECK', 'NOTOKENRENEWAL', 'NOREQUIREMENU', 'NOREQUIREHTML', 'NOREQUIREAJAX', 'NOLOGIN', 'NOSESSION', 'NODEFAULTVALUES'] as $apidiscoveryconstant) {
	if (!defined($apidiscoveryconstant)) {
		define($apidiscoveryconstant, '1');
	}
}
unset($apidiscoveryconstant);

// dolGetModulesDirs() and getModuleDirForApiClass(), which the copy below needs, and
// DolibarrApi, which every endpoint class extends. htdocs/api/index.php loads both.
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/api.lib.php';
require_once DOL_DOCUMENT_ROOT.'/api/class/api.class.php';

// Every endpoint file is loaded here, at the global scope, so that the copy of the
// old scan finds them already loaded: main.inc.php reads and writes the request
// globals directly, and included from inside a method it finds them undefined and
// fails on a null $conf. The shared function has no such constraint - it reads the
// declared classes with the tokenizer instead of executing the file - which is
// precisely one of the things this extraction buys.
foreach (dolGetApiEndpoints(true, true) as $apidiscoveryendpoint) {
	require_once $apidiscoveryendpoint['path'];
}
unset($apidiscoveryendpoint);

$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class ApiEndpointDiscoveryEquivalenceTest
 *
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class ApiEndpointDiscoveryEquivalenceTest extends CommonClassTest
{
	/**
	 * The endpoint scan exactly as htdocs/api/index.php performed it before
	 * dolGetApiEndpoints() existed, kept here as the reference to compare against.
	 *
	 * @return array<string,string> Class name by lowercase class name, as index.php built it
	 */
	private function legacyScan()
	{
		$listofapis = [];

		$modulesdir = dolGetModulesDirs();
		foreach ($modulesdir as $dir) {
			$handle = @opendir(dol_osencode($dir));
			if (!is_resource($handle)) {
				continue;
			}
			while (($file = readdir($handle)) !== false) {
				$regmod = [];
				if (!is_readable($dir.$file) || !preg_match("/^mod(.*)\.class\.php$/i", $file, $regmod)) {
					continue;
				}
				$module = strtolower($regmod[1]);
				$moduledirforclass = getModuleDirForApiClass($module);
				$modulenameforenabled = $module;
				if ($module == 'propale') {
					$modulenameforenabled = 'propal';
				} elseif ($module == 'supplierproposal') {
					$modulenameforenabled = 'supplier_proposal';
				} elseif ($module == 'ficheinter') {
					$modulenameforenabled = 'intervention';
				} elseif ($module == 'product' && !isModEnabled('product') && isModEnabled('service')) {
					$modulenameforenabled = 'service';
				}

				if (!isModEnabled($modulenameforenabled)) {
					continue;
				}

				$dir_part = dol_buildpath('/'.$moduledirforclass.'/class/');
				$handle_part = @opendir(dol_osencode($dir_part));
				if (!is_resource($handle_part)) {
					continue;
				}
				while (($file_searched = readdir($handle_part)) !== false) {
					if ($file_searched == 'api_access.class.php') {
						continue;
					}
					$regapi = [];
					if (!is_readable($dir_part.$file_searched) || !preg_match("/^api_(.*)\.class\.php$/i", $file_searched, $regapi)) {
						continue;
					}
					$classname = ucwords($regapi[1]);
					$classname = str_replace('_', '', $classname);
					require_once $dir_part.$file_searched;
					if (class_exists($classname.'Api')) {
						$listofapis[strtolower($classname.'Api')] = $classname.'Api';
					} elseif (class_exists($classname)) {
						$listofapis[strtolower($classname)] = $classname;
					}
				}
				closedir($handle_part);
			}
			closedir($handle);
		}
		asort($listofapis);

		return $listofapis;
	}

	/**
	 * The shared discovery, asked the same question as the old inline scan, must
	 * return the same classes under the same keys.
	 *
	 * @return void
	 */
	public function testSharedDiscoveryMatchesTheLegacyInlineScan()
	{
		$legacy = $this->legacyScan();

		$shared = [];
		foreach (dolGetApiEndpoints(true, true) as $key => $endpoint) {
			$shared[$key] = $endpoint['class'];
		}
		asort($shared);

		$this->assertNotEmpty($legacy);
		$this->assertSame($legacy, $shared);
	}

	/**
	 * Asked for the whole surface, the discovery must be a superset of what the old
	 * scan found, since the old scan dropped the modules that are switched off.
	 *
	 * @return void
	 */
	public function testWholeSurfaceIsASupersetOfTheEnabledOne()
	{
		$enabled = array_keys($this->legacyScan());
		$all = array_keys(dolGetApiEndpoints(true, false));

		$this->assertSame([], array_values(array_diff($enabled, $all)));
	}
}
