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
 * \file test/phpunit/InstallRepairOrphanFilesTest.php
 * \brief Conservative orphan detection with native SQLite and real files.
 */
global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/sqlite3.class.php';
require_once DOL_DOCUMENT_ROOT.'/install/lib/repair.lib.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 */
class InstallRepairOrphanFilesTest extends CommonClassTest
{
	/** @var DoliDBSqlite3|null */
	private $database;
	/** @var string */
	private $documentroot = '';

	/** @return void */
	protected function setUp(): void
	{
		parent::setUp();
		if (!extension_loaded('sqlite3')) {
			$this->markTestSkipped('The native SQLite driver is required.');
		}
		global $conf, $main_data_dir;
		$conf = clone $this->savconf;
		$this->documentroot = sys_get_temp_dir().'/dolibarr-orphan-repair-'.bin2hex(random_bytes(8));
		dol_mkdir($this->documentroot.'/2/invoice');
		$previousdatadir = $main_data_dir ?? null;
		$main_data_dir = $this->documentroot;
		try {
			// Keep real SQL methods, without the constructor's unrelated custom SQL callbacks.
			$this->database = $this->getMockBuilder(DoliDBSqlite3::class)->disableOriginalConstructor()->onlyMethods(array())->getMock();
			$this->database->db = $this->database->connect('', '', '', 'repair');
			$this->database->connected = (bool) $this->database->db;
			$this->database->transaction_opened = 0;
		} finally {
			$main_data_dir = $previousdatadir;
		}
		$this->assertTrue($this->database->connected);
		foreach (array('facture', 'facture_fourn', 'societe', 'chargesociales') as $table) {
			$this->assertNotFalse($this->database->query('CREATE TABLE '.$this->database->prefix().$table.' (rowid INTEGER PRIMARY KEY, entity INTEGER, ref TEXT)'));
		}
	}

	/** @return void */
	protected function tearDown(): void
	{
		global $conf;
		if ($this->database) {
			$this->database->close();
		}
		if ($this->documentroot !== '') {
			$countdeleted = 0;
			dol_delete_dir_recursive($this->documentroot, 0, 1, 0, $countdeleted, 0, 1);
		}
		$conf = $this->savconf;
		parent::tearDown();
	}

	/** @return void */
	public function testPreviewPreservesExistingObjectsAcrossEntitiesAndExcludedFiles()
	{
		$this->database->query("INSERT INTO ".$this->database->prefix()."facture VALUES (1,1,'SAME'),(2,2,'SAME'),(3,1,'FOREIGN/REF'),(4,3,'DISABLED')");
		$directory = $this->documentroot.'/2/invoice';
		foreach (array('same/keep.pdf', 'FOREIGN_REF/keep.pdf', 'DISABLED/keep.pdf', 'GONE/remove.pdf', 'GONE/sub/remove.pdf', 'GONE/SPECIMEN.pdf', 'GONE/.private', 'GONE/photo_preview.png', 'GONE/x.meta', 'temp/keep.pdf', 'GONE/thumbs/keep.png', 'root-file.pdf') as $file) {
			dol_mkdir(dirname($directory.'/'.$file));
			file_put_contents($directory.'/'.$file, 'fixture');
		}
		$report = repairOrphanFiles($this->database, 'invoice', $directory, $this->documentroot);
		$this->assertSame('', $report['error']);
		$this->assertCount(2, $report['files']);
		$this->assertContains($directory.'/GONE/remove.pdf', $report['files']);
		$this->assertContains($directory.'/GONE/sub/remove.pdf', $report['files']);
		$this->assertSame(0, $report['deleted']);
		foreach ($report['files'] as $file) {
			$this->assertSame('fixture', file_get_contents($file));
		}
	}

	/** @return void */
	public function testSupplierAndNumericObjectLayoutsKeepTheirNativeOwners()
	{
		$this->database->query("INSERT INTO ".$this->database->prefix()."facture_fourn VALUES (212,1,'SAME')");
		$directory = $this->documentroot.'/2/supplier';
		foreach (array('2/1/SAME/keep.pdf', '2/1/212/keep.pdf', '2/1/GONE/remove.pdf', 'unrecognized/path/keep.pdf') as $file) {
			dol_mkdir(dirname($directory.'/'.$file));
			file_put_contents($directory.'/'.$file, 'fixture');
		}
		$report = repairOrphanFiles($this->database, 'invoice_supplier', $directory, $this->documentroot);
		$this->assertSame('', $report['error']);
		$this->assertSame(array($directory.'/2/1/GONE/remove.pdf'), $report['files']);
		$this->database->query('INSERT INTO '.$this->database->prefix().'societe VALUES (212,1,NULL)');
		$report = repairOrphanFiles($this->database, 'company', $directory, $this->documentroot);
		$this->assertSame(array(), $report['files']); // Numeric legacy hierarchies are not flat company directories.
	}

	/** @return void */
	public function testDatabaseFailureAndUnavailableDirectoryCannotDeleteFiles()
	{
		$directory = $this->documentroot.'/2/invoice';
		dol_mkdir($directory.'/GONE');
		file_put_contents($directory.'/GONE/keep.pdf', 'fixture');
		$this->database->query('DROP TABLE '.$this->database->prefix().'facture');
		$report = repairOrphanFiles($this->database, 'invoice', $directory, $this->documentroot, true);
		$this->assertSame('RepairOrphanFilesFailed', $report['error']);
		$this->assertSame(0, $report['deleted']);
		$this->assertSame('fixture', file_get_contents($directory.'/GONE/keep.pdf'));
		$report = repairOrphanFiles($this->database, 'invoice', $directory.'/missing', $this->documentroot, true);
		$this->assertSame('RepairOrphanFilesFailed', $report['error']);
		$report = repairOrphanFiles($this->database, '../facture', $directory, $this->documentroot, true);
		$this->assertSame('RepairOrphanFilesFailed', $report['error']);
	}
}
