<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2019	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2021		Waël Almoman            <info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/comm/mailing/card.php
 *       \ingroup    mailing
 *       \brief      Fiche mailing, onglet general
 */

if (!defined('NOSTYLECHECK')) {
	define('NOSTYLECHECK', '1');
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/emailing.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/class/mailing.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load translation files required by the page
$langs->loadLangs(array("mails", "admin"));

$id = (GETPOSTINT('mailid') ? GETPOSTINT('mailid') : GETPOSTINT('id'));

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$urlfrom = GETPOST('urlfrom');
$backtopageforcancel = GETPOST('backtopageforcancel');

// Initialize a technical objects
$object = new Mailing($db);
$extrafields = new ExtraFields($db);
$hookmanager->initHooks(array('mailingcard', 'globalcard'));

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// Array of possible substitutions (See also file mailing-send.php that should manage same substitutions)
$object->substitutionarray = FormMail::getAvailableSubstitKey('emailing');


// Set $object->substitutionarrayfortest
$signature = ((!empty($user->signature) && !getDolGlobalString('MAIN_MAIL_DO_NOT_USE_SIGN')) ? $user->signature : '');

$targetobject = null; // Not defined with mass emailing

$parameters = array('mode' => 'emailing');
$substitutionarray = FormMail::getAvailableSubstitKey('emailing', $targetobject);

$object->substitutionarrayfortest = $substitutionarray;

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

$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

//$permissiontoread = $user->hasRight('maling', 'read');
$permissiontocreate = $user->hasRight('mailing', 'creer');
$permissiontovalidatesend = $user->hasRight('mailing', 'valider');
$permissiontodelete = $user->hasRight('mailing', 'supprimer');


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = DOL_URL_ROOT.'/comm/mailing/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/comm/mailing/card.php?id='.((!empty($id) && $id > 0) ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		/*var_dump($cancel);var_dump($backtopage);var_dump($backtopageforcancel);exit;*/
		if (!empty($backtopageforcancel)) {
			header("Location: ".$backtopageforcancel);
			exit;
		} elseif (!empty($backtopage)) {
			header("Location: ".$backtopage);
			exit;
		}
		$action = '';
	}

	// Action clone object
	if ($action == 'confirm_clone' && $confirm == 'yes' && $permissiontocreate) {
		if (!GETPOST("clone_content", 'alpha') && !GETPOST("clone_receivers", 'alpha')) {
			setEventMessages($langs->trans("NoCloneOptionsSpecified"), null, 'errors');
		} else {
			$result = $object->createFromClone($user, $object->id, GETPOST("clone_content", 'alpha'), GETPOST("clone_receivers", 'alpha'));
			if ($result > 0) {
				header("Location: ".$_SERVER['PHP_SELF'].'?id='.$result);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		$action = '';
	}

	// Action send emailing for everybody
	if ($action == 'sendallconfirmed' && $confirm == 'yes' && $permissiontovalidatesend) {
		if (!getDolGlobalString('MAILING_LIMIT_SENDBYWEB')) {
			// As security measure, we don't allow send from the GUI
			setEventMessages($langs->trans("MailingNeedCommand"), null, 'warnings');
			setEventMessages('<textarea cols="70" rows="'.ROWS_2.'" wrap="soft">php ./scripts/emailings/mailing-send.php '.$object->id.'</textarea>', null, 'warnings');
			setEventMessages($langs->trans("MailingNeedCommand2"), null, 'warnings');
			$action = '';
		} elseif (getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') < 0) {
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'warnings');
			$action = '';
		} else {
			if ($object->status == 0) {
				dol_print_error(null, 'ErrorMailIsNotValidated');
				exit;
			}

			$id       = $object->id;
			$subject  = $object->sujet;
			$message  = $object->body;
			$from     = $object->email_from;
			$replyto = $object->email_replyto;
			$errorsto = $object->email_errorsto;
			// Is the message in html
			$msgishtml = -1; // Unknown by default
			if (preg_match('/[\s\t]*<html>/i', $message)) {
				$msgishtml = 1;
			}

			// Warning, we must not use begin-commit transaction here
			// because we want to save update for each mail sent.

			$nbok = 0;
			$nbko = 0;

			// We choose mails not already sent for this mailing (statut=0)
			// or sent in error (statut=-1)
			$sql = "SELECT mc.rowid, mc.fk_mailing, mc.lastname, mc.firstname, mc.email, mc.other, mc.source_url, mc.source_id, mc.source_type, mc.tag";
			$sql .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
			$sql .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".((int) $object->id);
			$sql .= " ORDER BY mc.statut DESC"; // first status 0, then status -1

			dol_syslog("card.php: select targets", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql); // Number of possible recipients

				if ($num) {
					dol_syslog("comm/mailing/card.php: nb of targets = ".$num, LOG_DEBUG);

					$now = dol_now();

					// Positioning date of start sending
					$sql = "UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi='".$db->idate($now)."' WHERE rowid=".((int) $object->id);
					$resql2 = $db->query($sql);
					if (!$resql2) {
						dol_print_error($db);
					}

					$thirdpartystatic = new Societe($db);
					// Loop on each email and send it
					$iforemailloop = 0;

					while ($iforemailloop < $num && $iforemailloop < $conf->global->MAILING_LIMIT_SENDBYWEB) {
						// Here code is common with same loop ino mailing-send.php
						$res = 1;
						$now = dol_now();

						$obj = $db->fetch_object($resql);

						// sendto en RFC2822
						$sendto = str_replace(',', ' ', dolGetFirstLastname($obj->firstname, $obj->lastname))." <".$obj->email.">";

						// Make substitutions on topic and body. From (AA=YY;BB=CC;...) we keep YY, CC, ...
						$other = explode(';', $obj->other);
						$tmpfield = explode('=', $other[0], 2);
						$other1 = (isset($tmpfield[1]) ? $tmpfield[1] : $tmpfield[0]);
						$tmpfield = explode('=', $other[1], 2);
						$other2 = (isset($tmpfield[1]) ? $tmpfield[1] : $tmpfield[0]);
						$tmpfield = explode('=', $other[2], 2);
						$other3 = (isset($tmpfield[1]) ? $tmpfield[1] : $tmpfield[0]);
						$tmpfield = explode('=', $other[3], 2);
						$other4 = (isset($tmpfield[1]) ? $tmpfield[1] : $tmpfield[0]);
						$tmpfield = explode('=', $other[4], 2);
						$other5 = (isset($tmpfield[1]) ? $tmpfield[1] : $tmpfield[0]);

						$signature = ((!empty($user->signature) && !getDolGlobalString('MAIN_MAIL_DO_NOT_USE_SIGN')) ? $user->signature : '');

						$parameters = array('mode' => 'emailing');
						$substitutionarray = getCommonSubstitutionArray($langs, 0, array('object', 'objectamount'), $targetobject); // Note: On mass emailing, this is null because be don't know object

						// Array of possible substitutions (See also file mailing-send.php that should manage same substitutions)
						$substitutionarray['__ID__'] = $obj->source_id;
						if ($obj->source_type == "thirdparty") {
							$result = $thirdpartystatic->fetch($obj->source_id);

							if ($result > 0) {
								$substitutionarray['__THIRDPARTY_CUSTOMER_CODE__'] = $thirdpartystatic->code_client;
							} else {
								$substitutionarray['__THIRDPARTY_CUSTOMER_CODE__'] = '';
							}
						}
						$substitutionarray['__EMAIL__'] = $obj->email;
						$substitutionarray['__LASTNAME__'] = $obj->lastname;
						$substitutionarray['__FIRSTNAME__'] = $obj->firstname;
						$substitutionarray['__MAILTOEMAIL__'] = '<a href="mailto:'.$obj->email.'">'.$obj->email.'</a>';
						$substitutionarray['__OTHER1__'] = $other1;
						$substitutionarray['__OTHER2__'] = $other2;
						$substitutionarray['__OTHER3__'] = $other3;
						$substitutionarray['__OTHER4__'] = $other4;
						$substitutionarray['__OTHER5__'] = $other5;
						$substitutionarray['__USER_SIGNATURE__'] = $signature; // Signature is empty when ran from command line or taken from user in parameter)
						$substitutionarray['__SENDEREMAIL_SIGNATURE__'] = $signature; // Signature is empty when ran from command line or taken from user in parameter)
						$substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.urlencode($obj->tag).'&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY').'-'.$obj->tag.'-'.$obj->email.'-'.$obj->rowid, "md5").'&email='.urlencode($obj->email).'&mtid='.((int) $obj->rowid).'" width="1" height="1" style="width:1px;height:1px" border="0"/>';
						$substitutionarray['__UNSUBSCRIBE__'] = '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag='.urlencode($obj->tag).'&unsuscrib=1&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY').'-'.$obj->tag.'-'.$obj->email.'-'.$obj->rowid, "md5").'&email='.urlencode($obj->email).'&mtid='.((int) $obj->rowid).'" target="_blank" rel="noopener noreferrer">'.$langs->trans("MailUnsubcribe").'</a>';
						$substitutionarray['__UNSUBSCRIBE_URL__'] = DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag='.urlencode($obj->tag).'&unsuscrib=1&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY').'-'.$obj->tag.'-'.$obj->email.'-'.$obj->rowid, "md5").'&email='.urlencode($obj->email).'&mtid='.((int) $obj->rowid);

						$onlinepaymentenabled = 0;
						if (isModEnabled('paypal')) {
							$onlinepaymentenabled++;
						}
						if (isModEnabled('paybox')) {
							$onlinepaymentenabled++;
						}
						if (isModEnabled('stripe')) {
							$onlinepaymentenabled++;
						}
						if ($onlinepaymentenabled && getDolGlobalString('PAYMENT_SECURITY_TOKEN')) {
							require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
							$substitutionarray['__ONLINEPAYMENTLINK_MEMBER__'] = getHtmlOnlinePaymentLink('member', $obj->source_id);
							$substitutionarray['__ONLINEPAYMENTLINK_DONATION__'] = getHtmlOnlinePaymentLink('donation', $obj->source_id);
							$substitutionarray['__ONLINEPAYMENTLINK_ORDER__'] = getHtmlOnlinePaymentLink('order', $obj->source_id);
							$substitutionarray['__ONLINEPAYMENTLINK_INVOICE__'] = getHtmlOnlinePaymentLink('invoice', $obj->source_id);
							$substitutionarray['__ONLINEPAYMENTLINK_CONTRACTLINE__'] = getHtmlOnlinePaymentLink('contractline', $obj->source_id);

							$substitutionarray['__SECUREKEYPAYMENT__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 2);
							if (!getDolGlobalString('PAYMENT_SECURITY_TOKEN_UNIQUE')) {
								$substitutionarray['__SECUREKEYPAYMENT_MEMBER__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 2);
								$substitutionarray['__SECUREKEYPAYMENT_DONATION__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 2);
								$substitutionarray['__SECUREKEYPAYMENT_ORDER__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 2);
								$substitutionarray['__SECUREKEYPAYMENT_INVOICE__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 2);
								$substitutionarray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN'), 2);
							} else {
								$substitutionarray['__SECUREKEYPAYMENT_MEMBER__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . 'member'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_DONATION__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . 'donation'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_ORDER__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . 'order'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_INVOICE__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . 'invoice'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = dol_hash(getDolGlobalString('PAYMENT_SECURITY_TOKEN') . 'contractline'.$obj->source_id, 2);
							}
						}
						if (getDolGlobalString('MEMBER_ENABLE_PUBLIC')) {
							$substitutionarray['__PUBLICLINK_NEWMEMBERFORM__'] = '<a target="_blank" rel="noopener noreferrer" href="'.DOL_MAIN_URL_ROOT.'/public/members/new.php'.((isModEnabled('multicompany')) ? '?entity='.$conf->entity : '').'">'.$langs->trans('BlankSubscriptionForm'). '</a>';
						}
						/* For backward compatibility, deprecated */
						if (isModEnabled('paypal') && getDolGlobalString('PAYPAL_SECURITY_TOKEN')) {
							$substitutionarray['__SECUREKEYPAYPAL__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN'), 2);

							if (!getDolGlobalString('PAYPAL_SECURITY_TOKEN_UNIQUE')) {
								$substitutionarray['__SECUREKEYPAYPAL_MEMBER__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN'), 2);
							} else {
								$substitutionarray['__SECUREKEYPAYPAL_MEMBER__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN') . 'membersubscription'.$obj->source_id, 2);
							}

							if (!getDolGlobalString('PAYPAL_SECURITY_TOKEN_UNIQUE')) {
								$substitutionarray['__SECUREKEYPAYPAL_ORDER__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN'), 2);
							} else {
								$substitutionarray['__SECUREKEYPAYPAL_ORDER__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN') . 'order'.$obj->source_id, 2);
							}

							if (!getDolGlobalString('PAYPAL_SECURITY_TOKEN_UNIQUE')) {
								$substitutionarray['__SECUREKEYPAYPAL_INVOICE__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN'), 2);
							} else {
								$substitutionarray['__SECUREKEYPAYPAL_INVOICE__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN') . 'invoice'.$obj->source_id, 2);
							}

							if (!getDolGlobalString('PAYPAL_SECURITY_TOKEN_UNIQUE')) {
								$substitutionarray['__SECUREKEYPAYPAL_CONTRACTLINE__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN'), 2);
							} else {
								$substitutionarray['__SECUREKEYPAYPAL_CONTRACTLINE__'] = dol_hash(getDolGlobalString('PAYPAL_SECURITY_TOKEN') . 'contractline'.$obj->source_id, 2);
							}
						}
						//$substitutionisok=true;

						complete_substitutions_array($substitutionarray, $langs);
						$newsubject = make_substitutions($subject, $substitutionarray);
						$newmessage = make_substitutions($message, $substitutionarray, null, 0);

						$moreinheader = '';
						if (preg_match('/__UNSUBSCRIBE_(_|URL_)/', $message)) {
							$moreinheader = "List-Unsubscribe: <__UNSUBSCRIBE_URL__>\n";
							$moreinheader = make_substitutions($moreinheader, $substitutionarray);
						}

						$arr_file = array();
						$arr_mime = array();
						$arr_name = array();
						$arr_css  = array();

						$listofpaths = dol_dir_list($upload_dir, 'all', 0, '', '', 'name', SORT_ASC, 0);
						if (count($listofpaths)) {
							foreach ($listofpaths as $key => $val) {
								$arr_file[] = $listofpaths[$key]['fullname'];
								$arr_mime[] = dol_mimetype($listofpaths[$key]['name']);
								$arr_name[] = $listofpaths[$key]['name'];
							}
						}

						// Mail making
						$trackid = 'emailing-'.$obj->fk_mailing.'-'.$obj->rowid;
						$upload_dir_tmp = $upload_dir;
						$mail = new CMailFile($newsubject, $sendto, $from, $newmessage, $arr_file, $arr_mime, $arr_name, '', '', 0, $msgishtml, $errorsto, $arr_css, $trackid, $moreinheader, 'emailing', $replyto, $upload_dir_tmp);

						if ($mail->error) {
							$res = 0;
						}
						/*if (! $substitutionisok)
						{
							$mail->error='Some substitution failed';
							$res=0;
						}*/

						// Send mail
						if ($res) {
							$res = $mail->sendfile();
						}

						if ($res) {
							// Mail successful
							$nbok++;

							dol_syslog("comm/mailing/card.php: ok for #".$iforemailloop.($mail->error ? ' - '.$mail->error : ''), LOG_DEBUG);

							$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
							$sql .= " SET statut=1, date_envoi = '".$db->idate($now)."' WHERE rowid=".((int) $obj->rowid);
							$resql2 = $db->query($sql);
							if (!$resql2) {
								dol_print_error($db);
							} else {
								//if check read is use then update prospect contact status
								if (strpos($message, '__CHECK_READ__') !== false) {
									//Update status communication of thirdparty prospect
									$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".((int) $obj->rowid).")";
									dol_syslog("card.php: set prospect thirdparty status", LOG_DEBUG);
									$resql2 = $db->query($sql);
									if (!$resql2) {
										dol_print_error($db);
									}

									//Update status communication of contact prospect
									$sql = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."socpeople AS sc INNER JOIN ".MAIN_DB_PREFIX."mailing_cibles AS mc ON mc.rowid=".((int) $obj->rowid)." AND mc.source_type = 'contact' AND mc.source_id = sc.rowid)";
									dol_syslog("card.php: set prospect contact status", LOG_DEBUG);

									$resql2 = $db->query($sql);
									if (!$resql2) {
										dol_print_error($db);
									}
								}
							}

							if (getDolGlobalString('MAILING_DELAY')) {
								dol_syslog("Wait a delay of MAILING_DELAY=".((float) $conf->global->MAILING_DELAY));
								usleep((int) ((float) $conf->global->MAILING_DELAY * 1000000));
							}

							//test if CHECK READ change statut prospect contact
						} else {
							// Mail failed
							$nbko++;

							dol_syslog("comm/mailing/card.php: error for #".$iforemailloop.($mail->error ? ' - '.$mail->error : ''), LOG_WARNING);

							$sql = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
							$sql .= " SET statut=-1, error_text='".substr($db->escape($mail->error), 0, 255)."', date_envoi='".$db->idate($now)."' WHERE rowid=".((int) $obj->rowid);
							$resql2 = $db->query($sql);
							if (!$resql2) {
								dol_print_error($db);
							}
						}

						$iforemailloop++;
					}
				} else {
					setEventMessages($langs->transnoentitiesnoconv("NoMoreRecipientToSendTo"), null, 'mesgs');
				}

				// Loop finished, set global statut of mail
				if ($nbko > 0) {
					$statut = 2; // Status 'sent partially' (because at least one error)
					setEventMessages($langs->transnoentitiesnoconv("EMailSentToNRecipients", $nbok), null, 'mesgs');
				} else {
					if ($nbok >= $num) {
						$statut = 3; // Send to everybody
					} else {
						$statut = 2; // Status 'sent partially' (because not send to everybody)
					}
					setEventMessages($langs->transnoentitiesnoconv("EMailSentToNRecipients", $nbok), null, 'mesgs');
				}

				$sql = "UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".((int) $statut)." WHERE rowid = ".((int) $object->id);
				dol_syslog("comm/mailing/card.php: update global status", LOG_DEBUG);
				$resql2 = $db->query($sql);
				if (!$resql2) {
					dol_print_error($db);
				}
			} else {
				dol_syslog($db->error());
				dol_print_error($db);
			}
			$object->fetch($id);
			$action = '';
		}
	}

	// Action send test emailing
	if ($action == 'send' && ! $cancel && $permissiontovalidatesend) {
		$error = 0;

		$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

		$object->sendto = GETPOST("sendto", 'alphawithlgt');
		if (!$object->sendto) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("MailTo")), null, 'errors');
			$error++;
		}

		if (!$error) {
			// Is the message in html
			$msgishtml = -1; // Unknown = autodetect by default
			if (preg_match('/[\s\t]*<html>/i', $object->body)) {
				$msgishtml = 1;
			}

			$signature = ((!empty($user->signature) && !getDolGlobalString('MAIN_MAIL_DO_NOT_USE_SIGN')) ? $user->signature : '');

			$parameters = array('mode' => 'emailing');
			$substitutionarray = getCommonSubstitutionArray($langs, 0, array('object', 'objectamount'), $targetobject); // Note: On mass emailing, this is null because be don't know object

			// other are set at begin of page
			$substitutionarray['__EMAIL__'] = $object->sendto;
			$substitutionarray['__MAILTOEMAIL__'] = '<a href="mailto:'.$object->sendto.'">'.$object->sendto.'</a>';
			$substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag=undefinedintestmode&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')."-undefinedintestmode-".$obj->sendto."-0", 'md5').'&email='.urlencode($obj->sendto).'&mtid=0" width="1" height="1" style="width:1px;height:1px" border="0"/>';
			$substitutionarray['__UNSUBSCRIBE__'] = '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag=undefinedintestmode&unsuscrib=1&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')."-undefinedintestmode-".$obj->sendto."-0", 'md5').'&email='.urlencode($obj->sendto).'&mtid=0" target="_blank" rel="noopener noreferrer">'.$langs->trans("MailUnsubcribe").'</a>';
			$substitutionarray['__UNSUBSCRIBE_URL__'] = DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag=undefinedintestmode&unsuscrib=1&securitykey='.dol_hash(getDolGlobalString('MAILING_EMAIL_UNSUBSCRIBE_KEY')."-undefinedintestmode-".$obj->sendto."-0", 'md5').'&email='.urlencode($obj->sendto).'&mtid=0';

			// Subject and message substitutions
			complete_substitutions_array($substitutionarray, $langs, $targetobject);

			$tmpsujet = make_substitutions($object->sujet, $substitutionarray);
			$tmpbody = make_substitutions($object->body, $substitutionarray);

			$arr_file = array();
			$arr_mime = array();
			$arr_name = array();
			$arr_css  = array();

			// Add CSS
			if (!empty($object->bgcolor)) {
				$arr_css['bgcolor'] = (preg_match('/^#/', $object->bgcolor) ? '' : '#').$object->bgcolor;
			}
			if (!empty($object->bgimage)) {
				$arr_css['bgimage'] = $object->bgimage;
			}

			// Attached files
			$listofpaths = dol_dir_list($upload_dir, 'all', 0, '', '', 'name', SORT_ASC, 0);
			if (count($listofpaths)) {
				foreach ($listofpaths as $key => $val) {
					$arr_file[] = $listofpaths[$key]['fullname'];
					$arr_mime[] = dol_mimetype($listofpaths[$key]['name']);
					$arr_name[] = $listofpaths[$key]['name'];
				}
			}

			$trackid = 'emailing-test';
			$upload_dir_tmp = $upload_dir;
			$mailfile = new CMailFile($tmpsujet, $object->sendto, $object->email_from, $tmpbody, $arr_file, $arr_mime, $arr_name, '', '', 0, $msgishtml, $object->email_errorsto, $arr_css, $trackid, '', 'emailing', '', $upload_dir_tmp);

			$result = $mailfile->sendfile();
			if ($result) {
				setEventMessages($langs->trans("MailSuccessfulySent", $mailfile->getValidAddress($object->email_from, 2), $mailfile->getValidAddress($object->sendto, 2)), null, 'mesgs');
				$action = '';
			} else {
				setEventMessages($langs->trans("ResultKo").'<br>'.$mailfile->error.' '.json_encode($result), null, 'errors');
				$action = 'test';
			}
		}
	}

	// Action add emailing
	if ($action == 'add' && $permissiontocreate) {
		$mesgs = array();

		$object->messtype       = (string) GETPOST("messtype");
		if ($object->messtype == 'sms') {
			$object->email_from     = (string) GETPOST("from_phone", 'alphawithlgt'); // Must allow 'name <email>'
		} else {
			$object->email_from     = (string) GETPOST("from", 'alphawithlgt'); // Must allow 'name <email>'
		}
		$object->email_replyto  = (string) GETPOST("replyto", 'alphawithlgt'); // Must allow 'name <email>'
		$object->email_errorsto = (string) GETPOST("errorsto", 'alphawithlgt'); // Must allow 'name <email>'
		$object->title          = (string) GETPOST("title");
		$object->sujet          = (string) GETPOST("sujet");
		$object->body           = (string) GETPOST("bodyemail", 'restricthtml');
		$object->bgcolor        = preg_replace('/^#/', '', (string) GETPOST("bgcolor"));
		$object->bgimage        = (string) GETPOST("bgimage");

		if (!$object->title) {
			$mesgs[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTitle"));
		}
		if ($object->messtype != 'sms' && !$object->sujet) {
			$mesgs[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTopic"));
		}
		if (!$object->body) {
			$mesgs[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailMessage"));
		}

		if (!count($mesgs)) {
			if ($object->create($user) >= 0) {
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			$mesgs[] = $object->error;
			$mesgs = array_merge($mesgs, $object->errors);
		}

		setEventMessages('', $mesgs, 'errors');
		$action = "create";
	}

	// Action update description of emailing
	if (($action == 'settitle' || $action == 'setemail_from' || $action == 'setemail_replyto' || $action == 'setreplyto' || $action == 'setemail_errorsto' || $action == 'setevenunsubscribe') && $permissiontovalidatesend) {
		$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

		if ($action == 'settitle') {
			$object->title = trim(GETPOST('title', 'alpha'));
		} elseif ($action == 'setemail_from') {
			$object->email_from = trim(GETPOST('email_from', 'alphawithlgt')); // Must allow 'name <email>'
		} elseif ($action == 'setemail_replyto') {
			$object->email_replyto = trim(GETPOST('email_replyto', 'alphawithlgt')); // Must allow 'name <email>'
		} elseif ($action == 'setemail_errorsto') {
			$object->email_errorsto = trim(GETPOST('email_errorsto', 'alphawithlgt')); // Must allow 'name <email>'
		} elseif ($action == 'settitle' && empty($object->title)) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTitle"));
		} elseif ($action == 'setfrom' && empty($object->email_from)) {
			$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailFrom"));
		} elseif ($action == 'setevenunsubscribe') {
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
	 * Action of adding a file in email form
	 */
	if (GETPOST('addfile') && $permissiontocreate) {
		$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Set tmp user directory
		dol_add_file_process($upload_dir, 0, 0, 'addedfile', '', null, '', 0);

		$action = "edit";
	}

	// Action of file remove
	if (GETPOST("removedfile") && $permissiontocreate) {
		$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		dol_remove_file_process(GETPOST('removedfile'), 0, 0); // We really delete file linked to mailing

		$action = "edit";
	}

	// Action of emailing update
	if ($action == 'update' && !GETPOST("removedfile") && !$cancel && $permissiontocreate) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$isupload = 0;

		if (!$isupload) {
			$mesgs = array();

			//$object->messtype       = (string) GETPOST("messtype");	// We must not be able to change the messtype
			$object->sujet          = (string) GETPOST("sujet");
			$object->body           = (string) GETPOST("bodyemail", 'restricthtml');
			$object->bgcolor        = preg_replace('/^#/', '', (string) GETPOST("bgcolor"));
			$object->bgimage        = (string) GETPOST("bgimage");

			if ($object->messtype != 'sms' && !$object->sujet) {
				$mesgs[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailTopic"));
			}
			if (!$object->body) {
				$mesgs[] = $langs->trans("ErrorFieldRequired", $langs->transnoentities("MailMessage"));
			}

			if (!count($mesgs)) {
				if ($object->update($user) >= 0) {
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				}
				$mesgs[] = $object->error;
				$mesgs = array_merge($mesgs, $object->errors);
			}

			setEventMessages('', $mesgs, 'errors');
			$action = "edit";
		} else {
			$action = "edit";
		}
	}

	// Action of validation confirmation
	if ($action == 'confirm_valid' && $confirm == 'yes' && $permissiontovalidatesend) {
		if ($object->id > 0) {
			$object->valid($user);
			setEventMessages($langs->trans("MailingSuccessfullyValidated"), null, 'mesgs');
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		} else {
			dol_print_error($db);
		}
	}

	// Action of validation confirmation
	if ($action == 'confirm_settodraft' && $confirm == 'yes' && $permissiontocreate) {
		if ($object->id > 0) {
			$result = $object->setStatut(0);
			if ($result > 0) {
				//setEventMessages($langs->trans("MailingSuccessfullyValidated"), null, 'mesgs');
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
			}
		} else {
			dol_print_error($db);
		}
	}

	// Resend
	if ($action == 'confirm_reset' && $confirm == 'yes' && $permissiontocreate) {
		if ($object->id > 0) {
			$db->begin();

			$result = $object->valid($user);
			if ($result > 0) {
				$result = $object->reset_targets_status($user);
			}

			if ($result > 0) {
				$db->commit();
				header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
			}
		} else {
			dol_print_error($db);
		}
	}

	// Action of delete confirmation
	if ($action == 'confirm_delete' && $confirm == 'yes' && $permissiontodelete) {
		if ($object->delete($user)) {
			$url = (!empty($urlfrom) ? $urlfrom : 'list.php');
			header("Location: ".$url);
			exit;
		}
	}

	if ($cancel) {
		$action = '';
	}
}


/*
 * View
 */

$form = new Form($db);
$htmlother = new FormOther($db);

$help_url = 'EN:Module_EMailing|FR:Module_Mailing|ES:M&oacute;dulo_Mailing';
llxHeader(
	'',
	$langs->trans("Mailing"),
	$help_url,
	'',
	0,
	0,
	array(
		'/includes/ace/src/ace.js',
		'/includes/ace/src/ext-statusbar.js',
		'/includes/ace/src/ext-language_tools.js',
		//'/includes/ace/src/ext-chromevox.js'
	),
	array()
);


if ($action == 'create') {
	// EMailing in creation mode
	print '<form name="new_mailing" action="'.$_SERVER['PHP_SELF'].'" method="POST">'."\n";
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';

	$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br><br><span class="small">';
	foreach ($object->substitutionarray as $key => $val) {
		$htmltext .= $key.' = '.$langs->trans($val).'<br>';
	}
	$htmltext .= '</span></i>';


	$availablelink = $form->textwithpicto('<span class="opacitymedium">'.$langs->trans("AvailableVariables").'</span>', $htmltext, 1, 'helpclickable', '', 0, 2, 'availvar');
	//print '<a href="javascript:document_preview(\''.DOL_URL_ROOT.'/admin/modulehelp.php?id='.$objMod->numero.'\',\'text/html\',\''.dol_escape_js($langs->trans("Module")).'\')">'.img_picto($langs->trans("ClickToShowDescription"), $imginfo).'</a>';


	// Print mail form
	print load_fiche_titre($langs->trans("NewMailing"), $availablelink, 'object_email');

	print dol_get_fiche_head(array(), '', '', -3);

	print '<table class="border centpercent">';

	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("MailTitle").'</td><td><input class="flat minwidth300" name="title" value="'.dol_escape_htmltag(GETPOST('title')).'" autofocus="autofocus"></td></tr>';

	if (getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
		$arrayoftypes = array("email" => "Email", "sms" => "SMS");
		print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Type").'</td><td>';
		print $form->selectarray('messtype', $arrayoftypes, (GETPOSTISSET('messtype') ? GETPOST('messtype') : 'email'), 0, 0);

		print '<script>
		$( document ).ready(function() {
			jQuery("#messtype").on("change", function() {
				console.log("We change the message ttpe");
				if (jQuery("#messtype").val() == "email") {
					jQuery(".fieldsforsms").hide();
					jQuery(".fieldsforemail").show();
				}
				if (jQuery("#messtype").val() == "sms") {
					jQuery(".fieldsforsms").show();
					jQuery(".fieldsforemail").hide();
				}
			});
			jQuery("#messtype").change();
		})
		</script>';

		print '</td></tr>';
	}
	print '</table>';

	print '<br><br>';

	print '<table class="border centpercent">';

	print '<tr class="fieldsforemail"><td class="fieldrequired titlefieldcreate">'.$langs->trans("MailFrom").'</td><td><input class="flat minwidth200" name="from" value="'.(GETPOSTISSET('from') ? GETPOST('from') : getDolGlobalString('MAILING_EMAIL_FROM')).'"></td></tr>';

	print '<tr class="fieldsforsms hidden"><td class="fieldrequired titlefieldcreate">'.$langs->trans("PhoneFrom").'</td><td><input class="flat minwidth200" name="fromphone" value="'.(GETPOSTISSET('fromphone') ? GETPOST('fromphone') : getDolGlobalString('MAILING_SMS_FROM')).'" placeholder="+123..."></td></tr>';

	print '<tr class="fieldsforemail"><td>'.$langs->trans("MailErrorsTo").'</td><td><input class="flat minwidth200" name="errorsto" value="'.getDolGlobalString('MAILING_EMAIL_ERRORSTO', getDolGlobalString('MAIN_MAIL_ERRORS_TO')).'"></td></tr>';

	// Other attributes
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook)) {
		print $object->showOptionals($extrafields, 'create');
	}

	print '</table>';
	print '<br><br>';

	print '<table class="border centpercent">';
	print '<tr class="fieldsforemail"><td class="fieldrequired titlefieldcreate">'.$langs->trans("MailTopic").'</td><td><input id="sujet" class="flat minwidth200 quatrevingtpercent" name="sujet" value="'.dol_escape_htmltag(GETPOST('sujet', 'alphanohtml')).'"></td></tr>';
	print '<tr class="fieldsforemail"><td>'.$langs->trans("BackgroundColorByDefault").'</td><td colspan="3">';
	print $htmlother->selectColor(GETPOST('bgcolor'), 'bgcolor', '', 0);
	print '</td></tr>';

	$formmail = new FormMail($db);
	$formmail->withfckeditor = 1;
	$formmail->withlayout = 1;
	$formmail->withaiprompt = 'html';

	print '<tr class="fieldsforemail"><td></td><td class="tdtop">';

	$out = '';
	$showlinktolayout = $formmail->withlayout && $formmail->withfckeditor;
	$showlinktolayoutlabel = $langs->trans("FillMessageWithALayout");
	$showlinktoai = ($formmail->withaiprompt && isModEnabled('ai')) ? 'textgenerationemail' : '';
	$showlinktoailabel = $langs->trans("FillMessageWithAIContent");
	$formatforouput = 'html';
	$htmlname = 'bodyemail';

	// Fill $out
	include DOL_DOCUMENT_ROOT.'/core/tpl/formlayoutai.tpl.php';

	print $out;

	print '</td></tr>';
	print '</table>';

	print '<div style="padding-top: 10px">';
	// wysiwyg editor
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('bodyemail', GETPOST('bodyemail', 'restricthtmlallowunvalid'), '', 600, 'dolibarr_mailings', '', true, true, getDolGlobalInt('FCKEDITOR_ENABLE_MAILING'), 20, '90%');
	$doleditor->Create();
	print '</div>';

	print dol_get_fiche_end();

	print $form->buttonsSaveCancel("Create", 'Cancel');

	print '</form>';
} else {
	if ($object->id > 0) {
		$upload_dir = $conf->mailing->dir_output."/".get_exdir($object->id, 2, 0, 1, $object, 'mailing');

		$head = emailing_prepare_head($object);

		if ($action == 'settodraft') {
			// Confirmation back to draft
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("SetToDraft"), $langs->trans("ConfirmUnvalidateEmailing"), "confirm_settodraft", '', '', 1);
		} elseif ($action == 'valid') {
			// Confirmation of mailing validation
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("ValidMailing"), $langs->trans("ConfirmValidMailing"), "confirm_valid", '', '', 1);
		} elseif ($action == 'reset') {
			// Confirm reset
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id, $langs->trans("ResetMailing"), $langs->trans("ConfirmResetMailing", $object->ref), "confirm_reset", '', '', 2);
		} elseif ($action == 'delete') {
			// Confirm delete
			print $form->formconfirm($_SERVER["PHP_SELF"]."?id=".$object->id.(!empty($urlfrom) ? '&urlfrom='.urlencode($urlfrom) : ''), $langs->trans("DeleteMailing"), $langs->trans("ConfirmDeleteMailing"), "confirm_delete", '', '', 1);
		}

		if ($action != 'edit' && $action != 'edittxt' && $action != 'edithtml') {
			print dol_get_fiche_head($head, 'card', $langs->trans("Mailing"), -1, 'email');

			/*
			 * View mode mailing
			 */
			if ($action == 'sendall') {
				// Define message to recommend from command line
				$sendingmode = getDolGlobalString('EMAILING_MAIL_SENDMODE');
				if (empty($sendingmode)) {
					$sendingmode = getDolGlobalString('MAIN_MAIL_SENDMODE');
				}
				if (empty($sendingmode)) {
					$sendingmode = 'mail'; // If not defined, we use php mail function
				}

				// MAILING_NO_USING_PHPMAIL may be defined or not.
				// MAILING_LIMIT_SENDBYWEB is always defined to something != 0 (-1=forbidden).
				// MAILING_LIMIT_SENDBYCLI may be defined or not (-1=forbidden, 0 or undefined=no limit).
				// MAILING_LIMIT_SENDBYDAY may be defined or not (0 or undefined=no limit).
				if (getDolGlobalString('MAILING_NO_USING_PHPMAIL') && $sendingmode == 'mail') {
					// EMailing feature may be a spam problem, so when you host several users/instance, having this option may force each user to use their own SMTP agent.
					// You ensure that every user is using its own SMTP server when using the mass emailing module.
					$linktoadminemailbefore = '<a href="'.DOL_URL_ROOT.'/admin/mails_emailing.php">';
					$linktoadminemailend = '</a>';
					setEventMessages($langs->trans("MailSendSetupIs", $listofmethods[$sendingmode]), null, 'warnings');
					$messagetoshow = $langs->trans("MailSendSetupIs2", '{s1}', '{s2}', '{s3}', '{s4}');
					$messagetoshow = str_replace('{s1}', $linktoadminemailbefore, $messagetoshow);
					$messagetoshow = str_replace('{s2}', $linktoadminemailend, $messagetoshow);
					$messagetoshow = str_replace('{s3}', $langs->transnoentitiesnoconv("MAIN_MAIL_SENDMODE"), $messagetoshow);
					$messagetoshow = str_replace('{s4}', $listofmethods['smtps'], $messagetoshow);
					setEventMessages($messagetoshow, null, 'warnings');

					if (getDolGlobalString('MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS')) {
						setEventMessages($langs->trans("MailSendSetupIs3", getDolGlobalString('MAILING_SMTP_SETUP_EMAILS_FOR_QUESTIONS')), null, 'warnings');
					}
					$action = '';
				} elseif (getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') < 0) {
					if (getDolGlobalString('MAILING_LIMIT_WARNING_PHPMAIL') && $sendingmode == 'mail') {
						setEventMessages($langs->transnoentitiesnoconv($conf->global->MAILING_LIMIT_WARNING_PHPMAIL), null, 'warnings');
					}
					if (getDolGlobalString('MAILING_LIMIT_WARNING_NOPHPMAIL') && $sendingmode != 'mail') {
						setEventMessages($langs->transnoentitiesnoconv($conf->global->MAILING_LIMIT_WARNING_NOPHPMAIL), null, 'warnings');
					}

					// The feature is forbidden from GUI, we show just message to use from command line.
					setEventMessages($langs->trans("MailingNeedCommand"), null, 'warnings');
					setEventMessages('<textarea cols="60" rows="'.ROWS_1.'" wrap="soft">php ./scripts/emailings/mailing-send.php '.$object->id.'</textarea>', null, 'warnings');
					if ($conf->file->mailing_limit_sendbyweb != '-1') {  // MAILING_LIMIT_SENDBYWEB was set to -1 in database, but it is allowed to increase it.
						setEventMessages($langs->trans("MailingNeedCommand2"), null, 'warnings'); // You can send online with constant...
					}
					$action = '';
				} else {
					if (getDolGlobalString('MAILING_LIMIT_WARNING_PHPMAIL') && $sendingmode == 'mail') {
						setEventMessages($langs->transnoentitiesnoconv($conf->global->MAILING_LIMIT_WARNING_PHPMAIL), null, 'warnings');
					}
					if (getDolGlobalString('MAILING_LIMIT_WARNING_NOPHPMAIL') && $sendingmode != 'mail') {
						setEventMessages($langs->transnoentitiesnoconv($conf->global->MAILING_LIMIT_WARNING_NOPHPMAIL), null, 'warnings');
					}

					$text = '';

					if (getDolGlobalInt('MAILING_LIMIT_SENDBYDAY') > 0) {
						$text .= $langs->trans('WarningLimitSendByDay', getDolGlobalInt('MAILING_LIMIT_SENDBYDAY'));
						$text .= '<br><br>';
					}
					$text .= $langs->trans('ConfirmSendingEmailing').'<br>';
					$text .= $langs->trans('LimitSendingEmailing', getDolGlobalString('MAILING_LIMIT_SENDBYWEB'));

					if (!isset($conf->global->MAILING_LIMIT_SENDBYCLI) || getDolGlobalInt('MAILING_LIMIT_SENDBYCLI') >= 0) {
						$text .= '<br><br>';
						$text .= '<u>'.$langs->trans("AdvancedAlternative").':</u> '.$langs->trans("MailingNeedCommand");
						$text .= '<br><textarea class="quatrevingtpercent" rows="'.ROWS_2.'" wrap="soft" disabled>php ./scripts/emailings/mailing-send.php '.$object->id.' '.$user->login.'</textarea>';
					}

					print $form->formconfirm($_SERVER['PHP_SELF'].'?id='.$object->id, $langs->trans('SendMailing'), $text, 'sendallconfirmed', '', '', 1, 380, 660, 0, $langs->trans("Confirm"), $langs->trans("Cancel"));
				}
			}

			$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Ref customer
			$morehtmlref .= $form->editfieldkey("", 'title', $object->title, $object, $user->hasRight('mailing', 'creer'), 'string', '', 0, 1);
			$morehtmlref .= $form->editfieldval("", 'title', $object->title, $object, $user->hasRight('mailing', 'creer'), 'string', '', null, null, '', 1);
			$morehtmlref .= '</div>';

			$morehtmlstatus = '';
			$nbtry = $nbok = 0;
			if ($object->status == 2 || $object->status == 3) {
				$nbtry = $object->countNbOfTargets('alreadysent');
				$nbko  = $object->countNbOfTargets('alreadysentko');

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
			print $form->editfieldkey("MailFrom", 'email_from', $object->email_from, $object, $user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY, 'string');
			print '</td><td>';
			print $form->editfieldval("MailFrom", 'email_from', $object->email_from, $object, $user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY, 'string');
			$email = CMailFile::getValidAddress($object->email_from, 2);
			if ($email && !isValidEmail($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadEMail", $email));
			} elseif ($email && !isValidMailDomain($email)) {
				$langs->load("errors");
				print img_warning($langs->trans("ErrorBadMXDomain", $email));
			}

			print '</td></tr>';

			// Errors to
			if ($object->messtype != 'sms') {
				print '<tr><td>';
				print $form->editfieldkey("MailErrorsTo", 'email_errorsto', $object->email_errorsto, $object, $user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY, 'string');
				print '</td><td>';
				print $form->editfieldval("MailErrorsTo", 'email_errorsto', $object->email_errorsto, $object, $user->hasRight('mailing', 'creer') && $object->status < $object::STATUS_SENTCOMPLETELY, 'string');
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
					$nbemail .= ' '.img_warning('').' <span class="warning">'.$langs->trans("NoTargetYet").'</span>';
				}
				if ($text) {
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


			// Clone confirmation
			if ($action == 'clone') {
				// Create an array for form
				$formquestion = array(
					'text' => $langs->trans("ConfirmClone"),
				0 => array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneContent"), 'value' => 1),
				1 => array('type' => 'checkbox', 'name' => 'clone_receivers', 'label' => $langs->trans("CloneReceivers"), 'value' => 0)
				);
				// Incomplete payment. On demande si motif = escompte ou autre
				print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneEMailing', $object->ref), 'confirm_clone', $formquestion, 'yes', 2, 240);
			}

			// Actions Buttons
			if (GETPOST('cancel', 'alpha') || $confirm == 'no' || $action == '' || in_array($action, array('settodraft', 'valid', 'delete', 'sendall', 'clone', 'test', 'editevenunsubscribe'))) {
				print "\n\n<div class=\"tabsAction\">\n";

				if (($object->status == 1) && ($user->hasRight('mailing', 'valider') || $object->user_validation_id == $user->id)) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=settodraft&token='.newToken().'&id='.$object->id.'">'.$langs->trans("SetToDraft").'</a>';
				}

				if (($object->status == 0 || $object->status == 1 || $object->status == 2) && $user->hasRight('mailing', 'creer')) {
					if (isModEnabled('fckeditor') && getDolGlobalString('FCKEDITOR_ENABLE_MAILING') && $object->messtype != 'sms') {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edit&token='.newToken().'&id='.$object->id.'">'.$langs->trans("Edit").'</a>';
					} else {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edittxt&token='.newToken().'&id='.$object->id.'">'.$langs->trans("EditWithTextEditor").'</a>';
					}

					if (!getDolGlobalInt('EMAILINGS_SUPPORT_ALSO_SMS')) {
						if (!empty($conf->use_javascript_ajax)) {
							print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=edithtml&token='.newToken().'&id='.$object->id.'">'.$langs->trans("EditHTMLSource").'</a>';
						}
					}
				}

				//print '<a class="butAction" href="card.php?action=test&amp;id='.$object->id.'">'.$langs->trans("PreviewMailing").'</a>';

				if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('mailing', 'mailing_advance', 'send')) {
					print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("TestMailing").'</a>';
				} else {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=test&token='.newToken().'&id='.$object->id.'">'.$langs->trans("TestMailing").'</a>';
				}

				if ($object->status == 0) {
					if ($object->nbemail <= 0) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NoTargetYet")).'">'.$langs->trans("Validate").'</a>';
					} elseif (!$user->hasRight('mailing', 'valider')) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("Validate").'</a>';
					} else {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=valid&amp;id='.$object->id.'">'.$langs->trans("Validate").'</a>';
					}
				}

				if (($object->status == 1 || $object->status == 2) && $object->nbemail > 0 && $user->hasRight('mailing', 'valider')) {
					if (getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') < 0) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("SendingFromWebInterfaceIsNotAllowed")).'">'.$langs->trans("SendMailing").'</a>';
					} elseif (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('mailing', 'mailing_advance', 'send')) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("SendMailing").'</a>';
					} else {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=sendall&amp;id='.$object->id.'">'.$langs->trans("SendMailing").'</a>';
					}
				}

				if ($user->hasRight('mailing', 'creer')) {
					print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=clone&amp;object=emailing&amp;id='.$object->id.'">'.$langs->trans("ToClone").'</a>';
				}

				if (($object->status == 2 || $object->status == 3) && $user->hasRight('mailing', 'valider')) {
					if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('mailing', 'mailing_advance', 'send')) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("ResetMailing").'</a>';
					} else {
						print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?action=reset&amp;id='.$object->id.'">'.$langs->trans("ResetMailing").'</a>';
					}
				}

				if (($object->status <= 1 && $user->hasRight('mailing', 'creer')) || $user->hasRight('mailing', 'supprimer')) {
					if ($object->status > 0 && (getDolGlobalString('MAIN_USE_ADVANCED_PERMS') && !$user->hasRight('mailing', 'mailing_advance', 'delete'))) {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->transnoentitiesnoconv("NotEnoughPermissions")).'">'.$langs->trans("Delete").'</a>';
					} else {
						print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&id='.$object->id.(!empty($urlfrom) ? '&urlfrom='.$urlfrom : '').'">'.$langs->trans("Delete").'</a>';
					}
				}

				print '</div>';
			}

			// Display of the TEST form
			if ($action == 'test') {
				print '<div id="formmailbeforetitle" name="formmailbeforetitle"></div>';
				print load_fiche_titre($langs->trans("TestMailing"));

				print dol_get_fiche_head(null, '', '', -1);

				// Create mail form object
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				$formmail->fromname = $object->email_from;
				$formmail->frommail = $object->email_from;
				$formmail->withsubstit = 0;
				$formmail->withfrom = 0;
				$formmail->withto = $user->email ? $user->email : 1;
				$formmail->withtocc = 0;
				$formmail->withtoccc = getDolGlobalString('MAIN_EMAIL_USECCC');
				$formmail->withtopic = 0;
				$formmail->withtopicreadonly = 1;
				$formmail->withfile = 0;
				$formmail->withlayout = 0;
				$formmail->withaiprompt = '';
				$formmail->withbody = 0;
				$formmail->withbodyreadonly = 1;
				$formmail->withcancel = 1;
				$formmail->withdeliveryreceipt = 0;
				// Table of substitutions
				$formmail->substit = $object->substitutionarrayfortest;
				// Table of post's complementary params
				$formmail->param["action"] = "send";
				$formmail->param["models"] = 'none';
				$formmail->param["mailid"] = $object->id;
				$formmail->param["returnurl"] = $_SERVER['PHP_SELF']."?id=".$object->id;

				print $formmail->get_form();

				print '<br>';

				print dol_get_fiche_end();

				dol_set_focus('#sendto');
			}


			$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br><br><span class="small">';
			foreach ($object->substitutionarray as $key => $val) {
				$htmltext .= $key.' = '.$langs->trans($val).'<br>';
			}
			$htmltext .= '</span></i>';

			// Print mail content
			print load_fiche_titre($langs->trans("EMail"), $form->textwithpicto('<span class="opacitymedium hideonsmartphone">'.$langs->trans("AvailableVariables").'</span>', $htmltext, 1, 'helpclickable', '', 0, 3, 'emailsubstitionhelp'), 'generic');

			print dol_get_fiche_head('', '', '', -1);

			print '<table class="bordernooddeven tableforfield centpercent">';

			// Subject
			if ($object->messtype != 'sms') {
				print '<tr><td class="titlefield">'.$langs->trans("MailTopic").'</td><td colspan="3">'.$object->sujet.'</td></tr>';
			}

			// Joined files
			if ($object->messtype != 'sms') {
				print '<tr><td>'.$langs->trans("MailFile").'</td><td colspan="3">';
				// List of files
				$listofpaths = dol_dir_list($upload_dir, 'all', 0, '', '', 'name', SORT_ASC, 0);
				if (count($listofpaths)) {
					foreach ($listofpaths as $key => $val) {
						print img_mime($listofpaths[$key]['name']).' '.$listofpaths[$key]['name'];
						print '<br>';
					}
				} else {
					print '<span class="opacitymedium">'.$langs->trans("NoAttachedFiles").'</span><br>';
				}
				print '</td></tr>';
			}

			// Background color
			/*print '<tr><td width="15%">'.$langs->trans("BackgroundColorByDefault").'</td><td colspan="3">';
			print $htmlother->selectColor($object->bgcolor,'bgcolor','',0);
			print '</td></tr>';*/

			print '</table>';

			// Message
			print '<div style="padding-top: 10px; background: '.($object->bgcolor ? (preg_match('/^#/', $object->bgcolor) ? '' : '#').$object->bgcolor : 'white').'">';
			if (empty($object->bgcolor) || strtolower($object->bgcolor) == 'ffffff') {	// CKEditor does not apply the color of the div into its content area
				$readonly = 1;
				// wysiwyg editor
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor('bodyemail', $object->body, '', 600, 'dolibarr_mailings', '', false, true, !getDolGlobalString('FCKEDITOR_ENABLE_MAILING') ? 0 : 1, 20, '90%', $readonly);
				$doleditor->Create();
			} else {
				print dol_htmlentitiesbr($object->body);
			}
			print '</div>';

			print dol_get_fiche_end();
		} else {
			/*
			 * Edition mode mailing (CKeditor or HTML source)
			 */

			print dol_get_fiche_head($head, 'card', $langs->trans("Mailing"), -1, 'email');

			$linkback = '<a href="'.DOL_URL_ROOT.'/comm/mailing/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Ref customer
			$morehtmlref .= $form->editfieldkey("", 'title', $object->title, $object, $user->hasRight('mailing', 'creer'), 'string', '', 0, 1);
			$morehtmlref .= $form->editfieldval("", 'title', $object->title, $object, $user->hasRight('mailing', 'creer'), 'string', '', null, null, '', 1);
			$morehtmlref .= '</div>';

			$morehtmlstatus = '';
			$nbtry = $nbok = 0;
			if ($object->status == 2 || $object->status == 3) {
				$nbtry = $object->countNbOfTargets('alreadysent');
				$nbko  = $object->countNbOfTargets('alreadysentko');

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

			/*
			print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
			print '<td colspan="3">';
			print $form->showrefnav($object,'id', $linkback);
			print '</td></tr>';
			*/

			// From
			print '<tr><td class="titlefield">';
			print $langs->trans("MailFrom");
			print '</td><td>'.dol_print_email($object->email_from, 0, 0, 0, 0, 1).'</td></tr>';
			// To
			if ($object->messtype != 'sms') {
				print '<tr><td>'.$langs->trans("MailErrorsTo").'</td><td>'.dol_print_email($object->email_errorsto, 0, 0, 0, 0, 1).'</td></tr>';
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
				if ((getDolGlobalString('MAILING_LIMIT_SENDBYWEB') && $conf->global->MAILING_LIMIT_SENDBYWEB < $nbemail) && ($object->status == 1 || $object->status == 2)) {
					if (getDolGlobalInt('MAILING_LIMIT_SENDBYWEB') > 0) {
						$text .= $langs->trans('LimitSendingEmailing', getDolGlobalString('MAILING_LIMIT_SENDBYWEB'));
					} else {
						$text .= $langs->trans('SendingFromWebInterfaceIsNotAllowed');
					}
				}
				if (empty($nbemail)) {
					$nbemail .= ' '.img_warning('').' <span class="warning">'.$langs->trans("NoTargetYet").'</span>';
				}
				if ($text) {
					print $form->textwithpicto($nbemail, $text, 1, 'warning');
				} else {
					print $nbemail;
				}
			}
			print '</td></tr>';

			print '<tr><td>';
			print $langs->trans("MAIN_MAIL_SENDMODE");
			print '</td><td>';
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
					print ' <span class="opacitymedium">('.getDolGlobalString('MAIN_MAIL_SMTP_SERVER_EMAILING').')</span>';
				}
			} elseif (getDolGlobalString('MAIN_MAIL_SENDMODE') != 'mail' && getDolGlobalString('MAIN_MAIL_SMTP_SERVER')) {
				print ' <span class="opacitymedium">('.getDolGlobalString('MAIN_MAIL_SMTP_SERVER').')</span>';
			}
			print '</td></tr>';


			// Other attributes
			$parameters = array();
			$reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;
			if (empty($reshook)) {
				print $object->showOptionals($extrafields, 'edit', $parameters);
			}

			print '</table>';
			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();


			print "<br><br>\n";

			print '<form name="edit_mailing" action="card.php" method="post" enctype="multipart/form-data">'."\n";
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			$htmltext = '<i>'.$langs->trans("FollowingConstantsWillBeSubstituted").':<br><br><span class="small">';
			foreach ($object->substitutionarray as $key => $val) {
				$htmltext .= $key.' = '.$langs->trans($val).'<br>';
			}
			$htmltext .= '</span></i>';

			// Print mail content
			print load_fiche_titre($langs->trans("EMail"), '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("AvailableVariables").'</span>', $htmltext, 1, 'help', '', 0, 2, 'emailsubstitionhelp'), 'generic');

			print dol_get_fiche_head(null, '', '', -1);

			print '<table class="bordernooddeven centpercent">';

			// Subject
			if ($object->messtype != 'sms') {
				print '<tr><td class="fieldrequired titlefield">';
				print $langs->trans("MailTopic");
				print '</td><td colspan="3"><input class="flat quatrevingtpercent" type="text" name="sujet" value="'.$object->sujet.'"></td></tr>';
			}

			$trackid = ''; // TODO To avoid conflicts with 2 mass emailing, we should set a trackid here, even if we use another one into email header.
			dol_init_file_process($upload_dir, $trackid);

			// Joined files
			if ($object->messtype != 'sms') {
				$addfileaction = 'addfile';
				print '<tr><td>'.$langs->trans("MailFile").'</td>';
				print '<td colspan="3">';
				// List of files
				$listofpaths = dol_dir_list($upload_dir, 'all', 0, '', '', 'name', SORT_ASC, 0);
				$out = '';

				// TODO Trick to have param removedfile containing nb of image to delete. But this does not works without javascript
				$out .= '<input type="hidden" class="removedfilehidden" name="removedfile" value="">'."\n";
				$out .= '<script type="text/javascript">';
				$out .= 'jQuery(document).ready(function () {';
				$out .= '    jQuery(".removedfile").click(function() {';
				$out .= '        jQuery(".removedfilehidden").val(jQuery(this).val());';
				$out .= '    });';
				$out .= '})';
				$out .= '</script>'."\n";
				if (count($listofpaths)) {
					foreach ($listofpaths as $key => $val) {
						$out .= '<div id="attachfile_'.$key.'">';
						$out .= img_mime($listofpaths[$key]['name']).' '.$listofpaths[$key]['name'];
						$out .= ' <input type="image" style="border: 0px;" src="'.img_picto($langs->trans("Search"), 'delete.png', '', '', 1).'" value="'.($key + 1).'" class="removedfile" id="removedfile_'.$key.'" name="removedfile_'.$key.'" />';
						$out .= '<br></div>';
					}
				} else {
					//$out .= '<span class="opacitymedium">'.$langs->trans("NoAttachedFiles").'</span><br>';
				}

				// Add link to add file
				$maxfilesizearray = getMaxFileSizeArray();
				$maxmin = $maxfilesizearray['maxmin'];
				if ($maxmin > 0) {
					$out .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
				}
				$out .= '<input type="file" class="flat" id="addedfile" name="addedfile" value="'.$langs->trans("Upload").'" />';
				$out .= ' ';
				$out .= '<input type="submit" class="button smallpaddingimp" id="'.$addfileaction.'" name="'.$addfileaction.'" value="'.$langs->trans("MailingAddFile").'" />';
				print $out;
				print '</td></tr>';

				// Background color
				print '<tr><td>'.$langs->trans("BackgroundColorByDefault").'</td><td colspan="3">';
				print $htmlother->selectColor($object->bgcolor, 'bgcolor', '', 0);
				print '</td></tr>';
			}

			print '</table>';


			// Message
			print '<div style="padding-top: 10px">';

			if ($action == 'edit') {
				// wysiwyg editor
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor('bodyemail', $object->body, '', 600, 'dolibarr_mailings', '', true, true, getDolGlobalInt('FCKEDITOR_ENABLE_MAILING'), 20, '90%');
				$doleditor->Create();
			}
			if ($action == 'edittxt') {
				// wysiwyg editor
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor('bodyemail', $object->body, '', 600, 'dolibarr_mailings', '', true, true, 0, 20, '90%');
				$doleditor->Create();
			}
			if ($action == 'edithtml') {
				// HTML source editor
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor('bodyemail', $object->body, '', 600, 'dolibarr_mailings', '', true, true, 'ace', 20, '90%');
				$doleditor->Create(0, '', false, 'HTML Source', 'php');
			}

			print '</div>';


			print dol_get_fiche_end();

			print '<div class="center">';
			print '<input type="submit" class="button buttonforacesave button-save" value="'.$langs->trans("Save").'" name="save">';
			print '&nbsp; &nbsp; &nbsp;';
			print '<input type="submit" class="button button-cancel" value="'.$langs->trans("Cancel").'" name="cancel">';
			print '</div>';

			print '</form>';
			print '<br>';
		}
	} else {
		dol_print_error($db, $object->error);
	}
}

// End of page
llxFooter();
$db->close();
