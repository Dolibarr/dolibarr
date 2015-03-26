<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014      Ion Agorria          <ion@agorria.com>
 *
 * This	program	is free	software; you can redistribute it and/or modify
 * it under	the	terms of the GNU General Public	License	as published by
 * the Free	Software Foundation; either	version	2 of the License, or
 * (at your	option)	any	later version.
 *
 * This	program	is distributed in the hope that	it will	be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A	PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file		htdocs/fourn/commande/card.php
 *	\ingroup	supplier, order
 *	\brief		Card supplier order
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formorder.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_order/modules_commandefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (! empty($conf->askpricesupplier->enabled))
	require DOL_DOCUMENT_ROOT . '/comm/askpricesupplier/class/askpricesupplier.class.php';
if (!empty($conf->produit->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (!empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once NUSOAP_PATH.'/nusoap.php';     // Include SOAP

$langs->load('admin');
$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('askpricesupplier');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');

$id 			= GETPOST('id','int');
$ref 			= GETPOST('ref','alpha');
$action 		= GETPOST('action','alpha');
$confirm		= GETPOST('confirm','alpha');
$comclientid 	= GETPOST('comid','int');
$socid			= GETPOST('socid','int');
$projectid		= GETPOST('projectid','int');
$cancel         = GETPOST('cancel','alpha');
$lineid         = GETPOST('lineid', 'int');

$lineid = GETPOST('lineid', 'int');
$origin = GETPOST('origin', 'alpha');
$originid = (GETPOST('originid', 'int') ? GETPOST('originid', 'int') : GETPOST('origin_id', 'int')); // For backward compatibility

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

$datelivraison=dol_mktime(GETPOST('liv_hour','int'), GETPOST('liv_min','int'), GETPOST('liv_sec','int'), GETPOST('liv_month','int'), GETPOST('liv_day','int'),GETPOST('liv_year','int'));


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('ordersuppliercard','globalcard'));

$object = new CommandeFournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

//Date prefix
$date_pf = '';

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret = $object->fetch($id, $ref);
	if ($ret < 0) dol_print_error($db,$object->error);
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) dol_print_error($db,$object->error);
}
else if (! empty($socid) && $socid > 0)
{
	$fourn = new Fournisseur($db);
	$ret=$fourn->fetch($socid);
	if ($ret < 0) dol_print_error($db,$object->error);
	$object->socid = $fourn->id;
	$ret = $object->fetch_thirdparty();
	if ($ret < 0) dol_print_error($db,$object->error);
}

$permissionnote=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_setnotes.inc.php
$permissiontoedit=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_lineupdown.inc.php


