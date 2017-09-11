<?php
/* Copyright (C) 2011-2016	Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015-2016	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2015		Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	    \file       htdocs/compta/salaries/index.php
 *      \ingroup    salaries
 *		\brief     	List of salaries payments
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/salaries/class/paymentsalary.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("compta");
$langs->load("salaries");
$langs->load("bills");
$langs->load("hrm");

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'salaries', '', '', '');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$search_ref = GETPOST('search_ref','int');
$search_user = GETPOST('search_user','alpha');
$search_label = GETPOST('search_label','alpha');
$search_amount = GETPOST('search_amount','alpha');
$search_account = GETPOST('search_account','int');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="s.datep";
if (! $sortorder) $sortorder="DESC";
$optioncss = GETPOST('optioncss','alpha');

$filtre=$_GET["filtre"];

if (empty($_REQUEST['typeid']))
{
	$newfiltre=str_replace('filtre=','',$filtre);
	$filterarray=explode('-',$newfiltre);
	foreach($filterarray as $val)
	{
		$part=explode(':',$val);
		if ($part[0] == 's.fk_typepayment') $typeid=$part[1];
	}
}
else
{
	$typeid=$_REQUEST['typeid'];
}

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_amount="";
	$search_account='';
    $typeid="";
}

/*
 * View
 */

llxHeader();

$form = new Form($db);
$salstatic = new PaymentSalary($db);
$userstatic = new User($db);
$accountstatic = new Account($db);

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.admin, u.salary as current_salary, u.fk_soc as fk_soc,";
$sql.= " s.rowid, s.fk_user, s.amount, s.salary, s.label, s.datep as datep, s.datev as datev, s.fk_typepayment as type, s.num_payment, s.fk_bank,";
$sql.= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number, ba.accountancy_journal, ba.label as blabel,";
$sql.= " pst.code as payment_code";
$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON s.fk_typepayment = pst.id";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON s.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE u.rowid = s.fk_user";
$sql.= " AND s.entity = ".$conf->entity;

// Search criteria
if ($search_ref)	$sql.=" AND s.rowid=".$search_ref;
if ($search_user)   $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname', 'u.email', 'u.note'), $search_user);
if ($search_label) 	$sql.=natural_search(array('s.label'), $search_label);
if ($search_amount) $sql.=natural_search("s.amount", $search_amount, 1);
if ($search_account > 0) $sql .=" AND b.fk_account=".$search_account;
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND s.fk_typepayment=".$typeid;
}
$sql.= $db->order($sortfield,$sortorder);

//$sql.= " GROUP BY u.rowid, u.lastname, u.firstname, s.rowid, s.fk_user, s.amount, s.label, s.datev, s.fk_typepayment, s.num_payment, pst.code";
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
	if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
    if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
    print '<input type="hidden" name="action" value="list">';
    print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
    print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

	print_barre_liste($langs->trans("SalariesPayments"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num, $totalnboflines, 'title_accountancy.png', 0, '', '', $limit);
	
    print '<div class="div-table-responsive">';
    print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"s.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Employee"),$_SERVER["PHP_SELF"],"u.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"s.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"s.datep","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PaymentMode"),$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
    if (! empty($conf->banque->enabled)) print_liste_field_titre($langs->trans("BankAccount"),$_SERVER["PHP_SELF"],"ba.label","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"s.amount","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

	print '<tr class="liste_titre">';
	// Ref
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="3" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	// Employee
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="6" name="search_user" value="'.$search_user.'">';
	print '</td>';
	// Label
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="'.$search_label.'"></td>';
	// Date
	print '<td class="liste_titre">&nbsp;</td>';
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
	// Amount
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
        print "<tr ".$bc[$var].">";

        $userstatic->id=$obj->uid;
        $userstatic->lastname=$obj->lastname;
        $userstatic->firstname=$obj->firstname;
        $userstatic->admin=$obj->admin;
        $userstatic->login=$obj->login;
        $userstatic->email=$obj->email;
        $userstatic->societe_id=$obj->fk_soc;

        $salstatic->id=$obj->rowid;
		$salstatic->ref=$obj->rowid;
        // Ref
		print "<td>".$salstatic->getNomUrl(1)."</td>\n";
		// Employee
		print "<td>".$userstatic->getNomUrl(1)."</td>\n";
		// Label payment
        print "<td>".dol_trunc($obj->label,40)."</td>\n";
		// Date payment
        print '<td align="center">'.dol_print_date($db->jdate($obj->datep),'day')."</td>\n";
        // Type
        print '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';
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
        print "<td align=\"right\">".price($obj->amount)."</td>";
        print "<td></td>";
        print "</tr>\n";

        $total = $total + $obj->amount;

        $i++;
    }

    $colspan=5;
    if (! empty($conf->banque->enabled)) $colspan++;
    print '<tr class="liste_total"><td colspan="'.$colspan.'" class="liste_total">'.$langs->trans("Total").'</td>';
    print '<td class="liste_total" align="right">'.price($total)."</td>";
	print "<td></td></tr>";

    print "</table>";
    print '</div>';
	print '</form>';

    $db->free($result);
}
else
{
    dol_print_error($db);
}


llxFooter();
$db->close();
