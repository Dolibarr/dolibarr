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

if (!$user->rights->telephonie->facture->lire) accessforbidden();

if ($_GET["action"] == 'delete' && $_GET["id"] > 0)
{
  $id = $_GET["id"];
  $result = 0;

  $db->query("BEGIN");

  $sql = "SELECT rowid, fk_facture FROM ".MAIN_DB_PREFIX."telephonie_facture WHERE fk_batch=".$id.";";
  $resql = $db->query($sql);
  if ($resql > 0) $result += 1 ;

  $facturetel_id = array();
  $facture_id = array();
  while ($row = $db->fetch_row($resql))
    {
      array_push($facturetel_id, $row[0]);
    }
  $db->free($resql);

  foreach($facturetel_id as $tfid)
    {
      $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_communications_details WHERE fk_telephonie_facture=".$tfid.";";
      $resql = $db->query($sql);
      if ($resql > 0) $result += 1 ;
    }

  $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_facture WHERE fk_batch=".$id.";";
  $resql = $db->query($sql);
  if ($resql > 0) $result += 1 ;

  $sql = "DELETE FROM ".MAIN_DB_PREFIX."telephonie_facturation_batch WHERE rowid=".$id.";";
  $resql = $db->query($sql);
  if ($resql > 0) $result += 1 ;

  $goodres = 3 + sizeof($facturetel_id) + sizeof($facture_id);

  if ($result == $goodres)
    {
      $db->commit();
    }
  else
    {
      print "$goodres $result";
      $db->rollback();
    }
}

// FIN DES ACTIONS

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
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="";
}
if ($sortfield == "") {
  $sortfield="date_batch DESC";
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
 */

print_barre_liste("CDR a traiter", $page, "cdr.php", "", $sortfield, $sortorder, '', $num);

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
print '<tr class="liste_titre">';
print '<td>id</td><td>Date</td><td>Utilisateur</td>';

print "</tr>\n";

print '<tr class="liste_titre">';
print '<form action="cdr.php" method="GET">';
print '<td><input type="text" name="search_ligne" value="'. $_GET["search_ligne"].'" size="10"></td>'; 
print '<td><input type="text" name="search_num" value="'. $_GET["search_num"].'" size="10"></td>';
print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';

$var=True;

$sql = "SELECT rowid,date_batch";
$sql .= " FROM ".MAIN_DB_PREFIX."telephonie_facturation_batch";
$sql .= " WHERE 1=1";
if ($_GET["search_ligne"])
{
  $sel =urldecode($_GET["search_ligne"]);
  $sel = ereg_replace("\.","",$sel);
  $sel = ereg_replace(" ","",$sel);
  $sql .= " AND ligne LIKE '%".$sel."%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);
$resql = $db->query($sql);

while ($obj = $db->fetch_object($resql))
{
  $var=!$var;
  
  print "<tr $bc[$var]>";
  print '<td>'.$obj->rowid."</td>\n";
  print '<td>'.$obj->date_batch."</td>\n";
  print '<td><a href="batch.php?id='.$obj->rowid.'&amp;action=delete">Supprimer</td>';
}
print "</table>";


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
