<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2020      Maxime DEMAREST      <maxime@indelog.fr>
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
 *	\file       htdocs/compta/paiement/rapport.php
 *	\ingroup    facture
 *	\brief      Payment reports page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/rapport/pdf_paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$action = GETPOST('action', 'aZ09');

$socid = 0;
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$dir = $conf->facture->dir_output.'/payments';
if (!$user->hasRight('societe', 'client', 'voir') || $socid) {
	$dir .= '/private/'.$user->id; // If user has no permission to see all, output dir is specific to user
}

$year = GETPOST('year', 'int');
if (!$year) {
	$year = date("Y");
}

// Security check
if (!$user->hasRight('facture', 'lire')) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'builddoc') {
	$rap = new pdf_paiement($db);

	$outputlangs = $langs;
	if (GETPOST('lang_id', 'aZ09')) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang(GETPOST('lang_id', 'aZ09'));
	}

	// We save charset_output to restore it because write_file can change it if needed for
	// output format that does not support UTF8.
	$sav_charset_output = $outputlangs->charset_output;
	if ($rap->write_file($dir, GETPOST("remonth", "int"), GETPOST("reyear", "int"), $outputlangs) > 0) {
		$outputlangs->charset_output = $sav_charset_output;
	} else {
		$outputlangs->charset_output = $sav_charset_output;
		dol_print_error($db, $obj->error);
	}

	$year = GETPOST("reyear", "int");
}


/*
 * View
 */

$formother = new FormOther($db);
$formfile = new FormFile($db);

llxHeader();

$titre = ($year ? $langs->trans("PaymentsReportsForYear", $year) : $langs->trans("PaymentsReports"));
print load_fiche_titre($titre, '', 'bill');

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
$year_dirs = dol_dir_list($dir, 'directories', 0, '^[0-9]{4}$', '', 'DESC');
foreach ($year_dirs as $d) {
	print '<a href="'.$_SERVER["PHP_SELF"].'?year='.$d['name'].'">'.$d['name'].'</a> &nbsp;';
}

if ($year) {
	if (is_dir($dir.'/'.$year)) {
		if (!empty($year_dirs)) {
			print '<br>';
		}
		print '<br>';
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Reporting").'</td>';
		print '<td class="right">'.$langs->trans("Size").'</td>';
		print '<td class="right">'.$langs->trans("Date").'</td>';
		print '</tr>';

		$files = (dol_dir_list($dir.'/'.$year, 'files', 0, '^payments-[0-9]{4}-[0-9]{2}\.pdf$', '', 'name', 'DESC', 1));
		foreach ($files as $f) {
			$relativepath = $f['level1name'].'/'.$f['name'];
			print '<tr class="oddeven">';
			print '<td><a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?modulepart=facture_paiement&amp;file='.urlencode($relativepath).'">'.img_pdf().' '.$f['name'].'</a>'.$formfile->showPreview($f['name'], 'facture_paiement', $relativepath, 0).'</td>';
			print '<td class="right">'.dol_print_size($f['size']).'</td>';
			print '<td class="right">'.dol_print_date($f['date'], "dayhour").'</td>';
			print '</tr>';
		}
		print '</table>';
	}
}

// End of page
llxFooter();
$db->close();
