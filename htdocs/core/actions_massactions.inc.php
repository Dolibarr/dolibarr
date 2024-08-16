<?php
/* Copyright (C) 2015-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018-2021 Nicolas ZABOURI	<info@inovea-conseil.com>
 * Copyright (C) 2018 	   Juanjo Menent  <jmenent@2byte.es>
 * Copyright (C) 2019 	   Ferran Marcet  <fmarcet@2byte.es>
 * Copyright (C) 2019-2021 Frédéric France <frederic.france@netlogic.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 * or see https://www.gnu.org/
 */

/**
 *	\file			htdocs/core/actions_massactions.inc.php
 *  \brief			Code for actions done with massaction button (send by email, merge pdf, delete, ...)
 */


// $massaction must be defined
// $objectclass and $objectlabel must be defined
// $parameters, $object, $action must be defined for the hook.

// $permissiontoread, $permissiontoadd, $permissiontodelete, $permissiontoclose may be defined
// $uploaddir may be defined (example to $conf->project->dir_output."/";)
// $toselect may be defined
// $diroutputmassaction may be defined


// Protection
if (empty($objectclass) || empty($uploaddir)) {
	dol_print_error(null, 'include of actions_massactions.inc.php is done but var $objectclass or $uploaddir was not defined');
	exit;
}

// For backward compatibility
if (!empty($permtoread) && empty($permissiontoread)) {
	$permissiontoread = $permtoread;
}
if (!empty($permtocreate) && empty($permissiontoadd)) {
	$permissiontoadd = $permtocreate;
}
if (!empty($permtodelete) && empty($permissiontodelete)) {
	$permissiontodelete = $permtodelete;
}


// Mass actions. Controls on number of lines checked.
$maxformassaction = (empty($conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS) ? 1000 : $conf->global->MAIN_LIMIT_FOR_MASS_ACTIONS);
if (!empty($massaction) && is_array($toselect) && count($toselect) < 1) {
	$error++;
	setEventMessages($langs->trans("NoRecordSelected"), null, "warnings");
}
if (!$error && is_array($toselect) && count($toselect) > $maxformassaction) {
	setEventMessages($langs->trans('TooManyRecordForMassAction', $maxformassaction), null, 'errors');
	$error++;
}

if (!$error && $massaction == 'confirm_presend' && !GETPOST('sendmail')) {  // If we do not choose button send (for example when we change template or limit), we must not send email, but keep on send email form
	$massaction = 'presend';
}

