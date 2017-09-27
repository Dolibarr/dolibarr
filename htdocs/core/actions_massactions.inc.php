<?php
/* Copyright (C) 2015-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_massactions.inc.php
 *  \brief			Code for actions done with massaction button (send by email, merge pdf, delete, ...)
 */


// $massaction must be defined
// $objectclass and $$objectlabel must be defined
// $parameters, $object, $action must be defined for the hook.

// $permtoread, $permtocreate and $permtodelete may be defined
// $uploaddir may be defined (example to $conf->projet->dir_output."/";)
// $toselect may be defined


// Protection
if (empty($objectclass) || empty($uploaddir))
{
    dol_print_error(null, 'include of actions_massactions.inc.php is done but var $massaction or $objectclass or $uploaddir was not defined');
    exit;
}


// Mass actions. Controls on number of lines checked.
$maxformassaction=(empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS)?1000:$conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
if (! empty($massaction) && count($toselect) < 1)
{
    $error++;
    setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
}
if (! $error && count($toselect) > $maxformassaction)
{
    setEventMessages($langs->trans('TooManyRecordForMassAction',$maxformassaction), null, 'errors');
    $error++;
}

if (! $error && $massaction == 'confirm_presend' && ! GETPOST('sendmail'))  // If we do not choose button send (for example when we change template or limit), we must not send email, but keep on send email form
{
    $massaction='presend';
}
if (! $error && $massaction == 'confirm_presend')
{
    $resaction = '';
    $nbsent = 0;
    $nbignored = 0;
    $langs->load("mails");
    include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $listofobjectid=array();
    $listofobjectthirdparties=array();
    $listofobjectref=array();

    if (! $error)
    {
        $thirdparty=new Societe($db);
        if ($objecttmp->element == 'expensereport') $thirdparty=new User($db);

        $objecttmp=new $objectclass($db);
        foreach($toselect as $toselectid)
        {
            $objecttmp=new $objectclass($db);	// we must create new instance because instance is saved into $listofobjectref array for future use
            $result=$objecttmp->fetch($toselectid);
            if ($result > 0)
            {
                $listofobjectid[$toselectid]=$toselectid;
                $thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
                if ($objecttmp->element == 'societe') $thirdpartyid=$objecttmp->id;
                if ($objecttmp->element == 'expensereport') $thirdpartyid=$objecttmp->fk_user_author;
                $listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
                $listofobjectref[$thirdpartyid][$toselectid]=$objecttmp;
            }
        }
    }

    // Check mandatory parameters
    if (empty($user->email))
    {
        $error++;
        setEventMessages($langs->trans("NoSenderEmailDefined"), null, 'warnings');
        $massaction='presend';
    }

    $receiver=$_POST['receiver'];
    if (! is_array($receiver))
    {
    	if (empty($receiver) || $receiver == '-1') $receiver=array();
    	else $receiver=array($receiver);
    }
    if (! trim($_POST['sendto']) && count($receiver) == 0 && count($listofobjectthirdparties) == 1)	// if only one recipient, receiver is mandatory
    {
     	$error++;
       	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Recipient")), null, 'warnings');
       	$massaction='presend';
    }

    if (! GETPOST('subject','none'))
    {
    	$error++;
    	setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MailTopic")), null, 'warnings');
    	$massaction='presend';
    }

    // Loop on each recipient/thirdparty
    if (! $error)
    {
        foreach ($listofobjectthirdparties as $thirdpartyid)
        {
            $result = $thirdparty->fetch($thirdpartyid);
            if ($result < 0)
            {
                dol_print_error($db);
                exit;
            }

            $sendto='';
            $sendtocc='';
            $sendtobcc='';
            $sendtoid = array();

            // Define $sendto
            $tmparray=array();
            if (trim($_POST['sendto']))
            {
            	// Recipients are provided into free text
            	$tmparray[] = trim($_POST['sendto']);
            }
            if (count($receiver)>0)
            {
            	foreach($receiver as $key=>$val)
            	{
            		// Recipient was provided from combo list
            		if ($val == 'thirdparty') // Id of third party or user
            		{
            			$tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
            		}
            		elseif ($val && method_exists($thirdparty, 'contact_get_property'))		// Id of contact
            		{
            			$tmparray[] = $thirdparty->contact_get_property((int) $val,'email');
            			$sendtoid[] = $val;
            		}
            	}
            }
            $sendto=implode(',',$tmparray);

            // Define $sendtocc
            $receivercc=$_POST['receivercc'];
            if (! is_array($receivercc))
            {
            	if ($receivercc == '-1') $receivercc=array();
            	else $receivercc=array($receivercc);
            }
            $tmparray=array();
            if (trim($_POST['sendtocc']))
            {
            	$tmparray[] = trim($_POST['sendtocc']);
            }
            if (count($receivercc) > 0)
            {
            	foreach($receivercc as $key=>$val)
            	{
            		// Recipient was provided from combo list
            		if ($val == 'thirdparty') // Id of third party
            		{
            			$tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
            		}
            		elseif ($val)	// Id du contact
            		{
            			$tmparray[] = $thirdparty->contact_get_property((int) $val,'email');
            			//$sendtoid[] = $val;  TODO Add also id of contact in CC ?
            		}
            	}
            }
            $sendtocc=implode(',',$tmparray);

            //var_dump($listofobjectref);exit;
            $attachedfiles=array('paths'=>array(), 'names'=>array(), 'mimes'=>array());
            $listofqualifiedid=array();
            $listofqualifiedref=array();
            $thirdpartywithoutemail=array();

            foreach($listofobjectref[$thirdpartyid] as $objectid => $object)
            {
                //var_dump($thirdpartyid.' - '.$objectid.' - '.$object->statut);
                if ($objectclass == 'Propal' && $object->statut == Propal::STATUS_DRAFT)
                {
                	$langs->load("errors");
                    $nbignored++;
                    $resaction.='<div class="error">'.$langs->trans('ErrorOnlyProposalNotDraftCanBeSentInMassAction',$object->ref).'</div><br>';
                    continue; // Payment done or started or canceled
                }
            	if ($objectclass == 'Commande' && $object->statut == Commande::STATUS_DRAFT)
                {
                	$langs->load("errors");
                    $nbignored++;
                    $resaction.='<div class="error">'.$langs->trans('ErrorOnlyOrderNotDraftCanBeSentInMassAction',$object->ref).'</div><br>';
                    continue;
                }
                if ($objectclass == 'Facture' && $object->statut != Facture::STATUS_VALIDATED)
                {
                	$langs->load("errors");
                	$nbignored++;
                	$resaction.='<div class="error">'.$langs->trans('ErrorOnlyInvoiceValidatedCanBeSentInMassAction',$object->ref).'</div><br>';
                	continue; // Payment done or started or canceled
                }

                // Test recipient
	            if (empty($sendto)) 	// For the case, no recipient were set (multi thirdparties send)
	            {
	            	if ($object->element == 'expensereport')
	            	{
						$fuser = new User($db);
						$fuser->fetch($object->fk_user_author);
						$sendto = $fuser->email;
	            	}
	            	else
	            	{
	            		$object->fetch_thirdparty();
	               		$sendto = $object->thirdparty->email;
	            	}
	            }

	            if (empty($sendto))
	            {
	               	//print "No recipient for thirdparty ".$object->thirdparty->name;
	               	$nbignored++;
	                if (empty($thirdpartywithoutemail[$object->thirdparty->id]))
	                {
	                	$resaction.='<div class="error">'.$langs->trans('NoRecipientEmail',$object->thirdparty->name).'</div><br>';
	                }
	               	dol_syslog('No recipient for thirdparty: '.$object->thirdparty->name, LOG_WARNING);
	               	$thirdpartywithoutemail[$object->thirdparty->id]=1;
	               	continue;
	            }

	            if ($_POST['addmaindocfile'])
	            {
	            	// TODO Use future field $object->fullpathdoc to know where is stored default file
	            	// TODO If not defined, use $object->modelpdf (or defaut invoice config) to know what is template to use to regenerate doc.
	            	$filename=dol_sanitizeFileName($object->ref).'.pdf';
	            	$filedir=$uploaddir . '/' . dol_sanitizeFileName($object->ref);
	            	$file = $filedir . '/' . $filename;
	            	$mime = dol_mimetype($file);

       	            if (dol_is_file($file))
		            {
		                	// Create form object
		                	$attachedfiles=array(
		                	'paths'=>array_merge($attachedfiles['paths'],array($file)),
		                	'names'=>array_merge($attachedfiles['names'],array($filename)),
		                	'mimes'=>array_merge($attachedfiles['mimes'],array($mime))
		                	);
		            }
		            else
		            {
	    	                $nbignored++;
	        	            $langs->load("errors");
	            	        $resaction.='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div><br>';
	                	    dol_syslog('Failed to read file: '.$file, LOG_WARNING);
	                    	continue;
		            }
	            }

	            // Object of thirdparty qualified
	            $listofqualifiedid[$objectid]=$object;
	            $listofqualifiedref[$objectid]=$object->ref;


                //var_dump($listofqualifiedref);
            }

            // Send email if there is at least one qualified record
            if (count($listofqualifiedid) > 0)
            {
                $langs->load("commercial");

                $fromtype = GETPOST('fromtype');
                if ($fromtype === 'user') {
                    $from = $user->getFullName($langs) .' <'.$user->email.'>';
                }
                elseif ($fromtype === 'company') {
                    $from = $conf->global->MAIN_INFO_SOCIETE_NOM .' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
                }
                elseif (preg_match('/user_aliases_(\d+)/', $fromtype, $reg)) {
                    $tmp=explode(',', $user->email_aliases);
                    $from = trim($tmp[($reg[1] - 1)]);
                }
                elseif (preg_match('/global_aliases_(\d+)/', $fromtype, $reg)) {
                    $tmp=explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
                    $from = trim($tmp[($reg[1] - 1)]);
                }
                else {
                    $from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
                }

                $replyto = $from;
                $subject = GETPOST('subject','none');
                $message = GETPOST('message','none');
                $sendtocc = GETPOST('sentocc');
                $sendtobcc = '';
                if ($objectclass == 'Propale') 				$sendtocc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO);
                if ($objectclass == 'Commande') 			$sendtocc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO);
                if ($objectclass == 'Facture') 				$sendtocc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO);
                if ($objectclass == 'Supplier_Proposal') 	$sendtocc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO);
                if ($objectclass == 'CommandeFournisseur')	$sendtocc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO);
                if ($objectclass == 'FactureFournisseur')	$sendtocc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO);

                // $listofqualifiedid is array with key = object id of qualified objects for the current thirdparty
                $oneemailperrecipient=(GETPOST('oneemailperrecipient')=='on'?1:0);
                $looparray=array();
                if (! $oneemailperrecipient)
                {
                	$looparray = $listofqualifiedid;
                }
                else
                {
                	$objectforloop=new $objectclass($db);
                	$objectforloop->thirdparty = $thirdparty;
                	$looparray[0]=$objectforloop;
                }
                //var_dump($looparray);exit;

				foreach ($looparray as $objecttmp)		// $objecttmp is a real object or an empty if we choose to send one email per thirdparty instead of per record
				{
	                // Make substitution in email content
	                $substitutionarray=getCommonSubstitutionArray($langs, 0, null, $objecttmp);
	                $substitutionarray['__ID__']    = ($oneemailperrecipient ? join(', ',array_keys($listofqualifiedid)) : $objecttmp->id);
	                $substitutionarray['__REF__']   = ($oneemailperrecipient ? join(', ',$listofqualifiedref) : $objecttmp->ref);
	                $substitutionarray['__EMAIL__'] = $thirdparty->email;
	                $substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>';

	                $parameters=array('mode'=>'formemail');
	                complete_substitutions_array($substitutionarray, $langs, $objecttmp, $parameters);

	                $subject=make_substitutions($subject, $substitutionarray);
	                $message=make_substitutions($message, $substitutionarray);

	                $filepath = $attachedfiles['paths'];
	                $filename = $attachedfiles['names'];
	                $mimetype = $attachedfiles['mimes'];

	                //var_dump($filepath);

	                // Send mail (substitutionarray must be done just before this)
	                require_once(DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php');
	                $mailfile = new CMailFile($subject,$sendto,$from,$message,$filepath,$mimetype,$filename,$sendtocc,$sendtobcc,$deliveryreceipt,-1);
	                if ($mailfile->error)
	                {
	                    $resaction.='<div class="error">'.$mailfile->error.'</div>';
	                }
	                else
	                {
	                    $result=$mailfile->sendfile();
	                    if ($result)
	                    {
	                        $resaction.=$langs->trans('MailSuccessfulySent',$mailfile->getValidAddress($from,2),$mailfile->getValidAddress($sendto,2)).'<br>';		// Must not contain "

	                        $error=0;

	                        // Insert logs into agenda
	                        foreach($listofqualifiedid as $objid => $object)
	                        {
	                            /*if ($objectclass == 'Propale') $actiontypecode='AC_PROP';
	                            if ($objectclass == 'Commande') $actiontypecode='AC_COM';
	                            if ($objectclass == 'Facture') $actiontypecode='AC_FAC';
	                            if ($objectclass == 'Supplier_Proposal') $actiontypecode='AC_SUP_PRO';
	                            if ($objectclass == 'CommandeFournisseur') $actiontypecode='AC_SUP_ORD';
	                            if ($objectclass == 'FactureFournisseur') $actiontypecode='AC_SUP_INV';*/

	                            $actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
	                            if ($message)
	                            {
	                                if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
	                                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
	                                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
	                                $actionmsg = dol_concatdesc($actionmsg, $message);
	                            }
	                            $actionmsg2='';

	                            // Initialisation donnees
	                            $object->sendtoid		= 0;
	                            $object->actionmsg		= $actionmsg;  // Long text
	                            $object->actionmsg2		= $actionmsg2; // Short text
	                            $object->fk_element		= $objid;
	                            $object->elementtype	= $object->element;

	                            $triggername = strtoupper(get_class($object)) .'_SENTBYMAIL';
	                            if ($triggername == 'SOCIETE_SENTBYMAIL')    $triggername = 'COMPANY_SENTBYEMAIL';
	                            if ($triggername == 'CONTRAT_SENTBYMAIL')    $triggername = 'CONTRACT_SENTBYEMAIL';
	                            if ($triggername == 'COMMANDE_SENTBYMAIL')   $triggername = 'ORDER_SENTBYEMAIL';
	                            if ($triggername == 'FACTURE_SENTBYMAIL')    $triggername = 'BILL_SENTBYEMAIL';
	                            if ($triggername == 'EXPEDITION_SENTBYMAIL') $triggername = 'SHIPPING_SENTBYEMAIL';
	                            if ($triggername == 'COMMANDEFOURNISSEUR_SENTBYMAIL') $triggername = 'ORDER_SUPPLIER_SENTBYMAIL';
	                            if ($triggername == 'FACTUREFOURNISSEUR_SENTBYMAIL') $triggername = 'BILL_SUPPLIER_SENTBYEMAIL';
	                            if ($triggername == 'SUPPLIERPROPOSAL_SENTBYMAIL') $triggername = 'PROPOSAL_SUPPLIER_SENTBYEMAIL';

	                            if (! empty($trigger_name))
	                            {
		                            // Appel des triggers
		                            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
		                            $interface=new Interfaces($db);
		                            $result=$interface->run_triggers($trigger_name, $object, $user, $langs, $conf);
		                            if ($result < 0) { $error++; $errors=$interface->errors; }
		                            // Fin appel triggers

		                            if ($error)
		                            {
		                                setEventMessages($db->lasterror(), $errors, 'errors');
		                                dol_syslog("Error in trigger ".$trigger_name.' '.$db->lasterror(), LOG_ERR);
		                            }
	                            }

	                            $nbsent++;
	                        }
	                    }
	                    else
	                    {
	                        $langs->load("other");
	                        if ($mailfile->error)
	                        {
	                            $resaction.=$langs->trans('ErrorFailedToSendMail',$from,$sendto);
	                            $resaction.='<br><div class="error">'.$mailfile->error.'</div>';
	                        }
	                        else
	                        {
	                            $resaction.='<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
	                        }
	                    }
	                }
				}
            }
        }

        $resaction.=($resaction?'<br>':$resaction);
        $resaction.='<strong>'.$langs->trans("ResultOfMailSending").':</strong><br>'."\n";
        $resaction.=$langs->trans("NbSelected").': '.count($toselect)."\n<br>";
        $resaction.=$langs->trans("NbIgnored").': '.($nbignored?$nbignored:0)."\n<br>";
        $resaction.=$langs->trans("NbSent").': '.($nbsent?$nbsent:0)."\n<br>";

        if ($nbsent)
        {
            $action='';	// Do not show form post if there was at least one successfull sent
            //setEventMessages($langs->trans("EMailSentToNRecipients", $nbsent.'/'.count($toselect)), null, 'mesgs');
            setEventMessages($langs->trans("EMailSentForNElements", $nbsent.'/'.count($toselect)), null, 'mesgs');
            setEventMessages($resaction, null, 'mesgs');
        }
        else
        {
            //setEventMessages($langs->trans("EMailSentToNRecipients", 0), null, 'warnings');  // May be object has no generated PDF file
            setEventMessages($resaction, null, 'warnings');
        }

        $action='list';
    	$massaction='';
    }
}

