<?php
/* Copyright (C) 2012      Nicolas Villa aka Boyquotes http://informetic.fr
 * Copyright (C) 2013      Florian Henry <forian.henry@open-cocnept.pro
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
 *  \file       cron/public/cron/cron_run_jobs.php
 *  \ingroup    cron
 *  \brief      Execute pendings jobs
 */
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOLOGIN'))   define('NOLOGIN','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

// librarie core
// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
if (! $res) {
	$res = @include("../../../main.inc.php"); // From "custom" directory
}
if (! $res) die("Include of master.inc.php fails");

// librarie jobs
dol_include_once("/cron/class/cronjob.class.php");


global $langs, $conf;

// Check the key, avoid that a stranger starts cron
$key = $_GET['securitykey'];
if (empty($key)) {
	echo 'securitykey is require';
	exit;
}
if($key != $conf->global->CRON_KEY)
{
	echo 'securitykey is wrong';
	exit;
}
// Check the key, avoid that a stranger starts cron
$userlogin = $_GET['userlogin'];
if (empty($userlogin)) {
	echo 'userlogin is require';
	exit;
}
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
$user=new User($db);
$result=$user->fetch('',$userlogin);
if ($result<0) {
	echo "User Error:".$user->error;
	dol_syslog("cron_run_jobs.php:: User Error:".$user->error, LOG_ERR);
	exit;
}else {
	if (empty($user->id)) {
		echo " User user login:".$userlogin." do not exists";
		dol_syslog(" User user login:".$userlogin." do not exists", LOG_ERR);
		exit;
	}
}
$id = $_GET['id'];

// Language Management
$langs->load("admin");
$langs->load("cron@cron");

// create a jobs object
$object = new Cronjob($db);

$filter=array();
if (empty($id)) {
	$filter=array();
	$filter['t.rowid']=$id;
}

$result = $object->fetch_all('DESC','t.rowid', 0, 0, 1, $filter);
if ($result<0) {
	echo "Error:".$cronjob->error;
	dol_syslog("cron_run_jobs.php:: fetch Error".$cronjob->error, LOG_ERR);
	exit;
}

// current date
$now=dol_now();

if(is_array($object->lines) && (count($object->lines)>0)){
	// Loop over job
	foreach($object->lines as $line){

		dol_syslog("cron_run_jobs.php:: fetch cronjobid:".$line->id, LOG_ERR);

		//If date_next_jobs is less of current dat, execute the program, and store the execution time of the next execution in database
		if ((($line->datenextrun <= $now) && $line->dateend < $now)
				|| ((empty($line->datenextrun)) && (empty($line->dateend)))){

			dol_syslog("cron_run_jobs.php:: torun line->datenextrun:".dol_print_date($line->datenextrun,'dayhourtext')." line->dateend:".dol_print_date($line->dateend,'dayhourtext')." now:".dol_print_date($now,'dayhourtext'), LOG_ERR);

			$cronjob=new Cronjob($db);
			$result=$cronjob->fetch($line->id);
			if ($result<0) {
				echo "Error:".$cronjob->error;
				dol_syslog("cron_run_jobs.php:: fetch Error".$cronjob->error, LOG_ERR);
				exit;
			}
			// execute methode
			$result=$cronjob->run_jobs($userlogin);
			if ($result<0) {
				echo "Error:".$cronjob->error;
				dol_syslog("cron_run_jobs.php:: run_jobs Error".$cronjob->error, LOG_ERR);
				exit;
			}

				// we re-program the next execution and stores the last execution time for this job
			$result=$cronjob->reprogram_jobs($userlogin);
			if ($result<0) {
				echo "Error:".$cronjob->error;
				dol_syslog("cron_run_jobs.php:: reprogram_jobs Error".$cronjob->error, LOG_ERR);
				exit;
			}

		}
	}
	echo "OK";
} else {
	echo "No Jobs to run";
}
