<?php
/* Copyright (C) 2015   Peter Fontaine      <contact@peterfontaine.fr>
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
 *	\file       core/lib/dashboardlines.lib.php
 *	\brief      Functions for dashboard
 *	\ingroup    dashboardlines
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

function dbl_get_dashboardlines(&$dashboardlines)
{
    global $user, $db;

    $dbl = dbl_get_lines_from_db();

    if (! empty($dbl))
            {
                foreach ($dbl as $line)
                    {
                        if (! $line['right']) $evalright = 1;
            else $evalright = verifCond($line['right']);
            if ($evalright && ($line['allow_external'] || !$user->socid ))
                            {
                                include_once DOL_DOCUMENT_ROOT.$line['class_file'];
                                $board = new $line['class_name']($db);
                                if (! $line['extra_param']) $dashboardlines[] = $board->$line['class_func']($user);
                else $dashboardlines[] = $board->$line['class_func']($user, $line['extra_param']);
            }
        }
    }
}

function dbl_get_lines_from_db()
{
        global $conf, $db;

    $entity = $conf->entity;

    $sql = "SELECT * FROM ".MAIN_DB_PREFIX."dashboardlines ";
    $sql.= "WHERE entity = ".$entity;

    dol_syslog("Get modules dashboard lines", LOG_DEBUG);
    $resql = $db->query($sql);

    $dbl = array();

    if ($resql) {
               while ( $obj = $db->fetch_object($resql) ) {
                        $line = array(
                                'class_file'     => $obj->class_file ,
                                'class_name'     => $obj->class_name ,
                                'class_func'     => $obj->class_func ,
                                'extra_param'    => $obj->extra_param ,
                                'allow_external' => $obj->allow_external ,
                                'right'          => $obj->perm
                                );

                        $dbl[] = $line;
                    }
    }

    return $dbl;
}
