<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2013 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	    \file       htdocs/compta/bank/releve.php
 *      \ingroup    banque
 *		\brief      Page to show a bank receipt report
 */

require('config.php');
dol_include_once('/bankimport/class/bankimport.class.php');
dol_include_once('/compta/facture/class/facture.class.php');
dol_include_once('/fourn/class/fournisseur.facture.class.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("companies");
$langs->load("bills");

$action=GETPOST('action', 'alpha');
$id=GETPOST('account','int');
$ref=GETPOST('ref','alpha');
$dvid=GETPOST('dvid','int');
$num=GETPOST('num','alpha');

// Security check
$fieldid = (! empty($ref)?$ref:$id);
$fieldname = isset($ref)?'ref':'rowid';
$newToken = function_exists('newToken')?newToken():$_SESSION['newtoken'];
$socidVersion = "socid";
if (DOL_VERSION < 13){
	$socidVersion = "societe_id";
}
if ($user->{$socidVersion}) $socid=$user->{$socidVersion};

$result=restrictedArea($user,'banque',$fieldid,'bank_account','','',$fieldname);

if ($user->hasRight('banque', 'consolidate') && $action == 'dvnext' && ! empty($dvid))
{
	$al = new AccountLine($db);
	$al->datev_next($dvid);
}

if ($user->hasRight('banque', 'consolidate') && $action == 'dvprev' && ! empty($dvid))
{
	$al = new AccountLine($db);
	$al->datev_previous($dvid);
}


$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1 || empty($page)) { $page = 0; }
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$offset = ((int) $limit * $page);
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * View
 */

llxHeader();

$form = new Form($db);
$societestatic=new Societe($db);
$chargestatic=new ChargeSociales($db);
$memberstatic=new Adherent($db);
$paymentstatic=new Paiement($db);
$paymentsupplierstatic=new PaiementFourn($db);
$paymentvatstatic=new TVA($db);
$bankstatic=new Account($db);
$banklinestatic=new AccountLine($db);


// Load account
$acct = new Account($db);
if ($id > 0 || ! empty($ref))
{
	$acct->fetch($id, $ref);
}

