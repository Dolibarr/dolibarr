<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

llxHeader();

if ($_GET["type"] == '')
{
 $_GET["type"] = 'achat'; 
}

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="prefix, libelle, fournisseur";
}

/*
 * Recherche
 *
 *
 */

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;



/*
 * Mode Liste
 *
 *
 *
 */


$sql = "SELECT t.libelle as tarif, p.prefix"; 
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif as t";
$sql .= "," . MAIN_DB_PREFIX."telephonie_prefix as p";

$sql .= " WHERE p.fk_tarif = t.rowid";

if ($_GET["search_tarif"])
{
  $sql .=" AND t.libelle LIKE '%".$_GET["search_tarif"]."%'";
}

if ($_GET["search_prefix"])
{
  $sql .=" AND p.prefix LIKE '%".$_GET["search_prefix"]."%'";
}

$sql .= " ORDER BY t.libelle ASC " . $db->plimit($conf->liste_limit+1, $offset);


$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Prefix", $page, "prefix.php", "&type=".$_GET["type"], $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print_liste_field_titre("Tarif","prefix.php","f.nom");

  print_liste_field_titre("Prefix","prefix.php","libelle", "&type=".$_GET["type"]);
  print '<td>&nbsp;</td>';

  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="prefix.php" method="GET">';
  print '<input type="hidden" name="type" value="'.$_GET["type"].'">';
  print '<td><input type="text" name="search_tarif" size="20" value="'.$_GET["search_tarif"].'"></td>';
  print '<td><input type="text" name="search_prefix" size="8" value="'.$_GET["search_prefix"].'"></td>';

  print '<td><input type="submit"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print "<td>".$obj->tarif."</td>\n";
      print "<td>".$obj->prefix."</td>\n";
      print '<td>&nbsp;</td>'; 
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

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
