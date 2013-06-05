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

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("companies");
$langs->load("bills");

$action=GETPOST('action', 'alpha');
$id=GETPOST('account');
$ref=GETPOST('ref');
$dvid=GETPOST('dvid');
$num=GETPOST('num');

// Security check
$fieldid = (! empty($ref)?$ref:$id);
$fieldname = isset($ref)?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$fieldid,'bank_account','','',$fieldname);

if ($user->rights->banque->consolidate && $action == 'dvnext' && ! empty($dvid))
{
	$al = new AccountLine($db);
	$al->datev_next($dvid);
}

if ($user->rights->banque->consolidate && $action == 'dvprev' && ! empty($dvid))
{
	$al = new AccountLine($db);
	$al->datev_previous($dvid);
}


$sortfield = GETPOST('sortfield', 'alpha');
$sortorder = GETPOST('sortorder', 'alpha');
$page = GETPOST('page', 'int');
if ($page == -1) { $page = 0; }
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="s.nom";

$offset = $conf->liste_limit * $page;
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
	 *	Vue liste tous releves confondus
	 */
	$sql = "SELECT DISTINCT(b.num_releve) as numr";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.fk_account = ".$acct->id;
	$sql.= " ORDER BY numr DESC";

	$sql.= $db->plimit($conf->liste_limit+1,$offset);

	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$numrows = $db->num_rows($result);
		$i = 0;

		// Onglets
		$head=bank_prepare_head($acct);
		dol_fiche_head($head,'statement',$langs->trans("FinancialAccount"),0,'account');

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

		//while ($i < min($numrows,$conf->liste_limit))   // retrait de la limite tant qu'il n'y a pas de pagination
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
				print '<tr '.$bc[$var].'><td><a href="releve.php?num='.$objp->numr.'&amp;account='.$acct->id.'">'.$objp->numr.'</a></td></tr>'."\n";
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
	 *      Affiche liste ecritures d'un releve
	 */
	$ve=$_GET["ve"];

	$found=false;
	if ($_GET["rel"] == 'prev')
	{
		// Recherche valeur pour num = numero releve precedent
		$sql = "SELECT DISTINCT(b.num_releve) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
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
		// Recherche valeur pour num = numero releve precedent
		$sql = "SELECT DISTINCT(b.num_releve) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
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
		// On veut le releve num
		$found=true;
	}

	$mesprevnext ="<a href=\"releve.php?rel=prev&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">".img_previous()."</a> &nbsp;";
	$mesprevnext.= $langs->trans("AccountStatement")." $num";
	$mesprevnext.=" &nbsp; <a href=\"releve.php?rel=next&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">".img_next()."</a>";
	print_fiche_titre($langs->trans("AccountStatement").' '.$num.', '.$langs->trans("BankAccount").' : '.$acct->getNomUrl(0),$mesprevnext);
	print '<br>';

	print "<form method=\"post\" action=\"releve.php\">";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print "<input type=\"hidden\" name=\"action\" value=\"add\">";

	print '<table class="border" width="100%">';
	print '<tr class="liste_titre">';
	print '<td align="center">'.$langs->trans("DateOperationShort").'</td>';
	print '<td align="center">'.$langs->trans("DateValueShort").'</td>';
	print '<td>'.$langs->trans("Type").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Debit").'</td>';
	print '<td align="right" width="60">'.$langs->trans("Credit").'</td>';
	print '<td align="right">'.$langs->trans("Balance").'</td>';
	print '<td>&nbsp;</td>';
	print "</tr>\n";

	// Calcul du solde de depart du releve
	$sql = "SELECT sum(b.amount) as amount";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.num_releve < '".$db->escape($num)."'";
	$sql.= " AND b.fk_account = ".$acct->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$total = $obj->amount;
		$db->free($resql);
	}

	// Recherche les ecritures pour le releve
	$sql = "SELECT b.rowid, b.dateo as do, b.datev as dv,";
	$sql.= " b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type,";
	$sql.= " ba.rowid as bankid, ba.ref as bankref, ba.label as banklabel";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank_account as ba";
	$sql.= ", ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE b.num_releve='".$db->escape($num)."'";
	if (!isset($num))	$sql.= " OR b.num_releve is null";
	$sql.= " AND b.fk_account = ".$acct->id;
	$sql.= " AND b.fk_account = ba.rowid";
	$sql.= $db->order("b.datev, b.datec", "ASC");  // We add date of creation to have correct order when everything is done the same day

	dol_syslog("sql=".$sql);
	$result = $db->query($sql);
	if ($result)
	{
		$var=True;
		$numrows = $db->num_rows($result);
		$i = 0;

		// Ligne Solde debut releve
		print "<tr><td colspan=\"4\"><a href=\"releve.php?num=$num&amp;ve=1&amp;rel=$rel&amp;account=".$acct->id."\">&nbsp;</a></td>";
		print "<td align=\"right\" colspan=\"2\"><b>".$langs->trans("InitialBankBalance")." :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";

		while ($i < $numrows)
		{
			$objp = $db->fetch_object($result);
			$total = $total + $objp->amount;

			$var=!$var;
			print "<tr $bc[$var]>";

			// Date operation
			print '<td class="nowrap" align="center">'.dol_print_date($db->jdate($objp->do),"day").'</td>';

			// Date de valeur
			print '<td align="center" valign="center" class="nowrap">';
			print '<a href="releve.php?action=dvprev&amp;num='.$num.'&amp;account='.$acct->id.'&amp;dvid='.$objp->rowid.'">';
			print img_previous().'</a> ';
			print dol_print_date($db->jdate($objp->dv),"day") .' ';
			print '<a href="releve.php?action=dvnext&amp;num='.$num.'&amp;account='.$acct->id.'&amp;dvid='.$objp->rowid.'">';
			print img_next().'</a>';
			print "</td>\n";

			// Num cheque
			print '<td class="nowrap">'.$objp->fk_type.' '.($objp->num_chq?$objp->num_chq:'').'</td>';

			// Libelle
			print '<td valign="center"><a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
			$reg=array();
			preg_match('/\((.+)\)/i',$objp->label,$reg);	// Si texte entoure de parenthese on tente recherche de traduction
			if ($reg[1] && $langs->trans($reg[1])!=$reg[1]) print $langs->trans($reg[1]);
			else print $objp->label;
			print '</a>';

			/*
			 * Ajout les liens (societe, company...)
			 */
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
					print ' '.$paymentvatstatic->getNomUrl(2);
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

	// Line Total
	print "\n".'<tr class="liste_total"><td align="right" colspan="4">'.$langs->trans("Total")." :</td><td align=\"right\">".price($totald)."</td><td align=\"right\">".price($totalc)."</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

	// Line Balance
	print "\n<tr><td align=\"right\" colspan=\"4\">&nbsp;</td><td align=\"right\" colspan=\"2\"><b>".$langs->trans("EndBankBalance")." :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
	print "</table></form>\n";
}

$db->close();

llxFooter();
?>
