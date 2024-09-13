<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2004       Benoit Mortier          <benoit.mortier@opensides.be>
 * Copyright (C) 2004       Sebastien DiCintio      <sdicintio@ressource-toi.org>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *       \file      htdocs/install/step5.php
 *       \ingroup   install
 *       \brief     Last page of upgrade / install process
 *
 *       This page is called with parameter action=set by step4.php or action=upgrade by upgrade2.php
 *       For installation:
 *         It creates the login admin and set the MAIN_SECURITY_SALT to a random value.
 *         It set the value for MAIN_VERSION_LAST_INSTALL
 *         It activates some modules
 *         It creates the install.lock and shows the final message.
 *       For upgrade:
 *         It updates the value for MAIN_VERSION_LAST_UPGRADE.
 *         It (re)creates the install.lock and shows the final message.
 */

define('ALLOWED_IF_UPGRADE_UNLOCK_FOUND', 1);
include_once 'inc.php';
if (file_exists($conffile)) {
	include_once $conffile;
}
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';
require_once $dolibarr_main_document_root.'/core/lib/security.lib.php'; // for dol_hash
require_once $dolibarr_main_document_root.'/core/lib/functions2.lib.php';

global $langs;

$versionfrom = GETPOST("versionfrom", 'alpha', 3) ? GETPOST("versionfrom", 'alpha', 3) : (empty($argv[1]) ? '' : $argv[1]);
$versionto = GETPOST("versionto", 'alpha', 3) ? GETPOST("versionto", 'alpha', 3) : (empty($argv[2]) ? '' : $argv[2]);
$setuplang = GETPOST('selectlang', 'aZ09', 3) ? GETPOST('selectlang', 'aZ09', 3) : (empty($argv[3]) ? 'auto' : $argv[3]);
$langs->setDefaultLang($setuplang);
$action = GETPOST('action', 'alpha') ? GETPOST('action', 'alpha') : (empty($argv[4]) ? '' : $argv[4]);

// Define targetversion used to update MAIN_VERSION_LAST_INSTALL for first install
// or MAIN_VERSION_LAST_UPGRADE for upgrade.
$targetversion = DOL_VERSION; // If it's latest upgrade
if (!empty($action) && preg_match('/upgrade/i', $action)) {
	// If it's an old upgrade
	$tmp = explode('_', $action, 2);
	if ($tmp[0] == 'upgrade') {
		if (!empty($tmp[1])) {
			$targetversion = $tmp[1]; // if $action = 'upgrade_6.0.0-beta', we use '6.0.0-beta'
		} else {
			$targetversion = DOL_VERSION; // if $action = 'upgrade', we use DOL_VERSION
		}
	}
}

$langs->loadLangs(array("admin", "install"));

$login = GETPOST('login', 'alpha') ? GETPOST('login', 'alpha') : (empty($argv[5]) ? '' : $argv[5]);
$pass = GETPOST('pass', 'alpha') ? GETPOST('pass', 'alpha') : (empty($argv[6]) ? '' : $argv[6]);
$pass_verif = GETPOST('pass_verif', 'alpha') ? GETPOST('pass_verif', 'alpha') : (empty($argv[7]) ? '' : $argv[7]);
$force_install_lockinstall = (int) (!empty($force_install_lockinstall) ? $force_install_lockinstall : (GETPOST('installlock', 'aZ09') ? GETPOST('installlock', 'aZ09') : (empty($argv[8]) ? '' : $argv[8])));

$success = 0;

$useforcedwizard = false;
$forcedfile = "./install.forced.php";
if ($conffile == "/etc/dolibarr/conf.php") {
	$forcedfile = "/etc/dolibarr/install.forced.php";
}
if (@file_exists($forcedfile)) {
	$useforcedwizard = true;
	include_once $forcedfile;
	// If forced install is enabled, replace post values. These are empty because form fields are disabled.
	if ($force_install_noedit == 2) {
		if (!empty($force_install_dolibarrlogin)) {
			$login = $force_install_dolibarrlogin;
		}
	}
}