if (!$error && $massaction == 'confirm_presend') {
	$resaction = '';
	$nbsent = 0;
	$nbignored = 0;
	$langs->load("mails");
	include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	include_once DOL_DOCUMENT_ROOT.'/core/lib/signature.lib.php';

	$listofobjectid = array();
	$listofobjectthirdparties = array();
	$listofobjectcontacts = array();
	$listofobjectref = array();
	$contactidtosend = array();
	$attachedfilesThirdpartyObj = array();
	$oneemailperrecipient = (GETPOST('oneemailperrecipient', 'int') ? 1 : 0);

	if (!$error) {
		$thirdparty = new Societe($db);

		$objecttmp = new $objectclass($db);
		if ($objecttmp->element == 'expensereport') {
			$thirdparty = new User($db);
		}
		if ($objecttmp->element == 'partnership' && getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
			$thirdparty = new Adherent($db);
		}
		if ($objecttmp->element == 'holiday') {
			$thirdparty = new User($db);
		}

		foreach ($toselect as $toselectid) {
			$objecttmp = new $objectclass($db); // we must create new instance because instance is saved into $listofobjectref array for future use
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$listofobjectid[$toselectid] = $toselectid;

				$thirdpartyid = ($objecttmp->fk_soc ? $objecttmp->fk_soc : $objecttmp->socid);
				if ($objecttmp->element == 'societe') {
					$thirdpartyid = $objecttmp->id;
				}
				if ($objecttmp->element == 'expensereport') {
					$thirdpartyid = $objecttmp->fk_user_author;
				}
				if ($objecttmp->element == 'partnership' && getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
					$thirdpartyid = $objecttmp->fk_member;
				}
				if ($objecttmp->element == 'holiday') {
					$thirdpartyid = $objecttmp->fk_user;
				}
				if (empty($thirdpartyid)) {
					$thirdpartyid = 0;
				}

				if ($objectclass == 'Facture') {
					$tmparraycontact = array();
					$tmparraycontact = $objecttmp->liste_contact(-1, 'external', 0, 'BILLING');
					if (is_array($tmparraycontact) && count($tmparraycontact) > 0) {
						foreach ($tmparraycontact as $data_email) {
							$listofobjectcontacts[$toselectid][$data_email['id']] = $data_email['email'];
						}
					}
				} elseif ($objectclass == 'CommandeFournisseur') {
					$tmparraycontact = array();
					$tmparraycontact = $objecttmp->liste_contact(-1, 'external', 0, 'CUSTOMER');
					if (is_array($tmparraycontact) && count($tmparraycontact) > 0) {
						foreach ($tmparraycontact as $data_email) {
							$listofobjectcontacts[$toselectid][$data_email['id']] = $data_email['email'];
						}
					}
				}

				$listofobjectthirdparties[$thirdpartyid] = $thirdpartyid;
				$listofobjectref[$thirdpartyid][$toselectid] = $objecttmp;
			}
		}
	}

	// Check mandatory parameters
	if (GETPOST('fromtype', 'alpha') === 'user' && empty($user->email)) {
		$error++;
		setEventMessages($langs->trans("NoSenderEmailDefined"), null, 'warnings');
		$massaction = 'presend';
	}

	$receiver = GETPOST('receiver', 'alphawithlgt');
	if (!is_array($receiver)) {
		if (empty($receiver) || $receiver == '-1') {
			$receiver = array();
		} else {
			$receiver = array($receiver);
		}
	}
	if (!trim($_POST['sendto']) && count($receiver) == 0 && count($listofobjectthirdparties) == 1) {	// if only one recipient, receiver is mandatory
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Recipient")), null, 'warnings');
		$massaction = 'presend';
	}

	if (!GETPOST('subject', 'restricthtml')) {
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MailTopic")), null, 'warnings');
		$massaction = 'presend';
	}

	// Loop on each recipient/thirdparty
	if (!$error) {
		foreach ($listofobjectthirdparties as $thirdpartyid) {
			$result = $thirdparty->fetch($thirdpartyid);
			if ($result < 0) {
				dol_print_error($db);
				exit;
			}

			$sendto = '';
			$sendtocc = '';
			$sendtobcc = '';
			$sendtoid = array();

			// Define $sendto
			$tmparray = array();
			if (trim($_POST['sendto'])) {
				// Recipients are provided into free text
				$tmparray[] = trim(GETPOST('sendto', 'alphawithlgt'));
			}
			if (count($receiver) > 0) {
				foreach ($receiver as $key => $val) {
					// Recipient was provided from combo list
					if ($val == 'thirdparty') { // Id of third party or user
						$tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
					} elseif ($val && method_exists($thirdparty, 'contact_get_property')) {		// Id of contact
						$tmparray[] = $thirdparty->contact_get_property((int) $val, 'email');
						$sendtoid[] = $val;
					}
				}
			}
			$sendto = implode(',', $tmparray);

			// Define $sendtocc
			$receivercc = GETPOST('receivercc', 'alphawithlgt');
			if (!is_array($receivercc)) {
				if ($receivercc == '-1') {
					$receivercc = array();
				} else {
					$receivercc = array($receivercc);
				}
			}
			$tmparray = array();
			if (trim($_POST['sendtocc'])) {
				$tmparray[] = trim(GETPOST('sendtocc', 'alphawithlgt'));
			}
			if (count($receivercc) > 0) {
				foreach ($receivercc as $key => $val) {
					// Recipient was provided from combo list
					if ($val == 'thirdparty') { // Id of third party
						$tmparray[] = $thirdparty->name.' <'.$thirdparty->email.'>';
					} elseif ($val) {	// Id du contact
						$tmparray[] = $thirdparty->contact_get_property((int) $val, 'email');
						//$sendtoid[] = $val;  TODO Add also id of contact in CC ?
					}
				}
			}
			$sendtocc = implode(',', $tmparray);

			//var_dump($listofobjectref);exit;
			$listofqualifiedobj = array();
			$listofqualifiedref = array();
			$thirdpartywithoutemail = array();

			foreach ($listofobjectref[$thirdpartyid] as $objectid => $objectobj) {
				//var_dump($thirdpartyid.' - '.$objectid.' - '.$objectobj->statut);
				if ($objectclass == 'Propal' && $objectobj->statut == Propal::STATUS_DRAFT) {
					$langs->load("errors");
					$nbignored++;
					$resaction .= '<div class="error">'.$langs->trans('ErrorOnlyProposalNotDraftCanBeSentInMassAction', $objectobj->ref).'</div><br>';
					continue; // Payment done or started or canceled
				}
				if ($objectclass == 'Commande' && $objectobj->statut == Commande::STATUS_DRAFT) {
					$langs->load("errors");
					$nbignored++;
					$resaction .= '<div class="error">'.$langs->trans('ErrorOnlyOrderNotDraftCanBeSentInMassAction', $objectobj->ref).'</div><br>';
					continue;
				}
				if ($objectclass == 'Facture' && $objectobj->statut == Facture::STATUS_DRAFT) {
					$langs->load("errors");
					$nbignored++;
					$resaction .= '<div class="error">'.$langs->trans('ErrorOnlyInvoiceValidatedCanBeSentInMassAction', $objectobj->ref).'</div><br>';
					continue; // Payment done or started or canceled
				}

				// Test recipient
				if (empty($sendto)) { 	// For the case, no recipient were set (multi thirdparties send)
					if ($objectobj->element == 'societe') {
						$sendto = $objectobj->email;
					} elseif ($objectobj->element == 'expensereport') {
						$fuser = new User($db);
						$fuser->fetch($objectobj->fk_user_author);
						$sendto = $fuser->email;
					} elseif ($objectobj->element == 'partnership' && getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR') == 'member') {
						$fadherent = new Adherent($db);
						$fadherent->fetch($objectobj->fk_member);
						$sendto = $fadherent->email;
					} elseif ($objectobj->element == 'holiday') {
						$fuser = new User($db);
						$fuser->fetch($objectobj->fk_user);
						$sendto = $fuser->email;
					} elseif ($objectobj->element == 'facture' && !empty($listofobjectcontacts[$objectid])) {
						$emails_to_sends = array();
						$objectobj->fetch_thirdparty();
						$contactidtosend = array();
						foreach ($listofobjectcontacts[$objectid] as $contactemailid => $contactemailemail) {
							$emails_to_sends[] = $objectobj->thirdparty->contact_get_property($contactemailid, 'email');
							if (!in_array($contactemailid, $contactidtosend)) {
								$contactidtosend[] = $contactemailid;
							}
						}
						if (count($emails_to_sends) > 0) {
							$sendto = implode(',', $emails_to_sends);
						}
					} elseif ($objectobj->element == 'order_supplier' && !empty($listofobjectcontacts[$objectid])) {
						$emails_to_sends = array();
						$objectobj->fetch_thirdparty();
						$contactidtosend = array();
						foreach ($listofobjectcontacts[$objectid] as $contactemailid => $contactemailemail) {
							$emails_to_sends[] = $objectobj->thirdparty->contact_get_property($contactemailid, 'email');
							if (!in_array($contactemailid, $contactidtosend)) {
								$contactidtosend[] = $contactemailid;
							}
						}
						if (count($emails_to_sends) > 0) {
							$sendto = implode(',', $emails_to_sends);
						}
					} else {
						$objectobj->fetch_thirdparty();
						$sendto = $objectobj->thirdparty->email;
					}
				}

				if (empty($sendto)) {
					if ($objectobj->element == 'societe') {
						$objectobj->thirdparty = $objectobj; // Hack so following code is comaptible when objectobj is a thirdparty
					}

					//print "No recipient for thirdparty ".$objectobj->thirdparty->name;
					$nbignored++;
					if (empty($thirdpartywithoutemail[$objectobj->thirdparty->id])) {
						$resaction .= '<div class="error">'.$langs->trans('NoRecipientEmail', $objectobj->thirdparty->name).'</div><br>';
					}
					dol_syslog('No recipient for thirdparty: '.$objectobj->thirdparty->name, LOG_WARNING);
					$thirdpartywithoutemail[$objectobj->thirdparty->id] = 1;
					continue;
				}

				if (GETPOST('addmaindocfile')) {
					// TODO Use future field $objectobj->fullpathdoc to know where is stored default file
					// TODO If not defined, use $objectobj->model_pdf (or defaut invoice config) to know what is template to use to regenerate doc.
					$filename = dol_sanitizeFileName($objectobj->ref).'.pdf';
					$subdir = '';
					// TODO Set subdir to be compatible with multi levels dir trees
					// $subdir = get_exdir($objectobj->id, 2, 0, 0, $objectobj, $objectobj->element)
					$filedir = $uploaddir.'/'.$subdir.dol_sanitizeFileName($objectobj->ref);
					$filepath = $filedir.'/'.$filename;

					// For supplier invoices, we use the file provided by supplier, not the one we generate
					if ($objectobj->element == 'invoice_supplier') {
						$fileparams = dol_most_recent_file($uploaddir.'/'.get_exdir($objectobj->id, 2, 0, 0, $objectobj, $objectobj->element).$objectobj->ref, preg_quote($objectobj->ref, '/').'([^\-])+');
						$filepath = $fileparams['fullname'];
					}

					// try to find other files generated for this object (last_main_doc)
					$filename_found = '';
					$filepath_found = '';
					$file_check_list = array();
					$file_check_list[] = array(
						'name' => $filename,
						'path' => $filepath,
					);
					if (!empty($conf->global->MAIL_MASS_ACTION_ADD_LAST_IF_MAIN_DOC_NOT_FOUND) && !empty($objectobj->last_main_doc)) {
						$file_check_list[] = array(
							'name' => basename($objectobj->last_main_doc),
							'path' => DOL_DATA_ROOT . '/' . $objectobj->last_main_doc,
						);
					}
					foreach ($file_check_list as $file_check_arr) {
						if (dol_is_file($file_check_arr['path'])) {
							$filename_found = $file_check_arr['name'];
							$filepath_found = $file_check_arr['path'];
							break;
						}
					}

					if ($filepath_found) {
						// Create form object
						$attachedfilesThirdpartyObj[$thirdpartyid][$objectid] = array(
							'paths'=>array($filepath_found),
							'names'=>array($filename_found),
							'mimes'=>array(dol_mimetype($filepath_found))
						);
					} else {
						$nbignored++;
						$langs->load("errors");
						foreach ($file_check_list as $file_check_arr) {
							$resaction .= '<div class="error">'.$langs->trans('ErrorCantReadFile', $file_check_arr['path']).'</div><br>';
							dol_syslog('Failed to read file: '.$file_check_arr['path'], LOG_WARNING);
						}
						continue;
					}
				}

				// Object of thirdparty qualified, we add it
				$listofqualifiedobj[$objectid] = $objectobj;
				$listofqualifiedref[$objectid] = $objectobj->ref;


				//var_dump($listofqualifiedref);
			}

			// Send email if there is at least one qualified object for current thirdparty
			if (count($listofqualifiedobj) > 0) {
				$langs->load("commercial");

				$reg = array();
				$fromtype = GETPOST('fromtype');
				if ($fromtype === 'user') {
					$from = dol_string_nospecial($user->getFullName($langs), ' ', array(",")).' <'.$user->email.'>';
				} elseif ($fromtype === 'company') {
					$from = $conf->global->MAIN_INFO_SOCIETE_NOM.' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
				} elseif (preg_match('/user_aliases_(\d+)/', $fromtype, $reg)) {
					$tmp = explode(',', $user->email_aliases);
					$from = trim($tmp[($reg[1] - 1)]);
				} elseif (preg_match('/global_aliases_(\d+)/', $fromtype, $reg)) {
					$tmp = explode(',', $conf->global->MAIN_INFO_SOCIETE_MAIL_ALIASES);
					$from = trim($tmp[($reg[1] - 1)]);
				} elseif (preg_match('/senderprofile_(\d+)_(\d+)/', $fromtype, $reg)) {
					$sql = "SELECT rowid, label, email FROM ".MAIN_DB_PREFIX."c_email_senderprofile WHERE rowid = ".(int) $reg[1];
					$resql = $db->query($sql);
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$from = dol_string_nospecial($obj->label, ' ', array(",")).' <'.$obj->email.'>';
					}
				} else {
					$from = GETPOST('fromname').' <'.GETPOST('frommail').'>';
				}

				$replyto = $from;
				$subject = GETPOST('subject', 'restricthtml');
				$message = GETPOST('message', 'restricthtml');

				$sendtobcc = GETPOST('sendtoccc');
				if ($objectclass == 'Propal') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_PROPOSAL_TO));
				}
				if ($objectclass == 'Commande') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_ORDER_TO));
				}
				if ($objectclass == 'Facture') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_INVOICE_TO));
				}
				if ($objectclass == 'Supplier_Proposal') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_PROPOSAL_TO));
				}
				if ($objectclass == 'CommandeFournisseur') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_ORDER_TO));
				}
				if ($objectclass == 'FactureFournisseur') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_SUPPLIER_INVOICE_TO));
				}
				if ($objectclass == 'Project') {
					$sendtobcc .= (empty($conf->global->MAIN_MAIL_AUTOCOPY_PROJECT_TO) ? '' : (($sendtobcc ? ", " : "").$conf->global->MAIN_MAIL_AUTOCOPY_PROJECT_TO));
				}

				// $listofqualifiedobj is array with key = object id and value is instance of qualified objects, for the current thirdparty (but thirdparty property is not loaded yet)
				// $looparray will be an array with number of email to send for the current thirdparty (so 1 or n if n object for same thirdparty)
				$looparray = array();
				if (!$oneemailperrecipient) {
					$looparray = $listofqualifiedobj;
					foreach ($looparray as $key => $objecttmp) {
						$looparray[$key]->thirdparty = $thirdparty; // Force thirdparty on object
					}
				} else {
					$objectforloop = new $objectclass($db);
					$objectforloop->thirdparty = $thirdparty; // Force thirdparty on object (even if object was not loaded)
					$looparray[0] = $objectforloop;
				}
				//var_dump($looparray);exit;
				dol_syslog("We have set an array of ".count($looparray)." emails to send. oneemailperrecipient=".$oneemailperrecipient);
				//var_dump($oneemailperrecipient); var_dump($listofqualifiedobj); var_dump($listofqualifiedref);
				foreach ($looparray as $objectid => $objecttmp) {		// $objecttmp is a real object or an empty object if we choose to send one email per thirdparty instead of one per object
					// Make substitution in email content
					if (!empty($conf->project->enabled) && method_exists($objecttmp, 'fetch_projet') && is_null($objecttmp->project)) {
						$objecttmp->fetch_projet();
					}
					$substitutionarray = getCommonSubstitutionArray($langs, 0, null, $objecttmp);
					$substitutionarray['__ID__']    = ($oneemailperrecipient ? join(', ', array_keys($listofqualifiedobj)) : $objecttmp->id);
					$substitutionarray['__REF__']   = ($oneemailperrecipient ? join(', ', $listofqualifiedref) : $objecttmp->ref);
					$substitutionarray['__EMAIL__'] = $thirdparty->email;
					$substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.urlencode($thirdparty->tag).'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>';

					$parameters = array('mode'=>'formemail');

					if (!empty($listofobjectthirdparties)) {
						$parameters['listofobjectthirdparties'] = $listofobjectthirdparties;
					}
					if (!empty($listofobjectref)) {
						$parameters['listofobjectref'] = $listofobjectref;
					}

					complete_substitutions_array($substitutionarray, $langs, $objecttmp, $parameters);

					$subjectreplaced = make_substitutions($subject, $substitutionarray);
					$messagereplaced = make_substitutions($message, $substitutionarray);

					$attachedfiles = array('paths'=>array(), 'names'=>array(), 'mimes'=>array());
					if ($oneemailperrecipient) {
						// if "one email per recipient" is check we must collate $attachedfiles by thirdparty
						if (is_array($attachedfilesThirdpartyObj[$thirdparty->id]) && count($attachedfilesThirdpartyObj[$thirdparty->id])) {
							foreach ($attachedfilesThirdpartyObj[$thirdparty->id] as $keyObjId => $objAttachedFiles) {
								// Create form object
								$attachedfiles = array(
									'paths'=>array_merge($attachedfiles['paths'], $objAttachedFiles['paths']),
									'names'=>array_merge($attachedfiles['names'], $objAttachedFiles['names']),
									'mimes'=>array_merge($attachedfiles['mimes'], $objAttachedFiles['mimes'])
								);
							}
						}
					} elseif (!empty($attachedfilesThirdpartyObj[$thirdparty->id][$objectid])) {
						// Create form object
						// if "one email per recipient" isn't check we must separate $attachedfiles by object
						$attachedfiles = $attachedfilesThirdpartyObj[$thirdparty->id][$objectid];
					}

					$filepath = $attachedfiles['paths'];
					$filename = $attachedfiles['names'];
					$mimetype = $attachedfiles['mimes'];

					// Define the trackid when emails sent from the mass action
					if ($oneemailperrecipient) {
						$trackid = 'thi'.$thirdparty->id;
						if ($objecttmp->element == 'expensereport') {
							$trackid = 'use'.$thirdparty->id;
						}
						if ($objecttmp->element == 'holiday') {
							$trackid = 'use'.$thirdparty->id;
						}
					} else {
						$trackid = strtolower(get_class($objecttmp));
						if (get_class($objecttmp) == 'Contrat') {
							$trackid = 'con';
						}
						if (get_class($objecttmp) == 'Propal') {
							$trackid = 'pro';
						}
						if (get_class($objecttmp) == 'Commande') {
							$trackid = 'ord';
						}
						if (get_class($objecttmp) == 'Facture') {
							$trackid = 'inv';
						}
						if (get_class($objecttmp) == 'Supplier_Proposal') {
							$trackid = 'spr';
						}
						if (get_class($objecttmp) == 'CommandeFournisseur') {
							$trackid = 'sor';
						}
						if (get_class($objecttmp) == 'FactureFournisseur') {
							$trackid = 'sin';
						}

						$trackid .= $objecttmp->id;
					}
					//var_dump($filepath);
					//var_dump($trackid);exit;
					//var_dump($subjectreplaced);

					if (empty($sendcontext)) {
						$sendcontext = 'standard';
					}

					// Send mail (substitutionarray must be done just before this)
					require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
					$mailfile = new CMailFile($subjectreplaced, $sendto, $from, $messagereplaced, $filepath, $mimetype, $filename, $sendtocc, $sendtobcc, $deliveryreceipt, -1, '', '', $trackid, '', $sendcontext);
					if ($mailfile->error) {
						$resaction .= '<div class="error">'.$mailfile->error.'</div>';
					} else {
						$result = $mailfile->sendfile();
						if ($result > 0) {
							$resaction .= $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($mailfile->addr_from, 2), $mailfile->getValidAddress($mailfile->addr_to, 2)).'<br>'; // Must not contain "

							$error = 0;

							// Insert logs into agenda
							foreach ($listofqualifiedobj as $objid2 => $objectobj2) {
								if ((!$oneemailperrecipient) && $objid2 != $objectid) {
									continue; // We discard this pass to avoid duplicate with other pass in looparray at higher level
								}

								dol_syslog("Try to insert email event into agenda for objid=".$objid2." => objectobj=".get_class($objectobj2));

								/*if ($objectclass == 'Propale') $actiontypecode='AC_PROP';
								if ($objectclass == 'Commande') $actiontypecode='AC_COM';
								if ($objectclass == 'Facture') $actiontypecode='AC_FAC';
								if ($objectclass == 'Supplier_Proposal') $actiontypecode='AC_SUP_PRO';
								if ($objectclass == 'CommandeFournisseur') $actiontypecode='AC_SUP_ORD';
								if ($objectclass == 'FactureFournisseur') $actiontypecode='AC_SUP_INV';*/

								$actionmsg = $langs->transnoentities('MailSentBy').' '.$from.' '.$langs->transnoentities('To').' '.$sendto;
								if ($message) {
									if ($sendtocc) {
										$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('Bcc').": ".$sendtocc);
									}
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('MailTopic').": ".$subjectreplaced);
									$actionmsg = dol_concatdesc($actionmsg, $langs->transnoentities('TextUsedInTheMessageBody').":");
									$actionmsg = dol_concatdesc($actionmsg, $messagereplaced);
								}
								$actionmsg2 = '';

								// Initialisation donnees
								$objectobj2->sendtoid = (empty($contactidtosend) ? 0 : $contactidtosend);
								$objectobj2->actionmsg = $actionmsg; // Long text
								$objectobj2->actionmsg2		= $actionmsg2; // Short text
								$objectobj2->fk_element		= $objid2;
								$objectobj2->elementtype	= $objectobj2->element;

								$triggername = strtoupper(get_class($objectobj2)).'_SENTBYMAIL';
								if ($triggername == 'SOCIETE_SENTBYMAIL') {
									$triggername = 'COMPANY_SENTBYMAIL';
								}
								if ($triggername == 'CONTRAT_SENTBYMAIL') {
									$triggername = 'CONTRACT_SENTBYMAIL';
								}
								if ($triggername == 'COMMANDE_SENTBYMAIL') {
									$triggername = 'ORDER_SENTBYMAIL';
								}
								if ($triggername == 'FACTURE_SENTBYMAIL') {
									$triggername = 'BILL_SENTBYMAIL';
								}
								if ($triggername == 'EXPEDITION_SENTBYMAIL') {
									$triggername = 'SHIPPING_SENTBYMAIL';
								}
								if ($triggername == 'COMMANDEFOURNISSEUR_SENTBYMAIL') {
									$triggername = 'ORDER_SUPPLIER_SENTBYMAIL';
								}
								if ($triggername == 'FACTUREFOURNISSEUR_SENTBYMAIL') {
									$triggername = 'BILL_SUPPLIER_SENTBYMAIL';
								}
								if ($triggername == 'SUPPLIERPROPOSAL_SENTBYMAIL') {
									$triggername = 'PROPOSAL_SUPPLIER_SENTBYMAIL';
								}

								if (!empty($triggername)) {
									// Call trigger
									$result = $objectobj2->call_trigger($triggername, $user);
									if ($result < 0) {
										$error++;
									}
									// End call triggers

									if ($error) {
										setEventMessages($db->lasterror(), $errors, 'errors');
										dol_syslog("Error in trigger ".$triggername.' '.$db->lasterror(), LOG_ERR);
									}
								}

								$nbsent++; // Nb of object sent
							}
						} else {
							$langs->load("other");
							if ($mailfile->error) {
								$resaction .= $langs->trans('ErrorFailedToSendMail', $from, $sendto);
								$resaction .= '<br><div class="error">'.$mailfile->error.'</div>';
							} elseif (!empty($conf->global->MAIN_DISABLE_ALL_MAILS)) {
								$resaction .= '<div class="warning">No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS</div>';
							} else {
								$resaction .= $langs->trans('ErrorFailedToSendMail', $from, $sendto) . '<br><div class="error">(unhandled error)</div>';
							}
						}
					}
				}
			}
		}

		$resaction .= ($resaction ? '<br>' : $resaction);
		$resaction .= '<strong>'.$langs->trans("ResultOfMailSending").':</strong><br>'."\n";
		$resaction .= $langs->trans("NbSelected").': '.count($toselect)."\n<br>";
		$resaction .= $langs->trans("NbIgnored").': '.($nbignored ? $nbignored : 0)."\n<br>";
		$resaction .= $langs->trans("NbSent").': '.($nbsent ? $nbsent : 0)."\n<br>";

		if ($nbsent) {
			$action = ''; // Do not show form post if there was at least one successfull sent
			//setEventMessages($langs->trans("EMailSentToNRecipients", $nbsent.'/'.count($toselect)), null, 'mesgs');
			setEventMessages($langs->trans("EMailSentForNElements", $nbsent.'/'.count($toselect)), null, 'mesgs');
			setEventMessages($resaction, null, 'mesgs');
		} else {
			//setEventMessages($langs->trans("EMailSentToNRecipients", 0), null, 'warnings');  // May be object has no generated PDF file
			setEventMessages($resaction, null, 'warnings');
		}

		$action = 'list';
		$massaction = '';
	}
}


