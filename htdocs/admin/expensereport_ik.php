<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 * Copyright (C) 2017      ATM Consulting         <contact@atm-consulting.fr>
 * Copyright (C) 2017      Pierre-Henry Favre     <phf@atm-consulting.fr>
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
 *      \file       htdocs/admin/expensereport_ik.php
 *		\ingroup    expensereport
 *		\brief      Page to display expense tax ik. Used when MAIN_USE_EXPENSE_IK is set.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "trips", "errors", "other", "dict"));

$error = 0;

$action = GETPOST('action', 'aZ09');

$id = GETPOSTINT('id');
$ikoffset = (float) price2num(GETPOST('ikoffset', 'alpha'));
$coef = (float) price2num(GETPOST('coef', 'alpha'));
$fk_c_exp_tax_cat = GETPOSTINT('fk_c_exp_tax_cat');
$fk_range = GETPOSTINT('fk_range');

$expIk = new ExpenseReportIk($db);

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'updateik') {
	if ($id > 0) {
		$result = $expIk->fetch($id);
		if ($result < 0) {
			dol_print_error(null, $expIk->error, $expIk->errors);
		}
	}

	$expIk->coef = $coef;
	$expIk->ikoffset = $ikoffset;
	$expIk->fk_c_exp_tax_cat = $fk_c_exp_tax_cat;
	$expIk->fk_range = $fk_range;

	if ($expIk->id > 0) {
		$result = $expIk->update($user);
	} else {
		$result = $expIk->create($user);
	}
	if ($result > 0) {
		setEventMessages('SetupSaved', null, 'mesgs');

		header('Location: '.$_SERVER['PHP_SELF']);
		exit;
	} else {
		setEventMessages($expIk->error, $expIk->errors, 'errors');
	}
} elseif ($action == 'delete') { // TODO add confirm
	if ($id > 0) {
		$result = $expIk->fetch($id);
		if ($result < 0) {
			dol_print_error(null, $expIk->error, $expIk->errors);
		}

		$expIk->delete($user);
	}

	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

$rangesbycateg = $expIk->getAllRanges();


/*
 * View
 */

llxHeader('', $langs->trans("ExpenseReportsSetup"), '', '', 0, 0, '', '', '', 'mod-admin page-expensereport_ik');

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExpenseReportsSetup"), $linkback, 'title_setup');

$head = expensereport_admin_prepare_head();
print dol_get_fiche_head($head, 'expenseik', $langs->trans("ExpenseReportsIk"), -1, 'trip');

echo '<span class="opacitymedium">'.$langs->trans('ExpenseReportIkDesc').'</span>';
print '<br><br>';

echo '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">';
echo '<input type="hidden" name="token" value="'.newToken().'" />';

if ($action == 'edit') {
	echo '<input type="hidden" name="id" value="'.$id.'" />';
	echo '<input type="hidden" name="fk_c_exp_tax_cat" value="'.$fk_c_exp_tax_cat.'" />';
	echo '<input type="hidden" name="fk_range" value="'.$fk_range.'" />';
	echo '<input type="hidden" name="action" value="updateik" />';
}

echo '<table class="noborder centpercent">';

foreach ($rangesbycateg as $fk_c_exp_tax_cat => $Tab) {
	$title = ($Tab['active'] == 1) ? $langs->trans($Tab['label']) : $form->textwithpicto($langs->trans($Tab['label']), $langs->trans('expenseReportCatDisabled'), 1, 'help', '', 0, 3);
	echo '<tr class="liste_titre">';
	echo '<td>'.$title.'</td>';
	echo '<td>'.$langs->trans('expenseReportOffset').'</td>';
	echo '<td>'.$langs->trans('expenseReportCoef').'</td>';
	echo '<td>'.$langs->trans('expenseReportTotalForFive').'</td>';
	echo '<td>&nbsp;</td>';
	echo '</tr>';

	if ($Tab['active'] == 0) {
		continue;
	}

	$tranche = 1;

	foreach ($Tab['ranges'] as $k => $range) {
		if (isset($Tab['ranges'][$k + 1])) {
			$label = $langs->trans('expenseReportRangeFromTo', $range->range_ik, ($Tab['ranges'][$k + 1]->range_ik - 1));
		} else {
			$label = $langs->trans('expenseReportRangeMoreThan', $range->range_ik);
		}

		if ($range->range_active == 0) {
			$label = $form->textwithpicto($label, $langs->trans('expenseReportRangeDisabled'), 1, 'help', '', 0, 3);
		}

		echo '<tr class="oddeven">';

		// Label
		echo '<td class="nowraponall"><b>['.$langs->trans('RangeNum', $tranche++).']</b> - '.$label.'</td>';

		// Offset
		echo '<td class="nowraponall">';
		if ($action == 'edit' && $range->ik->id == $id && $range->rowid == $fk_range && $range->fk_c_exp_tax_cat == $fk_c_exp_tax_cat) {
			echo '<input type="text" class="maxwidth100" name="ikoffset" value="'.$range->ik->ikoffset.'" />';
		} else {
			echo $range->ik->ikoffset;
		}
		echo '</td>';

		// Coef
		echo '<td class="nowraponall">';
		if ($action == 'edit' && $range->ik->id == $id && $range->rowid == $fk_range && $range->fk_c_exp_tax_cat == $fk_c_exp_tax_cat) {
			echo '<input type="text" class="maxwidth100" name="coef" value="'.$range->ik->coef.'" />';
		} else {
			echo($range->ik->id > 0 ? $range->ik->coef : $langs->trans('expenseReportCoefUndefined'));
		}
		echo '</td>';

		// Total for one
		echo '<td class="nowraponall">'.$langs->trans('expenseReportPrintExample', price($range->ik->ikoffset + 5 * $range->ik->coef)).'</td>';

		// Action
		echo '<td class="right">';
		if ($range->range_active == 1) {
			if ($action == 'edit' && $range->ik->id == $id && $range->rowid == $fk_range && $range->fk_c_exp_tax_cat == $fk_c_exp_tax_cat) {
				echo '<input id="" class="button button-save" name="save" value="'.$langs->trans("Save").'" type="submit" />';
				echo '<input class="button button-cancel" value="'.$langs->trans("Cancel").'" onclick="history.go(-1)" type="button" />';
			} else {
				echo '<a class="editfielda marginrightonly paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'&id='.$range->ik->id.'&fk_c_exp_tax_cat='.$range->fk_c_exp_tax_cat.'&fk_range='.$range->rowid.'">'.img_edit().'</a>';
				if (!empty($range->ik->id)) {
					echo '<a class="paddingleft paddingright" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&id='.$range->ik->id.'">'.img_delete().'</a>';
				}
				// TODO add delete link
			}
		}
		echo '</td>';

		echo '</tr>';
	}
}

echo '</table>';
echo '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
