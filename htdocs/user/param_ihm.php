<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/user/param_ihm.php
        \brief      Onglet parametrage de la fiche utilisateur
        \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("users");

// Defini si peux lire/modifier permisssions
$canreadperms=($user->admin || $user->rights->user->user->lire);

if ($_GET["id"])
{
  // $user est le user qui edite, $_GET["id"] est l'id de l'utilisateur edité
  $caneditfield=( (($user->id == $_GET["id"]) && $user->rights->user->self->creer)
		  || (($user->id != $_GET["id"]) && $user->rights->user->user->creer));
}
if ($user->id <> $_GET["id"] && ! $canreadperms)
{
  accessforbidden();
}

$id=isset($_GET["id"])?$_GET["id"]:$_POST["id"];
$dirtop = "../includes/menus/barre_top";
$dirleft = "../includes/menus/barre_left";
$dirtheme = "../theme";

// Charge utilisateur edité
$fuser = new User($db, $id);
$fuser->fetch();
$fuser->getrights();

// Liste des zone de recherche permanantes supportées
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array($conf->global->MAIN_SEARCHFORM_SOCIETE,$conf->global->MAIN_SEARCHFORM_CONTACT,$conf->global->MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));

$html = new Form($db);


/*
 * Actions
 */
if ($_POST["action"] == 'update' && ($caneditfield  || $user->admin))
{
    if ($_POST["cancel"])
    {
        $_GET["id"]=$_POST["id"];
    }
    else 
    {
        $tabparam=array();
        
        if ($_POST["check_MAIN_LANG_DEFAULT"]=="on") $tabparam["MAIN_LANG_DEFAULT"]=$_POST["main_lang_default"];
        else $tabparam["MAIN_LANG_DEFAULT"]='';
        
        $tabparam["MAIN_MENU_BARRETOP"]=$_POST["main_menu_barretop"];
        $tabparam["MAIN_MENU_BARRELEFT"]=$_POST["main_menu_barreleft"];
    
        if ($_POST["check_SIZE_LISTE_LIMIT"]=="on") $tabparam["MAIN_SIZE_LISTE_LIMIT"]=$_POST["main_size_liste_limit"];
        else $tabparam["MAIN_SIZE_LISTE_LIMIT"]='';
    
        if ($_POST["check_MAIN_THEME"]=="on") $tabparam["MAIN_THEME"]=$_POST["main_theme"];
        else $tabparam["MAIN_THEME"]='';

        $tabparam["MAIN_SEARCHFORM_CONTACT"]=$_POST["main_searchform_contact"];
        $tabparam["MAIN_SEARCHFORM_SOCIETE"]=$_POST["main_searchform_societe"];
        $tabparam["MAIN_SEARCHFORM_PRODUITSERVICE"]=$_POST["main_searchform_produitservice"];
    
        dolibarr_set_user_page_param($db, $fuser, '', $tabparam);

        $_SESSION["mainmenu"]="";   // Le gestionnaire de menu a pu changer
    
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$_POST["id"]);
        exit;
    }
}



llxHeader();


/*
 * Affichage onglets
 */
$head = user_prepare_head($fuser);

dolibarr_fiche_head($head, 'guisetup', $langs->trans("User"));


print '<table class="border" width="100%">';

// Ref
print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
print '<td colspan="2">';
print $html->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
print '</td>';
print '</tr>';

// Nom
print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
print '<td colspan="2">'.$fuser->nom.'</td>';
print "</tr>\n";

// Prenom
print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
print '<td colspan="2">'.$fuser->prenom.'</td>';
print "</tr>\n";

print '</table><br>';


