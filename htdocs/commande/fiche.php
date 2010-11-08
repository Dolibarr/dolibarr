<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2010 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010      Juanjo Menent         <jmenent@2byte.es>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/commande/fiche.php
 *	\ingroup    commande
 *	\brief      Page to show customer order
 *	\version    $Id$
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formorder.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');

if (!$user->rights->commande->lire) accessforbidden();

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');

$comid = isset($_GET["id"])?$_GET["id"]:(isset($_POST["id"])?$_POST["id"]:'');
if (empty($comid)) $comid=isset($_GET["orderid"])?$_GET["orderid"]:(isset($_POST["orderid"])?$_POST["orderid"]:'');

// Security check
$socid=0;
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$comid,'');

$mesg=isset($_GET['mesg'])?$_GET['mesg']:'';

$object = new Commande($db);

// Instantiate hooks of thirdparty module
if (is_array($conf->hooks_modules) && !empty($conf->hooks_modules))
{
	$object->callHooks('objectcard');
}


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

// Hook of thirdparty module
if (! empty($object->hooks))
{
	foreach($object->hooks as $module)
	{
		$module->doActions($object);
		$mesg = $module->error;
	}
}

// Action clone object
if ($_REQUEST["action"] == 'confirm_clone' && $_REQUEST['confirm'] == 'yes')
{
	if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$result=$object->createFromClone($comid);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$_GET['action']='';
		}
	}
}

// Reopen a closed order
if ($_GET['action'] == 'reopen' && $user->rights->commande->creer)
{
	$object->fetch($comid);
	if ($object->statut == 3)
	{
		$result = $object->set_reopen($user);
		if ($result > 0)
		{
			Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$comid);
			exit;
		}
		else
		{
			$mesg='<div class="error">'.$fac->error.'</div>';
		}
	}
}

// Suppression de la commande
if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->commande->supprimer)
	{
		$object->fetch($comid);
		$result=$object->delete($user);
		if ($result > 0)
		{
			Header('Location: index.php');
			exit;
		}
		else
		{
			$mesg=$object->error;
		}
	}
}

// Remove a product line
if ($_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->commande->creer)
	{
		$object->fetch($comid);
		$object->fetch_thirdparty();

		$result = $object->deleteline($_GET['lineid']);
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
			commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);
		}
		else
		{
			print $object->error;
		}
	}
	Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$comid);
	exit;
}

// Categorisation dans projet
if ($_POST['action'] == 'classin')
{
	$object->fetch($comid);
	$object->setProject($_POST['projectid']);
}

// Add order
if ($_POST['action'] == 'add' && $user->rights->commande->creer)
{
	$datecommande='';
	$datecommande  = dol_mktime(12, 0, 0, $_POST['remonth'],  $_POST['reday'],  $_POST['reyear']);
	$datelivraison = dol_mktime(12, 0, 0, $_POST['liv_month'],$_POST['liv_day'],$_POST['liv_year']);

	$object->socid=$_POST['socid'];
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
	$object->date_livraison       = $datelivraison;
	$object->fk_delivery_address  = $_POST['fk_address'];
	$object->contactid            = $_POST['contactidp'];

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

	$object_id = $object->create($user);

	if ($object_id > 0)
	{
		// Insertion contact par defaut si defini
		if ($_POST["contactidp"])
		{
			$result=$object->add_contact($_POST["contactidp"],'CUSTOMER','external');

			if ($result > 0)
			{
				$error=0;
			}
			else
			{
				$mesg = '<div class="error">'.$langs->trans("ErrorFailedToAddContact").'</div>';
				$error=1;
			}
		}

		$comid = $object_id;
		$action = '';
	}

	// Fin creation facture, on l'affiche
	if ($object_id > 0 && ! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
		$_GET["action"]='create';
		$_GET['socid']=$_POST['socid'];
		if (! $mesg) $mesg='<div class="error">'.$object->error.'</div>';
	}

}

if ($_GET["action"] == 'classifybilled')
{
	$object->fetch($comid);
	$object->classer_facturee();
}

