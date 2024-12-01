<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2021 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016      Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2019-2024	Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2019      Tim Otte			    <otte@meuser.it>
 * Copyright (C) 2020      Pierre Ardoin        <mapiolca@me.com>
 * Copyright (C) 2023	   Joachim Kueter		<git-jk@bloxera.com>
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
 *  \file       htdocs/product/price_suppliers.php
 *  \ingroup    product
 *  \brief      Page of tab suppliers for products
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_expression.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
if (isModEnabled('barcode')) {
	dol_include_once('/core/class/html.formbarcode.class.php');
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('products', 'suppliers', 'bills', 'margins', 'stocks'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$rowid = GETPOSTINT('rowid');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'pricesuppliercard';

$socid = GETPOSTINT('socid');
$cost_price = GETPOSTFLOAT('cost_price');
$pmp = GETPOSTFLOAT('pmp');

$backtopage = GETPOST('backtopage', 'alpha');
$error = 0;

$extrafields = new ExtraFields($db);

// If socid provided by ajax company selector
if (GETPOSTINT('search_fourn_id')) {
	$_GET['id_fourn'] = GETPOSTINT('search_fourn_id');	// Keep set to $_GET an $_POST. Used later.
	$_POST['id_fourn'] = GETPOSTINT('search_fourn_id');	// Keep set to $_GET an $_POST. Used later.
}

// Security check
$fieldvalue = (!empty($id) ? $id : (!empty($ref) ? $ref : ''));
$fieldtype = (!empty($ref) ? 'ref' : 'rowid');
if ($user->socid) {
	$socid = $user->socid;
}

if (!$user->hasRight('fournisseur', 'lire') && (!isModEnabled('margin') && !$user->hasRight("margin", "liretous"))) {
	accessforbidden();
}

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTINT("page") ? GETPOSTINT("page") : 0;
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "s.nom";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('pricesuppliercard', 'globalcard'));

$object = new ProductFournisseur($db);
$prod = new Product($db);
if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
	$prod->fetch($id, $ref);
}

$usercanread = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'lire')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'lire')));
$usercancreate = (($object->type == Product::TYPE_PRODUCT && $user->hasRight('produit', 'creer')) || ($object->type == Product::TYPE_SERVICE && $user->hasRight('service', 'creer')));

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


/*
 * Actions
 */

if ($cancel) {
	$action = '';
}

$parameters = array('socid'=>$socid, 'id_prod'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($action == 'setcost_price' && $usercancreate) {
		if ($id) {
			$result = $object->fetch($id);
			//Need dol_clone methode 1 (same object class) because update product use hasbatch method on oldcopy
			$object->oldcopy = dol_clone($object, 1);
			$object->cost_price = $cost_price;
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
	if ($action == 'setpmp' && $usercancreate) {
		if ($id) {
			$result = $object->fetch($id);
			$object->pmp = $pmp;
			$sql = "UPDATE ".MAIN_DB_PREFIX."product SET pmp = ".((float) $object->pmp)." WHERE rowid = ".((int) $id);
			$resql = $db->query($sql);
			//$result = $object->update($object->id, $user);
			if ($resql) {
				setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
				$action = '';
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'confirm_remove_pf' && $usercancreate) {
		if ($rowid) {	// id of product supplier price to remove
			$action = '';
			$result = $object->remove_product_fournisseur_price($rowid);
			if ($result > 0) {
				$db->query("DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields WHERE fk_object = ".((int) $rowid));
				setEventMessages($langs->trans("PriceRemoved"), null, 'mesgs');
			} else {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == 'save_price' && $usercancreate) {
		$ref_fourn_price_id = GETPOSTINT('ref_fourn_price_id');
		$id_fourn = GETPOST("id_fourn");
		if (empty($id_fourn)) {
			$id_fourn = GETPOST("search_id_fourn");
		}
		$ref_fourn = GETPOST("ref_fourn");
		if (empty($ref_fourn)) {
			$ref_fourn = GETPOST("search_ref_fourn");
		}
		$ref_fourn_old = GETPOST("ref_fourn_old");
		if (empty($ref_fourn_old)) {
			$ref_fourn_old = $ref_fourn;
		}
		$quantity = price2num(GETPOST("qty", 'alphanohtml'), 'MS');
		$remise_percent = price2num(GETPOST('remise_percent', 'alpha'));

		$npr = preg_match('/\*/', GETPOST('tva_tx', 'alpha')) ? 1 : 0;
		$tva_tx = str_replace('*', '', GETPOST('tva_tx', 'alpha'));
		if (!preg_match('/\((.*)\)/', $tva_tx)) {
			$tva_tx = price2num($tva_tx);
		}

		$price_expression = GETPOSTINT('eid') ? GETPOSTINT('eid') : ''; // Discard expression if not in expression mode
		$delivery_time_days = GETPOSTINT('delivery_time_days') ? GETPOSTINT('delivery_time_days') : '';
		$supplier_reputation = GETPOST('supplier_reputation');
		$supplier_description = GETPOST('supplier_description', 'restricthtml');
		$barcode = GETPOST('barcode', 'alpha');
		$fk_barcode_type = GETPOSTINT('fk_barcode_type');
		$packaging = price2num(GETPOST("packaging", 'alphanohtml'), 'MS');

		if ($tva_tx == '') {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("VATRateForSupplierProduct")), null, 'errors');
		}
		if (!is_numeric($tva_tx)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldMustBeANumeric", $langs->transnoentities("VATRateForSupplierProduct")), null, 'errors');
		}
		if (empty($quantity)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Qty")), null, 'errors');
		}
		if (empty($ref_fourn)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("RefSupplier")), null, 'errors');
		}
		if ($id_fourn <= 0) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Supplier")), null, 'errors');
		}
		if (price2num(GETPOST("price")) < 0 || GETPOST("price") == '') {
			if ($price_expression === '') {	// Return error of missing price only if price_expression not set
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Price")), null, 'errors');
			} else {
				$_POST["price"] = 0;
			}
		}
		if (isModEnabled("multicurrency")) {
			if (!GETPOST("multicurrency_code")) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Currency")), null, 'errors');
			}
			if (price2num(GETPOST("multicurrency_tx")) <= 0 || GETPOST("multicurrency_tx") == '') {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("CurrencyRate")), null, 'errors');
			}
			if (price2num(GETPOST("multicurrency_price")) < 0 || GETPOST("multicurrency_price") == '') {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("PriceCurrency")), null, 'errors');
			}
		}

		if (!$error) {
			$db->begin();

			if (empty($ref_fourn_price_id)) {
				$ret = $object->add_fournisseur($user, $id_fourn, $ref_fourn_old, $quantity); // This insert record with no value for price. Values are update later with update_buyprice
				if ($ret == -3) {
					$error++;

					$tmpobject = new Product($db);
					$tmpobject->fetch($object->product_id_already_linked);
					$productLink = $tmpobject->getNomUrl(1, 'supplier');

					$texttoshow = $langs->trans("ReferenceSupplierIsAlreadyAssociatedWithAProduct", '{s1}');
					$texttoshow = str_replace('{s1}', $productLink, $texttoshow);
					setEventMessages($texttoshow, null, 'errors');
				} elseif ($ret < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}

			if (!$error) {
				$supplier = new Fournisseur($db);
				$result = $supplier->fetch($id_fourn);
				if (GETPOSTISSET('ref_fourn_price_id')) {
					$object->fetch_product_fournisseur_price($ref_fourn_price_id);
				}
				$extralabels = $extrafields->fetch_name_optionals_label("product_fournisseur_price");
				$extrafield_values = $extrafields->getOptionalsFromPost("product_fournisseur_price");

				$newprice = GETPOSTFLOAT("price");

				if (empty($packaging)) {
					$packaging = 1;
				}
				/* We can have a purchase ref that need to buy 100 min for a given price and with a packaging of 50.
				if ($packaging < $quantity) {
					$packaging = $quantity;
				}*/
				$object->packaging = $packaging;

				if (isModEnabled("multicurrency")) {
					$multicurrency_tx = GETPOSTFLOAT("multicurrency_tx");
					$multicurrency_price = GETPOSTFLOAT("multicurrency_price");
					$multicurrency_code = GETPOST("multicurrency_code", 'alpha');

					$ret = $object->update_buyprice($quantity, $newprice, $user, GETPOST("price_base_type"), $supplier, GETPOST("oselDispo"), $ref_fourn, $tva_tx, GETPOST("charges"), $remise_percent, 0, $npr, $delivery_time_days, $supplier_reputation, array(), '', $multicurrency_price, GETPOST("multicurrency_price_base_type"), $multicurrency_tx, $multicurrency_code, $supplier_description, $barcode, $fk_barcode_type, $extrafield_values);
				} else {
					$ret = $object->update_buyprice($quantity, $newprice, $user, GETPOST("price_base_type"), $supplier, GETPOST("oselDispo"), $ref_fourn, $tva_tx, GETPOST("charges"), $remise_percent, 0, $npr, $delivery_time_days, $supplier_reputation, array(), '', 0, 'HT', 1, '', $supplier_description, $barcode, $fk_barcode_type, $extrafield_values);
				}
				if ($ret < 0) {
					$error++;
					setEventMessages($object->error, $object->errors, 'errors');
				} else {
					if (isModEnabled('dynamicprices') && $price_expression !== '') {
						//Check the expression validity by parsing it
						require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
						$priceparser = new PriceParser($db);
						$object->fk_supplier_price_expression = $price_expression;
						$price_result = $priceparser->parseProductSupplier($object);
						if ($price_result < 0) { //Expression is not valid
							$error++;
							setEventMessages($priceparser->translatedError(), null, 'errors');
						}
					}
					if (!$error && isModEnabled('dynamicprices')) {
						//Set the price expression for this supplier price
						$ret = $object->setSupplierPriceExpression($price_expression);
						if ($ret < 0) {
							$error++;
							setEventMessages($object->error, $object->errors, 'errors');
						}
					}
				}
			}

			if (!$error) {
				$db->commit();
				$action = '';
			} else {
				$db->rollback();
			}
		} else {
			$action = 'create_price';
		}
	}
}


