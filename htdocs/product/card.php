<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005		Eric Seigne				<eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2015	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2006		Auguria SARL			<info@auguria.org>
 * Copyright (C) 2010-2014	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2013-2014	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2012-2013	Cédric Salvador			<csalvador@gpcsolutions.fr>
 * Copyright (C) 2011-2014	Alexandre Spangaro		<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2014		Cédric Gross			<c.gross@kreiz-it.fr>
 * Copyright (C) 2014-2015	Ferran Marcet			<fmarcet@2byte.es>
 * Copyright (C) 2015       Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2015       Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *  \file       htdocs/product/card.php
 *  \ingroup    product
 *  \brief      Page to show product
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
if (! empty($conf->propal->enabled))   require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))  require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->commande->enabled)) require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';

$langs->load("products");
$langs->load("other");
if (! empty($conf->stock->enabled)) $langs->load("stocks");
if (! empty($conf->facture->enabled)) $langs->load("bills");
if (! empty($conf->productbatch->enabled)) $langs->load("productbatch");

$mesg=''; $error=0; $errors=array();

$refalreadyexists=0;

$id=GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$type=GETPOST('type','int');
$action=(GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$cancel=GETPOST('cancel');
$confirm=GETPOST('confirm','alpha');
$socid=GETPOST('socid','int');
if (! empty($user->societe_id)) $socid=$user->societe_id;

$object = new Product($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

if ($id > 0 || ! empty($ref))
{
	$object = new Product($db);
	$object->fetch($id, $ref);
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = !empty($object->canvas)?$object->canvas:GETPOST("canvas");
$objcanvas=null;
if (! empty($canvas))
{
    require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','card',$canvas);
}

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype,$objcanvas);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('productcard','globalcard'));



/*
 * Actions
 */

if ($cancel) $action = '';

$createbarcode=empty($conf->barcode->enabled)?0:1;
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->creer_advance)) $createbarcode=0;