if (!$error && $massaction == 'cancelorders') {
	$db->begin();

	$nbok = 0;


	$orders = GETPOST('toselect', 'array');
	foreach ($orders as $id_order) {
		$cmd = new Commande($db);
		if ($cmd->fetch($id_order) <= 0) {
			continue;
		}

		if ($cmd->statut != Commande::STATUS_VALIDATED) {
			$langs->load('errors');
			setEventMessages($langs->trans("ErrorObjectMustHaveStatusValidToBeCanceled", $cmd->ref), null, 'errors');
			$error++;
			break;
		} else {
			// TODO We do not provide warehouse so no stock change here for the moment.
			$result = $cmd->cancel();
		}

		if ($result < 0) {
			setEventMessages($cmd->error, $cmd->errors, 'errors');
			$error++;
			break;
		} else {
			$nbok++;
		}
	}
	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}


if (!$error && $massaction == "builddoc" && $permissiontoread && !GETPOST('button_search')) {
	if (empty($diroutputmassaction)) {
		dol_print_error(null, 'include of actions_massactions.inc.php is done but var $diroutputmassaction was not defined');
		exit;
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	$objecttmp = new $objectclass($db);
	$listofobjectid = array();
	$listofobjectthirdparties = array();
	$listofobjectref = array();
	foreach ($toselect as $toselectid) {
		$objecttmp = new $objectclass($db); // must create new instance because instance is saved into $listofobjectref array for future use
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			$listofobjectid[$toselectid] = $toselectid;
			$thirdpartyid = $objecttmp->fk_soc ? $objecttmp->fk_soc : $objecttmp->socid;
			$listofobjectthirdparties[$thirdpartyid] = $thirdpartyid;
			$listofobjectref[$toselectid] = $objecttmp->ref;
		}
	}

	$arrayofinclusion = array();
	foreach ($listofobjectref as $tmppdf) {
		$arrayofinclusion[] = '^'.preg_quote(dol_sanitizeFileName($tmppdf), '/').'\.pdf$';
	}
	foreach ($listofobjectref as $tmppdf) {
		$arrayofinclusion[] = '^'.preg_quote(dol_sanitizeFileName($tmppdf), '/').'_[a-zA-Z0-9\-\_\'\&\.]+\.pdf$'; // To include PDF generated from ODX files
	}
	$listoffiles = dol_dir_list($uploaddir, 'all', 1, implode('|', $arrayofinclusion), '\.meta$|\.png', 'date', SORT_DESC, 0, true);

	// build list of files with full path
	$files = array();


    foreach ($listofobjectref as $basename) {
        $basename = dol_sanitizeFileName($basename);
        foreach ($listoffiles as $filefound) {
            if (strstr($filefound["name"], $basename)) {
                $files[] = $filefound['fullname'];
                break;
            }
        }
    }
    
	// Define output language (Here it is not used because we do only merging existing PDF)
	$outputlangs = $langs;
	$newlang = '';
	if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
		$newlang = GETPOST('lang_id', 'aZ09');
	}
	//elseif ($conf->global->MAIN_MULTILANGS && empty($newlang) && is_object($objecttmp->thirdparty)) {		// On massaction, we can have several values for $objecttmp->thirdparty
	//	$newlang = $objecttmp->thirdparty->default_lang;
	//}
	if (!empty($newlang)) {
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang($newlang);
	}

	if (!empty($conf->global->USE_PDFTK_FOR_PDF_CONCAT)) {
		// Create output dir if not exists
		dol_mkdir($diroutputmassaction);

		// Defined name of merged file
		$filename = strtolower(dol_sanitizeFileName($langs->transnoentities($objectlabel)));
		$filename = preg_replace('/\s/', '_', $filename);

		// Save merged file
		if (in_array($objecttmp->element, array('facture', 'invoice_supplier')) && $search_status == Facture::STATUS_VALIDATED) {
			if ($option == 'late') {
				$filename .= '_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid"))).'_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Late")));
			} else {
				$filename .= '_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid")));
			}
		}
		if ($year) {
			$filename .= '_'.$year;
		}
		if ($month) {
			$filename .= '_'.$month;
		}

		if (count($files) > 0) {
			$now = dol_now();
			$file = $diroutputmassaction.'/'.$filename.'_'.dol_print_date($now, 'dayhourlog').'.pdf';

			$input_files = '';
			foreach ($files as $f) {
				$input_files .= ' '.escapeshellarg($f);
			}

			$cmd = 'pdftk '.$input_files.' cat output '.escapeshellarg($file);
			exec($cmd);

			// check if pdftk is installed
			if (file_exists($file)) {
				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$langs->load("exports");
				setEventMessages($langs->trans('FileSuccessfullyBuilt', $filename.'_'.dol_print_date($now, 'dayhourlog')), null, 'mesgs');
			} else {
				setEventMessages($langs->trans('ErrorPDFTkOutputFileNotFound'), null, 'errors');
			}
		} else {
			setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
		}
	} else {
		// Create empty PDF
		$formatarray = pdf_getFormat();
		$page_largeur = $formatarray['width'];
		$page_hauteur = $formatarray['height'];
		$format = array($page_largeur, $page_hauteur);

		$pdf = pdf_getInstance($format);

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		if (!empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) {
			$pdf->SetCompression(false);
		}
        
		// Add all others
		foreach ($files as $file) {
			// Charge un document PDF depuis un fichier.
			$pagecount = $pdf->setSourceFile($file);
			for ($i = 1; $i <= $pagecount; $i++) {
				$tplidx = $pdf->importPage($i);
				$s = $pdf->getTemplatesize($tplidx);
				$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
				$pdf->useTemplate($tplidx);
			}
		}

		// Create output dir if not exists
		dol_mkdir($diroutputmassaction);

		// Defined name of merged file
		$filename = strtolower(dol_sanitizeFileName($langs->transnoentities($objectlabel)));
		$filename = preg_replace('/\s/', '_', $filename);
        

		// Save merged file
		if (in_array($objecttmp->element, array('facture', 'invoice_supplier')) && $search_status == Facture::STATUS_VALIDATED) {
			if ($option == 'late') {
				$filename .= '_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid"))).'_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Late")));
			} else {
				$filename .= '_'.strtolower(dol_sanitizeFileName($langs->transnoentities("Unpaid")));
			}
		}
		if ($year) {
			$filename .= '_'.$year;
		}
		if ($month) {
			$filename .= '_'.$month;
		}
		if ($pagecount) {
			$now = dol_now();
			$file = $diroutputmassaction.'/'.$filename.'_'.dol_print_date($now, 'dayhourlog').'.pdf';
			$pdf->Output($file, 'F');
			if (!empty($conf->global->MAIN_UMASK)) {
				@chmod($file, octdec($conf->global->MAIN_UMASK));
			}

			$langs->load("exports");
			setEventMessages($langs->trans('FileSuccessfullyBuilt', $filename.'_'.dol_print_date($now, 'dayhourlog')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans('NoPDFAvailableForDocGenAmongChecked'), null, 'errors');
		}
	}
}

