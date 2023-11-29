<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require_once dirname(__FILE__).'/../../htdocs/main.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';


if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS=1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class SecurityTest extends PHPUnit\Framework\TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @param 	string	$name		Name
	 * @return SecurityTest
	 */
	public function __construct($name = '')
	{
		parent::__construct($name);

		//$this->sharedFixture
		global $conf,$user,$langs,$db;
		$this->savconf=$conf;
		$this->savuser=$user;
		$this->savlangs=$langs;
		$this->savdb=$db;

		print __METHOD__." db->type=".$db->type." user->id=".$user->id;
		//print " - db ".$db->db;
		print "\n";
	}

	/**
	 * setUpBeforeClass
	 *
	 * @return void
	 */
	public static function setUpBeforeClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

		print __METHOD__."\n";
	}

	/**
	 * tearDownAfterClass
	 *
	 * @return	void
	 */
	public static function tearDownAfterClass(): void
	{
		global $conf,$user,$langs,$db;
		$db->rollback();

		print __METHOD__."\n";
	}

	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
	protected function setUp(): void
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		print __METHOD__."\n";
	}

	/**
	 * End phpunit tests
	 *
	 * @return	void
	 */
	protected function tearDown(): void
	{
		print __METHOD__."\n";
	}

	/**
	 * testSetLang
	 *
	 * @return string
	 */
	public function testSetLang()
	{
		global $conf;
		$conf=$this->savconf;

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
		$expectedresult=0;

		/*
		$test = '';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual(0, $result, 'Error on testSqlAndScriptInject kkk');
		*/

		$_SERVER["PHP_SELF"]='/DIR WITH SPACE/htdocs/admin/index.php';
		$result=testSqlAndScriptInject($_SERVER["PHP_SELF"], 2);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for PHP_SELF that should be ok');

		$test = 'This is a < inside string with < and > also and tag like <a> before the >';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject expected 0b');

		$test = 'This is the union of all for the selection of the best';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject expected 0c');

		$test='/user/perms.php?id=1&action=addrights&entity=1&rights=123&confirm=yes&token=123456789&updatedmodulename=lmscoursetracking';
		$result=testSqlAndScriptInject($test, 1);
		print "test=".$test." result=".$result."\n";
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject with a valid url');

		// Should detect attack
		$expectedresult=1;

		$_SERVER["PHP_SELF"]='/DIR WITH SPACE/htdocs/admin/index.php/<svg>';
		$result=testSqlAndScriptInject($_SERVER["PHP_SELF"], 2);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject for PHP_SELF that should detect XSS');

		$test = 'select @@version';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL1a. Should find an attack on POST param and did not.');

		$test = 'select @@version';
		$result=testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL1b. Should find an attack on GET param and did not.');

		$test = '... update ... set ... =';
		$result=testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL2a. Should find an attack on GET param and did not.');

		$test = "delete\nfrom";
		$result=testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL2b. Should find an attack on GET param and did not.');

		$test = 'action=update& ... set ... =';
		$result=testSqlAndScriptInject($test, 1);
		$this->assertEquals(0, $result, 'Error on testSqlAndScriptInject for SQL2b. Should not find an attack on GET param and did.');

		$test = '... union ... selection ';
		$result=testSqlAndScriptInject($test, 1);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for SQL2c. Should find an attack on GET param and did not.');

		$test = 'j&#x61;vascript:';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for javascript1. Should find an attack and did not.');

		$test = 'j&#x61vascript:';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for javascript2. Should find an attack and did not.');

		$test = 'javascript&colon&#x3B;alert(1)';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject for javascript2');

		$test="<img src='1.jpg' onerror =javascript:alert('XSS')>";
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa1');

		$test="<img src='1.jpg' onerror =javascript:alert('XSS')>";
		$result=testSqlAndScriptInject($test, 2);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa2');

		$test='<IMG SRC=# onmouseover="alert(1)">';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa3');
		$test='<IMG SRC onmouseover="alert(1)">';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa4');
		$test='<IMG onmouseover="alert(1)">';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa5');
		$test='<IMG SRC=/ onerror="alert(1)">';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa6');
		$test='<IMG SRC=" &#14;  javascript:alert(1);">';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa7');

		$test='<IMG SRC=&#106;&#97;&#118;&#97;&#115;&#99;&#114;&#105;&#112;&#116;&#58;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#88;&#83;&#83;&#39;&#41;>';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject bbb');

		$test='<SCRIPT SRC=http://xss.rocks/xss.js></SCRIPT>';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject ccc');

		$test='<IMG SRC="javascript:alert(\'XSS\');">';
		$result=testSqlAndScriptInject($test, 1);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject ddd');

		$test='<IMG """><SCRIPT>alert("XSS")</SCRIPT>">';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject eee');

		$test='<!-- Google analytics -->
			<script>
			  (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
			  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
			  })(window,document,\'script\',\'https://www.google-analytics.com/analytics.js\',\'ga\');

			  ga(\'create\',\'UA-99999999-9\', \'auto\');
			  ga(\'send\', \'pageview\');

			</script>';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject eee');

		$test="<IMG SRC=\"jav\tascript:alert('XSS');\">";		// Is locked by some browser like chrome because the default directive no-referrer-when-downgrade is sent when requesting the SRC and then refused because of browser protection on img src load without referrer.
		$test="<IMG SRC=\"jav&#x0D;ascript:alert('XSS');\">";	// Same

		$test='<SCRIPT/XSS SRC="http://xss.rocks/xss.js"></SCRIPT>';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject fff1');
		$test='<SCRIPT/SRC="http://xss.rocks/xss.js"></SCRIPT>';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject fff2');

		// This case seems to be filtered by browsers now.
		$test='<BODY onload!#$%&()*~+-_.,:;?@[/|\]^`=alert(1)>';
		//$result=testSqlAndScriptInject($test, 0);
		//$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject ggg');

		$test='<iframe src=http://xss.rocks/scriptlet.html <';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject hhh');

		$test='Set.constructor`alert\x281\x29```';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject iii');

		$test="on<!-- ab\nc -->error=alert(1)";
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject jjj');

		$test="<img src=x one<a>rror=alert(document.location)";
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject kkk');

		$test="<a onpointerdown=alert(document.domain)>XSS</a>";
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject lll');

		$test='<a onscrollend=alert(1) style="display:block;overflow:auto;border:1px+dashed;width:500px;height:100px;"><br><br><br><br><br><span+id=x>test</span></a>';	// Add the char %F6 into the variable
		$result=testSqlAndScriptInject($test, 0);
		//print "test=".$test." result=".$result."\n";
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject mmm');


		$test="Text with ' encoded with the numeric html entity converted into text entity &#39; (like when submited by CKEditor)";
		$result=testSqlAndScriptInject($test, 0);	// result must be 0
		$this->assertEquals(0, $result, 'Error on testSqlAndScriptInject mmm, result should be 0 and is not');

		$test ='<a href="j&Tab;a&Tab;v&Tab;asc&NewLine;ri&Tab;pt:&lpar;a&Tab;l&Tab;e&Tab;r&Tab;t&Tab;(document.cookie)&rpar;">XSS</a>';
		$result=testSqlAndScriptInject($test, 0);
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject nnn, result should be >= 1 and is not');

		$test="/dolibarr/htdocs/index.php/".chr('246')."abc";	// Add the char %F6 into the variable
		$result=testSqlAndScriptInject($test, 2);
		//print "test=".$test." result=".$result."\n";
		$this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject with a non valid UTF8 char');
	}

	/**
	 * testGETPOST
	 *
	 * @return string
	 */
	public function testGETPOST()
	{
		global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		// Force default mode
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 0;

		$_COOKIE["id"]=111;
		$_POST["param0"]='A real string with <a href="rrr" title="aa&quot;bb">aaa</a> and " and \' and &amp; inside content';
		$_GET["param1"]="222";
		$_POST["param1"]="333";
		$_GET["param2"]='a/b#e(pr)qq-rr\cc';
		$_GET["param3"]='"&#110;a/b#e(pr)qq-rr\cc';    // Same than param2 + " and &#110;
		$_GET["param4a"]='..&#47;../dir';
		$_GET["param4b"]='..&#92;..\dirwindows';
		$_GET["param5"]="a_1-b";
		$_POST["param6"]="&quot;&gt;<svg o&#110;load='console.log(&quot;123&quot;)'&gt;";
		$_POST["param6b"]='<<<../>../>../svg><<<../>../>../animate =alert(1)>abc';
		$_GET["param7"]='"c:\this is a path~1\aaa&#110; &#x&#x31;&#x31;&#x30;;" abc<bad>def</bad>';
		$_POST["param8a"]="Hacker<svg o&#110;load='console.log(&quot;123&quot;)'";	// html tag is not closed so it is not detected as html tag but is still harmfull
		$_POST['param8b']='<img src=x onerror=alert(document.location) t=';		// this is html obfuscated by non closing tag
		$_POST['param8c']='< with space after is ok';
		$_POST['param8d']='<abc123 is html to clean';
		$_POST['param8e']='<123abc is not html to clean';	// other similar case: '<2021-12-12'
		$_POST['param8f']='abc<<svg <><<animate onbegin=alert(document.domain) a';
		$_POST["param9"]='is_object($object) ? ($object->id < 10 ? round($object->id / 2, 2) : (2 * $user->id) * (int) substr($mysoc->zip, 1, 2)) : \'objnotdefined\'';
		$_POST["param10"]='is_object($object) ? ($object->id < 10 ? round($object->id / 2, 2) : (2 * $user->id) * (int) substr($mysoc->zip, 1, 2)) : \'<abc>objnotdefined\'';
		$_POST["param11"]=' Name <email@email.com> ';
		$_POST["param12"]='<!DOCTYPE html><html>aaa</html>';
		$_POST["param13"]='&#110; &#x6E; &gt; &lt; &quot; <a href=\"j&#x61;vascript:alert(document.domain)\">XSS</a>';
		$_POST["param13b"]='&#110; &#x6E; &gt; &lt; &quot; <a href=\"j&#x61vascript:alert(document.domain)\">XSS</a>';
		$_POST["param14"]="Text with ' encoded with the numeric html entity converted into text entity &#39; (like when submited by CKEditor)";
		$_POST["param15"]="<img onerror<=alert(document.domain)> src=>0xbeefed";
		//$_POST["param15b"]="<html><head><title>Example HTML</title></head><body><div><p>This is a paragraph.</div><ul><li>Item 1</li><li>Item 2</li></ol></body><html>";
		$_POST["param16"]='<a style="z-index: 1000">abc</a>';
		$_POST["param17"]='<span style="background-image: url(logout.php)">abc</span>';
		$_POST["param18"]='<span style="background-image: url(...?...action=aaa)">abc</span>';
		$_POST["param19"]='<a href="j&Tab;a&Tab;v&Tab;asc&NewLine;ri&Tab;pt:&lpar;alert(document.cookie)&rpar;">XSS</a>';
		//$_POST["param19"]='<a href="javascript:alert(document.cookie)">XSS</a>';



		$result=GETPOST('id', 'int');              // Must return nothing
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result=GETPOST("param1", 'int');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(222, $result, 'Test on param1 with no 3rd param');

		$result=GETPOST("param1", 'int', 2);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(333, $result, 'Test on param1 with 3rd param = 2');

		// Test with alpha

		$result=GETPOST("param0", 'alpha');		// a simple format, so " completely removed
		$resultexpected = 'A real string with aaa and and \' and & inside content';
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($resultexpected, $result, 'Test on param0');

		$result=GETPOST("param2", 'alpha');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, $_GET["param2"], 'Test on param2');

		$result=GETPOST("param3", 'alpha');  // Must return string sanitized from char "
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'na/b#e(pr)qq-rr\cc', 'Test on param3');

		$result=GETPOST("param4a", 'alpha');  // Must return string sanitized from ../
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'dir');

		$result=GETPOST("param4b", 'alpha');  // Must return string sanitized from ../
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'dirwindows');

		// Test with aZ09

		$result=GETPOST("param1", 'aZ09');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, $_GET["param1"]);

		$result=GETPOST("param2", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '');

		$result=GETPOST("param3", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '');

		$result=GETPOST("param4a", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result=GETPOST("param4b", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('', $result);

		$result=GETPOST("param5", 'aZ09');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_GET["param5"], $result);

		// Test with nohtml

		$result=GETPOST("param6", 'nohtml');
		print __METHOD__." result6=".$result."\n";
		$this->assertEquals('">', $result);

		// Test with alpha = alphanohtml. We must convert the html entities like &#110; and disable all entities

		$result=GETPOST("param6", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('>', $result);

		$result=GETPOST("param6b", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abc', $result);

		$result=GETPOST("param8a", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("Hackersvg onload='console.log(123)'", $result);

		$result=GETPOST("param8b", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('img src=x onerror=alert(document.location) t=', $result, 'Test a string with non closing html tag with alphanohtml');

		$result=GETPOST("param8c", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST['param8c'], $result, 'Test a string with non closing html tag with alphanohtml');

		$result=GETPOST("param8d", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abc123 is html to clean', $result, 'Test a string with non closing html tag with alphanohtml');

		$result=GETPOST("param8e", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST['param8e'], $result, 'Test a string with non closing html tag with alphanohtml');

		$result=GETPOST("param8f", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('abcsvg animate onbegin=alert(document.domain) a', $result, 'Test a string with html tag open with several <');

		$result=GETPOST("param9", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST["param9"], $result);

		$result=GETPOST("param10", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($_POST["param9"], $result, 'We should get param9 after processing param10');

		$result=GETPOST("param11", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("Name", $result, 'Test an email string with alphanohtml');

		$result=GETPOST("param13", 'alphanohtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('n n > <  XSS', $result, 'Test that html entities are decoded with alpha');


		// Test with alphawithlgt

		$result=GETPOST("param11", 'alphawithlgt');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(trim($_POST["param11"]), $result, 'Test an email string with alphawithlgt');


		// Test with restricthtml: we must remove html open/close tag and content but not htmlentities (we can decode html entities for ascii chars like &#110;)

		$result=GETPOST("param0", 'restricthtml');
		$resultexpected = 'A real string with <a href="rrr" title="aa&quot;bb">aaa</a> and " and \' and &amp; inside content';
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($resultexpected, $result, 'Test on param0');

		$result=GETPOST("param6", 'restricthtml');
		print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
		$this->assertEquals('&quot;&gt;', $result);

		$result=GETPOST("param7", 'restricthtml');
		print __METHOD__." result param7 = ".$result."\n";
		$this->assertEquals('"c:\this is a path~1\aaan &#x;;;;" abcdef', $result);

		$result=GETPOST("param8e", 'restricthtml');
		print __METHOD__." result param8e = ".$result."\n";
		$this->assertEquals('', $result);

		$result=GETPOST("param12", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals(trim($_POST["param12"]), $result, 'Test a string with DOCTYPE and restricthtml');

		$result=GETPOST("param13", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('n n &gt; &lt; &quot; <a href=\"alert(document.domain)\">XSS</a>', $result, 'Test 13 that HTML entities are decoded with restricthtml, but only for common alpha chars');

		$result=GETPOST("param13b", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('n n &gt; &lt; &quot; <a href=\"alert(document.domain)\">XSS</a>', $result, 'Test 13b that HTML entities are decoded with restricthtml, but only for common alpha chars');

		$result=GETPOST("param14", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("Text with ' encoded with the numeric html entity converted into text entity &#39; (like when submited by CKEditor)", $result, 'Test 14');

		$result=GETPOST("param15", 'restricthtml');		// param15 = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
		print __METHOD__." result=".$result."\n";
		$this->assertEquals("<img onerror=alert(document.domain) src=>0xbeefed", $result, 'Test 15');	// The GETPOST return a harmull string

		$result=GETPOST("param19", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<a href="&lpar;alert(document.cookie)&rpar;">XSS</a>', $result, 'Test 19');


		// Test with restricthtml + MAIN_RESTRICTHTML_ONLY_VALID_HTML only to test disabling of bad atrributes

		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 0;

		//$_POST["param0"] = 'A real string with <a href="rrr" title="aabb">aaa</a> and " inside content';
		$result=GETPOST("param0", 'restricthtml');
		$resultexpected = 'A real string with <a href="rrr" title=\'aa"bb\'>aaa</a> and " and \' and &amp; inside content';
		print __METHOD__." result for param0=".$result."\n";
		$this->assertEquals($resultexpected, $result, 'Test on param0');

		$result=GETPOST("param15", 'restricthtml');		// param15 = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
		print __METHOD__." result for param15=".$result."\n";
		//$this->assertEquals('InvalidHTMLStringCantBeCleaned', $result, 'Test 15b');                 // With some PHP and libxml version, we got this result when parsing invalid HTML, but ...
		//$this->assertEquals('<img onerror> src=&gt;0xbeefed', $result, 'Test 15b');	// ... on other PHP and libxml versions, we got a HTML that has been cleaned

		$result=GETPOST("param6", 'restricthtml');		// param6 = "&gt;
		print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
		$this->assertEquals('"&gt;', $result);

		$result=GETPOST("param7", 'restricthtml');		// param7 = "c:\this is a path~1\aaa&#110; &#x&#x31;&#x31;&#x30;;" abc<bad>def</bad>
		print __METHOD__." result param7 = ".$result."\n";
		$this->assertEquals('"c:\this is a path~1\aaan 110;" abcdef', $result);


		// Test with restricthtml + MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY only to test disabling of bad atrributes

		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 0;
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;

			$result=GETPOST("param0", 'restricthtml');
			$resultexpected = 'A real string with <a href="rrr" title="aa&quot;bb">aaa</a> and " and \' and & inside content';
			print __METHOD__." result for param0=".$result."\n";
			$this->assertEquals($resultexpected, $result, 'Test on param0');

			$result=GETPOST("param15", 'restricthtml');		// param15 = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
			print __METHOD__." result=".$result."\n";

			$result=GETPOST("param6", 'restricthtml');
			print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
			$this->assertEquals('"&gt;', $result);

			$result=GETPOST("param7", 'restricthtml');
			print __METHOD__." result param7 = ".$result."\n";
			$this->assertEquals('"c:\this is a path~1\aaan &amp;#x110;" abcdef', $result);
		}


		// Test with restricthtml + MAIN_RESTRICTHTML_ONLY_VALID_HTML + MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY to test disabling of bad atrributes

		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;

			$result=GETPOST("param0", 'restricthtml');
			$resultexpected = 'A real string with <a href="rrr" title=\'aa"bb\'>aaa</a> and " and \' and & inside content';
			print __METHOD__." result for param0=".$result."\n";
			$this->assertEquals($resultexpected, $result, 'Test on param0');

			$result=GETPOST("param15", 'restricthtml');		// param15 = <img onerror<=alert(document.domain)> src=>0xbeefed that is a dangerous string
			print __METHOD__." result=".$result."\n";

			$result=GETPOST("param6", 'restricthtml');
			print __METHOD__." result for param6=".$result." - before=".$_POST["param6"]."\n";
			$this->assertEquals('"&gt;', $result);

			$result=GETPOST("param7", 'restricthtml');
			print __METHOD__." result param7 = ".$result."\n";
			$this->assertEquals('"c:\this is a path~1\aaan 110;" abcdef', $result);
		}


		// Test with restricthtml + MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES to test disabling of bad atrributes

		unset($conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML);
		unset($conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY);
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 1;

		$result=GETPOST("param15", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<img src="">0xbeefed', $result, 'Test 15c');

		$result=GETPOST('param16', 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<a style=" 1000">abc</a>', $result, 'Test tag a with forbidden attribute z-index');

		$result=GETPOST('param17', 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<span style="background-image: url()">abc</span>', $result, 'Test anytag with a forbidden value for attribute');

		$result=GETPOST('param18', 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('<span style="background-image: url(...?...aaa)">abc</span>', $result, 'Test anytag with a forbidden value for attribute');

		unset($conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES);


		// Special test for GETPOST of backtopage, backtolist or backtourl parameter

		$_POST["backtopage"]='//www.google.com';
		$result=GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('www.google.com', $result, 'Test for backtopage param');

		$_POST["backtopage"]='https:https://www.google.com';
		$result=GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('www.google.com', $result, 'Test for backtopage param');

		$_POST["backtolist"]='::HTTPS://www.google.com';
		$result=GETPOST("backtolist");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('www.google.com', $result, 'Test for backtopage param');

		$_POST["backtopage"]='http:www.google.com';
		$result=GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('httpwww.google.com', $result, 'Test for backtopage param');

		$_POST["backtopage"]='/mydir/mypage.php?aa=a%10a';
		$result=GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('/mydir/mypage.php?aa=a%10a', $result, 'Test for backtopage param');

		$_POST["backtopage"]='javascripT&javascript#javascriptxjavascript3a alert(1)';
		$result=GETPOST("backtopage");
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('x3aalert(1)', $result, 'Test for backtopage param');


		$conf->global->MAIN_SECURITY_MAX_IMG_IN_HTML_CONTENT = 3;
		$_POST["pagecontentwithlinks"]='<img src="aaa"><img src="bbb"><img src="ccc"><span style="background: url(/ddd)"></span>';
		$result=GETPOST("pagecontentwithlinks", 'restricthtml');
		print __METHOD__." result=".$result."\n";
		$this->assertEquals('TooManyLinksIntoHTMLString', $result, 'Test on limit on GETPOST fails');

		return $result;
	}

	/**
	 * testEncodeDecode
	 *
	 * @return int
	 */
	public function testEncodeDecode()
	{
		$stringtotest="This is a string to test encode/decode. This is a string to test encode/decode. This is a string to test encode/decode.";

		$encodedstring=dol_encode($stringtotest);
		$decodedstring=dol_decode($encodedstring);
		print __METHOD__." encodedstring=".$encodedstring." ".base64_encode($stringtotest)."\n";
		$this->assertEquals($stringtotest, $decodedstring, 'Use dol_encode/decode with no parameter');

		$encodedstring=dol_encode($stringtotest, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
		$decodedstring=dol_decode($encodedstring, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
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
		$decodedstring = dol_html_entity_decode($stringtotest, ENT_QUOTES|ENT_HTML5);
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
		$this->assertEquals('<a href="aaa">bbbڴ', $decodedstring, 'Function did not sanitize correclty with test 1');

		$stringtotest = '<a href="java'.chr(0).'script:aaa">bbbڴ';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1);
		$this->assertEquals('<a href="aaa">bbbڴ', $decodedstring, 'Function did not sanitize correclty with test 2');

		$stringtotest = '<a href="javascript&colon;aaa">bbbڴ';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1);
		$this->assertEquals('<a href="aaa">bbbڴ', $decodedstring, 'Function did not sanitize correclty with test 3');

		$stringtotest = 'text <link href="aaa"> text';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1, 0, array(), 0);
		$this->assertEquals('text  text', $decodedstring, 'Function did not sanitize correclty with test 4a');

		$stringtotest = 'text <link href="aaa"> text';
		$decodedstring = dol_string_onlythesehtmltags($stringtotest, 1, 1, 1, 0, array(), 1);
		$this->assertEquals('text <link href="aaa"> text', $decodedstring, 'Function did not sanitize correclty with test 4b');

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
		$this->assertEquals('e&eacute;e', $decodedstring, 'Function did not sanitize correclty with test 1');

		$stringtotest = '<div onload="ee"><a href="123"><span class="abc">abc</span></a></div>';
		$decodedstring = dol_string_onlythesehtmlattributes($stringtotest);
		$decodedstring = preg_replace("/\n$/", "", $decodedstring);
		$this->assertEquals('<div><a href="123"><span class="abc">abc</span></a></div>', $decodedstring, 'Function did not sanitize correclty with test 2');

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

		$genpass1=getRandomPassword(true);				// Should be a string return by dol_hash (if no option set, will be md5)
		print __METHOD__." genpass1=".$genpass1."\n";
		$this->assertEquals(strlen($genpass1), 32);

		$genpass1=getRandomPassword(true, array('I'));	// Should be a string return by dol_hash (if no option set, will be md5)
		print __METHOD__." genpass1=".$genpass1."\n";
		$this->assertEquals(strlen($genpass1), 32);

		$conf->global->USER_PASSWORD_GENERATED='None';
		$genpass2=getRandomPassword(false);				// Should return an empty string
		print __METHOD__." genpass2=".$genpass2."\n";
		$this->assertEquals($genpass2, '');

		$conf->global->USER_PASSWORD_GENERATED='Standard';
		$genpass3=getRandomPassword(false);				// Should return a password of 12 chars
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
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		//$dummyuser=new User($db);
		//$result=restrictedArea($dummyuser,'societe');

		$result=restrictedArea($user, 'societe');
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
		$this->assertGreaterThan(0, strpos($tmp['curl_error_msg'], 'not supported'));	// Test error if return does not contains 'not supported'

		$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
		$tmp = getURLContent($url, 'GET', '', 0);	// We do NOT follow
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(301, $tmp['http_code'], 'Should GET url 301 response and stop here');

		$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
		$tmp = getURLContent($url);		// We DO follow a page with return 300 so result should be 200
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(200, $tmp['http_code'], 'Should GET url 301 with a follow -> 200 but we get '.$tmp['http_code']);

		$url = 'http://localhost';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, $tmp['http_code'], 'Should GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL

		$url = 'http://127.0.0.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, $tmp['http_code'], 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 127.0.0.1 is not an external URL

		$url = 'http://127.0.2.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, $tmp['http_code'], 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 127.0.2.1 is not an external URL

		$url = 'https://169.254.0.1';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, $tmp['http_code'], 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because 169.254.0.1 is not an external URL

		$url = 'http://[::1]';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		print __METHOD__." url=".$url."\n";
		$this->assertEquals(400, $tmp['http_code'], 'Should GET url to '.$url.' that is a local URL');	// Test we receive an error because [::1] is not an external URL

		/*$url = 'localtest.me';
		 $tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
		 print __METHOD__." url=".$url."\n";
		 $this->assertEquals(400, $tmp['http_code'], 'Should GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL
		 */

		$url = 'http://192.0.0.192';
		$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL but on an IP in blacklist
		print __METHOD__." url=".$url." tmp['http_code'] = ".$tmp['http_code']."\n";
		$this->assertEquals(400, $tmp['http_code'], 'Access should be refused and was not');	// Test we receive an error because ip is in blacklist

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
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$test = 'javascripT&javascript#x3a alert(1)';
		$result=dol_sanitizeUrl($test);
		$this->assertEquals('x3a alert(1)', $result, 'Test on dol_sanitizeUrl A');

		$test = 'javajavascriptscript&cjavascriptolon;alert(1)';
		$result=dol_sanitizeUrl($test);
		$this->assertEquals('alert(1)', $result, 'Test on dol_sanitizeUrl B');

		$test = '/javas:cript/google.com';
		$result=dol_sanitizeUrl($test);
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
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$test = 'aaa@mycompany.com <My name>, bbb@mycompany.com <Another name>';
		$result=dol_sanitizeEmail($test);
		$this->assertEquals($test, $result, 'Test on dol_sanitizeEmail A');

		$test = "aaa@mycompany.com <My name>,\nbbb@mycompany.com <Another name>";
		$result=dol_sanitizeEmail($test);
		$this->assertEquals('aaa@mycompany.com <My name>,bbb@mycompany.com <Another name>', $result, 'Test on dol_sanitizeEmail B');

		$test = 'aaa@mycompany.com <My name>,\nbbb@mycompany.com <Another name>';
		$result=dol_sanitizeEmail($test);
		$this->assertEquals('aaa@mycompany.com <My name>,nbbb@mycompany.com <Another name>', $result, 'Test on dol_sanitizeEmail C');

		$test = 'aaa@mycompany.com <My name>, "bcc:bbb"@mycompany.com <Another name>';
		$result=dol_sanitizeEmail($test);
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
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		//$dummyuser=new User($db);
		//$result=restrictedArea($dummyuser,'societe');

		$result=dol_sanitizeFileName('bad file | evilaction');
		$this->assertEquals('bad file _ evilaction', $result);

		$result=dol_sanitizeFileName('bad file -evilparam --evilparam ---evilparam ----evilparam');
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
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$result=dol_eval('1==1', 1, 0);
		print "result = ".$result."\n";
		$this->assertTrue($result);

		$result=dol_eval('1==2', 1, 0);
		print "result = ".$result."\n";
		$this->assertFalse($result);

		include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
		include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

		$s = '(($reloadedobj = new Task($db)) && ($reloadedobj->fetchNoCompute($object->id) > 0) && ($secondloadedobj = new Project($db)) && ($secondloadedobj->fetchNoCompute($reloadedobj->fk_project) > 0)) ? $secondloadedobj->ref : "Parent project not found"';
		$result=dol_eval($s, 1, 1, '2');
		print "result = ".$result."\n";
		$this->assertEquals('Parent project not found', $result);

		$s = '(($reloadedobj = new Task($db)) && ($reloadedobj->fetchNoCompute($object->id) > 0) && ($secondloadedobj = new Project($db)) && ($secondloadedobj->fetchNoCompute($reloadedobj->fk_project) > 0)) ? $secondloadedobj->ref : \'Parent project not found\'';
		$result=dol_eval($s, 1, 1, '2');
		print "result = ".$result."\n";
		$this->assertEquals('Parent project not found', $result);

		$s = 'new abc->invoke(\'whoami\')';
		$result=dol_eval($s, 1, 1, '2');
		print "result = ".$result."\n";
		$this->assertEquals('Bad string syntax to evaluate: new abc__forbiddenstring__(\'whoami\')', $result);

		$s = 'new ReflectionFunction(\'abc\')';
		$result=dol_eval($s, 1, 1, '2');
		print "result = ".$result."\n";
		$this->assertEquals('Bad string syntax to evaluate: new __forbiddenstring__(\'abc\')', $result);

		$result=dol_eval('$a=function() { }; $a;', 1, 1, '');
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval('$a=exec("ls");', 1, 1);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval('$a=exec ("ls")', 1, 1);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval('$a="test"; $$a;', 1, 0);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval('`ls`', 1, 0);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval("('ex'.'ec')('echo abc')", 1, 0);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval("sprintf(\"%s%s\", \"ex\", \"ec\")('echo abc')", 1, 0);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);

		$result=dol_eval("90402.38+267678+0", 1, 1, 1);
		print "result = ".$result."\n";
		$this->assertEquals('358080.38', $result);

		global $leftmenu;	// Used into strings to eval

		$leftmenu = 'AAA';
		$result=dol_eval('$conf->currency && preg_match(\'/^(AAA|BBB)/\',$leftmenu)', 1, 1, '1');
		print "result = ".$result."\n";
		$this->assertTrue($result);

		// Same with syntax error
		$leftmenu = 'XXX';
		$result=dol_eval('$conf->currency && preg_match(\'/^(AAA|BBB)/\',$leftmenu)', 1, 1, '1');
		print "result = ".$result."\n";
		$this->assertFalse($result);


		// Case with param onlysimplestring = 1

		$result=dol_eval('1 && getDolGlobalInt("doesnotexist1") && $conf->global->MAIN_FEATURES_LEVEL', 1, 0);	// Should return false and not a 'Bad string syntax to evaluate ...'
		print "result = ".$result."\n";
		$this->assertFalse($result);

		$result=dol_eval("(\$a.'aa')", 1, 0);
		print "result = ".$result."\n";
		$this->assertContains('Bad string syntax to evaluate', $result);
	}


	/**
	 * testDolPrintHTML.
	 * This method include calls to dol_htmlwithnojs()
	 *
	 * @return int
	 */
	public function testDolPrintHTML()
	{
		global $conf;

		// Set options for cleaning data
		$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML = 1;
		// Enabled option MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY if possible
		if (extension_loaded('tidy') && class_exists("tidy")) {
			$conf->global->MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY = 1;
		}
		$conf->global->MAIN_RESTRICTHTML_REMOVE_ALSO_BAD_ATTRIBUTES = 1;



		// For a string that is already HTML (contains HTML tags) with special tags but badly formated
		$stringtotest = "&quot;&gt;";
		$stringfixed = "&quot;&gt;";
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)), 1, 1, 'common', 0, 1);
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');    // Expected '' because should failed because login 'auto' does not exists


		// For a string that is already HTML (contains HTML tags) with special tags but badly formated
		$stringtotest = "testA\n<h1>hhhh</h1><z>ddd</z><header>aaa</header><footer>bbb</footer>";
		if (getDolGlobalString("MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY")) {
			$stringfixed = "testA\n<h1>hhhh</h1>\nddd\n<header>aaa</header>\n<footer>bbb</footer>";
		} else {
			$stringfixed = "testA\n<h1>hhhh</h1>ddd<header>aaa</header><footer>bbb</footer>";
		}
		//$result = dol_htmlentitiesbr($stringtotest);
		//$result = dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0);
		//$result = dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0));
		//$result = dol_escape_htmltag(dol_htmlwithnojs(dol_string_onlythesehtmltags(dol_htmlentitiesbr($stringtotest), 1, 1, 1, 0)), 1, 1, 'common', 0, 1);
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');    // Expected '' because should failed because login 'auto' does not exists


		// For a string that is already HTML (contains HTML tags) but badly formated
		$stringtotest = "testB\n<h1>hhh</h1>\n<td>td alone</td><h1>iii</h1>";
		if (getDolGlobalString("MAIN_RESTRICTHTML_ONLY_VALID_HTML_TIDY")) {
			$stringfixed = "testB\n<h1>hhh</h1>\n<h1>iii</h1>\n<table>\n<tr>\n<td>td alone</td>\n</tr>\n</table>";
		} else {
			$stringfixed = "testB\n<h1>hhh</h1>\n<td>td alone</td><h1>iii</h1>";
		}
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');    // Expected '' because should failed because login 'auto' does not exists


		// For a string with no HTML tags
		$stringtotest = "testC\ntest";
		$stringfixed = "testC<br>\ntest";
		$result = dolPrintHTML($stringtotest);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($stringfixed, $result, 'Error');    // Expected '' because should failed because login 'auto' does not exists

		return 0;
	}


	/**
	 * testCheckLoginPassEntity
	 *
	 * @return	void
	 */
	public function testCheckLoginPassEntity()
	{
		$login=checkLoginPassEntity('loginbidon', 'passwordbidon', 1, array('dolibarr'));
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, '');

		$login=checkLoginPassEntity('admin', 'passwordbidon', 1, array('dolibarr'));
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, '');

		$login=checkLoginPassEntity('admin', 'admin', 1, array('dolibarr'));            // Should works because admin/admin exists
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, 'admin', 'The test to check if pass of user "admin" is "admin" has failed');

		$login=checkLoginPassEntity('admin', 'admin', 1, array('http','dolibarr'));    // Should work because of second authentication method
		print __METHOD__." login=".$login."\n";
		$this->assertEquals($login, 'admin');

		$login=checkLoginPassEntity('admin', 'admin', 1, array('forceuser'));
		print __METHOD__." login=".$login."\n";
		$this->assertEquals('', $login, 'Error');    // Expected '' because should failed because login 'auto' does not exists
	}
}