if (! $error && $massaction == "builddoc" && $permtoread && ! GETPOST('button_search'))
{
	if (empty($diroutputmassaction))
	{
		dol_print_error(null, 'include of actions_massactions.inc.php is done but var $diroutputmassaction was not defined');
		exit;
	}

    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
    require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

    $objecttmp=new $objectclass($db);
    $listofobjectid=array();
    $listofobjectthirdparties=array();
    $listofobjectref=array();
    foreach($toselect as $toselectid)
    {
        $objecttmp=new $objectclass($db);	// must create new instance because instance is saved into $listofobjectref array for future use
        $result=$objecttmp->fetch($toselectid);
        if ($result > 0)
        {
            $listofobjectid[$toselectid]=$toselectid;
            $thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
            $listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
            $listofobjectref[$toselectid]=$objecttmp->ref;
        }
    }

    $arrayofinclusion=array();
    foreach($listofobjectref as $tmppdf) $arrayofinclusion[]='^'.preg_quote($tmppdf.'.pdf','/').'$';
    $listoffiles = dol_dir_list($uploaddir,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);

    // build list of files with full path
    $files = array();
    foreach($listofobjectref as $basename)
    {
        foreach($listoffiles as $filefound)
        {
            if (strstr($filefound["name"],$basename))
            {
                $files[] = $uploaddir.'/'.$basename.'/'.$filefound["name"];
                break;
            }
        }
    }

    // Define output language (Here it is not used because we do only merging existing PDF)
    $outputlangs = $langs;
    $newlang='';
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id','aZ09')) $newlang=GETPOST('lang_id','aZ09');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->thirdparty->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }

    if (!empty($conf->global->USE_PDFTK_FOR_PDF_CONCAT))
    {
    	// Create output dir if not exists
		dol_mkdir($diroutputmassaction);

		// Defined name of merged file
		$filename=strtolower(dol_sanitizeFileName($langs->transnoentities($objectlabel)));
		$filename=preg_replace('/\s/','_',$filename);

		// Save merged file
	    if (in_array($object->element, array('facture', 'facture_fournisseur')) && $search_status == Facture::STATUS_VALIDATED)
		{
			if ($option=='late') $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid"))).'_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Late")));
			else $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid")));
		}
		if ($year) $filename.='_'.$year;
		if ($month) $filename.='_'.$month;

    	if (count($files)>0)
    	{
    		$now=dol_now();
    		$file=$diroutputmassaction.'/'.$filename.'_'.dol_print_date($now,'dayhourlog').'.pdf';

    		$input_files = '';
    		foreach($files as $f) {
    			$input_files.=' '.escapeshellarg($f);
    		}

    		$cmd = 'pdftk '.$input_files.' cat output '.escapeshellarg($file);
    		exec($cmd);

    		if (! empty($conf->global->MAIN_UMASK))
    			@chmod($file, octdec($conf->global->MAIN_UMASK));

    		$langs->load("exports");
    		setEventMessages($langs->trans('FileSuccessfullyBuilt',$filename.'_'.dol_print_date($now,'dayhourlog')), null, 'mesgs');
    	}
    	else
    	{
    		setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
    	}
    }
    else {
	    // Create empty PDF
    	$formatarray=pdf_getFormat();
    	$page_largeur = $formatarray['width'];
    	$page_hauteur = $formatarray['height'];
    	$format = array($page_largeur,$page_hauteur);

	    $pdf=pdf_getInstance($format);

	    if (class_exists('TCPDF'))
	    {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
	    }
	    $pdf->SetFont(pdf_getPDFFont($outputlangs));

	    if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

	    // Add all others
	    foreach($files as $file)
	    {
			// Charge un document PDF depuis un fichier.
			$pagecount = $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++)
			{
			    $tplidx = $pdf->importPage($i);
			    $s = $pdf->getTemplatesize($tplidx);
			    $pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
			    $pdf->useTemplate($tplidx);
			}
	    }

	    // Create output dir if not exists
	    dol_mkdir($diroutputmassaction);

	    // Defined name of merged file
	    $filename=strtolower(dol_sanitizeFileName($langs->transnoentities($objectlabel)));
	    $filename=preg_replace('/\s/','_',$filename);

	    // Save merged file
	    if (in_array($object->element, array('facture', 'facture_fournisseur')) && $search_status == Facture::STATUS_VALIDATED)
	    {
			if ($option=='late') $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid"))).'_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Late")));
			else $filename.='_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid")));
	    }
	    if ($year) $filename.='_'.$year;
	    if ($month) $filename.='_'.$month;
	    if ($pagecount)
	    {
			$now=dol_now();
			$file=$diroutputmassaction.'/'.$filename.'_'.dol_print_date($now,'dayhourlog').'.pdf';
			$pdf->Output($file,'F');
			if (! empty($conf->global->MAIN_UMASK))
			    @chmod($file, octdec($conf->global->MAIN_UMASK));

		    $langs->load("exports");
		    setEventMessages($langs->trans('FileSuccessfullyBuilt',$filename.'_'.dol_print_date($now,'dayhourlog')), null, 'mesgs');
	    }
	    else
	    {
		setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
	    }
    }
}

