<?php
/* Copyright (C) 2001-2005 	Rodolphe Quiedeville 	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 	Laurent Destailleur  	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 	Regis Houssin        	<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2107 	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2019	   	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2021-2024	Anthony Berton       	<anthony.berton@bb2a.fr>
 * Copyright (C) 2022		Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024       Nick Fragoulis
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 * @var Societe $mysoc
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'bills', 'companies', 'languages', 'members', 'other', 'products', 'propal', 'receptions', 'stocks', 'trips', 'orders'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');

$diroffile = '';
$varname = '';
if ($modulepart == 'propal') {
	$diroffile = $conf->propal->dir_output;
	$varname = 'MAIN_INFO_PROPAL_TERMSOFSALE';
}
if ($modulepart == 'order') {
	$diroffile = $conf->order->dir_output;
	$varname = 'MAIN_INFO_ORDER_TERMSOFSALE';
}
if ($modulepart == 'invoice') {
	$diroffile = $conf->invoice->dir_output;
	$varname = 'MAIN_INFO_INVOICE_TERMSOFSALE';
}


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
	if (GETPOSTISSET('SALES_ORDER_SHOW_SHIPPING_ADDRESS')) {
		dolibarr_set_const($db, "SALES_ORDER_SHOW_SHIPPING_ADDRESS", GETPOSTINT("SALES_ORDER_SHOW_SHIPPING_ADDRESS"), 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($db, "SALES_ORDER_SHOW_SHIPPING_ADDRESS", $conf->entity);
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
	if (GETPOSTISSET('MAIN_PDF_ADD_TERMSOFSALE_PROPAL')) {
		dolibarr_set_const($db, "MAIN_PDF_ADD_TERMSOFSALE_PROPAL", GETPOST("MAIN_PDF_ADD_TERMSOFSALE_PROPAL", 'int'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_ADD_TERMSOFSALE_ORDER')) {
		dolibarr_set_const($db, "MAIN_PDF_ADD_TERMSOFSALE_ORDER", GETPOST("MAIN_PDF_ADD_TERMSOFSALE_ORDER", 'int'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_ADD_TERMSOFSALE_INVOICE')) {
		dolibarr_set_const($db, "MAIN_PDF_ADD_TERMSOFSALE_INVOICE", GETPOST("MAIN_PDF_ADD_TERMSOFSALE_INVOICE", 'int'), 'chaine', 0, '', $conf->entity);
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
	if (GETPOSTISSET('PDF_INVOICE_SHOW_VAT_ANALYSIS')) {
		dolibarr_set_const($db, "PDF_INVOICE_SHOW_VAT_ANALYSIS", GETPOSTINT("PDF_INVOICE_SHOW_VAT_ANALYSIS"), 'chaine', 0, '', $conf->entity);
		dolibarr_del_const($db, "PDF_INVOICE_SHOW_VAT_ANALYSIS", $conf->entity);
	}
	if (GETPOSTISSET('INVOICE_HIDE_LINKED_OBJECT')) {
		dolibarr_set_const($db, "INVOICE_HIDE_LINKED_OBJECT", GETPOSTINT("INVOICE_HIDE_LINKED_OBJECT"), 'chaine', 0, '', $conf->entity);
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

	// add file to concat
	foreach (array('MAIN_INFO_PROPAL_TERMSOFSALE', 'MAIN_INFO_ORDER_TERMSOFSALE', 'MAIN_INFO_INVOICE_TERMSOFSALE') as $varname) {
		if ($_FILES[$varname]["name"]) {
			if (!preg_match('/(\.pdf)$/i', $_FILES[$varname]["name"])) {	// Document can be used on a lot of different places. Only pdf can be supported.
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorBadFormat"), null, 'errors');
			} else {
				if ($varname == 'MAIN_INFO_PROPAL_TERMSOFSALE') {
					$diroffile = $conf->propal->dir_output;
				} elseif ($varname == 'MAIN_INFO_ORDER_TERMSOFSALE') {
					$diroffile = $conf->order->dir_output;
				} elseif ($varname == 'MAIN_INFO_INVOICE_TERMSOFSALE') {
					$diroffile = $conf->invoice->dir_output;
				}
				if ($diroffile) {
					$dirforterms = $diroffile.'/';
					$original_file = $_FILES[$varname]["name"];
					$result = dol_move_uploaded_file($_FILES[$varname]["tmp_name"], $dirforterms.$original_file, 1, 0, $_FILES[$varname]['error']);
					if ((int) $result > 0) {
						dolibarr_set_const($db, $varname, $original_file, 'chaine', 0, '', $conf->entity);
					}
				}
			}
		}
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


// Remove file to concat
if ($action == 'removetermsofsale') {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$filename = getDolGlobalString($varname);
	if ($filename != '') {
		$file = dol_sanitizePathName($diroffile).'/'.dol_sanitizeFileName($filename);
		dol_delete_file($file);
	}
	dolibarr_del_const($db, $varname, $conf->entity);
}


/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-pdf_other');

$form = new Form($db);
$formother = new FormOther($db);
$formadmin = new FormAdmin($db);
$formfile = new FormFile($db);

print load_fiche_titre($langs->trans("PDF"), '', 'title_setup');

$head = pdf_admin_prepare_head();

print dol_get_fiche_head($head, 'other', '', -1, '');

$tooltiptext = '';
print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("PDFOtherDesc"), $tooltiptext)."</span><br>\n";
print "<br>\n";


$maxfilesizearray = getMaxFileSizeArray();
$tooltipconcatpdf = ($maxfilesizearray['maxmin'] > 0) ? $langs->trans('MaxSize').' : '.$maxfilesizearray['maxmin'].' '.$langs->trans('Kb') : '';
$documenturl = DOL_URL_ROOT.'/document.php';
if (isset($conf->global->DOL_URL_ROOT_DOCUMENT_PHP)) {
	$documenturl = getDolGlobalString('DOL_URL_ROOT_DOCUMENT_PHP');
}


print '<form enctype="multipart/form-data" method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

if (isModEnabled('propal')) {
	print load_fiche_titre($langs->trans("Proposal"), '', 'proposal');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px"></td></tr>';

	/* This feature seems not yet used into Dolibarr. So option is kept hidden and enabled by default
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING", $arrval, getDolGlobalString('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING'));
	}
	print '</td></tr>';
	*/

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
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_WITH_PICTURE"), $langs->trans("RandomlySelectedIfSeveral"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_PROPOSALS_WITH_PICTURE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_PROPOSALS_WITH_PICTURE", $arrval, getDolGlobalString('MAIN_GENERATE_PROPOSALS_WITH_PICTURE'));
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

	print '<br>';
}

