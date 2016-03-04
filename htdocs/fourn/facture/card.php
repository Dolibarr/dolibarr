<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.fr>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2015	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
 * Copyright (C) 2014       Marcos García           <marcosgdf@gmail.com>
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
 *	\file       htdocs/fourn/facture/card.php
 *	\ingroup    facture, fournisseur
 *	\brief      Page for supplier invoice card (view, edit, validate)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_invoice/modules_facturefournisseur.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
if (!empty($conf->produit->enabled))
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}


$langs->load('bills');
$langs->load('compta');
$langs->load('suppliers');
$langs->load('companies');
$langs->load('products');
$langs->load('banks');
if (!empty($conf->incoterm->enabled)) $langs->load('incoterm');

$id			= (GETPOST('facid','int') ? GETPOST('facid','int') : GETPOST('id','int'));
$action		= GETPOST("action");
$confirm	= GETPOST("confirm");
$ref		= GETPOST('ref','alpha');
$cancel     = GETPOST('cancel','alpha');
$lineid     = GETPOST('lineid', 'int');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicesuppliercard','globalcard'));

$object=new FactureFournisseur($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
	$object->fetch_thirdparty();
}

$permissionnote=$user->rights->fournisseur->facture->creer;	// Used by the include of actions_setnotes.inc.php
$permissiondellink=$user->rights->fournisseur->facture->creer;	// Used by the include of actions_dellink.inc.php
$permissionedit=$user->rights->fournisseur->facture->creer; // Used by the include of actions_lineupdown.inc.php


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

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes')
	{
	//    if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	//    {
	//        $mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	//    }
	//    else
	//    {
	        $result=$object->createFromClone($id);
	        if ($result > 0)
	        {
	            header("Location: ".$_SERVER['PHP_SELF'].'?action=editref_supplier&id='.$result);
	            exit;
	        }
	        else
	        {
	            $langs->load("errors");
		        setEventMessages($langs->trans($object->error), null, 'errors');
	            $action='';
	        }
	//    }
	}

	elseif ($action == 'confirm_valid' && $confirm == 'yes' &&
	    ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->facture->creer))
	    || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_invoice_advance->validate)))
	)
	{
	    $idwarehouse=GETPOST('idwarehouse');

	    $object->fetch($id);
	    $object->fetch_thirdparty();

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
	    if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change)
	    {
	        $langs->load("stocks");
	        if (! $idwarehouse || $idwarehouse == -1)
	        {
	            $error++;
		        setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse")), null, 'errors');
	            $action='';
	        }
	    }

	    if (! $error)
	    {
	        $result = $object->validate($user,'',$idwarehouse);
	        if ($result < 0)
	        {
	            setEventMessages($object->error,$object->errors,'errors');
	        }
	    }
	}

	elseif ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->fournisseur->facture->supprimer)
	{
	    $object->fetch($id);
	    $object->fetch_thirdparty();
	    $result=$object->delete($id);
	    if ($result > 0)
	    {
	        header('Location: list.php');
	        exit;
	    }
	    else
	    {
		    setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	// Remove a product line
	else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->fournisseur->facture->creer)
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

	elseif ($action == 'confirm_paid' && $confirm == 'yes' && $user->rights->fournisseur->facture->creer)
	{
	    $object->fetch($id);
	    $result=$object->set_paid($user);
	    if ($result<0)
	    {
	        setEventMessages($object->error, $object->errors, 'errors');
	    }
	}

	// Set supplier ref
	if ($action == 'setref_supplier' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setValueFrom('ref_supplier',GETPOST('ref_supplier','alpha'));
	    if ($result < 0) dol_print_error($db, $object->error);
	}

	// payments conditions
	if ($action == 'setconditions' && $user->rights->fournisseur->commande->creer)
	{
	    $result=$object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
	}

	// payment mode
	else if ($action == 'setmode' && $user->rights->fournisseur->commande->creer)
	{
	    $result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
	}

	// bank account
	else if ($action == 'setbankaccount' && $user->rights->fournisseur->facture->creer) {
	    $result=$object->setBankAccount(GETPOST('fk_account', 'int'));
	}

	// Set label
	elseif ($action == 'setlabel' && $user->rights->fournisseur->facture->creer)
	{
	    $object->fetch($id);
	    $object->label=$_POST['label'];
	    $result=$object->update($user);
	    if ($result < 0) dol_print_error($db);
	}
	elseif ($action == 'setdatef' && $user->rights->fournisseur->facture->creer)
	{
	    $object->fetch($id);
	    $object->date=dol_mktime(12,0,0,$_POST['datefmonth'],$_POST['datefday'],$_POST['datefyear']);
	    if ($object->date_echeance && $object->date_echeance < $object->date) $object->date_echeance=$object->date;
	    $result=$object->update($user);
	    if ($result < 0) dol_print_error($db,$object->error);
	}
	elseif ($action == 'setdate_lim_reglement' && $user->rights->fournisseur->facture->creer)
	{
	    $object->fetch($id);
	    $object->date_echeance=dol_mktime(12,0,0,$_POST['date_lim_reglementmonth'],$_POST['date_lim_reglementday'],$_POST['date_lim_reglementyear']);
	    if (! empty($object->date_echeance) && $object->date_echeance < $object->date)
	    {
	    	$object->date_echeance=$object->date;
	    	setEventMessages($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"), null, 'warnings');
	    }
	    $result=$object->update($user);
	    if ($result < 0) dol_print_error($db,$object->error);
	}

	// Delete payment
	elseif ($action == 'deletepaiement' && $user->rights->fournisseur->facture->creer)
	{
	    $object->fetch($id);
	    if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0)
	    {
	    	$paiementfourn = new PaiementFourn($db);
	        $result=$paiementfourn->fetch(GETPOST('paiement_id'));
	        if ($result > 0) $result=$paiementfourn->delete(); // If fetch ok and found
	        if ($result < 0) {
		        setEventMessages($paiementfourn->error, $paiementfourn->errors, 'errors');
	        }
	    }
	}

	// Create
	elseif ($action == 'add' && $user->rights->fournisseur->facture->creer)
	{
	    $error=0;

	    $datefacture=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
	    $datedue=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);

	    if (GETPOST('socid','int')<1)
	    {
		    setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')), null, 'errors');
	    	$action='create';
	    	$error++;
	    }

	    if ($datefacture == '')
	    {
		    setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('DateInvoice')), null, 'errors');
	        $action='create';
	        $_GET['socid']=$_POST['socid'];
	        $error++;
	    }
	    if (! GETPOST('ref_supplier'))
	    {
		    setEventMessages($langs->trans('ErrorFieldRequired',$langs->transnoentities('RefSupplier')), null, 'errors');
	        $action='create';
	        $_GET['socid']=$_POST['socid'];
	        $error++;
	    }

	    // Fill array 'array_options' with data from add form

	    if (! $error)
	    {
	        $db->begin();

	        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
			if ($ret < 0) $error++;

	        $tmpproject = GETPOST('projectid', 'int');

	        // Creation facture
	        $object->ref           = $_POST['ref'];
			$object->ref_supplier  = $_POST['ref_supplier'];
	        $object->socid         = $_POST['socid'];
	        $object->libelle       = $_POST['libelle'];
	        $object->date          = $datefacture;
	        $object->date_echeance = $datedue;
	        $object->note_public   = GETPOST('note_public');
	        $object->note_private  = GETPOST('note_private');
			$object->cond_reglement_id = GETPOST('cond_reglement_id');
	        $object->mode_reglement_id = GETPOST('mode_reglement_id');
	        $object->fk_account        = GETPOST('fk_account', 'int');
	        $object->fk_project    = ($tmpproject > 0) ? $tmpproject : null;
			$object->fk_incoterms = GETPOST('incoterm_id', 'int');
	        $object->location_incoterms = GETPOST('location_incoterms', 'alpha');

			// Auto calculation of date due if not filled by user
			if(empty($object->date_echeance)) $object->date_echeance = $object->calculate_date_lim_reglement();

	        // If creation from another object of another module
	        if (! $error && $_POST['origin'] && $_POST['originid'])
	        {
	            // Parse element/subelement (ex: project_task)
	            $element = $subelement = $_POST['origin'];
	            /*if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
	             {
	            $element = $regs[1];
	            $subelement = $regs[2];
	            }*/

	            // For compatibility
	            if ($element == 'order')    {
	                $element = $subelement = 'commande';
	            }
	            if ($element == 'propal')   {
	                $element = 'comm/propal'; $subelement = 'propal';
	            }
	            if ($element == 'contract') {
	                $element = $subelement = 'contrat';
	            }
	            if ($element == 'order_supplier') {
	                $element = 'fourn'; $subelement = 'fournisseur.commande';
	            }
	            if ($element == 'project')
	            {
	            	$element = 'projet';
	            }
	            $object->origin    = $_POST['origin'];
	            $object->origin_id = $_POST['originid'];

	            $id = $object->create($user);

	            // Add lines
	            if ($id > 0)
	            {
	                require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
	                $classname = ucfirst($subelement);
	                if ($classname == 'Fournisseur.commande') $classname='CommandeFournisseur';
	                $srcobject = new $classname($db);

	                $result=$srcobject->fetch($_POST['originid']);
	                if ($result > 0)
	                {
	                    $lines = $srcobject->lines;
	                    if (empty($lines) && method_exists($srcobject,'fetch_lines'))
	                    {
	                    	$srcobject->fetch_lines();
	                    	$lines = $srcobject->lines;
	                    }

	                    $num=count($lines);
	                    for ($i = 0; $i < $num; $i++)
	                    {
	                        $desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
	                        $product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);

	                        // Dates
	                        // TODO mutualiser
	                        $date_start=$lines[$i]->date_debut_prevue;
	                        if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
	                        if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
	                        $date_end=$lines[$i]->date_fin_prevue;
	                        if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
	                        if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;

	                        // FIXME Missing $lines[$i]->ref_supplier and $lines[$i]->label into addline and updateline methods. They are filled when coming from order for example.
	                        $result = $object->addline(
	                            $desc,
	                            $lines[$i]->subprice,
	                            $lines[$i]->tva_tx,
	                            $lines[$i]->localtax1_tx,
	                            $lines[$i]->localtax2_tx,
	                            $lines[$i]->qty,
	                            $lines[$i]->fk_product,
	                            $lines[$i]->remise_percent,
	                            $date_start,
	                            $date_end,
	                            0,
	                            $lines[$i]->info_bits,
	                            'HT',
	                            $product_type,
	                        	$lines[$i]->rang,
	                        	0,
	                        	$lines[$i]->array_options,
	                        	$lines[$i]->fk_unit
	                        );

	                        if ($result < 0)
	                        {
	                            $error++;
	                            break;
	                        }
	                    }
	                    
	                    // Now reload line
	                    $object->fetch_lines();
	                }
	                else
	                {
	                    $error++;
	                }
	            }
	            else
	            {
	                $error++;
	            }
	        }
	        else if (! $error)
	        {
	            $id = $object->create($user);
	            if ($id < 0)
	            {
	                $error++;
	            }
	            
	            if (! $error)
	            {
        	        // If some invoice's lines already known
	                for ($i = 1 ; $i < 9 ; $i++)
	                {
	                    $label = $_POST['label'.$i];
	                    $amountht  = price2num($_POST['amount'.$i]);
	                    $amountttc = price2num($_POST['amountttc'.$i]);
	                    $tauxtva   = price2num($_POST['tauxtva'.$i]);
	                    $qty = $_POST['qty'.$i];
	                    $fk_product = $_POST['fk_product'.$i];
	                    if ($label)
	                    {
	                        if ($amountht)
	                        {
	                            $price_base='HT'; $amount=$amountht;
	                        }
	                        else
	                        {
	                            $price_base='TTC'; $amount=$amountttc;
	                        }
	                        $atleastoneline=1;

	                        $product=new Product($db);
	                        $product->fetch($_POST['idprod'.$i]);

	                        $ret=$object->addline($label, $amount, $tauxtva, $product->localtax1_tx, $product->localtax2_tx, $qty, $fk_product, $remise_percent, '', '', '', 0, $price_base, $_POST['rang'.$i], 1);
	                        if ($ret < 0) $error++;
	                    }
	                }
	            }
	        }

	        if ($error)
	        {
	            $langs->load("errors");
	            $db->rollback();
	            
		        setEventMessages($object->error, $object->errors, 'errors');
	            $action='create';
	            $_GET['socid']=$_POST['socid'];
	        }
	        else
	        {
	            $db->commit();

	            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
		            $outputlangs = $langs;
		            $result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	            	if ($result	<= 0)
	            	{
	            		dol_print_error($db,$object->error,$object->errors);
	            		exit;
	            	}
	            }

	            header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	            exit;
	        }
	    }
	}

	// Edit line
	elseif ($action == 'updateline' && $user->rights->fournisseur->facture->creer)
	{
		$db->begin();

			$object->fetch($id);
	        $object->fetch_thirdparty();

	        $tva_tx = GETPOST('tva_tx');

			if (GETPOST('price_ht') != '')
	    	{
	    		$up = price2num(GETPOST('price_ht'));
	    		$price_base_type = 'HT';
	    	}
	    	else
	    	{
	    		$up = price2num(GETPOST('price_ttc'));
	    		$price_base_type = 'TTC';
	    	}

	        if (GETPOST('productid'))
	        {
	            $prod = new Product($db);
	            $prod->fetch(GETPOST('productid'));
	            $label = $prod->description;
	            if (trim($_POST['product_desc']) != trim($label)) $label=$_POST['product_desc'];

	            $type = $prod->type;
	        }
	        else
	        {
	            $label = $_POST['product_desc'];
	            $type = $_POST["type"]?$_POST["type"]:0;
	        }

		    $date_start=dol_mktime(GETPOST('date_starthour'), GETPOST('date_startmin'), GETPOST('date_startsec'), GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
		    $date_end=dol_mktime(GETPOST('date_endhour'), GETPOST('date_endmin'), GETPOST('date_endsec'), GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

	        $localtax1_tx= get_localtax($_POST['tauxtva'], 1, $mysoc,$object->thirdparty);
	        $localtax2_tx= get_localtax($_POST['tauxtva'], 2, $mysoc,$object->thirdparty);
	        $remise_percent=GETPOST('remise_percent');

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

	        $result=$object->updateline(GETPOST('lineid'), $label, $up, $tva_tx, $localtax1_tx, $localtax2_tx, GETPOST('qty'), GETPOST('productid'), $price_base_type, 0, $type, $remise_percent, 0, $date_start, $date_end, $array_options, $_POST['units']);
	        if ($result >= 0)
	        {
	            unset($_POST['label']);
	            $db->commit();
	        }
	        else
	        {
	        	$db->rollback();
	            setEventMessages($object->error, $object->errors, 'errors');
	        }
	}

	elseif ($action == 'addline' && $user->rights->fournisseur->facture->creer)
	{
		$db->begin();

	    $ret=$object->fetch($id);
	    if ($ret < 0)
	    {
	        dol_print_error($db,$object->error);
	        exit;
	    }
	    $ret=$object->fetch_thirdparty();

	    $langs->load('errors');
		$error=0;

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

		$date_start=dol_mktime(GETPOST('date_start'.$predef.'hour'), GETPOST('date_start'.$predef.'min'), GETPOST('date_start' . $predef . 'sec'), GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end=dol_mktime(GETPOST('date_end'.$predef.'hour'), GETPOST('date_end'.$predef.'min'), GETPOST('date_end' . $predef . 'sec'), GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

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

	    if (GETPOST('prod_entry_mode') != 'free')	// With combolist mode idprodfournprice is > 0 or -1. With autocomplete, idprodfournprice is > 0 or ''
	    {
	    	$idprod=0;
	    	$productsupplier=new ProductFournisseur($db);

	        if (GETPOST('idprodfournprice') == -1 || GETPOST('idprodfournprice') == '') $idprod=-2;	// Same behaviour than with combolist. When not select idprodfournprice is now -2 (to avoid conflict with next action that may return -1)

	    	if (GETPOST('idprodfournprice') > 0)
	    	{
	    		$idprod=$productsupplier->get_buyprice(GETPOST('idprodfournprice'), $qty);    // Just to see if a price exists for the quantity. Not used to found vat.
	    	}

		    //Replaces $fk_unit with the product's
	        if ($idprod > 0)
	        {
	            $result=$productsupplier->fetch($idprod);

	            $label = $productsupplier->label;

	            $desc = $productsupplier->description;
	            if (trim($product_desc) != trim($desc)) $desc = dol_concatdesc($desc, $product_desc);

	            $tva_tx=get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, $_POST['idprodfournprice']);
	            $tva_npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, $_POST['idprodfournprice']);
				if (empty($tva_tx)) $tva_npr=0;
	            $localtax1_tx= get_localtax($tva_tx, 1, $mysoc, $object->thirdparty, $tva_npr);
	            $localtax2_tx= get_localtax($tva_tx, 2, $mysoc, $object->thirdparty, $tva_npr);

	            $type = $productsupplier->type;
	            $price_base_type = 'HT';

	            // TODO Save the product supplier ref into database into field ref_supplier (must rename field ref into ref_supplier first)
	            $result=$object->addline($desc, $productsupplier->fourn_pu, $tva_tx, $localtax1_tx, $localtax2_tx, $qty, $idprod, $remise_percent, $date_start, $date_end, 0, $tva_npr, $price_base_type, $type, -1, 0, $array_options, $productsupplier->fk_unit);
	        }
	    	if ($idprod == -2 || $idprod == 0)
	        {
	            // Product not selected
	            $error++;
	            $langs->load("errors");
		        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ProductOrService")), null, 'errors');
	        }
	        if ($idprod == -1)
	        {
	            // Quantity too low
	            $error++;
	            $langs->load("errors");
		        setEventMessages($langs->trans("ErrorQtyTooLowForThisSupplier"), null, 'errors');
	        }
	    }
	    else if( GETPOST('price_ht')!=='' || GETPOST('price_ttc')!=='' )
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
	    	$localtax1_tx= get_localtax($tva_tx, 1,$mysoc,$object->thirdparty);
	    	$localtax2_tx= get_localtax($tva_tx, 2,$mysoc,$object->thirdparty);

	    	if (!empty($_POST['price_ht']))
	    	{
	    		$ht = price2num($_POST['price_ht']);
	            $price_base_type = 'HT';
	        }
	        else
			{
	    		$ttc = price2num($_POST['price_ttc']);
	            $ht = $ttc / (1 + ($tva_tx / 100));
	            $price_base_type = 'HT';
	        }

			$result=$object->addline($product_desc, $ht, $tva_tx, $localtax1_tx, $localtax2_tx, $qty, 0, $remise_percent, $date_start, $date_end, 0, $tva_npr, $price_base_type, $type, -1, 0, $array_options, $fk_unit);
	    }

	    //print "xx".$tva_tx; exit;
	    if (! $error && $result > 0)
	    {
	    	$db->commit();

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
	    }
	    else
		{
	    	$db->rollback();
		    setEventMessages($object->error, $object->errors, 'errors');
	    }

	    $action = '';
	}

	elseif ($action == 'classin')
	{
	    $object->fetch($id);
	    $result=$object->setProject($_POST['projectid']);
	}


	// Set invoice to draft status
	elseif ($action == 'edit' && $user->rights->fournisseur->facture->creer)
	{
	    $object->fetch($id);

	    $totalpaye = $object->getSommePaiement();
	    $resteapayer = $object->total_ttc - $totalpaye;

	    // On verifie si les lignes de factures ont ete exportees en compta et/ou ventilees
	    //$ventilExportCompta = $object->getVentilExportCompta();

	    // On verifie si aucun paiement n'a ete effectue
	    if ($resteapayer == $object->total_ttc	&& $object->paye == 0 && $ventilExportCompta == 0)
	    {
	        $object->set_draft($user);

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

	        $action='';
	    }
	}

	// Set invoice to validated/unpaid status
	elseif ($action == 'reopen' && $user->rights->fournisseur->facture->creer)
	{
	    $result = $object->fetch($id);
	    if ($object->statut == FactureFournisseur::STATUS_CLOSED
	    || ($object->statut == FactureFournisseur::STATUS_ABANDONED && $object->close_code != 'replaced'))
	    {
	        $result = $object->set_unpaid($user);
	        if ($result > 0)
	        {
	            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
	            exit;
	        }
	        else
	        {
		        setEventMessages($object->error, $object->errors, 'errors');
	        }
	    }
	}

	// Link invoice to order
	if (GETPOST('linkedOrder')) {
		$object->fetch($id);
		$object->fetch_thirdparty();
		$result = $object->add_object_linked('order_supplier', GETPOST('linkedOrder'));
	}

	// Add file in email form
	if (GETPOST('addfile'))
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    // Set tmp user directory TODO Use a dedicated directory for temp mails files
	    $vardir=$conf->user->dir_output."/".$user->id;
	    $upload_dir_tmp = $vardir.'/temp';

	    dol_add_file_process($upload_dir_tmp,0,0);
	    $action='presend';
	}

	// Remove file in email form
	if (! empty($_POST['removedfile']))
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    // Set tmp user directory
	    $vardir=$conf->user->dir_output."/".$user->id;
	    $upload_dir_tmp = $vardir.'/temp';

		// TODO Delete only files that was uploaded from email form
	    dol_remove_file_process(GETPOST('removedfile','alpha'),0);
	    $action='presend';
	}

	// Send mail
	if ($action == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
	{
	    $langs->load('mails');

	    $object->fetch($id);
	    $result=$object->fetch_thirdparty();
	    if ($result > 0)
	    {
            if ($_POST['sendto'])
            {
                // Le destinataire a ete fourni via le champ libre
                $sendto = $_POST['sendto'];
                $sendtoid = 0;
            }
            elseif ($_POST['receiver'] != '-1')
            {
                // Recipient was provided from combo list
                if ($_POST['receiver'] == 'thirdparty') // Id of third party
                {
                    $sendto = $object->client->email;
                    $sendtoid = 0;
                }
                else	// Id du contact
                {
                    $sendto = $object->client->contact_get_property($_POST['receiver'],'email');
                    $sendtoid = $_POST['receiver'];
                }
            }

            if (dol_strlen($sendto))
            {
                $langs->load("commercial");

                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
                $message = $_POST['message'];
                $sendtocc = $_POST['sendtocc'];
                $deliveryreceipt = $_POST['deliveryreceipt'];

                if ($action == 'send')
                {
                    if (dol_strlen($_POST['subject'])) $subject=$_POST['subject'];
                    else $subject = $langs->transnoentities('CustomerOrder').' '.$object->ref;
                    $actiontypecode='AC_SUP_INV';
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
                    setEventMessages($mailfile->error, $mailfile->errors, 'errors');
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));		// Must not contain "
                        setEventMessages($mesg, null, 'mesgs');

                        $error=0;

                        // Init data for trigger
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;
                        
                        $object->email_subject  = $subject;
                        $object->email_to       = $sendto;
                        $object->email_tocc     = $sendtocc;
                        $object->email_tobcc    = $sendtobcc;
                        $object->email_from     = $from;
                        $object->email_content  = $_POST['message'];

                        // Call triggers
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('BILL_SUPPLIER_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) {
                            $error++; $object->errors=$interface->errors;
                        }
                        // End call triggers

                        if ($error)
                        {
                            dol_print_error($db);
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
                            $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
                            $mesg.='<br>'.$mailfile->error;
                        }
                        else
                        {
                            $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                        }
	                    setEventMessages($mesg, null, 'errors');
                    }
                }
            }

            else
            {
                $langs->load("other");
	            setEventMessages($langs->trans('ErrorMailRecipientIsEmpty'), null, 'errors');
                dol_syslog('Recipient email is empty');
            }
	    }
	    else
	    {
	        $langs->load("other");
		    setEventMessages($langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")), null, 'errors');
	        dol_syslog('Unable to read data from the invoice. The invoice file has perhaps not been generated.');
	    }

	    //$action = 'presend';
	}

	// Build document
	if ($action == 'builddoc')
	{
		// Save modele used
	    $object->fetch($id);
	    $object->fetch_thirdparty();

		// Save last template used to generate document
		if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

	    $outputlangs = $langs;
	    $newlang=GETPOST('lang_id','alpha');
	    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
	    if (! empty($newlang))
	    {
	        $outputlangs = new Translate("",$conf);
	        $outputlangs->setDefaultLang($newlang);
	    }
		$result = $object->generateDocument($object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	    if ($result	<= 0)
	    {
			setEventMessages($object->error, $object->errors, 'errors');
    	    $action='';
	    }
	}
	// Make calculation according to calculationrule
	if ($action == 'calculate')
	{
		$calculationrule=GETPOST('calculationrule');

	    $object->fetch($id);
	    $object->fetch_thirdparty();
		$result=$object->update_price(0, (($calculationrule=='totalofround')?'0':'1'), 0, $object->thirdparty);
	    if ($result	<= 0)
	    {
	        dol_print_error($db,$result);
	        exit;
	    }
	}
	// Delete file in doc form
	if ($action == 'remove_file')
	{
	    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	    if ($object->fetch($id))
	    {
	    	$object->fetch_thirdparty();
	        $upload_dir =	$conf->fournisseur->facture->dir_output . "/";
	        $file =	$upload_dir	. '/' .	GETPOST('file');
	        $ret=dol_delete_file($file,0,0,0,$object);
	        if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
	        else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
	    }
	}

	if ($action == 'update_extras')
	{
		// Fill array 'array_options' with data from add form
		$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
		$ret = $extrafields->setOptionalsFromPost($extralabels,$object,GETPOST('attribute'));
		if ($ret < 0) $error++;

		if (!$error)
		{
			// Actions on extra fields (by external module or standard code)
			// TODO le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('supplierinvoicedao'));
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

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->fournisseur->facture->creer)
	{
		if ($action == 'addcontact')
		{
			$result = $object->fetch($id);

			if ($result > 0 && $id > 0)
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
		else if ($action == 'swapstatut')
		{
			if ($object->fetch($id))
			{
				$result=$object->swapContactStatus(GETPOST('ligne'));
			}
			else
			{
				dol_print_error($db);
			}
		}

		// Efface un contact
		else if ($action == 'deletecontact')
		{
			$object->fetch($id);
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
 *	View
 */

$form = new Form($db);
$formfile = new FormFile($db);
$bankaccountstatic=new Account($db);

llxHeader('','','');

// Mode creation
if ($action == 'create')
{
	$facturestatic = new FactureFournisseur($db);
	$extralabels = $extrafields->fetch_name_optionals_label($facturestatic->table_element);

    print load_fiche_titre($langs->trans('NewBill'));

    dol_htmloutput_events();

    $societe='';
    if (GETPOST('socid') > 0)
    {
        $societe=new Societe($db);
        $societe->fetch(GETPOST('socid','int'));
    }

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
            $element = 'projet';
        }
        else if (in_array($element,array('order_supplier')))
        {
            // For compatibility
            if ($element == 'order')    {
                $element = $subelement = 'commande';
            }
            if ($element == 'propal')   {
                $element = 'comm/propal'; $subelement = 'propal';
            }
            if ($element == 'contract') {
                $element = $subelement = 'contrat';
            }
            if ($element == 'order_supplier') {
                $element = 'fourn'; $subelement = 'fournisseur.commande';
            }

            require_once DOL_DOCUMENT_ROOT.'/'.$element.'/class/'.$subelement.'.class.php';
            $classname = ucfirst($subelement);
            if ($classname == 'Fournisseur.commande') $classname='CommandeFournisseur';
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            $objectsrc->fetch_thirdparty();

            $projectid			= (!empty($objectsrc->fk_project)?$objectsrc->fk_project:'');
            //$ref_client			= (!empty($objectsrc->ref_client)?$object->ref_client:'');

            $soc = $objectsrc->thirdparty;
            $cond_reglement_id 	= (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_supplier_id)?$soc->cond_reglement_supplier_id:1));
            $mode_reglement_id 	= (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_supplier_id)?$soc->mode_reglement_supplier_id:0));
            $fk_account         = (! empty($objectsrc->fk_account)?$objectsrc->fk_account:(! empty($soc->fk_account)?$soc->fk_account:0));
            $remise_percent 	= (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
            $remise_absolue 	= (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
            $dateinvoice		= empty($conf->global->MAIN_AUTOFILL_DATE)?-1:'';

            $datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
            $dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$datetmp);
            $datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
            $datedue=($datetmp==''?-1:$datetmp);
        }
    }
    else
    {
		$cond_reglement_id 	= $societe->cond_reglement_supplier_id;
		$mode_reglement_id 	= $societe->mode_reglement_supplier_id;
        $fk_account         = $societe->fk_account;
        $datetmp=dol_mktime(12,0,0,$_POST['remonth'],$_POST['reday'],$_POST['reyear']);
        $dateinvoice=($datetmp==''?(empty($conf->global->MAIN_AUTOFILL_DATE)?-1:''):$datetmp);
        $datetmp=dol_mktime(12,0,0,$_POST['echmonth'],$_POST['echday'],$_POST['echyear']);
        $datedue=($datetmp==''?-1:$datetmp);
    }


    print '<form name="add" action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
    print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';

    dol_fiche_head();
    
    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%">'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

    // Third party
    print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
    print '<td>';

    if (GETPOST('socid') > 0)
    {
        print $societe->getNomUrl(1);
        print '<input type="hidden" name="socid" value="'.GETPOST('socid','int').'">';
    }
    else
    {
        print $form->select_company(GETPOST('socid','int'),'socid','s.fournisseur = 1',1);
    }
    print '</td></tr>';

    // Ref supplier
    print '<tr><td class="fieldrequired">'.$langs->trans('RefSupplier').'</td><td><input name="ref_supplier" value="'.(isset($_POST['ref_supplier'])?$_POST['ref_supplier']:'').'" type="text"></td>';
    print '</tr>';

    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
    print '<table class="nobordernopadding">'."\n";

    // Standard invoice
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="0"'.($_POST['type']==0?' checked':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    /*
     // Deposit
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="3"'.($_POST['type']==3?' checked':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceDeposit"),$langs->transnoentities("InvoiceDepositDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    // Proforma
    if (! empty($conf->global->FACTURE_USE_PROFORMAT))
    {
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="4"'.($_POST['type']==4?' checked':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceProForma"),$langs->transnoentities("InvoiceProFormaDesc"),1);
    print $desc;
    print '</td></tr>'."\n";
    }

    // Replacement
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" name="type" value="1"'.($_POST['type']==1?' checked':'');
    if (! $options) print ' disabled';
    print '>';
    print '</td><td valign="middle">';
    $text=$langs->trans("InvoiceReplacementAsk").' ';
    $text.='<select class="flat" name="fac_replacement"';
    if (! $options) $text.=' disabled';
    $text.='>';
    if ($options)
    {
    $text.='<option value="-1">&nbsp;</option>';
    $text.=$options;
    }
    else
    {
    $text.='<option value="-1">'.$langs->trans("NoReplacableInvoice").'</option>';
    }
    $text.='</select>';
    $desc=$form->textwithpicto($text,$langs->transnoentities("InvoiceReplacementDesc"),1);
    print $desc;
    print '</td></tr>';

    // Credit note
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" name="type" value="2"'.($_POST['type']==2?' checked':'');
    if (! $optionsav) print ' disabled';
    print '>';
    print '</td><td valign="middle">';
    $text=$langs->transnoentities("InvoiceAvoirAsk").' ';
    //	$text.='<input type="text" value="">';
    $text.='<select class="flat" name="fac_avoir"';
    if (! $optionsav) $text.=' disabled';
    $text.='>';
    if ($optionsav)
    {
    $text.='<option value="-1">&nbsp;</option>';
    $text.=$optionsav;
    }
    else
    {
    $text.='<option value="-1">'.$langs->trans("NoInvoiceToCorrect").'</option>';
    }
    $text.='</select>';
    $desc=$form->textwithpicto($text,$langs->transnoentities("InvoiceAvoirDesc"),1);
    print $desc;
    print '</td></tr>'."\n";
    */
    print '</table>';
    print '</td></tr>';

    // Label
    print '<tr><td>'.$langs->trans('Label').'</td><td><input size="30" name="libelle" value="'.(isset($_POST['libelle'])?$_POST['libelle']:'').'" type="text"></td></tr>';

    // Date invoice
    print '<tr><td class="fieldrequired">'.$langs->trans('DateInvoice').'</td><td>';
    $form->select_date($dateinvoice,'','','','',"add",1,1);
    print '</td></tr>';

    // Due date
    print '<tr><td>'.$langs->trans('DateMaxPayment').'</td><td>';
    $form->select_date($datedue,'ech','','','',"add",1,1);
    print '</td></tr>';

	// Payment term
	print '<tr><td class="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$form->select_conditions_paiements(isset($_POST['cond_reglement_id'])?$_POST['cond_reglement_id']:$cond_reglement_id, 'cond_reglement_id');
	print '</td></tr>';

	// Payment mode
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements(isset($_POST['mode_reglement_id'])?$_POST['mode_reglement_id']:$mode_reglement_id, 'mode_reglement_id', 'DBIT');
	print '</td></tr>';

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
		print '<td><label for="incoterm_id">'.$form->textwithpicto($langs->trans("IncotermLabel"), $objectsrc->libelle_incoterms, 1).'</label></td>';
        print '<td colspan="3" class="maxwidthonsmartphone">';
        print $form->select_incoterms((!empty($objectsrc->fk_incoterms) ? $objectsrc->fk_incoterms : ''), (!empty($objectsrc->location_incoterms)?$objectsrc->location_incoterms:''));
		print '</td></tr>';
	}

    // Bank Account
    print '<tr><td>'.$langs->trans('BankAccount').'</td><td colspan="2">';
    $form->select_comptes($fk_account, 'fk_account', 0, '', 1);
    print '</td></tr>';

	// Public note
	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
    print '<td>';
    $note_public = $object->getDefaultCreateValueFor('note_public');
    $doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
   // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';

    // Private note
    print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
    print '<td>';
    $note_private = $object->getDefaultCreateValueFor('note_private');
    $doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
    // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';

	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields, 'edit');
	}

    if (is_object($objectsrc))
    {
        print "\n<!-- ".$classname." info -->";
        print "\n";
        print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
        print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
        print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

        $txt=$langs->trans($classname);
        if ($classname=='CommandeFournisseur') {
	        $langs->load('orders');
	        $txt=$langs->trans("SupplierOrder");
        }
        print '<tr><td>'.$txt.'</td><td colspan="2">'.$objectsrc->getNomUrl(1);
        // We check if Origin document (id and type is known) has already at least one invoice attached to it
        $objectsrc->fetchObjectLinked($originid,$origin,'','invoice_supplier');
        $cntinvoice=count($objectsrc->linkedObjects['invoice_supplier']);
        if ($cntinvoice>=1)
        {
        	setEventMessages('WarningBillExist', null, 'warnings');
        	echo ' ('.$langs->trans('LatestRelatedBill').end($objectsrc->linkedObjects['invoice_supplier'])->getNomUrl(1).')';
        }
        echo '</td></tr>';
        print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
        if ($mysoc->country_code=='ES')
        {
            if ($mysoc->localtax1_assuj=="1" || $object->total_localtax1 != 0) //Localtax1
            {
                print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
            }

            if ($mysoc->localtax2_assuj=="1" || $object->total_localtax2 != 0) //Localtax2
            {
                print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax2)."</td></tr>";
            }
        }
        print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($objectsrc->total_ttc)."</td></tr>";
    }
    else
    {
    	// TODO more bugs
        if (1==2 && ! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
        {
            print '<tr class="liste_titre">';
            print '<td>&nbsp;</td>';
            print '<td>'.$langs->trans('Label').'</td>';
            print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
            print '<td align="right">'.$langs->trans('VAT').'</td>';
            print '<td align="right">'.$langs->trans('Qty').'</td>';
            print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
            print '</tr>';

            for ($i = 1 ; $i < 9 ; $i++)
            {
                $value_qty = '1';
                $value_tauxtva = '';
                print '<tr><td>'.$i.'</td>';
                print '<td><input size="50" name="label'.$i.'" value="'.$value_label.'" type="text"></td>';
                print '<td align="right"><input type="text" size="8" name="amount'.$i.'" value="'.$value_pu.'"></td>';
                print '<td align="right">';
                print $form->load_tva('tauxtva'.$i,$value_tauxtva,$societe,$mysoc);
                print '</td>';
                print '<td align="right"><input type="text" size="3" name="qty'.$i.'" value="'.$value_qty.'"></td>';
                print '<td align="right"><input type="text" size="8" name="amountttc'.$i.'" value=""></td></tr>';
            }
        }
    }

    // Other options
    $parameters=array('colspan' => ' colspan="6"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

    // Bouton "Create Draft"
    print "</table>\n";

    dol_fiche_end();

    print '<div class="center"><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></div>';

    print "</form>\n";


    // Show origin lines
    if (is_object($objectsrc))
    {
        print '<br>';

        $title=$langs->trans('ProductsAndServices');
        print load_fiche_titre($title);

        print '<table class="noborder" width="100%">';

        $objectsrc->printOriginLinesList();

        print '</table>';
    }
}
else
{
    if ($id > 0 || ! empty($ref))
    {
        /* *************************************************************************** */
        /*                                                                             */
        /* Fiche en mode visu ou edition                                               */
        /*                                                                             */
        /* *************************************************************************** */

        $now=dol_now();

        $productstatic = new Product($db);

        $object->fetch($id,$ref);
        $result=$object->fetch_thirdparty();
        if ($result < 0) dol_print_error($db);

        $societe = new Fournisseur($db);
        $result=$societe->fetch($object->socid);
        if ($result < 0) dol_print_error($db);

        // fetch optionals attributes and labels
		$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

        /*
         *	View card
         */
        $head = facturefourn_prepare_head($object);
        $titre=$langs->trans('SupplierInvoice');

        dol_fiche_head($head, 'card', $titre, 0, 'bill');

        // Clone confirmation
        if ($action == 'clone')
        {
            // Create an array for form
            $formquestion=array(
            //'text' => $langs->trans("ConfirmClone"),
            //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
            );
            // Paiement incomplet. On demande si motif = escompte ou autre
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$object->ref),'confirm_clone',$formquestion,'yes', 1);
        }

        // Confirmation de la validation
        if ($action == 'valid')
        {
			 // on verifie si l'objet est en numerotation provisoire
            $objectref = substr($object->ref, 1, 4);
            if ($objectref == 'PROV')
            {
                $savdate=$object->date;
                $numref = $object->getNextNumRef($societe);
            }
            else
            {
                $numref = $object->ref;
            }

            $text=$langs->trans('ConfirmValidateBill',$numref);
            /*if (! empty($conf->notification->enabled))
            {
            	require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
            	$notify=new Notify($db);
            	$text.='<br>';
            	$text.=$notify->confirmMessage('BILL_SUPPLIER_VALIDATE',$object->socid, $object);
            }*/
            $formquestion=array();

            $qualified_for_stock_change=0;
		    if (empty($conf->global->STOCK_SUPPORTS_SERVICES))
		    {
		    	$qualified_for_stock_change=$object->hasProductsOrServices(2);
		    }
		    else
		    {
		    	$qualified_for_stock_change=$object->hasProductsOrServices(1);
		    }

            if (! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL) && $qualified_for_stock_change)
            {
                $langs->load("stocks");
                require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
                $formproduct=new FormProduct($db);
                $formquestion=array(
                //'text' => $langs->trans("ConfirmClone"),
                //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
            }

			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, 1, 1, 240);

        }

        // Confirmation set paid
        if ($action == 'paid')
        {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', 0, 1);

        }

        // Confirmation de la suppression de la facture fournisseur
        if ($action == 'delete')
        {
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBill'), $langs->trans('ConfirmDeleteBill'), 'confirm_delete', '', 0, 1);

        }

       	// Confirmation to delete line
		if ($action == 'ask_deleteline')
		{
			$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
		}

        if (!$formconfirm) {
			$parameters=array('lineid'=>$lineid);
			$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if (empty($reshook)) $formconfirm.=$hookmanager->resPrint;
			elseif ($reshook > 0) $formconfirm=$hookmanager->resPrint;
		}

		// Print form confirm
		print $formconfirm;


        /**
         * 	Invoice
         */
        print '<table class="border" width="100%">';

        $linkback = '<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

        // Ref
        print '<tr><td class="nowrap" width="20%">'.$langs->trans("Ref").'</td><td colspan="4">';
        print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref');
        print '</td>';
        print "</tr>\n";

        // Ref supplier
        print '<tr><td>'.$form->editfieldkey("RefSupplier",'ref_supplier',$object->ref_supplier,$object,($object->statut<FactureFournisseur::STATUS_CLOSED && $user->rights->fournisseur->facture->creer)).'</td><td colspan="4">';
        print $form->editfieldval("RefSupplier",'ref_supplier',$object->ref_supplier,$object,($object->statut<FactureFournisseur::STATUS_CLOSED && $user->rights->fournisseur->facture->creer));
        print '</td></tr>';

        // Third party
        print '<tr><td>'.$langs->trans('Supplier').'</td><td colspan="4">'.$societe->getNomUrl(1,'supplier');
        print ' &nbsp; (<a href="'.DOL_URL_ROOT.'/fourn/facture/list.php?socid='.$object->socid.'">'.$langs->trans('OtherBills').'</a>)</td>';
        print '</tr>';

        // Type
        print '<tr><td>'.$langs->trans('Type').'</td><td colspan="4">';
        print $object->getLibType();
        if ($object->type == FactureFournisseur::TYPE_REPLACEMENT)
        {
            $facreplaced=new FactureFournisseur($db);
            $facreplaced->fetch($object->fk_facture_source);
            print ' ('.$langs->transnoentities("ReplaceInvoice",$facreplaced->getNomUrl(1)).')';
        }
        if ($object->type == FactureFournisseur::TYPE_CREDIT_NOTE)
        {
            $facusing=new FactureFournisseur($db);
            $facusing->fetch($object->fk_facture_source);
            print ' ('.$langs->transnoentities("CorrectInvoice",$facusing->getNomUrl(1)).')';
        }

        $facidavoir=$object->getListIdAvoirFromInvoice();
        if (count($facidavoir) > 0)
        {
            print ' ('.$langs->transnoentities("InvoiceHasAvoir");
            $i=0;
            foreach($facidavoir as $id)
            {
                if ($i==0) print ' ';
                else print ',';
                $facavoir=new FactureFournisseur($db);
                $facavoir->fetch($id);
                print $facavoir->getNomUrl(1);
            }
            print ')';
        }
        if (isset($facidnext) && $facidnext > 0)
        {
            $facthatreplace=new FactureFournisseur($db);
            $facthatreplace->fetch($facidnext);
            print ' ('.$langs->transnoentities("ReplacedByInvoice",$facthatreplace->getNomUrl(1)).')';
        }
        print '</td></tr>';

        // Label
        print '<tr><td>'.$form->editfieldkey("Label",'label',$object->label,$object,($user->rights->fournisseur->facture->creer)).'</td>';
        print '<td colspan="3">'.$form->editfieldval("Label",'label',$object->label,$object,($user->rights->fournisseur->facture->creer)).'</td>';

        /*
         * List of payments
         */
        $nbrows=9; $nbcols=2;
        if (! empty($conf->projet->enabled)) $nbrows++;
        if (! empty($conf->banque->enabled)) { $nbrows++; $nbcols++; }

        // Local taxes
        if ($societe->localtax1_assuj=="1") $nbrows++;
        if ($societe->localtax2_assuj=="1") $nbrows++;

        print '<td rowspan="'.$nbrows.'" valign="top">';

        $sql = 'SELECT p.datep as dp, p.num_paiement, p.rowid, p.fk_bank,';
        $sql.= ' c.id as paiement_type,';
        $sql.= ' pf.amount,';
        $sql.= ' ba.rowid as baid, ba.ref, ba.label';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'paiementfourn as p';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_paiement as c ON p.fk_paiement = c.id';
        $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'paiementfourn_facturefourn as pf ON pf.fk_paiementfourn = p.rowid';
        $sql.= ' WHERE pf.fk_facturefourn = '.$object->id;
        $sql.= ' ORDER BY p.datep, p.tms';

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0; $totalpaye = 0;
            print '<table class="nobordernopadding" width="100%">';
            print '<tr class="liste_titre">';
            print '<td>'.$langs->trans('Payments').'</td>';
            print '<td>'.$langs->trans('Type').'</td>';
            if (! empty($conf->banque->enabled)) print '<td align="right">'.$langs->trans('BankAccount').'</td>';
            print '<td align="right">'.$langs->trans('Amount').'</td>';
            print '<td width="18">&nbsp;</td>';
            print '</tr>';

            $var=true;
            if ($num > 0)
            {
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    print '<tr '.$bc[$var].'>';
                    print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fourn/paiement/card.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' '.dol_print_date($db->jdate($objp->dp),'day')."</a></td>\n";
                    print '<td>';
                    print $form->form_modes_reglement(null, $objp->paiement_type,'none').' '.$objp->num_paiement;
                    print '</td>';
                    if (! empty($conf->banque->enabled))
                    {
                        $bankaccountstatic->id=$objp->baid;
                        $bankaccountstatic->ref=$objp->ref;
                        $bankaccountstatic->label=$objp->ref;
                        print '<td align="right">';
                        if ($objp->baid > 0) print $bankaccountstatic->getNomUrl(1,'transactions');
                        print '</td>';
                    }
                    print '<td align="right">'.price($objp->amount).'</td>';
                    print '<td align="center">';
                    if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0 && $user->societe_id == 0)
                    {
                        print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deletepaiement&paiement_id='.$objp->rowid.'">';
                        print img_delete();
                        print '</a>';
                    }
                    print '</td>';
                    print '</tr>';
                    $totalpaye += $objp->amount;
                    $i++;
                }
            }
            else
            {
                 print '<tr '.$bc[$var].'><td colspan="'.$nbcols.'">'.$langs->trans("None").'</td><td></td><td></td></tr>';
            }

            if ($object->paye == 0)
            {
                print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans('AlreadyPaid').' :</td><td align="right"><b>'.price($totalpaye).'</b></td><td></td></tr>';
                print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans("Billed").' :</td><td align="right" style="border: 1px solid;">'.price($object->total_ttc).'</td><td></td></tr>';

                $resteapayer = $object->total_ttc - $totalpaye;

                print '<tr><td colspan="'.$nbcols.'" align="right">'.$langs->trans('RemainderToPay').' :</td>';
                print '<td align="right" style="border: 1px solid;" bgcolor="#f0f0f0"><b>'.price($resteapayer).'</b></td><td></td></tr>';
            }
            print '</table>';
            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }
        print '</td>';

        print '</tr>';

	    $form_permission = $object->statut<FactureFournisseur::STATUS_CLOSED && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0;

        // Date
        print '<tr><td>'.$form->editfieldkey("Date",'datef',$object->datep,$object,$form_permission,'datepicker').'</td><td colspan="3">';
        print $form->editfieldval("Date",'datef',$object->datep,$object,$form_permission,'datepicker');
        print '</td>';

        // Due date
        print '<tr><td>'.$form->editfieldkey("DateMaxPayment",'date_lim_reglement',$object->date_echeance,$object,$form_permission,'datepicker').'</td><td colspan="3">';
        print $form->editfieldval("DateMaxPayment",'date_lim_reglement',$object->date_echeance,$object,$form_permission,'datepicker');
        if ($action != 'editdate_lim_reglement' && $object->hasDelay()) {
	        print img_warning($langs->trans('Late'));
        }
        print '</td>';

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
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'mode_reglement_id', 'DBIT');
		}
		else
		{
			$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id, $object->mode_reglement_id, 'none', 'DBIT');
		}
		print '</td></tr>';

        // Bank Account
        print '<tr><td class="nowrap">';
        print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
        print $langs->trans('BankAccount');
        print '<td>';
        if ($action != 'editbankaccount' && $user->rights->fournisseur->facture->creer)
            print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbankaccount&amp;id='.$object->id.'">'.img_edit($langs->trans('SetBankAccount'),1).'</a></td>';
        print '</tr></table>';
        print '</td><td colspan="3">';
        if ($action == 'editbankaccount') {
            $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'fk_account', 1);
        } else {
             $form->formSelectAccount($_SERVER['PHP_SELF'].'?id='.$object->id, $object->fk_account, 'none');
        }
        print "</td>";
        print '</tr>';

        // Status
        $alreadypaid=$object->getSommePaiement();
        print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$object->getLibStatut(4,$alreadypaid).'</td></tr>';

        // Amount
        print '<tr><td>'.$langs->trans('AmountHT').'</td><td colspan="3">'.price($object->total_ht,1,$langs,0,-1,-1,$conf->currency).'</td></tr>';
        print '<tr><td>'.$langs->trans('AmountVAT').'</td><td>'.price($object->total_tva,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">';
        if (GETPOST('calculationrule')) $calculationrule=GETPOST('calculationrule','alpha');
        else $calculationrule=(empty($conf->global->MAIN_ROUNDOFTOTAL_NOT_TOTALOFROUND)?'totalofround':'roundoftotal');
        if ($calculationrule == 'totalofround') $calculationrulenum=1;
        else  $calculationrulenum=2;
        $s=$langs->trans("ReCalculate").' ';
        $s.='<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=calculate&calculationrule=totalofround">'.$langs->trans("Mode1").'</a>';
        $s.=' / ';
        $s.='<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=calculate&calculationrule=roundoftotal">'.$langs->trans("Mode2").'</a>';
        print $form->textwithtooltip($s, $langs->trans("CalculationRuleDesc",$calculationrulenum).'<br>'.$langs->trans("CalculationRuleDescSupplier"), 2, 1, img_picto('','help'));
        print '</td></tr>';

        // Amount Local Taxes
        //TODO: Place into a function to control showing by country or study better option
        if ($societe->localtax1_assuj=="1") //Localtax1
        {
            print '<tr><td>'.$langs->transcountry("AmountLT1",$societe->country_code).'</td>';
            print '<td colspan="3">'.price($object->total_localtax1,1,$langs,0,-1,-1,$conf->currency).'</td>';
            print '</tr>';
        }
        if ($societe->localtax2_assuj=="1") //Localtax2
        {
            print '<tr><td>'.$langs->transcountry("AmountLT2",$societe->country_code).'</td>';
            print '<td colspan="3">'.price($object->total_localtax2,1,$langs,0,-1,-1,$conf->currency).'</td>';
            print '</tr>';
        }
        print '<tr><td>'.$langs->trans('AmountTTC').'</td><td colspan="3">'.price($object->total_ttc,1,$langs,0,-1,-1,$conf->currency).'</td></tr>';

        // Project
        if (! empty($conf->projet->enabled))
        {
            $langs->load('projects');
            print '<tr>';
            print '<td>';

            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('Project');
            print '</td>';
            if ($action != 'classify')
            {
                print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=classify&amp;id='.$object->id.'">';
                print img_edit($langs->trans('SetProject'),1);
                print '</a></td>';
            }
            print '</tr></table>';

            print '</td><td colspan="3">';
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
	        if ($user->rights->fournisseur->facture->creer) print '<a href="'.DOL_URL_ROOT.'/fourn/facture/card.php?facid='.$object->id.'&action=editincoterm">'.img_edit().'</a>';
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
        $cols = 4;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print '</table><br>';

        if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
        {
        	$blocname = 'contacts';
        	$title = $langs->trans('ContactsAddresses');
        	include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
        }

        if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
        {
        	$colwidth=20;
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

		if (! empty($conf->use_javascript_ajax) && $object->statut == FactureFournisseur::STATUS_DRAFT) {
			include DOL_DOCUMENT_ROOT . '/core/tpl/ajaxrow.tpl.php';
		}

		print '<table id="tablelines" class="noborder noshadow" width="100%">';

       	global $forceall, $senderissupplier, $dateSelector, $inputalsopricewithtax;
		$forceall=1; $senderissupplier=1; $dateSelector=0; $inputalsopricewithtax=1;
		
		// Show object lines
		if (! empty($object->lines))
			$ret = $object->printObjectLines($action, $societe, $mysoc, $lineid, 1);

		$num=count($object->lines);

		// Form to add new line
        if ($object->statut == FactureFournisseur::STATUS_DRAFT && $user->rights->fournisseur->facture->creer)
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


        if ($action != 'presend')
        {
            /*
             * Boutons actions
             */

            print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
			                                                                                          // modified by hook
			if (empty($reshook)) 
			{
	
			    // Modify a validated invoice with no payments
				if ($object->statut == FactureFournisseur::STATUS_VALIDATED && $action != 'edit' && $object->getSommePaiement() == 0 && $user->rights->fournisseur->facture->creer)
				{
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans('Modify').'</a>';
				}
	
	 	 		// Reopen a standard paid invoice
	            if (($object->type == FactureFournisseur::TYPE_STANDARD || $object->type == FactureFournisseur::TYPE_REPLACEMENT) && ($object->statut == 2 || $object->statut == 3))				// A paid invoice (partially or completely)
	            {
	                if (! $facidnext && $object->close_code != 'replaced')	// Not replaced by another invoice
	                {
	                    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a>';
	                }
	                else
	                {
	                    print '<span class="butActionRefused" title="'.$langs->trans("DisabledBecauseReplacedInvoice").'">'.$langs->trans('ReOpen').'</span>';
	                }
	            }
	
	            // Send by mail
	            if (($object->statut == FactureFournisseur::STATUS_VALIDATED || $object->statut == FactureFournisseur::STATUS_CLOSED))
	            {
	                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->fournisseur->supplier_invoice_advance->send)
	                {
	                    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
	                }
	                else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
	            }
	
	
	            // Make payments
	            if ($action != 'edit' && $object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0  && $user->societe_id == 0)
	            {
	                print '<a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create &amp;accountid='.$object->fk_account.'">'.$langs->trans('DoPayment').'</a>';	// must use facid because id is for payment id not invoice
	            }
	
	            // Classify paid
	            if ($action != 'edit' && $object->statut == FactureFournisseur::STATUS_VALIDATED && $object->paye == 0  && $user->societe_id == 0)
	            {
	                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=paid"';
	                print '>'.$langs->trans('ClassifyPaid').'</a>';
	
	                //print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a>';
	            }
	
	            // Validate
	            if ($action != 'edit' && $object->statut == FactureFournisseur::STATUS_DRAFT)
	            {
	                if (count($object->lines))
	                {
				        if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->facture->creer))
				       	|| (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->fournisseur->supplier_invoice_advance->validate)))
	                    {
	                        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=valid"';
	                        print '>'.$langs->trans('Validate').'</a>';
	                    }
	                    else
	                    {
	                        print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'"';
	                        print '>'.$langs->trans('Validate').'</a>';
	                    }
	                }
	            }
	
	            // Clone
	            if ($action != 'edit' && $user->rights->fournisseur->facture->creer)
	            {
	                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=clone&amp;socid='.$object->socid.'">'.$langs->trans('ToClone').'</a>';
	            }
	
	            // Delete
	            if ($action != 'edit' && $user->rights->fournisseur->facture->supprimer)
	            {
	                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
	            }
	            print '</div>';
	            print '<br>';
	
	            if ($action != 'edit')
	            {
					print '<div class="fichecenter"><div class="fichehalfleft">';
	            	//print '<table width="100%"><tr><td width="50%" valign="top">';
	                //print '<a name="builddoc"></a>'; // ancre
	
	                /*
	                 * Documents generes
	                */
	
	                $ref=dol_sanitizeFileName($object->ref);
	                $subdir = get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref;
	                $filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref;
	                $urlsource=$_SERVER['PHP_SELF'].'?id='.$object->id;
	                $genallowed=$user->rights->fournisseur->facture->creer;
	                $delallowed=$user->rights->fournisseur->facture->supprimer;
	                $modelpdf=(! empty($object->modelpdf)?$object->modelpdf:(empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF)?'':$conf->global->INVOICE_SUPPLIER_ADDON_PDF));
	
	                print $formfile->showdocuments('facture_fournisseur',$subdir,$filedir,$urlsource,$genallowed,$delallowed,$modelpdf,1,0,0,40,0,'','','',$societe->default_lang);
	                $somethingshown=$formfile->numoffiles;
	
					// Linked object block
					$somethingshown = $form->showLinkedObjectBlock($object);
	
					// Show links to link elements
					$linktoelem = $form->showLinkToObjectBlock($object,array('supplier_order'));
					if ($linktoelem) print '<br>'.$linktoelem;
	
	
					print '</div><div class="fichehalfright"><div class="ficheaddleft">';
	                //print '</td><td valign="top" width="50%">';
	                //print '<br>';
	
	                // List of actions on element
	                include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	                $formactions=new FormActions($db);
	                $somethingshown=$formactions->showactions($object,'invoice_supplier',$socid);
	
					print '</div></div></div>';
	                //print '</td></tr></table>';
	            }
			}
        }

        /*
         * Show mail form
         */
        if (GETPOST('modelselected')) {
        	$action = 'presend';
        }
        if ($action == 'presend')
        {
            $ref = dol_sanitizeFileName($object->ref);
            include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            $fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref, preg_quote($ref,'/').'([^\-])+');
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
                $outputlangs->load('bills');
            }

            // Build document if it not exists
            if (! $file || ! is_readable($file))
            {
	            $result = $object->generateDocument(GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($result <= 0)
                {
                    dol_print_error($db,$object->error,$object->errors);
                    exit;
                }
                $fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2,0,0,$object,'invoice_supplier').$ref, preg_quote($ref,'/').'([^\-])+');
                $file=$fileparams['fullname'];
            }

			print '<div class="clearboth"></div>';
            print '<br>';
            print load_fiche_titre($langs->trans('SendBillByMail'));

            dol_fiche_head('');

            // Cree l'objet formulaire mail
            include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
            $formmail = new FormMail($db);
            $formmail->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);
            $formmail->fromtype = 'user';
            $formmail->fromid   = $user->id;
            $formmail->fromname = $user->getFullName($langs);
            $formmail->frommail = $user->email;
            if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 1))	// If bit 1 is set
            {
            	$formmail->trackid='sin'.$object->id;
            }
            if (! empty($conf->global->MAIN_EMAIL_ADD_TRACK_ID) && ($conf->global->MAIN_EMAIL_ADD_TRACK_ID & 2))	// If bit 2 is set
            {
            	include DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
            	$formmail->frommail=dolAddEmailTrackId($formmail->frommail, 'sin'.$object->id);
            }            
            $formmail->withfrom=1;
			$liste=array();
			foreach ($object->thirdparty->thirdparty_and_contact_email_array(1) as $key=>$value)	$liste[$key]=$value;
			$formmail->withto=GETPOST("sendto")?GETPOST("sendto"):$liste;
			$formmail->withtocc=$liste;
            $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
            $formmail->withtopic=$outputlangs->trans('SendBillRef','__REF__');
            $formmail->withfile=2;
            $formmail->withbody=1;
            $formmail->withdeliveryreceipt=1;
            $formmail->withcancel=1;
            // Tableau des substitutions
            $formmail->substit['__REF__']=$object->ref;
            $formmail->substit['__SIGNATURE__']=$user->signature;
            $formmail->substit['__PERSONALIZED__']='';
            $formmail->substit['__CONTACTCIVNAME__']='';

            //Find the good contact adress
            $custcontact='';
            $contactarr=array();
            $contactarr=$object->liste_contact(-1,'external');

            if (is_array($contactarr) && count($contactarr)>0) {
            	foreach($contactarr as $contact) {
            		if ($contact['libelle']==$langs->trans('TypeContact_invoice_supplier_external_BILLING')) {
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
            $formmail->param['models']='invoice_supplier_send';
            $formmail->param['models_id']=GETPOST('modelmailselected','int');
            $formmail->param['facid']=$object->id;
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
    }
}


// End of page
llxFooter();
$db->close();