if ($_GET["action"] == 'edit')
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="update">';
    print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

    clearstatcache();
    $var=true;
    
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

    // Langue par defaut
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Language").'</td>';
    print '<td>'.($conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$conf->global->MAIN_LANG_DEFAULT).'</td>';
    print '<td align="left" nowrap="nowrap" width="20%"><input name="check_MAIN_LANG_DEFAULT" type="checkbox" '.($fuser->conf->MAIN_LANG_DEFAULT?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>';
    $html=new Form($db);
    $html->select_lang($fuser->conf->MAIN_LANG_DEFAULT,'main_lang_default',1);
    print '</td></tr>';

    // Taille max des listes
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MaxSizeList").'</td>';
    print '<td>'.$conf->global->MAIN_SIZE_LISTE_LIMIT.'</td>';
    print '<td align="left" nowrap="nowrap" width="20%"><input name="check_SIZE_LISTE_LIMIT" type="checkbox" '.($fuser->conf->MAIN_SIZE_LISTE_LIMIT?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td><input class="flat" name="main_size_liste_limit" size="4" value="' . $fuser->conf->SIZE_LISTE_LIMIT . '"></td></tr>';

    print '</table><br>';


    // Theme
    show_theme($fuser,1);

    print '</div>';

    print '<center>';
    print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
    print ' &nbsp; &nbsp; ';
    print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
    print '</center>';
    print '</form>';
    
}
else
{
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td>&nbsp;</td><td>'.$langs->trans("PersonalValue").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("Language").'</td>';
    print '<td>'.($conf->global->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$conf->global->MAIN_LANG_DEFAULT).'</td>';
    print '<td align="left" nowrap="nowrap" width="20%"><input type="checkbox" disabled '.($fuser->conf->MAIN_LANG_DEFAULT?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>'.($fuser->conf->MAIN_LANG_DEFAULT=='auto'?$langs->trans("AutoDetectLang"):$fuser->conf->MAIN_LANG_DEFAULT).'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MaxSizeList").'</td>';
    print '<td>'.$conf->global->MAIN_SIZE_LISTE_LIMIT.'</td>';
    print '<td align="left" nowrap="nowrap" width="20%"><input type="checkbox" disabled '.($fuser->conf->MAIN_SIZE_LISTE_LIMIT?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td>' . $fuser->conf->MAIN_SIZE_LISTE_LIMIT . '</td></tr>';

    print '</table><br>';


    // Skin
    show_theme($fuser,0);

    print '</div>';

    print '<div class="tabsAction">';
    if ($caneditfield  || $user->admin)       // Si utilisateur édité = utilisateur courant ayant les droits de créer ou admin
    {
        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$_GET["id"].'">'.$langs->trans("Edit").'</a>';
    }
    print '</div>';

}

$db->close();

llxFooter('$Date$ - $Revision$');


function show_theme($fuser,$edit=0) 
{
    global $conf,$langs,$dirtheme,$bc;
    
    $thumbsbyrow=6;
    
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Parameter").'</td><td width="25%">'.$langs->trans("DefaultValue").'</td><td colspan="2">&nbsp;</td></tr>';

    $var=false;

    print '<tr '.$bc[$var].'><td>'.$langs->trans("DefaultSkin").'</td>';
    print '<td>'.$conf->global->MAIN_THEME.'</td>';
    print '<td '.$bc[$var].' align="left" nowrap="nowrap" width="20%"><input name="check_MAIN_THEME"'.($edit?'':' disabled').' type="checkbox" '.($fuser->conf->MAIN_THEME?" checked":"").'> '.$langs->trans("UsePersonalValue").'</td>';
    print '<td '.$bc[$var].'>&nbsp;</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td colspan="4">';

    print '<table class="notopnoleftnoright" width="100%">';
    $handle=opendir($dirtheme);
    $i=0;
    while (($subdir = readdir($handle))!==false)
    {
        if (is_dir($dirtheme."/".$subdir) && substr($subdir, 0, 1) <> '.'
        	&& substr($subdir, 0, 3) <> 'CVS' && ! eregi('common',$subdir))
        {
            if ($i % $thumbsbyrow == 0)
            {
                print '<tr '.$bc[$var].'>';
            }
            
            print '<td align="center">';
            $file=$dirtheme."/".$subdir."/thumb.png";
            if (! file_exists($file)) $file=$dirtheme."/common/nophoto.jpg";
            print '<table><tr><td><img src="'.$file.'" width="80" height="60"></td></tr><tr><td align="center">';
            if ($subdir == $fuser->conf->MAIN_THEME)
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

?>
