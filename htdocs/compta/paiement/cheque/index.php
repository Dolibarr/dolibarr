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

$user->getrights("facture");

// Sécurité accés client
if (! $user->rights->facture->lire)
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

print '<table width="100%"><tr><td width="40%" valign="top">';

$sql = "SELECT bc.rowid,".$db->pdate("bc.date_bordereau")." as db, bc.amount,";
$sql.= " bc.statut, ba.label, ba.rowid as bid";
$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc";
$sql.= ",".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE ba.rowid=bc.fk_bank_account"; 
$sql.= " ORDER BY bc.rowid DESC LIMIT 10;";

$resql = $db->query($sql);

if ($resql)
{
  $i = 0;

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("Date")."</td>";
  print '<td>'.$langs->trans("Account").'</td>';
  print '<td align="right">'.$langs->trans("Amount").'</td>';
  print "</tr>\n";
  
  $var=true;
  while ( $objp = $db->fetch_object($resql) )
    {
      $var=!$var;
      print "<tr $bc[$var]>\n";
      print '<td>';	
      print '<a href="'.DOL_URL_ROOT.'/compta/paiement/cheque/fiche.php?id='.$objp->rowid.'">';
      print dolibarr_print_date($objp->db,'%d/%m').'</a></td>';
      
      print '<td>';
      print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</a>';
      
      print '</td>';
      print '<td align="right">'.price($objp->amount).'</td></tr>';
      $i++;
    }
  print "</table>";
}
else
{
  dolibarr_print_error($db);
}

print "</td>\n";
print '<td width="60%" valign="top">';

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
 
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";
  
$sql = "SELECT b.amount,b.emetteur,".$db->pdate("b.dateo")." as date,";
$sql.= " c.code as paiement_code,"; 
$sql.= " ba.rowid as bid, ba.label";
$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as c";
$sql.= ",".MAIN_DB_PREFIX."bank as b";
$sql.= ",".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE c.code = 'CHQ' AND c.code = b.fk_type AND b.fk_bordereau = 0 AND b.fk_account = ba.rowid";
$sql.= " AND b.amount > 0";
//$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit+1 ,$offset);
//print "$sql";

$resql = $db->query($sql);

if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  $paramlist=($_GET["orphelins"]?"&orphelins=1":"");

  print '<table class="noborder" width="100%">';
  print '<tr class="liste_titre">';
  print '<td>'.$langs->trans("Date")."</td>\n";
  print '<td>'.$langs->trans("Account")."</td>\n";
  print '<td align="right">'.$langs->trans("Amount")."</td>\n";
  print '<td>'.$langs->trans("CheckTransmitter")."</td>\n";

  print "</tr>\n";
  
  $var=true;
  while ($i < min($num,$limit))
    {
      $objp = $db->fetch_object($resql);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td>'.dolibarr_print_date($objp->date).'</td><td>';

      if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</a>';
      else print '&nbsp;';
      print '</td>';
      print '<td align="right">'.price($objp->amount).'</td>';
      print '<td>'.$objp->emetteur.'</td>';
      print '</tr>';
      $i++;
    }
  print "</table>";
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