$parameters=array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    // Type
    if ($action ==	'setfk_product_type' && $user->rights->produit->creer)
    {
    	$result = $object->setValueFrom('fk_product_type', GETPOST('fk_product_type'));
    	header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    // Barcode type
    if ($action ==	'setfk_barcode_type' && $createbarcode)
    {
        $result = $object->setValueFrom('fk_barcode_type', GETPOST('fk_barcode_type'));
    	header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    // Barcode value
    if ($action ==	'setbarcode' && $createbarcode)
    {
    	$result=$object->check_barcode(GETPOST('barcode'),GETPOST('barcode_type_code'));

		if ($result >= 0)
		{
	    	$result = $object->setValueFrom('barcode', GETPOST('barcode'));
	    	header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
	    	exit;
		}
		else
		{
			$langs->load("errors");
        	if ($result == -1) $errors[] = 'ErrorBadBarCodeSyntax';
        	else if ($result == -2) $errors[] = 'ErrorBarCodeRequired';
        	else if ($result == -3) $errors[] = 'ErrorBarCodeAlreadyUsed';
        	else $errors[] = 'FailedToValidateBarCode';

			$error++;
			setEventMessage($errors,'errors');
		}
    }

    if ($action == 'setaccountancy_code_buy') {

	    $result = $object->setAccountancyCode('buy', GETPOST('accountancy_code_buy'));
        if ($result < 0) setEventMessage(join(',',$object->errors), 'errors');
        $action="";
    }

    if ($action == 'setaccountancy_code_sell')
    {
	    $result = $object->setAccountancyCode('sell', GETPOST('accountancy_code_sell'));
	    if ($result < 0) setEventMessage(join(',',$object->errors), 'errors');
	    $action="";
    }

    // Add a product or service
    if ($action == 'add' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
        $error=0;

        if (! GETPOST('label'))
        {
            setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentities('Label')), 'errors');
            $action = "create";
            $error++;
        }
        if (empty($ref))
        {
            setEventMessage($langs->trans('ErrorFieldRequired',$langs->transnoentities('Ref')), 'errors');
            $action = "create";
            $error++;
        }

        if (! $error)
        {
	        $units = GETPOST('units', 'int');

            $object->ref                   = $ref;
            $object->label                 = GETPOST('label');
            $object->price_base_type       = GETPOST('price_base_type');

            if ($object->price_base_type == 'TTC')
            	$object->price_ttc = GETPOST('price');
            else
            	$object->price = GETPOST('price');
            if ($object->price_base_type == 'TTC')
            	$object->price_min_ttc = GETPOST('price_min');
            else
            	$object->price_min = GETPOST('price_min');

            $object->tva_tx                = str_replace('*','',GETPOST('tva_tx'));
            $object->tva_npr               = preg_match('/\*/',GETPOST('tva_tx'))?1:0;

            // local taxes.
            $object->localtax1_tx 			   = get_localtax($object->tva_tx,1);
            $object->localtax2_tx 			   = get_localtax($object->tva_tx,2);

            $object->type               	 = $type;
            $object->status             	 = GETPOST('statut','int');
            $object->status_buy            = GETPOST('statut_buy','int');
			$object->status_batch          	= GETPOST('status_batch','int');

            $object->barcode_type          = GETPOST('fk_barcode_type');
            $object->barcode		           = GETPOST('barcode');
            // Set barcode_type_xxx from barcode_type id
            $stdobject=new GenericObject($db);
    	    $stdobject->element='product';
            $stdobject->barcode_type=GETPOST('fk_barcode_type');
            $result=$stdobject->fetch_barcode();
            if ($result < 0)
            {
            	$error++;
            	setEventMessage('Failed to get bar code type information '.$stdobject->error, 'errors');
            }
            $object->barcode_type_code      = $stdobject->barcode_type_code;
            $object->barcode_type_coder     = $stdobject->barcode_type_coder;
            $object->barcode_type_label     = $stdobject->barcode_type_label;

            $object->description        	 = dol_htmlcleanlastbr(GETPOST('desc'));
            $object->url					 = GETPOST('url');
            $object->note               	 = dol_htmlcleanlastbr(GETPOST('note'));
            $object->customcode              = GETPOST('customcode');
            $object->country_id              = GETPOST('country_id','int');
            $object->duration_value     	 = GETPOST('duration_value');
            $object->duration_unit      	 = GETPOST('duration_unit','alpha');
            $object->seuil_stock_alerte 	 = GETPOST('seuil_stock_alerte','int')?GETPOST('seuil_stock_alerte','int'):0;
            $object->desiredstock            = GETPOST('desiredstock','int')?GETPOST('desiredstock','int'):0;
            $object->canvas             	 = GETPOST('canvas');
            $object->weight             	 = GETPOST('weight');
            $object->weight_units       	 = GETPOST('weight_units');
            $object->length             	 = GETPOST('size');
            $object->length_units       	 = GETPOST('size_units');
            $object->surface            	 = GETPOST('surface');
            $object->surface_units      	 = GETPOST('surface_units');
            $object->volume             	 = GETPOST('volume');
            $object->volume_units       	 = GETPOST('volume_units');
            $object->finished           	 = GETPOST('finished');
            $object->hidden             	 = GETPOST('hidden')=='yes'?1:0;
	        $object->fk_unit                 = GETPOST('units');
            $object->accountancy_code_sell = GETPOST('accountancy_code_sell');
            $object->accountancy_code_buy  = GETPOST('accountancy_code_buy');

            // MultiPrix
            if (! empty($conf->global->PRODUIT_MULTIPRICES))
            {
                for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
                {
                    if (isset($_POST["price_".$i]))
                    {
                        $object->multiprices["$i"] = price2num($_POST["price_".$i],'MU');
                        $object->multiprices_base_type["$i"] = $_POST["multiprices_base_type_".$i];
                    }
                    else
                    {
                        $object->multiprices["$i"] = "";
                    }
                }
            }

            // Fill array 'array_options' with data from add form
        	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

			if (! $error)
			{
            	$id = $object->create($user);
			}

            if ($id > 0)
            {
				// Category association
				$categories = GETPOST('categories');
				if(!empty($categories)) {
					$cat = new Categorie($db);
					foreach($categories as $id_category) {
						$cat->fetch($id_category);
						$cat->add_type($object, 'product');
					}
				}

                header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
                exit;
            }
            else
			{
            	if (count($object->errors)) setEventMessage($object->errors, 'errors');
				else setEventMessage($langs->trans($object->error), 'errors');
                $action = "create";
            }
        }
    }

    // Update a product or service
    if ($action == 'update' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
    	if (GETPOST('cancel'))
        {
            $action = '';
        }
        else
        {
            if ($object->id > 0)
            {
            	$object->oldcopy=dol_clone($object);

                $object->ref                    = $ref;
                $object->label                  = GETPOST('label');
                $object->description            = dol_htmlcleanlastbr(GETPOST('desc'));
            	$object->url					= GETPOST('url');
                $object->note                   = dol_htmlcleanlastbr(GETPOST('note'));
                $object->customcode             = GETPOST('customcode');
                $object->country_id             = GETPOST('country_id','int');
                $object->status                 = GETPOST('statut','int');
                $object->status_buy             = GETPOST('statut_buy','int');
                $object->status_batch	        = GETPOST('status_batch','int');
                $object->seuil_stock_alerte     = GETPOST('seuil_stock_alerte','int');
                $object->desiredstock           = GETPOST('desiredstock','int');
                $object->duration_value         = GETPOST('duration_value');
                $object->duration_unit          = GETPOST('duration_unit');
                $object->canvas                 = GETPOST('canvas');
                $object->weight                 = GETPOST('weight');
                $object->weight_units           = GETPOST('weight_units');
                $object->length                 = GETPOST('size');
                $object->length_units           = GETPOST('size_units');
                $object->surface                = GETPOST('surface');
                $object->surface_units          = GETPOST('surface_units');
                $object->volume                 = GETPOST('volume');
                $object->volume_units           = GETPOST('volume_units');
                $object->finished               = GETPOST('finished');
                $object->hidden                 = GETPOST('hidden')=='yes'?1:0;

	            $units = GETPOST('units', 'int');

	            if ($units > 0) {
		            $object->fk_unit = $units;
	            } else {
		            $object->fk_unit = null;
	            }

	            $object->barcode_type           = GETPOST('fk_barcode_type');
    	        $object->barcode		        = GETPOST('barcode');
    	        // Set barcode_type_xxx from barcode_type id
    	        $stdobject=new GenericObject($db);
    	        $stdobject->element='product';
    	        $stdobject->barcode_type=GETPOST('fk_barcode_type');
    	        $result=$stdobject->fetch_barcode();
    	        if ($result < 0)
    	        {
    	        	$error++;
    	        	setEventMessage('Failed to get bar code type information '.$stdobject->error, 'errors');
    	        }
    	        $object->barcode_type_code      = $stdobject->barcode_type_code;
    	        $object->barcode_type_coder     = $stdobject->barcode_type_coder;
    	        $object->barcode_type_label     = $stdobject->barcode_type_label;

            	$object->accountancy_code_sell  = GETPOST('accountancy_code_sell');
                $object->accountancy_code_buy   = GETPOST('accountancy_code_buy');

                // Fill array 'array_options' with data from add form
        		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
				if ($ret < 0) $error++;

                if (! $error && $object->check())
                {
                    if ($object->update($object->id, $user) > 0)
                    {
						// Category association
						// First we delete all categories association
						$sql  = "DELETE FROM ".MAIN_DB_PREFIX."categorie_product";
						$sql .= " WHERE fk_product = ".$object->id;
						$db->query($sql);

						// Then we add the associated categories
						$categories = GETPOST('categories');
						if(!empty($categories)) {
							$cat = new Categorie($db);

							foreach($categories as $id_category) {
								$cat->fetch($id_category);
								$cat->add_type($object, 'product');
							}
						}

                        $action = 'view';
                    }
                    else
					{
						if (count($object->errors)) setEventMessage($object->errors, 'errors');
                    	else setEventMessage($langs->trans($object->error), 'errors');
                        $action = 'edit';
                    }
                }
                else
				{
					if (count($object->errors)) setEventMessage($object->errors, 'errors');
                	else setEventMessage($langs->trans("ErrorProductBadRefOrLabel"), 'errors');
                    $action = 'edit';
                }
            }

        }
    }

    // Action clone object
    if ($action == 'confirm_clone' && $confirm != 'yes') { $action=''; }
    if ($action == 'confirm_clone' && $confirm == 'yes' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
        if (! GETPOST('clone_content') && ! GETPOST('clone_prices') )
        {
        	setEventMessage($langs->trans("NoCloneOptionsSpecified"), 'errors');
        }
        else
        {
            $db->begin();

            $originalId = $id;
            if ($object->id > 0)
            {
                $object->ref = GETPOST('clone_ref');
                $object->status = 0;
                $object->status_buy = 0;
                $object->id = null;
                $object->barcode = -1;

                if ($object->check())
                {
                    $id = $object->create($user);
                    if ($id > 0)
                    {
                        if (GETPOST('clone_composition'))
                        {
                            $result = $object->clone_associations($originalId, $id);

                            if ($result < 1)
                            {
                                $db->rollback();
                                setEventMessage($langs->trans('ErrorProductClone'), 'errors');
                                header("Location: ".$_SERVER["PHP_SELF"]."?id=".$originalId);
                                exit;
                            }
                        }

                        // $object->clone_fournisseurs($originalId, $id);

                        $db->commit();
                        $db->close();

                        header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
                        exit;
                    }
                    else
                    {
                        $id=$originalId;

                        if ($object->error == 'ErrorProductAlreadyExists')
                        {
                            $db->rollback();

                            $refalreadyexists++;
                            $action = "";

                            $mesg=$langs->trans("ErrorProductAlreadyExists",$object->ref);
                            $mesg.=' <a href="'.$_SERVER["PHP_SELF"].'?ref='.$object->ref.'">'.$langs->trans("ShowCardHere").'</a>.';
                            setEventMessage($mesg, 'errors');
                            $object->fetch($id);
                        }
                        else
                     {
                            $db->rollback();
                            if (count($object->errors))
                            {
                            	setEventMessage($object->errors, 'errors');
                            	dol_print_error($db,$object->errors);
                            }
                            else
                            {
                            	setEventMessage($langs->trans($object->error), 'errors');
                            	dol_print_error($db,$object->error);
                            }
                        }
                    }
                }
            }
            else
            {
                $db->rollback();
                dol_print_error($db,$object->error);
            }
        }
    }

    // Delete a product
    if ($action == 'confirm_delete' && $confirm != 'yes') { $action=''; }
    if ($action == 'confirm_delete' && $confirm == 'yes')
    {
        if (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->supprimer) || ($object->type == Product::TYPE_SERVICE && $user->rights->service->supprimer))
        {
            $result = $object->delete($object->id);
        }

        if ($result > 0)
        {
            header('Location: '.DOL_URL_ROOT.'/product/list.php?type='.$object->type.'&delprod='.urlencode($object->ref));
            exit;
        }
        else
        {
        	setEventMessage($langs->trans($object->error), 'errors');
            $reload = 0;
            $action='';
        }
    }


    // Add product into object
    if ($object->id > 0 && $action == 'addin')
    {
        if (GETPOST('propalid') > 0)
        {
        	$propal = new Propal($db);
	        $result=$propal->fetch(GETPOST('propalid'));
	        if ($result <= 0)
	        {
	            dol_print_error($db,$propal->error);
	            exit;
	        }
	        $thirpdartyid = $propal->socid;
        }
        elseif (GETPOST('commandeid') > 0)
        {
            $commande = new Commande($db);
	        $result=$commande->fetch(GETPOST('commandeid'));
	        if ($result <= 0)
	        {
	            dol_print_error($db,$commande->error);
	            exit;
	        }
	        $thirpdartyid = $commande->socid;
        }
        elseif (GETPOST('factureid') > 0)
        {
    	    $facture = new Facture($db);
	        $result=$facture->fetch(GETPOST('factureid'));
	        if ($result <= 0)
	        {
	            dol_print_error($db,$facture->error);
	            exit;
	        }
	        $thirpdartyid = $facture->socid;
        }

        $soc = new Societe($db);
        $result=$soc->fetch($thirpdartyid);
        if ($result <= 0)
        {
            dol_print_error($db,$soc->error);
            exit;
        }

        $desc = $object->description;

        $tva_tx = get_default_tva($mysoc, $soc, $object->id);
        $localtax1_tx= get_localtax($tva_tx, 1, $soc);
        $localtax2_tx= get_localtax($tva_tx, 2, $soc);

        $pu_ht = $object->price;
        $pu_ttc = $object->price_ttc;
        $price_base_type = $object->price_base_type;

        // If multiprice
        if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
        {
            $pu_ht = $object->multiprices[$soc->price_level];
            $pu_ttc = $object->multiprices_ttc[$soc->price_level];
            $price_base_type = $object->multiprices_base_type[$soc->price_level];
        }
   		elseif (! empty($conf->global->PRODUIT_CUSTOMER_PRICES))
		{
			require_once DOL_DOCUMENT_ROOT . '/product/class/productcustomerprice.class.php';

			$prodcustprice = new Productcustomerprice($db);

			$filter = array('t.fk_product' => $object->id,'t.fk_soc' => $soc->id);

			$result = $prodcustprice->fetch_all('', '', 0, 0, $filter);
			if ($result) {
				if (count($prodcustprice->lines) > 0) {
					$pu_ht = price($prodcustprice->lines [0]->price);
					$pu_ttc = price($prodcustprice->lines [0]->price_ttc);
					$price_base_type = $prodcustprice->lines [0]->price_base_type;
					$prod->tva_tx = $prodcustprice->lines [0]->tva_tx;
				}
			}
		}

        // On reevalue prix selon taux tva car taux tva transaction peut etre different
        // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
        if ($tva_tx != $object->tva_tx)
        {
            if ($price_base_type != 'HT')
            {
                $pu_ht = price2num($pu_ttc / (1 + ($tva_tx/100)), 'MU');
            }
            else
            {
                $pu_ttc = price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
            }
        }

        if (GETPOST('propalid') > 0)
        {
	        $result = $propal->addline(
	            $desc,
	            $pu_ht,
	            GETPOST('qty'),
	            $tva_tx,
	            $localtax1_tx, // localtax1
	            $localtax2_tx, // localtax2
	            $object->id,
	            GETPOST('remise_percent'),
	            $price_base_type,
	            $pu_ttc,
		        0,
		        0,
		        -1,
		        0,
		        0,
		        0,
		        0,
		        '',
		        '',
		        '',
		        0,
		        $object->fk_unit
	        );
	        if ($result > 0)
	        {
	            header("Location: ".DOL_URL_ROOT."/comm/propal.php?id=".$propal->id);
	            return;
	        }

        	setEventMessage($langs->trans("ErrorUnknown").": $result", 'errors');
        }
        elseif (GETPOST('commandeid') > 0)
        {
            $result =  $commande->addline(
	            $desc,
	            $pu_ht,
	            GETPOST('qty'),
	            $tva_tx,
	            $localtax1_tx, // localtax1
	            $localtax2_tx, // localtax2
	            $object->id,
	            GETPOST('remise_percent'),
	            '',
	            '',
	            $price_base_type,
	            $pu_ttc,
		        '',
		        '',
		        0,
		        -1,
		        0,
		        0,
		        null,
		        0,
		        '',
		        0,
		        $object->fk_unit
	        );

	        if ($result > 0)
	        {
	            header("Location: ".DOL_URL_ROOT."/commande/card.php?id=".$commande->id);
	            exit;
	        }
        }
		elseif (GETPOST('factureid') > 0)
		{
	        $result = $facture->addline(
	            $desc,
	            $pu_ht,
	            GETPOST('qty'),
	            $tva_tx,
	            $localtax1_tx,
	            $localtax2_tx,
	            $object->id,
	            GETPOST('remise_percent'),
	            '',
	            '',
	            '',
	            '',
	            '',
	            $price_base_type,
	            $pu_ttc,
		        Facture::TYPE_STANDARD,
		        -1,
		        0,
		        '',
		        0,
		        0,
		        null,
		        0,
		        '',
		        0,
		        100,
		        '',
		        $object->fk_unit
	        );

	        if ($result > 0)
	        {
	            header("Location: ".DOL_URL_ROOT."/compta/facture.php?facid=".$facture->id);
	            exit;
	        }
		}
    }
}



