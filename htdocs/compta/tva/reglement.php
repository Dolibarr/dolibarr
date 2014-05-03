<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2014 Alexandre Spangaro   <alexandre.spangaro@gmail.com>
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

$langs->load("compta");
$langs->load("bills");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', '', '', 'charges');

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
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

/*
 * View
 */

llxHeader();

$form = new Form($db);
$tva_static = new Tva($db);

$sql = "SELECT t.rowid, t.amount, t.label, t.datev as dm, t.fk_typepayment as type, t.num_payment, pst.code as payment_code";
$sql.= " FROM ".MAIN_DB_PREFIX."tva as t";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON t.fk_typepayment = pst.id";
$sql.= " WHERE t.entity = ".$conf->entity;
if (GETPOST("search_label")) $sql.=" AND t.label LIKE '%".$db->escape(GETPOST("search_label"))."%'";
if (GETPOST("search_amount")) $sql.=" AND t.amount = ".price2num(GETPOST("search_amount"));
if ($filtre) {
    $filtre=str_replace(":","=",$filtre);
    $sql .= " AND ".$filtre;
}
if ($typeid) {
    $sql .= " AND t.fk_typepayment=".$typeid;
}
$sql.= " GROUP BY t.rowid, t.fk_typepayment, t.amount, t.datev, t.label";
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
	
	print_barre_liste($langs->trans("VATPayments"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num,$totalnboflines);

	dol_htmloutput_mesg($mesg);
	
	print '<form method="GET" action="'.$_SERVER["PHP_SELF"].'">';

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"t.rowid","",$param,"",$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Label"),$_SERVER["PHP_SELF"],"t.label","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("DatePayment"),$_SERVER["PHP_SELF"],"dm","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Type"),$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("PayedByThisPayment"),$_SERVER["PHP_SELF"],"t.amount","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre("");
	print "</tr>\n";
	
	print '<tr class="liste_titre">';
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
        print '<td align="left">'.dol_print_date($db->jdate($obj->dm),'day')."</td>\n";
		// Type
		print $type;
		// Amount
        $total = $total + $obj->amount;
		print "<td align=\"right\">".price($obj->amount)."</td>";
		print "<td>&nbsp;</td>";
        print "</tr>\n";

        $i++;
    }
    print '<tr class="liste_total"><td colspan="4">'.$langs->trans("Total").'</td>';
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


$db->close();

llxFooter();
