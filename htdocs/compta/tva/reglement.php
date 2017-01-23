<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
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
 *	    \file       htdocs/compta/tva/reglement.php
 *      \ingroup    tax
 *		\brief      List of VAT payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$langs->load("compta");
$langs->load("bills");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$search_ref = GETPOST('search_ref','int');
$search_label = GETPOST('search_label','alpha');
$search_amount = GETPOST('search_amount','alpha');
$search_account = GETPOST('search_account','int');
$month = GETPOST("month","int");
$year = GETPOST("year","int");

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="t.datev";
if (! $sortorder) $sortorder="DESC";

$filtre=$_GET["filtre"];

if (empty($_REQUEST['typeid']))
{
	$newfiltre=str_replace('filtre=','',$filtre);
	$filterarray=explode('-',$newfiltre);
	foreach($filterarray as $val)
	{
		$part=explode(':',$val);
		if ($part[0] == 't.fk_typepayment') $typeid=$part[1];
	}
}
else
{
	$typeid=$_REQUEST['typeid'];
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_amount="";
	$search_account='';
	$year="";
	$month="";
    $typeid="";
}


/*
 * View
 */

llxHeader('', $langs->trans("VATPayments"));

$form = new Form($db);
$formother=new FormOther($db);
$tva_static = new Tva($db);
$accountstatic = new Account($db);

$sql = "SELECT t.rowid, t.amount, t.label, t.datev as dv, t.datep as dp, t.fk_typepayment as type, t.num_payment, t.fk_bank, pst.code as payment_code,";
$sql.= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.accountancy_journal, ba.label as blabel";
$sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON t.fk_typepayment = pst.id";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON t.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql.= " WHERE t.entity = ".$conf->entity;
if ($search_ref)	$sql.=" AND t.rowid=".$search_ref;
if ($search_label) 	$sql.=" AND t.label LIKE '%".$db->escape($search_label)."%'";
if ($search_amount) $sql.=" AND t.amount='".$db->escape(price2num(trim($search_amount)))."'";
if ($search_account > 0) $sql .=" AND b.fk_account=".$search_account;
if ($month > 0)
{
	if ($year > 0)
	$sql.= " AND t.datev BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
	else
	$sql.= " AND date_format(t.datev, '%m') = '$month'";
}
else if ($year > 0)
{
	$sql.= " AND t.datev BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
}
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND t.fk_typepayment=".$typeid;
}
$sql.= $db->order($sortfield,$sortorder);
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql.= $db->plimit($limit+1,$offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0 ;
	$var=true;

	$param='';
    if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
	if ($typeid) $param.='&amp;typeid='.$typeid;


	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	
	print_barre_liste($langs->trans("VATPayments"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$totalnboflines, 'title_accountancy', 0, '', '', $limit);
	
	print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"t.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"t.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DateValue"),$_SERVER["PHP_SELF"],"dv","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"dp","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
    if (! empty($conf->banque->enabled)) print_liste_field_titre($langs->trans("Account"),$_SERVER["PHP_SELF"],"ba.label","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"t.amount","",$param,'align="right"',$sortfield,$sortorder);
    print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre"><input type="text" class="flat" size="4" name="search_ref" value="'.$search_ref.'"></td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="'.$search_label.'"></td>';
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre" colspan="1" align="center">';
	print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
	$syear = $year;
	$formother->select_year($syear?$syear:-1,'year',1, 20, 5);
	print '</td>';
	// Type
	print '<td class="liste_titre" align="left">';
	$form->select_types_paiements($typeid,'typeid','',0,0,1,16);
	print '</td>';
	// Account
	if (! empty($conf->banque->enabled))
    {
	    print '<td class="liste_titre">';
	    $form->select_comptes($search_account,'search_account',0,'',1);
	    print '</td>';
    }
	print '<td class="liste_titre" align="right"><input name="search_amount" class="flat" type="text" size="8" value="'.$search_amount.'"></td>';
    print '<td class="liste_titre" align="right">';
    $searchpitco=$form->showFilterAndCheckAddButtons(0);
    print $searchpitco;
    print '</td>';
	print "</tr>\n";

	while ($i < min($num,$limit))
    {
        $obj = $db->fetch_object($result);
        $var=!$var;

		if ($obj->payment_code <> '')
		{
			$type = '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';
		}
		else
		{
			$type = '<td>&nbsp;</td>';
		}

        print "<tr ".$bc[$var].">";

		$tva_static->id=$obj->rowid;
		$tva_static->ref=$obj->rowid;
		print "<td>".$tva_static->getNomUrl(1)."</td>\n";
        print "<td>".dol_trunc($obj->label,40)."</td>\n";
        print '<td align="center">'.dol_print_date($db->jdate($obj->dv),'day')."</td>\n";
        print '<td align="center">'.dol_print_date($db->jdate($obj->dp),'day')."</td>\n";
        // Type
		print $type;
		// Account
    	if (! empty($conf->banque->enabled))
	    {
	        print '<td>';
	        if ($obj->fk_bank > 0)
	        {
	        	//$accountstatic->fetch($obj->fk_bank);
	            $accountstatic->id=$obj->bid;
	            $accountstatic->ref=$obj->bref;
	            $accountstatic->number=$obj->bnumber;
	            $accountstatic->accountancy_number=$obj->account_number;
	            $accountstatic->accountancy_journal=$obj->accountancy_journal;
	            $accountstatic->label=$obj->blabel;
	            print $accountstatic->getNomUrl(1);
	        }
	        else print '&nbsp;';
	        print '</td>';
	    }
		// Amount
        $total = $total + $obj->amount;
		print "<td align=\"right\">".price($obj->amount)."</td>";
	    print "<td>&nbsp;</td>";
        print "</tr>\n";

        $i++;
    }
    
    $colspan=5;
    if (! empty($conf->banque->enabled)) $colspan++;
    print '<tr class="liste_total"><td colspan="'.$colspan.'">'.$langs->trans("Total").'</td>';
    print "<td align=\"right\"><b>".price($total)."</b></td>";
	print "<td>&nbsp;</td></tr>";

    print "</table>";

	print '</form>';

    $db->free($result);
}
else
{
    dol_print_error($db);
}


llxFooter();

$db->close();
