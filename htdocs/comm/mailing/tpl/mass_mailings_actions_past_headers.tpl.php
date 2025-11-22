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
// $project_id	== 0 - all possible mass mailings will be shown, >= 1 - only mass mailings assigned to this project will be shown

print '<!-- Begin mass_mailings_actions_past_headers.tpl -->';

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = "&search_all=".urlencode($search_all);
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_ref != '') {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_messtype != '') {
	$param .= '&search_type='.urlencode($search_messtype);
}
if ($search_refproject != '') {
	$param .= '&search_refproject='.urlencode($search_refproject);
}
if ($search_project != '') {
	$param .= '&search_project='.urlencode($search_project);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

if ($filteremail) {
	$param .= '&filteremail='.urlencode($filteremail);
}
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// List of mass actions available
$arrayofmassactions = array(
	//'validate'=>img_picto('', 'check', 'class="pictofixedwidth"').$langs->trans("Validate"),
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

$newcardbutton = '';
if ($user->hasRight('mailing', 'creer')) {
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewMailing'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/mailing/card.php?action=create');
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'object_email', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation or email presend form)
$topicmail = "SendMailingRef";
$modelmail = "mailing";
$objecttmp = new Mailing($db);
$trackid = 'mailing'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($search_all) {
	$setupstring = '';
	foreach ($fieldstosearchall as $key => $val) {
		$fieldstosearchall[$key] = $langs->trans($val);
		$setupstring .= $key."=".$val.";";
	}
	print '<!-- Search done like if MYOBJECT_QUICKSEARCH_ON_FIELDS = '.$setupstring.' -->'."\n";
	print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $search_all).implode(', ', $fieldstosearchall).'</div>'."\n";
}

$moreforfilter = '';

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

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

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);
	if (empty($obj)) {
		break; // Should not happen
	}

	$object->id = $obj->rowid;
	$object->ref = $obj->rowid;

	$projectstatic->id = $obj->project_id;
	$projectstatic->ref = $obj->project_ref;
	$projectstatic->title = $obj->project_label;

	// Show here line of result
	print '<tr data-rowid="'.$object->id.'" class="oddeven row-with-select">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Ref
	print '<td>';
	print $object->getNomUrl(1);
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Message type
	if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
		print '<td>';
		print dol_escape_htmltag($obj->messtype);
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Title
	print '<td class="tdoverflowmax200" title="'.dolPrintHTMLForAttribute($obj->title).'">';
	$savref = $object->ref;
	$object->ref = $obj->title;
	print $object->getNomUrl(0);
	$object->ref = $savref;
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Topic
	print '<td class="tdoverflowmax200" title="'.dolPrintHTMLForAttribute($obj->subject).'">';
	print $obj->subject;
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Date creation
	print '<td class="center">';
	print dol_print_date($db->jdate($obj->datec), 'day');
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Project
	print '<td class="nowraponall">';
	print '<a href="/projet/card.php?id='.((int) $projectstatic->id).'">'.dol_escape_htmltag($projectstatic->title).'</a>';
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Nb of email
	if (!$filteremail) {
		print '<td class="center nowraponall">';
		$nbemail = $obj->nbemail;
		/*if ($obj->status != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail)
		{
			$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
			print $form->textwithpicto($nbemail,$text,1,'warning');
		}
		else
		{
			print $nbemail;
		}*/
		print $nbemail;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Last send
	print '<td class="nowrap center">'.dol_print_date($db->jdate($obj->date_envoi), 'day').'</td>';
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Status
	print '<td class="nowrap center">';
	if ($filteremail) {
		print $object::libStatutDest($obj->sendstatut, 2);
	} else {
		print $object->LibStatut($obj->status, 5);
	}
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	print '</tr>'."\n";

	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if (empty($num)) {
	$colspan = $savnbfield;
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";

print '<!-- End mass_mailings_actions_past_headers.tpl -->';

