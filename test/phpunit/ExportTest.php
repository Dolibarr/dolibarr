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
 *      \file       test/phpunit/ImportTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/exports/class/export.class.php';

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
 * When no cover is provided. We use everything.
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ExportTest extends PHPUnit_Framework_TestCase
{
	protected $savconf;
	protected $savuser;
	protected $savlangs;
	protected $savdb;

	/**
	 * Constructor
	 * We save global variables into local variables
	 *
	 * @return ExportTest
	 */
	function ExportTest()
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
	 * Ran on start
	 *
	 * @return void
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
	 * Ran on start
	 *
	 * @return void
	 */
    protected function tearDown()
    {
    	print __METHOD__."\n";
    }


    /**
     * Test export function
     *
	 * @return void
     */
    public function testExportPersonalizedExport()
    {
        global $conf,$user,$langs,$db;

        $sql = "SELECT f.facnumber as f_facnumber, f.amount as f_amount, f.total as f_total, f.tva as f_tva FROM ".MAIN_DB_PREFIX."facture f";

        $objexport=new Export($db);
        //$objexport->load_arrays($user,$datatoexport);

        // Define properties
        $datatoexport='test';
        $array_selected = array("f.facnumber"=>1, "f.amount"=>2, "f.total"=>3, "f.tva"=>4);
        $array_export_fields = array("f.facnumber"=>"FacNumber", "f.amount"=>"FacAmount", "f.total"=>"FacTotal", "f.tva"=>"FacVat");
        $array_alias = array("f_facnumber"=>"facnumber", "f_amount"=>"amount", "f_total"=>"total", "f_tva"=>"tva");
        $objexport->array_export_fields[0]=$array_export_fields;
        $objexport->array_export_alias[0]=$array_alias;

        dol_mkdir($conf->export->dir_temp);
        
        $model='csv';

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $sql);
		$expectedresult=1;
        $this->assertEquals($result,$expectedresult);

        $model='tsv';

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $sql);
		$expectedresult=1;
        $this->assertEquals($result,$expectedresult);

        $model='excel';

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $sql);
		$expectedresult=1;
        $this->assertEquals($result,$expectedresult);

        return true;
    }

    /**
     * Test export function
     *
	 * @return void
     */
    public function testExportSociete()
    {
        global $conf,$user,$langs,$db;

        $sql = "";
        $datatoexport='societe_1';
        $array_selected = array("s.rowid"=>1, "s.nom"=>2);	// Mut be fields found into declaration of dataset
        $model='csv';
        
        $objexport=new Export($db);
        $result=$objexport->load_arrays($user,$datatoexport);

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $sql);
		$expectedresult=1;
        $this->assertEquals($result,$expectedresult);

        return true;
    }
}
?>