<?PHP
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

llxHeader('','Telephonie - Clients - Remises à faire');
/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="s.nom";
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

$sql = "SELECT s.rowid as socid, s.nom, r.amount";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql .= ",".MAIN_DB_PREFIX."telephonie_client_remise as r";
$sql .= ",".MAIN_DB_PREFIX."societe_perms as sp";

$sql .= " WHERE r.fk_client = s.rowid ";
$sql .= " AND r.fk_facture = 0";
$sql .= " AND s.rowid = sp.fk_soc";
$sql .= " AND sp.fk_user = ".$user->id." AND sp.pread = 1";

if ($_GET["search_client"])
{
  $sel = urldecode($_GET["search_client"]);
  $sql .= " AND s.nom LIKE '%".$sel."%'";
}

$sql .= " ORDER BY r.fk_facture ASC, $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  $urladd= "&amp;statut=".$_GET["statut"];

  print_barre_liste("Remises sur prochaines factures", $page, "remises.php", $urladd, $sortfield, $sortorder, '', $num);

  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre">';
  print_liste_field_titre("Client","remises.php","s.nom","","",' width="50%"');
  print '<td width="25%" align="right">Remise';
  print '</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="remises.php" method="GET">';
  print '<td><input type="text" name="search_client" value="'. $_GET["search_client"].'" size="12"></td>';  
  print '<td><input type="submit" class="button" value="'.$langs->trans("Search").'"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  $ligne = new LigneTel($db);

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($i);	
      $var=!$var;

      print "<tr $bc[$var]><td>";

      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">';
      print img_file();      
      print '</a>&nbsp;';

      print '<a href="'.DOL_URL_ROOT.'/telephonie/client/fiche.php?id='.$obj->socid.'">'.$obj->nom.'</a></td>';

      print '<td align="right">'.price($obj->amount)." euros HT</td>\n";

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
