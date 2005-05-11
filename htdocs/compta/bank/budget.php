<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/compta/bank/budget.php
        \ingroup    banque
		\brief      Page de budget
		\version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->banque->lire)
  accessforbidden();

llxHeader();

/*
 *
 *
 */

if ($_GET["bid"] == 0)
{
  /*
   *    Liste mouvements par catégories d'écritures financières
   */
  print_titre("Ecritures bancaires par catégories");
  print '<br>';
  
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print '<td>'.$langs->trans("Description").'</td><td align="center">'.$langs->trans("Nb").'</td><td align="right">'.$langs->trans("Total").'</td><td align="right">'.$langs->trans("Average").'</td>';
  print "</tr>\n";

  $sql = "SELECT sum(d.amount) as somme, count(*) as nombre, c.label, c.rowid ";
  $sql .= " FROM ".MAIN_DB_PREFIX."bank_categ as c, ".MAIN_DB_PREFIX."bank_class as l, ".MAIN_DB_PREFIX."bank as d";
  $sql .= " WHERE d.rowid=l.lineid AND c.rowid = l.fk_categ GROUP BY c.label, c.rowid ORDER BY c.label";
  
  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows($result);
      $i = 0; $total = 0;
      
      $var=true;
      while ($i < $num)
	{
	  $objp = $db->fetch_object($result);
	  $var=!$var;
	  print "<tr ".$bc[$var].">";
	  print "<td><a href=\"budget.php?bid=$objp->rowid\">$objp->label</a></td>";
	  print '<td align="center">'.$objp->nombre.'</td>';
	  print "<td align=\"right\">".price(abs($objp->somme))."</td>";
	  print "<td align=\"right\">".price(abs($objp->somme / $objp->nombre))."</td>";
	  print "</tr>";
	  $i++;
	  $total = $total + abs($objp->somme);
	}
      $db->free($result);

      print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").'</td>';
      print '<td align="right"><b>'.price($total).'</b></td><td colspan="2">&nbsp;</td></tr>';
    }
  else
    {
      dolibarr_print_error($db);
    }
  print "</table>";

}
else
{
  /*
   *  Rapport mouvements pour une catégorie donnée
   */
  $sql = "SELECT label FROM ".MAIN_DB_PREFIX."bank_categ WHERE rowid=".$_GET["bid"];
  if ( $db->query($sql) )
    {
      if ( $db->num_rows() )
	{
	  $budget_name = $db->result(0,0);
	}
      $db->free();
    }
  
  print_titre("Ecriture bancaire pour la catégorie: $budget_name");
  print '<br>';
  
  print '<table class="noborder" width="100%">';
  print "<tr class=\"liste_titre\">";
  print '<td align="center">'.$langs->trans("Date").'</td>';
  print '<td align="left">'.$langs->trans("Bank").'</td>';
  print '<td width="60%">'.$langs->trans("Description").'</td><td align="right">'.$langs->trans("Amount").'</td><td>&nbsp;</td>';
  print "</tr>\n";

  $sql = "SELECT b.amount, b.label, ".$db->pdate("b.dateo")." as do, b.rowid, ba.label as labelcompte, ba.rowid as bankid";
  $sql.= " FROM ".MAIN_DB_PREFIX."bank_class as l, ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."bank_account as ba";
  $sql.= " WHERE b.rowid=l.lineid AND l.fk_categ=".$_GET["bid"];
  $sql.= " AND b.fk_account = ba.rowid";
  $sql.= " ORDER BY b.dateo DESC";
  
  $result = $db->query($sql);
  if ($result)
    {
      $num = $db->num_rows($result);
      $i = 0; $total = 0;
      
      $var=True;
      while ($i < $num)
	{
	  $objp = $db->fetch_object($result);
	  $var=!$var;
	  print "<tr $bc[$var]>";
	  print "<td align=\"center\">".dolibarr_print_date($objp->do)."</td>\n";
	  
	  print "<td><a href=\"account.php?account=$objp->bankid\">$objp->labelcompte</a></td>";
	  print "<td><a href=\"ligne.php?rowid=$objp->rowid\">".img_object($langs->trans("ShowPayment"),"payment").' '.$objp->label.'</a></td>';
	  print "<td align=\"right\">".price(0 - $objp->amount)."</td><td>&nbsp;</td>";
	  print "</tr>";
	  $i++;
	  $total = $total + (0 - $objp->amount);
	}
      $db->free();
      print '<tr class="liste_total"><td colspan="3" align="right">'.$langs->trans("Total")."</td><td align=\"right\"><b>".price(abs($total))."</b></td><td>".$conf->monnaie."</td></tr>";
    }
  else
    {
      dolibarr_print_error($db);
  }
  print "</table>";
  
}

$db->close();

llxFooter('$Date$ - $Revision$');

?>
