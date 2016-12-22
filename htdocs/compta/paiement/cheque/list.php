<?php
/* Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007-2009	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2014		Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
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
 *   \file       htdocs/compta/paiement/cheque/list.php
 *   \ingroup    compta
 *   \brief      Page list of cheque deposits
 */

require('../../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/cheque/class/remisecheque.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("bills");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '','');

$search_ref = GETPOST('search_ref','int');
$search_account = GETPOST('search_account','int');
$search_amount = GETPOST('search_amount','alpha');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="dp";

$year=GETPOST("year");
$month=GETPOST("month");

$form=new Form($db);
$formother = new FormOther($db);
$checkdepositstatic=new RemiseCheque($db);
$accountstatic=new Account($db);

// If click on purge search criteria ?
if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
    $search_ref='';
    $search_amount='';
    $search_account='';
    $year='';
    $month='';
}

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

// Search criteria
if ($search_ref)			$sql.=" AND bc.number=".$search_ref;
if ($search_account > 0)	$sql.=" AND bc.fk_bank_account=".$search_account;
if ($search_amount)			$sql.=" AND bc.amount='".$db->escape(price2num(trim($search_amount)))."'";
if ($month > 0)
{
    if ($year > 0 && empty($day))
    $sql.= " AND bc.date_bordereau BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
    else if ($year > 0 && ! empty($day))
    $sql.= " AND bc.date_bordereau BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
    else
    $sql.= " AND date_format(bc.date_bordereau, '%m') = '".$month."'";
}
else if ($year > 0)
{
	$sql.= " AND bc.date_bordereau BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
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

	print_barre_liste($langs->trans("MenuChequeDeposits"), $page, $_SERVER["PHP_SELF"], $params, $sortfield, $sortorder, '', $num);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	print '<table class="liste">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"bc.number","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateCreation"),$_SERVER["PHP_SELF"],"dp","",$params,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Account"),$_SERVER["PHP_SELF"],"ba.label","",$params,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("NbOfCheques"),$_SERVER["PHP_SELF"],"bc.nbcheque","",$params,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Amount"),$_SERVER["PHP_SELF"],"bc.amount","",$params,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"bc.statut","",$params,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="4" name="search_ref" value="'.$search_ref.'">';
    print '</td>';
	print '<td class="liste_titre" align="center">';
    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
    $formother->select_year($year?$year:-1,'year',1, 20, 5);
    print '</td>';
    print '<td>';
    $form->select_comptes($search_account,'search_account',0,'',1);
    print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input class="flat" type="text" size="6" name="search_amount" value="'.$search_amount.'">';
	print '</td>';
	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
    print "</td></tr>\n";

    if ($num > 0)
    {
    	$var=true;
    	while ($i < min($num,$limit))
    	{
    		$objp = $db->fetch_object($resql);
    		$var=!$var;
    		print "<tr ".$bc[$var].">";
    
    		// Num ref cheque
    		print '<td width="80">';
    		$checkdepositstatic->id=$objp->rowid;
    		$checkdepositstatic->ref=($objp->ref?$objp->ref:$objp->rowid);
    		$checkdepositstatic->statut=$objp->statut;
    		print $checkdepositstatic->getNomUrl(1);
    		print '</td>';
    
    		// Date
    		print '<td align="center">'.dol_print_date($db->jdate($objp->dp),'day').'</td>';  // TODO Use date hour
    
    		// Bank
    		print '<td>';
    		if ($objp->bid) print '<a href="'.DOL_URL_ROOT.'/compta/bank/account.php?account='.$objp->bid.'">'.img_object($langs->trans("ShowAccount"),'account').' '.$objp->label.'</a>';
    		else print '&nbsp;';
    		print '</td>';
    
    		// Number of cheques
    		print '<td align="right">'.$objp->nbcheque.'</td>';
    
    		// Amount
    		print '<td align="right">'.price($objp->amount).'</td>';
    
    		// Statut
    		print '<td align="right">';
    		print $checkdepositstatic->LibStatut($objp->statut,5);
    		print "</td></tr>\n";
    		$i++;
    	}
    }
    else
    {
   		$var=!$var;
   		print "<tr ".$bc[$var].">";
   		print '<td colspan="6">'.$langs->trans("None")."</td>";
   		print '</tr>';
    }
	print "</table>";
	print "</form>\n";
}
else
{
	dol_print_error($db);
}


llxFooter();
$db->close();
