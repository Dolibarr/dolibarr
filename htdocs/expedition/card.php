<?php
/* Copyright (C) 2003-2008	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Simon TOSSER			<simon@kornog-computing.com>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2017	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2013       Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2014		Cedric GROSS			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2017	Francis Appels			<francis.appels@yahoo.com>
 * Copyright (C) 2015		Claudio Aschieri		<c.aschieri@19.coop>
 * Copyright (C) 2016		Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2016		Yasser Carreón			<yacasia@gmail.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/expedition/card.php
 *	\ingroup    expedition
 *	\brief      Card of a shipment
 */

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
if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))  require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (! empty($conf->propal->enabled))   require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->productbatch->enabled)) require_once DOL_DOCUMENT_ROOT.'/product/class/productbatch.class.php';
if (! empty($conf->projet->enabled)) {
    require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->loadLangs(array("sendings","companies","bills",'deliveries','orders','stocks','other','propal'));

if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');
if (! empty($conf->productbatch->enabled)) $langs->load('productbatch');

$origin		= GETPOST('origin','alpha')?GETPOST('origin','alpha'):'expedition';   // Example: commande, propal
$origin_id 	= GETPOST('id','int')?GETPOST('id','int'):'';
$id = $origin_id;
if (empty($origin_id)) $origin_id  = GETPOST('origin_id','int');    // Id of order or propal
if (empty($origin_id)) $origin_id  = GETPOST('object_id','int');    // Id of order or propal
$ref=GETPOST('ref','alpha');
$line_id = GETPOST('lineid','int')?GETPOST('lineid','int'):'';

// Security check
$socid='';
if ($user->societe_id) $socid=$user->societe_id;

if ($origin == 'expedition') $result=restrictedArea($user, $origin, $id);
else {
	$result=restrictedArea($user, 'expedition');
	if (empty($user->rights->{$origin}->lire) && empty($user->rights->{$origin}->read)) accessforbidden();
}

$action		= GETPOST('action','alpha');
$confirm	= GETPOST('confirm','alpha');
$cancel     = GETPOST('cancel','alpha');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

$object = new Expedition($db);
$extrafields = new ExtraFields($db);
$extrafieldsline = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// fetch optionals attributes lines and labels
$extralabelslines=$extrafieldsline->fetch_name_optionals_label($object->table_element_line);


// Load object. Make an object->fetch
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('expeditioncard','globalcard'));