// Remove a file from massaction area
if ($action == 'remove_file')
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    $langs->load("other");
    $upload_dir = $diroutputmassaction;
    $file = $upload_dir . '/' . GETPOST('file');
    $ret=dol_delete_file($file);
    if ($ret) setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
    else setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
    $action='';
}

// Validate records
if (! $error && $massaction == 'validate' && $permtocreate)
{
	if ($object->element == 'invoice_supplier' && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL))
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorMassValidationNotAllowedWhenStockIncreaseOnAction'), null, 'errors');
		$error++;
	}
	if (! $error)
	{
		$db->begin();

		$objecttmp=new $objectclass($db);
		$nbok = 0;
		foreach($toselect as $toselectid)
		{
			$result=$objecttmp->fetch($toselectid);
			if ($result > 0)
			{
				//if (in_array($objecttmp->element, array('societe','member'))) $result = $objecttmp->delete($objecttmp->id, $user, 1);
				//else
				$result = $objecttmp->validate($user);
				if ($result == 0)
				{
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorObjectMustHaveStatusDraftToBeValidated", $objecttmp->ref), null, 'errors');
					$error++;
					break;
				}
				elseif ($result < 0)
				{
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				}
				else $nbok++;
			}
			else
			{
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}

		if (! $error)
		{
			if ($nbok > 1) setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
			else setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
			$db->commit();
		}
		else
		{
			$db->rollback();
		}
		//var_dump($listofobjectthirdparties);exit;
	}
}

