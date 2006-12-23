<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */
 
/**
   \file       htdocs/compta/paiement/cheque/liste.php
    \ingroup    compta
     \brief      Page liste des bordereau de remise de cheque
      \version    $Revision$
*/

require("./pre.inc.php");

$langs->load("banks");

$user->getrights("banque");

// Sécurité accés client
if (! $user->rights->banque)
  accessforbidden();

$socid=0;
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

/*
 * Affichage
 */

llxHeader('',$langs->trans("CheckReceipt"));

print_titre($langs->trans("CheckReceipt") );

print '<table width="100%"><tr><td width="30%" valign="top">';

$sql = "SELECT count(b.rowid)";
$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
$sql.= " WHERE b.fk_type = 'CHQ'AND b.fk_bordereau = 0";
$sql.= " AND b.amount > 0";

$resql = $db->query($sql);

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("BankChecks")."</td>\n";
print "</tr>\n";

if ($resql)
{
  $var=true;
  if ($row = $db->fetch_row($resql) )
    {
      $num = $row[0];
    }
  print "<tr $bc[$var]>";
  print '<td>'.$langs->trans("BankChecksToReceipt").'</td>';
  print '<td align="right">'.$num.'</td></tr>';
  print "</table>\n";
}
else
{
  dolibarr_print_error($db);
}

print "</td>\n";
print '<td width="70%" valign="top">';

$sql = "SELECT bc.rowid,".$db->pdate("bc.date_bordereau")." as db, bc.amount,bc.number,";
$sql.= " bc.statut, ba.label, ba.rowid as bid";
$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc";
$sql.= ",".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE ba.rowid=bc.fk_bank_account"; 
$sql.= " ORDER BY bc.rowid DESC LIMIT 10;";

$resql = $db->query($sql);

if ($resql)
{
  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("Date")."</td>";
  print '<td>'.$langs->trans("Numero").'</td>';
  print '<td>'.$langs->trans("Account").'</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print "</tr>\n";
  
  $var=true;
  while ( $objp = $db->fetch_object($resql) )
    {
      $var=!$var;
      print "<tr $bc[$var]>\n";
      print '<td>';	
      print '<img src="statut'.$objp->statut.'.png" alt="Statut" width="12" height="12"> ';
      print dolibarr_print_date($objp->db,'%d/%m').'</td>';
      if ($objp->statut == 1)
	{
	  print '<td><a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$objp->rowid.'">'.$objp->number.'</a></td>';
	}
      else
	{
	  print '<td><a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$objp->rowid.'">(PROV'.$objp->rowid.')</a></td>';
	}
      print '<td><a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</a>';      
      print '</td>';
      print '<td align="right">'.price($objp->amount).'</td></tr>';
    }
  print "</table>";
  $db->free($resql);
}
else
{
  dolibarr_print_error($db);
}

print "</td></tr>\n";
print "</table>\n";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
