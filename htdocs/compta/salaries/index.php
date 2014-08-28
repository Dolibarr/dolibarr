<?php
/* Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortfield) $sortfield="s.datev";
if (! $sortorder) $sortorder="DESC";

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

/*
 * View
 */

llxHeader();

$form = new Form($db);
$salstatic = new PaymentSalary($db);
$userstatic = new User($db);

$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, s.rowid, s.fk_user, s.amount, s.label, s.datev as dm, s.fk_typepayment as type, s.num_payment,";
$sql.= " pst.code as payment_code";
$sql.= " FROM ".MAIN_DB_PREFIX."payment_salary as s";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON s.fk_typepayment = pst.id,";
$sql.= " ".MAIN_DB_PREFIX."user as u";
$sql.= " WHERE u.rowid = s.fk_user";
$sql.= " AND s.entity = ".$conf->entity;
if (GETPOST("search_label")) $sql.=" AND s.label LIKE '%".$db->escape(GETPOST("search_label"))."%'";
if (GETPOST("search_amount")) $sql.=" AND s.amount = ".price2num(GETPOST("search_amount"));
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND s.fk_typepayment=".$typeid;
}
//$sql.= " GROUP BY u.rowid, u.lastname, u.firstname, s.rowid, s.fk_user, s.amount, s.label, s.datev, s.fk_typepayment, s.num_payment, pst.code";
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

	print_barre_liste($langs->trans("SalariesPayments"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$totalnboflines);

	dol_htmloutput_mesg($mesg);

	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"s.rowid","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Person"),$_SERVER["PHP_SELF"],"u.rowid","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"s.label","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"s.datev","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"s.amount","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre("");
    print "</tr>\n";

	print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre"><input type="text" class="flat" size="14" name="search_label" value="'.GETPOST("search_label").'"></td>';
	print '<td class="liste_titre">&nbsp;</td>';
	// Type
	print '<td class="liste_titre" align="left">';
	$form->select_types_paiements($typeid,'typeid','',0,0,1,16);
	print '</td>';
	print '<td class="liste_titre" align="right"><input name="search_amount" class="flat" type="text" size="8" value="'.GETPOST("search_amount").'"></td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="image" class="liste_titre" src="'.img_picto($langs->trans("Search"),'search.png','','',1).'" name="button_search" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
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
        $salstatic->id=$obj->rowid;
		$salstatic->ref=$obj->rowid;
        print "<td>".$salstatic->getNomUrl(1)."</td>\n";
		print "<td>".$userstatic->getNomUrl(1)."</td>\n";
        print "<td>".dol_trunc($obj->label,40)."</td>\n";
        print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day')."</td>\n";
        // Type
        print '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';
		// Amount
        print "<td align=\"right\">".price($obj->amount,0,$outputlangs,1,-1,-1,$conf->currency)."</td>";
        print "<td>&nbsp;</td>";
        print "</tr>\n";

        $total = $total + $obj->amount;

        $i++;
    }
    print '<tr class="liste_total"><td colspan="5" class="liste_total">'.$langs->trans("Total").'</td>';
    print '<td  class="liste_total" align="right">'.price($total,0,$outputlangs,1,-1,-1,$conf->currency)."</td>";
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
