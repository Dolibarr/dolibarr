<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Charles-Fr BENKE     <charles.fr@benke.fr>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016      Marcos García        <marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/compta/bank/categ.php
 *      \ingroup    compta
 *      \brief      Page ajout de categories bancaires
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories'));

$action = GETPOST('action', 'aZ09');
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

if (!$user->rights->banque->configurer)
  accessforbidden();

$bankcateg = new BankCateg($db);
$categid = GETPOST('categid');
$label = GETPOST("label");

/*
 * Add category
 */
if (GETPOST('add'))
{
	if ($label) {
		$bankcateg = new BankCateg($db);
		$bankcateg->label = GETPOST('label');
		$bankcateg->create($user);
	}
}

if ($categid) {
	$bankcateg = new BankCateg($db);

	if ($bankcateg->fetch($categid) > 0) {
		//Update category
		if (GETPOST('update') && $label) {
			$bankcateg->label = $label;
			$bankcateg->update($user);
		}
		//Delete category
		if ($action == 'delete') {
			$bankcateg->delete($user);
		}
	}
}


/*
 * View
 */

llxHeader();


print load_fiche_titre($langs->trans("RubriquesTransactions"), '', 'object_category');

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
/*print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
*/

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td><td colspan="2">'.$langs->trans("Label").'</td>';
print "</tr>\n";

// Line to add category
if ($action != 'edit')
{
	print '<tr class="oddeven">';
	print '<td>&nbsp;</td><td><input name="label" type="text" size="45"></td>';
	print '<td class="center"><input type="submit" name="add" class="button" value="'.$langs->trans("Add").'"></td>';
	print '</tr>';
}


$sql = "SELECT rowid, label";
$sql .= " FROM ".MAIN_DB_PREFIX."bank_categ";
$sql .= " WHERE entity = ".$conf->entity;
$sql .= " ORDER BY label";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0; $total = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($result);

		print '<tr class="oddeven">';
		print '<td><a href="'.DOL_URL_ROOT.'/compta/bank/budget.php?bid='.$objp->rowid.'">'.$objp->rowid.'</a></td>';
		if (GETPOST('action', 'aZ09') == 'edit' && GETPOST("categid") == $objp->rowid)
		{
			print "<td colspan=2>";
			print '<input type="hidden" name="categid" value="'.$objp->rowid.'">';
			print '<input name="label" type="text" size=45 value="'.$objp->label.'">';
			print '<input type="submit" name="update" class="button" value="'.$langs->trans("Edit").'">';
			print "</td>";
		}
		else
		{
			print "<td>".$objp->label."</td>";
			print '<td class="center">';
			print '<a class="editfielda reposition marginleftonly marginrightonly" href="'.$_SERVER["PHP_SELF"].'?categid='.$objp->rowid.'&amp;action=edit">'.img_edit().'</a>';
			print '<a class="marginleftonly" href="'.$_SERVER["PHP_SELF"].'?categid='.$objp->rowid.'&amp;action=delete">'.img_delete().'</a>';
			print '</td>';
		}
		print "</tr>";
		$i++;
	}
	$db->free($result);
}

print '</table>';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