dolibarr_install_syslog("--- step5: entering step5.php page ".$versionfrom." ".$versionto);

$error = 0;

/*
 *	Actions
 */

// If install, check password and password_verification used to create admin account
if ($action == "set") {
	if ($pass != $pass_verif) {
		header("Location: step4.php?error=1&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
		exit;
	}

	if (dol_strlen(trim($pass)) == 0) {
		header("Location: step4.php?error=2&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
		exit;
	}

	if (dol_strlen(trim($login)) == 0) {
		header("Location: step4.php?error=3&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
		exit;
	}
}


/*
 *	View
 */

$morehtml = '';

pHeader($langs->trans("DolibarrSetup").' - '.$langs->trans("SetupEnd"), "step5", 'set', '', '', 'main-inside main-inside-borderbottom');
print '<br>';

// Test if we can run a first install process
if (empty($versionfrom) && empty($versionto) && !is_writable($conffile)) {
	print $langs->trans("ConfFileIsNotWritable", $conffiletoshow);
	pFooter(1, $setuplang, 'jscheckparam');
	exit;
}

// Ensure $modulesdir is set and array
if (!isset($modulesdir) || !is_array($modulesdir)) {
	$modulesdir = array();
}

if ($action == "set" || empty($action) || preg_match('/upgrade/i', $action)) {
	$error = 0;

	// If password is encoded, we decode it
	if ((!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) || !empty($dolibarr_main_db_encrypted_pass)) {
		require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
		if (!empty($dolibarr_main_db_pass) && preg_match('/crypted:/i', $dolibarr_main_db_pass)) {
			$dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
			$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
			$dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass; // We need to set this as it is used to know the password was initially encrypted
		} else {
			$dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
		}
	}

	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;
	$conf->db->dolibarr_main_db_encryption = isset($dolibarr_main_db_encryption) ? $dolibarr_main_db_encryption : 0;
	$conf->db->dolibarr_main_db_cryptkey = isset($dolibarr_main_db_cryptkey) ? $dolibarr_main_db_cryptkey : '';

	$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

	// Create the global $hookmanager object
	include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
	$hookmanager = new HookManager($db);

	$ok = 0;

	// If first install
	if ($action == "set") {
		// Active module user
		$modName = 'modUser';
		$file = $modName.".class.php";
		dolibarr_install_syslog('step5: load module user '.DOL_DOCUMENT_ROOT."/core/modules/".$file, LOG_INFO);
		include_once DOL_DOCUMENT_ROOT."/core/modules/".$file;
		$objMod = new $modName($db);
		$result = $objMod->init();
		if (!$result) {
			print "ERROR: failed to init module file = ".$file;
		}

		if ($db->connected) {
			$conf->setValues($db);
			// Reset forced setup after the setValues
			if (defined('SYSLOG_FILE')) {
				$conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
			}
			$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

			// Create admin user
			include_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

			// Set default encryption to yes, generate a salt and set default encryption algorithm (but only if there is no user yet into database)
			$sql = "SELECT u.rowid, u.pass, u.pass_crypted";
			$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
			$resql = $db->query($sql);
			if ($resql) {
				$numrows = $db->num_rows($resql);
				if ($numrows == 0) {
					// Define default setup for password encryption
					dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1", 'chaine', 0, '', $conf->entity);
					dolibarr_set_const($db, "MAIN_SECURITY_SALT", dol_print_date(dol_now(), 'dayhourlog'), 'chaine', 0, '', 0); // All entities
					if (function_exists('password_hash')) {
						dolibarr_set_const($db, "MAIN_SECURITY_HASH_ALGO", 'password_hash', 'chaine', 0, '', 0); // All entities
					} else {
						dolibarr_set_const($db, "MAIN_SECURITY_HASH_ALGO", 'sha1md5', 'chaine', 0, '', 0); // All entities
					}
				}

				dolibarr_install_syslog('step5: DATABASE_PWD_ENCRYPTED = ' . getDolGlobalString('DATABASE_PWD_ENCRYPTED').' MAIN_SECURITY_HASH_ALGO = ' . getDolGlobalString('MAIN_SECURITY_HASH_ALGO'), LOG_INFO);
			}

			// Create user used to create the admin user
			$createuser = new User($db);
			$createuser->id = 0;
			$createuser->admin = 1;

			// Set admin user
			$newuser = new User($db);
			$newuser->lastname = 'SuperAdmin';
			$newuser->firstname = '';
			$newuser->login = $login;
			$newuser->pass = $pass;
			$newuser->admin = 1;
			$newuser->entity = 0;

			$conf->global->USER_MAIL_REQUIRED = 0; 			// Force global option to be sure to create a new user with no email
			$conf->global->USER_PASSWORD_GENERATED = '';	// To not use any rule for password validation

			$result = $newuser->create($createuser, 1);
			if ($result > 0) {
				print $langs->trans("AdminLoginCreatedSuccessfuly", $login)."<br>";
				$success = 1;
			} else {
				if ($result == -6) {	//login or email already exists
					dolibarr_install_syslog('step5: AdminLoginAlreadyExists', LOG_WARNING);
					print '<br><div class="warning">'.$newuser->error."</div><br>";
					$success = 1;
				} else {
					dolibarr_install_syslog('step5: FailedToCreateAdminLogin '.$newuser->error, LOG_ERR);
					setEventMessages($langs->trans("FailedToCreateAdminLogin").' '.$newuser->error, null, 'errors');
					//header("Location: step4.php?error=3&selectlang=$setuplang".(isset($login) ? '&login='.$login : ''));
					print '<br><div class="error">'.$langs->trans("FailedToCreateAdminLogin").': '.$newuser->error.'</div><br><br>';
					print $langs->trans("ErrorGoBackAndCorrectParameters").'<br><br>';
				}
			}

			if ($success) {
				// Insert MAIN_VERSION_FIRST_INSTALL in a dedicated transaction. So if it fails (when first install was already done), we can do other following requests.
				$db->begin();
				dolibarr_install_syslog('step5: set MAIN_VERSION_FIRST_INSTALL const to '.$targetversion, LOG_DEBUG);
				$resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name, value, type, visible, note, entity) values(".$db->encrypt('MAIN_VERSION_FIRST_INSTALL').", ".$db->encrypt($targetversion).", 'chaine', 0, 'Dolibarr version when first install', 0)");
				if ($resql) {
					$conf->global->MAIN_VERSION_FIRST_INSTALL = $targetversion;
					$db->commit();
				} else {
					//if (! $resql) dol_print_error($db,'Error in setup program');      // We ignore errors. Key may already exists
					$db->commit();
				}

				$db->begin();

				dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_INSTALL const to '.$targetversion, LOG_DEBUG);
				$resql = $db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')." = 'MAIN_VERSION_LAST_INSTALL'");
				if (!$resql) {
					dol_print_error($db, 'Error in setup program');
				}
				$resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_VERSION_LAST_INSTALL').", ".$db->encrypt($targetversion).", 'chaine', 0, 'Dolibarr version when last install', 0)");
				if (!$resql) {
					dol_print_error($db, 'Error in setup program');
				}
				$conf->global->MAIN_VERSION_LAST_INSTALL = $targetversion;

				if ($useforcedwizard) {
					dolibarr_install_syslog('step5: set MAIN_REMOVE_INSTALL_WARNING const to 1', LOG_DEBUG);
					$resql = $db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')." = 'MAIN_REMOVE_INSTALL_WARNING'");
					if (!$resql) {
						dol_print_error($db, 'Error in setup program');
					}
					// The install.lock file is created few lines later if version is last one or if option MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE is on
					/* No need to enable this
					$resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) values(".$db->encrypt('MAIN_REMOVE_INSTALL_WARNING').", ".$db->encrypt(1).", 'chaine', 1, 'Disable install warnings', 0)");
					if (!$resql) {
						dol_print_error($db, 'Error in setup program');
					}
					$conf->global->MAIN_REMOVE_INSTALL_WARNING = 1;
					*/
				}

				// List of modules to enable
				$tmparray = array();

				// If we ask to force some modules to be enabled
				if (!empty($force_install_module)) {
					if (!defined('DOL_DOCUMENT_ROOT') && !empty($dolibarr_main_document_root)) {
						define('DOL_DOCUMENT_ROOT', $dolibarr_main_document_root);
					}

					$tmparray = explode(',', $force_install_module);
				}

				$modNameLoaded = array();

				// Search modules dirs
				$modulesdir[] = $dolibarr_main_document_root.'/core/modules/';

				foreach ($modulesdir as $dir) {
					// Load modules attributes in arrays (name, numero, orders) from dir directory
					//print $dir."\n<br>";
					dol_syslog("Scan directory ".$dir." for module descriptor files (modXXX.class.php)");
					$handle = @opendir($dir);
					if (is_resource($handle)) {
						while (($file = readdir($handle)) !== false) {
							if (is_readable($dir.$file) && substr($file, 0, 3) == 'mod' && substr($file, dol_strlen($file) - 10) == '.class.php') {
								$modName = substr($file, 0, dol_strlen($file) - 10);
								if ($modName) {
									if (!empty($modNameLoaded[$modName])) {   // In cache of already loaded modules ?
										$mesg = "Error: Module ".$modName." was found twice: Into ".$modNameLoaded[$modName]." and ".$dir.". You probably have an old file on your disk.<br>";
										setEventMessages($mesg, null, 'warnings');
										dol_syslog($mesg, LOG_ERR);
										continue;
									}

									try {
										$res = include_once $dir.$file; // A class already exists in a different file will send a non catchable fatal error.
										if (class_exists($modName)) {
											$objMod = new $modName($db);
											$modNameLoaded[$modName] = $dir;
											if (!empty($objMod->enabled_bydefault) && !in_array($file, $tmparray)) {
												$tmparray[] = $file;
											}
										}
									} catch (Exception $e) {
										dol_syslog("Failed to load ".$dir.$file." ".$e->getMessage(), LOG_ERR);
									}
								}
							}
						}
					}
				}

				// Loop on each modules to activate it
				if (!empty($tmparray)) {
					foreach ($tmparray as $modtoactivate) {
						$modtoactivatenew = preg_replace('/\.class\.php$/i', '', $modtoactivate);
						//print $langs->trans("ActivateModule", $modtoactivatenew).'<br>';

						$file = $modtoactivatenew.'.class.php';
						dolibarr_install_syslog('step5: activate module file='.$file);
						$res = dol_include_once("/core/modules/".$file);

						$res = activateModule($modtoactivatenew, 1);
						if (!empty($res['errors'])) {
							print 'ERROR: failed to activateModule() file='.$file;
						}
					}
					//print '<br>';
				}

				// Now delete the flag that say installation is not complete
				dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const');
				$resql = $db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')." = 'MAIN_NOT_INSTALLED'");
				if (!$resql) {
					dol_print_error($db, 'Error in setup program');
				}

				// May fail if parameter already defined
				dolibarr_install_syslog('step5: set the default language');
				$resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name,value,type,visible,note,entity) VALUES (".$db->encrypt('MAIN_LANG_DEFAULT').", ".$db->encrypt($setuplang).", 'chaine', 0, 'Default language', 1)");
				//if (! $resql) dol_print_error($db,'Error in setup program');

				$db->commit();
			}
		} else {
			print $langs->trans("ErrorFailedToConnect")."<br>";
		}
	} elseif (empty($action) || preg_match('/upgrade/i', $action)) {
		// If upgrade
		if ($db->connected) {
			$conf->setValues($db);
			// Reset forced setup after the setValues
			if (defined('SYSLOG_FILE')) {
				$conf->global->SYSLOG_FILE = constant('SYSLOG_FILE');
			}
			$conf->global->MAIN_ENABLE_LOG_TO_HTML = 1;

			// Define if we need to update the MAIN_VERSION_LAST_UPGRADE value in database
			$tagdatabase = false;
			if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE')) {
				$tagdatabase = true; // We don't know what it was before, so now we consider we at the chosen version.
			} else {
				$mainversionlastupgradearray = preg_split('/[.-]/', $conf->global->MAIN_VERSION_LAST_UPGRADE);
				$targetversionarray = preg_split('/[.-]/', $targetversion);
				if (versioncompare($targetversionarray, $mainversionlastupgradearray) > 0) {
					$tagdatabase = true;
				}
			}

			if ($tagdatabase) {
				dolibarr_install_syslog('step5: set MAIN_VERSION_LAST_UPGRADE const to value '.$targetversion);
				$resql = $db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')." = 'MAIN_VERSION_LAST_UPGRADE'");
				if (!$resql) {
					dol_print_error($db, 'Error in setup program');
				}
				$resql = $db->query("INSERT INTO ".MAIN_DB_PREFIX."const(name, value, type, visible, note, entity) VALUES (".$db->encrypt('MAIN_VERSION_LAST_UPGRADE').", ".$db->encrypt($targetversion).", 'chaine', 0, 'Dolibarr version for last upgrade', 0)");
				if (!$resql) {
					dol_print_error($db, 'Error in setup program');
				}
				$conf->global->MAIN_VERSION_LAST_UPGRADE = $targetversion;
			} else {
				dolibarr_install_syslog('step5: we run an upgrade to version '.$targetversion.' but database was already upgraded to ' . getDolGlobalString('MAIN_VERSION_LAST_UPGRADE').'. We keep MAIN_VERSION_LAST_UPGRADE as it is.');

				// Force the delete of the flag that say installation is not complete
				dolibarr_install_syslog('step5: remove MAIN_NOT_INSTALLED const after upgrade process (should not exists but this is a security)');
				$resql = $db->query("DELETE FROM ".MAIN_DB_PREFIX."const WHERE ".$db->decrypt('name')." = 'MAIN_NOT_INSTALLED'");
				if (!$resql) {
					dol_print_error($db, 'Error in setup program');
				}
			}
		} else {
			print $langs->trans("ErrorFailedToConnect")."<br>";
		}
	} else {
		dol_print_error(null, 'step5.php: unknown choice of action');
	}

	$db->close();
}