/*
 * View
 */

$helpurl='';
if (GETPOST("type") == '0' || ($object->type == Product::TYPE_PRODUCT)) $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
if (GETPOST("type") == '1' || ($object->type == Product::TYPE_SERVICE)) $helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';

if (isset($_GET['type'])) $title = $langs->trans('CardProduct'.GETPOST('type'));
else $title = $langs->trans('ProductServiceCard');

llxHeader('', $title, $helpurl);

$form = new Form($db);
$formproduct = new FormProduct($db);


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
	// -----------------------------------------
	// When used with CANVAS
	// -----------------------------------------
	if (empty($object->error) && $id)
	{
		$object = new Product($db);
		$result=$object->fetch($id);
		if ($result <= 0) dol_print_error('',$object->error);
	}
	$objcanvas->assign_values($action, $object->id, $object->ref);	// Set value for templates
	$objcanvas->display_canvas($action);							// Show template
}
else
{
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------
    if ($action == 'create' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
        //WYSIWYG Editor
        require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

		// Load object modCodeProduct
        $module=(! empty($conf->global->PRODUCT_CODEPRODUCT_ADDON)?$conf->global->PRODUCT_CODEPRODUCT_ADDON:'mod_codeproduct_leopard');
        if (substr($module, 0, 16) == 'mod_codeproduct_' && substr($module, -3) == 'php')
        {
            $module = substr($module, 0, dol_strlen($module)-4);
        }
        $result=dol_include_once('/core/modules/product/'.$module.'.php');
        if ($result > 0)
        {
        	$modCodeProduct = new $module();
        }

		// Load object modBarCodeProduct
		if (! empty($conf->barcode->enabled) && ! empty($conf->global->BARCODE_PRODUCT_ADDON_NUM))
		{
			$module=strtolower($conf->global->BARCODE_PRODUCT_ADDON_NUM);
			$dirbarcode=array_merge(array('/core/modules/barcode/'),$conf->modules_parts['barcode']);
            foreach ($dirbarcode as $dirroot)
            {
                $res=dol_include_once($dirroot.$module.'.php');
                if ($res) break;
            }
        	if ($res > 0)
        	{
				$modBarCodeProduct =new $module();
        	}
		}

        print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="type" value="'.$type.'">'."\n";
		if (! empty($modCodeProduct->code_auto))
			print '<input type="hidden" name="code_auto" value="1">';
		if (! empty($modBarCodeProduct->code_auto))
			print '<input type="hidden" name="barcode_auto" value="1">';

        if ($type==1) $title=$langs->trans("NewService");
        else $title=$langs->trans("NewProduct");
        $linkback="";
        print_fiche_titre($title,$linkback,'title_products.png');

        dol_fiche_head('');

        print '<table class="border" width="100%">';
        print '<tr>';
        $tmpcode='';
		if (! empty($modCodeProduct->code_auto)) $tmpcode=$modCodeProduct->getNextValue($object,$type);
        print '<td class="fieldrequired" width="20%">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" size="32" maxlength="128" value="'.dol_escape_htmltag(GETPOST('ref')?GETPOST('ref'):$tmpcode).'">';
        if ($refalreadyexists)
        {
            print $langs->trans("RefAlreadyExists");
        }
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" size="40" maxlength="255" value="'.dol_escape_htmltag(GETPOST('label')).'"></td></tr>';

        // On sell
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
        $statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
        print $form->selectarray('statut',$statutarray,GETPOST('statut'));
        print '</td></tr>';

        // To buy
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
        $statutarray=array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
        print $form->selectarray('statut_buy',$statutarray,GETPOST('statut_buy'));
        print '</td></tr>';

	    // Batch number management
		if (! empty($conf->productbatch->enabled))
		{
			print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td colspan="3">';
			$statutarray=array('0' => $langs->trans("ProductStatusNotOnBatch"), '1' => $langs->trans("ProductStatusOnBatch"));
			print $form->selectarray('status_batch',$statutarray,GETPOST('status_batch'));
			print '</td></tr>';
		}

        $showbarcode=empty($conf->barcode->enabled)?0:1;
        if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;

        if ($showbarcode)
        {
 	        print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
 	        if (isset($_POST['fk_barcode_type']))
	        {
	         	$fk_barcode_type=GETPOST('fk_barcode_type');
	        }
	        else
	        {
	        	if (empty($fk_barcode_type) && ! empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) $fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
	        }
	        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
            $formbarcode = new FormBarCode($db);
	        print $formbarcode->select_barcode_type($fk_barcode_type, 'fk_barcode_type', 1);
	        print '</td><td>'.$langs->trans("BarcodeValue").'</td><td>';
	        $tmpcode=isset($_POST['barcode'])?GETPOST('barcode'):$object->barcode;
	        if (empty($tmpcode) && ! empty($modBarCodeProduct->code_auto)) $tmpcode=$modBarCodeProduct->getNextValue($object,$type);
	        print '<input size="40" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
	        print '</td></tr>';
        }

        // Description (used in invoice, propal...)
        print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';

        $doleditor = new DolEditor('desc', GETPOST('desc'), '', 160, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 4, 80);
        $doleditor->Create();

        print "</td></tr>";

        // Public URL
        print '<tr><td>'.$langs->trans("PublicUrl").'</td><td colspan="3">';
		print '<input type="text" name="url" size="90" value="'.GETPOST('url').'">';
        print '</td></tr>';

        // Stock min level
        if ($type != 1 && ! empty($conf->stock->enabled))
        {
            print '<tr><td>'.$langs->trans("StockLimit").'</td><td>';
            print '<input name="seuil_stock_alerte" size="4" value="'.GETPOST('seuil_stock_alerte').'">';
            print '</td>';
            // Stock desired level
            print '<td>'.$langs->trans("DesiredStock").'</td><td>';
            print '<input name="desiredstock" size="4" value="'.GETPOST('desiredstock').'">';
            print '</td></tr>';
        }
        else
        {
            print '<input name="seuil_stock_alerte" type="hidden" value="0">';
            print '<input name="desiredstock" type="hidden" value="0">';
        }

        // Nature
        if ($type != 1)
        {
            print '<tr><td>'.$langs->trans("Nature").'</td><td colspan="3">';
            $statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
            print $form->selectarray('finished',$statutarray,GETPOST('finished'),1);
            print '</td></tr>';
        }

        // Duration
        if ($type == 1)
        {
            print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3"><input name="duration_value" size="6" maxlength="5" value="'.GETPOST('duration_value').'"> &nbsp;';
            print '<input name="duration_unit" type="radio" value="h">'.$langs->trans("Hour").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="d">'.$langs->trans("Day").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="w">'.$langs->trans("Week").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="m">'.$langs->trans("Month").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="y">'.$langs->trans("Year").'&nbsp;';
            print '</td></tr>';
        }

        if ($type != 1)	// Le poids et le volume ne concerne que les produits et pas les services
        {
            // Weight
            print '<tr><td>'.$langs->trans("Weight").'</td><td colspan="3">';
            print '<input name="weight" size="4" value="'.GETPOST('weight').'">';
            print $formproduct->select_measuring_units("weight_units","weight");
            print '</td></tr>';
            // Length
            print '<tr><td>'.$langs->trans("Length").'</td><td colspan="3">';
            print '<input name="size" size="4" value="'.GETPOST('size').'">';
            print $formproduct->select_measuring_units("size_units","size");
            print '</td></tr>';
            // Surface
            print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="3">';
            print '<input name="surface" size="4" value="'.GETPOST('surface').'">';
            print $formproduct->select_measuring_units("surface_units","surface");
            print '</td></tr>';
            // Volume
            print '<tr><td>'.$langs->trans("Volume").'</td><td colspan="3">';
            print '<input name="volume" size="4" value="'.GETPOST('volume').'">';
            print $formproduct->select_measuring_units("volume_units","volume");
            print '</td></tr>';
        }

        // Units
	    if($conf->global->PRODUCT_USE_UNITS)
	    {
		    print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td>';
		    print '<td colspan="3">';
		    print $form->selectUnits('','units');
		    print '</td></tr>';
	    }

        // Custom code
        if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO))
        {
	        print '<tr><td>'.$langs->trans("CustomCode").'</td><td><input name="customcode" size="10" value="'.GETPOST('customcode').'"></td>';
	        // Origin country
	        print '<td>'.$langs->trans("CountryOrigin").'</td><td>';
	        print $form->select_country(GETPOST('country_id','int'),'country_id');
	        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	        print '</td></tr>';
        }

        // Other attributes
        $parameters=array('colspan' => 3);
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
        	print $object->showOptionals($extrafields,'edit',$parameters);
        }

        // Note (private, no output on invoices, propales...)
        print '<tr><td class="tdtop">'.$langs->trans("NoteNotVisibleOnBill").'</td><td colspan="3">';

        // We use dolibarr_details as type of DolEditor here, because we must not accept images as description is included into PDF and not accepted by TCPDF.
        $doleditor = new DolEditor('note', GETPOST('note'), '', 140, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 8, 70);
	    $doleditor->Create();

        print "</td></tr>";

		if($conf->categorie->enabled) {
			// Categories
			print '<tr><td>'.$langs->trans("Categories").'</td><td colspan="3">';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
			print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
			print "</td></tr>";
		}

        print '</table>';

        print '<br>';

        if (! empty($conf->global->PRODUIT_MULTIPRICES))
        {
            // We do no show price array on create when multiprices enabled.
            // We must set them on prices tab.
        }
        else
		{
            print '<table class="border" width="100%">';

            // Price
            print '<tr><td>'.$langs->trans("SellingPrice").'</td>';
            print '<td><input name="price" size="10" value="'.$object->price.'">';
            print $form->selectPriceBaseType($object->price_base_type, "price_base_type");
            print '</td></tr>';

            // Min price
            print '<tr><td>'.$langs->trans("MinPrice").'</td>';
            print '<td><input name="price_min" size="10" value="'.$object->price_min.'">';
            print '</td></tr>';

            // VAT
            print '<tr><td width="20%">'.$langs->trans("VATRate").'</td><td>';
            print $form->load_tva("tva_tx",-1,$mysoc,'');
            print '</td></tr>';

            print '</table>';

            print '<br>';
        }

        print '<table class="border" width="100%">';

        // Accountancy_code_sell
        print '<tr><td>'.$langs->trans("ProductAccountancySellCode").'</td>';
        print '<td><input name="accountancy_code_sell" size="16" value="'.$object->accountancy_code_sell.'">';
        print '</td></tr>';

        // Accountancy_code_buy
        print '<tr><td width="20%">'.$langs->trans("ProductAccountancyBuyCode").'</td>';
        print '<td><input name="accountancy_code_buy" size="16" value="'.$object->accountancy_code_buy.'">';
        print '</td></tr>';

        print '</table>';

        print '<br>';

        dol_fiche_end();

        print '<div class="center"><input type="submit" class="button" value="'.$langs->trans("Create").'"></div>';

        print '</form>';
    }

    /*
     * Product card
     */

    else if ($object->id > 0)
    {
        // Fiche en mode edition
        if ($action == 'edit' && ($user->rights->produit->creer || $user->rights->service->creer))
        {
            //WYSIWYG Editor
            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

            $type = $langs->trans('Product');
            if ($object->isservice()) $type = $langs->trans('Service');
            //print_fiche_titre($langs->trans('Modify').' '.$type.' : '.(is_object($object->oldcopy)?$object->oldcopy->ref:$object->ref), "");

            // Main official, simple, and not duplicated code
            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';
            print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';

            $head=product_prepare_head($object);
            $titre=$langs->trans("CardProduct".$object->type);
            $picto=($object->type== Product::TYPE_SERVICE?'service':'product');
            dol_fiche_head($head, 'card', $titre, 0, $picto);

            print '<table class="border allwidth">';

            // Ref
            print '<tr><td width="20%" class="fieldrequired">'.$langs->trans("Ref").'</td><td colspan="3"><input name="ref" size="32" maxlength="128" value="'.dol_escape_htmltag($object->ref).'"></td></tr>';

            // Label
            print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="3"><input name="label" size="40" maxlength="255" value="'.dol_escape_htmltag($object->label).'"></td></tr>';

            // Status To sell
            print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="3">';
            print '<select class="flat" name="statut">';
            if ($object->status)
            {
                print '<option value="1" selected>'.$langs->trans("OnSell").'</option>';
                print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
            }
            else
            {
                print '<option value="1">'.$langs->trans("OnSell").'</option>';
                print '<option value="0" selected>'.$langs->trans("NotOnSell").'</option>';
            }
            print '</select>';
            print '</td></tr>';

            // Status To Buy
            print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="3">';
            print '<select class="flat" name="statut_buy">';
            if ($object->status_buy)
            {
                print '<option value="1" selected>'.$langs->trans("ProductStatusOnBuy").'</option>';
                print '<option value="0">'.$langs->trans("ProductStatusNotOnBuy").'</option>';
            }
            else
            {
                print '<option value="1">'.$langs->trans("ProductStatusOnBuy").'</option>';
                print '<option value="0" selected>'.$langs->trans("ProductStatusNotOnBuy").'</option>';
            }
            print '</select>';
            print '</td></tr>';

			// Batch number managment
			if ($conf->productbatch->enabled) {
				print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td colspan="3">';
				$statutarray=array('0' => $langs->trans("ProductStatusNotOnBatch"), '1' => $langs->trans("ProductStatusOnBatch"));
				print $form->selectarray('status_batch',$statutarray,$object->status_batch);
				print '</td></tr>';
			}

            // Barcode
            $showbarcode=empty($conf->barcode->enabled)?0:1;
            if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;

	        if ($showbarcode)
	        {
		        print '<tr><td>'.$langs->trans('BarcodeType').'</td><td>';
		        if (isset($_POST['fk_barcode_type']))
		        {
		         	$fk_barcode_type=GETPOST('fk_barcode_type');
		        }
		        else
		        {
	        		$fk_barcode_type=$object->barcode_type;
		        	if (empty($fk_barcode_type) && ! empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) $fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
		        }
		        require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
	            $formbarcode = new FormBarCode($db);
		        print $formbarcode->select_barcode_type($fk_barcode_type, 'fk_barcode_type', 1);
		        print '</td><td>'.$langs->trans("BarcodeValue").'</td><td>';
		        $tmpcode=isset($_POST['barcode'])?GETPOST('barcode'):$object->barcode;
		        if (empty($tmpcode) && ! empty($modBarCodeProduct->code_auto)) $tmpcode=$modBarCodeProduct->getNextValue($object,$type);
		        print '<input size="40" type="text" name="barcode" value="'.dol_escape_htmltag($tmpcode).'">';
		        print '</td></tr>';
	        }

            // Description (used in invoice, propal...)
            print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="3">';

            // We use dolibarr_details as type of DolEditor here, because we must not accept images as description is included into PDF and not accepted by TCPDF.
            $doleditor = new DolEditor('desc', $object->description, '', 160, 'dolibarr_details', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 4, 80);
            $doleditor->Create();

            print "</td></tr>";
            print "\n";

            // Public Url
            print '<tr><td>'.$langs->trans("PublicUrl").'</td><td colspan="3">';
			print '<input type="text" name="url" size="80" value="'.$object->url.'">';
            print '</td></tr>';

            // Stock
            /*
            if ($object->isproduct() && ! empty($conf->stock->enabled))
            {
                print "<tr>".'<td>'.$langs->trans("StockLimit").'</td><td>';
                print '<input name="seuil_stock_alerte" size="4" value="'.$object->seuil_stock_alerte.'">';
                print '</td>';

                print '<td>'.$langs->trans("DesiredStock").'</td><td>';
                print '<input name="desiredstock" size="4" value="'.$object->desiredstock.'">';
                print '</td></tr>';
            }
            else
            {
                print '<input name="seuil_stock_alerte" type="hidden" value="'.$object->seuil_stock_alerte.'">';
                print '<input name="desiredstock" type="hidden" value="'.$object->desiredstock.'">';
            }*/

            // Nature
            if($object->type!= Product::TYPE_SERVICE)
            {
                print '<tr><td>'.$langs->trans("Nature").'</td><td colspan="3">';
                $statutarray=array('-1'=>'&nbsp;', '1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
                print $form->selectarray('finished',$statutarray,$object->finished);
                print '</td></tr>';
            }

            if ($object->isservice())
            {
                // Duration
                print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="3"><input name="duration_value" size="3" maxlength="5" value="'.$object->duration_value.'">';
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="h"'.($object->duration_unit=='h'?' checked':'').'>'.$langs->trans("Hour");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="d"'.($object->duration_unit=='d'?' checked':'').'>'.$langs->trans("Day");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="w"'.($object->duration_unit=='w'?' checked':'').'>'.$langs->trans("Week");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="m"'.($object->duration_unit=='m'?' checked':'').'>'.$langs->trans("Month");
                print '&nbsp; ';
                print '<input name="duration_unit" type="radio" value="y"'.($object->duration_unit=='y'?' checked':'').'>'.$langs->trans("Year");

                print '</td></tr>';
            }
            else
			{
                // Weight
                print '<tr><td>'.$langs->trans("Weight").'</td><td colspan="3">';
                print '<input name="weight" size="5" value="'.$object->weight.'"> ';
                print $formproduct->select_measuring_units("weight_units", "weight", $object->weight_units);
                print '</td></tr>';
                // Length
                print '<tr><td>'.$langs->trans("Length").'</td><td colspan="3">';
                print '<input name="size" size="5" value="'.$object->length.'"> ';
                print $formproduct->select_measuring_units("size_units", "size", $object->length_units);
                print '</td></tr>';
                // Surface
                print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="3">';
                print '<input name="surface" size="5" value="'.$object->surface.'"> ';
                print $formproduct->select_measuring_units("surface_units", "surface", $object->surface_units);
                print '</td></tr>';
                // Volume
                print '<tr><td>'.$langs->trans("Volume").'</td><td colspan="3">';
                print '<input name="volume" size="5" value="'.$object->volume.'"> ';
                print $formproduct->select_measuring_units("volume_units", "volume", $object->volume_units);
                print '</td></tr>';
            }
        	// Units
	        if($conf->global->PRODUCT_USE_UNITS)
	        {
		        print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td>';
		        print '<td colspan="3">';
		        print $form->selectUnits($object->fk_unit, 'units');
		        print '</td></tr>';
	        }

	        // Custom code
    	    if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO))
        	{
	            print '<tr><td>'.$langs->trans("CustomCode").'</td><td><input name="customcode" size="10" value="'.$object->customcode.'"></td>';
	            // Origin country
	            print '<td>'.$langs->trans("CountryOrigin").'</td><td>';
	            print $form->select_country($object->country_id,'country_id');
	            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	            print '</td></tr>';
        	}

            // Other attributes
            $parameters=array('colspan' => ' colspan="2"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
            	print $object->showOptionals($extrafields,'edit');
            }

            // Note
            print '<tr><td class="tdtop">'.$langs->trans("NoteNotVisibleOnBill").'</td><td colspan="3">';

            $doleditor = new DolEditor('note', $object->note, '', 140, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_PRODUCTDESC, 4, 80);
            $doleditor->Create();

            print "</td></tr>";

			if($conf->categorie->enabled) {
				// Categories
				print '<tr><td class="tdtop">'.$langs->trans("Categories").'</td><td colspan="3">';
				$cate_arbo = $form->select_all_categories(Categorie::TYPE_PRODUCT, '', 'parent', 64, 0, 1);
				$c = new Categorie($db);
				$cats = $c->containing($object->id,Categorie::TYPE_PRODUCT);
				foreach($cats as $cat) {
					$arrayselected[] = $cat->id;
				}
				print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
				print "</td></tr>";
			}

            print '</table>';

            print '<br>';

            /*if (empty($conf->accounting->enabled) && empty($conf->comptabilite->enabled) && empty($conf->accountingexpert->enabled))
            {
                // Don't show accounting field when accounting id disabled.
            }
            else
            {*/
                print '<table class="border" width="100%">';

                // Accountancy_code_sell
                print '<tr><td width="20%">'.$langs->trans("ProductAccountancySellCode").'</td>';
                print '<td><input name="accountancy_code_sell" size="16" value="'.$object->accountancy_code_sell.'">';
                print '</td></tr>';

                // Accountancy_code_buy
                print '<tr><td width="20%">'.$langs->trans("ProductAccountancyBuyCode").'</td>';
                print '<td><input name="accountancy_code_buy" size="16" value="'.$object->accountancy_code_buy.'">';
                print '</td></tr>';

                print '</table>';
            //}

            dol_fiche_end();

            print '<div class="center">';
			print '<input type="submit" class="button" value="'.$langs->trans("Save").'">';
			print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			print '</div>';

            print '</form>';
        }
        // Fiche en mode visu
        else
		{
            $head=product_prepare_head($object);
            $titre=$langs->trans("CardProduct".$object->type);
            $picto=($object->type== Product::TYPE_SERVICE?'service':'product');
            dol_fiche_head($head, 'card', $titre, 0, $picto);

            $showphoto=$object->is_photo_available($conf->product->multidir_output[$object->entity]);
            $showbarcode=empty($conf->barcode->enabled)?0:1;
            if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->barcode->lire_advance)) $showbarcode=0;

            // En mode visu
            print '<table class="border" width="100%"><tr>';

            // Ref
            print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="'.(2+(($showphoto||$showbarcode)?1:0)).'">';
            print $form->showrefnav($object,'ref','',1,'ref');
            print '</td>';

            print '</tr>';

            // Label
            print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$object->label.'</td>';

            $nblignes=7;
            if (! empty($conf->produit->enabled) && ! empty($conf->service->enabled)) $nblignes++;
            if ($showbarcode) $nblignes+=2;
            if ($object->type!= Product::TYPE_SERVICE) $nblignes++;
            if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO)) $nblignes+=2;
            if ($object->isservice()) $nblignes++;
            else $nblignes+=4;

            // Photo
            if ($showphoto || $showbarcode)
            {
                print '<td valign="middle" align="center" width="25%" rowspan="'.$nblignes.'">';
				print '<div class="photolist">';
				$maxvisiblephotos=(isset($conf->global->PRODUCT_MAX_VISIBLE_PHOTO)?$conf->global->PRODUCT_MAX_VISIBLE_PHOTO:5);
				if ($conf->browser->phone) $maxvisiblephotos=1;
                if ($showphoto)   print $object->show_photos($conf->product->multidir_output[$object->entity],1,$maxvisiblephotos,0,0,0,80);
                if ($showphoto && $showbarcode) print '<br><br>';
                if ($showbarcode) print $form->showbarcode($object);
				print '</div>';
                print '</td>';
            }

            print '</tr>';

            // Type
            if (! empty($conf->produit->enabled) && ! empty($conf->service->enabled))
            {
            	// TODO change for compatibility with edit in place
            	$typeformat='select;0:'.$langs->trans("Product").',1:'.$langs->trans("Service");
                print '<tr><td>'.$form->editfieldkey("Type",'fk_product_type',$object->type,$object,$user->rights->produit->creer||$user->rights->service->creer,$typeformat).'</td><td colspan="2">';
                print $form->editfieldval("Type",'fk_product_type',$object->type,$object,$user->rights->produit->creer||$user->rights->service->creer,$typeformat);
                print '</td></tr>';
            }

            if ($showbarcode)
            {
                // Barcode type
                print '<tr><td class="nowrap">';
                print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
                print $langs->trans("BarcodeType");
                print '<td>';
                if (($action != 'editbarcodetype') && ! empty($user->rights->barcode->creer)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbarcodetype&amp;id='.$object->id.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="2">';
                if ($action == 'editbarcodetype')
                {
                    require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
                    $formbarcode = new FormBarCode($db);
                    $formbarcode->form_barcode_type($_SERVER['PHP_SELF'].'?id='.$object->id,$object->barcode_type,'fk_barcode_type');
                }
                else
                {
                    $object->fetch_barcode();
                    print $object->barcode_type_label?$object->barcode_type_label:($object->barcode?'<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>':'');
                }
                print '</td></tr>'."\n";

                // Barcode value
                print '<tr><td class="nowrap">';
                print '<table width="100%" class="nobordernopadding"><tr><td class="nowrap">';
                print $langs->trans("BarcodeValue");
                print '<td>';
                if (($action != 'editbarcode') && ! empty($user->rights->barcode->creer)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbarcode&amp;id='.$object->id.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="2">';
                if ($action == 'editbarcode')
                {
                    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="setbarcode">';
                    print '<input type="hidden" name="barcode_type_code" value="'.$object->barcode_type_code.'">';
                    print '<input size="40" type="text" name="barcode" value="'.$object->barcode.'">';
                    print '&nbsp;<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
                }
                else
                {
                    print $object->barcode;
                }
                print '</td></tr>'."\n";
            }

            // Accountancy sell code
            print '<tr><td>'.$form->editfieldkey("ProductAccountancySellCode",'accountancy_code_sell',$object->accountancy_code_sell,$object,$user->rights->produit->creer||$user->rights->service->creer,'string').'</td><td colspan="2">';
            print $form->editfieldval("ProductAccountancySellCode",'accountancy_code_sell',$object->accountancy_code_sell,$object,$user->rights->produit->creer||$user->rights->service->creer,'string');
            print '</td></tr>';

            // Accountancy buy code
            print '<tr><td>'.$form->editfieldkey("ProductAccountancyBuyCode",'accountancy_code_buy',$object->accountancy_code_buy,$object,$user->rights->produit->creer||$user->rights->service->creer,'string').'</td><td colspan="2">';
            print $form->editfieldval("ProductAccountancyBuyCode",'accountancy_code_buy',$object->accountancy_code_buy,$object,$user->rights->produit->creer||$user->rights->service->creer,'string');
            print '</td></tr>';

            // Status (to sell)
            print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
            if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                print ajax_object_onoff($object, 'status', 'tosell', 'ProductStatusOnSell', 'ProductStatusNotOnSell');
            } else {
                print $object->getLibStatut(2,0);
            }
            print '</td></tr>';

            // Status (to buy)
            print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
            if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                print ajax_object_onoff($object, 'status_buy', 'tobuy', 'ProductStatusOnBuy', 'ProductStatusNotOnBuy');
            } else {
                print $object->getLibStatut(2,1);
            }
            print '</td></tr>';

            // Batch number management (to batch)
            if (! empty($conf->productbatch->enabled)) {
                print '<tr><td>'.$langs->trans("ManageLotSerial").'</td><td colspan="2">';
                if (! empty($conf->use_javascript_ajax) && $user->rights->produit->creer && ! empty($conf->global->MAIN_DIRECT_STATUS_UPDATE)) {
                    print ajax_object_onoff($object, 'status_batch', 'tobatch', 'ProductStatusOnBatch', 'ProductStatusNotOnBatch');
                } else {
                    print $object->getLibStatut(0,2);
                }
                print '</td></tr>';
            }

            // Description
            print '<tr><td class="tdtop">'.$langs->trans("Description").'</td><td colspan="2">'.(dol_textishtml($object->description)?$object->description:dol_nl2br($object->description,1,true)).'</td></tr>';

            // Public URL
            print '<tr><td>'.$langs->trans("PublicUrl").'</td><td colspan="2">';
			print dol_print_url($object->url);
            print '</td></tr>';

            // Nature
            if($object->type!= Product::TYPE_SERVICE)
            {
                print '<tr><td>'.$langs->trans("Nature").'</td><td colspan="2">';
                print $object->getLibFinished();
                print '</td></tr>';
            }

            if ($object->isservice())
            {
                // Duration
                print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="2">'.$object->duration_value.'&nbsp;';
                if ($object->duration_value > 1)
                {
                    $dur=array("h"=>$langs->trans("Hours"),"d"=>$langs->trans("Days"),"w"=>$langs->trans("Weeks"),"m"=>$langs->trans("Months"),"y"=>$langs->trans("Years"));
                }
                else if ($object->duration_value > 0)
                {
                    $dur=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
                }
                print (! empty($object->duration_unit) && isset($dur[$object->duration_unit]) ? $langs->trans($dur[$object->duration_unit]) : '')."&nbsp;";

                print '</td></tr>';
            }
            else
            {
                // Weight
                print '<tr><td>'.$langs->trans("Weight").'</td><td colspan="2">';
                if ($object->weight != '')
                {
                    print $object->weight." ".measuring_units_string($object->weight_units,"weight");
                }
                else
                {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
                // Length
                print '<tr><td>'.$langs->trans("Length").'</td><td colspan="2">';
                if ($object->length != '')
                {
                    print $object->length." ".measuring_units_string($object->length_units,"size");
                }
                else
                {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
                // Surface
                print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="2">';
                if ($object->surface != '')
                {
                    print $object->surface." ".measuring_units_string($object->surface_units,"surface");
                }
                else
                {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
                // Volume
                print '<tr><td>'.$langs->trans("Volume").'</td><td colspan="2">';
                if ($object->volume != '')
                {
                    print $object->volume." ".measuring_units_string($object->volume_units,"volume");
                }
                else
                {
                    print '&nbsp;';
                }
                print "</td></tr>\n";
            }

			// Unit
			if (! empty($conf->global->PRODUCT_USE_UNITS))
			{
				$unit = $object->getLabelOfUnit();

				print '<tr><td>'.$langs->trans('DefaultUnitToShow').'</td><td>';
				if ($unit !== '') {
					print $langs->trans($unit);
				}
				print '</td></tr>';
			}

        	// Custom code
        	if (empty($conf->global->PRODUCT_DISABLE_CUSTOM_INFO))
        	{
	            print '<tr><td>'.$langs->trans("CustomCode").'</td><td colspan="2">'.$object->customcode.'</td>';

            	// Origin country code
            	print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td colspan="2">'.getCountry($object->country_id,0,$db).'</td>';
        	}

            // Other attributes
            $parameters=array('colspan' => ' colspan="'.(2+(($showphoto||$showbarcode)?1:0)).'"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
            	print $object->showOptionals($extrafields);
            }

            // Note
            print '<!-- show Note --> '."\n";
            print '<tr><td class="tdtop">'.$langs->trans("Note").'</td><td colspan="'.(2+(($showphoto||$showbarcode)?1:0)).'">'.(dol_textishtml($object->note)?$object->note:dol_nl2br($object->note,1,true)).'</td></tr>'."\n";
            print '<!-- End show Note --> '."\n";

			// Categories
			if($conf->categorie->enabled) {
				print '<tr><td valign="middle">'.$langs->trans("Categories").'</td><td colspan="3">';
				print $form->showCategories($object->id,'product',1);
				print "</td></tr>";
			}

            print "</table>\n";

            dol_fiche_end();
        }

    }
    else if ($action != 'create')
    {
        header("Location: index.php");
        exit;
    }
}


