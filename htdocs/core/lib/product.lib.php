<?php
/* Copyright (C) 2006-2015  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2007       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2010  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015-2016	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2023	   	Gauthier VERDOL			<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024	   	Jean-Rémi TAPONIER		<jean-remi@netlogic.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Mélina Joum				<melina.joum@altairis.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 * \file       htdocs/core/lib/product.lib.php
 * \brief      Ensemble de functions de base pour le module produit et service
 * \ingroup	product
 */

/**
 * Prepare array with list of tabs
 *
 * @param   Product	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function product_prepare_head($object)
{
	global $db, $langs, $conf, $user;
	$langs->load("products");

	$label = $langs->trans('Product');
	$usercancreadprice = getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('product', 'product_advance', 'read_prices') : $user->hasRight('product', 'read');

	if ($object->isService()) {
		$label = $langs->trans('Service');
		$usercancreadprice = getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('service', 'service_advance', 'read_prices') : $user->hasRight('service', 'read');
	}

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/card.php?id=".$object->id;
	$head[$h][1] = $label;
	$head[$h][2] = 'card';
	$h++;

	// if (!empty($object->status)) {
	if ($usercancreadprice) {
		$head[$h][0] = DOL_URL_ROOT."/product/price.php?id=".$object->id;
		$head[$h][1] = $langs->trans("SellingPrices");
		$head[$h][2] = 'price';
		$h++;
	} else {
		$head[$h][0] = '#';
		$head[$h][1] = $langs->trans("SellingPrices");
		$head[$h][2] = 'price';
		$head[$h][5] = 'disabled';
		$h++;
	}
	// }

	// if (!empty($object->status_buy) || (isModEnabled('margin') && !empty($object->status))) {   // If margin is on and product on sell, we may need the cost price even if product os not on purchase
	if ((isModEnabled("supplier_proposal") || isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) && ($user->hasRight('fournisseur', 'lire') || $user->hasRight('supplier_order', 'read') || $user->hasRight('supplier_invoice', 'read'))
		|| (isModEnabled('margin') && $user->hasRight("margin", "liretous"))
	) {
		if ($usercancreadprice) {
			$head[$h][0] = DOL_URL_ROOT."/product/price_suppliers.php?id=".$object->id;
			$head[$h][1] = $langs->trans("BuyingPrices");
			$head[$h][2] = 'suppliers';
			$h++;
		} else {
			$head[$h][0] = '#';
			$head[$h][1] = $langs->trans("BuyingPrices");
			$head[$h][2] = 'suppliers';
			$head[$h][5] = 'disabled';
			$h++;
		}
	}
	// }

	// Multilangs
	if (getDolGlobalInt('MAIN_MULTILANGS')) {
		$head[$h][0] = DOL_URL_ROOT."/product/traduction.php?id=".$object->id;
		$head[$h][1] = $langs->trans("Translations");
		$nbTranslations = !empty($object->multilangs) ? count($object->multilangs) : 0;
		if ($nbTranslations > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbTranslations.'</span>';
		}
		$head[$h][2] = 'translation';
		$h++;
	}

	// Sub products
	if (getDolGlobalString('PRODUIT_SOUSPRODUITS')) {
		$head[$h][0] = DOL_URL_ROOT."/product/composition/card.php?id=".$object->id;
		$head[$h][1] = $langs->trans('AssociatedProducts');

		$nbFatherAndChild = $object->hasFatherOrChild();
		if ($nbFatherAndChild > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbFatherAndChild.'</span>';
		}
		$head[$h][2] = 'subproduct';
		$h++;
	}

	if (isModEnabled('variants') && ($object->isProduct() || $object->isService())) {
		global $db;

		require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';

		$prodcomb = new ProductCombination($db);

		if ($prodcomb->fetchByFkProductChild($object->id) <= 0) {
			$head[$h][0] = DOL_URL_ROOT."/variants/combinations.php?id=".$object->id;
			$head[$h][1] = $langs->trans('ProductCombinations');
			$head[$h][2] = 'combinations';
			$nbVariant = $prodcomb->countNbOfCombinationForFkProductParent($object->id);
			if ($nbVariant > 0) {
				$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbVariant.'</span>';
			}
		}

		$h++;
	}

	if ($object->isProduct() || ($object->isService() && getDolGlobalString('STOCK_SUPPORTS_SERVICES'))) {    // If physical product we can stock (or service with option)
		if (isModEnabled('stock') && $user->hasRight('stock', 'lire')) {
			$head[$h][0] = DOL_URL_ROOT."/product/stock/product.php?id=".$object->id;
			$head[$h][1] = $langs->trans("Stock");
			$head[$h][2] = 'stock';
			$h++;
		}
	}

	// Tab to link resources
	if (isModEnabled('resource')) {
		if ($object->isProduct() && getDolGlobalString('RESOURCE_ON_PRODUCTS')) {
			$head[$h][0] = DOL_URL_ROOT.'/resource/element_resource.php?element=product&ref='.$object->ref;
			$head[$h][1] = $langs->trans("Resources");
			$head[$h][2] = 'resources';
			$h++;
		}
		if ($object->isService() && getDolGlobalString('RESOURCE_ON_SERVICES')) {
			$head[$h][0] = DOL_URL_ROOT.'/resource/element_resource.php?element=service&ref='.$object->ref;
			$head[$h][1] = $langs->trans("Resources");
			$head[$h][2] = 'resources';
			$h++;
		}
	}

	$head[$h][0] = DOL_URL_ROOT."/product/stats/facture.php?showmessage=1&id=".$object->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stats/card.php?id=".$object->id;
	$head[$h][1] = $langs->trans('Statistics');
	$head[$h][2] = 'stats';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'product', 'add', 'core');

	// Notes
	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT.'/product/note.php?id='.$object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbNote.'</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = '';
	if (isModEnabled("product") && ($object->type == Product::TYPE_PRODUCT)) {
		$upload_dir = $conf->product->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	}
	if (isModEnabled("service") && ($object->type == Product::TYPE_SERVICE)) {
		$upload_dir = $conf->service->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	}
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
		if (isModEnabled("product") && ($object->type == Product::TYPE_PRODUCT)) {
			$upload_dir = $conf->product->multidir_output[$object->entity].'/'.get_exdir($object->id, 2, 0, 0, $object, 'product').$object->id.'/photos';
		}
		if (isModEnabled("service") && ($object->type == Product::TYPE_SERVICE)) {
			$upload_dir = $conf->service->multidir_output[$object->entity].'/'.get_exdir($object->id, 2, 0, 0, $object, 'product').$object->id.'/photos';
		}
		$nbFiles += count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	}
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/product/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Documents');
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	// Log
	$head[$h][0] = DOL_URL_ROOT.'/product/messaging.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Events");
	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		$head[$h][1] .= '/';
		$head[$h][1] .= $langs->trans("Agenda");
	}
	$head[$h][2] = 'agenda';
	$h++;

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'product', 'add', 'external');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'product', 'remove');

	return $head;
}

/**
 * Prepare array with list of tabs
 *
 * @param   ProductLot	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function productlot_prepare_head($object)
{
	global $db, $langs, $conf, $user;

	// Load translation files required by the page
	$langs->loadLangs(array("products", "productbatch"));

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/stock/productlot_card.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Lot");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT."/product/stock/stats/expedition.php?showmessage=1&id=".$object->id;
	$head[$h][1] = $langs->trans('Referers');
	$head[$h][2] = 'referers';
	$h++;

	// Attachments
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->productbatch->multidir_output[$object->entity].'/'.dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT."/product/stock/productlot_document.php?id=".$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;

	// Notes
	if (!getDolGlobalString('MAIN_DISABLE_NOTES_TAB')) {
		$nbNote = 0;
		if (!empty($object->note_private)) {
			$nbNote++;
		}
		if (!empty($object->note_public)) {
			$nbNote++;
		}
		$head[$h][0] = DOL_URL_ROOT .'/product/stock/productlot_note.php?id=' . $object->id;
		$head[$h][1] = $langs->trans('Notes');
		if ($nbNote > 0) {
			$head[$h][1] .= '<span class="badge marginleftonlyshort">' . $nbNote . '</span>';
		}
		$head[$h][2] = 'note';
		$h++;
	}

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
 *  Return array head with list of tabs to view object information.
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function product_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('product');
	$extrafields->fetch_name_optionals_label('product_price');
	$extrafields->fetch_name_optionals_label('product_customer_price');
	$extrafields->fetch_name_optionals_label('product_fournisseur_price');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/admin/product.php";
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'general';
	$h++;

	if (getDolGlobalString('PRODUIT_MULTIPRICES') && getDolGlobalString('PRODUIT_MULTIPRICES_ALLOW_AUTOCALC_PRICELEVEL')) {
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
	$nbExtrafields = $extrafields->attributes['product']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/product_price_extrafields.php';
	$head[$h][1] = $langs->trans("ProductLevelExtraFields");
	$nbExtrafields = $extrafields->attributes['product_price']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'levelAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/product_customer_extrafields.php';
	$head[$h][1] = $langs->trans("ProductCustomerExtraFields");
	$nbExtrafields = $extrafields->attributes['product_customer_price']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'customerAttributes';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/product_supplier_extrafields.php';
	$head[$h][1] = $langs->trans("ProductSupplierExtraFields");
	$nbExtrafields = $extrafields->attributes['product_fournisseur_price']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'supplierAttributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'product_admin', 'remove');

	return $head;
}



/**
 * Return array head with list of tabs to view object information.
 *
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function product_lot_admin_prepare_head()
{
	global $langs, $conf, $user, $db;

	$extrafields = new ExtraFields($db);
	$extrafields->fetch_name_optionals_label('product_lot');

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/product/admin/product_lot.php";
	$head[$h][1] = $langs->trans('Parameters');
	$head[$h][2] = 'settings';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, null, $head, $h, 'product_lot_admin');

	$head[$h][0] = DOL_URL_ROOT.'/product/admin/product_lot_extrafields.php';
	$head[$h][1] = $langs->trans("ExtraFields");
	$nbExtrafields = $extrafields->attributes['product_lot']['count'];
	if ($nbExtrafields > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.$nbExtrafields.'</span>';
	}
	$head[$h][2] = 'attributes';
	$h++;

	complete_head_from_modules($conf, $langs, null, $head, $h, 'product_lot_admin', 'remove');

	return $head;
}



/**
 * Show stats for a product
 *
 * @param	Product		$product	Product object
 * @param 	int			$socid		Thirdparty id
 * @return	integer					NB of lines shown into array
 */
