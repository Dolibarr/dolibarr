#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2016 Regis Houssin <regis.houssin@inodbox.com>
 * Copyright (C) 2019 		Nicolas ZABOURI	<info@inovea-conseil.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file 	scripts/emailings/mailing-send.php
 * \ingroup mailing
 * \brief 	Script to send a prepared and validated emaling from command line
 */

if (!defined('NOSESSION')) define('NOSESSION', '1');

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__.'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (!isset($argv[1]) || !$argv[1]) {
	print "Usage: ".$script_file." (ID_MAILING|all) [userloginforsignature] [maxnbofemails]\n";
	exit(-1);
}

$id = $argv[1];

if (isset($argv[2]) || !empty($argv[2])) $login = $argv[2];
else $login = '';

$max = 0;

if (isset($argv[3]) || !empty($argv[3])) $max = $argv[3];


require_once $path."../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT."/core/class/CMailFile.class.php";
require_once DOL_DOCUMENT_ROOT."/comm/mailing/class/mailing.class.php";

// Global variables
$version = DOL_VERSION;
$error = 0;

if (empty($conf->global->MAILING_LIMIT_SENDBYCLI))
{
	$conf->global->MAILING_LIMIT_SENDBYCLI = 0;
}


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";

if (!empty($conf->global->MAILING_DELAY)) {
	print 'A delay of '.((float) $conf->global->MAILING_DELAY * 1000000).' millisecond has been set between each email'."\n";
}

if ($conf->global->MAILING_LIMIT_SENDBYCLI == '-1') {}

$user = new User($db);
// for signature, we use user send as parameter
if (!empty($login))
	$user->fetch('', $login);

