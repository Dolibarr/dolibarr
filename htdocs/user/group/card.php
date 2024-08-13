<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2021	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Herve Prot				<herve.prot@symeos.com>
 * Copyright (C) 2012		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2018		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/user/group/card.php
 *       \brief      Tab of a user group
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Define if user can read permissions
$canreadperms = ($user->admin || $user->hasRight("user", "user", "read"));
$caneditperms = ($user->admin || $user->hasRight("user", "user", "write"));
$candisableperms = ($user->admin || $user->hasRight("user", "user", "delete"));
$feature2 = 'user';

// Advanced permissions
$advancedpermsactive = false;
if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
	$advancedpermsactive = true;
	$canreadperms = ($user->admin || ($user->hasRight("user", "group_advance", "read") && $user->hasRight("user", "group_advance", "readperms")));
	$caneditperms = ($user->admin || $user->hasRight("user", "group_advance", "write"));
	$candisableperms = ($user->admin || $user->hasRight("user", "group_advance", "delete"));
	$feature2 = 'group_advance';
}

// Load translation files required by page
$langs->loadLangs(array('users', 'other'));

$id = GETPOSTINT('id');
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'groupcard'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha');

$userid = GETPOSTINT('user');

$object = new UserGroup($db);
$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be include, not include_once.
$object->getrights();

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('groupcard', 'globalcard'));

// Security check
$result = restrictedArea($user, 'user', $id, 'usergroup&usergroup', $feature2);

// Users/Groups management only in master entity if transverse mode
if (isModEnabled('multicompany') && $conf->entity > 1 && $conf->global->MULTICOMPANY_TRANSVERSE_MODE) {
	accessforbidden();
}


/**
 * Actions
 */

$parameters = array('id' => $id, 'userid' => $userid, 'caneditperms' => $caneditperms);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	$backurlforlist = DOL_URL_ROOT.'/user/group/list.php';

	if (empty($backtopage) || ($cancel && empty($id))) {
		if (empty($backtopage) || ($cancel && strpos($backtopage, '__ID__'))) {
			if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
				$backtopage = $backurlforlist;
			} else {
				$backtopage = DOL_URL_ROOT.'/user/group/card.php?id='.($id > 0 ? $id : '__ID__');
			}
		}
	}

	if ($cancel) {
		header("Location: ".$backtopage);
		exit;
	}

	// Action remove group
	if ($action == 'confirm_delete' && $confirm == "yes") {
		if ($caneditperms) {
			$object->fetch($id);
			$object->delete($user);
			header("Location: ".DOL_URL_ROOT."/user/group/list.php?restore_lastsearch_values=1");
			exit;
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	// Action add group
	if ($action == 'add') {
		if ($caneditperms) {
			if (!GETPOST("nom", "alphanohtml")) {
				setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
				$action = "create"; // Go back to create page
			} else {
				$object->name	= GETPOST("nom", 'alphanohtml');
				$object->note	= dol_htmlcleanlastbr(trim(GETPOST("note", 'restricthtml')));

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) {
					$error++;
				}

				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$object->entity = 0;
				} else {
					if ($conf->entity == 1 && $user->admin && !$user->entity) {		// Same permissions test than the one used to show the combo of entities into the form
						$object->entity = GETPOSTISSET("entity") ? GETPOST("entity") : $conf->entity;
					} else {
						$object->entity = $conf->entity;
					}
				}

				$db->begin();

				$id = $object->create();

				if ($id > 0) {
					$db->commit();

					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				} else {
					$db->rollback();

					$langs->load("errors");
					setEventMessages($langs->trans("ErrorGroupAlreadyExists", $object->name), null, 'errors');
					$action = "create"; // Go back to create page
				}
			}
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	// Add/Remove user into group
	if ($action == 'adduser' || $action == 'removeuser') {
		if ($caneditperms) {
			if ($userid > 0) {
				$object->fetch($id);
				$object->oldcopy = clone $object;

				$edituser = new User($db);
				$edituser->fetch($userid);
				if ($action == 'adduser') {
					$result = $edituser->SetInGroup($object->id, $object->entity);
				}
				if ($action == 'removeuser') {
					$result = $edituser->RemoveFromGroup($object->id, $object->entity);
				}

				if ($result > 0) {
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				} else {
					setEventMessages($edituser->error, $edituser->errors, 'errors');
				}
			}
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}


	if ($action == 'update') {
		if ($caneditperms) {
			$db->begin();

			$object->fetch($id);

			$object->oldcopy = clone $object;

			$object->name = GETPOST("nom", 'alphanohtml');
			$object->note = dol_htmlcleanlastbr(trim(GETPOST("note", 'restricthtml')));
			$object->tms = dol_now();

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object, '@GETPOSTISSET');
			if ($ret < 0) {
				$error++;
			}

			if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
				$object->entity = 0;
			} elseif (GETPOSTISSET("entity")) {
				$object->entity = GETPOSTINT("entity");
			}

			$ret = $object->update();

			if ($ret >= 0 && !count($object->errors)) {
				setEventMessages($langs->trans("GroupModified"), null, 'mesgs');
				$db->commit();
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
			}
		} else {
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'mesgs');
		}
	}

	// Actions to build doc
	$upload_dir = $conf->user->dir_output.'/usergroups';
	$permissiontoadd = $user->hasRight("user", "user", "write");
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

