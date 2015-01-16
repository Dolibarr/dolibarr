<?php
/* Copyright (C) 2004-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric	Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2013 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2013      Florian Henry        <florian.henry@open-concept.pro>
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
 *	\file		htdocs/fourn/commande/fiche.php
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
if (!empty($conf->produit->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (!empty($conf->projet->enabled))
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');
$langs->load('stocks');

$id 			= GETPOST('id','int');
$ref 			= GETPOST('ref','alpha');
$action 		= GETPOST('action','alpha');
$confirm		= GETPOST('confirm','alpha');
$comclientid 	= GETPOST('comid','int');
$socid			= GETPOST('socid','int');
$projectid		= GETPOST('projectid','int');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));


// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, '', 'commande');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('ordersuppliercard'));

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


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once

if ($action == 'setref_supplier' && $user->rights->fournisseur->commande->creer)
{
    $result=$object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
    if ($result < 0) dol_print_error($db, $object->error);
}

// conditions de reglement
if ($action == 'setconditions' && $user->rights->fournisseur->commande->creer)
{
    $result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
}

// mode de reglement
else if ($action == 'setmode' && $user->rights->fournisseur->commande->creer)
{
    $result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
}

// date de livraison
if ($action == 'setdate_livraison' && $user->rights->fournisseur->commande->creer)
{
	$datelivraison=dol_mktime(0, 0, 0, GETPOST('liv_month','int'), GETPOST('liv_day','int'),GETPOST('liv_year','int'));

	$result=$object->set_date_livraison($user,$datelivraison);
	if ($result < 0)
	{
		setEventMessage($object->error, 'errors');
	}
}

// Set project
else if ($action ==	'classin' && $user->rights->fournisseur->commande->creer)
{
    $object->setProject($projectid);
}

else if ($action == 'setremisepercent' && $user->rights->fournisseur->commande->creer)
{
    $result = $object->set_remise($user, $_POST['remise_percent']);
}

else if ($action == 'reopen' && $user->rights->fournisseur->commande->approuver)
{
    if (in_array($object->statut, array(1, 5, 6, 7, 9)))
    {
        if ($object->statut == 1) $newstatus=0;	// Validated->Draft
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
else if ($action == 'addline' && $user->rights->fournisseur->commande->creer)
{
    $langs->load('errors');
    $error = 0;

	// Set if we used free entry or predefined product
	$predef='';
	$product_desc=(GETPOST('dp_desc')?GETPOST('dp_desc'):'');
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

    	if (GETPOST('idprodfournprice') == -1 || GETPOST('idprodfournprice') == '') $idprod=-2;	// Same behaviour than with combolist. When not select idprodfournprice is now -2 (to avoid conflict with next action that may return -1)

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
    			$type
    		);
    	}
    	if ($idprod == -2 || $idprod == 0)
    	{
    		// Product not selected
    		$error++;
    		$langs->load("errors");
    		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ProductOrService")), 'errors');
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

    	if (!empty($_POST['price_ht']))
    	{
    		$price_base_type = 'HT';
    		$ht = price2num($_POST['price_ht']);
    		$result=$object->addline($desc, $ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', $remise_percent, $price_base_type, 0, $type);
    	}
    	else
    	{
    		$ttc = price2num($_POST['price_ttc']);
    		$ht = $ttc / (1 + ($tauxtva / 100));
    		$price_base_type = 'HT';
    		$result=$object->addline($desc, $ht, $qty, $tva_tx, $localtax1_tx, $localtax2_tx, 0, 0, '', $remise_percent, $price_base_type, $ttc, $type);
    	}
	}

    //print "xx".$tva_tx; exit;
    if (! $error && $result > 0)
    {
    	$ret=$object->fetch($object->id);    // Reload to get new records

    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    	{
    		// Define output language
    		$outputlangs = $langs;
    		$newlang=GETPOST('lang_id','alpha');
    		if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    		if (! empty($newlang))
    		{
    			$outputlangs = new Translate("",$conf);
    			$outputlangs->setDefaultLang($newlang);
    		}

    		supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
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
else if ($action == 'update_line' && $user->rights->fournisseur->commande->creer &&	! GETPOST('cancel'))
{
    if ($_POST["elrowid"])
    {
        $line = new CommandeFournisseurLigne($db);
        $res = $line->fetch($_POST["elrowid"]);
        if (!$res) dol_print_error($db);
    }

    $localtax1_tx=get_localtax($_POST['tva_tx'],1,$mysoc,$object->thirdparty);
    $localtax2_tx=get_localtax($_POST['tva_tx'],2,$mysoc,$object->thirdparty);

    $result	= $object->updateline(
        $_POST['elrowid'],
        $_POST['eldesc'],
        $_POST['pu'],
        $_POST['qty'],
        $_POST['remise_percent'],
        $_POST['tva_tx'],
        $localtax1_tx,
        $localtax2_tx,
        'HT',
        0,
        isset($_POST["type"])?$_POST["type"]:$line->product_type
    );
    unset($_POST['qty']);
    unset($_POST['type']);
    unset($_POST['idprodfournprice']);
    unset($_POST['remmise_percent']);
    unset($_POST['dp_desc']);
    unset($_POST['np_desc']);
    unset($_POST['pu']);
    unset($_POST['tva_tx']);
    unset($localtax1_tx);
    unset($localtax2_tx);
    if ($result	>= 0)
    {
        $outputlangs = $langs;
        if (GETPOST('lang_id'))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang(GETPOST('lang_id'));
        }
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
        {
            $ret=$object->fetch($object->id);    // Reload to get new records
            supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        }
    }
    else
    {
        dol_print_error($db,$object->error);
        exit;
    }
}

else if ($action == 'confirm_deleteproductline' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer)
{

    $result = $object->deleteline(GETPOST('lineid'));
    if ($result	>= 0)
    {
        $outputlangs = $langs;
        if (GETPOST('lang_id'))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang(GETPOST('lang_id'));
        }
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
        {
            $ret=$object->fetch($object->id);    // Reload to get new records
            supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        }
    }
    else
    {
        $error++;
        setEventMessage($object->error, 'errors');
    }

    if (! $error)
    {
        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
        exit;
    }
}

else if ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->fournisseur->commande->valider)
{
    $object->date_commande=dol_now();
    $result = $object->valid($user);
    if ($result	>= 0)
    {
        $outputlangs = $langs;
        if (GETPOST('lang_id'))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang(GETPOST('lang_id'));
        }
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
        {
            $ret=$object->fetch($object->id);    // Reload to get new records
            supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        }
    }
    else
    {
        setEventMessage($object->error, 'errors');
    }

    // If we have permission, and if we don't need to provide the idwarehouse, we go directly on approved step
    if ($user->rights->fournisseur->commande->approuver && ! (! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_VALIDATE_ORDER) && $object->hasProductsOrServices(1)))
    {
        $action='confirm_approve';
    }
}

else if ($action == 'confirm_approve' && $confirm == 'yes' && $user->rights->fournisseur->commande->approuver)
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
            $outputlangs = $langs;
            if (GETPOST('lang_id'))
            {
                $outputlangs = new Translate("",$conf);
                $outputlangs->setDefaultLang(GETPOST('lang_id'));
            }
            
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
                supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
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

else if ($action == 'confirm_refuse' &&	$confirm == 'yes' && $user->rights->fournisseur->commande->approuver)
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

else if ($action == 'confirm_commande' && $confirm	== 'yes' &&	$user->rights->fournisseur->commande->commander)
{
    $result	= $object->commande($user, $_REQUEST["datecommande"],	$_REQUEST["methode"], $_REQUEST['comment']);
    if ($result > 0)
    {
        $outputlangs = $langs;
        if (GETPOST('lang_id'))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang(GETPOST('lang_id'));
        }
        
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
            supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        }
        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id);
        exit;
    }
    else
    {
        setEventMessage($object->error, 'errors');
    }
}


else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->commande->supprimer)
{
    $result=$object->delete($user);
    if ($result > 0)
    {
        header("Location: ".DOL_URL_ROOT.'/fourn/commande/liste.php');
        exit;
    }
    else
    {
        setEventMessage($object->error, 'errors');
    }
}

// Action clone object
else if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->fournisseur->commande->creer)
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

// Receive
else if ($action == 'livraison' && $user->rights->fournisseur->commande->receptionner)
{

    if ($_POST["type"])
    {
        $date_liv = dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

        $result	= $object->Livraison($user, $date_liv, $_POST["type"], $_POST["comment"]);
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
            dol_print_error($db,$object->error);
            exit;
        }
    }
    else
    {
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("Delivery")).'</div>';
    }
}

else if ($action == 'confirm_cancel' && $confirm == 'yes' &&	$user->rights->fournisseur->commande->commander)
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

// Line ordering
else if ($action == 'up'	&& $user->rights->fournisseur->commande->creer)
{
    $object->line_up($_GET['rowid']);

    $outputlangs = $langs;
    if (! empty($_REQUEST['lang_id']))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
    header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#'.$_GET['rowid']));
    exit;
}
else if ($action == 'down' && $user->rights->fournisseur->commande->creer)
{
    $object->line_down($_GET['rowid']);

    $outputlangs = $langs;
    if (! empty($_REQUEST['lang_id']))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) supplier_order_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
    header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#'.$_GET['rowid']));
    exit;
}

else if ($action == 'builddoc' && $user->rights->fournisseur->commande->creer)	// En get ou en	post
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
    $result=supplier_order_pdf_create($db, $object,$object->modelpdf,$outputlangs, $hidedetails, $hidedesc, $hideref);
    if ($result	<= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
}

// Delete file in doc form
else if ($action == 'remove_file' && $object->id > 0 && $user->rights->fournisseur->commande->creer)
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    $langs->load("other");
    $upload_dir =	$conf->fournisseur->commande->dir_output;
    $file =	$upload_dir	. '/' .	GETPOST('file');
    $ret=dol_delete_file($file,0,0,0,$object);
    if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
    else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
}

elseif ($action == 'update_extras')
{
	// Fill array 'array_options' with data from add form
	$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	if($ret < 0) $error++;

	if (!$error)
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
else if ($action == 'add' && $user->rights->fournisseur->commande->creer)
{
 	$error=0;

    if ($socid <1)
    {
    	$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')).'</div>';
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
        $object->note_private	= GETPOST('note_private');
        $object->note_public   	= GETPOST('note_public');

		// Fill array 'array_options' with data from add form
        $ret = $extrafields->setOptionalsFromPost($extralabels,$object);

        $id = $object->create($user);
		if ($id < 0)
		{
			$error++;
		}

        if ($error)
        {
            $langs->load("errors");
            $db->rollback();
            $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
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
                    $actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto.".\n";
                    if ($message)
                    {
                        $actionmsg.=$langs->transnoentities('MailTopic').": ".$subject."\n";
                        $actionmsg.=$langs->transnoentities('TextUsedInTheMessageBody').":\n";
                        $actionmsg.=$message;
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
if ($action=="create")
{
	print_fiche_titre($langs->trans('NewOrder'));

	dol_htmloutput_mesg($mesg);
	dol_htmloutput_events();

	$societe='';
	if ($socid>0)
	{
		$societe=new Societe($db);
		$societe->fetch($socid);
	}

	$cond_reglement_id 	= $societe->cond_reglement_supplier_id;
	$mode_reglement_id 	= $societe->mode_reglement_supplier_id;

	print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
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

	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_public', GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td>';
	//print '<textarea name="note_public" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea>';
	print '</tr>';

	print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
	print '<td>';
	$doleditor = new DolEditor('note_private', GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
	print $doleditor->Create(1);
	print '</td>';
	//print '<td><textarea name="note_private" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
	print '</tr>';



	// Other options
    $parameters=array();
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

	if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
		print $object->showOptionals($extrafields,'edit');
    }

	// Bouton "Create Draft"
    print "</table>\n";

	print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></center>';

	print "</form>\n";
}
elseif (! empty($object->id))
{
	$author	= new User($db);
	$author->fetch($object->user_author_id);

    $societe = new Fournisseur($db);
    $result=$societe->fetch($object->socid);
    if ($result < 0) dol_print_error($db);

	$head = ordersupplier_prepare_head($object);

	$title=$langs->trans("SupplierOrder");
	dol_fiche_head($head, 'card', $title, 0, 'order');

	$res=$object->fetch_optionals($object->id,$extralabels);

	/*
	 * Confirmation de la suppression de la commande
	 */
	if ($action	== 'delete')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 2);

	}

	// Clone confirmation
	if ($action == 'clone')
	{
		// Create an array for form
		$formquestion=array(
				//array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1)
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneOrder'),$langs->trans('ConfirmCloneOrder',$object->ref),'confirm_clone',$formquestion,'yes',1);

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

		$text=$langs->trans('ConfirmValidateOrder',$newref);
		if (! empty($conf->notification->enabled))
		{
			require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
			$notify=new	Notify($db);
			$text.='<br>';
			$text.=$notify->confirmMessage('ORDER_SUPPLIER_APPROVE', $object->socid);
		}

		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_valid', '', 0, 1);

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

		print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("ApproveThisOrder"),$langs->trans("ConfirmApproveThisOrder",$object->ref),"confirm_approve", $formquestion, 1, 1, 240);

	}

	/*
	 * Confirmation de la desapprobation
	 */
	if ($action	== 'refuse')
	{
		print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id",$langs->trans("DenyingThisOrder"),$langs->trans("ConfirmDenyingThisOrder",$object->ref),"confirm_refuse", '', 0, 1);

	}

	/*
	 * Confirmation de l'annulation
	 */
	if ($action	== 'cancel')
	{
		print $form->formconfirm($_SERVER['PHP_SELF']."?id=$object->id",$langs->trans("Cancel"),$langs->trans("ConfirmCancelThisOrder",$object->ref),"confirm_cancel", '', 0, 1);

	}

	/*
	 * Confirmation de l'envoi de la commande
	 */
	if ($action	== 'commande')
	{
		$date_com = dol_mktime(0,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);
		print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id."&datecommande=".$date_com."&methode=".$_POST["methodecommande"]."&comment=".urlencode($_POST["comment"]), $langs->trans("MakeOrder"),$langs->trans("ConfirmMakeOrder",dol_print_date($date_com,'day')),"confirm_commande",'',0,2);

	}

	/*
	 * Confirmation de la suppression d'une ligne produit
	 */
	if ($action == 'delete_product_line')
	{
		print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline','',0,2);

	}

	/*
	 *	Commande
	*/
	$nbrow=8;
	if (! empty($conf->projet->enabled))	$nbrow++;

	//Local taxes
	//TODO: Place into a function to control showing by country or study better option
	if ($mysoc->country_code=='ES')
	{
		if($mysoc->localtax1_assuj=="1") $nbrow++;
		if($object->thirdparty->localtax2_assuj=="1") $nbrow++;
	}
	else
	{
		if($mysoc->localtax1_assuj=="1") $nbrow++;
		if($mysoc->localtax2_assuj=="1") $nbrow++;
	}
	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/liste.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

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

	// Delivery date planed
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
		print '<input type="hidden" name="action" value="setdate_livraison">';
		$form->select_date($object->date_livraison?$object->date_livraison:-1,'liv_','','','',"setdate_livraison");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print $object->date_livraison ? dol_print_date($object->date_livraison,'daytext') : '&nbsp;';
	}
	print '</td>';

	// Project
	if (! empty($conf->projet->enabled))
	{
		$langs->load('projects');
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project');
		print '</td>';
		if ($action != 'classify') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		//print "$object->id, $object->socid, $object->fk_project";
		if ($action == 'classify')
		{
			$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS)?$object->socid:'-1', $object->fk_project, 'projectid');
		}
		else
		{
			$form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none');
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	$parameters=array('socid'=>$socid, 'colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		if ($action == 'edit_extras')
		{
			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';
			print '<input type="hidden" name="action" value="update_extras">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		foreach($extrafields->attribute_label as $key=>$label)
		{
			if ($action == 'edit_extras') {
				$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
			} else {
				$value=$object->array_options["options_".$key];
			}

			if ($extrafields->attribute_type[$key] == 'separate')
			{
				print $extrafields->showSeparator($key);
			}
			else
			{
				print '<tr><td';
				if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
				print '>'.$label.'</td><td colspan="5">';
				// Convert date into timestamp format
				if (in_array($extrafields->attribute_type[$key],array('date','datetime')))
				{
					$value = isset($_POST["options_".$key])?dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]):$db->jdate($object->array_options['options_'.$key]);
				}

				if ($action == 'edit_extras' && $user->rights->fournisseur->commande->creer)
				{
		  			print $extrafields->showInputField($key,$value);
				}
				else
				{
		  			print $extrafields->showOutputField($key,$value);
				}

				print '</td></tr>'."\n";
		  	}
		}

		if(count($extrafields->attribute_label) > 0)
		{
			if ($action == 'edit_extras' && $user->rights->fournisseur->commande->creer)
			{
				print '<tr><td></td><td colspan="5">';
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
				print '</td></tr>';
			}
			else
			{
				if ($object->statut == 0 && $user->rights->fournisseur->commande->creer)
				{
					print '<tr><td></td><td><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit_extras">'.img_picto('','edit').' '.$langs->trans('Modify').'</a></td></tr>';
				}
			}
		}
	}

	// Ligne de	3 colonnes
	print '<tr><td>'.$langs->trans("AmountHT").'</td>';
	print '<td align="right"><b>'.price($object->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	print '<tr><td>'.$langs->trans("AmountVAT").'</td><td align="right">'.price($object->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Amount Local Taxes
	//TODO: Place into a function to control showing by country or study better option
	if ($mysoc->country_code=='ES')
	{
		if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
		{
			print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
			print '<td align="right">'.price($object->total_localtax1).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
		if ($object->thirdparty->localtax2_assuj=="1") //Localtax2 IRPF
		{
			print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
			print '<td align="right">'.price($object->total_localtax2).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
	}
	else
	{
		if ($mysoc->localtax1_assuj=="1") //Localtax1
		{
			print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
			print '<td align="right">'.price($object->total_localtax1).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
		if ($mysoc->localtax2_assuj=="1") //Localtax2
		{
			print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
			print '<td align="right">'.price($object->total_localtax2).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
	}
	print '<tr><td>'.$langs->trans("AmountTTC").'</td><td align="right">'.price($object->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

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


	print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?etat=1&id='.$object->id.(($action != 'edit_line')?'#add':'#line_'.GETPOST('lineid')).'" method="POST">
	<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
	<input type="hidden" name="action" value="'.(($action != 'edit_line')?'addline':'update_line').'">
	<input type="hidden" name="mode" value="">
	<input type="hidden" name="id" value="'.$object->id.'">
    <input type="hidden" name="facid" value="'.$object->id.'">
    <input type="hidden" name="socid" value="'.$societe->id.'">
	';


	print '<table id="tablelines" class="noborder noshadow" width="100%">';

	$num = count($object->lines);
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
		if ($action != 'edit_line' || $_GET['rowid'] != $line->id)
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
				print '<td align="center" width="16"><a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit_line&amp;rowid='.$line->id.'#'.$line->id.'">';
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
		if ($action	== 'edit_line' && $user->rights->fournisseur->commande->creer && ($_GET["rowid"] == $line->id))
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

	// Form to add new line
	if ($object->statut == 0 && $user->rights->fournisseur->commande->creer && $action != 'edit_line')
	{
		// Add free products/services form
		global $forceall, $senderissupplier, $dateSelector;
		$forceall=1; $senderissupplier=1; $dateSelector=0;

		$var = true;

		// Add free products/services
		$object->formAddObjectLine(1, $societe, $mysoc);

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formAddObjectLine', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

	}
	print '</table>';

	print '</form>';

	dol_fiche_end();


	if ($action != 'presend')
	{
		/**
		 * Boutons actions
		 */
		if ($user->societe_id == 0 && $action != 'edit_line' && $action != 'delete')
		{
			print '<div	 class="tabsAction">';

			// Validate
			if ($object->statut == 0 && $num > 0)
			{
				if ($user->rights->fournisseur->commande->valider)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid"';
					print '>'.$langs->trans('Validate').'</a>';
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
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/fourn/facture/fiche.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
				}

				//if ($user->rights->fournisseur->commande->creer && $object->statut > 2)
				//{
				//	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
				//}
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
			print '<form name="commande" action="fiche.php?id='.$object->id.'&amp;action=commande" method="post">';
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
			print '<form action="fiche.php?id='.$object->id.'" method="post">';
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

	/*
	 * Action presend
	 */
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->fournisseur->commande->dir_output . '/' . $ref, preg_quote($ref,'/'));
		$file=$fileparams['fullname'];

		// Build document if it not exists
		if (! $file || ! is_readable($file))
		{
			// Define output language
			$outputlangs = $langs;
			$newlang='';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) $newlang=$_REQUEST['lang_id'];
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}

			$result=supplier_order_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
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
		$formmail->withtopic=$langs->trans('SendOrderRef','__ORDERREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;
		// Tableau des substitutions
		$formmail->substit['__ORDERREF__']=$object->ref;
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

	print '</td></tr></table>';
}

// End of page
llxFooter();

$db->close();
