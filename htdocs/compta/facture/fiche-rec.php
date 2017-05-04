<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013      Florian Henry	    <florian.henry@open-concept.pro>
 * Copyright (C) 2013      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016      Meziane Sof		<virtualsof@yahoo.fr>
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
 *	\file       htdocs/compta/facture/fiche-rec.php
 *	\ingroup    facture
 *	\brief      Page to show predefined invoice
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
if (! empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
}
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';

$langs->load('bills');
$langs->load('compta');
$langs->load('admin');

// Security check
$id=(GETPOST('facid','int')?GETPOST('facid','int'):GETPOST('id','int'));
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'alpha');
$lineid=GETPOST('lineid','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action', 'alpha');
if ($user->societe_id) $socid=$user->societe_id;
$objecttype = 'facture_rec';
if ($action == "create" || $action == "add") $objecttype = '';
$result = restrictedArea($user, 'facture', $id, $objecttype);
$projectid = GETPOST('projectid','int');

$search_ref=GETPOST('search_ref');
$search_societe=GETPOST('search_societe');
$search_montant_ht=GETPOST('search_montant_ht');
$search_montant_vat=GETPOST('search_montant_vat');
$search_montant_ttc=GETPOST('search_montant_ttc');
$day=GETPOST('day');
$year=GETPOST('year');
$month=GETPOST('month');
$day_date_when=GETPOST('day_date_when');
$year_date_when=GETPOST('year_date_when');
$month_date_when=GETPOST('month_date_when');
$search_frequency=GETPOST('search_frequency');

$limit = GETPOST('limit')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0; }
$offset = $limit * $page;
if (! $sortorder) $sortorder='DESC';
if (! $sortfield) $sortfield='f.titre';
$pageprev = $page - 1;
$pagenext = $page + 1;

