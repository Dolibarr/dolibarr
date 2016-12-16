<?php
/* Copyright (C) 2014 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_setnotes.inc.php
 *  \brief			Code for actions on setting notes of object page
 */


// $action must be defined
// $permissionnote must be defined to permission to edit object
// $object must be defined (object is loaded in this file with fetch)
// $id must be defined (object is loaded in this file with fetch)

// Set public note
if ($action == 'setnote_public' && ! empty($permissionnote) && ! GETPOST('cancel'))
{
	if (empty($action) || ! is_object($object) || empty($id)) dol_print_error('','Include of actions_setnotes.inc.php was done but required variable was not set before');
	if (empty($object->id)) $object->fetch($id);	// Fetch may not be already done
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_public'), ENT_QUOTES),'_public');
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}
// Set public note
else if ($action == 'setnote_private' && ! empty($permissionnote) && ! GETPOST('cancel'))
{
	if (empty($action) || ! is_object($object) || empty($id)) dol_print_error('','Include of actions_setnotes.inc.php was done but required variable was not set before');
	if (empty($object->id)) $object->fetch($id);	// Fetch may not be already done
	$result=$object->update_note(dol_html_entity_decode(GETPOST('note_private'), ENT_QUOTES),'_private');
	if ($result < 0) setEventMessages($object->error, $object->errors, 'errors');
}
