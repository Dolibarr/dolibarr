<?php
/* Copyright (C) 2001-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis@dolibarr.fr>
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
 *       \file       htdocs/admin/ihm.php
 *       \brief      Page to setup GUI display options
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';

$langs->load("admin");
$langs->load("languages");
$langs->load("other");

$langs->load("companies");
$langs->load("products");
$langs->load("members");

if (! $user->admin) accessforbidden();

$action = GETPOST('action');


if (! defined("MAIN_MOTD")) define("MAIN_MOTD","");

// List of supported permanent search area
$searchform=array("MAIN_SEARCHFORM_SOCIETE","MAIN_SEARCHFORM_CONTACT", "MAIN_SEARCHFORM_PRODUITSERVICE","MAIN_SEARCHFORM_ADHERENT");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE,$conf->global->MAIN_SEARCHFORM_ADHERENT);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"),$langs->trans("Members"));
$searchformmodule=array('Module1Name','Module1Name','Module50Name','Module310Name');


if ($action == 'update')
{
	dolibarr_set_const($db, "MAIN_LANG_DEFAULT",       $_POST["main_lang_default"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MULTILANGS",         $_POST["main_multilangs"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SIZE_LISTE_LIMIT",   $_POST["main_size_liste_limit"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_DISABLE_JAVASCRIPT", $_POST["main_disable_javascript"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_BUTTON_HIDE_UNAUTHORIZED", $_POST["MAIN_BUTTON_HIDE_UNAUTHORIZED"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_START_WEEK",         $_POST["MAIN_START_WEEK"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SHOW_LOGO",          $_POST["MAIN_SHOW_LOGO"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_FIRSTNAME_NAME_POSITION",          $_POST["MAIN_FIRSTNAME_NAME_POSITION"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_THEME",              $_POST["main_theme"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_SEARCHFORM_CONTACT", $_POST["MAIN_SEARCHFORM_CONTACT"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_SOCIETE", $_POST["MAIN_SEARCHFORM_SOCIETE"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_PRODUITSERVICE",$_POST["MAIN_SEARCHFORM_PRODUITSERVICE"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_ADHERENT",$_POST["MAIN_SEARCHFORM_ADHERENT"],'chaine',0,'',$conf->entity);

	dolibarr_set_const($db, "MAIN_HELPCENTER_DISABLELINK", $_POST["MAIN_HELPCENTER_DISABLELINK"],'chaine',0,'',0);	// Param for all entities
	dolibarr_set_const($db, "MAIN_MOTD",                   dol_htmlcleanlastbr($_POST["main_motd"]),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_HOME",                   dol_htmlcleanlastbr($_POST["main_home"]),'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_HELP_DISABLELINK",       $_POST["MAIN_HELP_DISABLELINK"],'chaine',0,'',0);	    // Param for all entities

	// This one is not always defined
	if (isset($_POST["MAIN_USE_PREVIEW_TABS"])) dolibarr_set_const($db, "MAIN_USE_PREVIEW_TABS", $_POST["MAIN_USE_PREVIEW_TABS"],'chaine',0,'',$conf->entity);
	if (isset($_POST["MAIN_BUGTRACK_ENABLELINK"])) dolibarr_set_const($db, "MAIN_BUGTRACK_ENABLELINK", $_POST["MAIN_BUGTRACK_ENABLELINK"],'chaine',0,'',$conf->entity);

	$_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer

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

print_fiche_titre($langs->trans("GUISetup"),'','setup');

print $langs->trans("DisplayDesc")."<br>\n";
print "<br>\n";


if ($action == 'edit')	// Edit
{
    //WYSIWYG Editor
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;

    print_fiche_titre($langs->trans("Language"),'','').'<br>';
    print '<table summary="edit" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Default language
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DefaultLanguage").'</td><td>';
    print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT,'main_lang_default',1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Multilangual GUI
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("EnableMultilangInterface").'</td><td>';
    print $form->selectyesno('main_multilangs',$conf->global->MAIN_MULTILANGS,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	print '</table><br>'."\n";

    // Themes
    show_theme('',1);
    print '<br>';

    // Liste des zone de recherche permanantes supportees
    print '<table summary="search" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("PermanentLeftSearchForm").'</td><td colspan="2">'.$langs->trans("Activated").'</td></tr>';
    $var=True;
    foreach ($searchform as $key => $value)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$searchformtitle[$key].'</td><td colspan="2">';
        print $form->selectyesno($searchform[$key],$searchformconst[$key],1);
        print '</td></tr>';
    }
    print '</table>';
    print '<br>';

    // Other
    print '<table summary="edit" class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Show logo
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("EnableShowLogo").'</td><td>';
    print $form->selectyesno('MAIN_SHOW_LOGO',$conf->global->MAIN_SHOW_LOGO,1);
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
    print $form->selectyesno('main_disable_javascript',isset($conf->global->MAIN_DISABLE_JAVASCRIPT)?$conf->global->MAIN_DISABLE_JAVASCRIPT:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Activate preview tab on element card
    if (class_exists("Imagick"))
	{
	    $var=!$var;
	    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePreviewTabs").'</td><td>';
	    print $form->selectyesno('MAIN_USE_PREVIEW_TABS',isset($conf->global->MAIN_USE_PREVIEW_TABS)?$conf->global->MAIN_USE_PREVIEW_TABS:0,1);
	    print '</td>';
		print '<td width="20">&nbsp;</td>';
		print '</tr>';
	}

    // First day for weeks
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("WeekStartOnDay").'</td><td>';
    print $formother->select_dayofweek((isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:'1'),'MAIN_START_WEEK',0);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Firstname/Name
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("FirstnameNamePosition").'</td><td>';
	$array=array(0=>$langs->trans("Firstname").' '.$langs->trans("Lastname"),1=>$langs->trans("Lastname").' '.$langs->trans("Firstname"));
    print $form->selectarray('MAIN_FIRSTNAME_NAME_POSITION',$array,(isset($conf->global->MAIN_FIRSTNAME_NAME_POSITION)?$conf->global->MAIN_FIRSTNAME_NAME_POSITION:0));
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Hide unauthorized button
	$var=!$var;
	print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ButtonHideUnauthorized").'</td><td>';
	print $form->selectyesno('MAIN_BUTTON_HIDE_UNAUTHORIZED',isset($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)?$conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED:0,1);
	print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    // Hide helpcenter link on login page
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelpCenter").'</td><td>';
    print $form->selectyesno('MAIN_HELPCENTER_DISABLELINK',isset($conf->global->MAIN_HELPCENTER_DISABLELINK)?$conf->global->MAIN_HELPCENTER_DISABLELINK:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Hide wiki link on login page
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelp",img_picto('',DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/helpdoc.png','',1)).'</td><td>';
    print $form->selectyesno('MAIN_HELP_DISABLELINK', isset($conf->global->MAIN_HELP_DISABLELINK)?$conf->global->MAIN_HELP_DISABLELINK:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	if ($conf->global->MAIN_FEATURES_LEVEL >= 1 || ! empty($conf->global->MAIN_BUGTRACK_ENABLELINK))
	{
		 // Show bugtrack link
		$var=!$var;
		print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';
		print $form->selectyesno('MAIN_BUGTRACK_ENABLELINK',$conf->global->MAIN_BUGTRACK_ENABLELINK,1);
		print '</td>';
		print '<td width="20">&nbsp;</td>';
		print '</tr>';
	}

    // Message on login page
	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageLogin").'</td><td colspan="2">';

    $doleditor = new DolEditor('main_home', (isset($conf->global->MAIN_HOME)?$conf->global->MAIN_HOME:''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, 90);
	$doleditor->Create();

	print '</td></tr>'."\n";

	// Message of the day on home page
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td colspan="2">';

    $doleditor = new DolEditor('main_motd', (isset($conf->global->MAIN_MOTD)?$conf->global->MAIN_MOTD:''), '', 142, 'dolibarr_notes', 'In', false, true, true, ROWS_4, 90);
	$doleditor->Create();

	print '</td></tr>'."\n";

	print '</table>'."\n";


    print '<br><center>';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</center>';

    print '</form>';
    print '<br>';
}
else	// Show
{
    $var=true;

    // Language
    print_fiche_titre($langs->trans("Language"),'','').'<br>';
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td><td>&nbsp;</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DefaultLanguage").'</td><td>';
    $s=picto_from_langcode($conf->global->MAIN_LANG_DEFAULT);
    print ($s?$s.' ':'');
    print ($conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$langs->trans("Language_".$conf->global->MAIN_LANG_DEFAULT));
    print '</td>';
	print '<td width="20">';
    if ($user->admin && $conf->global->MAIN_LANG_DEFAULT!='auto') print info_admin($langs->trans("SubmitTranslation",$conf->global->MAIN_LANG_DEFAULT),1);
	print '</td>';
	print "</tr>";

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("EnableMultilangInterface").'</td><td>' . yn($conf->global->MAIN_MULTILANGS) . '</td>';
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

	print '</table><br>'."\n";


	// Themes
    show_theme('',0);
    print '<br>';


    // Liste des zone de recherches permanentes supportees
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td><td>&nbsp;</td></tr>';
    $var=true;
    foreach ($searchform as $key => $value)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$searchformtitle[$key].'</td><td>'.yn($searchformconst[$key]).'</td>';
		print '<td align="left">'.$langs->trans("IfModuleEnabled",$langs->transnoentitiesnoconv($searchformmodule[$key]));
        print '</td></tr>';
    }
    print '</table>';
    print '<br>';


    // Other
    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Parameter").'</td><td colspan="2">'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("EnableShowLogo").'</td><td>' . yn($conf->global->MAIN_SHOW_LOGO) . '</td>';
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

    // Activate preview tab on element card
    if (class_exists("Imagick"))
	{
		$var=!$var;
	    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePreviewTabs").'</td><td>';
	    print yn(isset($conf->global->MAIN_USE_PREVIEW_TABS)?$conf->global->MAIN_USE_PREVIEW_TABS:0)."</td>";
		print '<td width="20">&nbsp;</td>';
		print "</tr>";
	}

	// First day for weeks
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("WeekStartOnDay").'</td><td>';
    print $langs->trans("Day".(isset($conf->global->MAIN_START_WEEK)?$conf->global->MAIN_START_WEEK:'1'));
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Firstname / Name position
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("FirstnameNamePosition").'</td><td>';
    if (empty($conf->global->MAIN_FIRSTNAME_NAME_POSITION)) { print $langs->trans("Firstname").' '.$langs->trans("Lastname"); }
    else { print $langs->trans("Lastname").' '.$langs->trans("Firstname"); }
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

	// Hide unauthorized button
	$var=!$var;
	print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ButtonHideUnauthorized").'</td><td colspan="2">';
	print yn((isset($conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED)?$conf->global->MAIN_BUTTON_HIDE_UNAUTHORIZED:0),1);
	print '</td></tr>';

    // Link to help center
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelpCenter").'</td><td colspan="2">';
    print yn((isset($conf->global->MAIN_HELPCENTER_DISABLELINK)?$conf->global->MAIN_HELPCENTER_DISABLELINK:0),1);
    print '</td></tr>';

    // Link to wiki help
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableLinkToHelp",img_picto('',DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/helpdoc.png','',1)).'</td><td colspan="2">';
    print yn((isset($conf->global->MAIN_HELP_DISABLELINK)?$conf->global->MAIN_HELP_DISABLELINK:0),1);
    print '</td></tr>';

    if ($conf->global->MAIN_FEATURES_LEVEL >= 1 || ! empty($conf->global->MAIN_BUGTRACK_ENABLELINK))
    {
    	// Show bugtrack link
    	$var=!$var;
    	print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';
    	print yn($conf->global->MAIN_BUGTRACK_ENABLELINK)."</td>";
    	print '<td width="20">&nbsp;</td>';
    	print "</tr>";
    }

    // Message login
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageLogin").'</td><td colspan="2">';
    if (isset($conf->global->MAIN_HOME)) print dol_htmlcleanlastbr($conf->global->MAIN_HOME);
    else print '&nbsp;';
    print '</td></tr>'."\n";

    // Message of the day
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td colspan="2">';
    if (isset($conf->global->MAIN_MOTD)) print dol_htmlcleanlastbr($conf->global->MAIN_MOTD);
    else print '&nbsp;';
    print '</td></tr>'."\n";

    print '</table>'."\n";

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	print '<br>';
}


llxFooter();
$db->close();
?>