<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
**
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
 *	\file       htdocs/commande/fiche.php
 *	\ingroup    commande
 *	\brief      Page to show customer order
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formorder.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php');
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');

if (!$user->rights->commande->lire) accessforbidden();

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');

$id      = (GETPOST("id")?GETPOST("id"):GETPOST("orderid"));
$ref     = GETPOST('ref');
$socid   = GETPOST('socid');
$action  = GETPOST('action');
$confirm = GETPOST('confirm');
$lineid  = GETPOST('lineid');
$mesg    = GETPOST('mesg');

$object = new Commande($db);

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$id,'');

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->callHooks(array('ordercard'));


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
    if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
    {
        $mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
    }
    else
    {
    	if ($object->fetch($id) > 0)
    	{
    		$result=$object->createFromClone($socid, $hookmanager);
    		if ($result > 0)
    		{
    			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
    			exit;
    		}
    		else
    		{
    			$mesg='<div class="error">'.$object->error.'</div>';
    			$action='';
    		}
    	}
    }
}

// Reopen a closed order
if ($action == 'reopen' && $user->rights->commande->creer)
{
    $object->fetch($id);
    if ($object->statut == 3)
    {
        $result = $object->set_reopen($user);
        if ($result > 0)
        {
            Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Suppression de la commande
if ($action == 'confirm_delete' && $confirm == 'yes')
{
    if ($user->rights->commande->supprimer)
    {
        $object->fetch($id);
        $result=$object->delete($user);
        if ($result > 0)
        {
            Header('Location: index.php');
            exit;
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
}

// Remove a product line
if ($action == 'confirm_deleteline' && $confirm == 'yes')
{
    if ($user->rights->commande->creer)
    {
        $object->fetch($id);
        $object->fetch_thirdparty();

        $result = $object->deleteline($lineid);
        if ($result > 0)
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
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
            {
                $ret=$object->fetch($id);    // Reload to get new records
                commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
            }
        }
        else
        {
            $mesg='<div class="error">'.$object->error.'</div>';
        }
    }
    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
    exit;
}

// Categorisation dans projet
if ($action == 'classin')
{
    $object->fetch($id);
    $object->setProject($_POST['projectid']);
}

// Add order
if ($action == 'add' && $user->rights->commande->creer)
{
    $datecommande  = dol_mktime(12, 0, 0, $_POST['remonth'],  $_POST['reday'],  $_POST['reyear']);
    $datelivraison = dol_mktime(12, 0, 0, $_POST['liv_month'],$_POST['liv_day'],$_POST['liv_year']);

    $object->socid=GETPOST('socid');
    $object->fetch_thirdparty();

    $db->begin();

    $object->date_commande        = $datecommande;
    $object->note                 = $_POST['note'];
    $object->note_public          = $_POST['note_public'];
    $object->source               = $_POST['source_id'];
    $object->fk_project           = $_POST['projectid'];
    $object->ref_client           = $_POST['ref_client'];
    $object->modelpdf             = $_POST['model'];
    $object->cond_reglement_id    = $_POST['cond_reglement_id'];
    $object->mode_reglement_id    = $_POST['mode_reglement_id'];
    $object->availability_id      = $_POST['availability_id'];
    $object->demand_reason_id     = $_POST['demand_reason_id'];
    $object->date_livraison       = $datelivraison;
    $object->fk_delivery_address  = $_POST['fk_address'];
    $object->contactid            = $_POST['contactidp'];

    // If creation from another object of another module (Example: origin=propal, originid=1)
    if ($_POST['origin'] && $_POST['originid'])
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = $_POST['origin'];
        if (preg_match('/^([^_]+)_([^_]+)/i',$_POST['origin'],$regs))
        {
            $element = $regs[1];
            $subelement = $regs[2];
        }

        // For compatibility
        if ($element == 'order')    { $element = $subelement = 'commande'; }
        if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
        if ($element == 'contract') { $element = $subelement = 'contrat'; }

        $object->origin    = $_POST['origin'];
        $object->origin_id = $_POST['originid'];

        $object_id = $object->create($user);

        if ($object_id > 0)
        {
            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $srcobject = new $classname($db);

            dol_syslog("Try to find source object origin=".$object->origin." originid=".$object->origin_id." to add lines");
            $result=$srcobject->fetch($object->origin_id);
            if ($result > 0)
            {
                $lines = $srcobject->lines;
                if (empty($lines) && method_exists($srcobject,'fetch_lines'))  $lines = $srcobject->fetch_lines();

                $fk_parent_line=0;
                $num=count($lines);

                for ($i=0;$i<$num;$i++)
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

                    // Reset fk_parent_line for no child products and special product
                    if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9) {
                        $fk_parent_line = 0;
                    }

                    $result = $object->addline(
                        $object_id,
                        $desc,
                        $lines[$i]->subprice,
                        $lines[$i]->qty,
                        $lines[$i]->tva_tx,
                        $lines[$i]->localtax1_tx,
                        $lines[$i]->localtax2_tx,
                        $lines[$i]->fk_product,
                        $lines[$i]->remise_percent,
                        $lines[$i]->info_bits,
                        $lines[$i]->fk_remise_except,
                        'HT',
                        0,
                        $datestart,
                        $dateend,
                        $product_type,
                        $lines[$i]->rang,
                        $lines[$i]->special_code,
                        $fk_parent_line
                    );

                    if ($result < 0)
                    {
                        $error++;
                        break;
                    }

                    // Defined the new fk_parent_line
                    if ($result > 0 && $lines[$i]->product_type == 9) {
                        $fk_parent_line = $result;
                    }
                }

                // Hooks
                $parameters=array('objFrom'=>$srcobject);
                $reshook=$hookmanager->executeHooks('createFrom',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
                if ($reshook < 0) $error++;
            }
            else
            {
                $mesg=$srcobject->error;
                $error++;
            }
        }
        else
        {
            $mesg=$object->error;
            $error++;
        }
    }
    else
    {
        $object_id = $object->create($user);

        // If some invoice's lines already known
        $NBLINES=8;
        for ($i = 1 ; $i <= $NBLINES ; $i++)
        {
            if ($_POST['idprod'.$i])
            {
                $xid = 'idprod'.$i;
                $xqty = 'qty'.$i;
                $xremise = 'remise_percent'.$i;
                $object->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
            }
        }
    }

    // Insert default contacts if defined
    if ($object_id > 0)
    {
        if ($_POST["contactidp"])
        {
            $result=$object->add_contact($_POST["contactidp"],'CUSTOMER','external');

            if ($result < 0)
            {
                $mesg = '<div class="error">'.$langs->trans("ErrorFailedToAddContact").'</div>';
                $error++;
            }
        }

        $id = $object_id;
        $action = '';
    }

    // End of object creation, we show it
    if ($object_id > 0 && ! $error)
    {
        $db->commit();
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object_id);
        exit;
    }
    else
    {
        $db->rollback();
        $action='create';
        $socid=$_POST['socid'];
        if (! $mesg) $mesg='<div class="error">'.$object->error.'</div>';
    }

}

if ($action == 'classifybilled')
{
    $object->fetch($id);
    $object->classer_facturee();
}

// Positionne ref commande client
if ($action == 'set_ref_client' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $object->set_ref_client($user, $_POST['ref_client']);
}

if ($action == 'setremise' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $object->set_remise($user, $_POST['remise']);
}

if ($action == 'setabsolutediscount' && $user->rights->commande->creer)
{
    if ($_POST["remise_id"])
    {
        $ret=$object->fetch($id);
        if ($ret > 0)
        {
            $object->insert_discount($_POST["remise_id"]);
        }
        else
        {
            dol_print_error($db,$object->error);
        }
    }
}

if ($action == 'setdate' && $user->rights->commande->creer)
{
    //print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
    $date=dol_mktime(0, 0, 0, $_POST['order_month'], $_POST['order_day'], $_POST['order_year']);

    $object->fetch($id);
    $result=$object->set_date($user,$date);
    if ($result < 0)
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

if ($action == 'setdate_livraison' && $user->rights->commande->creer)
{
    //print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
    $datelivraison=dol_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

    $object->fetch($id);
    $result=$object->set_date_livraison($user,$datelivraison);
    if ($result < 0)
    {
        $mesg='<div class="error">'.$object->error.'</div>';
    }
}

if ($action == 'setaddress' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $object->set_adresse_livraison($user,$_POST['fk_address']);
}

if ($action == 'setmode' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $result=$object->mode_reglement($_POST['mode_reglement_id']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setavailability' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $result=$object->availability($_POST['availability_id']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setdemandreason' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $result=$object->demand_reason($_POST['demand_reason_id']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setconditions' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $result=$object->cond_reglement($_POST['cond_reglement_id']);
    if ($result < 0) dol_print_error($db,$object->error);
}

if ($action == 'setremisepercent' && $user->rights->facture->creer)
{
    $object->fetch($id);
    $result = $object->set_remise($user, $_POST['remise_percent']);
}

if ($action == 'setremiseabsolue' && $user->rights->facture->creer)
{
    $object->fetch($id);
    $result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
}

/*
 *  Ajout d'une ligne produit dans la commande
 */
if ($action == 'addline' && $user->rights->commande->creer)
{
    $result=0;

    if (empty($_POST['idprod']) && $_POST["type"] < 0)
    {
        $mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
        $result = -1 ;
    }
    if (empty($_POST['idprod']) && (! isset($_POST["np_price"]) || $_POST["np_price"]==''))	// Unit price can be 0 but not ''
    {
        $mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")).'</div>';
        $result = -1 ;
    }

    if ($result >= 0 && $_POST['qty'] && (($_POST['np_price'] != '' && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprod']))
    {
        $ret=$object->fetch($id);
        if ($ret < 0)
        {
            dol_print_error($db,$object->error);
            exit;
        }
        $ret=$object->fetch_thirdparty();

        // Clean parameters
        $suffixe = $_POST['idprod'] ? '_predef' : '';
        $date_start=dol_mktime(0, 0, 0, $_POST['date_start'.$suffixe.'month'], $_POST['date_start'.$suffixe.'day'], $_POST['date_start'.$suffixe.'year']);
        $date_end=dol_mktime(0, 0, 0, $_POST['date_end'.$suffixe.'month'], $_POST['date_end'.$suffixe.'day'], $_POST['date_end'.$suffixe.'year']);
        $price_base_type = 'HT';

        // Ecrase $pu par celui du produit
        // Ecrase $desc par celui du produit
        // Ecrase $txtva par celui du produit
        // Ecrase $base_price_type par celui du produit
        if ($_POST['idprod'])
        {
            $prod = new Product($db);
            $prod->fetch($_POST['idprod']);

            $tva_tx = get_default_tva($mysoc,$object->client,$prod->id);

            // multiprix
            if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)
            {
                $pu_ht = $prod->multiprices[$object->client->price_level];
                $pu_ttc = $prod->multiprices_ttc[$object->client->price_level];
                $price_min = $prod->multiprices_min[$object->client->price_level];
                $price_base_type = $prod->multiprices_base_type[$object->client->price_level];
            }
            else
            {
                $pu_ht = $prod->price;
                $pu_ttc = $prod->price_ttc;
                $price_min = $prod->price_min;
                $price_base_type = $prod->price_base_type;
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

            // Define output language
			if (! empty($conf->global->MAIN_MULTILANGS) && ! empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE))
			{
				$outputlangs = $langs;
				$newlang='';
				if (empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
				if (empty($newlang)) $newlang=$object->client->default_lang;
				if (! empty($newlang))
				{
					$outputlangs = new Translate("",$conf);
					$outputlangs->setDefaultLang($newlang);
				}

				$desc = (! empty($prod->multilangs[$outputlangs->defaultlang]["description"])) ? $prod->multilangs[$outputlangs->defaultlang]["description"] : $prod->description;
			}
			else
			{
				$desc = $prod->description;
			}

            $desc.= ($desc && $_POST['np_desc']) ? ((dol_textishtml($desc) || dol_textishtml($_POST['np_desc']))?"<br />\n":"\n") : "";
            $desc.= $_POST['np_desc'];
            $type = $prod->type;
        }
        else
        {
            $pu_ht=$_POST['np_price'];
            $tva_tx=str_replace('*','',$_POST['np_tva_tx']);
            $tva_npr=preg_match('/\*/',$_POST['np_tva_tx'])?1:0;
            $desc=$_POST['dp_desc'];
            $type=$_POST["type"];
        }

        // Local Taxes
        $localtax1_tx= get_localtax($tva_tx, 1, $object->client);
        $localtax2_tx= get_localtax($tva_tx, 2, $object->client);

        $desc=dol_htmlcleanlastbr($desc);

        $info_bits=0;
        if ($tva_npr) $info_bits |= 0x01;

        if ($result >= 0)
        {
            if($price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
            {
                //print "CantBeLessThanMinPrice ".$up_ht." - ".GETPOST('remise_percent')." - ".$product->price_min;
                $mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency)).'</div>' ;
            }
            else
            {
                // Insert line
                $result = $object->addline(
                    $id,
                    $desc,
                    $pu_ht,
                    $_POST['qty'],
                    $tva_tx,
                    $localtax1_tx,
                    $localtax2_tx,
                    $_POST['idprod'],
                    $_POST['remise_percent'],
                    $info_bits,
                    0,
                    $price_base_type,
                    $pu_ttc,
                    $date_start,
                    $date_end,
                    $type,
                    -1,
                    '',
                    $_POST['fk_parent_line']
                );

                if ($result > 0)
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
                    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
                    {
                        $ret=$object->fetch($id);    // Reload to get new records
                        commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
                    }

                    unset($_POST['qty']);
                    unset($_POST['type']);
                    unset($_POST['idprod']);
                    unset($_POST['remise_percent']);
                    unset($_POST['dp_desc']);
                    unset($_POST['np_desc']);
                    unset($_POST['np_price']);
                    unset($_POST['np_tva_tx']);
                }
                else
                {
                    $mesg='<div class="error">'.$object->error.'</div>';
                }
            }
        }
    }
}

/*
 *  Mise a jour d'une ligne dans la commande
 */
if ($action == 'updateligne' && $user->rights->commande->creer && $_POST['save'] == $langs->trans('Save'))
{
    if (! $object->fetch($id) > 0) dol_print_error($db);
    $object->fetch_thirdparty();

    // Clean parameters
    $date_start='';
    $date_end='';
    $date_start=dol_mktime(0, 0, 0, $_POST['date_start'.$suffixe.'month'], $_POST['date_start'.$suffixe.'day'], $_POST['date_start'.$suffixe.'year']);
    $date_end=dol_mktime(0, 0, 0, $_POST['date_end'.$suffixe.'month'], $_POST['date_end'.$suffixe.'day'], $_POST['date_end'.$suffixe.'year']);
    $description=dol_htmlcleanlastbr($_POST['desc']);
    $up_ht=GETPOST('pu')?GETPOST('pu'):GETPOST('subprice');

    // Define info_bits
    $info_bits=0;
    if (preg_match('/\*/',$_POST['tva_tx'])) $info_bits |= 0x01;

    // Define vat_rate
    $vat_rate=$_POST['tva_tx'];
    $vat_rate=str_replace('*','',$vat_rate);
    $localtax1_rate=get_localtax($vat_rate,1,$object->client);
    $localtax2_rate=get_localtax($vat_rate,2,$object->client);

    // Check parameters
    if (empty($_POST['productid']) && $_POST["type"] < 0)
    {
        $mesg = '<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
        $result = -1 ;
    }
    // Check minimum price
    if(! empty($_POST['productid']))
    {
        $productid = $_POST['productid'];
        $product = new Product($db);
        $product->fetch($productid);
        $type=$product->type;
        $price_min = $product->price_min;
        if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)	$price_min = $product->multiprices_min[$object->client->price_level];
    }
    if ($price_min && GETPOST('productid') && (price2num($up_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
    {
        $mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency)).'</div>' ;
        $result=-1;
    }

    // Define params
    if (! empty($_POST['productid']))
    {
        $type=$product->type;
    }
    else
    {
        $type=$_POST["type"];
    }

    if ($result >= 0)
    {
        $result = $object->updateline(
            $_POST['lineid'],
            $description,
            $up_ht,
            $_POST['qty'],
            $_POST['remise_percent'],
            $vat_rate,
            $localtax1_rate,
            $localtax2_rate,
    		'HT',
            $info_bits,
            $date_start,
            $date_end,
            $type,
            $_POST['fk_parent_line']
        );

        if ($result >= 0)
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
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
            {
                $ret=$object->fetch($id);    // Reload to get new records
                commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
            }
        }
        else
        {
            dol_print_error($db,$object->error);
            exit;
        }
    }
}

if ($action == 'updateligne' && $user->rights->commande->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
    Header('Location: fiche.php?id='.$id);   // Pour reaffichage de la fiche en cours d'edition
    exit;
}

if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->commande->valider)
{
    $idwarehouse=GETPOST('idwarehouse');

    $object->fetch($id);	// Load order and lines
    $object->fetch_thirdparty();

    // Check parameters
    if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $object->hasProductsOrServices(1))
    {
        if (! $idwarehouse || $idwarehouse == -1)
        {
            $error++;
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
            $action='';
        }
    }

    if (! $error)
    {
        $result=$object->valid($user,$idwarehouse);
        if ($result	>= 0)
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
            if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
        }
    }
}

// Go back to draft status
if ($action == 'confirm_modif' && $user->rights->commande->creer)
{
    $idwarehouse=GETPOST('idwarehouse');

    $object->fetch($id);		// Load order and lines
    $object->fetch_thirdparty();

    // Check parameters
    if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $object->hasProductsOrServices(1))
    {
        if (! $idwarehouse || $idwarehouse == -1)
        {
            $error++;
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
            $action='';
        }
    }

	if (! $error)
	{
	    $result = $object->set_draft($user,$idwarehouse);
	    if ($result	>= 0)
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
	        if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	        {
                $ret=$object->fetch($id);    // Reload to get new records
	            commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
	        }
	    }
	}
}

