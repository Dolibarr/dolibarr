<?php
/* Copyright (C) 2010-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/FunctionsLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';
require_once dirname(__FILE__).'/../../htdocs/product/class/product.class.php';
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

print "\n".$langs->trans("CurrentTimeZone").' : '.getServerTimeZoneString();
print "\n".$langs->trans("CurrentHour").' : '.dol_print_date(dol_now('gmt'), 'dayhour', 'tzserver');
print "\n";


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FunctionsLibTest extends CommonClassTest
{
	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		//$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		if (! function_exists('mb_substr')) {
			print "\n".__METHOD__." function mb_substr must be enabled.\n";
			die(1);
		}

		if ($conf->global->MAIN_MAX_DECIMALS_UNIT != 5) {
			print "\n".__METHOD__." bad setup for number of digits for unit amount. Must be 5 for this test.\n";
			die(1);
		}

		if ($conf->global->MAIN_MAX_DECIMALS_TOT != 2) {
			print "\n".__METHOD__." bad setup for number of digits for unit amount. Must be 2 for this test.\n";
			die(1);
		}

		print __METHOD__."\n";
	}


	/**
	 * testDolCheckFilters
	 *
	 * @return boolean
	 */
	public function testDolCheckFilters()
	{
		global $conf, $langs, $db;

		// A sql with global parenthesis at level 2
		$error = '';
		$parenthesislevel = 0;
		$sql = '(( ... (a:=:1) .éééé. (b:=:1) ... ))';
		$result = dolCheckFilters($sql, $error, $parenthesislevel);
		$this->assertEquals(2, $parenthesislevel);
		$this->assertTrue($result);

		// A sql with global parenthesis at level 2
		$error = '';
		$parenthesislevel = 0;
		$sql = '(((((a:=:1) ... ) .éééé.. (b:=:1) ..) ... ))';
		$result = dolCheckFilters($sql, $error, $parenthesislevel);
		$this->assertEquals(2, $parenthesislevel);
		$this->assertTrue($result);

		// A sql with global parenthesis at level 2
		$error = '';
		$parenthesislevel = 0;
		$sql = '((... (((a:=:1) ... ( .éééé.. (b:=:1) ..)))))';
		$result = dolCheckFilters($sql, $error, $parenthesislevel);
		$this->assertEquals(2, $parenthesislevel);
		$this->assertTrue($result);

		// A sql with global parenthesis at level 0
		$error = '';
		$parenthesislevel = 0;
		$sql = '(a:=:1) ... (b:=:1) éééé ...';
		$result = dolCheckFilters($sql, $error, $parenthesislevel);
		$this->assertEquals(0, $parenthesislevel);
		$this->assertTrue($result);

		// A sql with bad balance
		$error = '';
		$parenthesislevel = 0;
		$sql = '((((a:=:1) ... (b:=:1) éééé ..))';
		$result = dolCheckFilters($sql, $error, $parenthesislevel);
		$this->assertEquals(0, $parenthesislevel);
		$this->assertFalse($result);

		// A sql with bad balance
		$error = '';
		$parenthesislevel = 0;
		$sql = '(((a:=:1) ... (b:=:1) éééé ..)))';
		$result = dolCheckFilters($sql, $error, $parenthesislevel);
		$this->assertEquals(0, $parenthesislevel);
		$this->assertFalse($result);

		return true;
	}


	/**
	 * testDolForgeExplodeAnd
	 *
	 * @return boolean
	 */
	public function testDolForgeExplodeAnd()
	{
		$tmp = dolForgeExplodeAnd('');
		$this->assertEquals(0, count($tmp));

		$tmp = dolForgeExplodeAnd('(a:=:1)');
		$this->assertEquals('(a:=:1)', $tmp[0]);

		$tmp = dolForgeExplodeAnd('(a:=:1) AND (b:=:2)');
		$this->assertEquals('(a:=:1)', $tmp[0]);
		$this->assertEquals('(b:=:2)', $tmp[1]);

		$tmp = dolForgeExplodeAnd('(a:=:1) AND ((b:=:2) OR (c:=:3))');
		$this->assertEquals('(a:=:1)', $tmp[0]);
		$this->assertEquals('((b:=:2) OR (c:=:3))', $tmp[1]);

		$tmp = dolForgeExplodeAnd('(a:=:1) AND (b:=:2) OR (c:=:3)');
		$this->assertEquals('(a:=:1)', $tmp[0]);
		$this->assertEquals('(b:=:2) OR (c:=:3)', $tmp[1]);

		$tmp = dolForgeExplodeAnd('(a:=:1) OR (b:=:2) AND (c:=:3)');
		$this->assertEquals('(a:=:1) OR (b:=:2)', $tmp[0]);
		$this->assertEquals('(c:=:3)', $tmp[1]);

		$tmp = dolForgeExplodeAnd('(a:=:1) OR ((b:=:2) AND (c:=:3))');
		$this->assertEquals('(a:=:1) OR ((b:=:2) AND (c:=:3))', $tmp[0]);

		$tmp = dolForgeExplodeAnd('((y:=:1) AND (p:=:8))');
		$this->assertEquals('(y:=:1)', $tmp[0]);
		$this->assertEquals('(p:=:8)', $tmp[1]);

		return true;
	}

	/**
	 * testDolForgeCriteriaCallback
	 *
	 * @return boolean
	 */
	public function testDolForgeCriteriaCallback()
	{
		global $conf, $langs, $db;

		// Test using like
		$filter = "(lastname:like:'%aaa%') OR (firstname:like:'%bbb%')";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(" AND ((lastname LIKE '%aaa%') OR (firstname LIKE '%bbb%'))", $sql);

		// Test on NOW
		$filter = "(client:!=:8) AND (datefin:>=:'__NOW__')";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertStringContainsStringIgnoringCase(" AND ((client <> 8) AND (datefin >= '", $sql);

		// An attempt for SQL injection
		$filter = 'if(now()=sysdate()%2Csleep(6)%2C0)';
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals('Filter error - Bad syntax of the search string', $sql);

		// A real search string
		$filter = '(((statut:=:1) or (entity:in:__AAA__)) and (abc:<:2.0) and (abc:!=:1.23))';
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(' AND ((((statut = 1) or (entity IN (__AAA__))) and (abc < 2) and (abc <> 1.23)))', $sql);

		// A real search string
		$filter = "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.date_creation:<:'2016-01-01 12:30:00') or (t.nature:is:NULL)";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(" AND ((t.ref LIKE 'SO-%') or (t.date_creation < '20160101') or (t.date_creation < 0) or (t.nature IS NULL))", $sql);

		// A real search string
		$filter = "(t.fieldstring:=:'aaa ttt')";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(" AND ((t.fieldstring = 'aaa ttt'))", $sql);

		// Check that parenthesis are NOT allowed inside the last operand. Very important.
		$filter = "(t.fieldint:=:(1,2))";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals("Filter error - Bad syntax of the search string", $sql);

		// Check that ' is escaped into the last operand
		$filter = "(t.fieldstring:=:'aaa'ttt')";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);

		if ($db->type == 'mysqli') {
			$this->assertEquals(" AND ((t.fieldstring = 'aaa\'ttt'))", $sql);	// with mysql
		} else {
			$this->assertEquals(" AND ((t.fieldstring = 'aaa''ttt'))", $sql);	// with pgsql
		}

		$filter = "(t.fk_soc:IN:1,2)";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(" AND ((t.fk_soc IN (1,2)))", $sql);

		$filter = "(t.fk_soc:IN:'1','2=b')";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(" AND ((t.fk_soc IN ('1','2=b')))", $sql);

		$filter = "(t.fk_soc:IN:SELECT rowid FROM llx_societe WHERE fournisseur = 1)";
		$sql = forgeSQLFromUniversalSearchCriteria($filter);
		$this->assertEquals(" AND ((t.fk_soc IN (SELECT rowid FROM llx_societe WHERE fournisseur = 1)))", $sql);

		return true;
	}


	/**
	 * testDolClone
	 *
	 * @return void
	 */
	public function testDolClone()
	{
		global $db;

		$newproduct1 = new Product($db);

		print __METHOD__." this->savdb has type ".(is_resource($db->db) ? get_resource_type($db->db) : (is_object($db->db) ? 'object' : 'unknown'))."\n";
		print __METHOD__." newproduct1->db->db has type ".(is_resource($newproduct1->db->db) ? get_resource_type($newproduct1->db->db) : (is_object($newproduct1->db->db) ? 'object' : 'unknown'))."\n";
		$this->assertEquals($db->connected, 1, 'Savdb is connected');
		$this->assertNotNull($newproduct1->db->db, 'newproduct1->db is not null');

		$newproductcloned1 = dol_clone($newproduct1);

		print __METHOD__." this->savdb has type ".(is_resource($db->db) ? get_resource_type($db->db) : (is_object($db->db) ? 'object' : 'unknown'))."\n";
		print __METHOD__." newproduct1->db->db has type ".(is_resource($newproduct1->db->db) ? get_resource_type($newproduct1->db->db) : (is_object($newproduct1->db->db) ? 'object' : 'unknown'))."\n";
		$this->assertEquals($db->connected, 1, 'Savdb is connected');
		$this->assertNotNull($newproduct1->db->db, 'newproduct1->db is not null');

		//$newproductcloned2 = dol_clone($newproduct1, 2);
		//var_dump($newproductcloned2);
		//print __METHOD__." newproductcloned1->db must be null\n";
		//$this->assertNull($newproductcloned1->db, 'newproductcloned1->db is null');
	}

	/**
	 * testNum2Alpha
	 *
	 * @return void
	 */
	public function testNum2Alpha()
	{
		$result = num2Alpha(0);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'A', 'Check num2Alpha 0');

		$result = num2Alpha(5);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'F', 'Check num2Alpha 5');

		$result = num2Alpha(26);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'AA', 'Check num2Alpha 26');
	}

	/**
	 * testIsValidEmail
	 *
	 * @return void
	 */
	public function testIsValidEmail()
	{
		// Nb of line is same than entry text

		$input = "bidon@bademail";
		$result = isValidEmail($input);
		print __METHOD__." result=".$result."\n";
		$this->assertFalse($result, 'Check isValidEmail '.$input);

		$input = "test@yahoo.com";
		$result = isValidEmail($input);
		print __METHOD__." result=".$result."\n";
		$this->assertTrue($result, 'Check isValidEmail '.$input);

		$input = "The name of sender <test@yahoo.com>";
		$result = isValidEmail($input);
		print __METHOD__." result=".$result."\n";
		$this->assertFalse($result, 'Check isValidEmail '.$input);

		$input = "1234.abcdefg@domainame.com.br";
		$result = isValidEmail($input);
		print __METHOD__." result=".$result."\n";
		$this->assertTrue($result, 'Check isValidEmail '.$input);
	}

	/**
	 * testIsValidMXRecord
	 *
	 * @return void
	 */
	public function testIsValidMXRecord()
	{
		// Nb of line is same than entry text

		$input = "yahoo.com";
		$result = isValidMXRecord($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(1, $result);

		$input = "yhaoo.com";
		$result = isValidMXRecord($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result);

		$input = "dolibarr.fr";
		$result = isValidMXRecord($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(0, $result);
	}

	/**
	 * testDolGetFirstLineOfText
	 *
	 * @return void
	 */
	public function testDolGetFirstLineOfText()
	{
		// Nb of line is same than entry text

		$input = "aaaa";
		$result = dolGetFirstLineOfText($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa", $result);

		$input = "aaaa\nbbbbbbbbbbbb\n";
		$result = dolGetFirstLineOfText($input, 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa\nbbbbbbbbbbbb", $result);

		$input = "aaaa<br>bbbbbbbbbbbb<br>";
		$result = dolGetFirstLineOfText($input, 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb", $result);

		// Nb of line is lower

		$input = "aaaa\nbbbbbbbbbbbb\ncccccc\n";
		$result = dolGetFirstLineOfText($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa...", $result);

		$input = "aaaa<br>bbbbbbbbbbbb<br>cccccc<br>";
		$result = dolGetFirstLineOfText($input);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa...", $result);

		$input = "aaaa\nbbbbbbbbbbbb\ncccccc\n";
		$result = dolGetFirstLineOfText($input, 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa\nbbbbbbbbbbbb...", $result);

		$input = "aaaa<br>bbbbbbbbbbbb<br>cccccc<br>";
		$result = dolGetFirstLineOfText($input, 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb...", $result);

		// Nb of line is higher

		$input = "aaaa<br>bbbbbbbbbbbb<br>cccccc";
		$result = dolGetFirstLineOfText($input, 100);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb<br>\ncccccc", $result, 'dolGetFirstLineOfText with nb 100 a');

		$input = "aaaa<br>bbbbbbbbbbbb<br>cccccc<br>";
		$result = dolGetFirstLineOfText($input, 100);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb<br>\ncccccc", $result, 'dolGetFirstLineOfText with nb 100 b');

		$input = "aaaa<br>bbbbbbbbbbbb<br>cccccc<br>\n";
		$result = dolGetFirstLineOfText($input, 100);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb<br>\ncccccc", $result, 'dolGetFirstLineOfText with nb 100 c');
	}


	/**
	 * testDolBuildPath
	 *
	 * @return void
	 */
	public function testDolBuildPath()
	{
		/*$tmp=dol_buildpath('/google/oauth2callback.php', 0);
		var_dump($tmp);
		*/

		/*$tmp=dol_buildpath('/google/oauth2callback.php', 1);
		var_dump($tmp);
		*/

		$result = dol_buildpath('/google/oauth2callback.php', 2);
		print __METHOD__." dol_buildpath result=".$result."\n";
		$this->assertStringStartsWith('http', $result);

		$result = dol_buildpath('/google/oauth2callback.php', 3);
		print __METHOD__." dol_buildpath result=".$result."\n";
		$this->assertStringStartsWith('http', $result);
	}


	/**
	* testGetBrowserInfo
	*
	* @return void
	*/
	public function testGetBrowserInfo()
	{
		// MSIE 5.0
		$user_agent = 'Mozilla/4.0 (compatible; MSIE 5.0; Windows 98; DigExt; KITV4 Wanadoo; KITV5 Wanadoo)';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('ie', $tmp['browsername']);
		$this->assertEquals('5.0', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		// Firefox 0.9.1
		$user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.5a) Gecko/20030728 Mozilla Firefox/0.9.1';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('firefox', $tmp['browsername']);
		$this->assertEquals('0.9.1', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		$user_agent = 'Mozilla/3.0 (Windows 98; U) Opera 6.03  [en]';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('opera', $tmp['browsername']);
		$this->assertEquals('6.03', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		$user_agent = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.21 (KHTML, like Gecko) Chrome/19.0.1042.0 Safari/535.21';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('chrome', $tmp['browsername']);
		$this->assertEquals('19.0.1042.0', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		$user_agent = 'chrome (Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11)';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('chrome', $tmp['browsername']);
		$this->assertEquals('17.0.963.56', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		$user_agent = 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; de-at) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('safari', $tmp['browsername']);
		$this->assertEquals('533.21.1', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		//Internet Explorer 11
		$user_agent = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('ie', $tmp['browsername']);
		$this->assertEquals('11.0', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		//Internet Explorer 11 bis
		$user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; NP06; rv:11.0) like Gecko';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('ie', $tmp['browsername']);
		$this->assertEquals('11.0', $tmp['browserversion']);
		$this->assertEmpty($tmp['phone']);
		$this->assertFalse($tmp['tablet']);
		$this->assertEquals('classic', $tmp['layout']);

		//iPad
		$user_agent = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('safari', $tmp['browsername']);
		$this->assertEquals('8536.25', $tmp['browserversion']);
		$this->assertEquals('ios', $tmp['browseros']);
		$this->assertEquals('tablet', $tmp['layout']);
		$this->assertEquals('iphone', $tmp['phone']);

		//Lynx
		$user_agent = 'Lynx/2.8.8dev.3 libwww‑FM/2.14 SSL‑MM/1.4.1';
		$tmp = getBrowserInfo($user_agent);
		$this->assertEquals('lynxlinks', $tmp['browsername']);
		$this->assertEquals('2.8.8', $tmp['browserversion']);
		$this->assertEquals('unknown', $tmp['browseros']);
		$this->assertEquals('classic', $tmp['layout']);
	}


	/**
	 * testGetLanguageCodeFromCountryCode
	 *
	 * @return void
	 */
	public function testGetLanguageCodeFromCountryCode()
	{
		global $mysoc;

		$language = getLanguageCodeFromCountryCode('US');
		$this->assertEquals('en_US', $language, 'US');

		$language = getLanguageCodeFromCountryCode('ES');
		$this->assertEquals('es_ES', $language, 'ES');

		$language = getLanguageCodeFromCountryCode('CL');
		$this->assertEquals('es_CL', $language, 'CL');

		$language = getLanguageCodeFromCountryCode('CA');
		$this->assertEquals('en_CA', $language, 'CA');

		$language = getLanguageCodeFromCountryCode('MQ');
		$this->assertEquals('fr_CA', $language);

		$language = getLanguageCodeFromCountryCode('FR');
		$this->assertEquals('fr_FR', $language);

		$language = getLanguageCodeFromCountryCode('BE');
		$this->assertEquals('fr_BE', $language);

		$mysoc->country_code = 'FR';
		$language = getLanguageCodeFromCountryCode('CH');
		$this->assertEquals('fr_CH', $language);

		$mysoc->country_code = 'DE';
		$language = getLanguageCodeFromCountryCode('CH');
		$this->assertEquals('de_CH', $language);

		$language = getLanguageCodeFromCountryCode('DE');
		$this->assertEquals('de_DE', $language);

		$language = getLanguageCodeFromCountryCode('SA');
		$this->assertEquals('ar_SA', $language);

		$language = getLanguageCodeFromCountryCode('SE');
		$this->assertEquals('sv_SE', $language);

		$language = getLanguageCodeFromCountryCode('DK');
		$this->assertEquals('da_DK', $language);
	}

	/**
	 * testDolTextIsHtml
	 *
	 * @return void
	 */
	public function testDolTextIsHtml()
	{
		// True
		$input = '<html>xxx</html>';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with html tag');
		$input = '<body>xxx</body>';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with body tag');
		$input = 'xxx <b>yyy</b> zzz';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with b tag');
		$input = 'xxx <u>yyy</u> zzz';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with u tag');
		$input = 'text with <div>some div</div>';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with div tag');
		$input = 'text with HTML &nbsp; entities';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with entities tag');
		$input = 'xxx<br>';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with entities br');
		$input = 'xxx<br >';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with entities br');
		$input = 'xxx<br style="eee">';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with entities br and attributes');
		$input = 'xxx<br style="eee" >';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with entities br and attributes bis');
		$input = '<h2>abc</h2>';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with entities h2');
		$input = '<img id="abc" src="https://xxx.com/aaa/image.png" />';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with img tag');
		$input = '<a class="azerty" href="https://xxx.com/aaa/image.png" />';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with a tag');
		$input = 'This is a text with&nbsp;html spaces';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with a &nbsp;');
		$input = 'This is a text with accent &eacute;';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with a &eacute;');
		$input = '<i class="abc">xxx</i>';
		$after = dol_textishtml($input);
		$this->assertTrue($after, 'Test with i tag and class;');

		// False
		$input = 'xxx < br>';
		$after = dol_textishtml($input);
		$this->assertFalse($after);
		$input = 'xxx <email@email.com>';	// <em> is html, <em... is not
		$after = dol_textishtml($input);
		$this->assertFalse($after);
		$input = 'xxx <brstyle="ee">';
		$after = dol_textishtml($input);
		$this->assertFalse($after);
		$input = 'This is a text with html comments <!-- comment -->';	// we suppose this is not enough to be html content
		$after = dol_textishtml($input);
		$this->assertFalse($after);

		$input = "A text\nwith a link https://aaa?param=abc&amp;param2=def";
		$after = dol_textishtml($input);
		$this->assertFalse($after);
	}


	/**
	 * testDolHtmlCleanLastBr
	 *
	 * @return boolean
	 */
	public function testDolHtmlCleanLastBr()
	{
		$input = "A string\n";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string", $after);

		$input = "A string first\nA string second\n";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string first\nA string second", $after);

		$input = "A string\n\n\n";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string", $after);

		$input = "A string<br>";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string", $after);

		$input = "A string first<br>\nA string second<br>";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string first<br>\nA string second", $after);

		$input = "A string\n<br type=\"_moz\" />\n";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string", $after);

		$input = "A string\n<br><br />\n\n";
		$after = dol_htmlcleanlastbr($input);
		$this->assertEquals("A string", $after);

		return true;
	}

	/**
	 * testDolConcat
	 *
	 * @return boolean
	 */
	public function testDolConcat()
	{
		$text1 = "A string 1";
		$text2 = "A string 2";	// text 1 and 2 are text, concat need only \n
		$after = dol_concatdesc($text1, $text2);
		$this->assertEquals("A string 1\nA string 2", $after);

		$text1 = "A<br>string 1";
		$text2 = "A string 2";	// text 1 is html, concat need <br>\n
		$after = dol_concatdesc($text1, $text2);
		$this->assertEquals("A<br>string 1<br>\nA string 2", $after);

		$text1 = "A string 1";
		$text2 = "A <b>string</b> 2";	// text 2 is html, concat need <br>\n
		$after = dol_concatdesc($text1, $text2);
		$this->assertEquals("A string 1<br>\nA <b>string</b> 2", $after);

		return true;
	}


	/**
	 * testDolStringNoSpecial
	 *
	 * @return boolean
	 */
	public function testDolStringNoSpecial()
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$text = "A string with space and special char like ' or ° and more...\n";
		$after = dol_string_nospecial($text, '_', '', '', 0);
		$this->assertEquals("A_string_with_space_and_special_char_like___or___and_more...\n", $after, "testDolStringNoSpecial 1");

		$text = "A string with space and special char like ' or ° and more...\n";
		$after = dol_string_nospecial($text, '_', '', '', 1);
		$this->assertEquals("A string with space and special char like _ or _ and more...\n", $after, "testDolStringNoSpecial 2");

		$text = 'Bahnhofstraße';
		$after = dolEscapeXML(dol_trunc(dol_string_nospecial(dol_string_unaccent($text), ' '), 70, 'right', 'UTF-8', 1));
		$this->assertEquals("Bahnhofstraße", $after, "testDolStringNoSpecial with german char");

		return true;
	}

	/**
	 * testDolStringNohtmltag
	 *
	 * @return boolean
	 */
	public function testDolStringNohtmltag()
	{
		$text = "A\nstring\n\nand more\n";
		$after = dol_string_nohtmltag($text, 0);
		$this->assertEquals("A\nstring\n\nand more", $after, "test1a");

		$text = "A <b>string<b><br>\n<br>\n\nwith html tag<br>\n";
		$after = dol_string_nohtmltag($text, 0);
		$this->assertEquals("A string\n\n\n\n\nwith html tag", $after, 'test2a 2 br and 3 \n give 5 \n');

		$text = "A <b>string<b><br>\n<br>\n\nwith html tag<br>\n";
		$after = dol_string_nohtmltag($text, 1);
		$this->assertEquals("A string with html tag", $after, 'test2b 2 br and 3 \n give 1 space');

		$text = "A <b>string<b><br>\n<br>\n\nwith html tag<br>\n";
		$after = dol_string_nohtmltag($text, 2);
		$this->assertEquals("A string\n\nwith html tag", $after, 'test2c 2 br and 3 \n give 2 \n');

		$text = "A <b>string<b><br>\r\n<br>\r\n\r\nwith html tag<br>\n";
		$after = dol_string_nohtmltag($text, 2);
		$this->assertEquals("A string\n\nwith html tag", $after, 'test2c 2 br and 3 \r\n give 2 \n');

		$text = "A string<br>Another string";
		$after = dol_string_nohtmltag($text, 0);
		$this->assertEquals("A string\nAnother string", $after, "test4");

		$text = "A string<br>Another string";
		$after = dol_string_nohtmltag($text, 1);
		$this->assertEquals("A string Another string", $after, "test5");

		$text = '<a href="/myurl" title="<u>Afficher projet</u>">ABC</a>';
		$after = dol_string_nohtmltag($text, 1);
		$this->assertEquals("ABC", $after, "test6");

		$text = '<a href="/myurl" title="&lt;u&gt;Afficher projet&lt;/u&gt;">DEF</a>';
		$after = dol_string_nohtmltag($text, 1);
		$this->assertEquals("DEF", $after, "test7");

		$text = '<a href="/myurl" title="<u>A title</u>">HIJ</a>';
		$after = dol_string_nohtmltag($text, 0);
		$this->assertEquals("HIJ", $after, "test8");

		$text = "A <b>string<b>\n\nwith html tag and '<' chars<br>\n";
		$after = dol_string_nohtmltag($text, 0);
		$this->assertEquals("A string\n\nwith html tag and '<' chars", $after, "test9");

		$text = "A <b>string<b>\n\nwith tag with < chars<br>\n";
		$after = dol_string_nohtmltag($text, 1);
		$this->assertEquals("A string with tag with < chars", $after, "test10");

		return true;
	}



	/**
	 * testDolHtmlEntitiesBr
	 *
	 * @return boolean
	 */
	public function testDolHtmlEntitiesBr()
	{
		// Text not already HTML

		$input = "A string\nwith a é, &, < and >.";
		$after = dol_htmlentitiesbr($input, 0);    // Add <br> before \n
		$this->assertEquals("A string<br>\nwith a &eacute;, &amp;, &lt; and &gt;.", $after);

		$input = "A string\nwith a é, &, < and >.";
		$after = dol_htmlentitiesbr($input, 1);    // Replace \n with <br>
		$this->assertEquals("A string<br>with a &eacute;, &amp;, &lt; and &gt;.", $after);

		$input = "A string\nwith a é, &, < and >.\n\n";	// With some \n at end that should be cleaned
		$after = dol_htmlentitiesbr($input, 0);    // Add <br> before \n
		$this->assertEquals("A string<br>\nwith a &eacute;, &amp;, &lt; and &gt;.", $after);

		$input = "A string\nwith a é, &, < and >.\n\n";	// With some \n at end that should be cleaned
		$after = dol_htmlentitiesbr($input, 1);    // Replace \n with <br>
		$this->assertEquals("A string<br>with a &eacute;, &amp;, &lt; and &gt;.", $after);

		// Text already HTML, so &,<,> should not be converted

		$input = "A string<br>\nwith a é, &, < and >.";
		$after = dol_htmlentitiesbr($input);
		$this->assertEquals("A string<br>\nwith a &eacute;, &, < and >.", $after);

		$input = "<li>\nA string with a é, &, < and >.</li>\nAnother string";
		$after = dol_htmlentitiesbr($input);
		$this->assertEquals("<li>\nA string with a &eacute;, &, < and >.</li>\nAnother string", $after);

		$input = "A string<br>\nwith a é, &, < and >.<br>";	// With some <br> at end that should be cleaned
		$after = dol_htmlentitiesbr($input);
		$this->assertEquals("A string<br>\nwith a &eacute;, &, < and >.", $after);

		$input = "<li>\nA string with a é, &, < and >.</li>\nAnother string<br>";	// With some <br> at end that should be cleaned
		$after = dol_htmlentitiesbr($input);
		$this->assertEquals("<li>\nA string with a &eacute;, &, < and >.</li>\nAnother string", $after);

		// TODO Add test with param $removelasteolbr = 0

		return true;
	}


	/**
	 * testDolNbOfLinesBis
	 *
	 * @return boolean
	 */
	public function testDolNbOfLinesBis()
	{
		// This is not a html string so nb of lines depends on \n
		$input = "A string\nwith a é, &, < and > and bold tag.\nThird line";
		$after = dol_nboflines_bis($input, 0);
		$this->assertEquals($after, 3);

		// This is a html string so nb of lines depends on <br>
		$input = "A string\nwith a é, &, < and > and <b>bold</b> tag.\nThird line";
		$after = dol_nboflines_bis($input, 0);
		$this->assertEquals($after, 1);

		// This is a html string so nb of lines depends on <br>
		$input = "A string<br>with a é, &, < and > and <b>bold</b> tag.<br>Third line";
		$after = dol_nboflines_bis($input, 0);
		$this->assertEquals($after, 3);

		return true;
	}


	/**
	 * testDolUnaccent
	 *
	 * @return boolean
	 */
	public function testDolUnaccent()
	{
		// Text not already HTML

		$input = "A string\nwith a à ä é è ë ï ü ö ÿ, &, < and >.";
		$after = dol_string_unaccent($input);
		$this->assertEquals("A string\nwith a a a e e e i u o y, &, < and >.", $after);
	}


	/**
	 * testDolUtf8Check
	 *
	 * @return void
	 */
	public function testDolUtf8Check()
	{
		// True
		$result = utf8_check('azerty');
		$this->assertTrue($result);

		$file = dirname(__FILE__).'/textutf8.txt';
		$filecontent = file_get_contents($file);
		$result = utf8_check($filecontent);
		$this->assertTrue($result);

		$file = dirname(__FILE__).'/textiso.txt';
		$filecontent = file_get_contents($file);
		$result = utf8_check($filecontent);
		$this->assertFalse($result);
	}

	/**
	 * testDolAsciiCheck
	 *
	 * @return void
	 */
	public function testDolAsciiCheck()
	{
		// True
		$result = ascii_check('azerty');
		$this->assertTrue($result);

		$result = ascii_check('é');
		$this->assertFalse($result);

		$file = dirname(__FILE__).'/textutf8.txt';
		$filecontent = file_get_contents($file);
		$result = ascii_check($filecontent);
		$this->assertFalse($result);
	}

	/**
	 * testDolTrunc
	 *
	 * @return boolean
	 */
	public function testDolTrunc()
	{
		// Default trunc (will add … if truncation truncation or keep last char if only one char)
		$input = "éeéeéeàa";
		$after = dol_trunc($input, 3);
		$this->assertEquals("éeé…", $after, 'Test A1');
		$after = dol_trunc($input, 2);
		$this->assertEquals("ée…", $after, 'Test A2');
		$after = dol_trunc($input, 1);
		$this->assertEquals("é…", $after, 'Test A3');
		$input = "éeée";
		$after = dol_trunc($input, 3);
		$this->assertEquals("éeée", $after, 'Test B1');
		$after = dol_trunc($input, 2);
		$this->assertEquals("ée…", $after, 'Test B2');
		$after = dol_trunc($input, 1);
		$this->assertEquals("é…", $after, 'Test B3');
		$input = "éeée";
		$after = dol_trunc($input, 3);
		$this->assertEquals("éeée", $after, 'Test C1');
		$after = dol_trunc($input, 2);
		$this->assertEquals("ée…", $after, 'Test C2');
		$after = dol_trunc($input, 1);
		$this->assertEquals("é…", $after, 'Test C3');
		$input = "éeé";
		$after = dol_trunc($input, 3);
		$this->assertEquals("éeé", $after, 'Test C');
		$after = dol_trunc($input, 2);
		$this->assertEquals("éeé", $after, 'Test D');
		$after = dol_trunc($input, 1);
		$this->assertEquals("é…", $after, 'Test E');
		// Trunc with no …
		$input = "éeéeéeàa";
		$after = dol_trunc($input, 3, 'right', 'UTF-8', 1);
		$this->assertEquals("éeé", $after, 'Test F');
		$after = dol_trunc($input, 2, 'right', 'UTF-8', 1);
		$this->assertEquals("ée", $after, 'Test G');
		$input = "éeé";
		$after = dol_trunc($input, 3, 'right', 'UTF-8', 1);
		$this->assertEquals("éeé", $after, 'Test H');
		$after = dol_trunc($input, 2, 'right', 'UTF-8', 1);
		$this->assertEquals("ée", $after, 'Test I');
		$after = dol_trunc($input, 1, 'right', 'UTF-8', 1);
		$this->assertEquals("é", $after, 'Test J');
		$input = "éeéeéeàa";
		$after = dol_trunc($input, 4, 'middle');
		$this->assertEquals("ée…àa", $after, 'Test K');

		return true;
	}

	/**
	 * testDolMkTime
	 *
	 * @return	void
	 */
	public function testDolMkTime()
	{
		global $conf;

		$savtz = date_default_timezone_get();

		// Some test for UTC TZ
		date_default_timezone_set('UTC');

		// Check bad hours
		$result = dol_mktime(25, 0, 0, 1, 1, 1970, 1, 1);    // Error (25 hours)
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);
		$result = dol_mktime(2, 61, 0, 1, 1, 1970, 1, 1);    // Error (61 minutes)
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);
		$result = dol_mktime(2, 1, 61, 1, 1, 1970, 1, 1);    // Error (61 seconds)
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);
		$result = dol_mktime(2, 1, 1, 1, 32, 1970, 1, 1);    // Error (day 32)
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);
		$result = dol_mktime(2, 1, 1, 13, 1, 1970, 1, 1);    // Error (month 13)
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result = dol_mktime(2, 1, 1, 1, 1, 1970, 1);    // 1970-01-01 02:01:01 in GMT area -> 7261
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(7261, $result);

		$result = dol_mktime(2, 0, 0, 1, 1, 1970, 0);                // 1970-01-01 02:00:00 = 7200 in local area Europe/Paris = 3600 GMT
		print __METHOD__." result=".$result."\n";
		$tz = getServerTimeZoneInt('winter');                  // +1 in Europe/Paris at this time (this time is winter)
		$this->assertEquals(7200 - ($tz * 3600), $result);        // 7200 if we are at greenwich winter, 7200-($tz*3600) at local winter

		// Some test for local TZ Europe/Paris
		date_default_timezone_set('Europe/Paris');

		// Check that tz for paris in winter is used
		$result = dol_mktime(2, 0, 0, 1, 1, 1970, 'server');         // 1970-01-01 02:00:00 = 7200 in local area Europe/Paris = 3600 GMT
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(3600, $result);        			 // 7200 if we are at greenwich winter, 3600 at Europe/Paris

		// Check that daylight saving time is used
		$result = dol_mktime(2, 0, 0, 6, 1, 2014, 0);         		// 2014-06-01 02:00:00 = 1401588000-3600(location)-3600(daylight) in local area Europe/Paris = 1401588000 GMT
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(1401588000 - 3600 - 3600, $result);  // 1401588000 are at greenwich summer, 1401588000-3600(location)-3600(daylight) at Europe/Paris summer

		date_default_timezone_set($savtz);
	}


	/**
	 * testDolEscapeJs
	 *
	 * @return	void
	 */
	public function testDolEscapeJs()
	{
		$input = "x&<b>#</b>,\"'";    // " will be converted into '
		$result = dol_escape_js($input);
		$this->assertEquals("x&<b>#</b>,\'\'", $result, "Test mode=0");

		$result = dol_escape_js($input, 1);
		$this->assertEquals("x&<b>#</b>,\"\'", $result, "Test mode=1");

		$result = dol_escape_js($input, 2);
		$this->assertEquals("x&<b>#</b>,\\\"'", $result, "Test mode=2");
	}


	/**
	 * testDolEscapeHtmlTag
	 *
	 * @return	void
	 */
	public function testDolEscapeHtmlTag()
	{
		$input = 'x&<b>#</b>,"';    // & and " are converted into html entities, <b> are removed
		$result = dol_escape_htmltag($input);
		$this->assertEquals('x&amp;#,&quot;', $result);

		$input = 'x&<b>#</b>,"';    // & and " are converted into html entities, <b> are not removed
		$result = dol_escape_htmltag($input, 1);
		$this->assertEquals('x&amp;&lt;b&gt;#&lt;/b&gt;,&quot;', $result);

		$input = '<img alt="" src="https://github.githubassets.com/assets/GitHub-Mark-ea2971cee799.png">';    // & and " are converted into html entities, <b> are not removed
		$result = dol_escape_htmltag($input, 1, 1, 'common', 0, 1);
		$this->assertEquals('<img alt="" src="https://github.githubassets.com/assets/GitHub-Mark-ea2971cee799.png">', $result);


		$input = '<div style="float:left; margin-left:0px; margin-right:5px">
		<img id="sigPhoto" src="https://www.domain.com/aaa.png" style="height:65px; width:65px" />
		</div>
		<div style="margin-left:74px"><strong>A text here</strong> and more<br>
		<a href="mailto:abc+def@domain.com" id="sigEmail" style="color:#428BCA;">abc+def@domain.com</a><br>
		<a href="https://www.another-domain.com" id="sigWebsite" style="color:#428BCA;">https://www.another-domain.com</a><br>
		</div>';

		$result = dol_escape_htmltag($input, 1, 1, 'common');
		$this->assertEquals($input, $result);
	}


	/**
	 * testDolFormatAddress
	 *
	 * @return	void
	 */
	public function testDolFormatAddress()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$object = new Societe($db);
		$object->initAsSpecimen();

		$object->country_code = 'FR';
		$address = dol_format_address($object);
		$this->assertEquals("21 jump street\n99999 MyTown", $address);

		$object->country_code = 'GB';
		$address = dol_format_address($object);
		$this->assertEquals("21 jump street\nMyTown, MyState\n99999", $address);

		$object->country_code = 'US';
		$address = dol_format_address($object);
		$this->assertEquals("21 jump street\nMyTown, MyState, 99999", $address);

		$object->country_code = 'AU';
		$address = dol_format_address($object);
		$this->assertEquals("21 jump street\nMyTown, MyState, 99999", $address);

		$object->country_code = 'JP';
		$address = dol_format_address($object);
		$this->assertEquals("21 jump street\nMyState, MyTown 99999", $address);
	}


	/**
	 * testDolPrintPhone
	 *
	 * @return	void
	 */
	public function testDolPrintPhone()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$object = new Societe($db);
		$object->initAsSpecimen();

		$object->country_code = 'FR';
		$phone = dol_print_phone('1234567890', $object->country_code);
		$this->assertEquals('<span style="margin-right: 10px;">12&nbsp;34&nbsp;56&nbsp;78&nbsp;90</span>', $phone, 'Phone for FR 1');

		$object->country_code = 'FR';
		$phone = dol_print_phone('1234567890', $object->country_code, 0, 0, 0, '');
		$this->assertEquals('<span style="margin-right: 10px;">1234567890</span>', $phone, 'Phone for FR 2');

		$object->country_code = 'FR';
		$phone = dol_print_phone('1234567890', $object->country_code, 0, 0, 0, ' ');
		$this->assertEquals('<span style="margin-right: 10px;">12 34 56 78 90</span>', $phone, 'Phone for FR 3');

		$object->country_code = 'CA';
		$phone = dol_print_phone('1234567890', $object->country_code, 0, 0, 0, ' ');
		$this->assertEquals('<span style="margin-right: 10px;">(123) 456-7890</span>', $phone, 'Phone for CA 1');
	}


	/**
	 * testImgPicto
	 *
	 * @return	void
	 */
	public function testImgPicto()
	{
		$s = img_picto('title', 'user');
		print __METHOD__." s=".$s."\n";
		$this->assertStringContainsStringIgnoringCase('fa-user', $s, 'testImgPicto1');

		$s = img_picto('title', 'img.png', 'style="float: right"', 0);
		print __METHOD__." s=".$s."\n";
		$this->assertStringContainsStringIgnoringCase('theme', $s, 'testImgPicto2');
		$this->assertStringContainsStringIgnoringCase('style="float: right"', $s, 'testImgPicto2');

		$s = img_picto('title', '/fullpath/img.png', '', 1);
		print __METHOD__." s=".$s."\n";
		$this->assertEquals('<img src="/fullpath/img.png" alt="" title="title" class="inline-block">', $s, 'testImgPicto3');

		$s = img_picto('title', '/fullpath/img.png', '', true);
		print __METHOD__." s=".$s."\n";
		$this->assertEquals('<img src="/fullpath/img.png" alt="" title="title" class="inline-block">', $s, 'testImgPicto4');

		$s = img_picto('title', 'delete', '', 0, 1);
		print __METHOD__." s=".$s."\n";
		$this->assertEquals(DOL_URL_ROOT.'/theme/eldy/img/delete.png', $s, 'testImgPicto5');
	}

	/**
	 * testDolNow
	 *
	 * @return	void
	 */
	public function testDolNow()
	{
		$now = dol_now('gmt');
		$nowtzserver = dol_now('tzserver');
		print __METHOD__." getServerTimeZoneInt=".(getServerTimeZoneInt('now') * 3600)."\n";
		$this->assertEquals(getServerTimeZoneInt('now') * 3600, ($nowtzserver - $now));
	}



	/**
	 * Data provider for testVerifCond
	 *
	 * @return array<string,array{0:string,1:bool}>
	 */
	public function verifCondDataProvider(): array
	{
		return [
			'Test a true comparison' => ['1==1', true,],
			'Test a false comparison' => ['1==2', false,],
			'Test that the conf property of a module reports true when enabled' => ['isModEnabled("facture")', true,],
			'Test that the conf property of a module reports false when disabled' => ['isModEnabled("moduledummy")', false,],
			'Test that verifConf(0) returns false' => [0, false,],
			'Test that verifConf("0") returns false' => ["0", false,],
			'Test that verifConf("") returns false (special case)' => ['', true,],
		];
	}

	/**
	 * testVerifCond
	 *
	 * @dataProvider verifCondDataProvider
	 *
	 * @param string $cond     Condition to test using verifCond
	 * @param string $expected Expected outcome of verifCond
	 *
	 * @return	void
	 */
	public function testVerifCond($cond, $expected)
	{
		if ($expected) {
			$this->assertTrue(verifCond($cond));
		} else {
			$this->assertFalse(verifCond($cond));
		}
	}

	/**
	 * testGetDefaultTva
	 *
	 * @return	void
	 */
	public function testGetDefaultTva()
	{
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		// Sellers
		$companyfrnovat = new Societe($db);
		$companyfrnovat->country_code = 'FR';
		$companyfrnovat->tva_assuj = 0;

		$companyfr = new Societe($db);
		$companyfr->country_code = 'FR';
		$companyfr->tva_assuj = 1;
		$companyfr->tva_intra = 'FR9999';

		// Buyers
		$companymc = new Societe($db);
		$companymc->country_code = 'MC';
		$companymc->tva_assuj = 1;
		$companyfr->tva_intra = 'MC9999';

		$companyit = new Societe($db);
		$companyit->country_code = 'IT';
		$companyit->tva_assuj = 1;
		$companyit->tva_intra = 'IT99999';

		$companyde = new Societe($db);
		$companyde->country_code = 'DE';
		$companyde->tva_assuj = 1;
		$companyde->tva_intra = 'DE99999';

		$notcompanyde = new Societe($db);
		$notcompanyde->country_code = 'DE';
		$notcompanyde->tva_assuj = 0;
		$notcompanyde->tva_intra = '';
		$notcompanyde->typent_code = 'TE_PRIVATE';

		$companyus = new Societe($db);
		$companyus->country_code = 'US';
		$companyus->tva_assuj = 1;
		$companyus->tva_intra = '';


		// Test RULE 0 (FR-DE)
		// Not tested

		// Test RULE 1
		$vat = get_default_tva($companyfrnovat, $companymc, 0);
		$this->assertEquals(0, $vat, 'RULE 1');

		// Test RULE 2 (FR-FR)
		$vat = get_default_tva($companyfr, $companyfr, 0);
		$this->assertEquals(20, $vat, 'RULE 2');

		// Test RULE 2 (FR-MC)
		$vat = get_default_tva($companyfr, $companymc, 0);
		$this->assertEquals(20, $vat, 'RULE 2');

		// Test RULE 3 (FR-DE company)
		$vat = get_default_tva($companyfr, $companyit, 0);
		$this->assertEquals(0, $vat, 'RULE 3');

		// Test RULE 4 (FR-DE not a company)
		$vat = get_default_tva($companyfr, $notcompanyde, 0);
		$this->assertEquals(20, $vat, 'RULE 4');

		// Test RULE 5 (FR-US)
		$vat = get_default_tva($companyfr, $companyus, 0);
		$this->assertEquals(0, $vat, 'RULE 5');


		// We do same tests but with option SERVICE_ARE_ECOMMERCE_200238EC on.
		$conf->global->SERVICE_ARE_ECOMMERCE_200238EC = 1;

		// Test RULE 1 (FR-US)
		$vat = get_default_tva($companyfr, $companyus, 0);
		$this->assertEquals(0, $vat, 'RULE 1 ECOMMERCE_200238EC');

		// Test RULE 2 (FR-FR)
		$vat = get_default_tva($companyfr, $companyfr, 0);
		$this->assertEquals(20, $vat, 'RULE 2 ECOMMERCE_200238EC');

		// Test RULE 3 (FR-DE company)
		$vat = get_default_tva($companyfr, $companyde, 0);
		$this->assertEquals(0, $vat, 'RULE 3 ECOMMERCE_200238EC');

		// Test RULE 4 (FR-DE not a company)
		$vat = get_default_tva($companyfr, $notcompanyde, 0);
		$this->assertEquals(19, $vat, 'RULE 4 ECOMMERCE_200238EC');

		// Test RULE 5 (FR-US)
		$vat = get_default_tva($companyfr, $companyus, 0);
		$this->assertEquals(0, $vat, 'RULE 5 ECOMMERCE_200238EC');
	}

	/**
	 * testGetDefaultLocalTax
	 *
	 * @return	void
	 */
	public function testGetDefaultLocalTax()
	{
		global $conf,$user,$langs,$db;
		$this->savconf = $conf;
		$this->savuser = $user;
		$this->savlangs = $langs;
		$this->savdb = $db;

		$companyfrnovat = new Societe($db);
		$companyfrnovat->country_code = 'FR';
		$companyfrnovat->tva_assuj = 0;
		$companyfrnovat->localtax1_assuj = 0;
		$companyfrnovat->localtax2_assuj = 0;

		$companyes = new Societe($db);
		$companyes->country_code = 'ES';
		$companyes->tva_assuj = 1;
		$companyes->localtax1_assuj = 1;
		$companyes->localtax2_assuj = 1;

		$companymc = new Societe($db);
		$companymc->country_code = 'MC';
		$companymc->tva_assuj = 1;
		$companymc->localtax1_assuj = 0;
		$companymc->localtax2_assuj = 0;

		$companyit = new Societe($db);
		$companyit->country_code = 'IT';
		$companyit->tva_assuj = 1;
		$companyit->tva_intra = 'IT99999';
		$companyit->localtax1_assuj = 0;
		$companyit->localtax2_assuj = 0;

		$notcompanyit = new Societe($db);
		$notcompanyit->country_code = 'IT';
		$notcompanyit->tva_assuj = 1;
		$notcompanyit->tva_intra = '';
		$notcompanyit->typent_code = 'TE_PRIVATE';
		$notcompanyit->localtax1_assuj = 0;
		$notcompanyit->localtax2_assuj = 0;

		$companyus = new Societe($db);
		$companyus->country_code = 'US';
		$companyus->tva_assuj = 1;
		$companyus->tva_intra = '';
		$companyus->localtax1_assuj = 0;
		$companyus->localtax2_assuj = 0;

		// Test RULE FR-MC
		$vat1 = get_default_localtax($companyfrnovat, $companymc, 1, 0);
		$vat2 = get_default_localtax($companyfrnovat, $companymc, 2, 0);
		$this->assertEquals(0, $vat1);
		$this->assertEquals(0, $vat2);

		// Test RULE ES-ES
		$vat1 = get_default_localtax($companyes, $companyes, 1, 0);
		$vat2 = get_default_localtax($companyes, $companyes, 2, 0);
		$this->assertEquals($vat1, 5.2);
		$this->assertStringStartsWith((string) $vat2, '-19:-15:-9');       // Can be -19 (old version) or '-19:-15:-9' (new setup)

		// Test RULE ES-IT
		$vat1 = get_default_localtax($companyes, $companyit, 1, 0);
		$vat2 = get_default_localtax($companyes, $companyit, 2, 0);
		$this->assertEquals(0, $vat1);
		$this->assertEquals(0, $vat2);

		// Test RULE ES-IT
		$vat1 = get_default_localtax($companyes, $notcompanyit, 1, 0);
		$vat2 = get_default_localtax($companyes, $notcompanyit, 2, 0);
		$this->assertEquals(0, $vat1);
		$this->assertEquals(0, $vat2);

		// Test RULE FR-IT
		// Not tested

		// Test RULE ES-US
		$vat1 = get_default_localtax($companyes, $companyus, 1, 0);
		$vat2 = get_default_localtax($companyes, $companyus, 2, 0);
		$this->assertEquals(0, $vat1);
		$this->assertEquals(0, $vat2);
	}


	/**
	 * testGetLocalTaxByThird
	 *
	 * @return	void
	 */
	public function testGetLocalTaxByThird()
	{
		global $mysoc;

		$mysoc->country_code = 'ES';

		$result = get_localtax_by_third(1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('5.2', $result);

		$result = get_localtax_by_third(2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('-19:-15:-9', $result);
	}


	/**
	 * testDolExplodeIntoArray
	 *
	 * @return	void
	 */
	public function testDolExplodeIntoArray()
	{
		$stringtoexplode = 'AA=B/B.CC=.EE=FF.HH=GG;.';
		$tmp = dolExplodeIntoArray($stringtoexplode, '.', '=');

		print __METHOD__." tmp=".json_encode($tmp)."\n";
		$this->assertEquals('{"AA":"B\/B","CC":"","EE":"FF","HH":"GG;"}', json_encode($tmp));

		$stringtoexplode = "AA=B/B;CC=\n\rEE=FF\nHH=GG;;;\nII=JJ\n";
		$tmp = dolExplodeIntoArray($stringtoexplode, "(\r\n|\n|\r|;)", '=');

		print __METHOD__." tmp=".json_encode($tmp)."\n";
		$this->assertEquals('{"AA":"B\/B","CC":"","EE":"FF","HH":"GG","II":"JJ"}', json_encode($tmp));
	}

	/**
	 * dol_nl2br
	 *
	 * @return void
	 */
	public function testDolNl2Br()
	{

		//String to encode
		$string = "a\na";

		$this->assertEquals(dol_nl2br($string), "a<br>\na");

		//With $forxml parameter
		$this->assertEquals(dol_nl2br($string, 0, 1), "a<br />\na");

		//Replacing \n by br
		$this->assertEquals(dol_nl2br($string, 1), "a<br>a");

		//With $forxml parameter
		$this->assertEquals(dol_nl2br($string, 1, 1), "a<br />a");
	}

	/**
	 * testDolPrice2Num
	 *
	 * @return boolean
	 */
	public function testDolPrice2Num()
	{
		global $langs, $conf;

		$oldlangs = $langs;

		$newlangs = new Translate('', $conf);
		$newlangs->setDefaultLang('en_US');
		$newlangs->load("main");
		$langs = $newlangs;

		$this->assertEquals(150, price2num('(SELECT/**/CASE/**/WHEN/**/(0<1)/**/THEN/**/SLEEP(5)/**/ELSE/**/SLEEP(0)/**/END)'));

		$this->assertEquals(1000, price2num('1 000.0'));
		$this->assertEquals(1000, price2num('1 000', 'MT'));
		$this->assertEquals(1000, price2num('1 000', 'MU'));

		$this->assertEquals(1000.123456, price2num('1 000.123456'));

		// Round down
		$this->assertEquals(1000.12, price2num('1 000.123452', 'MT'), 'Error in round down with MT');
		$this->assertEquals(1000.12345, price2num('1 000.123452', 'MU'), "Test MU");

		// Round up
		$this->assertEquals(1000.13, price2num('1 000.125456', 'MT'));
		$this->assertEquals(1000.12546, price2num('1 000.125456', 'MU'), "Test MU");

		$this->assertEquals(1, price2num('1.000'), 'Test 1.000 give 1 with english language');

		// Text can't be converted
		$this->assertEquals('12.4', price2num('12.4$'));
		$this->assertEquals('12.4', price2num('12r.4$'));

		// For spanish language
		$newlangs2 = new Translate('', $conf);
		$newlangs2->setDefaultLang('es_ES');
		$newlangs2->load("main");
		$langs = $newlangs2;

		// Test with 3 chars after . or ,
		// If a . is used and there is 3 digits after, it is a thousand separator
		$this->assertEquals(1234, price2num('1.234', '', 2), 'Test 1.234 give 1234 with spanish language if user input');
		$this->assertEquals(1.234, price2num('1,234', '', 2), 'Test 1,234 give 1234 with spanish language if user input');
		$this->assertEquals(1234, price2num('1 234', '', 2), 'Test 1 234 give 1234 with spanish language if user input');
		$this->assertEquals(-1.234, price2num('-1.234'), 'Test 1.234 give 1.234 with spanish language');
		$this->assertEquals(-1.234, price2num('-1,234'), 'Test 1,234 give 1234 with spanish language');
		$this->assertEquals(-1234, price2num('-1 234'), 'Test 1 234 give 1234 with spanish language');
		$this->assertEquals(21500123, price2num('21.500.123'), 'Test 21.500.123 give 21500123 with spanish language');
		$this->assertEquals(21500123, price2num('21500.123', 0, 2), 'Test 21500.123 give 21500123 with spanish language if user input');
		$this->assertEquals(21500.123, price2num('21500.123'), 'Test 21500.123 give 21500123 with spanish language');
		$this->assertEquals(21500.123, price2num('21500,123'), 'Test 21500,123 give 21500.123 with spanish language');
		// Test with 2 digits
		$this->assertEquals(21500.12, price2num('21500.12'), 'Test 21500.12 give 21500.12 with spanish language');
		$this->assertEquals(21500.12, price2num('21500,12'), 'Test 21500,12 give 21500.12 with spanish language');
		// Test with 3 digits
		$this->assertEquals(12123, price2num('12.123', '', 2), 'Test 12.123 give 12123 with spanish language if user input');
		$this->assertEquals(12.123, price2num('12,123', '', 2), 'Test 12,123 give 12.123 with spanish language if user input');
		$this->assertEquals(12.123, price2num('12.123'), 'Test 12.123 give 12.123 with spanish language');
		$this->assertEquals(12.123, price2num('12,123'), 'Test 12,123 give 12.123 with spanish language');

		// For french language
		$newlangs3 = new Translate('', $conf);
		$newlangs3->setDefaultLang('fr_FR');
		$newlangs3->load("main");
		$langs = $newlangs3;

		$this->assertEquals(1, price2num('1.000', '', 2), 'Test 1.000 give 1 with french language if user input');
		$this->assertEquals(1, price2num('1.000'), 'Test 1.000 give 1 with french language');
		$this->assertEquals(1000, price2num('1 000'), 'Test 1.000 give 1 with french language');
		$this->assertEquals(1.234, price2num('1.234', '', 2), 'Test 1.234 give 1.234 with french language if user input');
		$this->assertEquals(1.234, price2num('1.234'), 'Test 1.234 give 1.234 with french language');
		$this->assertEquals(1.234, price2num('1,234', '', 2), 'Test 1,234 give 1.234 with french language if user input');
		$this->assertEquals(1.234, price2num('1,234'), 'Test 1,234 give 1.234 with french language');
		$this->assertEquals(21500000, price2num('21500 000'), 'Test 21500 000 give 21500000 with french language');
		$this->assertEquals(21500000, price2num('21 500 000'), 'Test 21 500 000 give 21500000 with french language');
		$this->assertEquals(21500, price2num('21500.00'), 'Test 21500.00 give 21500 with french language');
		$this->assertEquals(21500, price2num('21500,00'), 'Test 21500,00 give 21500 with french language');

		$langs = $oldlangs;

		return true;
	}

	/**
	 * testDolGetDate
	 *
	 * @return boolean
	 */
	public function testDolGetDate()
	{
		global $conf;

		$conf->global->MAIN_START_WEEK = 0;

		$tmp = dol_getdate(24 * 60 * 60 + 1, false, 'UTC');		// 2/1/1970 and 1 second = friday
		$this->assertEquals(5, $tmp['wday'], 'Bad value of day in week');

		$conf->global->MAIN_START_WEEK = 1;

		$tmp = dol_getdate(1, false, 'UTC');				// 1/1/1970 and 1 second = thirday
		$this->assertEquals(4, $tmp['wday'], 'Bad value of day in week');

		$tmp = dol_getdate(24 * 60 * 60 + 1, false, 'UTC');		// 2/1/1970 and 1 second = friday
		$this->assertEquals(5, $tmp['wday'], 'Bad value of day in week');

		$tmp = dol_getdate(1, false, "Europe/Paris");						// 1/1/1970 and 1 second = thirday
		$this->assertEquals(1970, $tmp['year']);
		$this->assertEquals(1, $tmp['mon']);
		$this->assertEquals(1, $tmp['mday']);
		$this->assertEquals(4, $tmp['wday']);
		$this->assertEquals(0, $tmp['yday']);
		$this->assertEquals(1, $tmp['hours']);		// We are winter, so we are GMT+1 even during summer
		$this->assertEquals(0, $tmp['minutes']);
		$this->assertEquals(1, $tmp['seconds']);

		$tmp = dol_getdate(15638401, false, "Europe/Paris");					// 1/7/1970 and 1 second = wednesday
		$this->assertEquals(1970, $tmp['year']);
		$this->assertEquals(7, $tmp['mon']);
		$this->assertEquals(1, $tmp['mday']);
		$this->assertEquals(3, $tmp['wday']);
		$this->assertEquals(181, $tmp['yday']);
		$this->assertEquals(1, $tmp['hours']);		// There is no daylight in 1970, so we are GMT+1 even during summer
		$this->assertEquals(0, $tmp['minutes']);
		$this->assertEquals(1, $tmp['seconds']);

		$tmp = dol_getdate(1593561601, false, "Europe/Paris");				// 1/7/2020 and 1 second = wednesday
		$this->assertEquals(2020, $tmp['year']);
		$this->assertEquals(7, $tmp['mon']);
		$this->assertEquals(1, $tmp['mday']);
		$this->assertEquals(3, $tmp['wday']);
		$this->assertEquals(182, $tmp['yday']);		// 182 and not 181, due to the 29th february
		$this->assertEquals(2, $tmp['hours']);		// There is a daylight, so we are GMT+2
		$this->assertEquals(0, $tmp['minutes']);
		$this->assertEquals(1, $tmp['seconds']);

		$tmp = dol_getdate(1, false, 'UTC');						// 1/1/1970 and 1 second = thirday
		$this->assertEquals(1970, $tmp['year']);
		$this->assertEquals(1, $tmp['mon']);
		$this->assertEquals(1, $tmp['mday']);
		$this->assertEquals(4, $tmp['wday']);
		$this->assertEquals(0, $tmp['yday']);
		// We must disable this because on CI, timezone is may be UTC or something else
		//$this->assertEquals(1, $tmp['hours']);	// We are winter, so we are GMT+1 even during summer
		$this->assertEquals(0, $tmp['minutes']);
		$this->assertEquals(1, $tmp['seconds']);

		$tmp = dol_getdate(15638401, false, 'UTC');				// 1/7/1970 and 1 second = wednesday
		$this->assertEquals(1970, $tmp['year']);
		$this->assertEquals(7, $tmp['mon']);
		$this->assertEquals(1, $tmp['mday']);
		$this->assertEquals(3, $tmp['wday']);
		$this->assertEquals(181, $tmp['yday']);
		// We must disable this because on CI, timezone is may be UTC or something else
		//$this->assertEquals(1, $tmp['hours']);	// There is no daylight in 1970, so we are GMT+1 even during summer
		$this->assertEquals(0, $tmp['minutes']);
		$this->assertEquals(1, $tmp['seconds']);

		$tmp = dol_getdate(1593561601, false, 'UTC');				// 1/7/2020 and 1 second = wednesday
		$this->assertEquals(2020, $tmp['year']);
		$this->assertEquals(7, $tmp['mon']);
		$this->assertEquals(1, $tmp['mday']);
		$this->assertEquals(3, $tmp['wday']);
		$this->assertEquals(182, $tmp['yday']);		// 182 and not 181, due to the 29th february
		// We must disable this because on CI, timezone is may be UTC or something else
		//$this->assertEquals(2, $tmp['hours']);	// There is a daylight, so we are GMT+2
		$this->assertEquals(0, $tmp['minutes']);
		$this->assertEquals(1, $tmp['seconds']);

		return true;
	}


	/**
	 * testMakeSubstitutions
	 *
	 * @return boolean
	 */
	public function testMakeSubstitutions()
	{
		global $conf, $langs, $mysoc;
		$langs->load("main");

		// Try simple replacement
		$substit = array("__AAA__" => 'Not used', "__BBB__" => 'Not used', "__CCC__" => "C instead", "DDD" => "D instead");
		$substit += getCommonSubstitutionArray($langs);

		$chaine = 'This is a string with theme constant __[MAIN_THEME]__ and __(DIRECTION)__ and __CCC__ and DDD and __MYCOMPANY_NAME__ and __YEAR__';
		$newstring = make_substitutions($chaine, $substit);
		print __METHOD__." ".$newstring."\n";
		$this->assertEquals($newstring, 'This is a string with theme constant eldy and ltr and C instead and D instead and '.$mysoc->name.' and '.dol_print_date(dol_now(), '%Y', 'gmt'));

		// Try mix HTML not HTML, no change on initial text
		$substit = array("__NOHTML__" => 'No html', "__HTML__" => '<b>HTML</b>');

		$chaine = "This is a text with\nNew line\nThen\n__NOHTML__\nThen\n__HTML__";
		$newstring = make_substitutions($chaine, $substit, $langs);
		print __METHOD__." ".$newstring."\n";
		$this->assertEquals($newstring, "This is a text with\nNew line\nThen\nNo html\nThen\n<b>HTML</b>", 'Test on make_substitutions with conversion of inserted values only');

		// Try mix HTML not HTML, accept to change initial text
		$substit = array("__NOHTML__" => 'No html', "__HTML__" => '<b>HTML</b>');

		$chaine = "This is a text with\nNew line\nThen\n__NOHTML__\nThen\n__HTML__";
		$newstring = make_substitutions($chaine, $substit, $langs, 1);
		print __METHOD__." ".$newstring."\n";
		$this->assertEquals($newstring, "This is a text with<br>\nNew line<br>\nThen<br>\nNo html<br>\nThen<br>\n<b>HTML</b>", 'Test on make_substitutions with full conversion of text accepted');

		return true;
	}

	/**
	 * testDolStringIsGoodIso
	 *
	 * @return boolean
	 */
	public function testDolStringIsGoodIso()
	{
		global $conf, $langs;

		$chaine = 'This is an ISO string';
		$result = dol_string_is_good_iso($chaine);
		$this->assertEquals($result, 1);

		$chaine = 'This is a not ISO string '.chr(0);
		$result = dol_string_is_good_iso($chaine);
		$this->assertEquals($result, 0);

		return true;
	}

	/**
	 * testUtf8Check
	 *
	 * @return boolean
	 */
	public function testUtf8Check()
	{
		global $conf, $langs;

		$chaine = 'This is an UTF8 string with a é.';
		$result = utf8_check($chaine);
		$this->assertEquals(true, $result);

		$chaine = mb_convert_encoding('This is an UTF8 with a é.', 'ISO-8859-1', 'UTF-8');
		$result = utf8_check($chaine);
		$this->assertEquals(false, $result);

		return true;
	}

	/**
	 * testUtf8Valid
	 *
	 * @return boolean
	 */
	public function testUtf8Valid()
	{
		global $conf, $langs;

		$chaine = 'This is an UTF8 string with a é.';
		$result = utf8_valid($chaine);
		$this->assertEquals(true, $result);

		$chaine = mb_convert_encoding('This is an UTF8 with a é.', 'ISO-8859-1', 'UTF-8');
		$result = utf8_valid($chaine);
		$this->assertEquals(false, $result);

		return true;
	}

	/**
	 * testGetUserRemoteIP
	 *
	 * @return boolean
	 */
	public function testGetUserRemoteIP()
	{
		global $conf, $langs;

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4';
		$_SERVER['HTTP_CLIENT_IP'] = '5.6.7.8';
		$result = getUserRemoteIP();
		$this->assertEquals($result, '1.2.3.4');

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '1.2.3.4<corrupted>';
		$_SERVER['HTTP_CLIENT_IP'] = '5.6.7.8';
		$result = getUserRemoteIP();
		$this->assertEquals($result, '5.6.7.8');

		$_SERVER['HTTP_X_FORWARDED_FOR'] = '[1:2:3:4]';
		$_SERVER['HTTP_CLIENT_IP'] = '5.6.7.8';
		$result = getUserRemoteIP();
		$this->assertEquals($result, '[1:2:3:4]');

		return true;
	}

	/**
	 * testFetchObjectByElement
	 *
	 * @return boolean;
	 */
	public function testFetchObjectByElement()
	{
		global $conf, $langs;

		$result = fetchObjectByElement(0, 'product');

		$this->assertTrue(is_object($result));

		return true;
	}


	/**
	 * testRoundUpToNextMultiple
	 *
	 * @return void;
	 */
	public function testRoundUpToNextMultiple()
	{
		$this->assertEquals(roundUpToNextMultiple(39.5), 40);
		$this->assertEquals(roundUpToNextMultiple(40), 40);
		$this->assertEquals(roundUpToNextMultiple(40.4), 45);
		$this->assertEquals(roundUpToNextMultiple(40.5), 45);
		$this->assertEquals(roundUpToNextMultiple(44.5), 45);

		$this->assertEquals(roundUpToNextMultiple(39.5, 10), 40);
		$this->assertEquals(roundUpToNextMultiple(40, 10), 40);
		$this->assertEquals(roundUpToNextMultiple(40.5, 10), 50);
		$this->assertEquals(roundUpToNextMultiple(44.5, 10), 50);

		$this->assertEquals(roundUpToNextMultiple(39.5, 6), 42);
		$this->assertEquals(roundUpToNextMultiple(40, 6), 42);
		$this->assertEquals(roundUpToNextMultiple(40.5, 6), 42);
		$this->assertEquals(roundUpToNextMultiple(44.5, 6), 48);
	}
}
