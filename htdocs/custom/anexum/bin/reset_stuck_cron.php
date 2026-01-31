#!/usr/bin/env php
<?php
/* Copyright (C) 2026  Florian Hödl  <florian@hoedl.co>
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
 *      \file       htdocs/custom/anexum/bin/reset_stuck_cron.php
 *      \ingroup    anexum
 *      \brief      CLI script to reset stuck Dolibarr cron jobs
 *
 *      This script finds cron jobs that have been processing for longer than
 *      a configurable threshold (default: 30 minutes) and resets them.
 *      This fixes the issue where EmailCollector or other cron jobs get stuck
 *      in an infinite loop.
 *
 *      Usage: php reset_stuck_cron.php [OPTIONS]
 *
 *      Options:
 *        -v, --verbose       Show detailed output
 *        -n, --dry-run       Show what would be done without making changes
 *        --threshold=MIN     Minutes before a job is considered stuck (default: 30)
 *        -h, --help          Show help message
 *
 *      Recommended crontab entry (every 5 minutes):
 *      0,5,10,15,20,25,30,35,40,45,50,55 * * * * php /var/www/html/htdocs/custom/anexum/bin/reset_stuck_cron.php
 */

// CLI environment constants - disable unnecessary web features
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', '1');
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(1);
}

// Global variables
$version = '1.0';
$error = 0;

// Parse command line options
$verbose = false;
$dryrun = false;
$opts = getopt('vnh', array('verbose', 'dry-run', 'help', 'threshold:'));

if (isset($opts['h']) || isset($opts['help'])) {
	print "Usage: php ".$script_file." [OPTIONS]\n";
	print "\n";
	print "Reset stuck Dolibarr cron jobs that have been processing for too long.\n";
	print "\n";
	print "Options:\n";
	print "  -v, --verbose       Show detailed output\n";
	print "  -n, --dry-run       Show what would be done without making changes\n";
	print "  --threshold=MIN     Minutes before a job is considered stuck (default: 30)\n";
	print "  -h, --help          Show this help message\n";
	exit(0);
}

if (isset($opts['v']) || isset($opts['verbose'])) {
	$verbose = true;
}
if (isset($opts['n']) || isset($opts['dry-run'])) {
	$dryrun = true;
	$verbose = true;
}

// Configuration
$stuck_threshold_minutes = isset($opts['threshold']) ? (int) $opts['threshold'] : 30;
if ($stuck_threshold_minutes < 1) {
	$stuck_threshold_minutes = 30;
}

@set_time_limit(0);
define('EVEN_IF_ONLY_LOGIN_ALLOWED', 1);

// Load Dolibarr environment
// Script is in htdocs/custom/anexum/bin/ so we need to go up 3 levels to htdocs
$res = 0;

// First try: direct path from script location (most reliable for CLI)
$script_dir = dirname(__FILE__);
$dolibarr_htdocs = realpath($script_dir.'/../../../');
if ($dolibarr_htdocs && file_exists($dolibarr_htdocs.'/master.inc.php')) {
	chdir($dolibarr_htdocs);
	$res = @include $dolibarr_htdocs.'/master.inc.php';
}

// Fallback: try relative paths (from htdocs directory)
if (!$res && file_exists("master.inc.php")) {
	$res = @include "master.inc.php";
}

if (!$res) {
	print "Include of master fails. Make sure script is run from Dolibarr htdocs directory.\n";
	exit(1);
}

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$langs->load("cron");

// Load user for logging purposes
$result = $user->fetch(0, 'admin');
if (!($result > 0)) {
	// Try without user - we just need DB access
	$user->id = 0;
}

$hookmanager->initHooks(array('cli'));

/*
 * Main
 */

if ($verbose) {
	print "***** ".$script_file." (".$version.") pid=".dol_getmypid()." *****\n";
	print "Checking for stuck cron jobs (processing > ".$stuck_threshold_minutes." minutes)...\n";
}

