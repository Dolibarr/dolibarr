<?php
/* Copyright (C) 2013		Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2016		Gilles Poirier 		<glgpoirier@gmail.com>
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
 *      \file       resource/element_resource.php
 *      \ingroup    resource
 *      \brief      Page to show and manage linked resources to an element
 */


$res=0;
$res=@include("../main.inc.php");                               // For root directory
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

// Load traductions files requiredby by page
$langs->load("resource");
$langs->load("other");

/*
$sortorder                      = GETPOST('sortorder','alpha');
$sortfield                      = GETPOST('sortfield','alpha');
$page                           = GETPOST('page','int');
*/

if( ! $user->rights->resource->read)
        accessforbidden();

$object=new Dolresource($db);

$hookmanager->initHooks(array('element_resource'));
$object->available_resources = array('dolresource');

// Get parameters
$id                     = GETPOST('id','int');                          // resource id
$element_id             = GETPOST('element_id','int');                  // element_id
$element_ref            = GETPOST('ref','alpha');                       // element ref
$element                = GETPOST('element','alpha');                   // element_type
$action                 = GETPOST('action','alpha');
$mode                   = GETPOST('mode','alpha');
$lineid                 = GETPOST('lineid','int');
$resource_id            = GETPOST('fk_resource','int');
$resource_type          = GETPOST('resource_type','alpha');
$busy                   = GETPOST('busy','int');
$mandatory              = GETPOST('mandatory','int');
$cancel                 = GETPOST('cancel','alpha');
$confirm                = GETPOST('confirm','alpha');
$socid                  = GETPOST('socid','int');