/*
 * Actions
 */

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	if ($cancel) $action='';

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	if ($action == 'setref_supplier' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// Set incoterm
	if ($action == 'set_incoterms' && $user->rights->fournisseur->commande->creer)
	{
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// conditions de reglement
	if ($action == 'setconditions' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// mode de reglement
	if ($action == 'setmode' && $user->rights->fournisseur->commande->creer)
	{
	    $result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// bank account
	if ($action == 'setbankaccount' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setBankAccount(GETPOST('fk_account', 'int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// date de livraison
	if ($action == 'setdate_livraison' && $user->rights->fournisseur->commande->creer)
	{
		$result=$object->set_date_livraison($user,$datelivraison);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// Set project
	if ($action ==	'classin' && $user->rights->fournisseur->commande->creer)
	{
	    $object->setProject($projectid);
	}

	if ($action == 'setremisepercent' && $user->rights->fournisseur->commande->creer)
	{
	    $result = $object->set_remise($user, $_POST['remise_percent']);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($action == 'reopen' && $user->rights->fournisseur->commande->approuver)
	{
	    if (in_array($object->statut, array(1, 2, 5, 6, 7, 9)))
	    {
	        if ($object->statut == 1) $newstatus=0;	// Validated->Draft
	        else if ($object->statut == 2) $newstatus=0;	// Approved->Draft
	        else if ($object->statut == 5) $newstatus=4;	// Received->Received partially
	        else if ($object->statut == 6) $newstatus=2;	// Canceled->Approved
	        else if ($object->statut == 7) $newstatus=3;	// Canceled->Process running
	        else if ($object->statut == 9) $newstatus=1;	// Refused->Validated

	        $result = $object->setStatus($user, $newstatus);
	        if ($result > 0)
	        {
	            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	            exit;
	        }
	        else
	        {
	        	setEventMessage($object->error, 'errors');
	        }
	    }
	}

	/*
	 *	Add a line into product
	 */
	if ($action == 'addline' && $user->rights->fournisseur->commande->creer)
	{
	    $langs->load('errors');
	    $error = 0;

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		$date_start=dol_mktime(GETPOST('date_start'.$date_pf.'hour'), GETPOST('date_start'.$date_pf.'min'), 0, GETPOST('date_start'.$date_pf.'month'), GETPOST('date_start'.$date_pf.'day'), GETPOST('date_start'.$date_pf.'year'));
		$date_end=dol_mktime(GETPOST('date_end'.$date_pf.'hour'), GETPOST('date_end'.$date_pf.'min'), 0, GETPOST('date_end'.$date_pf.'month'), GETPOST('date_end'.$date_pf.'day'), GETPOST('date_end'.$date_pf.'year'));
		if (GETPOST('prod_entry_mode') == 'free')
		{
			$idprod=0;
			$price_ht = GETPOST('price_ht');
			$tva_tx = (GETPOST('tva_tx') ? GETPOST('tva_tx') : 0);
		}
		else
		{
			$idprod=GETPOST('idprod', 'int');
			$price_ht = '';
			$tva_tx = '';
		}

		$qty = GETPOST('qty'.$predef);
		$remise_percent=GETPOST('remise_percent'.$predef);

	    // Extrafields
	    $extrafieldsline = new ExtraFields($db);
	    $extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	    $array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline, $predef);
	    // Unset extrafield
	    if (is_array($extralabelsline)) {
	    	// Get extra fields
	    	foreach ($extralabelsline as $key => $value) {
	    		unset($_POST["options_" . $key]);
	    	}
	    }

	    if (GETPOST('prod_entry_mode')=='free' && GETPOST('price_ht') < 0 && $qty < 0)
	    {
	        setEventMessage($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPrice'), $langs->transnoentitiesnoconv('Qty')), 'errors');
	        $error++;
	    }
	    if (GETPOST('prod_entry_mode')=='free'  && ! GETPOST('idprodfournprice') && GETPOST('type') < 0)
	    {
	        setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), 'errors');
	        $error++;
	    }
	    if (GETPOST('prod_entry_mode')=='free' && GETPOST('price_ht')==='' && GETPOST('price_ttc')==='') // Unit price can be 0 but not ''
	    {
	        setEventMessage($langs->trans($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UnitPrice'))), 'errors');
	        $error++;
	    }
	    if (GETPOST('prod_entry_mode')=='free' && ! GETPOST('dp_desc'))
	    {
	        setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), 'errors');
	        $error++;
	    }
	    if (! GETPOST('qty'))
	    {
	        setEventMessage($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), 'errors');
	        $error++;
	    }

	    // Ecrase $pu par celui	du produit
	    // Ecrase $desc	par	celui du produit
	    // Ecrase $txtva  par celui du produit
	    if ((GETPOST('prod_entry_mode') != 'free') && empty($error))	// With combolist mode idprodfournprice is > 0 or -1. With autocomplete, idprodfournprice is > 0 or ''
	    {
	    	$idprod=0;
	    	$productsupplier = new ProductFournisseur($db);

    		if (GETPOST('idprodfournprice') == -1 || GETPOST('idprodfournprice') == '') $idprod=-99;	// Same behaviour than with combolist. When not select idprodfournprice is now -99 (to avoid conflict with next action that may return -1, -2, ...)

	    	if (GETPOST('idprodfournprice') > 0)
	    	{
	    		$idprod=$productsupplier->get_buyprice(GETPOST('idprodfournprice'), $qty);    // Just to see if a price exists for the quantity. Not used to found vat.
	    	}

	    	if ($idprod > 0)
	    	{
	    		$res=$productsupplier->fetch($idprod);

	    		$label = $productsupplier->libelle;

	    		$desc = $productsupplier->description;
	    		if (trim($product_desc) != trim($desc)) $desc = dol_concatdesc($desc, $product_desc);

	    		$tva_tx	= get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice'));
	    		$type = $productsupplier->type;

	    		// Local Taxes
	    		$localtax1_tx= get_localtax($tva_tx, 1,$mysoc,$object->thirdparty);
	    		$localtax2_tx= get_localtax($tva_tx, 2,$mysoc,$object->thirdparty);

	    		$result=$object->addline(
	    			$desc,
	    			$productsupplier->fourn_pu,
	    			$qty,
	    			$tva_tx,
	    			$localtax1_tx,
	    			$localtax2_tx,
	    			$productsupplier->id,
	    			GETPOST('idprodfournprice'),
	    			$productsupplier->fourn_ref,
	    			$remise_percent,
	    			'HT',
	    			$pu_ttc,
	    			$type,
	    			'',
	    			'',
	    			$date_start,
	    			$date_end,
	    			$array_options
	    		);
	    	}
	    	if ($idprod == -99 || $idprod == 0)
	    	{
    			// Product not selected
    			$error++;
    			$langs->load("errors");
    			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ProductOrService")).' '.$langs->trans("or").' '.$langs->trans("NoPriceDefinedForThisSupplier"), 'errors');
	    	}
	    	if ($idprod == -1)
	    	{
	    		// Quantity too low
	    		$error++;
	    		$langs->load("errors");
	    		setEventMessage($langs->trans("ErrorQtyTooLowForThisSupplier"), 'errors');
	    	}
	    }
	    else if((GETPOST('price_ht')!=='' || GETPOST('price_ttc')!=='') && empty($error))
		{
			$pu_ht = price2num($price_ht, 'MU');
			$pu_ttc = price2num(GETPOST('price_ttc'), 'MU');
			$tva_npr = (preg_match('/\*/', $tva_tx) ? 1 : 0);
			$tva_tx = str_replace('*', '', $tva_tx);
			$label = (GETPOST('product_label') ? GETPOST('product_label') : '');
			$desc = $product_desc;
			$type = GETPOST('type');

	    	$tva_tx = price2num($tva_tx);	// When vat is text input field

	    	// Local Taxes
	    	$localtax1_tx= get_localtax($tva_tx, 1,$mysoc,$object->thirdparty);
	    	$localtax2_tx= get_localtax($tva_tx, 2,$mysoc,$object->thirdparty);

	    	if (GETPOST('price_ht')!=='')
	    	{
	    		$price_base_type = 'HT';
	    		$ht = price2num(GETPOST('price_ht'));
	    		$result=$object->addline($desc, $ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', $remise_percent, $price_base_type, 0, $type,'','', $date_start, $date_end, $array_options);
	    	}
	    	else
	    	{
	    		$ttc = price2num(GETPOST('price_ttc'));
	    		$ht = $ttc / (1 + ($tva_tx / 100));
	    		$price_base_type = 'HT';
	    		$result=$object->addline($desc, $ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', $remise_percent, $price_base_type, $ttc, $type,'','', $date_start, $date_end, $array_options);
	    	}
		}

	    //print "xx".$tva_tx; exit;
	    if (! $error && $result > 0)
	    {
	    	$ret=$object->fetch($object->id);    // Reload to get new records

	        // Define output language
	    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	    	{
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

	    		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
	    		if ($result < 0) dol_print_error($db,$result);
	    	}

			unset($_POST ['prod_entry_mode']);

	    	unset($_POST['qty']);
	    	unset($_POST['type']);
	    	unset($_POST['remise_percent']);
	    	unset($_POST['pu']);
	    	unset($_POST['price_ht']);
	    	unset($_POST['price_ttc']);
	    	unset($_POST['tva_tx']);
	    	unset($_POST['label']);
	    	unset($localtax1_tx);
	    	unset($localtax2_tx);
			unset($_POST['np_marginRate']);
			unset($_POST['np_markRate']);
	    	unset($_POST['dp_desc']);
			unset($_POST['idprodfournprice']);

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
	    }
	    else
		{
	    	setEventMessage($object->error, 'errors');
	    }
	}

	/*
	 *	Mise a jour	d'une ligne	dans la	commande
	 */
	if ($action == 'updateline' && $user->rights->fournisseur->commande->creer &&	! GETPOST('cancel'))
	{
		$tva_tx = GETPOST('tva_tx');

		if (GETPOST('price_ht') != '')
    	{
    		$price_base_type = 'HT';
    		$ht = price2num(GETPOST('price_ht'));
    	}
    	else
    	{
    		$ttc = price2num(GETPOST('price_ttc'));
    		$ht = $ttc / (1 + ($tva_tx / 100));
    		$price_base_type = 'HT';
    	}

   		if ($lineid)
	    {
	        $line = new CommandeFournisseurLigne($db);
	        $res = $line->fetch($lineid);
	        if (!$res) dol_print_error($db);
	    }

	    $date_start=dol_mktime(GETPOST('date_start'.$date_pf.'hour'), GETPOST('date_start'.$date_pf.'min'), 0, GETPOST('date_start'.$date_pf.'month'), GETPOST('date_start'.$date_pf.'day'), GETPOST('date_start'.$date_pf.'year'));
	    $date_end=dol_mktime(GETPOST('date_end'.$date_pf.'hour'), GETPOST('date_end'.$date_pf.'min'), 0, GETPOST('date_end'.$date_pf.'month'), GETPOST('date_end'.$date_pf.'day'), GETPOST('date_end'.$date_pf.'year'));

	    $localtax1_tx=get_localtax($tva_tx,1,$mysoc,$object->thirdparty);
	    $localtax2_tx=get_localtax($tva_tx,2,$mysoc,$object->thirdparty);

		// Extrafields Lines
		$extrafieldsline = new ExtraFields($db);
		$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
		$array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
		// Unset extrafield POST Data
		if (is_array($extralabelsline)) {
			foreach ($extralabelsline as $key => $value) {
				unset($_POST["options_" . $key]);
			}
		}

	    $result	= $object->updateline(
	        $lineid,
	        $_POST['product_desc'],
	        $ht,
	        $_POST['qty'],
	        $_POST['remise_percent'],
	        $tva_tx,
	        $localtax1_tx,
	        $localtax2_tx,
	        $price_base_type,
	        0,
	        isset($_POST["type"])?$_POST["type"]:$line->product_type,
	        false,
	        $date_start,
	        $date_end,
	    	$array_options
	    );
	    unset($_POST['qty']);
	    unset($_POST['type']);
	    unset($_POST['idprodfournprice']);
	    unset($_POST['remmise_percent']);
	    unset($_POST['dp_desc']);
	    unset($_POST['np_desc']);
	    unset($_POST['pu']);
	    unset($_POST['tva_tx']);
	    unset($_POST['date_start']);
	    unset($_POST['date_end']);
	    unset($localtax1_tx);
	    unset($localtax2_tx);

	    if ($result	>= 0)
	    {
	        // Define output language
	    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	    	{
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

	    		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
	    		if ($result < 0) dol_print_error($db,$result);
	    	}
	    }
	    else
	    {
	        dol_print_error($db,$object->error);
	        exit;
	    }
	}

	// Remove a product line
	if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer)
	{
		$result = $object->deleteline($lineid);
		if ($result > 0)
		{
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
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
			exit;
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			/* Fix bug 1485 : Reset action to avoid asking again confirmation on failure */
			$action='';
		}
	}

	if ($action == 'confirm_valid' && $confirm == 'yes' &&
	    ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->commande->creer))
	    || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_order_advance->validate)))
		)
	{
	    $object->date_commande=dol_now();
	    $result = $object->valid($user);
	    if ($result	>= 0)
	    {
	        // Define output language
	    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	    	{
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

	    		$result=$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
	    		if ($result < 0) dol_print_error($db,$result);
	    	}
	    }
	    else
		{
	        setEventMessages($object->error, $object->errors, 'errors');
	    }

	    // If we have permission, and if we don't need to provide the idwarehouse, we go directly on approved step
	    if ($user->rights->fournisseur->commande->approuver && ! (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $object->hasProductsOrServices(1)))
	    {
	        $action='confirm_approve';
	    }
	}

	if ($action == 'confirm_approve' && $confirm == 'yes' && $user->rights->fournisseur->commande->approuver)
	{
	    $idwarehouse=GETPOST('idwarehouse', 'int');

	    $qualified_for_stock_change=0;
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(2);
		}
		else
		{
		   	$qualified_for_stock_change=$object->hasProductsOrServices(1);
		}

	    // Check parameters
	    if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $qualified_for_stock_change)
	    {
	        if (! $idwarehouse || $idwarehouse == -1)
	        {
	            $error++;
	            setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")), 'errors');
	            $action='';
	        }
	    }

	    if (! $error)
	    {
	        $result	= $object->approve($user, $idwarehouse);
	        if ($result > 0)
	        {
	            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
	                $outputlangs = $langs;
	                $newlang = '';
	                if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
	                if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
	                if (! empty($newlang)) {
	                    $outputlangs = new Translate("", $conf);
	                    $outputlangs->setDefaultLang($newlang);
	                }
		            $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	            }
	            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
	            exit;
	        }
	        else
	        {
	            setEventMessage($object->error, 'errors');
	        }
	    }
	}

	if ($action == 'confirm_refuse' &&	$confirm == 'yes' && $user->rights->fournisseur->commande->approuver)
	{
	    $result = $object->refuse($user);
	    if ($result > 0)
	    {
	        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
	        exit;
	    }
	    else
	    {
	        setEventMessage($object->error, 'errors');
	    }
	}

	if ($action == 'confirm_commande' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander)
	{
	    $result	= $object->commande($user, $_REQUEST["datecommande"],	$_REQUEST["methode"], $_REQUEST['comment']);
	    if ($result > 0)
	    {
	        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
		        $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	        }
	        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
	        exit;
	    }
	    else
	    {
	        setEventMessage($object->error, 'errors');
	    }
	}


	if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->commande->supprimer)
	{
	    $result=$object->delete($user);
	    if ($result > 0)
	    {
	        header("Location: ".DOL_URL_ROOT.'/fourn/commande/list.php');
	        exit;
	    }
	    else
	    {
	        setEventMessage($object->error, 'errors');
	    }
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer)
	{
		if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
		{
			setEventMessage($langs->trans("NoCloneOptionsSpecified"), 'errors');
		}
		else
		{
			if ($object->id > 0)
			{
				$result=$object->createFromClone();
				if ($result > 0)
				{
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit;
				}
				else
				{
					setEventMessage($object->error, 'errors');
					$action='';
				}
			}
		}
	}

	// Set status of reception (complete, partial, ...)
	if ($action == 'livraison' && $user->rights->fournisseur->commande->receptionner)
	{
	    if (GETPOST("type") != '')
	    {
	        $date_liv = dol_mktime(GETPOST('rehour'),GETPOST('remin'),GETPOST('resec'),GETPOST("remonth"),GETPOST("reday"),GETPOST("reyear"));

	        $result	= $object->Livraison($user, $date_liv, GETPOST("type"), GETPOST("comment"));
	        if ($result > 0)
	        {
	            header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
	            exit;
	        }
	        else if($result == -3)
	        {
	        	setEventMessage($langs->trans("NotAuthorized"), 'errors');
	        }
	        else
	        {
	            setEventMessages($object->error, $object->errors, 'errors');
	        }
	    }
	    else
	    {
		    setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("Delivery")), 'errors');
	    }
	}

	if ($action == 'confirm_cancel' && $confirm == 'yes' &&	$user->rights->fournisseur->commande->commander)
	{
	    $result	= $object->cancel($user);
	    if ($result > 0)
	    {
	        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
	        exit;
	    }
	    else
	    {
	        setEventMessage($object->error, 'errors');
	    }
	}

	if ($action == 'builddoc' && $user->rights->fournisseur->commande->creer)	// En get ou en	post
	{
	    // Build document

		// Save last template used to generate document
		if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	    $outputlangs = $langs;
	    if (GETPOST('lang_id'))
	    {
	        $outputlangs = new Translate("",$conf);
	        $outputlangs->setDefaultLang(GETPOST('lang_id'));
	    }
	    $result= $object->generateDocument($object->modelpdf,$outputlangs, $hidedetails, $hidedesc, $hideref);
	    if ($result	<= 0)
	    {
	        dol_print_error($db,$result);
	        exit;
	    }
	}

	// Delete file in doc form
	if ($action == 'remove_file' && $object->id > 0 && $user->rights->fournisseur->commande->creer)
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	    $langs->load("other");
	    $upload_dir =	$conf->fournisseur->commande->dir_output;
	    $file =	$upload_dir	. '/' .	GETPOST('file');
	    $ret=dol_delete_file($file,0,0,0,$object);
	    if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
	    else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
	}

	if ($action == 'update_extras')
	{
		// Fill array 'array_options' with data from add form
		$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object,GETPOST('attribute'));
		if ($ret < 0) $error++;

		if (! $error)
		{
			// Actions on extra fields (by external module or standard code)
			// FIXME le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('supplierorderdao'));
			$parameters=array('id'=>$object->id);

			$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$object,$action); // Note that $action and $object may have been modified by some hooks

			if (empty($reshook))
			{
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$object->insertExtraFields();

					if ($result < 0)
					{
						$error++;
					}

				}
			}
			else if ($reshook < 0) $error++;
		}
		else
		{
			$action = 'edit_extras';
		}
	}


	/*
	 * Create an order
	 */
	if ($action == 'add' && $user->rights->fournisseur->commande->creer)
	{
	 	$error=0;

	    if ($socid <1)
	    {
		    setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')), 'errors');
	    	$action='create';
	    	$error++;
	    }

	    if (! $error)
	    {
	        $db->begin();

	        // Creation commande
	        $object->ref_supplier  	= GETPOST('refsupplier');
	        $object->socid         	= $socid;
			$object->cond_reglement_id = GETPOST('cond_reglement_id');
	        $object->mode_reglement_id = GETPOST('mode_reglement_id');
	        $object->fk_account        = GETPOST('fk_account', 'int');
	        $object->note_private	= GETPOST('note_private');
	        $object->note_public   	= GETPOST('note_public');
			$object->date_livraison = $datelivraison;
			$object->fk_incoterms = GETPOST('incoterm_id', 'int');
			$object->location_incoterms = GETPOST('location_incoterms', 'alpha');

			// Fill array 'array_options' with data from add form
	       	if (! $error)
	       	{
				$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
				if ($ret < 0) $error++;
	       	}

	       	if (! $error)
	       	{
       			// If creation from another object of another module (Example: origin=propal, originid=1)
				if (! empty($origin) && ! empty($originid))
				{
					$element = 'comm/askpricesupplier';
					$subelement = 'askpricesupplier';

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects [$object->origin] = $object->origin_id;
					$other_linked_objects = GETPOST('other_linked_objects', 'array');
					if (! empty($other_linked_objects)) {
						$object->linked_objects = array_merge($object->linked_objects, $other_linked_objects);
					}

					$object_id = $object->create($user);

					if ($object_id > 0)
					{
						dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

						$classname = ucfirst($subelement);
						$srcobject = new $classname($db);
						$srcobject->fetch($object->origin_id);

						$object->set_date_livraison($user, $srcobject->date_livraison);
						$object->set_id_projet($user, $srcobject->fk_project);

						dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines");
						$result = $srcobject->fetch($object->origin_id);
						if ($result > 0)
						{
							$lines = $srcobject->lines;
							if (empty($lines) && method_exists($srcobject, 'fetch_lines'))
							{
								$srcobject->fetch_lines();
								$lines = $srcobject->lines;
							}

							$fk_parent_line = 0;
							$num = count($lines);

							$productsupplier = new ProductFournisseur($db);

							for($i = 0; $i < $num; $i ++)
							{

								if (empty($lines[$i]->subprice) || $lines[$i]->qty <= 0)
									continue;

								$label = (! empty($lines [$i]->label) ? $lines [$i]->label : '');
								$desc = (! empty($lines [$i]->desc) ? $lines [$i]->desc : $lines [$i]->libelle);
								$product_type = (! empty($lines [$i]->product_type) ? $lines [$i]->product_type : 0);

								// Reset fk_parent_line for no child products and special product
								if (($lines [$i]->product_type != 9 && empty($lines [$i]->fk_parent_line)) || $lines [$i]->product_type == 9) {
									$fk_parent_line = 0;
								}

								// Extrafields
								if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines [$i], 'fetch_optionals')) 							// For avoid conflicts if
								                                                                                                      // trigger used
								{
									$lines [$i]->fetch_optionals($lines [$i]->rowid);
									$array_option = $lines [$i]->array_options;
								}

								$idprod = $productsupplier->find_min_price_product_fournisseur($lines [$i]->fk_product, $lines [$i]->qty);
								$res = $productsupplier->fetch($idProductFourn);

								$result = $object->addline(
									$desc,
									$lines [$i]->subprice,
									$lines [$i]->qty,
									$lines [$i]->tva_tx,
									$lines [$i]->localtax1_tx,
									$lines [$i]->localtax2_tx,
									$lines [$i]->fk_product,
									$productsupplier->product_fourn_price_id,
									$productsupplier->ref_fourn,
									$lines [$i]->remise_percent,
									'HT',
									0,
									$lines [$i]->product_type,
									'',
									'',
									null,
									null
								);

								if ($result < 0) {
									$error ++;
									break;
								}

								// Defined the new fk_parent_line
								if ($result > 0 && $lines [$i]->product_type == 9) {
									$fk_parent_line = $result;
								}
							}

							// Hooks
							$parameters = array('objFrom' => $srcobject);
							$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been
							                                                                               // modified by hook
							if ($reshook < 0)
								$error ++;
						} else {
							setEventMessage($srcobject->error, 'errors');
							$error ++;
						}
					} else {
						setEventMessage($object->error, 'errors');
						$error ++;
					}
				}
				else
				{
		       		$id = $object->create($user);
		        	if ($id < 0)
		        	{
		        		$error++;
			        	setEventMessage($langs->trans($object->error), 'errors');
		        	}
				}
	        }

	        if ($error)
	        {
	            $langs->load("errors");
	            $db->rollback();
	            $action='create';
	            $_GET['socid']=$_POST['socid'];
	        }
	        else
			{
	            $db->commit();
	            header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	            exit;
	        }
	    }
	}

	/*
	 * Add file in email form
	 */
	if (GETPOST('addfile'))
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    // Set tmp user directory TODO Use a dedicated directory for temp mails files
	    $vardir=$conf->user->dir_output."/".$user->id;
	    $upload_dir_tmp = $vardir.'/temp';

	    dol_add_file_process($upload_dir_tmp,0,0);
	    $action='presend';
	}

	/*
	 * Remove file in email form
	 */
	if (GETPOST('removedfile'))
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    // Set tmp user directory
	    $vardir=$conf->user->dir_output."/".$user->id;
	    $upload_dir_tmp = $vardir.'/temp';

		// TODO Delete only files that was uploaded from email form
	    dol_remove_file_process($_POST['removedfile'],0);
	    $action='presend';
	}

	/*
	 * Send mail
	 */
	if ($action == 'send' && ! GETPOST('addfile') && ! GETPOST('removedfile') && ! GETPOST('cancel'))
	{
	    $langs->load('mails');

	    if ($object->id > 0)
	    {
	//        $ref = dol_sanitizeFileName($object->ref);
	//        $file = $conf->fournisseur->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';

	//        if (is_readable($file))
	//        {
	            if (GETPOST('sendto','alpha'))
	            {
	                // Le destinataire a ete fourni via le champ libre
	                $sendto = GETPOST('sendto','alpha');
	                $sendtoid = 0;
	            }
	            elseif (GETPOST('receiver','alpha') != '-1')
	            {
	                // Recipient was provided from combo list
	                if (GETPOST('receiver','alpha') == 'thirdparty') // Id of third party
	                {
	                    $sendto = $object->client->email;
	                    $sendtoid = 0;
	                }
	                else	// Id du contact
	                {
	                    $sendto = $object->client->contact_get_property(GETPOST('receiver','alpha'),'email');
	                    $sendtoid = GETPOST('receiver','alpha');
	                }
	            }

	            if (dol_strlen($sendto))
	            {
	                $langs->load("commercial");

	                $from = GETPOST('fromname','alpha') . ' <' . GETPOST('frommail','alpha') .'>';
	                $replyto = GETPOST('replytoname','alpha'). ' <' . GETPOST('replytomail','alpha').'>';
	                $message = GETPOST('message');
	                $sendtocc = GETPOST('sendtocc','alpha');
	                $deliveryreceipt = GETPOST('deliveryreceipt','alpha');

	                if ($action == 'send')
	                {
	                    if (dol_strlen(GETPOST('subject'))) $subject=GETPOST('subject');
	                    else $subject = $langs->transnoentities('CustomerOrder').' '.$object->ref;
	                    $actiontypecode='AC_SUP_ORD';
	                    $actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
	                    if ($message)
	                    {
							if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
							$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
							$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
							$actionmsg = dol_concatdesc($actionmsg, $message);
	                    }
	                    $actionmsg2=$langs->transnoentities('Action'.$actiontypecode);
	                }

	                // Create form object
	                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
	                $formmail = new FormMail($db);

	                $attachedfiles=$formmail->get_attached_files();
	                $filepath = $attachedfiles['paths'];
	                $filename = $attachedfiles['names'];
	                $mimetype = $attachedfiles['mimes'];

	                // Send mail
	                require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
	                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
	                if ($mailfile->error)
	                {
	                	setEventMessage($mailfile->error, 'errors');
	                }
	                else
	                {
	                    $result=$mailfile->sendfile();
	                    if ($result)
	                    {
	                    	$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));		// Must not contain "
	                    	setEventMessage($mesg);

	                        $error=0;

	                        // Initialisation donnees
	                        $object->sendtoid		= $sendtoid;
	                        $object->actiontypecode	= $actiontypecode;
	                        $object->actionmsg 		= $actionmsg;
	                        $object->actionmsg2		= $actionmsg2;
	                        $object->fk_element		= $object->id;
	                        $object->elementtype	= $object->element;

	                        // Appel des triggers
	                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	                        $interface=new Interfaces($db);
	                        $result=$interface->run_triggers('ORDER_SUPPLIER_SENTBYMAIL',$object,$user,$langs,$conf);
	                        if ($result < 0) { $error++; $errors=$interface->errors; }
	                        // Fin appel triggers

	                        if ($error)
	                        {
	                            setEventMessage($object->error, 'errors');
	                        }
	                        else
	                        {
	                            // Redirect here
	                            // This avoid sending mail twice if going out and then back to page
	                            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	                            exit;
	                        }
	                    }
	                    else
	                    {
	                        $langs->load("other");
	                        if ($mailfile->error)
	                        {
	                            $mesg = $langs->trans('ErrorFailedToSendMail',$from,$sendto);
	                            $mesg.= '<br>'.$mailfile->error;
	                        }
	                        else
	                        {
	                            $mesg = 'No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
	                        }

	                        setEventMessage($mesg, 'errors');
	                    }
	                }
	/*            }
	            else
	            {
	                $langs->load("other");
	                $mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').' !</div>';
	                $action='presend';
	                dol_syslog('Recipient email is empty');
	            }*/
	        }
	        else
	        {
	            $langs->load("errors");
	            setEventMessage($langs->trans('ErrorCantReadFile',$file), 'errors');
	            dol_syslog('Failed to read file: '.$file);
	        }
	    }
	    else
	    {
	        $langs->load("other");
	        setEventMessage($langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")), 'errors');
	        dol_syslog('Impossible de lire les donnees de la facture. Le fichier facture n\'a peut-etre pas ete genere.');
	    }
	}

	if ($action == 'webservice' && GETPOST('mode', 'alpha') == "send" && ! GETPOST('cancel'))
	{
	    $ws_url         = $object->thirdparty->webservices_url;
	    $ws_key         = $object->thirdparty->webservices_key;
	    $ws_user        = GETPOST('ws_user','alpha');
	    $ws_password    = GETPOST('ws_password','alpha');
	    $ws_entity      = GETPOST('ws_entity','int');
	    $ws_thirdparty  = GETPOST('ws_thirdparty','int');

	    // NS and Authentication parameters
	    $ws_ns='http://www.dolibarr.org/ns/';
	    $ws_authentication=array(
	        'dolibarrkey'=>$ws_key,
	        'sourceapplication'=>'DolibarrWebServiceClient',
	        'login'=>$ws_user,
	        'password'=>$ws_password,
	        'entity'=>$ws_entity
	    );

	    //Is sync supplier web services module activated? and everything filled?
	    if (empty($conf->syncsupplierwebservices->enabled)) {
	        setEventMessage($langs->trans("WarningModuleNotActive",$langs->transnoentities("Module2650Name")));
	    } else if (empty($ws_url) || empty($ws_key)) {
	        setEventMessage($langs->trans("ErrorWebServicesFieldsRequired"), 'errors');
	    } else if (empty($ws_user) || empty($ws_password) || empty($ws_thirdparty)) {
	        setEventMessage($langs->trans("ErrorFieldsRequired"), 'errors');
	    }
	    else
	    {
	        //Create SOAP client and connect it to order
	        $soapclient_order = new nusoap_client($ws_url."/webservices/server_order.php");
	        $soapclient_order->soap_defencoding='UTF-8';
	        $soapclient_order->decodeUTF8(false);

	        //Create SOAP client and connect it to product/service
	        $soapclient_product = new nusoap_client($ws_url."/webservices/server_productorservice.php");
	        $soapclient_product->soap_defencoding='UTF-8';
	        $soapclient_product->decodeUTF8(false);

	        //Prepare the order lines from order
	        $order_lines = array();
	        foreach ($object->lines as $line)
	        {
	            $ws_parameters = array('authentication' => $ws_authentication, 'id' => '', 'ref' => $line->ref_supplier);
	            $result_product = $soapclient_product->call("getProductOrService", $ws_parameters, $ws_ns, '');

	            if ($result_product["result"]["result_code"] == "OK")
	            {
	                $order_lines[] = array(
	                    'desc'          => $line->product_desc,
	                    'type'          => $line->product_type,
	                    'product_id'    => $result_product["product"]["id"],
	                    'vat_rate'      => $line->tva_tx,
	                    'qty'           => $line->qty,
	                    'price'         => $line->price,
	                    'unitprice'     => $line->subprice,
	                    'total_net'     => $line->total_ht,
	                    'total_vat'     => $line->total_tva,
	                    'total'         => $line->total_ttc,
	                    'date_start'    => $line->date_start,
	                    'date_end'      => $line->date_end,
	                );
	            }
	        }

	        //Prepare the order header
	        $order = array(
	            'thirdparty_id' => $ws_thirdparty,
	            'date'          => dol_print_date(dol_now(),'dayrfc'),
	            'total_net'     => $object->total_ht,
	            'total_var'     => $object->total_tva,
	            'total'         => $object->total_ttc,
	            'lines'         => $order_lines
	        );

	        $ws_parameters = array('authentication'=>$ws_authentication, 'order' => $order);
	        $result_order = $soapclient_order->call("createOrder", $ws_parameters, $ws_ns, '');

	        if (empty($result_order["result"]["result_code"])) //No result, check error str
	        {
	            setEventMessage($langs->trans("SOAPError")." '".$soapclient_order->error_str."'", 'errors');
	        }
	        else if ($result_order["result"]["result_code"] != "OK") //Something went wrong
	        {
	            setEventMessage($langs->trans("SOAPError")." '".$result_order["result"]["result_code"]."' - '".$result_order["result"]["result_label"]."'", 'errors');
	        }
	        else
	        {
	            setEventMessage($langs->trans("RemoteOrderRef")." ".$result_order["ref"], 'mesgs');
	        }
	    }
	}

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->fournisseur->commande->creer)
	{
		if ($action == 'addcontact')
		{
			if ($object->id > 0)
			{
				$contactid = (GETPOST('userid') ? GETPOST('userid') : GETPOST('contactid'));
				$result = $object->add_contact($contactid, $_POST["type"], $_POST["source"]);
			}

			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else
			{
				if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
				{
					$langs->load("errors");
					setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
				}
				else
				{
					setEventMessage($object->error, 'errors');
				}
			}
		}

		// bascule du statut d'un contact
		else if ($action == 'swapstatut' && $object->id > 0)
		{
			$result=$object->swapContactStatus(GETPOST('ligne'));
		}

		// Efface un contact
		else if ($action == 'deletecontact' && $object->id > 0)
		{
			$result = $object->delete_contact($_GET["lineid"]);

			if ($result >= 0)
			{
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else {
				dol_print_error($db);
			}
		}
	}
}


