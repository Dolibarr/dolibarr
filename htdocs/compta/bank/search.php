<?php
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/compta/bank/search.php
 *	\ingroup    banque
 *	\brief      List of bank transactions
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/bankcateg.class.php");

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque');

$description=$_REQUEST["description"];
$debit=$_REQUEST["debit"];
$credit=$_REQUEST["credit"];
$type=$_REQUEST["type"];
$account=$_REQUEST["account"];

$param='';
if (! empty($_REQUEST["description"])) $param.='&description='.$_REQUEST["description"];
if (! empty($_REQUEST["type"])) $param.='&type='.$_REQUEST["type"];
if (! empty($_REQUEST["debit"])) $param.='&debit='.$_REQUEST["debit"];
if (! empty($_REQUEST["credit"])) $param.='&credit='.$_REQUEST["credit"];
if (! empty($_REQUEST["account"])) $param.='&account='.$_REQUEST["account"];
if (! empty($_REQUEST["bid"]))  $param.='&bid='.$_REQUEST["bid"];

$page     =$_GET['page'];
$sortorder=$_GET['sortorder'];
$sortfield=$_GET['sortfield'];
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='b.dateo';


/*
 * View
 */

$companystatic=new Societe($db);
$bankaccountstatic=new Account($db);

llxHeader();

$html = new Form($db);

if ($vline) $viewline = $vline;
else $viewline = 50;

$sql = "SELECT b.rowid, b.dateo as do, b.amount, b.label, b.rappro, b.num_releve, b.num_chq,";
$sql.= " b.fk_account, b.fk_type,";
$sql.= " ba.rowid as bankid, ba.ref as bankref,";
$sql.= " bu.label as labelurl, bu.url_id";
$sql.= " FROM (";
if (! empty($_REQUEST["bid"])) $sql.= MAIN_DB_PREFIX."bank_class as l, ";
$sql.= MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."bank_account as ba)";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_url as bu on (bu.fk_bank = b.rowid AND type = 'company')";
$sql.= " WHERE b.fk_account = ba.rowid";
$sql.= " AND ba.entity = ".$conf->entity;
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
// Search criteria description
if ($description) {
	$si++;
	$sqlw[$si] .= " b.label like '%" . $description . "%'";
}
// Other search criteria
for ($i = 1 ; $i <= $si; $i++) {
	$sql .= " AND " . $sqlw[$i];
}
$sql.= $db->order($sortfield,$sortorder);
$sql.= $db->plimit($limit+1,$offset);

