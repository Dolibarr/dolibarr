<?php
/* Copyright (C) 2021 NextGestion  <contact@nextgestion.com>
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
 *  \file       partnership/class/partnershiputils.class.php
 *  \ingroup    partnership
 *  \brief      Class with utilities
 */

//require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");
//require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/lib/partnership.lib.php';
require_once DOL_DOCUMENT_ROOT.'/partnership/class/partnership.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

/**
 *	Class with cron tasks of Partnership module
 */
class PartnershipUtils
{
	public $db; //!< To store db handler
	public $error; //!< To return error code (or message)
	public $errors = array(); //!< To return several error codes (or messages)

	public $output;	// To store output of some cron methods


	/**
	 *  Constructor
	 *
	 *  @param	DoliDb		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		return 1;
	}

	/**
	 * Action executed by scheduler to cancel status of partnership when subscription is expired + x days. (Max number of action batch per call = $conf->global->PARTNERSHIP_MAX_EXPIRATION_CANCEL_PER_CALL)
	 *
	 * CAN BE A CRON TASK
	 *
	 * @return  int                 0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doCancelStatusOfMemberPartnership()
	{
		global $conf, $langs, $user;

		$managedfor	= getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR', 'thirdparty');

		if ($managedfor != 'member') {
			return 0; // If option 'PARTNERSHIP_IS_MANAGED_FOR' = 'thirdparty', this cron job does nothing.
		}

		$partnership = new Partnership($this->db);
		$MAXPERCALL = (empty($conf->global->PARTNERSHIP_MAX_EXPIRATION_CANCEL_PER_CALL) ? 25 : $conf->global->PARTNERSHIP_MAX_EXPIRATION_CANCEL_PER_CALL); // Limit to 25 per call

		$langs->loadLangs(array("partnership", "member"));

		$error = 0;
		$erroremail = '';
		$this->output = '';
		$this->error = '';
		$partnershipsprocessed = array();

		$gracedelay = $conf->global->PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL;
		if ($gracedelay < 1) {
			$this->error = 'BadValueForDelayBeforeCancelCheckSetup';
			return -1;
		}

		dol_syslog(get_class($this)."::doCancelStatusOfMemberPartnership cancel expired partnerships with grace delay of ".$gracedelay);

		$now = dol_now();
		$datetotest = dol_time_plus_duree($now, -1 * abs($gracedelay), 'd');

		$this->db->begin();

		$sql = "SELECT p.rowid, p.fk_member, p.status";
		$sql .= ", d.datefin, d.fk_adherent_type, dty.subscription";
		$sql .= " FROM ".MAIN_DB_PREFIX."partnership as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as d on (d.rowid = p.fk_member)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent_type as dty on (dty.rowid = d.fk_adherent_type)";
		$sql .= " WHERE fk_member > 0";
		$sql .= " AND (d.datefin < '".$this->db->idate($datetotest)."' AND dty.subscription = 1)";
		$sql .= " AND p.status = ".((int) $partnership::STATUS_APPROVED); // Only accepted not yet canceled
		$sql .= $this->db->order('d.rowid', 'ASC');
		// Limit is managed into loop later

		$resql = $this->db->query($sql);
		if ($resql) {
			$numofexpiredmembers = $this->db->num_rows($resql);

			$somethingdoneonpartnership = 0;
			$ifetchpartner = 0;
			while ($ifetchpartner < $numofexpiredmembers) {
				$ifetchpartner++;

				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					if (!empty($partnershipsprocessed[$obj->rowid])) continue;

					if ($somethingdoneonpartnership >= $MAXPERCALL) {
						dol_syslog("We reach the limit of ".$MAXPERCALL." partnership processed, so we quit loop for this batch doCancelStatusOfMemberPartnership to avoid to reach email quota.", LOG_WARNING);
						break;
					}

					$object = new Partnership($this->db);
					$object->fetch($obj->rowid);

					// Get expiration date
					$expirationdate = $obj->datefin;

					if ($expirationdate && $expirationdate < $now) {  // If contract expired (we already had a test into main select, this is a security)
						$somethingdoneonpartnership++;

						$result = $object->cancel($user, 0);
						// $conf->global->noapachereload = null;
						if ($result < 0) {
							$error++;
							$this->error = $object->error;
							if (is_array($object->errors) && count($object->errors)) {
								if (is_array($this->errors)) $this->errors = array_merge($this->errors, $object->errors);
								else $this->errors = $object->errors;
							}
						} else {
							$partnershipsprocessed[$object->id] = $object->ref;

							// Send an email to inform member
							$labeltemplate = '(SendingEmailOnPartnershipCanceled)';

							dol_syslog("Now we will send an email to member id=".$object->fk_member." with label ".$labeltemplate);

							// Send deployment email
							include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
							include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
							$formmail = new FormMail($this->db);

							// Define output language
							$outputlangs = $langs;
							$newlang = '';
							if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
							if (!empty($newlang)) {
								$outputlangs = new Translate("", $conf);
								$outputlangs->setDefaultLang($newlang);
								$outputlangs->loadLangs(array('main', 'member', 'partnership'));
							}

							$arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'partnership_send', $user, $outputlangs, 0, 1, $labeltemplate);

							$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
							complete_substitutions_array($substitutionarray, $outputlangs, $object);

							$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $outputlangs);
							$msg     = make_substitutions($arraydefaultmessage->content, $substitutionarray, $outputlangs);
							$from = dol_string_nospecial($conf->global->MAIN_INFO_SOCIETE_NOM, ' ', array(",")).' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';

							$adherent = new Adherent($this->db);
							$adherent->fetch($object->fk_member);
							$to = $adherent->email;

							$cmail = new CMailFile($subject, $to, $from, $msg, array(), array(), array(), '', '', 0, 1);
							$result = $cmail->sendfile();
							if (!$result || $cmail->error) {
								$erroremail .= ($erroremail ? ', ' : '').$cmail->error;
								$this->errors[] = $cmail->error;
								if (is_array($cmail->errors) && count($cmail->errors) > 0) $this->errors += $cmail->errors;
							}
						}
					}
				}
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
		}

		if (!$error) {
			$this->db->commit();
			$this->output = $numofexpiredmembers.' expired partnership members found'."\n";
			if ($erroremail) $this->output .= '. Got errors when sending some email : '.$erroremail;
		} else {
			$this->db->rollback();
			$this->output = "Rollback after error\n";
			$this->output .= $numofexpiredmembers.' expired partnership members found'."\n";
			if ($erroremail) $this->output .= '. Got errors when sending some email : '.$erroremail;
		}

		return ($error ? 1 : 0);
	}


	/**
	 * Action executed by scheduler to check if Dolibarr backlink not found on partner website. (Max number of action batch per call = $conf->global->PARTNERSHIP_MAX_WARNING_BACKLINK_PER_CALL)
	 *
	 * CAN BE A CRON TASK
	 *
	 * @return  int                 0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doWarningOfPartnershipIfDolibarrBacklinkNotfound()
	{
		global $conf, $langs, $user;

		$managedfor = getDolGlobalString('PARTNERSHIP_IS_MANAGED_FOR');

		$partnership = new Partnership($this->db);
		$MAXPERCALL = (empty($conf->global->PARTNERSHIP_MAX_WARNING_BACKLINK_PER_CALL) ? 10 : $conf->global->PARTNERSHIP_MAX_WARNING_BACKLINK_PER_CALL); // Limit to 10 per call

		$langs->loadLangs(array("partnership", "member"));

		$error = 0;
		$erroremail = '';
		$this->output = '';
		$this->error = '';
		$partnershipsprocessed = array();

		$gracedelay = $conf->global->PARTNERSHIP_NBDAYS_AFTER_MEMBER_EXPIRATION_BEFORE_CANCEL;
		if ($gracedelay < 1) {
			$this->error = 'BadValueForDelayBeforeCancelCheckSetup';
			return -1;
		}

		$fk_partner = ($managedfor == 'member') ? 'fk_member' : 'fk_soc';

		dol_syslog(get_class($this)."::doWarningOfPartnershipIfDolibarrBacklinkNotfound Warning of partnership");

		$now = dol_now();
		$datetotest = dol_time_plus_duree($now, -1 * abs($gracedelay), 'd');

		$this->db->begin();

		$sql = "SELECT p.rowid, p.status, p.".$fk_partner;
		$sql .= ", p.last_check_backlink";

		$sql .= ', partner.url, partner.email';

		$sql .= " FROM ".MAIN_DB_PREFIX."partnership as p";

		if ($managedfor == 'member') {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as partner on (partner.rowid = p.fk_member)";
		} else {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as partner on (partner.rowid = p.fk_soc)";
		}

		$sql .= " WHERE 1 = 1";
		$sql .= " AND p.".$fk_partner." > 0";
		$sql .= " AND p.status = ".((int) $partnership::STATUS_APPROVED); // Only accepted not yet canceled
		$sql .= " AND (p.last_check_backlink IS NULL OR p.last_check_backlink <= '".$this->db->idate($now - 7 * 24 * 3600)."')"; // Every week, check that website contains a link to dolibarr.
		$sql .= $this->db->order('p.rowid', 'ASC');
		// Limit is managed into loop later

		$resql = $this->db->query($sql);
		if ($resql) {
			$numofexpiredmembers = $this->db->num_rows($resql);
			$somethingdoneonpartnership = 0;
			$ifetchpartner = 0;
			$websitenotfound = '';
			while ($ifetchpartner < $numofexpiredmembers) {
				$ifetchpartner++;

				$obj = $this->db->fetch_object($resql);
				if ($obj) {
					if (!empty($partnershipsprocessed[$obj->rowid])) continue;

					if ($somethingdoneonpartnership >= $MAXPERCALL) {
						dol_syslog("We reach the limit of ".$MAXPERCALL." partnership processed, so we quit loop for this batch doWarningOfPartnershipIfDolibarrBacklinkNotfound to avoid to reach email quota.", LOG_WARNING);
						break;
					}

					$backlinkfound = 0;

					$object = new Partnership($this->db);
					$object->fetch($obj->rowid);

					if ($managedfor == 'member') {
						$fk_partner = $object->fk_member;
					} else {
						$fk_partner = $object->fk_soc;
					}

					$website = $obj->url;

					if (empty($website)) {
						$websitenotfound .= ($websitenotfound ? ', ' : '').'Website not found for id="'.$fk_partner.'"'."\n";
					} else {
						$backlinkfound = $this->checkDolibarrBacklink($website);
					}

					if (!$backlinkfound) {
						$tmpcount = $object->count_last_url_check_error + 1;

						if ($tmpcount > 2 && $tmpcount <= 4) { // Send Warning Email
							if (!empty($obj->email)) {
								$emailnotfound .= ($emailnotfound ? ', ' : '').'Email not found for id="'.$fk_partner.'"'."\n";
							} else {
								$labeltemplate = '(SendingEmailOnPartnershipWillSoonBeCanceled)';

								dol_syslog("Now we will send an email to partner id=".$fk_partner." with label ".$labeltemplate);

								// Send deployment email
								include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
								include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
								$formmail = new FormMail($this->db);

								// Define output language
								$outputlangs = $langs;
								$newlang = '';
								if ($conf->global->MAIN_MULTILANGS && empty($newlang) && GETPOST('lang_id', 'aZ09')) $newlang = GETPOST('lang_id', 'aZ09');
								if (!empty($newlang)) {
									$outputlangs = new Translate("", $conf);
									$outputlangs->setDefaultLang($newlang);
									$outputlangs->loadLangs(array('main', 'member', 'partnership'));
								}

								$arraydefaultmessage = $formmail->getEMailTemplate($this->db, 'partnership_send', $user, $outputlangs, 0, 1, $labeltemplate);

								$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
								complete_substitutions_array($substitutionarray, $outputlangs, $object);

								$subject = make_substitutions($arraydefaultmessage->topic, $substitutionarray, $outputlangs);
								$msg     = make_substitutions($arraydefaultmessage->content, $substitutionarray, $outputlangs);
								$from = dol_string_nospecial($conf->global->MAIN_INFO_SOCIETE_NOM, ' ', array(",")).' <'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';

								$to = $obj->email;

								$cmail = new CMailFile($subject, $to, $from, $msg, array(), array(), array(), '', '', 0, 1);
								$result = $cmail->sendfile();
								if (!$result || $cmail->error) {
									$erroremail .= ($erroremail ? ', ' : '').$cmail->error;
									$this->errors[] = $cmail->error;
									if (is_array($cmail->errors) && count($cmail->errors) > 0) $this->errors += $cmail->errors;
								}
							}
						} elseif ($tmpcount > 4) { // Cancel Partnership
							$object->status = $object::STATUS_CANCELED;
							$object->reason_decline_or_cancel = $langs->trans('BacklinkNotFoundOnPartnerWebsite');
						}

						$object->count_last_url_check_error = $tmpcount;
					} else {
						$object->count_last_url_check_error = 0;
						$object->reason_decline_or_cancel = '';
					}

					$partnershipsprocessed[$object->id] = $object->ref;

					$object->last_check_backlink = $this->db->idate($now);

					$object->update($user);
				}
			}
		} else {
			$error++;
			$this->error = $this->db->lasterror();
		}

		if (!$error) {
			$this->db->commit();
			$this->output = $numofexpiredmembers.' partnership checked'."\n";
			if ($erroremail) $this->output .= '. Got errors when sending some email : '.$erroremail."\n";
			if ($emailnotfound) $this->output .= '. Email not found for some partner : '.$emailnotfound."\n";
			if ($websitenotfound) $this->output .= '. Website not found for some partner : '.$websitenotfound."\n";
		} else {
			$this->db->rollback();
			$this->output = "Rollback after error\n";
			$this->output .= $numofexpiredmembers.' partnership checked'."\n";
			if ($erroremail) $this->output .= '. Got errors when sending some email : '.$erroremail."\n";
			if ($emailnotfound) $this->output .= '. Email not found for some partner : '.$emailnotfound."\n";
			if ($websitenotfound) $this->output .= '. Website not found for some partner : '.$websitenotfound."\n";
		}

		return ($error ? 1 : 0);
	}

	/**
	 * Action to check if Dolibarr backlink not found on partner website
	 *
	 * @param  $website      Website	Partner's website
	 * @return  int                 	0 if KO, 1 if OK
	 */
	private function checkDolibarrBacklink($website = null)
	{
		global $conf, $langs, $user;

		$found 		= 0;
		$error 		= 0;
		$webcontent = '';

		// $website = 'https://nextgestion.com/'; // For Test
		$tmpgeturl = getURLContent($website, 'GET', '', 1, array(), array('http', 'https'), 0);
		if ($tmpgeturl['curl_error_no']) {
			$error++;
			dol_syslog('Error getting '.$website.': '.$tmpgeturl['curl_error_msg']);
		} elseif ($tmpgeturl['http_code'] != '200') {
			$error++;
			dol_syslog('Error getting '.$website.': '.$tmpgeturl['curl_error_msg']);
		} else {
			$urlContent = $tmpgeturl['content'];
			$dom = new DOMDocument();
			@$dom->loadHTML($urlContent);

			$xpath = new DOMXPath($dom);
			$hrefs = $xpath->evaluate("//a");

			for ($i = 0; $i < $hrefs->length; $i++) {
				$href = $hrefs->item($i);
				$url = $href->getAttribute('href');
				$url = filter_var($url, FILTER_SANITIZE_URL);
				if (!filter_var($url, FILTER_VALIDATE_URL) === false) {
					$webcontent .= $url;
				}
			}
		}

		if ($webcontent && !empty($conf->global->PARTNERSHIP_BACKLINKS_TO_CHECK) && preg_match('/'.$conf->global->PARTNERSHIP_BACKLINKS_TO_CHECK.'/', $webcontent)) {
			$found = 1;
		}

		return $found;
	}
}
