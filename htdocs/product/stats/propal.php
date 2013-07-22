<?php
/* Copyright (C) 2004-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	    \file       htdocs/product/stats/propal.php
 *      \ingroup    product service propal
 *		\brief      Page des stats des propals pour un produit
 */


require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->load("products");
$langs->load("companies");

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productstatspropal'));

$mesg = '';

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (! $sortorder) $sortorder="DESC";
if (! $sortfield) $sortfield="p.datep";


/*
 * View
 */
$form = new Form($db);

if ($id > 0 || ! empty($ref))
{
	$product = new Product($db);
	$result = $product->fetch($id, $ref);
	
	$parameters=array('id'=>$id);
	$reshook=$hookmanager->executeHooks('doActions',$parameters,$product,$action);    // Note that $action and $object may have been modified by some hooks

	llxHeader("","",$langs->trans("CardProduct".$product->type));

	if ($result > 0)
	{
		$head=product_prepare_head($product, $user);
		$titre=$langs->trans("CardProduct".$product->type);
		$picto=($product->type==1?'service':'product');
		dol_fiche_head($head, 'referers', $titre,0,$picto);
		
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$product,$action);    // Note that $action and $object may have been modified by hook


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


		$sql = "SELECT DISTINCT s.nom, s.rowid as socid, p.rowid as propalid, p.ref, p.total_ht as amount,";
		$sql.= "p.datep, p.fk_statut as statut";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", sc.fk_soc, sc.fk_user ";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
		$sql.= ",".MAIN_DB_PREFIX."propal as p";
		$sql.= ", ".MAIN_DB_PREFIX."propaldet as d";
		if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		$sql.= " WHERE p.fk_soc = s.rowid";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND d.fk_propal = p.rowid";
		$sql.= " AND d.fk_product =".$product->id;
		if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
		if ($socid) $sql.= " AND p.fk_soc = ".$socid;
		$sql.= " ORDER BY $sortfield $sortorder ";
		$sql.= $db->plimit($conf->liste_limit +1, $offset);

		$result = $db->query($sql);
		if ($result)
		{
			$num = $db->num_rows($result);

			print_barre_liste($langs->trans("Proposals"),$page,$_SERVER["PHP_SELF"],"&amp;id=$product->id",$sortfield,$sortorder,'',$num,0,'');

			$i = 0;
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"p.rowid","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","&amp;id=".$product->id,'',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DatePropal"),$_SERVER["PHP_SELF"],"p.datep","","&amp;id=".$product->id,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("AmountHT"),$_SERVER["PHP_SELF"],"p.total","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("Status"),$_SERVER["PHP_SELF"],"p.fk_statut","","&amp;id=".$product->id,'align="right"',$sortfield,$sortorder);
			print "</tr>\n";

			$propalstatic=new Propal($db);

			if ($num > 0)
			{
				$var=True;
				while ($i < $num && $i < $conf->liste_limit)
				{
					$objp = $db->fetch_object($result);
					$var=!$var;

					print '<tr '.$bc[$var].'>';
					print '<td><a href="'.DOL_URL_ROOT.'/comm/propal.php?id='.$objp->propalid.'">'.img_object($langs->trans("ShowPropal"),"propal").' ';
					print $objp->ref;
					print '</a></td>'."\n";
					print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($objp->nom,44).'</a></td>';
					print '<td align="center">';
					print dol_print_date($db->jdate($objp->datep))."</td>";
					print '<td align="right">'.price($objp->amount).'</td>'."\n";
					print '<td align="right">'.$propalstatic->LibStatut($objp->statut,5).'</td>';
					print '</tr>'."\n";
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
