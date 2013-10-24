<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2013 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne           <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2012 Regis Houssin         <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani      <acianfa@free.fr>
 * Copyright (C) 2010-2013 Juanjo Menent         <jmenent@2byte.es>
 * Copyright (C) 2010-2011 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2012-2013 Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Florian Henry		 <florian.henry@open-concept.pro>
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
 *	\file       	htdocs/comm/propal.php
 *	\ingroup    	propale
 *	\brief      	Page of commercial proposals card and list
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formpropal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/propal.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (! empty($conf->projet->enabled))
{
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

$langs->load('companies');
$langs->load('propal');
$langs->load('compta');
$langs->load('bills');
$langs->load('orders');
$langs->load('products');
$langs->load("deliveries");
if (! empty($conf->margin->enabled))
	$langs->load('margins');

$error=0;

$id=GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$socid=GETPOST('socid','int');
$action=GETPOST('action','alpha');
$origin=GETPOST('origin','alpha');
$originid=GETPOST('originid','int');
$confirm=GETPOST('confirm','alpha');
$lineid=GETPOST('lineid','int');

//PDF
$hidedetails = (GETPOST('hidedetails','int') ? GETPOST('hidedetails','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc 	 = (GETPOST('hidedesc','int') ? GETPOST('hidedesc','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ?  1 : 0));
$hideref 	 = (GETPOST('hideref','int') ? GETPOST('hideref','int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));

// Nombre de ligne pour choix de produit/service predefinis
$NBLINES=4;

// Security check
if (! empty($user->societe_id))	$socid=$user->societe_id;
$result = restrictedArea($user, 'propal', $id);

$object = new Propal($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
if ($id > 0 || ! empty($ref))
{
	$ret=$object->fetch($id, $ref);
	if ($ret > 0) $ret=$object->fetch_thirdparty();
	if ($ret < 0) dol_print_error('',$object->error);
}

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('propalcard'));



/*
 * Actions
*/

$parameters=array('socid'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

// Action clone object
if ($action == 'confirm_clone' && $confirm == 'yes')
{
	if (1==0 &&  ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
	{
		setEventMessage($langs->trans("NoCloneOptionsSpecified"), 'errors');
	}
	else
	{
		if ($object->id > 0)
		{
			$result=$object->createFromClone($socid);
			if ($result > 0)
			{
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
				exit;
			}
			else
			{
				setEventMessage($object->error, 'errors');
				$action='';
			}
		}
	}
}

// Suppression de la propale
else if ($action == 'confirm_delete' && $confirm == 'yes' && $user->rights->propal->supprimer)
{
	$result=$object->delete($user);
	if ($result > 0)
	{
		header('Location: '.DOL_URL_ROOT.'/comm/propal/list.php');
		exit;
	}
	else
	{
		$langs->load("errors");
		setEventMessage($langs->trans($object->error), 'errors');
	}
}

// Remove line
else if ($action == 'confirm_deleteline' && $confirm == 'yes' && $user->rights->propal->creer)
{
	$result = $object->deleteline($lineid);
	// reorder lines
	if ($result) $object->line_order(true);

	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
		// Define output language
		$outputlangs = $langs;
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$outputlangs = new Translate("",$conf);
			$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
			$outputlangs->setDefaultLang($newlang);
		}
		$ret=$object->fetch($id);    // Reload to get new records
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
	exit;
}

// Validation
else if ($action == 'confirm_validate' && $confirm == 'yes' && $user->rights->propal->valider)
{
	$result=$object->valid($user);
	if ($result >= 0)
	{
		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
		{
			// Define output language
			$outputlangs = $langs;
			if (! empty($conf->global->MAIN_MULTILANGS))
			{
				$outputlangs = new Translate("",$conf);
				$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
				$outputlangs->setDefaultLang($newlang);
			}
			$ret=$object->fetch($id);    // Reload to get new records
			propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
		}
	}
	else
	{
		$langs->load("errors");
		setEventMessage($langs->trans($object->error), 'errors');
	}
}

else if ($action == 'setdate' && $user->rights->propal->creer)
{
	$datep=dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);

	if (empty($datep))
	{
		$error++;
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")), 'errors');
	}

	if (! $error)
	{
		$result=$object->set_date($user,$datep);
		if ($result < 0) dol_print_error($db,$object->error);
	}
}
else if ($action == 'setecheance' && $user->rights->propal->creer)
{
	$result=$object->set_echeance($user,dol_mktime(12, 0, 0, $_POST['echmonth'], $_POST['echday'], $_POST['echyear']));
	if ($result < 0) dol_print_error($db,$object->error);
}
else if ($action == 'setdate_livraison' && $user->rights->propal->creer)
{
	$result=$object->set_date_livraison($user,dol_mktime(12, 0, 0, $_POST['liv_month'], $_POST['liv_day'], $_POST['liv_year']));
	if ($result < 0) dol_print_error($db,$object->error);
}

// Positionne ref client
else if ($action == 'set_ref_client' && $user->rights->propal->creer)
{
	$object->set_ref_client($user, $_POST['ref_client']);
}

else if ($action == 'setnote_public' && $user->rights->propal->creer)
{
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES),'_public');
	if ($result < 0) dol_print_error($db,$object->error);
}

else if ($action == 'setnote_private' && $user->rights->propal->creer)
{
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES),'_private');
	if ($result < 0) dol_print_error($db,$object->error);
}

