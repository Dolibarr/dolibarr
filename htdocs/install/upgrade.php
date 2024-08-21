<?php
/* Copyright (C) 2004       Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2018  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2015-2016  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *
 * Upgrade scripts can be ran from command line with syntax:
 *
 * cd htdocs/install
 * php upgrade.php 3.4.0 3.5.0 [dirmodule|ignoredbversion]
 * php upgrade2.php 3.4.0 3.5.0 [MODULE_NAME1_TO_ENABLE,MODULE_NAME2_TO_ENABLE]
 *
 * And for final step:
 * php step5.php 3.4.0 3.5.0
 *
 * Option 'dirmodule' allows to provide a path for an external module, so we migrate from command line using a script from a module.
 * Option 'ignoredbversion' allows to run migration even if database version does not match start version of migration
 * Return code is 0 if OK, >0 if error
 */

/**
 *		\file       htdocs/install/upgrade.php
 *      \brief      Run migration script
 */

define('ALLOWED_IF_UPGRADE_UNLOCK_FOUND', 1);
include_once 'inc.php';
if (!file_exists($conffile)) {
	print 'Error: Dolibarr config file was not found. This may means that Dolibarr is not installed yet. Please call the page "/install/index.php" instead of "/install/upgrade.php").';
}
require_once $conffile;
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';

global $langs;

$grant_query = '';
$step = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err = error_reporting();
error_reporting(0);
@set_time_limit(300);
error_reporting($err);


$setuplang = GETPOST("selectlang", 'aZ09', 3) ? GETPOST("selectlang", 'aZ09', 3) : 'auto';
$langs->setDefaultLang($setuplang);
$versionfrom = GETPOST("versionfrom", 'alpha', 3) ? GETPOST("versionfrom", 'alpha', 3) : (empty($argv[1]) ? '' : $argv[1]);
$versionto = GETPOST("versionto", 'alpha', 3) ? GETPOST("versionto", 'alpha', 3) : (empty($argv[2]) ? '' : $argv[2]);
$dirmodule = ((GETPOST("dirmodule", 'alpha', 3) && GETPOST("dirmodule", 'alpha', 3) != 'ignoredbversion')) ? GETPOST("dirmodule", 'alpha', 3) : ((empty($argv[3]) || $argv[3] == 'ignoredbversion') ? '' : $argv[3]);
$ignoredbversion = (GETPOST('ignoredbversion', 'alpha', 3) == 'ignoredbversion') ? GETPOST('ignoredbversion', 'alpha', 3) : ((empty($argv[3]) || $argv[3] != 'ignoredbversion') ? '' : $argv[3]);

$langs->loadLangs(array("admin", "install", "other", "errors"));

if ($dolibarr_main_db_type == "mysqli") {
	$choix = 1;
}
if ($dolibarr_main_db_type == "pgsql") {
	$choix = 2;
}
if ($dolibarr_main_db_type == "mssql") {
	$choix = 3;
}


dolibarr_install_syslog("--- upgrade: entering upgrade.php page ".$versionfrom." ".$versionto);
if (!is_object($conf)) {
	dolibarr_install_syslog("upgrade: conf file not initialized", LOG_ERR);
}


/*
 * View
 */

if (!$versionfrom && !$versionto) {
	print 'Error: Parameter versionfrom or versionto missing.'."\n";
	print 'Upgrade must be ran from command line with parameters or called from page install/index.php (like a first install)'."\n";
	// Test if batch mode
	$sapi_type = php_sapi_name();
	$script_file = basename(__FILE__);
	$path = __DIR__.'/';
	if (substr($sapi_type, 0, 3) == 'cli') {
		print 'Syntax from command line: '.$script_file." x.y.z a.b.c\n";
	}
	exit;
}


pHeader('', "upgrade2", GETPOST('action', 'aZ09'), 'versionfrom='.$versionfrom.'&versionto='.$versionto, '', 'main-inside main-inside-borderbottom');

$actiondone = 0;

