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
 *      \file       test/phpunit/SecurityGETPOSTTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';

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
class SecurityGETPOSTTest extends CommonClassTest
{
	/**
	 * testGETPOST
	 *
	 * @return string
	 */
	public function testGETPOST()
	{
		global $conf,$user,$langs,$db,$mysoc;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		// Force default mode
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;
		$conf->global->MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 0;

		$_COOKIE["id"] = 111;
		$_POST["param0"] = 'A real string with <a href="rrr" title="aa&quot;bb">aaa</a> and " and \' and &amp; inside content';
		$_GET["param1"] = "222";
		$_POST["param1"] = "333";
		$_GET["param2"] = 'a/b#e(pr)qq-rr\cc';
		$_GET["param3"] = '"&#110;a/b#e(pr)qq-rr\cc';    // Same than param2 + " and &#110;
		$_GET["param4a"] = '..&#47;../dir';
		$_GET["param4b"] = '..&#92;..\dirwindows';
		$_GET["param4c"] = '\a123 \123 \u123 \x123';
		$_GET["param5"] = "a_1-b";
		$_POST["param6"] = "&quot;&gt;<svg o&#110;load='console.log(&quot;123&quot;)'&gt;";
		$_POST["param6b"] = '<<<../>../>../svg><<<../>../>../animate =alert(1)>abc';
		$_GET["param7"] = '"c:\this is a path~1\aaa&#110; &#x&#x31;&#x31;&#x30;;" abc<bad>def</bad>';
		$_POST["param8a"] = "Hacker<svg o&#110;load='console.log(&quot;123&quot;)'";	// html tag is not closed so it is not detected as html tag but is still harmfull
		$_POST['param8b'] = '<img src=x onerror=alert(document.location) t=';		// this is html obfuscated by non closing tag
		$_POST['param8c'] = '< with space after is ok';
		$_POST['param8d'] = '<abc123 is html to clean';
		$_POST['param8e'] = '<123abc is not html to clean';	// other similar case: '<2021-12-12'
		$_POST['param8f'] = 'abc<<svg <><<animate onbegin=alert(document.domain) a';
		$_POST["param9"] = 'is_object($object) ? ($object->id < 10 ? round($object->id / 2, 2) : (2 * $user->id) * (int) substr($mysoc->zip, 1, 2)) : \'objnotdefined\'';
		$_POST["param10"] = 'is_object($object) ? ($object->id < 10 ? round($object->id / 2, 2) : (2 * $user->id) * (int) substr($mysoc->zip, 1, 2)) : \'<abc>objnotdefined\'';
		$_POST["param11"] = ' Name <email@email.com> ';
		$_POST["param12"] = '<!DOCTYPE html><html>aaa</html>';
		$_POST["param13"] = '&#110; &#x6E; &gt; &lt; &quot; <a href=\"j&#x61;vascript:alert(document.domain)\">XSS</a>';
		$_POST["param13b"] = '&#110; &#x6E; &gt; &lt; &quot; <a href=\"j&#x61vascript:alert(document.domain)\">XSS</a>';
		$_POST["param13c"] = 'aaa:<:bbb';
		$_POST["param14"] = "Text with ' encoded with the numeric html entity converted into text entity &#39; (like when submitted by CKEditor)";
		$_POST["param15"] = "<img onxxxx<=alert(document.domain)> src=>0xbeefed";
		$_POST["param15b"] = "<img onerror<=alert(document.domain)> src=>0xbeefed";
		$_POST["param16"] = '<a style="z-index: 1000">abc</a>';
		$_POST["param17"] = '<span style="background-image: url(logout.php)">abc</span>';
		$_POST["param18"] = '<span style="background-image: url(...?...action=aaa)">abc</span>';
		$_POST["param19"] = '<a href="j&Tab;a&Tab;v&Tab;asc&NewLine;ri&Tab;pt:&lpar;alert(document.cookie)&rpar;">XSS</a>';
		//$_POST["param19"]='<a href="javascript:alert(document.cookie)">XSS</a>';
		$_GET["param20"] = '<link rel="dns-prefetch" href="//cdnjs.cloudflare.com" />';



		$result = GETPOST('id', 'int');              // Must return nothing
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result = GETPOST("param1", 'int');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(222, $result, 'Test on param1 with no 3rd param');

		$result = GETPOST("param1", 'int', 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(333, $result, 'Test on param1 with 3rd param = 2');

		// Test with alpha

		$result = GETPOST("param0", 'alpha');		// a simple format, so " completely removed
		$resultexpected = 'A real string with aaa and and \' and & inside content';
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($resultexpected, $result, 'Test on param0');

		$result = GETPOST("param2", 'alpha');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('a/b#e(pr)qq-rr\cc', $result, 'Test on param2');

		$result = GETPOST("param3", 'alpha');  // Must return string sanitized from char "
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('na/b#e(pr)qq-rr\cc', $result, 'Test on param3');

		$result = GETPOST("param4a", 'alpha');  // Must return string sanitized from ../
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dir', $result);

		$result = GETPOST("param4b", 'alpha');  // Must return string sanitized from ../
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('dirwindows', $result);

		$result = GETPOST("param4c", 'alpha');  // Must return string sanitized from ../
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('\a123 /123 /u123 /x123', $result);

		// Test with aZ09

		$result = GETPOST("param1", 'aZ09');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, $_GET["param1"]);

		$result = GETPOST("param2", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '');

		$result = GETPOST("param3", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '');

		$result = GETPOST("param4a", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result = GETPOST("param4b", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result = GETPOST("param5", 'aZ09');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_GET["param5"], $result);

		// Test with nohtml

		$result = GETPOST("param6", 'nohtml');
		print __METHOD__." result6=".$result."\n";
		$this->assertEquals('">', $result);

		// Test with alpha = alphanohtml. We must convert the html entities like &#110; and disable all entities

		$result = GETPOST("param6", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('>', $result);

		$result = GETPOST("param6b", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abc', $result);

		$result = GETPOST("param8a", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("Hackersvg onload='console.log(123)'", $result);

		$result = GETPOST("param8b", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('img src=x onerror=alert(document.location) t=', $result, 'Test a string with non closing html tag with alphanohtml');

		$result = GETPOST("param8c", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST['param8c'], $result, 'Test a string with non closing html tag with alphanohtml');

		$result = GETPOST("param8d", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abc123 is html to clean', $result, 'Test a string with non closing html tag with alphanohtml');

		$result = GETPOST("param8e", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST['param8e'], $result, 'Test a string with non closing html tag with alphanohtml');

		$result = GETPOST("param8f", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abcsvg animate onbegin=alert(document.domain) a', $result, 'Test a string with html tag open with several <');

		$result = GETPOST("param9", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST["param9"], $result);

		$result = GETPOST("param10", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST["param9"], $result, 'We should get param9 after processing param10');

		$result = GETPOST("param11", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("Name", $result, 'Test an email string with alphanohtml');

		$result = GETPOST("param13", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('n n > <  XSS', $result, 'Test that html entities are decoded with alpha');

		$result = GETPOST("param13c", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('aaa:<:bbb', $result, 'Test 13c');


		// Test with alphawithlgt

		$result = GETPOST("param11", 'alphawithlgt');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(trim($_POST["param11"]), $result, 'Test an email string with alphawithlgt');


		// Test with restricthtml: we must remove html open/close tag and content but not htmlentities (we can decode html entities for ascii chars like &#110;)

		$result = GETPOST("param0", 'restricthtml');
		$resultexpected = 'A real string with <a href="rrr" title="aa&quot;bb">aaa</a> and " and \' and &amp; inside content';
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($resultexpected, $result, 'Test on param0');

		$result = GETPOST("param6", 'restricthtml');
		print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
		$this->assertEquals('&quot;&gt;', $result);

		$result = GETPOST("param7", 'restricthtml');
		print __METHOD__." result param7 = ".$result."\n";
		$this->assertEquals('"c:\this is a path~1\aaan &#x;;;;" abcdef', $result);

		$result = GETPOST("param8e", 'restricthtml');
		print __METHOD__." result param8e = ".$result."\n";
		$this->assertEquals('', $result);

		$result = GETPOST("param12", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(trim($_POST["param12"]), $result, 'Test a string with DOCTYPE and restricthtml');

		$result = GETPOST("param13", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('n n &gt; &lt; &quot; <a href=\"alert(document.domain)\">XSS</a>', $result, 'Test 13 that HTML entities are decoded with restricthtml, but only for common alpha chars');

		$result = GETPOST("param13b", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('n n &gt; &lt; &quot; <a href=\"alert(document.domain)\">XSS</a>', $result, 'Test 13b that HTML entities are decoded with restricthtml, but only for common alpha chars');

		$result = GETPOST("param14", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("Text with ' encoded with the numeric html entity converted into text entity &#39; (like when submitted by CKEditor)", $result, 'Test 14');

		$result = GETPOST("param15", 'restricthtml');		// param15 = <img onxxxx<=alert(document.domain)> src=>0xbeefed that is a dangerous string
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("<img onxxxx=alert(document.domain) src=>0xbeefed", $result, 'Test 15');	// The GETPOST return a harmull string

		$result = GETPOST("param15b", 'restricthtml');		// param15b = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("<img alert(document.domain) src=>0xbeefed", $result, 'Test 15b');	// The GETPOST return a harmull string

		$result = GETPOST("param19", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<a href="&lpar;alert(document.cookie)&rpar;">XSS</a>', $result, 'Test 19');


		// Test with restricthtml + MAIN_RESTRICTHTML_ONLY_VALID_HTML only to test disabling of bad attributes

		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;

		//$_POST["param0"] = 'A real string with <a href="rrr" title="aabb">aaa</a> and " inside content';
		$result = GETPOST("param0", 'restricthtml');
		$resultexpected = 'A real string with <a href="rrr" title=\'aa"bb\'>aaa</a> and " and \' and &amp; inside content';
		print __METHOD__." result for param0=".$result."\n";
		$this->assertEquals($resultexpected, $result, 'Test on param0');

		$result = GETPOST("param15b", 'restricthtml');		// param15b = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
		print __METHOD__." result for param15b=".$result."\n";
		//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
		//$this->assertEquals('<img onerror> src=&gt;0xbeefed', $result, 'Test 15b');	// ... on other PHP and libxml versions, we got a HTML that has been cleaned

		$result = GETPOST("param6", 'restricthtml');		// param6 = "&quot;&gt;<svg o&#110;load='console.log(&quot;123&quot;)'&gt;"
		print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
		//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
		//$this->assertEquals('"&gt;', $result);										// ... on other PHP and libxml versions, we got a HTML that has been cleaned

		$result = GETPOST("param7", 'restricthtml');		// param7 = "c:\this is a path~1\aaa&#110; &#x&#x31;&#x31;&#x30;;" abc<bad>def</bad>
		print __METHOD__." result param7 = ".$result."\n";
		//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
		//$this->assertEquals('"c:\this is a path~1\aaan 110;" abcdef', $result);		// ... on other PHP and libxml versions, we got a HTML that has been cleaned


		// Test with restricthtml + MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY only to test disabling of bad attributes

		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;

			$result = GETPOST("param0", 'restricthtml');
			$resultexpected = 'A real string with <a href="rrr" title="aa&quot;bb">aaa</a> and " and \' and & inside content';
			print __METHOD__." result for param0=".$result."\n";
			$this->assertEquals($resultexpected, $result, 'Test on param0');

			$result = GETPOST("param15b", 'restricthtml');		// param15b = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
			print __METHOD__." result for param15b=".$result."\n";
			//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
			//$this->assertEquals('<img onerror> src=&gt;0xbeefed', $result, 'Test 15b');	// ... on other PHP and libxml versions, we got a HTML that has been cleaned

			$result = GETPOST("param6", 'restricthtml');
			print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
			//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
			$this->assertEquals('"&gt;', $result);

			$result = GETPOST("param7", 'restricthtml');
			print __METHOD__." result param7 = ".$result."\n";
			//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
			$this->assertEquals('"c:\this is a path~1\aaan &amp;#x110;" abcdef', $result);
		}


		// Test with restricthtml + MAIN_RESTRICTHTML_ONLY_VALID_HTML + MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY to test disabling of bad attributes

		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;

			$result = GETPOST("param0", 'restricthtml');
			$resultexpected = 'A real string with <a href="rrr" title=\'aa"bb\'>aaa</a> and " and \' and & inside content';
			print __METHOD__." result for param0=".$result."\n";
			$this->assertEquals($resultexpected, $result, 'Test on param0');

			$result = GETPOST("param15b", 'restricthtml');		// param15b = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
			print __METHOD__." result=".$result."\n";
			//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
			//$this->assertEquals('<img onerror> src=&gt;0xbeefed', $result, 'Test 15b');	// ... on other PHP and libxml versions, we got a HTML that has been cleaned

			$result = GETPOST("param6", 'restricthtml');
			print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
			//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
			$this->assertEquals('"&gt;', $result);

			$result = GETPOST("param7", 'restricthtml');
			print __METHOD__." result param7 = ".$result."\n";
			//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');   // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
			$this->assertEquals('"c:\this is a path~1\aaan 110;" abcdef', $result);
		}


		// Test with restricthtml + MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES to test disabling of bad attributes

		unset($conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML);
		unset($conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY);
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 1;

		$result = GETPOST("param15", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<img src="">0xbeefed', $result, 'Test 15c');

		$result = GETPOST('param16', 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<a style=" 1000">abc</a>', $result, 'Test tag a with forbidden attribute z-index');

		$result = GETPOST('param17', 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<span style="background-image: url()">abc</span>', $result, 'Test anytag with a forbidden value for attribute');

		$result = GETPOST('param18', 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<span style="background-image: url(...?...aaa)">abc</span>', $result, 'Test anytag with a forbidden value for attribute');

		$result = GETPOST("param20", 'restricthtmlallowlinkscript');
		print __METHOD__." result param20 = ".$result."\n";
		$this->assertEquals('<link rel="dns-prefetch" href="//cdnjs.cloudflare.com">', $result);


		unset($conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES);


		// Special test for GETPOST of backtopage, backtolist or backtourl parameter

		$_POST["backtopage"] = '//www.google.com';
		$result = GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('www.google.com', $result, 'Test for backtopage param');

		$_POST["backtopage"] = 'https:https://www.google.com';
		$result = GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('www.google.com', $result, 'Test for backtopage param');

		$_POST["backtolist"] = '::HTTPS://www.google.com';
		$result = GETPOST("backtolist");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('www.google.com', $result, 'Test for backtopage param');

		$_POST["backtopage"] = 'http:www.google.com';
		$result = GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('httpwww.google.com', $result, 'Test for backtopage param');

		$_POST["backtopage"] = '/mydir/mypage.php?aa=a%10a';
		$result = GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('/mydir/mypage.php?aa=a%10a', $result, 'Test for backtopage param');

		$_POST["backtopage"] = 'javascripT&javascript#javascriptxjavascript3a alert(1)';
		$result = GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('x3aalert(1)', $result, 'Test for backtopage param');


		$conf->global->MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT = 3;
		$_POST["pagecontentwithlinks"] = '<img src="aaa"><img src="bbb"><img src="/ccc"><span style="background: url(/ddd)"></span>';
		$result = GETPOST("pagecontentwithlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('ErrorTooManyLinksIntoHTMLString', $result, 'Test on limit on GETPOST fails');

		// Test that img src="data:..." is excluded from the count of external links
		$conf->global->MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT = 3;
		$_POST["pagecontentwithlinks"] = '<img src="data:abc"><img src="bbb"><img src="/ccc"><span style="background: url(/ddd)"></span>';
		$result = GETPOST("pagecontentwithlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<img src="data:abc"><img src="bbb"><img src="/ccc"><span style="background: url(/ddd)"></span>', $result, 'Test on limit on GETPOST fails');

		$conf->global->MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 2;

		// Test that no links is allowed
		$_POST["pagecontentwithlinks"] = '<img src="data:abc"><img src="bbb"><img src="/ccc"><span style="background: url(/ddd)"></span>';
		$result = GETPOST("pagecontentwithlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('ErrorHTMLLinksNotAllowed', $result, 'Test on limit on MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 2 (no links allowed)');

		$conf->global->MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 1;

		// Test that links on wrapper or local url are allowed
		$_POST["pagecontentwithnowrapperlinks"] = '<img src="data:abc"><img src="bbb"><img src="/ccc"><span style="background: url(/ddd)"></span>';
		$result = GETPOST("pagecontentwithnowrapperlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<img src="data:abc"><img src="bbb"><img src="/ccc"><span style="background: url(/ddd)"></span>', $result, 'Test on MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 1 (links on data or relative links ar allowed)');

		// Test that links not on wrapper and not data are disallowed
		$_POST["pagecontentwithnowrapperlinks"] = '<img src="https://aaa">';
		$result = GETPOST("pagecontentwithnowrapperlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('ErrorHTMLExternalLinksNotAllowed', $result, 'Test on MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 1 (no links to http allowed)');

		// Test that links not on wrapper and not data are disallowed
		$_POST["pagecontentwithnowrapperlinks"] = '<span style="background: url(http://ddd)"></span>';
		$result = GETPOST("pagecontentwithnowrapperlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('ErrorHTMLExternalLinksNotAllowed', $result, 'Test on MAIN_DISALLOW_URL_INTO_DESCRIPTIONS = 1 (no links to http allowed)');


		// Test substitution in GET url
		$user->fk_user = 999;
		$mysoc->country_id = 1;
		$_GET['paramtestsubstit'] = 'XXX __NOTDEFINED__ XXX __USER_SUPERVISOR_ID__ XXX __MYCOMPANY_COUNTRY_ID__ XXX  __MYCOUNTRY_ID__ XXX';

		// Test that links not on wrapper and not data are disallowed
		$result = GETPOST("paramtestsubstit", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('XXX XXX 999 XXX 1 XXX 1 XXX', $result, 'Failed to do conversion');


		return $result;
	}
}
