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
 */

/*! \file htdocs/compta/bank/config.php
        \ingroup    banque
		\brief      Page de configuration des comptes bancaires
		\version    $Revision$
*/

require("./pre.inc.php");
require("./bank.lib.php");

$user->getrights('compta');
$user->getrights('banque');

if (!$user->admin && !$user->rights->compta->bank)
  accessforbidden();

llxHeader();

print_titre("Configuration des comptes");
print '<br>';

print '<table class="noborder" width="100%" cellspacing="0" cellpadding="2">';
print "<tr class=\"liste_titre\">";
print "<td>id</td><td>Label</td><td>".$langs->trans("Description")."</td>";
print "<td align=\"left\">".$langs->trans("Number")."</a></td>";
print "<td align=\"center\">Clos</a></td>";
print "</tr>\n";

$sql = "SELECT rowid, label,number,bank,clos from ".MAIN_DB_PREFIX."bank_account";

$result = $db->query($sql);
$var=false;
if ($result)
{
  $var=True;  
  $num = $db->num_rows();
  $i = 0; $total = 0;

  $sep = 0;

  while ($i < $num) {
    $objp = $db->fetch_object( $i);

    $var=!$var;
    print "<tr $bc[$var]><td>$objp->rowid</td><td><a href=\"fiche.php?id=$objp->rowid\">$objp->label</a></td>";
    print "<td>$objp->bank&nbsp;</td><td>$objp->number&nbsp;</td><td align=\"center\">".$yn[$objp->clos]."</td></tr>";

    $i++;
  }
  $db->free();
}
print "</table>";


/*
 * Boutons d'actions
 */
print "<br><div class=\"tabsAction\">\n";
if ($user->rights->banque->configurer) {
	print '<a class="tabAction" href="fiche.php?action=create">Nouveau compte</a>';
	print '<a class="tabAction" href="categ.php">Catégories</a>';
}
print "</div>";


$db->close();

llxFooter(strftime("%H:%M",time()). " - <em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