if ($action == 'confirm_close' && $confirm == 'yes' && $user->rights->commande->cloturer)
{
    $object->fetch($id);		// Load order and lines

    $result = $object->cloture($user);
    if ($result < 0) $mesgs=$object->errors;
}

if ($action == 'confirm_cancel' && $confirm == 'yes' && $user->rights->commande->valider)
{
    $idwarehouse=GETPOST('idwarehouse');

    $object->fetch($id);		// Load order and lines
    $object->fetch_thirdparty();

    // Check parameters
    if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $object->hasProductsOrServices(1))
    {
        if (! $idwarehouse || $idwarehouse == -1)
        {
            $error++;
            $errors[]=$langs->trans('ErrorFieldRequired',$langs->transnoentitiesnoconv("Warehouse"));
            $action='';
        }
    }

	if (! $error)
	{
	    $result = $object->cancel($user,$idwarehouse);
	}
}


/*
 * Ordonnancement des lignes
 */

if ($action == 'up' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->line_up($_GET['rowid']);

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

    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.$_GET['rowid']);
    exit;
}

if ($action == 'down' && $user->rights->commande->creer)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $object->line_down($_GET['rowid']);

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
    if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

    Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.$_GET['rowid']);
    exit;
}

if ($action == 'builddoc')	// In get or post
{
    /*
     * Generate order document
     * define into /core/modules/commande/modules_commande.php
     */

    // Sauvegarde le dernier modele choisi pour generer un document
    $result=$object->fetch($id);
    $object->fetch_thirdparty();

    if ($_REQUEST['model'])
    {
        $object->setDocModel($user, $_REQUEST['model']);
    }

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
    $result=commande_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }
    else
    {
        Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
        exit;
    }
}

