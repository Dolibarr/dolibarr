<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/ImportTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ImportTest extends CommonClassTest
{
	/**
	 * testImportSample1
	 *
	 * @return boolean
	 */
	public function testImportSample1()
	{
		$file = dirname(__FILE__).'/Example_import_company_1.csv';

		// TODO
		// Run import on file and check the record with field "auto" are filled
		// according to option
		$this->assertEquals(0, 0);

		// TODO Export and compare the file exported with the $file imported.



		return true;
	}

	/**
	 * testImportCsvReadRecord
	 *
	 * Check that the BOM is not part of the first value, that a long line is not cut, and that a separator
	 * or an enclosure of more than one char is refused (and does not throw a ValueError)
	 *
	 * @return void
	 */
	public function testImportCsvReadRecord()
	{
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/core/modules/import/import_csv.modules.php';

		$long = str_repeat('a', 150000);
		$content = "\xEF\xBB\xBFref;label;note\r\nP1;\"Label; with separator\";".$long."\r\nP2;\xC3\xA9t\xC3\xA9;\r\n";
		$file = tempnam(sys_get_temp_dir(), 'importcsv');
		file_put_contents($file, $content);

		$obj = new ImportCsv($db, 'product_1');
		$obj->separator = ';';
		$obj->enclosure = '"';
		$this->assertEquals(1, $obj->import_open_file($file));

		$header = $obj->import_read_record();
		$this->assertIsArray($header);
		$this->assertEquals('ref', $header[0]['val'], 'The BOM must not be part of the first value');

		$record = $obj->import_read_record();
		$this->assertIsArray($record);
		$this->assertCount(3, $record, 'A line longer than 100000 bytes must not be split in two records');
		$this->assertEquals('P1', $record[0]['val']);
		$this->assertEquals('Label; with separator', $record[1]['val']);
		$this->assertEquals(150000, strlen($record[2]['val']));

		$record = $obj->import_read_record();
		$this->assertIsArray($record);
		$this->assertEquals('P2', $record[0]['val']);
		$this->assertEquals("\xC3\xA9t\xC3\xA9", $record[1]['val']);
		$this->assertFalse($obj->import_read_record());
		$obj->import_close_file();

		// A file without BOM is read from its first byte
		file_put_contents($file, "ref;label\r\nP1;test\r\n");
		$this->assertEquals(1, $obj->import_open_file($file));
		$header = $obj->import_read_record();
		$this->assertIsArray($header);
		$this->assertEquals('ref', $header[0]['val']);
		$obj->import_close_file();

		// A separator or an enclosure of more than one char is refused
		$obj->separator = ';;';
		$this->assertEquals(-1, $obj->import_open_file($file));
		$this->assertNotEmpty($obj->error);
		$obj->separator = '';
		$this->assertEquals(-1, $obj->import_open_file($file));
		$obj->separator = ';';
		$obj->enclosure = '""';
		$this->assertEquals(-1, $obj->import_open_file($file));

		unlink($file);
	}
}
