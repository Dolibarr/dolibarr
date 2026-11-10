<?php
/* Copyright (C) 2026		Maxime DION				<m.dion@guiltek.fr>
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
 *      \file       test/phpunit/DolReceiptPrinterTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf, $user, $langs, $db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__) . '/../../htdocs/master.inc.php';
require_once dirname(__FILE__) . '/../../htdocs/blockedlog/lib/blockedlog.lib.php';
require_once dirname(__FILE__) . '/../../htdocs/takepos/class/dolreceiptprinter.class.php';
require_once dirname(__FILE__) . '/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__) . '/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks    backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class DolReceiptPrinterTest extends CommonClassTest
{
	/**
	 * @var string[]	Undefined array key warnings raised while a ticket is printed
	 */
	public static $phperrors = array();

	/**
	 * Error handler that records the 'Undefined ...' messages (PHPUnit would turn them into an exception that the printer code catches)
	 *
	 * @param	int		$errno		Error level
	 * @param	string	$errstr		Error message
	 * @return	bool				True: the error is handled
	 */
	public static function collectPhpError($errno, $errstr)
	{
		if (strpos($errstr, 'Undefined') === 0) {
			self::$phperrors[] = $errstr;
		}
		return true;
	}

	/**
	 * testSendToPrinterTagWithoutText
	 *
	 * A tag directly followed by another tag has no text, so no 'value' key once the template is parsed.
	 * It must print nothing and let the ticket go on, instead of sending null to the printer library.
	 *
	 * @return void
	 */
	public function testSendToPrinterTagWithoutText()
	{
		global $conf, $user, $langs, $db, $mysoc;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		if (!extension_loaded('intl')) {
			$this->markTestSkipped('The receipt printer requires the PHP intl extension.');
		}
		if (isALNERunningVersion()) {
			$this->markTestSkipped('The certified version always prints the default template.');
		}

		$savmysoc = $mysoc;
		$savmethod = getDolGlobalString('TAKEPOS_PRINT_METHOD');
		if (!is_object($mysoc)) {
			$mysoc = new Societe($db);
		}
		$conf->global->TAKEPOS_PRINT_METHOD = 'takeposconnector';	// In-memory connector: the printed bytes are echoed in base64

		$object = new Facture($db);
		$object->id = 0;
		$object->ref = 'PHPUNITREF';
		$object->date = dol_now();
		$object->status = 2;
		$object->thirdparty = new Societe($db);
		$object->thirdparty->phone = '';	// An optional value left empty is the usual cause

		// A template has no closing tag: a tag keeps the text that follows it, up to the next tag.
		$template = '{dol_print_text}{dol_value_customer_phone}{dol_line_feed}';
		$template .= '{dol_print_barcode}{dol_value_customer_phone}{dol_line_feed}';
		$template .= '{dol_print_qrcode}{dol_value_customer_phone}{dol_line_feed}';
		$template .= '{dol_print_curr_date}{dol_line_feed}';
		$template .= '{dol_unknown_tag}{dol_line_feed}';
		$template .= "{dol_print_text}after\r\n";

		$receiptprinter = new dolReceiptPrinter($db);
		$this->assertEquals(0, $receiptprinter->addTemplate('PHPUNIT_EMPTYTAGS', $template));
		$templateid = (int) $db->last_insert_id(MAIN_DB_PREFIX.'printer_receipt_template');
		$this->assertGreaterThan(0, $templateid);

		$result = -1;
		self::$phperrors = array();
		set_error_handler(array('DolReceiptPrinterTest', 'collectPhpError'));
		ob_start();
		try {
			$result = $receiptprinter->sendToPrinter($object, $templateid, 0);
		} finally {
			restore_error_handler();
			$printed = (string) base64_decode(strtr(ob_get_clean(), '-_', '+/'));
			$receiptprinter->deleteTemplate($templateid);
		}
		print __METHOD__ . " result=" . $result . "\n";
		$this->assertEquals(2, $result, 'Only the empty barcode and the unknown tag are reported as errors');
		$this->assertStringContainsString('after', $printed, 'The ticket stopped at a tag without text');
		$this->assertEquals(array(), self::$phperrors, 'PHP warning: '.implode(' | ', self::$phperrors));

		$mysoc = $savmysoc;
		$conf->global->TAKEPOS_PRINT_METHOD = $savmethod;
	}
}
