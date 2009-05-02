<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/compta/index.php
 *	\ingroup    compta
 *	\brief      Main page of accountancy area
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT.'/facture.class.php');
require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.facture.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/fourn/fournisseur.commande.class.php');
if ($conf->tax->enabled) require_once(DOL_DOCUMENT_ROOT.'/chargesociales.class.php');

// L'espace compta/tréso doit toujours etre actif car c'est un espace partagé
// par de nombreux modules (banque, facture, commande à facturer, etc...) indépendemment
// de l'utilisation de la compta ou non. C'est au sein de cet espace que chaque sous fonction
// est protégé par le droit qui va bien du module concerné.
//if (!$user->rights->compta->general->lire)
//  accessforbidden();

$langs->load("compta");
$langs->load("bills");
if ($conf->commande->enabled) $langs->load("orders");

// Security check
$socid='';
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}


/*
 * Actions
 */

if (isset($_GET["action"]) && $_GET["action"] == 'add_bookmark')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE fk_soc = ".$socid." AND fk_user=".$user->id;
	if (! $db->query($sql) )
	{
		dol_print_error($db);
	}
	$sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark (fk_soc, dateb, fk_user) VALUES (".$socid.", ".$db->idate(mktime()).",".$user->id.");";
	if (! $db->query($sql) )
	{
		dol_print_error($db);
	}
}

if (isset($_GET["action"]) && $_GET["action"] == 'del_bookmark')
{
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."bookmark WHERE rowid=".$_GET["bid"];
	$result = $db->query($sql);
}




/*
 * Affichage page
 *
 */

$now=gmmktime();

$facturestatic=new Facture($db);
$facturesupplierstatic=new FactureFournisseur($db);

$html = new Form($db);
$formfile = new FormFile($db);

llxHeader("",$langs->trans("AccountancyTreasuryArea"));

print_fiche_titre($langs->trans("AccountancyTreasuryArea"));

print '<table border="0" width="100%" class="notopnoleftnoright">';

print '<tr>';

if (($conf->facture->enabled && $user->rights->facture->lire) ||
    ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
{
	print '<td valign="top" width="30%" class="notopnoleft">';
}

/*
 * Find invoices
 */
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/compta/facture.php">';
	print '<table class="noborder" width="100%">';
	print "<tr class=\"liste_titre\">";
	print '<td colspan="3">'.$langs->trans("SearchACustomerInvoice").'</td></tr>';
	print "<tr $bc[0]><td>".$langs->trans("Ref").':</td><td><input type="text" name="sf_ref" class="flat" size="18"></td>';
	print '<td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print "<tr $bc[0]><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
	print '</tr>';
	print "</table></form><br>";
}

if ($conf->fournisseur->enabled && $user->rights->fournisseur->lire)
{
	print '<form method="post" action="'.DOL_URL_ROOT.'/fourn/facture/index.php">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchASupplierInvoice").'</td></tr>';
	print "<tr ".$bc[0].">";
	print "<td>".$langs->trans("Ref").':</td><td><input type="text" name="search_ref" class="flat" size="18"></td>';
	print '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
	//print "<tr ".$bc[0]."><td>".$langs->trans("Other").':</td><td><input type="text" name="sall" class="flat" size="18"></td>';
	print '</tr>';
	print "</table></form><br>";
}


