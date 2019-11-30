<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       test/phpunit/Functions2LibTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions2.lib.php';

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
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class Functions2LibTest extends PHPUnit\Framework\TestCase
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

        print __METHOD__." db->type=".$db->type." user->id=".$user->id;
        //print " - db ".$db->db;
        print "\n";
    }

    // Static methods
    public static function setUpBeforeClass()
    {
        global $conf,$user,$langs,$db;
        //$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

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
     * testJsUnEscape
     *
     * @return void
     */
    public function testJsUnEscape()
    {
        $result=jsUnEscape('%u03BD%u03B5%u03BF');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals('νεο', $result);
    }

    /**
     * testIsValidMailDomain
     *
     * @return void
     */
    public function testIsValidMailDomain()
    {
    }

    /**
     * testIsValidURL
     *
     * @return	void
     */
    public function testIsValidUrl()
    {
	    //Simple check
	    $result = isValidUrl('http://google.com');
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('goo=gle');	// This is good, it might be an alias of hostname
	    $this->assertEquals(1, $result);

	    //With scheme check
    	$result = isValidUrl('http://www.google.com', 1);
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('ftp://www.google.com', 1);
	    $this->assertEquals(0, $result);

	    //With password check invalid. This test should be ko but currently it is not
	    //$result = isValidUrl('http://user:password@http://www.google.com', 1, 1);
	    //$this->assertEquals(0, $result);

	    //With password check valid
	    $result = isValidUrl('http://user:password@www.google.com', 1, 1);
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('http://www.google.com', 1, 1);
	    $this->assertEquals(0, $result);

	    //With port check
	    $result = isValidUrl('http://google.com:8080', 0, 0, 1);
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('http://google.com', 0, 0, 1);
	    $this->assertEquals(0, $result);

	    //With path check
	    $result = isValidUrl('http://google.com/search', 0, 0, 0, 1);
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('http://google.com', 0, 0, 0, 0);
	    $this->assertEquals(1, $result);

	    //With query check
	    $result = isValidUrl('http://google.com/search?test=test', 0, 0, 0, 0, 1);
	    $this->assertEquals(1, $result);

	    //With query check
	    $result = isValidUrl('http://google.com?test=test', 0, 0, 0, 0, 1);
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('http://google.com', 0, 0, 0, 0, 1);
	    $this->assertEquals(0, $result);

	    //With anchor check
	    $result = isValidUrl('http://google.com/search#done', 0, 0, 0, 0, 0, 1);
	    $this->assertEquals(1, $result);

	    $result = isValidUrl('http://google.com/search', 0, 0, 0, 0, 0, 1);
	    $this->assertEquals(0, $result);
    }

    /**
     * testIsIP
     *
     * @return	void
     */
    public function testIsIP()
    {
    	// Not valid
    	$ip='a299.299.299.299';
    	$result=is_ip($ip);
        print __METHOD__." for ".$ip." result=".$result."\n";
    	$this->assertEquals(0, $result, $ip);

    	// Reserved IP range (not checked by is_ip function)
    	$ip='169.254.0.0';
    	$result=is_ip($ip);
        print __METHOD__." for ".$ip." result=".$result."\n";
    	//$this->assertEquals(2,$result,$ip);      // Assertion disabled because returned value differs between PHP patch version

    	$ip='1.2.3.4';
    	$result=is_ip($ip);
        print __METHOD__." for ".$ip." result=".$result."\n";
    	$this->assertEquals(1, $result, $ip);

    	// Private IP ranges
    	$ip='10.0.0.0';
    	$result=is_ip($ip);
        print __METHOD__." for ".$ip." result=".$result."\n";
    	$this->assertEquals(2, $result, $ip);

    	$ip='172.16.0.0';
    	$result=is_ip($ip);
        print __METHOD__." for ".$ip." result=".$result."\n";
    	$this->assertEquals(2, $result, $ip);

        $ip='192.168.0.0';
        $result=is_ip($ip);
        print __METHOD__." for ".$ip." result=".$result."\n";
        $this->assertEquals(2, $result, $ip);
    }
}
