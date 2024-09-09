<?php
/* Copyright (C) 2001-2007  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005       Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2018  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2006       Andre Cianfarani        <acianfa@free.fr>
 * Copyright (C) 2011-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2023		Benjamin Falière		<benjamin.faliere@altairis.fr>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 */

/**
 *  \file       htdocs/product/composition/card.php
 *  \ingroup    product
 *  \brief      Page of product file
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('bills', 'products', 'stocks'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$key = GETPOST('key');
$parent = GETPOST('parent');

// Security check
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('productcompositioncard', 'globalcard'));

$object = new Product($db);
$objectid = 0;
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
	$objectid = $object->id;
	$id = $object->id;
}

$result = restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	}
	if ($object->type == $object::TYPE_SERVICE) {
		restrictedArea($user, 'service', $object->id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'produit|service', $fieldvalue, 'product&product', '', '', $fieldtype);
}
$usercanread = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'lire')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'lire')));
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'creer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'creer')));
$usercandelete = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'supprimer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'supprimer')));


/*
 * Actions
 */

if ($cancel) {
	$action = '';
}

$reshook = $hookmanager->executeHooks('doActions', [], $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Add subproduct to product
	if ($action == 'add_prod' && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
		$error = 0;
		$maxprod = GETPOSTINT("max_prod");

		for ($i = 0; $i < $maxprod; $i++) {
			$qty = price2num(GETPOST("prod_qty_" . $i, 'alpha'), 'MS');
			if ($qty > 0) {
				if ($object->add_sousproduit($id, GETPOSTINT("prod_id_" . $i), $qty, GETPOSTINT("prod_incdec_" . $i)) > 0) {
					//var_dump($i.' '.GETPOST("prod_id_".$i, 'int'), $qty, GETPOST("prod_incdec_".$i, 'int'));
					$action = 'edit';
				} else {
					$error++;
					$action = 're-edit';
					if ($object->error == "isFatherOfThis") {
						setEventMessages($langs->trans("ErrorAssociationIsFatherOfThis"), null, 'errors');
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}
			} else {
				if ($object->del_sousproduit($id, GETPOSTINT("prod_id_" . $i)) > 0) {
					$action = 'edit';
				} else {
					$error++;
					$action = 're-edit';
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}

		if (!$error) {
			header("Location: " . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
			exit;
		}
	} elseif ($action === 'save_composed_product') {
		$TProduct = GETPOST('TProduct', 'array');
		if (!empty($TProduct)) {
			foreach ($TProduct as $id_product => $row) {
				if ($row['qty'] > 0) {
					$object->update_sousproduit($id, $id_product, $row['qty'], isset($row['incdec']) ? 1 : 0);
				} else {
					$object->del_sousproduit($id, $id_product);
				}
			}
			setEventMessages('RecordSaved', null);
		}
		$action = '';
		header("Location: " . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
		exit;
	}
}

/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
$product_fourn = new ProductFournisseur($db);
$productstatic = new Product($db);

// action recherche des produits par mot-cle et/ou par categorie
if ($action == 'search') {
	$current_lang = $langs->getDefaultLang();

	$sql = 'SELECT DISTINCT p.rowid, p.ref, p.label, p.fk_product_type as type, p.barcode, p.price, p.price_ttc, p.price_base_type, p.entity,';
	$sql .= ' p.fk_product_type, p.tms as datem, p.tobatch';
	$sql .= ', p.tosell as status, p.tobuy as status_buy';
	if (getDolGlobalInt('MAIN_MULTILANGS')) {
		$sql .= ', pl.label as labelm, pl.description as descriptionm';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	$sql .= ' FROM '.MAIN_DB_PREFIX.'product as p';
	$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'categorie_product as cp ON p.rowid = cp.fk_product';
	if (getDolGlobalInt('MAIN_MULTILANGS')) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lang as pl ON pl.fk_product = p.rowid AND lang='".($current_lang)."'";
	}
	$sql .= ' WHERE p.entity IN ('.getEntity('product').')';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object); // Note that $action and $object may have been modified by hook
	$sql .= $hookmanager->resPrint;

	if ($key != "") {
		// For natural search
		$params = array('p.ref', 'p.label', 'p.description', 'p.note');
		// multilang
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			$params[] = 'pl.label';
			$params[] = 'pl.description';
			$params[] = 'pl.note';
		}
		if (isModEnabled('barcode')) {
			$params[] = 'p.barcode';
		}
		$sql .= natural_search($params, $key);
	}
	if (isModEnabled('category') && !empty($parent) && $parent != -1) {
		$sql .= " AND cp.fk_categorie ='".$db->escape($parent)."'";
	}
	$sql .= " ORDER BY p.ref ASC";

	$resql = $db->query($sql);
}

$title = $langs->trans('ProductServiceCard');
$help_url = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('AssociatedProducts');
	$help_url = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('AssociatedProducts');
	$help_url = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios|DE:Modul_Leistungen';
}

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-product page-composition_card');

$head = product_prepare_head($object);

$titre = $langs->trans("CardProduct".$object->type);
$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

print dol_get_fiche_head($head, 'subproduct', $titre, -1, $picto);


if ($id > 0 || !empty($ref)) {
	/*
	 * Product card
	 */
	if ($user->hasRight('produit', 'lire') || $user->hasRight('service', 'lire')) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';

		$shownav = 1;
		if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', '');

		if ($object->type != Product::TYPE_SERVICE || getDolGlobalString('STOCK_SUPPORTS_SERVICES') || !getDolGlobalString('PRODUIT_MULTIPRICES')) {
			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent tableforfield">';

			// Type
			if (isModEnabled("product") && isModEnabled("service")) {
				$typeformat = 'select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");
				print '<tr><td class="titlefield">';
				print (!getDolGlobalString('PRODUCT_DENY_CHANGE_PRODUCT_TYPE')) ? $form->editfieldkey("Type", 'fk_product_type', $object->type, $object, $usercancreate, $typeformat) : $langs->trans('Type');
				print '</td><td>';
				print $form->editfieldval("Type", 'fk_product_type', $object->type, $object, $usercancreate, $typeformat);
				print '</td></tr>';
			}

			print '</table>';

			print '</div><div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent tableforfield">';

			// Nature
			if ($object->type != Product::TYPE_SERVICE) {
				if (!getDolGlobalString('PRODUCT_DISABLE_NATURE')) {
					print '<tr><td>'.$form->textwithpicto($langs->trans("NatureOfProductShort"), $langs->trans("NatureOfProductDesc")).'</td><td>';
					print $object->getLibFinished();
					//print $formproduct->selectProductNature('finished', $object->finished);
					print '</td></tr>';
				}
			}

			if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
				// Price
				print '<tr><td class="titlefield">'.$langs->trans("SellingPrice").'</td><td>';
				if ($object->price_base_type == 'TTC') {
					print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
				} else {
					print price($object->price).' '.$langs->trans($object->price_base_type ? $object->price_base_type : 'HT');
				}
				print '</td></tr>';

				// Price minimum
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($object->price_base_type == 'TTC') {
					print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
				} else {
					print price($object->price_min).' '.$langs->trans($object->price_base_type ? $object->price_base_type : 'HT');
				}
				print '</td></tr>';
			}

			print '</table>';
			print '</div>';
			print '</div>';
		}

		print dol_get_fiche_end();


		print '<br><br>';

		$prodsfather = $object->getFather(); // Parent Products
		$object->get_sousproduits_arbo(); // Load $object->sousprods
		$parent_label = $object->label;
		$prods_arbo = $object->get_arbo_each_prod();

		$tmpid = $id;
		if (!empty($conf->use_javascript_ajax)) {
			$nboflines = $prods_arbo;
			$table_element_line='product_association';

			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}
		$id = $tmpid;

		$nbofsubsubproducts = count($prods_arbo); // This include sub sub product into nb
		$prodschild = $object->getChildsArbo($id, 1);
		$nbofsubproducts = count($prodschild); // This include only first level of children


		print '<div class="fichecenter">';

		print load_fiche_titre($langs->trans("ProductParentList"), '', '');

		print '<table class="liste">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('ParentProducts').'</td>';
		print '<td>'.$langs->trans('Label').'</td>';
		print '<td class="right">'.$langs->trans('Qty').'</td>';
		print '</td>';
		if (count($prodsfather) > 0) {
			foreach ($prodsfather as $value) {
				$idprod = $value["id"];
				$productstatic->id = $idprod; // $value["id"];
				$productstatic->type = $value["fk_product_type"];
				$productstatic->ref = $value['ref'];
				$productstatic->label = $value['label'];
				$productstatic->entity = $value['entity'];
				$productstatic->status = $value['status'];
				$productstatic->status_buy = $value['status_buy'];

				print '<tr class="oddeven">';
				print '<td>'.$productstatic->getNomUrl(1, 'composition').'</td>';
				print '<td>'.dol_escape_htmltag($productstatic->label).'</td>';
				print '<td class="right">'.dol_escape_htmltag($value['qty']).'</td>';
				print '</tr>';
			}
		} else {
			print '<tr class="oddeven">';
			print '<td colspan="3"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}
		print '</table>';
		print '</div>';

		print '<br>'."\n";


		print '<div class="fichecenter">';

		$atleastonenotdefined = 0;
		print load_fiche_titre($langs->trans("ProductAssociationList"), '', '');

		print '<form name="formComposedProduct" action="'.$_SERVER['PHP_SELF'].'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'" />';
		print '<input type="hidden" name="action" value="save_composed_product" />';
		print '<input type="hidden" name="id" value="'.$id.'" />';

		print '<table id="tablelines" class="ui-sortable liste nobottom">';

		print '<tr class="liste_titre nodrag nodrop">';
		// Rank
		print '<td>'.$langs->trans('Position').'</td>';
		// Product ref
		print '<td>'.$langs->trans('ComposedProduct').'</td>';
		// Product label
		print '<td>'.$langs->trans('Label').'</td>';
		// Min supplier price
		print '<td class="right" colspan="2">'.$langs->trans('MinSupplierPrice').'</td>';
		// Min customer price
		print '<td class="right" colspan="2">'.$langs->trans('MinCustomerPrice').'</td>';
		// Stock
		if (isModEnabled('stock')) {
			print '<td class="right">'.$langs->trans('Stock').'</td>';
		}
		// Hook fields
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		// Qty in kit
		print '<td class="right">'.$langs->trans('Qty').'</td>';
		// Stoc inc/dev
		print '<td class="center">'.$langs->trans('ComposedProductIncDecStock').'</td>';
		// Move
		print '<td class="linecolmove" style="width: 10px"></td>';
		print '</tr>'."\n";

		$totalsell = 0;
		$total = 0;
		if (count($prods_arbo)) {
			foreach ($prods_arbo as $value) {
				$productstatic->fetch($value['id']);

				if ($value['level'] <= 1) {
					print '<tr id="'.$object->sousprods[$parent_label][$value['id']][6].'" class="drag drop oddeven level1">';

					// Rank
					print '<td>'.$object->sousprods[$parent_label][$value['id']][7].'</td>';

					$notdefined = 0;
					$nb_of_subproduct = $value['nb'];

					// Product ref
					print '<td>'.$productstatic->getNomUrl(1, 'composition').'</td>';

					// Product label
					print '<td title="'.dol_escape_htmltag($productstatic->label).'" class="tdoverflowmax150">'.dol_escape_htmltag($productstatic->label).'</td>';

					// Best buying price
					print '<td class="right">';
					if ($product_fourn->find_min_price_product_fournisseur($productstatic->id) > 0) {
						print $langs->trans("BuyingPriceMinShort").': ';
						if ($product_fourn->product_fourn_price_id > 0) {
							print $product_fourn->display_price_product_fournisseur(0, 0);
						} else {
							print $langs->trans("NotDefined");
							$notdefined++;
							$atleastonenotdefined++;
						}
					}
					print '</td>';

					// For avoid a non-numeric value
					$fourn_unitprice = (!empty($product_fourn->fourn_unitprice) ? $product_fourn->fourn_unitprice : 0);
					$fourn_remise_percent = (!empty($product_fourn->fourn_remise_percent) ? $product_fourn->fourn_remise_percent : 0);
					$fourn_remise = (!empty($product_fourn->fourn_remise) ? $product_fourn->fourn_remise : 0);

					$unitline = price2num(($fourn_unitprice * (1 - ($fourn_remise_percent / 100)) - $fourn_remise), 'MU');
					$totalline = price2num($value['nb'] * ($fourn_unitprice * (1 - ($fourn_remise_percent / 100)) - $fourn_remise), 'MT');
					$total +=  $totalline;

					print '<td class="right nowraponall">';
					print($notdefined ? '' : ($value['nb'] > 1 ? $value['nb'].'x ' : '').'<span class="amount">'.price($unitline, 0, '', 0, 0, -1, $conf->currency)).'</span>';
					print '</td>';

					// Best selling price
					$pricesell = $productstatic->price;
					if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
						$pricesell = 'Variable';
					} else {
						$totallinesell = price2num($value['nb'] * ($pricesell), 'MT');
						$totalsell += $totallinesell;
					}
					print '<td class="right" colspan="2">';
					print($notdefined ? '' : ($value['nb'] > 1 ? $value['nb'].'x ' : ''));
					if (is_numeric($pricesell)) {
						print '<span class="amount">'.price($pricesell, 0, '', 0, 0, -1, $conf->currency).'</span>';
					} else {
						print '<span class="opacitymedium">'.$langs->trans($pricesell).'</span>';
					}
					print '</td>';

					// Stock
					if (isModEnabled('stock')) {
						print '<td class="right">'.$value['stock'].'</td>'; // Real stock
					}

					// Hook fields
					$parameters = array();
					$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters, $productstatic); // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;

					// Qty + IncDec
					if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
						print '<td class="center"><input type="text" value="'.$nb_of_subproduct.'" name="TProduct['.$productstatic->id.'][qty]" class="right width40" /></td>';
						print '<td class="center"><input type="checkbox" name="TProduct['.$productstatic->id.'][incdec]" value="1" '.($value['incdec'] == 1 ? 'checked' : '').' /></td>';
					} else {
						print '<td>'.$nb_of_subproduct.'</td>';
						print '<td>'.($value['incdec'] == 1 ? 'x' : '').'</td>';
					}

					// Move action
					print '<td class="linecolmove tdlineupdown center"></td>';

					print '</tr>'."\n";
				} else {
					$hide = '';
					if (!getDolGlobalString('PRODUCT_SHOW_SUB_SUB_PRODUCTS')) {
						$hide = ' hideobject'; // By default, we do not show this. It makes screen very difficult to understand
					}

					print '<tr class="oddeven'.$hide.'" id="sub-'.$value['id_parent'].'" data-ignoreidfordnd=1>';

					//$productstatic->ref=$value['label'];
					$productstatic->ref = $value['ref'];

					// Rankd
					print '<td></td>';

					// Product ref
					print '<td>';
					for ($i = 0; $i < $value['level']; $i++) {
						print ' &nbsp; &nbsp; '; // Add indentation
					}
					print $productstatic->getNomUrl(1, 'composition');
					print '</td>';

					// Product label
					print '<td>'.dol_escape_htmltag($productstatic->label).'</td>';

					// Best buying price
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';
					// Best selling price
					print '<td>&nbsp;</td>';
					print '<td>&nbsp;</td>';

					// Stock
					if (isModEnabled('stock')) {
						print '<td></td>'; // Real stock
					}

					// Hook fields
					$parameters = array();
					$reshook=$hookmanager->executeHooks('printFieldListValue', $parameters, $productstatic); // Note that $action and $object may have been modified by hook
					print $hookmanager->resPrint;

					// Qty in kit
					print '<td class="right">'.dol_escape_htmltag((string) $value['nb']).'</td>';

					// Inc/dec
					print '<td>&nbsp;</td>';

					// Action move
					print '<td>&nbsp;</td>';

					print '</tr>'."\n";
				}
			}


			// Total

			print '<tr class="liste_total">';

			// Rank
			print '<td></td>';

			// Product ref
			print '<td class="liste_total"></td>';

			// Product label
			print '<td class="liste_total"></td>';

			// Minimum buying price
			print '<td class="liste_total right">';
			print $langs->trans("TotalBuyingPriceMinShort");
			print '</td>';

			print '<td class="liste_total right">';
			if ($atleastonenotdefined) {
				print $langs->trans("Unknown").' ('.$langs->trans("SomeSubProductHaveNoPrices").')';
			}
			print($atleastonenotdefined ? '' : price($total, 0, '', 0, 0, -1, $conf->currency));
			print '</td>';

			// Minimum selling price
			print '<td class="liste_total right">';
			print $langs->trans("TotalSellingPriceMinShort");
			print '</td>';

			print '<td class="liste_total right">';
			if ($atleastonenotdefined) {
				print $langs->trans("Unknown").' ('.$langs->trans("SomeSubProductHaveNoPrices").')';
			}
			print($atleastonenotdefined ? '' : price($totalsell, 0, '', 0, 0, -1, $conf->currency));
			print '</td>';

			// Stock
			if (isModEnabled('stock')) {
				print '<td class="liste_total right">&nbsp;</td>';
			}

			print '<td></td>';

			print '<td class="center">';
			if ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer')) {
				print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
			}
			print '</td>';

			print '<td></td>';

			print '</tr>'."\n";
		} else {
			$colspan = 10;
			if (isModEnabled('stock')) {
				$colspan++;
			}

			print '<tr class="oddeven">';
			print '<td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td>';
			print '</tr>';
		}

		print '</table>';

		/*if($user->rights->produit->creer || $user->hasRight('service', 'creer')) {
			print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
		}*/

		print '</form>';
		print '</div>';



		// Form with product to add
		if ((empty($action) || $action == 'view' || $action == 'edit' || $action == 'search' || $action == 're-edit') && ($user->hasRight('produit', 'creer') || $user->hasRight('service', 'creer'))) {
			print '<br>';

			$rowspan = 1;
			if (isModEnabled('category')) {
				$rowspan++;
			}

			print load_fiche_titre($langs->trans("ProductToAddSearch"), '', '');
			print '<form action="'.DOL_URL_ROOT.'/product/composition/card.php?id='.$id.'" method="POST">';
			print '<input type="hidden" name="action" value="search">';
			print '<input type="hidden" name="id" value="'.$id.'">';
			print '<div class="inline-block">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print $langs->trans("KeywordFilter").': ';
			print '<input type="text" name="key" value="'.$key.'"> &nbsp; ';
			print '</div>';
			if (isModEnabled('category')) {
				require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
				print '<div class="inline-block">'.$langs->trans("CategoryFilter").': ';
				print $form->select_all_categories(Categorie::TYPE_PRODUCT, $parent, 'parent').' &nbsp; </div>';
				print ajax_combobox('parent');
			}
			print '<div class="inline-block">';
			print '<input type="submit" class="button small" value="'.$langs->trans("Search").'">';
			print '</div>';
			print '</form>';
		}


		// List of products
		if ($action == 'search') {
			print '<br>';
			print '<form action="'.DOL_URL_ROOT.'/product/composition/card.php?id='.$id.'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="add_prod">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print '<table class="noborder centpercent">';
			print '<tr class="liste_titre">';
			print '<th class="liste_titre">'.$langs->trans("ComposedProduct").'</td>';
			print '<th class="liste_titre">'.$langs->trans("Label").'</td>';
			//print '<th class="liste_titre center">'.$langs->trans("IsInPackage").'</td>';
			print '<th class="liste_titre right">'.$langs->trans("Qty").'</td>';
			print '<th class="center">'.$langs->trans('ComposedProductIncDecStock').'</th>';
			print '</tr>';
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0;

				if ($num == 0) {
					print '<tr><td colspan="4">'.$langs->trans("NoMatchFound").'</td></tr>';
				}

				$MAX = 100;

				while ($i < min($num, $MAX)) {
					$objp = $db->fetch_object($resql);
					if ($objp->rowid != $id) {
						// check if a product is not already a parent product of this one
						$prod_arbo = new Product($db);
						$prod_arbo->id = $objp->rowid;
						// This type is not supported (not required to have virtual products working).
						if (getDolGlobalString('PRODUCT_USE_DEPRECATED_ASSEMBLY_AND_STOCK_KIT_TYPE')) {
							if ($prod_arbo->type == 2 || $prod_arbo->type == 3) {
								$is_pere = 0;
								$prod_arbo->get_sousproduits_arbo();
								// associations sousproduits
								$prods_arbo = $prod_arbo->get_arbo_each_prod();
								if (count($prods_arbo) > 0) {
									foreach ($prods_arbo as $key => $value) {
										if ($value[1] == $id) {
											$is_pere = 1;
										}
									}
								}
								if ($is_pere == 1) {
									$i++;
									continue;
								}
							}
						}

						print "\n";
						print '<tr class="oddeven">';

						$productstatic->id = $objp->rowid;
						$productstatic->ref = $objp->ref;
						$productstatic->label = $objp->label;
						$productstatic->type = $objp->type;
						$productstatic->entity = $objp->entity;
						$productstatic->status = $objp->status;
						$productstatic->status_buy = $objp->status_buy;
						$productstatic->status_batch = $objp->tobatch;

						print '<td>'.$productstatic->getNomUrl(1, '', 24).'</td>';
						$labeltoshow = $objp->label;
						if (getDolGlobalInt('MAIN_MULTILANGS') && !empty($objp->labelm)) {
							$labeltoshow = $objp->labelm;
						}

						print '<td>'.$labeltoshow.'</td>';


						if ($object->is_sousproduit($id, $objp->rowid)) {
							//$addchecked = ' checked';
							$qty = $object->is_sousproduit_qty;
							$incdec = $object->is_sousproduit_incdec;
						} else {
							//$addchecked = '';
							$qty = 0;
							$incdec = 0;
						}
						// Contained into package
						/*print '<td class="center"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'">';
						print '<input type="checkbox" '.$addchecked.'name="prod_id_chk'.$i.'" value="'.$objp->rowid.'"></td>';*/
						// Qty
						print '<td class="right"><input type="hidden" name="prod_id_'.$i.'" value="'.$objp->rowid.'"><input type="text" size="2" name="prod_qty_'.$i.'" value="'.($qty ? $qty : '').'"></td>';

						// Inc Dec
						print '<td class="center">';
						if ($qty) {
							print '<input type="checkbox" name="prod_incdec_'.$i.'" value="1" '.($incdec ? 'checked' : '').'>';
						} else {
							// TODO Hide field and show it when setting a qty
							print '<input type="checkbox" name="prod_incdec_'.$i.'" value="1" checked>';
							//print '<input type="checkbox" disabled name="prod_incdec_'.$i.'" value="1" checked>';
						}
						print '</td>';

						print '</tr>';
					}
					$i++;
				}
				if ($num > $MAX) {
					print '<tr class="oddeven">';
					print '<td><span class="opacitymedium">'.$langs->trans("More").'...</span></td>';
					print '<td></td>';
					print '<td></td>';
					print '<td></td>';
					print '</tr>';
				}
			} else {
				dol_print_error($db);
			}
			print '</table>';
			print '<input type="hidden" name="max_prod" value="'.$i.'">';

			if ($num > 0) {
				print '<div class="center">';
				print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Add").'/'.$langs->trans("Update").'">';
				print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';
			}

			print '</form>';
		}
	}
}

// End of page
llxFooter();
$db->close();
