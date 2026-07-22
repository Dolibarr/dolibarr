<?php
/* Copyright (C) 2025       Thomas Negre            <tnegre@open-dsi.fr>
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
 *      \file       test/phpunit/BonVirementTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/admin.lib.php';
require_once dirname(__FILE__).'/../../htdocs/compta/prelevement/class/bonprelevement.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/companybankaccount.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;

$langs->load("main");


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class BonVirementTest extends CommonClassTest
{
	// ---------------------------------------------------------------------------
	// Test IBANs valid (mod97 check compliant).
	// Distinct from BonPrelevementTest IBANs to avoid collisions when both
	// suites run against the same database instance.
	// ---------------------------------------------------------------------------
	const IBAN_A_DEFAULT  = 'FR7630001007941234567890670'; // default bank account of COMPANY_A
	const IBAN_A_SPECIFIC = 'FR7630001007941234567890767'; // specific bank account of COMPANY_A
	const IBAN_B_DEFAULT  = 'FR7630001007941234567890864'; // default bank account of COMPANY_B
	const IBAN_B_SPECIFIC = 'FR7630001007941234567890961'; // specific bank account of COMPANY_B
	const BIC             = 'BNPAFRPPXXX';
	const XSD_PAIN_001    = __DIR__.'/../assets/xsd/pain.001.001.03.xsd';

	// ---------------------------------------------------------------------------
	// Shared fixtures created once in setUpBeforeClass(),
	// inside the parent transaction (rolled back by tearDownAfterClass()).
	// ---------------------------------------------------------------------------
	/** @var int Row ID of COMPANY_A */
	protected static $socidA = 0;
	/** @var int Row ID of COMPANY_B */
	protected static $socidB = 0;
	/** @var int Row ID of the default bank account of COMPANY_A */
	protected static $ribADefaultId = 0;
	/** @var int Row ID of the specific bank account of COMPANY_A */
	protected static $ribASpecificId = 0;
	/** @var int Row ID of the default bank account of COMPANY_B */
	protected static $ribBDefaultId = 0;
	/** @var int Row ID of the specific bank account of COMPANY_B */
	protected static $ribBSpecificId = 0;
	/** @var int Row ID of the issuer bank account (llx_bank_account) */
	protected static $fkBankAccount = 0;

	/**
	 * setUpBeforeClass
	 *
	 * Creates shared fixtures for all tests in this class:
	 *   - COMPANY_A and COMPANY_B (French supplier third parties)
	 *   - Two bank accounts per company (one default, one specific)
	 *   - One issuer bank account used for BonPrelevement generation
	 *
	 * Everything is created inside the parent transaction;
	 * the final rollback in tearDownAfterClass() removes all this data.
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $db, $user;
		parent::setUpBeforeClass(); // Opens the parent transaction ($db->begin())

		// Enable required modules if not already active
		if (!isModEnabled('paymentbybanktransfer')) {
			activateModule('modPaymentByBankTransfer', 1, 1);
		}
		if (!isModEnabled('fournisseur')) {
			activateModule('modFournisseur', 1, 1);
		}

		// ------------------------------------------------------------------
		// COMPANY_A: French supplier third party
		// ------------------------------------------------------------------
		$socA = new Societe($db);
		$socA->name = 'BonVirementTest CompanyA';
		$socA->fournisseur = 1;
		$socA->country_id = 1; // France (rowid=1 in c_country)
		$socA->code_fournisseur = -1; // -1 = auto-generate supplier code
		self::$socidA = (int) $socA->create($user);

		// RIB_A_DEFAULT: first bank account for COMPANY_A, will be the default (default_rib=1)
		$ribADef = new CompanyBankAccount($db);
		$ribADef->socid = self::$socidA;
		$ribADef->type = 'ban';
		$ribADef->iban = self::IBAN_A_DEFAULT;
		$ribADef->bic = self::BIC;
		$ribADef->default_rib = 1;
		self::$ribADefaultId = (int) $ribADef->create($user);
		$ribADef->update($user);

		// RIB_A_SPECIFIC: second bank account for COMPANY_A, non-default
		$ribASpec = new CompanyBankAccount($db);
		$ribASpec->socid = self::$socidA;
		$ribASpec->type = 'ban';
		$ribASpec->iban = self::IBAN_A_SPECIFIC;
		$ribASpec->bic = self::BIC;
		$ribASpec->default_rib = 0;
		self::$ribASpecificId = (int) $ribASpec->create($user);
		$ribASpec->update($user);

		// ------------------------------------------------------------------
		// COMPANY_B: same structure, distinct IBANs
		// ------------------------------------------------------------------
		$socB = new Societe($db);
		$socB->name = 'BonVirementTest CompanyB';
		$socB->fournisseur = 1;
		$socB->country_id = 1;
		$socB->code_fournisseur = -1;
		self::$socidB = (int) $socB->create($user);

		$ribBDef = new CompanyBankAccount($db);
		$ribBDef->socid = self::$socidB;
		$ribBDef->type = 'ban';
		$ribBDef->iban = self::IBAN_B_DEFAULT;
		$ribBDef->bic = self::BIC;
		$ribBDef->default_rib = 1;
		self::$ribBDefaultId = (int) $ribBDef->create($user);
		$ribBDef->update($user);

		$ribBSpec = new CompanyBankAccount($db);
		$ribBSpec->socid = self::$socidB;
		$ribBSpec->type = 'ban';
		$ribBSpec->iban = self::IBAN_B_SPECIFIC;
		$ribBSpec->bic = self::BIC;
		$ribBSpec->default_rib = 0;
		self::$ribBSpecificId = (int) $ribBSpec->create($user);
		$ribBSpec->update($user);

		// ------------------------------------------------------------------
		// Issuer bank account (llx_bank_account) passed as fk_bank_account
		// ------------------------------------------------------------------
		$account = new Account($db);
		$account->ref = 'BONVIR-TEST';
		$account->label = 'BonVirementTest Issuer';
		$account->country_id = 1; // France
		$account->date_solde = dol_now();
		$account->iban = 'FR7630001007941234567891058'; // valid IBAN, not used in assertions
		$account->bic = self::BIC;
		$account->ics = 'FR77ZZZ123456789'; // SEPA identifier (also used for bank-transfer)
		$account->ics_transfer = 'FR77ZZZ123456789'; // used when SEPA_USE_IDS is enabled
		$account->owner_name = 'TestCorp';
		$account->currency_code = 'EUR';
		self::$fkBankAccount = (int) $account->create($user);
	}

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param string       $name       Name
	 * @param array<mixed> $data      Test data
	 * @param string       $dataName   Test data name.
	 */
	public function __construct($name = null, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		// This const is tested in prelevement_check_config(), it SHOULD be set.
		$this->savconf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT = self::$fkBankAccount;
	}

	/**
	 * testBonVirementCreate
	 *
	 * Non-regression test: verifies that create() in simulation mode
	 * with no pending payment requests returns 0 (no requests processed, no error).
	 *
	 * @return	int
	 */
	public function testBonVirementCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject = new BonPrelevement($db);
		$result = $localobject->Create(0, 0, 'simu');

		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 0);

		return $result;
	}

	/**
	 * testTwoSuppliersSpecificRib
	 *
	 * Verifies that when two different suppliers each have one invoice with a
	 * specific bank account forced in the bank transfer request, the generated
	 * SEPA file contains the forced IBAN for each transaction (not the
	 * supplier's default bank account.)
	 *
	 * Scenario:
	 *   INV_A (100) -> request with RIB_A_SPECIFIC -> expects IBAN_A_SPECIFIC
	 *   INV_B (300) -> request with RIB_B_SPECIFIC -> expects IBAN_B_SPECIFIC
	 *
	 * @return void
	 */
	public function testTwoSuppliersSpecificRib()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertGreaterThan(0, self::$socidA, 'setUpBeforeClass() did not create fixtures (socidA)');
		$this->assertGreaterThan(0, self::$fkBankAccount, 'setUpBeforeClass() did not create issuer account');

		// Create one invoice for COMPANY_A (100) and one for COMPANY_B (300)
		$facA = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$facB = $this->createValidatedSupplierInvoice(self::$socidB, 300.0);

		// Link each invoice to its specific bank account (not the default)
		$demAId = $this->createPaymentRequest($facA, 100.0, self::$ribASpecificId);
		$demBId = $this->createPaymentRequest($facB, 300.0, self::$ribBSpecificId);

		// Generate the bank transfer order with both requests
		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer',
							   array($demAId, $demBId), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error);

		// Parse the SEPA file and extract IBAN -> amount pairs
		$ibanAmounts = $this->parseSepaIbanAmounts($bon->filename);

		// Invoice A must use COMPANY_A's specific IBAN
		$this->assertEquals(100.0, $ibanAmounts[self::IBAN_A_SPECIFIC],
			'COMPANY_A invoice must use IBAN_A_SPECIFIC (forced bank account)');
		// Invoice B must use COMPANY_B's specific IBAN
		$this->assertEquals(300.0, $ibanAmounts[self::IBAN_B_SPECIFIC],
			'COMPANY_B invoice must use IBAN_B_SPECIFIC (forced bank account)');
		// Total must be 100 + 300
		$this->assertEquals(400.0, $bon->total,
			'Order total must equal the sum of both invoices');

		$this->assertSepaXmlValid($bon->filename);
	}

	/**
	 * testTwoSuppliersDefaultRib
	 *
	 * Verifies that when two different suppliers each have one invoice with no
	 * forced bank account in the bank transfer request (fk_societe_rib IS NULL),
	 * the generated SEPA file contains each supplier's default IBAN (default_rib=1).
	 *
	 * Scenario:
	 *   INV_A (100) -> request with no forced RIB -> expects IBAN_A_DEFAULT
	 *   INV_B (300) -> request with no forced RIB -> expects IBAN_B_DEFAULT
	 *
	 * @return void
	 */
	public function testTwoSuppliersDefaultRib()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertGreaterThan(0, self::$socidA, 'setUpBeforeClass() did not create fixtures (socidA)');
		$this->assertGreaterThan(0, self::$fkBankAccount, 'setUpBeforeClass() did not create issuer account');

		// Create one invoice for COMPANY_A (100) and one for COMPANY_B (300)
		$facA = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$facB = $this->createValidatedSupplierInvoice(self::$socidB, 300.0);

		// No forced bank account: fk_societe_rib will be NULL -> default RIB used
		$demAId = $this->createPaymentRequest($facA, 100.0);
		$demBId = $this->createPaymentRequest($facB, 300.0);

		// Generate the bank transfer order with both requests
		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer',
							   array($demAId, $demBId), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error);

		// Parse the SEPA file and extract IBAN -> amount pairs
		$ibanAmounts = $this->parseSepaIbanAmounts($bon->filename);

		// Invoice A must use COMPANY_A's default IBAN
		$this->assertEquals(100.0, $ibanAmounts[self::IBAN_A_DEFAULT],
			'COMPANY_A invoice must use IBAN_A_DEFAULT (default_rib=1, no forced account)');
		// Invoice B must use COMPANY_B's default IBAN
		$this->assertEquals(300.0, $ibanAmounts[self::IBAN_B_DEFAULT],
			'COMPANY_B invoice must use IBAN_B_DEFAULT (default_rib=1, no forced account)');
		// No specific IBAN must appear in this order
		$this->assertArrayNotHasKey(self::IBAN_A_SPECIFIC, $ibanAmounts,
			'IBAN_A_SPECIFIC must not appear when no RIB is forced for COMPANY_A');
		$this->assertArrayNotHasKey(self::IBAN_B_SPECIFIC, $ibanAmounts,
			'IBAN_B_SPECIFIC must not appear when no RIB is forced for COMPANY_B');
		// Total must be 100 + 300
		$this->assertEquals(400.0, $bon->total,
			'Order total must equal the sum of both invoices');

		$this->assertSepaXmlValid($bon->filename);
	}

	/**
	 * testTwoSuppliersDefaultRibFilteredToFirst
	 *
	 * Verifies that when $dids is limited to only the first payment request,
	 * only COMPANY_A's invoice appears in the SEPA file (even though a second
	 * request exists in the database for COMPANY_B.)
	 *
	 * Scenario:
	 *   INV_A (100) -> request with no forced RIB -> expects IBAN_A_DEFAULT  (in $dids)
	 *   INV_B (300) -> request with no forced RIB -> must NOT appear in file  (not in $dids)
	 *
	 * @return void
	 */
	public function testTwoSuppliersDefaultRibFilteredToFirst()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertGreaterThan(0, self::$socidA, 'setUpBeforeClass() did not create fixtures (socidA)');
		$this->assertGreaterThan(0, self::$fkBankAccount, 'setUpBeforeClass() did not create issuer account');

		// Create one invoice for each supplier
		$facA = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$facB = $this->createValidatedSupplierInvoice(self::$socidB, 300.0);

		// Create both payment requests (no forced bank account for either)
		$demAId = $this->createPaymentRequest($facA, 100.0);
		$demBId = $this->createPaymentRequest($facB, 300.0); // exists in DB but excluded from $dids

		// Generate the order with ONLY the first request ($demAId)
		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer',
							   array($demAId), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error);

		$ibanAmounts = $this->parseSepaIbanAmounts($bon->filename);

		// COMPANY_A must appear with its default IBAN
		$this->assertEquals(100.0, $ibanAmounts[self::IBAN_A_DEFAULT],
			'COMPANY_A invoice must use IBAN_A_DEFAULT when no RIB is forced');
		// COMPANY_B must NOT appear since its request was not in $dids
		$this->assertArrayNotHasKey(self::IBAN_B_DEFAULT, $ibanAmounts,
			'COMPANY_B must not appear: its request was excluded from $dids');
		// Total must be 100 only
		$this->assertEquals(100.0, $bon->total,
			'Order total must equal only the included request');

		$this->assertSepaXmlValid($bon->filename);
	}

	/**
	 * testOneSupplierTwoRibs
	 *
	 * Verifies that for the same supplier with two invoices, the bank account
	 * selection is correct based on whether a specific account is forced:
	 *   - A request with a forced bank account uses that specific IBAN.
	 *   - A request without a forced account (ribId=0) falls back to the
	 *     supplier's default bank account (default_rib=1).
	 * No IBAN from COMPANY_B should appear in the file.
	 *
	 * Scenario:
	 *   INV_A1 (100) -> request with RIB_A_SPECIFIC -> expects IBAN_A_SPECIFIC
	 *   INV_A2 (200) -> request without forced RIB  -> expects IBAN_A_DEFAULT
	 *
	 * @return void
	 */
	public function testOneSupplierTwoRibs()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertGreaterThan(0, self::$socidA, 'setUpBeforeClass() did not create fixtures (socidA)');
		$this->assertGreaterThan(0, self::$fkBankAccount, 'setUpBeforeClass() did not create issuer account');

		// Two invoices for COMPANY_A with different amounts
		$facA1 = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$facA2 = $this->createValidatedSupplierInvoice(self::$socidA, 200.0);

		// First request: specific bank account forced (not the default)
		$demA1Id = $this->createPaymentRequest($facA1, 100.0, self::$ribASpecificId);
		// Second request: no forced bank account (ribId=0 -> default will be used)
		$demA2Id = $this->createPaymentRequest($facA2, 200.0);

		// Generate the order with both COMPANY_A requests only
		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer',
							   array($demA1Id, $demA2Id), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error);

		$ibanAmounts = $this->parseSepaIbanAmounts($bon->filename);

		// INV_A1: forced bank account -> must use IBAN_A_SPECIFIC
		$this->assertEquals(100.0, $ibanAmounts[self::IBAN_A_SPECIFIC],
			'INV_A1 with forced bank account must use IBAN_A_SPECIFIC');
		// INV_A2: no forced account -> must fall back to default IBAN_A_DEFAULT
		$this->assertEquals(200.0, $ibanAmounts[self::IBAN_A_DEFAULT],
			'INV_A2 without forced account must use IBAN_A_DEFAULT (default_rib=1)');
		// No IBAN from COMPANY_B must appear in this order
		$this->assertArrayNotHasKey(self::IBAN_B_DEFAULT, $ibanAmounts,
			'COMPANY_B bank account must not appear in a COMPANY_A-only order');
		$this->assertEquals(300.0, $bon->total);

		$this->assertSepaXmlValid($bon->filename);
	}

	/**
	 * testOneSupplierTwoRibsSpecificOnly
	 *
	 * Verifies that when $dids contains only the request with the specific bank
	 * account, only that invoice appears in the SEPA file (even though a second
	 * request using the default RIB exists in the database.)
	 *
	 * Scenario:
	 *   INV_A1 (100) -> request with RIB_A_SPECIFIC -> expects IBAN_A_SPECIFIC  (in $dids)
	 *   INV_A2 (200) -> request without forced RIB   -> must NOT appear in file   (not in $dids)
	 *
	 * @return void
	 */
	public function testOneSupplierTwoRibsSpecificOnly()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertGreaterThan(0, self::$socidA, 'setUpBeforeClass() did not create fixtures (socidA)');
		$this->assertGreaterThan(0, self::$fkBankAccount, 'setUpBeforeClass() did not create issuer account');

		// Two invoices for COMPANY_A with different amounts
		$facA1 = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$facA2 = $this->createValidatedSupplierInvoice(self::$socidA, 200.0);

		// First request: specific bank account forced
		$demA1Id = $this->createPaymentRequest($facA1, 100.0, self::$ribASpecificId);
		// Second request: no forced bank account (exists in DB but excluded from $dids)
		$demA2Id = $this->createPaymentRequest($facA2, 200.0);

		// Generate the order with ONLY the specific-RIB request ($demA1Id)
		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer',
							   array($demA1Id), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error);

		$ibanAmounts = $this->parseSepaIbanAmounts($bon->filename);

		// INV_A1 must appear with the forced specific IBAN
		$this->assertEquals(100.0, $ibanAmounts[self::IBAN_A_SPECIFIC],
			'INV_A1 with forced bank account must use IBAN_A_SPECIFIC');
		// INV_A2 must NOT appear since its request was not in $dids
		$this->assertArrayNotHasKey(self::IBAN_A_DEFAULT, $ibanAmounts,
			'INV_A2 must not appear: its request was excluded from $dids');
		// Total must be 100 only
		$this->assertEquals(100.0, $bon->total,
			'Order total must equal only the included request');

		$this->assertSepaXmlValid($bon->filename);
	}

	/**
	 * testMixedOrderFourInvoices
	 *
	 * Verifies that bank account assignment remains correct when bank transfer
	 * requests from two suppliers are interleaved (A1, B1, A2, B2).
	 * The test guards against cross-contamination between suppliers or between
	 * consecutive requests.
	 *
	 * Scenario (requests created in interleaved order):
	 *   INV_A1 (100) -> forced: RIB_A_SPECIFIC -> expects IBAN_A_SPECIFIC
	 *   INV_B1 (300) -> no forced RIB -> default  -> expects IBAN_B_DEFAULT
	 *   INV_A2 (200) -> no forced RIB -> default  -> expects IBAN_A_DEFAULT
	 *   INV_B2 (400) -> forced: RIB_B_SPECIFIC -> expects IBAN_B_SPECIFIC
	 *
	 * @return void
	 */
	public function testMixedOrderFourInvoices()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->assertGreaterThan(0, self::$socidA, 'setUpBeforeClass() did not create fixtures (socidA)');
		$this->assertGreaterThan(0, self::$fkBankAccount, 'setUpBeforeClass() did not create issuer account');

		// Interleaved invoices: A1, B1, A2, B2
		$facA1 = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$facB1 = $this->createValidatedSupplierInvoice(self::$socidB, 300.0);
		$facA2 = $this->createValidatedSupplierInvoice(self::$socidA, 200.0);
		$facB2 = $this->createValidatedSupplierInvoice(self::$socidB, 400.0);

		// Interleaved requests: forced, no RIB, no RIB, forced
		$demA1Id = $this->createPaymentRequest($facA1, 100.0, self::$ribASpecificId); // forced
		$demB1Id = $this->createPaymentRequest($facB1, 300.0);                        // -> RIB_B_DEFAULT
		$demA2Id = $this->createPaymentRequest($facA2, 200.0);                        // -> RIB_A_DEFAULT
		$demB2Id = $this->createPaymentRequest($facB2, 400.0, self::$ribBSpecificId); // forced

		// Generate the order with all four requests in interleaved order
		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer',
							   array($demA1Id, $demB1Id, $demA2Id, $demB2Id), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error);

		$ibanAmounts = $this->parseSepaIbanAmounts($bon->filename);

		// Verify each invoice is matched to the correct IBAN with no cross-contamination
		$this->assertEquals(100.0, $ibanAmounts[self::IBAN_A_SPECIFIC],
			'INV_A1 with forced bank account must use IBAN_A_SPECIFIC');
		$this->assertEquals(300.0, $ibanAmounts[self::IBAN_B_DEFAULT],
			'INV_B1 without forced account must use IBAN_B_DEFAULT (default_rib=1 of COMPANY_B)');
		$this->assertEquals(200.0, $ibanAmounts[self::IBAN_A_DEFAULT],
			'INV_A2 without forced account must use IBAN_A_DEFAULT (default_rib=1 of COMPANY_A)');
		$this->assertEquals(400.0, $ibanAmounts[self::IBAN_B_SPECIFIC],
			'INV_B2 with forced bank account must use IBAN_B_SPECIFIC');
		$this->assertEquals(1000.0, $bon->total,
			'Order total must equal the sum of all four invoices');

		$this->assertSepaXmlValid($bon->filename);
	}

	// ---------------------------------------------------------------------------
	// Private helpers
	// ---------------------------------------------------------------------------

	/**
	 * Creates and validates a supplier invoice for a given company and amount.
	 * The invoice contains a single service line at 0% VAT.
	 *
	 * @param int   $socid  Row ID of the third party
	 * @param float $amount Pre-tax amount of the line (= total incl. tax with 0% VAT)
	 * @return FactureFournisseur
	 */
	private function createValidatedSupplierInvoice(int $socid, float $amount): FactureFournisseur
	{
		global $user, $db;

		$fac = new FactureFournisseur($db);
		$fac->socid = $socid;
		$fac->date = dol_now();
		$fac->cond_reglement_code = 'RECEP';
		$fac->mode_reglement_code = 'VIR'; // bank transfer payment mode
		$fac->ref_supplier = 'SUPP-TEST-'.$socid.'-'.$amount.'-'.uniqid();

		$result = $fac->create($user);
		$this->assertGreaterThan(0, $result, 'FactureFournisseur::create() failed: '.$fac->error);

		// Service line at 0% VAT so total_ttc = $amount
		// Signature: addline(desc, pu, txtva, txlocaltax1, txlocaltax2, qty, ...)
		$fac->addline('Service test', $amount, 0, 0, 0, 1);

		$result = $fac->validate($user);
		$this->assertGreaterThanOrEqual(0, $result, 'FactureFournisseur::validate() failed: '.$fac->error);

		return $fac;
	}

	/**
	 * Creates a bank transfer payment request (demande_prelevement) for a validated supplier invoice.
	 * If $ribId is provided, the request stores that specific bank account in fk_societe_rib.
	 * Otherwise (ribId=0), the supplier's default bank account will be used.
	 *
	 * @param FactureFournisseur $fac    Validated supplier invoice
	 * @param float              $amount Requested amount
	 * @param int                $ribId  Row ID of the bank account to force (0 = use default)
	 * @return int Row ID of the created entry in llx_prelevement_demande
	 */
	private function createPaymentRequest(FactureFournisseur $fac, float $amount, int $ribId = 0): int
	{
		global $user, $db;

		// Insert a row in llx_prelevement_demande with fk_facture_fourn and fk_societe_rib = $ribId
		$result = $fac->demande_prelevement($user, $amount, 'bank-transfer', 'supplier_invoice', 0, $ribId);
		$this->assertEquals(1, $result, 'demande_prelevement() failed for invoice #'.$fac->id.': '.$fac->error);

		// Retrieve the row ID of the freshly inserted request
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."prelevement_demande WHERE fk_facture_fourn = ".((int) $fac->id)." AND traite = 0 ORDER BY rowid DESC LIMIT 1";
		$resql = $db->query($sql);
		$obj = $db->fetch_object($resql);
		$this->assertNotNull($obj, 'Payment request not found in DB for invoice #'.$fac->id);

		return (int) $obj->rowid;
	}

	/**
	 * Asserts that a generated SEPA XML file validates against the pain.001.001.03 XSD schema.
	 *
	 * @param string $filename Path to the SEPA XML file
	 * @return void
	 */
	private function assertSepaXmlValid(string $filename): void
	{
		$this->assertFileExists($filename, 'SEPA XML file does not exist: '.$filename);
		$this->assertFileExists(self::XSD_PAIN_001, 'XSD schema file not found: '.self::XSD_PAIN_001);

		$dom = new DOMDocument();
		$loaded = $dom->load($filename);
		$this->assertTrue($loaded, 'DOMDocument failed to load SEPA XML: '.$filename);

		libxml_use_internal_errors(true);
		$valid = $dom->schemaValidate(self::XSD_PAIN_001);
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		$messages = array();
		foreach ($errors as $error) {
			$messages[] = trim($error->message).' (line '.$error->line.')';
		}
		$this->assertTrue($valid, 'SEPA XML does not validate against pain.001.001.03 XSD: '.implode('; ', $messages));
	}

	/**
	 * Parses a generated SEPA pain.001 XML file and returns an associative
	 * array of IBAN => amount for each CdtTrfTxInf transaction element.
	 *
	 * @param string $filename Path to the SEPA XML file
	 * @return array<string,float> Array keyed by IBAN with transaction amount as value
	 */
	private function parseSepaIbanAmounts(string $filename): array
	{
		$this->assertFileExists($filename, 'SEPA XML file was not created: '.$filename);

		$xml = simplexml_load_file($filename);
		$this->assertNotFalse($xml, 'Failed to parse SEPA XML file: '.$filename);

		// Register the SEPA credit-transfer namespace (pain.001.001.03)
		$ns = 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.03';
		$xml->registerXPathNamespace('ns', $ns);

		$result = array();
		foreach ($xml->xpath('//ns:CdtTrfTxInf') as $txInf) {
			$txInf->registerXPathNamespace('ns', $ns);
			// Creditor IBAN (the supplier being paid)
			$ibanNodes = $txInf->xpath('ns:CdtrAcct/ns:Id/ns:IBAN');
			// Transaction amount (nested under Amt element in pain.001)
			$amountNodes = $txInf->xpath('ns:Amt/ns:InstdAmt');

			if (!empty($ibanNodes) && !empty($amountNodes)) {
				$iban   = (string) $ibanNodes[0];
				$amount = (float) $amountNodes[0];
				$result[$iban] = $amount;
			}
		}

		return $result;
	}
}
