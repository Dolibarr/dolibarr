<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2017	Francis Appels			<francis.appels@yahoo.com>
 * Copyright (C) 2015		Claudio Aschieri		<c.aschieri@19.coop>
 * Copyright (C) 2016-2018	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016		Yasser Carreón			<yacasia@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2020       Lenin Rivas         	<lenin@leninrivas.com>
 * Copyright (C) 2022       Josep Lluís Amador      <joseplluis@lliuretic.cat>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/expedition/card.php
 *	\ingroup    expedition
 *	\brief      Card of a shipment
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (isModEnabled("product") || isModEnabled("service")) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}
if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled('productbatch')) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
}
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("sendings", "companies", "bills", 'deliveries', 'orders', 'stocks', 'other', 'propal', 'productbatch'));

if (isModEnabled('incoterm')) {
	$langs->load('incoterm');
}
if (isModEnabled('productbatch')) {
	$langs->load('productbatch');
}

$origin = GETPOST('origin', 'alpha') ? GETPOST('origin', 'alpha') : 'expedition'; // Example: commande, propal
$origin_id = GETPOSTINT('id') ? GETPOSTINT('id') : '';
$id = $origin_id;
if (empty($origin_id)) {
	$origin_id  = GETPOSTINT('origin_id'); // Id of order or propal
}
if (empty($origin_id)) {
	$origin_id  = GETPOSTINT('object_id'); // Id of order or propal
}
$ref = GETPOST('ref', 'alpha');
$line_id = GETPOSTINT('lineid');
$facid = GETPOSTINT('facid');

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');

