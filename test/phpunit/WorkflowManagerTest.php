<?php
/* Copyright (C) 2026       Guenter Lukas           <gl@gl.co.at>
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
 *      \file       test/phpunit/WorkflowManagerTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the workflow triggers (module Workflow)
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/expedition/class/expedition.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/facture/class/facture.class.php';

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
class WorkflowManagerTest extends CommonClassTest
{
	/** @var array<string,mixed> Constants changed by a test, with their value before the test */
	private $savedconsts = array();

	/**
	 * Restore the constants changed by the test.
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;
		foreach ($this->savedconsts as $key => $value) {
			if ($value === null) {
				unset($conf->global->$key);
			} else {
				$conf->global->$key = $value;
			}
		}
		$this->savedconsts = array();
		parent::tearDown();
	}

	/**
	 * Enable the modules shipping, invoice and workflow and set the workflow option to classify shipments billed.
	 *
	 * @param	int		$classifybilled		Value of WORKFLOW_SHIPPING_CLASSIFY_BILLED_INVOICE
	 * @return	void
	 */
	private function configureWorkflow($classifybilled)
	{
		global $conf;

		foreach (array('SHIPMENT_STANDALONE' => 1, 'WORKFLOW_SHIPPING_CLASSIFY_BILLED_INVOICE' => $classifybilled) as $key => $value) {
			if (!array_key_exists($key, $this->savedconsts)) {
				$this->savedconsts[$key] = isset($conf->global->$key) ? $conf->global->$key : null;
			}
			$conf->global->$key = $value;
		}
		foreach (array('expedition' => 'shipping', 'facture' => 'invoice', 'workflow' => 'workflow') as $module => $property) {
			$conf->modules[$module] = $module;
			if (!isset($conf->$property) || !is_object($conf->$property)) {
				$conf->$property = new stdClass();
			}
			$conf->$property->enabled = 1;
		}
	}

	/**
	 * Create a standalone shipment (without source order) with one free text line.
	 *
	 * @param	bool	$validate	Validate the shipment
	 * @return	Expedition			Shipment
	 */
	private function createStandaloneShipment($validate)
	{
		global $db, $user;

		$soc = new Societe($db);
		$soc->name = 'Workflow standalone shipment phpunit';
		$socId = $soc->create($user);
		$this->assertGreaterThan(0, $socId, $soc->errorsToString());

		$shipment = new Expedition($db);
		$shipment->socid = $socId;
		$result = $shipment->create($user);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$result = $shipment->addlinefree(2, 'shipping', 0, 0, 1, 'Free text line', 0);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		if ($validate) {
			$result = $shipment->valid($user);
			$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		}

		return $shipment;
	}

	/**
	 * Create and validate an invoice of 10 (excl. tax) linked to a shipment, like an invoice created from the shipment card.
	 *
	 * @param	Expedition	$shipment	Shipment
	 * @return	void
	 */
	private function validateInvoiceOfShipment($shipment)
	{
		global $db, $user;

		$invoice = new Facture($db);
		$invoice->socid = $shipment->socid;
		$invoice->type = Facture::TYPE_STANDARD;
		$invoice->date = dol_now();
		$result = $invoice->create($user);
		$this->assertGreaterThan(0, $result, $invoice->errorsToString());
		$result = $invoice->addline('Free text line', 5, 2, 0);
		$this->assertGreaterThan(0, $result, $invoice->errorsToString());
		$result = $invoice->add_object_linked('shipping', $shipment->id);
		$this->assertGreaterThan(0, $result, $invoice->errorsToString());
		$result = $invoice->validate($user);
		$this->assertGreaterThan(0, $result, $invoice->errorsToString());
	}

	/**
	 * Get the billed flag of a shipment from the database.
	 *
	 * @param	int		$id		Id of shipment
	 * @return	int				Billed flag
	 */
	private function getBilled($id)
	{
		global $db;

		$shipment = new Expedition($db);
		$this->assertGreaterThan(0, $shipment->fetch($id), $shipment->errorsToString());

		return (int) $shipment->billed;
	}

	/**
	 * A standalone shipment has no amount: it is classified billed by the validation of an invoice linked to it.
	 *
	 * @return void
	 */
	public function testStandaloneShipmentClassifiedBilledByInvoice()
	{
		$this->configureWorkflow(1);
		$shipment = $this->createStandaloneShipment(true);
		$this->validateInvoiceOfShipment($shipment);
		$this->assertSame(1, $this->getBilled($shipment->id));
	}

	/**
	 * Without the workflow option, the standalone shipment is not classified billed.
	 *
	 * @return void
	 */
	public function testStandaloneShipmentNotClassifiedBilledWithoutOption()
	{
		$this->configureWorkflow(0);
		$shipment = $this->createStandaloneShipment(true);
		$this->validateInvoiceOfShipment($shipment);
		$this->assertSame(0, $this->getBilled($shipment->id));
	}

	/**
	 * A draft standalone shipment is not classified billed.
	 *
	 * @return void
	 */
	public function testDraftStandaloneShipmentNotClassifiedBilled()
	{
		$this->configureWorkflow(1);
		$shipment = $this->createStandaloneShipment(false);
		$this->validateInvoiceOfShipment($shipment);
		$this->assertSame(0, $this->getBilled($shipment->id));
	}

	/**
	 * A shipment with a source object keeps the comparison of amounts: its amount (0, no order line) differs from the invoice.
	 *
	 * @return void
	 */
	public function testShipmentWithSourceKeepsAmountComparison()
	{
		$this->configureWorkflow(1);
		$shipment = $this->createStandaloneShipment(true);
		$result = $shipment->add_object_linked('commande', 1);
		$this->assertGreaterThan(0, $result, $shipment->errorsToString());
		$this->validateInvoiceOfShipment($shipment);
		$this->assertSame(0, $this->getBilled($shipment->id));
	}
}
