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
  $sortfield="cg.numero";
}


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

$sql = "SELECT cg.rowid, cg.numero, cg.intitule, ".$db->pdate("cg.date_creation")." as dc";

$sql .= " FROM ".MAIN_DB_PREFIX."compta_compte_generaux as cg";

if (strlen(trim($_GET["search_numero"])) )
{
  
  $sql .= " WHERE cg.numero LIKE '%".$_GET["search_numero"]."%'";
  
  if ( strlen(trim($_GET["search_intitule"])))
    {     
      $sql .= " AND cg.intitule LIKE '%".$_GET["search_intitule"]."%'";
    }
  
}
else
{
  if ( strlen(trim($_GET["search_intitule"])))
    {
      $sql .= " WHERE cg.intitule LIKE '%".$_GET["search_intitule"]."%'";
    }
}


$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Comptes généraux", $page, "liste.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="liste">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("N° compte","liste.php","s.nom");
  print_liste_field_titre("Intitulé compte","liste.php","s.nom");
  print '<td align="right">Date création</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="liste.php" method="GET">';
  print '<td><input type="text" name="search_numero" value="'.$_GET["search_numero"].'"></td>';
  print '<td><input type="text" name="search_intitule" value="'.$_GET["search_intitule"].'"><input type="submit"></td>';
  print '<td>&nbsp;</td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print '<td>'.$obj->numero.'</td>'."\n";
      print '<td>'.$obj->intitule.'</td>';
      print '<td align="right" width="100">';
      print dolibarr_print_date($obj->dc);

      print '</td>';
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