// Remove a file from massaction area
if ($action == 'remove_file') {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

	$langs->load("other");
	$upload_dir = $diroutputmassaction;
	$file = $upload_dir.'/'.GETPOST('file');
	$ret = dol_delete_file($file);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
	}
	$action = '';
}


// Validate records
if (!$error && $massaction == 'validate' && $permissiontoadd) {
	$objecttmp = new $objectclass($db);

	if (($objecttmp->element == 'facture' || $objecttmp->element == 'invoice') && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_BILL)) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorMassValidationNotAllowedWhenStockIncreaseOnAction'), null, 'errors');
		$error++;
	}
	if ($objecttmp->element == 'invoice_supplier' && !empty($conf->stock->enabled) && !empty($conf->global->STOCK_CALCULATE_ON_SUPPLIER_BILL)) {
		$langs->load("errors");
		setEventMessages($langs->trans('ErrorMassValidationNotAllowedWhenStockIncreaseOnAction'), null, 'errors');
		$error++;
	}
	if ($objecttmp->element == 'facture') {
		if (!empty($toselect) && !empty($conf->global->INVOICE_CHECK_POSTERIOR_DATE)) {
			// order $toselect by date
			$sql  = "SELECT rowid FROM ".MAIN_DB_PREFIX."facture";
			$sql .= " WHERE rowid IN (".$db->sanitize(implode(",", $toselect)).")";
			$sql .= " ORDER BY datef";

			$resql = $db->query($sql);
			if ($resql) {
				$toselectnew = [];
				while ( !empty($arr = $db->fetch_row($resql))) {
					$toselectnew[] = $arr[0];
				}
				$toselect = (empty($toselectnew)) ? $toselect : $toselectnew;
			} else {
				dol_print_error($db);
				$error++;
			}
		}
	}
	if (!$error) {
		$db->begin();

		$nbok = 0;
		foreach ($toselect as $toselectid) {
			$result = $objecttmp->fetch($toselectid);
			if ($result > 0) {
				$result = $objecttmp->validate($user);
				if ($result == 0) {
					$langs->load("errors");
					setEventMessages($langs->trans("ErrorObjectMustHaveStatusDraftToBeValidated", $objecttmp->ref), null, 'errors');
					$error++;
					break;
				} elseif ($result < 0) {
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
					break;
				} else {
					// validate() rename pdf but do not regenerate
					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
							$newlang = GETPOST('lang_id', 'aZ09');
						}
						if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
							$newlang = $objecttmp->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
							$outputlangs->load('products');
						}
						$model = $objecttmp->model_pdf;
						$ret = $objecttmp->fetch($objecttmp->id); // Reload to get new records
						// To be sure vars is defined
						$hidedetails = !empty($hidedetails) ? $hidedetails : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0);
						$hidedesc = !empty($hidedesc) ? $hidedesc : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0);
						$hideref = !empty($hideref) ? $hideref : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0);
						$moreparams = !empty($moreparams) ? $moreparams : null;

						$result = $objecttmp->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
						if ($result < 0) {
							setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
						}
					}
					$nbok++;
				}
			} else {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			}
		}

		if (!$error) {
			if ($nbok > 1) {
				setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("RecordModifiedSuccessfully"), null, 'mesgs');
			}
			$db->commit();
		} else {
			$db->rollback();
		}
		//var_dump($listofobjectthirdparties);exit;
	}
}