/**
 * Draft customers invoices
 */
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	$sql  = "SELECT f.facnumber, f.rowid, f.total_ttc, f.type,";
	$sql.= " s.nom, s.rowid as socid";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	if ($socid)
	{
		$sql .= " AND f.fk_soc = $socid";
	}

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var = false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("CustomersDraftInvoices").($num?' ('.$num.')':'').'</td></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num && $i < 20)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td nowrap="nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td nowrap="nowrap">';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=1;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right" nowrap="nowrap">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/**
 * Draft suppliers invoices
 */
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	$sql  = "SELECT f.facnumber, f.rowid, f.total_ttc, f.type,";
	$sql.= " s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."facture_fourn as f, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.fk_statut = 0";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND f.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var = false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("SuppliersDraftInvoices").($num?' ('.$num.')':'').'</td></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			$tot_ttc = 0;
			while ($i < $num && $i < 20)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td nowrap>';
				$facturesupplierstatic->ref=$obj->facnumber;
				$facturesupplierstatic->id=$obj->rowid;
				$facturesupplierstatic->type=$obj->type;
				print $facturesupplierstatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td>';
				$companystatic->id=$obj->socid;
				$companystatic->nom=$obj->nom;
				$companystatic->client=1;
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '</tr>';
				$tot_ttc+=$obj->total_ttc;
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td align="left">'.$langs->trans("Total").'</td>';
			print '<td colspan="2" align="right">'.price($tot_ttc).'</td>';
			print '</tr>';
		}
		else
		{
			print '<tr colspan="3" '.$bc[$var].'><td>'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

if (($conf->facture->enabled && $user->rights->facture->lire) ||
    ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
{
	print '</td>';
	print '<td valign="top" width="70%" class="notopnoleftnoright">';
}
else
{
	print '<td valign="top" width="100%" class="notopnoleftnoright">';
}


// Last customers
if ($conf->societe->enabled && $user->rights->societe->lire)
{
	$langs->load("boxes");
	$max=3;

	$sql = "SELECT s.nom, s.rowid, ".$db->pdate("s.datec")." as dc";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.client = 1";
	$sql.= " AND s.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY s.datec DESC ";
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);

	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);

		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>'.$langs->trans("BoxTitleLastCustomers",min($max,$num)).'</td>';
		print '<td align="right">'.$langs->trans("Date").'</td>';
		print '</tr>';
		if ($num)
		{
			$var = True;
			$total_ttc = $totalam = $total = 0;

			$customerstatic=new Client($db);
			$var=true;
			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);
				$customerstatic->id=$objp->rowid;
				$customerstatic->nom=$objp->nom;
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td>'.$customerstatic->getNomUrl(1).'</td>';
				print '<td align="right">'.dol_print_date($objp->dc,'day').'</td>';
				print '</tr>';

				$i++;
			}

		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("None").'</td></tr>';
		}
		print '</table><br>';
	}
}


// Last suppliers
if ($conf->fournisseur->enabled && $user->rights->societe->lire)
{
	$langs->load("boxes");
	$max=3;

	$sql = "SELECT s.nom, s.rowid, ".$db->pdate("s.datec")." as dc";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.fournisseur = 1";
	$sql.= " AND s.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$user->societe_id) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND s.rowid = ".$socid;
	$sql.= " ORDER BY s.datec DESC";
	$sql.= $db->plimit($max, 0);

	$result = $db->query($sql);
	if ($result)
	{
		$var=false;
		$num = $db->num_rows($result);

		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td>'.$langs->trans("BoxTitleLastSuppliers",min($max,$num)).'</td>';
		print '<td align="right">'.$langs->trans("Date").'</td>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;

			$customerstatic=new Client($db);
			while ($i < $num && $i < $max)
			{
				$objp = $db->fetch_object($result);
				$customerstatic->id=$objp->rowid;
				$customerstatic->nom=$objp->nom;
				print '<tr '.$bc[$var].'>';
				print '<td>'.$customerstatic->getNomUrl(1).'</td>';
				print '<td align="right">'.dol_print_date($objp->dc,'day').'</td>';
				print '</tr>';
				$var=!$var;
				$i++;
			}

		}
		else
		{
			print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("None").'</td></tr>';
		}
		print '</table><br>';
	}
}


/**
 * Social contributions to pay
 */