// Find stuck cron jobs:
// - processing = 1 (currently running)
// - datelastrun < NOW() - threshold (running for too long)
$sql = "SELECT rowid, label, datelastrun, pid, module_name, methodename";
$sql .= " FROM ".$db->prefix()."cronjob";
$sql .= " WHERE processing = 1";
$sql .= " AND datelastrun < DATE_SUB(NOW(), INTERVAL ".((int) $stuck_threshold_minutes)." MINUTE)";

$resql = $db->query($sql);
if (!$resql) {
	dol_syslog($script_file.": SQL error: ".$db->lasterror(), LOG_ERR);
	print "SQL Error: ".$db->lasterror()."\n";
	exit(1);
}

$num = $db->num_rows($resql);
$reset_count = 0;

if ($verbose) {
	print "Found ".$num." potentially stuck job(s)\n";
}

while ($obj = $db->fetch_object($resql)) {
	$should_reset = true;

	// If we have a PID, check if the process is still alive
	if (!empty($obj->pid) && $obj->pid > 0) {
		// Check if process exists (Linux-specific)
		if (file_exists('/proc/'.$obj->pid)) {
			// Process still exists - might actually be running
			// Check process start time to be sure it's the same process
			$proc_stat = @file_get_contents('/proc/'.$obj->pid.'/stat');
			if ($proc_stat !== false) {
				// Process is alive - don't reset unless it's really old
				$running_minutes = round((time() - strtotime($obj->datelastrun)) / 60);
				if ($running_minutes < 60) {
					// Less than 60 minutes and process is alive - skip
					$should_reset = false;
					if ($verbose) {
						print "  - Job #".$obj->rowid." '".$obj->label."': PID ".$obj->pid." still alive, skipping\n";
					}
				}
			}
		}
	}

	if ($should_reset) {
		$running_minutes = round((time() - strtotime($obj->datelastrun)) / 60);

		if ($dryrun) {
			// Dry-run mode: just print what would be done
			$log_msg = "DRY-RUN: Would reset stuck cron job #".$obj->rowid." '".$obj->label."' ";
			$log_msg .= "(processing for ".$running_minutes." minutes";
			if (!empty($obj->pid)) {
				$log_msg .= ", PID: ".$obj->pid;
			}
			$log_msg .= ")";
			print "  - ".$log_msg."\n";
			$reset_count++;
		} else {
			// Reset the stuck job
			$sql_update = "UPDATE ".$db->prefix()."cronjob SET";
			$sql_update .= " processing = 0,";
			$sql_update .= " pid = NULL";
			$sql_update .= " WHERE rowid = ".((int) $obj->rowid);

			$res_update = $db->query($sql_update);
			if ($res_update) {
				$reset_count++;

				$log_msg = "Reset stuck cron job #".$obj->rowid." '".$obj->label."' ";
				$log_msg .= "(was processing for ".$running_minutes." minutes";
				if (!empty($obj->pid)) {
					$log_msg .= ", PID: ".$obj->pid;
				}
				$log_msg .= ")";

				dol_syslog($script_file.": ".$log_msg, LOG_WARNING);
				if ($verbose) {
					print "  - ".$log_msg."\n";
				}
			} else {
				$error++;
				dol_syslog($script_file.": Failed to reset job #".$obj->rowid.": ".$db->lasterror(), LOG_ERR);
				if ($verbose) {
					print "  - ERROR: Failed to reset job #".$obj->rowid.": ".$db->lasterror()."\n";
				}
			}
		}
	}
}

$db->free($resql);
$db->close();

if ($verbose) {
	if ($dryrun) {
		print "--- Dry-run complete. Would reset ".$reset_count." job(s).\n";
	} else {
		print "--- Done. Reset ".$reset_count." job(s).\n";
	}
}

// Always log to syslog if we reset something (not in dry-run mode)
if ($reset_count > 0 && !$dryrun) {
	dol_syslog($script_file.": Reset ".$reset_count." stuck cron job(s)", LOG_NOTICE);
}

exit($error);
