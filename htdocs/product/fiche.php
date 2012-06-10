<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Auguria SARL         <info@auguria.org>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *  \file       htdocs/product/fiche.php
 *  \ingroup    product
 *  \brief      Page to show product
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/canvas.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/extrafields.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/product.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
if ($conf->propal->enabled)   require_once(DOL_DOCUMENT_ROOT."/comm/propal/class/propal.class.php");
if ($conf->facture->enabled)  require_once(DOL_DOCUMENT_ROOT."/compta/facture/class/facture.class.php");
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT."/commande/class/commande.class.php");

$langs->load("products");
$langs->load("other");
if ($conf->stock->enabled) $langs->load("stocks");
if ($conf->facture->enabled) $langs->load("bills");

$mesg=''; $error=0; $errors=array();

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action=(GETPOST('action','alpha') ? GETPOST('action','alpha') : 'view');
$confirm=GETPOST('confirm','alpha');
$socid=GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;

$object = new Product($db);
$extrafields = new ExtraFields($db);

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$object->getCanvas($id,$ref);
$canvas = $object->canvas?$object->canvas:GETPOST("canvas");
if (! empty($canvas))
{
    require_once(DOL_DOCUMENT_ROOT."/core/class/canvas.class.php");
    $objcanvas = new Canvas($db,$action);
    $objcanvas->getCanvas('product','card',$canvas);
}

// Security check
$fieldvalue = (! empty($id) ? $id : (! empty($ref) ? $ref : ''));
$fieldtype = (! empty($ref) ? 'ref' : 'rowid');
$result=restrictedArea($user,'produit|service',$fieldvalue,'product&product','','',$fieldtype,$objcanvas);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->initHooks(array('productcard'));



/*
 * Actions
 */

$parameters=array('id'=>$id, 'ref'=>$ref, 'objcanvas'=>$objcanvas);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
$error=$hookmanager->error; $errors=$hookmanager->errors;