/*
 * View
 */

llxHeader('',$langs->trans("OrderCard"),"CommandeFournisseur");

$form =	new	Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);
$productstatic = new Product($db);


/* *************************************************************************** */
/*                                                                             */
/* Mode vue et edition                                                         */
/*                                                                             */
/* *************************************************************************** */

$now=dol_now();
if ($action=='create')
{
	print_fiche_titre($langs->trans('NewOrder'));

	dol_htmloutput_events();

	$societe='';
	if ($socid>0)
	{
		$societe=new Societe($db);
		$societe->fetch($socid);
	}

	if (! empty($origin) && ! empty($originid))
	{
		// Parse element/subelement (ex: project_task)
		$element = $subelement = $origin;
		if (preg_match('/^([^_]+)_([^_]+)/i', $origin, $regs)) {
			$element = $regs [1];
			$subelement = $regs [2];
		}

		$element = 'comm/askpricesupplier';
		$subelement = 'askpricesupplier';

		dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

		$classname = ucfirst($subelement);
		$objectsrc = new $classname($db);
		$objectsrc->fetch($originid);
		if (empty($objectsrc->lines) && method_exists($objectsrc, 'fetch_lines'))
			$objectsrc->fetch_lines();
		$objectsrc->fetch_thirdparty();

		// Replicate extrafields
		$objectsrc->fetch_optionals($originid);
		$object->array_options = $objectsrc->array_options;

		$projectid = (! empty($objectsrc->fk_project) ? $objectsrc->fk_project : '');
		$ref_client = (! empty($objectsrc->ref_client) ? $objectsrc->ref_client : '');

		$soc = $objectsrc->client;
		$cond_reglement_id	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
		$mode_reglement_id	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
        $fk_account         = (! empty($objectsrc->fk_account)?$objectsrc->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
		$availability_id	= (!empty($objectsrc->availability_id)?$objectsrc->availability_id:(!empty($soc->availability_id)?$soc->availability_id:0));
        $shipping_method_id = (! empty($objectsrc->shipping_method_id)?$objectsrc->shipping_method_id:(! empty($soc->shipping_method_id)?$soc->shipping_method_id:0));
		$demand_reason_id	= (!empty($objectsrc->demand_reason_id)?$objectsrc->demand_reason_id:(!empty($soc->demand_reason_id)?$soc->demand_reason_id:0));
		$remise_percent		= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
		$remise_absolue		= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
		$dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:'';

		$datedelivery = (! empty($objectsrc->date_livraison) ? $objectsrc->date_livraison : '');

		$note_private = (! empty($objectsrc->note_private) ? $objectsrc->note_private : (! empty($objectsrc->note_private) ? $objectsrc->note_private : ''));
		$note_public = (! empty($objectsrc->note_public) ? $objectsrc->note_public : '');

		// Object source contacts list
		$srccontactslist = $objectsrc->liste_contact(- 1, 'external', 1);

	}
	else
	{
		$cond_reglement_id 	= $societe->cond_reglement_supplier_id;
		$mode_reglement_id 	= $societe->mode_reglement_supplier_id;
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input type="hidden" name="remise_percent" value="' . $soc->remise_percent . '">';
	print '<input type="hidden" name="origin" value="' . $origin . '">';
	print '<input type="hidden" name="originid" value="' . $originid . '">';

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

	// Third party
	print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
	print '<td>';

	if ($socid > 0)
	{
		print $societe->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$socid.'">';
	}
	else
	{
		print $form->select_company((empty($socid)?'':$socid),'socid','s.fournisseur = 1',1);
	}
	print '</td>';

	// Ref supplier
	print '<tr><td>'.$langs->trans('RefSupplier').'</td><td><input name="refsupplier" type="text"></td>';
	print '</tr>';

	print '</td></tr>';

	// Payment term
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$form->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';

	// Planned delivery date
	print '<tr><td>';
	print $langs->trans('DateDeliveryPlanned');
	print '</td>';
	print '<td>';
	$usehourmin=0;
	if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE)) $usehourmin=1;
	$form->select_date($datelivraison?$datelivraison:-1,'liv_',$usehourmin,$usehourmin,'',"set");
	print '</td></tr>';

    // Bank Account
    if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER) && ! empty($conf->banque->enabled))
    {
    	$langs->load("bank");
	    print '<tr><td>' . $langs->trans('BankAccount') . '</td><td colspan="2">';
	    $form->select_comptes($fk_account, 'fk_account', 0, '', 1);
	    print '</td></tr>';
    }

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr>';
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $object->libelle_incoterms, 1).'</label></td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
        print $form->select_incoterms((!empty($object->fk_incoterms) ? $object->fk_incoterms : ''), (!empty($object->location_incoterms)?$object->location_incoterms:''));
		print '</td></tr>';
	}

	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', isset($note_public) ? $note_public : GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td>';
	//print '<textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_private', isset($note_private) ? $note_private : GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td>';
	//print '<td><textarea name="note_private" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
	print '</tr>';

	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {

		print "\n<!-- " . $classname . " info -->";
		print "\n";
		print '<input type="hidden" name="amount"         value="' . $objectsrc->total_ht . '">' . "\n";
		print '<input type="hidden" name="total"          value="' . $objectsrc->total_ttc . '">' . "\n";
		print '<input type="hidden" name="tva"            value="' . $objectsrc->total_tva . '">' . "\n";
		print '<input type="hidden" name="origin"         value="' . $objectsrc->element . '">';
		print '<input type="hidden" name="originid"       value="' . $objectsrc->id . '">';

		$newclassname = $classname;
		if ($newclassname == 'AskPriceSupplier')
			$newclassname = 'CommercialAskPriceSupplier';
		print '<tr><td>' . $langs->trans($newclassname) . '</td><td colspan="2">' . $objectsrc->getNomUrl(1) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalHT') . '</td><td colspan="2">' . price($objectsrc->total_ht) . '</td></tr>';
		print '<tr><td>' . $langs->trans('TotalVAT') . '</td><td colspan="2">' . price($objectsrc->total_tva) . "</td></tr>";
		if ($mysoc->localtax1_assuj == "1" || $objectsrc->total_localtax1 != 0) 		// Localtax1 RE
		{
			print '<tr><td>' . $langs->transcountry("AmountLT1", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax1) . "</td></tr>";
		}

		if ($mysoc->localtax2_assuj == "1" || $objectsrc->total_localtax2 != 0) 		// Localtax2 IRPF
		{
			print '<tr><td>' . $langs->transcountry("AmountLT2", $mysoc->country_code) . '</td><td colspan="2">' . price($objectsrc->total_localtax2) . "</td></tr>";
		}

		print '<tr><td>' . $langs->trans('TotalTTC') . '</td><td colspan="2">' . price($objectsrc->total_ttc) . "</td></tr>";
	}

	// Other options
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

	if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
		print $object->showOptionals($extrafields,'edit');
    }

	// Bouton "Create Draft"
    print "</table>\n";

	print '<br><div class="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></div>';

	print "</form>\n";

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc)) {
		$title = $langs->trans('ProductsAndServices');
		print_titre($title);

		print '<table class="noborder" width="100%">';

		$objectsrc->printOriginLinesList();

		print '</table>';
	}
}
elseif (! empty($object->id))
{
    $societe = new Fournisseur($db);
    $result=$societe->fetch($object->socid);
    if ($result < 0) dol_print_error($db);

	$author	= new User($db);
	$author->fetch($object->user_author_id);

	$res=$object->fetch_optionals($object->id,$extralabels);

	$head = ordersupplier_prepare_head($object);

	$title=$langs->trans("SupplierOrder");
	dol_fiche_head($head, 'card', $title, 0, 'order');


	$formconfirm='';

	/*
	 * Confirmation de la suppression de la commande
	 */
	if ($action	== 'delete')
	{
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 2);

	}

	// Clone confirmation
	if ($action == 'clone')
	{
		// Create an array for form
		$formquestion=array(
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1)
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneOrder'),$langs->trans('ConfirmCloneOrder',$object->ref),'confirm_clone',$formquestion,'yes',1);

	}

	/*
	 * Confirmation de la validation
	 */
	if ($action	== 'valid')
	{
		$object->date_commande=dol_now();

		// We check if number is temporary number
		if (preg_match('/^[\(]?PROV/i',$object->ref)) $newref = $object->getNextNumRef($object->thirdparty);
		else $newref = $object->ref;

		if ($newref < 0)
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action='';
		}
		else
		{
			$text=$langs->trans('ConfirmValidateOrder',$newref);
			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
				$notify=new	Notify($db);
				$text.='<br>';
				$text.=$notify->confirmMessage('ORDER_SUPPLIER_VALIDATE', $object->socid, $object);
			}

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_valid', '', 0, 1);
		}
	}

	/*
	 * Confirmation de l'approbation
	 */
	if ($action	== 'approve')
	{
        $qualified_for_stock_change=0;
	    if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
	    {
	    	$qualified_for_stock_change=$object->hasProductsOrServices(2);
	    }
	    else
	    {
	    	$qualified_for_stock_change=$object->hasProductsOrServices(1);
	    }

		$formquestion=array();
		if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $qualified_for_stock_change)
		{
			$langs->load("stocks");
			require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
			$formproduct=new FormProduct($db);
			$formquestion=array(
					//'text' => $langs->trans("ConfirmClone"),
					//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
					//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
					array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1))
			);
		}
		$text=$langs->trans("ConfirmApproveThisOrder",$object->ref);
		if (! empty($conf->notification->enabled))
		{
			require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
			$notify=new	Notify($db);
			$text.='<br>';
			$text.=$notify->confirmMessage('ORDER_SUPPLIER_APPROVE', $object->socid, $object);
		}

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("ApproveThisOrder"), $text, "confirm_approve", $formquestion, 1, 1, 240);
	}

	/*
	 * Confirmation de la desapprobation
	 */
	if ($action	== 'refuse')
	{
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id",$langs->trans("DenyingThisOrder"),$langs->trans("ConfirmDenyingThisOrder",$object->ref),"confirm_refuse", '', 0, 1);

	}

	/*
	 * Confirmation de l'annulation
	 */
	if ($action	== 'cancel')
	{
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id",$langs->trans("Cancel"),$langs->trans("ConfirmCancelThisOrder",$object->ref),"confirm_cancel", '', 0, 1);

	}

	/*
	 * Confirmation de l'envoi de la commande
	 */
	if ($action	== 'commande')
	{
		$date_com = dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id."&datecommande=".$date_com."&methode=".$_POST["methodecommande"]."&comment=".urlencode($_POST["comment"]), $langs->trans("MakeOrder"),$langs->trans("ConfirmMakeOrder",dol_print_date($date_com,'day')),"confirm_commande",'',0,2);

	}

	// Confirmation to delete line
	if ($action == 'ask_deleteline')
	{
		 $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
	}

	if (!$formconfirm)
	{
		$parameters=array('lineid'=>$lineid);
		$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	/*
	 *	Commande
	*/
	$nbrow=8;
	if (! empty($conf->projet->enabled))	$nbrow++;

	//Local taxes
	if($mysoc->localtax1_assuj=="1") $nbrow++;
	if($mysoc->localtax2_assuj=="1") $nbrow++;

	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td width="20%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="2">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
	print '</td>';
	print '</tr>';

	// Ref supplier
	print '<tr><td>';
	print $form->editfieldkey("RefSupplier",'ref_supplier',$object->ref_supplier,$object,$user->rights->fournisseur->commande->creer);
	print '</td><td colspan="2">';
	print $form->editfieldval("RefSupplier",'ref_supplier',$object->ref_supplier,$object,$user->rights->fournisseur->commande->creer);
	print '</td></tr>';

	// Fournisseur
	print '<tr><td>'.$langs->trans("Supplier")."</td>";
	print '<td colspan="2">'.$object->thirdparty->getNomUrl(1,'supplier').'</td>';
	print '</tr>';

	// Statut
	print '<tr>';
	print '<td>'.$langs->trans("Status").'</td>';
	print '<td colspan="2">';
	print $object->getLibStatut(4);
	print "</td></tr>";

	// Date
	if ($object->methode_commande_id > 0)
	{
		print '<tr><td>'.$langs->trans("Date").'</td><td colspan="2">';
		if ($object->date_commande)
		{
			print dol_print_date($object->date_commande,"dayhourtext")."\n";
		}
		print "</td></tr>";

		if ($object->methode_commande)
		{
			print '<tr><td>'.$langs->trans("Method").'</td><td colspan="2">'.$object->getInputMethod().'</td></tr>';
		}
	}

	// Author
	print '<tr><td>'.$langs->trans("AuthorRequest").'</td>';
	print '<td colspan="2">'.$author->getNomUrl(1).'</td>';
	print '</tr>';

	// Conditions de reglement par defaut
	$langs->load('bills');
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('PaymentConditions');
	print '<td>';
	if ($action != 'editconditions') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="2">';
	if ($action == 'editconditions')
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,  $object->cond_reglement_id,'cond_reglement_id');
	}
	else
	{
		$form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,  $object->cond_reglement_id,'none');
	}
	print "</td>";
	print '</tr>';

	// Mode of payment
	$langs->load('bills');
	print '<tr><td class="nowrap">';
	print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
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
	print '</td></tr>';

    // Bank Account
	if (! empty($conf->global->BANK_ASK_PAYMENT_BANK_DURING_SUPPLIER_ORDER) && ! empty($conf->banque->enabled))
	{
	    print '<tr><td class="nowrap">';
	    print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
	    print $langs->trans('BankAccount');
	    print '<td>';
	    if ($action != 'editbankaccount' && $user->rights->fournisseur->commande->creer)
	        print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
	    print '</tr></table>';
	    print '</td><td colspan="3">';
	    if ($action == 'editbankaccount') {
	        $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
	    } else {
	        $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
	    }
	    print '</td>';
	    print '</tr>';
	}

	// Delivery date planed
	print '<tr><td>';
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
		print '<input type="hidden" name="action" value="setdate_livraison">';
		$usehourmin=0;
		if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE)) $usehourmin=1;
		$form->select_date($object->date_livraison?$object->date_livraison:-1,'liv_',$usehourmin,$usehourmin,'',"setdate_livraison");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		$usehourmin='day';
		if (! empty($conf->global->SUPPLIER_ORDER_USE_HOUR_FOR_DELIVERY_DATE)) $usehourmin='dayhour';
		print $object->date_livraison ? dol_print_date($object->date_livraison, $usehourmin) : '&nbsp;';
	}
	print '</td></tr>';


	// Delai livraison jours
	print '<tr>';
	print '<td>'.$langs->trans('NbDaysToDelivery').'&nbsp;'.img_picto($langs->trans('DescNbDaysToDelivery'), 'info', 'style="cursor:help"').'</td>';
	print '<td>'.$object->getMaxDeliveryTimeDay($langs).'</td>';
	print '</tr>';

	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load('projects');
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project');
		print '</td>';
		if ($action != 'classify') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		//print "$object->id, $object->socid, $object->fk_project";
		if ($action == 'classify')
		{
			$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS)?$object->socid:'-1'), $object->fk_project, 'projectid', 0, 0, 1);
		}
		else
		{
			$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0);
		}
		print '</td>';
		print '</tr>';
	}

	// Incoterms
	if (!empty($conf->incoterm->enabled))
	{
		print '<tr><td>';
        print '<table width="100%" class="nobordernopadding"><tr><td>';
        print $langs->trans('IncotermLabel');
        print '<td><td align="right">';
        if ($user->rights->fournisseur->commande->creer) print '<a href="'.DOL_URL_ROOT.'/fourn/commande/card.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
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
	$cols = 3;
	include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

	// Ligne de	3 colonnes
	print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	print '<td colspan="2">'.price($object->total_ht,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	print '<tr><td>'.$langs->trans("AmountVAT").'</td><td colspan="2">'.price($object->total_tva,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj=="1" || $object->total_localtax1 != 0) //Localtax1
	{
		print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
		print '<td colspan="2">'.price($object->total_localtax1,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';
	}
	if ($mysoc->localtax2_assuj=="1" || $object->total_localtax2 != 0) //Localtax2
	{
		print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
		print '<td colspan="2">'.price($object->total_localtax2,'',$langs,1,-1,-1,$conf->currency).'</td>';
		print '</tr>';
	}

	print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="2">'.price($object->total_ttc,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	print "</table><br>";

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	/*
	 * Lines
	 */
	//$result = $object->getLinesArray();


	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.(($action != 'editline')?'#add':'#line_'.GETPOST('lineid')).'" method="POST">
	<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
	<input type="hidden" name="action" value="' . (($action != 'editline') ? 'addline' : 'updateline') . '">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="'.$object->id.'">
    <input type="hidden" name="socid" value="'.$societe->id.'">
	';

	if (! empty($conf->use_javascript_ajax) && $object->statut == 0) {
		include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
	}

	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	// Add free products/services form
	global $forceall, $senderissupplier, $dateSelector;
	$forceall=1; $senderissupplier=1; $dateSelector=0;

	// Show object lines
	$inputalsopricewithtax=0;
	if (! empty($object->lines))
		$ret = $object->printObjectLines($action, $soc, $mysoc, $lineid, 1, $user->rights->fournisseur->commande->creer);

	$num = count($object->lines);

/*
	$i = 0;	$total = 0;
	if ($num)
	{
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Label').'</td>';
		print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
		print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
		print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
		print '<td align="right" width="50">'.$langs->trans('TotalHTShort').'</td>';
		print '<td width="48" colspan="3">&nbsp;</td>';
		print "</tr>\n";
	}
	$var=true;
	while ($i <	$num)
	{
		$line =	$object->lines[$i];
		$var=!$var;

		// Show product and description
		$type=(! empty($line->product_type)?$line->product_type:(! empty($line->fk_product_type)?$line->fk_product_type:0));
		// Try to enhance type detection using date_start and date_end for free lines where type
		// was not saved.
		$date_start='';
		$date_end='';
		if (! empty($line->date_start))
		{
			$date_start=$line->date_start;
			$type=1;
		}
		if (! empty($line->date_end))
		{
			$date_end=$line->date_end;
			$type=1;
		}

		// Edit line
		if ($action != 'editline' || $_GET['rowid'] != $line->id)
		{
			print '<tr id="row-'.$line->id.'" '.$bc[$var].'>';

			// Show product and description
			print '<td>';
			if ($line->fk_product > 0)
			{
				print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne

				$product_static=new ProductFournisseur($db);
				$product_static->fetch($line->fk_product);
				$text=$product_static->getNomUrl(1,'supplier');
				$text.= ' - '.$product_static->libelle;
				$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->description));
				print $form->textwithtooltip($text,$description,3,'','',$i);

				// Show range
				print_date_range($date_start,$date_end);

				// Add description in form
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM)) print ($line->description && $line->description!=$product_static->libelle)?'<br>'.dol_htmlentitiesbr($line->description):'';
			}

			// Description - Editor wysiwyg
			if (! $line->fk_product)
			{
				if ($type==1) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');
				print $text.' '.nl2br($line->description);

				// Show range
				print_date_range($date_start,$date_end);
			}

			print '</td>';

			print '<td align="right" class="nowrap">'.vatrate($line->tva_tx).'%</td>';

			print '<td align="right" class="nowrap">'.price($line->subprice)."</td>\n";

			print '<td align="right" class="nowrap">'.$line->qty.'</td>';

			if ($line->remise_percent >	0)
			{
				print '<td align="right" class="nowrap">'.dol_print_reduction($line->remise_percent,$langs)."</td>\n";
			}
			else
			{
				print '<td>&nbsp;</td>';
			}

			print '<td align="right" class="nowrap">'.price($line->total_ht).'</td>';

			if (is_object($hookmanager))
			{
				$parameters=array('line'=>$line,'num'=>$num,'i'=>$i);
				$reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$object,$action);
			}

			if ($object->statut == 0	&& $user->rights->fournisseur->commande->creer)
			{
				print '<td align="center" width="16"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;rowid='.$line->id.'#'.$line->id.'">';
				print img_edit();
				print '</a></td>';

				$actiondelete='delete_product_line';
				print '<td align="center" width="16"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action='.$actiondelete.'&amp;lineid='.$line->id.'">';
				print img_delete();
				print '</a></td>';
			}
			else
			{
				print '<td>&nbsp;</td><td>&nbsp;</td>';
			}
			print "</tr>";
		}

		// Edit line
		if ($action	== 'editline' && $user->rights->fournisseur->commande->creer && ($_GET["rowid"] == $line->id))
		{
			print "\n";
			print '<tr '.$bc[$var].'>';
			print '<td>';

			print '<input type="hidden" name="elrowid" value="'.$_GET['rowid'].'">';

			print '<a name="'.$line->id.'"></a>'; // ancre pour retourner sur la ligne
			if ((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && $line->fk_product > 0)
			{
				$product_static=new ProductFournisseur($db);
				$product_static->fetch($line->fk_product);
				$text=$product_static->getNomUrl(1,'supplier');
				$text.= ' - '.$product_static->libelle;
				$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->description));
				print $form->textwithtooltip($text,$description,3,'','',$i);

				// Show range
				print_date_range($date_start,$date_end);
				print '<br>';
			}
			else
			{
                $forceall=1;	// For suppliers, we always show all types
                print $form->select_type_of_lines($line->product_type,'type',1,0,$forceall);
                if ($forceall || (! empty($conf->product->enabled) && ! empty($conf->service->enabled))
                || (empty($conf->product->enabled) && empty($conf->service->enabled))) print '<br>';
			}

			if (is_object($hookmanager))
			{
				$parameters=array('fk_parent_line'=>$line->fk_parent_line, 'line'=>$line,'var'=>$var,'num'=>$num,'i'=>$i);
				$reshook=$hookmanager->executeHooks('formEditProductOptions',$parameters,$object,$action);
			}

			$nbrows=ROWS_2;
			if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
			$doleditor=new DolEditor('eldesc',$line->description,'',200,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
			$doleditor->Create();

            print '<br>';
            print $langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
            print $form->select_date($date_start,'date_start'.$date_pf,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,'');
            print ' '.$langs->trans('to').' ';
            print $form->select_date($date_end,'date_end'.$date_pf,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE,1,'');

			print '</td>';
			print '<td>';
			print $form->load_tva('tva_tx',$line->tva_tx,$object->thirdparty,$mysoc);
			print '</td>';
			print '<td align="right"><input	size="5" type="text" name="pu"	value="'.price($line->subprice).'"></td>';
			print '<td align="right"><input size="2" type="text" name="qty" value="'.$line->qty.'"></td>';
			print '<td align="right" class="nowrap"><input size="1" type="text" name="remise_percent" value="'.$line->remise_percent.'"><span class="hideonsmartphone">%</span></td>';
			print '<td align="center" colspan="4"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
			print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
			print '</tr>' .	"\n";
		}
		$i++;
	}
*/
	// Form to add new line
	if ($object->statut == 0 && $user->rights->fournisseur->commande->creer)
	{
		if ($action != 'editline')
		{
			$var = true;

			// Add free products/services
			$object->formAddObjectLine(1, $societe, $mysoc);

			$parameters = array();
			$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		}
	}
	print '</table>';

	print '</form>';

	dol_fiche_end();


	/*
	 * Action presend
	 */
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref,'/'));
		$file=$fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->client->default_lang;

		if (!empty($newlang))
		{
			$outputlangs = new Translate('', $conf);
			$outputlangs->setDefaultLang($newlang);
			$outputlangs->load('commercial');
		}

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
			$result= $object->generateDocument(GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$result);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref,'/'));
			$file=$fileparams['fullname'];
		}

		print '<br>';

		print_titre($langs->trans('SendOrderByMail'));

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		$formmail->withfrom=1;
		$liste=array();
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key=>$value)	$liste[$key]=$value;
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=(! empty($conf->global->MAIN_EMAIL_USECCC)?$conf->global->MAIN_EMAIL_USECCC:false);
		$formmail->withtopic=$outputlangs->trans('SendOrderRef','__ORDERREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		$object->fetch_projet();
		// Tableau des substitutions
		$formmail->substit['__ORDERREF__']=$object->ref;
		$formmail->substit['__ORDERSUPPLIERREF__']=$object->ref_supplier;
		$formmail->substit['__THIRPARTY_NAME__'] = $object->thirdparty->name;
		$formmail->substit['__PROJECT_REF__'] = (is_object($object->projet)?$object->projet->ref:'');
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		//Find the good contact adress
		$custcontact='';
		$contactarr=array();
		$contactarr=$object->liste_contact(-1,'external');

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {
				if ($contact['libelle']==$langs->trans('TypeContact_order_supplier_external_BILLING')) {
					require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
					$contactstatic=new Contact($db);
					$contactstatic->fetch($contact['id']);
					$custcontact=$contactstatic->getFullName($langs,1);
				}
			}

			if (!empty($custcontact)) {
				$formmail->substit['__CONTACTCIVNAME__']=$custcontact;
			}
		}

		// Tableau des parametres complementaires
		$formmail->param['action']='send';
		$formmail->param['models']='order_supplier_send';
		$formmail->param['orderid']=$object->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}

		// Show form
		print $formmail->get_form();

		print '<br>';
	}
	/*
	 * Action webservice
	 */
	elseif ($action == 'webservice' && GETPOST('mode', 'alpha') != "send" && ! GETPOST('cancel'))
	{
		$mode        = GETPOST('mode', 'alpha');
		$ws_url      = $object->thirdparty->webservices_url;
		$ws_key      = $object->thirdparty->webservices_key;
		$ws_user     = GETPOST('ws_user','alpha');
		$ws_password = GETPOST('ws_password','alpha');

        // NS and Authentication parameters
        $ws_ns = 'http://www.dolibarr.org/ns/';
        $ws_authentication = array(
            'dolibarrkey'=>$ws_key,
            'sourceapplication'=>'DolibarrWebServiceClient',
            'login'=>$ws_user,
            'password'=>$ws_password,
            'entity'=>''
        );

        print_titre($langs->trans('CreateRemoteOrder'));

        //Is everything filled?
        if (empty($ws_url) || empty($ws_key)) {
            setEventMessage($langs->trans("ErrorWebServicesFieldsRequired"), 'errors');
            $mode = "init";
            $error_occurred = true; //Don't allow to set the user/pass if thirdparty fields are not filled
        } else if ($mode != "init" && (empty($ws_user) || empty($ws_password))) {
            setEventMessage($langs->trans("ErrorFieldsRequired"), 'errors');
            $mode = "init";
        }

        if ($mode == "init")
        {
            //Table/form header
            print '<table class="border" width="100%">';
            print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="webservice">';
            print '<input type="hidden" name="mode" value="check">';

            if ($error_occurred)
            {
                print "<br>".$langs->trans("ErrorOccurredReviseAndRetry")."<br>";
                print '<input class="button" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
            }
            else
            {
                $textinput_size = "50";
                // Webservice url
                print '<tr><td>'.$langs->trans("WebServiceURL").'</td><td colspan="3">'.dol_print_url($ws_url).'</td></tr>';
                //Remote User
                print '<tr><td>'.$langs->trans("User").'</td><td><input size="'.$textinput_size.'" type="text" name="ws_user"></td></tr>';
                //Remote Password
                print '<tr><td>'.$langs->trans("Password").'</td><td><input size="'.$textinput_size.'" type="text" name="ws_password"></td></tr>';
                //Submit button
                print '<tr><td align="center" colspan="2">';
                print '<input class="button" type="submit" id="ws_submit" name="ws_submit" value="'.$langs->trans("CreateRemoteOrder").'">';
                print ' &nbsp; &nbsp; ';
                //Cancel button
                print '<input class="button" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
                print '</td></tr>';
            }

            //End table/form
            print '</form>';
            print '</table>';
        }
        elseif ($mode == "check")
        {
            $ws_entity = '';
            $ws_thirdparty = '';
            $error_occurred = false;

            //Create SOAP client and connect it to user
            $soapclient_user = new nusoap_client($ws_url."/webservices/server_user.php");
            $soapclient_user->soap_defencoding='UTF-8';
            $soapclient_user->decodeUTF8(false);

            //Get the thirdparty associated to user
            $ws_parameters = array('authentication'=>$ws_authentication, 'id' => '', 'ref'=>$ws_user);
            $result_user = $soapclient_user->call("getUser", $ws_parameters, $ws_ns, '');
            $user_status_code = $result_user["result"]["result_code"];

            if ($user_status_code == "OK")
            {
                //Fill the variables
                $ws_entity = $result_user["user"]["entity"];
                $ws_authentication['entity'] = $ws_entity;
                $ws_thirdparty = $result_user["user"]["fk_thirdparty"];
                if (empty($ws_thirdparty))
                {
                    setEventMessage($langs->trans("RemoteUserMissingAssociatedSoc"), 'errors');
                    $error_occurred = true;
                }
                else
                {
                    //Create SOAP client and connect it to product/service
                    $soapclient_product = new nusoap_client($ws_url."/webservices/server_productorservice.php");
                    $soapclient_product->soap_defencoding='UTF-8';
                    $soapclient_product->decodeUTF8(false);

                    // Iterate each line and get the reference that uses the supplier of that product/service
                    $i = 0;
                    foreach ($object->lines as $line) {
                        $i = $i + 1;
                        $ref_supplier = $line->ref_supplier;
                        $line_id = $i."º) ".$line->product_ref.": ";
                        if (empty($ref_supplier)) {
                            continue;
                        }
                        $ws_parameters = array('authentication' => $ws_authentication, 'id' => '', 'ref' => $ref_supplier);
                        $result_product = $soapclient_product->call("getProductOrService", $ws_parameters, $ws_ns, '');
                        if (!$result_product)
                        {
                            setEventMessage($line_id.$langs->trans("SOAPError")." ".$soapclient_product->error_str." - ".$soapclient_product->response, 'errors');
                            $error_occurred = true;
                            break;
                        }

                        // Check the result code
                        $status_code = $result_product["result"]["result_code"];
                        if (empty($status_code)) //No result, check error str
                        {
                            setEventMessage($langs->trans("SOAPError")." '".$soapclient_order->error_str."'", 'errors');
                        }
                        else if ($status_code != "OK") //Something went wrong
                        {
                            if ($status_code == "NOT_FOUND")
                            {
                                setEventMessage($line_id.$langs->trans("SupplierMissingRef")." '".$ref_supplier."'", 'warnings');
                            }
                            else
                            {
                                setEventMessage($line_id.$langs->trans("ResponseNonOK")." '".$status_code."' - '".$result_product["result"]["result_label"]."'", 'errors');
                                $error_occurred = true;
                                break;
                            }
                        }


                        // Ensure that price is equal and warn user if it's not
                        $supplier_price = price($result_product["product"]["price_net"]); //Price of client tab in supplier dolibarr
                        $local_price = NULL; //Price of supplier as stated in product suppliers tab on this dolibarr, NULL if not found

                        $product_fourn = new ProductFournisseur($db);
                        $product_fourn_list = $product_fourn->list_product_fournisseur_price($line->fk_product);
                        if (count($product_fourn_list)>0)
                        {
                            foreach($product_fourn_list as $product_fourn_line)
                            {
                                //Only accept the line where the supplier is the same at this order and has the same ref
                                if ($product_fourn_line->fourn_id == $object->socid && $product_fourn_line->fourn_ref == $ref_supplier) {
                                    $local_price = price($product_fourn_line->fourn_price);
                                }
                            }
                        }

                        if ($local_price != NULL && $local_price != $supplier_price) {
                            setEventMessage($line_id.$langs->trans("RemotePriceMismatch")." ".$supplier_price." - ".$local_price, 'warnings');
                        }

                        // Check if is in sale
                        if (empty($result_product["product"]["status_tosell"])) {
                            setEventMessage($line_id.$langs->trans("ProductStatusNotOnSellShort")." '".$ref_supplier."'", 'warnings');
                        }
                    }
                }

            }
            elseif ($user_status_code == "PERMISSION_DENIED")
            {
                setEventMessage($langs->trans("RemoteUserNotPermission"), 'errors');
                $error_occurred = true;
            }
            elseif ($user_status_code == "BAD_CREDENTIALS")
            {
                setEventMessage($langs->trans("RemoteUserBadCredentials"), 'errors');
                $error_occurred = true;
            }
            else
            {
                setEventMessage($langs->trans("ResponseNonOK")." '".$user_status_code."'", 'errors');
                $error_occurred = true;
            }

            //Form
            print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="webservice">';
            print '<input type="hidden" name="mode" value="send">';
            print '<input type="hidden" name="ws_user" value="'.$ws_user.'">';
            print '<input type="hidden" name="ws_password" value="'.$ws_password.'">';
            print '<input type="hidden" name="ws_entity" value="'.$ws_entity.'">';
            print '<input type="hidden" name="ws_thirdparty" value="'.$ws_thirdparty.'">';
            if ($error_occurred)
            {
                print "<br>".$langs->trans("ErrorOccurredReviseAndRetry")."<br>";
            }
            else
            {
                print '<input class="button" type="submit" id="ws_submit" name="ws_submit" value="'.$langs->trans("Confirm").'">';
                print ' &nbsp; &nbsp; ';
            }
            print '<input class="button" type="submit" id="cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
            print '</form>';
        }
	}
	/*
	 * Show buttons
	 */
	else
	{
		/**
		 * Boutons actions
		 */
		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		// modified by hook
		if (empty($reshook))
		{
			if ($user->societe_id == 0 && $action != 'editline' && $action != 'delete')
			{
				print '<div	 class="tabsAction">';

				// Validate
				if ($object->statut == 0 && $num > 0)
				{
			        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->commande->creer))
			       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_order_advance->validate)))
					{
						$tmpbuttonlabel=$langs->trans('Validate');
						if ($user->rights->fournisseur->commande->approuver && empty($conf->global->SUPPLIER_ORDER_NO_DIRECT_APPROVE)) $tmpbuttonlabel = $langs->trans("ValidateAndApprove");

						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">';
						print $tmpbuttonlabel;
						print '</a>';
					}
				}

				// Modify
				if ($object->statut == 1)
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Modify").'</a>';
					}
				}

				// Approve
				if ($object->statut == 1)
				{
					if ($user->rights->fournisseur->commande->approuver)
					{
						print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';
						print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
					}
					else
					{
						print '<a class="butActionRefused" href="#">'.$langs->trans("ApproveOrder").'</a>';
						print '<a class="butActionRefused" href="#">'.$langs->trans("RefuseOrder").'</a>';
					}
				}

				// Send
				if (in_array($object->statut, array(2, 3, 4, 5)))
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
					}
				}

				// Reopen
				if (in_array($object->statut, array(2)))
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Disapprove").'</a>';
					}
				}
				if (in_array($object->statut, array(5, 6, 7, 9)))
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
					}
				}

				// Create bill
				if (! empty($conf->fournisseur->enabled) && $object->statut >= 2)  // 2 means accepted
				{
					if ($user->rights->fournisseur->facture->creer)
					{
						print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
					}

					//if ($user->rights->fournisseur->commande->creer && $object->statut > 2)
					//{
					//	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
					//}
				}


				// Create a remote order using WebService only if module is activated
				if (! empty($conf->syncsupplierwebservices->enabled) && $object->statut >= 2) // 2 means accepted
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=webservice&amp;mode=init">'.$langs->trans('CreateRemoteOrder').'</a>';
				}

				// Cancel
				if ($object->statut == 2)
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
					}
				}

				// Clone
				if ($user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a>';
				}

				// Delete
				if ($user->rights->fournisseur->commande->supprimer)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
				}

				print "</div>";
			}
		}
		print "<br>";


		print '<div class="fichecenter"><div class="fichehalfleft">';

		/*
		 * Documents generes
		 */
		$comfournref = dol_sanitizeFileName($object->ref);
		$file =	$conf->fournisseur->dir_output . '/commande/' . $comfournref .	'/'	. $comfournref . '.pdf';
		$relativepath =	$comfournref.'/'.$comfournref.'.pdf';
		$filedir = $conf->fournisseur->dir_output	. '/commande/' .	$comfournref;
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed=$user->rights->fournisseur->commande->creer;
		$delallowed=$user->rights->fournisseur->commande->supprimer;

		print $formfile->showdocuments('commande_fournisseur',$comfournref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,0,0,'','','',$object->thirdparty->default_lang);
		$somethingshown=$formfile->numoffiles;

		/*
		 * Linked object block
		 */
		$somethingshown=$object->showLinkedObjectBlock();

		print '</div><div class="fichehalfright"><div class="ficheaddleft">';


        // List of actions on element
        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        $formactions=new FormActions($db);
        $somethingshown=$formactions->showactions($object,'order_supplier',$socid);


		if ($user->rights->fournisseur->commande->commander && $object->statut == 2)
		{
			/*
			 * Commander (action=commande)
			 */
			print '<br>';
			print '<form name="commande" action="card.php?id='.$object->id.'&amp;action=commande" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden"	name="action" value="commande">';
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
			print '<tr><td>'.$langs->trans("OrderDate").'</td><td>';
			$date_com = dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
			print $form->select_date($date_com,'','','','',"commande");
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("OrderMode").'</td><td>';
			$formorder->selectInputMethod(GETPOST('methodecommande'), "methodecommande", 1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="40" type="text" name="comment" value="'.GETPOST('comment').'"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("ToOrder").'"></td></tr>';
			print '</table>';
			print '</form>';
		}

		if ($user->rights->fournisseur->commande->receptionner	&& ($object->statut == 3 || $object->statut == 4))
		{
			/*
			 * Receptionner (action=livraison)
			 */
			print '<br>';
			print '<form action="card.php?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden"	name="action" value="livraison">';
			print '<table class="border" width="100%">';
			print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Receive").'</td></tr>';
			print '<tr><td>'.$langs->trans("DeliveryDate").'</td><td>';
			print $form->select_date('','','','','',"commande");
			print "</td></tr>\n";

			print "<tr><td>".$langs->trans("Delivery")."</td><td>\n";
			$liv = array();
			$liv[''] = '&nbsp;';
			$liv['tot']	= $langs->trans("TotalWoman");
			$liv['par']	= $langs->trans("PartialWoman");
			$liv['nev']	= $langs->trans("NeverReceived");
			$liv['can']	= $langs->trans("Canceled");

			print $form->selectarray("type",$liv);

			print '</td></tr>';
			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="40" type="text" name="comment"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("Receive").'"></td></tr>';
			print "</table>\n";
			print "</form>\n";
		}

		// List of actions on element
		/* Hidden because" available into "Log" tab
		print '<br>';
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($object,'order_supplier',$socid);
		*/

		print '</div></div></div>';
	}

	print '</td></tr></table>';
}

// End of page
llxFooter();

$db->close();
