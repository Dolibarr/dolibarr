<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/lib/product.lib.php
 *	\brief      Ensemble de fonctions de base pour le module produit et service
 * 	\ingroup	product
 *	\version    $Id$
 * 
 * 	Ensemble de fonctions de base de dolibarr sous forme d'include
 */

function product_prepare_head($product, $user)
{
	global $langs, $conf;
	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Price");
	$head[$h][2] = 'price';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$product->id;
	$head[$h][1] = $langs->trans("Photos");
	$head[$h][2] = 'photos';
	$h++;

	// Show category tab
	if ($conf->categorie->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT."/categories/categorie.php?id=".$product->id;
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}

	// Show barcode tab
	if ($conf->global->MAIN_MODULE_BARCODE)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/barcode.php?id=".$product->id;
		$head[$h][1] = $langs->trans("BarCode");
		$head[$h][2] = 'barcode';
		$h++;
	}

	// Multilangs
	if($conf->global->MAIN_MULTILANGS)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	// Sub products
	if($conf->global->PRODUIT_SOUSPRODUITS == 1)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/sousproduits/fiche.php?id=".$product->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');
		$head[$h][2] = 'subproduct';
		$h++;
	}

	if ($conf->fournisseur->enabled)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$product->id;
		$head[$h][1] = $langs->trans("Suppliers");
		$head[$h][2] = 'suppliers';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$product->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$product->id;
	$head[$h][1] = $langs->trans('Documents');
	$head[$h][2] = 'documents';
	$h++;

	if($product->isproduct())	// Si produit stockable
	{
		if ($conf->stock->enabled)
		{
			$head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$product->id;
			$head[$h][1] = $langs->trans("Stock");
			$head[$h][2] = 'stock';
			$h++;
		}
	}

	// More tabs from modules
	if (is_array($conf->tabs_modules['product']))
	{
		$i=0;
		foreach ($conf->tabs_modules['product'] as $value)
		{
			$values=split(':',$value);
			if ($values[2]) $langs->load($values[2]);
			$head[$h][0] = eregi_replace('__ID__',$product->id,$values[3]);
			$head[$h][1] = $langs->trans($values[1]);
			$head[$h][2] = 'tab'.$values[1];
			$h++;
		}
	}

	// More tabs from canvas
	if (is_array($product->onglets))
	{
		foreach ($product->onglets as $onglet)
		{
			$head[$h] = $onglet;
			$h++;
		}
	}

	return $head;
}