if ($socid > 0) // Special for thirdparty
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
if (!$ret) {
    print '<div class="warning">'.$langs->trans('NoResourceInDatabase').'</div>';
}
else
{
	// Confirmation suppression resource line
	if ($action == 'delete_resource')
	{
		print $form->formconfirm("element_resource.php?element=".$element."&element_id=".$element_id."&id=".$id."&lineid=".$lineid,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResourceElement"),"confirm_delete_linked_resource",'','',1);
	}


	// Specific to agenda module
	if (($element_id || $element_ref) && $element == 'action')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

		$act = fetchObjectByElement($element_id,$element, $element_ref);
		if (is_object($act))
		{

			$head=actions_prepare_head($act);

			dol_fiche_head($head, 'resources', $langs->trans("Action"),0,'action');

			$linkback =img_picto($langs->trans("BackToList"),'object_list','class="hideonsmartphone pictoactionview"');
			$linkback.= '<a href="'.DOL_URL_ROOT.'/comm/action/listactions.php">'.$langs->trans("BackToList").'</a>';

			// Link to other agenda views
			$out='';
			$out.=img_picto($langs->trans("ViewPerUser"),'object_calendarperuser','class="hideonsmartphone pictoactionview"');
			$out.='<a href="'.DOL_URL_ROOT.'/comm/action/peruser.php?action=show_peruser&year='.dol_print_date($act->datep,'%Y').'&month='.dol_print_date($act->datep,'%m').'&day='.dol_print_date($act->datep,'%d').'">'.$langs->trans("ViewPerUser").'</a>';
			$out.='<br>';
			$out.=img_picto($langs->trans("ViewCal"),'object_calendar','class="hideonsmartphone pictoactionview"');
			$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_month&year='.dol_print_date($act->datep,'%Y').'&month='.dol_print_date($act->datep,'%m').'&day='.dol_print_date($act->datep,'%d').'">'.$langs->trans("ViewCal").'</a>';
			$out.=img_picto($langs->trans("ViewWeek"),'object_calendarweek','class="hideonsmartphone pictoactionview"');
			$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($act->datep,'%Y').'&month='.dol_print_date($act->datep,'%m').'&day='.dol_print_date($act->datep,'%d').'">'.$langs->trans("ViewWeek").'</a>';
			$out.=img_picto($langs->trans("ViewDay"),'object_calendarday','class="hideonsmartphone pictoactionview"');
			$out.='<a href="'.DOL_URL_ROOT.'/comm/action/index.php?action=show_day&year='.dol_print_date($act->datep,'%Y').'&month='.dol_print_date($act->datep,'%m').'&day='.dol_print_date($act->datep,'%d').'">'.$langs->trans("ViewDay").'</a>';

			$linkback.=$out;

			dol_banner_tab($act, 'element_id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '', '&element='.$element, 0, '', '');

			print '<div class="underbanner clearboth"></div>';

			print '<table class="border" width="100%">';

			// Type
			if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
			{
				print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td colspan="3">'.$act->type.'</td></tr>';
			}

			// Full day event
			print '<tr><td class="titlefield">'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($act->fulldayevent, 3).'</td></tr>';

			// Date start
			print '<tr><td>'.$langs->trans("DateActionStart").'</td><td colspan="3">';
			if (! $act->fulldayevent) print dol_print_date($act->datep,'dayhour');
			else print dol_print_date($act->datep,'day');
			if ($act->percentage == 0 && $act->datep && $act->datep < ($now - $delay_warning)) print img_warning($langs->trans("Late"));
			print '</td>';
			print '</tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
			if (! $act->fulldayevent) print dol_print_date($act->datef,'dayhour');
			else print dol_print_date($act->datef,'day');
			if ($act->percentage > 0 && $act->percentage < 100 && $act->datef && $act->datef < ($now- $delay_warning)) print img_warning($langs->trans("Late"));
			print '</td></tr>';

			// Location
			if (empty($conf->global->AGENDA_DISABLE_LOCATION))
			{
				print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$act->location.'</td></tr>';
			}

			// Assigned to
			print '<tr><td class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
			$listofuserid=array();
			if (empty($donotclearsession))
			{
				if ($act->userownerid > 0) $listofuserid[$act->userownerid]=array('id'=>$act->userownerid,'transparency'=>$act->transparency);	// Owner first
				if (! empty($act->userassigned))	// Now concat assigned users
				{
					// Restore array with key with same value than param 'id'
					$tmplist1=$act->userassigned; $tmplist2=array();
					foreach($tmplist1 as $key => $val)
					{
						if ($val['id'] && $val['id'] != $act->userownerid) $listofuserid[$val['id']]=$val;
					}
				}
				$_SESSION['assignedtouser']=json_encode($listofuserid);
			}
			else
			{
				if (!empty($_SESSION['assignedtouser']))
				{
					$listofuserid=json_decode($_SESSION['assignedtouser'], true);
				}
			}
			print '<div class="assignedtouser">';
			print $form->select_dolusers_forevent('view', 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, '', 0, '', 'maxwidth300');
			print '</div>';
			if (in_array($user->id,array_keys($listofuserid)))
			{
				print '<div class="myavailability">';
				print $langs->trans("MyAvailability").': '.(($act->userassigned[$user->id]['transparency'] > 0)?$langs->trans("Busy"):$langs->trans("Available"));	// We show nothing if event is assigned to nobody
				print '</div>';
			}
			print '	</td></tr>';

			print '</table>';

			dol_fiche_end();
		}
	}

    // Specific to thirdparty module
	if (($element_id || $element_ref) && $element == 'societe')
	{
		$socstatic = fetchObjectByElement($element_id, $element, $element_ref);
		if (is_object($socstatic)) {

			$savobject = $object;
			$object = $socstatic;

			require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
			$head = societe_prepare_head($socstatic);

			dol_fiche_head($head, 'resources', $langs->trans("ThirdParty"), 0, 'company');

			dol_banner_tab($socstatic, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom', '', '&element='.$element);

			print '<div class="fichecenter">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border" width="100%">';

			// Alias name (commercial, trademark or alias name)
			print '<tr><td class="titlefield">' . $langs->trans('AliasNames') . '</td><td colspan="3">';
			print $socstatic->name_alias;
			print "</td></tr>";

			print '</table>';

			print '</div>';

			dol_fiche_end();

			$object = $savobject;
		}
	}

	// Specific to fichinter module
	if (($element_id || $element_ref) && $element == 'fichinter')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';

        $fichinter = new Fichinter($db);
        $fichinter->fetch($element_id, $element_ref);
        $fichinter->fetch_thirdparty();
        
		if (is_object($fichinter)) 
		{
			$head=fichinter_prepare_head($fichinter);
			dol_fiche_head($head, 'resource', $langs->trans("InterventionCard"),0,'intervention');

			// Intervention card
			$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';
			
			
			$morehtmlref='<div class="refidno">';
			// Ref customer
			//$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', 0, 1);
			//$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $object->ref_client, $object, 0, 'string', '', null, null, '', 1);
			// Thirdparty
			$morehtmlref.=$langs->trans('ThirdParty') . ' : ' . $fichinter->thirdparty->getNomUrl(1);
			// Project
			if (! empty($conf->projet->enabled))
			{
				$langs->load("projects");
				$morehtmlref.='<br>'.$langs->trans('Project') . ' ';
				if ($user->rights->commande->creer)
				{
					if ($action != 'classify')
						//$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $fichinter->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
						$morehtmlref.=' : ';
					if ($action == 'classify') {
						//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $fichinter->id, $fichinter->socid, $fichinter->fk_project, 'projectid', 0, 0, 1, 1);
						$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$fichinter->id.'">';
						$morehtmlref.='<input type="hidden" name="action" value="classin">';
						$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
						$morehtmlref.=$formproject->select_projects($fichinter->socid, $fichinter->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
						$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
						$morehtmlref.='</form>';
					} else {
						$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $fichinter->id, $fichinter->socid, $fichinter->fk_project, 'none', 0, 0, 0, 1);
					}
				} else {
					if (! empty($fichinter->fk_project)) {
						$proj = new Project($db);
						$proj->fetch($fichinter->fk_project);
						$morehtmlref.='<a href="'.DOL_URL_ROOT.'/projet/card.php?id=' . $fichinter->fk_project . '" title="' . $langs->trans('ShowProject') . '">';
						$morehtmlref.=$proj->ref;
						$morehtmlref.='</a>';
					} else {
						$morehtmlref.='';
					}
				}
			}
			$morehtmlref.='</div>';
			
			dol_banner_tab($fichinter, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&element='.$element, 0, '', '', 1);
			
			dol_fiche_end();
		}
	}


	// hook for other elements linked
	$parameters=array('element'=>$element, 'element_id'=>$element_id, 'element_ref'=>$element_ref);
	$reshook=$hookmanager->executeHooks('printElementTab',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');


	//print load_fiche_titre($langs->trans('ResourcesLinkedToElement'),'','');
	print '<br>';

	// Show list of resource links

	foreach ($object->available_resources as $modresources => $resources)
	{
		$resources=(array) $resources;  // To be sure $resources is an array
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
