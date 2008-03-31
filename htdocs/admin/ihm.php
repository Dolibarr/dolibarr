<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/admin/ihm.php
        \brief      Page de configuration de l'interface homme machine
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("other");

if (!$user->admin)
  accessforbidden();

if (! defined("MAIN_MOTD")) define("MAIN_MOTD","");

$dirtheme = "../theme";

// Liste des zone de recherche permanantes support�es
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));


if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
	dolibarr_set_const($db, "MAIN_LANG_DEFAULT",       $_POST["main_lang_default"]);
	dolibarr_set_const($db, "MAIN_MULTILANGS",         $_POST["main_multilangs"]);
	dolibarr_set_const($db, "MAIN_SIZE_LISTE_LIMIT",   $_POST["main_size_liste_limit"]);
	dolibarr_set_const($db, "MAIN_DISABLE_JAVASCRIPT", $_POST["main_disable_javascript"]);
	dolibarr_set_const($db, "MAIN_CONFIRM_AJAX",       $_POST["main_confirm_ajax"]);
	dolibarr_set_const($db, "MAIN_POPUP_CALENDAR",     $_POST["main_popup_calendar"]);
	dolibarr_set_const($db, "MAIN_USE_PREVIEW_TABS",   $_POST["main_use_preview_tabs"]);
	
	dolibarr_set_const($db, "MAIN_SHOW_BUGTRACK_LINK", $_POST["main_show_bugtrack_link"]);
	dolibarr_set_const($db, "MAIN_SHOW_WORKBOARD",     $_POST["main_show_workboard"]);
	
	dolibarr_set_const($db, "MAIN_THEME",              $_POST["main_theme"]);
	
	dolibarr_set_const($db, "MAIN_SEARCHFORM_CONTACT", $_POST["main_searchform_contact"]);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_SOCIETE", $_POST["main_searchform_societe"]);
	dolibarr_set_const($db, "MAIN_SEARCHFORM_PRODUITSERVICE",$_POST["main_searchform_produitservice"]);
	
	dolibarr_set_const($db, "MAIN_MOTD",               $_POST["main_motd"]);
	dolibarr_set_const($db, "MAIN_HOME",               $_POST["main_home"]);
	
	$_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer
	
	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


llxHeader();

$html=new Form($db);

print_fiche_titre($langs->trans("GUISetup"),'','setup');

print $langs->trans("DisplayDesc")."<br>\n";
print "<br>\n";


if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
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
    $html->select_lang($conf->global->MAIN_LANG_DEFAULT,'main_lang_default',1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';


    // Taille max des listes
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMaxSizeList").'</td><td><input class="flat" name="main_size_liste_limit" size="4" value="' . $conf->global->MAIN_SIZE_LISTE_LIMIT . '"></td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("EnableMultilangInterface").'</td><td>';
    print $html->selectyesno('main_multilangs',$conf->global->MAIN_MULTILANGS,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';

    /*
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';
    print $html->selectyesno('main_show_bugtrack_link',$conf->global->MAIN_SHOW_BUGTRACK_LINK,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';
	*/

    // Desactivation javascript et ajax
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableJavascript").'</td><td>';
    print $html->selectyesno('main_disable_javascript',isset($conf->global->MAIN_DISABLE_JAVASCRIPT)?$conf->global->MAIN_DISABLE_JAVASCRIPT:0,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';
    
    // Confirmation par popup ajax
    if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
		$var=!$var;
	    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ConfirmAjax").'</td><td>';
	    print $html->selectyesno('main_confirm_ajax',isset($conf->global->MAIN_CONFIRM_AJAX)?$conf->global->MAIN_CONFIRM_AJAX:0,1);
	    print ' ('.$langs->trans("AvailableOnlyIfJavascriptAndAjaxNotDisabled").')';
	    print '</td>';
		print '<td width="20">'.$html->textwithhelp('',$langs->trans("FeatureDevelopment")).'</td>';
		print '</tr>';
	}
	
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

    // Activer onglet preview
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePreviewTabs").'</td><td>';
    print $html->selectyesno('main_use_preview_tabs',isset($conf->global->MAIN_USE_PREVIEW_TABS)?$conf->global->MAIN_USE_PREVIEW_TABS:1,1);
    print '</td>';
	print '<td width="20">&nbsp;</td>';
	print '</tr>';
    
    print '</table><br>';


    // Themes
    show_theme(1);
    print '<br>';


    // Liste des zone de recherche permanantes support�es
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td></tr>';
    $var=True;
    foreach ($searchform as $key => $value)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="35%">'.$searchformtitle[$key].'</td><td>';
        print $html->selectyesno($searchform[$key],$searchformconst[$key],1);
        print '</td></tr>';
    }
    print '</table>';
    print '<br>';


    // Message of the day
    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td>';
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


    // Message d'accueil'
	$var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageLogin").'</td><td>';
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

    /*
	$var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';   
    print yn($conf->global->MAIN_SHOW_BUGTRACK_LINK)."</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";
	*/

    // Disable javascript/ajax
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DisableJavascript").'</td><td>';   
    print yn($conf->global->MAIN_DISABLE_JAVASCRIPT)."</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";
    
    // Confirm ajax
    if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
	{
	    $var=!$var;
	    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("ConfirmAjax").'</td><td>';   
	    if ($conf->global->MAIN_DISABLE_JAVASCRIPT) print $langs->trans("No").' ('.$langs->trans("JavascriptDisabled").')';
	    else print yn(isset($conf->global->MAIN_CONFIRM_AJAX)?$conf->global->MAIN_CONFIRM_AJAX:0)."</td>";
		print '<td width="20">'.$html->textwithhelp('',$langs->trans("FeatureDevelopment")).'</td>';
		print "</tr>";
	}
	
    // Calendrier en popup
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePopupCalendar").'</td><td>';   
    if ($conf->global->MAIN_DISABLE_JAVASCRIPT) print $langs->trans("No").' ('.$langs->trans("JavascriptDisabled").')';
    else print ($conf->global->MAIN_POPUP_CALENDAR?$langs->trans("Yes"):$langs->trans("No"));
    print "</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    // Activer onglet preview
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("UsePreviewTabs").'</td><td>';
    print yn(isset($conf->global->MAIN_USE_PREVIEW_TABS)?$conf->global->MAIN_USE_PREVIEW_TABS:1)."</td>";
	print '<td width="20">&nbsp;</td>';
	print "</tr>";

    print '</table><br>';


    // Themes
    show_theme(0);
    print '<br>';


    // Liste des zone de recherche permanantes support�es
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td></tr>';
    $var=true;
    foreach ($searchform as $key => $value) {
        $var=!$var;
        print '<tr '.$bc[$var].'"><td width="35%">'.$searchformtitle[$key].'</td><td>' . ($searchformconst[$key]?$langs->trans("yes"):$langs->trans("no")) . '</td></tr>';
    }
    print '</table>';
    print '<br>';

	// Message of the day
    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td>';
    print nl2br($conf->global->MAIN_MOTD);
    print '</td></tr>';
    
	// Message login
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageLogin").'</td><td>';
    print nl2br($conf->global->MAIN_HOME);
    print '</td></tr>';
    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
	print '<br>';
}


