<?php
/* Copyright (C) 2015-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_dellink.inc.php
 *  \brief			Code for actions on linking and deleting link between elements
 */


// $action must be defined
// $object must be defined
// $permissiondellink must be defined

$dellinkid = GETPOST('dellinkid', 'int');
$addlinkid = GETPOST('idtolinkto', 'int');

// Link invoice to order
if ($action == 'addlink' && ! empty($permissiondellink) && ! GETPOST('cancel', 'alpha') && $id > 0 && $addlinkid > 0)
{
    $object->fetch($id);
    $object->fetch_thirdparty();
    $result = $object->add_object_linked(GETPOST('addlink', 'alpha'), $addlinkid);
}

// Delete link
if ($action == 'dellink' && ! empty($permissiondellink) && ! GETPOST('cancel', 'alpha') && $dellinkid > 0)
{
	$result=$object->deleteObjectLinked(0, '', 0, '', $dellinkid);
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}
