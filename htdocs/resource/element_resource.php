<?php
/* Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *   	\file       resource/element_resource.php
 *		\ingroup    resource
 *		\brief      Page to show and manage linked resources to an element
 */


$res=0;
$res=@include("../main.inc.php");				// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

require 'class/resource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load traductions files requiredby by page
$langs->load("resource");
$langs->load("other");

/*
$sortorder			= GETPOST('sortorder','alpha');
$sortfield			= GETPOST('sortfield','alpha');
$page				= GETPOST('page','int');
*/

if( ! $user->rights->resource->read)
	accessforbidden();

$object=new Resource($db);

$hookmanager->initHooks(array('element_resource'));
$object->available_resources = array('resource');

// Get parameters
$id			    = GETPOST('id','int');
$action			= GETPOST('action','alpha');
$mode			= GETPOST('mode','alpha');
$lineid			= GETPOST('lineid','int');
$element 		= GETPOST('element','alpha');			// element_type
$element_id		= GETPOST('element_id','int');
$resource_id 	= GETPOST('fk_resource','int');
$resource_type	= GETPOST('resource_type','alpha');
$busy 			= GETPOST('busy','int');
$mandatory 		= GETPOST('mandatory','int');
$cancel			= GETPOST('cancel','alpha');
$confirm        = GETPOST('confirm','alpha');
$socid          = GETPOST('socid','int');

if ($socid > 0) 
{
    $element_id = $socid;
    $element = 'societe';
}

    
    
/*
 * Actions
 */

if ($action == 'add_element_resource' && ! $cancel)
{
	$error++;
	$res = 0;
	if (! ($resource_id > 0))
	{
	    $error++;
        setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Resource")), null, 'errors');
        $action='';
	}
	else
	{
        $objstat = fetchObjectByElement($element_id, $element);
	   
        $res = $objstat->add_element_resource($resource_id, $resource_type, $busy, $mandatory);
	}
	if (! $error && $res > 0)
	{
		setEventMessages($langs->trans('ResourceLinkedWithSuccess'), null, 'mesgs');
		header("Location: ".$_SERVER['PHP_SELF'].'?element='.$element.'&element_id='.$element_id);
		exit;
	}
}

// Update ressource
if ($action == 'update_linked_resource' && $user->rights->resource->write && !GETPOST('cancel') )
{
	$res = $object->fetch_element_resource($lineid);
	if($res)
	{
		$object->busy = $busy;
		$object->mandatory = $mandatory;

		$result = $object->update_element_resource($user);

		if ($result >= 0)
		{
			setEventMessages($langs->trans('RessourceLineSuccessfullyUpdated'), null, 'mesgs');
			header("Location: ".$_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id);
			exit;
		}
		else 
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// Delete a resource linked to an element
if ($action == 'confirm_delete_linked_resource' && $user->rights->resource->delete && $confirm === 'yes')
{
    $result = $object->delete_resource($lineid,$element);

    if ($result >= 0)
    {
        setEventMessages($langs->trans('RessourceLineSuccessfullyDeleted'), null, 'mesgs');
        header("Location: ".$_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id);
        exit;
    }
    else 
    {
        setEventMessages($object->error, $object->errors, 'errors');
    }
}

$parameters=array('resource_id'=>$resource_id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


$parameters=array('resource_id'=>$resource_id);
$reshook=$hookmanager->executeHooks('getElementResources',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');



/*
 * View
 */

$form=new Form($db);

$pagetitle=$langs->trans('ResourceElementPage');
llxHeader('',$pagetitle,'');


// Load available resource, declared by modules
$ret = count($object->available_resources);
if($ret == -1) {
	dol_print_error($db,$object->error);
	exit;
}
if(!$ret) {
	print '<div class="warning">'.$langs->trans('NoResourceInDatabase').'</div>';
}
else
{
	// Confirmation suppression resource line
	if ($action == 'delete_resource')
	{
		print $form->formconfirm("element_resource.php?element=".$element."&element_id=".$element_id."&id=".$id."&lineid=".$lineid,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResourceElement"),"confirm_delete_linked_resource",'','',1);
	}


	/*
	 * Specific to agenda module
	 */
	if ($element_id && $element == 'action')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

		$act = fetchObjectByElement($element_id,$element);
		if (is_object($act)) 
		{

			$head=actions_prepare_head($act);

			dol_fiche_head($head, 'resources', $langs->trans("Action"),0,'action');

			// Affichage fiche action en mode visu
			print '<table class="border" width="100%">';

			$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php">'.$langs->trans("BackToList").'</a>';

			// Ref
			print '<tr><td width="30%">'.$langs->trans("Ref").'</td><td colspan="3">';
			print $form->showrefnav($act, 'id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '');
			print '</td></tr>';

			// Type
			if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
			{
				print '<tr><td>'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';
			}

			// Title
			print '<tr><td>'.$langs->trans("Title").'</td><td colspan="3">'.$act->label.'</td></tr>';
			print '</table>';

			dol_fiche_end();
		}
	}

	/*
	 * Specific to thirdparty module
	 */
	if ($element_id && $element == 'societe')
	{
		$socstatic = fetchObjectByElement($element_id,$element);
		if (is_object($socstatic)) 
		{
		    $savobject = $object;
		    
		    $object = $socstatic;
		    
			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			$head = societe_prepare_head($socstatic);

			dol_fiche_head($head, 'resources', $langs->trans("ThirdParty"),0,'company');

            dol_banner_tab($socstatic, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
                
        	print '<div class="fichecenter">';
        
            print '<div class="underbanner clearboth"></div>';
        	print '<table class="border" width="100%">';
        
        	// Alias name (commercial, trademark or alias name)
        	print '<tr><td class="titelfield">'.$langs->trans('AliasNames').'</td><td colspan="3">';
        	print $socstatic->name_alias;
        	print "</td></tr>";
			
			print '</table>';

			print '</div>';
			
			dol_fiche_end();
			
			$object = $savobject;
		}
	}



	//print load_fiche_titre($langs->trans('ResourcesLinkedToElement'),'','');



	foreach ($object->available_resources as $modresources => $resources)
	{
		$resources=(array) $resources;	// To be sure $resources is an array
		foreach($resources as $resource_obj)
		{
			$element_prop = getElementProperties($resource_obj);

			//print '/'.$modresources.'/class/'.$resource_obj.'.class.php<br />';

			$path = '';
			if(strpos($resource_obj,'@'))
				$path .= '/'.$element_prop['module'];

			$linked_resources = $object->getElementResources($element,$element_id,$resource_obj);


			// If we have a specific template we use it
			if(file_exists(dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_add.tpl.php')))
			{
				$res=include dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_add.tpl.php');
			}
			else
			{
				$res=include DOL_DOCUMENT_ROOT . '/core/tpl/resource_add.tpl.php';
			}
            //var_dump($element_id);

			if ($mode != 'add' || $resource_obj != $resource_type)
			{
				//print load_fiche_titre($langs->trans(ucfirst($element_prop['element']).'Singular'));

				// If we have a specific template we use it
				if(file_exists(dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_view.tpl.php')))
				{
					$res=@include dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_view.tpl.php');

				}
				else
				{
					$res=include DOL_DOCUMENT_ROOT . '/core/tpl/resource_view.tpl.php';
				}
			}
		}
	}
}

llxFooter();

$db->close();
