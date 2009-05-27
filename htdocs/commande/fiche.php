<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
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
 *	\brief      Fiche commande client
 *	\version    $Id$
 */

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/html.formorder.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/lib/project.lib.php');
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');

if (!$user->rights->commande->lire) accessforbidden();

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');

// Security check
$socid=0;
$comid = isset($_GET["id"])?$_GET["id"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'commande',$comid,'');

$usehm=$conf->global->MAIN_USE_HOURMIN_IN_DATE_RANGE;

// Recuperation de l'id de projet
$projetid = 0;
if ($_GET["projetid"])
{
	$projetid = $_GET["projetid"];
}


/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

// Action clone object
if ($_REQUEST["action"] == 'confirm_clone' && $_REQUEST['confirm'] == 'yes')
{
	if (1==0 && empty($_REQUEST["clone_content"]) && empty($_REQUEST["clone_receivers"]))
	{
		$mesg='<div class="error">'.$langs->trans("NoCloneOptionsSpecified").'</div>';
	}
	else
	{
		$object=new Commande($db);
		$result=$object->createFromClone($_REQUEST['id']);
		if ($result > 0)
		{
			header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
			exit;
		}
		else
		{
			$mesg=$object->error;
			$_GET['action']='';
			$_GET['id']=$_REQUEST['id'];
		}
	}
}

// Suppression de la commande
if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->commande->supprimer )
	{
		$commande = new Commande($db);
		$commande->fetch($_GET['id']);
		$commande->delete($user);
		Header('Location: index.php');
		exit;
	}
}

/*
 *  Remove a product line
 */
if ($_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes')
{
	if ($user->rights->commande->creer)
	{
		$commande = new Commande($db);
		$commande->fetch($_GET['id']);
		$result = $commande->delete_line($_GET['lineid']);
		if ($result > 0)
		{
			$outputlangs = $langs;
			if (! empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			commande_pdf_create($db, $_GET['id'], $commande->modelpdf, $outputlangs);
		}
		else
		{
			print $commande->error;
		}
	}
	Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET['id']);
	exit;
}

// Categorisation dans projet
if ($_POST['action'] == 'classin')
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->setProject($_POST['projetid']);
}

// Ajout commande
if ($_POST['action'] == 'add' && $user->rights->commande->creer)
{
	$datecommande='';
	$datecommande  = dol_mktime(12, 0, 0, $_POST['remonth'],  $_POST['reday'],  $_POST['reyear']);
	$datelivraison = dol_mktime(12, 0, 0, $_POST['liv_month'],$_POST['liv_day'],$_POST['liv_year']);

	$commande = new Commande($db);
	$commande->socid=$_POST['socid'];
	$commande->fetch_client();

	$db->begin();

	$commande->date_commande        = $datecommande;
	$commande->note                 = $_POST['note'];
	$commande->note_public          = $_POST['note_public'];
	$commande->source               = $_POST['source_id'];
	$commande->projetid             = $_POST['projetid'];
	$commande->ref_client           = $_POST['ref_client'];
	$commande->modelpdf             = $_POST['model'];
	$commande->cond_reglement_id    = $_POST['cond_reglement_id'];
	$commande->mode_reglement_id    = $_POST['mode_reglement_id'];
	$commande->date_livraison       = $datelivraison;
	$commande->adresse_livraison_id = $_POST['adresse_livraison_id'];
	$commande->contactid            = $_POST['contactidp'];

	$NBLINES=8;
	for ($i = 1 ; $i <= $NBLINES ; $i++)
	{
		if ($_POST['idprod'.$i])
		{
			$xid = 'idprod'.$i;
			$xqty = 'qty'.$i;
			$xremise = 'remise_percent'.$i;
			$commande->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
		}
	}

	$commande_id = $commande->create($user);

	if ($commande_id > 0)
	{
		// Insertion contact par defaut si defini
		if ($_POST["contactidp"])
		{
			$result=$commande->add_contact($_POST["contactidp"],'CUSTOMER','external');

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

		$_GET['id'] = $commande->id;
		$action = '';
	}

	// Fin creation facture, on l'affiche
	if ($commande_id > 0 && ! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
		$_GET["action"]='create';
		$_GET['socid']=$_POST['socid'];
		if (! $mesg) $mesg='<div class="error">'.$commande->error.'</div>';
	}

}

// Positionne ref commande client
if ($_POST['action'] == 'set_ref_client' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->set_ref_client($user, $_POST['ref_client']);
}

if ($_POST['action'] == 'setremise' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->set_remise($user, $_POST['remise']);
}

if ($_POST['action'] == "setabsolutediscount" && $user->rights->commande->creer)
{
	if ($_POST["remise_id"])
	{
		$com = new Commande($db);
		$com->id=$_GET['id'];
		$ret=$com->fetch($_GET['id']);
		if ($ret > 0)
		{
	  $com->insert_discount($_POST["remise_id"]);
		}
		else
		{
	  dol_print_error($db,$com->error);
		}
	}
}

if ($_POST['action'] == 'setdate_livraison' && $user->rights->commande->creer)
{
	//print "x ".$_POST['liv_month'].", ".$_POST['liv_day'].", ".$_POST['liv_year'];
	$datelivraison=dol_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->set_date_livraison($user,$datelivraison);
	if ($result < 0)
	{
		$mesg='<div class="error">'.$commande->error.'</div>';
	}
}

if ($_POST['action'] == 'setdeliveryadress' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->set_adresse_livraison($user,$_POST['adresse_livraison_id']);
}

if ($_POST['action'] == 'setmode' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->mode_reglement($_POST['mode_reglement_id']);
	if ($result < 0) dol_print_error($db,$commande->error);
}

if ($_POST['action'] == 'setconditions' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result=$commande->cond_reglement($_POST['cond_reglement_id']);
	if ($result < 0) dol_print_error($db,$commande->error);
}

if ($_REQUEST['action'] == 'setremisepercent' && $user->rights->facture->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_REQUEST['id']);
	$result = $commande->set_remise($user, $_POST['remise_percent']);
	$_GET['id']=$_REQUEST['id'];
}

