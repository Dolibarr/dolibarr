<?php
/* Copyright (C) 2017       Alexandre Spangaro  <aspangaro@zendsi.com>
 * Copyright (C) 2017       Laurent Destailleur <eldy@users.sourceforge.net>
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
 *  \file       htdocs/compta/bank/various_payment/index.php
 *  \ingroup    bank
 *  \brief      List of various payments
 */

require '../../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/paymentvarious.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';

$langs->loadLangs(array("compta","banks","bills","accountancy"));

// Security check
$socid = GETPOST("socid","int");
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'banque', '', '', '');

$optioncss = GETPOST('optioncss','alpha');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$search_ref = GETPOST('search_ref','int');
$search_user = GETPOST('search_user','alpha');
$search_label = GETPOST('search_label','alpha');
$search_amount_deb = GETPOST('search_amount_deb','alpha');
$search_amount_cred = GETPOST('search_amount_cred','alpha');
$search_account = GETPOST('search_account','int');
$search_date = dol_mktime(0, 0, 0, GETPOST('date_docmonth', 'int'), GETPOST('date_docday', 'int'), GETPOST('date_docyear', 'int'));
$search_accountancy_code = GETPOST("search_accountancy_code");

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if (empty($page) || $page == -1) { $page = 0; }	 // If $page is not defined, or '' or -1
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortfield) $sortfield="v.datep";
if (! $sortorder) $sortorder="DESC";

$filtre=GETPOST("filtre",'alpha');

if (! GETPOST('typeid'))
{
	$newfiltre=str_replace('filtre=','',$filtre);
	$filterarray=explode('-',$newfiltre);
	foreach($filterarray as $val)
	{
		$part=explode(':',$val);
		if ($part[0] == 'v.fk_typepayment') $typeid=$part[1];
	}
}
else
{
	$typeid=GETPOST('typeid');
}

if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
{
	$search_ref="";
	$search_label="";
	$search_amount_deb="";
	$search_amount_cred="";
	$search_account='';
	$typeid="";
	$search_date = '';
	$search_accountancy_code = '';
}

/*
 * View
 */

llxHeader();

$form = new Form($db);
$formaccounting = new FormAccounting($db);
$variousstatic = new PaymentVarious($db);
$accountstatic = new Account($db);

$sql = "SELECT v.rowid, v.sens, v.amount, v.label, v.datep as datep, v.datev as datev, v.fk_typepayment as type, v.num_payment, v.fk_bank, v.accountancy_code,";
$sql.= " ba.rowid as bid, ba.ref as bref, ba.number as bnumber, ba.account_number as bank_account_number, ba.fk_accountancy_journal as accountancy_journal, ba.label as blabel,";
$sql.= " pst.code as payment_code";
$sql.= " FROM ".MAIN_DB_PREFIX."payment_various as v";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as pst ON v.fk_typepayment = pst.id";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON v.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql.= " WHERE v.entity IN (".getEntity('payment_various').")";

