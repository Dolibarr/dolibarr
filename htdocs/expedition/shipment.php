<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2015	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018-2022  Philippe Grand          <philippe.grand@atoo-net.com>
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
 *	\file       htdocs/expedition/shipment.php
 *	\ingroup    expedition
 *  \brief      Tab shipments/delivery receipts on the order
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if (isModEnabled('stock')) {
	require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
}
if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled("product") || isModEnabled("service")) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('orders', 'sendings', 'companies', 'bills', 'propal', 'deliveries', 'stocks', 'productbatch', 'incoterm', 'other'));

$id     = GETPOSTINT('id'); // id of order
$ref    = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$hookmanager->initHooks(array('ordershipmentcard'));


// Security check
$socid = 0;
if (!empty($user->socid)) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'commande', $id);

$object = new Commande($db);
$shipment = new Expedition($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

$result = restrictedArea($user, 'expedition', 0, '');	// We use 0 for id, because there is no particular shipment on this tab, only id of order is known

$permissiontoread = $user->hasRight('expedition', 'lire');
$permissiontoadd = $user->hasRight('expedition', 'creer'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('expedition', 'supprimer') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$permissionnote = $user->hasRight('expedition', 'creer'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('expedition', 'creer'); // Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Categorisation dans projet
	if ($action == 'classin' && $permissiontoadd) {
		$object->fetch($id);
		$object->setProject(GETPOSTINT('projectid'));
	}

	if ($action == 'confirm_cloture' && GETPOST('confirm', 'alpha') == 'yes' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->cloture($user);
	} elseif ($action == 'setref_client' && $permissiontoadd) {
		// Positionne ref commande client
		$result = $object->set_ref_client($user, GETPOST('ref_client'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setdatedelivery' && $permissiontoadd) {
		$datedelivery = dol_mktime(GETPOSTINT('liv_hour'), GETPOSTINT('liv_min'), 0, GETPOSTINT('liv_month'), GETPOSTINT('liv_day'), GETPOSTINT('liv_year'));

		$object->fetch($id);
		$result = $object->setDeliveryDate($user, $datedelivery);
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
	if ($action == 'setmode' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setPaymentMethods(GETPOSTINT('mode_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setavailability' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->availability(GETPOST('availability_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setdemandreason' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->demand_reason(GETPOST('demand_reason_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'setconditions' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setPaymentTerms(GETPOSTINT('cond_reglement_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	} elseif ($action == 'set_incoterms' && isModEnabled('incoterm')) {
		// Set incoterm
		$result = $object->setIncoterms(GETPOSTINT('incoterm_id'), GETPOSTINT('location_incoterms'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// shipping method
	if ($action == 'setshippingmethod' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setShippingMethod(GETPOSTINT('shipping_method_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// warehouse
	if ($action == 'setwarehouse' && $permissiontoadd) {
		$object->fetch($id);
		$result = $object->setWarehouse(GETPOSTINT('warehouse_id'));
		if ($result < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'update_extras' && $permissiontoadd) {
		$object->oldcopy = dol_clone($object, 2);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('SHIPMENT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->fetch($id);
		$object->setValueFrom('fk_soc', $socid, '', '', 'date', '', $user, 'ORDER_MODIFY');

		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit();
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';
}

/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
if (isModEnabled('project')) {
	$formproject = new FormProjets($db);
}

$title = $object->ref." - ".$langs->trans('Shipments');
$help_url = 'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes|DE:Modul_Kundenaufträge';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-expedition page-shipment');


if ($id > 0 || !empty($ref)) {
	$object = new Commande($db);
	if ($object->fetch($id, $ref) > 0) {
		$object->loadExpeditions(1);

		$product_static = new Product($db);

		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals();

		$head = commande_prepare_head($object);
		print dol_get_fiche_head($head, 'shipping', $langs->trans("CustomerOrder"), -1, 'order');


		$formconfirm = '';

		// Confirm validation
		if ($action == 'cloture') {
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".urlencode((string) ($id)), $langs->trans("CloseShipment"), $langs->trans("ConfirmCloseShipment"), "confirm_cloture");
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


		// Order card

		$linkback = '<a href="'.DOL_URL_ROOT.'/commande/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


		$morehtmlref = '<div class="refidno">';
		// Ref customer
		$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_client, $object, $permissiontoadd, 'string', '', 0, 1);
		$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_client, $object, $permissiontoadd, 'string', '', null, null, '', 1);
		// Thirdparty
		$morehtmlref .= '<br>'.$soc->getNomUrl(1);
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

		print '<table class="border centpercent tableforfield">';

		// Discounts for third party
		if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
			$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
			$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be subtracted to payments only and not to total of final invoice
		} else {
			$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
			$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
		}

		print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td colspan="2">';

		$absolute_discount = $soc->getAvailableDiscounts('', $filterabsolutediscount);
		$absolute_creditnote = $soc->getAvailableDiscounts('', $filtercreditnote);
		$absolute_discount = price2num($absolute_discount, 'MT');
		$absolute_creditnote = price2num($absolute_creditnote, 'MT');

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = urlencode($_SERVER["PHP_SELF"].'?id='.$object->id);
		$cannotApplyDiscount = 1;
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
		print '</td></tr>';

		// Date
		print '<tr><td>'.$langs->trans('Date').'</td>';
		print '<td colspan="2">';
		print dol_print_date($object->date, 'day');
		if ($object->hasDelay() && empty($object->delivery_date)) {	// If there is a delivery date planned, warning should be on this date
			print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
		}
		print '</td>';
		print '</tr>';

		// Delivery date planned
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
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
			print '<input type="hidden" name="action" value="setdatedelivery">';
			print $form->selectDate($object->delivery_date ? $object->delivery_date : -1, 'liv_', 1, 1, 0, "setdate_livraison", 1, 0);
			print '<input type="submit" class="button button-edit" value="'.$langs->trans('Modify').'">';
			print '</form>';
		} else {
			print dol_print_date($object->delivery_date, 'dayhour');
			if ($object->hasDelay() && !empty($object->delivery_date)) {
				print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
			}
		}
		print '</td>';
		print '</tr>';

		// Delivery delay
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('AvailabilityPeriod');
		print '</td>';
		if ($action != 'editavailability') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editavailability&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetAvailability'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editavailability') {
			$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'availability_id', 1);
		} else {
			$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id, $object->availability_id, 'none', 1);
		}
		print '</td></tr>';

		// Shipping Method
		print '<tr><td>';
		print '<table width="100%" class="nobordernopadding"><tr><td>';
		print $langs->trans('SendingMethod');
		print '</td>';
		if ($action != 'editshippingmethod' && $user->hasRight('expedition', 'creer')) {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editshippingmethod&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetShippingMode'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editshippingmethod') {
			$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'shipping_method_id', 1);
		} else {
			$form->formSelectShippingMethod($_SERVER['PHP_SELF'].'?id='.$object->id, $object->shipping_method_id, 'none');
		}
		print '</td>';
		print '</tr>';

		// Warehouse
		if (isModEnabled('stock') && getDolGlobalString('WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER')) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct = new FormProduct($db);
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('Warehouse');
			print '</td>';
			if ($action != 'editwarehouse' && $permissiontoadd) {
				print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editwarehouse&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetWarehouse'), 1).'</a></td>';
			}
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($action == 'editwarehouse') {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'warehouse_id', 1);
			} else {
				$formproduct->formSelectWarehouses($_SERVER['PHP_SELF'].'?id='.$object->id, $object->warehouse_id, 'none');
			}
			print '</td>';
			print '</tr>';
		}

		// Source reason (why we have an order)
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Source');
		print '</td>';
		if ($action != 'editdemandreason') {
			print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editdemandreason&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetDemandReason'), 1).'</a></td>';
		}
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editdemandreason') {
			$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'demand_reason_id', 1);
		} else {
			$form->formInputReason($_SERVER['PHP_SELF'].'?id='.$object->id, $object->demand_reason_id, 'none');
		}

		// Terms of payment
		/*
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';

		if ($action != 'editconditions' && $object->statut == Expedition::STATUS_VALIDATED) print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editconditions&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editconditions')
		{
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
		}
		else
		{
			$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none');
		}
		print '</td></tr>';

		// Mode of payment
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && $object->statut == Expedition::STATUS_VALIDATED) print '<td class="right"><a class="editfielda" href="'.$_SERVER["PHP_SELF"].'?action=editmode&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editmode')
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
		}
		else
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'none');
		}
		print '</td></tr>';*/

		$tmparray = $object->getTotalWeightVolume();
		$totalWeight = $tmparray['weight'];
		$totalVolume = $tmparray['volume'];
		if ($totalWeight || $totalVolume) {
			print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
			print '<td colspan="2">';
			print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, getDolGlobalInt('MAIN_WEIGHT_DEFAULT_ROUND', -1), getDolGlobalString('MAIN_WEIGHT_DEFAULT_UNIT', 'no'));
			print '</td></tr>';
			print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
			print '<td colspan="2">';
			print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, getDolGlobalInt('MAIN_VOLUME_DEFAULT_ROUND', -1), getDolGlobalString('MAIN_VOLUME_DEFAULT_UNIT', 'no'));
			print '</td></tr>';
		}

		// TODO How record was recorded OrderMode (llx_c_input_method)

		// Incoterms
		if (isModEnabled('incoterm')) {
			print '<tr><td>';
			print '<table width="100%" class="nobordernopadding"><tr><td>';
			print $langs->trans('IncotermLabel');
			print '<td><td class="right">';
			if ($permissiontoadd) {
				print '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'/expedition/shipment.php?id='.$object->id.'&action=editincoterm&token='.newToken().'">'.img_edit().'</a>';
			} else {
				print '&nbsp;';
			}
			print '</td></tr></table>';
			print '</td>';
			print '<td colspan="2">';
			if ($action != 'editincoterm') {
				print $form->textwithpicto($object->display_incoterms(), $object->label_incoterms, 1);
			} else {
				print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms) ? $object->location_incoterms : ''), $_SERVER['PHP_SELF'].'?id='.$object->id);
			}
			print '</td></tr>';
		}

		$expe = new Expedition($db);
		$extrafields->fetch_name_optionals_label($expe->table_element);

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent tableforfield">';

		if (isModEnabled("multicurrency") && ($object->multicurrency_code != $conf->currency)) {
			// Multicurrency Amount HT
			print '<tr><td class="titlefieldmiddle">'.$form->editfieldkey('MulticurrencyAmountHT', 'multicurrency_total_ht', '', $object, 0).'</td>';
			print '<td class="nowrap">'.price($object->multicurrency_total_ht, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
			print '</tr>';

			// Multicurrency Amount VAT
			print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountVAT', 'multicurrency_total_tva', '', $object, 0).'</td>';
			print '<td class="nowrap">'.price($object->multicurrency_total_tva, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
			print '</tr>';

			// Multicurrency Amount TTC
			print '<tr><td>'.$form->editfieldkey('MulticurrencyAmountTTC', 'multicurrency_total_ttc', '', $object, 0).'</td>';
			print '<td class="nowrap">'.price($object->multicurrency_total_ttc, 0, $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)).'</td>';
			print '</tr>';
		}

		// Total HT
		print '<tr><td class="titlefieldmiddle">'.$langs->trans('AmountHT').'</td>';
		print '<td>'.price($object->total_ht, 0, '', 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';

		// Total VAT
		print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($object->total_tva, 0, '', 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';

		// Amount Local Taxes
		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) { 		// Localtax1
			print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
			print '<td>'.price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
		}
		if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) { 		// Localtax2 IRPF
			print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
			print '<td>'.price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency).'</td></tr>';
		}

		// Total TTC
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($object->total_ttc, 0, '', 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';

		print '</table>';

		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';


		/**
		 *  Lines or orders with quantity shipped and remain to ship
		 *  Note: Qty shipped are already available into $object->expeditions[fk_product]
		 */
		print '<table id="tablelines" class="noborder noshadow" width="100%">';

		$sql = "SELECT cd.rowid, cd.fk_product, cd.product_type as type, cd.label, cd.description,";
		$sql .= " cd.price, cd.tva_tx, cd.subprice,";
		$sql .= " cd.qty, cd.fk_unit, cd.rang,";
		$sql .= ' cd.date_start,';
		$sql .= ' cd.date_end,';
		$sql .= ' cd.special_code,';
		$sql .= ' p.rowid as prodid, p.label as product_label, p.entity, p.ref, p.fk_product_type as product_type, p.description as product_desc,';
		$sql .= ' p.weight, p.weight_units, p.length, p.length_units, p.width, p.width_units, p.height, p.height_units,';
		$sql .= ' p.surface, p.surface_units, p.volume, p.volume_units';
		$sql .= ', p.tobatch, p.tosell, p.tobuy, p.barcode';
		$sql .= ', u.short_label as unit_order';
		$sql .= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_units as u ON cd.fk_unit = u.rowid";
		$sql .= " WHERE cd.fk_commande = ".((int) $object->id);
		$sql .= " ORDER BY cd.rang, cd.rowid";

		//print $sql;
		dol_syslog("shipment.php", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			print '<thead>';
			print '<tr class="liste_titre">';
			if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
				print '<th>'.$langs->trans("Rank").'</th>';
			}
			print '<th>'.$langs->trans("Description").'</th>';
			print '<th class="center">'.$langs->trans("QtyOrdered").'</th>';
			print '<th class="center">'.$langs->trans("QtyShipped").'</th>';
			print '<th class="center">'.$langs->trans("KeepToShip").'</th>';
			if (isModEnabled('stock')) {
				print '<th class="center">'.$langs->trans("RealStock").'</th>';
			} else {
				print '<th>&nbsp;</th>';
			}
			print "</tr>\n";
			print '</thead>';

			$toBeShipped = array();
			$toBeShippedTotal = 0;
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				$parameters = array('i' => $i, 'line' => $objp, 'num' => $num);
				$reshook = $hookmanager->executeHooks('printObjectLine', $parameters, $object, $action);
				if ($reshook < 0) {
					setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
				}

				if (empty($reshook)) {
					// Show product and description
					$type = isset($objp->type) ? $objp->type : $objp->product_type;

					// Try to enhance type detection using date_start and date_end for free lines where type
					// was not saved.
					if (!empty($objp->date_start)) {
						$type = 1;
					}
					if (!empty($objp->date_end)) {
						$type = 1;
					}

					print '<tr class="oddeven">';

					// Rank
					if (getDolGlobalString('MAIN_VIEW_LINE_NUMBER')) {
						print '<td class="center">'.$objp->rang.'</td>';
					}

					// Product label
					if ($objp->fk_product > 0) {
						// Define output language
						if (getDolGlobalInt('MAIN_MULTILANGS') && getDolGlobalString('PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE')) {
							$object->fetch_thirdparty();

							$prod = new Product($db);
							$prod->id = $objp->fk_product;
							$prod->entity = $objp->entity;
							$prod->getMultiLangs();

							$outputlangs = $langs;
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

							$label = (!empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
						} else {
							$label = (!empty($objp->label) ? $objp->label : $objp->product_label);
						}

						print '<td>';
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

						// Show product and description
						$product_static->type = $type;
						$product_static->id = $objp->fk_product;
						$product_static->ref = $objp->ref;
						$product_static->entity = $objp->entity;
						$product_static->status = $objp->tosell;
						$product_static->status_buy = $objp->tobuy;
						$product_static->status_batch = $objp->tobatch;
						$product_static->barcode = $objp->barcode;

						$product_static->weight = $objp->weight;
						$product_static->weight_units = $objp->weight_units;
						$product_static->length = $objp->length;
						$product_static->length_units = $objp->length_units;
						$product_static->width = $objp->width;
						$product_static->width_units = $objp->width_units;
						$product_static->height = $objp->height;
						$product_static->height_units = $objp->height_units;
						$product_static->surface = $objp->surface;
						$product_static->surface_units = $objp->surface_units;
						$product_static->volume = $objp->volume;
						$product_static->volume_units = $objp->volume_units;

						$text = $product_static->getNomUrl(1);
						$text .= ' - '.$label;
						$description = (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE') ? '' : dol_htmlentitiesbr($objp->description)).'<br>';
						$description .= $product_static->show_photos('product', $conf->product->multidir_output[$product_static->entity], 1, 1, 0, 0, 0, 80);
						print $form->textwithtooltip($text, $description, 3, '', '', $i);

						// Show range
						print_date_range($db->jdate($objp->date_start), $db->jdate($objp->date_end));

						// Add description in form
						if (getDolGlobalInt('PRODUIT_DESC_IN_FORM_ACCORDING_TO_DEVICE')) {
							print ($objp->description && $objp->description != $objp->product_label) ? '<br>'.dol_htmlentitiesbr($objp->description) : '';
						}

						print '</td>';
					} else {
						print "<td>";
						if ($type == 1) {
							$text = img_object($langs->trans('Service'), 'service');
						} else {
							$text = img_object($langs->trans('Product'), 'product');
						}

						if (!empty($objp->label)) {
							$text .= ' <strong>'.$objp->label.'</strong>';
							print $form->textwithtooltip($text, $objp->description, 3, '', '', $i);
						} else {
							print $text.' '.nl2br($objp->description);
						}

						// Show range
						print_date_range($db->jdate($objp->date_start), $db->jdate($objp->date_end));
						print "</td>\n";
					}

					// Qty ordered
					print '<td class="center">'.$objp->qty.($objp->unit_order ? ' '.$objp->unit_order : '').'</td>';

					// Qty already shipped
					$qtyProdCom = $objp->qty;
					print '<td class="center">';
					// Nb of sending products for this line of order
					$qtyAlreadyShipped = (!empty($object->expeditions[$objp->rowid]) ? $object->expeditions[$objp->rowid] : 0);
					print $qtyAlreadyShipped;
					print($objp->unit_order ? ' '.$objp->unit_order : '').'</td>';

					// Qty remains to ship
					print '<td class="center">';
					if ($type == 0 || getDolGlobalString('STOCK_SUPPORTS_SERVICES') || getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES')) {
						$toBeShipped[$objp->fk_product] = $objp->qty - $qtyAlreadyShipped;
						$toBeShippedTotal += $toBeShipped[$objp->fk_product];
						print $toBeShipped[$objp->fk_product];
					} else {
						print '0 <span class="opacitymedium">('.$langs->trans("Service").')</span>';
					}
					print($objp->unit_order ? ' '.$objp->unit_order : '').'</td>';

					if ($objp->fk_product > 0) {
						$product = new Product($db);
						$product->fetch($objp->fk_product);
						$product->load_stock('warehouseopen');
					}

					if ($objp->fk_product > 0 && ($type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) && isModEnabled('stock')) {
						print '<td class="center">';
						print $product->stock_reel;
						if ($product->stock_reel < $toBeShipped[$objp->fk_product]) {
							print ' '.img_warning($langs->trans("StockTooLow"));
							if (getDolGlobalString('STOCK_CORRECT_STOCK_IN_SHIPMENT')) {
								$nbPiece = $toBeShipped[$objp->fk_product] - $product->stock_reel;
								print ' &nbsp; '.$langs->trans("GoTo").' <a href="'.DOL_URL_ROOT.'/product/stock/product.php?id='.((int) $product->id).'&action=correction&token='.newToken().'&nbpiece='.urlencode((string) ($nbPiece)).'&backtopage='.urlencode((string) ($_SERVER["PHP_SELF"].'?id='.((int) $object->id))).'">'.$langs->trans("CorrectStock").'</a>';
							}
						}
						print '</td>';
					} elseif ($objp->fk_product > 0 && $type == Product::TYPE_SERVICE && getDolGlobalString('SHIPMENT_SUPPORTS_SERVICES') && isModEnabled('stock')) {
						print '<td class="center"><span class="opacitymedium">('.$langs->trans("Service").')</span></td>';
					} else {
						print '<td>&nbsp;</td>';
					}
					print "</tr>\n";

					// Show subproducts lines
					if ($objp->fk_product > 0 && getDolGlobalString('PRODUIT_SOUSPRODUITS')) {
						// Set tree of subproducts in product->sousprods
						$product->get_sousproduits_arbo();
						//var_dump($product->sousprods);exit;

						// Define a new tree with quantiies recalculated
						$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
						//var_dump($prods_arbo);
						if (count($prods_arbo) > 0) {
							foreach ($prods_arbo as $key => $value) {
								$img = '';
								if ($value['stock'] < $value['stock_alert']) {
									$img = img_warning($langs->trans("StockTooLow"));
								}
								print '<tr class="oddeven"><td>&nbsp; &nbsp; &nbsp; -> <a href="'.DOL_URL_ROOT."/product/card.php?id=".$value['id'].'">'.$value['fullpath'].'</a> ('.$value['nb'].')</td>';
								print '<td class="center"> '.$value['nb_total'].'</td>';
								print '<td>&nbsp;</td>';
								print '<td>&nbsp;</td>';
								print '<td class="center">'.$value['stock'].' '.$img.'</td></tr>'."\n";
							}
						}
					}
				}
				$i++;
			}
			$db->free($resql);

			if (!$num) {
				print '<tr '.$bc[false].'><td colspan="5">'.$langs->trans("NoArticleOfTypeProduct").'<br>';
			}

			print "</table>";
		} else {
			dol_print_error($db);
		}

		print '</div>';


		/*
		 * Boutons Actions
		 */

		if (empty($user->socid)) {
			print '<div class="tabsAction">';

			// Bouton expedier sans gestion des stocks
			if (!isModEnabled('stock') && ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED)) {
				if ($user->hasRight('expedition', 'creer')) {
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/card.php?action=create&amp;origin=commande&amp;object_id='.$id.'">'.$langs->trans("CreateShipment").'</a>';
					if ($toBeShippedTotal <= 0) {
						print ' '.img_warning($langs->trans("WarningNoQtyLeftToSend"));
					}
				} else {
					print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("CreateShipment").'</a>';
				}
			}
			print "</div>";
		}


		// Button to create a shipment

		if (isModEnabled('stock') && $object->statut == Commande::STATUS_DRAFT) {
			print $langs->trans("ValidateOrderFirstBeforeShipment");
		}

		if (isModEnabled('stock') && ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED)) {
			if ($user->hasRight('expedition', 'creer')) {
				//print load_fiche_titre($langs->trans("CreateShipment"));
				print '<div class="tabsAction">';

				print '<form method="GET" action="'.DOL_URL_ROOT.'/expedition/card.php">';
				print '<input type="hidden" name="action" value="create">';
				//print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="shipping_method_id" value="'.$object->shipping_method_id.'">';
				print '<input type="hidden" name="origin" value="commande">';
				print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
				print '<input type="hidden" name="projectid" value="'.$object->fk_project.'">';
				//print '<table class="border centpercent">';

				$langs->load("stocks");

				//print '<tr>';

				if (isModEnabled('stock')) {
					//print '<td>';
					print $langs->trans("WarehouseSource");
					//print '</td>';
					//print '<td>';
					print $formproduct->selectWarehouses(!empty($object->warehouse_id) ? $object->warehouse_id : 'ifone', 'entrepot_id', '', 1, 0, 0, '', 0, 0, array(), 'minwidth200');
					if (count($formproduct->cache_warehouses) <= 0) {
						print ' &nbsp; '.$langs->trans("WarehouseSourceNotDefined").' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create">'.$langs->trans("AddOne").'</a>';
					}
					//print '</td>';
				}
				//print '<td class="center">';
				print '<input type="submit" class="butAction" named="save" value="'.$langs->trans("CreateShipment").'">';
				if ($toBeShippedTotal <= 0) {
					print ' '.img_warning($langs->trans("WarningNoQtyLeftToSend"));
				}
				//print '</td></tr>';

				//print "</table>";
				print "</form>\n";

				print '</div>';

				$somethingshown = 1;
			} else {
				print '<div class="tabsAction">';
				print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("CreateShipment").'</a>';
				print '</div>';
			}
		}

		show_list_sending_receive('commande', $object->id);
	} else {
		/* Order not found */
		setEventMessages($langs->trans("NonExistentOrder"), null, 'errors');
	}
}

// End of page
llxFooter();
$db->close();
