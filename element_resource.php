<?php
/* Module to manage locations, buildings, floors and rooms into Dolibarr ERP/CRM
 * Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
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

// Load traductions files requiredby by page
$langs->load("resource@resource");
$langs->load("other");

// Get parameters
$id					= GETPOST('id','int');
$action				= GETPOST('action','alpha');
$mode				= GETPOST('mode','alpha');
$lineid				= GETPOST('lineid','int');
$element 			= GETPOST('element','alpha');
$element_id			= GETPOST('element_id','int');
$resource_id		= GETPOST('resource_id','int');
$resource_type		= GETPOST('resource_type','alpha');

/*
$sortorder			= GETPOST('sortorder','alpha');
$sortfield			= GETPOST('sortfield','alpha');
$page				= GETPOST('page','int');
*/

if( ! $user->rights->place->read)
	accessforbidden();

$object=new Resource($db);

$hookmanager->initHooks(array('element_resource'));

$parameters=array('resource_id'=>$resource_id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks



/***************************************************
 * VIEW
*
* Put here all code to build page
****************************************************/

$pagetitle=$langs->trans('ResourceElementPage');
llxHeader('',$pagetitle,'');


$form=new Form($db);


// Load available resource, declared by modules
$ret = $object->fetch_all_available();
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
		print $form->formconfirm("element_resource.php?element=".$element."&element_id=".$element_id."&lineid=".$lineid,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResourceElement"),"confirm_delete_resource",'','',1);
	}


	/*
	 * Specific to agenda module
	 */
	if($element_id && $element == 'action')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

		$act = $object->fetchObjectByElement($element_id,$element);
		if(is_object($act)) {

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

			print '</div>';
		}
	}
	/*
	 * Specific to thirdparty module
	 */
	if($element_id && $element == 'societe')
	{
		$socstatic = $object->fetchObjectByElement($element_id,$element);
		if(is_object($socstatic)) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			$head = societe_prepare_head($socstatic);

			dol_fiche_head($head, 'resources', $langs->trans("ThirdParty"),0,'company');

			// Affichage fiche action en mode visu
			print '<table class="border" width="100%">';

			//$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php">'.$langs->trans("BackToList").'</a>';

			// Name
        print '<tr><td width="25%">'.$langs->trans('ThirdPartyName').'</td>';
        print '<td colspan="3">';
        print $form->showrefnav($socstatic, 'socid', '', ($user->societe_id?0:1), 'rowid', 'nom');
        print '</td>';
        print '</tr>';
			print '</table>';

			print '</div>';
		}
	}



	print_fiche_titre($langs->trans('ResourcesLinkedToElement'),'','resource_32@resource');


	foreach ($object->available_resources as $modresources => $resources)
	{
		$langs->load($modresources);
		//print '<h2>'.$modresources.'</h2>';
		//var_dump($resources);

		$resources=(array) $resources;	// To be sure $resources is an array
		foreach($resources as $resource_obj)
		{
			$element_prop = $object->getElementProperties($resource_obj);
			//var_dump($element_prop);

			print_titre($langs->trans(ucfirst($element_prop['element']).'Singular'));

			//print '/'.$modresources.'/class/'.$resource_obj.'.class.php<br />';

			$linked_resources = $object->getElementResources($element,$element_id,$resource_obj);

			if ( $mode == 'add' && $resource_obj == $resource_type)
			{
				//print '/'.$element_prop['module'].'/core/tpl/resource_'.$element_prop['element'].'_'.$mode.'.tpl.php'.'<BR>';

				$path = $element_prop['module'];
				if(strpos($element_prop['module'],'@'))
					$path .= '/'.$element_prop['module'];
				
				// If we have a specific template we use it
				if(file_exists(dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_'.$mode.'.tpl.php')))
				{
					$res=include dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_'.$mode.'.tpl.php');

				}
				else
				{
					$res=@include dol_buildpath('/resource/core/tpl/resource_add.tpl.php');

				}
			}
			else
			{
				//print '/'.$element_prop['module'].'/core/tpl/resource_'.$element_prop['element'].'_view.tpl.php';

				// If we have a specific template we use it
				if(file_exists(dol_buildpath('/'.$element_prop['module'].'/core/tpl/resource_'.$element_prop['element'].'_view.tpl.php')))
				{
					$res=@include dol_buildpath('/'.$element_prop['module'].'/core/tpl/resource_'.$element_prop['element'].'_view.tpl.php');

				}
				else
				{
					$res=include dol_buildpath('/resource/core/tpl/resource_view.tpl.php');

				}
			}

			if($resource_obj!=$resource_type )
			{
				print '<div class="tabsAction">';
				print '<div class="inline-block divButAction">';
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?mode=add&resource_type='.$resource_obj.'&element='.$element.'&element_id='.$element_id.'">Add resource</a>';
				print '</div>';
				print '</div>';
			}
		}
	}
}

llxFooter();

$db->close();
