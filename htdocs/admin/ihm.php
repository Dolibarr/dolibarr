<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!
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


// Liste des zone de recherche permanantes supportées
$searchform=array("main_searchform_societe","main_searchform_contact","main_searchform_produitservice");
$searchformconst=array(MAIN_SEARCHFORM_SOCIETE,MAIN_SEARCHFORM_CONTACT,MAIN_SEARCHFORM_PRODUITSERVICE);
$searchformtitle=array($langs->trans("Companies"),$langs->trans("Contacts"),$langs->trans("ProductsAndServices"));



if ($_POST["action"] == 'update')
{
  dolibarr_set_const($db, "MAIN_THEME",             $_POST["main_theme"]);
  dolibarr_set_const($db, "SIZE_LISTE_LIMIT",       $_POST["size_liste_limit"]);
  dolibarr_set_const($db, "MAIN_MENU_BARRETOP",     $_POST["main_menu_barretop"]);
  dolibarr_set_const($db, "MAIN_LANG_DEFAULT",      $_POST["main_lang_default"]);
  dolibarr_set_const($db, "MAIN_MOTD",              trim($_POST["main_motd"]));

  dolibarr_set_const($db, "MAIN_SEARCHFORM_CONTACT",$_POST["main_searchform_contact"]);
  dolibarr_set_const($db, "MAIN_SEARCHFORM_SOCIETE",$_POST["main_searchform_societe"]);
  dolibarr_set_const($db, "MAIN_SEARCHFORM_PRODUITSERVICE",$_POST["main_searchform_produitservice"]);

  Header("Location: ihm.php");
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

  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

  print '<tr class="impair"><td>Thème</td>';
  print '<td><select name="main_theme">';
  clearstatcache();
  $dir = "../theme/";
  $handle=opendir($dir);
  while (($file = readdir($handle))!==false)
    {
      if (is_dir($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
	{
	  if ($file == MAIN_THEME)
	    {
	      print '<option value="'.$file.'" selected>'.$file;
	    }
	  else
	    {
	      print '<option value="'.$file.'">'.$file;
	    }
	}
      
    }
  print '</select>';
  print '<input type="hidden" name="action" value="update">';
  print '</td></tr>';

  print '<tr class="pair"><td width="50%">Longueur maximum des listes</td><td><input name="size_liste_limit" size="20" value="' . SIZE_LISTE_LIMIT . '"></td></tr>';

  print '<tr class="impair"><td width="50%">Gestionnaire du menu du haut</td>';
  print '<td><select name="main_menu_barretop">';
  $dir = "../includes/menus/barre_top/";
  $handle=opendir($dir);
  while (($file = readdir($handle))!==false)
    {
      if (is_file($dir.$file) && substr($file, 0, 1) <> '.' && substr($file, 0, 3) <> 'CVS')
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

  print '<tr class="pair"><td width="50%">Langue par défaut à utiliser (code langue)</td><td>';
  $html=new Form($db);
  $html->select_lang(MAIN_LANG_DEFAULT,'main_lang_default');
  print '</td></tr>';
  print '<tr class="impair"><td width="50%">Message du jour</td><td><textarea cols="40" rows="3" name="main_motd" size="20">' .stripslashes(MAIN_MOTD) . '</textarea></td></tr>';

  print '</table><br>';

  // Liste des zone de recherche permanantes supportées
  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>Zone de recherche permanante du menu de gauche</td><td>'.$langs->trans("Active").'</td></tr>';
  $var=True;
  foreach ($searchform as $key => $value) {
    $var=!$var;
    print '<tr '.$bc[$var].'"><td>'.$searchformtitle[$key].'</td><td>';
    $html->selectyesnonum($searchform[$key],$searchformconst[$key]);
    print '</td></tr>';
  }
  print '</table><br>';

  print '<div class="tabsAction">';
  print '<input class="tabAction" type="submit" value="'.$langs->trans("Save").'">';
  print '</div>';

  print '</form>';
}
else
{

  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';
  print '<tr class="impair"><td width="50%">Thème</td><td>' . MAIN_THEME . '</td></tr>';
  print '<tr class="pair"><td>Longueur maximum des listes</td><td>' . SIZE_LISTE_LIMIT . '</td></tr>';
  print '<tr class="impair"><td width="50%">Gestionnaire du menu du haut</td><td>';
  $filelib=eregi_replace('\.php$','',MAIN_MENU_BARRETOP);
  print $filelib;
  print '</td></tr>';
  print '<tr class="pair"><td width="50%">Langue par défaut à utiliser (code langue)</td><td>' . MAIN_LANG_DEFAULT . '</td></tr>';

  print '<tr class="impair"><td width="50%">Message du jour</td><td>' . stripslashes(nl2br(MAIN_MOTD)) . '</td></tr>';

  print '</table><br>';

  // Liste des zone de recherche permanantes supportées
  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>Zone de recherche permanante du menu de gauche</td><td>'.$langs->trans("Active").'</td></tr>';
  $var=True;
  foreach ($searchform as $key => $value) {
    $var=!$var;
    print '<tr '.$bc[$var].'"><td>'.$searchformtitle[$key].'</td><td>' . ($searchformconst[$key]?"oui":"non") . '</td></tr>';
  }
  print '</table><br>';

  print '<div class="tabsAction">';
  print '<a class="tabAction" href="ihm.php?action=edit">'.$langs->trans("Edit").'</a>';
  print '</div>';

}


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
