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
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions_creditorref.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions_gr.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/paymentref.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
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
			dolGRCalculateCreditorReference('8000000003015873', 1, '92192')
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

	/**
	 * A finnish company can choose the national reference instead of the RF form
	 *
	 * @return void
	 */
	public function testFinnishReferenceFormat()
	{
		global $conf;
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions_fi.lib.php';

		$conf->global->INVOICE_PAYMENT_REF_FI_FORMAT = '';
		$ref = dolPayRefBuild('2024001', 1, 'FI');
		$this->assertSame('SCOR', dolPayRefDetectScheme($ref), 'RF is the default');
		$this->assertTrue(dolCreditorRefIsValid($ref));

		$conf->global->INVOICE_PAYMENT_REF_FI_FORMAT = 'national';
		$ref = dolPayRefBuild('2024001', 1, 'FI');
		$this->assertSame('20240015', $ref, 'Base followed by the 7-3-1 check digit');
		$this->assertTrue(dolFIIsValidReference($ref));

		// The national form travels inside the RF one
		$conf->global->INVOICE_PAYMENT_REF_FI_FORMAT = 'rf';
		$this->assertSame($ref, dol_substr(dolPayRefBuild('2024001', 1, 'FI'), 4));

		// A base without any digit must not give the same 0000 to everybody
		$this->assertSame('', dolPayRefBuild('ABCDEF', 1, 'FI'));
		$conf->global->INVOICE_PAYMENT_REF_FI_FORMAT = 'rf';
		$this->assertSame('', dolPayRefBuild('ABCDEF', 1, 'FI'));
		$conf->global->INVOICE_PAYMENT_REF_FI_FORMAT = 'national';

		// The setting only concerns finnish companies
		$conf->global->INVOICE_PAYMENT_REF_FI_FORMAT = 'national';
		$this->assertSame('SCOR', dolPayRefDetectScheme(dolPayRefBuild('2024001', 1, 'DE')));

		unset($conf->global->INVOICE_PAYMENT_REF_FI_FORMAT);
	}

	/**
	 * A finnish national reference is printed in groups of five from the right
	 *
	 * @return void
	 */
	public function testFinnishReferenceDisplay()
	{
		$this->assertSame('1 23456', dolPayRefFormatForDisplay('123456', 'FI'));
		$this->assertSame('12345 67890 12345 67890', dolPayRefFormatForDisplay('12345678901234567890', 'FI'));
		$this->assertSame('RF18 5390 0754 7034', dolPayRefFormatForDisplay('RF18539007547034', 'FI'), 'RF stays in groups of four');
	}
}
