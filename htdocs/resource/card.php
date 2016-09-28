<?php
/* Copyright (C) 2013-2014	Jean-François Ferry	<jfefe@aternatik.fr>
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
 *   	\file       resource/card.php
 *		\ingroup    resource
 *		\brief      Page to manage resource object
 */


// Change this following line to use the correct relative path (../, ../../, etc)
$res=0;
$res=@include("../main.inc.php");				// For root directory
if (! $res) $res=@include("../../main.inc.php");	// For "custom" directory
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/resourceschedule.class.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/html.formresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/resource.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';

// Load traductions files requiredby by page
$langs->load("resource");
$langs->load("companies");
$langs->load("other");
$langs->load("main");

// Get parameters
$id						= GETPOST('id','int');
$action					= GETPOST('action','alpha');
$cancel					= GETPOST('cancel','alpha');
$ref					= GETPOST('ref');
$description			= GETPOST('description');
$available				= GETPOST('available', 'int');
$confirm				= GETPOST('confirm');
$fk_code_type_resource	= GETPOST('fk_code_type_resource','alpha');
$country_id				= GETPOST('country_id', 'int');
$management_type		= GETPOST('management_type','alpha');
$duration_value			= GETPOST('duration_value', 'int');
$duration_unit			= GETPOST('duration_unit', 'alpha');
$starting_hour			= GETPOST('starting_hour','int');


//Check if duration exceeds year
if ($duration_value && $duration_unit)
{
    $max = dol_time_plus_duree(0, 1, 'y');
    $reached_limit = $max < dol_time_plus_duree(0, $duration_value, $duration_unit);
}

// Protection if external user
if ($user->socid > 0)
{
	accessforbidden();
}

if( ! $user->rights->resource->read)
{
	accessforbidden();
}

//Bound starting hour to 24h
if (empty($starting_hour)) $starting_hour = 0;
if ($starting_hour < 0) $starting_hour = 0;
if ($starting_hour > 23) $starting_hour = 23;

//Set to 0 if necessary
if (!empty($duration_value))
{
	if ($duration_unit == 'h' || ($duration_unit == 'm' && $duration_value >= 12))
	{
		$starting_hour = 0;
	}
}


$object = new Dolresource($db);

$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('resource', 'resource_card','globalcard'));
$parameters=array('resource_id'=>$id);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
$management_types = Dolresource::management_types_trans();

