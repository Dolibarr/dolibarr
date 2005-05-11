<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}



/*
 * Recherche
 *
 *
 */
if ($mode == 'search') {
  if ($mode-search == 'soc') {
    $sql = "SELECT s.idp FROM ".MAIN_DB_PREFIX."societe as s ";
    $sql .= " WHERE lower(s.nom) like '%".strtolower($socname)."%'";
  }
      
  if ( $db->query($sql) ) {
    if ( $db->num_rows() == 1) {
      $obj = $db->fetch_object(0);
      $socid = $obj->idp;
    }
    $db->free();
  }
}

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

if ($sortorder == "") $sortorder="ASC";
if ($sortfield == "") $sortfield="t.libelle ASC, d.rowid ";

$offset = $conf->liste_limit * $page ;

/*
 * Mode Liste
 *
 *
 *
 */
print '<table width="100%" class="noborder"><tr><td valign="top" width="70%">';

$sql = "SELECT d.libelle as tarif_desc, d.type_tarif";
$sql .= " , t.libelle as tarif";
$sql .= " , m.temporel, m.fixe";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_grille as d";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif_montant as m";
$sql .= "," . MAIN_DB_PREFIX."telephonie_tarif as t";

$sqlc .= " WHERE d.rowid = m.fk_tarif_desc";
$sqlc .= " AND m.fk_tarif = t.rowid";


$sqlc .= " AND t.rowid = '".$_GET["id"]."'";


if ($_GET["search_libelle"])
{
  $sqlc .=" AND t.libelle LIKE '%".$_GET["search_libelle"]."%'";
}

if ($_GET["search_prefix"])
{
  $sqlc .=" AND tf.prefix LIKE '%".$_GET["search_prefix"]."%'";
}

if ($_GET["type"])
{
  $sqlc .= " AND d.type_tarif = '".$_GET["type"]."'";
}


$sql = $sql . $sqlc . " ORDER BY $sortfield $sortorder";

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print_liste_field_titre("Tarif","index.php","d.libelle");

  print_liste_field_titre("Destination","index.php","t.libelle", "&type=".$_GET["type"]);

  print_liste_field_titre("Cout / min","index.php","temporel", "&type=".$_GET["type"]);
  print "</td>";
  print "<td>Cout fixe</td>";
  print "<td>Type</td>";
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print "<td>".$obj->tarif_desc."</td>\n";
      print "<td>".$obj->tarif."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->temporel)."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->fixe)."</td>\n";
      print "<td>".$obj->type_tarif."</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
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

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print '<td>Prefix</td>';
  print "</tr>\n";

  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print "<td>".$obj->prefix."</td>\n";

      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

print '</td></tr></table>';





$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
