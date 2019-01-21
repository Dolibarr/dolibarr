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

// Load translation files required by the page
$langs->loadLangs(array("banks","categories",'withdrawals','bills'));

// Security check
if ($user->societe_id > 0) accessforbidden();

// Get supervariables
$prev_id = GETPOST('id','int');
$ref = GETPOST('ref', 'alpha');

// Load variable for pagination
$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST('sortfield','alpha');
$sortorder = GETPOST('sortorder','alpha');
$page = GETPOST('page','int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


$object = new BonPrelevement($db,"");


/*
 * View
 */

llxHeader('',$langs->trans("WithdrawalsReceipts"));

if ($prev_id > 0 || $ref)
{
	if ($object->fetch($prev_id, $ref) >= 0)
	{
		$head = prelevement_prepare_head($object);
		dol_fiche_head($head, 'statistics', $langs->trans("WithdrawalsReceipts"), -1, 'payment');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/bons.php">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">'."\n";

		//print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->getNomUrl(1).'</td></tr>';
		print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td>'.dol_print_date($object->datec,'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount).'</td></tr>';

		// Status
		/*
		print '<tr><td>'.$langs->trans('Status').'</td>';
		print '<td>'.$object->getLibStatut(1).'</td>';
		print '</tr>';
		*/

		if($object->date_trans <> 0)
		{
			$muser = new User($db);
			$muser->fetch($object->user_trans);

			print '<tr><td>'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($object->date_trans,'day');
			print ' '.$langs->trans("By").' '.$muser->getFullName($langs).'</td></tr>';
			print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
			print $object->methodes_trans[$object->method_trans];
			print '</td></tr>';
		}
		if($object->date_credit <> 0)
		{
			print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($object->date_credit,'day');
			print '</td></tr>';
		}

		print '</table>';

		print '<br>';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border" width="100%">';

		$acc = new Account($db);
		$result=$acc->fetch($conf->global->PRELEVEMENT_ID_BANKACCOUNT);

		print '<tr><td class="titlefield">';
		print $langs->trans("BankToReceiveWithdraw");
		print '</td>';
		print '<td>';
		if ($acc->id > 0)
			print $acc->getNomUrl(1);
		print '</td>';
		print '</tr>';

		print '<tr><td class="titlefield">';
		print $langs->trans("WithdrawalFile").'</td><td>';
		$relativepath = 'receipts/'.$object->ref.'.xml';
		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart=prelevement&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';
		print '</td></tr></table>';

		print '</div>';

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
	$sql.= " WHERE pl.fk_prelevement_bons = ".$object->id;
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

		while ($i < $num)
		{
			$row = $db->fetch_row($resql);

			print '<tr class="oddeven"><td>';

			print $ligne->LibStatut($row[1],1);

			print '</td><td align="right">';
			print price($row[0]);

			print '</td><td align="right">';
			if ($object->amount) print round($row[0]/$object->amount*100,2)." %";
			print '</td>';

			print "</tr>\n";


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