// Create proposal
else if ($action == 'add' && $user->rights->propal->creer)
{
	$object->socid=$socid;
	$object->fetch_thirdparty();

	$datep=dol_mktime(12, 0, 0, GETPOST('remonth'), GETPOST('reday'), GETPOST('reyear'));
	$date_delivery=dol_mktime(12, 0, 0, GETPOST('liv_month'), GETPOST('liv_day'), GETPOST('liv_year'));
	$duration=GETPOST('duree_validite');

	if (empty($datep))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Date")), 'errors');
		$action='create';
		$error++;
	}
	if (empty($duration))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("ValidityDuration")), 'errors');
		$action='create';
		$error++;
	}

	if ($socid<1)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Customer")),'errors');
		$action='create';
		$error++;
	}

	if (! $error)
	{
		$db->begin();

		// Si on a selectionne une propal a copier, on realise la copie
		if (GETPOST('createmode')=='copy' && GETPOST('copie_propal'))
		{
			if ($object->fetch(GETPOST('copie_propal')) > 0)
			{
				$object->ref       				= GETPOST('ref');
				$object->datep 					= $datep;
				$object->date_livraison 		= $date_delivery;
				$object->availability_id 		= GETPOST('availability_id');
				$object->demand_reason_id       = GETPOST('demand_reason_id');
				$object->fk_delivery_address 	= GETPOST('fk_address');
				$object->duree_validite			= $duration;
				$object->cond_reglement_id 		= GETPOST('cond_reglement_id');
				$object->mode_reglement_id 		= GETPOST('mode_reglement_id');
				$object->remise_percent 		= GETPOST('remise_percent');
				$object->remise_absolue 		= GETPOST('remise_absolue');
				$object->socid    				= GETPOST('socid');
				$object->contactid 				= GETPOST('contactidp');
				$object->fk_project				= GETPOST('projectid');
				$object->modelpdf  				= GETPOST('model');
				$object->author    				= $user->id;			// deprecated
				$object->note      				= GETPOST('note');
				$object->statut    				= 0;

				$id = $object->create_from($user);
			}
			else
			{
				setEventMessage($langs->trans("ErrorFailedToCopyProposal",GETPOST('copie_propal')), 'errors');
			}
		}
		else
		{
			$object->ref					= GETPOST('ref');
			$object->ref_client 			= GETPOST('ref_client');
			$object->datep 					= $datep;
			$object->date_livraison 		= $date_delivery;
			$object->availability_id 		= GETPOST('availability_id');
			$object->demand_reason_id       = GETPOST('demand_reason_id');
			$object->fk_delivery_address 	= GETPOST('fk_address');
			$object->duree_validite 		= GETPOST('duree_validite');
			$object->cond_reglement_id 		= GETPOST('cond_reglement_id');
			$object->mode_reglement_id 		= GETPOST('mode_reglement_id');

			$object->contactid  = GETPOST('contactidp');
			$object->fk_project = GETPOST('projectid');
			$object->modelpdf   = GETPOST('model');
			$object->author     = $user->id;		// deprecated
			$object->note       = GETPOST('note');

			$object->origin		= GETPOST('origin');
			$object->origin_id	= GETPOST('originid');

			for ($i = 1; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i++)
			{
				if ($_POST['idprod'.$i])
				{
					$xid = 'idprod'.$i;
					$xqty = 'qty'.$i;
					$xremise = 'remise'.$i;
					$object->add_product($_POST[$xid],$_POST[$xqty],$_POST[$xremise]);
				}
			}

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if($ret < 0) {
				$error++;
				$action = 'create';
			}
		}

		if(!$error) {
			$id = $object->create($user);

			if ($id > 0)
			{
				// Insertion contact par defaut si defini
				if (GETPOST('contactidp') > 0)
				{
					$result=$object->add_contact(GETPOST('contactidp'),'CUSTOMER','external');
					if ($result < 0)
					{
						$error++;
						setEventMessage($langs->trans("ErrorFailedToAddContact"), 'errors');
					}
				}

				if (! $error)
				{
					$db->commit();

					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
					{
						// Define output language
						$outputlangs = $langs;
						if (! empty($conf->global->MAIN_MULTILANGS))
						{
							$outputlangs = new Translate("",$conf);
							$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
							$outputlangs->setDefaultLang($newlang);
						}
						$ret=$object->fetch($id);    // Reload to get new records
						propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}

					header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id);
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
}

// Classify billed
else if ($action == 'classifybilled' && $user->rights->propal->cloturer)
{
	$object->cloture($user, 4, '');
}

// Reopen proposal
else if ($action == 'confirm_reopen' && $user->rights->propal->cloturer && ! GETPOST('cancel'))
{
	// prevent browser refresh from reopening proposal several times
	if ($object->statut==2 || $object->statut==3)
	{
		$object->reopen($user,1);
	}
}

// Close proposal
else if ($action == 'setstatut' && $user->rights->propal->cloturer && ! GETPOST('cancel'))
{
	if (! GETPOST('statut'))
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentities("CloseAs")), 'errors');
		$action='statut';
	}
	else
	{
		// prevent browser refresh from closing proposal several times
		if ($object->statut==1)
		{
			$object->cloture($user, GETPOST('statut'), GETPOST('note'));
		}
	}
}

/*
 * Add file in email form
*/
if (GETPOST('addfile'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory TODO Use a dedicated directory for temp mails files
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	dol_add_file_process($upload_dir_tmp,0,0);
	$action='presend';
}

/*
 * Remove file in email form
*/
if (GETPOST('removedfile'))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir_tmp = $vardir.'/temp';

	// TODO Delete only files that was uploaded from email form
	dol_remove_file_process($_POST['removedfile'],0);
	$action='presend';
}

/*
 * Send mail
*/
if ($action == 'send' && ! GETPOST('addfile') && ! GETPOST('removedfile') && ! GETPOST('cancel'))
{
	$langs->load('mails');

	if ($object->id > 0)
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

			// Create form object
			include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($db);

			$attachedfiles=$formmail->get_attached_files();
			$filepath = $attachedfiles['paths'];
			$filename = $attachedfiles['names'];
			$mimetype = $attachedfiles['mimes'];

			// Envoi de la propal
			require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
			$mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,'',$deliveryreceipt,-1);
			if ($mailfile->error)
			{
				setEventMessage($mailfile->error, 'errors');
			}
			else
			{
				$result=$mailfile->sendfile();
				if ($result)
				{
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
					$result=$interface->run_triggers('PROPAL_SENTBYMAIL',$object,$user,$langs,$conf);
					if ($result < 0) {
						$error++; $this->errors=$interface->errors;
					}
					// Fin appel triggers

					if (! $error)
					{
						// Redirect here
						// This avoid sending mail twice if going out and then back to page
						$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2));
						setEventMessage($mesg);
						header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id);
						exit;
					}
					else
					{
						dol_print_error($db);
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
					setEventMessage($mesg, 'errors');
				}
			}
		}
		else
		{
			$langs->load("other");
			setEventMessage($langs->trans('ErrorMailRecipientIsEmpty').'!', 'errors');
			dol_syslog($langs->trans('ErrorMailRecipientIsEmpty'));
		}
	}
	else
	{
		$langs->load("other");
		setEventMessage($langs->trans('ErrorFailedToReadEntity',$langs->trans("Proposal")), 'errors');
		dol_syslog($langs->trans('ErrorFailedToReadEntity',$langs->trans("Proposal")));
	}
}

// Go back to draft
if ($action == 'modif' && $user->rights->propal->creer)
{
	$object->set_draft($user);

	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
		// Define output language
		$outputlangs = $langs;
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$outputlangs = new Translate("",$conf);
			$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
			$outputlangs->setDefaultLang($newlang);
		}
		$ret=$object->fetch($id);    // Reload to get new records
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}
}

else if ($action == "setabsolutediscount" && $user->rights->propal->creer)
{
	if ($_POST["remise_id"])
	{
		if ($object->id > 0)
		{
			$result=$object->insert_discount($_POST["remise_id"]);
			if ($result < 0)
			{
				setEventMessage($object->error, 'errors');
			}
		}
	}
}