// Delete records
if (! $error && $massaction == 'delete' && $permtodelete)
{
    $db->begin();

    $objecttmp=new $objectclass($db);
    $nbok = 0;
    foreach($toselect as $toselectid)
    {
    	$result=$objecttmp->fetch($toselectid);
        if ($result > 0)
        {
        	// Refuse deletion for some status ?
        	/*
       		if ($objectclass == 'Facture' && $objecttmp->status == Facture::STATUS_DRAFT)
       		{
       			$langs->load("errors");
       			$nbignored++;
       			$resaction.='<div class="error">'.$langs->trans('ErrorOnlyDraftStatusCanBeDeletedInMassAction',$object->ref).'</div><br>';
       			continue;
       		}*/

            if (in_array($objecttmp->element, array('societe','member'))) $result = $objecttmp->delete($objecttmp->id, $user, 1);
            else $result = $objecttmp->delete($user);

            if ($result <= 0)
            {
                setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                $error++;
                break;
            }
            else $nbok++;
        }
        else
        {
            setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
            $error++;
            break;
        }
    }

    if (! $error)
    {
        if ($nbok > 1) setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
        else setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
        $db->commit();
    }
    else
    {
        $db->rollback();
    }
    //var_dump($listofobjectthirdparties);exit;
}

$parameters['toselect']=$toselect;
$parameters['uploaddir']=$uploaddir;

$reshook=$hookmanager->executeHooks('doMassActions',$parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



