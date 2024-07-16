<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2024	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/projet/tasks/contact.php
 *	\ingroup    project
 *	\brief      Actors of a task
 */

require "../../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array('projects', 'companies'));

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$withproject = GETPOSTINT('withproject');
$project_ref = GETPOST('project_ref', 'alpha');

$object = new Task($db);
$projectstatic = new Project($db);

if ($id > 0 || $ref) {
	$object->fetch($id, $ref);
}

// Security check
$socid = 0;

restrictedArea($user, 'projet', $object->fk_project, 'projet&project');


/*
 * Actions
 */

// Add new contact
if ($action == 'addcontact' && $user->hasRight('projet', 'creer')) {
	$source = 'internal';
	if (GETPOST("addsourceexternal")) {
		$source = 'external';
	}

	$result = $object->fetch($id, $ref);

	if ($result > 0 && $id > 0) {
		if ($source == 'internal') {
			$idfortaskuser = ((GETPOST("userid") != 0 && GETPOST('userid') != -1) ? GETPOST("userid") : 0); // GETPOST('contactid') may val -1 to mean empty or -2 to means "everybody"
			$typeid = GETPOST('type');
		} else {
			$idfortaskuser = ((GETPOST("contactid") > 0) ? GETPOSTINT("contactid") : 0); // GETPOST('contactid') may val -1 to mean empty or -2 to means "everybody"
			$typeid = GETPOST('typecontact');
		}
		if ($idfortaskuser == -2) {
			$result = $projectstatic->fetch($object->fk_project);
			if ($result <= 0) {
				dol_print_error($db, $projectstatic->error, $projectstatic->errors);
			} else {
				$contactsofproject = $projectstatic->getListContactId('internal');
				foreach ($contactsofproject as $key => $val) {
					$result = $object->add_contact($val, $typeid, $source);
				}
			}
		} else {
			$result = $object->add_contact($idfortaskuser, $typeid, $source);
		}
	}

	if ($result >= 0) {
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id.($withproject ? '&withproject=1' : ''));
		exit;
	} else {
		if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
		} else {
			setEventMessages($object->error, $object->errors, 'errors');
		}
	}
}

// bascule du statut d'un contact
if ($action == 'swapstatut' && $user->hasRight('projet', 'creer')) {
	if ($object->fetch($id, $ref)) {
		$result = $object->swapContactStatus(GETPOSTINT('ligne'));
	} else {
		dol_print_error($db);
	}
}

// Efface un contact
if ($action == 'deleteline' && $user->hasRight('projet', 'creer')) {
	$object->fetch($id, $ref);
	$result = $object->delete_contact(GETPOSTINT("lineid"));

	if ($result >= 0) {
		header("Location: ".$_SERVER["PHP_SELF"]."?id=".$object->id.($withproject ? '&withproject=1' : ''));
		exit;
	} else {
		dol_print_error($db);
	}
}

// Retrieve First Task ID of Project if withprojet is on to allow project prev next to work
if (!empty($project_ref) && !empty($withproject)) {
	if ($projectstatic->fetch(0, $project_ref) > 0) {
		$tasksarray = $object->getTasksArray(0, 0, $projectstatic->id, $socid, 0);
		if (count($tasksarray) > 0) {
			$id = $tasksarray[0]->id;
		} else {
			header("Location: ".DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.($withproject ? '&withproject=1' : '').(empty($mode) ? '' : '&mode='.$mode));
			exit;
		}
	}
}

/*
 * View
 */
$form = new Form($db);
$formcompany   = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);
$result = $projectstatic->fetch($object->fk_project);

$title = $object->ref . ' - ' . $langs->trans("Contacts");
if (!empty($withproject)) {
	$title .= ' | ' . $langs->trans("Project") . (!empty($projectstatic->ref) ? ': '.$projectstatic->ref : '')  ;
}
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-project project-tasks page-task_contact');


/* *************************************************************************** */
/*                                                                             */
/* Card view and edit mode                                                       */
/*                                                                             */
/* *************************************************************************** */

