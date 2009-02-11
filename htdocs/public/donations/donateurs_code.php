<?php
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
 */

/**
 * 	\version	$Id$
 */

require("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/don.class.php");

$langs->setDefaultLang('auto');

$langs->load("donations");

$sql = "SELECT ".$db->pdate("d.datedon")." as datedon, d.nom, d.prenom, d.amount, d.public, d.societe";
$sql .= " FROM ".MAIN_DB_PREFIX."don as d";
$sql .= " WHERE d.fk_don_projet = 1 AND d.fk_statut in (2, 3) ORDER BY d.datedon DESC";

if ( $db->query( $sql) )
{
  $num = $db->num_rows();

  if ($num)
    {

      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

      print '<TR>';
      print "<td>".$langs->trans("Name")." / ".$langs->trans("Company")."</td>";
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
	      print "<td>".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." ".stripslashes($objp->societe)."</td>\n";
	    }
	  else
	    {
	      print "<td>Anonyme Anonyme</td>\n";
	    }
	  print "<td>".dolibarr_print_date($objp->datedon)."</td>\n";
	  print '<td align="right">'.number_format($objp->amount,2,'.',' ').' '.$langs->trans("Currency".$conf->monnaie).'</td>';
	  print "</tr>";
	  $i++;
	}
      print "</table>";

    }
    else
    {
    	print "Aucun don publique";
    }
}
else
{
	dolibarr_print_error($db);
}

$db->close();

?>
