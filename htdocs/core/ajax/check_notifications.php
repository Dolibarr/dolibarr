<?php
/* Copyright (C) 2016	   Sergio Sanchis		<sergiosanchis@hotmail.com>
 * Copyright (C) 2017	   Juanjo Menent		<jmenent@2byte.es>
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

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1'); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

global $user, $db, $langs, $conf;

$time = GETPOST('time');
//$time=dol_now();

session_start();

$time_update = (empty($conf->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY)?'3':(int) $conf->global->MAIN_BROWSER_NOTIFICATION_FREQUENCY);

$eventos = array();
//$eventos[]=array('type'=>'agenda', 'id'=>1, 'tipo'=>'eee', 'location'=>'aaa');

// TODO Remove test on session. Timer should be managed by a javascript timer
if ($_SESSION['auto_check_events'] <= (int) $time) 
{
    $_SESSION['auto_check_events'] = $time + $time_update;

    $sql = 'SELECT id';
    $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm a, ' . MAIN_DB_PREFIX . 'actioncomm_resources ar';
    $sql .= ' WHERE a.id = ar.fk_actioncomm';
    // TODO Try to make a solution with only a javascript timer that is easier. Difficulty is to avoid notification twice when.
    // This need to extend period to be sure to not miss and save what we notified to avoid duplicate (save is not done yet).
    $sql .= " AND datep BETWEEN '" . $db->idate($time + 1) . "' AND '" . $db->idate($time + $time_update) . "'";
    $sql .= ' AND a.code <> "AC_OTH_AUTO"';
    $sql .= ' AND ar.element_type = "user"';
    $sql .= ' AND ar.fk_element = ' . $user->id;
    $sql .= ' LIMIT 10';    // Avoid too many notification at once

    $resql = $db->query($sql);
    if ($resql) {

        $actionmod = new ActionComm($db);

        while ($obj = $db->fetch_object($resql)) {

            $actionmod->fetch($obj->id);

            $event = array();
            $event['type'] = 'agenda';
            $event['id'] = $actionmod->id;
            $event['tipo'] = $langs->transnoentities('Action' . $actionmod->code);
            $event['titulo'] = $actionmod->label;
            $event['location'] = $actionmod->location;
            
            $eventos[] = $event;
        }
    }

}

print json_encode($eventos);

