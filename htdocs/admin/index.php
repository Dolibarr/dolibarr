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
    	\file       htdocs/admin/index.php
		\brief      Page d'accueil de l'espace administration/configuration
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("admin");
$langs->load("companies");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'update')
{
  dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM",$_POST["nom"]);
  dolibarr_set_const($db, "MAIN_INFO_SOCIETE_PAYS",$_POST["pays_id"]);
  dolibarr_set_const($db, "MAIN_MONNAIE",$_POST["currency"]);
  dolibarr_set_const($db, "MAIN_INFO_CAPITAL",$_POST["capital"]);

  dolibarr_set_const($db, "MAIN_INFO_SIRET",$_POST["siret"]);
  dolibarr_set_const($db, "MAIN_INFO_SIREN",$_POST["siren"]);
  dolibarr_set_const($db, "MAIN_INFO_APE",$_POST["ape"]);
  dolibarr_set_const($db, "MAIN_INFO_TVAINTRA",$_POST["tva"]);

  Header("Location: index.php");
}


llxHeader();
$form = new Form($db);

print_titre($langs->trans("GlobalSetup"));


print "<br>\n";

if ($_GET["action"] == 'edit')
{
  /*
   * Edition des paramètres
   */
  print '<form method="post" action="index.php">';
  print '<input type="hidden" name="action" value="update">';

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("CompanyInfo").'</td></tr>';

  print '<tr class="impair"><td>'.$langs->trans("CompanyName").'</td><td>';
  print '<input name="nom" value="'. MAIN_INFO_SOCIETE_NOM . '"></td></tr>';

  print '<tr class="pair"><td>'.$langs->trans("Country").'</td><td>';
  $form->select_pays(MAIN_INFO_SOCIETE_PAYS);
  print '</td></tr>';

  print '<tr class="impair"><td>'.$langs->trans("CompanyCurrency").'</td><td>';
  print '<input name="currency" value="'. MAIN_MONNAIE . '"></td></tr>';

  print '<tr class="pair"><td width="50%">'.$langs->trans("Capital").'</td><td>';
  print '<input name="capital" size="20" value="' . MAIN_INFO_CAPITAL . '"></td></tr>';

  print '</table>';

  print '<br>';
  
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';

  $langs->load("companies");

  // Recupere code pays
  $code_pays=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
  $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
  $sql .= " WHERE rowid = ".MAIN_INFO_SOCIETE_PAYS;
  $result=$db->query($sql);
  if ($result) {
    $obj = $db->fetch_object();
    if ($obj->code) $code_pays=$obj->code;
  }
  else {
    dolibarr_print_error($db);
  }
  
  if ($langs->transcountry("ProfId1",$code_pays) != '-')
  {
      print '<tr class="impair"><td width="50%">'.$langs->transcountry("ProfId1",$code_pays).'</td><td>';
      print '<input name="siret" size="20" value="' . MAIN_INFO_SIRET . '"></td></tr>';
  }
  
  if ($langs->transcountry("ProfId2",$code_pays) != '-')
  {
      print '<tr class="pair"><td width="50%">'.$langs->transcountry("ProfId2",$code_pays).'</td><td>';
      print '<input name="siren" size="20" value="' . MAIN_INFO_SIREN . '"></td></tr>';
  }

  if ($langs->transcountry("ProfId3",$code_pays) != '-')
  {
      print '<tr class="impair"><td width="50%">'.$langs->transcountry("ProfId3",$code_pays).'</td><td>';
      print '<input name="ape" size="20" value="' . MAIN_INFO_APE . '"></td></tr>';
  }
  
  print '<tr class="pair"><td width="50%">'.$langs->trans("TVAIntra").'</td><td>';
  print '<input name="tva" size="20" value="' . MAIN_INFO_TVAINTRA . '"></td></tr>';

  print '</table>';

  print '<br><center><input type="submit" value="'.$langs->trans("Save").'"></center>';

  print '</form>';
}
else
{
  /*
   * Affichage des paramètres
   */

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("CompanyInfo").'</td><td>'.$langs->trans("Value").'</td></tr>';

  print '<tr class="impair"><td width="50%">'.$langs->trans("CompanyName").'</td><td>' . MAIN_INFO_SOCIETE_NOM . '</td></tr>';

  print '<tr class="pair"><td>'.$langs->trans("Country").'</td><td>';
  print $form->pays_name(MAIN_INFO_SOCIETE_PAYS);
  print '</td></tr>';

  print '<tr class="impair"><td width="50%">'.$langs->trans("CompanyCurrency").'</td><td>' . MAIN_MONNAIE . '</td></tr>';

  print '<tr class="pair"><td width="50%">'.$langs->trans("Capital").'</td><td>';
  print MAIN_INFO_CAPITAL . '</td></tr>';

  print '</table>';

  print '<br>';
  
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("CompanyIds").'</td><td>'.$langs->trans("Value").'</td></tr>';

  // Recupere code pays
  $code_pays=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
  $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
  $sql .= " WHERE rowid = ".MAIN_INFO_SOCIETE_PAYS;
  $result=$db->query($sql);
  if ($result) {
    $obj = $db->fetch_object();
    if ($obj->code) $code_pays=$obj->code;
  }
  else {
    dolibarr_print_error($db);
  }
  
  if ($langs->transcountry("ProfId1",$code_pays) != '-')
  {
    print '<tr class="impair"><td width="50%">'.$langs->transcountry("ProfId1",$code_pays).'</td><td>';
    print MAIN_INFO_SIRET . '</td></tr>';
  }
  
  if ($langs->transcountry("ProfId2",$code_pays) != '-')
  {
    print '<tr class="pair"><td width="50%">'.$langs->transcountry("ProfId2",$code_pays).'</td><td>';
    print MAIN_INFO_SIREN . '</td></tr>';
  }
  
  if ($langs->transcountry("ProfId3",$code_pays) != '-')
  {
    print '<tr class="impair"><td width="50%">'.$langs->transcountry("ProfId3",$code_pays).'</td><td>';
    print MAIN_INFO_APE . '</td></tr>';
  }
  
  print '<tr class="pair"><td>'.$langs->trans("TVAIntra").'</td><td>' . MAIN_INFO_TVAINTRA . '</td></tr>';

  print '</table><br>';

  // Boutons d'action
  print '<div class="tabsAction">';
  print '<a class="tabAction" href="index.php?action=edit">'.$langs->trans("Edit").'</a>';
  print '</div>';


}


llxFooter('$Date$ - $Revision$');

?>