if ($_REQUEST['action'] == 'setremiseabsolue' && $user->rights->facture->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_REQUEST['id']);
	$result = $commande->set_remise_absolue($user, $_POST['remise_absolue']);
	$_GET['id']=$_REQUEST['id'];
}

/*
 *  Ajout d'une ligne produit dans la commande
 */
if ($_POST['action'] == 'addline' && $user->rights->commande->creer)
{
	$result=0;

	if (empty($_POST['idprod']) && $_POST["type"] < 0)
	{
		$fac->error = $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")) ;
		$result = -1 ;
	}
	if (empty($_POST['idprod']) && empty($_POST["pu"]))
	{
		$fac->error = $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")) ;
		$result = -1 ;
	}

	if ($_POST['qty'] && (($_POST['pu'] != '' && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprod']))
	{
		$commande = new Commande($db);
		$ret=$commande->fetch($_POST['id']);
		if ($ret < 0)
		{
			dol_print_error($db,$commande->error);
			exit;
		}
		$ret=$commande->fetch_client();

		// Clean parameters
		$suffixe = $_POST['idprod'] ? '_prod' : '';
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

			$tva_tx = get_default_tva($mysoc,$commande->client,$prod->tva_tx);

			// multiprix
			if ($conf->global->PRODUIT_MULTIPRICES)
			{
				$pu_ht = $prod->multiprices[$commande->client->price_level];
				$pu_ttc = $prod->multiprices_ttc[$commande->client->price_level];
				$price_base_type = $prod->multiprices_base_type[$commande->client->price_level];
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
			$pu_ht=$_POST['pu'];
			$tva_tx=eregi_replace('\*','',$_POST['tva_tx']);
			$tva_npr=eregi('\*',$_POST['tva_tx'])?1:0;
			$desc=$_POST['dp_desc'];
			$type=$_POST["type"];
		}
		$desc=dol_htmlcleanlastbr($desc);

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;

		if ($result >= 0)
		{
			if($prod->price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($prod->price_min)))
			{
				$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($prod->price_min,'MU').' '.$langs->trans("Currency".$conf->monnaie)).'</div>' ;
			}
			else
			{
				// Insert line
				$result = $commande->addline(
				$_POST['id'],
				$desc,
				$pu_ht,
				$_POST['qty'],
				$tva_tx,
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
					$outputlangs = $langs;
					if (! empty($_REQUEST['lang_id']))
					{
						$outputlangs = new Translate("",$conf);
						$outputlangs->setDefaultLang($_REQUEST['lang_id']);
					}
					commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);

					unset($_POST['qty']);
					unset($_POST['type']);
					unset($_POST['idprod']);
					unset($_POST['remmise_percent']);
					unset($_POST['dp_desc']);
					unset($_POST['np_desc']);
					unset($_POST['pu']);
					unset($_POST['tva_tx']);
				}
				else
				{
					$mesg='<div class="error">'.$commande->error.'</div>';
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
	$commande = new Commande($db,'',$_POST['id']);
	if (! $commande->fetch($_POST['id']) > 0) dol_print_error($db);

	// Clean parameters
	$date_start='';
	$date_end='';
	$date_start=dol_mktime(0, 0, 0, $_POST['date_start'.$suffixe.'month'], $_POST['date_start'.$suffixe.'day'], $_POST['date_start'.$suffixe.'year']);
	$date_end=dol_mktime(0, 0, 0, $_POST['date_end'.$suffixe.'month'], $_POST['date_end'.$suffixe.'day'], $_POST['date_end'.$suffixe.'year']);
	$description=dol_htmlcleanlastbr($_POST['eldesc']);

	// Define info_bits
	$info_bits=0;
	if (eregi('\*',$_POST['tva_tx'])) $info_bits |= 0x01;

	// Define vat_rate
	$vat_rate=$_POST['tva_tx'];
	$vat_rate=eregi_replace('\*','',$vat_rate);

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
	if ($product->price_min && ($_POST['productid']!='') && ( price2num($_POST['pu'])*(1-price2num($_POST['elremise_percent'])/100) < price2num($product->price_min)))
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
		$result = $commande->updateline($_POST['elrowid'],
		$description,
		$_POST['pu'],
		$_POST['qty'],
		$_POST['elremise_percent'],
		$vat_rate,
		'HT',
		$info_bits,
		$date_start,
		$date_end,
		$type
		);

		if ($result >= 0)
		{
			$outputlangs = $langs;
			if (! empty($_REQUEST['lang_id']))
			{
				$outputlangs = new Translate("",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
		}
		else
		{
			dol_print_error($db,$commande->error);
			exit;
		}
	}
	$_GET['id']=$_POST['id'];   // Pour reaffichage de la fiche en cours d'edition
}

if ($_POST['action'] == 'updateligne' && $user->rights->commande->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
	Header('Location: fiche.php?id='.$_POST['id']);   // Pour reaffichage de la fiche en cours d'edition
	exit;
}

if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes' && $user->rights->commande->valider)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);

	$result=$commande->valid($user);
	if ($result	>= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	}
}

if ($_REQUEST['action'] == 'confirm_close' && $_REQUEST['confirm'] == 'yes' && $user->rights->commande->creer)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result = $commande->cloture($user);
}

if ($_REQUEST['action'] == 'confirm_cancel' && $_REQUEST['confirm'] == 'yes' && $user->rights->commande->valider)
{
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$result = $commande->cancel($user);
}

if ($_GET['action'] == 'modif' && $user->rights->commande->creer)
{
	/*
	 *  Repasse la commande en mode brouillon
	 */
	$commande = new Commande($db);
	$commande->fetch($_GET['id']);
	$commande->set_draft($user);

	$result = $commande->set_draft($user);
	if ($result	>= 0)
	{
		$outputlangs = $langs;
		if (! empty($_REQUEST['lang_id']))
		{
			$outputlangs = new Translate("",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
		commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	}
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->commande->creer)
{
	$commande = new Commande($db,'',$_GET['id']);
	$commande->fetch($_GET['id']);
	$commande->line_up($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}

if ($_GET['action'] == 'down' && $user->rights->commande->creer)
{
	$commande = new Commande($db,'',$_GET['id']);
	$commande->fetch($_GET['id']);
	$commande->line_down($_GET['rowid']);

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
	exit;
}

if ($_REQUEST['action'] == 'builddoc')	// In get or post
{
	/*
	 * Generate order document
	 * define into /includes/modules/commande/modules_commande.php
	 */

	// Sauvegarde le dernier modele choisi pour generer un document
	$commande = new Commande($db, 0, $_REQUEST['id']);
	$result=$commande->fetch($_REQUEST['id']);
	if ($_REQUEST['model'])
	{
		$commande->setDocModel($user, $_REQUEST['model']);
	}

	$outputlangs = $langs;
	if (! empty($_REQUEST['lang_id']))
	{
		$outputlangs = new Translate("",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$commande->id.'#builddoc');
		exit;
	}
}

// Efface les fichiers
if ($_REQUEST['action'] == 'remove_file')
{
	$com = new Commande($db);

	if ($com->fetch($id))
	{
		$upload_dir = $conf->commande->dir_output . "/";
		$file = $upload_dir . '/' . urldecode($_GET['file']);
		dol_delete_file($file);
		$mesg = '<div class="ok">'.$langs->trans("FileWasRemoved").'</div>';
	}
}

/*
 * Add file
 */
if ($_POST['addfile'])
{
	// Set tmp user directory
	$vardir=$conf->users->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	if (! empty($_FILES['addedfile']['tmp_name']))
	{
		if (! is_dir($upload_dir)) create_exdir($upload_dir);

		if (is_dir($upload_dir))
		{
			if (dol_move_uploaded_file($_FILES['addedfile']['tmp_name'], $upload_dir . "/" . $_FILES['addedfile']['name'],0) > 0)
			{
				$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
				//print_r($_FILES);

				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->add_attached_files($upload_dir . "/" . $_FILES['addedfile']['name'],$_FILES['addedfile']['name'],$_FILES['addedfile']['type']);
			}
			else
			{
				// Echec transfert (fichier depassant la limite ?)
				$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
				// print_r($_FILES);
			}
		}
	}
	$_GET["action"]='presend';
}

/*
 * Send mail
 */
if ($_POST['action'] == 'send' && ! $_POST['addfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$commande= new Commande($db);
	$result=$commande->fetch($_POST['orderid']);
	if ($result)
	{
		$ref = dol_sanitizeFileName($commande->ref);
		$file = $conf->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';

		if (is_readable($file))
		{
			$commande->fetch_client();

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
					$sendto = $commande->client->email;
					$sendtoid = 0;
				}
				else	// Id du contact
				{
					$sendto = $commande->client->contact_get_email($_POST['receiver']);
					$sendtoid = $_POST['receiver'];
				}
			}

			if (strlen($sendto))
			{
				$langs->load("commercial");

				$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				$replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
				$message = $_POST['message'];
				$sendtocc = $_POST['sendtocc'];
				$deliveryreceipt = $_POST['deliveryreceipt'];

				if ($_POST['action'] == 'send')
				{
					if (strlen($_POST['subject'])) $subject=$_POST['subject'];
					else $subject = $langs->transnoentities('Order').' '.$commande->ref;
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
				include_once('../html.formmail.class.php');
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
						$commande->sendtoid=$sendtoid;
						$commande->actiontypecode=$actiontypecode;
						$commande->actionmsg = $actionmsg;
						$commande->actionmsg2= $actionmsg2;
						$commande->orderrowid=$commande->id;

						// Appel des triggers
						include_once(DOL_DOCUMENT_ROOT . "/interfaces.class.php");
						$interface=new Interfaces($db);
						$result=$interface->run_triggers('ORDER_SENTBYMAIL',$commande,$user,$langs,$conf);
						if ($result < 0) { $error++; $this->errors=$interface->errors; }
						// Fin appel triggers

						if ($error)
						{
							dol_print_error($db);
						}
						else
						{
							// Renvoie sur la fiche
							Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&msg='.urlencode($mesg));
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

llxHeader('',$langs->trans('Order'),'Commande');

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

	$new_commande = new Commande($db);

	if ($propalid)
	{
		$sql = 'SELECT s.nom, s.prefix_comm, s.rowid';
		$sql.= ', p.price, p.remise, p.remise_percent, p.tva, p.total, p.ref, p.fk_cond_reglement, p.fk_mode_reglement';
		$sql.= ', '.$db->pdate('p.datep').' as dp';
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

			print '<form name="crea_commande" action="fiche.php" method="post">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="action" value="add">';
			print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
			print '<input type="hidden" name="remise_percent" value="'.$soc->remise_client.'">';
			print '<input name="facnumber" type="hidden" value="provisoire">';

			print '<table class="border" width="100%">';

			// Reference
			print '<tr><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td></tr>';

			// Reference client
			print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
			print '<input type="text" name="ref_client" value=""></td>';
			print '</tr>';

			// Client
			print '<tr><td>'.$langs->trans('Customer').'</td><td>'.$soc->getNomUrl(1).'</td></tr>';

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
			$absolute_discount=$soc->getAvailableDiscounts();
			print '. ';
			if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->monnaie));
			else print $langs->trans("CompanyHasNoAbsoluteDiscount");
			print '.';
			print '</td></tr>';

			// Date
			print '<tr><td>'.$langs->trans('Date').'</td><td>';
			$html->select_date('','re','','','',"crea_commande");
			print '</td></tr>';

			// Date de livraison
			print "<tr><td>".$langs->trans("DeliveryDate")."</td><td>";
			if ($conf->global->DATE_LIVRAISON_WEEK_DELAY)
			{
				$tmpdte = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
				$html->select_date($tmpdte,'liv_','','',1,"crea_commande");
			}
			else
			{
				$dateorder=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
				$html->select_date($dateorder,'liv_','','',1,"crea_commande");
			}
			print "</td></tr>";

			// Adresse de livraison
			print '<tr><td nowrap="nowrap">'.$langs->trans('DeliveryAddress').'</td><td>';
			$numaddress = $html->select_adresse_livraison($soc->adresse_livraison_id, $_GET['socid'],'adresse_livraison_id',1);

			if ($numaddress==0)
			{
				print ' &nbsp; <a href="../comm/adresse_livraison.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddAddress").'</a>';
			}

			print '</td></tr>';

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
				$projetid=$_POST["projetid"]?$_POST["projetid"]:$commande->projetid;
				print '<tr><td>'.$langs->trans('Project').'</td><td colspan="2">';
				$numprojet=select_projects($soc->id,$projetid,'projetid');
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
			$html->select_array('model',$liste,$conf->global->COMMANDE_ADDON_PDF);
			print "</td></tr>";

			// Note publique
			print '<tr>';
			print '<td class="border" valign="top">'.$langs->trans('NotePublic').'</td>';
			print '<td valign="top" colspan="2">';
			print '<textarea name="note_public" wrap="soft" cols="70" rows="'.ROWS_3.'">';
			print '</textarea></td></tr>';

			// Note priv�e
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
						print $html->select_produits('','idprod'.$i,'',$conf->produit->limit_size,$soc->price_level);
						else
						print $html->select_produits('','idprod'.$i,'',$conf->produit->limit_size);
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
				if ($db->query($sql))
				{
					$num = $db->num_rows();
					$i = 0;
					while ($i < $num)
					{
						$objp = $db->fetch_object();
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
	$now=gmmktime();

	$id = $_GET['id'];
	$ref= $_GET['ref'];
	if ($id > 0 || ! empty($ref))
	{
		if ($mesg) print $mesg.'<br>';

		$product_static=new Product($db);

		$commande = new Commande($db);
		$result=$commande->fetch($_GET['id'],$_GET['ref']);
		if ($result > 0)
		{
			$soc = new Societe($db);
			$soc->fetch($commande->socid);

			$author = new User($db);
			$author->id = $commande->user_author_id;
			$author->fetch();

			$head = commande_prepare_head($commande);
			dol_fiche_head($head, 'order', $langs->trans("CustomerOrder"));

			/*
			 * Confirmation de la suppression de la commande
			 */
			if ($_GET['action'] == 'delete')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la validation
			 */
			if ($_GET['action'] == 'validate')
			{
				// on verifie si la facture est en numerotation provisoire
				$ref = substr($commande->ref, 1, 4);
				if ($ref == 'PROV')
				{
					$num = $commande->getNextNumRef($soc);
				}
				else
				{
					$num = $commande->ref;
				}

				$text=$langs->trans('ConfirmValidateOrder',$num);
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('ValidateOrder'), $text, 'confirm_validate', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la cloture
			 */
			if ($_GET['action'] == 'close')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_close', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de l'annulation
			 */
			if ($_GET['action'] == 'cancel')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('Cancel'), $langs->trans('ConfirmCancelOrder'), 'confirm_cancel', '', 0, 1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 * Confirmation de la suppression d'une ligne produit
			 */
			if ($_GET['action'] == 'ask_deleteline')
			{
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id.'&lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline', '', 0, 1);
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
				$ret=$html->form_confirm($_SERVER["PHP_SELF"].'?id='.$commande->id,$langs->trans('CloneOrder'),$langs->trans('ConfirmCloneOrder',$commande->ref),'confirm_clone',$formquestion,'yes',1);
				if ($ret == 'html') print '<br>';
			}

			/*
			 *   Commande
			 */
			$nbrow=7;
			if ($conf->projet->enabled) $nbrow++;

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
			print '<td colspan="3">';
			print $html->showrefnav($commande,'ref','',1,'ref','ref');
			print '</td>';
			print '</tr>';

			// Ref commande client
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
			print $langs->trans('RefCustomer').'</td><td align="left">';
			print '</td>';
			if ($_GET['action'] != 'RefCustomerOrder' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=RefCustomerOrder&amp;id='.$commande->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($user->rights->commande->creer && $_GET['action'] == 'RefCustomerOrder')
			{
				print '<form action="fiche.php?id='.$id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="set_ref_client">';
				print '<input type="text" class="flat" size="20" name="ref_client" value="'.$commande->ref_client.'">';
				print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $commande->ref_client;
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
				if ($commande->statut > 0)
				{
					print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->transnoentities("Currency".$conf->monnaie));
				}
				else
				{
					// Remise dispo de type non avoir
					$filter='fk_facture_source IS NULL';
					print '<br>';
					$html->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$commande->id,0,'remise_id',$soc->id,$absolute_discount,$filter);
				}
			}
			if ($absolute_creditnote)
			{
				print $langs->trans("CompanyHasCreditNote",price($absolute_creditnote,1),$langs->transnoentities("Currency".$conf->monnaie)).'. ';
			}
			if (! $absolute_discount && ! $absolute_creditnote) print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
			print '</td></tr>';

			// Date
			print '<tr><td>'.$langs->trans('Date').'</td>';
			print '<td colspan="2">'.dol_print_date($commande->date,'daytext').'</td>';
			print '<td width="50%">'.$langs->trans('Source').' : '.$commande->getLabelSource();
			if ($commande->source == 0 && $conf->propal->enabled && $commande->propale_id)
			{
				// Si source = propal
				$propal = new Propal($db);
				$propal->fetch($commande->propale_id);
				print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
			}
			print '</td>';
			print '</tr>';

			// Delivery date
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DeliveryDate');
			print '</td>';

			if ($_GET['action'] != 'editdate_livraison' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryDate'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editdate_livraison')
			{
				print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$html->select_date($commande->date_livraison,'liv_','','','',"setdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print $commande->date_livraison ? dol_print_date($commande->date_livraison,'daytext') : '&nbsp;';
			}
			print '</td>';
			print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
			print nl2br($commande->note_public);
			print '</td>';
			print '</tr>';

			if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
			{
				// Adresse de livraison
				print '<tr><td height="10">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('DeliveryAddress');
				print '</td>';

				if ($_GET['action'] != 'editdelivery_adress' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$commande->socid.'&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';

				if ($_GET['action'] == 'editdelivery_adress')
				{
					$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'adresse_livraison_id','commande',$commande->id);
				}
				else
				{
					$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->adresse_livraison_id,$_GET['socid'],'none','commande',$commande->id);
				}
				print '</td></tr>';
			}

			// Conditions et modes de reglement
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentConditionsShort');
			print '</td>';

			if ($_GET['action'] != 'editconditions' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editconditions')
			{
				$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'cond_reglement_id');
			}
			else
			{
				$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->cond_reglement_id,'none');
			}
			print '</td>';

			print '</tr>';

			// Payment mode
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($_GET['action'] != 'editmode') print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="2">';
			if ($_GET['action'] == 'editmode')
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'mode_reglement_id');
			}
			else
			{
				$html->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$commande->id,$commande->mode_reglement_id,'none');
			}
			print '</td></tr>';

			// Projet
			if ($conf->projet->enabled)
			{
				$langs->load('projects');
				print '<tr><td height="10">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('Project');
				print '</td>';
				if ($_GET['action'] != 'classer') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';
				if ($_GET['action'] == 'classer')
				{
					$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'projetid');
				}
				else
				{
					$html->form_project($_SERVER['PHP_SELF'].'?id='.$commande->id, $commande->socid, $commande->projet_id, 'none');
				}
				print '</td></tr>';
			}

			// Lignes de 3 colonnes

			// Total HT
			print '<tr><td>'.$langs->trans('AmountHT').'</td>';
			print '<td align="right"><b>'.price($commande->total_ht).'</b></td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Total TVA
			print '<tr><td>'.$langs->trans('AmountVAT').'</td><td align="right">'.price($commande->total_tva).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Total TTC
			print '<tr><td>'.$langs->trans('AmountTTC').'</td><td align="right">'.price($commande->total_ttc).'</td>';
			print '<td>'.$langs->trans('Currency'.$conf->monnaie).'</td></tr>';

			// Statut
			print '<tr><td>'.$langs->trans('Status').'</td>';
			print '<td colspan="2">'.$commande->getLibStatut(4).'</td>';
			print '</tr>';

			print '</table><br>';
			print "\n";

			/*
			 * Lines
			 */
			$sql = 'SELECT l.rowid, l.fk_product, l.product_type, l.description, l.price, l.qty, l.tva_tx, ';
			$sql.= ' l.fk_remise_except, l.remise_percent, l.subprice, l.info_bits,';
			$sql.= ' l.total_ht, l.total_tva, l.total_ttc,';
			$sql.= ' '.$db->pdate('l.date_start').' as date_start,';
			$sql.= ' '.$db->pdate('l.date_end').' as date_end,';
			$sql.= ' p.label as product_label, p.ref, p.fk_product_type, p.rowid as prodid, ';
			$sql.= ' p.description as product_desc';
			$sql.= ' FROM '.MAIN_DB_PREFIX.'commandedet as l';
			$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON l.fk_product=p.rowid';
			$sql.= ' WHERE l.fk_commande = '.$commande->id;
			$sql.= ' ORDER BY l.rang ASC, l.rowid';

			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				$i = 0; $total = 0;

				print '<table class="noborder" width="100%">';
				if ($num)
				{
					print '<tr class="liste_titre">';
					print '<td>'.$langs->trans('Description').'</td>';
					print '<td align="right" width="50">'.$langs->trans('VAT').'</td>';
					print '<td align="right" width="80">'.$langs->trans('PriceUHT').'</td>';
					print '<td align="right" width="50">'.$langs->trans('Qty').'</td>';
					print '<td align="right" width="50">'.$langs->trans('ReductionShort').'</td>';
					print '<td align="right" width="50">'.$langs->trans('AmountHT').'</td>';
					print '<td width="48" colspan="3">&nbsp;</td>';
					print "</tr>\n";
				}
				$var=true;
				while ($i < $num)
				{
					$objp = $db->fetch_object($resql);
					$var=!$var;

					// Show product and description
					$type=$objp->product_type?$objp->product_type:$objp->fk_product_type;
					// Try to enhance type detection using date_start and date_end for free lines where type
					// was not saved.
					if (! empty($objp->date_start)) $type=1;
					if (! empty($objp->date_end)) $type=1;

					// Ligne en mode visu
					if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
					{
						print '<tr '.$bc[$var].'>';
						if ($objp->fk_product > 0)
						{
							print '<td>';
							print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne

							// Show product and description
							$product_static->type=$objp->fk_product_type;
							$product_static->id=$objp->fk_product;
							$product_static->ref=$objp->ref;
							$product_static->libelle=$objp->product_label;
							$text=$product_static->getNomUrl(1);
							$text.= ' - '.$objp->product_label;
							$description=($conf->global->PRODUIT_DESC_IN_FORM?'':dol_htmlentitiesbr($objp->description));
							print $html->textwithtooltip($text,$description,3,'','',$i);

							// Show range
							print_date_range($objp->date_start,$objp->date_end);

							// Add description in form
							if ($conf->global->PRODUIT_DESC_IN_FORM)
							{
								print ($objp->description && $objp->description!=$objp->product_label)?'<br>'.dol_htmlentitiesbr($objp->description):'';
							}

							print '</td>';
						}
						else
						{
							print '<td>';
							print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
							if (($objp->info_bits & 2) == 2)
							{
								print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$commande->socid.'">';
								print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
								print '</a>';
								if ($objp->description)
								{
									if ($objp->description == '(CREDIT_NOTE)')
									{
										require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
										$discount=new DiscountAbsolute($db);
										$discount->fetch($objp->fk_remise_except);
										print ' - '.$langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
									}
									else
									{
										print ' - '.nl2br($objp->description);
									}
								}
							}
							else
							{
								if ($type==1) $text = img_object($langs->trans('Service'),'service');
								else $text = img_object($langs->trans('Product'),'product');
								print $text.' '.nl2br($objp->description);

								// Show range
								print_date_range($objp->date_start,$objp->date_end);
							}
							print '</td>';
						}

						print '<td align="right" nowrap="nowrap">'.vatrate($objp->tva_tx,'%',$objp->info_bits).'</td>';
						print '<td align="right" nowrap="nowrap">'.price($objp->subprice).'</td>';
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print $objp->qty;
						}
						else print '&nbsp;';
						print '</td>';
						if ($objp->remise_percent > 0)
						{
							print '<td align="right">'.dol_print_reduction($objp->remise_percent,$langs).'</td>';
						}
						else
						{
							print '<td>&nbsp;</td>';
						}
						print '<td align="right" nowrap="nowrap">'.price($objp->total_ht).'</td>';

						// Icone d'edition et suppression
						if ($commande->statut == 0  && $user->rights->commande->creer)
						{
							print '<td align="center">';
							if (($objp->info_bits & 2) == 2)
							{
								// Ligne remise predefinie, on ne permet pas modif
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
								print img_edit();
								print '</a>';
							}
							print '</td>';
							print '<td align="center">';
							print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=ask_deleteline&amp;lineid='.$objp->rowid.'">';
							print img_delete();
							print '</a></td>';
							if ($num > 1)
							{
								print '<td align="center">';
								if ($i > 0)
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
									print img_up();
									print '</a>';
								}
								if ($i < $num-1)
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
									print img_down();
									print '</a>';
								}
								print '</td>';
							}
						}
						else
						{
							print '<td colspan="3">&nbsp;</td>';
						}
						print '</tr>';
					}

					// Ligne en mode update
					if ($_GET['action'] == 'editline' && $user->rights->commande->creer && $_GET['rowid'] == $objp->rowid)
					{
						print '<form action="'.$_SERVER["PHP_SELF"].'#'.$objp->rowid.'" method="post">';
						print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						print '<input type="hidden" name="action" value="updateligne">';
						print '<input type="hidden" name="id" value="'.$id.'">';
						print '<input type="hidden" name="elrowid" value="'.$_GET['rowid'].'">';
						print '<tr '.$bc[$var].'>';
						print '<td>';
						print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne


						// Show product and description
						if ($objp->fk_product > 0)
						{
							print '<input type="hidden" name="productid" value="'.$objp->fk_product.'">';
							$product_static->type=$objp->fk_product_type;
							$product_static->id=$objp->fk_product;
							$product_static->ref=$objp->ref;
							$product_static->libelle=$objp->product_label;
							$text=$product_static->getNomUrl(1);
							$text.= ' - '.$objp->product_label;
							print $text;
							print '<br>';
						}
						else
						{
							print $html->select_type_of_lines($objp->product_type,'type',1);
							if ($conf->produit->enabled && $conf->service->enabled) print '<br>';
						}

						// Editor wysiwyg
						if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
						{
							require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
							$doleditor=new DolEditor('eldesc',$objp->description,200,'dolibarr_details');
							$doleditor->Create();
						}
						else
						{
							print '<textarea name="eldesc" class="flat" cols="70" rows="'.ROWS_2.'">';
							//print $objp->description;
							print dol_htmlentitiesbr_decode($objp->description);
							print '</textarea>';
						}
						print '</td>';
						print '<td align="right">';
						if($soc->tva_assuj == "0")
						print '<input type="hidden" name="tva_tx" value="0">0';
						else
						print $html->select_tva('tva_tx',$objp->tva_tx,$mysoc,$soc);
						print '</td>';
						print '<td align="right"><input size="5" type="text" class="flat" name="pu" value="'.price($objp->subprice,0,'',0).'"></td>';
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print '<input size="2" type="text" class="flat" name="qty" value="'.$objp->qty.'">';
						}
						else print '&nbsp;';
						print '</td>';
						print '<td align="right" nowrap="nowrap">';
						if (($objp->info_bits & 2) != 2)
						{
							print '<input size="1" type="text" class="flat" name="elremise_percent" value="'.$objp->remise_percent.'">%';
						}
						else print '&nbsp;';
						print '</td>';
						print '<td align="center" colspan="4"><input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
						print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'"></td>';
						print '</tr>';

						// Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
						// Start and end dates selector
						print '<tr '.$bc[$var].'>';
						print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
						print $html->select_date($objp->date_start,'date_start',$usehm,$usehm,$objp->date_start?0:1,"updateligne");
						print ' '.$langs->trans('to').' ';
						print $html->select_date($objp->date_end,'date_end',$usehm,$usehm,$objp->date_end?0:1,"updateligne");
						print '</td>';
						print '</tr>';

						print '</form>';
					}

					$total = $total + ($objp->qty * $objp->price);
					$i++;
				}
				$db->free($resql);

				$numlines=$num;
			}
			else
			{
				dol_print_error($db);
			}

			/*
			 * Form to add new line
			 */
			if ($commande->statut == 0 && $user->rights->commande->creer && $_GET["action"] <> 'editline')
			{
				print '<tr class="liste_titre">';
				print '<td>';
				print '<a name="add"></a>'; // ancre
				print $langs->trans('AddNewLine').' - '.$langs->trans("FreeZone").'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
				print '<td colspan="4">&nbsp;</td>';
				print '</tr>';

				// Add free products/services form
				print '<form action="fiche.php?id='.$id.'#add" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="id" value="'.$id.'">';
				print '<input type="hidden" name="action" value="addline">';

				$var=true;
				print '<tr '.$bc[$var].'>';
				print '<td>';

				print $html->select_type_of_lines(isset($_POST["type"])?$_POST["type"]:-1,'type',1);
				if ($conf->produit->enabled && $conf->service->enabled) print '<br>';

				// Editor wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('dp_desc',$_POST["dp_desc"],100,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea class="flat" cols="70" name="dp_desc" rows="'.ROWS_2.'">'.$_POST["dp_desc"].'</textarea>';
				}
				print '</td>';
				print '<td align="right">';
				if($soc->tva_assuj == "0")
				print '<input type="hidden" name="tva_tx" value="0">0';
				else
				print $html->select_tva('tva_tx',$conf->defaulttx,$mysoc,$soc);
				print '</td>';
				print '<td align="right"><input type="text" name="pu" size="5"></td>';
				print '<td align="right"><input type="text" name="qty" value="1" size="2"></td>';
				print '<td align="right" nowrap="nowrap"><input type="text" name="remise_percent" size="1" value="'.$soc->remise_client.'">%</td>';
				print '<td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td>';
				print '</tr>';

				if ($conf->service->enabled)
				{
					// Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
					// Start and end dates selector
					print '<tr '.$bc[$var].'>';
					print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
					print $html->select_date('','date_start',$usehm,$usehm,1,"addline");
					print ' '.$langs->trans('to').' ';
					print $html->select_date('','date_end',$usehm,$usehm,1,"addline");
					print '</td>';
					print '</tr>';
				}
				print '</form>';

				// Ajout de produits/services predefinis
				if ($conf->produit->enabled || $conf->service->enabled)
				{
					print '<tr class="liste_titre">';
					print '<td colspan="3">';
					print $langs->trans("AddNewLine").' - ';
					if ($conf->service->enabled)
					{
						print $langs->trans('RecordedProductsAndServices');
					}
					else
					{
						print $langs->trans('RecordedProducts');
					}
					print '</td>';
					print '<td align="right">'.$langs->trans('Qty').'</td>';
					print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
					print '<td colspan="4">&nbsp;</td>';
					print '</tr>';

					print '<form id="addpredefinedproduct" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'#add" method="post">';
					print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
					print '<input type="hidden" name="id" value="'.$id.'">';
					print '<input type="hidden" name="action" value="addline">';

					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td colspan="3">';
					// multiprix
					if($conf->global->PRODUIT_MULTIPRICES)
					{
						$html->select_produits('','idprod','',$conf->produit->limit_size,$soc->price_level);
					}
					else
					{
						$html->select_produits('','idprod','',$conf->produit->limit_size);
					}

					if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';

			 		// Editor wysiwyg
					if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
					{
						require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
						$doleditor=new DolEditor('np_desc',$_POST["np_desc"],100,'dolibarr_details');
						$doleditor->Create();
					}
					else
					{
						print '<textarea cols="70" name="np_desc" rows="'.ROWS_2.'" class="flat">'.$_POST["np_desc"].'</textarea>';
					}

					print '</td>';
					print '<td align="right"><input type="text" size="2" name="qty" value="1"></td>';
					print '<td align="right" nowrap="nowrap"><input type="text" size="1" name="remise_percent" value="'.$soc->remise_client.'">%</td>';
					print '<td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td>';
					print '</tr>';

					if ($conf->service->enabled)
					{
						// Added by Matelli (See http://matelli.fr/showcases/patchs-dolibarr/add-dates-in-order-lines.html)
						// Start and end dates selector
						print '<tr '.$bc[$var].'>';
						print '<td colspan="9">'.$langs->trans('ServiceLimitedDuration').' '.$langs->trans('From').' ';
						print $html->select_date('','date_start_prod',$usehm,$usehm,1,"addline");
						print ' '.$langs->trans('to').' ';
						print $html->select_date('','date_end_prod',$usehm,$usehm,1,"addline");
						print '</td>';
						print '</tr>';
					}
					print '</form>';
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
					if ($commande->statut == 0 && $commande->total_ttc >= 0 && $numlines > 0 && $user->rights->commande->valider)
					{
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=validate"';
						print '>'.$langs->trans('Validate').'</a>';
					}

					// Edit
					if ($commande->statut == 1)
					{
						if ($user->rights->commande->creer)
						{
							print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
						}
					}

					// Send
					if ($commande->statut > 0)
					{
						if ($user->rights->commande->envoyer)
						{
							$comref = dol_sanitizeFileName($commande->ref);
							$file = $conf->commande->dir_output . '/'.$comref.'/'.$comref.'.pdf';
							if (file_exists($file))
							{
								print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
							}
						}
					}

					// Ship
					if ($commande->statut > 0 && $commande->statut < 3 && $commande->getNbOfProductsLines() > 0)
					{
						if ($user->rights->expedition->creer)
						{
							// Chargement des permissions
							$error = $user->load_entrepots();
							if (sizeof($user->entrepots) === 1)
							{
								print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/fiche.php?id='.$_GET['id'].'&amp;action=create&amp;commande_id='.$_GET["id"].'&entrepot_id='.$user->entrepots[0]['id'].'">';
								print $langs->trans('ShipProduct').'</a>';

							}
							else
							{
								print '<a class="butAction" href="'.DOL_URL_ROOT.'/expedition/commande.php?id='.$_GET['id'].'">'.$langs->trans('ShipProduct').'</a>';
							}
						}
						else
						{
							print '<a class="butActionRefused" href="#">'.$langs->trans('ShipProduct').'</a>';
						}
					}

					// Cloturer
					if ($commande->statut == 1 || $commande->statut == 2)
					{
						if ($user->rights->commande->cloturer)
						{
							print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=close"';
							print '>'.$langs->trans('Close').'</a>';
						}
					}

					// Clone
					if ($user->rights->commande->creer)
					{
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$commande->id.'&amp;action=clone&amp;object=order">'.$langs->trans("ToClone").'</a>';
					}

					// Annuler commande
					if ($commande->statut == 1)
					{
						$nb_expedition = $commande->nb_expedition();
						if ($user->rights->commande->annuler && $nb_expedition == 0)
						{
							print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=cancel"';
							print '>'.$langs->trans('CancelOrder').'</a>';
						}
					}

					// Delete order
					if ($user->rights->commande->supprimer)
					{
						print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'&amp;action=delete"';
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
				$comref = dol_sanitizeFileName($commande->ref);
				$file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
				$relativepath = $comref.'/'.$comref.'.pdf';
				$filedir = $conf->commande->dir_output . '/' . $comref;
				$urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
				$genallowed=$user->rights->commande->creer;
				$delallowed=$user->rights->commande->supprimer;

				$somethingshown=$formfile->show_documents('commande',$comref,$filedir,$urlsource,$genallowed,$delallowed,$commande->modelpdf);

				/*
				 * Liste des factures
				 */
				$sql = 'SELECT f.rowid,f.facnumber, f.total_ttc, '.$db->pdate('f.datef').' as df';
				$sql .= ' FROM '.MAIN_DB_PREFIX.'facture as f, '.MAIN_DB_PREFIX.'co_fa as cf';
				$sql .= ' WHERE f.rowid = cf.fk_facture AND cf.fk_commande = '. $commande->id;

				$result = $db->query($sql);
				if ($result)
				{
					$num = $db->num_rows($result);
					if ($num)
					{
						print '<br>';
						print_titre($langs->trans('RelatedBills'));
						$i = 0; $total = 0;
						print '<table class="noborder" width="100%">';
						print '<tr class="liste_titre"><td>'.$langs->trans('Ref')."</td>";
						print '<td align="center">'.$langs->trans('Date').'</td>';
						print '<td align="right">'.$langs->trans('Price').'</td>';
						print '</tr>';

						$var=True;
						while ($i < $num)
						{
							$objp = $db->fetch_object($result);
							$var=!$var;
							print '<tr '.$bc[$var].'>';
							print '<td><a href="../compta/facture.php?facid='.$objp->rowid.'">'.img_object($langs->trans('ShowBill'),'bill').' '.$objp->facnumber.'</a></td>';
							print '<td align="center">'.dol_print_date($objp->df,'day').'</td>';
							print '<td align="right">'.$objp->total_ttc.'</td></tr>';
							$i++;
						}
						print '</table>';
					}
				}
				else
				{
					dol_print_error($db);
				}
				print '</td><td valign="top" width="50%">';

				// List of actions on element
				include_once(DOL_DOCUMENT_ROOT.'/html.formactions.class.php');
				$formactions=new FormActions($db);
				$somethingshown=$formactions->showactions($commande,'order',$socid);

				print '</td></tr></table>';
			}


			/*
			 * Action presend
			 *
			 */
			if ($_GET['action'] == 'presend')
			{
				$ref = dol_sanitizeFileName($commande->ref);
				$file = $conf->commande->dir_output . '/' . $ref . '/' . $ref . '.pdf';

				print '<br>';
				print_titre($langs->trans('SendOrderByMail'));

				$soc = new Societe($db);
				$soc->fetch($commande->socid);

				$liste[0]="&nbsp;";
				foreach ($soc->thirdparty_and_contact_email_array() as $key=>$value)
				{
					$liste[$key]=$value;
				}

				// Cree l'objet formulaire mail
				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->fromtype = 'user';
				$formmail->fromid   = $user->id;
				$formmail->fromname = $user->fullname;
				$formmail->frommail = $user->email;
				$formmail->withfrom=1;
				$formmail->withto=$liste;
				$formmail->withtocc=1;
				$formmail->withtopic=$langs->trans('SendOrderRef','__ORDERREF__');
				$formmail->withfile=2;
				$formmail->withbody=1;
				$formmail->withdeliveryreceipt=1;
				$formmail->withcancel=1;
				// Tableau des substitutions
				$formmail->substit['__ORDERREF__']=$commande->ref;
				// Tableau des parametres complementaires
				$formmail->param['action']='send';
				$formmail->param['models']='order_send';
				$formmail->param['orderid']=$commande->id;
				$formmail->param['returnurl']=DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;

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
