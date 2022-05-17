<?php
/* Copyright (C) 2003       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2020  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2021  Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 */

/**
 *  \file       	htdocs/expensereport/card.php
 *  \ingroup    	expensereport
 *  \brief      	Page for trip and expense report card
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/expensereport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/expensereport/class/paymentexpensereport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if (!empty($conf->accounting->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("trips", "bills", "mails"));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$id = GETPOST('id', 'int');
$date_start = dol_mktime(0, 0, 0, GETPOST('date_debutmonth', 'int'), GETPOST('date_debutday', 'int'), GETPOST('date_debutyear', 'int'));
$date_end = dol_mktime(0, 0, 0, GETPOST('date_finmonth', 'int'), GETPOST('date_finday', 'int'), GETPOST('date_finyear', 'int'));
$date = dol_mktime(0, 0, 0, GETPOST('datemonth', 'int'), GETPOST('dateday', 'int'), GETPOST('dateyear', 'int'));
$fk_project = GETPOST('fk_project', 'int');
$vatrate = GETPOST('vatrate', 'alpha');
$ref = GETPOST("ref", 'alpha');
$comments = GETPOST('comments', 'restricthtml');
$fk_c_type_fees = GETPOST('fk_c_type_fees', 'int');
$socid = GETPOST('socid', 'int') ?GETPOST('socid', 'int') : GETPOST('socid_id', 'int');

$childids = $user->getAllChildIds(1);

if (! empty($conf->global->EXPENSEREPORT_PREFILL_DATES_WITH_CURRENT_MONTH)) {
	if (empty($date_start)) {
		$date_start = dol_mktime(0, 0, 0, (int) dol_print_date(dol_now(), '%m'),  1, (int) dol_print_date(dol_now(), '%Y'));
	}

	if (empty($date_end)) {
		// date('t') => number of days in the month, so last day of the month too
		$date_end = dol_mktime(0, 0, 0, (int) dol_print_date(dol_now(), '%m'), (int) date('t'), (int) dol_print_date(dol_now(), '%Y'));
	}
}

// Hack to use expensereport dir
$rootfordata = DOL_DATA_ROOT;
$rootforuser = DOL_DATA_ROOT;
// If multicompany module is enabled, we redefine the root of data
if (!empty($conf->multicompany->enabled) && !empty($conf->entity) && $conf->entity > 1) {
	$rootfordata .= '/'.$conf->entity;
}
$conf->expensereport->dir_output = $rootfordata.'/expensereport';

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// PDF
$hidedetails = (GETPOST('hidedetails', 'int') ? GETPOST('hidedetails', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DETAILS) ? 1 : 0));
$hidedesc = (GETPOST('hidedesc', 'int') ? GETPOST('hidedesc', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_DESC) ? 1 : 0));
$hideref = (GETPOST('hideref', 'int') ? GETPOST('hideref', 'int') : (!empty($conf->global->MAIN_GENERATE_DOCUMENTS_HIDE_REF) ? 1 : 0));


$object = new ExpenseReport($db);
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('expensereportcard', 'globalcard'));

$permissionnote = $user->rights->expensereport->creer; // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->rights->expensereport->creer; // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->rights->expensereport->creer; // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php

$upload_dir = $conf->expensereport->dir_output.'/'.dol_sanitizeFileName($object->ref);

$projectRequired = $conf->projet->enabled && ! empty($conf->global->EXPENSEREPORT_PROJECT_IS_REQUIRED);
$fileRequired = !empty($conf->global->EXPENSEREPORT_FILE_IS_REQUIRED);

if ($object->id > 0) {
	// Check current user can read this expense report
	$canread = 0;
	if (!empty($user->rights->expensereport->readall)) {
		$canread = 1;
	}
	if (!empty($user->rights->expensereport->lire) && in_array($object->fk_user_author, $childids)) {
		$canread = 1;
	}
	if (!$canread) {
		accessforbidden();
	}
}

$candelete = 0;
if (!empty($user->rights->expensereport->supprimer)) {
	$candelete = 1;
}
if ($object->statut == ExpenseReport::STATUS_DRAFT && $user->rights->expensereport->write && in_array($object->fk_user_author, $childids)) {
	$candelete = 1;
}

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'expensereport', $object->id, 'expensereport');

$permissiontoadd = $user->rights->expensereport->creer;	// Used by the include of actions_dellink.inc.php


/*
 * Actions
 */

