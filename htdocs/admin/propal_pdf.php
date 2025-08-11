<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville        <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur         <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio         <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier              <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Eric Seigne                 <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin               <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
 * Copyright (C) 2011-2013 Juanjo Menent			   <jmenent@2byte.es>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/admin/propal_pdf.php
 *		\ingroup    propale
 *		\brief      Setup page for commercial proposal module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "other", "errors", "propal"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'propal';
$dirforterms = $conf->propal->dir_output.'/';
if (!empty($conf->propal->multidir_output[$conf->entity])) {
	$dirforterms = $conf->propal->multidir_output[$conf->entity].'/';
}

$varname = 'MAIN_INFO_PROPAL_TERMSOFSALE';
$error = 0;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == "update") {
	if (GETPOSTISSET('MAIN_PDF_ADD_TERMSOFSALE_PROPAL')) {
		dolibarr_set_const($db, "MAIN_PDF_ADD_TERMSOFSALE_PROPAL", GETPOST("MAIN_PDF_ADD_TERMSOFSALE_PROPAL", 'int'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
		dolibarr_set_const($db, "MAIN_GENERATE_PROPOSALS_WITH_PICTURE", GETPOST("MAIN_GENERATE_PROPOSALS_WITH_PICTURE"), 'chaine', 0, '', $conf->entity);
	}

	// Add file
	if ($_FILES[$varname]["name"]) {
		if (!preg_match('/(\.pdf)$/i', $_FILES[$varname]["name"])) {	// Document can be used on a lot of different places. Only pdf can be supported.
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadFormat"), null, 'errors');
		} else {
			$original_file = $_FILES[$varname]["name"];
			$result = dol_move_uploaded_file($_FILES[$varname]["tmp_name"], $dirforterms.$original_file, 1, 0, $_FILES[$varname]['error']);
			if ($result) {
				dolibarr_set_const($db, $varname, $original_file, 'chaine', 0, '', $conf->entity);
			}
		}
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}

// Terms of sale
if ($action == 'removetermsofsale') {
	$filename = getDolGlobalString('MAIN_INFO_PROPAL_TERMSOFSALE');
	$file = $dirforterms.'/'.$filename;

	if ($filename != '') {
		dol_delete_file($file);
	}
	dolibarr_del_const($db, 'MAIN_INFO_PROPAL_TERMSOFSALE', $conf->entity);
}


/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

$maxfilesizearray = getMaxFileSizeArray();
$tooltipconcatpdf = ($maxfilesizearray['maxmin'] > 0) ? $langs->trans('MaxSize').' : '.$maxfilesizearray['maxmin'].' '.$langs->trans('Kb') : '';
$documenturl = DOL_URL_ROOT.'/document.php';
if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
	$documenturl = getDolGlobalString('DOL_URL_ROOT_DOCUMENT_PHP');
}

llxHeader('', $langs->trans("PropalSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-propal');

//if ($mesg) print $mesg;

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';

print load_fiche_titre($langs->trans("PropalSetup"), $linkback, 'title_setup');

$head = propal_admin_prepare_head();

print dol_get_fiche_head($head, 'pdf', $langs->trans("Proposals"), -1, 'propal');

print '<form enctype="multipart/form-data" method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px"></td></tr>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("PDF_XXX_SHOW_PRICE_INCL_TAX"), $langs->trans("AvailableWithSomePDFTemplatesOnly"));
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_PROPAL_SHOW_PRICE_INCL_TAX');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("PDF_PROPAL_SHOW_PRICE_INCL_TAX", $arrval, getDolGlobalString('PDF_PROPAL_SHOW_PRICE_INCL_TAX'));
}
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("MAIN_GENERATE_PROPOSALS_WITH_PICTURE"), $langs->trans("RandomlySelectedIfSeveral"));
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_PROPOSALS_WITH_PICTURE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_GENERATE_PROPOSALS_WITH_PICTURE", $arrval, $conf->global->MAIN_GENERATE_PROPOSALS_WITH_PICTURE);
}
print '</td></tr>';

// Concat PDF
print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("MAIN_PDF_ADD_TERMSOFSALE_PROPAL"), $tooltipconcatpdf);
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_ADD_TERMSOFSALE_PROPAL', array(), null, 0, 0, 1);
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_PDF_ADD_TERMSOFSALE_PROPAL", $arrval, getDolGlobalString('MAIN_PDF_ADD_TERMSOFSALE_PROPAL'));
}

if (getDolGlobalString("MAIN_PDF_ADD_TERMSOFSALE_PROPAL")) {
	$modulepart = 'propal';
	print '<div class="inline-block nobordernopadding valignmiddle "><div class="inline-block marginrightonly">';
	print '<input type="file" class="flat minwidth100 maxwidthinputfileonsmartphone" name="MAIN_INFO_PROPAL_TERMSOFSALE" id="MAIN_INFO_PROPAL_TERMSOFSALE" accept="application/pdf">';
	if (getDolGlobalString("MAIN_INFO_PROPAL_TERMSOFSALE")) {
		$termofsale = getDolGlobalString("MAIN_INFO_PROPAL_TERMSOFSALE");
		if (file_exists($conf->propal->dir_output.'/'.$termofsale)) {
			$file = dol_dir_list($conf->propal->dir_output, 'files', 0, $termofsale);
			print '<div class="inline-block valignmiddle marginrightonly"><a href="'.$documenturl.'?modulepart='.$modulepart.'&file='.urlencode($termofsale).'">'.$termofsale.'</a>'.$formfile->showPreview($file[0], $modulepart, $termofsale, 0, '');
			print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removetermsofsale&modulepart='.$modulepart.'&token='.newToken().'">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
		}
	}
	print '</div>';
}
print '</td></tr>';

print '</table>';
print '</div>';

if (getDolGlobalString("MAIN_PDF_ADD_TERMSOFSALE_PROPAL")) {
	print '<br><div class="center">';
	print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'">';
	print '</div>';
}

print '</form>';


print '<br><br>';

// End of page
llxFooter();
$db->close();
