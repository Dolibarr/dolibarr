<?php
/* Copyright (C) 2013-2018	Jean-François Ferry	<hello+jf@librethic.io>
 * Copyright (C) 2016		Gilles Poirier 		<glgpoirier@gmail.com>
 * Copyright (C) 2019		Josep Lluís Amador	<joseplluis@lliuretic.cat>
 * Copyright (C) 2021		Frédéric France		<frederic.france@netlogic.fr>
 * Copyright (C) 2023		William Mead			<william.mead@manchenumerique.fr>
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
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *      \file       resource/element_resource.php
 *      \ingroup    resource
 *      \brief      Page to show and manage linked resources to an element
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/resource/class/dolresource.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}
if (isModEnabled("product") || isModEnabled("service")) {
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('resource', 'other', 'interventions'));

/*
$sortorder                      = GETPOST('sortorder','alpha');
$sortfield                      = GETPOST('sortfield','alpha');
$page                           = GETPOST('page','int');
*/

$object = new DolResource($db);

$hookmanager->initHooks(array('element_resource'));
$object->available_resources = array('resource');

// Get parameters
$id                     = GETPOSTINT('id'); // resource id
$element_id             = GETPOSTINT('element_id'); // element_id
$element_ref            = GETPOST('ref', 'alpha'); // element ref
$element                = GETPOST('element', 'alpha'); // element_type
$action                 = GETPOST('action', 'alpha');
$mode                   = GETPOST('mode', 'alpha');
$lineid                 = GETPOSTINT('lineid');
$resource_id            = GETPOSTINT('fk_resource');
$resource_type          = GETPOST('resource_type', 'alpha');
$busy                   = GETPOSTINT('busy');
$mandatory              = GETPOSTINT('mandatory');
$cancel                 = GETPOST('cancel', 'alpha');
$confirm                = GETPOST('confirm', 'alpha');
$socid                  = GETPOSTINT('socid');

if (empty($mandatory)) {
	$mandatory = 0;
}
if (empty($busy)) {
	$busy = 0;
}

if ($socid > 0) { // Special for thirdparty
	$element_id = $socid;
	$element = 'societe';
}

if (!$user->hasRight('resource', 'read')) {
	accessforbidden();
}

