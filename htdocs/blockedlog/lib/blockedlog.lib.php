<?php
/* Copyright (C) 2017 ATM Consulting <contact@atm-consulting.fr>
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
 *    \file       htdocs/blockedlog/lib/blockedlog.lib.php
 *    \ingroup    system
 *    \brief      Library for common blockedlog functions
 */

include_once DOL_DOCUMENT_ROOT.'/blockedlog/versionmod.inc.php';


/**
 *  Define head array for tabs of blockedlog tools setup pages
 *
 *  @return	string		Version
 */
function getBlockedLogVersionToShow()
{
	// return DOL_VERSION;
	return constant('DOLCERT_VERSION');
}


/**
 *  Define head array for tabs of blockedlog tools setup pages
 *
 *  @param	string		$withtabsetup					Add also the tab "Setup"
 *  @return	array<array{0:string,1:string,2:string}>	Array of head
 */
function blockedlogadmin_prepare_head($withtabsetup)
{
	global $db, $langs, $conf, $mysoc;

	$langs->load("blockedlog");

	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';

	$param = '';
	$param .= ($withtabsetup? "?withtab=".$withtabsetup : "");
	$param .= (GETPOST('origin') ? ($param ? '&' : '?').'origin='.GETPOST('origin') : '');

	$h = 0;
	$head = array();

	if (!userIsTaxAuditor()) {
		$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/registration.php".$param;
		$head[$h][1] = $langs->trans("UserRegistration");
		$head[$h][2] = 'registration';
		$h++;
	}

	if (!userIsTaxAuditor()) {
		$b = new BlockedLog($db);
		$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog_list.php".$param;
		$head[$h][1] = $langs->trans("BrowseBlockedLog");
		if ($b->alreadyUsed()) {
			$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">...</span>' : '');
		}
		$head[$h][2] = 'fingerprints';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog_archives.php".$param;
	$head[$h][1] = $langs->trans("Archives");
	// TODO Add number of archive files in badge
	$head[$h][2] = 'archives';
	$h++;

	if ($mysoc->country_code == 'FR') {
		$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/documentation.php".$param;
		$head[$h][1] = $langs->trans("Documentation");
		$head[$h][2] = 'documentation';
		$h++;
	}

	if ($withtabsetup && !userIsTaxAuditor()) {
		$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog.php".$param;
		$head[$h][1] = $langs->trans("TechnicalInformation");
		$head[$h][2] = 'technicalinfo';
		$h++;
	}


	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'blockedlog');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'blockedlog', 'remove');

	return $head;
}


/**
 * Return if the KYC mandatory parameters are set
 * Must be the samefields than the one defined as mandatory into the registration form.
 *
 * @return boolean		True or false
 */
function isRegistrationDataSaved()
{
	global $mysoc;

	$companyname = getDolGlobalString('BLOCKEDLOG_REGISTRATION_NAME', $mysoc->name);
	$companyemail = getDolGlobalString('BLOCKEDLOG_REGISTRATION_EMAIL', $mysoc->email);
	$companycountrycode = getDolGlobalString('BLOCKEDLOG_REGISTRATION_COUNTRY_CODE', $mysoc->country_code);
	$companyidprof1 = getDolGlobalString('BLOCKEDLOG_REGISTRATION_IDPROF1', $mysoc->idprof1);
	//$companytel = getDolGlobalString('BLOCKEDLOG_REGISTRATION_TEL', $mysoc->phone);

	if (empty($companyname) || empty($companycountrycode) || empty($companyidprof1) || empty($companyemail)) {
		return false;
	}

	/*
	$providerset = getDolGlobalString('MAIN_INFO_ITPROVIDER_NAME');	// Can be 'myself'

	if (empty($providerset)) {
		return false;
	}
	*/

	return true;
}


/**
 * Return if the KYC mandatory parameters are set AND pushed/registered centralized server
 *
 * @return boolean		True or false
 */
function isRegistrationDataSavedAndPushed()
{
	return isRegistrationDataSaved() && (bool) getDolGlobalString('MAIN_FIRST_REGISTRATION_OK_DATE');
}


/**
 * Return a hash unique identifier of the registration (used to identify the registration of instance without disclosing personal data)
 *
 * @param	string	$algo		Algorithm to use for hash key
 * @return 	string				Hash unique ID
 */