function show_stats_for_company($product, $socid)
{
	global $langs, $user, $db, $hookmanager;

	$form = new Form($db);

	$nblines = 0;

	print '<tr class="liste_titre">';
	print '<td class="left" width="25%">'.$langs->trans("Referers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfThirdParties").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfObjectReferers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
	print '</tr>';

	// Customer proposals
	if (isModEnabled("propal") && $user->hasRight('propal', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_propale($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("propal");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/propal.php?id='.$product->id.'">'.img_object('', 'propal', 'class="pictofixedwidth"').$langs->trans("Proposals").'</a>';
		print '</td><td class="right">';
		print $product->stats_propale['customers'];
		print '</td><td class="right">';
		print $product->stats_propale['nb'];
		print '</td><td class="right">';
		print price($product->stats_propale['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}
	// Supplier proposals
	if (isModEnabled('supplier_proposal') && $user->hasRight('supplier_proposal', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_proposal_supplier($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("supplier_proposal");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/supplier_proposal.php?id='.$product->id.'">'.img_object('', 'supplier_proposal', 'class="pictofixedwidth"').$langs->trans("SupplierProposals").'</a>';
		print '</td><td class="right">';
		print $product->stats_proposal_supplier['suppliers'];
		print '</td><td class="right">';
		print $product->stats_proposal_supplier['nb'];
		print '</td><td class="right">';
		print price($product->stats_proposal_supplier['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}
	// Sales orders
	if (isModEnabled('order') && $user->hasRight('commande', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_commande($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/commande.php?id='.$product->id.'">'.img_object('', 'order', 'class="pictofixedwidth"').$langs->trans("CustomersOrders").'</a>';
		print '</td><td class="right">';
		print $product->stats_commande['customers'];
		print '</td><td class="right">';
		print $product->stats_commande['nb'];
		print '</td><td class="right">';
		print price($product->stats_commande['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}
	// Supplier orders
	if ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'commande', 'lire')) || (isModEnabled("supplier_order") && $user->hasRight('supplier_order', 'lire'))) {
		$nblines++;
		$ret = $product->load_stats_commande_fournisseur($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("orders");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/commande_fournisseur.php?id='.$product->id.'">'.img_object('', 'supplier_order', 'class="pictofixedwidth"').$langs->trans("SuppliersOrders").'</a>';
		print '</td><td class="right">';
		print $product->stats_commande_fournisseur['suppliers'];
		print '</td><td class="right">';
		print $product->stats_commande_fournisseur['nb'];
		print '</td><td class="right">';
		print price($product->stats_commande_fournisseur['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}
	// Customer invoices
	if (isModEnabled('invoice') && $user->hasRight('facture', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_facture($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/facture.php?id='.$product->id.'">'.img_object('', 'bill', 'class="pictofixedwidth"').$langs->trans("CustomersInvoices").'</a>';
		print '</td><td class="right">';
		print $product->stats_facture['customers'];
		print '</td><td class="right">';
		print $product->stats_facture['nb'];
		print '</td><td class="right">';
		print price($product->stats_facture['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}
	// Customer template invoices
	if (isModEnabled("invoice") && $user->hasRight('facture', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_facturerec($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/facturerec.php?id='.$product->id.'">'.img_object('', 'bill', 'class="pictofixedwidth"').$langs->trans("RecurringInvoiceTemplate").'</a>';
		print '</td><td class="right">';
		print $product->stats_facture['customers'];
		print '</td><td class="right">';
		print $product->stats_facturerec['nb'];
		print '</td><td class="right">';
		print $product->stats_facturerec['qty'];
		print '</td>';
		print '</tr>';
	}
	// Supplier invoices
	if ((isModEnabled("fournisseur") && !getDolGlobalString('MAIN_USE_NEW_SUPPLIERMOD') && $user->hasRight('fournisseur', 'facture', 'lire')) || (isModEnabled("supplier_invoice") && $user->hasRight('supplier_invoice', 'lire'))) {
		$nblines++;
		$ret = $product->load_stats_facture_fournisseur($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/facture_fournisseur.php?id='.$product->id.'">'.img_object('', 'supplier_invoice', 'class="pictofixedwidth"').$langs->trans("SuppliersInvoices").'</a>';
		print '</td><td class="right">';
		print $product->stats_facture_fournisseur['suppliers'];
		print '</td><td class="right">';
		print $product->stats_facture_fournisseur['nb'];
		print '</td><td class="right">';
		print price($product->stats_facture_fournisseur['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}

	// Shipments
	if (isModEnabled('shipping') && $user->hasRight('shipping', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_sending($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("sendings");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/expedition.php?id='.$product->id.'">'.img_object('', 'shipment', 'class="pictofixedwidth"').$langs->trans("Shipments").'</a>';
		print '</td><td class="right">';
		print $product->stats_expedition['customers'];
		print '</td><td class="right">';
		print $product->stats_expedition['nb'];
		print '</td><td class="right">';
		print $product->stats_expedition['qty'];
		print '</td>';
		print '</tr>';
	}

	// Receptions
	if ((isModEnabled("reception") && $user->hasRight('reception', 'lire'))) {
		$nblines++;
		$ret = $product->load_stats_reception($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("receptions");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/reception.php?id='.$product->id.'">'.img_object('', 'reception', 'class="pictofixedwidth"').$langs->trans("Receptions").'</a>';
		print '</td><td class="right">';
		print $product->stats_reception['suppliers'];
		print '</td><td class="right">';
		print $product->stats_reception['nb'];
		print '</td><td class="right">';
		print $product->stats_reception['qty'];
		print '</td>';
		print '</tr>';
	}

	// Contracts
	if (isModEnabled('contract') && $user->hasRight('contrat', 'lire')) {
		$nblines++;
		$ret = $product->load_stats_contrat($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("contracts");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/contrat.php?id='.$product->id.'">'.img_object('', 'contract', 'class="pictofixedwidth"').$langs->trans("Contracts").'</a>';
		print '</td><td class="right">';
		print $product->stats_contrat['customers'];
		print '</td><td class="right">';
		print $product->stats_contrat['nb'];
		print '</td><td class="right">';
		print price($product->stats_contrat['qty'], 1, $langs, 0, 0);
		print '</td>';
		print '</tr>';
	}

	// BOM
	if (isModEnabled('bom') && $user->hasRight('bom', 'read')) {
		$nblines++;
		$ret = $product->load_stats_bom($socid);
		if ($ret < 0) {
			setEventMessage($product->error, 'errors');
		}
		$langs->load("mrp");

		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/bom.php?id='.$product->id.'">'.img_object('', 'bom', 'class="pictofixedwidth"').$langs->trans("BOM").'</a>';
		print '</td><td class="right">';

		print '</td><td class="right">';
		print $form->textwithpicto($product->stats_bom['nb_toconsume'], $langs->trans("RowMaterial"));
		print ' ';
		print $form->textwithpicto($product->stats_bom['nb_toproduce'], $langs->trans("Finished"));
		print '</td><td class="right">';
		print $form->textwithpicto($product->stats_bom['qty_toconsume'], $langs->trans("RowMaterial"));
		print ' ';
		print $form->textwithpicto($product->stats_bom['qty_toproduce'], $langs->trans("Finished"));
		print '</td>';
		print '</tr>';
	}

	// MO
	if (isModEnabled('mrp') && $user->hasRight('mrp', 'read')) {
		$nblines++;
		$ret = $product->load_stats_mo($socid);
		if ($ret < 0) {
			setEventMessages($product->error, $product->errors, 'errors');
		}
		$langs->load("mrp");
		print '<tr><td>';
		print '<a href="'.DOL_URL_ROOT.'/product/stats/mo.php?id='.$product->id.'">'.img_object('', 'mrp', 'class="pictofixedwidth"').$langs->trans("MO").'</a>';
		print '</td><td class="right">';
		print $form->textwithpicto($product->stats_mo['customers_toconsume'], $langs->trans("ToConsume"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['customers_consumed'], $langs->trans("QtyAlreadyConsumed"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['customers_toproduce'], $langs->trans("QtyToProduce"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['customers_produced'], $langs->trans("QtyAlreadyProduced"));
		print '</td><td class="right">';
		print $form->textwithpicto($product->stats_mo['nb_toconsume'], $langs->trans("ToConsume"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['nb_consumed'], $langs->trans("QtyAlreadyConsumed"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['nb_toproduce'], $langs->trans("QtyToProduce"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['nb_produced'], $langs->trans("QtyAlreadyProduced"));
		print '</td><td class="right">';
		print $form->textwithpicto($product->stats_mo['qty_toconsume'], $langs->trans("ToConsume"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['qty_consumed'], $langs->trans("QtyAlreadyConsumed"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['qty_toproduce'], $langs->trans("QtyToProduce"));
		print ' ';
		print $form->textwithpicto($product->stats_mo['qty_produced'], $langs->trans("QtyAlreadyProduced"));
		print '</td>';
		print '</tr>';
	}
	$parameters = array('socid' => $socid);
	$reshook = $hookmanager->executeHooks('addMoreProductStat', $parameters, $product, $nblines); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	print $hookmanager->resPrint;


	return $nblines++;
}

/**
 * Show stats for product batch
 *
 * @param	Productlot	$batch	Product batch object
 * @param 	int			$socid	Thirdparty id
 * @return	integer				NB of lines shown into array
 */
function show_stats_for_batch($batch, $socid)
{
	global $conf, $langs, $user, $db, $hookmanager;

	$langs->LoadLangs(array('sendings', 'orders', 'receptions'));

	$form = new Form($db);

	$nblines = 0;

	print '<tr class="liste_titre">';
	print '<td class="left" width="25%">'.$langs->trans("Referers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfThirdParties").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("NbOfObjectReferers").'</td>';
	print '<td class="right" width="25%">'.$langs->trans("TotalQuantity").'</td>';
	print '</tr>';

	// Expeditions
	if (isModEnabled('shipping') && $user->hasRight('expedition', 'lire')) {
		$nblines++;
		$ret = $batch->loadStatsExpedition($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="'.dol_buildpath('/product/stock/stats/expedition.php', 1).'?id='.$batch->id.'">'.img_object('', 'bill', 'class="pictofixedwidth"').$langs->trans("Shipments").'</a>';
		print '</td><td class="right">';
		print $batch->stats_expedition['customers'];
		print '</td><td class="right">';
		print $batch->stats_expedition['nb'];
		print '</td><td class="right">';
		print $batch->stats_expedition['qty'];
		print '</td>';
		print '</tr>';
	}

	if (isModEnabled("reception") && $user->hasRight('reception', 'lire')) {
		$nblines++;
		$ret = $batch->loadStatsReception($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="'.dol_buildpath('/product/stock/stats/reception.php', 1).'?id='.$batch->id.'">'.img_object('', 'bill', 'class="pictofixedwidth"').$langs->trans("Receptions").'</a>';
		print '</td><td class="right">';
		print $batch->stats_reception['customers'];
		print '</td><td class="right">';
		print $batch->stats_reception['nb'];
		print '</td><td class="right">';
		print $batch->stats_reception['qty'];
		print '</td>';
		print '</tr>';
	} elseif (isModEnabled('supplier_order') && $user->hasRight('fournisseur', 'commande', 'lire')) {
		$nblines++;
		$ret = $batch->loadStatsSupplierOrder($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("bills");
		print '<tr><td>';
		print '<a href="'.dol_buildpath('/product/stock/stats/commande_fournisseur.php', 1).'?id='.$batch->id.'">'.img_object('', 'bill', 'class="pictofixedwidth"').$langs->trans("SuppliersOrders").'</a>';
		print '</td><td class="right">';
		print $batch->stats_supplier_order['customers'];
		print '</td><td class="right">';
		print $batch->stats_supplier_order['nb'];
		print '</td><td class="right">';
		print $batch->stats_supplier_order['qty'];
		print '</td>';
		print '</tr>';
	}

	if (isModEnabled('mrp') && $user->hasRight('mrp', 'read')) {
		$nblines++;
		$ret = $batch->loadStatsMo($socid);
		if ($ret < 0) {
			dol_print_error($db);
		}
		$langs->load("mrp");
		print '<tr><td>';
		print '<a href="'.dol_buildpath('/product/stock/stats/mo.php', 1).'?id='.$batch->id.'">'.img_object('', 'mrp', 'class="pictofixedwidth"').$langs->trans("MO").'</a>';
		print '</td><td class="right">';
		//      print $form->textwithpicto($batch->stats_mo['customers_toconsume'], $langs->trans("ToConsume")); Makes no sense with batch, at this moment we don't know batch number
		print $form->textwithpicto($batch->stats_mo['customers_consumed'], $langs->trans("QtyAlreadyConsumed"));
		//      print $form->textwithpicto($batch->stats_mo['customers_toproduce'], $langs->trans("QtyToProduce")); Makes no sense with batch, at this moment we don't know batch number
		print $form->textwithpicto($batch->stats_mo['customers_produced'], $langs->trans("QtyAlreadyProduced"));
		print '</td><td class="right">';
		//      print $form->textwithpicto($batch->stats_mo['nb_toconsume'], $langs->trans("ToConsume")); Makes no sense with batch, at this moment we don't know batch number
		print $form->textwithpicto($batch->stats_mo['nb_consumed'], $langs->trans("QtyAlreadyConsumed"));
		//      print $form->textwithpicto($batch->stats_mo['nb_toproduce'], $langs->trans("QtyToProduce")); Makes no sense with batch, at this moment we don't know batch number
		print $form->textwithpicto($batch->stats_mo['nb_produced'], $langs->trans("QtyAlreadyProduced"));
		print '</td><td class="right">';
		//      print $form->textwithpicto($batch->stats_mo['qty_toconsume'], $langs->trans("ToConsume")); Makes no sense with batch, at this moment we don't know batch number
		print $form->textwithpicto($batch->stats_mo['qty_consumed'], $langs->trans("QtyAlreadyConsumed"));
		//      print $form->textwithpicto($batch->stats_mo['qty_toproduce'], $langs->trans("QtyToProduce")); Makes no sense with batch, at this moment we don't know batch number
		print $form->textwithpicto($batch->stats_mo['qty_produced'], $langs->trans("QtyAlreadyProduced"));
		print '</td>';
		print '</tr>';
	}

	$parameters = array('socid' => $socid);
	$reshook = $hookmanager->executeHooks('addMoreBatchProductStat', $parameters, $batch, $nblines); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}

	print $hookmanager->resPrint;


	return $nblines++;
}

/**
 *	Return translation label of a unit key.
 *  Function kept for backward compatibility.
 *
 *  @param	string  	$unitscale			Scale of unit: '0', '-3', '6', ...
 *	@param  string		$measuring_style    Style of unit: weight, volume,...
 *	@param	int			$unitid             ID of unit (rowid in llx_c_units table)
 *  @param	int			$use_short_label	1=Use short label ('g' instead of 'gram'). Short labels are not translated.
 *  @param	Translate	$outputlangs		Language object
 *	@return	string	   			         	Unit string
 * 	@see	measuringUnitString() formproduct->selectMeasuringUnits()
 */
function measuring_units_string($unitscale = '', $measuring_style = '', $unitid = 0, $use_short_label = 0, $outputlangs = null)
{
	return measuringUnitString($unitid, $measuring_style, $unitscale, $use_short_label, $outputlangs);
}

/**
 *	Return translation label of a unit key
 *
 *	@param	int			$unitid             ID of unit (rowid in llx_c_units table)
 *	@param  string		$measuring_style    Style of unit: 'weight', 'volume', ..., '' = 'net_measure' for option PRODUCT_ADD_NET_MEASURE
 *  @param	string  	$unitscale			Scale of unit: '0', '-3', '6', ...
 *  @param	int			$use_short_label	1=Use very short label ('g' instead of 'gram'), not translated. 2=Use translated short label.
 *  @param	Translate	$outputlangs		Language object
 *	@return	string|-1	   			        Unit string if OK, -1 if KO
 * 	@see	formproduct->selectMeasuringUnits()
 */
function measuringUnitString($unitid, $measuring_style = '', $unitscale = '', $use_short_label = 0, $outputlangs = null)
{
	global $langs, $db;
	global $measuring_unit_cache;

	if (empty($outputlangs)) {
		$outputlangs = $langs;
	}

	if (empty($measuring_unit_cache[$unitid.'_'.$measuring_style.'_'.$unitscale.'_'.$use_short_label])) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/cunits.class.php';
		$measuringUnits = new CUnits($db);

		if ($measuring_style == '' && $unitscale == '') {
			$arrayforfilter = array(
				't.rowid' => $unitid,
				't.active' => 1
			);
		} elseif ($unitscale !== '') {
			$arrayforfilter = array(
				't.scale' => $unitscale,
				't.unit_type' => $measuring_style,
				't.active' => 1
			);
		} else {
			$arrayforfilter = array(
				't.rowid' => $unitid,
				't.unit_type' => $measuring_style,
				't.active' => 1
			);
		}
		$result = $measuringUnits->fetchAll('', '', 0, 0, $arrayforfilter);

		if ($result < 0) {
			return -1;
		} else {
			if (is_array($measuringUnits->records) && count($measuringUnits->records) > 0) {
				if ($use_short_label == 1) {
					$labeltoreturn = $measuringUnits->records[key($measuringUnits->records)]->short_label;
				} elseif ($use_short_label == 2) {
					$labeltoreturn = $outputlangs->transnoentitiesnoconv(ucfirst($measuringUnits->records[key($measuringUnits->records)]->label).'Short');
				} else {
					$labeltoreturn = $outputlangs->transnoentitiesnoconv($measuringUnits->records[key($measuringUnits->records)]->label);
				}
			} else {
				$labeltoreturn = '';
			}
			$measuring_unit_cache[$unitid.'_'.$measuring_style.'_'.$unitscale.'_'.$use_short_label] = $labeltoreturn;
			return $labeltoreturn;
		}
	} else {
		return $measuring_unit_cache[$unitid.'_'.$measuring_style.'_'.$unitscale.'_'.$use_short_label];
	}
}

/**
 *	Transform a given unit scale into the square of that unit, if known.
 *
 *	@param	int		$unitscale       Unit scale key (-3,-2,-1,0,98,99...)
 *	@return	int	   			         Squared unit key (-6,-4,-2,0,98,99...)
 * 	@see	formproduct->selectMeasuringUnits
 */
function measuring_units_squared($unitscale)
{
	$measuring_units = array();
	$measuring_units[0] = 0; // m -> m3
	$measuring_units[-1] = -2; // dm-> dm2
	$measuring_units[-2] = -4; // cm -> cm2
	$measuring_units[-3] = -6; // mm -> mm2
	$measuring_units[98] = 98; // foot -> foot2
	$measuring_units[99] = 99; // inch -> inch2
	return $measuring_units[$unitscale];
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
	$measuring_units = array();
	$measuring_units[0] = 0; // m -> m2
	$measuring_units[-1] = -3; // dm-> dm3
	$measuring_units[-2] = -6; // cm -> cm3
	$measuring_units[-3] = -9; // mm -> mm3
	$measuring_units[98] = 88; // foot -> foot3
	$measuring_units[99] = 89; // inch -> inch3
	return $measuring_units[$unit];
}
