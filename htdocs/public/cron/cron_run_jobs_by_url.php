<?php
/* Copyright (C) 2012		Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013		Florian Henry		<forian.henry@open-cocnept.pro>
 * Copyright (C) 2013-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2017		Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *  \file       htdocs/public/cron/cron_run_jobs_by_url.php
 *  \ingroup    cron
 *  \brief      Execute pendings jobs
 */

if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Disables token renewal
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
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}

// So log file will have a suffix
if (!defined('USESUFFIXINLOG')) {
	define('USESUFFIXINLOG', '_cron');
}

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Error if CLI mode
if (php_sapi_name() == "cli") {
	echo "Error: This page can't be used as a CLI script. For the CLI version of script, launch cron_run_job.php available into scripts/cron/ directory.\n";
	exit(1);
}

// core library
// Dolibarr environment
require '../../main.inc.php';

// cron jobs library
dol_include_once("/cron/class/cronjob.class.php");
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Translate $langs
 */
global $langs, $conf, $db;

// Language Management
$langs->loadLangs(array("admin", "cron", "dict"));

// Security check
if (!isModEnabled('cron')) {
	httponly_accessforbidden('Module Cron not enabled');
}



/*
 * View
 */

// current date
$now = dol_now();

// Check the key, avoid that a stranger starts cron
$key = GETPOST('securitykey', 'alpha');
if (empty($key)) {
	echo 'Securitykey is required. Check setup of cron jobs module.';
	exit;
}
if ($key != getDolGlobalString('CRON_KEY')) {
	echo 'Securitykey is wrong.';
	exit;
}
// Check the key, avoid that a stranger starts cron
$userlogin = GETPOST('userlogin', 'alpha');
if (empty($userlogin)) {
	echo 'Userlogin is required.';
	exit;
}
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
$user = new User($db);
$result = $user->fetch(0, $userlogin);
if ($result < 0) {
	echo "User Error:".$user->error;
	dol_syslog("cron_run_jobs.php:: User Error:".$user->error, LOG_ERR);
	exit;
} else {
	if (empty($user->id)) {
		echo " User login:".$userlogin." do not exists";
		dol_syslog(" User login:".$userlogin." do not exists", LOG_ERR);
		exit;
	}
}
$user->loadRights();

$id = GETPOST('id', 'alpha'); // We accept non numeric id. We will filter later.


// create a jobs object
$object = new Cronjob($db);

$filter = array();
if (!empty($id)) {
	if (!is_numeric($id)) {
		echo "Error: Bad value for parameter job id";
		dol_syslog("cron_run_jobs.php Bad value for parameter job id", LOG_WARNING);
		exit;
	}
	$filter['t.rowid'] = $id;
}

// Update old jobs that were not closed correctly so processing is moved from 1 to 0 (otherwise task stopped with fatal error are always in status "in progress")
$sql = "UPDATE ".MAIN_DB_PREFIX."cronjob set processing = 0";
$sql .= " WHERE processing = 1";
$sql .= " AND datelastrun <= '".$db->idate(dol_now() - getDolGlobalInt('CRON_MAX_DELAY_FOR_JOBS', 24) * 3600, 'gmt')."'";
$sql .= " AND datelastresult IS NULL";
$db->query($sql);

// Also unlock jobs that have a PID but the process does not exist anymore (SIGKILL, crash, segfault, ...).
if (function_exists('posix_kill') && function_exists('posix_get_last_error')) {
	$sql = "SELECT rowid, pid";
	$sql .= " FROM ".MAIN_DB_PREFIX."cronjob";
	$sql .= " WHERE processing = 1";
	$sql .= " AND datelastresult IS NULL";
	$sql .= " AND pid IS NOT NULL";
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$pid = (int) $obj->pid;
			if ($pid <= 0) {
				continue;
			}

			$isalive = @posix_kill($pid, 0);
			if (!$isalive) {
				$errno = posix_get_last_error();
				if ($errno === 3) { // ESRCH = No such process
					$nowcleanup = dol_now();
					$msg = 'Cron job unlocked: stale PID '.$pid;

					$sqlu = "UPDATE ".MAIN_DB_PREFIX."cronjob";
					$sqlu .= " SET processing = 0, pid = NULL, datelastresult = '".$db->idate($nowcleanup)."', lastresult = '-1', lastoutput = '".$db->escape($msg)."'";
					$sqlu .= " WHERE rowid = ".((int) $obj->rowid)." AND processing = 1 AND pid = ".((int) $pid)." AND datelastresult IS NULL";
					$db->query($sqlu);

					dol_syslog("cron_run_jobs_by_url.php unlocked stuck job id=".$obj->rowid." (stale pid ".$pid.")", LOG_WARNING);
					echo "Unlocked stuck job id=".$obj->rowid." (stale pid ".$pid.")\n";
				}
			}
		}
		$db->free($resql);
	}
}

$result = $object->fetchAll('ASC,ASC,ASC', 't.priority,t.entity,t.rowid', 0, 0, 1, $filter, 0);
if ($result < 0) {
	echo "Error: ".$object->error;
	dol_syslog("cron_run_jobs.php fetch Error".$object->error, LOG_ERR);
	exit;
}

// TODO Duplicate code. This sequence of code must be shared with code into cron_run_jobs.php script.

