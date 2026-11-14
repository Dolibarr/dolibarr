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
 * \file test/phpunit/InstallRepairUserPicturesTest.php
 * \brief Tests restoration of user photos in isolated owner directories.
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
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/install/lib/repair.lib.php';

/** @backupGlobals disabled */
class InstallRepairUserPicturesTest extends PHPUnit\Framework\TestCase
{
	/** @var string */
	private $documentroot = '';
	/** @var Conf|null */
	private $previousconf;

	/** @return void */
	protected function setUp(): void
	{
		global $conf;
		$this->previousconf = $conf ?? null;
		$conf = new Conf();
		$this->documentroot = sys_get_temp_dir().'/dolibarr-user-photo-repair-'.bin2hex(random_bytes(8));
		foreach (array('/users/2/1/thumbs', '/2/users/2/1/thumbs', '/3/users/312/thumbs') as $directory) {
			$this->assertGreaterThanOrEqual(0, dol_mkdir($this->documentroot.$directory));
		}
		file_put_contents($this->documentroot.'/users/2/1/avatar.photo.png', 'master');
		file_put_contents($this->documentroot.'/2/users/2/1/avatar.photo.png', 'owner');
		file_put_contents($this->documentroot.'/2/users/2/1/thumbs/avatar.photo_small.png', 'small');
		file_put_contents($this->documentroot.'/3/users/312/avatar.photo.png', 'flat');
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
	public function testPreviewDoesNotCreateDestinationAndUsesOwnerFiles()
	{
		$directory = $this->documentroot.'/2/users';
		$report = repairUserPicture(212, 'avatar.photo.png', $directory, $this->documentroot);
		$this->assertSame('', $report['error']);
		$this->assertCount(2, $report['files']);
		$this->assertFalse(dol_is_dir($directory.'/212'));
		$this->assertSame($directory.'/212/photos/avatar.photo.png', $report['files'][0]['target']);
	}

	/** @return void */
	public function testRepairPreservesOriginalsAndExistingTargetsAndIsIdempotent()
	{
		$directory = $this->documentroot.'/2/users';
		dol_mkdir($directory.'/212/photos');
		file_put_contents($directory.'/212/photos/avatar.photo.png', 'current');
		$report = repairUserPicture(212, 'avatar.photo.png', $directory, $this->documentroot, true);
		$this->assertSame('', $report['error']);
		$this->assertSame('current', file_get_contents($directory.'/212/photos/avatar.photo.png'));
		$this->assertSame('small', file_get_contents($directory.'/212/photos/thumbs/avatar.photo_small.png'));
		$this->assertSame('owner', file_get_contents($directory.'/2/1/avatar.photo.png'));
		$this->assertFalse(dol_is_dir($this->documentroot.'/users/212'));
		$this->assertSame(array(), repairUserPicture(212, 'avatar.photo.png', $directory, $this->documentroot, true)['files']);
	}

	/** @return void */
	public function testIntermediateLayoutAndGlobalUserDirectory()
	{
		$report = repairUserPicture(312, 'avatar.photo.png', $this->documentroot.'/3/users', $this->documentroot, true);
		$this->assertSame('', $report['error']);
		$this->assertSame('flat', file_get_contents($this->documentroot.'/3/users/312/photos/avatar.photo.png'));
		$report = repairUserPicture(12, 'avatar.photo.png', $this->documentroot.'/users', $this->documentroot, true);
		$this->assertSame('', $report['error']);
		$this->assertSame('master', file_get_contents($this->documentroot.'/users/12/photos/avatar.photo.png'));
	}

	/** @return void */
	public function testInvalidNamesAndMissingOwnerDirectoryAreRefused()
	{
		foreach (array('../avatar.png', 'thumbs/avatar.png', 'a\\b.png', '') as $photo) {
			$report = repairUserPicture(212, $photo, $this->documentroot.'/2/users', $this->documentroot, true);
			$this->assertSame('ErrorWrongParameters', $report['error']);
		}
		$report = repairUserPicture(12, 'avatar.photo.png', $this->documentroot.'/missing/users', $this->documentroot, true);
		$this->assertSame('RepairImagesDirectoryUnavailable', $report['error']);
		$this->assertFalse(dol_is_dir($this->documentroot.'/missing'));
	}
}
