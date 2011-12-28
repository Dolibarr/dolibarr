<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/usergroups.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");

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

	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
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

print_fiche_titre($langs->trans("PDF"),'','setup');

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
    print_fiche_titre($langs->trans("DictionnaryPaperFormat"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    $selected=$conf->global->MAIN_PDF_FORMAT;
    if (empty($selected)) $selected=dol_getDefaultFormat();

    // Show pdf format
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DictionnaryPaperFormat").'</td><td>';
    print $formadmin->select_paper_format($selected,'MAIN_PDF_FORMAT');
    print '</td></tr>';

	print '</table>';

	print '<br>';


    // Addresses
    print_fiche_titre($langs->trans("PDFAddressForging"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

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
    print_fiche_titre($langs->trans("Other"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Hide any PDF informations
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td>';
	print $form->selectyesno('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT',(! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))?$conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT:0,1);
    print '</td></tr>';

	print '</table>';

    print '<br><center>';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</center>';

    print '</form>';
    print '<br>';
}
else	// Show
{
    $var=true;

    // Misc options
    print_fiche_titre($langs->trans("DictionnaryPaperFormat"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Show pdf format
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DictionnaryPaperFormat").'</td><td>';
    $pdfformatlabel=$conf->global->MAIN_PDF_FORMAT;
    if (! empty($conf->global->MAIN_PDF_FORMAT))
    {
    	$sql="SELECT code, label, width, height, unit FROM ".MAIN_DB_PREFIX."c_paper_format";
        $sql.=" WHERE code LIKE '%".$conf->global->MAIN_PDF_FORMAT."%'";

        $resql=$db->query($sql);
        if ($resql)
        {
            $obj=$db->fetch_object($resql);
            $pdfformatlabel=$obj->label.' - '.round($obj->width).'x'.round($obj->height).' '.$obj->unit;
        }
    }
    print $pdfformatlabel;
    print '</td></tr>';

	print '</table>';

	print '<br>';

	print_fiche_titre($langs->trans("PDFAddressForging"),'','').'<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

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
    print_fiche_titre($langs->trans("Other"),'','').'<br>';
	$var=true;
    print '<table summary="more" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td width="200px">'.$langs->trans("Value").'</td></tr>';

    // Hide any PDF informations
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("HideAnyVATInformationOnPDF").'</td><td>';
    print yn($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT,1);
    print '</td></tr>';

	print '</table>';


    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	print '<br>';
}


$db->close();

llxFooter();
?>