// Define confirmation messages
$formquestionclone=array(
	'text' => $langs->trans("ConfirmClone"),
    array('type' => 'text', 'name' => 'clone_ref','label' => $langs->trans("NewRefForClone"), 'value' => $langs->trans("CopyOf").' '.$object->ref, 'size'=>24),
    array('type' => 'checkbox', 'name' => 'clone_content','label' => $langs->trans("CloneContentProduct"), 'value' => 1),
    array('type' => 'checkbox', 'name' => 'clone_prices', 'label' => $langs->trans("ClonePricesProduct").' ('.$langs->trans("FeatureNotYetAvailable").')', 'value' => 0, 'disabled' => true),
    array('type' => 'checkbox', 'name' => 'clone_composition', 'label' => $langs->trans('CloneCompositionProduct'), 'value' => 1)
);

// Confirm delete product
if (($action == 'delete' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))	// Output when action = clone if jmobile or no js
	|| (! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
    print $form->formconfirm("card.php?id=".$object->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete",'',0,"action-delete");
}

// Clone confirmation
if (($action == 'clone' && (empty($conf->use_javascript_ajax) || ! empty($conf->dol_use_jmobile)))		// Output when action = clone if jmobile or no js
	|| (! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile)))							// Always output when not jmobile nor js
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneProduct'),$langs->trans('ConfirmCloneProduct',$object->ref),'confirm_clone',$formquestionclone,'yes','action-clone',250,600);
}



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n".'<div class="tabsAction">'."\n";

