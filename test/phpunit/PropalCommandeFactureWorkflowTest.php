<?php
/* Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/PropalCommandeFactureWorkflowTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test of the Propal -> Commande -> Facture business workflow
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Tests that a customer proposal, once converted into an order and then into an invoice,
 * carries its thirdparty, lines, totals and notes correctly through the chain, and that
 * each object is linked back to the one it was created from.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PropalCommandeFactureWorkflowTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		self::assertTrue(isModEnabled('propal'), 'module commercial proposal must be enabled');
		self::assertTrue(isModEnabled('order'), 'module customer order must be enabled');
		self::assertTrue(isModEnabled('invoice'), 'module customer invoice must be enabled');
		parent::setUpBeforeClass();
	}

	/**
	 * testWorkflowCreateAndValidatePropal
	 *
	 * Entry point of the chain: create a thirdparty and a proposal for it, then validate the proposal.
	 *
	 * @return Propal
	 */
	public function testWorkflowCreateAndValidatePropal()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = 'PropalCommandeFactureWorkflowTest Unittest';
		$socid = $soc->create($user);
		$this->assertGreaterThan(0, $socid, $soc->errorsToString());

		// Use initAsSpecimen() only for realistic header defaults (payment terms, availability, ...).
		// Its lines reference random real products, which can be kits/BOMs that silently expand into extra
		// lines on each create()/createFrom...() down the chain: replace them with controlled, product-less
		// lines so the totals asserted at each step of the chain are deterministic.
		$propal = new Propal($db);
		$propal->initAsSpecimen(array('tosell' => 1));
		$propal->lines = array();
		$propal->total_ht = 0;
		$propal->total_tva = 0;
		$propal->total_ttc = 0;
		$propal->socid = $socid;
		$propal->note_public = 'PHPUnit workflow test note (public)';
		$propal->note_private = 'PHPUnit workflow test note (private)';
		$result = $propal->create($user);
		$this->assertGreaterThan(0, $result, $propal->errorsToString());

		$lineid1 = $propal->addline('Workflow line A', 100, 2, 20);	// 200 HT / 40 VAT / 240 TTC
		$this->assertGreaterThan(0, $lineid1, $propal->errorsToString());
		$lineid2 = $propal->addline('Workflow line B', 50, 3, 10);	// 150 HT / 15 VAT / 165 TTC
		$this->assertGreaterThan(0, $lineid2, $propal->errorsToString());

		$propal->fetch($propal->id);
		$this->assertEqualsWithDelta(350.0, (float) $propal->total_ht, 0.01, 'total_ht of the 2 controlled lines');

		$result = $propal->valid($user);
		$this->assertGreaterThan(0, $result, $propal->errorsToString());

		$propal->fetch($propal->id);
		$this->assertEquals(Propal::STATUS_VALIDATED, $propal->status);
		$this->assertLineTotalsMatchHeader($propal, 'on validated proposal');

		return $propal;
	}

	/**
	 * testWorkflowCreateOrderFromPropal
	 *
	 * @param	Propal		$propal		Proposal validated by the previous test
	 * @return	Commande
	 *
	 * @depends	testWorkflowCreateAndValidatePropal
	 * The depends says test is run only if previous is ok
	 */
	public function testWorkflowCreateOrderFromPropal($propal)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$commande = new Commande($db);
		$result = $commande->createFromProposal($propal, $user);

		print __METHOD__." propalid=".$propal->id." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $commande->errorsToString());

		$commande->fetch($result);

		$this->assertEquals($propal->socid, $commande->socid, 'Order thirdparty must match proposal thirdparty');
		$this->assertEquals($propal->note_public, $commande->note_public, 'Order public note must be propagated from proposal');
		$this->assertEquals($propal->note_private, $commande->note_private, 'Order private note must be propagated from proposal');
		$this->assertCount(count($propal->lines), $commande->lines, 'Order must have the same number of lines as the proposal');
		$this->assertEqualsWithDelta((float) $propal->total_ht, (float) $commande->total_ht, 0.01, 'Order total_ht must match proposal total_ht');
		$this->assertEqualsWithDelta((float) $propal->total_tva, (float) $commande->total_tva, 0.01, 'Order total_tva must match proposal total_tva');
		$this->assertEqualsWithDelta((float) $propal->total_ttc, (float) $commande->total_ttc, 0.01, 'Order total_ttc must match proposal total_ttc');
		$this->assertLineTotalsMatchHeader($commande, 'on order created from proposal');

		// The order must be linked back to its originating proposal (llx_element_element)
		$commande->fetchObjectLinked();
		$this->assertArrayHasKey('propal', $commande->linkedObjectsIds, 'Order must be linked to its originating proposal');
		$this->assertContains($propal->id, $commande->linkedObjectsIds['propal']);

		$result = $commande->valid($user);
		print __METHOD__." id=".$commande->id." valid result=".$result."\n";
		$this->assertGreaterThan(0, $result, $commande->errorsToString());

		$commande->fetch($commande->id);
		$this->assertEquals(Commande::STATUS_VALIDATED, $commande->status);

		return $commande;
	}

	/**
	 * testWorkflowCreateInvoiceFromOrder
	 *
	 * @param	Commande	$commande	Order validated by the previous test
	 * @return	Facture
	 *
	 * @depends	testWorkflowCreateOrderFromPropal
	 * The depends says test is run only if previous is ok
	 */
	public function testWorkflowCreateInvoiceFromOrder($commande)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$facture = new Facture($db);
		$result = $facture->createFromOrder($commande, $user);

		print __METHOD__." commandeid=".$commande->id." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $facture->errorsToString());

		$facture->fetch($result);

		$this->assertEquals($commande->socid, $facture->socid, 'Invoice thirdparty must match order thirdparty');
		$this->assertEquals($commande->note_public, $facture->note_public, 'Invoice public note must be propagated from order');
		$this->assertEquals($commande->note_private, $facture->note_private, 'Invoice private note must be propagated from order');
		$this->assertCount(count($commande->lines), $facture->lines, 'Invoice must have the same number of lines as the order');
		$this->assertEqualsWithDelta((float) $commande->total_ht, (float) $facture->total_ht, 0.01, 'Invoice total_ht must match order total_ht');
		$this->assertEqualsWithDelta((float) $commande->total_tva, (float) $facture->total_tva, 0.01, 'Invoice total_tva must match order total_tva');
		$this->assertEqualsWithDelta((float) $commande->total_ttc, (float) $facture->total_ttc, 0.01, 'Invoice total_ttc must match order total_ttc');
		$this->assertLineTotalsMatchHeader($facture, 'on invoice created from order');

		// The invoice must be linked back to its originating order (llx_element_element)
		$facture->fetchObjectLinked();
		$this->assertArrayHasKey('commande', $facture->linkedObjectsIds, 'Invoice must be linked to its originating order');
		$this->assertContains($commande->id, $facture->linkedObjectsIds['commande']);

		// Force to default setup, same as FactureTest::testFactureValid
		$conf->global->FAC_FORCE_DATE_VALIDATION = 0;
		$conf->global->INVOICE_CHECK_POSTERIOR_DATE = 0;

		$result = $facture->validate($user);
		print __METHOD__." id=".$facture->id." validate result=".$result."\n";
		$this->assertGreaterThan(0, $result, $facture->errorsToString());

		$facture->fetch($facture->id);
		$this->assertEquals(Facture::STATUS_VALIDATED, $facture->status);

		return $facture;
	}
}
