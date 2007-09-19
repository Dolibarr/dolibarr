<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur   <eldy@users.sourceforge.net>
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
   \file       htdocs/commande/fiche.php
   \ingroup    commande
   \brief      Fiche commande client
   \version    $Revision$
*/

require('./pre.inc.php');
require_once(DOL_DOCUMENT_ROOT ."/includes/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT.'/commande/commande.class.php');
require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT.'/lib/CMailFile.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/order.lib.php");
if ($conf->projet->enabled) require_once(DOL_DOCUMENT_ROOT.'/project.class.php');
if ($conf->propal->enabled) require_once(DOL_DOCUMENT_ROOT.'/propal.class.php');

$langs->load('orders');
$langs->load('sendings');
$langs->load('companies');
$langs->load('bills');
$langs->load('propal');
$langs->load('deliveries');
$langs->load('products');

$user->getrights('commande');
$user->getrights('expedition');

if (!$user->rights->commande->lire) accessforbidden();


// Sécurité accés client
$socid=0;
if ($user->societe_id > 0)
{
	$socid = $user->societe_id;
}
if ($user->societe_id >0 && isset($_GET["id"]) && $_GET["id"]>0)
{
   $commande = new Commande($db);
   $commande->fetch((int)$_GET['id']);
   if ($user->societe_id !=  $commande->socid) {
      accessforbidden();
   }
}



// Récupération de l'id de projet
$projetid = 0;
if ($_GET["projetid"])
{
  $projetid = $_GET["projetid"];
}

/*
 * Actions
 */

// Categorisation dans projet
if ($_POST['action'] == 'classin' && $user->rights->commande->creer)
{
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $commande->classin($_POST['projetid']);
}

// Ajout commande
if ($_POST['action'] == 'add' && $user->rights->commande->creer)
{
  $datecommande='';
  $datecommande  = dolibarr_mktime(12, 0, 0, $_POST['remonth'],  $_POST['reday'],  $_POST['reyear']);
  $datelivraison = dolibarr_mktime(12, 0, 0, $_POST['liv_month'],$_POST['liv_day'],$_POST['liv_year']);

  $commande = new Commande($db);
  $commande->socid=$_POST['socid'];
  $commande->fetch_client();

  $db->begin();

  $commande->date_commande        = $datecommande;
  $commande->note                 = $_POST['note'];
  $commande->source               = $_POST['source_id'];
  $commande->projetid             = $_POST['projetid'];
  //$commande->remise_absolue       = $_POST['remise_absolue']; //la remise était appliquée sur les lignes et sur le total
  //$commande->remise_percent       = $_POST['remise_percent'];
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
      // Insertion contact par defaut si défini
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

  // Fin création facture, on l'affiche
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
	  dolibarr_print_error($db,$com->error);
	}
    }
}

if ($_POST['action'] == 'setdate_livraison' && $user->rights->commande->creer)
{
  $datelivraison=dolibarr_mktime(0, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);
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
  if ($result < 0) dolibarr_print_error($db,$commande->error);
}

