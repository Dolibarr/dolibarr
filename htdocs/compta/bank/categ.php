<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copytight (C) 2013      Charles-Fr BENKE		<charles.fr@benke.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/compta/bank/categ.php
 *      \ingroup    compta
 *      \brief      Page ajout de categories bancaires
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");

$action=GETPOST('action');

if (!$user->rights->banque->configurer)
  accessforbidden();



/*
 * Add category
 */
if (GETPOST('add'))
{
	if (GETPOST("label"))
	{
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."bank_categ (";
		$sql.= "label";
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$db->escape(GETPOST("label"))."'";
		$sql.= ", ".$conf->entity;
		$sql.= ")";

		dol_syslog("sql=".$sql);
		$result = $db->query($sql);
		if (!$result)
		{
			dol_print_error($db);
		}
	}
}

/*
 * Update category
 */
if (GETPOST('update'))
{
	if (GETPOST("label"))
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."bank_categ ";
		$sql.= "set label='".$db->escape(GETPOST("label"))."'";
		$sql.= " WHERE rowid = '".GETPOST('categid')."'";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("sql=".$sql);
		$result = $db->query($sql);
		if (!$result)
		{
			dol_print_error($db);
		}
	}
}
/*
* Action suppression catégorie
*/
if ($action == 'delete')
{
	if (GETPOST('categid'))
	{
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."bank_categ";
		$sql.= " WHERE rowid = '".GETPOST('categid')."'";
		$sql.= " AND entity = ".$conf->entity;

		dol_syslog("sql=".$sql);
		$result = $db->query($sql);
		if (!$result)
		{
			dol_print_error($db);
		}
	}
}



/*
 * View
 */

llxHeader();


print_fiche_titre($langs->trans("Rubriques"));

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Ref").'</td><td colspan="2">'.$langs->trans("Label").'</td>';
print "</tr>\n";

$sql = "SELECT rowid, label";
$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ";
$sql.= " WHERE entity = ".$conf->entity;
$sql.= " ORDER BY label";

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
		print "<tr ".$bc[$var].">";
		print '<td><a href="'.DOL_URL_ROOT.'/compta/bank/budget.php?bid='.$objp->rowid.'">'.$objp->rowid.'</a></td>';
		if (GETPOST("action") == 'edit' && GETPOST("categid")== $objp->rowid)
		{
			print "<td colspan=2>";
			print '<input type="hidden" name="categid" value="'.$objp->rowid.'">';
			print '<input name="label" type="text" size=45 value="'.$objp->label.'">';
			print '<input type="submit" name="update" class="button" value="'.$langs->trans("Edit").'">';

			print "</td>";
		}
		else
		{
			print "<td >".$objp->label."</td>";
			print '<td style="text-align: center;">';
			print '<a href="'.$_SERVER["PHP_SELF"].'?categid='.$objp->rowid.'&amp;action=edit">'.img_edit().'</a>&nbsp;&nbsp;';
			print '<a href="'.$_SERVER["PHP_SELF"].'?categid='.$objp->rowid.'&amp;action=delete">'.img_delete().'</a></td>';
		}
		print "</tr>";
		$i++;
	}
	$db->free($result);
}

print "</form>";

/*
 * Line to add category
 */
if ($action != 'edit')
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>&nbsp;</td><td><input name="label" type="text" size="45"></td>';
	print '<td align="center"><input type="submit" name="add" class="button" value="'.$langs->trans("Add").'"></td>';
	print '</tr>';
}

print "</table>";

print "</form>";

llxFooter();

$db->close();
?>
