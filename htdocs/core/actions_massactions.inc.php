<?php
/* Copyright (C) 2015-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018	   Nicolas ZABOURI	<info@inovea-conseil.com>
 * Copyright (C) 2018 	   Juanjo Menent  <jmenent@2byte.es>
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
// $objectclass and $objectlabel must be defined
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
if (! $error && is_array($toselect) && count($toselect) > $maxformassaction)
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
	if (GETPOST('fromtype','alpha') === 'user' && empty($user->email))
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
			$listofqualifiedobj=array();
			$listofqualifiedref=array();
			$thirdpartywithoutemail=array();

			foreach($listofobjectref[$thirdpartyid] as $objectid => $objectobj)
			{
				//var_dump($thirdpartyid.' - '.$objectid.' - '.$objectobj->statut);
				if ($objectclass == 'Propal' && $objectobj->statut == Propal::STATUS_DRAFT)
				{
					$langs->load("errors");
					$nbignored++;
					$resaction.='<div class="error">'.$langs->trans('ErrorOnlyProposalNotDraftCanBeSentInMassAction',$objectobj->ref).'</div><br>';
					continue; // Payment done or started or canceled
				}
				if ($objectclass == 'Commande' && $objectoj->statut == Commande::STATUS_DRAFT)
				{
					$langs->load("errors");
					$nbignored++;
					$resaction.='<div class="error">'.$langs->trans('ErrorOnlyOrderNotDraftCanBeSentInMassAction',$objectobj->ref).'</div><br>';
					continue;
				}
				if ($objectclass == 'Facture' && $objectobj->statut == Facture::STATUS_DRAFT)
				{
					$langs->load("errors");
					$nbignored++;
					$resaction.='<div class="error">'.$langs->trans('ErrorOnlyInvoiceValidatedCanBeSentInMassAction',$objectobj->ref).'</div><br>';
					continue; // Payment done or started or canceled
				}

				// Test recipient
				if (empty($sendto)) 	// For the case, no recipient were set (multi thirdparties send)
				{
					if ($objectobj->element == 'expensereport')
					{
						$fuser = new User($db);
						$fuser->fetch($objectobj->fk_user_author);
						$sendto = $fuser->email;
					}
					else
					{
						$objectobj->fetch_thirdparty();
						$sendto = $objectobj->thirdparty->email;
					}
				}

				if (empty($sendto))
				{
				   	//print "No recipient for thirdparty ".$objectobj->thirdparty->name;
				   	$nbignored++;
				   	if (empty($thirdpartywithoutemail[$objectobj->thirdparty->id]))
					{
						$resaction.='<div class="error">'.$langs->trans('NoRecipientEmail',$objectobj->thirdparty->name).'</div><br>';
					}
					dol_syslog('No recipient for thirdparty: '.$objectobj->thirdparty->name, LOG_WARNING);
					$thirdpartywithoutemail[$objectobj->thirdparty->id]=1;
				   	continue;
				}

				if ($_POST['addmaindocfile'])
				{
					// TODO Use future field $objectobj->fullpathdoc to know where is stored default file
					// TODO If not defined, use $objectobj->modelpdf (or defaut invoice config) to know what is template to use to regenerate doc.
					$filename=dol_sanitizeFileName($objectobj->ref).'.pdf';
					$filedir=$uploaddir . '/' . dol_sanitizeFileName($objectobj->ref);
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
				$listofqualifiedobj[$objectid]=$objectobj;
				$listofqualifiedref[$objectid]=$objectobj->ref;


				//var_dump($listofqualifiedref);
			}

			// Send email if there is at least one qualified record
			if (count($listofqualifiedobj) > 0)
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
				elseif (preg_match('/senderprofile_(\d+)_(\d+)/', $fromtype, $reg)) {
					$sql='SELECT rowid, label, email FROM '.MAIN_DB_PREFIX.'c_email_senderprofile WHERE rowid = '.(int) $reg[1];
					$resql = $db->query($sql);
					$obj = $db->fetch_object($resql);
					if ($obj)
					{
						$from = $obj->label.' <'.$obj->email.'>';
					}
				}
				else {
					$from = $_POST['fromname'] . ' <' . $_POST['frommail'] .'>';
				}

				$replyto = $from;
				$subject = GETPOST('subject','none');
				$message = GETPOST('message','none');

				$sendtobcc = GETPOST('sendtoccc');
				if ($objectclass == 'Propale') 				$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO));
				if ($objectclass == 'Commande') 			$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO));
				if ($objectclass == 'Facture') 				$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO));
				if ($objectclass == 'Supplier_Proposal') 	$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO));
				if ($objectclass == 'CommandeFournisseur')	$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO));
				if ($objectclass == 'FactureFournisseur')	$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO) ? '' : (($sendtobcc?", ":"").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO));

				// $listofqualifiedobj is array with key = object id and value is instance of qualified objects, for the current thirdparty (but thirdparty property is not loaded yet)
				$oneemailperrecipient=(GETPOST('oneemailperrecipient')=='on'?1:0);
				$looparray=array();
				if (! $oneemailperrecipient)
				{
					$looparray = $listofqualifiedobj;
					foreach ($looparray as $key => $objecttmp)
					{
						$looparray[$key]->thirdparty = $thirdparty;
					}
				}
				else
				{
					$objectforloop=new $objectclass($db);
					$objectforloop->thirdparty = $thirdparty;
					$looparray[0]=$objectforloop;
				}
				//var_dump($looparray);exit;

				foreach ($looparray as $objecttmp)		// $objecttmp is a real object or an empty object if we choose to send one email per thirdparty instead of one per record
				{
					// Make substitution in email content
					$substitutionarray=getCommonSubstitutionArray($langs, 0, null, $objecttmp);
					$substitutionarray['__ID__']    = ($oneemailperrecipient ? join(', ',array_keys($listofqualifiedobj)) : $objecttmp->id);
					$substitutionarray['__REF__']   = ($oneemailperrecipient ? join(', ',$listofqualifiedref) : $objecttmp->ref);
					$substitutionarray['__EMAIL__'] = $thirdparty->email;
					$substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$thirdparty->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>';

					$parameters=array('mode'=>'formemail');

					if ( ! empty( $listofobjectthirdparties ) ) {
						$parameters['listofobjectthirdparties'] = $listofobjectthirdparties;
					}
					if ( ! empty( $listofobjectref ) ) {
						$parameters['listofobjectref'] = $listofobjectref;
					}

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
							foreach($listofqualifiedobj as $objid => $objectobj)
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
								$objectobj->sendtoid		= 0;
								$objectobj->actionmsg		= $actionmsg;  // Long text
								$objectobj->actionmsg2		= $actionmsg2; // Short text
								$objectobj->fk_element		= $objid;
								$objectobj->elementtype	= $objectobj->element;

								$triggername = strtoupper(get_class($objectobj)) .'_SENTBYMAIL';
								if ($triggername == 'SOCIETE_SENTBYMAIL')    $triggername = 'COMPANY_SENTBYEMAIL';
								if ($triggername == 'CONTRAT_SENTBYMAIL')    $triggername = 'CONTRACT_SENTBYEMAIL';
								if ($triggername == 'COMMANDE_SENTBYMAIL')   $triggername = 'ORDER_SENTBYEMAIL';
								if ($triggername == 'FACTURE_SENTBYMAIL')    $triggername = 'BILL_SENTBYMAIL';
								if ($triggername == 'EXPEDITION_SENTBYMAIL') $triggername = 'SHIPPING_SENTBYEMAIL';
								if ($triggername == 'COMMANDEFOURNISSEUR_SENTBYMAIL') $triggername = 'ORDER_SUPPLIER_SENTBYMAIL';
								if ($triggername == 'FACTUREFOURNISSEUR_SENTBYMAIL') $triggername = 'BILL_SUPPLIER_SENTBYEMAIL';
								if ($triggername == 'SUPPLIERPROPOSAL_SENTBYMAIL') $triggername = 'PROPOSAL_SUPPLIER_SENTBYEMAIL';

								if (! empty($triggername))
								{
									// Appel des triggers
									include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
									$interface=new Interfaces($db);
									$result=$interface->run_triggers($triggername, $objectobj, $user, $langs, $conf);
									if ($result < 0) { $error++; $errors=$interface->errors; }
									// Fin appel triggers

									if ($error)
									{
										setEventMessages($db->lasterror(), $errors, 'errors');
										dol_syslog("Error in trigger ".$triggername.' '.$db->lasterror(), LOG_ERR);
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

if ($massaction == 'confirm_createbills')
{
	$orders = GETPOST('toselect','array');
	$createbills_onebythird = GETPOST('createbills_onebythird', 'int');
	$validate_invoices = GETPOST('valdate_invoices', 'int');

	$TFact = array();
	$TFactThird = array();

	$nb_bills_created = 0;

	$db->begin();

	foreach($orders as $id_order)
	{
		$cmd = new Commande($db);
		if ($cmd->fetch($id_order) <= 0) continue;

		$objecttmp = new Facture($db);
		if (!empty($createbills_onebythird) && !empty($TFactThird[$cmd->socid])) $objecttmp = $TFactThird[$cmd->socid]; // If option "one bill per third" is set, we use already created order.
		else {

			$objecttmp->socid = $cmd->socid;
			$objecttmp->type = Facture::TYPE_STANDARD;
			$objecttmp->cond_reglement_id	= $cmd->cond_reglement_id;
			$objecttmp->mode_reglement_id	= $cmd->mode_reglement_id;
			$objecttmp->fk_project			= $cmd->fk_project;

			$datefacture = dol_mktime(12, 0, 0, $_POST['remonth'], $_POST['reday'], $_POST['reyear']);
			if (empty($datefacture))
			{
				$datefacture = dol_mktime(date("h"), date("M"), 0, date("m"), date("d"), date("Y"));
			}

			$objecttmp->date = $datefacture;
			$objecttmp->origin    = 'commande';
			$objecttmp->origin_id = $id_order;

			$res = $objecttmp->create($user);

			if($res > 0) $nb_bills_created++;
		}

		if ($objecttmp->id > 0)
		{
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_element (";
			$sql.= "fk_source";
			$sql.= ", sourcetype";
			$sql.= ", fk_target";
			$sql.= ", targettype";
			$sql.= ") VALUES (";
			$sql.= $id_order;
			$sql.= ", '".$objecttmp->origin."'";
			$sql.= ", ".$objecttmp->id;
			$sql.= ", '".$objecttmp->element."'";
			$sql.= ")";

			if (! $db->query($sql))
			{
				$error++;
			}

			if (! $error)
			{
				$lines = $cmd->lines;
				if (empty($lines) && method_exists($cmd, 'fetch_lines'))
				{
					$cmd->fetch_lines();
					$lines = $cmd->lines;
				}

				$fk_parent_line=0;
				$num=count($lines);

				for ($i=0;$i<$num;$i++)
				{
					$desc=($lines[$i]->desc?$lines[$i]->desc:$lines[$i]->libelle);
					if ($lines[$i]->subprice < 0)
					{
						// Negative line, we create a discount line
						$discount = new DiscountAbsolute($db);
						$discount->fk_soc=$objecttmp->socid;
						$discount->amount_ht=abs($lines[$i]->total_ht);
						$discount->amount_tva=abs($lines[$i]->total_tva);
						$discount->amount_ttc=abs($lines[$i]->total_ttc);
						$discount->tva_tx=$lines[$i]->tva_tx;
						$discount->fk_user=$user->id;
						$discount->description=$desc;
						$discountid=$discount->create($user);
						if ($discountid > 0)
						{
							$result=$objecttmp->insert_discount($discountid);
							//$result=$discount->link_to_invoice($lineid,$id);
						}
						else
						{
							setEventMessages($discount->error, $discount->errors, 'errors');
							$error++;
							break;
						}
					}
					else
					{
						// Positive line
						$product_type=($lines[$i]->product_type?$lines[$i]->product_type:0);
						// Date start
						$date_start=false;
						if ($lines[$i]->date_debut_prevue) $date_start=$lines[$i]->date_debut_prevue;
						if ($lines[$i]->date_debut_reel) $date_start=$lines[$i]->date_debut_reel;
						if ($lines[$i]->date_start) $date_start=$lines[$i]->date_start;
						//Date end
						$date_end=false;
						if ($lines[$i]->date_fin_prevue) $date_end=$lines[$i]->date_fin_prevue;
						if ($lines[$i]->date_fin_reel) $date_end=$lines[$i]->date_fin_reel;
						if ($lines[$i]->date_end) $date_end=$lines[$i]->date_end;
						// Reset fk_parent_line for no child products and special product
						if (($lines[$i]->product_type != 9 && empty($lines[$i]->fk_parent_line)) || $lines[$i]->product_type == 9)
						{
							$fk_parent_line = 0;
						}

						// Extrafields
						if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED) && method_exists($lines[$i], 'fetch_optionals')) {
							$lines[$i]->fetch_optionals($lines[$i]->rowid);
							$array_options = $lines[$i]->array_options;
						}

						$result = $objecttmp->addline(
							$desc,
							$lines[$i]->subprice,
							$lines[$i]->qty,
							$lines[$i]->tva_tx,
							$lines[$i]->localtax1_tx,
							$lines[$i]->localtax2_tx,
							$lines[$i]->fk_product,
							$lines[$i]->remise_percent,
							$date_start,
							$date_end,
							0,
							$lines[$i]->info_bits,
							$lines[$i]->fk_remise_except,
							'HT',
							0,
							$product_type,
							$ii,
							$lines[$i]->special_code,
							$objecttmp->origin,
							$lines[$i]->rowid,
							$fk_parent_line,
							$lines[$i]->fk_fournprice,
							$lines[$i]->pa_ht,
							$lines[$i]->label,
							$array_options
							);
						if ($result > 0)
						{
							$lineid=$result;
						}
						else
						{
							$lineid=0;
							$error++;
							break;
						}
						// Defined the new fk_parent_line
						if ($result > 0 && $lines[$i]->product_type == 9)
						{
							$fk_parent_line = $result;
						}
					}
				}
			}
		}

		//$cmd->classifyBilled($user);        // Disabled. This behavior must be set or not using the workflow module.

		if(!empty($createbills_onebythird) && empty($TFactThird[$cmd->socid])) $TFactThird[$cmd->socid] = $objecttmp;
		else $TFact[$objecttmp->id] = $objecttmp;
	}

	// Build doc with all invoices
	$TAllFact = empty($createbills_onebythird) ? $TFact : $TFactThird;
	$toselect = array();

	if (! $error && $validate_invoices)
	{
		$massaction = $action = 'builddoc';
		foreach($TAllFact as &$objecttmp)
		{
			$result = $objecttmp->validate($user);
			if ($result <= 0)
			{
				$error++;
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				break;
			}

			$id = $objecttmp->id; // For builddoc action

			// Builddoc
			$donotredirect = 1;
			$upload_dir = $conf->facture->dir_output;
			$permissioncreate=$user->rights->facture->creer;
			include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
		}

		$massaction = $action = 'confirm_createbills';
	}

	if (! $error)
	{
		$db->commit();
		setEventMessage($langs->trans('BillCreated', $nb_bills_created));

		// Make a redirect to avoid to bill twice if we make a refresh or back
		$param='';
		if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.urlencode($limit);
		if ($sall)					$param.='&sall='.urlencode($sall);
		if ($socid > 0)             $param.='&socid='.urlencode($socid);
		if ($viewstatut != '')      $param.='&viewstatut='.urlencode($viewstatut);
		if ($search_orderday)      		$param.='&search_orderday='.urlencode($search_orderday);
		if ($search_ordermonth)      		$param.='&search_ordermonth='.urlencode($search_ordermonth);
		if ($search_orderyear)       		$param.='&search_orderyear='.urlencode($search_orderyear);
		if ($search_deliveryday)   		$param.='&search_deliveryday='.urlencode($search_deliveryday);
		if ($search_deliverymonth)   		$param.='&search_deliverymonth='.urlencode($search_deliverymonth);
		if ($search_deliveryyear)    		$param.='&search_deliveryyear='.urlencode($search_deliveryyear);
		if ($search_ref)      		$param.='&search_ref='.urlencode($search_ref);
		if ($search_company)  		$param.='&search_company='.urlencode($search_company);
		if ($search_ref_customer)	$param.='&search_ref_customer='.urlencode($search_ref_customer);
		if ($search_user > 0) 		$param.='&search_user='.urlencode($search_user);
		if ($search_sale > 0) 		$param.='&search_sale='.urlencode($search_sale);
		if ($search_total_ht != '') $param.='&search_total_ht='.urlencode($search_total_ht);
		if ($search_total_vat != '') $param.='&search_total_vat='.urlencode($search_total_vat);
		if ($search_total_ttc != '') $param.='&search_total_ttc='.urlencode($search_total_ttc);
		if ($search_project_ref >= 0)  	$param.="&search_project_ref=".urlencode($search_project_ref);
		if ($show_files)            $param.='&show_files=' .urlencode($show_files);
		if ($optioncss != '')       $param.='&optioncss='.urlencode($optioncss);
		if ($billed != '')			$param.='&billed='.urlencode($billed);

		header("Location: ".$_SERVER['PHP_SELF'].'?'.$param);
		exit;
	}
	else
	{
		$db->rollback();
		$action='create';
		$_GET["origin"]=$_POST["origin"];
		$_GET["originid"]=$_POST["originid"];
		setEventMessages("Error", null, 'errors');
		$error++;
	}
}

if (!$error && $massaction == 'cancelorders')
{

	$db->begin();

	$nbok = 0;


	$orders = GETPOST('toselect', 'array');
	foreach ($orders as $id_order)
	{

		$cmd = new Commande($db);
		if ($cmd->fetch($id_order) <= 0)
			continue;

		if ($cmd->statut != Commande::STATUS_VALIDATED)
		{
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorObjectMustHaveStatusValidToBeCanceled", $cmd->ref), null, 'errors');
			$error++;
			break;
		}
		else
			$result = $cmd->cancel();

		if ($result < 0)
		{
			setEventMessages($cmd->error, $cmd->errors, 'errors');
			$error++;
			break;
		}
		else
			$nbok++;
	}
	if (!$error)
	{
		if ($nbok > 1)
			setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		else
			setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		$db->commit();
	}
	else
	{
		$db->rollback();
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
	foreach($listofobjectref as $tmppdf) $arrayofinclusion[]='^'.preg_quote(dol_sanitizeFileName($tmppdf).'.pdf','/').'$';
	$listoffiles = dol_dir_list($uploaddir,'all',1,implode('|',$arrayofinclusion),'\.meta$|\.png','date',SORT_DESC,0,true);

	// build list of files with full path
	$files = array();
	foreach($listofobjectref as $basename)
	{
		$basename = dol_sanitizeFileName($basename);
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
	if ($conf->global->MAIN_MULTILANGS && empty($newlang)) $newlang=$objecttmp->thirdparty->default_lang;
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
		if (in_array($objecttmp->element, array('facture', 'facture_fournisseur')) && $search_status == Facture::STATUS_VALIDATED)
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

			$cmd = 'pdftk '.escapeshellarg($input_files).' cat output '.escapeshellarg($file);
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
		if (in_array($objecttmp->element, array('facture', 'facture_fournisseur')) && $search_status == Facture::STATUS_VALIDATED)
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
	$objecttmp=new $objectclass($db);

	if ($objecttmp->element == 'invoice' && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_BILL))
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorMassValidationNotAllowedWhenStockIncreaseOnAction'), null, 'errors');
		$error++;
	}
	if ($objecttmp->element == 'invoice_supplier' && ! empty($conf->stock->enabled) && ! empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL))
	{
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorMassValidationNotAllowedWhenStockIncreaseOnAction'), null, 'errors');
		$error++;
	}
	if (! $error)
	{
		$db->begin();

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
// Closed records
if (!$error && $massaction == 'closed' && $objectclass == "Propal" && $permtoclose) {
    $db->begin();

    $objecttmp = new $objectclass($db);
    $nbok = 0;
    foreach ($toselect as $toselectid) {
        $result = $objecttmp->fetch($toselectid);
        if ($result > 0) {
            $result = $objecttmp->cloture($user, 3);
            if ($result <= 0) {
                setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
                $error++;
                break;
            } else
                $nbok++;
        }
        else {
            setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
            $error++;
            break;
        }
    }

    if (!$error) {
        if ($nbok > 1)
            setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
        else
            setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
        $db->commit();
    }
    else {
        $db->rollback();
    }
}
// Delete record from mass action (massaction = 'delete' for direct delete, action/confirm='delete'/'yes' with a confirmation step before)
if (! $error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permtodelete)
{
	$db->begin();

	$objecttmp=new $objectclass($db);
	$nbok = 0;
	foreach($toselect as $toselectid)
	{
		$result=$objecttmp->fetch($toselectid);
		if ($result > 0)
		{
			// Refuse deletion for some objects/status
			if ($objectclass == 'Facture' && empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED) && $objecttmp->status != Facture::STATUS_DRAFT)
			{
				$langs->load("errors");
				$nbignored++;
				$resaction.='<div class="error">'.$langs->trans('ErrorOnlyDraftStatusCanBeDeletedInMassAction',$objecttmp->ref).'</div><br>';
				continue;
			}

			if ($objectclass == "Task" && $objecttmp->hasChildren() > 0)
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET fk_task_parent = 0 WHERE fk_task_parent = ".$objecttmp->id;
				$res = $db->query($sql);

				if (!$res)
				{
					setEventMessage('ErrorRecordParentingNotModified', 'errors');
					$error++;
				}
			}

			if (in_array($objecttmp->element, array('societe', 'member'))) $result = $objecttmp->delete($objecttmp->id, $user, 1);
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



