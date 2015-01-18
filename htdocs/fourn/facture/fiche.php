<?php
/* Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013	Laurent Destailleur 	<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Christophe Combelles	<ccomb@free.fr>
 * Copyright (C) 2005		Marc Barilley			<marc@ocebo.fr>
 * Copyright (C) 2005-2013	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013		Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013       Florian Henry		  	<florian.henry@open-concept.pro>
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
 *	\file       htdocs/fourn/facture/fiche.php
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

$mesg='';
$errors=array();
$id			= (GETPOST('facid','int') ? GETPOST('facid','int') : GETPOST('id','int'));
$action		= GETPOST("action");
$confirm	= GETPOST("confirm");
$ref		= GETPOST('ref','alpha');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Security check
$socid='';
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $id, 'facture_fourn', 'facture');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('invoicesuppliercard'));

$object=new FactureFournisseur($db);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
}

$permissionnote=$user->rights->fournisseur->facture->creer;	// Used by the include of actions_setnotes.inc.php


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php';	// Must be include, not includ_once

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
    if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
    {
        $mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
    }
    else
    {
        $result=$object->createFromClone($id);
        if ($result > 0)
        {
            header("Location: ".$_SERVER['PHP_SELF'].'?action=editref_supplier&id='.$result);
            exit;
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
            $action='';
        }
    }
}

elseif ($action == 'confirm_valid' && $confirm == 'yes' && $user->rights->fournisseur->facture->valider)
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
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
            $action='';
        }
    }

    if (! $error)
    {
        $result = $object->validate($user,'',$idwarehouse);
        if ($result < 0)
        {
            setEventMessage($object->error,'errors');
            setEventMessage($object->errors,'errors');
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
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

elseif ($action == 'confirm_delete_line' && $confirm == 'yes' && $user->rights->fournisseur->facture->creer)
{
	$object->fetch($id);
	$ret = $object->deleteline(GETPOST('lineid'));
	if ($ret > 0)
	{
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
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
        setEventMessage($object->error,'errors');
    }
}

// Set supplier ref
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
    	setEventMessage($langs->trans("DatePaymentTermCantBeLowerThanObjectDate"),'warnings');
    }
    $result=$object->update($user);
    if ($result < 0) dol_print_error($db,$object->error);
}

// Delete payment
elseif ($action == 'deletepaiement' && $user->rights->fournisseur->facture->creer)
{
    $object->fetch($id);
    if ($object->statut == 1 && $object->paye == 0)
    {
    	$paiementfourn = new PaiementFourn($db);
        $result=$paiementfourn->fetch(GETPOST('paiement_id'));
        if ($result > 0) $result=$paiementfourn->delete(); // If fetch ok and found
        if ($result < 0) $mesg='<div class="error">'.$paiementfourn->error.'</div>';
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
    	$mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Supplier')).'</div>';
    	$action='create';
    	$error++;
    }

    if ($datefacture == '')
    {
        $mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('DateInvoice')).'</div>';
        $action='create';
        $_GET['socid']=$_POST['socid'];
        $error++;
    }
    if (! GETPOST('ref_supplier'))
    {
        $mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('RefSupplier')).'</div>';
        $action='create';
        $_GET['socid']=$_POST['socid'];
        $error++;
    }

    if (! $error)
    {
        $db->begin();

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
        $object->fk_project    = ($tmpproject > 0) ? $tmpproject : null;

		// Auto calculation of date due if not filled by user
		if(empty($object->date_echeance)) $object->date_echeance = $object->calculate_date_lim_reglement();

        // If creation from another object of another module
        if ($_POST['origin'] && $_POST['originid'])
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
                            $product_type
                        );

                        if ($result < 0)
                        {
                            $error++;
                            break;
                        }
                    }
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
        // If some invoice's lines already known
        else
        {
            $id = $object->create($user);
            if ($id < 0)
            {
                $error++;
            }

            if (! $error)
            {
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

                        $ret=$object->addline($label, $amount, $tauxtva, $product->localtax1_tx, $product->localtax2_tx, $qty, $fk_product, $remise_percent, '', '', '', 0, $price_base);
                        if ($ret < 0) $error++;
                    }
                }
            }
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

            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
	            $outputlangs = $langs;
            	$result=supplier_invoice_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
            	if ($result	<= 0)
            	{
            		dol_print_error($db,$result);
            		exit;
            	}
            }

            header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
            exit;
        }
    }
}

// Edit line
elseif ($action == 'update_line' && $user->rights->fournisseur->facture->creer)
{
	// TODO Missing transaction
    if (GETPOST('etat') == '1' && ! GETPOST('cancel')) // si on valide la modification
    {
        $object->fetch($id);
        $object->fetch_thirdparty();

        if ($_POST['puht'])
        {
            $pu=$_POST['puht'];
            $price_base_type='HT';
        }
        if ($_POST['puttc'])
        {
            $pu=$_POST['puttc'];
            $price_base_type='TTC';
        }

        if (GETPOST('idprod'))
        {
            $prod = new Product($db);
            $prod->fetch($_POST['idprod']);
            $label = $prod->description;
            if (trim($_POST['desc']) != trim($label)) $label=$_POST['desc'];

            $type = $prod->type;
        }
        else
        {

            $label = $_POST['desc'];
            $type = $_POST["type"]?$_POST["type"]:0;

        }

        $localtax1_tx= get_localtax($_POST['tauxtva'], 1, $mysoc,$object->thirdparty);
        $localtax2_tx= get_localtax($_POST['tauxtva'], 2, $mysoc,$object->thirdparty);
        $remise_percent=GETPOST('remise_percent');

        $result=$object->updateline(GETPOST('lineid'), $label, $pu, GETPOST('tauxtva'), $localtax1_tx, $localtax2_tx, GETPOST('qty'), GETPOST('idprod'), $price_base_type, 0, $type, $remise_percent);
        if ($result >= 0)
        {
            unset($_POST['label']);
        }
        else
        {
            setEventMessage($object->error,'errors');
        }
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

    if (GETPOST('prod_entry_mode') != 'free')	// With combolist mode idprodfournprice is > 0 or -1. With autocomplete, idprodfournprice is > 0 or ''
    {
    	$idprod=0;
    	$productsupplier=new ProductFournisseur($db);

        if (GETPOST('idprodfournprice') == -1 || GETPOST('idprodfournprice') == '') $idprod=-2;	// Same behaviour than with combolist. When not select idprodfournprice is now -2 (to avoid conflict with next action that may return -1)

    	if (GETPOST('idprodfournprice') > 0)
    	{
    		$idprod=$productsupplier->get_buyprice(GETPOST('idprodfournprice'), $qty);    // Just to see if a price exists for the quantity. Not used to found vat.
    	}

        if ($idprod > 0)
        {
            $result=$productsupplier->fetch($idprod);

            $label = $productsupplier->libelle;

            $desc = $productsupplier->description;
            if (trim($product_desc) != trim($desc)) $desc = dol_concatdesc($desc, $product_desc);

            $tvatx=get_default_tva($object->thirdparty, $mysoc, $productsupplier->id, $_POST['idprodfournprice']);
            $npr = get_default_npr($object->thirdparty, $mysoc, $productsupplier->id, $_POST['idprodfournprice']);

            $localtax1_tx= get_localtax($tvatx, 1, $mysoc,$object->thirdparty);
            $localtax2_tx= get_localtax($tvatx, 2, $mysoc,$object->thirdparty);

            $type = $productsupplier->type;

            // TODO Save the product supplier ref into database into field ref_supplier (must rename field ref into ref_supplier first)
            $result=$object->addline($desc, $productsupplier->fourn_pu, $tvatx, $localtax1_tx, $localtax2_tx, $qty, $idprod, $remise_percent, '', '', 0, $npr);
        }
    	if ($idprod == -2 || $idprod == 0)
        {
            // Product not selected
            $error++;
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ProductOrService")).'</div>';
        }
        if ($idprod == -1)
        {
            // Quantity too low
            $error++;
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans("ErrorQtyTooLowForThisSupplier").'</div>';
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

    	$tva_tx = price2num($tva_tx);	// When vat is text input field

    	// Local Taxes
    	$localtax1_tx= get_localtax($tva_tx, 1,$mysoc,$object->thirdparty);
    	$localtax2_tx= get_localtax($tva_tx, 2,$mysoc,$object->thirdparty);

    	if (!empty($_POST['price_ht']))
    	{
    		$ht = price2num($_POST['price_ht']);
            $price_base_type = 'HT';

            //print $product_desc, $pu, $txtva, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0
            $result=$object->addline($product_desc, $ht, $tva_tx, $localtax1_tx, $localtax2_tx, $qty, 0, $remise_percent, $datestart, $dateend, 0, $npr, $price_base_type, $type);
        }
        else
		{
    		$ttc = price2num($_POST['price_ttc']);
            $ht = $ttc / (1 + ($tva_tx / 100));
            $price_base_type = 'HT';
            //print $product_desc, $pu, $txtva, $qty, $fk_product=0, $remise_percent=0, $date_start='', $date_end='', $ventil=0, $info_bits='', $price_base_type='HT', $type=0
            $result=$object->addline($product_desc, $ht, $tva_tx,$localtax1_tx, $localtax2_tx, $qty, 0, $remise_percent, $datestart, $dateend, 0, $npr, $price_base_type, $type);
        }
    }

    //print "xx".$tva_tx; exit;
    if (! $error && $result > 0)
    {
    	$db->commit();

    	// Define output language
    	$outputlangs = $langs;
        $newlang=GETPOST('lang_id','alpha');
        if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->client->default_lang;
    	if (! empty($newlang))
    	{
    		$outputlangs = new Translate("",$conf);
    		$outputlangs->setDefaultLang($newlang);
    	}
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
        {
        	$result=supplier_invoice_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        	if ($result	<= 0)
        	{
        		dol_print_error($db,$result);
        		exit;
        	}
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
    	$db->rollback();
		if (empty($mesg))
	    {
	        $mesg='<div class="error">'.$object->error.'</div>';
	    }
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

        $outputlangs = $langs;
        if (! empty($_REQUEST['lang_id']))
        {
            $outputlangs = new Translate("",$conf);
            $outputlangs->setDefaultLang($_REQUEST['lang_id']);
        }
        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
        	$result=supplier_invoice_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
        	if ($result	<= 0)
        	{
        		dol_print_error($db,$result);
        		exit;
        	}
        }

        $action='';
    }
}

// Set invoice to validated/unpaid status
elseif ($action == 'reopen' && $user->rights->fournisseur->facture->creer)
{
    $result = $object->fetch($id);
    if ($object->statut == 2
    || ($object->statut == 3 && $object->close_code != 'replaced'))
    {
        $result = $object->set_unpaid($user);
        if ($result > 0)
        {
            header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
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
    dol_remove_file_process($_POST['removedfile'],0);
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
//        $ref = dol_sanitizeFileName($object->ref);
//        $file = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$ref.'/'.$ref.'.pdf';

//        if (is_readable($file))
//        {
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
                    setEventMessage($mailfile->error,'errors');
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
                        $object->actionmsg		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('BILL_SUPPLIER_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) {
                            $error++; $object->errors=$interface->errors;
                        }
                        // Fin appel triggers

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
                        $mesg='<div class="error">';
                        if ($mailfile->error)
                        {
                            $mesg.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
                            $mesg.='<br>'.$mailfile->error;
                        }
                        else
                        {
                            $mesg.='No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS';
                        }
                        $mesg.='</div>';
                    }
                }
            }

            else
            {
                $langs->load("other");
                $mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').'</div>';
                dol_syslog('Recipient email is empty');
            }
/*        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }*/
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
        dol_syslog('Unable to read data from the invoice. The invoice file has perhaps not been generated.');
    }

    //$action = 'presend';
}