if ($_POST['action'] == 'setconditions' && $user->rights->commande->creer)
{
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $result=$commande->cond_reglement($_POST['cond_reglement_id']);
  if ($result < 0) dolibarr_print_error($db,$commande->error);
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
if ($_POST['action'] == 'addligne' && $user->rights->commande->creer)
{
  if ($_POST['qty'] && (($_POST['pu'] && $_POST['np_desc']) || $_POST['idprod']))
  {
  	$commande = new Commande($db);
    $ret=$commande->fetch($_POST['id']);
    $soc = new Societe($db, $commande->socid);
    $soc->fetch($commande->socid);
    
    if ($ret < 0)
    {
    	dolibarr_print_error($db,$commande->error);
    	exit;
    }

    // Ecrase $pu par celui du produit
    // Ecrase $desc par celui du produit
    // Ecrase $txtva par celui du produit
    if ($_POST['idprod'])
    {
    	$prod = new Product($db, $_POST['idprod']);
    	$prod->fetch($_POST['idprod']);
    	
    	$libelle = $prod->libelle;
    	
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
	    	$desc=$_POST['np_desc'];
	    }
	    
	    $tva_tx = get_default_tva($mysoc,$soc,$prod->tva_tx); 
	  }
	  else
	  {
	  	$pu=$_POST['pu'];
	  	$tva_tx=$_POST['tva_tx'];
	  	$desc=$_POST['np_desc'];
	  }

      $commande->addline(
			 $_POST['id'],
			 $desc,
			 $pu,
			 $_POST['qty'],
			 $tva_tx,
			 $_POST['idprod'],
			 $_POST['remise_percent']
			 );

      if ($_REQUEST['lang_id'])
	{
	  $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
	  $outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
      commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
    }
}

/*
 *  Mise à jour d'une ligne dans la commande
 */
if ($_POST['action'] == 'updateligne' && $user->rights->commande->creer && $_POST['save'] == $langs->trans('Save'))
{
  $commande = new Commande($db,'',$_POST['id']);
  if (! $commande->fetch($_POST['id']) > 0) dolibarr_print_error($db);
  
  $result = $commande->updateline($_POST['elrowid'],
				  $_POST['eldesc'],
				  $_POST['pu'],
				  $_POST['qty'],
				  $_POST['elremise_percent'],
				  $_POST['tva_tx']
				  );
  
  if ($result >= 0)
    {
      if ($_REQUEST['lang_id'])
	{
	  $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
	  $outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
      commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
    }
  else
    {
      dolibarr_print_error($db,$commande->error);
      exit;
    }
  
  $_GET['id']=$_POST['id'];   // Pour réaffichage de la fiche en cours d'édition
}

if ($_POST['action'] == 'updateligne' && $user->rights->commande->creer && $_POST['cancel'] == $langs->trans('Cancel'))
{
  Header('Location: fiche.php?id='.$_POST['id']);   // Pour réaffichage de la fiche en cours d'édition
  exit;
}

if ($_GET['action'] == 'deleteline' && $user->rights->commande->creer && !$conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $result = $commande->delete_line($_GET['lineid']);
  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
  commande_pdf_create($db, $_GET['id'], $commande->modelpdf, $outputlangs);
  Header('Location: fiche.php?id='.$_GET['id']);
  exit;
}

if ($_POST['action'] == 'confirm_valid' && $_POST['confirm'] == 'yes' && $user->rights->commande->valider)
{
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $soc = new Societe($db);
  $soc->fetch($commande->socid);
  $result = $commande->valid($user);
}

if ($_POST['action'] == 'confirm_close' && $_POST['confirm'] == 'yes' && $user->rights->commande->creer)
{
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $result = $commande->cloture($user);
}

if ($_POST['action'] == 'confirm_cancel' && $_POST['confirm'] == 'yes' && $user->rights->commande->valider)
{
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $result = $commande->cancel($user);
}

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes')
{
  if ($user->rights->commande->supprimer )
    {
      $commande = new Commande($db);
      $commande->fetch($_GET['id']);
      $commande->delete();
      Header('Location: index.php');
      exit;
    }
}

if ($_POST['action'] == 'confirm_deleteproductline' && $_POST['confirm'] == 'yes' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
{
  if ($user->rights->commande->creer)
    {
      $commande = new Commande($db);
      $commande->fetch($_GET['id']);
      $commande->delete_line($_GET['lineid']);
      if ($_REQUEST['lang_id'])
	{
	  $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
	  $outputlangs->setDefaultLang($_REQUEST['lang_id']);
	}
      commande_pdf_create($db, $_GET['id'], $commande->modelpdf, $outputlangs);
    }
  Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET['id']);
  exit;
}

if ($_GET['action'] == 'modif' && $user->rights->commande->creer)
{
  /*
   *  Repasse la commande en mode brouillon
   */
  $commande = new Commande($db);
  $commande->fetch($_GET['id']);
  $commande->set_draft($user);
}

/*
* Ordonnancement des lignes
*/

if ($_GET['action'] == 'up' && $user->rights->commande->creer)
{
  $commande = new Commande($db,'',$_GET['id']);
  $commande->fetch($_GET['id']);
  $commande->line_up($_GET['rowid']);
  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
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
  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
  commande_pdf_create($db, $commande->id, $commande->modelpdf, $outputlangs);
  Header ('Location: '.$_SERVER["PHP_SELF"].'?id='.$_GET["id"].'#'.$_GET['rowid']);
  exit;
}

if ($_REQUEST['action'] == 'builddoc')	// En get ou en post
{
  /*
   * Generation de la commande
   * définit dans /includes/modules/commande/modules_commande.php
   */

  // Sauvegarde le dernier modèle choisi pour générer un document
  $commande = new Commande($db, 0, $_REQUEST['id']);
  $commande->fetch($_REQUEST['id']);
  if ($_REQUEST['model'])
    {
      $commande->set_pdf_model($user, $_REQUEST['model']);
    }

  if ($_REQUEST['lang_id'])
    {
      $outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
      $outputlangs->setDefaultLang($_REQUEST['lang_id']);
    }
  $result=commande_pdf_create($db, $commande->id,$commande->modelpdf,$outputlangs);
  if ($result <= 0)
    {
      dolibarr_print_error($db,$result);
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
 * Envoi de la commande par mail
 */
if ($_POST['action'] == 'send')
{
  $langs->load('mails');

  $commande= new Commande($db);
  if ( $commande->fetch($_POST['orderid']) )
    {
      $orderref = sanitize_string($commande->ref);
      $file = $conf->commande->dir_output . '/' . $orderref . '/' . $orderref . '.pdf';

      if (is_readable($file))
	{
	  $commande->fetch_client();

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
		      $subject = $langs->trans('Order').' '.$commande->ref;
		    }

		  $actiontypeid=8;
		  $actionmsg ='Mail envoyé par '.$from.' à '.$sendto.'.<br>';

		  if ($message)
		    {
		      $actionmsg.='Texte utilisé dans le corps du message:<br>';
		      $actionmsg.=$message;
		    }

		  $actionmsg2='Envoi commande par mail';
		}

	      $filepath[0] = $file;
	      $filename[0] = $commande->ref.'.pdf';
	      $mimetype[0] = 'application/pdf';
	      if ($_FILES['addedfile']['tmp_name'])
		{
		  $filepath[1] = $_FILES['addedfile']['tmp_name'];
		  $filename[1] = $_FILES['addedfile']['name'];
		  $mimetype[1] = $_FILES['addedfile']['type'];
		}

	      // Envoi de la commande
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
		      $actioncomm->societe     = new Societe($db,$commande->socid);
		      $actioncomm->user        = $user;   // User qui a fait l'action
		      $actioncomm->orderrowid  = $commande->id;

		      $ret=$actioncomm->add($user);       // User qui saisit l'action
		      if ($ret < 0)
			{
			  dolibarr_print_error($db);
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

llxHeader('',$langs->trans('Order'),'Commande');

$html = new Form($db);

/*********************************************************************
*
* Mode creation
*
*********************************************************************/
if ($_GET['action'] == 'create' && $user->rights->commande->creer)
{
  print_titre($langs->trans('CreateOrder'));
  
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
	  
	  $nbrow=7;
	  
	  print '<form name="crea_commande" action="fiche.php" method="post">';
	  print '<input type="hidden" name="action" value="add">';
	  print '<input type="hidden" name="socid" value="'.$soc->id.'">' ."\n";
	  print '<input type="hidden" name="remise_percent" value="'.$soc->remise_client.'">';
	  print '<input name="facnumber" type="hidden" value="provisoire">';
	  
	  print '<table class="border" width="100%">';
	  
	  // Reference
	  print '<tr><td>'.$langs->trans('Ref').'</td><td>'.$langs->trans("Draft").'</td>';
	  print '<td>'.$langs->trans('NotePublic').'</td></tr>';
	  
	  // Reference client
	  print '<tr><td>'.$langs->trans('RefCustomer').'</td><td>';
	  print '<input type="text" name="ref_client" value=""></td>';
	  print '<td rowspan="'.$nbrow.'" valign="top"><textarea name="note" cols="70" rows="8"></textarea></td></tr>';
	  
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
	  $absolute_discount=$soc->getCurrentDiscount();
	  print '. ';
	  if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
	  else print $langs->trans("CompanyHasNoAbsoluteDiscount");
	  print '.';
	  print '</td></tr>';
	  
	  // Date
	  print '<tr><td>'.$langs->trans('Date').'</td><td>';
	  $html->select_date('','re','','','',"crea_commande");
	  print '</td></tr>';
	  
	  // Date de livraison
	  if ($conf->expedition->enabled)
	    {
	      print "<tr><td>".$langs->trans("DateDelivery")."</td><td>";
	      if ($conf->global->DATE_LIVRAISON_WEEK_DELAY)
		{
		  $tmpdte = time() + ((7*$conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		  $html->select_date($tmpdte,'liv_','','',1,"crea_commande");
		}
	      else
		{
		  $html->select_date(-1,'liv_','','',1,"crea_commande");
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
	    }
	  
	  // Conditions de réglement
	  print '<tr><td nowrap="nowrap">'.$langs->trans('PaymentConditionsShort').'</td><td>';
	  $html->select_conditions_paiements($soc->cond_reglement,'cond_reglement_id',-1,1);
	  print '</td></tr>';
	  
	  // Mode de réglement
	  print '<tr><td>'.$langs->trans('PaymentMode').'</td><td>';
	  $html->select_types_paiements($soc->mode_reglement,'mode_reglement_id');
	  print '</td></tr>';
	  
	  // Projet
	  if ($conf->projet->enabled)
	    {
	      print '<tr><td>'.$langs->trans('Project').'</td><td>';
	      $numprojet=$html->select_projects($soc->id,$projetid,'projetid');
	      if ($numprojet==0)
		{
		  print ' &nbsp; <a href=../projet/fiche.php?socid='.$soc->id.'&action=create>'.$langs->trans("AddProject").'</a>';
		}
	      print '</td></tr>';
	    }

	  print '<tr><td>'.$langs->trans('Source').'</td><td>';
	  $html->selectSourcesCommande('','source_id',1);
	  print '</td></tr>';
	  print '<tr><td>'.$langs->trans('Model').'</td>';
	  print '<td>';
	  // pdf
	  include_once(DOL_DOCUMENT_ROOT.'/includes/modules/commande/modules_commande.php');
	  $model=new ModelePDFCommandes();
	  $liste=$model->liste_modeles($db);
	  $html->select_array('model',$liste,$conf->global->COMMANDE_ADDON_PDF);
	  print "</td></tr>";
	  
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
		   * Services/produits prédéfinis
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
						if($conf->global->PRODUIT_MULTIPRICES == 1)
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
				print '<td align="center">'.$langs->trans('Reductionshort').'</td>';
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
					dolibarr_print_error($db);
				}

				print '</table>';
			}
		}
	}
	else
	{
		dolibarr_print_error($db);
	}
}
else
{
  /* *************************************************************************** */
  /*                                                                             */
  /* Mode vue et edition                                                         */
  /*                                                                             */
  /* *************************************************************************** */
  $id = $_GET['id'];
  if ($id > 0)
    {
      if ($mesg) print $mesg.'<br>';
      
      $commande = new Commande($db);
      if ( $commande->fetch($_GET['id']) > 0)
	{
	  $soc = new Societe($db);
	  $soc->fetch($commande->socid);
	  
	  $author = new User($db);
	  $author->id = $commande->user_author_id;
	  $author->fetch();
	  
	  $head = commande_prepare_head($commande);
	  dolibarr_fiche_head($head, 'order', $langs->trans("CustomerOrder"));
	  
	  /*
	   * Confirmation de la suppression de la commande
	   */
	  if ($_GET['action'] == 'delete')
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('DeleteOrder'), $langs->trans('ConfirmDeleteOrder'), 'confirm_delete');
	      print '<br />';
	    }
	  
	  /*
	   * Confirmation de la validation
	   */
	  if ($_GET['action'] == 'valid')
	    {
	      // on vérifie si la facture est en numérotation provisoire
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
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('ValidateOrder'), $text, 'confirm_valid');
	      print '<br />';
	    }
	  
	  /*
	   * Confirmation de la cloture
	   */
	  if ($_GET['action'] == 'cloture')
	    {
	      //$numfa = commande_get_num($soc);
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('CloseOrder'), $langs->trans('ConfirmCloseOrder'), 'confirm_close');
	      print '<br />';
	    }
	  
	  /*
	   * Confirmation de l'annulation
	   */
	  if ($_GET['action'] == 'annuler')
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id, $langs->trans('Cancel'), $langs->trans('ConfirmCancelOrder'), 'confirm_cancel');
	      print '<br />';
	    }
	  
	  /*
	   * Confirmation de la suppression d'une ligne produit
	   */
	  if ($_GET['action'] == 'delete_product_line' && $conf->global->PRODUIT_CONFIRM_DELETE_LINE)
	    {
	      $html->form_confirm($_SERVER["PHP_SELF"].'?id='.$id.'&amp;lineid='.$_GET["lineid"], $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteproductline');
	      print '<br>';
	    }
	  
	  /*
	   *   Commande
	   */
	  $nbrow=8;
	  if ($conf->projet->enabled) $nbrow++;
	  
	  print '<table class="border" width="100%">';
	  
	  // Ref
	  print '<tr><td width="18%">'.$langs->trans('Ref').'</td>';
	  print '<td colspan="2">'.$commande->ref.'</td>';
	  print '<td align="right">'.$langs->trans('Author').' : '.$author->fullname.'</td>';
	  print '</tr>';
	  
	  // Ref commande client
	  print '<tr><td>';
	  print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
	  print $langs->trans('RefCustomer').'</td><td align="left">';
	  print '</td>';
	  if ($_GET['action'] != 'RefCustomerOrder' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=RefCustomerOrder&amp;id='.$commande->id.'">'.img_edit($langs->trans('Edit')).'</a></td>';
	  print '</tr></table>';
	  print '</td><td colspan="3">';
	  if ($user->rights->commande->creer && $_GET['action'] == 'RefCustomerOrder')
	    {
	      print '<form action="fiche.php?id='.$id.'" method="post">';
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
	  

	  // Société
	  print '<tr><td>'.$langs->trans('Company').'</td>';
	  print '<td colspan="3">'.$soc->getNomUrl(1).'</td>';
	  print '</tr>';

			// Ligne info remises tiers
			print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="3">';
			if ($soc->remise_client) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_client);
			else print $langs->trans("CompanyHasNoRelativeDiscount");
			$absolute_discount=$soc->getCurrentDiscount();
			print '. ';
			if ($absolute_discount)
			{
				if ($commande->statut > 0)
				{
					print $langs->trans("CompanyHasAbsoluteDiscount",$absolute_discount,$langs->trans("Currency".$conf->monnaie));
				}
				else
				{
					print '<br>';
					print $html->form_remise_dispo($_SERVER["PHP_SELF"].'?id='.$commande->id,0,'remise_id',$soc->id,$absolute_discount);
				}
			}
			else print $langs->trans("CompanyHasNoAbsoluteDiscount").'.';
			print '</td></tr>';

			// Date
			print '<tr><td>'.$langs->trans('Date').'</td>';
			print '<td colspan="2">'.dolibarr_print_date($commande->date,'daytext').'</td>';
			print '<td width="50%">'.$langs->trans('Source').' : ' . $commande->sources[$commande->source] ;
			if ($commande->source == 0 && $conf->propal->enabled)
			{
				// Si source = propal
				$propal = new Propal($db);
				$propal->fetch($commande->propale_id);
				print ' -> <a href="'.DOL_URL_ROOT.'/comm/propal.php?propalid='.$propal->id.'">'.$propal->ref.'</a>';
			}
			print '</td>';
			print '</tr>';

			// Date de livraison
			if ($conf->expedition->enabled)
			{
				print '<tr><td height="10">';
				print '<table class="nobordernopadding" width="100%"><tr><td>';
				print $langs->trans('DateDelivery');
				print '</td>';

				if ($_GET['action'] != 'editdate_livraison' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetDateDelivery'),1).'</a></td>';
				print '</tr></table>';
				print '</td><td colspan="2">';
				if ($_GET['action'] == 'editdate_livraison')
				{
					print '<form name="setdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$commande->id.'" method="post">';
					print '<input type="hidden" name="action" value="setdate_livraison">';
					$html->select_date($commande->date_livraison,'liv_','','','',"setdate_livraison");
					print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
					print '</form>';
				}
				else
				{
					print dolibarr_print_date($commande->date_livraison,'daytext');
				}
				print '</td>';
				print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
				print nl2br($commande->note_public);
				print '</td>';
				print '</tr>';


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

			// Conditions et modes de réglement
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
			
			//Note public lorsque le module expedition n'est pas activé
			if (!$conf->projet->enabled) $nbrow--;
			if (!$conf->expedition->enabled)
			{
				$nbrow--;
				if ($conf->projet->enabled) $nbrow--;
				print '<td rowspan="'.$nbrow.'" valign="top">'.$langs->trans('NotePublic').' :<br>';
				print nl2br($commande->note_public);
				print '</td>';
			}
			
			print '</tr>';
			print '<tr><td height="10">';
			print '<table class="nobordernopadding" width="100%"><tr><td>';
			print $langs->trans('PaymentMode');
			print '</td>';
			if ($_GET['action'] != 'editmode' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetMode'),1).'</a></td>';
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
				if ($_GET['action'] != 'classer' && $commande->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classer&amp;id='.$commande->id.'">'.img_edit($langs->trans('SetProject')).'</a></td>';
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
			* Lignes de commandes
			*/
			$sql = 'SELECT l.fk_product, l.description, l.price, l.qty, l.rowid, l.tva_tx, ';
			$sql.= ' l.fk_remise_except, l.remise_percent, l.subprice, l.info_bits,';
			$sql.= ' p.label as product, p.ref, p.fk_product_type, p.rowid as prodid, ';
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

					// Ligne en mode visu
					if ($_GET['action'] != 'editline' || $_GET['rowid'] != $objp->rowid)
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
							// Todo: voir si on insert ou pas en option les dates de début et de fin de service
							//print_date_range($objp->date_start,$objp->date_end);
							
							if ($conf->global->PRODUIT_DESC_IN_FORM)
							{
								if ($conf->global->PRODUIT_CHANGE_PROD_DESC)
								{
									print ($objp->description && $objp->description!=$objp->product)?'<br>'.nl2br(stripslashes($objp->description)):'';
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
								print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$commande->socid.'">';
								print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
								print '</a>';
								if ($objp->description)
								{
									if ($objp->description == '(CREDIT_NOTE)')
									{
										include_once(
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
							}
							print '</td>';
						}

						print '<td align="right">'.vatrate($objp->tva_tx).'%</td>';
						print '<td align="right">'.price($objp->subprice).'</td>';
						print '<td align="right">';
						if (($objp->info_bits & 2) != 2)
						{
							print $objp->qty;
						}
						else print '&nbsp;';
						print '</td>';
						if ($objp->remise_percent > 0)
						{
							print '<td align="right">'.$objp->remise_percent.'%</td>';
						}
						else
						{
							print '<td>&nbsp;</td>';
						}
						print '<td align="right">'.price($objp->subprice*$objp->qty*(100-$objp->remise_percent)/100).'</td>';

						// Icone d'edition et suppression
						if ($commande->statut == 0  && $user->rights->commande->creer)
						{
							print '<td align="center">';
							if (($objp->info_bits & 2) == 2)
							{
								// Ligne remise prédéfinie, on permet pas modif
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
								print img_edit();
								print '</a>';
							}
							print '</td>';
							print '<td align="center">';
							if ($conf->global->PRODUIT_CONFIRM_DELETE_LINE)
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=delete_product_line&amp;lineid='.$objp->rowid.'">';
							}
							else
							{
								print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$id.'&amp;action=deleteline&amp;lineid='.$objp->rowid.'">';
							}
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
						print '<input type="hidden" name="action" value="updateligne">';
						print '<input type="hidden" name="id" value="'.$id.'">';
						print '<input type="hidden" name="elrowid" value="'.$_GET['rowid'].'">';
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
							$doleditor=new DolEditor('eldesc',$objp->description,200,'dolibarr_details');
							$doleditor->Create();
						}
						else
						{
							print '<textarea name="eldesc" class="flat" cols="70" rows="1">'.$objp->description.'</textarea>';
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
						print '</form>';
					}

					$total = $total + ($objp->qty * $objp->price);
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
			if ($commande->statut == 0 && $user->rights->commande->creer && $_GET["action"] <> 'editline')
			{
				print '<tr class="liste_titre">';
				print '<td>';
				print '<a name="add"></a>'; // ancre
				print $langs->trans('Description').'</td>';
				print '<td align="right">'.$langs->trans('VAT').'</td>';
				print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td align="right">'.$langs->trans('Qty').'</td>';
				print '<td align="right">'.$langs->trans('ReductionShort').'</td>';
				print '<td colspan="4">&nbsp;</td>';
				print '</tr>';

				// Ajout produit produits/services personnalisés
				print '<form action="fiche.php?id='.$id.'#add" method="post">';
				print '<input type="hidden" name="id" value="'.$id.'">';
				print '<input type="hidden" name="action" value="addligne">';

				$var=true;
				print '<tr '.$bc[$var].'>';
				print '<td>';
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

				print '</form>';

				// Ajout de produits/services prédéfinis
				if ($conf->produit->enabled)
				{
					print '<tr class="liste_titre">';
					print '<td colspan="3">';
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
					print '<input type="hidden" name="id" value="'.$id.'">';
					print '<input type="hidden" name="action" value="addligne">';

				  $var=!$var;
				  print '<tr '.$bc[$var].'>';
				  print '<td colspan="3">';
				  // multiprix
				  if($conf->global->PRODUIT_MULTIPRICES == 1)
				  {
					  $html->select_produits('','idprod','',$conf->produit->limit_size,$soc->price_level);
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
				  print '<td align="right" nowrap="nowrap"><input type="text" size="1" name="remise_percent" value="'.$soc->remise_client.'">%</td>';
				  print '<td align="center" colspan="4"><input type="submit" class="button" value="'.$langs->trans('Add').'"></td>';
				  print '</tr>';

				  print '</form>';
			  }
			}
			print '</table>';
			print '</div>';

			/*
			* Boutons actions
			*/
			if ($user->societe_id == 0 && $_GET['action'] <> 'editline')
			{
				print '<div class="tabsAction">';

				// Valid
				if ($commande->statut == 0)
				{
					if ($user->rights->commande->valider)
					{
						print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=valid">'.$langs->trans('Valid').'</a>';
					}
				}

				// Edit
				if ($commande->statut == 1)
				{
					if ($user->rights->commande->creer)
					{
						print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=modif">'.$langs->trans('Edit').'</a>';
					}
				}

				// Build PDF
				if ($user->rights->commande->creer && $commande->statut < 3)
				{
					print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=builddoc">'.$langs->trans("BuildPDF").'</a>';
				}

				// ReBuild PDF
				if ($user->rights->commande->creer && $commande->statut >= 3)
				{
					print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=builddoc">'.$langs->trans("RebuildPDF").'</a>';
				}

				// Send
				if ($commande->statut > 0)
				{
					if ($user->rights->commande->envoyer)
					{
						$comref = sanitize_string($commande->ref);
						$file = $conf->commande->dir_output . '/'.$comref.'/'.$comref.'.pdf';
						if (file_exists($file))
						{
							print '<a class="butAction" href="fiche.php?id='.$commande->id.'&amp;action=presend">'.$langs->trans('SendByMail').'</a>';
						}
					}
				}

				// Ship
				if ($commande->statut > 0 && $commande->statut < 3 && $user->rights->expedition->creer
				&& $commande->getNbOfProductsLines() > 0)
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

				if ($commande->statut == 1 || $commande->statut == 2)
				{
					if ($user->rights->commande->cloturer)
					{
						print '<a class="butAction" href="fiche.php?id='.$id.'&amp;action=cloture">'.$langs->trans('Close').'</a>';
					}
				}

				if ($commande->statut == 1)
				{
					$nb_expedition = $commande->nb_expedition();
					if ($user->rights->commande->annuler && $nb_expedition == 0)
					{
						print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=annuler">'.$langs->trans('CancelOrder').'</a>';
					}
				}

				if ($commande->statut == 0 && $user->rights->commande->supprimer)
				{
					print '<a class="butActionDelete" href="fiche.php?id='.$id.'&amp;action=delete">'.$langs->trans('Delete').'</a>';
				}

				print '</div>';
			}
			print '<br>';


			print '<table width="100%"><tr><td width="50%" valign="top">';
			print '<a name="builddoc"></a>'; // ancre

			/*
			* Documents générés
			*
			*/
			$comref = sanitize_string($commande->ref);
			$file = $conf->commande->dir_output . '/' . $comref . '/' . $comref . '.pdf';
			$relativepath = $comref.'/'.$comref.'.pdf';
			$filedir = $conf->commande->dir_output . '/' . $comref;
			$urlsource=$_SERVER["PHP_SELF"]."?id=".$commande->id;
			$genallowed=$user->rights->commande->creer;
			$delallowed=$user->rights->commande->supprimer;

			$somethingshown=$html->show_documents('commande',$comref,$filedir,$urlsource,$genallowed,$delallowed,$commande->modelpdf);

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
						print '<td align="center">'.dolibarr_print_date($objp->df).'</td>';
						print '<td align="right">'.$objp->total_ttc.'</td></tr>';
						$i++;
					}
					print '</table>';
				}
			}
			else
			{
				dolibarr_print_error($db);
			}
			print '</td><td valign="top" width="50%">';

			/*
			* Liste des actions propres à la commande
			*/
			$sql = 'SELECT id, '.$db->pdate('a.datea'). ' as da, label, note, fk_user_author' ;
			$sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
			$sql .= ' WHERE a.fk_commande = '.$commande->id ;
			if ($socid) $sql .= ' AND a.fk_soc = '.$socid;
			$resql = $db->query($sql);
			if ($resql)
			{
				$num = $db->num_rows($resql);
				if ($num)
				{
					//print '<br>';
					print_titre($langs->trans('ActionsOnOrder'));
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
				$orderref = sanitize_string($commande->ref);
				$file = $conf->commande->dir_output . '/' . $orderref . '/' . $orderref . '.pdf';

				// Construit PDF si non existant
				if (! is_readable($file))
				{
					if ($_REQUEST['lang_id'])
					{
						$outputlangs = new Translate(DOL_DOCUMENT_ROOT ."/langs",$conf);
						$outputlangs->setDefaultLang($_REQUEST['lang_id']);
					}
					$result=commande_pdf_create($db, $_REQUEST['id'], '', $_REQUEST['model'], $outputlangs);
					if ($result <= 0)
					{
						dolibarr_print_error($db,$result);
						exit;
					}
				}

				print '<br>';
				print_titre($langs->trans('SendOrderByMail'));

				$soc = new Societe($db);
				$soc->fetch($commande->socid);

				$liste[0]="&nbsp;";
				foreach ($soc->thirdparty_and_contact_email_array() as $key=>$value)
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
				$formmail->withtopic=$langs->trans('SendOrderRef','__ORDERREF__');
				$formmail->withfile=1;
				$formmail->withbody=1;
				$formmail->withdeliveryreceipt=1;
				// Tableau des substitutions
				$formmail->substit['__ORDERREF__']=$commande->ref;
				// Tableau des paramètres complémentaires
				$formmail->param['action']='send';
				$formmail->param['models']='order_send';
				$formmail->param['orderid']=$commande->id;
				$formmail->param['returnurl']=DOL_URL_ROOT.'/commande/fiche.php?id='.$commande->id;

				$formmail->show_form();

				print '<br>';
			}
		}
		else
		{
			// Commande non trouvée
			dolibarr_print_error($db);
		}
	}
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
