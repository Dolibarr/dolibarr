<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/LangTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}

if (empty($user->id)) {
	print "Load permissions for admin user nb 1".PHP_EOL;
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class LangTest extends CommonClassTest
{
	/**
	 * Data provider for testLang
	 *
	 * @return array<string,array{0:string}>
	 */
	public function langDataProvider(): array
	{
		$langCodes = [];
		$filesarray = scandir(DOL_DOCUMENT_ROOT.'/langs');
		foreach ($filesarray as $key => $code) {
			if (! preg_match('/^[a-z]+_[A-Z]+$/', $code)) {
				continue;
			}
			if (in_array($code, array('mk_MK'))) {	// We exclude some language not yet ready
				continue;
			}
			$langCodes[$code] = [$code];
		}
		return $langCodes;
	}


	/**
	 * testLang
	 * @dataProvider langDataProvider
	 *
	 * @param $code Language code for which to verify translations
	 *
	 * @return void
	 */
	public function testLang($code): void
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		include_once DOL_DOCUMENT_ROOT.'/core/class/translate.class.php';


		$prefix = __METHOD__."($code) ";
		$tmplangs = new Translate('', $conf);
		$langcode = $code;
		$tmplangs->setDefaultLang($langcode);
		$tmplangs->load("main");

		print PHP_EOL.$prefix."Check language file".PHP_EOL;
		$result = $tmplangs->transnoentitiesnoconv("FONTFORPDF");
		print $prefix."FONTFORPDF=".$result.PHP_EOL;
		$this->assertTrue(in_array($result, array('msungstdlight', 'stsongstdlight', 'helvetica', 'DejaVuSans', 'cid0jp', 'cid0kr', 'freemono', 'freeserif')), 'Error bad value '.$result.' for FONTFORPDF in main.lang file '.$code);

		$result = $tmplangs->transnoentitiesnoconv("DIRECTION");
		print $prefix."DIRECTION=".$result.PHP_EOL;
		$this->assertTrue(in_array($result, array('rtl', 'ltr')), 'Error bad value for DIRECTION in main.lang file '.$code);

		$result = $tmplangs->transnoentitiesnoconv("SeparatorDecimal");
		print $prefix."SeparatorDecimal=".$result.PHP_EOL;
		$this->assertTrue(in_array($result, array('.',',','/','。',' ','','None')), 'Error on decimal separator for lang code '.$code);	// Note that ، that is coma for RTL languages is not supported

		$result = $tmplangs->transnoentitiesnoconv("SeparatorThousand");
		print $prefix."SeparatorThousand=".$result.PHP_EOL;
		$this->assertTrue(in_array($result, array('.',',','/',' ','','\'','None','Space')), 'Error on thousand separator for lang code '.$code);	// Note that ، that is coma for RTL languages is not supported

		// Test java string contains only d,M,y,/,-,. and not m,...
		$result = $tmplangs->transnoentitiesnoconv("FormatDateShortJava");
		print $prefix."FormatDateShortJava=".$result.PHP_EOL;
		$tmpvar = preg_match('/^[dMy\/\-\.]+$/', $result);
		$this->assertEquals(1, $tmpvar, 'FormatDateShortJava KO for lang code '.$code.'. Does not match /[dMy\/\-\.]+/');

		$result = $tmplangs->trans("FormatDateShortJavaInput");
		print $prefix."FormatDateShortJavaInput=".$result.PHP_EOL;
		$tmpvar = preg_match('/^[dMy\/\-\.]+$/', $result);
		$this->assertEquals(1, $tmpvar, 'FormatDateShortJavaInput KO for lang code '.$code.'. Does not match /^[dMy\/\-\.]+$/');

		unset($tmplangs);

		print $prefix."Check some syntax rules in the language file".PHP_EOL;
		$filesarray2 = scandir(DOL_DOCUMENT_ROOT.'/langs/'.$code);
		foreach ($filesarray2 as $key => $file) {
			if (! preg_match('/\.lang$/', $file)) {
				continue;
			}

			//print 'Check lang file '.$file.PHP_EOL;
			$filecontent = file_get_contents(DOL_DOCUMENT_ROOT.'/langs/'.$code.'/'.$file);

			$result = preg_match('/=--$/m', $filecontent);	// A special % char we don't want. We want the common one.
			//print $prefix."Result for checking we don't have bad percent char = ".$result.PHP_EOL;
			$this->assertTrue($result == 0, 'Found a translation KEY=-- in file '.$code.'/'.$file.'. We probably want Key=- instead.');

			$result = strpos($filecontent, '％');	// A special % char we don't want. We want the common one.
			//print $prefix."Result for checking we don't have bad percent char = ".$result.PHP_EOL;
			$this->assertTrue($result === false, 'Found a bad percent char ％ instead of % in file '.$code.'/'.$file);

			$result = preg_match('/%n/m', $filecontent);	// A sequence of char we don't want
			//print $prefix."Result for checking we don't have bad percent char = ".$result.PHP_EOL;
			$this->assertTrue($result == 0, 'Found a sequence %n in the translation file '.$code.'/'.$file.'. We probably want %s');

			$result = preg_match('/<<<<</m', $filecontent);	// A sequence of char we don't want
			//print $prefix."Result for checking we don't have bad percent char = ".$result.PHP_EOL;
			$this->assertTrue($result == 0, 'Found a sequence <<<<< in the translation file '.$code.'/'.$file.'. Probably a bad merge of code were done.');

			$reg = array();
			$result = preg_match('/(.*)\'notranslate\'/im', $filecontent, $reg);	// A sequence of char we don't want
			//print $prefix."Result for checking we don't have bad percent char = ".$result.PHP_EOL;
			$this->assertTrue($result == 0, 'Found a sequence tag \'notranslate\' in the translation file '.$code.'/'.$file.' in line '.empty($reg[1]) ? '' : $reg[1]);

			if (!in_array($code, array('ar_SA'))) {
				$reg = array();
				$result = preg_match('/(.*)<([^a-z\/\s,=\(]1)/im', $filecontent, $reg);	// A sequence of char we don't want
				//print $prefix."Result for checking we don't have bad percent char = ".$result.PHP_EOL;
				$this->assertTrue($result == 0, 'Found a sequence tag <'.(empty($reg[2]) ? '' : $reg[2]).' in the translation file '.$code.'/'.$file.' in line '.empty($reg[1]) ? '' : $reg[1]);
			}
		}
	}


	/**
	 * Data provider for testTrans
	 *
	 * @return array<string,string[]>
	 */
	public function transDataProvider(): array
	{
		return [
			'en_US-1' => [
				'Result of lang->trans must have original translation string with its original HTML tag, but inserted values must be fully encoded.', // Description
				"en_US",  // Langcode
				"main",   // Dict
				"Search criteria '<b>&lt;input autofocus onfocus='alert(1337)' &lt;--!</b>' into fields ",  // Expected
				"FilterOnInto", "<input autofocus onfocus='alert(1337)' <--!"],
			'fr-FR-1' => [
				'Result of lang->trans must have original translation string with its original HTML tag, but inserted values must be fully encoded.', // Description
				"fr_FR",  // Langcode
				"main",   // Dict
				"Rechercher le crit&egrave;re '<b>&lt;input autofocus onfocus='alert(1337)' &lt;--!</b>' dans les champs ",  // Expected
				"FilterOnInto", "<input autofocus onfocus='alert(1337)' <--!"],
		];
	}


	/**
	 * testTrans
	 * @dataProvider transDataProvider
	 *
	 * @param string  $description Test description
	 * @param string  $langcode    Language code for translation
	 * @param string  $dict        Dictionary file for translation
	 * @param string  $expected    Expected translation result
	 * @param string  $key         Key for translation
	 * @param ?string $param1      Parameter 1 for translation
	 * @param ?string $param2      Parameter 2 for translation
	 *
	 * @return string
	 */
	public function testTrans($description, $langcode, $dict, $expected, $key, $param1 = null, $param2 = null)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$tmplangs = new Translate('', $conf);
		$tmplangs->setDefaultLang($langcode);
		$tmplangs->load($dict);

		$result = $tmplangs->trans($key, $param1, $param2);
		$prefix = __METHOD__."({$this->dataName()}) ";
		print $prefix."result trans $key = ".$result.PHP_EOL;
		$this->assertEquals($expected, $result, $description);
	}
}
