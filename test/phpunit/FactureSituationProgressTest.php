<?php
/* Copyright (C) 2026       ATM Consulting          <support@atm-consulting.fr>
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
 *      \file       test/phpunit/FactureSituationProgressTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test of the progress of situation invoice lines in progressive mode (INVOICE_USE_SITUATION = 2)
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FactureSituationProgressTest extends CommonClassTest
{
	/**
	 * setUp
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf, $invoicecache;

		parent::setUp();
		$conf->global->INVOICE_USE_SITUATION = 2;
		$invoicecache = array();
	}

	/**
	 * Insert an invoice of a situation cycle
	 *
	 * @param int $type   Invoice type
	 * @param int $cycle  Situation cycle
	 * @param int $status Invoice status
	 * @return int        Invoice id
	 */
	private function insertInvoice(int $type, int $cycle, int $status): int
	{
		$db = $this->savdb;
		$resql = $db->query("SELECT MIN(rowid) as socid FROM ".$db->prefix()."societe");
		$obj = $db->fetch_object($resql);
		$db->free($resql);

		$sql = "INSERT INTO ".$db->prefix()."facture (ref, entity, fk_soc, datec, datef, type, fk_statut, situation_cycle_ref, situation_counter, situation_final)";
		$sql .= " VALUES ('".$db->escape(uniqid('PHPUNITSIT'))."', 1, ".((int) $obj->socid).", '".$db->idate(dol_now())."', '".$db->idate(dol_now())."', ".$type.", ".$status.", ".$cycle.", 1, 0)";
		$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror().' '.$sql);

		return (int) $db->last_insert_id($db->prefix().'facture');
	}

	/**
	 * Insert a line of a situation cycle
	 *
	 * @param int       $invoiceId Invoice id
	 * @param float     $percent   situation_percent of the line
	 * @param int|null  $prevId    Previous line
	 * @return int                 Line id
	 */
	private function insertLine(int $invoiceId, float $percent, ?int $prevId): int
	{
		$db = $this->savdb;
		$sql = "INSERT INTO ".$db->prefix()."facturedet (fk_facture, description, qty, subprice, tva_tx, total_ht, total_tva, total_ttc, product_type, situation_percent, fk_prev_id)";
		$sql .= " VALUES (".$invoiceId.", 'Lot A', 1, 1000, 0, ".(10 * $percent).", 0, ".(10 * $percent).", 1, ".$percent.", ".($prevId ? $prevId : 'null').")";
		$this->assertTrue((bool) $db->query($sql), (string) $db->lasterror().' '.$sql);

		return (int) $db->last_insert_id($db->prefix().'facturedet');
	}

	/**
	 * Progress before a new line of $invoiceId linked to $prevId
	 *
	 * @param int $invoiceId Invoice holding the new line
	 * @param int $prevId    Previous line
	 * @return float
	 */
	private function previousProgress(int $invoiceId, int $prevId): float
	{
		$line = new FactureLigne($this->savdb);
		$line->fk_prev_id = $prevId;

		return (float) $line->getAllPrevProgress($invoiceId);
	}

	/**
	 * A credit note brought back to 50 % on a line invoiced at 60 % credits the 10 % left, as a negative amount
	 *
	 * @return void
	 */
	public function testPartialCreditNoteCreditsTheDifference()
	{
		$db = $this->savdb;
		$cycle = 900000 + mt_rand(1, 99999);
		$s1 = $this->insertInvoice(Facture::TYPE_SITUATION, $cycle, Facture::STATUS_VALIDATED);
		$l1 = $this->insertLine($s1, 30, null);
		$s2 = $this->insertInvoice(Facture::TYPE_SITUATION, $cycle, Facture::STATUS_VALIDATED);
		$l2 = $this->insertLine($s2, 30, $l1);
		$creditNoteId = $this->insertInvoice(Facture::TYPE_CREDIT_NOTE, $cycle, Facture::STATUS_DRAFT);
		$lineId = $this->insertLine($creditNoteId, 0, $l2);
		$db->query("UPDATE ".$db->prefix()."facturedet SET subprice = -1000 WHERE rowid = ".$lineId);

		$creditNote = new Facture($db);
		$creditNote->fetch($creditNoteId);
		$creditNote->update_percent($creditNote->lines[0], 50, false);

		$line = new FactureLigne($db);
		$line->fetch($lineId);
		$this->assertEquals(10, $line->situation_percent);
		$this->assertEquals(-100, $line->total_ht);
	}

	/**
	 * After a removal from cycle, the new cycle does not inherit the progress of the old one
	 *
	 * @return void
	 */
	public function testPreviousProgressStopsAtCycleBoundary()
	{
		$oldCycle = 900000 + mt_rand(1, 99999);
		$newCycle = $oldCycle + 100000;
		$s1 = $this->insertInvoice(Facture::TYPE_SITUATION, $oldCycle, Facture::STATUS_VALIDATED);
		$l1 = $this->insertLine($s1, 30, null);
		$removed = $this->insertInvoice(Facture::TYPE_SITUATION, $newCycle, Facture::STATUS_CLOSED);
		$lRemoved = $this->insertLine($removed, 30, $l1);

		$this->assertEquals(0, $this->previousProgress($removed, $l1), 'line of the removed invoice');
		$nextNew = $this->insertInvoice(Facture::TYPE_SITUATION, $newCycle, Facture::STATUS_DRAFT);
		$this->assertEquals(30, $this->previousProgress($nextNew, $lRemoved), 'next situation of the new cycle');
		$nextOld = $this->insertInvoice(Facture::TYPE_SITUATION, $oldCycle, Facture::STATUS_DRAFT);
		$this->assertEquals(30, $this->previousProgress($nextOld, $l1), 'next situation of the old cycle');
	}
}