//Ajout d'une ligne produit dans la propale
else if ($action == "addline" && $user->rights->propal->creer)
{
	$idprod=GETPOST('idprod', 'int');
	$product_desc = (GETPOST('product_desc')?GETPOST('product_desc'):(GETPOST('np_desc')?GETPOST('np_desc'):(GETPOST('dp_desc')?GETPOST('dp_desc'):'')));
	$price_ht = GETPOST('price_ht');
	$tva_tx = (GETPOST('tva_tx')?GETPOST('tva_tx'):0);
	$predef=((! empty($idprod) && $conf->global->MAIN_FEATURES_LEVEL < 2) ? '_predef' : '');

	//Extrafields
	$extrafieldsline = new ExtraFields($db);
	$extralabelsline =$extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	$array_option = $extrafieldsline->getOptionalsFromPost($extralabelsline);
	//Unset extrafield
	if (is_array($extralabelsline))
	{
		// Get extra fields
		foreach ($extralabelsline as $key => $value)
		{
			unset($_POST["options_".$key]);
		}
	}

	if (empty($idprod) && GETPOST('type') < 0)
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")), 'errors');
		$error++;
	}

	if ((empty($idprod) || GETPOST('usenewaddlineform')) && $price_ht == '')	// Unit price can be 0 but not ''. Also price can be negative for proposal.
	{
		setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("UnitPriceHT")), 'errors');
		$error++;
	}
	if (empty($idprod) && empty($product_desc))
	{
		setEventMessage($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Description")), 'errors');
		$error++;
	}

	if (! $error && (GETPOST('qty') >= 0) && (! empty($product_desc) || ! empty($idprod)))
	{
		$pu_ht=0;
		$pu_ttc=0;
		$price_min=0;
		$price_base_type = (GETPOST('price_base_type', 'alpha')?GETPOST('price_base_type', 'alpha'):'HT');

		// Ecrase $pu par celui du produit
		// Ecrase $desc par celui du produit
		// Ecrase $txtva par celui du produit
		if (! empty($idprod))
		{
			$prod = new Product($db);
			$prod->fetch($idprod);

			$label = ((GETPOST('product_label') && GETPOST('product_label')!=$prod->label)?GETPOST('product_label'):'');

			// If prices fields are update
			if (GETPOST('usenewaddlineform'))
			{
				$pu_ht=price2num($price_ht, 'MU');
				$pu_ttc=price2num(GETPOST('price_ttc'), 'MU');
				$tva_npr=(preg_match('/\*/', $tva_tx)?1:0);
				$tva_tx=str_replace('*','', $tva_tx);
				$desc = $product_desc;
			}
			else
			{
				$tva_tx = get_default_tva($mysoc,$object->client,$prod->id);
				$tva_npr = get_default_npr($mysoc,$object->client,$prod->id);

				// On defini prix unitaire
				if (! empty($conf->global->PRODUIT_MULTIPRICES) && $object->client->price_level)
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

				// if price ht is forced (ie: calculated by margin rate and cost price)
				if (!empty($price_ht))
				{
					$pu_ht	= price2num($price_ht, 'MU');
					$pu_ttc	= price2num($pu_ht * (1 + ($tva_tx/100)), 'MU');
				}

				// On reevalue prix selon taux tva car taux tva transaction peut etre different
				// de ceux du produit par defaut (par exemple si pays different entre vendeur et acheteur).
				elseif ($tva_tx != $prod->tva_tx)
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

				$desc='';

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

				$desc=dol_concatdesc($desc,$product_desc);

				// Add custom code and origin country into description
				if (empty($conf->global->MAIN_PRODUCT_DISABLE_CUSTOMCOUNTRYCODE) && (! empty($prod->customcode) || ! empty($prod->country_code)))
				{
					$tmptxt='(';
					if (! empty($prod->customcode)) $tmptxt.=$langs->transnoentitiesnoconv("CustomCode").': '.$prod->customcode;
					if (! empty($prod->customcode) && ! empty($prod->country_code)) $tmptxt.=' - ';
					if (! empty($prod->country_code)) $tmptxt.=$langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prod->country_code,0,$db,$langs,0);
					$tmptxt.=')';
					$desc= dol_concatdesc($desc, $tmptxt);
				}
			}

			$type = $prod->type;
		}
		else
		{
			$pu_ht		= price2num($price_ht, 'MU');
			$pu_ttc		= price2num(GETPOST('price_ttc'), 'MU');
			$tva_npr	= (preg_match('/\*/', $tva_tx)?1:0);
			$tva_tx		= str_replace('*', '', $tva_tx);
			$label		= (GETPOST('product_label')?GETPOST('product_label'):'');
			$desc		= $product_desc;
			$type		= GETPOST('type');
		}

		// Margin
		$fournprice=(GETPOST('fournprice')?GETPOST('fournprice'):'');
		$buyingprice=(GETPOST('buying_price')?GETPOST('buying_price'):'');

		$date_start=dol_mktime(0, 0, 0, GETPOST('date_start'.$predef.'month'), GETPOST('date_start'.$predef.'day'), GETPOST('date_start'.$predef.'year'));
		$date_end=dol_mktime(0, 0, 0, GETPOST('date_end'.$predef.'month'), GETPOST('date_end'.$predef.'day'), GETPOST('date_end'.$predef.'year'));

		// Local Taxes
		$localtax1_tx= get_localtax($tva_tx, 1, $object->client);
		$localtax2_tx= get_localtax($tva_tx, 2, $object->client);

		$info_bits=0;
		if ($tva_npr) $info_bits |= 0x01;

		if (! empty($price_min) && (price2num($pu_ht)*(1-price2num(GETPOST('remise_percent'))/100) < price2num($price_min)))
		{
			$mesg = $langs->trans("CantBeLessThanMinPrice",price(price2num($price_min,'MU'),0,$langs,0,0,-1,$conf->currency));
			setEventMessage($mesg, 'errors');
		}
		else
		{
			// Insert line
			$result=$object->addline(
				$desc,
				$pu_ht,
				GETPOST('qty'),
				$tva_tx,
				$localtax1_tx,
				$localtax2_tx,
				$idprod,
				GETPOST('remise_percent'),
				$price_base_type,
				$pu_ttc,
				$info_bits,
				$type,
				-1,
				0,
				GETPOST('fk_parent_line'),
				$fournprice,
				$buyingprice,
				$label,
				$date_start,
				$date_end,
				$array_option
			);

			if ($result > 0)
			{
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
				{
					// Define output language
					$outputlangs = $langs;
					if (! empty($conf->global->MAIN_MULTILANGS))
					{
						$outputlangs = new Translate("",$conf);
						$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
						$outputlangs->setDefaultLang($newlang);
					}
					$ret=$object->fetch($id);    // Reload to get new records
					propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}

				unset($_POST['qty']);
				unset($_POST['type']);
				unset($_POST['idprod']);
				unset($_POST['remise_percent']);
				unset($_POST['price_ht']);
				unset($_POST['price_ttc']);
				unset($_POST['tva_tx']);
				unset($_POST['product_ref']);
				unset($_POST['product_label']);
				unset($_POST['product_desc']);
				unset($_POST['fournprice']);
				unset($_POST['buying_price']);
				unset($_POST['np_marginRate']);
				unset($_POST['np_markRate']);

				// old method
				unset($_POST['np_desc']);
				unset($_POST['dp_desc']);
			}
			else
			{
				setEventMessage($object->error, 'errors');
			}
		}
	}
}