//var_dump($_POST);var_dump($massaction);exit;

// Delete record from mass action (massaction = 'delete' for direct delete, action/confirm='delete'/'yes' with a confirmation step before)
if (!$error && ($massaction == 'delete' || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	$TMsg = array();

	//$toselect could contain duplicate entries, cf https://github.com/Dolibarr/dolibarr/issues/26244
	$unique_arr = array_unique($toselect);
	foreach ($unique_arr as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			// Refuse deletion for some objects/status
			if ($objectclass == 'Facture' && empty($conf->global->INVOICE_CAN_ALWAYS_BE_REMOVED) && $objecttmp->status != Facture::STATUS_DRAFT) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorOnlyDraftStatusCanBeDeletedInMassAction', $objecttmp->ref).'</div><br>';
				continue;
			}

			if (method_exists($objecttmp, 'is_erasable') && $objecttmp->is_erasable() <= 0) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorRecordHasChildren').' '.$objecttmp->ref.'</div><br>';
				continue;
			}

			if ($objectclass == 'Holiday' && ! in_array($objecttmp->statut, array(Holiday::STATUS_DRAFT, Holiday::STATUS_CANCELED, Holiday::STATUS_REFUSED))) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorLeaveRequestMustBeDraftCanceledOrRefusedToBeDeleted', $objecttmp->ref).'</div><br>';
				continue;
			}

			if ($objectclass == "Task" && $objecttmp->hasChildren() > 0) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task SET fk_task_parent = 0 WHERE fk_task_parent = ".((int) $objecttmp->id);
				$res = $db->query($sql);

				if (!$res) {
					setEventMessage('ErrorRecordParentingNotModified', 'errors');
					$error++;
				}
			}

			if (in_array($objecttmp->element, array('societe', 'member'))) {
				$result = $objecttmp->delete($objecttmp->id, $user, 1);
			} elseif (in_array($objecttmp->element, array('action'))) {
				$result = $objecttmp->delete();
			} else {
				$result = $objecttmp->delete($user);
			}

			if (empty($result)) { // if delete returns 0, there is at least one object linked
				$TMsg = array_merge($objecttmp->errors, $TMsg);
			} elseif ($result < 0) { // if delete returns is < 0, there is an error, we break and rollback later
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (empty($error)) {
		// Message for elements well deleted
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
		} elseif ($nbok > 0) {
			setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
		}

		// Message for elements which can't be deleted
		if (!empty($TMsg)) {
			sort($TMsg);
			setEventMessages('', array_unique($TMsg), 'warnings');
		}

		$db->commit();
	} else {
		$db->rollback();
	}

	//var_dump($listofobjectthirdparties);exit;
}