if (empty($num))
{
	/*
	 *	List view of all statements
	 */
	$sql = "SELECT DISTINCT(b.num_releve) as numr";
	$sql.= " FROM ".$db->prefix()."bank as b";
	$sql.= " WHERE b.fk_account = ".$acct->id;
	$sql.= " ORDER BY numr DESC";

	$sql.= $db->plimit($conf->liste_limit+1,$offset);

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$numrows = $db->num_rows($result);
		$i = 0;

		// Tabs
		$head=bank_prepare_head($acct);
		dol_fiche_head($head,'bankimport_statement',$langs->trans("FinancialAccount"),0,'account');

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3">';
		print $form->showrefnav($acct, 'ref', $linkback, 1, 'ref');
		print '</td></tr>';

		// Label
		print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
		print '<td colspan="3">'.$acct->label.'</td></tr>';

		print '</table>';

		print '<br>';


		print_barre_liste('', $page, $_SERVER["PHP_SELF"], "&amp;account=".$acct->id, $sortfield, $sortorder,'',$numrows);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("AccountStatement").'</td></tr>';

		//while ($i < min($numrows,$conf->liste_limit))   // removed limit until pagination is implemented
		while ($i < min($numrows,$conf->liste_limit))
		{
			$objp = $db->fetch_object($result);
			$var=!$var;
			if (! isset($objp->numr))
			{
				//
			}
			else
			{
				print '<tr '.$bc[$var].'><td><a href="'.dol_buildpath('/bankimport/releve.php?num='.$objp->numr.'&account='.$acct->id,2).'">'.$objp->numr.'</a></td></tr>'."\n";
			}
			$i++;
		}
		print "</table>\n";

		print "\n</div>\n";
	}
	else
	{
		dol_print_error($db);
	}
}
else
{
	/**
	 *      Display list of entries for a statement
	 */
	$ve=$_GET["ve"];

	$found=false;
	if ($_GET["rel"] == 'prev')
	{
		// Find previous statement number
		$sql = "SELECT DISTINCT(b.num_releve) as num";
		$sql.= " FROM ".$db->prefix()."bank as b";
		$sql.= " WHERE b.num_releve < '".$db->escape($num)."'";
		$sql.= " AND b.fk_account = ".$acct->id;
		$sql.= " ORDER BY b.num_releve DESC";

		dol_syslog("htdocs/compta/bank/releve.php sql=".$sql);
		$resql = $db->query($sql);
		if ($resql)
		{
			$numrows = $db->num_rows($resql);
			if ($numrows > 0)
			{
				$obj = $db->fetch_object($resql);
				$num = $obj->num;
				$found=true;
			}
		}
	}
	elseif ($_GET["rel"] == 'next')
	{
		// Find next statement number
		$sql = "SELECT DISTINCT(b.num_releve) as num";
		$sql.= " FROM ".$db->prefix()."bank as b";
		$sql.= " WHERE b.num_releve > '".$db->escape($num)."'";
		$sql.= " AND b.fk_account = ".$acct->id;
		$sql.= " ORDER BY b.num_releve ASC";

		dol_syslog("htdocs/compta/bank/releve.php sql=".$sql);
		$resql = $db->query($sql);
		if ($resql)
		{
			$numrows = $db->num_rows($resql);
			if ($numrows > 0)
			{
				$obj = $db->fetch_object($resql);
				$num = $obj->num;
				$found=true;
			}
		}
	}
	else {
		// We want this specific statement number
		$found=true;
	}

	$mesprevnext ="<a href=\"releve.php?rel=prev&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">".img_previous()."</a> &nbsp;";
	$mesprevnext.= $langs->trans("AccountStatement")." $num";
	$mesprevnext.=" &nbsp; <a href=\"releve.php?rel=next&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">".img_next()."</a>";
	print_fiche_titre($langs->trans("AccountStatement").' '.$num.', '.$langs->trans("BankAccount").' : '.$acct->getNomUrl(0),$mesprevnext);
	print '<br>';

	print "<form method=\"post\" action=\"releve.php\">";
	print '<input type="hidden" name="token" value="'.$newToken.'">';
	print "<input type=\"hidden\" name=\"action\" value=\"add\">";



	$PDOdb = new TPDOdb;

/*

	print '<td align="center">'.$langs->trans("DateOperationShort").'</td>';
	print '<td align="center">'.$langs->trans("DateValueShort").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Debit").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Credit").'</td>';
	print '<td align="right">'.$langs->trans("Balance").'</td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";
*/
	// Calculate opening balance of the statement
	$sql = "SELECT sum(b.amount) as amount";
	$sql.= " FROM ".$db->prefix()."bank as b";
	$sql.= " WHERE b.num_releve < '".$db->escape($num)."'";
	$sql.= " AND b.fk_account = ".$acct->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$total = $obj->amount;
		$db->free($resql);
	}


	$TEcriture = array();

	// Fetch entries for the statement
	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
	$sql.= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
	$sql.= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel, bih.rowid AS historyId, bih.line_imported_title, bih.line_imported_value";
	$sql.= " FROM ".$db->prefix()."bank_account as ba";
	$sql.= ", ".$db->prefix()."bank as b";
	$sql.= " LEFT JOIN ".$db->prefix()."bankimport_history bih ON (b.rowid = bih.fk_bank)";
	$sql.= " WHERE b.num_releve='".$db->escape($num)."'";
	if (!isset($num))	$sql.= " OR b.num_releve is null";
	$sql.= " AND b.fk_account = ".$acct->id;
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= $db->order("b.datev, b.datec", "ASC");  // We add date of creation to have correct order when everything is done the same day

	$result = $db->query($sql);
	if ($result)
	{
		while ($objp = $db->fetch_object($result))
		{
			$TEcriture[$objp->line_imported_title][] = $objp;
		}
	}
	$db->free($result);

	if (!empty($TEcriture))
	{
		$var=true;

		$solde_initial = $total;
		foreach ($TEcriture as $title_serialize => $TObjp)
		{
			printTableHeader($title_serialize, $solde_initial, $acct->id);
			$totald = $totalc = 0;

			foreach ($TObjp as $objp)
			{
				$var=!$var;
				$total = $total + $objp->amount;

				print "<tr ".$bc[$var].">";

				// History
				$bankImportHistory = new TBankImportHistory;
				$bankImportHistory->load($PDOdb, $objp->historyId);
				if ($bankImportHistory->getId() > 0)
				{
					foreach ($bankImportHistory->line_imported_value as $val)
					{
						print '<td class="line_imported_value">'.$val.'</td>';
					}
				}

				printStandardValues($db, $user, $langs, $acct, $objp, $num, $totald, $totalc, $paymentsupplierstatic, $paymentstatic, $paymentvatstatic, $bankstatic, $banklinestatic);

				print '</tr>';
			}

			$solde_initial = $total;
			printTableFooter($title_serialize, $totald, $totalc, $total);
		}
	}
	else
	{
		print '<div class="warning">'.$langs->trans('bankImportNoReccordFound').'</div>';
	}


	/*dol_syslog("sql=".$sql);
	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$numrows = $db->num_rows($result);
		$i = 0;

		// Opening balance line
		print "<tr><td colspan=\"4\"><a href=\"releve.php?num=$num&amp;ve=1&amp;rel=$rel&amp;account=".$acct->id."\">&nbsp;</a></td>";
		print "<td align=\"right\" colspan=\"2\"><b>".$langs->trans("InitialBankBalance")." :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";

		while ($i < $numrows)
		{
			$objp = $db->fetch_object($result);
			$total = $total + $objp->amount;


			$var=!$var;
			print "<tr ".$bc[$var].">";

			// Date operation
			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->do),"day").'</td>';

			// Value date
			print '<td align="center" valign="center" class="nowrap">';
			print '<a href="releve.php?action=dvprev&amp;num='.$num.'&amp;account='.$acct->id.'&amp;dvid='.$objp->rowid.'">';
			print img_previous().'</a> ';
			print dol_print_date($db->jdate($objp->dv),"day") .' ';
			print '<a href="releve.php?action=dvnext&amp;num='.$num.'&amp;account='.$acct->id.'&amp;dvid='.$objp->rowid.'">';
			print img_next().'</a>';
			print "</td>\n";

			// Type and num
            if ($objp->fk_type == 'SOLD') {
                $type_label='&nbsp;';
            } else {
                $type_label=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$objp->fk_type;
            }
			print '<td class="nowrap">'.$type_label.' '.($objp->num_chq?$objp->num_chq:'').'</td>';

			// Description
			print '<td valign="center"><a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// If text is in parentheses, attempt translation lookup
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
			else print $objp->label;
			print '</a>';

			/*
			 * Add links (company, payment, etc.)

			$newline=1;
			$links = $acct->get_url($objp->rowid);
			foreach($links as $key=>$val)
			{
				if (! $newline) print ' - ';
				else print '<br>';
				if ($links[$key]['type']=='payment')
				{
					$paymentstatic->id=$links[$key]['url_id'];
					$paymentstatic->ref=$langs->trans("Payment");
					print ' '.$paymentstatic->getNomUrl(1);
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_supplier')
				{
					$paymentsupplierstatic->id=$links[$key]['url_id'];
					$paymentsupplierstatic->ref=$langs->trans("Payment");;
					print ' '.$paymentsupplierstatic->getNomUrl(1);
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_sc')
				{
					print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$links[$key]['url_id'].'">';
					print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("SocialContributionPayment");
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_vat')
				{
					$paymentvatstatic->id=$links[$key]['url_id'];
					$paymentvatstatic->ref=$langs->trans("Payment");
					print ' '.$paymentvatstatic->getNomUrl(1);
				}
				elseif ($links[$key]['type']=='banktransfert') {
					// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
					if ($objp->amount > 0)
					{
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id=$banklinestatic->fk_account;
						$bankstatic->label=$banklinestatic->bank_account_label;
						print ' ('.$langs->trans("from").' ';
						print $bankstatic->getNomUrl(1,'transactions');
						print ' '.$langs->trans("toward").' ';
						$bankstatic->id=$objp->bankid;
						$bankstatic->label=$objp->bankref;
						print $bankstatic->getNomUrl(1,'');
						print ')';
					}
					else
					{
						$bankstatic->id=$objp->bankid;
						$bankstatic->label=$objp->bankref;
						print ' ('.$langs->trans("from").' ';
						print $bankstatic->getNomUrl(1,'');
						print ' '.$langs->trans("toward").' ';
						$banklinestatic->fetch($links[$key]['url_id']);
						$bankstatic->id=$banklinestatic->fk_account;
						$bankstatic->label=$banklinestatic->bank_account_label;
						print $bankstatic->getNomUrl(1,'transactions');
						print ')';
					}
				}
				elseif ($links[$key]['type']=='company') {
					print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowCustomer'),'company').' ';
					print dol_trunc($links[$key]['label'],24);
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='member') {
					print '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowMember'),'user').' ';
					print $links[$key]['label'];
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowBill'),'bill').' ';
					print $langs->trans("SocialContribution");
					print '</a>';
					$newline=0;
				}
				else {
					print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
					print $links[$key]['label'];
					print '</a>';
					$newline=0;
				}
			}

			// Categories
			if ($ve)
			{
				$sql = "SELECT label";
				$sql.= " FROM ".MAIN_DB_PREFIX."bank_categ as ct";
				$sql.= ", ".MAIN_DB_PREFIX."bank_class as cl";
				$sql.= " WHERE ct.rowid = cl.fk_categ";
				$sql.= " AND ct.entity = ".$conf->entity;
				$sql.= " AND cl.lineid = ".$objp->rowid;

				$resc = $db->query($sql);
				if ($resc)
				{
					$numc = $db->num_rows($resc);
					$ii = 0;
					if ($numc && ! $newline) print '<br>';
					while ($ii < $numc)
					{
						$objc = $db->fetch_object($resc);
						print "<br>-&nbsp;<i>$objc->label</i>";
						$ii++;
					}
				}
				else
				{
					dol_print_error($db);
				}
			}

			print "</td>";

			if ($objp->amount < 0)
			{
				$totald = $totald + abs($objp->amount);
				print '<td align="right" nowrap=\"nowrap\">'.price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
			}
			else
			{
				$totalc = $totalc + abs($objp->amount);
				print "<td>&nbsp;</td><td align=\"right\" nowrap=\"nowrap\">".price($objp->amount)."</td>\n";
			}

			print "<td align=\"right\" nowrap=\"nowrap\">".price($total)."</td>\n";

			if ($user->rights->banque->modifier || $user->rights->banque->consolidate)
			{
				print "<td align=\"center\"><a href=\"ligne.php?rowid=$objp->rowid&amp;account=".$acct->id."\">";
				print img_edit();
				print "</a></td>";
			}
			else
			{
				print "<td align=\"center\">&nbsp;</td>";
			}
			print "</tr>";

			$i++;
		}
		$db->free($result);
	}
*/
/*
	// Line Total
	print "\n".'<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("Total")." :</td><td align=\"right\">".price($totald)."</td><td align=\"right\">".price($totalc)."</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

	// Line Balance
	print "\n<tr><td align=\"right\" colspan=\"4\">&nbsp;</td><td align=\"right\" colspan=\"2\"><b>".$langs->trans("EndBankBalance")." :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
	print "</table></form>\n";*/

}

