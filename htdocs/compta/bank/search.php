<?php
/* Copyright (C) 2001-2002  Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2010  Regis Houssin        <regis.houssin@capnetworks.com>
 * Copytight (C) 2012       Vinícius Nogueira    <viniciusvgn@gmail.com>
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
 *	\file       htdocs/compta/bank/search.php
 *	\ingroup    banque
 *	\brief      List of bank transactions
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/bankcateg.class.php';

$langs->load("banks");
$langs->load("categories");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');

$description=GETPOST("description");
$debit=GETPOST("debit");
$credit=GETPOST("credit");
$type=GETPOST("type");
$account=GETPOST("account");
$bid=GETPOST("bid");

$param='';
if ($description) $param.='&description='.$description;
if ($type) $param.='&type='.$type;
if ($debit) $param.='&debit='.$debit;
if ($credit) $param.='&credit='.$credit;
if ($account) $param.='&account='.$account;
if ($bid)  $param.='&bid='.$bid;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
$limit = $conf->liste_limit;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='b.dateo';


/*
 * View
 */

$companystatic=new Societe($db);
$bankaccountstatic=new Account($db);

llxHeader();

$form = new Form($db);

if ($vline) $viewline = $vline;
else $viewline = 50;

$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.label as labelurl, bu.url_id";
$sql.= " FROM ";
if (! empty($_REQUEST["bid"])) $sql.= MAIN_DB_PREFIX."bank_class as l,";
$sql.= " ".MAIN_DB_PREFIX."bank_account as ba,";
$sql.= " ".MAIN_DB_PREFIX."bank as b";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu ON bu.fk_bank = b.rowid AND type = 'company'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON bu.url_id = s.rowid";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity = ".$conf->entity;
if (GETPOST("req_nb"))
{
    $sql.= " AND b.num_chq like '%".$db->escape(GETPOST("req_nb"))."%'";
    $param.='&amp;req_nb='.urlencode(GETPOST("req_nb"));
}
if (GETPOST("thirdparty"))
{
    $sql.=" AND (COALESCE(s.nom,'') LIKE '%".$db->escape(GETPOST("thirdparty"))."%')";
    $param.='&amp;thirdparty='.urlencode(GETPOST("thirdparty"));
}
if (! empty($_REQUEST["bid"]))
{
	$sql.= " AND b.rowid=l.lineid AND l.fk_categ=".$_REQUEST["bid"];
}
if(! empty($type))
{
	$sql .= " AND b.fk_type = '" . $type ."' ";
}
// Search criteria amount
$si=0;
$debit = price2num(str_replace('-','',$debit));
$credit = price2num(str_replace('-','',$credit));
if (is_numeric($debit)) {
	$si++;
	$sqlw[$si] .= " b.amount = -" . $debit;
}
if (is_numeric($credit)) {
	$si++;
	$sqlw[$si] .= " b.amount = " . $credit;
}
// Other search criteria
for ($i = 1 ; $i <= $si; $i++) {
	$sql .= " AND " . $sqlw[$i];
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1,$offset);
//print $sql;

