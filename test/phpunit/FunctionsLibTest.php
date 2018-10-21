<?php
/* Copyright (C) 2010-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015	   Juanjo Menent		<jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/FunctionsLibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/date.lib.php';

if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined("NOLOGIN"))        define("NOLOGIN",'1');       // If this page is public (can be called outside logged session)

/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class FunctionsLibTest extends PHPUnit_Framework_TestCase
{
    protected $savconf;
    protected $savuser;
    protected $savlangs;
    protected $savdb;

    /**
     * Constructor
     * We save global variables into local variables
     *
     * @return CoreTest
     */
    function __construct()
    {
    	parent::__construct();

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

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        //$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

        if (! function_exists('mb_substr')) { print "\n".__METHOD__." function mb_substr must be enabled.\n"; die(); }

        print __METHOD__."\n";
    }

    // tear down after class
    public static function tearDownAfterClass()
    {
        global $conf,$user,$langs,$db;
        //$db->rollback();

        print __METHOD__."\n";
    }

	/**
	 * Init phpunit tests
	 *
	 * @return	void
	 */
    protected function setUp()
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
    protected function tearDown()
    {
        print __METHOD__."\n";
    }



    /**
     * testDolGetFirstLineOfText
     *
     * @return void
     */
    public function testDolGetFirstLineOfText()
    {
    	// Nb of line is same than entry text

    	$input="aaaa";
    	$result=dolGetFirstLineOfText($input);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa", $result);

    	$input="aaaa\nbbbbbbbbbbbb\n";
    	$result=dolGetFirstLineOfText($input, 2);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa\nbbbbbbbbbbbb", $result);

    	$input="aaaa<br>bbbbbbbbbbbb<br>";
    	$result=dolGetFirstLineOfText($input, 2);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb", $result);

    	// Nb of line is lower

    	$input="aaaa\nbbbbbbbbbbbb\ncccccc\n";
    	$result=dolGetFirstLineOfText($input);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa...", $result);

    	$input="aaaa<br>bbbbbbbbbbbb<br>cccccc<br>";
    	$result=dolGetFirstLineOfText($input);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa...", $result);

    	$input="aaaa\nbbbbbbbbbbbb\ncccccc\n";
    	$result=dolGetFirstLineOfText($input, 2);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa\nbbbbbbbbbbbb...", $result);

    	$input="aaaa<br>bbbbbbbbbbbb<br>cccccc<br>";
    	$result=dolGetFirstLineOfText($input, 2);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb...", $result);

    	// Nb of line is higher

    	$input="aaaa<br>bbbbbbbbbbbb<br>cccccc";
    	$result=dolGetFirstLineOfText($input, 100);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb<br>\ncccccc", $result, 'dolGetFirstLineOfText with nb 100 a');

    	$input="aaaa<br>bbbbbbbbbbbb<br>cccccc<br>";
    	$result=dolGetFirstLineOfText($input, 100);
    	print __METHOD__." result=".$result."\n";
    	$this->assertEquals("aaaa<br>\nbbbbbbbbbbbb<br>\ncccccc", $result, 'dolGetFirstLineOfText with nb 100 b');

    	$input="aaaa<br>bbbbbbbbbbbb<br>cccccc<br>\n";
    	$result=dolGetFirstLineOfText($input, 100);
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

	    $result=dol_buildpath('/google/oauth2callback.php', 2);
	    print __METHOD__." result=".$result."\n";
	    $this->assertStringStartsWith('http', $result);

	    $result=dol_buildpath('/google/oauth2callback.php', 3);
        print __METHOD__." result=".$result."\n";
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
        $user_agent ='Mozilla/4.0 (compatible; MSIE 5.0; Windows 98; DigExt; KITV4 Wanadoo; KITV5 Wanadoo)';
        $tmp=getBrowserInfo($user_agent);
        $this->assertEquals('ie',$tmp['browsername']);
        $this->assertEquals('5.0',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

		// Firefox 0.9.1
        $user_agent ='Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.5a) Gecko/20030728 Mozilla Firefox/0.9.1';
        $tmp=getBrowserInfo($user_agent);
        $this->assertEquals('firefox',$tmp['browsername']);
        $this->assertEquals('0.9.1',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

        $user_agent ='Mozilla/3.0 (Windows 98; U) Opera 6.03  [en]';
        $tmp=getBrowserInfo($user_agent);
        $this->assertEquals('opera',$tmp['browsername']);
        $this->assertEquals('6.03',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

        $user_agent ='Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/535.21 (KHTML, like Gecko) Chrome/19.0.1042.0 Safari/535.21';
        $tmp=getBrowserInfo($user_agent);
        $this->assertEquals('chrome',$tmp['browsername']);
        $this->assertEquals('19.0.1042.0',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

        $user_agent ='chrome (Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.11 (KHTML, like Gecko) Chrome/17.0.963.56 Safari/535.11)';
        $tmp=getBrowserInfo($user_agent);
        $this->assertEquals('chrome',$tmp['browsername']);
        $this->assertEquals('17.0.963.56',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

        $user_agent ='Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_8; de-at) AppleWebKit/533.21.1 (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1';
        $tmp=getBrowserInfo($user_agent);
        $this->assertEquals('safari',$tmp['browsername']);
        $this->assertEquals('533.21.1',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

	    //Internet Explorer 11
	    $user_agent = 'Mozilla/5.0 (Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko';
	    $tmp=getBrowserInfo($user_agent);
	    $this->assertEquals('ie',$tmp['browsername']);
	    $this->assertEquals('11.0',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

	    //Internet Explorer 11 bis
	    $user_agent = 'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; NP06; rv:11.0) like Gecko';
	    $tmp=getBrowserInfo($user_agent);
	    $this->assertEquals('ie',$tmp['browsername']);
	    $this->assertEquals('11.0',$tmp['browserversion']);
	    $this->assertEmpty($tmp['phone']);
	    $this->assertFalse($tmp['tablet']);
	    $this->assertEquals('classic', $tmp['layout']);

	    //iPad
	    $user_agent = 'Mozilla/5.0 (iPad; CPU OS 6_0 like Mac OS X) AppleWebKit/536.26 (KHTML, like Gecko) Version/6.0 Mobile/10A5355d Safari/8536.25';
	    $tmp=getBrowserInfo($user_agent);
	    $this->assertEquals('safari',$tmp['browsername']);
	    $this->assertEquals('8536.25',$tmp['browserversion']);
	    $this->assertEquals('ios',$tmp['browseros']);
	    $this->assertEquals('tablet',$tmp['layout']);
	    $this->assertEquals('iphone',$tmp['phone']);
    }


    /**
     * testDolTextIsHtml
     *
     * @return void
     */
    public function testDolTextIsHtml()
    {
        // True
        $input='<html>xxx</html>';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with html tag');
        $input='<body>xxx</body>';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with body tag');
        $input='xxx <b>yyy</b> zzz';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with b tag');
        $input='xxx <u>yyy</u> zzz';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with u tag');
        $input='text with <div>some div</div>';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with div tag');
        $input='text with HTML &nbsp; entities';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with entities tag');
        $input='xxx<br>';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with entities br');
        $input='xxx<br >';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with entities br');
        $input='xxx<br style="eee">';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with entities br and attributes');
        $input='xxx<br style="eee" >';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with entities br and attributes bis');
        $input='<h2>abc</h2>';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with entities h2');
        $input='<img id="abc" src="https://xxx.com/aaa/image.png" />';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with img tag');
        $input='<a class="azerty" href="https://xxx.com/aaa/image.png" />';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with a tag');
        $input='This is a text with&nbsp;html spaces';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with a &nbsp;');
        $input='This is a text with accent &eacute;';
        $after=dol_textishtml($input);
        $this->assertTrue($after, 'Test with a &eacute;');

        // False
        $input='xxx < br>';
        $after=dol_textishtml($input);
        $this->assertFalse($after);
        $input='xxx <email@email.com>';	// <em> is html, <em... is not
        $after=dol_textishtml($input);
        $this->assertFalse($after);
        $input='xxx <brstyle="ee">';
        $after=dol_textishtml($input);
        $this->assertFalse($after);
        $input='This is a text with html comments <!-- comment -->';	// we suppose this is not enough to be html content
        $after=dol_textishtml($input);
        $this->assertFalse($after);

    }


    /**
     * testDolHtmlCleanLastBr
     *
     * @return boolean
     */
    public function testDolHtmlCleanLastBr()
    {
        $input="A string\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);

        $input="A string first\nA string second\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string first\nA string second",$after);

        $input="A string\n\n\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);

        $input="A string<br>";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);

        $input="A string first<br>\nA string second<br>";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string first<br>\nA string second",$after);

        $input="A string\n<br type=\"_moz\" />\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);

        $input="A string\n<br><br />\n\n";
        $after=dol_htmlcleanlastbr($input);
        $this->assertEquals("A string",$after);

        return true;
    }

    /**
     * testDolConcat
     *
     * @return boolean
     */
    public function testDolConcat()
    {
        $text1="A string 1"; $text2="A string 2";	// text 1 and 2 are text, concat need only \n
        $after=dol_concatdesc($text1, $text2);
        $this->assertEquals("A string 1\nA string 2",$after);

        $text1="A<br>string 1"; $text2="A string 2";	// text 1 is html, concat need <br>\n
        $after=dol_concatdesc($text1, $text2);
        $this->assertEquals("A<br>string 1<br>\nA string 2",$after);

        $text1="A string 1"; $text2="A <b>string</b> 2";	// text 2 is html, concat need <br>\n
        $after=dol_concatdesc($text1, $text2);
        $this->assertEquals("A string 1<br>\nA <b>string</b> 2",$after);

        return true;
    }


    /**
     * testDolStringNohtmltag
     *
     * @return boolean
     */
    public function testDolStringNohtmltag()
    {
        $text="A\nstring\n\nand more\n";
        $after=dol_string_nohtmltag($text,0);
        $this->assertEquals("A\nstring\n\nand more",$after,"test1a");

        $text="A <b>string<b><br>\n<br>\n\nwith html tag<br>\n";
        $after=dol_string_nohtmltag($text, 0);
        $this->assertEquals("A string\n\n\n\n\nwith html tag",$after,"test2a 2 br and 3 \n give 5 \n");

        $text="A <b>string<b><br>\n<br>\n\nwith html tag<br>\n";
        $after=dol_string_nohtmltag($text, 1);
        $this->assertEquals("A string with html tag",$after,"test2b 2 br and 3 \n give 1 space");

        $text="A <b>string<b><br>\n<br>\n\nwith html tag<br>\n";
        $after=dol_string_nohtmltag($text, 2);
        $this->assertEquals("A string\n\nwith html tag",$after,"test2c 2 br and 3 \n give 2 \n");

        $text="A string<br>Another string";
        $after=dol_string_nohtmltag($text,0);
        $this->assertEquals("A string\nAnother string",$after,"test4");

        $text="A string<br>Another string";
        $after=dol_string_nohtmltag($text,1);
        $this->assertEquals("A string Another string",$after,"test5");

        $text='<a href="/myurl" title="<u>Afficher projet</u>">ABC</a>';
        $after=dol_string_nohtmltag($text,1);
        $this->assertEquals("ABC",$after,"test6");

        $text='<a href="/myurl" title="&lt;u&gt;Afficher projet&lt;/u&gt;">DEF</a>';
        $after=dol_string_nohtmltag($text,1);
        $this->assertEquals("DEF",$after,"test7");

        $text='<a href="/myurl" title="<u>A title</u>">HIJ</a>';
        $after=dol_string_nohtmltag($text,0);
        $this->assertEquals("HIJ",$after,"test8");

        $text="A <b>string<b>\n\nwith html tag and '<' chars<br>\n";
        $after=dol_string_nohtmltag($text, 0);
        $this->assertEquals("A string\n\nwith html tag and '<' chars",$after,"test9");

        $text="A <b>string<b>\n\nwith tag with < chars<br>\n";
        $after=dol_string_nohtmltag($text, 1);
        $this->assertEquals("A string with tag with < chars",$after,"test10");

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

    	$input="A string\nwith a é, &, < and >.";
        $after=dol_htmlentitiesbr($input,0);    // Add <br> before \n
        $this->assertEquals("A string<br>\nwith a &eacute;, &amp;, &lt; and &gt;.",$after);

        $input="A string\nwith a é, &, < and >.";
        $after=dol_htmlentitiesbr($input,1);    // Replace \n with <br>
        $this->assertEquals("A string<br>with a &eacute;, &amp;, &lt; and &gt;.",$after);

        $input="A string\nwith a é, &, < and >.\n\n";	// With some \n at end that should be cleaned
        $after=dol_htmlentitiesbr($input,0);    // Add <br> before \n
        $this->assertEquals("A string<br>\nwith a &eacute;, &amp;, &lt; and &gt;.",$after);

        $input="A string\nwith a é, &, < and >.\n\n";	// With some \n at end that should be cleaned
        $after=dol_htmlentitiesbr($input,1);    // Replace \n with <br>
        $this->assertEquals("A string<br>with a &eacute;, &amp;, &lt; and &gt;.",$after);

        // Text already HTML, so &,<,> should not be converted

        $input="A string<br>\nwith a é, &, < and >.";
        $after=dol_htmlentitiesbr($input);
        $this->assertEquals("A string<br>\nwith a &eacute;, &, < and >.",$after);

        $input="<li>\nA string with a é, &, < and >.</li>\nAnother string";
        $after=dol_htmlentitiesbr($input);
        $this->assertEquals("<li>\nA string with a &eacute;, &, < and >.</li>\nAnother string",$after);

        $input="A string<br>\nwith a é, &, < and >.<br>";	// With some <br> at end that should be cleaned
        $after=dol_htmlentitiesbr($input);
        $this->assertEquals("A string<br>\nwith a &eacute;, &, < and >.",$after);

        $input="<li>\nA string with a é, &, < and >.</li>\nAnother string<br>";	// With some <br> at end that should be cleaned
        $after=dol_htmlentitiesbr($input);
        $this->assertEquals("<li>\nA string with a &eacute;, &, < and >.</li>\nAnother string",$after);

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
        $input="A string\nwith a é, &, < and > and bold tag.\nThird line";
        $after=dol_nboflines_bis($input,0);
        $this->assertEquals($after,3);

        // This is a html string so nb of lines depends on <br>
        $input="A string\nwith a é, &, < and > and <b>bold</b> tag.\nThird line";
        $after=dol_nboflines_bis($input,0);
        $this->assertEquals($after,1);

        // This is a html string so nb of lines depends on <br>
        $input="A string<br>with a é, &, < and > and <b>bold</b> tag.<br>Third line";
        $after=dol_nboflines_bis($input,0);
        $this->assertEquals($after,3);

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

    	$input="A string\nwith a à ä é è ë ï ü ö ÿ, &, < and >.";
        $after=dol_string_unaccent($input);
        $this->assertEquals("A string\nwith a a a e e e i u o y, &, < and >.",$after);
    }


    /**
     * testDolUtf8Check
     *
     * @return void
     */
    public function testDolUtf8Check()
    {
        // True
        $result=utf8_check('azerty');
        $this->assertTrue($result);

        $file=dirname(__FILE__).'/textutf8.txt';
        $filecontent=file_get_contents($file);
        $result=utf8_check($filecontent);
        $this->assertTrue($result);

        $file=dirname(__FILE__).'/textiso.txt';
        $filecontent=file_get_contents($file);
        $result=utf8_check($filecontent);
        $this->assertFalse($result);
    }

    /**
     * testDolTrunc
     *
     * @return boolean
     */
    public function testDolTrunc()
    {
        // Default trunc (will add ... if truncation truncation or keep last char if only one char)
        $input="éeéeéeàa";
        $after=dol_trunc($input,3);
        $this->assertEquals("éeé...",$after,'Test A1');
        $after=dol_trunc($input,2);
        $this->assertEquals("ée...",$after,'Test A2');
        $after=dol_trunc($input,1);
        $this->assertEquals("é...",$after,'Test A3');
        $input="éeéeé";
        $after=dol_trunc($input,3);
        $this->assertEquals("éeéeé",$after,'Test B1');
        $after=dol_trunc($input,2);
        $this->assertEquals("éeéeé",$after,'Test B2');
        $after=dol_trunc($input,1);
        $this->assertEquals("é...",$after,'Test B3');
        $input="éeée";
        $after=dol_trunc($input,3);
        $this->assertEquals("éeée",$after,'Test C1');
        $after=dol_trunc($input,2);
        $this->assertEquals("éeée",$after,'Test C2');
        $after=dol_trunc($input,1);
        $this->assertEquals("éeée",$after,'Test C3');
        $input="éeé";
        $after=dol_trunc($input,3);
        $this->assertEquals("éeé",$after,'Test C');
        $after=dol_trunc($input,2);
        $this->assertEquals("éeé",$after,'Test D');
        $after=dol_trunc($input,1);
        $this->assertEquals("éeé",$after,'Test E');
        // Trunc with no ...
        $input="éeéeéeàa";
        $after=dol_trunc($input,3,'right','UTF-8',1);
        $this->assertEquals("éeé",$after,'Test F');
        $after=dol_trunc($input,2,'right','UTF-8',1);
        $this->assertEquals("ée",$after,'Test G');
        $input="éeé";
        $after=dol_trunc($input,3,'right','UTF-8',1);
        $this->assertEquals("éeé",$after,'Test H');
        $after=dol_trunc($input,2,'right','UTF-8',1);
        $this->assertEquals("ée",$after,'Test I');
        $after=dol_trunc($input,1,'right','UTF-8',1);
        $this->assertEquals("é",$after,'Test J');
        $input="éeéeéeàa";
        $after=dol_trunc($input,4,'middle');
        $this->assertEquals("ée...àa",$after,'Test K');

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

    	$savtz=date_default_timezone_get();

		// Some test for UTC TZ
    	date_default_timezone_set('UTC');

    	// Check bad hours
        $result=dol_mktime(25,0,0,1,1,1970,1,1);    // Error (25 hours)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,61,0,1,1,1970,1,1);    // Error (61 minutes)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,1,61,1,1,1970,1,1);    // Error (61 seconds)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,1,1,1,32,1970,1,1);    // Error (day 32)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);
        $result=dol_mktime(2,1,1,13,1,1970,1,1);    // Error (month 13)
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('',$result);

        $result=dol_mktime(2,1,1,1,1,1970,1);    // 1970-01-01 02:01:01 in GMT area -> 7261
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(7261,$result);

        $result=dol_mktime(2,0,0,1,1,1970,0);                // 1970-01-01 02:00:00 = 7200 in local area Europe/Paris = 3600 GMT
        print __METHOD__." result=".$result."\n";
        $tz=getServerTimeZoneInt('winter');                  // +1 in Europe/Paris at this time (this time is winter)
        $this->assertEquals(7200-($tz*3600),$result);        // 7200 if we are at greenwich winter, 7200-($tz*3600) at local winter

        // Some test for local TZ Europe/Paris
        date_default_timezone_set('Europe/Paris');

        // Check that tz for paris in winter is used
        $result=dol_mktime(2,0,0,1,1,1970,'server');         // 1970-01-01 02:00:00 = 7200 in local area Europe/Paris = 3600 GMT
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(3600,$result);        			 // 7200 if we are at greenwich winter, 3600 at Europe/Paris

        // Check that daylight saving time is used
        $result=dol_mktime(2,0,0,6,1,2014,0);         		// 2014-06-01 02:00:00 = 1401588000-3600(location)-3600(daylight) in local area Europe/Paris = 1401588000 GMT
        print __METHOD__." result=".$result."\n";
        $this->assertEquals(1401588000-3600-3600,$result);  // 1401588000 are at greenwich summer, 1401588000-3600(location)-3600(daylight) at Europe/Paris summer

        date_default_timezone_set($savtz);
    }


    /**
     * testDolEscapeJs
     *
     * @return	void
     */
    public function testDolEscapeJs()
    {
        $input="x&<b>#</b>,\"'";    // " will be converted into '
        $result=dol_escape_js($input);
        $this->assertEquals("x&<b>#</b>,\'\'",$result,"Test mode=0");

        $result=dol_escape_js($input,1);
        $this->assertEquals("x&<b>#</b>,\"\'",$result,"Test mode=1");

        $result=dol_escape_js($input,2);
        $this->assertEquals("x&<b>#</b>,\\\"'",$result,"Test mode=2");
    }


    /**
    * testDolEscapeHtmlTag
    *
    * @return	void
    */
    public function testDolEscapeHtmlTag()
    {
        $input='x&<b>#</b>,"';    // & and " are converted into html entities, <b> are removed
        $result=dol_escape_htmltag($input);
        $this->assertEquals('x&amp;#,&quot;',$result);

        $input='x&<b>#</b>,"';    // & and " are converted into html entities, <b> are not removed
        $result=dol_escape_htmltag($input,1);
        $this->assertEquals('x&amp;&lt;b&gt;#&lt;/b&gt;,&quot;',$result);
    }


    /**
     * testDolFormatAddress
     *
     * @return	void
     */
    public function testDolFormatAddress()
    {
    	global $conf,$user,$langs,$db;
		$conf=$this->savconf;
		$user=$this->savuser;
		$langs=$this->savlangs;
		$db=$this->savdb;

		$object=new Societe($db);
		$object->initAsSpecimen();

		$object->country_code='FR';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\n99999 MyTown",$address);

		$object->country_code='GB';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\nMyTown, MyState\n99999",$address);

		$object->country_code='US';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\nMyTown, MyState, 99999",$address);

		$object->country_code='AU';
    	$address=dol_format_address($object);
    	$this->assertEquals("21 jump street\nMyTown, MyState, 99999",$address);
    }


    /**
     * testDolFormatAddress
     *
     * @return	void
     */
    public function testDolPrintPhone()
    {
        global $conf,$user,$langs,$db;
        $conf=$this->savconf;
        $user=$this->savuser;
        $langs=$this->savlangs;
        $db=$this->savdb;

        $object=new Societe($db);
        $object->initAsSpecimen();

        $object->country_code='FR';
        $phone=dol_print_phone('1234567890', $object->country_code);
        $this->assertEquals('<span style="margin-right: 10px;">12&nbsp;34&nbsp;56&nbsp;78&nbsp;90</span>', $phone, 'Phone for FR 1');

        $object->country_code='FR';
        $phone=dol_print_phone('1234567890', $object->country_code, 0, 0, 0, '');
        $this->assertEquals('<span style="margin-right: 10px;">1234567890</span>', $phone, 'Phone for FR 2');

        $object->country_code='FR';
        $phone=dol_print_phone('1234567890', $object->country_code, 0, 0, 0, ' ');
        $this->assertEquals('<span style="margin-right: 10px;">12 34 56 78 90</span>', $phone, 'Phone for FR 3');

        $object->country_code='CA';
        $phone=dol_print_phone('1234567890', $object->country_code, 0, 0, 0, ' ');
        $this->assertEquals('<span style="margin-right: 10px;">(123) 456-7890</span>', $phone, 'Phone for CA 1');

    }


    /**
     * testImgPicto
     *
     * @return	void
     */
    public function testImgPicto()
    {
        $s=img_picto('title','user');
        print __METHOD__." s=".$s."\n";
        $this->assertContains('theme',$s,'testImgPicto1');

    	$s=img_picto('title','img.png','style="float: right"',0);
        print __METHOD__." s=".$s."\n";
        $this->assertContains('theme',$s,'testImgPicto2');
        $this->assertContains('style="float: right"',$s,'testImgPicto2');

        $s=img_picto('title', '/fullpath/img.png', '', 1);
        print __METHOD__." s=".$s."\n";
        $this->assertEquals('<img src="/fullpath/img.png" alt="" title="title" class="inline-block">',$s,'testImgPicto3');

        $s=img_picto('title', '/fullpath/img.png', '', true);
        print __METHOD__." s=".$s."\n";
        $this->assertEquals('<img src="/fullpath/img.png" alt="" title="title" class="inline-block">',$s,'testImgPicto4');

        $s=img_picto('title', 'delete', '', 0, 1);
        print __METHOD__." s=".$s."\n";
        $this->assertEquals(DOL_URL_ROOT.'/theme/eldy/img/delete.png',$s,'testImgPicto5');
    }

    /**
     * testDolNow
     *
     * @return	void
     */
    public function testDolNow()
    {
        $now=dol_now('gmt');
        $nowtzserver=dol_now('tzserver');
        print __METHOD__." getServerTimeZoneInt=".(getServerTimeZoneInt('now')*3600)."\n";
        $this->assertEquals(getServerTimeZoneInt('now')*3600,($nowtzserver-$now));
    }

    /**
     * testVerifCond
     *
     * @return	void
     */
    public function testVerifCond()
    {
        $verifcond=verifCond('1==1');
        $this->assertTrue($verifcond,'Test a true comparison');

        $verifcond=verifCond('1==2');
        $this->assertFalse($verifcond,'Test a false comparison');

        $verifcond=verifCond('$conf->facture->enabled');
        $this->assertTrue($verifcond,'Test that conf property of a module report true when enabled');

        $verifcond=verifCond('$conf->moduledummy->enabled');
        $this->assertFalse($verifcond,'Test that conf property of a module report false when disabled');

        $verifcond=verifCond('');
        $this->assertTrue($verifcond);
    }

    /**
     * testGetDefaultTva
     *
     * @return	void
     */
    public function testGetDefaultTva()
    {
        global $conf,$user,$langs,$db;
        $this->savconf=$conf;
        $this->savuser=$user;
        $this->savlangs=$langs;
        $this->savdb=$db;

        // Sellers
        $companyfrnovat=new Societe($db);
        $companyfrnovat->country_code='FR';
        $companyfrnovat->tva_assuj=0;

        $companyfr=new Societe($db);
        $companyfr->country_code='FR';
        $companyfr->tva_assuj=1;
		$companyfr->tva_intra='FR9999';

        // Buyers
        $companymc=new Societe($db);
        $companymc->country_code='MC';
        $companymc->tva_assuj=1;
		$companyfr->tva_intra='MC9999';

        $companyit=new Societe($db);
        $companyit->country_code='IT';
        $companyit->tva_assuj=1;
        $companyit->tva_intra='IT99999';

        $companyde=new Societe($db);
        $companyde->country_code='DE';
        $companyde->tva_assuj=1;
        $companyde->tva_intra='DE99999';

        $notcompanyde=new Societe($db);
        $notcompanyde->country_code='DE';
        $notcompanyde->tva_assuj=0;
        $notcompanyde->tva_intra='';
        $notcompanyde->typent_code='TE_PRIVATE';

        $companyus=new Societe($db);
        $companyus->country_code='US';
        $companyus->tva_assuj=1;
        $companyus->tva_intra='';


        // Test RULE 0 (FR-DE)
        // Not tested

        // Test RULE 1
        $vat=get_default_tva($companyfrnovat,$companymc,0);
        $this->assertEquals(0,$vat,'RULE 1');

        // Test RULE 2 (FR-FR)
        $vat=get_default_tva($companyfr,$companyfr,0);
        $this->assertEquals(20,$vat,'RULE 2');

        // Test RULE 2 (FR-MC)
        $vat=get_default_tva($companyfr,$companymc,0);
        $this->assertEquals(20,$vat,'RULE 2');

        // Test RULE 3 (FR-DE company)
        $vat=get_default_tva($companyfr,$companyit,0);
        $this->assertEquals(0,$vat,'RULE 3');

        // Test RULE 4 (FR-DE not a company)
        $vat=get_default_tva($companyfr,$notcompanyde,0);
        $this->assertEquals(20,$vat,'RULE 4');

        // Test RULE 5 (FR-US)
        $vat=get_default_tva($companyfr,$companyus,0);
        $this->assertEquals(0,$vat,'RULE 5');


        // We do same tests but with option SERVICE_ARE_ECOMMERCE_200238EC on.
        $conf->global->SERVICE_ARE_ECOMMERCE_200238EC = 1;


        // Test RULE 1 (FR-US)
        $vat=get_default_tva($companyfr,$companyus,0);
        $this->assertEquals(0,$vat,'RULE 1 ECOMMERCE_200238EC');

        // Test RULE 2 (FR-FR)
        $vat=get_default_tva($companyfr,$companyfr,0);
        $this->assertEquals(20,$vat,'RULE 2 ECOMMERCE_200238EC');

        // Test RULE 3 (FR-DE company)
        $vat=get_default_tva($companyfr,$companyde,0);
        $this->assertEquals(0,$vat,'RULE 3 ECOMMERCE_200238EC');

        // Test RULE 4 (FR-DE not a company)
        $vat=get_default_tva($companyfr,$notcompanyde,0);
        $this->assertEquals(19,$vat,'RULE 4 ECOMMERCE_200238EC');

        // Test RULE 5 (FR-US)
        $vat=get_default_tva($companyfr,$companyus,0);
        $this->assertEquals(0,$vat,'RULE 5 ECOMMERCE_200238EC');

    }

    /**
     * testGetDefaultTva
     *
     * @return	void
     */
    public function testGetDefaultLocalTax()
    {
    	global $conf,$user,$langs,$db;
    	$this->savconf=$conf;
    	$this->savuser=$user;
    	$this->savlangs=$langs;
    	$this->savdb=$db;

    	$companyfrnovat=new Societe($db);
    	$companyfrnovat->country_code='FR';
    	$companyfrnovat->tva_assuj=0;
    	$companyfrnovat->localtax1_assuj=0;
    	$companyfrnovat->localtax2_assuj=0;

    	$companyes=new Societe($db);
    	$companyes->country_code='ES';
    	$companyes->tva_assuj=1;
    	$companyes->localtax1_assuj=1;
    	$companyes->localtax2_assuj=1;

    	$companymc=new Societe($db);
    	$companymc->country_code='MC';
    	$companymc->tva_assuj=1;
    	$companymc->localtax1_assuj=0;
    	$companymc->localtax2_assuj=0;

    	$companyit=new Societe($db);
    	$companyit->country_code='IT';
    	$companyit->tva_assuj=1;
    	$companyit->tva_intra='IT99999';
    	$companyit->localtax1_assuj=0;
    	$companyit->localtax2_assuj=0;

    	$notcompanyit=new Societe($db);
    	$notcompanyit->country_code='IT';
    	$notcompanyit->tva_assuj=1;
    	$notcompanyit->tva_intra='';
    	$notcompanyit->typent_code='TE_PRIVATE';
    	$notcompanyit->localtax1_assuj=0;
    	$notcompanyit->localtax2_assuj=0;

    	$companyus=new Societe($db);
    	$companyus->country_code='US';
    	$companyus->tva_assuj=1;
    	$companyus->tva_intra='';
    	$companyus->localtax1_assuj=0;
    	$companyus->localtax2_assuj=0;

    	// Test RULE FR-MC
    	$vat1=get_default_localtax($companyfrnovat,$companymc,1,0);
    	$vat2=get_default_localtax($companyfrnovat,$companymc,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);

    	// Test RULE ES-ES
    	$vat1=get_default_localtax($companyes,$companyes,1,0);
    	$vat2=get_default_localtax($companyes,$companyes,2,0);
    	$this->assertEquals($vat1, 5.2);
    	$this->assertStringStartsWith((string) $vat2, '-19:-15:-9');       // Can be -19 (old version) or '-19:-15:-9' (new setup)

    	// Test RULE ES-IT
    	$vat1=get_default_localtax($companyes,$companyit,1,0);
    	$vat2=get_default_localtax($companyes,$companyit,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);

    	// Test RULE ES-IT
    	$vat1=get_default_localtax($companyes,$notcompanyit,1,0);
    	$vat2=get_default_localtax($companyes,$notcompanyit,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);

    	// Test RULE FR-IT
    	// Not tested

    	// Test RULE ES-US
    	$vat1=get_default_localtax($companyes,$companyus,1,0);
    	$vat2=get_default_localtax($companyes,$companyus,2,0);
    	$this->assertEquals(0,$vat1);
    	$this->assertEquals(0,$vat2);
    }


    /**
     * testDolExplodeIntoArray
     *
     * @return	void
     */
    public function testDolExplodeIntoArray()
    {
    	$stringtoexplode='AA=B/B.CC=.EE=FF.HH=GG;.';
    	$tmp=dolExplodeIntoArray($stringtoexplode,'.','=');

        print __METHOD__." tmp=".json_encode($tmp)."\n";
        $this->assertEquals('{"AA":"B\/B","CC":"","EE":"FF","HH":"GG;"}',json_encode($tmp));
    }

	/**
	 * dol_nl2br
	 *
	 * @return void
	 */
	public function testDolNl2Br() {

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
		$this->assertEquals(1000, price2num('1 000.0'));
		$this->assertEquals(1000, price2num('1 000','MT'));
		$this->assertEquals(1000, price2num('1 000','MU'));

		$this->assertEquals(1000.123456, price2num('1 000.123456'));

		// Round down
		$this->assertEquals(1000.12, price2num('1 000.123452','MT'));
		$this->assertEquals(1000.12345, price2num('1 000.123452','MU'),"Test MU");

		// Round up
		$this->assertEquals(1000.13, price2num('1 000.125456','MT'));
		$this->assertEquals(1000.12546, price2num('1 000.125456','MU'),"Test MU");

		// Text can't be converted
		$this->assertEquals('12.4$',price2num('12.4$'));
		$this->assertEquals('12r.4$',price2num('12r.4$'));

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

		$tmp=dol_getdate(1);				// 1/1/1970 and 1 second = thirday
		$this->assertEquals(4, $tmp['wday']);

		$tmp=dol_getdate(24*60*60+1);		// 2/1/1970 and 1 second = friday
		$this->assertEquals(5, $tmp['wday']);

		$conf->global->MAIN_START_WEEK = 1;

		$tmp=dol_getdate(1);				// 1/1/1970 and 1 second = thirday
		$this->assertEquals(4, $tmp['wday']);

		$tmp=dol_getdate(24*60*60+1);		// 2/1/1970 and 1 second = friday
		$this->assertEquals(5, $tmp['wday']);

		return true;
	}


	/**
	 * testDolGetDate
	 *
	 * @return boolean
	 */
	public function testMakeSubstitutions()
	{
		global $conf, $langs;
		$langs->load("main");

		$substit=array("AAA"=>'Not used', "BBB"=>'Not used', "CCC"=>"C replaced");
		$chaine='This is a string with __[MAIN_THEME]__ and __(DIRECTION)__ and __CCC__';
		$newstring = make_substitutions($chaine, $substit);
		$this->assertEquals($newstring, 'This is a string with eldy and ltr and __C replaced__');

		return true;
	}

}
