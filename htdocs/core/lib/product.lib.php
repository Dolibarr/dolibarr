<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/product.lib.php
 *	\brief      Ensemble de fonctions de base pour le module produit et service
 * 	\ingroup	product
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Object	$object		Object related to tabs
 * @param	User	$user		Object user
 * @return  array				Array of tabs to shoc
 */
function product_prepare_head($object, $user)
{
	global $langs, $conf;
	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$object->id;
	$head[$h][1] = $langs->trans("CustomerPrices");
	$head[$h][2] = 'price';
	$h++;

	if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$object->id;
		$head[$h][1] = $langs->trans("SuppliersPrices");
		$head[$h][2] = 'suppliers';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/photos.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Photos");
	$head[$h][2] = 'photos';
	$h++;

	// Show category tab
	if (! empty($conf->categorie->enabled) && $user->rights->categorie->lire)
	{
		$head[$h][0] = DOL_URL_ROOT."/categories/categorie.php?id=".$object->id.'&type=0';
		$head[$h][1] = $langs->trans('Categories');
		$head[$h][2] = 'category';
		$h++;
	}

	// Multilangs
	if (! empty($conf->global->MAIN_MULTILANGS))
	{
		$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$object->id;
		$head[$h][1] = $langs->trans("Translation");
		$head[$h][2] = 'translation';
		$h++;
	}

	// Sub products
	if (! empty($conf->global->PRODUIT_SOUSPRODUITS))
	{
		$head[$h][0] = DOL_URL_ROOT."/product/composition/fiche.php?id=".$object->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');
		$head[$h][2] = 'subproduct';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/stats/fiche.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

    if($object->isproduct())    // Si produit stockable
    {
        if (! empty($conf->stock->enabled) && $user->rights->stock->lire)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$object->id;
            $head[$h][1] = $langs->trans("Stock");
            $head[$h][2] = 'stock';
            $h++;
        }
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf,$langs,$object,$head,$h,'product');

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	if (! empty($conf->product->enabled)) $upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    elseif (! empty($conf->service->enabled)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir));
    $head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if($nbFiles > 0) $head[$h][1].= ' ('.$nbFiles.')';
	$head[$h][2] = 'documents';
	$h++;


	// More tabs from canvas
	// TODO Is this still used ?
	if (isset($object->onglets) && is_array($object->onglets))
	{
		foreach ($object->onglets as $onglet)
		{
			$head[$h] = $onglet;
			$h++;
		}
	}

    complete_head_from_modules($conf,$langs,$object,$head,$h,'product', 'remove');

	return $head;
}

/**
*  Return array head with list of tabs to view object informations.
*
*  @param	Object	$object		Product
*  @return	array   	        head array with tabs
*/
function product_admin_prepare_head($object=null)
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/admin/product.php";
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf,$langs,$object,$head,$h,'product_admin');

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/product_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf,$langs,$object,$head,$h,'product_admin','remove');

	return $head;
}


/**
 * Show stats for company
 *
 * @param	Product		$product	Product object
 * @param 	int			$socid		Thirdparty id
 * @return	void
 */
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
	if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
	{
		$ret=$product->load_stats_propale($socid);
		if ($ret < 0) dol_print_error($db);
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
	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$ret=$product->load_stats_commande($socid);
		if ($ret < 0) dol_print_error($db);
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
	if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire)
	{
		$ret=$product->load_stats_commande_fournisseur($socid);
		if ($ret < 0) dol_print_error($db);
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
	if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
	{
		$ret=$product->load_stats_contrat($socid);
		if ($ret < 0) dol_print_error($db);
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
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		$ret=$product->load_stats_facture($socid);
		if ($ret < 0) dol_print_error($db);
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
	if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
	{
		$ret=$product->load_stats_facture_fournisseur($socid);
		if ($ret < 0) dol_print_error($db);
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
 *	Return translation label of a unit key
 *
 *	@param	int		$unit                Unit key (-3,0,3,98,99...)
 *	@param  string	$measuring_style     Style of unit: weight, volume,...
 *	@return	string	   			         Unit string
 * 	@see	load_measuring_units
 */
function measuring_units_string($unit,$measuring_style='')
{
	global $langs;

	if ($measuring_style == 'weight')
	{
		$measuring_units[3] = $langs->trans("WeightUnitton");
		$measuring_units[0] = $langs->trans("WeightUnitkg");
		$measuring_units[-3] = $langs->trans("WeightUnitg");
		$measuring_units[-6] = $langs->trans("WeightUnitmg");
        $measuring_units[99] = $langs->trans("WeightUnitpound");
	}
	else if ($measuring_style == 'size')
	{
		$measuring_units[0] = $langs->trans("SizeUnitm");
		$measuring_units[-1] = $langs->trans("SizeUnitdm");
		$measuring_units[-2] = $langs->trans("SizeUnitcm");
		$measuring_units[-3] = $langs->trans("SizeUnitmm");
        $measuring_units[98] = $langs->trans("SizeUnitfoot");
		$measuring_units[99] = $langs->trans("SizeUnitinch");
	}
	else if ($measuring_style == 'surface')
	{
		$measuring_units[0] = $langs->trans("SurfaceUnitm2");
		$measuring_units[-2] = $langs->trans("SurfaceUnitdm2");
		$measuring_units[-4] = $langs->trans("SurfaceUnitcm2");
		$measuring_units[-6] = $langs->trans("SurfaceUnitmm2");
        $measuring_units[98] = $langs->trans("SurfaceUnitfoot2");
		$measuring_units[99] = $langs->trans("SurfaceUnitinch2");
	}
	else if ($measuring_style == 'volume')
	{
		$measuring_units[0] = $langs->trans("VolumeUnitm3");
		$measuring_units[-3] = $langs->trans("VolumeUnitdm3");
		$measuring_units[-6] = $langs->trans("VolumeUnitcm3");
		$measuring_units[-9] = $langs->trans("VolumeUnitmm3");
        $measuring_units[88] = $langs->trans("VolumeUnitfoot3");
        $measuring_units[89] = $langs->trans("VolumeUnitinch3");
		$measuring_units[97] = $langs->trans("VolumeUnitounce");
		$measuring_units[98] = $langs->trans("VolumeUnitlitre");
        $measuring_units[99] = $langs->trans("VolumeUnitgallon");
	}

	return $measuring_units[$unit];
}

?>
