<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


$dirtop = "../includes/menus/barre_top";
$dirleft = "../includes/menus/barre_left";
$dirtheme = "../theme";

// Liste des zone de recherche permanantes supportées
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array(MAIN_SEARCHFORM_SOCIETE,MAIN_SEARCHFORM_CONTACT,MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));



if ($_POST["action"] == 'update')
{
    dolibarr_set_const($db, "MAIN_LANG_DEFAULT",      $_POST["main_lang_default"]);
    dolibarr_set_const($db, "MAIN_MENU_BARRETOP",     $_POST["main_menu_barretop"]);
    dolibarr_set_const($db, "MAIN_MENU_BARRELEFT",    $_POST["main_menu_barreleft"]);
    dolibarr_set_const($db, "MAIN_THEME",             $_POST["main_theme"]);

    dolibarr_set_const($db, "SIZE_LISTE_LIMIT",       $_POST["size_liste_limit"]);
    dolibarr_set_const($db, "MAIN_MOTD",              trim($_POST["main_motd"]));

    dolibarr_set_const($db, "MAIN_SEARCHFORM_CONTACT",$_POST["main_searchform_contact"]);
    dolibarr_set_const($db, "MAIN_SEARCHFORM_SOCIETE",$_POST["main_searchform_societe"]);
    dolibarr_set_const($db, "MAIN_SEARCHFORM_PRODUITSERVICE",$_POST["main_searchform_produitservice"]);

    $_SESSION["mainmenu"]="";

    Header("Location: ihm.php?mainmenu=home&leftmenu=setup");
}


llxHeader();

if (!defined("MAIN_MOTD") && strlen(trim(MAIN_MOTD)))
{
    define("MAIN_MOTD","");
}

print_titre($langs->trans("GUISetup"));

print "<br>\n";



if ($_GET["action"] == 'edit')
{
    print '<form method="post" action="ihm.php">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;
    
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    // Langue par defaut
    $var=!$var;
    print '<tr '.$bc[$var].'><td width="30%">'.$langs->trans("DefaultLanguage").'</td><td>';
    $html=new Form($db);
    $html->select_lang(MAIN_LANG_DEFAULT,'main_lang_default');
    print '</td></tr>';

    // Menu top
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MenuTopManager").'</td>';
    print '<td><select class="flat" name="main_menu_barretop">';
    $handle=opendir($dirtop);
    while (($file = readdir($handle))!==false)
    {
        if (is_file($dirtop."/".$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
        {
            $filelib=eregi_replace('\.php$','',$file);
            if ($file == MAIN_MENU_BARRETOP)
            {
                print '<option value="'.$file.'" selected>'.$filelib.'</option>';
            }
            else
            {
                print '<option value="'.$file.'">'.$filelib.'</option>';
            }
        }

    }
    print '</select>';
    print '</td></tr>';

    // Menu left
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MenuLeftManager").'</td>';
    print '<td><select class="flat" name="main_menu_barreleft">';
    $handle=opendir($dirleft);
    while (($file = readdir($handle))!==false)
    {
        if (is_file($dirleft."/".$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
        {
            $filelib=eregi_replace('\.php$','',$file);
            if ($file == MAIN_MENU_BARRELEFT)
            {
                print '<option value="'.$file.'" selected>'.$filelib.'</option>';
            }
            else
            {
                print '<option value="'.$file.'">'.$filelib.'</option>';
            }
        }

    }
    print '</select>';
    print '</td></tr>';

    // Taille max des listes
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MaxSizeList").'</td><td><input class="flat" name="size_liste_limit" size="4" value="' . SIZE_LISTE_LIMIT . '"></td></tr>';

    // Message of the day
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MessageOfDay").'</td><td><textarea cols="60" rows="3" name="main_motd" size="20">' .stripslashes(MAIN_MOTD) . '</textarea></td></tr>';

    print '</table><br>';


    // Theme
    show_theme(1);
    print '<br>';


    // Liste des zone de recherche permanantes supportées
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td></tr>';
    $var=True;
    foreach ($searchform as $key => $value) {
        $var=!$var;
        print '<tr '.$bc[$var].'"><td>'.$searchformtitle[$key].'</td><td>';
        $html->selectyesnonum($searchform[$key],$searchformconst[$key]);
        print '</td></tr>';
    }
    print '</table>';

    print '<div class="tabsAction">';
    print '<input class="tabAction" type="submit" value="'.$langs->trans("Save").'">';
    print '</div>';

    print '</form>';
}
else
{
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="30%">'.$langs->trans("DefaultLanguage").'</td><td>' . MAIN_LANG_DEFAULT . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MenuTopManager").'</td><td>';
    $filelib=eregi_replace('\.php$','',MAIN_MENU_BARRETOP);
    print $filelib;
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MenuLeftManager").'</td><td>';
    $filelib=eregi_replace('\.php$','',MAIN_MENU_BARRELEFT);
    print $filelib;
    print '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MaxSizeList").'</td><td>' . SIZE_LISTE_LIMIT . '</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MessageOfDay").'</td><td>' . stripslashes(nl2br(MAIN_MOTD)) . '</td></tr>';

    print '</table><br>';


    // Skin
    show_theme(0);
    print '<br>';

    // Liste des zone de recherche permanantes supportées
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("PermanentLeftSearchForm").'</td><td>'.$langs->trans("Activated").'</td></tr>';
    $var=True;
    foreach ($searchform as $key => $value) {
        $var=!$var;
        print '<tr '.$bc[$var].'"><td>'.$searchformtitle[$key].'</td><td>' . ($searchformconst[$key]?$langs->trans("yes"):$langs->trans("no")) . '</td></tr>';
    }
    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="tabAction" href="ihm.php?action=edit">'.$langs->trans("Edit").'</a>';
    print '</div>';

}


function show_theme($edit=0) 
{
    global $langs,$dirtheme,$bc;
    
    $nbofthumbs=4;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="'.$nbofthumbs.'">'.$langs->trans("Skin").'</td></tr>';

    $handle=opendir($dirtheme);
    $var=false;
    $i=0;
    while (($subdir = readdir($handle))!==false)
    {
        if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) <> '.' && substr($subdir, 0, 3) <> 'CVS')
        {
            if ($i % $nbofthumbs == 0) {
                print '<tr '.$bc[$var].'>';
            }
            
            print '<td align="center">';
            $file=$dirtheme."/".$subdir."/thumb.png";
            if (! file_exists($file)) $file=$dirtheme."/nophoto.jpg";
            print '<table><tr><td><img src="'.$file.'" width="80" height="60"></td></tr><tr><td align="center">';
            if ($subdir == MAIN_THEME)
            {
                print '<input '.($edit?'':'disabled').' type="radio" '.$bc[$var].' style="border: 0px;" checked name="main_theme" value="'.$subdir.'"> <b>'.$subdir.'</b>';
            }
            else
            {
                print '<input '.($edit?'':'disabled').' type="radio" '.$bc[$var].' style="border: 0px;" name="main_theme" value="'.$subdir.'"> '.$subdir;
            }
            print '</td></tr></table></td>';

            $i++;

            if ($i % $nbofthumbs == 0) print '</tr>';
        }
    }
    if ($i % $nbofthumbs != 0) {
        while ($i % $nbofthumbs != 0) {
            print '<td>&nbsp;</td>';
            $i++;
        }
        print '</tr>';
    }    

    print '</table>';
}

llxFooter('$Date$ - $Revision$');
?>