if (empty($reshook))
{
	/*******************************************************************
	* ACTIONS
	********************************************************************/
	if ($action == 'add' && $user->rights->resource->write)
	{
		if (! $cancel)
		{
			$error='';

			if (empty($ref))
			{
				setEventMessages($langs->trans("ErrorFieldRequired",$langs->transnoentities("Ref")), null, 'errors');
				$error++;
			}
			else if ($management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE && (empty($duration_value) || empty($duration_unit)))
			{
				setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("SectionDuration")), null, 'errors');
				$error++;
			}

			if (! $error)
			{
				$object->ref                    = $ref;
				$object->description            = $description;
				$object->fk_code_type_resource  = $fk_code_type_resource;
				$object->country_id             = $country_id;
				$object->duration_value         = $duration_value;
				$object->duration_unit          = $duration_unit;
				$object->available              = $available;
				$object->management_type        = $management_type;
				$object->starting_hour          = $starting_hour;

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
				if ($ret < 0) $error++;

				$result=$object->create($user);
				if ($result > 0)
				{
					// Creation OK
					setEventMessages($langs->trans('ResourceCreatedWithSuccess'), null, 'mesgs');
					Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				}
				else
				{
					// Creation KO
					setEventMessages($object->error, $object->errors, 'errors');
				}
			}
		}
		else
		{
			Header("Location: list.php");
			exit;
		}
	}

	if ($action == 'update' && ! $cancel && $user->rights->resource->write)
	{
		$error=0;

		if (empty($ref))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("Ref")), null, 'errors');
			$error++;
		}
		else if ($management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE && (empty($duration_value) || empty($duration_unit)))
		{
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("SectionDuration")), null, 'errors');
			$error++;
		}

		if (! $error)
		{
			$res = $object->fetch($id);
			if ( $res <= 0 )
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
			else if ($management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE)
			{
				$schedule = new ResourceSchedule($db);
				$schedules = $schedule->fetchAll($object->id);
				if ($schedules < 0)
				{
					setEventMessages($schedule->error, $schedule->errors, 'errors');
					$error++;
				}
				else if ($schedules > 0
				 && ($object->duration_value != $duration_value
				 || $object->duration_unit != $duration_unit
				 || $object->starting_hour != $starting_hour)
				)
				{
					setEventMessages($langs->trans("ErrorResourceSchedulePresent"), null, 'errors');
					$error++;
				}
			}
		}

		if (! $error)
		{
			$object->ref                    = $ref;
			$object->description            = $description;
			$object->fk_code_type_resource  = $fk_code_type_resource;
			$object->country_id             = $country_id;
			$object->duration_value         = $duration_value;
			$object->duration_unit          = $duration_unit;
			$object->available              = $available;
			$object->management_type        = $management_type;
			$object->starting_hour          = $starting_hour;

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost($extralabels, $object);
			if ($ret < 0) {
				$error ++;
			}

			$result=$object->update($user);
			if ($result > 0)
			{
				Header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
				exit;
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$error++;
			}
		}

		if ($error)
		{
			$action='edit';
		}
	}

	if ($action == 'confirm_delete_resource' && $user->rights->resource->delete && $confirm === 'yes')
	{
		$res = $object->fetch($id);
		if($res > 0)
		{
			$result = $object->delete($id);

			if ($result >= 0)
			{
				setEventMessages($langs->trans('RessourceSuccessfullyDeleted'), null, 'mesgs');
				Header('Location: '.DOL_URL_ROOT.'/resource/list.php');
				exit;
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
			}
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}


/***************************************************
* VIEW
*
* Put here all code to build page
****************************************************/
$title = $langs->trans($action == 'create' ? 'AddResource' : 'ResourceCard');
llxHeader('',$title,'');

$form = new Form($db);
$formresource = new FormResource($db);

if ($action == 'create' || $object->fetch($id) > 0)
{
	if ($action == 'create')
	{
        print load_fiche_titre($title,'','title_generic');
		dol_fiche_head('');
	}
	else
	{
		$head = resource_prepare_head($object);
		dol_fiche_head($head, 'resource', $title ,0,'resource');
	}

	if ($action == 'create' || $action == 'edit')
	{
		if ( ! $user->rights->resource->write )
			accessforbidden('',0);


		/*---------------------------------------
		 * Create/Edit object
		 */
	    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'?id='.$id.'">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="'.($action == "create"?"add":"update").'">';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td width="20%" class="titlefieldcreate fieldrequired">'.$langs->trans("ResourceFormLabel_ref").'</td>';
		print '<td><input size="12" name="ref" value="'.($ref ? $ref : $object->ref).'"></td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("ResourceType").'</td>';
		print '<td>';
		$ret = $formresource->select_types_resource($object->fk_code_type_resource,'fk_code_type_resource','',2);
		print '</td></tr>';

		// Description
		print '<tr><td class="tdtop">'.$langs->trans("ResourceFormLabel_description").'</td>';
		print '<td>';
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor = new DolEditor('description', $description, '', '200', 'dolibarr_notes', false);
		$doleditor->Create();
		print '</td></tr>';

		// Available
		print '<tr><td width="20%">'.$langs->trans("Available").'</td>';
		print '<td>';
		print $form->selectyesno('available',($available ? $available : $object->available),1);
		print '</td></tr>';

		// Origin country
		print '<tr><td>'.$langs->trans("CountryOrigin").'</td><td>';
		print $form->select_country($object->country_id,'country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td></tr>';

		// Management Types
		print '<tr><td width="20%">'.$langs->trans("ManagementType").'</td>';
		print '<td>';
		print $form->selectarray('management_type', $management_types, ($management_type ? $management_type : $object->management_type));
		print '</td></tr>';

		// Duration
		print '<tr id="duration"><td class="fieldrequired">'.$langs->trans("SectionDuration").'</td>';
		print '<td>';
		print '<input name="duration_value" size="6" maxlength="5" value="'.($duration_value ? $duration_value : $object->duration_value).'">&nbsp;';
		$unit = $duration_unit ? $duration_unit : $object->duration_unit;
		print '<input name="duration_unit" type="radio" value="h" '.($unit == 'h'?'checked':'').'>'.$langs->trans("Hour").'&nbsp;';
		print '<input name="duration_unit" type="radio" value="d" '.($unit == 'd'?'checked':'').'>'.$langs->trans("Day").'&nbsp;';
		print '<input name="duration_unit" type="radio" value="w" '.($unit == 'w'?'checked':'').'>'.$langs->trans("Week").'&nbsp;';
		print '<input name="duration_unit" type="radio" value="m" '.($unit == 'm'?'checked':'').'>'.$langs->trans("Month").'&nbsp;';
		print '</td></tr>';

		// Starting hour
		print '<tr id="starting_hour"><td class="fieldrequired">'.$langs->trans("StartingHour").'</td>';
		print '<td>';
		print '<input name="starting_hour" size="6" maxlength="2" value="' . $object->starting_hour . '">&nbsp;';
		print '</td></tr>';

		//Duration/Starting hour hiding
		print '<script type="text/javascript">
			function duration() {
				if ($("#management_type").val() == '.Dolresource::MANAGEMENT_TYPE_SCHEDULE.') {
					$("#duration").show();
					$("#starting_hour").show();
				} else {
					$("#duration").hide();
					$("#starting_hour").hide();
				}
			}
			duration();
			jQuery("#management_type").change(duration);
		</script>';

		// Other attributes
		$parameters=array('objectsrc' => $objectsrc, 'colspan' => ' colspan="3"');
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit');
		}

		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input type="submit" class="button" value="' . $langs->trans($action == "create"?"Create":"Modify") . '">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '">';
		print '</div>';
		print '</div>';

		print '</form>';
	}
	else
	{
		// Confirm deleting resource line
		if ($action == 'delete')
		{
			print $form->formconfirm("card.php?&id=".$id,$langs->trans("DeleteResource"),$langs->trans("ConfirmDeleteResource"),"confirm_delete_resource",'','',1);
		}


		/*---------------------------------------
		 * View object
		 */
		print '<table width="100%" class="border">';

		print '<tr><td class="titlefield">'.$langs->trans("ResourceFormLabel_ref").'</td><td>';
		$linkback = $objet->ref.' <a href="list.php">'.$langs->trans("BackToList").'</a>';
		print $form->showrefnav($object, 'id', $linkback,1,"rowid");
		print '</td>';
		print '</tr>';

		// Resource type
		print '<tr>';
		print '<td>' . $langs->trans("ResourceType") . '</td>';
		print '<td>';
		print $object->type_label;
		print '</td>';
		print '</tr>';

		// Description
		print '<tr>';
		print '<td>' . $langs->trans("ResourceFormLabel_description") . '</td>';
		print '<td>';
		print $object->description;
		print '</td>';

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields);
		}

		print '</tr>';

		// Available
		print '<tr>';
		print '<td>' . $langs->trans("Available") . '</td>';
		print '<td>';
		print yn($object->available);
		print '</td>';
		print '</tr>';

		// Origin country code
		print '<tr>';
		print '<td>'.$langs->trans("CountryOrigin").'</td>';
		print '<td>';
		print getCountry($object->country_id,0,$db);
		print '</td>';
		print '</tr>';

		// Management Type
		print '<tr>';
		print '<td>' . $langs->trans("ManagementType") . '</td>';
		print '<td>';
		print $management_types[$object->management_type];
		print '</td>';
		print '</tr>';

		if ($object->management_type == Dolresource::MANAGEMENT_TYPE_SCHEDULE) {
			//Duration
			print '<tr><td style="width:35%">'.$langs->trans("SectionDuration").'</td>';
			print '<td>'.$object->duration_value.'&nbsp;';
			$da=array("h"=>$langs->trans("Hour"),"d"=>$langs->trans("Day"),"w"=>$langs->trans("Week"),"m"=>$langs->trans("Month"),"y"=>$langs->trans("Year"));
			print $langs->trans($da[$object->duration_unit].($object->duration_value > 1?'s':''));
			print '</td></tr>';

			//Starting hour
			print '<tr><td style="width:30%">'.$langs->trans("StartingHour").'</td><td>';
			print $object->starting_hour;
			print '</td></tr>';
		}

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields);
		}

		print '</table>';
	}

	print '</div>';

	/*
	 * Boutons actions
	 */
	print '<div class="tabsAction">';
	$parameters = array();
	$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been
	// modified by hook
	if (empty($reshook))
	{
		if ($action != "create" && $action != "edit" )
		{
			// Edit resource
			if($user->rights->resource->write)
			{
				print '<div class="inline-block divButAction">';
				print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;action=edit" class="butAction">'.$langs->trans('Modify').'</a>';
				print '</div>';
			}
		}
		if ($action != "delete" && $action != "create" && $action != "edit")
		{
		    // Delete resource
		    if($user->rights->resource->delete)
		    {
		        print '<div class="inline-block divButAction">';
		        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$id.'&amp;action=delete" class="butActionDelete">'.$langs->trans('Delete').'</a>';
		        print '</div>';
		    }
		}
	}
	print '</div>';
}
else {
	dol_print_error();
}



// End of page
llxFooter();
$db->close();
