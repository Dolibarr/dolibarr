<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals', 'companies'));

$type = GETPOST('type', 'aZ09');

// Security check
$socid = GETPOST('socid', 'int');
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

$title = $langs->trans("WithdrawStatistics");
if ($type == 'bank-transfer') {
	$title = $langs->trans("CreditTransferStatistics");
}

llxHeader('', $title);

print load_fiche_titre($title);

// Define total and nbtotal
$sql = "SELECT sum(pl.amount), count(pl.amount)";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql .= " WHERE pl.fk_prelevement_bons = pb.rowid";
if ($type == 'bank-transfer') {
	$sql .= " AND pb.type = 'bank-transfer'";
} else {
	$sql .= " AND pb.type = 'debit-order'";
}
$sql .= " AND pb.entity = ".$conf->entity;
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


/*
 * Stats
 */

print '<br>';
print load_fiche_titre($langs->trans("ByStatus"), '', '');

$ligne = new LignePrelevement($db);

$sql = "SELECT sum(pl.amount), count(pl.amount), pl.statut";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql .= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql .= " AND pb.entity = ".$conf->entity;
if ($type == 'bank-transfer') {
	$sql .= " AND pb.type = 'bank-transfer'";
} else {
	$sql .= " AND pb.type = 'debit-order'";
}
$sql .= " GROUP BY pl.statut";

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td><td class="right">%</td>';
	print '<td class="right">'.$langs->trans("Amount").'</td><td class="right">%</td></tr>';

	while ($i < $num) {
		$row = $db->fetch_row($resql);

		print '<tr class="oddeven"><td>';

		print $ligne->LibStatut($row[2], 1);
		//print $st[$row[2]];
		print '</td><td align="center">';
		print $row[1];

		print '</td><td class="right">';
		print round($row[1] / $nbtotal * 100, 2)." %";

		print '</td><td class="right">';

		print price($row[0]);

		print '</td><td class="right">';
		print round($row[0] / $total * 100, 2)." %";
		print '</td></tr>';

		$i++;
	}

	print '<tr class="liste_total"><td class="right">'.$langs->trans("Total").'</td>';
	print '<td class="center">'.$nbtotal.'</td><td>&nbsp;</td><td class="right">';
	print price($total);
	print '</td><td class="right">&nbsp;</td>';
	print "</tr></table>";

	$db->free($resql);
} else {
	dol_print_error($db);
}


/*
 * Stats on errors
 */

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

/*
 * Stats sur les rejets
 */

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

		print '</td><td align="center">'.$row[1];

		print '</td><td class="right">';
		print round($row[1] / $nbtotal * 100, 2)." %";

		print '</td><td class="right">';
		print price($row[0]);

		print '</td><td class="right">';
		print round($row[0] / $total * 100, 2)." %";

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

// End of page
llxFooter();
$db->close();
