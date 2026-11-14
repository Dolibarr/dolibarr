<?php
/*
 * Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2026 Joachim Kueter        <git-jk@bloxera.com>
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
 *
 * \file       test/phpunit/BookKeepingTest.php
 * \ingroup    test
 * \brief      PHPUnit test
 *	\remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/accountancy/class/bookkeeping.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/fiscalyear.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/triggers/interface_99_all_PhpunitBookKeeping.class.php';

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
class BookKeepingTest extends CommonClassTest
{
	/**
	 * testBookKeepingCreate
	 *
	 * @return	int
	 */
	public function testBookKeepingCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = "BookKeepingTest Unittest";
		$socid = $soc->create($user);
		$this->assertLessThan($socid, 0, $soc->errorsToString());

		$localobject = new BookKeeping($db);
		$localobject->initAsSpecimen();
		$localobject->socid = $socid;
		$result = $localobject->create($user);

		print __METHOD__." result=".$result." id=".$localobject->id."\n";
		$this->assertLessThan($result, 0, $localobject->errorsToString());
		return $localobject->id;
	}

	/**
	 * testBookKeepingFetch
	 *
	 * @param	int		$id    Id of bookkeeping entry
	 * @return	BookKeeping    BookKeeping record object
	 *
	 * @depends	testBookKeepingCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testBookKeepingFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new BookKeeping($db);
		$result = $localobject->fetch($id);
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testBookKeepingUpdate
	 *
	 * @param	BookKeeping	$localobject	BookKeeping record object
	 * @return	int
	 *
	 * @depends	testBookKeepingFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testBookKeepingUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// TODO
		$localobject->owner = 'New owner';
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testBookKeepingDelete
	 *
	 * @param	BookKeeping	$localobject	BookKeeping record object
	 * @return	int
	 *
	 * @depends	testBookKeepingUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testBookKeepingDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// TODO
		$result = $localobject->delete($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $result;
	}

	/**
	 * Changes of a whole transaction call the bookkeeping triggers for each line, and a trigger can refuse them,
	 * for example a module that locks closed periods: date, journal or reference changed, transaction validated
	 * from draft.
	 * The test runs inside one transaction, so a refused change is not rolled back here: the test checks the error.
	 *
	 * @return	void
	 */
	public function testBookKeepingTriggersOnTransactionChanges()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$fiscalperiod = $this->createFiscalPeriod('2094', '2094-01-01', '2094-12-31');
		$bookkeeping = new BookKeeping($db);
		$bookkeeping->getCanModifyBookkeepingSQL('', true);	// Reload the active fiscal periods

		$savtriggerdirs = $conf->modules_parts['triggers'];
		$conf->modules_parts['triggers'][] = '/../test/phpunit/triggers';
		InterfacePhpunitBookKeeping::$refuse = '';

		try {
			$olddate = dol_mktime(0, 0, 0, 3, 15, 2094);
			$newdate = dol_mktime(0, 0, 0, 4, 20, 2094);
			$piecenum = $this->createTransaction($olddate, '');

			// Change of the date: one BOOKKEEPING_MODIFY per line, with the old date in oldcopy
			InterfacePhpunitBookKeeping::$calls = array();
			$result = $bookkeeping->updateByMvt((string) $piecenum, 'doc_date', $db->idate($newdate));
			$this->assertGreaterThan(0, $result, $bookkeeping->errorsToString());
			$this->assertCount(2, InterfacePhpunitBookKeeping::$calls);
			foreach (InterfacePhpunitBookKeeping::$calls as $call) {
				$this->assertEquals('BOOKKEEPING_MODIFY', $call['action']);
				$this->assertGreaterThan(0, $call['id']);
				$this->assertEquals($newdate, $call['doc_date']);
				$this->assertEquals($olddate, $call['old_doc_date']);
			}

			// A trigger that refuses the change: error
			InterfacePhpunitBookKeeping::$refuse = 'BOOKKEEPING_MODIFY';
			$result = $bookkeeping->updateByMvt((string) $piecenum, 'doc_date', $db->idate($olddate));
			$this->assertLessThan(0, $result, 'A refused change of the date must return an error');
			$this->assertStringContainsString('Refused by test trigger', $bookkeeping->errorsToString());
			InterfacePhpunitBookKeeping::$refuse = '';

			// Validation of a draft transaction: one BOOKKEEPING_CREATE per line in the ledger, refused validation keeps the draft
			$draftpiecenum = $this->createTransaction($olddate, '_tmp');
			InterfacePhpunitBookKeeping::$refuse = 'BOOKKEEPING_CREATE';
			$result = $bookkeeping->transformTransaction(0, (string) $draftpiecenum);
			$this->assertLessThan(0, $result, 'A refused validation must return an error');
			$this->assertEquals(2, $this->countLines($draftpiecenum, '_tmp'), 'A refused validation must keep the draft');
			InterfacePhpunitBookKeeping::$refuse = '';

			InterfacePhpunitBookKeeping::$calls = array();
			$result = $bookkeeping->transformTransaction(0, (string) $draftpiecenum);
			$this->assertGreaterThan(0, $result, $bookkeeping->errorsToString());
			$this->assertCount(2, InterfacePhpunitBookKeeping::$calls);
			$this->assertEquals('BOOKKEEPING_CREATE', InterfacePhpunitBookKeeping::$calls[0]['action']);
			$this->assertEquals($olddate, InterfacePhpunitBookKeeping::$calls[0]['doc_date']);
			$this->assertEquals(2, $this->countLines($bookkeeping->piece_num, ''));
			$this->assertEquals(0, $this->countLines($draftpiecenum, '_tmp'));

			// notrigger: no trigger called
			InterfacePhpunitBookKeeping::$calls = array();
			$result = $bookkeeping->updateByMvt((string) $piecenum, 'doc_ref', 'PHPUNIT-NOTRIGGER', '', 1);
			$this->assertGreaterThan(0, $result, $bookkeeping->errorsToString());
			$this->assertCount(0, InterfacePhpunitBookKeeping::$calls);
		} finally {
			$conf->modules_parts['triggers'] = $savtriggerdirs;
			InterfacePhpunitBookKeeping::$refuse = '';
			$fiscalperiod->delete($user);
		}
	}

	/**
	 * Create a transaction with two lines in the ledger or in the draft table, with triggers off.
	 *
	 * @param	int		$docdate	Date of the transaction
	 * @param	string	$mode		'' for the ledger, '_tmp' for a draft
	 * @return	int					Piece num
	 */
	private function createTransaction($docdate, $mode)
	{
		global $db, $user;

		$piecenum = (new BookKeeping($db))->getNextNumMvt($mode);
		foreach (array('D' => 100, 'C' => -100) as $sens => $amount) {
			$line = new BookKeeping($db);
			$line->doc_date = $docdate;
			$line->doc_type = '';
			$line->doc_ref = 'PHPUNIT-TRIGGERS';
			$line->numero_compte = ($sens == 'D' ? '6000' : '4000');
			$line->label_compte = 'Test';
			$line->label_operation = 'Test triggers';
			$line->debit = ($sens == 'D' ? $amount : 0);
			$line->credit = ($sens == 'C' ? -$amount : 0);
			$line->montant = abs($amount);
			$line->sens = $sens;
			$line->code_journal = 'OD';
			$line->journal_label = 'Miscellaneous';
			$line->piece_num = $piecenum;
			$this->assertGreaterThan(0, $line->createStd($user, 1, $mode), $line->errorsToString());
		}

		return $piecenum;
	}

	/**
	 * Count the lines of a transaction
	 *
	 * @param	int		$piecenum	Piece num
	 * @param	string	$mode		'' for the ledger, '_tmp' for a draft
	 * @return	int
	 */
	private function countLines($piecenum, $mode)
	{
		global $db;

		$resql = $db->query("SELECT COUNT(*) as nb FROM ".MAIN_DB_PREFIX."accounting_bookkeeping".$mode." WHERE piece_num = ".((int) $piecenum));
		$obj = $db->fetch_object($resql);

		return (int) $obj->nb;
	}

	/**
	 * Closing a fiscal period needs an end date and a next period that starts after it.
	 *
	 * @return	void
	 */
	public function testCloseFiscalPeriodNeedsNextPeriod()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$periodtoclose = $this->createFiscalPeriod('2091', '2091-01-01', '2091-12-31');
		$nextperiod = $this->createFiscalPeriod('2092', '2092-01-01', '2092-12-31');

		$bookkeeping = new BookKeeping($db);
		$result = $bookkeeping->closeFiscalPeriod($periodtoclose->id, $periodtoclose->id, false, false);
		$this->assertLessThan(0, $result, 'Closing a period into itself must be refused');
		$periodtoclose->fetch($periodtoclose->id);
		$this->assertEquals(Fiscalyear::STATUS_OPEN, $periodtoclose->status, 'Refused closure must leave the period open');

		$periodwithoutend = $this->createFiscalPeriod('2093', '2093-01-01', '2093-12-31');
		$db->query("UPDATE ".MAIN_DB_PREFIX."accounting_fiscalyear SET date_end = NULL WHERE rowid = ".((int) $periodwithoutend->id));
		$result = $bookkeeping->closeFiscalPeriod($periodwithoutend->id, $nextperiod->id, false, false);
		$this->assertLessThan(0, $result, 'Closing a period without end date must be refused');

		$result = $bookkeeping->closeFiscalPeriod($periodtoclose->id, $nextperiod->id, false, false);
		$this->assertGreaterThan(0, $result, 'Closing a period into the next one failed: '.implode(',', $bookkeeping->errors));
		$periodtoclose->fetch($periodtoclose->id);
		$this->assertEquals(Fiscalyear::STATUS_CLOSED, $periodtoclose->status);

		$periodtoclose->delete($user);
		$nextperiod->delete($user);
		$periodwithoutend->delete($user);
	}

	/**
	 * Create an open fiscal period.
	 *
	 * @param	string	$label		Label
	 * @param	string	$datestart	Start date (YYYY-MM-DD)
	 * @param	string	$dateend	End date (YYYY-MM-DD)
	 * @return	Fiscalyear
	 */
	private function createFiscalPeriod($label, $datestart, $dateend)
	{
		global $db, $user;

		$fiscalperiod = new Fiscalyear($db);
		$fiscalperiod->label = $label;
		$fiscalperiod->date_start = dol_stringtotime($datestart);
		$fiscalperiod->date_end = dol_stringtotime($dateend);
		$fiscalperiod->entity = 1;
		$fiscalperiod->statut = Fiscalyear::STATUS_OPEN;
		$this->assertGreaterThan(0, $fiscalperiod->create($user), 'Fiscalyear::create failed: '.$fiscalperiod->error);

		return $fiscalperiod;
	}

	/**
	 * An abandoned customer invoice already in accounting is reversed once, at its closing date, and one not in accounting is not.
	 *
	 * @return	void
	 */
	public function testAbandonedCustomerInvoiceReversal()
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
		require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';

		$savcustomeraccount = getDolGlobalString('ACCOUNTING_ACCOUNT_CUSTOMER');
		$conf->global->ACCOUNTING_ACCOUNT_CUSTOMER = '1100';
		$fiscalperiod = $this->createFiscalPeriod('2095', '2095-01-01', '2095-12-31');
		$bookkeeping = new BookKeeping($db);
		$bookkeeping->getCanModifyBookkeepingSQL('', true);	// Reload the active fiscal periods

		try {
			$soc = new Societe($db);
			$soc->name = 'Test abandoned invoice '.dol_print_date(dol_now(), 'dayhourlog');
			$soc->client = 1;
			$soc->code_client = -1;
			$this->assertGreaterThan(0, $soc->create($user), $soc->errorsToString());
			$db->query("UPDATE ".MAIN_DB_PREFIX."societe SET code_compta = 'C-PHPUNIT' WHERE rowid = ".((int) $soc->id));

			$invoicedate = dol_mktime(0, 0, 0, 3, 1, 2095);
			$closingdate = dol_mktime(12, 0, 0, 6, 15, 2095);
			$accounted = $this->createAbandonedInvoice($soc->id, $invoicedate, $closingdate);
			$notaccounted = $this->createAbandonedInvoice($soc->id, $invoicedate, $closingdate);

			$originallines = array(
				array('1100', 'C-PHPUNIT', 120, 0),
				array('3200', '', 0, 100),
				array('2200', '', 0, 20),
			);
			foreach ($originallines as $originalline) {
				$line = new BookKeeping($db);
				$line->doc_date = $invoicedate;
				$line->doc_type = 'customer_invoice';
				$line->doc_ref = $accounted->ref;
				$line->fk_doc = $accounted->id;
				$line->fk_docdet = 0;
				$line->numero_compte = $originalline[0];
				$line->label_compte = 'Test';
				$line->subledger_account = $originalline[1];
				$line->label_operation = 'Test abandoned invoice - '.$originalline[0];
				$line->debit = $originalline[2];
				$line->credit = $originalline[3];
				$line->montant = $originalline[2] + $originalline[3];
				$line->sens = $originalline[2] ? 'D' : 'C';
				$line->code_journal = 'VT';
				$line->journal_label = 'Sale journal';
				$line->fk_user_author = $user->id;
				$this->assertGreaterThanOrEqual(0, $line->create($user, 1), $line->errorsToString());
			}
			$originalpiecenum = $line->piece_num;

			$this->assertSame(array(), $bookkeeping->getAbandonedCustomerInvoiceReversal($notaccounted->id, 'VT', 'Sale journal'));
			$this->assertEquals(0, $bookkeeping->createAbandonedCustomerInvoiceReversal($user, $notaccounted->id, 'VT', 'Sale journal'));

			$result = $bookkeeping->createAbandonedCustomerInvoiceReversal($user, $accounted->id, 'VT', 'Sale journal');
			$this->assertEquals(3, $result, $bookkeeping->errorsToString());

			$resql = $db->query("SELECT numero_compte, subledger_account, debit, credit, doc_date, piece_num, label_operation FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE doc_type = 'customer_invoice' AND fk_doc = ".((int) $accounted->id)." AND piece_num <> ".((int) $originalpiecenum)." ORDER BY rowid");
			$reversallines = array();
			while ($obj = $db->fetch_object($resql)) {
				$reversallines[] = $obj;
			}
			$this->assertCount(3, $reversallines);
			foreach ($originallines as $i => $originalline) {
				$this->assertEquals($originalline[0], $reversallines[$i]->numero_compte);
				$this->assertEquals($originalline[1], (string) $reversallines[$i]->subledger_account);
				$this->assertEquals($originalline[3], (float) $reversallines[$i]->debit);
				$this->assertEquals($originalline[2], (float) $reversallines[$i]->credit);
				$this->assertEquals(dol_print_date($closingdate, 'day'), dol_print_date($db->jdate($reversallines[$i]->doc_date), 'day'));
				$this->assertEquals($reversallines[0]->piece_num, $reversallines[$i]->piece_num);
				$this->assertStringContainsString($langs->transnoentitiesnoconv("CancellationOfAbandonedInvoice"), $reversallines[$i]->label_operation);
			}

			$resql = $db->query("SELECT SUM(debit) - SUM(credit) as balance FROM ".MAIN_DB_PREFIX."accounting_bookkeeping WHERE doc_type = 'customer_invoice' AND fk_doc = ".((int) $accounted->id)." AND numero_compte = '1100' AND subledger_account = 'C-PHPUNIT'");
			$this->assertEquals(0, (float) $db->fetch_object($resql)->balance);

			$this->assertSame(array(), $bookkeeping->getAbandonedCustomerInvoiceReversal($accounted->id, 'VT', 'Sale journal'));
			$this->assertEquals(0, $bookkeeping->createAbandonedCustomerInvoiceReversal($user, $accounted->id, 'VT', 'Sale journal'));
		} finally {
			$conf->global->ACCOUNTING_ACCOUNT_CUSTOMER = $savcustomeraccount;
			$fiscalperiod->delete($user);
		}
	}

	/**
	 * Create a validated customer invoice then classify it abandoned at a given date.
	 *
	 * @param	int		$socid			Thirdparty id
	 * @param	int		$invoicedate	Invoice date
	 * @param	int		$closingdate	Date the invoice is abandoned
	 * @return	Facture
	 */
	private function createAbandonedInvoice($socid, $invoicedate, $closingdate)
	{
		global $db, $user;

		$invoice = new Facture($db);
		$invoice->socid = $socid;
		$invoice->type = Facture::TYPE_STANDARD;
		$invoice->date = $invoicedate;
		$invoice->cond_reglement_id = 1;
		$this->assertGreaterThan(0, $invoice->create($user), $invoice->errorsToString());
		$this->assertGreaterThan(0, $invoice->addline('Service', 100, 1, 20), $invoice->errorsToString());
		$this->assertGreaterThan(0, $invoice->validate($user), $invoice->errorsToString());
		$this->assertGreaterThan(0, $invoice->setCanceled($user, Facture::CLOSECODE_ABANDONED), $invoice->errorsToString());
		$db->query("UPDATE ".MAIN_DB_PREFIX."facture SET date_closing = '".$db->idate($closingdate)."' WHERE rowid = ".((int) $invoice->id));

		return $invoice;
	}
}