$object = new FactureRec($db);
if (($id > 0 || $ref) && $action != 'create' && $action != 'add')
{
	$ret = $object->fetch($id, $ref);
	if (!$ret)
	{
		setEventMessages($langs->trans("ErrorRecordNotFound"), null, 'errors');
	}
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicereccard','globalcard'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label('facture');
$search_array_options=$extrafields->getOptionalsFromPost($extralabels,'','search_');

$permissionnote = $user->rights->facture->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->facture->creer;	// Used by the include of actions_dellink.inc.php
$permissiontoedit = $user->rights->facture->creer; // Used by the include of actions_lineupdonw.inc.php

$arrayfields=array(
    'f.titre'=>array('label'=>$langs->trans("Ref"), 'checked'=>1),
    's.nom'=>array('label'=>$langs->trans("ThirdParty"), 'checked'=>1),
    'f.total'=>array('label'=>$langs->trans("AmountHT"), 'checked'=>1),
    'f.tva'=>array('label'=>$langs->trans("AmountVAT"), 'checked'=>1),
    'f.total_ttc'=>array('label'=>$langs->trans("AmountTTC"), 'checked'=>1),
    'f.frequency'=>array('label'=>$langs->trans("RecurringInvoiceTemplate"), 'checked'=>1),
    'f.date_last_gen'=>array('label'=>$langs->trans("DateLastGeneration"), 'checked'=>1),
    'f.date_when'=>array('label'=>$langs->trans("NextDateToExecution"), 'checked'=>1),
    'f.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'f.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
);
// Extra fields
if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
{
    foreach($extrafields->attribute_label as $key => $val)
    {
        $arrayfields["ef.".$key]=array('label'=>$extrafields->attribute_label[$key], 'checked'=>$extrafields->attribute_list[$key], 'position'=>$extrafields->attribute_pos[$key], 'enabled'=>$extrafields->attribute_perms[$key]);
    }
}


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if (GETPOST('cancel')) $action='';

    // Set note
    include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not include_once

    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

    include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

    // Do we click on purge search criteria ?
    if (GETPOST("button_removefilter_x") || GETPOST("button_removefilter.x") || GETPOST("button_removefilter")) // All test are required to be compatible with all browsers
    {
        $search_ref='';
        $search_societe='';
        $search_montant_ht='';
        $search_montant_vat='';
        $search_montant_ttc='';
        $day='';
        $year='';
        $month='';
        $day_date_when='';
        $year_date_when='';
        $month_date_when='';
        $search_frequency='';
        $search_array_options=array();
    }

    // Create predefined invoice
    if ($action == 'add')
    {
    	if (! GETPOST('titre'))
    	{
    		setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("Title")), null, 'errors');
    		$action = "create";
    		$error++;
    	}

    	$frequency=GETPOST('frequency', 'int');
    	$reyear=GETPOST('reyear');
    	$remonth=GETPOST('remonth');
    	$reday=GETPOST('reday');
    	$rehour=GETPOST('rehour');
    	$remin=GETPOST('remin');
    	$nb_gen_max=GETPOST('nb_gen_max', 'int');
    	//if (empty($nb_gen_max)) $nb_gen_max =0;

    	if (GETPOST('frequency'))
    	{
    		if (empty($reyear) || empty($remonth) || empty($reday))
    		{
    			setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("Date")), null, 'errors');
    			$action = "create";
    			$error++;
    		}
    		if ($nb_gen_max === '')
    		{
    			setEventMessages($langs->transnoentities("ErrorFieldRequired",$langs->trans("MaxPeriodNumber")), null, 'errors');
    			$action = "create";
    			$error++;
    		}
    	}

    	if (! $error)
    	{
    		$object->titre = GETPOST('titre', 'alpha');
    		$object->note_private = GETPOST('note_private');
    		$object->note_public  = GETPOST('note_public');
    		$object->usenewprice = GETPOST('usenewprice');

    		$object->frequency = $frequency;
    		$object->unit_frequency = GETPOST('unit_frequency', 'alpha');
    		$object->nb_gen_max = $nb_gen_max;
    		$object->auto_validate = GETPOST('auto_validate', 'int');

    		$object->fk_project = $projectid;

    		$date_next_execution = dol_mktime($rehour, $remin, 0, $remonth, $reday, $reyear);
    		$object->date_when = $date_next_execution;

    		// Get first contract linked to invoice used to generate template
    		if ($id > 0)
    		{
    			$srcObject = new Facture($db);
    			$srcObject->fetch(GETPOST('facid','int'));

    			$srcObject->fetchObjectLinked();

    			if (! empty($srcObject->linkedObjectsIds['contrat']))
    			{
    				$contractidid = reset($srcObject->linkedObjectsIds['contrat']);

    				$object->origin = 'contrat';
    				$object->origin_id = $contractidid;
    				$object->linked_objects[$object->origin] = $object->origin_id;
    			}
    		}

    		$db->begin();

    		$oldinvoice = new Facture($db);
    		$oldinvoice->fetch($id);

    		$result = $object->create($user, $oldinvoice->id);
    		if ($result > 0)
    		{
    			$result=$oldinvoice->delete($user, 1);
    			if ($result < 0)
    			{
    				$error++;
    				setEventMessages($oldinvoice->error, $oldinvoice->errors, 'errors');
    				$action = "create";
    			}
    		}
    		else
    		{
    			$error++;
    			setEventMessages($object->error, $object->errors, 'errors');
    			$action = "create";
    		}

    		if (! $error)
    		{
    			$db->commit();

    			header("Location: " . $_SERVER['PHP_SELF'] . '?facid=' . $object->id);
       			exit;
    		}
    		else
    		{
    			$db->rollback();

    			$error++;
    			setEventMessages($object->error, $object->errors, 'errors');
    			$action = "create";
    		}
    	}
    }

    // Delete
    if ($action == 'confirm_deleteinvoice' && $confirm == 'yes' && $user->rights->facture->supprimer)
    {
    	$object->delete();
    	header("Location: " . $_SERVER['PHP_SELF'] );
    	exit;
    }


    // Update field
    // Set condition
    if ($action == 'setconditions' && $user->rights->facture->creer)
    {
    	$result=$object->setPaymentTerms(GETPOST('cond_reglement_id', 'int'));

    }
    // Set mode
    elseif ($action == 'setmode' && $user->rights->facture->creer)
    {
    	$result=$object->setPaymentMethods(GETPOST('mode_reglement_id', 'int'));
    }
    // Set project
    elseif ($action == 'classin' && $user->rights->facture->creer)
    {
    	$object->setProject(GETPOST('projectid', 'int'));
    }
    // Set bank account
    elseif ($action == 'setref' && $user->rights->facture->creer)
    {
        //var_dump(GETPOST('ref', 'alpha'));exit;
        $result=$object->setValueFrom('titre', GETPOST('ref', 'alpha'), '', null, 'text', '', $user, 'BILLREC_MODIFY');
        if ($result > 0)
        {
        	$object->titre = GETPOST('ref', 'alpha');
        	$object->ref = $object->titre;
        }
        else dol_print_error($db, $object->error, $object->errors);
    }
    // Set bank account
    elseif ($action == 'setbankaccount' && $user->rights->facture->creer)
    {
    	$result=$object->setBankAccount(GETPOST('fk_account', 'int'));
    }
    // Set frequency and unit frequency
    elseif ($action == 'setfrequency' && $user->rights->facture->creer)
    {
    	$object->setFrequencyAndUnit(GETPOST('frequency', 'int'), GETPOST('unit_frequency', 'alpha'));
    }
    // Set next date of execution
    elseif ($action == 'setdate_when' && $user->rights->facture->creer)
    {
    	$date = dol_mktime(GETPOST('date_whenhour'), GETPOST('date_whenmin'), 0, GETPOST('date_whenmonth'), GETPOST('date_whenday'), GETPOST('date_whenyear'));
    	if (!empty($date)) $object->setNextDate($date);
    }
    // Set max period
    elseif ($action == 'setnb_gen_max' && $user->rights->facture->creer)
    {
    	$object->setMaxPeriod(GETPOST('nb_gen_max', 'int'));
    }
    // Set auto validate
    elseif ($action == 'setauto_validate' && $user->rights->facture->creer)
    {
    	$object->setAutoValidate(GETPOST('auto_validate', 'int'));
    }

    // Delete line
    if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->facture->creer)
    {
    	$object->fetch($id);
    	$object->fetch_thirdparty();

    	$db->begin();

    	$line=new FactureLigneRec($db);

    	// For triggers
    	$line->id = $lineid;

    	if ($line->delete() > 0)
    	{
    		$result=$object->update_price(1);

    		if ($result > 0)
    		{
    		    $db->commit();
    		    $object->fetch($object->id);    // Reload lines
    		}
    		else
    		{
    		    $db->rollback();
    		    setEventMessages($db->lasterror(), null, 'errors');
    		}
    	}
    	else
    	{
    		$db->rollback();
    		setEventMessages($line->error, $line->errors, 'errors');
    	}
    }

    // Add a new line
    if ($action == 'addline' && $user->rights->facture->creer)
    {
    	$langs->load('errors');
    	$error = 0;

    	// Set if we used free entry or predefined product
    	$predef='';
    	$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
    	$price_ht = GETPOST('price_ht');
    	if (GETPOST('prod_entry_mode') == 'free')
    	{
    		$idprod=0;
    		$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
    	}
    	else
    	{
    		$idprod=GETPOST('idprod', 'int');
    		$tva_tx = '';
    	}

    	$qty = GETPOST('qty' . $predef);
    	$remise_percent = GETPOST('remise_percent' . $predef);

    	// Extrafields
    	$extrafieldsline = new ExtraFields($db);
    	$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
    	$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
    	// Unset extrafield
    	if (is_array($extralabelsline))
    	{
    		// Get extra fields
    		foreach ($extralabelsline as $key => $value) {
    			unset($_POST["options_" . $key . $predef]);
    		}
    	}

    	if (empty($idprod) && ($price_ht < 0) && ($qty < 0)) {
    		setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPriceHT'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
    		$error ++;
    	}
    	if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && GETPOST('type') < 0) {
    		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
    		$error ++;
    	}
    	if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && (! ($price_ht >= 0) || $price_ht == '')) 	// Unit price can be 0 but not ''
    	{
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("UnitPriceHT")), null, 'errors');
    		$error ++;
    	}
    	if ($qty == '') {
    		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
    		$error ++;
    	}
    	if (GETPOST('prod_entry_mode') == 'free' && empty($idprod) && empty($product_desc)) {
    		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
    		$error ++;
    	}
    	if ($qty < 0) {
    		$langs->load("errors");
    		setEventMessages($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), null, 'errors');
    		$error ++;
    	}

        if (! $error && ($qty >= 0) && (! empty($product_desc) || ! empty($idprod)))
        {
    	$ret = $object->fetch($id);
    	if ($ret < 0) {
    		dol_print_error($db, $object->error);
    		exit();
    	}
    	$ret = $object->fetch_thirdparty();

    	// Clean parameters
    	$date_start = dol_mktime(GETPOST('date_start' . $predef . 'hour'), GETPOST('date_start' . $predef . 'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start' . $predef . 'month'), GETPOST('date_start' . $predef . 'day'), GETPOST('date_start' . $predef . 'year'));
    	$date_end = dol_mktime(GETPOST('date_end' . $predef . 'hour'), GETPOST('date_end' . $predef . 'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end' . $predef . 'month'), GETPOST('date_end' . $predef . 'day'), GETPOST('date_end' . $predef . 'year'));
    	$price_base_type = (GETPOST('price_base_type', 'alpha') ? GETPOST('price_base_type', 'alpha') : 'HT');

    	// Define special_code for special lines
    	$special_code = 0;
    	// if (empty($_POST['qty'])) $special_code=3; // Options should not exists on invoices

    	// Ecrase $pu par celui du produit
    	// Ecrase $desc par celui du produit
    	// Ecrase $tva_tx par celui du produit
    	// Ecrase $base_price_type par celui du produit
    	// Replaces $fk_unit with the product's
    	if (! empty($idprod))
            {
    		$prod = new Product($db);
    		$prod->fetch($idprod);

    		$label = ((GETPOST('product_label') && GETPOST('product_label') != $prod->label) ? GETPOST('product_label') : '');

    		// Update if prices fields are defined
    		$tva_tx = get_default_tva($mysoc, $object->thirdparty, $prod->id);
    		$tva_npr = get_default_npr($mysoc, $object->thirdparty, $prod->id);
    		if (empty($tva_tx)) $tva_npr=0;

    		$pu_ht = $prod->price;
    		$pu_ttc = $prod->price_ttc;
    		$price_min = $prod->price_min;
    		$price_base_type = $prod->price_base_type;

    		// We define price for product
    		if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
    		{
    			$pu_ht = $prod->multiprices[$object->thirdparty->price_level];
    			$pu_ttc = $prod->multiprices_ttc[$object->thirdparty->price_level];
    			$price_min = $prod->multiprices_min[$object->thirdparty->price_level];
    			$price_base_type = $prod->multiprices_base_type[$object->thirdparty->price_level];
    			if (! empty($conf->global->PRODUIT_MULTIPRICES_USE_VAT_PER_LEVEL))  // using this option is a bug. kept for backward compatibility
    			{
    				if (isset($prod->multiprices_tva_tx[$object->thirdparty->price_level])) $tva_tx=$prod->multiprices_tva_tx[$object->thirdparty->price_level];
    				if (isset($prod->multiprices_recuperableonly[$object->thirdparty->price_level])) $tva_npr=$prod->multiprices_recuperableonly[$object->thirdparty->price_level];
    				if (empty($tva_tx)) $tva_npr=0;
    			}
    		}
    		elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
    		{
    			require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

    			$prodcustprice = new Productcustomerprice($db);

    			$filter = array('t.fk_product' => $prod->id,'t.fk_soc' => $object->thirdparty->id);

    			$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
    			if ($result)
    			{
    				if (count($prodcustprice->lines) > 0)
    				{
    					$pu_ht = price($prodcustprice->lines[0]->price);
    					$pu_ttc = price($prodcustprice->lines[0]->price_ttc);
    					$price_base_type = $prodcustprice->lines[0]->price_base_type;
    					$tva_tx = $prodcustprice->lines[0]->tva_tx;
    				}
    			}
    		}

			$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $tva_tx));
			$tmpprodvat = price2num(preg_replace('/\s*\(.*\)/', '', $prod->tva_tx));
			
    		// if price ht was forced (ie: from gui when calculated by margin rate and cost price). TODO Why this ?
    		if (! empty($price_ht))
    		{
    			$pu_ht = price2num($price_ht, 'MU');
    			$pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
    		}
    		// On reevalue prix selon taux tva car taux tva transaction peut etre different
    		// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
    		elseif ($tmpvat != $tmpprodvat)
    		{
    			if ($price_base_type != 'HT')
    			{
    			    $pu_ht = price2num($pu_ttc / (1 + ($tmpvat / 100)), 'MU');
    			}
    			else
    			{
    			    $pu_ttc = price2num($pu_ht * (1 + ($tmpvat / 100)), 'MU');
    			}
    		}

    		$desc = '';

    		// Define output language
    		if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
    		{
    			$outputlangs = $langs;
    			$newlang = '';
    			if (empty($newlang) && GETPOST('lang_id'))
    				$newlang = GETPOST('lang_id');
    			if (empty($newlang))
    				$newlang = $object->thirdparty->default_lang;
    			if (! empty($newlang))
    			{
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			}

    			$desc = (! empty($prod->multilangs [$outputlangs->defaultlang] ["description"])) ? $prod->multilangs [$outputlangs->defaultlang] ["description"] : $prod->description;
    		}
    		else
    		{
    			$desc = $prod->description;
    		}

                	$desc = dol_concatdesc($desc, $product_desc);

    		// Add custom code and origin country into description
    		if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code)))
    		{
    			$tmptxt = '(';
    			if (! empty($prod->customcode))
    				$tmptxt .= $langs->transnoentitiesnoconv("CustomCode") . ': ' . $prod->customcode;
    			if (! empty($prod->customcode) && ! empty($prod->country_code))
    				$tmptxt .= ' - ';
    			if (! empty($prod->country_code))
    				$tmptxt .= $langs->transnoentitiesnoconv("CountryOrigin") . ': ' . getCountry($prod->country_code, 0, $db, $langs, 0);
    			$tmptxt .= ')';
    			$desc = dol_concatdesc($desc, $tmptxt);

    		}

    		$type = $prod->type;
    		$fk_unit = $prod->fk_unit;

    	}
    	else
    	{
    		$pu_ht = price2num($price_ht, 'MU');
    		$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
    		$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
    		$tva_tx = str_replace('*', '', $tva_tx);
    		if (empty($tva_tx)) $tva_npr=0;
    		$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
    		$desc = $product_desc;
    		$type = GETPOST('type');
    		$fk_unit= GETPOST('units', 'alpha');
    	}

    	// Margin
    	$fournprice = price2num(GETPOST('fournprice' . $predef) ? GETPOST('fournprice' . $predef) : '');
    	$buyingprice = price2num(GETPOST('buying_price' . $predef) != '' ? GETPOST('buying_price' . $predef) : '');    // If buying_price is '0', we must keep this value

    	// Local Taxes
    	$localtax1_tx = get_localtax($tva_tx, 1, $object->thirdparty, $mysoc, $tva_npr);
    	$localtax2_tx = get_localtax($tva_tx, 2, $object->thirdparty, $mysoc, $tva_npr);

    	$info_bits = 0;
    	if ($tva_npr)
    		$info_bits |= 0x01;

    	if (! empty($price_min) && (price2num($pu_ht) * (1 - price2num($remise_percent) / 100) < price2num($price_min)))
    	{
    		$mesg = $langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency));
    		setEventMessages($mesg, null, 'errors');
    	}
    	else
    	{
    		// Insert line
    		$result = $object->addline($desc, $pu_ht, $qty, $tva_tx, $idprod, $remise_percent, $price_base_type, $info_bits, '', $pu_ttc, $type, - 1, $special_code, $label, $fk_unit);

    		if ($result > 0)
    		{
    			/*if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    			{
    			    // Define output language
    			    $outputlangs = $langs;
    			    $newlang = '';
    			    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    			    if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    			    if (! empty($newlang)) {
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			    }
    			    $model=$object->modelpdf;
    			    $ret = $object->fetch($id); // Reload to get new records

    			    $result = $object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    			    if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
    			}*/
    			$object->fetch($object->id);    // Reload lines

    			unset($_POST['prod_entry_mode']);

    			unset($_POST['qty']);
    			unset($_POST['type']);
    			unset($_POST['remise_percent']);
    			unset($_POST['price_ht']);
    			unset($_POST['multicurrency_price_ht']);
    			unset($_POST['price_ttc']);
    			unset($_POST['tva_tx']);
    			unset($_POST['product_ref']);
    			unset($_POST['product_label']);
    			unset($_POST['product_desc']);
    			unset($_POST['fournprice']);
    			unset($_POST['buying_price']);
    			unset($_POST['np_marginRate']);
    			unset($_POST['np_markRate']);
    			unset($_POST['dp_desc']);
    			unset($_POST['idprod']);
    			unset($_POST['units']);

    			unset($_POST['date_starthour']);
    			unset($_POST['date_startmin']);
    			unset($_POST['date_startsec']);
    			unset($_POST['date_startday']);
    			unset($_POST['date_startmonth']);
    			unset($_POST['date_startyear']);
    			unset($_POST['date_endhour']);
    			unset($_POST['date_endmin']);
    			unset($_POST['date_endsec']);
    			unset($_POST['date_endday']);
    			unset($_POST['date_endmonth']);
    			unset($_POST['date_endyear']);

    			unset($_POST['situations']);
    			unset($_POST['progress']);
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    		}

    		$action = '';
    	}
        }
    }

    elseif ($action == 'updateligne' && $user->rights->facture->creer && ! GETPOST('cancel'))
    {
    	if (! $object->fetch($id) > 0)	dol_print_error($db);
    	$object->fetch_thirdparty();

    	// Clean parameters
    	$date_start = '';
    	$date_end = '';
    	//$date_start = dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
    	//$date_end = dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));
    	$description = dol_htmlcleanlastbr(GETPOST('product_desc') ? GETPOST('product_desc') : GETPOST('desc'));
    	$pu_ht = GETPOST('price_ht');
    	$vat_rate = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
    	$qty = GETPOST('qty');

    	// Define info_bits
    	$info_bits = 0;
    	if (preg_match('/\*/', $vat_rate))
            $info_bits |= 0x01;

            // Define vat_rate
            $vat_rate = str_replace('*', '', $vat_rate);
            $localtax1_rate = get_localtax($vat_rate, 1, $object->thirdparty);
            $localtax2_rate = get_localtax($vat_rate, 2, $object->thirdparty);

            // Add buying price
            $fournprice = price2num(GETPOST('fournprice') ? GETPOST('fournprice') : '');
            $buyingprice = price2num(GETPOST('buying_price') != '' ? GETPOST('buying_price') : '');       // If buying_price is '0', we muste keep this value

            // Extrafields
            $extrafieldsline = new ExtraFields($db);
            $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
            $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
            // Unset extrafield
            if (is_array($extralabelsline))
    	    {
                // Get extra fields
                foreach ($extralabelsline as $key => $value)
    	        {
    		         unset($_POST["options_" . $key]);
                }
            }

            // Define special_code for special lines
            $special_code=GETPOST('special_code');
            if (! GETPOST('qty')) $special_code=3;

            /*$line = new FactureLigne($db);
            $line->fetch(GETPOST('lineid'));
            $percent = $line->get_prev_progress($object->id);

            if (GETPOST('progress') < $percent)
            {
                $mesg = '<div class="warning">' . $langs->trans("CantBeLessThanMinPercent") . '</div>';
                setEventMessages($mesg, null, 'warnings');
                $error++;
                $result = -1;
            }*/

            // Check minimum price
            $productid = GETPOST('productid', 'int');
            if (! empty($productid))
            {
    		$product = new Product($db);
    		$product->fetch($productid);

    		$type = $product->type;

    		$price_min = $product->price_min;
    		if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->thirdparty->price_level))
                    $price_min = $product->multiprices_min [$object->thirdparty->price_level];

                    $label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label') : '');

                    // Check price is not lower than minimum (check is done only for standard or replacement invoices)
                    if (($object->type == Facture::TYPE_STANDARD || $object->type == Facture::TYPE_REPLACEMENT) && $price_min && (price2num($pu_ht) * (1 - price2num(GETPOST('remise_percent')) / 100) < price2num($price_min))) {
                        setEventMessages($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min, 'MU'), 0, $langs, 0, 0, - 1, $conf->currency)), null, 'errors');
                        $error ++;
                    }
            } else {
                $type = GETPOST('type');
                $label = (GETPOST('product_label') ? GETPOST('product_label') : '');

                // Check parameters
                if (GETPOST('type') < 0) {
                    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
                    $error ++;
                }
            }
            if ($qty < 0) {
                $langs->load("errors");
                setEventMessages($langs->trans('ErrorQtyForCustomerInvoiceCantBeNegative'), null, 'errors');
                $error ++;
            }

            // Update line
            if (! $error)
    	{
    		$result = $object->updateline(
    			GETPOST('lineid'),
    			$description,
    			$pu_ht,
    			$qty,
                    	$vat_rate,
    			GETPOST('productid'),
    			GETPOST('remise_percent'),
    			'HT',
    			$info_bits,
    			0,
    			0,
    			$type,
    			0,
    			$special_code,
    			$label,
    			GETPOST('units')
    		);

    		if ($result >= 0)
    		{
                    /*if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                        // Define output language
                        $outputlangs = $langs;
                        $newlang = '';
                        if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id'))
                            $newlang = GETPOST('lang_id');
                            if ($conf->global->MAIN_MULTILANGS && empty($newlang))
                                $newlang = $object->thirdparty->default_lang;
                                if (! empty($newlang)) {
                                    $outputlangs = new Translate("", $conf);
                                    $outputlangs->setDefaultLang($newlang);
                                }

                                $ret = $object->fetch($id); // Reload to get new records
                                $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                    }*/

    			$object->fetch($object->id);    // Reload lines

    			unset($_POST['qty']);
    			unset($_POST['type']);
    			unset($_POST['productid']);
    			unset($_POST['remise_percent']);
    			unset($_POST['price_ht']);
    			unset($_POST['multicurrency_price_ht']);
    			unset($_POST['price_ttc']);
    			unset($_POST['tva_tx']);
    			unset($_POST['product_ref']);
    			unset($_POST['product_label']);
    			unset($_POST['product_desc']);
    			unset($_POST['fournprice']);
    			unset($_POST['buying_price']);
    			unset($_POST['np_marginRate']);
    			unset($_POST['np_markRate']);

    			unset($_POST['dp_desc']);
    			unset($_POST['idprod']);
    			unset($_POST['units']);

    			unset($_POST['date_starthour']);
    			unset($_POST['date_startmin']);
    			unset($_POST['date_startsec']);
    			unset($_POST['date_startday']);
    			unset($_POST['date_startmonth']);
    			unset($_POST['date_startyear']);
    			unset($_POST['date_endhour']);
    			unset($_POST['date_endmin']);
    			unset($_POST['date_endsec']);
    			unset($_POST['date_endday']);
    			unset($_POST['date_endmonth']);
    			unset($_POST['date_endyear']);

    			unset($_POST['situations']);
    			unset($_POST['progress']);
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    		}
    	}
    }
}


