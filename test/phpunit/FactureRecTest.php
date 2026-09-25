<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/FactureRecTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture-rec.class.php';
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
class FactureRecTest extends CommonClassTest
{
	/**
	 * testFactureRecCreate
	 *
	 * @return int
	 */
	public function testFactureRecCreate()
	{
		global $conf,$user,$langs,$db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectinv = new Facture($db);
		$localobjectinv->initAsSpecimen();
		$result = $localobjectinv->create($user);

		print __METHOD__." result=".$result."\n";

		$localobject = new FactureRec($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user, $localobjectinv->id);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Create recurring invoice from common invoice: '.$localobject->error);

		return $result;
	}

	/**
	 * testFactureRecFetch
	 *
	 * @param  int 	$id  	Id of created recuriing invoice
	 * @return int
	 *
	 * @depends testFactureRecCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureRecFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new FactureRec($db);
		$result = $localobject->fetch($id);

		print __METHOD__." result=".$result."\n";
		$this->assertGreaterThan(0, $result);
		return $result;
	}



	/**
	 * Recurring invoice templates are processed before their next generation date when
	 * INVOICE_RECURRING_GENERATION_ADDDAYS is set, and the generated invoice keeps that date.
	 *
	 * @return void
	 */
	public function testCreateRecurringInvoicesAheadOfTime()
	{
		global $conf, $user, $langs, $db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobjectinv = new Facture($db);
		$localobjectinv->initAsSpecimen();
		$result = $localobjectinv->create($user);
		$this->assertGreaterThan(0, $result, 'Create source invoice: '.$localobjectinv->error);

		$datewhen = dol_time_plus_duree(dol_now(), 3, 'd');

		$localobject = new FactureRec($db);
		$localobject->initAsSpecimen();
		$localobject->title = 'SPECIMENADDDAYS';	// llx_facture_rec has a unique index on (titre, entity)
		$localobject->frequency = 1;
		$localobject->unit_frequency = 'm';
		$localobject->date_when = $datewhen;
		$result = $localobject->create($user, $localobjectinv->id);
		$this->assertGreaterThan(0, $result, 'Create invoice template: '.$localobject->error);

		// getNextDate() has its own end of month rule, so the expected next date is taken from the
		// template itself, while its date_when is still the initial one.
		$expectednextdate = $localobject->getNextDate();
		$savoption = isset($conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS) ? $conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS : null;

		try {
			$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = 0;
			$result = $localobject->createRecurringInvoices($localobject->id);
			$this->assertSame(0, $result, 'Generation must not report an error: '.$localobject->error);
			$this->assertStringContainsString($langs->transnoentitiesnoconv('NoQualifiedRecurringInvoiceTemplateFound'), $localobject->output, 'The template must not even be qualified without the option');
			$this->assertCount(0, $this->getInvoicesGeneratedFrom($localobject->id), 'A template dated in 3 days must not be generated without the option');

			$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = 5;
			$result = $localobject->createRecurringInvoices($localobject->id);
			$this->assertSame(0, $result, 'Generation must not report an error: '.$localobject->error);
			$generatedids = $this->getInvoicesGeneratedFrom($localobject->id);
			$this->assertCount(1, $generatedids, 'A template dated in 3 days must be generated with 5 days in advance');

			$generated = new Facture($db);
			$result = $generated->fetch($generatedids[0]);
			$this->assertGreaterThan(0, $result, 'Fetch generated invoice: '.$generated->error);
			$this->assertSame(dol_print_date($datewhen, 'day'), dol_print_date($generated->date, 'day'), 'The invoice generated in advance must keep the generation date of the template');

			$reloaded = new FactureRec($db);
			$result = $reloaded->fetch($localobject->id);
			$this->assertGreaterThan(0, $result, 'Fetch template again: '.$reloaded->error);
			$this->assertSame(dol_print_date($expectednextdate, 'day'), dol_print_date($reloaded->date_when, 'day'), 'Generating in advance must not shift the schedule: the next date is computed from the previous one, not from today');
		} finally {
			// $conf is shared by reference with the next test suites, a failed assertion must not leak the option
			if ($savoption === null) {
				unset($conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS);
			} else {
				$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = $savoption;
			}
		}
	}

	/**
	 * A template not yet due is generated in advance only once its previous occurrence is past, while a
	 * late template is still caught up occurrence by occurrence.
	 *
	 * @return void
	 */
	public function testCreateRecurringInvoicesAtMostOneInAdvance()
	{
		global $conf, $user, $langs, $db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$savoption = isset($conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS) ? $conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS : null;

		try {
			$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = 30;

			$template = $this->createWeeklyTemplate('SPECIMENADVANCEONE', dol_time_plus_duree(dol_now(), 2, 'd'));
			for ($call = 0; $call < 3; $call++) {
				$result = $template->createRecurringInvoices($template->id);
				$this->assertSame(0, $result, 'Generation must not report an error: '.$template->error);
			}
			$this->assertCount(1, $this->getInvoicesGeneratedFrom($template->id), 'A weekly template with 30 days in advance must never be more than one invoice ahead');

			// Two late occurrences, the one due today, then a single one in advance
			$template = $this->createWeeklyTemplate('SPECIMENADVANCELATE', dol_time_plus_duree(dol_now(), -14, 'd'));
			for ($call = 0; $call < 6; $call++) {
				$result = $template->createRecurringInvoices($template->id);
				$this->assertSame(0, $result, 'Generation must not report an error: '.$template->error);
			}
			$this->assertCount(4, $this->getInvoicesGeneratedFrom($template->id), 'The late occurrences must still be caught up, plus one invoice in advance');
		} finally {
			if ($savoption === null) {
				unset($conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS);
			} else {
				$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = $savoption;
			}
		}
	}