function getHashUniqueIdOfRegistration($algo = 'sha256')
{
	global $conf;

	return dol_hash('dolibarr'.$conf->file->instance_unique_id.($conf->entity > 1 ? $conf->entity : ''), $algo, 1);
}


/**
 * Return if the version is a candidate version to get the LNE certification and if the prerequisites are OK in production to be switched to LNE certified mode.
 * The difference with isALNERunningVersion() is that isALNEQualifiedVersion() just checks if it has a sense or not to activate
 * the restrictions (it is not a check to say if we are or not in a mode with restrictions activated, but if we are in a context that has a sense to activate them).
 * It can be used to show warnings or alerts to end users.
 *
 * @param   int<0,1>	$ignoredev			Set this to 1 to ignore the fact the version is an alpha or beta version
 * @param   int<0,1>	$ignoremodule		Set this to 1 to not take into account if module BlockedLog is on, so function can be used during module activation.
 * @return 	string							'' if false or a string if true
 */
function isALNEQualifiedVersion($ignoredev = 0, $ignoremodule = 0)
{
	global $mysoc;

	// For Debug help: Constant set by developer to force all LNE restrictions even if country is not France so we can test them on any dev instance.
	// Note that you can force, with this option, the enabling of the LNE restrictions, but there is no way to force the disabling of the LNE restriction.
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 2) {
		return 'CERTIF_LNE_IS_2';
	}

	if (!$ignoredev && preg_match('/\-/', DOL_VERSION)) {	// This is not a stable version
		return '';
	}
	if ($mysoc->country_code != 'FR') {
		return '';
	}
	if (!defined('CERTIF_LNE') || (int) constant('CERTIF_LNE') === 0) {
		return '';
	}
	if (!$ignoremodule && !isModEnabled('blockedlog')) {
		return '';
	}

	return ($ignoredev ? '' : 'NOT_BETA+').'FR+CERTIF_LNE_IS_1'.($ignoremodule ? '' : '+MODENABLED');	// all conditions are ok to become a LNE certified version
}


/**
 * Return if the application is executed with the LNE requirements on.
 * This function can be used to disable some features like custom receipts, or to enable others like showing the information "Certified LNE".
 *
 * @param	int		$blockedlogtestalreadydone	Test on blockedlog used already done
 * @return 	boolean								True or false
 */
function isALNERunningVersion($blockedlogtestalreadydone = 0)
{
	// For Debug help: Constant set by developer to force all LNE restrictions
	// even if country is not France so we can test them on any dev instance.
	// Note that you can force, with this option, the enabling of the LNE restrictions,
	// but there is no way to force the disabling of the LNE restriction.
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 2
		&& isModEnabled('blockedlog') && ($blockedlogtestalreadydone || isBlockedLogUsed())) {
		return true;
	}
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 1
		&& isModEnabled('blockedlog') && ($blockedlogtestalreadydone || isBlockedLogUsed())) {
		return true;
	}

	return false;
}

/**
 * Return if the blocked log was already used to block some events.
 *
 * @param   int<0,1>	$ignoresystem       Ignore system events for the test
 * @return 	boolean							True if blocked log was already used, false if not
 */
function isBlockedLogUsed($ignoresystem = 0)
{
	global $conf, $db;

	$result = true;	// by default restrictions are on, so we can't disable them

	// Note: if module on, we suppose it is used, if not, we check in case of it was disabled.
	if (!isModEnabled('blockedlog')) {
		// Test the cache key
		if (array_key_exists('isblockedlogused', $conf->cache)) {
			return $conf->cache['isblockedlogused'.$ignoresystem];
		}

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);	// Sharing entity in blocked log is disallowed
		if ($ignoresystem) {
			$sql .= " AND action NOT IN ('MODULE_SET', 'MODULE_RESET')";
		}
		$sql .= $db->plimit(1);

		$resql = $db->query($sql);
		if ($resql !== false) {
			$obj = $db->fetch_object($resql);
			if (!$obj) {
				$result = false;
			}
		} else {
			dol_print_error($db);
		}

		$conf->cache['isblockedlogused'.$ignoresystem] = $result;
	}

	dol_syslog("isBlockedLogUsed: ignoresystem=".$ignoresystem." returns ".(string) $result);

	return $result;
}