/*
 * view
 */

$form = new Form($db);

$title = $langs->trans('ProductServiceCard');
$helpurl = '';
$shortlabel = dol_trunc($object->label, 16);
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) {
	$title = $langs->trans('Product')." ".$shortlabel." - ".$langs->trans('BuyingPrices');
	$helpurl = 'EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos|DE:Modul_Produkte';
}
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) {
	$title = $langs->trans('Service')." ".$shortlabel." - ".$langs->trans('BuyingPrices');
	$helpurl = 'EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios|DE:Modul_Lesitungen';
}

llxHeader('', $title, $helpurl, '', 0, 0, '', '', '', 'classforhorizontalscrolloftabs mod-product page-price_suppliers');

if ($id > 0 || $ref) {
	if ($result) {
		if ($action == 'ask_remove_pf') {
			$form = new Form($db);
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$id.'&rowid='.$rowid, $langs->trans('DeleteProductBuyPrice'), $langs->trans('ConfirmDeleteProductBuyPrice'), 'confirm_remove_pf', '', 0, 1);
			echo $formconfirm;
		}

		if ($action != 'edit' && $action != 're-edit') {
			$head = product_prepare_head($object);
			$titre = $langs->trans("CardProduct".$object->type);
			$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');

			print dol_get_fiche_head($head, 'suppliers', $titre, -1, $picto);

			$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1&type='.$object->type.'">'.$langs->trans("BackToList").'</a>';
			$object->next_prev_filter = "fk_product_type:=:".((int) $object->type); // usf filter

			$shownav = 1;
			if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
				$shownav = 0;
			}

			dol_banner_tab($prod, 'ref', $linkback, $shownav, 'ref');

			print '<div class="fichecenter">';

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

			// Cost price. Can be used for margin module for option "calculate margin on explicit cost price
			print '<tr><td>';
			$textdesc = $langs->trans("CostPriceDescription");
			$textdesc .= "<br>".$langs->trans("CostPriceUsage");
			$text = $form->textwithpicto($langs->trans("CostPrice"), $textdesc, 1, 'help', '');
			print $form->editfieldkey($text, 'cost_price', $object->cost_price, $object, $usercancreate, 'amount:6');
			print '</td><td>';
			print $form->editfieldval($text, 'cost_price', $object->cost_price, $object, $usercancreate, 'amount:6');
			print '</td></tr>';

			// PMP
			$usercaneditpmp = 0;
			if (getDolGlobalString('PRODUCT_CAN_EDIT_WAP')) {
				$usercaneditpmp = $usercancreate;
			}
			print '<tr><td class="titlefieldcreate">';
			$textdesc = $langs->trans("AverageUnitPricePMPDesc");
			$text = $form->textwithpicto($langs->trans("AverageUnitPricePMPShort"), $textdesc, 1, 'help', '');
			print $form->editfieldkey($text, 'pmp', $object->pmp, $object, $usercaneditpmp, 'amount:6');
			print '</td><td>';
			print $form->editfieldval($text, 'pmp', ($object->pmp > 0 ? $object->pmp : ''), $object, $usercaneditpmp, 'amount:6');
			if ($object->pmp > 0) {
				print ' '.$langs->trans("HT");
			}
			/*
			.$form->textwithpicto($langs->trans("AverageUnitPricePMPShort"), $langs->trans("AverageUnitPricePMPDesc")).'</td>';
			print '<td>';
			if ($object->pmp > 0) {
				print price($object->pmp).' '.$langs->trans("HT");
			}*/
			print '</td>';
			print '</tr>';

			// Best buying Price
			print '<tr><td class="titlefieldcreate">'.$langs->trans("BuyingPriceMin").'</td>';
			print '<td>';
			$product_fourn = new ProductFournisseur($db);
			if ($product_fourn->find_min_price_product_fournisseur($object->id) > 0) {
				if ($product_fourn->product_fourn_price_id > 0) {
					print $product_fourn->display_price_product_fournisseur();
				} else {
					print $langs->trans("NotDefined");
				}
			}
			print '</td></tr>';

			print '</table>';

			print '</div>';
			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();


			// Form to add or update a price
			if (($action == 'create_price' || $action == 'edit_price') && $usercancreate) {
				$langs->load("suppliers");

				print "<!-- form to add a supplier price -->\n";
				print '<br>';

				if ($rowid) {
					$object->fetch_product_fournisseur_price($rowid, 1); //Ignore the math expression when getting the price
					print load_fiche_titre($langs->trans("ChangeSupplierPrice"));
				} else {
					print load_fiche_titre($langs->trans("AddSupplierPrice"));
				}

				print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="save_price">';

				print dol_get_fiche_head();

				print '<table class="border centpercent">';

				// Supplier
				print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Supplier").'</td><td>';
				if ($rowid) {
					$supplier = new Fournisseur($db);
					$supplier->fetch($socid);
					print $supplier->getNomUrl(1);
					print '<input type="hidden" name="id_fourn" value="'.$socid.'">';
					print '<input type="hidden" name="ref_fourn_price_id" value="'.$rowid.'">';
					print '<input type="hidden" name="rowid" value="'.$rowid.'">';
					print '<input type="hidden" name="socid" value="'.$socid.'">';
				} else {
					$events = array();
					$events[] = array('method' => 'getVatRates', 'url' => dol_buildpath('/core/ajax/vatrates.php', 1), 'htmlname' => 'tva_tx', 'params' => array());
					$filter = '(fournisseur:=:1) AND (status:=:1)';
					print img_picto('', 'company', 'class="pictofixedwidth"').$form->select_company(GETPOST("id_fourn", 'alpha'), 'id_fourn', $filter, $langs->transnoentitiesnoconv('SelectThirdParty'), 0, 0, $events);

					$parameters = array('filter'=>$filter, 'html_name'=>'id_fourn', 'selected'=>GETPOST("id_fourn"), 'showempty'=>1, 'prod_id'=>$object->id);
					$reshook = $hookmanager->executeHooks('formCreateThirdpartyOptions', $parameters, $object, $action);
					if (empty($reshook)) {
						if (empty($form->result)) {
							print '<a href="'.DOL_URL_ROOT.'/societe/card.php?action=create&type=f&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.((int) $object->id).'&action='.urlencode($action).($action == 'create_price' ? '&token='.newToken() : '')).'">';
							print img_picto($langs->trans("CreateDolibarrThirdPartySupplier"), 'add', 'class="marginleftonly"');
							print '</a>';
						}
					}
					print '<script type="text/javascript">
					$(document).ready(function () {
						console.log("Requesting default VAT rate for the supplier...")
						$("#search_id_fourn").change(load_vat)
					});
					function load_vat() {
						// get soc id
						let socid = $("#id_fourn")[0].value

						// load available VAT rates
						let vat_url = "'.dol_buildpath('/core/ajax/vatrates.php', 1).'"
						// make GET request with params
						let options = "";
						options += "id=" + socid
						options += "&htmlname=tva_tx"
						options += "&token='.currentToken().'"
						options += "&action=getBuyerVATRates" // not defined in vatrates.php, default behavior.

						var get = $.getJSON(
							vat_url,
							options,
							(data) => {
								rate_options = $.parseHTML(data.value)
								rate_options.forEach(opt => {
									if (opt.selected) {
										replaceVATWithSupplierValue(opt.value);
										return;
									}
								})
							}
						);

					}
					function replaceVATWithSupplierValue(vat_rate) {
						console.log("Default VAT rate for the supplier: " + vat_rate + "%")
						$("[name=\'tva_tx\']")[0].value = vat_rate;
					}
				</script>';
				}
				print '</td></tr>';

				// Ref supplier
				print '<tr><td class="fieldrequired">'.$langs->trans("SupplierRef").'</td><td>';
				if ($rowid) {
					print '<input type="hidden" name="ref_fourn_old" value="'.$object->ref_supplier.'">';
					print '<input class="flat width150" maxlength="128" name="ref_fourn" value="'.$object->ref_supplier.'">';
				} else {
					print '<input class="flat width150" maxlength="128" name="ref_fourn" value="'.(GETPOST("ref_fourn") ? GETPOST("ref_fourn") : '').'">';
				}
				print '</td>';
				print '</tr>';

				// Availability
				if (getDolGlobalInt('FOURN_PRODUCT_AVAILABILITY')) {
					$langs->load("propal");
					print '<tr><td>'.$langs->trans("Availability").'</td><td>';
					$form->selectAvailabilityDelay($object->fk_availability, "oselDispo", 1);
					print '</td></tr>'."\n";
				}

				// Qty min
				print '<tr>';
				print '<td class="fieldrequired">'.$langs->trans("QtyMin").'</td>';
				print '<td>';
				$quantity = GETPOSTISSET('qty') ? price2num(GETPOST('qty', 'alphanohtml'), 'MS') : "1";
				if ($rowid) {
					print '<input type="hidden" name="qty" value="'.$object->fourn_qty.'">';
					print $object->fourn_qty;
				} else {
					print '<input class="flat" name="qty" size="5" value="'.$quantity.'">';
				}
				// Units
				if (getDolGlobalString('PRODUCT_USE_UNITS')) {
					$unit = $object->getLabelOfUnit();
					if ($unit !== '') {
						print '&nbsp;&nbsp;'.$langs->trans($unit);
					}
				}
				print '</td></tr>';

				if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					// Packaging/Conditionnement
					print '<tr>';

					print '<td class="fieldrequired">'.$form->textwithpicto($langs->trans("PackagingForThisProduct"), $langs->trans("PackagingForThisProductDesc")).'</td>';
					print '<td>';
					$packaging = GETPOSTISSET('packaging') ? price2num(GETPOST('packaging', 'alphanohtml'), 'MS') : ((empty($rowid)) ? "1" : price2num($object->packaging, 'MS'));
					print '<input class="flat" name="packaging" size="5" value="'.$packaging.'">';

					// Units
					if (getDolGlobalString('PRODUCT_USE_UNITS')) {
						$unit = $object->getLabelOfUnit();
						if ($unit !== '') {
							print '&nbsp;&nbsp;'.$langs->trans($unit);
						}
					}
				}
				// Vat rate
				$default_vat = '';

				// We don't have supplier, so we try to guess.
				// For this we build a fictive supplier with same properties than user but using vat)
				$mysoc2 = clone $mysoc;
				$mysoc2->name = 'Fictive seller with same country';
				$mysoc2->tva_assuj = 1;
				$default_vat = get_default_tva($mysoc2, $mysoc, $object->id, 0);
				$default_npr = get_default_npr($mysoc2, $mysoc, $object->id, 0);
				if (empty($default_vat)) {
					$default_npr = $default_vat;
				}

				print '<tr><td class="fieldrequired">'.$langs->trans("VATRateForSupplierProduct").'</td>';
				print '<td>';
				//print $form->load_tva('tva_tx',$object->tva_tx,$supplier,$mysoc);    // Do not use list here as it may be any vat rates for any country
				if (!empty($rowid)) {	// If we have a supplier, it is an update, we must show the vat of current supplier price
					$tmpproductsupplier = new ProductFournisseur($db);
					$tmpproductsupplier->fetch_product_fournisseur_price($rowid, 1);
					$default_vat = $tmpproductsupplier->fourn_tva_tx;
					$default_npr = $tmpproductsupplier->fourn_tva_npr;
				} else {
					if (empty($default_vat)) {
						$default_vat = $object->tva_tx;
					}
				}
				$vattosuggest = (GETPOSTISSET("tva_tx") ? vatrate(GETPOST("tva_tx")) : ($default_vat != '' ? vatrate($default_vat) : ''));
				$vattosuggest = preg_replace('/\s*\(.*\)$/', '', $vattosuggest);
				print '<input type="text" class="flat" size="5" name="tva_tx" value="'.$vattosuggest.'">';
				print '</td></tr>';

				if (isModEnabled('dynamicprices')) { //Only show price mode and expression selector if module is enabled
					// Price mode selector
					print '<tr><td class="fieldrequired">'.$langs->trans("PriceMode").'</td><td>';
					$price_expression = new PriceExpression($db);
					$price_expression_list = array(0 => $langs->trans("PriceNumeric")); //Put the numeric mode as first option
					foreach ($price_expression->list_price_expression() as $entry) {
						$price_expression_list[$entry->id] = $entry->title;
					}
					$price_expression_preselection = GETPOST('eid') ? GETPOST('eid') : ($object->fk_supplier_price_expression ? $object->fk_supplier_price_expression : '0');
					print $form->selectarray('eid', $price_expression_list, $price_expression_preselection);
					print '&nbsp; <div id="expression_editor" class="button smallpaddingimp">'.$langs->trans("PriceExpressionEditor").'</div>';
					print '</td></tr>';
					// This code hides the numeric price input if is not selected, loads the editor page if editor button is pressed
					print '<script type="text/javascript">
						jQuery(document).ready(run);
						function run() {
							jQuery("#expression_editor").click(on_click);
							jQuery("#eid").change(on_change);
							on_change();
						}
						function on_click() {
							window.location = "'.DOL_URL_ROOT.'/product/dynamic_price/editor.php?id='.$id.'&tab=fournisseurs&eid=" + $("#eid").val();
						}
						function on_change() {
							if ($("#eid").val() == 0) {
								jQuery("#price_numeric").show();
							} else {
								jQuery("#price_numeric").hide();
							}
						}
					</script>';
				}

				if (isModEnabled("multicurrency")) {
					// Currency
					print '<tr><td class="fieldrequired">'.$langs->trans("Currency").'</td>';
					print '<td>';
					$currencycodetouse = GETPOST('multicurrency_code') ? GETPOST('multicurrency_code') : (isset($object->fourn_multicurrency_code) ? $object->fourn_multicurrency_code : '');
					if (empty($currencycodetouse) && $object->fourn_multicurrency_tx == 1) {
						$currencycodetouse = $conf->currency;
					}
					print $form->selectMultiCurrency($currencycodetouse, "multicurrency_code", 1);
					print ' &nbsp; &nbsp; '.$langs->trans("CurrencyRate").' ';
					print '<input class="flat width50" name="multicurrency_tx" value="';
					print GETPOST('multicurrency_tx');
					$vatratetoshow = GETPOST('multicurrency_tx') ? GETPOST('multicurrency_tx') : (isset($object->fourn_multicurrency_tx) ? $object->fourn_multicurrency_tx : '');
					if ($vatratetoshow !== '') {
						print vatrate($vatratetoshow);
					}
					print '">';
					print '</td>';
					print '</tr>';

					// Currency price qty min
					print '<tr><td class="fieldrequired">'.$form->textwithpicto($langs->trans("PriceQtyMinCurrency"), $langs->transnoentitiesnoconv("WithoutDiscount")).'</td>';
					$pricesupplierincurrencytouse = (GETPOST('multicurrency_price') ? GETPOST('multicurrency_price') : (isset($object->fourn_multicurrency_price) ? $object->fourn_multicurrency_price : ''));
					print '<td><input class="flat" name="multicurrency_price" size="8" value="'.price($pricesupplierincurrencytouse).'">';
					print '&nbsp;';
					print $form->selectPriceBaseType((GETPOST('multicurrency_price_base_type') ? GETPOST('multicurrency_price_base_type') : 'HT'), "multicurrency_price_base_type", 1); // We keep 'HT' here, multicurrency_price_base_type is not yet supported for supplier prices
					print '</td></tr>';

					// Price qty min
					print '<tr><td class="fieldrequired">'.$form->textwithpicto($langs->trans("PriceQtyMin"), $langs->transnoentitiesnoconv("WithoutDiscount")).'</td>';
					print '<td><input class="flat" name="disabled_price" size="8" value="">';
					print '<input type="hidden" name="price" value="">';
					print '<input type="hidden" name="price_base_type" value="">';
					print '&nbsp;';
					print $form->selectPriceBaseType('', "disabled_price_base_type", 1);
					print '</td></tr>';

					$currencies = array();
					$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity = ".((int) $conf->entity);
					$resql = $db->query($sql);
					if ($resql) {
						$currency = new MultiCurrency($db);
						while ($obj = $db->fetch_object($resql)) {
							$currency->fetch($obj->rowid);
							$currencies[$currency->code] = ((float) $currency->rate->rate);
						}
					}
					$currencies = json_encode($currencies);
					print "<!-- javascript to autocalculate the minimum price -->
					<script type='text/javascript'>
						function edit_price_from_multicurrency() {
							console.log('edit_price_from_multicurrency');
							var multicurrency_price = price2numjs($('input[name=\"multicurrency_price\"]').val());
							var multicurrency_tx = price2numjs($('input[name=\"multicurrency_tx\"]').val());
							if (multicurrency_tx != 0) {
								$('input[name=\"price\"]').val(multicurrency_price / multicurrency_tx);
								$('input[name=\"disabled_price\"]').val(multicurrency_price / multicurrency_tx);
							} else {
								$('input[name=\"price\"]').val('');
								$('input[name=\"disabled_price\"]').val('');
							}
						}

						jQuery(document).ready(function () {
							$('input[name=\"disabled_price\"]').prop('disabled', true);
							$('select[name=\"disabled_price_base_type\"]').prop('disabled', true);
							edit_price_from_multicurrency();

							$('input[name=\"multicurrency_price\"], input[name=\"multicurrency_tx\"]').keyup(function () {
								edit_price_from_multicurrency();
							});
							$('input[name=\"multicurrency_price\"], input[name=\"multicurrency_tx\"]').change(function () {
								edit_price_from_multicurrency();
							});
							$('input[name=\"multicurrency_price\"], input[name=\"multicurrency_tx\"]').on('paste', function () {
								edit_price_from_multicurrency();
							});

							$('select[name=\"multicurrency_price_base_type\"]').change(function () {
								$('input[name=\"price_base_type\"]').val($(this).val());
								$('select[name=\"disabled_price_base_type\"]').val($(this).val());
							});

							var currencies_array = $currencies;
							$('select[name=\"multicurrency_code\"]').change(function () {
								console.log(\"We change the currency\");
								$('input[name=\"multicurrency_tx\"]').val(currencies_array[$(this).val()]);
								edit_price_from_multicurrency();
							});
						});
					</script>";
				} else {
					// Price qty min
					print '<tr><td class="fieldrequired">'.$langs->trans("PriceQtyMin").'</td>';
					print '<td><input class="flat" name="price" size="8" value="'.(GETPOST('price') ? price(GETPOST('price')) : (isset($object->fourn_price) ? price($object->fourn_price) : '')).'">';
					print '&nbsp;';
					print $form->selectPriceBaseType((GETPOSTISSET('price_base_type') ? GETPOST('price_base_type') : 'HT'), "price_base_type", 1); // We keep 'HT' here, price_base_type is not yet supported for supplier prices
					print '</td></tr>';
				}

				// Option to define a transport cost on supplier price
				if (getDolGlobalString('PRODUCT_CHARGES')) {
					print '<tr>';
					print '<td>'.$langs->trans("Charges").'</td>';
					print '<td><input class="flat" name="charges" size="8" value="'.(GETPOST('charges') ? price(GETPOST('charges')) : (isset($object->fourn_charges) ? price($object->fourn_charges) : '')).'">';
					print '</td>';
					print '</tr>';
				}

				// Discount qty min
				print '<tr><td>'.$langs->trans("DiscountQtyMin").'</td>';
				print '<td><input class="flat" name="remise_percent" size="4" value="'.(GETPOSTISSET('remise_percent') ? vatrate(price2num(GETPOST('remise_percent'), '', 2)) : (isset($object->fourn_remise_percent) ? vatrate(price2num($object->fourn_remise_percent)) : '')).'"> %';
				print '</td>';
				print '</tr>';

				// Delivery delay in days
				print '<tr>';
				print '<td>'.$langs->trans('NbDaysToDelivery').'</td>';
				print '<td><input class="flat" name="delivery_time_days" size="4" value="'.(GETPOSTISSET('delivery_time_days') ? GETPOST('delivery_time_days') : ($rowid ? $object->delivery_time_days : '')).'">&nbsp;'.$langs->trans('days').'</td>';
				print '</tr>';

				// Reputation
				print '<tr><td>'.$langs->trans("ReferenceReputation").'</td><td>';
				echo $form->selectarray('supplier_reputation', $object->reputations, !empty($supplier_reputation) ? $supplier_reputation : $object->supplier_reputation);
				print '</td></tr>';

				// Barcode
				if (isModEnabled('barcode')) {
					$formbarcode = new FormBarCode($db);

					// Barcode type
					print '<tr>';
					print '<td>'.$langs->trans('GencodBuyPrice').'</td>';
					print '<td>';
					print img_picto('', 'barcode', 'class="pictofixedwidth"');
					print $formbarcode->selectBarcodeType((GETPOSTISSET('fk_barcode_type') ? GETPOSTINT('fk_barcode_type') : ($rowid ? $object->supplier_fk_barcode_type : getDolGlobalInt("PRODUIT_DEFAULT_BARCODE_TYPE"))), 'fk_barcode_type', 1);
					print ' <input class="flat" name="barcode"  value="'.(GETPOSTISSET('barcode') ? GETPOST('barcode') : ($rowid ? $object->supplier_barcode : '')).'"></td>';
					print '</tr>';
				}

				// Product description of the supplier
				if (getDolGlobalString('PRODUIT_FOURN_TEXTS')) {
					//WYSIWYG Editor
					require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

					print '<tr>';
					print '<td>'.$langs->trans('ProductSupplierDescription').'</td>';
					print '<td>';

					$doleditor = new DolEditor('supplier_description', $object->desc_supplier, '', 160, 'dolibarr_details', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_4, '90%');
					$doleditor->Create();

					print '</td>';
					print '</tr>';
				}

				// Extrafields
				$extrafields->fetch_name_optionals_label("product_fournisseur_price");
				$extralabels = !empty($extrafields->attributes["product_fournisseur_price"]['label']) ? $extrafields->attributes["product_fournisseur_price"]['label'] : '';
				$extrafield_values = $extrafields->getOptionalsFromPost("product_fournisseur_price");
				if (!empty($extralabels)) {
					if (empty($rowid)) {
						foreach ($extralabels as $key => $value) {
							if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && ($extrafields->attributes["product_fournisseur_price"]['list'][$key] == 1 || $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 3 || ($action == "edit_price" && $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 4))) {
								if (!empty($extrafields->attributes["product_fournisseur_price"]['langfile'][$key])) {
									$langs->load($extrafields->attributes["product_fournisseur_price"]['langfile'][$key]);
								}

								print '<tr><td'.($extrafields->attributes["product_fournisseur_price"]['required'][$key] ? ' class="fieldrequired"' : '').'>';
								if (!empty($extrafields->attributes["product_fournisseur_price"]['help'][$key])) {
									print $form->textwithpicto($langs->trans($value), $langs->trans($extrafields->attributes["product_fournisseur_price"]['help'][$key]));
								} else {
									print $langs->trans($value);
								}
								print '</td><td>'.$extrafields->showInputField($key, GETPOSTISSET('options_'.$key) ? $extrafield_values['options_'.$key] : '', '', '', '', '', 0, 'product_fournisseur_price').'</td></tr>';
							}
						}
					} else {
						$sql  = "SELECT";
						$sql .= " fk_object";
						foreach ($extralabels as $key => $value) {
							$sql .= ", ".$key;
						}
						$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields";
						$sql .= " WHERE fk_object = ".((int) $rowid);
						$resql = $db->query($sql);
						if ($resql) {
							$obj = $db->fetch_object($resql);
							foreach ($extralabels as $key => $value) {
								if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && ($extrafields->attributes["product_fournisseur_price"]['list'][$key] == 1 || $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 3 || ($action == "edit_price" && $extrafields->attributes["product_fournisseur_price"]['list'][$key] == 4))) {
									if (!empty($extrafields->attributes["product_fournisseur_price"]['langfile'][$key])) {
										$langs->load($extrafields->attributes["product_fournisseur_price"]['langfile'][$key]);
									}

									print '<tr><td'.($extrafields->attributes["product_fournisseur_price"]['required'][$key] ? ' class="fieldrequired"' : '').'>';
									if (!empty($extrafields->attributes["product_fournisseur_price"]['help'][$key])) {
										print $form->textwithpicto($langs->trans($value), $langs->trans($extrafields->attributes["product_fournisseur_price"]['help'][$key]));
									} else {
										print $langs->trans($value);
									}
									print '</td><td>'.$extrafields->showInputField($key, GETPOSTISSET('options_'.$key) ? $extrafield_values['options_'.$key] : $obj->{$key}, '', '', '', '', 0, 'product_fournisseur_price');

									print '</td></tr>';
								}
							}
							$db->free($resql);
						}
					}
				}

				if (is_object($hookmanager)) {
					$parameters = array('id_fourn'=>!empty($id_fourn) ? $id_fourn : 0, 'prod_id'=>$object->id);
					$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action);
					print $hookmanager->resPrint;
				}

				print '</table>';

				print dol_get_fiche_end();

				print '<div class="center">';
				print '<input class="button button-save" type="submit" value="'.$langs->trans("Save").'">';
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
				print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
				print '</div>';

				print '</form>'."\n";
			}


			// Actions buttons

			print '<div class="tabsAction">'."\n";

			if ($action != 'create_price' && $action != 'edit_price') {
				$parameters = array();
				$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
				if (empty($reshook)) {
					if ($usercancreate) {
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/product/price_suppliers.php?id='.((int) $object->id).'&action=create_price&token='.newToken().'">';
						print $langs->trans("AddSupplierPrice").'</a>';
					}
				}
			}

			print "</div>\n";

			if ($user->hasRight("fournisseur", "read")) { // Duplicate ? this check is already in the head of this file
				$param = '';
				if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
					$param .= '&contextpage='.urlencode($contextpage);
				}
				if ($limit > 0 && $limit != $conf->liste_limit) {
					$param .= '&limit='.((int) $limit);
				}
				$param .= '&ref='.urlencode($object->ref);

				$product_fourn = new ProductFournisseur($db);
				$product_fourn_list = $product_fourn->list_product_fournisseur_price($object->id, $sortfield, $sortorder, $limit, $offset);
				$product_fourn_list_all = $product_fourn->list_product_fournisseur_price($object->id, $sortfield, $sortorder, 0, 0);
				$nbtotalofrecords = count($product_fourn_list_all);
				$num = count($product_fourn_list);
				if (($num + ($offset * $limit)) < $nbtotalofrecords) {
					$num++;
				}

				print_barre_liste($langs->trans('SupplierPrices'), $page, $_SERVER['PHP_SELF'], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy.png', 0, '', '', $limit, 1);

				// Definition of fields for lists
				// Some fields are missing because they are not included in the database query
				$arrayfields = array(
					'pfp.datec'=>array('label'=>$langs->trans("AppliedPricesFrom"), 'checked'=>1, 'position'=>1),
					's.nom'=>array('label'=>$langs->trans("Suppliers"), 'checked'=>1, 'position'=>2),
					'pfp.fk_availability'=>array('label'=>$langs->trans("Availability"), 'enabled' => getDolGlobalInt('FOURN_PRODUCT_AVAILABILITY'), 'checked'=>0, 'position'=>4),
					'pfp.quantity'=>array('label'=>$langs->trans("QtyMin"), 'checked'=>1, 'position'=>5),
					'pfp.unitprice'=>array('label'=>$langs->trans("UnitPriceHT"), 'checked'=>1, 'position'=>9),
					'pfp.multicurrency_unitprice'=>array('label'=>$langs->trans("UnitPriceHTCurrency"), 'enabled' => isModEnabled('multicurrency'), 'checked'=>0, 'position'=>10),
					'pfp.charges'=>array('label'=>$langs->trans("Charges"), 'enabled' => getDolGlobalString('PRODUCT_CHARGES'), 'checked'=>0, 'position'=>11),
					'pfp.delivery_time_days'=>array('label'=>$langs->trans("NbDaysToDelivery"), 'checked'=>-1, 'position'=>13),
					'pfp.supplier_reputation'=>array('label'=>$langs->trans("ReputationForThisProduct"), 'checked'=>-1, 'position'=>14),
					'pfp.fk_barcode_type'=>array('label'=>$langs->trans("BarcodeType"), 'enabled' => isModEnabled('barcode'), 'checked'=>0, 'position'=>15),
					'pfp.barcode'=>array('label'=>$langs->trans("BarcodeValue"), 'enabled' => isModEnabled('barcode'), 'checked'=>0, 'position'=>16),
					'pfp.packaging'=>array('label'=>$langs->trans("PackagingForThisProduct"), 'enabled' => getDolGlobalInt('PRODUCT_USE_SUPPLIER_PACKAGING'), 'checked'=>0, 'position'=>17),
					'pfp.status'=>array('label'=>$langs->trans("Status"), 'enabled' => 1, 'checked'=>0, 'position'=>40),
					'pfp.tms'=>array('label'=>$langs->trans("DateModification"), 'enabled' => isModEnabled('barcode'), 'checked'=>1, 'position'=>50),
					'pfp.price'=>array('label'=>$langs->trans("PriceQtyMinHT"), 'checked'=>1, 'position'=>60),
					'pfp.multicurrency_price'=>array('label'=>$langs->trans("PriceQtyMinHTCurrency"), 'enabled' => isModEnabled('multicurrency'), 'checked'=>1, 'position'=>70),

				);

				// fetch optionals attributes and labels
				$extrafields->fetch_name_optionals_label("product_fournisseur_price");
				if ($extrafields->attributes["product_fournisseur_price"] && array_key_exists('label', $extrafields->attributes["product_fournisseur_price"])) {
					$extralabels = $extrafields->attributes["product_fournisseur_price"]['label'];

					if (!empty($extralabels)) {
						foreach ($extralabels as $key => $value) {
							// Show field if not hidden
							if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
								$extratitle = $langs->trans($value);
								$arrayfields['ef.' . $key] = array('label'    => $extratitle, 'checked' => 0,
								'position' => (end($arrayfields)['position'] + 1),
								'langfile' => $extrafields->attributes["product_fournisseur_price"]['langfile'][$key],
								'help'     => $extrafields->attributes["product_fournisseur_price"]['help'][$key]);
							}
						}
					}
				}

				// Selection of new fields
				include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

				$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
				$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields

				print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post" name="formulaire">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
				print '<input type="hidden" name="action" value="list">';
				print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
				print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

				// Suppliers list title
				print '<!-- List of supplier prices -->'."\n";
				print '<div class="div-table-responsive">';
				print '<table class="liste centpercent noborder">';

				$param = "&id=".$object->id;

				$nbfields = 0;

				print '<tr class="liste_titre">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch actioncolumn ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.datec']['checked'])) {
					print_liste_field_titre("AppliedPricesFrom", $_SERVER["PHP_SELF"], "pfp.datec", "", $param, "", $sortfield, $sortorder, '', '', 1);
					$nbfields++;
				}
				if (!empty($arrayfields['s.nom']['checked'])) {
					print_liste_field_titre("Suppliers", $_SERVER["PHP_SELF"], "s.nom", "", $param, "", $sortfield, $sortorder, '', '', 1);
					$nbfields++;
				}
				print_liste_field_titre("SupplierRef", $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder, '', '', 1);
				$nbfields++;
				if (!empty($arrayfields['pfp.fk_availability']['checked'])) {
					print_liste_field_titre("Availability", $_SERVER["PHP_SELF"], "pfp.fk_availability", "", $param, "", $sortfield, $sortorder);
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.quantity']['checked'])) {
					print_liste_field_titre("QtyMin", $_SERVER["PHP_SELF"], "pfp.quantity", "", $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				print_liste_field_titre("VATRate", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
				$nbfields++;
				if (!empty($arrayfields['pfp.price']['checked'])) {
					print_liste_field_titre("PriceQtyMinHT", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				if (isModEnabled("multicurrency") && !empty($arrayfields['pfp.multicurrency_price']['checked'])) {
					print_liste_field_titre("PriceQtyMinHTCurrency", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.unitprice']['checked'])) {
					print_liste_field_titre("UnitPriceHT", $_SERVER["PHP_SELF"], "pfp.unitprice", "", $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.multicurrency_unitprice']['checked'])) {
					print_liste_field_titre("UnitPriceHTCurrency", $_SERVER["PHP_SELF"], "pfp.multicurrency_unitprice", "", $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				if (isModEnabled("multicurrency")) {
					print_liste_field_titre("Currency", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.charges']['checked'])) {	// possible only when $conf->global->PRODUCT_CHARGES is set
					print_liste_field_titre("Charges", $_SERVER["PHP_SELF"], "pfp.charges", "", $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				print_liste_field_titre("DiscountQtyMin", $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
				$nbfields++;
				if (!empty($arrayfields['pfp.delivery_time_days']['checked'])) {
					print_liste_field_titre("NbDaysToDelivery", $_SERVER["PHP_SELF"], "pfp.delivery_time_days", "", $param, '', $sortfield, $sortorder, 'right ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.supplier_reputation']['checked'])) {
					print_liste_field_titre("ReputationForThisProduct", $_SERVER["PHP_SELF"], "pfp.supplier_reputation", "", $param, '', $sortfield, $sortorder, 'center ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.fk_barcode_type']['checked'])) {
					print_liste_field_titre("BarcodeType", $_SERVER["PHP_SELF"], "pfp.fk_barcode_type", "", $param, '', $sortfield, $sortorder, 'center ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.barcode']['checked'])) {
					print_liste_field_titre("BarcodeValue", $_SERVER["PHP_SELF"], "pfp.barcode", "", $param, '', $sortfield, $sortorder, 'center ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.packaging']['checked'])) {
					print_liste_field_titre("PackagingForThisProduct", $_SERVER["PHP_SELF"], "pfp.packaging", "", $param, '', $sortfield, $sortorder, 'center ');
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.status']['checked'])) {
					print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "pfp.status", "", $param, '', $sortfield, $sortorder, 'center ', '', 1);
					$nbfields++;
				}
				if (!empty($arrayfields['pfp.tms']['checked'])) {
					print_liste_field_titre("DateModification", $_SERVER["PHP_SELF"], "pfp.tms", "", $param, '', $sortfield, $sortorder, 'right ', '', 1);
					$nbfields++;
				}

				// fetch optionals attributes and labels
				$extrafields->fetch_name_optionals_label("product_fournisseur_price");
				if ($extrafields->attributes["product_fournisseur_price"] && array_key_exists('label', $extrafields->attributes["product_fournisseur_price"])) {
					$extralabels = $extrafields->attributes["product_fournisseur_price"]['label'];

					if (!empty($extralabels)) {
						foreach ($extralabels as $key => $value) {
							// Show field if not hidden
							if (!empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
								if (!empty($extrafields->attributes["product_fournisseur_price"]['langfile'][$key])) {
									$langs->load($extrafields->attributes["product_fournisseur_price"]['langfile'][$key]);
								}
								if (!empty($extrafields->attributes["product_fournisseur_price"]['help'][$key])) {
									$extratitle = $form->textwithpicto($langs->trans($value), $langs->trans($extrafields->attributes["product_fournisseur_price"]['help'][$key]));
								} else {
									$extratitle = $langs->trans($value);
								}
								if (!empty($arrayfields['ef.' . $key]['checked'])) {
									print_liste_field_titre($extratitle, $_SERVER["PHP_SELF"], 'ef.' . $key, '', $param, '', $sortfield, $sortorder, 'right ');
									$nbfields++;
								}
							}
						}
					}
				}

				if (is_object($hookmanager)) {
					$parameters = array('id_fourn'=>(!empty($id_fourn) ? $id_fourn : ''), 'prod_id'=>$object->id, 'nbfields'=>$nbfields);
					$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
				}
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch center ');
					$nbfields++;
				}
				print "</tr>\n";

				if (is_array($product_fourn_list)) {
					foreach ($product_fourn_list as $productfourn) {
						print '<tr class="oddeven">';

						// Action column
						if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
							print '<td class="center nowraponall">';
							if ($usercancreate) {
								print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.((int) $object->id).'&socid='.((int) $productfourn->fourn_id).'&action=edit_price&token='.newToken().'&rowid='.((int) $productfourn->product_fourn_price_id).'">'.img_edit()."</a>";
								print ' &nbsp; ';
								print '<a href="'.$_SERVER['PHP_SELF'].'?id='.((int) $object->id).'&socid='.((int) $productfourn->fourn_id).'&action=ask_remove_pf&token='.newToken().'&rowid='.((int) $productfourn->product_fourn_price_id).'">'.img_picto($langs->trans("Remove"), 'delete').'</a>';
							}

							print '</td>';
						}

						// Date from
						if (!empty($arrayfields['pfp.datec']['checked'])) {
							print '<td>'.dol_print_date(($productfourn->fourn_date_creation ? $productfourn->fourn_date_creation : $productfourn->date_creation), 'dayhour', 'tzuserrel').'</td>';
						}

						// Supplier
						if (!empty($arrayfields['s.nom']['checked'])) {
							print '<td class="tdoverflowmax150">'.$productfourn->getSocNomUrl(1, 'supplier').'</td>';
						}

						// Supplier ref
						if ($usercancreate) { // change required right here
							print '<td class="tdoverflowmax150">'.$productfourn->getNomUrl().'</td>';
						} else {
							print '<td class="tdoverflowmax150">'.dol_escape_htmltag($productfourn->fourn_ref).'</td>';
						}

						// Availability
						if (!empty($arrayfields['pfp.fk_availability']['checked'])) {
							$form->load_cache_availability();
							$availability = $form->cache_availability[$productfourn->fk_availability]['label'];
							print '<td class="left">'.$availability.'</td>';
						}

						// Quantity
						if (!empty($arrayfields['pfp.quantity']['checked'])) {
							print '<td class="right">';
							print $productfourn->fourn_qty;
							// Units
							if (getDolGlobalString('PRODUCT_USE_UNITS')) {
								$unit = $object->getLabelOfUnit();
								if ($unit !== '') {
									print '&nbsp;&nbsp;'.$langs->trans($unit);
								}
							}
							print '</td>';
						}

						// VAT rate
						print '<td class="right">';
						print vatrate($productfourn->fourn_tva_tx, true);
						print '</td>';

						// Price for the quantity
						if (!empty($arrayfields['pfp.price']['checked'])) {
							print '<td class="right">';
							print $productfourn->fourn_price ? '<span class="amount">'.price($productfourn->fourn_price).'</span>' : "";
							print '</td>';
						}

						if (isModEnabled("multicurrency") && !empty($arrayfields['pfp.multicurrency_price']['checked'])) {
							// Price for the quantity in currency
							print '<td class="right">';
							print $productfourn->fourn_multicurrency_price ? '<span class="amount">'.price($productfourn->fourn_multicurrency_price).'</span>' : "";
							print '</td>';
						}

						// Unit price
						if (!empty($arrayfields['pfp.unitprice']['checked'])) {
							print '<td class="right">';
							print price($productfourn->fourn_unitprice);
							//print $objp->unitprice? price($objp->unitprice) : ($objp->quantity?price($objp->price/$objp->quantity):"&nbsp;");
							print '</td>';
						}

						// Unit price in currency
						if (!empty($arrayfields['pfp.multicurrency_unitprice']['checked'])) {
							print '<td class="right">';
							print price($productfourn->fourn_multicurrency_unitprice);
							print '</td>';
						}

						// Currency
						if (isModEnabled("multicurrency")) {
							print '<td class="right nowraponall">';
							print $productfourn->fourn_multicurrency_code ? currency_name($productfourn->fourn_multicurrency_code) : '';
							print '</td>';
						}

						// Charges
						if (!empty($arrayfields['pfp.charges']['checked'])) {	// Possible only when getDolGlobalString('PRODUCT_CHARGES') is set
							print '<td class="right">';
							print price($productfourn->fourn_charges);
							print '</td>';
						}

						// Discount
						print '<td class="right">';
						print price2num($productfourn->fourn_remise_percent).'%';
						print '</td>';

						// Delivery delay
						if (!empty($arrayfields['pfp.delivery_time_days']['checked'])) {
							print '<td class="right">';
							print $productfourn->delivery_time_days;
							print '</td>';
						}

						// Reputation
						if (!empty($arrayfields['pfp.supplier_reputation']['checked'])) {
							print '<td class="center">';
							if (!empty($productfourn->supplier_reputation) && !empty($object->reputations[$productfourn->supplier_reputation])) {
								print $object->reputations[$productfourn->supplier_reputation];
							}
							print'</td>';
						}

						// Barcode type
						if (!empty($arrayfields['pfp.fk_barcode_type']['checked'])) {
							print '<td class="center">';
							$productfourn->barcode_type = !empty($productfourn->supplier_fk_barcode_type) ? $productfourn->supplier_fk_barcode_type : 0;
							$productfourn->fetchBarCode();
							print $productfourn->barcode_type_label ? $productfourn->barcode_type_label : ($productfourn->supplier_barcode ? '<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>' : '');
							print '</td>';
						}

						// Barcode
						if (!empty($arrayfields['pfp.barcode']['checked'])) {
							print '<td class="right">';
							print $productfourn->supplier_barcode;
							print '</td>';
						}

						// Packaging
						if (!empty($arrayfields['pfp.packaging']['checked'])) {
							print '<td class="center">';
							print price2num($productfourn->packaging);
							print '</td>';
						}

						// Status
						if (!empty($arrayfields['pfp.status']['checked'])) {
							print '<td class="center">';
							print $productfourn->getLibStatut(3);
							print '</td>';
						}

						// Date modification
						if (!empty($arrayfields['pfp.tms']['checked'])) {
							print '<td class="right nowraponall">';
							print dol_print_date(($productfourn->fourn_date_modification ? $productfourn->fourn_date_modification : $productfourn->date_modification), "dayhour", "tzuserrel");
							print '</td>';
						}

						// Extrafields
						if (!empty($extralabels)) {
							$sql  = "SELECT";
							$sql .= " fk_object";
							foreach ($extralabels as $key => $value) {
								$sql .= ", ".$key;
							}
							$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price_extrafields";
							$sql .= " WHERE fk_object = ".((int) $productfourn->product_fourn_price_id);
							$resql = $db->query($sql);
							if ($resql) {
								if ($db->num_rows($resql) != 1) {
									foreach ($extralabels as $key => $value) {
										if (!empty($arrayfields['ef.'.$key]['checked']) && !empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
											print "<td></td>";
										}
									}
								} else {
									$obj = $db->fetch_object($resql);
									foreach ($extralabels as $key => $value) {
										if (!empty($arrayfields['ef.'.$key]['checked']) && !empty($extrafields->attributes["product_fournisseur_price"]['list'][$key]) && $extrafields->attributes["product_fournisseur_price"]['list'][$key] != 3) {
											print '<td align="right">'.$extrafields->showOutputField($key, $obj->{$key}, '', 'product_fournisseur_price')."</td>";
										}
									}
								}
								$db->free($resql);
							}
						}

						if (is_object($hookmanager)) {
							$parameters = array('id_pfp'=>$productfourn->product_fourn_price_id, 'id_fourn'=>(!empty($id_fourn) ? $id_fourn : ''), 'prod_id'=>$object->id);
							$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action);
						}

						// Modify-Remove
						if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
							print '<td class="center nowraponall">';
							if ($usercancreate) {
								print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?id='.((int) $object->id).'&socid='.((int) $productfourn->fourn_id).'&action=edit_price&token='.newToken().'&rowid='.((int) $productfourn->product_fourn_price_id).'">'.img_edit()."</a>";
								print ' &nbsp; ';
								print '<a href="'.$_SERVER['PHP_SELF'].'?id='.((int) $object->id).'&socid='.((int) $productfourn->fourn_id).'&action=ask_remove_pf&token='.newToken().'&rowid='.((int) $productfourn->product_fourn_price_id).'">'.img_picto($langs->trans("Remove"), 'delete').'</a>';
							}

							print '</td>';
						}

						print '</tr>';
					}

					if (empty($product_fourn_list)) {
						print '<tr><td colspan="'.$nbfields.'"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
					}
				} else {
					dol_print_error($db);
				}

				print '</table>';
				print '</div>';
				print '</form>';
			}
		}
	}
} else {
	print $langs->trans("ErrorUnknown");
}

// End of page
llxFooter();
$db->close();