// Remove file in doc form
if ($action == 'remove_file')
{
    if ($object->fetch($id))
    {
        require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

        $upload_dir = $conf->commande->dir_output;
        $file = $upload_dir . '/' . $_GET['file'];
        dol_delete_file($file);
        $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
    }
}

/*
 * Add file in email form
 */
if ($_POST['addfile'])
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory TODO Use a dedicated directory for temp mails files
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

    $mesg=dol_add_file_process($upload_dir_tmp,0,0);

    $action ='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
    require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");

    // Set tmp user directory
    $vardir=$conf->user->dir_output."/".$user->id;
    $upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
    $mesg=dol_remove_file_process($_POST['removedfile'],0);

    $action ='presend';
}

/*
 * Send mail
 */
if ($action == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
    $langs->load('mails');

    $result=$object->fetch($id);
    $result=$object->fetch_thirdparty();

    if ($result > 0)
    {
        $ref = dol_sanitizeFileName($object->ref);
        $file = $conf->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';

        if (is_readable($file))
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

                if ($_POST['action'] == 'send')
                {
                    if (dol_strlen($_POST['subject'])) $subject=$_POST['subject'];
                    else $subject = $langs->transnoentities('Order').' '.$object->ref;
                    $actiontypecode='AC_COM';
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
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);

                $attachedfiles=$formmail->get_attached_files();
                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];

                // Send mail
                require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt);
                if ($mailfile->error)
                {
                    $mesg='<div class="error">'.$mailfile->error.'</div>';
                }
                else
                {
                    $result=$mailfile->sendfile();
                    if ($result)
                    {
                        $mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));	// Must not contains "

                        $error=0;

                        // Initialisation donnees
                        $object->sendtoid		= $sendtoid;
                        $object->actiontypecode	= $actiontypecode;
                        $object->actionmsg		= $actionmsg;
                        $object->actionmsg2		= $actionmsg2;
                        $object->fk_element		= $object->id;
                        $object->elementtype	= $object->element;

                        // Appel des triggers
                        include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                        $interface=new Interfaces($db);
                        $result=$interface->run_triggers('ORDER_SENTBYMAIL',$object,$user,$langs,$conf);
                        if ($result < 0) { $error++; $this->errors=$interface->errors; }
                        // Fin appel triggers

                        if ($error)
                        {
                            dol_print_error($db);
                        }
                        else
                        {
                            // Redirect here
                            // This avoid sending mail twice if going out and then back to page
                            Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.'&mesg='.urlencode($mesg));
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
                $mesg='<div class="error">'.$langs->trans('ErrorMailRecipientIsEmpty').' !</div>';
                $action='presend';
                dol_syslog('Recipient email is empty');
            }
        }
        else
        {
            $langs->load("errors");
            $mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
            dol_syslog('Failed to read file: '.$file);
        }
    }
    else
    {
        $langs->load("other");
        $mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Order")).'</div>';
        dol_syslog($langs->trans('ErrorFailedToReadEntity', $langs->trans("Order")));
    }
}


