<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020 Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne             <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Simon TOSSER            <simon@kornog-computing.com>
 * Copyright (C) 2005-2009 Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Cédric Salvador         <csalvador.gpcsolutions.fr>
 * Copyright (C) 2013-2018 Juanjo Menent	       <jmenent@2byte.es>
 * Copyright (C) 2014-2015 Cédric Gross            <c.gross@kreiz-it.fr>
 * Copyright (C) 2015      Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2021	   Gauthier VERDOL         <gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/product/stock/product.php
 *	\ingroup    product stock
 *	\brief      Page to list detailed stock of a product
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productstockentrepot.class.php';
if (isModEnabled('productbatch')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

if (isModEnabled('variants')) {
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttribute.class.php';
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductAttributeValue.class.php';
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
	require_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
}

// Load translation files required by the page
$langs->loadlangs(array('products', 'suppliers', 'orders', 'bills', 'stocks', 'sendings', 'margins'));
if (isModEnabled('productbatch')) {
	$langs->load("productbatch");
}

$backtopage = GETPOST('backtopage', 'alpha');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$stocklimit = GETPOSTFLOAT('seuil_stock_alerte');
$desiredstock = GETPOSTFLOAT('desiredstock');
$cancel = GETPOST('cancel', 'alpha');
$fieldid = GETPOSTISSET("ref") ? 'ref' : 'rowid';
$d_eatby = dol_mktime(0, 0, 0, GETPOSTINT('eatbymonth'), GETPOSTINT('eatbyday'), GETPOSTINT('eatbyyear'));
$d_sellby = dol_mktime(0, 0, 0, GETPOSTINT('sellbymonth'), GETPOSTINT('sellbyday'), GETPOSTINT('sellbyyear'));
$pdluoid = GETPOSTINT('pdluoid');
$batchnumber = GETPOST('batch_number', 'san_alpha');
if (!empty($batchnumber)) {
	$batchnumber = trim($batchnumber);
}
$cost_price = GETPOST('cost_price', 'alpha');

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

$object = new Product($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref);
}

if (empty($id) && !empty($object->id)) {
	$id = $object->id;
}

$modulepart = 'product';

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas) ? $object->canvas : GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('stockproduct', 'card', $canvas);
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('stockproductcard', 'globalcard'));

$error = 0;

$usercanread = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'lire')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'lire')));
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'creer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'creer')));
$usercancreadprice = getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('product', 'product_advance', 'read_prices') : $user->hasRight('product', 'lire');

if ($object->isService()) {
	$label = $langs->trans('Service');
	$usercancreadprice = getDolGlobalString('MAIN_USE_ADVANCED_PERMS') ? $user->hasRight('service', 'service_advance', 'read_prices') : $user->hasRight('service', 'lire');
}

if ($object->id > 0) {
	if ($object->type == $object::TYPE_PRODUCT) {
		restrictedArea($user, 'produit', $object->id, 'product&product', '', '');
	}
	if ($object->type == $object::TYPE_SERVICE) {
		restrictedArea($user, 'service', $object->id, 'product&product', '', '');
	}
} else {
	restrictedArea($user, 'produit|service', $id, 'product&product', '', '', $fieldid);
}


/*
 *	Actions
 */

if ($cancel) {
	$action = '';
}

