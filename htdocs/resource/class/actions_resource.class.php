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

/**
 * Actions class file for resources
 *
 * TODO Remove this class and replace a method into commonobject
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

	/**
	 * doActions for resource module
	 *
	 * @param 	array 	$parameters 	parameters
	 * @param 	Object 	&$object 		object
	 * @param 	string 	&$action 		action
	 * @return	void
	 */
	/* Why a hook action ? TODO Remove this class and replace a method into commonobject
	function doActions($parameters, &$object, &$action)
	{
		global $langs,$user;
		$langs->load('resource');

		if (in_array('resource_card',explode(':',$parameters['context'])))
		{
		    if($action == 'confirm_delete_resource' && !GETPOST('cancel'))
		    {
		        $res = $object->fetch(GETPOST('id'));
		        if($res)
		        {

		            $result = $object->delete(GETPOST('id'));

		            if ($result >= 0)
		            {
		                setEventMessage($langs->trans('RessourceSuccessfullyDeleted'));
		                Header("Location: list.php");
		                exit;
		            }
		            else {
		                setEventMessage($object->error,'errors');
		            }
		        }
		        else
		        {
		            setEventMessage($object->error,'errors');
		        }
		    }
		}
		if (in_array('element_resource',explode(':',$parameters['context'])))
		{

		    $element_id = GETPOST('element_id','int');
		    $element = GETPOST('element','alpha');
		    $resource_type = GETPOST('resource_type');

		    $fk_resource = GETPOST('fk_resource');

		    $busy = GETPOST('busy','int');
		    $mandatory = GETPOST('mandatory','int');

		    if($action == 'add_element_resource' && !GETPOST('cancel'))
		    {
		        $objstat = fetchObjectByElement($element_id,$element);

		        $res = $objstat->add_element_resource($fk_resource,$resource_type,$busy,$mandatory);


		        if($res > 0)
		        {
		            setEventMessage($langs->trans('ResourceLinkedWithSuccess'),'mesgs');
		            header("Location: ".$_SERVER['PHP_SELF'].'?element='.$element.'&element_id='.$element_id);
		            exit;
		        }
		        else
		        {
		            setEventMessage($langs->trans('ErrorWhenLinkingResource'),'errors');
		            header("Location: ".$_SERVER['PHP_SELF'].'?mode=add&resource_type='.$resource_type.'&element='.$element.'&element_id='.$element_id);
		            exit;
		        }
		    }

			// Delete a resource linked to an element
			if ($action == 'confirm_delete_linked_resource' && $user->rights->resource->delete && GETPOST('confirm') == 'yes')
			{
				$res = $object->fetch(GETPOST('id'));
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
				else
				{
				    setEventMessage($object->error,'errors');
				}
			}

			// Update ressource
			if ($action == 'update_linked_resource' && $user->rights->resource->write && !GETPOST('cancel') )
			{
				$res = $object->fetch_element_resource(GETPOST('lineid'));
				if($res)
				{

					$object->busy = GETPOST('busy');
					$object->mandatory = GETPOST('mandatory');

					$result = $object->update_element_resource($user);

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
	}*/
}
