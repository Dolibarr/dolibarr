<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
// $uploaddir (example $conf->projet->dir_output . "/";)
// $toselect may be defined


// Protection
if (empty($objectclass) || empty($uploaddir)) 
{
    dol_print_error(null, 'include of actions_massactions.inc.php is done but var $massaction or $objectclass or $uploaddir was not defined');
    exit;
}


// Mass actions. Controls on number of lines checked
$maxformassaction=1000;
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

if (! $error && $massaction == 'confirm_presend' && GETPOST('modelselected'))  // If we change the template, we must not send email, but keep on send email form
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

    if (!$error && !isset($user->email))
    {
        $error++;
        setEventMessages($langs->trans("NoSenderEmailDefined"), null, 'warnings');
    }

    if (! $error)
    {
        $thirdparty=new Societe($db);
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
                $listoinvoicesid[$toselectid]=$toselectid;
                $thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
                $listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
                $listofobjectref[$thirdpartyid][$toselectid]=$objecttmp;
            }
        }
        //var_dump($listofobjectthirdparties);exit;
        	
        foreach ($listofobjectthirdparties as $thirdpartyid)
        {
            $result = $thirdparty->fetch($thirdpartyid);
            if ($result < 0)
            {
                dol_print_error($db);
                exit;
            }

            // Define recipient $sendto and $sendtocc
            if (trim($_POST['sendto']))
            {
                // Recipient is provided into free text
                $sendto = trim($_POST['sendto']);
                $sendtoid = 0;
            }
            elseif ($_POST['receiver'] != '-1')
            {
                // Recipient was provided from combo list
                if ($_POST['receiver'] == 'thirdparty') // Id of third party
                {
                    $sendto = $thirdparty->email;
                    $sendtoid = 0;
                }
                else	// Id du contact
                {
                    $sendto = $thirdparty->contact_get_property((int) $_POST['receiver'],'email');
                    $sendtoid = $_POST['receiver'];
                }
            }
            if (trim($_POST['sendtocc']))
            {
                $sendtocc = trim($_POST['sendtocc']);
            }
            elseif ($_POST['receivercc'] != '-1')
            {
                // Recipient was provided from combo list
                if ($_POST['receivercc'] == 'thirdparty')	// Id of third party
                {
                    $sendtocc = $thirdparty->email;
                }
                else	// Id du contact
                {
                    $sendtocc = $thirdparty->contact_get_property((int) $_POST['receivercc'],'email');
                }
            }

            //var_dump($listofobjectref[$thirdpartyid]);	// Array of invoice for this thirdparty

            $attachedfiles=array('paths'=>array(), 'names'=>array(), 'mimes'=>array());
            $listofqualifiedinvoice=array();
            $listofqualifiedref=array();
            foreach($listofobjectref[$thirdpartyid] as $objectid => $object)
            {
                //var_dump($object);
                //var_dump($thirdpartyid.' - '.$objectid.' - '.$object->statut);
                	
                if ($objectclass == 'Facture' && $object->statut != Facture::STATUS_VALIDATED)
                {
                    $nbignored++;
                    $resaction.='<div class="error">'.$langs->trans('ErrorOnlyInvoiceValidatedCanBeSentInMassAction',$object->ref).'</div><br>';
                    continue; // Payment done or started or canceled
                }
                if ($objectclass == 'Commande' && $object->statut == Commande::STATUS_DRAFT)
                {
                    $nbignored++;
                    $resaction.='<div class="error">'.$langs->trans('ErrorOnlyOrderNotDraftCanBeSentInMassAction',$object->ref).'</div><br>';
                    continue;
                }
                
                // Read document
                // TODO Use future field $object->fullpathdoc to know where is stored default file
                // TODO If not defined, use $object->modelpdf (or defaut invoice config) to know what is template to use to regenerate doc.
                $filename=dol_sanitizeFileName($object->ref).'.pdf';
                $filedir=$uploaddir . '/' . dol_sanitizeFileName($object->ref);
                $file = $filedir . '/' . $filename;
                $mime = dol_mimetype($file);

                if (dol_is_file($file))
                {
                    if (empty($sendto)) 	// For the case, no recipient were set (multi thirdparties send)
                    {
                        $object->fetch_thirdparty();
                        $sendto = $object->thirdparty->email;
                    }

                    if (empty($sendto))
                    {
                        //print "No recipient for thirdparty ".$object->thirdparty->name;
                        $nbignored++;
                        continue;
                    }

                    if (dol_strlen($sendto))
                    {
                        // Create form object
                        $attachedfiles=array(
                            'paths'=>array_merge($attachedfiles['paths'],array($file)),
                            'names'=>array_merge($attachedfiles['names'],array($filename)),
                            'mimes'=>array_merge($attachedfiles['mimes'],array($mime))
                        );
                    }

                    $listofqualifiedinvoice[$objectid]=$object;
                    $listofqualifiedref[$objectid]=$object->ref;
                }
                else
                {
                    $nbignored++;
                    $langs->load("errors");
                    $resaction.='<div class="error">'.$langs->trans('ErrorCantReadFile',$file).'</div><br>';
                    dol_syslog('Failed to read file: '.$file, LOG_WARNING);
                    continue;
                }
                	
                //var_dump($listofqualifiedref);
            }

            if (count($listofqualifiedinvoice) > 0)
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
                $subject = GETPOST('subject');
                $message = GETPOST('message');
                $sendtocc = GETPOST('sentocc');
                $sendtobcc = (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO)?'':$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO);

                $substitutionarray=array(
                    '__ID__' => join(', ',array_keys($listofqualifiedinvoice)),
                    '__EMAIL__' => $thirdparty->email,
                    '__CHECK_READ__' => '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>',
                    '__FACREF__' => join(', ',$listofqualifiedref),            // For backward compatibility
                    '__ORDERREF__' => join(', ',$listofqualifiedref),          // For backward compatibility
                    '__PROPREF__' => join(', ',$listofqualifiedref),           // For backward compatibility
                    '__REF__' => join(', ',$listofqualifiedref),
                    '__REFCLIENT__' => $thirdparty->name
                );

                $subject=make_substitutions($subject, $substitutionarray);
                $message=make_substitutions($message, $substitutionarray);

                $filepath = $attachedfiles['paths'];
                $filename = $attachedfiles['names'];
                $mimetype = $attachedfiles['mimes'];
                	
                //var_dump($filepath);
                	
                // Send mail
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
                        foreach($listofqualifiedinvoice as $invid => $object)
                        {
                            if ($objectclass == 'Propale') $actiontypecode='AC_PROP';
                            if ($objectclass == 'Commande') $actiontypecode='AC_COM';
                            if ($objectclass == 'Facture') $actiontypecode='AC_FAC';
                            if ($objectclass == 'Supplier_Proposal') $actiontypecode='AC_SUP_PRO';
                            if ($objectclass == 'CommandeFournisseur') $actiontypecode='AC_SUP_ORD';
                            if ($objectclass == 'FactureFournisseur') $actiontypecode='AC_SUP_INV';
                            
                            $actionmsg=$langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
                            if ($message)
                            {
                                if ($sendtocc) $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc') . ": " . $sendtocc);
                                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic') . ": " . $subject);
                                $actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody') . ":");
                                $actionmsg = dol_concatdesc($actionmsg, $message);
                            }

                            // Initialisation donnees
                            $object->sendtoid		= 0;
                            $object->actiontypecode	= $actiontypecode;
                            $object->actionmsg		= $actionmsg;  // Long text
                            $object->actionmsg2		= $actionmsg2; // Short text
                            $object->fk_element		= $invid;
                            $object->elementtype	= $object->element;

                            // Appel des triggers
                            include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                            $interface=new Interfaces($db);
                            $result=$interface->run_triggers('BILL_SENTBYMAIL',$object,$user,$langs,$conf);
                            if ($result < 0) { $error++; $errors=$interface->errors; }
                            // Fin appel triggers

                            if ($error)
                            {
                                setEventMessages($db->lasterror(), $errors, 'errors');
                                dol_syslog("Error in trigger BILL_SENTBYMAIL ".$db->lasterror(), LOG_ERR);
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
    }

    $action='list';
    $massaction='';
}

if (! $error && $massaction == "builddoc" && $permtoread && ! GETPOST('button_search'))
{
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
            $listoinvoicesid[$toselectid]=$toselectid;
            $thirdpartyid=$objecttmp->fk_soc?$objecttmp->fk_soc:$objecttmp->socid;
            $listofobjectthirdparties[$thirdpartyid]=$thirdpartyid;
            $listofobjectref[$toselectid]=$objecttmp->ref;
        }
    }

    $arrayofinclusion=array();
    foreach($listofobjectref as $tmppdf) $arrayofinclusion[]=preg_quote($tmppdf.'.pdf','/');
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
    if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id')) $newlang=GETPOST('lang_id');
    if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$object->thirdparty->default_lang;
    if (! empty($newlang))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang($newlang);
    }

    // Create empty PDF
    $pdf=pdf_getInstance();
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

    // Save merged file
    $filename=strtolower(dol_sanitizeFileName($langs->transnoentities($objectlabel)));
    if ($filter=='paye:0')
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
            $result = $objecttmp->delete($user);
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