$parameters = array('socid' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/expensereport/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/expensereport/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';

		$fk_project = '';
		$date_start = '';
		$date_end = '';
		$date = '';
		$comments = '';
		$vatrate = '';
		$value_unit_ht = '';
		$value_unit = '';
		$qty = 1;
		$fk_c_type_fees = -1;
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

	if (!empty(GETPOST('sendit', 'alpha'))) {   // If we just submit a file
		if ($action == 'updateline') {
			$action = 'editline'; // To avoid to make the updateline now
		} else {
			$action = ''; // To avoid to make the addline now
		}
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_setnotes.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php'; // Must be include, not include_once

	include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php'; // Must be include, not include_once

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $user->rights->expensereport->creer) {
		if (1 == 0 && !GETPOST('clone_content', 'alpha') && !GETPOST('clone_receivers', 'alpha')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			if ($object->id > 0) {
				// Because createFromClone modifies the object, we must clone it so that we can restore it later if it fails
				$orig = clone $object;

				$result = $object->createFromClone($user, GETPOST('fk_user_author', 'int'));
				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
					exit;
				} else {
					setEventMessages($object->error, $object->errors, 'errors');
					$object = $orig;
					$action = '';
				}
			}
		}
	}

	if ($action == 'confirm_delete' && GETPOST("confirm", 'alpha') == "yes" && $id > 0 && $candelete) {
		$object = new ExpenseReport($db);
		$result = $object->fetch($id);
		$result = $object->delete($user);
		if ($result >= 0) {
			header("Location: index.php");
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'add' && $user->rights->expensereport->creer) {
		$error = 0;

		$object = new ExpenseReport($db);

		$object->date_debut = $date_start;
		$object->date_fin = $date_end;

		$object->fk_user_author = GETPOST('fk_user_author', 'int');
		if (!($object->fk_user_author > 0)) {
			$object->fk_user_author = $user->id;
		}

		// Check that expense report is for a user inside the hierarchy, or that advanced permission for all is set
		if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->expensereport->creer))
			|| (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && empty($user->rights->expensereport->creer) && empty($user->rights->expensereport->writeall_advance))) {
			$error++;
			setEventMessages($langs->trans("NotEnoughPermission"), null, 'errors');
		}
		if (!$error) {
			if (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->expensereport->writeall_advance)) {
				if (!in_array($object->fk_user_author, $childids)) {
					$error++;
					setEventMessages($langs->trans("UserNotInHierachy"), null, 'errors');
				}
			}
		}

		$fuser = new User($db);
		$fuser->fetch($object->fk_user_author);

		$object->status = 1;
		$object->fk_c_paiement = GETPOST('fk_c_paiement', 'int');
		$object->fk_user_validator = GETPOST('fk_user_validator', 'int');
		$object->note_public = GETPOST('note_public', 'restricthtml');
		$object->note_private = GETPOST('note_private', 'restricthtml');
		// Fill array 'array_options' with data from add form
		if (!$error) {
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) {
				$error++;
			}
		}

		if (!$error && empty($conf->global->EXPENSEREPORT_ALLOW_OVERLAPPING_PERIODS)) {
			$overlappingExpenseReportID = $object->periode_existe($fuser, $object->date_debut, $object->date_fin, true);

			if ($overlappingExpenseReportID > 0) {
				$error++;
				setEventMessages($langs->trans("ErrorDoubleDeclaration").' <a href="'.$_SERVER['PHP_SELF'].'?id='.$overlappingExpenseReportID.'">'. $langs->trans('ShowTrip').'</a>', null, 'errors');
				$action = 'create';
			}
		}

		if (!$error) {
			$db->begin();

			$id = $object->create($user);
			if ($id <= 0) {
				$error++;
			}

			if (!$error) {
				$db->commit();
				Header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
				$action = 'create';
			}
		}
	}

	if ($action == 'update' && $user->rights->expensereport->creer) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		$object->date_debut = $date_start;
		$object->date_fin = $date_end;

		if ($object->status < 3) {
			$object->fk_user_validator = GETPOST('fk_user_validator', 'int');
		}

		$object->fk_c_paiement = GETPOST('fk_c_paiement', 'int');
		$object->note_public = GETPOST('note_public', 'restricthtml');
		$object->note_private = GETPOST('note_private', 'restricthtml');
		$object->fk_user_modif = $user->id;

		$result = $object->update($user);
		if ($result > 0) {
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".GETPOST('id', 'int'));
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == 'update_extras') {
		$object->oldcopy = dol_clone($object);

		// Fill array 'array_options' with data from update form
		$ret = $extrafields->setOptionalsFromPost(null, $object, GETPOST('attribute', 'restricthtml'));
		if ($ret < 0) {
			$error++;
		}

		if (!$error) {
			// Actions on extra fields
			$result = $object->insertExtraFields('EXPENSEREPORT_MODIFY');
			if ($result < 0) {
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error) {
			$action = 'edit_extras';
		}
	}

	if ($action == "confirm_validate" && GETPOST("confirm", 'alpha') == "yes" && $id > 0 && $user->rights->expensereport->creer) {
		$error = 0;

		$db->begin();

		$object = new ExpenseReport($db);
		$object->fetch($id);

		$result = $object->setValidate($user);

		if ($result >= 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}

		if (!$error && $result > 0 && $object->fk_user_validator > 0) {
			$langs->load("mails");

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_validator);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_author);
			$emailFrom = $expediteur->email;

			if ($emailTo && $emailFrom) {
				$filename = array(); $filedir = array(); $mimetype = array();

				// SUBJECT
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentities("ExpenseReportWaitingForApproval");

				// CONTENT
				$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
				$link = '<a href="'.$link.'">'.$link.'</a>';
				$message = $langs->transnoentities("ExpenseReportWaitingForApprovalMessage", $expediteur->getFullName($langs), get_date_range($object->date_debut, $object->date_fin, '', $langs), $link);

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
				$mailfile = new CMailFile($subject, $emailTo, $emailFrom, $message, $filedir, $mimetype, $filename, '', '', 0, -1);

				if ($mailfile) {
					// SEND
					$result = $mailfile->sendfile();
					if ($result) {
						$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($emailFrom, 2), $mailfile->getValidAddress($emailTo, 2));
						setEventMessages($mesg, null, 'mesgs');
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							$mesg = '';
							$mesg .= $langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
							$mesg .= '<br>'.$mailfile->error;
							setEventMessages($mesg, null, 'errors');
						} else {
							setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
						}
					}
				} else {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					$action = '';
				}
			} else {
				setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
				$action = '';
			}
		}

		if (!$error) {
			$db->commit();
			header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
			exit;
		} else {
			$db->rollback();
		}
	}

	if ($action == "confirm_save_from_refuse" && GETPOST("confirm", 'alpha') == "yes" && $id > 0 && $user->rights->expensereport->creer) {
		$object = new ExpenseReport($db);
		$object->fetch($id);
		$result = $object->set_save_from_refuse($user);

		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		if ($result > 0) {
			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_validator);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_author);
			$emailFrom = $expediteur->email;

			if ($emailFrom && $emailTo) {
				$filename = array(); $filedir = array(); $mimetype = array();

				// SUBJECT
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentities("ExpenseReportWaitingForReApproval");

				// CONTENT
				$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
				$link = '<a href="'.$link.'">'.$link.'</a>';
				$dateRefusEx = explode(" ", $object->date_refuse);
				$message = $langs->transnoentities("ExpenseReportWaitingForReApprovalMessage", $dateRefusEx[0], $object->detail_refuse, $expediteur->getFullName($langs), $link);

				// Rebuild pdf
				/*
				$object->setDocModel($user,"");
				$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);

				   if($resultPDF)
				   {
					   // ATTACHMENT
					   $filename=array(); $filedir=array(); $mimetype=array();
					   array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
					   array_push($filedir,$conf->expensereport->dir_output . "/" . dol_sanitizeFileName($object->ref) . "/" . dol_sanitizeFileName($object->ref_number).".pdf");
					   array_push($mimetype,"application/pdf");
				}
				*/


				// PREPARE SEND
				$mailfile = new CMailFile($subject, $emailTo, $emailFrom, $message, $filedir, $mimetype, $filename, '', '', 0, -1);

				if ($mailfile) {
					// SEND
					$result = $mailfile->sendfile();
					if ($result) {
						$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($emailFrom, 2), $mailfile->getValidAddress($emailTo, 2));
						setEventMessages($mesg, null, 'mesgs');
						header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
						exit;
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							$mesg = '';
							$mesg .= $langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
							$mesg .= '<br>'.$mailfile->error;
							setEventMessages($mesg, null, 'errors');
						} else {
							setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
						}
					}
				} else {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					$action = '';
				}
			} else {
				setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
				$action = '';
			}
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	// Approve
	if ($action == "confirm_approve" && GETPOST("confirm", 'alpha') == "yes" && $id > 0 && $user->rights->expensereport->approve) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		$result = $object->setApproved($user);

		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		if ($result > 0) {
			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_author);
			$emailTo = $destinataire->email;

			// CC
			$emailCC = $conf->global->NDF_CC_EMAILS;
			if (empty($emailTo)) {
				$emailTo = $emailCC;
			}

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_approve > 0 ? $object->fk_user_approve : $object->fk_user_validator);
			$emailFrom = $expediteur->email;

			if ($emailFrom && $emailTo) {
				$filename = array(); $filedir = array(); $mimetype = array();

				// SUBJECT
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentities("ExpenseReportApproved");

				// CONTENT
				$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
				$link = '<a href="'.$link.'">'.$link.'</a>';
				$message = $langs->transnoentities("ExpenseReportApprovedMessage", $object->ref, $destinataire->getFullName($langs), $expediteur->getFullName($langs), $link);

				// Rebuilt pdf
				/*
				$object->setDocModel($user,"");
				$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);

				if($resultPDF
				{
					// ATTACHMENT
					$filename=array(); $filedir=array(); $mimetype=array();
					array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
					array_push($filedir, $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref)."/".dol_sanitizeFileName($object->ref).".pdf");
					array_push($mimetype,"application/pdf");
				}
				*/

				$mailfile = new CMailFile($subject, $emailTo, $emailFrom, $message, $filedir, $mimetype, $filename, '', '', 0, -1);

				if ($mailfile) {
					// SEND
					$result = $mailfile->sendfile();
					if ($result) {
						$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($emailFrom, 2), $mailfile->getValidAddress($emailTo, 2));
						setEventMessages($mesg, null, 'mesgs');
						header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
						exit;
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							$mesg = '';
							$mesg .= $langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
							$mesg .= '<br>'.$mailfile->error;
							setEventMessages($mesg, null, 'errors');
						} else {
							setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
						}
					}
				} else {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					$action = '';
				}
			} else {
				setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
				$action = '';
			}
		} else {
			setEventMessages($langs->trans("FailedtoSetToApprove"), null, 'warnings');
			$action = '';
		}
	}

	if ($action == "confirm_refuse" && GETPOST('confirm', 'alpha') == "yes" && $id > 0 && $user->rights->expensereport->approve) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		$detailRefuse = GETPOST('detail_refuse', 'alpha');
		$result = $object->setDeny($user, $detailRefuse);

		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		if ($result > 0) {
			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_author);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($object->fk_user_refuse);
			$emailFrom = $expediteur->email;

			if ($emailFrom && $emailTo) {
				$filename = array(); $filedir = array(); $mimetype = array();

				// SUBJECT
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentities("ExpenseReportRefused");

				// CONTENT
				$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
				$link = '<a href="'.$link.'">'.$link.'</a>';
				$message = $langs->transnoentities("ExpenseReportRefusedMessage", $object->ref, $destinataire->getFullName($langs), $expediteur->getFullName($langs), $detailRefuse, $link);

				// Rebuilt pdf
				/*
				$object->setDocModel($user,"");
				$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);

				if($resultPDF
				{
					// ATTACHMENT
					$filename=array(); $filedir=array(); $mimetype=array();
					array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
					array_push($filedir, $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref)."/".dol_sanitizeFileName($object->ref).".pdf");
					array_push($mimetype,"application/pdf");
				}
				*/

				// PREPARE SEND
				$mailfile = new CMailFile($subject, $emailTo, $emailFrom, $message, $filedir, $mimetype, $filename, '', '', 0, -1);

				if ($mailfile) {
					// SEND
					$result = $mailfile->sendfile();
					if ($result) {
						$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($emailFrom, 2), $mailfile->getValidAddress($emailTo, 2));
						setEventMessages($mesg, null, 'mesgs');
						header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
						exit;
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							$mesg = '';
							$mesg .= $langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
							$mesg .= '<br>'.$mailfile->error;
							setEventMessages($mesg, null, 'errors');
						} else {
							setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
						}
					}
				} else {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					$action = '';
				}
			} else {
				setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
				$action = '';
			}
		} else {
			setEventMessages($langs->trans("FailedtoSetToDeny"), null, 'warnings');
			$action = '';
		}
	}

	//var_dump($user->id == $object->fk_user_validator);exit;
	if ($action == "confirm_cancel" && GETPOST('confirm', 'alpha') == "yes" && $id > 0 && $user->rights->expensereport->creer) {
		if (!GETPOST('detail_cancel', 'alpha')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Comment")), null, 'errors');
		} else {
			$object = new ExpenseReport($db);
			$object->fetch($id);

			if ($user->id == $object->fk_user_valid || $user->id == $object->fk_user_author) {
				$detailCancel = GETPOST('detail_cancel', 'alpha');
				$result = $object->set_cancel($user, $detailCancel);

				if ($result > 0) {
					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
							$newlang = GETPOST('lang_id', 'aZ09');
						}
						if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
							$newlang = $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$model = $object->model_pdf;
						$ret = $object->fetch($id); // Reload to get new records

						$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}
				}

				if ($result > 0) {
					// Send mail

					// TO
					$destinataire = new User($db);
					$destinataire->fetch($object->fk_user_author);
					$emailTo = $destinataire->email;

					// FROM
					$expediteur = new User($db);
					$expediteur->fetch($object->fk_user_cancel);
					$emailFrom = $expediteur->email;

					if ($emailFrom && $emailTo) {
						$filename = array(); $filedir = array(); $mimetype = array();

						// SUBJECT
						$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
						if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
							$societeName = $conf->global->MAIN_APPLICATION_TITLE;
						}

						$subject = $societeName." - ".$langs->transnoentities("ExpenseReportCanceled");

						// CONTENT
						$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
						$link = '<a href="'.$link.'">'.$link.'</a>';
						$message = $langs->transnoentities("ExpenseReportCanceledMessage", $object->ref, $destinataire->getFullName($langs), $expediteur->getFullName($langs), $detailCancel, $link);

						// Rebuilt pdf
						/*
						$object->setDocModel($user,"");
						$resultPDF = expensereport_pdf_create($db,$object,'',"",$langs);

						if($resultPDF
						{
							// ATTACHMENT
							$filename=array(); $filedir=array(); $mimetype=array();
							array_push($filename,dol_sanitizeFileName($object->ref).".pdf");
							array_push($filedir, $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref)."/".dol_sanitizeFileName($object->ref).".pdf");
							array_push($mimetype,"application/pdf");
						}
						*/

						// PREPARE SEND
						$mailfile = new CMailFile($subject, $emailTo, $emailFrom, $message, $filedir, $mimetype, $filename, '', '', 0, -1);

						if ($mailfile) {
							// SEND
							$result = $mailfile->sendfile();
							if ($result) {
								$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($emailFrom, 2), $mailfile->getValidAddress($emailTo, 2));
								setEventMessages($mesg, null, 'mesgs');
								header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
								exit;
							} else {
								$langs->load("other");
								if ($mailfile->error) {
									$mesg = '';
									$mesg .= $langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
									$mesg .= '<br>'.$mailfile->error;
									setEventMessages($mesg, null, 'errors');
								} else {
									setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
								}
							}
						} else {
							setEventMessages($mailfile->error, $mailfile->errors, 'errors');
							$action = '';
						}
					} else {
						setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
						$action = '';
					}
				} else {
					setEventMessages($langs->trans("FailedToSetToCancel"), null, 'warnings');
					$action = '';
				}
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	if ($action == "confirm_setdraft" && GETPOST('confirm', 'alpha') == "yes" && $id > 0 && $user->rights->expensereport->creer) {
		$object = new ExpenseReport($db);
		$object->fetch($id);
		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid) {
			$result = $object->setStatut(0);

			if ($result > 0) {
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model = $object->model_pdf;
					$ret = $object->fetch($id); // Reload to get new records

					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}

			if ($result > 0) {
				header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			setEventMessages("NOT_AUTHOR", '', 'errors');
		}
	}

	if ($action == 'set_unpaid' && $id > 0 && $user->rights->expensereport->to_paid) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		$result = $object->setUnpaid($user);

		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}
	}

	if ($action == 'set_paid' && $id > 0 && $user->rights->expensereport->to_paid) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		$result = $object->setPaid($id, $user);

		if ($result > 0) {
			// Define output language
			if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
				$outputlangs = $langs;
				$newlang = '';
				if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
					$newlang = GETPOST('lang_id', 'aZ09');
				}
				if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
					$newlang = $object->thirdparty->default_lang;
				}
				if (!empty($newlang)) {
					$outputlangs = new Translate("", $conf);
					$outputlangs->setDefaultLang($newlang);
				}
				$model = $object->model_pdf;
				$ret = $object->fetch($id); // Reload to get new records

				$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
			}
		}

		if ($result > 0) {
			// Send mail

			// TO
			$destinataire = new User($db);
			$destinataire->fetch($object->fk_user_author);
			$emailTo = $destinataire->email;

			// FROM
			$expediteur = new User($db);
			$expediteur->fetch($user->id);
			$emailFrom = $expediteur->email;

			if ($emailFrom && $emailTo) {
				$filename = array(); $filedir = array(); $mimetype = array();

				// SUBJECT
				$societeName = $conf->global->MAIN_INFO_SOCIETE_NOM;
				if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
					$societeName = $conf->global->MAIN_APPLICATION_TITLE;
				}

				$subject = $societeName." - ".$langs->transnoentities("ExpenseReportPaid");

				// CONTENT
				$link = $urlwithroot.'/expensereport/card.php?id='.$object->id;
				$link = '<a href="'.$link.'">'.$link.'</a>';
				$message = $langs->transnoentities("ExpenseReportPaidMessage", $object->ref, $destinataire->getFullName($langs), $expediteur->getFullName($langs), $link);

				// Generate pdf before attachment
				$object->setDocModel($user, "");
				$resultPDF = expensereport_pdf_create($db, $object, '', "", $langs);

				// PREPARE SEND
				$mailfile = new CMailFile($subject, $emailTo, $emailFrom, $message, $filedir, $mimetype, $filename, '', '', 0, -1);

				if ($mailfile) {
					// SEND
					$result = $mailfile->sendfile();
					if ($result) {
						$mesg = $langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($emailFrom, 2), $mailfile->getValidAddress($emailTo, 2));
						setEventMessages($mesg, null, 'mesgs');
						header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
						exit;
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							$mesg = '';
							$mesg .= $langs->trans('ErrorFailedToSendMail', $emailFrom, $emailTo);
							$mesg .= '<br>'.$mailfile->error;
							setEventMessages($mesg, null, 'errors');
						} else {
							setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'warnings');
						}
					}
				} else {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
					$action = '';
				}
			} else {
				setEventMessages($langs->trans("NoEmailSentBadSenderOrRecipientEmail"), null, 'warnings');
				$action = '';
			}
		} else {
			setEventMessages($langs->trans("FailedToSetPaid"), null, 'warnings');
			$action = '';
		}
	}

	if ($action == "addline" && $user->rights->expensereport->creer) {
		$error = 0;

		// First save uploaded file
		$fk_ecm_files = 0;
		if (GETPOSTISSET('attachfile')) {
			$arrayoffiles = GETPOST('attachfile', 'array');
			if (is_array($arrayoffiles) && !empty($arrayoffiles[0])) {
				include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
				$entityprefix = ($conf->entity != '1') ? $conf->entity.'/' : '';
				$relativepath = 'expensereport/'.$object->ref.'/'.$arrayoffiles[0];
				$ecmfiles = new EcmFiles($db);
				$ecmfiles->fetch(0, '', $relativepath);
				$fk_ecm_files = $ecmfiles->id;
			}
		}

		// if VAT is not used in Dolibarr, set VAT rate to 0 because VAT rate is necessary.
		if (empty($vatrate)) {
			$vatrate = "0.000";
		}
		$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $vatrate));

		$value_unit_ht = price2num(GETPOST('value_unit_ht', 'alpha'), 'MU');
		$value_unit = price2num(GETPOST('value_unit', 'alpha'), 'MU');
		if (empty($value_unit)) {
			$value_unit = price2num($value_unit_ht + ($value_unit_ht * $tmpvat / 100), 'MU');
		}

		$fk_c_exp_tax_cat = GETPOST('fk_c_exp_tax_cat', 'int');

		$qty = price2num(GETPOST('qty', 'alpha'));
		if (empty($qty)) {
			$qty = 1;
		}

		if (!($fk_c_type_fees > 0)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
			$action = '';
		}

		if ((float) $tmpvat < 0 || $tmpvat === '') {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("VAT")), null, 'errors');
			$action = '';
		}

		// If no date entered
		if (empty($date) || $date == "--") {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Date")), null, 'errors');
		} elseif ($date < $object->date_debut || $date > ($object->date_fin + (24 * 3600 - 1))) {
			// Warning if date out of range
			$langs->load("errors");
			setEventMessages($langs->trans("WarningDateOfLineMustBeInExpenseReportRange"), null, 'warnings');
		}

		// If no price entered
		if ($value_unit == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("PriceUTTC")), null, 'errors');
		}

		// If no project entered
		if ($projectRequired && $fk_project <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Project")), null, 'errors');
		}

		// If no file associated
		if ($fileRequired && $fk_ecm_files == 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
		}

		if (!$error) {
			$type = 0; // TODO What if service ? We should take the type product/service from the type of expense report llx_c_type_fees

			// Insert line
			$result = $object->addline($qty, $value_unit, $fk_c_type_fees, $vatrate, $date, $comments, $fk_project, $fk_c_exp_tax_cat, $type, $fk_ecm_files);
			if ($result > 0) {
				$ret = $object->fetch($object->id); // Reload to get new records

				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					// Define output language
					$outputlangs = $langs;
					$newlang = GETPOST('lang_id', 'alpha');
					if (!empty($conf->global->MAIN_MULTILANGS) && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}

					$object->generateDocument($object->model_pdf, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}

				unset($qty);
				unset($value_unit_ht);
				unset($value_unit);
				unset($vatrate);
				unset($comments);
				unset($fk_c_type_fees);
				unset($fk_project);

				unset($date);
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}

		$action = '';
	}

	if ($action == 'confirm_delete_line' && GETPOST("confirm", 'alpha') == "yes" && $user->rights->expensereport->creer) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		$object_ligne = new ExpenseReportLine($db);
		$object_ligne->fetch(GETPOST("rowid", 'int'));
		$total_ht = $object_ligne->total_ht;
		$total_tva = $object_ligne->total_tva;

		$result = $object->deleteline(GETPOST("rowid", 'int'), $user);
		if ($result >= 0) {
			if ($result > 0) {
				// Define output language
				if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
					$outputlangs = $langs;
					$newlang = '';
					if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
						$newlang = GETPOST('lang_id', 'aZ09');
					}
					if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
						$newlang = $object->thirdparty->default_lang;
					}
					if (!empty($newlang)) {
						$outputlangs = new Translate("", $conf);
						$outputlangs->setDefaultLang($newlang);
					}
					$model = $object->model_pdf;
					$ret = $object->fetch($id); // Reload to get new records

					$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
				}
			}

			header("Location: ".$_SERVER["PHP_SELF"]."?id=".GETPOST('id', 'int'));
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}

	if ($action == "updateline" && $user->rights->expensereport->creer) {
		$object = new ExpenseReport($db);
		$object->fetch($id);

		// First save uploaded file
		$fk_ecm_files = 0;
		if (GETPOSTISSET('attachfile')) {
			$arrayoffiles = GETPOST('attachfile', 'array');
			if (is_array($arrayoffiles) && !empty($arrayoffiles[0])) {
				include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
				$relativepath = 'expensereport/'.$object->ref.'/'.$arrayoffiles[0];
				$ecmfiles = new EcmFiles($db);
				$ecmfiles->fetch(0, '', $relativepath);
				$fk_ecm_files = $ecmfiles->id;
			}
		}

		$rowid = GETPOST('rowid', 'int');
		$type_fees_id = GETPOST('fk_c_type_fees', 'int');
		$fk_c_exp_tax_cat = GETPOST('fk_c_exp_tax_cat', 'int');
		$projet_id = $fk_project;
		$comments = GETPOST('comments', 'restricthtml');
		$qty = price2num(GETPOST('qty', 'alpha'));
		$vatrate = GETPOST('vatrate', 'alpha');

		// if VAT is not used in Dolibarr, set VAT rate to 0 because VAT rate is necessary.
		if (empty($vatrate)) {
			$vatrate = "0.000";
		}
		$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $vatrate));

		$value_unit_ht = price2num(GETPOST('value_unit_ht', 'alpha'), 'MU');
		$value_unit = price2num(GETPOST('value_unit', 'alpha'), 'MU');
		if (empty($value_unit)) {
			$value_unit = price2num($value_unit_ht + ($value_unit_ht * $tmpvat / 100), 'MU');
		}

		if (!GETPOST('fk_c_type_fees', 'int') > 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Type")), null, 'errors');
			$action = '';
		}
		if ((float) $tmpvat < 0 || $tmpvat == '') {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Vat")), null, 'errors');
			$action = '';
		}
		// Warning if date out of range
		if ($date < $object->date_debut || $date > ($object->date_fin + (24 * 3600 - 1))) {
			$langs->load("errors");
			setEventMessages($langs->trans("WarningDateOfLineMustBeInExpenseReportRange"), null, 'warnings');
		}

		// If no project entered
		if ($projectRequired && $projet_id <= 0) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Project")), null, 'errors');
		}

		if (!$error) {
			// TODO Use update method of ExpenseReportLine
			$result = $object->updateline($rowid, $type_fees_id, $projet_id, $vatrate, $comments, $qty, $value_unit, $date, $id, $fk_c_exp_tax_cat, $fk_ecm_files);
			if ($result >= 0) {
				if ($result > 0) {
					// Define output language
					if (empty($conf->global->MAIN_DISABLE_PDF_AUTOUPDATE)) {
						$outputlangs = $langs;
						$newlang = '';
						if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) {
							$newlang = GETPOST('lang_id', 'aZ09');
						}
						if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
							$newlang = $object->thirdparty->default_lang;
						}
						if (!empty($newlang)) {
							$outputlangs = new Translate("", $conf);
							$outputlangs->setDefaultLang($newlang);
						}
						$model = $object->model_pdf;
						$ret = $object->fetch($id); // Reload to get new records

						$object->generateDocument($model, $outputlangs, $hidedetails, $hidedesc, $hideref);
					}

					unset($qty);
					unset($value_unit_ht);
					unset($value_unit);
					unset($vatrate);
					unset($comments);
					unset($fk_c_type_fees);
					unset($fk_project);
					unset($date);
				}

				//header("Location: ".$_SERVER["PHP_SELF"]."?id=".$id);
				//exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
	}

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'EXPENSEREPORT_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_EXPENSEREPORT_TO';
	$trackid = 'exp'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Actions to build doc
	$upload_dir = $conf->expensereport->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$title = $langs->trans("ExpenseReport")." - ".$langs->trans("Card");
