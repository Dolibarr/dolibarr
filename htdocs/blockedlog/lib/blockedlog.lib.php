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

/**
 *  Define head array for tabs of blockedlog tools setup pages
 *
 *  @param	string		$withtabsetup					Add also the tab "Setup"
 *  @return	array<array{0:string,1:string,2:string}>	Array of head
 */
function blockedlogadmin_prepare_head($withtabsetup)
{
	global $db, $langs, $conf;

	$langs->load("blockedlog");

	$h = 0;
	$head = array();

	if ($withtabsetup) {
		$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog.php?withtab=".$withtabsetup;
		$head[$h][1] = $langs->trans("Setup");
		$head[$h][2] = 'blockedlog';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog_list.php?withtab=".$withtabsetup;
	$head[$h][1] = $langs->trans("BrowseBlockedLog");

	require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
	$b = new BlockedLog($db);
	if ($b->alreadyUsed()) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">...</span>' : '');
	}
	$head[$h][2] = 'fingerprints';
	$h++;


	$head[$h][0] = DOL_URL_ROOT."/blockedlog/admin/blockedlog_archives.php?withtab=".$withtabsetup;
	$head[$h][1] = $langs->trans("Archives");
	// TODO Add number of archive files in badge
	$head[$h][2] = 'archives';
	$h++;


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

	$providerset = getDolGlobalString('MAIN_INFO_ITPROVIDER_NAME');	// Can be 'myself'

	if (empty($providerset)) {
		return false;
	}

	return true;
}


/**
 * Return a hash unique identifier of the registration
 *
 * @return string		Hash unique ID (used to idenfiy the registration without disclosing personal data)
 */
function getHashUniqueIdOfRegistration()
{
	global $conf;

	return dol_hash('dolibarr'.$conf->file->instance_unique_id, 'sha256', 1);
}


/**
 * Return if the version is a candidate version to get the LNE certification and if the prerequisites are OK in production to be switched to LNE certified mode.
 * The difference with isALNERunningVersion() is that isALNEQualifiedVersion() just checks if it has a sense or not to activate
 * the restrictions (it is not a check to say if we are or not in a mode with restrictions activated, but if we are in a context that has a sense to activate them).
 * It can be used to show warnings or alerts to end users.
 *
 * @param   int<0,1>	$ignoredev			Set this to 1 to ignore the fact the version is an alpha or beta version
 * @param   int<0,1>	$ignoremodule		Set this to 1 to not take into account if module BlockedLog is on, so function can be used during module activation.
 * @return 	boolean							True or false
 */
function isALNEQualifiedVersion($ignoredev = 0, $ignoremodule = 0)
{
	global $mysoc;

	// For Debug help: Constant set by developer to force all LNE restrictions even if country is not France so we can test them on any dev instance.
	// Note that you can force, with this option, the enabling of the LNE restrictions, but there is no way to force the disabling of the LNE restriction.
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 2) {
		return true;
	}

	if (!$ignoredev && preg_match('/\-/', DOL_VERSION)) {	// This is not a stable version
		return false;
	}
	if ($mysoc->country_code != 'FR') {
		return false;
	}
	if (!defined('CERTIF_LNE') || (int) constant('CERTIF_LNE') === 0) {
		return false;
	}
	if (!$ignoremodule && !isModEnabled('blockedlog')) {
		return false;
	}

	return true;	// all conditions are ok to become a LNE certified version
}


/**
 * Return if the application is executed with the LNE requirements on.
 * This function can be used to disable some features like custom receipts, or to enable others like showing the information "Certified LNE".
 *
 * @return 	boolean		True or false
 */
function isALNERunningVersion()
{
	// For Debug help: Constant set by developer to force all LNE restrictions even if country is not France so we can test them on any dev instance.
	// Note that you can force, with this option, the enabling of the LNE restrictions, but there is no way to force the disabling of the LNE restriction.
	if (defined('CERTIF_LNE') && (int) constant('CERTIF_LNE') === 2) {
		return true;
	}
	if (isModEnabled('blockedlog') && isBlockedLogused()) {
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
function isBlockedLogused($ignoresystem = 0)
{
	global $conf, $db;

	$result = true;	// by default restrictions are on, so we can't disable them

	// For the moment, we don't need this. We already have a feature that does not allow to disable the LNE rstriction by
	// adding an inalterable event in the log.
	if (!isModEnabled('blockedlog')) {
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);	// Sharing entity in blocked is disallowed
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
	}

	dol_syslog("isBlockedLogused: ignoresystem=".$ignoresystem." returns ".(string) $result);

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

	if (in_array($seller->country_code, array('FR')) && isALNEQualifiedVersion()) {	// If necessary, we could replace with "if isALNERunningVersion()"
		$outputlangs->load("blockedlog");
		$blockedlog_mention = $outputlangs->trans("InvoiceGeneratedWithLNECertifiedPOSSystem");
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
