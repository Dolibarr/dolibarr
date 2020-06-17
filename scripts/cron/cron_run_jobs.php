#!/usr/bin/env php
<?php
/*
 * Copyright (C) 2012 Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013 Florian Henry <forian.henry@open-concept.pro
 * Copyright (C) 2013-2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file scripts/cron/cron_run_jobs.php
 * \ingroup cron
 * \brief Execute pendings jobs
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOLOGIN'))        define('NOLOGIN', '1');


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path = __DIR__ . '/';

// Error if Web mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute " . $script_file . " from command line, you must use PHP for CLI mode.\n";
	exit(- 1);
}

require_once $path . "../../htdocs/master.inc.php";
require_once DOL_DOCUMENT_ROOT . "/cron/class/cronjob.class.php";
require_once DOL_DOCUMENT_ROOT . '/user/class/user.class.php';

// Check parameters
if (! isset($argv[1]) || ! $argv[1]) {
	usage($path, $script_file);
	exit(- 1);
}
$key = $argv[1];

if (! isset($argv[2]) || ! $argv[2]) {
	usage($path, $script_file);
	exit(- 1);
}

$userlogin = $argv[2];

// Global variables
$version = DOL_VERSION;
$error = 0;

// Language Management
$langs->loadLangs(array('main', 'admin', 'cron', 'dict'));


/*
 * Main
 */

// current date
$now = dol_now();

@set_time_limit(0);
print "***** " . $script_file . " (" . $version . ") pid=" . dol_getmypid() . " ***** userlogin=" . $userlogin . " ***** " . dol_print_date($now, 'dayhourrfc') . " *****\n";

// Check module cron is activated
if (empty($conf->cron->enabled)) {
	print "Error: module Scheduled jobs (cron) not activated\n";
	exit(- 1);
}

// Check module cron is activated
if (empty($conf->cron->enabled)) {
	print "Error: module Scheduled jobs (cron) not activated\n";
	exit(- 1);
}

// Check security key
if ($key != $conf->global->CRON_KEY) {
	print "Error: securitykey is wrong\n";
	exit(- 1);
}

// If param userlogin is reserved word 'firstadmin'
if ($userlogin == 'firstadmin') {
	$sql = 'SELECT login, entity from ' . MAIN_DB_PREFIX . 'user WHERE admin = 1 and statut = 1 ORDER BY entity LIMIT 1';
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		if ($obj) {
			$userlogin = $obj->login;
			echo "First admin user found is login '" . $userlogin . "', entity " . $obj->entity . "\n";
		}
	} else
		dol_print_error($db);
}

// Check user login
$user = new User($db);
$result = $user->fetch('', $userlogin);
if ($result < 0) {
	echo "User Error: " . $user->error;
	dol_syslog("cron_run_jobs.php:: User Error:" . $user->error, LOG_ERR);
	exit(- 1);
} else {
	if (empty($user->id)) {
		echo "User login: " . $userlogin . " does not exists";
		dol_syslog("User login:" . $userlogin . " does not exists", LOG_ERR);
		exit(- 1);
	}
}
$user->getrights();

if (isset($argv[3]) || $argv[3]) {
	$id = $argv[3];
}

// create a jobs object
$object = new Cronjob($db);

$filter = array();
if (! empty($id)) {
	if (! is_numeric($id)) {
		echo "Error: Bad value for parameter job id";
		dol_syslog("cron_run_jobs.php Bad value for parameter job id", LOG_WARNING);
		exit();
	}
	$filter['t.rowid'] = $id;
}

$result = $object->fetch_all('ASC,ASC,ASC', 't.priority,t.entity,t.rowid', 0, 0, 1, $filter, 0);
if ($result < 0) {
	echo "Error: " . $object->error;
	dol_syslog("cron_run_jobs.php fetch Error " . $object->error, LOG_ERR);
	exit(- 1);
}

$qualifiedjobs = array();
foreach ($object->lines as $val) {
	if (! verifCond($val->test))
	{
		continue;
	}
	$qualifiedjobs[] = $val;
}

// TODO Duplicate code. This sequence of code must be shared with code into public/cron/cron_run_jobs.php php page.

$nbofjobs = count($qualifiedjobs);
$nbofjobslaunchedok = 0;
$nbofjobslaunchedko = 0;

