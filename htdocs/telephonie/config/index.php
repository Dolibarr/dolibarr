<?PHP
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
    \file       htdocs/telephonie/config/index.php
    \ingroup    telephonie
    \brief      Page configuration telephonie
    \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin) accessforbidden();

if ($_GET["action"] == "set")
{
  for ($i = 1 ; $i < 5 ; $i++)
    {
      dolibarr_set_const($db, $_POST["nom$i"], $_POST["value$i"], $type='chaine');
    }

  Header("Location: index.php");
}

if ($_GET["action"] == "switch")
{
  dolibarr_set_const($db, $_GET["name"], $_GET["value"], $type='chaine');

  Header("Location: index.php");
}

/*
 *
 *
 *
 */
llxHeader('','T�l�phonie - Configuration');
print_titre("Configuration du module de T�l�phonie");

print "<br>";

/*
 *
 *
 */
print_titre("Emails");
print '<form method="post" action="index.php?action=set">';
print '<table class="noborder" cellpadding="3" cellspacing="0" width="100%">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td>&nbsp;</td><td>Constante</td>';
print "</tr>\n";


print '<tr class="pair"><td>';
print 'Marge minimale</td><td>';
print '<input type="hidden" name="nom1" value="TELEPHONIE_MARGE_MINI">';
print '<input type="text"   name="value1" value="'.TELEPHONIE_MARGE_MINI.'" size="3" >%';

print '</td><td><input type="submit" value="'.$langs->trans('Update').'"></td><td>TELEPHONIE_MARGE_MINI</td></tr>';

print '<tr class="pair"><td>';
print 'Compte de ventilation</td><td align="center">';
print TELEPHONIE_COMPTE_VENTILATION;
print '</td><td>-</td><td>TELEPHONIE_COMPTE_VENTILATION</td></tr>';

print '<tr class="pair"><td>';
print 'Email facturation FROM</td><td align="center">';
print TELEPHONIE_EMAIL_FACTURATION_EMAIL;
print '</td><td>-</td><td>TELEPHONIE_EMAIL_FACTURATION_EMAIL</td></tr>';


print '<tr class="impair"><td>';
print 'Email facturation BCC</td><td align="center">';
print TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC;
print '</td><td>-</td><td>TELEPHONIE_LIGNE_COMMANDE_EMAIL_BCC</td></tr>';

$form = new Form($db);

/* ***************************************** */

print '<tr class="pair"><td>Groupes des commerciaux</td>';
print '<td align="left">';

$ff = array();
$ff[0] = "Aucun groupe defini";
$sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."usergroup ORDER BY nom ASC;";
$resql = $db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $ff[$row[0]] = $row[1];
    }
  $db->free($resql);	
}
$form->select_array("value4",$ff,TELEPHONIE_GROUPE_COMMERCIAUX_ID);

print '</td><td><input type="submit" value="'.$langs->trans('Update').'">';
print '</td><td>TELEPHONIE_GROUPE_COMMERCIAUX_ID</td></tr>';
print '<input type="hidden" name="nom4" value="TELEPHONIE_GROUPE_COMMERCIAUX_ID">';

/* ***************************************** */


print '<tr class="pair"><td>Fournisseur par d�faut</td><td>';
$ff = array();
$sql = "SELECT rowid, nom FROM ".MAIN_DB_PREFIX."telephonie_fournisseur WHERE commande_active = 1 ORDER BY nom ";
$resql = $db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $ff[$row[0]] = $row[1];
    }
  $db->free($resql);	
}
$form->select_array("value2",$ff,TELEPHONIE_FOURNISSEUR_DEFAUT_ID);

print '</td><td><input type="submit" value="'.$langs->trans('Update').'">';
print '</td><td>TELEPHONIE_FOURNISSEUR_DEFAUT_ID</td></tr>';
print '<input type="hidden" name="nom2" value="TELEPHONIE_FOURNISSEUR_DEFAUT_ID">';

print '<tr class="pair"><td>Grille vente par d�faut</td>';

$gg = array();
$gg[0] = "Aucune grille definie";
$sql = "SELECT d.rowid,d.libelle";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= " WHERE d.type_tarif='vente'";
$sql .= " ORDER BY d.libelle";

$resql = $db->query($sql);
if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $gg[$row[0]] = $row[1];
    }
  $db->free($resql);	
}


print '<td>';

$form->select_array("value3",$gg,TELEPHONIE_GRILLE_VENTE_DEFAUT_ID);

print '</td><td><input type="submit" value="'.$langs->trans('Update').'"></td>';
print '<td>TELEPHONIE_GRILLE_VENTE_DEFAUT_ID</td></tr>';
print '<input type="hidden" name="nom3" value="TELEPHONIE_GRILLE_VENTE_DEFAUT_ID"></td></tr>';


print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td>&nbsp;</td><td>Constante</td>';
print "</tr>\n";

print '<tr class="pair"><td>Module ADSL</td>';
print '<td>';
if (TELEPHONIE_MODULE_ADSL == 1)
{
  print 'oui</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_ADSL&amp;value=0">Changer</a>';
}
else
{
  print 'non</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_ADSL&amp;value=1">Changer</a>';
}
print '</td><td>TELEPHONIE_MODULE_ADSL</td></tr>';


print '<tr class="pair"><td>Module SIMULATION</td>';
print '<td>';
if (TELEPHONIE_MODULE_SIMULATION == 1)
{
  print 'oui</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_SIMULATION&amp;value=0">Changer</a>';
}
else
{
  print 'non</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_SIMULATION&amp;value=1">Changer</a>';
}
print '</td><td>TELEPHONIE_MODULE_SIMULATION</td></tr>';


print '<tr class="pair"><td>Module GROUPES</td>';
print '<td>';
if (TELEPHONIE_MODULE_GROUPES == 1)
{
  print 'oui</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_GROUPES&amp;value=0">Changer</a>';
}
else
{
  print 'non</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_GROUPES&amp;value=1">Changer</a>';
}
print '</td><td>TELEPHONIE_MODULE_GROUPES</td></tr>';

/* ***************************************** */

print '<tr class="pair"><td>Module NUMDATA</td>';
print '<td>';
if (TELEPHONIE_MODULE_NUMDATA == 1)
{
  print 'oui</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_NUMDATA&amp;value=0">Changer</a>';
}
else
{
  print 'non</td><td><a href="index.php?action=switch&amp;name=TELEPHONIE_MODULE_NUMDATA&amp;value=1">Changer</a>';
}
print '</td><td>TELEPHONIE_MODULE_NUMDATA</td></tr>';


print '</table>';
print '</form>';

$db->close();

llxFooter();
?>
