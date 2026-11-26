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

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/html.form.class.php';
require_once dirname(__FILE__).'/../../htdocs/comm/propal/class/propal.class.php';
require_once dirname(__FILE__).'/../../htdocs/commande/class/commande.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';

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

		// createFromOrder() returns a status flag (1/-1), not the new invoice id (unlike
		// Commande::createFromProposal()) - the created invoice's id must be read from $facture->id.
		print __METHOD__." commandeid=".$commande->id." result=".$result."\n";
		$this->assertGreaterThan(0, $result, $facture->errorsToString());

		$facture->fetch($facture->id);

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

	/**
	 * testUpdateLinkedBuyPrice
	 *
	 * The cost price of a line of an order can be applied to the same product of its linked proposal and invoice.
	 *
	 * @return void
	 */
	public function testUpdateLinkedBuyPrice()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		if (!isModEnabled('margin')) {
			$this->markTestSkipped('Module margin disabled: cost prices can not be applied to linked documents.');
		}

		$optionbefore = getDolGlobalString('MARGIN_CAN_UPDATE_BUYINGPRICE_ON_LINKED_DOCUMENTS');
		$conf->global->MARGIN_CAN_UPDATE_BUYINGPRICE_ON_LINKED_DOCUMENTS = 1;

		// The module Margin may not be enabled on the test database, so we enable it and give the right to change cost prices
		$savmodmargin = $conf->modules['margin'] ?? null;
		$conf->modules['margin'] = 'margin';
		$savrightsmargins = isset($user->rights->margins) ? clone $user->rights->margins : null;
		if (!isset($user->rights->margins)) {
			$user->rights->margins = new stdClass();
		}
		$user->rights->margins->creer = 1;

		$soc = new Societe($db);
		$soc->name = 'PropalCommandeFactureWorkflowTest linked cost price Unittest';
		$socid = $soc->create($user);
		$this->assertGreaterThan(0, $socid, $soc->errorsToString());

		$product = new Product($db);
		$product->initAsSpecimen();
		$product->ref = 'PHPUNIT_LINKED_BUYPRICE_'.dol_now();
		$productid = $product->create($user);
		$this->assertGreaterThan(0, $productid, $product->errorsToString());

		// A proposal, an order and an invoice (kept as draft) with a line of the product
		$propal = new Propal($db);
		$propal->initAsSpecimen(array('tosell' => 1));
		$commande = new Commande($db);
		$commande->initAsSpecimen();
		$facture = new Facture($db);
		$facture->initAsSpecimen();
		$lineids = array();
		foreach (array($propal, $commande, $facture) as $document) {
			$document->lines = array();
			$document->socid = $socid;
			$this->assertGreaterThan(0, $document->create($user), $document->errorsToString());
			$lineids[$document->element] = $document->addline('Line', 100, 1, 0, 0, 0, $productid);
			$this->assertGreaterThan(0, $lineids[$document->element], $document->errorsToString());
		}
		foreach (array('propal' => 10, 'commande' => 12, 'facture' => 10) as $element => $buyprice) {
			$db->query("UPDATE ".MAIN_DB_PREFIX.$element."det SET buy_price_ht = ".((float) $buyprice)." WHERE rowid = ".((int) $lineids[$element]));
		}
		$commande->add_object_linked('propal', $propal->id);
		$facture->add_object_linked('commande', $commande->id);

		// The cost price of the order line is proposed on the proposal and on the invoice
		$documents = $commande->getLinkedBuyPriceDocuments($user, $lineids['commande']);
		$this->assertEquals(array('propal', 'facture'), array_keys($documents), 'Proposal and invoice have another cost price');
		$this->assertEquals(12, $documents['propal']['buyprice'], 'Cost price to apply');
		$this->assertEquals(array(), $commande->getLinkedBuyPriceDocuments($user, $lineids['commande'], 12), 'Nothing when the cost price did not change');
		$this->assertEquals(array(), $commande->getLinkedBuyPriceDocuments(new User($db), $lineids['commande']), 'Nothing for a user without rights');

		// The line must belong to the object, so a line of another order is ignored
		$commande2 = new Commande($db);
		$commande2->initAsSpecimen();
		$commande2->ref = '';	// Another order is already using the ref of the specimen
		$commande2->lines = array();
		$commande2->socid = $socid;
		$this->assertGreaterThan(0, $commande2->create($user), $commande2->errorsToString());
		$lineid2 = $commande2->addline('Line', 100, 1, 0, 0, 0, $productid);
		$db->query("UPDATE ".MAIN_DB_PREFIX."commandedet SET buy_price_ht = 99 WHERE rowid = ".((int) $lineid2));
		$this->assertEquals(array(), $commande->getLinkedBuyPriceDocuments($user, $lineid2), 'The line must belong to the object');

		// A user allowed to change cost prices and to see all customers, but to edit proposals only
		$limiteduser = new User($db);
		$limiteduser->rights = new stdClass();
		$limiteduser->rights->margins = (object) array('creer' => 1);
		$limiteduser->rights->propal = (object) array('lire' => 1, 'creer' => 1);
		$limiteduser->rights->facture = (object) array('lire' => 1);
		$limiteduser->rights->societe = (object) array('client' => (object) array('voir' => 1));
		$this->assertEquals(array('propal'), array_keys($commande->getLinkedBuyPriceDocuments($limiteduser, $lineids['commande'])), 'Documents the user can not edit are ignored');

		// Without the right to see all customers, the documents of a customer that is not his own are ignored
		unset($limiteduser->rights->societe);
		$this->assertEquals(array(), $commande->getLinkedBuyPriceDocuments($limiteduser, $lineids['commande']), 'Documents of the customers of others are ignored');

		// Only the proposal is updated, the invoice has still another cost price
		$this->assertEquals(1, $commande->updateLinkedBuyPrice($user, $lineids['commande'], array('propal' => 'on', 'facture' => '')), 'One line updated');
		$this->assertEquals(array('facture'), array_keys($commande->getLinkedBuyPriceDocuments($user, $lineids['commande'])), 'Only the invoice is still proposed');

		$conf->global->MARGIN_CAN_UPDATE_BUYINGPRICE_ON_LINKED_DOCUMENTS = 0;
		$this->assertEquals(array(), $commande->getLinkedBuyPriceDocuments($user, $lineids['commande']), 'Nothing when the option is off');
		$conf->global->MARGIN_CAN_UPDATE_BUYINGPRICE_ON_LINKED_DOCUMENTS = $optionbefore;
		if ($savmodmargin === null) {
			unset($conf->modules['margin']);
		} else {
			$conf->modules['margin'] = $savmodmargin;
		}
		if ($savrightsmargins === null) {
			unset($user->rights->margins);
		} else {
			$user->rights->margins = $savrightsmargins;
		}

		$facture->delete($user);
		$commande2->delete($user);
		$commande->delete($user);
		$propal->delete($user);
		$product->delete($user);
		$soc->delete($socid, $user);
	}

	/**
	 * testIndirectObjectLinked
	 *
	 * The proposals, orders and invoices of the same thirdparty linked to the documents linked to a document are listed
	 * in the box of linked objects, only if the option is on.
	 *
	 * @return void
	 */
	public function testIndirectObjectLinked()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$soc = new Societe($db);
		$soc->name = 'PropalCommandeFactureWorkflowTest indirect links Unittest';
		$socid = $soc->create($user);
		$this->assertGreaterThan(0, $socid, $soc->errorsToString());
		$soc2 = new Societe($db);
		$soc2->name = 'PropalCommandeFactureWorkflowTest indirect links other Unittest';
		$socid2 = $soc2->create($user);
		$this->assertGreaterThan(0, $socid2, $soc2->errorsToString());

		// An order and an invoice created from the proposal, and an invoice created from the order
		$propal = $this->createDraftFrom(new Propal($db), $socid, array());
		$order = $this->createDraftFrom(new Commande($db), $socid, array('propal' => $propal->id));
		$invoiceA = $this->createDraftFrom(new Facture($db), $socid, array('propal' => $propal->id));
		$invoiceB = $this->createDraftFrom(new Facture($db), $socid, array('commande' => $order->id));

		$this->assertEquals(array('facture'.$invoiceA->id), $this->getIndirectIds($order), 'The order finds the invoice created from its proposal');
		$this->assertEquals(array('commande'.$order->id), $this->getIndirectIds($invoiceA), 'The invoice of the proposal finds the order, not the other invoice');
		$this->assertEquals(array('propal'.$propal->id), $this->getIndirectIds($invoiceB), 'The invoice of the order finds the proposal, not the other invoice');
		$this->assertEquals(array('facture'.$invoiceB->id), $this->getIndirectIds($propal), 'The proposal finds the invoice of its order');

		// Each document is listed through the document of this object it is linked to, whatever the direction of the link
		$this->assertEquals(array('facture'.$invoiceA->id => array('propal'.$propal->id)), $this->getIndirectVia($order), 'The invoice is listed through the proposal');
		$this->assertEquals(array('commande'.$order->id => array('propal'.$propal->id)), $this->getIndirectVia($invoiceA), 'The order is listed through the proposal');
		$this->assertEquals(array('propal'.$propal->id => array('commande'.$order->id)), $this->getIndirectVia($invoiceB), 'The proposal is listed through the order');
		$this->assertEquals(array('facture'.$invoiceB->id => array('commande'.$order->id)), $this->getIndirectVia($propal), 'The invoice is listed through the order');

		// A document of another thirdparty linked to the proposal is ignored
		$invoiceC = $this->createDraftFrom(new Facture($db), $socid2, array('propal' => $propal->id));
		$this->assertEquals(array('facture'.$invoiceA->id), $this->getIndirectIds($order), 'A document of another thirdparty is ignored');

		// The documents that the user can not read are ignored
		$limiteduser = new User($db);
		$limiteduser->rights = new stdClass();
		$limiteduser->rights->propal = (object) array('lire' => 1);
		$limiteduser->rights->societe = (object) array('client' => (object) array('voir' => 1));
		$this->assertEquals(array(), $this->getIndirectIds($order, $limiteduser), 'The invoice is not listed for a user who can not read invoices');
		$this->assertEquals(array('propal'.$propal->id), $this->getIndirectIds($invoiceB, $limiteduser), 'The proposal is listed for a user who can read proposals');
		$this->assertEquals(array(), $this->getIndirectIds($order, new User($db)), 'Nothing for a user without rights');

		// A document without thirdparty lists nothing
		$order->socid = 0;
		$this->assertEquals(array(), $this->getIndirectIds($order), 'A document without thirdparty lists nothing');
		$order->socid = $socid;

		// The thirdparty of the document can be given by fk_soc only
		$order->socid = null;
		$order->fk_soc = $socid;
		$this->assertEquals(array('facture'.$invoiceA->id), $this->getIndirectIds($order), 'The thirdparty of the document can be in fk_soc');
		$order->fk_soc = null;
		$order->socid = $socid;

		// Two documents linked to the order and to each other are not listed again
		$db->query("INSERT INTO ".MAIN_DB_PREFIX."element_element (fk_source, sourcetype, fk_target, targettype) VALUES (".((int) $propal->id).", 'propal', ".((int) $invoiceB->id).", 'facture')");
		$this->assertEquals(array('facture'.$invoiceA->id), $this->getIndirectIds($order), 'The documents already linked to the order are not listed again');
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."element_element WHERE fk_source = ".((int) $propal->id)." AND sourcetype = 'propal' AND fk_target = ".((int) $invoiceB->id)." AND targettype = 'facture'");

		// A link to a document that does not exist is ignored
		$db->query("INSERT INTO ".MAIN_DB_PREFIX."element_element (fk_source, sourcetype, fk_target, targettype) VALUES (".((int) $propal->id).", 'propal', 999999999, 'facture')");
		$this->assertEquals(array('facture'.$invoiceA->id), $this->getIndirectIds($order), 'A link to a document that does not exist is ignored');
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."element_element WHERE fk_target = 999999999 AND targettype = 'facture'");

		// The box of linked objects lists them with the option only, without link to remove them
		$optionbefore = getDolGlobalString('MAIN_SHOW_INDIRECT_LINKED_OBJECTS');
		$langs->loadLangs(array('main', 'bills'));
		$this->assertNotEquals('OtherRelatedObjects', $langs->trans('OtherRelatedObjects'), 'The title of the list must be translated');
		$this->assertNotEquals('RelatedVia', $langs->trans('RelatedVia'), 'The text of the source must be translated');
		$db->query("UPDATE ".MAIN_DB_PREFIX."facture SET fk_statut = 1 WHERE rowid = ".((int) $invoiceA->id));
		foreach (array(0, 1) as $optionvalue) {
			$conf->global->MAIN_SHOW_INDIRECT_LINKED_OBJECTS = $optionvalue;
			$html = $this->getLinkedObjectBlock($order);
			$this->assertEquals(2, substr_count($html, 'dellinkid'), 'Only the 2 documents linked to the order can be unlinked');
			if ($optionvalue) {
				$this->assertStringContainsString($langs->trans('OtherRelatedObjects'), $html);
				$this->assertStringContainsString($invoiceA->ref, $html, 'The invoice of the proposal is listed with the option');
				$propalcopy = new Propal($db);
				$propalcopy->fetch($propal->id);
				$this->assertStringContainsString($langs->trans('RelatedVia', $propalcopy->getNomUrl(1)), $html, 'The invoice is shown with the proposal it is listed through');
			} else {
				$this->assertStringNotContainsString($langs->trans('OtherRelatedObjects'), $html);
				$this->assertStringNotContainsString($invoiceA->ref, $html, 'Nothing more without the option');
				$this->assertStringNotContainsString($langs->trans('RelatedVia', ''), $html);
			}
		}

		// The status of the invoice counts its payments: not paid, then started
		$this->assertStringContainsString($langs->trans('BillStatusNotPaid'), $html);
		$this->assertStringNotContainsString($langs->trans('BillStatusStarted'), $html);
		$conf->global->MAIN_SHOW_INDIRECT_LINKED_OBJECTS = 1;
		$db->query("INSERT INTO ".MAIN_DB_PREFIX."paiement_facture (fk_facture, amount) VALUES (".((int) $invoiceA->id).", 10)");
		$html = $this->getLinkedObjectBlock($order);
		$this->assertStringContainsString($langs->trans('BillStatusStarted'), $html);
		$this->assertStringNotContainsString($langs->trans('BillStatusNotPaid'), $html);

		// A document linked to several documents of this object is listed through all of them, even of the same type
		$propal2 = $this->createDraftFrom(new Propal($db), $socid, array());
		$order->add_object_linked('propal', $propal2->id);
		$invoiceA->add_object_linked('propal', $propal2->id);
		$invoiceA->add_object_linked('facture', $invoiceB->id);
		$this->assertEquals(array('facture'.$invoiceA->id => array('facture'.$invoiceB->id, 'propal'.$propal->id, 'propal'.$propal2->id)), $this->getIndirectVia($order), 'The invoice is listed through the 3 documents it is linked to');
		$this->assertEquals(3, substr_count($this->getLinkedObjectBlock($order), $langs->trans('RelatedVia', '')), 'The invoice is shown with the 3 documents');
		$invoiceD = $this->createDraftFrom(new Facture($db), $socid, array('propal' => $propal->id));
		$invoiceD->add_object_linked('facture', $invoiceB->id);
		$this->assertEquals(array('facture'.$invoiceB->id => array('commande'.$order->id, 'facture'.$invoiceA->id, 'facture'.$invoiceD->id)), $this->getIndirectVia($propal), 'The invoice of the order is listed through the order and the 2 invoices it is the source of');

		$conf->global->MAIN_SHOW_INDIRECT_LINKED_OBJECTS = $optionbefore;
		$db->query("DELETE FROM ".MAIN_DB_PREFIX."paiement_facture WHERE fk_facture = ".((int) $invoiceA->id));
		$db->query("UPDATE ".MAIN_DB_PREFIX."facture SET fk_statut = 0 WHERE rowid = ".((int) $invoiceA->id));

		foreach (array($invoiceD, $invoiceC, $invoiceB, $invoiceA, $order, $propal2, $propal) as $document) {
			$document->delete($user);
		}
		$soc2->delete($socid2, $user);
		$soc->delete($socid, $user);
	}

	/**
	 * Create a draft document of a thirdparty, with the links given at its creation
	 *
	 * @param	Propal|Commande|Facture	$document		Document to create
	 * @param	int						$socid			Id of the thirdparty
	 * @param	array<string,int>		$linkedobjects	Documents it is created from ('propal' => id, ...)
	 * @return	Propal|Commande|Facture					Document created
	 */
	private function createDraftFrom($document, $socid, $linkedobjects)
	{
		global $user;

		$document->initAsSpecimen();
		$document->ref = '';
		$document->lines = array();
		$document->socid = $socid;
		$document->linked_objects = $linkedobjects;
		$this->assertGreaterThan(0, $document->create($user), $document->errorsToString());

		return $document;
	}

	/**
	 * Return the html of the box of linked objects of a document
	 *
	 * @param	Propal|Commande|Facture	$document	Document
	 * @return	string									Html
	 */
	private function getLinkedObjectBlock($document)
	{
		global $db;

		$document->clearObjectLinkedCache();
		$form = new Form($db);
		ob_start();
		$form->showLinkedObjectBlock($document);

		return ob_get_clean();
	}

	/**
	 * Return the sorted element and id of the documents listed by fetchIndirectObjectLinked()
	 *
	 * @param	Propal|Commande|Facture	$document	Document
	 * @param	?User					$reader		User reading the documents (default is the current user)
	 * @return	string[]							For example array('facture12', 'propal3')
	 */
	private function getIndirectIds($document, $reader = null)
	{
		global $user;

		$document->clearObjectLinkedCache();
		$document->fetchObjectLinked();
		$ids = array();
		foreach ($document->fetchIndirectObjectLinked($reader ? $reader : $user) as $linked) {
			$ids[] = $linked->element.$linked->id;
		}
		sort($ids);

		return $ids;
	}

	/**
	 * Return, for each document listed by fetchIndirectObjectLinked(), the sorted documents linked to this object that it is listed through
	 *
	 * @param	Propal|Commande|Facture	$document	Document
	 * @return	array<string,string[]>				For example array('facture12' => array('propal3'))
	 */
	private function getIndirectVia($document)
	{
		global $user;

		$document->clearObjectLinkedCache();
		$document->fetchObjectLinked();
		$via = array();
		foreach ($document->fetchIndirectObjectLinked($user) as $linked) {
			$via[$linked->element.$linked->id] = array();
			foreach ($linked->linkedObjects as $objects) {
				foreach ($objects as $object) {
					$via[$linked->element.$linked->id][] = $object->element.$object->id;
				}
			}
			sort($via[$linked->element.$linked->id], SORT_NATURAL);
		}

		return $via;
	}
}