// Build document
elseif ($action == 'builddoc')
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
    $result=supplier_invoice_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
    if ($result	<= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
}
// Make calculation according to calculationrule
elseif ($action == 'calculate')
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
elseif ($action == 'remove_file')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if ($object->fetch($id))
    {
    	$object->fetch_thirdparty();
        $upload_dir =	$conf->fournisseur->facture->dir_output . "/";
        $file =	$upload_dir	. '/' .	GETPOST('file');
        $ret=dol_delete_file($file,0,0,0,$object);
        if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('urlfile')));
        else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), 'errors');
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
				$mesg = '<div class="error">'.$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType").'</div>';
			}
			else
			{
				$mesg = '<div class="error">'.$object->error.'</div>';
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
    print_fiche_titre($langs->trans('NewBill'));

    dol_htmloutput_mesg($mesg);
    dol_htmloutput_events();

    $societe='';
    if ($_GET['socid'])
    {
        $societe=new Societe($db);
        $societe->fetch($_GET['socid']);
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
                dol_htmloutput_errors('',$errors);
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

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Draft').'</td></tr>';

    // Third party
    print '<tr><td class="fieldrequired">'.$langs->trans('Supplier').'</td>';
    print '<td>';

    if ($_REQUEST['socid'] > 0)
    {
        print $societe->getNomUrl(1);
        print '<input type="hidden" name="socid" value="'.$_GET['socid'].'">';
    }
    else
    {
        print $form->select_company((empty($_GET['socid'])?'':$_GET['socid']),'socid','s.fournisseur = 1',1);
    }
    print '</td></tr>';

    // Ref supplier
    print '<tr><td class="fieldrequired">'.$langs->trans('RefSupplier').'</td><td><input name="ref_supplier" value="'.(isset($_POST['ref_supplier'])?$_POST['ref_supplier']:'').'" type="text"></td>';
    print '</tr>';

    print '<tr><td valign="top" class="fieldrequired">'.$langs->trans('Type').'</td><td colspan="2">';
    print '<table class="nobordernopadding">'."\n";

    // Standard invoice
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="0"'.($_POST['type']==0?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceStandardAsk"),$langs->transnoentities("InvoiceStandardDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    /*
     // Deposit
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="3"'.($_POST['type']==3?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceDeposit"),$langs->transnoentities("InvoiceDepositDesc"),1);
    print $desc;
    print '</td></tr>'."\n";

    // Proforma
    if (! empty($conf->global->FACTURE_USE_PROFORMAT))
    {
    print '<tr height="18"><td width="16px" valign="middle">';
    print '<input type="radio" name="type" value="4"'.($_POST['type']==4?' checked="checked"':'').'>';
    print '</td><td valign="middle">';
    $desc=$form->textwithpicto($langs->trans("InvoiceProForma"),$langs->transnoentities("InvoiceProFormaDesc"),1);
    print $desc;
    print '</td></tr>'."\n";
    }

    // Replacement
    print '<tr height="18"><td valign="middle">';
    print '<input type="radio" name="type" value="1"'.($_POST['type']==1?' checked="checked"':'');
    if (! $options) print ' disabled="disabled"';
    print '>';
    print '</td><td valign="middle">';
    $text=$langs->trans("InvoiceReplacementAsk").' ';
    $text.='<select class="flat" name="fac_replacement"';
    if (! $options) $text.=' disabled="disabled"';
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
    print '<input type="radio" name="type" value="2"'.($_POST['type']==2?' checked=true':'');
    if (! $optionsav) print ' disabled="disabled"';
    print '>';
    print '</td><td valign="middle">';
    $text=$langs->transnoentities("InvoiceAvoirAsk").' ';
    //	$text.='<input type="text" value="">';
    $text.='<select class="flat" name="fac_avoir"';
    if (! $optionsav) $text.=' disabled="disabled"';
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
	if (! empty($conf->projet->enabled)) {
		$formproject = new FormProjets($db);

		$langs->load('projects');
		print '<tr><td>' . $langs->trans('Project') . '</td><td colspan="2">';
		$formproject->select_projects($soc->id, $projectid, 'projectid');
		print '</td></tr>';
	}

	// Public note
	print '<tr><td>'.$langs->trans('NotePublic').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_public', GETPOST('note_public'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
   // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';

    // Private note
    print '<tr><td>'.$langs->trans('NotePrivate').'</td>';
    print '<td>';
    $doleditor = new DolEditor('note_private', GETPOST('note_private'), '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, 70);
    print $doleditor->Create(1);
    print '</td>';
    // print '<td><textarea name="note" wrap="soft" cols="60" rows="'.ROWS_5.'"></textarea></td>';
    print '</tr>';

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
        print '<tr><td>'.$txt.'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalHT').'</td><td colspan="2">'.price($objectsrc->total_ht).'</td></tr>';
        print '<tr><td>'.$langs->trans('TotalVAT').'</td><td colspan="2">'.price($objectsrc->total_tva)."</td></tr>";
        if ($mysoc->country_code=='ES')
        {
            if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
            {
                print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td><td colspan="2">'.price($objectsrc->total_localtax1)."</td></tr>";
            }

            if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
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

    print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></center>';

    print "</form>\n";


    // Show origin lines
    if (is_object($objectsrc))
    {
        print '<br>';

        $title=$langs->trans('ProductsAndServices');
        print_titre($title);

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

        /*
         *	View card
         */
        $head = facturefourn_prepare_head($object);
        $titre=$langs->trans('SupplierInvoice');

        dol_fiche_head($head, 'card', $titre, 0, 'bill');

        dol_htmloutput_mesg($mesg);
        dol_htmloutput_errors('',$errors);

        // Confirmation de la suppression d'une ligne produit
        if ($action == 'confirm_delete_line')
        {
            print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_delete_line', '', 1, 1);
        }

        // Clone confirmation
        if ($action == 'clone')
        {
            // Create an array for form
            $formquestion=array(
            //'text' => $langs->trans("ConfirmClone"),
            //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
            );
            // Paiement incomplet. On demande si motif = escompte ou autre
            print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneInvoice'),$langs->trans('ConfirmCloneInvoice',$object->ref),'confirm_clone',$formquestion,'yes', 1);
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
            	$text.=$notify->confirmMessage('BILL_SUPPLIER_VALIDATE',$object->socid);
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

            print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateBill'), $text, 'confirm_valid', $formquestion, 1, 1, 240);

        }

        // Confirmation set paid
        if ($action == 'paid')
        {
            print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ClassifyPaid'), $langs->trans('ConfirmClassifyPaidBill', $object->ref), 'confirm_paid', '', 0, 1);

        }

        // Confirmation de la suppression de la facture fournisseur
        if ($action == 'delete')
        {
            print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteBill'), $langs->trans('ConfirmDeleteBill'), 'confirm_delete', '', 0, 1);

        }


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
        print '<tr><td>'.$form->editfieldkey("RefSupplier",'ref_supplier',$object->ref_supplier,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer)).'</td><td colspan="4">';
        print $form->editfieldval("RefSupplier",'ref_supplier',$object->ref_supplier,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer));
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
        print '<tr><td>'.$form->editfieldkey("Label",'label',$object->label,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer)).'</td>';
        print '<td colspan="3">'.$form->editfieldval("Label",'label',$object->label,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer)).'</td>';

        /*
         * List of payments
         */
        $nbrows=9; $nbcols=2;
        if (! empty($conf->projet->enabled)) $nbrows++;
        if (! empty($conf->banque->enabled)) $nbcols++;

        // Local taxes
        if ($mysoc->country_code=='ES')
        {
        	if($mysoc->localtax1_assuj=="1") $nbrows++;
        	if($societe->localtax2_assuj=="1") $nbrows++;
        }
        else
       {
        	if ($societe->localtax1_assuj=="1") $nbrows++;
        	if ($societe->localtax2_assuj=="1") $nbrows++;
        }
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
                    print '<td class="nowrap"><a href="'.DOL_URL_ROOT.'/fourn/paiement/fiche.php?id='.$objp->rowid.'">'.img_object($langs->trans('ShowPayment'),'payment').' '.dol_print_date($db->jdate($objp->dp),'day')."</a></td>\n";
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
                    if ($object->statut == 1 && $object->paye == 0 && $user->societe_id == 0)
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

        // Date
        print '<tr><td>'.$form->editfieldkey("Date",'datef',$object->datep,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker').'</td><td colspan="3">';
        print $form->editfieldval("Date",'datef',$object->datep,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker');
        print '</td>';

        // Due date
        print '<tr><td>'.$form->editfieldkey("DateMaxPayment",'date_lim_reglement',$object->date_echeance,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker').'</td><td colspan="3">';
        print $form->editfieldval("DateMaxPayment",'date_lim_reglement',$object->date_echeance,$object,($object->statut<2 && $user->rights->fournisseur->facture->creer && $object->getSommePaiement() <= 0),'datepicker');
        if ($action != 'editdate_lim_reglement' && $object->statut < 2 && $object->date_echeance && $object->date_echeance < ($now - $conf->facture->fournisseur->warning_delay)) print img_warning($langs->trans('Late'));
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

        // Status
        $alreadypaid=$object->getSommePaiement();
        print '<tr><td>'.$langs->trans('Status').'</td><td colspan="3">'.$object->getLibStatut(4,$alreadypaid).'</td></tr>';

        print '<tr><td>'.$langs->trans('AmountHT').'</td><td align="right">'.price($object->total_ht,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">&nbsp;</td></tr>';
        print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($object->total_tva,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">';
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
        if ($mysoc->country_code=='ES')
        {
        	if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
	        {
	            print '<tr><td>'.$langs->transcountry("AmountLT1",$societe->country_code).'</td>';
	            print '<td align="right">'.price($object->total_localtax1,1,$langs,0,-1,-1,$conf->currency).'</td>';
	            print '<td colspan="2">&nbsp;</td></tr>';
	        }
	        if ($societe->localtax2_assuj=="1") //Localtax2 IRPF
	        {
	            print '<tr><td>'.$langs->transcountry("AmountLT2",$societe->country_code).'</td>';
	            print '<td align="right">'.price($object->total_localtax2,1,$langs,0,-1,-1,$conf->currency).'</td>';
	            print '<td colspan="2">&nbsp;</td></tr>';
	        }
        }
        else
       {
	        if ($societe->localtax1_assuj=="1") //Localtax1 RE
	        {
	            print '<tr><td>'.$langs->transcountry("AmountLT1",$societe->country_code).'</td>';
	            print '<td align="right">'.price($object->total_localtax1,1,$langs,0,-1,-1,$conf->currency).'</td>';
	            print '<td colspan="2">&nbsp;</td></tr>';
	        }
	        if ($societe->localtax2_assuj=="1") //Localtax2 IRPF
	        {
	            print '<tr><td>'.$langs->transcountry("AmountLT2",$societe->country_code).'</td>';
	            print '<td align="right">'.price($object->total_localtax2,1,$langs,0,-1,-1,$conf->currency).'</td>';
	            print '<td colspan="2">&nbsp;</td></tr>';
	        }
        }
        print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($object->total_ttc,1,$langs,0,-1,-1,$conf->currency).'</td><td colspan="2" align="left">&nbsp;</td></tr>';

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
                $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, empty($conf->global->PROJECT_CAN_ALWAYS_LINK_TO_ALL_SUPPLIERS)?$object->socid:'-1', $object->fk_project, 'projectid');
            }
            else
            {
                $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none');
            }
            print '</td>';
            print '</tr>';
        }

        // Other options
        $parameters=array('colspan' => ' colspan="4"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action); // Note that $action and $object may have been modified by hook

        print '</table>';

        if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
        {
        	print '<br>';
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


		print '	<form name="addproduct" id="addproduct" action="'.$_SERVER["PHP_SELF"].'?etat=1&id='.$object->id.(($action != 'edit_line')?'#add':'#line_'.GETPOST('lineid')).'" method="POST">
		<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">
		<input type="hidden" name="action" value="'.(($action != 'edit_line')?'addline':'update_line').'">
		<input type="hidden" name="mode" value="">
		<input type="hidden" name="id" value="'.$object->id.'">
        <input type="hidden" name="facid" value="'.$object->id.'">
        <input type="hidden" name="socid" value="'.$societe->id.'">

		';


        print '<br>';
        print '<table id="tablelines" class="noborder noshadow" width="100%">';
        $var=1;
        $num=count($object->lines);
        for ($i = 0; $i < $num; $i++)
        {
            if ($i == 0)
            {
                print '<tr class="liste_titre"><td>'.$langs->trans('Label').'</td>';
                print '<td align="right">'.$langs->trans('VAT').'</td>';
                print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
                print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
                print '<td align="right">'.$langs->trans('Qty').'</td>';
                print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
                print '<td align="right">'.$langs->trans('TotalHTShort').'</td>';
                print '<td align="right">'.$langs->trans('TotalTTCShort').'</td>';
                print '<td>&nbsp;</td>';
                print '<td>&nbsp;</td>';
                print '</tr>';
            }

            // Show product and description
            $type=(! empty($object->lines[$i]->product_type)?$object->lines[$i]->product_type:(! empty($object->lines[$i]->fk_product_type)?$object->lines[$i]->fk_product_type:0));
            // Try to enhance type detection using date_start and date_end for free lines where type was not saved.
            $date_start='';
            $date_end='';
            if (! empty($object->lines[$i]->date_start))
            {
            	$date_start=$object->lines[$i]->date_start;
            	$type=1;
            }
            if (! empty($object->lines[$i]->date_end))
            {
            	$date_end=$object->lines[$i]->date_end;
            	$type=1;
            }

            $var=!$var;

            // Edit line
            if ($object->statut == 0 && $action == 'edit_line' && $_GET['etat'] == '0' && $_GET['lineid'] == $object->lines[$i]->rowid)
            {
                print '<tr '.$bc[$var].'>';

                // Show product and description
                print '<td>';

                print '<input type="hidden" name="lineid" value="'.$object->lines[$i]->rowid.'">';

                if ((! empty($conf->product->enabled) || ! empty($conf->service->enabled)) && $object->lines[$i]->fk_product > 0)
                {
                    print '<input type="hidden" name="idprod" value="'.$object->lines[$i]->fk_product.'">';
                    $product_static=new ProductFournisseur($db);
                    $product_static->fetch($object->lines[$i]->fk_product);
                    $text=$product_static->getNomUrl(1);
                    $text.= ' - '.$product_static->libelle;
                    print $text;
                    print '<br>';
                }
                else
				{
                    $forceall=1;	// For suppliers, we always show all types
                    print $form->select_type_of_lines($object->lines[$i]->product_type,'type',1,0,$forceall);
                    if ($forceall || (! empty($conf->product->enabled) && ! empty($conf->service->enabled))
                    || (empty($conf->product->enabled) && empty($conf->service->enabled))) print '<br>';
                }

                if (is_object($hookmanager))
                {
                    $parameters=array('fk_parent_line'=>$line->fk_parent_line, 'line'=>$object->lines[$i],'var'=>$var,'num'=>$num,'i'=>$i);
                    $reshook=$hookmanager->executeHooks('formEditProductOptions',$parameters,$object,$action);
                }

                $nbrows=ROWS_2;
                if (! empty($conf->global->MAIN_INPUT_DESC_HEIGHT)) $nbrows=$conf->global->MAIN_INPUT_DESC_HEIGHT;
                $doleditor=new DolEditor('desc',$object->lines[$i]->description,'',128,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_DETAILS,$nbrows,70);
                $doleditor->Create();
                print '</td>';

                // VAT
                print '<td align="right">';
                print $form->load_tva('tauxtva',$object->lines[$i]->tva_tx,$societe,$mysoc);
                print '</td>';

                // Unit price
                print '<td align="right" class="nowrap"><input size="4" name="puht" type="text" value="'.price($object->lines[$i]->pu_ht).'"></td>';

                print '<td align="right" class="nowrap"><input size="4" name="puttc" type="text" value=""></td>';

                print '<td align="right"><input size="1" name="qty" type="text" value="'.$object->lines[$i]->qty.'"></td>';

                print '<td align="right" class="nowrap"><input size="1" name="remise_percent" type="text" value="'.$object->lines[$i]->remise_percent.'"><span class="hideonsmartphone">%</span></td>';

                print '<td align="right" class="nowrap">&nbsp;</td>';

                print '<td align="right" class="nowrap">&nbsp;</td>';

                print '<td align="center" colspan="2"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
                print '<br><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';

                print '</tr>';
            }
            else // Affichage simple de la ligne
            {
                print '<tr id="row-'.$object->lines[$i]->rowid.'" '.$bc[$var].'>';

                // Show product and description
                print '<td>';
                if ($object->lines[$i]->fk_product)
                {
                    print '<a name="'.$object->lines[$i]->rowid.'"></a>'; // ancre pour retourner sur la ligne

                    $product_static=new ProductFournisseur($db);
                    $product_static->fetch($object->lines[$i]->fk_product);
                    $text=$product_static->getNomUrl(1);
                    $text.= ' - '.$product_static->libelle;
                    $description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($object->lines[$i]->description));
                    print $form->textwithtooltip($text,$description,3,'','',$i);

                    // Show range
                    print_date_range($date_start,$date_end);

                    // Add description in form
                    if (! empty($conf->global->PRODUIT_DESC_IN_FORM)) print ($object->lines[$i]->description && $object->lines[$i]->description!=$product_static->libelle)?'<br>'.dol_htmlentitiesbr($object->lines[$i]->description):'';
                }

                // Description - Editor wysiwyg
                if (! $object->lines[$i]->fk_product)
                {
                    if ($type==1) $text = img_object($langs->trans('Service'),'service');
                    else $text = img_object($langs->trans('Product'),'product');
                    print $text.' '.nl2br($object->lines[$i]->description);

                    // Show range
                    print_date_range($date_start,$date_end);
                }

                if (is_object($hookmanager))
                {
                	$parameters=array('fk_parent_line'=>$line->fk_parent_line, 'line'=>$object->lines[$i],'var'=>$var,'num'=>$num,'i'=>$i);
                	$reshook=$hookmanager->executeHooks('formViewProductSupplierOptions',$parameters,$object,$action);
                }
                print '</td>';

                // VAT
                print '<td align="right">'.vatrate($object->lines[$i]->tva_tx, true, $object->lines[$i]->info_bits).'</td>';

                // Unit price
                print '<td align="right" class="nowrap">'.price($object->lines[$i]->pu_ht,'MU').'</td>';

                print '<td align="right" class="nowrap">'.($object->lines[$i]->pu_ttc?price($object->lines[$i]->pu_ttc,'MU'):'&nbsp;').'</td>';

                print '<td align="right">'.$object->lines[$i]->qty.'</td>';

                print '<td align="right">'.(($object->lines[$i]->remise_percent > 0)?$object->lines[$i]->remise_percent.'%':'').'</td>';

                print '<td align="right" class="nowrap">'.price($object->lines[$i]->total_ht).'</td>';

                print '<td align="right" class="nowrap">'.price($object->lines[$i]->total_ttc).'</td>';

				if (is_object($hookmanager))
				{
					$parameters=array('line'=>$object->lines[$i],'num'=>$num,'i'=>$i);
					$reshook=$hookmanager->executeHooks('printObjectLine',$parameters,$object,$action);
				}

                print '<td align="center" width="16">';
                if ($object->statut == 0) print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=edit_line&amp;etat=0&amp;lineid='.$object->lines[$i]->rowid.'">'.img_edit().'</a>';
                else print '&nbsp;';
                print '</td>';

                print '<td align="center" width="16">';
                if ($object->statut == 0)
                {
                	print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=confirm_delete_line&amp;lineid='.$object->lines[$i]->rowid.'">'.img_delete().'</a>';
                }
                else print '&nbsp;';
                print '</td>';

                print '</tr>';
            }

        }

		// Form to add new line
        if ($object->statut == 0 && $action != 'edit_line')
        {
       		global $forceall, $senderissupplier, $dateSelector, $inputalsopricewithtax;
			$forceall=1; $senderissupplier=1; $dateSelector=0; $inputalsopricewithtax=1;
			if ($object->statut == 0 && $user->rights->fournisseur->facture->creer)
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

		    // Modify a validated invoice with no payments
			if ($object->statut == 1 && $action != 'edit' && $object->getSommePaiement() == 0 && $user->rights->fournisseur->facture->creer)
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
            if (($object->statut == 1 || $object->statut == 2))
            {
                if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->fournisseur->supplier_invoice_advance->send)
                {
                    print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                }
                else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
            }


            // Make payments
            if ($action != 'edit' && $object->statut == 1 && $object->paye == 0  && $user->societe_id == 0)
            {
                print '<a class="butAction" href="paiement.php?facid='.$object->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a>';	// must use facid because id is for payment id not invoice
            }

            // Classify paid
            if ($action != 'edit' && $object->statut == 1 && $object->paye == 0  && $user->societe_id == 0)
            {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=paid"';
                print '>'.$langs->trans('ClassifyPaid').'</a>';

                //print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=paid">'.$langs->trans('ClassifyPaid').'</a>';
            }

            // Validate
            if ($action != 'edit' && $object->statut == 0)
            {
                if (count($object->lines))
                {
                    if ($user->rights->fournisseur->facture->valider)
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
                $subdir = get_exdir($object->id,2).$ref;
                $filedir = $conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$ref;
                $urlsource=$_SERVER['PHP_SELF'].'?id='.$object->id;
                $genallowed=$user->rights->fournisseur->facture->creer;
                $delallowed=$user->rights->fournisseur->facture->supprimer;
                $modelpdf=(! empty($object->modelpdf)?$object->modelpdf:(empty($conf->global->INVOICE_SUPPLIER_ADDON_PDF)?'':$conf->global->INVOICE_SUPPLIER_ADDON_PDF));

                print $formfile->showdocuments('facture_fournisseur',$subdir,$filedir,$urlsource,$genallowed,$delallowed,$modelpdf,1,0,0,40,0,'','','',$societe->default_lang);
                $somethingshown=$formfile->numoffiles;

                /*
                 * Linked object block
                 */
                $somethingshown=$object->showLinkedObjectBlock();

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
        /*
         * Show mail form
        */
        if ($action == 'presend')
        {
            $ref = dol_sanitizeFileName($object->ref);
            include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            $fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$ref, preg_quote($ref,'/'));
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

                $result=supplier_invoice_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
                if ($result <= 0)
                {
                    dol_print_error($db,$result);
                    exit;
                }
                $fileparams = dol_most_recent_file($conf->fournisseur->facture->dir_output.'/'.get_exdir($object->id,2).$ref, preg_quote($ref,'/'));
                $file=$fileparams['fullname'];
            }

            print '<br>';
            print_titre($langs->trans('SendBillByMail'));

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
            $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
            $formmail->withtopic=$langs->trans('SendBillRef','__FACREF__');
            $formmail->withfile=2;
            $formmail->withbody=1;
            $formmail->withdeliveryreceipt=1;
            $formmail->withcancel=1;
            // Tableau des substitutions
            $formmail->substit['__FACREF__']=$object->ref;
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

            print '<br>';
        }
    }
}


// End of page
llxFooter();
$db->close();
