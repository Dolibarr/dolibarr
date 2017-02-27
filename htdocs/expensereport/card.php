<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2015-2016 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
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
 *  \file       	htdocs/expensereport/card.php
 *  \ingroup    	expensereport
 *  \brief      	Page for trip and expense report card
 */

$res=0;
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/modules/expensereport/modules_expensereport.php';
require_once DOL_DOCUMENT_ROOT . '/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$langs->load("trips");
$langs->load("bills");
$langs->load("mails");

$action=GETPOST('action');
$cancel=GETPOST('cancel');
$confirm = GETPOST('confirm', 'alpha');

$date_start = dol_mktime(0, 0, 0, GETPOST('date_debutmonth'), GETPOST('date_debutday'), GETPOST('date_debutyear'));
$date_end = dol_mktime(0, 0, 0, GETPOST('date_finmonth'), GETPOST('date_finday'), GETPOST('date_finyear'));
$date = dol_mktime(0, 0, 0, GETPOST('datemonth'), GETPOST('dateday'), GETPOST('dateyear'));
$fk_projet=GETPOST('fk_projet');
$vatrate=GETPOST('vatrate');
$ref=GETPOST("ref",'alpha');
$comments=GETPOST('comments');
$fk_c_type_fees=GETPOST('fk_c_type_fees','int');

// If socid provided by ajax company selector
if (! empty($_REQUEST['socid_id']))
{
	$_GET['socid'] = $_GET['socid_id'];
	$_POST['socid'] = $_POST['socid_id'];
	$_REQUEST['socid'] = $_REQUEST['socid_id'];
}

// Security check
$id=GETPOST("id",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'expensereport', 0, 'expensereport');


// Hack to use expensereport dir
$rootfordata = DOL_DATA_ROOT;
$rootforuser = DOL_DATA_ROOT;
// If multicompany module is enabled, we redefine the root of data
if (! empty($conf->multicompany->enabled) && ! empty($conf->entity) && $conf->entity > 1)
{
	$rootfordata.='/'.$conf->entity;
}
$conf->expensereport->dir_output = $rootfordata.'/expensereport';

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));


$object=new ExpenseReport($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('expensereportcard','globalcard'));

$permissionnote = $user->rights->expensereport->creer; 		// Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->expensereport->creer; 	// Used by the include of actions_dellink.inc.php
$permissionedit = $user->rights->expensereport->creer; 		// Used by the include of actions_lineupdown.inc.php