if ($conf->tax->enabled && $user->rights->tax->charges->lire)
{
	if (!$socid)
	{
		$chargestatic=new ChargeSociales($db);

		$sql = "SELECT c.rowid, c.amount, c.date_ech, c.paye,";
		$sql.= " cc.libelle,";
		$sql.= " sum(pc.amount) as sumpayed";
		$sql.= " FROM (".MAIN_DB_PREFIX."chargesociales as c, ".MAIN_DB_PREFIX."c_chargesociales as cc)";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementcharge as pc ON c.rowid = pc.fk_charge";
		$sql.= " WHERE c.fk_type = cc.id";
		$sql.= " AND c.entity = ".$conf->entity;
		$sql.= " AND c.paye = 0";
		$sql.= " GROUP BY c.rowid, c.amount, c.date_ech, c.paye, cc.libelle";

		$resql = $db->query($sql);
		if ( $resql )
		{
			$var = false;
			$num = $db->num_rows($resql);

			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("ContributionsToPay").($num?' ('.$num.')':'').'</td>';
			print '<td align="center">'.$langs->trans("DateDue").'</td>';
			print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
			print '<td align="right">'.$langs->trans("Payed").'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			if ($num)
			{
				$i = 0;
				$tot_ttc=0;
				while ($i < $num)
				{
					$obj = $db->fetch_object($resql);
					print "<tr $bc[$var]>";
					$chargestatic->id=$obj->rowid;
					$chargestatic->lib=$obj->libelle;
					$chargestatic->paye=$obj->paye;
					print '<td>'.$chargestatic->getNomUrl(1).'</td>';
					print '<td align="center">'.dol_print_date($obj->date_ech,'day').'</td>';
					print '<td align="right">'.price($obj->amount).'</td>';
					print '<td align="right">'.price($obj->sumpayed).'</td>';
					print '<td align="center">'.$chargestatic->getLibStatut(3).'</td>';
					print '</tr>';
					$tot_ttc+=$obj->amount;
					$var = !$var;
					$i++;
				}

				print '<tr class="liste_total"><td align="left" colspan="2">'.$langs->trans("Total").'</td>';
				print '<td align="right">'.price($tot_ttc).'</td>';
				print '<td align="right"></td>';
				print '<td align="right">&nbsp</td>';
				print '</tr>';
			}
			else
			{
				print '<tr '.$bc[$var].'><td colspan="5">'.$langs->trans("None").'</td></tr>';
			}
			print "</table><br>";
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}
}

/*
 * Commandes clients à facturer
 */
if ($conf->facture->enabled && $conf->commande->enabled && $user->rights->commande->lire)
{
	$commandestatic=new Commande($db);
	$langs->load("orders");

	$sql = "SELECT sum(f.total) as tot_fht, sum(f.total_ttc) as tot_fttc,";
	$sql.= " s.nom, s.rowid as socid,";
	$sql.= " p.rowid, p.ref, p.facture, p.fk_statut, p.total_ht, p.total_ttc";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe AS s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ", ".MAIN_DB_PREFIX."commande AS p";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."co_fa AS co_fa ON co_fa.fk_commande = p.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."facture AS f ON co_fa.fk_facture = f.rowid";
	$sql.= " WHERE p.fk_soc = s.rowid";
	$sql.= " AND p.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid)	$sql.= " AND p.fk_soc = ".$socid;
	$sql.= " AND p.fk_statut = 3";
	$sql.= " AND p.facture = 0";
	$sql.= " GROUP BY p.rowid";

	$resql = $db->query($sql);

	if ( $resql )
	{
		$var=false;
		$num = $db->num_rows($resql);

		if ($num)
		{
			$i = 0;
			print '<table class="noborder" width="100%">';
			print "<tr class=\"liste_titre\">";
			print '<td colspan="2"><a href="'.DOL_URL_ROOT.'/compta/commande/liste.php?status=3&afacturer=1">'.$langs->trans("OrdersToBill").' ('.$num.')</a></td>';
			if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
			print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
			print '<td align="right">'.$langs->trans("ToBill").'</td>';
			print '<td align="center" width="16">&nbsp;</td>';
			print '</tr>';
			$tot_ht=$tot_ttc=$tot_tobill=0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap">';

				$commandestatic->id=$obj->rowid;
				$commandestatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="100" class="nobordernopadding" nowrap="nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';
				print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
				print '&nbsp;';
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','','','',1);
				print '</td></tr></table>';

				print '</td>';

				print '<td align="left"><a href="fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").'</a>&nbsp;';
				print '<a href="fiche.php?socid='.$obj->socid.'">'.dol_trunc($obj->nom,44).'</a></td>';
				if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($obj->total_ht).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->total_ttc-$obj->tot_fttc).'</td>';
				print '<td>'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,3).'</td>';
				print '</tr>';
				$tot_ht += $obj->total_ht;
				$tot_ttc += $obj->total_ttc;
				//print "x".$tot_ttc."z".$obj->tot_fttc;
				$tot_tobill += ($obj->total_ttc-$obj->tot_fttc);
				$i++;
				$var=!$var;
			}

			print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToBill").': '.price($tot_tobill).')</font> </td>';
			if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($tot_ht).'</td>';
			print '<td align="right">'.price($tot_ttc).'</td>';
			print '<td align="right">'.price($tot_tobill).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
			print '</table><br>';
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Factures clients impayées
 */
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	$facstatic=new Facture($db);

	$sql = "SELECT f.rowid, f.facnumber, f.fk_statut, f.type, f.total, f.total_ttc, ";
	$sql.= $db->pdate("f.date_lim_reglement")." as datelimite,";
	$sql.= " sum(pf.amount) as am,";
	$sql.= " s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture as f";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiement_facture as pf on f.rowid=pf.fk_facture";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = f.fk_soc AND f.paye = 0 AND f.fk_statut = 1";
	$sql.= " AND f.entity = ".$conf->entity;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	$sql.= " GROUP BY f.rowid, f.facnumber, f.fk_statut, f.total, f.total_ttc, s.nom, s.rowid";
	$sql.= " ORDER BY f.datef ASC, f.facnumber ASC";

	$resql = $db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);
		$i = 0;

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2"><a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">'.$langs->trans("BillsCustomersUnpayed",min($conf->liste_limit,$num)).' ('.$num.')</a></td>';
		if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("Received").'</td>';
		print '<td width="16">&nbsp;</td>';
		print '</tr>';
		if ($num)
		{
			$total_ttc = $totalam = $total = 0;
			while ($i < $num && $i < $conf->liste_limit)
			{
				$obj = $db->fetch_object($resql);

				print '<tr '.$bc[$var].'>';
				print '<td nowrap="nowrap">';

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="100" class="nobordernopadding" nowrap="nowrap">';
				$facturestatic->ref=$obj->facnumber;
				$facturestatic->id=$obj->rowid;
				$facturestatic->type=$obj->type;
				print $facturestatic->getNomUrl(1,'');
				print '</td>';
				print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
				if ($obj->datelimite < ($now - $conf->facture->client->warning_delay)) print img_warning($langs->trans("Late"));
				print '</td>';
				print '<td width="16" align="right" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->facnumber);
				$filedir=$conf->facture->dir_output . '/' . dol_sanitizeFileName($obj->facnumber);
				$urlsource=$_SERVER['PHP_SELF'].'?facid='.$obj->rowid;
				$formfile->show_documents('facture',$filename,$filedir,$urlsource,'','','','','',1);
				print '</td></tr></table>';

				print '</td>';
				print '<td align="left"><a href="fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCustomer"),"company").' '.dol_trunc($obj->nom,44).'</a></td>';
				if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($obj->total).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3,$obj->am).'</td>';
				print '</tr>';

				$total_ttc +=  $obj->total_ttc;
				$total += $obj->total;
				$totalam +=  $obj->am;
				$var=!$var;
				$i++;
			}

			print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToTake").': '.price($total_ttc-$totalam).')</font> </td>';
			if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($total).'</td>';
			print '<td align="right">'.price($total_ttc).'</td>';
			print '<td align="right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=5;
			if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}

