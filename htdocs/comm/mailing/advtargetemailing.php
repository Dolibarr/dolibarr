<?php
/* Copyright (C) 2014 Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024 MDW                  <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *       \file      htdocs/comm/mailing/advtargetemailing.php
 *       \ingroup   mailing
 *       \brief     Page to define emailing targets. Visible when MAIN_FEATURES_LEVEL is 1.
 *					@TODO This page needs a lot of works to be stable and understandable.
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/advthirdparties.modules.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/advtargetemailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/html.formadvtargetemailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('mails', 'admin', 'companies', 'categories'));

$action = GETPOST('action', 'aZ09');
$toselect   = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "email";
}

$id = GETPOSTINT('id');
$rowid = GETPOSTINT('rowid');
$search_nom = GETPOST("search_nom");
$search_prenom = GETPOST("search_prenom");
$search_email = GETPOST("search_email");
$template_id = GETPOSTINT('template_id');

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha')) {
	$search_nom = '';
	$search_prenom = '';
	$search_email = '';
}
$array_query = array();
'@phan-var-force array<string,int|string|string[]> $array_query';

$object = new Mailing($db);
$result = $object->fetch($id);

$advTarget = new AdvanceTargetingMailing($db);

if ($template_id <= 0) {
	$advTarget->fk_element = $id;
	$advTarget->type_element = 'mailing';
	$result = $advTarget->fetch_by_mailing();
} else {
	$result = $advTarget->fetch($template_id);
}

if ($result < 0) {
	setEventMessages($advTarget->error, $advTarget->errors, 'errors');
} else {
	if (!empty($advTarget->id)) {
		$array_query = json_decode($advTarget->filtervalue, true);
	}
}

// List of sending methods
$listofmethods = array();
//$listofmethods['default'] = $langs->trans('DefaultOutgoingEmailSetup');
$listofmethods['mail'] = 'PHP mail function';
//$listofmethods['simplemail']='Simplemail class';
$listofmethods['smtps'] = 'SMTP/SMTPS socket library';
if (version_compare(phpversion(), '7.0', '>=')) {
	$listofmethods['swiftmailer'] = 'Swift Mailer socket library';
}

// Security check
if (!$user->hasRight('mailing', 'lire') || (!getDolGlobalString('EXTERNAL_USERS_ARE_AUTHORIZED') && $user->socid > 0)) {
	accessforbidden();
}
if (empty($action) && empty($object->id)) {
	accessforbidden('Object not found');
}

$permissiontoread = $user->hasRight('mailing', 'lire');
$permissiontoadd = $user->hasRight('mailing', 'creer');
$permissiontovalidatesend = $user->hasRight('mailing', 'valider');
$permissiontodelete = $user->hasRight('mailing', 'supprimer');


/*
 * Actions
 */

if ($action == 'loadfilter' && $permissiontoread) {
	if (!empty($template_id)) {
		$result = $advTarget->fetch($template_id);
		if ($result < 0) {
			setEventMessages($advTarget->error, $advTarget->errors, 'errors');
		} else {
			if (!empty($advTarget->id)) {
				$array_query = json_decode($advTarget->filtervalue, true);
			}
		}
	}
}

