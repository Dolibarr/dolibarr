<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2024 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2014      Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2024      MDW	                <mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/comm/mailing/cibles.php
 *       \ingroup    mailing
 *       \brief      Page to define or view emailing targets
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/mailings/modules_mailings.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ajax.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("mails", "admin"));

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
if (!$sortfield) {
	$sortfield = "mc.statut,email";
}
if (!$sortorder) {
	$sortorder = "DESC,ASC";
}

$id = GETPOSTINT('id');
$rowid = GETPOSTINT('rowid');
$action = GETPOST('action', 'aZ09');
$search_lastname = GETPOST("search_lastname", 'alphanohtml');
$search_firstname = GETPOST("search_firstname", 'alphanohtml');
$search_email = GETPOST("search_email", 'alphanohtml');
$search_other = GETPOST("search_other", 'alphanohtml');
$search_dest_status = GETPOST('search_dest_status', 'int');

// Search modules dirs
$modulesdir = dolGetModulesDirs('/mailings');

$object = new Mailing($db);
$result = $object->fetch($id);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('ciblescard', 'globalcard'));

$sqlmessage = '';

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

$permissiontoread = $user->hasRight('maling', 'lire');
$permissiontocreate = $user->hasRight('mailing', 'creer');
$permissiontovalidatesend = $user->hasRight('mailing', 'valider');
$permissiontodelete = $user->hasRight('mailing', 'supprimer');


/*
 * Actions
 */

if ($action == 'add' && $permissiontocreate) {		// Add recipients
	$module = GETPOST("module", 'alpha');
	$result = -1;

	foreach ($modulesdir as $dir) {
		// Load modules attributes in arrays (name, numero, orders) from dir directory
		//print $dir."\n<br>";
		dol_syslog("Scan directory ".$dir." for modules");

		// Loading Class
		$file = $dir."/".$module.".modules.php";
		$classname = "mailing_".$module;

		if (file_exists($file)) {
			include_once $file;

			// Add targets into database
			dol_syslog("Call add_to_target() on class ".$classname." evenunsubscribe=".$object->evenunsubscribe);

			if (class_exists($classname)) {
				$obj = new $classname($db);
				$obj->evenunsubscribe = $object->evenunsubscribe;

				$result = $obj->add_to_target($id);

				$sqlmessage = $obj->sql;
			} else {
				$result = -1;
				break;
			}
		}
	}
	if ($result > 0) {
		// If status of emailing is sent completely, change to to send partially
		if ($object->status == $object::STATUS_SENTCOMPLETELY) {
			$object->setStatut($object::STATUS_SENTPARTIALY);
		}

		setEventMessages($langs->trans("XTargetsAdded", $result), null, 'mesgs');
		$action = '';
	}
	if ($result == 0) {
		setEventMessages($langs->trans("WarningNoEMailsAdded"), null, 'warnings');
	}
	if ($result < 0) {
		setEventMessages($langs->trans("Error").($obj->error ? ' '.$obj->error : ''), null, 'errors');
	}
}

if (GETPOSTINT('clearlist') && $permissiontocreate) {
	// Loading Class
	$obj = new MailingTargets($db);
	$obj->clear_target($id);
	/* Avoid this to allow reposition
	header("Location: ".$_SERVER['PHP_SELF']."?id=".$id);
	exit;
	*/
}

if (GETPOSTINT('exportcsv') && $permissiontoread) {
	$completefilename = 'targets_emailing'.$object->id.'_'.dol_print_date(dol_now(), 'dayhourlog').'.csv';
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename='.$completefilename);

	// List of selected targets
	$sql  = "SELECT mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut as status, mc.date_envoi, mc.tms,";
	$sql .= " mc.source_id, mc.source_type, mc.error_text";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " WHERE mc.fk_mailing = ".((int) $object->id);
	$sql .= $db->order($sortfield, $sortorder);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		$sep = ',';

		while ($obj = $db->fetch_object($resql)) {
			print $obj->rowid.$sep;
			print '"'.$obj->lastname.'"'.$sep;
			print '"'.$obj->firstname.'"'.$sep;
			print $obj->email.$sep;
			print $obj->other.$sep;
			print $obj->tms.$sep;
			print $obj->source_type.$sep;
			print $obj->source_id.$sep;
			print $obj->date_envoi.$sep;
			print $obj->status.$sep;
			print '"'.$obj->error_text.'"'.$sep;
			print "\n";
		}

		exit;
	} else {
		dol_print_error($db);
	}
	exit;
}

