<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2007 Regis Houssin         <regis.houssin@cap-networks.com>
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
 *
 * $Id$
 * $Source$
 */

/**
   \file       htdocs/comm/propal.php
   \ingroup    propale
   \brief      Page liste des propales (vision commercial)
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT."/lib/propal.lib.php");

$user->getrights('propale');

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->commande->enabled) require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');

$sall=isset($_GET["sall"])?$_GET["sall"]:$_POST["sall"];
if (isset($_GET["msg"])) { $mesg=urldecode($_GET["mesg"]); }
$year=isset($_GET["year"])?$_GET["year"]:"";
$month=isset($_GET["month"])?$_GET["month"]:"";

if (isset($_GET["socid"]))
{
	$objectid=$_GET["socid"];
	$module='societe';
	$dbtable='';
}
else if (isset($_GET["propalid"]) &&  $_GET["propalid"] > 0)
{
	$objectid=$_GET["propalid"];
	$module='propale';
	$dbtable='propal';
}

// Sécurité d'accès client et commerciaux
$socid = restrictedArea($user, $module, $objectid, $dbtable);

// Nombre de ligne pour choix de produit/service prédéfinis
$NBLINES=4;

$form=new Form($db);



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')
{
  if ($user->rights->propale->supprimer)
    {
      $propal = new Propal($db, 0, $_GET['propalid']);
      $propal->fetch($_GET['propalid']);
      $propal->delete($user);
      $propalid = 0;
      $brouillon = 1;
    }
  Header('Location: '.$_SERVER["PHP_SELF"]);
  exit;
}

/*
 *  Supprime une ligne produit SANS confirmation
 */
if ($_GET['action'] == 'deleteline' && $user->rights->propale->creer && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);
	$propal->delete_product($_GET['ligne']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
}

/*
 *  Supprime une ligne produit AVEC confirmation
 */
