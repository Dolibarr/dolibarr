<?php
/* Copyright (C) 2003-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/product/stats/facture.php
 *	\ingroup    product service facture
 *	\brief      Page des stats des factures clients pour un produit
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("companies");
$langs->load("bills");
$langs->load("products");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="f.datef";


/*
 * View
 */

$invoicestatic=new Facture($db);

$form = new Form($db);


if ($id > 0 || ! empty($ref))
{
	$product = new Product($db);
	$result = $product->fetch($id, $ref);

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	/*
	 *  En mode visu
	 */
	if ($result > 0)
	{
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'referers', $titre, 0, $picto);

		print '<table class="border" width="100%">';

		// Reference
		print '<tr>';
		print '<td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
		print $form->showrefnav($product,'ref','',1,'ref');
		print '</td>';
		print '</tr>';

		// Libelle
		print '<tr><td>'.$langs->trans("Label").'</td><td colspan="3">'.$product->libelle.'</td>';
		print '</tr>';

		// Status (to sell)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
		print $product->getLibStatut(2,0);
		print '</td></tr>';

		// Status (to buy)
		print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
		print $product->getLibStatut(2,1);
		print '</td></tr>';

		show_stats_for_company($product,$socid);

		print "</table>";

		print '</div>';


		$sql = "SELECT distinct s.nom, s.rowid as socid, s.code_client,";
		$sql.= " f.facnumber, f.total as total_ht,";
		$sql.= " f.datef, f.paye, f.fk_statut as statut, f.rowid as facid";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= ", ".MAIN_DB_PREFIX."facture as f";
		$sql.= ", ".MAIN_DB_PREFIX."facturedet as d";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND d.fk_facture = f.rowid";
		$sql.= " AND d.fk_product =".$product->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid) $sql.= " AND f.fk_soc = $socid";
		$sql.= " ORDER BY $sortfield $sortorder ";
		$sql.= $db->plimit($conf->liste_limit +1, $offset);

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);

			print_barre_liste($langs->trans("CustomersInvoices"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num,0,'');

			$i = 0;
			print "<table class=\"noborder\" width=\"100%\">";

			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"s.rowid","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("CustomerCode"),$_SERVER["PHP_SELF"],"s.code_client","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DateInvoice"),$_SERVER["PHP_SELF"],"f.datef","","&amp;id=".$product->id,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"f.total_ht","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"f.paye,f.fk_statut","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print "</tr>\n";

			if ($num > 0)
			{
				$var=True;
				while ($i < $num && $i < $conf->liste_limit)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;

					print "<tr $bc[$var]>";
					print '<td>';
					$invoicestatic->id=$objp->facid;
					$invoicestatic->ref=$objp->facnumber;
					print $invoicestatic->getNomUrl(1);
					print "</td>\n";
					print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,44).'</a></td>';
					print "<td>".$objp->code_client."</td>\n";
					print "<td align=\"center\">";
					print dol_print_date($db->jdate($objp->datef),'day')."</td>";
					print "<td align=\"right\">".price($objp->total_ht)."</td>\n";
					print '<td align="right">'.$invoicestatic->LibStatut($objp->paye,$objp->statut,5).'</td>';
					print "</tr>\n";
					$i++;
				}
			}
		}
		else
		{
			dol_print_error($db);
		}
		print "</table>";
		print '<br>';
		$db->free($result);
	}
}
else
{
	dol_print_error();
}


llxFooter();
$db->close();
?>