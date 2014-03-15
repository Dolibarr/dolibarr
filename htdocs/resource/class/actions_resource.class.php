<?php
/* Copyright (C) 2013 Jean-François FERRY  <jfefe@aternatik.fr>
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
 *       \file       resource/class/actions_resource.class.php
 *       \brief      Place module actions
 */

class ActionsResource
{

	var $db;
	var $error;
	var $errors=array();

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	*/
	function __construct($db)
	{
		$this->db = $db;
	}

	function doActions($parameters, &$object, &$action) {

		global $langs,$user;
		$langs->load('resource@resource');

		if (in_array('element_resource',explode(':',$parameters['context'])))
		{
			// Efface une ressource
			if ($action == 'confirm_delete_resource' && $user->rights->resource->delete && GETPOST('confirm') == 'yes')
			{
				$res = $object->fetch(GETPOST('lineid'));
				if($res)
				{
					$result = $object->delete_resource(GETPOST('lineid'),GETPOST('element'));

					if ($result >= 0)
					{
						setEventMessage($langs->trans('RessourceLineSuccessfullyDeleted'));
						Header("Location: ".$_SERVER['PHP_SELF']."?element=".GETPOST('element')."&element_id=".GETPOST('element_id'));
						exit;
					}
					else {
						setEventMessage($object->error,'errors');
					}
				}
			}

			// Update ressource
			if ($action == 'update_resource' && $user->rights->resource->write && !GETPOST('cancel') )
			{
				$res = $object->fetch(GETPOST('lineid'));
				if($res)
				{
					$object->id = GETPOST('lineid');
					$object->busy = GETPOST('busy');
					$object->mandatory = GETPOST('mandatory');

					$result = $object->update();

					if ($result >= 0)
					{
						setEventMessage($langs->trans('RessourceLineSuccessfullyUpdated'));
						Header("Location: ".$_SERVER['PHP_SELF']."?element=".GETPOST('element')."&element_id=".GETPOST('element_id'));
						exit;
					}
					else {
						setEventMessage($object->error,'errors');
					}
				}
			}
		}

	}
}
