<?php
/* Copyright (C) 2012      Mikael Carlavan        <contact@mika-carl.fr>
 * Copyright (C) 2017      ATM Consulting         <contact@atm-consulting.fr>
 * Copyright (C) 2017      Pierre-Henry Favre     <phf@atm-consulting.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *		\brief      Page to display expense tax ik
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport_ik.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "trips", "errors", "other", "dict"));

if (!$user->admin) accessforbidden();

//Init error
$error = false;
$message = false;

$action = GETPOST('action', 'alpha');
$id = GETPOST('id', 'int');
$offset = GETPOST('offset', 'int');
$coef = GETPOST('coef', 'int');

$fk_c_exp_tax_cat = GETPOST('fk_c_exp_tax_cat');
$fk_range = GETPOST('fk_range');

if ($action == 'updateik')
{
	$expIk = new ExpenseReportIk($db);
	if ($id > 0)
	{
		$result = $expIk->fetch($id);
		if ($result < 0) dol_print_error('', $expIk->error, $expIk->errors);
	}

	$expIk->setValues($_POST);
	$result = $expIk->create($user);

	if ($result > 0) setEventMessages('SetupSaved', null, 'mesgs');

	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}
elseif ($action == 'delete') // TODO add confirm
{
	$expIk = new ExpenseReportIk($db);
	if ($id > 0)
	{
		$result = $expIk->fetch($id);
		if ($result < 0) dol_print_error('', $expIk->error, $expIk->errors);

		$expIk->delete($user);
	}


	header('Location: '.$_SERVER['PHP_SELF']);
	exit;
}

$rangesbycateg = ExpenseReportIk::getAllRanges();

/*
 * View
 */

llxHeader('', $langs->trans("ExpenseReportsSetup"));

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ExpenseReportsIkSetup"), $linkback, 'title_setup');

$head = expensereport_admin_prepare_head();
dol_fiche_head($head, 'expenseik', $langs->trans("ExpenseReportsIk"), -1, 'trip');

echo $langs->trans('ExpenseReportIkDesc');

echo '<form action="'.$_SERVER['PHP_SELF'].'" method="post">';

if ($action == 'edit')
{
	echo '<input type="hidden" name="id" value="'.$id.'" />';
	echo '<input type="hidden" name="fk_c_exp_tax_cat" value="'.$fk_c_exp_tax_cat.'" />';
	echo '<input type="hidden" name="fk_range" value="'.$fk_range.'" />';
	echo '<input type="hidden" name="action" value="updateik" />';
}

echo '<input type="hidden" name="token" value="'.newToken().'" />';

echo '<table class="noborder centpercent">';

foreach ($rangesbycateg as $fk_c_exp_tax_cat => $Tab)
{
	$title = ($Tab['active'] == 1) ? $langs->trans($Tab['label']) : $form->textwithpicto($langs->trans($Tab['label']), $langs->trans('expenseReportCatDisabled'), 1, 'help', '', 0, 3);
	echo '<tr class="liste_titre">';
	echo '<td>'.$title.'</td>';
	echo '<td>'.$langs->trans('expenseReportOffset').'</td>';
	echo '<td>'.$langs->trans('expenseReportCoef').'</td>';
	echo '<td>'.$langs->trans('expenseReportTotalForFive').'</td>';
	echo '<td>&nbsp;</td>';
	echo '</tr>';

	if ($Tab['active'] == 0) continue;

	$tranche = 1;

	foreach ($Tab['ranges'] as $k => $range)
	{
		if (isset($Tab['ranges'][$k + 1])) $label = $langs->trans('expenseReportRangeFromTo', $range->range_ik, ($Tab['ranges'][$k + 1]->range_ik - 1));
		else $label = $langs->trans('expenseReportRangeMoreThan', $range->range_ik);

		if ($range->range_active == 0) $label = $form->textwithpicto($label, $langs->trans('expenseReportRangeDisabled'), 1, 'help', '', 0, 3);

		echo '<tr class="oddeven">';

		// Label
		echo '<td width="20%"><b>['.$langs->trans('RangeNum', $tranche++).']</b> - '.$label.'</td>';

		// Offset
		echo '<td width="20%">';
		if ($action == 'edit' && $range->ik->id == $id && $range->rowid == $fk_range && $range->fk_c_exp_tax_cat == $fk_c_exp_tax_cat) echo '<input type="text" name="offset" value="'.$range->ik->offset.'" />';
		else echo $range->ik->offset;
		echo '</td>';
		// Coef
		echo '<td width="20%">';
		if ($action == 'edit' && $range->ik->id == $id && $range->rowid == $fk_range && $range->fk_c_exp_tax_cat == $fk_c_exp_tax_cat) echo '<input type="text" name="coef" value="'.$range->ik->coef.'" />';
		else echo ($range->ik->id > 0 ? $range->ik->coef : $langs->trans('expenseReportCoefUndefined'));
		echo '</td>';

		// Total for one
		echo '<td width="30%">'.$langs->trans('expenseReportPrintExample', price($range->ik->offset + 5 * $range->ik->coef)).'</td>';

		// Action
		echo '<td class="right">';
		if ($range->range_active == 1)
		{
			if ($action == 'edit' && $range->ik->id == $id && $range->rowid == $fk_range && $range->fk_c_exp_tax_cat == $fk_c_exp_tax_cat)
			{
				echo '<input id="" class="button" name="save" value="'.$langs->trans('Save').'" type="submit" />';
				echo '<input class="button" value="'.$langs->trans('Cancel').'" onclick="javascript:history.go(-1)" type="button" />';
			}
			else
			{
				echo '<a href="'.$_SERVER['PHP_SELF'].'?action=edit&id='.$range->ik->id.'&fk_c_exp_tax_cat='.$range->fk_c_exp_tax_cat.'&fk_range='.$range->rowid.'">'.img_edit().'</a>';
				if (!empty($range->ik->id)) echo '<a href="'.$_SERVER['PHP_SELF'].'?action=delete&id='.$range->ik->id.'">'.img_delete().'</a>';
				// TODO add delete link
			}
		}
		echo '</td>';

		echo '</tr>';
	}
}

echo '</table>';
echo '</form>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