if ($id > 0 || !empty($ref)) {
	if ($object->fetch($id, $ref) > 0) {
		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_TASK') && method_exists($object, 'fetchComments') && empty($object->comments)) {
			$object->fetchComments();
		}
		$id = $object->id; // So when doing a search from ref, id is also set correctly.

		if (getDolGlobalString('PROJECT_ALLOW_COMMENT_ON_PROJECT') && method_exists($projectstatic, 'fetchComments') && empty($projectstatic->comments)) {
			$projectstatic->fetchComments();
		}
		if (!empty($projectstatic->socid)) {
			$projectstatic->fetch_thirdparty();
		}

		$object->project = clone $projectstatic;

		$userWrite = $projectstatic->restrictedProjectArea($user, 'write');

		if ($withproject) {
			// Tabs for project
			$tab = 'tasks';
			$head = project_prepare_head($projectstatic);
			print dol_get_fiche_head($head, $tab, $langs->trans("Project"), -1, ($projectstatic->public ? 'projectpub' : 'project'));

			$param = (!empty($mode) && $mode == 'mine' ? '&mode=mine' : '');

			// Project card

			$linkback = '<a href="'.DOL_URL_ROOT.'/projet/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			$morehtmlref = '<div class="refidno">';
			// Title
			$morehtmlref .= $projectstatic->title;
			// Thirdparty
			if (isset($projectstatic->thirdparty->id) && $projectstatic->thirdparty->id > 0) {
				$morehtmlref .= '<br>'.$projectstatic->thirdparty->getNomUrl(1, 'project');
			}
			$morehtmlref .= '</div>';

			// Define a complementary filter for search of next/prev ref.
			if (!$user->hasRight('projet', 'all', 'lire')) {
				$objectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 0);
				$projectstatic->next_prev_filter = "rowid IN (".$db->sanitize(count($objectsListId) ? implode(',', array_keys($objectsListId)) : '0').")";
			}

			dol_banner_tab($projectstatic, 'project_ref', $linkback, 1, 'ref', 'ref', $morehtmlref);

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
					print '<input type="checkbox" disabled name="usage_opportunity"'.(GETPOSTISSET('usage_opportunity') ? (GETPOST('usage_opportunity', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_opportunity ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("ProjectFollowOpportunity");
					print $form->textwithpicto($langs->trans("ProjectFollowOpportunity"), $htmltext);
					print '<br>';
				}
				if (!getDolGlobalString('PROJECT_HIDE_TASKS')) {
					print '<input type="checkbox" disabled name="usage_task"'.(GETPOSTISSET('usage_task') ? (GETPOST('usage_task', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_task ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("ProjectFollowTasks");
					print $form->textwithpicto($langs->trans("ProjectFollowTasks"), $htmltext);
					print '<br>';
				}
				if (!getDolGlobalString('PROJECT_HIDE_TASKS') && getDolGlobalString('PROJECT_BILL_TIME_SPENT')) {
					print '<input type="checkbox" disabled name="usage_bill_time"'.(GETPOSTISSET('usage_bill_time') ? (GETPOST('usage_bill_time', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_bill_time ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("ProjectBillTimeDescription");
					print $form->textwithpicto($langs->trans("BillTime"), $htmltext);
					print '<br>';
				}
				if (isModEnabled('eventorganization')) {
					print '<input type="checkbox" disabled name="usage_organize_event"'.(GETPOSTISSET('usage_organize_event') ? (GETPOST('usage_organize_event', 'alpha') != '' ? ' checked="checked"' : '') : ($projectstatic->usage_organize_event ? ' checked="checked"' : '')).'"> ';
					$htmltext = $langs->trans("EventOrganizationDescriptionLong");
					print $form->textwithpicto($langs->trans("ManageOrganizeEvent"), $htmltext);
				}
				print '</td></tr>';
			}

			// Visibility
			print '<tr><td class="titlefield">'.$langs->trans("Visibility").'</td><td>';
			if ($projectstatic->public) {
				print img_picto($langs->trans('SharedProject'), 'world', 'class="paddingrightonly"');
				print $langs->trans('SharedProject');
			} else {
				print img_picto($langs->trans('PrivateProject'), 'private', 'class="paddingrightonly"');
				print $langs->trans('PrivateProject');
			}
			print '</td></tr>';

			// Budget
			print '<tr><td>'.$langs->trans("Budget").'</td><td>';
			if (isset($projectstatic->budget_amount) && strcmp($projectstatic->budget_amount, '')) {
				print price($projectstatic->budget_amount, 0, $langs, 1, 0, 0, $conf->currency);
			}
			print '</td></tr>';

			// Date start - end project
			print '<tr><td>'.$langs->trans("Dates").'</td><td>';
			$start = dol_print_date($projectstatic->date_start, 'day');
			print($start ? $start : '?');
			$end = dol_print_date($projectstatic->date_end, 'day');
			print ' - ';
			print($end ? $end : '?');
			if ($projectstatic->hasDelay()) {
				print img_warning("Late");
			}
			print '</td></tr>';

			// Other attributes
			$cols = 2;
			//include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

			print '</table>';

			print '</div>';
			print '<div class="fichehalfright">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border tableforfield centpercent">';

			// Description
			print '<td class="titlefield tdtop">'.$langs->trans("Description").'</td><td>';
			print nl2br($projectstatic->description);
			print '</td></tr>';

			// Categories
			if (isModEnabled('category')) {
				print '<tr><td class="valignmiddle">'.$langs->trans("Categories").'</td><td>';
				print $form->showCategories($projectstatic->id, 'project', 1);
				print "</td></tr>";
			}

			print '</table>';

			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();

			print '<br>';
		}


		// To verify role of users
		//$userAccess = $projectstatic->restrictedProjectArea($user); // We allow task affected to user even if a not allowed project
		//$arrayofuseridoftask=$object->getListContactId('internal');

		$head = task_prepare_head($object);
		print dol_get_fiche_head($head, 'task_contact', $langs->trans("Task"), -1, 'projecttask', 0, '', 'reposition');


		$param = (GETPOST('withproject') ? '&withproject=1' : '');
		$linkback = GETPOST('withproject') ? '<a href="'.DOL_URL_ROOT.'/projet/tasks.php?id='.$projectstatic->id.'">'.$langs->trans("BackToList").'</a>' : '';

		if (!GETPOST('withproject') || empty($projectstatic->id)) {
			$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user, 0, 1);
			$object->next_prev_filter = "fk_projet IN (".$db->sanitize($projectsListId).")";
		} else {
			$object->next_prev_filter = "fk_projet = ".((int) $projectstatic->id);
		}

		$morehtmlref = '';

		// Project
		if (empty($withproject)) {
			$result = $projectstatic->fetch($object->fk_project);
			$morehtmlref .= '<div class="refidno">';
			$morehtmlref .= $langs->trans("Project").': ';
			$morehtmlref .= $projectstatic->getNomUrl(1);
			$morehtmlref .= '<br>';

			// Third party
			$morehtmlref .= $langs->trans("ThirdParty").': ';
			if ($projectstatic->socid > 0) {
				$projectstatic->fetch_thirdparty();
				$morehtmlref .= $projectstatic->thirdparty->getNomUrl(1);
			}

			$morehtmlref .= '</div>';
		}

		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref, $param, 0, '', '', 1);

		print dol_get_fiche_end();

		/*
		 * Lines of contacts
		 */
		/*
		// Contacts lines (modules that overwrite templates must declare this into descriptor)
		$dirtpls=array_merge($conf->modules_parts['tpl'],array('/core/tpl'));
		foreach($dirtpls as $reldir)
		{
			$res=@include dol_buildpath($reldir.'/contacts.tpl.php');
			if ($res) break;
		}
		*/

		/*
		 * Add a new contact line
		 */
		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$id.'" method="POST">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="addcontact">';
		print '<input type="hidden" name="id" value="'.$id.'">';
		if ($withproject) {
			print '<input type="hidden" name="withproject" value="'.$withproject.'">';
		}

		print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder centpercent">';

		if ($action != 'editline' && $user->hasRight('projet', 'creer')) {
			print '<tr class="liste_titre">';
			print '<td>'.$langs->trans("NatureOfContact").'</td>';
			print '<td>'.$langs->trans("ThirdParty").'</td>';
			print '<td>'.$langs->trans("Users").'</td>';
			print '<td>'.$langs->trans("ContactType").'</td>';
			print '<td colspan="3">&nbsp;</td>';
			print "</tr>\n";

			// Ligne ajout pour contact interne
			print '<tr class="oddeven nohover">';

			print '<td class="nowrap">';
			print img_object('', 'user').' '.$langs->trans("Users");
			print '</td>';

			print '<td>';
			print $conf->global->MAIN_INFO_SOCIETE_NOM;
			print '</td>';

			print '<td>';
			// On recupere les id des users deja selectionnes
			if ($object->project->public) {
				$contactsofproject = ''; // Everybody
			} else {
				$contactsofproject = $projectstatic->getListContactId('internal');
			}
			print $form->select_dolusers((GETPOSTISSET('userid') ? GETPOSTINT('userid') : $user->id), 'userid', 0, '', 0, '', $contactsofproject, 0, 0, 0, '', 1, $langs->trans("ResourceNotAssignedToProject"));
			print '</td>';
			print '<td>';
			$formcompany->selectTypeContact($object, '', 'type', 'internal', 'position');
			print '</td>';
			print '<td class="right" colspan="3" ><input type="submit" class="button button-add small" value="'.$langs->trans("Add").'" name="addsourceinternal"></td>';
			print '</tr>';

			// Line to add an external contact. Only if project linked to a third party.
			if ($projectstatic->socid) {
				print '<tr class="oddeven">';

				print '<td class="nowrap">';
				print img_object('', 'contact').' '.$langs->trans("ThirdPartyContacts");
				print '</td>';

				print '<td>';
				$thirdpartyofproject = $projectstatic->getListContactId('thirdparty');
				$selectedCompany = GETPOSTISSET("newcompany") ? GETPOST("newcompany") : $projectstatic->socid;
				$selectedCompany = $formcompany->selectCompaniesForNewContact($object, 'id', $selectedCompany, 'newcompany', $thirdpartyofproject, 0, '&withproject='.$withproject);
				print '</td>';

				print '<td>';
				$contactofproject = $projectstatic->getListContactId('external');
				//print $form->selectcontacts($selectedCompany, '', 'contactid', 0, '', $contactofproject, 0, '', false, 0, 0);
				print $form->select_contact($selectedCompany, '', 'contactid', 0, '', $contactofproject, 0, 'maxwidth300 widthcentpercentminusx', true);
				$nbofcontacts = $form->num;
				print '</td>';
				print '<td>';
				$formcompany->selectTypeContact($object, '', 'typecontact', 'external', 'position');
				print '</td>';
				print '<td class="right" colspan="3" ><input type="submit" class="button button-add small" id="add-customer-contact" name="addsourceexternal" value="'.$langs->trans("Add").'"';
				if (!$nbofcontacts) {
					print ' disabled';
				}
				print '></td>';
				print '</tr>';
			}
		}

		// List of contact line
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("Source").'</td>';
		print '<td>'.$langs->trans("ThirdParty").'</td>';
		print '<td>'.$langs->trans("TaskContact").'</td>';
		print '<td>'.$langs->trans("ContactType").'</td>';
		print '<td class="center">'.$langs->trans("Status").'</td>';
		print '<td colspan="2">&nbsp;</td>';
		print "</tr>\n";

		$companystatic = new Societe($db);

		foreach (array('internal', 'external') as $source) {
			$tab = $object->liste_contact(-1, $source);

			$num = count($tab);

			$i = 0;
			while ($i < $num) {
				print '<tr class="oddeven" valign="top">';

				// Source
				print '<td class="left">';
				if ($tab[$i]['source'] == 'internal') {
					print $langs->trans("User");
				}
				if ($tab[$i]['source'] == 'external') {
					print $langs->trans("ThirdPartyContact");
				}
				print '</td>';

				// Societe
				print '<td class="left">';
				if ($tab[$i]['socid'] > 0) {
					$companystatic->fetch($tab[$i]['socid']);
					print $companystatic->getNomUrl(1);
				}
				if ($tab[$i]['socid'] < 0) {
					print $conf->global->MAIN_INFO_SOCIETE_NOM;
				}
				if (!$tab[$i]['socid']) {
					print '&nbsp;';
				}
				print '</td>';

				// Contact
				print '<td>';
				if ($tab[$i]['source'] == 'internal') {
					$userstatic->id = $tab[$i]['id'];
					$userstatic->lastname = $tab[$i]['lastname'];
					$userstatic->firstname = $tab[$i]['firstname'];
					$userstatic->photo = $tab[$i]['photo'];
					$userstatic->login = $tab[$i]['login'];
					$userstatic->email = $tab[$i]['email'];
					$userstatic->gender = $tab[$i]['gender'];
					$userstatic->status = $tab[$i]['statuscontact'];

					print $userstatic->getNomUrl(-1);
				}
				if ($tab[$i]['source'] == 'external') {
					$contactstatic->id = $tab[$i]['id'];
					$contactstatic->lastname = $tab[$i]['lastname'];
					$contactstatic->firstname = $tab[$i]['firstname'];
					$contactstatic->email = $tab[$i]['email'];
					$contactstatic->statut = $tab[$i]['statuscontact'];
					print $contactstatic->getNomUrl(1);
				}
				print '</td>';

				// Type de contact
				print '<td>'.$tab[$i]['libelle'].'</td>';

				// Statut
				print '<td class="center">';
				// Activation desativation du contact
				if ($object->status >= 0) {
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=swapstatut&ligne='.$tab[$i]['rowid'].($withproject ? '&withproject=1' : '').'">';
				}
				print $contactstatic->LibStatut($tab[$i]['status'], 3);
				if ($object->status >= 0) {
					print '</a>';
				}
				print '</td>';

				// Icon update et delete
				print '<td class="center nowrap">';
				if ($user->hasRight('projet', 'creer')) {
					print '&nbsp;';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=deleteline&token='.newToken().'&lineid='.$tab[$i]['rowid'].($withproject ? '&withproject=1' : '').'">';
					print img_picto($langs->trans('Unlink'), 'unlink');
					print '</a>';
				}
				print '</td>';

				print "</tr>\n";

				$i++;
			}
		}
		print "</table>";
		print '</div>';

		print "</form>";
	} else {
		print "ErrorRecordNotFound";
	}
}

if (is_object($hookmanager)) {
	$hookmanager->initHooks(array('contacttpl'));
	$parameters = array();
	$reshook = $hookmanager->executeHooks('formContactTpl', $parameters, $object, $action);
}

// End of page
llxFooter();
$db->close();
