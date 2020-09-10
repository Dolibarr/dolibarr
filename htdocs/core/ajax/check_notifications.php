<?php
/* Copyright (C) 2016	   Sergio Sanchis		<sergiosanchis@hotmail.com>
 * Copyright (C) 2017	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2019       Frédéric France         <frederic.france@netlogic.fr>
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

if (!defined('NOCSRFCHECK')) define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL', '1'); // Disables token renewal
if (!defined('NOREQUIREMENU'))  define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))  define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX', '1');
if (!defined('NOREQUIRESOC'))   define('NOREQUIRESOC', '1');

require '../../main.inc.php';


/*
 * View
 */

top_httphead('text/html'); // TODO Use a json mime type

global $user, $db, $langs, $conf;

$time = (int) GETPOST('time', 'int'); // Use the time parameter that is always increased by time_update, even if call is late
//$time=dol_now();


$eventfound = array();
//Uncomment this to force a test
//$eventfound[]=array('type'=>'agenda', 'id'=>1, 'tipo'=>'eee', 'location'=>'aaa');

//dol_syslog('time='.$time.' $_SESSION[auto_ck_events_not_before]='.$_SESSION['auto_check_events_not_before']);

// TODO Try to make a solution with only a javascript timer that is easier. Difficulty is to avoid notification twice when several tabs are opened.
if ($time >= $_SESSION['auto_check_events_not_before'])
{
    $time_update = (int) $conf->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY; // Always defined
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

    // Force save of session change we did.
    // WARNING: Any change in sessions after that will not be saved !
    session_write_close();

    require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';


    dol_syslog('NEW $_SESSION[auto_check_events_not_before]='.$_SESSION['auto_check_events_not_before']);

    $sql = 'SELECT id, arm.rowid as id_reminder';
    $sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm a';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_reminder arm ON (arm.fk_actioncomm = a.id AND arm.fk_user = '.$user->id.' ) ';
    $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'actioncomm_resources ar ON ar.fk_actioncomm = a.id';
    $sql .= ' WHERE';
    // TODO Try to make a solution with only a javascript timer that is easier. Difficulty is to avoid notification twice when several tabs are opened.
    // This need to extend period to be sure to not miss and save in session what we notified to avoid duplicate (save is not done yet).
    if (!empty($user->conf->MAIN_USER_WANT_ALL_EVENTS_NOTIFICATIONS))
    {
        $sql .= ' (';
	    $sql .= " ( arm.typeremind = 'browser' AND arm.dateremind < '".$db->idate(dol_now())."' AND arm.status = 0 ";
	    $sql .= ' OR ( datep BETWEEN "'.$db->idate($starttime).'" AND "'.$db->idate($time + $time_update - 1).'" )';
		$sql .= " AND a.code <> 'AC_OTH_AUTO'";
		$sql .= ' )';
    }
    else {
        $sql .= " AND arm.typeremind = 'browser' AND arm.dateremind < NOW() AND arm.status = 0  ";
    }

    $sql .= ' AND ar.element_type = "user"';
    $sql .= ' AND ar.fk_element = '.$user->id;
    $sql .= ' LIMIT 10'; // Avoid too many notification at once

    $resql = $db->query($sql);
    if ($resql) {
        $actionmod = new ActionComm($db);

        while ($obj = $db->fetch_object($resql))
        {
            // Load translation files required by the page
            $langs->loadLangs(array('agenda', 'commercial'));

            $actionmod->fetch($obj->id);

            $actioncommReminder = new ActionCommReminder($db);
            $res = $actioncommReminder->fetch($obj->id_reminder);

            $event = array();
            $event['type'] = 'agenda';
            $event['id'] = $actionmod->id;

            //Message "reminder"
            if ($res > 0 && $actioncommReminder->status == 0 && $actioncommReminder->dateremind < dol_now()){
                $event['tipo'] = $langs->transnoentities('Event');
                $event['titulo'] = $actionmod->label;
                $event['location'] = $langs->transnoentities('Location').': '.$actionmod->location;
                $event['date'] = $langs->transnoentities('Date').': '. dol_print_date($actionmod->datep, 'Y-m-d H:i:s');

                //Update reminder to status "done"
                $actioncommReminder->status = $actioncommReminder::STATUS_DONE;
                $res = $actioncommReminder->update($user);
            } else {
                // Message must be formated and translated to be used with javascript directly
                $event['tipo'] = $langs->transnoentities('Action'.$actionmod->code);
                $event['titulo'] = $actionmod->label;
                $event['location'] = $langs->transnoentities('Location').': '.$actionmod->location;
            }

            $eventfound[] = $event;
        }
    } else {
        dol_syslog("Error sql = ".$db->lasterror(), LOG_ERR);
    }
}

print json_encode($eventfound);