/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
    if ($cancel) 
    {
        $action='';
    	$fk_projet='';
    	$date_start='';
    	$date_end='';
    	$date='';
    	$comments='';
    	$vatrate='';
    	$value_unit='';
    	$qty=1;
    	$fk_c_type_fees=-1;
    }

    include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; 	// Must be include, not include_once

    include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

    include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be include, not include_once

    // Action clone object
    if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->expensereport->creer)
    {
        if (1==0 && ! GETPOST('clone_content') && ! GETPOST('clone_receivers'))
        {
            setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
        }
        else
        {
            if ($object->id > 0)
            {
                // Because createFromClone modifies the object, we must clone it so that we can restore it later
                $orig = clone $object;
    
                $result=$object->createFromClone($socid);
                if ($result > 0)
                {
                    header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
                    exit;
                }
                else
                {
                    setEventMessages($object->error, $object->errors, 'errors');
                    $object = $orig;
                    $action='';
                }
            }
        }
    }
    
    if ($action == 'confirm_delete' && GETPOST("confirm") == "yes" && $id > 0 && $user->rights->expensereport->supprimer)
    {
    	$object = new ExpenseReport($db);
    	$result = $object->fetch($id);
    	$result = $object->delete($user);
    	if ($result >= 0)
    	{
    		header("Location: index.php");
    		exit;
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    if ($action == 'add' && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    
    	$object->date_debut = $date_start;
    	$object->date_fin = $date_end;
    
    	$object->fk_user_author             = GETPOST('fk_user_author','int');
    	if (! ($object->fk_user_author > 0)) $object->fk_user_author = $user->id;
    	
    	$fuser=new User($db);
    	$fuser->fetch($object->fk_user_author);
    	
    	$object->fk_statut = 1;
    	$object->fk_c_paiement				= GETPOST('fk_c_paiement','int');
    	$object->fk_user_validator			= GETPOST('fk_user_validator','int');
    	$object->note_public				= GETPOST('note_public');
    	$object->note_private				= GETPOST('note_private');
    	// Fill array 'array_options' with data from add form
    	if (! $error)
    	{
    	    $ret = $extrafields->setOptionalsFromPost($extralabels, $object);
    	    if ($ret < 0) $error++;
    	}
    	 
    	if ($object->periode_existe($fuser,$object->date_debut,$object->date_fin))
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorDoubleDeclaration"), null, 'errors');
    		$action='create';
    	}
    
    	if (! $error)
    	{
    		$db->begin();
    
    		$id = $object->create($user);
    
    		if ($id > 0)
    		{
    			$db->commit();
    			Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    			exit;
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    			$db->rollback();
    			$action='create';
    		}
    	}
    }
    
    if ($action == 'update' && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	$object->date_debut = $date_start;
    	$object->date_fin = $date_end;
    
    	if($object->fk_statut < 3)
    	{
    		$object->fk_user_validator = GETPOST('fk_user_validator','int');
    	}
    
    	$object->fk_c_paiement = GETPOST('fk_c_paiement','int');
    	$object->note_public = GETPOST('note_public');
    	$object->note_private = GETPOST('note_private');
    	$object->fk_user_modif = $user->id;
    	
    	$result = $object->update($user);
    	if ($result > 0)
    	{
    		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_POST['id']);
    		exit;
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    if ($action == 'update_extras')
    {
        // Fill array 'array_options' with data from update form
        $extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
        $ret = $extrafields->setOptionalsFromPost($extralabels, $object, GETPOST('attribute'));
        if ($ret < 0) $error++;
    
        if (! $error)
        {
            // Actions on extra fields (by external module or standard code)
            $hookmanager->initHooks(array('expensereportdao'));
            $parameters = array('id' => $object->id);
            $reshook = $hookmanager->executeHooks('insertExtraFields', $parameters, $object, $action); // Note that $action and $object may have been modified by
            // some hooks
            if (empty($reshook)) {
                $result = $object->insertExtraFields();
                if ($result < 0) {
                    $error++;
                }
            } else if ($reshook < 0)
                $error++;
        }
    
        if ($error)
            $action = 'edit_extras';
    }
    
    if ($action == "confirm_validate" && GETPOST("confirm") == "yes" && $id > 0 && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    	$result = $object->setValidate($user);
    
    	if ($result > 0)
    	{
    		// Define output language
    		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    		{
    			$outputlangs = $langs;
    			$newlang = '';
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    			if (! empty($newlang)) {
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			}
    			$model=$object->modelpdf;
    			$ret = $object->fetch($id); // Reload to get new records
    
    			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		}
    	}
    
    	if ($result > 0 && $object->fk_user_validator > 0)
    	{
    		$langs->load("mails");
    
    		// TO
    		$destinataire = new User($db);
    		$destinataire->fetch($object->fk_user_validator);
    		$emailTo = $destinataire->email;
    
    		// FROM
    		$expediteur = new User($db);
    		$expediteur->fetch($object->fk_user_author);
    		$emailFrom = $expediteur->email;
    
    		if ($emailTo && $emailFrom)
    		{
    			$filename=array(); $filedir=array(); $mimetype=array();
    
    			// SUBJECT
    			$subject = $langs->trans("ExpenseReportWaitingForApproval");
    
    			// CONTENT
    			$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
    			$message = $langs->trans("ExpenseReportWaitingForApprovalMessage", $expediteur->getFullName($langs), get_date_range($object->date_debut,$object->date_fin,'',$langs), $link);
    
    			// Rebuild pdf
    			/*
    			$object->setDocModel($user,"");
    			$resultPDF = expensereport_pdf_create($db,$id,'',"",$langs);
    
    			if($resultPDF):
    			// ATTACHMENT
    			array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
    			array_push($filedir,$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref) . "/" . dol_sanitizeFileName($object->ref).".pdf");
    			array_push($mimetype,"application/pdf");
    			*/
    
    			// PREPARE SEND
    			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message,$filedir,$mimetype,$filename);
    
    			if ($mailfile)
    			{
    				// SEND
    				$result=$mailfile->sendfile();
    				if ($result)
    				{
    					$mesg=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($emailFrom,2),$mailfile->getValidAddress($emailTo,2));
    					setEventMessages($mesg, null, 'mesgs');
    					header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    					exit;
    				}
    				else
    				{
    					$langs->load("other");
    					if ($mailfile->error)
    					{
    						$mesg='';
    						$mesg.=$langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
    						$mesg.='<br>'.$mailfile->error;
    						setEventMessages($mesg, null, 'errors');
    					}
    					else
    					{
    						setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
    					}
    				}
    			}
    			else
    			{
    				setEventMessages($mailfile->error,$mailfile->errors,'errors');
    				$action='';
    			}
    		}
    		else
    		{
    			setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
    			$action='';
    		}
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    if ($action == "confirm_save_from_refuse" && GETPOST("confirm") == "yes" && $id > 0 && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    	$result = $object->set_save_from_refuse($user);
    
    	if ($result > 0)
    	{
    		// Define output language
    		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    		{
    			$outputlangs = $langs;
    			$newlang = '';
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    			if (! empty($newlang)) {
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			}
    			$model=$object->modelpdf;
    			$ret = $object->fetch($id); // Reload to get new records
    
    			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		}
    	}
    
    	if ($result > 0)
    	{
    		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))  // TODO Translate this so we can remove condition
    		{
    			// Send mail
    
    			// TO
    			$destinataire = new User($db);
    			$destinataire->fetch($object->fk_user_validator);
    			$emailTo = $destinataire->email;
    
    			if ($emailTo)
    			{
    				// FROM
    				$expediteur = new User($db);
    				$expediteur->fetch($object->fk_user_author);
    				$emailFrom = $expediteur->email;
    
    				// SUBJECT
    				$subject = "' ERP - Note de frais à re-approuver";
    
    				// CONTENT
    				$dateRefusEx = explode(" ",$object->date_refuse);
    
    				$message = "Bonjour {$destinataire->firstname},\n\n";
    				$message.= "Le {$dateRefusEx[0]} à {$dateRefusEx[1]} vous avez refusé d'approuver la note de frais \"{$object->ref}\". Vous aviez émis le motif suivant : {$object->detail_refuse}\n\n";
    				$message.= "L'auteur vient de modifier la note de frais, veuillez trouver la nouvelle version en pièce jointe.\n";
    				$message.= "- Déclarant : {$expediteur->firstname} {$expediteur->lastname}\n";
    				$message.= "- Période : du {$object->date_debut} au {$object->date_fin}\n";
    				$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
    				$message.= "Bien cordialement,\n' SI";
    
    				// Génération du pdf avant attachement
    				$object->setDocModel($user,"");
    				$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);
    
    				if($resultPDF)
    				{
    					// ATTACHMENT
    					$filename=array(); $filedir=array(); $mimetype=array();
    					array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
    					array_push($filedir,$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref) . "/" . dol_sanitizeFileName($object->ref_number).".pdf");
    					array_push($mimetype,"application/pdf");
    
    					// PREPARE SEND
    					$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message,$filedir,$mimetype,$filename);
    
    					if (! $mailfile->error)
    					{
    						// SEND
    						$result=$mailfile->sendfile();
    						if ($result)
    						{
    							Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    							exit;
    						}
    						else
    						{
    							$mesg=$mailfile->error;
    							setEventMessages($mesg, null, 'errors');
    						}
    						// END - Send mail
    					}
    					else
    					{
    						dol_print_error($db,$resultPDF);
    						exit;
    					}
    				}
    			}
    		}
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    // Approve
    if ($action == "confirm_approve" && GETPOST("confirm") == "yes" && $id > 0 && $user->rights->expensereport->approve)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	$result = $object->setApproved($user);
    
    	if ($result > 0)
    	{
    		// Define output language
    		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    		{
    			$outputlangs = $langs;
    			$newlang = '';
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    			if (! empty($newlang)) {
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			}
    			$model=$object->modelpdf;
    			$ret = $object->fetch($id); // Reload to get new records
    
    			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		}
    	}
    
    	if ($result > 0)
    	{
    		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))  // TODO Translate this so we can remove condition
    		{
    			// Send mail
    
    			// TO
    			$destinataire = new User($db);
    			$destinataire->fetch($object->fk_user_author);
    			$emailTo = $destinataire->email;
    
    			// CC
    			$emailCC = $conf->global->NDF_CC_EMAILS;
    
    			// FROM
    			$expediteur = new User($db);
    			$expediteur->fetch($object->fk_user_valid);
    			$emailFrom = $expediteur->email;
    
    			// SUBJECT
    			$subject = "' ERP - Note de frais validée";
    
    			// CONTENT
    			$message = "Bonjour {$destinataire->firstname},\n\n";
    			$message.= "Votre note de frais \"{$object->ref}\" vient d'être approuvé!\n";
    			$message.= "- Approbateur : {$expediteur->firstname} {$expediteur->lastname}\n";
    			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
    			$message.= "Bien cordialement,\n' SI";
    
    			// Génération du pdf avant attachement
    			$object->setDocModel($user,"");
    			$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);
    
    			if($resultPDF):
    				// ATTACHMENT
    				$filename=array(); $filedir=array(); $mimetype=array();
    				array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
    				array_push($filedir, $conf->expensereport->dir_output.
    					"/".
    					dol_sanitizeFileName($object->ref) .
    					"/".
    					dol_sanitizeFileName($object->ref).
    					".pdf"
    					);
    				array_push($mimetype,"application/pdf");
    
    				// PREPARE SEND
    				$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message,$filedir,$mimetype,$filename,$emailCC);
    
    				if(!$mailfile->error):
    
    					// SEND
    					$result=$mailfile->sendfile();
    					if ($result):
    						setEventMessages($langs->trans("MailSuccessfulySent",$emailFrom,$emailTo), null, 'mesgs');
    						Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    						exit;
    					else:
    						setEventMessages($langs->trans("ErrorFailedToSendMail",$emailFrom,$emailTo), null, 'errors');
    					endif;
    
    				else:
    					setEventMessages($langs->trans("ErrorFailedToSendMail",$emailFrom,$emailTo), null, 'errors');
    				endif;
    				// END - Send mail
    			else : // if ($resultPDF)
    				dol_print_error($db,$resultPDF);
    				exit;
    			endif;
    		}
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    if ($action == "confirm_refuse" && GETPOST('confirm')=="yes" && $id > 0 && $user->rights->expensereport->approve)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	$result = $object->setDeny($user,GETPOST('detail_refuse'));
    
    	if ($result > 0)
    	{
    		// Define output language
    		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    		{
    			$outputlangs = $langs;
    			$newlang = '';
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    			if (! empty($newlang)) {
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			}
    			$model=$object->modelpdf;
    			$ret = $object->fetch($id); // Reload to get new records
    
    			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		}
    	}
    
    	if ($result > 0)
    	{
    		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))  // TODO Translate this so we can remove condition
    		{
    			// Send mail
    
    			// TO
    			$destinataire = new User($db);
    			$destinataire->fetch($object->fk_user_author);
    			$emailTo = $destinataire->email;
    
    			// FROM
    			$expediteur = new User($db);
    			$expediteur->fetch($object->fk_user_refuse);
    			$emailFrom = $expediteur->email;
    
    			// SUBJECT
    			$subject = "' ERP - Note de frais refusée";
    
    			// CONTENT
    			$message = "Bonjour {$destinataire->firstname},\n\n";
    			$message.= "Votre note de frais \"{$object->ref}\" vient d'être refusée.\n";
    			$message.= "- Refuseur : {$expediteur->firstname} {$expediteur->lastname}\n";
    			$message.= "- Motif de refus : {$_POST['detail_refuse']}\n";
    			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
    			$message.= "Bien cordialement,\n' SI";
    
    			// PREPARE SEND
    			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message);
    
    			if(!$mailfile->error)
    			{
    				// SEND
    				$result=$mailfile->sendfile();
    				if ($result)
    				{
    					setEventMessages($langs->trans("MailSuccessfulySent",$emailFrom,$emailTo), null, 'mesgs');
    					Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    					exit;
    				}
    				else
    				{
    					setEventMessages($langs->trans("ErrorFailedToSendMail",$emailFrom,$emailTo), null, 'errors');
    				}
    				// END - Send mail
    			}
    		}
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    //var_dump($user->id == $object->fk_user_validator);exit;
    if ($action == "confirm_cancel" && GETPOST('confirm')=="yes" && GETPOST('detail_cancel') && $id > 0 && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	if ($user->id == $object->fk_user_valid || $user->id == $object->fk_user_author)
    	{
    		$result = $object->set_cancel($user,GETPOST('detail_cancel'));
    
    		if ($result > 0)
    		{
    			// Define output language
    			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    			{
    				$outputlangs = $langs;
    				$newlang = '';
    				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    				if (! empty($newlang)) {
    					$outputlangs = new Translate("", $conf);
    					$outputlangs->setDefaultLang($newlang);
    				}
    				$model=$object->modelpdf;
    				$ret = $object->fetch($id); // Reload to get new records
    
    				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    			}
    		}
    
    		if ($result > 0)
    		{
    			if (! empty($conf->global->DEPLACEMENT_TO_CLEAN))     // TODO Translate this so we can remove condition
    			{
    				// Send mail
    
    				// TO
    				$destinataire = new User($db);
    				$destinataire->fetch($object->fk_user_author);
    				$emailTo = $destinataire->email;
    
    				// FROM
    				$expediteur = new User($db);
    				$expediteur->fetch($object->fk_user_cancel);
    				$emailFrom = $expediteur->email;
    
    				// SUBJECT
    				$subject = "' ERP - Note de frais annulée";
    
    				// CONTENT
    				$message = "Bonjour {$destinataire->firstname},\n\n";
    				$message.= "Votre note de frais \"{$object->ref}\" vient d'être annulée.\n";
    				$message.= "- Annuleur : {$expediteur->firstname} {$expediteur->lastname}\n";
    				$message.= "- Motif d'annulation : {$_POST['detail_cancel']}\n";
    				$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
    				$message.= "Bien cordialement,\n' SI";
    
    				// PREPARE SEND
    				$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message);
    
    				if(!$mailfile->error)
    				{
    					// SEND
    					$result=$mailfile->sendfile();
    					if ($result)
    					{
    						header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    						exit;
    					}
    					else
    					{
    						$mesg="Impossible d'envoyer l'email.";
    						setEventMessages($mesg, null, 'errors');
    					}
    					// END - Send mail
    				}
    				else
    				{
    					setEventMessages($mail->error, $mail->errors, 'errors');
    				}
    			}
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    		}
    	}
    	else
    	{
    	    setEventMessages($langs->transnoentitiesnoconv("OnlyOwnerCanCancel"), '', 'errors');    // Should not happened
    	}
    }
    
    if ($action == "confirm_brouillonner" && GETPOST('confirm')=="yes" && $id > 0 && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    	if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid)
    	{
    		$result = $object->setStatut(0);
    
    		if ($result > 0)
    		{
    			// Define output language
    			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    			{
    				$outputlangs = $langs;
    				$newlang = '';
    				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    				if (! empty($newlang)) {
    					$outputlangs = new Translate("", $conf);
    					$outputlangs->setDefaultLang($newlang);
    				}
    				$model=$object->modelpdf;
    				$ret = $object->fetch($id); // Reload to get new records
    
    				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    			}
    		}
    
    		if ($result > 0)
    		{
    			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    			exit;
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    		}
    	}
    	else
    	{
    		setEventMessages("NOT_AUTHOR", '', 'errors');
    	}
    }
    
    if ($action == 'set_paid' && $id > 0 && $user->rights->expensereport->to_paid)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	$result = $object->set_paid($id, $user);
    
    	if ($result > 0)
    	{
    		// Define output language
    		if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    		{
    			$outputlangs = $langs;
    			$newlang = '';
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    			if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    			if (! empty($newlang)) {
    				$outputlangs = new Translate("", $conf);
    				$outputlangs->setDefaultLang($newlang);
    			}
    			$model=$object->modelpdf;
    			$ret = $object->fetch($id); // Reload to get new records
    
    			$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    		}
    	}
    
    	if ($result > 0)
    	{
    		if (! empty($conf->global->DEPLACEMENT_TO_CLEAN)) // TODO Translate this so we can remove condition
    		{
    			// Send mail
    
    			// TO
    			$destinataire = new User($db);
    			$destinataire->fetch($object->fk_user_author);
    			$emailTo = $destinataire->email;
    
    			// FROM
    			$expediteur = new User($db);
    			$expediteur->fetch($user->id);
    			$emailFrom = $expediteur->email;
    
    			// SUBJECT
    			$subject = "'ERP - Note de frais payée";
    
    			// CONTENT
    			$message = "Bonjour {$destinataire->firstname},\n\n";
    			$message.= "Votre note de frais \"{$object->ref}\" vient d'être payée.\n";
    			$message.= "- Payeur : {$expediteur->firstname} {$expediteur->lastname}\n";
    			$message.= "- Lien : {$dolibarr_main_url_root}/expensereport/card.php?id={$object->id}\n\n";
    			$message.= "Bien cordialement,\n' SI";
    
    			// Generate pdf before attachment
    			$object->setDocModel($user,"");
    			$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);
    
    			// PREPARE SEND
    			$mailfile = new CMailFile($subject,$emailTo,$emailFrom,$message);
    
    			if(!$mailfile->error):
    
    			// SEND
    			$result=$mailfile->sendfile();
    			if ($result):
    
    			// Retour
    			if($result):
    				Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    				exit;
    			else:
    				dol_print_error($db);
    			endif;
    
    			else:
    			dol_print_error($db,$acct->error);
    			endif;
    
    			else:
                    $mesg="Impossible d'envoyer l'email.";
                    setEventMessages($mesg, null, 'errors');
    			endif;
    			// END - Send mail
    		}
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    if ($action == "addline" && $user->rights->expensereport->creer)
    {
    	$error = 0;
    
    	$db->begin();
    
    	$object_ligne = new ExpenseReportLine($db);
    
    	$vatrate = GETPOST('vatrate');
    	$object_ligne->comments = GETPOST('comments');
    	$qty  = GETPOST('qty','int');
    	if (empty($qty)) $qty=1;
    	$object_ligne->qty = $qty;
    
    	$up=price2num(GETPOST('value_unit'),'MU');
    	$object_ligne->value_unit = $up;
    
    	$object_ligne->date = $date;
    
    	$object_ligne->fk_c_type_fees = GETPOST('fk_c_type_fees');
    
    	// if VAT is not used in Dolibarr, set VAT rate to 0 because VAT rate is necessary.
    	if (empty($vatrate)) $vatrate = "0.000";
    	$object_ligne->vatrate = price2num($vatrate);
    
    	$object_ligne->fk_projet = $fk_projet;
    	
    
    	if (! GETPOST('fk_c_type_fees') > 0)
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
    		$action='';
    	}
    
    	if ($vatrate < 0 || $vatrate == '')
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("VAT")), null, 'errors');
    		$action='';
    	}
    
        /* Projects are never required. To force them, check module forceproject
    	if ($conf->projet->enabled)
    	{
    		if (empty($object_ligne->fk_projet) || $object_ligne->fk_projet==-1)
    		{
    			$error++;
    			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Project")), null, 'errors');
    		}
    	}*/
    
    	// Si aucune date n'est rentrée
    	if (empty($object_ligne->date) || $object_ligne->date=="--")
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
    	}
    	// Si aucun prix n'est rentré
    	if($object_ligne->value_unit==0)
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PriceUTTC")), null, 'errors');
    	}
    
    	// S'il y'a eu au moins une erreur
    	if (! $error)
    	{
    		$object_ligne->fk_expensereport = $_POST['fk_expensereport'];
    
    		$type = 0;	// TODO What if service ?
    		$seller = '';  // seller is unknown
    		$tmp = calcul_price_total($qty, $up, 0, $vatrate, 0, 0, 0, 'TTC', 0, $type, $seller);
    
    		$object_ligne->vatrate = price2num($vatrate);
    		$object_ligne->total_ttc = $tmp[2];
    		$object_ligne->total_ht = $tmp[0];
    		$object_ligne->total_tva = $tmp[1];
    
    		$result = $object_ligne->insert();
    		if ($result > 0)
    		{
    			$db->commit();
    			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    			exit;
    		}
    		else
    		{
    			dol_print_error($db,$object->error);
    			$db->rollback();
    		}
    	}
    
    	$action='';
    }
    
    if ($action == 'confirm_delete_line' && GETPOST("confirm") == "yes" && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	$object_ligne = new ExpenseReportLine($db);
    	$object_ligne->fetch(GETPOST("rowid"));
    	$total_ht = $object_ligne->total_ht;
    	$total_tva = $object_ligne->total_tva;
    
    	$result=$object->deleteline(GETPOST("rowid"), $user);
    	if ($result >= 0)
    	{
    		if ($result > 0)
    		{
    			// Define output language
    			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    			{
    				$outputlangs = $langs;
    				$newlang = '';
    				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    				if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    				if (! empty($newlang)) {
    					$outputlangs = new Translate("", $conf);
    					$outputlangs->setDefaultLang($newlang);
    				}
    				$model=$object->modelpdf;
    				$ret = $object->fetch($id); // Reload to get new records
    
    				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    			}
    		}
    
    		$object->update_totaux_del($object_ligne->total_ht,$object_ligne->total_tva);
    		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$_GET['id']);
    		exit;
    	}
    	else
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
    	}
    }
    
    if ($action == "updateligne" && $user->rights->expensereport->creer)
    {
    	$object = new ExpenseReport($db);
    	$object->fetch($id);
    
    	$rowid = $_POST['rowid'];
    	$type_fees_id = GETPOST('fk_c_type_fees');
    	$projet_id = $fk_projet;
    	$comments = GETPOST('comments');
    	$qty = GETPOST('qty');
    	$value_unit = GETPOST('value_unit');
    	$vatrate = GETPOST('vatrate');

        // if VAT is not used in Dolibarr, set VAT rate to 0 because VAT rate is necessary.
        if (empty($vatrate)) $vatrate = "0.000";
        $vatrate = price2num($vatrate);
    
    	if (! GETPOST('fk_c_type_fees') > 0)
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
    		$action='';
    	}
    	if ((int) $vatrate < 0 || $vatrate == '')
    	{
    		$error++;
    		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Vat")), null, 'errors');
    		$action='';
    	}
    
    	if (! $error)
    	{
    	    // TODO Use update method of ExpenseReportLine
    		$result = $object->updateline($rowid, $type_fees_id, $projet_id, $vatrate, $comments, $qty, $value_unit, $date, $id);
    		if ($result >= 0)
    		{
    			if ($result > 0)
    			{
    				// Define output language
    				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE))
    				{
    					$outputlangs = $langs;
    					$newlang = '';
    					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang = GETPOST('lang_id','alpha');
    					if ($conf->global->MAIN_MULTILANGS && empty($newlang))	$newlang = $object->thirdparty->default_lang;
    					if (! empty($newlang)) {
    						$outputlangs = new Translate("", $conf);
    						$outputlangs->setDefaultLang($newlang);
    					}
    					$model=$object->modelpdf;
    					$ret = $object->fetch($id); // Reload to get new records
    
    					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
    				}
    			}
    
    			$result = $object->recalculer($id);
    
    			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
    			exit;
    		}
    		else
    		{
    			setEventMessages($object->error, $object->errors, 'errors');
    		}
    	}
    }
    
    
    /*
     * Generate or regenerate the PDF document
     */
    if ($action == 'builddoc')	// GET or POST
    {
    	$depl = new ExpenseReport($db, 0, $_GET['id']);
    	$depl->fetch($id);
    
    	if ($_REQUEST['model'])
    	{
    		$depl->setDocModel($user, $_REQUEST['model']);
    	}
    
    	$outputlangs = $langs;
    	if (! empty($_REQUEST['lang_id']))
    	{
    		$outputlangs = new Translate("",$conf);
    		$outputlangs->setDefaultLang($_REQUEST['lang_id']);
    	}
    	$result=expensereport_pdf_create($db, $depl, '', $depl->modelpdf, $outputlangs);
    	if ($result <= 0)
    	{
    		setEventMessages($object->error, $object->errors, 'errors');
            $action='';
    	}
    }
    
    // Remove file in doc form
    else if ($action == 'remove_file')
    {
    	$object = new ExpenseReport($db, 0, $_GET['id']);
    	if ($object->fetch($id))
    	{
    		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    
    		$object->fetch_thirdparty();
    
    		$langs->load("other");
    		$upload_dir = $conf->expensereport->dir_output;
    		$file = $upload_dir . '/' . GETPOST('file');
    		$ret=dol_delete_file($file,0,0,0,$object);
    		if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
    		else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
    		$action='';
    	}
    }
}


