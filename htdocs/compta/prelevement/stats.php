<?php
/* Copyright (C) 2005       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *		\file       htdocs/compta/prelevement/stats.php
 *      \ingroup    prelevement
 *      \brief      Page with statistics on withdrawals
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies'));

$type = GETPOST('type', 'aZ09');

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

$usercancreate = $user->hasRight('prelevement', 'bons', 'creer');


/*
 * View
 */

$title = $langs->trans("WithdrawalsReceipts");
if ($type == 'bank-transfer') {
	$title = $langs->trans("BankTransferReceipts");
}

llxHeader('', $title);

$param = '&type='.urlencode($type);
$mode = 'statistics';

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', DOL_URL_ROOT.'/compta/prelevement/orders_list.php?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', DOL_URL_ROOT.'/compta/prelevement/orders_list.php?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('Statistics'), '', 'fa fa-chart-bar imgforviewmode', DOL_URL_ROOT.'/compta/prelevement/stats.php?'.preg_replace('/(&|\?)*(mode|groupby)=[^&]+/', '', $param), '', ($mode == 'statistics' ? 2 : 1), array('morecss' => 'reposition'));
if ($usercancreate) {
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewStandingOrder'), '', 'fa fa-plus-circle', dolBuildUrl(DOL_URL_ROOT.'/compta/prelevement/create.php', ['type' => $type]));
}

$massactionbutton = '';
$limit = 0;

print_barre_liste($title, 0, $_SERVER["PHP_SELF"], $param, '', '', $massactionbutton, 0, $langs->trans("Statistics"), 'generic', 0, $newcardbutton, '', $limit, 0, 0, 1);


// Define total and nbtotal
$sql = "SELECT sum(pb.amount) as amount, count(pb.amount) as nb";
//$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql .= " WHERE pb.entity = ".$conf->entity;
if ($type == 'bank-transfer') {
	$sql .= " AND pb.type = 'bank-transfer'";
} else {
	$sql .= " AND pb.type = 'debit-order'";
}

$total = 0;
$nbtotal = 0;
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	if ($num > 0) {
		$obj = $db->fetch_object($resql);
		$total = $obj->amount;
		$nbtotal = $obj->nb;
	}
}


/*
 * Stats
 */

print '<br>';
print load_fiche_titre($langs->trans("ByStatus"), '', '');

$bon = new BonPrelevement($db);
$ligne = new LignePrelevement($db);

$sql = "SELECT COUNT(pb.rowid) as nb, SUM(pb.amount) as amount, pb.statut as status";
//$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as pb";
//$sql .= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql .= " WHERE pb.entity = ".$conf->entity;
if ($type == 'bank-transfer') {
	$sql .= " AND pb.type = 'bank-transfer'";
} else {
	$sql .= " AND pb.type = 'debit-order'";
}
$sql .= " GROUP BY pb.statut";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td>';
	//print '<td class="right">%</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td>';
	//print '<td class="right">%</td>';
	print '</tr>';

	while ($i < $num) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		print '<td>';
		print $bon->LibStatut($obj->status, 1);
		//print $st[$row[2]];
		print '</td>';

		print '<td class="center nowraponall">';
		print $obj->nb;
		print '</td>';

		/*print '<td class="right nowraponall">';
		print price2num($obj->amount / $nbtotal * 100, 2)." %";
		print '</td>';*/

		print '<td class="right amount nowraponall">';
		print price($obj->amount);
		print '</td>';

		/*
		print '<td class="right nowraponall">';
		print price2num($row[0] / $total * 100, 2)." %";
		print '</td>';
		*/

		print '</tr>';

		$i++;
	}

	print '<tr class="liste_total"><td class="right">'.$langs->trans("Total").'</td>';
	print '<td class="center nowraponall">'.$nbtotal.'</td>';
	//print '<td>&nbsp;</td>';
	print '<td class="right nowraponall">';
	print price($total);
	print '</td>';
	//print '<td class="right">&nbsp;</td>';
	print "</tr></table>";

	$db->free($resql);
} else {
	dol_print_error($db);
}


/*
 * Stats on errors
 */
/*

print '<br>';
print load_fiche_titre($langs->trans("Rejects"), '', '');


// Define total and nbtotal
$sql = "SELECT sum(pl.amount), count(pl.amount)";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql .= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql .= " AND pb.entity = ".$conf->entity;
$sql .= " AND pl.statut = 3";
if ($type == 'bank-transfer') {
	$sql .= " AND pb.type = 'bank-transfer'";
} else {
	$sql .= " AND pb.type = 'debit-order'";
}
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	if ($num > 0) {
		$row = $db->fetch_row($resql);
		$total = $row[0];
		$nbtotal = $row[1];
	}
}

$sql = "SELECT sum(pl.amount), count(pl.amount) as cc, pr.motif";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql .= ", ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql .= " AND pb.entity = ".$conf->entity;
$sql .= " AND pl.statut = 3";
$sql .= " AND pr.fk_prelevement_lignes = pl.rowid";
if ($type == 'bank-transfer') {
	$sql .= " AND pb.type = 'bank-transfer'";
} else {
	$sql .= " AND pb.type = 'debit-order'";
}
$sql .= " GROUP BY pr.motif";
$sql .= " ORDER BY cc DESC";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td>';
	print '<td class="right">%</td><td class="right">'.$langs->trans("Amount").'</td><td class="right">%</td></tr>';

	require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
	$Rejet = new RejetPrelevement($db, $user, $type);

	while ($i < $num) {
		$row = $db->fetch_row($resql);

		print '<tr class="oddeven"><td>';
		print $Rejet->motifs[$row[2]];
		print '</td>';

		print '<td class="center">'.$row[1];
		print '</td>';

		print '<td class="right">';
		print price2num($row[1] / $nbtotal * 100, 2)." %";

		print '</td><td class="right">';
		print price($row[0]);

		print '</td><td class="right">';
		print price2num($row[0] / $total * 100, 2)." %";

		print '</td></tr>';

		$i++;
	}

	print '<tr class="liste_total"><td class="right">'.$langs->trans("Total").'</td><td align="center">'.$nbtotal.'</td>';
	print '<td>&nbsp;</td><td class="right">';
	print price($total);
	print '</td><td class="right">&nbsp;</td>';
	print "</tr></table>";
	$db->free($resql);
} else {
	dol_print_error($db);
}
*/

// End of page
llxFooter();
$db->close();
