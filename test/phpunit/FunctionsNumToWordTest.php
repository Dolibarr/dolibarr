<?php
/* Copyright (C) 2026 Dolibarr contributors
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
 *      \file       test/phpunit/FunctionsNumToWordTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the number-to-words conversion (functionsnumtoword.lib.php)
 *      \remarks    To run this script as CLI:  phpunit FunctionsNumToWordTest.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver

require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functionsnumtoword.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class to test the number-to-words conversion functions, focused on the French rules
 * (et, hyphens, agreement of cent/vingt, elision of "un" before cent/mille, centimes).
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FunctionsNumToWordTest extends CommonClassTest
{
	/**
	 * Set up the French language before each test.
	 *
	 * @return void
	 */
	protected function setUp(): void
	{
		global $conf, $user, $langs, $db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$langs->setDefaultLang('fr_FR');
		$langs->load('main');

		// Traditional spelling (pre-1990) is the default
		unset($conf->global->CONVERT_TO_WORD_FR);
	}

	/**
	 * Data provider: integer => expected French words (traditional, pre-1990 spelling).
	 *
	 * @return array<int, array{0:int, 1:string}>
	 */
	public function providerFrenchIntegersTraditional()
	{
		$map = array(
			0 => 'zéro', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre', 5 => 'cinq',
			6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf', 10 => 'dix', 11 => 'onze',
			12 => 'douze', 13 => 'treize', 14 => 'quatorze', 15 => 'quinze', 16 => 'seize',
			17 => 'dix-sept', 18 => 'dix-huit', 19 => 'dix-neuf', 20 => 'vingt',
			21 => 'vingt et un', 22 => 'vingt-deux', 23 => 'vingt-trois', 30 => 'trente',
			31 => 'trente et un', 32 => 'trente-deux', 40 => 'quarante', 41 => 'quarante et un',
			50 => 'cinquante', 51 => 'cinquante et un', 60 => 'soixante', 61 => 'soixante et un',
			62 => 'soixante-deux', 69 => 'soixante-neuf', 70 => 'soixante-dix',
			71 => 'soixante et onze', 72 => 'soixante-douze', 73 => 'soixante-treize',
			74 => 'soixante-quatorze', 75 => 'soixante-quinze', 76 => 'soixante-seize',
			77 => 'soixante-dix-sept', 78 => 'soixante-dix-huit', 79 => 'soixante-dix-neuf',
			80 => 'quatre-vingts', 81 => 'quatre-vingt-un', 82 => 'quatre-vingt-deux',
			89 => 'quatre-vingt-neuf', 90 => 'quatre-vingt-dix', 91 => 'quatre-vingt-onze',
			92 => 'quatre-vingt-douze', 95 => 'quatre-vingt-quinze', 97 => 'quatre-vingt-dix-sept',
			99 => 'quatre-vingt-dix-neuf', 100 => 'cent', 101 => 'cent un', 102 => 'cent deux',
			110 => 'cent dix', 111 => 'cent onze', 120 => 'cent vingt', 121 => 'cent vingt et un',
			180 => 'cent quatre-vingts', 181 => 'cent quatre-vingt-un', 199 => 'cent quatre-vingt-dix-neuf',
			200 => 'deux cents', 201 => 'deux cent un', 202 => 'deux cent deux',
			280 => 'deux cent quatre-vingts', 300 => 'trois cents', 360 => 'trois cent soixante',
			500 => 'cinq cents', 555 => 'cinq cent cinquante-cinq', 999 => 'neuf cent quatre-vingt-dix-neuf',
			1000 => 'mille', 1001 => 'mille un', 1100 => 'mille cent',
			1199 => 'mille cent quatre-vingt-dix-neuf', 1200 => 'mille deux cents', 1234 => 'mille deux cent trente-quatre',
			1500 => 'mille cinq cents', 1980 => 'mille neuf cent quatre-vingts', 1990 => 'mille neuf cent quatre-vingt-dix',
			2000 => 'deux mille', 2001 => 'deux mille un', 2020 => 'deux mille vingt',
			10000 => 'dix mille', 21000 => 'vingt et un mille', 80000 => 'quatre-vingt mille',
			100000 => 'cent mille', 200000 => 'deux cent mille', 300000 => 'trois cent mille',
			143360 => 'cent quarante-trois mille trois cent soixante',
			1000000 => 'un million', 1000001 => 'un million un', 2000000 => 'deux millions',
			1234567 => 'un million deux cent trente-quatre mille cinq cent soixante-sept',
			71000000 => 'soixante et onze millions', 80000000 => 'quatre-vingts millions',
			100000000 => 'cent millions', 200000000 => 'deux cents millions',
			123456789 => 'cent vingt-trois millions quatre cent cinquante-six mille sept cent quatre-vingt-neuf',
			999999999 => 'neuf cent quatre-vingt-dix-neuf millions neuf cent quatre-vingt-dix-neuf mille neuf cent quatre-vingt-dix-neuf',
			1000000000 => 'un milliard', 2000000000 => 'deux milliards',
			1000000000000 => 'un billion',
		);
		$data = array();
		foreach ($map as $n => $expected) {
			$data[(string) $n] = array($n, $expected);
		}
		return $data;
	}

	/**
	 * Test dolConvertIntToFrenchWords() with the traditional (pre-1990) spelling.
	 *
	 * @param	int		$number		Integer to convert
	 * @param	string	$expected	Expected French words
	 * @return	void
	 * @dataProvider providerFrenchIntegersTraditional
	 */
	public function testDolConvertIntToFrenchWordsTraditional($number, $expected)
	{
		$this->assertSame($expected, dolConvertIntToFrenchWords($number, false), "Conversion failed for $number");
	}

	/**
	 * Test dolConvertIntToFrenchWords() with the 1990 rectified spelling (hyphens everywhere).
	 *
	 * @return	void
	 */
	public function testDolConvertIntToFrenchWordsReform()
	{
		$map = array(
			21 => 'vingt-et-un',
			71 => 'soixante-et-onze',
			80 => 'quatre-vingts',
			81 => 'quatre-vingt-un',
			91 => 'quatre-vingt-onze',
			143360 => 'cent-quarante-trois-mille-trois-cent-soixante',
			200000000 => 'deux-cents millions',
			1234567 => 'un million deux-cent-trente-quatre-mille-cinq-cent-soixante-sept',
		);
		foreach ($map as $n => $expected) {
			$this->assertSame($expected, dolConvertIntToFrenchWords($n, true), "Reform conversion failed for $n");
		}
	}

	/**
	 * Test dol_convertToWord() for French amounts (currency empty, with centimes).
	 *
	 * @return	void
	 */
	public function testDolConvertToWordFrenchAmounts()
	{
		global $langs;

		$map = array(
			'0.70' => 'soixante-dix centimes',
			'0.07' => 'sept centimes',
			'0.05' => 'cinq centimes',
			'0.01' => 'un centime',
			'1.5' => 'un et cinquante centimes',
			'2.01' => 'deux et un centime',
			'100.99' => 'cent et quatre-vingt-dix-neuf centimes',
			'143360' => 'cent quarante-trois mille trois cent soixante',
			'143360.70' => 'cent quarante-trois mille trois cent soixante et soixante-dix centimes',
		);
		foreach ($map as $num => $expected) {
			$this->assertSame($expected, dol_convertToWord((float) $num, $langs, '', true), "Amount conversion failed for $num");
		}
	}

	/**
	 * Test dol_convertToWord() for French amounts with a currency word and negative values.
	 *
	 * @return	void
	 */
	public function testDolConvertToWordFrenchCurrencyAndNegative()
	{
		global $langs;

		$this->assertSame(
			'cent quarante-trois mille trois cent soixante dirhams et soixante-dix centimes',
			dol_convertToWord(143360.70, $langs, 'dirhams', true)
		);
		$this->assertSame('deux millions dirhams', dol_convertToWord(2000000, $langs, 'dirhams', true));
		$this->assertSame('moins cinq', dol_convertToWord(-5, $langs, '', true));
		$this->assertSame(
			'moins mille deux cent trente-quatre et cinquante-six centimes',
			dol_convertToWord(-1234.56, $langs, '', true)
		);
	}

	/**
	 * Test the 1990 rectified spelling on full amounts (driven by CONVERT_TO_WORD_FR).
	 *
	 * @return	void
	 */
	public function testDolConvertToWordFrenchReformConstant()
	{
		global $conf, $langs;

		$conf->global->CONVERT_TO_WORD_FR = 'REFORME1990';
		$this->assertSame(
			'cent-quarante-trois-mille-trois-cent-soixante et soixante-dix centimes',
			dol_convertToWord(143360.70, $langs, '', true)
		);
		unset($conf->global->CONVERT_TO_WORD_FR);
	}

	/**
	 * Non-regression test for the cents extraction bug (e.g. 0.07 must be 7 cents, not 70),
	 * checked on the generic (non-French) path in English.
	 *
	 * @return	void
	 */
	public function testDolConvertToWordCentsExtractionEnglish()
	{
		global $conf;

		// Use a fresh Translate object: the shared $langs caches translations, so reusing it
		// after the French tests would keep French values (seven => sept) on the English path.
		$langsen = new Translate('', $conf);
		$langsen->setDefaultLang('en_US');
		$langsen->load('main');

		// 0.07 used to wrongly yield "seventy"; it must be "seven"
		$this->assertSame('seven', trim(dol_convertToWord(0.07, $langsen, '', true)));
		$this->assertSame('five', trim(dol_convertToWord(0.05, $langsen, '', true)));
		$this->assertSame('seventy', trim(dol_convertToWord(0.70, $langsen, '', true)));
	}
}
