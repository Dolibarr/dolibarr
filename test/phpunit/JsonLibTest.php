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
 *      \file       test/phpunit/JsonLibTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';

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
class JsonLibTest extends PHPUnit\Framework\TestCase
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
     * testJsonEncode
     *
     * @return  void
     */
    public function testJsonEncode()
    {
        //$this->sharedFixture
        global $conf,$user,$langs,$db;
        $this->savconf=$conf;
        $this->savuser=$user;
        $this->savlangs=$langs;
        $this->savdb=$db;

        // Do a test with an array starting with 0
        $arraytotest=array(0=>array('key'=>1,'value'=>'PRODREF','label'=>'Product ref with é and special chars \\ \' "'));
        $arrayencodedexpected='[{"key":1,"value":"PRODREF","label":"Product ref with \u00e9 and special chars \\\\ \' \""}]';

        $encoded=json_encode($arraytotest);
        $this->assertEquals($arrayencodedexpected, $encoded);
        $decoded=json_decode($encoded, true);
        $this->assertEquals($arraytotest, $decoded, 'test for json_xxx');

        $encoded=dol_json_encode($arraytotest);
        $this->assertEquals($arrayencodedexpected, $encoded);
        $decoded=dol_json_decode($encoded, true);
        $this->assertEquals($arraytotest, $decoded, 'test for dol_json_xxx');

        // Same test but array start with 2 instead of 0
        $arraytotest=array(2=>array('key'=>1,'value'=>'PRODREF','label'=>'Product ref with é and special chars \\ \' "'));
        $arrayencodedexpected='{"2":{"key":1,"value":"PRODREF","label":"Product ref with \u00e9 and special chars \\\\ \' \""}}';

        $encoded=json_encode($arraytotest);
        $this->assertEquals($arrayencodedexpected, $encoded);
        $decoded=json_decode($encoded, true);
        $this->assertEquals($arraytotest, $decoded, 'test for json_xxx');

        $encoded=dol_json_encode($arraytotest);
        $this->assertEquals($arrayencodedexpected, $encoded);
        $decoded=dol_json_decode($encoded, true);
        $this->assertEquals($arraytotest, $decoded, 'test for dol_json_xxx');

        // Test with object
        $now=gmmktime(12, 0, 0, 1, 1, 1970);
        $objecttotest=new stdClass();
        $objecttotest->property1='abc';
        $objecttotest->property2=1234;
        $objecttotest->property3=$now;
        $encoded=dol_json_encode($objecttotest);
        $this->assertEquals('{"property1":"abc","property2":1234,"property3":43200}', $encoded);
    }
}
