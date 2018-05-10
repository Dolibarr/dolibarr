<?php
/* Copyright (C) 2003-2006	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2015	Juanjo Menent			<jmenent@2byte.es>
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
 */

/**
 *	\file       htdocs/expedition/shipment.php
 *	\ingroup    expedition
 *  \brief      Tab shipments/delivery receipts on the order
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/order.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/sendings.lib.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
if (! empty($conf->stock->enabled))  require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
if (! empty($conf->propal->enabled)) require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled)) 	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

$langs->loadLangs(array('orders',"companies","bills",'propal','deliveries','stocks',"productbatch",'incoterm'));

$id=GETPOST('id','int');			// id of order
$ref= GETPOST('ref','alpha');
$action=GETPOST('action','alpha');

// Security check
$socid=0;
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$id);

$object = new Commande($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once




/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Categorisation dans projet
    if ($action == 'classin')
    {
    	$object = new Commande($db);
    	$object->fetch($id);
    	$object->setProject(GETPOST('projectid','int'));
    }

    if ($action == 'confirm_cloture' && GETPOST('confirm','alpha') == 'yes')
    {
    	$object = new Commande($db);
    	$object->fetch($id);
    	$result = $object->cloture($user);
    }

    // Positionne ref commande client
    else if ($action == 'setref_client' && $user->rights->commande->creer) {
        $result = $object->set_ref_client($user, GETPOST('ref_client'));
        if ($result < 0)
        {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }

    if ($action == 'setdatedelivery' && $user->rights->commande->creer)
    {
    	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
    	$datelivraison=dol_mktime(0, 0, 0, GETPOST('liv_month','int'), GETPOST('liv_day','int'),GETPOST('liv_year','int'));

    	$object = new Commande($db);
    	$object->fetch($id);
    	$result=$object->set_date_livraison($user,$datelivraison);
    	if ($result < 0)
    		setEventMessages($object->error, $object->errors, 'errors');
    }
    /*
    if ($action == 'setdeliveryaddress' && $user->rights->commande->creer)
    {
    	$object = new Commande($db);
    	$object->fetch($id);
    	$object->setDeliveryAddress(GETPOST('delivery_address_id','int'));
    	if ($result < 0)
    		setEventMessages($object->error, $object->errors, 'errors');
    }
    */
    if ($action == 'setmode' && $user->rights->commande->creer)
    {
    	$object = new Commande($db);
    	$object->fetch($id);
    	$result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
    	if ($result < 0)
    		setEventMessages($object->error, $object->errors, 'errors');
    }

    if ($action == 'setavailability' && $user->rights->commande->creer) {
        $object = new Commande($db);
        $object->fetch($id);
        $result=$object->availability(GETPOST('availability_id'));
        if ($result < 0)
            setEventMessages($object->error, $object->errors, 'errors');
    }

    if ($action == 'setdemandreason' && $user->rights->commande->creer) {
        $object = new Commande($db);
        $object->fetch($id);
        $result=$object->demand_reason(GETPOST('demand_reason_id'));
        if ($result < 0)
            setEventMessages($object->error, $object->errors, 'errors');
    }

    if ($action == 'setconditions' && $user->rights->commande->creer)
    {
    	$object = new Commande($db);
    	$object->fetch($id);
    	$result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
    	if ($result < 0)
    		setEventMessages($object->error, $object->errors, 'errors');
    }

    // Set incoterm
    elseif ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
    {
    	$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
    	if ($result < 0) {
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }

    // shipping method
    if ($action == 'setshippingmethod' && $user->rights->commande->creer) {
        $object = new Commande($db);
        $object->fetch($id);
        $result=$object->setShippingMethod(GETPOST('shipping_method_id', 'int'));
    	if ($result < 0)
    		setEventMessages($object->error, $object->errors, 'errors');
    }

    // warehouse
    if ($action == 'setwarehouse' && $user->rights->commande->creer) {
        $object = new Commande($db);
        $object->fetch($id);
        $result = $object->setWarehouse(GETPOST('warehouse_id', 'int'));
        if ($result < 0)
            setEventMessages($object->error, $object->errors, 'errors');
    }

    if ($action == 'update_extras')
    {
    	$object->oldcopy = dol_clone($object);

    	// Fill array 'array_options' with data from update form
        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute', 'none'));
        if ($ret < 0) $error++;

        if (! $error)
        {
            // Actions on extra fields
            $result = $object->insertExtraFields('SHIPMENT_MODIFY');
			if ($result < 0)
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
        }

        if ($error)
            $action = 'edit_extras';
    }

    if ($action == 'set_thirdparty' && $user->rights->commande->creer)
    {
        $object->fetch($id);
        $object->setValueFrom('fk_soc', $socid, '', '', 'date', '', $user, 'ORDER_MODIFY');

        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
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
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

llxHeader('',$langs->trans('OrderCard'),'');


if ($id > 0 || ! empty($ref))
{
	$object = new Commande($db);
	if ( $object->fetch($id,$ref) > 0)
	{
		$object->loadExpeditions(1);

		$product_static=new Product($db);

		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$author = new User($db);
		$author->fetch($object->user_author_id);

		$res = $object->fetch_optionals();

		$head = commande_prepare_head($object);
		dol_fiche_head($head, 'shipping', $langs->trans("CustomerOrder"), -1, 'order');


		$formconfirm = '';

		// Confirm validation
		if ($action == 'cloture')
		{
			$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$id,$langs->trans("CloseShipment"),$langs->trans("ConfirmCloseShipment"),"confirm_cloture");

		}

		if (! $formconfirm) {
		    $parameters = array();
		    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		    if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		    elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;


		// Order card

		$linkback = '<a href="' . DOL_URL_ROOT . '/commande/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';


		$morehtmlref='<div class="refidno">';
		// Ref customer
		$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->commande->creer, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->commande->creer, 'string', '', null, null, '', 1);
	    // Thirdparty
	    $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
	    // Project
	    if (! empty($conf->projet->enabled))
	    {
	        $langs->load("projects");
	        $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
	        if ($user->rights->commande->creer)
	        {
	            if ($action != 'classify')
	                $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
	                if ($action == 'classify') {
	                    //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
	                    $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
	                    $morehtmlref.='<input type="hidden" name="action" value="classin">';
	                    $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	                    $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
	                    $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
	                    $morehtmlref.='</form>';
	                } else {
	                    $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
	                }
	        } else {
	            if (! empty($object->fk_project)) {
	                $proj = new Project($db);
	                $proj->fetch($object->fk_project);
	                $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
	                $morehtmlref.=$proj->ref;
	                $morehtmlref.='</a>';
	            } else {
	                $morehtmlref.='';
	            }
	        }
	    }
	    $morehtmlref.='</div>';


	    dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	    print '<div class="fichecenter">';
	    print '<div class="fichehalfleft">';
	    print '<div class="underbanner clearboth"></div>';

	    print '<table class="border" width="100%">';

		// Discounts for third party
	    if (! empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) {
	    	$filterabsolutediscount = "fk_facture_source IS NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	    	$filtercreditnote = "fk_facture_source IS NOT NULL"; // If we want deposit to be substracted to payments only and not to total of final invoice
	    } else {
	    	$filterabsolutediscount = "fk_facture_source IS NULL OR (description LIKE '(DEPOSIT)%' AND description NOT LIKE '(EXCESS RECEIVED)%')";
	    	$filtercreditnote = "fk_facture_source IS NOT NULL AND (description NOT LIKE '(DEPOSIT)%' OR description LIKE '(EXCESS RECEIVED)%')";
	    }

		print '<tr><td class="titlefield">'.$langs->trans('Discounts').'</td><td colspan="3">';

		$absolute_discount=$soc->getAvailableDiscounts('',$filterabsolutediscount);
		$absolute_creditnote=$soc->getAvailableDiscounts('',$filtercreditnote);
		$absolute_discount=price2num($absolute_discount,'MT');
		$absolute_creditnote=price2num($absolute_creditnote,'MT');

		$thirdparty = $soc;
		$discount_type = 0;
		$backtopage = urlencode($_SERVER["PHP_SELF"] . '?id=' . $object->id);
		$cannotApplyDiscount = 1;
		include DOL_DOCUMENT_ROOT.'/core/tpl/object_discounts.tpl.php';
		print '</td></tr>';

		// Date
		print '<tr><td>'.$langs->trans('Date').'</td>';
		print '<td colspan="2">';
		print dol_print_date($object->date,'daytext');
		if ($object->hasDelay() && empty($object->date_livraison)) {
		    print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
		}
		print '</td>';
		print '</tr>';

		// Delivery date planned
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DateDeliveryPlanned');
		print '</td>';

		if ($action != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editdate_livraison')
		{
			print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setdatedelivery">';
			$form->select_date($object->date_livraison>0?$object->date_livraison:-1,'liv_','','','',"setdatedelivery");
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			print dol_print_date($object->date_livraison,'daytext');
			if ($object->hasDelay() && ! empty($object->date_livraison)) {
			    print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
			}
		}
		print '</td>';
		// Note on several rows
		//print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
		//print nl2br($object->note_public);
		//print '</td>';
		print '</tr>';

        // Shipping Method
        print '<tr><td>';
        print '<table width="100%" class="nobordernopadding"><tr><td>';
        print $langs->trans('SendingMethod');
        print '</td>';
        if ($action != 'editshippingmethod' && $user->rights->expedition->creer)
            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editshippingmethod&amp;id='.$object->id.'">'.img_edit($langs->trans('SetShippingMode'),1).'</a></td>';
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
        if (! empty($conf->stock->enabled) && ! empty($conf->global->WAREHOUSE_ASK_WAREHOUSE_DURING_ORDER)) {
            require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
            $formproduct=new FormProduct($db);
            print '<tr><td>';
            print '<table width="100%" class="nobordernopadding"><tr><td>';
            print $langs->trans('Warehouse');
            print '</td>';
            if ($action != 'editwarehouse' && $user->rights->commande->creer)
                print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editwarehouse&amp;id='.$object->id.'">'.img_edit($langs->trans('SetWarehouse'),1).'</a></td>';
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

		// Terms of payment
		/*
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';

		if ($action != 'editconditions' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
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
		if ($action != 'editmode' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
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

		// Availability
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('AvailabilityPeriod');
		print '</td>';
		if ($action != 'editavailability')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editavailability&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetAvailability'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editavailability') {
			$form->form_availability($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->availability_id, 'availability_id', 1);
		} else {
			$form->form_availability($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->availability_id, 'none', 1);
		}
		print '</td></tr>';

		// Source
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Source');
		print '</td>';
		if ($action != 'editdemandreason')
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editdemandreason&amp;id=' . $object->id . '">' . img_edit($langs->trans('SetDemandReason'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editdemandreason') {
			$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'demand_reason_id', 1);
		} else {
			$form->formInputReason($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->demand_reason_id, 'none');
		}

		$tmparray=$object->getTotalWeightVolume();
		$totalWeight=$tmparray['weight'];
		$totalVolume=$tmparray['volume'];
		if ($totalWeight || $totalVolume)
		{
		    print '<tr><td>'.$langs->trans("CalculatedWeight").'</td>';
		    print '<td>';
		    print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND)?$conf->global->MAIN_WEIGHT_DEFAULT_ROUND:-1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT)?$conf->global->MAIN_WEIGHT_DEFAULT_UNIT:'no');
		    print '</td></tr>';
		    print '<tr><td>'.$langs->trans("CalculatedVolume").'</td>';
		    print '<td>';
		    print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND)?$conf->global->MAIN_VOLUME_DEFAULT_ROUND:-1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT)?$conf->global->MAIN_VOLUME_DEFAULT_UNIT:'no');
		    print '</td></tr>';
		}

		// TODO How record was recorded OrderMode (llx_c_input_method)

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
		    print '<tr><td>';
		    print '<table width="100%" class="nobordernopadding"><tr><td>';
		    print $langs->trans('IncotermLabel');
		    print '<td><td align="right">';
		    if ($user->rights->commande->creer) print '<a href="'.$_SERVER['PHP_SELF'].'/expedition/shipment.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
		    else print '&nbsp;';
		    print '</td></tr></table>';
		    print '</td>';
		    print '<td colspan="3">';
		    if ($action != 'editincoterm')
		    {
		        print $form->textwithpicto($object->display_incoterms(), $object->libelle_incoterms, 1);
		    }
		    else
		    {
		        print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''), $_SERVER['PHP_SELF'].'?id='.$object->id);
		    }
		    print '</td></tr>';
		}

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		if (!empty($conf->multicurrency->enabled) && ($object->multicurrency_code != $conf->currency))
		{
		    // Multicurrency Amount HT
		    print '<tr><td class="titlefieldmiddle">' . fieldLabel('MulticurrencyAmountHT','multicurrency_total_ht') . '</td>';
		    print '<td class="nowrap">' . price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		    print '</tr>';

		    // Multicurrency Amount VAT
		    print '<tr><td>' . fieldLabel('MulticurrencyAmountVAT','multicurrency_total_tva') . '</td>';
		    print '<td class="nowrap">' . price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		    print '</tr>';

		    // Multicurrency Amount TTC
		    print '<tr><td>' . fieldLabel('MulticurrencyAmountTTC','multicurrency_total_ttc') . '</td>';
		    print '<td class="nowrap">' . price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
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
		if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) 		// Localtax1
		{
		    print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td>';
		    print '<td>' . price($object->total_localtax1, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
		}
		if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) 		// Localtax2 IRPF
		{
		    print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td>';
		    print '<td>' . price($object->total_localtax2, 1, '', 1, - 1, - 1, $conf->currency) . '</td></tr>';
		}

		// Total TTC
		print '<tr><td>'.$langs->trans('AmountTTC').'</td><td>'.price($object->total_ttc, 0, '', 1, -1, -1, $conf->currency).'</td>';
		print '</tr>';

		print '</table>';

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';



		/**
		 *  Lines or orders with quantity shipped and remain to ship
		 *  Note: Qty shipped are already available into $object->expeditions[fk_product]
		 */
		print '<table class="noborder noshadow" width="100%">';

		$sql = "SELECT cd.rowid, cd.fk_product, cd.product_type as type, cd.label, cd.description,";
		$sql.= " cd.price, cd.tva_tx, cd.subprice,";
		$sql.= " cd.qty,";
		$sql.= ' cd.date_start,';
		$sql.= ' cd.date_end,';
		$sql.= ' p.rowid as prodid, p.label as product_label, p.entity, p.ref, p.fk_product_type as product_type, p.description as product_desc';
		$sql.= " FROM ".MAIN_DB_PREFIX."commandedet as cd";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
		$sql.= " WHERE cd.fk_commande = ".$object->id;
		$sql.= " ORDER BY cd.rang, cd.rowid";

		//print $sql;
		dol_syslog("shipment.php", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;

			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Description").'</td>';
			print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
			print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			print '<td align="center">'.$langs->trans("KeepToShip").'</td>';
			if (! empty($conf->stock->enabled))
			{
				print '<td align="center">'.$langs->trans("RealStock").'</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}
			print "</tr>\n";

			$var=true;
			$toBeShipped=array();
			$toBeShippedTotal=0;
			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);


				// Show product and description
				$type=isset($objp->type)?$objp->type:$objp->product_type;

				// Try to enhance type detection using date_start and date_end for free lines where type
				// was not saved.
				if (! empty($objp->date_start)) $type=1;
				if (! empty($objp->date_end)) $type=1;

				print '<tr class="oddeven">';

				// Product label
				if ($objp->fk_product > 0)
				{
					// Define output language
					if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
					{
						$object->fetch_thirdparty();

						$prod = new Product($db);
                        $prod->id = $objp->fk_product;
                        $prod->entity = $objp->entity;
						$prod->getMultiLangs();

						$outputlangs = $langs;
						$newlang='';
						if (empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
						if (empty($newlang)) $newlang=$object->thirdparty->default_lang;
						if (! empty($newlang))
						{
							$outputlangs = new Translate("",$conf);
							$outputlangs->setDefaultLang($newlang);
						}

						$label = (! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $objp->product_label;
					}
					else
						$label = (! empty($objp->label)?$objp->label:$objp->product_label);

					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

					// Show product and description
					$product_static->type=$type;
					$product_static->id=$objp->fk_product;
					$product_static->ref=$objp->ref;
                    $product_static->entity = $objp->entity;
					$text=$product_static->getNomUrl(1);
					$text.= ' - '.$label;
					$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description)).'<br>';
                    $description.= $product_static->show_photos('product', $conf->product->multidir_output[$product_static->entity], 1, 1, 0, 0, 0, 80);
					print $form->textwithtooltip($text,$description,3,'','',$i);

					// Show range
					print_date_range($db->jdate($objp->date_start),$db->jdate($objp->date_end));

					// Add description in form
					if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
					{
						print ($objp->description && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
					}

					print '</td>';
				}
				else
				{
					print "<td>";
					if ($type==1) $text = img_object($langs->trans('Service'),'service');
					else $text = img_object($langs->trans('Product'),'product');

					if (! empty($objp->label)) {
						$text.= ' <strong>'.$objp->label.'</strong>';
						print $form->textwithtooltip($text,$objp->description,3,'','',$i);
					} else {
						print $text.' '.nl2br($objp->description);
					}

					// Show range
					print_date_range($db->jdate($objp->date_start),$db->jdate($objp->date_end));
					print "</td>\n";
				}

				// Qty ordered
				print '<td align="center">' . $objp->qty . '</td>';

				// Qty already shipped
				$qtyProdCom=$objp->qty;
				print '<td align="center">';
				// Nb of sending products for this line of order
				$qtyAlreadyShipped = (! empty($object->expeditions[$objp->rowid])?$object->expeditions[$objp->rowid]:0);
				print $qtyAlreadyShipped;
				print '</td>';

				// Qty remains to ship
				print '<td align="center">';
				if ($type == 0 || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
				{
					$toBeShipped[$objp->fk_product] = $objp->qty - $qtyAlreadyShipped;
					$toBeShippedTotal += $toBeShipped[$objp->fk_product];
					print $toBeShipped[$objp->fk_product];
				}
				else
				{
					print '0 ('.$langs->trans("Service").')';
				}
				print '</td>';

				if ($objp->fk_product > 0)
				{
					$product = new Product($db);
					$product->fetch($objp->fk_product);
					$product->load_stock('warehouseopen');
				}

				if ($objp->fk_product > 0 && ($type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES)) && ! empty($conf->stock->enabled))
				{
					print '<td align="center">';
					print $product->stock_reel;
					if ($product->stock_reel < $toBeShipped[$objp->fk_product])
					{
						print ' '.img_warning($langs->trans("StockTooLow"));
					}
					print '</td>';
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print "</tr>\n";

				// Show subproducts lines
				if ($objp->fk_product > 0 && ! empty($conf->global->PRODUIT_SOUSPRODUITS))
				{
					// Set tree of subproducts in product->sousprods
					$product->get_sousproduits_arbo();
					//var_dump($product->sousprods);exit;

					// Define a new tree with quantiies recalculated
					$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
					//var_dump($prods_arbo);
					if (count($prods_arbo) > 0)
					{
						foreach($prods_arbo as $key => $value)
						{
							$img='';
							if ($value['stock'] < $value['stock_alert'])
							{
								$img=img_warning($langs->trans("StockTooLow"));
							}
							print '<tr class="oddeven"><td>&nbsp; &nbsp; &nbsp; -> <a href="'.DOL_URL_ROOT."/product/card.php?id=".$value['id'].'">'.$value['fullpath'].'</a> ('.$value['nb'].')</td>';
							print '<td align="center"> '.$value['nb_total'].'</td>';
							print '<td>&nbsp</td>';
							print '<td>&nbsp</td>';
							print '<td align="center">'.$value['stock'].' '.$img.'</td></tr>'."\n";
						}
					}
				}

				$i++;
			}
			$db->free($resql);

			if (! $num)
			{
				print '<tr '.$bc[false].'><td colspan="5">'.$langs->trans("NoArticleOfTypeProduct").'<br>';
			}

			print "</table>";
		}
		else
		{
			dol_print_error($db);
		}

		print '</div>';


		/*
		 * Boutons Actions
		 */

		if (empty($user->societe_id))
		{
			print '<div class="tabsAction">';

            // Bouton expedier sans gestion des stocks
            if (empty($conf->stock->enabled) && ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED))
			{
				if ($user->rights->expedition->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/card.php?action=create&amp;origin=commande&amp;object_id='.$id.'">'.$langs->trans("CreateShipment").'</a>';
					if ($toBeShippedTotal <= 0)
					{
						print ' '.img_warning($langs->trans("WarningNoQtyLeftToSend"));
					}
				}
				else
				{
					print '<a class="butActionRefused" href="#">'.$langs->trans("CreateShipment").'</a>';
				}
			}
			print "</div>";
		}


        // Bouton expedier avec gestion des stocks

        if (! empty($conf->stock->enabled) && $object->statut == Commande::STATUS_DRAFT)
        {
            print $langs->trans("ValidateOrderFirstBeforeShipment");
        }

		if (! empty($conf->stock->enabled) && ($object->statut > Commande::STATUS_DRAFT && $object->statut < Commande::STATUS_CLOSED))
		{
			if ($user->rights->expedition->creer)
			{
				//print load_fiche_titre($langs->trans("CreateShipment"));
                print '<div class="tabsAction">';

				print '<form method="GET" action="'.DOL_URL_ROOT.'/expedition/card.php">';
				print '<input type="hidden" name="action" value="create">';
				//print '<input type="hidden" name="id" value="'.$object->id.'">';
                print '<input type="hidden" name="shipping_method_id" value="'.$object->shipping_method_id.'">';
				print '<input type="hidden" name="origin" value="commande">';
				print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
				print '<input type="hidden" name="projectid" value="'.$object->fk_project.'">';
				//print '<table class="border" width="100%">';

				$langs->load("stocks");

				//print '<tr>';

				if (! empty($conf->stock->enabled))
				{
					//print '<td>';
					print $langs->trans("WarehouseSource");
					//print '</td>';
					//print '<td>';
					print $formproduct->selectWarehouses(! empty($object->warehouse_id)?$object->warehouse_id:-1, 'entrepot_id', '', 1, 0, 0, '', 0, 0, array(), 'minwidth200');
					if (count($formproduct->cache_warehouses) <= 0)
					{
						print ' &nbsp; '.$langs->trans("WarehouseSourceNotDefined").' <a href="'.DOL_URL_ROOT.'/product/stock/card.php?action=create">'.$langs->trans("AddOne").'</a>';
					}
					//print '</td>';
				}
				//print '<td align="center">';
				print '<input type="submit" class="butAction" named="save" value="'.$langs->trans("CreateShipment").'">';
				if ($toBeShippedTotal <= 0)
				{
					print ' '.img_warning($langs->trans("WarningNoQtyLeftToSend"));
				}
				//print '</td></tr>';

				//print "</table>";
				print "</form>\n";

				print '</div>';

				$somethingshown=1;

			}
			else
			{
				print '<div class="tabsAction">';
				print '<a class="butActionRefused" href="#">'.$langs->trans("CreateShipment").'</a>';
				print '</div>';
			}
		}

		show_list_sending_receive('commande',$object->id);
	}
	else
	{
		/* Commande non trouvee */
		print "Commande inexistante";
	}
}


llxFooter();

$db->close();