$parameters = array('id' => $id, 'ref' => $ref, 'objcanvas' => $objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if ($action == 'setcost_price') {
	if ($id) {
		$result = $object->fetch($id);
		$object->cost_price = (float) price2num($cost_price);
		$result = $object->update($object->id, $user);
		if ($result > 0) {
			setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
			$action = '';
		} else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

if ($action == 'addlimitstockwarehouse' && $user->hasRight('produit', 'creer')) {
	$seuil_stock_alerte = GETPOST('seuil_stock_alerte');
	$desiredstock = GETPOST('desiredstock');

	$maj_ok = true;
	if ($seuil_stock_alerte == '') {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("StockLimit")), null, 'errors');
		$maj_ok = false;
	}
	if ($desiredstock == '' || is_array($desiredstock)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DesiredStock")), null, 'errors');
		$maj_ok = false;
	}

	$desiredstock = (float) $desiredstock;

	if ($maj_ok) {
		$pse = new ProductStockEntrepot($db);
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		if ($pse->fetch(0, $id, GETPOSTINT('fk_entrepot')) > 0) {
			// Update
			$pse->seuil_stock_alerte = $seuil_stock_alerte;
			$pse->desiredstock = $desiredstock;
			if ($pse->update($user) > 0) {
				setEventMessages($langs->trans('ProductStockWarehouseUpdated'), null, 'mesgs');
			}
		} else {
			// Create
			$pse->fk_entrepot = GETPOSTINT('fk_entrepot');
			$pse->fk_product  	 	 = $id;
			$pse->seuil_stock_alerte = GETPOST('seuil_stock_alerte');
			$pse->desiredstock  	 = GETPOSTFLOAT('desiredstock');
			if ($pse->create($user) > 0) {
				setEventMessages($langs->trans('ProductStockWarehouseCreated'), null, 'mesgs');
			}
		}
	}

	header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
	exit;
}

if ($action == 'delete_productstockwarehouse' && $user->hasRight('produit', 'creer')) {
	$pse = new ProductStockEntrepot($db);

	$pse->fetch(GETPOSTINT('fk_productstockwarehouse'));
	if ($pse->delete($user) > 0) {
		setEventMessages($langs->trans('ProductStockWarehouseDeleted'), null, 'mesgs');
	}

	$action = '';
}

// Set stock limit
if ($action == 'setseuil_stock_alerte' && $user->hasRight('produit', 'creer')) {
	$object = new Product($db);
	$result = $object->fetch($id);
	$object->seuil_stock_alerte = $stocklimit;
	$result = $object->update($object->id, $user, 0, 'update');
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	//else
	//	setEventMessages($lans->trans("SavedRecordSuccessfully"), null, 'mesgs');
	$action = '';
}

// Set desired stock
if ($action == 'setdesiredstock' && $user->hasRight('produit', 'creer')) {
	$object = new Product($db);
	$result = $object->fetch($id);
	$object->desiredstock = $desiredstock;
	$result = $object->update($object->id, $user, 0, 'update');
	if ($result < 0) {
		setEventMessages($object->error, $object->errors, 'errors');
	}
	$action = '';
}


// Correct stock
if ($action == "correct_stock" && !$cancel) {
	if (!(GETPOSTINT("id_entrepot") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'correction';
	}
	if (!GETPOST("nbpiece")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'correction';
	}

	if (isModEnabled('productbatch')) {
		$object = new Product($db);
		$result = $object->fetch($id);

		if ($object->hasbatch() && !$batchnumber) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
			$error++;
			$action = 'correction';
		}
	}

	if (!$error) {
		$priceunit = price2num(GETPOST("unitprice"));
		$nbpiece = price2num(GETPOST("nbpiece", 'alphanohtml'));
		if (is_numeric($nbpiece) && $nbpiece != 0 && $id) {
			$origin_element = '';
			$origin_id = null;

			if (GETPOSTINT('projectid')) {
				$origin_element = 'project';
				$origin_id = GETPOSTINT('projectid');
			}

			if (empty($object)) {
				$object = new Product($db);
				$result = $object->fetch($id);
			}

			$disablestockchangeforsubproduct = 0;
			if (GETPOST('disablesubproductstockchange')) {
				$disablestockchangeforsubproduct = 1;
			}

			if ($object->hasbatch()) {
				$result = $object->correct_stock_batch(
					$user,
					GETPOSTINT("id_entrepot"),
					$nbpiece,
					GETPOSTINT("mouvement"),
					GETPOST("label", 'alphanohtml'), // label movement
					$priceunit,
					$d_eatby,
					$d_sellby,
					$batchnumber,
					GETPOST('inventorycode', 'alphanohtml'),
					$origin_element,
					$origin_id,
					$disablestockchangeforsubproduct
				); // We do not change value of stock for a correction
			} else {
				$result = $object->correct_stock(
					$user,
					GETPOSTINT("id_entrepot"),
					$nbpiece,
					GETPOSTINT("mouvement"),
					GETPOST("label", 'alphanohtml'),
					$priceunit,
					GETPOST('inventorycode', 'alphanohtml'),
					$origin_element,
					$origin_id,
					$disablestockchangeforsubproduct
				); // We do not change value of stock for a correction
			}

			if ($result > 0) {
				if ($backtopage) {
					header("Location: ".$backtopage);
					exit;
				} else {
					header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$action = 'correction';
			}
		}
	}
}

// Transfer stock from a warehouse to another warehouse
if ($action == "transfert_stock" && !$cancel) {
	if (!(GETPOSTINT("id_entrepot") > 0) || !(GETPOSTINT("id_entrepot_destination") > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (!GETPOSTINT("nbpiece")) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("NumberOfUnit")), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (GETPOSTINT("id_entrepot") == GETPOSTINT("id_entrepot_destination")) {
		setEventMessages($langs->trans("ErrorSrcAndTargetWarehouseMustDiffers"), null, 'errors');
		$error++;
		$action = 'transfert';
	}
	if (isModEnabled('productbatch')) {
		$object = new Product($db);
		$result = $object->fetch($id);

		if ($object->hasbatch() && !$batchnumber) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("batch_number")), null, 'errors');
			$error++;
			$action = 'transfert';
		}
	}

	if (!$error) {
		if ($id) {
			$object = new Product($db);
			$result = $object->fetch($id);

			$db->begin();

			$object->load_stock('novirtual'); // Load array product->stock_warehouse

			// Define value of products moved
			$pricesrc = 0;
			if (isset($object->pmp)) {
				$pricesrc = $object->pmp;
			}
			$pricedest = $pricesrc;

			$nbpiece = price2num(GETPOST("nbpiece", 'alphanohtml'));

			if ($object->hasbatch()) {
				$pdluo = new Productbatch($db);

				if ($pdluoid > 0) {
					$result = $pdluo->fetch($pdluoid);
					if ($result) {
						$srcwarehouseid = $pdluo->warehouseid;
						$batch = $pdluo->batch;
						$eatby = $pdluo->eatby;
						$sellby = $pdluo->sellby;
					} else {
						setEventMessages($pdluo->error, $pdluo->errors, 'errors');
						$error++;
					}
				} else {
					$srcwarehouseid = GETPOSTINT('id_entrepot');
					$batch = $batchnumber;
					$eatby = $d_eatby;
					$sellby = $d_sellby;
				}

				$nbpiece = price2num(GETPOST("nbpiece", 'alphanohtml'));

				if (!$error) {
					// Remove stock
					$result1 = $object->correct_stock_batch(
						$user,
						$srcwarehouseid,
						$nbpiece,
						1,
						GETPOST("label", 'alphanohtml'),
						$pricesrc,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode', 'alphanohtml')
					);
					if ($result1 < 0) {
						$error++;
					}
				}
				if (!$error) {
					// Add stock
					$result2 = $object->correct_stock_batch(
						$user,
						GETPOSTINT("id_entrepot_destination"),
						$nbpiece,
						0,
						GETPOST("label", 'alphanohtml'),
						$pricedest,
						$eatby,
						$sellby,
						$batch,
						GETPOST('inventorycode', 'alphanohtml')
					);
					if ($result2 < 0) {
						$error++;
					}
				}
			} else {
				if (!$error) {
					// Remove stock
					$result1 = $object->correct_stock(
						$user,
						GETPOSTINT("id_entrepot"),
						$nbpiece,
						1,
						GETPOST("label", 'alphanohtml'),
						$pricesrc,
						GETPOST('inventorycode', 'alphanohtml')
					);
					if ($result1 < 0) {
						$error++;
					}
				}
				if (!$error) {
					// Add stock
					$result2 = $object->correct_stock(
						$user,
						GETPOSTINT("id_entrepot_destination"),
						$nbpiece,
						0,
						GETPOST("label", 'alphanohtml'),
						$pricedest,
						GETPOST('inventorycode', 'alphanohtml')
					);
					if ($result2 < 0) {
						$error++;
					}
				}
			}


			if (!$error && $result1 >= 0 && $result2 >= 0) {
				$db->commit();

				if ($backtopage) {
					header("Location: ".$backtopage);
					exit;
				} else {
					header("Location: product.php?id=".$object->id);
					exit;
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
				$action = 'transfert';
			}
		}
	}
}

// Update batch information
if ($action == 'updateline' && GETPOST('save') == $langs->trans("Save")) {
	$pdluo = new Productbatch($db);
	$result = $pdluo->fetch(GETPOSTINT('pdluoid'));

	if ($result > 0) {
		if ($pdluo->id) {
			if ((!GETPOST("sellby")) && (!GETPOST("eatby")) && (!$batchnumber)) {
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("atleast1batchfield")), null, 'errors');
			} else {
				$d_eatby = dol_mktime(0, 0, 0, GETPOSTINT('eatbymonth'), GETPOSTINT('eatbyday'), GETPOSTINT('eatbyyear'));
				$d_sellby = dol_mktime(0, 0, 0, GETPOSTINT('sellbymonth'), GETPOSTINT('sellbyday'), GETPOSTINT('sellbyyear'));
				$pdluo->batch = $batchnumber;
				$pdluo->eatby = $d_eatby;
				$pdluo->sellby = $d_sellby;
				$result = $pdluo->update($user);
				if ($result < 0) {
					setEventMessages($pdluo->error, $pdluo->errors, 'errors');
				}
			}
		} else {
			setEventMessages($langs->trans('BatchInformationNotfound'), null, 'errors');
		}
	} else {
		setEventMessages($pdluo->error, null, 'errors');
	}
	header("Location: product.php?id=".$id);
	exit;
}