$parameters=array();
$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
if (empty($reshook))
{
	if ($action == '' || $action == 'view')
	{
	    if ($user->rights->produit->creer || $user->rights->service->creer)
	    {
	        if (! isset($object->no_button_edit) || $object->no_button_edit <> 1) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a></div>';

	        if (! isset($object->no_button_copy) || $object->no_button_copy <> 1)
	        {
	            if (! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
	            {
	                print '<div class="inline-block divButAction"><span id="action-clone" class="butAction">'.$langs->trans('ToClone').'</span></div>'."\n";
	            }
	            else
				{
	                print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=clone&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a></div>';
	            }
	        }
	    }
	    $object_is_used = $object->isObjectUsed($object->id);

	    if (($object->type == Product::TYPE_PRODUCT && $user->rights->produit->supprimer)
	    || ($object->type == Product::TYPE_SERVICE && $user->rights->service->supprimer))
	    {
	        if (empty($object_is_used) && (! isset($object->no_button_delete) || $object->no_button_delete <> 1))
	        {
	            if (! empty($conf->use_javascript_ajax) && empty($conf->dol_use_jmobile))
	            {
	                print '<div class="inline-block divButAction"><span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span></div>'."\n";
	            }
	            else
				{
	                print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a></div>';
	            }
	        }
	        else
			{
	            print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("ProductIsUsed").'">'.$langs->trans("Delete").'</a></div>';
	        }
	    }
	    else
		{
	        print '<div class="inline-block divButAction"><a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Delete").'</a></div>';
	    }
	}
}

print "\n</div>\n";


/*
 * All the "Add to" areas
 */

if ($object->id && ($action == '' || $action == 'view') && $object->status)
{
    //Variable used to check if any text is going to be printed
    $html = '';
	//print '<div class="fichecenter"><div class="fichehalfleft">';

    // Propals
    if (! empty($conf->propal->enabled) && $user->rights->propale->creer)
    {
        $propal = new Propal($db);

        $langs->load("propal");

        $var=true;
        $otherprop = $propal->liste_array(2,1,0);

        if (is_array($otherprop) && count($otherprop))
        {
        	$var=!$var;
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftProposals").'</td><td colspan="2">';
        	$html .= $form->selectarray("propalid", $otherprop, 0, 1);
        	$html .= '</td></tr>';
        }
        else
       {
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftProposals").'</td><td colspan="2">';
        	$html .= $langs->trans("NoDraftProposals");
        	$html .= '</td></tr>';
        }
    }

    // Commande
    if (! empty($conf->commande->enabled) && $user->rights->commande->creer)
    {
        $commande = new Commande($db);

        $langs->load("orders");

        $var=true;
        $othercom = $commande->liste_array(2, 1, null);
        if (is_array($othercom) && count($othercom))
        {
        	$var=!$var;
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftOrders").'</td><td colspan="2">';
        	$html .= $form->selectarray("commandeid", $othercom, 0, 1);
        	$html .= '</td></tr>';
        }
        else
		{
        	$html .= '<tr><td style="width: 200px;">';
        	$html .= $langs->trans("AddToDraftOrders").'</td><td colspan="2">';
        	$html .= $langs->trans("NoDraftOrders");
        	$html .= '</td></tr>';
        }
    }

    // Factures
    if (! empty($conf->facture->enabled) && $user->rights->facture->creer)
    {
    	$invoice = new Facture($db);

    	$langs->load("bills");

    	$var=true;
    	$otherinvoice = $invoice->liste_array(2, 1, null);
    	if (is_array($otherinvoice) && count($otherinvoice))
    	{
    		$var=!$var;
    		$html .= '<tr><td style="width: 200px;">';
    		$html .= $langs->trans("AddToDraftInvoices").'</td><td colspan="2">';
    		$html .= $form->selectarray("factureid", $otherinvoice, 0, 1);
    		$html .= '</td></tr>';
    	}
    	else
    	{
    		$html .= '<tr><td style="width: 200px;">';
    		$html .= $langs->trans("AddToDraftInvoices").'</td><td colspan="2">';
    		$html .= $langs->trans("NoDraftInvoices");
    		$html .= '</td></tr>';
    	}
    }

    //If any text is going to be printed, then we show the table
    if (!empty($html))
    {
	    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	print '<input type="hidden" name="action" value="addin">';

	    print load_fiche_titre($langs->trans("Add"),'','');

    	$html .= '<tr><td class="nowrap">'.$langs->trans("Quantity").' ';
    	$html .= '<input type="text" class="flat" name="qty" size="1" value="1"></td><td class="nowrap">'.$langs->trans("ReductionShort").'(%) ';
    	$html .= '<input type="text" class="flat" name="remise_percent" size="1" value="0">';
    	$html .= '</td></tr>';

    	print '<table width="100%" class="border">';
        print $html;
        print '</table>';

        print '<div class="center">';
        print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
        print '</div>';

        print '</form>';
    }
}


llxFooter();
$db->close();
