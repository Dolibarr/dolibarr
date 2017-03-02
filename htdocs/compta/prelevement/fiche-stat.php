<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Juanjo Menent   <jmenent@2byte.es>
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
 *	\file       htdocs/compta/prelevement/fiche-stat.php
 *  \ingroup    prelevement
 *	\brief      Prelevement statistics
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/ligneprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("withdrawals");

// Security check
if ($user->societe_id > 0) accessforbidden();

// Get supervariables
$prev_id = GETPOST('id','int');
$page = GETPOST('page','int');

/*
 * View
 */

llxHeader('',$langs->trans("WithdrawalsReceipts"));

if ($prev_id)
{
	$bon = new BonPrelevement($db,"");

	if ($bon->fetch($prev_id) == 0)
	{
		$head = prelevement_prepare_head($bon);
		dol_fiche_head($head, 'statistics', $langs->trans("WithdrawalsReceipts"), '', 'payment');

		print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$bon->getNomUrl(1).'</td></tr>';
		print '<tr><td>'.$langs->trans("Date").'</td><td>'.dol_print_date($bon->datec,'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($bon->amount).'</td></tr>';

		// Status
		print '<tr><td>'.$langs->trans('Status').'</td>';
		print '<td>'.$bon->getLibStatut(1).'</td>';
		print '</tr>';

		if($bon->date_trans <> 0)
		{
			$muser = new User($db);
			$muser->fetch($bon->user_trans);

			print '<tr><td>'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($bon->date_trans,'day');
			print ' '.$langs->trans("By").' '.$muser->getFullName($langs).'</td></tr>';
			print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
			print $bon->methodes_trans[$bon->method_trans];
			print '</td></tr>';
		}
		if($bon->date_credit <> 0)
		{
			print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($bon->date_credit,'day');
			print '</td></tr>';
		}

		print '</table>';

		print '<br>';

		print '<table class="border" width="100%"><tr><td class="titlefield">';
		print $langs->trans("WithdrawalFile").'</td><td>';
		$relativepath = 'receipts/'.$bon->ref.'.xml';
		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';
		print '</td></tr></table>';

		dol_fiche_end();

	}
	else
	{
		$langs->load("errors");
		print $langs->trans("Error");
	}

	/*
	 * Stats
	 */
	$ligne=new LignePrelevement($db,$user);

	$sql = "SELECT sum(pl.amount), pl.statut";
	$sql.= " FROM ".MAIN_DB_PREFIX."prelevement_lignes as pl";
	$sql.= " WHERE pl.fk_prelevement_bons = ".$prev_id;
	$sql.= " GROUP BY pl.statut";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		print load_fiche_titre($langs->trans("StatisticsByLineStatus"),'','');
		
		print"\n<!-- debut table -->\n";
		print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Status").'</td><td align="right">'.$langs->trans("Amount").'</td><td align="right">%</td></tr>';

		$var=false;

		while ($i < $num)
		{
			$row = $db->fetch_row($resql);

			print "<tr ".$bc[$var]."><td>";

			print $ligne->LibStatut($row[1],1);

			print '</td><td align="right">';
			print price($row[0]);

			print '</td><td align="right">';
			if ($bon->amount) print round($row[0]/$bon->amount*100,2)." %";
			print '</td>';

			print "</tr>\n";

			$var=!$var;
			$i++;
		}

		print "</table>";
		$db->free($resql);
	}
	else
	{
		print $db->error() . ' ' . $sql;
	}
}

llxFooter();
$db->close();
