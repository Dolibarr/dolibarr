<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
 * Copyright (C) 2014       Florian Henry        <florian.henry@open-cooncept.pro>
 * Copyright (C) 2015       Jean-François Ferry  <jfefe@aternatik.fr>
 * Copyright (C) 2016       Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2017       Alexandre Spangaro   <aspangaro@zendsi.com>
 * Copyright (C) 2018       Andreu Bisquerra	 <jove@bisquerra.com>
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
 *	\file       htdocs/compta/cashcontrol/report.php
 *	\ingroup    pos
 *	\brief      List of bank transactions
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/cashcontrol/class/cashcontrol.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$id = GETPOST('id','int');

$_GET['optioncss']="print";
include_once 'class/cashcontrol.class.php';
$cashcontrol= new CashControl($db);
$cashcontrol->fetch($id);

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortorder='ASC';
$sortfield='b.datev,b.dateo,b.rowid';

$arrayfields=array(
    'b.rowid'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    'b.dateo'=>array('label'=>$langs->trans("DateOperationShort"), 'checked'=>1),
    'b.num_chq'=>array('label'=>$langs->trans("Number"), 'checked'=>1),
    'ba.ref'=>array('label'=>$langs->trans("BankAccount"), 'checked'=>1),
    'b.debit'=>array('label'=>$langs->trans("Debit"), 'checked'=>1, 'position'=>600),
    'b.credit'=>array('label'=>$langs->trans("Credit"), 'checked'=>1, 'position'=>605),
);

$syear  = $cashcontrol->year_close;
$smonth = $cashcontrol->month_close;
$sday   = $cashcontrol->day_close;

$posmodule = $cashcontrol->posmodule;
$terminalid = $cashcontrol->posnumber;


/*
 * View
 */

llxHeader('', $langs->trans("CashControl"), '', '', 0, 0, array(), array(), $param);

