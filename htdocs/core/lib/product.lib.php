<?php
/* Copyright (C) 2006-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2010  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García			<marcosgdf@gmail.com>
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
 * @param   Product	$object		Object related to tabs
 * @return  array				Array of tabs to show
 */
function product_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("products");

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Card");
	$head[$h][2] = 'card';
	$h++;

	if (! empty($object->status))
	{
    	$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$object->id;
    	$head[$h][1] = $langs->trans("SellingPrices");
    	$head[$h][2] = 'price';
    	$h++;
	}

	if (! empty($object->status_buy) || (! empty($conf->margin->enabled) && ! empty($object->status)))   // If margin is on and product on sell, we may need the cost price even if product os not on purchase
	{
    	if ((! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->lire)
    	|| (! empty($conf->margin->enabled) && $user->rights->margin->liretous)
    	)
    	{
    		$head[$h][0] = DOL_URL_ROOT."/product/fournisseurs.php?id=".$object->id;
    		$head[$h][1] = $langs->trans("BuyingPrices");
    		$head[$h][2] = 'suppliers';
    		$h++;
    	}
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
		$head[$h][0] = DOL_URL_ROOT."/product/composition/card.php?id=".$object->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');

		$nbFatherAndChild = $object->hasFatherOrChild();
		if ($nbFatherAndChild > 0) $head[$h][1].= ' <span class="badge">'.$nbFatherAndChild.'</span>';
		$head[$h][2] = 'subproduct';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/product/stats/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?showmessage=1&id=".$object->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

	if (!empty($conf->variants->enabled) && ($object->isProduct() || $object->isService())) {

		global $db;

		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';

		$prodcomb = new ProductCombination($db);

		if ($prodcomb->fetchByFkProductChild($object->id) == -1)
		{
			$head[$h][0] = DOL_URL_ROOT."/variants/combinations.php?id=".$object->id;
			$head[$h][1] = $langs->trans('ProductCombinations');
			$head[$h][2] = 'combinations';
			$nbVariant = $prodcomb->countNbOfCombinationForFkProductParent($object->id);
            if ($nbVariant > 0) $head[$h][1].= ' <span class="badge">'.$nbVariant.'</span>';
		}

		$h++;
	}

    if ($object->isProduct() || ($object->isService() && ! empty($conf->global->STOCK_SUPPORTS_SERVICES)))    // If physical product we can stock (or service with option)
    {
        if (! empty($conf->stock->enabled) && $user->rights->stock->lire)
        {
            $head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$object->id;
            $head[$h][1] = $langs->trans("Stock");
            $head[$h][2] = 'stock';
            $h++;
        }
    }

    // Tab to link resources
    if (!empty($conf->resource->enabled))
    {
        if ($object->isProduct() && ! empty($conf->global->RESOURCE_ON_PRODUCTS))
        {
            $head[$h][0] = DOL_URL_ROOT . '/resource/element_resource.php?element=product&ref=' . $object->ref;
            $head[$h][1] = $langs->trans("Resources");
            $head[$h][2] = 'resources';
            $h++;
        }
        if ($object->isService() && ! empty($conf->global->RESOURCE_ON_SERVICES))
        {
            $head[$h][0] = DOL_URL_ROOT . '/resource/element_resource.php?element=service&ref=' . $object->ref;
            $head[$h][1] = $langs->trans("Resources");
            $head[$h][2] = 'resources';
            $h++;
        }
    }

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'product');

    // Notes
    if (empty($conf->global->MAIN_DISABLE_NOTES_TAB))
    {
        $nbNote = 0;
        if(!empty($object->note_private)) $nbNote++;
        if(!empty($object->note_public)) $nbNote++;
        $head[$h][0] = DOL_URL_ROOT.'/product/note.php?id='.$object->id;
        $head[$h][1] = $langs->trans('Notes');
        if ($nbNote > 0) $head[$h][1].= ' <span class="badge">'.$nbNote.'</span>';
        $head[$h][2] = 'note';
        $h++;
    }

    // Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    if (! empty($conf->product->enabled) && ($object->type==Product::TYPE_PRODUCT)) $upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    if (! empty($conf->service->enabled) && ($object->type==Product::TYPE_SERVICE)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
        if (! empty($conf->product->enabled) && ($object->type==Product::TYPE_PRODUCT)) $upload_dir = $conf->produit->multidir_output[$object->entity].'/'.get_exdir($object->id, 2, 0, 0, $object, 'product').$object->id.'/photos';
        if (! empty($conf->service->enabled) && ($object->type==Product::TYPE_SERVICE)) $upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir($object->id, 2, 0, 0, $object, 'product').$object->id.'/photos';
        $nbFiles += count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    }
    $nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
	$head[$h][2] = 'documents';
	$h++;

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'product', 'remove');

    // Log
    $head[$h][0] = DOL_URL_ROOT.'/product/agenda.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Events");
    if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
    {
    	$head[$h][1].= '/';
    	$head[$h][1].= $langs->trans("Agenda");
    }
    $head[$h][2] = 'agenda';
    $h++;

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   ProductLot	$object		Object related to tabs
 * @return  array		     		Array of tabs to show
 */
