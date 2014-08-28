<?PHP
/* Copyright (C) 2013-2014 Olivier Geffroy		<jeff@jeffinfo.com>
 * Copyright (C) 2013-2014 Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2013-2014 Alexandre Spangaro	<alexandre.spangaro@gmail.com>
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
 */

/**
 * \file		htdocs/accountancy/customer/card.php
 * \ingroup		Accounting Expert
 * \brief		Card customer ventilation
 */

// Dolibarr environment
$res = @include ("../main.inc.php");
if (! $res && file_exists("../main.inc.php"))
	$res = @include ("../main.inc.php");
if (! $res && file_exists("../../main.inc.php"))
	$res = @include ("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php"))
	$res = @include ("../../../main.inc.php");
if (! $res)
	die("Include of main fails");
	
	// Class
dol_include_once("/compta/facture/class/facture.class.php");
dol_include_once("/accountancy/class/html.formventilation.class.php");

// Langs
$langs->load("bills");
$langs->load("accountancy");

$action = GETPOST('action', 'alpha');
$codeventil = GETPOST('codeventil');
$id = GETPOST('id');

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->access)
	accessforbidden();
	
/*
 * Actions
 */

if ($action == 'ventil' && $user->rights->accounting->access) {
	$sql = " UPDATE " . MAIN_DB_PREFIX . "facturedet";
	$sql .= " SET fk_code_ventilation = " . $codeventil;
	$sql .= " WHERE rowid = " . $id;
	
	dol_syslog("/accounting/customer/card.php sql=" . $sql, LOG_DEBUG);
	$resql = $db->query($sql);
	if (! $resql) {
		setEventMessage($db->lasterror(), 'errors');
	}
}

llxHeader("", "", "FicheVentilation");

if ($cancel == $langs->trans("Cancel")) {
	$action = '';
}

/*
 * Create
 */
$form = new Form($db);
$facture_static = new Facture($db);
$formventilation = new FormVentilation($db);

if (! empty($id)) {
	$sql = "SELECT f.facnumber, f.rowid as facid, l.fk_product, l.description, l.price,";
	$sql .= " l.qty, l.rowid, l.tva_tx, l.remise_percent, l.subprice, p.accountancy_code_sell as code_sell,";
	$sql .= " l.fk_code_ventilation, aa.account_number, aa.label";
	$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as l";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON l.fk_code_ventilation = aa.rowid";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture as f ON f.rowid = l.fk_facture";
	$sql .= " WHERE f.fk_statut > 0 AND l.rowid = " . $id;
	
	if (! empty($conf->multicompany->enabled)) {
		$sql .= " AND f.entity = '" . $conf->entity . "'";
	}
	
	dol_syslog("/accounting/customer/card.php sql=" . $sql, LOG_DEBUG);
	$result = $db->query($sql);
	
	if ($result) {
		$num_lines = $db->num_rows($result);
		$i = 0;
		
		if ($num_lines) {
			
			$objp = $db->fetch_object($result);
			
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="ventil">';
			
			print_fiche_titre($langs->trans("Ventilation"));
			
			print '<table class="border" width="100%">';
			
			// Ref facture
			print '<tr><td>' . $langs->trans("Invoice") . '</td>';
			$facture_static->ref = $objp->facnumber;
			$facture_static->id = $objp->facid;
			print '<td>' . $facture_static->getNomUrl(1) . '</td>';
			print '</tr>';
			
			print '<tr><td width="20%">' . $langs->trans("Line") . '</td>';
			print '<td>' . nl2br($objp->description) . '</td></tr>';
			print '<tr><td width="20%">' . $langs->trans("Account") . '</td><td>';
			print $objp->account_number . '-' . $objp->label;
			print '<tr><td width="20%">' . $langs->trans("NewAccount") . '</td><td>';
			print $formventilation->select_account($objp->fk_code_ventilation, 'codeventil', 1);
			print '</td></tr>';
			print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="' . $langs->trans("Update") . '"></td></tr>';
			
			print '</table>';
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

llxFooter();
$db->close();