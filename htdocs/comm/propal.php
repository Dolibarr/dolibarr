<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2011 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
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
 *	\file       	htdocs/comm/propal.php
 *	\ingroup    	propale
 *	\brief      	Page of commercial proposals card and list
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php');
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/propal.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");
if ($conf->projet->enabled)   require_once(DOL_DOCUMENT_ROOT.'/projet/class/project.class.php');

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');

$search_ref=GETPOST('sf_ref')?GETPOST('sf_ref','alpha'):GETPOST('search_ref','alpha');
$search_societe=GETPOST('search_societe','alpha');
$search_montant_ht=GETPOST('search_montant_ht','alpha');

$sall=GETPOST("sall");
$mesg=(GETPOST("msg") ? GETPOST("msg") : GETPOST("mesg"));
$year=GETPOST("year");
$month=GETPOST("month");

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

$object = new Propal($db);

// Security check
$module='propale';
if (isset($socid))
{
	$objectid=$socid;
	$module='societe';
	$dbtable='';
}
else if (isset($id) &&  $id > 0)
{
	$objectid=$id;
	$module='propale';
	$dbtable='propal';
}
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, $module, $objectid, $dbtable);

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->callHooks(array('propalcard'));



/******************************************************************************/
/*                     Actions                                                */
/******************************************************************************/

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
	if (1==0 &&  ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
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
				$mesg=$object->error;
				$action='';
			}
		}
	}
}

// Suppression de la propale
else if ($action == 'confirm_delete' && $confirm == 'yes')
{
	if ($user->rights->propale->supprimer)
	{
		$object->fetch($id);
		$result=$object->delete($user);
		$id = 0;
		$brouillon = 1;

		if ($result > 0)
		{
			Header('Location: '.$_SERVER["PHP_SELF"]);
			exit;
		}
		else
		{
			$langs->load("errors");
			if ($object->error == 'ErrorFailToDeleteDir') $mesg='<div class="error">'.$langs->trans('ErrorFailedToDeleteJoinedFiles').'</div>';
			else $mesg='<div class="error">'.$object->error.'</div>';
		}
	}
}

// Remove line
else if ($action == 'confirm_deleteline' && $confirm == 'yes')
{
	if ($user->rights->propale->creer)
	{
		$object->fetch($id);
		$object->fetch_thirdparty();
		$result = $object->deleteline($lineid);
		// reorder lines
		if ($result) $object->line_order(true);

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
		    propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
		}

		Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
		exit;
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

// Validation
else if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->propale->valider)
{
	$object->fetch($id);
	$object->fetch_thirdparty();

	$result=$object->valid($user);
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
		    propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
		}
	}
	else
	{
		$mesg='<div class="error">'.$object->error.'</div>';
	}
}