// Positionne ref commande client
if ($_POST['action'] == 'set_ref_client' && $user->rights->commande->creer)
{
	$object->fetch($comid);
	$object->set_ref_client($user, $_POST['ref_client']);
}

if ($_POST['action'] == 'setremise' && $user->rights->commande->creer)
{
	$object->fetch($comid);
	$object->set_remise($user, $_POST['remise']);
}

if ($_POST['action'] == "setabsolutediscount" && $user->rights->commande->creer)
{
	if ($_POST["remise_id"])
	{
		$ret=$object->fetch($comid);
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

if ($_POST['action'] == 'setdate' && $user->rights->commande->creer)
{
	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	$date=dol_mktime(0, 0, 0, $_POST['order_month'], $_POST['order_day'], $_POST['order_year']);

	$object->fetch($comid);
	$result=$object->set_date($user,$date);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

if ($_POST['action'] == 'setdate_livraison' && $user->rights->commande->creer)
{
	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	$datelivraison=dol_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

	$object->fetch($comid);
	$result=$object->set_date_livraison($user,$datelivraison);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

if ($_POST['action'] == 'setaddress' && $user->rights->commande->creer)
{
	$object->fetch($comid);
	$object->set_adresse_livraison($user,$_POST['fk_address']);
}

if ($_POST['action'] == 'setmode' && $user->rights->commande->creer)
{
	$object->fetch($comid);
	$result=$object->mode_reglement($_POST['mode_reglement_id']);
	if ($result < 0) dol_print_error($db,$object->error);
}

if ($_POST['action'] == 'setconditions' && $user->rights->commande->creer)
{
	$object->fetch($comid);
	$result=$object->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dol_print_error($db,$object->error);
}

if ($_REQUEST['action'] == 'setremisepercent' && $user->rights->facture->creer)
{
	$object->fetch($comid);
	$result = $object->set_remise($user, $_POST['remise_percent']);
}

if ($_REQUEST['action'] == 'setremiseabsolue' && $user->rights->facture->creer)
{
	$object->fetch($comid);
	$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
}

/*
 *  Ajout d'une ligne produit dans la commande
 */
if ($_POST['action'] == 'addline' && $user->rights->commande->creer)
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
		$ret=$object->fetch($comid);
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
			$prod = new Product($db, $_POST['idprod']);
			$prod->fetch($_POST['idprod']);

			$tva_tx = get_default_tva($mysoc,$object->client,$prod->id);

			// multiprix
			if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)
			{
				$pu_ht = $prod->multiprices[$object->client->price_level];
				$pu_ttc = $prod->multiprices_ttc[$object->client->price_level];
				$price_base_type = $prod->multiprices_base_type[$object->client->price_level];
			}
			else
			{
				$pu_ht = $prod->price;
				$pu_ttc = $prod->price_ttc;
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

			$desc = $prod->description;
			$desc.= ($prod->description && $_POST['np_desc']) ? ((dol_textishtml($prod->description) || dol_textishtml($_POST['np_desc']))?"<br>":"\n") : "";
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
			if($prod->price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($prod->price_min)))
			{
                //print "CantBeLessThanMinPrice ".$up_ht." - ".GETPOST('remise_percent')." - ".$product->price_min;
			    $mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($prod->price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)).'</div>' ;
			}
			else
			{
				// Insert line
				$result = $object->addline(
				$comid,
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
				$type
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
					commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);

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
if ($_POST['action'] == 'updateligne' && $user->rights->commande->creer && $_POST['save'] == $langs->trans('Save'))
{
	if (! $object->fetch($comid) > 0) dol_print_error($db);
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
	}
	if ($product->price_min && GETPOST('productid') && (price2num($up_ht)*(1-price2num($_POST['elremise_percent'])/100) < price2num($product->price_min)))
	{
		$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($product->price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)).'</div>' ;
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
		$result = $object->updateline($_POST['lineid'],
		$description,
		$up_ht,
		$_POST['qty'],
		$_POST['elremise_percent'],
		$vat_rate,
		$localtax1_rate,
		$localtax2_rate,
		'HT',
		$info_bits,
		$date_start,
		$date_end,
		$type
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
			commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);
		}
		else
		{
			dol_print_error($db,$object->error);
			exit;
		}
	}
}