if (is_array($qualifiedjobs) && (count($qualifiedjobs) > 0)) {
    $savconf = dol_clone($conf);

	// Loop over job
	foreach ($qualifiedjobs as $line) {
		dol_syslog("cron_run_jobs.php cronjobid: ".$line->id." priority=".$line->priority." entity=".$line->entity." label=".$line->label, LOG_DEBUG);
		echo "cron_run_jobs.php cronjobid: ".$line->id." priority=".$line->priority." entity=".$line->entity." label=".$line->label;

		// Force reload of setup for the current entity
		if ((empty($line->entity)?1:$line->entity) != $conf->entity)
		{
			dol_syslog("cron_run_jobs.php we work on another entity conf than ".$conf->entity." so we reload mysoc, langs, user and conf", LOG_DEBUG);
			echo " -> we change entity so we reload mysoc, langs, user and conf";

		    $conf->entity = (empty($line->entity)?1:$line->entity);
		    $conf->setValues($db);        // This make also the $mc->setValues($conf); that reload $mc->sharings
		    $mysoc->setMysoc($conf);

		    // Force recheck that user is ok for the entity to process and reload permission for entity
		    if ($conf->entity != $user->entity && $user->entity != 0)
		    {
    		    $result=$user->fetch('', $userlogin, '', 0, $conf->entity);
    		    if ($result < 0)
    		    {
    		        echo "\nUser Error: ".$user->error."\n";
    		        dol_syslog("cron_run_jobs.php:: User Error:".$user->error, LOG_ERR);
    		        exit(-1);
    		    }
    		    else
    		    {
    		        if ($result == 0)
    		        {
    		            echo "\nUser login: ".$userlogin." does not exists for entity ".$conf->entity."\n";
    		            dol_syslog("User login:".$userlogin." does not exists", LOG_ERR);
    		            exit(-1);
    		        }
    		    }
    		    $user->getrights();
		    }

		    // Reload langs
		    $langcode = (empty($conf->global->MAIN_LANG_DEFAULT)?'auto':$conf->global->MAIN_LANG_DEFAULT);
		    if (! empty($user->conf->MAIN_LANG_DEFAULT)) $langcode = $user->conf->MAIN_LANG_DEFAULT;
		    if ($langs->getDefaultLang() != $langcode) $langs->setDefaultLang($langcode);
		}

		//If date_next_jobs is less of current date, execute the program, and store the execution time of the next execution in database
		if (($line->datenextrun < $now) && (empty($line->datestart) || $line->datestart <= $now) && (empty($line->dateend) || $line->dateend >= $now)) {
			echo " - qualified";

			dol_syslog("cron_run_jobs.php line->datenextrun:" . dol_print_date($line->datenextrun, 'dayhourrfc') . " line->datestart:" . dol_print_date($line->datestart, 'dayhourrfc') . " line->dateend:" . dol_print_date($line->dateend, 'dayhourrfc') . " now:" . dol_print_date($now, 'dayhourrfc'));

			$cronjob = new Cronjob($db);
			$result = $cronjob->fetch($line->id);
			if ($result < 0) {
				echo "Error cronjobid: " . $line->id . " cronjob->fetch: " . $cronjob->error . "\n";
				echo "Failed to fetch job " . $line->id . "\n";
				dol_syslog("cron_run_jobs.php::fetch Error " . $cronjob->error, LOG_ERR);
				exit(- 1);
			}
			// Execute job
			$result = $cronjob->run_jobs($userlogin);
			if ($result < 0) {
				echo "Error cronjobid: " . $line->id . " cronjob->run_job: " . $cronjob->error . "\n";
				echo "At least one job failed. Go on menu Home-Setup-Admin tools to see result for each job.\n";
				echo "You can also enable module Log if not yet enabled, run again and take a look into dolibarr.log file\n";
				dol_syslog("cron_run_jobs.php::run_jobs Error " . $cronjob->error, LOG_ERR);
				$nbofjobslaunchedko ++;
			} else {
				$nbofjobslaunchedok ++;
			}

			echo " - result of run_jobs = " . $result;

			// We re-program the next execution and stores the last execution time for this job
			$result = $cronjob->reprogram_jobs($userlogin, $now);
			if ($result < 0) {
				echo "Error cronjobid: " . $line->id . " cronjob->reprogram_job: " . $cronjob->error . "\n";
				echo "Enable module Log if not yet enabled, run again and take a look into dolibarr.log file\n";
				dol_syslog("cron_run_jobs.php::reprogram_jobs Error " . $cronjob->error, LOG_ERR);
				exit(- 1);
			}

			echo " - reprogrammed\n";
		} else {
			echo " - not qualified\n";

			dol_syslog("cron_run_jobs.php job not qualified line->datenextrun:" . dol_print_date($line->datenextrun, 'dayhourrfc') . " line->datestart:" . dol_print_date($line->datestart, 'dayhourrfc') . " line->dateend:" . dol_print_date($line->dateend, 'dayhourrfc') . " now:" . dol_print_date($now, 'dayhourrfc'));
		}
	}

	$conf = $savconf;
}
else
{
	echo "cron_run_jobs.php no qualified job found\n";
}

$db->close();

if ($nbofjobslaunchedko)
	exit(1);
exit(0);

/**
 * script cron usage
 *
 * @param string $path				Path
 * @param string $script_file		Filename
 * @return void
 */
function usage($path, $script_file)
{
	print "Usage: " . $script_file . " securitykey userlogin|'firstadmin' [cronjobid]\n";
	print "The script return 0 when everything worked successfully.\n";
	print "\n";
	print "On Linux system, you can have cron jobs ran automatically by adding an entry into cron.\n";
	print "For example, to run pending tasks each day at 3:30, you can add this line:\n";
	print "30 3 * * * " . $path . $script_file . " securitykey userlogin > " . DOL_DATA_ROOT . "/" . $script_file . ".log\n";
	print "For example, to run pending tasks every 5mn, you can add this line:\n";
	print "*/5 * * * * " . $path . $script_file . " securitykey userlogin > " . DOL_DATA_ROOT . "/" . $script_file . ".log\n";
}