if (empty($reshook))
{
    // Type
    if ($action ==	'setfk_product_type' && $user->rights->produit->creer)
    {
        $object->fetch($id);
    	$result = $object->setValueFrom('fk_product_type', $_POST['fk_product_type']);
    	Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    // Barcode type
    if ($action ==	'setfk_barcode_type' && $user->rights->barcode->creer)
    {
    	$object->fetch($id);
    	$result = $object->setValueFrom('fk_barcode_type', $_POST['fk_barcode_type']);
    	Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    // Barcode value
    if ($action ==	'setbarcode' && $user->rights->barcode->creer)
    {
    	$object->fetch($id);
    	//Todo: ajout verification de la validite du code barre en fonction du type
    	$result = $object->setValueFrom('barcode', $_POST['barcode']);
    	Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
    	exit;
    }

    if ($action == 'setaccountancy_code_buy')
    {
        $object->fetch($id,$ref);
        $result = $object->setValueFrom('accountancy_code_buy', $_POST['accountancy_code_buy']);
        if ($result < 0)
        {
            $mesg=join(',',$object->errors);
        }
        $action="";
    }

    if ($action == 'setaccountancy_code_buy')
    {
        $object->fetch($id,$ref);
        $result = $object->setValueFrom('accountancy_code_sell', $_POST['accountancy_code_sell']);
        if ($result < 0)
        {
            $mesg=join(',',$object->errors);
        }
        $action="";
    }

    // Add a product or service
    if ($action == 'add' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
        $error=0;

        if (empty($_POST["libelle"]))
        {
            $mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Label')).'</div>';
            $action = "create";
            $_GET["canvas"] = $_POST["canvas"];
            $_GET["type"] 	= $_POST["type"];
            $error++;
        }
        if (empty($ref))
        {
            $mesg='<div class="error">'.$langs->trans('ErrorFieldRequired',$langs->transnoentities('Ref')).'</div>';
            $action = "create";
            $_GET["canvas"] = $_POST["canvas"];
            $_GET["type"] 	= $_POST["type"];
            $error++;
        }

        if (! $error)
        {
            $object->ref                = $ref;
            $object->libelle            = $_POST["libelle"];
            $object->price_base_type    = $_POST["price_base_type"];
            if ($object->price_base_type == 'TTC') $object->price_ttc = $_POST["price"];
            else $object->price = $_POST["price"];
            if ($object->price_base_type == 'TTC') $object->price_min_ttc = $_POST["price_min"];
            else $object->price_min = $_POST["price_min"];
            $object->tva_tx             = str_replace('*','',$_POST['tva_tx']);
            $object->tva_npr            = preg_match('/\*/',$_POST['tva_tx'])?1:0;

            // local taxes.
            $object->localtax1_tx 			= get_localtax($object->tva_tx,1);
            $object->localtax2_tx 			= get_localtax($object->tva_tx,2);

            $object->type               	= $_POST["type"];
            $object->status             	= $_POST["statut"];
            $object->status_buy           	= $_POST["statut_buy"];
            $object->description        	= dol_htmlcleanlastbr($_POST["desc"]);
            $object->note               	= dol_htmlcleanlastbr($_POST["note"]);
            $object->customcode            = $_POST["customcode"];
            $object->country_id            = $_POST["country_id"];
            $object->duration_value     	= $_POST["duration_value"];
            $object->duration_unit      	= $_POST["duration_unit"];
            $object->seuil_stock_alerte 	= $_POST["seuil_stock_alerte"]?$_POST["seuil_stock_alerte"]:0;
            $object->canvas             	= $_POST["canvas"];
            $object->weight             	= $_POST["weight"];
            $object->weight_units       	= $_POST["weight_units"];
            $object->length             	= $_POST["size"];
            $object->length_units       	= $_POST["size_units"];
            $object->surface            	= $_POST["surface"];
            $object->surface_units      	= $_POST["surface_units"];
            $object->volume             	= $_POST["volume"];
            $object->volume_units       	= $_POST["volume_units"];
            $object->finished           	= $_POST["finished"];
            $object->hidden             	= $_POST["hidden"]=='yes'?1:0;

            // MultiPrix
            if($conf->global->PRODUIT_MULTIPRICES)
            {
                for($i=2;$i<=$conf->global->PRODUIT_MULTIPRICES_LIMIT;$i++)
                {
                    if($_POST["price_".$i])
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

            // Get extra fields
            foreach($_POST as $key => $value)
            {
                if (preg_match("/^options_/",$key))
                {
                    $object->array_options[$key]=$_POST[$key];
                }
            }

            $id = $object->create($user);

            if ($id > 0)
            {
                Header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
                exit;
            }
            else
            {
                $mesg='<div class="error">'.$langs->trans($object->error).'</div>';
                $action = "create";
                $_GET["type"] = $_POST["type"];
            }
        }
    }

    // Update a product or service
    if ($action == 'update' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
        if (! empty($_POST["cancel"]))
        {
            $action = '';
        }
        else
        {
            if ($object->fetch($id,$ref))
            {
            	$object->oldcopy=dol_clone($object);

                $object->ref                = $ref;
                $object->libelle            = $_POST["libelle"];
                $object->description        = dol_htmlcleanlastbr($_POST["desc"]);
                $object->note               = dol_htmlcleanlastbr($_POST["note"]);
                $object->customcode         = $_POST["customcode"];
                $object->country_id         = $_POST["country_id"];
                $object->status             = $_POST["statut"];
                $object->status_buy         = $_POST["statut_buy"];
                $object->seuil_stock_alerte = $_POST["seuil_stock_alerte"];
                $object->duration_value     = $_POST["duration_value"];
                $object->duration_unit      = $_POST["duration_unit"];
                $object->canvas             = $_POST["canvas"];
                $object->weight             = $_POST["weight"];
                $object->weight_units       = $_POST["weight_units"];
                $object->length             = $_POST["size"];
                $object->length_units       = $_POST["size_units"];
                $object->surface            = $_POST["surface"];
                $object->surface_units      = $_POST["surface_units"];
                $object->volume             = $_POST["volume"];
                $object->volume_units       = $_POST["volume_units"];
                $object->finished           = $_POST["finished"];
                $object->hidden             = $_POST["hidden"]=='yes'?1:0;

                // Get extra fields
                foreach($_POST as $key => $value)
                {
                    if (preg_match("/^options_/",$key))
                    {
                        $object->array_options[$key]=$_POST[$key];
                    }
                }

                if ($object->check())
                {
                    if ($object->update($object->id, $user) > 0)
                    {
                        $action = 'view';
                    }
                    else
                    {
                        $action = 'edit';
                        $mesg = $object->error;
                    }
                }
                else
                {
                    $action = 'edit';
                    $mesg = $langs->trans("ErrorProductBadRefOrLabel");
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
            $mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
        }
        else
        {
            $db->begin();

            $originalId = $id;
            if ($object->fetch($id,$ref) > 0)
            {
                $object->ref = GETPOST('clone_ref');
                $object->status = 0;
                $object->status_buy = 0;
                $object->finished = 1;
                $object->id = null;

                if ($object->check())
                {
                    $id = $object->create($user);
                    if ($id > 0)
                    {
                        // $object->clone_fournisseurs($originalId, $id);

                        $db->commit();
                        $db->close();

                        Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
                        exit;
                    }
                    else
                    {
                        $id=$originalId;

                        if ($object->error == 'ErrorProductAlreadyExists')
                        {
                            $db->rollback();

                            $_error = 1;
                            $action = "";

                            $mesg='<div class="error">'.$langs->trans("ErrorProductAlreadyExists",$object->ref);
                            $mesg.=' <a href="'.$_SERVER["PHP_SELF"].'?ref='.$object->ref.'">'.$langs->trans("ShowCardHere").'</a>.';
                            $mesg.='</div>';
                            //dol_print_error($object->db);
                        }
                        else
                        {
                            $db->rollback();
                            $mesg=$object->error;
                            dol_print_error($db,$object->error);
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
        $object = new Product($db);
        $object->fetch($id,$ref);

        if ( ($object->type == 0 && $user->rights->produit->supprimer)	|| ($object->type == 1 && $user->rights->service->supprimer) )
        {
            $result = $object->delete($object->id);
        }

        if ($result > 0)
        {
            Header('Location: '.DOL_URL_ROOT.'/product/liste.php?delprod='.urlencode($object->ref));
            exit;
        }
        else
        {
            $mesg=$object->error;
            $reload = 0;
            $action='';
        }
    }


    // Add product into proposal
    if ($action == 'addinpropal')
    {
        $propal = new Propal($db);
        $result=$propal->fetch($_POST["propalid"]);
        if ($result <= 0)
        {
            dol_print_error($db,$propal->error);
            exit;
        }

        $soc = new Societe($db);
        $result=$soc->fetch($propal->socid);
        if ($result <= 0)
        {
            dol_print_error($db,$soc->error);
            exit;
        }

        $prod = new Product($db);
        $result=$prod->fetch($id,$ref);
        if ($result <= 0)
        {
            dol_print_error($db,$prod->error);
            exit;
        }

        $desc = $prod->description;

        $tva_tx = get_default_tva($mysoc, $soc, $prod->id);
        $localtax1_tx= get_localtax($tva_tx, 1, $soc);
        $localtax2_tx= get_localtax($tva_tx, 2, $soc);

        $pu_ht = $prod->price;
        $pu_ttc = $prod->price_ttc;
        $price_base_type = $prod->price_base_type;

        // If multiprice
        if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
        {
            $pu_ht = $prod->multiprices[$soc->price_level];
            $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
            $price_base_type = $prod->multiprices_base_type[$soc->price_level];
        }

        // On reevalue prix selon taux tva car taux tva transaction peut etre different
        // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
        if ($tva_tx != $prod->tva_tx)
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

        $result = $propal->addline(
            $propal->id,
            $desc,
            $pu_ht,
            $_POST["qty"],
            $tva_tx,
            $localtax1_tx, // localtax1
            $localtax2_tx, // localtax2
            $prod->id,
            $_POST["remise_percent"],
            $price_base_type,
            $pu_ttc
        );
        if ($result > 0)
        {
            Header("Location: ".DOL_URL_ROOT."/comm/propal.php?id=".$propal->id);
            return;
        }

        $mesg = $langs->trans("ErrorUnknown").": $result";
    }

    // Add product into order
    if ($action == 'addincommande')
    {
        $commande = new Commande($db);
        $result=$commande->fetch($_POST["commandeid"]);
        if ($result <= 0)
        {
            dol_print_error($db,$commande->error);
            exit;
        }

        $soc = new Societe($db);
        $result=$soc->fetch($commande->socid);
        if ($result <= 0)
        {
            dol_print_error($db,$soc->error);
            exit;
        }

        $prod = new Product($db);
        $result=$prod->fetch($id,$ref);
        if ($result <= 0)
        {
            dol_print_error($db,$prod->error);
            exit;
        }

        $desc = $prod->description;

        $tva_tx = get_default_tva($mysoc, $soc, $prod->id);
        $localtax1_tx= get_localtax($tva_tx, 1, $soc);
        $localtax2_tx= get_localtax($tva_tx, 2, $soc);


        $pu_ht = $prod->price;
        $pu_ttc = $prod->price_ttc;
        $price_base_type = $prod->price_base_type;

        // If multiprice
        if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
        {
            $pu_ht = $prod->multiprices[$soc->price_level];
            $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
            $price_base_type = $prod->multiprices_base_type[$soc->price_level];
        }

        // On reevalue prix selon taux tva car taux tva transaction peut etre different
        // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
        if ($tva_tx != $prod->tva_tx)
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

        $result =  $commande->addline(
            $commande->id,
            $desc,
            $pu_ht,
            $_POST["qty"],
            $tva_tx,
            $localtax1_tx, // localtax1
            $localtax2_tx, // localtax2
            $prod->id,
            $_POST["remise_percent"],
            '',
            '', // TODO voir si fk_remise_except est encore valable car n'apparait plus dans les propales
            $price_base_type,
            $pu_ttc
        );

        if ($result > 0)
        {
            Header("Location: ".DOL_URL_ROOT."/commande/fiche.php?id=".$commande->id);
            exit;
        }
    }

    // Add product into invoice
    if ($action == 'addinfacture' && $user->rights->facture->creer)
    {
        $facture = New Facture($db);
        $result=$facture->fetch($_POST["factureid"]);
        if ($result <= 0)
        {
            dol_print_error($db,$facture->error);
            exit;
        }

        $soc = new Societe($db);
        $soc->fetch($facture->socid);
        if ($result <= 0)
        {
            dol_print_error($db,$soc->error);
            exit;
        }

        $prod = new Product($db);
        $result = $prod->fetch($id,$ref);
        if ($result <= 0)
        {
            dol_print_error($db,$prod->error);
            exit;
        }

        $desc = $prod->description;

        $tva_tx = get_default_tva($mysoc, $soc, $prod->id);
        $localtax1_tx= get_localtax($tva_tx, 1, $soc);
        $localtax2_tx= get_localtax($tva_tx, 2, $soc);

        $pu_ht = $prod->price;
        $pu_ttc = $prod->price_ttc;
        $price_base_type = $prod->price_base_type;

        // If multiprice
        if ($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
        {
            $pu_ht = $prod->multiprices[$soc->price_level];
            $pu_ttc = $prod->multiprices_ttc[$soc->price_level];
            $price_base_type = $prod->multiprices_base_type[$soc->price_level];
        }

        // On reevalue prix selon taux tva car taux tva transaction peut etre different
        // de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
        if ($tva_tx != $prod->tva_tx)
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

        $result = $facture->addline(
            $facture->id,
            $desc,
            $pu_ht,
            $_POST["qty"],
            $tva_tx,
            $localtax1_tx,
            $localtax2_tx,
            $prod->id,
            $_POST["remise_percent"],
            '',
            '',
            '',
            '',
            '',
            $price_base_type,
            $pu_ttc
        );

        if ($result > 0)
        {
            Header("Location: ".DOL_URL_ROOT."/compta/facture.php?facid=".$facture->id);
            exit;
        }
    }
}

if (GETPOST("cancel") == $langs->trans("Cancel"))
{
    $action = '';
    Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    exit;
}


/*
 * View
 */

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label('product');

$helpurl='';
if (GETPOST("type") == '0') $helpurl='EN:Module_Products|FR:Module_Produits|ES:M&oacute;dulo_Productos';
if (GETPOST("type") == '1')	$helpurl='EN:Module_Services_En|FR:Module_Services|ES:M&oacute;dulo_Servicios';

llxHeader('',$langs->trans("CardProduct".$_GET["type"]),$helpurl);

$form = new Form($db);
$formproduct = new FormProduct($db);


if (is_object($objcanvas) && $objcanvas->displayCanvasExists($action))
{
    // -----------------------------------------
    // When used with CANVAS
    // -----------------------------------------
    if (empty($object->error) && ($id || $ref))
    {
        $object = new Product($db);
        $object->fetch($id, $ref);
    }
    $objcanvas->assign_values($action, $object->id, $ref);	// Set value for templates
    $objcanvas->display_canvas($action);				// Show template
}
else
{
    // -----------------------------------------
    // When used in standard mode
    // -----------------------------------------
    if ($action == 'create' && ($user->rights->produit->creer || $user->rights->service->creer))
    {
        print '<form action="fiche.php" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="add">';
        print '<input type="hidden" name="type" value="'.$_GET["type"].'">'."\n";

        if ($_GET["type"]==1) $title=$langs->trans("NewService");
        else $title=$langs->trans("NewProduct");
        print_fiche_titre($title);

        dol_htmloutput_mesg($mesg);

        print '<table class="border" width="100%">';
        print '<tr>';
        print '<td class="fieldrequired" width="20%">'.$langs->trans("Ref").'</td><td><input name="ref" size="40" maxlength="32" value="'.$ref.'">';
        if ($_error == 1)
        {
            print $langs->trans("RefAlreadyExists");
        }
        print '</td></tr>';

        // Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td><input name="libelle" size="40" value="'.$_POST["libelle"].'"></td></tr>';

        // On sell
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td>';
        $statutarray=array('1' => $langs->trans("OnSell"), '0' => $langs->trans("NotOnSell"));
        print $form->selectarray('statut',$statutarray,$_POST["statut"]);
        print '</td></tr>';

        // To buy
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td>';
        $statutarray=array('1' => $langs->trans("ProductStatusOnBuy"), '0' => $langs->trans("ProductStatusNotOnBuy"));
        print $form->selectarray('statut_buy',$statutarray,$_POST["statut_buy"]);
        print '</td></tr>';

        // Stock min level
        if ($_GET["type"] != 1 && $conf->stock->enabled)
        {
            print '<tr><td>'.$langs->trans("StockLimit").'</td><td>';
            print '<input name="seuil_stock_alerte" size="4" value="'.$_POST["seuil_stock_alerte"].'">';
            print '</td></tr>';
        }
        else
        {
            print '<input name="seuil_stock_alerte" type="hidden" value="0">';
        }

        // Description (used in invoice, propal...)
        print '<tr><td valign="top">'.$langs->trans("Description").'</td><td>';

        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('desc',$_POST["desc"],'',160,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,4,90);
        $doleditor->Create();

        print "</td></tr>";

        // Nature
        if ($_GET["type"] != 1)
        {
            print '<tr><td>'.$langs->trans("Nature").'</td><td>';
            $statutarray=array('1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
            print $form->selectarray('finished',$statutarray,$_POST["finished"]);
            print '</td></tr>';
        }

        // Duration
        if ($_GET["type"] == 1)
        {
            print '<tr><td>'.$langs->trans("Duration").'</td><td><input name="duration_value" size="6" maxlength="5" value="'.$object->duree.'"> &nbsp;';
            print '<input name="duration_unit" type="radio" value="h">'.$langs->trans("Hour").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="d">'.$langs->trans("Day").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="w">'.$langs->trans("Week").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="m">'.$langs->trans("Month").'&nbsp;';
            print '<input name="duration_unit" type="radio" value="y">'.$langs->trans("Year").'&nbsp;';
            print '</td></tr>';
        }

        if ($_GET["type"] != 1)	// Le poids et le volume ne concerne que les produits et pas les services
        {
            // Weight
            print '<tr><td>'.$langs->trans("Weight").'</td><td>';
            print '<input name="weight" size="4" value="'.$_POST["weight"].'">';
            print $formproduct->select_measuring_units("weight_units","weight");
            print '</td></tr>';
            // Length
            print '<tr><td>'.$langs->trans("Length").'</td><td>';
            print '<input name="size" size="4" value="'.$_POST["size"].'">';
            print $formproduct->select_measuring_units("size_units","size");
            print '</td></tr>';
            // Surface
            print '<tr><td>'.$langs->trans("Surface").'</td><td>';
            print '<input name="surface" size="4" value="'.$_POST["surface"].'">';
            print $formproduct->select_measuring_units("surface_units","surface");
            print '</td></tr>';
            // Volume
            print '<tr><td>'.$langs->trans("Volume").'</td><td>';
            print '<input name="volume" size="4" value="'.$_POST["volume"].'">';
            print $formproduct->select_measuring_units("volume_units","volume");
            print '</td></tr>';
        }

        // Customs code
        print '<tr><td>'.$langs->trans("CustomCode").'</td><td><input name="customcode" size="10" value="'.$_POST["customcode"].'"></td></tr>';

        // Origin country
        print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td>';
        print $form->select_country($_POST["country_id"],'country_id');
        if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
        print '</td></tr>';

        // Other attributes
        $parameters=array('colspan' => ' colspan="2"');
        $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
        if (empty($reshook) && ! empty($extrafields->attribute_label))
        {
            foreach($extrafields->attribute_label as $key=>$label)
            {
                $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
                print '<tr><td>'.$label.'</td><td colspan="3">';
                print $extrafields->showInputField($key,$value);
                print '</td></tr>'."\n";
            }
        }

        // Note (private, no output on invoices, propales...)
        print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td>';
        require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
        $doleditor=new DolEditor('note',$_POST["note"],'',180,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,8,70);
        $doleditor->Create();

        print "</td></tr>";
        print '</table>';

        print '<br>';

        if ($conf->global->PRODUIT_MULTIPRICES)
        {
            // We do no show price array on create when multiprices enabled.
            // We must set them on prices tab.
        }
        else
        {
            print '<table class="border" width="100%">';

            // PRIX
            print '<tr><td>'.$langs->trans("SellingPrice").'</td>';
            print '<td><input name="price" size="10" value="'.$object->price.'">';
            print $form->select_PriceBaseType($object->price_base_type, "price_base_type");
            print '</td></tr>';

            // MIN PRICE
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

        print '<center><input type="submit" class="button" value="'.$langs->trans("Create").'"></center>';

        print '</form>';
    }

    /*
     * Product card
     */

    else if ($id || $ref)
    {
        $res=$object->fetch($id,$ref);
        if ($res < 0) { dol_print_error($db,$object->error); exit; }
        $res=$object->fetch_optionals($object->id,$extralabels);

        // Fiche en mode edition
        if ($action == 'edit' && ($user->rights->produit->creer || $user->rights->service->creer))
        {
            $type = $langs->trans('Product');
            if ($object->isservice()) $type = $langs->trans('Service');
            print_fiche_titre($langs->trans('Modify').' '.$type.' : '.$object->ref, "");

            dol_htmloutput_errors($mesg);

            // Main official, simple, and not duplicated code
            print '<form action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';
            print '<input type="hidden" name="id" value="'.$object->id.'">';
            print '<input type="hidden" name="canvas" value="'.$object->canvas.'">';
            print '<table class="border allwidth">';

            // Ref
            print '<tr><td width="15%" class="fieldrequired">'.$langs->trans("Ref").'</td><td colspan="2"><input name="ref" size="40" maxlength="32" value="'.$object->ref.'"></td></tr>';

            // Label
            print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td><td colspan="2"><input name="libelle" size="40" value="'.$object->libelle.'"></td></tr>';

            // Status
            print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
            print '<select class="flat" name="statut">';
            if ($object->status)
            {
                print '<option value="1" selected="selected">'.$langs->trans("OnSell").'</option>';
                print '<option value="0">'.$langs->trans("NotOnSell").'</option>';
            }
            else
            {
                print '<option value="1">'.$langs->trans("OnSell").'</option>';
                print '<option value="0" selected="selected">'.$langs->trans("NotOnSell").'</option>';
            }
            print '</select>';
            print '</td></tr>';

            // To Buy
            print '<tr><td class="fieldrequired">'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
            print '<select class="flat" name="statut_buy">';
            if ($object->status_buy)
            {
                print '<option value="1" selected="selected">'.$langs->trans("ProductStatusOnBuy").'</option>';
                print '<option value="0">'.$langs->trans("ProductStatusNotOnBuy").'</option>';
            }
            else
            {
                print '<option value="1">'.$langs->trans("ProductStatusOnBuy").'</option>';
                print '<option value="0" selected="selected">'.$langs->trans("ProductStatusNotOnBuy").'</option>';
            }
            print '</select>';
            print '</td></tr>';

            // Description (used in invoice, propal...)
            print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">';
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('desc',$object->description,'',160,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,4,90);
            $doleditor->Create();
            print "</td></tr>";
            print "\n";

            // Nature
            if($object->type!=1)
            {
                print '<tr><td>'.$langs->trans("Nature").'</td><td colspan="2">';
                $statutarray=array('-1'=>'&nbsp;', '1' => $langs->trans("Finished"), '0' => $langs->trans("RowMaterial"));
                print $form->selectarray('finished',$statutarray,$object->finished);
                print '</td></tr>';
            }

            if ($object->isproduct() && $conf->stock->enabled)
            {
                print "<tr>".'<td>'.$langs->trans("StockLimit").'</td><td colspan="2">';
                print '<input name="seuil_stock_alerte" size="4" value="'.$object->seuil_stock_alerte.'">';
                print '</td></tr>';
            }
            else
            {
                print '<input name="seuil_stock_alerte" type="hidden" value="'.$object->seuil_stock_alerte.'">';
            }

            if ($object->isservice())
            {
                // Duration
                print '<tr><td>'.$langs->trans("Duration").'</td><td colspan="2"><input name="duration_value" size="3" maxlength="5" value="'.$object->duration_value.'">';
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
                print '<tr><td>'.$langs->trans("Weight").'</td><td colspan="2">';
                print '<input name="weight" size="5" value="'.$object->weight.'"> ';
                print $formproduct->select_measuring_units("weight_units", "weight", $object->weight_units);
                print '</td></tr>';
                // Length
                print '<tr><td>'.$langs->trans("Length").'</td><td colspan="2">';
                print '<input name="size" size="5" value="'.$object->length.'"> ';
                print $formproduct->select_measuring_units("size_units", "size", $object->length_units);
                print '</td></tr>';
                // Surface
                print '<tr><td>'.$langs->trans("Surface").'</td><td colspan="2">';
                print '<input name="surface" size="5" value="'.$object->surface.'"> ';
                print $formproduct->select_measuring_units("surface_units", "surface", $object->surface_units);
                print '</td></tr>';
                // Volume
                print '<tr><td>'.$langs->trans("Volume").'</td><td colspan="2">';
                print '<input name="volume" size="5" value="'.$object->volume.'"> ';
                print $formproduct->select_measuring_units("volume_units", "volume", $object->volume_units);
                print '</td></tr>';
            }

            // Customs code
            print '<tr><td>'.$langs->trans("CustomCode").'</td><td colspan="2"><input name="customcode" size="10" value="'.$object->customcode.'"></td></tr>';

            // Origin country
            print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td colspan="2">';
            print $form->select_country($object->country_id,'country_id');
            if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionnarySetup"),1);
            print '</td></tr>';

            // Other attributes
            $parameters=array('colspan' => ' colspan="2"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
                foreach($extrafields->attribute_label as $key=>$label)
                {
                    $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
                    print '<tr><td>'.$label.'</td><td colspan="3">';
                    print $extrafields->showInputField($key,$value);
                    print '</td></tr>'."\n";
                }
            }

            // Note
            print '<tr><td valign="top">'.$langs->trans("NoteNotVisibleOnBill").'</td><td colspan="2">';
            require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
            $doleditor=new DolEditor('note',$object->note,'',200,'dolibarr_details','',false,true,$conf->global->FCKEDITOR_ENABLE_PRODUCTDESC,8,70);
            $doleditor->Create();
            print "</td></tr>";
            print '</table>';

            print '<br>';

            print '<center><input type="submit" class="button" value="'.$langs->trans("Save").'"> &nbsp; &nbsp; ';
            print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></center>';

            print '</form>';
        }
        // Fiche en mode visu
        else
        {
            dol_htmloutput_mesg($mesg);

            $head=product_prepare_head($object, $user);
            $titre=$langs->trans("CardProduct".$object->type);
            $picto=($object->type==1?'service':'product');
            dol_fiche_head($head, 'card', $titre, 0, $picto);

            $showphoto=$object->is_photo_available($conf->product->multidir_output[$object->entity]);
            $showbarcode=$conf->barcode->enabled && $user->rights->barcode->lire;

            // En mode visu
            print '<table class="border" width="100%"><tr>';

            // Ref
            print '<td width="15%">'.$langs->trans("Ref").'</td><td colspan="'.(2+(($showphoto||$showbarcode)?1:0)).'">';
            print $form->showrefnav($object,'ref','',1,'ref');
            print '</td>';

            print '</tr>';

            // Label
            print '<tr><td>'.$langs->trans("Label").'</td><td colspan="2">'.$object->libelle.'</td>';

            $nblignes=8;
            if ($object->type!=1) $nblignes++;
            if ($object->isservice()) $nblignes++;
            else $nblignes+=4;
            if ($showbarcode) $nblignes+=2;

            // Photo
            if ($showphoto || $showbarcode)
            {
                print '<td valign="middle" align="center" width="25%" rowspan="'.$nblignes.'">';
                if ($showphoto)   print $object->show_photos($conf->product->multidir_output[$object->entity],1,1,0,0,0,80);
                if ($showphoto && $showbarcode) print '<br><br>';
                if ($showbarcode) print $form->showbarcode($object);
                print '</td>';
            }

            print '</tr>';

            // Type
            if ($conf->produit->enabled && $conf->service->enabled)
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
                print '<tr><td nowrap>';
                print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
                print $langs->trans("BarcodeType");
                print '<td>';
                if (($action != 'editbarcodetype') && $user->rights->barcode->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbarcodetype&amp;id='.$object->id.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="2">';
                if ($action == 'editbarcodetype')
                {
                    require_once(DOL_DOCUMENT_ROOT."/core/class/html.formbarcode.class.php");
                    $formbarcode = new FormBarCode($db);
                    $formbarcode->form_barcode_type($_SERVER['PHP_SELF'].'?id='.$object->id,$object->barcode_type,'barcodetype_id');
                }
                else
                {
                    $object->fetch_barcode();
                    print $object->barcode_type_label?$object->barcode_type_label:($object->barcode?'<div class="warning">'.$langs->trans("SetDefaultBarcodeType").'<div>':'');
                }
                print '</td></tr>'."\n";

                // Barcode value
                print '<tr><td nowrap>';
                print '<table width="100%" class="nobordernopadding"><tr><td nowrap>';
                print $langs->trans("BarcodeValue");
                print '<td>';
                if (($action != 'editbarcode') && $user->rights->barcode->creer) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editbarcode&amp;id='.$object->id.'">'.img_edit($langs->trans('Edit'),1).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="2">';
                if ($action == 'editbarcode')
                {
                    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="setbarcode">';
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
            print '<tr><td>'.$form->editfieldkey("ProductAccountancySellCode",'accountancy_code_sell',$object->accountancy_code_sell,$object,$user->rights->produit->creer||$user->rights->service->creer).'</td><td colspan="2">';
            print $form->editfieldval("ProductAccountancySellCode",'accountancy_code_sell',$object->accountancy_code_sell,$object,$user->rights->produit->creer||$user->rights->service->creer);
            print '</td></tr>';

            // Accountancy buy code
            print '<tr><td>'.$form->editfieldkey("ProductAccountancyBuyCode",'accountancy_code_buy',$object->accountancy_code_buy,$object,$user->rights->produit->creer||$user->rights->service->creer).'</td><td colspan="2">';
            print $form->editfieldval("ProductAccountancyBuyCode",'accountancy_code_buy',$object->accountancy_code_buy,$object,$user->rights->produit->creer||$user->rights->service->creer);
            print '</td></tr>';

            // Status (to sell)
            print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Sell").')</td><td colspan="2">';
            print $object->getLibStatut(2,0);
            print '</td></tr>';

            // Status (to buy)
            print '<tr><td>'.$langs->trans("Status").' ('.$langs->trans("Buy").')</td><td colspan="2">';
            print $object->getLibStatut(2,1);
            print '</td></tr>';

            // Description
            print '<tr><td valign="top">'.$langs->trans("Description").'</td><td colspan="2">'.(dol_textishtml($object->description)?$object->description:dol_nl2br($object->description,1,true)).'</td></tr>';

            // Nature
            if($object->type!=1)
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
                print $langs->trans($dur[$object->duration_unit])."&nbsp;";

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

            // Customs code
            print '<tr><td>'.$langs->trans("CustomCode").'</td><td colspan="2">'.$object->customcode.'</td>';

            // Origin country code
            print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td colspan="2">'.getCountry($object->country_id,0,$db).'</td>';

            // Other attributes
            $parameters=array('colspan' => ' colspan="'.(2+(($showphoto||$showbarcode)?1:0)).'"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
                foreach($extrafields->attribute_label as $key=>$label)
                {
                    $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
                    print '<tr><td>'.$label.'</td><td colspan="3">';
                    print $extrafields->showOutputField($key,$value);
                    print '</td></tr>'."\n";
                }
            }

            // Note
            print '<tr><td valign="top">'.$langs->trans("Note").'</td><td colspan="'.(2+(($showphoto||$showbarcode)?1:0)).'">'.(dol_textishtml($object->note)?$object->note:dol_nl2br($object->note,1,true)).'</td></tr>';

            print "</table>\n";

            dol_fiche_end();
        }

    }
    else if ($action != 'create')
    {
        Header("Location: index.php");
        exit;
    }
}


// Define confirmation messages
$formquestionclone=array(
	'text' => $langs->trans("ConfirmClone"),
    array('type' => 'text', 'name' => 'clone_ref','label' => $langs->trans("NewRefForClone"), 'value' => $langs->trans("CopyOf").' '.$object->ref, 'size'=>24),
    array('type' => 'checkbox', 'name' => 'clone_content','label' => $langs->trans("CloneContentProduct"), 'value' => 1),
    array('type' => 'checkbox', 'name' => 'clone_prices', 'label' => $langs->trans("ClonePricesProduct").' ('.$langs->trans("FeatureNotYetAvailable").')', 'value' => 0, 'disabled' => true)
);

// Confirm delete product
if ($action == 'delete' && empty($conf->use_javascript_ajax))
{
    print $form->formconfirm("fiche.php?id=".$object->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete",'',0,"action-delete");
}

// Clone confirmation
if ($action == 'clone' && empty($conf->use_javascript_ajax))
{
    print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneProduct'),$langs->trans('ConfirmCloneProduct',$object->ref),'confirm_clone',$formquestionclone,'yes','action-clone',230,600);
}



/* ************************************************************************** */
/*                                                                            */
/* Barre d'action                                                             */
/*                                                                            */
/* ************************************************************************** */

print "\n".'<div class="tabsAction">'."\n";

if ($action == '' || $action == 'view')
{
    if ($user->rights->produit->creer || $user->rights->service->creer)
    {
        if ($object->no_button_edit <> 1) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&amp;id='.$object->id.'">'.$langs->trans("Modify").'</a>';

        if ($object->no_button_copy <> 1)
        {
            if ($conf->use_javascript_ajax)
            {
                print '<span id="action-clone" class="butAction">'.$langs->trans('ToClone').'</span>'."\n";
                print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneProduct'),$langs->trans('ConfirmCloneProduct',$object->ref),'confirm_clone',$formquestionclone,'yes','action-clone',230,600);
            }
            else
            {
                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=clone&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a>';
            }
        }
    }

    $object_is_used = $object->isObjectUsed($object->id);
    if (($object->type == 0 && $user->rights->produit->supprimer)
    || ($object->type == 1 && $user->rights->service->supprimer))
    {
        if (! $object_is_used && $object->no_button_delete <> 1)
        {
            if ($conf->use_javascript_ajax)
            {
                print '<span id="action-delete" class="butActionDelete">'.$langs->trans('Delete').'</span>'."\n";
                print $form->formconfirm("fiche.php?id=".$object->id,$langs->trans("DeleteProduct"),$langs->trans("ConfirmDeleteProduct"),"confirm_delete",'',0,"action-delete");
            }
            else
            {
                print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("Delete").'</a>';
            }
        }
        else
        {
            print '<a class="butActionRefused" href="#" title="'.$langs->trans("ProductIsUsed").'">'.$langs->trans("Delete").'</a>';
        }
    }
    else
    {
        print '<a class="butActionRefused" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans("Delete").'</a>';
    }
}

print "\n</div><br>\n";


/*
 * All the "Add to" areas
 */

if ($id && ($action == '' || $action == 'view') && $object->status)
{
    print '<table width="100%" class="noborder">';

    // Propals
    if($conf->propal->enabled && $user->rights->propale->creer)
    {
        $propal = new Propal($db);

        $langs->load("propal");

        print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
        print $langs->trans("AddToMyProposals") . '</td>';

        if ($user->rights->societe->client->voir)
        {
            print '<td width="50%" class="liste_titre">';
            print $langs->trans("AddToOtherProposals").'</td>';
        }
        else
        {
            print '<td width="50%" class="liste_titre">&nbsp;</td>';
        }

        print '</tr>';

        // Liste de "Mes propals"
        print '<tr><td'.($user->rights->societe->client->voir?' width="50%"':'').' valign="top">';

        $sql = "SELECT s.nom, s.rowid as socid, p.rowid as propalid, p.ref, p.datep as dp";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."propal as p";
        $sql.= " WHERE p.fk_soc = s.rowid";
        $sql.= " AND p.entity = ".$conf->entity;
        $sql.= " AND p.fk_statut = 0";
        $sql.= " AND p.fk_user_author = ".$user->id;
        $sql.= " ORDER BY p.datec DESC, p.tms DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $var=true;
            $num = $db->num_rows($result);
            print '<table class="nobordernopadding" width="100%">';
            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="addinpropal">';
                    print "<tr ".$bc[$var].">";
                    print '<td nowrap="nowrap">';
                    print "<a href=\"../comm/propal.php?id=".$objp->propalid."\">".img_object($langs->trans("ShowPropal"),"propal")." ".$objp->ref."</a></td>\n";
                    print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,18)."</a></td>\n";
                    print "<td nowrap=\"nowrap\">".dol_print_date($objp->dp,"%d %b")."</td>\n";
                    print '<td><input type="hidden" name="propalid" value="'.$objp->propalid.'">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print " ".$object->stock_proposition;
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
            }
            else {
                print "<tr ".$bc[!$var]."><td>";
                print $langs->trans("NoOpenedPropals");
                print "</td></tr>";
            }
            print "</table>";
            $db->free($result);
        }

        print '</td>';

        if ($user->rights->societe->client->voir)
        {
            // Liste de "Other propals"
            print '<td width="50%" valign="top">';

            $var=true;
            $otherprop = $propal->liste_array(1,1,1);
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" width="100%">';
            if (is_array($otherprop) && count($otherprop))
            {
                $var=!$var;
                print '<tr '.$bc[$var].'><td colspan="3">';
                print '<input type="hidden" name="action" value="addinpropal">';
                print $langs->trans("OtherPropals").'</td><td>';
                print $form->selectarray("propalid", $otherprop);
                print '</td></tr>';
                print '<tr '.$bc[$var].'><td nowrap="nowrap" colspan="2">'.$langs->trans("Qty");
                print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
                print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                print '</td><td align="right">';
                print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                print '</td></tr>';
            }
            else
            {
                print "<tr ".$bc[!$var]."><td>";
                print $langs->trans("NoOtherOpenedPropals");
                print '</td></tr>';
            }
            print '</table>';
            print '</form>';

            print '</td>';
        }

        print '</tr>';
    }

    // Commande
    if($conf->commande->enabled && $user->rights->commande->creer)
    {
        $commande = new Commande($db);

        $langs->load("orders");

        print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
        print $langs->trans("AddToMyOrders").'</td>';

        if ($user->rights->societe->client->voir)
        {
            print '<td width="50%" class="liste_titre">';
            print $langs->trans("AddToOtherOrders").'</td>';
        }
        else
        {
            print '<td width="50%" class="liste_titre">&nbsp;</td>';
        }

        print '</tr>';

        // Liste de "Mes commandes"
        print '<tr><td'.($user->rights->societe->client->voir?' width="50%"':'').' valign="top">';

        $sql = "SELECT s.nom, s.rowid as socid, c.rowid as commandeid, c.ref, c.date_commande as dc";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."commande as c";
        $sql.= " WHERE c.fk_soc = s.rowid";
        $sql.= " AND c.entity = ".$conf->entity;
        $sql.= " AND c.fk_statut = 0";
        $sql.= " AND c.fk_user_author = ".$user->id;
        $sql.= " ORDER BY c.date_creation DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $var=true;
            print '<table class="nobordernopadding" width="100%">';
            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $objc = $db->fetch_object($result);
                    $var=!$var;
                    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="addincommande">';
                    print "<tr ".$bc[$var].">";
                    print '<td nowrap="nowrap">';
                    print "<a href=\"../commande/fiche.php?id=".$objc->commandeid."\">".img_object($langs->trans("ShowOrder"),"order")." ".$objc->ref."</a></td>\n";
                    print "<td><a href=\"../comm/fiche.php?socid=".$objc->socid."\">".dol_trunc($objc->nom,18)."</a></td>\n";
                    print "<td nowrap=\"nowrap\">".dol_print_date($db->jdate($objc->dc),"%d %b")."</td>\n";
                    print '<td><input type="hidden" name="commandeid" value="'.$objc->commandeid.'">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print " ".$object->stock_proposition;
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
            }
            else
            {
                print "<tr ".$bc[!$var]."><td>";
                print $langs->trans("NoOpenedOrders");
                print '</td></tr>';
            }
            print "</table>";
            $db->free($result);
        }

        print '</td>';

        if ($user->rights->societe->client->voir)
        {
            // Liste de "Other orders"
            print '<td width="50%" valign="top">';

            $var=true;
            $othercom = $commande->liste_array(1, $user);
            print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<table class="nobordernopadding" width="100%">';
            if (is_array($othercom) && count($othercom))
            {
                $var=!$var;
                print '<tr '.$bc[$var].'><td colspan="3">';
                print '<input type="hidden" name="action" value="addincommande">';
                print $langs->trans("OtherOrders").'</td><td>';
                print $form->selectarray("commandeid", $othercom);
                print '</td></tr>';
                print '<tr '.$bc[$var].'><td colspan="2">'.$langs->trans("Qty");
                print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
                print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                print '</td><td align="right">';
                print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                print '</td></tr>';
            }
            else
            {
                print "<tr ".$bc[!$var]."><td>";
                print $langs->trans("NoOtherOpenedOrders");
                print '</td></tr>';
            }
            print '</table>';
            print '</form>';

            print '</td>';
        }

        print '</tr>';
    }

    // Factures
    if ($conf->facture->enabled && $user->rights->facture->creer)
    {
        print '<tr class="liste_titre"><td width="50%" class="liste_titre">';
        print $langs->trans("AddToMyBills").'</td>';

        if ($user->rights->societe->client->voir)
        {
            print '<td width="50%" class="liste_titre">';
            print $langs->trans("AddToOtherBills").'</td>';
        }
        else
        {
            print '<td width="50%" class="liste_titre">&nbsp;</td>';
        }

        print '</tr>';

        // Liste de Mes factures
        print '<tr><td'.($user->rights->societe->client->voir?' width="50%"':'').' valign="top">';

        $sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber, f.datef as df";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
        $sql.= " WHERE f.fk_soc = s.rowid";
        $sql.= " AND f.entity = ".$conf->entity;
        $sql.= " AND f.fk_statut = 0";
        $sql.= " AND f.fk_user_author = ".$user->id;
        $sql.= " ORDER BY f.datec DESC, f.rowid DESC";

        $result=$db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $var=true;
            print '<table class="nobordernopadding" width="100%">';
            if ($num)
            {
                $i = 0;
                while ($i < $num)
                {
                    $objp = $db->fetch_object($result);
                    $var=!$var;
                    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="action" value="addinfacture">';
                    print "<tr $bc[$var]>";
                    print "<td nowrap>";
                    print "<a href=\"../compta/facture.php?facid=".$objp->factureid."\">".img_object($langs->trans("ShowBills"),"bill")." ".$objp->facnumber."</a></td>\n";
                    print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,18)."</a></td>\n";
                    print "<td nowrap=\"nowrap\">".dol_print_date($db->jdate($objp->df),"%d %b")."</td>\n";
                    print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
                    print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
                    print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                    print '</td><td align="right">';
                    print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                    print '</td>';
                    print '</tr>';
                    print '</form>';
                    $i++;
                }
            }
            else {
                print "<tr ".$bc[!$var]."><td>";
                print $langs->trans("NoDraftBills");
                print '</td></tr>';
            }
            print "</table>";
            $db->free($result);
        }
        else
        {
            dol_print_error($db);
        }

        print '</td>';

        if ($user->rights->societe->client->voir)
        {
            $facture = new Facture($db);

            print '<td width="50%" valign="top">';

            // Liste de Autres factures
            $var=true;

            $sql = "SELECT s.nom, s.rowid as socid, f.rowid as factureid, f.facnumber, f.datef as df";
            $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."facture as f";
            $sql.= " WHERE f.fk_soc = s.rowid";
            $sql.= " AND f.entity = ".$conf->entity;
            $sql.= " AND f.fk_statut = 0";
            $sql.= " AND f.fk_user_author <> ".$user->id;
            $sql.= " ORDER BY f.datec DESC, f.rowid DESC";

            $result=$db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $var=true;
                print '<table class="nobordernopadding" width="100%">';
                if ($num)
                {
                    $i = 0;
                    while ($i < $num)
                    {
                        $objp = $db->fetch_object($result);

                        $var=!$var;
                        print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'">';
                        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                        print '<input type="hidden" name="action" value="addinfacture">';
                        print "<tr ".$bc[$var].">";
                        print "<td><a href=\"../compta/facture.php?facid=".$objp->factureid."\">$objp->facnumber</a></td>\n";
                        print "<td><a href=\"../comm/fiche.php?socid=".$objp->socid."\">".dol_trunc($objp->nom,24)."</a></td>\n";
                        print "<td colspan=\"2\">".$langs->trans("Qty");
                        print "</td>";
                        print '<td><input type="hidden" name="factureid" value="'.$objp->factureid.'">';
                        print '<input type="text" class="flat" name="qty" size="1" value="1"></td><td nowrap>'.$langs->trans("ReductionShort");
                        print '<input type="text" class="flat" name="remise_percent" size="1" value="0">%';
                        print '</td><td align="right">';
                        print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                        print '</td>';
                        print '</tr>';
                        print '</form>';
                        $i++;
                    }
                }
                else
                {
                    print "<tr ".$bc[!$var]."><td>";
                    print $langs->trans("NoOtherDraftBills");
                    print '</td></tr>';
                }
                print "</table>";
                $db->free($result);
            }
            else
            {
                dol_print_error($db);
            }

            print '</td>';
        }

        print '</tr>';
    }

    print '</table>';

    print '<br>';
}


$db->close();
llxFooter();
?>
