<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/admin/ihm.php
 *       \brief      Page de configuration de l'interface homme machine
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/html.formadmin.class.php");

$langs->load("admin");
$langs->load("other");

$langs->load("companies");
$langs->load("products");
$langs->load("members");

if (!$user->admin)
  accessforbidden();


if (! defined("MAIN_MOTD")) define("MAIN_MOTD","");

$dirtheme = "../theme";

// List of supported permanent search area
$searchform=array(	"MAIN_SEARCHFORM_SOCIETE","MAIN_SEARCHFORM_CONTACT",
					"MAIN_SEARCHFORM_PRODUITSERVICE","MAIN_SEARCHFORM_ADHERENT");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,
					$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE,$conf->global->MAIN_SEARCHFORM_ADHERENT);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),
					$langs->trans("ProductsAndServices"),$langs->trans("Members"));
$searchformmodule=array('Module1Name','Module1Name',
					'Module50Name','Module310Name');


if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
	dolibarr_set_const($db, "MAIN_LANG_DEFAULT",       $_POST["main_lang_default"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MULTILANGS",         $_POST["main_multilangs"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SIZE_LISTE_LIMIT",   $_POST["main_size_liste_limit"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_DISABLE_JAVASCRIPT", $_POST["main_disable_javascript"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_CONFIRM_AJAX",       $_POST["main_confirm_ajax"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_POPUP_CALENDAR",     $_POST["main_popup_calendar"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_USE_PREVIEW_TABS",   $_POST["main_use_preview_tabs"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_THEME",              $_POST["main_theme"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_SEARCHFORM_CONTACT", $_POST["MAIN_SEARCHFORM_CONTACT"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_SOCIETE", $_POST["MAIN_SEARCHFORM_SOCIETE"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_PRODUITSERVICE",$_POST["MAIN_SEARCHFORM_PRODUITSERVICE"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_ADHERENT",$_POST["MAIN_SEARCHFORM_ADHERENT"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_HELPCENTER_DISABLELINK", $_POST["MAIN_HELPCENTER_DISABLELINK"],'chaine',0,'',0);	// Param for all entities
	dolibarr_set_const($db, "MAIN_MOTD",                   dol_htmlcleanlastbr($_POST["main_motd"]),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_HOME",                   dol_htmlcleanlastbr($_POST["main_home"]),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_HELP_DISABLELINK",       $_POST["MAIN_HELP_DISABLELINK"],'chaine',0,'',0);	    // Param for all entities
	dolibarr_set_const($db, "MAIN_BUGTRACK_ENABLELINK",    $_POST["MAIN_BUGTRACK_ENABLELINK"],'chaine',0,'',$conf->entity);


	$_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer

	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * View
 */

llxHeader();

$html=new Form($db);
$formadmin=new FormAdmin($db);

print_fiche_titre($langs->trans("GUISetup"),'','setup');

print $langs->trans("DisplayDesc")."<br>\n";
print "<br>\n";


if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Langue par defaut
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DefaultLanguage").'</td><td>';
    $formadmin->select_lang($conf->global->MAIN_LANG_DEFAULT,'main_lang_default',1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Multilangual GUI
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("EnableMultilangInterface").'</td><td>';
    print $html->selectyesno('main_multilangs',$conf->global->MAIN_MULTILANGS,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Taille max des listes
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMaxSizeList").'</td><td><input class="flat" name="main_size_liste_limit" size="4" value="' . $conf->global->MAIN_SIZE_LISTE_LIMIT . '"></td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Desactivation javascript et ajax
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableJavascript").'</td><td>';
    print $html->selectyesno('main_disable_javascript',isset($conf->global->MAIN_DISABLE_JAVASCRIPT)?$conf->global->MAIN_DISABLE_JAVASCRIPT:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Use Ajax popups for confirmation
	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ConfirmAjax").'</td><td>';
    print $html->selectyesno('main_confirm_ajax',isset($conf->global->MAIN_CONFIRM_AJAX)?$conf->global->MAIN_CONFIRM_AJAX:0,1);
    print ' ('.$langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled").')';
    print '</td>';
	print '<td width="20">';
	//print $html->textwithpicto('',$langs->trans("FeatureDevelopment"));
	print '&nbsp;</td>';
	print '</tr>';

    // Desactiver le calendrier popup
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePopupCalendar").'</td><td>';
    $liste_popup_calendar=array(
		'0'=>$langs->trans("No"),
		'eldy'=>$langs->trans("Yes")
		//'eldy'=>$langs->trans("Yes").' (style eldy)',
		//'andre'=>$langs->trans("Yes").' (style andre)'
		);
    $html->select_array('main_popup_calendar',$liste_popup_calendar,$conf->global->MAIN_POPUP_CALENDAR);
    print ' ('.$langs->trans("AvailableOnlyIfJavascriptNotDisabled").')';
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Activate previeuw tab on element card
    if (function_exists("imagick_readimage"))
	{
	    $var=!$var;
	    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePreviewTabs").'</td><td>';
	    print $html->selectyesno('main_use_preview_tabs',isset($conf->global->MAIN_USE_PREVIEW_TABS)?$conf->global->MAIN_USE_PREVIEW_TABS:0,1);
	    print '</td>';
		print '<td width="20">&nbsp;</td>';
		print '</tr>';
	}

    print '</table><br>';


    // Themes
    show_theme('',1);
    print '<br>';


    // Liste des zone de recherche permanantes supportees
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("PermanentLeftSearchForm").'</td><td colspan="2">'.$langs->trans("Activated").'</td></tr>';
    $var=True;
    foreach ($searchform as $key => $value)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$searchformtitle[$key].'</td><td colspan="2">';
        print $html->selectyesno($searchform[$key],$searchformconst[$key],1);
        print '</td></tr>';
    }
    print '</table>';
    print '<br>';

    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td colspan="2">'.$langs->trans("Value").'</td></tr>';

    // Hide help link on login page
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelpCenter").'</td><td>';
    print $html->selectyesno('MAIN_HELPCENTER_DISABLELINK',isset($conf->global->MAIN_HELPCENTER_DISABLELINK)?$conf->global->MAIN_HELPCENTER_DISABLELINK:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Message on login page
	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageLogin").'</td><td colspan="2">';
	if ($conf->fckeditor->enabled)
	{
		// Editeur wysiwyg
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('main_home',$conf->global->MAIN_HOME,158,'dolibarr_notes','In',false);
		$doleditor->Create();
	}
	else
	{
		print '<textarea name="main_home" cols="90" rows="'.ROWS_5.'">'.dol_htmlentitiesbr_decode($conf->global->MAIN_HOME).'</textarea>';
	}
	print '</td></tr>';

	// Message of the day on home page
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td colspan="2">';
	if ($conf->fckeditor->enabled)
	{
		// Editeur wysiwyg
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('main_motd',$conf->global->MAIN_MOTD,158,'dolibarr_notes','In',false);
		$doleditor->Create();
	}
	else
	{
		print '<textarea name="main_motd" cols="90" rows="'.ROWS_5.'">'.dol_htmlentitiesbr_decode($conf->global->MAIN_MOTD).'</textarea>';
	}
	print '</td></tr>';

    // Hide help link on login page
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelp",img_picto('',DOL_URL_ROOT.'/theme/common/helpdoc.png','',1)).'</td><td>';
    print $html->selectyesno('MAIN_HELP_DISABLELINK',isset($conf->global->MAIN_HELP_DISABLELINK)?$conf->global->MAIN_HELP_DISABLELINK:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	/*
	// Show bugtrack link
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';
    print $html->selectyesno('main_show_bugtrack_link',$conf->global->MAIN_BUGTRACK_ENABLELINK,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';
	*/

	print '</table>';

    print '<br><center>';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</center>';

    print '</form>';
    print '<br>';
}
else
{
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td><td>&nbsp;</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DefaultLanguage").'</td><td>' . ($conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$conf->global->MAIN_LANG_DEFAULT) . '</td>';
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("EnableMultilangInterface").'</td><td>' . yn($conf->global->MAIN_MULTILANGS) . '</td>';
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMaxSizeList").'</td><td>' . $conf->global->MAIN_SIZE_LISTE_LIMIT . '</td>';
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    // Disable javascript/ajax
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableJavascript").'</td><td>';
    print yn($conf->global->MAIN_DISABLE_JAVASCRIPT)."</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    // Confirm ajax
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ConfirmAjax").'</td><td>';
    if ($conf->global->MAIN_DISABLE_JAVASCRIPT) print $langs->trans("No").' ('.$langs->trans("JavascriptDisabled").')';
    else print yn(isset($conf->global->MAIN_CONFIRM_AJAX)?$conf->global->MAIN_CONFIRM_AJAX:0)."</td>";
	print '<td width="20">';
	//print $html->textwithpicto('',$langs->trans("FeatureDevelopment"));
	print '&nbsp;</td>';
	print "</tr>";

    // Calendrier en popup
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePopupCalendar").'</td><td>';
    if ($conf->global->MAIN_DISABLE_JAVASCRIPT) print $langs->trans("No").' ('.$langs->trans("JavascriptDisabled").')';
    else print ($conf->global->MAIN_POPUP_CALENDAR?$langs->trans("Yes"):$langs->trans("No"));
    print "</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    // Activate previeuw tab on element card
    if (function_exists("imagick_readimage"))
	{
		$var=!$var;
	    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePreviewTabs").'</td><td>';
	    print yn(isset($conf->global->MAIN_USE_PREVIEW_TABS)?$conf->global->MAIN_USE_PREVIEW_TABS:0)."</td>";
		print '<td width="20">&nbsp;</td>';
		print "</tr>";
	}

    print '</table><br>';


    // Themes
    show_theme('',0);
    print '<br>';


    // Liste des zone de recherche permanantes support�es
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td><td>&nbsp;</td></tr>';
    $var=true;
    foreach ($searchform as $key => $value)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'"><td width="35%">'.$searchformtitle[$key].'</td><td>'.yn($searchformconst[$key]).'</td>';
		print '<td align="left">'.$langs->trans("IfModuleEnabled",$langs->transnoentitiesnoconv($searchformmodule[$key]));
        print '</td></tr>';
    }
    print '</table>';
    print '<br>';

    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // Link to help center
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelpCenter").'</td><td>';
    print yn($conf->global->MAIN_HELPCENTER_DISABLELINK,1);
    print '</td></tr>';

    // Message login
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageLogin").'</td><td>';
    print nl2br($conf->global->MAIN_HOME);
    print '</td></tr>';

    // Message of the day
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td>';
    print nl2br($conf->global->MAIN_MOTD);
    print '</td></tr>';

    // Link to wiki help
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelp",img_picto('',DOL_URL_ROOT.'/theme/common/helpdoc.png','',1)).'</td><td>';
    print yn($conf->global->MAIN_HELP_DISABLELINK,1);
    print '</td></tr>';

    /*
    // Show bugtrack link
	$var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';
    print yn($conf->global->MAIN_BUGTRACK_ENABLELINK)."</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";
	*/


    print '</table>';


    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	print '<br>';
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