// Generate document foreach object according to model linked to object
// @todo : propose model selection
if (!$error && $massaction == 'generate_doc' && $permissiontoread) {
	$db->begin();
	$objecttmp = new $objectclass($db);
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
				$newlang = GETPOST('lang_id', 'aZ09');
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($objecttmp->thirdparty->default_lang)) {
				$newlang = $objecttmp->thirdparty->default_lang; // for proposal, order, invoice, ...
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && isset($objecttmp->default_lang)) {
				$newlang = $objecttmp->default_lang; // for thirdparty
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && empty($objecttmp->thirdparty)) { //load lang from thirdparty
				$objecttmp->fetch_thirdparty();
				$newlang = $objecttmp->thirdparty->default_lang; // for proposal, order, invoice, ...
			}
			if (!empty($newlang)) {
				$outputlangs = new Translate("", $conf);
				$outputlangs->setDefaultLang($newlang);
			}

			// To be sure vars is defined
			if (empty($hidedetails)) {
				$hidedetails = (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0);
			}
			if (empty($hidedesc)) {
				$hidedesc = (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0);
			}
			if (empty($hideref)) {
				$hideref = (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0);
			}
			if (empty($moreparams)) {
				$moreparams = null;
			}

			$result = $objecttmp->generateDocument($objecttmp->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);

			if ($result <= 0) {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsGenerated", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("RecordGenerated", $nbok), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}

if (!$error && ($action == 'affecttag' && $confirm == 'yes') && $permissiontoadd) {
	$db->begin();

	$affecttag_type=GETPOST('affecttag_type', 'alpha');
	if (!empty($affecttag_type)) {
		$affecttag_type_array=explode(',', $affecttag_type);
	} else {
		setEventMessage('CategTypeNotFound', 'errors');
	}
	if (!empty($affecttag_type_array)) {
		//check if tag type submited exists into Tag Map categorie class
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categ = new Categorie($db);
		$to_affecttag_type_array=array();
		$categ_type_array=$categ->getMapList();
		foreach ($categ_type_array as $categdef) {
			if (in_array($categdef['code'],  $affecttag_type_array)) {
				$to_affecttag_type_array[] = $categdef['code'];
			}
		}

		//For each valid categ type set common categ
		$nbok = 0;
		if (!empty($to_affecttag_type_array)) {
			foreach ($to_affecttag_type_array as $categ_type) {
				$contcats = GETPOST('contcats_' . $categ_type, 'array');
				//var_dump($toselect);exit;
				foreach ($toselect as $toselectid) {
					$result = $object->fetch($toselectid);
					//var_dump($contcats);exit;
					if ($result > 0) {
						$result = $object->setCategoriesCommon($contcats, $categ_type, false);
						if ($result > 0) {
							$nbok++;
						} else {
							setEventMessages($object->error, $object->errors, 'errors');
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
						$error++;
						break;
					}
				}
			}
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsModified", $nbok), null);
		} else {
			setEventMessages($langs->trans("RecordsModified", $nbok), null);
		}
		$db->commit();
		$toselect=array();
	} else {
		$db->rollback();
	}
}

if (!$error && ($massaction == 'enable' || ($action == 'enable' && $confirm == 'yes')) && $permissiontoadd) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result>0) {
			if (in_array($objecttmp->element, array('societe'))) {
				$result =$objecttmp->setStatut(1);
			}
			if ($result <= 0) {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsEnabled", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("RecordEnabled"), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}

if (!$error && ($massaction == 'disable' || ($action == 'disable' && $confirm == 'yes')) && $permissiontoadd) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result>0) {
			if (in_array($objecttmp->element, array('societe'))) {
				$result =$objecttmp->setStatut(0);
			}
			if ($result <= 0) {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsDisabled", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("RecordDisabled"), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}

if (!$error && $action == 'confirm_edit_value_extrafields' && $confirm == 'yes' && $permissiontoadd) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$e = new ExtraFields($db);// fetch optionals attributes and labels
	$e->fetch_name_optionals_label($objecttmp->table_element);

	$nbok = 0;
	$extrafieldKeyToUpdate = GETPOST('extrafield-key-to-update');


	foreach ($toselect as $toselectid) {
		/** @var CommonObject $objecttmp */
		$objecttmp = new $objectclass($db); // to avoid ghost data
		$result = $objecttmp->fetch($toselectid);
		if ($result>0) {
			// Fill array 'array_options' with data from add form
			$ret = $e->setOptionalsFromPost(null, $objecttmp, $extrafieldKeyToUpdate);
			if ($ret > 0) {
				$objecttmp->insertExtraFields();
			} else {
				$error++;
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsDisabled", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("save"), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}

if (!$error && ($massaction == 'affectcommercial' || ($action == 'affectcommercial' && $confirm == 'yes')) && $permissiontoadd) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;

	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result>0) {
			if (in_array($objecttmp->element, array('societe'))) {
				$result = $objecttmp->setSalesRep(GETPOST("commercial", "alpha"));
			}
			if ($result <= 0) {
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("CommercialsAffected", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("CommercialAffected"), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}

// Approve for leave only
if (!$error && ($massaction == 'approveleave' || ($action == 'approveleave' && $confirm == 'yes')) && $permissiontoapprove) {
	$db->begin();

	$objecttmp = new $objectclass($db);
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			if ($objecttmp->statut != Holiday::STATUS_VALIDATED) {
				setEventMessages($langs->trans('StatusOfRefMustBe', $objecttmp->ref, $langs->transnoentitiesnoconv('Validated')), null, 'warnings');
				continue;
			}
			if ($user->id == $objecttmp->fk_validator) {
				$objecttmp->oldcopy = dol_clone($objecttmp);

				$objecttmp->date_valid = dol_now();
				$objecttmp->fk_user_valid = $user->id;
				$objecttmp->statut = Holiday::STATUS_APPROVED;

				$verif = $objecttmp->approve($user);

				if ($verif <= 0) {
					setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
					$error++;
				}

				// If no SQL error, we redirect to the request form
				if (!$error) {
					// Calculcate number of days consumed
					$nbopenedday = num_open_day($objecttmp->date_debut_gmt, $objecttmp->date_fin_gmt, 0, 1, $objecttmp->halfday);
					$soldeActuel = $objecttmp->getCpforUser($objecttmp->fk_user, $objecttmp->fk_type);
					$newSolde = ($soldeActuel - $nbopenedday);

					// The modification is added to the LOG
					$result = $objecttmp->addLogCP($user->id, $objecttmp->fk_user, $langs->transnoentitiesnoconv("Holidays"), $newSolde, $objecttmp->fk_type);
					if ($result < 0) {
						$error++;
						setEventMessages(null, $objecttmp->errors, 'errors');
					}

					// Update balance
					$result = $objecttmp->updateSoldeCP($objecttmp->fk_user, $newSolde, $objecttmp->fk_type);
					if ($result < 0) {
						$error++;
						setEventMessages(null, $objecttmp->errors, 'errors');
					}
				}

				if (!$error) {
					// To
					$destinataire = new User($db);
					$destinataire->fetch($objecttmp->fk_user);
					$emailTo = $destinataire->email;

					if (!$emailTo) {
						dol_syslog("User that request leave has no email, so we redirect directly to finished page without sending email");
					} else {
						// From
						$expediteur = new User($db);
						$expediteur->fetch($objecttmp->fk_validator);
						//$emailFrom = $expediteur->email;		Email of user can be an email into another company. Sending will fails, we must use the generic email.
						$emailFrom = $conf->global->MAIN_MAIL_EMAIL_FROM;

						// Subject
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
							$societeName = $conf->global->MAIN_APPLICATION_TITLE;
						}

						$subject = $societeName." - ".$langs->transnoentitiesnoconv("HolidaysValidated");

						// Content
						$message = $langs->transnoentitiesnoconv("Hello")." ".$destinataire->firstname.",\n";
						$message .= "\n";

						$message .= $langs->transnoentities("HolidaysValidatedBody", dol_print_date($objecttmp->date_debut, 'day'), dol_print_date($objecttmp->date_fin, 'day'))."\n";

						$message .= "- ".$langs->transnoentitiesnoconv("ValidatedBy")." : ".dolGetFirstLastname($expediteur->firstname, $expediteur->lastname)."\n";

						$message .= "- ".$langs->transnoentitiesnoconv("Link")." : ".$dolibarr_main_url_root."/holiday/card.php?id=".$objecttmp->id."\n\n";
						$message .= "\n";

						$trackid = 'leav'.$objecttmp->id;

						require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
						$mail = new CMailFile($subject, $emailTo, $emailFrom, $message, array(), array(), array(), '', '', 0, 0, '', '', $trackid);

						// Sending email
						$result = $mail->sendfile();

						if (!$result) {
							setEventMessages($mail->error, $mail->errors, 'warnings'); // Show error, but do no make rollback, so $error is not set to 1
							$action = '';
						}
					}
				}
			} else {
				$langs->load("errors");
				setEventMessages($langs->trans('ErrorNotApproverForHoliday', $objecttmp->ref), null, 'errors');
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}

	if (!$error) {
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsApproved", $nbok), null, 'mesgs');
		} elseif ($nbok == 1) {
			setEventMessages($langs->trans("RecordAproved"), null, 'mesgs');
		}
		$db->commit();
	} else {
		$db->rollback();
	}
}

$parameters['toselect'] = $toselect;
$parameters['uploaddir'] = $uploaddir;
$parameters['massaction'] = $massaction;
$parameters['diroutputmassaction'] = isset($diroutputmassaction) ? $diroutputmassaction : null;

$reshook = $hookmanager->executeHooks('doMassActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