else if ($action == 'setdate')
{
    $datep=dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

    if (empty($datep))
    {
        $error++;
        $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")).'</div>';
    }

    if (! $error)
    {
    	$object->fetch($id);
    	$result=$object->set_date($user,$datep);
    	if ($result < 0) dol_print_error($db,$object->error);
    }
}
else if ($action == 'setecheance')
{
	$object->fetch($id);
	$result=$object->set_echeance($user,dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']));
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setdate_livraison')
{
	$object->fetch($id);
	$result=$object->set_date_livraison($user,dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setaddress' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$result=$object->set_adresse_livraison($user,$_POST['fk_address']);
	if ($result < 0) dol_print_error($db,$object->error);
}

// Positionne ref client
else if ($action == 'set_ref_client' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$object->set_ref_client($user, $_POST['ref_client']);
}

// Create proposal
else if ($action == 'add' && $user->rights->propale->creer)
{
	$object->socid=$socid;
	$object->fetch_thirdparty();

	$datep=dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
	$date_delivery=dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']);

	if (empty($datep))
	{
	    $error++;
	    $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")).'</div>';
	    header("Location: ".DOL_URL_ROOT.'/comm/addpropal.php?socid='.$socid.'&action=create&mesg='.urlencode($mesg));
	    exit;
	}

	if (! $error)
	{
    	$db->begin();

    	// Si on a selectionne une propal a copier, on realise la copie
    	if($_POST['createmode']=='copy' && $_POST['copie_propal'])
    	{
    		if ($object->fetch($_POST['copie_propal']) > 0)
    		{
    			$object->ref       				= $_POST['ref'];
    			$object->datep 					= $datep;
    			$object->date_livraison 		= $date_delivery;
    			$object->availability_id 		= $_POST['availability_id'];
    			$object->demand_reason_id       = $_POST['demand_reason_id'];
    			$object->fk_delivery_address 	= $_POST['fk_address'];
    			$object->duree_validite			= $_POST['duree_validite'];
    			$object->cond_reglement_id 		= $_POST['cond_reglement_id'];
    			$object->mode_reglement_id 		= $_POST['mode_reglement_id'];
    			$object->remise_percent 		= $_POST['remise_percent'];
    			$object->remise_absolue 		= $_POST['remise_absolue'];
    			$object->socid    				= $_POST['socid'];
    			$object->contactid 				= $_POST['contactidp'];
    			$object->fk_project				= $_POST['projectid'];
    			$object->modelpdf  				= $_POST['model'];
    			$object->author    				= $user->id;			// deprecated
    			$object->note      				= $_POST['note'];
    			$object->statut    				= 0;

    			$id = $object->create_from($user);
    		}
    		else
    		{
    			$mesg = '<div class="error">'.$langs->trans("ErrorFailedToCopyProposal",$_POST['copie_propal']).'</div>';
    		}
    	}
    	else
    	{
    		$object->ref					= $_POST['ref'];
    		$object->ref_client 			= $_POST['ref_client'];
    		$object->datep 					= $datep;
    		$object->date_livraison 		= $date_delivery;
    		$object->availability_id 		= $_POST['availability_id'];
    		$object->demand_reason_id       = $_POST['demand_reason_id'];
    		$object->fk_delivery_address 	= $_POST['fk_address'];
    		$object->duree_validite 		= $_POST['duree_validite'];
    		$object->cond_reglement_id 		= $_POST['cond_reglement_id'];
    		$object->mode_reglement_id 		= $_POST['mode_reglement_id'];

    		$object->contactid  = $_POST['contactidp'];
    		$object->fk_project = $_POST['projectid'];
    		$object->modelpdf   = $_POST['model'];
    		$object->author     = $user->id;		// deprecated
    		$object->note       = $_POST['note'];

    		$object->origin		= $_POST['origin'];
    		$object->origin_id	= $_POST['originid'];

    		for ($i = 1 ; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i++)
    		{
    			if ($_POST['idprod'.$i])
    			{
    				$xid = 'idprod'.$i;
    				$xqty = 'qty'.$i;
    				$xremise = 'remise'.$i;
    				$object->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
    			}
    		}

    		$id = $object->create($user);
    	}

    	if ($id > 0)
    	{
    		$error=0;

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

    		if (! $error)
    		{
    			$db->commit();

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
    			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

    			Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
    			exit;
    		}
    		else
    		{
    			$db->rollback();
    		}
    	}
    	else
    	{
    		dol_print_error($db,$object->error);
    		$db->rollback();
    		exit;
    	}
	}
}

// Classify billed
else if ($action == 'classifybilled')
{
	$object->fetch($id);
	$object->cloture($user, 4, '');
}

// Reopen proposal
else if ($action == 'confirm_reopen' && $user->rights->propale->cloturer)
{
	if (! $_POST['cancel'])
	{
		$object->fetch($id);
		// prevent browser refresh from reopening proposal several times
		if ($object->statut==2 || $object->statut==3)
		{
			$object->setStatut(1);
		}
	}
}

// Close proposal
else if ($action == 'setstatut' && $user->rights->propale->cloturer)
{
    if (! $_POST['cancel'])
    {
        if (! GETPOST('statut'))
        {
            $mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("CloseAs")).'</div>';
            $action='statut';
            $action='statut';
        }
        else
        {
            $object->fetch($id);
            // prevent browser refresh from closing proposal several times
            if ($object->statut==1)
            {
                $object->cloture($user, $_REQUEST['statut'], $_REQUEST['note']);
            }
        }
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

	$action='presend';
	$_POST["action"]='presend';
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

	$action='presend';
	$_POST["action"]='presend';
}

/*
 * Send mail
 */
if ($action == 'send' && ! $_POST['addfile'] && ! $_POST['removedfile'] && ! $_POST['cancel'])
{
	$langs->load('mails');

	$result=$object->fetch($_POST["id"]);
	$result=$object->fetch_thirdparty();

	if ($result > 0)
	{
		$objectref = dol_sanitizeFileName($object->ref);
		$file = $conf->propale->dir_output . '/' . $objectref . '/' . $objectref . '.pdf';

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
				if ($_POST['receiver'] == 'thirdparty')	// Id of third party
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
					if (dol_strlen($_POST['subject'])) $subject = $_POST['subject'];
					else $subject = $langs->transnoentities('Propal').' '.$object->ref;
					$actiontypecode='AC_PROP';
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

				// Envoi de la propal
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
						$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));	// Must not contain "

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
						$result=$interface->run_triggers('PROPAL_SENTBYMAIL',$object,$user,$langs,$conf);
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
		$mesg='<div class="error">'.$langs->trans('ErrorFailedToReadEntity',$langs->trans("Proposal")).'</div>';
		dol_syslog('Impossible de lire les donnees de la facture. Le fichier propal n\'a peut-etre pas ete genere.');
	}
}

// Go back to draft
if ($action == 'modif' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->set_draft($user);

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

	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
}

else if ($action == "setabsolutediscount" && $user->rights->propale->creer)
{
	if ($_POST["remise_id"])
	{
		$object->id=$id;
		$ret=$object->fetch($id);
		if ($ret > 0)
		{
			$result=$object->insert_discount($_POST["remise_id"]);
			if ($result < 0)
			{
				$mesg='<div class="error">'.$object->error.'</div>';
			}
		}
		else
		{
			dol_print_error($db,$object->error);
		}
	}
}

//Ajout d'une ligne produit dans la propale
else if ($action == "addline" && $user->rights->propale->creer)
{
	$result=0;

	if (empty($_POST['idprod']) && $_POST["type"] < 0)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")).'</div>';
		$result = -1 ;
	}
	if (empty($_POST['idprod']) && (! isset($_POST["np_price"]) || $_POST["np_price"]==''))	// Unit price can be 0 but not ''
	{
		$mesg='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")).'</div>';
		$result = -1 ;
	}

	if ($result >= 0 && isset($_POST['qty']) && (($_POST['np_price']!='' && ($_POST['np_desc'] || $_POST['dp_desc'])) || $_POST['idprod']))
	{
		$ret=$object->fetch($_POST["id"]);
		if ($ret < 0)
		{
			dol_print_error($db,$object->error);
			exit;
		}
		$ret=$object->fetch_thirdparty();

		$price_base_type = 'HT';

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
		if ($_POST['idprod'])
		{
			$prod = new Product($db);
			$prod->fetch($_POST['idprod']);

			$tva_tx = get_default_tva($mysoc,$object->client,$prod->id);
			$localtax1_tx= get_localtax($tva_tx, 1, $object->client);  //get_default_localtax($mysoc,$object->client,1,$prod->id);
			$localtax2_tx= get_localtax($tva_tx, 2, $object->client); //get_default_localtax($mysoc,$object->client,2,$prod->id);
			$tva_npr = get_default_npr($mysoc,$object->client,$prod->id);

			// On defini prix unitaire
			if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)
			{
				$pu_ht  = $prod->multiprices[$object->client->price_level];
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
			$localtax1_tx=get_localtax($tva_tx,1,$object->client);
			$localtax2_tx=get_localtax($tva_tx,2,$object->client);
		}

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;

		if ($price_min && (price2num($pu_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
		{
			$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency)).'</div>' ;
		}
		else
		{
			// Insert line
			$result=$object->addline(
    			$_POST["id"],
    			$desc,
    			$pu_ht,
    			$_POST['qty'],
    			$tva_tx,
    			$localtax1_tx,
    			$localtax2_tx,
    			$_POST['idprod'],
    			$_POST['remise_percent'],
    			$price_base_type,
    			$pu_ttc,
    			$info_bits,
    			$type,
    			-1,
    			0,
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
				    propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
				}

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['np_price']);
				unset($_POST['dp_desc']);
				unset($_POST['np_tva_tx']);
			}
			else
			{
				$mesg='<div class="error">'.$object->error.'</div>';
			}
		}
	}
}