function show_theme($edit=0) 
{
    global $conf,$langs,$dirtheme,$bc;
    
    $thumbsbyrow=6;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="'.$thumbsbyrow.'">'.$langs->trans("DefaultSkin").'</td></tr>';
    $var=true;

    $var=!$var;
    print '<tr '.$bc[$var].'><td colspan="2">';

    print '<table class="notopnoleftnoright" width="100%">';
    $handle=opendir($dirtheme);
    $i=0;
    while (($subdir = readdir($handle))!==false)
    {
        if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) <> '.'
        	&& substr($subdir, 0, 3) <> 'CVS' &&  ! eregi('common',$subdir))
        {
            if ($i % $thumbsbyrow == 0)
            {
                print '<tr '.$bc[$var].'>';
            }
            
            print '<td align="center">';
            $file=$dirtheme."/".$subdir."/thumb.png";
            if (! file_exists($file)) $file=$dirtheme."/common/nophoto.jpg";
            print '<table><tr><td>';
			if ($edit) print '<a href="'.$_SERVER["PHP_SELF"].($edit?'?action=edit&theme=':'?theme=').$subdir.'" style="font-weight: normal;" alt="'.$langs->trans("Preview").'">';
			if ($edit) 
			{
				if ($subdir == $conf->global->MAIN_THEME) $title=$langs->trans("ThemeCurrentlyActive");
				else $title=$langs->trans("ShowPreview");
			}
			print '<img src="'.$file.'" width="80" height="60" border="0" title="'.$title.'">';
			if ($edit) print '</a>';
			print '</td></tr><tr><td align="center">';
            if ($subdir == $conf->global->MAIN_THEME)
            {
                print '<input '.($edit?'':'disabled').' type="radio" '.$bc[$var].' style="border: 0px;" checked name="main_theme" value="'.$subdir.'"> <b>'.$subdir.'</b>';
            }
            else
            {
                print '<input '.($edit?'':'disabled').' type="radio" '.$bc[$var].' style="border: 0px;" name="main_theme" value="'.$subdir.'"> '.$subdir;
            }
            print '</td></tr></table></td>';

            $i++;

            if ($i % $thumbsbyrow == 0) print '</tr>';
        }
    }
    if ($i % $thumbsbyrow != 0) {
        while ($i % $thumbsbyrow != 0) {
            print '<td>&nbsp;</td>';
            $i++;
        }
        print '</tr>';
    }    
    print '</table>';

    print '</td></tr>';
    print '</table>';
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