/*
 * Factures fournisseurs impayées
 */
if ($conf->facture->enabled && $user->rights->facture->lire)
{
	$facstatic=new FactureFournisseur($db);

	$sql = "SELECT ff.rowid, ff.facnumber, ff.fk_statut, ff.fk_statut, ff.libelle, ff.total_ht, ff.total_ttc,";
	$sql.= " sum(pf.amount) as am,";
	$sql.= " s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture_fourn as ff";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf on ff.rowid=pf.fk_facturefourn";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = ff.fk_soc";
	$sql.= " AND s.entity = ".$conf->entity;
	$sql.= " AND ff.paye=0 AND ff.fk_statut = 1";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
	if ($socid) $sql.= " AND ff.fk_soc = ".$socid;
	$sql.= " GROUP BY ff.rowid, ff.facnumber, ff.fk_statut, ff.total, ff.total_ttc, s.nom, s.rowid";

	$resql=$db->query($sql);
	if ($resql)
	{
		$var=false;
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre"><td colspan="2"><a href="'.DOL_URL_ROOT.'/fourn/facture/impayees.php">'.$langs->trans("BillsSuppliersUnpayed",min($conf->liste_limit,$num)).' ('.$num.')</a></td>';
		if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.$langs->trans("AmountHT").'</td>';
		print '<td align="right">'.$langs->trans("AmountTTC").'</td>';
		print '<td align="right">'.$langs->trans("Payed").'</td>';
		print '<td width="16">&nbsp;</td>';
		print "</tr>\n";
		if ($num)
		{
			$i = 0;
			$total = $total_ttc = $totalam = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr '.$bc[$var].'><td>';
				$facstatic->ref=$obj->facnumber;
				$facstatic->id=$obj->rowid;
				print $facstatic->getNomUrl(1,'');
				print '</td>';
				print '<td><a href="fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowSupplier"),"company").' '.dol_trunc($obj->nom,44).'</a></td>';
				if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($obj->total_ht).'</td>';
				print '<td align="right">'.price($obj->total_ttc).'</td>';
				print '<td align="right">'.price($obj->am).'</td>';
				print '<td>'.$facstatic->LibStatut($obj->paye,$obj->fk_statut,3).'</td>';
				print '</tr>';
				$total += $obj->total_ht;
				$total_ttc +=  $obj->total_ttc;
				$totalam +=  $obj->am;
				$i++;
				$var = !$var;
			}

			print '<tr class="liste_total"><td colspan="2" align="right">'.$langs->trans("Total").' &nbsp; <font style="font-weight: normal">('.$langs->trans("RemainderToPay").': '.price($total_ttc-$totalam).')</font> </td>';
			if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) print '<td align="right">'.price($total).'</td>';
			print '<td align="right">'.price($total_ttc).'</td>';
			print '<td align="right">'.price($totalam).'</td>';
			print '<td>&nbsp;</td>';
			print '</tr>';
		}
		else
		{
			$colspan=5;
			if ($conf->global->MAIN_SHOW_HT_ON_SUMMARY) $colspan++;
			print '<tr '.$bc[$var].'><td colspan="'.$colspan.'">'.$langs->trans("NoInvoice").'</td></tr>';
		}
		print '</table><br>';
	}
	else
	{
		dol_print_error($db);
	}
}



// \todo Mettre ici recup des actions en rapport avec la compta
$resql = 0;
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("TasksToDo").'</td>';
	print "</tr>\n";
	$var = True;
	$i = 0;
	while ($i < $db->num_rows($resql))
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;

		print "<tr $bc[$var]><td>".dol_print_date($obj->da,"day")."</td>";
		print "<td><a href=\"action/fiche.php\">$obj->libelle $obj->label</a></td></tr>";
		$i++;
	}
	$db->free($resql);
	print "</table><br>";
}

print '</td></tr>';

print '</table>';

$db->close();


llxFooter('$Date$ - $Revision$');
?>
