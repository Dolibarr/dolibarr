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
 * \file test/phpunit/InstallRepairMailingTest.php
 * \brief Mailing legacy/new directory contract and safe migration preview.
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
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/install/lib/repair.lib.php';

/** @backupGlobals disabled */
class InstallRepairMailingTest extends PHPUnit\Framework\TestCase
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
		$this->documentroot = sys_get_temp_dir().'/dolibarr-mailing-repair-'.bin2hex(random_bytes(8));
		dol_mkdir($this->documentroot.'/2/mailing/1/0/thumbs');
		file_put_contents($this->documentroot.'/2/mailing/1/0/attachment.pdf', 'owner');
		file_put_contents($this->documentroot.'/2/mailing/1/0/thumbs/photo.png', 'thumbnail');
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
	public function testNewMailingPathUsesFullIdButExplicitLegacyPathRemainsAvailable()
	{
		global $conf;
		$mailing = new Mailing(null);
		$mailing->id = 101;
		$mailing->ref = '101';
		$this->assertSame('1/0', get_exdir(101, 0, 0, 1, $mailing, 'mailing'));
		$conf->global->MAILING_USE_NEW_PATH_FOR_FILES = 1;
		$this->assertSame('101', get_exdir(101, 0, 0, 1, $mailing, 'mailing'));
		$this->assertSame('1/0', get_exdir(101, 2, 0, 1, $mailing, 'mailing'));
		$this->assertSame('1/0', get_exdir(101, 0, 0, 1, $mailing, 'invoice_supplier'));
	}

	/** @return void */
	public function testPreviewPreservesFilesDirectoriesAndConfiguration()
	{
		global $conf;
		$mailing = new Mailing(null);
		$mailing->id = 101;
		$mailing->ref = '101';
		$mailing->entity = 2;
		$directory = $this->documentroot.'/2/mailing';
		$conf->global->MAILING_USE_NEW_PATH_FOR_FILES = '0';
		$report = repairMailingFiles($mailing, $directory, $this->documentroot);
		$this->assertSame('', $report['error']);
		$this->assertCount(2, $report['files']);
		$this->assertSame('0', $conf->global->MAILING_USE_NEW_PATH_FOR_FILES);
		$this->assertFalse(dol_is_dir($directory.'/101'));
		$this->assertSame('owner', file_get_contents($directory.'/1/0/attachment.pdf'));
		$this->assertSame($directory.'/101/thumbs/photo.png', $report['files'][1]['target']);
	}

	/** @return void */
	public function testAmbiguousBucketAndCollisionPreserveAllSourceFiles()
	{
		$mailing = new Mailing(null);
		$mailing->id = 101;
		$mailing->ref = '101';
		$mailing->entity = 2;
		$directory = $this->documentroot.'/2/mailing';
		$report = repairMailingFiles($mailing, $directory, $this->documentroot, true, true);
		$this->assertSame('RepairMailingAmbiguous', $report['error']);
		$this->assertFalse(dol_is_dir($directory.'/101'));
		dol_mkdir($directory.'/101');
		file_put_contents($directory.'/101/attachment.pdf', 'existing');
		$report = repairMailingFiles($mailing, $directory, $this->documentroot, false, true);
		$this->assertSame('RepairMailingFailed', $report['error']);
		$this->assertSame('owner', file_get_contents($directory.'/1/0/attachment.pdf'));
		$this->assertSame('existing', file_get_contents($directory.'/101/attachment.pdf'));
		$this->assertFalse(dol_is_dir($directory.'/101/thumbs'));
	}
}
