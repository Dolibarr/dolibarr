<?php
/* Copyright (C) 2019-2020 	Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 		Christian Humpel     <christian.humpel@gmail.com>
 * Copyright (C) 2023 		Vincent de Grandpré  <vincent@de-grandpre.quebec>
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
 *    \file       mo_production.php
 *    \ingroup    mrp
 *    \brief      Page to make production on a MO
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/lib/mrp_mo.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';


// Load translation files required by the page
$langs->loadLangs(array("mrp", "stocks", "other", "product", "productbatch"));

// Get parameters
$id          = GETPOST('id', 'int');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'mocard'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$lineid      = GETPOST('lineid', 'int');
$fk_movement = GETPOST('fk_movement', 'int');
$fk_default_warehouse = GETPOST('fk_default_warehouse', 'int');

$collapse = GETPOST('collapse', 'aZ09comma');

// Initialize technical objects
$object = new Mo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$objectline = new MoLine($db);

$hookmanager->initHooks(array('moproduction', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criterias
$search_all = GETPOST("search_all", 'alpha');
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

// Permissions
$permissionnote = $user->rights->mrp->write; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->mrp->write; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->mrp->write; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->rights->mrp->delete || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);

$permissiontoproduce = $permissiontoadd;
$permissiontoupdatecost = $user->hasRight('bom', 'read'); // User who can define cost must have knowledge of pricing

$upload_dir = $conf->mrp->multidir_output[isset($object->entity) ? $object->entity : 1];


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/mrp/mo_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		//var_dump($backurlforlist);exit;
		if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
			$backtopage = $backurlforlist;
		} else {
			$backtopage = DOL_URL_ROOT.'/mrp/mo_production.php?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'MO_MODIFY'; // Name of trigger action code to execute when we modify record

	if ($action == 'confirm_cancel' && $confirm == 'yes' && !empty($permissiontoadd)) {
		$also_cancel_consumed_and_produced_lines = (GETPOST('alsoCancelConsumedAndProducedLines', 'alpha') ? 1 : 0);
		$result = $object->cancel($user, 0, $also_cancel_consumed_and_produced_lines);
		if ($result > 0) {
			header("Location: " . dol_buildpath('/mrp/mo_card.php?id=' . $object->id, 1));
			exit;
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'confirm_delete' && $confirm == 'yes' && !empty($permissiontodelete)) {
		$also_cancel_consumed_and_produced_lines = (GETPOST('alsoCancelConsumedAndProducedLines', 'alpha') ? 1 : 0);
		$result = $object->delete($user, 0, $also_cancel_consumed_and_produced_lines);
		if ($result > 0) {
			header("Location: " . $backurlforlist);
			exit;
		} else {
			$action = '';
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'MO_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MO_TO';
	$trackid = 'mo'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOST('fk_soc', 'int'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOST('projectid', 'int'));
	}

	if ($action == 'confirm_reopen' && $permissiontoadd) {
		$result = $object->setStatut($object::STATUS_INPROGRESS, 0, '', 'MRP_REOPEN');
	}

	if (($action == 'confirm_addconsumeline' && GETPOST('addconsumelinebutton') && $permissiontoadd)
	|| ($action == 'confirm_addproduceline' && GETPOST('addproducelinebutton') && $permissiontoadd)) {
		$moline = new MoLine($db);

		// Line to produce
		$moline->fk_mo = $object->id;
		$moline->qty = GETPOST('qtytoadd', 'int');
		$moline->fk_product = GETPOST('productidtoadd', 'int');
		if (GETPOST('addconsumelinebutton')) {
			$moline->role = 'toconsume';
		} else {
			$moline->role = 'toproduce';
		}
		$moline->origin_type = 'free'; // free consume line
		$moline->position = 0;

		// Is it a product or a service ?
		if (!empty($moline->fk_product)) {
			$tmpproduct = new Product($db);
			$tmpproduct->fetch($moline->fk_product);
			if ($tmpproduct->type == Product::TYPE_SERVICE) {
				$moline->fk_default_workstation = $tmpproduct->fk_default_workstation;
			}
			$moline->disable_stock_change = ($tmpproduct->type == Product::TYPE_SERVICE ? 1 : 0);
			if ($conf->global->PRODUCT_USE_UNITS) {
				$moline->fk_unit = $tmpproduct->fk_unit;
			}
		}
		// Extrafields
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafields->getOptionalsFromPost($object->table_element_line);
		// Unset extrafield
		if (is_array($extralabelsline)) {
			// Get extra fields
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_".$key]);
			}
		}
		if (is_array($array_options) && count($array_options) > 0) {
			$moline->array_options = $array_options;
		}

		$resultline = $moline->create($user, false); // Never use triggers here
		if ($resultline <= 0) {
			$error++;
			setEventMessages($moline->error, $moline->errors, 'errors');
		}

		$action = '';
		// Redirect to refresh the tab information
		header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}

	if (in_array($action, array('confirm_consumeorproduce', 'confirm_consumeandproduceall')) && $permissiontoproduce) {
		$stockmove = new MouvementStock($db);

		$labelmovement = GETPOST('inventorylabel', 'alphanohtml');
		$codemovement  = GETPOST('inventorycode', 'alphanohtml');

		$db->begin();
		$pos = 0;
		// Process line to consume
		foreach ($object->lines as $line) {
			if ($line->role == 'toconsume') {
				$tmpproduct = new Product($db);
				$tmpproduct->fetch($line->fk_product);

				$i = 1;
				while (GETPOSTISSET('qty-'.$line->id.'-'.$i)) {
					$qtytoprocess = price2num(GETPOST('qty-'.$line->id.'-'.$i));

					if ($qtytoprocess != 0) {
						// Check warehouse is set if we should have to
						if (GETPOSTISSET('idwarehouse-'.$line->id.'-'.$i)) {	// If there is a warehouse to set
							if (!(GETPOST('idwarehouse-'.$line->id.'-'.$i) > 0)) {	// If there is no warehouse set.
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Warehouse"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
							if ($tmpproduct->status_batch && (!GETPOST('batch-'.$line->id.'-'.$i))) {
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Batch"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
						}

						$idstockmove = 0;
						if (!$error && GETPOST('idwarehouse-'.$line->id.'-'.$i) > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->setOrigin($object->element, $object->id);
							$stockmove->context['mrp_role'] = 'toconsume';

							if ($qtytoprocess >= 0) {
								$idstockmove = $stockmove->livraison($user, $line->fk_product, GETPOST('idwarehouse-'.$line->id.'-'.$i), $qtytoprocess, 0, $labelmovement, dol_now(), '', '', GETPOST('batch-'.$line->id.'-'.$i), $id_product_batch, $codemovement);
							} else {
								$idstockmove = $stockmove->reception($user, $line->fk_product, GETPOST('idwarehouse-'.$line->id.'-'.$i), $qtytoprocess * -1, 0, $labelmovement, dol_now(), '', '', GETPOST('batch-'.$line->id.'-'.$i), $id_product_batch, $codemovement);
							}
							if ($idstockmove < 0) {
								$error++;
								setEventMessages($stockmove->error, $stockmove->errors, 'errors');
							}
						}

						if (!$error) {
							// Record consumption
							$moline = new MoLine($db);
							$moline->fk_mo = $object->id;
							$moline->position = $pos;
							$moline->fk_product = $line->fk_product;
							$moline->fk_warehouse = GETPOST('idwarehouse-'.$line->id.'-'.$i);
							$moline->qty = $qtytoprocess;
							$moline->batch = GETPOST('batch-'.$line->id.'-'.$i);
							$moline->role = 'consumed';
							$moline->fk_mrp_production = $line->id;
							$moline->fk_stock_movement = $idstockmove == 0 ? null : $idstockmove;
							$moline->fk_user_creat = $user->id;

							$resultmoline = $moline->create($user);
							if ($resultmoline <= 0) {
								$error++;
								setEventMessages($moline->error, $moline->errors, 'errors');
							}

							$pos++;
						}
					}

					$i++;
				}
			}
		}

		// Process line to produce
		$pos = 0;

		foreach ($object->lines as $line) {
			if ($line->role == 'toproduce') {
				$tmpproduct = new Product($db);
				$tmpproduct->fetch($line->fk_product);

				$i = 1;
				while (GETPOSTISSET('qtytoproduce-'.$line->id.'-'.$i)) {
					$qtytoprocess = price2num(GETPOST('qtytoproduce-'.$line->id.'-'.$i));
					$pricetoprocess = GETPOST('pricetoproduce-'.$line->id.'-'.$i) ? price2num(GETPOST('pricetoproduce-'.$line->id.'-'.$i)) : 0;

					if ($qtytoprocess != 0) {
						// Check warehouse is set if we should have to
						if (GETPOSTISSET('idwarehousetoproduce-'.$line->id.'-'.$i)) {	// If there is a warehouse to set
							if (!(GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i) > 0)) {	// If there is no warehouse set.
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Warehouse"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
							if (isModEnabled('productbatch') && $tmpproduct->status_batch && (!GETPOST('batchtoproduce-'.$line->id.'-'.$i))) {
								$langs->load("errors");
								setEventMessages($langs->trans("ErrorFieldRequiredForProduct", $langs->transnoentitiesnoconv("Batch"), $tmpproduct->ref), null, 'errors');
								$error++;
							}
						}

						$idstockmove = 0;
						if (!$error && GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i) > 0) {
							// Record stock movement
							$id_product_batch = 0;
							$stockmove->origin_type = $object->element;
							$stockmove->origin_id = $object->id;
							$stockmove->context['mrp_role'] = 'toproduce';

							$idstockmove = $stockmove->reception($user, $line->fk_product, GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i), $qtytoprocess, $pricetoprocess, $labelmovement, '', '', GETPOST('batchtoproduce-'.$line->id.'-'.$i), dol_now(), $id_product_batch, $codemovement);
							if ($idstockmove < 0) {
								$error++;
								setEventMessages($stockmove->error, $stockmove->errors, 'errors');
							}
						}

						if (!$error) {
							// Record production
							$moline = new MoLine($db);
							$moline->fk_mo = $object->id;
							$moline->position = $pos;
							$moline->fk_product = $line->fk_product;
							$moline->fk_warehouse = GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i);
							$moline->qty = $qtytoprocess;
							$moline->batch = GETPOST('batchtoproduce-'.$line->id.'-'.$i);
							$moline->role = 'produced';
							$moline->fk_mrp_production = $line->id;
							$moline->fk_stock_movement = $idstockmove;
							$moline->fk_user_creat = $user->id;

							$resultmoline = $moline->create($user);
							if ($resultmoline <= 0) {
								$error++;
								setEventMessages($moline->error, $moline->errors, 'errors');
							}

							$pos++;
						}
					}

					$i++;
				}
			}
		}

		if (!$error) {
			$consumptioncomplete = true;
			$productioncomplete = true;

			if (GETPOST('autoclose', 'int')) {
				foreach ($object->lines as $line) {
					if ($line->role == 'toconsume') {
						$arrayoflines = $object->fetchLinesLinked('consumed', $line->id);
						$alreadyconsumed = 0;
						foreach ($arrayoflines as $line2) {
							$alreadyconsumed += $line2['qty'];
						}

						if ($alreadyconsumed < $line->qty) {
							$consumptioncomplete = false;
						}
					}
					if ($line->role == 'toproduce') {
						$arrayoflines = $object->fetchLinesLinked('produced', $line->id);
						$alreadyproduced = 0;
						foreach ($arrayoflines as $line2) {
							$alreadyproduced += $line2['qty'];
						}

						if ($alreadyproduced < $line->qty) {
							$productioncomplete = false;
						}
					}
				}
			} else {
				$consumptioncomplete = false;
				$productioncomplete = false;
			}

			// Update status of MO
			dol_syslog("consumptioncomplete = ".$consumptioncomplete." productioncomplete = ".$productioncomplete);
			//var_dump("consumptioncomplete = ".$consumptioncomplete." productioncomplete = ".$productioncomplete);
			if ($consumptioncomplete && $productioncomplete) {
				$result = $object->setStatut($object::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
			} else {
				$result = $object->setStatut($object::STATUS_INPROGRESS, 0, '', 'MRP_MO_PRODUCED');
			}
			if ($result <= 0) {
				$error++;
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		if ($error) {
			$action = str_replace('confirm_', '', $action);
			$db->rollback();
		} else {
			$db->commit();

			// Redirect to avoid to action done a second time if we make a back from browser
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
	}

	// Action close produced
	if ($action == 'confirm_produced' && $confirm == 'yes' && $permissiontoadd) {
		$result = $object->setStatut($object::STATUS_PRODUCED, 0, '', 'MRP_MO_PRODUCED');
		if ($result >= 0) {
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

				$object->generateDocument($model, $outputlangs, 0, 0, 0);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'confirm_editline' && $permissiontoadd) {
		$moline = new MoLine($db);
		$res = $moline->fetch(GETPOST('lineid', 'int'));
		if ($result > 0) {
			$extrafields->fetch_name_optionals_label($moline->element);
			foreach ($extrafields->attributes[$moline->table_element]['label'] as $key => $label) {
				$value = GETPOST('options_'.$key, 'alphanohtml');
				$moline->array_options["options_".$key] = $value;
			}
			$moline->qty = GETPOST('qty_lineProduce', 'int');
			$res = $moline->update($user);
			if ($res < 0) {
				setEventMessages($moline->error, $moline->errors, 'errors');
				header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
				exit;
			}
			header("Location: ".$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
	}
}



/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);
$formproduct = new FormProduct($db);
$tmpwarehouse = new Entrepot($db);
$tmpbatch = new Productlot($db);
$tmpstockmovement = new MouvementStock($db);

$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag';
$morejs = array('/mrp/js/lib_dispatch.js.php');
llxHeader('', $langs->trans('Mo'), $help_url, '', 0, 0, $morejs);

$newToken = newToken();

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_thirdparty();
	$res = $object->fetch_optionals();

	if (getDolGlobalString('STOCK_CONSUMPTION_FROM_MANUFACTURING_WAREHOUSE') && $object->fk_warehouse > 0) {
		$tmpwarehouse->fetch($object->fk_warehouse);
		$fk_default_warehouse = $object->fk_warehouse;
	}

	$head = moPrepareHead($object);

	print dol_get_fiche_head($head, 'production', $langs->trans("ManufacturingOrder"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formquestion = array(
			array(
				'label' => $langs->trans('MoCancelConsumedAndProducedLines'),
				'name' => 'alsoCancelConsumedAndProducedLines',
				'type' => 'checkbox',
				'value' => 0
			),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMo'), $langs->trans('ConfirmDeleteMo'), 'confirm_delete', $formquestion, 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid.'&fk_movement='.$fk_movement, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMo', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of validation
	if ($action == 'validate') {
		// We check that object has a temporary ref
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV') {
			$object->fetch_product();
			$numref = $object->getNextNumRef($object->product);
		} else {
			$numref = $object->ref;
		}

		$text = $langs->trans('ConfirmValidateMo', $numref);
		/*if (isModEnabled('notification'))
		 {
		 require_once DOL_DOCUMENT_ROOT . '/core/class/notify.class.php';
		 $notify = new Notify($db);
		 $text .= '<br>';
		 $text .= $notify->confirmMessage('BOM_VALIDATE', $object->socid, $object);
		 }*/

		$formquestion = array();
		if (isModEnabled('mrp')) {
			$langs->load("mrp");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			$forcecombo = 0;
			if ($conf->browser->name == 'ie') {
				$forcecombo = 1; // There is a bug in IE10 that make combo inside popup crazy
			}
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
				// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			);
		}

		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Validate'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
	}

	// Confirmation to cancel
	if ($action == 'cancel') {
		$formquestion = array(
			array(
				'label' => $langs->trans('MoCancelConsumedAndProducedLines'),
				'name' => 'alsoCancelConsumedAndProducedLines',
				'type' => 'checkbox',
				'value' => !getDolGlobalString('MO_ALSO_CANCEL_CONSUMED_AND_PRODUCED_LINES_BY_DEFAULT') ? 0 : 1
			),
		);
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CancelMo'), $langs->trans('ConfirmCancelMo'), 'confirm_cancel', $formquestion, 0, 1);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// MO file
	// ------------------------------------------------------------
	$linkback = '<a href="'.DOL_URL_ROOT.'/mrp/mo_list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';

	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', null, null, '', 1);
	*/

	// Thirdparty
	if (is_object($object->thirdparty)) {
		$morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
	}

	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		if (is_object($object->thirdparty)) {
			$morehtmlref .= '<br>';
		}
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
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
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'fk_warehouse';
	unset($object->fields['fk_project']);
	unset($object->fields['fk_soc']);
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	if (!in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
		print '<div class="tabsAction">';

		$parameters = array();
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
		if (empty($reshook)) {
			// Validate
			if ($object->status == $object::STATUS_DRAFT) {
				if ($permissiontoadd) {
					if (empty($object->table_element_line) || (is_array($object->lines) && count($object->lines) > 0)) {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=validate&token='.$newToken.'">'.$langs->trans("Validate").'</a>';
					} else {
						$langs->load("errors");
						print '<a class="butActionRefused" href="" title="'.$langs->trans("ErrorAddAtLeastOneLineFirst").'">'.$langs->trans("Validate").'</a>';
					}
				}
			}

			// Consume or produce
			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=consumeorproduce&token='.$newToken.'">'.$langs->trans('ConsumeOrProduce').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ConsumeOrProduce').'</a>';
				}
			} elseif ($object->status == Mo::STATUS_DRAFT) {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('ConsumeOrProduce').'</a>';
			}

			// ConsumeAndProduceAll
			if ($object->status == Mo::STATUS_VALIDATED || $object->status == Mo::STATUS_INPROGRESS) {
				if ($permissiontoproduce) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=consumeandproduceall&token='.$newToken.'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
				} else {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
				}
			} elseif ($object->status == Mo::STATUS_DRAFT) {
				print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("ValidateBefore").'">'.$langs->trans('ConsumeAndProduceAll').'</a>';
			}

			// Cancel - Reopen
			if ($permissiontoadd) {
				if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_INPROGRESS) {
					$arrayproduced = $object->fetchLinesLinked('produced', 0);
					$nbProduced = 0;
					foreach ($arrayproduced as $lineproduced) {
						$nbProduced += $lineproduced['qty'];
					}
					if ($nbProduced > 0) {	// If production has started, we can close it
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_produced&confirm=yes&token='.$newToken.'">'.$langs->trans("Close").'</a>'."\n";
					} else {
						print '<a class="butActionRefused" href="#" title="'.$langs->trans("GoOnTabProductionToProduceFirst", $langs->transnoentitiesnoconv("Production")).'">'.$langs->trans("Close").'</a>'."\n";
					}

					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=cancel&token='.$newToken.'">'.$langs->trans("Cancel").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_CANCELED) {
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&confirm=yes&token='.$newToken.'">'.$langs->trans("ReOpen").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_PRODUCED) {
					if ($permissiontoproduce) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&token='.$newToken.'">'.$langs->trans('ReOpen').'</a>';
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ReOpen').'</a>';
					}
				}
			}
		}

		print '</div>';
	}

	if (in_array($action, array('consumeorproduce', 'consumeandproduceall', 'addconsumeline', 'addproduceline', 'editline'))) {
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="confirm_'.$action.'">';
		print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		// Note: closing form is add end of page

		if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
			$defaultstockmovementlabel = GETPOST('inventorylabel', 'alphanohtml') ? GETPOST('inventorylabel', 'alphanohtml') : $langs->trans("ProductionForRef", $object->ref);
			$defaultstockmovementcode = GETPOST('inventorycode', 'alphanohtml') ? GETPOST('inventorycode', 'alphanohtml') : dol_print_date(dol_now(), 'dayhourlog');

			print '<div class="center'.(in_array($action, array('consumeorproduce', 'consumeandproduceall')) ? ' formconsumeproduce' : '').'">';
			print '<div class="opacitymedium hideonsmartphone paddingbottom">'.$langs->trans("ConfirmProductionDesc", $langs->transnoentitiesnoconv("Confirm")).'<br></div>';
			print '<span class="fieldrequired">'.$langs->trans("InventoryCode").':</span> <input type="text" class="minwidth150 maxwidth200" name="inventorycode" value="'.$defaultstockmovementcode.'"> &nbsp; ';
			print '<span class="clearbothonsmartphone"></span>';
			print $langs->trans("MovementLabel").': <input type="text" class="minwidth300" name="inventorylabel" value="'.$defaultstockmovementlabel.'"><br><br>';
			print '<input type="checkbox" id="autoclose" name="autoclose" value="1"'.(GETPOSTISSET('inventorylabel') ? (GETPOST('autoclose') ? ' checked="checked"' : '') : ' checked="checked"').'> <label for="autoclose">'.$langs->trans("AutoCloseMO").'</label><br>';
			print '<input type="submit" class="button" value="'.$langs->trans("Confirm").'" name="confirm">';
			print ' &nbsp; ';
			print '<input class="button button-cancel" type="submit" value="'.$langs->trans("Cancel").'" name="cancel">';
			print '<br><br>';
			print '</div>';

			print '<br>';
		}
	}


	/*
	 * Lines
	 */
	$collapse = 1;

	if (!empty($object->table_element_line)) {
		// Show object lines
		$object->fetchLines();

		$bomcost = 0;
		if ($object->fk_bom > 0) {
			$bom = new BOM($db);
			$res = $bom->fetch($object->fk_bom);
			if ($res > 0) {
				$bom->calculateCosts();
				$bomcost = $bom->unit_cost;
			}
		}

		// Lines to consume

		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="clearboth"></div>';

		$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addconsumeline&token='.newToken();
		$permissiontoaddaconsumeline = $object->status != $object::STATUS_PRODUCED && $object->status != $object::STATUS_CANCELED;
		$parameters = array('morecss'=>'reposition');

		$newcardbutton = '';
		if ($action != 'consumeorproduce' && $action != 'consumeandproduceall') {
			$newcardbutton = dolGetButtonTitle($langs->trans('AddNewConsumeLines'), '', 'fa fa-plus-circle size15x', $url, '', $permissiontoaddaconsumeline, $parameters);
		}

		print load_fiche_titre($langs->trans('Consumption'), $newcardbutton, '', 0, '', '', '');

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder noshadow centpercent nobottom">';

		print '<tr class="liste_titre">';
		// Product
		print '<td>'.$langs->trans("Product").'</td>';
		// Qty
		print '<td class="right">'.$langs->trans("Qty").'</td>';
		// Unit
		if ($conf->global->PRODUCT_USE_UNITS) {
			print '<td class="right">' . $langs->trans("Unit") . '</td>';
		}
		// Cost price
		if ($permissiontoupdatecost && getDolGlobalString('MRP_SHOW_COST_FOR_CONSUMPTION')) {
			print '<td class="right">'.$langs->trans("UnitCost").'</td>';
		}
		// Qty already consumed
		print '<td class="right">'.$form->textwithpicto($langs->trans("QtyAlreadyConsumedShort"), $langs->trans("QtyAlreadyConsumed")).'</td>';
		// Warehouse
		print '<td>';
		if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
			print $langs->trans("Warehouse");
			if (isModEnabled('workstation')) {
				print ' '.$langs->trans("or").' '.$langs->trans("Workstation");
			}
			// Select warehouse to force it everywhere
			if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
				$listwarehouses = $tmpwarehouse->list_array(1);
				if (count($listwarehouses) > 1) {
					print '<br>'.$form->selectarray('fk_default_warehouse', $listwarehouses, $fk_default_warehouse, $langs->trans("ForceTo"), 0, 0, '', 0, 0, 0, '', 'minwidth100 maxwidth200', 1);
				} elseif (count($listwarehouses) == 1) {
					print '<br>'.$form->selectarray('fk_default_warehouse', $listwarehouses, $fk_default_warehouse, 0, 0, 0, '', 0, 0, 0, '', 'minwidth100 maxwidth200', 1);
				}
			}
		}
		print '</td>';

		if (isModEnabled('stock')) {
			// Available
			print '<td align="right">';
			if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
				print $langs->trans("Stock");
			}
			print '</td>';
		}
		// Lot - serial
		if (isModEnabled('productbatch')) {
			print '<td>';
			if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
				print $langs->trans("Batch");
			}
			print '</td>';
		}
		// Action
		if ($permissiontodelete) {
			print '<td></td>';
		}

		// Split
		print '<td></td>';

		// SplitAll
		print '<td></td>';

		// Edit Line
		if ($object->status == Mo::STATUS_DRAFT) {
			print '<td></td>';
		}

		print '</tr>';

		if ($action == 'addconsumeline') {
			print '<!-- Add line to consume -->'."\n";
			print '<tr class="liste_titre">';
			// Product
			print '<td>';
			print $form->select_produits('', 'productidtoadd', '', 0, 0, -1, 2, '', 1, array(), 0, '1', 0, 'maxwidth300');
			print '</td>';
			// Qty
			print '<td class="right"><input type="text" name="qtytoadd" value="1" class="width50 right"></td>';
			if ($conf->global->PRODUCT_USE_UNITS) {
				print '<td></td>';
			}
			// Cost price
			if ($permissiontoupdatecost && getDolGlobalString('MRP_SHOW_COST_FOR_CONSUMPTION')) {
				print '<td></td>';
			}
			// Qty already consumed + Warehouse
			print '<td colspan="2">';
			print '<input type="submit" class="button buttongen button-add" name="addconsumelinebutton" value="'.$langs->trans("Add").'">';
			print '<input type="submit" class="button buttongen button-cancel" name="canceladdconsumelinebutton" value="'.$langs->trans("Cancel").'">';
			print '</td>';
			if (isModEnabled('stock')) {
				print '<td></td>';
			}
			// Lot - serial
			if (isModEnabled('productbatch')) {
				print '<td></td>';
			}
			// Action
			if ($permissiontodelete) {
				print '<td></td>';
			}
			// Split
			print '<td></td>';
			// SplitAll
			print '<td></td>';
			// Edit Line
			if ($object->status == Mo::STATUS_DRAFT) {
				print '<td></td>';
			}
			print '</tr>';

			// Extrafields Line
			if (is_object($objectline)) {
				$extrafields->fetch_name_optionals_label($object->table_element_line);
				$temps = $objectline->showOptionals($extrafields, 'edit', array(), '', '', 1, 'line');
				if (!empty($temps)) {
					print '<tr class="liste_titre"><td style="padding-top: 20px" colspan="9" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
					print $temps;
					print '</td></tr>';
				}
			}
		}

		// Lines to consume

		$bomcostupdated = 0;	// We will recalculate the unitary cost to produce a product using the real "products to consume into MO"

		if (!empty($object->lines)) {
			$nblinetoconsume = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toconsume') {
					$nblinetoconsume++;
				}
			}

			$nblinetoconsumecursor = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toconsume') {
					$nblinetoconsumecursor++;

					$tmpproduct = new Product($db);
					$tmpproduct->fetch($line->fk_product);
					$linecost = price2num($tmpproduct->pmp, 'MT');

					if ($object->qty > 0) {
						// add free consume line cost to $bomcostupdated
						$costprice = price2num((!empty($tmpproduct->cost_price)) ? $tmpproduct->cost_price : $tmpproduct->pmp);
						if (empty($costprice)) {
							require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
							$productFournisseur = new ProductFournisseur($db);
							if ($productFournisseur->find_min_price_product_fournisseur($line->fk_product) > 0) {
								$costprice = $productFournisseur->fourn_unitprice;
							} else {
								$costprice = 0;
							}
						}
						$linecost = price2num(($line->qty * $costprice) / $object->qty, 'MT');	// price for line for all quantities
						$bomcostupdated += price2num(($line->qty * $costprice) / $object->qty, 'MU');	// same but with full accuracy
					}

					$bomcostupdated = price2num($bomcostupdated, 'MU');
					$arrayoflines = $object->fetchLinesLinked('consumed', $line->id);
					$alreadyconsumed = 0;
					foreach ($arrayoflines as $line2) {
						$alreadyconsumed += $line2['qty'];
					}

					if ($action == 'editline' && $lineid == $line->id) {
						$linecost = price2num($tmpproduct->pmp, 'MT');

						$arrayoflines = $object->fetchLinesLinked('consumed', $line->id);
						$alreadyconsumed = 0;
						if (is_array($arrayoflines) && !empty($arrayoflines)) {
							foreach ($arrayoflines as $line2) {
								$alreadyconsumed += $line2['qty'];
							}
						}
						$suffix = '_' . $line->id;
						print '<!-- Line to dispatch ' . $suffix . ' -->' . "\n";
						// hidden fields for js function
						print '<input id="qty_ordered' . $suffix . '" type="hidden" value="' . $line->qty . '">';
						// Duration - Time spent
						print '<input id="qty_dispatched' . $suffix . '" type="hidden" value="' . $alreadyconsumed . '">';
						print '<tr>';
						print '<input name="lineid" type="hidden" value="' . $line->id . '">';

						// Product
						print '<td>' . $tmpproduct->getNomUrl(1);
						print '<br><div class="opacitymedium small tdoverflowmax150" title="' . dol_escape_htmltag($tmpproduct->label) . '">' . $tmpproduct->label . '</span>';
						print '</td>';

						// Qty
						print '<td class="right nowraponall">';
						print '<input class="width40" name="qty_lineProduce" value="'. $line->qty.'">';
						print '</td>';
						// Unit
						if ($conf->global->PRODUCT_USE_UNITS) {
							print '<td class="right nowraponall">';
							print measuringUnitString($line->fk_unit, '', '', 1);
							print '</td>';
						}
						// Qty consumed
						print '<td class="right">';
						print ' ' . price2num($alreadyconsumed, 'MS');
						print '</td>';
						// Entrepot
						print '<td class="right">';
						print '</td>';
						// Stock
						print '<td class="nowraponall right">';
						if ($tmpproduct->isStockManaged()) {
							if ($tmpproduct->stock_reel < ($line->qty - $alreadyconsumed)) {
								print img_warning($langs->trans('StockTooLow')).' ';
							}
							print '<span class="left">'. $tmpproduct->stock_reel  .' </span>';
						}
						print '</td>';

						// Batch
						/*
						print '<td class="right">';
						print '</td>';
						*/

						// Action delete line
						print '<td colspan="2">';
						print '<input type="submit" class="button buttongen button-add small nominwidth" name="save" value="' . $langs->trans("Save") . '">';
						print '<input type="submit" class="button buttongen button-cancel small nominwidth" name="cancel" value="' . $langs->trans("Cancel") . '">';
						print '</td>';

						// Action delete line
						if ($permissiontodelete) {
							print '<td></td>';
						}
						print '<td></td>';
						print '</tr>';

						// Extrafields Line
						if (!empty($extrafields)) {
							$line->fetch_optionals();
							$temps = $line->showOptionals($extrafields, 'edit', array(), '', '', 1, 'line');
							if (!empty($temps)) {
								print '<td colspan="10"><div style="padding-top: 20px" id="extrafield_lines_area_edit" name="extrafield_lines_area_edit">';
								print $temps;
								print '</div></td>';
							}
						}
					} else {
						$suffix = '_' . $line->id;
						print '<!-- Line to dispatch ' . $suffix . ' -->' . "\n";
						// hidden fields for js function
						print '<input id="qty_ordered' . $suffix . '" type="hidden" value="' . $line->qty . '">';
						print '<input id="qty_dispatched' . $suffix . '" type="hidden" value="' . $alreadyconsumed . '">';

						print '<tr data-line-id="' . $line->id . '">';
						// Product
						print '<td>' . $tmpproduct->getNomUrl(1);
						print '<br><div class="opacitymedium small tdoverflowmax150" title="' . dol_escape_htmltag($tmpproduct->label) . '">' . $tmpproduct->label . '</div>';
						print '</td>';
						// Qty
						print '<td class="right nowraponall">';
						$help = '';
						$picto = 'help';
						if ($line->qty_frozen) {
							$help = ($help ? '<br>' : '') . '<strong>' . $langs->trans("QuantityFrozen") . '</strong>: ' . yn(1) . ' (' . $langs->trans("QuantityConsumedInvariable") . ')';
							print $form->textwithpicto('', $help, -1, 'lock') . ' ';
						}
						if ($line->disable_stock_change) {
							$help = ($help ? '<br>' : '') . '<strong>' . $langs->trans("DisableStockChange") . '</strong>: ' . yn(1) . ' (' . (($tmpproduct->type == Product::TYPE_SERVICE && !getDolGlobalString('STOCK_SUPPORTS_SERVICES')) ? $langs->trans("NoStockChangeOnServices") : $langs->trans("DisableStockChangeHelp")) . ')';
							print $form->textwithpicto('', $help, -1, 'help') . ' ';
						}
						print price2num($line->qty, 'MS');
						print '</td>';
						// Unit
						if ($conf->global->PRODUCT_USE_UNITS) {
							print '<td class="right nowraponall">';
							print measuringUnitString($line->fk_unit, '', '', 1);
							print '</td>';
						}
						// Cost price
						if ($permissiontoupdatecost && getDolGlobalString('MRP_SHOW_COST_FOR_CONSUMPTION')) {
							print '<td class="right nowraponall">';
							print price($linecost);
							print '</td>';
						}
						// Already consumed
						print '<td class="right">';
						if ($alreadyconsumed) {
							print '<script>';
							print 'jQuery(document).ready(function() {
								jQuery("#expandtoproduce' . $line->id . '").click(function() {
									console.log("Expand mrp_production line ' . $line->id . '");
									jQuery(".expanddetail' . $line->id . '").toggle();';
							if ($nblinetoconsume == $nblinetoconsumecursor) {    // If it is the last line
								print 'if (jQuery("#tablelines").hasClass("nobottom")) { jQuery("#tablelines").removeClass("nobottom"); } else { jQuery("#tablelines").addClass("nobottom"); }';
							}
							print '
								});
							});';
							print '</script>';
							if (empty($conf->use_javascript_ajax)) {
								print '<a href="' . $_SERVER["PHP_SELF"] . '?collapse=' . $collapse . ',' . $line->id . '">';
							}
							print img_picto($langs->trans("ShowDetails"), "chevron-down", 'id="expandtoproduce' . $line->id . '"');
							if (empty($conf->use_javascript_ajax)) {
								print '</a>';
							}
						} else {
							if ($nblinetoconsume == $nblinetoconsumecursor) {    // If it is the last line
								print '<script>jQuery("#tablelines").removeClass("nobottom");</script>';
							}
						}
						print ' ' . price2num($alreadyconsumed, 'MS');
						print '</td>';
						// Warehouse and/or workstation
						print '<td>';
						if (getDolGlobalString('STOCK_CONSUMPTION_FROM_MANUFACTURING_WAREHOUSE') && $tmpwarehouse->id > 0) {
							print img_picto('', $tmpwarehouse->picto) . " " . $tmpwarehouse->label;
						}
						if (isModEnabled('workstation') && $line->fk_default_workstation > 0) {
							$tmpworkstation = new Workstation($db);
							$tmpworkstation->fetch($line->fk_default_workstation);
							print $tmpworkstation->getNomUrl(1);
						}
						print '</td>';
						// Stock
						if (isModEnabled('stock')) {
							print '<td class="nowraponall right">';
							if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES') && $tmpproduct->type != Product::TYPE_SERVICE) {
								if (!$line->disable_stock_change && $tmpproduct->stock_reel < ($line->qty - $alreadyconsumed)) {
									print img_warning($langs->trans('StockTooLow')) . ' ';
								}
								if (!getDolGlobalString('STOCK_CONSUMPTION_FROM_MANUFACTURING_WAREHOUSE') || empty($tmpwarehouse->id)) {
									print price2num($tmpproduct->stock_reel, 'MS'); // Available
								} else {
									// Print only the stock in the selected warehouse
									$tmpproduct->load_stock();
									$wh_stock = $tmpproduct->stock_warehouse[$tmpwarehouse->id];
									if (!empty($wh_stock)) {
										print price2num($wh_stock->real, 'MS');
									} else {
										print "0";
									}
								}
							}
							print '</td>';
						}
						// Lot
						if (isModEnabled('productbatch')) {
							print '<td></td>';
						}

						// Split
						print '<td></td>';

						// Split All
						print '<td></td>';

						// Action Edit line
						if ($object->status == Mo::STATUS_DRAFT) {
							$href = $_SERVER["PHP_SELF"] . '?id=' . ((int) $object->id) . '&action=editline&token=' . newToken() . '&lineid=' . ((int) $line->id);
							print '<td class="center">';
							print '<a class="reposition" href="' . $href . '">';
							print img_picto($langs->trans('TooltipEditAndRevertStockMovement'), 'edit');
							print '</a>';
							print '</td>';
						}

						// Action delete line
						if ($permissiontodelete) {
							$href = $_SERVER["PHP_SELF"] . '?id=' . ((int) $object->id) . '&action=deleteline&token=' . newToken() . '&lineid=' . ((int) $line->id);
							print '<td class="center">';
							print '<a class="reposition" href="' . $href . '">';
							print img_picto($langs->trans('TooltipDeleteAndRevertStockMovement'), 'delete');
							print '</a>';
							print '</td>';
						}

						print '</tr>';
						// Extrafields Line
						if (!empty($extrafields)) {
							$line->fetch_optionals();
							$temps = $line->showOptionals($extrafields, 'view', array(), '', '', 1, 'line');
							if (!empty($temps)) {
								print '<td colspan="10"><div id="extrafield_lines_area_'.$line->id.'" name="extrafield_lines_area_'.$line->id.'">';
								print $temps;
								print '</div></td>';
							}
						}
					}
					// Show detailed of already consumed with js code to collapse
					foreach ($arrayoflines as $line2) {
						print '<tr class="expanddetail'.$line->id.' hideobject opacitylow">';

						// Date
						print '<td>';
						$tmpstockmovement->id = $line2['fk_stock_movement'];
						print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?search_ref='.$tmpstockmovement->id.'">'.img_picto($langs->trans("StockMovement"), 'movement', 'class="paddingright"').'</a>';
						print dol_print_date($line2['date'], 'dayhour', 'tzuserrel');
						print '</td>';

						// Already consumed
						print '<td></td>';

						// Qty
						print '<td class="right">'.$line2['qty'].'</td>';

						// Cost price
						if ($permissiontoupdatecost && getDolGlobalString('MRP_SHOW_COST_FOR_CONSUMPTION')) {
							print '<td></td>';
						}

						// Warehouse
						print '<td class="tdoverflowmax150">';
						if ($line2['fk_warehouse'] > 0) {
							$result = $tmpwarehouse->fetch($line2['fk_warehouse']);
							if ($result > 0) {
								print $tmpwarehouse->getNomUrl(1);
							}
						}
						print '</td>';

						// Stock
						if (isModEnabled('stock')) {
							print '<td></td>';
						}

						// Lot Batch
						if (isModEnabled('productbatch')) {
							print '<td>';
							if ($line2['batch'] != '') {
								$tmpbatch->fetch(0, $line2['fk_product'], $line2['batch']);
								print $tmpbatch->getNomUrl(1);
							}
							print '</td>';
						}

						// Split
						print '<td></td>';

						// Split All
						print '<td></td>';

						// Action Edit line
						if ($object->status == Mo::STATUS_DRAFT) {
							$href = $_SERVER["PHP_SELF"] . '?id=' . ((int) $object->id) . '&action=editline&token=' . newToken() . '&lineid=' . ((int) $line2['rowid']);
							print '<td class="center">';
							print '<a class="reposition" href="' . $href . '">';
							print img_picto($langs->trans('TooltipEditAndRevertStockMovement'), 'edit');
							print '</a>';
							print '</td>';
						}

						// Action delete line
						if ($permissiontodelete) {
							$href = $_SERVER["PHP_SELF"].'?id='.((int) $object->id).'&action=deleteline&token='.newToken().'&lineid='.((int) $line2['rowid']).'&fk_movement='.((int) $line2['fk_stock_movement']);
							print '<td class="center">';
							print '<a class="reposition" href="'.$href.'">';
							print img_picto($langs->trans('TooltipDeleteAndRevertStockMovement'), 'delete');
							print '</a>';
							print '</td>';
						}

						print '</tr>';
					}

					if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
						$i = 1;
						print '<!-- Enter line to consume -->'."\n";
						$maxQty = 1;
						print '<tr data-max-qty="'.$maxQty.'" name="batch_'.$line->id.'_'.$i.'">';
						// Ref
						print '<td><span class="opacitymedium">'.$langs->trans("ToConsume").'</span></td>';
						$preselected = (GETPOSTISSET('qty-'.$line->id.'-'.$i) ? GETPOST('qty-'.$line->id.'-'.$i) : max(0, $line->qty - $alreadyconsumed));
						if ($action == 'consumeorproduce' && !GETPOSTISSET('qty-'.$line->id.'-'.$i)) {
							$preselected = 0;
						}

						$disable = '';
						if (getDolGlobalString('MRP_NEVER_CONSUME_MORE_THAN_EXPECTED') && ($line->qty - $alreadyconsumed) <= 0) {
							$disable = 'disabled';
						}

						// input hidden with fk_product of line
						print '<input type="hidden" name="product-'.$line->id.'-'.$i.'" value="'.$line->fk_product.'">';

						// Qty
						print '<td class="right"><input type="text" class="width50 right" id="qtytoconsume-' . $line->id . '-' . $i . '" name="qty-' . $line->id . '-' . $i . '" value="' . $preselected . '" ' . $disable . '></td>';

						// Unit
						if ($conf->global->PRODUCT_USE_UNITS) {
							print '<td></td>';
						}

						// Cost
						if ($permissiontoupdatecost && getDolGlobalString('MRP_SHOW_COST_FOR_CONSUMPTION')) {
							print '<td></td>';
						}

						// Already consumed
						print '<td></td>';

						// Warehouse
						print '<td>';
						if ($tmpproduct->type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
							if (empty($line->disable_stock_change)) {
								$preselected = (GETPOSTISSET('idwarehouse-'.$line->id.'-'.$i) ? GETPOST('idwarehouse-'.$line->id.'-'.$i) : ($tmpproduct->fk_default_warehouse > 0 ? $tmpproduct->fk_default_warehouse : 'ifone'));
								print $formproduct->selectWarehouses($preselected, 'idwarehouse-'.$line->id.'-'.$i, '', 1, 0, $line->fk_product, '', 1, 0, null, 'maxwidth200 csswarehouse_'.$line->id.'_'.$i);
							} else {
								print '<span class="opacitymedium">'.$langs->trans("DisableStockChange").'</span>';
							}
						} else {
							print '<span class="opacitymedium">'.$langs->trans("NoStockChangeOnServices").'</span>';
						}
						print '</td>';

						// Stock
						if (isModEnabled('stock')) {
							print '<td></td>';
						}

						// Lot / Batch
						if (isModEnabled('productbatch')) {
							print '<td class="nowraponall">';
							if ($tmpproduct->status_batch) {
								$preselected = (GETPOSTISSET('batch-'.$line->id.'-'.$i) ? GETPOST('batch-'.$line->id.'-'.$i) : '');
								print '<input type="text" class="width75" name="batch-'.$line->id.'-'.$i.'" value="'.$preselected.'" list="batch-'.$line->id.'-'.$i.'">';
								print $formproduct->selectLotDataList('batch-'.$line->id.'-'.$i, 0, $line->fk_product, '', '');
							}
							print '</td>';
						}

						// Split
						$type = 'batch';
						print '<td align="right" class="split">';
						print ' '.img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine('.((int) $line->id).', \''.dol_escape_js($type).'\', \'qtymissingconsume\')"');
						print '</td>';

						// Split All
						print '<td align="right" class="splitall">';
						if (($action == 'consumeorproduce' || $action == 'consumeandproduceall') && $tmpproduct->status_batch == 2) {
							print img_picto($langs->trans('SplitAllQuantity'), 'split.png', 'class="splitbutton splitallbutton field-error-icon" data-max-qty="1" onClick="addDispatchLine('.$line->id.', \'batch\', \'allmissingconsume\')"');
						}
						print '</td>';

						// Edit Line
						if ($object->status == Mo::STATUS_DRAFT) {
							print '<td></td>';
						}

						// Action delete line
						if ($permissiontodelete) {
							print '<td></td>';
						}

						print '</tr>';
					}
				}
			}
		}

		print '</table>';
		print '</div>';

		// default warehouse processing
		print '<script type="text/javascript">
			$(document).ready(function () {
				$("select[name=fk_default_warehouse]").change(function() {
                    var fk_default_warehouse = $("option:selected", this).val();
					$("select[name^=idwarehouse-]").val(fk_default_warehouse).change();
                });
			});
		</script>';

		if (in_array($action, array('consumeorproduce', 'consumeandproduceall')) &&
			getDolGlobalString('STOCK_CONSUMPTION_FROM_MANUFACTURING_WAREHOUSE')) {
			print '<script>$(document).ready(function () {
				$("#fk_default_warehouse").change();
			});</script>';
		}


		// Lines to produce

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="clearboth"></div>';

		$nblinetoproduce = 0;
		foreach ($object->lines as $line) {
			if ($line->role == 'toproduce') {
				$nblinetoproduce++;
			}
		}

		$newcardbutton = '';
		$url = $_SERVER["PHP_SELF"].'?id='.$object->id.'&action=addproduceline&token='.newToken();
		$permissiontoaddaproductline = $object->status != $object::STATUS_PRODUCED && $object->status != $object::STATUS_CANCELED;
		$parameters = array('morecss'=>'reposition');
		if ($action != 'consumeorproduce' && $action != 'consumeandproduceall') {
			if ($nblinetoproduce == 0 || $object->mrptype == 1) {
				$newcardbutton = dolGetButtonTitle($langs->trans('AddNewProduceLines'), '', 'fa fa-plus-circle size15x', $url, '', $permissiontoaddaproductline, $parameters);
			}
		}

		print load_fiche_titre($langs->trans('Production'), $newcardbutton, '', 0, '', '');

		print '<div class="div-table-responsive-no-min">';
		print '<table id="tablelinestoproduce" class="noborder noshadow nobottom centpercent">';

		print '<tr class="liste_titre">';
		// Product
		print '<td>'.$langs->trans("Product").'</td>';
		// Qty
		print '<td class="right">'.$langs->trans("Qty").'</td>';
		/// Unit
		if ($conf->global->PRODUCT_USE_UNITS) {
			print '<td class="right">'.$langs->trans("Unit").'</td>';
		}
		// Cost price
		if ($permissiontoupdatecost) {
			if (empty($bomcostupdated)) {
				print '<td class="right">'.$form->textwithpicto($langs->trans("UnitCost"), $langs->trans("AmountUsedToUpdateWAP")).'</td>';
			} else {
				print '<td class="right">'.$form->textwithpicto($langs->trans("ManufacturingPrice"), $langs->trans("AmountUsedToUpdateWAP")).'</td>';
			}
		}
		// Already produced
		print '<td class="right">'.$form->textwithpicto($langs->trans("QtyAlreadyProducedShort"), $langs->trans("QtyAlreadyProduced")).'</td>';
		// Warehouse
		print '<td>';
		if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
			print $langs->trans("Warehouse");
		}
		print '</td>';

		// Lot
		if (isModEnabled('productbatch')) {
			print '<td>';
			if ($collapse || in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
				print $langs->trans("Batch");
			}
			print '</td>';

			// Split
			print '<td></td>';

			// Split All
			print '<td></td>';
		}

		// Action delete
		if ($permissiontodelete) {
			print '<td></td>';
		}

		print '</tr>';

		if ($action == 'addproduceline') {
			print '<!-- Add line to produce -->'."\n";
			print '<tr class="liste_titre">';

			// Product
			print '<td>';
			print $form->select_produits('', 'productidtoadd', '', 0, 0, -1, 2, '', 1, array(), 0, '1', 0, 'maxwidth300');
			print '</td>';
			// Qty
			print '<td class="right"><input type="text" name="qtytoadd" value="1" class="width50 right"></td>';
			//Unit
			if ($conf->global->PRODUCT_USE_UNITS) {
				print '<td></td>';
			}
			// Cost price
			if ($permissiontoupdatecost) {
				print '<td></td>';
			}
			// Action (cost price + already produced)
			print '<td colspan="2">';
			print '<input type="submit" class="button buttongen button-add" name="addproducelinebutton" value="'.$langs->trans("Add").'">';
			print '<input type="submit" class="button buttongen button-cancel" name="canceladdproducelinebutton" value="'.$langs->trans("Cancel").'">';
			print '</td>';
			// Lot - serial
			if (isModEnabled('productbatch')) {
				print '<td></td>';

				// Split
				print '<td></td>';

				// Split All
				print '<td></td>';
			}
			// Action delete
			if ($permissiontodelete) {
				print '<td></td>';
			}
			print '</tr>';
		}

		if (!empty($object->lines)) {
			$nblinetoproduce = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toproduce') {
					$nblinetoproduce++;
				}
			}

			$nblinetoproducecursor = 0;
			foreach ($object->lines as $line) {
				if ($line->role == 'toproduce') {
					$i = 1;

					$nblinetoproducecursor++;

					$tmpproduct = new Product($db);
					$tmpproduct->fetch($line->fk_product);

					$arrayoflines = $object->fetchLinesLinked('produced', $line->id);
					$alreadyproduced = 0;
					foreach ($arrayoflines as $line2) {
						$alreadyproduced += $line2['qty'];
					}

					$suffix = '_'.$line->id;
					print '<!-- Line to dispatch '.$suffix.' -->'."\n";
					// hidden fields for js function
					print '<input id="qty_ordered'.$suffix.'" type="hidden" value="'.$line->qty.'">';
					print '<input id="qty_dispatched'.$suffix.'" type="hidden" value="'.$alreadyproduced.'">';

					print '<tr>';
					// Product
					print '<td>'.$tmpproduct->getNomUrl(1);
					print '<br><span class="opacitymedium small">'.$tmpproduct->label.'</span>';
					print '</td>';
					// Qty
					print '<td class="right">'.$line->qty.'</td>';
					// Unit
					if ($conf->global->PRODUCT_USE_UNITS) {
						print '<td class="right">'.measuringUnitString($line->fk_unit, '', '', 1).'</td>';
					}
					// Cost price
					if ($permissiontoupdatecost) {
						// Defined $manufacturingcost
						$manufacturingcost = 0;
						$manufacturingcostsrc = '';
						if ($object->mrptype == 0) {	// If MO is a "Manufacture" type (and not "Disassemble")
							$manufacturingcost = $bomcostupdated;
							$manufacturingcostsrc = $langs->trans("CalculatedFromProductsToConsume");
							if (empty($manufacturingcost)) {
								$manufacturingcost = $bomcost;
								$manufacturingcostsrc = $langs->trans("ValueFromBom");
							}
							if (empty($manufacturingcost)) {
								$manufacturingcost = price2num($tmpproduct->cost_price, 'MU');
								$manufacturingcostsrc = $langs->trans("CostPrice");
							}
							if (empty($manufacturingcost)) {
								$manufacturingcost = price2num($tmpproduct->pmp, 'MU');
								$manufacturingcostsrc = $langs->trans("PMPValue");
							}
						}

						print '<td class="right nowraponall" title="'.dol_escape_htmltag($manufacturingcostsrc).'">';
						if ($manufacturingcost) {
							print price($manufacturingcost);
						}
						print '</td>';
					}
					// Already produced
					print '<td class="right nowraponall">';
					if ($alreadyproduced) {
						print '<script>';
						print 'jQuery(document).ready(function() {
							jQuery("#expandtoproduce'.$line->id.'").click(function() {
								console.log("Expand mrp_production line '.$line->id.'");
								jQuery(".expanddetailtoproduce'.$line->id.'").toggle();';
						if ($nblinetoproduce == $nblinetoproducecursor) {
							print 'if (jQuery("#tablelinestoproduce").hasClass("nobottom")) { jQuery("#tablelinestoproduce").removeClass("nobottom"); } else { jQuery("#tablelinestoproduce").addClass("nobottom"); }';
						}
						print '
							});
						});';
						print '</script>';
						if (empty($conf->use_javascript_ajax)) {
							print '<a href="'.$_SERVER["PHP_SELF"].'?collapse='.$collapse.','.$line->id.'">';
						}
						print img_picto($langs->trans("ShowDetails"), "chevron-down", 'id="expandtoproduce'.$line->id.'"');
						if (empty($conf->use_javascript_ajax)) {
							print '</a>';
						}
					}
					print ' '.$alreadyproduced;
					print '</td>';
					// Warehouse
					print '<td>';
					print '</td>';
					// Lot
					if (isModEnabled('productbatch')) {
						print '<td></td>';

						// Split
						print '<td></td>';

						// Split All
						print '<td></td>';
					}
					// Delete
					if ($permissiontodelete) {
						if ($line->origin_type == 'free') {
							$href = $_SERVER["PHP_SELF"];
							$href .= '?id='.$object->id;
							$href .= '&action=deleteline';
							$href .= '&lineid='.$line->id;
							print '<td class="center">';
							print '<a class="reposition" href="'.$href.'">';
							print img_picto($langs->trans('TooltipDeleteAndRevertStockMovement'), "delete");
							print '</a>';
							print '</td>';
						} else {
							print '<td></td>';
						}
					}
					print '</tr>';

					// Show detailed of already consumed with js code to collapse
					foreach ($arrayoflines as $line2) {
						print '<tr class="expanddetailtoproduce'.$line->id.' hideobject opacitylow">';
						// Product
						print '<td>';
						$tmpstockmovement->id = $line2['fk_stock_movement'];
						print '<a href="'.DOL_URL_ROOT.'/product/stock/movement_list.php?search_ref='.$tmpstockmovement->id.'">'.img_picto($langs->trans("StockMovement"), 'movement', 'class="paddingright"').'</a>';
						print dol_print_date($line2['date'], 'dayhour', 'tzuserrel');
						print '</td>';
						// Qty
						print '<td></td>';
						// Unit
						if ($conf->global->PRODUCT_USE_UNITS) {
							print '<td></td>';
						}
						// Cost price
						if ($permissiontoupdatecost) {
							print '<td></td>';
						}
						// Already produced
						print '<td class="right">'.$line2['qty'].'</td>';
						// Warehouse
						print '<td class="tdoverflowmax150">';
						if ($line2['fk_warehouse'] > 0) {
							$result = $tmpwarehouse->fetch($line2['fk_warehouse']);
							if ($result > 0) {
								print $tmpwarehouse->getNomUrl(1);
							}
						}
						print '</td>';
						// Lot
						if (isModEnabled('productbatch')) {
							print '<td>';
							if ($line2['batch'] != '') {
								$tmpbatch->fetch(0, $line2['fk_product'], $line2['batch']);
								print $tmpbatch->getNomUrl(1);
							}
							print '</td>';

							// Split
							print '<td></td>';

							// Split All
							print '<td></td>';
						}
						// Action delete
						if ($permissiontodelete) {
							print '<td></td>';
						}
						print '</tr>';
					}

					if (in_array($action, array('consumeorproduce', 'consumeandproduceall'))) {
						print '<!-- Enter line to produce -->'."\n";
						$maxQty = 1;
						print '<tr data-max-qty="'.$maxQty.'" name="batch_'.$line->id.'_'.$i.'">';
						// Product
						print '<td><span class="opacitymedium">'.$langs->trans("ToProduce").'</span></td>';
						$preselected = (GETPOSTISSET('qtytoproduce-'.$line->id.'-'.$i) ? GETPOST('qtytoproduce-'.$line->id.'-'.$i) : max(0, $line->qty - $alreadyproduced));
						if ($action == 'consumeorproduce' && !GETPOSTISSET('qtytoproduce-'.$line->id.'-'.$i)) {
							$preselected = 0;
						}
						// Qty
						print '<td class="right"><input type="text" class="width50 right" id="qtytoproduce-'.$line->id.'-'.$i.'" name="qtytoproduce-'.$line->id.'-'.$i.'" value="'.$preselected.'"></td>';
						//Unit
						if ($conf->global->PRODUCT_USE_UNITS) {
							print '<td class="right"></td>';
						}
						// Cost
						if ($permissiontoupdatecost) {
							// Defined $manufacturingcost
							$manufacturingcost = 0;
							$manufacturingcostsrc = '';
							if ($object->mrptype == 0) {	// If MO is a "Manufacture" type (and not "Disassemble")
								$manufacturingcost = $bomcostupdated;
								$manufacturingcostsrc = $langs->trans("CalculatedFromProductsToConsume");
								if (empty($manufacturingcost)) {
									$manufacturingcost = $bomcost;
									$manufacturingcostsrc = $langs->trans("ValueFromBom");
								}
								if (empty($manufacturingcost)) {
									$manufacturingcost = price2num($tmpproduct->cost_price, 'MU');
									$manufacturingcostsrc = $langs->trans("CostPrice");
								}
								if (empty($manufacturingcost)) {
									$manufacturingcost = price2num($tmpproduct->pmp, 'MU');
									$manufacturingcostsrc = $langs->trans("PMPValue");
								}
							}

							if ($tmpproduct->type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
								$preselected = (GETPOSTISSET('pricetoproduce-'.$line->id.'-'.$i) ? GETPOST('pricetoproduce-'.$line->id.'-'.$i) : ($manufacturingcost ? price($manufacturingcost) : ''));
								print '<td class="right"><input type="text" class="width75 right" name="pricetoproduce-'.$line->id.'-'.$i.'" value="'.$preselected.'"></td>';
							} else {
								print '<td><input type="hidden" class="width50 right" name="pricetoproduce-'.$line->id.'-'.$i.'" value="'.($manufacturingcost ? $manufacturingcost : '').'"></td>';
							}
						}
						// Already produced
						print '<td></td>';
						// Warehouse
						print '<td>';
						if ($tmpproduct->type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
							$preselected = (GETPOSTISSET('idwarehousetoproduce-'.$line->id.'-'.$i) ? GETPOST('idwarehousetoproduce-'.$line->id.'-'.$i) : ($object->fk_warehouse > 0 ? $object->fk_warehouse : 'ifone'));
							print $formproduct->selectWarehouses($preselected, 'idwarehousetoproduce-'.$line->id.'-'.$i, '', 1, 0, $line->fk_product, '', 1, 0, null, 'maxwidth200 csswarehouse_'.$line->id.'_'.$i);
						} else {
							print '<span class="opacitymedium">'.$langs->trans("NoStockChangeOnServices").'</span>';
						}
						print '</td>';
						// Lot
						if (isModEnabled('productbatch')) {
							print '<td>';
							if ($tmpproduct->status_batch) {
								$preselected = (GETPOSTISSET('batchtoproduce-'.$line->id.'-'.$i) ? GETPOST('batchtoproduce-'.$line->id.'-'.$i) : '');
								print '<input type="text" class="width75" name="batchtoproduce-'.$line->id.'-'.$i.'" value="'.$preselected.'">';
							}
							print '</td>';
							// Batch number in same column than the stock movement picto
							if ($tmpproduct->status_batch) {
								$type = 'batch';
								print '<td align="right" class="split">';
								print img_picto($langs->trans('AddStockLocationLine'), 'split.png', 'class="splitbutton" onClick="addDispatchLine('.$line->id.', \''.$type.'\', \'qtymissing\')"');
								print '</td>';

								print '<td align="right"  class="splitall">';
								if (($action == 'consumeorproduce' || $action == 'consumeandproduceall') && $tmpproduct->status_batch == 2) {
									print img_picto($langs->trans('SplitAllQuantity'), 'split.png', 'class="splitbutton splitallbutton field-error-icon" onClick="addDispatchLine('.$line->id.', \'batch\', \'alltoproduce\')"');
								} //
								print '</td>';
							} else {
								print '<td></td>';

								print '<td></td>';
							}
						}

						// Action delete
						print '<td></td>';

						print '</tr>';
					}
				}
			}
		}

		print '</table>';
		print '</div>';

		print '</div>';
		print '</div>';
	}

	if (in_array($action, array('consumeorproduce', 'consumeandproduceall', 'addconsumeline'))) {
		print "</form>\n";
	} ?>

		<script  type="text/javascript" language="javascript">

			$(document).ready(function() {
				//Consumption : When a warehouse is selected, only the lot/serial numbers that are available in it are offered
				updateselectbatchbywarehouse();
				//Consumption : When a lot/serial number is selected and it is only available in one warehouse, the warehouse is automatically selected
				updateselectwarehousebybatch();
			});

			function updateselectbatchbywarehouse() {
				$(document).on('change', "select[name*='idwarehouse']", function () {
					console.log("We change warehouse so we update the list of possible batch number");

					var selectwarehouse = $(this);

					var selectbatch_name = selectwarehouse.attr('name').replace('idwarehouse', 'batch');
					var selectbatch = $("datalist[id*='" + selectbatch_name + "']");
					var selectedbatch = selectbatch.val();

					var product_element_name = selectwarehouse.attr('name').replace('idwarehouse', 'product');

					$.ajax({
						type: "POST",
						url: "<?php echo DOL_URL_ROOT . '/mrp/ajax/interface.php'; ?>",
						data: {
							action: "updateselectbatchbywarehouse",
							permissiontoproduce: <?php echo $permissiontoproduce ?>,
							warehouse_id: $(this).val(),
							token: '<?php echo currentToken(); ?>',
							product_id: $("input[name='" + product_element_name + "']").val()
						}
					}).done(function (data) {

						selectbatch.empty();

						if (typeof data == "object") {
							console.log("data is already type object, no need to parse it");
						} else {
							console.log("data is type "+(typeof data));
							data = JSON.parse(data);
						}

						selectbatch.append($('<option>', {
							value: '',
						}));

						$.each(data, function (key, value) {

							if(selectwarehouse.val() == -1) {
								var label = " (<?php echo $langs->trans('Stock total') ?> : " + value + ")";
							} else {
								var label =  " (<?php echo $langs->trans('Stock') ?> : " + value + ")";
							}

							if(key === selectedbatch) {
								var option ='<option value="'+key+'" selected>'+ label +'</option>';
							} else {
								var option ='<option value="'+key+'">'+ label +'</option>';
							}

							selectbatch.append(option);
						});
					});
				});
			}

			function updateselectwarehousebybatch() {
				$(document).on('change', 'input[name*=batch]', function(){
					console.log("We change batch so we update the list of possible warehouses");

					var selectbatch = $(this);

					var selectwarehouse_name = selectbatch.attr('name').replace('batch', 'idwarehouse');
					var selectwarehouse = $("select[name*='" + selectwarehouse_name + "']");
					var selectedwarehouse = selectwarehouse.val();

					if(selectedwarehouse != -1){
						return;
					}

					var product_element_name = selectbatch.attr('name').replace('batch', 'product');

					$.ajax({
						type: "POST",
						url: "<?php echo DOL_URL_ROOT . '/mrp/ajax/interface.php'; ?>",
						data: {
							action: "updateselectwarehousebybatch",
							permissiontoproduce: <?php echo $permissiontoproduce ?>,
							batch: $(this).val(),
							token: '<?php echo currentToken(); ?>',
							product_id: $("input[name='" + product_element_name + "']").val()
						}
					}).done(function (data) {

						if (typeof data == "object") {
							console.log("data is already type object, no need to parse it");
						} else {
							console.log("data is type "+(typeof data));
							data = JSON.parse(data);
						}

						if(data != 0){
							selectwarehouse.val(data).change();
						}
					});
				});
			}

		</script>

	<?php
}

// End of page
llxFooter();
$db->close();
