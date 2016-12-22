<?php
/* Copyright (C) 2011-2014 Alexandre Spangaro  <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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

$langs->load("compta");
$langs->load("salaries");
$langs->load("bills");

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'salaries', '', '', '');

$search_ref = GETPOST('search_ref','int');
$search_user = GETPOST('search_user','alpha');
$search_label = GETPOST('search_label','alpha');
$search_amount = GETPOST('search_amount','alpha');
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
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

if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter")) // Both test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_amount="";
    $typeid="";
}

/*
 * View
 */

llxHeader();

$form = new Form($db);
$salstatic = new PaymentSalary($db);
$userstatic = new User($db);

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.login, u.email, u.admin, u.salary as current_salary, u.fk_soc as fk_soc,";
$sql.= " s.rowid, s.fk_user, s.amount, s.salary, s.label, s.datep as datep, s.datev as datev, s.fk_typepayment as type, s.num_payment,";
$sql.= " pst.code as payment_code";
$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON s.fk_typepayment = pst.id,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE u.rowid = s.fk_user";
$sql.= " AND s.entity = ".$conf->entity;

// Search criteria
if ($search_ref)	$sql.=" AND s.rowid=".$search_ref;
if ($search_user)   $sql.=natural_search(array('u.login', 'u.lastname', 'u.firstname', 'u.email', 'u.note'), $search_user);
if ($search_label) 	$sql.=natural_search(array('s.label'), $search_label);
if ($search_amount) $sql.=natural_search("s.amount", $search_amount, 1);
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND s.fk_typepayment=".$typeid;
}
//$sql.= " GROUP BY u.rowid, u.lastname, u.firstname, s.rowid, s.fk_user, s.amount, s.label, s.datev, s.fk_typepayment, s.num_payment, pst.code";
$totalnboflines=0;
$result=$db->query($sql);
if ($result)
{
    $totalnboflines = $db->num_rows($result);
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1,$offset);

$result = $db->query($sql);
if ($result)
{
    $num = $db->num_rows($result);
    $i = 0;
    $total = 0 ;
	$var=true;

	$param='';
	if ($typeid) $param.='&amp;typeid='.$typeid;
	if ($optioncss != '') $param.='&amp;optioncss='.$optioncss;

	print_barre_liste($langs->trans("SalariesPayments"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$totalnboflines, 'title_accountancy.png');

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"s.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"u.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ExpectedToPay"),$_SERVER["PHP_SELF"],"s.salary","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"s.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"s.datep","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PaymentMode"),$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"s.amount","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
    print "</tr>\n";

	print '<tr class="liste_titre">';
	// Ref
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="3" name="search_ref" value="'.$search_ref.'">';
	print '</td>';
	// People
	print '<td class="liste_titre">';
	print '<input class="flat" type="text" size="6" name="search_user" value="'.$search_user.'">';
	print '</td>';
	// Current salary
	print '<td class="liste_titre">&nbsp;</td>';
	// Label
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="'.$search_label.'"></td>';
	// Date
	print '<td class="liste_titre">&nbsp;</td>';
	// Type
	print '<td class="liste_titre" align="left">';
	$form->select_types_paiements($typeid,'typeid','',0,0,1,16);
	print '</td>';
	// Amount
	print '<td class="liste_titre" align="right"><input name="search_amount" class="flat" type="text" size="8" value="'.$search_amount.'"></td>';
	print '<td class="liste_titre" align="right"><input type="image" class="liste_titre" name="button_search" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '<input type="image" class="liste_titre" name="button_removefilter" src="'.img_picto($langs->trans("Search"),'searchclear.png','','',1).'" value="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'" title="'.dol_escape_htmltag($langs->trans("RemoveFilter")).'">';
	print "</td></tr>\n";

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
		// User name
		print "<td>".$userstatic->getNomUrl(1)."</td>\n";
		// Current salary
		print "<td>".($obj->salary?price($obj->salary):'')."</td>\n";
		// Label payment
        print "<td>".dol_trunc($obj->label,40)."</td>\n";
		// Date payment
        print '<td align="center">'.dol_print_date($db->jdate($obj->datep),'day')."</td>\n";
        // Type
        print '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';
		// Amount
        print "<td align=\"right\">".price($obj->amount)."</td>";
        print "<td></td>";
        print "</tr>\n";

        $total = $total + $obj->amount;

        $i++;
    }
    
    print '<tr class="liste_total"><td colspan="6" class="liste_total">'.$langs->trans("Total").'</td>';
    print '<td  class="liste_total" align="right">'.price($total,0,$outputlangs,1,-1,-1,$conf->currency)."</td>";
	print "<td></td></tr>";

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
