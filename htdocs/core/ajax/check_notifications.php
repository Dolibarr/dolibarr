<?php
/* Copyright (C) 2016	   Sergio Sanchis		<sergiosanchis@hotmail.com>
 * Copyright (C) 2017	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019      Frédéric France      <frederic.france@netlogic.fr>
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

if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', '1');
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', '1'); // Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
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
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOREQUIRETRAN')) {
	define('NOREQUIRETRAN', '1');
}

//if (! defined('NOREQUIREUSER'))            define('NOREQUIREUSER', '1');					// Do not load object $user
//if (! defined('NOREQUIRESOC'))             define('NOREQUIRESOC', '1');					// Do not load object $mysoc
//if (! defined('NOREQUIRETRAN'))            define('NOREQUIRETRAN', '1');					// Do not load object $langs
//if (! defined('NOSCANGETFORINJECTION'))    define('NOSCANGETFORINJECTION', '1');			// Do not check injection attack on GET parameters
//if (! defined('NOSCANPOSTFORINJECTION'))   define('NOSCANPOSTFORINJECTION', '1');			// Do not check injection attack on POST parameters
//if (! defined('NOCSRFCHECK'))              define('NOCSRFCHECK', '1');					// Do not check CSRF attack (test on referer + on token if option MAIN_SECURITY_CSRF_WITH_TOKEN is on).
//if (! defined('NOTOKENRENEWAL'))           define('NOTOKENRENEWAL', '1');					// Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
//if (! defined('NOSTYLECHECK'))             define('NOSTYLECHECK', '1');					// Do not check style html tag into posted data
//if (! defined('NOIPCHECK'))                define('NOIPCHECK', '1');						// Do not check IP defined into conf $dolibarr_main_restrict_ip
//if (! defined('NOREQUIREMENU'))            define('NOREQUIREMENU', '1');					// If there is no need to load and show top and left menu
//if (! defined('NOREQUIREHTML'))            define('NOREQUIREHTML', '1');					// If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))            define('NOREQUIREAJAX', '1');       		  	// Do not load ajax.lib.php library
//if (! defined("NOLOGIN"))                  define("NOLOGIN", '1');						// If this page is public (can be called outside logged session)
//if (! defined("MAIN_LANG_DEFAULT"))        define('MAIN_LANG_DEFAULT', 'auto');			// Force lang to a particular value
//if (! defined("MAIN_AUTHENTICATION_MODE")) define('MAIN_AUTHENTICATION_MODE', 'aloginmodule');		// Force authentication handler
//if (! defined("NOREDIRECTBYMAINTOLOGIN"))  define('NOREDIRECTBYMAINTOLOGIN', '1');		// The main.inc.php does not make a redirect if not logged, instead show simple error message
//if (! defined("XFRAMEOPTIONS_ALLOWALL"))   define('XFRAMEOPTIONS_ALLOWALL', '1');			// Do not add the HTTP header 'X-Frame-Options: SAMEORIGIN' but 'X-Frame-Options: ALLOWALL'


require '../../main.inc.php';

//$time = (int) GETPOST('time', 'int'); // Use the time parameter that is always increased by time_update, even if call is late
$time = dol_now();
$action = GETPOST('action', 'aZ09');
$listofreminderids = GETPOST('listofreminderids', 'aZ09');


/*
 * Actions
 */

if ($action == 'stopreminder') {
	dol_syslog("Clear notification for listofreminderids=".$listofreminderids);
	$listofreminderid = GETPOST('listofreminderids', 'intcomma');

	// Set the reminder as done
	$sql = 'UPDATE '.MAIN_DB_PREFIX.'actioncomm_reminder SET status = 1';
	$sql .= ' WHERE status = 0 AND rowid IN ('.$db->sanitize($db->escape($listofreminderid)).')';
	$sql .= ' AND fk_user = '.((int) $user->id).' AND entity = '.((int) $conf->entity);
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
	//}

	include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

	// Clean database
	$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'actioncomm_reminder';
	$sql .= " WHERE dateremind < '".$db->idate(dol_time_plus_duree(dol_now(), -1, 'm'))."'";
	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}

	exit;
}


/*
 * View
 */

top_httphead('application/json');

global $user, $db, $langs, $conf;

