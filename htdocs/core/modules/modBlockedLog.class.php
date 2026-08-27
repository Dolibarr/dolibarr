<?php
/* Copyright (C) 2017-2025	Laurent Destailleur	<eldy@users.sourcefore.net>
 * Copyright (C) 2026		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2026		MDW					<mdeweerd@users.noreply.github.com>
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
 * 	\defgroup   blockedlog   Module BlockedLog
 *  \brief      Add a log into a block chain for some actions.
 *  \file       htdocs/core/modules/modBlockedLog.class.php
 *  \ingroup    blockedlog
 *  \brief      Description and activation file for the module BlockedLog
 */
include_once DOL_DOCUMENT_ROOT.'/core/modules/DolibarrModules.class.php';
include_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
include_once DOL_DOCUMENT_ROOT.'/blockedlog/versionmod.inc.php';


/**
 *	Class to describe a BlockedLog module
 */
class modBlockedLog extends DolibarrModules
{
	/**
	 *   Constructor. Define names, constants, directories, boxes, permissions
	 *
	 *   @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $mysoc;

		$this->db = $db;
		$this->numero = 3200;
		// Key text used to identify module (for permissions, menus, etc...)
		$this->rights_class = 'blockedlog';

		// Family can be 'crm','financial','hr','projects','products','ecm','technic','other'
		// It is used to group modules in module setup page
		$this->family = "base";
		// Module position in the family on 2 digits ('01', '10', '20', ...)
		$this->module_position = '76';
		// Module label (no space allowed), used if translation string 'ModuleXXXName' not found (where XXX is value of numeric property 'numero' of module)
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->description = "Enable a log on some business events into an unalterable log. This module may be mandatory for some countries.";

		// Possible values for version are: 'development', 'experimental', 'dolibarr' or version
		$this->version = constant('DOLCERT_VERSION');
		$this->version_if_core = 1;
		// Key used in llx_const table to save module status enabled/disabled (where MYMODULE is value of property name of module in uppercase)
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		// Name of image file used for this module.
		$this->picto = 'blockedlog';

		// Data directories to create when module is enabled
		$this->dirs = array();

		// Config pages
		//-------------
		$this->config_page_url = array('registration.php?origin=setupmodule&withtab=1@blockedlog');

		// Dependencies
		//-------------
		$this->hidden = false; // A condition to disable module
		$this->depends = array('always' => 'modFacture'); // List of modules id that must be enabled if this module is enabled
		$this->requiredby = array(); // List of modules id to disable if this one is disabled
		$this->conflictwith = array(); // List of modules id this module is in conflict with
		$this->langfiles = array('blockedlog');

		$this->warnings_activation = array();
		$this->warnings_activation_ext = array();
		$this->warnings_unactivation = array('FR' => 'BlockedLogAreRequiredByYourCountryLegislation');

		// Currently, activation is not automatic because only companies (in France) making invoices to non business customers must
		// enable this module.
		/*if (getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY')) {
			$tmp = explode(',', getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY'));
			$this->automatic_activation = array();
			foreach($tmp as $countrycodekey)
			{
				$this->automatic_activation[$countrycodekey] = 'BlockedLogActivatedBecauseRequiredByYourCountryLegislation';
			}
		}*/
		//var_dump($this->automatic_activation);

		$this->always_enabled = (isModEnabled('blockedlog')
			&& getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY')
			&& in_array((empty($mysoc->country_code) ? '' : $mysoc->country_code), explode(',', getDolGlobalString('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY')))
			&& $this->alreadyUsed());

		// Constants
		//-----------
		$this->const = array(
			1 => array('BLOCKEDLOG_DISABLE_NOT_ALLOWED_FOR_COUNTRY', 'chaine', 'FR', 'This is list of country code where the module may be mandatory', 0, 'current', 0)
		);

		// New pages on tabs
		// -----------------
		$this->tabs = array();

		// Boxes
		//------
		$this->boxes = array();

		// Permissions
		// -----------------
		$this->rights = array(); // Permission array used by this module

