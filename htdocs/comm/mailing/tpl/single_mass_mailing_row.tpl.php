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

print '<!-- Begin single_mass_mailing_row.tpl -->';

// Loops over the records in $arrayMailings
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $arrayMailings[$i];
	if (empty($obj)) {
		break; // Should not happen
	}

	$object->id = $obj['rowid'];
	$object->ref = $obj['rowid'];
	$object->messtype = $obj['messtype'];
	$object->title = $obj['title'];
	$object->sujet = $obj['subject'];
	$object->nbemail = $obj['nbemail'];
	$object->status = $obj['status'];
	$object->datec = $obj['datec'];
	$object->date_envoi = $obj['date_envoi'];

	$projectstatic = new Project($db);
	$projectstatic->id = $obj['project_id'];
	$projectstatic->ref = $obj['project_ref'];
	$projectstatic->title = $obj['project_label'];

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
	print '<td class="tdoverflowmax200" title="'.dolPrintHTMLForAttribute($object->title).'">';
	$savref = $object->ref;
	$object->ref = $object->title;
	print $object->getNomUrl(0);
	$object->ref = $savref;
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Topic
	print '<td class="tdoverflowmax200" title="'.dolPrintHTMLForAttribute($object->sujet).'">';
	print $object->sujet;
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Date creation
	print '<td class="center">';
	print dol_print_date($object->datec, 'day');
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
		/*if ($obj->status != 3 && !empty($conf->global->MAILING_LIMIT_SENDBYWEB) && $conf->global->MAILING_LIMIT_SENDBYWEB < $$object->nbemail)
		{
			$text=$langs->trans('LimitSendingEmailing',$conf->global->MAILING_LIMIT_SENDBYWEB);
			print $form->textwithpicto($$object->nbemail,$text,1,'warning');
		}
		else
		{
			print $$object->nbemail;
		}*/
		print $object->nbemail;
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Last send
	print '<td class="nowrap center">'.dol_print_date($object->date_envoi, 'day').'</td>';
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Status
	print '<td class="nowrap center">';
	if ($filteremail) {
		print $object::libStatutDest($obj['sendstatut'], 2);
	} else {
		print $object->LibStatut($object->status, 5);
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
print '<!-- End single_mass_mailing_row.tpl -->';