/**
 *      Add legal mention
 *
 *      @param	TCPDF      			$pdf            	Object PDF
 *      @param  Translate			$outputlangs		Object lang
 *      @param  Societe				$seller         	Seller company
 *      @param  int					$default_font_size  Default font size
 *      @param  float				$posy            	Y position
 *      @param  CommonDocGenerator	$pdftemplate    	PDF template
 *      @return	int                                 	0 if nothing done, 1 if a mention was printed
 */
function pdfCertifMentionblockedLog(&$pdf, $outputlangs, $seller, $default_font_size, &$posy, $pdftemplate)
{
	$result = 0;

	if (in_array($seller->country_code, array('FR'))) {
		$outputlangs->load("blockedlog");

		$isalne = isALNEQualifiedVersion(); // If necessary, we could replace with "if isALNERunningVersion()"
		if ($isalne == 'CERTIF_LNE_IS_2') {
			$blockedlog_mention = $outputlangs->transnoentitiesnoconv("InvoiceGeneratedWithLNECandidatePOSSystem");
		} else {
			$blockedlog_mention = $outputlangs->transnoentitiesnoconv("InvoiceGeneratedWithLNECertifiedPOSSystem");
		}

		if ($blockedlog_mention) {
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($pdftemplate->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $blockedlog_mention, 0, 'L', false);
			$posy = $pdf->GetY();
			$result = 1;
		}
	}

	return $result;
}

/**
 *      sumAmountsForUnalterableEvent
 *
 *      @param	BlockedLog			$block								Object BlockedLog
 *      @param	array<string,int>	$refinvoicefound					Array of ref of invoice already found (to avoid duplicates. Should be useless but just in case of)
 *      @param  array<string,array<string,float>>	$totalhtamount		Array of total per code event and module
 *      @param  array<string,array<string,float>>	$totalvatamount		Array of total per code event and module
 *      @param  array<string,array<string,float>>	$totalamount		Array of total per code event and module
 *      @param  float				$total_ht							Total HT
 *      @param  float				$total_vat							Total VAT
 *      @param  float				$total_ttc							Total TTC
 *      @return	int                                 					Return > 0
 */
function sumAmountsForUnalterableEvent($block, &$refinvoicefound, &$totalhtamount, &$totalvatamount, &$totalamount, &$total_ht, &$total_vat, &$total_ttc)
{
	// Init to avoid warnings if not initialized yet
	if (!isset($totalamount[$block->action][$block->module_source])) {
		$totalhtamount[$block->action][$block->module_source] = 0;
		$totalvatamount[$block->action][$block->module_source] = 0;
		$totalamount[$block->action][$block->module_source] = 0;
	}

	if ($block->action == 'BILL_VALIDATE') {
		$total_ht = $block->object_data->total_ht;
		$total_vat = $block->object_data->total_tva;
		$total_ttc = $block->object_data->total_ttc;

		// We add total for the invoice if "invoice validate event" not yet met.
		// If we already met the event for this object, we keep only first one but this should not happen because edition of validated invoice is not allowed on secured versions.
		if (empty($refinvoicefound[$block->ref_object])) {
			$totalhtamount[$block->action][$block->module_source] += $total_ht;
			$totalvatamount[$block->action][$block->module_source] += $total_vat;
			$totalamount[$block->action][$block->module_source] += $total_ttc;
		}
		$refinvoicefound[$block->ref_object] = 1;
	} elseif ($block->action == 'PAYMENT_CUSTOMER_CREATE' || $block->action == 'PAYMENT_CUSTOMER_DELETE') {
		$total_ht = $block->object_data->amount;
		$total_vat = 0;
		$total_ttc = $block->object_data->amount;

		//$actionkey = $block->action;
		$actionkey = 'PAYMENT_CUSTOMER';

		$totalhtamount[$actionkey][$block->module_source] += $total_ht;
		$totalvatamount[$actionkey][$block->module_source] += $total_vat;
		$totalamount[$actionkey][$block->module_source] += $total_ttc;
	} else {
		$total_ttc = $block->amounts;
	}

	return 1;
}


