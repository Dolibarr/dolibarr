<?php
/* Copyright (C) 2011-2014		Juanjo Menent <jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	    \file       htdocs/compta/localtax/list.php
 *      \ingroup    tax
 *		\brief      List of IRPF payments
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/localtax/class/localtax.class.php';

// Load translation files required by the page
$langs->load("compta");

$limit = GETPOSTINT('limit');

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'tax', '', '', 'charges');
$ltt = GETPOSTINT("localTaxType");
$mode = GETPOST('mode', 'alpha');


/*
 * View
 */

llxHeader();

$localtax_static = new Localtax($db);

$url = DOL_URL_ROOT.'/compta/localtax/card.php?action=create&localTaxType='.$ltt;
if (!empty($socid)) {
	$url .= '&socid='.$socid;
}
$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?localTaxType='.$ltt.'&mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?localTaxType='.$ltt.'&mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitleSeparator();
$newcardbutton .= dolGetButtonTitle($langs->trans('NewLocalTaxPayment', ($ltt + 1)), '', 'fa fa-plus-circle', $url, '', $user->hasRight('tax', 'charges', 'creer'));

print load_fiche_titre($langs->transcountry($ltt == 2 ? "LT2Payments" : "LT1Payments", $mysoc->country_code), $newcardbutton, 'title_accountancy');

$sql = "SELECT rowid, amount, label, f.datev, f.datep";
$sql .= " FROM ".MAIN_DB_PREFIX."localtax as f ";
$sql .= " WHERE f.entity = ".$conf->entity." AND localtaxtype = ".((int) $ltt);
$sql .= " ORDER BY datev DESC";

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;
	$total = 0;

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td class="nowrap" align="left">'.$langs->trans("Ref").'</td>';
	print "<td>".$langs->trans("Label")."</td>";
	print "<td>".$langs->trans("PeriodEndDate")."</td>";
	print '<td class="nowrap" align="left">'.$langs->trans("DatePayment").'</td>';
	print '<td class="right">'.$langs->trans("PayedByThisPayment").'</td>';
	print "</tr>\n";

	$savnbfield = 5;

	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$obj = $db->fetch_object($result);

		$localtax_static->label = $obj->label;
		$localtax_static->id = $obj->rowid;
		$localtax_static->ref = $obj->rowid;
		$localtax_static->datev = $obj->datev;
		$localtax_static->datep = $obj->datep;
		$localtax_static->amount = $obj->amount;

		$total += $obj->amount;

		if ($mode == 'kanban') {
			if ($i == 0) {
				print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
				print '<div class="box-flex-container kanban">';
			}
			// Output Kanban
			print $localtax_static->getKanbanView('', array('selected' => in_array($object->id, $arrayofselected)));
			if ($i == ($imaxinloop - 1)) {
				print '</div>';
				print '</td></tr>';
			}
		} else {
			print '<tr class="oddeven">';
			print "<td>".$localtax_static->getNomUrl(1)."</td>\n";
			print "<td>".dol_trunc($obj->label, 40)."</td>\n";
			print '<td class="left">'.dol_print_date($db->jdate($obj->datev), 'day')."</td>\n";
			print '<td class="left">'.dol_print_date($db->jdate($obj->datep), 'day')."</td>\n";

			print '<td class="right nowraponall"><span class="amount">'.price($obj->amount).'</span></td>';
			print "</tr>\n";
		}
		$i++;
	}
	print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Total").'</td>';
	print '<td class="right"><span class="amount">'.price($total).'</span></td></tr>';

	print "</table>";
	print '</div>';

	$db->free($result);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
