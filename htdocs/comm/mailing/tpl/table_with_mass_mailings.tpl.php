<?php
/* 2025-11-22 Copy htdocs/comm/mailing/list.php
 * Copyright (C) 2025  Jon Bendtsen         <jon.bendtsen.github@jonb.dk>
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
 * or see https://www.gnu.org/
 */

// Following var can be set
// $arrayMailings the result of asking the database with sql for mass mailings
// $num the number of results of asking the database with sql for mass mailings

print '<!-- Begin table_with_mass_mailings.tpl -->';

print '<table id="table_with_mass_mailings.tpl" class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre maxwidthsearch center">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
print '<td class="liste_titre">';
print '<input type="text" class="flat maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
print '</td>';
// Message type
if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
	print '<td class="liste_titre">';
	print '<input type="text" class="flat maxwidth50" name="search_messtype" value="'.dol_escape_htmltag($search_messtype).'">';
	print '</td>';
}
// Title
print '<td class="liste_titre">';
print '<input type="text" class="flat maxwidth100 maxwidth50onsmartphone" name="search_title" value="'.dol_escape_htmltag($search_title).'">';
print '</td>';
// Subject
print '<td class="liste_titre">';
print '<input type="text" class="flat maxwidth100 maxwidth50onsmartphone" name="search_subject" value="'.dol_escape_htmltag($search_subject).'">';
print '</td>';
// Creation date
print '<td class="liste_titre">&nbsp;</td>';

// Project
print '<td class="liste_titre">';
print '<input type="text" class="flat maxwidth100 maxwidth50onsmartphone" name="search_project" value="'.dol_escape_htmltag($search_project).'">';
print '</td>';

if (!$filteremail) {
	print '<td class="liste_titre">&nbsp;</td>';
}
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre">&nbsp;</td>';
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "m.rowid", "", $param, "", $sortfield, $sortorder);
$totalarray['nbfield']++;
// Message type
if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
	print_liste_field_titre("Type", $_SERVER["PHP_SELF"], "m.messtype", "", $param, "", $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
print_liste_field_titre("Title", $_SERVER["PHP_SELF"], "m.titre", "", $param, "", $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Subject", $_SERVER["PHP_SELF"], "m.sujet", "", $param, "", $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "m.date_creat", "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;

// Project
print_liste_field_titre("Project", $_SERVER["PHP_SELF"], "project_label", "", $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;

if (!$filteremail) {
	$title = $langs->trans("NbOfEMails");
	if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
		$title .= ' | '.$langs->trans("SMS");
	}
	print_liste_field_titre($title, $_SERVER["PHP_SELF"], "m.nbemail", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!$filteremail) {
	print_liste_field_titre("DateLastSend", $_SERVER["PHP_SELF"], "m.date_envoi", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
} else {
	print_liste_field_titre("DateSending", $_SERVER["PHP_SELF"], "mc.date_envoi", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
print_liste_field_titre("Status", $_SERVER["PHP_SELF"], ($filteremail ? "mc.statut" : "m.statut"), "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList(($mode != 'kanban' ? $selectedfields : ''), 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// include tpl that loops over the records in $arrayMailings
print '<!-- pre include single_mass_mailing_row.tpl -->';
include DOL_DOCUMENT_ROOT.'/comm/mailing/tpl/single_mass_mailing_row.tpl.php';
print '<!-- post include single_mass_mailing_row.tpl -->';


// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if (empty($num)) {
	$colspan = $savnbfield;
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

// WHY do we need the SQL statement and NOW at the very end of the table?
$parameters = array('arrayfields' => $arrayfields, 'sql' => '');
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

print '<!-- End table_with_mass_mailings.tpl -->';

