<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

print '<table border="0" width="100%" cellspacing="0" cellpadding="4">';

print '<tr><td valign="top" width="30%">';
/*
 *
 */


if ($sortfield == "")
{
  $sortfield="o.orders_status ASC, o.date_purchased";
}
if ($sortorder == "")
{
  $sortorder="DESC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

$sql = "SELECT o.orders_id, o.customers_name, o.orders_status FROM ".DB_NAME_OSC.".orders as o";
  
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit($limit + 1,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();

  print_barre_liste("Liste des Commandes",$page,$PHP_SELF,"",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<p><table class="liste" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>';
  print_liste_field_titre("Client",$PHP_SELF, "p.ref");
  print "</td>";
  print "<td></td>";
  print "</tr>\n";
  $var=True;
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?id=$objp->orders_id\">$objp->customers_name</a></TD>\n";
      print "<td>$objp->orders_status</TD>\n";
      print "</tr>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
/*
 *
 */
print '</td><td valign="top" width="70%">';

/*
 * Propales à facturer
 */
if ($user->comm > 0 && $conf->commercial ) 
{
  $sql = "SELECT p.rowid, p.ref, s.nom, s.idp FROM llx_propal as p, llx_societe as s";
  $sql .= " WHERE p.fk_soc = s.idp AND p.fk_statut = 2";
  if ($socidp)
    {
      $sql .= " AND p.fk_soc = $socidp";
    }

  if ( $db->query($sql) ) 
    {
      $num = $db->num_rows();
      if ($num)
	{
	  $i = 0;
	  print '<table border="0" cellspacing="0" cellpadding="3" width="100%">';
	  print "<tr class=\"liste_titre\">";
	  print '<td colspan="2">'.translate("Propositions commerciales signées").'</td></tr>';
  
	  while ($i < $num)
	    {
	      $var=!$var;
	      $obj = $db->fetch_object($i);
	      print "<tr $bc[$var]><td width=\"20%\"><a href=\"propal.php?propalid=$obj->rowid\">$obj->ref</a></td>";
	      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.$obj->nom.'</a></td></tr>';
	      $i++;
	    }
	  print "</table><br>";
	}
    }
}



print '</td></tr></table>';

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
