<?php
/* Copyright (C) 2012		Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013		Florian Henry		<forian.henry@open-cocnept.pro>
 * Copyright (C) 2013-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2017		Regis Houssin		<regis.houssin@inodbox.com>
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
 *  \file       htdocs/public/cron/cron_run_jobs.php
 *  \ingroup    cron
 *  \brief      Execute pendings jobs
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (! defined('NOLOGIN'))        define('NOLOGIN', '1');

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

// Error if CLI mode
if (php_sapi_name() == "cli")
{
    echo "Error: This page can't be used as a CLI script. For the CLI version of script, launch cron_run_job.php available into scripts/cron/ directory.\n";
    exit(-1);
}

// librarie core
// Dolibarr environment
require '../../main.inc.php';

// librarie jobs
dol_include_once("/cron/class/cronjob.class.php");

global $langs, $conf;

// Language Management
$langs->loadLangs(array("admin", "cron", "dict"));




/*
 * View
 */

// current date
$now=dol_now();

// Check the key, avoid that a stranger starts cron
$key = GETPOST('securitykey', 'alpha');
if (empty($key))
{
	echo 'Securitykey is required. Check setup of cron jobs module.';
	exit;
}
if($key != $conf->global->CRON_KEY)
{
	echo 'Securitykey is wrong.';
	exit;
}
// Check the key, avoid that a stranger starts cron
$userlogin = GETPOST('userlogin', 'alpha');
if (empty($userlogin))
{
	echo 'Userlogin is required.';
	exit;
}
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
$user=new User($db);
$result=$user->fetch('', $userlogin);
if ($result < 0)
{
	echo "User Error:".$user->error;
	dol_syslog("cron_run_jobs.php:: User Error:".$user->error, LOG_ERR);
	exit;
}
else
{
	if (empty($user->id))
	{
		echo " User login:".$userlogin." do not exists";
		dol_syslog(" User login:".$userlogin." do not exists", LOG_ERR);
		exit;
	}
}
$id = GETPOST('id', 'alpha');	// We accept non numeric id. We will filter later.


// create a jobs object
$object = new Cronjob($db);

$filter=array();
if (! empty($id)) {
	if (! is_numeric($id))
	{
		echo "Error: Bad value for parameter job id";
		dol_syslog("cron_run_jobs.php Bad value for parameter job id", LOG_WARNING);
		exit;
	}
	$filter['t.rowid']=$id;
}

$result = $object->fetch_all('ASC,ASC,ASC', 't.priority,t.entity,t.rowid', 0, 0, 1, $filter, 0);
if ($result<0)
{
	echo "Error: ".$object->error;
	dol_syslog("cron_run_jobs.php fetch Error".$object->error, LOG_ERR);
	exit;
}

$qualifiedjobs = array();
foreach($object->lines as $val)
{
	if (! verifCond($val->test)) continue;
	$qualifiedjobs[] = $val;
}

// TODO Duplicate code. This sequence of code must be shared with code into cron_run_jobs.php script.

// current date
$nbofjobs=count($qualifiedjobs);
$nbofjobslaunchedok=0;
$nbofjobslaunchedko=0;

if (is_array($qualifiedjobs) && (count($qualifiedjobs)>0))
{
    $savconf = dol_clone($conf);

    // Loop over job
	foreach($qualifiedjobs as $line)
	{
	    dol_syslog("cron_run_jobs.php cronjobid: ".$line->id." priority=".$line->priority." entity=".$line->entity." label=".$line->label, LOG_DEBUG);
	    echo "cron_run_jobs.php cronjobid: ".$line->id." priority=".$line->priority." entity=".$line->entity." label=".$line->label;

		// Force reload of setup for the current entity
		if ($line->entity != $conf->entity)
		{
		    dol_syslog("cron_run_jobs.php we work on another entity so we reload user and conf", LOG_DEBUG);
		    echo " -> we change entity so we reload user and conf";

		    $conf->entity = (empty($line->entity)?1:$line->entity);
		    $conf->setValues($db);        // This make also the $mc->setValues($conf); that reload $mc->sharings

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
		}

		//If date_next_jobs is less of current date, execute the program, and store the execution time of the next execution in database
		if (($line->datenextrun < $now) && (empty($line->datestart) || $line->datestart <= $now) && (empty($line->dateend) || $line->dateend >= $now))
		{
		    echo " - qualified";

		    dol_syslog("cron_run_jobs.php line->datenextrun:".dol_print_date($line->datenextrun, 'dayhourrfc')." line->datestart:".dol_print_date($line->datestart, 'dayhourrfc')." line->dateend:".dol_print_date($line->dateend, 'dayhourrfc')." now:".dol_print_date($now, 'dayhourrfc'));

			$cronjob=new Cronjob($db);
			$result=$cronjob->fetch($line->id);
			if ($result<0)
			{
			    echo "Error cronjobid: ".$line->id." cronjob->fetch: ".$cronjob->error."\n";
			    echo "Failed to fetch job ".$line->id."\n";
				dol_syslog("cron_run_jobs.php::fetch Error".$cronjob->error, LOG_ERR);
				exit;
			}
			// Execute job
			$result=$cronjob->run_jobs($userlogin);
			if ($result < 0)
			{
			    echo "Error cronjobid: ".$line->id." cronjob->run_job: ".$cronjob->error."\n";
			    echo "At least one job failed. Go on menu Home-Setup-Admin tools to see result for each job.\n";
			    echo "You can also enable module Log if not yet enabled, run again and take a look into dolibarr.log file\n";
			    dol_syslog("cron_run_jobs.php::run_jobs Error".$cronjob->error, LOG_ERR);
				$nbofjobslaunchedko++;
			}
			else
			{
				$nbofjobslaunchedok++;
			}

			echo " - result of run_jobs = ".$result;

			// We re-program the next execution and stores the last execution time for this job
			$result=$cronjob->reprogram_jobs($userlogin, $now);
			if ($result<0)
			{
			    echo "Error cronjobid: ".$line->id." cronjob->reprogram_job: ".$cronjob->error."\n";
			    echo "Enable module Log if not yet enabled, run again and take a look into dolibarr.log file\n";
			    dol_syslog("cron_run_jobs.php::reprogram_jobs Error".$cronjob->error, LOG_ERR);
				exit;
			}

			echo " - reprogrammed\n";
		}
		else
		{
		    echo " - not qualified\n";

		    dol_syslog("cron_run_jobs.php job not qualified line->datenextrun:".dol_print_date($line->datenextrun, 'dayhourrfc')." line->datestart:".dol_print_date($line->datestart, 'dayhourrfc')." line->dateend:".dol_print_date($line->dateend, 'dayhourrfc')." now:".dol_print_date($now, 'dayhourrfc'));
		}
	}

	$conf = $savconf;

	echo "Result: ".($nbofjobs)." jobs - ".($nbofjobslaunchedok+$nbofjobslaunchedko)." launched = ".$nbofjobslaunchedok." OK + ".$nbofjobslaunchedko." KO";
}
else
{
	echo "Result: No active jobs found.";
}

$db->close();
