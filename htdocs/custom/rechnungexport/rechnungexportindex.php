<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 */

/**
 *	\file       rechnungexport/rechnungexportindex.php
 *	\ingroup    rechnungexport
 *	\brief      Home page of rechnungexport top menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) { $i--; $j--; }
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("rechnungexport@rechnungexport"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->rechnungexport->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$action = GETPOST('action', 'aZ09');

$max = 5;
$now = dol_now();

//public function createInvoiceZip() {

	//return 0;
//}
/*
 * Actions
 */
if($action == 'chooseMonth') {
	$timespan = GETPOST('month', 'aZ09');
	$timespan = str_replace("-", "", $timespan);
	$ym = str_split($timespan,2);
	setlocale (LC_ALL, "de_DE.UTF-8");
	$year = $ym[0].$ym[1];
	$month = strftime("%B",mktime(null,null,null,$ym[2]));
	$filetime = $ym[1].$ym[2];

	$zip = new ZipArchive();
	$filename = DOL_DATA_ROOT."/rechnungexport/Rechnungen_".$month."_".$year.".zip";
	$zip->open($filename,ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$allInvoices = scandir(DOL_DATA_ROOT . "/facture");
	foreach ($allInvoices as $invDir) {
		if(preg_match_all("/[A-Z]{2}$filetime-[0-9]{4}/", $invDir)) {
			$zip->addFile(DOL_DATA_ROOT . "/facture/" . $invDir . "/" . $invDir . ".pdf", $invDir.".pdf");
		}
	}
	$tempi = $zip->numFiles;
	if($zip->numFiles == 0) {
		$zip->addFromString("IN DIESEM MONAT KEINE RECHNUNGEN GEFUNDEN","");
	}
	$zip->close();
	$action = '';
}
elseif ($action == 'delete') {
	$file = $conf->rechnungexport->dir_output."/".basename(GETPOST('urlfile', 'alpha'));
	$ret = dol_delete_file($file, 1);
	if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	$action = '';
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

llxHeader("", $langs->trans("Rechnungen Export"));

print load_fiche_titre($langs->trans("Alle Rechnungen eines Monats als zip-Datei exportieren"), '', 'file-export');

print '<div class="fichecenter"><div class="fichehalfleft">';

// Pre-chosen month is last month
$chosen = intval(date('m'));
$chosen--;
$chosen = ($chosen == 0) ? 1 : $chosen;
$chosen = (string)$chosen;
$chosen = (strlen($chosen) == 1) ? "0".$chosen : $chosen;
$chosen = date('Y-').$chosen;

print '<form action="' . $_SERVER["PHP_SELF"] . '" method="POST">';
print 'zip-Datei erstellen für: ';
print '<input type="month" name="month" value="'.$chosen.'" max="'.$chosen.'">';
print '<input type="hidden" name="action" value="chooseMonth">';
print '<input type="submit" value="Erstellen" class="butAction">';
print '</form>';

print '</div><div class="fichehalfright">';
$zipdir = "/rechnungexport/";
$zips = scandir(DOL_DATA_ROOT.$zipdir);

$sortfield = GETPOST('sortfield', 'aZ09');
$sortorder = GETPOST('sortorder', 'aZ09');
$sortfield = ($sortfield == '') ? 'date' : $sortfield;
$sortorder = ($sortorder == '') ? 'desc' : $sortorder;

$filearray = dol_dir_list($conf->rechnungexport->dir_output, 'files', 0, '', '', $sortfield, (strtolower($sortorder) == 'asc' ?SORT_ASC:SORT_DESC), 1);
$formfile->list_of_documents($filearray, null, 'rechnungexport', '', 1, '', 1, 0, $langs->trans("NoBackupFileAvailable"), 0, $langs->trans("bestehende zip-Dateien"));

print '</div></div>';

// End of page
llxFooter();
$db->close();
