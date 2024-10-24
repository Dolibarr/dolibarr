<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville     <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin            <regis.houssin@capnetworks.com>
 * Copyright (C) 2018	   Quentin Vial-Gouteyron   <quentin.vial-gouteyron@atm-consulting.fr>
 * Copyright (C) 2019      Nicolas ZABOURI          <info@inovea-conseil.com>
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
 *       \file       htdocs/reception/index.php
 *       \ingroup    reception
 *       \brief      Home page of reception area.
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/reception/class/reception.class.php';

$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('receptionindex'));

$langs->loadLangs(array("orders", "receptions"));

$reception = new Reception($db);

// Security check
$socid = '';
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'reception', 0, '');


/*
 *	View
 */

$orderstatic = new CommandeFournisseur($db);
$companystatic = new Societe($db);

$helpurl = 'EN:Module_Receptions|FR:Module_Receptions|ES:M&oacute;dulo_Receptiones';
llxHeader('', $langs->trans("Reception"), $helpurl, '', 0, 0, '', '', '', 'mod-reception page-dashboard');

print load_fiche_titre($langs->trans("ReceptionsArea"), '', 'dollyrevert');


print '<div class="fichecenter"><div class="fichethirdleft">';


if (getDolGlobalString('MAIN_SEARCH_FORM_ON_HOME_AREAS')) {     // This may be useless due to the global search combo
	print '<form method="post" action="list.php">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder nohover centpercent">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
	print '<tr class="oddeven"><td>';
	print $langs->trans("Reception").':</td><td><input type="text" class="flat" name="sall" size="18"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "</table></div></form><br>\n";
}


/*
 * Draft receptions
 */

$sql = "SELECT e.rowid, e.ref, e.ref_supplier,";
$sql .= " s.nom as name, s.rowid as socid,";
$sql .= " c.ref as commande_fournisseur_ref, c.rowid as commande_fournisseur_id";
$sql .= " FROM ".MAIN_DB_PREFIX."reception as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'reception'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as c ON el.fk_source = c.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = e.fk_soc AND sc.fk_user = ".((int) $user->id).")";
}
$sql .= " WHERE e.fk_statut = 0";
$sql .= " AND e.entity IN (".getEntity('reception').")";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}

$resql = $db->query($sql);
if ($resql) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="3">'.$langs->trans("ReceptionsToValidate").'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$reception->id = $obj->rowid;
			$reception->ref = $obj->ref;
			$reception->ref_supplier = $obj->ref_supplier;

			print '<tr class="oddeven"><td class="nowrap">';
			print $reception->getNomUrl(1);
			print "</td>";
			print '<td>';
			print '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->name.'</a>';
			print '</td>';
			print '<td>';
			if ($obj->commande_fournisseur_id) {
				print '<a href="'.DOL_URL_ROOT.'/commande_fournisseur/card.php?id='.$obj->commande_fournisseur_id.'">'.$obj->commande_fournisseur_ref.'</a>';
			}
			print '</td></tr>';
			$i++;
		}
	} else {
		print '<tr><td><span class="opacitymedium">'.$langs->trans("None").'</span></td><td></td><td></td></tr>';
	}

	print "</table></div><br>";
}


print '</div><div class="fichetwothirdright">';

$max = 5;

/*
 * Latest receptions
 */

$sql = "SELECT e.rowid, e.ref, e.ref_supplier,";
$sql .= " s.nom as name, s.rowid as socid,";
$sql .= " c.ref as commande_fournisseur_ref, c.rowid as commande_fournisseur_id";
$sql .= " FROM ".MAIN_DB_PREFIX."reception as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'reception' AND el.sourcetype IN ('order_supplier')";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande_fournisseur as c ON el.fk_source = c.rowid AND el.sourcetype IN ('order_supplier') AND el.targettype = 'reception'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
}
$sql .= " WHERE e.entity IN (".getEntity('reception').")";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND sc.fk_user = ".((int) $user->id);
}
$sql .= " AND e.fk_statut = 1";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
$sql .= " ORDER BY e.date_delivery DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	if ($num) {
		$i = 0;
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("LastReceptions", $num).'</th></tr>';
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$reception->id = $obj->rowid;
			$reception->ref = $obj->ref;
			$reception->ref_supplier = $obj->ref_supplier;

			print '<tr class="oddeven"><td>';
			print $reception->getNomUrl(1);
			print '</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"), "company").' '.$obj->name.'</a></td>';
			print '<td>';
			if ($obj->commande_fournisseur_id > 0) {
				$orderstatic->id = $obj->commande_fournisseur_id;
				$orderstatic->ref = $obj->commande_fournisseur_ref;
				print $orderstatic->getNomUrl(1);
			} else {
				print '&nbsp;';
			}
			print '</td></tr>';
			$i++;
		}
		print "</table></div><br>";
	}
	$db->free($resql);
} else {
	dol_print_error($db);
}



/*
 * Open pruchase orders to process
 */

$sql = "SELECT c.rowid, c.ref, c.ref_supplier as ref_supplier, c.fk_statut as status, c.billed as billed, s.nom as name, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity IN (".getEntity('supplier_order').")";
$sql .= " AND c.fk_statut IN (".CommandeFournisseur::STATUS_ORDERSENT.", ".CommandeFournisseur::STATUS_RECEIVED_PARTIALLY.")";
if ($socid > 0) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = c.fk_soc AND sc.fk_user = ".((int) $user->id).")";
}
$sql .= " ORDER BY c.rowid ASC";
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	if ($num) {
		$langs->load("orders");

		$i = 0;
		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("SuppliersOrdersToProcess");
		print ' <a href="'.DOL_URL_ROOT.'/reception/list.php?search_status=1" alt="'.$langs->trans("GoOnList").'"><span class="badge">'.$num.'</span></a>';
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$orderstatic->id = $obj->rowid;
			$orderstatic->ref = $obj->ref;
			$orderstatic->ref_supplier = $obj->ref_supplier;
			$orderstatic->statut = $obj->status;
			$orderstatic->facturee = $obj->billed;

			$companystatic->name = $obj->name;
			$companystatic->id = $obj->socid;

			print '<tr class="oddeven">';
			print '<td class="nowrap">';
			print $orderstatic->getNomUrl(1);
			print '</td>';
			print '<td>';
			print $companystatic->getNomUrl(1, 'customer', 32);
			print '</td>';
			print '<td class="right">';
			print $orderstatic->getLibStatut(3);
			print '</td>';
			print '</tr>';
			$i++;
		}
		print "</table></div><br>";
	}
}

print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardWarehouseReceptions', $parameters, $object); // Note that $action and $object may have been modified by hook

llxFooter();
$db->close();
