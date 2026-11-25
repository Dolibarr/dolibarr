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
 * \file test/phpunit/InstallRepairEcmTest.php
 * \ingroup test
 * \brief Tests ECM index repair with an isolated native SQLite database and real files.
 */

if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
	define('DOL_DATA_ROOT', sys_get_temp_dir());
	define('DOL_INC_FOR_VERSION_ERROR', 1);
	require_once DOL_DOCUMENT_ROOT.'/filefunc.inc.php';
}
if (!defined('MAIN_DB_PREFIX')) {
	define('MAIN_DB_PREFIX', 'llx_');
}
require_once DOL_DOCUMENT_ROOT.'/core/class/conf.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/db/sqlite3.class.php';
require_once DOL_DOCUMENT_ROOT.'/install/lib/repair.lib.php';

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class InstallRepairEcmTest extends PHPUnit\Framework\TestCase
{
	/** @var DoliDBSqlite3 Database isolated from the application database */
	private $database;
	/** @var string Temporary document root */
	private $documentroot = '';
	/** @var Conf|null Original configuration */
	private $previousconf;

	/** @return void */
	protected function setUp(): void
	{
		if (!extension_loaded('sqlite3')) {
			$this->markTestSkipped('The native SQLite driver is required for this isolated test.');
		}
		global $conf, $main_data_dir;
		$this->previousconf = $conf ?? null;
		$conf = new Conf();
		$this->documentroot = sys_get_temp_dir().'/dolibarr-ecm-repair-'.bin2hex(random_bytes(8));
		$this->assertGreaterThanOrEqual(0, dol_mkdir($this->documentroot.'/invoice'));
		$this->assertGreaterThanOrEqual(0, dol_mkdir($this->documentroot.'/2/invoice'));
		$previousdatadir = $main_data_dir ?? null;
		$main_data_dir = $this->documentroot;
		try {
			// Only the native connection is needed, without the optional custom SQL functions.
			$this->database = (new ReflectionClass(DoliDBSqlite3::class))->newInstanceWithoutConstructor();
			$this->database->db = $this->database->connect('', '', '', 'repair');
			$this->database->connected = (bool) $this->database->db;
			$this->database->transaction_opened = 0;
		} finally {
			$main_data_dir = $previousdatadir;
		}
		$this->assertTrue($this->database->connected);
		$sql = 'CREATE TABLE '.$this->database->prefix().'ecm_files (rowid INTEGER PRIMARY KEY, entity INTEGER, filepath TEXT, filename TEXT)';
		$this->assertNotFalse($this->database->query($sql));
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
			$conf = $this->previousconf;
		}
	}

	/**
	 * @param int $id Index identifier
	 * @param int $entity Owner
	 * @param string $path Relative directory
	 * @param string $filename File name
	 * @return void
	 */
	private function addEntry($id, $entity, $path, $filename = 'missing.pdf')
	{
		$sql = 'INSERT INTO '.$this->database->prefix().'ecm_files (rowid, entity, filepath, filename) VALUES (';
		$sql .= ((int) $id).', '.((int) $entity).", '".$this->database->escape($path)."', '".$this->database->escape($filename)."')";
		$this->assertNotFalse($this->database->query($sql));
	}

	/** @return int[] Remaining index identifiers */
	private function remainingIds()
	{
		$resql = $this->database->query('SELECT rowid FROM '.$this->database->prefix().'ecm_files ORDER BY rowid');
		$this->assertNotFalse($resql);
		$ids = array();
		while (is_object($obj = $this->database->fetch_object($resql))) {
			$ids[] = (int) $obj->rowid;
		}
		$this->database->free($resql);
		return $ids;
	}

	/** @return void */
	public function testPreviewDoesNotChangeEitherEntity()
	{
		$this->addEntry(1, 1, 'invoice');
		$this->addEntry(2, 2, '2/invoice');
		$report = repairEcmFiles($this->database, 2, $this->documentroot);
		$this->assertSame('', $report['error']);
		$this->assertSame(1, $report['processed']);
		$this->assertSame(1, $report['missing']);
		$this->assertSame(0, $report['deleted']);
		$this->assertSame(array(1, 2), $this->remainingIds());
	}

	/** @return void */
	public function testConfirmedRepairIsScopedAndIdempotent()
	{
		$this->addEntry(1, 1, 'invoice');
		$this->addEntry(2, 2, '2/invoice');
		$report = repairEcmFiles($this->database, 2, $this->documentroot, true);
		$this->assertSame('', $report['error']);
		$this->assertSame(1, $report['deleted']);
		$this->assertSame(array(1), $this->remainingIds());
		$report = repairEcmFiles($this->database, 2, $this->documentroot, true);
		$this->assertSame(0, $report['deleted']);
	}

	/** @return void */
	public function testExistingFilesAndNoexeVariantsArePreserved()
	{
		$this->addEntry(1, 2, '2/invoice', 'existing.pdf');
		$this->addEntry(2, 2, '2/invoice', 'blocked.php');
		file_put_contents($this->documentroot.'/2/invoice/existing.pdf', 'existing');
		file_put_contents($this->documentroot.'/2/invoice/blocked.php.noexe', 'blocked');
		$report = repairEcmFiles($this->database, 2, $this->documentroot, true);
		$this->assertSame(0, $report['missing']);
		$this->assertSame(array(1, 2), $this->remainingIds());
		$this->assertFileExists($this->documentroot.'/2/invoice/existing.pdf');
		$this->assertFileExists($this->documentroot.'/2/invoice/blocked.php.noexe');
	}

	/** @return void */
	public function testSingleEntityRepair()
	{
		$this->addEntry(1, 1, 'invoice');
		$report = repairEcmFiles($this->database, 1, $this->documentroot, true);
		$this->assertSame(1, $report['deleted']);
		$this->assertSame(array(), $this->remainingIds());
	}

	/** @return void */
	public function testUnavailableRootAndInvalidEntityDoNotDelete()
	{
		$this->addEntry(1, 2, '2/invoice');
		$report = repairEcmFiles($this->database, 2, $this->documentroot.'/unavailable', true);
		$this->assertSame('RepairEcmDirectoryUnavailable', $report['error']);
		$report = repairEcmFiles($this->database, 0, $this->documentroot, true);
		$this->assertSame('ErrorWrongParameters', $report['error']);
		$this->assertSame(array(1), $this->remainingIds());
	}

	/** @return void */
	public function testUnsafePathsAndMissingDirectoriesAreSkipped()
	{
		$this->addEntry(1, 2, '../outside');
		$this->addEntry(2, 2, '2/unavailable');
		$this->addEntry(3, 2, '/absolute');
		$this->addEntry(4, 2, '2/invoice', '../escape.pdf');
		$this->addEntry(5, 2, '2/invoice', '..\\escape.pdf');
		$report = repairEcmFiles($this->database, 2, $this->documentroot, true);
		$this->assertSame(5, $report['skipped']);
		$this->assertSame(0, $report['deleted']);
		$this->assertCount(5, $this->remainingIds());
	}

	/** @return void */
	public function testLimitAndRepeatedBatches()
	{
		$this->addEntry(1, 1, 'invoice');
		for ($id = 2; $id <= 102; $id++) {
			$this->addEntry($id, 2, '2/invoice', $id.'.pdf');
		}
		$preview = repairEcmFiles($this->database, 2, $this->documentroot);
		$this->assertTrue($preview['limitreached']);
		$this->assertCount(100, $preview['entries']);
		$this->assertCount(102, $this->remainingIds());
		$report = repairEcmFiles($this->database, 2, $this->documentroot, true);
		$this->assertSame(100, $report['deleted']);
		$this->assertTrue($report['limitreached']);
		$this->assertSame(array(1, 102), $this->remainingIds());
		$report = repairEcmFiles($this->database, 2, $this->documentroot, true);
		$this->assertSame(1, $report['deleted']);
		$this->assertFalse($report['limitreached']);
		$this->assertSame(array(1), $this->remainingIds());
	}

	/** @return void */
	public function testFailedCommitRollsBackTheBatch()
	{
		$this->addEntry(1, 2, '2/invoice');
		$database = $this->getMockBuilder(DoliDBSqlite3::class)->disableOriginalConstructor()->onlyMethods(array('commit'))->getMock();
		$database->db = $this->database->db;
		$database->transaction_opened = 0;
		$database->expects($this->once())->method('commit')->willReturn(0);
		$report = repairEcmFiles($database, 2, $this->documentroot, true);
		$this->assertSame('RepairEcmDatabaseError', $report['error']);
		$this->assertSame(0, $report['deleted']);
		$this->assertSame(array(1), $this->remainingIds());
	}

	/** @return void */
	public function testFailedBeginDoesNotDelete()
	{
		$this->addEntry(1, 2, '2/invoice');
		$database = $this->getMockBuilder(DoliDBSqlite3::class)->disableOriginalConstructor()->onlyMethods(array('begin'))->getMock();
		$database->db = $this->database->db;
		$database->expects($this->once())->method('begin')->willReturn(0);
		$report = repairEcmFiles($database, 2, $this->documentroot, true);
		$this->assertSame('RepairEcmDatabaseError', $report['error']);
		$this->assertSame(0, $report['deleted']);
		$this->assertSame(array(1), $this->remainingIds());
	}

	/** @return void */
	public function testDeleteFailureRollsBackEarlierDeletions()
	{
		$this->addEntry(1, 2, '2/invoice', 'first.pdf');
		$this->addEntry(2, 2, '2/invoice', 'second.pdf');
		$database = $this->getMockBuilder(DoliDBSqlite3::class)->disableOriginalConstructor()->onlyMethods(array('query'))->getMock();
		$database->db = $this->database->db;
		$database->transaction_opened = 0;
		$database->method('query')->willReturnCallback(function ($sql) {
			if (strpos($sql, 'DELETE FROM ') === 0 && strpos($sql, 'rowid = 2') !== false) {
				return false;
			}
			return $this->database->query($sql);
		});
		$report = repairEcmFiles($database, 2, $this->documentroot, true);
		$this->assertSame('RepairEcmDatabaseError', $report['error']);
		$this->assertSame(0, $report['deleted']);
		$this->assertSame(array(1, 2), $this->remainingIds());
	}
}