$resql = $db->query($sql);
if ($resql)
{
	$var=True;
	$num = $db->num_rows($resql);
	$i = 0;

	// Title
	$bankcateg=new BankCateg($db);
	if (! empty($_REQUEST["bid"]))
	{
		$result=$bankcateg->fetch($_REQUEST["bid"]);
		print_barre_liste($langs->trans("BankTransactionForCategory",$bankcateg->label).' '.($socid?' '.$soc->nom:''), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	}
	else
	{
		print_barre_liste($langs->trans("BankTransactions"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num);
	}

	print '<table class="liste" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans('Ref'),$_SERVER['PHP_SELF'],'b.rowid','',$param,'',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans('DateOperationShort'),$_SERVER['PHP_SELF'],'b.dateo','',$param,'align="left"',$sortfield,$sortorder);
	print '<td class="liste_titre">'.$langs->trans("Description").'</td>';
	print '<td class="liste_titre">'.$langs->trans("ThirdParty").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("Debit").'</td>';
	print '<td class="liste_titre" align="right">'.$langs->trans("Credit").'</td>';
	print '<td class="liste_titre" align="center">'.$langs->trans("Type").'</td>';
	print '<td class="liste_titre" align="left">'.$langs->trans("Account").'</td>';
	print "</tr>\n";

	print '<form method="post" action="search.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<tr class="liste_titre">';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre">';
	print '<input type="text" class="flat" name="description" size="28" value="'.$description.'">';
	print '</td>';
	print '<td class="liste_titre">&nbsp;</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="debit" size="6" value="'.$debit.'">';
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="text" class="flat" name="credit" size="6" value="'.$credit.'">';
	print '</td>';
	print '<td class="liste_titre" align="center">';
	$html->select_types_paiements(empty($_REQUEST["type"])?'':$_REQUEST["type"], 'type', '', 2, 0, 1);
	print '</td>';
	print '<td class="liste_titre" align="right">';
	print '<input type="hidden" name="action" value="search">';
	if (! empty($_REQUEST['bid'])) print '<input type="hidden" name="bid" value="'.$_REQUEST["bid"].'">';
	print '<input type="image" class="liste_titre" name="submit" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '</td>';
	print '</tr>';

	// Loop on each record
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);

		$var=!$var;

		print "<tr $bc[$var]>";

		// Ref
		print '<td align="left" nowrap="nowrap">';
		print "<a href=\"ligne.php?rowid=".$objp->rowid.'">'.img_object($langs->trans("ShowPayment"),"payment").' '.$objp->rowid."</a> &nbsp; ";
		print '</td>';

		// Date
		print '<td align="left" nowrap="nowrap">'.dol_print_date($db->jdate($objp->do),"day")." &nbsp; </td>\n";

		// Description
		print "<td>";

		/* This bloc is same than in page compta/bank/account.php

		 		// Show generic description
				if (preg_match('/^\((.*)\)$/i',$objp->label,$reg))
				{
					// Generic description because between (). We show it after translating.
					print $langs->trans($reg[1]);
				}
				else
				{
					print dol_trunc($objp->label,60);
				}
				// Add links after description
				$links = $acct->get_url($objp->rowid);
				foreach($links as $key=>$val)
				{
					if ($links[$key]['type']=='payment') {
						$paymentstatic->id=$links[$key]['url_id'];
						print ' '.$paymentstatic->getNomUrl(2);
					}
					else if ($links[$key]['type']=='payment_supplier') {
						$paymentsupplierstatic->id=$links[$key]['url_id'];
						$paymentsupplierstatic->ref=$links[$key]['url_id'];
						print ' '.$paymentsupplierstatic->getNomUrl(2);
					}
					else if ($links[$key]['type']=='company') {
					}
					else if ($links[$key]['type']=='sc') {	// This is waiting for card to link to payment_sc
						$chargestatic->id=$links[$key]['url_id'];
						$chargestatic->ref=$links[$key]['url_id'];
						$chargestatic->lib=$langs->trans("SocialContribution");
						print ' '.$chargestatic->getNomUrl(2);
					}
					else if ($links[$key]['type']=='payment_sc')
					{
						//print ' - ';
						print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$links[$key]['url_id'].'">';
						print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
						//print $langs->trans("SocialContributionPayment");
						print '</a>';

					}
					else if ($links[$key]['type']=='payment_vat')
					{
						$paymentvatstatic->id=$links[$key]['url_id'];
						$paymentvatstatic->ref=$links[$key]['url_id'];
						print ' '.$paymentvatstatic->getNomUrl(2);
					}
					else if ($links[$key]['type']=='banktransfert') {
						// Do not show this link (avoid confusion). Can already be accessed from transaction detail
					}
					else if ($links[$key]['type']=='member') {
					}
					else {
						//print ' - ';
						print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
						if (preg_match('/^\((.*)\)$/i',$links[$key]['label'],$reg))
						{
							// Label generique car entre parentheses. On l'affiche en le traduisant
							if ($reg[1]=='paiement') $reg[1]='Payment';
							print $langs->trans($reg[1]);
						}
						else
						{
							print $links[$key]['label'];
						}
						print '</a>';
					}
				}
		*/

		print "<a href=\"ligne.php?rowid=$objp->rowid&amp;account=$objp->fk_account\">";
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

		// Payment type
		print "<td align=\"center\">".$langs->getLabelFromKey($db,$objp->fk_type,'c_paiement','code','libelle')."</td>\n";

		// Bank account
		print '<td align="left" nowrap="nowrap">';
		$bankaccountstatic->id=$objp->bankid;
		$bankaccountstatic->label=$objp->bankref;
		print $bankaccountstatic->getNomUrl(1);
		print "</td>\n";
		print "</tr>";

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

llxFooter('$Date$ - $Revision$');
?>
