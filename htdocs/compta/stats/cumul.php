<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*
 *
 */

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $socidp = $user->societe_id;
}

print_titre("Chiffre d'affaire cumulé (".MAIN_MONNAIE." HT)");

print '<table width="100%"><tr><td valign="top">';

$sql = "SELECT sum(f.total) as amount , date_format(f.datef,'%Y-%m') as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.fk_statut = 1";
if ($conf->compta->mode != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm";

pt($db, $sql,"Suivi cumul par mois");

print "</td><td valign=\"top\">";

$sql = "SELECT sum(f.total) as amount, year(f.datef) as dm";
$sql .= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql .= " WHERE f.fk_statut = 1";
if ($conf->compta->mode != 'CREANCES-DETTES') { 
	$sql .= " AND f.paye = 1";
}
if ($socidp)
{
  $sql .= " AND f.fk_soc = $socidp";
}
$sql .= " GROUP BY dm";


pt($db, $sql,"Suivi cumul par année");

print "</td></tr></table>";

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");

/*
 * Fonctions
 *
 */

function pt ($db, $sql, $date)
{
  $bc[0]="class=\"pair\"";
  $bc[1]="class=\"impair\"";

  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows();
      $i = 0; $total = 0 ;
      print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
      print "<tr class=\"liste_titre\">";
      print "<td width=\"60%\">$date</td>";
      print "<td align=\"right\">Montant</td>";
      print "<td align=\"right\">Cumul</td>\n";
      print "</tr>\n";
      $var=True;
      while ($i < $num) 
	{
	  $obj = $db->fetch_object( $i);
	  $var=!$var;
	  $total = $total + $obj->amount;
	  print "<tr $bc[$var]>";
	  print "<td>$obj->dm</td>\n";
	  print "<td align=\"right\">".price($obj->amount)."</td><td align=\"right\">".price($total)."</td>\n";
	  print "</tr>\n";
	  
	  $i++;
	}
      print "<tr class=\"total\"><td  align=\"right\">Cumul :</td><td align=\"right\">&nbsp;</b></td><td align=\"right\"><b>".price($total)."</b></td></tr>\n";
      
      print "</table>\n";
      $db->free();
    }
}


?>
