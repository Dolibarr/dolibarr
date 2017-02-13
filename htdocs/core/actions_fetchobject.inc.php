<?php
/* Copyright (C) 2014      Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2015      Frederic France       <frederic.france@free.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file           htdocs/core/actions_fetchobject.inc.php
 *  \brief          Code for actions on fetching object page
 */


// $action must be defined
// $object must be defined (object is loaded in this file with fetch)
// $cancel must be defined
// $id or $ref must be defined (object is loaded in this file with fetch)

if (($id > 0 || (! empty($ref) && ! in_array($action, array('create','createtask')))) && empty($cancel))
{
    $ret = $object->fetch($id,$ref);
    if ($ret > 0)
    {
        $object->fetch_thirdparty();
        $id = $object->id;
    }
    else
    {
        setEventMessages($object->error, $object->errors, 'errors');
        $action='';
    }
}