$resql = $db->query($sql);
if ($resql)
{
	$var=True;
	$num = $db->num_rows($resql);
	$i = 0;

	// Title
	$bankcateg=new BankCateg($db);
	if (GETPOST("bid"))
	{
		$result=$bankcateg->fetch(GETPOST("bid"));
		print_barre_liste($langs->trans("BankTransactionForCategory",$bankcateg->label).' '.($socid?' '.$soc->nom:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	}
	else
	{
		print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	}

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'b.rowid','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DateOperationShort'),$_SERVER['PHP_SELF'],'b.dateo','',$param,'align="center"',$sortfield,$sortorder);
    print_liste_field_titre($langs->trans('Value'),$_SERVER['PHP_SELF'],'b.datev','',$param,'align="center"',$sortfield,$sortorder);
	print '<td class="liste_titre" align="center">'.$langs->trans("Type").'</td>';
    print '<td class="liste_titre">'.$langs->trans("Numero").'</td>';
	print '<td class="liste_titre">'.$langs->trans("Description").'</td>';
	print '<td class="liste_titre">'.$langs->trans("ThirdParty").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("Debit").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("Credit").'</td>';
	print '<td class="liste_titre" align="left"> &nbsp; '.$langs->trans("Account").'</td>';
	print "</tr>\n";

	print '<form method="post" action="search.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre">&nbsp;</td>';
    print '<td class="liste_titre" align="center">';
    $form->select_types_paiements(empty($_REQUEST["type"])?'':$_REQUEST["type"], 'type', '', 2, 0, 1, 8);
    print '</td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="req_nb" value="'.GETPOST("req_nb").'" size="2"></td>';
    print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="description" size="24" value="'.$description.'">';
	print '</td>';
    print '<td class="liste_titre"><input type="text" class="flat" name="thirdparty" value="'.GETPOST("thirdparty").'" size="14"></td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="debit" size="4" value="'.$debit.'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="credit" size="4" value="'.$credit.'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="hidden" name="action" value="search">';
	if (! empty($_REQUEST['bid'])) print '<input type="hidden" name="bid" value="'.$_REQUEST["bid"].'">';
	print '<input type="image" class="liste_titre" name="submit" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
	print '</td>';
	print '</tr>';

	// Loop on each record
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$printline=false;
		//Search Description
		if ($description) {
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthee on tente recherche de traduction
			if ($reg[1]) {
				if ($langs->transnoentities($reg[1])==$description) {
					$printline=true;
				}
			}elseif ($objp->label==$description) {$printline=true;}
		}else {$printline=true;}

		if ($printline) {
			$var=!$var;

			print "<tr $bc[$var]>";

			// Ref
			print '<td align="left" class="nowrap">';
			print "<a href=\"ligne.php?rowid=".$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$objp->rowid."</a> &nbsp; ";
			print '</td>';

			// Date ope
	        print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($objp->do),"day")."</td>\n";

	        // Date value
	        print '<td align="center" class="nowrap">'.dol_print_date($db->jdate($objp->dv),"day")."</td>\n";

	        // Payment type
	        print "<td align=\"center\">";
	        $labeltype=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$langs->getLabelFromKey($db,$objp->fk_type,'c_paiement','code','libelle');
	        if ($labeltype == 'SOLD') print '&nbsp;'; //$langs->trans("InitialBankBalance");
	        else print $labeltype;
	        print "</td>\n";

	        // Num
	        print '<td nowrap>'.($objp->num_chq?$objp->num_chq:"")."</td>\n";

	        // Description
			print "<td>";

			print "<a href=\"ligne.php?rowid=".$objp->rowid."&amp;account=".$objp->fk_account."\">";
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthee on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
			else print dol_trunc($objp->label,40);
			print "</a>&nbsp;";

			print '</td>';

			// Third party
			print "<td>";
			if ($objp->url_id)
			{
				$companystatic->id=$objp->url_id;
				$companystatic->nom=$objp->labelurl;
				print $companystatic->getNomUrl(1);
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';

			// Debit/Credit
			if ($objp->amount < 0)
			{
				print "<td align=\"right\">".price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
			}
			else
			{
				print "<td>&nbsp;</td><td align=\"right\">".price($objp->amount)."</td>\n";
			}

			// Bank account
			print '<td align="left" class="nowrap">';
			$bankaccountstatic->id=$objp->bankid;
			$bankaccountstatic->label=$objp->bankref;
			print $bankaccountstatic->getNomUrl(1);
			print "</td>\n";
			print "</tr>";
		}
		$i++;
	}

	print "</table>";

	$db->free($resql);
}
else
{
	dol_print_error($db);
}

// Si acc�s issu d'une recherche et rien de trouv�
if ($_POST["action"] == "search" && ! $num)
{
	print $langs->trans("NoRecordFound");
}


$db->close();

llxFooter();
?>
