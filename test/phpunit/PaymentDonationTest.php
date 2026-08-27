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
 *      \file       test/phpunit/PaymentDonationTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/don/class/don.class.php';
require_once dirname(__FILE__).'/../../htdocs/don/class/paymentdonation.class.php';
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
 * Unlike Tva::addPayment()/getSommePaiement() (see TvaTest.php), PaymentDonation::create() and
 * Don::getRemainToPay() do agree on the same table/link (payment_donation.fk_donation), so a
 * payment against a donation is really reflected back on the Don once fetched.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class PaymentDonationTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $conf;

		if (!isModEnabled('don')) {
			// Activating a module re-runs its SQL install scripts (CREATE/ALTER TABLE), which causes an
			// implicit commit in MySQL/InnoDB: this activation is real and is NOT undone by the
			// rollback in tearDownAfterClass, exactly like an admin enabling it from Setup > Modules
			// would be (see also FactureTest::setUpBeforeClass(), which similarly disables the
			// blockedlog module for real, outside of any transaction). Do this before starting the
			// test transaction below, so the transaction-open counter stays consistent.
			require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
			$result = activateModule('modDon');
			self::assertEmpty($result['errors'], 'Failed to activate module don: '.implode(', ', $result['errors']));
			$conf->setValues($db);
		}

		self::assertTrue(isModEnabled('don'), 'module don must be enabled');

		parent::setUpBeforeClass();
	}

	/**
	 * testPaymentDonationCreate
	 *
	 * @return int Id of the payment
	 */
	public function testPaymentDonationCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$don = new Don($db);
		$don->initAsSpecimen();
		$don->amount = 100;
		$donid = $don->create($user);
		$this->assertGreaterThan(0, $donid, $don->errorsToString());
		$this->assertGreaterThan(0, $don->setValid($user), $don->errorsToString());

		$typepaymentid = dol_getIdFromCode($db, 'VIR', 'c_paiement', 'code', 'id', 1);
		$this->assertGreaterThan(0, $typepaymentid, 'VIR must be a known active payment type in this environment');

		$localobject = new PaymentDonation($db);
		$localobject->fk_donation = $donid;
		$localobject->datep = dol_now();
		$localobject->paymenttype = $typepaymentid;
		$localobject->amounts = array($donid => 100);
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_donation=".$donid."\n";

		$don->fetch($donid);
		$this->assertEqualsWithDelta(0.0, (float) $don->getRemainToPay(), 0.00001, 'Donation paid in full must have nothing left to pay');

		return $result;
	}

	/**
	 * testPaymentDonationFetch
	 *
	 * @param	int		$id		Id of object
	 * @return	PaymentDonation
	 *
	 * @depends	testPaymentDonationCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentDonationFetch($id)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new PaymentDonation($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertEqualsWithDelta(100.0, (float) $localobject->amount, 0.00001);
		$this->assertSame('VIR', $localobject->type_code);

		return $localobject;
	}

	/**
	 * testPaymentDonationUpdate
	 *
	 * @param	PaymentDonation	$localobject	Donation payment
	 * @return	PaymentDonation
	 *
	 * @depends	testPaymentDonationFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentDonationUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->amount = 120;
		$localobject->note_public = 'New note public after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertEqualsWithDelta(120.0, (float) $localobject->amount, 0.00001);
		$this->assertSame('New note public after update', $localobject->note_public);

		return $localobject;
	}

	/**
	 * testPaymentDonationDelete
	 *
	 * @param	PaymentDonation	$localobject	Donation payment
	 * @return	int
	 *
	 * @depends	testPaymentDonationUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testPaymentDonationDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$id = $localobject->id;
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