// Search criteria
if ($search_ref)					$sql.=" AND v.rowid=".$search_ref;
if ($search_label)					$sql.=natural_search(array('v.label'), $search_label);
if ($search_amount_deb)				$sql.=natural_search("v.amount", $search_amount_deb, 1);
if ($search_amount_cred)			$sql.=natural_search("v.amount", $search_amount_cred, 1);
if ($search_account > 0)			$sql.=" AND b.fk_account=".$search_account;
if ($search_date)					$sql.=" AND v.datep=".$search_date;
if ($search_accountancy_code > 0)	$sql.=" AND v.accountancy_code=".$search_accountancy_code;
if ($typeid > 0) $sql .= " AND v.fk_typepayment=".$typeid;
if ($filtre) {
	$filtre=str_replace(":","=",$filtre);
	$sql .= " AND ".$filtre;
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
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
	if ($search_ref)			$param.='&search_ref='.urlencode($search_ref);
	if ($search_label)			$param.='&search_label='.urlencode($search_label);
	if ($typeid > 0)            $param.='&typeid='.urlencode($typeid);
	if ($search_amount_deb)     $param.='&search_amount_deb='.urlencode($search_amount_deb);
	if ($search_amount_cred)    $param.='&search_amount_cred='.urlencode($search_amount_cred);
	if ($search_account > 0)			$param.='&search_amount='.urlencode($search_account);
	//if ($search_date)					$param.='&search_date='.$search_date;
	if ($search_accountancy_code > 0)	$param.='&search_accountancy_code='.urlencode($search_accountancy_code);

	if ($optioncss != '') $param.='&amp;optioncss='.urlencode($optioncss);

	$newcardbutton='<a class="butAction" href="'.DOL_URL_ROOT.'/compta/bank/various_payment/card.php?action=create">'.$langs->trans('MenuNewVariousPayment').'</a>';

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';

	if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';

	print_barre_liste($langs->trans("VariousPayments"),$page,$_SERVER["PHP_SELF"],$param,$sortfield,$sortorder,'',$num, $totalnboflines, 'title_accountancy.png', 0, $newcardbutton, '', $limit);

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

	print '<tr class="liste_titre">';

	// Ref
	print '<td class="liste_titre" align="left">';
	print '<input class="flat" type="text" size="3" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
	print '</td>';

	// Label
	print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_label" value="'.dol_escape_htmltag($search_label).'"></td>';

	// Date
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->select_date($search_date, 'date_doc', 0, 0, 1);
	print '</div>';
	print '</td>';

	// Type
	print '<td class="liste_titre" align="left">';
	$form->select_types_paiements($typeid,'typeid','',0,1,1,16);
	print '</td>';

	// Account
	if (! empty($conf->banque->enabled))
	{
		print '<td class="liste_titre">';
		$form->select_comptes($search_account,'search_account',0,'',1);
		print '</td>';
	}

	// Accounting account
	if (! empty($conf->accounting->enabled))
	{
		print '<td class="liste_titre">';
		print '<div class="nowrap">';
		print $formaccounting->select_account($search_accountancy_code, 'search_accountancy_code', 1, array (), 1, 1, 'maxwidth200');
		print '</div>';
		print '</td>';
	}

	// Debit
	print '<td class="liste_titre" align="right"><input name="search_amount_deb" class="flat" type="text" size="8" value="'.$search_amount_deb.'"></td>';

	// Credit
	print '<td class="liste_titre" align="right"><input name="search_amount_cred" class="flat" type="text" size="8" value="'.$search_amount_cred.'"></td>';

	print '<td class="liste_titre" align="right">';
	$searchpicto=$form->showFilterAndCheckAddButtons(0);
	print $searchpicto;
	print '</td>';

	print "</tr>\n";


	print '<tr class="liste_titre">';
	print_liste_field_titre("Ref",$_SERVER["PHP_SELF"],"v.rowid","",$param,"",$sortfield,$sortorder);
	print_liste_field_titre("Label",$_SERVER["PHP_SELF"],"v.label","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre("DatePayment",$_SERVER["PHP_SELF"],"v.datep","",$param,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre("PaymentMode",$_SERVER["PHP_SELF"],"type","",$param,'align="left"',$sortfield,$sortorder);
	if (! empty($conf->banque->enabled))     print_liste_field_titre("BankAccount",$_SERVER["PHP_SELF"],"ba.label","",$param,"",$sortfield,$sortorder);
	if (! empty($conf->accounting->enabled)) print_liste_field_titre("AccountAccounting",$_SERVER["PHP_SELF"],"v.accountancy_code","",$param,'align="left"',$sortfield,$sortorder);
	print_liste_field_titre("Debit",$_SERVER["PHP_SELF"],"v.amount","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre("Credit",$_SERVER["PHP_SELF"],"v.amount","",$param,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre('',$_SERVER["PHP_SELF"],"",'','','',$sortfield,$sortorder,'maxwidthsearch ');
	print "</tr>\n";


	$totalarray=array();
	while ($i < min($num,$limit))
	{
		$obj = $db->fetch_object($result);

		print '<tr class="oddeven">';

		$variousstatic->id=$obj->rowid;
		$variousstatic->ref=$obj->rowid;

		// Ref
		print "<td>".$variousstatic->getNomUrl(1)."</td>\n";

		// Label payment
		print "<td>".dol_trunc($obj->label,40)."</td>\n";

		// Date payment
		print '<td class="center">'.dol_print_date($db->jdate($obj->datep),'day')."</td>\n";

		// Type
		print '<td>'.$langs->trans("PaymentTypeShort".$obj->payment_code).' '.$obj->num_payment.'</td>';

		// Account
		if (! empty($conf->banque->enabled))
		{
			print '<td>';
			if ($obj->bid > 0)
			{
				$accountstatic->id=$obj->bid;
				$accountstatic->ref=$obj->bref;
				$accountstatic->number=$obj->bnumber;

				if (! empty($conf->accounting->enabled)) {
					$accountstatic->account_number=$obj->bank_account_number;

					$accountingjournal = new AccountingJournal($db);
					$accountingjournal->fetch($obj->accountancy_journal);
					$accountstatic->accountancy_journal = $accountingjournal->getNomUrl(0,1,1,'',1);
				}

				$accountstatic->label=$obj->blabel;
				print $accountstatic->getNomUrl(1);
			}
			else print '&nbsp;';
			print '</td>';
		}

		// Accounting account
		if (! empty($conf->accounting->enabled)) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch('', $obj->accountancy_code, 1);

			print '<td>'.$accountingaccount->getNomUrl(0,1,1,'',1).'</td>';
		}

		// Debit
		print "<td align=\"right\">";
		if ($obj->sens == 0)
		{
			print price($obj->amount);
			$totalarray['totaldeb'] += $obj->amount;
		}
		print "</td>";

		// Credit
		print "<td align=\"right\">";
		if ($obj->sens == 1)
		{
			print price($obj->amount);
			$totalarray['totalcred'] += $obj->amount;
		}
		print "</td>";

		print "<td></td>";

		$i++;
	}

	$colspan=4;
	if (! empty($conf->banque->enabled)) $colspan++;
	if (! empty($conf->accounting->enabled)) $colspan++;

	print '<tr class="liste_total">';
	print '<td colspan="'.$colspan.'" class="liste_total">'.$langs->trans("Total").'</td>';
	print '<td class="liste_total" align="right">'.price($totalarray['totaldeb'])."</td>";
	print '<td class="liste_total" align="right">'.price($totalarray['totalcred'])."</td>";
	print '<td></td>';
	print '</tr>';

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
