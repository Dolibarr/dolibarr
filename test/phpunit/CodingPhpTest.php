<?php
/* Copyright (C) 2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Alexandre Janniaux   <alexandre.janniaux@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       test/phpunit/CodingPhpTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
//define('TEST_DB_FORCE_TYPE','mysql');	// This is to force using mysql driver
//require_once 'PHPUnit/Autoload.php';
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security.lib.php';
require_once dirname(__FILE__).'/../../htdocs/core/lib/security2.lib.php';
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

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->getrights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CodingPhpTest extends CommonClassTest
{
	/**
	 * Return list of files for which to verify Php checks
	 *
	 * @return array{name:string,path:string,level1name:string,relativename:string,fullname:string,date:string,size:int,perm:int,type:string} List of php files to check (dol_dir_list)
	 */
	public function phpFilesProvider()
	{
		// File functions are needed
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


		$excludeRegexList
			= array(
				'\/includes\/',
				'\/install\/doctemplates\/websites\/',
				'\/custom\/',
				'\/dolimed',
				'\/nltechno',
				'\/teclib',
			);
		$fullRegex = '(?:'.implode('|', $excludeRegexList).')';
		$filesarray = dol_dir_list(DOL_DOCUMENT_ROOT, 'files', 1, '\.php', [$fullRegex], 'fullname', SORT_ASC, 0, 1, '', 1);

		/*
		$filteredArray =  array_filter(
			$filesarray,
			static function($file) use (&$fullRegex) {
				return !preg_match($fullRegex, $file['relativename']);
			}
		));
		*/
		return array_map(function ($value) {
			return array($value);
		}, $filesarray);
	}

	/**
	 * testPHP
	 *
	 * @param array{name:string,path:string,level1name:string,relativename:string,fullname:string,date:string,size:int,perm:int,type:string} $file File information
	 * @return string
	 *
	 * @dataProvider phpFilesProvider
	 */
	public function testPHP($file)
	{
		$this->nbLinesToShow = 1;
		//print 'Check php file '.$file['relativename']."\n";
		$filecontentorigin = file_get_contents($file['fullname']);

		// We are not interested in the comments
		$filecontent = $this->removePhpComments($filecontentorigin);

		// File path for reports
		$report_filepath = "htdocs/{$file['relativename']}";

		$this->verifyIsModuleEnabledOk($filecontent, $report_filepath);

		if (preg_match('/\.class\.php/', $file['relativename'])
			|| preg_match('/boxes\/box_/', $file['relativename'])
			|| preg_match('/modules\/.*\/doc\/(doc|pdf)_/', $file['relativename'])
			|| preg_match('/modules\/(import|mailings|printing)\//', $file['relativename'])
			|| in_array($file['name'], array('modules_boxes.php', 'TraceableDB.php'))) {
			// Check Class files
			if (! in_array($file['name'], array(
				'api.class.php',
				'commonobject.class.php',
				'conf.class.php',
				'html.form.class.php',
				'translate.class.php',
				'utils.class.php',
				'TraceableDB.php',
				'multicurrency.class.php'
			))) {
				// Must not find $db->
				$ok = true;
				$matches = array();
				// Check string $db-> inside a class.php file (it should be $this->db-> in such classes)
				preg_match_all('/'.preg_quote('$db->', '/').'/', $filecontent, $matches, PREG_SET_ORDER);
				foreach ($matches as $key => $val) {
					$ok = false;
					break;
				}
				//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
				$this->assertTrue($ok, 'Found string $db-> in a .class.php file in '.$file['relativename'].'. Inside a .class file, you should use $this->db-> instead.');
				//exit;
			}

			if (preg_match('/\.class\.php$/', $file['relativename']) && ! in_array($file['relativename'], array(
				'adherents/class/adherent.class.php',
				'adherents/canvas/actions_adherentcard_common.class.php',
				'contact/canvas/actions_contactcard_common.class.php',
				'compta/facture/class/facture.class.php',
				'core/class/commonobject.class.php',
				'core/class/extrafields.class.php',
				'core/class/html.form.class.php',
				'core/class/html.formfile.class.php',
				'core/class/html.formcategory.class.php',
				'core/class/html.formmail.class.php',
				'core/class/html.formother.class.php',
				'core/class/html.formsms.class.php',
				'core/class/html.formticket.class.php',
				'core/class/utils.class.php',
				'core/class/openid.class.php',
				'fourn/class/fournisseur.facture.class.php',
				'societe/canvas/actions_card_common.class.php',
				'societe/canvas/individual/actions_card_individual.class.php',
				'ticket/class/actions_ticket.class.php',
				'ticket/class/ticket.class.php',
				'webportal/class/context.class.php',
				'webportal/class/html.formcardwebportal.class.php',
				'webportal/class/html.formlistwebportal.class.php',
				'webportal/controllers/document.controller.class.php',
				'workstation/class/workstation.class.php',
			))) {
				// Must not find GETPOST
				$ok = true;
				$matches = array();
				// Check string GETPOSTFLOAT a class.php file (should not be found in classes)
				preg_match_all('/GETPOST\(["\'](....)/', $filecontent, $matches, PREG_SET_ORDER);
				foreach ($matches as $key => $val) {
					if (in_array($val[1], array('lang', 'forc', 'mass', 'conf'))) {
						continue;
					}
					//var_dump($val);
					$ok = false;
					break;
				}
				$this->assertTrue($ok, 'Found string GETPOST in a .class.php file in '.$file['relativename'].'.');
			}
		} else {
			// Check Include files
			if (! in_array($file['name'], array(
				'objectline_view.tpl.php',
				'extrafieldsinexport.inc.php',
				'extrafieldsinimport.inc.php',
				'DolQueryCollector.php',
				'DoliStorage.php'
			))) {
				// Must not found $this->db->
				$ok = true;
				$matches = array();
				// Check string $this->db-> in a non class.php file (it should be $db-> in such classes)
				preg_match_all('/'.preg_quote('$this->db->', '/').'/', $filecontent, $matches, PREG_SET_ORDER);
				foreach ($matches as $key => $val) {
					$ok = false;
					break;
				}
				//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
				$this->assertTrue($ok, 'Found string "$this->db->" in '.$file['relativename']);
				//exit;
			}
		}

		// Check we don't miss top_httphead() in any ajax pages
		if (preg_match('/ajax\//', $file['relativename'])) {
			//print "Analyze ajax page ".$file['relativename']."\n";
			$ok = true;
			$matches = array();
			preg_match_all('/top_httphead/', $filecontent, $matches, PREG_SET_ORDER);
			if (count($matches) == 0) {
				$ok = false;
			}
			//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
			$this->assertTrue($ok, 'Did not find top_httphead in the ajax page '.$file['relativename']);
			//exit;
		}

		// Check for unauthorised vardumps
		if (!preg_match('/test\/phpunit/', $file['fullname'])) {
			$this->verifyNoActiveVardump($filecontent, $report_filepath);
		}

		// Check get_class followed by __METHOD__
		$ok = true;
		$matches = array();
		preg_match_all('/'.preg_quote('get_class($this)."::".__METHOD__', '/').'/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found string get_class($this)."::".__METHOD__ that must be replaced with __METHOD__ only in '.$file['relativename']);
		//exit;

		// Check string $this->db->idate without quotes
		$ok = true;
		$matches = array();
		preg_match_all('/(..)\s*\.\s*\$this->db->idate\(/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($val[1] != '\'"' && $val[1] != '\'\'') {
				$ok = false;
				break;
			}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found a $this->db->idate to forge a sql request without quotes around this date field '.$file['relativename']);
		//exit;



		// Check sql string DELETE|OR|AND|WHERE|INSERT ... yyy = ".$xxx
		//  with xxx that is not 'thi' (for $this->db->sanitize) and 'db-' (for $db->sanitize). It means we forget a ' if string, or an (int) if int, when forging sql request.
		$ok = true;
		$matches = array();
		preg_match_all('/(DELETE|OR|AND|WHERE|INSERT)\s.*([^\s][^\s][^\s])\s*=\s*(\'|")\s*\.\s*\$(...)/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($val[2] == 'ity' && $val[4] == 'con') {		// exclude entity = ".$conf->entity
				continue;
			}
			if ($val[2] == 'ame' && $val[4] == 'db-' && preg_match('/WHERE name/', $val[0])) {		// exclude name = ".$db->encrypt(
				continue;
			}
			if ($val[2] == 'ame' && $val[4] == 'thi' && preg_match('/WHERE name/', $val[0])) {		// exclude name = ".$this->db->encrypt(
				continue;
			}
			var_dump($matches);
			$ok = false;
			break;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non quoted or not casted var in sql request '.$file['relativename'].' - Bad.');
		//exit;

		// Check that forged sql string is using ' instead of " as string PHP quotes
		$ok = true;
		$matches = array();
		preg_match_all('/\$sql \.= \'\s*VALUES.*\$/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			//if ($val[1] != '\'"' && $val[1] != '\'\'') {
			var_dump($matches);
			$ok = false;
			break;
			//}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found a forged SQL string that mix on same line the use of \' for PHP string and PHP variables in file '.$file['relativename'].' Use " to forge PHP string like this: $sql = "SELECT ".$myvar...');
		//exit;

		// Check that forged sql string is using ' instead of " as string PHP quotes
		$ok = true;
		$matches = array();
		preg_match_all('/\$sql \.?= \'SELECT.*\$/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			var_dump($matches);
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found a forged SQL string that mix on same line the use of \' for PHP string and PHP variables in file '.$file['relativename'].' Use " to forge PHP string like this: $sql = "SELECT ".$myvar...');

		// Check sql string VALUES ... , ".$xxx
		//  with xxx that is not 'db-' (for $db->escape). It means we forget a ' if string, or an (int) if int, when forging sql request.
		$ok = true;
		$matches = array();
		preg_match_all('/(VALUES).*,\s*"\s*\.\s*\$(...)/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($val[1] == 'VALUES' && $val[2] == 'db-') {		// exclude $db->escape(
				continue;
			}
			if ($val[1] == 'VALUES' && $val[2] == 'thi' && preg_match('/this->db->encrypt/', $val[0])) {	// exclude ".$this->db->encrypt(
				continue;
			}
			var_dump($matches);
			$ok = false;
			break;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non quoted or not casted var in sql request '.$file['relativename'].' - Bad.');
		//exit;

		// Check '".$xxx non escaped

		// Check string   ='".$this->xxx   with xxx that is not 'escape'. It means we forget a db->escape when forging sql request.
		$ok = true;
		$matches = array();
		preg_match_all('/=\s*\'"\s*\.\s*\$this->(....)/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($val[1] != 'db->' && $val[1] != 'esca') {
				$ok = false;
				break;
			}
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non escaped string in building of a sql request (case 1) in '.$file['relativename'].' - Bad.');

		// Check string sql|set|WHERE|...'".$yyy->xxx   with xxx that is not 'escape', 'idate', .... It means we forget a db->escape when forging sql request.
		$ok = true;
		$matches = array();
		$found = "";
		preg_match_all('/(sql|SET|WHERE|where|INSERT|insert|VALUES|LIKE).+\s*\'"\s*\.\s*\$(.......)/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if (! in_array($val[2], array('this->d', 'this->e', 'db->esc', 'dbs->es', 'dbs->id', 'mydb->e', 'dbsessi', 'db->ida', 'escaped', 'exclude', 'include'))) {
				$found = $val[0];
				$ok = false;	// This will generate error
				break;
			}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non escaped string in building of a sql request (case 2) in '.$file['relativename'].': '.$found.' - Bad.');
		//exit;

		// Check string sql|set...'.$yyy->xxx   with xxx that is not 'escape', 'idate', .... It means we forget a db->escape when forging sql request.
		$ok = true;
		$matches = array();
		$found = "";
		preg_match_all('/(\$sql|SET\s|WHERE\s|INSERT\s|VALUES\s|VALUES\().+\s*\'\s*\.\s*\$(.........)/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if (! in_array($val[2], array('this->db-', 'db->prefi', 'db->sanit', 'dbs->pref', 'dbs->sani', 'conf->ent', 'key : \'\')', 'key])."\')', 'excludefi', 'regexstri', ''))) {
				$found = $val[0];
				$ok = false;
				var_dump($matches);
				break;
			}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non escaped string in building of a sql request (case 3) in '.$file['relativename'].': '.$found.' - Bad.');
		//exit;

		// Check string sql|set...=".... without (int). It means we forget a cast (int) when forging sql request.
		$ok = true;
		$matches = array();
		$found = "";
		// $sql .= " field = ".(isset($this->field) ? $this->escape($this->field) : "null")... is KO
		// $sql .= " field = ".(isset($this->field) ? "'".$this->escape($this->field)."'" : "null")... is OK
		/*
		preg_match_all('/(\$sql|VALUES\()[^\'\n]*[^\'\n]"\s*\.\s*([^\n]+)\n/m', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if (! preg_match('/^(implode\(\' OR \', \$search|implode\(\' AND \', \$search|MAIN_DB_PREFIX|accountancy_code|\w+::|\$key|\$db->prefix|\$this->db->prefix|\$predefinedgroupwhere|\$db->sanitize|\$this->db->sanitize|\$db->ifsql|\$db->decrypt|\(int\)|\(float\)|\(\(int\)|\(\(float\)|\$conf->entity|getEntity|\$this->from)/', $val[2])) {
				//print "Found a suspicious string: ".$val[2]."\n";
				if (preg_match('/.+\?.+:.+/', $val[2])) {
					// We found a string that follow the " in $sql .= " "..... and does not contains simple quote for escapement nor casting
					// May be it is later, into the b or c in case of  a ? b : c
					// Example:
					// $val[2] is (isset($this->field) ? $this->escape($this->field) : "null")... is KO
					// $val[2] is (isset($this->field) ? "'".$this->escape($this->field)."'" : "null")... is OK
					$tmps = $val[2];
					$tmps = preg_replace('/^[^\?]+\?/', '', $tmps);
					$tmps2 = explode(':', $tmps, 2);
					$tmps2a = trim($tmps2[0]);
					if (!empty($tmps2[1])) {
						$tmps2b = trim($tmps2[1]);
					} else {
						$tmps2b = '';
					}
				} else {
					$tmps2a = $val[2];
					$tmps2b = '';
				}
				if (preg_match('/^(\(*["\']|\(+int\)|\(+float\)|\(*\d|GETPOSTINT|getDolGlobalInt|dolSqlDateFilter|\$user->id|\$conf->entity|\$this->entity|\$this->where|\(?\$this->societe|str_pad\(\(int)/', $tmps2a)
					&& (empty($tmps2b) || preg_match('/^(\(*["\']|\(+int\)|\(+float\)|\(*\d|GETPOSTINT|getDolGlobalInt|dolSqlDateFilter|\$user->id|\$conf->entity|\$this->entity|\$this->where|\(?\$this->societe|str_pad\(\(int)/', $tmps2b))) {
					continue;	// No problem
				}

				var_dump($val);

				$found = $val[0];
				$ok = false;
				break;
			}
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non escaped or non casted string in building of a sql request (case 4) in '.$file['relativename'].': '.$found.' - Bad.');
		*/

		// Checks with IN

		// Check string ' IN (".xxx' or ' IN (\'.xxx'  with xxx that is not '$this->db->sanitize' and not '$db->sanitize'. It means we forget a db->sanitize when forging sql request.
		$ok = true;
		$matches = array();
		preg_match_all('/\s+IN\s*\([\'"]\s*\.\s*(.........)/i', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			//var_dump($val);
			if (!in_array($val[1], array('$db->sani', '$this->db', 'getEntity', 'WON\',\'L', 'self::STA', 'Commande:', 'CommandeF', 'Entrepot:', 'Facture::', 'FactureFo', 'ExpenseRe', 'Societe::', 'Ticket::S'))) {
				$ok = false;
				break;
			}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non sanitized string in building of a IN or NOT IN sql request '.$file['relativename'].' - Bad.');
		//exit;

		// Check string ' IN (\'".xxx'   with xxx that is not '$this->db->sanitize' and not '$db->sanitize'. It means we forget a db->sanitize when forging sql request.
		$ok = true;
		$matches = array();
		preg_match_all('/\s+IN\s*\(\'"\s*\.\s*(.........)/i', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			//var_dump($val);
			if (!in_array($val[1], array('$db->sani', '$this->db', 'getEntity', 'WON\',\'L', 'self::STA', 'Commande:', 'CommandeF', 'Entrepot:', 'Facture::', 'FactureFo', 'ExpenseRe', 'Societe::', 'Ticket::S'))) {
				$ok = false;
				break;
			}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found non sanitized string in building of a IN or NOT IN sql request '.$file['relativename'].' - Bad.');
		//exit;

		// Test that output of $_SERVER\[\'QUERY_STRING\'\] is escaped.
		$ok = true;
		$matches = array();
		preg_match_all('/(..............)\$_SERVER\[\'QUERY_STRING\'\]/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($val[1] != 'scape_htmltag(' && $val[1] != 'ing_nohtmltag(' && $val[1] != 'dol_escape_js(') {
				$ok = false;
				break;
			}
		}
		$this->assertTrue($ok, 'Found a $_SERVER[\'QUERY_STRING\'] without dol_escape_htmltag neither dol_string_nohtmltag around it, in file '.$file['relativename'].'. Bad.');


		// Check GETPOST(... 'none');
		$ok = true;
		$matches = array();
		preg_match_all('/GETPOST\s*\(([^\)]+),\s*["\']none["\']/i', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			//var_dump($val);
			if (!in_array($val[1], array(
				"'content'", "'replacestring'", "'htmlheader'", "'WEBSITE_HTML_HEADER'", "'WEBSITE_CSS_INLINE'", "'WEBSITE_JS_INLINE'", "'WEBSITE_MANIFEST_JSON'", "'PAGE_CONTENT'", "'WEBSITE_README'", "'WEBSITE_LICENSE'",
					'"mysqldump"', '"postgresqldump"',
					"'db_pass_root'", "'db_pass'", '"pass"', '"pass1"', '"pass2"', '"password"', "'password'"))) {
				$ok = false;
				break;
			}
			//if ($reg[0] != 'db') $ok=false;
		}
		//print __METHOD__." Result for checking we don't have non escaped string in sql requests for file ".$file."\n";
		$this->assertTrue($ok, 'Found a GETPOST that use \'none\' as a parameter in file '.$file['relativename'].' and param is not an allowed parameter for using none - Bad.');
		//exit;


		// Test that first param of print_liste_field_titre is a translation key and not the translated value
		$ok = true;
		$matches = array();
		// Check string ='print_liste_field_titre\(\$langs'.
		preg_match_all('/print_liste_field_titre\(\$langs/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found a use of print_liste_field_titre with first parameter that is a translated value instead of just the translation key in file '.$file['relativename'].'. Bad.');


		// Test we don't have <br />
		$ok = true;
		$matches = array();
		preg_match_all('/<br\s+\/>/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($file['name'] != 'functions.lib.php') {
				$ok = false;
				break;
			}
		}
		$this->assertTrue($ok, 'Found a tag <br /> that is for xml in file '.$file['relativename'].'. You must use html syntax <br> instead.');


		// Test we don't have name="token" value="'.$_SESSION['newtoken'], we must use name="token" value="'.newToken() instead.
		$ok = true;
		$matches = array();
		preg_match_all('/name="token" value="\'\s*\.\s*\$_SESSION/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if ($file['name'] != 'excludefile.php') {
				$ok = false;
				break;
			}
		}
		$this->assertTrue($ok, 'Found a forbidden string sequence in '.$file['relativename'].' : name="token" value="\'.$_SESSION[..., you must use a newToken() instead of $_SESSION[\'newtoken\'].');


		// Test we don't have preg_grep with a param without preg_quote
		$ok = true;
		$matches = array();
		preg_match_all('/preg_grep\(.*\$/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			if (strpos($val[0], 'preg_quote') === false) {
				$ok = false;
				break;
			}
		}
		$this->assertTrue($ok, 'Found a preg_grep with a param that is a $var but without preg_quote in file '.$file['relativename'].'.');


		// Test we don't have "if ($resql >"
		$ok = true;
		$matches = array();
		preg_match_all('/if \(\$resql >/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found a if $resql with a > operator (when $resql is a boolean or resource) in file '.$file['relativename'].'. Please remove the > ... part.');

		// Test we don't have empty($user->hasRight
		$ok = true;
		$matches = array();
		preg_match_all('/empty\(\$user->hasRight/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found code empty($user->hasRight in file '.$file['relativename'].'. empty() must not be used on a var not on a function.');

		// Test we don't have empty(DolibarrApiAccess::$user->hasRight
		$ok = true;
		$matches = array();
		preg_match_all('/empty\(DolibarrApiAccess::\$user->hasRight/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found code empty(DolibarrApiAccess::$user->hasRight in file '.$file['relativename'].'. empty() must not be used on a var not on a function.');

		// Test we don't have empty($user->hasRight
		$ok = true;
		$matches = array();
		preg_match_all('/empty\(getDolGlobal/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found code empty(getDolGlobal... in file '.$file['relativename'].'. empty() must be used on a var not on a function.');

		// Test we don't have @var array(
		$ok = true;
		$matches = array();
		preg_match_all('/@var\s+array\(/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found a declaration @var array() instead of @var array in file '.$file['relativename'].'.');


		// Test we don't have CURDATE()
		$ok = true;
		$matches = array();
		preg_match_all('/CURDATE\(\)/', $filecontent, $matches, PREG_SET_ORDER);
		foreach ($matches as $key => $val) {
			$ok = false;
			break;
		}
		$this->assertTrue($ok, 'Found a CURDATE\(\) in code. Do not use this SQL method in file '.$file['relativename'].'. You must use the PHP function dol_now() instead.');


		// Test we don't have if ($action == 'xxx'... without test on permission
		// We do not test on file into admin, protection is done on page on user->admin
		if (!preg_match('/admin\//', $file['fullname'])
			&& !preg_match('/\.tpl\.php/', $file['fullname'])
			&& !preg_match('/\.lib\.php/', $file['fullname'])
			&& !preg_match('/\.inc\.php/', $file['fullname'])
			&& !preg_match('/\.class\.php/', $file['fullname'])
			&& !preg_match('/NORUN$/', $file['fullname'])) {
			$ok = true;
			$matches = array();

			// Get to part of string to use for analysis
			$reg = array();
			if (preg_match('/\*\s+Action(.*)\*\s+View/ims', $filecontentorigin, $reg)) {
				$filecontentaction = $reg[1];
			} else {
				$filecontentaction = $filecontent;
			}

			preg_match_all('/if\s*\(\s*\$action\s*==\s*[\'"][a-z]+[\'"].*/', $filecontentaction, $matches, PREG_SET_ORDER);
			foreach ($matches as $key => $val) {
				if (!preg_match('/\$user->hasR/', $val[0])
					&& !preg_match('/\$permission/', $val[0])
					&& !preg_match('/\$usercan/', $val[0])
					&& !preg_match('/\$canedit/', $val[0])
					&& !preg_match('/already done/i', $val[0])
					&& !preg_match('/not required/i', $val[0])) {
					$ok = false;
					print "Line: ".$val[0]."\n";
					break;
				}
			}
			$this->assertTrue($ok, 'Found a test on action without check on permission and without comment to say this is expected, in file '.$file['relativename'].'.');
		}
	}


	/**
	 * Verify that no active var_dump was left over in the code
	 *
	 * @param string $filecontent Contents to check for php code that uses a module name
	 * @param string $filename    File name for the contents (used for reporting)
	 *
	 * @return void
	 */
	private function verifyNoActiveVardump(&$filecontent, $filename)
	{
		$ok = true;
		$matches = array();
		// Match!:
		//  - Line-start, whitespace, var_dump
		//  - Line-start, no-comment-leader, var_dump
		//  no-commen-leader=
		//    - Any character not / or *
		//    - Any / not preceded with / and not followed by / or *
		//    - Any * not preceded with /
		preg_match_all('{^(?:^|^(?:[ \t]*|(?:(?:[^*/]|(?<![^/])/(?![*/])|(?!/)\*)(\S))))\bvar_dump\(}m', $filecontent, $matches, PREG_SET_ORDER);
		$failing_string = "";
		foreach ($matches as $key => $val) {
			if (!isset($val[1]) || $val[1] != '/' && $val[1] != '*') {
				$ok = false;
				$failing_string = $val[0];
				break;
			}
		}
		$this->assertTrue($ok, "Found string var_dump that is not just after /* or // in '$filename': $failing_string");
	}


	/**
	 * Provide test data for testing the method detecting var_dump presence.
	 *
	 * @return array<string,array{0:string,1:bool}> Test sets
	 */
	public function vardumpTesterProvider()
	{
		return [
			 'var_dump at start of file' => ["var_dump(\$help)\n", true],
			 'var_dump at start of line' => ["\nvar_dump(\$help)\n", true],
			 'var_dump after comment next line' => ["/* Hello */\nvar_dump(\$help)\n", true],
			 'var_dump with space' => [" var_dump(\$help)\n", true],
			 'var_dump after comment' => [" // var_dump(\$help)\n", false],
			 '2 var_dumps after comment' => [" // var_dump(\$help); var_dump(\$help)\n", false],
			 'var_dump before and after comment' => [" var_dump(\$help); // var_dump(\$help)\n", true],
		];
	}

	/**
	 * Test that verifyNoActiveVardump generates a notification
	 *
	 * @param string $filecontent Fake file content
	 * @param bool   $hasVardump  When true, expect var_dump detection
	 *
	 * @return void
	 *
	 * @dataProvider vardumpTesterProvider
	 */
	public function testVerifyNoActiveVardump(&$filecontent, $hasVardump)
	{
		$this->nbLinesToShow = 1;
		// Create some dummy file content
		$filename = $this->getName(false);

		$notification = false;
		ob_start(); // Do not disturb the output with tests that are meant to fail.
		try {
			$this->verifyNoActiveVardump($filecontent, $filename);
		} catch (Throwable $e) {
			$notification = (string) $e;
		}
		$output = ob_get_clean();

		// Assert that a notification was generated
		if ($hasVardump) {
			$this->assertStringContainsString("Found string var_dump", $notification ?? '', "Expected notification not found.");
		} else {
			$this->assertFalse($notification, "Unexpection detection of var_dump");
		}
	}

	/**
	 * Verify that only known modules are used
	 *
	 * @param string $filecontent Contents to check for php code that uses a module name
	 * @param string $filename    File name for the contents (used for reporting)
	 *
	 * @return void
	 */
	private function verifyIsModuleEnabledOk(&$filecontent, $filename)
	{
		// Verify that only known modules are used
		preg_match_all("/isModEnabled\\(\s*[\"']([^\$\"']+)[\"']\\s*\\)/", $filecontent, $matches, PREG_SET_ORDER);

		foreach ($matches as $key => $val) {
			$module_name = $val[1];
			$this->assertModuleIsOk($module_name, "isModEnabled('$module_name') in '$filename'");
		}
	}

	/**
	 * Assert that the module name is ok, generate appropriate notifications
	 *
	 * @param string $module_name Module name to check
	 * @param string $message     Message to shown in case an assertion fails
	 *
	 * @return void
	 */
	private function assertModuleIsOk($module_name, $message = '')
	{
		if (isset(self::EFFECTIVE_DEPRECATED_MODULE_MAPPING[$module_name])) {
			$new_name = self::EFFECTIVE_DEPRECATED_MODULE_MAPPING[$module_name];
			print("\033[31mDeprecated module name, use '$new_name':\033[0m$message".PHP_EOL);

			//trigger_error("Deprecated module name, use '$new_name': $message", E_USER_NOTICE);
			trigger_error("Deprecated module name, use '$new_name': $message", E_USER_DEPRECATED);
		} else {
			$this->assertTrue(
				array_key_exists($module_name, self::VALID_MODULE_MAPPING)
				|| array_key_exists($module_name, self::DEPRECATED_MODULE_MAPPING),
				"Unknown module: $message"
			);
		}
	}


	/**
	 * Remove php comments from source string
	 *
	 * @param string $string The string from which the PHP comments are removed
	 *
	 * @return string The string without the comments
	 */
	private function removePhpComments($string)
	{
		return preg_replace_callback(
			'{(//.*?$)|(/\*.*?\*/)}ms',
			static function ($match) {
				if (isset($match[2])) {
					// Count the number of newline characters in the comment
					$num_newlines = substr_count($match[0], "\n");
					// Generate whitespace equivalent to the number of newlines
					if ($num_newlines == 0) {
						// /* Comment on single line -> space
						return " ";
					} else {
						// /* Comment on multiple lines -> new lines
						return str_repeat("\n", $num_newlines);
					}
				} else {
					// Double slash comment, just remove
					return "";
				}
			},
			$string
		);
	}

	/**
	 * Provide test data for testing the comments remover
	 *
	 * @return array<string,array{0:string,1:string}> Test sets
	 */
	public function commentRemovalTestProvider()
	{
		return [
			 'complete line 1' => ["/*Comment complete line*/", " "],
			 'complete line 2' => ["// Comment complete line", ""],
			 'partial line 1' => ["a/*Comment complete line*/b", "a b"],
			 'partial line 2' => ["a// Comment complete line", "a"],
			 'multi line full 1' => ["/*Comment\ncomplete line*/", "\n"],
			 'multi line full 2' => ["/*Comment\ncomplete line*/\n", "\n\n"],
			 'multi line partials 1' => ["a/*Comment\ncomplete line*/b", "a\nb"],
		];
	}

	/**
	 * Test that comments are properly removed
	 *
	 * @param string $source	Fake file content
	 * @param bool   $expected	When true, expect var_dump detection
	 *
	 * @return void
	 *
	 * @dataProvider commentRemovalTestProvider
	 */
	public function testRemovePhpComments(&$source, &$expected)
	{
		$this->nbLinesToShow = 0;

		$this->assertEquals($expected, $this->removePhpComments($source), "Comments not removed as expected");
	}
}
