<?php
/* Copyright (C) 2010       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2025  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2023       Alexandre Janniaux      <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/FactureTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/modBlockedLog.class.php';
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
class FactureTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('invoice'), " module customer invoice must be enabled");
		self::assertFalse(isModEnabled('ecotaxdeee'), " module ecotaxdeee must not be enabled");
		parent::setUpBeforeClass();

		// We disable module blocked log to avoid interference with tests
		global $db;
		$blockedlogmodule = new modBlockedLog($db);
		$blockedlogmodule->remove();
	}


	/**
	 * testFactureCreate
	 *
	 * @return int
	 */
	public function testFactureCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Facture($db);
		$localobject->initAsSpecimen();
		$result = $localobject->create($user);
		$this->assertLessThan($result, 0);
		print __METHOD__." result=".$result."\n";
		return $result;
	}

	/**
	 * testSendEmailsRemindersTemplateNotFound
	 *
	 * @return  void
	 */
	public function testSendEmailsRemindersTemplateNotFound()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$conf->global->MAIN_MAIL_EMAIL_FROM = 'test@example.com';
		$db->query("INSERT INTO ".$db->prefix()."c_email_templates (entity, type_template, private, datec, label, position, enabled, active, topic, joinfiles, content) VALUES (1, 'facture_send', 0, '".$db->idate(dol_now())."', 'TestRemindFound', 990, '1', 1, 'Topic', 0, 'Content')");

		// An invoice due today, for a third party with an email
		$soc = new Societe($db);
		$soc->name = 'TESTREMIND template '.dol_now();
		$soc->email = 'customer@example.com';
		$soc->client = 1;
		$soc->code_client = -1;
		$socid = $soc->create($user);
		$invoice = new Facture($db);
		$invoice->socid = $socid;
		$invoice->date = dol_now();
		$invoice->cond_reglement_id = 1;
		$id = $invoice->create($user);
		$invoice->addline('Test', 100, 1, 20);
		$duedate = dol_get_first_hour(dol_now(), 'gmt');
		$db->query("UPDATE ".$db->prefix()."facture SET fk_statut = 1, paye = 0, date_lim_reglement = '".$db->idate($duedate, 'gmt')."' WHERE rowid = ".((int) $id));

		// Mails are disabled here, so an email that is prepared fails to be sent and the failure is recorded as an event of the invoice
		$sql = "SELECT count(*) as nb FROM ".$db->prefix()."actioncomm WHERE elementtype = 'invoice' AND fk_element = ".((int) $id)." AND label = 'sendEmailsRemindersOnInvoiceDueDateKO'";

		// The template does not exist: no email is prepared (it was the generic text of an invoice that was sent), and the error is reported
		$localobject = new Facture($db);
		$result = $localobject->sendEmailsRemindersOnInvoiceDueDate(0, 'all', 'TestRemindMissing');
		$this->assertGreaterThan(0, $result);
		$this->assertStringContainsString('TestRemindMissing', $localobject->error);
		$this->assertEquals(0, $db->fetch_object($db->query($sql))->nb, 'An email was prepared with a template that does not exist');

		// The template exists: the email is prepared
		$localobject->sendEmailsRemindersOnInvoiceDueDate(0, 'all', 'TestRemindFound');
		$this->assertEquals(1, $db->fetch_object($db->query($sql))->nb, 'No email was prepared with a template that exists');

		// No template set: the generic text is still used, as before
		$localobject->sendEmailsRemindersOnInvoiceDueDate(0, 'all', '');
		$this->assertEquals(2, $db->fetch_object($db->query($sql))->nb, 'No email was prepared without a template');

		// The placeholder of the default parameters means no template too
		$localobject->sendEmailsRemindersOnInvoiceDueDate(0, 'all', 'EmailTemplateCode');
		$this->assertEquals(3, $db->fetch_object($db->query($sql))->nb, 'No email was prepared with the placeholder of the default parameters');

		$db->query("DELETE FROM ".$db->prefix()."actioncomm WHERE fk_soc = ".((int) $socid));
		$db->query("DELETE FROM ".$db->prefix()."facturedet WHERE fk_facture = ".((int) $id));
		$db->query("DELETE FROM ".$db->prefix()."facture WHERE rowid = ".((int) $id));
		$db->query("DELETE FROM ".$db->prefix()."societe WHERE rowid = ".((int) $socid));
		$db->query("DELETE FROM ".$db->prefix()."c_email_templates WHERE label = 'TestRemindFound'");
	}

	/**
	 * testFactureFetch
	 *
	 * @param   int $id     Id invoice
	 * @return  int
	 *
	 * @depends testFactureCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new Facture($db);
		$result = $localobject->fetch($id);

		$this->assertLessThan($result, 0);
		print __METHOD__." id=".$id." result=".$result."\n";
		return $localobject;
	}

	/**
	 * testFactureFetch
	 *
	 * @param   Facture $localobject Invoice
	 * @return  int
	 *
	 * @depends testFactureFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->changeProperties($localobject);
		$result = $localobject->update($user);

		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		return $localobject;
	}

	/**
	 * testFactureValid
	 *
	 * @param   Facture $localobject Invoice
	 * @return  void
	 *
	 * @depends testFactureUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureValid($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Force to default setup
		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		$result = $localobject->validate($user);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$this->assertLessThan($result, 0);

		// Test everything is still the same as specimen
		$newlocalobject = new Facture($db);
		$newlocalobject->initAsSpecimen();
		$this->changeProperties($newlocalobject);

		// Hack to avoid test to be wrong when some modules are one
		unset($localobject->array_options);
		//unset($localobject->array_options['options_reseller']);
		//unset($localobject->array_options['options_reseller']);

		$arraywithdiff = $this->objCompare(
			$localobject,
			$newlocalobject,
			true,
			// Not comparing:
			array(
				'newref','oldcopy','oldref','id','lines','client','thirdparty','brouillon', 'fk_user_author', 'user_modification_id', 'date_creation','date_validation','datem','date_modification',
				'ref','statut','status','paye','ref','actiontypecode','actionmsg2','actionmsg','mode_reglement','cond_reglement',
				'cond_reglement_doc', 'cond_reglement_label', 'modelpdf',
				'multicurrency_total_ht','multicurrency_total_tva',	'multicurrency_total_ttc','fk_multicurrency','multicurrency_code','multicurrency_tx',
				'retained_warranty' ,'retained_warranty_date_limit', 'retained_warranty_fk_cond_reglement', 'specimen', 'situation_cycle_ref', 'situation_counter', 'situation_final',
				'trackid','user_creat','user_valid', 'note'
			)
		);

		$this->assertEquals($arraywithdiff, array(), 'Found differences '.var_export($arraywithdiff, true));    // Actual, Expected. If it differs, do a var_dump($arraywithdiff) to see what differs

		return $localobject;
	}

	/**
	 * testFactureOther
	 *
	 * @param   Facture $localobject Invoice
	 * @return  int
	 *
	 * @depends testFactureValid
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureOther($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		/*$result=$localobject->setstatus(0);
		print __METHOD__." id=".$localobject->id." result=".$result."\n";
		$this->assertLessThan($result, 0);
		*/

		$localobject->info($localobject->id);
		print __METHOD__." localobject->date_creation=".$localobject->date_creation."\n";
		$this->assertNotEquals($localobject->date_creation, '');

		$result = $localobject->demande_prelevement($user);
		print __METHOD__." result=".$result."\n";
		$this->assertLessThan($result, 0);

		return $localobject->id;
	}

	/**
	 * testSendEmailsRemindersSkipSettled
	 *
	 * @return  void
	 */
	public function testSendEmailsRemindersSkipSettled()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once dirname(__FILE__).'/../../htdocs/compta/paiement/class/paiement.class.php';

		$conf->global->MAIN_MAIL_EMAIL_FROM = 'test@example.com';
		$db->query("INSERT INTO ".$db->prefix()."c_email_templates (entity, type_template, private, datec, label, position, enabled, active, topic, joinfiles, content) VALUES (1, 'facture_send', 0, '".$db->idate(dol_now())."', 'TestRemindSettled', 990, '1', 1, 'Topic', 0, 'Content')");

		// Invoices of 120 due today, for third parties with an email: with a reminder expected (1) or not (0)
		$cases = array('unpaid' => 1, 'partial' => 1, 'exact' => 0, 'overpaid' => 0, 'disputeopen' => 0, 'disputewon' => 1);
		$paid = array('partial' => 50, 'exact' => 120, 'overpaid' => 150);
		$dispute = array('disputeopen' => 1, 'disputewon' => 9);
		$duedate = dol_get_first_hour(dol_now(), 'gmt');
		$items = array();
		$paymentids = array();
		foreach ($cases as $key => $expected) {
			$soc = new Societe($db);
			$soc->name = 'TESTREMIND settled '.$key.' '.dol_now();
			$soc->email = 'customer@example.com';
			$soc->client = 1;
			$soc->code_client = -1;
			$socid = $soc->create($user);
			$invoice = new Facture($db);
			$invoice->socid = $socid;
			$invoice->date = dol_now();
			$invoice->cond_reglement_id = 1;
			$id = $invoice->create($user);
			$invoice->addline('Test', 100, 1, 20);
			$db->query("UPDATE ".$db->prefix()."facture SET fk_statut = 1, paye = 0, dispute_status = ".(empty($dispute[$key]) ? 0 : $dispute[$key]).", date_lim_reglement = '".$db->idate($duedate, 'gmt')."' WHERE rowid = ".((int) $id));
			if (isset($paid[$key])) {
				$payment = new Paiement($db);
				$payment->datepaye = dol_now();
				$payment->amounts = array($id => $paid[$key]);
				$payment->paiementid = dol_getIdFromCode($db, 'LIQ', 'c_paiement', 'code', 'id', 1);
				$paymentids[] = $payment->create($user, 0);
			}
			$items[$key] = array('socid' => $socid, 'id' => $id);
		}

		// Mails are disabled here, so an email that is prepared fails to be sent and the failure is recorded as an event of the invoice
		$localobject = new Facture($db);
		$localobject->sendEmailsRemindersOnInvoiceDueDate(0, 'all', 'TestRemindSettled');
		foreach ($cases as $key => $expected) {
			$sql = "SELECT count(*) as nb FROM ".$db->prefix()."actioncomm WHERE elementtype = 'invoice' AND fk_element = ".((int) $items[$key]['id'])." AND label = 'sendEmailsRemindersOnInvoiceDueDateKO'";
			$this->assertEquals($expected, $db->fetch_object($db->query($sql))->nb, 'Reminders of the invoice '.$key);
		}

		foreach ($items as $item) {
			$db->query("DELETE FROM ".$db->prefix()."actioncomm WHERE fk_soc = ".((int) $item['socid']));
			$db->query("DELETE FROM ".$db->prefix()."paiement_facture WHERE fk_facture = ".((int) $item['id']));
			$db->query("DELETE FROM ".$db->prefix()."facturedet WHERE fk_facture = ".((int) $item['id']));
			$db->query("DELETE FROM ".$db->prefix()."facture WHERE rowid = ".((int) $item['id']));
			$db->query("DELETE FROM ".$db->prefix()."societe WHERE rowid = ".((int) $item['socid']));
		}
		foreach ($paymentids as $paymentid) {
			$db->query("DELETE FROM ".$db->prefix()."paiement WHERE rowid = ".((int) $paymentid));
		}
		$db->query("DELETE FROM ".$db->prefix()."c_email_templates WHERE label = 'TestRemindSettled'");
	}

	/**
	 * testFactureDelete
	 *
	 * @param   int $id     Id of invoice
	 * @return  int
	 *
	 * @depends testFactureOther
	 * The depends says test is run only if previous is ok
	 */
	public function testFactureDelete($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Force default setup
		unset($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED);
		unset($conf->global->INVOICE_CAN_NEVER_BE_REMOVED);

		$localobject = new Facture($db);
		$result = $localobject->fetch($id);

		// Create another invoice and validate it after $localobject
		$localobject2 = new Facture($db);
		$result = $localobject2->initAsSpecimen();
		$result = $localobject2->create($user);
		$result = $localobject2->validate($user);
		print 'Invoice $localobject ref = '.$localobject->ref."\n";
		print 'Invoice $localobject2 created with ref = '.$localobject2->ref."\n";

		$conf->global->INVOICE_CAN_NEVER_BE_REMOVED = 1;

		$result = $localobject2->delete($user);					// Deletion is KO, option INVOICE_CAN_NEVER_BE_REMOVED is on
		print __METHOD__." id=".$localobject2->id." ref=".$localobject2->ref." result=".$result."\n";
		$this->assertEquals(0, $result, 'Deletion should fail, option INVOICE_CAN_NEVER_BE_REMOVED is on');

		unset($conf->global->INVOICE_CAN_NEVER_BE_REMOVED);

		$result = $localobject->delete($user);					// Deletion is KO, it is not last invoice
		print __METHOD__." id=".$localobject->id." ref=".$localobject->ref." result=".$result."\n";
		$this->assertEquals(0, $result, 'Deletion should fail, it is not last invoice');

		var_dump($localobject2->is_erasable());

		$result = $localobject2->delete($user);					// Deletion is OK, it is last invoice
		print __METHOD__." id=".$localobject2->id." ref=".$localobject2->ref." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Deletion should work, it is last invoice');

		$result = $localobject->delete($user);					// Deletion is KO, it is not last invoice
		print __METHOD__." id=".$localobject->id." ref=".$localobject->ref." result=".$result."\n";
		$this->assertGreaterThan(0, $result, 'Deletion should work, it is again last invoice');

		return $result;
	}

	/**
	 * Edit an object to test updates
	 *
	 * @param   Facture $localobject        Object Facture
	 * @return  void
	 */
	public function changeProperties(&$localobject)
	{
		$localobject->note_private = 'New note';
		//$localobject->note='New note after update';
	}
}