//PDF
$hidedetails = (GETPOSTINT('hidedetails') ? GETPOSTINT('hidedetails') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS') ? 1 : 0));
$hidedesc = (GETPOSTINT('hidedesc') ? GETPOSTINT('hidedesc') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_DESC') ? 1 : 0));
$hideref = (GETPOSTINT('hideref') ? GETPOSTINT('hideref') : (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_HIDE_REF') ? 1 : 0));

$object = new Expedition($db);
$objectorder = new Commande($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$extrafields->fetch_name_optionals_label($object->table_element_line);
$extrafields->fetch_name_optionals_label($objectorder->table_element_line);

// Load object. Make an object->fetch
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('expeditioncard', 'globalcard'));

$date_delivery = dol_mktime(GETPOSTINT('date_deliveryhour'), GETPOSTINT('date_deliverymin'), 0, GETPOSTINT('date_deliverymonth'), GETPOSTINT('date_deliveryday'), GETPOSTINT('date_deliveryyear'));

if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
}

// Security check
$socid = '';
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'expedition', $object->id, '');

$permissiondellink = $user->hasRight('expedition', 'delivery', 'creer'); // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->hasRight('expedition', 'creer');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if ($cancel) {
		if ($origin && $origin_id > 0) {
			if ($origin == 'commande') {
				header("Location: ".DOL_URL_ROOT.'/expedition/shipment.php?id='.((int) $origin_id));
				exit;
			}
		} else {
			$action = '';
			$object->fetch($id); // show shipment also after canceling modification
		}
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be 'include', not 'include_once'

	// Actions to build doc
	$upload_dir = $conf->expedition->dir_output.'/sending';
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';

	// Back to draft
	if ($action == 'setdraft' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setDraft($user, 0);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}
	// Reopen
	if ($action == 'reopen' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->reOpen();
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}

	// Set incoterm
	if ($action == 'set_incoterms' && isModEnabled('incoterm') && $permissiontoadd) {
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
	}

	if ($action == 'setref_customer' && $permissiontoadd) {
		$result = $object->fetch($id);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$result = $object->setValueFrom('ref_customer', GETPOST('ref_customer', 'alpha'), '', null, 'text', '', $user, 'SHIPMENT_MODIFY');
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$action = 'editref_customer';
		} else {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}

	if ($action == 'update_extras' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);
		$attribute_name = GETPOST('attribute', 'restricthtml');

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, $attribute_name);
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->updateExtraField($attribute_name, 'SHIPMENT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	// Create shipment
	if ($action == 'add' && $permissiontoadd) {
		$error = 0;

		$db->begin();

		$object->note = GETPOST('note', 'restricthtml');
		$object->note_private = GETPOST('note', 'restricthtml');
		$object->origin = $origin;
		$object->origin_id = $origin_id;
		$object->fk_project = GETPOSTINT('projectid');
		$object->weight = GETPOSTINT('weight') == '' ? "NULL" : GETPOSTINT('weight');
		$object->sizeH = GETPOSTINT('sizeH') == '' ? "NULL" : GETPOSTINT('sizeH');
		$object->sizeW = GETPOSTINT('sizeW') == '' ? "NULL" : GETPOSTINT('sizeW');
		$object->sizeS = GETPOSTINT('sizeS') == '' ? "NULL" : GETPOSTINT('sizeS');
		$object->size_units = GETPOSTINT('size_units');
		$object->weight_units = GETPOSTINT('weight_units');

		$product = new Product($db);

		// We will loop on each line of the original document to complete the shipping object with various info and quantity to deliver
		$classname = ucfirst($object->origin);
		$objectsrc = new $classname($db);
		$objectsrc->fetch($object->origin_id);

		$object->socid = $objectsrc->socid;
		$object->ref_customer = GETPOST('ref_customer', 'alpha');
		$object->model_pdf = GETPOST('model');
		$object->date_delivery = $date_delivery; // Date delivery planned
		$object->fk_delivery_address = $objectsrc->fk_delivery_address;
		$object->shipping_method_id = GETPOSTINT('shipping_method_id');
		$object->tracking_number = GETPOST('tracking_number', 'alpha');
		$object->note_private = GETPOST('note_private', 'restricthtml');
		$object->note_public = GETPOST('note_public', 'restricthtml');
		$object->fk_incoterms = GETPOSTINT('incoterm_id');
		$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

		$batch_line = array();
		$stockLine = array();
		$array_options = array();

		$num = count($objectsrc->lines);
		$totalqty = 0;

		$product_batch_used = array();

		for ($i = 0; $i < $num; $i++) {
			$idl = "idl".$i;

			$sub_qty = array();
			$subtotalqty = 0;

			$j = 0;

			$batch = "batchl".$i."_0";
			$stockLocation = "ent1".$i."_0";
			$qty = "qtyl".$i;

			$is_batch_or_serial = 0;
			if (!empty($objectsrc->lines[$i]->fk_product)) {
				$resultFetch = $product->fetch($objectsrc->lines[$i]->fk_product, '', '', '', 1, 1, 1);
				if ($resultFetch < 0) {
					setEventMessages($product->error, $product->errors, 'errors');
				}
				$is_batch_or_serial = $product->status_batch;
			}

			// If product need a batch or serial number
			if (isModEnabled('productbatch') && $objectsrc->lines[$i]->product_tobatch) {
				if (GETPOSTISSET($batch)) {
					//shipment line with batch-enable product
					$qty .= '_'.$j;
					while (GETPOSTISSET($batch)) {
						// save line of detail into sub_qty
						$sub_qty[$j]['q'] = price2num(GETPOST($qty, 'alpha'), 'MS'); // the qty we want to move for this stock record
						$sub_qty[$j]['id_batch'] = GETPOSTINT($batch); // the id into llx_product_batch of stock record to move
						$subtotalqty += $sub_qty[$j]['q'];

						//var_dump($qty);
						//var_dump($batch);
						//var_dump($sub_qty[$j]['q']);
						//var_dump($sub_qty[$j]['id_batch']);

						//var_dump($qty);var_dump($batch);var_dump($sub_qty[$j]['q']);var_dump($sub_qty[$j]['id_batch']);
						if ($is_batch_or_serial == 2 && ($sub_qty[$j]['q'] > 1 || ($sub_qty[$j]['q'] > 0 && in_array($sub_qty[$j]['id_batch'], $product_batch_used)))) {
							setEventMessages($langs->trans("TooManyQtyForSerialNumber", $product->ref, ''), null, 'errors');
							$totalqty = 0;
							break 2;
						}

						if ($is_batch_or_serial == 2 && $sub_qty[$j]['q'] > 0) {
							// we stock the batch id to test later if the same serial is shipped on another line for the same product
							$product_batch_used[$j] = $sub_qty[$j]['id_batch'];
						}

						$j++;
						$batch = "batchl".$i."_".$j;
						$qty = "qtyl".$i.'_'.$j;
					}

					$batch_line[$i]['detail'] = $sub_qty; // array of details
					$batch_line[$i]['qty'] = $subtotalqty;
					$batch_line[$i]['ix_l'] = GETPOSTINT($idl);

					$totalqty += $subtotalqty;
				} else {
					// No detail were provided for lots, so if a qty was provided, we can throw an error.
					if (GETPOST($qty)) {
						// We try to set an amount
						// Case we don't use the list of available qty for each warehouse/lot
						// GUI does not allow this yet
						setEventMessages($langs->trans("StockIsRequiredToChooseWhichLotToUse").' ('.$langs->trans("Line").' '.GETPOSTINT($idl).')', null, 'errors');
						$error++;
					}
				}
			} elseif (GETPOSTISSET($stockLocation)) {
				//shipment line from multiple stock locations
				$qty .= '_'.$j;
				while (GETPOSTISSET($stockLocation)) {
					// save sub line of warehouse
					$stockLine[$i][$j]['qty'] = price2num(GETPOST($qty, 'alpha'), 'MS');
					$stockLine[$i][$j]['warehouse_id'] = GETPOSTINT($stockLocation);
					$stockLine[$i][$j]['ix_l'] = GETPOSTINT($idl);

					$totalqty += price2num(GETPOST($qty, 'alpha'), 'MS');
					$subtotalqty += price2num(GETPOST($qty, 'alpha'), 'MS');

					$j++;
					$stockLocation = "ent1".$i."_".$j;
					$qty = "qtyl".$i.'_'.$j;
				}
			} else {
				$p = new Product($db);
				$res = $p->fetch($objectsrc->lines[$i]->fk_product);
				if ($res > 0) {
					if (GETPOST('entrepot_id', 'int') == -1) {
						$qty .= '_'.$j;
					}

					if ($p->stockable_product == Product::DISABLED_STOCK) {
						$w = new Entrepot($db);
						$Tw = $w->list_array();
						if (count($Tw) > 0) {
							$w_Id = array_keys($Tw);
							$stockLine[$i][$j]['qty'] = GETPOST($qty, 'int');

							// lorsque que l'on a le stock désactivé sur un produit/service
							// on force l'entrepot pour passer le test  d'ajout de ligne dans expedition.class.php
							//
							$stockLine[$i][$j]['warehouse_id'] = $w_Id[0];
							$stockLine[$i][$j]['ix_l'] = GETPOST($idl, 'int');
						} else {
							setEventMessage($langs->trans('NoWarehouseInBase'));
						}
					}
				}
				//shipment line for product with no batch management and no multiple stock location
				if (GETPOSTINT($qty) > 0) {
					$totalqty += price2num(GETPOST($qty, 'alpha'), 'MS');
					$subtotalqty = price2num(GETPOST($qty, 'alpha'), 'MS');
				}
			}

			// check qty shipped not greater than ordered
			if (getDolGlobalInt("MAIN_DONT_SHIP_MORE_THAN_ORDERED") && $subtotalqty > $objectsrc->lines[$i]->qty) {
				setEventMessages($langs->trans("ErrorTooMuchShipped", $i + 1), null, 'errors');
				$error++;
				continue;
			}

			// Extrafields
			$array_options[$i] = $extrafields->getOptionalsFromPost($object->table_element_line, $i);
			// Unset extrafield
			if (isset($extrafields->attributes[$object->table_element_line]['label']) && is_array($extrafields->attributes[$object->table_element_line]['label'])) {
				// Get extra fields
				foreach ($extrafields->attributes[$object->table_element_line]['label'] as $key => $value) {
					unset($_POST["options_".$key]);
				}
			}
		}

		//var_dump($batch_line[2]);
		if (($totalqty > 0 || getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS')) && !$error) {		// There is at least one thing to ship and no error
			for ($i = 0; $i < $num; $i++) {
				$qty = "qtyl".$i;

				if (!isset($batch_line[$i])) {
					// not batch mode
					if (isset($stockLine[$i])) {
						//shipment from multiple stock locations
						$nbstockline = count($stockLine[$i]);
						for ($j = 0; $j < $nbstockline; $j++) {
							if ($stockLine[$i][$j]['qty'] > 0 || ($stockLine[$i][$j]['qty'] == 0 && getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS'))) {
								$ret = $object->addline($stockLine[$i][$j]['warehouse_id'], $stockLine[$i][$j]['ix_l'], $stockLine[$i][$j]['qty'], $array_options[$i]);
								if ($ret < 0) {
									setEventMessages($object->error, $object->errors, 'errors');
									$error++;
								}
							}
						}
					} else {
						if (GETPOSTINT($qty) > 0 || getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS')) {
							$ent = "entl".$i;
							$idl = "idl".$i;
							$entrepot_id = is_numeric(GETPOSTINT($ent)) ? GETPOSTINT($ent) : GETPOSTINT('entrepot_id');
							if ($entrepot_id < 0) {
								$entrepot_id = '';
							}
							if (!($objectsrc->lines[$i]->fk_product > 0)) {
								$entrepot_id = 0;
							}

							$ret = $object->addline($entrepot_id, GETPOSTINT($idl), price2num(GETPOSTINT($qty), 'MS'), $array_options[$i]);
							if ($ret < 0) {
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
							}
						}
					}
				} else {
					// batch mode
					if ($batch_line[$i]['qty'] > 0 || ($batch_line[$i]['qty'] == 0 && getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS'))) {
						$ret = $object->addline_batch($batch_line[$i], $array_options[$i]);
						if ($ret < 0) {
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}
				}
			}
			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}

			if (!$error) {
				$ret = $object->create($user); // This create shipment (like Odoo picking) and lines of shipments. Stock movement will be done when validating or closing shipment.
				if ($ret <= 0) {
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
			}
		} elseif (!$error) {
			$labelfieldmissing = $langs->transnoentitiesnoconv("QtyToShip");
			if (isModEnabled('stock')) {
				$labelfieldmissing .= '/'.$langs->transnoentitiesnoconv("Warehouse");
			}
			setEventMessages($langs->trans("ErrorFieldRequired", $labelfieldmissing), null, 'errors');
			$error++;
		}

		if (!$error) {
			$db->commit();
			header("Location: card.php?id=".$object->id);
			exit;
		} else {
			$db->rollback();
			//$_GET["commande_id"] = GETPOSTINT('commande_id');
			$action = 'create';
		}
	} elseif ($action == 'create_delivery' && getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && $user->hasRight('expedition', 'delivery', 'creer')) {
		// Build a receiving receipt
		$db->begin();

		$result = $object->create_delivery($user);
		if ($result > 0) {
			$db->commit();

			header("Location: ".DOL_URL_ROOT.'/delivery/card.php?action=create_delivery&token='.newToken().'&id='.$result);
			exit;
		} else {
			$db->rollback();

			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_valid' && $confirm == 'yes' && ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'creer'))
		|| (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'shipping_advance', 'validate')))
	) {
		$object->fetch_thirdparty();

		$result = $object->valid($user);

		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		} else {
			// Define output language
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				if ($result < 0) {
					dol_print_error($db, $result);
				}
			}
		}
	} elseif ($action == 'confirm_cancel' && $confirm == 'yes' && $user->hasRight('expedition', 'supprimer')) {
		$also_update_stock = (GETPOST('alsoUpdateStock', 'alpha') ? 1 : 0);
		$result = $object->cancel(0, $also_update_stock);
		if ($result > 0) {
			$result = $object->setStatut(-1);
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->hasRight('expedition', 'supprimer')) {
		$also_update_stock = (GETPOST('alsoUpdateStock', 'alpha') ? 1 : 0);
		$result = $object->delete($user, 0, $also_update_stock);
		if ($result > 0) {
			header("Location: ".DOL_URL_ROOT.'/expedition/index.php');
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
		// TODO add alternative status
		//} elseif ($action == 'reopen' && ($user->hasRight('expedition', 'creer') || $user->hasRight('expedition', 'shipping_advance', 'validate')))
		//{
		//	$result = $object->setStatut(0);
		//	if ($result < 0)
		//	{
		//		setEventMessages($object->error, $object->errors, 'errors');
		//	}
		//}
	} elseif ($action == 'setdate_livraison' && $user->hasRight('expedition', 'creer')) {
		$datedelivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));

		$object->fetch($id);
		$result = $object->setDeliveryDate($user, $datedelivery);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif (in_array($action, array('settracking_number', 'settracking_url', 'settrueWeight', 'settrueWidth', 'settrueHeight', 'settrueDepth', 'setshipping_method_id')) && $user->hasRight('expedition', 'creer')) {
		// Action update
		$error = 0;

		if ($action == 'settracking_number') {
			$object->tracking_number = trim(GETPOST('tracking_number', 'alpha'));
		}
		if ($action == 'settracking_url') {
			$object->tracking_url = trim(GETPOST('tracking_url', 'restricthtml'));
		}
		if ($action == 'settrueWeight') {
			$object->trueWeight = GETPOSTINT('trueWeight');
			$object->weight_units = GETPOSTINT('weight_units');
		}
		if ($action == 'settrueWidth') {
			$object->trueWidth = GETPOSTINT('trueWidth');
		}
		if ($action == 'settrueHeight') {
			$object->trueHeight = GETPOSTINT('trueHeight');
			$object->size_units = GETPOSTINT('size_units');
		}
		if ($action == 'settrueDepth') {
			$object->trueDepth = GETPOSTINT('trueDepth');
		}
		if ($action == 'setshipping_method_id') {
			$object->shipping_method_id = GETPOSTINT('shipping_method_id');
		}

		if (!$error) {
			if ($object->update($user) >= 0) {
				header("Location: card.php?id=".$object->id);
				exit;
			}
			setEventMessages($object->error, $object->errors, 'errors');
		}

		$action = "";
	} elseif ($action == 'classifybilled' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setBilled();
		if ($result >= 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		}
		setEventMessages($object->error, $object->errors, 'errors');
	} elseif ($action == 'classifyclosed' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setClosed();
		if ($result >= 0) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		}
		setEventMessages($object->error, $object->errors, 'errors');
	} elseif ($action == 'deleteline' && !empty($line_id) && $permissiontoadd) {
		// delete a line
		$object->fetch($id);
		$lines = $object->lines;
		$line = new ExpeditionLigne($db);
		$line->fk_expedition = $object->id;

		$num_prod = count($lines);
		for ($i = 0; $i < $num_prod; $i++) {
			if ($lines[$i]->id == $line_id) {
				if (count($lines[$i]->details_entrepot) > 1) {
					// delete multi warehouse lines
					foreach ($lines[$i]->details_entrepot as $details_entrepot) {
						$line->id = $details_entrepot->line_id;
						if (!$error && $line->delete($user) < 0) {
							$error++;
						}
					}
				} else {
					// delete single warehouse line
					$line->id = $line_id;
					if (!$error && $line->delete($user) < 0) {
						$error++;
					}
				}
			}
			unset($_POST["lineid"]);
		}

		if (!$error) {
			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit();
		} else {
			setEventMessages($line->error, $line->errors, 'errors');
		}
	} elseif ($action == 'updateline' && $permissiontoadd && GETPOST('save')) {
		// Update a line
		// Clean parameters
		$qty = 0;
		$entrepot_id = 0;
		$batch_id = 0;

		$lines = $object->lines;
		$num_prod = count($lines);
		for ($i = 0; $i < $num_prod; $i++) {
			if ($lines[$i]->id == $line_id) {		// we have found line to update
				$update_done = false;
				$line = new ExpeditionLigne($db);
				$line->fk_expedition = $object->id;

				// Extrafields Lines
				$line->array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
				// Unset extrafield POST Data
				if (is_array($extrafields->attributes[$object->table_element_line]['label'])) {
					foreach ($extrafields->attributes[$object->table_element_line]['label'] as $key => $value) {
						unset($_POST["options_".$key]);
					}
				}
				$line->fk_product = $lines[$i]->fk_product;
				if (is_array($lines[$i]->detail_batch) && count($lines[$i]->detail_batch) > 0) {
					// line with lot
					foreach ($lines[$i]->detail_batch as $detail_batch) {
						$lotStock = new Productbatch($db);
						$batch = "batchl".$detail_batch->fk_expeditiondet."_".$detail_batch->fk_origin_stock;
						$qty = "qtyl".$detail_batch->fk_expeditiondet.'_'.$detail_batch->id;
						$batch_id = GETPOSTINT($batch);
						$batch_qty = GETPOSTINT($qty);
						if (!empty($batch_id)) {
							if ($lotStock->fetch($batch_id) > 0 && $line->fetch($detail_batch->fk_expeditiondet) > 0) {	// $line is ExpeditionLine
								if ($lines[$i]->entrepot_id != 0) {
									// allow update line entrepot_id if not multi warehouse shipping
									$line->entrepot_id = $lotStock->warehouseid;
								}

								// detail_batch can be an object with keys, or an array of ExpeditionLineBatch
								if (empty($line->detail_batch)) {
									$line->detail_batch = new stdClass();
								}

								$line->detail_batch->fk_origin_stock = $batch_id;
								$line->detail_batch->batch = $lotStock->batch;
								$line->detail_batch->id = $detail_batch->id;
								$line->detail_batch->entrepot_id = $lotStock->warehouseid;
								$line->detail_batch->qty = $batch_qty;
								if ($line->update($user) < 0) {
									setEventMessages($line->error, $line->errors, 'errors');
									$error++;
								} else {
									$update_done = true;
								}
							} else {
								setEventMessages($lotStock->error, $lotStock->errors, 'errors');
								$error++;
							}
						}
						unset($_POST[$batch]);
						unset($_POST[$qty]);
					}
					// add new batch
					$lotStock = new Productbatch($db);
					$batch = "batchl".$line_id."_0";
					$qty = "qtyl".$line_id."_0";
					$batch_id = GETPOSTINT($batch);
					$batch_qty = GETPOSTINT($qty);
					$lineIdToAddLot = 0;
					if ($batch_qty > 0 && !empty($batch_id)) {
						if ($lotStock->fetch($batch_id) > 0) {
							// check if lotStock warehouse id is same as line warehouse id
							if ($lines[$i]->entrepot_id > 0) {
								// single warehouse shipment line
								if ($lines[$i]->entrepot_id == $lotStock->warehouseid) {
									$lineIdToAddLot = $line_id;
								}
							} elseif (count($lines[$i]->details_entrepot) > 1) {
								// multi warehouse shipment lines
								foreach ($lines[$i]->details_entrepot as $detail_entrepot) {
									if ($detail_entrepot->entrepot_id == $lotStock->warehouseid) {
										$lineIdToAddLot = $detail_entrepot->line_id;
									}
								}
							}
							if ($lineIdToAddLot) {
								// add lot to existing line
								if ($line->fetch($lineIdToAddLot) > 0) {
									$line->detail_batch->fk_origin_stock = $batch_id;
									$line->detail_batch->batch = $lotStock->batch;
									$line->detail_batch->entrepot_id = $lotStock->warehouseid;
									$line->detail_batch->qty = $batch_qty;
									if ($line->update($user) < 0) {
										setEventMessages($line->error, $line->errors, 'errors');
										$error++;
									} else {
										$update_done = true;
									}
								} else {
									setEventMessages($line->error, $line->errors, 'errors');
									$error++;
								}
							} else {
								// create new line with new lot
								$line->origin_line_id = $lines[$i]->origin_line_id;
								$line->entrepot_id = $lotStock->warehouseid;
								$line->detail_batch[0] = new ExpeditionLineBatch($db);
								$line->detail_batch[0]->fk_origin_stock = $batch_id;
								$line->detail_batch[0]->batch = $lotStock->batch;
								$line->detail_batch[0]->entrepot_id = $lotStock->warehouseid;
								$line->detail_batch[0]->qty = $batch_qty;
								if ($object->create_line_batch($line, $line->array_options) < 0) {
									setEventMessages($object->error, $object->errors, 'errors');
									$error++;
								} else {
									$update_done = true;
								}
							}
						} else {
							setEventMessages($lotStock->error, $lotStock->errors, 'errors');
							$error++;
						}
					}
				} else {
					if ($lines[$i]->fk_product > 0) {
						// line without lot
						if ($lines[$i]->entrepot_id == 0) {
							// single warehouse shipment line or line in several warehouses context but with warehouse not defined
							$stockLocation = "entl".$line_id;
							$qty = "qtyl".$line_id;
							$line->id = $line_id;
							$line->entrepot_id = GETPOSTINT((string) $stockLocation);
							$line->qty = GETPOSTFLOAT($qty);
							if ($line->update($user) < 0) {
								setEventMessages($line->error, $line->errors, 'errors');
								$error++;
							}
							unset($_POST[$stockLocation]);
							unset($_POST[$qty]);
						} elseif ($lines[$i]->entrepot_id > 0) {
							// single warehouse shipment line
							$stockLocation = "entl".$line_id;
							$qty = "qtyl".$line_id;
							$line->id = $line_id;
							$line->entrepot_id = GETPOSTINT($stockLocation);
							$line->qty = GETPOSTFLOAT($qty);
							if ($line->update($user) < 0) {
								setEventMessages($line->error, $line->errors, 'errors');
								$error++;
							}
							unset($_POST[$stockLocation]);
							unset($_POST[$qty]);
						} elseif (count($lines[$i]->details_entrepot) > 1) {
							// multi warehouse shipment lines
							foreach ($lines[$i]->details_entrepot as $detail_entrepot) {
								if (!$error) {
									$stockLocation = "entl".$detail_entrepot->line_id;
									$qty = "qtyl".$detail_entrepot->line_id;
									$warehouse = GETPOSTINT($stockLocation);
									if (!empty($warehouse)) {
										$line->id = $detail_entrepot->line_id;
										$line->entrepot_id = $warehouse;
										$line->qty = GETPOSTFLOAT($qty);
										if ($line->update($user) < 0) {
											setEventMessages($line->error, $line->errors, 'errors');
											$error++;
										} else {
											$update_done = true;
										}
									}
									unset($_POST[$stockLocation]);
									unset($_POST[$qty]);
								}
							}
						} elseif (!isModEnabled('stock') && empty($conf->productbatch->enabled)) { // both product batch and stock are not activated.
							$qty = "qtyl".$line_id;
							$line->id = $line_id;
							$line->qty = GETPOSTFLOAT($qty);
							$line->entrepot_id = 0;
							if ($line->update($user) < 0) {
								setEventMessages($line->error, $line->errors, 'errors');
								$error++;
							} else {
								$update_done = true;
							}
							unset($_POST[$qty]);
						}
					} else {
						// Product no predefined
						$qty = "qtyl".$line_id;
						$line->id = $line_id;
						$line->qty = GETPOSTFLOAT($qty);
						$line->entrepot_id = 0;
						if ($line->update($user) < 0) {
							setEventMessages($line->error, $line->errors, 'errors');
							$error++;
						} else {
							$update_done = true;
						}
						unset($_POST[$qty]);
					}
				}

				if (empty($update_done)) {
					$line->id = $lines[$i]->id;
					$line->insertExtraFields();
				}
			}
		}

		unset($_POST["lineid"]);

		if (!$error) {
			if (!getDolGlobalString('MAIN_DISABLE_PDF_AUTOUPDATE')) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if (getDolGlobalInt('MAIN_MULTILANGS') && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // To redisplay the form being edited
			exit();
		}
	} elseif ($action == 'updateline' && $permissiontoadd && GETPOST('cancel', 'alpha') == $langs->trans("Cancel")) {
		header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id); // To redisplay the form being edited
		exit();
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	if (empty($id)) {
		$id = $facid;
	}
	$triggersendname = 'SHIPPING_SENTBYMAIL';
	$paramname = 'id';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_SHIPMENT_TO';
	$mode = 'emailfromshipment';
	$trackid = 'shi'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';
}


/*
 * View
 */

$title = $object->ref.' - '.$langs->trans("Shipment");
if ($action == 'create2') {
	$title = $langs->trans("CreateShipment");
}
$help_url = 'EN:Module_Shipments|FR:Module_Expéditions|ES:M&oacute;dulo_Expediciones|DE:Modul_Lieferungen';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-expedition page-card');

if (empty($action)) {
	$action = 'view';
}

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$product_static = new Product($db);
$shipment_static = new Expedition($db);
$warehousestatic = new Entrepot($db);

if ($action == 'create2') {
	print load_fiche_titre($langs->trans("CreateShipment"), '', 'dolly');

	print '<br>'.$langs->trans("ShipmentCreationIsDoneFromOrder");
	$action = '';
	$id = '';
	$ref = '';
}

// Mode creation.
if ($action == 'create') {
	$expe = new Expedition($db);

	print load_fiche_titre($langs->trans("CreateShipment"), '', 'dolly');

	if (!$origin) {
		setEventMessages($langs->trans("ErrorBadParameters"), null, 'errors');
	}

	if ($origin) {
		$classname = ucfirst($origin);

		$object = new $classname($db);
		if ($object->fetch($origin_id)) {	// This include the fetch_lines
			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$author = new User($db);
			$author->fetch($object->user_author_id);

			if (isModEnabled('stock')) {
				$entrepot = new Entrepot($db);
			}

			print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="origin" value="'.$origin.'">';
			print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
			if (GETPOSTINT('entrepot_id')) {
				print '<input type="hidden" name="entrepot_id" value="'.GETPOSTINT('entrepot_id').'">';
			}

			print dol_get_fiche_head('');

			print '<table class="border centpercent">';

			// Ref
			print '<tr><td class="titlefieldcreate fieldrequired">';
			if ($origin == 'commande' && isModEnabled('order')) {
				print $langs->trans("RefOrder");
			}
			if ($origin == 'propal' && isModEnabled("propal")) {
				print $langs->trans("RefProposal");
			}
			print '</td><td colspan="3">';
			print $object->getNomUrl(1);
			print '</td>';
			print "</tr>\n";

			// Ref client
			print '<tr><td>';
			if ($origin == 'commande') {
				print $langs->trans('RefCustomerOrder');
			} elseif ($origin == 'propal') {
				print $langs->trans('RefCustomerOrder');
			} else {
				print $langs->trans('RefCustomer');
			}
			print '</td><td colspan="3">';
			print '<input type="text" name="ref_customer" value="'.$object->ref_client.'" />';
			print '</td>';
			print '</tr>';

			// Tiers
			print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Company').'</td>';
			print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
			print '</tr>';

			// Project
			if (isModEnabled('project')) {
				$projectid = GETPOSTINT('projectid') ? GETPOSTINT('projectid') : 0;
				if (empty($projectid) && !empty($object->fk_project)) {
					$projectid = $object->fk_project;
				}
				if ($origin == 'project') {
					$projectid = ($originid ? $originid : 0);
				}

				$langs->load("projects");
				print '<tr>';
				print '<td>'.$langs->trans("Project").'</td><td colspan="2">';
				print img_picto('', 'project', 'class="pictofixedwidth"');
				$numprojet = $formproject->select_projects($soc->id, $projectid, 'projectid', 0);
				print ' <a class="paddingleft" href="'.DOL_URL_ROOT.'/projet/card.php?socid='.$soc->id.'&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'"><span class="fa fa-plus-circle valignmiddle"></span></a>';
				print '</td>';
				print '</tr>';
			}

			// Date delivery planned
			print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
			print '<td colspan="3">';
			print img_picto('', 'action', 'class="pictofixedwidth"');
			$date_delivery = ($date_delivery ? $date_delivery : $object->delivery_date); // $date_delivery comes from GETPOST
			print $form->selectDate($date_delivery ? $date_delivery : -1, 'date_delivery', 1, 1, 1);
			print "</td>\n";
			print '</tr>';

			// Note Public
			print '<tr><td>'.$langs->trans("NotePublic").'</td>';
			print '<td colspan="3">';
			$doleditor = new DolEditor('note_public', $object->note_public, '', 60, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PUBLIC') ? 0 : 1, ROWS_3, '90%');
			print $doleditor->Create(1);
			print "</td></tr>";

			// Note Private
			if ($object->note_private && !$user->socid) {
				print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
				print '<td colspan="3">';
				$doleditor = new DolEditor('note_private', $object->note_private, '', 60, 'dolibarr_notes', 'In', 0, false, !getDolGlobalString('FCKEDITOR_ENABLE_NOTE_PRIVATE') ? 0 : 1, ROWS_3, '90%');
				print $doleditor->Create(1);
				print "</td></tr>";
			}

			// Weight
			print '<tr><td>';
			print $langs->trans("Weight");
			print '</td><td colspan="3">';
			print img_picto('', 'fa-balance-scale', 'class="pictofixedwidth"');
			print '<input name="weight" size="4" value="'.GETPOSTINT('weight').'"> ';
			$text = $formproduct->selectMeasuringUnits("weight_units", "weight", GETPOSTINT('weight_units'), 0, 2);
			$htmltext = $langs->trans("KeepEmptyForAutoCalculation");
			print $form->textwithpicto($text, $htmltext);
			print '</td></tr>';
			// Dim
			print '<tr><td>';
			print $langs->trans("Width").' x '.$langs->trans("Height").' x '.$langs->trans("Depth");
			print ' </td><td colspan="3">';
			print img_picto('', 'fa-ruler', 'class="pictofixedwidth"');
			print '<input name="sizeW" size="4" value="'.GETPOSTINT('sizeW').'">';
			print ' x <input name="sizeH" size="4" value="'.GETPOSTINT('sizeH').'">';
			print ' x <input name="sizeS" size="4" value="'.GETPOSTINT('sizeS').'">';
			print ' ';
			$text = $formproduct->selectMeasuringUnits("size_units", "size", GETPOSTINT('size_units'), 0, 2);
			$htmltext = $langs->trans("KeepEmptyForAutoCalculation");
			print $form->textwithpicto($text, $htmltext);
			print '</td></tr>';

			// Delivery method
			print "<tr><td>".$langs->trans("DeliveryMethod")."</td>";
			print '<td colspan="3">';
			$expe->fetch_delivery_methods();
			print img_picto('', 'dolly', 'class="pictofixedwidth"');
			print $form->selectarray("shipping_method_id", $expe->meths, GETPOSTINT('shipping_method_id'), 1, 0, 0, "", 1);
			if ($user->admin) {
				print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
			}
			print "</td></tr>\n";

			// Tracking number
			print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
			print '<td colspan="3">';
			print img_picto('', 'barcode', 'class="pictofixedwidth"');
			print '<input name="tracking_number" size="20" value="'.GETPOST('tracking_number', 'alpha').'">';
			print "</td></tr>\n";

			// Other attributes
			$parameters = array('objectsrc' => isset($objectsrc) ? $objectsrc : '', 'colspan' => ' colspan="3"', 'cols' => '3', 'socid' => $socid);
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $expe, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			if (empty($reshook)) {
				// copy from order
				if ($object->fetch_optionals() > 0) {
					$expe->array_options = array_merge($expe->array_options, $object->array_options);
				}
				print $expe->showOptionals($extrafields, 'edit', $parameters);
			}


			// Incoterms
			if (isModEnabled('incoterm')) {
				print '<tr>';
				print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $object->label_incoterms, 1).'</label></td>';
				print '<td colspan="3" class="maxwidthonsmartphone">';
				print img_picto('', 'incoterm', 'class="pictofixedwidth"');
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''));
				print '</td></tr>';
			}

			// Document model
			include_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
			$list = ModelePdfExpedition::liste_modeles($db);
			if (is_countable($list) && count($list) > 1) {
				print "<tr><td>".$langs->trans("DefaultModel")."</td>";
				print '<td colspan="3">';
				print img_picto('', 'pdf', 'class="pictofixedwidth"');
				print $form->selectarray('model', $list, $conf->global->EXPEDITION_ADDON_PDF);
				print "</td></tr>\n";
			}

			print "</table>";

			print dol_get_fiche_end();


			// Shipment lines

			$numAsked = count($object->lines);

			print '<script type="text/javascript">'."\n";
			print 'jQuery(document).ready(function() {'."\n";
			print 'jQuery("#autofill").click(function() {';
			$i = 0;
			while ($i < $numAsked) {
				print 'jQuery("#qtyl'.$i.'").val(jQuery("#qtyasked'.$i.'").val() - jQuery("#qtydelivered'.$i.'").val());'."\n";
				if (isModEnabled('productbatch')) {
					print 'jQuery("#qtyl'.$i.'_'.$i.'").val(jQuery("#qtyasked'.$i.'").val() - jQuery("#qtydelivered'.$i.'").val());'."\n";
				}
				$i++;
			}
			print 'return false; });'."\n";
			print 'jQuery("#autoreset").click(function() { console.log("Reset values to 0"); jQuery(".qtyl").val(0);'."\n";
			print 'return false; });'."\n";
			print '});'."\n";
			print '</script>'."\n";

			print '<br>';

			print '<table class="noborder centpercent">';

			// Load shipments already done for same order
			$object->loadExpeditions();


			$alreadyQtyBatchSetted = $alreadyQtySetted = array();

			if ($numAsked) {
				print '<tr class="liste_titre">';
				print '<td>'.$langs->trans("Description").'</td>';
				print '<td class="center">'.$langs->trans("QtyOrdered").'</td>';
				print '<td class="center">'.$langs->trans("QtyShipped").'</td>';
				print '<td class="center">'.$langs->trans("QtyToShip");
				if (empty($conf->productbatch->enabled)) {
					print '<br><a href="#" id="autofill" class="opacitymedium link cursor cursorpointer">'.img_picto($langs->trans("Autofill"), 'autofill', 'class="paddingrightonly"').'</a>';
					print ' / ';
				} else {
					print '<br>';
				}
				print '<span id="autoreset" class="opacitymedium link cursor cursorpointer">'.img_picto($langs->trans("Reset"), 'eraser').'</span>';
				print '</td>';
				if (isModEnabled('stock')) {
					if (empty($conf->productbatch->enabled)) {
						print '<td class="left">'.$langs->trans("Warehouse").' ('.$langs->trans("Stock").')</td>';
					} else {
						print '<td class="left">'.$langs->trans("Warehouse").' / '.$langs->trans("Batch").' ('.$langs->trans("Stock").')</td>';
					}
				}
				if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
					print '<td class="left">'.$langs->trans('StockEntryDate').'</td>';
				}
				print "</tr>\n";
			}

			$warehouse_id = GETPOSTINT('entrepot_id');
			$warehousePicking = array();
			// get all warehouse children for picking
			if ($warehouse_id > 0) {
				$warehousePicking[] = $warehouse_id;
				$warehouseObj = new Entrepot($db);
				$warehouseObj->get_children_warehouses($warehouse_id, $warehousePicking);
			}

			$indiceAsked = 0;
			while ($indiceAsked < $numAsked) {
				$product = new Product($db);

				$line = $object->lines[$indiceAsked];

				$parameters = array('i' => $indiceAsked, 'line' => $line, 'num' => $numAsked);
				$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $object, $action);
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				if (empty($reshook)) {
					// Show product and description
					$type = $line->product_type ? $line->product_type : $line->fk_product_type;
					// Try to enhance type detection using date_start and date_end for free lines where type
					// was not saved.
					if (!empty($line->date_start)) {
						$type = 1;
					}
					if (!empty($line->date_end)) {
						$type = 1;
					}

					print '<!-- line for order line '.$line->id.' -->'."\n";
					print '<tr class="oddeven" id="row-'.$line->id.'">'."\n";

					// Product label
					if ($line->fk_product > 0) {  // If predefined product
						$res = $product->fetch($line->fk_product);
						if ($res < 0) {
							dol_print_error($db, $product->error, $product->errors);
						}
						$product->load_stock('warehouseopen'); // Load all $product->stock_warehouse[idwarehouse]->detail_batch
						//var_dump($product->stock_warehouse[1]);

						print '<td>';
						print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne

						// Show product and description
						$product_static->type = $line->fk_product_type;
						$product_static->id = $line->fk_product;
						$product_static->ref = $line->ref;
						$product_static->status = $line->product_tosell;
						$product_static->status_buy = $line->product_tobuy;
						$product_static->status_batch = $line->product_tobatch;

						$showdescinproductdesc = getDolGlobalString('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE');

						$text = $product_static->getNomUrl(1);
						$text .= ' - '.(!empty($line->label) ? $line->label : $line->product_label);
						$description = ($showdescinproductdesc ? '' : dol_htmlentitiesbr($line->desc));
						$description .= empty($product->stockable_product) ? $langs->trans('StockDisabled') : $langs->trans('StockEnabled') ;
						print $form->textwithtooltip($text, $description, 3, '', '', $i);

						// Show range
						print_date_range($db->jdate($line->date_start), $db->jdate($line->date_end));

						// Add description in form
						if ($showdescinproductdesc) {
							print ($line->desc && $line->desc != $line->product_label) ? '<br>'.dol_htmlentitiesbr($line->desc) : '';
						}

						print '</td>';
					} else {
						print "<td>";
						if ($type == 1) {
							$text = img_object($langs->trans('Service'), 'service');
						} else {
							$text = img_object($langs->trans('Product'), 'product');
						}

						if (!empty($line->label)) {
							$text .= ' <strong>'.$line->label.'</strong>';
							print $form->textwithtooltip($text, $line->desc, 3, '', '', $i);
						} else {
							print $text.' '.nl2br($line->desc);
						}

						// Show range
						print_date_range($db->jdate($line->date_start), $db->jdate($line->date_end));
						print "</td>\n";
					}

					// unit of order
					$unit_order = '';
					if (getDolGlobalString('PRODUCT_USE_UNITS')) {
						$unit_order = measuringUnitString($line->fk_unit);
					}

					// Qty
					print '<td class="center">'.$line->qty;
					print '<input name="qtyasked'.$indiceAsked.'" id="qtyasked'.$indiceAsked.'" type="hidden" value="'.$line->qty.'">';
					print ''.$unit_order.'</td>';
					$qtyProdCom = $line->qty;

					// Qty already shipped
					print '<td class="center">';
					$quantityDelivered = isset($object->expeditions[$line->id]) ? $object->expeditions[$line->id] : '';
					print $quantityDelivered;
					print '<input name="qtydelivered'.$indiceAsked.'" id="qtydelivered'.$indiceAsked.'" type="hidden" value="'.$quantityDelivered.'">';
					print ''.$unit_order.'</td>';

					// Qty to ship
					$quantityAsked = $line->qty;
					if ($line->product_type == 1 && !getDolGlobalString('STOCK_SUPPORTS_SERVICES') && !getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
						$quantityToBeDelivered = 0;
					} else {
						if (is_numeric($quantityDelivered)) {
							$quantityToBeDelivered = $quantityAsked - $quantityDelivered;
						} else {
							$quantityToBeDelivered = $quantityAsked;
						}
					}

					$warehouseObject = null;
					if (count($warehousePicking) == 1 || !($line->fk_product > 0) || !isModEnabled('stock')) {     // If warehouse was already selected or if product is not a predefined, we go into this part with no multiwarehouse selection
						print '<!-- Case warehouse already known or product not a predefined product -->';
						//ship from preselected location
						$stock = + (isset($product->stock_warehouse[$warehouse_id]->real) ? $product->stock_warehouse[$warehouse_id]->real : 0); // Convert to number
						if (getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
							$deliverableQty = $quantityToBeDelivered;
						} else {
							$deliverableQty = min($quantityToBeDelivered, $stock);
						}
						if ($deliverableQty < 0) {
							$deliverableQty = 0;
						}
						if (empty($conf->productbatch->enabled) || !$product->hasbatch()) {
							// Quantity to send
							print '<td class="center">';
							if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES') || getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
								if (GETPOSTINT('qtyl'.$indiceAsked)) {
									$deliverableQty = GETPOSTINT('qtyl'.$indiceAsked);
								}
								print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
								print '<input name="qtyl'.$indiceAsked.'" id="qtyl'.$indiceAsked.'" class="qtyl right" type="text" size="4" value="'.$deliverableQty.'">';
							} else {
								if (getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS')) {
									print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
									print '<input name="qtyl'.$indiceAsked.'" id="qtyl'.$indiceAsked.'" type="hidden" value="0">';
								}

								print $langs->trans("NA");
							}
							print '</td>';

							// Stock
							if (isModEnabled('stock')) {
								print '<td class="left">';
								if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {   // Type of product need stock change ?
									// Show warehouse combo list
									$ent = "entl".$indiceAsked;
									$idl = "idl".$indiceAsked;
									$tmpentrepot_id = is_numeric(GETPOST($ent)) ? GETPOSTINT($ent) : $warehouse_id;
									if ($line->fk_product > 0) {
										print '<!-- Show warehouse selection -->';

										$stockMin = false;
										if (!getDolGlobalInt('STOCK_ALLOW_NEGATIVE_TRANSFER')) {
											$stockMin = 0;
										}
										if ($product->stockable_product == Product::ENABLED_STOCK){
											print $formproduct->selectWarehouses($tmpentrepot_id, 'entl'.$indiceAsked, '', 1, 0, $line->fk_product, '', 1, 0, array(), 'minwidth200', '', 1, $stockMin, 'stock DESC, e.ref');
										} else {
											print img_warning().' '.$langs->trans('StockDisabled') ;
										}
										if ($tmpentrepot_id > 0 && $tmpentrepot_id == $warehouse_id) {
											//print $stock.' '.$quantityToBeDelivered;
											if ($stock < $quantityToBeDelivered) {
												print ' '.img_warning($langs->trans("StockTooLow")); // Stock too low for this $warehouse_id but you can change warehouse
											}
										}
									}
								} else {
									print '<span class="opacitymedium">('.$langs->trans("Service").')</span><input name="entl'.$indiceAsked.'" id="entl'.$indiceAsked.'" type="hidden" value="0">';
								}
								print '</td>';
							}
							if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
								print '<td></td>';
							} //StockEntrydate
							print "</tr>\n";

							// Show subproducts of product
							if (getDolGlobalString('PRODUIT_SOUSPRODUITS') && $line->fk_product > 0) {
								$product->get_sousproduits_arbo();
								$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
								if (count($prods_arbo) > 0) {
									foreach ($prods_arbo as $key => $value) {
										//print $value[0];
										$img = '';
										if ($value['stock'] < $value['stock_alert']) {
											$img = img_warning($langs->trans("StockTooLow"));
										}
										print "<tr class=\"oddeven\"><td>&nbsp; &nbsp; &nbsp; ->
											<a href=\"".DOL_URL_ROOT."/product/card.php?id=".$value['id']."\">".$value['fullpath']."
											</a> (".$value['nb'].")</td><td class=\"center\"> ".$value['nb_total']."</td><td>&nbsp;</td><td>&nbsp;</td>
											<td class=\"center\">".$value['stock']." ".$img."</td>";
										if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
											print '<td></td>';
										} //StockEntrydate
										print "</tr>";
									}
								}
							}
						} else {
							// Product need lot
							print '<td></td><td></td>';
							if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
								print '<td></td>';
							} //StockEntrydate
							print '</tr>'; // end line and start a new one for lot/serial
							print '<!-- Case product need lot -->';

							$staticwarehouse = new Entrepot($db);
							if ($warehouse_id > 0) {
								$staticwarehouse->fetch($warehouse_id);
							}

							$subj = 0;
							// Define nb of lines suggested for this order line
							$nbofsuggested = 0;
							if (is_object($product->stock_warehouse[$warehouse_id]) && count($product->stock_warehouse[$warehouse_id]->detail_batch)) {
								foreach ($product->stock_warehouse[$warehouse_id]->detail_batch as $dbatch) {
									$nbofsuggested++;
								}
							}
							print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
							if (is_object($product->stock_warehouse[$warehouse_id]) && count($product->stock_warehouse[$warehouse_id]->detail_batch)) {
								foreach ($product->stock_warehouse[$warehouse_id]->detail_batch as $dbatch) {	// $dbatch is instance of Productbatch
									//var_dump($dbatch);
									$batchStock = + $dbatch->qty; // To get a numeric
									$deliverableQty = min($quantityToBeDelivered, $batchStock);

									// Now we will check if we have to reduce the deliverableQty by taking into account the qty already suggested in previous line
									if (isset($alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)])) {
										$deliverableQty = min($quantityToBeDelivered, $batchStock - $alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)]);
									} else {
										if (!isset($alreadyQtyBatchSetted[$line->fk_product])) {
											$alreadyQtyBatchSetted[$line->fk_product] = array();
										}

										if (!isset($alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch])) {
											$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch] = array();
										}

										$deliverableQty = min($quantityToBeDelivered, $batchStock);
									}

									if ($deliverableQty < 0) $deliverableQty = 0;

									$inputName = 'qtyl'.$indiceAsked.'_'.$subj;
									if (GETPOSTISSET($inputName)) {
										$deliverableQty = GETPOST($inputName, 'int');
									}

									$tooltipClass = $tooltipTitle = '';
									if (!empty($alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)])) {
										$tooltipClass = ' classfortooltip';
										$tooltipTitle = $langs->trans('StockQuantitiesAlreadyAllocatedOnPreviousLines').' : '.$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)];
									} else {
										$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)] = 0 ;
									}
									$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)] = $deliverableQty + $alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)];

									print '<!-- subj='.$subj.'/'.$nbofsuggested.' --><tr '.((($subj + 1) == $nbofsuggested) ? 'oddeven' : '').'>';
									print '<td colspan="3" ></td><td class="center">';
									print '<input class="qtyl '.$tooltipClass.' right" title="'.$tooltipTitle.'" name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="'.$deliverableQty.'">';
									print '</td>';

									print '<!-- Show details of lot -->';
									print '<td class="left">';

									print $staticwarehouse->getNomUrl(0).' / ';

									print '<input name="batchl'.$indiceAsked.'_'.$subj.'" type="hidden" value="'.$dbatch->id.'">';

									$detail = '';
									$detail .= $langs->trans("Batch").': '.$dbatch->batch;
									if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY') && !empty($dbatch->sellby)) {
										$detail .= ' - '.$langs->trans("SellByDate").': '.dol_print_date($dbatch->sellby, "day");
									}
									if (!getDolGlobalString('PRODUCT_DISABLE_EATBY') && !empty($dbatch->eatby)) {
										$detail .= ' - '.$langs->trans("EatByDate").': '.dol_print_date($dbatch->eatby, "day");
									}
									$detail .= ' - '.$langs->trans("Qty").': '.$dbatch->qty;
									$detail .= '<br>';
									print $detail;

									$quantityToBeDelivered -= $deliverableQty;
									if ($quantityToBeDelivered < 0) {
										$quantityToBeDelivered = 0;
									}
									$subj++;
									print '</td>';
									if (getDolGlobalInt('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
										print '<td>'.dol_print_date($dbatch->context['stock_entry_date'], 'day').'</td>'; //StockEntrydate
									}
									print '</tr>';
								}
							} else {
								print '<!-- Case there is no details of lot at all -->';
								print '<tr class="oddeven"><td colspan="3"></td><td class="center">';
								print '<input class="qtyl right" name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="0" disabled="disabled"> ';
								print '</td>';

								print '<td class="left">';
								print img_warning().' '.$langs->trans("NoProductToShipFoundIntoStock", $staticwarehouse->label);
								print '</td>';
								if (getDolGlobalInt('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
									print '<td></td>';
								} //StockEntrydate
								print '</tr>';
							}
						}
					} else {
						// ship from multiple locations
						if (empty($conf->productbatch->enabled) || !$product->hasbatch()) {
							print '<!-- Case warehouse not already known and product does not need lot -->';
							print '<td></td><td></td>';
							if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
								print '<td></td>';
							}//StockEntrydate
							print '</tr>'."\n"; // end line and start a new one for each warehouse

							print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
							$subj = 0;
							// Define nb of lines suggested for this order line
							$nbofsuggested = 0;

							foreach ($product->stock_warehouse as $warehouse_id => $stock_warehouse) {
								if ($stock_warehouse->real > 0 || !empty($conf->global->STOCK_ALLOW_NEGATIVE_TRANSFER)) {
									$nbofsuggested++;
								}
							}
							$tmpwarehouseObject = new Entrepot($db);
							foreach ($product->stock_warehouse as $warehouse_id => $stock_warehouse) {    // $stock_warehouse is product_stock
								$var = $subj % 2;
								if (!empty($warehousePicking) && !in_array($warehouse_id, $warehousePicking)) {
									// if a warehouse was selected by user, picking is limited to this warehouse and his children
									continue;
								}

								$tmpwarehouseObject->fetch($warehouse_id);
								if ($stock_warehouse->real > 0 || !empty($conf->global->STOCK_ALLOW_NEGATIVE_TRANSFER)) {
									$stock = + $stock_warehouse->real; // Convert it to number
									$deliverableQty = min($quantityToBeDelivered, $stock);
									$deliverableQty = max(0, $deliverableQty);
									// Quantity to send
									print '<!-- subj='.$subj.'/'.$nbofsuggested.' --><tr '.((($subj + 1) == $nbofsuggested) ? 'oddeven' : '').'>';
									print '<td colspan="3" ></td><td class="center"><!-- qty to ship (no lot management for product line indiceAsked='.$indiceAsked.') -->';
									if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES') || getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
										if (isset($alreadyQtySetted[$line->fk_product][intval($warehouse_id)])) {
											$deliverableQty = min($quantityToBeDelivered, $stock - $alreadyQtySetted[$line->fk_product][intval($warehouse_id)]);
										} else {
											if (!isset($alreadyQtySetted[$line->fk_product])) {
												$alreadyQtySetted[$line->fk_product] = array();
											}

											$deliverableQty = min($quantityToBeDelivered, $stock);
										}

										if ($deliverableQty < 0) {
											$deliverableQty = 0;
										}

										$tooltipClass = $tooltipTitle = '';
										if (!empty($alreadyQtySetted[$line->fk_product][intval($warehouse_id)])) {
											$tooltipClass = ' classfortooltip';
											$tooltipTitle = $langs->trans('StockQuantitiesAlreadyAllocatedOnPreviousLines').' : '.$alreadyQtySetted[$line->fk_product][intval($warehouse_id)];
										} else {
											$alreadyQtySetted[$line->fk_product][intval($warehouse_id)] = 0;
										}

										$alreadyQtySetted[$line->fk_product][intval($warehouse_id)] = $deliverableQty + $alreadyQtySetted[$line->fk_product][intval($warehouse_id)];

										$inputName = 'qtyl'.$indiceAsked.'_'.$subj;
										if (GETPOSTISSET($inputName)) {
											$deliverableQty = GETPOSTINT($inputName);
										}

										print '<input class="qtyl'.$tooltipClass.' right" title="'.$tooltipTitle.'" name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$deliverableQty.'">';
										print '<input name="ent1'.$indiceAsked.'_'.$subj.'" type="hidden" value="'.$warehouse_id.'">';
									} else {
										if (getDolGlobalString('SHIPMENT_GETS_ALL_ORDER_PRODUCTS')) {
											print '<input name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'" type="hidden" value="0">';
										}

										print $langs->trans("NA");
									}
									print '</td>';

									// Stock
									if (isModEnabled('stock')) {
										print '<td class="left">';
										if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
											if ($product->stockable_product == Product::ENABLED_STOCK){
												print $tmpwarehouseObject->getNomUrl(0).' ';
												print '<!-- Show details of stock -->';
												print '('.$stock.')';
											} else {
												print img_warning().' '.$langs->trans('StockDisabled') ;
											}
										} else {
											print '<span class="opacitymedium">('.$langs->trans("Service").')</span>';
										}
										print '</td>';
									}
									$quantityToBeDelivered -= $deliverableQty;
									if ($quantityToBeDelivered < 0) {
										$quantityToBeDelivered = 0;
									}
									$subj++;
									if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
										print '<td></td>';
									}//StockEntrydate
									print "</tr>\n";
								}
							}
							// Show subproducts of product (not recommended)
							if (getDolGlobalString('PRODUIT_SOUSPRODUITS') && $line->fk_product > 0) {
								$product->get_sousproduits_arbo();
								$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
								if (count($prods_arbo) > 0) {
									foreach ($prods_arbo as $key => $value) {
										//print $value[0];
										$img = '';
										if ($value['stock'] < $value['stock_alert']) {
											$img = img_warning($langs->trans("StockTooLow"));
										}
										print '<tr class"oddeven"><td>';
										print "&nbsp; &nbsp; &nbsp; ->
										<a href=\"".DOL_URL_ROOT."/product/card.php?id=".$value['id']."\">".$value['fullpath']."
										</a> (".$value['nb'].")</td><td class=\"center\"> ".$value['nb_total']."</td><td>&nbsp;</td><td>&nbsp;</td>
										<td class=\"center\">".$value['stock']." ".$img."</td>";
										if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
											print '<td></td>';
										}//StockEntrydate
										print "</tr>";
									}
								}
							}
						} else {
							print '<!-- Case warehouse not already known and product need lot -->';
							print '<td></td><td></td>';
							if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
								print '<td></td>';
							}//StockEntrydate
							print '</tr>'; // end line and start a new one for lot/serial

							$subj = 0;
							print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';

							$tmpwarehouseObject = new Entrepot($db);
							$productlotObject = new Productlot($db);

							// Define nb of lines suggested for this order line
							$nbofsuggested = 0;
							foreach ($product->stock_warehouse as $warehouse_id => $stock_warehouse) {
								if (($stock_warehouse->real > 0 || !empty($conf->global->STOCK_ALLOW_NEGATIVE_TRANSFER)) && (count($stock_warehouse->detail_batch))) {
									$nbofsuggested += count($stock_warehouse->detail_batch);
								}
							}

							foreach ($product->stock_warehouse as $warehouse_id => $stock_warehouse) {
								$var = $subj % 2;
								if (!empty($warehousePicking) && !in_array($warehouse_id, $warehousePicking)) {
									// if a warehouse was selected by user, picking is limited to this warehouse and his children
									continue;
								}

								$tmpwarehouseObject->fetch($warehouse_id);
								if (($stock_warehouse->real > 0 || !empty($conf->global->STOCK_ALLOW_NEGATIVE_TRANSFER)) && (count($stock_warehouse->detail_batch))) {
									foreach ($stock_warehouse->detail_batch as $dbatch) {
										$batchStock = + $dbatch->qty; // To get a numeric
										if (isset($alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)])) {
											$deliverableQty = min($quantityToBeDelivered, $batchStock - $alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)]);
										} else {
											if (!isset($alreadyQtyBatchSetted[$line->fk_product])) {
												$alreadyQtyBatchSetted[$line->fk_product] = array();
											}

											if (!isset($alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch])) {
												$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch] = array();
											}

											$deliverableQty = min($quantityToBeDelivered, $batchStock);
										}

										if ($deliverableQty < 0) {
											$deliverableQty = 0;
										}

										$inputName = 'qtyl'.$indiceAsked.'_'.$subj;
										if (GETPOSTISSET($inputName)) {
											$deliverableQty = GETPOSTINT($inputName);
										}

										$tooltipClass = $tooltipTitle = '';
										if (!empty($alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)])) {
											$tooltipClass = ' classfortooltip';
											$tooltipTitle = $langs->trans('StockQuantitiesAlreadyAllocatedOnPreviousLines').' : '.$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)];
										} else {
											$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)] = 0 ;
										}
										$alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)] = $deliverableQty + $alreadyQtyBatchSetted[$line->fk_product][$dbatch->batch][intval($warehouse_id)];

										print '<!-- subj='.$subj.'/'.$nbofsuggested.' --><tr '.((($subj + 1) == $nbofsuggested) ? 'oddeven' : '').'><td colspan="3"></td><td class="center">';
										print '<input class="qtyl right '.$tooltipClass.'" title="'.$tooltipTitle.'" name="'.$inputName.'" id="'.$inputName.'" type="text" size="4" value="'.$deliverableQty.'">';
										print '</td>';

										print '<td class="left">';

										print $tmpwarehouseObject->getNomUrl(0).' / ';

										print '<!-- Show details of lot -->';
										print '<input name="batchl'.$indiceAsked.'_'.$subj.'" type="hidden" value="'.$dbatch->id.'">';

										//print '|'.$line->fk_product.'|'.$dbatch->batch.'|<br>';
										print $langs->trans("Batch").': ';
										$result = $productlotObject->fetch(0, $line->fk_product, $dbatch->batch);
										if ($result > 0) {
											print $productlotObject->getNomUrl(1);
										} else {
											print $langs->trans("TableLotIncompleteRunRepairWithParamStandardEqualConfirmed");
										}
										if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY') && !empty($dbatch->sellby)) {
											print ' - '.$langs->trans("SellByDate").': '.dol_print_date($dbatch->sellby, "day");
										}
										if (!getDolGlobalString('PRODUCT_DISABLE_EATBY') && !empty($dbatch->eatby)) {
											print ' - '.$langs->trans("EatByDate").': '.dol_print_date($dbatch->eatby, "day");
										}
										print ' ('.$dbatch->qty.')';
										$quantityToBeDelivered -= $deliverableQty;
										if ($quantityToBeDelivered < 0) {
											$quantityToBeDelivered = 0;
										}
										//dol_syslog('deliverableQty = '.$deliverableQty.' batchStock = '.$batchStock);
										$subj++;
										print '</td>';
										if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
											print '<td class="left">'.dol_print_date($dbatch->context['stock_entry_date'], 'day').'</td>';
										}
										print '</tr>';
									}
								}
							}
						}
						if ($subj == 0) { // Line not shown yet, we show it
							$warehouse_selected_id = GETPOSTINT('entrepot_id');

							print '<!-- line not shown yet, we show it -->';
							print '<tr class="oddeven"><td colspan="3"></td><td class="center">';

							if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
								$disabled = '';
								if (isModEnabled('productbatch') && $product->hasbatch()) {
									$disabled = 'disabled="disabled"';
								}
								if ($warehouse_selected_id <= 0) {		// We did not force a given warehouse, so we won't have no warehouse to change qty.
									$disabled = 'disabled="disabled"';
								}
								// finally we overwrite the input with the product status stockable_product if it's disabled
								if ($product->stockable_product == Product::DISABLED_STOCK){
									$disabled = '';
								}
								print '<input class="qtyl right" name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="0"'.($disabled ? ' '.$disabled : '').'> ';
								if (empty($disabled) && getDolGlobalString('STOCK_ALLOW_NEGATIVE_TRANSFER')) {
									print '<input name="ent1' . $indiceAsked . '_' . $subj . '" type="hidden" value="' . $warehouse_selected_id . '">';
								}
							} elseif ($line->product_type == Product::TYPE_SERVICE && getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
								$disabled = '';
								if (isModEnabled('productbatch') && $product->hasbatch()) {
									$disabled = 'disabled="disabled"';
								}
								if ($warehouse_selected_id <= 0) {		// We did not force a given warehouse, so we won't have no warehouse to change qty.
									$disabled = 'disabled="disabled"';
								}
								print '<input class="qtyl right" name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="'.$quantityToBeDelivered.'"'.($disabled ? ' '.$disabled : '').'> ';
								if (empty($disabled) && getDolGlobalString('STOCK_ALLOW_NEGATIVE_TRANSFER')) {
									print '<input name="ent1' . $indiceAsked . '_' . $subj . '" type="hidden" value="' . $warehouse_selected_id . '">';
								}
							} else {
								print $langs->trans("NA");
							}
							print '</td>';

							print '<td class="left">';
							if ($line->product_type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
								if ($warehouse_selected_id > 0) {
									$warehouseObject = new Entrepot($db);
									$warehouseObject->fetch($warehouse_selected_id);
									print img_warning().' '.$langs->trans("NoProductToShipFoundIntoStock", $warehouseObject->label);
								} else {
									if ($line->fk_product) {
										if($product->stockable_product == Product::ENABLED_STOCK) {
											print img_warning().' '.$langs->trans('StockTooLow');
										} else {
											print img_warning().' '.$langs->trans('StockDisabled');
										}									} else {
										print '';
									}
								}
							} else {
								print '<span class="opacitymedium">('.$langs->trans("Service").')</span>';
							}
							print '</td>';
							if (getDolGlobalString('SHIPPING_DISPLAY_STOCK_ENTRY_DATE')) {
								print '<td></td>';
							}//StockEntrydate
							print '</tr>';
						}
					}

					// Display lines for extrafields of the Shipment line
					// $line is a 'Order line'
					if (!empty($extrafields)) {
						//var_dump($line);
						$colspan = 5;
						$expLine = new ExpeditionLigne($db);

						$srcLine = new OrderLine($db);
						$srcLine->id = $line->id;
						$srcLine->fetch_optionals(); // fetch extrafields also available in orderline

						$expLine->array_options = array_merge($expLine->array_options, $srcLine->array_options);

						print $expLine->showOptionals($extrafields, 'edit', array('style' => 'class="drag drop oddeven"', 'colspan' => $colspan), $indiceAsked, '', 1);
					}
				}

				$indiceAsked++;
			}

			print "</table>";

			print '<br>';

			print $form->buttonsSaveCancel("Create");

			print '</form>';

			print '<br>';
		} else {
			dol_print_error($db);
		}
	}
} elseif ($object->id > 0) {
	/* *************************************************************************** */
	/*                                                                             */
	/* Edit and view mode                                                          */
	/*                                                                             */
	/* *************************************************************************** */
	$lines = $object->lines;

	$num_prod = count($lines);

	if (!empty($object->origin) && $object->origin_id > 0) {
		$typeobject = $object->origin;
		$origin = $object->origin;
		$origin_id = $object->origin_id;

		$object->fetch_origin(); // Load property $object->origin_object (old $object->commande, $object->propal, ...)
	}

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$res = $object->fetch_optionals();

	$head = shipping_prepare_head($object);
	print dol_get_fiche_head($head, 'shipping', $langs->trans("Shipment"), -1, $object->picto);

	$formconfirm = '';

	// Confirm deletion
	if ($action == 'delete') {
		$formquestion = array();
		if ($object->status == Expedition::STATUS_CLOSED && getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE')) {
			$formquestion = array(
					array(
						'label' => $langs->trans('ShipmentIncrementStockOnDelete'),
						'name' => 'alsoUpdateStock',
						'type' => 'checkbox',
						'value' => 0
					),
				);
		}
		$formconfirm = $form->formconfirm(
			$_SERVER['PHP_SELF'].'?id='.$object->id,
			$langs->trans('DeleteSending'),
			$langs->trans("ConfirmDeleteSending", $object->ref),
			'confirm_delete',
			$formquestion,
			0,
			1
		);
	}

	// Confirmation validation
	if ($action == 'valid') {
		$objectref = substr($object->ref, 1, 4);
		if ($objectref == 'PROV') {
			$numref = $object->getNextNumRef($soc);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans("ConfirmValidateSending", $numref);
		if (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT')) {
			$text .= '<br>'.img_picto('', 'movement', 'class="pictofixedwidth"').$langs->trans("StockMovementWillBeRecorded").'.';
		} elseif (getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT_CLOSE')) {
			$text .= '<br>'.img_picto('', 'movement', 'class="pictofixedwidth"').$langs->trans("StockMovementNotYetRecorded").'.';
		}

		if (isModEnabled('notification')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/notify.class.php';
			$notify = new Notify($db);
			$text .= '<br>';
			$text .= $notify->confirmMessage('SHIPPING_VALIDATE', $object->socid, $object);
		}

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('ValidateSending'), $text, 'confirm_valid', '', 0, 1, 250);
	}
	// Confirm cancellation
	if ($action == 'cancel') {
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('CancelSending'), $langs->trans("ConfirmCancelSending", $object->ref), 'confirm_cancel', '', 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Calculate totalWeight and totalVolume for all products
	// by adding weight and volume of each product line.
	$tmparray = $object->getTotalWeightVolume();
	$totalWeight = $tmparray['weight'];
	$totalVolume = $tmparray['volume'];

	if (!empty($typeobject) && $typeobject === 'commande' && is_object($object->origin_object) && $object->origin_object->id && isModEnabled('order')) {
		$objectsrc = new Commande($db);
		$objectsrc->fetch($object->origin_object->id);
	}
	if (!empty($typeobject) && $typeobject === 'propal' && is_object($object->origin_object) && $object->origin_object->id && isModEnabled("propal")) {
		$objectsrc = new Propal($db);
		$objectsrc->fetch($object->origin_object->id);
	}

	// Shipment card
	$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';
	$morehtmlref = '<div class="refidno">';
	// Ref customer shipment
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->hasRight('expedition', 'creer'), 'string'.(isset($conf->global->THIRDPARTY_REF_INPUT_SIZE) ? ':' . getDolGlobalString('THIRDPARTY_REF_INPUT_SIZE') : ''), '', null, null, '', 1);
	// Thirdparty
	$morehtmlref .= '<br>'.$object->thirdparty->getNomUrl(1);
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		$morehtmlref .= '<br>';
		if (0) {	// Do not change on shipment
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $objectsrc->socid, $objectsrc->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($objectsrc) && !empty($objectsrc->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($objectsrc->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Linked documents
	if (!empty($typeobject) && $typeobject == 'commande' && $object->origin_object->id && isModEnabled('order')) {
		print '<tr><td>';
		print $langs->trans("RefOrder").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'commande');
		print "</td>\n";
		print '</tr>';
	}
	if (!empty($typeobject) && $typeobject == 'propal' && $object->origin_object->id && isModEnabled("propal")) {
		print '<tr><td>';
		print $langs->trans("RefProposal").'</td>';
		print '<td colspan="3">';
		print $objectsrc->getNomUrl(1, 'expedition');
		print "</td>\n";
		print '</tr>';
	}

	// Date creation
	print '<tr><td class="titlefield">'.$langs->trans("DateCreation").'</td>';
	print '<td colspan="3">'.dol_print_date($object->date_creation, "dayhour")."</td>\n";
	print '</tr>';

	// Delivery date planned
	print '<tr><td height="10">';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('DateDeliveryPlanned');
	print '</td>';

	if ($action != 'editdate_livraison') {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editdate_livraison') {
		print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		print $form->selectDate($object->date_delivery ? $object->date_delivery : -1, 'liv_', 1, 1, 0, "setdate_livraison", 1, 0);
		print '<input type="submit" class="button button-edit smallpaddingimp" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		print $object->date_delivery ? dol_print_date($object->date_delivery, 'dayhour') : '&nbsp;';
	}
	print '</td>';
	print '</tr>';

	// Weight
	print '<tr><td>';
	print $form->editfieldkey("Weight", 'trueWeight', $object->trueWeight, $object, $user->hasRight('expedition', 'creer'));
	print '</td><td colspan="3">';

	if ($action == 'edittrueWeight') {
		print '<form name="settrueweight" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		print '<input name="action" value="settrueWeight" type="hidden">';
		print '<input name="id" value="'.$object->id.'" type="hidden">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input id="trueWeight" name="trueWeight" value="'.$object->trueWeight.'" type="text" class="width50 valignmiddle">';
		print $formproduct->selectMeasuringUnits("weight_units", "weight", $object->weight_units, 0, 2, 'maxwidth125 valignmiddle');
		print ' <input class="button smallpaddingimp valignmiddle" name="modify" value="'.$langs->trans("Modify").'" type="submit">';
		print ' <input class="button button-cancel smallpaddingimp valignmiddle" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
		print '</form>';
	} else {
		print $object->trueWeight;
		print ($object->trueWeight && $object->weight_units != '') ? ' '.measuringUnitString(0, "weight", $object->weight_units) : '';
	}

	// Calculated
	if ($totalWeight > 0) {
		if (!empty($object->trueWeight)) {
			print ' ('.$langs->trans("SumOfProductWeights").': ';
		}
		print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND) ? $conf->global->MAIN_WEIGHT_DEFAULT_ROUND : -1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT) ? $conf->global->MAIN_WEIGHT_DEFAULT_UNIT : 'no');
		if (!empty($object->trueWeight)) {
			print ')';
		}
	}
	print '</td></tr>';

	// Width
	print '<tr><td>'.$form->editfieldkey("Width", 'trueWidth', $object->trueWidth, $object, $user->hasRight('expedition', 'creer')).'</td><td colspan="3">';
	print $form->editfieldval("Width", 'trueWidth', $object->trueWidth, $object, $user->hasRight('expedition', 'creer'));
	print ($object->trueWidth && $object->width_units != '') ? ' '.measuringUnitString(0, "size", $object->width_units) : '';
	print '</td></tr>';

	// Height
	print '<tr><td>'.$form->editfieldkey("Height", 'trueHeight', $object->trueHeight, $object, $user->hasRight('expedition', 'creer')).'</td><td colspan="3">';
	if ($action == 'edittrueHeight') {
		print '<form name="settrueHeight" action="'.$_SERVER["PHP_SELF"].'" method="post">';
		print '<input name="action" value="settrueHeight" type="hidden">';
		print '<input name="id" value="'.$object->id.'" type="hidden">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input id="trueHeight" name="trueHeight" value="'.$object->trueHeight.'" type="text" class="width50">';
		print $formproduct->selectMeasuringUnits("size_units", "size", $object->size_units, 0, 2);
		print ' <input class="button smallpaddingimp" name="modify" value="'.$langs->trans("Modify").'" type="submit">';
		print ' <input class="button button-cancel smallpaddingimp" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
		print '</form>';
	} else {
		print $object->trueHeight;
		print ($object->trueHeight && $object->height_units != '') ? ' '.measuringUnitString(0, "size", $object->height_units) : '';
	}

	print '</td></tr>';

	// Depth
	print '<tr><td>'.$form->editfieldkey("Depth", 'trueDepth', $object->trueDepth, $object, $user->hasRight('expedition', 'creer')).'</td><td colspan="3">';
	print $form->editfieldval("Depth", 'trueDepth', $object->trueDepth, $object, $user->hasRight('expedition', 'creer'));
	print ($object->trueDepth && $object->depth_units != '') ? ' '.measuringUnitString(0, "size", $object->depth_units) : '';
	print '</td></tr>';

	// Volume
	print '<tr><td>';
	print $langs->trans("Volume");
	print '</td>';
	print '<td colspan="3">';
	$calculatedVolume = 0;
	$volumeUnit = 0;
	if ($object->trueWidth && $object->trueHeight && $object->trueDepth) {
		$calculatedVolume = ($object->trueWidth * $object->trueHeight * $object->trueDepth);
		$volumeUnit = $object->size_units * 3;
	}
	// If sending volume not defined we use sum of products
	if ($calculatedVolume > 0) {
		if ($volumeUnit < 50) {
			print showDimensionInBestUnit($calculatedVolume, $volumeUnit, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
		} else {
			print $calculatedVolume.' '.measuringUnitString(0, "volume", (string) $volumeUnit);
		}
	}
	if ($totalVolume > 0) {
		if ($calculatedVolume) {
			print ' ('.$langs->trans("SumOfProductVolumes").': ';
		}
		print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND) ? $conf->global->MAIN_VOLUME_DEFAULT_ROUND : -1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT) ? $conf->global->MAIN_VOLUME_DEFAULT_UNIT : 'no');
		//if (empty($calculatedVolume)) print ' ('.$langs->trans("Calculated").')';
		if ($calculatedVolume) {
			print ')';
		}
	}
	print "</td>\n";
	print '</tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Sending method
	print '<tr><td height="10">';
	print '<table class="nobordernopadding centpercent"><tr><td>';
	print $langs->trans('SendingMethod');
	print '</td>';

	if ($action != 'editshipping_method_id') {
		print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshipping_method_id&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetSendingMethod'), 1).'</a></td>';
	}
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editshipping_method_id') {
		print '<form name="setshipping_method_id" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="setshipping_method_id">';
		$object->fetch_delivery_methods();
		print $form->selectarray("shipping_method_id", $object->meths, $object->shipping_method_id, 1, 0, 0, "", 1);
		if ($user->admin) {
			print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
		}
		print '<input type="submit" class="button button-edit smallpaddingimp" value="'.$langs->trans('Modify').'">';
		print '</form>';
	} else {
		if ($object->shipping_method_id > 0) {
			// Get code using getLabelFromKey
			$code = $langs->getLabelFromKey($db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
			print $langs->trans("SendingMethod".strtoupper($code));
		}
	}
	print '</td>';
	print '</tr>';

	// Tracking Number
	print '<tr><td class="titlefield">'.$form->editfieldkey("TrackingNumber", 'tracking_number', $object->tracking_number, $object, $user->hasRight('expedition', 'creer')).'</td><td colspan="3">';
	print $form->editfieldval("TrackingNumber", 'tracking_number', $object->tracking_url, $object, $user->hasRight('expedition', 'creer'), 'safehtmlstring', $object->tracking_number);
	print '</td></tr>';

	// Incoterms
	if (isModEnabled('incoterm')) {
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('IncotermLabel');
		print '<td><td class="right">';
		if ($user->hasRight('expedition', 'creer')) {
			print '<a class="editfielda" href="'.DOL_URL_ROOT.'/expedition/card.php?id='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
		} else {
			print '&nbsp;';
		}
		print '</td></tr></table>';
		print '</td>';
		print '<td colspan="3">';
		if ($action != 'editincoterm') {
			print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
		} else {
			print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		}
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('colspan' => ' colspan="3"', 'cols' => '3');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>";

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';


	// Lines of products

	if ($action == 'editline') {
		print '	<form name="updateline" id="updateline" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;lineid='.$line_id.'" method="POST">
		<input type="hidden" name="token" value="' . newToken().'">
		<input type="hidden" name="action" value="updateline">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="' . $object->id.'">
		';
	}
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%" id="tablelines" >';
	print '<thead>';
	print '<tr class="liste_titre">';
	// Adds a line numbering column
	if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
		print '<td width="5" class="center linecolnum">&nbsp;</td>';
	}
	// Product/Service
	print '<td  class="linecoldescription" >'.$langs->trans("Products").'</td>';
	// Qty
	print '<td class="center linecolqty">'.$langs->trans("QtyOrdered").'</td>';
	if ($origin && $origin_id > 0) {
		print '<td class="center linecolqtyinothershipments">'.$langs->trans("QtyInOtherShipments").'</td>';
	}
	if ($action == 'editline') {
		$editColspan = 3;
		if (!isModEnabled('stock')) {
			$editColspan--;
		}
		if (empty($conf->productbatch->enabled)) {
			$editColspan--;
		}
		print '<td class="center linecoleditlineotherinfo" colspan="'.$editColspan.'">';
		if ($object->status <= 1) {
			print $langs->trans("QtyToShip").' - ';
		} else {
			print $langs->trans("QtyShipped").' - ';
		}
		if (isModEnabled('stock')) {
			print $langs->trans("WarehouseSource").' - ';
		}
		if (isModEnabled('productbatch')) {
			print $langs->trans("Batch");
		}
		print '</td>';
	} else {
		if ($object->status <= 1) {
			print '<td class="center linecolqtytoship">'.$langs->trans("QtyToShip").'</td>';
		} else {
			print '<td class="center linecolqtyshipped">'.$langs->trans("QtyShipped").'</td>';
		}
		if (isModEnabled('stock')) {
			print '<td class="left linecolwarehousesource">'.$langs->trans("WarehouseSource").'</td>';
		}

		if (isModEnabled('productbatch')) {
			print '<td class="left linecolbatch">'.$langs->trans("Batch").'</td>';
		}
	}
	print '<td class="center linecolweight">'.$langs->trans("CalculatedWeight").'</td>';
	print '<td class="center linecolvolume">'.$langs->trans("CalculatedVolume").'</td>';
	//print '<td class="center">'.$langs->trans("Size").'</td>';
	if ($object->status == 0) {
		print '<td class="linecoledit"></td>';
		print '<td class="linecoldelete" width="10"></td>';
	}
	print "</tr>\n";
	print '</thead>';

	$outputlangs = $langs;

	if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
		$object->fetch_thirdparty();
		$newlang = '';
		if (empty($newlang) && GETPOST('lang_id', 'aZ09')) {
			$newlang = GETPOST('lang_id', 'aZ09');
		}
		if (empty($newlang)) {
			$newlang = $object->thirdparty->default_lang;
		}
		if (!empty($newlang)) {
			$outputlangs = new Translate("", $conf);
			$outputlangs->setDefaultLang($newlang);
		}
	}

	// Get list of products already sent for same source object into $alreadysent
	$alreadysent = array();
	if ($origin && $origin_id > 0) {
		$sql = "SELECT obj.rowid, obj.fk_product, obj.label, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked, obj.fk_unit, obj.date_start, obj.date_end";
		$sql .= ", ed.rowid as shipmentline_id, ed.qty as qty_shipped, ed.fk_expedition as expedition_id, ed.fk_elementdet, ed.fk_entrepot";
		$sql .= ", e.rowid as shipment_id, e.ref as shipment_ref, e.date_creation, e.date_valid, e.date_delivery, e.date_expedition";
		//if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received";
		$sql .= ', p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, p.tosell as product_tosell, p.tobuy as product_tobuy, p.tobatch as product_tobatch';
		$sql .= ', p.description as product_desc';
		$sql .= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
		$sql .= ", ".MAIN_DB_PREFIX."expedition as e";
		$sql .= ", ".MAIN_DB_PREFIX.$origin."det as obj";
		//if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY')) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."delivery as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."deliverydet as ld ON ld.fk_delivery = l.rowid  AND obj.rowid = ld.fk_origin_line";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
		$sql .= " WHERE e.entity IN (".getEntity('expedition').")";
		$sql .= " AND obj.fk_".$origin." = ".((int) $origin_id);
		$sql .= " AND obj.rowid = ed.fk_elementdet";
		$sql .= " AND ed.fk_expedition = e.rowid";
		//if ($filter) $sql.= $filter;
		$sql .= " ORDER BY obj.fk_product";

		dol_syslog("expedition/card.php get list of shipment lines", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			while ($i < $num) {
				$obj = $db->fetch_object($resql);
				if ($obj) {
					// $obj->rowid is rowid in $origin."det" table
					$alreadysent[$obj->rowid][$obj->shipmentline_id] = array(
						'shipment_ref' => $obj->shipment_ref, 'shipment_id' => $obj->shipment_id, 'warehouse' => $obj->fk_entrepot, 'qty_shipped' => $obj->qty_shipped,
						'product_tosell' => $obj->product_tosell, 'product_tobuy' => $obj->product_tobuy, 'product_tobatch' => $obj->product_tobatch,
						'date_valid' => $db->jdate($obj->date_valid), 'date_delivery' => $db->jdate($obj->date_delivery));
				}
				$i++;
			}
		}
		//var_dump($alreadysent);
	}

	print '<tbody>';

	// Loop on each product to send/sent
	for ($i = 0; $i < $num_prod; $i++) {
		$parameters = array('i' => $i, 'line' => $lines[$i], 'line_id' => $line_id, 'num' => $num_prod, 'alreadysent' => $alreadysent, 'editColspan' => !empty($editColspan) ? $editColspan : 0, 'outputlangs' => $outputlangs);
		$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $object, $action);
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		}

		if (empty($reshook)) {
			print '<!-- origin line id = '.$lines[$i]->origin_line_id.' -->'; // id of order line
			print '<tr class="oddeven" id="row-'.$lines[$i]->id.'" data-id="'.$lines[$i]->id.'" data-element="'.$lines[$i]->element.'" >';

			// #
			if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
				print '<td class="center linecolnum">'.($i + 1).'</td>';
			}

			// Predefined product or service
			if ($lines[$i]->fk_product > 0) {
				// Define output language
				if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
					$prod = new Product($db);
					$prod->fetch($lines[$i]->fk_product);
					$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $lines[$i]->product_label;
				} else {
					$label = (!empty($lines[$i]->label) ? $lines[$i]->label : $lines[$i]->product_label);
				}

				print '<td class="linecoldescription">';

				// Show product and description
				$product_static->type = $lines[$i]->fk_product_type;
				$product_static->id = $lines[$i]->fk_product;
				$product_static->ref = $lines[$i]->ref;
				$product_static->status = $lines[$i]->product_tosell;
				$product_static->status_buy = $lines[$i]->product_tobuy;
				$product_static->status_batch = $lines[$i]->product_tobatch;

				$product_static->weight = $lines[$i]->weight;
				$product_static->weight_units = $lines[$i]->weight_units;
				$product_static->length = $lines[$i]->length;
				$product_static->length_units = $lines[$i]->length_units;
				$product_static->width = !empty($lines[$i]->width) ? $lines[$i]->width : 0;
				$product_static->width_units = !empty($lines[$i]->width_units) ? $lines[$i]->width_units : 0;
				$product_static->height = !empty($lines[$i]->height) ? $lines[$i]->height : 0;
				$product_static->height_units = !empty($lines[$i]->height_units) ? $lines[$i]->height_units : 0;
				$product_static->surface = $lines[$i]->surface;
				$product_static->surface_units = $lines[$i]->surface_units;
				$product_static->volume = $lines[$i]->volume;
				$product_static->volume_units = $lines[$i]->volume_units;
				$product_static->stockable_product = $lines[$i]->stockable_product;

				$text = $product_static->getNomUrl(1);
				$text .= ' - '.$label;
				$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($lines[$i]->description));
				print $form->textwithtooltip($text, $description, 3, '', '', $i);
				print_date_range(!empty($lines[$i]->date_start) ? $lines[$i]->date_start : '', !empty($lines[$i]->date_end) ? $lines[$i]->date_end : '');
				if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
					print (!empty($lines[$i]->description) && $lines[$i]->description != $lines[$i]->product) ? '<br>'.dol_htmlentitiesbr($lines[$i]->description) : '';
				}
				print "</td>\n";
			} else {
				print '<td class="linecoldescription" >';
				if ($lines[$i]->product_type == Product::TYPE_SERVICE) {
					$text = img_object($langs->trans('Service'), 'service');
				} else {
					$text = img_object($langs->trans('Product'), 'product');
				}

				if (!empty($lines[$i]->label)) {
					$text .= ' <strong>'.$lines[$i]->label.'</strong>';
					print $form->textwithtooltip($text, $lines[$i]->description, 3, '', '', $i);
				} else {
					print $text.' '.nl2br($lines[$i]->description);
				}

				print_date_range($lines[$i]->date_start, $lines[$i]->date_end);
				print "</td>\n";
			}

			$unit_order = '';
			if (getDolGlobalString('PRODUCT_USE_UNITS')) {
				$unit_order = measuringUnitString($lines[$i]->fk_unit);
			}

			// Qty ordered
			print '<td class="center linecolqty">'.$lines[$i]->qty_asked.' '.$unit_order.'</td>';

			// Qty in other shipments (with shipment and warehouse used)
			if ($origin && $origin_id > 0) {
				print '<td class="linecolqtyinothershipments center nowrap">';
				$htmltooltip = '';
				$qtyalreadysent = 0;
				foreach ($alreadysent as $key => $val) {
					if ($lines[$i]->fk_elementdet == $key) {
						$j = 0;
						foreach ($val as $shipmentline_id => $shipmentline_var) {
							if ($shipmentline_var['shipment_id'] == $lines[$i]->fk_expedition) {
								continue; // We want to show only "other shipments"
							}

							$j++;
							if ($j > 1) {
								$htmltooltip .= '<br>';
							}
							$shipment_static->fetch($shipmentline_var['shipment_id']);
							$htmltooltip .= $shipment_static->getNomUrl(1, '', 0, 0, 1);
							$htmltooltip .= ' - '.$shipmentline_var['qty_shipped'];
							$htmltooltip .= ' - '.$langs->trans("DateValidation").' : '.(empty($shipmentline_var['date_valid']) ? $langs->trans("Draft") : dol_print_date($shipmentline_var['date_valid'], 'dayhour'));
							/*if (isModEnabled('stock') && $shipmentline_var['warehouse'] > 0) {
								$warehousestatic->fetch($shipmentline_var['warehouse']);
								$htmltext .= '<br>'.$langs->trans("FromLocation").' : '.$warehousestatic->getNomUrl(1, '', 0, 1);
							}*/
							//print ' '.$form->textwithpicto('', $htmltext, 1);

							$qtyalreadysent += $shipmentline_var['qty_shipped'];
						}
						if ($j) {
							$htmltooltip = $langs->trans("QtyInOtherShipments").'...<br><br>'.$htmltooltip.'<br><input type="submit" name="dummyhiddenbuttontogetfocus" style="display:none" autofocus>';
						}
					}
				}
				print $form->textwithpicto($qtyalreadysent, $htmltooltip, 1, 'info', '', 0, 3, 'tooltip'.$lines[$i]->id);
				print '</td>';
			}

			if ($action == 'editline' && $lines[$i]->id == $line_id) {
				// edit mode
				print '<td colspan="'.$editColspan.'" class="center"><table class="nobordernopadding centpercent">';
				if (is_array($lines[$i]->detail_batch) && count($lines[$i]->detail_batch) > 0) {
					print '<!-- case edit 1 -->';
					$line = new ExpeditionLigne($db);
					foreach ($lines[$i]->detail_batch as $detail_batch) {
						print '<tr>';
						// Qty to ship or shipped
						print '<td><input class="qtyl right" name="qtyl'.$detail_batch->fk_expeditiondet.'_'.$detail_batch->id.'" id="qtyl'.$line_id.'_'.$detail_batch->id.'" type="text" size="4" value="'.$detail_batch->qty.'"></td>';
						// Batch number management
						if ($lines[$i]->entrepot_id == 0) {
							// only show lot numbers from src warehouse when shipping from multiple warehouses
							$line->fetch($detail_batch->fk_expeditiondet);
						}
						$entrepot_id = !empty($detail_batch->entrepot_id) ? $detail_batch->entrepot_id : $lines[$i]->entrepot_id;
						print '<td>'.$formproduct->selectLotStock($detail_batch->fk_origin_stock, 'batchl'.$detail_batch->fk_expeditiondet.'_'.$detail_batch->fk_origin_stock, '', 1, 0, $lines[$i]->fk_product, $entrepot_id).'</td>';
						print '</tr>';
					}
					// add a 0 qty lot row to be able to add a lot
					print '<tr>';
					// Qty to ship or shipped
					print '<td><input class="qtyl" name="qtyl'.$line_id.'_0" id="qtyl'.$line_id.'_0" type="text" size="4" value="0"></td>';
					// Batch number management
					print '<td>'.$formproduct->selectLotStock('', 'batchl'.$line_id.'_0', '', 1, 0, $lines[$i]->fk_product).'</td>';
					print '</tr>';
				} elseif (isModEnabled('stock')) {
					if ($lines[$i]->fk_product > 0) {
						if ($lines[$i]->entrepot_id > 0) {
							print '<!-- case edit 2 -->';
							print '<tr>';
							// Qty to ship or shipped
							print '<td><input class="qtyl right" name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty_shipped.'">'.$unit_order.'</td>';
							// Warehouse source
							print '<td>'.$formproduct->selectWarehouses($lines[$i]->entrepot_id, 'entl'.$line_id, '', 1, 0, $lines[$i]->fk_product, '', 1).'</td>';
							// Batch number management
							print '<td> - '.$langs->trans("NA").'</td>';
							print '</tr>';
						} elseif (count($lines[$i]->details_entrepot) > 1) {
							print '<!-- case edit 3 -->';
							foreach ($lines[$i]->details_entrepot as $detail_entrepot) {
								print '<tr>';
								// Qty to ship or shipped
								print '<td><input class="qtyl right" name="qtyl'.$detail_entrepot->line_id.'" id="qtyl'.$detail_entrepot->line_id.'" type="text" size="4" value="'.$detail_entrepot->qty_shipped.'">'.$unit_order.'</td>';
								// Warehouse source
								print '<td>'.$formproduct->selectWarehouses($detail_entrepot->entrepot_id, 'entl'.$detail_entrepot->line_id, '', 1, 0, $lines[$i]->fk_product, '', 1).'</td>';
								// Batch number management
								print '<td> - '.$langs->trans("NA").'</td>';
								print '</tr>';
							}
						} elseif ($lines[$i]->product_type == Product::TYPE_SERVICE && getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
							print '<!-- case edit 4 -->';
							print '<tr>';
							// Qty to ship or shipped
							print '<td><input class="qtyl right" name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty_shipped.'"></td>';
							print '<td><span class="opacitymedium">('.$langs->trans("Service").')</span></td>';
							print '<td></td>';
							print '</tr>';
						} else {
							print '<!-- case edit 5 -->';
							print '<tr><td colspan="3">'.$langs->trans("ErrorStockIsNotEnough").'</td></tr>';
						}
					} else {
						print '<!-- case edit 6 -->';
						print '<tr>';
						// Qty to ship or shipped
						print '<td><input class="qtyl right" name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty_shipped.'">'.$unit_order.'</td>';
						// Warehouse source
						print '<td></td>';
						// Batch number management
						print '<td></td>';
						print '</tr>';
					}
				} elseif (!isModEnabled('stock') && empty($conf->productbatch->enabled)) { // both product batch and stock are not activated.
					print '<!-- case edit 7 -->';
					print '<tr>';
					// Qty to ship or shipped
					print '<td><input class="qtyl right" name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty_shipped.'"></td>';
					// Warehouse source
					print '<td></td>';
					// Batch number management
					print '<td></td>';
					print '</tr>';
				}

				print '</table></td>';
			} else {
				// Qty to ship or shipped
				print '<td class="linecolqtytoship center">'.$lines[$i]->qty_shipped.' '.$unit_order.'</td>';

				// Warehouse source
				if (isModEnabled('stock')) {
					print '<td class="linecolwarehousesource tdoverflowmax200">';
					if ($lines[$i]->product_type == Product::TYPE_SERVICE && getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
						print '<span class="opacitymedium">('.$langs->trans("Service").')</span>';
					} elseif ($lines[$i]->entrepot_id > 0 && $lines[$i]->stockable_product == Product::ENABLED_STOCK) {
						$entrepot = new Entrepot($db);
						$entrepot->fetch($lines[$i]->entrepot_id);
						print $entrepot->getNomUrl(1);
					} elseif (count($lines[$i]->details_entrepot) > 1) {
						$detail = '';
						foreach ($lines[$i]->details_entrepot as $detail_entrepot) {
							if ($detail_entrepot->entrepot_id > 0) {
								$entrepot = new Entrepot($db);
								$entrepot->fetch($detail_entrepot->entrepot_id);
								$detail .= $langs->trans("DetailWarehouseFormat", $entrepot->label, $detail_entrepot->qty_shipped).'<br>';
							}
						}
						print $form->textwithtooltip(img_picto('', 'object_stock').' '.$langs->trans("DetailWarehouseNumber"), $detail);
					}
					print '</td>';
				}

				// Batch number management
				if (isModEnabled('productbatch')) {
					if (isset($lines[$i]->detail_batch)) {
						print '<!-- Detail of lot -->';
						print '<td class="linecolbatch">';
						if ($lines[$i]->product_tobatch) {
							$detail = '';
							foreach ($lines[$i]->detail_batch as $dbatch) {	// $dbatch is instance of ExpeditionLineBatch
								$detail .= $langs->trans("Batch").': '.$dbatch->batch;
								if (!getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
									$detail .= ' - '.$langs->trans("SellByDate").': '.dol_print_date($dbatch->sellby, "day");
								}
								if (!getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
									$detail .= ' - '.$langs->trans("EatByDate").': '.dol_print_date($dbatch->eatby, "day");
								}
								$detail .= ' - '.$langs->trans("Qty").': '.$dbatch->qty;
								$detail .= '<br>';
							}
							print $form->textwithtooltip(img_picto('', 'object_barcode').' '.$langs->trans("DetailBatchNumber"), $detail);
						} else {
							print $langs->trans("NA");
						}
						print '</td>';
					} else {
						print '<td class="linecolbatch" ></td>';
					}
				}
			}

			// Weight
			print '<td class="center linecolweight">';
			if ($lines[$i]->fk_product_type == Product::TYPE_PRODUCT) {
				print $lines[$i]->weight * $lines[$i]->qty_shipped.' '.measuringUnitString(0, "weight", $lines[$i]->weight_units);
			} else {
				print '&nbsp;';
			}
			print '</td>';

			// Volume
			print '<td class="center linecolvolume">';
			if ($lines[$i]->fk_product_type == Product::TYPE_PRODUCT) {
				print $lines[$i]->volume * $lines[$i]->qty_shipped.' '.measuringUnitString(0, "volume", $lines[$i]->volume_units);
			} else {
				print '&nbsp;';
			}
			print '</td>';

			// Size
			//print '<td class="center">'.$lines[$i]->volume*$lines[$i]->qty_shipped.' '.measuringUnitString(0, "volume", $lines[$i]->volume_units).'</td>';

			if ($action == 'editline' && $lines[$i]->id == $line_id) {
				print '<td class="center" colspan="2" valign="middle">';
				print '<input type="submit" class="button button-save" id="savelinebutton marginbottomonly" name="save" value="'.$langs->trans("Save").'"><br>';
				print '<input type="submit" class="button button-cancel" id="cancellinebutton" name="cancel" value="'.$langs->trans("Cancel").'"><br>';
				print '</td>';
			} elseif ($object->status == Expedition::STATUS_DRAFT) {
				// edit-delete buttons
				print '<td class="linecoledit center">';
				print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&token='.newToken().'&lineid='.$lines[$i]->id.'">'.img_edit().'</a>';
				print '</td>';
				print '<td class="linecoldelete" width="10">';
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&token='.newToken().'&lineid='.$lines[$i]->id.'">'.img_delete().'</a>';
				print '</td>';

				// Display lines extrafields
				if (!empty($rowExtrafieldsStart)) {
					print $rowExtrafieldsStart;
					print $rowExtrafieldsView;
					print $rowEnd;
				}
			}
			print "</tr>";

			// Display lines extrafields.
			// $line is a line of shipment
			if (!empty($extrafields)) {
				$colspan = 6;
				if ($origin && $origin_id > 0) {
					$colspan++;
				}
				if (isModEnabled('productbatch')) {
					$colspan++;
				}
				if (isModEnabled('stock')) {
					$colspan++;
				}

				$line = $lines[$i];
				$line->fetch_optionals();

				// TODO Show all in same line by setting $display_type = 'line'
				if ($action == 'editline' && $line->id == $line_id) {
					print $lines[$i]->showOptionals($extrafields, 'edit', array('colspan' => $colspan), !empty($indiceAsked) ? $indiceAsked : '', '', 0, 'card');
				} else {
					print $lines[$i]->showOptionals($extrafields, 'view', array('colspan' => $colspan), !empty($indiceAsked) ? $indiceAsked : '', '', 0, 'card');
				}
			}
		}
	}

	// TODO Show also lines ordered but not delivered

	if (empty($num_prod)) {
		print '<tr><td colspan="8"><span class="opacitymedium">'.$langs->trans("NoLineGoOnTabToAddSome", $langs->transnoentitiesnoconv("ShipmentDistribution")).'</span></td></tr>';
	}

	print "</table>\n";
	print '</tbody>';
	print '</div>';


	print dol_get_fiche_end();


	$object->fetchObjectLinked($object->id, $object->element);


	/*
	 *    Boutons actions
	 */

	if (($user->socid == 0) && ($action != 'presend')) {
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		// modified by hook
		if (empty($reshook)) {
			if ($object->status == Expedition::STATUS_DRAFT && $num_prod > 0) {
				if ((!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'creer'))
				 || (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && $user->hasRight('expedition', 'shipping_advance', 'validate'))) {
					print dolGetButtonAction('', $langs->trans('Validate'), 'default', $_SERVER["PHP_SELF"].'?action=valid&token='.newToken().'&id='.$object->id, '');
				} else {
					print dolGetButtonAction($langs->trans('NotAllowed'), $langs->trans('Validate'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
				}
			}

			// 0=draft, 1=validated/delivered, 2=closed/delivered
			if ($object->status == Expedition::STATUS_VALIDATED && !getDolGlobalString('STOCK_CALCULATE_ON_SHIPMENT')) {
				if ($user->hasRight('expedition', 'creer')) {
					print dolGetButtonAction('', $langs->trans('SetToDraft'), 'default', $_SERVER["PHP_SELF"].'?action=setdraft&token='.newToken().'&id='.$object->id, '');
				}
			}
			if ($object->status == Expedition::STATUS_CLOSED) {
				if ($user->hasRight('expedition', 'creer')) {
					print dolGetButtonAction('', $langs->trans('ReOpen'), 'default', $_SERVER["PHP_SELF"].'?action=reopen&token='.newToken().'&id='.$object->id, '');
				}
			}

			// Send
			if (empty($user->socid)) {
				if ($object->status > 0) {
					if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS') || $user->hasRight('expedition', 'shipping_advance', 'send')) {
						print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER["PHP_SELF"].'?action=presend&token='.newToken().'&id='.$object->id.'&mode=init#formmailbeforetitle', '');
					} else {
						print dolGetButtonAction('', $langs->trans('SendMail'), 'default', $_SERVER['PHP_SELF']. '#', '', false);
					}
				}
			}

			// Create bill
			if (isModEnabled('invoice') && ($object->status == Expedition::STATUS_VALIDATED || $object->status == Expedition::STATUS_CLOSED)) {
				if ($user->hasRight('facture', 'creer')) {
					if (getDolGlobalString('WORKFLOW_BILL_ON_SHIPMENT') !== '0') {
						print dolGetButtonAction('', $langs->trans('CreateBill'), 'default', DOL_URL_ROOT.'/compta/facture/card.php?action=create&origin='.$object->element.'&originid='.$object->id.'&socid='.$object->socid, '');
					}
				}
			}

			// This is just to generate a delivery receipt
			//var_dump($object->linkedObjectsIds['delivery']);
			if (getDolGlobalInt('MAIN_SUBMODULE_DELIVERY') && ($object->status == Expedition::STATUS_VALIDATED || $object->status == Expedition::STATUS_CLOSED) && $user->hasRight('expedition', 'delivery', 'creer') && empty($object->linkedObjectsIds['delivery'])) {
				print dolGetButtonAction('', $langs->trans('CreateDeliveryOrder'), 'default', $_SERVER["PHP_SELF"].'?action=create_delivery&token='.newToken().'&id='.$object->id, '');
			}

			// Set Billed and Closed
			if ($object->status == Expedition::STATUS_VALIDATED) {
				if ($user->hasRight('expedition', 'creer') && $object->status > 0) {
					if (!$object->billed && getDolGlobalString('WORKFLOW_BILL_ON_SHIPMENT') !== '0') {
						print dolGetButtonAction('', $langs->trans('ClassifyBilled'), 'default', $_SERVER["PHP_SELF"].'?action=classifybilled&token='.newToken().'&id='.$object->id, '');
					}
					print dolGetButtonAction('', $langs->trans("Close"), 'default', $_SERVER["PHP_SELF"].'?action=classifyclosed&token='.newToken().'&id='.$object->id, '');
				}
			}

			// Cancel
			if ($object->status == Expedition::STATUS_VALIDATED) {
				if ($user->hasRight('expedition', 'creer')) {
					print dolGetButtonAction('', $langs->trans('Cancel'), 'danger', $_SERVER["PHP_SELF"].'?action=cancel&token='.newToken().'&id='.$object->id.'&mode=init#formmailbeforetitle', '');
				}
			}

			// Delete
			if ($user->hasRight('expedition', 'supprimer')) {
				print dolGetButtonAction('', $langs->trans('Delete'), 'delete', $_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id, '');
			}
		}

		print '</div>';
	}


	/*
	 * Documents generated
	 */

	if ($action != 'presend' && $action != 'editline') {
		print '<div class="fichecenter"><div class="fichehalfleft">';

		$objectref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->expedition->dir_output."/sending/".$objectref;

		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

		$genallowed = $user->hasRight('expedition', 'lire');
		$delallowed = $user->hasRight('expedition', 'creer');

		print $formfile->showdocuments('expedition', $objectref, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', '', '', $soc->default_lang);


		// Show links to link elements
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('shipping'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

		// Show online signature link
		$useonlinesignature = getDolGlobalInt('EXPEDITION_ALLOW_ONLINESIGN');

		if ($object->statut != Expedition::STATUS_DRAFT && $useonlinesignature) {
			print '<br><!-- Link to sign -->';
			require_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';
			print showOnlineSignatureUrl('expedition', $object->ref, $object).'<br>';
		}

		print '</div><div class="fichehalfright">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions = new FormActions($db);
		$somethingshown = $formactions->showactions($object, 'shipping', $socid, 1);

		print '</div></div>';
	}


	/*
	 * Action presend
	 */

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail = 'shipping_send';
	$defaulttopic = 'SendShippingRef';
	$diroutput = $conf->expedition->dir_output.'/sending';
	$trackid = 'shi'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}

// End of page
llxFooter();
$db->close();