/**
 * Call remote API service to push the last counter and signature
 *
 * @param 	int		$id					Last counter ID/value
 * @param 	string	$signature			Signature
 * @param	int		$test				Add property test to 1 if it is for test
 * @param 	int		$previousid			Last counter ID/value
 * @param 	string	$previoussignature	Signature
 * @return	int							Return <0 if KO, 0 if nothing done, >0 if OK
 */
function callApiToPushCounter($id, $signature, $test, $previousid, $previoussignature)
{
	global $mysoc, $conf;

	if (isALNERunningVersion(1) && $mysoc->country_code == 'FR') {
		// Push last rowid + signature to remote dolibarr server
		// TODO Do it only for selected events: BILL_VALIDATE ?

		// Code here is similar to the one into printCodeForPing(), except that message code/properties/fields may differ.
		$url_for_ping = getDolGlobalString('MAIN_URL_FOR_PING', "https://ping.dolibarr.org/");

		$algo = 'sha256';
		$hash_unique_id = getHashUniqueIdOfRegistration($algo);		// The hash of the unique IDof instance

		$t = microtime(true);
		$micro = sprintf("%06d", (int) ($t - floor($t)) * 1000000);

		$data = '';
		$data .= 'hash_algo=dol_hash-'.urlencode($algo);
		$data .= '&hash_unique_id='.urlencode($hash_unique_id);
		$data .= '&action=dolibarrpushcounter';
		$data .= '&datesys='.urlencode(dol_print_date(dol_now('gmt'), 'standard', 'gmt').'.'.$micro);
		$data .= '&version='.(float) DOL_VERSION;
		$data .= '&version_full='.urlencode(DOL_VERSION);
		$data .= '&versionblockedlog='.(float) getBlockedLogVersionToShow();
		$data .= '&versionblockedlog_full='.urlencode(getBlockedLogVersionToShow());

		$data .= '&entity='.(int) $conf->entity;

		$data .= '&lastrowid='.(int) $id;
		$data .= '&lastsignature='.urlencode($signature);
		$data .= '&previousrowid='.(int) $previousid;
		$data .= '&previoussignature='.urlencode($previoussignature);
		if ($test) {
			$data .= '&test=1';
		}

		$addheaders = array();
		$timeoutconnect = 1;
		$timeoutresponse = 1;

		$conf->global->BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING = 1;		// Force probability to 1

		// Probability will be between 1/10 by default and 1/1 if const BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING is set to 1. Can't be lower than 1/10.
		$BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING = min(10, getDolGlobalInt('BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING', 10));
		$random = 1;
		//$BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING = 1;	// To force track at every call
		if ($BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING > 1) {
			$random = random_int(1, (int) $BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING);
		}

		if ($random == 1) {	// 1 chance on BLOCKEDLOG_RANDOMRANGE_FOR_TRACKING
			dol_syslog("callApiToPushCounter create Record is selected to be remotely pushed for tracking", LOG_DEBUG);

			include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';
			try {
				$tmpresult = getURLContent($url_for_ping, 'POST', $data, 1, $addheaders, array('https'), 0, -1, $timeoutconnect, $timeoutresponse, array(), '_dolibarrpushcounter');
				usleep(1000);

				// Add a warning in log in case of error
				if ($tmpresult['http_code'] != 200) {
					$logerrormessage = 'Error: '.$tmpresult['http_code'].' '.$tmpresult['content'];
					dol_syslog("callApiToPushCounter create Error when pushing track info: ".$logerrormessage, LOG_WARNING);
				}
			} catch (Exception $e) {
				dol_syslog("callApiToPushCounter create Error ".$e->getMessage(), LOG_ERR);
			}
		} else {
			dol_syslog("callApiToPushCounter create Record is NOT selected to be remotely pushed for tracking", LOG_DEBUG);
		}

		return 1;
	}

	return 0;
}

/**
 * Return if user is a ta auditor
 *
 * @return	int		Return > 0 if user is an external user so must be restricted to archive control feature
 */
function userIsTaxAuditor()
{
	global $user;

	return ((getDolGlobalString('BLOCKEDLOG_FOR_TAX_AUDITOR') && $user->socid) ? 1 : 0);
}
