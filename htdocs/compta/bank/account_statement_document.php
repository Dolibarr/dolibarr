<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2017 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2019	   Nicolas ZABOURI       <info@inovea-conseil.com>
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
 * 	\file       htdocs/compta/bankaccount_statement_document.php
 * 	\ingroup    banque
 * 	\brief      Page to manage document attached to a bank receipt
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/bank.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

global $conf, $db, $langs;
// Load translation files required by the page
$langs->loadLangs(array('banks', 'companies', 'other'));

$id = (GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('account'));
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$numref = (GETPOST('num', 'alpha') ? GETPOST('num', 'alpha') : GETPOST('sectionid', 'alpha'));

// Security check
if ($user->socid) {
	$action = '';
	$socid = $user->socid;
}
if ($user->socid) {
	$socid = $user->socid;
}

// Get parameters
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "name";
}

$object = new Account($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	$account = $object->id; // Force the search field on id of account
}

$result = restrictedArea($user, 'banque', $object->id, 'bank_account', '', '');

// Define number of receipt to show (current, previous or next one ?)
$found = false;
if (GETPOST("rel") == 'prev') {
	// Recherche valeur pour num = numero releve precedent
	$sql = "SELECT DISTINCT(b.num_releve) as num";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= " WHERE b.num_releve < '".$db->escape($numref)."'";
	$sql .= " AND b.fk_account = ".((int) $id);
	$sql .= " ORDER BY b.num_releve DESC";

	dol_syslog("htdocs/compta/bank/releve.php", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$numrows = $db->num_rows($resql);
		if ($numrows > 0) {
			$obj = $db->fetch_object($resql);
			$numref = $obj->num;
			$found = true;
		}
	}
} elseif (GETPOST("rel") == 'next') {
	// Recherche valeur pour num = numero releve precedent
	$sql = "SELECT DISTINCT(b.num_releve) as num";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= " WHERE b.num_releve > '".$db->escape($numref)."'";
	$sql .= " AND b.fk_account = ".((int) $id);
	$sql .= " ORDER BY b.num_releve ASC";

	dol_syslog("htdocs/compta/bank/releve.php", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql) {
		$numrows = $db->num_rows($resql);
		if ($numrows > 0) {
			$obj = $db->fetch_object($resql);
			$numref = $obj->num;
			$found = true;
		}
	}
} else {
	// On veut le releve num
	$found = true;
}

$permissiontoadd = $user->hasRight('banque', 'modifier');	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

if (!empty($numref)) {
	$object->fetch_thirdparty();
	$upload_dir = $conf->bank->dir_output."/".$id."/statement/".dol_sanitizeFileName($numref);
}
$backtopage = $_SERVER['PHP_SELF']."?account=".urlencode((string) ($id))."&num=".urlencode((string) ($numref));
include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("FinancialAccount").' - '.$langs->trans("Documents");
$helpurl = "";
llxHeader('', $title, $helpurl);

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref)) {
		$upload_dir = $conf->bank->dir_output."/".$id."/statement/".dol_sanitizeFileName($numref);

		// Onglets
		$head = account_statement_prepare_head($object, $numref);
		print dol_get_fiche_head($head, 'document', $langs->trans("AccountStatement"), -1, 'account');


		// Build file list
		$filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
		$totalsize = 0;
		foreach ($filearray as $key => $file) {
			$totalsize += $file['size'];
		}

		$morehtmlright = '';
		$morehtmlright .= '<div class="pagination"><ul>';
		$morehtmlright .= '<li class="pagination"><a class="paginationnext" href="'.$_SERVER["PHP_SELF"].'?rel=prev&amp;num='.$numref.'&amp;ve='.$ve.'&amp;account='.$object->id.'"><i class="fa fa-chevron-left" title="'.dol_escape_htmltag($langs->trans("Previous")).'"></i></a></li>';
		$morehtmlright .= '<li class="pagination"><span class="active">'.$langs->trans("AccountStatement")." ".$numref.'</span></li>';
		$morehtmlright .= '<li class="pagination"><a class="paginationnext" href="'.$_SERVER["PHP_SELF"].'?rel=next&amp;num='.$numref.'&amp;ve='.$ve.'&amp;account='.$object->id.'"><i class="fa fa-chevron-right" title="'.dol_escape_htmltag($langs->trans("Next")).'"></i></a></li>';
		$morehtmlright .= '</ul></div>';

		$title = $langs->trans("AccountStatement").' '.$numref.' - '.$langs->trans("BankAccount").' '.$object->getNomUrl(1, 'receipts');
		print load_fiche_titre($title, $morehtmlright, '');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border tableforfield centpercent">';
		print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
		print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
		print "</table>\n";

		print '</div>';

		print dol_get_fiche_end();


		$modulepart = 'bank';
		$permissiontoadd = $user->hasRight('banque', 'modifier');
		$permtoedit = $user->hasRight('banque', 'modifier');
		$param = '&id='.$object->id.'&num='.urlencode($numref);
		$moreparam = '&num='.urlencode($numref);
		$relativepathwithnofile = $id."/statement/".dol_sanitizeFileName($numref)."/";
		include DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
	} else {
		dol_print_error($db);
	}
} else {
	header('Location: index.php');
	exit;
}

// End of page
llxFooter();
$db->close();
