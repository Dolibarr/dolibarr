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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
$langs->loadLangs(array('admin', 'languages', 'other', 'companies', 'products', 'members'));

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');


/*
 * Actions
 */

if ($cancel) {
    $action='';
}

if ($action == 'update')
{
	dolibarr_set_const($db, "MAIN_PDF_FORMAT", $_POST["MAIN_PDF_FORMAT"],'chaine',0,'', $conf->entity);

	dolibarr_set_const($db, "MAIN_PDF_MARGIN_LEFT", $_POST["MAIN_PDF_MARGIN_LEFT"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_MARGIN_RIGHT", $_POST["MAIN_PDF_MARGIN_RIGHT"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_MARGIN_TOP", $_POST["MAIN_PDF_MARGIN_TOP"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_MARGIN_BOTTOM", $_POST["MAIN_PDF_MARGIN_BOTTOM"],'chaine',0,'', $conf->entity);

    dolibarr_set_const($db, "MAIN_PROFID1_IN_ADDRESS", $_POST["MAIN_PROFID1_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID2_IN_ADDRESS", $_POST["MAIN_PROFID2_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID3_IN_ADDRESS", $_POST["MAIN_PROFID3_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID4_IN_ADDRESS", $_POST["MAIN_PROFID4_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID5_IN_ADDRESS", $_POST["MAIN_PROFID5_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID6_IN_ADDRESS", $_POST["MAIN_PROFID6_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT", $_POST["MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT"],'chaine',0,'', $conf->entity);

	dolibarr_set_const($db, "MAIN_TVAINTRA_NOT_IN_ADDRESS", $_POST["MAIN_TVAINTRA_NOT_IN_ADDRESS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS", $_POST["MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DESC", $_POST["MAIN_GENERATE_DOCUMENTS_HIDE_DESC"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_REF", $_POST["MAIN_GENERATE_DOCUMENTS_HIDE_REF"],'chaine',0,'', $conf->entity);

	dolibarr_set_const($db, "MAIN_INVERT_SENDER_RECIPIENT", $_POST["MAIN_INVERT_SENDER_RECIPIENT"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_USE_ISO_LOCATION", $_POST["MAIN_PDF_USE_ISO_LOCATION"],'chaine',0,'', $conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS", $_POST["MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS"],'chaine',0,'', $conf->entity);


    dolibarr_set_const($db, "MAIN_PDF_MAIN_HIDE_SECOND_TAX", $_POST["MAIN_PDF_MAIN_HIDE_SECOND_TAX"],'chaine',0,'', $conf->entity);
    dolibarr_set_const($db, "MAIN_PDF_MAIN_HIDE_THIRD_TAX", $_POST["MAIN_PDF_MAIN_HIDE_THIRD_TAX"],'chaine',0,'', $conf->entity);

	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}

if ($action == 'activate_pdfsecurity')
{
	dolibarr_set_const($db, "PDF_SECURITY_ENCRYPTION", "1",'chaine',0,'',$conf->entity);
	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}
else if ($action == 'disable_pdfsecurity')
{
	dolibarr_del_const($db, "PDF_SECURITY_ENCRYPTION",$conf->entity);
	header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}



/*
 * View
 */

$wikihelp='EN:First_setup|FR:Premiers_param&eacute;trages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$wikihelp);

$form=new Form($db);
$formother=new FormOther($db);
$formadmin=new FormAdmin($db);

$arraydetailsforpdffoot = array(
	0 => $langs->transnoentitiesnoconv('NoDetails'),
	1 => $langs->transnoentitiesnoconv('DisplayCompanyInfo'),
	2 => $langs->transnoentitiesnoconv('DisplayCompanyManagers'),
	3 => $langs->transnoentitiesnoconv('DisplayCompanyInfoAndManagers')
);

print load_fiche_titre($langs->trans("PDF"),'','title_setup');

print $langs->trans("PDFDesc")."<br>\n";
print "<br>\n";

$noCountryCode = (empty($mysoc->country_code) ? true : false);

if ($action == 'edit')	// Edit
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();


    // Misc options
    print load_fiche_titre($langs->trans("DictionaryPaperFormat"),'','');

	print '<div class="div-table-responsive-no-min">';
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    $selected=$conf->global->MAIN_PDF_FORMAT;
    if (empty($selected)) $selected=dol_getDefaultFormat();

    // Show pdf format

    print '<tr class="oddeven"><td>'.$langs->trans("DictionaryPaperFormat").'</td><td>';
    print $formadmin->select_paper_format($selected,'MAIN_PDF_FORMAT');
    print '</td></tr>';

    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_LEFT").'</td><td>';
    print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_LEFT" value="'.(empty($conf->global->MAIN_PDF_MARGIN_LEFT)?10:$conf->global->MAIN_PDF_MARGIN_LEFT).'">';
    print '</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_RIGHT").'</td><td>';
    print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_RIGHT" value="'.(empty($conf->global->MAIN_PDF_MARGIN_RIGHT)?10:$conf->global->MAIN_PDF_MARGIN_RIGHT).'">';
    print '</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_TOP").'</td><td>';
    print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_TOP" value="'.(empty($conf->global->MAIN_PDF_MARGIN_TOP)?10:$conf->global->MAIN_PDF_MARGIN_TOP).'">';
    print '</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_BOTTOM").'</td><td>';
    print '<input type="text" class="maxwidth50" name="MAIN_PDF_MARGIN_BOTTOM" value="'.(empty($conf->global->MAIN_PDF_MARGIN_BOTTOM)?10:$conf->global->MAIN_PDF_MARGIN_BOTTOM).'">';
    print '</td></tr>';

    print '</table>';
	print '</div>';

	print '<br>';


    // Addresses
    print load_fiche_titre($langs->trans("PDFAddressForging"),'','');

	print '<div class="div-table-responsive-no-min">';
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Hide VAT Intra on address

    print '<tr class="oddeven"><td>'.$langs->trans("ShowVATIntaInAddress").'</td><td>';
    print $form->selectyesno('MAIN_TVAINTRA_NOT_IN_ADDRESS',(! empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS))?$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS:0,1);
    print '</td></tr>';

    // Show prof id in address into pdf
    for($i=1; $i<=6; $i++)
    {
        if (! $noCountryCode)
        {
            $pid=$langs->transcountry("ProfId".$i, $mysoc->country_code);
            if ($pid == '-') $pid=false;
        }
        else
        {
            $pid = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
        }
        if ($pid)
        {
            print '<tr class="oddeven"><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid.'</td><td>';
            $keyforconstant = 'MAIN_PROFID'.$i.'_IN_ADDRESS';
            print $form->selectyesno($keyforconstant, isset($conf->global->$keyforconstant)?$conf->global->$keyforconstant:0, 1, $noCountryCode);
            print '</td></tr>';
        }
    }

	print '</table>';
	print '</div>';


    print '<br>';


    // Localtaxes
    $locales ='';
    $text='';
    if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2))
    {
        if ($mysoc->useLocalTax(1))
        {
            $locales = $langs->transcountry("LT1",$mysoc->country_code);
            $text ='<tr class="oddeven"><td>' . $langs->trans("HideLocalTaxOnPDF",$langs->transcountry("LT1",$mysoc->country_code)) . '</td><td>';
            $text.= $form->selectyesno('MAIN_PDF_MAIN_HIDE_SECOND_TAX', (!empty($conf->global->MAIN_PDF_MAIN_HIDE_SECOND_TAX)) ? $conf->global->MAIN_PDF_MAIN_HIDE_SECOND_TAX : 0, 1);
            $text .= '</td></tr>';
        }

        if ($mysoc->useLocalTax(2))
        {
            $locales.=($locales?' & ':'').$langs->transcountry("LT2",$mysoc->country_code);

            $text.= '<tr class="oddeven"><td>' . $langs->trans("HideLocalTaxOnPDF",$langs->transcountry("LT2",$mysoc->country_code)) . '</td><td>';
            $text.= $form->selectyesno('MAIN_PDF_MAIN_HIDE_THIRD_TAX', (!empty($conf->global->MAIN_PDF_MAIN_HIDE_THIRD_TAX)) ? $conf->global->MAIN_PDF_MAIN_HIDE_THIRD_TAX : 0, 1);
            $text.= '</td></tr>';
        }
    }

    $title = $langs->trans("PDFRulesForSalesTax");
    if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2))
    {
   		$title.=' - '.$langs->trans("PDFLocaltax",$locales);
    }

    print load_fiche_titre($title,'','');

    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Hide any information on Sale tax / VAT

    print '<tr class="oddeven"><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))?$conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT:0,1);
    print '</td></tr>';

    // Locataxes
    print $text;

    print '</table>';
    print '<br>';


    // Other
    print load_fiche_titre($langs->trans("Other"),'','');

	print '<div class="div-table-responsive-no-min">';
	print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    //Desc

    print '<tr class="oddeven"><td>'.$langs->trans("HideDescOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DESC',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC))?$conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC:0,1);
    print '</td></tr>';

    //Ref

    print '<tr class="oddeven"><td>'.$langs->trans("HideRefOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_REF',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF))?$conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF:0,1);
    print '</td></tr>';

    //Details

    print '<tr class="oddeven"><td>'.$langs->trans("HideDetailsOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS))?$conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS:0,1);
    print '</td></tr>';

	//Invert sender and recipient

	print '<tr class="oddeven"><td>'.$langs->trans("SwapSenderAndRecipientOnPDF").'</td><td>';
	print $form->selectyesno('MAIN_INVERT_SENDER_RECIPIENT',(! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT))?$conf->global->MAIN_INVERT_SENDER_RECIPIENT:0,1);
	print '</td></tr>';

 	// Place customer adress to the ISO location

    print '<tr class="oddeven"><td>'.$langs->trans("PlaceCustomerAddressToIsoLocation").'</td><td>';
	print $form->selectyesno('MAIN_PDF_USE_ISO_LOCATION',(! empty($conf->global->MAIN_PDF_USE_ISO_LOCATION))?$conf->global->MAIN_PDF_USE_ISO_LOCATION:0,1);
    print '</td></tr>';


    print '<tr class="oddeven"><td>'.$langs->trans("ShowDetailsInPDFPageFoot").'</td><td>';
	print $form->selectarray('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', $arraydetailsforpdffoot, $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS);
	print '</td></tr>';

	print '</table>';
	print '</div>';

    print '<br><div class="center">';
    print '<input class="button" type="submit" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; ';
    print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</div>';

    print '</form>';
    print '<br>';
}
else	// Show
{
    // Misc options
    print load_fiche_titre($langs->trans("DictionaryPaperFormat"),'','');


	print '<div class="div-table-responsive-no-min">';
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Show pdf format

    print '<tr class="oddeven"><td>'.$langs->trans("DictionaryPaperFormat").'</td><td>';

    $pdfformatlabel='';
    if (empty($conf->global->MAIN_PDF_FORMAT))
    {
        include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
        $pdfformatlabel=dol_getDefaultFormat();
    }
    else $pdfformatlabel=$conf->global->MAIN_PDF_FORMAT;
    if (! empty($pdfformatlabel))
    {
    	$sql="SELECT code, label, width, height, unit FROM ".MAIN_DB_PREFIX."c_paper_format";
        $sql.=" WHERE code LIKE '%".$db->escape($pdfformatlabel)."%'";

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj=$db->fetch_object($resql);
            $paperKey = $langs->trans('PaperFormat'.$obj->code);
            $unitKey = $langs->trans('SizeUnit'.$obj->unit);
            $pdfformatlabel = ($paperKey == 'PaperFormat'.$obj->code ? $obj->label : $paperKey).' - '.round($obj->width).'x'.round($obj->height).' '.($unitKey == 'SizeUnit'.$obj->unit ? $obj->unit : $unitKey);
        }
    }
    print $pdfformatlabel;
    print '</td></tr>';

    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_LEFT").'</td><td>';
    print empty($conf->global->MAIN_PDF_MARGIN_LEFT)?10:$conf->global->MAIN_PDF_MARGIN_LEFT;
    print '</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_RIGHT").'</td><td>';
    print empty($conf->global->MAIN_PDF_MARGIN_RIGHT)?10:$conf->global->MAIN_PDF_MARGIN_RIGHT;
    print '</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_TOP").'</td><td>';
    print empty($conf->global->MAIN_PDF_MARGIN_TOP)?10:$conf->global->MAIN_PDF_MARGIN_TOP;
    print '</td></tr>';
    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_PDF_MARGIN_BOTTOM").'</td><td>';
    print empty($conf->global->MAIN_PDF_MARGIN_BOTTOM)?10:$conf->global->MAIN_PDF_MARGIN_BOTTOM;
    print '</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';

	print load_fiche_titre($langs->trans("PDFAddressForging"),'','');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	// Hide Intra VAT on address

	print '<tr class="oddeven"><td>'.$langs->trans("ShowVATIntaInAddress").'</td><td colspan="2">';
	print yn($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS,1);
	print '</td></tr>';

	// Show prof id in address into pdf
	for ($i=1; $i<=6; $i++)
	{
	    if (! $noCountryCode)
	    {
	        $pid=$langs->transcountry("ProfId".$i, $mysoc->country_code);
	        if ($pid == '-') $pid=false;
	    }
	    else
	    {
	        $pid = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
	    }
	    if ($pid)
	    {
	        print '<tr class="oddeven"><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid.'</td><td>';
	        $keyforconstant = 'MAIN_PROFID'.$i.'_IN_ADDRESS';
	        print yn($conf->global->$keyforconstant, 1);
	        print '</td></tr>';
	    }
	}

    print '</table>'."\n";
	print '</div>';

    print '<br>';

    // Localtaxes
    $locales ='';
    $text='';
    if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2))
    {
        if ($mysoc->useLocalTax(1))
        {
            $locales = $langs->transcountry("LT1",$mysoc->country_code);
            $text ='<tr class="oddeven"><td>' . $langs->trans("HideLocalTaxOnPDF",$langs->transcountry("LT1",$mysoc->country_code)) . '</td><td>';
            $text .= yn($conf->global->MAIN_PDF_MAIN_HIDE_SECOND_TAX,1);
            $text .= '</td></tr>';
        }

        if ($mysoc->useLocalTax(2))
        {
            $locales.=($locales?' & ':'').$langs->transcountry("LT2",$mysoc->country_code);

            $text.= '<tr class="oddeven"><td>' . $langs->trans("HideLocalTaxOnPDF",$langs->transcountry("LT2",$mysoc->country_code)) . '</td><td>';
            $text.= yn($conf->global->MAIN_PDF_MAIN_HIDE_THIRD_TAX,1);
            $text.= '</td></tr>';
        }
    }

    // Sales TAX / VAT information
    $title=$langs->trans("PDFRulesForSalesTax",$locales);
    if ($mysoc->useLocalTax(1) || $mysoc->useLocalTax(2)) $title.=' - '.$langs->trans("PDFLocaltax",$locales);

    print load_fiche_titre($title,'','');

    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    print '<tr class="oddeven"><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td colspan="2">';
    print yn($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT,1);
    print '</td></tr>';

    print $text;

    print '</table>';
    print '<br>';


    // Other
    print load_fiche_titre($langs->trans("Other"),'','');

	print '<div class="div-table-responsive-no-min">';
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px" colspan="2">'.$langs->trans("Value").'</td></tr>';

	// Encrypt and protect PDF

	print '<tr class="oddeven">';
	print '<td>';
	$text = $langs->trans("ProtectAndEncryptPdfFiles");
	$desc = $form->textwithpicto($text,$langs->transnoentities("ProtectAndEncryptPdfFilesDesc"),1);
	print $desc;
	print '</td>';
	print '<td width="60">';
	if($conf->global->PDF_SECURITY_ENCRYPTION == 1)
	{
		print img_picto($langs->trans("Active"),'tick');
	}
	print '</td>';
	print '<td align="center" width="140">';
	if (empty($conf->global->PDF_SECURITY_ENCRYPTION))
	{
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=activate_pdfsecurity">'.$langs->trans("Activate").'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=disable_pdfsecurity">'.$langs->trans("Disable").'</a>';
	}
	print "</td>";

	print "</td>";
	print '</tr>';

	// Hide Desc

	print '<tr class="oddeven"><td>'.$langs->trans("HideDescOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC,1);
	print '</td></tr>';

	// Hide Ref

	print '<tr class="oddeven"><td>'.$langs->trans("HideRefOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF,1);
	print '</td></tr>';

	// Hide Details

	print '<tr class="oddeven"><td>'.$langs->trans("HideDetailsOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS,1);
	print '</td></tr>';

	// Invert sender and recipient
	print '<tr class="oddeven"><td>'.$langs->trans("SwapSenderAndRecipientOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_INVERT_SENDER_RECIPIENT,1);
	print '</td></tr>';

	// Use French location
	print '<tr class="oddeven"><td>'.$langs->trans("PlaceCustomerAddressToIsoLocation").'</td><td colspan="2">';
	print yn($conf->global->MAIN_PDF_USE_ISO_LOCATION,1);
	print '</td></tr>';


	print '<tr class="oddeven"><td>'.$langs->trans("ShowDetailsInPDFPageFoot").'</td><td colspan="2">';
	print $arraydetailsforpdffoot[($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS ? $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS : 0)];
	print '</td></tr>';

	print '</table>';
	print '</div>';


	/*
	 *  Library
	 */

	print '<br>';
	print load_fiche_titre($langs->trans("Library"), '', '');

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">'."\n";

	print '<tr class="liste_titre">'."\n";
	print '<td>'.$langs->trans("Name").'</td>'."\n";
	print '<td>'.$langs->trans("Value").'</td>'."\n";
	print "</tr>\n";

	print '<tr class="oddeven">'."\n";
	print '<td>'.$langs->trans("LibraryToBuildPDF").'</td>'."\n";
	print '<td>';
	$i=0;
	$pdf=pdf_getInstance('A4');
	if (class_exists('FPDF') && ! class_exists('TCPDF'))
	{
		if ($i) print ' + ';
		print 'FPDF';
		print ' ('.@constant('FPDF_PATH').')';
		$i++;
	}
	if (class_exists('TCPDF'))
	{
		if ($i) print ' + ';
		print 'TCPDF';
		print ' ('.@constant('TCPDF_PATH').')';
		$i++;
	}
	if (class_exists('FPDI'))
	{
		if ($i) print ' + ';
		print 'FPDI';
		print ' ('.@constant('FPDI_PATH').')';
		$i++;
	}
	if (class_exists('TCPDI'))
	{
		if ($i) print ' + ';
		print 'TCPDI';
		print ' ('.@constant('TCPDI_PATH').')';
		$i++;
	}
	print '</td>'."\n";
	print '</tr>'."\n";

	print "</table>\n";
	print '</div>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	print '<br>';
}

// End of page
llxFooter();
$db->close();
