<?php
/* Copyright (C) 2013   Jean-François Ferry     <jfefe@aternatik.fr>
/* Copyright (C) 2016   Ion Agorria             <ion@agorria.com>
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
 *      \file       resource/linked.php
 *      \ingroup    resource
 *      \brief      Page to show and manage linked resources to an element
 */

$res=0;
$res=@include("../main.inc.php");                               // For root directory
if (! $res) $res=@include("../../main.inc.php");        // For "custom" directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';

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

$object = new ResourceLink($db);

$hookmanager->initHooks(array('resource', 'element_resource'));
$object->available_resources = array('dolresource');
$dependency_modes = $object->dependency_modes_translated();
$status_trans = ResourceStatus::translated();

// Get parameters
$id                     = GETPOST('id','int');                          // resource id
$element_id             = GETPOST('element_id','int');                  // element_id
$element_ref            = GETPOST('ref','alpha');                       // element ref
$element_type           = GETPOST('element_type','alpha');              // element_type
$action                 = GETPOST('action','alpha');
$mode                   = GETPOST('mode','alpha');
$lineid                 = GETPOST('lineid','int');
$resource_type          = GETPOST('resource_type','alpha');
$resource_id            = GETPOST($resource_type.'resource_id','int');
$mandatory              = GETPOST('mandatory','int');
$dependency             = GETPOST('dependency','int');
$cancel                 = GETPOST('cancel','alpha');
$confirm                = GETPOST('confirm','alpha');
$selected               = GETPOST('selected', 'int') != "-1" ? intval(GETPOST('selected', 'int')) : "";
$parent                 = intval(GETPOST('parent','int'));
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
	$error = 0;
	$res = 0;
	$objelement = null;
	$objresource = null;

	//Checks
	if ($resource_id <= 0)
	{
		$error++;
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Resource")), null, 'errors');
	}
	if (!$element_id || !$element_type)
	{
		$error++;
		setEventMessages($langs->trans('ErrorElementUnknown', $element_id, $element_type), null, 'errors');
	}
	if (!$error)
	{
		if ($parent == 0) //$parent is 0 when there is no selectable parent
		{
			$error++;
			setEventMessages($langs->trans('AllResourcesExcluded'), null, 'errors');
		}
		else //Anyways check if there is exclusion, never trust clients
		{
			$parent = $parent == -1 ? 0 : $parent; //$parent is -1 when root is selected
			$filtered_tree = $object->getFullTree($element_id, $element_type, true);
			$root_excluded = $object->filterTree($filtered_tree, $resource_type, $resource_id);
			//Check if root was excluded and parent is root or if parent is excluded
			if (($root_excluded && $parent == 0) || (!isset($filtered_tree[$parent]) && $parent != 0)) {
				$error++;
				setEventMessages($langs->trans('ResourceParentExcluded'), null, 'errors');
			}
		}
	}

	//Check if resource and element is correct
	if (!$error)
	{
		$objresource = fetchObjectByElement($resource_id, $resource_type);
		if (!is_object($objresource) || $objresource->id != $resource_id)
		{
			setEventMessages($langs->trans('ErrorResourceUnknown', $resource_id, $resource_type), null, 'errors');
			$error++;
		}


		if (!$error)
		{
			$objelement = fetchObjectByElement($element_id, $element_type);
			if (!is_object($objelement) || $objelement->id != $element_id)
			{
				setEventMessages($langs->trans('ErrorElementUnknown', $element_id, $element_type), null, 'errors');
				$error++;
			}
		}
	}

	//Special element type checks
	if (!$error)
	{
		if ($element_type == "action")
		{
			if (empty($objelement->datef))
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DateActionEnd")), null, 'errors');
				$error++;
			}
		}
		else if ($element_type == "service")
		{
			if ($objresource->management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE)
			{
				if ($objelement->duration_value == '' || empty($objelement->duration_unit))
				{
					setEventMessages($langs->trans('ErrorResourceServiceMissingDuration'), null, 'errors');
					$error++;
				}
				else
				{
					$service_duration = dol_time_plus_duree(0, $objelement->duration_value, $objelement->duration_unit);
					$resource_duration = dol_time_plus_duree(0, $objresource->duration_value, $objresource->duration_unit);
					$result = $resource_duration == 0 ? 0 : ($service_duration / $resource_duration);
					if ($result <= 0)
					{
						setEventMessages($langs->trans('ErrorResourceServiceDurationShort'), null, 'errors');
						$error++;
					}
					else
					{
						$diff = ceil($result)-floor($result);
						if ($diff != 0)
						{
							setEventMessages($langs->trans('ErrorResourceServiceDurationNotProportional'), null, 'errors');
							$error++;
						}
					}
				}
			}
		}
	}

	// Occupy resource if linked to event
	if (!$error && $element_type == "action")
	{
		$result = $this->setStatus($user, $objelement->datep, $objelement->datef, ResourceStatus::$AVAILABLE, ResourceStatus::OCCUPIED, $element_id, $element_type, false, ResourceLog::RESOURCE_OCCUPY);
		if ($result < 0)
		{
			setEventMessages($objresource->error, $objresource->errors, 'errors');
			$error++;
		}
	}

	//Link
	if (!$error)
	{
		$object->fk_parent = $parent;
		$object->resource_id = $resource_id;
		$object->resource_type = $resource_type;
		$object->element_id = $element_id;
		$object->element_type = $element_type;
		$object->mandatory = $mandatory;
		$object->dependency = $dependency;
		$res = $object->create($user);
		if ($res < 0) {
			setEventMessages($object->error, $object->errors, 'errors');
			$error++;
		}
	}

	if (! $error)
	{
		setEventMessages($langs->trans('ResourceLinkedWithSuccess'),null,'mesgs');
		header("Location: ".$_SERVER['PHP_SELF'].'?element_type='.$element_type.'&element_id='.$element_id);
		exit;
	}
	else
	{
		$action='';
	}
}

