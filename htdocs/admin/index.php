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
require("./pre.inc.php");

if (!$user->admin)
  accessforbidden();


if ($_POST["action"] == 'update')
{
  dolibarr_set_const($db, "MAIN_INFO_SOCIETE_NOM",$_POST["nom"]);
  dolibarr_set_const($db, "MAIN_INFO_SOCIETE_PAYS",$_POST["pays_id"]);
  dolibarr_set_const($db, "MAIN_INFO_TVAINTRA",$_POST["tva"]);
  dolibarr_set_const($db, "MAIN_INFO_CAPITAL",$_POST["capital"]);
  dolibarr_set_const($db, "MAIN_INFO_SIREN",$_POST["siren"]);
  dolibarr_set_const($db, "MAIN_INFO_SIRET",$_POST["siret"]);
  dolibarr_set_const($db, "MAIN_INFO_RCS",$_POST["rcs"]);

  Header("Location: index.php");
}


llxHeader();
$form = new Form($db);

print_titre("Configuration générale (Dolibarr version ".DOL_VERSION.")");

print "<br>\n";

if ($_GET["action"] == 'edit')
{
  print '<form method="post" action="index.php">';
  print '<input type="hidden" name="action" value="update">';

  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td colspan="2">Informations sur la société ou association</td></tr>';

  print '<tr class="impair"><td>Nom de la société/association</td><td>';
  print '<input name="nom" value="'. MAIN_INFO_SOCIETE_NOM . '"></td></tr>';

  print '<tr class="pair"><td>Pays de la société</td><td>';
  print $form->select_pays(MAIN_INFO_SOCIETE_PAYS);
  print '</td></tr>';

  print '<tr class="impair"><td width="50%">Numéro de TVA intracommunautaire</td><td>';
  print '<input name="tva" size="20" value="' . MAIN_INFO_TVAINTRA . '"></td></tr>';

  print '<tr class="pair"><td width="50%">Capital</td><td>';
  print '<input name="capital" size="20" value="' . MAIN_INFO_CAPITAL . '"></td></tr>';

  print '<tr class="impair"><td width="50%">Identifiant professionnel (SIREN,SIRET,...)</td><td>';
  print '<input name="siren" size="20" value="' . MAIN_INFO_SIREN . '"></td></tr>';

  print '<tr class="pair"><td width="50%">Code de l\'activité économique</td><td>';
  print '<input name="rcs" size="20" value="' . MAIN_INFO_APE . '"></td></tr>';

  print '<tr><td colspan="2" align="center">';
  print '<input type="submit" value="Enregistrer"></td></tr>';
  print '</table></form>';
}
else
{

  print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
  print '<tr class="liste_titre"><td>Informations sur la société/association</td><td>Valeur</td></tr>';
  print '<tr class="impair"><td width="50%">Nom de la société/association</td><td>' . MAIN_INFO_SOCIETE_NOM . '</td></tr>';

  print '<tr class="pair"><td>Pays de la société</td><td>';
  print $form->pays_name(MAIN_INFO_SOCIETE_PAYS);
  print '</td></tr>';

  print '<tr class="impair"><td>Numéro de TVA intracommunautaire</td><td>' . MAIN_INFO_TVAINTRA . '</td></tr>';

  print '<tr class="pair"><td width="50%">Capital</td><td>';
  print MAIN_INFO_CAPITAL . '</td></tr>';

  print '<tr class="impair"><td width="50%">Identifiant professionnel (SIREN,...)</td><td>';
  print MAIN_INFO_SIREN . '</td></tr>';

  print '<tr class="pair"><td width="50%">RCS</td><td>';
  print MAIN_INFO_RCS . '</td></tr>';


  print '</table><br>';

  // Boutons d'action
  print '<div class="tabsAction">';
  print '<a class="tabAction" href="index.php?action=edit">Editer</a>';
  print '</div>';


}


llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