if ($action == 'add' && $permissiontoadd) {		// Add recipients
	$user_contact_query = false;

	$array_query = array();

	// Get extra fields

	foreach ($_POST as $key => $value) {
		// print '$key='.$key.' $value='.$value.'<BR>';
		if (preg_match("/^options_.*(?<!_cnct)$/", $key)) {
			// Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/", $key)) {
				$dtarr = array();
				$dtarr = explode('_', $key);
				if (!array_key_exists('options_'.$dtarr[1].'_st_dt', $array_query)) {
					$array_query['options_'.$dtarr[1].'_st_dt'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_st_dtmonth'), GETPOSTINT('options_'.$dtarr[1].'_st_dtday'), GETPOSTINT('options_'.$dtarr[1].'_st_dtyear'));
				}
			} elseif (preg_match("/end_dt/", $key)) {
				// Special case for end date come with 3 inputs day, month, year
				$dtarr = array();
				$dtarr = explode('_', $key);
				if (!array_key_exists('options_'.$dtarr[1].'_end_dt', $array_query)) {
					$array_query['options_'.$dtarr[1].'_end_dt'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_end_dtmonth'), GETPOSTINT('options_'.$dtarr[1].'_end_dtday'), GETPOSTINT('options_'.$dtarr[1].'_end_dtyear'));
				}
			} else {
				$array_query[$key] = GETPOST($key);
			}
		}
		if (preg_match("/^options_.*_cnct/", $key)) {
			$user_contact_query = true;
			// Special case for start date come with 3 inputs day, month, year
			if (preg_match("/st_dt/", $key)) {
				$dtarr = array();
				$dtarr = explode('_', $key);
				if (!array_key_exists('options_'.$dtarr[1].'_st_dt_cnct', $array_query)) {
					$array_query['options_'.$dtarr[1].'_st_dt_cnct'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_st_dtmonth_cnct'), GETPOSTINT('options_'.$dtarr[1].'_st_dtday_cnct'), GETPOSTINT('options_'.$dtarr[1].'_st_dtyear_cnct'));
				}
			} elseif (preg_match("/end_dt/", $key)) {
				// Special case for end date come with 3 inputs day, month, year
				$dtarr = array();
				$dtarr = explode('_', $key);
				if (!array_key_exists('options_'.$dtarr[1].'_end_dt_cnct', $array_query)) {
					$array_query['options_'.$dtarr[1].'_end_dt_cnct'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_end_dtmonth_cnct'), GETPOSTINT('options_'.$dtarr[1].'_end_dtday_cnct'), GETPOSTINT('options_'.$dtarr[1].'_end_dtyear_cnct'));
				}
			} else {
				$array_query[$key] = GETPOST($key);
			}
		}

		if (preg_match("/^cust_/", $key)) {
			$array_query[$key] = GETPOST($key);
		}

		if (preg_match("/^contact_/", $key)) {
			$array_query[$key] = GETPOST($key);

			$specials_date_key = array(
					'contact_update_st_dt',
					'contact_update_end_dt',
					'contact_create_st_dt',
					'contact_create_end_dt'
			);
			foreach ($specials_date_key as $date_key) {
				if ($key == $date_key) {
					$dt = GETPOST($date_key);
					if (!empty($dt)) {
						$array_query[$key] = dol_mktime(0, 0, 0, GETPOSTINT($date_key.'month'), GETPOSTINT($date_key.'day'), GETPOSTINT($date_key.'year'));
					} else {
						$array_query[$key] = '';
					}
				}
			}

			if (!empty($array_query[$key])) {
				$user_contact_query = true;
			}
		}

		if ($array_query['type_of_target'] == 2 || $array_query['type_of_target'] == 4) {
			$user_contact_query = true;
		}

		if (preg_match("/^type_of_target/", $key)) {
			$array_query[$key] = GETPOST($key);
		}
	}

	// if ($array_query ['type_of_target'] == 1 || $array_query ['type_of_target'] == 3) {
	$result = $advTarget->query_thirdparty($array_query);
	if ($result < 0) {
		setEventMessages($advTarget->error, $advTarget->errors, 'errors');
	}
	/*} else {
		$advTarget->thirdparty_lines = array ();
	}*/

	if ($user_contact_query && ($array_query['type_of_target'] == 1 || $array_query['type_of_target'] == 2 || $array_query['type_of_target'] == 4)) {
		$result = $advTarget->query_contact($array_query, 1);
		if ($result < 0) {
			setEventMessages($advTarget->error, $advTarget->errors, 'errors');
		}
		// If use contact but no result use artefact to so not use socid into add_to_target
		if (count($advTarget->contact_lines) == 0) {
			$advTarget->contact_lines = array(
					0
			);
		}
	} else {
		$advTarget->contact_lines = array();
	}

	$mailingadvthirdparties = null;
	if ((count($advTarget->thirdparty_lines) > 0) || (count($advTarget->contact_lines) > 0)) {
		// Add targets into database
		$mailingadvthirdparties = new mailing_advthirdparties($db);
		$result = $mailingadvthirdparties->add_to_target_spec($id, $advTarget->thirdparty_lines, $array_query['type_of_target'], $advTarget->contact_lines);
	} else {
		$result = 0;
	}

	if ($result > 0) {
		$query_temlate_id = '';
		if (!empty($template_id)) {
			$query_temlate_id = '&template_id='.$template_id;
		}
		setEventMessages($langs->trans("XTargetsAdded", $result), null, 'mesgs');
		header("Location: ".$_SERVER['PHP_SELF']."?id=".$id.$query_temlate_id);
		exit();
	}
	if ($result == 0) {
		setEventMessages($langs->trans("WarningNoEMailsAdded"), null, 'warnings');
	}
	if ($result < 0 && is_object($mailingadvthirdparties)) {
		setEventMessages($mailingadvthirdparties->error, $mailingadvthirdparties->errors, 'errors');
	}
}

if ($action == 'clear' && $permissiontoadd) {
	$mailingtargets = new MailingTargets($db);
	$mailingtargets->clear_target($id);

	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit();
}

if (($action == 'savefilter' || $action == 'createfilter') && $permissiontoadd) {
	$template_name = GETPOST('template_name');
	$error = 0;

	if ($action == 'createfilter' && empty($template_name) && $permissiontoadd) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv('AdvTgtOrCreateNewFilter')), null, 'errors');
		$error++;
	}

	if (empty($error)) {
		$array_query = array();

		// Get extra fields
		foreach ($_POST as $key => $value) {
			if (preg_match("/^options_.*(?<!_cnct)$/", $key)) {
				// Special case for start date come with 3 inputs day, month, year
				if (preg_match("/st_dt/", $key)) {
					$dtarr = array();
					$dtarr = explode('_', $key);
					if (!array_key_exists('options_'.$dtarr[1].'_st_dt', $array_query)) {
						$array_query['options_'.$dtarr[1].'_st_dt'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_st_dtmonth'), GETPOSTINT('options_'.$dtarr[1].'_st_dtday'), GETPOSTINT('options_'.$dtarr[1].'_st_dtyear'));
					}
				} elseif (preg_match("/end_dt/", $key)) {
					// Special case for end date come with 3 inputs day, month, year
					$dtarr = array();
					$dtarr = explode('_', $key);
					if (!array_key_exists('options_'.$dtarr[1].'_end_dt', $array_query)) {
						$array_query['options_'.$dtarr[1].'_end_dt'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_end_dtmonth'), GETPOSTINT('options_'.$dtarr[1].'_end_dtday'), GETPOSTINT('options_'.$dtarr[1].'_end_dtyear'));
						// print $array_query['options_'.$dtarr[1].'_end_dt'];
						// 01/02/1013=1361228400
					}
				} else {
					$array_query[$key] = GETPOST($key);
				}
			}
			if (preg_match("/^options_.*_cnct/", $key)) {
				// Special case for start date come with 3 inputs day, month, year
				if (preg_match("/st_dt/", $key)) {
					$dtarr = array();
					$dtarr = explode('_', $key);
					if (!array_key_exists('options_'.$dtarr[1].'_st_dt_cnct', $array_query)) {
						$array_query['options_'.$dtarr[1].'_st_dt_cnct'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_st_dtmonth_cnct'), GETPOSTINT('options_'.$dtarr[1].'_st_dtday_cnct'), GETPOSTINT('options_'.$dtarr[1].'_st_dtyear_cnct'));
					}
				} elseif (preg_match("/end_dt/", $key)) {
					// Special case for end date come with 3 inputs day, month, year
					$dtarr = array();
					$dtarr = explode('_', $key);
					if (!array_key_exists('options_'.$dtarr[1].'_end_dt_cnct', $array_query)) {
						$array_query['options_'.$dtarr[1].'_end_dt_cnct'] = dol_mktime(0, 0, 0, GETPOSTINT('options_'.$dtarr[1].'_end_dtmonth_cnct'), GETPOSTINT('options_'.$dtarr[1].'_end_dtday_cnct'), GETPOSTINT('options_'.$dtarr[1].'_end_dtyear_cnct'));
						// print $array_query['cnct_options_'.$dtarr[1].'_end_dt'];
						// 01/02/1013=1361228400
					}
				} else {
					$array_query[$key] = GETPOST($key);
				}
			}

			if (preg_match("/^cust_/", $key)) {
				$array_query[$key] = GETPOST($key);
			}

			if (preg_match("/^contact_/", $key)) {
				$array_query[$key] = GETPOST($key);

				$specials_date_key = array(
						'contact_update_st_dt',
						'contact_update_end_dt',
						'contact_create_st_dt',
						'contact_create_end_dt'
				);
				foreach ($specials_date_key as $date_key) {
					if ($key == $date_key) {
						$dt = GETPOST($date_key);
						if (!empty($dt)) {
							$array_query[$key] = dol_mktime(0, 0, 0, GETPOSTINT($date_key.'month'), GETPOSTINT($date_key.'day'), GETPOSTINT($date_key.'year'));
						} else {
							$array_query[$key] = '';
						}
					}
				}
			}

			if (preg_match("/^type_of_target/", $key)) {
				$array_query[$key] = GETPOST($key);
			}
		}
		$advTarget->filtervalue = json_encode($array_query);

		if ($action == 'createfilter') {		// Test on permission already done
			$advTarget->name = $template_name;
			$result = $advTarget->create($user);
			if ($result < 0) {
				setEventMessages($advTarget->error, $advTarget->errors, 'errors');
			}
		} elseif ($action == 'savefilter') {	// Test on permission already done
			$result = $advTarget->update($user);
			if ($result < 0) {
				setEventMessages($advTarget->error, $advTarget->errors, 'errors');
			}
		}
		$template_id = $advTarget->id;
	}
}

if ($action == 'deletefilter' && $permissiontoadd) {
	$result = $advTarget->delete($user);
	if ($result < 0) {
		setEventMessages($advTarget->error, $advTarget->errors, 'errors');
	}
	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit();
}

if ($action == 'delete' && $permissiontoadd) {
	// Ici, rowid indique le destinataire et id le mailing
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid = ".((int) $rowid);
	$resql = $db->query($sql);
	if ($resql) {
		if (!empty($id)) {
			$mailingtargets = new MailingTargets($db);
			$mailingtargets->update_nb($id);

			header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
			exit();
		} else {
			header("Location: liste.php");
			exit();
		}
	} else {
		dol_print_error($db);
	}
}

if (GETPOST("button_removefilter")) {
	$search_nom = '';
	$search_prenom = '';
	$search_email = '';
}


/*
 * View
 */

$form = new Form($db);
$formmailing = new FormMailing($db);
$formadvtargetemaling = new FormAdvTargetEmailing($db);
$formcompany = new FormCompany($db);
$formother = new FormOther($db);

$help_url = 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing';
llxHeader('', $langs->trans("MailAdvTargetRecipients"), $help_url);

$arrayofselected = is_array($toselect) ? $toselect : array();
$totalarray = [
	'nbfield' => 0,
];

if ($object->fetch($id) >= 0) {
	$head = emailing_prepare_head($object);

	print dol_get_fiche_head($head, 'advtargets', $langs->trans("Mailing"), -1, 'email');

	$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("", 'title', $object->title, $object, 0, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("", 'title', $object->title, $object, 0, 'string', '', null, null, '', 1);
	$morehtmlref .= '</div>';

	$morehtmlstatus = '';
	$nbtry = $nbok = 0;
	if ($object->status == $object::STATUS_SENTPARTIALY || $object->status == $object::STATUS_SENTCOMPLETELY) {
		$nbtry = $object->countNbOfTargets('alreadysent');
		$nbko  = $object->countNbOfTargets('alreadysentko');
		$nbok = ($nbtry - $nbko);

		$morehtmlstatus .= ' ('.$nbtry.'/'.$object->nbemail;
		if ($nbko) {
			$morehtmlstatus .= ' - '.$nbko.' '.$langs->trans("Error");
		}
		$morehtmlstatus .= ') &nbsp; ';
	}

	dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlstatus);

	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">'."\n";

	// From
	print '<tr><td class="titlefield">';
	print $langs->trans("MailFrom").'</td><td>';
	$emailarray = CMailFile::getArrayAddress($object->email_from);
	foreach ($emailarray as $email => $name) {
		if ($name && $name != $email) {
			print dol_escape_htmltag($name).' &lt;'.$email;
			print '&gt;';
			if (!isValidEmail($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadEMail", $email));
			}
		} else {
			print dol_print_email($object->email_from, 0, 0, 0, 0, 1);
		}
	}

	print '</td></tr>';

	// Errors to
	if ($object->messtype != 'sms') {
		print '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>';
		$emailarray = CMailFile::getArrayAddress($object->email_errorsto);
		foreach ($emailarray as $email => $name) {
			if ($name != $email) {
				print dol_escape_htmltag((string) $name).' &lt;'.$email;
				print '&gt;';
				if ($email && !isValidEmail($email)) {
					$langs->load("errors");
					print img_warning($langs->trans("ErrorBadEMail", $email));
				} elseif ($email && !isValidMailDomain($email)) {
					$langs->load("errors");
					print img_warning($langs->trans("ErrorBadMXDomain", $email));
				}
			} else {
				print dol_print_email($object->email_errorsto, 0, 0, 0, 0, 1);
			}
		}
		print '</td></tr>';
	}

	// Reply to
	if ($object->messtype != 'sms') {
		print '<tr><td>';
		print $form->editfieldkey("MailReply", 'email_replyto', $object->email_replyto, $object, (int) ($user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY), 'string');
		print '</td><td>';
		print $form->editfieldval("MailReply", 'email_replyto', $object->email_replyto, $object, $user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY, 'string');
		$email = CMailFile::getValidAddress($object->email_replyto, 2);
		if ($action != 'editemail_replyto') {
			if ($email && !isValidEmail($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadEMail", $email));
			} elseif ($email && !isValidMailDomain($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadMXDomain", $email));
			}
		}
		print '</td></tr>';
	}

	print '</table>';
	print '</div>';


	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border centpercent tableforfield">';

	// Number of distinct emails
	print '<tr><td>';
	print $langs->trans("TotalNbOfDistinctRecipients");
	print '</td><td>';
	$nbemail = ($object->nbemail ? $object->nbemail : 0);
	if (is_numeric($nbemail)) {
		$htmltooltip = '';
		if ((getDolGlobalString('MAILING_LIMIT_SENDBYWEB') && getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') < $nbemail) && ($object->status == 1 || ($object->status == 2 && $nbtry < $nbemail))) {
			if (getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') > 0) {
				$htmltooltip .= $langs->trans('LimitSendingEmailing', getDolGlobalString('MAILING_LIMIT_SENDBYWEB'));
			} else {
				$htmltooltip .= $langs->trans('SendingFromWebInterfaceIsNotAllowed');
			}
		}
		if (empty($nbemail)) {
			$nbemail .= ' '.img_warning($langs->trans('ToAddRecipientsChooseHere'));//.' <span class="warning">'.$langs->trans("NoTargetYet").'</span>';
		}
		if ($htmltooltip) {
			print $form->textwithpicto($nbemail, $htmltooltip, 1, 'info');
		} else {
			print $nbemail;
		}
	}
	print '</td></tr>';

	print '<tr><td>';
	print $langs->trans("MAIN_MAIL_SENDMODE");
	print '</td><td>';
	if ($object->messtype != 'sms') {
		if (getDolGlobalString('MAIN_MAIL_SENDMODE_EMAILING') && getDolGlobalString('MAIN_MAIL_SENDMODE_EMAILING') != 'default') {
			$text = $listofmethods[getDolGlobalString('MAIN_MAIL_SENDMODE_EMAILING')];
		} elseif (getDolGlobalString('MAIN_MAIL_SENDMODE')) {
			$text = $listofmethods[getDolGlobalString('MAIN_MAIL_SENDMODE')];
		} else {
			$text = $listofmethods['mail'];
		}
		print $text;
		if (getDolGlobalString('MAIN_MAIL_SENDMODE_EMAILING') != 'default') {
			if (getDolGlobalString('MAIN_MAIL_SENDMODE_EMAILING') != 'mail') {
				print ' <span class="opacitymedium">('.getDolGlobalString('MAIN_MAIL_SMTP_SERVER_EMAILING', getDolGlobalString('MAIN_MAIL_SMTP_SERVER')).')</span>';
			}
		} elseif (getDolGlobalString('MAIN_MAIL_SENDMODE') != 'mail' && getDolGlobalString('MAIN_MAIL_SMTP_SERVER')) {
			print ' <span class="opacitymedium">('.getDolGlobalString('MAIN_MAIL_SMTP_SERVER').')</span>';
		}
	} else {
		print 'SMS ';
		print ' <span class="opacitymedium">('.getDolGlobalString('MAIN_MAIL_SMTP_SERVER').')</span>';
	}
	print '</td></tr>';

	// Other attributes. Fields from hook formObjectOptions and Extrafields.
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	print '<br>';


	// Show email selectors
	if ($object->status == 0 && $user->hasRight('mailing', 'creer')) {
		// @phan-assert FormAdvTargetEmailing $formadvtargetemaling
		// @phan-assert AdvanceTargetingMailing $advTarget

		// @phan-assert array<string,int|string|string[] $array_query'

		// From controller using view
		'
		@phan-var-force FormAdvTargetEmailing $formadvtargetemaling
		@phan-var-force AdvanceTargetingMailing $advTarget
		@phan-var-force array<string,string|int|string[]> $array_query
		';

		print '<script>
			$(document).ready(function() {

				// Click Function
				$(":button[name=addcontact]").click(function() {
						$(":hidden[name=action]").val("add");
						$("#find_customer").submit();
				});

				$(":button[name=loadfilter]").click(function() {
						$(":hidden[name=action]").val("loadfilter");
						$("#find_customer").submit();
				});

				$(":button[name=deletefilter]").click(function() {
						$(":hidden[name=action]").val("deletefilter");
						$("#find_customer").submit();
				});

				$(":button[name=savefilter]").click(function() {
						$(":hidden[name=action]").val("savefilter");
						$("#find_customer").submit();
				});

				$(":button[name=createfilter]").click(function() {
						$(":hidden[name=action]").val("createfilter");
						$("#find_customer").submit();
				});
			});
		</script>';


		print load_fiche_titre($langs->trans("AdvTgtTitle").'...', '', '');

		print '<form name="find_customer" id="find_customer" action="'.$_SERVER['PHP_SELF'].'?id='.$id.'"  method="POST">'."\n";
		print '<input type="hidden" name="token" value="'.newToken().'">'."\n";
		print '<input type="hidden" name="action" value="">'."\n";
		print '<table class="border centpercent">'."\n";

		print '<tr><td class="titlefieldcreate">'.$langs->trans('AdvTgtNameTemplate').'</td><td class="valignmiddle">';
		if (!empty($template_id)) {
			$default_template = $template_id;
		} else {
			$default_template = $advTarget->id;
		}
		print $formadvtargetemaling->selectAdvtargetemailingTemplate('template_id', $default_template, $langs->trans("SelectAPredefinedFilter"), $advTarget->type_element, 'minwidth100 valignmiddle');
		print '<input type="button" name="loadfilter" id="loadfilter" value="'.$langs->trans('AdvTgtLoadFilter').'" class="button smallpaddingimp"/>';
		print '<input type="button" name="deletefilter" id="deletefilter" value="'.$langs->trans('AdvTgtDeleteFilter').'" class="button smallpaddingimp"/>';
		print '<input type="button" name="savefilter" id="savefilter" value="'.$langs->trans('AdvTgtSaveFilter').'" class="button smallpaddingimp"/>';	// Update filter
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		print '<tr><td>'.$langs->trans('AdvTgtOrCreateNewFilter').'</td><td>';
		print '<input type="text" name="template_name" id="template_name" value=""/>';
		print '<input type="button" name="createfilter" id="createfilter" value="'.$langs->trans('AdvTgtCreateFilter').'" class="button smallpaddingimp"/>';
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		print '<tr><td colspan="3"><hr></td></tr>';

		print '<tr>'."\n";
		print '<td colspan="3" class="center">'."\n";
		print '<input type="button" name="addcontact" id="addcontact" value="'.$langs->trans('AdvTgtAddContact').'" class="button"/>'."\n";
		print '</td>'."\n";
		print '</tr>'."\n";

		print '<tr><td>'.$langs->trans('AdvTgtTypeOfIncude').'</td><td>';
		print $form->selectarray('type_of_target', $advTarget->select_target_type, $array_query['type_of_target']);
		print '</td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtTypeOfIncudeHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Customer name
		print '<tr><td>'.$langs->trans('ThirdPartyName');
		if (!empty($array_query['cust_name'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_name" value="'.$array_query['cust_name'].'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Code Client
		print '<tr><td>'.$langs->trans('CustomerCode');
		if (!empty($array_query['cust_code'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			$cust_code_str = (string) $array_query['cust_code'];
		} else {
			$cust_code_str = null;
		}
		print '</td><td><input type="text" name="cust_code"'.($cust_code_str != null ? ' value="'.$cust_code_str : '').'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Address Client
		print '<tr><td>'.$langs->trans('Address');
		if (!empty($array_query['cust_adress'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_adress" value="'.$array_query['cust_adress'].'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Zip Client
		print '<tr><td>'.$langs->trans('Zip');
		if (!empty($array_query['cust_zip'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_zip" value="'.$array_query['cust_zip'].'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// City Client
		print '<tr><td>'.$langs->trans('Town');
		if (!empty($array_query['cust_city'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="cust_city" value="'.$array_query['cust_city'].'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// State Client
		print '<tr><td>'.$langs->trans('State');
		if (!empty($array_query['cust_state'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print $formadvtargetemaling->multiselectState('cust_state', $array_query['cust_state']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Customer Country
		print '<tr><td>'.$langs->trans("Country");
		if (!empty($array_query['cust_country'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print $formadvtargetemaling->multiselectCountry('cust_country', $array_query['cust_country']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// State Customer
		print '<tr><td>'.$langs->trans('Status').' '.$langs->trans('ThirdParty');
		if (!empty($array_query['cust_status'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray(
			'cust_status',
			array(
				'0' => $langs->trans('ActivityCeased'),
				'1' => $langs->trans('InActivity')
			),
			$array_query['cust_status']
		);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Mother Company
		print '<tr><td>'.$langs->trans("ParentCompany");
		if (!empty($array_query['cust_mothercompany'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print '<input type="text" name="cust_mothercompany" value="'.$array_query['cust_mothercompany'].'"/>';
		print '</td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Prospect/Customer
		$selected = $array_query['cust_typecust'];
		print '<tr><td>'.$langs->trans('ProspectCustomer').' '.$langs->trans('ThirdParty');
		if (!empty($array_query['cust_typecust'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		$options_array = array(
			2 => $langs->trans('Prospect'),
			3 => $langs->trans('ProspectCustomer'),
			1 => $langs->trans('Customer'),
			0 => $langs->trans('NorProspectNorCustomer')
		);
		print $formadvtargetemaling->advMultiselectarray('cust_typecust', $options_array, $selected);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Prospection status
		print '<tr><td>'.$langs->trans('ProspectLevel');
		if (!empty($array_query['cust_prospect_status'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->multiselectProspectionStatus($array_query['cust_prospect_status'], 'cust_prospect_status');
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Prospection comm status
		print '<tr><td>'.$langs->trans('StatusProsp');
		if (!empty($array_query['cust_comm_status'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray('cust_comm_status', $advTarget->type_statuscommprospect, $array_query['cust_comm_status']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Customer Type
		print '<tr><td>'.$langs->trans("ThirdPartyType");
		if (!empty($array_query['cust_typeent'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print $formadvtargetemaling->advMultiselectarray('cust_typeent', $formcompany->typent_array(0, " AND id <> 0"), $array_query['cust_typeent']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Staff number
		print '<td>'.$langs->trans("Staff");
		if (!empty($array_query['cust_effectif_id'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray("cust_effectif_id", $formcompany->effectif_array(0, " AND id <> 0"), $array_query['cust_effectif_id']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Sales manager
		print '<tr><td>'.$langs->trans("SalesRepresentatives");
		if (!empty($array_query['cust_saleman'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print $formadvtargetemaling->multiselectselectSalesRepresentatives('cust_saleman', $array_query['cust_saleman'], $user);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Customer Default Language
		if (getDolGlobalInt('MAIN_MULTILANGS')) {
			print '<tr><td>'.$langs->trans("DefaultLang");
			if (!empty($array_query['cust_language'])) {
				print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
			}
			print '</td><td>'."\n";
			print $formadvtargetemaling->multiselectselectLanguage('cust_language', $array_query['cust_language']);
			print '</td><td>'."\n";
			print '</td></tr>'."\n";
		}

		if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
			// Customer Categories
			print '<tr><td>'.$langs->trans("CustomersCategoryShort");
			print '</td><td>'."\n";
			print $form->selectCategories(Categorie::TYPE_CUSTOMER, 'cust_categ', $object);
			print '</td><td>'."\n";
			print '</td></tr>'."\n";
		}

		// Standard Extrafield feature
		if (!getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
			$socstatic = new Societe($db);
			$elementtype = $socstatic->table_element;
			// fetch optionals attributes and labels
			require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($db);
			$extrafields->fetch_name_optionals_label($elementtype);
			foreach ($extrafields->attributes[$elementtype]['label'] as $key => $val) {
				if ($key != 'ts_nameextra' && $key != 'ts_payeur') {
					if (isset($extrafields->attributes[$elementtype]['langfile'][$key])) {
						$langs->load($extrafields->attributes[$elementtype]['langfile'][$key]);
					}
					print '<tr><td>'.$langs->trans($extrafields->attributes[$elementtype]['label'][$key]);
					if (!empty($array_query['options_'.$key]) || (is_array($array_query['options_'.$key]) && count($array_query['options_'.$key]) > 0)) {
						print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
					}
					print '</td><td>';
					if (($extrafields->attributes[$elementtype]['type'][$key] == 'varchar') || ($extrafields->attributes[$elementtype]['type'][$key] == 'text')) {
						print '<input type="text" name="options_'.$key.'"/></td><td>'."\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
					} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'int') || ($extrafields->attributes[$elementtype]['type'][$key] == 'double')) {
						print $langs->trans("AdvTgtMinVal").'<input type="text" name="options'.$key.'_min"/>';
						print $langs->trans("AdvTgtMaxVal").'<input type="text" name="options'.$key.'_max"/>';
						print '</td><td>'."\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
					} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'date') || ($extrafields->attributes[$elementtype]['type'][$key] == 'datetime')) {
						print '<table class="nobordernopadding"><tr>';
						print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
						print $form->selectDate('', 'options_'.$key.'_st_dt', 0, 0, 1);
						print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
						print $form->selectDate('', 'options_'.$key.'_end_dt', 0, 0, 1);
						print '</td></tr></table>';

						print '</td><td>'."\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
					} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'boolean')) {
						print $form->selectarray(
							'options_'.$key,
							array(
								'' => '',
								'1' => $langs->trans('Yes'),
								'0' => $langs->trans('No')
							),
							$array_query['options_'.$key]
						);
						print '</td><td>'."\n";
					} elseif ($extrafields->attributes[$elementtype]['type'][$key] == 'select') {
						print $formadvtargetemaling->advMultiselectarray('options_'.$key, $extrafields->attributes[$elementtype]['param'][$key]['options'], $array_query['options_'.$key]);
						print '</td><td>'."\n";
					} elseif ($extrafields->attributes[$elementtype]['type'][$key] == 'sellist') {
						print $formadvtargetemaling->advMultiselectarraySelllist('options_'.$key, $extrafields->attributes[$elementtype]['param'][$key]['options'], $array_query['options_'.$key]);
						print '</td><td>'."\n";
					} else {
						print '<table class="nobordernopadding"><tr>';
						print '<td></td><td>';
						if (is_array($array_query['options_'.$key])) {
							print $extrafields->showInputField($key, implode(',', $array_query['options_'.$key]), '', '', '', '', 0, 'societe', 1);
						} else {
							print $extrafields->showInputField($key, $array_query['options_'.$key], '', '', '', '', 0, 'societe', 1);
						}
						print '</td></tr></table>';

						print '</td><td>'."\n";
					}
					print '</td></tr>'."\n";
				}
			}
		} else {
			$std_soc = new Societe($db);
			$action_search = 'query';

			$parameters = array('advtarget' => 1);
			if (!empty($advTarget->id)) {
				$parameters = array('array_query' => $advTarget->filtervalue);
			}
			// Other attributes
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $std_soc, $action_search);
			print $hookmanager->resPrint;
		}

		// State Contact
		print '<tr><td>'.$langs->trans('Status').' '.$langs->trans('Contact');
		if (!empty($array_query['contact_status'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->advMultiselectarray(
			'contact_status',
			array(
				'0' => $langs->trans('ActivityCeased'),
				'1' => $langs->trans('InActivity')
			),
			$array_query['contact_status']
		);
		print '</td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtContactHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Civility
		print '<tr><td>'.$langs->trans("UserTitle");
		if (!empty($array_query['contact_civility'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>';
		print $formadvtargetemaling->multiselectCivility('contact_civility', $array_query['contact_civility']);
		print '</td></tr>';

		// contact name
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Lastname');
		if (!empty($array_query['contact_lastname'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="contact_lastname" value="'.$array_query['contact_lastname'].'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans('Firstname');
		if (!empty($array_query['contact_firstname'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td><input type="text" name="contact_firstname" value="'.$array_query['contact_firstname'].'"/></td><td>'."\n";
		print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
		print '</td></tr>'."\n";

		// Contact Country
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("Country");
		if (!empty($array_query['contact_country'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print $formadvtargetemaling->multiselectCountry('contact_country', $array_query['contact_country']);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Never send mass mailing
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("No_Email");
		if (!empty($array_query['contact_no_email'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print $form->selectarray(
			'contact_no_email',
			array(
				'' => '',
				'1' => $langs->trans('Yes'),
				'0' => $langs->trans('No')
			),
			$array_query['contact_no_email']
		);
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Contact Date Create
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateCreation");
		if (!empty($array_query['contact_create_st_dt'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print '<table class="nobordernopadding"><tr>';
		print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
		print $form->selectDate($array_query['contact_create_st_dt'], 'contact_create_st_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
		print $form->selectDate($array_query['contact_create_end_dt'], 'contact_create_end_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td></tr></table>';
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		// Contact update Create
		print '<tr><td>'.$langs->trans('Contact').' '.$langs->trans("DateLastModification");
		if (!empty($array_query['contact_update_st_dt'])) {
			print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
		}
		print '</td><td>'."\n";
		print '<table class="nobordernopadding"><tr>';
		print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
		print $form->selectDate($array_query['contact_update_st_dt'], 'contact_update_st_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
		print $form->selectDate($array_query['contact_update_end_dt'], 'contact_update_end_dt', 0, 0, 1, 'find_customer', 1, 1);
		print '</td></tr></table>';
		print '</td><td>'."\n";
		print '</td></tr>'."\n";

		if (isModEnabled('category') && $user->hasRight('categorie', 'lire')) {
			// Customer Categories
			print '<tr><td>'.$langs->trans("ContactCategoriesShort");
			print '</td><td>'."\n";
			print $form->selectCategories(Categorie::TYPE_CONTACT, 'contact_categ', $object);
			print '</td><td>'."\n";
			print '</td></tr>'."\n";
		}

		// Standard Extrafield feature
		if (!getDolGlobalString('MAIN_EXTRAFIELDS_DISABLED')) {
			$contactstatic = new Contact($db);
			$elementype = $contactstatic->table_element;
			// fetch optionals attributes and labels
			dol_include_once('/core/class/extrafields.class.php');
			$extrafields = new ExtraFields($db);
			$extrafields->fetch_name_optionals_label($elementype);
			if (!empty($extrafields->attributes[$elementtype]['type'])) {
				foreach ($extrafields->attributes[$elementtype]['type'] as $key => &$value) {
					if ($value == 'radio') {
						$value = 'select';
					}
				}
			}
			if (!empty($extrafields->attributes[$elementtype]['label'])) {
				foreach ($extrafields->attributes[$elementtype]['label'] as $key => $val) {
					print '<tr><td>'.$extrafields->attributes[$elementtype]['label'][$key];
					if ($array_query['options_'.$key.'_cnct'] != '' || (is_array($array_query['options_'.$key.'_cnct']) && count($array_query['options_'.$key.'_cnct']) > 0)) {
						print img_picto($langs->trans('AdvTgtUse'), 'ok.png@advtargetemailing');
					}
					print '</td><td>';
					if (($extrafields->attributes[$elementtype]['type'][$key] == 'varchar') || ($extrafields->attributes[$elementtype]['type'][$key] == 'text')) {
						print '<input type="text" name="options_'.$key.'_cnct"/></td><td>'."\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchTextHelp"), 1, 'help');
					} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'int') || ($extrafields->attributes[$elementtype]['type'][$key] == 'double')) {
						print $langs->trans("AdvTgtMinVal").'<input type="text" name="options_'.$key.'_min_cnct"/>';
						print $langs->trans("AdvTgtMaxVal").'<input type="text" name="options_'.$key.'_max_cnct"/>';
						print '</td><td>'."\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchIntHelp"), 1, 'help');
					} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'date') || ($extrafields->attributes[$elementtype]['type'][$key] == 'datetime')) {
						print '<table class="nobordernopadding"><tr>';
						print '<td>'.$langs->trans("AdvTgtStartDt").'</td><td>';
						print $form->selectDate('', 'options_'.$key.'_st_dt_cnct', 0, 0, 1);
						print '</td><td>'.$langs->trans("AdvTgtEndDt").'</td><td>';
						print $form->selectDate('', 'options_'.$key.'_end_dt_cnct', 0, 0, 1);
						print '</td></tr></table>';
						print '</td><td>'."\n";
						print $form->textwithpicto('', $langs->trans("AdvTgtSearchDtHelp"), 1, 'help');
					} elseif (($extrafields->attributes[$elementtype]['type'][$key] == 'boolean')) {
						print $form->selectarray(
							'options_'.$key.'_cnct',
							array(
								''  => '',
								'1' => $langs->trans('Yes'),
								'0' => $langs->trans('No')
							),
							$array_query['options_'.$key.'_cnct']
						);
						print '</td><td>'."\n";
					} elseif ($extrafields->attributes[$elementtype]['type'][$key] == 'select') {
						print $formadvtargetemaling->advMultiselectarray('options_'.$key.'_cnct', $extrafields->attributes[$elementtype]['param'][$key]['options'], $array_query['options_'.$key.'_cnct']);
						print '</td><td>'."\n";
					} elseif ($extrafields->attributes[$elementtype]['type'][$key] == 'sellist') {
						print $formadvtargetemaling->advMultiselectarraySelllist('options_'.$key.'_cnct', $extrafields->attributes[$elementtype]['param'][$key]['options'], $array_query['options_'.$key.'_cnct']);
						print '</td><td>'."\n";
					} else {
						if (is_array($array_query['options_'.$key.'_cnct'])) {
							print $extrafields->showInputField($key, implode(',', $array_query['options_'.$key.'_cnct']), '', '_cnct', '', '', 0, 'socpeople', 1);
						} else {
							print $extrafields->showInputField($key, $array_query['options_'.$key.'_cnct'], '', '_cnct', '', '', 0, 'socpeople', 1);
						}
						print '</td><td>'."\n";
					}
					print '</td></tr>'."\n";
				}
			}
		}

		print '</table>'."\n";
		print '<br>';
		print '<center><input type="button" name="addcontact" id="addcontact" value="'.$langs->trans('AdvTgtAddContact').'" class="butAction"/></center>'."\n";
		print '<br>';
		print '</form>'."\n";

		print '<br>';

		// TODO Replace this with an include of a.tpl that contains samecode than into targetemailing.php
		print '<form action="'.$_SERVER['PHP_SELF'].'?action=clear&id='.$object->id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print load_fiche_titre($langs->trans("ToClearAllRecipientsClickHere"));
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td class="liste_titre right"><input type="submit" class="button" value="'.$langs->trans("TargetsReset").'"></td>';
		print '</tr>';
		print '</table>';
		print '</form>';


		print '<br>';
	}
}

// End of page
llxFooter();
$db->close();
