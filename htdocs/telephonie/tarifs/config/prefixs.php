<?PHP
/* Copyright (C) 2005-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  $socid = $user->societe_id;
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
if ($sortfield == "") $sortfield="p.prefix ";

$offset = $conf->liste_limit * $page ;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT t.libelle as tarif, t.rowid as tarif_id";
$sql .= " , p.prefix";

$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_prefix as p";
$sql .= ","    . MAIN_DB_PREFIX."telephonie_tarif as t";

$sqlc .= " WHERE p.fk_tarif = t.rowid";


if ($_GET["search_libelle"])
{
  $sqlc .=" AND t.libelle LIKE '%".$_GET["search_libelle"]."%'";
}

if ($_GET["search_prefix"])
{
  $sqlc .=" AND p.prefix LIKE '%".$_GET["search_prefix"]."%'";
}

if ($_GET["type"])
{
  $sqlc .= " AND d.type_tarif = '".$_GET["type"]."'";
}

$sql = $sql . $sqlc . " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);


$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  $urladd = "&amp;type=".$_GET["type"]."&amp;search_prefix=".$_GET["search_prefix"]."&amp;search_libelle=".$_GET["search_libelle"];
  
  print_barre_liste("Prefixs", $page, "prefixs.php", $urladd, $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print_liste_field_titre("Prefix","prefixs.php","p.prefix");
  print_liste_field_titre("Tarif","prefixs.php","t.libelle");

  print "<td>&nbsp;</td></tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="prefixs.php" method="GET">';
  print '<input type="hidden" name="type" value="'.$_GET["type"].'">';
  print '<td><input type="text" name="search_prefix" size="10" value="'.$_GET["search_prefix"].'"></td>';
  print '<td><input type="text" name="search_libelle" size="20" value="'.$_GET["search_libelle"].'"></td>';
  print '<td><input type="submit" value="'.$langs->trans("Search").'"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]>";
      print "<td>".$obj->prefix."</td>\n";
      print '<td><a href="tarif.php?id='.$obj->tarif_id.'">';
      print $obj->tarif."</a></td>\n";
      print "<td>&nbsp;</td></tr>\n";

      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
