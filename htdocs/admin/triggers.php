<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/admin/triggers.php
 *       \brief      Page to view triggers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

$langs->load("admin");

if (!$user->admin) accessforbidden();

/*
 * Action
 */

// None


/*
 * View
 */

llxHeader("","");

$form = new Form($db);

print load_fiche_titre($langs->trans("TriggersAvailable"),'','title_setup');

print $langs->trans("TriggersDesc")."<br>";
print "<br>\n";

$template_dir = DOL_DOCUMENT_ROOT.'/core/tpl/';

$interfaces = new Interfaces($db);
$triggers = $interfaces->getTriggersList();

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder">
<tr class="liste_titre">
<td colspan="2">'.$langs->trans("File").'</td>
<td align="center">'.$langs->trans("Active").'</td>
<td align="center">&nbsp;</td>
</tr>
';

$var=True;
foreach ($triggers as $trigger)
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td valign="top" width="14" align="center">'.$trigger['picto'].'</td>';
	print '<td class="tdtop">'.$trigger['file'].'</td>';
	print '<td valign="top" align="center">'.$trigger['status'].'</td>';
	print '<td class="tdtop">';
	$text=$trigger['info'];
	$text.="<br>\n<strong>".$langs->trans("File")."</strong>:<br>\n".$trigger['relpath'];
	//$text.="\n".$langs->trans("ExternalModule",$trigger['isocreorexternal']);
	print $form->textwithpicto('', $text);
	print '</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

llxFooter();

$db->close();
