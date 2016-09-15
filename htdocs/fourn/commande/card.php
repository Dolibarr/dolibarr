<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2016 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011-2015 Philippe Grand       <philippe.grand@atoo-net.com>
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
if (! empty($conf->supplier_proposal->enabled))
	require DOL_DOCUMENT_ROOT . '/supplier_proposal/class/supplier_proposal.class.php';
if (!empty($conf->produit->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (!empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
require_once NUSOAP_PATH.'/nusoap.php';     // Include SOAP

$langs->load('admin');
$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('supplier_proposal');
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
$permissiondellink=$user->rights->fournisseur->commande->creer;	// Used by the include of actions_dellink.inc.php
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

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

	if ($action == 'setref_supplier' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
		else $object->ref_supplier = GETPOST('ref_supplier','alpha');	// The setValueFrom does not set new property of object
	}

	// Set incoterm
	if ($action == 'set_incoterms' && $user->rights->fournisseur->commande->creer)
	{
		$result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// payment conditions
	if ($action == 'setconditions' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// payment mode
	if ($action == 'setmode' && $user->rights->fournisseur->commande->creer)
	{
	    $result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// Multicurrency Code
	else if ($action == 'setmulticurrencycode' && $user->rights->fournisseur->commande->creer) {
		$result = $object->setMulticurrencyCode(GETPOST('multicurrency_code', 'alpha'));
	}

	// Multicurrency rate
	else if ($action == 'setmulticurrencyrate' && $user->rights->fournisseur->commande->creer) {
		$result = $object->setMulticurrencyRate(price2num(GETPOST('multicurrency_tx')));
	}

	// bank account
	if ($action == 'setbankaccount' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setBankAccount(GETPOST('fk_account', 'int'));
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// date of delivery
	if ($action == 'setdate_livraison' && $user->rights->fournisseur->commande->creer)
	{
		$result=$object->set_date_livraison($user,$datelivraison);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	// Set project
	if ($action ==	'classin' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setProject($projectid);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($action == 'setremisepercent' && $user->rights->fournisseur->commande->creer)
	{
	    $result = $object->set_remise($user, $_POST['remise_percent']);
		if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
	}

	if ($action == 'reopen')	// no test on permission here, permission to use will depends on status
	{
	    if (in_array($object->statut, array(1, 2, 3, 4, 5, 6, 7, 9)))
	    {
	        if ($object->statut == 1) $newstatus=0;	// Validated->Draft
	        else if ($object->statut == 2) $newstatus=0;	// Approved->Draft
	        else if ($object->statut == 3) $newstatus=2;	// Ordered->Approved
	        else if ($object->statut == 4) $newstatus=3;
	        else if ($object->statut == 5)
	        {
	            //$newstatus=2;    // Ordered
	            // TODO Can we set it to submited ?
	            //$newstatus=3;  // Submited
	            // TODO If there is at least one reception, we can set to Received->Received partially
	            $newstatus=4;  // Received partially

	        }
	        else if ($object->statut == 6) $newstatus=2;	// Canceled->Approved
	        else if ($object->statut == 7) $newstatus=3;	// Canceled->Process running
	        else if ($object->statut == 9) $newstatus=1;	// Refused->Validated
            else $newstatus = 2;

            //print "old status = ".$object->statut.' new status = '.$newstatus;
	        $db->begin();

	        $result = $object->setStatus($user, $newstatus);
	        if ($result > 0)
	        {
	            // Currently the "Re-open" also remove the billed flag because there is no button "Set unpaid" yet.
		        $sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
	        	$sql.= ' SET billed = 0';
	        	$sql.= ' WHERE rowid = '.$object->id;

	        	$resql=$db->query($sql);

	            if ($newstatus == 0)
	        	{
		        	$sql = 'UPDATE '.MAIN_DB_PREFIX.'commande_fournisseur';
	        		$sql.= ' SET fk_user_approve = null, fk_user_approve2 = null, date_approve = null, date_approve2 = null';
	        		$sql.= ' WHERE rowid = '.$object->id;

	        		$resql=$db->query($sql);
	        	}

        		$db->commit();

	            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	            exit;
	        }
	        else
			{
				$db->rollback();

	            setEventMessages($object->error, $object->errors, 'errors');
	        }
	    }
	}

	/*
	 * Classify supplier order as billed
	 */
	if ($action == 'classifybilled' && $user->rights->fournisseur->commande->creer)
	{
		$ret=$object->classifyBilled($user);
		if ($ret < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Add a product line
	if ($action == 'addline' && $user->rights->fournisseur->commande->creer)
	{
	    $langs->load('errors');
	    $error = 0;

		// Set if we used free entry or predefined product
		$predef='';
		$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
		$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));
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
	        setEventMessages($langs->trans('ErrorBothFieldCantBeNegative', $langs->transnoentitiesnoconv('UnitPrice'), $langs->transnoentitiesnoconv('Qty')), null, 'errors');
	        $error++;
	    }
	    if (GETPOST('prod_entry_mode')=='free'  && ! GETPOST('idprodfournprice') && GETPOST('type') < 0)
	    {
	        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Type')), null, 'errors');
	        $error++;
	    }
	    if (GETPOST('prod_entry_mode')=='free' && GETPOST('price_ht')==='' && GETPOST('price_ttc')==='') // Unit price can be 0 but not ''
	    {
	        setEventMessages($langs->trans($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('UnitPrice'))), null, 'errors');
	        $error++;
	    }
	    if (GETPOST('prod_entry_mode')=='free' && ! GETPOST('dp_desc'))
	    {
	        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Description')), null, 'errors');
	        $error++;
	    }
	    if (! GETPOST('qty'))
	    {
	        setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('Qty')), null, 'errors');
	        $error++;
	    }

	    // Ecrase $pu par celui	du produit
	    // Ecrase $desc	par	celui du produit
	    // Ecrase $txtva  par celui du produit
	    if ((GETPOST('prod_entry_mode') != 'free') && empty($error))	// With combolist mode idprodfournprice is > 0 or -1. With autocomplete, idprodfournprice is > 0 or ''
	    {
	    	$productsupplier = new ProductFournisseur($db);

	    	if (empty($conf->global->SUPPLIER_ORDER_WITH_NOPRICEDEFINED))
	    	{
				$idprod=0;
				if (GETPOST('idprodfournprice') == -1 || GETPOST('idprodfournprice') == '') $idprod=-99;	// Same behaviour than with combolist. When not select idprodfournprice is now -99 (to avoid conflict with next action that may return -1, -2, ...)
			}

	    	if (GETPOST('idprodfournprice') > 0)
	    	{
	    		$idprod=$productsupplier->get_buyprice(GETPOST('idprodfournprice'), $qty);    // Just to see if a price exists for the quantity. Not used to found vat.
	    	}

	    	if ($idprod > 0)
	    	{
	    		$res=$productsupplier->fetch($idprod);

	    		$label = $productsupplier->label;

	    		$desc = $productsupplier->description;
	    		if (trim($product_desc) != trim($desc)) $desc = dol_concatdesc($desc, $product_desc);

	    		$type = $productsupplier->type;

	    		$tva_tx	= get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice'));
	    		$tva_npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, GETPOST('idprodfournprice'));
				if (empty($tva_tx)) $tva_npr=0;
	    		$localtax1_tx= get_localtax($tva_tx, 1, $mysoc, $object->thirdparty, $tva_npr);
	    		$localtax2_tx= get_localtax($tva_tx, 2, $mysoc, $object->thirdparty, $tva_npr);

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
	    			$tva_npr,
	    			'',
	    			$date_start,
	    			$date_end,
	    			$array_options,
				    $productsupplier->fk_unit
	    		);
	    	}
	    	if ($idprod == -99 || $idprod == 0)
	    	{
    			// Product not selected
    			$error++;
    			$langs->load("errors");
    			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")).' '.$langs->trans("or").' '.$langs->trans("NoPriceDefinedForThisSupplier"), null, 'errors');
	    	}
	    	if ($idprod == -1)
	    	{
	    		// Quantity too low
	    		$error++;
	    		$langs->load("errors");
	    		setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'errors');
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

			$fk_unit= GETPOST('units', 'alpha');

	    	$tva_tx = price2num($tva_tx);	// When vat is text input field

	    	// Local Taxes
	    	$localtax1_tx= get_localtax($tva_tx, 1, $mysoc, $object->thirdparty);
	    	$localtax2_tx= get_localtax($tva_tx, 2, $mysoc, $object->thirdparty);

	    	if (GETPOST('price_ht')!=='')
	    	{
	    		$price_base_type = 'HT';
	    		$ht = price2num(GETPOST('price_ht'));
	    		$ttc = 0;
	    	}
	    	else
	    	{
	    		$ttc = price2num(GETPOST('price_ttc'));
	    		$ht = $ttc / (1 + ($tva_tx / 100));
	    		$price_base_type = 'HT';
	    	}

			$result=$object->addline($desc, $ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', $remise_percent, $price_base_type, $ttc, $type,'','', $date_start, $date_end, $array_options, $fk_unit);
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
			unset($_POST['multicurrency_price_ht']);
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
            setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	/*
	 *	Updating a line in the order
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

	    $date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	    $date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

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
	    	$array_options,
		    $_POST['units']
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
		unset($_POST['units']);
	    unset($localtax1_tx);
	    unset($localtax2_tx);

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

	// Validate
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
	    if (empty($conf->global->SUPPLIER_ORDER_NO_DIRECT_APPROVE) && $user->rights->fournisseur->commande->approuver && ! (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $object->hasProductsOrServices(1)))
	    {
	        $action='confirm_approve';	// can make standard or first level approval also if permission is set
	    }
	}

	if (($action == 'confirm_approve' || $action == 'confirm_approve2') && $confirm == 'yes' && $user->rights->fournisseur->commande->approuver)
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
	    if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $qualified_for_stock_change)	// warning name of option should be STOCK_CALCULATE_ON_SUPPLIER_APPROVE_ORDER
	    {
	        if (! $idwarehouse || $idwarehouse == -1)
	        {
	            $error++;
	            setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
	            $action='';
	        }
	    }

	    if (! $error)
	    {
	        $result	= $object->approve($user, $idwarehouse, ($action=='confirm_approve2'?1:0));
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
	            setEventMessages($object->error, $object->errors, 'errors');
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
            setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	if ($action == 'confirm_commande' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander)
	{
	    $result = $object->commande($user, $_REQUEST["datecommande"],	$_REQUEST["methode"], $_REQUEST['comment']);
	    if ($result > 0)
	    {
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
		        $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	        }
            $action = '';
        }
	    else
	    {
            setEventMessages($object->error, $object->errors, 'errors');
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
            setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer)
	{
		if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
		{
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
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
		            setEventMessages($object->error, $object->errors, 'errors');
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

	        $result = $object->Livraison($user, $date_liv, GETPOST("type"), GETPOST("comment"));
	        if ($result > 0)
	        {
	            $langs->load("deliveries");
                setEventMessages($langs->trans("DeliveryStateSaved"), null);
                $action = '';
            }
	        else if($result == -3)
	        {
                setEventMessages($object->error, $object->errors, 'errors');
	        }
	        else
	        {
	            setEventMessages($object->error, $object->errors, 'errors');
	        }
	    }
	    else
	    {
		    setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Delivery")), null, 'errors');
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
            setEventMessages($object->error, $object->errors, 'errors');
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
	        setEventMessages($object->error, $object->errors, 'errors');
	        $action='';
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
	    if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	    else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
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
			// TODO le hook fait double emploi avec le trigger !!
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

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';


	/*
	 * Create an order
	 */
	if ($action == 'add' && $user->rights->fournisseur->commande->creer)
	{
	 	$error=0;

	    if ($socid <1)
	    {
		    setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')), null, 'errors');
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
			$object->multicurrency_code = GETPOST('multicurrency_code', 'alpha');
			$object->multicurrency_tx = GETPOST('originmulticurrency_tx', 'int');
			$object->fk_project       = GETPOST('projectid');

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
					if ($origin == 'order' || $origin == 'commande')
					{
                        $element = $subelement = 'commande';
                    }
                    else
                    {
						$element = 'supplier_proposal';
						$subelement = 'supplier_proposal';
					}

					$object->origin = $origin;
					$object->origin_id = $originid;

					// Possibility to add external linked objects with hooks
					$object->linked_objects [$object->origin] = $object->origin_id;
					$other_linked_objects = GETPOST('other_linked_objects', 'array');
					if (! empty($other_linked_objects)) {
						$object->linked_objects = array_merge($object->linked_objects, $other_linked_objects);
					}

					$id = $object->create($user);
					if ($id > 0)
					{
						dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

						$classname = 'SupplierProposal';
						$srcobject = new $classname($db);

						dol_syslog("Try to find source object origin=" . $object->origin . " originid=" . $object->origin_id . " to add lines");
						$result = $srcobject->fetch($object->origin_id);
						if ($result > 0)
						{
							$object->set_date_livraison($user, $srcobject->date_livraison);
							$object->set_id_projet($user, $srcobject->fk_project);

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

								$label = (! empty($lines[$i]->label) ? $lines[$i]->label : '');
								$desc = (! empty($lines[$i]->desc) ? $lines[$i]->desc : $lines[$i]->libelle);
								$product_type = (! empty($lines[$i]->product_type) ? $lines[$i]->product_type : 0);

								// Reset fk_parent_line for no child products and special product
								if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
									$fk_parent_line = 0;
								}

								// Extrafields
								if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) 							// For avoid conflicts if
								                                                                                                      // trigger used
								{
									$lines[$i]->fetch_optionals($lines[$i]->rowid);
									$array_option = $lines[$i]->array_options;
								}

								$result = $productsupplier->find_min_price_product_fournisseur($lines[$i]->fk_product, $lines[$i]->qty);
								if ($result>=0)
								{
								    $tva_tx = $lines[$i]->tva_tx;

								    if ($origin=="commande")
								    {
					                    $soc=new societe($db);
					                    $soc->fetch($socid);
					                    $tva_tx=get_default_tva($soc, $mysoc, $lines[$i]->fk_product, $productsupplier->product_fourn_price_id);
								    }

									$result = $object->addline(
										$desc,
										$lines[$i]->subprice,
										$lines[$i]->qty,
										$tva_tx,
										$lines[$i]->localtax1_tx,
										$lines[$i]->localtax2_tx,
										$lines[$i]->fk_product > 0 ? $lines[$i]->fk_product : 0,
										$productsupplier->product_fourn_price_id,
										$productsupplier->ref_supplier,
										$lines[$i]->remise_percent,
										'HT',
										0,
										$lines[$i]->product_type,
										'',
										'',
										null,
										null,
										array(),
										$lines[$i]->fk_unit
									);
								}

								if ($result < 0) {
									$error++;
									break;
								}

								// Defined the new fk_parent_line
								if ($result > 0 && $lines[$i]->product_type == 9) {
									$fk_parent_line = $result;
								}
							}

							// Add link between elements


							// Hooks
							$parameters = array('objFrom' => $srcobject);
							$reshook = $hookmanager->executeHooks('createFrom', $parameters, $object, $action); // Note that $action and $object may have been

							if ($reshook < 0)
								$error ++;
						} else {
			        		setEventMessages($srcobject->error, $srcobject->errors, 'errors');
							$error ++;
						}
					} else {
			        	setEventMessages($object->error, $object->errors, 'errors');
						$error ++;
					}
				}
				else
				{
		       		$id = $object->create($user);
		        	if ($id < 0)
		        	{
		        		$error++;
			        	setEventMessages($object->error, $object->errors, 'errors');
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
	 * Send mail
	 */

	// Actions to send emails
	$actiontypecode='AC_SUP_ORD';
	$trigger_name='ORDER_SUPPLIER_SENTBYMAIL';
	$paramname='id';
	$mode='emailfromsupplierorder';
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';


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
	        setEventMessages($langs->trans("WarningModuleNotActive",$langs->transnoentities("Module2650Name")), null, 'mesgs');
	    } else if (empty($ws_url) || empty($ws_key)) {
	        setEventMessages($langs->trans("ErrorWebServicesFieldsRequired"), null, 'errors');
	    } else if (empty($ws_user) || empty($ws_password) || empty($ws_thirdparty)) {
	        setEventMessages($langs->trans("ErrorFieldsRequired"),null, 'errors');
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
	            setEventMessages($langs->trans("SOAPError")." '".$soapclient_order->error_str."'", null, 'errors');
	        }
	        else if ($result_order["result"]["result_code"] != "OK") //Something went wrong
	        {
	            setEventMessages($langs->trans("SOAPError")." '".$result_order["result"]["result_code"]."' - '".$result_order["result"]["result_label"]."'", null, 'errors');
	        }
	        else
	        {
	            setEventMessages($langs->trans("RemoteOrderRef")." ".$result_order["ref"], null, 'mesgs');
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
					setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
				}
				else
				{
					setEventMessages($object->error, $object->errors, 'errors');
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
$help_url='EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('',$langs->trans("Order"),$help_url);

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
	print load_fiche_titre($langs->trans('NewOrder'));

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

		$element = 'supplier_proposal';
		$subelement = 'supplier_proposal';

		dol_include_once('/' . $element . '/class/' . $subelement . '.class.php');

		$classname = 'SupplierProposal';
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

		if (!empty($conf->multicurrency->enabled))
		{
			if (!empty($objectsrc->multicurrency_code)) $currency_code = $objectsrc->multicurrency_code;
			if (!empty($conf->global->MULTICURRENCY_USE_ORIGIN_TX) && !empty($objectsrc->multicurrency_tx))	$currency_tx = $objectsrc->multicurrency_tx;
		}

		$note_private = $object->getDefaultCreateValueFor('note_private', (! empty($objectsrc->note_private) ? $objectsrc->note_private : null));
		$note_public = $object->getDefaultCreateValueFor('note_public', (! empty($objectsrc->note_public) ? $objectsrc->note_public : null));

		// Object source contacts list
		$srccontactslist = $objectsrc->liste_contact(- 1, 'external', 1);

	}
	else
	{
		$cond_reglement_id 	= $societe->cond_reglement_supplier_id;
		$mode_reglement_id 	= $societe->mode_reglement_supplier_id;

		if (!empty($conf->multicurrency->enabled) && !empty($soc->multicurrency_code)) $currency_code = $soc->multicurrency_code;

		$note_private = $object->getDefaultCreateValueFor('note_private');
		$note_public = $object->getDefaultCreateValueFor('note_public');
	}

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="socid" value="' . $soc->id . '">' . "\n";
	print '<input type="hidden" name="remise_percent" value="' . $soc->remise_percent . '">';
	print '<input type="hidden" name="origin" value="' . $origin . '">';
	print '<input type="hidden" name="originid" value="' . $originid . '">';
	if (!empty($currency_tx)) print '<input type="hidden" name="originmulticurrency_tx" value="' . $currency_tx . '">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="titlefieldcreate">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

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
		print $form->select_company((empty($socid)?'':$socid), 'socid', 's.fournisseur = 1', 'SelectThirdParty');
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

	// Project
	if (! empty($conf->projet->enabled))
	{
		$formproject = new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>' . $langs->trans('Project') . '</td><td colspan="2">';
		$formproject->select_projects((empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS)?$societe->id:-1), $projectid, 'projectid', 0, 0, 1, 1);
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

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		print '<tr>';
		print '<td>'.fieldLabel('Currency','multicurrency_code').'</td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
	    print $form->selectMultiCurrency($currency_code, 'multicurrency_code');
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

		if (!empty($conf->multicurrency->enabled))
		{
			print '<tr><td>' . $langs->trans('MulticurrencyTotalHT') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_ht) . '</td></tr>';
			print '<tr><td>' . $langs->trans('MulticurrencyTotalVAT') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_tva) . '</td></tr>';
			print '<tr><td>' . $langs->trans('MulticurrencyTotalTTC') . '</td><td colspan="2">' . price($objectsrc->multicurrency_total_ttc) . '</td></tr>';
		}
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

    dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

	print "</form>\n";

	// Show origin lines
	if (! empty($origin) && ! empty($originid) && is_object($objectsrc))
	{
		$title = $langs->trans('ProductsAndServices');
		print load_fiche_titre($title);

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
		if (preg_match('/^[\(]?PROV/i',$object->ref) || empty($object->ref)) // empty should not happened, but when it occurs, the test save life
		{
		    $newref = $object->getNextNumRef($object->thirdparty);
		}
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
	 * Confirm approval
	 */
	if ($action	== 'approve' || $action	== 'approve2')
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

		$formconfirm = $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("ApproveThisOrder"), $text, "confirm_".$action, $formquestion, 1, 1, 240);
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
		$date_com = dol_mktime(GETPOST('rehour'),GETPOST('remin'),GETPOST('resec'),GETPOST("remonth"),GETPOST("reday"),GETPOST("reyear"));
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
	print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
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

	// Multicurrency
	if (! empty($conf->multicurrency->enabled))
	{
		// Multicurrency code
		print '<tr>';
		print '<td width="25%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print fieldLabel('Currency','multicurrency_code');
		print '</td>';
		if ($action != 'editmulticurrencycode' && ! empty($object->brouillon))
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmulticurrencycode&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="5">';
		if ($action == 'editmulticurrencycode') {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_code, 'multicurrency_code');
		} else {
			$form->form_multicurrency_code($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_code, 'none');
		}
		print '</td></tr>';

		// Multicurrency rate
		print '<tr>';
		print '<td width="25%">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print fieldLabel('CurrencyRate','multicurrency_tx');
		print '</td>';
		if ($action != 'editmulticurrencyrate' && ! empty($object->brouillon))
			print '<td align="right"><a href="' . $_SERVER["PHP_SELF"] . '?action=editmulticurrencyrate&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetMultiCurrencyCode'), 1) . '</a></td>';
		print '</tr></table>';
		print '</td><td colspan="5">';
		if ($action == 'editmulticurrencyrate') {
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'multicurrency_tx', $object->multicurrency_code);
		} else {
			$form->form_multicurrency_rate($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->multicurrency_tx, 'none', $object->multicurrency_code);
		}
		print '</td></tr>';
	}

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
		if ($object->hasDelay() && ! empty($object->date_livraison)) {
		    print ' '.img_picto($langs->trans("Late").' : '.$object->showDelay(), "warning");
		}
	}
	print '</td></tr>';


	// Delivery delay (in days)
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
			$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS)?$object->socid:-1), $object->fk_project, 'projectid', 0, 0, 1);
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

	// Total
	print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	print '<td colspan="2">'.price($object->total_ht,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	// Total VAT
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

	// Total TTC
	print '<tr><td>'.$langs->trans("AmountTTC").'</td><td colspan="2">'.price($object->total_ttc,'',$langs,1,-1,-1,$conf->currency).'</td>';
	print '</tr>';

	if (!empty($conf->multicurrency->enabled))
	{
		// Multicurrency Amount HT
		print '<tr><td height="10">' . fieldLabel('MulticurrencyAmountHT','multicurrency_total_ht') . '</td>';
		print '<td class="nowrap" colspan="2">' . price($object->multicurrency_total_ht, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		print '</tr>';

		// Multicurrency Amount VAT
		print '<tr><td height="10">' . fieldLabel('MulticurrencyAmountVAT','multicurrency_total_tva') . '</td>';
		print '<td class="nowrap" colspan="2">' . price($object->multicurrency_total_tva, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		print '</tr>';

		// Multicurrency Amount TTC
		print '<tr><td height="10">' . fieldLabel('MulticurrencyAmountTTC','multicurrency_total_ttc') . '</td>';
		print '<td class="nowrap" colspan="2">' . price($object->multicurrency_total_ttc, '', $langs, 0, - 1, - 1, (!empty($object->multicurrency_code) ? $object->multicurrency_code : $conf->currency)) . '</td>';
		print '</tr>';
	}

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
		$ret = $object->printObjectLines($action, $societe, $mysoc, $lineid, 1);

	$num = count($object->lines);

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
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
		$file=$fileparams['fullname'];

		// Define output language
		$outputlangs = $langs;
		$newlang = '';
		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id']))
			$newlang = $_REQUEST['lang_id'];
		if ($conf->global->MAIN_MULTILANGS && empty($newlang))
			$newlang = $object->thirdparty->default_lang;

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
			$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref, '/').'[^\-]+');
			$file=$fileparams['fullname'];
		}

		print '<div class="clearboth"></div>';
		print '<br>';
		print load_fiche_titre($langs->trans('SendOrderByMail'));

		dol_fiche_head('');

		// Cree l'objet formulaire mail
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		$formmail = new FormMail($db);
		$formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
		$formmail->fromtype = 'user';
		$formmail->fromid   = $user->id;
		$formmail->fromname = $user->getFullName($langs);
		$formmail->frommail = $user->email;
		$formmail->trackid='sor'.$object->id;
		if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
		{
			include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'sor'.$object->id);
		}
		$formmail->withfrom=1;
		$liste=array();
		foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key=>$value)	$liste[$key]=$value;
		$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
		$formmail->withtocc=$liste;
		$formmail->withtoccc=(! empty($conf->global->MAIN_EMAIL_USECCC)?$conf->global->MAIN_EMAIL_USECCC:false);
		$formmail->withtopic=$outputlangs->trans('SendOrderRef','__REF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		$object->fetch_projet();
		// Tableau des substitutions
		$formmail->setSubstitFromObject($object);
		$formmail->substit['__ORDERREF__']=$object->ref;                  	// For backward compatibility
		$formmail->substit['__ORDERSUPPLIERREF__']=$object->ref_supplier;	// For backward compatibility
		$formmail->substit['__SUPPLIERORDERREF__']=$object->ref_supplier;

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
		$formmail->param['models_id']=GETPOST('modelmailselected','int');
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

		dol_fiche_end();
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

        print load_fiche_titre($langs->trans('CreateRemoteOrder'),'');

        //Is everything filled?
        if (empty($ws_url) || empty($ws_key)) {
            setEventMessages($langs->trans("ErrorWebServicesFieldsRequired"), null, 'errors');
            $mode = "init";
            $error_occurred = true; //Don't allow to set the user/pass if thirdparty fields are not filled
        } else if ($mode != "init" && (empty($ws_user) || empty($ws_password))) {
            setEventMessages($langs->trans("ErrorFieldsRequired"), null, 'errors');
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
                    setEventMessages($langs->trans("RemoteUserMissingAssociatedSoc"), null, 'errors');
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
                            setEventMessages($line_id.$langs->trans("SOAPError")." ".$soapclient_product->error_str." - ".$soapclient_product->response, null, 'errors');
                            $error_occurred = true;
                            break;
                        }

                        // Check the result code
                        $status_code = $result_product["result"]["result_code"];
                        if (empty($status_code)) //No result, check error str
                        {
                            setEventMessages($langs->trans("SOAPError")." '".$soapclient_order->error_str."'", null, 'errors');
                        }
                        else if ($status_code != "OK") //Something went wrong
                        {
                            if ($status_code == "NOT_FOUND")
                            {
                                setEventMessages($line_id.$langs->trans("SupplierMissingRef")." '".$ref_supplier."'", null, 'warnings');
                            }
                            else
                            {
                                setEventMessages($line_id.$langs->trans("ResponseNonOK")." '".$status_code."' - '".$result_product["result"]["result_label"]."'", null, 'errors');
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
                            setEventMessages($line_id.$langs->trans("RemotePriceMismatch")." ".$supplier_price." - ".$local_price, null, 'warnings');
                        }

                        // Check if is in sale
                        if (empty($result_product["product"]["status_tosell"])) {
                            setEventMessages($line_id.$langs->trans("ProductStatusNotOnSellShort")." '".$ref_supplier."'", null, 'warnings');
                        }
                    }
                }

            }
            elseif ($user_status_code == "PERMISSION_DENIED")
            {
                setEventMessages($langs->trans("RemoteUserNotPermission"), null, 'errors');
                $error_occurred = true;
            }
            elseif ($user_status_code == "BAD_CREDENTIALS")
            {
                setEventMessages($langs->trans("RemoteUserBadCredentials"), null, 'errors');
                $error_occurred = true;
            }
            else
            {
                setEventMessages($langs->trans("ResponseNonOK")." '".$user_status_code."'", null, 'errors');
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

		if ($user->societe_id == 0 && $action != 'editline' && $action != 'delete')
		{
			print '<div	class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			// modified by hook
			if (empty($reshook))
			{

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
				// Create event
				if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD)) 	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/comm/action/card.php?action=create&amp;origin=' . $object->element . '&amp;originid=' . $object->id . '&amp;socid=' . $object->socid . '">' . $langs->trans("AddAction") . '</a></div>';
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
						if (! empty($conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL) && $conf->global->MAIN_FEATURES_LEVEL > 0 && $object->total_ht >= $conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL && ! empty($object->user_approve_id))
						{
							print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("FirstApprovalAlreadyDone")).'">'.$langs->trans("ApproveOrder").'</a>';
						}
						else
						{
							print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve">'.$langs->trans("ApproveOrder").'</a>';
						}
					}
					else
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("ApproveOrder").'</a>';
					}
				}

				// Second approval (if option SUPPLIER_ORDER_DOUBLE_APPROVAL is set)
				if (! empty($conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL) && $conf->global->MAIN_FEATURES_LEVEL > 0 && $object->total_ht >= $conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL)
				{
					if ($object->statut == 1)
					{
						if ($user->rights->fournisseur->commande->approve2)
						{
							if (! empty($object->user_approve_id2))
							{
								print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("SecondApprovalAlreadyDone")).'">'.$langs->trans("Approve2Order").'</a>';
							}
							else
							{
								print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=approve2">'.$langs->trans("Approve2Order").'</a>';
							}
						}
						else
						{
							print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("Approve2Order").'</a>';
						}
					}
				}

				// Refuse
				if ($object->statut == 1)
				{
					if ($user->rights->fournisseur->commande->approuver || $user->rights->fournisseur->commande->approve2)
					{
						print '<a class="butAction"	href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=refuse">'.$langs->trans("RefuseOrder").'</a>';
					}
					else
					{
						print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans("RefuseOrder").'</a>';
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
				    $buttonshown=0;
				    if (! $buttonshown && $user->rights->fournisseur->commande->approuver)
				    {
				        if (empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY)
				            || (! empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER_ONLY) && $user->id == $object->user_approve_id))
				        {
				            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Disapprove").'</a>';
				            $buttonshown++;
				        }
				    }
				    if (! $buttonshown && $user->rights->fournisseur->commande->approve2 && ! empty($conf->global->SUPPLIER_ORDER_DOUBLE_APPROVAL))
				    {
				        if (empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY)
				            || (! empty($conf->global->SUPPLIER_ORDER_REOPEN_BY_APPROVER2_ONLY) && $user->id == $object->user_approve_id2))
				        {
				            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("Disapprove").'</a>';
				        }
				    }
				}
				if (in_array($object->statut, array(3, 4, 5, 6, 7, 9)))
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
					}
				}

				// Ship
				if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_DISPATCH_ORDER))
				{
					if (in_array($object->statut, array(3,4))) {
						if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->receptionner) {
							print '<div class="inline-block divButAction"><a class="butAction" href="' . DOL_URL_ROOT . '/fourn/commande/dispatch.php?id=' . $object->id . '">' . $langs->trans('OrderDispatch') . '</a></div>';
						} else {
							print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="' . dol_escape_htmltag($langs->trans("NotAllowed")) . '">' . $langs->trans('OrderDispatch') . '</a></div>';
						}
					}
				}

				// Create bill
				if (! empty($conf->facture->enabled))
				{
					if (! empty($conf->fournisseur->enabled) && ($object->statut >= 2 && $object->billed != 1))  // 2 means accepted
					{
						if ($user->rights->fournisseur->facture->creer)
						{
							print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
						}

						if ($user->rights->fournisseur->commande->creer && $object->statut >= 2 && !empty($object->linkedObjectsIds['invoice_supplier']))
						{
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
						}
					}

				}

				// Create a remote order using WebService only if module is activated
				if (! empty($conf->syncsupplierwebservices->enabled) && $object->statut >= 2) // 2 means accepted
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=webservice&amp;mode=init">'.$langs->trans('CreateRemoteOrder').'</a>';
				}

				// Clone
				if ($user->rights->fournisseur->commande->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a>';
				}

				// Cancel
				if ($object->statut == 2)
				{
					if ($user->rights->fournisseur->commande->commander)
					{
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=cancel">'.$langs->trans("CancelOrder").'</a>';
					}
				}

				// Delete
				if ($user->rights->fournisseur->commande->supprimer)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
				}

			}

			print "</div>";
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

		// Linked object block
		$somethingshown = $form->showLinkedObjectBlock($object);

		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object);
		//if ($linktoelem) print '<br>'.$linktoelem;


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';


		if ($user->rights->fournisseur->commande->commander && $object->statut == 2)
		{
			/*
			 * Commander (action=commande)
			 */
			print '<!-- form to record supplier order -->'."\n";
			print '<form name="commande" action="card.php?id='.$object->id.'&amp;action=commande" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden"	name="action" value="commande">';
			print load_fiche_titre($langs->trans("ToOrder"),'','');
			print '<table class="border" width="100%">';
			//print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("ToOrder").'</td></tr>';
			print '<tr><td>'.$langs->trans("OrderDate").'</td><td>';
			$date_com = dol_mktime(0, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
			print $form->select_date($date_com,'',1,1,'',"commande",1,0,1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("OrderMode").'</td><td>';
			$formorder->selectInputMethod(GETPOST('methodecommande'), "methodecommande", 1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("Comment").'</td><td><input size="40" type="text" name="comment" value="'.GETPOST('comment').'"></td></tr>';
			print '<tr><td align="center" colspan="2"><input type="submit" class="button" value="'.$langs->trans("ToOrder").'"></td></tr>';
			print '</table>';
			print '</form>';
			print "<br>";
		}

		if ($user->rights->fournisseur->commande->receptionner	&& ($object->statut == 3 || $object->statut == 4))
		{
			/*
			 * Receptionner (action=livraison)
			 */
			print '<!-- form to record supplier order received -->'."\n";
			print '<form action="card.php?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden"	name="action" value="livraison">';
			print load_fiche_titre($langs->trans("Receive"),'','');
			print '<table class="border" width="100%">';
			//print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Receive").'</td></tr>';
			print '<tr><td>'.$langs->trans("DeliveryDate").'</td><td>';
			print $form->select_date('','',1,1,'',"commande",1,0,1);
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
			print "<br>";
		}

        // List of actions on element
        include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
        $formactions=new FormActions($db);
        $somethingshown=$formactions->showactions($object,'order_supplier',$socid,0,'listaction'.($genallowed?'largetitle':''));


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