$permissiondellink=$user->rights->expedition->livraison->creer;	// Used by the include of actions_dellink.inc.php
//var_dump($object->lines[0]->detail_batch);


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($cancel)
	{
		$action = '';
		$object->fetch($id); // show shipment also after canceling modification
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

	// Reopen
	if ($action == 'reopen' && $user->rights->expedition->creer)
	{
	    $object->fetch($id);
	    $result = $object->reOpen();
	}

	// Set incoterm
	if ($action == 'set_incoterms' && !empty($conf->incoterm->enabled))
	{
	    $result = $object->setIncoterms(GETPOST('incoterm_id', 'int'), GETPOST('location_incoterms', 'alpha'));
	}

	if ($action == 'setref_customer')
	{
        $result = $object->fetch($id);
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
        }

        $result = $object->setValueFrom('ref_customer', GETPOST('ref_customer','alpha'), '', null, 'text', '', $user, 'SHIPMENT_MODIFY');
        if ($result < 0) {
            setEventMessages($object->error, $object->errors, 'errors');
            $action = 'editref_customer';
        } else {
            header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
            exit;
        }
	}

	if ($action == 'update_extras')
	{
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
	    $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
	    $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute','none'));
	    if ($ret < 0) $error++;

	    if (! $error)
	    {
	        // Actions on extra fields (by external module or standard code)
	        // TODO le hook fait double emploi avec le trigger !!
	        $hookmanager->initHooks(array('expeditiondao'));
	        $parameters = array('id' => $object->id);
	        $reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	        if (empty($reshook)) {
	            $result = $object->insertExtraFields('SHIPMENT_MODIFY');
       			if ($result < 0)
				{
					setEventMessages($object->error, $object->errors, 'errors');
					$error++;
				}
	        } else if ($reshook < 0)
	            $error++;
	    }

	    if ($error)
	        $action = 'edit_extras';
	}

	// Create shipment
	if ($action == 'add' && $user->rights->expedition->creer)
	{
	    $error=0;
	    $predef='';

	    $db->begin();

	    $object->note				= GETPOST('note','alpha');
	    $object->origin				= $origin;
        $object->origin_id			= $origin_id;
        $object->fk_project         = GETPOST('projectid','int');
	    $object->weight				= GETPOST('weight','int')==''?"NULL":GETPOST('weight','int');
	    $object->sizeH				= GETPOST('sizeH','int')==''?"NULL":GETPOST('sizeH','int');
	    $object->sizeW				= GETPOST('sizeW','int')==''?"NULL":GETPOST('sizeW','int');
	    $object->sizeS				= GETPOST('sizeS','int')==''?"NULL":GETPOST('sizeS','int');
	    $object->size_units			= GETPOST('size_units','int');
	    $object->weight_units		= GETPOST('weight_units','int');

	    $date_delivery = dol_mktime(GETPOST('date_deliveryhour','int'), GETPOST('date_deliverymin','int'), 0, GETPOST('date_deliverymonth','int'), GETPOST('date_deliveryday','int'), GETPOST('date_deliveryyear','int'));

	    // On va boucler sur chaque ligne du document d'origine pour completer objet expedition
	    // avec info diverses + qte a livrer
	    $classname = ucfirst($object->origin);
	    $objectsrc = new $classname($db);
	    $objectsrc->fetch($object->origin_id);

	    $object->socid					= $objectsrc->socid;
	    $object->ref_customer			= GETPOST('ref_customer','alpha');
	    $object->model_pdf				= GETPOST('model');
	    $object->date_delivery			= $date_delivery;	    // Date delivery planed
	    $object->fk_delivery_address	= $objectsrc->fk_delivery_address;
	    $object->shipping_method_id		= GETPOST('shipping_method_id','int');
	    $object->tracking_number		= GETPOST('tracking_number','alpha');
	    $object->ref_int				= GETPOST('ref_int','alpha');
	    $object->note_private			= GETPOST('note_private','none');
	    $object->note_public			= GETPOST('note_public','none');
		$object->fk_incoterms 			= GETPOST('incoterm_id', 'int');
		$object->location_incoterms 	= GETPOST('location_incoterms', 'alpha');

	    $batch_line = array();
		$stockLine = array();
		$array_options=array();

	    $num=count($objectsrc->lines);
	    $totalqty=0;

	    for ($i = 0; $i < $num; $i++)
	    {
			$idl="idl".$i;

			$sub_qty=array();
			$subtotalqty=0;

			$j=0;
			$batch="batchl".$i."_0";
			$stockLocation="ent1".$i."_0";
	    	$qty = "qtyl".$i;

			if ($objectsrc->lines[$i]->product_tobatch)      // If product need a batch number
			{
			    if (isset($_POST[$batch]))
			    {
    				//shipment line with batch-enable product
    				$qty .= '_'.$j;
    				while (isset($_POST[$batch]))
    				{
    					// save line of detail into sub_qty
    				    $sub_qty[$j]['q']=GETPOST($qty,'int');				// the qty we want to move for this stock record
    				    $sub_qty[$j]['id_batch']=GETPOST($batch,'int');		// the id into llx_product_batch of stock record to move
    					$subtotalqty+=$sub_qty[$j]['q'];

    					//var_dump($qty);var_dump($batch);var_dump($sub_qty[$j]['q']);var_dump($sub_qty[$j]['id_batch']);

    					$j++;
    					$batch="batchl".$i."_".$j;
    					$qty = "qtyl".$i.'_'.$j;
    				}

    				$batch_line[$i]['detail']=$sub_qty;		// array of details
    				$batch_line[$i]['qty']=$subtotalqty;
    				$batch_line[$i]['ix_l']=GETPOST($idl,'int');

    				$totalqty+=$subtotalqty;
			    }
			    else
			    {
			        // No detail were provided for lots
			        if (! empty($_POST[$qty]))
			        {
			            // We try to set an amount
    			        // Case we dont use the list of available qty for each warehouse/lot
    			        // GUI does not allow this yet
    			        setEventMessage('StockIsRequiredToChooseWhichLotToUse', 'errors');
			        }
			    }
			}
			else if (isset($_POST[$stockLocation]))
			{
			    //shipment line from multiple stock locations
			    $qty .= '_'.$j;
			    while (isset($_POST[$stockLocation]))
			    {
			        // save sub line of warehouse
			        $stockLine[$i][$j]['qty']=GETPOST($qty,'int');
			        $stockLine[$i][$j]['warehouse_id']=GETPOST($stockLocation,'int');
			        $stockLine[$i][$j]['ix_l']=GETPOST($idl,'int');

			        $totalqty+=GETPOST($qty,'int');

			        $j++;
			        $stockLocation="ent1".$i."_".$j;
			        $qty = "qtyl".$i.'_'.$j;
			    }
			}
			else
			{
			    //var_dump(GETPOST($qty,'int')); var_dump($_POST); var_dump($batch);exit;
				//shipment line for product with no batch management and no multiple stock location
				if (GETPOST($qty,'int') > 0) $totalqty+=GETPOST($qty,'int');
			}

			// Extrafields
			$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
            $array_options[$i] = $extrafieldsline->getOptionalsFromPost($extralabelsline, $i);
			// Unset extrafield
			if (is_array($extralabelsline)) {
				// Get extra fields
				foreach ($extralabelsline as $key => $value) {
					unset($_POST["options_" . $key]);
				}
			}

	    }

	    //var_dump($batch_line[2]);

	    if ($totalqty > 0)		// There is at least one thing to ship
	    {
	        //var_dump($_POST);exit;
	        for ($i = 0; $i < $num; $i++)
	        {
	            $qty = "qtyl".$i;
				if (! isset($batch_line[$i]))
				{
					// not batch mode
					if (isset($stockLine[$i]))
					{
    					//shipment from multiple stock locations
					    $nbstockline = count($stockLine[$i]);
    					for($j = 0; $j < $nbstockline; $j++)
    					{
    					    if ($stockLine[$i][$j]['qty']>0)
    					    {
    					        $ret=$object->addline($stockLine[$i][$j]['warehouse_id'], $stockLine[$i][$j]['ix_l'], $stockLine[$i][$j]['qty'], $array_options[$i]);
    					        if ($ret < 0)
    					        {
    					            setEventMessages($object->error, $object->errors, 'errors');
    					            $error++;
    					        }
    					    }
    					}
					}
					else
					{
						if (GETPOST($qty,'int') > 0 || (GETPOST($qty,'int') == 0 && $conf->global->SHIPMENT_GETS_ALL_ORDER_PRODUCTS))
						{
							$ent = "entl".$i;
							$idl = "idl".$i;
							$entrepot_id = is_numeric(GETPOST($ent,'int'))?GETPOST($ent,'int'):GETPOST('entrepot_id','int');
							if ($entrepot_id < 0) $entrepot_id='';
							if (! ($objectsrc->lines[$i]->fk_product > 0)) $entrepot_id = 0;

							$ret=$object->addline($entrepot_id, GETPOST($idl,'int'), GETPOST($qty,'int'), $array_options[$i]);
							if ($ret < 0)
							{
								setEventMessages($object->error, $object->errors, 'errors');
								$error++;
							}
						}
					}
				}
				else
				{
					// batch mode
					if ($batch_line[$i]['qty']>0)
					{
						$ret=$object->addline_batch($batch_line[$i],$array_options[$i]);
						if ($ret < 0)
						{
							setEventMessages($object->error, $object->errors, 'errors');
							$error++;
						}
					}
				}
	        }
	        // Fill array 'array_options' with data from add form
	        $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
	        if ($ret < 0) $error++;

	        if (! $error)
	        {
	            $ret=$object->create($user);		// This create shipment (like Odoo picking) and line of shipments. Stock movement will when validating shipment.
	            if ($ret <= 0)
	            {
	                setEventMessages($object->error, $object->errors, 'errors');
	                $error++;
	            }
	        }
	    }
	    else
	    {
	        setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("QtyToShip").'/'.$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
	        $error++;
	    }

	    if (! $error)
	    {
	        $db->commit();
	        header("Location: card.php?id=".$object->id);
	        exit;
	    }
	    else
	    {
	        $db->rollback();
	        $_GET["commande_id"]=GETPOST('commande_id','int');
	        $action='create';
	    }
	}

	/*
	 * Build a receiving receipt
	 */
	else if ($action == 'create_delivery' && $conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer)
	{
	    $result = $object->create_delivery($user);
	    if ($result > 0)
	    {
	        header("Location: ".DOL_URL_ROOT.'/livraison/card.php?action=create_delivery&id='.$result);
	        exit;
	    }
	    else
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	else if ($action == 'confirm_valid' && $confirm == 'yes' &&
        ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->creer))
       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->shipping_advance->validate)))
	)
	{
	    $object->fetch_thirdparty();

	    $result = $object->valid($user);

	    if ($result < 0)
	    {
			$langs->load("errors");
	        setEventMessages($langs->trans($object->error), null, 'errors');
	    }
	    else
	    {
	    	// Define output language
	    	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	    	{
	    		$outputlangs = $langs;
	    		$newlang = '';
	    		if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang = GETPOST('lang_id','aZ09');
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
	}

	else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->expedition->supprimer)
	{
	    $result = $object->delete();
	    if ($result > 0)
	    {
	        header("Location: ".DOL_URL_ROOT.'/expedition/index.php');
	        exit;
	    }
	    else
		{
			setEventMessages($object->error, $object->errors, 'errors');
	    }
	}
	// TODO add alternative status
	/*else if ($action == 'reopen' && (! empty($user->rights->expedition->creer) || ! empty($user->rights->expedition->shipping_advance->validate)))
	{
	    $result = $object->setStatut(0);
	    if ($result < 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}*/

	else if ($action == 'setdate_livraison' && $user->rights->expedition->creer)
	{
	    //print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	    $datedelivery=dol_mktime(GETPOST('liv_hour','int'), GETPOST('liv_min','int'), 0, GETPOST('liv_month','int'), GETPOST('liv_day','int'), GETPOST('liv_year','int'));

	    $object->fetch($id);
	    $result=$object->set_date_livraison($user,$datedelivery);
	    if ($result < 0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	// Action update
	else if ($action == 'settracking_number' || $action == 'settracking_url'
	|| $action == 'settrueWeight'
	|| $action == 'settrueWidth'
	|| $action == 'settrueHeight'
	|| $action == 'settrueDepth'
	|| $action == 'setshipping_method_id')
	{
	    $error=0;

	    if ($action == 'settracking_number')		$object->tracking_number = trim(GETPOST('tracking_number','alpha'));
	    if ($action == 'settracking_url')		$object->tracking_url = trim(GETPOST('tracking_url','int'));
	    if ($action == 'settrueWeight')	{
	    	$object->trueWeight = trim(GETPOST('trueWeight','int'));
			$object->weight_units = GETPOST('weight_units','int');
	    }
	    if ($action == 'settrueWidth')			$object->trueWidth = trim(GETPOST('trueWidth','int'));
	    if ($action == 'settrueHeight'){
	    				$object->trueHeight = trim(GETPOST('trueHeight','int'));
						$object->size_units = GETPOST('size_units','int');
		}
	    if ($action == 'settrueDepth')			$object->trueDepth = trim(GETPOST('trueDepth','int'));
	    if ($action == 'setshipping_method_id')	$object->shipping_method_id = trim(GETPOST('shipping_method_id','int'));

	    if (! $error)
	    {
	        if ($object->update($user) >= 0)
	        {
	            header("Location: card.php?id=".$object->id);
	            exit;
	        }
	        setEventMessages($object->error, $object->errors, 'errors');
	    }

	    $action="";
	}

	// Build document
	else if ($action == 'builddoc')	// En get ou en post
	{
		// Save last template used to generate document
		if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	    // Define output language
	    $outputlangs = $langs;
	    $newlang='';
	    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
	    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$shipment->thirdparty->default_lang;
	    if (! empty($newlang))
	    {
	        $outputlangs = new Translate("",$conf);
	        $outputlangs->setDefaultLang($newlang);
	    }
		$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	    if ($result <= 0)
	    {
			setEventMessages($object->error, $object->errors, 'errors');
	        $action='';
	    }
	}

	// Delete file in doc form
	elseif ($action == 'remove_file')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$upload_dir =	$conf->expedition->dir_output . "/sending";
		$file =	$upload_dir	. '/' .	GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	}

	elseif ($action == 'classifybilled')
	{
	    $object->fetch($id);
	    $result = $object->set_billed();
	    if($result >= 0) {
	    	header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	    	exit();
	    }
	}

	elseif ($action == 'classifyclosed')
	{
	    $object->fetch($id);
	    $result = $object->setClosed();
	    if($result >= 0) {
	    	header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
	    	exit();
	    }
	}

	/*
	 *  delete a line
	 */
	elseif ($action == 'deleteline' && ! empty($line_id))
	{
		$object->fetch($id);
		$lines = $object->lines;
		$line = new ExpeditionLigne($db);

		$num_prod = count($lines);
		for ($i = 0 ; $i < $num_prod ; $i++)
		{
			if ($lines[$i]->id == $line_id)
			{
				if (count($lines[$i]->details_entrepot) > 1)
				{
					// delete multi warehouse lines
					foreach ($lines[$i]->details_entrepot as $details_entrepot) {
						$line->id = $details_entrepot->line_id;
						if (! $error && $line->delete($user) < 0)
						{
							$error++;
						}
					}
				}
				else
				{
					// delete single warehouse line
					$line->id = $line_id;
					if (! $error && $line->delete($user) < 0)
					{
						$error++;
					}
				}
			}
			unset($_POST["lineid"]);
		}

		if(! $error) {
			header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id);
			exit();
		}
		else
		{
			setEventMessages($line->error, $line->errors, 'errors');
		}
	}

	/*
	 *  Update a line
	 */
	else if ($action == 'updateline' && $user->rights->expedition->creer && GETPOST('save'))
	{
		// Clean parameters
		$qty=0;
		$entrepot_id = 0;
		$batch_id = 0;

		$lines = $object->lines;
		$num_prod = count($lines);
		for ($i = 0 ; $i < $num_prod ; $i++)
		{
			if ($lines[$i]->id == $line_id)		// we have found line to update
			{
				$line = new ExpeditionLigne($db);
				// Extrafields Lines
				$extrafieldsline = new ExtraFields($db);
				$extralabelsline = $extrafieldsline->fetch_name_optionals_label($object->table_element_line);
				$line->array_options = $extrafieldsline->getOptionalsFromPost($extralabelsline);
				// Unset extrafield POST Data
				if (is_array($extralabelsline)) {
					foreach ($extralabelsline as $key => $value) {
						unset($_POST["options_" . $key]);
					}
				}
				$line->fk_product = $lines[$i]->fk_product;
				if (is_array($lines[$i]->detail_batch) && count($lines[$i]->detail_batch) > 0)
				{
					// line with lot
					foreach ($lines[$i]->detail_batch as $detail_batch)
					{
						$lotStock = new Productbatch($db);
						$batch="batchl".$detail_batch->fk_expeditiondet."_".$detail_batch->fk_origin_stock;
						$qty = "qtyl".$detail_batch->fk_expeditiondet.'_'.$detail_batch->id;
						$batch_id = GETPOST($batch,'int');
						$batch_qty = GETPOST($qty, 'int');
						if (! empty($batch_id) && ($batch_id != $detail_batch->fk_origin_stock || $batch_qty != $detail_batch->dluo_qty))
						{
							if ($lotStock->fetch($batch_id) > 0 && $line->fetch($detail_batch->fk_expeditiondet) > 0)	// $line is ExpeditionLine
							{
								if ($lines[$i]->entrepot_id != 0)
								{
									// allow update line entrepot_id if not multi warehouse shipping
									$line->entrepot_id = $lotStock->warehouseid;
								}

								// detail_batch can be an object with keys, or an array of ExpeditionLineBatch
								if (empty($line->detail_batch)) $line->detail_batch=new stdClass();

								$line->detail_batch->fk_origin_stock = $batch_id;
								$line->detail_batch->batch = $lotStock->batch;
								$line->detail_batch->id = $detail_batch->id;
								$line->detail_batch->entrepot_id = $lotStock->warehouseid;
								$line->detail_batch->dluo_qty = $batch_qty;
								if ($line->update($user) < 0) {
									setEventMessages($line->error, $line->errors, 'errors');
									$error++;
								}
							}
							else
							{
								setEventMessages($lotStock->error, $lotStock->errors, 'errors');
								$error++;
							}
						}
						unset($_POST[$batch]);
						unset($_POST[$qty]);
					}
					// add new batch
					$lotStock = new Productbatch($db);
					$batch="batchl".$line_id."_0";
					$qty = "qtyl".$line_id."_0";
					$batch_id = GETPOST($batch,'int');
					$batch_qty = GETPOST($qty, 'int');
					$lineIdToAddLot = 0;
					if ($batch_qty > 0 && ! empty($batch_id))
					{
						if ($lotStock->fetch($batch_id) > 0)
						{
							// check if lotStock warehouse id is same as line warehouse id
							if ($lines[$i]->entrepot_id > 0)
							{
								// single warehouse shipment line
								if ($lines[i]->entrepot_id == $lotStock->warehouseid)
								{
									$lineIdToAddLot = $line_id;
								}
							}
							else if (count($lines[$i]->details_entrepot) > 1)
							{
								// multi warehouse shipment lines
								foreach ($lines[$i]->details_entrepot as $detail_entrepot)
								{
									if ($detail_entrepot->entrepot_id == $lotStock->warehouseid)
									{
										$lineIdToAddLot = $detail_entrepot->line_id;
									}
								}
							}
							if ($lineIdToAddLot)
							{
								// add lot to existing line
								if ($line->fetch($lineIdToAddLot) > 0)
								{
									$line->detail_batch->fk_origin_stock = $batch_id;
									$line->detail_batch->batch = $lotStock->batch;
									$line->detail_batch->entrepot_id = $lotStock->warehouseid;
									$line->detail_batch->dluo_qty = $batch_qty;
									if ($line->update($user) < 0) {
										setEventMessages($line->error, $line->errors, 'errors');
										$error++;
									}
								}
								else
								{
									setEventMessages($line->error, $line->errors, 'errors');
									$error++;
								}
							}
							else
							{
								// create new line with new lot
								$line->origin_line_id = $lines[$i]->origin_line_id;
								$line->entrepot_id = $lotStock->warehouseid;
								$line->detail_batch[0] = new ExpeditionLineBatch($db);
								$line->detail_batch[0]->fk_origin_stock = $batch_id;
								$line->detail_batch[0]->batch = $lotStock->batch;
								$line->detail_batch[0]->entrepot_id = $lotStock->warehouseid;
								$line->detail_batch[0]->dluo_qty = $batch_qty;
								if ($object->create_line_batch($line, $line->array_options) < 0)
								{
									setEventMessages($object->error, $object->errors, 'errors');
									$error++;
								}
							}
						}
						else
						{
							setEventMessages($lotStock->error, $lotStock->errors, 'errors');
							$error++;
						}
					}
				}
				else
				{
					if ($lines[$i]->fk_product > 0)
					{
						// line without lot
						if ($lines[$i]->entrepot_id > 0)
						{
							// single warehouse shipment line
							$stockLocation="entl".$line_id;
							$qty = "qtyl".$line_id;
							$line->id = $line_id;
							$line->entrepot_id = GETPOST($stockLocation,'int');
							$line->qty = GETPOST($qty, 'int');
							if ($line->update($user) < 0) {
								setEventMessages($line->error, $line->errors, 'errors');
								$error++;
							}
							unset($_POST[$stockLocation]);
							unset($_POST[$qty]);
						}
						else if (count($lines[$i]->details_entrepot) > 1)
						{
							// multi warehouse shipment lines
							foreach ($lines[$i]->details_entrepot as $detail_entrepot)
							{
								if (! $error) {
									$stockLocation="entl".$detail_entrepot->line_id;
									$qty = "qtyl".$detail_entrepot->line_id;
									$warehouse = GETPOST($stockLocation,'int');
									if (!empty ($warehouse))
									{
										$line->id = $detail_entrepot->line_id;
										$line->entrepot_id = $warehouse;
										$line->qty = GETPOST($qty, 'int');
										if ($line->update($user) < 0) {
											setEventMessages($line->error, $line->errors, 'errors');
											$error++;
										}
									}
									unset($_POST[$stockLocation]);
									unset($_POST[$qty]);
								}
							}
						}
					}
					else	// Product no predefined
					{
						$qty = "qtyl".$line_id;
						$line->id = $line_id;
						$line->qty = GETPOST($qty, 'int');
						$line->entrepot_id = 0;
						if ($line->update($user) < 0) {
							setEventMessages($line->error, $line->errors, 'errors');
							$error++;
						}
						unset($_POST[$qty]);
					}
				}
			}
		}

		unset($_POST["lineid"]);

		if (! $error) {
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				// Define output language
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09'))
					$newlang = GETPOST('lang_id','aZ09');
				if ($conf->global->MAIN_MULTILANGS && empty($newlang))
					$newlang = $object->thirdparty->default_lang;
				if (! empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$ret = $object->fetch($object->id); // Reload to get new records
				$object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
		else
		{
			header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
			exit();
		}
	}

	else if ($action == 'updateline' && $user->rights->expedition->creer && GETPOST('cancel','alpha') == $langs->trans('Cancel')) {
		header('Location: ' . $_SERVER['PHP_SELF'] . '?id=' . $object->id); // Pour reaffichage de la fiche en cours d'edition
		exit();
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	if (empty($id)) $id=$facid;
	$trigger_name='SHIPPING_SENTBYMAIL';
	$paramname='id';
	$mode='emailfromshipment';
	$trackid='shi'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

}


/*
 * View
 */

llxHeader('',$langs->trans('Shipment'),'Expedition');

$form = new Form($db);
$formfile = new FormFile($db);
$formproduct = new FormProduct($db);
if (! empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$product_static = new Product($db);
$shipment_static = new Expedition($db);
$warehousestatic = new Entrepot($db);

if ($action == 'create2')
{
    print load_fiche_titre($langs->trans("CreateShipment")).'<br>';
    print $langs->trans("ShipmentCreationIsDoneFromOrder");
    $action=''; $id=''; $ref='';
}

// Mode creation.
if ($action == 'create')
{
    $expe = new Expedition($db);

    print load_fiche_titre($langs->trans("CreateShipment"));
    if (! $origin)
    {
        setEventMessages($langs->trans("ErrorBadParameters"), null, 'errors');
    }

    if ($origin)
    {
        $classname = ucfirst($origin);

        $object = new $classname($db);
        if ($object->fetch($origin_id))	// This include the fetch_lines
        {
            $soc = new Societe($db);
            $soc->fetch($object->socid);

            $author = new User($db);
            $author->fetch($object->user_author_id);

            if (! empty($conf->stock->enabled)) $entrepot = new Entrepot($db);

            print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="add">';
            print '<input type="hidden" name="origin" value="'.$origin.'">';
            print '<input type="hidden" name="origin_id" value="'.$object->id.'">';
            print '<input type="hidden" name="ref_int" value="'.$object->ref_int.'">';
            if (GETPOST('entrepot_id','int'))
            {
                print '<input type="hidden" name="entrepot_id" value="'.GETPOST('entrepot_id','int').'">';
            }

            dol_fiche_head('');

            print '<table class="border centpercent">';

            // Ref
            print '<tr><td class="titlefieldcreate fieldrequired">';
            if ($origin == 'commande' && ! empty($conf->commande->enabled))
            {
                print $langs->trans("RefOrder").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$object->id.'">'.img_object($langs->trans("ShowOrder"),'order').' '.$object->ref;
            }
            if ($origin == 'propal' && ! empty($conf->propal->enabled))
            {
                print $langs->trans("RefProposal").'</td><td colspan="3"><a href="'.DOL_URL_ROOT.'/comm/card.php?id='.$object->id.'">'.img_object($langs->trans("ShowProposal"),'propal').' '.$object->ref;
            }
            print '</a></td>';
            print "</tr>\n";

            // Ref client
            print '<tr><td>';
            if ($origin == 'commande') print $langs->trans('RefCustomerOrder');
            else if ($origin == 'propal') print $langs->trans('RefCustomerOrder');
            else print $langs->trans('RefCustomer');
            print '</td><td colspan="3">';
            print '<input type="text" name="ref_customer" value="'.$object->ref_client.'" />';
            print '</td>';
            print '</tr>';

            // Tiers
            print '<tr><td class="titlefieldcreate fieldrequired">'.$langs->trans('Company').'</td>';
            print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
            print '</tr>';

            // Project
            if (! empty($conf->projet->enabled))
            {
                $projectid = GETPOST('projectid','int')?GETPOST('projectid','int'):0;
                if ($origin == 'project') $projectid = ($originid ? $originid : 0);

                $langs->load("projects");
                print '<tr>';
                print '<td>' . $langs->trans("Project") . '</td><td colspan="2">';
                $numprojet = $formproject->select_projects($soc->id, $projectid, 'projectid', 0);
                print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/card.php?socid=' . $soc->id . '&action=create&status=1&backtopage='.urlencode($_SERVER["PHP_SELF"].'?action=create&socid='.$soc->id).'">' . $langs->trans("AddProject") . '</a>';
                print '</td>';
                print '</tr>';
            }

            // Date delivery planned
            print '<tr><td>'.$langs->trans("DateDeliveryPlanned").'</td>';
            print '<td colspan="3">';
            //print dol_print_date($object->date_livraison,"day");	// date_livraison come from order and will be stored into date_delivery planed.
            $date_delivery = ($date_delivery?$date_delivery:$object->date_livraison); // $date_delivery comes from GETPOST
            print $form->select_date($date_delivery?$date_delivery:-1,'date_delivery',1,1,1);
            print "</td>\n";
            print '</tr>';

            // Note Public
            print '<tr><td>'.$langs->trans("NotePublic").'</td>';
            print '<td colspan="3">';
            $doleditor = new DolEditor('note_public', $object->note_public, '', 60, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
            print $doleditor->Create(1);
            print "</td></tr>";

            // Note Private
            if ($object->note_private && ! $user->societe_id)
            {
                print '<tr><td>'.$langs->trans("NotePrivate").'</td>';
                print '<td colspan="3">';
                $doleditor = new DolEditor('note_private', $object->note_private, '', 60, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
        		print $doleditor->Create(1);
                print "</td></tr>";
            }

            // Weight
            print '<tr><td>';
            print $langs->trans("Weight");
            print '</td><td colspan="3"><input name="weight" size="4" value="'.GETPOST('weight','int').'"> ';
            $text=$formproduct->select_measuring_units("weight_units","weight",GETPOST('weight_units','int'));
            $htmltext=$langs->trans("KeepEmptyForAutoCalculation");
            print $form->textwithpicto($text, $htmltext);
            print '</td></tr>';
            // Dim
            print '<tr><td>';
            print $langs->trans("Width").' x '.$langs->trans("Height").' x '.$langs->trans("Depth");
            print ' </td><td colspan="3"><input name="sizeW" size="4" value="'.GETPOST('sizeW','int').'">';
            print ' x <input name="sizeH" size="4" value="'.GETPOST('sizeH','int').'">';
            print ' x <input name="sizeS" size="4" value="'.GETPOST('sizeS','int').'">';
            print ' ';
            $text=$formproduct->select_measuring_units("size_units","size");
            $htmltext=$langs->trans("KeepEmptyForAutoCalculation");
            print $form->textwithpicto($text, $htmltext);
            print '</td></tr>';

            // Delivery method
            print "<tr><td>".$langs->trans("DeliveryMethod")."</td>";
            print '<td colspan="3">';
            $expe->fetch_delivery_methods();
            print $form->selectarray("shipping_method_id", $expe->meths, GETPOST('shipping_method_id','int'),1,0,0,"",1);
            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
            print "</td></tr>\n";

            // Tracking number
            print "<tr><td>".$langs->trans("TrackingNumber")."</td>";
            print '<td colspan="3">';
            print '<input name="tracking_number" size="20" value="'.GETPOST('tracking_number','alpha').'">';
            print "</td></tr>\n";

            // Other attributes
            $parameters = array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"', 'socid'=>$socid);
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$expe,$action);    // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;

			if (empty($reshook) && ! empty($extrafields->attribute_label)) {
				// copy from order
				$orderExtrafields = new Extrafields($db);
				$orderExtrafieldLabels = $orderExtrafields->fetch_name_optionals_label($object->table_element);
				if ($object->fetch_optionals() > 0) {
					$expe->array_options = array_merge($expe->array_options, $object->array_options);
				}
				print $object->showOptionals($extrafields, 'edit');
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

            // Document model
			include_once DOL_DOCUMENT_ROOT . '/core/modules/expedition/modules_expedition.php';
			$liste = ModelePdfExpedition::liste_modeles($db);
			if (count($liste) > 1)
			{
    			print "<tr><td>".$langs->trans("DefaultModel")."</td>";
                print '<td colspan="3">';
    			print $form->selectarray('model', $liste, $conf->global->EXPEDITION_ADDON_PDF);
                print "</td></tr>\n";
			}

            print "</table>";

            dol_fiche_end();


            // Shipment lines

            $numAsked = count($object->lines);

            print '<script type="text/javascript" language="javascript">
            jQuery(document).ready(function() {
	            jQuery("#autofill").click(function() {';
    	    	$i=0;
    	    	while($i < $numAsked)
    	    	{
    	    		print 'jQuery("#qtyl'.$i.'").val(jQuery("#qtyasked'.$i.'").val() - jQuery("#qtydelivered'.$i.'").val());'."\n";
    	    		$i++;
    	    	}
        		print '});
	            jQuery("#autoreset").click(function() {';
    	    	$i=0;
    	    	while($i < $numAsked)
    	    	{
    	    		print 'jQuery("#qtyl'.$i.'").val(0);'."\n";
    	    		$i++;
    	    	}
        		print '});
        	});
            </script>';


            print '<br>';


            print '<table class="noborder" width="100%">';


            // Load shipments already done for same order
            $object->loadExpeditions();

            if ($numAsked)
            {
                print '<tr class="liste_titre">';
                print '<td>'.$langs->trans("Description").'</td>';
                print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
                print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
                print '<td align="center">'.$langs->trans("QtyToShip");
				if (empty($conf->productbatch->enabled))
				{
	                print ' <br>(<a href="#" id="autofill">'.$langs->trans("Fill").'</a>';
	                print ' / <a href="#" id="autoreset">'.$langs->trans("Reset").'</a>)';
				}
                print '</td>';
                if (! empty($conf->stock->enabled))
                {
					if (empty($conf->productbatch->enabled))
					{
                    	print '<td align="left">'.$langs->trans("Warehouse").' ('.$langs->trans("Stock").')</td>';
					}
					else
					{
						print '<td align="left">'.$langs->trans("Warehouse").' / '.$langs->trans("Batch").' ('.$langs->trans("Stock").')</td>';
					}
                }
                print "</tr>\n";
            }

            $var=true;
            $indiceAsked = 0;
            while ($indiceAsked < $numAsked)
            {
                $product = new Product($db);

                $line = $object->lines[$indiceAsked];


                // Show product and description
                $type=$line->product_type?$line->product_type:$line->fk_product_type;
                // Try to enhance type detection using date_start and date_end for free lines where type
                // was not saved.
                if (! empty($line->date_start)) $type=1;
                if (! empty($line->date_end)) $type=1;

                print '<!-- line '.$line->rowid.' for product -->'."\n";
                print '<tr class="oddeven">'."\n";

                // Product label
                if ($line->fk_product > 0)  // If predefined product
                {
                    $product->fetch($line->fk_product);
                    $product->load_stock('warehouseopen');	// Load all $product->stock_warehouse[idwarehouse]->detail_batch
                    //var_dump($product->stock_warehouse[1]);

                    print '<td>';
                    print '<a name="'.$line->rowid.'"></a>'; // ancre pour retourner sur la ligne

                    // Show product and description
                    $product_static->type=$line->fk_product_type;
                    $product_static->id=$line->fk_product;
                    $product_static->ref=$line->ref;
                    $product_static->status_batch=$line->product_tobatch;
                    $text=$product_static->getNomUrl(1);
                    $text.= ' - '.(! empty($line->label)?$line->label:$line->product_label);
                    $description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($line->desc));
                    print $form->textwithtooltip($text,$description,3,'','',$i);

                    // Show range
                    print_date_range($db->jdate($line->date_start),$db->jdate($line->date_end));

                    // Add description in form
                    if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
                    {
                        print ($line->desc && $line->desc!=$line->product_label)?'<br>'.dol_htmlentitiesbr($line->desc):'';
                    }

                    print '</td>';
                }
                else
				{
				    print "<td>";
                    if ($type==1) $text = img_object($langs->trans('Service'),'service');
                    else $text = img_object($langs->trans('Product'),'product');

                    if (! empty($line->label)) {
                    	$text.= ' <strong>'.$line->label.'</strong>';
                    	print $form->textwithtooltip($text,$line->desc,3,'','',$i);
                    } else {
                    	print $text.' '.nl2br($line->desc);
                    }

                    // Show range
                    print_date_range($db->jdate($line->date_start),$db->jdate($line->date_end));
                    print "</td>\n";
                }

                // Qty
                print '<td align="center">'.$line->qty;
                print '<input name="qtyasked'.$indiceAsked.'" id="qtyasked'.$indiceAsked.'" type="hidden" value="'.$line->qty.'">';
                print '</td>';
                $qtyProdCom=$line->qty;

                // Qty already shipped
                print '<td align="center">';
                $quantityDelivered = $object->expeditions[$line->id];
                print $quantityDelivered;
                print '<input name="qtydelivered'.$indiceAsked.'" id="qtydelivered'.$indiceAsked.'" type="hidden" value="'.$quantityDelivered.'">';
                print '</td>';

                // Qty to ship
                $quantityAsked = $line->qty;
				if ($line->product_type == 1 && empty($conf->global->STOCK_SUPPORTS_SERVICES))
				{
					$quantityToBeDelivered = 0;
				}
				else
				{
					$quantityToBeDelivered = $quantityAsked - $quantityDelivered;
				}
                $warehouse_id = GETPOST('entrepot_id','int');

				$warehouseObject = null;
				if ($warehouse_id > 0 || ! ($line->fk_product > 0) || empty($conf->stock->enabled))     // If warehouse was already selected or if product is not a predefined, we go into this part with no multiwarehouse selection
				{
				    print '<!-- Case warehouse already known or product not a predefined product -->';
					//ship from preselected location
					$stock = + $product->stock_warehouse[$warehouse_id]->real; // Convert to number
					$deliverableQty=min($quantityToBeDelivered, $stock);
					if ($deliverableQty < 0) $deliverableQty = 0;
					if (empty($conf->productbatch->enabled) || ! $product->hasbatch())
					{
						// Quantity to send
						print '<td align="center">';
						if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
						{
                            if (GETPOST('qtyl'.$indiceAsked, 'int')) $defaultqty=GETPOST('qtyl'.$indiceAsked, 'int');
                            print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
							print '<input name="qtyl'.$indiceAsked.'" id="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$deliverableQty.'">';
						}
						else print $langs->trans("NA");
						print '</td>';

						// Stock
						if (! empty($conf->stock->enabled))
						{
							print '<td align="left">';
							if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))   // Type of product need stock change ?
							{
								// Show warehouse combo list
								$ent = "entl".$indiceAsked;
								$idl = "idl".$indiceAsked;
								$tmpentrepot_id = is_numeric(GETPOST($ent,'int'))?GETPOST($ent,'int'):$warehouse_id;
								if ($line->fk_product > 0)
								{
								    print '<!-- Show warehouse selection -->';
									print $formproduct->selectWarehouses($tmpentrepot_id, 'entl'.$indiceAsked, '', 1, 0, $line->fk_product, '', 1);
									if ($tmpentrepot_id > 0 && $tmpentrepot_id == $warehouse_id)
									{
										//print $stock.' '.$quantityToBeDelivered;
										if ($stock < $quantityToBeDelivered)
										{
											print ' '.img_warning($langs->trans("StockTooLow"));	// Stock too low for this $warehouse_id but you can change warehouse
										}
									}
								}
							}
							else
							{
								print $langs->trans("Service");
							}
							print '</td>';
						}

						print "</tr>\n";

						// Show subproducts of product
						if (! empty($conf->global->PRODUIT_SOUSPRODUITS) && $line->fk_product > 0)
						{
							$product->get_sousproduits_arbo();
							$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
							if(count($prods_arbo) > 0)
							{
								foreach($prods_arbo as $key => $value)
								{
									//print $value[0];
									$img='';
									if ($value['stock'] < $value['stock_alert'])
									{
										$img=img_warning($langs->trans("StockTooLow"));
									}
									print "<tr class=\"oddeven\"><td>&nbsp; &nbsp; &nbsp; ->
										<a href=\"".DOL_URL_ROOT."/product/card.php?id=".$value['id']."\">".$value['fullpath']."
										</a> (".$value['nb'].")</td><td align=\"center\"> ".$value['nb_total']."</td><td>&nbsp</td><td>&nbsp</td>
										<td align=\"center\">".$value['stock']." ".$img."</td></tr>";
								}
							}
						}
					}
					else
					{
					    // Product need lot
						print '<td></td><td></td></tr>';	// end line and start a new one for lot/serial

						$staticwarehouse=new Entrepot($db);
						if ($warehouse_id > 0) $staticwarehouse->fetch($warehouse_id);

						$subj=0;
						// Define nb of lines suggested for this order line
						$nbofsuggested=0;
						if (is_object($product->stock_warehouse[$warehouse_id]) && count($product->stock_warehouse[$warehouse_id]->detail_batch))
						{
							foreach ($product->stock_warehouse[$warehouse_id]->detail_batch as $dbatch)
						    {
   						        $nbofsuggested++;
    						}
						}
						print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
						if (is_object($product->stock_warehouse[$warehouse_id]) && count($product->stock_warehouse[$warehouse_id]->detail_batch))
						{
							foreach ($product->stock_warehouse[$warehouse_id]->detail_batch as $dbatch)
							{
								//var_dump($dbatch);
								$batchStock = + $dbatch->qty;		// To get a numeric
								$deliverableQty = min($quantityToBeDelivered,$batchStock);
								print '<!-- subj='.$subj.'/'.$nbofsuggested.' --><tr '.((($subj + 1) == $nbofsuggested)?$bc[$var]:'').'>';
								print '<td colspan="3" ></td><td align="center">';
								print '<input name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="'.$deliverableQty.'">';
								print '</td>';

								print '<!-- Show details of lot -->';
								print '<td align="left">';

								print $staticwarehouse->getNomUrl(0).' / ';

								print '<input name="batchl'.$indiceAsked.'_'.$subj.'" type="hidden" value="'.$dbatch->id.'">';

								$detail='';
								$detail.= $langs->trans("Batch").': '.$dbatch->batch;
								$detail.= ' - '.$langs->trans("SellByDate").': '.dol_print_date($dbatch->sellby,"day");
								$detail.= ' - '.$langs->trans("EatByDate").': '.dol_print_date($dbatch->eatby,"day");
								$detail.= ' - '.$langs->trans("Qty").': '.$dbatch->dluo_qty;
								$detail.= '<br>';
								print $detail;

								$quantityToBeDelivered -= $deliverableQty;
								if ($quantityToBeDelivered < 0)
								{
									$quantityToBeDelivered = 0;
								}
								$subj++;
								print '</td></tr>';
							}
						}
						else
						{
						    print '<!-- Case there is no details of lot at all -->';
						    print '<tr class="oddeven"><td colspan="3"></td><td align="center">';
							print '<input name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="0" disabled="disabled"> ';
							print '</td>';

							print '<td align="left">';
							print img_warning().' '.$langs->trans("NoProductToShipFoundIntoStock", $staticwarehouse->libelle);
							print '</td></tr>';
						}
					}
				}
				else
				{
					// ship from multiple locations
					if (empty($conf->productbatch->enabled) || ! $product->hasbatch())
					{
					    print '<!-- Case warehouse not already known and product does not need lot -->';
					    print '<td></td><td></td></tr>'."\n";	// end line and start a new one for each warehouse

						print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';
						$subj=0;
    					// Define nb of lines suggested for this order line
						$nbofsuggested=0;
						foreach ($product->stock_warehouse as $warehouse_id=>$stock_warehouse)
						{
							if ($stock_warehouse->real > 0)
							{
                                $nbofsuggested++;
						    }
						}
						$tmpwarehouseObject=new Entrepot($db);
						foreach ($product->stock_warehouse as $warehouse_id=>$stock_warehouse)    // $stock_warehouse is product_stock
						{
							$tmpwarehouseObject->fetch($warehouse_id);
							if ($stock_warehouse->real > 0)
							{
								$stock = + $stock_warehouse->real; // Convert it to number
								$deliverableQty = min($quantityToBeDelivered,$stock);
								$deliverableQty = max(0, $deliverableQty);
								// Quantity to send
								print '<!-- subj='.$subj.'/'.$nbofsuggested.' --><tr '.((($subj + 1) == $nbofsuggested)?$bc[$var]:'').'>';
								print '<td colspan="3" ></td><td align="center"><!-- qty to ship (no lot management for product line indiceAsked='.$indiceAsked.') -->';
								if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
								{
									print '<input name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'" type="text" size="4" value="'.$deliverableQty.'">';
									print '<input name="ent1'.$indiceAsked.'_'.$subj.'" type="hidden" value="'.$warehouse_id.'">';
								}
								else print $langs->trans("NA");
								print '</td>';

								// Stock
								if (! empty($conf->stock->enabled))
								{
									print '<td align="left">';
									if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
									{
										print $tmpwarehouseObject->getNomUrl(0).' ';

										print '<!-- Show details of stock -->';
										print '('.$stock.')';

									}
									else
									{
										print $langs->trans("Service");
									}
									print '</td>';
								}
								$quantityToBeDelivered -= $deliverableQty;
								if ($quantityToBeDelivered < 0)
								{
									$quantityToBeDelivered = 0;
								}
								$subj++;
								print "</tr>\n";
							}
						}
						// Show subproducts of product (not recommanded)
						if (! empty($conf->global->PRODUIT_SOUSPRODUITS) && $line->fk_product > 0)
						{
							$product->get_sousproduits_arbo();
							$prods_arbo = $product->get_arbo_each_prod($qtyProdCom);
							if (count($prods_arbo) > 0)
							{
								foreach($prods_arbo as $key => $value)
								{
									//print $value[0];
									$img='';
									if ($value['stock'] < $value['stock_alert'])
									{
										$img=img_warning($langs->trans("StockTooLow"));
									}
									print '<tr class"oddeven"><td>';
									print "&nbsp; &nbsp; &nbsp; ->
									<a href=\"".DOL_URL_ROOT."/product/card.php?id=".$value['id']."\">".$value['fullpath']."
									</a> (".$value['nb'].")</td><td align=\"center\"> ".$value['nb_total']."</td><td>&nbsp</td><td>&nbsp</td>
									<td align=\"center\">".$value['stock']." ".$img."</td>";
									print "</tr>";
								}
							}
						}
					}
					else
					{
					    print '<!-- Case warehouse not already known and product need lot -->';
					    print '<td></td><td></td></tr>';	// end line and start a new one for lot/serial

						$subj=0;
						print '<input name="idl'.$indiceAsked.'" type="hidden" value="'.$line->id.'">';

						$tmpwarehouseObject=new Entrepot($db);
						$productlotObject=new Productlot($db);
						// Define nb of lines suggested for this order line
						$nbofsuggested=0;
						foreach ($product->stock_warehouse as $warehouse_id=>$stock_warehouse)
						{
						    if (($stock_warehouse->real > 0) && (count($stock_warehouse->detail_batch))) {
						        foreach ($stock_warehouse->detail_batch as $dbatch)
								{
                                    $nbofsuggested++;
								}
						    }
						}
						foreach ($product->stock_warehouse as $warehouse_id=>$stock_warehouse)
						{
							$tmpwarehouseObject->fetch($warehouse_id);
							if (($stock_warehouse->real > 0) && (count($stock_warehouse->detail_batch))) {
						        foreach ($stock_warehouse->detail_batch as $dbatch)
								{
									//var_dump($dbatch);
									$batchStock = + $dbatch->qty;		// To get a numeric
									$deliverableQty = min($quantityToBeDelivered,$batchStock);
									if ($deliverableQty < 0) $deliverableQty = 0;
									print '<!-- subj='.$subj.'/'.$nbofsuggested.' --><tr '.((($subj + 1) == $nbofsuggested)?$bc[$var]:'').'><td colspan="3"></td><td align="center">';
									print '<input name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="'.$deliverableQty.'">';
									print '</td>';

									print '<td align="left">';

									print $tmpwarehouseObject->getNomUrl(0).' / ';

									print '<!-- Show details of lot -->';
									print '<input name="batchl'.$indiceAsked.'_'.$subj.'" type="hidden" value="'.$dbatch->id.'">';

									//print $line->fk_product.' - '.$dbatch->batch;
									print $langs->trans("Batch").': ';
									$result = $productlotObject->fetch(0, $line->fk_product, $dbatch->batch);
									if ($result > 0) print $productlotObject->getNomUrl(1);
									else print 'TableLotIncompleteRunRepair';
									print ' ('.$dbatch->qty.')';
									$quantityToBeDelivered -= $deliverableQty;
									if ($quantityToBeDelivered < 0)
									{
										$quantityToBeDelivered = 0;
									}
									//dol_syslog('deliverableQty = '.$deliverableQty.' batchStock = '.$batchStock);
									$subj++;
									print '</td></tr>';
								}
							}
						}

					}
					if ($subj == 0) // Line not shown yet, we show it
					{
					    print '<!-- line not shown yet, we show it -->';
						print '<tr class="oddeven"><td colspan="3" ></td><td align="center">';
						if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
						{
						    $disabled='';
					        if (! empty($conf->productbatch->enabled) && $product->hasbatch())
					        {
                                $disabled='disabled="disabled"';
						    }
    						print '<input name="qtyl'.$indiceAsked.'_'.$subj.'" id="qtyl'.$indiceAsked.'_'.$subj.'" type="text" size="4" value="0"'.($disabled?' '.$disabled:'').'> ';
						}
						else
						{
						    print $langs->trans("NA");
						}
						print '</td>';

						print '<td align="left">';
						if ($line->product_type == Product::TYPE_PRODUCT || ! empty($conf->global->STOCK_SUPPORTS_SERVICES))
						{
							$warehouse_selected_id = GETPOST('entrepot_id','int');
    						if ($warehouse_selected_id > 0)
    						{
    							$warehouseObject=new Entrepot($db);
    							$warehouseObject->fetch($warehouse_selected_id);
    							print img_warning().' '.$langs->trans("NoProductToShipFoundIntoStock", $warehouseObject->libelle);
    						}
    						else
    						{
    						    if ($line->fk_product) print img_warning().' '.$langs->trans("StockTooLow");
    						    else print '';
    						}
						}
						else
						{
						    print $langs->trans("Service");
						}
						print '</td>';
						print '</tr>';
					}
				}


				//Display lines extrafields
				if (is_array($extralabelslines) && count($extralabelslines)>0)
				{
					$colspan=5;
					$orderLineExtrafields = new Extrafields($db);
					$orderLineExtrafieldLabels = $orderLineExtrafields->fetch_name_optionals_label($object->table_element_line);
					$srcLine = new OrderLine($db);
					$srcLine->fetch_optionals($line->id); // fetch extrafields also available in orderline
					$line = new ExpeditionLigne($db);
					$line->fetch_optionals($line->id);
					$line->array_options = array_merge($line->array_options, $srcLine->array_options);
					print '<tr class="oddeven">';
					print $line->showOptionals($extrafieldsline, 'edit', array('style'=>$bc[$var], 'colspan'=>$colspan),$indiceAsked);
					print '</tr>';
				}

                $indiceAsked++;
            }

            print "</table>";

            print '<br>';

            print '<div class="center">';
            print '<input type="submit" class="button" name="add" value="'.dol_escape_htmltag($langs->trans("Create")).'">';
            print '&nbsp; ';
            print '<input type="'.($backtopage?"submit":"button").'" class="button" name="cancel" value="'.dol_escape_htmltag($langs->trans("Cancel")).'"'.($backtopage?'':' onclick="javascript:history.go(-1)"').'>';	// Cancel for create does not post form if we don't know the backtopage
            print '</div>';

            print '</form>';

            print '<br>';
        }
        else
		{
            dol_print_error($db);
        }
    }
}
else if ($id || $ref)
/* *************************************************************************** */
/*                                                                             */
/* Edit and view mode                                                          */
/*                                                                             */
/* *************************************************************************** */
{
	$lines = $object->lines;

	$num_prod = count($lines);

	if ($object->id > 0)
	{
		if (!empty($object->origin) && $object->origin_id > 0)
		{
			$typeobject = $object->origin;
			$origin = $object->origin;
			$origin_id = $object->origin_id;
			$object->fetch_origin();         // Load property $object->commande, $object->propal, ...
		}

		$soc = new Societe($db);
		$soc->fetch($object->socid);

		$res = $object->fetch_optionals();

		$head=shipping_prepare_head($object);
		dol_fiche_head($head, 'shipping', $langs->trans("Shipment"), -1, 'sending');

		$formconfirm='';

		// Confirm deleteion
		if ($action == 'delete')
		{
			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('DeleteSending'),$langs->trans("ConfirmDeleteSending",$object->ref),'confirm_delete','',0,1);
		}

		// Confirmation validation
		if ($action == 'valid')
		{
			$objectref = substr($object->ref, 1, 4);
			if ($objectref == 'PROV')
			{
				$numref = $object->getNextNumRef($soc);
			}
			else
			{
				$numref = $object->ref;
			}

			$text = $langs->trans("ConfirmValidateSending",$numref);

			if (! empty($conf->notification->enabled))
			{
				require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
				$notify=new Notify($db);
				$text.='<br>';
				$text.=$notify->confirmMessage('SHIPPING_VALIDATE',$object->socid, $object);
			}

			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('ValidateSending'),$text,'confirm_valid','',0,1);

		}
		// Confirm cancelation
		if ($action == 'annuler')
		{
			$formconfirm=$form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id,$langs->trans('CancelSending'),$langs->trans("ConfirmCancelSending",$object->ref),'confirm_cancel','',0,1);

		}

		if (! $formconfirm) {
		    $parameters = array();
		    $reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		    if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
		    elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;


		// Calculate totalWeight and totalVolume for all products
		// by adding weight and volume of each product line.
		$tmparray=$object->getTotalWeightVolume();
		$totalWeight=$tmparray['weight'];
		$totalVolume=$tmparray['volume'];


		if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
		{
		    $objectsrc=new Commande($db);
		    $objectsrc->fetch($object->$typeobject->id);
		}
		if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
		{
		    $objectsrc=new Propal($db);
		    $objectsrc->fetch($object->$typeobject->id);
		}

		// Shipment card
		$linkback = '<a href="'.DOL_URL_ROOT.'/expedition/list.php?restore_lastsearch_values=1' . (! empty($socid) ? '&socid=' . $socid : '') . '">'.$langs->trans("BackToList").'</a>';
		$morehtmlref='<div class="refidno">';
		// Ref customer shipment
		$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', 0, 1);
		$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, $user->rights->expedition->creer, 'string', '', null, null, '', 1);
		// Thirdparty
        $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
        // Project
        if (! empty($conf->projet->enabled)) {
            $langs->load("projects");
            $morehtmlref .= '<br>' . $langs->trans('Project') . ' ';
            if (0) {    // Do not change on shipment
                if ($action != 'classify') {
                    $morehtmlref .= '<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
                }
                if ($action == 'classify') {
                    // $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
                    $morehtmlref .= '<form method="post" action="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '">';
                    $morehtmlref .= '<input type="hidden" name="action" value="classin">';
                    $morehtmlref .= '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                    $morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
                    $morehtmlref .= '<input type="submit" class="button" value="' . $langs->trans("Modify") . '">';
                    $morehtmlref .= '</form>';
                } else {
                    $morehtmlref .= $form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
                }
            } else {
                // We don't have project on shipment, so we will use the project or source object instead
                // TODO Add project on shipment
                $morehtmlref .= ' : ';
                if (! empty($objectsrc->fk_project)) {
                    $proj = new Project($db);
                    $proj->fetch($objectsrc->fk_project);
                    $morehtmlref .= '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $objectsrc->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
                    $morehtmlref .= $proj->ref;
                    $morehtmlref .= '</a>';
                } else {
                    $morehtmlref .= '';
                }
            }
        }
		$morehtmlref.='</div>';


    	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


    	print '<div class="fichecenter">';
    	print '<div class="fichehalfleft">';
    	print '<div class="underbanner clearboth"></div>';

        print '<table class="border" width="100%">';

		// Linked documents
		if ($typeobject == 'commande' && $object->$typeobject->id && ! empty($conf->commande->enabled))
		{
			print '<tr><td>';
			print $langs->trans("RefOrder").'</td>';
			print '<td colspan="3">';
			print $objectsrc->getNomUrl(1,'commande');
			print "</td>\n";
			print '</tr>';
		}
		if ($typeobject == 'propal' && $object->$typeobject->id && ! empty($conf->propal->enabled))
		{
			print '<tr><td>';
			print $langs->trans("RefProposal").'</td>';
			print '<td colspan="3">';
			print $objectsrc->getNomUrl(1,'expedition');
			print "</td>\n";
			print '</tr>';
		}

		// Date creation
		print '<tr><td class="titlefield">'.$langs->trans("DateCreation").'</td>';
		print '<td colspan="3">'.dol_print_date($object->date_creation,"dayhour")."</td>\n";
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
			print '<input type="hidden" name="action" value="setdate_livraison">';
			print $form->select_date($object->date_delivery?$object->date_delivery:-1,'liv_',1,1,'',"setdate_livraison",1,0,1);
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			print $object->date_delivery ? dol_print_date($object->date_delivery,'dayhour') : '&nbsp;';
		}
		print '</td>';
		print '</tr>';

		// Weight
		print '<tr><td>';
		print $form->editfieldkey("Weight",'trueWeight',$object->trueWeight,$object,$user->rights->expedition->creer);
		print '</td><td colspan="3">';

		if ($action=='edittrueWeight')
		{
			print '<form name="settrueweight" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input name="action" value="settrueWeight" type="hidden">';
			print '<input name="id" value="'.$object->id.'" type="hidden">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input id="trueWeight" name="trueWeight" value="'.$object->trueWeight.'" type="text">';
			print $formproduct->select_measuring_units("weight_units","weight",$object->weight_units);
			print ' <input class="button" name="modify" value="'.$langs->trans("Modify").'" type="submit">';
			print ' <input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
			print '</form>';

		}
		else
		{
			print $object->trueWeight;
			print ($object->trueWeight && $object->weight_units!='')?' '.measuring_units_string($object->weight_units,"weight"):'';
		}

        // Calculated
		if ($totalWeight > 0)
		{
			if (!empty($object->trueWeight)) print ' ('.$langs->trans("SumOfProductWeights").': ';
			//print $totalWeight.' '.measuring_units_string(0,"weight");
			print showDimensionInBestUnit($totalWeight, 0, "weight", $langs, isset($conf->global->MAIN_WEIGHT_DEFAULT_ROUND)?$conf->global->MAIN_WEIGHT_DEFAULT_ROUND:-1, isset($conf->global->MAIN_WEIGHT_DEFAULT_UNIT)?$conf->global->MAIN_WEIGHT_DEFAULT_UNIT:'no');
			//if (empty($object->trueWeight)) print ' ('.$langs->trans("Calculated").')';
			if (!empty($object->trueWeight)) print ')';
		}
		print '</td></tr>';

		// Width
		print '<tr><td>'.$form->editfieldkey("Width",'trueWidth',$object->trueWidth,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("Width",'trueWidth',$object->trueWidth,$object,$user->rights->expedition->creer);
		print ($object->trueWidth && $object->width_units!='')?' '.measuring_units_string($object->width_units,"size"):'';
		print '</td></tr>';

		// Height
		print '<tr><td>'.$form->editfieldkey("Height",'trueHeight',$object->trueHeight,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		if($action=='edittrueHeight')
		{
			print '<form name="settrueHeight" action="'.$_SERVER["PHP_SELF"].'" method="post">';
			print '<input name="action" value="settrueHeight" type="hidden">';
			print '<input name="id" value="'.$object->id.'" type="hidden">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input id="trueHeight" name="trueHeight" value="'.$object->trueHeight.'" type="text">';
			print $formproduct->select_measuring_units("size_units","size",$object->size_units);
			print ' <input class="button" name="modify" value="'.$langs->trans("Modify").'" type="submit">';
			print ' <input class="button" name="cancel" value="'.$langs->trans("Cancel").'" type="submit">';
			print '</form>';

		}
		else
		{
			print $object->trueHeight;
			print ($object->trueHeight && $object->height_units!='')?' '.measuring_units_string($object->height_units,"size"):'';
		}

		print '</td></tr>';

		// Depth
		print '<tr><td>'.$form->editfieldkey("Depth",'trueDepth',$object->trueDepth,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("Depth",'trueDepth',$object->trueDepth,$object,$user->rights->expedition->creer);
		print ($object->trueDepth && $object->depth_units!='')?' '.measuring_units_string($object->depth_units,"size"):'';
		print '</td></tr>';

		// Volume
		print '<tr><td>';
		print $langs->trans("Volume");
		print '</td>';
		print '<td colspan="3">';
		$calculatedVolume=0;
		$volumeUnit=0;
		if ($object->trueWidth && $object->trueHeight && $object->trueDepth)
		{
		    $calculatedVolume=($object->trueWidth * $object->trueHeight * $object->trueDepth);
		    $volumeUnit=$object->size_units * 3;
		}
		// If sending volume not defined we use sum of products
		if ($calculatedVolume > 0)
		{
			if ($volumeUnit < 50)
			{
			    //print $calculatedVolume.' '.measuring_units_string($volumeUnit,"volume");
			    print showDimensionInBestUnit($calculatedVolume, $volumeUnit, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND)?$conf->global->MAIN_VOLUME_DEFAULT_ROUND:-1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT)?$conf->global->MAIN_VOLUME_DEFAULT_UNIT:'no');
			}
			else print $calculatedVolume.' '.measuring_units_string($volumeUnit,"volume");
		}
		if ($totalVolume > 0)
		{
			if ($calculatedVolume) print ' ('.$langs->trans("SumOfProductVolumes").': ';
			//print $totalVolume.' '.measuring_units_string(0,"volume");
			print showDimensionInBestUnit($totalVolume, 0, "volume", $langs, isset($conf->global->MAIN_VOLUME_DEFAULT_ROUND)?$conf->global->MAIN_VOLUME_DEFAULT_ROUND:-1, isset($conf->global->MAIN_VOLUME_DEFAULT_UNIT)?$conf->global->MAIN_VOLUME_DEFAULT_UNIT:'no');
			//if (empty($calculatedVolume)) print ' ('.$langs->trans("Calculated").')';
			if ($calculatedVolume) print ')';
		}
		print "</td>\n";
		print '</tr>';

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		// Sending method
		print '<tr><td height="10">';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('SendingMethod');
		print '</td>';

		if ($action != 'editshipping_method_id') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editshipping_method_id&amp;id='.$object->id.'">'.img_edit($langs->trans('SetSendingMethod'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="2">';
		if ($action == 'editshipping_method_id')
		{
			print '<form name="setshipping_method_id" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="setshipping_method_id">';
			$object->fetch_delivery_methods();
			print $form->selectarray("shipping_method_id",$object->meths,$object->shipping_method_id,1,0,0,"",1);
			if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
			print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
			print '</form>';
		}
		else
		{
			if ($object->shipping_method_id > 0)
			{
				// Get code using getLabelFromKey
				$code=$langs->getLabelFromKey($db,$object->shipping_method_id,'c_shipment_mode','rowid','code');
				print $langs->trans("SendingMethod".strtoupper($code));
			}
		}
		print '</td>';
		print '</tr>';

		// Tracking Number
		print '<tr><td class="titlefield">'.$form->editfieldkey("TrackingNumber",'tracking_number',$object->tracking_number,$object,$user->rights->expedition->creer).'</td><td colspan="3">';
		print $form->editfieldval("TrackingNumber",'tracking_number',$object->tracking_url,$object,$user->rights->expedition->creer,'string',$object->tracking_number);
		print '</td></tr>';

		// Incoterms
		if (!empty($conf->incoterm->enabled))
		{
			print '<tr><td>';
	        print '<table width="100%" class="nobordernopadding"><tr><td>';
	        print $langs->trans('IncotermLabel');
	        print '<td><td align="right">';
	        if ($user->rights->expedition->creer) print '<a href="'.DOL_URL_ROOT.'/expedition/card.php?id='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
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

		print "</table>";

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';


		// Lines of products

		if ($action == 'editline')
		{
			print '	<form name="updateline" id="updateline" action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;lineid=' . $line_id . '" method="POST">
			<input type="hidden" name="token" value="' . $_SESSION ['newtoken'] . '">
			<input type="hidden" name="action" value="updateline">
			<input type="hidden" name="mode" value="">
			<input type="hidden" name="id" value="' . $object->id . '">
			';
		}
		print '<br>';

        print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		// #
		if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
		{
			print '<td width="5" align="center">&nbsp;</td>';
		}
		// Product/Service
		print '<td>'.$langs->trans("Products").'</td>';
		// Qty
		print '<td align="center">'.$langs->trans("QtyOrdered").'</td>';
		if ($origin && $origin_id > 0)
		{
			print '<td align="center">'.$langs->trans("QtyInOtherShipments").'</td>';
		}
		if ($action == 'editline')
		{
			$editColspan = 3;
			if (empty($conf->stock->enabled)) $editColspan--;
			if (empty($conf->productbatch->enabled)) $editColspan--;
			print '<td align="center" colspan="'. $editColspan . '">';
			if ($object->statut <= 1)
			{
				print $langs->trans("QtyToShip").' - ';
			}
			else
			{
				print $langs->trans("QtyShipped").' - ';
			}
			if (! empty($conf->stock->enabled))
			{
				print $langs->trans("WarehouseSource").' - ';
			}
			if (! empty($conf->productbatch->enabled))
			{
				print $langs->trans("Batch");
			}
			print '</td>';
		}
		else
		{
			if ($object->statut <= 1)
			{
				print '<td align="center">'.$langs->trans("QtyToShip").'</td>';
			}
			else
			{
				print '<td align="center">'.$langs->trans("QtyShipped").'</td>';
			}
			if (! empty($conf->stock->enabled))
			{
				print '<td align="left">'.$langs->trans("WarehouseSource").'</td>';
			}

			if (! empty($conf->productbatch->enabled))
			{
				print '<td align="left">'.$langs->trans("Batch").'</td>';
			}
		}
		print '<td align="center">'.$langs->trans("CalculatedWeight").'</td>';
		print '<td align="center">'.$langs->trans("CalculatedVolume").'</td>';
		//print '<td align="center">'.$langs->trans("Size").'</td>';
		if ($object->statut == 0)
		{
			print '<td class="linecoledit"></td>';
			print '<td class="linecoldelete" width="10"></td>';
		}
		print "</tr>\n";

		$var=false;

		if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
		{
			$object->fetch_thirdparty();
			$outputlangs = $langs;
			$newlang='';
			if (empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
			if (empty($newlang)) $newlang=$object->thirdparty->default_lang;
			if (! empty($newlang))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($newlang);
			}
		}

		// Get list of products already sent for same source object into $alreadysent
		$alreadysent = array();
		if ($origin && $origin_id > 0)
		{
    		$sql = "SELECT obj.rowid, obj.fk_product, obj.label, obj.description, obj.product_type as fk_product_type, obj.qty as qty_asked, obj.date_start, obj.date_end";
    		$sql.= ", ed.rowid as shipmentline_id, ed.qty as qty_shipped, ed.fk_expedition as expedition_id, ed.fk_origin_line, ed.fk_entrepot";
    		$sql.= ", e.rowid as shipment_id, e.ref as shipment_ref, e.date_creation, e.date_valid, e.date_delivery, e.date_expedition";
    		//if ($conf->livraison_bon->enabled) $sql .= ", l.rowid as livraison_id, l.ref as livraison_ref, l.date_delivery, ld.qty as qty_received";
    		$sql.= ', p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, p.tobatch as product_tobatch';
    		$sql.= ', p.description as product_desc';
    		$sql.= " FROM ".MAIN_DB_PREFIX."expeditiondet as ed";
    		$sql.= ", ".MAIN_DB_PREFIX."expedition as e";
    		$sql.= ", ".MAIN_DB_PREFIX.$origin."det as obj";
    		//if ($conf->livraison_bon->enabled) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."livraison as l ON l.fk_expedition = e.rowid LEFT JOIN ".MAIN_DB_PREFIX."livraisondet as ld ON ld.fk_livraison = l.rowid  AND obj.rowid = ld.fk_origin_line";
    		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON obj.fk_product = p.rowid";
    		$sql.= " WHERE e.entity IN (".getEntity('expedition').")";
    		$sql.= " AND obj.fk_".$origin." = ".$origin_id;
    		$sql.= " AND obj.rowid = ed.fk_origin_line";
    		$sql.= " AND ed.fk_expedition = e.rowid";
    		//if ($filter) $sql.= $filter;
    		$sql.= " ORDER BY obj.fk_product";

    		dol_syslog("get list of shipment lines", LOG_DEBUG);
    		$resql = $db->query($sql);
    		if ($resql)
    		{
    		    $num = $db->num_rows($resql);
    		    $i = 0;

    		    while($i < $num)
    		    {
        		    $obj = $db->fetch_object($resql);
        		    if ($obj)
        		    {
        		        // $obj->rowid is rowid in $origin."det" table
        		        $alreadysent[$obj->rowid][$obj->shipmentline_id]=array('shipment_ref'=>$obj->shipment_ref, 'shipment_id'=>$obj->shipment_id, 'warehouse'=>$obj->fk_entrepot, 'qty_shipped'=>$obj->qty_shipped, 'date_valid'=>$obj->date_valid, 'date_delivery'=>$obj->date_delivery);
        		    }
        		    $i++;
    		    }
    		}
    		//var_dump($alreadysent);
		}

		// Loop on each product to send/sent
		for ($i = 0 ; $i < $num_prod ; $i++)
		{
		    print '<!-- origin line id = '.$lines[$i]->origin_line_id.' -->'; // id of order line
			print '<tr class="oddeven">';

			// #
			if (! empty($conf->global->MAIN_VIEW_LINE_NUMBER))
			{
				print '<td align="center">'.($i+1).'</td>';
			}

			// Predefined product or service
			if ($lines[$i]->fk_product > 0)
			{
				// Define output language
				if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
				{
					$prod = new Product($db);
					$prod->fetch($lines[$i]->fk_product);
					$label = ( ! empty($prod->multilangs[$outputlangs->defaultlang]["label"])) ? $prod->multilangs[$outputlangs->defaultlang]["label"] : $lines[$i]->product_label;
				}
				else
					$label = (! empty($lines[$i]->label)?$lines[$i]->label:$lines[$i]->product_label);

				print '<td>';

				// Show product and description
				$product_static->type=$lines[$i]->fk_product_type;
				$product_static->id=$lines[$i]->fk_product;
				$product_static->ref=$lines[$i]->ref;
				$product_static->status_batch=$lines[$i]->product_tobatch;
				$text=$product_static->getNomUrl(1);
				$text.= ' - '.$label;
				$description=(! empty($conf->global->PRODUIT_DESC_IN_FORM)?'':dol_htmlentitiesbr($lines[$i]->description));
				print $form->textwithtooltip($text,$description,3,'','',$i);
				print_date_range($lines[$i]->date_start,$lines[$i]->date_end);
				if (! empty($conf->global->PRODUIT_DESC_IN_FORM))
				{
					print (! empty($lines[$i]->description) && $lines[$i]->description!=$lines[$i]->product)?'<br>'.dol_htmlentitiesbr($lines[$i]->description):'';
				}
				print "</td>\n";
			}
			else
			{
				print "<td>";
				if ($lines[$i]->product_type == Product::TYPE_SERVICE) $text = img_object($langs->trans('Service'),'service');
				else $text = img_object($langs->trans('Product'),'product');

				if (! empty($lines[$i]->label)) {
					$text.= ' <strong>'.$lines[$i]->label.'</strong>';
					print $form->textwithtooltip($text,$lines[$i]->description,3,'','',$i);
				} else {
					print $text.' '.nl2br($lines[$i]->description);
				}

				print_date_range($lines[$i]->date_start,$lines[$i]->date_end);
				print "</td>\n";
			}

			// Qty ordered
			print '<td align="center">'.$lines[$i]->qty_asked.'</td>';

			// Qty in other shipments (with shipment and warehouse used)
    		if ($origin && $origin_id > 0)
    		{
    			print '<td align="center" class="nowrap">';
    			foreach ($alreadysent as $key => $val)
    			{
    			    if ($lines[$i]->fk_origin_line == $key)
    			    {
    			        $j = 0;
    			        foreach($val as $shipmentline_id=> $shipmentline_var)
    			        {
    			            if ($shipmentline_var['shipment_id'] == $lines[$i]->fk_expedition) continue; // We want to show only "other shipments"

    			            $j++;
    			            if ($j > 1) print '<br>';
    			            $shipment_static->fetch($shipmentline_var['shipment_id']);
    			            print $shipment_static->getNomUrl(1);
    			            print ' - '.$shipmentline_var['qty_shipped'];
    			            $htmltext=$langs->trans("DateValidation").' : '.(empty($shipmentline_var['date_valid'])?$langs->trans("Draft"):dol_print_date($shipmentline_var['date_valid'], 'dayhour'));
    			            if (! empty($conf->stock->enabled) && $shipmentline_var['warehouse'] > 0)
    			            {
    			                $warehousestatic->fetch($shipmentline_var['warehouse']);
    			                $htmltext .= '<br>'.$langs->trans("From").' : '.$warehousestatic->getNomUrl(1);
    			            }
    			            print ' '.$form->textwithpicto('', $htmltext, 1);
    			        }
    			    }
    			}
    		}
			print '</td>';

			if ($action == 'editline' && $lines[$i]->id == $line_id)
			{
				// edit mode
				print '<td colspan="'.$editColspan.'" align="center"><table class="nobordernopadding">';
				if (is_array($lines[$i]->detail_batch) && count($lines[$i]->detail_batch) > 0)
				{
					print '<!-- case edit 1 -->';
					$line = new ExpeditionLigne($db);
					foreach ($lines[$i]->detail_batch as $detail_batch)
					{
						print '<tr>';
						// Qty to ship or shipped
						print '<td>' . '<input name="qtyl'.$detail_batch->fk_expeditiondet.'_'.$detail_batch->id.'" id="qtyl'.$line_id.'_'.$detail_batch->id.'" type="text" size="4" value="'.$detail_batch->dluo_qty.'">' . '</td>';
						// Batch number managment
						if ($lines[$i]->entrepot_id == 0)
						{
							// only show lot numbers from src warehouse when shipping from multiple warehouses
							$line->fetch($detail_batch->fk_expeditiondet);
						}
						print '<td>' . $formproduct->selectLotStock($detail_batch->fk_origin_stock, 'batchl'.$detail_batch->fk_expeditiondet.'_'.$detail_batch->fk_origin_stock, '', 1, 0, $lines[$i]->fk_product, $line->entrepot_id). '</td>';
						print '</tr>';
					}
					// add a 0 qty lot row to be able to add a lot
					print '<tr>';
					// Qty to ship or shipped
					print '<td>' . '<input name="qtyl'.$line_id.'_0" id="qtyl'.$line_id.'_0" type="text" size="4" value="0">' . '</td>';
					// Batch number managment
					print '<td>' . $formproduct->selectLotStock('', 'batchl'.$line_id.'_0', '', 1, 0, $lines[$i]->fk_product). '</td>';
					print '</tr>';
				}
				else if (! empty($conf->stock->enabled))
				{
					if ($lines[$i]->fk_product > 0)
					{
						if ($lines[$i]->entrepot_id > 0)
						{
							print '<!-- case edit 2 -->';
							print '<tr>';
							// Qty to ship or shipped
							print '<td>' . '<input name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty_shipped.'">' . '</td>';
							// Warehouse source
							print '<td>' . $formproduct->selectWarehouses($lines[$i]->entrepot_id, 'entl'.$line_id, '', 1, 0, $lines[$i]->fk_product, '', 1). '</td>';
							// Batch number managment
							print '<td> - ' . $langs->trans("NA") . '</td>';
							print '</tr>';
						}
						else if (count($lines[$i]->details_entrepot) > 1)
						{
							print '<!-- case edit 3 -->';
							foreach ($lines[$i]->details_entrepot as $detail_entrepot)
							{
								print '<tr>';
								// Qty to ship or shipped
								print '<td>' . '<input name="qtyl'.$detail_entrepot->line_id.'" id="qtyl'.$detail_entrepot->line_id.'" type="text" size="4" value="'.$detail_entrepot->qty_shipped.'">' . '</td>';
								// Warehouse source
								print '<td>' . $formproduct->selectWarehouses($detail_entrepot->entrepot_id, 'entl'.$detail_entrepot->line_id, '', 1, 0, $lines[$i]->fk_product, '', 1) . '</td>';
								// Batch number managment
								print '<td> - ' . $langs->trans("NA") . '</td>';
								print '</tr>';
							}
						}
						else
						{
							print '<!-- case edit 4 -->';
							print '<tr><td colspan="3">'.$langs->trans("NotEnoughStock").'</td></tr>';
						}
					}
					else
					{
						print '<!-- case edit 5 -->';
						print '<tr>';
						// Qty to ship or shipped
						print '<td>' . '<input name="qtyl'.$line_id.'" id="qtyl'.$line_id.'" type="text" size="4" value="'.$lines[$i]->qty_shipped.'">' . '</td>';
						// Warehouse source
						print '<td>' . '</td>';
						// Batch number managment
						print '<td>' . '</td>';
						print '</tr>';
					}
				}
				print '</table></td>';
			}
			else
			{
				// Qty to ship or shipped
				print '<td align="center">'.$lines[$i]->qty_shipped.'</td>';

				// Warehouse source
				if (! empty($conf->stock->enabled))
				{
					print '<td align="left">';
					if ($lines[$i]->entrepot_id > 0)
					{
						$entrepot = new Entrepot($db);
						$entrepot->fetch($lines[$i]->entrepot_id);
						print $entrepot->getNomUrl(1);
					}
					else if (count($lines[$i]->details_entrepot) > 1)
					{
						$detail = '';
						foreach ($lines[$i]->details_entrepot as $detail_entrepot)
						{
							if ($detail_entrepot->entrepot_id > 0)
							{
								$entrepot = new Entrepot($db);
								$entrepot->fetch($detail_entrepot->entrepot_id);
								$detail.= $langs->trans("DetailWarehouseFormat",$entrepot->libelle,$detail_entrepot->qty_shipped).'<br/>';
							}
						}
						print $form->textwithtooltip(img_picto('', 'object_stock').' '.$langs->trans("DetailWarehouseNumber"),$detail);
					}
					print '</td>';
				}

				// Batch number managment
				if (! empty($conf->productbatch->enabled))
				{
					if (isset($lines[$i]->detail_batch))
					{
						print '<!-- Detail of lot -->';
						print '<td>';
						if ($lines[$i]->product_tobatch)
						{
							$detail = '';
							foreach ($lines[$i]->detail_batch as $dbatch)
							{
								$detail.= $langs->trans("Batch").': '.$dbatch->batch;
								$detail.= ' - '.$langs->trans("SellByDate").': '.dol_print_date($dbatch->sellby,"day");
								$detail.= ' - '.$langs->trans("EatByDate").': '.dol_print_date($dbatch->eatby,"day");
								$detail.= ' - '.$langs->trans("Qty").': '.$dbatch->dluo_qty;
								$detail.= '<br>';
							}
							print $form->textwithtooltip(img_picto('', 'object_barcode').' '.$langs->trans("DetailBatchNumber"),$detail);
						}
						else
						{
							print $langs->trans("NA");
						}
						print '</td>';
					} else {
						print '<td></td>';
					}
				}
			}

			// Weight
			print '<td align="center">';
			if ($lines[$i]->fk_product_type == Product::TYPE_PRODUCT) print $lines[$i]->weight*$lines[$i]->qty_shipped.' '.measuring_units_string($lines[$i]->weight_units,"weight");
			else print '&nbsp;';
			print '</td>';

			// Volume
			print '<td align="center">';
			if ($lines[$i]->fk_product_type == Product::TYPE_PRODUCT) print $lines[$i]->volume*$lines[$i]->qty_shipped.' '.measuring_units_string($lines[$i]->volume_units,"volume");
			else print '&nbsp;';
			print '</td>';

			// Size
			//print '<td align="center">'.$lines[$i]->volume*$lines[$i]->qty_shipped.' '.measuring_units_string($lines[$i]->volume_units,"volume").'</td>';

			if ($action == 'editline' && $lines[$i]->id == $line_id)
			{
				print '<td align="center" colspan="2" valign="middle">';
				print '<input type="submit" class="button" id="savelinebutton" name="save" value="' . $langs->trans("Save") . '"><br>';
				print '<input type="submit" class="button" id="cancellinebutton" name="cancel" value="' . $langs->trans("Cancel") . '"><br>';
			}
			else if ($object->statut == 0)
			{
				// edit-delete buttons
				print '<td class="linecoledit" align="center">';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=editline&amp;lineid=' . $lines[$i]->id . '">' . img_edit() . '</a>';
				print '</td>';
				print '<td class="linecoldelete" width="10">';
				print '<a href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&amp;action=deleteline&amp;lineid=' . $lines[$i]->id . '">' . img_delete() . '</a>';
				print '</td>';

				// Display lines extrafields
				if (! empty($rowExtrafieldsStart))
				{
					print $rowExtrafieldsStart;
					print $rowExtrafieldsView;
					print $rowEnd;
				}
			}
			print "</tr>";

			// Display lines extrafields
			if (is_array($extralabelslines) && count($extralabelslines)>0) {
				$colspan= empty($conf->productbatch->enabled) ? 5 : 6;
				$line = new ExpeditionLigne($db);
				$line->fetch_optionals($lines[$i]->id);
				print '<tr class="oddeven">';
				if ($action == 'editline' && $lines[$i]->id == $line_id)
				{
					print $line->showOptionals($extrafieldsline, 'edit', array('style'=>$bc[$var], 'colspan'=>$colspan),$indiceAsked);
				}
				else
				{
					print $line->showOptionals($extrafieldsline, 'view', array('style'=>$bc[$var], 'colspan'=>$colspan),$indiceAsked);
				}
				print '</tr>';
			}
		}

		// TODO Show also lines ordered but not delivered

		print "</table>\n";
		print '</div>';
	}


	dol_fiche_end();


	$object->fetchObjectLinked($object->id,$object->element);


	/*
	 *    Boutons actions
	 */

	if (($user->societe_id == 0) && ($action!='presend'))
	{
		print '<div class="tabsAction">';

		$parameters = array();
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
		                                                                                               // modified by hook
		if (empty($reshook))
		{

			if ($object->statut == Expedition::STATUS_DRAFT && $num_prod > 0)
			{
				if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->creer))
	  		     || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expedition->shipping_advance->validate)))
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid">'.$langs->trans("Validate").'</a>';
				}
				else
				{
					print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotAllowed").'">'.$langs->trans("Validate").'</a>';
				}
			}

			// TODO add alternative status
			// 0=draft, 1=validated, 2=billed, we miss a status "delivered" (only available on order)
			if ($object->statut == Expedition::STATUS_CLOSED && $user->rights->expedition->creer)
			{
				if (! empty($conf->facture->enabled) && ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))  // Quand l'option est on, il faut avoir le bouton en plus et non en remplacement du Close ?
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ClassifyUnbilled").'</a>';
				}
				else
				{
			    	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans("ReOpen").'</a>';
				}
			}

			// Send
			if ($object->statut > 0)
			{
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->expedition->shipping_advance->send)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendByMail').'</a>';
				}
				else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
			}

			// Create bill
			if (! empty($conf->facture->enabled) && ($object->statut == Expedition::STATUS_VALIDATED || $object->statut == Expedition::STATUS_CLOSED))
			{
				if ($user->rights->facture->creer)
				{
					// TODO show button only   if (! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))
					// If we do that, we must also make this option official.
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture/card.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
				}
			}

			// This is just to generate a delivery receipt
			//var_dump($object->linkedObjectsIds['delivery']);
			if ($conf->livraison_bon->enabled && ($object->statut == Expedition::STATUS_VALIDATED || $object->statut == Expedition::STATUS_CLOSED) && $user->rights->expedition->livraison->creer && count($object->linkedObjectsIds['delivery']) == 0)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=create_delivery">'.$langs->trans("CreateDeliveryOrder").'</a>';
			}
			// Close
			if ($object->statut == Expedition::STATUS_VALIDATED)
			{
				if ($user->rights->expedition->creer && $object->statut > 0 && ! $object->billed)
				{
					$label="Close"; $paramaction='classifyclosed';       // = Transferred/Received
					// Label here should be "Close" or "ClassifyBilled" if we decided to make bill on shipments instead of orders
					if (! empty($conf->facture->enabled) && ! empty($conf->global->WORKFLOW_BILL_ON_SHIPMENT))  // Quand l'option est on, il faut avoir le bouton en plus et non en remplacement du Close ?
					{
					    $label="ClassifyBilled";
					    $paramaction='classifybilled';
					}
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action='.$paramaction.'">'.$langs->trans($label).'</a>';
				}
			}

			if ($user->rights->expedition->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans("Delete").'</a>';
			}

		}

		print '</div>';
	}


	/*
	 * Documents generated
	 */

	if ($action != 'presend' && $action != 'editline')
	{
        print '<div class="fichecenter"><div class="fichehalfleft">';

        $objectref = dol_sanitizeFileName($object->ref);
		$filedir = $conf->expedition->dir_output . "/sending/" .$objectref;

		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;

		$genallowed=$user->rights->expedition->lire;
		$delallowed=$user->rights->expedition->creer;

		print $formfile->showdocuments('expedition',$objectref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);


		// Show links to link elements
		//$linktoelem = $form->showLinkToObjectBlock($object, null, array('order'));
		$somethingshown = $form->showLinkedObjectBlock($object, '');


		print '</div><div class="fichehalfright"><div class="ficheaddleft">';

		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions=new FormActions($db);
		$somethingshown = $formactions->showactions($object,'shipping',$socid,1);

		print '</div></div></div>';
	}


	/*
	 * Action presend
	 */

	//Select mail models is same action as presend
	if (GETPOST('modelselected')) {
		$action = 'presend';
	}

	// Presend form
	$modelmail='shipping_send';
	$defaulttopic='SendShippingRef';
	$diroutput = $conf->expedition->dir_output. '/sending';
	$trackid = 'shi'.$object->id;

	include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';
}


llxFooter();

$db->close();
