<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2013 Juanjo Menent 		<jmenent@2byte.es>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/prelevement/rejets.php
 *      \ingroup    prelevement
 *      \brief      Reject page
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies'));

$type = GETPOST('type', 'aZ09');

// Get supervariables
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortorder = GETPOST('sortorder', 'aZ09comma');
$sortfield = GETPOST('sortfield', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("WithdrawsRefused");
if ($type == 'bank-transfer') {
	$title = $langs->trans("CreditTransfersRefused");
}

llxHeader('', $title);

if ($sortorder == "") {
	$sortorder = "DESC";
}
if ($sortfield == "") {
	$sortfield = "p.datec";
}

$rej = new RejetPrelevement($db, $user, $type);
$line = new LignePrelevement($db);
$thirdpartystatic = new Societe($db);
$userstatic = new User($db);

$hookmanager->initHooks(array('withdrawalsreceiptsrejectedlist'));


// List of invoices

$sql = "SELECT pl.rowid, pr.motif, p.ref, pl.statut, p.rowid as bonId,";
$sql .= " s.rowid as socid, s.nom as name, p.datec";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " WHERE pr.fk_prelevement_lignes = pl.rowid";
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND pl.fk_soc = s.rowid";
$sql .= " AND p.entity = ".((int) $conf->entity);
if ($type == 'bank-transfer') {
	$sql .= " AND p.type = 'bank-transfer'";
} else {
	$sql .= " AND p.type = 'debit-order'";
}
if ($socid > 0) {
	$sql .= " AND s.rowid = ".((int) $socid);
}
// Add list for salaries
if ($type == 'bank-transfer') {
	$sql .= " UNION";
	$sql .= " SELECT pl.rowid, pr.motif, p.ref, pl.statut, p.rowid as bonId,";
	$sql .= " u.rowid as socid, CONCAT(u.firstname,' ', u.lastname) as name, p.datec";
	$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
	$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
	$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql .= " , ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE pr.fk_prelevement_lignes = pl.rowid";
	$sql .= " AND pl.fk_prelevement_bons = p.rowid";
	$sql .= " AND pl.fk_user = u.rowid";
	$sql .= " AND p.entity = ".((int) $conf->entity);
	$sql .= " AND p.type = 'bank-transfer'";
	if ($socid) {
		$sql .= " AND s.rowid = ".((int) $socid);
	}
}
if ($type == 'bank-transfer') {
	$sortfield = 'datec';
}
$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);

	$param = '';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	print"\n<!-- debut table -->\n";
	print '<table class="noborder tagtable liste" width="100%" cellpadding="4">';
	print '<tr class="liste_titre">';
	print_liste_field_titre("Line", $_SERVER["PHP_SELF"], "p.ref", '', $param);
	print_liste_field_titre("ThirdParty", $_SERVER["PHP_SELF"], "s.nom", '', $param);
	print_liste_field_titre("Reason", $_SERVER["PHP_SELF"], "pr.motif", "", $param);
	print "</tr>\n";

	$bon = new BonPrelevement($db);
	if ($num) {
		$i = 0;
		$maxlim = min($num, $limit);
		while ($i < $maxlim) {
			$obj = $db->fetch_object($result);
			$bon->fetch($obj->bonId);

			print '<tr class="oddeven">';

			print '<td>';
			print $line->LibStatut($obj->statut, 2).'&nbsp;';
			print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/line.php?id='.$obj->rowid.'">';
			print substr('000000'.$obj->rowid, -6)."</a></td>";

			if ($bon->checkIfSalaryBonPrelevement()) {
				print '<td><a href="'.DOL_URL_ROOT.'/salaries/card.php?id='.$obj->socid.'">'.$obj->name."</a></td>\n";
			} else {
				$thirdpartystatic->id = $obj->socid;
				$thirdpartystatic->name = $obj->name;

				print '<td class="tdoverlowmax200"><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$thirdpartystatic->getNomUrl(1)."</a></td>\n";
			}

			print '<td>'.$rej->motifs[$obj->motif].'</td>';

			print "</tr>\n";

			$i++;
		}
	} else {
		print '<tr><td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
	}

	print "</table>";
	$db->free($result);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
