<?php
/* Copyright (C) 2017		ATM-Consulting  	 <support@atm-consulting.fr>
 * Copyright (C) 2020		Maxime DEMAREST  	 <maxime@indelog.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/fourn/facture/rapport.php
 *	\ingroup    fourn
 *	\brief      Payment reports page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/rapport/pdf_paiement_fourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->loadLangs(array('bills'));

// Security check
$socid = '';
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'fournisseur', 0, 'facture_fourn', 'facture');

$action = GETPOST('action', 'aZ09');
$fileToRemove = GETPOST('removefile', 'alpha');

$socid = 0;
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$dir = $conf->fournisseur->facture->dir_output.'/payments';
if (!$user->hasRight("societe", "client", "voir") || $socid) {
	$dir .= '/private/'.$user->id; // If user has no permission to see all, output dir is specific to user
}

$year = GETPOSTINT("year");
if (!$year) {
	$year = date("Y");
}

$permissiontoread = ($user->hasRight("fournisseur", "facture", "lire") || $user->hasRight("supplier_invoice", "lire"));
$permissiontoadd = ($user->hasRight("fournisseur", "facture", "creer") || $user->hasRight("supplier_invoice", "creer"));


/*
 * Actions
 */

if ($action == 'builddoc' && $permissiontoread) {
	$rap = new pdf_paiement_fourn($db);

	$outputlangs = $langs;
	if (GETPOST('lang_id', 'aZ09')) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
	}

	// We save charset_output to restore it because write_file can change it if needed for
	// output format that does not support UTF8.
	$sav_charset_output = $outputlangs->charset_output;
	if ($rap->write_file($dir, GETPOSTINT("remonth"), GETPOSTINT("reyear"), $outputlangs) > 0) {
		$outputlangs->charset_output = $sav_charset_output;
	} else {
		$outputlangs->charset_output = $sav_charset_output;
		dol_print_error($db, $obj->error);
	}

	$year = GETPOSTINT("reyear");
}

// Delete file from disk
if ($action == 'removedoc' && $permissiontoread && $fileToRemove) {
	$fileDirectory = dirname($dir.'/'.$fileToRemove);
	if (dol_delete_file($dir.'/'.$fileToRemove)) {
		// Delete empty directory after file deletion
		if (empty(dol_dir_list($fileDirectory))) {
			dol_delete_dir($fileDirectory);
		}
		setEventMessages($langs->trans("FileWasRemoved", $fileToRemove), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", $fileToRemove), null, 'errors');
	}
}


/*
 * View
 */

$formother = new FormOther($db);
$formfile = new FormFile($db);

$titre = ($year ? $langs->trans("PaymentsReportsForYear", $year) : $langs->trans("PaymentsReports"));

llxHeader('', $titre, '', '', 0, 0, '', '', '', 'mod-fourn-facture page-rapport');

print load_fiche_titre($titre, '', 'supplier_invoice');

// Formulaire de generation
print '<form method="post" action="rapport.php?year='.$year.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="builddoc">';
$cmonth = GETPOST("remonth") ? GETPOST("remonth") : date("n", time());
$syear = GETPOST("reyear") ? GETPOST("reyear") : date("Y", time());

print $formother->select_month($cmonth, 'remonth');

print $formother->selectyear($syear, 'reyear');

print '<input type="submit" class="button" value="'.$langs->trans("Create").'">';
print '</form>';
print '<br>';

clearstatcache();

// Show link on other years
$linkforyear = array();
$found = 0;
if (is_dir($dir)) {
	$handle = opendir($dir);
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			if (is_dir($dir.'/'.$file) && !preg_match('/^\./', $file) && is_numeric($file)) {
				$found = 1;
				$linkforyear[] = $file;
			}
		}
	}
}
asort($linkforyear);
foreach ($linkforyear as $cursoryear) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?year='.$cursoryear.'">'.$cursoryear.'</a> &nbsp;';
}

if ($year) {
	if (is_dir($dir.'/'.$year)) {
		$handle = opendir($dir.'/'.$year);

		if ($found) {
			print '<br>';
		}
		print '<br>';
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Reporting").'</td>';
		print '<td class="right">'.$langs->trans("Size").'</td>';
		print '<td class="right">'.$langs->trans("Date").'</td>';
		print '<td class="right"></td>';
		print '</tr>';

		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^supplier_payment/i', $file)) {
					$tfile = $dir.'/'.$year.'/'.$file;
					$relativepath = $year.'/'.$file;
					print '<tr class="oddeven"><td><a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture_fournisseur&amp;file=payments/'.urlencode($relativepath).'">'.img_pdf().' '.$file.'</a>'.$formfile->showPreview($file, 'facture_fournisseur', 'payments/'.$relativepath, 0).'</td>';
					print '<td class="right">'.dol_print_size(dol_filesize($tfile)).'</td>';
					print '<td class="right">'.dol_print_date(dol_filemtime($tfile), "dayhour").'</td>';
					print '<td class="right"><a href="rapport.php?removefile='.urlencode($relativepath).'&action=removedoc&token='.newToken().'">'.img_delete().'</a></td>';
					print '</tr>';
				}
			}
			closedir($handle);
		}

		print '</table>';
	}
}

// End of page
llxFooter();
$db->close();
