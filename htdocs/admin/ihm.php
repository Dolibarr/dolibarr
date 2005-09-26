<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */

/**
        \file       htdocs/admin/ihm.php
        \brief      Page de configuration du de l'interface homme machine
        \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");

if (!$user->admin)
  accessforbidden();

if (! defined("MAIN_MOTD")) define("MAIN_MOTD","");

$dirtop = "../includes/menus/barre_top";
$dirleft = "../includes/menus/barre_left";
$dirtheme = "../theme";

// Liste des zone de recherche permanantes supportées
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));


if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
  dolibarr_set_const($db, "MAIN_LANG_DEFAULT",       $_POST["main_lang_default"]);
  dolibarr_set_const($db, "SIZE_LISTE_LIMIT",        $_POST["size_liste_limit"]);
  dolibarr_set_const($db, "MAIN_DISABLE_JAVASCRIPT", $_POST["disable_javascript"]);
  
  dolibarr_set_const($db, "MAIN_SHOW_BUGTRACK_LINK", $_POST["bugtrack"]);
  dolibarr_set_const($db, "MAIN_SHOW_WORKBOARD", $_POST["workboard"]);

  dolibarr_set_const($db, "MAIN_MENU_BARRETOP",      $_POST["main_menu_barretop"]);
  dolibarr_set_const($db, "MAIN_MENU_BARRELEFT",     $_POST["main_menu_barreleft"]);

  dolibarr_set_const($db, "MAIN_MENUFRONT_BARRETOP",      $_POST["main_menufront_barretop"]);
  dolibarr_set_const($db, "MAIN_MENUFRONT_BARRELEFT",     $_POST["main_menufront_barreleft"]);

  dolibarr_set_const($db, "MAIN_THEME",             $_POST["main_theme"]);
  
  dolibarr_set_const($db, "MAIN_SEARCHFORM_CONTACT",$_POST["main_searchform_contact"]);
  dolibarr_set_const($db, "MAIN_SEARCHFORM_SOCIETE",$_POST["main_searchform_societe"]);
  dolibarr_set_const($db, "MAIN_SEARCHFORM_PRODUITSERVICE",$_POST["main_searchform_produitservice"]);
  
  dolibarr_set_const($db, "MAIN_MOTD",              trim($_POST["main_motd"]));
  
  $_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer
  
  Header("Location: ihm.php?mainmenu=home&leftmenu=setup");
}


llxHeader();

print_titre($langs->trans("GUISetup"));

print "<br>\n";

if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
    print '<form method="post" action="ihm.php">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // Langue par defaut
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DefaultLanguage").'</td><td>';
    $html=new Form($db);
    $html->select_lang($conf->global->MAIN_LANG_DEFAULT,'main_lang_default');
    print '</td></tr>';


    // Taille max des listes
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMaxSizeList").'</td><td><input class="flat" name="size_liste_limit" size="4" value="' . SIZE_LISTE_LIMIT . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';
    $html->selectyesnonum('bugtrack',$conf->global->MAIN_SHOW_BUGTRACK_LINK);
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowWorkBoard").'</td><td>';
    $html->selectyesnonum('workboard',$conf->global->MAIN_SHOW_WORKBOARD);
    print '</td></tr>';

    // Désactiver javascript
    $var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("DisableJavascript").'</td><td>';
    $html->selectyesnonum('disable_javascript',$conf->global->MAIN_DISABLE_JAVASCRIPT);
    print '</td></tr>';

    print '</table><br>';


    // Gestionnaires de menu
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Menu").'</td>';
    print '<td>'.$langs->trans("InternalUsers").'</td>';
    print '<td>'.$langs->trans("ExternalUsers").'</td>';
    print '</tr>';

    // Menu top
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuTopManager").'</td>';
    print '<td>';
    print $html->select_menu($conf->global->MAIN_MENU_BARRETOP,'main_menu_barretop',$dirtop);
    print '</td>';
    print '<td>';
    print $html->select_menu($conf->global->MAIN_MENUFRONT_BARRETOP,'main_menufront_barretop',$dirtop);
    print '</td>';
    print '</tr>';

    // Menu left
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuLeftManager").'</td>';
    print '<td>';
    print $html->select_menu($conf->global->MAIN_MENU_BARRELEFT,'main_menu_barreleft',$dirleft);
    print '</td>';
    print '<td>';
    print $html->select_menu($conf->global->MAIN_MENUFRONT_BARRELEFT,'main_menufront_barreleft',$dirleft);
    print '</td>';
    print '</tr>';

    print '</table><br>';


    // Themes
    show_theme(1);
    print '<br>';


    // Liste des zone de recherche permanantes supportées
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td></tr>';
    $var=True;
    foreach ($searchform as $key => $value)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'"><td width="35%">'.$searchformtitle[$key].'</td><td>';
        $html->selectyesnonum($searchform[$key],$searchformconst[$key]);
        print '</td></tr>';
    }
    print '</table>';
    print '<br>';


    // Message of the day
    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td><textarea cols="60" rows="3" name="main_motd" size="20">' . stripslashes($conf->global->MAIN_MOTD) . '</textarea></td></tr>';
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
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("DefaultLanguage").'</td><td>' . $conf->global->MAIN_LANG_DEFAULT . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMaxSizeList").'</td><td>' . $conf->global->SIZE_LISTE_LIMIT . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowBugTrackLink").'</td><td>';   
    print ($conf->global->MAIN_SHOW_BUGTRACK_LINK?$langs->trans("yes"):$langs->trans("no"))."</td></tr>";

    $var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("ShowWorkBoard").'</td><td>';   
    print ($conf->global->MAIN_SHOW_WORKBOARD?$langs->trans("yes"):$langs->trans("no"))."</td></tr>";

    // Disable javascript
    $var=!$var;
    print '<tr '.$bc[$var].'"><td width="35%">'.$langs->trans("DisableJavascript").'</td><td>';   
    print ($conf->global->MAIN_DISABLE_JAVASCRIPT?$langs->trans("yes"):$langs->trans("no"))."</td></tr>";

    print '</table><br>';


    // Gestionnaires de menu
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="35%">'.$langs->trans("Menu").'</td>';
    print '<td>'.$langs->trans("InternalUsers").'</td>';
    print '<td>'.$langs->trans("ExternalUsers").'</td>';
    print '</tr>';
    
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultMenuTopManager").'</td>';
    print '<td>';
    $filelib=eregi_replace('\.php$','',$conf->global->MAIN_MENU_BARRETOP);
    print $filelib;
    print '</td>';
    print '<td>';
    $filelib=eregi_replace('\.php$','',$conf->global->MAIN_MENUFRONT_BARRETOP);
    print $filelib;
    print '</td>';
    print '</tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'>';
    print '<td>'.$langs->trans("DefaultMenuLeftManager").'</td>';
    print '<td>';
    $filelib=eregi_replace('\.php$','',$conf->global->MAIN_MENU_BARRELEFT);
    print $filelib;
    print '</td>';
    print '<td>';
    $filelib=eregi_replace('\.php$','',$conf->global->MAIN_MENUFRONT_BARRELEFT);
    print $filelib;
    print '</td>';
    print '</tr>';

    print '</table><br>';


    // Themes
    show_theme(0);
    print '<br>';


    // Liste des zone de recherche permanantes supportées
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td></tr>';
    $var=true;
    foreach ($searchform as $key => $value) {
        $var=!$var;
        print '<tr '.$bc[$var].'"><td width="35%">'.$searchformtitle[$key].'</td><td>' . ($searchformconst[$key]?$langs->trans("yes"):$langs->trans("no")) . '</td></tr>';
    }
    print '</table>';
    print '<br>';

    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="35%">'.$langs->trans("MessageOfDay").'</td><td>' . stripslashes(nl2br($conf->global->MAIN_MOTD)) . '</td></tr>';
    print '</table><br>';

    print '<div class="tabsAction">';
    print '<a class="tabAction" href="ihm.php?action=edit">'.$langs->trans("Edit").'</a>';
    print '</div>';

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
        if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) <> '.' && substr($subdir, 0, 3) <> 'CVS')
        {
            if ($i % $thumbsbyrow == 0)
            {
                print '<tr '.$bc[$var].'>';
            }
            
            print '<td align="center">';
            $file=$dirtheme."/".$subdir."/thumb.png";
            if (! file_exists($file)) $file=$dirtheme."/nophoto.jpg";
            print '<table><tr><td><img src="'.$file.'" width="80" height="60"></td></tr><tr><td align="center">';
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

llxFooter('$Date$ - $Revision$');
?>
