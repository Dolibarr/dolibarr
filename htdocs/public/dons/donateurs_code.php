<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


$conf = new Conf();
$db = new Db();

$sql = "SELECT ".$db->pdate("d.datedon")." as datedon, d.nom, d.amount, d.public, d.societe";
$sql .= " FROM llx_don as d";
$sql .= " WHERE d.fk_don_projet = 1 AND d.fk_statut = 3 ORDER BY d.datedon DESC";

if ( $db->query( $sql) )
{
  $num = $db->num_rows();
  if ($num)
    {
      
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

      print '<TR>';
      print "<td>Nom / Société</td>";
      print "<td>Date</td>";
      print "<td align=\"right\">Montant</TD>";
      print "</TR>\n";
      
      $var=True;
      $bc[1]='bgcolor="#f5f5f5"';
      $bc[0]='bgcolor="#f0f0f0"';
      while ($i < $num)
	{
	  $objp = $db->fetch_object( $i);

	  $var=!$var;
	  print "<TR $bc[$var]>";
	  if ($objp->public)
	    {
	      print "<td>".stripslashes($objp->nom)." ".stripslashes($objp->societe)."</TD>\n";
	    }
	  else
	    {
	      print "<td>Anonyme Anonyme</TD>\n";
	    }
	  print "<TD>".strftime("%d %B %Y",$objp->datedon)."</td>\n";
	  print '<TD align="right">'.number_format($objp->amount,2,'.',' ').' euros</TD>';
	  print "</tr>";
	  $i++;
	}
      print "</table>";

    }
}
else
{
  print $db->error();
}

$db->close();

?>
