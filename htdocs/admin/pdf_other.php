<?php
/* Copyright (C) 2001-2005 	Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 	Regis Houssin        	<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2107 	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2019	   	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2021		Anthony Berton       	<bertonanthony@gmail.com>
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
 *       \file       htdocs/admin/pdf.php
 *       \brief      Page to setup PDF options
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'languages', 'other', 'companies', 'products', 'members', 'stocks', 'Trips'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'update') {
	if (GETPOSTISSET('PROPOSAL_PDF_HIDE_PAYMENTTERM')) {
		dolibarr_set_const($db, "PROPOSAL_PDF_HIDE_PAYMENTTERM", GETPOST("PROPOSAL_PDF_HIDE_PAYMENTTERM"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('PROPOSAL_PDF_HIDE_PAYMENTMODE')) {
		dolibarr_set_const($db, "PROPOSAL_PDF_HIDE_PAYMENTMODE", GETPOST("PROPOSAL_PDF_HIDE_PAYMENTMODE"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
		dolibarr_set_const($db, "MAIN_GENERATE_PROPOSALS_WITH_PICTURE", GETPOST("MAIN_GENERATE_PROPOSALS_WITH_PICTURE"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH')) {
		dolibarr_set_const($db, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", GETPOST("MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", 'int'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('INVOICE_ADD_ZATCA_QR_CODE')) {
		dolibarr_set_const($db, "INVOICE_ADD_ZATCA_QR_CODE", GETPOST("INVOICE_ADD_ZATCA_QR_CODE", 'int'), 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($db, "INVOICE_ADD_SWISS_QR_CODE", $conf->entity);
	}
	if (GETPOSTISSET('INVOICE_ADD_SWISS_QR_CODE')) {
		dolibarr_set_const($db, "INVOICE_ADD_SWISS_QR_CODE", GETPOST("INVOICE_ADD_SWISS_QR_CODE", 'int'), 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($db, "INVOICE_ADD_ZATCA_QR_CODE", $conf->entity);
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}



/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp);

$form = new Form($db);
$formother = new FormOther($db);
$formadmin = new FormAdmin($db);

print load_fiche_titre($langs->trans("PDF"), '', 'title_setup');

$head = pdf_admin_prepare_head();

print dol_get_fiche_head($head, 'other', '', -1, '');

$tooltiptext = '';
print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("PDFOtherDesc"), $tooltiptext)."</span><br>\n";
print "<br>\n";

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

if (!empty($conf->propal->enabled)) {
	print load_fiche_titre($langs->trans("Proposal"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

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

	print '</table>';
	print '</div>';
}


if (isModEnabled('facture')) {
	print load_fiche_titre($langs->trans("Invoices"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("INVOICE_ADD_ZATCA_QR_CODE"), $langs->trans("INVOICE_ADD_ZATCA_QR_CODEMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('INVOICE_ADD_ZATCA_QR_CODE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("INVOICE_ADD_ZATCA_QR_CODE", $arrval, $conf->global->INVOICE_ADD_ZATCA_QR_CODE);
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("INVOICE_ADD_SWISS_QR_CODE"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('INVOICE_ADD_SWISS_QR_CODE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("INVOICE_ADD_SWISS_QR_CODE", $arrval, $conf->global->INVOICE_ADD_SWISS_QR_CODE);
	}
	print '</td></tr>';

	/*
	  print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING").'</td><td>';
	  if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING');
	  } else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING", $arrval, $conf->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING);
	  }
	  print '</td></tr>';
	*/

	print '</table>';
	print '</div>';
}

print '<br><div class="center">';
print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
