<?php
/* Copyright (C) 2001-2005 	Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 	Regis Houssin        	<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2107 	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2019	   	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2021-2022	Anthony Berton       	<bertonanthony@gmail.com>
 * Copyright (C) 2022		Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *       \file       htdocs/admin/pdf.php
 *       \brief      Page to setup PDF options
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'bills', 'companies', 'languages', 'members', 'other', 'products', 'propal', 'receptions', 'stocks', 'trips', 'orders'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');


/*
 * Actions
 */

if ($action == 'update') {
	if (GETPOSTISSET('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING')) {
		dolibarr_set_const($db, "MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING", GETPOST("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('PROPOSAL_PDF_HIDE_PAYMENTTERM')) {
		dolibarr_set_const($db, "PROPOSAL_PDF_HIDE_PAYMENTTERM", GETPOST("PROPOSAL_PDF_HIDE_PAYMENTTERM"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('PROPOSAL_PDF_HIDE_PAYMENTMODE')) {
		dolibarr_set_const($db, "PROPOSAL_PDF_HIDE_PAYMENTMODE", GETPOST("PROPOSAL_PDF_HIDE_PAYMENTMODE"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
		dolibarr_set_const($db, "MAIN_GENERATE_PROPOSALS_WITH_PICTURE", GETPOST("MAIN_GENERATE_PROPOSALS_WITH_PICTURE"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE", GETPOST("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN", GETPOST("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE", GETPOST("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN", GETPOST("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH')) {
		dolibarr_set_const($db, "MAIN_DOCUMENTS_WITH_PICTURE_WIDTH", GETPOSTINT("MAIN_DOCUMENTS_WITH_PICTURE_WIDTH"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('INVOICE_ADD_ZATCA_QR_CODE')) {
		dolibarr_set_const($db, "INVOICE_ADD_ZATCA_QR_CODE", GETPOSTINT("INVOICE_ADD_ZATCA_QR_CODE"), 'chaine', 0, '', $conf->entity);
		if (GETPOSTINT('INVOICE_ADD_ZATCA_QR_CODE') == 1) {
			dolibarr_del_const($db, "INVOICE_ADD_SWISS_QR_CODE", $conf->entity);
		}
	}
	if (GETPOSTISSET('INVOICE_ADD_EPC_QR_CODE')) {
		dolibarr_set_const($db, "INVOICE_ADD_EPC_QR_CODE", GETPOST("INVOICE_ADD_EPC_QR_CODE", 'int'), 'chaine', 0, '', $conf->entity);
		if (GETPOSTINT('INVOICE_ADD_EPC_QR_CODE') == 1) {
			dolibarr_del_const($db, "INVOICE_ADD_EPC_QR_CODE", $conf->entity);
		}
	}
	if (GETPOSTISSET('INVOICE_ADD_SWISS_QR_CODE')) {
		dolibarr_set_const($db, "INVOICE_ADD_SWISS_QR_CODE", GETPOST("INVOICE_ADD_SWISS_QR_CODE", 'alpha'), 'chaine', 0, '', $conf->entity);
		if (GETPOST('INVOICE_ADD_SWISS_QR_CODE', 'alpha') != '0') {
			dolibarr_del_const($db, "INVOICE_ADD_ZATCA_QR_CODE", $conf->entity);
		}
	}
	if (GETPOSTISSET('INVOICE_CATEGORY_OF_OPERATION')) {
		dolibarr_set_const($db, "INVOICE_CATEGORY_OF_OPERATION", GETPOSTINT("INVOICE_CATEGORY_OF_OPERATION"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('INVOICE_SHOW_SHIPPING_ADDRESS')) {
		dolibarr_set_const($db, "INVOICE_SHOW_SHIPPING_ADDRESS", GETPOSTINT("INVOICE_SHOW_SHIPPING_ADDRESS"), 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($db, "INVOICE_SHOW_SHIPPING_ADDRESS", $conf->entity);
	}

	if (GETPOSTISSET('BARCODE_ON_SHIPPING_PDF')) {
		dolibarr_set_const($db, "BARCODE_ON_SHIPPING_PDF", GETPOSTINT("BARCODE_ON_SHIPPING_PDF"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('BARCODE_ON_RECEPTION_PDF')) {
		dolibarr_set_const($db, "BARCODE_ON_RECEPTION_PDF", GETPOSTINT("BARCODE_ON_RECEPTION_PDF"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('BARCODE_ON_STOCKTRANSFER_PDF')) {
		dolibarr_set_const($db, "BARCODE_ON_STOCKTRANSFER_PDF", GETPOSTINT("BARCODE_ON_STOCKTRANSFER_PDF"), 'chaine', 0, '', $conf->entity);
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}



/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-pdf_other');

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

if (isModEnabled('propal')) {
	print load_fiche_titre($langs->trans("Proposal"), '', 'proposal');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	/* This feature seems not yet used into Dolibarr. So option is kept hidden and enabled by default
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING", $arrval, $conf->global->MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING);
	}
	print '</td></tr>';
	*/

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


if (isModEnabled('supplier_proposal')) {
	$langs->load("supplier_proposal");
	print load_fiche_titre($langs->trans("SupplierProposal"), '', 'supplier_proposal');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE", $arrval, $conf->global->MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE);
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN", $arrval, $conf->global->MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN);
	}
	print '</td></tr>';

	print '</table>';
	print '</div>';
}


if (isModEnabled('supplier_order')) {
	$langs->load("supplier_order");
	print load_fiche_titre($langs->trans("SupplierOrder"), '', 'supplier_proposal');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE", $arrval, $conf->global->MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE);
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN", $arrval, $conf->global->MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN);
	}
	print '</td></tr>';

	print '</table>';
	print '</div>';
}

if (isModEnabled('invoice')) {
	print load_fiche_titre($langs->trans("Invoices"), '', 'bill');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("INVOICE_ADD_ZATCA_QR_CODE"), $langs->trans("INVOICE_ADD_ZATCA_QR_CODEMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('INVOICE_ADD_ZATCA_QR_CODE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("INVOICE_ADD_ZATCA_QR_CODE", $arrval, getDolGlobalString('INVOICE_ADD_ZATCA_QR_CODE'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("INVOICE_ADD_EPC_QR_CODE"), $langs->trans("INVOICE_ADD_EPC_QR_CODEMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('INVOICE_ADD_EPC_QR_CODE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("INVOICE_ADD_EPC_QR_CODE", $arrval, getDolGlobalString('INVOICE_ADD_EPC_QR_CODE'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	if (getDolGlobalString('INVOICE_ADD_SWISS_QR_CODE') == 'bottom') {
		print $form->textwithpicto($langs->trans("INVOICE_ADD_SWISS_QR_CODE"), $langs->trans("INVOICE_ADD_SWISS_QR_CODEMore"));
	} else {
		print $langs->trans("INVOICE_ADD_SWISS_QR_CODE");
	}
	print '</td><td>';
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	if (getDolGlobalString('MAIN_FEATURES_LEVEL') >= 1) {
		$arrval['bottom'] = $langs->trans("AtBottomOfPage").' ('.$langs->trans("Experimental").' - Need PHP 8.1+ and some PHP libs)';
	}
	print $form->selectarray("INVOICE_ADD_SWISS_QR_CODE", $arrval, getDolGlobalString('INVOICE_ADD_SWISS_QR_CODE'));
	print '</td></tr>';

	// Mention category of operations
	// French Decret n°2099-1299 2022-10-07
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("InvoiceOptionCategoryOfOperations"), $langs->trans('InvoiceOptionCategoryOfOperationsHelp'), 1);
	print '</td><td>';
	$arrval = array('0'=>$langs->trans("No"),
		'1'=>$langs->trans("InvoiceOptionCategoryOfOperationsYes1"),
		'2'=>$langs->trans("InvoiceOptionCategoryOfOperationsYes2")
	);
	print $form->selectarray("INVOICE_CATEGORY_OF_OPERATION", $arrval, getDolGlobalString('INVOICE_CATEGORY_OF_OPERATION'), 0, 0, 0, '', 0, 0, 0, '', 'minwidth75imp');
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("INVOICE_SHOW_SHIPPING_ADDRESS"), $langs->trans("INVOICE_SHOW_SHIPPING_ADDRESSMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('INVOICE_SHOW_SHIPPING_ADDRESS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("INVOICE_SHOW_SHIPPING_ADDRESS", $arrval, $conf->global->INVOICE_SHOW_SHIPPING_ADDRESS);
	}
	print '</td></tr>';

	/* Keep this option hidden for the moment to avoid options inflation. We'll see later if it is used enough...
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("SUPPLIER_PROPOSAL_ADD_BILLING_CONTACT"), $langs->trans("SUPPLIER_PROPOSAL_ADD_BILLING_CONTACTMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('SUPPLIER_PROPOSAL_ADD_BILLING_CONTACT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("SUPPLIER_PROPOSAL_ADD_BILLING_CONTACT", $arrval, $conf->global->SUPPLIER_PROPOSAL_ADD_BILLING_CONTACT);
	}
	print '</td></tr>';
	*/
	
	print '</table>';
	print '</div>';
}

if (isModEnabled('shipping')) {
	print load_fiche_titre($langs->trans("Shipments"), '', 'shipment');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans("BARCODE_ON_SHIPPING_PDF");
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('BARCODE_ON_SHIPPING_PDF');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("BARCODE_ON_SHIPPING_PDF", $arrval, getDolGlobalString('BARCODE_ON_SHIPPING_PDF'));
	}
	print '</td></tr>';
	print '</table>';
	print '</div>';
}


if (isModEnabled('reception')) {
	print load_fiche_titre($langs->trans("Receptions"), '', 'reception');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans("RECEPTION_PDF_HIDE_ORDERED");
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('RECEPTION_PDF_HIDE_ORDERED');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("RECEPTION_PDF_HIDE_ORDERED", $arrval, getDolGlobalString('RECEPTION_PDF_HIDE_ORDERED'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans("MAIN_PDF_RECEPTION_DISPLAY_AMOUNT_HT");
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PDF_RECEPTION_DISPLAY_AMOUNT_HT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_PDF_RECEPTION_DISPLAY_AMOUNT_HT", $arrval, getDolGlobalString('MAIN_PDF_RECEPTION_DISPLAY_AMOUNT_HT'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans("BARCODE_ON_RECEPTION_PDF");
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('BARCODE_ON_RECEPTION_PDF');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("BARCODE_ON_RECEPTION_PDF", $arrval, getDolGlobalString('BARCODE_ON_RECEPTION_PDF'));
	}
	print '</td></tr>';
	print '</table>';
	print '</div>';
}

if (isModEnabled('stocktransfer')) {
	print load_fiche_titre($langs->trans("StockTransfer"), '', 'stock');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $langs->trans("BARCODE_ON_STOCKTRANSFER_PDF");
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('BARCODE_ON_STOCKTRANSFER_PDF');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("BARCODE_ON_STOCKTRANSFER_PDF", $arrval, getDolGlobalString('BARCODE_ON_STOCKTRANSFER_PDF'));
	}
	print '</td></tr>';

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