$db->close();

llxFooter();

function printTableHeader($title_serialize, $total, $acct_id)
{
	global $langs;

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';

	if (!empty($title_serialize))
	{
		$TTitle = unserialize($title_serialize);
		foreach ($TTitle as $title) print '<td>'.$title.'</td>';
	}

	print '<td align="center">'.$langs->trans("DateOperationShort").'</td>';
	print '<td align="center">'.$langs->trans("DateValueShort").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Debit").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Credit").'</td>';
	print '<td align="right">'.$langs->trans("Balance").'</td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";

	// Opening balance line
	print '	<tr>';
	if (!empty($TTitle)) print '<td colspan="'.count($TTitle).'"></td>';
	print '		<td colspan="4"><a href="releve.php?num=$num&amp;ve=1&amp;rel=$rel&amp;account='.$acct_id.'">&nbsp;</a></td>
				<td align="right" colspan="2"><b>'.$langs->trans("InitialBankBalance").' :</b></td><td align="right"><b>'.price($total).'</b></td><td>&nbsp;</td>
			</tr>';
}

function printTableFooter($title_serialize, $totald, $totalc, $total)
{
	global $langs;

	print '	<tr class="liste_total">';

	if (!empty($title_serialize))
	{
		$TTitle = unserialize($title_serialize);
		if (count($TTitle) > 0) print '<td colspan="'.count($TTitle).'"></td>';
	}

	// Line Total
	print '	<td align="right" colspan="4">'.$langs->trans("Total").' :</td>
			<td align="right" class="nowrap">'.price($totald).'</td>
			<td align="right" class="nowrap">'.price($totalc).'</td>
			<td>&nbsp;</td>
			<td>&nbsp;</td>';
	print '</tr>';

	// Line Balance
	print '	<tr>';
	if (!empty($TTitle)) print '<td colspan="'.count($TTitle).'"></td>';
	print '	<td align="right" colspan="4">&nbsp;</td>
			<td align="right" colspan="2"><b>'.$langs->trans("EndBankBalance").' :</b></td>
			<td align="right" class="nowrap" ><b>'.price($total).'</b></td>
			<td>&nbsp;</td>';
	print '</tr>';

	print '</table></form>';
}