/*
 *	View
 */

llxHeader('',$langs->trans("RepeatableInvoices"),'ch-facture.html#s-fac-facture-rec');

$form = new Form($db);
$formother = new FormOther($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }
$companystatic = new Societe($db);

$now = dol_now();
$tmparray=dol_getdate($now);
$today = dol_mktime(23,59,59,$tmparray['mon'],$tmparray['mday'],$tmparray['year']);   // Today is last second of current day


/*
 * Create mode
 */
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("CreateRepeatableInvoice"),'','title_accountancy.png');

	$object = new Facture($db);   // Source invoice
	$product_static = new Product($db);

	if ($object->fetch($id, $ref) > 0)
	{
		$result = $object->getLinesArray();

		print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="add">';
		print '<input type="hidden" name="facid" value="'.$object->id.'">';

		dol_fiche_head();

		$rowspan=4;
		if (! empty($conf->projet->enabled)) $rowspan++;
		if ($object->fk_account > 0) $rowspan++;

		print '<table class="border" width="100%">';

		$object->fetch_thirdparty();

		// Title
		print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans("Title").'</td><td>';
		print '<input class="flat quatrevingtpercent" type="text" name="titre" value="'.$_POST["titre"].'">';
		print '</td></tr>';

		// Third party
		print '<tr><td class="titlefieldcreate">'.$langs->trans("Customer").'</td><td>'.$object->thirdparty->getNomUrl(1,'customer').'</td>';
		print '</tr>';

		$note_public=GETPOST('note_public')?GETPOST('note_public'):$object->note_public;
		$note_private=GETPOST('note_private')?GETPOST('note_private'):$object->note_private;

		// Help of substitution key
		$substitutionarray=array(
		    '__TOTAL_HT__' => $langs->trans("AmountHT").' ('.$langs->trans("Example").': '.price($object->total_ht).')',
		    '__TOTAL_TTC__' =>  $langs->trans("AmountTTC").' ('.$langs->trans("Example").': '.price($object->total_ttc).')',
		    '__INVOICE_PREVIOUS_MONTH__' => $langs->trans("PreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, -1, 'm'),'%m').')',
		    '__INVOICE_MONTH__' =>  $langs->trans("MonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($object->date,'%m').')',
		    '__INVOICE_NEXT_MONTH__' => $langs->trans("NextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'm'),'%m').')',
		    '__INVOICE_PREVIOUS_MONTH_TEXT__' => $langs->trans("TextPreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, -1, 'm'),'%B').')',
		    '__INVOICE_MONTH_TEXT__' =>  $langs->trans("TextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($object->date,'%B').')',
		    '__INVOICE_NEXT_MONTH_TEXT__' => $langs->trans("TextNextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'm'), '%B').')',
		    '__INVOICE_PREVIOUS_YEAR__' => $langs->trans("YearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, -1, 'y'),'%Y').')',
		    '__INVOICE_YEAR__' =>  $langs->trans("PreviousYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($object->date,'%Y').')',
		    '__INVOICE_NEXT_YEAR__' => $langs->trans("NextYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($object->date, 1, 'y'),'%Y').')'
		);

		$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br>';
		foreach($substitutionarray as $key => $val)
		{
		    $htmltext.=$key.' = '.$langs->trans($val).'<br>';
		}
		$htmltext.='</i>';

		// Public note
		print '<tr>';
		print '<td class="border tdtop">';
		print $form->textwithpicto($langs->trans('NotePublic'), $htmltext);
		print '</td>';
		print '<td valign="top" colspan="2">';
		$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        print $doleditor->Create(1);

		// Private note
		if (empty($user->societe_id))
		{
		    print '<tr>';
		    print '<td class="border tdtop">';
		    print $form->textwithpicto($langs->trans('NotePrivate'), $htmltext);
		    print '</td>';
		    print '<td valign="top" colspan="2">';
		    $doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		    print $doleditor->Create(1);
		    // print '<textarea name="note_private" wrap="soft" cols="70" rows="'.ROWS_3.'">'.$note_private.'.</textarea>
		    print '</td></tr>';
		}

		// Author
		print "<tr><td>".$langs->trans("Author")."</td><td>".$user->getFullName($langs)."</td></tr>";

		// Payment term
		print "<tr><td>".$langs->trans("PaymentConditions")."</td><td>";
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->cond_reglement_id, 'none');
		print "</td></tr>";

		// Payment mode
		print "<tr><td>".$langs->trans("PaymentMode")."</td><td>";
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none');
		print "</td></tr>";

		// Project
		if (! empty($conf->projet->enabled) && is_object($object->thirdparty) && $object->thirdparty->id > 0)
		{
			$projectid = GETPOST('projectid')?GETPOST('projectid'):$object->fk_project;
			$langs->load('projects');
			print '<tr><td>' . $langs->trans('Project') . '</td><td>';
			$numprojet = $formproject->select_projects($object->thirdparty->id, $projectid, 'projectid', 0, 0, 1, 0, 0, 0, 0, '', 0, 0, '');
			print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid=' . $object->thirdparty->id . '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$object->thirdparty->id.(!empty($id)?'&id='.$id:'')).'">' . $langs->trans("AddProject") . '</a>';
			print '</td></tr>';
		}

		// Bank account
		if ($object->fk_account > 0)
		{
			print "<tr><td>".$langs->trans('RIB')."</td><td>";
			$form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
			print "</td></tr>";
		}

		print "</table>";

		print '<br><br>';


		// Autogeneration
		$title = $langs->trans("Recurrence");
		print load_fiche_titre($title, '', 'calendar');

		print '<table class="border" width="100%">';

		// Frequency
		print '<tr><td class="titlefieldcreate">'.$form->textwithpicto($langs->trans("Frequency"), $langs->transnoentitiesnoconv('toolTipFrequency'))."</td><td>";
		print "<input type='text' name='frequency' value='".GETPOST('frequency', 'int')."' size='4' />&nbsp;".$form->selectarray('unit_frequency', array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), (GETPOST('unit_frequency')?GETPOST('unit_frequency'):'m'));
		print "</td></tr>";

		// First date of execution for cron
		print "<tr><td>".$langs->trans('NextDateToExecution')."</td><td>";
		$date_next_execution = isset($date_next_execution) ? $date_next_execution : (GETPOST('remonth') ? dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear')) : -1);
		print $form->select_date($date_next_execution, '', 1, 1, '', "add", 1, 1, 1);
		print "</td></tr>";

		// Number max of generation
		print "<tr><td>".$langs->trans("MaxPeriodNumber")."</td><td>";
		print '<input type="text" name="nb_gen_max" value="'.GETPOST('nb_gen_max').'" size="5" />';
		print "</td></tr>";

		// Auto validate the invoice
		print "<tr><td>".$langs->trans("StatusOfGeneratedInvoices")."</td><td>";
		$select = array('0'=>$langs->trans('BillStatusDraft'),'1'=>$langs->trans('BillStatusValidated'));
		print $form->selectarray('auto_validate', $select, GETPOST('auto_validate'));
		print "</td></tr>";

		print "</table>";

		print '<br><br>';

		$title = $langs->trans("ProductsAndServices");
		if (empty($conf->service->enabled))
			$title = $langs->trans("Products");
		else if (empty($conf->product->enabled))
			$title = $langs->trans("Services");

		print load_fiche_titre($title, '', '');

		/*
		 * Invoice lines
		 */
		print '<table id="tablelines" class="noborder noshadow" width="100%">';
		// Show object lines
		if (! empty($object->lines))
		{
		    $disableedit=1;
		    $disablemove=1;
		    $disableremove=1;
		    $ret = $object->printObjectLines('', $mysoc, $soc, $lineid, 0);      // No date selector for template invoice
		}

		print "</table>\n";

		print '</td></tr>';

		if ($flag_price_may_change)
		{
			print '<tr><td colspan="3" align="left">';
			print '<select name="usenewprice" class="flat">';
			print '<option value="0">'.$langs->trans("AlwaysUseFixedPrice").'</option>';
			print '<option value="1" disabled>'.$langs->trans("AlwaysUseNewPrice").'</option>';
			print '</select>';
			print '</td></tr>';
		}
		print "</table>\n";

        dol_fiche_end();

		print '<div align="center"><input type="submit" class="button" value="'.$langs->trans("Create").'">';
        print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	    print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
        print '</div>';
		print "</form>\n";
	}
	else
	{
		dol_print_error('',"Error, no invoice ".$object->id);
	}
}
else
{
	/*
	 * View mode
	 */
	if ($object->id > 0)
	{
		$object->fetch_thirdparty();

		// Confirmation de la suppression d'une ligne produit
		if ($action == 'ask_deleteline') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&lineid=' . $lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 'no', 1);
		}

		// Confirm delete of repeatable invoice
		if ($action == 'ask_deleteinvoice') {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('DeleteRepeatableInvoice'), $langs->trans('ConfirmDeleteRepeatableInvoice'), 'confirm_deleteinvoice', '', 'no', 1);
		}

		print $formconfirm;

		$author = new User($db);
		$author->fetch($object->user_author);

		$head=array();
		$h=0;
		$head[$h][0] = $_SERVER["PHP_SELF"].'?id='.$object->id;
		$head[$h][1] = $langs->trans("CardBill");
		$head[$h][2] = 'card';

		dol_fiche_head($head, 'card', $langs->trans("RepeatableInvoice"),0,'bill');	// Add a div

		// Recurring invoice content

		$linkback = '<a href="' . DOL_URL_ROOT . '/compta/facture/fiche-rec.php' . (! empty($socid) ? '?socid=' . $socid : '') . '">' . $langs->trans("BackToList") . '</a>';

		$morehtmlref='';
		if ($action != 'editref') $morehtmlref.=$form->editfieldkey($object->ref, 'ref', $object->ref, $object, $user->rights->facture->creer, '', '', 0, 2);
		else $morehtmlref.= $form->editfieldval('', 'ref', $object->ref, $object, $user->rights->facture->creer, 'string');

    	$morehtmlref.='<div class="refidno">';
    	// Ref customer
    	//$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->facture->creer, 'string', '', 0, 1);
    	//$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->facture->creer, 'string', '', null, null, '', 1);
    	// Thirdparty
    	$morehtmlref.=$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
    	// Project
    	if (! empty($conf->projet->enabled))
    	{
    	    $langs->load("projects");
    	    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
    	    if ($user->rights->facture->creer)
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

	    dol_banner_tab($object, 'ref', $linkback, 1, 'titre', 'none', $morehtmlref, '', 0, '', $morehtmlright);

    	print '<div class="fichecenter">';
    	print '<div class="fichehalfleft">';
    	print '<div class="underbanner clearboth"></div>';

	    print '<table class="border" width="100%">';

		print '<tr><td class="titlefield">'.$langs->trans("Author").'</td><td colspan="3">'.$author->getFullName($langs)."</td></tr>";

		print '<tr><td>'.$langs->trans("AmountHT").'</td>';
		print '<td colspan="3">'.price($object->total_ht,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("AmountVAT").'</td><td colspan="3">'.price($object->total_tva,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';
		print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="3">'.price($object->total_ttc,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';

		// Payment term
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentConditionsShort');
		print '</td>';
		if ($object->type != Facture::TYPE_CREDIT_NOTE && $action != 'editconditions' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editconditions&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetConditions'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($object->type != Facture::TYPE_CREDIT_NOTE)
		{
			if ($action == 'editconditions')
			{
				$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'cond_reglement_id');
			}
			else
			{
				$form->form_conditions_reglement($_SERVER['PHP_SELF'] . '?facid=' . $object->id, $object->cond_reglement_id, 'none');
			}
		} else {
			print '&nbsp;';
		}
		print '</td></tr>';

		// Payment mode
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('PaymentMode');
		print '</td>';
		if ($action != 'editmode' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmode&amp;facid=' . $object->id . '">' . img_edit($langs->trans('SetMode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editmode')
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'CRDT');
		}
		else
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?facid='.$object->id, $object->mode_reglement_id, 'none', 'CRDT');
		}
		print '</td></tr>';

		// Help of substitution key
		$dateexample=dol_now();
		if (! empty($object->frequency) && ! empty($object->date_when)) $dateexample=$object->date_when;
		$substitutionarray=array(
		    '__TOTAL_HT__' => $langs->trans("AmountHT").' ('.$langs->trans("Example").': '.price($object->total_ht).')',
		    '__TOTAL_TTC__' =>  $langs->trans("AmountTTC").' ('.$langs->trans("Example").': '.price($object->total_ttc).')',
		    '__INVOICE_PREVIOUS_MONTH__' => $langs->trans("PreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'),'%m').')',
		    '__INVOICE_MONTH__' =>  $langs->trans("MonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample,'%m').')',
		    '__INVOICE_NEXT_MONTH__' => $langs->trans("NextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'),'%m').')',
		    '__INVOICE_PREVIOUS_MONTH_TEXT__' => $langs->trans("TextPreviousMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'm'),'%B').')',
		    '__INVOICE_MONTH_TEXT__' =>  $langs->trans("TextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample,'%B').')',
		    '__INVOICE_NEXT_MONTH_TEXT__' => $langs->trans("TextNextMonthOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'm'), '%B').')',
		    '__INVOICE_PREVIOUS_YEAR__' => $langs->trans("YearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, -1, 'y'),'%Y').')',
		    '__INVOICE_YEAR__' =>  $langs->trans("PreviousYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date($dateexample,'%Y').')',
		    '__INVOICE_NEXT_YEAR__' => $langs->trans("NextYearOfInvoice").' ('.$langs->trans("Example").': '.dol_print_date(dol_time_plus_duree($dateexample, 1, 'y'),'%Y').')'
		);

		$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br>';
		foreach($substitutionarray as $key => $val)
		{
		    $htmltext.=$key.' = '.$langs->trans($val).'<br>';
		}
		$htmltext.='</i>';

		// Note public
		print '<tr><td>';
		print $form->editfieldkey($form->textwithpicto($langs->trans('NotePublic'), $htmltext), 'note_public', $object->note_public, $object, $user->rights->facture->creer);
		print '</td><td colspan="5">';
		print $form->editfieldval($langs->trans("NotePublic"), 'note_public', $object->note_public, $object, $user->rights->facture->creer, 'textarea:'.ROWS_4.':60');
		print '</td>';
		print '</tr>';

		// Note private
		print '<tr><td>';
		print $form->editfieldkey($form->textwithpicto($langs->trans("NotePrivate"), $htmltext), 'note_private', $object->note_private, $object, $user->rights->facture->creer);
		print '</td><td colspan="5">';
		print $form->editfieldval($langs->trans("NotePrivate"), 'note_private', $object->note_private, $object, $user->rights->facture->creer, 'textarea:'.ROWS_4.':60');
		print '</td>';
		print '</tr>';

		// Bank Account
		$langs->load('banks');

		print '<tr><td class="nowrap">';
		print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
		print $langs->trans('RIB');
		print '<td>';
		if (($action != 'editbankaccount') && $user->rights->commande->creer && ! empty($object->brouillon))
		    print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';
		if ($action == 'editbankaccount')
		{
		    $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
		}
		else
		{
		    $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
		}
		print "</td>";
		print '</tr>';

    	print '</table>';

    	print '</div>';
    	print '<div class="fichehalfright">';
    	print '<div class="ficheaddleft">';
    	print '<div class="underbanner clearboth"></div>';

    	print '<table class="border centpercent">';

		/*
		 * Recurrence
		 */
		$title = $langs->trans("Recurrence");
		print load_fiche_titre($title, '', 'calendar');

		print '<table class="border" width="100%">';

		// if "frequency" is empty or = 0, the reccurence is disabled
		print '<tr><td style="width: 50%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Frequency');
		print '</td>';
		if ($action != 'editfrequency' && ! empty($object->brouillon) && $user->rights->facture->creer)
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editfrequency&amp;facid=' . $object->id . '">' . img_edit($langs->trans('Edit'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td>';
		if ($action == 'editfrequency')
		{
			print '<form method="post" action="'.$_SERVER["PHP_SELF"] . '?facid=' . $object->id.'">';
			print '<input type="hidden" name="action" value="setfrequency">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
			print '<tr><td>';
			print "<input type='text' name='frequency' value='".$object->frequency."' size='5' />&nbsp;".$form->selectarray('unit_frequency', array('d'=>$langs->trans('Day'), 'm'=>$langs->trans('Month'), 'y'=>$langs->trans('Year')), ($object->unit_frequency?$object->unit_frequency:'m'));
			print '</td>';
			print '<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
			print '</tr></table></form>';
		}
		else
		{
		    	if ($object->frequency > 0)
		    	{
				print $langs->trans('FrequencyPer_'.$object->unit_frequency, $object->frequency);
		    	}
		    	else
		    	{
		        	print $langs->trans("NotARecurringInvoiceTemplate");
			}
		}
		print '</td></tr>';

		// Date when
		print '<tr><td>';
		if ($action == 'date_when' || $object->frequency > 0)
		{
		    print $form->editfieldkey($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->rights->facture->creer, 'day');
		}
		else
		{
		    print $langs->trans("NextDateToExecution");
		}
		print '</td><td>';
		if ($action == 'date_when' || $object->frequency > 0)
		{
		    print $form->editfieldval($langs->trans("NextDateToExecution"), 'date_when', $object->date_when, $object, $user->rights->facture->creer, 'day');
		}
		print '</td>';
		print '</tr>';

		// Max period / Rest period
		print '<tr><td>';
		if ($action == 'nb_gen_max' || $object->frequency > 0)
		{
		    print $form->editfieldkey($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max, $object, $user->rights->facture->creer);
		}
		else
		{
		    print $langs->trans("MaxPeriodNumber");
		}
		print '</td><td>';
		if ($action == 'nb_gen_max' || $object->frequency > 0)
		{
		      print $form->editfieldval($langs->trans("MaxPeriodNumber"), 'nb_gen_max', $object->nb_gen_max?$object->nb_gen_max:'', $object, $user->rights->facture->creer);
		}
		else
		{
		    print '';
		}
		print '</td>';
		print '</tr>';

		// Status of generated invoices
		print '<tr><td>';
		if ($action == 'auto_validate' || $object->frequency > 0)
		    print $form->editfieldkey($langs->trans("StatusOfGeneratedInvoices"), 'auto_validate', $object->auto_validate, $object, $user->rights->facture->creer);
		else
		    print $langs->trans("StatusOfGeneratedInvoices");
		print '</td><td>';
    		$select = 'select;0:'.$langs->trans('BillStatusDraft').',1:'.$langs->trans('BillStatusValidated');
		if ($action == 'auto_validate' || $object->frequency > 0)
		{
    		print $form->editfieldval($langs->trans("StatusOfGeneratedInvoices"), 'auto_validate', $object->auto_validate, $object, $user->rights->facture->creer, $select);
		}
		print '</td>';
		print '</tr>';

		print '</table>';

    	// Frequencry/Recurring section
    	if ($object->frequency > 0)
    	{
    	    print '<br>';

    	    if (empty($conf->cron->enabled))
    		{
    			print info_admin($langs->trans("EnableAndSetupModuleCron", $langs->transnoentitiesnoconv("Module2300Name")));
    		}

            print '<div class="underbanner clearboth"></div>';
            print '<table class="border centpercent">';

    		// Nb of generation already done
    		print '<tr><td style="width: 50%">'.$langs->trans("NbOfGenerationDone").'</td>';
    		print '<td>';
    		print $object->nb_gen_done?$object->nb_gen_done:'0';
    		print '</td>';
    		print '</tr>';

    		// Date last
    		print '<tr><td>';
    		print $langs->trans("DateLastGeneration");
    		print '</td><td>';
    		print dol_print_date($object->date_last_gen, 'dayhour');
    		print '</td>';
    		print '</tr>';

    		print '</table>';

    		print '<br>';
		}

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div><br>';


		// Lines
		print '	<form name="addproduct" id="addproduct" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . (($action != 'editline') ? '#add' : '#line_' . GETPOST('lineid')) . '" method="POST">
        	<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
        	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateligne') . '">
        	<input type="hidden" name="mode" value="">
        	<input type="hidden" name="id" value="' . $object->id . '">
        	';

		if (! empty($conf->use_javascript_ajax) && $object->statut == 0) {
		    include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

		print '<table id="tablelines" class="noborder noshadow" width="100%">';
		// Show object lines
		if (! empty($object->lines))
		{
		    //$disableedit=1;
		    //$disablemove=1;
		    $ret = $object->printObjectLines($action, $mysoc, $soc, $lineid, 0);      // No date selector for template invoice
		}

		// Form to add new line
		if ($object->statut == 0 && $user->rights->facture->creer && $action != 'valid' && $action != 'editline')
		{
		    if ($action != 'editline')
		    {
    		    $var = true;

    		    // Add free products/services
    		    $object->formAddObjectLine(0, $mysoc, $soc);                          // No date selector for template invoice

    		    $parameters = array();
    		    $reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		    }
		}

		print "</table>\n";

		print "</form>\n";

		dol_fiche_end();


		/**
		 * Barre d'actions
		 */
		print '<div class="tabsAction">';

		//if ($object->statut == Facture::STATUS_DRAFT)   // there is no draft status on templates.
		//{
		if ($user->rights->facture->creer)
		{
				if (! empty($object->frequency) && $object->nb_gen_max > 0 && ($object->nb_gen_done >= $object->nb_gen_max))
				{
					print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("MaxGenerationReached")).'">'.$langs->trans("CreateBill").'</a></div>';
				}
				else
				{
					if (empty($object->frequency) || $object->date_when <= $today)
					{
						print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;socid='.$object->thirdparty->id.'&amp;fac_rec='.$object->id.'">'.$langs->trans("CreateBill").'</a></div>';
					}
					else
					{
						print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("DateIsNotEnough")).'">'.$langs->trans("CreateBill").'</a></div>';
        		    		}
		        	}
			}
			else
			{
				print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans("CreateBill").'</a></div>';
    			}
		//}

		//if ($object->statut == Facture::STATUS_DRAFT && $user->rights->facture->supprimer)
		if ($user->rights->facture->supprimer)
		{
			print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=ask_deleteinvoice&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
		}

		print '</div>';



		print '<div class="fichecenter"><div class="fichehalfleft">';
		print '<a name="builddoc"></a>'; // ancre


		// Show links to link elements
        $linktoelem = $form->showLinkToObjectBlock($object, null, array('invoice'));

		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);


        print '</div></div>';

	}
	else
	{
		/*
		 *  List mode
		 */
		$sql = "SELECT s.nom as name, s.rowid as socid, f.rowid as facid, f.titre, f.total, f.tva as total_vat, f.total_ttc, f.frequency,";
		$sql.= " f.date_last_gen, f.date_when";
		$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,".MAIN_DB_PREFIX."facture_rec as f";
		if (! $user->rights->societe->client->voir && ! $socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}
		$sql.= " WHERE f.fk_soc = s.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		if (! $user->rights->societe->client->voir && ! $socid) {
			$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".$user->id;
		}
		if ($search_ref) $sql .= natural_search('f.titre', $search_ref);
		if ($search_societe) $sql .= natural_search('s.nom', $search_societe);
		if ($search_frequency) $sql .= natural_search('f.frequency', $search_frequency);
		if ($search_montant_ht != '') $sql.= natural_search('f.total', $search_montant_ht, 1);
		if ($search_montant_vat != '') $sql.= natural_search('f.tva', $search_montant_vat, 1);
		if ($search_montant_ttc != '') $sql.= natural_search('f.total_ttc', $search_montant_ttc, 1);
		if ($search_frequency == '1') $sql.= ' AND f.frequency > 0';
		if ($search_frequency == '0') $sql.= ' AND (f.frequency IS NULL or f.frequency = 0)';

		if ($month > 0)
		{
		    if ($year > 0 && empty($day))
		        $sql.= " AND f.date_last_gen BETWEEN '".$db->idate(dol_get_first_day($year,$month,false))."' AND '".$db->idate(dol_get_last_day($year,$month,false))."'";
		        else if ($year > 0 && ! empty($day))
		            $sql.= " AND f.date_last_gen BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month, $day, $year))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month, $day, $year))."'";
		            else
		                $sql.= " AND date_format(f.date_last_gen, '%m') = '".$month."'";
		}
		else if ($year > 0)
		{
		    $sql.= " AND f.date_last_gen BETWEEN '".$db->idate(dol_get_first_day($year,1,false))."' AND '".$db->idate(dol_get_last_day($year,12,false))."'";
		}
		if ($month_date_when > 0)
		{
		    if ($year_date_when > 0 && empty($day_date_when))
		        $sql.= " AND f.date_when BETWEEN '".$db->idate(dol_get_first_day($year_date_when,$month_date_when,false))."' AND '".$db->idate(dol_get_last_day($year_date_when,$month_date_when,false))."'";
		        else if ($year_date_when > 0 && ! empty($day_date_when))
		            $sql.= " AND f.date_date_when_reglement BETWEEN '".$db->idate(dol_mktime(0, 0, 0, $month_date_when, $day_date_when, $year_date_when))."' AND '".$db->idate(dol_mktime(23, 59, 59, $month_date_when, $day_date_when, $year_date_when))."'";
			else
			$sql.= " AND date_format(f.date_when, '%m') = '".$month_date_when."'";
		}
		else if ($year_date_when > 0)
		{
		    $sql.= " AND f.date_when BETWEEN '".$db->idate(dol_get_first_day($year_date_when,1,false))."' AND '".$db->idate(dol_get_last_day($year_date_when,12,false))."'";
		}

		$nbtotalofrecords = '';
        	if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        	{
        		$result = $db->query($sql);
        		$nbtotalofrecords = $db->num_rows($result);
        	}

        	$sql.= $db->order($sortfield, $sortorder);
        	$sql.= $db->plimit($limit+1,$offset);

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);

			$param='&socid='.$socid;
            		if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
			if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;
			if ($day)                $param.='&day='.$day;
			if ($month)              $param.='&month='.$month;
			if ($year)               $param.='&year=' .$year;
			if ($day_date_when)      $param.='&day_date_when='.$day_date_when;
			if ($month_date_when)    $param.='&month_date_when='.$month_date_when;
			if ($year_date_when)     $param.='&year_date_when=' .$year_date_when;
			if ($search_ref)         $param.='&search_ref=' .$search_ref;
			if ($search_societe)     $param.='&search_societe=' .$search_societe;
			if ($search_montant_ht != '')  $param.='&search_montant_ht='.$search_montant_ht;
			if ($search_montant_vat != '')  $param.='&search_montant_vat='.$search_montant_vat;
			if ($search_montant_ttc != '') $param.='&search_montant_ttc='.$search_montant_ttc;
			if ($search_frequency)         $param.='&search_frequency=' .$search_frequency;
			if ($option)             $param.="&option=".$option;
			if ($optioncss != '')    $param.='&optioncss='.$optioncss;
			// Add $param from extra fields
			foreach ($search_array_options as $key => $val)
			{
			    $crit=$val;
			    $tmpkey=preg_replace('/search_options_/','',$key);
			    if ($val != '') $param.='&search_options_'.$tmpkey.'='.urlencode($val);
			}

			$massactionbutton=$form->selectMassAction('', $massaction == 'presend' ? array() : array('presend'=>$langs->trans("SendByMail"), 'builddoc'=>$langs->trans("PDFMerge")));

            print '<form method="POST" name="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
            if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
        	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        	print '<input type="hidden" name="action" value="list">';
        	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
        	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
        	print '<input type="hidden" name="viewstatut" value="'.$viewstatut.'">';

	        print_barre_liste($langs->trans("RepeatableInvoices"),$page,$_SERVER['PHP_SELF'],$param,$sortfield,$sortorder,'',$num,$nbtotalofrecords,'title_accountancy.png',0,'','',$limit);

			print $langs->trans("ToCreateAPredefinedInvoice", $langs->transnoentitiesnoconv("ChangeIntoRepeatableInvoice")).'<br><br>';

			$i = 0;

			print '<div class="div-table-responsive">';
            print '<table class="tagtable liste'.($moreforfilter?" listwithfilterbefore":"").'">'."\n";

			print '<tr class="liste_titre">';
			print_liste_field_titre($langs->trans("Ref"),$_SERVER['PHP_SELF'],"f.titre","",$param,"",$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("ThirdParty"),$_SERVER['PHP_SELF'],"s.nom","",$param,"",$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("AmountHT"),$_SERVER['PHP_SELF'],"f.total","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("AmountVAT"),$_SERVER['PHP_SELF'],"f.tva","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("AmountTTC"),$_SERVER['PHP_SELF'],"f.total_ttc","",$param,'align="right"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("RecurringInvoiceTemplate"),$_SERVER['PHP_SELF'],"f.frequency","",$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("DateLastGeneration"),$_SERVER['PHP_SELF'],"f.date_last_gen","",$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre($langs->trans("NextDateToExecution"),$_SERVER['PHP_SELF'],"f.date_when","",$param,'align="center"',$sortfield,$sortorder);
			print_liste_field_titre('');		// Field may contains ling text
			print "</tr>\n";


			// Filters lines
			print '<tr class="liste_titre">';
			// Ref
			if (! empty($arrayfields['f.titre']['checked']))
			{
			    print '<td class="liste_titre" align="left">';
			    print '<input class="flat" size="6" type="text" name="search_ref" value="'.$search_ref.'">';
			    print '</td>';
			}
			// Thirpdarty
			if (! empty($arrayfields['s.nom']['checked']))
			{
			    print '<td class="liste_titre" align="left"><input class="flat" type="text" size="8" name="search_societe" value="'.$search_societe.'"></td>';
			}
			if (! empty($arrayfields['f.total']['checked']))
			{
			    // Amount
			    print '<td class="liste_titre" align="right">';
			    print '<input class="flat" type="text" size="5" name="search_montant_ht" value="'.$search_montant_ht.'">';
			    print '</td>';
			}
			if (! empty($arrayfields['f.tva']['checked']))
			{
			    // Amount
			    print '<td class="liste_titre" align="right">';
			    print '<input class="flat" type="text" size="5" name="search_montant_vat" value="'.$search_montant_vat.'">';
			    print '</td>';
			}
			if (! empty($arrayfields['f.total_ttc']['checked']))
			{
			    // Amount
			    print '<td class="liste_titre" align="right">';
			    print '<input class="flat" type="text" size="5" name="search_montant_ttc" value="'.$search_montant_ttc.'">';
			    print '</td>';
			}
			if (! empty($arrayfields['f.frequency']['checked']))
			{
			    // Amount
			    print '<td class="liste_titre" align="center">';
			    print $form->selectyesno('search_frequency', $search_frequency, 1, false, 1);
			    print '</td>';
			}
			// Date invoice
			if (! empty($arrayfields['f.date_last_gen']['checked']))
			{
			    print '<td class="liste_titre" align="center">';
			    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day" value="'.$day.'">';
			    print '<input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
			    $formother->select_year($year?$year:-1,'year',1, 20, 5);
			    print '</td>';
			}
			// Date due
			if (! empty($arrayfields['f.date_when']['checked']))
			{
			    print '<td class="liste_titre" align="center">';
			    if (! empty($conf->global->MAIN_LIST_FILTER_ON_DAY)) print '<input class="flat" type="text" size="1" maxlength="2" name="day_date_when" value="'.$day_date_when.'">';
			    print '<input class="flat" type="text" size="1" maxlength="2" name="month_date_when" value="'.$month_date_when.'">';
			    $formother->select_year($year_date_when?$year_date_when:-1,'year_date_when',1, 20, 5);
			    print '</td>';
			}
			// Extra fields
			if (is_array($extrafields->attribute_label) && count($extrafields->attribute_label))
			{
			    foreach($extrafields->attribute_label as $key => $val)
			    {
			        if (! empty($arrayfields["ef.".$key]['checked']))
			        {
			            $align=$extrafields->getAlignFlag($key);
			            $typeofextrafield=$extrafields->attribute_type[$key];
			            print '<td class="liste_titre'.($align?' '.$align:'').'">';
			            if (in_array($typeofextrafield, array('varchar', 'int', 'double', 'select')))
			            {
			                $crit=$val;
			                $tmpkey=preg_replace('/search_options_/','',$key);
			                $searchclass='';
			                if (in_array($typeofextrafield, array('varchar', 'select'))) $searchclass='searchstring';
			                if (in_array($typeofextrafield, array('int', 'double'))) $searchclass='searchnum';
			                print '<input class="flat'.($searchclass?' '.$searchclass:'').'" size="4" type="text" name="search_options_'.$tmpkey.'" value="'.dol_escape_htmltag($search_array_options['search_options_'.$tmpkey]).'">';
			            }
			            print '</td>';
			        }
			    }
			}
			// Fields from hook
			$parameters=array('arrayfields'=>$arrayfields);
			$reshook=$hookmanager->executeHooks('printFieldListOption',$parameters);    // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			// Date creation
			if (! empty($arrayfields['f.datec']['checked']))
			{
			    print '<td class="liste_titre">';
			    print '</td>';
			}
			// Date modification
			if (! empty($arrayfields['f.tms']['checked']))
			{
			    print '<td class="liste_titre">';
			    print '</td>';
			}
			// Action column
			print '<td class="liste_titre" align="middle">';
			$searchpitco=$form->showFilterAndCheckAddButtons(0, 'checkforselect', 1);
			print $searchpitco;
			print '</td>';
			print "</tr>\n";


			if ($num > 0)
			{
				$var=true;
				while ($i < min($num,$limit))
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					print "<tr ".$bc[$var].">";

					print '<td><a href="'.$_SERVER['PHP_SELF'].'?id='.$objp->facid.'">'.img_object($langs->trans("ShowBill"),"bill").' '.$objp->titre;
					print "</a></td>\n";

					$companystatic->id=$objp->socid;
					$companystatic->name=$objp->name;
					print '<td>'.$companystatic->getNomUrl(1,'customer').'</td>';

					print '<td align="right">'.price($objp->total).'</td>'."\n";
					print '<td align="right">'.price($objp->total_vat).'</td>'."\n";
					print '<td align="right">'.price($objp->total_ttc).'</td>'."\n";
					print '<td align="center">'.yn($objp->frequency?1:0).'</td>';
					print '<td align="center">'.($objp->frequency ? dol_print_date($objp->date_last_gen,'day') : '').'</td>';
					print '<td align="center">'.($objp->frequency ? dol_print_date($objp->date_when,'day') : '').'</td>';

					print '<td align="center">';
					if ($user->rights->facture->creer)
					{
				        if (empty($objp->frequency) || $db->jdate($objp->date_when) <= $today)
				        {
                            print '<a href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;socid='.$objp->socid.'&amp;fac_rec='.$objp->facid.'">';
                            print $langs->trans("CreateBill").'</a>';
				        }
				        else
				        {
				            print $langs->trans("DateIsNotEnough");
				        }
					}
					else
					{
					    print "&nbsp;";
					}
					print "</td>";
					print "</tr>\n";
					$i++;
				}
			}
			else print '<tr '.$bc[false].'><td colspan="9" class="opacitymedium">'.$langs->trans("NoneF").'</td></tr>';

			print "</table>";
			print "</div>";
			print "</form>";

			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
		}
	}

}

llxFooter();

$db->close();
