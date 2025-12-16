<?php
/* Copyright (C) 2025
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
 * \file        htdocs/projet/ajax/reorderreferrers.php
 * \brief       Save user-defined ordering of project referrers list (propal, invoices, supplier orders, ...).
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disable token renewal
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}
if (!defined('CSRFCHECK_WITH_TOKEN')) {
	define('CSRFCHECK_WITH_TOKEN', '1'); // Token is required even in GET mode
}

require '../../main.inc.php';

$projectid = GETPOSTINT('projectid', 3);
$elementtype = GETPOST('elementtype', 'aZ09', 3);
$roworder = GETPOST('roworder', 'alpha', 3);

top_httphead();

if (empty($projectid) || empty($elementtype)) {
	http_response_code(400);
	exit;
}

// Security: we change the display ordering for a project, so require write access on projects.
if (!$user->hasRight('projet', 'creer')) {
	httponly_accessforbidden('Not allowed');
}

// Ensure table exists (feature may be deployed before DB update).
$infotable = $db->DDLInfoTable(MAIN_DB_PREFIX.'projet_elementorder');
if (empty($infotable)) {
	http_response_code(409);
	print 'Missing table '.MAIN_DB_PREFIX.'projet_elementorder';
	exit;
}

$allowed = array(
	'propal' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'commande' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'facture' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'facture_rec' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'supplier_proposal' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'commande_fournisseur' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'facture_fourn' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'facture_fourn_rec' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'contrat' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'fichinter' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'expedition' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'loan' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'don' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'chargesociales' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'salary' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'payment_various' => array('projectfield' => 'fk_projet', 'idfield' => 'rowid'),
	'entrepot' => array('projectfield' => 'fk_project', 'idfield' => 'rowid'),
	'mrp_mo' => array('projectfield' => 'fk_project', 'idfield' => 'rowid'),
	'stocktransfer_stocktransfer' => array('projectfield' => 'fk_project', 'idfield' => 'rowid'),
);

if (empty($allowed[$elementtype])) {
	http_response_code(403);
	exit;
}

$ids = array();
foreach (explode(',', (string) $roworder) as $value) {
	$value = trim($value);
	if ($value === '') {
		continue;
	}
	$intvalue = (int) $value;
	if ($intvalue > 0) {
		$ids[$intvalue] = $intvalue;
	}
}
$ids = array_values($ids);

if (empty($ids)) {
	exit;
}

$projectfield = $allowed[$elementtype]['projectfield'];
$idfield = $allowed[$elementtype]['idfield'];
$tablename = MAIN_DB_PREFIX.$elementtype;

// Keep only ids that are still linked to the project.
$sql = "SELECT ".$idfield." as rowid";
$sql .= " FROM ".$tablename;
$sql .= " WHERE ".$projectfield." = ".((int) $projectid);
$sql .= " AND ".$idfield." IN (".$db->sanitize(implode(',', $ids)).")";

$resql = $db->query($sql);
if (!$resql) {
	http_response_code(500);
	exit;
}

$validids = array();
while ($obj = $db->fetch_object($resql)) {
	$validids[(int) $obj->rowid] = (int) $obj->rowid;
}

if (empty($validids)) {
	exit;
}

$db->begin();

$rank = 1;
foreach ($ids as $id) {
	if (empty($validids[$id])) {
		continue;
	}

	$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_elementorder(entity, fk_projet, elementtype, fk_element, rang)";
	$sql .= " VALUES (".((int) $conf->entity).", ".((int) $projectid).", '".$db->escape($elementtype)."', ".((int) $id).", ".((int) $rank).")";
	$sql .= " ON DUPLICATE KEY UPDATE rang = ".((int) $rank);

	$res = $db->query($sql);
	if (!$res) {
		$db->rollback();
		http_response_code(500);
		exit;
	}
	$rank++;
}

$db->commit();