// Mise a jour d'une ligne dans la propale
if ($action == 'updateligne' && $user->rights->propale->creer && $_POST["save"] == $langs->trans("Save"))
{
	if (! $object->fetch($_POST["id"]) > 0)
	{
		dol_print_error($db,$object->error);
		exit;
	}
	$object->fetch_thirdparty();

	// Define info_bits
	$info_bits=0;
	if (preg_match('/\*/',$_POST['tva_tx'])) $info_bits |= 0x01;

	// Define vat_rate
	$vat_rate=$_POST['tva_tx'];
	$vat_rate=str_replace('*','',$vat_rate);
	$localtax1_rate=get_localtax($vat_rate,1,$object->client);
	$localtax2_rate=get_localtax($vat_rate,2,$object->client);
    $up_ht=GETPOST('pu')?GETPOST('pu'):GETPOST('subprice');

    // Define special_code for special lines
    $special_code=0;
    if (empty($_POST['qty'])) $special_code=3;

	// On verifie que le prix minimum est respecte
	$productid = $_POST['productid'] ;
	if ($productid)
	{
		$product = new Product($db);
		$res=$product->fetch($productid);
		$price_min = $product->price_min;
		if ($conf->global->PRODUIT_MULTIPRICES && $object->client->price_level)	$price_min = $product->multiprices_min[$object->client->price_level];
	}
	if ($productid && $price_min && (price2num($up_ht)*(1-price2num($_POST['remise_percent'])/100) < price2num($price_min)))
	{
		$mesg = '<div class="error">'.$langs->trans("CantBeLessThanMinPrice",price2num($price_min,'MU').' '.$langs->trans("Currency".$conf->currency)).'</div>' ;
	}
	else
	{
		$result = $object->updateline(
    		$_POST['lineid'],
    		$up_ht,
    		$_POST['qty'],
    		$_POST['remise_percent'],
    		$vat_rate,
    		$localtax1_rate,
    		$localtax2_rate,
    		$_POST['desc'],
    		'HT',
    		$info_bits,
    		$special_code,
    		$_POST['fk_parent_line']
		);

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
		    propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
		}
	}
}

// Generation doc (depuis lien ou depuis cartouche doc)
else if ($action == 'builddoc' && $user->rights->propale->creer)
{
	$object->fetch($id);
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
	$result=propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);
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

// Set project
else if ($action == 'classin')
{
	$object->fetch($id);
	$object->setProject($_POST['projectid']);
}

// Delai de livraison
else if ($action == 'setavailability')
{
	$object->fetch($id);
	$result = $object->availability($_POST['availability_id']);
}

// Origine de la propale
else if ($action == 'setdemandreason')
{
	$object->fetch($id);
	$result = $object->demand_reason($_POST['demand_reason_id']);
}