/*
 * View
 */

$form = new Form($db);
$formproduct = new FormProduct($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

if ($id > 0 || $ref) {
	$object = new Product($db);
	$result = $object->fetch($id, $ref);

	$variants = $object->hasVariants();

	$object->load_stock();

	$title = $langs->trans('ProductServiceCard');
	$helpurl = '';
	$shortlabel = dol_trunc($object->label, 16);
	if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
		$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('Stock');
		$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
	}
	if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
		$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('Stock');
		$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';
	}

	llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'mod-product page-card_stock_product');

	if (!empty($conf->use_javascript_ajax)) {
		?>
		<script type="text/javascript">
			$(document).ready(function() {
				$(".collapse_batch").click(function() {
					console.log("We click on collapse_batch");
					var id_entrepot = $(this).attr('id').replace('ent', '');

					if($(this).text().indexOf('+') > 0) {
						$(".batch_warehouse" + id_entrepot).show();
						$(this).html('(-)');
						jQuery("#show_all").hide();
						jQuery("#hide_all").show();
					}
					else {
						$(".batch_warehouse" + id_entrepot).hide();
						$(this).html('(+)');
					}

					return false;
				});

				$("#show_all").click(function() {
					console.log("We click on show_all");
					$("[class^=batch_warehouse]").show();
					$("[class^=collapse_batch]").html('(-)');
					jQuery("#show_all").hide();
					jQuery("#hide_all").show();
					return false;
				});

				$("#hide_all").click(function() {
					console.log("We click on hide_all");
					$("[class^=batch_warehouse]").hide();
					$("[class^=collapse_batch]").html('(+)');
					jQuery("#hide_all").hide();
					jQuery("#show_all").show();
					return false;
				});

			});
		</script>
		<?php
	}

	if ($result > 0) {
		$head = product_prepare_head($object);
		$titre = $langs->trans("CardProduct".$object->type);
		$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

		print dol_get_fiche_head($head, 'stock', $titre, -1, $picto);

		dol_htmloutput_events();

		$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';

		$shownav = 1;
		if ($user->socid && !in_array('stock', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
			$shownav = 0;
		}

		dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

		if (!$variants) {
			print '<div class="fichecenter">';

			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Type
			if (isModEnabled("product") && isModEnabled("service")) {
				$typeformat = 'select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");
				print '<tr><td class="">';
				print (!getDolGlobalString('PRODUCT_DENY_CHANGE_PRODUCT_TYPE')) ? $form->editfieldkey("Type", 'fk_product_type', $object->type, $object, 0, $typeformat) : $langs->trans('Type');
				print '</td><td>';
				print $form->editfieldval("Type", 'fk_product_type', $object->type, $object, 0, $typeformat);
				print '</td></tr>';
			}

			if (isModEnabled('productbatch')) {
				print '<tr><td class="">'.$langs->trans("ManageLotSerial").'</td><td>';
				print $object->getLibStatut(0, 2);
				print '</td></tr>';
			}

			// Cost price. Can be used for margin module for option "calculate margin on explicit cost price
			print '<tr><td>';
			$textdesc = $langs->trans("CostPriceDescription");
			$textdesc .= "<br>".$langs->trans("CostPriceUsage");
			$text = $form->textwithpicto($langs->trans("CostPrice"), $textdesc, 1, 'help', '');
			if (!$usercancreadprice) {
				print $form->editfieldkey($text, 'cost_price', '', $object, 0, 'amount:6');
				print '</td><td>';
				print $form->editfieldval($text, 'cost_price', '', $object, 0, 'amount:6');
			} else {
				print $form->editfieldkey($text, 'cost_price', $object->cost_price, $object, $usercancreate, 'amount:6');
				print '</td><td>';
				print $form->editfieldval($text, 'cost_price', $object->cost_price, $object, $usercancreate, 'amount:6');
			}
			print '</td></tr>';



			// AWP
			print '<tr><td class="titlefield">';
			print $form->textwithpicto($langs->trans("AverageUnitPricePMPShort"), $langs->trans("AverageUnitPricePMPDesc"));
			print '</td>';
			print '<td>';
			if ($object->pmp > 0 && $usercancreadprice) {
				print price($object->pmp).' '.$langs->trans("HT");
			}
			print '</td>';
			print '</tr>';

			// Minimum Price
			print '<tr><td>'.$langs->trans("BuyingPriceMin").'</td>';
			print '<td>';
			$product_fourn = new ProductFournisseur($db);
			if ($product_fourn->find_min_price_product_fournisseur($object->id) > 0) {
				if ($product_fourn->product_fourn_price_id > 0 && $usercancreadprice) {
					print $product_fourn->display_price_product_fournisseur();
				} else {
					print $langs->trans("NotDefined");
				}
			}
			print '</td></tr>';

			if (!getDolGlobalString('PRODUIT_MULTIPRICES')) {
				// Price
				print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
				if ($usercancreadprice) {
					if ($object->price_base_type == 'TTC') {
						print price($object->price_ttc).' '.$langs->trans($object->price_base_type);
					} else {
						print price($object->price).' '.$langs->trans($object->price_base_type);
					}
				}
				print '</td></tr>';

				// Price minimum
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				if ($usercancreadprice) {
					if ($object->price_base_type == 'TTC') {
						print price($object->price_min_ttc).' '.$langs->trans($object->price_base_type);
					} else {
						print price($object->price_min).' '.$langs->trans($object->price_base_type);
					}
				}
				print '</td></tr>';
			} else {
				// Price
				print '<tr><td>'.$langs->trans("SellingPrice").'</td><td>';
				print '<span class="opacitymedium">'.$langs->trans("Variable").'</span>';
				print '</td></tr>';

				// Price minimum
				print '<tr><td>'.$langs->trans("MinPrice").'</td><td>';
				print '<span class="opacitymedium">'.$langs->trans("Variable").'</span>';
				print '</td></tr>';
			}

			// Hook formObject
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright"><div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Stock alert threshold
			print '<tr><td>'.$form->editfieldkey($form->textwithpicto($langs->trans("StockLimit"), $langs->trans("StockLimitDesc"), 1), 'seuil_stock_alerte', $object->seuil_stock_alerte, $object, $user->hasRight('produit', 'creer')).'</td><td>';
			print $form->editfieldval("StockLimit", 'seuil_stock_alerte', $object->seuil_stock_alerte, $object, $user->hasRight('produit', 'creer'), 'string');
			print '</td></tr>';

			// Desired stock
			print '<tr><td>'.$form->editfieldkey($form->textwithpicto($langs->trans("DesiredStock"), $langs->trans("DesiredStockDesc"), 1), 'desiredstock', $object->desiredstock, $object, $user->hasRight('produit', 'creer'));
			print '</td><td>';
			print $form->editfieldval("DesiredStock", 'desiredstock', $object->desiredstock, $object, $user->hasRight('produit', 'creer'), 'string');
			print '</td></tr>';

			// Real stock
			$text_stock_options = $langs->trans("RealStockDesc").'<br>';
			$text_stock_options .= $langs->trans("RealStockWillAutomaticallyWhen").'<br>';
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT') || getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE') ? '- '.$langs->trans("DeStockOnShipment").'<br>' : '');
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_VALIDATE_ORDER') ? '- '.$langs->trans("DeStockOnValidateOrder").'<br>' : '');
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_BILL') ? '- '.$langs->trans("DeStockOnBill").'<br>' : '');
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_BILL') ? '- '.$langs->trans("ReStockOnBill").'<br>' : '');
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER') ? '- '.$langs->trans("ReStockOnValidateOrder").'<br>' : '');
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER') ? '- '.$langs->trans("ReStockOnDispatchOrder").'<br>' : '');
			$text_stock_options .= (getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION') || getDolGlobalString('STOCK_CALCULATE_ON_RECEPTION_CLOSE') ? '- '.$langs->trans("StockOnReception").'<br>' : '');
			$parameters = array();
			$reshook = $hookmanager->executeHooks('physicalStockTextStockOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook > 0) {
				$text_stock_options = $hookmanager->resPrint;
			} elseif ($reshook == 0) {
				$text_stock_options .= $hookmanager->resPrint;
			} else {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}

			print '<tr><td>';
			print $form->textwithpicto($langs->trans("PhysicalStock"), $text_stock_options, 1);
			print '</td>';
			print '<td>'.price2num($object->stock_reel, 'MS');
			if ($object->seuil_stock_alerte != '' && ($object->stock_reel < $object->seuil_stock_alerte)) {
				print ' '.img_warning($langs->trans("StockLowerThanLimit", $object->seuil_stock_alerte));
			}

			print ' &nbsp; &nbsp;<a href="'.DOL_URL_ROOT.'/product/stock/stockatdate.php?productid='.$object->id.'">'.$langs->trans("StockAtDate").'</a>';
			print '</td>';
			print '</tr>';

			$stocktheo = price2num($object->stock_theorique, 'MS');

			$found = 0;
			$helpondiff = '<strong>'.$langs->trans("StockDiffPhysicTeoric").':</strong><br>';
			// Number of sales orders running
			if (isModEnabled('order')) {
				if ($found) {
					$helpondiff .= '<br>';
				} else {
					$found = 1;
				}
				$helpondiff .= $langs->trans("ProductQtyInCustomersOrdersRunning").': '.$object->stats_commande['qty'];
				$result = $object->load_stats_commande(0, '0', 1);
				if ($result < 0) {
					dol_print_error($db, $object->error);
				}
				$helpondiff .= ' <span class="opacitymedium">('.$langs->trans("ProductQtyInDraft").': '.$object->stats_commande['qty'].')</span>';
			}

			// Number of product from sales order already sent (partial shipping)
			if (isModEnabled("shipping")) {
				require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
				$filterShipmentStatus = '';
				if (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT')) {
					$filterShipmentStatus = Expedition::STATUS_VALIDATED.','.Expedition::STATUS_CLOSED;
				} elseif (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE')) {
					$filterShipmentStatus = Expedition::STATUS_CLOSED;
				}
				if ($found) {
					$helpondiff .= '<br>';
				} else {
					$found = 1;
				}
				$result = $object->load_stats_sending(0, '2', 1, $filterShipmentStatus);
				$helpondiff .= $langs->trans("ProductQtyInShipmentAlreadySent").': '.$object->stats_expedition['qty'];
			}

			// Number of supplier order running
			if (isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
				if ($found) {
					$helpondiff .= '<br>';
				} else {
					$found = 1;
				}
				$result = $object->load_stats_commande_fournisseur(0, '3,4', 1);
				$helpondiff .= $langs->trans("ProductQtyInSuppliersOrdersRunning").': '.$object->stats_commande_fournisseur['qty'];
				$result = $object->load_stats_commande_fournisseur(0, '0,1,2', 1);
				if ($result < 0) {
					dol_print_error($db, $object->error);
				}
				$helpondiff .= ' <span class="opacitymedium">('.$langs->trans("ProductQtyInDraftOrWaitingApproved").': '.$object->stats_commande_fournisseur['qty'].')</span>';
			}

			// Number of product from supplier order already received (partial receipt)
			if (isModEnabled("supplier_order") || isModEnabled("supplier_invoice")) {
				if ($found) {
					$helpondiff .= '<br>';
				} else {
					$found = 1;
				}
				$helpondiff .= $langs->trans("ProductQtyInSuppliersShipmentAlreadyRecevied").': '.$object->stats_reception['qty'];
			}

			// Number of product in production
			if (isModEnabled('mrp')) {
				if ($found) {
					$helpondiff .= '<br>';
				} else {
					$found = 1;
				}
				$helpondiff .= $langs->trans("ProductQtyToConsumeByMO").': '.$object->stats_mrptoconsume['qty'].'<br>';
				$helpondiff .= $langs->trans("ProductQtyToProduceByMO").': '.$object->stats_mrptoproduce['qty'];
			}
			$parameters = array('found' => &$found, 'id' => $object->id, 'includedraftpoforvirtual' => null);
			$reshook = $hookmanager->executeHooks('virtualStockHelpOnDiff', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook > 0) {
				$helpondiff = $hookmanager->resPrint;
			} elseif ($reshook == 0) {
				$helpondiff .= $hookmanager->resPrint;
			} else {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}


			// Calculating a theoretical value
			print '<tr><td>';
			print $form->textwithpicto($langs->trans("VirtualStock"), $langs->trans("VirtualStockDesc"));
			print '</td>';
			print "<td>";
			//print (empty($stocktheo)?0:$stocktheo);
			print $form->textwithpicto((empty($stocktheo) ? 0 : $stocktheo), $helpondiff);
			if ($object->seuil_stock_alerte != '' && ($object->stock_theorique < $object->seuil_stock_alerte)) {
				print ' '.img_warning($langs->trans("StockLowerThanLimit", $object->seuil_stock_alerte));
			}
			print ' &nbsp; &nbsp;<a href="'.DOL_URL_ROOT.'/product/stock/stockatdate.php?mode=future&productid='.$object->id.'">'.$langs->trans("VirtualStockAtDate").'</a>';
			print '</td>';
			print '</tr>';

			// Last movement
			if ($user->hasRight('stock', 'mouvement', 'lire')) {
				$sql = "SELECT max(m.datem) as datem";
				$sql .= " FROM ".MAIN_DB_PREFIX."stock_mouvement as m";
				$sql .= " WHERE m.fk_product = ".((int) $object->id);
				$resqlbis = $db->query($sql);
				if ($resqlbis) {
					$obj = $db->fetch_object($resqlbis);
					$lastmovementdate = $db->jdate($obj->datem);
				} else {
					dol_print_error($db);
				}
				print '<tr><td class="tdtop">'.$langs->trans("LastMovement").'</td><td>';
				if ($lastmovementdate) {
					print dol_print_date($lastmovementdate, 'dayhour').' ';
					print ' &nbsp; &nbsp; ';
					print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
					print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$object->id.'">'.$langs->trans("FullList").'</a>';
				} else {
					print img_picto($langs->trans("StockMovement"), 'movement', 'class="pictofixedwidth"');
					print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?idproduct='.$object->id.'">'.$langs->trans("None").'</a>';
				}
				print "</td></tr>";
			}

			print "</table>";

			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';
		}

		print dol_get_fiche_end();
	}

	// Correct stock
	if ($action == "correction") {
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stockcorrection.tpl.php';
		print '<br><br>';
	}

	// Transfer of units
	if ($action == "transfert") {
		include DOL_DOCUMENT_ROOT.'/product/stock/tpl/stocktransfer.tpl.php';
		print '<br><br>';
	}
} else {
	dol_print_error();
}


