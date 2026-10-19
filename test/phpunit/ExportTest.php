<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
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
 *      \file       test/phpunit/ExportTest.php
 *		\ingroup    test
 *      \brief      PHPUnit test
 *		\remarks	To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/exports/class/export.class.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/files.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/functions2.lib.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (! defined('NOREQUIREUSER')) {
	define('NOREQUIREUSER', '1');
}
if (! defined('NOREQUIREDB')) {
	define('NOREQUIREDB', '1');
}
if (! defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (! defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (! defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (! defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (! defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1'); // If there is no menu to show
}
if (! defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1'); // If we don't need to load the html.form.class.php
}
if (! defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (! defined("NOLOGIN")) {
	define("NOLOGIN", '1');       // If this page is public (can be called outside logged session)
}


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class ExportTest extends CommonClassTest
{
	/**
	 * Other tests
	 *
	 * @return void
	 */
	public function testExportCsvUtf()
	{
		global $conf,$user,$langs,$db;

		$model = 'csvutf8';

		$conf->global->EXPORT_CSV_SEPARATOR_TO_USE = ',';
		print 'EXPORT_CSV_SEPARATOR_TO_USE = ' . getDolGlobalString('EXPORT_CSV_SEPARATOR_TO_USE');

		// Creation of class to export using model ExportXXX
		$dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once $dir.$file;
		$objmodel = new $classname($db);

		// First test without option USE_STRICT_CSV_RULES
		unset($conf->global->USE_STRICT_CSV_RULES);

		$valtotest = 'A simple string';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'A simple string');

		$valtotest = 'A string with , and ; inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with , and ; inside"', 'Error in csvClean for '.$file);

		$valtotest = 'A string with " inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with "" inside"');

		$valtotest = 'A string with " inside and '."\r\n".' carriage returns';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with "" inside and \n carriage returns"');

		$valtotest = 'A string with <a href="aaa"><strong>html<br>content</strong></a> inside<br>'."\n";
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with <a href=""aaa""><strong>html<br>content</strong></a> inside"');

		// Same tests with strict mode
		$conf->global->USE_STRICT_CSV_RULES = 1;

		$valtotest = 'A simple string';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'A simple string');

		$valtotest = 'A string with , and ; inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with , and ; inside"');

		$valtotest = 'A string with " inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with "" inside"');

		$valtotest = 'A string with " inside and '."\r\n".' carriage returns';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "\"A string with \"\" inside and \r\n carriage returns\"");

		$valtotest = 'A string with <a href="aaa"><strong>html<br>content</strong></a> inside<br>'."\n";
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with <a href=""aaa""><strong>html<br>content</strong></a> inside"');
	}


	/**
	 * Other tests
	 *
	 * @return void
	 */
	public function testExportOther()
	{
		global $conf,$user,$langs,$db;

		$model = 'csviso';

		$conf->global->EXPORT_CSV_SEPARATOR_TO_USE = ',';
		print 'EXPORT_CSV_SEPARATOR_TO_USE = ' . getDolGlobalString('EXPORT_CSV_SEPARATOR_TO_USE');

		// Creation of class to export using model ExportXXX
		$dir = DOL_DOCUMENT_ROOT . "/core/modules/export/";
		$file = "export_".$model.".modules.php";
		$classname = "Export".$model;
		require_once $dir.$file;
		$objmodel = new $classname($db);

		// First test without option USE_STRICT_CSV_RULES
		unset($conf->global->USE_STRICT_CSV_RULES);

		$valtotest = 'A simple string';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'A simple string');

		$valtotest = 'A string with , and ; inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with , and ; inside"', 'Error in csvClean for '.$file);

		$valtotest = 'A string with " inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with "" inside"');

		$valtotest = 'A string with " inside and '."\r\n".' carriage returns';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with "" inside and \n carriage returns"');

		$valtotest = 'A string with <a href="aaa"><strong>html<br>content</strong></a> inside<br>'."\n";
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with <a href=""aaa""><strong>html<br>content</strong></a> inside"');

		// Same tests with strict mode
		$conf->global->USE_STRICT_CSV_RULES = 1;

		$valtotest = 'A simple string';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, 'A simple string');

		$valtotest = 'A string with , and ; inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with , and ; inside"');

		$valtotest = 'A string with " inside';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, '"A string with "" inside"');

		$valtotest = 'A string with " inside and '."\r\n".' carriage returns';
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
		print __METHOD__." result=".$result."\n";
		$this->assertEquals($result, "\"A string with \"\" inside and \r\n carriage returns\"");

		$valtotest = 'A string with <a href="aaa"><strong>html<br>content</strong></a> inside<br>'."\n";
		print __METHOD__." valtotest=".$valtotest."\n";
		$result = csvClean($valtotest, $langs->charset_output);
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

		$sql = "SELECT f.ref as f_ref, f.total_ht as f_total, f.total_tva as f_tva FROM ".MAIN_DB_PREFIX."facture f";

		$objexport = new Export($db);
		//$objexport->load_arrays($user,$datatoexport);

		// Define properties
		$datatoexport = 'test';
		$array_selected = array("f.ref" => 1, "f.total" => 2, "f.tva" => 3);
		$array_export_fields = array("f.ref" => "FacNumber", "f.total" => "FacTotal", "f.tva" => "FacVat");
		$array_alias = array("f_ref" => "ref", "f_total" => "total", "f_tva" => "tva");
		$objexport->array_export_fields[0] = $array_export_fields;
		$objexport->array_export_alias[0] = $array_alias;

		dol_mkdir($conf->export->dir_temp);

		$model = 'csviso';

		// Build export file
		print "Process build_file for model = ".$model."\n";
		$result = $objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult = 1;
		$this->assertEquals($expectedresult, $result, 'Error in CSV export');

		$model = 'csvutf8';

		// Build export file
		print "Process build_file for model = ".$model."\n";
		$result = $objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult = 1;
		$this->assertEquals($expectedresult, $result, 'Error in CSV export');

		$model = 'tsv';

		// Build export file
		print "Process build_file for model = ".$model."\n";
		$result = $objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult = 1;
		$this->assertEquals($expectedresult, $result, 'Error in TSV export');

		$model = 'excel2007';

		// Build export file
		/* ko on php 7.4 on travis (zip not available) */
		print "Process build_file for model = ".$model."\n";
		$result = $objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
		$expectedresult = 1;
		$this->assertEquals($expectedresult, $result, 'Error in Excel2007 export');

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
		$sql = "SELECT f.ref as f_ref, f.total_ht as f_total_ht, f.total_tva as f_total_tva FROM ".MAIN_DB_PREFIX."facture f";

		$objexport=new Export($db);
		//$objexport->load_arrays($user,$datatoexport);

		// Define properties
		$datatoexport='test_filtered';
		$array_selected = array("f.ref"=>1, "f.total_ht"=>2, "f.total_tva"=>3);
		$array_export_fields = array("f.ref"=>"FacNumber", "f.total_ht"=>"FacTotal", "f.total_tva"=>"FacVat");
		$array_filtervalue = array("f.total_ht" => ">100");
		$array_filtered = array("f.total_ht" => 1);
		$array_alias = array("f_ref"=>"ref", "f_total_ht"=>"total_ht", "f_total_tva"=>"total_tva");
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
		$this->assertEquals(true, true);
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
		global $conf,$user,$langs,$db,$mysoc;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;
		$mysoc = $this->savmysoc;

		$model = 'csviso';

		print "ccccccccccccccccc\n";
		print var_export($db, true);
		print var_export($mysoc->db, true);

		$filterdatatoexport = '';
		//$filterdatatoexport='';
		//$array_selected = array("s.rowid"=>1, "s.nom"=>2);	// Must be fields found into declaration of dataset

		// Load properties of arrays to make export
		$objexport = new Export($db);
		$result = $objexport->load_arrays($user, $filterdatatoexport);	// This load ->array_export_xxx properties for datatoexport

		// Loop on each dataset
		foreach ($objexport->array_export_code as $key => $datatoexport) {
			$exportfile = $conf->export->dir_temp.'/'.$user->id.'/export_'.$datatoexport.'.csv';
			print "Process export for dataset ".$datatoexport." into ".$exportfile."\n";
			dol_delete_file($exportfile);

			// Generate $array_selected
			$i = 0;
			$array_selected = array();
			foreach ($objexport->array_export_fields[$key] as $key => $val) {
				$array_selected[$key] = $i++;
			}
			//var_dump($array_selected);

			// Build export file
			$sql = "";
			$result = $objexport->build_file($user, $model, $datatoexport, $array_selected, array(), $sql);
			$expectedresult = 1;
			$this->assertEquals($expectedresult, $result, "Call build_file() to export ".$exportfile.' failed: '.$objexport->error);
			$result = dol_is_file($exportfile);
			$this->assertTrue($result, 'File '.$exportfile.' not found');
		}

		return true;
	}

	/**
	 * Test export datasets SQL is restricted in the same way than the UI for a restricted user
	 *
	 * @return void
	 */
	public function testExportDatasetSqlScopingForRestrictedUser()
	{
		global $conf, $user, $langs, $db;

		$savuser = $user;

		// Build a restricted internal user (no thirdparty advanced visibility, no hierarchy-wide read rights)
		$restricteduser = new User($db);
		$restricteduser->id = 999999901;
		$restricteduser->rights = new stdClass();

		// Build a user with all the rights that remove the export scope restrictions
		$fulluser = new User($db);
		$fulluser->id = 999999902;
		$fulluser->rights = new stdClass();
		$fulluser->rights->societe = new stdClass();
		$fulluser->rights->societe->client = new stdClass();
		$fulluser->rights->societe->client->voir = 1;
		$fulluser->rights->projet = new stdClass();
		$fulluser->rights->projet->all = new stdClass();
		$fulluser->rights->projet->all->lire = 1;
		$fulluser->rights->expensereport = new stdClass();
		$fulluser->rights->expensereport->readall = 1;
		$fulluser->rights->salaries = new stdClass();
		$fulluser->rights->salaries->readall = 1;
		$fulluser->rights->salaries->readchild = 1;

		$datasets = array(
			'modContrat' => array('contrat_1', 'contrat', 'AND (co.fk_soc IS NULL OR sc.fk_user = ID)', 'societe_commerciaux'),
			'modFicheinter' => array('ficheinter_1', 'ficheinter', 'AND (f.fk_soc IS NULL OR sc.fk_user = ID)', 'societe_commerciaux'),
			'modTicket' => array('ticket_1', 'ticket', 'AND (t.fk_soc IS NULL OR EXISTS (SELECT sc.fk_soc FROM '.MAIN_DB_PREFIX.'societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ID))', 'societe_commerciaux'),
			'modProjet' => array('projet_1', 'projet', 'AND p.rowid IN (', 'projet'),
			'modExpenseReport' => array('expensereport_1', 'expensereport', 'AND d.fk_user_author IN (', 'expensereport'),
			'modSalaries' => array('salaries_1', 'salaries', 'AND s.fk_user = ID', 'salary')
		);

		// Phase 1: with a restricted user, every dataset must include a scope restriction
		$user = $restricteduser;

		foreach ($datasets as $modulefile => $datasetdef) {
			list($exportcode, $modulename, $expectedclause, $expectedtable) = $datasetdef;
			$expectedclause = str_replace('ID', (string) $restricteduser->id, $expectedclause);

			require_once DOL_DOCUMENT_ROOT.'/core/modules/'.$modulefile.'.class.php';
			$mod = new $modulefile($db);

			$found = 0;
			$sql = '';
			foreach ($mod->export_code as $key => $code) {
				if ($code != $exportcode) {
					continue;
				}
				$found++;
				$sql = $mod->export_sql_start[$key].$mod->export_sql_end[$key];
			}

			$this->assertEquals(1, $found, 'Dataset '.$exportcode.' not found into '.$modulefile);

			print __METHOD__." dataset=".$exportcode." sql=".$sql."\n";

			$regex = '/'.preg_quote(MAIN_DB_PREFIX.$expectedtable, '/').'/';
			$result = (bool) preg_match($regex, $sql);
			$this->assertTrue($result, 'Dataset '.$exportcode.' does not join expected table '.$expectedtable);
			$this->assertStringContainsString($expectedclause, $sql, 'Dataset '.$exportcode.' is not restricted for a user without permission to see all records');
		}

		// Phase 2: with a user that has all visibility rights, no dataset must be restricted
		$user = $fulluser;

		foreach ($datasets as $modulefile => $datasetdef) {
			list($exportcode, $modulename, $expectedclause, $expectedtable) = $datasetdef;
			$expectedclause = str_replace('ID', (string) $fulluser->id, $expectedclause);

			if (!isModEnabled($modulename) || !isModEnabled('societe')) {
				print __METHOD__." dataset=".$exportcode." skipped (module not enabled)\n";
				continue;
			}

			$mod = new $modulefile($db);

			$found = 0;
			$sql = '';
			foreach ($mod->export_code as $key => $code) {
				if ($code != $exportcode) {
					continue;
				}
				$found++;
				$sql = $mod->export_sql_start[$key].$mod->export_sql_end[$key];
			}

			$this->assertEquals(1, $found, 'Dataset '.$exportcode.' not found into '.$modulefile);
			$this->assertStringNotContainsString($expectedclause, $sql, 'Dataset '.$exportcode.' is wrongly restricted for a user with all visibility rights');
		}

		$user = $savuser;

		return true;
	}
}
