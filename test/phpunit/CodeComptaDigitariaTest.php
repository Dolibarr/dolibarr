<?php
/* Copyright (C) 2026 Open-DSI <support@opendsi.fr>
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
 *      \file       test/phpunit/CodeComptaDigitariaTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the Digitaria thirdparty accountancy code model
 *      \remarks    To run this script as CLI:  phpunit CodeComptaDigitariaTest.php
 */

global $conf, $user, $langs, $db;

require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/modules/societe/mod_codecompta_digitaria.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/accounting.lib.php';

/**
 * In-memory subclass of the Digitaria model. The two seams that touch the database
 * (checkIfAccountancyCodeIsAlreadyUsed() and fetchUsedAccountancyCodes()) are overridden
 * to read from an in-memory set of already-used codes, so get_code() can be characterized
 * without any database access.
 */
class InMemoryDigitaria extends mod_codecompta_digitaria
{
	/** @var string[] Flat list of already stored codes */
	public $usedcodes = array();

	/** @var bool When true, both DB seams simulate an SQL error */
	public $simulateSqlError = false;

	/** @var bool When true, only checkIfAccountancyCodeIsAlreadyUsed() simulates an SQL error (fetch stays normal) */
	public $simulateCheckError = false;

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * @param	DoliDB	$db		Database handler (unused here)
	 * @param	string	$code	Code to check
	 * @param	string	$type	'customer' or 'supplier'
	 * @return	int				0 if available, -1 if already used, -2 on simulated SQL error
	 */
	public function checkIfAccountancyCodeIsAlreadyUsed($db, $code, $type = '')
	{
		// phpcs:enable
		if ($this->simulateSqlError || $this->simulateCheckError) {
			$this->error = 'SQL error simulated';
			return -2;
		}
		return in_array($code, $this->usedcodes, true) ? -1 : 0;
	}

	/**
	 * @param	DoliDB	$db				Database handler (unused here)
	 * @param	string	$type			'customer' or 'supplier'
	 * @param	string	$familyprefix	Prefix the codes must start with
	 * @param	int		$codelen		Exact length of the codes to return
	 * @return	string[]|false			Matching codes, or false on simulated SQL error
	 */
	protected function fetchUsedAccountancyCodes($db, $type, $familyprefix, $codelen)
	{
		if ($this->simulateSqlError) {
			$this->error = 'SQL error simulated';
			return false;
		}
		$res = array();
		foreach ($this->usedcodes as $c) {
			if (strlen($c) === (int) $codelen && strpos($c, $familyprefix) === 0) {
				$res[] = $c;
			}
		}
		return $res;
	}