// Actions buttons

$parameters = array();

$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	if (empty($action) && $object->id) {
		print "<div class=\"tabsAction\">\n";

		if ($user->hasRight('stock', 'mouvement', 'creer')) {
			if (!$variants || getDolGlobalString('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT')) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=transfert&token='.newToken().'">'.$langs->trans("TransferStock").'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ActionAvailableOnVariantProductOnly").'">'.$langs->trans("TransferStock").'</a>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CorrectStock").'</a>';
		}

		if ($user->hasRight('stock', 'mouvement', 'creer')) {
			if (!$variants || getDolGlobalString('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT')) {
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=correction&token='.newToken().'">'.$langs->trans("CorrectStock").'</a>';
			} else {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ActionAvailableOnVariantProductOnly").'">'.$langs->trans("CorrectStock").'</a>';
			}
		} else {
			print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("CorrectStock").'</a>';
		}

		print '</div>';
	}
}


if (!$variants || getDolGlobalString('VARIANT_ALLOW_STOCK_MOVEMENT_ON_VARIANT_PARENT')) {
	/*
	 * Stock detail (by warehouse). May go down into batch details.
	 */

	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td colspan="4">'.$langs->trans("Warehouse").'</td>';
	print '<td class="right">'.$langs->trans("NumberOfUnit").'</td>';
	print '<td class="right">'.$form->textwithpicto($langs->trans("AverageUnitPricePMPShort"), $langs->trans("AverageUnitPricePMPDesc")).'</td>';
	print '<td class="right">'.$langs->trans("EstimatedStockValueShort").'</td>';
	print '<td class="right">'.$langs->trans("SellPriceMin").'</td>';
	print '<td class="right">'.$langs->trans("EstimatedStockValueSellShort").'</td>';
	print '<td></td>';
	print '<td></td>';
	print '</tr>';

	if ((isModEnabled('productbatch')) && $object->hasbatch()) {
		$colspan = 3;
		print '<tr class="liste_titre"><td class="minwidth200">';
		if (!empty($conf->use_javascript_ajax)) {
			print '<a id="show_all" href="#" class="hideobject">'.img_picto('', 'folder-open', 'class="paddingright"').$langs->trans("ShowAllLots").'</a>';
			//print ' &nbsp; ';
			print '<a id="hide_all" href="#">'.img_picto('', 'folder', 'class="paddingright"').$langs->trans("HideLots").'</a>';
			//print '&nbsp;'.$form->textwithpicto('', $langs->trans('CollapseBatchDetailHelp'), 1, 'help', '');
		}
		print '</td>';
		print '<td class="right">'.$langs->trans("batch_number").'</td>';
		if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
			$colspan--;
			print '<td class="center width100">'.$langs->trans("SellByDate").'</td>';
		}
		if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
			$colspan--;
			print '<td class="center width100">'.$langs->trans("EatByDate").'</td>';
		}
		print '<td colspan="'.$colspan.'"></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '<td></td>';
		print '</tr>';
	}

	$sql = "SELECT e.rowid, e.ref, e.lieu, e.fk_parent, e.statut as status, ps.reel, ps.rowid as product_stock_id, p.pmp";
	$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
	$sql .= " ".MAIN_DB_PREFIX."product_stock as ps";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = ps.fk_product";
	$sql .= " WHERE ps.reel != 0";
	$sql .= " AND ps.fk_entrepot = e.rowid";
	$sql .= " AND e.entity IN (".getEntity('stock').")";
	$sql .= " AND ps.fk_product = ".((int) $object->id);
	$sql .= " ORDER BY e.ref";

	$entrepotstatic = new Entrepot($db);
	$product_lot_static = new Productlot($db);

	$num = 0;
	$total = 0;
	$totalvalue = $totalvaluesell = 0;
	$totalwithpmp = 0;

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$total = $totalwithpmp;
		$i = 0;
		$var = false;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);

			$entrepotstatic->id = $obj->rowid;
			$entrepotstatic->ref = $obj->ref;
			$entrepotstatic->label = $obj->ref;
			$entrepotstatic->lieu = $obj->lieu;
			$entrepotstatic->fk_parent = $obj->fk_parent;
			$entrepotstatic->statut = $obj->status;
			$entrepotstatic->status = $obj->status;

			$stock_real = price2num($obj->reel, 'MS');
			print '<tr class="oddeven">';

			// Warehouse
			print '<td colspan="4">';
			print $entrepotstatic->getNomUrl(1);
			if (!empty($conf->use_javascript_ajax) && isModEnabled('productbatch') && $object->hasbatch()) {
				print '<a class="collapse_batch marginleftonly" id="ent' . $entrepotstatic->id . '" href="#">';
				print(!getDolGlobalString('STOCK_SHOW_ALL_BATCH_BY_DEFAULT') ? '(+)' : '(-)');
				print '</a>';
			}
			print '</td>';

			print '<td class="right">'.$stock_real.($stock_real < 0 ? ' '.img_warning() : '').'</td>';

			// PMP
			print '<td class="right nowraponall">'.(price2num($object->pmp) ? price2num($object->pmp, 'MU') : '').'</td>';

			// Value purchase
			if ($usercancreadprice) {
				print '<td class="right amount nowraponall">'.(price2num($object->pmp) ? price(price2num($object->pmp * $obj->reel, 'MT')) : '').'</td>';
			} else {
				print '<td class="right amount nowraponall"></td>';
			}

			// Sell price
			$minsellprice = null;
			$maxsellprice = null;
			print '<td class="right nowraponall">';
			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				foreach ($object->multiprices as $priceforlevel) {
					if (is_numeric($priceforlevel)) {
						if (is_null($maxsellprice) || $priceforlevel > $maxsellprice) {
							$maxsellprice = $priceforlevel;
						}
						if (is_null($minsellprice) || $priceforlevel < $minsellprice) {
							$minsellprice = $priceforlevel;
						}
					}
				}
				print '<span class="valignmiddle">';
				if ($usercancreadprice) {
					if ($minsellprice != $maxsellprice) {
						print price(price2num($minsellprice, 'MU'), 1).' - '.price(price2num($maxsellprice, 'MU'), 1);
					} else {
						print price(price2num($minsellprice, 'MU'), 1);
					}
				}
				print '</span>';
				print $form->textwithpicto('', $langs->trans("Variable"));
			} elseif ($usercancreadprice) {
				print price(price2num($object->price, 'MU'), 1);
			}
			print '</td>';

			// Value sell
			print '<td class="right amount nowraponall">';
			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				print '<span class="valignmiddle">';
				if ($usercancreadprice) {
					if ($minsellprice != $maxsellprice) {
						print price(price2num($minsellprice * $obj->reel, 'MT'), 1).' - '.price(price2num($maxsellprice * $obj->reel, 'MT'), 1);
					} else {
						print price(price2num($minsellprice * $obj->reel, 'MT'), 1);
					}
				}
				print '</span>';
				print $form->textwithpicto('', $langs->trans("Variable"));
			} else {
				if ($usercancreadprice) {
					print price(price2num($object->price * $obj->reel, 'MT'), 1);
				}
			}
			print '</td>';
			print '<td></td>';
			print '<td></td>';
			print '</tr>';
			$total += $obj->reel;
			if (price2num($object->pmp)) {
				$totalwithpmp += $obj->reel;
			}
			$totalvalue += ($object->pmp * $obj->reel);
			$totalvaluesell += ($object->price * $obj->reel);
			// Batch Detail
			if ((isModEnabled('productbatch')) && $object->hasbatch()) {
				$details = Productbatch::findAll($db, $obj->product_stock_id, 0, $object->id);
				if ($details < 0) {
					dol_print_error($db);
				}
				foreach ($details as $pdluo) {
					$product_lot_static->id = $pdluo->lotid;
					$product_lot_static->batch = $pdluo->batch;
					$product_lot_static->eatby = $pdluo->eatby;
					$product_lot_static->sellby = $pdluo->sellby;

					if ($action == 'editline' && GETPOSTINT('lineid') == $pdluo->id) { //Current line edit
						print "\n".'<tr>';
						print '<td colspan="9">';
						print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<input type="hidden" name="pdluoid" value="'.$pdluo->id.'"><input type="hidden" name="action" value="updateline"><input type="hidden" name="id" value="'.$id.'"><table class="noborder centpercent"><tr><td width="10%"></td>';
						print '<td class="right" width="10%"><input type="text" name="batch_number" value="'.$pdluo->batch.'"></td>';
						if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
							print '<td class="center" width="10%">';
							print $form->selectDate($pdluo->sellby, 'sellby', 0, 0, 1, '', 1, 0);
							print '</td>';
						}
						if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
							print '<td class="center" width="10%">';
							print $form->selectDate($pdluo->eatby, 'eatby', 0, 0, 1, '', 1, 0);
							print '</td>';
						}
						print '<td class="right" colspan="3">'.$pdluo->qty.($pdluo->qty < 0 ? ' '.img_warning() : '').'</td>';
						print '<td colspan="4"><input type="submit" class="button button-save" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'">';
						print '<input type="submit" class="button button-cancel" id="cancellinebutton" name="Cancel" value="'.$langs->trans("Cancel").'"></td></tr>';
						print '</table>';
						print '</form>';
						print '</td>';
						print '<td></td>';
						print '<td></td>';
						print '</tr>';
					} else {
						print "\n".'<tr style="display:'.(!getDolGlobalString('STOCK_SHOW_ALL_BATCH_BY_DEFAULT') ? 'none' : 'visible').';" class="batch_warehouse'.$entrepotstatic->id.'"><td class="left">';
						print '</td>';
						print '<td class="right nowraponall">';
						if ($product_lot_static->id > 0) {
							print $product_lot_static->getNomUrl(1);
						} else {
							print $product_lot_static->getNomUrl(1, 'nolink');
						}
						print '</td>';
						$colspan = 3;
						if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
							$colspan--;
							print '<td class="center">'.dol_print_date($pdluo->sellby, 'day').'</td>';
						}
						if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
							$colspan--;
							print '<td class="center">'.dol_print_date($pdluo->eatby, 'day').'</td>';
						}
						print '<td class="right" colspan="'.$colspan.'">'.$pdluo->qty.($pdluo->qty < 0 ? ' '.img_warning() : (($pdluo->qty > 1 && $object->status_batch == 2) ? ' '.img_warning($langs->trans('IlligalQtyForSerialNumbers')) : '')).'</td>';
						print '<td colspan="4"></td>';
						print '<td class="center tdoverflowmax125" title="'.dol_escape_htmltag($langs->trans("TransferStock")).'">';
						if ($entrepotstatic->status != $entrepotstatic::STATUS_CLOSED) {
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&id_entrepot='.$entrepotstatic->id.'&action=transfert&pdluoid='.$pdluo->id.'&token='.newToken().'">';
							print img_picto($langs->trans("TransferStock"), 'add', 'class="hideonsmartphone paddingright" style="color: #a69944"');
							print $langs->trans("TransferStock");
							print '</a>';
							// Disabled, because edition of stock content must use the "Correct stock menu".
							// Do not use this, or data will be wrong (bad tracking of movement label, inventory code, ...
							//print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=editline&token='.newToken().'&lineid='.$pdluo->id.'#'.$pdluo->id.'">';
							//print img_edit().'</a>';
						}
						print '</td>';
						print '<td class="center tdoverflowmax125" title="'.dol_escape_htmltag($langs->trans("CorrectStock")).'">';
						if ($entrepotstatic->status != $entrepotstatic::STATUS_CLOSED) {
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&id_entrepot='.$entrepotstatic->id.'&action=correction&pdluoid='.$pdluo->id.'&token='.newToken().'">';
							print img_picto($langs->trans("CorrectStock"), 'add', 'class="hideonsmartphone paddingright" style="color: #a69944"');
							print $langs->trans("CorrectStock");
							print '</a>';
							// Disabled, because edition of stock content must use the "Correct stock menu".
							// Do not use this, or data will be wrong (bad tracking of movement label, inventory code, ...
							//print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&action=editline&token='.newToken().'&lineid='.$pdluo->id.'#'.$pdluo->id.'">';
							//print img_edit().'</a>';
						}
						print '</td>';
						print '</tr>';
					}
				}
			}
			$i++;
		}
	} else {
		dol_print_error($db);
	}

	// Total line
	print '<tr class="liste_total"><td class="right liste_total" colspan="4">'.$langs->trans("Total").':</td>';
	print '<td class="liste_total right">'.price2num($total, 'MS').'</td>';
	print '<td class="liste_total right">';
	if ($usercancreadprice) {
		print($totalwithpmp ? price(price2num($totalvalue / $totalwithpmp, 'MU')) : '&nbsp;'); // This value may have rounding errors
	}
	print '</td>';
	// Value purchase
	print '<td class="liste_total right">';
	if ($usercancreadprice) {
		print $totalvalue ? price(price2num($totalvalue, 'MT'), 1) : '&nbsp;';
	}
	print '</td>';
	print '<td class="liste_total right">';
	if ($num) {
		if ($total) {
			print '<span class="valignmiddle">';
			if (getDolGlobalString('PRODUIT_MULTIPRICES')) {
				print $form->textwithpicto('', $langs->trans("Variable"));
			} elseif ($usercancreadprice) {
				print price($totalvaluesell / $total, 1);
			}
			print '</span>';
		}
	}
	print '</td>';
	// Value to sell
	print '<td class="liste_total right amount">';
	if ($num) {
		print '<span class="valignmiddle">';
		if (!getDolGlobalString('PRODUIT_MULTIPRICES') && $usercancreadprice) {
			print price(price2num($totalvaluesell, 'MT'), 1);
		} else {
			print $form->textwithpicto('', $langs->trans("Variable"));
		}
		print '</span>';
	}
	print '</td>';
	print '<td></td>';
	print '<td></td>';
	print "</tr>";

	print "</table>";
	print '</div>';

	if (getDolGlobalString('STOCK_ALLOW_ADD_LIMIT_STOCK_BY_WAREHOUSE')) {
		print '<br><br>';
		print load_fiche_titre($langs->trans('AddNewProductStockWarehouse'));

		if ($user->hasRight('produit', 'creer')) {
			print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="addlimitstockwarehouse">';
			print '<input type="hidden" name="id" value="'.$id.'">';
		}
		print '<table class="noborder centpercent">';
		if ($user->hasRight('produit', 'creer')) {
			print '<tr class="liste_titre"><td>'.$formproduct->selectWarehouses('', 'fk_entrepot').'</td>';
			print '<td class="right"><input name="seuil_stock_alerte" type="text" placeholder="'.$langs->trans("StockLimit").'" /></td>';
			print '<td class="right"><input name="desiredstock" type="text" placeholder="'.$langs->trans("DesiredStock").'" /></td>';
			print '<td class="right"><input type="submit" value="'.$langs->trans("Save").'" class="button button-save" /></td>';
			print '</tr>';
		} else {
			print '<tr class="liste_titre"><td>'.$langs->trans("Warehouse").'</td>';
			print '<td class="right">'.$langs->trans("StockLimit").'</td>';
			print '<td class="right">'.$langs->trans("DesiredStock").'</td>';
			print '</tr>';
		}

		$pse = new ProductStockEntrepot($db);
		$lines = $pse->fetchAll($id);

		$visibleWarehouseEntities = explode(',', getEntity('stock')); 	// For MultiCompany compatibility

		if (!empty($lines)) {
			$var = false;
			foreach ($lines as $line) {
				$ent = new Entrepot($db);
				$ent->fetch($line['fk_entrepot']);

				if (!isModEnabled("multicompany") || in_array($ent->entity, $visibleWarehouseEntities)) {
					// Display only warehouses from our entity and entities sharing stock with actual entity
					print '<tr class="oddeven"><td>'.$ent->getNomUrl(3).'</td>';
					print '<td class="right">'.$line['seuil_stock_alerte'].'</td>';
					print '<td class="right">'.$line['desiredstock'].'</td>';
					if ($user->hasRight('produit', 'creer')) {
						print '<td class="right"><a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&fk_productstockwarehouse='.$line['id'].'&action=delete_productstockwarehouse&token='.newToken().'">'.img_delete().'</a></td>';
					}
					print '</tr>';
				}
			}
		}

		print "</table>";

		if ($user->hasRight('produit', 'creer')) {
			print '</form>';
		}
	}
} else {
	// List of variants
	include_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination.class.php';
	include_once DOL_DOCUMENT_ROOT.'/variants/class/ProductCombination2ValuePair.class.php';
	$prodstatic = new Product($db);
	$prodcomb = new ProductCombination($db);
	$comb2val = new ProductCombination2ValuePair($db);
	$productCombinations = $prodcomb->fetchAllByFkProductParent($object->id);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="massaction">';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	// load variants
	$title = $langs->trans("ProductCombinations");

	print_barre_liste($title, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0);

	print '<div class="div-table-responsive">'; ?>
	<table class="liste">
		<tr class="liste_titre">
			<td class="liste_titre"><?php echo $langs->trans('Product') ?></td>
			<td class="liste_titre"><?php echo $langs->trans('Combination') ?></td>
			<td class="liste_titre center"><?php echo $langs->trans('OnSell') ?></td>
			<td class="liste_titre center"><?php echo $langs->trans('OnBuy') ?></td>
			<td class="liste_titre right"><?php echo $langs->trans('Stock') ?></td>
			<td class="liste_titre"></td>
		</tr>
		<?php

		if (count($productCombinations)) {
			$stock_total = 0;
			foreach ($productCombinations as $currcomb) {
				$prodstatic->fetch($currcomb->fk_product_child);
				$prodstatic->load_stock();
				$stock_total += $prodstatic->stock_reel; ?>
				<tr class="oddeven">
					<td><?php echo $prodstatic->getNomUrl(1) ?></td>
					<td>
						<?php

						$productCombination2ValuePairs = $comb2val->fetchByFkCombination($currcomb->id);
						$iMax = count($productCombination2ValuePairs);

						for ($i = 0; $i < $iMax; $i++) {
							echo dol_htmlentities($productCombination2ValuePairs[$i]);

							if ($i !== ($iMax - 1)) {
								echo ', ';
							}
						} ?>
					</td>
					<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 0) ?></td>
					<td style="text-align: center;"><?php echo $prodstatic->getLibStatut(2, 1) ?></td>
					<td class="right"><?php echo $prodstatic->stock_reel ?></td>
					<td class="right">
						<a class="paddingleft paddingright editfielda" href="<?php echo dol_buildpath('/product/stock/product.php?id='.$currcomb->fk_product_child, 2) ?>"><?php echo img_edit() ?></a>
					</td>
					<?php
					?>
				</tr>
				<?php
			}

			print '<tr class="liste_total">';
			print '<td colspan="4" class="left">'.$langs->trans("Total").'</td>';
			print '<td class="right">'.$stock_total.'</td>';
			print '<td></td>';
			print '</tr>';
		} else {
			print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
		} ?>
	</table>

	<?php
	print '</div>';

	print '</form>';
}

// End of page
llxFooter();
$db->close();
