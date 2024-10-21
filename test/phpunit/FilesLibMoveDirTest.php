<?php
/* Copyright (C) 2010-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2023		Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/FilesLibRenameTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit test/phpunit/FilesLibRenameTest.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FilesLibMoveDirTest extends CommonClassTest
{
	protected $sourceDir;
	protected $destinationDir;

	/**
	 * Setup test case
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setup();

		global $conf;
		$this->sourceDir = $conf->admin->dir_temp."/source Dir";
		$this->destinationDir = $conf->admin->dir_temp.'/dest dir';

		$this->cleanupDirectories();
		mkdir($this->sourceDir, 0777, true);
		file_put_contents($this->sourceDir . '/file1.txt', 'Test file 1 content');
		file_put_contents($this->sourceDir . '/file2.txt', 'Test file 2 content');

		// Create a temporary destination directory
		mkdir($this->destinationDir);

		$this->nbLinesToShow = 0;  // Nothing useful in the dolibarr log for debugging.
	}

	/**
	 * Tear down test case
	 *
	 * Cleanup files that were created.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		parent::tearDown();
		$this->cleanupDirectories();
	}

	/**
	 * Clean up the test directories
	 *
	 * @return bool True if success
	 */
	protected function cleanUpDirectories()
	{
		if (is_dir($this->sourceDir)) {
			$this->removeDirectory($this->sourceDir);
		}
		if (is_dir($this->destinationDir)) {
			$this->removeDirectory($this->destinationDir);
		}
	}

	/**
	 * Remove a directory (not a test)
	 *
	 * @param $dir Directory to test for emptyness
	 *
	 * @return bool True if success
	 */
	protected function removeDirectory($dir)
	{
		$files = array_diff(scandir($dir), array('.', '..'));
		foreach ($files as $file) {
			(is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
		}
		return rmdir($dir);
	}


	/**
	 * Assert a directory is empty
	 *
	 * @param $dir 		string	Directory to test for emptyness
	 * @param $message 	string	Message in case of failure
	 *
	 * @return bool True if success
	 */
	protected function assertDirectoryIsEmpty($dir, $message = '')
	{
		// Check if the destination directory is empty
		$filesInDirectory = array_diff(scandir($dir), ['.', '..']);
		$this->assertDirectoryExists($dir, $message);
		//var_dump($filesInDirectory); exit;
		$this->assertEmpty($filesInDirectory, $message."(".implode(",", $filesInDirectory).")");
	}



	/**
	 * Test "renaming" a non empty directory to an empty one
	 *
	 * @return void
	 */
	public function testRenameNonEmptyDirToEmptyDir()
	{
		// Compute and store MD5 checksums of source files
		$file1MD5 = md5_file($this->sourceDir . '/file1.txt');
		$file2MD5 = md5_file($this->sourceDir . '/file2.txt');

		$this->assertTrue(dol_move_dir($this->sourceDir, $this->destinationDir), "Failed renaming '{$this->sourceDir}' to '{$this->destinationDir}'");
		$this->assertDirectoryExists($this->destinationDir);
		$this->assertFileNotExistsCompat($this->sourceDir . '/file1.txt', "file1.txt must not exist in source");
		$this->assertFileNotExistsCompat($this->sourceDir . '/file2.txt', "file2.txt must not exist in source");
		$this->assertFileExists($this->destinationDir . '/file1.txt', "file1.txt must exist in destination");
		$this->assertFileExists($this->destinationDir . '/file2.txt', "file2.txt must exist in destination");
		// Validate MD5 checksums
		$this->assertEquals(md5_file($this->destinationDir . '/file1.txt'), $file1MD5, "file1.txt was not moved correctly");
		$this->assertEquals(md5_file($this->destinationDir . '/file2.txt'), $file2MD5, "file2.txt was not moved correctly");
	}

	/**
	 * Test "renaming" an empty directory to an non empty one
	 *
	 * @return void
	 *
	 * @depends	testRenameNonEmptyDirToEmptyDir
	 * The depends says test is run only if previous is ok
	 */
	public function testRenameEmptyDirToNonEmptyDir()
	{
		mkdir($src = $this->sourceDir . '/rename_empty_directory');
		mkdir($dst = $this->destinationDir . '/dest_nonempty_directory');

		// Create some files in the destination directory
		file_put_contents($dst . '/file2.txt', 'Dest Test file 2 content');
		file_put_contents($dst . '/file3.txt', 'Dest Test file 3 content');
		$file2MD5 = md5_file($dst . '/file2.txt');
		$file3MD5 = md5_file($dst . '/file3.txt');

		$this->assertFalse(dol_move_dir($src, $dst), "Rename empty to non empty directory must fail");
		$this->assertDirectoryExists($src, "Empty source directory must still exist");
		$this->assertDirectoryExists($dst, "Destination directory does not exist");
		$this->assertFileExists($dst . '/file2.txt', "File2 should still exist");
		$this->assertFileExists($dst . '/file3.txt', "File3 should still exist");
		// Validate MD5 checksums
		$this->assertEquals($file2MD5, md5_file($dst . '/file2.txt'), "File2 does not have the expected contents");
		$this->assertEquals($file3MD5, md5_file($dst . '/file3.txt'), "File3 does not have the expected contents");
	}

	/**
	 * Test "renaming" an empty directory to an existing non empty one
	 *
	 * @return void
	 *
	 * @depends	testRenameEmptyDirToNonEmptyDir
	 * The depends says test is run only if previous is ok
	 */
	public function testRenameEmptyDirToExistingEmptyDir()
	{
		// Create an empty directory
		mkdir($src = $this->sourceDir . '/empty_directory');
		mkdir($dst = $this->destinationDir . '/destEmpty_directory');

		$this->assertDirectoryIsEmpty($src, 'Source directory is not empty before rename.');
		$this->assertDirectoryIsEmpty($dst, 'Destination directory is not empty before rename.');
		$this->assertTrue(dol_move_dir($src, $dst), "Renaming the empty directory to an empty one did not succeed");

		$this->assertDirectoryNotExistsCompat($src);
		$this->assertDirectoryExists($dst, "The destination directory does not exist");
		$this->assertDirectoryIsEmpty($dst, 'Destination directory is not empty after rename.');
	}

	/**
	 * Test "renaming" an non empty directory to an non empty one
	 *
	 * @return void
	 *
	 * @depends	testRenameEmptyDirToExistingEmptyDir
	 * The depends says test is run only if previous is ok
	 */
	public function testRenameNonEmptyDirToNonEmptyDir()
	{
		// The source directory and destination directory also have same file names.
		mkdir($src = $this->sourceDir . '/src_directory');
		file_put_contents($src . '/file1.txt', 'Src Test file 1 content');
		file_put_contents($src . '/file3.txt', 'Src Test file 3 content');
		$srcFile1MD5 = md5_file($src . '/file1.txt');
		$srcFile3MD5 = md5_file($src . '/file3.txt');

		// Create some files in the destination directory
		mkdir($dst = $this->destinationDir . '/destNonEmpty_directory');
		file_put_contents($dst . '/file2.txt', 'Dest Test file 2 content');
		file_put_contents($dst . '/file3.txt', 'Dest Test file 3 content');
		$dstFile2MD5 = md5_file($dst . '/file2.txt');
		$dstFile3MD5 = md5_file($dst . '/file3.txt');

		// Try to rename
		$this->assertFalse(dol_move_dir($src, $dst), "Rename non empty directory to non empty directory must fail");

		// Validate result
		$this->assertDirectoryExists($src, "Source directory must still exist");
		$this->assertDirectoryExists($dst, "Destination directory must still exist");
		$this->assertFileExists($dst . '/file2.txt', "File2 does not exist");
		$this->assertFileExists($dst . '/file3.txt', "File3 does not exist");
		// Validate MD5 checksums
		$this->assertEquals(md5_file($src . '/file1.txt'), $srcFile1MD5, "SrcFile1 does not have the expected contents");
		$this->assertEquals(md5_file($src . '/file3.txt'), $srcFile3MD5, "SrcFile3 does not have the expected contents");
		$this->assertEquals(md5_file($dst . '/file2.txt'), $dstFile2MD5, "DstFile1 does not have the expected contents");
		$this->assertEquals(md5_file($dst . '/file3.txt'), $dstFile3MD5, "DstFile3 does not have the expected contents");
	}


	/**
	 * Test "renaming" an directory with depth and contents to an non existing one
	 *
	 * @return void
	 *
	 * @depends	testRenameNonEmptyDirToNonEmptyDir
	 * The depends says test is run only if previous is ok
	 */
	public function testRenameDirectoryDepthOfTwo()
	{
		// Create a directory with depth of 2
		mkdir($srcDir = $this->sourceDir . '/subdirectory');
		$dstDir = $this->destinationDir . '/subdirectory';
		file_put_contents($srcDir.'/file3.txt', 'Test file 3 content');

		// Compute and store MD5 checksums of source files
		$file3MD5 = md5_file($srcDir.'/file3.txt');

		$this->assertTrue(dol_move_dir($srcDir, $dstDir), "Rename did not succeed");
		$this->assertDirectoryExists($dstDir, "Destination directory does not exist");
		$this->assertDirectoryExists($dstDir, "Destination subdirectory does not exist");
		$this->assertFileExists($dstDir . '/file3.txt', "Destination file does not exist");
		// Validate MD5 checksum
		$this->assertEquals($file3MD5, md5_file($dstDir . '/file3.txt'), "The destination file's contents is not correct");
	}
}
