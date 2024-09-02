<?php
/* Copyright (C) 2010      Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/projet/contact.php
 *       \ingroup    project
 *       \brief      List of all contacts of a project
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
if (isModEnabled('category')) {
	require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
}

// Load translation files required by the page
$langsLoad = array('projects', 'companies');
if (isModEnabled('eventorganization')) {
	$langsLoad[] = 'eventorganization';
}

$langs->loadLangs($langsLoad);

$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$lineid = GETPOSTINT('lineid');
$socid  = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');

$mine   = GETPOST('mode') == 'mine' ? 1 : 0;
//if (! $user->rights->projet->all->lire) $mine=1;	// Special for projects

$object = new Project($db);

include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once
if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
	$object->fetchComments();
}

// Security check
$socid = 0;
//if ($user->socid > 0) $socid = $user->socid;    // For external user, no check is done on company because readability is managed by public status of project and assignment.
$result = restrictedArea($user, 'projet', $id, 'projet&project');

$hookmanager->initHooks(array('projectcontactcard', 'globalcard'));


/*
 * Actions
 */

$parameters = array('id' => $id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Test if we can add contact to the tasks at the same times, if not or not required, make a redirect
	$formconfirmtoaddtasks = '';
	if ($action == 'addcontact') {
		$form = new Form($db);

		$source = GETPOST("source", 'aZ09');

		$taskstatic = new Task($db);
		$task_array = $taskstatic->getTasksArray(0, 0, $object->id, 0, 0);
		$nbTasks = count($task_array);

		//If no task available, redirec to to add confirm
		$type_to = (GETPOST('typecontact') ? 'typecontact='.GETPOST('typecontact') : 'type='.GETPOST('type'));
		$personToAffect = (GETPOST('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
		$affect_to = (GETPOST('userid') ? 'userid='.$personToAffect : 'contactid='.$personToAffect);
		$url_redirect = '?id='.$object->id.'&'.$affect_to.'&'.$type_to.'&source='.$source;

		if ($personToAffect > 0 && (!getDolGlobalString('PROJECT_HIDE_TASKS') || $nbTasks > 0)) {
			$text = $langs->trans('AddPersonToTask');
			$textbody = $text.' (<a href="#" class="selectall">'.$langs->trans("SelectAll").'</a>)';
			$formquestion = array('text' => $textbody);

			$task_to_affect = array();
			foreach ($task_array as $task) {
				$task_already_affected = false;
				$personsLinked = $task->liste_contact(-1, $source);
				if (!is_array($personsLinked) && count($personsLinked) < 0) {
					setEventMessage($object->error, 'errors');
				} else {
					foreach ($personsLinked as $person) {
						if ($person['id'] == $personToAffect) {
							$task_already_affected = true;
							break;
						}
					}
					if (!$task_already_affected) {
						$task_to_affect[$task->id] = $task->id;
					}
				}
			}

			if (empty($task_to_affect)) {
				$action = 'addcontact_confirm';
			} else {
				$formcompany = new FormCompany($db);
				foreach ($task_array as $task) {
					$key = $task->id;
					$val = $task->ref . ' '.dol_trunc($task->label);
					$formquestion[] = array(
						'type' => 'other',
						'name' => 'person_'.$key.',person_role_'.$key,
						'label' => '<input type="checkbox" class="flat'.(in_array($key, $task_to_affect) ? ' taskcheckboxes"' : '" checked disabled').' id="person_'.$key.'" name="person_'.$key.'" value="1"> <label for="person_'.$key.'">'.$val.'<label>',
						'value' => $formcompany->selectTypeContact($taskstatic, '', 'person_role_'.$key, $source, 'position', 0, 'minwidth100imp', 0, 1)
					);
				}
				$formquestion[] = array('type' => 'other', 'name' => 'tasksavailable', 'label' => '', 'value' => '<input type="hidden" id="tasksavailable" name="tasksavailable" value="'.implode(',', array_keys($task_to_affect)).'">');
			}

			$formconfirmtoaddtasks = $form->formconfirm($_SERVER['PHP_SELF'] . $url_redirect, $text, '', 'addcontact_confirm', $formquestion, '', 1, 300, 590);
			$formconfirmtoaddtasks .= '
			 <script>
			 $(document).ready(function() {
				var saveprop = false;
			 	$(".selectall").click(function(){
					console.log("We click on select all with "+saveprop);
					if (!saveprop) {
						$(".taskcheckboxes").prop("checked", true);
						saveprop = true;
					} else {
						$(".taskcheckboxes").prop("checked", false);
						saveprop = false;
					}
				});
			 });
			 </script>';
		} else {
			$action = 'addcontact_confirm';
		}
	}

	// Add new contact
	if ($action == 'addcontact_confirm' && $user->hasRight('projet', 'creer')) {
		if (GETPOST('confirm', 'alpha') == 'no') {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}

		$contactid = (GETPOST('userid') ? GETPOSTINT('userid') : GETPOSTINT('contactid'));
		$typeid = (GETPOST('typecontact') ? GETPOST('typecontact') : GETPOST('type'));
		$groupid = GETPOSTINT('groupid');
		$contactarray = array();
		$errorgroup = 0;
		$errorgrouparray = array();

		if ($groupid > 0) {
			require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
			$usergroup = new UserGroup($db);
			$result = $usergroup->fetch($groupid);
			if ($result > 0) {
				$excludefilter = 'statut = 1';
				$tmpcontactarray = $usergroup->listUsersForGroup($excludefilter, 0);
				if ($contactarray <= 0) {
					$error++;
				} else {
					foreach ($tmpcontactarray as $tmpuser) {
						$contactarray[] = $tmpuser->id;
					}
				}
			} else {
				$error++;
			}
		} elseif (! ($contactid > 0)) {
			$error++;
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Contact")), null, 'errors');
		} else {
			$contactarray[] = $contactid;
		}

		$result = 0;
		$result = $object->fetch($id);
		if (!$error && $result > 0 && $id > 0) {
			foreach ($contactarray as $key => $contactid) {
				$result = $object->add_contact($contactid, $typeid, GETPOST("source", 'aZ09'));

				if ($result == 0) {
					if ($groupid > 0) {
						$errorgroup++;
						$errorgrouparray[] = $contactid;
					} else {
						$langs->load("errors");
						setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
					}
				} elseif ($result < 0) {
					if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
						if ($groupid > 0) {
							$errorgroup++;
							$errorgrouparray[] = $contactid;
						} else {
							$langs->load("errors");
							setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
						}
					} else {
						setEventMessages($object->error, $object->errors, 'errors');
					}
				}

				$affecttotask = GETPOST('tasksavailable', 'intcomma');
				if (!empty($affecttotask)) {
					require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
					$task_to_affect = explode(',', $affecttotask);
					if (!empty($task_to_affect)) {
						foreach ($task_to_affect as $task_id) {
							if (GETPOSTISSET('person_'.$task_id) && GETPOST('person_'.$task_id, 'san_alpha')) {
								$tasksToAffect = new Task($db);
								$result = $tasksToAffect->fetch($task_id);
								if ($result < 0) {
									setEventMessages($tasksToAffect->error, null, 'errors');
								} else {
									$result = $tasksToAffect->add_contact($contactid, GETPOST('person_role_'.$task_id), GETPOST("source", 'aZ09'));
									if ($result < 0) {
										if ($tasksToAffect->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
											$langs->load("errors");
											setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
										} else {
											setEventMessages($tasksToAffect->error, $tasksToAffect->errors, 'errors');
										}
									}
								}
							}
						}
					}
				}
			}
		}
		if ($errorgroup > 0) {
			$langs->load("errors");
			if ($errorgroup == count($contactarray)) {
				setEventMessages($langs->trans("ErrorThisGroupIsAlreadyDefinedAsThisType"), null, 'errors');
			} else {
				$tmpuser = new User($db);
				foreach ($errorgrouparray as $key => $value) {
					$tmpuser->fetch($value);
					setEventMessages($langs->trans("ErrorThisContactXIsAlreadyDefinedAsThisType", dolGetFirstLastname($tmpuser->firstname, $tmpuser->lastname)), null, 'errors');
				}
			}
		}

		if ($result >= 0) {
			header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
			exit;
		}
	}

	// Change contact's status
	if ($action == 'swapstatut' && $user->hasRight('projet', 'creer')) {
		if ($object->fetch($id)) {
			$result = $object->swapContactStatus(GETPOSTINT('ligne'));
		} else {
			dol_print_error($db);
		}
	}

	// Delete a contact
	if (($action == 'deleteline' || $action == 'deletecontact') && $user->hasRight('projet', 'creer')) {
		$object->fetch($id);
		$result = $object->delete_contact(GETPOSTINT("lineid"));

		if ($result >= 0) {
			header("Location: contact.php?id=".$object->id);
			exit;
		} else {
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

$title = $langs->trans('ProjectContact').' - '.$object->ref.' '.$object->name;
if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/projectnameonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->name) {
	$title = $object->ref.' '.$object->name.' - '.$langs->trans('ProjectContact');
}

$help_url = 'EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos|DE:Modul_Projekte';

llxHeader('', $title, $help_url);



if ($id > 0 || !empty($ref)) {
	/*
	 * View
	 */
	if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($object, 'fetchComments') && empty($object->comments)) {
		$object->fetchComments();
	}
	// To verify role of users
	//$userAccess = $object->restrictedProjectArea($user,'read');
	$userWrite = $object->restrictedProjectArea($user, 'write');
	//$userDelete = $object->restrictedProjectArea($user,'delete');
	//print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

	$head = project_prepare_head($object);
	print dol_get_fiche_head($head, 'contact', $langs->trans("Project"), -1, ($object->public ? 'projectpub' : 'project'));

	$formconfirm = $formconfirmtoaddtasks;

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;

	// Project card

	if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['project'])) {
		$tmpurl = $_SESSION['pageforbacktolist']['project'];
		$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
		$linkback = '<a href="'.$tmpurl.(preg_match('/\?/', $tmpurl) ? '&' : '?'). 'restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	} else {
		$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

	$morehtmlref = '<div class="refidno">';
	// Title
	$morehtmlref .= dol_escape_htmltag($object->title);
	$morehtmlref .= '<br>';
	// Thirdparty
	if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
		$morehtmlref .= $object->thirdparty->getNomUrl(1, 'project');
	}
	$morehtmlref .= '</div>';

	// Define a complementary filter for search of next/prev ref.
	if (!$user->hasRight('projet', 'all', 'lire')) {
		$objectsListId = $object->getProjectsAuthorizedForUser($user, 0, 0);
		$object->next_prev_filter = "rowid IN (".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0').")";
	}

	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Usage
	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') || !getDolGlobalString('PROJECT_HIDE_TASKS') || isModEnabled('eventorganization')) {
		print '<tr><td class="tdtop">';
		print $langs->trans("Usage");
		print '</td>';
		print '<td>';
		if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES')) {
			print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_opportunity ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowOpportunity");
			print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
			print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_task ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectFollowTasks");
			print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
			print '<br>';
		}
		if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
			print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_bill_time ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("ProjectBillTimeDescription");
			print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
			print '<br>';
		}
		if (isModEnabled('eventorganization')) {
			print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($object->usage_organize_event ? ' checked="checked"' : '')).'"> ';
			$htmltext = $langs->trans("EventOrganizationDescriptionLong");
			print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
		}
		print '</td></tr>';
	}

	// Visibility
	print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
	if ($object->public) {
		print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
		print $langs->trans('SharedProject');
	} else {
		print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
		print $langs->trans('PrivateProject');
	}
	print '</td></tr>';

	if (getDolGlobalString('PROJECT_USE_OPPORTUNITIES') && !empty($object->usage_opportunity)) {
		// Opportunity status
		print '<tr><td>'.$langs->trans("OpportunityStatus").'</td><td>';
		$code = dol_getIdFromCode($db, $object->opp_status, 'c_lead_status', 'rowid', 'code');
		if ($code) {
			print $langs->trans("OppStatus".$code);
		}

		// Opportunity percent
		print ' <span title="'.$langs->trans("OpportunityProbability").'"> / ';
		if (strcmp($object->opp_percent, '')) {
			print price($object->opp_percent, 0, $langs, 1, 0).' %';
		}
		print '</span></td></tr>';

		// Opportunity Amount
		print '<tr><td>'.$langs->trans("OpportunityAmount").'</td><td>';
		if (strcmp($object->opp_amount, '')) {
			print '<span class="amount">'.price($object->opp_amount, 0, $langs, 1, 0, -1, $conf->currency).'</span>';
			if (strcmp($object->opp_percent, '')) {
				print ' &nbsp; &nbsp; &nbsp; <span title="'.dol_escape_htmltag($langs->trans('OpportunityWeightedAmount')).'"><span class="opacitymedium">'.$langs->trans("OpportunityWeightedAmountShort").'</span>: <span class="amount">'.price($object->opp_amount * $object->opp_percent / 100, 0, $langs, 1, 0, -1, $conf->currency).'</span></span>';
			}
		}
		print '</td></tr>';
	}

	// Budget
	print '<tr><td>'.$langs->trans("Budget").'</td><td>';
	if (!is_null($object->budget_amount) && strcmp($object->budget_amount, '')) {
		print '<span class="amount">'.price($object->budget_amount, 0, $langs, 1, 0, 0, $conf->currency).'</span>';
	}
	print '</td></tr>';

	// Date start - end project
	print '<tr><td>'.$langs->trans("Dates").'</td><td>';
	$start = dol_print_date($object->date_start, 'day');
	print($start ? $start : '?');
	$end = dol_print_date($object->date_end, 'day');
	print ' <span class="opacitymedium">-</span> ';
	print($end ? $end : '?');
	if ($object->hasDelay()) {
		print img_warning("Late");
	}
	print '</td></tr>';

	// Other attributes
	$cols = 2;
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print "</table>";

	print '</div>';
	print '<div class="fichehalfright">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Description
	print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
	print dol_htmlentitiesbr($object->description);
	print '</td></tr>';

	// Categories
	if (isModEnabled('category')) {
		print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
		print $form->showCategories($object->id, Categorie::TYPE_PROJECT, 1);
		print "</td></tr>";
	}

	print '</table>';

	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	print '<br>';

	// Contacts lines (modules that overwrite templates must declare this into descriptor)
	$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
	foreach ($dirtpls as $reldir) {
		$res = @include dol_buildpath($reldir.'/contacts.tpl.php');
		if ($res) {
			break;
		}
	}
}

// End of page
llxFooter();
$db->close();
