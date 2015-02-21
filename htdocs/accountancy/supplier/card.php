<?php
/* Copyright (C) 2004       Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2005       Simon TOSSER          <simon@kornog-computing.com>
 * Copyright (C) 2013-2014  Alexandre Spangaro    <alexandre.spangaro@gmail.com>
 * Copyright (C) 2013-2014  Olivier Geffroy       <jeff@jeffinfo.com>
 * Copyright (C) 2013-2014	Florian Henry	      <florian.henry@open-concept.pro>
 * Copyright (C) 2014	    Juanjo Menent		  <jmenent@2byte.es>
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
 * \ingroup		Accounting Expert
 * \brief		Card supplier ventilation
 */

require '../../main.inc.php';
	
// Class
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/html.formventilation.class.php';

// Langs
$langs->load("compta");
$langs->load("bills");
$langs->load("other");
$langs->load("main");
$langs->load("accountancy");

$action = GETPOST('action');
$id = GETPOST('id', 'int');
$codeventil = GETPOST('codeventil');

// Security check
if ($user->societe_id > 0)
	accessforbidden();

if ($action == 'ventil' && $user->rights->accounting->ventilation->dispatch)
{
	$sql = " UPDATE " . MAIN_DB_PREFIX . "facture_fourn_det";
	$sql .= " SET fk_code_ventilation = " . $codeventil;
	$sql .= " WHERE rowid = " . $id;
	
	dol_syslog('accountancy/journal/sellsjournal.php:: $sql=' . $sql);
	
	$resql = $db->query($sql);
	if (! $resql) {
		setEventMessage($db->lasterror(), 'errors');
	}
}

/*
 * View
 */
llxHeader("", "", "FicheVentilation");

if ($cancel == $langs->trans("Cancel")) {
	$action = '';
}

/*
 * Create
 */
$form = new Form($db);
$facturefournisseur_static = new FactureFournisseur($db);
$formventilation = new FormVentilation($db);

if ($_GET["id"]) {
	$sql = "SELECT f.ref as facnumber, f.rowid as facid, l.fk_product, l.description, l.rowid, l.fk_code_ventilation, ";
	$sql .= " p.rowid as product_id, p.ref as product_ref, p.label as product_label";
	$sql .= ", aa.account_number, aa.label";
	$sql .= " FROM " . MAIN_DB_PREFIX . "facture_fourn_det as l";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product as p ON p.rowid = l.fk_product";
	$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "accountingaccount as aa ON l.fk_code_ventilation = aa.rowid";
	$sql .= " INNER JOIN " . MAIN_DB_PREFIX . "facture_fourn as f ON f.rowid = l.fk_facture_fourn ";
	$sql .= " WHERE f.fk_statut > 0 AND l.rowid = " . $id;
	if (! empty($conf->multicompany->enabled)) {
		$sql .= " AND f.entity = '" . $conf->entity . "'";
	}
	
	$result = $db->query($sql);
	if ($result) {
		$num_lines = $db->num_rows($result);
		$i = 0;
		
		if ($num_lines) {
			$objp = $db->fetch_object($result);
			
			print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '" method="post">' . "\n";
			print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
			print '<input type="hidden" name="action" value="ventil">';
			
			print_fiche_titre($langs->trans("SuppliersVentilation"));
			
			print '<table class="border" width="100%" cellspacing="0" cellpadding="4">';
			
			// ref invoice
			print '<tr><td>' . $langs->trans("BillsSuppliers") . '</td>';
			$facturefournisseur_static->ref = $objp->facnumber;
			$facturefournisseur_static->id = $objp->facid;
			print '<td>' . $facturefournisseur_static->getNomUrl(1) . '</td>';
			print '</tr>';
			
			print '<tr><td width="20%">Ligne</td>';
			print '<td>' . stripslashes(nl2br($objp->description)) . '</td></tr>';
			print '<tr><td width="20%">' . $langs->trans("ProductLabel") . '</td>';
			print '<td>' . dol_trunc($objp->product_label, 24) . '</td>';
			print '<tr><td width="20%">' . $langs->trans("Account") . '</td><td>';
			print $objp->account_number . '-' . $objp->label;
			print '<tr><td width="20%">' . $langs->trans("NewAccount") . '</td><td>';
			print $formventilation->select_account($objp->fk_code_ventilation, 'codeventil', 1);
			print '</td></tr>';
			print '<tr><td>&nbsp;</td><td><input type="submit" class="button" value="' . $langs->trans("Update") . '"></td></tr>';
			
			print '</table>';
			print '</form>';
		} else {
			print "Error 1";
		}
	} else {
		print "Error 2";
	}
} else {
	print "Error ID incorrect";
}

llxFooter();
$db->close();