function show_stats_for_company($product,$socid)
{
	global $conf,$langs,$user,$db;

	print '<tr>';
	print '<td align="left" width="25%" valign="top">'.$langs->trans("Referers").'</td>';
	print '<td align="right" width="25%">'.$langs->trans("NbOfThirdParties").'</td>';
	print '<td align="right" width="25%">'.$langs->trans("NbOfReferers").'</td>';
	print '<td align="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
	print '</tr>';

	// Propals
	if ($conf->propal->enabled && $user->rights->propale->lire)
	{
		$ret=$product->load_stats_propale($socid);
		if ($ret < 0) dolibarr_print_error($db);
		$langs->load("propal");
		print '<tr><td>';
		print '<a href="propal.php?id='.$product->id.'">'.img_object('','propal').' '.$langs->trans("Proposals").'</a>';
		print '</td><td align="right">';
		print $product->stats_propale['customers'];
		print '</td><td align="right">';
		print $product->stats_propale['nb'];
		print '</td><td align="right">';
		print $product->stats_propale['qty'];
		print '</td>';
		print '</tr>';
	}
	// Commandes clients
	if ($conf->commande->enabled && $user->rights->commande->lire)
	{
		$ret=$product->load_stats_commande($socid);
		if ($ret < 0) dolibarr_print_error($db);
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="commande.php?id='.$product->id.'">'.img_object('','order').' '.$langs->trans("CustomersOrders").'</a>';
		print '</td><td align="right">';
		print $product->stats_commande['customers'];
		print '</td><td align="right">';
		print $product->stats_commande['nb'];
		print '</td><td align="right">';
		print $product->stats_commande['qty'];
		print '</td>';
		print '</tr>';
	}
	// Commandes fournisseurs
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire)
	{
		$ret=$product->load_stats_commande_fournisseur($socid);
		if ($ret < 0) dolibarr_print_error($db);
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="commande_fournisseur.php?id='.$product->id.'">'.img_object('','order').' '.$langs->trans("SuppliersOrders").'</a>';
		print '</td><td align="right">';
		print $product->stats_commande_fournisseur['suppliers'];
		print '</td><td align="right">';
		print $product->stats_commande_fournisseur['nb'];
		print '</td><td align="right">';
		print $product->stats_commande_fournisseur['qty'];
		print '</td>';
		print '</tr>';
	}
	// Contrats
	if ($conf->contrat->enabled && $user->rights->contrat->lire)
	{
		$ret=$product->load_stats_contrat($socid);
		if ($ret < 0) dolibarr_print_error($db);
		$langs->load("contracts");
		print '<tr><td>';
		print '<a href="contrat.php?id='.$product->id.'">'.img_object('','contract').' '.$langs->trans("Contracts").'</a>';
		print '</td><td align="right">';
		print $product->stats_contrat['customers'];
		print '</td><td align="right">';
		print $product->stats_contrat['nb'];
		print '</td><td align="right">';
		print $product->stats_contrat['qty'];
		print '</td>';
		print '</tr>';
	}
	// Factures clients
	if ($conf->facture->enabled && $user->rights->facture->lire)
	{
		$ret=$product->load_stats_facture($socid);
		if ($ret < 0) dolibarr_print_error($db);
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="facture.php?id='.$product->id.'">'.img_object('','bill').' '.$langs->trans("CustomersInvoices").'</a>';
		print '</td><td align="right">';
		print $product->stats_facture['customers'];
		print '</td><td align="right">';
		print $product->stats_facture['nb'];
		print '</td><td align="right">';
		print $product->stats_facture['qty'];
		print '</td>';
		print '</tr>';
	}
	// Factures fournisseurs
	if ($conf->fournisseur->enabled && $user->rights->fournisseur->facture->lire)
	{
		$ret=$product->load_stats_facture_fournisseur($socid);
		if ($ret < 0) dolibarr_print_error($db);
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="facture_fournisseur.php?id='.$product->id.'">'.img_object('','bill').' '.$langs->trans("SuppliersInvoices").'</a>';
		print '</td><td align="right">';
		print $product->stats_facture_fournisseur['suppliers'];
		print '</td><td align="right">';
		print $product->stats_facture_fournisseur['nb'];
		print '</td><td align="right">';
		print $product->stats_facture_fournisseur['qty'];
		print '</td>';
		print '</tr>';
	}

	return 0;
}


/**
 *	\brief   Renvoi le texte d'une unite
 *	\param   int                 Unit
 *	\param   measuring_style     Le style de mesure : weight, volume,...
 *	\return  string	            Unite
 *	\TODO gerer les autres unites de mesure comme la livre, le gallon, le litre, ...
 */
function measuring_units_string($unit,$measuring_style='')
{
	/* Note Rodo aux dev :)
	 * Ne pas ins�rer dans la base de donn�es ces valeurs
	 * cela surchagerait inutilement d'une requete suppl�mentaire
	 * pour quelque chose qui est somme toute peu variable
	 */

	global $langs;

	if ($measuring_style == 'weight')
	{
		$measuring_units[3] = $langs->trans("WeightUnitton");
		$measuring_units[0] = $langs->trans("WeightUnitkg");
		$measuring_units[-3] = $langs->trans("WeightUnitg");
		$measuring_units[-6] = $langs->trans("WeightUnitmg");
	}
	else if ($measuring_style == 'volume')
	{
		$measuring_units[0] = $langs->trans("VolumeUnitm3");
		$measuring_units[-3] = $langs->trans("VolumeUnitdm3");
		$measuring_units[-6] = $langs->trans("VolumeUnitcm3");
		$measuring_units[-9] = $langs->trans("VolumeUnitmm3");
	}
	else if ($measuring_style == 'size')
	{
		$measuring_units[0] = $langs->trans("SizeUnitm");
		$measuring_units[-1] = $langs->trans("SizeUnitdm");
		$measuring_units[-2] = $langs->trans("SizeUnitcm");
		$measuring_units[-3] = $langs->trans("SizeUnitmm");
	}

	return $measuring_units[$unit];
}

?>
