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

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/admin.lib.php';
require_once dirname(__FILE__).'/../../htdocs/compta/prelevement/class/bonprelevement.class.php';
require_once dirname(__FILE__).'/../../htdocs/fourn/class/fournisseur.facture.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/societe.class.php';
require_once dirname(__FILE__).'/../../htdocs/societe/class/companybankaccount.class.php';
require_once dirname(__FILE__).'/../../htdocs/compta/bank/class/account.class.php';

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
	const XSD_PAIN_001_V9 = __DIR__.'/../assets/xsd/pain.001.001.09.xsd';
	const XSD_PAIN_001_V9_CH = __DIR__.'/../assets/xsd/pain.001.001.09.ch.03.xsd';
	const IBAN_CH         = 'CH9300762011623852957';
	const QRIBAN_CH       = 'CH4431999123000889012';
	const QRREFERENCE     = '210000000003139471430009017';
	const BIC_CH          = 'UBSWCHZH80A';

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
	/** @var array<string,?string> Constants changed by setCompanyAddressForVersion9(), with the value to restore */
	private $savedConstants = array();

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
	 * Init phpunit tests
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		parent::setUp();

		// This const is tested in prelevement_check_config(), it SHOULD be set.
		$this->savconf->global->PAYMENTBYBANKTRANSFER_ID_BANKACCOUNT = self::$fkBankAccount;
	}

	/**
	 * End phpunit tests
	 *
	 * @return void
	 */
	protected function tearDown(): void
	{
		global $conf;

		foreach ($this->savedConstants as $name => $value) {
			if ($value === null) {
				unset($conf->global->$name);
			} else {
				$conf->global->$name = $value;
			}
		}
		$this->savedConstants = array();

		parent::tearDown();
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

	/**
	 * testGenerateWithSepaSchemaVersion9
	 *
	 * With PAYMENTBYBANKTRANSFER_SEPA_SCHEMA_VERSION=9 the file is a pain.001.001.09 that passes the ISO and the
	 * Swiss SPS schemas, a long street continues on a second AdrLine and an invoice paid to a QR-IBAN carries its
	 * QR reference.
	 *
	 * @return void
	 */
	public function testGenerateWithSepaSchemaVersion9()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->setCompanyAddressForVersion9();

		$longstreet = 'Avenue de la Gare 12 Batiment B troisieme etage porte de gauche apres l ascenseur';
		$socidC = $this->createSwissSupplier('BonVirementTest CompanyC', $longstreet, self::IBAN_CH);
		$socidD = $this->createSwissSupplier('BonVirementTest CompanyD', 'Rue du Lac 1', self::QRIBAN_CH);

		$facC = $this->createValidatedSupplierInvoice($socidC, 150.0);
		$facD = $this->createValidatedSupplierInvoice($socidD, 250.0);
		$facD->payment_reference = self::QRREFERENCE;
		$this->assertGreaterThan(0, $facD->update($user), 'FactureFournisseur::update() failed: '.$facD->error);

		$demCId = $this->createPaymentRequest($facC, 150.0);
		$demDId = $this->createPaymentRequest($facD, 250.0);

		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer', array($demCId, $demDId), self::$fkBankAccount);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error.' '.implode(', ', $bon->invoice_in_error));

		$this->assertXmlValidAgainst($bon->filename, self::XSD_PAIN_001_V9);
		$this->assertXmlValidAgainst($bon->filename, self::XSD_PAIN_001_V9_CH);

		$xml = simplexml_load_file($bon->filename);
		$xml->registerXPathNamespace('ns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09');

		$this->assertCount(1, $xml->xpath('//ns:PmtInf/ns:ReqdExctnDt/ns:Dt'));
		$this->assertCount(1, $xml->xpath('//ns:DbtrAgt/ns:FinInstnId/ns:BICFI'));

		$adrlines = $xml->xpath('//ns:CdtTrfTxInf[ns:CdtrAcct/ns:Id/ns:IBAN="'.self::IBAN_CH.'"]/ns:Cdtr/ns:PstlAdr/ns:AdrLine');
		$this->assertCount(2, $adrlines, 'A street longer than 70 chars must continue on a second AdrLine');
		$this->assertSame('Avenue de la Gare 12 Batiment B troisieme etage porte de gauche apres', (string) $adrlines[0]);
		$this->assertSame('l ascenseur', (string) $adrlines[1]);

		$qrr = $xml->xpath('//ns:CdtTrfTxInf[ns:CdtrAcct/ns:Id/ns:IBAN="'.self::QRIBAN_CH.'"]/ns:RmtInf/ns:Strd/ns:CdtrRefInf');
		$this->assertCount(1, $qrr, 'The invoice paid to a QR-IBAN must carry its QR reference');
		$this->assertSame('QRR', (string) $qrr[0]->Tp->CdOrPrtry->Prtry);
		$this->assertSame(self::QRREFERENCE, (string) $qrr[0]->Ref);
	}

	/**
	 * testFormatOfTheBankAccount
	 *
	 * The file uses the format of the bank account it is issued from, whatever the format of the setup.
	 *
	 * @return void
	 */
	public function testFormatOfTheBankAccount()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->setCompanyAddressForVersion9();

		$account = new Account($db);
		$account->ref = 'BONVIR-SEPA3';
		$account->label = 'BonVirementTest pain.001.001.03';
		$account->country_id = 1;
		$account->date_solde = dol_now();
		$account->iban = 'FR7630001007941234567891058';
		$account->bic = self::BIC;
		$account->owner_name = 'TestCorp';
		$account->currency_code = 'EUR';
		$account->sepa_transfer_schema_version = '3';
		$this->assertGreaterThan(0, $account->create($user), $account->errorsToString());

		$facA = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$demAId = $this->createPaymentRequest($facA, 100.0);

		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer', array($demAId), $account->id);
		$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error.' '.implode(', ', $bon->invoice_in_error));

		$this->assertStringContainsString('urn:iso:std:iso:20022:tech:xsd:pain.001.001.03', (string) file_get_contents($bon->filename));
		$this->assertSepaXmlValid($bon->filename);
	}

	/**
	 * testDocumentCurrency
	 *
	 * Each transfer goes in the currency of its invoice: the SEPA payments in one block, the others in one block per
	 * currency with shared fees, and the payment made when the order is credited settles the invoice in its currency.
	 * A SEPA file (pain.001.001.03) refuses the invoices that are not in EUR.
	 *
	 * @return void
	 */
	public function testDocumentCurrency()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';
		$savmodmulticurrency = $conf->modules['multicurrency'] ?? null;
		$conf->modules['multicurrency'] = 'multicurrency';
		if (!MultiCurrency::getIdFromCode($db, 'USD')) {
			$currency = new MultiCurrency($db);
			$currency->code = 'USD';
			$currency->name = 'US Dollar';
			$this->assertGreaterThan(0, $currency->create($user), 'MultiCurrency::create failed: '.implode(',', $currency->errors));
		}

		try {
			$this->setCompanyAddressForVersion9();
			$socid = $this->createSwissSupplier('BonVirementTest CompanyE', 'Rue du Lac 2', self::IBAN_CH);

			$facEUR = $this->createValidatedSupplierInvoice($socid, 100.0);
			$facUSD = $this->createValidatedSupplierInvoice($socid, 100.0, 'USD', 1.1);
			$demEURId = $this->createPaymentRequest($facEUR, 100.0);
			$demUSDId = $this->createPaymentRequest($facUSD, 100.0);

			$resql = $db->query("SELECT multicurrency_code, multicurrency_amount FROM ".MAIN_DB_PREFIX."prelevement_demande WHERE rowid = ".((int) $demUSDId));
			$obj = $db->fetch_object($resql);
			$this->assertSame('USD', $obj->multicurrency_code);
			$this->assertEquals(110.0, (float) $obj->multicurrency_amount);

			$account = new Account($db);
			$account->ref = 'BONVIR-SPS9';
			$account->label = 'BonVirementTest pain.001.001.09';
			$account->country_id = 1;
			$account->date_solde = dol_now();
			$account->iban = 'FR7630001007941234567891058';
			$account->bic = self::BIC;
			$account->owner_name = 'TestCorp';
			$account->currency_code = 'EUR';
			$account->sepa_transfer_schema_version = '9';
			$this->assertGreaterThan(0, $account->create($user), $account->errorsToString());

			$bon = new BonPrelevement($db);
			$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer', array($demEURId, $demUSDId), $account->id);
			$this->assertGreaterThanOrEqual(0, $result, 'BonPrelevement::create() failed: '.$bon->error.' '.implode(', ', $bon->invoice_in_error));
			$this->assertEquals(200.0, $bon->total, 'The order total stays in the company currency');

			$this->assertXmlValidAgainst($bon->filename, self::XSD_PAIN_001_V9);
			$this->assertXmlValidAgainst($bon->filename, self::XSD_PAIN_001_V9_CH);

			$xml = simplexml_load_file($bon->filename);
			$xml->registerXPathNamespace('ns', 'urn:iso:std:iso:20022:tech:xsd:pain.001.001.09');
			$this->assertSame('210', (string) $xml->xpath('//ns:GrpHdr/ns:CtrlSum')[0]);
			$this->assertCount(2, $xml->xpath('//ns:PmtInf'));
			$this->assertSame('SLEV', (string) $xml->xpath('//ns:PmtInf[ns:CdtTrfTxInf/ns:Amt/ns:InstdAmt/@Ccy="EUR"]/ns:ChrgBr')[0]);
			$this->assertSame('SHAR', (string) $xml->xpath('//ns:PmtInf[ns:CdtTrfTxInf/ns:Amt/ns:InstdAmt/@Ccy="USD"]/ns:ChrgBr')[0]);
			$this->assertSame('110', (string) $xml->xpath('//ns:InstdAmt[@Ccy="USD"]')[0]);

			$this->assertGreaterThan(0, $bon->fetch($bon->id));
			$this->assertGreaterThanOrEqual(0, $bon->set_infotrans($user, dol_now(), 2), (string) $bon->error);
			$this->assertGreaterThan(0, $bon->fetch($bon->id));
			$this->assertGreaterThanOrEqual(0, $bon->set_infocredit($user, dol_now()), (string) $bon->error);

			$facUSD->fetch($facUSD->id);
			$this->assertEquals(1, $facUSD->paid, 'The invoice in USD must be paid by the 110 USD of the transfer');
			$this->assertEquals(0, (float) $facUSD->getRemainToPay(1));

			$facUSD2 = $this->createValidatedSupplierInvoice($socid, 100.0, 'USD', 1.1);
			$demUSD2Id = $this->createPaymentRequest($facUSD2, 100.0);
			$conf->global->PAYMENTBYBANKTRANSFER_SEPA_SCHEMA_VERSION = '3';
			$sepa = new BonPrelevement($db);
			$result = $sepa->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer', array($demUSD2Id), self::$fkBankAccount);
			$this->assertLessThan(0, $result, 'A SEPA file must refuse an invoice in USD');
			$this->assertArrayHasKey('currency'.$facUSD2->id, $sepa->invoice_in_error);
		} finally {
			if ($savmodmulticurrency === null) {
				unset($conf->modules['multicurrency']);
			} else {
				$conf->modules['multicurrency'] = $savmodmulticurrency;
			}
		}
	}

	/**
	 * testVersion9RefusesIncompleteAddress
	 *
	 * pain.001.001.09 needs the town and the country of every party: the order is not created and the error
	 * links to the edit page of the supplier to complete.
	 *
	 * @return void
	 */
	public function testVersion9RefusesIncompleteAddress()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$this->setCompanyAddressForVersion9();

		$facA = $this->createValidatedSupplierInvoice(self::$socidA, 100.0);
		$demAId = $this->createPaymentRequest($facA, 100.0);

		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer', array($demAId), self::$fkBankAccount);

		$this->assertLessThan(0, $result, 'The order must not be created when the address of a supplier has no town');
		$this->assertArrayHasKey('soc'.self::$socidA, $bon->invoice_in_error);
		$this->assertStringContainsString('/societe/card.php?socid='.self::$socidA.'&action=edit', $bon->invoice_in_error['soc'.self::$socidA]);
	}

	/**
	 * testQRIBANNeedsQRReference
	 *
	 * A QR-IBAN only accepts payments with a QR reference, whatever the file format.
	 *
	 * @return void
	 */
	public function testQRIBANNeedsQRReference()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$socidD = $this->createSwissSupplier('BonVirementTest CompanyD', 'Rue du Lac 1', self::QRIBAN_CH);
		$facD = $this->createValidatedSupplierInvoice($socidD, 250.0);
		$demDId = $this->createPaymentRequest($facD, 250.0);

		$bon = new BonPrelevement($db);
		$result = $bon->create('', '', 'real', 'ALL', 0, 0, 'bank-transfer', array($demDId), self::$fkBankAccount);

		$this->assertLessThan(0, $result, 'The order must not be created when an invoice paid to a QR-IBAN has no QR reference');
		$this->assertArrayHasKey('invoice'.$facD->id, $bon->invoice_in_error);
		$this->assertStringContainsString('/fourn/facture/card.php?facid='.$facD->id, $bon->invoice_in_error['invoice'.$facD->id]);
	}

	// ---------------------------------------------------------------------------
	// Private helpers
	// ---------------------------------------------------------------------------

	/**
	 * Selects pain.001.001.09 and gives the company the town and country it needs, for the current test only.
	 *
	 * @return void
	 */
	private function setCompanyAddressForVersion9(): void
	{
		global $conf;

		$values = array(
			'PAYMENTBYBANKTRANSFER_SEPA_SCHEMA_VERSION' => '9',
			'MAIN_INFO_SOCIETE_TOWN' => 'Paris',
			'MAIN_INFO_SOCIETE_COUNTRY' => '1:FR:France',
		);
		foreach ($values as $name => $value) {
			$this->savedConstants[$name] = $conf->global->$name ?? null;
			$conf->global->$name = $value;
		}
	}

	/**
	 * Creates a Swiss supplier with a full address and a default bank account.
	 *
	 * @param string $name   Name of the supplier
	 * @param string $street Street address
	 * @param string $iban   IBAN of its default bank account
	 * @return int Row ID of the supplier
	 */
	private function createSwissSupplier(string $name, string $street, string $iban): int
	{
		global $user, $db;

		$soc = new Societe($db);
		$soc->name = $name;
		$soc->fournisseur = 1;
		$soc->code_fournisseur = -1;
		$soc->address = $street;
		$soc->zip = '1003';
		$soc->town = 'Lausanne';
		$soc->country_id = 6; // Switzerland
		$socid = (int) $soc->create($user);
		$this->assertGreaterThan(0, $socid, 'Societe::create() failed: '.$soc->error);

		$rib = new CompanyBankAccount($db);
		$rib->socid = $socid;
		$rib->type = 'ban';
		$rib->iban = $iban;
		$rib->bic = self::BIC_CH;
		$rib->default_rib = 1;
		$this->assertGreaterThan(0, $rib->create($user), 'CompanyBankAccount::create() failed: '.$rib->error);
		$rib->update($user);

		return $socid;
	}

	/**
	 * Creates and validates a supplier invoice for a given company and amount.
	 * The invoice contains a single service line at 0% VAT.
	 *
	 * @param int    $socid    Row ID of the third party
	 * @param float  $amount   Pre-tax amount of the line (= total incl. tax with 0% VAT)
	 * @param string $currency Currency of the invoice, '' for the company currency
	 * @param float  $rate     Rate of the currency of the invoice
	 * @return FactureFournisseur
	 */
	private function createValidatedSupplierInvoice(int $socid, float $amount, string $currency = '', float $rate = 1): FactureFournisseur
	{
		global $user, $db;

		$fac = new FactureFournisseur($db);
		$fac->socid = $socid;
		if ($currency) {
			$fac->multicurrency_code = $currency;
			$fac->multicurrency_tx = $rate;
		}
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
		$this->assertXmlValidAgainst($filename, self::XSD_PAIN_001);
	}

	/**
	 * Asserts that a generated XML file validates against an XSD schema.
	 *
	 * @param string $filename Path to the XML file
	 * @param string $xsd      Path to the XSD schema
	 * @return void
	 */
	private function assertXmlValidAgainst(string $filename, string $xsd): void
	{
		$this->assertFileExists($filename, 'SEPA XML file does not exist: '.$filename);
		$this->assertFileExists($xsd, 'XSD schema file not found: '.$xsd);

		$dom = new DOMDocument();
		$loaded = $dom->load($filename);
		$this->assertTrue($loaded, 'DOMDocument failed to load SEPA XML: '.$filename);

		libxml_use_internal_errors(true);
		$valid = $dom->schemaValidate($xsd);
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors(false);

		$messages = array();
		foreach ($errors as $error) {
			$messages[] = trim($error->message).' (line '.$error->line.')';
		}
		$this->assertTrue($valid, 'SEPA XML does not validate against '.basename($xsd).': '.implode('; ', $messages));
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
