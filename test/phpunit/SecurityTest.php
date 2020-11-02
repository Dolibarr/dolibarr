<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';

if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER', '1');
if (! defined('NOREQUIREDB'))    define('NOREQUIREDB', '1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN', '1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK', '1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined("NOLOGIN"))        define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)

if (empty($user->id))
{
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
	 * @return SecurityTest
	 */
	public function __construct()
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

    /**
     * setUpBeforeClass
     *
     * @return void
     */
    public static function setUpBeforeClass()
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
    public static function tearDownAfterClass()
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

        $_COOKIE["id"]=111;
		$_GET["param1"]="222";
        $_POST["param1"]="333";
		$_GET["param2"]='a/b#e(pr)qq-rr\cc';
        $_GET["param3"]='"&#110;a/b#e(pr)qq-rr\cc';    // Same than param2 + " and &#110;
        $_GET["param4"]='../dir';
        $_GET["param5"]="a_1-b";
        $_POST["param6"]="&quot;&gt;<svg o&#110;load='console.log(&quot;123&quot;)'&gt;";
        $_GET["param7"]='"c:\this is a path~1\aaa&#110;" abc<bad>def</bad>';
        $_POST["param8"]="Hacker<svg o&#110;load='console.log(&quot;123&quot;)'";	// html tag is not closed so it is not detected as html tag but is still harmfull

        // Test int
        $result=GETPOST('id', 'int');              // Must return nothing
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param1", 'int');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, 222);

        $result=GETPOST("param1", 'int', 2);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, 333);

        // Test alpha
        $result=GETPOST("param2", 'alpha');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, $_GET["param2"]);

        $result=GETPOST("param3", 'alpha');  // Must return string sanitized from char "
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, 'na/b#e(pr)qq-rr\cc');

        $result=GETPOST("param4", 'alpha');  // Must return string sanitized from ../
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, 'dir');

        // Test aZ09
        $result=GETPOST("param1", 'aZ09');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, $_GET["param1"]);

        $result=GETPOST("param2", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param3", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param4", 'aZ09');  // Must return '' as string contains car not in aZ09 definition
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('', $result);

        $result=GETPOST("param5", 'aZ09');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($_GET["param5"], $result);

        $result=GETPOST("param6", 'nohtml');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('">', $result);

        // With restricthtml we must remove html open/close tag and content but not htmlentities like &#110;
        $result=GETPOST("param7", 'restricthtml');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('"c:\this is a path~1\aaa&#110;" abcdef', $result);

        // With alphanohtml, we must convert the html entities like &#110;
        $result=GETPOST("param8", 'alphanohtml');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals("Hacker<svg onload='console.log(123)'", $result);

        return $result;
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
        $this->assertEquals($login, 'admin');

        $login=checkLoginPassEntity('admin', 'admin', 1, array('http','dolibarr'));    // Should work because of second authetntication method
        print __METHOD__." login=".$login."\n";
        $this->assertEquals($login, 'admin');

        $login=checkLoginPassEntity('admin', 'admin', 1, array('forceuser'));
        print __METHOD__." login=".$login."\n";
        $this->assertEquals($login, '');    // Expected '' because should failed because login 'auto' does not exists
    }

    /**
     * testEncodeDecode
     *
     * @return number
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
     * @return number
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
     * @return number
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

        return 0;
    }

    /**
     * testGetRandomPassword
     *
     * @return number
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
        $genpass3=getRandomPassword(false);				// Should return a password of 10 chars
        print __METHOD__." genpass3=".$genpass3."\n";
        $this->assertEquals(strlen($genpass3), 10);

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
     * @return number
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
    	$this->assertEquals(301, $tmp['http_code'], 'GET url 301 without following -> 301');

    	$url = 'https://www.dolibarr.fr';	// This is a redirect 301 page
    	$tmp = getURLContent($url);		// We DO follow
    	print __METHOD__." url=".$url."\n";
    	$this->assertEquals(200, $tmp['http_code'], 'GET url 301 with following -> 200');	// Test error if return does not contains 'not supported'

    	$url = 'http://localhost';
    	$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
    	print __METHOD__." url=".$url."\n";
    	$this->assertEquals(400, $tmp['http_code'], 'GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL

    	$url = 'http://127.0.0.1';
    	$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
    	print __METHOD__." url=".$url."\n";
    	$this->assertEquals(400, $tmp['http_code'], 'GET url to '.$url.' that is a local URL');	// Test we receive an error because localtest.me is not an external URL

    	$url = 'https://169.254.0.1';
    	$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
    	print __METHOD__." url=".$url."\n";
    	$this->assertEquals(400, $tmp['http_code'], 'GET url to '.$url.' that is a local URL');	// Test we receive an error because localtest.me is not an external URL

    	$url = 'http://[::1]';
    	$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
    	print __METHOD__." url=".$url."\n";
    	$this->assertEquals(400, $tmp['http_code'], 'GET url to '.$url.' that is a local URL');	// Test we receive an error because localtest.me is not an external URL

    	/*$url = 'localtest.me';
    	$tmp = getURLContent($url, 'GET', '', 0, array(), array('http', 'https'), 0);		// Only external URL
    	print __METHOD__." url=".$url."\n";
    	$this->assertEquals(400, $tmp['http_code'], 'GET url to '.$url.' that resolves to a local URL');	// Test we receive an error because localtest.me is not an external URL
		*/

    	return 0;
    }
}
