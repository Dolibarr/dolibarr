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
 *    \file       handson/mailindex.php
 *    \ingroup    handson
 *    \brief      Home page of mail menu
 */

// Load Dolibarr environment
$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"] . "/main.inc.php";
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME'];
$tmp2 = realpath(__FILE__);
$i = strlen($tmp) - 1;
$j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--;
	$j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1)) . "/main.inc.php")) $res = @include substr($tmp, 0, ($i + 1)) . "/main.inc.php";
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php")) $res = @include dirname(substr($tmp, 0, ($i + 1))) . "/main.inc.php";
// Try main.inc.php using relative path
if (!$res && file_exists("../main.inc.php")) $res = @include "../main.inc.php";
if (!$res && file_exists("../../main.inc.php")) $res = @include "../../main.inc.php";
if (!$res && file_exists("../../../main.inc.php")) $res = @include "../../../main.inc.php";
if (!$res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT . '/custom/handson/parsecsv-for-php/parsecsv.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

global $conf, $langs, $db;

// Load translation files required by the page
$langs->loadLangs(array("handson@handson"));

$action = GETPOST('action', 'aZ09');


// Security check
// if (! $user->rights->handson->myobject->read) {
// 	accessforbidden();
// }
$socid = GETPOST('socid', 'int');
if (isset($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();
$output = [];

/*
 * Actions
 */
$error = '';
if ($action == 'convert') {

	$csv = new \ParseCsv\Csv();
	$csv->encoding('UTF-8', 'UTF-8');
	$csv->delimiter = ';';
	$csv->parseFile($_FILES['uploadedFile']['tmp_name']);

	foreach ($csv->data as $no => $entry) {
		foreach ($entry as $key => $val) {
			switch ($key) {
				case 'team_id':
					$output[$no][5] = $val;
					//array_push($output[$no], $val);
					break;
				case 'cuser':
					$val = preg_replace('/[0-9]+:\s/', '', $val);
					$name = explode(', ', $val);
					$output[$no][1] = ($name[1] != '') ? $name[1] : 'FEHLT';
					//array_push($output[$no], $name[1]);
					$output[$no][2] = ($name[0] != '') ? $name[0] : 'FEHLT';
					//array_push($output[$no], $name[0]);
					$output[$no][3] = '';
					//array_push($output[$no], '');
					break;
				case 'register_region':
					$val = preg_replace('/[0-9]+:\s/', '', $val);
					$val = str_replace('/', '_', $val);
					$output[$no][8] = ($val != '') ? $val : 'FEHLT';
					//array_push($output[$no], $val);
					break;
				case 'coach-mail':
					$output[$no][0] = ($val != '') ? $val : 'FEHLT';
					//array_push($output[$no], $val);
					break;
				case 'team_name':
					$output[$no][4] = ($val != '') ? $val : 'FEHLT';
					//array_push($output[$no], $val);
					break;
				case 'billing_address_country':
					$output[$no][6] = ($val != '') ? $val : 'FEHLT';
					//array_push($output[$no], $val);
					$output[$no][7] = 'DACH';
					//array_push($output[$no], 'DACH');
					break;
				default:
					// do nothing
					break;

			}
		}
		ksort($output[$no]);
	}

	usort($output, function ($elem1, $elem2) {
		return strcmp($elem1[8], $elem2[8]);
	});

	$last_region = '';
	$i = 0;
	$csv_out = new \ParseCsv\Csv();
	$csv_out->encoding('UTF-8', 'UTF-8');
	$csv_out->delimiter = ';';

	if(!is_dir(DOL_DATA_ROOT . '/handson/csvtmp/')) mkdir(DOL_DATA_ROOT . '/handson/csvtmp/');
	if(!is_dir(DOL_DATA_ROOT . '/handson/temp/')) mkdir(DOL_DATA_ROOT . '/handson/temp/');

	foreach ($output as $entry) {
		$region = $entry[8];
		unset($entry[8]);
		if ($region == $last_region) {
			$csv_out->data[$i++] = array("CoachEmail" => $entry[0], "CoachFirstName" => $entry[1], "CoachLastName" => $entry[2], "CoachPhone" => $entry[3], "TeamName" => $entry[4], "TeamNumber" => $entry[5], "TeamCountry" => $entry[6], "TeamRegion" => $entry[7]);
		} else {
			if ($last_region != '') {
				$csv_out->enclose_all = true;
				$csv_out->save(DOL_DATA_ROOT . '/handson/csvtmp/' . $last_region . '.csv', $csv_out->data, true, array("CoachEmail", "CoachFirstName", "CoachLastName", "CoachPhone", "TeamName", "TeamNumber", "TeamCountry", "TeamRegion"));
			}
			$last_region = $region;
			unset($csv_out->data);
			$i = 0;
			$csv_out->data[$i++] = array("CoachEmail" => "CoachEmail", "CoachFirstName" => "CoachFirstName", "CoachLastName" => "CoachLastName", "CoachPhone" => "CoachPhone", "TeamName" => "TeamName", "TeamNumber" => "TeamNumber", "TeamCountry" => "TeamCountry", "TeamRegion" => "TeamRegion");
			$csv_out->data[$i++] = array("CoachEmail" => $entry[0], "CoachFirstName" => $entry[1], "CoachLastName" => $entry[2], "CoachPhone" => $entry[3], "TeamName" => $entry[4], "TeamNumber" => $entry[5], "TeamCountry" => $entry[6], "TeamRegion" => $entry[7]);

		}
	}

	$zip = new ZipArchive();
	$theirname = str_replace('.csv', '', $_FILES['uploadedFile']['name']);
	$filename = DOL_DATA_ROOT . "/handson/temp/" . $theirname . '_' . date('d-m-y-H-i-s') . ".zip";
	$zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE);
	$allFiles = scandir(DOL_DATA_ROOT . "/handson/csvtmp/");
	foreach ($allFiles as $file) {
		if (preg_match('/\.csv/', $file)) {
			$zip->addFile(DOL_DATA_ROOT . "/handson/csvtmp/" . $file, $file);
		}

	}
	$zip->close();

	foreach ($allFiles as $file) {
		if (preg_match('/\.csv/', $file)) {
			unlink(DOL_DATA_ROOT . "/handson/csvtmp/" . $file);
		}
	}
	$action = 'showDownload';
} elseif ($action == 'delete') {
	$file = DOL_DATA_ROOT."/handson/temp/".basename(GETPOST('urlfile', 'alpha'));
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

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';
$com = new Commande($db);

llxHeader("", $langs->trans("HandsOnArea"));
print load_fiche_titre($langs->trans("CSVs aus Contao für den Hub aufbereiten"), '', 'object_list-alt');

print load_fiche_titre($langs->trans("Neue CSV-Datei hinzufügen"), '', 'file-import');

print '<form action="csv_for_hub.php" method="POST" enctype="multipart/form-data">';
print '<input type="hidden" name="action" value="convert">';
print '<input type="file" name="uploadedFile">';
print '<input type="submit" value="Hochladen" class="button">';
print '</form>';

print '<hr>';

$sortfield = GETPOST('sortfield', 'aZ09');
$sortorder = GETPOST('sortorder', 'aZ09');
$sortfield = ($sortfield == '') ? 'date' : $sortfield;
$sortorder = ($sortorder == '') ? 'desc' : $sortorder;

$filearray = dol_dir_list($conf->handson->dir_output.'/temp', 'files', 0, '', '', $sortfield, (strtolower($sortorder) == 'asc' ?SORT_ASC:SORT_DESC), 1);
$formfile->list_of_documents($filearray, null, 'handson_temp', '', 1, '', 1, 0, $langs->trans("NoBackupFileAvailable"), 0, $langs->trans("bestehende zip-Dateien"));



// End of page
llxFooter();
$db->close();