// Conditions de reglement
else if ($action == 'setconditions')
{
	$object->fetch($id);
	$result = $object->cond_reglement($_POST['cond_reglement_id']);
}

else if ($action == 'setremisepercent' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$result = $object->set_remise_percent($user, $_POST['remise_percent']);
}

else if ($action == 'setremiseabsolue' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
}

// Mode de reglement
else if ($action == 'setmode')
{
	$object->fetch($id);
	$result = $object->mode_reglement($_POST['mode_reglement_id']);
}

/*
 * Ordonnancement des lignes
 */

else if ($action == 'up' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->line_up(GETPOST('rowid'));

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
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

	Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.GETPOST('rowid'));
	exit;
}

else if ($action == 'down' && $user->rights->propale->creer)
{
	$object->fetch($id);
	$object->fetch_thirdparty();
	$object->line_down(GETPOST('rowid'));

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
	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, GETPOST('hidedetails'), GETPOST('hidedesc'), GETPOST('hideref'), $hookmanager);

	Header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.GETPOST('rowid'));
	exit;
}


/*
 * View
 */

llxHeader('',$langs->trans('Proposal'),'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$companystatic=new Societe($db);

$now=dol_now();

if ($id > 0 || ! empty($ref))
{
	/*
	 * Show object in view mode
	 */

	dol_htmloutput_mesg($mesg,$mesgs);

	$object->fetch($id,$ref);

	$soc = new Societe($db);
	$soc->fetch($object->socid);

	$head = propal_prepare_head($object);
	dol_fiche_head($head, 'comm', $langs->trans('Proposal'), 0, 'propal');

	$formconfirm='';

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
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('ClonePropal'),$langs->trans('ConfirmClonePropal',$object->ref),'confirm_clone',$formquestion,'yes',1);
	}

	// Confirm delete
	if ($action == 'delete')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp',$object->ref), 'confirm_delete','',0,1);
	}

	// Confirm reopen
	if ($action == 'reopen')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenProp',$object->ref), 'confirm_reopen','',0,1);
	}

	// Confirmation delete product/service line
	if ($action == 'ask_deleteline')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline','',0,1);
	}

	// Confirm validate proposal
	if ($action == 'validate')
	{
	    $error=0;

		// on verifie si l'objet est en numerotation provisoire
		$ref = substr($object->ref, 1, 4);
		if ($ref == 'PROV')
		{
			$numref = $object->getNextNumRef($soc);
			if (empty($numref))
			{
			    $error++;
			    dol_htmloutput_errors($object->error);
			}
		}
		else
		{
			$numref = $object->ref;
		}

		$text=$langs->trans('ConfirmValidateProp',$numref);
		if ($conf->notification->enabled)
		{
			require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
			$notify=new Notify($db);
			$text.='<br>';
			$text.=$notify->confirmMessage('NOTIFY_VAL_PROPAL',$object->socid);
		}

		if (! $error) $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProp'), $text, 'confirm_validate','',0,1);
	}

	if (! $formconfirm)
	{
	    $parameters=array('lineid'=>$lineid);
	    $formconfirm=$hookmanager->executeHooks('formconfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	}

	// Print form confirm
	print $formconfirm;


	print '<table class="border" width="100%">';

	$linkback="<a href=\"propal.php?page=$page&socid=$socid&viewstatut=$viewstatut&sortfield=$sortfield&$sortorder\">".$langs->trans("BackToList")."</a>";

	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">';
	print $form->showrefnav($object,'ref',$linkback,1,'ref','ref','');
	print '</td></tr>';

	// Ref client
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td nowrap="nowrap">';
	print $langs->trans('RefCustomer').'</td><td align="left">';
	print '</td>';
	if ($action != 'refclient' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="5">';
	if ($user->rights->propale->creer && $action == 'refclient')
	{
		print '<form action="propal.php?id='.$object->id.'" method="post">';
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

	$rowspan=11;

	// Company
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$soc->getNomUrl(1).'</td>';
	print '</tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
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

	// Date of proposal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Date');
	print '</td>';
	if ($action != 'editdate' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->brouillon && $action == 'editdate')
	{
		print '<form name="editdate" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdate">';
		$form->select_date($object->date,'re','','',0,"editdate");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		if ($object->date)
		{
			print dol_print_date($object->date,'daytext');
		}
		else
		{
			print '&nbsp;';
		}
	}
	print '</td>';

	if ($conf->projet->enabled) $rowspan++;
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS) $rowspan++;

	//Local taxes
	if ($mysoc->country_code=='ES')
	{
		if($mysoc->localtax1_assuj=="1") $rowspan++;
		if($mysoc->localtax2_assuj=="1") $rowspan++;
	}

	// Notes
	print '<td valign="top" colspan="2" width="50%" rowspan="'.$rowspan.'">'.$langs->trans('NotePublic').' :<br>'. nl2br($object->note_public).'</td>';
	print '</tr>';

	// Date end proposal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateEndPropal');
	print '</td>';
	if ($action != 'editecheance' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editecheance&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($object->brouillon && $action == 'editecheance')
	{
		print '<form name="editecheance" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setecheance">';
		$form->select_date($object->fin_validite,'ech','','','',"editecheance");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		if ($object->fin_validite)
		{
			print dol_print_date($object->fin_validite,'daytext');
			if ($object->statut == 1 && $object->fin_validite < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
		}
		else
		{
			print '&nbsp;';
		}
	}
	print '</td>';
	print '</tr>';

    // Payment term
    print '<tr><td>';
    print '<table class="nobordernopadding" width="100%"><tr><td>';
    print $langs->trans('PaymentConditionsShort');
    print '</td>';
    if ($action != 'editconditions' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetConditions'),1).'</a></td>';
    print '</tr></table>';
    print '</td><td colspan="3">';
    if ($action == 'editconditions')
    {
        $form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'cond_reglement_id');
    }
    else
    {
        $form->form_conditions_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->cond_reglement_id,'none');
    }
    print '</td>';
    print '</tr>';

	// Delivery date
	$langs->load('deliveries');
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DeliveryDate');
	print '</td>';
	if ($action != 'editdate_livraison' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDeliveryDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editdate_livraison')
	{
		print '<form name="editdate_livraison" action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="setdate_livraison">';
		$form->select_date($object->date_livraison,'liv_','','','',"editdate_livraison");
		print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
		print '</form>';
	}
	else
	{
		print dol_print_date($object->date_livraison,'daytext');
	}
	print '</td>';
	print '</tr>';

	// Delivery address (deprecated)
	if ($conf->global->PROPALE_ADD_DELIVERY_ADDRESS)
	{
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('DeliveryAddress');
		print '</td>';

		if ($action != 'editdelivery_address' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdelivery_address&amp;socid='.$object->socid.'&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDeliveryAddress'),1).'</a></td>';
		print '</tr></table>';
		print '</td><td colspan="3">';

		if ($action == 'editdelivery_address')
		{
			$form->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,GETPOST('socid'),'fk_address','propal',$object->id);
		}
		else
		{
			$form->form_address($_SERVER['PHP_SELF'].'?id='.$object->id,$object->fk_delivery_address,GETPOST('socid'),'none','propal',$object->id);
		}
		print '</td></tr>';
	}

	// Delivery delay
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('AvailabilityPeriod');
	if ($conf->commande->enabled) print ' ('.$langs->trans('AfterOrder').')';
	print '</td>';
	if ($action != 'editavailability' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editavailability&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetAvailability'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editavailability')
	{
		$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id,$object->availability_id,'availability_id',1);
	}
	else
	{
		$form->form_availability($_SERVER['PHP_SELF'].'?id='.$object->id,$object->availability_id,'none',1);
	}

	print '</td>';
	print '</tr>';

	// Origin of demand
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('Source');
	print '</td>';
	if ($action != 'editdemandreason' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdemandreason&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDemandReason'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	//print $object->demand_reason_id;
	if ($action == 'editdemandreason')
	{
		$form->form_demand_reason($_SERVER['PHP_SELF'].'?id='.$object->id,$object->demand_reason_id,'demand_reason_id',1);
	}
	else
	{
		$form->form_demand_reason($_SERVER['PHP_SELF'].'?id='.$object->id,$object->demand_reason_id,'none');
	}

	print '</td>';
	print '</tr>';

	// Payment mode
	print '<tr>';
	print '<td width="25%">';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('PaymentMode');
	print '</td>';
	if ($action != 'editmode' && $object->brouillon) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMode'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if ($action == 'editmode')
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'mode_reglement_id');
	}
	else
	{
		$form->form_modes_reglement($_SERVER['PHP_SELF'].'?id='.$object->id,$object->mode_reglement_id,'none');
	}
	print '</td></tr>';

	// Project
	if ($conf->projet->enabled)
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		if ($user->rights->propale->creer)
		{
			if ($action != 'classify') print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a></td>';
			print '</tr></table>';
			print '</td><td colspan="3">';
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
		else
		{
			print '</td></tr></table>';
			if (!empty($object->fk_project))
			{
				print '<td colspan="3">';
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				print '<a href="../projet/fiche.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
				print $proj->ref;
				print '</a>';
				print '</td>';
			}
			else {
				print '<td colspan="3">&nbsp;</td>';
			}
		}
		print '</tr>';
	}

	// Insert hooks
	$parameters=array('colspan'=>' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook

	// Amount HT
	print '<tr><td height="10">'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" colspan="2" nowrap><b>'.price($object->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Amount VAT
	print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($object->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Amount Local Taxes
	if ($mysoc->country_code=='ES')
	{
		if ($mysoc->localtax1_assuj=="1") //Localtax1 RE
		{
			print '<tr><td height="10">'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
			print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax1).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
		if ($mysoc->localtax2_assuj=="1") //Localtax2 IRPF
		{
			print '<tr><td height="10">'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
			print '<td align="right" colspan="2" nowrap>'.price($object->total_localtax2).'</td>';
			print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
		}
	}

	// Amount TTC
	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td>';
	print '<td align="right" colspan="2" nowrap>'.price($object->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Statut
	print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="3">'.$object->getLibStatut(4).'</td></tr>';
	print '</table><br>';

	/*
	 * Lines
	 */

	if ($conf->use_javascript_ajax && $object->statut == 0)
	{
		include(DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php');
	}

	// TODO test using div instead of tables
	//print '<div class="table" id="tablelines">';
	print '<table id="tablelines" class="noborder" width="100%">';

	// Show object lines
	$result = $object->getLinesArray();
	if (! empty($object->lines)) $object->printObjectLines($action,$mysoc,$soc,$lineid,0,$hookmanager);

	//print '<table id="tablelines" class="noborder" width="100%">';

	/*
	 * Form to add new line
	 */
	if ($object->statut == 0 && $user->rights->propale->creer)
	{
		if ($action != 'editline')
		{
			$var=true;

			// Add free products/services
			$object->formAddFreeProduct(0,$mysoc,$soc,$hookmanager);

			// Add predefined products/services
			if ($conf->product->enabled || $conf->service->enabled)
			{
				$var=!$var;
				$object->formAddPredefinedProduct(0,$mysoc,$soc,$hookmanager);
			}

			$parameters=array();
			$reshook=$hookmanager->executeHooks('formAddObject',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		}
	}

	// TODO test using div instead of tables
	print '</table>';
	//print '</div>';

	print '</div>';
	print "\n";

	if ($action == 'statut')
	{
		/*
		 * Formulaire cloture (signe ou non)
		 */
		$form_close = '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post">';
		$form_close.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$form_close.= '<table class="border" width="100%">';
        $form_close.= '<tr><td width="150"  align="left">'.$langs->trans("CloseAs").'</td><td align="left">';
        $form_close.= '<input type="hidden" name="action" value="setstatut">';
        $form_close.= '<select id="statut" name="statut" class="flat">';
        $form_close.= '<option value="0">&nbsp;</option>';
        $form_close.= '<option value="2">'.$object->labelstatut[2].'</option>';
        $form_close.= '<option value="3">'.$object->labelstatut[3].'</option>';
        $form_close.= '</select>';
        $form_close.= '</td></tr>';
		$form_close.= '<tr><td width="150" align="left">'.$langs->trans('Note').'</td><td align="left"><textarea cols="70" rows="'.ROWS_3.'" wrap="soft" name="note">';
		$form_close.= $object->note;
		$form_close.= '</textarea></td></tr>';
		$form_close.= '<tr><td align="center" colspan="2">';
		$form_close.= '<input type="submit" class="button" name="validate" value="'.$langs->trans('Validate').'">';
		$form_close.= ' &nbsp; <input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
		$form_close.= '<a name="close">&nbsp;</a>';
		$form_close.= '</td>';
		$form_close.= '</tr></table></form>';

		print $form_close;
	}


	/*
	 * Boutons Actions
	 */
	if ($action != 'presend')
	{
		print '<div class="tabsAction">';

		if ($action != 'statut' && $action <> 'editline')
		{
			// Validate
			if ($object->statut == 0 && $user->rights->propale->valider)
			{
			    if (count($object->lines) > 0) print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a>';
			    else print '<a class="butActionRefused" href="#">'.$langs->trans('Validate').'</a>';
			}

			// Edit
			if ($object->statut == 1 && $user->rights->propale->creer)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a>';
			}

			// ReOpen
			if (($object->statut == 2 || $object->statut == 3) && $user->rights->propale->cloturer)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen'.(empty($conf->global->MAIN_JUMP_TAG)?'':'#reopen').'"';
				print '>'.$langs->trans('ReOpen').'</a>';
			}

			// Send
			if ($object->statut == 1 || $object->statut == 2)
			{
				$propref = dol_sanitizeFileName($object->ref);
				$file = $conf->propale->dir_output . '/'.$propref.'/'.$propref.'.pdf';
				if (file_exists($file))
				{
                    if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propale->propal_advance->send)
                    {
					   print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a>';
                    }
                    else print '<a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a>';
				}
			}

            // Create an order
            if ($conf->commande->enabled && $object->statut == 2 && $user->societe_id == 0)
            {
                if ($user->rights->commande->creer)
                {
                    print '<a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddOrder").'</a>';
                }
            }

            // Create an invoice and classify billed
			if ($object->statut == 2 && $user->societe_id == 0)
			{
				if ($conf->facture->enabled && $user->rights->facture->creer)
				{
					print '<a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddBill").'</a>';
				}

				$arraypropal=$object->getInvoiceArrayList();
				if (is_array($arraypropal) && count($arraypropal) > 0)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled&amp;socid='.$object->socid.'">'.$langs->trans("ClassifyBilled").'</a>';
				}
			}

			// Close
			if ($object->statut == 1 && $user->rights->propale->cloturer)
			{
				print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=statut'.(empty($conf->global->MAIN_JUMP_TAG)?'':'#close').'"';
				print '>'.$langs->trans('Close').'</a>';
			}

			// Clone
			if ($object->type == 0 && $user->rights->propale->creer)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object=propal">'.$langs->trans("ToClone").'</a>';
			}

			// Delete
			if ($user->rights->propale->supprimer)
			{
				print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
				print '>'.$langs->trans('Delete').'</a>';
			}

		}

		print '</div>';
		print "<br>\n";
	}

	if ($action != 'presend')
	{
		print '<table width="100%"><tr><td width="50%" valign="top">';
		print '<a name="builddoc"></a>'; // ancre


		/*
		 * Documents generes
		 */
		$filename=dol_sanitizeFileName($object->ref);
		$filedir=$conf->propale->dir_output . "/" . dol_sanitizeFileName($object->ref);
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed=$user->rights->propale->creer;
		$delallowed=$user->rights->propale->supprimer;

		$var=true;

		$somethingshown=$formfile->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'',0,'',$soc->default_lang,$hookmanager);


		/*
		 * Linked object block
		 */
		$somethingshown=$object->showLinkedObjectBlock();

		print '</td><td valign="top" width="50%">';

		// List of actions on element
		include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php');
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($object,'propal',$socid);

		print '</td></tr></table>';
	}


	/*
	 * Action presend
	 *
	 */
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		$file = $conf->propale->dir_output . '/' . $ref . '/' . $ref . '.pdf';

		print '<br>';
		print_titre($langs->trans('SendPropalByMail'));

		// Create form object
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
		$formmail->withtopic=$langs->trans('SendPropalRef','__PROPREF__');
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		// Tableau des substitutions
		$formmail->substit['__PROPREF__']=$object->ref;
		// Tableau des parametres complementaires
		$formmail->param['action']='send';
		$formmail->param['models']='propal_send';
		$formmail->param['id']=$object->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;

		// Init list of files
		if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,dol_sanitizeFilename($object->ref).'.pdf','application/pdf');
		}

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

	$now=dol_now();

	$sortfield = GETPOST("sortfield",'alpha');
	$sortorder = GETPOST("sortorder",'alpha');
	$page = GETPOST("page",'int');
	if ($page == -1) { $page = 0; }
	$offset = $conf->liste_limit * $page;
	$pageprev = $page - 1;
	$pagenext = $page + 1;

	$viewstatut=$db->escape(GETPOST('viewstatut'));
	$object_statut = $db->escape(GETPOST('propal_statut'));
	if($object_statut != '')
	$viewstatut=$object_statut;

	if (! $sortfield) $sortfield='p.datep';
	if (! $sortorder) $sortorder='DESC';
	$limit = $conf->liste_limit;

	$sql = 'SELECT s.nom, s.rowid, s.client, ';
	$sql.= 'p.rowid as propalid, p.total_ht, p.ref, p.fk_statut, p.fk_user_author, p.datep as dp, p.fin_validite as dfv,';
	if (!$user->rights->societe->client->voir && !$socid) $sql .= " sc.fk_soc, sc.fk_user,";
	$sql.= ' u.login';
	$sql.= ' FROM ('.MAIN_DB_PREFIX.'societe as s, '.MAIN_DB_PREFIX.'propal as p';
	if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= ')';
	if ($sall) $sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'propaldet as pd ON p.rowid=pd.fk_propal';
	$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'user as u ON p.fk_user_author = u.rowid';
	$sql.= ' WHERE p.fk_soc = s.rowid';
	$sql.= ' AND p.entity = '.$conf->entity;

	if (!$user->rights->societe->client->voir && !$socid) //restriction
	{
		$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	}
	if ($search_ref)
	{
		$sql.= " AND p.ref LIKE '%".$db->escape(trim($search_ref))."%'";
	}
	if ($search_societe)
	{
		$sql.= " AND s.nom LIKE '%".$db->escape(trim($search_societe))."%'";
	}
	if ($search_montant_ht)
	{
		$sql.= " AND p.total_ht='".$db->escape(trim($search_montant_ht))."'";
	}
	if ($sall) $sql.= " AND (s.nom LIKE '%".$db->escape($sall)."%' OR p.note LIKE '%".$db->escape($sall)."%' OR pd.description LIKE '%".$db->escape($sall)."%')";
	if ($socid) $sql.= ' AND s.rowid = '.$socid;
	if ($viewstatut <> '')
	{
		$sql.= ' AND p.fk_statut IN ('.$viewstatut.')';
	}
	if ($month > 0)
	{
		if ($year > 0)
		$sql.= " AND date_format(p.datep, '%Y-%m') = '".$year."-".$month."'";
		else
		$sql.= " AND date_format(p.datep, '%m') = '".$month."'";
	}
	if ($year > 0)
	{
		$sql.= " AND date_format(p.datep, '%Y') = '".$year."'";
	}

	$sql.= ' ORDER BY '.$sortfield.' '.$sortorder.', p.ref DESC';
	$sql.= $db->plimit($limit + 1,$offset);
	$result=$db->query($sql);

	if ($result)
	{
		$objectstatic=new Propal($db);
		$userstatic=new User($db);

		$num = $db->num_rows($result);

	 	if ($socid)
		{
			$soc = new Societe($db);
			 $soc->fetch($socid);
		}

		$param='&amp;socid='.$socid.'&amp;viewstatut='.$viewstatut;
		if ($month) $param.='&amp;month='.$month;
		if ($year) $param.='&amp;year='.$year;
		print_barre_liste($langs->trans('ListOfProposals').' '.($socid?'- '.$soc->nom:''), $page,'propal.php',$param,$sortfield,$sortorder,'',$num);

		$i = 0;
		print '<table class="liste" width="100%">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Ref'),$_SERVER["PHP_SELF"],'p.ref','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Company'),$_SERVER["PHP_SELF"],'s.nom','',$param,'',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Date'),$_SERVER["PHP_SELF"],'p.datep','',$param, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('DateEndPropalShort'),$_SERVER["PHP_SELF"],'dfv','',$param, 'align="center"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Price'),$_SERVER["PHP_SELF"],'p.total_ht','',$param, 'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Author'),$_SERVER["PHP_SELF"],'u.login','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans('Status'),$_SERVER["PHP_SELF"],'p.fk_statut','',$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre('');
		print "</tr>\n";
		// Lignes des champs de filtre
		print '<form method="get" action="'.$_SERVER["PHP_SELF"].'">';

		print '<tr class="liste_titre">';
		print '<td class="liste_titre">';
		print '<input class="flat" size="10" type="text" name="search_ref" value="'.$search_ref.'">';
		print '</td>';
		print '<td class="liste_titre" align="left">';
		print '<input class="flat" type="text" size="16" name="search_societe" value="'.$search_societe.'">';
		print '</td>';
		print '<td class="liste_titre" colspan="1" align="center">';
		print $langs->trans('Month').': <input class="flat" type="text" size="1" maxlength="2" name="month" value="'.$month.'">';
		print '&nbsp;'.$langs->trans('Year').': ';
		$syear = $year;
		//if($syear == '') $syear = date("Y");
		$htmlother->select_year($syear,'year',1, 20, 5);
		print '</td>';
		print '<td class="liste_titre" colspan="1">&nbsp;</td>';
		print '<td class="liste_titre" align="right">';
		print '<input class="flat" type="text" size="10" name="search_montant_ht" value="'.$search_montant_ht.'">';
		print '</td>';
		print '<td class="liste_titre">&nbsp;</td>';
		print '<td class="liste_titre" align="right">';
		$form->select_propal_statut($viewstatut,1);
		print '</td>';
		print '<td class="liste_titre" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" value="'.dol_escape_htmltag($langs->trans("Search")).'" title="'.dol_escape_htmltag($langs->trans("Search")).'">';
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

			$objectstatic->id=$objp->propalid;
			$objectstatic->ref=$objp->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td class="nobordernopadding" nowrap="nowrap">';
			print $objectstatic->getNomUrl(1);
			print '</td>';

			print '<td width="20" class="nobordernopadding" nowrap="nowrap">';
			if ($objp->fk_statut == 1 && $objp->dfv < ($now - $conf->propal->cloture->warning_delay)) print img_warning($langs->trans("Late"));
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=dol_sanitizeFileName($objp->ref);
			$filedir=$conf->propale->dir_output . '/' . dol_sanitizeFileName($objp->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$objp->propalid;
			$formfile->show_documents('propal',$filename,$filedir,$urlsource,'','','',1,'',1);
			print '</td></tr></table>';

			if ($objp->client == 1)
			{
				$url = DOL_URL_ROOT.'/comm/fiche.php?socid='.$objp->rowid;
			}
			else
			{
				$url = DOL_URL_ROOT.'/comm/prospect/fiche.php?socid='.$objp->rowid;
			}

			// Company
			$companystatic->id=$objp->rowid;
			$companystatic->nom=$objp->nom;
			$companystatic->client=$objp->client;
			print '<td>';
			print $companystatic->getNomUrl(1,'customer');
			print '</td>';

			// Date propale
			print '<td align="center">';
			$y = dol_print_date($db->jdate($objp->dp),'%Y');
			$m = dol_print_date($db->jdate($objp->dp),'%m');
			$mt= dol_print_date($db->jdate($objp->dp),'%b');
			$d = dol_print_date($db->jdate($objp->dp),'%d');
			print $d."\n";
			print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'&amp;month='.$m.'">';
			print $mt."</a>\n";
			print ' <a href="'.$_SERVER["PHP_SELF"].'?year='.$y.'">';
			print $y."</a></td>\n";

			// Date fin validite
			if ($objp->dfv)
			{
				print '<td align="center">'.dol_print_date($db->jdate($objp->dfv),'day');
				print '</td>';
			}
			else
			{
				print '<td>&nbsp;</td>';
			}

			print '<td align="right">'.price($objp->total_ht)."</td>\n";

			$userstatic->id=$objp->fk_user_author;
			$userstatic->login=$objp->login;
			print '<td align="center">';
			if ($userstatic->id) print $userstatic->getLoginUrl(1);
			else print '&nbsp;';
			print "</td>\n";

			print '<td align="right">'.$objectstatic->LibStatut($objp->fk_statut,5)."</td>\n";

			print '<td>&nbsp;</td>';

			print "</tr>\n";

			$total = $total + $objp->total_ht;
			$subtotal = $subtotal + $objp->total_ht;

			$i++;
		}
		print '</table>';
		$db->free($result);
	}
	else
	{
		dol_print_error($db);
	}
}
$db->close();

llxFooter();

?>
