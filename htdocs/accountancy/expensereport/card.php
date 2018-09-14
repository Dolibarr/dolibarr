<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2013-2017	Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2013-2014	Olivier Geffroy			<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014	Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
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
 *
 */
/**
 * \file		htdocs/accountancy/supplier/card.php
 * \ingroup		Advanced accountancy
 * \brief		Card expense report ventilation
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("bills","accountancy","trips"));

$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$codeventil = GETPOST('codeventil');
$id = GETPOST('id');

// Security check
if ($user->societe_id > 0)
	accessforbidden();


/*
 * Actions
 */

if ($action == 'ventil' && $user->rights->accounting->bind->write)
{
	if (! $cancel)
	{
		if ($codeventil < 0) $codeventil = 0;

		$sql = " UPDATE " . MAIN_DB_PREFIX . "expensereport_det";
		$sql .= " SET fk_code_ventilation = " . $codeventil;
		$sql .= " WHERE rowid = " . $id;

		$resql = $db->query($sql);
		if (! $resql) {
			setEventMessages($db->lasterror(), null, 'errors');
		}
		else
		{
			setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
			if ($backtopage)
			{
				header("Location: ".$backtopage);
				exit();
			}
		}
	} else {
		header("Location: ./lines.php");
		exit();
	}
}



/*
 * View
 */
llxHeader("", $langs->trans('FicheVentilation'));

if ($cancel == $langs->trans("Cancel")) {
	$action = '';
}

// Create
$form = new Form($db);
$expensereport_static = new ExpenseReport($db);
$formaccounting = new FormAccounting($db);

if (! empty($id)) {
	$sql = "SELECT er.ref, er.rowid as facid, erd.fk_c_type_fees, erd.comments, erd.rowid, erd.fk_code_ventilation,";
	$sql .= " f.id as type_fees_id, f.code as type_fees_code, f.label as type_fees_label,";
	$sql .= " aa.account_number, aa.label";
	$sql .= " FROM " . MAIN_DB_PREFIX . "expensereport_det as erd";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_fees as f ON f.id = erd.fk_c_type_fees";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accounting_account as aa ON erd.fk_code_ventilation = aa.rowid";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "expensereport as er ON er.rowid = erd.fk_expensereport";
	$sql .= " WHERE er.fk_statut > 0 AND erd.rowid = " . $id;
	$sql .= " AND er.entity IN (" . getEntity('expensereport', 0) . ")";     // We don't share object for accountancy

	dol_syslog("/accounting/expensereport/card.php sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);

	if ($result) {
		$num_lines = $db->num_rows($result);
		$i = 0;

		if ($num_lines) {
			$objp = $db->fetch_object($result);

			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="ventil">';
			print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($backtopage).'">';

			print load_fiche_titre($langs->trans('ExpenseReportsVentilation'), '', 'title_setup');

			dol_fiche_head();

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td class="titlefield">' . $langs->trans("ExpenseReport") . '</td>';
			$expensereport_static->ref = $objp->ref;
			$expensereport_static->id = $objp->erid;
			print '<td>' . $expensereport_static->getNomUrl(1) . '</td>';
			print '</tr>';

			print '<tr><td>' . $langs->trans("Line") . '</td>';
			print '<td>' . stripslashes(nl2br($objp->rowid)) . '</td></tr>';

			print '<tr><td>' . $langs->trans("Description") . '</td>';
			print '<td>' . stripslashes(nl2br($objp->comments)) . '</td></tr>';

			print '<tr><td>' . $langs->trans("TypeFees") . '</td>';
			print '<td>' . ($langs->trans($objp->type_fees_code) == $objp->type_fees_code ? $objp->type_fees_label : $langs->trans(($objp->type_fees_code))) . '</td>';

			print '<tr><td>' . $langs->trans("Account") . '</td><td>';
			print $formaccounting->select_account($objp->fk_code_ventilation, 'codeventil', 1);
			print '</td></tr>';
			print '</table>';

			dol_fiche_end();

			print '<div class="center">';
			print '<input class="button" type="submit" value="' . $langs->trans("Save") . '">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input class="button" type="submit" name="cancel" value="' . $langs->trans("Cancel") . '">';
			print '</div>';

			print '</form>';
		} else {
			print "Error";
		}
	} else {
		print "Error";
	}
} else {
	print "Error ID incorrect";
}

// End of page
llxFooter();
$db->close();
