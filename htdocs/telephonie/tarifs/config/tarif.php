<?PHP
/* Copyright (C) 2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */
require("./pre.inc.php");

require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.class.php";
require_once DOL_DOCUMENT_ROOT."/telephonie/telephonie.tarif.prefix.class.php";
$ta = new TelephonieTarif($db,1,0);
$ta->fetch($_GET["id"]);
$tp = new TelephonieTarifPrefix($db);

if ($_POST["action"] == 'add_prefix')
{    
  $result = $tp->Create($user, $_POST["prefix"], $_GET["id"], $_POST["force"]);

  //Header("Location: tarif.php?id=".$_GET["id"]);
}

llxHeader();


/*
 * Mode Liste
 *
 */
print_titre($ta->libelle);
print '<table width="100%" class="noborder"><tr><td valign="top" width="70%">';

$sql = "SELECT d.libelle as tarif_desc, d.type_tarif, d.rowid";
$sql .= " , t.libelle as tarif";
$sql .= " , m.temporel, m.fixe";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= ","    . MAIN_DB_PREFIX."telephonie_tarif_grille_rights as r";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";

$sql .= " WHERE d.rowid = m.fk_tarif_desc";
$sql .= " AND m.fk_tarif = t.rowid";
$sql .= " AND t.rowid = '".$_GET["id"]."'";
$sql .= " AND d.type_tarif = 'vente'";
$sql .= " AND d.rowid = r.fk_grille";
$sql .= " AND r.fk_user =".$user->id;
$sql .= " AND r.pread = 1";

$sql .= " ORDER BY t.libelle asc";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td width="25%">Grille</td>';
  print '<td width="30%">Tarif</td>';
  print '<td width="15%">Cout / min</td>';
  print '<td width="15%">Cout fixe</td>';
  print '<td width="15%">Type</td>';
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td><a href="grille.php?id='.$obj->rowid.'">';
      print $obj->tarif_desc."</a></td>\n";

      print "<td>".$obj->tarif."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->temporel)."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->fixe)."</td>\n";
      print "<td>".$obj->type_tarif."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  print $db->error() . ' ' . $sql;
}

$sql = "SELECT d.libelle as tarif_desc, d.type_tarif, d.rowid";
$sql .= " , t.libelle as tarif";
$sql .= " , m.temporel, m.fixe";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= ","    . MAIN_DB_PREFIX."telephonie_tarif_grille_rights as r";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";

$sql .= " WHERE d.rowid = m.fk_tarif_desc";
$sql .= " AND m.fk_tarif = t.rowid";
$sql .= " AND t.rowid = '".$_GET["id"]."'";
$sql .= " AND d.type_tarif = 'achat'";
$sql .= " AND d.rowid = r.fk_grille";
$sql .= " AND r.fk_user =".$user->id;
$sql .= " AND r.pread = 1";
$sql .= " ORDER BY t.libelle ASC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<br><table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print '<td width="25%">Grille</td>';
  print '<td width="30%">Tarif</td>';
  print '<td width="15%">Cout / min</td>';
  print '<td width="15%">Cout fixe</td>';
  print '<td width="15%">Type</td>';
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td><a href="../grille.php?id='.$obj->rowid.'">';
      print $obj->tarif_desc."</a></td>\n";

      print "<td>".$obj->tarif."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->temporel)."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->fixe)."</td>\n";
      print "<td>".$obj->type_tarif."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  print $db->error() . ' ' . $sql;
}


print '</td><td valign="top" width="30%">';

$sql = "SELECT prefix";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_prefix";
$sql .= " WHERE fk_tarif = ".$_GET["id"];
$sql .= " ORDER BY prefix ASC";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Prefix</td>';
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$obj->prefix."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  print $db->error() . ' ' . $sql;
}


print '<br><form action="tarif.php?id='.$_GET["id"].'" method="post">';
print '<input type="hidden" name="token_level_1" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add_prefix">';

print '<table class="border" width="100%">';

// Nom
print '<tr><td valign="top">Prefix</td>';
print '<td><input size="10" type="text" name="prefix" value=""></td></tr>';

if ($tp->tarif_id > 0)
{
  print '<tr><td valign="top">Forcer la recuperation</td>';
  print '<td><input type="checkbox" name="force"></td></tr>';
}

print '<tr><td align="center"><input class="button" value="'.$langs->trans("Add").'" type="submit"></td></tr>';

print '</table></form>';


if ($tp->tarif_id > 0)
{

  $etarif = new TelephonieTarif($db,1,0);
  $etarif->Fetch($tp->tarif_id);

  print '<br><table class="border" width="100%">';

  print '<tr><td valign="top"><b>Erreur</b></td></tr>';
  print '<tr><td valign="top">Le prefix <b>'.$_POST["prefix"].' </b> existe deja !<br> ';
  print 'Il est affecte au tarif : <b>'.$etarif->libelle.'</b><br>';
  print 'Vous pouvez le reaffecter a ce tarif en cochant la case "Forcer la recuperation"';
  print '</td></tr>';
  
  print '</table>';
 
}

print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
