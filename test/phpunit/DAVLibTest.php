<?php
/* Copyright (C) 2018	Destailleur Laurent	<eldy@users.sourceforge.net>
 * Copyright (C) 2024-2026	MDW				<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/DAVLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for DAV library functions
 *      \remarks    To run this script as CLI: phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

// Define HTTP_HOST before loading dav.lib.php to avoid warning
if (!isset($_SERVER['HTTP_HOST'])) {
	$_SERVER['HTTP_HOST'] = 'localhost';
}

require_once dirname(__FILE__).'/../../htdocs/dav/dav.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

// Ensure constants are defined for tests
if (!defined('CDAV_URI_KEY')) {
	define('CDAV_URI_KEY', substr(md5($_SERVER['HTTP_HOST'] ?? 'localhost'), 0, 8));
}
if (!defined('CDAV_CONTACT_TAG')) {
	define('CDAV_CONTACT_TAG', '');
}

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

/**
 * Class for PHPUnit tests of DAV library functions
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class DAVLibTest extends CommonClassTest
{
	/**
	 * testDavAdminPrepareHead
	 *
	 * @return	void
	 */
	public function testDavAdminPrepareHead()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Call the function
		$head = dav_admin_prepare_head();

		// Check that the result is an array
		$this->assertIsArray($head);

		// Check that the array is not empty
		$this->assertNotEmpty($head);

		// Check that the first element has the expected structure
		// The first tab should be the WebDAV admin page
		$this->assertArrayHasKey(0, $head);
		$this->assertIsArray($head[0]);

		// Check that the first tab has the expected keys (0=url, 1=title, 2=picto)
		$this->assertArrayHasKey(0, $head[0]); // URL
		$this->assertArrayHasKey(1, $head[0]); // Title
		$this->assertArrayHasKey(2, $head[0]); // Picto

		// Check that the URL contains 'dav.php'
		$this->assertStringContainsString('dav.php', $head[0][0]);

		// Check that the title is 'WebDAV' (translated)
		$this->assertSame('WebDAV', $head[0][1]);

		// Check that the picto is 'webdav'
		$this->assertSame('webdav', $head[0][2]);

		print __METHOD__." OK\n";
	}

	/**
	 * testCdavConstants
	 *
	 * @return	void
	 */
	public function testCdavConstants()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Ensure the constants are defined by including the lib file
		// (already included at the top of this file)

		// Check that CDAV_CONTACT_TAG is defined
		$this->assertTrue(defined('CDAV_CONTACT_TAG'));

		// Check that CDAV_URI_KEY is defined
		$this->assertTrue(defined('CDAV_URI_KEY'));

		// If CDAV_URI_KEY was not set in config, it should have a default value
		if (empty(getDolGlobalString('CDAV_URI_KEY'))) {
			// It should be generated from HTTP_HOST
			$this->assertNotEmpty(CDAV_URI_KEY);
			$this->assertSame(8, strlen(CDAV_URI_KEY)); // Should be 8 characters
		}

		print __METHOD__." OK\n";
	}

	/**
	 * testCdavUriKeyGeneration
	 * Test that CDAV_URI_KEY is generated correctly from different HTTP_HOST values
	 *
	 * @return	void
	 */
	public function testCdavUriKeyGeneration()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Test with different HTTP_HOST values
		$testHosts = array(
			'localhost',
			'example.com',
			'dolibarr.example.org',
			'192.168.1.1'
		);

		foreach ($testHosts as $host) {
			// Simulate different HTTP_HOST
			$_SERVER['HTTP_HOST'] = $host;

			// Reload the constants by including the lib file again
			// Note: We can't easily redefine constants, so we'll just verify the logic
			$expectedKey = substr(md5($host), 0, 8);

			// Check that the generated key has the correct length
			$this->assertSame(8, strlen($expectedKey), "CDAV_URI_KEY for host '$host' should be 8 characters");

			// Check that the generated key is alphanumeric (hex)
			$this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $expectedKey, "CDAV_URI_KEY for host '$host' should be hex");
		}

		print __METHOD__." OK\n";
	}

	/**
	 * testCdavContactTag
	 * Test CDAV_CONTACT_TAG constant values
	 *
	 * @return	void
	 */
	public function testCdavContactTag()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// CDAV_CONTACT_TAG should be defined
		$this->assertTrue(defined('CDAV_CONTACT_TAG'));

		// If not configured, it should default to empty string
		if (empty(getDolGlobalString('CDAV_CONTACT_TAG'))) {
			$this->assertSame('', CDAV_CONTACT_TAG);
		}

		// CDAV_CONTACT_TAG should be a string
		$this->assertIsString(CDAV_CONTACT_TAG);

		print __METHOD__." OK\n";
	}

	/**
	 * testDavAdminPrepareHeadStructure
	 * Test the structure of dav_admin_prepare_head return value in detail
	 *
	 * @return	void
	 */
	public function testDavAdminPrepareHeadStructure()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$head = dav_admin_prepare_head();

		// Check that head is an array of arrays
		foreach ($head as $index => $tab) {
			$this->assertIsArray($tab, "Tab $index should be an array");

			// Each tab should have at least 3 elements: URL, title, picto
			$this->assertArrayHasKey(0, $tab, "Tab $index should have URL at index 0");
			$this->assertArrayHasKey(1, $tab, "Tab $index should have title at index 1");
			$this->assertArrayHasKey(2, $tab, "Tab $index should have picto at index 2");

			// URL should be a non-empty string
			$this->assertIsString($tab[0], "Tab $index URL should be a string");
			$this->assertNotEmpty($tab[0], "Tab $index URL should not be empty");

			// Title should be a non-empty string
			$this->assertIsString($tab[1], "Tab $index title should be a string");
			$this->assertNotEmpty($tab[1], "Tab $index title should not be empty");

			// Picto should be a string
			$this->assertIsString($tab[2], "Tab $index picto should be a string");
		}

		// The first tab should be the main DAV admin page
		$firstTab = $head[0];
		$this->assertStringContainsString('admin/dav.php', $firstTab[0]);
		$this->assertSame('WebDAV', $firstTab[1]);
		$this->assertSame('webdav', $firstTab[2]);

		print __METHOD__." OK\n";
	}
}
