<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 */

/**
   \file       htdocs/compta/bank/config.php
   \ingroup    banque
   \brief      Page de configuration des comptes bancaires
   \version    $Revision$
*/

require("./pre.inc.php");
require("./bank.lib.php");

if (!$user->rights->banque->configurer)
  accessforbidden();

llxHeader();

print_titre($langs->trans("AccountSetup"));
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref")."</td><td>".$langs->trans("Type")."</td><td>".$langs->trans("Bank").'</td>';
print '<td align="left">'.$langs->trans("AccountIdShort").'</a></td>';
print '<td align="center">'.$langs->trans("Conciliable").'</a></td>';
print '<td align="center">'.$langs->trans("Status").'</a></td>';
print "</tr>\n";

$sql = "SELECT rowid, label, number, bank, courant as type, clos, rappro";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_account";
$sql.= " ORDER BY label";

$result = $db->query($sql);
$var=false;
if ($result)
{
  $account=new Account($db);
  
  $var=True;  
  $num = $db->num_rows($result);
  $i = 0; $total = 0;

  $sep = 0;

  while ($i < $num) {
    $objp = $db->fetch_object($result);

    $var=!$var;
    print '<tr '.$bc[$var].'><td><a href="fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</td>';
    print '<td>'.$account->type_lib[$objp->type].'</td>';
    print '<td>'.$objp->bank.'&nbsp;</td><td>'.$objp->number.'&nbsp;</td>';
    print '<td align="center">'.yn($objp->rappro).'</td>';
    print '<td align="center">'.$account->status[$objp->clos].'</td></tr>';

    $i++;
  }
  $db->free($result);
}
print "</table>";


/*
 * Boutons d'actions
 */
print "<br><div class=\"tabsAction\">\n";
if ($user->rights->banque->configurer)
{
  print '<a class="tabAction" href="fiche.php?action=create">'.$langs->trans("NewFinancialAccount").'</a>';
  print '<a class="tabAction" href="categ.php">'.$langs->trans("Categories").'</a>';
}
print "</div>";

$db->close();

llxFooter('$Date$ - $Revision$');
?>