function productlot_prepare_head($object)
{
    global $db, $langs, $conf, $user;

    // Load translation files required by the page
    $langs->loadLangs(array("products","productbatch"));

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT."/product/stock/productlot_card.php?id=".$object->id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'card';
	$h++;

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
    $upload_dir = $conf->productbatch->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
    $nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
    $nbLinks=Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT."/product/stock/productlot_document.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles+$nbLinks) > 0) $head[$h][1].= ' <span class="badge">'.($nbFiles+$nbLinks).'</span>';
    $head[$h][2] = 'documents';
	$h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'productlot');

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'productlot', 'remove');

    // Log
    /*
    $head[$h][0] = DOL_URL_ROOT.'/product/info.php?id='.$object->id;
    $head[$h][1] = $langs->trans("Info");
    $head[$h][2] = 'info';
    $h++;
    */

    return $head;
}



/**
*  Return array head with list of tabs to view object informations.
*
*  @return	array   	        head array with tabs
*/
function product_admin_prepare_head()
{
	global $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/admin/product.php";
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	if (!empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($conf->global->PRODUIT_MULTIPRICES_ALLOW_AUTOCALC_PRICELEVEL))
	{
		$head[$h] = array(
			0 => DOL_URL_ROOT."/product/admin/price_rules.php",
			1 => $langs->trans('MultipriceRules'),
			2 => 'generator'
		);
		$h++;
	}

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'product_admin');

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/product_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'product_admin', 'remove');

	return $head;
}



/**
 *  Return array head with list of tabs to view object informations.
 *
 *  @return	array   	        head array with tabs
 */
function product_lot_admin_prepare_head()
{
    global $langs, $conf, $user;

    $h = 0;
    $head = array();

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    // $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
    // $this->tabs = array('entity:-tabname);   												to remove a tab
    complete_head_from_modules($conf, $langs, null, $head, $h, 'product_lot_admin');

    $head[$h][0] = DOL_URL_ROOT.'/product/admin/product_lot_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFields");
    $head[$h][2] = 'attributes';
    $h++;

    complete_head_from_modules($conf, $langs, null, $head, $h, 'product_lot_admin', 'remove');

    return $head;
}



/**
 * Show stats for company
 *
 * @param	Product		$product	Product object
 * @param 	int			$socid		Thirdparty id
 * @return	integer					NB of lines shown into array
 */
