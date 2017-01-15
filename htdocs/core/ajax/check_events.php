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
 *
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

global $user, $db, $langs, $conf;

$time = GETPOST('time');

session_start();

//TODO Configure how long the upgrade will take
$time_update = 60;

if (! empty($conf->global->AGENDA_NOTIFICATION)) {
    if ($_SESSION['auto_check_events'] <= (int) $time) {
        $_SESSION['auto_check_events'] = $time + $time_update;

        $eventos = array();

        $sql = 'SELECT id';
        $sql .= ' FROM ' . MAIN_DB_PREFIX . 'actioncomm a, ' . MAIN_DB_PREFIX . 'actioncomm_resources ar';
        $sql .= ' WHERE datep BETWEEN ' . $db->idate($time + 1) . ' AND ' . $db->idate($time + $time_update);
        $sql .= ' AND a.id = ar.fk_actioncomm';
        $sql .= ' AND a.code <> "AC_OTH_AUTO"';
        $sql .= ' AND ar.element_type = "user"';
        $sql .= ' AND ar.fk_element = ' . $user->id;

        $resql = $db->query($sql);

        if ($resql) {

            $actionmod = new ActionComm($db);

            while ($obj = $db->fetch_object($resql)) {

                $event = array();

                $actionmod->fetch($obj->id);

                $event['id'] = $actionmod->id;
                $event['tipo'] = $langs->transnoentities('Action' . $actionmod->code);
                $event['titulo'] = $actionmod->label;
                $event['location'] = $actionmod->location;
                $eventos[] = $event;
                $actionmod->initAsSpecimen();

            }
        }

        print json_encode($eventos);
    }
}