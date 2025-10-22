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
 *	    \file       htdocs/admin/order_pdf.php
 *		\ingroup    order
 *		\brief      Setup page for order module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
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
$langs->loadLangs(array("admin", "other", "errors", "orders"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'order';
$dirforterms = $conf->order->dir_output;
if (!empty($conf->order->multidir_output[$conf->entity])) {
	$dirforterms = $conf->order->multidir_output[$conf->entity].'/';
}

/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

$error = 0;
if ($action == "update") {
	if (GETPOSTISSET('MAIN_PDF_ADD_TERMSOFSALE_ORDER')) {
		dolibarr_set_const($db, "MAIN_PDF_ADD_TERMSOFSALE_ORDER", GETPOST("MAIN_PDF_ADD_TERMSOFSALE_ORDER", 'int'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('SALES_ORDER_SHOW_SHIPPING_ADDRESS')) {
		dolibarr_set_const($db, "SALES_ORDER_SHOW_SHIPPING_ADDRESS", GETPOSTINT("SALES_ORDER_SHOW_SHIPPING_ADDRESS"), 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($db, "SALES_ORDER_SHOW_SHIPPING_ADDRESS", $conf->entity);
	}

	// Terms of sale
	if ($_FILES['termsofsale']["name"]) {
		if (!preg_match('/(\.pdf)$/i', $_FILES['termsofsale']["name"])) {	// Document can be used on a lot of different places. Only pdf can be supported.
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorBadFormat"), null, 'errors');
		} else {
			$original_file = $_FILES['termsofsale']["name"];
			$result = dol_move_uploaded_file($_FILES['termsofsale']["tmp_name"], $dirforterms.$original_file, 1, 0, $_FILES['termsofsale']['error']);
			if ($result) {
				dolibarr_set_const($db, 'MAIN_INFO_ORDER_TERMSOFSALE', $original_file, 'chaine', 0, '', $conf->entity);
			}
		}
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}

// Terms of sale
if ($action == 'removetermsofsale') {
	$filename = getDolGlobalString('MAIN_INFO_ORDER_TERMSOFSALE');
	$file = $dirforterms.'/'.$filename;

	if ($filename != '') {
		dol_delete_file($file);
	}
	dolibarr_del_const($db, 'MAIN_INFO_ORDER_TERMSOFSALE', $conf->entity);
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader('', $langs->trans("OrdersSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-order');

//if ($mesg) print $mesg;

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("OrdersSetup"), $linkback, 'title_setup');

$head = order_admin_prepare_head();

print dol_get_fiche_head($head, 'pdf', $langs->trans("Orders"), -1, 'order');

print '<form enctype="multipart/form-data" method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px"></td></tr>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("MAIN_PDF_ADD_TERMSOFSALE_ORDER"), $langs->trans("PdfAddTermOfSaleHelp"));
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_ADD_TERMSOFSALE_ORDER');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_PDF_ADD_TERMSOFSALE_ORDER", $arrval, $conf->global->MAIN_PDF_ADD_TERMSOFSALE_ORDER);
}
print '</td></tr>';

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("SALES_ORDER_SHOW_SHIPPING_ADDRESS"), $langs->trans("SALES_ORDER_SHOW_SHIPPING_ADDRESSMore"));
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('SALES_ORDER_SHOW_SHIPPING_ADDRESS');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("SALES_ORDER_SHOW_SHIPPING_ADDRESS", $arrval, $conf->global->SALES_ORDER_SHOW_SHIPPING_ADDRESS);
}
print '</td></tr>';

print '</table>';
print '</div>';

print load_fiche_titre($langs->trans("FileToConcatToGeneratedPDF"), '', 'file');
print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px"></td></tr>';

// Terms of sale
$tooltiptermsofsale = $langs->trans('AvailableFormats').' : pdf';
$maxfilesizearray = getMaxFileSizeArray();
$tooltiptermsofsale .= ($maxfilesizearray['maxmin'] > 0) ? '<br>'.$langs->trans('MaxSize').' : '.$maxfilesizearray['maxmin'].' '.$langs->trans('Kb') : '';
$documenturl = DOL_URL_ROOT.'/document.php';
if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
	$documenturl = $conf->global->DOL_URL_ROOT_DOCUMENT_PHP;
}
$modulepart = 'order';

print '<tr class="oddeven"><td><label for="logo">'.$form->textwithpicto($langs->trans("FileToConcatToGeneratedPDF"), $tooltiptermsofsale).'</label></td><td>';
print '<div class="centpercent nobordernopadding valignmiddle "><div class="inline-block marginrightonly">';
print '<input type="file" class="flat minwidth100 maxwidthinputfileonsmartphone" name="termsofsale" id="termsofsale" accept="application/pdf">';

if (getDolGlobalString("MAIN_INFO_ORDER_TERMSOFSALE")) {
	$termofsale = getDolGlobalString("MAIN_INFO_ORDER_TERMSOFSALE");
	if (file_exists($dirforterms.'/'.$termofsale)) {
		$file = dol_dir_list($dirforterms, 'files', 0, $termofsale);
		print '<div class="inline-block valignmiddle marginrightonly"><a href="'.$documenturl.'?modulepart='.$modulepart.'&amp;file='.urlencode($termofsale).'">'.$termofsale.'</a>'.$formfile->showPreview($file[0], $modulepart, $termofsale, 0, '');
		print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removetermsofsale&token='.newToken().'">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
	}
}
print '</div>';
print '</td></tr>';
print '</table>';
print '</div>';


print '<center><input type="submit" class="button button-edit reposition" value="'.$langs->trans("Save").'"></center>';

print '</form>';


print '<br><br>';

// End of page
llxFooter();
$db->close();