if ($action == 'delete' && $permissiontocreate) {
	// Ici, rowid indique le destinataire et id le mailing
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid = ".((int) $rowid);
	$resql = $db->query($sql);
	if ($resql) {
		if (!empty($id)) {
			$obj = new MailingTargets($db);
			$obj->update_nb($id);

			setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		} else {
			header("Location: list.php");
			exit;
		}
	} else {
		dol_print_error($db);
	}
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_lastname = '';
	$search_firstname = '';
	$search_email = '';
	$search_other = '';
	$search_dest_status = '';
}

// Action update description of emailing
if (($action == 'settitle' || $action == 'setemail_from' || $action == 'setreplyto' || $action == 'setemail_errorsto' || $action == 'setevenunsubscribe') && $permissiontocreate) {
	$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

	if ($action == 'settitle') {					// Test on permission already done
		$object->title = trim(GETPOST('title', 'alpha'));
	} elseif ($action == 'setemail_from') {			// Test on permission already done
		$object->email_from = trim(GETPOST('email_from', 'alphawithlgt')); // Must allow 'name <email>'
	} elseif ($action == 'setemail_replyto') {		// Test on permission already done
		$object->email_replyto = trim(GETPOST('email_replyto', 'alphawithlgt')); // Must allow 'name <email>'
	} elseif ($action == 'setemail_errorsto') {		// Test on permission already done
		$object->email_errorsto = trim(GETPOST('email_errorsto', 'alphawithlgt')); // Must allow 'name <email>'
	} elseif ($action == 'settitle' && empty($object->title)) {		// Test on permission already done
		$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTitle"));
	} elseif ($action == 'setfrom' && empty($object->email_from)) {	// Test on permission already done
		$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailFrom"));
	} elseif ($action == 'setevenunsubscribe') {	// Test on permission already done
		$object->evenunsubscribe = (GETPOST('evenunsubscribe') ? 1 : 0);
	}

	if (!$mesg) {
		$result = $object->update($user);
		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
		$mesg = $object->error;
	}

	setEventMessages($mesg, $mesgs, 'errors');
	$action = "";
}


/*
 * View
 */

