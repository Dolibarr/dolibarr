<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/compta/stats/cumul.php
		\brief      Page reporting compta chiffre affaire cumulé
		\version    $Id$
*/

require("./pre.inc.php");

// Sécurité accés client
if ($user->societe_id > 0) 
{
  $socid = $user->societe_id;
}

// Define modecompta ('CREANCES-DETTES' or 'RECETTES-DEPENSES')
$modecompta = $conf->compta->mode;
if ($_GET["modecompta"]) $modecompta=$_GET["modecompta"];



llxHeader();


print_titre("Chiffre d'affaire cumulé (".$langs->trans("Currency".$conf->monnaie)." HT)");

print '<table width="100%"><tr><td valign="top">';

$sql = "SELECT sum(f.total) as amount , date_format(f.datef,'%Y-%m') as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= " WHERE f.fk_statut in (1,2)";
$sql.= " AND f.entity = ".$conf->entity;
if ($modecompta != 'CREANCES-DETTES') $sql.= " AND f.paye = 1";
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY dm";

pt($db, $sql,"Suivi cumul par mois");

print '</td><td valign="top">';

$sql = "SELECT sum(f.total) as amount, year(f.datef) as dm";
$sql.= " FROM ".MAIN_DB_PREFIX."facture as f";
$sql.= " WHERE f.fk_statut in (1,2)";
$sql.= " AND f.entity = ".$conf->entity;
if ($modecompta != 'CREANCES-DETTES') $sql.= " AND f.paye = 1";
if ($socid) $sql.= " AND f.fk_soc = ".$socid;
$sql.= " GROUP BY dm";


pt($db, $sql,"Suivi cumul par année");

print "</td></tr></table>";

$db->close();

llxFooter('$Date$ - $Revision$');



/*
 * Fonctions
 *
 */

function pt ($db, $sql, $date)
{
    global $langs;
    
  $bc[0]="class=\"pair\"";
  $bc[1]="class=\"impair\"";

  $resql = $db->query($sql);
  if ($resql)
    {
      $num = $db->num_rows($resql);
      $i = 0; $total = 0 ;
      print '<table class="noborder" width="100%">';
      print "<tr class=\"liste_titre\">";
      print "<td width=\"60%\">$date</td>";
      print "<td align=\"right\">".$langs->trans("Amount")."</td>";
      print "<td align=\"right\">".$langs->trans("Total")."</td>\n";
      print "</tr>\n";
      $var=True;
      while ($i < $num) 
	{
	  $obj = $db->fetch_object($resql);
	  $var=!$var;
	  $total = $total + $obj->amount;
	  print "<tr $bc[$var]>";
	  print "<td>$obj->dm</td>\n";
	  print "<td align=\"right\">".price($obj->amount)."</td><td align=\"right\">".price($total)."</td>\n";
	  print "</tr>\n";
	  
	  $i++;
	}
      print "<tr class=\"liste_total\"><td  align=\"right\">".$langs->trans("Total")."</td><td align=\"right\">&nbsp;</b></td><td align=\"right\"><b>".price($total)."</b></td></tr>\n";
      
      print "</table>\n";
      $db->free($resql);
    }
}


llxFooter('$Date$ - $Revision$');
?>
