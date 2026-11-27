<?php
/*
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * \file       test/phpunit/AccountancyExportQuadratusTest.php
 * \ingroup    test
 * \brief      PHPUnit test for the Quadratus export fixed-width layout
 */

use PHPUnit\Framework\TestCase;

// This test does not need a database: only the pure formatting code of the class is used.
if (!defined('DOL_DOCUMENT_ROOT')) {
	define('DOL_DOCUMENT_ROOT', dirname(__FILE__).'/../../htdocs');
}
if (!defined('DOL_CLASS_PATH')) {
	define('DOL_CLASS_PATH', '/class/');
}
if (!class_exists('DoliDB', false)) {
	/**
	 * Minimal stand-in: the export code only stores the handler
	 */
	abstract class DoliDB
	{
	}
}
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once dirname(__FILE__).'/../../htdocs/accountancy/class/accountancyexport.class.php';

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 */
class AccountancyExportQuadratusTest extends TestCase
{
	/**
	 * The "C" (account) line must keep its fixed width whatever the length of the third party name.
	 *
	 * @return void
	 */
	public function testQuadratusAccountLineWidthWithShortName()
	{
		global $conf, $hookmanager;

		$conf = new stdClass();
		$conf->cache = array();
		$conf->tzuserinputkey = "tzserver";
		$conf->global = new stdClass();
		$conf->currency = "EUR";
		$db = $this->createMock(DoliDB::class);
		$hookmanager = $this->createMock(HookManager::class);

		$lengths = array();
		foreach (array('LONGET', 'AB', 'A VERY LONG THIRDPARTY NAME') as $name) {
			$line = new stdClass();
			$line->doc_ref = 'FA2611-0001';
			$line->label_operation = 'Invoice';
			$line->numero_compte = '411000';
			$line->label_compte = 'Customers';
			$line->subledger_account = '0LONGET';
			$line->subledger_label = $name;
			$line->doc_type = 'customer_invoice';
			$line->doc_date = 1795132800;
			$line->code_journal = 'VT';
			$line->piece_num = 1;
			$line->sens = 'D';
			$line->debit = 10;
			$line->credit = 0;
			$line->date_lim_reglement = 0;

			$export = new AccountancyExport($db);
			$file = fopen('php://memory', 'w+');
			$export->exportQuadratus(array($line), $file);
			rewind($file);
			$content = stream_get_contents($file);
			fclose($file);

			$rows = explode("\r\n", $content);
			$this->assertSame('C', substr($rows[0], 0, 1));
			$lengths[] = strlen($rows[0]);
		}

		$this->assertSame(array($lengths[2], $lengths[2], $lengths[2]), $lengths, 'C line width must not depend on the third party name length');
	}
}
