<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2105 Juanjo Menent		<jmenent@2byte.es>
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

$langs->load("admin");
$langs->load("languages");
$langs->load("other");

$langs->load("companies");
$langs->load("products");
$langs->load("members");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

/*
 * Actions
 */

if ($action == 'update')
{
	dolibarr_set_const($db, "MAIN_PDF_FORMAT",    $_POST["MAIN_PDF_FORMAT"],'chaine',0,'',$conf->entity);


    dolibarr_set_const($db, "MAIN_PROFID1_IN_ADDRESS",    $_POST["MAIN_PROFID1_IN_ADDRESS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID2_IN_ADDRESS",    $_POST["MAIN_PROFID2_IN_ADDRESS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID3_IN_ADDRESS",    $_POST["MAIN_PROFID3_IN_ADDRESS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_PROFID4_IN_ADDRESS",    $_POST["MAIN_PROFID4_IN_ADDRESS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT",    $_POST["MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_TVAINTRA_NOT_IN_ADDRESS",    $_POST["MAIN_TVAINTRA_NOT_IN_ADDRESS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS", $_POST["MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_DESC",    $_POST["MAIN_GENERATE_DOCUMENTS_HIDE_DESC"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_HIDE_REF",     $_POST["MAIN_GENERATE_DOCUMENTS_HIDE_REF"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_PDF_USE_ISO_LOCATION",     $_POST["MAIN_PDF_USE_ISO_LOCATION"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS",     $_POST["MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS"],'chaine',0,'',$conf->entity);
	
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
	0 => $langs->trans('NoDetails'),
	1 => $langs->trans('DisplayCompanyInfo'),
	2 => $langs->trans('DisplayManagersInfo'),
	3 => $langs->trans('DisplayCompanyInfoAndManagers')
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
    print load_fiche_titre($langs->trans("DictionaryPaperFormat"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    $selected=$conf->global->MAIN_PDF_FORMAT;
    if (empty($selected)) $selected=dol_getDefaultFormat();

    // Show pdf format
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DictionaryPaperFormat").'</td><td>';
    print $formadmin->select_paper_format($selected,'MAIN_PDF_FORMAT');
    print '</td></tr>';

	print '</table>';

	print '<br>';


    // Addresses
    print load_fiche_titre($langs->trans("PDFAddressForging"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Hide VAT Intra on address
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowVATIntaInAddress").'</td><td>';
    print $form->selectyesno('MAIN_TVAINTRA_NOT_IN_ADDRESS',(! empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS))?$conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS:0,1);
    print '</td></tr>';

    // Show prof id 1 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid1=$langs->transcountry("ProfId1",$mysoc->country_code);
    	if ($pid1 == '-') $pid1=false;
    }
    else
    {
    	$pid1 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid1)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid1.'</td><td>';
    	print $form->selectyesno('MAIN_PROFID1_IN_ADDRESS',isset($conf->global->MAIN_PROFID1_IN_ADDRESS)?$conf->global->MAIN_PROFID1_IN_ADDRESS:0,1,$noCountryCode);
    	print '</td></tr>';
    }

    // Show prof id 2 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid2=$langs->transcountry("ProfId2",$mysoc->country_code);
    	if ($pid2 == '-') $pid2=false;
    }
    else
    {
    	$pid2 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid2)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid2.'</td><td>';
    	print $form->selectyesno('MAIN_PROFID2_IN_ADDRESS',isset($conf->global->MAIN_PROFID2_IN_ADDRESS)?$conf->global->MAIN_PROFID2_IN_ADDRESS:0,1,$noCountryCode);
    	print '</td></tr>';
    }

    // Show prof id 3 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid3=$langs->transcountry("ProfId3",$mysoc->country_code);
    	if ($pid3 == '-') $pid3=false;
    }
    else
    {
    	$pid3 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid3)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid3.'</td><td>';
    	print $form->selectyesno('MAIN_PROFID3_IN_ADDRESS',isset($conf->global->MAIN_PROFID3_IN_ADDRESS)?$conf->global->MAIN_PROFID3_IN_ADDRESS:0,1,$noCountryCode);
    	print '</td></tr>';
    }

    // Show prof id 4 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid4=$langs->transcountry("ProfId4",$mysoc->country_code);
    	if ($pid4 == '-') $pid4=false;
    }
    else
    {
    	$pid4 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid4)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid4.'</td><td>';
    	print $form->selectyesno('MAIN_PROFID4_IN_ADDRESS',isset($conf->global->MAIN_PROFID4_IN_ADDRESS)?$conf->global->MAIN_PROFID4_IN_ADDRESS:0,1,$noCountryCode);
    	print '</td></tr>';
    }

	print '</table>';

    print '<br>';

    // Other
    print load_fiche_titre($langs->trans("Other"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Hide any PDF informations
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td>';
	print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))?$conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT:0,1);
    print '</td></tr>';

    //Desc
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideDescOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DESC',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC))?$conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC:0,1);
    print '</td></tr>';

    //Ref
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideRefOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_REF',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF))?$conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF:0,1);
    print '</td></tr>';

    //Details
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideDetailsOnPDF").'</td><td>';
    print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS))?$conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS:0,1);
    print '</td></tr>';

 	// Place customer adress to the ISO location
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("PlaceCustomerAddressToIsoLocation").'</td><td>';
	print $form->selectyesno('MAIN_PDF_USE_ISO_LOCATION',(! empty($conf->global->MAIN_PDF_USE_ISO_LOCATION))?$conf->global->MAIN_PDF_USE_ISO_LOCATION:0,1);
    print '</td></tr>';
	
	$var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowDetailsInPDFPageFoot").'</td><td>';
	print $form->selectarray('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', $arraydetailsforpdffoot, $conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS);
	print '</td></tr>';

	print '</table>';

    print '<br><div class="center">';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</div>';

    print '</form>';
    print '<br>';
}
else	// Show
{
    $var=true;

    // Misc options
    print load_fiche_titre($langs->trans("DictionaryPaperFormat"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Show pdf format
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DictionaryPaperFormat").'</td><td>';

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

	print '</table>';

	print '<br>';

	print load_fiche_titre($langs->trans("PDFAddressForging"),'','').'<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

	// Hide Intra VAT on address
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowVATIntaInAddress").'</td><td colspan="2">';
	print yn($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS,1);
	print '</td></tr>';

    // Show prof id 1 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid1=$langs->transcountry("ProfId1",$mysoc->country_code);
    	if ($pid1 == '-') $pid1=false;
    }
    else
    {
    	$pid1 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid1)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid1.'</td><td>';
    	print yn($conf->global->MAIN_PROFID1_IN_ADDRESS,1);
    	print '</td></tr>';
    }

    // Show prof id 2 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid2=$langs->transcountry("ProfId2",$mysoc->country_code);
    	if ($pid2 == '-') $pid2=false;
    }
    else
    {
    	$pid2 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid2)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid2.'</td><td>';
    	print yn($conf->global->MAIN_PROFID2_IN_ADDRESS,1);
    	print '</td></tr>';
    }

    // Show prof id 3 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid3=$langs->transcountry("ProfId3",$mysoc->country_code);
    	if ($pid3 == '-') $pid3=false;
    }
    else
    {
    	$pid3 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid3)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid3.'</td><td>';
    	print yn($conf->global->MAIN_PROFID3_IN_ADDRESS,1);
    	print '</td></tr>';
    }

    // Show prof id 4 in address into pdf
    $var=!$var;
    if (! $noCountryCode)
    {
    	$pid4=$langs->transcountry("ProfId4",$mysoc->country_code);
    	if ($pid4 == '-') $pid4=false;
    }
    else
    {
    	$pid4 = img_warning().' <font class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("CompanyCountry")).'</font>';
    }
    if ($pid4)
    {
    	print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowProfIdInAddress").' - '.$pid4.'</td><td>';
    	print yn($conf->global->MAIN_PROFID4_IN_ADDRESS,1);
    	print '</td></tr>';
    }

    print '</table>'."\n";

    print '<br>';

    // Other
    print load_fiche_titre($langs->trans("Other"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px" colspan="2">'.$langs->trans("Value").'</td></tr>';


	// Encrypt and protect PDF
	$var=!$var;
	print "<tr ".$bc[$var].">";
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

    // Hide any PDF informations
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td colspan="2">';
    print yn($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT,1);
    print '</td></tr>';

	//Desc
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("HideDescOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC,1);
	print '</td></tr>';

	//Ref
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("HideRefOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF,1);
	print '</td></tr>';

	//Details
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("HideDetailsOnPDF").'</td><td colspan="2">';
	print yn($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS,1);
	print '</td></tr>';

	$var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("PlaceCustomerAddressToIsoLocation").'</td><td colspan="2">';
	print yn($conf->global->MAIN_PDF_USE_ISO_LOCATION,1);
	print '</td></tr>';
	
	$var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("ShowDetailsInPDFPageFoot").'</td><td colspan="2">';
	print $arraydetailsforpdffoot[$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS];
	print '</td></tr>';

	print '</table>';


	/*
	 *  Library
	 */
	print '<br>';
	print load_fiche_titre($langs->trans("Library"));

	print '<table class="noborder" width="100%">'."\n";

	print '<tr class="liste_titre">'."\n";
	print '<td>'.$langs->trans("Name").'</td>'."\n";
	print '<td>'.$langs->trans("Value").'</td>'."\n";
	print "</tr>\n";

	$var=false;
	if (! empty($dolibarr_pdf_force_fpdf))
	{
		$var=!$var;
		print '<tr '.$bc[$var].'>'."\n";
		print '<td>dolibarr_pdf_force_fpdf</td>'."\n";
		print '<td>';
		print $dolibarr_pdf_force_fpdf;
		print '</td>';
		print '</tr>';
	}

	$var=!$var;
	print '<tr '.$bc[$var].'>'."\n";
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
	print '<!-- $conf->global->MAIN_USE_FPDF = '.$conf->global->MAIN_USE_FPDF.' -->';
	print '</td>'."\n";
	print '</tr>'."\n";

	print "</table>\n";

	if (! empty($dolibarr_pdf_force_fpdf))
	{
		print info_admin($langs->trans("WarningUsingFPDF")).'<br>';
	}

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	print '<br>';
}


llxFooter();

$db->close();
