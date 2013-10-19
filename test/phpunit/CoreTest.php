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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       test/phpunit/CoreTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

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
 * Class to test core functions
 *
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
	function __construct()
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
     * testDetectURLROOT
     *
     * @return	void
     */
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

		// Case 1:
		// Test for subdir dolibarr (that point to htdocs) in root directory /var/www
		// URL: http://localhost/dolibarrnew/admin/system/phpinfo.php
    	$_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhost';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www';
		$_SERVER["SCRIPT_NAME"]='/dolibarrnew/admin/system/phpinfo.php';
		$expectedresult='/dolibarrnew';

		// Case 2:
		// Test for subdir aaa (that point to dolibarr) in root directory /var/www
		// URL: http://localhost/aaa/htdocs/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhost';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www';
		$_SERVER["SCRIPT_NAME"]='/aaa/htdocs/admin/system/phpinfo.php';
		$expectedresult='/aaa/htdocs';

		// Case 3:
		// Test for virtual host localhostdolibarrnew that point to htdocs directory with
		// a direct document root
		// URL: http://localhostdolibarrnew/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhostdolibarrnew';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/home/ldestail/workspace/dolibarr/htdocs';
		$_SERVER["SCRIPT_NAME"]='/admin/system/phpinfo.php';
		$expectedresult='';

		// Case 4:
		// Test for virtual host localhostdolibarrnew that point to htdocs directory with
		// a symbolic link
		// URL: http://localhostdolibarrnew/admin/system/phpinfo.php
        $_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhostdolibarrnew';
        $_SERVER["SERVER_PORT"]='80';
        $_SERVER["DOCUMENT_ROOT"]='/var/www/dolibarr';	// This is a link that point to /home/ldestail/workspace/dolibarr/htdocs
		$_SERVER["SCRIPT_NAME"]='/admin/system/phpinfo.php';
		$expectedresult='';

		// Case 5:
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

		// Case 6:
		// Test when using nginx
		// URL: https://localhost/dolibarr/admin/system/phpinfo.php
		$_SERVER["HTTPS"]='';
        $_SERVER["SERVER_NAME"]='localhost';
        $_SERVER["SERVER_PORT"]='80';
		$_SERVER["DOCUMENT_ROOT"]='/var/www/dolibarr/htdocs';
		$_SERVER["SCRIPT_NAME"]='/dolibarr/admin/system/phpinfo.php';
		$expectedresult='/dolibarr';
		// Put this into conf.php because autodetect will fails in this case
		//$dolibarr_main_url_root='http://localhost/dolibarr';

		// Force to rerun filefunc.inc.php
		include dirname(__FILE__).'/../../htdocs/filefunc.inc.php';

		print __METHOD__." DOL_MAIN_URL_ROOT=".DOL_MAIN_URL_ROOT."\n";
		print __METHOD__." DOL_URL_ROOT=".DOL_URL_ROOT."\n";
//		$this->assertEquals(DOL_URL_ROOT,$expectedresult);

		return true;
    }


    /**
     * testSqlAndScriptInject
     * 
     * @return	void
     */
    public function testSqlAndScriptInject()
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
		
				
		// This is code copied from main.inc.php
		
		/**
		 * Security: SQL Injection and XSS Injection (scripts) protection (Filters on GET, POST, PHP_SELF).
		 *
		 * @param		string		$val		Value
		 * @param		string		$type		1=GET, 0=POST, 2=PHP_SELF
		 * @return		int						>0 if there is an injection
		 */
		function test_sql_and_script_inject($val, $type)
		{
		    $sql_inj = 0;
		    // For SQL Injection (only GET and POST are used to be included into bad escaped SQL requests)
		    if ($type != 2)
		    {
		        $sql_inj += preg_match('/delete[\s]+from/i', $val);
		        $sql_inj += preg_match('/create[\s]+table/i', $val);
		        $sql_inj += preg_match('/update.+set.+=/i', $val);
		        $sql_inj += preg_match('/insert[\s]+into/i', $val);
		        $sql_inj += preg_match('/select.+from/i', $val);
		        $sql_inj += preg_match('/union.+select/i', $val);
		        $sql_inj += preg_match('/(\.\.%2f)+/i', $val);
		    }
		    // For XSS Injection done by adding javascript with script
		    // This is all cases a browser consider text is javascript:
		    // When it found '<script', 'javascript:', '<style', 'onload\s=' on body tag, '="&' on a tag size with old browsers
		    // All examples on page: http://ha.ckers.org/xss.html#XSScalc
		    $sql_inj += preg_match('/<script/i', $val);
		    if (! defined('NOSTYLECHECK')) $sql_inj += preg_match('/<style/i', $val);
		    $sql_inj += preg_match('/base[\s]+href/i', $val);
		    if ($type == 1)
		    {
		        $sql_inj += preg_match('/javascript:/i', $val);
		        $sql_inj += preg_match('/vbscript:/i', $val);
		    }
		    // For XSS Injection done by adding javascript closing html tags like with onmousemove, etc... (closing a src or href tag with not cleaned param)
		    if ($type == 1) $sql_inj += preg_match('/"/i', $val);		// We refused " in GET parameters value
		    if ($type == 2) $sql_inj += preg_match('/[\s;"]/', $val);	// PHP_SELF is an url and must match url syntax
		    return $sql_inj;
		}
		
    	//type=2 key=0 value=/DIR WITH SPACE/htdocs/admin/index.php?mainmenu=home&leftmenu=setup&username=weservices
    	$_SERVER["PHP_SELF"]='/DIR WITH SPACE/htdocs/admin/index.php?mainmenu=home&leftmenu=setup&username=weservices';
    	$result=test_sql_and_script_inject($_SERVER["PHP_SELF"],2);
		$expectedresult=1;
		
		$this->assertEquals($result,$expectedresult);
    }
}
?>