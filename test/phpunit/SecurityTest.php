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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
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

    // Static methods
    public static function setUpBeforeClass()
    {
    	global $conf,$user,$langs,$db;
		$db->begin();	// This is to have all actions inside a transaction even if test launched without suite.

    	print __METHOD__."\n";
    }

    // tear down after class
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
        $_GET["param3"]='"a/b#e(pr)qq-rr\cc';    // Same than param2 + "
        $_GET["param4"]='../dir';
        $_GET["param5"]="a_1-b";

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

        $result=GETPOST("param3", 'alpha');  // Must return '' as there is a forbidden char "
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param4", 'alpha');  // Must return '' as there is a forbidden char ../
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        // Test aZ09
        $result=GETPOST("param1", 'aZ09');  // Must return '' as there is a forbidden char ../
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, $_GET["param1"]);

        $result=GETPOST("param2", 'aZ09');  // Must return '' as there is a forbidden char ../
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param3", 'aZ09');  // Must return '' as there is a forbidden char ../
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param4", 'aZ09');  // Must return '' as there is a forbidden char ../
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '');

        $result=GETPOST("param5", 'aZ09');
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, $_GET["param5"]);

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
        $genpass3=getRandomPassword(false);				// Should return a password of 8 chars
        print __METHOD__." genpass3=".$genpass3."\n";
        $this->assertEquals(strlen($genpass3), 8);

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
}
