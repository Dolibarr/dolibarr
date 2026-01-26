<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2013 ATM Consulting <support@atm-consulting.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Handle files with Mac line endings (http://stackoverflow.com/questions/4541749/fgetcsv-fails-to-read-line-ending-in-mac-formatted-csv-file-any-better-solution)

require 'config.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';

dol_include_once('/bankimport/class/bankimport.class.php');
dol_include_once('/compta/facture/class/facture.class.php');

global $db, $langs;

$langs->load('bills');
$hookmanager = new HookManager($db);
$hookmanager->initHooks('bankimport');

ini_set("auto_detect_line_endings", true);

$mesg = "";
$form = new Form($db);
$tpl = 'tpl/bankimport.new.tpl.php';

$import = new BankImport($db);

if(GETPOST('compare','alphanohtml')) {
	$action = "compare";
	$parameters = array("moduleName" => "bankimport");
	$reshook = $hookmanager->executeHooks('doActions', $parameters, $import, $action);
	if($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	$datestart = dol_mktime(0, 0, 0, GETPOST('dsmonth','int'), GETPOST('dsday','int'), GETPOST('dsyear','int'));
	$dateend = dol_mktime(0, 0, 0, GETPOST('demonth','int'), GETPOST('deday','int'), GETPOST('deyear','int'));
	$numreleve = GETPOST('numreleve','alphanohtml');
	$hasHeader = GETPOST('hasheader','alphanohtml');

	if($import->analyse(GETPOST('accountid','int'), 'bankimportfile', $datestart, $dateend, $numreleve, $hasHeader)) {
		$import->load_transactions(GETPOST('bankimportseparator','alphanohtml'), GETPOST('bankimportdateformat','alphanohtml'), GETPOST('bankimportmapping','alphanohtml'));
		$import->compare_transactions();

		$TTransactions = $import->TFile;

		global $bc;

		$langs->load('bankimport@bankimport');
		$var = true;
		$tpl = 'tpl/bankimport.check.tpl.php';
	}
} else if(GETPOST('import','alphanohtml')) {

	if(
		$import->analyse(
			GETPOST('accountid','int'),
			GETPOST('filename','alpha'),
			GETPOST('datestart','int'),
			GETPOST('dateend','int'),
			GETPOST('numreleve','alphanohtml'),
			GETPOST('hasheader','alphanohtml')
		)
	) {
		$import->load_transactions(GETPOST('bankimportseparator','alpha'), GETPOST('bankimportdateformat','alphanohtml'), GETPOST('bankimportmapping','alphanohtml'));
		$import->import_data(GETPOST('TLine','array'));
		$tpl = 'tpl/bankimport.end.tpl.php';
	}
}

llxHeader('', $langs->trans('TitleBankImport'));

print_fiche_titre($langs->trans("TitleBankImport"));

include($tpl);

$action = "index";
$parameters = array("moduleName" => "bankimport", "tpl" => $tpl);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $import, $action);
if($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

llxFooter();
$db->close();
