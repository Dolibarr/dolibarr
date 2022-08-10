<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2019      Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2020      Tobias Sekan         <tobias.sekan@startmail.com>
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
 *       \file       htdocs/expedition/index.php
 *       \ingroup    expedition
 *       \brief      Home page of shipping area.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

$hookmanager = new HookManager($db);
$socid = GETPOST('socid', 'int');

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('sendingindex'));

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings'));

/*
 *	View
 */

$orderstatic = new Commande($db);
$companystatic = new Societe($db);
$shipment = new Expedition($db);

$helpurl = 'EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('', $langs->trans("Shipment"), $helpurl);

print load_fiche_titre($langs->trans("SendingsArea"), '', 'dolly');


print '<div class="fichecenter"><div class="fichethirdleft">';

/*
 * Shipments to validate
 */

$clause = " WHERE ";

$sql = "SELECT e.rowid, e.ref, e.ref_customer,";
$sql .= " s.nom as name, s.rowid as socid,";
$sql .= " c.ref as commande_ref, c.rowid as commande_id";
$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'shipping'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON el.fk_source = c.rowid";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
	$sql .= $clause." sc.fk_user = ".((int) $user->id);
	$clause = " AND ";
}
$sql .= $clause." e.fk_statut = ".Expedition::STATUS_DRAFT;
$sql .= " AND e.entity IN (".getEntity('expedition').")";
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="3">';
	print $langs->trans("SendingsToValidate").' ';
	print '<a href="'.DOL_URL_ROOT.'/expedition/list.php?search_status='.Expedition::STATUS_DRAFT.'">';
	print '<span class="badge">'.$num.'</span>';
	print '</a>';
	print '</th>';
	print '</tr>';

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$shipment->id = $obj->rowid;
			$shipment->ref = $obj->ref;
			$shipment->ref_customer = $obj->ref_customer;

			print '<tr class="oddeven"><td class="nowrap">';
			print $shipment->getNomUrl(1);
			print "</td>";
			print '<td>';
			print '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->name.'</a>';
			print '</td>';
			print '<td>';
			if ($obj->commande_id) {
				print '<a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a>';
			}
			print '</td></tr>';
			$i++;
		}
	} else {
		print '<tr><td>'.$langs->trans("None").'</td><td></td><td></td></tr>';
	}

	print "</table></div><br>";
}



//print '</td><td valign="top" width="70%">';
print '</div><div class="fichetwothirdright">';

$max = 5;

/*
 * Latest shipments
 */
$sql = "SELECT e.rowid, e.ref, e.ref_customer,";
$sql .= " s.nom as name, s.rowid as socid,";
$sql .= " c.ref as commande_ref, c.rowid as commande_id";
$sql .= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'shipping' AND el.sourcetype IN ('commande')";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON el.fk_source = c.rowid AND el.sourcetype IN ('commande') AND el.targettype = 'shipping'";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
}
$sql .= " WHERE e.entity IN (".getEntity('expedition').")";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " AND sc.fk_user = ".((int) $user->id);
}
$sql .= " AND e.fk_statut = ".Expedition::STATUS_VALIDATED;
if ($socid) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
$sql .= " ORDER BY e.date_delivery DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">';
	print $langs->trans("LastSendings").' ';
	print '<a href="'.DOL_URL_ROOT.'/expedition/list.php?search_status='.Expedition::STATUS_VALIDATED.'">';
	print '<span class="badge">'.$num.'</span>';
	print '</a>';
	print '</th>';
	print '</tr>';

	if ($num) {
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$shipment->id = $obj->rowid;
			$shipment->ref = $obj->ref;
			$shipment->ref_customer = $obj->ref_customer;

			print '<tr class="oddeven"><td>';
			print $shipment->getNomUrl(1);
			print '</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"), "company").' '.$obj->name.'</a></td>';
			print '<td>';
			if ($obj->commande_id > 0) {
				$orderstatic->id = $obj->commande_id;
				$orderstatic->ref = $obj->commande_ref;
				print $orderstatic->getNomUrl(1);
			}
			print '</td>';
			print '<td class="">';

			print '</td>';
			print '</tr>';
			$i++;
		}
	} else {
		print '<tr><td>'.$langs->trans("None").'</td><td></td><td></td><td></td></tr>';
	}
	print "</table></div><br>";
	$db->free($resql);
} else {
	dol_print_error($db);
}

/*
 * Open orders
 */
$sql = "SELECT c.rowid, c.ref, c.ref_client as ref_customer, c.fk_statut as status, c.facture as billed, s.nom as name, s.rowid as socid";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c,";
$sql .= " ".MAIN_DB_PREFIX."societe as s";
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE c.fk_soc = s.rowid";
$sql .= " AND c.entity IN (".getEntity('order').")";
$sql .= " AND c.fk_statut IN (".Commande::STATUS_VALIDATED.", ".Commande::STATUS_ACCEPTED.")";
if ($socid > 0) {
	$sql .= " AND c.fk_soc = ".((int) $socid);
}
if (empty($user->rights->societe->client->voir) && !$socid) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
$sql .= " ORDER BY c.rowid ASC";

$resql = $db->query($sql);
if ($resql) {
	$langs->load("orders");

	$num = $db->num_rows($resql);

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<th colspan="3">'.$langs->trans("OrdersToProcess").' ';
	print '<a href="'.DOL_URL_ROOT.'/commande/list.php?search_status='.Commande::STATUS_VALIDATED.','.Commande::STATUS_ACCEPTED.'">';
	print '<span class="badge">'.$num.'</span>';
	print '</a>';
	print '</th>';
	print '</tr>';

	if ($num) {
		$i = 0;
		while ($i < $num && $i < 10) {
			$obj = $db->fetch_object($resql);

			$orderstatic->id = $obj->rowid;
			$orderstatic->ref = $obj->ref;
			$orderstatic->ref_customer = $obj->ref_customer;
			$orderstatic->statut = $obj->status;
			$orderstatic->billed = $obj->billed;

			$companystatic->name = $obj->name;
			$companystatic->id = $obj->socid;

			print '<tr class="oddeven"><td>';
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

		if ($i < $num) {
			print '<tr class="opacitymedium">';
			print '<td>'.$langs->trans("More").'...</td>';
			print '<td></td>';
			print '<td></td>';
			print '</tr>';
		}
	} else {
		print '<tr><td>'.$langs->trans("None").'</td><td></td><td></td></tr>';
	}

	print "</table></div><br>";
} else {
	dol_print_error($db);
}


print '</div></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardWarehouseSendings', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
