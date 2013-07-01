#!/usr/bin/php
<?php
/* Copyright (C) 2012   Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013   Florian Henry <forian.henry@open-concept.pro
 * Copyright (C) 2013   Laurent Destailleur <eldy@users.sourceforge.net>
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
 *  \file       cron/script/cron/cron_run_jobs.php
 *  \ingroup    cron
 *  \brief      Execute pendings jobs
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOLOGIN'))        define('NOLOGIN','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');


$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test if batch mode
if (substr($sapi_type, 0, 3) == 'cgi') {
	echo "Error: You are using PHP for CGI. To execute ".$script_file." from command line, you must use PHP for CLI mode.\n";
	exit(-1);
}

if (! isset($argv[1]) || ! $argv[1]) {
	print "Usage: ".$script_file." securitykey userlogin cronjobid(optional)\n";
	exit(-1);
}
$key=$argv[1];

if (! isset($argv[2]) || ! $argv[2]) {
	print "Usage: ".$script_file." securitykey userlogin cronjobid(optional)\n";
	exit(-1);
} else {
	$userlogin=$argv[2];
}

require_once ($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/cron/class/cronjob.class.php");
require_once (DOL_DOCUMENT_ROOT.'/user/class/user.class.php');

// Global variables
$version=DOL_VERSION;
$error=0;


/*
 * Main
 */

@set_time_limit(0);
print "***** ".$script_file." (".$version.") pid=".getmypid()." *****\n";

// Check security key
if ($key != $conf->global->CRON_KEY)
{
	print "Error: securitykey is wrong\n";
	exit(-1);
}

// Check user login
$user=new User($db);
$result=$user->fetch('',$userlogin);
if ($result < 0)
{
	echo "User Error: ".$user->error;
	dol_syslog("cron_run_jobs.php:: User Error:".$user->error, LOG_ERR);
	exit(-1);
}
else
{
	if (empty($user->id))
	{
		echo " User user login: ".$userlogin." do not exists";
		dol_syslog(" User user login:".$userlogin." do not exists", LOG_ERR);
		exit(-1);
	}
}

if (isset($argv[3]) || $argv[3])
{
	$id = $argv[3];
}

// create a jobs object
$object = new Cronjob($db);

$filter=array();
if (empty($id)) {
	$filter=array();
	$filter['t.rowid']=$id;
}

$result = $object->fetch_all('DESC','t.rowid', 0, 0, 1, $filter);
if ($result<0)
{
	echo "Error: ".$object->error;
	dol_syslog("cron_run_jobs.php:: fetch Error ".$object->error, LOG_ERR);
	exit(-1);
}

// current date
$now=dol_now();

if(is_array($object->lines) && (count($object->lines)>0))
{
		// Loop over job
		foreach($object->lines as $line)
		{

			//If date_next_jobs is less of current dat, execute the program, and store the execution time of the next execution in database
			if (($line->datenextrun < $now) && $line->dateend < $now){
				$cronjob=new Cronjob($db);
				$result=$cronjob->fetch($line->id);
				if ($result<0) {
					echo "Error:".$cronjob->error;
					dol_syslog("cron_run_jobs.php:: fetch Error".$cronjob->error, LOG_ERR);
					exit(-1);
				}
				// execute methode
				$result=$cronjob->run_jobs($userlogin);
				if ($result<0) {
					echo "Error:".$cronjob->error;
					dol_syslog("cron_run_jobs.php:: run_jobs Error".$cronjob->error, LOG_ERR);
					exit(-1);
				}

					// we re-program the next execution and stores the last execution time for this job
				$result=$cronjob->reprogram_jobs($userlogin);
				if ($result<0) {
					echo "Error:".$cronjob->error;
					dol_syslog("cron_run_jobs.php:: reprogram_jobs Error".$cronjob->error, LOG_ERR);
					exit(-1);
				}

			}
		}
}

$db->close();

exit(0);
?>
