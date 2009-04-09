<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/compta/bank/releve.php
 *      \ingroup    banque
 *		\brief      Page d'affichage d'un releve
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");

$langs->load("companies");
$langs->load("banks");
$langs->load("bills");

if (!$user->rights->banque->lire)
accessforbidden();


if ($_GET["action"] == 'dvnext')
{
	$ac = new Account($db);
	$ac->datev_next($_GET["dvid"]);
}

if ($_GET["action"] == 'dvprev')
{
	$ac = new Account($db);
	$ac->datev_previous($_GET["dvid"]);
}


$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:$_POST["sortfield"];
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:$_POST["sortorder"];
$page=isset($_GET["page"])?$_GET["page"]:$_POST["page"];
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

$html = new Form($db);


// Load account
$acct = new Account($db);
if ($_GET["account"])
{
	$acct->fetch($_GET["account"]);
}
if ($_GET["ref"])
{
	$acct->fetch(0,$_GET["ref"]);
	$_GET["account"]=$acct->id;
}

if (! isset($_GET["num"]))
{
	/*
	 *	Vue liste tous releves confondus
	 */
	$sql = "SELECT distinct(b.num_releve) as numr";
	$sql.= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql.= " WHERE fk_account = ".$_GET["account"];
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
		dol_fiche_head($head,'statement',$langs->trans("FinancialAccount"),0);

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3">';
		print $html->showrefnav($acct,'ref','',1,'ref');
		print '</td></tr>';

		// Label
		print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
		print '<td colspan="3">'.$acct->label.'</td></tr>';

		print '</table>';

		print '<br>';



		print_barre_liste('', $page, $_SERVER["PHP_SELF"], "&amp;account=".$_GET["account"], $sortfield, $sortorder,'',$numrows);

		print '<table class="noborder" width="100%">';
		print "<tr class=\"liste_titre\">";
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
				print "<tr $bc[$var]><td><a href=\"releve.php?num=$objp->numr&amp;account=".$_GET["account"]."\">$objp->numr</a></td></tr>\n";
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
		// Recherche valeur pour num = num�ro relev� pr�c�dent
		$sql = "SELECT distinct(num_releve) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank";
		$sql.= " WHERE num_releve < '".$_GET["num"]."' AND fk_account = ".$_GET["account"];
		$sql.= " ORDER BY num_releve DESC";
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
		// Recherche valeur pour num = num�ro relev� pr�c�dent
		$sql = "SELECT distinct(num_releve) as num";
		$sql.= " FROM ".MAIN_DB_PREFIX."bank";
		$sql.= " WHERE num_releve > '".$_GET["num"]."' AND fk_account = ".$_GET["account"];
		$sql.= " ORDER BY num_releve ASC";
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
		$num=$_GET["num"];
		$found=true;
	}
	if (! $found) $num=$_GET["num"];

	$mesprevnext ="<a href=\"releve.php?rel=prev&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">".img_previous()."</a> &nbsp;";
	$mesprevnext.= $langs->trans("AccountStatement")." $num";
	$mesprevnext.=" &nbsp; <a href=\"releve.php?rel=next&amp;num=$num&amp;ve=$ve&amp;account=$acct->id\">".img_next()."</a>";
	print_fiche_titre($langs->trans("AccountStatement").' '.$num.', '.$langs->trans("BankAccount").' : '.$acct->getNomUrl(0),$mesprevnext);
	print '<br>';

	print "<form method=\"post\" action=\"releve.php\">";
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

	// Calcul du solde de depart du relev
	$sql = "SELECT sum(amount) as amount FROM ".MAIN_DB_PREFIX."bank";
	$sql.= " WHERE num_releve < '".$num."' AND fk_account = ".$acct->id;
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		$total = $obj->amount;
		$db->free($resql);
	}

	// Recherche les �critures pour le relev
	$sql = "SELECT b.rowid,".$db->pdate("b.dateo")." as do,".$db->pdate("b.datev")." as dv, b.amount, b.label, b.rappro, b.num_releve, b.num_chq, b.fk_type";
	$sql .= " FROM ".MAIN_DB_PREFIX."bank as b";
	$sql .= " WHERE num_releve='".$num."'";
	if (!isset($num))
	{
		$sql .= " or num_releve is null";
	}
	$sql .= " AND fk_account = ".$acct->id;
	$sql .= " ORDER BY datev ASC";
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
			print '<td nowrap="nowrap" align="center">'.dol_print_date($objp->do,"day").'</td>';

			// Date de valeur
			print '<td align="center" valign="center" nowrap="nowrap">';
			print '<a href="releve.php?action=dvprev&amp;num='.$num.'&amp;account='.$_GET["account"].'&amp;dvid='.$objp->rowid.'">';
			print img_previous().'</a> ';
			print dol_print_date($objp->dv,"day") .' ';
			print '<a href="releve.php?action=dvnext&amp;num='.$num.'&amp;account='.$_GET["account"].'&amp;dvid='.$objp->rowid.'">';
			print img_next().'</a>';
			print "</td>\n";

			// Num chq
			print '<td nowrap="nowrap">'.$objp->fk_type.' '.($objp->num_chq?$objp->num_chq:'').'</td>';

			// Libelle
			print '<td valign="center"><a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$objp->rowid.'&amp;account='.$acct->id.'">';
			$reg=array();
			eregi('\((.+)\)',$objp->label,$reg);	// Si texte entour� de parenth�e on tente recherche de traduction
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
					print '<a href="'.DOL_URL_ROOT.'/compta/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("Payment");
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='payment_supplier') {
					print '<a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("Payment");
					print '</a>';
					$newline=0;
				}
				elseif ($links[$key]['type']=='company') {
					print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowCustomer'),'company').' ';
					print dol_trunc($links[$key]['label'],24);
					print '</a>';
					$newline=0;
				}
				else if ($links[$key]['type']=='sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/sociales/charges.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowBill'),'bill').' ';
					print $langs->trans("SocialContribution");
					print '</a>';
					$newline=0;
				}
				else if ($links[$key]['type']=='payment_sc') {
					print '<a href="'.DOL_URL_ROOT.'/compta/sociales/xxx.php?id='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowPayment'),'payment').' ';
					print $langs->trans("SocialContributionPayment");
					print '</a>';
					$newline=0;
				}
				else if ($links[$key]['type']=='member') {
					print '<a href="'.DOL_URL_ROOT.'/adherents/fiche.php?rowid='.$links[$key]['url_id'].'">';
					print img_object($langs->trans('ShowMember'),'user').' ';
					print $links[$key]['label'];
					print '</a>';
					$newline=0;
				}
				else if ($links[$key]['type']=='banktransfert') {
					/*	print '<a href="'.DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$links[$key]['url_id'].'">';
					 print img_object($langs->trans('ShowTransaction'),'payment').' ';
					 print $langs->trans("TransactionOnTheOtherAccount");
					 print '</a>';
					 $newline=0;
					 */
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
				$sql = "SELECT label FROM ".MAIN_DB_PREFIX."bank_categ as ct, ".MAIN_DB_PREFIX."bank_class as cl";
				$sql.= " WHERE ct.rowid=cl.fk_categ AND cl.lineid=".$objp->rowid;
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

	// Ligne Total
	print "<tr><td align=\"right\" colspan=\"4\">".$langs->trans("Total")." :</td><td align=\"right\">".price($totald)."</td><td align=\"right\">".price($totalc)."</td><td>&nbsp;</td><td>&nbsp;</td></tr>";

	// Ligne Solde
	print "<tr><td align=\"right\" colspan=\"4\">&nbsp;</td><td align=\"right\" colspan=\"2\"><b>".$langs->trans("EndBankBalance")." :</b></td><td align=\"right\"><b>".price($total)."</b></td><td>&nbsp;</td></tr>\n";
	print "</table></form>\n";
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