$title = $object->name.' - '.$langs->trans("Card");
if ($action == 'create') {
	$title = $langs->trans("NewGroup");
}
$help_url = "";
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-group_card');


$form = new Form($db);
$fuserstatic = new User($db);
$form = new Form($db);
$formfile = new FormFile($db);

if ($action == 'create') {
	print load_fiche_titre($langs->trans("NewGroup"), '', 'object_group');

	dol_set_focus('#nom');

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';

	print dol_get_fiche_head(array(), '', '', 0, '');

	print '<table class="border centpercent tableforfieldcreate">';

	// Multicompany
	if (isModEnabled('multicompany') && is_object($mc)) {
		if (!getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity == 1 && $user->admin && !$user->entity) {
			print "<tr>".'<td class="tdtop">'.$langs->trans("Entity").'</td>';
			print "<td>".$mc->select_entities($conf->entity);
			print "</td></tr>\n";
		} else {
			print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
		}
	}

	// Common attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_add.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_add.tpl.php';

	print "</table>\n";

	print dol_get_fiche_end();

	print '<div class="center">';
	print '<input class="button" name="add" value="'.$langs->trans("CreateGroup").'" type="submit">';
	print ' &nbsp; ';
	print '<input class="button button-cancel" value="'.$langs->trans("Cancel").'" name="cancel" type="submit">';
	print '</div>';

	print "</form>";
} else {
	/* ************************************************************************** */
	/*                                                                            */
	/* Visu et edition                                                            */
	/*                                                                            */
	/* ************************************************************************** */
	if ($id) {
		$head = group_prepare_head($object);
		$title = $langs->trans("Group");

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete') {
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("DeleteAGroup"), $langs->trans("ConfirmDeleteGroup", $object->name), "confirm_delete", '', 0, 1);
		}

		/*
		 * Card in view mode
		 */

		if ($action != 'edit') {
			print dol_get_fiche_head($head, 'group', $title, -1, 'group');

			$linkback = '<a href="'.DOL_URL_ROOT.'/user/group/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			dol_banner_tab($object, 'id', $linkback, $user->hasRight("user", "user", "read") || $user->admin);

			print '<div class="fichecenter">';
			print '<div class="fichehalfleft">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent tableforfield">';

			// Name (already in dol_banner, we keep it to have the GlobalGroup picto, but we should move it in dol_banner)
			if (!empty($conf->mutlicompany->enabled)) {
				print '<tr><td class="titlefield">'.$langs->trans("Name").'</td>';
				print '<td class="valeur">'.dol_escape_htmltag($object->name);
				if (empty($object->entity)) {
					print img_picto($langs->trans("GlobalGroup"), 'redstar');
				}
				print "</td></tr>\n";
			}

			// Multicompany
			if (isModEnabled('multicompany') && is_object($mc) && !getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity == 1 && $user->admin && !$user->entity) {
				$mc->getInfo($object->entity);
				print "<tr>".'<td class="titlefield">'.$langs->trans("Entity").'</td>';
				print '<td class="valeur">'.dol_escape_htmltag($mc->label);
				print "</td></tr>\n";
			}

			unset($object->fields['nom']); // Name already displayed in banner

			// Common attributes
			$keyforbreak = '';
			include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

			// Other attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print '</table>';
			print '</div>';
			print '</div>';

			print '<div class="clearboth"></div>';

			print dol_get_fiche_end();


			/*
			 * Action bar
			 */
			print '<div class="tabsAction">';

			$parameters = array();
			$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			if ($reshook < 0) {
				setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
			}

			if ($caneditperms) {
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=edit&token='.newToken().'">'.$langs->trans("Modify").'</a>';
			}

			if ($candisableperms) {
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&token='.newToken().'&id='.$object->id.'">'.$langs->trans("DeleteGroup").'</a>';
			}

			print "</div>\n";

			// List users in group

			print load_fiche_titre($langs->trans("ListOfUsersInGroup"), '', 'user');

			// Select the users that do not belong to the group yet
			$exclude = array();

			if (!empty($object->members)) {
				foreach ($object->members as $useringroup) {
					$exclude[] = $useringroup->id;
				}
			}

			// Other form for add user to group
			$parameters = array('caneditperms' => $caneditperms, 'exclude' => $exclude);
			$reshook = $hookmanager->executeHooks('formAddUserToGroup', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			if (empty($reshook)) {
				if ($caneditperms) {
					print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="adduser">';
					print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
					print '<table class="noborder centpercent">'."\n";
					print '<tr class="liste_titre"><td class="titlefield liste_titre">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
					print '<td class="liste_titre">';
					print $form->select_dolusers('', 'user', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'minwidth200 maxwidth500');
					print ' &nbsp; ';
					print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
					print '<input type="submit" class="button buttongen button-add" value="'.$langs->trans("Add").'">';
					print '</td></tr>'."\n";
					print '</table>';
					print '</div>';
					print '</form>'."\n";
					//print '<br>';
				}

				/*
				 * Group members
				 */

				print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre">'.$langs->trans("Login").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
				print '<td class="liste_titre center" width="5">'.$langs->trans("Status").'</td>';
				print '<td class="liste_titre right" width="5">&nbsp;</td>';
				print "</tr>\n";

				$object->fetch($object->id, '', true);	// true to force load of all users, member of the group

				if (!empty($object->members)) {
					foreach ($object->members as $useringroup) {
						print '<tr class="oddeven">';
						print '<td class="tdoverflowmax150">';
						print $useringroup->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
						if (isModEnabled('multicompany') && $useringroup->admin && empty($useringroup->entity)) {
							print img_picto($langs->trans("SuperAdministratorDesc"), 'redstar');
						} elseif ($useringroup->admin) {
							print img_picto($langs->trans("AdministratorDesc"), 'star');
						}
						print '</td>';
						print '<td>'.$useringroup->lastname.'</td>';
						print '<td>'.$useringroup->firstname.'</td>';
						print '<td class="center">'.$useringroup->getLibStatut(5).'</td>';
						print '<td class="right">';
						if (!empty($user->admin)) {
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&action=removeuser&token='.newToken().'&user='.$useringroup->id.'">';
							print img_picto($langs->trans("RemoveFromGroup"), 'unlink');
							print '</a>';
						} else {
							print "-";
						}
						print "</td></tr>\n";
					}
				} else {
					print '<tr><td colspan="6"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
				}
				print "</table>";
				print '</div>';
			}

			print "<br>";

			print '<div class="fichecenter"><div class="fichehalfleft">';

			/*
			 * Generated documents
			 */

			$filename = dol_sanitizeFileName($object->ref);
			$filedir = $conf->user->dir_output."/usergroups/".dol_sanitizeFileName($object->ref);
			$urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
			$genallowed = $user->hasRight("user", "user", "write");
			$delallowed = $user->hasRight("user", "user", "delete");

			$somethingshown = $formfile->showdocuments('usergroup', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->model_pdf, 1, 0, 0, 28, 0, '', 0, '', $mysoc->default_lang);

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, null);
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			print '</div><div class="fichehalfright">';

			// List of actions on element
			/*include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'usergroup', $socid, 1, '', $MAXEVENT);*/

			print '</div></div>';
		}

		/*
		 * Card in edit mode
		 */

		if ($action == 'edit' && $caneditperms) {
			print '<form action="'.$_SERVER['PHP_SELF'].'" method="post" name="updategroup" enctype="multipart/form-data">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="update">';
			print '<input type="hidden" name="backtopage" value="'.$backtopage.'">';
			print '<input type="hidden" name="id" value="'.$object->id.'">';

			print dol_get_fiche_head($head, 'group', $title, 0, 'group');

			print '<table class="border centpercent tableforfieldedit">'."\n";

			// Multicompany
			if (isModEnabled('multicompany') && is_object($mc)) {
				if (!getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') && $conf->entity == 1 && $user->admin && !$user->entity) {
					print "<tr>".'<td class="tdtop">'.$langs->trans("Entity").'</td>';
					print "<td>".$mc->select_entities($object->entity);
					print "</td></tr>\n";
				} else {
					print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
			}

			// Common attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_edit.tpl.php';

			// Other attributes
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_edit.tpl.php';

			print '</table>';

			print dol_get_fiche_end();

			print $form->buttonsSaveCancel();

			print '</form>';
		}
	}
}

// End of page
llxFooter();
$db->close();