// current date
$nbofjobs = count($object->lines);
$nbofjobslaunchedok = 0;
$nbofjobslaunchedko = 0;

if (is_array($object->lines) && (count($object->lines) > 0)) {
	$savconf = dol_clone($conf, 0);

	// Loop over job
	foreach ($object->lines as $line) {
		'@phan-var-force Cronjob $line';
		dol_syslog("cron_run_jobs.php cronjobid: ".$line->id." priority=".$line->priority." entity=".$line->entity." label=".$line->label, LOG_DEBUG);
		echo "cron_run_jobs.php cronjobid: ".$line->id." priority=".$line->priority." entity=".$line->entity." label=".$line->label;

		// Force reload of setup for the current entity
		if ($line->entity != $conf->entity) {
			dol_syslog("cron_run_jobs.php we work on another entity so we reload user and conf", LOG_DEBUG);
			echo " -> we change entity so we reload user and conf";

			$conf->entity = (empty($line->entity) ? 1 : $line->entity);
			$conf->setValues($db); // This make also the $mc->setValues($conf); that reload $mc->sharings

			// Force recheck that user is ok for the entity to process and reload permission for entity
			if ($conf->entity != $user->entity && $user->entity != 0) {
				$result = $user->fetch(0, $userlogin, '', 0, $conf->entity);
				if ($result < 0) {
					echo "\nUser Error: ".$user->error."\n";
					dol_syslog("cron_run_jobs.php:: User Error:".$user->error, LOG_ERR);
					exit(1);
				} else {
					if ($result == 0) {
						echo "\nUser login: ".$userlogin." does not exists for entity ".$conf->entity."\n";
						dol_syslog("User login:".$userlogin." does not exists", LOG_ERR);
						exit(1);
					}
				}
				$user->loadRights();
			}
		}

		if (!verifCond($line->test)) {
			continue;
		}

		//If date_next_jobs is less of current date, execute the program, and store the execution time of the next execution in database
		$datenextrunok = (empty($line->datenextrun) || (int) $line->datenextrun < $now);
		$datestartok = (empty($line->datestart) || $line->datestart <= $now);
		$dateendok = (empty($line->dateend) || $line->dateend >= $now);
		if ($datenextrunok && $datestartok && $dateendok) {
			echo " - qualified";

			dol_syslog("cron_run_jobs.php line->datenextrun:".dol_print_date($line->datenextrun, 'dayhourrfc')." line->datestart:".dol_print_date($line->datestart, 'dayhourrfc')." line->dateend:".dol_print_date($line->dateend, 'dayhourrfc')." now:".dol_print_date($now, 'dayhourrfc'));

			$cronjob = new Cronjob($db);
			$result = $cronjob->fetch($line->id);
			if ($result < 0) {
				echo "Error cronjobid: ".$line->id." cronjob->fetch: ".$cronjob->error."\n";
				echo "Failed to fetch job ".$line->id."\n";
				dol_syslog("cron_run_jobs.php::fetch Error".$cronjob->error, LOG_ERR);
				exit;
			}
			// Execute job
			$result = $cronjob->run_jobs($userlogin);
			if ($result < 0) {
				echo "Error cronjobid: ".$line->id." cronjob->run_job: ".$cronjob->error."\n";
				echo "At least one job failed. Go on menu Home-Setup-Admin tools to see result for each job.\n";
				echo "You can also enable module Log if not yet enabled, run again and take a look into dolibarr.log file\n";
				dol_syslog("cron_run_jobs.php::run_jobs Error".$cronjob->error, LOG_ERR);
				$nbofjobslaunchedko++;
				$resultstring = 'KO';
			} else {
				$nbofjobslaunchedok++;
				$resultstring = 'OK';
			}

			echo "Result of run_jobs = ".$resultstring." result = ".$result;

			// We re-program the next execution and stores the last execution time for this job
			$result = $cronjob->reprogram_jobs($userlogin, $now);
			if ($result < 0) {
				echo " - Error cronjobid: ".$line->id." cronjob->reprogram_job: ".$cronjob->error."\n";
				echo "Enable module Log if not yet enabled, run again and take a look into dolibarr.log file\n";
				dol_syslog("cron_run_jobs.php::reprogram_jobs Error".$cronjob->error, LOG_ERR);
				exit(1);
			}

			echo " - Job re-scheduled\n";
		} else {
			echo " - not qualified (datenextrunok=".($datenextrunok ?: 0).", datestartok=".($datestartok ?: 0).", dateendok=".($dateendok ?: 0).")\n";

			dol_syslog("cron_run_jobs.php job ".$line->id." not qualified line->datenextrun:".dol_print_date($line->datenextrun, 'dayhourrfc')." line->datestart:".dol_print_date($line->datestart, 'dayhourrfc')." line->dateend:".dol_print_date($line->dateend, 'dayhourrfc')." now:".dol_print_date($now, 'dayhourrfc'));
		}
	}

	$conf = $savconf;

	echo "Result: ".($nbofjobs)." jobs - ".($nbofjobslaunchedok + $nbofjobslaunchedko)." launched = ".$nbofjobslaunchedok." OK + ".$nbofjobslaunchedko." KO";
} else {
	echo "Result: No active jobs found.";
}

$db->close();
