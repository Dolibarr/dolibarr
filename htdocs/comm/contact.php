<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003      Eric Seigne			<erics@rycks.com>
 * Copyright (C) 2004-2009 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *      \file       htdocs/comm/contact.php
 *      \ingroup    commercial
 *      \brief      Liste des contacts
 */

// Load Dolibarr environment
require '../main.inc.php';

// Load translation files required by the page
$langs->load("companies");

$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "p.lastname";
}
if ($page < 0) {
	$page = 0;
}
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$offset = $limit * $page;

$type = GETPOST('type', 'alpha');
$search_lastname = GETPOST('search_nom') ? GETPOST('search_nom') : GETPOST('search_lastname'); // For backward compatibility
$search_firstname = GETPOST('search_firstname') ? GETPOST('search_firstname') : GETPOST('search_firstname'); // For backward compatibility
$search_company = GETPOST('search_societe') ? GETPOST('search_societe') : GETPOST('search_company'); // For backward compatibility
$contactname = GETPOST('contactname');
$begin = GETPOST('begin', 'alpha');

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$action = '';
	$socid = $user->socid;
}

$hookmanager->initHooks(array('contactlist'));
$result = restrictedArea($user, 'societe', $socid, '');


/*
 * View
 */

llxHeader('', $langs->trans("Contacts"));

if ($type == "c" || $type == "p") {
	$label = $langs->trans("Customers");
	$urlfiche = "card.php";
}
if ($type == "f") {
	$label = $langs->trans("Suppliers");
	$urlfiche = "card.php";
}

/*
 * List mode
 */

$sql = "SELECT s.rowid, s.nom as name, st.libelle as stcomm,";
$sql .= " p.rowid as cidp, p.lastname, p.firstname, p.email, p.phone";
$sql .= " FROM ".MAIN_DB_PREFIX."c_stcomm as st,";
$sql .= " ".MAIN_DB_PREFIX."socpeople as p";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = p.fk_soc";
$sql .= " WHERE s.fk_stcomm = st.id";
$sql .= " AND p.entity IN (".getEntity('contact').")";
if ($type == "c") {
	$sql .= " AND s.client IN (1, 3)";
}
if ($type == "p") {
	$sql .= " AND s.client IN (2, 3)";
}
if ($type == "f") {
	$sql .= " AND s.fournisseur = 1";
}
if (!empty($search_lastname)) {
	$sql .= " AND p.lastname LIKE '%".$db->escape($search_lastname)."%'";
}
if (!empty($search_firstname)) {
	$sql .= " AND p.firstname LIKE '%".$db->escape($search_firstname)."%'";
}
if (!empty($search_company)) {
	$sql .= " AND s.nom LIKE '%".$db->escape($search_company)."%'";
}
if (!empty($contactname)) { // access a partir du module de recherche
	$sql .= " AND (p.lastname LIKE '%".$db->escape($contactname)."%' OR lower(p.firstname) LIKE '%".$db->escape($contactname)."%') ";
	$sortfield = "p.lastname";
	$sortorder = "ASC";
}
// If the internal user must only see his customers, force searching by him
$search_sale = 0;
if (!$user->hasRight('societe', 'client', 'voir')) {
	$search_sale = $user->id;
}
// Search on sale representative
if ($search_sale && $search_sale != '-1') {
	if ($search_sale == -2) {
		$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc)";
	} elseif ($search_sale > 0) {
		$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = p.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
	}
}
// Search on socid
if ($socid) {
	$sql .= " AND p.fk_soc = ".((int) $socid);
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);

	$param = "&type=".$type;

	$title = (getDolGlobalString('SOCIETE_ADDRESSES_MANAGEMENT') ? $langs->trans("ListOfContacts") : $langs->trans("ListOfContactsAddresses"));
	print_barre_liste($title.($label ? " (".$label.")" : ""), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, "", $num);

	print '<form action="'.$_SERVER["PHP_SELF"].'?type='.GETPOST("type", "alpha").'" method="GET">';

	print '<table class="liste centpercent">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Lastname", $_SERVER["PHP_SELF"], "p.lastname", $begin, $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Firstname", $_SERVER["PHP_SELF"], "p.firstname", $begin, $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Company", $_SERVER["PHP_SELF"], "s.nom", $begin, $param, "", $sortfield, $sortorder);
	print_liste_field_titre("Email");
	print_liste_field_titre("Phone");
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input class="flat" name="search_lastname" size="12" value="'.$search_lastname.'"></td>';
	print '<td class="liste_titre"><input class="flat" name="search_firstname" size="12"  value="'.$search_firstname.'"></td>';
	print '<td class="liste_titre"><input class="flat" name="search_company" size="12"  value="'.$search_company.'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre right"><input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"), 'search.png', '', '', 1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'"></td>';
	print "</tr>\n";

	$i = 0;
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';
		print '<td><a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$obj->cidp.'&socid='.$obj->rowid.'">'.img_object($langs->trans("ShowContact"), "contact");
		print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/contact/card.php?id='.$obj->cidp.'&socid='.$obj->rowid.'">'.$obj->name.'</a></td>';
		print '<td>'.dol_escape_htmltag($obj->firstname).'</td>';

		print '<td><a href="'.$_SERVER["PHP_SELF"].'?type='.$type.'&socid='.$obj->rowid.'">'.img_object($langs->trans("ShowCompany"), "company").'</a>&nbsp;';
		print '<a href="'.$urlfiche."?socid=".$obj->rowid.'">'.$obj->name."</a></td>\n";

		print '<td>'.dol_print_email($obj->email, $obj->cidp, $obj->rowid, 'AC_EMAIL').'</td>';

		print '<td>'.dol_print_phone($obj->phone, $obj->country_code, $obj->cidp, $obj->rowid, 'AC_TEL').'&nbsp;</td>';

		print "</tr>\n";
		$i++;
	}
	print "</table>";

	print '</form>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
