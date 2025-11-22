<?php
/* Copyright (C) 2014-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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

/**
 * @var CommonObject $object
 * @var DoliDB $db
 * @var Form $form
 * @var Translate $langs
 * @var string $search_user
 * @var string $search_entity
 * @var string $search_datec_start
 * @var string $search_datec_end
 * @var string $search_tms_start
 * @var string $search_tms_end
 * @var string $param
 * @var string $sortfield
 * @var string $sortorder
 * @var int $limit
 * @var mysqli_result $resql
 * @var string $massactionbutton
 * @var string $massaction
 * @var array<int> $arrayofselected
 * @var int $colspan
 *
 * @var int $num
 */

'
@phan-var-force Propal|Contrat|Commande|Facture|Expedition|Delivery|FactureFournisseur|FactureFournisseur|SupplierProposal $object
@phan-var-force array<string,array{label:string,checked?:string,position?:int,help?:string,enabled?:string}> $arrayfields
@phan-var-force int $num
@phan-var-force string $search_user
@phan-var-force string $search_entity
@phan-var-force string $search_datec_start
@phan-var-force string $search_datec_end
@phan-var-force string $search_tms_start
@phan-var-force string $search_tms_end
@phan-var-force int $colspan
';

echo "<!-- BEGIN PHP TEMPLATE apitoken_list.tpl.php -->\n";

print '<table class="noborder centpercent">';

print '<tr class="liste_titre_filter">';

// Action buttons
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

// Token string
// We don't search out tokens because it is encrypted in database
print '<td class="liste_titre"></td>';

// User
if (!empty($arrayfields['u.login']['checked'])) {
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
	print '</td>';
}

// Number of perms
// We don't search out number of perms because it is a string field,
// and we don't want to count into it with sql query
print '<td class="liste_titre"></td>';

// Date creation
if (!empty($arrayfields['oat.datec']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datec_start ? $search_datec_start : -1, 'search_datec_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_datec_end ? $search_datec_end : -1, 'search_datec_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Date modification
if (!empty($arrayfields['oat.tms']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_tms_start ? $search_tms_start : -1, 'search_tms_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'));
	print '</div>';
	print '<div class="nowrapfordate">';
	print $form->selectDate($search_tms_end ? $search_tms_end : -1, 'search_tms_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'));
	print '</div>';
	print '</td>';
}

// Action buttons
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}

print "</tr>";

print '<tr class="liste_titre">';
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<th class="wrapcolumntitle center maxwidthsearch liste_titre">';
	print $form->showCheckAddButtons('checkforselect', 1);
	print '</th>';
}
print '<th class="liste_titre">'.$langs->trans("Token").'</th>';
if (!empty($arrayfields['u.login']['checked'])) {
	// @phan-suppress-next-line PhanTypeInvalidDimOffset
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], 'u.login', '', $param, '', $sortfield, $sortorder);
}
print '<th class="liste_titre right">'.$langs->trans("LastAccess").'</th>';
if (!empty($arrayfields['oat.datec']['checked'])) {
	print_liste_field_titre($arrayfields['oat.datec']['label'], $_SERVER["PHP_SELF"], 'oat.datec', '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!empty($arrayfields['oat.tms']['checked'])) {
	print_liste_field_titre($arrayfields['oat.tms']['label'], $_SERVER["PHP_SELF"], 'oat.tms', '', $param, '', $sortfield, $sortorder, 'center ');
}
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<th class="wrapcolumntitle center maxwidthsearch liste_titre">';
	print $form->showCheckAddButtons('checkforselect', 1);
	print '</th>';
}
print '</tr>';

// List of tokens of user
$i = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
if ($num > 0) {
	while ($i < $imaxinloop) {
		// Compute number of perms
		$obj = $db->fetch_object($resql);

		$useridparam = isset($obj->fk_user) ? $obj->fk_user : $object->id;

		if (isset($obj->fk_user)) {
			$currentuser = new User($db);
			$currentuser->fetch($obj->fk_user);
		} else {
			$currentuser = $object;
		}

		/*
		$numperms = 0;
		if (!empty($obj->rights)) {
			$numperms = count(explode(",", $obj->rights));
		} elseif (!(strlen($obj->rights) == 1 && substr($obj->rights, 0, 1) == 0)) {
			$currentuser->loadRights();
			$numperms = $currentuser->nb_rights;
		}
		*/

		print '<tr class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
		}
		print '<td>';
		print '<a href="'.DOL_URL_ROOT.'/user/api_token/card.php?id='.$useridparam.'&tokenid='.$obj->rowid.'">';
		print dolDecrypt($obj->tokenstring);
		print '</a>';
		print '</td>';
		if (!empty($arrayfields['u.login']['checked'])) {
			print '<td>';
			print '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->fk_user.'">';
			print $currentuser->getNomUrl(1);
			print '</a>';
			print '</td>';
		}
		print '<td class="right">';
		print dol_print_date($db->jdate($obj->lastaccess));
		print '</td>';
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_creation), 'dayhour');
		print '</td>';
		print '<td class="center">';
		print dol_print_date($db->jdate($obj->date_modification), 'dayhour');
		print '</td>';
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) {
				$selected = 0;
				if (in_array($obj->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
		}
		print '</tr>';
		$i++;
	}
} else {
	if (isModEnabled('multicompany')) {
		$colspan++;
	}
	print '<tr class="oddeven"><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
}

print "</table>";
