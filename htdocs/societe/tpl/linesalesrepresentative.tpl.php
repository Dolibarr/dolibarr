<?php
/* Copyright (C) 2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// Protection to avoid direct call of template
if (empty($conf) || !is_object($conf)) {
	print "Error, template page can't be called as URL";
	exit;
}

print '<!-- linesalesrepresentative.tpl.php -->';

// Sale representative
print '<tr><td>';
print '<table class="nobordernopadding centpercent"><tr><td>';
print $langs->trans('SalesRepresentatives');
print '</td>';
if ($action != 'editsalesrepresentatives' && $user->hasRight('societe', 'creer')) {
	print '<td class="right">';
	print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?action=editsalesrepresentatives&token='.newToken().'&socid='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a>';
	print '</td>';
}
print '</tr></table>';
print '</td><td colspan="3">';

if ($action == 'editsalesrepresentatives') {
	print '<form method="post" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="action" value="set_salesrepresentatives" />';
	print '<input type="hidden" name="token" value="'.newToken().'" />';
	print '<input type="hidden" name="socid" value="'.$object->id.'" />';
	$userlist = $form->select_dolusers('', '', 0, null, 0, '', '', 0, 0, 0, '', 0, '', '', 0, 1);
	$arrayselected = GETPOST('commercial', 'array');
	if (empty($arrayselected)) {
		$arrayselected = $object->getSalesRepresentatives($user, 1);
	}
	print $form->multiselectarray('commercial', $userlist, $arrayselected, null, null, null, null, "90%");
	print '<input type="submit" class="button valignmiddle smallpaddingimp" value="'.$langs->trans("Modify").'" />';
	print '</form>';
} else {
	$listsalesrepresentatives = $object->getSalesRepresentatives($user);
	$nbofsalesrepresentative = count($listsalesrepresentatives);
	if ($nbofsalesrepresentative > 0 && is_array($listsalesrepresentatives)) {
		$userstatic = new User($db);
		foreach ($listsalesrepresentatives as $val) {
			$userstatic->id = $val['id'];
			$userstatic->login = $val['login'];
			$userstatic->lastname = $val['lastname'];
			$userstatic->firstname = $val['firstname'];
			$userstatic->status = $val['statut'];
			$userstatic->photo = $val['photo'];
			$userstatic->email = $val['email'];
			$userstatic->office_phone = $val['office_phone'];
			$userstatic->user_mobile = $val['user_mobile'];
			$userstatic->job = $val['job'];
			$userstatic->entity = $val['entity'];
			$userstatic->gender = $val['gender'];
			print $userstatic->getNomUrl(-1, '', 0, 0, ($nbofsalesrepresentative > 1 ? 16 : (empty($conf->dol_optimize_smallscreen) ? 24 : 20)));
			print ' ';
		}
	} else {
		print '<span class="opacitymedium">'.$langs->trans("NoSalesRepresentativeAffected").'</span>';
	}
	print '</td></tr>';
}
