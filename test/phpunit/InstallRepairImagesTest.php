<?php
/* Copyright (C) 2026 Pierre Ardoin <developpeur@lesmetiersdubatiment.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file test/phpunit/InstallRepairImagesTest.php
 * \ingroup test
 * \brief Tests image repair boundaries with real files and directories.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
	define('DOL_DATA_ROOT', sys_get_temp_dir());
	define('DOL_INC_FOR_VERSION_ERROR', 1);
	require_once DOL_DOCUMENT_ROOT.'/filefunc.inc.php';
}
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/install/lib/repair.lib.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class InstallRepairImagesTest extends PHPUnit\Framework\TestCase
{
	/** @var string Isolated documents directory */
	private $documentroot = '';
	/** @var Conf|null Previous application configuration */
	private $previousconf;

	/** @return void */
	protected function setUp(): void
	{
		global $conf;
		$this->previousconf = $conf ?? null;
		$conf = new Conf();
		$this->documentroot = sys_get_temp_dir().'/dolibarr-image-repair-'.bin2hex(random_bytes(8));
		foreach (array('/societe', '/2/societe', '/2/produit', '/2/produit-extra') as $directory) {
			$this->assertGreaterThanOrEqual(0, dol_mkdir($this->documentroot.$directory));
		}
		$this->assertSame(5, file_put_contents($this->documentroot.'/societe/logo.png', 'other'));
		$this->assertSame(5, file_put_contents($this->documentroot.'/2/societe/logo.png', 'owner'));
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $conf;
		$countdeleted = 0;
		dol_delete_dir_recursive($this->documentroot, 0, 1, 0, $countdeleted, 0, 1);
		$conf = $this->previousconf;
	}

	/** @return void */
	public function testExistingFilesAndNewDestinationsStayInOwnerDirectory()
	{
		$directory = $this->documentroot.'/2/societe';
		foreach (array('/logo.png', '/22/logos/logo.png', '/22/logos/thumbs/logo_small.png') as $relativepath) {
			$this->assertTrue(repairImagePathIsSafe($directory.$relativepath, $directory, $this->documentroot));
		}
		$this->assertFalse(dol_is_dir($directory.'/22'));
		$this->assertSame('owner', file_get_contents($directory.'/logo.png'));
		$this->assertSame('other', file_get_contents($this->documentroot.'/societe/logo.png'));
	}

	/** @return void */
	public function testForeignMissingAndTraversalPathsAreRefused()
	{
		$directory = $this->documentroot.'/2/produit';
		foreach (array($this->documentroot.'/societe/logo.png', $directory.'-extra/photo.png', $directory.'/../societe/logo.png', $directory.'/missing/../../societe/logo.png', '') as $path) {
			$this->assertFalse(repairImagePathIsSafe($path, $directory, $this->documentroot), $path);
		}
		$this->assertFalse(repairImagePathIsSafe($directory.'/photo.png', $directory.'/unavailable', $this->documentroot));
		$this->assertFalse(repairImagePathIsSafe($directory.'/photo.png', $directory, $this->documentroot.'/unavailable'));
	}

	/** @return void */
	public function testUtf8DirectoryAndFileNamesKeepTheirBoundaries()
	{
		$directory = $this->documentroot.'/2/produit/échantillon';
		$this->assertGreaterThanOrEqual(0, dol_mkdir($directory));
		$this->assertSame(5, file_put_contents($directory.'/été.png', 'owner'));
		$this->assertTrue(repairImagePathIsSafe($directory.'/été.png', $directory, $this->documentroot));
		$this->assertTrue(repairImagePathIsSafe($directory.'/thumbs/été_small.png', $directory, $this->documentroot));
		$this->assertFalse(repairImagePathIsSafe($this->documentroot.'/societe/logo.png', $directory, $this->documentroot));
	}

	/** @return void */
	public function testRedirectedDestinationDirectoryIsRefused()
	{
		$directory = $this->documentroot.'/2/societe';
		if (!@symlink($this->documentroot.'/societe', $directory.'/22')) {
			$this->markTestSkipped('Creating directory links requires additional privileges on this platform.');
		}
		$this->assertFalse(repairImagePathIsSafe($directory.'/22/logos/logo.png', $directory, $this->documentroot));
		$this->assertFalse(repairImagePathIsSafe($directory.'/22/logo.png', $directory, $this->documentroot));
		$this->assertSame('other', file_get_contents($this->documentroot.'/societe/logo.png'));
	}

	/** @return void */
	public function testRedirectedOwnerDirectoryAndBrokenLinksAreRefused()
	{
		if (!@symlink($this->documentroot.'/societe', $this->documentroot.'/linked-owner')) {
			$this->markTestSkipped('Creating directory links requires additional privileges on this platform.');
		}
		$this->assertFalse(repairImagePathIsSafe($this->documentroot.'/linked-owner/logo.png', $this->documentroot.'/linked-owner', $this->documentroot));
		$directory = $this->documentroot.'/2/societe';
		$this->assertTrue(symlink($this->documentroot.'/missing.png', $directory.'/broken.png'));
		$this->assertFalse(repairImagePathIsSafe($directory.'/broken.png', $directory, $this->documentroot));
	}
}
