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

/**	        \file       htdocs/telephonie/tarifs/index.php
	        \ingroup    telephonie
	        \brief      Page accueil tarif telephonie
	        \version    $Revision$
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

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="libelle";
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

$sql = "SELECT distinct(t.libelle) as libelle,  temporel ,t.fixe";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_tarif_vente as t";
$sql .= " WHERE 1=1";

if ($_GET["search_libelle"])
{
  $sqlc .=" AND t.libelle LIKE '%".$_GET["search_libelle"]."%'";
}

$sql = $sql . $sqlc . " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);



$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Tarifs à la vente", $page, "index.php", "&type=".$_GET["type"], $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';

  print_liste_field_titre("Destination","index.php","libelle", "&type=".$_GET["type"]);

  print_liste_field_titre("Coût / min","index.php","temporel", "&type=".$_GET["type"]);

  print "<td>Coût fixe</td>";
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="index.php" method="GET">';
  print '<input type="hidden" name="type" value="'.$_GET["type"].'">';
  print '<td><input type="text" name="search_libelle" size="20" value="'.$_GET["search_libelle"].'"></td>';
  print '<td>&nbsp;</td>';
  print '<td><input type="submit"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]>";

      print "<td>".$obj->libelle."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->temporel)."</td>\n";
      print "<td>".sprintf("%01.4f",$obj->fixe)."</td>\n";
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