$help_url = "EN:Module_Expense_Reports|FR:Module_Notes_de_frais";

llxHeader("", $title, $help_url);

$form = new Form($db);
$formfile = new FormFile($db);
$formproject = new FormProjets($db);
$projecttmp = new Project($db);
$paymentexpensereportstatic = new PaymentExpenseReport($db);
$bankaccountstatic = new Account($db);
$ecmfilesstatic = new EcmFiles($db);
$formexpensereport = new FormExpenseReport($db);

// Create
if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewTrip"), '', 'trip');

	print '<form action="'.$_SERVER['PHP_SELF'].'" method="post" name="create">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	print dol_get_fiche_head('');

	print '<table class="border centpercent">';
	print '<tbody>';

	// Date start
	print '<tr>';
	print '<td class="titlefieldcreate fieldrequired">'.$langs->trans("DateStart").'</td>';
	print '<td>';
	print $form->selectDate($date_start ? $date_start : -1, 'date_debut', 0, 0, 0, '', 1, 1);
	print '</td>';
	print '</tr>';

	// Date end
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("DateEnd").'</td>';
	print '<td>';
	print $form->selectDate($date_end ? $date_end : -1, 'date_fin', 0, 0, 0, '', 1, 1);
	print '</td>';
	print '</tr>';

	// User for expense report
	print '<tr>';
	print '<td class="fieldrequired">'.$langs->trans("User").'</td>';
	print '<td>';
	$defaultselectuser = $user->id;
	if (GETPOST('fk_user_author', 'int') > 0) {
		$defaultselectuser = GETPOST('fk_user_author', 'int');
	}
	$include_users = 'hierarchyme';
	if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS) && !empty($user->rights->expensereport->writeall_advance)) {
		$include_users = array();
	}
	$s = $form->select_dolusers($defaultselectuser, "fk_user_author", 0, "", 0, $include_users, '', '0,'.$conf->entity);
	print $s;
	print '</td>';
	print '</tr>';

	// Approver
	print '<tr>';
	print '<td>'.$langs->trans("VALIDATOR").'</td>';
	print '<td>';
	$object = new ExpenseReport($db);
	$include_users = $object->fetch_users_approver_expensereport();
	if (empty($include_users)) {
		print img_warning().' '.$langs->trans("NobodyHasPermissionToValidateExpenseReport");
	} else {
		$defaultselectuser = (empty($user->fk_user_expense_validator) ? $user->fk_user : $user->fk_user_expense_validator); // Will work only if supervisor has permission to approve so is inside include_users
		if (!empty($conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR)) {
			$defaultselectuser = $conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR; // Can force default approver
		}
		if (GETPOST('fk_user_validator', 'int') > 0) {
			$defaultselectuser = GETPOST('fk_user_validator', 'int');
		}
		$s = $form->select_dolusers($defaultselectuser, "fk_user_validator", 1, "", ((empty($defaultselectuser) || empty($conf->global->EXPENSEREPORT_DEFAULT_VALIDATOR_UNCHANGEABLE)) ? 0 : 1), $include_users);
		print $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
	}
	print '</td>';
	print '</tr>';

	// Payment mode
	if (!empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION)) {
		print '<tr>';
		print '<td>'.$langs->trans("ModePaiement").'</td>';
		print '<td>';
		$form->select_types_paiements('', 'fk_c_paiement');
		print '</td>';
		print '</tr>';
	}

	// Public note
	print '<tr>';
	print '<td class="tdtop">'.$langs->trans('NotePublic').'</td>';
	print '<td>';

	$doleditor = new DolEditor('note_public', $note_public, '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PUBLIC) ? 0 : 1, ROWS_3, '90%');
	print $doleditor->Create(1);
	print '</td></tr>';

	// Private note
	if (empty($user->socid)) {
		print '<tr>';
		print '<td class="tdtop">'.$langs->trans('NotePrivate').'</td>';
		print '<td>';

		$doleditor = new DolEditor('note_private', $note_private, '', 80, 'dolibarr_notes', 'In', 0, false, empty($conf->global->FCKEDITOR_ENABLE_NOTE_PRIVATE) ? 0 : 1, ROWS_3, '90%');
		print $doleditor->Create(1);
		print '</td></tr>';
	}

	// Other attributes
	$parameters = array('colspan' => ' colspan="3"', 'cols' => 3);
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create', $parameters);
	}

	print '<tbody>';
	print '</table>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("AddTrip");

	print '</form>';
} elseif ($id > 0 || $ref) {
	$result = $object->fetch($id, $ref);

	if ($result > 0) {
		if (!in_array($object->fk_user_author, $user->getAllChildIds(1))) {
			if (empty($user->rights->expensereport->readall) && empty($user->rights->expensereport->lire_tous)
				&& (empty($conf->global->MAIN_USE_ADVANCED_PERMS) || empty($user->rights->expensereport->writeall_advance))) {
					print load_fiche_titre($langs->trans('TripCard'), '', 'trip');

					print '<div class="tabBar">';
					print $langs->trans('NotUserRightToView');
					print '</div>';

					// End of page
					llxFooter();
					$db->close();

					exit;
			}
		}

		$head = expensereport_prepare_head($object);

		if ($action == 'edit' && ($object->status < 3 || $object->status == 99)) {
			print "<form name='update' action=\"".$_SERVER['PHP_SELF']."\" method=\"post\">\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="id" value="'.$id.'">';

			print dol_get_fiche_head($head, 'card', $langs->trans("ExpenseReport"), 0, 'trip');

			if ($object->status == 99) {
				print '<input type="hidden" name="action" value="updateFromRefuse">';
			} else {
				print '<input type="hidden" name="action" value="update">';
			}

			$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

			print '<table class="border" style="width:100%;">';

			print '<tr>';
			print '<td>'.$langs->trans("User").'</td>';
			print '<td>';
			$userfee = new User($db);
			if ($object->fk_user_author > 0) {
				$userfee->fetch($object->fk_user_author);
				print $userfee->getNomUrl(-1);
			}
			print '</td></tr>';

			// Ref
			print '<tr><td class="titlefieldcreate">'.$langs->trans("Ref").'</td><td>';
			print $form->showrefnav($object, 'ref', $linkback, 1, 'ref', 'ref', '');
			print '</td></tr>';

			print '<tr>';
			print '<td>'.$langs->trans("DateStart").'</td>';
			print '<td>';
			print $form->selectDate($object->date_debut, 'date_debut');
			print '</td>';
			print '</tr>';
			print '<tr>';
			print '<td>'.$langs->trans("DateEnd").'</td>';
			print '<td>';
			print $form->selectDate($object->date_fin, 'date_fin');
			print '</td>';
			print '</tr>';

			if (!empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION)) {
				print '<tr>';
				print '<td>'.$langs->trans("ModePaiement").'</td>';
				print '<td>';
				$form->select_types_paiements($object->fk_c_paiement, 'fk_c_paiement');
				print '</td>';
				print '</tr>';
			}

			if ($object->status < 3) {
				print '<tr>';
				print '<td>'.$langs->trans("VALIDATOR").'</td>'; // Approbator
				print '<td>';
				$include_users = $object->fetch_users_approver_expensereport();
				$s = $form->select_dolusers($object->fk_user_validator, "fk_user_validator", 1, "", 0, $include_users);
				print $form->textwithpicto($s, $langs->trans("AnyOtherInThisListCanValidate"));
				print '</td>';
				print '</tr>';
			} else {
				print '<tr>';
				print '<td>'.$langs->trans("VALIDOR").'</td>';
				print '<td>';
				$userfee = new User($db);
				$userfee->fetch($object->fk_user_valid);
				print $userfee->getNomUrl(-1);
				print '</td></tr>';
			}

			if ($object->status == 6) {
				print '<tr>';
				print '<td>'.$langs->trans("AUTHORPAIEMENT").'</td>';
				print '<td>';
				$userfee = new User($db);
				$userfee->fetch($user->id);
				print $userfee->getNomUrl(-1);
				print '</td></tr>';
			}

			// Other attributes
			//$cols = 3;
			//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_edit.tpl.php';

			print '</table>';

			print dol_get_fiche_end();

			print $form->buttonsSaveCancel("Modify");

			print '</form>';
		} else {
			$taxlessUnitPriceDisabled = ! empty($conf->global->EXPENSEREPORT_FORCE_LINE_AMOUNTS_INCLUDING_TAXES_ONLY) ? ' disabled' : '';

			print dol_get_fiche_head($head, 'card', $langs->trans("ExpenseReport"), -1, 'trip');

			// Clone confirmation
			if ($action == 'clone') {
				// Create an array for form
				$criteriaforfilter = 'hierarchyme';
				if (!empty($user->rights->expensereport->readall)) {
					$criteriaforfilter = '';
				}
				$formquestion = array(
					'text' => '',
					array('type' => 'other', 'name' => 'fk_user_author', 'label' => $langs->trans("SelectTargetUser"), 'value' => $form->select_dolusers((GETPOST('fk_user_author', 'int') > 0 ? GETPOST('fk_user_author', 'int') : $user->id), 'fk_user_author', 0, null, 0, $criteriaforfilter, '', '0', 0, 0, '', 0, '', 'maxwidth150'))
				);
				// Paiement incomplet. On demande si motif = escompte ou autre
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneExpenseReport', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
			}

			if ($action == 'save') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("SaveTrip"), $langs->trans("ConfirmSaveTrip"), "confirm_validate", "", "", 1);
			}

			if ($action == 'save_from_refuse') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("SaveTrip"), $langs->trans("ConfirmSaveTrip"), "confirm_save_from_refuse", "", "", 1);
			}

			if ($action == 'delete') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("DeleteTrip"), $langs->trans("ConfirmDeleteTrip"), "confirm_delete", "", "", 1);
			}

			if ($action == 'validate') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("ValideTrip"), $langs->trans("ConfirmValideTrip"), "confirm_approve", "", "", 1);
			}

			if ($action == 'paid') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("PaidTrip"), $langs->trans("ConfirmPaidTrip"), "confirm_paid", "", "", 1);
			}

			if ($action == 'cancel') {
				$array_input = array('text'=>$langs->trans("ConfirmCancelTrip"), array('type'=>"text", 'label'=>'<strong>'.$langs->trans("Comment").'</strong>', 'name'=>"detail_cancel", 'value'=>""));
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("Cancel"), "", "confirm_cancel", $array_input, "", 1);
			}

			if ($action == 'setdraft') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("BrouillonnerTrip"), $langs->trans("ConfirmBrouillonnerTrip"), "confirm_setdraft", "", "", 1);
			}

			if ($action == 'refuse') {		// Deny
				$array_input = array('text'=>$langs->trans("ConfirmRefuseTrip"), array('type'=>"text", 'label'=>$langs->trans("Comment"), 'name'=>"detail_refuse", 'value'=>""));
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id, $langs->trans("Deny"), '', "confirm_refuse", $array_input, "yes", 1);
			}

			if ($action == 'delete_line') {
				$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$id."&rowid=".GETPOST('rowid', 'int'), $langs->trans("DeleteLine"), $langs->trans("ConfirmDeleteLine"), "confirm_delete_line", '', 'yes', 1);
			}

			// Print form confirm
			print $formconfirm;

			// Expense report card
			$linkback = '<a href="'.DOL_URL_ROOT.'/expensereport/list.php?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
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
			 $morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			 if ($action == 'classify') {
			 //$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			 $morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			 $morehtmlref.='<input type="hidden" name="action" value="classin">';
			 $morehtmlref.='<input type="hidden" name="token" value="'.newToken().'">';
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
			$morehtmlref .= '</div>';

			dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Author
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("User").'</td>';
			print '<td>';
			if ($object->fk_user_author > 0) {
				$userauthor = new User($db);
				$result = $userauthor->fetch($object->fk_user_author);
				if ($result < 0) {
					dol_print_error('', $userauthor->error);
				} elseif ($result > 0) {
					print $userauthor->getNomUrl(-1);
				}
			}
			print '</td></tr>';

			// Period
			print '<tr>';
			print '<td class="titlefield">'.$langs->trans("Period").'</td>';
			print '<td>';
			print get_date_range($object->date_debut, $object->date_fin, 'day', $langs, 0);
			print '</td>';
			print '</tr>';
			if (!empty($conf->global->EXPENSEREPORT_ASK_PAYMENTMODE_ON_CREATION)) {
				print '<tr>';
				print '<td>'.$langs->trans("ModePaiement").'</td>';
				print '<td>'.$object->fk_c_paiement.'</td>';
				print '</tr>';
			}

			// Validation date
			print '<tr>';
			print '<td>'.$langs->trans("DATE_SAVE").'</td>';
			print '<td>'.dol_print_date($object->date_valid, 'dayhour', 'tzuser');
			if ($object->status == 2 && $object->hasDelay('toapprove')) {
				print ' '.img_warning($langs->trans("Late").' - '.$langs->trans("ToApprove"));
			}
			if ($object->status == 5 && $object->hasDelay('topay')) {
				print ' '.img_warning($langs->trans("Late").' - '.$langs->trans("ToPay"));
			}
			print '</td></tr>';
			print '</tr>';

			// User to inform for approval
			if ($object->status <= ExpenseReport::STATUS_VALIDATED) {	// informed
				print '<tr>';
				print '<td>'.$langs->trans("VALIDATOR").'</td>'; // approver
				print '<td>';
				if ($object->fk_user_validator > 0) {
					$userfee = new User($db);
					$result = $userfee->fetch($object->fk_user_validator);
					if ($result > 0) {
						print $userfee->getNomUrl(-1);
					}
					if (empty($userfee->email) || !isValidEmail($userfee->email)) {
						$langs->load("errors");
						print img_warning($langs->trans("ErrorBadEMail", $userfee->email));
					}
				}
				print '</td></tr>';
			} elseif ($object->status == ExpenseReport::STATUS_CANCELED) {
				print '<tr>';
				print '<td>'.$langs->trans("CANCEL_USER").'</span></td>';
				print '<td>';
				if ($object->fk_user_cancel > 0) {
					$userfee = new User($db);
					$result = $userfee->fetch($object->fk_user_cancel);
					if ($result > 0) {
						print $userfee->getNomUrl(-1);
					}
				}
				print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("MOTIF_CANCEL").'</td>';
				print '<td>'.$object->detail_cancel.'</td></tr>';
				print '</tr>';
				print '<tr>';
				print '<td>'.$langs->trans("DATE_CANCEL").'</td>';
				print '<td>'.dol_print_date($object->date_cancel, 'dayhour', 'tzuser').'</td></tr>';
				print '</tr>';
			} else {
				print '<tr>';
				print '<td>'.$langs->trans("ApprovedBy").'</td>';
				print '<td>';
				if ($object->fk_user_approve > 0) {
					$userapp = new User($db);
					$result = $userapp->fetch($object->fk_user_approve);
					if ($result > 0) {
						print $userapp->getNomUrl(-1);
					}
				}
				print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("DateApprove").'</td>';
				print '<td>'.dol_print_date($object->date_approve, 'dayhour', 'tzuser').'</td></tr>';
				print '</tr>';
			}

			if ($object->status == 99 || !empty($object->detail_refuse)) {
				print '<tr>';
				print '<td>'.$langs->trans("REFUSEUR").'</td>';
				print '<td>';
				$userfee = new User($db);
				$result = $userfee->fetch($object->fk_user_refuse);
				if ($result > 0) {
					print $userfee->getNomUrl(-1);
				}
				print '</td></tr>';

				print '<tr>';
				print '<td>'.$langs->trans("DATE_REFUS").'</td>';
				print '<td>'.dol_print_date($object->date_refuse, 'dayhour', 'tzuser');
				if ($object->detail_refuse) {
					print ' - '.$object->detail_refuse;
				}
				print '</td>';
				print '</tr>';
			}

			if ($object->status == $object::STATUS_CLOSED) {
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
			$cols = 2;
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Amount
			print '<tr>';
			print '<td class="titlefieldmiddle">'.$langs->trans("AmountHT").'</td>';
			print '<td class="nowrap amountcard">'.price($object->total_ht, 1, '', 1, - 1, - 1, $conf->currency).'</td>';
			$rowspan = 5;
			if ($object->status <= ExpenseReport::STATUS_VALIDATED) {
				$rowspan++;
			} elseif ($object->status == ExpenseReport::STATUS_CANCELED) {
				$rowspan += 2;
			} else {
				$rowspan += 2;
			}
			if ($object->status == ExpenseReport::STATUS_REFUSED || !empty($object->detail_refuse)) {
				$rowspan += 2;
			}
			if ($object->status == ExpenseReport::STATUS_CLOSED) {
				$rowspan += 2;
			}
			print "</td>";
			print '</tr>';

			print '<tr>';
			print '<td>'.$langs->trans("AmountVAT").'</td>';
			print '<td class="nowrap amountcard">'.price($object->total_tva, 1, '', 1, -1, -1, $conf->currency).'</td>';
			print '</tr>';

			// Amount Local Taxes
			if ($mysoc->localtax1_assuj == "1" || $object->total_localtax1 != 0) { 		// Localtax1
				print '<tr><td>'.$langs->transcountry("AmountLT1", $mysoc->country_code).'</td>';
				print '<td class="valuefield">'.price($object->total_localtax1, 1, '', 1, -1, -1, $conf->currency).'</td></tr>';
			}
			if ($mysoc->localtax2_assuj == "1" || $object->total_localtax2 != 0) { 		// Localtax2 IRPF
				print '<tr><td>'.$langs->transcountry("AmountLT2", $mysoc->country_code).'</td>';
				print '<td class="valuefield">'.price($object->total_localtax2, 1, '', 1, -1, -1, $conf->currency).'</td></tr>';
			}

			print '<tr>';
			print '<td>'.$langs->trans("AmountTTC").'</td>';
			print '<td class="nowrap amountcard">'.price($object->total_ttc, 1, '', 1, -1, -1, $conf->currency).'</td>';
			print '</tr>';

			// List of payments already done
			$nbcols = 3;
			if (!empty($conf->banque->enabled)) {
				$nbrows++;
				$nbcols++;
			}

			print '<table class="noborder paymenttable centpercent">';

			print '<tr class="liste_titre">';
			print '<td class="liste_titre">'.$langs->trans('Payments').'</td>';
			print '<td class="liste_titre">'.$langs->trans('Date').'</td>';
			print '<td class="liste_titre">'.$langs->trans('Type').'</td>';
			if (!empty($conf->banque->enabled)) {
				print '<td class="liste_titre right">'.$langs->trans('BankAccount').'</td>';
			}
			print '<td class="liste_titre right">'.$langs->trans('Amount').'</td>';
			print '<td class="liste_titre" width="18">&nbsp;</td>';
			print '</tr>';

			// Payments already done (from payment on this expensereport)
			$sql = "SELECT p.rowid, p.num_payment, p.datep as dp, p.amount, p.fk_bank,";
			$sql .= "c.code as p_code, c.libelle as payment_type,";
			$sql .= "ba.rowid as baid, ba.ref as baref, ba.label, ba.number as banumber, ba.account_number, ba.fk_accountancy_journal";
			$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e, ".MAIN_DB_PREFIX."payment_expensereport as p";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_typepayment = c.id";
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank as b ON p.fk_bank = b.rowid';
			$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'bank_account as ba ON b.fk_account = ba.rowid';
			$sql .= " WHERE e.rowid = ".((int) $id);
			$sql .= " AND p.fk_expensereport = e.rowid";
			$sql .= ' AND e.entity IN ('.getEntity('expensereport').')';
			$sql .= " ORDER BY dp";

			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);
				$i = 0; $totalpaid = 0;
				while ($i < $num) {
					$objp = $db->fetch_object($resql);

					$paymentexpensereportstatic->id = $objp->rowid;
					$paymentexpensereportstatic->datepaye = $db->jdate($objp->dp);
					$paymentexpensereportstatic->ref = $objp->rowid;
					$paymentexpensereportstatic->num_payment = $objp->num_payment;
					$paymentexpensereportstatic->payment_code = $objp->payment_code;

					print '<tr class="oddseven">';
					print '<td>';
					print $paymentexpensereportstatic->getNomUrl(1);
					print '</td>';
					print '<td>'.dol_print_date($db->jdate($objp->dp), 'day')."</td>\n";
					$labeltype = $langs->trans("PaymentType".$objp->p_code) != ("PaymentType".$objp->p_code) ? $langs->trans("PaymentType".$objp->p_code) : $objp->payment_type;
					print "<td>".$labeltype.' '.$objp->num_payment."</td>\n";
					if (!empty($conf->banque->enabled)) {
						$bankaccountstatic->id = $objp->baid;
						$bankaccountstatic->ref = $objp->baref;
						$bankaccountstatic->label = $objp->baref;
						$bankaccountstatic->number = $objp->banumber;

						if (!empty($conf->accounting->enabled)) {
							$bankaccountstatic->account_number = $objp->account_number;

							$accountingjournal = new AccountingJournal($db);
							$accountingjournal->fetch($objp->fk_accountancy_journal);
							$bankaccountstatic->accountancy_journal = $accountingjournal->getNomUrl(0, 1, 1, '', 1);
						}

						print '<td class="right">';
						if ($bankaccountstatic->id) {
							print $bankaccountstatic->getNomUrl(1, 'transactions');
						}
						print '</td>';
					}
					print '<td class="right">'.price($objp->amount)."</td>";
					print '<td></td>';
					print "</tr>";
					$totalpaid += $objp->amount;
					$i++;
				}
				if (!is_null($totalpaid)) {
					$totalpaid = price2num($totalpaid); // Round $totalpaid to fix floating problem after addition into loop
				}

				$remaintopay = price2num($object->total_ttc - $totalpaid);
				$resteapayeraffiche = $remaintopay;

				$cssforamountpaymentcomplete = 'amountpaymentcomplete';

				if ($object->status == ExpenseReport::STATUS_REFUSED) {
					$cssforamountpaymentcomplete = 'amountpaymentneutral';
					$resteapayeraffiche = 0;
				} elseif ($object->paid == 0) {
					$cssforamountpaymentcomplete = 'amountpaymentneutral';
				}
				print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AlreadyPaid").':</td><td class="right">'.price($totalpaid).'</td><td></td></tr>';
				print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("AmountExpected").':</td><td class="right">'.price($object->total_ttc).'</td><td></td></tr>';

				print '<tr><td colspan="'.$nbcols.'" class="right">'.$langs->trans("RemainderToPay").':</td>';
				print '<td class="right'.($resteapayeraffiche ? ' amountremaintopay' : (' '.$cssforamountpaymentcomplete)).'">'.price($resteapayeraffiche).'</td><td></td></tr>';

				$db->free($resql);
			} else {
				dol_print_error($db);
			}
			print "</table>";

			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div><br>';

			print '<div style="clear: both;"></div>';

			$actiontouse = 'updateline';
			if (($object->status == 0 || $object->status == 99) && $action != 'editline') {
				$actiontouse = 'addline';
			}

			print '<form name="expensereport" action="'.$_SERVER["PHP_SELF"].'" enctype="multipart/form-data" method="post" >';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="'.$actiontouse.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';
			print '<input type="hidden" name="fk_expensereport" value="'.$object->id.'" />';

			print '<div class="div-table-responsive-no-min">';
			print '<table id="tablelines" class="noborder centpercent">';

			if (!empty($object->lines)) {
				$i = 0; $total = 0;

				print '<tr class="liste_titre">';
				print '<td class="center">'.$langs->trans('LineNb').'</td>';
				//print '<td class="center">'.$langs->trans('Piece').'</td>';
				print '<td class="center">'.$langs->trans('Date').'</td>';
				if (!empty($conf->projet->enabled)) {
					print '<td class="minwidth100imp">'.$langs->trans('Project').'</td>';
				}
				print '<td class="center">'.$langs->trans('Type').'</td>';
				if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
					print '<td>'.$langs->trans('CarCategory').'</td>';
				}
				print '<td>'.$langs->trans('Description').'</td>';
				print '<td class="right">'.$langs->trans('VAT').'</td>';
				print '<td class="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td class="right">'.$langs->trans('PriceUTTC').'</td>';
				print '<td class="right">'.$langs->trans('Qty').'</td>';
				if ($action != 'editline') {
					print '<td class="right">'.$langs->trans('AmountHT').'</td>';
					print '<td class="right">'.$langs->trans('AmountTTC').'</td>';
				}
				// Picture
				print '<td>';
				print '</td>';

				// Information if theres a rule restriction
				print '<td>';
				print '</td>';

				// Ajout des boutons de modification/suppression
				if (($object->status < 2 || $object->status == 99) && $user->rights->expensereport->creer) {
					print '<td class="right"></td>';
				}
				print '</tr>';

				foreach ($object->lines as &$line) {
					$numline = $i + 1;

					if ($action != 'editline' || $line->rowid != GETPOST('rowid', 'int')) {
						print '<tr class="oddeven">';

						// Num
						print '<td class="center">';
						print $numline;
						print '</td>';

						// Date
						print '<td class="center">'.dol_print_date($db->jdate($line->date), 'day').'</td>';

						// Project
						if (!empty($conf->projet->enabled)) {
							print '<td>';
							if ($line->fk_project > 0) {
								$projecttmp->id = $line->fk_project;
								$projecttmp->ref = $line->projet_ref;
								$projecttmp->title = $line->projet_title;
								print $projecttmp->getNomUrl(1);
							}
							print '</td>';
						}

						$titlealt = '';
						if (!empty($conf->accounting->enabled)) {
							require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
							$accountingaccount = new AccountingAccount($db);
							$resaccountingaccount = $accountingaccount->fetch(0, $line->type_fees_accountancy_code, 1);
							//$titlealt .= '<span class="opacitymedium">';
							$titlealt .= $langs->trans("AccountancyCode").': ';
							if ($resaccountingaccount > 0) {
								$titlealt .= $accountingaccount->account_number;
							} else {
								$titlealt .= $langs->trans("NotFound");
							}
							//$titlealt .= '</span>';
						}

						// Type of fee
						print '<td class="center" title="'.dol_escape_htmltag($titlealt).'">';
						$labeltype = ($langs->trans(($line->type_fees_code)) == $line->type_fees_code ? $line->type_fees_libelle : $langs->trans($line->type_fees_code));
						print $labeltype;
						print '</td>';

						// IK
						if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
							print '<td class="fk_c_exp_tax_cat">';
							$exp_tax_cat_label = dol_getIdFromCode($db, $line->fk_c_exp_tax_cat, 'c_exp_tax_cat', 'rowid', 'label');
							print $langs->trans($exp_tax_cat_label);
							print '</td>';
						}

						// Comment
						print '<td class="left">'.dol_nl2br($line->comments).'</td>';

						// VAT rate
						print '<td class="right">'.vatrate($line->vatrate.($line->vat_src_code ? ' ('.$line->vat_src_code.')' : ''), true).'</td>';

						// Unit price HT
						print '<td class="right">';
						if (!empty($line->value_unit_ht)) {
							print price($line->value_unit_ht);
						} else {
							$tmpvat = price2num(preg_replace('/\s*\(.*\)/', '', $line->vatrate));
							$pricenettoshow = price2num($line->value_unit / (1 + $tmpvat / 100), 'MU');
							print price($pricenettoshow);
						}
						print '</td>';

						print '<td class="right">'.price($line->value_unit).'</td>';

						print '<td class="right">'.dol_escape_htmltag($line->qty).'</td>';

						if ($action != 'editline') {
							print '<td class="right">'.price($line->total_ht).'</td>';
							print '<td class="right">'.price($line->total_ttc).'</td>';
						}

						// Column with preview
						print '<td class="center">';
						if ($line->fk_ecm_files > 0) {
							$modulepart = 'expensereport';
							$maxheightmini = 32;

							$result = $ecmfilesstatic->fetch($line->fk_ecm_files);
							if ($result > 0) {
								$relativepath = preg_replace('/expensereport\//', '', $ecmfilesstatic->filepath);
								$fileinfo = pathinfo($ecmfilesstatic->filepath.'/'.$ecmfilesstatic->filename);
								if (image_format_supported($fileinfo['basename']) > 0) {
									$minifile = getImageFileNameForSize($fileinfo['basename'], '_mini'); // For new thumbs using same ext (in lower case howerver) than original
									if (!dol_is_file($conf->expensereport->dir_output.'/'.$relativepath.'/'.$minifile)) {
										$minifile = getImageFileNameForSize($fileinfo['basename'], '_mini', '.png'); // For backward compatibility of old thumbs that were created with filename in lower case and with .png extension
									}
									//print $file['path'].'/'.$minifile.'<br>';
									$urlforhref = getAdvancedPreviewUrl($modulepart, $relativepath.'/'.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));
									if (empty($urlforhref)) {
										$urlforhref = DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity) ? $object->entity : $conf->entity).'&file='.urlencode($relativepath.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']));
										print '<a href="'.$urlforhref.'" class="aphoto" target="_blank" rel="noopener noreferrer">';
									} else {
										print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
									}
									print '<img class="photo" height="'.$maxheightmini.'" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart='.$modulepart.'&entity='.(!empty($object->entity) ? $object->entity : $conf->entity).'&file='.urlencode($relativepath.'/'.$minifile).'" title="">';
									print '</a>';
								} else {
									$modulepart = 'expensereport';
									$thumbshown = 0;
									if (preg_match('/\.pdf$/i', $ecmfilesstatic->filename)) {
										$filepdf = $conf->expensereport->dir_output.'/'.$relativepath.'/'.$ecmfilesstatic->filename;
										$fileimage = $conf->expensereport->dir_output.'/'.$relativepath.'/'.$ecmfilesstatic->filename.'_preview.png';
										$relativepathimage = $relativepath.'/'.$ecmfilesstatic->filename.'_preview.png';

										$pdfexists = file_exists($filepdf);
										if ($pdfexists) {
											// Conversion du PDF en image png si fichier png non existant
											if (!file_exists($fileimage) || (filemtime($fileimage) < filemtime($filepdf))) {
												if (empty($conf->global->MAIN_DISABLE_PDF_THUMBS)) {		// If you experience trouble with pdf thumb generation and imagick, you can disable here.
													include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
													$ret = dol_convert_file($filepdf, 'png', $fileimage, '0'); // Convert first page of PDF into a file _preview.png
													if ($ret < 0) {
														$error++;
													}
												}
											}
										}

										if ($pdfexists && !$error) {
											$heightforphotref = 70;
											if (!empty($conf->dol_optimize_smallscreen)) {
												$heightforphotref = 60;
											}
											// If the preview file is found
											if (file_exists($fileimage)) {
												$thumbshown = 1;
												$urlforhref = getAdvancedPreviewUrl($modulepart, $relativepath.'/'.$fileinfo['filename'].'.'.strtolower($fileinfo['extension']), 1, '&entity='.(!empty($object->entity) ? $object->entity : $conf->entity));
												print '<a href="'.$urlforhref['url'].'" class="'.$urlforhref['css'].'" target="'.$urlforhref['target'].'" mime="'.$urlforhref['mime'].'">';
												print '<img height="'.$heightforphotref.'" class="photo photowithmargin photowithborder" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=apercu'.$modulepart.'&amp;file='.urlencode($relativepathimage).'">';
												print '</a>';
											}
										}
									}

									if (!$thumbshown) {
										print img_mime($ecmfilesstatic->filename);
									}
								}
							}
						}
						print '</td>';

						print '<td class="nowrap right">';
						print !empty($line->rule_warning_message) ? img_warning(html_entity_decode($line->rule_warning_message)) : '&nbsp;';
						print '</td>';

						// Ajout des boutons de modification/suppression
						if (($object->status < ExpenseReport::STATUS_VALIDATED || $object->status == ExpenseReport::STATUS_REFUSED) && $user->rights->expensereport->creer) {
							print '<td class="nowrap right">';

							print '<a class="editfielda reposition paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=editline&token='.newToken().'&rowid='.$line->rowid.'">';
							print img_edit();
							print '</a> &nbsp; ';
							print '<a class="paddingrightonly" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete_line&token='.newToken().'&rowid='.$line->rowid.'">';
							print img_delete();
							print '</a>';

							print '</td>';
						}

						print '</tr>';
					}

					if ($action == 'editline' && $line->rowid == GETPOST('rowid', 'int')) {
						// Add line with link to add new file or attach line to an existing file
						$colspan = 11;
						if (!empty($conf->projet->enabled)) {
							$colspan++;
						}
						if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
							$colspan++;
						}

						print '<!-- line of expense report -->'."\n";
						print '<tr class="tredited">';

						print '<td class="center">';
						print $numline;
						print '</td>';

						print '<td colspan="'.($colspan - 1).'" class="liste_titre">';
						print '<a href="" class="commonlink auploadnewfilenow reposition">'.$langs->trans("UploadANewFileNow");
						print img_picto($langs->trans("UploadANewFileNow"), 'chevron-down', '', false, 0, 0, '', 'marginleftonly');
						print '</a>';
						if (empty($conf->global->EXPENSEREPORT_DISABLE_ATTACHMENT_ON_LINES)) {
							print ' &nbsp; - &nbsp; <a href="" class="commonlink aattachtodoc reposition">'.$langs->trans("AttachTheNewLineToTheDocument");
							print img_picto($langs->trans("AttachTheNewLineToTheDocument"), 'chevron-down', '', false, 0, 0, '', 'marginleftonly');
							print '</a>';
						}

						print '<!-- Code to open/close section to submit or link files in edit mode -->'."\n";
						print '<script type="text/javascript">'."\n";
						print '$(document).ready(function() {
        				        $( ".auploadnewfilenow" ).click(function() {
        				            jQuery(".truploadnewfilenow").toggle();
                                    jQuery(".trattachnewfilenow").hide();
                                    return false;
                                });
        				        $( ".aattachtodoc" ).click(function() {
        				            jQuery(".trattachnewfilenow").toggle();
                                    jQuery(".truploadnewfilenow").hide();
                                    return false;
                                });';
						if (is_array(GETPOST('attachfile', 'array')) && count(GETPOST('attachfile', 'array'))) {
							print 'jQuery(".trattachnewfilenow").toggle();'."\n";
						}
						print '
                        		jQuery("form[name=\"expensereport\"]").submit(function() {
                            		if (jQuery(".truploadnewfilenow").is(":hidden")) {
                                		jQuery("input[name=\"sendit\"]").val("");
                            		}
                        		});
                    		';
						print '
                            });
        				    ';
						print '</script>'."\n";
						print '</td></tr>';

						$filenamelinked = '';
						if ($line->fk_ecm_files > 0) {
							$result = $ecmfilesstatic->fetch($line->fk_ecm_files);
							if ($result > 0) {
								$filenamelinked = $ecmfilesstatic->filename;
							}
						}

						$tredited = 'tredited';
						include DOL_DOCUMENT_ROOT.'/expensereport/tpl/expensereport_addfile.tpl.php';
						include DOL_DOCUMENT_ROOT.'/expensereport/tpl/expensereport_linktofile.tpl.php';

						print '<tr class="oddeven tredited">';

						print '<td></td>';

						// Select date
						print '<td class="center">';
						print $form->selectDate($line->date, 'date');
						print '</td>';

						// Select project
						if (!empty($conf->projet->enabled)) {
							print '<td>';
							$formproject->select_projects(-1, $line->fk_project, 'fk_project', 0, 0, $projectRequired ? 0 : 1, 1, 0, 0, 0, '', 0, 0, 'maxwidth300');
							print '</td>';
						}

						// Select type
						print '<td class="center">';
						print $formexpensereport->selectTypeExpenseReport($line->fk_c_type_fees, 'fk_c_type_fees');
						print '</td>';

						if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
							print '<td class="fk_c_exp_tax_cat">';
							$params = array('fk_expense' => $object->id, 'fk_expense_det' => $line->rowid, 'date' => $line->dates);
							print $form->selectExpenseCategories($line->fk_c_exp_tax_cat, 'fk_c_exp_tax_cat', 1, array(), 'fk_c_type_fees', $userauthor->default_c_exp_tax_cat, $params);
							print '</td>';
						}

						// Add comments
						print '<td>';
						print '<textarea name="comments" class="flat_ndf centpercent">'.dol_escape_htmltag($line->comments, 0, 1).'</textarea>';
						print '</td>';

						// VAT
						$selectedvat = price2num($line->vatrate).($line->vat_src_code ? ' ('.$line->vat_src_code.')' : '');
						print '<td class="right">';
						print $form->load_tva('vatrate', (GETPOSTISSET("vatrate") ? GETPOST("vatrate") : $selectedvat), $mysoc, '', 0, 0, '', false, 1);
						print '</td>';

						// Unit price
						print '<td class="right">';
						print '<input type="text" min="0" class="right maxwidth50" id="value_unit_ht" name="value_unit_ht" value="'.dol_escape_htmltag(price2num($line->value_unit_ht)).'"'.$taxlessUnitPriceDisabled.' />';
						print '</td>';

						// Unit price with tax
						print '<td class="right">';
						print '<input type="text" min="0" class="right maxwidth50" id="value_unit" name="value_unit" value="'.dol_escape_htmltag(price2num($line->value_unit)).'" />';
						print '</td>';

						// Quantity
						print '<td class="right">';
						print '<input type="text" min="0" class="right maxwidth50" name="qty" value="'.dol_escape_htmltag($line->qty).'" />';  // We must be able to enter decimal qty
						print '</td>';

						//print '<td class="right">'.$langs->trans('AmountHT').'</td>';
						//print '<td class="right">'.$langs->trans('AmountTTC').'</td>';

						// Picture
						print '<td class="center">';
						//print $line->fk_ecm_files;
						print '</td>';
						// Information if theres a rule restriction
						print '<td class="center">';
						print '</td>';

						print '<td>';
						print '<input type="hidden" name="rowid" value="'.$line->rowid.'">';
						print $form->buttonsSaveCancel('Save', 'Cancel', array(), 0, 'small');
						print '</td>';

						print '</tr>';
					}

					$i++;
				}
			}

			 // Add a new line
			if (($object->status == ExpenseReport::STATUS_DRAFT || $object->status == ExpenseReport::STATUS_REFUSED) && $action != 'editline' && $user->rights->expensereport->creer) {
				$colspan = 12;
				if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
					$colspan++;
				}
				if (!empty($conf->projet->enabled)) {
					$colspan++;
				}
				if ($action != 'editline') {
					$colspan++;
				}

				$nbFiles = $nbLinks = 0;
				$arrayoffiles = array();
				if (empty($conf->global->EXPENSEREPORT_DISABLE_ATTACHMENT_ON_LINES)) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
					require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
					require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
					$upload_dir = $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref);
					$arrayoffiles = dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png|'.preg_quote(dol_sanitizeFileName($object->ref.'.pdf'), '/').')$');
					$nbFiles = count($arrayoffiles);
					$nbLinks = Link::count($db, $object->element, $object->id);
				}

				// Add line with link to add new file or attach to an existing file
				print '<tr class="liste_titre">';
				print '<td colspan="'.$colspan.'" class="liste_titre">';
				print '<a href="" class="commonlink auploadnewfilenow reposition">'.$langs->trans("UploadANewFileNow");
				print img_picto($langs->trans("UploadANewFileNow"), 'chevron-down', '', false, 0, 0, '', 'marginleftonly');
				print '</a>';
				if (empty($conf->global->EXPENSEREPORT_DISABLE_ATTACHMENT_ON_LINES)) {
					print ' &nbsp; - &nbsp; <a href="" class="commonlink aattachtodoc reposition">'.$langs->trans("AttachTheNewLineToTheDocument");
					print img_picto($langs->trans("AttachTheNewLineToTheDocument"), 'chevron-down', '', false, 0, 0, '', 'marginleftonly');
					print '</a>';
				}

				print '<!-- Code to open/close section to submit or link files in the form to add new line -->'."\n";
				print '<script type="text/javascript">'."\n";
				print '$(document).ready(function() {
				        $( ".auploadnewfilenow" ).click(function() {
							console.log("We click on toggle of auploadnewfilenow");
				            jQuery(".truploadnewfilenow").toggle();
                            jQuery(".trattachnewfilenow").hide();
							if (jQuery(".truploadnewfilenow").is(":hidden")) {
                            	jQuery("input[name=\"sendit\"]").prop("name", "senditdisabled");
                            } else {
                                jQuery("input[name=\"senditdisabled\"]").prop("name", "sendit");
                            }
                            return false;
                        });
				        $( ".aattachtodoc" ).click(function() {
							console.log("We click on toggle of aattachtodoc");
				            jQuery(".trattachnewfilenow").toggle();
                            jQuery(".truploadnewfilenow").hide();
                            return false;
                        });'."\n";
				if (is_array(GETPOST('attachfile', 'array')) && count(GETPOST('attachfile', 'array')) && $action != 'updateline') {
					print 'jQuery(".trattachnewfilenow").show();'."\n";
				}
				print '
						jQuery("form[name=\"expensereport\"]").submit(function() {
							if (jQuery(".truploadnewfilenow").is(":hidden")) {
								/* When section to send file is not expanded, we disable the button sendit that submit form to add a new file, so button to submit line will work. */
								jQuery("input[name=\"sendit\"]").val("");
								jQuery("input[name=\"sendit\"]").prop("name", "senditdisabled");
							} else {
								jQuery("input[name=\"senditdisabled\"]").prop("name", "sendit");
							}
						});
					';
				print '
                    });
				    ';
				print '</script>'."\n";
				print '</td></tr>';

				include DOL_DOCUMENT_ROOT.'/expensereport/tpl/expensereport_linktofile.tpl.php';
				include DOL_DOCUMENT_ROOT.'/expensereport/tpl/expensereport_addfile.tpl.php';

				print '<tr class="liste_titre">';
				print '<td></td>';
				print '<td class="center">'.$langs->trans('Date').'</td>';
				if (!empty($conf->projet->enabled)) {
					print '<td class="minwidth100imp">'.$form->textwithpicto($langs->trans('Project'), $langs->trans("ClosedProjectsAreHidden")).'</td>';
				}
				print '<td class="center">'.$langs->trans('Type').'</td>';
				if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
					print '<td>'.$langs->trans('CarCategory').'</td>';
				}
				print '<td>'.$langs->trans('Description').'</td>';
				print '<td class="right">'.$langs->trans('VAT').'</td>';
				print '<td class="right">'.$langs->trans('PriceUHT').'</td>';
				print '<td class="right">'.$langs->trans('PriceUTTC').'</td>';
				print '<td class="right">'.$langs->trans('Qty').'</td>';
				print '<td></td>';
				print '<td></td>';
				print '<td></td>';
				print '<td></td>';
				print '<td></td>';
				print '</tr>';

				print '<tr class="oddeven nohover">';

				// Line number
				print '<td></td>';

				// Select date
				print '<td class="center">';
				print $form->selectDate($date ? $date : -1, 'date', 0, 0, 0, '', 1, 1);
				print '</td>';

				// Select project
				if (!empty($conf->projet->enabled)) {
					print '<td>';
					$formproject->select_projects(-1, $fk_project, 'fk_project', 0, 0, $projectRequired ? 0 : 1, -1, 0, 0, 0, '', 0, 0, 'maxwidth300');
					print '</td>';
				}

					// Select type
					print '<td class="center">';
					print $formexpensereport->selectTypeExpenseReport($fk_c_type_fees, 'fk_c_type_fees', 1);
					print '</td>';

				if (!empty($conf->global->MAIN_USE_EXPENSE_IK)) {
					print '<td class="fk_c_exp_tax_cat">';
					$params = array('fk_expense' => $object->id);
					print $form->selectExpenseCategories('', 'fk_c_exp_tax_cat', 1, array(), 'fk_c_type_fees', $userauthor->default_c_exp_tax_cat, $params, 0);
					print '</td>';
				}

				// Add comments
				print '<td>';
				print '<textarea class="flat_ndf centpercent" name="comments" rows="'.ROWS_2.'">'.dol_escape_htmltag($comments, 0, 1).'</textarea>';
				print '</td>';

				// Select VAT
				print '<td class="right">';
				$defaultvat = -1;
				if (!empty($conf->global->EXPENSEREPORT_NO_DEFAULT_VAT)) {
					$conf->global->MAIN_VAT_DEFAULT_IF_AUTODETECT_FAILS = 'none';
				}
				print $form->load_tva('vatrate', ($vatrate != '' ? $vatrate : $defaultvat), $mysoc, '', 0, 0, '', false, 1);
				print '</td>';

				// Unit price net
				print '<td class="right">';
				print '<input type="text" class="right maxwidth50" id="value_unit_ht" name="value_unit_ht" value="'.dol_escape_htmltag($value_unit_ht).'"'.$taxlessUnitPriceDisabled.' />';
				print '</td>';

				// Unit price with tax
				print '<td class="right">';
				print '<input type="text" class="right maxwidth50" id="value_unit" name="value_unit" value="'.dol_escape_htmltag($value_unit).'">';
				print '</td>';

				// Quantity
				print '<td class="right">';
				print '<input type="text" min="0" class="right maxwidth50" name="qty" value="'.dol_escape_htmltag($qty ? $qty : 1).'">'; // We must be able to enter decimal qty
				print '</td>';

				// Picture
				print '<td></td>';

				if ($action != 'editline') {
					print '<td class="right"></td>';
					print '<td class="right"></td>';
				}

				print '<td class="center">';
				print $form->buttonsSaveCancel("Add", '', '', 1);
				print '</td>';

				print '</tr>';
			} // Fin si c'est payé/validé

			print '</table>';
			print '</div>';

			print '<script javascript>

			/* JQuery for product free or predefined select */
			jQuery(document).ready(function() {
				jQuery("#value_unit_ht").keyup(function(event) {
					console.log(event.which);		// discard event tag and arrows
					if (event.which != 9 && (event.which < 37 ||event.which > 40) && jQuery("#value_unit_ht").val() != "") {
						jQuery("#value_unit").val("");
					}
				});
				jQuery("#value_unit").keyup(function(event) {
					console.log(event.which);		// discard event tag and arrows
					if (event.which != 9 && (event.which < 37 || event.which > 40) && jQuery("#value_unit").val() != "") {
						jQuery("#value_unit_ht").val("");
					}
				});
			});

			</script>';

			print '</form>';

			print dol_get_fiche_end();
		}
	} else {
		dol_print_error($db);
	}
} else {
	print 'Record not found';

	llxFooter();
	exit(1);
}


