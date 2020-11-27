<?php
/* Copyright (C) 2010-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/CoreTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
//require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

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


/**
 * Class to test core functions
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CoreTest extends PHPUnit\Framework\TestCase
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
    public function __construct()
    {
    	parent::__construct();

    	//$this->sharedFixture
        global $conf,$user,$langs,$db;
        $this->savconf=$conf;
        $this->savuser=$user;
        $this->savlangs=$langs;
        $this->savdb=$db;

        //print __METHOD__." db->type=".$db->type." user->id=".$user->id;
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
        //$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

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
        //$db->rollback();

        print __METHOD__."\n";
    }

    /**
     * Init phpunit tests
     *
     * @return  void
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
     * @return  void
     */
    protected function tearDown()
    {
        print __METHOD__."\n";
    }


    /**
     * testDetectURLROOT
     *
     * @return	void
     */
    /*
    public function testDetectURLROOT()
    {
        global $dolibarr_main_prod;

        global $dolibarr_main_url_root;
        global $dolibarr_main_data_root;
        global $dolibarr_main_document_root;
        global $dolibarr_main_data_root_alt;
        global $dolibarr_main_document_root_alt;
        global $dolibarr_main_db_host;
        global $dolibarr_main_db_port;
        global $dolibarr_main_db_type;
        global $dolibarr_main_db_prefix;

        $testtodo=0;

        // Case 1:
        // Test for subdir dolibarrnew (that point to htdocs) in root directory /var/www
        // URL: http://localhost/dolibarrnew/admin/system/phpinfo.php
        // To prepare this test:
        // - Create link from htdocs to /var/www/dolibarrnew
        // - Put into conf.php $dolibarr_main_document_root='/var/www/dolibarrnew';
        if ($testtodo == 1) {
            $_SERVER["HTTPS"]='';
            $_SERVER["SERVER_NAME"]='localhost';
            $_SERVER["SERVER_PORT"]='80';
            $_SERVER["DOCUMENT_ROOT"]='/var/www';
            $_SERVER["SCRIPT_NAME"]='/dolibarrnew/admin/system/phpinfo.php';
            $expectedresult='/dolibarrnew';
        }

        // Case 2:
        // Test for subdir aaa (that point to dolibarr) in root directory /var/www
        // URL: http://localhost/aaa/htdocs/admin/system/phpinfo.php
        // To prepare this test:
        // - Create link from dolibarr to /var/www/aaa
        // - Put into conf.php $dolibarr_main_document_root='/var/www/aaa/htdocs';
        if ($testtodo == 2) {
            $_SERVER["HTTPS"]='';
            $_SERVER["SERVER_NAME"]='localhost';
            $_SERVER["SERVER_PORT"]='80';
            $_SERVER["DOCUMENT_ROOT"]='/var/www';
            $_SERVER["SCRIPT_NAME"]='/aaa/htdocs/admin/system/phpinfo.php';
            $expectedresult='/aaa/htdocs';
        }

        // Case 3:
        // Test for virtual host localhostdolibarrnew that point to htdocs directory with
        // a direct document root
        // URL: http://localhostdolibarrnew/admin/system/phpinfo.php
        // To prepare this test:
        // - Create virtual host localhostdolibarrnew that point to /home/ldestailleur/git/dolibarr/htdocs
        // - Put into conf.php $dolibarr_main_document_root='/home/ldestailleur/git/dolibarr/htdocs';
        if ($testtodo == 3) {
            $_SERVER["HTTPS"]='';
            $_SERVER["SERVER_NAME"]='localhostdolibarrnew';
            $_SERVER["SERVER_PORT"]='80';
            $_SERVER["DOCUMENT_ROOT"]='/home/ldestailleur/git/dolibarr/htdocs';
            $_SERVER["SCRIPT_NAME"]='/admin/system/phpinfo.php';
            $expectedresult='';
        }

        // Case 4:
        // Test for virtual host localhostdolibarrnew that point to htdocs directory with
        // a symbolic link
        // URL: http://localhostdolibarrnew/admin/system/phpinfo.php
        if ($testtodo == 4) {
            $_SERVER["HTTPS"]='';
            $_SERVER["SERVER_NAME"]='localhostdolibarrnew';
            $_SERVER["SERVER_PORT"]='80';
            $_SERVER["DOCUMENT_ROOT"]='/var/www/dolibarr';	// This is a link that point to /home/ldestail/workspace/dolibarr/htdocs
            $_SERVER["SCRIPT_NAME"]='/admin/system/phpinfo.php';
            $expectedresult='';
        }

        // Case 5:
        // Test for alias /dolibarralias, Test when using nginx, Test when using lighttpd
        // URL: http://localhost/dolibarralias/admin/system/phpinfo.php
        // To prepare this test:
        // - Copy content of dolibarr project into /var/www/dolibarr
        // - Put into conf.php $dolibarr_main_document_root='/var/www/dolibarr/htdocs';
        // - Put into conf.php $dolibarr_main_url_root='http://localhost/dolibarralias';  (because autodetect will fails in this case)
        if ($testtodo == 5) {
            $_SERVER["HTTPS"]='';
            $_SERVER["SERVER_NAME"]='localhost';
            $_SERVER["SERVER_PORT"]='80';
            $_SERVER["DOCUMENT_ROOT"]='/var/www';
            $_SERVER["SCRIPT_NAME"]='/dolibarralias/admin/system/phpinfo.php';
            $expectedresult='/dolibarralias';
        }

        // Force to rerun filefunc.inc.php
        include dirname(__FILE__).'/../../htdocs/filefunc.inc.php';

        if ($testtodo != 0)
        {
        	print __METHOD__." DOL_MAIN_URL_ROOT=".DOL_MAIN_URL_ROOT."\n";
        	print __METHOD__." DOL_URL_ROOT=".DOL_URL_ROOT."\n";
        	$this->assertEquals($expectedresult, DOL_URL_ROOT);
        }

        return true;
    }
	*/

    /**
     * testSqlAndScriptInjectWithPHPUnit
     *
     * @return  void
     */
    public function testSqlAndScriptInjectWithPHPUnit()
    {
        // This is code copied from main.inc.php !!!!!!!!!!!!!!!

        /**
         * Security: WAF layer for SQL Injection and XSS Injection (scripts) protection (Filters on GET, POST, PHP_SELF).
         *
         * @param		string		$val		Value brut found int $_GET, $_POST or PHP_SELF
         * @param		string		$type		1=GET, 0=POST, 2=PHP_SELF, 3=GET without sql reserved keywords (the less tolerant test)
         * @return		int						>0 if there is an injection, 0 if none
         */
        function testSqlAndScriptInject($val, $type)
        {
        	// Decode string first
        	// So <svg o&#110;load='console.log(&quot;123&quot;)' become <svg onload='console.log(&quot;123&quot;)'
        	// So "&colon;&apos;" become ":'" (due to ENT_HTML5)
        	$val = html_entity_decode($val, ENT_QUOTES | ENT_HTML5);

        	// TODO loop to decode until no more thing to decode ?

        	// We clean string because some hacks try to obfuscate evil strings by inserting non printable chars. Example: 'java(ascci09)scr(ascii00)ipt' is processed like 'javascript' (whatever is place of evil ascii char)
        	// We should use dol_string_nounprintableascii but function is not yet loaded/available
        	$val = preg_replace('/[\x00-\x1F\x7F]/u', '', $val); // /u operator makes UTF8 valid characters being ignored so are not included into the replace
        	// We clean html comments because some hacks try to obfuscate evil strings by inserting HTML comments. Example: on<!-- -->error=alert(1)
        	$val = preg_replace('/<!--[^>]*-->/', '', $val);

        	$inj = 0;
        	// For SQL Injection (only GET are used to be included into bad escaped SQL requests)
        	if ($type == 1 || $type == 3)
        	{
        		$inj += preg_match('/delete\s+from/i', $val);
        		$inj += preg_match('/create\s+table/i', $val);
        		$inj += preg_match('/insert\s+into/i', $val);
        		$inj += preg_match('/select\s+from/i', $val);
        		$inj += preg_match('/into\s+(outfile|dumpfile)/i', $val);
        		$inj += preg_match('/user\s*\(/i', $val); // avoid to use function user() that return current database login
        		$inj += preg_match('/information_schema/i', $val); // avoid to use request that read information_schema database
        		$inj += preg_match('/<svg/i', $val); // <svg can be allowed in POST
        	}
        	if ($type == 3)
        	{
        		$inj += preg_match('/select|update|delete|truncate|replace|group\s+by|concat|count|from|union/i', $val);
        	}
        	if ($type != 2)	// Not common key strings, so we can check them both on GET and POST
        	{
        		$inj += preg_match('/updatexml\(/i', $val);
        		$inj += preg_match('/update.+set.+=/i', $val);
        		$inj += preg_match('/union.+select/i', $val);
        		$inj += preg_match('/(\.\.%2f)+/i', $val);
        	}
        	// For XSS Injection done by closing textarea to execute content into a textarea field
        	$inj += preg_match('/<\/textarea/i', $val);
        	// For XSS Injection done by adding javascript with script
        	// This is all cases a browser consider text is javascript:
        	// When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
        	// All examples on page: http://ha.ckers.org/xss.html#XSScalc
        	// More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
        	$inj += preg_match('/<audio/i', $val);
        	$inj += preg_match('/<embed/i', $val);
        	$inj += preg_match('/<iframe/i', $val);
        	$inj += preg_match('/<object/i', $val);
        	$inj += preg_match('/<script/i', $val);
        	$inj += preg_match('/Set\.constructor/i', $val); // ECMA script 6
        	if (!defined('NOSTYLECHECK')) $inj += preg_match('/<style/i', $val);
        	$inj += preg_match('/base\s+href/si', $val);
        	$inj += preg_match('/=data:/si', $val);
        	// List of dom events is on https://www.w3schools.com/jsref/dom_obj_event.asp
        	$inj += preg_match('/onmouse([a-z]*)\s*=/i', $val); // onmousexxx can be set on img or any html tag like <img title='...' onmouseover=alert(1)>
        	$inj += preg_match('/ondrag([a-z]*)\s*=/i', $val); //
        	$inj += preg_match('/ontouch([a-z]*)\s*=/i', $val); //
        	$inj += preg_match('/on(abort|afterprint|beforeprint|beforeunload|blur|canplay|canplaythrough|change|click|contextmenu|copy|cut)\s*=/i', $val);
        	$inj += preg_match('/on(dblclick|drop|durationchange|ended|error|focus|focusin|focusout|hashchange|input|invalid)\s*=/i', $val);
        	$inj += preg_match('/on(keydown|keypress|keyup|load|loadeddata|loadedmetadata|loadstart|offline|online|pagehide|pageshow)\s*=/i', $val);
        	$inj += preg_match('/on(paste|pause|play|playing|progress|ratechange|resize|reset|scroll|search|seeking|select|show|stalled|start|submit|suspend)\s*=/i', $val);
        	$inj += preg_match('/on(timeupdate|toggle|unload|volumechange|waiting)\s*=/i', $val);
        	//$inj += preg_match('/on[A-Z][a-z]+\*=/', $val);   // To lock event handlers onAbort(), ...
        	$inj += preg_match('/&#58;|&#0000058|&#x3A/i', $val); // refused string ':' encoded (no reason to have it encoded) to lock 'javascript:...'
        	$inj += preg_match('/javascript\s*:/i', $val);
        	$inj += preg_match('/vbscript\s*:/i', $val);
        	// For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
        	if ($type == 1) {
        		$val = str_replace('enclosure="', 'enclosure=X', $val); // We accept enclosure="
        		$inj += preg_match('/"/i', $val); // We refused " in GET parameters value.
        	}
        	if ($type == 2) $inj += preg_match('/[;"]/', $val); // PHP_SELF is a file system path. It can contains spaces.
        	return $inj;
        }


        // Run tests
        // More on https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet

        // Should be OK
        $expectedresult=0;

        $_SERVER["PHP_SELF"]='/DIR WITH SPACE/htdocs/admin/index.php?mainmenu=home&leftmenu=setup&username=weservices';
        $result=testSqlAndScriptInject($_SERVER["PHP_SELF"], 2);
        $this->assertEquals($expectedresult, $result, 'Error on testSqlAndScriptInject 1a');

        // Should detect XSS
        $expectedresult=1;

        $_SERVER["PHP_SELF"]='/DIR WITH SPACE/htdocs/admin/index.php?mainmenu=home&leftmenu=setup&username=weservices;badaction';
        $result=testSqlAndScriptInject($_SERVER["PHP_SELF"], 2);
        $this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject 1b');

        $test="<img src='1.jpg' onerror =javascript:alert('XSS')>";
        $result=testSqlAndScriptInject($test, 0);
        $this->assertGreaterThanOrEqual($expectedresult, $result, 'Error on testSqlAndScriptInject aaa');

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
    }
}