// We get list of emailing id to process
$sql = "SELECT m.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."mailing as m";
$sql .= " WHERE m.statut IN (1,2)";
if ($id != 'all') {
	$sql .= " AND m.rowid= ".$id;
	$sql .= " LIMIT 1";
}

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$j = 0;

	if ($num) {
		for ($j = 0; $j < $num; $j++) {
			$obj = $db->fetch_object($resql);

			dol_syslog("Process mailing with id ".$obj->rowid);
			print "Process mailing with id ".$obj->rowid."\n";

			$emailing = new Mailing($db);
			$emailing->fetch($obj->rowid);

			$upload_dir = $conf->mailing->dir_output."/".get_exdir($emailing->id, 2, 0, 1, $emailing, 'mailing');

			$id = $emailing->id;
			$subject = $emailing->sujet;
			$message = $emailing->body;
			$from = $emailing->email_from;
			$replyto = $emailing->email_replyto;
			$errorsto = $emailing->email_errorsto;
			// Le message est-il en html
			$msgishtml = - 1; // Unknown by default
			if (preg_match('/[\s\t]*<html>/i', $message))
				$msgishtml = 1;

			$nbok = 0;
			$nbko = 0;

			// On choisit les mails non deja envoyes pour ce mailing (statut=0)
			// ou envoyes en erreur (statut=-1)
			$sql2 = "SELECT mc.rowid, mc.fk_mailing, mc.lastname, mc.firstname, mc.email, mc.other, mc.source_url, mc.source_id, mc.source_type, mc.tag";
			$sql2 .= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
			$sql2 .= " WHERE mc.statut < 1 AND mc.fk_mailing = ".((int) $id);
			if ($conf->global->MAILING_LIMIT_SENDBYCLI > 0 && empty($max)) {
				$sql2 .= " LIMIT ".$conf->global->MAILING_LIMIT_SENDBYCLI;
			} elseif ($conf->global->MAILING_LIMIT_SENDBYCLI > 0 && $max > 0) {
				$sql2 .= " LIMIT ".min($conf->global->MAILING_LIMIT_SENDBYCLI, $max);
			} elseif ($max > 0) {
				$sql2 .= " LIMIT ".$max;
			}

			$resql2 = $db->query($sql2);
			if ($resql2) {
				$num2 = $db->num_rows($resql2);
				dol_syslog("Nb of targets = ".$num2, LOG_DEBUG);
				print "Nb of targets = ".$num2."\n";

				if ($num2) {
					$now = dol_now();

					// Positionne date debut envoi
					$sqlstartdate = "UPDATE ".MAIN_DB_PREFIX."mailing SET date_envoi='".$db->idate($now)."' WHERE rowid=".((int) $id);
					$resqlstartdate = $db->query($sqlstartdate);
					if (!$resqlstartdate) {
						dol_print_error($db);
						$error++;
					}

					// Look on each email and sent message
					$i = 0;
					while ($i < $num2) {
						// Here code is common with same loop ino card.php
						$res = 1;
						$now = dol_now();

						$obj = $db->fetch_object($resql2);

						// sendto en RFC2822
						$sendto = str_replace(',', ' ', dolGetFirstLastname($obj->firstname, $obj->lastname)." <".$obj->email.">");

						// Make subtsitutions on topic and body
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
						$signature = ((!empty($user->signature) && empty($conf->global->MAIN_MAIL_DO_NOT_USE_SIGN)) ? $user->signature : '');

						$object = null; // Not defined with mass emailing
						$parameters = array('mode' => 'emailing');
						$substitutionarray = getCommonSubstitutionArray($langs, 0, array('object', 'objectamount'), $object); // Note: On mass emailing, this is null because we don't know object

						// Array of possible substitutions (See also file mailing-send.php that should manage same substitutions)
						$substitutionarray['__ID__'] = $obj->source_id;
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
						$substitutionarray['__SIGNATURE__'] = $signature; // For backward compatibility
						$substitutionarray['__CHECK_READ__'] = '<img src="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-read.php?tag='.$obj->tag.'&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" width="1" height="1" style="width:1px;height:1px" border="0"/>';
						$substitutionarray['__UNSUBSCRIBE__'] = '<a href="'.DOL_MAIN_URL_ROOT.'/public/emailing/mailing-unsubscribe.php?tag='.$obj->tag.'&unsuscrib=1&securitykey='.urlencode($conf->global->MAILING_EMAIL_UNSUBSCRIBE_KEY).'" target="_blank">'.$langs->trans("MailUnsubcribe").'</a>';

						$onlinepaymentenabled = 0;
						if (!empty($conf->paypal->enabled))
							$onlinepaymentenabled++;
						if (!empty($conf->paybox->enabled))
							$onlinepaymentenabled++;
						if (!empty($conf->stripe->enabled))
							$onlinepaymentenabled++;
						if ($onlinepaymentenabled && !empty($conf->global->PAYMENT_SECURITY_TOKEN)) {
							$substitutionarray['__SECUREKEYPAYMENT__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
							if (empty($conf->global->PAYMENT_SECURITY_TOKEN_UNIQUE)) {
								$substitutionarray['__SECUREKEYPAYMENT_MEMBER__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
								$substitutionarray['__SECUREKEYPAYMENT_ORDER__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
								$substitutionarray['__SECUREKEYPAYMENT_INVOICE__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
								$substitutionarray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN, 2);
							} else {
								$substitutionarray['__SECUREKEYPAYMENT_MEMBER__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'membersubscription'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_ORDER__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'order'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_INVOICE__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'invoice'.$obj->source_id, 2);
								$substitutionarray['__SECUREKEYPAYMENT_CONTRACTLINE__'] = dol_hash($conf->global->PAYMENT_SECURITY_TOKEN.'contractline'.$obj->source_id, 2);
							}
						}
						/* For backward compatibility */
						if (!empty($conf->paypal->enabled) && !empty($conf->global->PAYPAL_SECURITY_TOKEN)) {
							$substitutionarray['__SECUREKEYPAYPAL__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);

							if (empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
								$substitutionarray['__SECUREKEYPAYPAL_MEMBER__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);
							else $substitutionarray['__SECUREKEYPAYPAL_MEMBER__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN.'membersubscription'.$obj->source_id, 2);

							if (empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
								$substitutionarray['__SECUREKEYPAYPAL_ORDER__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);
							else $substitutionarray['__SECUREKEYPAYPAL_ORDER__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN.'order'.$obj->source_id, 2);

							if (empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
								$substitutionarray['__SECUREKEYPAYPAL_INVOICE__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);
							else $substitutionarray['__SECUREKEYPAYPAL_INVOICE__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN.'invoice'.$obj->source_id, 2);

							if (empty($conf->global->PAYPAL_SECURITY_TOKEN_UNIQUE))
								$substitutionarray['__SECUREKEYPAYPAL_CONTRACTLINE__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN, 2);
							else $substitutionarray['__SECUREKEYPAYPAL_CONTRACTLINE__'] = dol_hash($conf->global->PAYPAL_SECURITY_TOKEN.'contractline'.$obj->source_id, 2);
						}

						complete_substitutions_array($substitutionarray, $langs);
						$newsubject = make_substitutions($subject, $substitutionarray);
						$newmessage = make_substitutions($message, $substitutionarray);

						$substitutionisok = true;

						$arr_file = array();
						$arr_mime = array();
						$arr_name = array();
						$arr_css  = array();

						$listofpaths = dol_dir_list($upload_dir, 'all', 0, '', '', 'name', SORT_ASC, 0);

						if (count($listofpaths))
						{
							foreach ($listofpaths as $key => $val)
							{
								$arr_file[] = $listofpaths[$key]['fullname'];
								$arr_mime[] = dol_mimetype($listofpaths[$key]['name']);
								$arr_name[] = $listofpaths[$key]['name'];
							}
						}
						// Fabrication du mail
						$trackid = 'emailing-'.$obj->fk_mailing.'-'.$obj->rowid;
						$mail = new CMailFile($newsubject, $sendto, $from, $newmessage, $arr_file, $arr_mime, $arr_name, '', '', 0, $msgishtml, $errorsto, $arr_css, $trackid, '', 'emailing');

						if ($mail->error) {
							$res = 0;
						}
						if (!$substitutionisok) {
							$mail->error = 'Some substitution failed';
							$res = 0;
						}

						// Send Email
						if ($res) {
							$res = $mail->sendfile();
						}

						if ($res) {
							// Mail successful
							$nbok++;

							dol_syslog("ok for emailing id ".$id." #".$i.($mail->error ? ' - '.$mail->error : ''), LOG_DEBUG);

							// Note: If emailing is 100 000 targets, 100 000 entries are added, so we don't enter events for each target here
							// We must union table llx_mailing_taget for event tab OR enter 1 event with a special table link (id of email in event)
							// Run trigger
							/*
							 * if ($obj->source_type == 'contact')
							 * {
							 * $emailing->sendtoid = $obj->source_id;
							 * }
							 * if ($obj->source_type == 'thirdparty')
							 * {
							 * $emailing->socid = $obj->source_id;
							 * }
							 * // Call trigger
							 * $result=$emailing->call_trigger('EMAILING_SENTBYMAIL',$user);
							 * if ($result < 0) $error++;
							 * // End call triggers
							 */

							$sqlok = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
							$sqlok .= " SET statut=1, date_envoi='".$db->idate($now)."' WHERE rowid=".$obj->rowid;
							$resqlok = $db->query($sqlok);
							if (!$resqlok) {
								dol_print_error($db);
								$error++;
							} else {
								// if cheack read is use then update prospect contact status
								if (strpos($message, '__CHECK_READ__') !== false) {
									// Update status communication of thirdparty prospect
									$sqlx = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT source_id FROM ".MAIN_DB_PREFIX."mailing_cibles WHERE rowid=".$obj->rowid.")";
									dol_syslog("card.php: set prospect thirdparty status", LOG_DEBUG);
									$resqlx = $db->query($sqlx);
									if (!$resqlx) {
										dol_print_error($db);
										$error++;
									}

									// Update status communication of contact prospect
									$sqlx = "UPDATE ".MAIN_DB_PREFIX."societe SET fk_stcomm=2 WHERE rowid IN (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."socpeople AS sc INNER JOIN ".MAIN_DB_PREFIX."mailing_cibles AS mc ON mc.rowid=".$obj->rowid." AND mc.source_type = 'contact' AND mc.source_id = sc.rowid)";
									dol_syslog("card.php: set prospect contact status", LOG_DEBUG);

									$resqlx = $db->query($sqlx);
									if (!$resqlx) {
										dol_print_error($db);
										$error++;
									}
								}

								if (!empty($conf->global->MAILING_DELAY)) {
									usleep((float) $conf->global->MAILING_DELAY * 1000000);
								}
							}
						} else {
							// Mail failed
							$nbko++;

							dol_syslog("error for emailing id ".$id." #".$i.($mail->error ? ' - '.$mail->error : ''), LOG_DEBUG);

							$sqlerror = "UPDATE ".MAIN_DB_PREFIX."mailing_cibles";
							$sqlerror .= " SET statut=-1, date_envoi='".$db->idate($now)."' WHERE rowid=".$obj->rowid;
							$resqlerror = $db->query($sqlerror);
							if (!$resqlerror) {
								dol_print_error($db);
								$error++;
							}
						}

						$i++;
					}
				} else {
					$mesg = "Emailing id ".$id." has no recipient to target";
					print $mesg."\n";
					dol_syslog($mesg, LOG_ERR);
				}

				// Loop finished, set global statut of mail
				$statut = 2;
				if (!$nbko)
					$statut = 3;

				$sqlenddate = "UPDATE ".MAIN_DB_PREFIX."mailing SET statut=".$statut." WHERE rowid=".$id;

				dol_syslog("update global status", LOG_DEBUG);
				print "Update status of emailing id ".$id." to ".$statut."\n";
				$resqlenddate = $db->query($sqlenddate);
				if (!$resqlenddate) {
					dol_print_error($db);
					$error++;
				}
			} else {
				dol_print_error($db);
				$error++;
			}
		}
	} else {
		$mesg = "No validated emailing id to send found.";
		print $mesg."\n";
		dol_syslog($mesg, LOG_ERR);
		$error++;
	}
} else {
	dol_print_error($db);
	$error++;
}

exit($error);
