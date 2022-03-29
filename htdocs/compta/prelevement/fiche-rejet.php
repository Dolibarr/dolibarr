<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
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
 * 		\file       htdocs/compta/prelevement/fiche-rejet.php
 *      \ingroup    prelevement
 *		\brief      Debit order or credit transfer reject
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/prelevement.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/rejetprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array("banks", "categories", 'withdrawals', 'bills'));

// Security check
if ($user->socid > 0) accessforbidden();

// Get supervariables
$prev_id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$type = GETPOST('type', 'aZ09');

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$object = new BonPrelevement($db);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals

if (!$user->rights->prelevement->bons->lire && $object->type != 'bank-transfer') {
	accessforbidden();
}
if (!$user->rights->paymentbybanktransfer->read && $object->type == 'bank-transfer') {
	accessforbidden();
}



/*
 * View
 */

llxHeader('', $langs->trans("WithdrawalsReceipts"));

if ($prev_id > 0 || $ref)
{
  	if ($object->fetch($prev_id, $ref) >= 0)
	{
		$head = prelevement_prepare_head($object);
		print dol_get_fiche_head($head, 'rejects', $langs->trans("WithdrawalsReceipts"), -1, 'payment');

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/prelevement/orders_list.php?restore_lastsearch_values=1'.($object->type != 'bank-transfer' ? '' : '&type=bank-transfer').'">'.$langs->trans("BackToList").'</a>';

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref');

		print '<div class="fichecenter">';
		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">'."\n";

		//print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td><td>'.$object->getNomUrl(1).'</td></tr>';
		print '<tr><td class="titlefield">'.$langs->trans("Date").'</td><td>'.dol_print_date($object->datec, 'day').'</td></tr>';
		print '<tr><td>'.$langs->trans("Amount").'</td><td>'.price($object->amount).'</td></tr>';

		if ($object->date_trans <> 0)
		{
			$muser = new User($db);
			$muser->fetch($object->user_trans);

			print '<tr><td>'.$langs->trans("TransData").'</td><td>';
			print dol_print_date($object->date_trans, 'day');
			print ' <span class="opacitymedium">'.$langs->trans("By").'</span> '.$muser->getFullName($langs).'</td></tr>';
			print '<tr><td>'.$langs->trans("TransMetod").'</td><td>';
			print $object->methodes_trans[$object->method_trans];
			print '</td></tr>';
		}
		if ($object->date_credit <> 0)
		{
			print '<tr><td>'.$langs->trans('CreditDate').'</td><td>';
			print dol_print_date($object->date_credit, 'day');
			print '</td></tr>';
		}

		print '</table>';

		print '<br>';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent tableforfield">';

		$acc = new Account($db);
		$result = $acc->fetch($conf->global->PRELEVEMENT_ID_BANKACCOUNT);

		print '<tr><td class="titlefield">';
		$labelofbankfield = "BankToReceiveWithdraw";
		if ($object->type == 'bank-transfer') $labelofbankfield = 'BankToPayCreditTransfer';
		print $langs->trans($labelofbankfield);
		print '</td>';
		print '<td>';
		if ($acc->id > 0)
			print $acc->getNomUrl(1);
		print '</td>';
		print '</tr>';

		print '<tr><td class="titlefield">';
		$labelfororderfield = 'WithdrawalFile';
		if ($object->type == 'bank-transfer') $labelfororderfield = 'CreditTransferFile';
		print $langs->trans($labelfororderfield).'</td><td>';
		$relativepath = 'receipts/'.$object->ref.'.xml';
		$modulepart = 'prelevement';
		if ($object->type == 'bank-transfer') $modulepart = 'paymentbybanktransfer';
		print '<a data-ajax="false" href="'.DOL_URL_ROOT.'/document.php?type=text/plain&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">'.$relativepath.'</a>';
		print '</td></tr></table>';

		print '</div>';

		print dol_get_fiche_end();
	} else {
	  	dol_print_error($db);
	}
}


$rej = new RejetPrelevement($db, $user, $type);

/*
 * List errors
 */
$sql = "SELECT pl.rowid, pl.amount, pl.statut";
$sql .= " , s.rowid as socid, s.nom as name";
$sql .= " , pr.motif, pr.afacturer, pr.fk_facture";
$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_lignes as pl";
$sql .= " , ".MAIN_DB_PREFIX."societe as s";
$sql .= " , ".MAIN_DB_PREFIX."prelevement_rejet as pr";
$sql .= " WHERE p.rowid=".$object->id;
$sql .= " AND pl.fk_prelevement_bons = p.rowid";
$sql .= " AND p.entity = ".$conf->entity;
$sql .= " AND pl.fk_soc = s.rowid";
$sql .= " AND pl.statut = 3 ";
$sql .= " AND pr.fk_prelevement_lignes = pl.rowid";
if ($socid) $sql .= " AND s.rowid = ".$socid;
$sql .= " ORDER BY pl.amount DESC";

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql)
{
 	$num = $db->num_rows($resql);

 	print_barre_liste($langs->trans("Rejects"), $page, $_SERVER["PHP_SELF"], $urladd, $sortfield, $sortorder, '', $num, $nbtotalofrecords, '');

  	print"\n<!-- debut table -->\n";
  	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you dont need reserved height for your table
  	print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  	print '<tr class="liste_titre">';
  	print '<td>'.$langs->trans("Line").'</td><td>'.$langs->trans("ThirdParty").'</td><td class="right">'.$langs->trans("Amount").'</td>';
  	print '<td>'.$langs->trans("Reason").'</td><td align="center">'.$langs->trans("ToBill").'</td><td class="center">'.$langs->trans("Invoice").'</td></tr>';

	$total = 0;

	if ($num > 0)
	{
	  	$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven"><td>';

			print '<a href="'.DOL_URL_ROOT.'/compta/prelevement/line.php?id='.$obj->rowid.'">';
			print img_picto('', 'statut'.$obj->statut).' ';
			print substr('000000'.$obj->rowid, -6);
			print '</a></td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->name."</a></td>\n";

			print '<td class="right">'.price($obj->amount)."</td>\n";
			print '<td>'.$rej->motifs[$obj->motif].'</td>';

			print '<td class="center">'.yn($obj->afacturer).'</td>';
			print '<td class="center">'.$obj->fk_facture.'</td>';
			print "</tr>\n";

			$total += $obj->amount;

			$i++;
		}
	} else {
		print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}

  	if ($num > 0)
	{
		print '<tr class="liste_total"><td>&nbsp;</td>';
		print '<td class="liste_total">'.$langs->trans("Total").'</td>';
		print '<td class="right">'.price($total)."</td>\n";
		print '<td colspan="3">&nbsp;</td>';
		print "</tr>\n";
	}
	print "</table>\n";
	print '</div>';

	$db->free($resql);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