llxHeader('', $langs->trans("Mailing"), 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing');

$form = new Form($db);
$formmailing = new FormMailing($db);

if ($object->fetch($id) >= 0) {
	$head = emailing_prepare_head($object);

	print dol_get_fiche_head($head, 'targets', $langs->trans("Mailing"), -1, 'email');

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

	print '<table class="border centpercent tableforfield">';

	// From
	print '<tr><td class="titlefield">'.$langs->trans("MailFrom").'</td><td>';
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
	//print dol_print_email($object->email_from, 0, 0, 0, 0, 1);
	//var_dump($object->email_from);
	print '</td></tr>';

	// Errors to
	if ($object->messtype != 'sms') {
		print '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>';
		$emailarray = CMailFile::getArrayAddress($object->email_errorsto);
		foreach ($emailarray as $email => $name) {
			if ($name != $email) {
				print dol_escape_htmltag($name).' &lt;'.$email;
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
		print $form->editfieldkey("MailReply", 'email_replyto', $object->email_replyto, $object, $user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY, 'string');
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
		$text = '';
		if ((getDolGlobalString('MAILING_LIMIT_SENDBYWEB') && getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') < $nbemail) && ($object->status == 1 || ($object->status == 2 && $nbtry < $nbemail))) {
			if (getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') > 0) {
				$text .= $langs->trans('LimitSendingEmailing', getDolGlobalString('MAILING_LIMIT_SENDBYWEB'));
			} else {
				$text .= $langs->trans('SendingFromWebInterfaceIsNotAllowed');
			}
		}
		if (empty($nbemail)) {
			$nbemail .= ' '.img_warning($langs->trans('ToAddRecipientsChooseHere'));//.' <span class="warning">'.$langs->trans("NoTargetYet").'</span>';
		}
		if ($text) {
			// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
			print $form->textwithpicto($nbemail, $text, 1, 'warning');
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


	$newcardbutton = '';
	$allowaddtarget = ($object->status == $object::STATUS_DRAFT);
	if (GETPOST('allowaddtarget')) {
		$allowaddtarget = 1;
	}
	if (!$allowaddtarget) {
		$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', $_SERVER["PHP_SELF"].'?id='.$object->id.'&allowaddtarget=1', '', $user->hasRight('mailing', 'creer'));
	}

	// Show email selectors
	if ($allowaddtarget && $user->hasRight('mailing', 'creer')) {
		print load_fiche_titre($langs->trans("ToAddRecipientsChooseHere"), ($user->admin ? info_admin($langs->trans("YouCanAddYourOwnPredefindedListHere"), 1) : ''), 'generic');

		print '<div class="div-table-responsive">';
		print '<div class="tagtable centpercentwithout1imp liste_titre_bydiv borderbottom" id="tablelines">';

		print '<div class="tagtr liste_titre">';
		print '<div class="tagtd"></div>';
		print '<div class="tagtd">'.$langs->trans("RecipientSelectionModules").'</div>';
		print '<div class="tagtd center maxwidth150">';
		if ($object->messtype != 'sms') {
			print $langs->trans("NbOfUniqueEMails");
		} else {
			print $langs->trans("NbOfUniquePhones");
		}
		print '</div>';
		print '<div class="tagtd left"><div class="inline-block">'.$langs->trans("Filters").'</div>';
		if ($object->messtype != 'sms') {
			print ' &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp; <div class=" inline-block">'.$langs->trans("EvenUnsubscribe").' ';
			print ajax_object_onoff($object, 'evenunsubscribe', 'evenunsubscribe', 'EvenUnsubscribe:switch_on:warning', 'EvenUnsubscribe', array(), 'small valignmiddle', '', 1);
			print '</div>';
		}
		print '</div>';
		print '<div class="tagtd">&nbsp;</div>';
		print '</div>';	// End tr

		clearstatcache();

		foreach ($modulesdir as $dir) {
			$modulenames = array();

			// Load modules attributes in arrays (name, numero, orders) from dir directory
			//print $dir."\n<br>";
			dol_syslog("Scan directory ".$dir." for modules");
			$handle = @opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (substr($file, 0, 1) != '.' && substr($file, 0, 3) != 'CVS') {
						$reg = array();
						if (preg_match("/(.*)\.modules\.php$/i", $file, $reg)) {
							if ($reg[1] == 'example') {
								continue;
							}
							$modulenames[] = $reg[1];
						}
					}
				}
				closedir($handle);
			}

			// Sort $modulenames
			sort($modulenames);

			$var = true;

			// Loop on each submodule
			foreach ($modulenames as $modulename) {
				// Loading Class
				$file = $dir.$modulename.".modules.php";
				$classname = "mailing_".$modulename;
				require_once $file;

				$obj = new $classname($db);

				// Check if qualified
				$qualified = (is_null($obj->enabled) ? 1 : (int) dol_eval($obj->enabled, 1));

				// Check dependencies
				foreach ($obj->require_module as $key) {
					if (empty($conf->$key->enabled) || (empty($user->admin) && $obj->require_admin)) {
						$qualified = 0;
						//print "Les prerequis d'activation du module mailing ne sont pas respectes. Il ne sera pas actif";
						break;
					}
				}

				// If module is qualified
				if ($qualified) {
					$var = !$var;

					if ($allowaddtarget) {
						print '<form '.$bctag[$var].' name="'.$modulename.'" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&module='.$modulename.'" method="POST" enctype="multipart/form-data">';
						print '<input type="hidden" name="token" value="'.newToken().'">';
						print '<input type="hidden" name="action" value="add">';
						print '<input type="hidden" name="page_y" value="'.newToken().'">';
					} else {
						print '<div '.$bctag[$var].'>';
					}

					print '<div class="tagtd paddingleftimp marginleftonly paddingrightimp marginrightonly valignmiddle center">';
					if (empty($obj->picto)) {
						$obj->picto = 'generic';
					}
					print img_object($langs->trans("EmailingTargetSelector").': '.get_class($obj), $obj->picto, 'class="valignmiddle width25 size15x"');
					print '</div>';
					print '<div class="tagtd valignmiddle">';	//  style="height: 4em"
					print $obj->getDesc();
					print '</div>';

					try {
						$obj->evenunsubscribe = $object->evenunsubscribe;	// Set flag to include/exclude email that has opt-out.

						$nbofrecipient = $obj->getNbOfRecipients('');
					} catch (Exception $e) {
						dol_syslog($e->getMessage(), LOG_ERR);
					}

					print '<div class="tagtd center valignmiddle">';
					if ($nbofrecipient === '' || $nbofrecipient >= 0) {
						print $nbofrecipient;
					} else {
						print $langs->trans("Error").' '.img_error($obj->error);
					}
					print '</div>';

					print '<div class="tagtd left valignmiddle">';
					if ($allowaddtarget) {
						try {
							$filter = $obj->formFilter();
						} catch (Exception $e) {
							dol_syslog($e->getMessage(), LOG_ERR);
						}
						if ($filter) {
							print $filter;
						} else {
							print $langs->trans("None");
						}
					}
					print '</div>';

					print '<div class="tagtd right valignmiddle">';
					if ($allowaddtarget) {
						print '<input type="submit" class="button button-add small reposition" name="button_'.$modulename.'" value="'.$langs->trans("Add").'">';
					} else {
						print '<input type="submit" class="button small disabled" disabled="disabled" name="button_'.$modulename.'" value="'.$langs->trans("Add").'">';
						//print $langs->trans("MailNoChangePossible");
						print "&nbsp;";
					}
					print '</div>';

					if ($allowaddtarget) {
						print '</form>';
					} else {
						print '</div>';
					}
				}
			}
		}	// End foreach dir

		$parameters = array();
		$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;

		print '</div>';	// End table
		print '</div>';

		print '<br>';

		if ($sqlmessage && $user->admin) {
			print info_admin($langs->trans("SQLUsedForExport").':<br> '.$sqlmessage, 0, 0, 1, '', 'TechnicalInformation');
			print '<br>';
		}

		print '<br>';
	}

	// List of selected targets
	$sql  = "SELECT mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut as status, mc.date_envoi, mc.tms,";
	$sql .= " mc.source_url, mc.source_id, mc.source_type, mc.error_text,";
	$sql .= " COUNT(mu.rowid) as nb";
	$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."mailing_unsubscribe as mu ON mu.email = mc.email";
	$sql .= " WHERE mc.fk_mailing=".((int) $object->id);
	$asearchcriteriahasbeenset = 0;
	if ($search_lastname) {
		$sql .= natural_search("mc.lastname", $search_lastname);
		$asearchcriteriahasbeenset++;
	}
	if ($search_firstname) {
		$sql .= natural_search("mc.firstname", $search_firstname);
		$asearchcriteriahasbeenset++;
	}
	if ($search_email) {
		$sql .= natural_search("mc.email", $search_email);
		$asearchcriteriahasbeenset++;
	}
	if ($search_other) {
		$sql .= natural_search("mc.other", $search_other);
		$asearchcriteriahasbeenset++;
	}
	if ($search_dest_status != '' && (int) $search_dest_status >= -1) {
		$sql .= " AND mc.statut = ".((int) $search_dest_status);
		$asearchcriteriahasbeenset++;
	}
	$sql .= ' GROUP BY mc.rowid, mc.lastname, mc.firstname, mc.email, mc.other, mc.statut, mc.date_envoi, mc.tms, mc.source_url, mc.source_id, mc.source_type, mc.error_text';
	$sql .= $db->order($sortfield, $sortorder);


	// Count total nb of records
	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
			$page = 0;
			$offset = 0;
		}

		// Fix/update nbemail on emailing record if it differs (may happen if user edit lines from database directly)
		if (empty($asearchcriteriahasbeenset)) {
			if ($nbtotalofrecords != $object->nbemail) {
				dol_syslog("We found a difference in nb of record in target table and the property ->nbemail, we fix ->nbemail");
				//print "nbemail=".$object->nbemail." nbtotalofrecords=".$nbtotalofrecords;
				$resultrefresh = $object->refreshNbOfTargets();
				if ($resultrefresh < 0) {
					dol_print_error($db, $object->error, $object->errors);
				}
			}
		}
	}

	//$nbtotalofrecords=$object->nbemail;     // nbemail is a denormalized field storing nb of targets
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		$param = "&id=".$object->id;
		//if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.urlencode($contextpage);
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		if ($search_lastname) {
			$param .= "&search_lastname=".urlencode($search_lastname);
		}
		if ($search_firstname) {
			$param .= "&search_firstname=".urlencode($search_firstname);
		}
		if ($search_email) {
			$param .= "&search_email=".urlencode($search_email);
		}
		if ($search_other) {
			$param .= "&search_other=".urlencode($search_other);
		}

		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="page_y" value="">';

		$morehtmlcenter = '';
		if ($object->status == $object::STATUS_DRAFT) {
			$morehtmlcenter = '<span class="opacitymedium hideonsmartphone">'.$langs->trans("ToClearAllRecipientsClickHere").'</span> <a href="'.$_SERVER["PHP_SELF"].'?clearlist=1&id='.$object->id.'" class="button reposition smallpaddingimp">'.$langs->trans("TargetsReset").'</a>';
		}
		$morehtmlcenter .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=exportcsv&token='.newToken().'&exportcsv=1&id='.$object->id.'">'.img_picto('', 'download', 'class="pictofixedwidth"').$langs->trans("Download").'</a>';

		$massactionbutton = '';

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		print_barre_liste($langs->trans("MailSelectedRecipients"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $morehtmlcenter, $num, $nbtotalofrecords, 'generic', 0, $newcardbutton, '', $limit, 0, 0, 1);

		print '</form>';

		print "\n<!-- Liste destinataires selectionnes -->\n";
		print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
		print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
		print '<input type="hidden" name="page" value="'.$page.'">';
		print '<input type="hidden" name="id" value="'.$object->id.'">';
		print '<input type="hidden" name="limit" value="'.$limit.'">';
		print '<input type="hidden" name="page_y" value="">';

		print '<div class="div-table-responsive">';
		print '<table class="noborder centpercent">';

		// Ligne des champs de filtres
		print '<tr class="liste_titre_filter">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre maxwidthsearch">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			print $searchpicto;
			print '</td>';
		}
		// EMail
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth75" type="text" name="search_email" value="'.dol_escape_htmltag($search_email).'">';
		print '</td>';
		// Name
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_lastname" value="'.dol_escape_htmltag($search_lastname).'">';
		print '</td>';
		// Firstname
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth50" type="text" name="search_firstname" value="'.dol_escape_htmltag($search_firstname).'">';
		print '</td>';
		// Other
		print '<td class="liste_titre">';
		print '<input class="flat maxwidth100" type="text" name="search_other" value="'.dol_escape_htmltag($search_other).'">';
		print '</td>';
		// Source
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';

		// Date last update
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';

		// Date sending
		print '<td class="liste_titre">';
		print '&nbsp;';
		print '</td>';

		// Status
		print '<td class="liste_titre center parentonrightofpage">';
		print $formmailing->selectDestinariesStatus($search_dest_status, 'search_dest_status', 1, 'width100 onrightofpage');
		print '</td>';

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="liste_titre maxwidthsearch">';
			$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
			print $searchpicto;
			print '</td>';
		}

		print '</tr>';

		if ($page) {
			$param .= "&page=".urlencode((string) ($page));
		}

		print '<tr class="liste_titre">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		print_liste_field_titre("EMail", $_SERVER["PHP_SELF"], "mc.email", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("Lastname", $_SERVER["PHP_SELF"], "mc.lastname", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("Firstname", $_SERVER["PHP_SELF"], "mc.firstname", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("OtherInformations", $_SERVER["PHP_SELF"], "", $param, "", "", $sortfield, $sortorder);
		print_liste_field_titre("Source", $_SERVER["PHP_SELF"], "", $param, "", '', $sortfield, $sortorder, 'center ');
		// Date last update
		print_liste_field_titre("DateLastModification", $_SERVER["PHP_SELF"], "mc.tms", $param, "", '', $sortfield, $sortorder, 'center ');
		// Date sending
		print_liste_field_titre("DateSending", $_SERVER["PHP_SELF"], "mc.date_envoi", $param, '', '', $sortfield, $sortorder, 'center ');
		print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "mc.statut", $param, '', '', $sortfield, $sortorder, 'center ');
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print_liste_field_titre('', $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'maxwidthsearch ');
		}
		print '</tr>';

		$i = 0;

		if ($num) {
			include_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
			include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
			include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
			include_once DOL_DOCUMENT_ROOT.'/eventorganization/class/conferenceorboothattendee.class.php';

			$objectstaticmember = new Adherent($db);
			$objectstaticuser = new User($db);
			$objectstaticcompany = new Societe($db);
			$objectstaticcontact = new Contact($db);
			$objectstaticeventorganization = new ConferenceOrBoothAttendee($db);

			while ($i < min($num, $limit)) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center">';
					print '<!-- ID mailing_cibles = '.$obj->rowid.' -->';
					if ($obj->status == $object::STATUS_DRAFT) {	// Not sent yet
						if ($user->hasRight('mailing', 'creer')) {
							print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&rowid='.((int) $obj->rowid).$param.'">'.img_delete($langs->trans("RemoveRecipient")).'</a>';
						}
					}
					/*if ($obj->status == -1)	// Sent with error
					 {
					 print '<a href="'.$_SERVER['PHP_SELF'].'?action=retry&rowid='.$obj->rowid.$param.'">'.$langs->trans("Retry").'</a>';
					 }*/
					print '</td>';
				}

				print '<td class="tdoverflowmax150">';
				print img_picto($obj->email, 'email', 'class="paddingright"');
				if ($obj->nb > 0) {
					print img_warning($langs->trans("EmailOptedOut"), 'warning', 'pictofixedwidth');
				}
				print dol_escape_htmltag($obj->email);
				print '</td>';

				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->lastname).'">'.dol_escape_htmltag($obj->lastname).'</td>';

				print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->firstname).'">'.dol_escape_htmltag($obj->firstname).'</td>';

				print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($obj->other).'"><span class="small">'.dol_escape_htmltag($obj->other).'</small></td>';

				print '<td class="center tdoverflowmax150">';
				if (empty($obj->source_id) || empty($obj->source_type)) {
					print empty($obj->source_url) ? '' : $obj->source_url; // For backward compatibility
				} else {
					if ($obj->source_type == 'member') {
						$objectstaticmember->fetch($obj->source_id);
						print $objectstaticmember->getNomUrl(1);
					} elseif ($obj->source_type == 'user') {
						$objectstaticuser->fetch($obj->source_id);
						print $objectstaticuser->getNomUrl(1);
					} elseif ($obj->source_type == 'thirdparty') {
						$objectstaticcompany->fetch($obj->source_id);
						print $objectstaticcompany->getNomUrl(1);
					} elseif ($obj->source_type == 'contact') {
						$objectstaticcontact->fetch($obj->source_id);
						print $objectstaticcontact->getNomUrl(1);
					} elseif ($obj->source_type == 'eventorganizationattendee') {
						$objectstaticeventorganization->fetch($obj->source_id);
						print $objectstaticeventorganization->getNomUrl(1);
					} else {
						print $obj->source_url;
					}
				}
				print '</td>';

				// Date last update
				print '<td class="center nowraponall">';
				print dol_print_date(dol_stringtotime($obj->tms), 'dayhour');
				print '</td>';

				// Date sent
				print '<td class="center nowraponall">';
				if ($obj->status != $object::STATUS_DRAFT) {		// If status of target line is not draft
					// Date sent
					print $obj->date_envoi;		// @TODO Must store date in date format
				}
				print '</td>';

				// Status of recipient sending email (Warning != status of emailing)
				print '<td class="nowrap center">';
				if ($obj->status == $object::STATUS_DRAFT) {		// If status of target line is not draft
					print $object::libStatutDest((int) $obj->status, 2, '');
				} else {
					print $object::libStatutDest((int) $obj->status, 2, $obj->error_text);
				}
				print '</td>';

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center">';
					print '<!-- ID mailing_cibles = '.$obj->rowid.' -->';
					if ($obj->status == $object::STATUS_DRAFT) {	// If status of target line is not sent yet
						if ($user->hasRight('mailing', 'creer')) {
							print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&rowid='.((int) $obj->rowid).$param.'">'.img_delete($langs->trans("RemoveRecipient")).'</a>';
						}
					}
					/*if ($obj->status == -1)	// Sent with error
					{
						print '<a href="'.$_SERVER['PHP_SELF'].'?action=retry&rowid='.$obj->rowid.$param.'">'.$langs->trans("Retry").'</a>';
					}*/
					print '</td>';
				}
				print '</tr>';

				$i++;
			}
		} else {
			if ($object->status < $object::STATUS_SENTPARTIALY) {
				print '<tr><td colspan="9">';
				print '<span class="opacitymedium">'.$langs->trans("NoTargetYet").'</span>';
				print '</td></tr>';
			} else {
				print '<tr><td colspan="9">';
				print '<span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span>';
				print '</td></tr>';
			}
		}
		print "</table><br>";
		print '</div>';

		print '</form>';

		$db->free($resql);
	} else {
		dol_print_error($db);
	}

	print "\n<!-- Fin liste destinataires selectionnes -->\n";
}

// End of page
llxFooter();
$db->close();
