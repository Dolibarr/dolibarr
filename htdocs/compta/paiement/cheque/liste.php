<?php
/* Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *   \file       htdocs/compta/paiement/cheque/liste.php
 *   \ingroup    compta
 *   \brief      Page liste des bordereau de remise de cheque
 */

require('../../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("bills");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '','');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="bc.number";

$checkdepositstatic=new RemiseCheque($db);
$accountstatic=new Account($db);


/*
 * View
 */

llxHeader('',$langs->trans("ChequesReceipts"));

$sql = "SELECT bc.rowid, bc.number as ref, bc.date_bordereau as dp,";
$sql.= " bc.nbcheque, bc.amount, bc.statut,";
$sql.= " ba.rowid as bid, ba.label";
$sql.= " FROM ".MAIN_DB_PREFIX."bordereau_cheque as bc,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba";
$sql.= " WHERE bc.fk_bank_account = ba.rowid";
$sql.= " AND bc.entity = ".$conf->entity;
if (GETPOST('search_montant'))
{
	$sql.=" AND bc.amount=".price2num(GETPOST('search_montant'));
}
$sql.= " ORDER BY $sortfield $sortorder";
$sql.= $db->plimit($limit+1, $offset);
//print "$sql";

$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	$params='';

	print_barre_liste($langs->trans("MenuChequeDeposits"), $page, 'liste.php', $params, $sortfield, $sortorder, '', $num);

	print '<form method="get" action="liste.php">';
	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","bc.number","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),"liste.php","dp","",$params,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Account"),"liste.php","ba.label","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("NbOfCheques"),"liste.php","bc.nbcheque","",$params,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Amount"),"liste.php","bc.amount","",$params,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php","bc.statut","",$params,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input class="fat" type="text" size="6" name="search_montant" value="'.GETPOST('search_montant').'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print "</tr>\n";

	$var=true;
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";

		// Num ref cheque
		print '<td width="80">';
		$checkdepositstatic->id=$objp->rowid;
		$checkdepositstatic->ref=($objp->ref?$objp->ref:$objp->rowid);
		$checkdepositstatic->statut=$objp->statut;
		print $checkdepositstatic->getNomUrl(1);
		print '</td>';

		// Date
		print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'day').'</td>';

		// Banque
		print '<td>';
		if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</a>';
		else print '&nbsp;';
		print '</td>';

		// Nb of cheques
		print '<td align="right">'.$objp->nbcheque.'</td>';

		// Montant
		print '<td align="right">'.price($objp->amount).'</td>';

		// Statut
		print '<td align="right">';
		print $checkdepositstatic->LibStatut($objp->statut,5);
		print "</td></tr>\n";
		$i++;
	}
	print "</table>";
	print "</form>\n";
}
else
{
	dol_print_error($db);
}

$db->close();

llxFooter();
?>