// Create lock file

// If first install
if ($action == "set") {
	if ($success) {
		if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
			// Install is finished (database is on same version than files)
			print '<br>'.$langs->trans("SystemIsInstalled")."<br>";

			// Create install.lock file
			// No need for the moment to create it automatically, creation by web assistant means permissions are given
			// to the web user, it is better to show a warning to say to create it manually with correct user/permission (not erasable by a web process)
			$createlock = 0;
			if (!empty($force_install_lockinstall) || getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE')) {
				// Install is finished, we create the "install.lock" file, so install won't be possible anymore.
				// TODO Upgrade will be still be possible if a file "upgrade.unlock" is present
				$lockfile = DOL_DATA_ROOT.'/install.lock';
				$fp = @fopen($lockfile, "w");
				if ($fp) {
					if (empty($force_install_lockinstall) || $force_install_lockinstall == 1) {
						$force_install_lockinstall = '444'; // For backward compatibility
					}
					fwrite($fp, "This is a lock file to prevent use of install or upgrade pages (set with permission ".$force_install_lockinstall.")");
					fclose($fp);
					dolChmod($lockfile, $force_install_lockinstall);

					$createlock = 1;
				}
			}
			if (empty($createlock)) {
				print '<div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
			}

			print "<br>";

			print $langs->trans("YouNeedToPersonalizeSetup")."<br><br><br>";

			print '<div class="center">&gt; <a href="../admin/index.php?mainmenu=home&leftmenu=setup'.(isset($login) ? '&username='.urlencode($login) : '').'">';
			print '<span class="fas fa-external-link-alt"></span> '.$langs->trans("GoToSetupArea");
			print '</a></div><br>';
		} else {
			// If here MAIN_VERSION_LAST_UPGRADE is not empty
			print $langs->trans("VersionLastUpgrade").': <b><span class="ok">' . getDolGlobalString('MAIN_VERSION_LAST_UPGRADE').'</span></b><br>';
			print $langs->trans("VersionProgram").': <b><span class="ok">'.DOL_VERSION.'</span></b><br>';
			print $langs->trans("MigrationNotFinished").'<br>';
			print "<br>";

			print '<div class="center"><a href="'.$dolibarr_main_url_root.'/install/index.php">';
			print '<span class="fas fa-link-alt"></span> '.$langs->trans("GoToUpgradePage");
			print '</a></div>';
		}
	}
} elseif (empty($action) || preg_match('/upgrade/i', $action)) {
	// If upgrade
	if (!getDolGlobalString('MAIN_VERSION_LAST_UPGRADE') || ($conf->global->MAIN_VERSION_LAST_UPGRADE == DOL_VERSION)) {
		// Upgrade is finished (database is on the same version than files)
		print '<img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/checklist.svg" width="20" alt="Configuration">';
		print ' <span class="valignmiddle">'.$langs->trans("SystemIsUpgraded")."</span><br>";

		// Create install.lock file if it does not exists.
		// Note: it should always exists. A better solution to allow upgrade will be to add an upgrade.unlock file
		$createlock = 0;
		if (!empty($force_install_lockinstall) || getDolGlobalString('MAIN_ALWAYS_CREATE_LOCK_AFTER_LAST_UPGRADE')) {
			// Upgrade is finished, we modify the lock file
			$lockfile = DOL_DATA_ROOT.'/install.lock';
			$fp = @fopen($lockfile, "w");
			if ($fp) {
				if (empty($force_install_lockinstall) || $force_install_lockinstall == 1) {
					$force_install_lockinstall = '444'; // For backward compatibility
				}
				fwrite($fp, "This is a lock file to prevent use of install or upgrade pages (set with permission ".$force_install_lockinstall.")");
				fclose($fp);
				dolChmod($lockfile, $force_install_lockinstall);

				$createlock = 1;
			}
		}
		if (empty($createlock)) {
			print '<br><div class="warning">'.$langs->trans("WarningRemoveInstallDir")."</div>";
		}

		// Delete the upgrade.unlock file it it exists
		$unlockupgradefile = DOL_DATA_ROOT.'/upgrade.unlock';
		dol_delete_file($unlockupgradefile, 0, 0, 0, null, false, 0);

		print "<br>";

		$morehtml = '<br><div class="center"><a class="buttonGoToupgrade" href="../index.php?mainmenu=home'.(isset($login) ? '&username='.urlencode($login) : '').'">';
		$morehtml .= '<span class="fas fa-link-alt"></span> '.$langs->trans("GoToDolibarr").'...';
		$morehtml .= '</a></div><br>';
	} else {
		// If here MAIN_VERSION_LAST_UPGRADE is not empty
		print $langs->trans("VersionLastUpgrade").': <b><span class="ok">' . getDolGlobalString('MAIN_VERSION_LAST_UPGRADE').'</span></b><br>';
		print $langs->trans("VersionProgram").': <b><span class="ok">'.DOL_VERSION.'</span></b>';

		print "<br>";

		$morehtml = '<br><div class="center"><a class="buttonGoToupgrade" href="../install/index.php">';
		$morehtml .= '<span class="fas fa-link-alt"></span> '.$langs->trans("GoToUpgradePage");
		$morehtml .= '</a></div>';
	}
} else {
	dol_print_error(null, 'step5.php: unknown choice of action='.$action.' in create lock file seaction');
}

// Clear cache files
clearstatcache();

$ret = 0;
if ($error && isset($argv[1])) {
	$ret = 1;
}
dolibarr_install_syslog("Exit ".$ret);

dolibarr_install_syslog("--- step5: Dolibarr setup finished");

pFooter(1, $setuplang, '', 0, $morehtml);

// Return code if ran from command line
if ($ret) {
	exit($ret);
}
