<?php
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2017 Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
 * \file 	htdocs/accountancy/customer/card.php
 * \ingroup Accountancy (Double entries)
 * \brief 	Card customer ventilation
 */
require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';

// Load translation files required by the page
$langs->loadLangs(array("bills", "accountancy"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$backtopage = GETPOST('backtopage', 'alpha');

$codeventil = GETPOST('codeventil', 'int');
$id = GETPOST('id', 'int');

// Security check
if ($user->socid > 0)
	accessforbidden();


/*
 * Actions
 */

if ($action == 'ventil' && $user->rights->accounting->bind->write)
{
	if (!$cancel)
	{
		if ($codeventil < 0) $codeventil = 0;

		$sql = " UPDATE ".MAIN_DB_PREFIX."facturedet";
		$sql .= " SET fk_code_ventilation = ".$codeventil;
		$sql .= " WHERE rowid = ".$id;

		$resql = $db->query($sql);
		if (!$resql) {
			setEventMessages($db->lasterror(), null, 'errors');
		} else {
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

/*
 * Create
 */
$form = new Form($db);
$facture_static = new Facture($db);
$formaccounting = new FormAccounting($db);

if (!empty($id)) {
	$sql = "SELECT f.ref, f.rowid as facid, l.fk_product, l.description, l.price,";
	$sql .= " l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice, p.accountancy_code_sell as code_sell,";
	$sql .= " l.fk_code_ventilation, aa.account_number, aa.label";
	$sql .= " FROM ".MAIN_DB_PREFIX."facturedet as l";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = l.fk_product";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."accounting_account as aa ON l.fk_code_ventilation = aa.rowid";
	$sql .= " INNER JOIN ".MAIN_DB_PREFIX."facture as f ON f.rowid = l.fk_facture";
	$sql .= " WHERE f.fk_statut > 0 AND l.rowid = ".$id;
	$sql .= " AND f.entity IN (".getEntity('invoice', 0).")"; // We don't share object for accountancy

	dol_syslog("/accounting/customer/card.php sql=".$sql, LOG_DEBUG);
	$result = $db->query($sql);

	if ($result) {
		$num_lines = $db->num_rows($result);
		$i = 0;

		if ($num_lines) {
			$objp = $db->fetch_object($result);

			print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="post">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="ventil">';
			print '<input type="hidden" name="backtopage" value="'.dol_escape_htmltag($backtopage).'">';

			print load_fiche_titre($langs->trans('CustomersVentilation'), '', 'title_accountancy');

			print dol_get_fiche_head();

			print '<table class="border centpercent">';

			// Ref facture
			print '<tr><td>'.$langs->trans("Invoice").'</td>';
			$facture_static->ref = $objp->ref;
			$facture_static->id = $objp->facid;
			print '<td>'.$facture_static->getNomUrl(1).'</td>';
			print '</tr>';

			print '<tr><td width="20%">'.$langs->trans("Line").'</td>';
			print '<td>'.nl2br($objp->description).'</td></tr>';
			print '<tr><td width="20%">'.$langs->trans("Account").'</td><td>';
			print $formaccounting->select_account($objp->fk_code_ventilation, 'codeventil', 1);
			print '</td></tr>';
			print '</table>';

			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
			print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
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