/*
 * View
 */

$title=$langs->trans("ExpenseReport") . " - " . $langs->trans("Card");
$helpurl="EN:Module_Expense_Reports";
llxHeader("",$title,$helpurl);

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$projecttmp = new Project($db);

// Create
if ($action == 'create')
{
	print load_fiche_titre($langs->trans("NewTrip"));

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="post" name="create">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';
	print '<tbody>';
	
	// Date start
	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("DateStart").'</td>';
	print '<td>';
	$form->select_date($date_start?$date_start:-1,'date_debut',0,0,0,'',1,1);
	print '</td>';
	print '</tr>';
	
	// Date end
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td>';
	print '<td>';
	$form->select_date($date_end?$date_end:-1,'date_fin',0,0,0,'',1,1);
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("User").'</td>';
	print '<td>';
	$defaultselectuser=$user->id;
	if (GETPOST('fk_user_author') > 0) $defaultselectuser=GETPOST('fk_user_author');
  $include_users = 'hierarchyme';
  if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->expensereport->writeall_advance)) $include_users=array();
	$s=$form->select_dolusers($defaultselectuser, "fk_user_author", 0, "", 0, $include_users);
	print $s;
	print '</td>';
	print '</tr>';
	
	print '<tr>';
	print '<td>'.$langs->trans("VALIDATOR").'</td>';
	print '<td>';
	$object = new ExpenseReport($db);
	$include_users = $object->fetch_users_approver_expensereport();
	if (empty($include_users)) print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateExpenseReport");
	else
	{
    	$defaultselectuser=$user->fk_user;	// Will work only if supervisor has permission to approve so is inside include_users
    	if (! empty($conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR)) $defaultselectuser=$conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR;   // Can force default approver
    	if (GETPOST('fk_user_validator') > 0) $defaultselectuser=GETPOST('fk_user_validator');
    	$s=$form->select_dolusers($defaultselectuser, "fk_user_validator", 1, "", 0, $include_users);
    	print $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
	}
	print '</td>';
	print '</tr>';
	
	// Payment mode
	if (! empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION))
	{
		print '<tr>';
		print '<td>'.$langs->trans("ModePaiement").'</td>';
		print '<td>';
		$form->select_types_paiements(2,'fk_c_paiement');
		print '</td>';
		print '</tr>';
	}

	// Public note
	print '<tr>';
	print '<td class="border" valign="top">' . $langs->trans('NotePublic') . '</td>';
	print '<td class="tdtop">';

	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Private note
	if (empty($user->societe_id)) {
		print '<tr>';
		print '<td class="border" valign="top">' . $langs->trans('NotePrivate') . '</td>';
		print '<td class="tdtop">';

		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, true, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('colspan' => ' colspan="3"');
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	// hook
	if (empty($reshook) && ! empty($extrafields->attribute_label)) {
	    print $object->showOptionals($extrafields, 'edit');
	}
	
	print '<tbody>';
	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" value="'.$langs->trans("AddTrip").'" name="bouton" class="button" />';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)" />';
	print '</div>';

	print '</form>';
}
else
{
	if($id > 0 || $ref)
	{
		$result = $object->fetch($id, $ref);

		$res = $object->fetch_optionals($object->id, $extralabels);
		
		if ($result > 0)
		{
			if (! in_array($object->fk_user_author, $user->getAllChildIds(1)))
			{
				if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)
				    && (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->expensereport->writeall_advance)))
				{
					print load_fiche_titre($langs->trans('TripCard'));

					print '<div class="tabBar">';
					print $langs->trans('NotUserRightToView');
					print '</div>';

					llxFooter();
					$db->close();

					exit;
				}
			}

			$head = expensereport_prepare_head($object);

			if ($action == 'edit' && ($object->fk_statut < 3 || $object->fk_statut==99))
			{
				print "<form name='update' action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="id" value="'.$id.'">';

				dol_fiche_head($head, 'card', $langs->trans("ExpenseReport"), 0, 'trip');

				if($object->fk_statut==99)
				{
					print '<input type="hidden" name="action" value="updateFromRefuse">';
				}
				else
				{
					print '<input type="hidden" name="action" value="update">';
				}

				$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

				print '<table class="border" style="width:100%;">';

				print '<tr>';
				print '<td>'.$langs->trans("User").'</td>';
				print '<td>';
				$userfee=new User($db);
				$userfee->fetch($object->fk_user_author);
				print $userfee->getNomUrl(-1);
				print '</td></tr>';
				
            	// Ref
            	print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td>';
            	print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
            	print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("DateStart").'</td>';
				print '<td>';
				$form->select_date($object->date_debut,'date_debut');
				print '</td>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("DateEnd").'</td>';
				print '<td>';
				$form->select_date($object->date_fin,'date_fin');
				print '</td>';
				print '</tr>';

				if (! empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION))
				{
					print '<tr>';
					print '<td>'.$langs->trans("ModePaiement").'</td>';
					print '<td>';
					$form->select_types_paiements($object->fk_c_paiement,'fk_c_paiement');
					print '</td>';
					print '</tr>';
				}

				if($object->fk_statut<3)
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDATOR").'</td>';	// Approbator
					print '<td>';
					$include_users = $object->fetch_users_approver_expensereport();
					$s=$form->select_dolusers($object->fk_user_validator,"fk_user_validator",1,"",0,$include_users);
					print $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
					print '</td>';
					print '</tr>';
				}
				else
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDOR").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_valid);
					print $userfee->getNomUrl(-1);
					print '</td></tr>';
				}

				if ($object->fk_statut==6)
				{
					print '<tr>';
					print '<td>'.$langs->trans("AUTHORPAIEMENT").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($user->id);
					print $userfee->getNomUrl(-1);
					print '</td></tr>';

				}

				// Other attributes
				//$cols = 3;
				//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

				print '</table>';

				dol_fiche_end();

				print '<div class="center">';
				print '<input type="submit" value="'.$langs->trans("Modify").'" name="bouton" class="button">';
				print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" value="'.$langs->trans("Cancel").'" class="button" onclick="history.go(-1)" />';
				print '</div>';

				print '</form>';
			}
			else
			{
				dol_fiche_head($head, 'card', $langs->trans("ExpenseReport"), 0, 'trip');

				// Clone confirmation
				if ($action == 'clone') {
				    // Create an array for form
				    $formquestion = array(
				        // 'text' => $langs->trans("ConfirmClone"),
				        // array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
				        // 1),
				        // array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value'
				        // => 1),
                    );
				    // Paiement incomplet. On demande si motif = escompte ou autre
				    $formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $object->id, $langs->trans('CloneExpenseReport'), $langs->trans('ConfirmCloneExpenseReport', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
				}
				
				if ($action == 'save')
				{
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("SaveTrip"),$langs->trans("ConfirmSaveTrip"),"confirm_validate","","",1);
				}

				if ($action == 'save_from_refuse')
				{
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("SaveTrip"),$langs->trans("ConfirmSaveTrip"),"confirm_save_from_refuse","","",1);
				}

				if ($action == 'delete')
				{
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("DeleteTrip"),$langs->trans("ConfirmDeleteTrip"),"confirm_delete","","",1);
				}

				if ($action == 'validate')
				{
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("ValideTrip"),$langs->trans("ConfirmValideTrip"),"confirm_approve","","",1);
				}

				if ($action == 'paid')
				{
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("PaidTrip"),$langs->trans("ConfirmPaidTrip"),"confirm_paid","","",1);
				}

				if ($action == 'cancel')
				{
					$array_input = array('text'=>$langs->trans("ConfirmCancelTrip"), array('type'=>"text",'label'=>$langs->trans("Comment"),'name'=>"detail_cancel",'size'=>"50",'value'=>""));
					$formconfirm=$form->form_confirm($_SEVER["PHP_SELF"]."?id=".$id,$langs->trans("Cancel"),"","confirm_cancel",$array_input,"",1);
				}

				if ($action == 'brouillonner')
				{
				    $formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("BrouillonnerTrip"),$langs->trans("ConfirmBrouillonnerTrip"),"confirm_brouillonner","","",1);
				}

				if ($action == 'refuse')		// Deny
				{
					$array_input = array('text'=>$langs->trans("ConfirmRefuseTrip"), array('type'=>"text",'label'=>$langs->trans("Comment"),'name'=>"detail_refuse",'size'=>"50",'value'=>""));
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id,$langs->trans("Deny"),'',"confirm_refuse",$array_input,"yes",1);
				}

				if ($action == 'delete_line')
				{
					$formconfirm=$form->form_confirm($_SERVER["PHP_SELF"]."?id=".$id."&rowid=".GETPOST('rowid'),$langs->trans("DeleteLine"),$langs->trans("ConfirmDeleteLine"),"confirm_delete_line",'','yes',1);
				}

				// Print form confirm
				print $formconfirm;
				
				
				// Expense report card
				
				$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
				
				
				$morehtmlref='<div class="refidno">';
				/*
				// Ref customer
				$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->commande->creer, 'string', '', 0, 1);
				$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, $user->rights->commande->creer, 'string', '', null, null, '', 1);
				// Thirdparty
				$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $soc->getNomUrl(1);
				// Project
				if (! empty($conf->projet->enabled))
				{
				    $langs->load("projects");
				    $morehtmlref.='<br>'.$langs->trans('Project') . ' ';
				    if ($user->rights->commande->creer)
				    {
				        if ($action != 'classify')
				            $morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
				            if ($action == 'classify') {
				                //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				                $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				                $morehtmlref.='<input type="hidden" name="action" value="classin">';
				                $morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				                $morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				                $morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				                $morehtmlref.='</form>';
				            } else {
				                $morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
				            }
				    } else {
				        if (! empty($object->fk_project)) {
				            $proj = new Project($db);
				            $proj->fetch($object->fk_project);
				            $morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $object->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
				            $morehtmlref.=$proj->ref;
				            $morehtmlref.='</a>';
				        } else {
				            $morehtmlref.='';
				        }
				    }
				}*/
				$morehtmlref.='</div>';
				
				
				dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);
				
				
				print '<div class="fichecenter">';
				print '<div class="fichehalfleft">';
				print '<div class="underbanner clearboth"></div>';
				
				print '<table class="border centpercent">';

				// Author
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("User").'</td>';
				print '<td>';
				if ($object->fk_user_author > 0)
				{
				    $userauthor=new User($db);
				    $result=$userauthor->fetch($object->fk_user_author);
				    if ($result < 0) dol_print_error('',$userauthor->error);
				    print $userauthor->getNomUrl(-1);
				}
				print '</td></tr>';
				
				print '<tr>';
				print '<td class="titlefield">'.$langs->trans("Period").'</td>';
				print '<td>';
				print get_date_range($object->date_debut,$object->date_fin,'day',$langs,0);
				print '</td>';
				print '</tr>';
				if (! empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION))
				{
					print '<tr>';
					print '<td>'.$langs->trans("ModePaiement").'</td>';
					print '<td>'.$object->libelle_paiement.'</td>';
					print '</tr>';
				}

				// Amount
				print '<tr>';
				print '<td>'.$langs->trans("AmountHT").'</td>';
				print '<td>'.price($object->total_ht).'</td>';
				$rowspan = 5;
				if ($object->fk_statut < 3) $rowspan++;
				elseif($object->fk_statut == 4) $rowspan+=2;
				else $rowspan+=2;
				if ($object->fk_statut==99 || !empty($object->detail_refuse)) $rowspan+=2;
				if($object->fk_statut==6) $rowspan+=2;
				print "</td>";
				print '</tr>';
				
				print '<tr>';
				print '<td>'.$langs->trans("AmountVAT").'</td>';
				print '<td>'.price($object->total_tva).'</td>';
				print '</tr>';
				
				print '<tr>';
				print '<td>'.$langs->trans("AmountTTC").'</td>';
				print '<td>'.price($object->total_ttc).'</td>';
				print '</tr>';

				// Validation date
				print '<tr>';
				print '<td>'.$langs->trans("DATE_SAVE").'</td>';
				print '<td>'.dol_print_date($object->date_valid,'dayhour');
				if ($object->status == 2 && $object->hasDelay('toapprove')) print ' '.img_warning($langs->trans("Late"));
				if ($object->status == 5 && $object->hasDelay('topay')) print ' '.img_warning($langs->trans("Late"));
				print '</td></tr>';
				print '</tr>';

				// User to inform for approval
				if ($object->fk_statut < 3)	// informed
				{
					print '<tr>';
					print '<td>'.$langs->trans("VALIDATOR").'</td>';   // approver
					print '<td>';
					if ($object->fk_user_validator > 0)
					{
						$userfee=new User($db);
						$userfee->fetch($object->fk_user_validator);
						print $userfee->getNomUrl(-1);
						if (empty($userfee->email) || ! isValidEmail($userfee->email)) 
						{
						    $langs->load("errors");
						    print img_warning($langs->trans("ErrorBadEMail", $userfee->email));
						}
					}
					print '</td></tr>';
				}
				elseif($object->fk_statut == 4)
				{
					print '<tr>';
					print '<td>'.$langs->trans("CANCEL_USER").'</span></td>';
					print '<td>';
					if ($object->fk_user_cancel > 0)
					{
						$userfee=new User($db);
						$userfee->fetch($object->fk_user_cancel);
						print $userfee->getNomUrl(-1);
					}
					print '</td></tr>';
					
					print '<tr>';
					print '<td>'.$langs->trans("MOTIF_CANCEL").'</td>';
					print '<td>'.$object->detail_cancel.'</td></tr>';
					print '</tr>';
					print '<tr>';
					print '<td>'.$langs->trans("DATE_CANCEL").'</td>';
					print '<td>'.dol_print_date($object->date_cancel,'dayhour').'</td></tr>';
					print '</tr>';
				}
				else
				{
					print '<tr>';
					print '<td>'.$langs->trans("ApprovedBy").'</td>';
					print '<td>';
					if ($object->fk_user_approve > 0)
					{
						$userapp=new User($db);
						$userapp->fetch($object->fk_user_approve);
						print $userapp->getNomUrl(-1);
					}
					print '</td></tr>';
					
					print '<tr>';
					print '<td>'.$langs->trans("DateApprove").'</td>';
					print '<td>'.dol_print_date($object->date_approve,'dayhour').'</td></tr>';
					print '</tr>';
				}

				if ($object->fk_statut==99 || !empty($object->detail_refuse))
				{
					print '<tr>';
					print '<td>'.$langs->trans("REFUSEUR").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_refuse);
					print $userfee->getNomUrl(-1);
					print '</td></tr>';
					
					print '<tr>';
					print '<td>'.$langs->trans("DATE_REFUS").'</td>';
					print '<td>'.dol_print_date($object->date_refuse,'dayhour');
					if ($object->detail_refuse) print ' - '.$object->detail_refuse;
					print '</td>';
					print '</tr>';
				}

				if($object->fk_statut==6)
				{
					/* TODO this fields are not yet filled
					print '<tr>';
					print '<td>'.$langs->trans("AUTHORPAIEMENT").'</td>';
					print '<td>';
					$userfee=new User($db);
					$userfee->fetch($object->fk_user_paid);
					print $userfee->getNomUrl(-1);
					print '</td></tr>';
					print '<tr>';
					print '<td>'.$langs->trans("DATE_PAIEMENT").'</td>';
					print '<td>'.$object->date_paiement.'</td></tr>';
					print '</tr>';
					*/
				}

				// Other attributes
				$cols = 3;
				include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';
				
				print '</table>';

				print '</div>';
				print '<div class="fichehalfright">';
				print '<div class="ficheaddleft">';
				//print '<div class="underbanner clearboth"></div>';
				
				// List of payments
				$sql = "SELECT p.rowid, p.num_payment, p.datep as dp, p.amount,";
				$sql.= "c.code as type_code,c.libelle as payment_type";
				$sql.= " FROM ".MAIN_DB_PREFIX."payment_expensereport as p";
				$sql.= ", ".MAIN_DB_PREFIX."c_paiement as c ";
				$sql.= ", ".MAIN_DB_PREFIX."expensereport as e";
				$sql.= " WHERE e.rowid = '".$id."'";
				$sql.= " AND p.fk_expensereport = e.rowid";
				$sql.= " AND e.entity = ".$conf->entity;
				$sql.= " AND p.fk_typepayment = c.id";
				$sql.= " ORDER BY dp";
				
				$resql = $db->query($sql);
				if ($resql)
				{
				    $num = $db->num_rows($resql);
				    $i = 0; $total = 0;
				    print '<table class="noborder paymenttable" width="100%">';
				    print '<tr class="liste_titre">';
				    print '<td>'.$langs->trans("RefPayment").'</td>';
				    print '<td>'.$langs->trans("Date").'</td>';
				    print '<td>'.$langs->trans("Type").'</td>';
				    print '<td align="right">'.$langs->trans("Amount").'</td>';
				    print '<td>&nbsp;</td>';
				    print '</tr>';
				
				    $var=True;
				    while ($i < $num)
				    {
				        $objp = $db->fetch_object($resql);
				        $var=!$var;
				        print "<tr ".$bc[$var]."><td>";
				        print '<a href="'.DOL_URL_ROOT.'/expensereport/payment/card.php?id='.$objp->rowid.'">'.img_object($langs->trans("Payment"),"payment").' '.$objp->rowid.'</a></td>';
				        print '<td>'.dol_print_date($db->jdate($objp->dp),'day')."</td>\n";
				        $labeltype=$langs->trans("PaymentType".$objp->type_code)!=("PaymentType".$objp->type_code)?$langs->trans("PaymentType".$objp->type_code):$objp->fk_typepayment;
				        print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
				        print '<td align="right">'.price($objp->amount)."</td><td>&nbsp;".$langs->trans("Currency".$conf->currency)."</td>\n";
				        print "</tr>";
				        $totalpaid += $objp->amount;
				        $i++;
				    }
				
				    if ($object->paid == 0)
				    {
				        print "<tr><td colspan=\"3\" align=\"right\">".$langs->trans("AlreadyPaid")." :</td><td align=\"right\">".price($totalpaid)."</td></tr>\n";
				        print "<tr><td colspan=\"3\" align=\"right\">".$langs->trans("AmountExpected")." :</td><td align=\"right\">".price($object->total_ttc)."</td></tr>\n";
				
				        $remaintopay = $object->total_ttc - $totalpaid;
				
				        print "<tr><td colspan=\"3\" align=\"right\">".$langs->trans("RemainderToPay")." :</td>";
				        print '<td align="right"'.($remaintopay?' class="amountremaintopay"':'').'>'.price($remaintopay)."</td></tr>\n";
				    }
				    print "</table>";
				    $db->free($resql);
				}
				else
				{
				    dol_print_error($db);
				}				
				
				print '</div>';
				print '</div>';
				print '</div>';
				
				print '<div class="clearboth"></div><br>';
				
				print '<br>';

				// Fetch Lines of current expense report
				$sql = 'SELECT fde.rowid, fde.fk_expensereport, fde.fk_c_type_fees, fde.fk_projet, fde.date,';
				$sql.= ' fde.tva_tx as vatrate, fde.comments, fde.qty, fde.value_unit, fde.total_ht, fde.total_tva, fde.total_ttc,';
				$sql.= ' ctf.code as type_fees_code, ctf.label as type_fees_libelle,';
				$sql.= ' pjt.rowid as projet_id, pjt.title as projet_title, pjt.ref as projet_ref';
				$sql.= ' FROM '.MAIN_DB_PREFIX.'expensereport_det as fde';
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_type_fees as ctf ON fde.fk_c_type_fees=ctf.id';
				$sql.= ' LEFT JOIN '.MAIN_DB_PREFIX.'projet as pjt ON fde.fk_projet=pjt.rowid';
				$sql.= ' WHERE fde.fk_expensereport = '.$object->id;

				print '<div style="clear: both;"></div>';

				$actiontouse='updateligne';
				if (($object->fk_statut==0 || $object->fk_statut==99) && $action != 'editline') $actiontouse='addline';
				
				print '<form name="expensereport" action="'.$_SERVER["PHP_SELF"].'" method="post">';
				print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
				print '<input type="hidden" name="action" value="'.$actiontouse.'">';
				print '<input type="hidden" name="id" value="'.$object->id.'">';
				print '<input type="hidden" name="fk_expensereport" value="'.$object->id.'" />';
				
				
				print '<div class="div-table-responsive">';
				print '<table id="tablelines" class="noborder" width="100%">';
				
		        $resql = $db->query($sql);
				if ($resql)
				{
					$num_lignes = $db->num_rows($resql);
					$i = 0;$total = 0;

					if ($num_lignes)
					{
						print '<tr class="liste_titre">';
						print '<td style="text-align:center;">'.$langs->trans('Piece').'</td>';
						print '<td style="text-align:center;">'.$langs->trans('Date').'</td>';
						if (! empty($conf->projet->enabled)) print '<td class="minwidth100imp">'.$langs->trans('Project').'</td>';
						print '<td style="text-align:center;">'.$langs->trans('Type').'</td>';
						print '<td style="text-align:left;">'.$langs->trans('Description').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('VAT').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('PriceUTTC').'</td>';
						print '<td style="text-align:right;">'.$langs->trans('Qty').'</td>';
						if ($action != 'editline')
						{
							print '<td style="text-align:right;">'.$langs->trans('AmountHT').'</td>';
							print '<td style="text-align:right;">'.$langs->trans('AmountTTC').'</td>';
						}
						// Ajout des boutons de modification/suppression
						if (($object->fk_statut < 2 || $object->fk_statut == 99) && $user->rights->expensereport->creer)
						{
							print '<td style="text-align:right;"></td>';
						}
						print '</tr>';

						$var=true;
						while ($i < $num_lignes)
						{
							$piece_comptable = $i + 1;
							$objp = $db->fetch_object($resql);
							$var=!$var;
							if ($action != 'editline' || $objp->rowid != GETPOST('rowid'))
							{
								print '<tr '.$bc[$var].'>';
								print '<td style="text-align:center;">';
								print img_picto($langs->trans("Document"), "object_generic");
								print ' <span>'.$piece_comptable.'</span></td>';
								print '<td style="text-align:center;">'.dol_print_date($db->jdate($objp->date), 'day').'</td>';
								if (! empty($conf->projet->enabled))
								{
    								print '<td>';
    								if ($objp->projet_id > 0)
    								{
    									$projecttmp->id=$objp->projet_id;
    									$projecttmp->ref=$objp->projet_ref;
    									print $projecttmp->getNomUrl(1);
    								}
    								print '</td>';
								}
								// print '<td style="text-align:center;">'.$langs->trans("TF_".strtoupper(empty($objp->type_fees_libelle)?'OTHER':$objp->type_fees_libelle)).'</td>';
								print '<td style="text-align:center;">'.($langs->trans(($objp->type_fees_code)) == $objp->type_fees_code ? $objp->type_fees_libelle : $langs->trans(($objp->type_fees_code))).'</td>';
								print '<td style="text-align:left;">'.$objp->comments.'</td>';
								print '<td style="text-align:right;">'.vatrate($objp->vatrate,true).'</td>';
								print '<td style="text-align:right;">'.price($objp->value_unit).'</td>';
								print '<td style="text-align:right;">'.$objp->qty.'</td>';
								
								if ($action != 'editline')
								{
									print '<td style="text-align:right;">'.price($objp->total_ht).'</td>';
									print '<td style="text-align:right;">'.price($objp->total_ttc).'</td>';
								}

								// Ajout des boutons de modification/suppression
								if (($object->fk_statut < 2 || $object->fk_statut == 99) && $user->rights->expensereport->creer)
								{
									print '<td style="text-align:right;" class="nowrap">';

									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=editline&amp;rowid='.$objp->rowid.'#'.$objp->rowid.'">';
									print img_edit();
									print '</a> &nbsp; ';
									print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&amp;action=delete_line&amp;rowid='.$objp->rowid.'">';
									print img_delete();
									print '</a>';

									print '</td>';
								}
								
								print '</tr>';
							}

							if ($action == 'editline' && $objp->rowid == GETPOST('rowid'))
							{
									//modif ligne!!!!!
									print '<tr '.$bc[$var].'>';
									
									print '<td></td>';

									// Select date
									print '<td style="text-align:center;">';
									$form->select_date($objp->date,'date');
									print '</td>';

									// Select project
									if (! empty($conf->projet->enabled))
									{
    									print '<td>';
    									$formproject->select_projects(-1, $objp->fk_projet,'fk_projet', 0, 0, 1, 1);
    									print '</td>';
									}
									
									// Select type
									print '<td style="text-align:center;">';
									select_type_fees_id($objp->type_fees_code,'fk_c_type_fees');
									print '</td>';

									// Add comments
									print '<td>';
									print '<textarea class="flat_ndf" name="comments" class="centpercent">'.$objp->comments.'</textarea>';
									print '</td>';

									// VAT
									print '<td style="text-align:right;">';
									print $form->load_tva('vatrate', (isset($_POST["vatrate"])?$_POST["vatrate"]:$objp->vatrate), $mysoc, '');
									print '</td>';

									// Unit price
									print '<td style="text-align:right;">';
									print '<input type="text" size="6" name="value_unit" value="'.$objp->value_unit.'" />';
									print '</td>';

									// Quantity
									print '<td style="text-align:right;">';
									print '<input type="text" size="4" name="qty" value="'.$objp->qty.'" />';
									print '</td>';

									if ($action != 'editline')
									{
									    print '<td style="text-align:right;">'.$langs->trans('AmountHT').'</td>';
									    print '<td style="text-align:right;">'.$langs->trans('AmountTTC').'</td>';
									}
									
									print '<td style="text-align:center;">';
									print '<input type="hidden" name="rowid" value="'.$objp->rowid.'">';
									print '<input type="submit" class="button" name="save" value="'.$langs->trans('Save').'">';
									print '<br /><input type="submit" class="button" name="cancel" value="'.$langs->trans('Cancel').'">';
									print '</td>';
							}

							$i++;
						}

						$db->free($resql);
					}
					else
					{
					/*	print '<table width="100%">';
						print '<tr><td><div class="error" style="display:block;">'.$langs->trans("AucuneLigne").'</div></td></tr>';
						print '</table>';*/
					}
					//print '</div>';

					// Add a line
					if (($object->fk_statut==0 || $object->fk_statut==99) && $action != 'editline' && $user->rights->expensereport->creer)
					{
						print '<tr class="liste_titre">';
						print '<td></td>';
						print '<td align="center">'.$langs->trans('Date').'</td>';
						if (! empty($conf->projet->enabled)) print '<td class="minwidth100imp">'.$langs->trans('Project').'</td>';
						print '<td align="center">'.$langs->trans('Type').'</td>';
						print '<td>'.$langs->trans('Description').'</td>';
						print '<td align="right">'.$langs->trans('VAT').'</td>';
						print '<td align="right">'.$langs->trans('PriceUTTC').'</td>';
						print '<td align="right">'.$langs->trans('Qty').'</td>';
						print '<td colspan="3"></td>';
						print '</tr>';

						
						print '<tr '.$bc[true].'>';

						print '<td></td>';
						
						// Select date
						print '<td align="center">';
						$form->select_date($date?$date:-1,'date');
						print '</td>';

						// Select project
						if (! empty($conf->projet->enabled))
						{
    						print '<td>';
    						$formproject->select_projects(-1, $fk_projet, 'fk_projet', 0, 0, 1, 1);
    						print '</td>';
						}
						
						// Select type
						print '<td align="center">';
						select_type_fees_id($fk_c_type_fees,'fk_c_type_fees',1);
						print '</td>';

						// Add comments
						print '<td>';
						print '<textarea class="flat_ndf centpercent" name="comments">'.$comments.'</textarea>';
						print '</td>';

						// Select VAT
						print '<td align="right">';
						$defaultvat=-1;
						if (! empty($conf->global->EXPENSEREPORT_NO_DEFAULT_VAT)) $conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS = 'none';
						print $form->load_tva('vatrate', ($vatrate!=''?$vatrate:$defaultvat), $mysoc, '', 0, 0, '', false);
						print '</td>';

						// Unit price
						print '<td align="right">';
						print '<input type="text" size="5" name="value_unit" value="'.$value_unit.'">';
						print '</td>';

						// Quantity
						print '<td align="right">';
						print '<input type="text" size="2" name="qty"  value="'.($qty?$qty:1).'">';
						print '</td>';

						if ($action != 'editline')
						{
						    print '<td align="right"></td>';
						    print '<td align="right"></td>';
						}

						print '<td align="center"><input type="submit" value="'.$langs->trans("Add").'" name="bouton" class="button"></td>';
						
						print '</tr>';
					} // Fin si c'est payé/validé

					print '</table>';
					print '</div>';
					
					print '</form>';
				}
				else
				{
					dol_print_error($db);
				}

				dol_fiche_end();

			} // end edit or not edit

		}	// end of if result
		else
		{
			dol_print_error($db);
		}

	} //fin si id > 0

}

