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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/admin/triggers.php
 *       \brief      Page to view triggers
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

// Load translation files required by the page
$langs->load("admin");

if (!$user->admin) {
	accessforbidden();
}

$sortfield = 'file';
$sortorder = 'ASC';


/*
 * Action
 */

// None


/*
 * View
 */

llxHeader("", "");

$form = new Form($db);

print load_fiche_titre($langs->trans("TriggersAvailable"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("TriggersDesc")."</span><br>";
print "<br>\n";


$interfaces = new Interfaces($db);
$triggers = $interfaces->getTriggersList();
$param = ''; $align = '';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder">';
print '<tr class="liste_titre">';
print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], 'none', "", $param, '', $sortfield, $sortorder, '', 1)."\n";
print getTitleFieldOfList($langs->trans("File"), 0, $_SERVER["PHP_SELF"], 'file', "", $param, ($align ? 'align="'.$align.'"' : ''), $sortfield, $sortorder, '', 1)."\n";
print getTitleFieldOfList($langs->trans("Active"), 0, $_SERVER["PHP_SELF"], 'active', "", $param, 'align="center"', $sortfield, $sortorder, '', 1)."\n";
print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], 'none', "", $param, ($align ? 'align="'.$align.'"' : ''), $sortfield, $sortorder, '', 1)."\n";
print '</tr>';

foreach ($triggers as $trigger) {
	print '<tr class="oddeven">';
	print '<td class=" width="32">'.$trigger['picto'].'</td>';
	print '<td>'.$trigger['file'].'</td>';
	print '<td class="center">'.$trigger['status'].'</td>';
	print '<td>';
	$text = $trigger['info'];
	$text .= "<br>\n<strong>".$langs->trans("File")."</strong>:<br>\n".$trigger['relpath'];
	//$text.="\n".$langs->trans("ExternalModule",$trigger['isocreorexternal']);
	print $form->textwithpicto('', $text);
	print '</td>';
	print '</tr>';
}

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