if (isModEnabled('order')) {
	$langs->load("orders");
	print load_fiche_titre($langs->trans('CustomersOrders'), '', 'order');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';

	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px"></td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("PDF_XXX_SHOW_PRICE_INCL_TAX"), $langs->trans("AvailableWithSomePDFTemplatesOnly"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('PDF_ORDER_SHOW_PRICE_INCL_TAX');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("PDF_ORDER_SHOW_PRICE_INCL_TAX", $arrval, getDolGlobalString('PDF_ORDER_SHOW_PRICE_INCL_TAX'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_WITH_PICTURE"), $langs->trans("RandomlySelectedIfSeveral"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_ORDERS_WITH_PICTURE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_ORDERS_WITH_PICTURE", $arrval, getDolGlobalString('MAIN_GENERATE_ORDERS_WITH_PICTURE'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("SALES_ORDER_SHOW_SHIPPING_ADDRESS"), $langs->trans("SALES_ORDER_SHOW_SHIPPING_ADDRESSMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('SALES_ORDER_SHOW_SHIPPING_ADDRESS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("SALES_ORDER_SHOW_SHIPPING_ADDRESS", $arrval, getDolGlobalString('SALES_ORDER_SHOW_SHIPPING_ADDRESS'));
	}
	print '</td></tr>';

	// Concat PDF
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_PDF_ADD_TERMSOFSALE_ORDER"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PDF_ADD_TERMSOFSALE_ORDER', array(), null, 0, 0, 1);
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_PDF_ADD_TERMSOFSALE_ORDER", $arrval, getDolGlobalString('MAIN_PDF_ADD_TERMSOFSALE_ORDER'));
	}

	if (getDolGlobalString("MAIN_PDF_ADD_TERMSOFSALE_ORDER")) {
		$modulepart = 'order';
		print '<div class="inline-block nobordernopadding valignmiddle "><div class="inline-block marginrightonly">';
		print '<input type="file" class="flat minwidth100 maxwidthinputfileonsmartphone" name="MAIN_INFO_ORDER_TERMSOFSALE" id="MAIN_INFO_ORDER_TERMSOFSALE" accept="application/pdf">';
		if (getDolGlobalString("MAIN_INFO_ORDER_TERMSOFSALE")) {
			$termofsale = getDolGlobalString("MAIN_INFO_ORDER_TERMSOFSALE");
			if (file_exists($conf->order->dir_output.'/'.$termofsale)) {
				$file = dol_dir_list($conf->order->dir_output, 'files', 0, $termofsale);
				print '<div class="inline-block valignmiddle marginrightonly"><a href="'.$documenturl.'?modulepart='.$modulepart.'&file='.urlencode($termofsale).'">'.$termofsale.'</a>'.$formfile->showPreview($file[0], $modulepart, $termofsale, 0, '');
				print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removetermsofsale&modulepart='.$modulepart.'&token='.newToken().'">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
			}
		}
		print '</div>';
	}

	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';
}