/*
 * Barre d'actions
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit')
{
	$object = new ExpenseReport($db);
	$object->fetch($id, $ref);


	/* Si l'état est "Brouillon"
	 *	ET user à droit "creer/supprimer"
	*	ET fk_user_author == user courant
	* 	Afficher : "Enregistrer" / "Modifier" / "Supprimer"
	*/
	if ($user->rights->expensereport->creer && $object->fk_statut==0)
	{
		if (in_array($object->fk_user_author, $user->getAllChildIds(1)) || !empty($user->rights->expensereport->writeall_advance))
		{
			// Modify
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

			// Validate
			if (count($object->lines) > 0 || count($object->lignes) > 0)
			{
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=save&id='.$object->id.'">'.$langs->trans('ValidateAndSubmit').'</a></div>';
			}
		}
	}

	/* Si l'état est "Refusée"
	 *	ET user à droit "creer/supprimer"
	 *	ET fk_user_author == user courant
	 * 	Afficher : "Enregistrer" / "Modifier" / "Supprimer"
	 */
	if($user->rights->expensereport->creer && $object->fk_statut==99)
	{
		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid)
		{
			// Modify
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

			// Brouillonner (le statut refusée est identique à brouillon)
			//print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$id.'">'.$langs->trans('BROUILLONNER').'</a>';
			// Enregistrer depuis le statut "Refusée"
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=save_from_refuse&id='.$object->id.'">'.$langs->trans('ValidateAndSubmit').'</a></div>';
		}
	}

	if ($user->rights->expensereport->to_paid && $object->fk_statut==5)
	{
		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid)
		{
			// Brouillonner
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$object->id.'">'.$langs->trans('SetToDraft').'</a></div>';
		}
	}

	/* Si l'état est "En attente d'approbation"
	 *	ET user à droit de "approve"
	 *	ET fk_user_validator == user courant
	 *	Afficher : "Valider" / "Refuser" / "Supprimer"
	 */
	if ($object->fk_statut == 2)
	{
		if (in_array($object->fk_user_author, $user->getAllChildIds(1)))
		{
			// Brouillonner
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$object->id.'">'.$langs->trans('SetToDraft').'</a></div>';
		}
	}

	if ($user->rights->expensereport->approve && $object->fk_statut == 2)
	{
		//if($object->fk_user_validator==$user->id)
		//{
			// Validate
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=validate&id='.$object->id.'">'.$langs->trans('Approve').'</a></div>';
			// Deny
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=refuse&id='.$object->id.'">'.$langs->trans('Deny').'</a></div>';
		//}

		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid)
		{
			// Cancel
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$object->id.'">'.$langs->trans('Cancel').'</a></div>';
		}
	}

	
	// If status is Appoved
	// --------------------
	
	if ($user->rights->expensereport->approve && $object->fk_statut == 5)
	{
	    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=refuse&id='.$object->id.'">'.$langs->trans('Deny').'</a></div>';
	}
	
	// If bank module is used
	if ($user->rights->expensereport->to_paid && ! empty($conf->banque->enabled) && $object->fk_statut == 5)
	{
		// Pay
		if ($remaintopay == 0)
		{
			print '<div class="inline-block divButAction"><span class="butActionRefused" title="' . $langs->trans("DisabledBecauseRemainderToPayIsZero") . '">' . $langs->trans('DoPayment') . '</span></div>';
		}
		else
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/expensereport/payment/payment.php?id=' . $object->id . '&amp;action=create">' . $langs->trans('DoPayment') . '</a></div>';
		}
	}
	
	// If bank module is not used	
	if (($user->rights->expensereport->to_paid || empty($conf->banque->enabled)) && $object->fk_statut == 5)
	{
		//if ((round($remaintopay) == 0 || empty($conf->banque->enabled)) && $object->paid == 0)
		if ($object->paid == 0)
		{
			print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . '?id='.$object->id.'&action=set_paid">'.$langs->trans("ClassifyPaid")."</a></div>";
		}
	}
	
	if ($user->rights->expensereport->creer && ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid) && $object->fk_statut == 5)
	{
    	// Cancel
   		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$object->id.'">'.$langs->trans('Cancel').'</a></div>';
	}
	
    // TODO Replace this. It should be SetUnpaid and should go back to status unpaid not canceled.
	if (($user->rights->expensereport->approve || $user->rights->expensereport->to_paid) && $object->fk_statut == 6)
	{
	    // Cancel
	    print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$object->id.'">'.$langs->trans('Cancel').'</a></div>';
	}
	
	// Clone
	if ($user->rights->expensereport->creer) {
	    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER['PHP_SELF'] . '?id=' . $object->id . '&amp;action=clone">' . $langs->trans("ToClone") . '</a></div>';
	}
	
	/* If draft, validated, cancel, and user can create, he can always delete its card before it is approved */ 
	if ($user->rights->expensereport->creer && $user->id == $object->fk_user_author && $object->fk_statut <= 4)
	{
	    // Delete
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
	}
	else if($user->rights->expensereport->supprimer && $object->fk_statut != 6)
	{
    	// Delete
	    print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
	}

}