// Permission is not permission on resources. We just make link here on objects.
if ($element == 'action') {
	$result = restrictedArea($user, 'agenda', $element_id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id');
}
if ($element == 'fichinter') {
	$result = restrictedArea($user, 'ficheinter', $element_id, 'fichinter');
}
if ($element == 'product' || $element == 'service') {	// When RESOURCE_ON_PRODUCTS or RESOURCE_ON_SERVICES is set
	$tmpobject = new Product($db);
	$tmpobject->fetch($element_id);
	$fieldtype = $tmpobject->type;
	$result = restrictedArea($user, 'produit|service', $element_id, 'product&product', '', '', $fieldtype);
}


/*
 * Actions
 */

$parameters = array('resource_id' => $resource_id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$error = 0;

	if ($action == 'add_element_resource' && !$cancel) {
		$res = 0;
		if (!($resource_id > 0)) {
			$error++;
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Resource")), null, 'errors');
			$action = '';
		} else {
			$objstat = fetchObjectByElement($element_id, $element, $element_ref);
			$objstat->element = $element; // For externals module, we need to keep @xx

			// TODO : add this check at update_linked_resource and when modifying event start or end date
			// check if an event resource is already in use
			if (getDolGlobalString('RESOURCE_USED_IN_EVENT_CHECK') && $objstat->element == 'action' && $resource_type == 'resource' && intval($busy) == 1) {
				$eventDateStart = $objstat->datep;
				$eventDateEnd   = $objstat->datef;
				$isFullDayEvent = $objstat->fulldayevent;
				if (empty($eventDateEnd)) {
					if ($isFullDayEvent) {
						$eventDateStartArr = dol_getdate($eventDateStart);
						$eventDateStart = dol_mktime(0, 0, 0, $eventDateStartArr['mon'], $eventDateStartArr['mday'], $eventDateStartArr['year']);
						$eventDateEnd   = dol_mktime(23, 59, 59, $eventDateStartArr['mon'], $eventDateStartArr['mday'], $eventDateStartArr['year']);
					}
				}

				$sql  = "SELECT er.rowid, r.ref as r_ref, ac.id as ac_id, ac.label as ac_label";
				$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as er";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."resource as r ON r.rowid = er.resource_id AND er.resource_type = '".$db->escape($resource_type)."'";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm as ac ON ac.id = er.element_id AND er.element_type = '".$db->escape($objstat->element)."'";
				$sql .= " WHERE er.resource_id = ".((int) $resource_id);
				$sql .= " AND er.busy = 1";
				$sql .= " AND (";

				// event date start between ac.datep and ac.datep2 (if datep2 is null we consider there is no end)
				$sql .= " (ac.datep <= '".$db->idate($eventDateStart)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($eventDateStart)."'))";
				// event date end between ac.datep and ac.datep2
				if (!empty($eventDateEnd)) {
					$sql .= " OR (ac.datep <= '".$db->idate($eventDateEnd)."' AND (ac.datep2 >= '".$db->idate($eventDateEnd)."'))";
				}
				// event date start before ac.datep and event date end after ac.datep2
				$sql .= " OR (";
				$sql .= "ac.datep >= '".$db->idate($eventDateStart)."'";
				if (!empty($eventDateEnd)) {
					$sql .= " AND (ac.datep2 IS NOT NULL AND ac.datep2 <= '".$db->idate($eventDateEnd)."')";
				}
				$sql .= ")";

				$sql .= ")";
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
					$objstat->error    = $db->lasterror();
					$objstat->errors[] = $objstat->error;
				} else {
					if ($db->num_rows($resql) > 0) {
						// Resource already in use
						$error++;
						$objstat->error = $langs->trans('ErrorResourcesAlreadyInUse').' : ';
						while ($obj = $db->fetch_object($resql)) {
							$objstat->error .= '<br> - '.$langs->trans('ErrorResourceUseInEvent', $obj->r_ref, $obj->ac_label.' ['.$obj->ac_id.']');
						}
						$objstat->errors[] = $objstat->error;
					}
					$db->free($resql);
				}
			}

			if (!$error) {
				$res = $objstat->add_element_resource($resource_id, $resource_type, $busy, $mandatory);
			}
		}

		if (!$error && $res > 0) {
			setEventMessages($langs->trans('ResourceLinkedWithSuccess'), null, 'mesgs');
			header("Location: ".$_SERVER['PHP_SELF'].'?element='.$element.'&element_id='.$objstat->id);
			exit;
		} elseif ($objstat) {
			setEventMessages($objstat->error, $objstat->errors, 'errors');
		}
	}

	// Update resource
	if ($action == 'update_linked_resource' && $user->hasRight('resource', 'write') && !GETPOST('cancel', 'alpha')) {
		$res = $object->fetchElementResource($lineid);
		if ($res) {
			$object->busy = $busy;
			$object->mandatory = $mandatory;

			if (getDolGlobalString('RESOURCE_USED_IN_EVENT_CHECK') && $object->element_type == 'action' && $object->resource_type == 'resource' && intval($object->busy) == 1) {
				$eventDateStart = $object->objelement->datep;
				$eventDateEnd   = $object->objelement->datef;
				$isFullDayEvent = $objstat->fulldayevent;
				if (empty($eventDateEnd)) {
					if ($isFullDayEvent) {
						$eventDateStartArr = dol_getdate($eventDateStart);
						$eventDateStart = dol_mktime(0, 0, 0, $eventDateStartArr['mon'], $eventDateStartArr['mday'], $eventDateStartArr['year']);
						$eventDateEnd   = dol_mktime(23, 59, 59, $eventDateStartArr['mon'], $eventDateStartArr['mday'], $eventDateStartArr['year']);
					}
				}

				$sql  = "SELECT er.rowid, r.ref as r_ref, ac.id as ac_id, ac.label as ac_label";
				$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as er";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."resource as r ON r.rowid = er.resource_id AND er.resource_type = '".$db->escape($object->resource_type)."'";
				$sql .= " INNER JOIN ".MAIN_DB_PREFIX."actioncomm as ac ON ac.id = er.element_id AND er.element_type = '".$db->escape($object->element_type)."'";
				$sql .= " WHERE er.resource_id = ".((int) $object->resource_id);
				$sql .= " AND ac.id <> ".((int) $object->element_id);
				$sql .= " AND er.busy = 1";
				$sql .= " AND (";

				// event date start between ac.datep and ac.datep2 (if datep2 is null we consider there is no end)
				$sql .= " (ac.datep <= '".$db->idate($eventDateStart)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($eventDateStart)."'))";
				// event date end between ac.datep and ac.datep2
				if (!empty($eventDateEnd)) {
					$sql .= " OR (ac.datep <= '".$db->idate($eventDateEnd)."' AND (ac.datep2 IS NULL OR ac.datep2 >= '".$db->idate($eventDateEnd)."'))";
				}
				// event date start before ac.datep and event date end after ac.datep2
				$sql .= " OR (";
				$sql .= "ac.datep >= '".$db->idate($eventDateStart)."'";
				if (!empty($eventDateEnd)) {
					$sql .= " AND (ac.datep2 IS NOT NULL AND ac.datep2 <= '".$db->idate($eventDateEnd)."')";
				}
				$sql .= ")";

				$sql .= ")";
				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
					$object->error    = $db->lasterror();
					$object->errors[] = $object->error;
				} else {
					if ($db->num_rows($resql) > 0) {
						// Resource already in use
						$error++;
						$object->error = $langs->trans('ErrorResourcesAlreadyInUse').' : ';
						while ($obj = $db->fetch_object($resql)) {
							$object->error .= '<br> - '.$langs->trans('ErrorResourceUseInEvent', $obj->r_ref, $obj->ac_label.' ['.$obj->ac_id.']');
						}
						$object->errors[] = $objstat->error;
					}
					$db->free($resql);
				}
			}

			if (!$error) {
				$result = $object->updateElementResource($user);
				if ($result < 0) {
					$error++;
				}
			}

			if ($error) {
				setEventMessages($object->error, $object->errors, 'errors');
			} else {
				setEventMessages($langs->trans('RessourceLineSuccessfullyUpdated'), null, 'mesgs');
				header("Location: ".$_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id);
				exit;
			}
		}
	}

	// Delete a resource linked to an element
	if ($action == 'confirm_delete_linked_resource' && $user->hasRight('resource', 'delete') && $confirm === 'yes') {
		$result = $object->delete_resource($lineid, $element);

		if ($result >= 0) {
			setEventMessages($langs->trans('RessourceLineSuccessfullyDeleted'), null, 'mesgs');
			header("Location: ".$_SERVER['PHP_SELF']."?element=".$element."&element_id=".$element_id);
			exit;
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

$parameters = array('resource_id'=>$resource_id);
$reshook = $hookmanager->executeHooks('getElementResources', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}



/*
 * View
 */

$form = new Form($db);

$pagetitle = $langs->trans('ResourceElementPage');
$help_url = '';
llxHeader('', $pagetitle, $help_url, '', 0, 0, '', '', '', 'mod-resource page-element_resource');

$now = dol_now();
$delay_warning = $conf->global->MAIN_DELAY_ACTIONS_TODO * 24 * 60 * 60;

// Load available resource, declared by modules
$ret = count($object->available_resources);
if ($ret == -1) {
	dol_print_error($db, $object->error);
	exit;
}
if (!$ret) {
	print '<div class="warning">'.$langs->trans('NoResourceInDatabase').'</div>';
} else {
	// Confirmation suppression resource line
	if ($action == 'delete_resource') {
		print $form->formconfirm("element_resource.php?element=".$element."&element_id=".$element_id."&id=".$id."&lineid=".$lineid, $langs->trans("DeleteResource"), $langs->trans("ConfirmDeleteResourceElement"), "confirm_delete_linked_resource", '', '', 1);
	}


	// Specific to agenda module
	if (($element_id || $element_ref) && $element == 'action') {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

		// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
		$hookmanager->initHooks(array('actioncard', 'globalcard'));

		$act = fetchObjectByElement($element_id, $element, $element_ref);
		if (is_object($act)) {
			$head = actions_prepare_head($act);

			print dol_get_fiche_head($head, 'resources', $langs->trans("Action"), -1, 'action');

			$linkback = '<a href="'.DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&restore_lastsearch_values=1">';
			$linkback .= img_picto($langs->trans("BackToList"), 'object_calendarlist', 'class="pictoactionview pictofixedwidth"');
			$linkback .= '<span class="hideonsmartphone">'.$langs->trans("BackToList").'</span>';
			$linkback .= '</a>';

			// Link to other agenda views
			$out = '';
			$out .= '</li><li class="noborder litext">';
			$out .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_month&year='.dol_print_date($act->datep, '%Y').'&month='.dol_print_date($act->datep, '%m').'&day='.dol_print_date($act->datep, '%d').'">';
			$out .= img_picto($langs->trans("ViewCal"), 'object_calendar', 'class="pictofixedwidth pictoactionview"');
			$out .= '<span class="hideonsmartphone">'.$langs->trans("ViewCal").'</span>';
			$out .= '</a>';
			$out .= '</li><li class="noborder litext">';
			$out .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&year='.dol_print_date($act->datep, '%Y').'&month='.dol_print_date($act->datep, '%m').'&day='.dol_print_date($act->datep, '%d').'">';
			$out .= img_picto($langs->trans("ViewWeek"), 'object_calendarweek', 'class="pictofixedwidth pictoactionview"');
			$out .= '<span class="hideonsmartphone">'.$langs->trans("ViewWeek").'</span>';
			$out .= '</a>';
			$out .= '</li><li class="noborder litext">';
			$out .= '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?mode=show_day&year='.dol_print_date($act->datep, '%Y').'&month='.dol_print_date($act->datep, '%m').'&day='.dol_print_date($act->datep, '%d').'">';
			$out .= img_picto($langs->trans("ViewDay"), 'object_calendarday', 'class="pictofixedwidth pictoactionview"');
			$out .= '<span class="hideonsmartphone">'.$langs->trans("ViewDay").'</span>';
			$out .= '</a>';
			$out .= '</li><li class="noborder litext">';
			$out .= '<a href="'.DOL_URL_ROOT.'/comm/action/peruser.php?mode=show_peruser&year='.dol_print_date($act->datep, '%Y').'&month='.dol_print_date($act->datep, '%m').'&day='.dol_print_date($act->datep, '%d').'">';
			$out .= img_picto($langs->trans("ViewPerUser"), 'object_calendarperuser', 'class="pictofixedwidth pictoactionview"');
			$out .= '<span class="hideonsmartphone">'.$langs->trans("ViewPerUser").'</span>';
			$out .= '</a>';

			// Add more views from hooks
			$parameters = array();
			$reshook = $hookmanager->executeHooks('addCalendarView', $parameters, $object, $action);
			if (empty($reshook)) {
				$out .= $hookmanager->resPrint;
			} elseif ($reshook > 1) {
				$out = $hookmanager->resPrint;
			}

			$linkback .= $out;

			$morehtmlref = '<div class="refidno">';
			// Thirdparty
			//$morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' : ' . $object->thirdparty->getNomUrl(1);
			// Project
			$savobject = $object;
			$object = $act;
			if (isModEnabled('project')) {
				$langs->load("projects");
				//$morehtmlref .= '<br>';
				if (0) {
					$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
					if ($action != 'classify') {
						$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
					}
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
				} else {
					if (!empty($object->fk_project)) {
						$proj = new Project($db);
						$proj->fetch($object->fk_project);
						$morehtmlref .= $proj->getNomUrl(1);
						if ($proj->title) {
							$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
						}
					}
				}
			}
			$object = $savobject;
			$morehtmlref .= '</div>';

			dol_banner_tab($act, 'element_id', $linkback, ($user->socid ? 0 : 1), 'id', 'ref', $morehtmlref, '&element='.$element, 0, '', '');

			print '<div class="fichecenter">';

			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Type
			if (getDolGlobalString('AGENDA_USE_EVENT_TYPE')) {
				print '<tr><td class="titlefield">'.$langs->trans("Type").'</td><td>';
				print $act->getTypePicto();
				print $langs->trans("Action".$act->type_code);
				print '</td></tr>';
			}

			// Full day event
			print '<tr><td class="titlefield">'.$langs->trans("EventOnFullDay").'</td><td colspan="3">'.yn($act->fulldayevent ? 1 : 0, 3).'</td></tr>';

			// Date start
			print '<tr><td>'.$langs->trans("DateActionStart").'</td><td colspan="3">';
			if (empty($act->fulldayevent)) {
				print dol_print_date($act->datep, 'dayhour', 'tzuser');
			} else {
				$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
				print dol_print_date($act->datep, 'day', ($tzforfullday ? $tzforfullday : 'tzuser'));
			}
			if ($act->percentage == 0 && $act->datep && $act->datep < ($now - $delay_warning)) {
				print img_warning($langs->trans("Late"));
			}
			print '</td>';
			print '</tr>';

			// Date end
			print '<tr><td>'.$langs->trans("DateActionEnd").'</td><td colspan="3">';
			if (empty($act->fulldayevent)) {
				print dol_print_date($act->datef, 'dayhour', 'tzuser');
			} else {
				$tzforfullday = getDolGlobalString('MAIN_STORE_FULL_EVENT_IN_GMT');
				print dol_print_date($act->datef, 'day', ($tzforfullday ? $tzforfullday : 'tzuser'));
			}
			if ($act->percentage > 0 && $act->percentage < 100 && $act->datef && $act->datef < ($now - $delay_warning)) {
				print img_warning($langs->trans("Late"));
			}
			print '</td></tr>';

			// Location
			if (!getDolGlobalString('AGENDA_DISABLE_LOCATION')) {
				print '<tr><td>'.$langs->trans("Location").'</td><td colspan="3">'.$act->location.'</td></tr>';
			}

			// Assigned to
			print '<tr><td class="nowrap">'.$langs->trans("ActionAffectedTo").'</td><td colspan="3">';
			$listofuserid = array();
			if (empty($donotclearsession)) {
				if ($act->userownerid > 0) {
					$listofuserid[$act->userownerid] = array('id'=>$act->userownerid, 'transparency'=>$act->transparency); // Owner first
				}
				if (!empty($act->userassigned)) {	// Now concat assigned users
					// Restore array with key with same value than param 'id'
					$tmplist1 = $act->userassigned;
					$tmplist2 = array();
					foreach ($tmplist1 as $key => $val) {
						if ($val['id'] && $val['id'] != $act->userownerid) {
							$listofuserid[$val['id']] = $val;
						}
					}
				}
				$_SESSION['assignedtouser'] = json_encode($listofuserid);
			} else {
				if (!empty($_SESSION['assignedtouser'])) {
					$listofuserid = json_decode($_SESSION['assignedtouser'], true);
				}
			}
			$listofcontactid = array(); // not used yet
			$listofotherid = array(); // not used yet
			print '<div class="assignedtouser">';
			print $form->select_dolusers_forevent('view', 'assignedtouser', 1, '', 0, '', '', 0, 0, 0, '', ($act->datep != $act->datef) ? 1 : 0, $listofuserid, $listofcontactid, $listofotherid);
			print '</div>';
			/*if (in_array($user->id,array_keys($listofuserid)))
			{
				print '<div class="myavailability">';
				print $langs->trans("MyAvailability").': '.(($act->userassigned[$user->id]['transparency'] > 0)?$langs->trans("Busy"):$langs->trans("Available"));	// We show nothing if event is assigned to nobody
				print '</div>';
			}*/
			print '	</td></tr>';

			print '</table>';

			print '</div>';

			print dol_get_fiche_end();
		}
	}

	// Specific to thirdparty module
	if (($element_id || $element_ref) && $element == 'societe') {
		$socstatic = fetchObjectByElement($element_id, $element, $element_ref);
		if (is_object($socstatic)) {
			$savobject = $object;
			$object = $socstatic;

			require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
			$head = societe_prepare_head($socstatic);

			print dol_get_fiche_head($head, 'resources', $langs->trans("ThirdParty"), -1, 'company');

			dol_banner_tab($socstatic, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom', '', '&element='.$element);

			print '<div class="fichecenter">';

			print '<div class="underbanner clearboth"></div>';
			print '<table class="border centpercent">';

			// Alias name (commercial, trademark or alias name)
			print '<tr><td class="titlefield">'.$langs->trans('AliasNames').'</td><td colspan="3">';
			print $socstatic->name_alias;
			print "</td></tr>";

			print '</table>';

			print '</div>';

			print dol_get_fiche_end();

			$object = $savobject;
		}
	}

	// Specific to fichinter module
	if (($element_id || $element_ref) && $element == 'fichinter') {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/fichinter.lib.php';

		$fichinter = new Fichinter($db);
		$fichinter->fetch($element_id, $element_ref);
		$fichinter->fetch_thirdparty();

		$usercancreate = $user->hasRight('fichinter', 'creer');

		if (is_object($fichinter)) {
			$head = fichinter_prepare_head($fichinter);
			print dol_get_fiche_head($head, 'resource', $langs->trans("InterventionCard"), -1, 'intervention');

			// Intervention card
			$linkback = '<a href="'.DOL_URL_ROOT.'/fichinter/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';


			$morehtmlref = '<div class="refidno">';
			// Ref customer
			//$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $fichinter->ref_client, $fichinter, $user->rights->ficheinter->creer, 'string', '', 0, 1);
			//$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $fichinter->ref_client, $fichinter, $user->rights->ficheinter->creer, 'string', '', null, null, '', 1);
			$morehtmlref.=$form->editfieldkey("RefCustomer", 'ref_client', $fichinter->ref_client, $fichinter, 0, 'string', '', 0, 1);
			$morehtmlref.=$form->editfieldval("RefCustomer", 'ref_client', $fichinter->ref_client, $fichinter, 0, 'string', '', null, null, '', 1);
			// Thirdparty
			$morehtmlref .= '<br>'.$fichinter->thirdparty->getNomUrl(1, 'customer');
			// Project
			if (isModEnabled('project')) {
				$langs->load("projects");
				$morehtmlref .= '<br>';
				if ($usercancreate && 0) {
					$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
					if ($action != 'classify') {
						$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$fichinter->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
					}
					$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$fichinter->id, $fichinter->socid, $fichinter->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
				} else {
					if (!empty($fichinter->fk_project)) {
						$proj = new Project($db);
						$proj->fetch($fichinter->fk_project);
						$morehtmlref .= $proj->getNomUrl(1);
						if ($proj->title) {
							$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
						}
					}
				}
			}
			$morehtmlref .= '</div>';

			dol_banner_tab($fichinter, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, '&element='.$element, 0, '', '', 1);

			print dol_get_fiche_end();
		}
	}

	// Specific to product/service module
	if (($element_id || $element_ref) && ($element == 'product' || $element == 'service')) {
		require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

		$product = new Product($db);
		$product->fetch($element_id, $element_ref);

		if (is_object($product)) {
			$head = product_prepare_head($product);
			$titre = $langs->trans("CardProduct".$product->type);
			$picto = ($product->type == Product::TYPE_SERVICE ? 'service' : 'product');

			print dol_get_fiche_head($head, 'resources', $titre, -1, $picto);

			$shownav = 1;
			if ($user->socid && !in_array('product', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
				$shownav = 0;
			}
			dol_banner_tab($product, 'ref', '', $shownav, 'ref', 'ref', '', '&element='.$element);

			print dol_get_fiche_end();
		}
	}


	// hook for other elements linked
	$parameters = array('element'=>$element, 'element_id'=>$element_id, 'element_ref'=>$element_ref);
	$reshook = $hookmanager->executeHooks('printElementTab', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}


	//print load_fiche_titre($langs->trans('ResourcesLinkedToElement'),'','');
	print '<br>';

	// Show list of resource links

	foreach ($object->available_resources as $modresources => $resources) {
		$resources = (array) $resources; // To be sure $resources is an array
		foreach ($resources as $resource_obj) {
			$element_prop = getElementProperties($resource_obj);

			//print '/'.$modresources.'/class/'.$resource_obj.'.class.php<br>';

			$path = '';
			if (strpos($resource_obj, '@')) {
				$path .= '/'.$element_prop['module'];
			}

			$linked_resources = $object->getElementResources($element, $element_id, $resource_obj);

			// Output template part (modules that overwrite templates must declare this into descriptor)
			$defaulttpldir = '/core/tpl';
			$dirtpls = array_merge($conf->modules_parts['tpl'], array($defaulttpldir), array($path.$defaulttpldir));

			foreach ($dirtpls as $module => $reldir) {
				if (file_exists(dol_buildpath($reldir.'/resource_'.$element_prop['element'].'_add.tpl.php'))) {
					$tpl = dol_buildpath($reldir.'/resource_'.$element_prop['element'].'_add.tpl.php');
				} else {
					$tpl = DOL_DOCUMENT_ROOT.$reldir.'/resource_add.tpl.php';
				}
				if (empty($conf->file->strict_mode)) {
					$res = @include $tpl;
				} else {
					$res = include $tpl; // for debug
				}
				if ($res) {
					break;
				}
			}

			if ($mode != 'add' || $resource_obj != $resource_type) {
				foreach ($dirtpls as $module => $reldir) {
					if (file_exists(dol_buildpath($reldir.'/resource_'.$element_prop['element'].'_view.tpl.php'))) {
						$tpl = dol_buildpath($reldir.'/resource_'.$element_prop['element'].'_view.tpl.php');
					} else {
						$tpl = DOL_DOCUMENT_ROOT.$reldir.'/resource_view.tpl.php';
					}
					if (empty($conf->file->strict_mode)) {
						$res = @include $tpl;
					} else {
						$res = include $tpl; // for debug
					}
					if ($res) {
						break;
					}
				}
			}
		}
	}
}

// End of page
llxFooter();
$db->close();
