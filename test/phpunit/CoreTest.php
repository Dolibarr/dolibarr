<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       test/phpunit/SecurityTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *      \version    $Id$
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/lib/functions.lib.php';

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
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CoreTest extends PHPUnit_Framework_TestCase
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
	function CoreTest()
	{
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
    public static function tearDownAfterClass()
    {
    	global $conf,$user,$langs,$db;
		//$db->rollback();

		print __METHOD__."\n";
    }

	/**
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
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }


    /**
     */
    public function testDetectURLROOT()
    {
    	// Test for subdir dolibarr (that point to htdocs) in root directory /var/www
		// URL: http://localhost/dolibarrnew/admin/system/phpinfo.php
    	$_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhost';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www';
		$_SERVER["SCRIPT_NAME"]='/dolibarrnew/admin/system/phpinfo.php';
		$expectedresult='/dolibarrnew';
		// Put this into conf.php if you want to test alt
		//$dolibarr_main_url_root='http://localhost/dolibarralias';
		//$dolibarr_main_url_root_alt='http://localhost/dolibarralias/custom2';

    	// Test for subdir aaa (that point to dolibarr) in root directory /var/www
		// URL: http://localhost/aaa/htdocs/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhost';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www';
		$_SERVER["SCRIPT_NAME"]='/aaa/htdocs/admin/system/phpinfo.php';
		$expectedresult='/aaa/htdocs';
		// Put this into conf.php if you want to test alt
		//$dolibarr_main_url_root='http://localhost/dolibarralias';
		//$dolibarr_main_url_root_alt='http://localhost/dolibarralias/custom2';

		// Test for virtual host localhostdolibarrnew that point to htdocs directory with
		// a direct document root
		// URL: http://localhostdolibarrnew/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhostdolibarrnew';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/home/ldestail/workspace/dolibarr/htdocs';
		$_SERVER["SCRIPT_NAME"]='/admin/system/phpinfo.php';
		$expectedresult='';
		// Put this into conf.php if you want to test alt
		//$dolibarr_main_url_root='http://localhost/dolibarralias';
		//$dolibarr_main_url_root_alt='http://localhost/dolibarralias/custom2';

		// Test for virtual host localhostdolibarrnew that point to htdocs directory with
		// a symbolic link
		// URL: http://localhostdolibarrnew/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhostdolibarrnew';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www/dolibarr';	// This is a link that point to /home/ldestail/workspace/dolibarr/htdocs
		$_SERVER["SCRIPT_NAME"]='/admin/system/phpinfo.php';
		$expectedresult='';
		// Put this into conf.php if you want to test alt
		//$dolibarr_main_url_root='http://localhost/dolibarralias';
		//$dolibarr_main_url_root_alt='http://localhost/dolibarralias/custom2';

		// Test for alias /dolibarralias
		// URL: http://localhost/dolibarralias/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhost';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www';
		$_SERVER["SCRIPT_NAME"]='/dolibarralias/admin/system/phpinfo.php';
		$expectedresult='/dolibarralias';
		// Put this into conf.php because autodetect will fails in this case
		//$dolibarr_main_url_root='http://localhost/dolibarralias';
		//$dolibarr_main_url_root_alt='http://localhost/dolibarralias/custom2';

		include dirname(__FILE__).'/../../htdocs/filefunc.inc.php';
		print __METHOD__." DOL_MAIN_URL_ROOT=".DOL_MAIN_URL_ROOT."\n";
		print __METHOD__." DOL_URL_ROOT=".DOL_URL_ROOT."\n";
        print __METHOD__." DOL_MAIN_URL_ROOT_ALT=".DOL_MAIN_URL_ROOT_ALT."\n";
		print __METHOD__." DOL_URL_ROOT_ALT=".DOL_URL_ROOT_ALT."\n";
		$this->assertEquals(DOL_URL_ROOT,$expectedresult);

		return true;
    }

}
?>