$eventfound = array();
//Uncomment this to force a test
//$eventfound[]=array('type'=>'agenda', 'id'=>1, 'tipo'=>'eee', 'location'=>'aaa');

//dol_syslog('time='.$time.' $_SESSION[auto_ck_events_not_before]='.$_SESSION['auto_check_events_not_before']);

// TODO Try to make a solution with only a javascript timer that is easier. Difficulty is to avoid notification twice when several tabs are opened.
// This need to extend period to be sure to not miss and save in session what we notified to avoid duplicate.
if (empty($_SESSION['auto_check_events_not_before']) || $time >= $_SESSION['auto_check_events_not_before'] || GETPOST('forcechecknow', 'int')) {
	/*$time_update = (int) $conf->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY; // Always defined
	if (!empty($_SESSION['auto_check_events_not_before']))
	{
		// We start scan from the not before so if two tabs were opend at differents seconds and we close one (so the js timer),
		// then we are not losing periods
		$starttime = $_SESSION['auto_check_events_not_before'];
		// Protection to avoid too long sessions
		if ($starttime < ($time - (int) $conf->global->MAIN_SESSION_TIMEOUT))
		{
			dol_syslog("We ask to check browser notification on a too large period. We fix this with current date.");
			$starttime = $time;
		}
	} else {
		$starttime = $time;
	}

	$_SESSION['auto_check_events_not_before'] = $time + $time_update;
	*/

	// Force save of the session change we did.
	// WARNING: Any change in sessions after that will not be saved !
	session_write_close();

	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';


	dol_syslog('NEW $_SESSION[auto_check_events_not_before]='.(empty($_SESSION['auto_check_events_not_before']) ? '' : $_SESSION['auto_check_events_not_before']));

	$sql = 'SELECT a.id as id_agenda, a.code, a.datep, a.label, a.location, ar.rowid as id_reminder, ar.dateremind, ar.fk_user as id_user_reminder';
	$sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
	if (!empty($user->conf->MAIN_USER_WANT_ALL_EVENTS_NOTIFICATIONS)) {
		$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_reminder as ar ON a.id = ar.fk_actioncomm AND ar.fk_user = '.((int) $user->id);
		$sql .= ' WHERE a.code <> "AC_OTH_AUTO"';
		$sql .= ' AND (';
		$sql .= " (ar.typeremind = 'browser' AND ar.dateremind < '".$db->idate(dol_now())."' AND ar.status = 0 AND ar.entity = ".$conf->entity;
		$sql .= ' )';
	} else {
		$sql .= ' JOIN '.MAIN_DB_PREFIX.'actioncomm_reminder as ar ON a.id = ar.fk_actioncomm AND ar.fk_user = '.((int) $user->id);
		$sql .= " AND ar.typeremind = 'browser' AND ar.dateremind < '".$db->idate(dol_now())."' AND ar.status = 0 AND ar.entity = ".$conf->entity;
	}
	$sql .= $db->order('datep', 'ASC');
	$sql .= ' LIMIT 10'; // Avoid too many notification at once

	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			// Message must be formated and translated to be used with javascript directly
			$event = array();
			$event['type'] = 'agenda';
			$event['id_reminder'] = $obj->id_reminder;
			$event['id_agenda'] = $obj->id_agenda;
			$event['id_user'] = $obj->id_user_reminder;
			$event['code'] = $obj->code;
			$event['label'] = $obj->label;
			$event['location'] = $obj->location;
			$event['reminder_date_formated_tzserver'] = dol_print_date($db->jdate($obj->dateremind), 'standard', 'tzserver');
			$event['event_date_start_formated_tzserver'] = dol_print_date($db->jdate($obj->datep), 'standard', 'tzserver');
			$event['reminder_date_formated'] = dol_print_date($db->jdate($obj->dateremind), 'standard', 'tzuser');
			$event['event_date_start_formated'] = dol_print_date($db->jdate($obj->datep), 'standard', 'tzuser');

			$eventfound[$obj->id_agenda] = $event;
		}
	} else {
		dol_syslog("Error sql = ".$db->lasterror(), LOG_ERR);
	}
}

print json_encode(array('pastreminders'=>$eventfound, 'nextreminder'=>''));