	/**
	 * Generate a code for a name and register it as used (simulates a thirdparty creation).
	 *
	 * @param	string	$name	Thirdparty name
	 * @param	string	$type	'customer' or 'supplier'
	 * @return	string|false	The generated code, or false if get_code() failed
	 */
	public function assign($name, $type = 'customer')
	{
		global $db;

		$soc = new stdClass();
		$soc->name = $name;
		$r = $this->get_code($db, $soc, $type);
		if ($r < 0) {
			return false;
		}
		$this->usedcodes[] = $this->code;
		return $this->code;
	}
}

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals          disabled
 * @backupStaticAttributes enabled
 * @remarks                backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CodeComptaDigitariaTest extends CommonClassTest
{
	/**
	 * Set the Digitaria configuration for a test run.
	 *
	 * @param	string	$prefixcust		Customer prefix mask
	 * @param	int		$widthcust		Customer width
	 * @param	int		$aacount		ACCOUNTING_LENGTH_AACCOUNT
	 * @param	string	$prefixsup		Supplier prefix mask
	 * @param	int		$widthsup		Supplier width
	 * @return	InMemoryDigitaria		A fresh in-memory model
	 */
	private function makeModel($prefixcust = '411', $widthcust = 6, $aacount = 9, $prefixsup = '401', $widthsup = 6)
	{
		global $conf;

		$conf->global->COMPANY_DIGITARIA_MASK_CUSTOMER = $prefixcust;
		$conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_CUSTOMER = (string) $widthcust;
		$conf->global->COMPANY_DIGITARIA_MASK_SUPPLIER = $prefixsup;
		$conf->global->COMPANY_DIGITARIA_MASK_NBCHARACTER_SUPPLIER = (string) $widthsup;
		$conf->global->ACCOUNTING_LENGTH_AACCOUNT = (string) $aacount;
		$conf->global->COMPANY_DIGITARIA_REMOVE_SPECIAL = '1';
		$conf->global->COMPANY_DIGITARIA_CLEAN_WORDS = '';
		$conf->global->COMPANY_DIGITARIA_CLEAN_REGEX = '';
		$conf->global->COMPANY_DIGITARIA_UNIQUE_CODE = '1';
		unset($conf->global->ACCOUNTING_MANAGE_ZERO);

		return new InMemoryDigitaria();
	}

	/**
	 * Non-regression: for a name that fills the whole width, the historical sequence
	 * (bare code, then counter shortening the root) must be reproduced exactly.
	 *
	 * @return void
	 */
	public function testLongRootKeepsHistoricalSequence()
	{
		$model = $this->makeModel('411', 6, 9);

		$expected = array(
			'411MARTIN',
			'411MARTI0', '411MARTI1', '411MARTI2', '411MARTI3', '411MARTI4',
			'411MARTI5', '411MARTI6', '411MARTI7', '411MARTI8', '411MARTI9',
			'411MART10', '411MART11',
		);

		$got = array();
		$nbexpected = count($expected);
		for ($n = 0; $n < $nbexpected; $n++) {
			$got[] = $model->assign('MARTIN');
		}

		$this->assertSame($expected, $got, 'Historical sequence changed for a long root');
		foreach ($got as $code) {
			$this->assertSame(9, strlen($code), 'Code '.$code.' is not of fixed length 9');
		}
	}

	/**
	 * Non-regression: the next free counter fills holes (smallest absent value), like the
	 * historical loop that tried i=0,1,2... and took the first free one.
	 *
	 * @return void
	 */
	public function testNextFreeCounterFillsHoles()
	{
		$model = $this->makeModel('411', 6, 9);

		// Bare + all single-digit counters except 3 are already used.
		$model->usedcodes = array('411MARTIN', '411MARTI0', '411MARTI1', '411MARTI2', '411MARTI4', '411MARTI5', '411MARTI6', '411MARTI7', '411MARTI8', '411MARTI9');

		$this->assertSame('411MARTI3', $model->assign('MARTIN'), 'The hole at counter 3 was not filled first');
	}

	/**
	 * The ticket bug: once ~1001 codes share a root, the historical algorithm returned -1.
	 * The counter must now use the full width and keep producing valid fixed-length codes.
	 *
	 * @return void
	 */
	public function testSaturationBeyondThousandIsLifted()
	{
		$model = $this->makeModel('411', 6, 9);

		// Pre-seed the 1001 codes the historical algorithm could produce for root MARTIN.
		$seed = array('411MARTIN');
		for ($i = 0; $i <= 9; $i++) {
			$seed[] = '411MARTI'.$i;        // a=1
		}
		for ($i = 10; $i <= 99; $i++) {
			$seed[] = '411MART'.$i;         // a=2
		}
		for ($i = 100; $i <= 999; $i++) {
			$seed[] = '411MAR'.$i;          // a=3
		}
		$this->assertSame(1001, count($seed), 'Seed size is not the historical cap');
		$model->usedcodes = $seed;

		$code = $model->assign('MARTIN');
		$this->assertNotFalse($code, 'Creation is still blocked after ~1001 codes');
		$this->assertSame('411MA1000', $code, 'The counter did not extend to a 4-digit depth');
		$this->assertSame(9, strlen($code), 'Overflow code is not of fixed length 9');
	}

	/**
	 * Latent defect: short names produced variable-length codes that collided once the ledger
	 * padded them. Codes must now be fixed-length and stay distinct after length_accounta().
	 *
	 * @return void
	 */
	public function testShortRootIsFixedLengthAndCollisionFree()
	{
		$model = $this->makeModel('411', 6, 9);

		$codes = array();
		$codes[] = $model->assign('EDF');
		$codes[] = $model->assign('EDF');
		$codes[] = $model->assign('EDF');

		$this->assertSame(array('411EDF000', '411EDF001', '411EDF002'), $codes, 'Short root sequence is wrong');

		// Short name bug fixed: the first thirdparty keeps the root letters at the front.
		$this->assertSame(0, strpos($codes[0], '411EDF'), 'First short-root code lost its leading letters');

		// Fixed length and no collision once padded by the general ledger.
		$padded = array();
		foreach ($codes as $code) {
			$this->assertSame(9, strlen($code), 'Short root code '.$code.' is not of fixed length 9');
			$padded[] = length_accounta($code);
		}
		$this->assertSame($codes, $padded, 'length_accounta() should be a no-op when the code already has the auxiliary length');
		$this->assertSame(count($padded), count(array_unique($padded)), 'Padded codes collide in the ledger');
	}

	/**
	 * The historical variable-length codes collided after padding; the new fixed-length codes
	 * for the same short names do not.
	 *
	 * @return void
	 */
	public function testPaddingCollisionIsRemoved()
	{
		// Historical codes stored for two homonyms (variable length) collide once padded.
		$this->assertSame(length_accounta('411EDF'), length_accounta('411EDF0'), 'Precondition: the historical codes must collide after padding');

		// The new algorithm produces two distinct fixed-length codes for the same two creations.
		$model = $this->makeModel('411', 6, 9);
		$a = $model->assign('EDF');
		$b = $model->assign('EDF');
		$this->assertNotSame(length_accounta($a), length_accounta($b), 'New codes still collide after padding');
	}

	/**
	 * At least the first root letter is always kept, and real exhaustion returns an error
	 * (never a non-unique code).
	 *
	 * @return void
	 */
	public function testExhaustionKeepsOneLetterAndReturnsError()
	{
		global $db;

		// Narrow width so a family can be fully exhausted: prefix '4', width 2 => targetlen 3.
		$model = $this->makeModel('4', 2, 3);

		// With only the bare code used, the counter keeps the first letter (never purely numeric).
		$model->usedcodes = array('4AB');
		$this->assertSame('4A0', $model->assign('AB'), 'The last depth did not keep the first root letter');

		// Fill the bare code and the whole single-letter family (4A0..4A9): now exhausted.
		$seed = array('4AB');
		for ($i = 0; $i <= 9; $i++) {
			$seed[] = '4A'.$i;
		}
		$model->usedcodes = $seed;

		$soc = new stdClass();
		$soc->name = 'AB';
		$this->assertSame(-1, $model->get_code($db, $soc, 'customer'), 'Exhaustion must return -1');
		$this->assertNotEmpty($model->error, 'Exhaustion must set a (translated) error message');
	}

	/**
	 * Supplier codes use the supplier prefix and width.
	 *
	 * @return void
	 */
	public function testSupplierUsesItsOwnPrefix()
	{
		$model = $this->makeModel('411', 6, 9, '401', 6);
		$code = $model->assign('MARTIN', 'supplier');
		$this->assertSame('401MARTIN', $code, 'Supplier code does not use the supplier prefix');
		$this->assertSame(9, strlen($code));
	}

	/**
	 * When uniqueness is disabled, a bare fixed-length code is returned without any lookup.
	 *
	 * @return void
	 */
	public function testUniqueCodeDisabledReturnsBarePadded()
	{
		global $conf, $db;
		$model = $this->makeModel('411', 6, 9);
		$conf->global->COMPANY_DIGITARIA_UNIQUE_CODE = '0';

		$soc = new stdClass();
		$soc->name = 'EDF';
		$this->assertSame(0, $model->get_code($db, $soc, 'customer'));
		$this->assertSame('411EDF000', $model->code, 'Bare code is not padded to the fixed length');
	}

	/**
	 * Option A: the fixed length is "prefix length + width", never forced up to AACCOUNT.
	 * With prefix 411 + width 5 = 8 while AACCOUNT = 9, codes stay at 8 (existing long codes
	 * are not re-lengthened). Uniformity still makes the ledger padding collision-free.
	 *
	 * @return void
	 */
	public function testLengthFollowsPrefixPlusWidthNotAaccount()
	{
		$model = $this->makeModel('411', 5, 9);

		$first = $model->assign('MARTIN');   // root = MARTI (5)
		$second = $model->assign('MARTIN');
		$this->assertSame('411MARTI', $first, 'Long root bare code should be prefix+width (8 chars)');
		$this->assertSame(8, strlen($first), 'Code was forced to AACCOUNT length instead of prefix+width');
		$this->assertSame(8, strlen($second));

		// Same length => ledger padding stays bijective (no collision), even if it adds a zero.
		$this->assertNotSame(length_accounta($first), length_accounta($second));
	}

	/**
	 * No silent failure: an SQL error while scanning the family must NOT be mistaken for an
	 * exhausted family. get_code() returns -1 and keeps the real SQL error (not the exhaustion one).
	 *
	 * @return void
	 */
	public function testSqlErrorIsNotConfusedWithExhaustion()
	{
		global $db;
		$model = $this->makeModel('411', 6, 9);
		$model->simulateSqlError = true;

		$soc = new stdClass();
		$soc->name = 'EDF'; // short root -> findNextFreeCounter() path
		$this->assertSame(-1, $model->get_code($db, $soc, 'customer'));
		$this->assertSame('SQL error simulated', $model->error, 'SQL error was overwritten by the exhaustion message');
	}

	/**
	 * No silent failure on the long-root bare check: a -2 (SQL error) from
	 * checkIfAccountancyCodeIsAlreadyUsed() must be propagated, not treated as "not available".
	 *
	 * @return void
	 */
	public function testSqlErrorOnBareCheckIsPropagated()
	{
		global $db;
		$model = $this->makeModel('411', 6, 9);
		$model->simulateCheckError = true; // only the bare check errors; the family scan would work

		$soc = new stdClass();
		$soc->name = 'MARTIN'; // long root -> bare check path
		// Without propagation, the -2 would be read as "not available" and a code would be produced.
		$this->assertSame(-1, $model->get_code($db, $soc, 'customer'));
		$this->assertSame('SQL error simulated', $model->error, 'Bare-check SQL error was not propagated');
	}

	/**
	 * A name emptied by the cleaning rules has no letter to keep: a purely numeric fixed-length
	 * code is produced, and the codes stay unique and fixed-length.
	 *
	 * @return void
	 */
	public function testEmptyRootFallsBackToNumericFixedLength()
	{
		$model = $this->makeModel('411', 6, 9); // REMOVE_SPECIAL = 1 => '!!!' becomes ''
		$a = $model->assign('!!!');
		$b = $model->assign('!!!');
		$this->assertSame('411000000', $a, 'Empty root should give a numeric fixed-length code');
		$this->assertSame('411000001', $b);
		$this->assertSame(9, strlen($a));
		$this->assertNotSame($a, $b);
	}

	/**
	 * A single-letter root keeps its letter and fills the rest with a zero-padded counter.
	 *
	 * @return void
	 */
	public function testSingleLetterRootKeepsItsLetter()
	{
		$model = $this->makeModel('411', 6, 9);
		$a = $model->assign('A');
		$b = $model->assign('A');
		$this->assertSame('411A00000', $a, 'Single-letter root lost its letter or wrong padding');
		$this->assertSame('411A00001', $b);
		$this->assertSame(9, strlen($a));
	}

	/**
	 * Characterization: digits are kept by the cleaning (COMPANY_DIGITARIA_REMOVE_SPECIAL keeps
	 * 0-9), so a purely numeric name is NOT emptied: its root is numeric and the code is numeric
	 * too. This is the historical behavior (not the empty-root fallback). Uniqueness still holds:
	 * a homonym is bumped through the family scan, which reads the digit suffix as a taken counter.
	 *
	 * @return void
	 */
	public function testNumericNameKeepsItsDigitsAndStaysUnique()
	{
		global $db;
		$model = $this->makeModel('411', 6, 9);

		$first = $model->assign('123456'); // long root (fills the width) -> bare code
		$this->assertSame('411123456', $first, 'Numeric name should keep its digits in the root');
		$this->assertSame(9, strlen($first));

		// Second homonym: the bare code is taken, the family scan sees '6' as counter 6 and fills 0.
		$second = $model->assign('123456');
		$this->assertSame('411123450', $second, 'Second numeric homonym must get a distinct code');
		$this->assertNotSame($first, $second, 'Numeric homonyms must not collide');

		// A third homonym still succeeds (return 0): the family scan keeps finding free counters.
		$soc = new stdClass();
		$soc->name = '123456';
		$this->assertSame(0, $model->get_code($db, $soc, 'customer'));
	}
}