function show_stats_for_company($product, $socid)
{
	global $conf,$langs,$user,$db;

	$nblines = 0;

	print '<tr class="liste_titre">';
	print '<td class="left" width="25%">'.$langs->trans("Referers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfThirdParties").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfObjectReferers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
	print '</tr>';

	// Customer proposals
	if (! empty($conf->propal->enabled) && $user->rights->propale->lire)
	{
		$nblines++;
		$ret=$product->load_stats_propale($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("propal");
		print '<tr><td>';
		print '<a href="propal.php?id='.$product->id.'">'.img_object('', 'propal').' '.$langs->trans("Proposals").'</a>';
		print '</td><td class="right">';
		print $product->stats_propale['customers'];
		print '</td><td class="right">';
		print $product->stats_propale['nb'];
		print '</td><td class="right">';
		print $product->stats_propale['qty'];
		print '</td>';
		print '</tr>';
	}
	// Supplier proposals
	if (! empty($conf->supplier_proposal->enabled) && $user->rights->supplier_proposal->lire)
	{
		$nblines++;
		$ret=$product->load_stats_proposal_supplier($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("propal");
		print '<tr><td>';
		print '<a href="supplier_proposal.php?id='.$product->id.'">'.img_object('', 'propal').' '.$langs->trans("SupplierProposals").'</a>';
		print '</td><td class="right">';
		print $product->stats_proposal_supplier['suppliers'];
		print '</td><td class="right">';
		print $product->stats_proposal_supplier['nb'];
		print '</td><td class="right">';
		print $product->stats_proposal_supplier['qty'];
		print '</td>';
		print '</tr>';
	}
	// Customer orders
	if (! empty($conf->commande->enabled) && $user->rights->commande->lire)
	{
		$nblines++;
		$ret=$product->load_stats_commande($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="commande.php?id='.$product->id.'">'.img_object('', 'order').' '.$langs->trans("CustomersOrders").'</a>';
		print '</td><td class="right">';
		print $product->stats_commande['customers'];
		print '</td><td class="right">';
		print $product->stats_commande['nb'];
		print '</td><td class="right">';
		print $product->stats_commande['qty'];
		print '</td>';
		print '</tr>';
	}
	// Supplier orders
	if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->commande->lire)
	{
		$nblines++;
		$ret=$product->load_stats_commande_fournisseur($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="commande_fournisseur.php?id='.$product->id.'">'.img_object('', 'order').' '.$langs->trans("SuppliersOrders").'</a>';
		print '</td><td class="right">';
		print $product->stats_commande_fournisseur['suppliers'];
		print '</td><td class="right">';
		print $product->stats_commande_fournisseur['nb'];
		print '</td><td class="right">';
		print $product->stats_commande_fournisseur['qty'];
		print '</td>';
		print '</tr>';
	}
	// Customer invoices
	if (! empty($conf->facture->enabled) && $user->rights->facture->lire)
	{
		$nblines++;
		$ret=$product->load_stats_facture($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="facture.php?id='.$product->id.'">'.img_object('', 'bill').' '.$langs->trans("CustomersInvoices").'</a>';
		print '</td><td class="right">';
		print $product->stats_facture['customers'];
		print '</td><td class="right">';
		print $product->stats_facture['nb'];
		print '</td><td class="right">';
		print $product->stats_facture['qty'];
		print '</td>';
		print '</tr>';
	}
	// Supplier invoices
	if (! empty($conf->fournisseur->enabled) && $user->rights->fournisseur->facture->lire)
	{
		$nblines++;
		$ret=$product->load_stats_facture_fournisseur($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="facture_fournisseur.php?id='.$product->id.'">'.img_object('', 'bill').' '.$langs->trans("SuppliersInvoices").'</a>';
		print '</td><td class="right">';
		print $product->stats_facture_fournisseur['suppliers'];
		print '</td><td class="right">';
		print $product->stats_facture_fournisseur['nb'];
		print '</td><td class="right">';
		print $product->stats_facture_fournisseur['qty'];
		print '</td>';
		print '</tr>';
	}

	// Contracts
	if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
	{
		$nblines++;
		$ret=$product->load_stats_contrat($socid);
		if ($ret < 0) dol_print_error($db);
		$langs->load("contracts");
		print '<tr><td>';
		print '<a href="contrat.php?id='.$product->id.'">'.img_object('', 'contract').' '.$langs->trans("Contracts").'</a>';
		print '</td><td class="right">';
		print $product->stats_contrat['customers'];
		print '</td><td class="right">';
		print $product->stats_contrat['nb'];
		print '</td><td class="right">';
		print $product->stats_contrat['qty'];
		print '</td>';
		print '</tr>';
	}

	return $nblines++;
}

/**
 *	Return translation label of a unit key.
 *  Function kept for backward compatibility.
 *
 *  @param	string  $scale				 Scale of unit: '0', '-3', '6', ...
 *	@param  string	$measuring_style     Style of unit: weight, volume,...
 *	@param	int		$unit                ID of unit (rowid in llx_c_units table)
 *  @param	int		$use_short_label	 1=Use short label ('g' instead of 'gram'). Short labels are not translated.
 *	@return	string	   			         Unit string
 * 	@see	measuringUnitString() formproduct->selectMeasuringUnits()
 */
function measuring_units_string($scale = '', $measuring_style = '', $unit = 0, $use_short_label = 0)
{
	return measuringUnitString($unit, $measuring_style, $scale, $use_short_label);
}

/**
 *	Return translation label of a unit key
 *
 *	@param	int		$unit                ID of unit (rowid in llx_c_units table)
 *	@param  string	$measuring_style     Style of unit: weight, volume,...
 *  @param	string  $scale				 Scale of unit: '0', '-3', '6', ...
 *  @param	int		$use_short_label	 1=Use short label ('g' instead of 'gram'). Short labels are not translated.
 *	@return	string	   			         Unit string
 * 	@see	formproduct->selectMeasuringUnits()
 */
function measuringUnitString($unit, $measuring_style = '', $scale = '', $use_short_label = 0)
{
	global $langs, $db;
	global $measuring_unit_cache;

	if (empty($measuring_unit_cache[$unit.'_'.$measuring_style.'_'.$scale.'_'.$use_short_label]))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
		$measuringUnits= new CUnits($db);

		if ($scale !== '')
		{
			$arrayforfilter = array(
				't.scale' => $scale,
				't.unit_type' => $measuring_style,
				't.active' => 1
			);
		}
		else
		{
			$arrayforfilter = array(
				't.rowid' => $unit,
				't.unit_type' => $measuring_style,
				't.active' => 1
			);
		}
		$result = $measuringUnits->fetchAll('', '', 0, 0, $arrayforfilter);

		if ($result < 0) {
			return -1;
		} else {
			if (is_array($measuringUnits->records) && count($measuringUnits->records)>0) {
				if ($use_short_label) $labeltoreturn = $measuringUnits->records[key($measuringUnits->records)]->short_label;
				else $labeltoreturn = $langs->transnoentitiesnoconv($measuringUnits->records[key($measuringUnits->records)]->label);
			} else {
				$labeltoreturn = '';
			}
			$measuring_unit_cache[$unit.'_'.$measuring_style.'_'.$scale.'_'.$use_short_label] = $labeltoreturn;
			return $labeltoreturn;
		}
	}
	else {
		return $measuring_unit_cache[$unit.'_'.$measuring_style.'_'.$scale.'_'.$use_short_label];
	}
}

/**
 *	Transform a given unit scale into the square of that unit, if known.
 *
 *	@param	int		$unit            Unit scale key (-3,-2,-1,0,98,99...)
 *	@return	int	   			         Squared unit key (-6,-4,-2,0,98,99...)
 * 	@see	formproduct->selectMeasuringUnits
 */
function measuring_units_squared($unit)
{
	$measuring_units=array();
	$measuring_units[0] = 0;   // m -> m3
	$measuring_units[-1] = -2; // dm-> dm2
	$measuring_units[-2] = -4; // cm -> cm2
	$measuring_units[-3] = -6; // mm -> mm2
	$measuring_units[98] = 98; // foot -> foot2
	$measuring_units[99] = 99; // inch -> inch2
	return $measuring_units[$unit];
}


/**
 *	Transform a given unit scale into the cube of that unit, if known
 *
 *	@param	int		$unit            Unit scale key (-3,-2,-1,0,98,99...)
 *	@return	int	   			         Cubed unit key (-9,-6,-3,0,88,89...)
 * 	@see	formproduct->selectMeasuringUnits
 */
function measuring_units_cubed($unit)
{
	$measuring_units=array();
	$measuring_units[0] = 0;   // m -> m2
	$measuring_units[-1] = -3; // dm-> dm3
	$measuring_units[-2] = -6; // cm -> cm3
	$measuring_units[-3] = -9; // mm -> mm3
	$measuring_units[98] = 88; // foot -> foot3
	$measuring_units[99] = 89; // inch -> inch3
	return $measuring_units[$unit];
}
