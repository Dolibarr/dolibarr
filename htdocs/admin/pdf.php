<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2107 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019	   Ferran Marcet		<fmarcet@2byte.es>
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
$langs->loadLangs(array('admin', 'languages', 'other', 'companies', 'products', 'members', 'stocks'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');


/*
 * Actions
 */

if ($cancel) {
	$action = '';
}

if ($action == 'update')
{
	dolibarr_set_const($db, "MAIN_PDF_FORMAT", GETPOST("MAIN_PDF_FORMAT"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_PDF_MARGIN_LEFT", GETPOST("MAIN_PDF_MARGIN_LEFT"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_MARGIN_RIGHT", GETPOST("MAIN_PDF_MARGIN_TOP"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_MARGIN_BOTTOM", GETPOST("MAIN_PDF_MARGIN_BOTTOM"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_PROFID1_IN_ADDRESS", GETPOST("MAIN_PROFID1_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID2_IN_ADDRESS", GETPOST("MAIN_PROFID2_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID3_IN_ADDRESS", GETPOST("MAIN_PROFID3_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID4_IN_ADDRESS", GETPOST("MAIN_PROFID4_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID5_IN_ADDRESS", GETPOST("MAIN_PROFID5_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID6_IN_ADDRESS", GETPOST("MAIN_PROFID6_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT", GETPOST("MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_TVAINTRA_NOT_IN_ADDRESS", GETPOST("MAIN_TVAINTRA_NOT_IN_ADDRESS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS", GETPOST("MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DESC", GETPOST("MAIN_GENERATE_DOCUMENTS_HIDE_DESC"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_REF", GETPOST("MAIN_GENERATE_DOCUMENTS_HIDE_REF"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_DOCUMENTS_LOGO_HEIGHT", GETPOST("MAIN_DOCUMENTS_LOGO_HEIGHT", 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INVERT_SENDER_RECIPIENT", GETPOST("MAIN_INVERT_SENDER_RECIPIENT"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_USE_ISO_LOCATION", GETPOST("MAIN_PDF_USE_ISO_LOCATION"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS", GETPOST("MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "MAIN_PDF_MAIN_HIDE_SECOND_TAX", GETPOST("MAIN_PDF_MAIN_HIDE_SECOND_TAX"), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_MAIN_HIDE_THIRD_TAX", GETPOST("MAIN_PDF_MAIN_HIDE_THIRD_TAX"), 'chaine', 0, '', $conf->entity);

	dolibarr_set_const($db, "PDF_USE_ALSO_LANGUAGE_CODE", GETPOST('PDF_USE_ALSO_LANGUAGE_CODE', 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "SHOW_SUBPRODUCT_REF_IN_PDF", GETPOST('SHOW_SUBPRODUCT_REF_IN_PDF', 'alpha'), 'chaine', 0, '', $conf->entity);

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

$arraydetailsforpdffoot = array(
	0 => $langs->transnoentitiesnoconv('NoDetails'),
	1 => $langs->transnoentitiesnoconv('DisplayCompanyInfo'),
	2 => $langs->transnoentitiesnoconv('DisplayCompanyManagers'),
	3 => $langs->transnoentitiesnoconv('DisplayCompanyInfoAndManagers')
);

$s = $langs->trans("LibraryToBuildPDF")."<br>";
$i = 0;
$pdf = pdf_getInstance('A4');
if (class_exists('FPDF') && !class_exists('TCPDF'))
{
	if ($i) $s .= ' + ';
	$s .= 'FPDF';
	$s .= ' ('.@constant('FPDF_PATH').')';
	$i++;
}
if (class_exists('TCPDF'))
{
	if ($i) $s .= ' + ';
	$s .= 'TCPDF';
	$s .= ' ('.@constant('TCPDF_PATH').')';
	$i++;
}
if (class_exists('FPDI'))
{
	if ($i) $s .= ' + ';
	$s .= 'FPDI';
	$s .= ' ('.@constant('FPDI_PATH').')';
	$i++;
}
if (class_exists('TCPDI'))
{
	if ($i) $s .= ' + ';
	$s .= 'TCPDI';
	$s .= ' ('.@constant('TCPDI_PATH').')';
	$i++;
}

print load_fiche_titre($langs->trans("PDF"), '', 'title_setup');

print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("PDFDesc"), $s)."</span><br>\n";
print "<br>\n";

$noCountryCode = (empty($mysoc->country_code) ? true : false);

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

clearstatcache();


// Misc options
print load_fiche_titre($langs->trans("DictionaryPaperFormat"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

$selected = $conf->global->MAIN_PDF_FORMAT;
if (empty($selected)) $selected = dol_getDefaultFormat();

// Show pdf format

print '<tr class="oddeven"><td>'.$langs->trans("DictionaryPaperFormat").'</td><td>';
print $formadmin->select_paper_format($selected, 'MAIN_PDF_FORMAT');
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_LEFT").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_LEFT" value="'.(empty($conf->global->MAIN_PDF_MARGIN_LEFT) ? 10 : $conf->global->MAIN_PDF_MARGIN_LEFT).'">';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_RIGHT").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_RIGHT" value="'.(empty($conf->global->MAIN_PDF_MARGIN_RIGHT) ? 10 : $conf->global->MAIN_PDF_MARGIN_RIGHT).'">';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_TOP").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_TOP" value="'.(empty($conf->global->MAIN_PDF_MARGIN_TOP) ? 10 : $conf->global->MAIN_PDF_MARGIN_TOP).'">';
print '</td></tr>';
print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_BOTTOM").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_BOTTOM" value="'.(empty($conf->global->MAIN_PDF_MARGIN_BOTTOM) ? 10 : $conf->global->MAIN_PDF_MARGIN_BOTTOM).'">';
print '</td></tr>';

print '</table>';
print '</div>';

print '<br>';


// Addresses
print load_fiche_titre($langs->trans("PDFAddressForging"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

// Hide VAT Intra on address

print '<tr class="oddeven"><td>'.$langs->trans("ShowVATIntaInAddress").'</td><td>';
print $form->selectyesno('MAIN_TVAINTRA_NOT_IN_ADDRESS', (!empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS)) ? $conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS : 0, 1);
print '</td></tr>';

// Show prof id in address into pdf
for ($i = 1; $i <= 6; $i++)
{
	if (!$noCountryCode)
	{
		$pid = $langs->transcountry("ProfId".$i, $mysoc->country_code);
		if ($pid == '-') $pid = false;
	} else {
		$pid = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
	}
	if ($pid)
	{
		print '<tr class="oddeven"><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid.'</td><td>';
		$keyforconstant = 'MAIN_PROFID'.$i.'_IN_ADDRESS';
		print $form->selectyesno($keyforconstant, isset($conf->global->$keyforconstant) ? $conf->global->$keyforconstant : 0, 1, $noCountryCode);
		print '</td></tr>';
	}
}

print '</table>';
print '</div>';


print '<br>';


// Localtaxes
$locales = '';
$text = '';
if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2))
{
	if ($mysoc->useLocalTax(1))
	{
		$locales = $langs->transcountry("LT1", $mysoc->country_code);
		$text = '<tr class="oddeven"><td>'.$langs->trans("HideLocalTaxOnPDF", $langs->transcountry("LT1", $mysoc->country_code)).'</td><td>';
		$text .= $form->selectyesno('MAIN_PDF_MAIN_HIDE_SECOND_TAX', (!empty($conf->global->MAIN_PDF_MAIN_HIDE_SECOND_TAX)) ? $conf->global->MAIN_PDF_MAIN_HIDE_SECOND_TAX : 0, 1);
		$text .= '</td></tr>';
	}

	if ($mysoc->useLocalTax(2))
	{
		$locales .= ($locales ? ' & ' : '').$langs->transcountry("LT2", $mysoc->country_code);

		$text .= '<tr class="oddeven"><td>'.$langs->trans("HideLocalTaxOnPDF", $langs->transcountry("LT2", $mysoc->country_code)).'</td><td>';
		$text .= $form->selectyesno('MAIN_PDF_MAIN_HIDE_THIRD_TAX', (!empty($conf->global->MAIN_PDF_MAIN_HIDE_THIRD_TAX)) ? $conf->global->MAIN_PDF_MAIN_HIDE_THIRD_TAX : 0, 1);
		$text .= '</td></tr>';
	}
}

$title = $langs->trans("PDFRulesForSalesTax");
if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2))
{
	$title .= ' - '.$langs->trans("PDFLocaltax", $locales);
}

print load_fiche_titre($title, '', '');

print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

// Hide any information on Sale tax / VAT

print '<tr class="oddeven"><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td>';
print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT', (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT)) ? $conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT : 0, 1);
print '</td></tr>';

// Locataxes
print $text;

print '</table>';
print '<br>';


// Other
print load_fiche_titre($langs->trans("Other"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table summary="more" class="noborder centpercent">';
print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

// Height of logo

print '<tr class="oddeven"><td>'.$langs->trans("MAIN_DOCUMENTS_LOGO_HEIGHT").'</td><td>';
print '<input type="text" class="maxwidth50" name="MAIN_DOCUMENTS_LOGO_HEIGHT" value="'.(!empty($conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT) ? $conf->global->MAIN_DOCUMENTS_LOGO_HEIGHT : 20).'">';
print '</td></tr>';

//Desc

print '<tr class="oddeven"><td>'.$langs->trans("HideDescOnPDF").'</td><td>';
print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DESC', (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC)) ? $conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC : 0, 1);
print '</td></tr>';

//Ref

print '<tr class="oddeven"><td>'.$langs->trans("HideRefOnPDF").'</td><td>';
print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_REF', (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF)) ? $conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF : 0, 1);
print '</td></tr>';

//Details

print '<tr class="oddeven"><td>'.$langs->trans("HideDetailsOnPDF").'</td><td>';
print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS', (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS)) ? $conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS : 0, 1);
print '</td></tr>';

//Invert sender and recipient

print '<tr class="oddeven"><td>'.$langs->trans("SwapSenderAndRecipientOnPDF").'</td><td>';
print $form->selectyesno('MAIN_INVERT_SENDER_RECIPIENT', (!empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) ? $conf->global->MAIN_INVERT_SENDER_RECIPIENT : 0, 1);
print '</td></tr>';

// Place customer adress to the ISO location

print '<tr class="oddeven"><td>'.$langs->trans("PlaceCustomerAddressToIsoLocation").'</td><td>';
print $form->selectyesno('MAIN_PDF_USE_ISO_LOCATION', (!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION)) ? $conf->global->MAIN_PDF_USE_ISO_LOCATION : 0, 1);
print '</td></tr>';


print '<tr class="oddeven"><td>'.$langs->trans("ShowDetailsInPDFPageFoot").'</td><td>';
print $form->selectarray('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', $arraydetailsforpdffoot, $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS);
print '</td></tr>';

print '<tr class="oddeven"><td>'.$langs->trans("PDF_USE_ALSO_LANGUAGE_CODE").'</td><td>';
//if (! empty($conf->global->MAIN_MULTILANGS))
//{
print $formadmin->select_language(GETPOSTISSET('PDF_USE_ALSO_LANGUAGE_CODE') ? GETPOST('PDF_USE_ALSO_LANGUAGE_CODE') : $conf->global->PDF_USE_ALSO_LANGUAGE_CODE, 'PDF_USE_ALSO_LANGUAGE_CODE', 0, null, 1);
//} else {
//	print '<span class="opacitymedium">'.$langs->trans("MultiLangNotEnabled").'</span>';
//}
print '</td></tr>';

// SHOW_SUBPRODUCT_REF_IN_PDF - Option to show the detail of product ref for kits.
print '<tr class="oddeven"><td>'.$langs->trans("SHOW_SUBPRODUCT_REF_IN_PDF", $langs->transnoentitiesnoconv("AssociatedProductsAbility"), $langs->transnoentitiesnoconv("Products")).'</td><td>';
print $form->selectyesno('SHOW_SUBPRODUCT_REF_IN_PDF', (!empty($conf->global->SHOW_SUBPRODUCT_REF_IN_PDF)) ? $conf->global->SHOW_SUBPRODUCT_REF_IN_PDF : 0, 1);
print '</td></tr>';

print '</table>';
print '</div>';

print '<br><div class="center">';
print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';


// End of page
llxFooter();
$db->close();