// Mise a jour d'une ligne dans la propale
else if ($action == 'updateligne' && $user->rights->propal->creer && GETPOST('save') == $langs->trans("Save"))
{
	// Define info_bits
	$info_bits=0;
	if (preg_match('/\*/', GETPOST('tva_tx'))) $info_bits |= 0x01;

	// Clean parameters
	$description=dol_htmlcleanlastbr(GETPOST('product_desc'));

	// Define vat_rate
	$vat_rate=(GETPOST('tva_tx')?GETPOST('tva_tx'):0);
	$vat_rate=str_replace('*','',$vat_rate);
	$localtax1_rate=get_localtax($vat_rate,1,$object->client);
	$localtax2_rate=get_localtax($vat_rate,2,$object->client);
	$pu_ht=GETPOST('price_ht');

	// Add buying price
	$fournprice=(GETPOST('fournprice')?GETPOST('fournprice'):'');
	$buyingprice=(GETPOST('buying_price')?GETPOST('buying_price'):'');

	$date_start=dol_mktime(0, 0, 0, GETPOST('date_startmonth'), GETPOST('date_startday'), GETPOST('date_startyear'));
	$date_end=dol_mktime(0, 0, 0, GETPOST('date_endmonth'), GETPOST('date_endday'), GETPOST('date_endyear'));

	//Extrafields
	$extrafieldsline = new ExtraFields($db);
	$extralabelsline =$extrafieldsline->fetch_name_optionals_label($object->table_element_line);
	$array_option = $extrafieldsline->getOptionalsFromPost($extralabelsline);
	//Unset extrafield
	if (is_array($extralabelsline))
	{
		// Get extra fields
		foreach ($extralabelsline as $key => $value)
		{
			unset($_POST["options_".$key]);
		}
	}

	// Define special_code for special lines
	$special_code=0;
	if (! GETPOST('qty')) $special_code=3;

	// Check minimum price
	$productid = GETPOST('productid', 'int');
	if (! empty($productid))
	{
		$product = new Product($db);
		$res=$product->fetch($productid);

		$type=$product->type;

		$price_min = $product->price_min;
		if (! empty($conf->global->PRODUIT_MULTIPRICES) && ! empty($object->client->price_level))
			$price_min = $product->multiprices_min[$object->client->price_level];

		$label = ((GETPOST('update_label') && GETPOST('product_label')) ? GETPOST('product_label'):'');

		if ($price_min && (price2num($pu_ht)*(1-price2num(GETPOST('remise_percent'))/100) < price2num($price_min)))
		{
			setEventMessage($langs->trans("CantBeLessThanMinPrice", price(price2num($price_min,'MU'),0,$langs,0,0,-1,$conf->currency)), 'errors');
			$error++;
		}
	}
	else
	{
		$type = GETPOST('type');
		$label = (GETPOST('product_label') ? GETPOST('product_label'):'');

		// Check parameters
		if (GETPOST('type') < 0) {
			setEventMessage($langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Type")), 'errors');
			$error++;
		}
	}

	if (! $error)
	{
		$result = $object->updateline(
			GETPOST('lineid'),
			$pu_ht,
			GETPOST('qty'),
			GETPOST('remise_percent'),
			$vat_rate,
			$localtax1_rate,
			$localtax2_rate,
			$description,
			'HT',
			$info_bits,
			$special_code,
			GETPOST('fk_parent_line'),
			0,
			$fournprice,
			$buyingprice,
			$label,
			$type,
			$date_start,
			$date_end,
			$array_option
		);

		if ($result >= 0)
		{
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
			{
				// Define output language
				$outputlangs = $langs;
				if (! empty($conf->global->MAIN_MULTILANGS))
				{
					$outputlangs = new Translate("",$conf);
					$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
					$outputlangs->setDefaultLang($newlang);
				}
				$ret=$object->fetch($id);    // Reload to get new records
				propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}

			unset($_POST['qty']);
			unset($_POST['type']);
			unset($_POST['productid']);
			unset($_POST['remise_percent']);
			unset($_POST['price_ht']);
			unset($_POST['price_ttc']);
			unset($_POST['tva_tx']);
			unset($_POST['product_ref']);
			unset($_POST['product_label']);
			unset($_POST['product_desc']);
			unset($_POST['fournprice']);
			unset($_POST['buying_price']);
		}
		else
		{
			setEventMessage($object->error, 'errors');
		}
	}
}

else if ($action == 'updateligne' && $user->rights->propal->creer && GETPOST('cancel') == $langs->trans('Cancel'))
{
	header('Location: '.$_SERVER['PHP_SELF'].'?id='.$object->id);   // Pour reaffichage de la fiche en cours d'edition
	exit;
}

// Generation doc (depuis lien ou depuis cartouche doc)
else if ($action == 'builddoc' && $user->rights->propal->creer)
{
	if (GETPOST('model'))
	{
		$object->setDocModel($user, GETPOST('model'));
	}

	// Define output language
	$outputlangs = $langs;
	if (! empty($conf->global->MAIN_MULTILANGS))
	{
		$outputlangs = new Translate("",$conf);
		$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
		$outputlangs->setDefaultLang($newlang);
	}
	$ret=$object->fetch($id);    // Reload to get new records
	$result=propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);

	if ($result <= 0)
	{
		dol_print_error($db,$result);
		exit;
	}
	else
	{
		header('Location: '.$_SERVER["PHP_SELF"].'?id='.$object->id.(empty($conf->global->MAIN_JUMP_TAG)?'':'#builddoc'));
		exit;
	}
}

// Remove file in doc form
else if ($action == 'remove_file' && $user->rights->propal->creer)
{
	if ($object->id > 0)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$langs->load("other");
		$upload_dir = $conf->propal->dir_output;
		$file = $upload_dir . '/' . GETPOST('file');
		$ret=dol_delete_file($file,0,0,0,$object);
		if ($ret) setEventMessage($langs->trans("FileWasRemoved", GETPOST('file')));
		else setEventMessage($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), 'errors');
	}
}

// Set project
else if ($action == 'classin' && $user->rights->propal->creer)
{
	$object->setProject($_POST['projectid']);
}

// Delai de livraison
else if ($action == 'setavailability' && $user->rights->propal->creer)
{
	$result = $object->availability($_POST['availability_id']);
}

// Origine de la propale
else if ($action == 'setdemandreason' && $user->rights->propal->creer)
{
	$result = $object->demand_reason($_POST['demand_reason_id']);
}

// Conditions de reglement
else if ($action == 'setconditions' && $user->rights->propal->creer)
{
	$result = $object->setPaymentTerms(GETPOST('cond_reglement_id','int'));
}

else if ($action == 'setremisepercent' && $user->rights->propal->creer)
{
	$result = $object->set_remise_percent($user, $_POST['remise_percent']);
}

else if ($action == 'setremiseabsolue' && $user->rights->propal->creer)
{
	$result = $object->set_remise_absolue($user, $_POST['remise_absolue']);
}

// Mode de reglement
else if ($action == 'setmode' && $user->rights->propal->creer)
{
	$result = $object->setPaymentMethods(GETPOST('mode_reglement_id','int'));
}

/*
 * Ordonnancement des lignes
*/