if (isModEnabled('invoice')) {
	print load_fiche_titre($langs->trans("Invoices"), '', 'bill');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px"></td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("PDF_XXX_SHOW_PRICE_INCL_TAX"), $langs->trans("AvailableWithSomePDFTemplatesOnly"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('PDF_INVOICE_SHOW_PRICE_INCL_TAX');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("PDF_INVOICE_SHOW_PRICE_INCL_TAX", $arrval, getDolGlobalString('PDF_INVOICE_SHOW_PRICE_INCL_TAX'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_WITH_PICTURE"), $langs->trans("RandomlySelectedIfSeveral"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_INVOICES_WITH_PICTURE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_INVOICES_WITH_PICTURE", $arrval, getDolGlobalString('MAIN_GENERATE_INVOICES_WITH_PICTURE'));
	}
	print '</td></tr>';

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
		print $form->selectarray("INVOICE_SHOW_SHIPPING_ADDRESS", $arrval, getDolGlobalString('INVOICE_SHOW_SHIPPING_ADDRESS'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("PDF_INVOICE_SHOW_VAT_ANALYSIS"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('PDF_INVOICE_SHOW_VAT_ANALYSIS');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("PDF_INVOICE_SHOW_VAT_ANALYSIS", $arrval, $conf->global->PDF_INVOICE_SHOW_VAT_ANALYSIS);
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
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("INVOICE_HIDE_LINKED_OBJECT"), $langs->trans("INVOICE_HIDE_LINKED_OBJECTMore"));
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('INVOICE_HIDE_LINKED_OBJECT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("INVOICE_HIDE_LINKED_OBJECT", $arrval, getDolGlobalString('INVOICE_HIDE_LINKED_OBJECT'));
	}
	print '</td></tr>';

	// Concat PDF
	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_PDF_ADD_TERMSOFSALE_INVOICE"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_PDF_ADD_TERMSOFSALE_INVOICE', array(), null, 0, 0, 1);
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_PDF_ADD_TERMSOFSALE_INVOICE", $arrval, getDolGlobalString('MAIN_PDF_ADD_TERMSOFSALE_INVOICE'));
	}

	if (getDolGlobalString("MAIN_PDF_ADD_TERMSOFSALE_INVOICE")) {
		$modulepart = 'invoice';
		print '<div class="inline-block nobordernopadding valignmiddle "><div class="inline-block marginrightonly">';
		print '<input type="file" class="flat minwidth100 maxwidthinputfileonsmartphone" name="MAIN_INFO_INVOICE_TERMSOFSALE" id="MAIN_INFO_INVOICE_TERMSOFSALE" accept="application/pdf">';
		if (getDolGlobalString("MAIN_INFO_INVOICE_TERMSOFSALE")) {
			$termofsale = getDolGlobalString("MAIN_INFO_INVOICE_TERMSOFSALE");
			if (file_exists($conf->invoice->dir_output.'/'.$termofsale)) {
				$file = dol_dir_list($conf->invoice->dir_output, 'files', 0, $termofsale);
				print '<div class="inline-block valignmiddle marginrightonly"><a href="'.$documenturl.'?modulepart='.$modulepart.'&file='.urlencode($termofsale).'">'.$termofsale.'</a>'.$formfile->showPreview($file[0], $modulepart, $termofsale, 0, '');
				print '<div class="inline-block valignmiddle marginrightonly"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=removetermsofsale&modulepart='.$modulepart.'&token='.newToken().'">'.img_delete($langs->trans("Delete"), '', 'marginleftonly').'</a></div>';
			}
		}
		print '</div>';
	}

	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';
}


if (isModEnabled('supplier_proposal')) {
	$langs->load("supplier_proposal");
	print load_fiche_titre($langs->trans("SupplierProposal"), '', 'supplier_proposal');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px"></td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE", $arrval, getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_UNIT_PRICE'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN", $arrval, getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SUPPLIER_PROPOSAL_WITHOUT_TOTAL_COLUMN'));
	}
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';
}


if (isModEnabled('supplier_order')) {
	$langs->load("supplier_order");
	print load_fiche_titre($langs->trans("SupplierOrder"), '', 'supplier_proposal');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameter").'</td><td width="200px"></td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE", $arrval, getDolGlobalString('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_UNIT_PRICE'));
	}
	print '</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN"), '');
	print '</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN", $arrval, getDolGlobalString('MAIN_GENERATE_DOCUMENTS_PURCHASE_ORDER_WITHOUT_TOTAL_COLUMN'));
	}
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';
}

if (isModEnabled('shipping')) {
	print load_fiche_titre($langs->trans("Shipments"), '', 'shipment');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px"></td></tr>';

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

	print '<br>';
}

if (isModEnabled('reception')) {
	print load_fiche_titre($langs->trans("Receptions"), '', 'reception');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px"></td></tr>';

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

	print '<br>';
}

if (isModEnabled('stocktransfer')) {
	print load_fiche_titre($langs->trans("StockTransfer"), '', 'stock');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder centpercent">';
	print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px"></td></tr>';

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

	print '<br>';
}

print '<br><div class="center">';
print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
