<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Association FSF France <contact@fsffrance.org>
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
$a = setlocale("LC_TIME", "FRENCH");
$sql = "SELECT ".$db->pdate("f.datef")." as datef, s.nom, f.total, f.note, f.paye";
$sql .= " FROM llx_facture_fourn as f, societe as s";
$sql .= " WHERE f.fk_soc = s.idp ORDER BY f.datef DESC";

if ( $db->query( $sql) )
{
  $num = $db->num_rows();
  if ($num)
    {
      
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

      print '<TR>';
      print "<td>Société</td>";
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
	  print "<td>".stripslashes($objp->nom)."</TD>\n";
	  	  
	  print "<TD>".strftime("%d %B %Y",$objp->datef)."</td>\n";
	  print '<TD align="right">'.number_format($objp->total,2,'.',' ').'<br>euros';

	  if ($obj->paye)
	    {
	      print "<br>payé";
	      $total_paye += $objp->total;
	    }
	  else
	    {
	      print "<br>A payer";
	      $total_apayer += $objp->total;
	    }

	  print '</TD>';
	  print "</tr>";
	  
	  print "<TR $bc[$var]><td>&nbsp</td><td>";
	  print nl2br(stripslashes($objp->note));
	  print "</td><td>&nbsp</td></tr>";

	  $total += $objp->total;

	  $i++;
	}

      $var=!$var;
      print "<TR $bc[$var]>";
      print '<TD colspan="3" align="right">Payé : '.number_format($total_paye,2,'.',' ').' euros</TD></tr>';

      $var=!$var;
      print "<TR $bc[$var]>";
      print '<TD colspan="3" align="right">A payer : '.number_format($total_apayer,2,'.',' ').' euros</TD></tr>;'

      $var=!$var;
      print "<TR $bc[$var]>";
      print '<TD colspan="3" align="right">Total : '.number_format($total,2,'.',' ').' euros</TD></tr>';

      print "</table>";

    }
}
else
{
  print $db->error();
}

$db->close();

?>