print '</div>';


//$conf->global->DOL_URL_ROOT_DOCUMENT_PHP=dol_buildpath('/expensereport/documentwrapper.php',1);


print '<div class="fichehalfleft">';

/*
 * Generate documents
 */

if($user->rights->expensereport->export && $action != 'create' && $action != 'edit')
{
	$filename	=	dol_sanitizeFileName($object->ref);
	$filedir	=	$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref);
	$urlsource	=	$_SERVER["PHP_SELF"]."?id=".$object->id;
	$genallowed	=	1;
	$delallowed	=	1;
	$var 		= 	true;
	print $formfile->showdocuments('expensereport',$filename,$filedir,$urlsource,$genallowed,$delallowed);
	$somethingshown = $formfile->numoffiles;
}

print '</div>';

if ($action != 'create' && $action != 'edit' && ($id || $ref))
{
    $permissiondellink=$user->rights->facture->creer;	// Used by the include of actions_dellink.inc.php
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';		// Must be include, not include_once

    // Link invoice to intervention
    if (GETPOST('LinkedFichinter')) {
        $object->fetch($id);
        $object->fetch_thirdparty();
        $result = $object->add_object_linked('fichinter', GETPOST('LinkedFichinter'));
    }
    
    // Show links to link elements
    $linktoelements=array();
    if (! empty($conf->global->EXPENSES_LINK_TO_INTERVENTION))
    {
        $linktoelements[]='fichinter';
        $linktoelem = $form->showLinkToObjectBlock($object, $linktoelements, array('expensereport'));
        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
    }
}

llxFooter();

$db->close();
