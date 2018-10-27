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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'prelevement','','','bons');


/*
 * View
 */

llxHeader('',$langs->trans("WithdrawStatistics"));

print load_fiche_titre($langs->trans("Statistics"));

// Define total and nbtotal
$sql = "SELECT sum(pl.amount), count(pl.amount)";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql.= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql.= " AND pb.entity = ".$conf->entity;
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    if ( $num > 0 )
    {
        $row = $db->fetch_row($resql);
        $total = $row[0];
        $nbtotal = $row[1];
    }
}


/*
 * Stats
 */

print '<br>';
print load_fiche_titre($langs->trans("WithdrawStatistics"), '', '');

$ligne=new LignePrelevement($db,$user);

$sql = "SELECT sum(pl.amount), count(pl.amount), pl.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql.= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql.= " AND pb.entity = ".$conf->entity;
$sql.= " GROUP BY pl.statut";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td><td align="right">%</td>';
	print '<td align="right">'.$langs->trans("Amount").'</td><td align="right">%</td></tr>';

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);

		print '<tr class="oddeven"><td>';

		print $ligne->LibStatut($row[2],1);
		//print $st[$row[2]];
		print '</td><td align="center">';
		print $row[1];

		print '</td><td align="right">';
		print round($row[1]/$nbtotal*100,2)." %";

		print '</td><td align="right">';

		print price($row[0]);

		print '</td><td align="right">';
		print round($row[0]/$total*100,2)." %";
		print '</td></tr>';
		
		$i++;
	}

	print '<tr class="liste_total"><td align="right">'.$langs->trans("Total").'</td>';
	print '<td align="center">'.$nbtotal.'</td><td>&nbsp;</td><td align="right">';
	print price($total);
	print '</td><td align="right">&nbsp;</td>';
	print "</tr></table>";
	$db->free();
}
else
{
	dol_print_error($db);
}


/*
 * Stats on errors
 */

print '<br>';
print load_fiche_titre($langs->trans("WithdrawRejectStatistics"), '', '');


// Define total and nbtotal
$sql = "SELECT sum(pl.amount), count(pl.amount)";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql.= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql.= " AND pb.entity = ".$conf->entity;
$sql.= " AND pl.statut = 3";
$resql=$db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    if ( $num > 0 )
    {
        $row = $db->fetch_row($resql);
        $total = $row[0];
        $nbtotal = $row[1];
    }
}

/*
 * Stats sur les rejets
 */
$sql = "SELECT sum(pl.amount), count(pl.amount) as cc, pr.motif";
$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_bons as pb";
$sql.= ", ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql.= " WHERE pl.fk_prelevement_bons = pb.rowid";
$sql.= " AND pb.entity = ".$conf->entity;
$sql.= " AND pl.statut = 3";
$sql.= " AND pr.fk_prelevement_lignes = pl.rowid";
$sql.= " GROUP BY pr.motif";
$sql.= " ORDER BY cc DESC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print"\n<!-- debut table -->\n";
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td width="30%">'.$langs->trans("Status").'</td><td align="center">'.$langs->trans("Number").'</td>';
	print '<td align="right">%</td><td align="right">'.$langs->trans("Amount").'</td><td align="right">%</td></tr>';

	require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
	$Rejet = new RejetPrelevement($db, $user);

	while ($i < $num)
	{
		$row = $db->fetch_row($resql);

		print '<tr class="oddeven"><td>';
		print $Rejet->motifs[$row[2]];

		print '</td><td align="center">'.$row[1];

		print '</td><td align="right">';
		print round($row[1]/$nbtotal*100,2)." %";

		print '</td><td align="right">';
		print price($row[0]);

		print '</td><td align="right">';
		print round($row[0]/$total*100,2)." %";

		print '</td></tr>';
		
		$i++;
	}

	print '<tr class="liste_total"><td align="right">'.$langs->trans("Total").'</td><td align="center">'.$nbtotal.'</td>';
	print '<td>&nbsp;</td><td align="right">';
	print price($total);
	print '</td><td align="right">&nbsp;</td>';
	print "</tr></table>";
	$db->free($resql);
}
else
{
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();