		$r = 1;
		$this->rights[$r][0] = $this->numero + $r; // Permission id (must not be already used)
		$this->rights[$r][1] = 'Read archived events and fingerprints'; // Permission label
		$this->rights[$r][3] = 0; // Permission by default for new user (0/1)
		$this->rights[$r][4] = 'read'; // In php code, permission will be checked by test if ($user->rights->mymodule->level1->level2)
		$this->rights[$r][5] = '';

		// Main menu entries
		// -----------------
		$r = 0;
		$this->menu[$r] = array(
			'fk_menu' => 'fk_mainmenu=tools', // Use 'fk_mainmenu=xxx' or 'fk_mainmenu=xxx,fk_leftmenu=yyy' where xxx is mainmenucode and yyy is a leftmenucode
			'mainmenu' => 'tools',
			'leftmenu' => 'blockedlogbrowser',
			'type' => 'left', // This is a Left menu entry
			'titre' => 'BrowseBlockedLog',
			'prefix' => img_picto('', $this->picto, 'class="paddingright pictofixedwidth"'),
			'url' => '/blockedlog/admin/blockedlog_list.php?mainmenu=tools&leftmenu=blockedlogbrowser',
			'langs' => 'blockedlog', // Lang file to use (without .lang) by module. File must be in langs/code_CODE/ directory.
			'position' => 200,
			'enabled' => 'isModEnabled("blockedlog")', // Define condition to show or hide menu entry. Use '$conf->mymodule->enabled' if entry must be visible if module is enabled. Use '$leftmenu==\'system\'' to show if leftmenu system is selected.
			'perms' => '$user->hasRight("blockedlog", "read")', // Use 'perms'=>'$user->hasRight("mymodule","level1","level2")' if you want your menu with a permission rules
			'target' => '',
			'user' => 2, // 0=Menu for internal users, 1=external users, 2=both
		);
		$r++;
	}


	/**
	 * Check if module was already used before unactivation linked to warnings_unactivation property
	 *
	 * @return	boolean		True if already used, otherwise False
	 */
	public function alreadyUsed()
	{
		require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';

		return isBlockedLogUsed();
	}


	/**
	 *      Function called when module is enabled.
	 *      The init function add constants, boxes, permissions and menus (defined in constructor) into Dolibarr database.
	 *      It also creates data directories.
	 *
	 *      @param      string	$options    Options when enabling module ('', 'noboxes', 'acceptredirect', 'forceinit')
	 *      @return     int             	1 if OK, 0 if KO
	 */
	public function init($options = '')
	{
		global $conf, $langs, $mysoc, $user;

		$sql = array();

		// Detect minimal version of PHP
		if (version_compare(PHP_VERSION, '7.0.0') < 0) {
			$errmsg = 'Error: You are using a too low version of PHP';
			dol_syslog($errmsg, LOG_ERR);
			$this->error = $errmsg;
			return 0;
		}

		// Clear cache
		unset($_SESSION['obfuscationkey_'.((int) $conf->entity)]);
		unset($conf->cache['obfuscationkey_'.((int) $conf->entity)]);

		require_once DOL_DOCUMENT_ROOT . '/blockedlog/class/blockedlog.class.php';
		$b = new BlockedLog($this->db);

		// any value of $options except 'acceptredirect' will bypass this redirection
		if (isALNEQualifiedVersion(1, 1) && $options == 'acceptredirect') {		// Redirect done for all french companies, even if not assujeti.
			// We first switch on registration page
			header("Location: ".DOL_URL_ROOT.'/blockedlog/admin/registration.php?origin=initmodule&withtab=0');
			exit;
		}

		// Check the context of running company is defined
		if (empty($mysoc->country_code)) {
			$errmsg = 'Error: The context of the running company is not defined';
			dol_syslog($errmsg, LOG_ERR);
			$this->error = $errmsg;
			return 0;
		}

		// Check that the HTTPS is forced
		$s = $b->canBeEnabled();
		if ($s) {	// Activation not allowed
			$this->error = $s;
			return 0;
		}

		// If we are here, it means the registration has been done, we can activate the module (this means creating a HMAC key).

		$this->db->begin();

		$error = 0;

		// Generate and save the HMAC key if it does not exists yet
		$hmac_encoded_secret_key = $b->getEncodedHMACSecretKey();

		if (empty($hmac_encoded_secret_key)) {
			// No HMAC key yet, we generate one.
			$randomsecret = bin2hex(random_bytes(32)); 	// 64 char hex - 256 bits

			$hmac_secret_key = 'BLOCKEDLOGHMAC'.$randomsecret;		// Example: 'BLOCKEDLOGHMACY3Ewx37RXbSd8gL9JV8p7Wqw7qvq2K2A'
			//$hmac_secret_key = 'BLOCKEDLOGHMACY3Ewx37RXbSd8gL9JV8p7Wqw7qvq2K2A';

			$obfuscationkey = '';
			if (isALNERunningVersion(1) && $mysoc->country_code == 'FR') {
				try {
					$obfuscationkey = $b->getObfuscationKey();	// Get the obfuscation key from memory or remote server. If not found, we retrieve it.
					//$obfuscationkey = '';		// Uncomment this to test if obfuscation key can't be retrieved.
				} catch (Exception $e) {
					$error++;
					setEventMessages($e->getMessage(), null, 'errors');
					$obfuscationkey = '';
				}

				if (empty($obfuscationkey)) {
					$error++;
					$url_for_ping = getDolGlobalString('MAIN_URL_FOR_PING', "https://ping.dolibarr.org/");
					setEventMessages($langs->trans('FailedToGetRemoteObfuscationKeyReTryLater', $url_for_ping), null, 'errors');
				}

				if (!$error) {
					// Save HMAC key to obfuscate it with the $obfuscationkey
					//$result = dolibarr_set_const($this->db, 'BLOCKEDLOG_HMAC_KEY', $hmac_secret_key, 'chaine', 0, 'The secret key for HMAC used for blockedlog record', 0);	// Will encrypt the value using dolCrypt and store it.
					$result = $b->saveHMACSecretKey($hmac_secret_key, 'dolobfuscationv1-'.$mysoc->idprof1, $obfuscationkey); 	// gitleaks:allow
					if ($result < 0) {
						$error++;
						setEventMessages($b->error, $b->errors, 'errors');
					}
				}
			} else {
				$result = $b->saveHMACSecretKey($hmac_secret_key, 'dolcrypt'); 	// gitleaks:allow
				if ($result < 0) {
					$error++;
					setEventMessages($b->error, $b->errors, 'errors');
				}
			}
		} else {
			// This case should not happen. If using a certified version, the module can't be disabled and reinitialized, so
			// we should not reach this code. In case it happens in future (or if using F5 just after enabling module), we reach this protection
			// that check everything is still ok and report a warning if not.

			// Here we have the obfuscated value of BLOCKEDLOG_HMAC_KEY in $hmac_encoded_secret_key. We need to unobfuscate it.
			$hmac_secret_key = '';
			try {
				$hmac_secret_key = $b->getClearHMACSecretKey($hmac_encoded_secret_key);		// Note: On network trouble, an Exception is thrown to the caller
			} catch (Exception $e) {
				$firsterrormessage = $e->getMessage();

				// Another chance to get HMAC when saved with old obfuscation method (dolcrypt) - Migration will be done at next writing.
				$hmac_encoded_secret_key_alt = $b->getEncodedHMACSecretKey(1, 1);
				if (!empty($hmac_encoded_secret_key_alt)) {
					try {
						$hmac_secret_key_alt = $b->getClearHMACSecretKey($hmac_encoded_secret_key_alt);		// Note: On network trouble, an Exception is thrown to the caller

						if (preg_match('/^BLOCKEDLOGHMAC/', (string) $hmac_secret_key_alt)) {
							$hmac_secret_key = $hmac_secret_key_alt;
							$firsterrormessage = '';
						}
					} catch (Exception $e) {
						if (empty($firsterrormessage)) {
							$firsterrormessage = $e->getMessage();
						}
					}
				} else {
					if (empty($firsterrormessage)) {
						$firsterrormessage = $e->getMessage();
					}
				}

				if ($firsterrormessage) {	// If error
					$error++;
					$this->error = 'modBlockLog init Error: '.$firsterrormessage.'. ';
				}
			}
			if (! preg_match('/^BLOCKEDLOGHMAC/', $hmac_secret_key)) {
				$error++;
				$this->error .= 'modBlockedLog init Error: Failed to decode the crypted value of the parameter BLOCKEDLOG_HMAC_KEY '.$hmac_encoded_secret_key.' using the remote obfuscation key. The value was found in llx_const table but decoding with obfuscation key failed. May be the remote server to get the obfuscation key to decode it was offline.';
				$this->error .= ' If you don\'t use the Unalterable Log module, you can also remove the BLOCKEDLOG_HMAC_KEY entry from llx_const table. If you use the Unalterable Log, this is not possible because this will invalidate all past record.';
			}
			/*
			if (preg_match('/^dolobfuscationv1/', $hmac_encoded_secret_key)) {
				// New method
				$obfuscationkey = '';
				try {
					dol_syslog("mysoc->profid1 = ".$mysoc->idprof1);
					$obfuscationkey = $b->getObfuscationKey();	// Get the obfuscation key from memory or remote server. If not found, we retrieve it.
					//$obfuscationkey = '';		// Uncomment this to test if obfuscation key can't be retrieved.
				} catch (Exception $e) {
					$error++;
					$this->error = $e->getMessage();
					$obfuscationkey = '';
				}

				if (empty($obfuscationkey)) {
					$error++;
					$url_for_ping = getDolGlobalString('MAIN_URL_FOR_PING', "https://ping.dolibarr.org/");
					$this->error = $langs->trans('FailedToGetRemoteObfuscationKeyReTryLater', $url_for_ping);
				}

				$hmac_secret_key = dolDecrypt($hmac_encoded_secret_key, $obfuscationkey);

				if (! preg_match('/^BLOCKEDLOGHMAC/', $hmac_secret_key)) {
					$error++;
					$this->error = 'modBlockedLog init Error: Failed to decode the crypted value of the parameter BLOCKEDLOG_HMAC_KEY '.$hmac_encoded_secret_key.' using the remote obfuscation key. The value was found in llx_const table but decoding with '.$obfuscationkey.' failed. May be the remote server to get the obfucation key to decode it was offline.';
					$this->error .= 'If you don\'t use the Unalterable Log module, you can also remove the BLOCKEDLOG_HMAC_KEY entry from llx_const table. If you use the Unalterable Log, this is not possible because this will invalidate all past record.';
				}
			} else {
				// Old method for backward compatibility
				// Note: The migration of the way to store the HMAC key from old method to the new one will be done automatically at next recording by buildFinalSignatureHash()
				$hmac_secret_key = dolDecrypt($hmac_encoded_secret_key);

				if (! preg_match('/^BLOCKEDLOGHMAC/', $hmac_secret_key)) {
					$error++;
					$this->error = 'modBlockedLog init Error: Failed to decode the crypted value of the parameter BLOCKEDLOG_HMAC_KEY '.$hmac_encoded_secret_key.' using the $dolibarr_main_crypt_key. The value was found in llx_const table but decoding failed. May be the database data were restored onto another environment and the coding/decoding key $dolibarr_main_dolcrypt_key was not restored with the same value in conf.php file.';
					$this->error .= 'Restore the value of $dolibarr_main_crypt_key that was used for encryption in database and restart the migration.';
					$this->error .= 'If you don\'t use the Unalterable Log module, you can also remove the BLOCKEDLOG_HMAC_KEY entry from llx_const table. If you use the Unalterable Log, this is not possible because this will invalidate all past record.';
				}
			}
			*/
		}

		if ($error) {
			$this->db->rollback();
			return 0;
		} else {
			$this->db->commit();
		}


		// We add an entry to show we enable the module

		$object = new stdClass();
		$object->id = 0;
		$object->element = 'module';
		$object->ref = 'systemevent';
		$object->entity = $conf->entity;
		$object->date = dol_now();
		$object->label = 'Module enabled';

		// Add first entry in unalterable Log to track that module was activated
		$action = 'MODULE_SET';
		$result = $b->setObjectData($object, $action, 0, $user, 0);

		if ($result < 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return 0;
		}

		$this->db->begin();

		$res = $b->create($user);
		if ($res <= 0) {
			$this->db->rollback();

			$this->error = $b->error;
			$this->errors = $b->errors;
			return $res;
		}

		$resinit = $this->_init($sql, $options);
		if ($resinit <= 0) {
			$this->db->rollback();

			return $resinit;
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * Function called when module is disabled.
	 * The remove function removes tabs, constants, boxes, permissions and menus from Dolibarr database.
	 * Data directories are not deleted
	 *
	 * @param      string	$options    Options when enabling module ('', 'noboxes', 'forcedisable')
	 * @return     int             		1 if OK, 0 if KO to cancel all actions
	 */
	public function remove($options = '')
	{
		global $conf, $user;

		$sql = array();

		// If already used, we add an entry to show we enable module
		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
		$b = new BlockedLog($this->db);

		dol_syslog("modBlockedLog::remove option=".$options, LOG_DEBUG);

		$object = new stdClass();
		$object->id = 1;
		$object->element = 'module';
		$object->ref = 'systemevent';
		$object->entity = $conf->entity;
		$object->date = dol_now();
		$object->label = 'Module disabled';

		// Add entry in unalterable Log to track that module was activated
		$action = 'MODULE_RESET';
		$result = $b->setObjectData($object, $action, 0, $user, 0);
		if ($result < 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return 0;
		}

		if ($b->alreadyUsed(1)) {
			// Unalterable log was already used.
			if ($options != 'forcedisable' && !$b->canBeDisabled()) {
				// Case we refuse to disable it
				global $langs;
				$this->error = $langs->trans('DisablingBlockedLogIsNotallowedOnceUsedExceptOnFullreset', $langs->transnoentitiesnoconv('BlockedLog'));
				return 0;
			} else {
				// Case we disable it with a log
				$res = $b->create($user, '0000000000'); // If already used for something else than SET or UNSET, we log with error
			}
		} else {
			$res = $b->create($user);
		}
		if ($res <= 0) {
			$this->error = $b->error;
			$this->errors = $b->errors;
			return $res;
		}

		return $this->_remove($sql, $options);
	}


	/**
	 * Overwrite the common getDesc() method
	 *
	 * @param 	int<0,1>	$foruseinpopupdesc  	If 1, we return a short description for use into popup window
	 * @return 	string  							Translated module description
	 */
	public function getDesc($foruseinpopupdesc = 0)
	{
		global $langs, $mysoc;
		$langs->load("admin");

		// If module description translation exists
		$s = $langs->transnoentitiesnoconv("Module".$this->numero."Desc");

		if ($foruseinpopupdesc) {
			$langs->load("blockedlog");
			$s .= '<br>';

			// Special message for France
			if ($mysoc->country_code == 'FR') {
				$islne = isALNEQualifiedVersion(1, 1);

				$versionbadge = '<span class="badge-text badge-secondary">'.getBlockedLogVersionToShow();
				/*
				if ($mysoc->country_code == 'FR' && !constant('CERTIF_LNE')) {
					// Can add an edditional mention
					$versionbadge .= ' - '.$langs->trans("NeedAThirdPartyStatement");
				}
				*/
				$versionbadge .= '</span>';
				if ($islne) {
					if (preg_match('/\-/', DOL_VERSION)) {
						// This is an alpha or beta version
						$s .= info_admin($langs->trans("LNECandidateVersionForCertificationFR", $versionbadge), 0, 0, 'info');
					} else {
						$s .= info_admin($langs->trans("LNECertifiedVersionFR", $versionbadge), 0, 0, 'info');
					}
				} else {
					$s .= info_admin($langs->trans("NotCertifiedVersionFR", $versionbadge), 0, 0, 'warning');
				}
			}

			// Add warning to advice users to make regularly archives
			if (in_array($mysoc->country_code, array('FR'))) {
				$s .= info_admin($langs->trans("UnalterableLogTool1FR"), 0, 0, 'warning');
			}
		}

		return $s;
	}
}