if ($_POST['action'] == 'updateligne' && $user->rights->commande->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
	Header('Location: fiche.php?id='.$comid);   // Pour reaffichage de la fiche en cours d'edition
	exit;
}

if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes' && $user->rights->commande->valider)
{
	$object->fetch($comid);	// Load order and lines
	$object->fetch_thirdparty();

	$result=$object->valid($user);
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
		commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	}
}

if ($_REQUEST['action'] == 'confirm_close' && $_REQUEST['confirm'] == 'yes' && $user->rights->commande->cloturer)
{
	$object->fetch($comid);		// Load order and lines

	$result = $object->cloture($user);
}

if ($_REQUEST['action'] == 'confirm_cancel' && $_REQUEST['confirm'] == 'yes' && $user->rights->commande->valider)
{
	$object->fetch($comid);		// Load order and lines

	$result = $object->cancel($user);
}

if ($_GET['action'] == 'modif' && $user->rights->commande->creer)
{
	/*
	 *  Repasse la commande en mode brouillon
	 */
	$object->fetch($comid);		// Load order and lines
	$object->fetch_thirdparty();

	$result = $object->set_draft($user);
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
		commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	}
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->commande->creer)
{
	$object->fetch($comid);
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

	commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);

	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$comid.'#'.$_GET['rowid']);
	exit;
}

if ($_GET['action'] == 'down' && $user->rights->commande->creer)
{
	$object->fetch($comid);
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
	commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);

	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$comid.'#'.$_GET['rowid']);
	exit;
}

