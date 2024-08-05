<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/compta/clients.php
 *	\ingroup    compta
 *	\brief      Show list of customers to add an new invoice
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

$action = GETPOST('action', 'aZ09');

// Secrutiy check
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

if (!$user->hasRight('facture', 'lire')) {
	accessforbidden();
}

// Load translation files required by the page
$langs->load("companies");

$mode = GETPOST("mode");

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "nom";
}
if (!$sortorder) {
	$sortorder = "ASC";
}


/*
 * View
 */

llxHeader();

$thirdpartystatic = new Societe($db);

if ($action == 'note') {
	$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET note='".$db->escape($note)."' WHERE rowid=".((int) $socid);
	$result = $db->query($sql);
}

if ($mode == 'search') {
	$resql = $db->query($sql);
	if ($resql) {
		if ($db->num_rows($resql) == 1) {
			$obj = $db->fetch_object($resql);
			$socid = $obj->rowid;
		}
		$db->free($resql);
	}
}



// Mode List

$sql = "SELECT s.rowid, s.nom as name, s.client, s.town, s.datec, s.datea";
$sql .= ", st.libelle as stcomm, s.prefix_comm, s.code_client, s.code_compta ";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", sc.fk_soc, sc.fk_user ";
}
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= " WHERE s.fk_stcomm = st.id AND s.client in (1, 3)";
$sql .= " AND s.entity IN (".getEntity('societe').")";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}
if (dol_strlen($stcomm)) {
	$sql .= " AND s.fk_stcomm=".((int) $stcomm);
}
if (GETPOST("search_nom")) {
	$sql .= natural_search("s.nom", GETPOST("search_nom"));
}
if (GETPOST("search_compta")) {
	$sql .= natural_search("s.code_compta", GETPOST("search_compta"));
}
if (GETPOST("search_code_client")) {
	$sql .= natural_search("s.code_client", GETPOST("search_code_client"));
}
if ($socid) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit($conf->liste_limit + 1, $offset);
//print $sql;

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	$langs->load('commercial');

	print_barre_liste($langs->trans("Customers"), $page, $_SERVER["PHP_SELF"], "", $sortfield, $sortorder, '', $num);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';

	print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", "", "", 'valign="center"', $sortfield, $sortorder);
	print_liste_field_titre("Town", $_SERVER["PHP_SELF"], "s.town", "", "", 'valign="center"', $sortfield, $sortorder);
	print_liste_field_titre("CustomerCode", $_SERVER["PHP_SELF"], "s.code_client", "", "", '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre("AccountancyCode", $_SERVER["PHP_SELF"], "s.code_compta", "", "", '', $sortfield, $sortorder, 'left ');
	print_liste_field_titre("DateCreation", $_SERVER["PHP_SELF"], "datec", $addu, "", '', $sortfield, $sortorder, 'right ');
	print "</tr>\n";

	// Fields title search
	print '<tr class="liste_titre">';

	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" name="search_nom" value="'.GETPOST("search_nom").'"></td>';

	print '<td class="liste_titre">&nbsp;</td>';

	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" size="10" name="search_code_client" value="'.GETPOST("search_code_client").'">';
	print '</td>';

	print '<td class="liste_titre left">';
	print '<input class="flat" type="text" size="10" name="search_compta" value="'.GETPOST("search_compta").'">';
	print '</td>';

	print '<td colspan="2" class="liste_titre right">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	while ($i < min($num, $conf->liste_limit)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';
		print '<td>';
		$thirdpartystatic->id = $obj->rowid;
		$thirdpartystatic->name = $obj->name;
		$thirdpartystatic->client = $obj->client;
		print $thirdpartystatic->getNomUrl(1, 'compta');
		print '</td>';
		print '<td>'.$obj->town.'&nbsp;</td>';
		print '<td class="left">'.$obj->code_client.'&nbsp;</td>';
		print '<td class="left">'.$obj->code_compta.'&nbsp;</td>';
		print '<td class="right">'.dol_print_date($db->jdate($obj->datec)).'</td>';
		print "</tr>\n";
		$i++;
	}
	print "</table>";

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

llxFooter();
$db->close();