// Action to launch the migrate script
if (!GETPOST('action', 'aZ09') || preg_match('/upgrade/i', GETPOST('action', 'aZ09'))) {
	$actiondone = 1;

	print '<h3><img class="valignmiddle inline-block paddingright" src="../theme/common/octicons/build/svg/database.svg" width="20" alt="Database"> ';
	print '<span class="inline-block">'.$langs->trans("DatabaseMigration").'</span></h3>';

	print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
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

	// $conf is already instantiated inside inc.php
	$conf->db->type = $dolibarr_main_db_type;
	$conf->db->host = $dolibarr_main_db_host;
	$conf->db->port = $dolibarr_main_db_port;
	$conf->db->name = $dolibarr_main_db_name;
	$conf->db->user = $dolibarr_main_db_user;
	$conf->db->pass = $dolibarr_main_db_pass;

	// Load type and crypt key
	if (empty($dolibarr_main_db_encryption)) {
		$dolibarr_main_db_encryption = 0;
	}
	$conf->db->dolibarr_main_db_encryption = $dolibarr_main_db_encryption;
	if (empty($dolibarr_main_db_cryptkey)) {
		$dolibarr_main_db_cryptkey = '';
	}
	$conf->db->dolibarr_main_db_cryptkey = $dolibarr_main_db_cryptkey;

	$db = getDoliDBInstance($conf->db->type, $conf->db->host, $conf->db->user, $conf->db->pass, $conf->db->name, (int) $conf->db->port);

	// Create the global $hookmanager object
	include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
	$hookmanager = new HookManager($db);

	if ($db->connected) {
		print '<tr><td class="nowrap">';
		print $langs->trans("ServerConnection")." : ".$dolibarr_main_db_host.'</td><td class="right"><span class="neutral">'.$langs->trans("OK").'</span></td></tr>'."\n";
		dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ServerConnection").": $dolibarr_main_db_host ".$langs->transnoentities("OK"));
		$ok = 1;
	} else {
		print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name).'</td><td class="right"><span class="error">'.$langs->transnoentities("Error")."</span></td></tr>\n";
		dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name));
		$ok = 0;
	}

	if ($ok) {
		if ($db->database_selected) {
			print '<tr><td class="nowrap">';
			print $langs->trans("DatabaseConnection")." : ".$dolibarr_main_db_name.'</td><td class="right"><span class="neutral">'.$langs->trans("OK")."</span></td></tr>\n";
			dolibarr_install_syslog("upgrade: Database connection successful: ".$dolibarr_main_db_name);
			$ok = 1;
		} else {
			print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name).'</td><td class="right"><span class="ok">'.$langs->trans("Error")."</span></td></tr>\n";
			dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name));
			$ok = 0;
		}
	}

	// Affiche version
	if ($ok) {
		$version = $db->getVersion();
		$versionarray = $db->getVersionArray();
		print '<tr><td>'.$langs->trans("ServerVersion").'</td>';
		print '<td class="right">'.$version.'</td></tr>';
		dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ServerVersion").": ".$version);
		if ($db->type == 'mysqli' && function_exists('mysqli_get_charset')) {
			$tmparray = $db->db->get_charset();
			print '<tr><td>'.$langs->trans("ClientCharset").'</td>';
			print '<td class="right">'.$tmparray->charset.'</td></tr>';
			dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ClientCharset").": ".$tmparray->charset);
			print '<tr><td>'.$langs->trans("ClientSortingCharset").'</td>';
			print '<td class="right">'.$tmparray->collation.'</td></tr>';
			dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ClientCollation").": ".$tmparray->collation);
		}

		// Test database version requirement
		$versionmindb = explode('.', $db::VERSIONMIN);
		//print join('.',$versionarray).' - '.join('.',$versionmindb);
		if (count($versionmindb) && count($versionarray)
			&& versioncompare($versionarray, $versionmindb) < 0) {
			// Warning: database version too low.
			print "<tr><td>".$langs->trans("ErrorDatabaseVersionTooLow", implode('.', $versionarray), implode('.', $versionmindb)).'</td><td class="right"><span class="error">'.$langs->trans("Error")."</span></td></tr>\n";
			dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorDatabaseVersionTooLow", implode('.', $versionarray), implode('.', $versionmindb)));
			$ok = 0;
		}

		// Test database version is not forbidden for migration
		if (empty($ignoredbversion)) {
			$dbversion_disallowed = array(
				array('type'=>'mysql', 'version'=>array(5, 5, 40)),
				array('type'=>'mysqli', 'version'=>array(5, 5, 40)) //,
				//array('type'=>'mysql','version'=>array(5,5,41)),
				//array('type'=>'mysqli','version'=>array(5,5,41))
			);
			$listofforbiddenversion = '';
			foreach ($dbversion_disallowed as $dbversion_totest) {
				if ($dbversion_totest['type'] == $db->type) {
					$listofforbiddenversion .= ($listofforbiddenversion ? ', ' : '').implode('.', $dbversion_totest['version']);
				}
			}
			foreach ($dbversion_disallowed as $dbversion_totest) {
				//print $db->type.' - '.join('.',$versionarray).' - '.versioncompare($dbversion_totest['version'],$versionarray)."<br>\n";
				if ($dbversion_totest['type'] == $db->type
					&& (versioncompare($dbversion_totest['version'], $versionarray) == 0 || versioncompare($dbversion_totest['version'], $versionarray) <= -4 || versioncompare($dbversion_totest['version'], $versionarray) >= 4)
				) {
					// Warning: database version too low.
					print '<tr><td><div class="warning">'.$langs->trans("ErrorDatabaseVersionForbiddenForMigration", implode('.', $versionarray), $listofforbiddenversion)."</div></td><td class=\"right\">".$langs->trans("Error")."</td></tr>\n";
					dolibarr_install_syslog("upgrade: ".$langs->transnoentities("ErrorDatabaseVersionForbiddenForMigration", implode('.', $versionarray), $listofforbiddenversion));
					$ok = 0;
					break;
				}
			}
		}
	}

	// Force l'affichage de la progression
	if ($ok) {
		print '<tr><td colspan="2"><span class="opacitymedium messagebepatient">'.$langs->trans("PleaseBePatient").'</span></td></tr>';
		print '</table>';

		flush();

		print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
	}


	/*
	 * Remove deprecated indexes and constraints for Mysql without knowing its name
	 */
	if ($ok && preg_match('/mysql/', $db->type)) {
		$versioncommande = array(4, 0, 0);
		if (count($versioncommande) && count($versionarray)
		&& versioncompare($versioncommande, $versionarray) <= 0) {	// Si mysql >= 4.0
			dolibarr_install_syslog("Clean database from bad named constraints");

			// Suppression vieilles contraintes sans noms et en doubles
			// Les contraintes indesirables ont un nom qui commence par 0_ ou se determine par ibfk_999
			$listtables = array(
								MAIN_DB_PREFIX.'adherent_options',
								MAIN_DB_PREFIX.'category_bankline',
								MAIN_DB_PREFIX.'c_ecotaxe',
								MAIN_DB_PREFIX.'c_methode_commande_fournisseur', // table renamed
								MAIN_DB_PREFIX.'c_input_method'
			);

			$listtables = $db->DDLListTables($conf->db->name, '');

			foreach ($listtables as $val) {
				// Database prefix filter
				if (preg_match('/^'.MAIN_DB_PREFIX.'/', $val)) {
					//print "x".$val."<br>";
					$sql = "SHOW CREATE TABLE ".$val;
					$resql = $db->query($sql);
					if ($resql) {
						$values = $db->fetch_array($resql);
						if (is_array($values)) {
							$i = 0;
							$createsql = $values[1];
							$reg = array();
							while (preg_match('/CONSTRAINT `(0_[0-9a-zA-Z]+|[_0-9a-zA-Z]+_ibfk_[0-9]+)`/i', $createsql, $reg) && $i < 100) {
								$sqldrop = "ALTER TABLE ".$val." DROP FOREIGN KEY ".$reg[1];
								$resqldrop = $db->query($sqldrop);
								if ($resqldrop) {
									print '<tr><td colspan="2">'.$sqldrop.";</td></tr>\n";
								}
								$createsql = preg_replace('/CONSTRAINT `'.$reg[1].'`/i', 'XXX', $createsql);
								$i++;
							}
						}
						$db->free($resql);
					} else {
						if ($db->lasterrno() != 'DB_ERROR_NOSUCHTABLE') {
							print '<tr><td colspan="2"><span class="error">'.dol_escape_htmltag($sql).' : '.dol_escape_htmltag($db->lasterror())."</span></td></tr>\n";
						}
					}
				}
			}
		}
	}

	/*
	 *	Load sql files
	 */
	if ($ok) {
		$dir = "mysql/migration/"; // We use mysql migration scripts whatever is database driver
		if (!empty($dirmodule)) {
			$dir = dol_buildpath('/'.$dirmodule.'/sql/', 0);
		}
		dolibarr_install_syslog("Scan sql files for migration files in ".$dir);

		// Clean last part to exclude minor version x.y.z -> x.y
		$newversionfrom = preg_replace('/(\.[0-9]+)$/i', '.0', $versionfrom);
		$newversionto = preg_replace('/(\.[0-9]+)$/i', '.0', $versionto);

		$filelist = array();
		$i = 0;
		$ok = 0;
		$from = '^'.preg_quote($newversionfrom, '/');
		$to = preg_quote($newversionto.'.sql', '/').'$';

		// Get files list
		$filesindir = array();
		$handle = opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/\.sql$/i', $file)) {
					$filesindir[] = $file;
				}
			}
			sort($filesindir);
		} else {
			print '<div class="error">'.$langs->trans("ErrorCanNotReadDir", $dir).'</div>';
		}

		// Define which file to run
		foreach ($filesindir as $file) {
			if (preg_match('/'.$from.'\-/i', $file)) {
				$filelist[] = $file;
			} elseif (preg_match('/\-'.$to.'/i', $file)) {	// First test may be false if we migrate from x.y.* to x.y.*
				$filelist[] = $file;
			}
		}

		if (count($filelist) == 0) {
			print '<div class="error">'.$langs->trans("ErrorNoMigrationFilesFoundForParameters").'</div>';
		} else {
			$listoffileprocessed = array(); // Protection to avoid to process twice the same file

			// Loop on each migrate files
			foreach ($filelist as $file) {
				if (in_array($dir.$file, $listoffileprocessed)) {
					continue;
				}

				print '<tr><td colspan="2"><hr style="border-color: #ccc; border-top-style: none;"></td></tr>';
				print '<tr><td class="nowrap">'.$langs->trans("ChoosedMigrateScript").'</td><td class="right">'.$file.'</td></tr>'."\n";

				// Run sql script
				$ok = run_sql($dir.$file, 0, '', 1, '', 'default', 32768, 0, 0, 2, 0, $db->database_name);
				$listoffileprocessed[$dir.$file] = $dir.$file;


				// Scan if there is migration scripts that depends of Dolibarr version
				// for modules htdocs/module/sql or htdocs/custom/module/sql (files called "dolibarr_x.y.z-a.b.c.sql" or "dolibarr_always.sql")
				$modulesfile = array();
				foreach ($conf->file->dol_document_root as $type => $dirroot) {
					$handlemodule = @opendir($dirroot); // $dirroot may be '..'
					if (is_resource($handlemodule)) {
						while (($filemodule = readdir($handlemodule)) !== false) {
							if (!preg_match('/\./', $filemodule) && is_dir($dirroot.'/'.$filemodule.'/sql')) {	// We exclude filemodule that contains . (are not directories) and are not directories.
								//print "Scan for ".$dirroot . '/' . $filemodule . '/sql/'.$file;
								if (is_file($dirroot.'/'.$filemodule.'/sql/dolibarr_'.$file)) {
									$modulesfile[$dirroot.'/'.$filemodule.'/sql/dolibarr_'.$file] = '/'.$filemodule.'/sql/dolibarr_'.$file;
								}
								if (is_file($dirroot.'/'.$filemodule.'/sql/dolibarr_allversions.sql')) {
									$modulesfile[$dirroot.'/'.$filemodule.'/sql/dolibarr_allversions.sql'] = '/'.$filemodule.'/sql/dolibarr_allversions.sql';
								}
							}
						}
						closedir($handlemodule);
					}
				}

				if (count($modulesfile)) {
					print '<tr><td colspan="2"><hr style="border-color: #ccc; border-top-style: none;"></td></tr>';

					foreach ($modulesfile as $modulefilelong => $modulefileshort) {
						if (in_array($modulefilelong, $listoffileprocessed)) {
							continue;
						}

						print '<tr><td class="nowrap">'.$langs->trans("ChoosedMigrateScript").' (external modules)</td><td class="right">'.$modulefileshort.'</td></tr>'."\n";

						// Run sql script
						$okmodule = run_sql($modulefilelong, 0, '', 1); // Note: Result of migration of external module should not decide if we continue migration of Dolibarr or not.
						$listoffileprocessed[$modulefilelong] = $modulefilelong;
					}
				}
			}
		}
	}

	print '</table>';

	if ($db->connected) {
		$db->close();
	}
}


if (empty($actiondone)) {
	print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}

$ret = 0;
if (!$ok && isset($argv[1])) {
	$ret = 1;
}
dolibarr_install_syslog("Exit ".$ret);

dolibarr_install_syslog("--- upgrade: end ".((int) (!$ok && !GETPOST("ignoreerrors")))." dirmodule=".$dirmodule);

$nonext = (!$ok && !GETPOST("ignoreerrors")) ? 2 : 0;
if ($dirmodule) {
	$nonext = 1;
}
pFooter($nonext, $setuplang);

if ($db->connected) {
	$db->close();
}

// Return code if ran from command line
if ($ret) {
	exit($ret);
}