else if ($action == 'up' && $user->rights->propal->creer)
{
	$object->line_up(GETPOST('rowid'));

	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
		// Define output language
		$outputlangs = $langs;
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$outputlangs = new Translate("",$conf);
			$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
			$outputlangs->setDefaultLang($newlang);
		}
		$ret=$object->fetch($id);    // Reload to get new records
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.GETPOST('rowid'));
	exit;
}

else if ($action == 'down' && $user->rights->propal->creer)
{
	$object->line_down(GETPOST('rowid'));

	if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
	{
		// Define output language
		$outputlangs = $langs;
		if (! empty($conf->global->MAIN_MULTILANGS))
		{
			$outputlangs = new Translate("",$conf);
			$newlang=(GETPOST('lang_id') ? GETPOST('lang_id') : $object->client->default_lang);
			$outputlangs->setDefaultLang($newlang);
		}
		$ret=$object->fetch($id);    // Reload to get new records
		propale_pdf_create($db, $object, $object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}

	header('Location: '.$_SERVER["PHP_SELF"].'?id='.$id.'#'.GETPOST('rowid'));
	exit;
}
else if ($action == 'update_extras')
{
	// Fill array 'array_options' with data from update form
	$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	if($ret < 0) {
		$error++;
		$action = 'edit_extras';
	}

	if(!$error) {
		// Actions on extra fields (by external module or standard code)
		// FIXME le hook fait double emploi avec le trigger !!
		$hookmanager->initHooks(array('propaldao'));
		$parameters=array('id'=>$object->id);
		$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
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
}

if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && $user->rights->propal->creer)
{
	if ($action == 'addcontact')
	{
		if ($object->id > 0)
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
				setEventMessage($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), 'errors');
			}
			else
			{
				setEventMessage($object->error, 'errors');
			}
		}
	}

	// Bascule du statut d'un contact
	else if ($action == 'swapstatut')
	{
		if ($object->fetch($id) > 0)
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
		$result = $object->delete_contact($lineid);

		if ($result >= 0)
		{
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		else
		{
			dol_print_error($db);
		}
	}
}


/*
 * View
*/

llxHeader('',$langs->trans('Proposal'),'EN:Commercial_Proposals|FR:Proposition_commerciale|ES:Presupuestos');

$form = new Form($db);
$formother = new FormOther($db);
$formfile = new FormFile($db);
$formpropal = new FormPropal($db);
$companystatic=new Societe($db);

$now=dol_now();

