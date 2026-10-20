<?php
/* Copyright (C) 2026		Nick Fragoulis
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
 */

/**
 *	\file		test/phpunit/CreditorRefLibTest.php
 *	\ingroup	test
 *	\brief		PHPUnit test for functions_creditorref.lib.php, functions_gr.lib.php
 *				and paymentref.lib.php
 */

global $conf, $user, $langs, $db;
//require_once 'PHPUnit/Autoload.php';
if (!defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions_creditorref.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions_gr.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/paymentref.lib.php';

if (empty($user->id) && is_object($db)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;



/**
 * Class for PHPUnit tests of the structured payment reference libraries
 */
class CreditorRefLibTest extends CommonClassTest
{
	/**
	 * Check digits follow the published ISO 11649 example
	 *
	 * @return void
	 */
	public function testCheckDigitsIso11649Example()
	{
		$this->assertSame('18', dolCreditorRefCheckDigits('539007547034'));
	}

	/**
	 * Leading zeros must not change the check digits
	 *
	 * @return void
	 */
	public function testCheckDigitsIgnoreLeadingZeros()
	{
		$this->assertSame(
			dolCreditorRefCheckDigits('539007547034'),
			dolCreditorRefCheckDigits('000000000539007547034')
		);
	}

	/**
	 * Letters map to 10-35 for the mod 97 computation
	 *
	 * @return void
	 */
	public function testAlphaToDigits()
	{
		$this->assertSame('10', dolCreditorRefAlphaToDigits('A'));
		$this->assertSame('2715', dolCreditorRefAlphaToDigits('RF'));
		$this->assertSame('271500', dolCreditorRefAlphaToDigits('RF00'));
	}

	/**
	 * Plain ISO 11649 generation
	 *
	 * @return void
	 */
	public function testCreditorRefGenerate()
	{
		$this->assertSame('RF18539007547034', dolCreditorRefGenerate('539007547034', 1));
		$this->assertSame('', dolCreditorRefGenerate('539007547034', 0), 'Draft must give an empty string');
		$this->assertSame('', dolCreditorRefGenerate('1234567890123456789012', 1), 'Payload over 21 chars must be refused');
		$this->assertSame(25, dol_strlen(dolCreditorRefGenerate('123456789012345678901', 1)), 'A 21 char payload gives a 25 char reference');
	}

	/**
	 * Validation of a complete reference
	 *
	 * @return void
	 */
	public function testCreditorRefIsValid()
	{
		$this->assertTrue(dolCreditorRefIsValid('RF18539007547034'));
		$this->assertTrue(dolCreditorRefIsValid('RF18 5390 0754 7034'), 'Spaces are accepted');
		$this->assertFalse(dolCreditorRefIsValid('RF99539007547034'), 'Wrong check digits');
		$this->assertFalse(dolCreditorRefIsValid('539007547034'), 'Missing RF prefix');
		$this->assertFalse(dolCreditorRefIsValid(''));
	}

	/**
	 * Scheme detection replaces the need for a second database column
	 *
	 * @return void
	 */
	public function testDetectScheme()
	{
		$this->assertSame('SCOR', dolPayRefDetectScheme('RF18539007547034'));
		$this->assertSame('SCOR', dolPayRefDetectScheme('RF18 5390 0754 7034'));
		$this->assertSame('BBA', dolPayRefDetectScheme('+++000/0000/00101+++'));
		$this->assertSame('FI', dolPayRefDetectScheme('20240015'));
		$this->assertSame('', dolPayRefDetectScheme(''));
	}

	/**
	 * Format and strip are symmetric
	 *
	 * @return void
	 */
	public function testFormatAndStrip()
	{
		$this->assertSame('RF18 5390 0754 7034', dolPayRefFormat('RF18539007547034'));
		$this->assertSame('RF18539007547034', dolPayRefStrip('RF18 5390 0754 7034'));
	}

	/**
	 * Greek DIAS reference, checked against a real reference in use
	 *
	 * @return void
	 */
	public function testGreekDiasReference()
	{
		$this->assertSame(
			'RF20921928000000003015873',
			dolGRCalculateCreditorReference('8000000003015873', 1, '92192', '8')
		);
		// Same reference from the debt code alone, the 8 is the code usage digit
		$this->assertSame(
			'RF20921928000000003015873',
			dolGRCalculateCreditorReference('3015873', 1, '92192', '8')
		);
	}

	/**
	 * A greek reference is always exactly 25 characters
	 *
	 * @return void
	 */
	public function testGreekReferenceIsAlways25Chars()
	{
		foreach (array('0', '1', '3015873', '9999999999999999') as $id) {
			$ref = dolGRCalculateCreditorReference($id, 1, '92192');
			$this->assertSame(25, dol_strlen($ref), 'Wrong length for payment id '.$id);
			$this->assertSame('92192', substr($ref, 4, 5), 'DIAS code must be kept');
		}
	}

	/**
	 * A greek reference cannot be produced without a valid DIAS code
	 *
	 * @return void
	 */
	public function testGreekReferenceNeedsDiasCode()
	{
		global $conf;

		// The function falls back to the configured code when none is passed,
		// so the configured one has to be cleared for this test to mean anything
		$savedcode = getDolGlobalString('MAIN_INFO_SOCIETE_DIAS_CODE');
		$conf->global->MAIN_INFO_SOCIETE_DIAS_CODE = '';

		$this->assertSame('', dolGRCalculateCreditorReference('1', 1, ''));
		$this->assertSame('', dolGRCalculateCreditorReference('1', 1, '1234'), 'Four digits is not enough');
		$this->assertSame('', dolGRCalculateCreditorReference('1', 1, '123456'), 'Six digits is too many');
		$this->assertSame('', dolGRCalculateCreditorReference('1', 1, '12345'), 'A DIAS code always starts with 9');
		$this->assertSame('', dolGRCalculateCreditorReference('1', 0, '92192'), 'Draft gives an empty string');

		$conf->global->MAIN_INFO_SOCIETE_DIAS_CODE = $savedcode;
	}

	/**
	 * The DIAS code can be read back from a reference
	 *
	 * @return void
	 */
	public function testGreekExtractDiasCode()
	{
		$this->assertSame('92192', dolGRGetDiasCodeFromReference('RF20921928000000003015873'));
		$this->assertSame('', dolGRGetDiasCodeFromReference('RF18539007547034'), 'Not 25 chars, not a greek reference');
	}

	/**
	 * Two customers never share a reference
	 *
	 * @return void
	 */
	public function testGreekReferencesAreDistinct()
	{
		$this->assertNotSame(
			dolGRCalculateCreditorReference('5001', 1, '92192'),
			dolGRCalculateCreditorReference('5002', 1, '92192')
		);
	}

	/**
	 * Digit 10 is the DIAS code usage, never part of the debt code. A 0 to 7 there
	 * would ask the paying bank for an amount check and most payments would be refused.
	 *
	 * @return void
	 */
	public function testGreekCodeUsageDigit()
	{
		global $conf;

		$savedusage = getDolGlobalString('MAIN_INFO_SOCIETE_DIAS_CODE_USAGE');

		$conf->global->MAIN_INFO_SOCIETE_DIAS_CODE_USAGE = '';
		foreach (array('1', '123', '2026000045', '999999999999999') as $id) {
			$ref = dolGRCalculateCreditorReference($id, 1, '92192');
			$this->assertSame('8', substr($ref, 9, 1), 'Default code usage must be 8 for debt code '.$id);
			$this->assertSame(str_pad($id, 15, '0', STR_PAD_LEFT), substr($ref, 10, 15), 'Debt code must fill the last 15 digits');
			$this->assertTrue(dolCreditorRefIsValid($ref));
		}
		$this->assertSame('RF75921928000000000000123', dolGRCalculateCreditorReference('123', 1, '92192'));

		$conf->global->MAIN_INFO_SOCIETE_DIAS_CODE_USAGE = '9';
		$ref = dolGRCalculateCreditorReference('123', 1, '92192');
		$this->assertSame('9', substr($ref, 9, 1), 'Code usage 9 from the setup');
		$this->assertTrue(dolCreditorRefIsValid($ref));

		$this->assertSame('8', substr(dolGRCalculateCreditorReference('123', 1, '92192', '8'), 9, 1), 'Parameter wins over the setup');
		$this->assertSame('8', substr(dolGRCalculateCreditorReference('123', 1, '92192', '3'), 9, 1), 'An amount checked usage is never produced');

		$conf->global->MAIN_INFO_SOCIETE_DIAS_CODE_USAGE = $savedusage;
	}

	/**
	 * A debt code longer than 15 digits keeps its last 15, where the counter changes
	 *
	 * @return void
	 */
	public function testGreekDebtCodeTooLong()
	{
		$ref = dolGRCalculateCreditorReference('1234567890123456789', 1, '92192', '8');
		$this->assertSame(25, dol_strlen($ref));
		$this->assertSame('567890123456789', substr($ref, 10, 15));
		$this->assertNotSame(
			dolGRCalculateCreditorReference('2026100000000000001', 1, '92192', '8'),
			dolGRCalculateCreditorReference('2026100000000000002', 1, '92192', '8')
		);
	}

	/**
	 * A DIAS reference with 0 to 7 at digit 10 checks a fixed amount
	 *
	 * @return void
	 */
	public function testGreekAmountCheckedReference()
	{
		$this->assertFalse(dolGRIsAmountCheckedReference(dolGRCalculateCreditorReference('123', 1, '92192', '8')));
		$this->assertFalse(dolGRIsAmountCheckedReference(dolGRCalculateCreditorReference('123', 1, '92192', '9')));

		// DIAS code 92192, amount check digit 5, same debt code, check digits recomputed
		$payload = '921925000000000000123';
		$amountchecked = 'RF'.dolCreditorRefCheckDigits($payload).$payload;
		$this->assertTrue(dolCreditorRefIsValid($amountchecked));
		$this->assertTrue(dolGRIsAmountCheckedReference($amountchecked));
		$this->assertTrue(dolGRIsAmountCheckedReference(dolPayRefFormat($amountchecked)), 'Spaces do not matter');

		$this->assertFalse(dolGRIsAmountCheckedReference('RF18539007547034'), 'Not a DIAS reference');
		$this->assertFalse(dolGRIsAmountCheckedReference('RF00921925000000000000123'), 'Invalid check digits');
		$this->assertFalse(dolGRIsAmountCheckedReference(''));

		// Never reused as a complete reference by a greek company
		$this->assertSame('', dolPayRefBuild($amountchecked, 1, 'GR'));
		$this->assertSame(dolPayRefStrip(dolGRCalculateCreditorReference('123', 1, '92192', '8')), dolPayRefBuild(dolGRCalculateCreditorReference('123', 1, '92192', '8'), 1, 'GR'));
	}

	/**
	 * A DIAS creditor code is 5 digits starting with 9
	 *
	 * @return void
	 */
	public function testGreekDiasCodeFormat()
	{
		$this->assertTrue(dolGRIsValidDiasCode('92192'));
		$this->assertTrue(dolGRIsValidDiasCode('90001'));
		$this->assertFalse(dolGRIsValidDiasCode('12345'));
		$this->assertFalse(dolGRIsValidDiasCode('9219'));
		$this->assertFalse(dolGRIsValidDiasCode('921920'));
		$this->assertFalse(dolGRIsValidDiasCode(''));
	}

	/**
	 * The scheme follows the country of the company
	 *
	 * @return void
	 */
	public function testSchemeForCountry()
	{
		$this->assertSame('BE', dolPayRefGetSchemeForCountry('BE'));
		$this->assertSame('FI', dolPayRefGetSchemeForCountry('FI'));
		$this->assertSame('GR', dolPayRefGetSchemeForCountry('GR'));
		$this->assertSame('GR', dolPayRefGetSchemeForCountry('gr'), 'Case must not matter');
		$this->assertSame('RF', dolPayRefGetSchemeForCountry('DE'));
		$this->assertSame('RF', dolPayRefGetSchemeForCountry('CH'));
		$this->assertSame('RF', dolPayRefGetSchemeForCountry(''));
	}

	/**
	 * Every scheme produces something that fits llx_facture.payment_reference
	 *
	 * @return void
	 */
	public function testEveryReferenceFitsColumn()
	{
		global $conf;
		$conf->global->MAIN_INFO_SOCIETE_DIAS_CODE = '92192';

		foreach (array('BE', 'FI', 'GR', 'DE') as $country) {
			$ref = dolPayRefBuild('20240001', 1, $country);
			$this->assertNotSame('', $ref, 'No reference for '.$country);
			$this->assertLessThanOrEqual(25, dol_strlen($ref), 'Reference too long for column, country '.$country);
		}
	}
}
