<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 */
 
/**
   \file       htdocs/compta/paiement/liste.php
    \ingroup    compta
     \brief      Page liste des paiements des factures clients
      \version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/paiement.class.php');
require_once(DOL_DOCUMENT_ROOT.'/compta/bank/account.class.php');

$langs->load("bills");

$user->getrights("facture");

// Sécurité accés client
if (! $user->rights->facture->lire)
  accessforbidden();

$socid=0;
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$paymentstatic=new Paiement($db);
$accountstatic=new Account($db);
$companystatic=new Societe($db);


/*
 * Affichage
 */
llxHeader('',$langs->trans("ListPayment"));

$page=$_GET["page"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
 
$limit = $conf->liste_limit;
$offset = $limit * $page ;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.rowid";
  
$sql = "SELECT DISTINCT p.rowid,".$db->pdate("p.datep")." as dp, p.amount,";
$sql.= " p.statut, p.num_paiement,";
//$sql.= " c.libelle as paiement_type,";
$sql.= " c.code as paiement_code,"; 
$sql.= " ba.rowid as bid, ba.label,";
$sql.= " s.rowid as socid, s.nom";
$sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank as b ON p.fk_bank = b.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."bank_account as ba ON b.fk_account = ba.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture as f ON pf.fk_facture = f.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
if (!$user->rights->commercial->client->voir && !$socid)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
}
$sql.= " WHERE p.fk_paiement = c.id";
if (!$user->rights->commercial->client->voir && !$socid)
{
	$sql.= " AND sc.fk_user = " .$user->id;
}
if ($socid)
{
  $sql.= " AND f.fk_soc = ".$socid;
}
if ($_GET["search_montant"])
{
  $sql .=" AND p.amount=".price2num($_GET["search_montant"]);
}

if ($_GET["orphelins"])     // Option qui ne sert qu'au debogage
{
  // Paiements liés à aucune facture (pour aide au diagnostic)
  $sql = "SELECT p.rowid,".$db->pdate("p.datep")." as dp, p.amount,";
  $sql.= " p.statut, p.num_paiement,";
  //$sql.= " c.libelle as paiement_type";
  $sql.= " c.code as paiement_code,";
  $sql.= " s.rowid as socid, s.nom";
  $sql.= " FROM ".MAIN_DB_PREFIX."paiement as p,";
  $sql.= " ".MAIN_DB_PREFIX."c_paiement as c";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf ON p.rowid = pf.fk_paiement";
  $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON f.fk_soc = s.rowid";
  $sql.= " WHERE p.fk_paiement = c.id AND pf.rowid IS NULL";
}
$sql .= " ORDER BY $sortfield $sortorder";
$sql .= $db->plimit( $limit+1 ,$offset);
//print "$sql";

$resql = $db->query($sql);

if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	$paramlist=($_GET["orphelins"]?"&orphelins=1":"");
	print_barre_liste($langs->trans("ReceivedCustomersPayments"), $page, "liste.php",$paramlist,$sortfield,$sortorder,'',$num);

	print '<form method="get" action="liste.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print_liste_field_titre($langs->trans("Ref"),"liste.php","p.rowid","",$paramlist,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Date"),"liste.php","dp","",$paramlist,'align="center"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("ThirdParty"),"liste.php","c.libelle","",$paramlist,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Type"),"liste.php","c.libelle","",$paramlist,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Account"),"liste.php","ba.label","",$paramlist,"",$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("AmountTTC"),"liste.php","p.amount","",$paramlist,'align="right"',$sortfield,$sortorder);
	print_liste_field_titre($langs->trans("Status"),"liste.php","p.statut","",$paramlist,'align="right"',$sortfield,$sortorder);
	print "</tr>\n";

	// Lignes des champs de filtre
	print '<tr class="liste_titre">';
	print '<td colspan="5">&nbsp;</td>';
	print '<td align="right">';
	print '<input class="fat" type="text" size="6" name="search_montant" value="'.$_GET["search_montant"].'">';
	print '</td><td align="right">';
	print '<input type="image" class="liste_titre" name="button_search" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
	print '</td>';
	print "</tr>\n";

	$var=true;
	while ($i < min($num,$limit))
	{
		$objp = $db->fetch_object($resql);
		$var=!$var;
		print "<tr $bc[$var]>";

		print '<td width="40">';
		$paymentstatic->rowid=$objp->rowid;
		print $paymentstatic->getNomUrl(1);
		print '</td>';

		print '<td align="center">'.dolibarr_print_date($objp->dp,'day').'</td>';

		// Company
		print '<td>';
		if ($objp->socid)
		{
			$companystatic->id=$objp->socid;
			$companystatic->nom=$objp->nom;
			print $companystatic->getNomUrl(1,'',24);
		}
		else print '&nbsp;';
		print '</td>';

		print '<td>'.$langs->trans("PaymentTypeShort".$objp->paiement_code).' '.$objp->num_paiement.'</td>';
		print '<td>';
		if ($objp->bid)
		{
			$accountstatic->id=$objp->bid;
			$accountstatic->label=$objp->label;
			print $accountstatic->getNomUrl(1);
		}
		else print '&nbsp;';
		print '</td>';
		print '<td align="right">'.price($objp->amount).'</td>';
		print '<td align="right">';
		if ($objp->statut == 0) print '<a href="fiche.php?id='.$objp->rowid.'&amp;action=valide">';
		print $paymentstatic->LibStatut($objp->statut,5);
		if ($objp->statut == 0) print '</a>';
		print '</td>';

		print '</tr>';
		
		$i++;
	}
	print "</table>\n";
	print "</form>\n";
}
else
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
