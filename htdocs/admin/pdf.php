<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2107 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019	   Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2021-2022 Anthony Berton		<bertonanthony@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
$langs->loadLangs(array('admin', 'companies', 'languages', 'members', 'other', 'products', 'stocks', 'trips'));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');


/*
 * Actions
 */

if ($cancel) {
	$action = '';
}

if ($action == 'update') {
	if (GETPOSTISSET('MAIN_PDF_FORMAT')) {
		dolibarr_set_const($db, "MAIN_PDF_FORMAT", GETPOST("MAIN_PDF_FORMAT"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_PDF_MARGIN_LEFT')) {
		dolibarr_set_const($db, "MAIN_PDF_MARGIN_LEFT", GETPOST("MAIN_PDF_MARGIN_LEFT"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_MARGIN_RIGHT')) {
		dolibarr_set_const($db, "MAIN_PDF_MARGIN_RIGHT", GETPOST("MAIN_PDF_MARGIN_RIGHT"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_MARGIN_TOP')) {
		dolibarr_set_const($db, "MAIN_PDF_MARGIN_TOP", GETPOST("MAIN_PDF_MARGIN_TOP"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_MARGIN_BOTTOM')) {
		dolibarr_set_const($db, "MAIN_PDF_MARGIN_BOTTOM", GETPOST("MAIN_PDF_MARGIN_BOTTOM"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_PROFID1_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_PROFID1_IN_ADDRESS", GETPOST("MAIN_PROFID1_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PROFID2_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_PROFID2_IN_ADDRESS", GETPOST("MAIN_PROFID2_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PROFID3_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_PROFID3_IN_ADDRESS", GETPOST("MAIN_PROFID3_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PROFID4_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_PROFID4_IN_ADDRESS", GETPOST("MAIN_PROFID4_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PROFID5_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_PROFID5_IN_ADDRESS", GETPOST("MAIN_PROFID5_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PROFID6_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_PROFID6_IN_ADDRESS", GETPOST("MAIN_PROFID6_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_PDF_NO_SENDER_FRAME')) {
		dolibarr_set_const($db, "MAIN_PDF_NO_SENDER_FRAME", GETPOST("MAIN_PDF_NO_SENDER_FRAME"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_NO_RECIPENT_FRAME')) {
		dolibarr_set_const($db, "MAIN_PDF_NO_RECIPENT_FRAME", GETPOST("MAIN_PDF_NO_RECIPENT_FRAME"), 'chaine', 0, '', $conf->entity);
	}

	/*if (GETPOSTISSET('MAIN_PDF_HIDE_SENDER_NAME')) {
		dolibarr_set_const($db, "MAIN_PDF_HIDE_SENDER_NAME", GETPOST("MAIN_PDF_HIDE_SENDER_NAME"), 'chaine', 0, '', $conf->entity);
	}*/

	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT", GETPOST("MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_TVAINTRA_NOT_IN_ADDRESS')) {
		dolibarr_set_const($db, "MAIN_TVAINTRA_NOT_IN_ADDRESS", GETPOST("MAIN_TVAINTRA_NOT_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	}

	if (isModEnabled('project')) {
		if (GETPOST('PDF_SHOW_PROJECT_REF_OR_LABEL') == 'no') {
			dolibarr_del_const($db, "PDF_SHOW_PROJECT", $conf->entity);
			dolibarr_del_const($db, "PDF_SHOW_PROJECT_TITLE", $conf->entity);
		} elseif (GETPOST('PDF_SHOW_PROJECT_REF_OR_LABEL') == 'showprojectref') {
			dolibarr_set_const($db, "PDF_SHOW_PROJECT", GETPOST("PDF_SHOW_PROJECT_REF_OR_LABEL"), 'chaine', 0, '', $conf->entity);
			dolibarr_del_const($db, "PDF_SHOW_PROJECT_TITLE", $conf->entity);
		} elseif (GETPOST('PDF_SHOW_PROJECT_REF_OR_LABEL') == 'showprojectlabel') {
			dolibarr_del_const($db, "PDF_SHOW_PROJECT", $conf->entity);
			dolibarr_set_const($db, "PDF_SHOW_PROJECT_TITLE", GETPOST("PDF_SHOW_PROJECT_REF_OR_LABEL"), 'chaine', 0, '', $conf->entity);
		}
	}

	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS", GETPOST("MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_HIDE_DESC')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DESC", GETPOST("MAIN_GENERATE_DOCUMENTS_HIDE_DESC"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_HIDE_REF')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_REF", GETPOST("MAIN_GENERATE_DOCUMENTS_HIDE_REF"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE", GETPOST("MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_DOCUMENTS_LOGO_HEIGHT')) {
		dolibarr_set_const($db, "MAIN_DOCUMENTS_LOGO_HEIGHT", GETPOSTINT("MAIN_DOCUMENTS_LOGO_HEIGHT"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_INVERT_SENDER_RECIPIENT')) {
		dolibarr_set_const($db, "MAIN_INVERT_SENDER_RECIPIENT", GETPOST("MAIN_INVERT_SENDER_RECIPIENT"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_USE_ISO_LOCATION')) {
		dolibarr_set_const($db, "MAIN_PDF_USE_ISO_LOCATION", GETPOST("MAIN_PDF_USE_ISO_LOCATION"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_NO_CUSTOMER_CODE')) {
		dolibarr_set_const($db, "MAIN_PDF_NO_CUSTOMER_CODE", GETPOST("MAIN_PDF_NO_CUSTOMER_CODE"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
		dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS", GETPOST("MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('MAIN_PDF_MAIN_HIDE_SECOND_TAX')) {
		dolibarr_set_const($db, "MAIN_PDF_MAIN_HIDE_SECOND_TAX", GETPOST("MAIN_PDF_MAIN_HIDE_SECOND_TAX"), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('MAIN_PDF_MAIN_HIDE_THIRD_TAX')) {
		dolibarr_set_const($db, "MAIN_PDF_MAIN_HIDE_THIRD_TAX", GETPOST("MAIN_PDF_MAIN_HIDE_THIRD_TAX"), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('PDF_USE_ALSO_LANGUAGE_CODE')) {
		dolibarr_set_const($db, "PDF_USE_ALSO_LANGUAGE_CODE", GETPOST('PDF_USE_ALSO_LANGUAGE_CODE', 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('SHOW_SUBPRODUCT_REF_IN_PDF')) {
		dolibarr_set_const($db, "SHOW_SUBPRODUCT_REF_IN_PDF", GETPOST('SHOW_SUBPRODUCT_REF_IN_PDF', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('PDF_SHOW_LINK_TO_ONLINE_PAYMENT')) {
		dolibarr_set_const($db, "PDF_SHOW_LINK_TO_ONLINE_PAYMENT", GETPOST('PDF_SHOW_LINK_TO_ONLINE_PAYMENT', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('DOC_SHOW_FIRST_SALES_REP')) {
		dolibarr_set_const($db, "DOC_SHOW_FIRST_SALES_REP", GETPOST('DOC_SHOW_FIRST_SALES_REP', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME')) {
		dolibarr_set_const($db, "PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME", GETPOST('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME', 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('PDF_USE_A')) {
		dolibarr_set_const($db, "PDF_USE_A", GETPOST('PDF_USE_A', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	if (GETPOSTISSET('PDF_BOLD_PRODUCT_LABEL')) {
		dolibarr_set_const($db, "PDF_BOLD_PRODUCT_LABEL", GETPOST('PDF_BOLD_PRODUCT_LABEL', 'alpha'), 'chaine', 0, '', $conf->entity);
	}
	if (GETPOSTISSET('PDF_BOLD_PRODUCT_REF_AND_PERIOD')) {
		dolibarr_set_const($db, "PDF_BOLD_PRODUCT_REF_AND_PERIOD", GETPOST('PDF_BOLD_PRODUCT_REF_AND_PERIOD', 'alpha'), 'chaine', 0, '', $conf->entity);
	}

	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}



/*
 * View
 */

$wikihelp = 'EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('', $langs->trans("Setup"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-pdf');

$form = new Form($db);
$formother = new FormOther($db);
$formadmin = new FormAdmin($db);

$arraydetailsforpdffoot = array(
	0 => $langs->transnoentitiesnoconv('NoDetails'),
	1 => $langs->transnoentitiesnoconv('DisplayCompanyInfo'),
	2 => $langs->transnoentitiesnoconv('DisplayCompanyManagers'),
	3 => $langs->transnoentitiesnoconv('DisplayCompanyInfoAndManagers')
);

$arraylistofpdfformat = array(
	0 => $langs->transnoentitiesnoconv('PDF 1.7'),
	1 => $langs->transnoentitiesnoconv('PDF/A-1b'),
	3 => $langs->transnoentitiesnoconv('PDF/A-3b'),
);

$s = $langs->trans("LibraryToBuildPDF")."<br>";
$i = 0;
$pdf = pdf_getInstance(array(210, 297));
if (class_exists('FPDF') && !class_exists('TCPDF')) {
	if ($i) {
		$s .= ' + ';
	}
	$s .= 'FPDF';
	$s .= ' ('.@constant('FPDF_PATH').')';
	$i++;
}
if (class_exists('TCPDF')) {
	if ($i) {
		$s .= ' + ';
	}
	$s .= 'TCPDF';
	$s .= ' ('.@constant('TCPDF_PATH').')';
	$i++;
}
if (class_exists('FPDI')) {
	if ($i) {
		$s .= ' + ';
	}
	$s .= 'FPDI';
	$s .= ' ('.@constant('FPDI_PATH').')';
	$i++;
}
if (class_exists('TCPDI')) {
	if ($i) {
		$s .= ' + ';
	}
	$s .= 'TCPDI';
	$s .= ' ('.@constant('TCPDI_PATH').')';
	$i++;
}

print load_fiche_titre($langs->trans("PDF"), '', 'title_setup');

$head = pdf_admin_prepare_head();

print dol_get_fiche_head($head, 'general', '', -1, '');

print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("PDFDesc"), $s)."</span><br>\n";
print "<br>\n";

$noCountryCode = empty($mysoc->country_code);

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

clearstatcache();


if (getDolGlobalString('PDF_SECURITY_ENCRYPTION')) {
	print '<div class="warning">';
	print 'The not supported and hidden option PDF_SECURITY_ENCRYPTION has been enabled. This means a lof of feature related to PDF will be broken, like mass PDF generation or online signature of PDF.'."\n";
	print 'You should disable this option.';
	print '</div>';
}



// Misc options
print load_fiche_titre($langs->trans("DictionaryPaperFormat"), '', '');


print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="titlefieldmiddle">'.$langs->trans("Parameters").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

$selected = getDolGlobalString('MAIN_PDF_FORMAT');
if (empty($selected)) {
	$selected = dol_getDefaultFormat();
}

// Show pdf format

print '<tr class="oddeven"><td>'.$langs->trans("DictionaryPaperFormat").'</td><td>';
print $formadmin->select_paper_format($selected, 'MAIN_PDF_FORMAT');
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_LEFT").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_LEFT" value="'.getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10).'">';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_RIGHT").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_RIGHT" value="'.getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10).'">';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_TOP").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_TOP" value="'.getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10).'">';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_BOTTOM").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_BOTTOM" value="'.getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10).'">';
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';


// Addresses
print load_fiche_titre($langs->trans("PDFAddressForging"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td class="">'.$langs->trans("Parameter").'</td><td></td></tr>';

// Show sender name

/* Set option as hidden because no need of this for 99.99% of users. Having it as hidden feature is enough.
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_HIDE_SENDER_NAME").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_HIDE_SENDER_NAME');
} else {
	print $form->selectyesno('MAIN_PDF_HIDE_SENDER_NAME', getDolGlobalInt('MAIN_PDF_HIDE_SENDER_NAME'), 1);
}
print '</td></tr>';
*/

// Hide VAT Intra on address

print '<tr class="oddeven"><td>'.$langs->trans("ShowVATIntaInAddress").' - <span class="opacitymedium">'.$langs->trans("ThirdPartyAddress").'</span></td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_TVAINTRA_NOT_IN_ADDRESS');
} else {
	print $form->selectyesno('MAIN_TVAINTRA_NOT_IN_ADDRESS', getDolGlobalInt('MAIN_TVAINTRA_NOT_IN_ADDRESS'), 1);
}
print '</td></tr>';

// Show prof id in address into pdf
for ($i = 1; $i <= 6; $i++) {
	if (!$noCountryCode) {
		$pid = $langs->transcountry("ProfId".$i, $mysoc->country_code);
		if ($pid == '-') {
			$pid = false;
		}
	} else {
		$pid = img_warning().' <span class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</span>';
	}
	if ($pid) {
		print '<tr class="oddeven"><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid.' - <span class="opacitymedium">'.$langs->trans("ThirdPartyAddress").'</span></td><td>';
		$keyforconstant = 'MAIN_PROFID'.$i.'_IN_ADDRESS';
		if ($conf->use_javascript_ajax) {
			print ajax_constantonoff($keyforconstant);
		} else {
			print $form->selectyesno($keyforconstant, getDolGlobalString($keyforconstant, 0), 1, $noCountryCode);
		}
		print '</td></tr>';
	}
}

print '<tr class="oddeven"><td>'.$langs->trans("ShowLegalFormInAddress").' - <span class="opacitymedium">'.$langs->trans("ThirdPartyAddress").'</span></td><td>';
$keyforconstant = 'MAIN_LEGALFORM_IN_ADDRESS';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff($keyforconstant);
} else {
	print $form->selectyesno($keyforconstant, getDolGlobalString($keyforconstant, 0), 1, $noCountryCode);
}
print '</td></tr>';


// Borders on address frame

print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_NO_SENDER_FRAME").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_NO_SENDER_FRAME');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_PDF_NO_SENDER_FRAME", $arrval, getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME'));
}
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_NO_RECIPENT_FRAME").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_NO_RECIPENT_FRAME');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_PDF_NO_RECIPENT_FRAME", $arrval, getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME'));
}
print '</td></tr>';

//Invert sender and recipient

print '<tr class="oddeven"><td>'.$langs->trans("SwapSenderAndRecipientOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_INVERT_SENDER_RECIPIENT');
} else {
	print $form->selectyesno('MAIN_INVERT_SENDER_RECIPIENT', getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT'), 1);
}
print '</td></tr>';

// Place customer address to the ISO location

print '<tr class="oddeven"><td>'.$langs->trans("PlaceCustomerAddressToIsoLocation").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_USE_ISO_LOCATION');
} else {
	print $form->selectyesno('MAIN_PDF_USE_ISO_LOCATION', getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION'), 1);
}
print '</td></tr>';

// Show alias in thirdparty name
print '<tr class="oddeven"><td>'.$langs->trans("PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME").'</td><td>';
if ($conf->use_javascript_ajax) {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("THIRDPARTY_ALIAS"), '2' => $langs->trans("ALIAS_THIRDPARTY"));
	print $form->selectarray("PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME", $arrval, getDolGlobalInt('PDF_INCLUDE_ALIAS_IN_THIRDPARTY_NAME'));
}

print '</table>';
print '</div>';


print '<br>';


// Localtaxes
$locales = '';
$text = '';
if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2)) {
	if ($mysoc->useLocalTax(1)) {
		$locales = $langs->transcountry("LT1", $mysoc->country_code);
		$text = '<tr class="oddeven"><td class="titlefieldmiddle">'.$langs->trans("HideLocalTaxOnPDF", $langs->transcountry("LT1", $mysoc->country_code)).'</td><td>';
		if ($conf->use_javascript_ajax) {
			$text .= ajax_constantonoff('MAIN_PDF_MAIN_HIDE_SECOND_TAX');
		} else {
			$text .= $form->selectyesno('MAIN_PDF_MAIN_HIDE_SECOND_TAX', getDolGlobalInt('MAIN_PDF_MAIN_HIDE_SECOND_TAX'), 1);
		}
		$text .= '</td></tr>';
	}

	if ($mysoc->useLocalTax(2)) {
		$locales .= ($locales ? ' & ' : '').$langs->transcountry("LT2", $mysoc->country_code);

		$text .= '<tr class="oddeven"><td class="titlefieldmiddle">'.$langs->trans("HideLocalTaxOnPDF", $langs->transcountry("LT2", $mysoc->country_code)).'</td><td>';
		if ($conf->use_javascript_ajax) {
			$text .= ajax_constantonoff('MAIN_PDF_MAIN_HIDE_THIRD_TAX');
		} else {
			$text .= $form->selectyesno('MAIN_PDF_MAIN_HIDE_THIRD_TAX', getDolGlobalInt('MAIN_PDF_MAIN_HIDE_THIRD_TAX'), 1);
		}
		$text .= '</td></tr>';
	}
}

$title = $langs->trans("PDFRulesForSalesTax");
if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2)) {
	$title .= ' - '.$langs->trans("PDFLocaltax", $locales);
}


print load_fiche_titre($title, '', '');

print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td></td></tr>';

// Hide any information on Sale tax / VAT

print '<tr class="oddeven"><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT');
} else {
	print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT', getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT'), 1);
}
print '</td></tr>';

// Locataxes
print $text;

print '</table>';
print '<br>';


// Other

print load_fiche_titre($langs->trans("Other"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

// Use 2 languages into PDF

print '<tr class="oddeven"><td>';
print $form->textwithpicto($langs->trans("PDFIn2Languages"), $langs->trans("PDF_USE_ALSO_LANGUAGE_CODE"));
print '</td><td>';
$selected = GETPOSTISSET('PDF_USE_ALSO_LANGUAGE_CODE') ? GETPOST('PDF_USE_ALSO_LANGUAGE_CODE') : getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE');
print $formadmin->select_language($selected, 'PDF_USE_ALSO_LANGUAGE_CODE', 0, array(), 1);
print '</td></tr>';

// Height of logo
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DOCUMENTS_LOGO_HEIGHT").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_DOCUMENTS_LOGO_HEIGHT" value="'.getDolGlobalInt('MAIN_DOCUMENTS_LOGO_HEIGHT', 20).'">';
print '</td></tr>';

// Show project
if (isModEnabled('project')) {
	print '<tr class="oddeven"><td>'.$langs->trans("PDF_SHOW_PROJECT").'</td><td>';
	$tmparray = array('no' => 'No', 'showprojectref' => 'RefProject', 'showprojectlabel' => 'ShowProjectLabel');
	$showprojectref = !getDolGlobalString('PDF_SHOW_PROJECT') ? (!getDolGlobalString('PDF_SHOW_PROJECT_TITLE') ? 'no' : 'showprojectlabel') : 'showprojectref';
	print $form->selectarray('PDF_SHOW_PROJECT_REF_OR_LABEL', $tmparray, $showprojectref, 0, 0, 0, '', 1);
	print '</td></tr>';
}

//

print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_HIDE_CUSTOMER_CODE");
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_PDF_HIDE_CUSTOMER_CODE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MAIN_PDF_HIDE_CUSTOMER_CODE", $arrval, getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE'));
}
print '</td></tr>';

// Ref

print '<tr class="oddeven"><td>'.$langs->trans("HideRefOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_REF');
} else {
	print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_REF', getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_HIDE_REF'), 1);
}
print '</td></tr>';

// Barcode

if (isModEnabled('barcode')) {
	print '<tr class="oddeven"><td>'.$langs->trans("ShowProductBarcodeOnPDF").'</td><td>';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE');
	} else {
		print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE', getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_PRODUCT_BARCODE'), 1);
	}
	print '</td></tr>';
}

// Desc

print '<tr class="oddeven"><td>'.$langs->trans("HideDescOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_DESC');
} else {
	print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_HIDE_DESC'), 1);
}
print '</td></tr>';

// Details

print '<tr class="oddeven"><td>'.$langs->trans("HideDetailsOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS');
} else {
	print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS', getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS'), 1);
}
print '</td></tr>';

// Switch in Bold

print '<tr class="oddeven"><td>'.$langs->trans("BoldLabelOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_BOLD_PRODUCT_LABEL');
} else {
	print $form->selectyesno('PDF_BOLD_PRODUCT_LABEL', getDolGlobalInt('PDF_BOLD_PRODUCT_LABEL'), 1);
}
print '</td></tr>';

// Switch in Bold

print '<tr class="oddeven"><td>'.$langs->trans("BoldRefAndPeriodOnPDF").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_BOLD_PRODUCT_REF_AND_PERIOD');
} else {
	print $form->selectyesno('PDF_BOLD_PRODUCT_REF_AND_PERIOD', getDolGlobalInt('PDF_BOLD_PRODUCT_REF_AND_PERIOD'), 1);
}
print '</td></tr>';

// SHOW_SUBPRODUCT_REF_IN_PDF - Option to show the detail of product ref for kits.

print '<tr class="oddeven"><td>'.$langs->trans("SHOW_SUBPRODUCT_REF_IN_PDF", $langs->transnoentitiesnoconv("AssociatedProductsAbility"), $langs->transnoentitiesnoconv("Products")).'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('SHOW_SUBPRODUCT_REF_IN_PDF');
} else {
	print $form->selectyesno('SHOW_SUBPRODUCT_REF_IN_PDF', getDolGlobalInt('SHOW_SUBPRODUCT_REF_IN_PDF'), 1);
}
print '</td></tr>';

// Show more details in footer

print '<tr class="oddeven"><td>'.$langs->trans("ShowDetailsInPDFPageFoot").'</td><td>';
print $form->selectarray('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', $arraydetailsforpdffoot, getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', '0'));
print '</td></tr>';

// Show the first sales representative

print '<tr class="oddeven"><td>'.$langs->trans("DOC_SHOW_FIRST_SALES_REP");
print ' <span class="opacitymedium">('.$langs->trans("SalesRepresentativeInfo").')</span>';
print '</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('DOC_SHOW_FIRST_SALES_REP');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("DOC_SHOW_FIRST_SALES_REP", $arrval, getDolGlobalString('DOC_SHOW_FIRST_SALES_REP'));
}

// Show online payment link on invoices

print '<tr class="oddeven"><td>'.$langs->trans("PDF_SHOW_LINK_TO_ONLINE_PAYMENT").'</td><td>';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('PDF_SHOW_LINK_TO_ONLINE_PAYMENT');
} else {
	print $form->selectyesno('PDF_SHOW_LINK_TO_ONLINE_PAYMENT', getDolGlobalInt('PDF_SHOW_LINK_TO_ONLINE_PAYMENT'), 1);
}
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("PDF_USE_A").'</td><td>';
//$pdfa = false; // PDF default version
$pdfa = getDolGlobalString('PDF_USE_A', '3'); 	// PDF/A-1 ou PDF/A-3
print $form->selectarray('PDF_USE_A', $arraylistofpdfformat, $pdfa);
print '</td></tr>';

print '</table>';
print '</div>';

print $form->buttonsSaveCancel("Save", '');

print '</form>';


// End of page
llxFooter();
$db->close();