// Update ressource
if ($action == 'update_linked_resource' && $user->rights->resource->write && ! $cancel)
{
	$res = $object->fetch($lineid);

	//Update
	if ($res)
	{
		$object->mandatory = $mandatory;
		$object->dependency = $dependency;

		$res = $object->update($user);
	}

	if ($res)
	{
		setEventMessages($langs->trans('RessourceLineSuccessfullyUpdated'),null,'mesgs');
		header("Location: ".$_SERVER['PHP_SELF']."?element_type=".$element_type."&element_id=".$element_id);
		exit;
	}
	else
	{
		setEventMessages($object->error,null,'errors');
	}
}

// Delete a resource linked to an element
if ($action == 'confirm_delete_linked_resource' && $user->rights->resource->delete && $confirm === 'yes')
{
	$error = 0;
	$res = $object->fetch($lineid);
	if(!$res > 0)
	{
		setEventMessages($object->error, $object->errors,'errors');
		$error++;
	}

	// Free resource if linked to event
	if (!$error && $element_type == "action")
	{
		$resource_id = $object->resource_id;
		$resource_type = $object->resource_type;

		$objresource = fetchObjectByElement($resource_id, $resource_type);
		if (!is_object($objresource) || $objresource->id != $resource_id)
		{
			setEventMessages($langs->trans('ErrorResourceUnknown', $resource_id, $resource_type), null, 'errors');
			$error++;
		}


		if (!$error)
		{
			$objelement = fetchObjectByElement($element_id, $element_type);
			if (!is_object($objelement) || $objelement->id != $element_id)
			{
				setEventMessages($langs->trans('ErrorElementUnknown', $element_id, $element_type), null, 'errors');
				$error++;
			}
		}

		if (!$error)
		{
			$result = $objresource->freeResource($user, $objelement->datep, $objelement->datef, null, ResourceStatus::OCCUPIED, $element_id, $element_type);
			if ($result < 0)
			{
				setEventMessages($objresource->error, $objresource->errors, 'errors');
				$error++;
			}
		}
	}

	if (!$error)
	{
		$res = $object->delete();
		if($res < 0)
		{
			setEventMessages($object->error, $object->errors,'errors');
			$error++;
		}
	}

	if (!$error)
	{
		setEventMessages($langs->trans('RessourceLineSuccessfullyDeleted'),null,'mesgs');
		header("Location: ".$_SERVER['PHP_SELF']."?element_type=".$element_type."&element_id=".$element_id);
		exit;
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
$arrayofjs=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.js', '/includes/jquery/plugins/jquerytreeview/lib/jquery.cookie.js');
$arrayofcss=array('/includes/jquery/plugins/jquerytreeview/jquery.treeview.css');

llxHeader('',$pagetitle,'','',0,0,$arrayofjs,$arrayofcss);


// Load available resource, declared by modules
$ret = count($object->available_resources);
if(!$ret) {
	dol_print_error($db,$object->error);
	exit;
}

if ($id) //Show resource referrers
{

	$resource = new Dolresource($db);
	$res = $resource->fetch($id);
	if ($res < 0)
	{
		dol_print_error($db, $resource->error, $resource->errors);
	}
	else
	{
		$head = resource_prepare_head($resource);
		dol_fiche_head($head, 'linked', $langs->trans("ResourceSingular"),0,'resource@resource');

		/*
		 * View object
		 */
		print '<table width="100%" class="border">';
		print '<tr><td style="width:35%">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
		$linkback = '<a href="list.php">'.$langs->trans("BackToList").'</a>';
		print $form->showrefnav($resource, 'id', $linkback,1,"rowid");
		print '</td></tr>';
		print '</table>';
		print '</div>';

		//List
		print '<table class="noborder" width="100%">'."\n";
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans('Ref'));
		print_liste_field_titre($langs->trans('Resources'),"","","","",'width="60" align="center"',"","");
		print "</tr>\n";

		$var=true;
		$elements = $object->getElementLinked($resource->id, $resource->element);
		if (is_array($elements))
		{
			foreach ($elements as $e_id => $e_type)
			{
				$element = fetchObjectByElement($e_id, $e_type);
				if (!is_object($element) || $element->id != $e_id) continue;

				$var=!$var;
				print '<tr '.$bc[$var].'>';

				//Ref
				print '<td>';
				print $element->getNomUrl(1);
				print '</td>';

				//Buttons
				print '<td align="center">';
				print '<a href="./linked.php?element_type='.$e_type.'&element_id='.$e_id.'">';
				print img_object('', 'resource', 'class="classfortooltip"');
				print '</a>';
				print '</td>';

				print '</tr>';
			}
		}
		else
		{
			dol_print_error($db, $object->error." -> ".$elements, $object->errors);
		}

		print '</table>';
	}
}
else if ($element_id && $element_type) //Show linked resources to this element
{
	// Confirmation suppression resource line
	if ($action == 'delete_resource')
	{
		print $form->formconfirm("linked.php?element_type=".$element_type."&element_id=".$element_id."&lineid=".$lineid,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResourceElement"),"confirm_delete_linked_resource",'','',1);
	}

	$element = null;
	if ($element_id && $element_type)
	{
		$element = fetchObjectByElement($element_id, $element_type, $element_ref);
		//Fill element id if ref was used
		if (is_object($element) && !$element_id) $element_id = $element->id;
	}

	// Specific to agenda module
	if (is_object($element) && $element_type == 'action')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

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

		dol_banner_tab($act, 'element_id', $linkback, ($user->societe_id?0:1), 'id', 'ref', '', '&element_type='.$element_type, 0, '', '');

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

	// Specific to thirdparty module
	if (is_object($element) && $element_type == 'societe')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
		$head = societe_prepare_head($element);

		dol_fiche_head($head, 'resources', $langs->trans("ThirdParty"),0,'company');

		dol_banner_tab($element, 'element_id', '', ($user->societe_id?0:1), 'rowid', 'nom', '', '&element_type='.$element_type);

		print '<div class="fichecenter">';

		print '<div class="underbanner clearboth"></div>';
		print '<table class="border centpercent">';

		// Alias name (commercial, trademark or alias name)
		print '<tr><td class="titelfield">'.$langs->trans('AliasNames').'</td><td colspan="3">';
		print $element->name_alias;
		print "</td></tr>";

		// Prefix
		if (! empty($conf->global->SOCIETE_USEPREFIX))  // Old not used prefix field
		{
			print '<tr><td>'.$langs->trans('Prefix').'</td><td colspan="3">'.$element->prefix_comm.'</td></tr>';
		}

		if ($element->client)
		{
			print '<tr><td>';
			print $langs->trans('CustomerCode').'</td><td colspan="3">';
			print $element->code_client;
			if ($element->check_codeclient() <> 0) print ' <font class="error">('.$langs->trans("WrongCustomerCode").')</font>';
			print '</td></tr>';
		}

		if ($element->fournisseur)
		{
			print '<tr><td>';
			print $langs->trans('SupplierCode').'</td><td colspan="3">';
			print $element->code_fournisseur;
			if ($element->check_codefournisseur() <> 0) print ' <font class="error">('.$langs->trans("WrongSupplierCode").')</font>';
			print '</td></tr>';
		}

		print '</table>';
		print '</div>';

		dol_fiche_end();

	}

	// Specific to fichinter module
	if (is_object($element) && $element_type == 'fichinter')
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';

       	$fichinter->fetch_thirdparty();

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

	// Specific to product/service module
	if (is_object($element) && ($element_type == 'product' || $element_type == 'service'))
	{
		require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
		$head = product_prepare_head($element);
		$titre=$langs->trans("CardProduct".$element->type);
		$picto=($element->type==Product::TYPE_SERVICE?'service':'product');

		dol_fiche_head($head, 'resources', $titre, 0, $picto);

		//dol_banner_tab($element, 'element_id', '', ($user->societe_id?0:1), 'rowid', 'ref', '', "&element_type=".$element_type);
		dol_banner_tab($element, '', '', 0); //TODO fix nav

		dol_fiche_end();
	}


	// hook for other elements linked
	$parameters=array('element_type'=>$element_type, 'element_id'=>$element_id, 'element_ref'=>$element_ref);
	$reshook=$hookmanager->executeHooks('printElementTab',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

	$res_tree = $object->getFullTree($element_id, $element_type, true);
	$roots = $object->getAvailableRoots($res_tree, 1);
	if ((count($roots['available']) + count($roots['notavailable'])) > 0 && $roots['need'] > 0) {
		print '<div class="warning">'.$langs->trans('WarningResourcesNotAvailable').'</div>';
	}

	foreach ($object->available_resources as $modresources => $resources)
	{
		$resources=(array) $resources;	// To be sure $resources is an array
		foreach($resources as $resource_obj)
		{
			$element_prop = getElementProperties($resource_obj);

			$path = '';
			if(strpos($resource_obj,'@'))
				$path .= '/'.$element_prop['module'];

			$filtered_tree = $res_tree; //Copy the tree
			$root_excluded = $object->filterTree($filtered_tree, $resource_obj, $selected);

			//Show title if more type of resources
			if (count($object->available_resources) > 1 && empty($mode)) {
				print '<br>'.load_fiche_titre($langs->trans(ucfirst($element_prop['element']).'Singular'));
			}

			if ($resource_type == "resource") $resource_type = "dolresource";

			if (empty($mode) || ($mode != 'edit' && $resource_obj == $resource_type))
			{
				// If we have a specific template we use it, else use the default one
				$template = dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_add.tpl.php');
				$template = file_exists($template)?$template:DOL_DOCUMENT_ROOT.'/core/tpl/resource_add.tpl.php';
				include $template;
			}

			if (empty($mode) || ($resource_obj == $resource_type))
			{
				// If we have a specific template we use it, else use the default one
				$template = dol_buildpath($path.'/core/tpl/resource_'.$element_prop['element'].'_view.tpl.php');
				$template = file_exists($template)?$template:DOL_DOCUMENT_ROOT.'/core/tpl/resource_view.tpl.php';
				include $template;
			}
		}
	}
}
else
{
	dol_print_error($db);
}

llxFooter();

$db->close();