/*
 * Action bar
 */

print '<div class="tabsAction">';

if ($action != 'create' && $action != 'edit' && $action != 'editline') {
	$object = new ExpenseReport($db);
	$object->fetch($id, $ref);

	// Send
	if (empty($user->socid)) {
		if ($object->status > ExpenseReport::STATUS_DRAFT) {
			//if ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) || $user->rights->expensereport->expensereport_advance->send)) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=presend&mode=init#formmailbeforetitle">'.$langs->trans('SendMail').'</a></div>';
			//} else
			//	print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#">' . $langs->trans('SendMail') . '</a></div>';
		}
	}

	/* Si l'état est "Brouillon"
	 *	ET user à droit "creer/supprimer"
	*	ET fk_user_author == user courant
	* 	Afficher : "Enregistrer" / "Modifier" / "Supprimer"
	*/
	if ($user->rights->expensereport->creer && $object->status == ExpenseReport::STATUS_DRAFT) {
		if (in_array($object->fk_user_author, $user->getAllChildIds(1)) || !empty($user->rights->expensereport->writeall_advance)) {
			// Modify
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

			// Validate
			if (count($object->lines) > 0) {
				print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=save&token='.newToken().'&id='.$object->id.'">'.$langs->trans('ValidateAndSubmit').'</a></div>';
			}
		}
	}

	/* Si l'état est "Refusée"
	 *	ET user à droit "creer/supprimer"
	 *	ET fk_user_author == user courant
	 * 	Afficher : "Enregistrer" / "Modifier" / "Supprimer"
	 */
	if ($user->rights->expensereport->creer && $object->status == ExpenseReport::STATUS_REFUSED) {
		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid) {
			// Modify
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans('Modify').'</a></div>';

			// setdraft (le statut refusée est identique à brouillon)
			//print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=brouillonner&id='.$id.'">'.$langs->trans('ReOpen').'</a>';
			// Enregistrer depuis le statut "Refusée"
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=save_from_refuse&token='.newToken().'&id='.$object->id.'">'.$langs->trans('ValidateAndSubmit').'</a></div>';
		}
	}

	if ($user->rights->expensereport->to_paid && $object->status == ExpenseReport::STATUS_APPROVED) {
		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid) {
			// setdraft
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=setdraft&token='.newToken().'&id='.$object->id.'">'.$langs->trans('SetToDraft').'</a></div>';
		}
	}

	/* Si l'état est "En attente d'approbation"
	 *	ET user à droit de "approve"
	 *	ET fk_user_validator == user courant
	 *	Afficher : "Valider" / "Refuser" / "Supprimer"
	 */
	if ($object->status == ExpenseReport::STATUS_VALIDATED) {
		if (in_array($object->fk_user_author, $user->getAllChildIds(1))) {
			// set draft
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=setdraft&token='.newToken().'&id='.$object->id.'">'.$langs->trans('SetToDraft').'</a></div>';
		}
	}

	if ($user->rights->expensereport->approve && $object->status == ExpenseReport::STATUS_VALIDATED) {
		//if($object->fk_user_validator==$user->id)
		//{
			// Validate
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=validate&id='.$object->id.'">'.$langs->trans('Approve').'</a></div>';
			// Deny
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=refuse&id='.$object->id.'">'.$langs->trans('Deny').'</a></div>';
		//}

		if ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid) {
			// Cancel
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&id='.$object->id.'">'.$langs->trans("Cancel").'</a></div>';
		}
	}


	// If status is Approved
	// ---------------------

	if ($user->rights->expensereport->approve && $object->status == ExpenseReport::STATUS_APPROVED) {
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=refuse&id='.$object->id.'">'.$langs->trans('Deny').'</a></div>';
	}

	// If bank module is used
	if ($user->rights->expensereport->to_paid && !empty($conf->banque->enabled) && $object->status == ExpenseReport::STATUS_APPROVED) {
		// Pay
		if ($remaintopay == 0) {
			print '<div class="inline-block divButAction"><span class="butActionRefused classfortooltip" title="'.$langs->trans("DisabledBecauseRemainderToPayIsZero").'">'.$langs->trans('DoPayment').'</span></div>';
		} else {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/expensereport/payment/payment.php?id='.$object->id.'&amp;action=create">'.$langs->trans('DoPayment').'</a></div>';
		}
	}

	// If bank module is not used
	if (($user->rights->expensereport->to_paid || empty($conf->banque->enabled)) && $object->status == ExpenseReport::STATUS_APPROVED) {
		//if ((round($remaintopay) == 0 || empty($conf->banque->enabled)) && $object->paid == 0)
		if ($object->paid == 0) {
			print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=set_paid&token='.newToken().'">'.$langs->trans("ClassifyPaid")."</a></div>";
		}
	}

	if ($user->rights->expensereport->creer && ($user->id == $object->fk_user_author || $user->id == $object->fk_user_valid) && $object->status == ExpenseReport::STATUS_APPROVED) {
		// Cancel
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Cancel").'</a></div>';
	}

	// TODO Replace this. It should be SetUnpaid and should go back to status unpaid not canceled.
	if (($user->rights->expensereport->approve || $user->rights->expensereport->to_paid) && $object->status == ExpenseReport::STATUS_CLOSED) {
		// Cancel
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=cancel&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Cancel").'</a></div>';
	}

	if ($user->rights->expensereport->to_paid && $object->paid && $object->status == ExpenseReport::STATUS_CLOSED) {
		// Set unpaid
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=set_unpaid&token='.newToken().'&id='.$object->id.'">'.$langs->trans('ClassifyUnPaid').'</a></div>';
	}

	// Clone
	if ($user->rights->expensereport->creer) {
		print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=clone&token='.newToken().'">'.$langs->trans("ToClone").'</a></div>';
	}

	/* If draft, validated, cancel, and user can create, he can always delete its card before it is approved */
	if ($user->rights->expensereport->creer && $user->id == $object->fk_user_author && $object->status < ExpenseReport::STATUS_APPROVED) {
		// Delete
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
	} elseif ($candelete && $object->status != ExpenseReport::STATUS_CLOSED) {
		// Delete
		print '<div class="inline-block divButAction"><a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&id='.$object->id.'">'.$langs->trans('Delete').'</a></div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
}