if ($_REQUEST['action'] == 'builddoc')	// In get or post
{
	/*
	 * Generate order document
	 * define into /includes/modules/commande/modules_commande.php
	 */

	// Sauvegarde le dernier modele choisi pour generer un document
	$result=$object->fetch($comid);
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
	$result=commande_pdf_create($db, $object, $object->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}

// Remove file in doc form
if ($_REQUEST['action'] == 'remove_file')
{
	if ($object->fetch($id))
	{
		$upload_dir = $conf->commande->dir_output . "/";
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
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	// Set tmp user directory TODO Use a dedicated directory for temp mails files
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	$mesg=dol_add_file_process($upload_dir,0,0);

	$_GET["action"]='presend';
	$_POST["action"]='presend';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']))
{
	require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	$mesg=dol_remove_file_process($_POST['removedfile'],0);

	$_GET["action"]='presend';
	$_POST["action"]='presend';
}

/*
 * Send mail
 */
if ($_POST['action'] == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$result=$object->fetch($_POST['orderid']);
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
			elseif ($_POST['receiver'])
			{
				// Le destinataire a ete fourni via la liste deroulante
				if ($_POST['receiver'] < 0)	// Id du tiers
				{
					$sendto = $object->client->email;
					$sendtoid = 0;
				}
				else	// Id du contact
				{
					$sendto = $object->client->contact_get_email($_POST['receiver']);
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
				require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
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
						$mesg='<div class="ok">'.$langs->trans('MailSuccessfulySent',$from,$sendto).'.</div>';

						$error=0;

						// Initialisation donnees
						$object->sendtoid=$sendtoid;
						$object->actiontypecode=$actiontypecode;
						$object->actionmsg = $actionmsg;
						$object->actionmsg2= $actionmsg2;
						$object->orderrowid=$object->id;

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
				$_GET["action"]='presend';
				dol_syslog('Recipient email is empty');
			}
		}
		else
		{
			$langs->load("other");
			$mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
			dol_syslog('Failed to read file: '.$file);
		}
	}
	else
	{
		$langs->load("other");
		$mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
		dol_syslog('Impossible de lire les donnees de la facture. Le fichier facture n\'a peut-etre pas ete genere.');
	}
}


/*
 *	View
 */

llxHeader('',$langs->trans('Order'),'EN:Customers_Orders|FR:Commandes_Clients|ES:Pedidos de clientes');

$html = new Form($db);
$formfile = new FormFile($db);
$formorder = new FormOrder($db);


/*********************************************************************
 *
 * Mode creation
 *
 *********************************************************************/
if ($_GET['action'] == 'create' && $user->rights->commande->creer)
{
	print_fiche_titre($langs->trans('CreateOrder'));

	if ($mesg) print $mesg.'<br>';

	if ($propalid)
	{
		$sql = 'SELECT s.nom, s.prefix_comm, s.rowid';
		$sql.= ', p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, p.fk_cond_reglement, p.fk_mode_reglement';
		$sql.= ', p.datep as dp';
		$sql.= ', c.id as statut, c.label as lst';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p, '.MAIN_DB_PREFIX.'c_propalst as c';
		$sql .= ' WHERE p.fk_soc = s.rowid AND p.fk_statut = c.id';
		$sql .= ' AND p.rowid = '.$propalid;
	}
	else
	{
		$sql = 'SELECT s.nom, s.prefix_comm, s.rowid, s.mode_reglement, s.cond_reglement ';
		$sql .= 'FROM '.MAIN_DB_PREFIX.'societe as s ';
		$sql .= 'WHERE s.rowid = '.$_GET['socid'];
	}
	$resql = $db->query($sql);
	if ( $resql )
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			$obj = $db->fetch_object($resql);

			$soc = new Societe($db);
			$soc->fetch($obj->rowid);

			$nbrow=10;

			print '<form name="crea_commande" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
			print '<input type="hidden" name="remise_percent" value="'.$soc->remise_client.'">';
			print '<input name="facnumber" type="hidden" value="provisoire">';

			if (isset($_GET["origin"]) && $_GET["origin"] != 'project' && isset($_GET["originid"]))
			{
				print '<input type="hidden" name="origin" value="'.$_GET["origin"].'">';
				print '<input type="hidden" name="originid" value="'.$_GET["originid"].'">';
			}

			print '<table class="border" width="100%">';

			// Reference
			print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';

			// Reference client
			print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
			print '<input type="text" name="ref_client" value=""></td>';
			print '</tr>';

			// Client
			print '<tr><td class="fieldrequired">'.$langs->trans('Customer').'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

			/*
			 * Contact de la commande
			 */
			print "<tr><td>".$langs->trans("DefaultContact").'</td><td>';
			$html->select_contacts($soc->id,$setcontact,'contactidp',1);
			print '</td></tr>';

			// Ligne info remises tiers
			print '<tr><td>'.$langs->trans('Discounts').'</td><td>';
			if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			print '. ';
			$absolute_discount=$soc->getAvailableDiscounts();
			if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
			else print $langs->trans("CompanyHasNoAbsoluteDiscount");
			print '.';
			print '</td></tr>';

			// Date
			print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td>';
			$html->select_date('','re','','','',"crea_commande",1,1);
			print '</td></tr>';

			// Date de livraison
			print "<tr><td>".$langs->trans("DeliveryDate")."</td><td>";
			if ($conf->global->DATE_LIVRAISON_WEEK_DELAY)
			{
				$datedelivery = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
			}
			else
			{
				$datedelivery=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
			}
			$html->select_date($datedelivery,'liv_','','','',"crea_commande",1,1);
			print "</td></tr>";

			// Delivery address
			if ($conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
			{
				// Link to edit: $html->form_address($_SERVER['PHP_SELF'].'?action=create','',$soc->id,'adresse_livraison_id','commande','');
				print '<tr><td nowrap="nowrap">'.$langs->trans('DeliveryAddress').'</td><td>';
				$numaddress = $html->select_address($soc->fk_delivery_address, $_GET['socid'],'fk_address',1);
				print ' &nbsp; <a href="../comm/address.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddAddress").'</a>';
				print '</td></tr>';
			}

			// Conditions de reglement
			print '<tr><td nowrap="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
			$html->select_conditions_paiements($soc->cond_reglement,'cond_reglement_id',-1,1);
			print '</td></tr>';

			// Mode de reglement
			print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
			$html->select_types_paiements($soc->mode_reglement,'mode_reglement_id');
			print '</td></tr>';

			// Projet
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

			print '<tr><td>'.$langs->trans('Source').'</td><td colspan="2">';
			$formorder->selectSourcesCommande('','source_id',1);
			print '</td></tr>';

			print '<tr><td>'.$langs->trans('Model').'</td>';
			print '<td colspan="2">';
			// pdf
			include_once(DOL_DOCUMENT_ROOT.'/includes/modules/commande/modules_commande.php');
			$model=new ModelePDFCommandes();
			$liste=$model->liste_modeles($db);
			print $html->selectarray('model',$liste,$conf->global->COMMANDE_ADDON_PDF);
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

			if ($propalid > 0)
			{
				$amount = ($obj->price);
				print '<input type="hidden" name="amount"   value="'.$amount.'">'."\n";
				print '<input type="hidden" name="total"    value="'.$obj->total.'">'."\n";
				print '<input type="hidden" name="remise"   value="'.$obj->remise.'">'."\n";
				print '<input type="hidden" name="remise_percent"   value="'.$obj->remise_percent.'">'."\n";
				print '<input type="hidden" name="tva"      value="'.$obj->tva.'">'."\n";
				print '<input type="hidden" name="propalid" value="'.$propalid.'">';

				print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="2">'.$obj->ref.'</td></tr>';
				print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($amount).'</td></tr>';
				print '<tr><td>'.$langs->trans('VAT').'</td><td colspan="2">'.price($obj->tva).'</td></tr>';
				print '<tr><td>'.$langs->trans('TotalTTC').'</td><td colspan="2">'.price($obj->total).'</td></tr>';
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
						print $html->select_produits('','idprod'.$i,'',$conf->product->limit_size,$soc->price_level);
						else
						print $html->select_produits('','idprod'.$i,'',$conf->product->limit_size);
						print '</td>';
						print '<td><input type="text" size="3" name="qty'.$i.'" value="1"></td>';
						print '<td><input type="text" size="3" name="remise_percent'.$i.'" value="'.$soc->remise_client.'">%</td></tr>';
					}

					print '</table>';
					print '</td></tr>';
				}
			}

			/*
			 *
			 */
			print '<tr><td colspan="3" align="center"><input type="submit" class="button" value="'.$langs->trans('CreateDraft').'"></td></tr>';
			print '</table>';

			print '</form>';

			if ($propalid)
			{
				/*
				 * Produits
				 */
				print_titre($langs->trans('Products'));
				print '<table class="noborder" width="100%">';
				print '<tr class="liste_titre"><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Product').'</td>';
				print '<td align="right">'.$langs->trans('Price').'</td>';
				print '<td align="center">'.$langs->trans('Qty').'</td>';
				print '<td align="center">'.$langs->trans('ReductionShort').'</td>';
				print '</tr>';

				$var=false;

				$sql = 'SELECT pt.rowid, p.label as product, p.ref, pt.price, pt.qty, p.rowid as prodid, pt.remise_percent';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt, '.MAIN_DB_PREFIX.'product as p WHERE pt.fk_product = p.rowid AND pt.fk_propal = '.$propalid;
				$sql .= ' ORDER BY pt.rowid ASC';
				$result = $db->query($sql);
				if ($result)
				{
					$num = $db->num_rows($result);
					$i = 0;
					while ($i < $num)
					{
						$objp = $db->fetch_object($result);
						$var=!$var;
						print '<tr '.$bc[$var].'><td>['.$objp->ref.']</td>';
						print '<td>'.img_object($langs->trans('ShowProduct'),'product').' '.$objp->product.'</td>';
						print '<td align="right">'.price($objp->price).'</td>';
						print '<td align="center">'.$objp->qty.'</td></tr>';
						print '<td align="center">'.$objp->remise_percent.'%</td>';
						$i++;
					}
				}

				$sql = 'SELECT pt.rowid, pt.description as product,  pt.price, pt.qty, pt.remise_percent';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt  WHERE  pt.fk_propal = '.$propalid.' AND pt.fk_product = 0';
				$sql .= ' ORDER BY pt.rowid ASC';
				$resql=$db->query($sql);
				if ($resql)
				{
					$num = $db->num_rows($resql);
					$i = 0;
					while ($i < $num)
					{
						$objp = $db->fetch_object($resql);
						$var=!$var;
						print '<tr '.$bc[$var].'><td>&nbsp;</td>';
						print '<td>'.img_object($langs->trans('ShowProduct'),'product').' '.$objp->product.'</td>';
						print '<td align="right">'.price($objp->price).'</td>';
						print '<td align="center">'.$objp->qty.'</td></tr>';
						print '<td align="center">'.$objp->remise_percent.'%</td>';
						$i++;
					}
				}
				else
				{
					dol_print_error($db);
				}

				print '</table>';
			}
		}
	}
	else
	{
		dol_print_error($db);
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

	$id = $comid;
	$ref= $_GET['ref'];

	if ($id > 0 || ! empty($ref))
	{
		if ($mesg) print $mesg.'<br>';

		$product_static=new Product($db);

		$result=$object->fetch($comid,$ref);
		if ($result > 0)
		{
			$soc = new Societe($db);
			$soc->fetch($object->socid);

			$author = new User($db);
			$author->fetch($object->user_author_id);

			$head = commande_prepare_head($object);
			dol_fiche_head($head, 'order', $langs->trans("CustomerOrder"), 0, 'order');

			/*
			 * Confirmation de la suppression de la commande
			 */
			if ($_GET['action'] == 'delete')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la validation
			 */
			if ($_GET['action'] == 'validate')
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
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateOrder'), $text, 'confirm_validate', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la cloture
			 */
			if ($_GET['action'] == 'close')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_close', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de l'annulation
			 */
			if ($_GET['action'] == 'cancel')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('Cancel'), $langs->trans('ConfirmCancelOrder'), 'confirm_cancel', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la suppression d'une ligne produit
			 */
			if ($_GET['action'] == 'ask_deleteline')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * TODO ajout temporaire pour test en attendant la migration en template
			 */
			if ($_GET['action'] == 'ask_deletemilestone')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteMilestone'), $langs->trans('ConfirmDeleteMilestone'), 'confirm_deletemilestone','',0,1);
				if ($ret == 'html') print '<br>';
			}

			// Clone confirmation
			if ($_GET["action"] == 'clone')
			{
				// Create an array for form
				$formquestion=array(
				//'text' => $langs->trans("ConfirmClone"),
				//array('type' => 'checkbox', 'name' => 'clone_content',   'label' => $langs->trans("CloneMainAttributes"),   'value' => 1)
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('CloneOrder'),$langs->trans('ConfirmCloneOrder',$object->ref),'confirm_clone',$formquestion,'yes',1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 *   Commande
			 */
			$nbrow=7;
			if ($conf->projet->enabled) $nbrow++;

			//Local taxes
			if ($mysoc->pays_code=='ES')
			{
				if($mysoc->localtax1_assuj=="1") $nbrow++;
				if($mysoc->localtax2_assuj=="1") $nbrow++;
			}

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
			print '<td colspan="3">';
			print $html->showrefnav($object,'ref','',1,'ref','ref');
			print '</td>';
			print '</tr>';

			// Ref commande client
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
			print $langs->trans('RefCustomer').'</td><td align="left">';
			print '</td>';
			if ($_GET['action'] != 'refcustomer' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refcustomer&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($user->rights->commande->creer && $_GET['action'] == 'refcustomer')
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
					print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->transnoentities("Currency".$conf->monnaie));
				}
				else
				{
					// Remise dispo de type non avoir
					$filter='fk_facture_source IS NULL';
					print '<br>';
					$html->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$object->id,0,'remise_id',$soc->id,$absolute_discount,$filter);
				}
			}
			if ($absolute_creditnote)
			{
				print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
			}
			if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
			print '</td></tr>';

			// Date
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('Date');
			print '</td>';

			if ($_GET['action'] != 'editdate' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editdate')
			{
				print '<form name="setdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate">';
				$html->select_date($object->date,'order_','','','',"setdate");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $object->date ? dol_print_date($object->date,'daytext') : '&nbsp;';
			}
			print '</td>';

			print '<td width="50%">'.$langs->trans('Source').' : '.$object->getLabelSource();
			if ($object->source == 0 && $conf->propal->enabled && $object->propale_id)
			{
				// Si source = propal
				$propal = new Propal($db);
				$propal->fetch($object->propale_id);
				print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?id='.$propal->id.'">'.$propal->ref.'</a>';
			}
			print '</td>';
			print '</tr>';

			// Delivery date planed
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateDeliveryPlanned');
			print '</td>';

			if ($_GET['action'] != 'editdate_livraison') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editdate_livraison')
			{
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$html->select_date($object->date_livraison?$object->date_livraison:-1,'liv_','','','',"setdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $object->date_livraison ? dol_print_date($object->date_livraison,'daytext') : '&nbsp;';
			}
			print '</td>';
			print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
			print nl2br($object->note_public);
			print '</td>';
			print '</tr>';

			// Delivery address
			if ($conf->global->COMMANDE_ADD_DELIVERY_ADDRESS)
			{
				print '<tr><td height="10">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('DeliveryAddress');
				print '</td>';

				if ($_GET['action'] != 'editdelivery_adress' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$object->socid.'&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';

				if ($_GET['action'] == 'editdelivery_adress')
				{
					$html->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$_GET['socid'],'fk_address','commande',$object->id);
				}
				else
				{
					$html->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,$_GET['socid'],'none','commande',$object->id);
				}
				print '</td></tr>';
			}

			// Terms of payment
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentConditionsShort');
			print '</td>';

			if ($_GET['action'] != 'editconditions' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editconditions')
			{
				$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
			}
			else
			{
				$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none');
			}
			print '</td>';

			print '</tr>';

			// Mode of payment
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($_GET['action'] != 'editmode' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editmode')
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
			}
			else
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'none');
			}
			print '</td></tr>';

			// Project
			if ($conf->projet->enabled)
			{
				$langs->load('projects');
				print '<tr><td height="10">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($_GET['action'] != 'classer') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$object->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';
				//print "$object->id, $object->socid, $object->fk_project";
				if ($_GET['action'] == 'classer')
				{
					$html->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'projectid');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none');
				}
				print '</td></tr>';
			}

			// Lignes de 3 colonnes

			// Total HT
			print '<tr><td>'.$langs->trans('AmountHT').'</td>';
			print '<td align="right"><b>'.price($object->total_ht).'</b></td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Total TVA
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($object->total_tva).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Amount Local Taxes
			if ($mysoc->pays_code=='ES')
			{
				if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
				{
					print '<tr><td>'.$langs->transcountry("AmountLT1",$mysoc->pays_code).'</td>';
					print '<td align="right">'.price($object->total_localtax1).'</td>';
					print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
				}
				if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
				{
					print '<tr><td>'.$langs->transcountry("AmountLT2",$mysoc->pays_code).'</td>';
					print '<td align="right">'.price($object->total_localtax2).'</td>';
					print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
				}
			}

			// Total TTC
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($object->total_ttc).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

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

            if ($conf->use_javascript_ajax && $object->statut == 0)
            {
                include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php');
            }

            print '<table id="tablelines" class="noborder" width="100%">';

            if (!empty($object->lines))
            {
                $object->print_title_list();
                $object->printLinesList(1,$mysoc,$soc);
            }

			$numlines=sizeof($object->lines);

			/*
			 * Form to add new line
			 */
			if ($object->statut == 0 && $user->rights->commande->creer)
			{
				if ($_GET['action'] != 'editline')
				{
					$var=true;

					$object->showAddFreeProductForm(1,$mysoc,$soc);

					// Add predefined products/services
					if ($conf->product->enabled || $conf->service->enabled)
					{
						$var=!$var;
						$object->showAddPredefinedProductForm(1,$mysoc,$soc);
					}

					// Hook of thirdparty module
					if (! empty($object->hooks))
					{
						foreach($object->hooks as $module)
						{
							$var=!$var;
							$module->formAddObject($object);
						}
					}
				}
			}
			print '</table>';
			print '</div>';


			/*
			 * Boutons actions
			 */
			if ($_GET['action'] != 'presend')
			{
				if ($user->societe_id == 0 && $_GET['action'] <> 'editline')
				{
					print '<div class="tabsAction">';

					// Valid
					if ($object->statut == 0 && $object->total_ttc >= 0 && $numlines > 0 && $user->rights->commande->valider)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate"';
						print '>'.$langs->trans('Validate').'</a>';
					}

					// Edit
					if ($object->statut == 1)
					{
						if ($user->rights->commande->creer)
						{
							print '<a class="butAction" href="fiche.php?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
						}
					}

					// Send
					if ($object->statut > 0)
					{
						if ($user->rights->commande->envoyer)
						{
							$comref = dol_sanitizeFileName($object->ref);
							$file = $conf->commande->dir_output . '/'.$comref.'/'.$comref.'.pdf';
							if (file_exists($file))
							{
								print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
							}
						}
					}

					// Ship
					if ($conf->expedition->enabled)
					{
						if ($object->statut > 0 && $object->statut < 3 && $object->getNbOfProductsLines() > 0)
						{
							if ($user->rights->expedition->creer)
							{
								// Chargement des permissions
								/*$error = $user->load_entrepots();	deprecated
								if (sizeof($user->entrepots) === 1)
								{
									print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$comid.'&amp;action=create&amp;commande_id='.$comid.'&entrepot_id='.$user->entrepots[0]['id'].'">';
									print $langs->trans('ShipProduct').'</a>';

								}
								else
								{*/
									print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/shipment.php?id='.$object->id.'">'.$langs->trans('ShipProduct').'</a>';
								//}
							}
							else
							{
								print '<a class="butActionRefused" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">'.$langs->trans('ShipProduct').'</a>';
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
					if ($object->statut == 1 || $object->statut == 2)
					{
						if ($user->rights->commande->cloturer)
						{
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=close"';
							print '>'.$langs->trans('Close').'</a>';
						}
					}

					// Clone
					if ($user->rights->commande->creer)
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a>';
					}

					// Cancel order
					if ($object->statut == 1)
					{
						$nb_expedition = $object->nb_expedition();
						if ($user->rights->commande->annuler && $nb_expedition == 0)
						{
							print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=cancel"';
							print '>'.$langs->trans('Cancel').'</a>';
						}
					}

					// Delete order
					if ($user->rights->commande->supprimer)
					{
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
						print '>'.$langs->trans('Delete').'</a>';
					}

					print '</div>';
				}
				print '<br>';
			}


			if ($_GET['action'] != 'presend')
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

				$somethingshown=$formfile->show_documents('commande',$comref,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'','','',$soc->default_lang);

				/*
				 * Linked object block
				 */
				$object->load_object_linked($object->id,$object->element);

				foreach($object->linked_object as $linked_object => $linked_objectid)
				{
					if($conf->$linked_object->enabled && $linked_object != $object->element)
					{
						$somethingshown=$object->showLinkedObjectBlock($linked_object,$linked_objectid,$somethingshown);
					}
				}

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
			if ($_GET['action'] == 'presend')
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
					$formmail->add_attached_files($file,$ref.'.pdf','application/pdf');
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

llxFooter('$Date$ - $Revision$');
?>