// Add new proposal
if ($action == 'create')
{
	print_fiche_titre($langs->trans("NewProp"));

	$soc = new Societe($db);
	if ($socid>0) $res=$soc->fetch($socid);

	$object = new Propal($db);

	print '<form name="addprop" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	if ($origin != 'project' && $originid)
	{
		print '<input type="hidden" name="origin" value="'.$origin.'">';
		print '<input type="hidden" name="originid" value="'.$originid.'">';
	}

	print '<table class="border" width="100%">';

	// Reference
	print '<tr><td class="fieldrequired">'.$langs->trans('Ref').'</td><td colspan="2">'.$langs->trans("Draft").'</td></tr>';

	// Ref customer
	print '<tr><td>'.$langs->trans('RefCustomer').'</td><td colspan="2">';
	print '<input type="text" name="ref_client" value=""></td>';
	print '</tr>';

	// Third party
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans('Customer').'</td>';
	if($socid>0)
	{
		print '<td colspan="2">';
		print $soc->getNomUrl(1);
		print '<input type="hidden" name="socid" value="'.$soc->id.'">';
		print '</td>';
	}
	else
	{
		print '<td colspan="2">';
		print $form->select_company('','socid','s.client = 1 OR s.client = 2 OR s.client = 3',1);
		print '</td>';
	}
	print '</tr>'."\n";

	// Contacts
	if($socid>0)
	{
		print "<tr><td>".$langs->trans("DefaultContact").'</td><td colspan="2">';
		$form->select_contacts($soc->id,$setcontact,'contactidp',1,$srccontactslist);
		print '</td></tr>';

		// Ligne info remises tiers
		print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="2">';
		if ($soc->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_percent);
		else print $langs->trans("CompanyHasNoRelativeDiscount");
		$absolute_discount=$soc->getAvailableDiscounts();
		print '. ';
		if ($absolute_discount) print $langs->trans("CompanyHasAbsoluteDiscount",price($absolute_discount),$langs->trans("Currency".$conf->currency));
		else print $langs->trans("CompanyHasNoAbsoluteDiscount");
		print '.';
		print '</td></tr>';
	}

	// Date
	print '<tr><td class="fieldrequired">'.$langs->trans('Date').'</td><td colspan="2">';
	$form->select_date('','','','','',"addprop");
	print '</td></tr>';

	// Validaty duration
	print '<tr><td class="fieldrequired">'.$langs->trans("ValidityDuration").'</td><td colspan="2"><input name="duree_validite" size="5" value="'.$conf->global->PROPALE_VALIDITY_DURATION.'"> '.$langs->trans("days").'</td></tr>';

	// Terms of payment
	print '<tr><td class="nowrap fieldrequired">'.$langs->trans('PaymentConditionsShort').'</td><td colspan="2">';
	$form->select_conditions_paiements($soc->cond_reglement_id,'cond_reglement_id');
	print '</td></tr>';

	// Mode of payment
	print '<tr><td>'.$langs->trans('PaymentMode').'</td><td colspan="2">';
	$form->select_types_paiements($soc->mode_reglement_id,'mode_reglement_id');
	print '</td></tr>';

	// What trigger creation
	print '<tr><td>'.$langs->trans('Source').'</td><td>';
	$form->select_demand_reason('','demand_reason_id',"SRC_PROP",1);
	print '</td></tr>';

	// Delivery delay
	print '<tr><td>'.$langs->trans('AvailabilityPeriod').'</td><td colspan="2">';
	$form->select_availability('','availability_id','',1);
	print '</td></tr>';

	// Delivery date (or manufacturing)
	print '<tr><td>'.$langs->trans("DeliveryDate").'</td>';
	print '<td colspan="2">';
	if ($conf->global->DATE_LIVRAISON_WEEK_DELAY != "")
	{
		$tmpdte = time() + ((7 * $conf->global->DATE_LIVRAISON_WEEK_DELAY) * 24 * 60 * 60);
		$syear = date("Y", $tmpdte);
		$smonth = date("m", $tmpdte);
		$sday = date("d", $tmpdte);
		$form->select_date($syear."-".$smonth."-".$sday,'liv_','','','',"addprop");
	}
	else
	{
		$datepropal=empty($conf->global->MAIN_AUTOFILL_DATE)?-1:0;
		$form->select_date($datepropal,'liv_','','','',"addprop");
	}
	print '</td></tr>';

	// Model
	print '<tr>';
	print '<td>'.$langs->trans("DefaultModel").'</td>';
	print '<td colspan="2">';
	$liste=ModelePDFPropales::liste_modeles($db);
	print $form->selectarray('model',$liste,($conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT?$conf->global->PROPALE_ADDON_PDF_ODT_DEFAULT:$conf->global->PROPALE_ADDON_PDF));
	print "</td></tr>";

	// Project
	if (! empty($conf->projet->enabled) && $socid>0)
	{

		$formproject=new FormProjets($db);

		$projectid = 0;
		if ($origin == 'project') $projectid = ($originid?$originid:0);

		print '<tr>';
		print '<td valign="top">'.$langs->trans("Project").'</td><td colspan="2">';

		$numprojet=$formproject->select_projects($soc->id,$projectid);
		if ($numprojet==0)
		{
			$langs->load("projects");
			print ' &nbsp; <a href="../projet/fiche.php?socid='.$soc->id.'&action=create">'.$langs->trans("AddProject").'</a>';
		}
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	$parameters=array('colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit');
	}

	print "</table>";
	print '<br>';

	/*
	 * Combobox pour la fonction de copie
	*/

	if (empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
	{
		print '<input type="hidden" name="createmode" value="empty">';
	}

	print '<table>';
	if (! empty($conf->global->PROPAL_CLONE_ON_CREATE_PAGE))
	{
		// For backward compatibility
		print '<tr>';
		print '<td><input type="radio" name="createmode" value="copy"></td>';
		print '<td>'.$langs->trans("CopyPropalFrom").' </td>';
		print '<td>';
		$liste_propal = array();
		$liste_propal[0] = '';

		$sql ="SELECT p.rowid as id, p.ref, s.nom";
		$sql.=" FROM ".MAIN_DB_PREFIX."propal p";
		$sql.= ", ".MAIN_DB_PREFIX."societe s";
		$sql.= " WHERE s.rowid = p.fk_soc";
		$sql.= " AND p.entity = ".$conf->entity;
		$sql.= " AND p.fk_statut <> 0";
		$sql.= " ORDER BY Id";

		$resql = $db->query($sql);
		if ($resql)
		{
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$row = $db->fetch_row($resql);
				$propalRefAndSocName = $row[1]." - ".$row[2];
				$liste_propal[$row[0]]=$propalRefAndSocName;
				$i++;
			}
			print $form->selectarray("copie_propal",$liste_propal, 0);
		}
		else
		{
			dol_print_error($db);
		}
		print '</td></tr>';

		if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE)) print '<tr><td colspan="3">&nbsp;</td></tr>';

		print '<tr><td valign="top"><input type="radio" name="createmode" value="empty" checked="checked"></td>';
		print '<td valign="top" colspan="2">'.$langs->trans("CreateEmptyPropal").'</td></tr>';
	}

	if (! empty($conf->global->PRODUCT_SHOW_WHEN_CREATE))
	{
		print '<tr><td colspan="3">';
		if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
		{
			$lib=$langs->trans("ProductsAndServices");

			print '<table class="border" width="100%">';
			print '<tr>';
			print '<td>'.$lib.'</td>';
			print '<td>'.$langs->trans("Qty").'</td>';
			print '<td>'.$langs->trans("ReductionShort").'</td>';
			print '</tr>';
			for ($i = 1 ; $i <= $conf->global->PRODUCT_SHOW_WHEN_CREATE; $i++)
			{
				print '<tr><td>';
				// multiprix
				if($conf->global->PRODUIT_MULTIPRICES && $soc->price_level)
					$form->select_produits('',"idprod".$i,'',$conf->product->limit_size,$soc->price_level);
				else
					$form->select_produits('',"idprod".$i,'',$conf->product->limit_size);
				print '</td>';
				print '<td><input type="text" size="2" name="qty'.$i.'" value="1"></td>';
				print '<td><input type="text" size="2" name="remise'.$i.'" value="'.$soc->remise_percent.'">%</td>';
				print '</tr>';
			}

			print "</table>";

		}
		print '</td></tr>';
	}
	print '</table>';
	print '<br>';

	$langs->load("bills");
	print '<center>';
	print '<input type="submit" class="button" value="'.$langs->trans("CreateDraft").'">';
	print '&nbsp;<input type="button" class="button" value="'.$langs->trans("Cancel").'" onClick="javascript:history.go(-1)">';
	print '</center>';

	print "</form>";
}
else
{
	/*
	 * Show object in view mode
	*/

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
		array('type' => 'other', 'name' => 'socid',   'label' => $langs->trans("SelectThirdParty"),   'value' => $form->select_company(GETPOST('socid','int'),'socid','(s.client=1 OR s.client=2 OR s.client=3)'))
		);
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans('ClonePropal'),$langs->trans('ConfirmClonePropal',$object->ref),'confirm_clone',$formquestion,'yes',1);
	}

	// Confirm delete
	else if ($action == 'delete')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteProp'), $langs->trans('ConfirmDeleteProp',$object->ref), 'confirm_delete','',0,1);
	}

	// Confirm reopen
	else if ($action == 'reopen')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenProp',$object->ref), 'confirm_reopen','',0,1);
	}

	// Confirmation delete product/service line
	else if ($action == 'ask_deleteline')
	{
		$formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteProductLine'), $langs->trans('ConfirmDeleteProductLine'), 'confirm_deleteline','',0,1);
	}

	// Confirm validate proposal
	else if ($action == 'validate')
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
		if (! empty($conf->notification->enabled))
		{
			require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
			$notify=new Notify($db);
			$text.='<br>';
			$text.=$notify->confirmMessage('PROPAL_VALIDATE',$object->socid);
		}

		if (! $error) $formconfirm=$form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ValidateProp'), $text, 'confirm_validate','',0,1);
	}

	if (! $formconfirm)
	{
		$parameters=array('lineid'=>$lineid);
		$formconfirm=$hookmanager->executeHooks('formConfirm',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	}

	// Print form confirm
	print $formconfirm;


	print '<table class="border" width="100%">';

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/propal/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

	// Ref
	print '<tr><td>'.$langs->trans('Ref').'</td><td colspan="5">';
	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
	print '</td></tr>';

	// Ref client
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
	print $langs->trans('RefCustomer').'</td>';
	print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('RefCustomer')).'</a></td>';
	print '</td>';
	if ($action != 'refclient' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER['PHP_SELF'].'?action=refclient&amp;id='.$object->id.'">'.img_edit($langs->trans('Modify')).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="5">';
	if ($user->rights->propal->creer && $action == 'refclient')
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
	// Company
	print '<tr><td>'.$langs->trans('Company').'</td><td colspan="5">'.$soc->getNomUrl(1).'</td>';
	print '</tr>';

	// Ligne info remises tiers
	print '<tr><td>'.$langs->trans('Discounts').'</td><td colspan="5">';
	if ($soc->remise_percent) print $langs->trans("CompanyHasRelativeDiscount",$soc->remise_percent);
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
	if ($action != 'editdate' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate&amp;id='.$object->id.'">'.img_edit($langs->trans('SetDate'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if (! empty($object->brouillon) && $action == 'editdate')
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

	// Date end proposal
	print '<tr>';
	print '<td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('DateEndPropal');
	print '</td>';
	if ($action != 'editecheance' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editecheance&amp;id='.$object->id.'">'.img_edit($langs->trans('SetConditions'),1).'</a></td>';
	print '</tr></table>';
	print '</td><td colspan="3">';
	if (! empty($object->brouillon) && $action == 'editecheance')
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
		if (! empty($object->fin_validite))
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
	if ($action != 'editconditions' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editconditions&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetConditions'),1).'</a></td>';
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
	if ($action != 'editdate_livraison' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdate_livraison&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDeliveryDate'),1).'</a></td>';
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

	// Delivery delay
	print '<tr><td>';
	print '<table class="nobordernopadding" width="100%"><tr><td>';
	print $langs->trans('AvailabilityPeriod');
	if (! empty($conf->commande->enabled)) print ' ('.$langs->trans('AfterOrder').')';
	print '</td>';
	if ($action != 'editavailability' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editavailability&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetAvailability'),1).'</a></td>';
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
	if ($action != 'editdemandreason' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editdemandreason&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetDemandReason'),1).'</a></td>';
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
	if ($action != 'editmode' && ! empty($object->brouillon)) print '<td align="right"><a href="'.$_SERVER["PHP_SELF"].'?action=editmode&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetMode'),1).'</a></td>';
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
	if (! empty($conf->projet->enabled))
	{
		$langs->load("projects");
		print '<tr><td>';
		print '<table class="nobordernopadding" width="100%"><tr><td>';
		print $langs->trans('Project').'</td>';
		if ($user->rights->propal->creer)
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
			if (! empty($object->fk_project))
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

	if ($soc->outstanding_limit)
	{
		// Outstanding Bill
		print '<tr><td>';
		print $langs->trans('OutstandingBill');
		print '</td><td align=right colspan=3>';
		print price($soc->get_OutstandingBill()).' / ';
		print price($soc->outstanding_limit, 0, '', 1, -1, -1, $conf->currency);
		print '</td>';
		print '</tr>';
	}

	// Other attributes
	$res=$object->fetch_optionals($object->id,$extralabels);
	$parameters=array('colspan' => ' colspan="3"');
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		if ($action == 'edit_extras')
		{
			print '<form enctype="multipart/form-data" action="'.$_SERVER["PHP_SELF"].'" method="post" name="formsoc">';
			print '<input type="hidden" name="action" value="update_extras">';
			print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
		}

		foreach($extrafields->attribute_label as $key=>$label)
		{
			if ($action == 'edit_extras') {
				$value=(isset($_POST["options_".$key])?$_POST["options_".$key]:$object->array_options["options_".$key]);
			} else {
				$value=$object->array_options["options_".$key];
			}
			if ($extrafields->attribute_type[$key] == 'separate')
			{
				print $extrafields->showSeparator($key);
			}
			else
			{
				print '<tr><td';
				if (! empty($extrafields->attribute_required[$key])) print ' class="fieldrequired"';
				print '>'.$label.'</td><td colspan="5">';
				// Convert date into timestamp format
				if (in_array($extrafields->attribute_type[$key],array('date','datetime')))
				{
					$value = isset($_POST["options_".$key])?dol_mktime($_POST["options_".$key."hour"], $_POST["options_".$key."min"], 0, $_POST["options_".$key."month"], $_POST["options_".$key."day"], $_POST["options_".$key."year"]):$db->jdate($object->array_options['options_'.$key]);
				}

				if ($action == 'edit_extras' && $user->rights->propal->creer)
				{
					print $extrafields->showInputField($key,$value);
				}
				else
				{
					print $extrafields->showOutputField($key,$value);
				}
				print '</td></tr>'."\n";
			}
		}

		if(count($extrafields->attribute_label) > 0) {

			if ($action == 'edit_extras' && $user->rights->propal->creer)
			{
				print '<tr><td></td><td colspan="5">';
				print '<input type="submit" class="button" value="'.$langs->trans('Modify').'">';
				print '</form>';
				print '</td></tr>';

			}
			else {
				if ($object->statut == 0 && $user->rights->propal->creer)
				{
					print '<tr><td></td><td><a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit_extras">'.img_picto('','edit').' '.$langs->trans('Modify').'</a></td></tr>';
				}
			}
		}
	}

	// Amount HT
	print '<tr><td height="10" width="25%">'.$langs->trans('AmountHT').'</td>';
	print '<td align="right" class="nowrap"><b>'.price($object->total_ht).'</b></td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td>';

	// Margin Infos
	if (! empty($conf->margin->enabled)) {
		print '<td valign="top" width="50%" rowspan="4">';
		$object->displayMarginInfos();
		print '</td>';
	}
	print '</tr>';

	// Amount VAT
	print '<tr><td height="10">'.$langs->trans('AmountVAT').'</td>';
	print '<td align="right" class="nowrap">'.price($object->total_tva).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Amount Local Taxes
	if ($mysoc->localtax1_assuj=="1") //Localtax1
	{
		print '<tr><td height="10">'.$langs->transcountry("AmountLT1",$mysoc->country_code).'</td>';
		print '<td align="right" class="nowrap">'.price($object->total_localtax1).'</td>';
		print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
	}
	if ($mysoc->localtax2_assuj=="1") //Localtax2
	{
		print '<tr><td height="10">'.$langs->transcountry("AmountLT2",$mysoc->country_code).'</td>';
		print '<td align="right" class="nowrap">'.price($object->total_localtax2).'</td>';
		print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';
	}


	// Amount TTC
	print '<tr><td height="10">'.$langs->trans('AmountTTC').'</td>';
	print '<td align="right" class="nowrap">'.price($object->total_ttc).'</td>';
	print '<td>'.$langs->trans("Currency".$conf->currency).'</td></tr>';

	// Statut
	print '<tr><td height="10">'.$langs->trans('Status').'</td><td align="left" colspan="2">'.$object->getLibStatut(4).'</td></tr>';

	print '</table><br>';

	if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
	{
		$blocname = 'contacts';
		$title = $langs->trans('ContactsAddresses');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}

	if (! empty($conf->global->MAIN_DISABLE_NOTES_TAB))
	{
		$blocname = 'notes';
		$title = $langs->trans('Notes');
		include DOL_DOCUMENT_ROOT.'/core/tpl/bloc_showhide.tpl.php';
	}


	/*
	 * Lines
	*/

	if (! empty($conf->use_javascript_ajax) && $object->statut == 0)
	{
		include DOL_DOCUMENT_ROOT.'/core/tpl/ajaxrow.tpl.php';
	}

	print '<table id="tablelines" class="noborder" width="100%">';

	// Show object lines
	$result = $object->getLinesArray();
	if (! empty($object->lines))
		$ret=$object->printObjectLines($action,$mysoc,$soc,$lineid,1);

	// Form to add new line
	if ($object->statut == 0 && $user->rights->propal->creer)
	{
		if ($action != 'editline')
		{
			$var=true;

			if ($conf->global->MAIN_FEATURES_LEVEL > 1)
			{
				// Add free or predefined products/services
				$object->formAddObjectLine(1,$mysoc,$soc);
			}
			else
			{
				// Add free products/services
				$object->formAddFreeProduct(1,$mysoc,$soc);

				// Add predefined products/services
				if (! empty($conf->product->enabled) || ! empty($conf->service->enabled))
				{
					$var=!$var;
					$object->formAddPredefinedProduct(1,$mysoc,$soc);
				}
			}

			$parameters=array();
			$reshook=$hookmanager->executeHooks('formAddObjectLine',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		}
	}

	print '</table>';

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
			if ($object->statut == 0 && $object->total_ttc >= 0 && count($object->lines) > 0 && $user->rights->propal->valider)
			{
				if (count($object->lines) > 0) print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=validate">'.$langs->trans('Validate').'</a></div>';
				//else print '<a class="butActionRefused" href="#">'.$langs->trans('Validate').'</a>';
			}
			// Create event
			if ($conf->agenda->enabled && ! empty($conf->global->MAIN_ADD_EVENT_ON_ELEMENT_CARD))	// Add hidden condition because this is not a "workflow" action so should appears somewhere else on page.
			{
				print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/fiche.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddAction").'</a>';
			}
			// Edit
			if ($object->statut == 1 && $user->rights->propal->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=modif">'.$langs->trans('Modify').'</a></div>';
			}

			// ReOpen
			if (($object->statut == 2 || $object->statut == 3) && $user->rights->propal->cloturer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=reopen'.(empty($conf->global->MAIN_JUMP_TAG)?'':'#reopen').'"';
				print '>'.$langs->trans('ReOpen').'</a></div>';
			}

			// Send
			if ($object->statut == 1 || $object->statut == 2)
			{
				if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->propal->propal_advance->send)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=presend&amp;mode=init">'.$langs->trans('SendByMail').'</a></div>';
				}
				else print '<div class="inline-block divButAction"><a class="butActionRefused" href="#">'.$langs->trans('SendByMail').'</a></div>';
			}

			// Create an order
			if (! empty($conf->commande->enabled) && $object->statut == 2 && $user->societe_id == 0)
			{
				if ($user->rights->commande->creer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/fiche.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddOrder").'</a></div>';
				}
			}

			// Create contract
			if ($conf->contrat->enabled && $object->statut == 2 && $user->societe_id == 0)
			{
				$langs->load("contracts");

				if ($user->rights->contrat->creer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/contrat/fiche.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans('AddContract').'</a></div>';
				}
			}

			// Create an invoice and classify billed
			if ($object->statut == 2 && $user->societe_id == 0)
			{
				if (! empty($conf->facture->enabled) && $user->rights->facture->creer)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&amp;origin='.$object->element.'&amp;originid='.$object->id.'&amp;socid='.$object->socid.'">'.$langs->trans("AddBill").'</a></div>';
				}

				$arraypropal=$object->getInvoiceArrayList();
				if (is_array($arraypropal) && count($arraypropal) > 0)
				{
					print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=classifybilled&amp;socid='.$object->socid.'">'.$langs->trans("ClassifyBilled").'</a></div>';
				}
			}

			// Close
			if ($object->statut == 1 && $user->rights->propal->cloturer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=statut'.(empty($conf->global->MAIN_JUMP_TAG)?'':'#close').'"';
				print '>'.$langs->trans('Close').'</a></div>';
			}

			// Clone
			if ($user->rights->propal->creer)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;socid='.$object->socid.'&amp;action=clone&amp;object='.$object->element.'">'.$langs->trans("ToClone").'</a></div>';
			}

			// Delete
			if ($user->rights->propal->supprimer)
			{
				print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete"';
				print '>'.$langs->trans('Delete').'</a></div>';
			}

		}

		print '</div>';
	}
	print "<br>\n";

	if ($action != 'presend')
	{
		print '<div class="fichecenter"><div class="fichehalfleft">';
		//print '<table width="100%"><tr><td width="50%" valign="top">';
		//print '<a name="builddoc"></a>'; // ancre


		/*
		 * Documents generes
		*/
		$filename=dol_sanitizeFileName($object->ref);
		$filedir=$conf->propal->dir_output . "/" . dol_sanitizeFileName($object->ref);
		$urlsource=$_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed=$user->rights->propal->creer;
		$delallowed=$user->rights->propal->supprimer;

		$var=true;

		$somethingshown=$formfile->show_documents('propal',$filename,$filedir,$urlsource,$genallowed,$delallowed,$object->modelpdf,1,0,0,28,0,'',0,'',$soc->default_lang);


		/*
		 * Linked object block
		*/
		$somethingshown=$object->showLinkedObjectBlock();



		print '</div><div class="fichehalfright"><div class="ficheaddleft">';
		//print '</td><td valign="top" width="50%">';


		// List of actions on element
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
		$formactions=new FormActions($db);
		$somethingshown=$formactions->showactions($object,'propal',$socid);


		//print '</td></tr></table>';
		print '</div></div></div>';
	}


	/*
	 * Action presend
	*
	*/
	if ($action == 'presend')
	{
		$ref = dol_sanitizeFileName($object->ref);
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref,'/'));
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

			$result=propale_pdf_create($db, $object, GETPOST('model')?GETPOST('model'):$object->modelpdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
			if ($result <= 0)
			{
				dol_print_error($db,$result);
				exit;
			}
			$fileparams = dol_most_recent_file($conf->propal->dir_output . '/' . $ref, preg_quote($ref,'/'));
			$file=$fileparams['fullname'];
		}

		print '<br>';
		print_titre($langs->trans('SendPropalByMail'));

		// Create form object
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
		$formmail->withtoccc=(! empty($conf->global->MAIN_EMAIL_USECCC)?$conf->global->MAIN_EMAIL_USECCC:false);
		if(empty($object->ref_client))
		{
			$formmail->withtopic=$langs->trans('SendPropalRef','__PROPREF__');
		}
		else if(!empty($object->ref_client))
		{
			$formmail->withtopic=$langs->trans('SendPropalRef','__PROPREF__(__REFCLIENT__)');
		}
		$formmail->withfile=2;
		$formmail->withbody=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withcancel=1;

		// Tableau des substitutions
		$formmail->substit['__PROPREF__']=$object->ref;
		$formmail->substit['__SIGNATURE__']=$user->signature;
		$formmail->substit['__REFCLIENT__']=$object->ref_client;
		$formmail->substit['__PERSONALIZED__']='';
		$formmail->substit['__CONTACTCIVNAME__']='';

		//Find the good contact adress
		$custcontact='';
		$contactarr=array();
		$contactarr=$object->liste_contact(-1,'external');

		if (is_array($contactarr) && count($contactarr)>0) {
			foreach($contactarr as $contact) {
				if ($contact['libelle']==$langs->trans('TypeContact_propal_external_CUSTOMER')) {
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
		$formmail->param['models']='propal_send';
		$formmail->param['id']=$object->id;
		$formmail->param['returnurl']=$_SERVER["PHP_SELF"].'?id='.$object->id;
		// Init list of files
		if (GETPOST("mode")=='init')
		{
			$formmail->clear_attached_files();
			$formmail->add_attached_files($file,basename($file),dol_mimetype($file));
		}

		$formmail->show_form();

		print '<br>';
	}
}

// End of page
llxFooter();
$db->close();
?>