print '</div>';


// Select mail models is same action as presend
if (GETPOST('modelselected', 'alpha')) {
	$action = 'presend';
}

if ($action != 'presend') {
	/*
	 * Generate documents
	 */

	print '<div class="fichecenter"><div class="fichehalfleft">';
	print '<a name="builddoc"></a>'; // ancre

	if ($user->rights->expensereport->creer && $action != 'create' && $action != 'edit') {
		$filename = dol_sanitizeFileName($object->ref);
		$filedir = $conf->expensereport->dir_output."/".dol_sanitizeFileName($object->ref);
		$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
		$genallowed	= $user->rights->expensereport->creer;
		$delallowed	= $user->rights->expensereport->creer;
		$var = true;
		print $formfile->showdocuments('expensereport', $filename, $filedir, $urlsource, $genallowed, $delallowed);
		$somethingshown = $formfile->numoffiles;
	}

	// Disabled for expensereport, there is no thirdparty on expensereport, so nothing to define the list of other object we can suggest to link to
	/*
	if ($action != 'create' && $action != 'edit' && ($id || $ref))
	{
		$linktoelem = $form->showLinkToObjectBlock($object, null, array('expensereport'));
		$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);
	}
	*/

	print '</div><div class="fichehalfright">';
	// List of actions on element
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions = new FormActions($db);
	$somethingshown = $formactions->showactions($object, 'expensereport', null);

	print '</div></div>';
}

// Presend form
$modelmail = 'expensereport';
$defaulttopic = 'SendExpenseReportRef';
$diroutput = $conf->expensereport->dir_output;
$trackid = 'exp'.$object->id;

include DOL_DOCUMENT_ROOT.'/core/tpl/card_presend.tpl.php';


llxFooter();

$db->close();