if ($_REQUEST['action'] == 'confirm_deleteline' && $_REQUEST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
  if ($user->rights->propale->creer)
  {
  	$propal = new Propal($db);
    $propal->fetch($_GET['propalid']);
    $result=$propal->delete_product($_GET['ligne']);
    if ($_REQUEST['lang_id'])
    {
    	$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
    	$outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
    propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
  }
  Header('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$_GET['propalid']);
  exit;
}

if ($_REQUEST['action'] == 'confirm_validate' && $_REQUEST['confirm'] == 'yes')
{
    if ($user->rights->propale->valider)
    {
        $propal = new Propal($db);
        $propal->fetch($_GET['propalid']);
        $result=$propal->update_price($_GET['propalid']);
		if ($_REQUEST['lang_id'])
		{
			$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
        propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
        $result=$propal->valid($user);
    }
    Header ('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$_GET['propalid']);
    exit;
}

if ($_POST['action'] == 'setecheance')
{
	$propal = new Propal($db);
    $propal->fetch($_GET['propalid']);
	$result=$propal->set_echeance($user,dolibarr_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']));
	if ($result < 0) dolibarr_print_error($db,$propal->error);
}
if ($_POST['action'] == 'setdate_livraison')
{
	$propal = new Propal($db);
    $propal->fetch($_GET['propalid']);
	$result=$propal->set_date_livraison($user,dolibarr_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
	if ($result < 0) dolibarr_print_error($db,$propal->error);
}

if ($_POST['action'] == 'setdeliveryadress' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);
	$result=$propal->set_adresse_livraison($user,$_POST['adresse_livraison_id']);
	if ($result < 0) dolibarr_print_error($db,$propal->error);
}

// Positionne ref client
if ($_POST['action'] == 'set_ref_client' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_GET['propalid']);
	$propal->set_ref_client($user, $_POST['ref_client']);
}

/*
 * Creation propale
 */
if ($_POST['action'] == 'add' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->socid=$_POST['socid'];
	$propal->fetch_client();
	
	$db->begin();
	
	// Si on a selectionné une propal à copier, on réalise la copie
	if($_POST['createmode']=='copy' && $_POST['copie_propal'])
	{
		if ($propal->fetch($_POST['copie_propal']) > 0)
		{
			$propal->datep = dolibarr_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			$propal->date_livraison = dolibarr_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);
			$propal->adresse_livraison_id = $_POST['adresse_livraison_id'];
			$propal->duree_validite = $_POST['duree_validite'];
			$propal->cond_reglement_id = $_POST['cond_reglement_id'];
			$propal->mode_reglement_id = $_POST['mode_reglement_id'];
			$propal->remise_percent = $_POST['remise_percent'];
			$propal->remise_absolue = $_POST['remise_absolue'];
			$propal->socid    = $_POST['socid'];
			$propal->contactid = $_POST['contactidp'];
			$propal->projetidp = $_POST['projetidp'];
			$propal->modelpdf  = $_POST['model'];
			$propal->author    = $user->id;
			$propal->note      = $_POST['note'];
			$propal->ref       = $_POST['ref'];
			$propal->statut    = 0;
			
			$id = $propal->create_from();
		}
		else
		{
			$mesg = '<div class="error">'.$langs->trans("ErrorFailedToCopyProposal",$_POST['copie_propal']).'</div>';
		}
	}
	else
	{
		$propal->datep = dolibarr_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
		$propal->date_livraison = dolibarr_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);
		$propal->adresse_livraison_id = $_POST['adresse_livraison_id'];
		$propal->duree_validite = $_POST['duree_validite'];
		$propal->cond_reglement_id = $_POST['cond_reglement_id'];
		$propal->mode_reglement_id = $_POST['mode_reglement_id'];
	
		$propal->contactid  = $_POST['contactidp'];
		$propal->projetidp  = $_POST['projetidp'];
		$propal->modelpdf   = $_POST['model'];
		$propal->author     = $user->id;
		$propal->note       = $_POST['note'];
		$propal->ref_client = $_POST['ref_client'];
		$propal->ref        = $_POST['ref'];
	
		for ($i = 1 ; $i <= $conf->global->PROPALE_NEW_FORM_NB_PRODUCT ; $i++)
		{
			if ($_POST['idprod'.$i])
			{
				$xid = 'idprod'.$i;
				$xqty = 'qty'.$i;
				$xremise = 'remise'.$i;
				$propal->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
			}
		}
	
		$id = $propal->create();
	}
	
	if ($id > 0)
	{
		$error=0;

		// Insertion contact par defaut si défini
		if ($_POST["contactidp"])
		{
			$result=$propal->add_contact($_POST["contactidp"],'CUSTOMER','external');
	
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

		if (! $error)
		{
			$db->commit();

			// Generation document PDF
			if ($_REQUEST['lang_id'])
			{
				$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
				$outputlangs->setDefaultLang($_REQUEST['lang_id']);
			}
			propale_pdf_create($db, $id, $_REQUEST['model'], $outputlangs);
			dolibarr_syslog('Redirect to '.$_SERVER["PHP_SELF"].'?propalid='.$id);
			Header ('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$id);
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dolibarr_print_error($db,$propal->error);
		$db->rollback();
		exit;
	}
}

/*
 *  Cloture de la propale
 */
if ($_REQUEST['action'] == 'setstatut' && $user->rights->propale->cloturer)
{
    if (! $_POST['cancel'])
    {
        $propal = new Propal($db);
        $propal->fetch($_GET['propalid']);
	// prevent browser refresh from closing proposal several times
	if ($propal->statut==1) {
        $propal->cloture($user, $_REQUEST['statut'], $_REQUEST['note']);
    }
}
}

/*
 * Envoi de la propale par mail
 */
if ($_POST['action'] == 'send')
{
    $langs->load('mails');

    $propal= new Propal($db);
    if ( $propal->fetch($_POST['propalid']) )
    {
        $propalref = sanitize_string($propal->ref);
        $file = $conf->propal->dir_output . '/' . $propalref . '/' . $propalref . '.pdf';
        
        if (is_readable($file))
        {
            $propal->fetch_client();
            
			if ($_POST['sendto'])
			{
				// Le destinataire a été fourni via le champ libre
				$sendto = $_POST['sendto'];
				$sendtoid = 0;
			}
			elseif ($_POST['receiver'])
			{
				// Le destinataire a été fourni via la liste déroulante
				if ($_POST['receiver'] < 0)	// Id du tiers
				{
					$sendto = $propal->client->email;
					$sendtoid = 0;
				}
				else	// Id du contact
				{
					$sendto = $propal->client->contact_get_email($_POST['receiver']);
					$sendtoid = $_POST['receiver'];
				}
			}

            if (strlen($sendto))
            {
                $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                $replyto = $_POST['replytoname']. ' <' . $_POST['replytomail'].'>';
                $message = $_POST['message'];
                $sendtocc = $_POST['sendtocc'];
                $deliveryreceipt = $_POST['deliveryreceipt'];

                if ($_POST['action'] == 'send')
                {
                	$subject = $_POST['subject'];

                	if($subject == '')
                	{
                		$subject = $langs->trans('Propal').' '.$propal->ref;
                	}

                  $actiontypeid=3;
                  $actionmsg ='Mail envoyé par '.$from.' à '.$sendto.'.<br>';

                  if ($message)
                  {
                    $actionmsg.='Texte utilisé dans le corps du message:<br>';
                    $actionmsg.=$message;
                  }

                  $actionmsg2='Envoi Propal par mail';
                }

                $filepath[0] = $file;
                $filename[0] = $propal->ref.'.pdf';
                $mimetype[0] = 'application/pdf';
                if ($_FILES['addedfile']['tmp_name'])
                {
                    $filepath[1] = $_FILES['addedfile']['tmp_name'];
                    $filename[1] = $_FILES['addedfile']['name'];
                    $mimetype[1] = $_FILES['addedfile']['type'];
                }

                // Envoi de la propal
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

	                    // Insertion action
	                    require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
						require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
	                    $actioncomm = new ActionComm($db);
	                    $actioncomm->type_id     = $actiontypeid;
	                    $actioncomm->label       = $actionmsg2;
	                    $actioncomm->note        = $actionmsg;
	                    $actioncomm->date        = time();  // L'action est faite maintenant
	                    $actioncomm->percent     = 100;
	                    $actioncomm->contact     = new Contact($db,$sendtoid);
	                    $actioncomm->societe     = new Societe($db,$propal->socid);
	                    $actioncomm->user        = $user;   // User qui a fait l'action
	                    $actioncomm->propalrowid = $propal->id;

	                    $ret=$actioncomm->add($user);       // User qui saisit l'action
	                    if ($ret < 0)
	                    {
	                        dolibarr_print_error($db);
	                    }
	                    else
	                    {
	                        // Renvoie sur la fiche
	                        Header('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&msg='.urlencode($mesg));
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
				dolibarr_syslog('Recipient email is empty');
            }
        }
        else
        {
			$langs->load("other");
			$mesg='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div>';
			dolibarr_syslog('Failed to read file: '.$file);
        }
    }
    else
    {
		$langs->load("other");
		$mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Invoice")).'</div>';
		dolibarr_syslog('Impossible de lire les données de la facture. Le fichier facture n\'a peut-être pas été généré.');
    }
}

if ($_GET['action'] == 'commande')
{
  /*
   *  Cloture de la propale
   */
  $propal = new Propal($db);
  $propal->fetch($propalid);
  $propal->create_commande($user);
}

if ($_GET['action'] == 'modif' && $user->rights->propale->creer)
{
  /*
   *  Repasse la propale en mode brouillon
   */
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->set_draft($user->id);
}


/*
 *  Ajout d'une ligne produit dans la propale
 */
if ($_POST['action'] == "addligne" && $user->rights->propale->creer)
{
	if ($_POST['qty'] && (($_POST['np_price']!=0 && $_POST['np_desc']) || $_POST['idprod']))
	{
	    $propal = new Propal($db);
	    $ret=$propal->fetch($_POST['propalid']);
	    $soc = new Societe($db, $propal->socid);
	    $soc->fetch($propal->socid);

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
        if ($_POST['idprod'])
        {
            $prod = new Product($db, $_POST['idprod']);
            $prod->fetch($_POST['idprod']);
            
            // multiprix
            if ($conf->global->PRODUIT_MULTIPRICES == 1)
            {
            	$pu = $prod->multiprices[$soc->price_level];
            }
            else
            {
            	$pu=$prod->price;
            }
            
            // La description de la ligne est celle saisie ou
            // celle du produit si PRODUIT_CHANGE_PROD_DESC est défini
            if ($conf->global->PRODUIT_CHANGE_PROD_DESC)
            {
            	$desc = $prod->description;
            }
            else
            {
            	$desc = $_POST['np_desc'];
            }
            
            $tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx);
        }
        else
        {
        	$pu=$_POST['np_price'];
        	$tva_tx=$_POST['np_tva_tx'];
        	$desc=$_POST['np_desc'];
        }

        $propal->addline(
			$_POST['propalid'],
			$desc,
			$pu,
			$_POST['qty'],
			$tva_tx,
			$_POST['idprod'],
			$_POST['remise_percent'],
			'HT'
			);

		if ($_REQUEST['lang_id'])
		{
			$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
			$outputlangs->setDefaultLang($_REQUEST['lang_id']);
		}
  	    propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
	}
}

/*
 *  Mise à jour d'une ligne dans la propale
 */
if ($_POST['action'] == 'updateligne' && $user->rights->propale->creer && $_POST["save"] == $langs->trans("Save"))
{
    $propal = new Propal($db);
	if (! $propal->fetch($_POST['propalid']) > 0) dolibarr_print_error($db);

    $result = $propal->updateline($_POST['ligne'],
    	$_POST['subprice'],
    	$_POST['qty'],
    	$_POST['remise_percent'],
    	$_POST['tva_tx'],
    	$_POST['desc'],
		'HT');

	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
    propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
}

/*
 * Generation doc (depuis lien ou depuis cartouche doc)
 */
if ($_REQUEST['action'] == 'builddoc' && $user->rights->propale->creer)
{
    $propal = new Propal($db);
    $propal->fetch($_GET['propalid']);
    if ($_REQUEST['model'])
    {
    	$propal->set_pdf_model($user, $_REQUEST['model']);
    }
    
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
	$result=propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
	if ($result <= 0)
	{
		dolibarr_print_error($db,$result);
		exit;
	}
	else
	{
		Header ('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'#builddoc');
		exit;
	} 
}

if ($_POST['action'] == 'classin')
{
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);
  $propal->set_project($user, $_POST['projetidp']);
}

// Conditions de règlement
if ($_POST["action"] == 'setconditions')
{
	$propal = new Propal($db);
	$propal->fetch($_REQUEST['propalid']);
	$result = $propal->cond_reglement($_POST['cond_reglement_id']);
	$_GET['propalid']=$_REQUEST['propalid'];
}

if ($_REQUEST['action'] == 'setremisepercent' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_REQUEST['propalid']);
	$result = $propal->set_remise_percent($user, $_POST['remise_percent']);
	$_GET['propalid']=$_REQUEST['propalid'];
}

if ($_REQUEST['action'] == 'setremiseabsolue' && $user->rights->propale->creer)
{
	$propal = new Propal($db);
	$propal->fetch($_REQUEST['propalid']);
	$result = $propal->set_remise_absolue($user, $_POST['remise_absolue']);
	$_GET['propalid']=$_REQUEST['propalid'];
}

// Mode de règlement
if ($_POST["action"] == 'setmode')
{
	$propal = new Propal($db);
	$propal->fetch($_REQUEST['propalid']);
	$result = $propal->mode_reglement($_POST['mode_reglement_id']);
	$_GET['propalid']=$_REQUEST['propalid'];
}

/*
 * Ordonnancement des lignes
 */

if ($_GET['action'] == 'up' && $user->rights->propale->creer)
{
	$propal = new Propal($db, '', $_GET["propalid"]);
	$propal->fetch($_GET['propalid']);
	$propal->line_up($_GET['rowid']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
  propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$_GET["propalid"].'#'.$_GET['rowid']);
}

if ($_GET['action'] == 'down' && $user->rights->propale->creer)
{
	$propal = new Propal($db, '', $_GET["propalid"]);
	$propal->fetch($_GET['propalid']);
	$propal->line_down($_GET['rowid']);
	if ($_REQUEST['lang_id'])
	{
		$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
  	propale_pdf_create($db, $propal->id, $propal->modelpdf, $outputlangs);
	Header ('Location: '.$_SERVER["PHP_SELF"].'?propalid='.$_GET["propalid"].'#'.$_GET['rowid']);
	exit;
}


/*
* Affichage page
*/

llxHeader('',$langs->trans('Proposal'),'Proposition');

$html = new Form($db);

if ($_GET['propalid'] > 0)
{
	/*
	 * Affichage fiche propal en mode visu
	 *
	 */
  
  if ($mesg) print "$mesg<br>";
  
  $propal = new Propal($db);
  $propal->fetch($_GET['propalid']);

  $societe = new Societe($db);
  $societe->fetch($propal->socid);
  
  $head = propal_prepare_head($propal);
  dolibarr_fiche_head($head, 'comm', $langs->trans('Proposal'));
    
  /*
   * Confirmation de la suppression de la propale
   */
  if ($_GET['action'] == 'delete')
    {
      $html->form_confirm($_SERVER["PHP_SELF"].'?propalid='.$propal->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp'), 'confirm_delete');
      print '<br>';
    }
  
  /*
   * Confirmation de la suppression d'une ligne produit/service
   */
  if ($_GET['action'] == 'ask_deleteline' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
    {
      $html->form_confirm($_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;ligne='.$_GET["ligne"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline');
      print '<br>';
    }
  
  /*
   * Confirmation de la validation de la propale
   */
  if ($_GET['action'] == 'validate')
    {
      $html->form_confirm($_SERVER["PHP_SELF"].'?propalid='.$propal->id, $langs->trans('ValidateProp'), $langs->trans('ConfirmValidateProp'), 'confirm_validate');
      print '<br>';
    }
  
  
  /*
   * Fiche propal
   *
   */
  
  print '<table class="border" width="100%">';
  
  // Ref
  print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">'.$propal->ref.'</td></tr>';
  
  // Ref client
  print '<tr><td>';
  print '<table class="nobordernopadding" width="100%"><tr><td nowrap>';
  print $langs->trans('RefCustomer').'</td><td align="left">';
  print '</td>';
  if ($_GET['action'] != 'refclient' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('Edit')).'</a></td>';
  print '</tr></table>';
  print '</td><td colspan="5">';
  if ($user->rights->propale->creer && $_GET['action'] == 'refclient')
    {
      print '<form action="propal.php?propalid='.$propal->id.'" method="post">';
      print '<input type="hidden" name="action" value="set_ref_client">';
      print '<input type="text" class="flat" size="20" name="ref_client" value="'.$propal->ref_client.'">';
      print ' <input type="submit" class="button" value="'.$langs->trans('Modify').'">';
      print '</form>';
    }
  else
    {
      print $propal->ref_client;
    }
  print '</td>';
  print '</tr>';
  
  $rowspan=8;
  
  // Société
  print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$societe->getNomUrl(1).'</td>';
  print '</tr>';
  
  // Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($societe->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$societe->remise_client);
	else print $langs->trans("CompanyHasNoRelativeDiscount");
	$absolute_discount=$societe->getCurrentDiscount();
	print '. ';
	if ($absolute_discount)
	{
		if ($propal->statut > 0)
		{
			print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
		}
		else
		{
			print '<br>';
			print $html->form_remise_dispo($_SERVER["PHP_SELF"].'?propalid='.$propal->id,0,'remise_id',$societe->id,$absolute_discount);
		}
	}
	else print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
	print '</td></tr>';

	// Dates
	print '<tr><td>'.$langs->trans('Date').'</td><td colspan="3">';
	print dolibarr_print_date($propal->date,'daytext');
	print '</td>';

	if ($conf->projet->enabled) $rowspan++;
	if ($conf->expedition->enabled)
	  {
	    if ($conf->global->PROPALE_ADD_SHIPPING_DATE) $rowspan++;
	    if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS) $rowspan++;
	  }
	
	// Notes
	print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('NotePublic').' :<br>'. nl2br($propal->note_public).'</td>';
	print '</tr>';

	// Date fin propal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateEndPropal');
	print '</td>';
	if ($_GET['action'] != 'editecheance' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editecheance&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '<td colspan="3">';
	if ($propal->brouillon && $_GET['action'] == 'editecheance')
	{
		print '<form name="editecheance" action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'" method="post">';
		print '<input type="hidden" name="action" value="setecheance">';
		$html->select_date($propal->fin_validite,'ech','','','',"editecheance");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		if ($propal->fin_validite)
		{
			print dolibarr_print_date($propal->fin_validite,'daytext');
			if ($propal->statut == 1 && $propal->fin_validite < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
		}
		else
		{
			print '&nbsp;';
		}
	}
	print '</td>';
	print '</tr>';


	// date de livraison (conditionné sur PROPALE_ADD_SHIPPING_DATE car carac à
	// gérer par les commandes et non les propal
	if ($conf->expedition->enabled)
	{
		if ($conf->global->PROPALE_ADD_SHIPPING_DATE)
		{
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DateDelivery');
			print '</td>';
			if ($_GET['action'] != 'editdate_livraison' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetDateLivraison'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
			if ($_GET['action'] == 'editdate_livraison')
			{
				print '<form name="editdate_livraison" action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'" method="post">';
				print '<input type="hidden" name="action" value="setdate_livraison">';
				$html->select_date($propal->date_livraison,'liv_','','','',"editdate_livraison");
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
			}
			else
			{
				print dolibarr_print_date($propal->date_livraison,'%a %d %B %Y');
			}
			print '</td>';
			print '</tr>';
		}

		// adresse de livraison
		if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
		{
			print '<tr><td>';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('DeliveryAddress');
			print '</td>';

			if ($_GET['action'] != 'editdelivery_adress' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_adress&amp;socid='.$propal->socid.'&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetDeliveryAddress'),1).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';

			if ($_GET['action'] == 'editdelivery_adress')
			{
				$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->adresse_livraison_id,$_GET['socid'],'adresse_livraison_id','propal',$propal->id);
			}
			else
			{
				$html->form_adresse_livraison($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->adresse_livraison_id,$_GET['socid'],'none','propal',$propal->id);
			}
			print '</td></tr>';
		}
	}

	// Conditions et modes de réglement
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentConditionsShort');
	print '</td>';
	if ($_GET['action'] != 'editconditions' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editconditions')
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'cond_reglement_id');
	}
	else
	{
		$html->form_conditions_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->cond_reglement_id,'none');
	}
	print '</td>';
	print '</tr>';

	// Mode paiement
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($_GET['action'] != 'editmode' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($_GET['action'] == 'editmode')
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$html->form_modes_reglement($_SERVER['PHP_SELF'].'?propalid='.$propal->id,$propal->mode_reglement_id,'none');
	}
	print '</td></tr>';

	// Projet
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		$numprojet = $societe->has_projects();
		if (! $numprojet)
		{
			print '</td></tr></table>';
			print '<td colspan="2">';
			print $langs->trans("NoProject").'</td><td>';
			print '<a href=../projet/fiche.php?socid='.$societe->id.'&action=create>'.$langs->trans('AddProject').'</a>';
			print '</td>';
		}
		else
		{
			if ($propal->statut == 0 && $user->rights->propale->creer)
			{
				if ($_GET['action'] != 'classer' && $propal->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;propalid='.$propal->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="3">';
				if ($_GET['action'] == 'classer')
				{
					$form->form_project($_SERVER['PHP_SELF'].'?propalid='.$propal->id, $propal->socid, $propal->projetidp, 'projetidp');
				}
				else
				{
					$form->form_project($_SERVER['PHP_SELF'].'?propalid='.$propal->id, $propal->socid, $propal->projetidp, 'none');
				}
				print '</td></tr>';
			}
			else
			{
				if (!empty($propal->projetidp))
				{
					print '</td></tr></table>';
					print '<td colspan="3">';
					$proj = new Project($db);
					$proj->fetch($propal->projetidp);
					print '<a href="../projet/fiche.php?id='.$propal->projetidp.'" title="'.$langs->trans('ShowProject').'">';
					print $proj->title;
					print '</a>';
					print '</td>';
				}
				else {
					print '</td></tr></table>';
					print '<td colspan="3">&nbsp;</td>';
				}
			}
		}
		print '</tr>';
	}

	// Amount HT
	print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" colspan="2" nowrap><b>'.price($propal->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	// Amount VAT
	print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($propal->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';
	
	// Amount TTC
	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($propal->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->monnaie).'</td></tr>';

	// Statut
	print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$propal->getLibStatut(4).'</td></tr>';
	print '</table><br>';

	/*
	 * Lignes de propale
	 */
	print '<table class="noborder" width="100%">';

	$sql = 'SELECT pt.rowid, pt.description, pt.fk_product, pt.fk_remise_except,';
	$sql.= ' pt.qty, pt.tva_tx, pt.remise_percent, pt.subprice, pt.info_bits,';
	$sql.= ' pt.total_ht, pt.total_tva, pt.total_ttc, pt.marge_tx, pt.marque_tx,';
	$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid,';
	$sql.= ' p.description as product_desc';
	$sql.= ' FROM '.MAIN_DB_PREFIX.'propaldet as pt';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'product as p ON pt.fk_product=p.rowid';
	$sql.= ' WHERE pt.fk_propal = '.$propal->id;
	$sql.= ' ORDER BY pt.rang ASC, pt.rowid';
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		$i = 0;

		if ($num)
		{
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans('Description').'</td>';
			if ($conf->global->PRODUIT_USE_MARKUP) print '<td align="right" width="80">'.$langs->trans('Markup').'</td>';
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

			// Ligne en mode visu
			if ($_GET['action'] != 'editline' || $_GET['ligne'] != $objp->rowid)
			{
				print '<tr '.$bc[$var].'>';
				if ($objp->fk_product > 0)
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
					$text = '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
					if ($objp->fk_product_type==1) $text.= img_object($langs->trans('ShowService'),'service');
					else $text.= img_object($langs->trans('ShowProduct'),'product');
					$text.= ' '.$objp->ref.'</a>';
					$text.= ' - '.nl2br(stripslashes($objp->product));
					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						print $text;
					}
					else
					{
						print $html->textwithtooltip($text,$objp->description,4,'','',$i,$objp->ref.' - '.nl2br(stripslashes($objp->product)));
					}
					print_date_range($objp->date_start,$objp->date_end);

					if ($conf->global->PRODUIT_DESC_IN_FORM)
					{
						if ($conf->global->PRODUIT_CHANGE_PROD_DESC)
						{
							print ($objp->description && $objp->description!=$objp->product)?'<br>'.stripslashes(nl2br($objp->description)):'';
						}
						else
						{
							print '<br>'.nl2br($objp->product_desc);
						}
					}
					
					print '</td>';
				}
				else
				{
					print '<td>';
					print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
					if (($objp->info_bits & 2) == 2)
					{
						print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$propal->socid.'">';
						print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
						print '</a>';
						if ($objp->description)
						{
							if ($objp->description == '(CREDIT_NOTE)')
							{
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
						print nl2br($objp->description);
						print_date_range($objp->date_start,$objp->date_end);
					}
					print "</td>\n";
				}
				if ($conf->global->PRODUIT_USE_MARKUP)
				{
					print '<td align="right">';
				  print '<table class="nobordernopadding" width="100%"><tr class="nocellnopadd">';
          print '<td class="nobordernopadding" nowrap="nowrap" align="left">'.img_calc($langs->trans("ToCalculateMarkup")).'</td>';
          print '<td class="nobordernopadding" nowrap="nowrap" align="right">'.vatrate($objp->marge_tx).'% </td>';
          print '</tr></table>';
          print '</td>';
        }
				print '<td align="right">'.vatrate($objp->tva_tx).'%</td>';
				print '<td align="right">'.price($objp->subprice)."</td>\n";

				// Qty
				print '<td align="right">';
				if (($objp->info_bits & 2) != 2)
				{
					print $objp->qty;
				}
				else print '&nbsp;';
				print '</td>';
				if ($objp->remise_percent > 0)
				{
					print '<td align="right">'.$objp->remise_percent."%</td>\n";
				}
				else
				{
					print '<td>&nbsp;</td>';
				}
				print '<td align="right">'.price($objp->total_ht)."</td>\n";

				// Icone d'edition et suppression
				if ($propal->statut == 0  && $user->rights->propale->creer)
				{
					print '<td align="center">';
					if (($objp->info_bits & 2) == 2)
					{
						// Ligne remise prédéfinie, on permet pas modif
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=editline&amp;ligne='.$objp->rowid.'#'.$objp->rowid.'">';
						print img_edit();
						print '</a>';
					}
					print '</td>';
					print '<td align="center">';
					if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE)
					{
						if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
						{
							$url = $_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&ligne='.$objp->rowid.'&action=confirm_deleteline&confirm=yes';
							print '<a href="#" onClick="dialogConfirm(\''.$url.'\',\''.$langs->trans('ConfirmDeleteProductLine').'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'deleteline'.$i.'\')">';
							print img_delete();
						}
						else
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=ask_deleteline&amp;ligne='.$objp->rowid.'">';
							print img_delete();
						}
					}
					else
					{
						print '<a href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=deleteline&amp;ligne='.$objp->rowid.'">';
						print img_delete();
					}
					print '</a></td>';
					if ($num > 1)
					{
						print '<td align="center">';
						if ($i > 0)
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=up&amp;rowid='.$objp->rowid.'">';
							print img_up();
							print '</a>';
						}
						if ($i < $num-1)
						{
							print '<a href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=down&amp;rowid='.$objp->rowid.'">';
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
			if ($propal->statut == 0 && $_GET["action"] == 'editline' && $user->rights->propale->creer && $_GET["ligne"] == $objp->rowid)
			{
				print '<form action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'#'.$objp->rowid.'" method="post">';
				print '<input type="hidden" name="action" value="updateligne">';
				print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
				print '<input type="hidden" name="ligne" value="'.$_GET["ligne"].'">';
				print '<tr '.$bc[$var].'>';
				print '<td>';
				print '<a name="'.$objp->rowid.'"></a>'; // ancre pour retourner sur la ligne
				if ($objp->fk_product > 0)
				{
					print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$objp->fk_product.'">';
					if ($objp->fk_product_type==1) print img_object($langs->trans('ShowService'),'service');
					else print img_object($langs->trans('ShowProduct'),'product');
					print ' '.$objp->ref.'</a>';
					print ' - '.nl2br($objp->product);
					print '<br>';
				}
				// éditeur wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('desc',$objp->description,164,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea name="desc" cols="70" class="flat" rows="'.ROWS_2.'">'.$objp->description.'</textarea>';
				}
				print '</td>';
				if ($conf->global->PRODUIT_USE_MARKUP) print '<td>&nbsp;</td>';
				print '<td align="right">';
				if($societe->tva_assuj == "0")
				print '<input type="hidden" name="tva_tx" value="0">0';
				else
				print $html->select_tva("tva_tx",$objp->tva_tx,$mysoc,$societe);
				print '</td>';
				print '<td align="right"><input size="6" type="text" class="flat" name="subprice" value="'.price($objp->subprice,0,'',0).'"></td>';
				print '<td align="right">';
				if (($objp->info_bits & 2) != 2)
				{
					print '<input size="2" type="text" class="flat" name="qty" value="'.$objp->qty.'">';
				}
				else print '&nbsp;';
				print '</td>';
				print '<td align="right" nowrap>';
				if (($objp->info_bits & 2) != 2)
				{
					print '<input size="1" type="text" class="flat" name="remise_percent" value="'.$objp->remise_percent.'">%';
				}
				else print '&nbsp;';
				print '</td>';
				print '<td align="center" colspan="5" valign="center"><input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
				print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'"></td>';
				print '</tr>' . "\n";
				/*
				if ($conf->service->enabled)
				{
				print "<tr $bc[$var]>";
				print '<td colspan="5">Si produit de type service à durée limitée: Du ';
				print $html->select_date($objp->date_start,"date_start",0,0,$objp->date_start?0:1);
				print ' au ';
				print $html->select_date($objp->date_end,"date_end",0,0,$objp->date_end?0:1);
				print '</td>';
				print '</tr>' . "\n";
				}
				*/
				print "</form>\n";
			}

			$i++;
		}

		$db->free($resql);
	}
	else
	{
		dolibarr_print_error($db);
	}

	/*
	* Ajouter une ligne
	*/
	if ($propal->statut == 0 && $user->rights->propale->creer && $_GET["action"] <> 'editline')
	{
		if ($conf->global->PRODUIT_USE_MARKUP) $colspan = 'colspan="2"';
		print '<tr class="liste_titre">';
		print '<td '.$colspan.'>';
		print '<a name="add"></a>'; // ancre
		print $langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
		print '<td colspan="4">&nbsp;</td>';
		print "</tr>\n";

		// Ajout produit produits/services personnalisés
		print '<form action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'#add" method="post">';
		print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
		print '<input type="hidden" name="action" value="addligne">';

		$var=true;

		print '<tr '.$bc[$var].">\n";
		print '<td '.$colspan.'>';
		// éditeur wysiwyg
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS_PERSO)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('np_desc','',100,'dolibarr_details');
			$doleditor->Create();
		}
		else
		{
			print '<textarea class="flat" cols="70" name="np_desc" rows="'.ROWS_2.'"></textarea>';
		}
		print '</td>';
		print '<td align="center">';
		if($societe->tva_assuj == "0")
		{
			print '<input type="hidden" name="np_tva_tx" value="0">0';
		}
		else
		{
			$html->select_tva('np_tva_tx', $conf->defaulttx, $mysoc, $societe);
		}
		print "</td>\n";
		print '<td align="right"><input type="text" size="5" name="np_price"></td>';
		print '<td align="right"><input type="text" size="2" value="1" name="qty"></td>';
		print '<td align="right" nowrap><input type="text" size="1" value="'.$societe->remise_client.'" name="remise_percent">%</td>';
		print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'" name="addligne"></td>';
		print '</tr>';

		print '</form>';

		// Ajout de produits/services prédéfinis
		if ($conf->produit->enabled)
		{
			if ($conf->global->PRODUIT_USE_MARKUP)
			{
				$colspan = 'colspan="4"';
			}
			else
			{
				$colspan = 'colspan="3"';
			}
			print '<tr class="liste_titre">';
		  print '<td '.$colspan.'>';
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
			print '<form id="addpredefinedproduct" action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'#add" method="post">';
			print '<input type="hidden" name="propalid" value="'.$propal->id.'">';
			print '<input type="hidden" name="action" value="addligne">';

			$var=!$var;

			print '<tr '.$bc[$var].'>';
			print '<td '.$colspan.'>';
			// multiprix
			if($conf->global->PRODUIT_MULTIPRICES == 1)
			{
				$html->select_produits('','idprod','',$conf->produit->limit_size,$societe->price_level);
			}
			else
			{
				$html->select_produits('','idprod','',$conf->produit->limit_size);
			}
			if (! $conf->global->PRODUIT_USE_SEARCH_TO_SELECT) print '<br>';
			
			if (! $conf->global->PRODUIT_CHANGE_PROD_DESC)
			{
				// éditeur wysiwyg
				if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_DETAILS_PERSO)
				{
					require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
					$doleditor=new DolEditor('np_desc','',100,'dolibarr_details');
					$doleditor->Create();
				}
				else
				{
					print '<textarea cols="70" name="np_desc" rows="'.ROWS_2.'" class="flat"></textarea>';
				}
			}

			print '</td>';
			print '<td align="right"><input type="text" size="2" name="qty" value="1"></td>';
			print '<td align="right" nowrap><input type="text" size="1" name="remise_percent" value="'.$societe->remise_client.'">%</td>';

			print '<td align="center" valign="middle" colspan="4"><input type="submit" class="button" value="'.$langs->trans("Add").'" name="addligne">';
			print '</td></tr>'."\n";

			print '</form>';
		}
	}

	print '</table>';

	print '</div>';
	print "\n";

	/*
	* Formulaire cloture (signé ou non)
	*/
	$form_close = '<form action="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'" method="post">';
	$form_close.= '<table class="border" width="100%">';
	$form_close.= '<tr><td width="150" align="left">'.$langs->trans('Note').'</td><td align="left"><textarea cols="70" rows="'.ROWS_3.'" wrap="soft" name="note">';
	$form_close.= $propal->note;
	$form_close.= '</textarea></td></tr>';
	$form_close.= '<tr><td width="150"  align="left">'.$langs->trans("CloseAs").'</td><td align="left">';
	$form_close.= '<input type="hidden" name="action" value="setstatut">';
	$form_close.= '<select name="statut">';
	$form_close.= '<option value="0">&nbsp;</option>';
	$form_close.= '<option value="2">'.$propal->labelstatut[2].'</option>';
	$form_close.= '<option value="3">'.$propal->labelstatut[3].'</option>';
	$form_close.= '</select>';
	$form_close.= '</td></tr>';
	$form_close.= '<tr><td align="center" colspan="2">';
	$form_close.= '<input type="submit" class="button" name="validate" value="'.$langs->trans('Validate').'">';
	if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
	{
		$form_close.= ' &nbsp; <input onClick="Dialog.closeInfo()" type="button" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
	}
	else
	{
		$form_close.= ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
	}
	$form_close.= '</td>';
	$form_close.= '</tr></table></form>';
	
	if ($_GET['action'] == 'statut')
	{
		print $form_close;
	}


	/*
	* Boutons Actions
	*/
	print '<div class="tabsAction">';

	if ($_GET['action'] != 'statut' && $_GET['action'] <> 'editline')
	{

		// Valid
		if ($propal->statut == 0 && $propal->total_ttc > 0 && $user->rights->propale->valider)
		{
			print '<a class="butAction" ';
			if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
			{
				$url = $_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&action=confirm_validate&confirm=yes';
				print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.$langs->trans('ConfirmValidateProp').'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'validate\')"';
			}
			else
			{
				print 'href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=validate"';
			}
			print '>'.$langs->trans('Validate').'</a>';
		}

		// Edit
		if ($propal->statut == 1 && $user->rights->propale->creer)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=modif">'.$langs->trans('Edit').'</a>';
		}

		// Build PDF
		if ($user->rights->propale->creer)
		{
			if ($propal->statut < 2)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=builddoc">'.$langs->trans("BuildPDF").'</a>';
			}
			else
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=builddoc">'.$langs->trans("RebuildPDF").'</a>';
			}
		}

		// Send
		if ($propal->statut == 1 && $user->rights->propale->envoyer)
		{
			$propref = sanitize_string($propal->ref);
			$file = $conf->propal->dir_output . '/'.$propref.'/'.$propref.'.pdf';
			if (file_exists($file))
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=presend">'.$langs->trans('SendByMail').'</a>';
			}
		}

		// Close
		if ($propal->statut == 1 && $user->rights->propale->cloturer)
		{
			print '<div id="confirm_close" style="display:none">';
			print $form_close."\n";
			print '</div>'."\n";
			
			print '<a class="butAction" ';
			if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
			{
				print 'href="#" onClick="dialogInfo($(\'confirm_close\').innerHTML)"'."\n";
			}
			else
			{
				print 'href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=statut"';
			}
			print '>'.$langs->trans('Close').'</a>';
		}

		// Delete
		if ($propal->statut == 0 && $user->rights->propale->supprimer)
		{
			print '<a class="butActionDelete" ';
			if ($conf->use_ajax && $conf->global->MAIN_CONFIRM_AJAX)
			{
				$url = $_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&action=confirm_delete&confirm=yes';
				print 'href="#" onClick="dialogConfirm(\''.$url.'\',\''.$langs->trans('ConfirmDeleteProp').'\',\''.$langs->trans("Yes").'\',\''.$langs->trans("No").'\',\'delete\')"';
			}
			else
			{
				print 'href="'.$_SERVER["PHP_SELF"].'?propalid='.$propal->id.'&amp;action=delete"';
			}
			print '>'.$langs->trans('Delete').'</a>';
		}

	}

	print '</div>';
	print "<br>\n";


	print '<table width="100%"><tr><td width="50%" valign="top">';
	print '<a name="builddoc"></a>'; // ancre


	/*
	* Documents générés
	*/
	$filename=sanitize_string($propal->ref);
	$filedir=$conf->propal->dir_output . "/" . sanitize_string($propal->ref);
	$urlsource=$_SERVER["PHP_SELF"]."?propalid=".$propal->id;
	$genallowed=$user->rights->propale->creer;
	$delallowed=$user->rights->propale->supprimer;

	$var=true;

	$somethingshown=$html->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed,$propal->modelpdf);


	/*
	* Commandes rattachées
	*/
	if($conf->commande->enabled)
	{
		$propal->loadOrders();
		$coms = $propal->commandes;
		if (sizeof($coms) > 0)
		{
			if ($somethingshown) { print '<br>'; $somethingshown=1; }
			print_titre($langs->trans('RelatedOrders'));
			print '<table class="noborder" width="100%">';
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("Ref").'</td>';
			print '<td align="center">'.$langs->trans("Date").'</td>';
			print '<td align="right">'.$langs->trans("Price").'</td>';
			print '<td align="right">'.$langs->trans("Status").'</td>';
			print '</tr>';
			$var=true;
			for ($i = 0 ; $i < sizeof($coms) ; $i++)
			{
				$var=!$var;
				print '<tr '.$bc[$var].'><td>';
				print '<a href="'.DOL_URL_ROOT.'/commande/fiche.php?id='.$coms[$i]->id.'">'.img_object($langs->trans("ShowOrder"),"order").' '.$coms[$i]->ref."</a></td>\n";
				print '<td align="center">'.dolibarr_print_date($coms[$i]->date,'day').'</td>';
				print '<td align="right">'.price($coms[$i]->total_ttc).'</td>';
				print '<td align="right">'.$coms[$i]->getLibStatut(3).'</td>';
				print "</tr>\n";
			}
			print '</table>';
		}
	}

	print '</td><td valign="top" width="50%">';

	/*
	* Liste des actions propres à la propal
	*/
	$sql = 'SELECT id, '.$db->pdate('a.datea'). ' as da, label, note, fk_user_author' ;
	$sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
	$sql .= ' WHERE a.propalrowid = '.$propal->id ;
	if ($socid) $sql .= ' AND a.fk_soc = '.$socid;
	$resql = $db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);
		if ($num)
		{
			print_titre($langs->trans('ActionsOnPropal'));
			$i = 0;
			$total = 0;
			$var=true;

			print '<table class="border" width="100%">';
			print '<tr '.$bc[$var].'><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans('Date').'</td><td>'.$langs->trans('Action').'</td><td>'.$langs->trans('By').'</td></tr>';
			print "\n";

			while ($i < $num)
			{
				$objp = $db->fetch_object($resql);
				$var=!$var;
				print '<tr '.$bc[$var].'>';
				print '<td><a href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$objp->id.'">'.img_object($langs->trans('ShowTask'),'task').' '.$objp->id.'</a></td>';
				print '<td>'.dolibarr_print_date($objp->da)."</td>\n";
				print '<td>'.stripslashes($objp->label).'</td>';
				$authoract = new User($db);
				$authoract->id = $objp->fk_user_author;
				$authoract->fetch('');
				print '<td>'.$authoract->login.'</td>';
				print "</tr>\n";
				$i++;
			}
			print '</table>';
		}
	}
	else
	{
		dolibarr_print_error($db);
	}

	print '</td></tr></table>';


	/*
	* Action presend
	*
	*/
	if ($_GET['action'] == 'presend')
	{
		print '<br>';
		print_titre($langs->trans('SendPropalByMail'));

		$liste[0]="&nbsp;";
		foreach ($societe->thirdparty_and_contact_email_array() as $key=>$value)
		{
			$liste[$key]=$value;
		}

		// Créé l'objet formulaire mail
		include_once('../html.formmail.class.php');
		$formmail = new FormMail($db);
		$formmail->fromname = $user->fullname;
		$formmail->frommail = $user->email;
		$formmail->withfrom=1;
		$formmail->withto=$liste;
		$formmail->withcc=1;
		$formmail->withtopic=$langs->trans('SendPropalRef','__PROPREF__');
		$formmail->withfile=1;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		// Tableau des substitutions
		$formmail->substit['__PROPREF__']=$propal->ref;
		// Tableau des paramètres complémentaires
		$formmail->param['action']='send';
		$formmail->param['models']='propal_send';
		$formmail->param['propalid']=$propal->id;
		$formmail->param['returnurl']=DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id;

		$formmail->show_form();

		print '<br>';
	}

}
else
{
  /****************************************************************************
   *                                                                          *
   *                         Mode Liste des propales                          *
   *                                                                          *
   ****************************************************************************/
  
  $sortorder=$_GET['sortorder'];
  $sortfield=$_GET['sortfield'];
  $page=$_GET['page'];
  $viewstatut=$_GET['viewstatut'];

  if (! $sortfield) $sortfield='p.datep';
  if (! $sortorder) $sortorder='DESC';
  $limit = $conf->liste_limit;
  $offset = $limit * $page ;
  $pageprev = $page - 1;
  $pagenext = $page + 1;

  $sql = 'SELECT s.nom, s.rowid, s.client, ';
  $sql.= 'p.rowid as propalid, p.price, p.ref, p.fk_statut, '.$db->pdate('p.datep').' as dp,'.$db->pdate('p.fin_validite').' as dfv';
  if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
  $sql.= ' FROM '.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p';
  if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
  if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
  $sql.= ' WHERE p.fk_soc = s.rowid';

  if (!$user->rights->commercial->client->voir && !$socid) //restriction
    {
	    $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
    }
  if (!empty($_GET['search_ref']))
    {
      $sql .= " AND p.ref LIKE '%".addslashes($_GET['search_ref'])."%'";
    }
  if (!empty($_GET['search_societe']))
    {
      $sql .= " AND s.nom LIKE '%".addslashes($_GET['search_societe'])."%'";
    }
  if (!empty($_GET['search_montant_ht']))
    {
      $sql .= " AND p.price='".addslashes($_GET['search_montant_ht'])."'";
    }
  if ($sall) $sql.= " AND (s.nom like '%".addslashes($sall)."%' OR p.note like '%".addslashes($sall)."%' OR pd.description like '%".addslashes($sall)."%')";
  if ($socid) $sql .= ' AND s.rowid = '.$socid;
  if ($_GET['viewstatut'] <> '')
    {
      $sql .= ' AND p.fk_statut in ('.$_GET['viewstatut'].')';
    }
  if ($month > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y-%m') = '$year-$month'";
    }
  if ($year > 0)
    {
      $sql .= " AND date_format(p.datep, '%Y') = $year";
    }
  if (strlen($_POST['sf_ref']) > 0)
    {
      $sql .= " AND p.ref like '%".addslashes($_POST["sf_ref"]) . "%'";
    }

  $sql .= ' ORDER BY '.$sortfield.' '.$sortorder.', p.ref DESC';
  $sql .= $db->plimit($limit + 1,$offset);
  $result=$db->query($sql);

  if ($result)
    {
      $num = $db->num_rows($result);
      $propalstatic=new Propal($db);
      print_barre_liste($langs->trans('ListOfProposals'), $page,'propal.php','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,$sortfield,$sortorder,'',$num);
      $i = 0;
      print '<table class="liste" width="100%">';
      print '<tr class="liste_titre">';
      print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'p.ref','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'',$sortfield);
      print_liste_field_titre($langs->trans('Company'),$_SERVER["PHP_SELF"],'s.nom','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'',$sortfield);
      print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'p.datep','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="center"',$sortfield);
      print_liste_field_titre($langs->trans('DateEndPropalShort'),$_SERVER["PHP_SELF"],'dfv','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="center"',$sortfield);
      print_liste_field_titre($langs->trans('Price'),$_SERVER["PHP_SELF"],'p.price','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut, 'align="right"',$sortfield);
      print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'p.fk_statut','','&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut,'align="right"',$sortfield);
      print "</tr>\n";
      // Lignes des champs de filtre
      print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

      print '<tr class="liste_titre">';
      print '<td class="liste_titre" valign="right">';
      print '<input class="flat" size="10" type="text" name="search_ref" value="'.$_GET['search_ref'].'">';
      print '</td>';
      print '<td class="liste_titre" align="left">';
      print '<input class="flat" type="text" size="40" name="search_societe" value="'.$_GET['search_societe'].'">';
      print '</td>';
      print '<td class="liste_titre" colspan="2">&nbsp;</td>';
      print '<td class="liste_titre" align="right">';
      print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$_GET['search_montant_ht'].'">';
      print '</td>';
      print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'">';
      print '</td>';
      print "</tr>\n";
      print '</form>';

      $var=true;

      while ($i < min($num,$limit))
        {
            $objp = $db->fetch_object($result);
            $now = time();
            $var=!$var;
            print '<tr '.$bc[$var].'>';
            print '<td nowrap="nowrap">';
            
            $propalstatic->id=$objp->propalid;
            $propalstatic->ref=$objp->ref;
                
            print '<table class="nobordernopadding"><tr class="nocellnopadd">';
            print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
            print $propalstatic->getNomUrl(1);
            print '</td>';
      
            print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
            if ($objp->fk_statut == 1 && $objp->dfv < (time() - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
            print '</td>';
                
            print '<td width="16" align="right" class="nobordernopadding">';
                
            $filename=sanitize_string($objp->ref);
            $filedir=$conf->propal->dir_output . '/' . sanitize_string($objp->ref);
            $urlsource=$_SERVER['PHP_SELF'].'?propalid='.$objp->propalid;
            $html->show_documents('propal',$filename,$filedir,$urlsource,'','','','','',1);
                
            print '</td></tr></table>';

            if ($objp->client == 1)
            {
                $url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->rowid;
            }
            else
            {
                $url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->rowid;
            }

			// Société
            print '<td><a href="'.$url.'">'.img_object($langs->trans('ShowCompany'),'company').' '.$objp->nom.'</a></td>';

            // Date propale
            print '<td align="center">';
            $y = strftime('%Y',$objp->dp);
            $m = strftime('%m',$objp->dp);

            print strftime('%d',$objp->dp)."\n";
            print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'&amp;month='.$m.'">';
            print dolibarr_print_date($objp->dp,'%b')."</a>\n";
            print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'">';
            print strftime('%Y',$objp->dp)."</a></td>\n";

            // Date fin validite
            if ($objp->dfv)
            {
                print '<td align="center">'.dolibarr_print_date($objp->dfv);
                print '</td>';
            }
            else
            {
                print '<td>&nbsp;</td>';
            }

            print '<td align="right">'.price($objp->price)."</td>\n";
            $propal=New Propal($db);
            print '<td align="right">'.$propal->LibStatut($objp->fk_statut,5)."</td>\n";
            print "</tr>\n";

            $total = $total + $objp->price;
            $subtotal = $subtotal + $objp->price;

            $i++;
        }
      print '</table>';
      $db->free($result);
    }
  else
    {
      dolibarr_print_error($db);
    }
}
$db->close();

llxFooter('$Date$ - $Revision$');

?>