/*
 *	View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$form = new Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($action == 'create' && $user->rights->commande->creer)
{
    print_fiche_titre($langs->trans('CreateOrder'));

    dol_htmloutput_mesg($mesg,$mesgs,'error');

    $soc = new Societe($db);
    if ($socid) $res=$soc->fetch($socid);

    if (GETPOST('origin') && GETPOST('originid'))
    {
        // Parse element/subelement (ex: project_task)
        $element = $subelement = GETPOST('origin');
        if (preg_match('/^([^_]+)_([^_]+)/i',GETPOST('origin'),$regs))
        {
            $element = $regs[1];
            $subelement = $regs[2];
        }

        if ($element == 'project')
        {
            $projectid=GETPOST('originid');
        }
        else
        {
            // For compatibility
            if ($element == 'order' || $element == 'commande')    { $element = $subelement = 'commande'; }
            if ($element == 'propal')   { $element = 'comm/propal'; $subelement = 'propal'; }
            if ($element == 'contract') { $element = $subelement = 'contrat'; }

            dol_include_once('/'.$element.'/class/'.$subelement.'.class.php');

            $classname = ucfirst($subelement);
            $objectsrc = new $classname($db);
            $objectsrc->fetch(GETPOST('originid'));
            if (empty($objectsrc->lines) && method_exists($objectsrc,'fetch_lines'))  $objectsrc->fetch_lines();
            $objectsrc->fetch_thirdparty();

            $projectid          = (!empty($objectsrc->fk_project)?$object->fk_project:'');
            $ref_client         = (!empty($objectsrc->ref_client)?$object->ref_client:'');

            $soc = $objectsrc->client;
            $cond_reglement_id  = (!empty($objectsrc->cond_reglement_id)?$objectsrc->cond_reglement_id:(!empty($soc->cond_reglement_id)?$soc->cond_reglement_id:1));
            $mode_reglement_id  = (!empty($objectsrc->mode_reglement_id)?$objectsrc->mode_reglement_id:(!empty($soc->mode_reglement_id)?$soc->mode_reglement_id:0));
            $availability_id  = (!empty($objectsrc->availability_id)?$objectsrc->availability_id:(!empty($soc->availability_id)?$soc->availability_id:0));
            $demand_reason_id  = (!empty($objectsrc->demand_reason_id)?$objectsrc->demand_reason_id:(!empty($soc->demand_reason_id)?$soc->demand_reason_id:0));
            $remise_percent     = (!empty($objectsrc->remise_percent)?$objectsrc->remise_percent:(!empty($soc->remise_percent)?$soc->remise_percent:0));
            $remise_absolue     = (!empty($objectsrc->remise_absolue)?$objectsrc->remise_absolue:(!empty($soc->remise_absolue)?$soc->remise_absolue:0));
            $dateinvoice        = empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;

            // Object source contacts list
            $srccontactslist = $objectsrc->liste_contact(-1,'external',1);
        }
    }
    else
    {
        $cond_reglement_id  = $soc->cond_reglement_id;
        $mode_reglement_id  = $soc->mode_reglement_id;
        $availability_id    = $soc->availability_id;
        $demand_reason_id   = $soc->demand_reason_id;
        $remise_percent     = $soc->remise_percent;
        $remise_absolue     = 0;
        $dateinvoice        = empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
    }
    $absolute_discount=$soc->getAvailableDiscounts();



    $nbrow=10;

    print '<form name="crea_commande" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';
    print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
    print '<input type="hidden" name="remise_percent" value="'.$soc->remise_client.'">';
    print '<input name="facnumber" type="hidden" value="provisoire">';
    print '<input type="hidden" name="origin" value="'.GETPOST('origin').'">';
    print '<input type="hidden" name="originid" value="'.GETPOST('originid').'">';

    print '<table class="border" width="100%">';

    // Reference
    print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans("Draft").'</td></tr>';

    // Reference client
    print '<tr><td>'.$langs->trans('RefCustomer').'</td><td colspan="2">';
    print '<input type="text" name="ref_client" value=""></td>';
    print '</tr>';

    // Client
    print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td colspan="2">'.$soc->getNomUrl(1).'</td></tr>';

    /*
     * Contact de la commande
     */
    print "<tr><td>".$langs->trans("DefaultContact").'</td><td colspan="2">';
    $form->select_contacts($soc->id,$setcontact,'contactidp',1,$srccontactslist);
    print '</td></tr>';

    // Ligne info remises tiers
    print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
    if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
    else print $langs->trans("CompanyHasNoRelativeDiscount");
    print '. ';
    $absolute_discount=$soc->getAvailableDiscounts();
    if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
    else print $langs->trans("CompanyHasNoAbsoluteDiscount");
    print '.';
    print '</td></tr>';

    // Date
    print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
    $form->select_date('','re','','','',"crea_commande",1,1);
    print '</td></tr>';

    // Date de livraison
    print "<tr><td>".$langs->trans("DeliveryDate").'</td><td colspan="2">';
    if ($conf->global->DATE_LIVRAISON_WEEK_DELAY)
    {
        $datedelivery = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
    }
    else
    {
        $datedelivery=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
    }
    $form->select_date($datedelivery,'liv_','','','',"crea_commande",1,1);
    print "</td></tr>";

    // Delivery address
    if ($conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
    {
        // Link to edit: $form->form_address($_SERVER['PHP_SELF'].'?action=create','',$soc->id,'adresse_livraison_id','commande','');
        print '<tr><td nowrap="nowrap">'.$langs->trans('DeliveryAddress').'</td><td colspan="2">';
        $numaddress = $form->select_address($soc->fk_delivery_address, $socid,'fk_address',1);
        print ' &nbsp; <a href="../comm/address.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddAddress").'</a>';
        print '</td></tr>';
    }

    // Conditions de reglement
    print '<tr><td nowrap="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
    $form->select_conditions_paiements($soc->cond_reglement,'cond_reglement_id',-1,1);
    print '</td></tr>';

    // Mode de reglement
    print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
    $form->select_types_paiements($soc->mode_reglement,'mode_reglement_id');
    print '</td></tr>';

    // Delivery delay
    print '<tr><td>'.$langs->trans('AvailabilityPeriod').'</td><td colspan="2">';
    $form->select_availability($propal->availability,'availability_id','',1);
    print '</td></tr>';

    // What trigger creation
    print '<tr><td>'.$langs->trans('Source').'</td><td colspan="2">';
    $form->select_demand_reason((GETPOST("origin")=='propal'?'SRC_COMM':''),'demand_reason_id','',1);
    print '</td></tr>';

    // Project
    if ($conf->projet->enabled)
    {
        $projectid = 0;
        if (isset($_GET["origin"]) && $_GET["origin"] == 'project') $projectid = ($_GET["originid"]?$_GET["originid"]:0);

        print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
        $numprojet=select_projects($soc->id,$projectid);
        if ($numprojet==0)
        {
            print ' &nbsp; <a href="'.DOL_URL_ROOT.'/projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
        }
        print '</td></tr>';
    }

    // Other attributes
    $parameters=array('colspan' => ' colspan="3"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook))
    {
        foreach($extrafields->attribute_label as $key=>$label)
        {
            $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
            print "<tr><td>".$label.'</td><td colspan="3">';
            print $extrafields->showInputField($key,$value);
            print '</td></tr>'."\n";
        }
    }

    // Template to use by default
    print '<tr><td>'.$langs->trans('Model').'</td>';
    print '<td colspan="2">';
    include_once(DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php');
    $liste=ModelePDFCommandes::liste_modeles($db);
    print $form->selectarray('model',$liste,$conf->global->COMMANDE_ADDON_PDF);
    print "</td></tr>";

    // Note publique
    print '<tr>';
    print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
    print '<td valign="top" colspan="2">';
    print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
    print '</textarea></td></tr>';

    // Note privee
    if (! $user->societe_id)
    {
        print '<tr>';
        print '<td class="border" valign="top">'.$langs->trans('NotePrivate').'</td>';
        print '<td valign="top" colspan="2">';
        print '<textarea name="note" wrap="soft" cols="70" rows="'.ROWS_3.'">';
        print '</textarea></td></tr>';
    }

    if (is_object($objectsrc))
    {
        // TODO for compatibility
        if ($_GET['origin'] == 'contrat')
        {
            // Calcul contrat->price (HT), contrat->total (TTC), contrat->tva
            $objectsrc->remise_absolue=$remise_absolue;
            $objectsrc->remise_percent=$remise_percent;
            $objectsrc->update_price(1);
        }

        print "\n<!-- ".$classname." info -->";
        print "\n";
        print '<input type="hidden" name="amount"         value="'.$objectsrc->total_ht.'">'."\n";
        print '<input type="hidden" name="total"          value="'.$objectsrc->total_ttc.'">'."\n";
        print '<input type="hidden" name="tva"            value="'.$objectsrc->total_tva.'">'."\n";
        print '<input type="hidden" name="origin"         value="'.$objectsrc->element.'">';
        print '<input type="hidden" name="originid"       value="'.$objectsrc->id.'">';

        $newclassname=$classname;
        if ($newclassname=='Propal') $newclassname='CommercialProposal';
        print '<tr><td>'.$langs->trans($newclassname).'</td><td colspan="2">'.$objectsrc->getNomUrl(1).'</td></tr>';
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
        if ($conf->global->PRODUCT_SHOW_WHEN_CREATE)
        {
            /*
             * Services/produits predefinis
             */
            $NBLINES=8;

            print '<tr><td colspan="3">';

            print '<table class="noborder">';
            print '<tr><td>'.$langs->trans('ProductsAndServices').'</td>';
            print '<td>'.$langs->trans('Qty').'</td>';
            print '<td>'.$langs->trans('ReductionShort').'</td>';
            print '</tr>';
            for ($i = 1 ; $i <= $NBLINES ; $i++)
            {
                print '<tr><td>';
                // multiprix
                if($conf->global->PRODUIT_MULTIPRICES)
                print $form->select_produits('','idprod'.$i,'',$conf->product->limit_size,$soc->price_level);
                else
                print $form->select_produits('','idprod'.$i,'',$conf->product->limit_size);
                print '</td>';
                print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
                print '<td><input type="text" size="3" name="remise_percent'.$i.'" value="'.$soc->remise_client.'">%</td></tr>';
            }

            print '</table>';
            print '</td></tr>';
        }
    }

    print '</table>';

    // Button "Create Draft"
    print '<br><center><input type="submit" class="button" name="bouton" value="'.$langs->trans('CreateDraft').'"></center>';

    print '</form>';


    // Show origin lines
    if (is_object($objectsrc))
    {
        $title=$langs->trans('ProductsAndServices');
        print_titre($title);

        print '<table class="noborder" width="100%">';

        $objectsrc->printOriginLinesList($hookmanager);

        print '</table>';
    }

}
else
{
    /* *************************************************************************** */
    /*                                                                             */
    /* Mode vue et edition                                                         */
    /*                                                                             */
    /* *************************************************************************** */
    $now=dol_now();

    if ($id > 0 || ! empty($ref))
    {
        dol_htmloutput_mesg($mesg,$mesgs);
        dol_htmloutput_errors('',$errors);

        $product_static=new Product($db);

        $result=$object->fetch($id,$ref);
        if ($result > 0)
        {
            $soc = new Societe($db);
            $soc->fetch($object->socid);

            $author = new User($db);
            $author->fetch($object->user_author_id);

            $head = commande_prepare_head($object);
            dol_fiche_head($head, 'order', $langs->trans("CustomerOrder"), 0, 'order');

            $formconfirm='';

            /*
             * Confirmation de la suppression de la commande
             */
            if ($action == 'delete')
            {
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
            }

            /*
             * Confirmation de la validation
             */
            if ($action == 'validate')
            {
                // on verifie si l'objet est en numerotation provisoire
                $ref = substr($object->ref, 1, 4);
                if ($ref == 'PROV')
                {
                    $numref = $object->getNextNumRef($soc);
                }
                else
                {
                    $numref = $object->ref;
                }

                $text=$langs->trans('ConfirmValidateOrder',$numref);
                if ($conf->notification->enabled)
                {
                    require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
                    $notify=new Notify($db);
                    $text.='<br>';
                    $text.=$notify->confirmMessage('NOTIFY_VAL_ORDER',$object->socid);
                }
                $formquestion=array();
                if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
                }

                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_validate', $formquestion, 0, 1, 220);
            }

            // Confirm back to draft status
            if ($action == 'modif')
            {
                $text=$langs->trans('ConfirmUnvalidateOrder',$object->ref);
                $formquestion=array();
                if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
                }

                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('UnvalidateOrder'), $text, 'confirm_modif', $formquestion, "yes", 1, 220);
            }


            /*
             * Confirmation de la cloture
             */
            if ($action == 'close')
            {
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_close', '', 0, 1);
            }

            /*
             * Confirmation de l'annulation
             */
            if ($action == 'cancel')
            {
                $text=$langs->trans('ConfirmCancelOrder',$object->ref);
                $formquestion=array();
                if (! empty($conf->global->STOCK_CALCULATE_ON_VALIDATE_ORDER) && $object->hasProductsOrServices(1))
                {
                    $langs->load("stocks");
                    require_once(DOL_DOCUMENT_ROOT."/product/class/html.formproduct.class.php");
                    $formproduct=new FormProduct($db);
                    $formquestion=array(
                    //'text' => $langs->trans("ConfirmClone"),
                    //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                    //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                    array('type' => 'other', 'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockIncrease"),   'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse'),'idwarehouse','',1)));
                }

                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Cancel'), $text, 'confirm_cancel', $formquestion, 0, 1);
            }

            /*
             * Confirmation de la suppression d'une ligne produit
             */
            if ($action == 'ask_deleteline')
            {
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
            }

            // Clone confirmation
            if ($action == 'clone')
            {
                // Create an array for form
                $formquestion=array(
                //'text' => $langs->trans("ConfirmClone"),
                //array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1),
                //array('type' => 'checkbox', 'name' => 'update_prices',   'label' => $langs->trans("PuttingPricesUpToDate"),   'value' => 1),
                array('type' => 'other', 'name' => 'socid',   'label' => $langs->trans("SelectThirdParty"),   'value' => $form->select_company(GETPOST('socid'),'socid','(s.client=1 OR s.client=3)'))
                );
                // Paiement incomplet. On demande si motif = escompte ou autre
                $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneOrder'),$langs->trans('ConfirmCloneOrder',$object->ref),'confirm_clone',$formquestion,'yes',1);
            }

            if (! $formconfirm)
            {
                $parameters=array('lineid'=>$lineid);
                $formconfirm=$hookmanager->executeHooks('formConfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            }

            // Print form confirm
            print $formconfirm;

            /*
             *   Commande
             */
            $nbrow=9;
            if ($conf->projet->enabled) $nbrow++;

            //Local taxes
            if ($mysoc->country_code=='ES')
            {
                if($mysoc->localtax1_assuj=="1") $nbrow++;
                if($mysoc->localtax2_assuj=="1") $nbrow++;
            }

            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
            print '<td colspan="3">';
            print $form->showrefnav($object,'ref','',1,'ref','ref');
            print '</td>';
            print '</tr>';

            // Ref commande client
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
            print $langs->trans('RefCustomer').'</td><td align="left">';
            print '</td>';
            if ($action != 'refcustomer' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refcustomer&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($user->rights->commande->creer && $action == 'refcustomer')
            {
                print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="set_ref_client">';
                print '<input type="text" class="flat" size="20" name="ref_client" value="'.$object->ref_client.'">';
                print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
                print '</form>';
            }
            else
            {
                print $object->ref_client;
            }
            print '</td>';
            print '</tr>';


            // Societe
            print '<tr><td>'.$langs->trans('Company').'</td>';
            print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
            print '</tr>';

            // Ligne info remises tiers
            print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="3">';
            if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
            else print $langs->trans("CompanyHasNoRelativeDiscount");
            print '. ';
            $absolute_discount=$soc->getAvailableDiscounts('','fk_facture_source IS NULL');
            $absolute_creditnote=$soc->getAvailableDiscounts('','fk_facture_source IS NOT NULL');
            $absolute_discount=price2num($absolute_discount,'MT');
            $absolute_creditnote=price2num($absolute_creditnote,'MT');
            if ($absolute_discount)
            {
                if ($object->statut > 0)
                {
                    print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->currency));
                }
                else
                {
                    // Remise dispo de type non avoir
                    $filter='fk_facture_source IS NULL';
                    print '<br>';
                    $form->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$object->id,0,'remise_id',$soc->id,$absolute_discount,$filter);
                }
            }
            if ($absolute_creditnote)
            {
                print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->currency)).'. ';
            }
            if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
            print '</td></tr>';

            // Date
            print '<tr><td>';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('Date');
            print '</td>';

            if ($action != 'editdate' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="3">';
            if ($action == 'editdate')
            {
                print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="setdate">';
                $form->select_date($object->date,'order_','','','',"setdate");
                print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
                print '</form>';
            }
            else
            {
                print $object->date ? dol_print_date($object->date,'daytext') : '&nbsp;';
            }
            print '</td>';
            print '</tr>';

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
            print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
            print dol_htmlcleanlastbr($object->note_public);
            print '</td>';
            print '</tr>';

            // Delivery address
            if ($conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
            {
                print '<tr><td height="10">';
                print '<table class="nobordernopadding" width="100%"><tr><td>';
                print $langs->trans('DeliveryAddress');
                print '</td>';

                if ($action != 'editdelivery_adress' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$object->socid.'&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
                print '</tr></table>';
                print '</td><td colspan="2">';

                if ($action == 'editdelivery_adress')
                {
                    $form->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$socid,'fk_address','commande',$object->id);
                }
                else
                {
                    $form->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$socid,'none','commande',$object->id);
                }
                print '</td></tr>';
            }

            // Terms of payment
            print '<tr><td height="10">';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('PaymentConditionsShort');
            print '</td>';
            if ($action != 'editconditions' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="2">';
            if ($action == 'editconditions')
            {
                $form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id',1);
            }
            else
            {
                $form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none',1);
            }
            print '</td>';

            print '</tr>';

            // Mode of payment
            print '<tr><td height="10">';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('PaymentMode');
            print '</td>';
            if ($action != 'editmode' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
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

            // Availability
            print '<tr><td height="10">';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('AvailabilityPeriod');
            print '</td>';
            if ($action != 'editavailability' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editavailability&amp;id='.$object->id.'">'.img_edit($langs->trans('SetAvailability'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="2">';
            if ($action == 'editavailability')
            {
                $form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id,$object->availability_id,'availability_id',1);
            }
            else
            {
                $form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id,$object->availability_id,'none',1);
            }
            print '</td></tr>';

            // Source
            print '<tr><td height="10">';
            print '<table class="nobordernopadding" width="100%"><tr><td>';
            print $langs->trans('Source');
            print '</td>';
            if ($_GET['action'] != 'editdemandreason' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdemandreason&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDemandReason'),1).'</a></td>';
            print '</tr></table>';
            print '</td><td colspan="2">';
            if ($_GET['action'] == 'editdemandreason')
            {
                $form->form_demand_reason($_SERVER['PHP_SELF'].'?id='.$object->id,$object->demand_reason_id,'demand_reason_id',1);
            }
            else
            {
                $form->form_demand_reason($_SERVER['PHP_SELF'].'?id='.$object->id,$object->demand_reason_id,'none');
            }
            // Removed because using dictionnary is an admin feature, not a user feature. Ther is already the "star" to show info to admin users.
            // This is to avoid too heavy screens and have an uniform look and feel for all screens.
            //print '</td><td>';
            //print '<a href="'.DOL_URL_ROOT.'/admin/dict.php?id=22&origin=order&originid='.$object->id.'">'.$langs->trans("DictionnarySource").'</a>';
            print '</td></tr>';

            // Project
            if ($conf->projet->enabled)
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
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'projectid');
                }
                else
                {
                    $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none');
                }
                print '</td></tr>';
            }

            // Other attributes
            $parameters=array('colspan' => ' colspan="2"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook))
            {
                foreach($extrafields->attribute_label as $key=>$label)
                {
                    $value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
                    print '<tr><td>'.$label.'</td><td colspan="3">';
                    print $extrafields->showInputField($key,$value);
                    print '</td></tr>'."\n";
                }
            }

            // Total HT
            print '<tr><td>'.$langs->trans('AmountHT').'</td>';
            print '<td align="right"><b>'.price($object->total_ht).'</b></td>';
            print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

            // Total TVA
            print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($object->total_tva).'</td>';
            print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

            // Amount Local Taxes
            if ($mysoc->country_code=='ES')
            {
                if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
                {
                    print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
                    print '<td align="right">'.price($object->total_localtax1).'</td>';
                    print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
                }
                if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
                {
                    print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
                    print '<td align="right">'.price($object->total_localtax2).'</td>';
                    print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
                }
            }

            // Total TTC
            print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($object->total_ttc).'</td>';
            print '<td>'.$langs->trans('Currency'.$conf->currency).'</td></tr>';

            // Statut
            print '<tr><td>'.$langs->trans('Status').'</td>';
            print '<td colspan="2">'.$object->getLibStatut(4).'</td>';
            print '</tr>';

            print '</table><br>';
            print "\n";

            /*
             * Lines
             */
            $result = $object->getLinesArray();

            $numlines = count($object->lines);

            if ($conf->use_javascript_ajax && $object->statut == 0)
            {
                include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php');
            }

            print '<table id="tablelines" class="noborder" width="100%">';

            // Show object lines
            if (! empty($object->lines)) $object->printObjectLines($action,$mysoc,$soc,$lineid,1,$hookmanager);

            /*
             * Form to add new line
             */
            if ($object->statut == 0 && $user->rights->commande->creer)
            {
                if ($action != 'editline')
                {
                    $var=true;

                    $object->formAddFreeProduct(1,$mysoc,$soc,$hookmanager);

                    // Add predefined products/services
                    if ($conf->product->enabled || $conf->service->enabled)
                    {
                        $var=!$var;
                        $object->formAddPredefinedProduct(1,$mysoc,$soc,$hookmanager);
                    }

                    $parameters=array();
                    $reshook=$hookmanager->executeHooks('formAddObject',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
                }
            }
            print '</table>';
            print '</div>';


            /*
             * Boutons actions
             */
            if ($action != 'presend')
            {
                if ($user->societe_id == 0 && $action <> 'editline')
                {
                    print '<div class="tabsAction">';

                    // Valid
                    if ($object->statut == 0 && $object->total_ttc >= 0 && $numlines > 0 && $user->rights->commande->valider)
                    {
                        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a>';
                    }

                    // Edit
                    if ($object->statut == 1 && $user->rights->commande->creer)
                    {
                        print '<a class="butAction" href="fiche.php?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
                    }

                    // Send
                    if ($object->statut > 0)
                    {
                        $comref = dol_sanitizeFileName($object->ref);
                        $file = $conf->commande->dir_output . '/'.$comref.'/'.$comref.'.pdf';
                        if (file_exists($file))
                        {
                            if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->commande->order_advance->send))
                            {
                                print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                            }
                            else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
                        }
                    }

                    // Ship
                    $numshipping=0;
                    if ($conf->expedition->enabled)
                    {
                        $numshipping = $object->nb_expedition();

                        if ($object->statut > 0 && $object->statut < 3 && $object->getNbOfProductsLines() > 0)
                        {
						    if (($conf->expedition_bon->enabled && $user->rights->expedition->creer)
	                        || ($conf->livraison_bon->enabled && $user->rights->expedition->livraison->creer))
	                        {
                                if ($user->rights->expedition->creer)
                                {
                                    print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/shipment.php?id='.$object->id.'">'.$langs->trans('ShipProduct').'</a>';
                                }
                                else
                                {
                                    print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('ShipProduct').'</a>';
                                }
	                        }
	                        else
	                        {
                                $langs->load("errors");
	                            print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("ErrorModuleSetupNotComplete")).'">'.$langs->trans('ShipProduct').'</a>';
	                        }
                        }
                    }

                    // Reopen a closed order
                    if ($object->statut == 3)
                    {
                        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=reopen">'.$langs->trans('ReOpen').'</a>';
                    }

                    // Create bill and Classify billed
                    if ($conf->facture->enabled && $object->statut > 0  && ! $object->facturee)
                    {
                        if ($user->rights->facture->creer)
                        {
                            print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("CreateBill").'</a>';
                        }

                        if ($user->rights->commande->creer && $object->statut > 2)
                        {
                            print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled">'.$langs->trans("ClassifyBilled").'</a>';
                        }
                    }

                    // Close
                    if (($object->statut == 1 || $object->statut == 2) && $user->rights->commande->cloturer)
                    {
                        print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close">'.$langs->trans('Close').'</a>';
                    }

                    // Clone
                    if ($user->rights->commande->creer)
                    {
                        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a>';
                    }

                    // Cancel order
                    if ($object->statut == 1 && $user->rights->commande->annuler)
                    {
                        print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=cancel">'.$langs->trans('Cancel').'</a>';
                    }

                    // Delete order
                    if ($user->rights->commande->supprimer)
                    {
                        if ($numshipping == 0)
                        {
                            print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
                        }
                        else
                        {
                            print '<a class="butActionRefused" href="#" title="'.$langs->trans("ShippingExist").'">'.$langs->trans("Delete").'</a>';
                        }
                    }

                    print '</div>';
                }
                print '<br>';
            }


            if ($action != 'presend')
            {
                print '<table width="100%"><tr><td width="50%" valign="top">';
                print '<a name="builddoc"></a>'; // ancre

                /*
                 * Documents generes
                 *
                 */
                $comref = dol_sanitizeFileName($object->ref);
                $file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
                $relativepath = $comref.'/'.$comref.'.pdf';
                $filedir = $conf->commande->dir_output . '/' . $comref;
                $urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
                $genallowed=$user->rights->commande->creer;
                $delallowed=$user->rights->commande->supprimer;

                $somethingshown=$formfile->show_documents('commande',$comref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang,$hookmanager);

                /*
                 * Linked object block
                 */
                $somethingshown=$object->showLinkedObjectBlock();

                print '</td><td valign="top" width="50%">';

                // List of actions on element
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
                $formactions=new FormActions($db);
                $somethingshown=$formactions->showactions($object,'order',$socid);

                print '</td></tr></table>';
            }


            /*
             * Action presend
             *
             */
            if ($action == 'presend')
            {
                $ref = dol_sanitizeFileName($object->ref);
                $file = $conf->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';

                print '<br>';
                print_titre($langs->trans('SendOrderByMail'));

                // Cree l'objet formulaire mail
                include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
                $formmail = new FormMail($db);
                $formmail->fromtype = 'user';
                $formmail->fromid   = $user->id;
                $formmail->fromname = $user->getFullName($langs);
                $formmail->frommail = $user->email;
                $formmail->withfrom=1;
                $formmail->withto=empty($_POST["sendto"])?1:$_POST["sendto"];
                $formmail->withtosocid=$soc->id;
                $formmail->withtocc=1;
                $formmail->withtoccsocid=0;
                $formmail->withtoccc=$conf->global->MAIN_EMAIL_USECCC;
                $formmail->withtocccsocid=0;
                $formmail->withtopic=$langs->trans('SendOrderRef','__ORDERREF__');
                $formmail->withfile=2;
                $formmail->withbody=1;
                $formmail->withdeliveryreceipt=1;
                $formmail->withcancel=1;
                // Tableau des substitutions
                $formmail->substit['__ORDERREF__']=$object->ref;
                // Tableau des parametres complementaires
                $formmail->param['action']='send';
                $formmail->param['models']='order_send';
                $formmail->param['orderid']=$object->id;
                $formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

                // Init list of files
                if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
                {
                    $formmail->clear_attached_files();
                    $formmail->add_attached_files($file,dol_sanitizeFilename($ref.'.pdf'),'application/pdf');
                }

                // Show form
                $formmail->show_form();

                print '<br>';
            }
        }
        else
        {
            // Commande non trouvee
            dol_print_error($db);
        }
    }
}

$db->close();

llxFooter();
?>