/*$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro as conciliated, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.url_id,";
$sql.= " f.module_source, f.facnumber as facnumber";
$sql.= " FROM ";
//if ($bid) $sql.= MAIN_DB_PREFIX."bank_class as l,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql.= " ".MAIN_DB_PREFIX."bank as b";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'payment'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON bu.url_id = f.rowid";
$sql.= " WHERE b.fk_account = ba.rowid";
// Define filter on invoice
$sql.= " AND f.module_source = '".$db->escape($cashcontrol->posmodule)."'";
$sql.= " AND f.pos_source = '".$db->escape($cashcontrol->posnumber)."'";
$sql.= " AND f.entity IN (".getEntity('facture').")";
// Define filter on data
if ($syear && ! $smonth)              $sql.= " AND dateo BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
elseif ($syear && $smonth && ! $sday) $sql.= " AND dateo BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
elseif ($syear && $smonth && $sday)   $sql.= " AND dateo BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
else dol_print_error('', 'Year not defined');
// Define filter on bank account
$sql.=" AND (b.fk_account=".$conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
$sql.=" OR b.fk_account=".$conf->global->CASHDESK_ID_BANKACCOUNT_CB;
$sql.=" OR b.fk_account=".$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
$sql.=")";
*/
$sql = "SELECT f.facnumber, pf.amount as total, cp.code";
$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."c_paiement as cp";
$sql.= " WHERE pf.fk_facture = f.rowid AND p.rowid = pf.fk_paiement AND cp.id = p.fk_paiement";
$sql.= " AND f.module_source = '".$db->escape($posmodule)."'";
$sql.= " AND f.pos_source = '".$db->escape($terminalid)."'";
$sql.= " AND f.paye = 1";
$sql.= " AND p.entity IN (".getEntity('facture').")";
/*if ($key == 'cash')       $sql.=" AND cp.code = 'LIQ'";
elseif ($key == 'cheque') $sql.=" AND cp.code = 'CHQ'";
elseif ($key == 'card')   $sql.=" AND cp.code = 'CB'";
else
{
	dol_print_error('Value for key = '.$key.' not supported');
	exit;
}*/
if ($syear && ! $smonth)              $sql.= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, 1))."' AND '".$db->idate(dol_get_last_day($syear, 12))."'";
elseif ($syear && $smonth && ! $sday) $sql.= " AND datef BETWEEN '".$db->idate(dol_get_first_day($syear, $smonth))."' AND '".$db->idate(dol_get_last_day($syear, $smonth))."'";
elseif ($syear && $smonth && $sday)   $sql.= " AND datef BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $smonth, $sday, $syear))."' AND '".$db->idate(dol_mktime(23, 59, 59, $smonth, $sday, $syear))."'";
else dol_print_error('', 'Year not defined');

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print "<center><h2>";
	if ($cashcontrol->status==2) print $langs->trans("CashControl")." ".$cashcontrol->id;
	else print $langs->trans("CashControl")." - ".$langs->trans("Draft");
	print "<br>".$langs->trans("DateCreationShort").": ".dol_print_date($cashcontrol->date_creation, 'dayhour')."</h2></center>";


    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste">'."\n";

	// Fields title
	print '<tr class="liste_titre">';
	print_liste_field_titre($arrayfields['b.rowid']['label'],$_SERVER['PHP_SELF'],'b.rowid','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($arrayfields['b.dateo']['label'],$_SERVER['PHP_SELF'],'b.dateo','',$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($arrayfields['ba.ref']['label'],$_SERVER['PHP_SELF'],'ba.ref','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($arrayfields['b.debit']['label'],$_SERVER['PHP_SELF'],'b.amount','',$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($arrayfields['b.credit']['label'],$_SERVER['PHP_SELF'],'b.amount','',$param,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

    $balance = 0;    // For balance
	$balancecalculated = false;
	$posconciliatecol = 0;

	// Loop on each record
	$sign = 1;
	$first='yes';

    $totalarray=array();
    while ($i < min($num,$limit))
    {
        $objp = $db->fetch_object($resql);

        // If we are in a situation where we need/can show balance, we calculate the start of balance
        if (! $balancecalculated && (! empty($arrayfields['balancebefore']['checked']) || ! empty($arrayfields['balance']['checked'])) && $mode_balance_ok)
        {
            if (! $account)
            {
                dol_print_error('', 'account is not defined but $mode_balance_ok is true');
                exit;
            }

            // Loop on each record before
            $sign = 1;
            $i = 0;
            $sqlforbalance='SELECT SUM(b.amount) as balance';
            $sqlforbalance.= " FROM ";
            $sqlforbalance.= " ".MAIN_DB_PREFIX."bank_account as ba,";
            $sqlforbalance.= " ".MAIN_DB_PREFIX."bank as b";
            $sqlforbalance.= " WHERE b.fk_account = ba.rowid";
            $sqlforbalance.= " AND ba.entity IN (".getEntity('bank_account').")";
            $sqlforbalance.= " AND b.fk_account = ".$account;
            $sqlforbalance.= " AND (b.datev < '" . $db->idate($db->jdate($objp->dv)) . "' OR (b.datev = '" . $db->idate($db->jdate($objp->dv)) . "' AND (b.dateo < '".$db->idate($db->jdate($objp->do))."' OR (b.dateo = '".$db->idate($db->jdate($objp->do))."' AND b.rowid < ".$objp->rowid."))))";
            $resqlforbalance = $db->query($sqlforbalance);
            if ($resqlforbalance)
            {
                $objforbalance = $db->fetch_object($resqlforbalance);
                if ($objforbalance)
                {
                    $balance = $objforbalance->balance;
                }
            }
            else dol_print_error($db);

            $balancecalculated=true;
        }

        $balance = price2num($balance + ($sign * $objp->amount),'MT');

        if (empty($cachebankaccount[$objp->bankid]))
        {
            $bankaccounttmp = new Account($db);
            $bankaccounttmp->fetch($objp->bankid);
            $cachebankaccount[$objp->bankid]=$bankaccounttmp;
            $bankaccount = $bankaccounttmp;
        }
        else
        {
            $bankaccount = $cachebankaccount[$objp->bankid];
        }
        print '<tr class="oddeven">';

		if ($first == "yes")
		{
			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("InitialBankBalance").' - '.$langs->trans("Cash").'</td>';
			print '<td></td><td></td><td></td><td align="right">'.price($cashcontrol->opening).'</td>';
			print '</tr>';
			$first = "no";
		}

		print '<tr class="oddeven">';

		// Ref
        print '<td align="left" class="nowrap">';
        print $objp->facnumber;
        print '</td>';
        if (! $i) $totalarray['nbfield']++;


        // Date ope
    	print '<td align="left" class="nowrap">';
    	print '<span id="dateoperation_'.$objp->rowid.'">'.dol_print_date($db->jdate($objp->do),"day")."</span>";
    	print "</td>\n";
        if (! $i) $totalarray['nbfield']++;

    	// Bank account
        print '<td align="right" class="nowrap">';
		print $bankaccount->getNomUrl(1);
		if ($sql.=$conf->global->CASHDESK_ID_BANKACCOUNT_CASH==$bankaccount->id) $cash+=$objp->amount;
		if ($sql.=$conf->global->CASHDESK_ID_BANKACCOUNT_CB==$bankaccount->id) $bank+=$objp->amount;
		if ($sql.=$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE==$bankaccount->id) $cheque+=$objp->amount;
		print "</td>\n";
        if (! $i) $totalarray['nbfield']++;

    	// Debit
    	print '<td align="right">';
    	if ($objp->amount < 0)
    	{
    	    print price($objp->amount * -1);
    	    $totalarray['totaldeb'] += $objp->amount;
    	}
    	print "</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    	if (! $i) $totalarray['totaldebfield']=$totalarray['nbfield'];

    	// Credit
    	print '<td align="right">';
    	if ($objp->amount > 0)
    	{
			print price($objp->amount);
    	    $totalarray['totalcred'] += $objp->amount;
    	}
    	print "</td>\n";
    	if (! $i) $totalarray['nbfield']++;
    	if (! $i) $totalarray['totalcredfield']=$totalarray['nbfield'];

		print "</tr>";

		$i++;
	}

	// Show total line
	if (isset($totalarray['totaldebfield']) || isset($totalarray['totalcredfield']))
	{
	    print '<tr class="liste_total">';
	    $i=0;
	    while ($i < $totalarray['nbfield'])
	    {
	        $i++;
	        if ($i == 1)
	        {
	            if ($num < $limit && empty($offset)) print '<td align="left">'.$langs->trans("Total").'</td>';
	            else print '<td align="left">'.$langs->trans("Totalforthispage").'</td>';
	        }
	        elseif ($totalarray['totaldebfield'] == $i) print '<td align="right">'.price(-1 * $totalarray['totaldeb']).'</td>';
	        elseif ($totalarray['totalcredfield'] == $i) print '<td align="right">'.price($totalarray['totalcred']).'</td>';
	        else print '<td></td>';
	    }
	    print '</tr>';
	}

	print "</table>";

	$cash=$cash+$cashcontrol->opening;
	print "<div style='text-align: right'><h2>";
	print $langs->trans("Cash").": ".price($cash)."<br><br>";
	print $langs->trans("PaymentTypeCB").": ".price($bank)."<br><br>";
	print $langs->trans("PaymentTypeCheque").": ".price($cheque)."<br><br>";
	print "</h2></div>";

	//save totals to DB
	/*
	$sql = "UPDATE ".MAIN_DB_PREFIX."pos_cash_fence ";
	$sql .= "SET";
	$sql .= " cash='".$cash."'";
    $sql .= ", card='".$bank."'";
	$sql .= " where rowid=".$id;
	$db->query($sql);
	*/

	print "</div>";

    print '</form>';

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