function printStandardValues(&$db, &$user, &$langs, &$acct, &$objp, &$num, &$totald, &$totalc, &$paymentsupplierstatic, &$paymentstatic, &$paymentvatstatic, &$bankstatic, &$banklinestatic)
{
	// Date operation
	print '<td class="standard_td nowrap" align="center">'.dol_print_date($db->jdate($objp->do),"day").'</td>';

	// Value date
	print '<td align="center" valign="center" class="standard_td nowrap">';
	print '<a href="releve.php?action=dvprev&num='.$num.'&token='.newToken().'&account='.$acct->id.'&dvid='.$objp->rowid.'">';
	print img_previous().'</a> ';
	print dol_print_date($db->jdate($objp->dv),"day") .' ';
	print '<a href="releve.php?action=dvnext&num='.$num.'&token='.newToken().'&account='.$acct->id.'&dvid='.$objp->rowid.'">';
	print img_next().'</a>';
	print "</td>\n";

	// Type and num
    if ($objp->fk_type == 'SOLD') {
        $type_label='&nbsp;';
    } else {
        $type_label=($langs->trans("PaymentTypeShort".$objp->fk_type)!="PaymentTypeShort".$objp->fk_type)?$langs->trans("PaymentTypeShort".$objp->fk_type):$objp->fk_type;
    }
	print '<td class="standard_td nowrap">'.$type_label.' '.($objp->num_chq?$objp->num_chq:'').'</td>';

	// Description
	$bankLineUrl = DOL_URL_ROOT.'/compta/bank/line.php';
	if(version_compare(DOL_VERSION , '13.0.0', '<')){
		$bankLineUrl = DOL_URL_ROOT.'/compta/bank/ligne.php';
	}

	print '<td valign="center" class="standard_td nowrap"><a href="'.$bankLineUrl.'?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
	$reg=array();
	preg_match('/\((.+)\)/i',$objp->label,$reg);	// If text is in parentheses, attempt translation lookup
	if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
	else print $objp->label;
	print '</a>';

	/*
	 * Add links (company, payment, etc.)
	 */

	$newline=1;
	$links = $acct->get_url($objp->rowid);
	if(!empty($links)){
		foreach($links as $key=>$val)
		{
			if (! $newline) print ' - ';
			else print '<br>';
			if ($links[$key]['type']=='payment')
			{
				$paymentstatic->id=$links[$key]['url_id'];
				$paymentstatic->ref=$langs->trans("Payment");

				print '<br />'.$paymentstatic->getNomUrl(1);

				$sql = "SELECT pf.fk_facture
						FROM ".$db->prefix()."paiement_facture as pf
							LEFT JOIN ".$db->prefix()."paiement as p ON (p.rowid = pf.fk_paiement)
						WHERE p.rowid = ".$paymentstatic->id;
				$resql = $db->query($sql);
				$res = $db->fetch_object($resql);
				if ($res)
				{
					$facture = new Facture($db);
					$facture->fetch($res->fk_facture);
					//if ($facture->id > 0) print '<br />'.$facture->getNomUrl(1); Invoice is now displayed by the getListFacture() function below
				}

				$newline=0;
			}
			elseif ($links[$key]['type']=='payment_supplier')
			{
				$paymentsupplierstatic->id=$links[$key]['url_id'];
				$paymentsupplierstatic->ref=$langs->trans("Payment");

				print '<br />'.$paymentsupplierstatic->getNomUrl(1);

				$sql = "SELECT pf.fk_facturefourn
						FROM ".$db->prefix()."paiementfourn_facturefourn as pf
							LEFT JOIN " . $db->prefix() . "paiementfourn as p ON (p.rowid = pf.fk_paiementfourn)
						WHERE p.rowid = ".$paymentsupplierstatic->id;
				$resql = $db->query($sql);
				$res = $db->fetch_object($resql);
				if ($res)
				{
					$facture = new FactureFournisseur($db);
					$facture->fetch($res->fk_facturefourn);
					//if ($facture->id > 0) print '<br />'.$facture->getNomUrl(1); Invoice is now displayed by the getListFacture() function below
				}

				$newline=0;
			}
			elseif ($links[$key]['type']=='payment_sc')
			{
				print '<a href="'.DOL_URL_ROOT.'/compta/payment_sc/fiche.php?id='.$links[$key]['url_id'].'">';
				print ' '.img_object($langs->trans('ShowPayment'),'payment').' ';
				print $langs->trans("SocialContributionPayment");
				print '</a>';
				$newline=0;
			}
			elseif ($links[$key]['type']=='payment_vat')
			{
				$paymentvatstatic->id=$links[$key]['url_id'];
				$paymentvatstatic->ref=$langs->trans("Payment");
				print ' '.$paymentvatstatic->getNomUrl(1);
			}
			elseif ($links[$key]['type']=='banktransfert') {
				// Do not show link to transfer since there is no transfer card (avoid confusion). Can already be accessed from transaction detail.
				if ($objp->amount > 0)
				{
					$banklinestatic->fetch($links[$key]['url_id']);
					$bankstatic->id=$banklinestatic->fk_account;
					$bankstatic->label=$banklinestatic->bank_account_label;
					print ' ('.$langs->trans("from").' ';
					print $bankstatic->getNomUrl(1,'transactions');
					print ' '.$langs->trans("toward").' ';
					$bankstatic->id=$objp->bankid;
					$bankstatic->label=$objp->bankref;
					print $bankstatic->getNomUrl(1,'');
					print ')';
				}
				else
				{
					$bankstatic->id=$objp->bankid;
					$bankstatic->label=$objp->bankref;
					print ' ('.$langs->trans("from").' ';
					print $bankstatic->getNomUrl(1,'');
					print ' '.$langs->trans("toward").' ';
					$banklinestatic->fetch($links[$key]['url_id']);
					$bankstatic->id=$banklinestatic->fk_account;
					$bankstatic->label=$banklinestatic->bank_account_label;
					print $bankstatic->getNomUrl(1,'transactions');
					print ')';
				}
			}
			elseif ($links[$key]['type']=='company') {
				print '<a href="'.DOL_URL_ROOT.'/societe/soc.php?socid='.$links[$key]['url_id'].'">';
				print img_object($langs->trans('ShowCustomer'),'company').' ';
				print dol_trunc($links[$key]['label'],24);
				print '</a>';
				$newline=0;
			}
			elseif ($links[$key]['type']=='member') {
				print '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$links[$key]['url_id'].'">';
				print img_object($langs->trans('ShowMember'),'user').' ';
				print $links[$key]['label'];
				print '</a>';
				$newline=0;
			}
			elseif ($links[$key]['type']=='sc') {
				print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$links[$key]['url_id'].'">';
				print img_object($langs->trans('ShowBill'),'bill').' ';
				print $langs->trans("SocialContribution");
				print '</a>';
				$newline=0;
			}
			else {
				print '<a href="'.$links[$key]['url'].$links[$key]['url_id'].'">';
				print $links[$key]['label'];
				print '</a>';
				$newline=0;
			}
		}


		if($links[key($links)]['type']=='payment_supplier') $param = 'fourn';
		print '<br />'.getListFacture($links[key($links)]['url_id'], $param);

	}
	// With the new bankimport version, invoices from different third parties can be paid with a single payment, so we display all of them


	// Categories
	if ($ve)
	{
		$sql = "SELECT label";
		$sql.= " FROM ". $db->prefix() ."bank_categ as ct";
		if(version_compare(DOL_VERSION , '21.0.0', '<')) {
			$sql .= ", " . $db->prefix() . "bank_class as cl";
		}else {
			$sql .= ", " . $db->prefix() . "category_bankline as cl";
		}
		$sql.= " WHERE ct.rowid = cl.fk_categ";
		$sql.= " AND ct.entity = ".$conf->entity;
		$sql.= " AND cl.lineid = ".$objp->rowid;

		$resc = $db->query($sql);
		if ($resc)
		{
			$numc = $db->num_rows($resc);
			$ii = 0;
			if ($numc && ! $newline) print '<br>';
			while ($ii < $numc)
			{
				$objc = $db->fetch_object($resc);
				print "<br>-&nbsp;<i>$objc->label</i>";
				$ii++;
			}
		}
		else
		{
			dol_print_error($db);
		}
	}

	print "</td>";

	if ($objp->amount < 0)
	{
		$totald = $totald + abs($objp->amount);
		print '<td align="right" class="nowrap">'.price($objp->amount * -1)."</td><td>&nbsp;</td>\n";
	}
	else
	{
		$totalc = $totalc + abs($objp->amount);
		print "<td>&nbsp;</td><td align=\"right\" class='nowrap' >".price($objp->amount)."</td>\n";
	}

	print "<td align=\"right\" class='nowrap' >".price($total)."</td>\n";

	if ($user->hasRight('banque', 'modifier') || $user->hasRight('banque', 'consolidate'))
	{
		// Description
		$bankLineUrl = DOL_URL_ROOT.'/compta/bank/line.php';
		if(version_compare(DOL_VERSION , '11.0.0', '<')){
			$bankLineUrl = DOL_URL_ROOT.'/compta/bank/ligne.php';
		}

		print '<td align="center"><a href="'.$bankLineUrl.'?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
		print img_edit();
		print "</a></td>";
	}
	else
	{
		print "<td align=\"center\">&nbsp;</td>";
	}
}

