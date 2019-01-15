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
 *      \file       test/phpunit/ImportTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/exports/class/export.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';

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
     * Other tests
     *
     * @return void
     */
    public function testExportOther()
    {
        global $conf,$user,$langs,$db;

        $model='csv';

        // Creation of class to export using model ExportXXX
        $dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
        $file = "export_".$model.".modules.php";
        $classname = "Export".$model;
        require_once $dir.$file;
        $objmodel = new $classname($this->db);

        // First test without option USE_STRICT_CSV_RULES
        unset($conf->global->USE_STRICT_CSV_RULES);

        $valtotest='A simple string';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, 'A simple string');

        $valtotest='A string with , and ; inside';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with , and ; inside"');

        $valtotest='A string with " inside';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with "" inside"');

        $valtotest='A string with " inside and '."\r\n".' carriage returns';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with "" inside and \n carriage returns"');

        $valtotest='A string with <a href="aaa"><strong>html<br>content</strong></a> inside<br>'."\n";
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with <a href=""aaa""><strong>html<br>content</strong></a> inside"');

        // Same tests with strict mode
        $conf->global->USE_STRICT_CSV_RULES=1;

        $valtotest='A simple string';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, 'A simple string');

        $valtotest='A string with , and ; inside';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with , and ; inside"');

        $valtotest='A string with " inside';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with "" inside"');

        $valtotest='A string with " inside and '."\r\n".' carriage returns';
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, "\"A string with \"\" inside and \r\n carriage returns\"");

        $valtotest='A string with <a href="aaa"><strong>html<br>content</strong></a> inside<br>'."\n";
        print __METHOD__." valtotest=".$valtotest."\n";
        $result = $objmodel->csvClean($valtotest, $langs->charset_output);
        print __METHOD__." result=".$result."\n";
        $this->assertEquals($result, '"A string with <a href=""aaa""><strong>html<br>content</strong></a> inside"');
    }

    /**
     * Test export function for a personalized dataset
     *
     * @depends	testExportOther
	 * @return void
     */
    public function testExportPersonalizedExport()
    {
        global $conf,$user,$langs,$db;

        $sql = "SELECT f.ref as f_ref, f.total as f_total, f.tva as f_tva FROM ".MAIN_DB_PREFIX."facture f";

        $objexport=new Export($db);
        //$objexport->load_arrays($user,$datatoexport);

        // Define properties
        $datatoexport='test';
        $array_selected = array("f.ref"=>1, "f.total"=>2, "f.tva"=>3);
        $array_export_fields = array("f.ref"=>"FacNumber", "f.total"=>"FacTotal", "f.tva"=>"FacVat");
        $array_alias = array("f_ref"=>"ref", "f_total"=>"total", "f_tva"=>"tva");
        $objexport->array_export_fields[0]=$array_export_fields;
        $objexport->array_export_alias[0]=$array_alias;

        dol_mkdir($conf->export->dir_temp);

        $model='csv';

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult=1;
        $this->assertEquals($expectedresult,$result);

        $model='tsv';

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult=1;
        $this->assertEquals($expectedresult,$result);

        $model='excel';

        // Build export file
        $result=$objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult=1;
        $this->assertEquals($expectedresult,$result);

        return true;
    }

    /**
     * Test export function for a personalized dataset with filters
     *
     * @depends	testExportPersonalizedExport
     * @return void
     */
    public function testExportPersonalizedWithFilter()
    {
    	global $conf,$user,$langs,$db;
/*
    	$sql = "SELECT f.ref as f_ref, f.total as f_total, f.tva as f_tva FROM ".MAIN_DB_PREFIX."facture f";

    	$objexport=new Export($db);
    	//$objexport->load_arrays($user,$datatoexport);

    	// Define properties
    	$datatoexport='test_filtered';
    	$array_selected = array("f.ref"=>1, "f.total"=>2, "f.tva"=>3);
    	$array_export_fields = array("f.ref"=>"FacNumber", "f.total"=>"FacTotal", "f.tva"=>"FacVat");
    	$array_filtervalue = array("f.total" => ">100");
    	$array_filtered = array("f.total" => 1);
    	$array_alias = array("f_ref"=>"ref", "f_total"=>"total", "f_tva"=>"tva");
    	$objexport->array_export_fields[0]=$array_export_fields;
    	$objexport->array_export_alias[0]=$array_alias;

    	dol_mkdir($conf->export->dir_temp);

    	$model='csv';

    	// Build export file
    	$result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $array_filtervalue, $sql);
    	$expectedresult=1;
    	$this->assertEquals($expectedresult,$result);

    	$model='tsv';

    	// Build export file
    	$result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $array_filtervalue, $sql);
    	$expectedresult=1;
    	$this->assertEquals($expectedresult,$result);

    	$model='excel';

    	// Build export file
    	$result=$objexport->build_file($user, $model, $datatoexport, $array_selected, $array_filtervalue, $sql);
    	$expectedresult=1;
    	$this->assertEquals($expectedresult,$result);
*/
    	return true;
    }

    /**
     * Test export function for all dataset predefined into modules
     *
     * @depends	testExportPersonalizedWithFilter
	 * @return void
     */
    public function testExportModulesDatasets()
    {
        global $conf,$user,$langs,$db;

        $model='csv';

        $filterdatatoexport='';
        //$filterdatatoexport='';
        //$array_selected = array("s.rowid"=>1, "s.nom"=>2);	// Mut be fields found into declaration of dataset

        // Load properties of arrays to make export
        $objexport=new Export($db);
        $result=$objexport->load_arrays($user,$filterdatatoexport);	// This load ->array_export_xxx properties for datatoexport

        // Loop on each dataset
        foreach($objexport->array_export_code as $key => $datatoexport)
        {
        	$exportfile=$conf->export->dir_temp.'/'.$user->id.'/export_'.$datatoexport.'.csv';
	        print "Process export for dataset ".$datatoexport." into ".$exportfile."\n";
	        dol_delete_file($exportfile);

	        // Generate $array_selected
	        $i=0;
	        $array_selected=array();
			foreach($objexport->array_export_fields[$key] as $key => $val)
			{
				$array_selected[$key]=$i++;
			}
			//var_dump($array_selected);

	        // Build export file
        	$sql = "";
			$result=$objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
			$expectedresult=1;
	        $this->assertEquals($expectedresult, $result, "Call build_file() to export ".$exportfile.' failed');
	        $result=dol_is_file($exportfile);
	        $this->assertTrue($result, 'File '.$exportfile.' not found');
        }

        return true;
    }
}
