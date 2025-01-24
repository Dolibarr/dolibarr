<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *      \file       test/phpunit/SecurityTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';

if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
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
if (! defined("NOSESSION")) {
	define("NOSESSION", '1');
}

require_once dirname(__FILE__).'/../../htdocs/main.inc.php';	// We force include of main.inc.php instead of master.inc.php even if we are in CLI mode because it contains a lot of security components we want to test.
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
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
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class SecurityTest extends CommonClassTest
{
	/**
	 * testSetLang
	 *
	 * @return string
	 */
	public function testSetLang()
	{
		global $conf;
		$conf = $this->savconf;

		$tmplangs = new Translate('', $conf);

		$_SERVER['HTTP_ACCEPT_LANGUAGE'] = "' malicious text with quote";
		$tmplangs->setDefaultLang('auto');
		print __METHOD__.' $tmplangs->defaultlang='.$tmplangs->defaultlang."\n";
		$this->assertEquals($tmplangs->defaultlang, 'malicioustextwithquote_MALICIOUSTEXTWITHQUOTE');
	}


	/**
	 * testSqlAndScriptInjectWithPHPUnit
	 *
	 * @return  void
	 */
	public function testSqlAndScriptInjectWithPHPUnit()
	{
		// Run tests
		// More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet

		// Should be OK
		$expectedresult = 0;

		/*
		$test = '';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual(0, $result, 'Error on testSqlAndScriptInject kkk');
		*/

		$_SERVER["PHP_SELF"] = '/DIR WITH SPACE/htdocs/admin/index.php';
		$result = testSqlAndScriptInject($_SERVER["PHP_SELF"], 2);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for PHP_SELF that should be ok');

		$test = 'This is a < inside string with < and > also and tag like <a> before the >';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject expected 0b');

		$test = 'This is the union of all for the selection of the best';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject expected 0c');

		$test = '/user/perms.php?id=1&action=addrights&entity=1&rights=123&confirm=yes&token=123456789&updatedmodulename=lmscoursetracking';
		$result = testSqlAndScriptInject($test, 1);
		print "test=".$test." result=".$result."\n";
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject with a valid url');

		// Should detect attack
		$expectedresult = 1;

		$_SERVER["PHP_SELF"] = '/DIR WITH SPACE/htdocs/admin/index.php/<svg>';
		$result = testSqlAndScriptInject($_SERVER["PHP_SELF"], 2);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject for PHP_SELF that should detect XSS');

		$test = 'select @@version';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL1a. Should find an attack on POST param and did not.');

		$test = 'select @@version';
		$result = testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL1b. Should find an attack on GET param and did not.');

		$test = '... update ... set ... =';
		$result = testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL2a. Should find an attack on GET param and did not.');

		$test = "delete\nfrom";
		$result = testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL2b. Should find an attack on GET param and did not.');

		$test = 'action=update& ... set ... =';
		$result = testSqlAndScriptInject($test, 1);
		$this->assertEquals(0, $result, 'Error on testSqlAndScriptInject for SQL2b. Should not find an attack on GET param and did.');

		$test = '... union ... selection ';
		$result = testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL2c. Should find an attack on GET param and did not.');

		$test = 'j&#x61;vascript:';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for javascript1. Should find an attack and did not.');

		$test = 'j&#x61vascript:';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for javascript2. Should find an attack and did not.');

		$test = 'javascript&colon&#x3B;alert(1)';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for javascript2');

		$test = "<img src='1.jpg' onerror =javascript:alert('XSS')>";
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa1');

		$test = "<img src='1.jpg' onerror =javascript:alert('XSS')>";
		$result = testSqlAndScriptInject($test, 2);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa2');

		$test = '<IMG SRC=# onmouseover="alert(1)">';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa3');
		$test = '<IMG SRC onmouseover="alert(1)">';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa4');
		$test = '<IMG onmouseover="alert(1)">';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa5');
		$test = '<IMG SRC=/ onerror="alert(1)">';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa6');
		$test = '<IMG SRC=" &#14;  javascript:alert(1);">';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa7');

		$test = '<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject bbb');

		$test = '<SCRIPT SRC=http://xss.rocks/xss.js></SCRIPT>';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject ccc');

		$test = '<IMG SRC="javascript:alert(\'XSS\');">';
		$result = testSqlAndScriptInject($test, 1);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject ddd');

		$test = '<IMG """><SCRIPT>alert("XSS")</SCRIPT>">';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject eee');

		$test = '<!-- Google analytics -->
			<script>
			  (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,\'script\',\'https://www.google-analytics.com/analytics.js\',\'ga\');

			  ga(\'create\',\'UA-99999999-9\', \'auto\');
			  ga(\'send\', \'pageview\');

			</script>';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject eee');

		$test = "<IMG SRC=\"jav\tascript:alert('XSS');\">";		// Is locked by some browser like chrome because the default directive no-referrer-when-downgrade is sent when requesting the SRC and then refused because of browser protection on img src load without referrer.
		$test = "<IMG SRC=\"jav&#x0D;ascript:alert('XSS');\">";	// Same

		$test = '<SCRIPT/XSS SRC="http://xss.rocks/xss.js"></SCRIPT>';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject fff1');
		$test = '<SCRIPT/SRC="http://xss.rocks/xss.js"></SCRIPT>';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject fff2');

		// This case seems to be filtered by browsers now.
		$test = '<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert(1)>';
		//$result=testSqlAndScriptInject($test, 0);
		//$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject ggg');

		$test = '<iframe src=http://xss.rocks/scriptlet.html <';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject hhh');

		$test = 'Set.constructor`alert\x281\x29```';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject iii');

		$test = "on<!-- ab\nc -->error=alert(1)";
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject jjj');

		$test = "<img src=x one<a>rror=alert(document.location)";
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject kkk');

		$test = "<a onpointerdown=alert(document.domain)>XSS</a>";
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject lll');

		$test = '<a onscrollend=alert(1) style="display:block;overflow:auto;border:1px+dashed;width:500px;height:100px;"><br><br><br><br><br><span+id=x>test</span></a>';	// Add the char %F6 into the variable
		$result = testSqlAndScriptInject($test, 0);
		//print "test=".$test." result=".$result."\n";
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject mmm');


		$test = "Text with ' encoded with the numeric html entity converted into text entity &#39; (like when submitted by CKEditor)";
		$result = testSqlAndScriptInject($test, 0);	// result must be 0
		$this->assertEquals(0, $result, 'Error on testSqlAndScriptInject mmm, result should be 0 and is not');

		$test = '<a href="j&Tab;a&Tab;v&Tab;asc&NewLine;ri&Tab;pt:&lpar;a&Tab;l&Tab;e&Tab;r&Tab;t&Tab;(document.cookie)&rpar;">XSS</a>';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject nnn, result should be >= 1 and is not');

		$test = "/dolibarr/htdocs/index.php/".chr('246')."abc";	// Add the char %F6 into the variable
		$result = testSqlAndScriptInject($test, 2);
		//print "test=".$test." result=".$result."\n";
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject with a non valid UTF8 char');

		$test = '<img onerror<>=alert(document.domain)';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject with an obfuscated string that bypass the WAF');

		$test = '<img onerror<abc>=alert(document.domain)';
		$result = testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject with an obfuscated string that bypass the WAF');
	}

	/**
	 * testEncodeDecode
	 *
	 * @return int
	 */
	public function testEncodeDecode()
	{
		$stringtotest = "This is a string to test encode/decode. This is a string to test encode/decode. This is a string to test encode/decode.";

		$encodedstring = dol_encode($stringtotest);
		$decodedstring = dol_decode($encodedstring);
		print __METHOD__." encodedstring=".$encodedstring." ".base64_encode($stringtotest)."\n";
		$this->assertEquals($stringtotest, $decodedstring, 'Use dol_encode/decode with no parameter');

		$encodedstring = dol_encode($stringtotest, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
		$decodedstring = dol_decode($encodedstring, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
		print __METHOD__." encodedstring=".$encodedstring." ".base64_encode($stringtotest)."\n";
		$this->assertEquals($stringtotest, $decodedstring, 'Use dol_encode/decode with a key parameter');

		return 0;
	}

	/**
	 * testDolStringOnlyTheseHtmlTags
	 *
	 * @return int
	 */
	public function testDolHTMLEntityDecode()
	{
		$stringtotest = 'a &colon; b &quot; c &#039; d &apos; e &eacute;';
		$decodedstring = dol_html_entity_decode($stringtotest, ENT_QUOTES);
		$this->assertEquals('a &colon; b " c \' d &apos; e é', $decodedstring, 'Function did not sanitize correclty');

		$stringtotest = 'a &colon; b &quot; c &#039; d &apos; e &eacute;';
		$decodedstring = dol_html_entity_decode($stringtotest, ENT_QUOTES | ENT_HTML5);
		$this->assertEquals('a : b " c \' d \' e é', $decodedstring, 'Function did not sanitize correclty');

		return 0;
	}

	/**
	 * testDolStringOnlyTheseHtmlTags
	 *
	 * @return int
	 */
	public function testDolStringOnlyTheseHtmlTags()
	{
		$stringtotest = '<a href="javascript:aaa">bbbڴ';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1);
		$this->assertEquals('<a href="aaa">bbbڴ', $decodedstring, 'Function did not sanitize correctly with test 1');

		$stringtotest = '<a href="java'.chr(0).'script:aaa">bbbڴ';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1);
		$this->assertEquals('<a href="aaa">bbbڴ', $decodedstring, 'Function did not sanitize correctly with test 2');

		$stringtotest = '<a href="javascript&colon;aaa">bbbڴ';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1);
		$this->assertEquals('<a href="aaa">bbbڴ', $decodedstring, 'Function did not sanitize correctly with test 3');

		$stringtotest = 'text <link href="aaa"> text';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1, 0, array(), 0);
		$this->assertEquals('text  text', $decodedstring, 'Function did not sanitize correctly with test 4a');

		$stringtotest = 'text <link href="aaa"> text';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1, 0, array(), 1);
		$this->assertEquals('text <link href="aaa"> text', $decodedstring, 'Function did not sanitize correctly with test 4b');

		return 0;
	}

	/**
	 * testDolStringOnlyTheseHtmlAttributes
	 *
	 * @return int
	 */
	public function testDolStringOnlyTheseHtmlAttributes()
	{
		$stringtotest = 'eée';
		$decodedstring = dol_string_onlythesehtmlattributes($stringtotest);
		$this->assertEquals('e&eacute;e', $decodedstring, 'Function did not sanitize correctly with test 1');

		$stringtotest = '<div onload="ee"><a href="123"><span class="abc">abc</span></a></div>';
		$decodedstring = dol_string_onlythesehtmlattributes($stringtotest);
		$decodedstring = preg_replace("/\n$/", "", $decodedstring);
		$this->assertEquals('<div><a href="123"><span class="abc">abc</span></a></div>', $decodedstring, 'Function did not sanitize correctly with test 2');

		return 0;
	}

	/**
	 * testGetRandomPassword
	 *
	 * @return int
	 */
	public function testGetRandomPassword()
	{
		global $conf;

		$genpass1 = getRandomPassword(true);				// Should be a string return by dol_hash (if no option set, will be md5)
		print __METHOD__." genpass1=".$genpass1."\n";
		$this->assertEquals(strlen($genpass1), 32);

		$genpass1 = getRandomPassword(true, array('I'));	// Should be a string return by dol_hash (if no option set, will be md5)
		print __METHOD__." genpass1=".$genpass1."\n";
		$this->assertEquals(strlen($genpass1), 32);

		$conf->global->USER_PASSWORD_GENERATED = 'None';
		$genpass2 = getRandomPassword(false);				// Should return an empty string
		print __METHOD__." genpass2=".$genpass2."\n";
		$this->assertEquals($genpass2, '');

		$conf->global->USER_PASSWORD_GENERATED = 'Standard';
		$genpass3 = getRandomPassword(false);				// Should return a password of 12 chars
		print __METHOD__." genpass3=".$genpass3."\n";
		$this->assertEquals(strlen($genpass3), 12);

		return 0;
	}

	/**
	 * testRestrictedArea
	 *
	 * @return void
	 */
	public function testRestrictedArea()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		//$dummyuser=new User($db);
		//$result=restrictedArea($dummyuser,'societe');

		$result = restrictedArea($user, 'societe');
		$this->assertEquals(1, $result);
	}


	/**
	 * testGetRandomPassword
	 *
	 * @return int
	 */
	public function testGetURLContent()
	{
		global $conf;
		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$url = 'ftp://mydomain.com';
		$tmp = getURLContent($url);
		print __METHOD__." url=".$url."\n";

		$tmpvar = preg_match('/not supported/', $tmp['curl_error_msg']);
		$this->assertEquals(1, $tmpvar, "Did not find the /not supported/ in getURLContent error message. We should.");

		$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
		$tmp = getURLContent($url, 'GET', '', 0);	// We do NOT follow
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(301, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url 301 response');

		$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
		$tmp = getURLContent($url);		// We DO follow a page with return 300 so result should be 200
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(200, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url 301 with a follow -> 200 but we get '.(empty($tmp['http_code']) ? 0 : $tmp['http_code']));

		$url = 'http://localhost';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL

		$url = 'http://127.0.0.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 127.0.0.1 is not an external URL

		$url = 'http://127.0.2.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 127.0.2.1 is not an external URL

		$url = 'https://169.254.0.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 169.254.0.1 is not an external URL

		$url = 'http://[::1]';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because [::1] is not an external URL

		/*$url = 'localtest.me';
		 $tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		 print __METHOD__." url=".$url."\n";
		 $this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Should GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL
		 */

		$url = 'http://192.0.0.192';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL but on an IP in blacklist
		print __METHOD__." url=".$url." tmp['http_code'] = ".(empty($tmp['http_code']) ? 0 : $tmp['http_code'])."\n";
		$this->assertEquals(400, (empty($tmp['http_code']) ? 0 : $tmp['http_code']), 'Access should be refused and was not');	// Test we receive an error because ip is in blacklist

		return 0;
	}

	/**
	 * testDolSanitizeUrl
	 *
	 * @return void
	 */
	public function testDolSanitizeUrl()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$test = 'javascripT&javascript#x3a alert(1)';
		$result = dol_sanitizeUrl($test);
		$this->assertEquals('x3a alert(1)', $result, 'Test on dol_sanitizeUrl A');

		$test = 'javajavascriptscript&cjavascriptolon;alert(1)';
		$result = dol_sanitizeUrl($test);
		$this->assertEquals('alert(1)', $result, 'Test on dol_sanitizeUrl B');

		$test = '/javas:cript/google.com';
		$result = dol_sanitizeUrl($test);
		$this->assertEquals('google.com', $result, 'Test on dol_sanitizeUrl C');
	}

	/**
	 * testDolSanitizeEmail
	 *
	 * @return void
	 */
	public function testDolSanitizeEmail()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$test = 'aaa@mycompany.com <My name>, bbb@mycompany.com <Another name>';
		$result = dol_sanitizeEmail($test);
		$this->assertEquals($test, $result, 'Test on dol_sanitizeEmail A');

		$test = "aaa@mycompany.com <My name>,\nbbb@mycompany.com <Another name>";
		$result = dol_sanitizeEmail($test);
		$this->assertEquals('aaa@mycompany.com <My name>,bbb@mycompany.com <Another name>', $result, 'Test on dol_sanitizeEmail B');

		$test = 'aaa@mycompany.com <My name>,\nbbb@mycompany.com <Another name>';
		$result = dol_sanitizeEmail($test);
		$this->assertEquals('aaa@mycompany.com <My name>,nbbb@mycompany.com <Another name>', $result, 'Test on dol_sanitizeEmail C');

		$test = 'aaa@mycompany.com <My name>, "bcc:bbb"@mycompany.com <Another name>';
		$result = dol_sanitizeEmail($test);
		$this->assertEquals('aaa@mycompany.com <My name>, bccbbb@mycompany.com <Another name>', $result, 'Test on dol_sanitizeEmail D');
	}

	/**
	 * testDolSanitizeFileName
	 *
	 * @return void
	 */
	public function testDolSanitizeFileName()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		//$dummyuser=new User($db);
		//$result=restrictedArea($dummyuser,'societe');

		$result = dol_sanitizeFileName('bad file | evilaction');
		$this->assertEquals('bad file _ evilaction', $result);

		$result = dol_sanitizeFileName('bad file -evilparam --evilparam ---evilparam ----evilparam');
		$this->assertEquals('bad file _evilparam _evilparam _evilparam _evilparam', $result);
	}

	/**
	 * testDolEval
	 *
	 * @return void
	 */
	public function testDolEval()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Declare classes found into string to evaluate
		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

		$result = dol_eval('1==\x01', 1, 0);	// Check that we can't make dol_eval on string containing \ char.
		print "result0 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result);

		$result = dol_eval('1==1', 1, 0);
		print "result1 = ".$result."\n";
		$this->assertTrue($result);

		$result = dol_eval('1==2', 1, 0);
		print "result2 = ".$result."\n";
		$this->assertFalse($result);

		$s = '((($reloadedobj = new ClassThatDoesNotExists($db)) && ($reloadedobj->fetchNoCompute($objectoffield->fk_product) > 0)) ? \'1\' : \'0\')';
		$result3a = dol_eval($s, 1, 1, '2');
		print "result3a = ".$result3a."\n";
		$this->assertEquals('Exception during evaluation: '.$s, $result3a);

		$s = '((($reloadedobj = new Project($db)) && ($reloadedobj->fetchNoCompute($objectoffield->fk_product) > 0)) ? \'1\' : \'0\')';
		$result3b = dol_eval($s, 1, 1, '2');
		print "result3b = ".$result."\n";
		$this->assertEquals('0', $result3b);

		$s = '(($reloadedobj = new Task($db)) && ($reloadedobj->fetchNoCompute($object->id) > 0) && ($secondloadedobj = new Project($db)) && ($secondloadedobj->fetchNoCompute($reloadedobj->fk_project) > 0)) ? $secondloadedobj->ref : "Parent project not found"';
		$result = (string) dol_eval($s, 1, 1, '2');
		print "result3 = ".$result."\n";
		$this->assertEquals('Parent project not found', $result);

		$s = '(($reloadedobj = new Task($db)) && ($reloadedobj->fetchNoCompute($object->id) > 0) && ($secondloadedobj = new Project($db)) && ($secondloadedobj->fetchNoCompute($reloadedobj->fk_project) > 0)) ? $secondloadedobj->ref : \'Parent project not found\'';
		$result = (string) dol_eval($s, 1, 1, '2');
		print "result4 = ".$result."\n";
		$this->assertEquals('Parent project not found', $result, 'Test 4');

		$s = '4 < 5';
		$result = (string) dol_eval($s, 1, 1, '2');
		print "result5 = ".$result."\n";
		$this->assertEquals('1', $result, 'Test 5');


		/* not allowed. Not a one line eval string
		$result = (string) dol_eval('if ($a == 1) { }', 1, 1);
		print "result4b = ".$result."\n";
		$this->assertEquals('aaa', $result);
		*/

		// Now string not allowed

		$s = '4 <5';
		$result = (string) dol_eval($s, 1, 1, '2');		// in mode 2, char < is allowed only if followed by a space
		print "result = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'Test 4 <5 - The string was not detected as evil');

		$s = '4 < 5';
		$result = (string) dol_eval($s, 1, 1, '1');		// in mode 1, char < is always forbidden
		print "result = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'Test 4 < 5 - The string was not detected as evil');

		$s = 'new abc->invoke(\'whoami\')';
		$result = (string) dol_eval($s, 1, 1, '2');
		print "result = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$s = 'new ReflectionFunction(\'abc\')';
		$result = (string) dol_eval($s, 1, 1, '2');
		print "result = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = dol_eval('$a=function() { }; $a', 1, 1, '0');		// result of dol_eval may be an object Closure
		print "result5 = ".json_encode($result)."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', json_encode($result), 'The string was not detected as evil');

		$result = dol_eval('$a=function() { }; $a();', 1, 1, '1');
		print "result6 = ".json_encode($result)."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', json_encode($result), 'The string was not detected as evil');

		$result = (string) dol_eval('$a=exec("ls");', 1, 1);
		print "result7 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = (string) dol_eval('$a=exec ("ls")', 1, 1);
		print "result8 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = (string) dol_eval("strrev('metsys') ('whoami')", 1, 1);
		print "result8b = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = (string) dol_eval('$a="test"; $$a;', 1, 0);
		print "result9 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = (string) dol_eval('`ls`', 1, 0);
		print "result10 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = (string) dol_eval("('ex'.'ec')('echo abc')", 1, 0);
		print "result11 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = (string) dol_eval("sprintf(\"%s%s\", \"ex\", \"ec\")('echo abc')", 1, 0);
		print "result12 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'The string was not detected as evil');

		$result = dol_eval("90402.38+267678+0", 1, 1, 1);
		print "result13 = ".$result."\n";
		$this->assertEquals('358080.38', $result, 'The string was not detected as evil');

		// Must be allowed

		global $leftmenu;	// Used into strings to eval

		$leftmenu = 'AAA';
		$result = dol_eval('$conf->currency && preg_match(\'/^(AAA|BBB)/\',$leftmenu)', 1, 1, '1');
		print "result = ".$result."\n";
		$this->assertTrue($result);

		// Same with a value that does not match
		$leftmenu = 'XXX';
		$result = dol_eval('$conf->currency && preg_match(\'/^(AAA|BBB)/\',$leftmenu)', 1, 1, '1');
		print "result14 = ".$result."\n";
		$this->assertFalse($result);

		$leftmenu = 'AAA';
		$result = dol_eval('$conf->currency && isStringVarMatching(\'leftmenu\', \'(AAA|BBB)\')', 1, 1, '1');
		print "result15 = ".$result."\n";
		$this->assertTrue($result);

		$leftmenu = 'XXX';
		$result = dol_eval('$conf->currency && isStringVarMatching(\'leftmenu\', \'(AAA|BBB)\')', 1, 1, '1');
		print "result16 = ".$result."\n";
		$this->assertFalse($result);

		$leftmenu = 'XXX';
		$conf->global->MAIN_FEATURES_LEVEL = 1;		// Force for the case option is -1
		$string = '(isModEnabled("agenda") || isModEnabled("resource")) && getDolGlobalInt("MAIN_FEATURES_LEVEL") >= 0 && preg_match(\'/^(admintools|all|XXX)/\', $leftmenu)';
		$result = dol_eval($string, 1, 1, '1');
		print "result17 = ".$result."\n";
		$this->assertTrue($result);

		$result = dol_eval('1 && getDolGlobalInt("doesnotexist1") && $conf->global->MAIN_FEATURES_LEVEL', 1, 0);	// Should return false and not a 'Bad string syntax to evaluate ...'
		print "result18 = ".$result."\n";
		$this->assertFalse($result);

		// Not allowed

		$a = 'ab';
		$result = (string) dol_eval("(\$a.'s')", 1, 0);
		print "result19 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'Test 19 - The string was not detected as evil');

		$leftmenu = 'abs';
		$result = (string) dol_eval('$leftmenu(-5)', 1, 0);
		print "result20 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'Test 20 - The string was not detected as evil');

		$result = (string) dol_eval('str_replace("z","e","zxzc")("whoami");', 1, 0);
		print "result21 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'Test 21 - The string was not detected as evil');

		$result = (string) dol_eval('($a = "ex") && ($b = "ec") && ($cmd = "$a$b") && $cmd ("curl localhost:5555")', 1, 0);
		print "result22 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', $result, 'Test 22 - The string was not detected as evil');

		$result = (string) dol_eval('\'exec\'("aaa")', 1, 0);
		print "result23 = ".$result."\n";
		$this->assertStringContainsString('Bad string syntax to evaluate', json_encode($result), 'Test 23 - The string was not detected as evil - Can\'t find the string Bad string syntax when i should');
	}


	/**
	 * testDolPrintHTMLAndDolPrintHtmlForAttribute.
	 * This method include calls to dol_htmlwithnojs()
	 *
	 * @return int
	 */
	public function testDolPrintHTMLAndDolPrintHtmlForAttribute()
	{
		global $conf;

		// Set options for cleaning data
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;	// disabled, does not work on HTML5 and some libxml versions
		// Enable option MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY if possible
		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;
		} else {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
		}
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;	// disabled, does not work on HTML5 and some libxml versions


		// dolPrintHTML - With dolPrintHTML(), only content not already in HTML is encoded with HTML.

		$stringtotest = "< > <b>bold</b>";
		$stringfixed = "&lt; &gt; <b>bold</b>";
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0, array())), 1, 1, 'common', 0, 1);
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTML test 1');    // Expected '' because should failed because login 'auto' does not exists

		// For a string that is already HTML (contains HTML tags) with special tags but badly formatted
		$stringtotest = "&quot; &gt; &lt; <b>bold</b>";
		$stringfixed = "&quot; &gt; &lt; <b>bold</b>";
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0, array())), 1, 1, 'common', 0, 1);
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTML test 2');    // Expected '' because should failed because login 'auto' does not exists


		// dolPrintHTMLForAttribute - With dolPrintHTMLForAttribute(), the content is HTML encode, even if it is already HTML content.

		$stringtotest = "< > <b>bold</b>";
		$stringfixed = "&lt; &gt; &lt;b&gt;bold&lt;/b&gt;";
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0, array())), 1, 1, 'common', 0, 1);
		$result = dolPrintHTMLForAttribute($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTMLForAttribute test 1');    // Expected '' because should failed because login 'auto' does not exists

		// For a string that is already HTML (contains HTML tags) with special tags but badly formatted
		$stringtotest = "&quot; &gt; &lt; <b>bold</b>";
		$stringfixed = "&amp;quot; &amp;gt; &amp;lt; &lt;b&gt;bold&lt;/b&gt;";
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0, array())), 1, 1, 'common', 0, 1);
		$result = dolPrintHTMLForAttribute($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTMLForAttribute test 2');    // Expected '' because should failed because login 'auto' does not exists


		// dolPrintHTMLForAttributeUrl - With dolPrintHTMLForAttributeUrl(), the param should already be and HTML URL encoded

		$stringtotest = "<b>aa</b> & &amp; a=%10";
		$stringfixed = "aa &amp; &amp; a=%10";
		// $result = dol_escape_htmltag(dol_string_onlythesehtmltags($s, 1, 1, 1, 0, array()), 0, 0, '', $escapeonlyhtmltags, 1);
		$result = dolPrintHTMLForAttributeUrl($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTMLForAttributeUrl test 1');    // Expected '' because should failed because login 'auto' does not exists

		// For a string that is already HTML (contains HTML tags) with special tags but badly formatted
		$stringtotest = "aa & &amp; a=%10";
		$stringfixed = "aa &amp; &amp; a=%10";
		// $result = dol_escape_htmltag(dol_string_onlythesehtmltags($s, 1, 1, 1, 0, array()), 0, 0, '', $escapeonlyhtmltags, 1);
		$result = dolPrintHTMLForAttributeUrl($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTMLForAttributeUrl test 2');    // Expected '' because should failed because login 'auto' does not exists


		// dolPrintHTML

		/*
		//return dol_escape_htmltag(dol_string_onlythesehtmltags(dol_htmlentitiesbr($s), 1, 0, 0, 0, array('br', 'b', 'font', 'hr', 'span')), 1, -1, '', 0, 1);
		$result = dolPrintHTMLForAttribute($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error in dolPrintHTML test 2');    // Expected '' because should failed because login 'auto' does not exists
		*/

		// For a string that is already HTML (contains HTML tags) with special tags but badly formatted
		$stringtotest = "testA\n<h1>hhhh</h1><z>ddd</z><header>aaa</header><footer>bbb</footer>";
		if (getDolGlobalString("MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY")) {
			$stringfixed = "testA\n<h1>hhhh</h1>\nddd\n<header>aaa</header>\n<footer>bbb</footer>\n";
		} else {
			$stringfixed = "testA\n<h1>hhhh</h1>ddd<header>aaa</header><footer>bbb</footer>";
		}
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)), 1, 1, 'common', 0, 1);
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');


		// For a string that is already HTML (contains HTML tags) but badly formatted
		$stringtotest = "testB\n<h1>hhh</h1>\n<td>td alone</td><h1>iii</h1>";
		if (getDolGlobalString("MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY")) {
			$stringfixed = "testB\n<h1>hhh</h1>\n<h1>iii</h1>\n<table>\n<tr>\n<td>td alone</td>\n</tr>\n</table>\n";
		} else {
			$stringfixed = "testB\n<h1>hhh</h1>\n<td>td alone</td><h1>iii</h1>";
		}
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');


		// For a string with no HTML tags
		$stringtotest = "testwithnewline\nsecond line";
		$stringfixed = "testwithnewline<br>\nsecond line";
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');


		// For a string with ' and &#39;
		// With no clean option
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;

		$stringtotest = "Message<br>with ' and &egrave; and &#39; !";
		/*
		var_dump($stringtotest);
		var_dump(dol_htmlentitiesbr($stringtotest));
		var_dump(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		var_dump(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)));
		var_dump(dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)), 1, 1, 'common', 0, 1));
		*/
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringtotest, $result, 'Error');


		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		// Enabled option MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY if possible
		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;
		}

		// For a string with ' and &#39;
		// With cleaning options of HTML TIDY
		if (extension_loaded('tidy') && class_exists("tidy")) {
			$stringtotest = "Message<br>with ' and &egrave; and &#39; !";
			$stringexpected = "Message<br>\nwith ' and &egrave; and ' !";		// The &#39; is modified into ' because html tidy fix it.
			/*
			var_dump($stringtotest);
			var_dump(dol_htmlentitiesbr($stringtotest));
			var_dump(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
			var_dump(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)));
			var_dump(dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)), 1, 1, 'common', 0, 1));
			*/
			$result = dolPrintHTML($stringtotest);
			print __METHOD__." result=".$result."\n";
			$this->assertEquals($stringexpected, $result, 'Error');
		}

		return 0;
	}


	/**
	 * testRealCharforNumericEntities()
	 *
	 * @return int
	 */
	public function testRealCharforNumericEntities()
	{
		global $conf;

		// Test that testRealCharforNumericEntities return an ascii char when code is inside Ascii range
		$arraytmp = array(0 => '&#97;', 1 => '97;');
		$result = realCharForNumericEntities($arraytmp);
		$this->assertEquals('a', $result);

		// Test that testRealCharforNumericEntities return an emoji utf8 char when code is inside Emoji range
		$arraytmp = array(0 => '&#9989;', 1 => '9989;');	// Encoded as decimal
		$result = realCharForNumericEntities($arraytmp);
		$this->assertEquals('✅', $result);

		$arraytmp = array(0 => '&#x2705;', 1 => 'x2705;');	// Encoded as hexadecimal
		$result = realCharForNumericEntities($arraytmp);
		$this->assertEquals('✅', $result);

		return 0;
	}


	/**
	 * testDolHtmlWithNoJs()
	 *
	 * @return int
	 */
	public function testDolHtmlWithNoJs()
	{
		global $conf;

		$sav1 = getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML');
		$sav2 = getDolGlobalString('MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY');

		// Test with an emoji
		$test = 'abc ✅ def';

		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;
		$result = dol_htmlwithnojs($test);
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;

		print __METHOD__." result for dol_htmlwithnojs and MAIN_RESTRICTHTML_ONLY_VALID_HTML=0 with emoji = ".$result."\n";
		$this->assertEquals($test, $result, 'dol_htmlwithnojs failed with an emoji when MAIN_RESTRICTHTML_ONLY_VALID_HTML=0');


		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
		$result = dol_htmlwithnojs($test);
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;

		print __METHOD__." result for dol_htmlwithnojs and MAIN_RESTRICTHTML_ONLY_VALID_HTML=1 with emoji = ".$result."\n";
		$this->assertEquals($test, $result, 'dol_htmlwithnojs failed with an emoji when MAIN_RESTRICTHTML_ONLY_VALID_HTML=1');


		// For a string with js on attribute

		// Without HTML_TIDY
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;

		$result = dol_htmlwithnojs('<img onerror=alert(document.domain) src=x>', 1, 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<img alert(document.domain) src=x>', $result, 'Test js sanitizing without tidy on');

		$result = dol_htmlwithnojs('<<r>scr<r>ipt<r>>alert("hello")<<r>&#x2f;scr<r>ipt<r>>', 1, 'restricthtml');
		//$result = dol_string_onlythesehtmltags($aa, 0, 1, 1);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('alert("hello")', $result, 'Test js sanitizing without tidy');

		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;


		// With HTML TIDY
		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;

			$result = dol_htmlwithnojs('<img onerror=alert(document.domain) src=x>', 1, 'restricthtml');
			//$result = dol_string_onlythesehtmltags($aa, 0, 1, 1);
			print __METHOD__." result=".$result."\n";
			$this->assertEquals('<img src="x">', $result, 'Test js sanitizing with tidy on');

			$result = dol_htmlwithnojs('<<r>scr<r>ipt<r>>alert("hello")<<r>&#x2f;scr<r>ipt<r>>', 1, 'restricthtml');
			//$result = dol_string_onlythesehtmltags($aa, 0, 1, 1);
			print __METHOD__." result=".$result."\n";
			$this->assertEquals('&lt;script&gt;alert("hello")&lt;/script&gt;', $result, 'Test js sanitizing with tidy on');

			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;
		}


		// For a string with js and link with restricthtmlallowlinkscript
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
		$s='<link rel="stylesheet" id="google-fonts-css" href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700">
		<link rel="stylesheet" id="font-wasesome-css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
		$result = dol_htmlwithnojs($s, 1, 'restricthtmlallowlinkscript');
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($s, $result, 'Test for restricthtmlallowlinkscript');

		// For a string with js and link with restricthtmlallowlinkscript
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;
		$s='<link rel="stylesheet" id="google-fonts-css" href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700">
		<link rel="stylesheet" id="font-wasesome-css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
		$result = dol_htmlwithnojs($s, 1, 'restricthtmlallowlinkscript');
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($s, $result, 'Test for restricthtmlallowlinkscript');

		// For a string with js and link with restricthtmlallowlinkscript
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
		$s='<link rel="stylesheet" id="google-fonts-css" href="//fonts.googleapis.com/css?family=Open+Sans:300,400,700">
		<link rel="stylesheet" id="font-wasesome-css" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>';
		$result = dol_htmlwithnojs($s, 1, 'restricthtmlallowlinkscript');
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = $sav1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = $sav2;
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($s, $result, 'Test for restricthtmlallowlinkscript');

		return 0;
	}
}