	/**
	 * Without the option, only the occurrences due up to today are generated, as before the option existed.
	 *
	 * @return void
	 */
	public function testCreateRecurringInvoicesWithoutAdvance()
	{
		global $conf, $user, $langs, $db;

		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$savoption = isset($conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS) ? $conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS : null;

		try {
			$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = 0;

			$template = $this->createWeeklyTemplate('SPECIMENNOADVANCELATE', dol_time_plus_duree(dol_now(), -14, 'd'));
			for ($call = 0; $call < 6; $call++) {
				$result = $template->createRecurringInvoices($template->id);
				$this->assertSame(0, $result, 'Generation must not report an error: '.$template->error);
			}
			$this->assertCount(3, $this->getInvoicesGeneratedFrom($template->id), 'Only the two late occurrences and the one due today must be generated');

			$reloaded = new FactureRec($db);
			$result = $reloaded->fetch($template->id);
			$this->assertGreaterThan(0, $result, 'Fetch template again: '.$reloaded->error);
			$this->assertFalse($reloaded->isDueForGeneration(), 'The next occurrence, in 7 days, must not be due');
			$this->assertTrue($reloaded->isDueForGeneration((int) $reloaded->date_when), 'The next occurrence must be due on its own day');
		} finally {
			if ($savoption === null) {
				unset($conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS);
			} else {
				$conf->global->INVOICE_RECURRING_GENERATION_ADDDAYS = $savoption;
			}
		}
	}

	/**
	 * The generation limit is computed on calendar days: it may overflow the month and keeps 23:59:59
	 * across a DST change.
	 *
	 * @return void
	 */
	public function testGetEndOfDayShifted()
	{
		$this->assertSame(dol_mktime(23, 59, 59, 2, 4, 2027, 'tzserver'), FactureRec::getEndOfDayShifted(dol_mktime(12, 0, 0, 1, 30, 2027, 'tzserver'), 5));
		$this->assertSame(dol_mktime(23, 59, 59, 3, 3, 2027, 'tzserver'), FactureRec::getEndOfDayShifted(dol_mktime(12, 0, 0, 2, 26, 2027, 'tzserver'), 5));
		$this->assertSame(dol_mktime(23, 59, 59, 1, 4, 2027, 'tzserver'), FactureRec::getEndOfDayShifted(dol_mktime(12, 0, 0, 12, 30, 2026, 'tzserver'), 5));

		$savtz = date_default_timezone_get();
		try {
			date_default_timezone_set('Europe/Paris');
			$result = FactureRec::getEndOfDayShifted(dol_mktime(12, 0, 0, 10, 22, 2026, 'tzserver'), 5);
			$this->assertSame('2026-10-27 23:59:59', date('Y-m-d H:i:s', $result), 'The limit must stay at 23:59:59 after the switch to winter time');
		} finally {
			date_default_timezone_set($savtz);
		}
	}

	/**
	 * Create a weekly invoice template, from a new specimen invoice.
	 *
	 * @param	string		$title		Title of the template, unique by entity
	 * @param	int			$datewhen	Next generation date
	 * @return	FactureRec				The created template
	 */
	private function createWeeklyTemplate(string $title, int $datewhen): FactureRec
	{
		global $user, $db;

		$invoice = new Facture($db);
		$invoice->initAsSpecimen();
		$result = $invoice->create($user);
		$this->assertGreaterThan(0, $result, 'Create source invoice: '.$invoice->error);

		$template = new FactureRec($db);
		$template->initAsSpecimen();
		$template->title = $title;
		$template->frequency = 7;
		$template->unit_frequency = 'd';
		$template->date_when = $datewhen;
		$result = $template->create($user, $invoice->id);
		$this->assertGreaterThan(0, $result, 'Create invoice template: '.$template->error);

		return $template;
	}

	/**
	 * Return the ids of the invoices already generated from an invoice template.
	 *
	 * @param	int			$id		Id of the invoice template
	 * @return	int[]				Ids of the invoices generated from this template
	 */
	private function getInvoicesGeneratedFrom(int $id): array
	{
		global $db;

		$ids = array();

		$sql = "SELECT rowid FROM ".$db->prefix()."facture";
		$sql .= " WHERE fk_fac_rec_source = ".((int) $id);
		$sql .= $db->order('rowid', 'ASC');

		$resql = $db->query($sql);
		$this->assertNotFalse($resql, 'Search invoices generated from template '.$id.': '.$db->lasterror());

		while ($obj = $db->fetch_object($resql)) {
			$ids[] = (int) $obj->rowid;
		}
		$db->free($resql);

		return $ids;
	}

	/**
	 * Edit an object to test updates
	 *
	 * @param 	FactureRec	$localobject		Object Facture rec
	 * @return	void
	 */
	public function changeProperties(&$localobject)
	{
		$localobject->note_private = 'New note';
		//$localobject->note='New note after update';
	}
}