/**
 * @param $id_reglement Payment ID
 * @param $fourn Empty string or "fourn" - for supplier payments, the table is llx_paiementfourn_facturefourn
 */
function getListFacture($id_reglement, $fourn='') {

	global $db;

	$sql = 'SELECT pf.fk_facture'.$fourn;
	// empty($fourn) = specific to customer invoices
	if(empty($fourn)) $sql.= ', rem.fk_facture  as fac_finale';
	$sql.= ' FROM ' . $db->prefix() . 'paiement'.$fourn.'_facture'.$fourn.' as pf';
	if(empty($fourn)) $sql.= ' LEFT JOIN ' . $db->prefix() . 'societe_remise_except as rem ON (pf.fk_facture = rem.fk_facture_source)';
	$sql.= ' WHERE fk_paiement'.$fourn.' = '.$id_reglement;
	//echo $sql;exit;
	$resql = $db->query($sql);

	$Tfact = array();

	$classname = 'Facture';
	if (!empty($fourn)) $classname = 'FactureFournisseur';

	while($res = $db->fetch_object($resql)) {

		$f = new $classname($db);
		if($f->fetch($res->{'fk_facture'.$fourn}) > 0) {

			// Display the final invoice if this is a down payment
			$suite = '';
			if(empty($fourn) && !empty($res->fac_finale)) {
				$fac_finale = new Facture($db);
				$fac_finale->fetch($res->fac_finale);
				$suite = ' / '.$fac_finale->getNomUrl(1);
			}

			$Tfact[] = $f->getNomURL(1).$suite;
		}
	}

	return implode('<br />', $Tfact);

}
