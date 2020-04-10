<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2017	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Herve Prot			<herve.prot@symeos.com>
 * Copyright (C) 2012		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2018		Juanjo Menent		<jmenent@2byte.es>
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
 *       \brief      Onglet groupes utilisateurs
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Defini si peux lire/modifier utilisateurs et permisssions
$canreadperms = ($user->admin || $user->rights->user->user->lire);
$caneditperms = ($user->admin || $user->rights->user->user->creer);
$candisableperms = ($user->admin || $user->rights->user->user->supprimer);
$feature2 = 'user';

// Advanced permissions
if (!empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadperms = ($user->admin || $user->rights->user->group_advance->read);
    $caneditperms = ($user->admin || $user->rights->user->group_advance->write);
    $candisableperms = ($user->admin || $user->rights->user->group_advance->delete);
    $feature2 = 'group_advance';
}

// Load translation files required by page
$langs->loadLangs(array('users', 'other'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'alpha');
$cancel = GETPOST('cancel', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'groupcard'; // To manage different context of search

$userid = GETPOST('user', 'int');

// Security check
$result = restrictedArea($user, 'user', $id, 'usergroup&usergroup', $feature2);

// Users/Groups management only in master entity if transverse mode
if (!empty($conf->multicompany->enabled) && $conf->entity > 1 && $conf->global->MULTICOMPANY_TRANSVERSE_MODE)
{
    accessforbidden();
}

$object = new Usergroup($db);
if ($id > 0)
{
	$object->fetch($id);
	$object->getrights();
}

$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('groupcard', 'globalcard'));



/**
 * Actions
 */

$parameters = array('id' => $id, 'userid' => $userid, 'caneditperms' => $caneditperms);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
	if ($cancel)
	{
		if (!empty($backtopage))
		{
			header("Location: ".$backtopage);
			exit;
		}
		else
		{
			header("Location: ".DOL_URL_ROOT.'/user/group/list.php');
			exit;
		}
		$action = '';
	}

	// Action remove group
	if ($action == 'confirm_delete' && $confirm == "yes")
	{
		if ($caneditperms)
		{
			$object->fetch($id);
			$object->delete($user);
			header("Location: ".DOL_URL_ROOT."/user/group/list.php?restore_lastsearch_values=1");
			exit;
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	// Action add group
	if ($action == 'add')
	{
		if ($caneditperms)
		{
			if (!$_POST["nom"]) {
				setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
				$action = "create"; // Go back to create page
			} else {
				$object->name	= trim(GETPOST("nom", 'nohtml'));
				$object->nom = $object->name; // For backward compatibility
				$object->note	= dol_htmlcleanlastbr(trim(GETPOST("note", 'none')));

				// Fill array 'array_options' with data from add form
				$ret = $extrafields->setOptionalsFromPost(null, $object);
				if ($ret < 0) $error++;

				if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) $object->entity = 0;
				else $object->entity = $_POST["entity"];

				$db->begin();

				$id = $object->create();

				if ($id > 0)
				{
					$db->commit();

					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				}
				else
				{
					$db->rollback();

					$langs->load("errors");
					setEventMessages($langs->trans("ErrorGroupAlreadyExists", $object->name), null, 'errors');
					$action = "create"; // Go back to create page
				}
			}
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}

	// Add/Remove user into group
	if ($action == 'adduser' || $action == 'removeuser')
	{
		if ($caneditperms)
		{
			if ($userid > 0)
			{
				$object->fetch($id);
				$object->oldcopy = clone $object;

				$edituser = new User($db);
				$edituser->fetch($userid);
				if ($action == 'adduser')    $result = $edituser->SetInGroup($object->id, $object->entity);
				if ($action == 'removeuser') $result = $edituser->RemoveFromGroup($object->id, $object->entity);

				if ($result > 0)
				{
					header("Location: ".$_SERVER['PHP_SELF']."?id=".$object->id);
					exit;
				}
				else
				{
					setEventMessages($edituser->error, $edituser->errors, 'errors');
				}
			}
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
		}
	}


	if ($action == 'update')
	{
		if ($caneditperms)
		{
			$db->begin();

			$object->fetch($id);

			$object->oldcopy = clone $object;

			$object->name	= trim(GETPOST("group", 'nohtml'));
			$object->nom = $object->name; // For backward compatibility
			$object->note	= dol_htmlcleanlastbr(trim(GETPOST("note", 'none')));

			// Fill array 'array_options' with data from add form
			$ret = $extrafields->setOptionalsFromPost(null, $object);
			if ($ret < 0) $error++;

			if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) $object->entity = 0;
			else $object->entity = $_POST["entity"];

			$ret = $object->update();

			if ($ret >= 0 && !count($object->errors))
			{
				setEventMessages($langs->trans("GroupModified"), null, 'mesgs');
				$db->commit();
			}
			else
			{
				setEventMessages($object->error, $object->errors, 'errors');
				$db->rollback();
			}
		}
		else
		{
			$langs->load("errors");
			setEventMessages($langs->trans('ErrorForbidden'), null, 'mesgs');
		}
	}

	// Actions to build doc
	$upload_dir = $conf->usergroup->dir_output;
	$permissiontoadd = $user->rights->user->user->creer;
	include DOL_DOCUMENT_ROOT.'/core/actions_builddoc.inc.php';
}


/*
 * View
 */

llxHeader('', $langs->trans("GroupCard"));

$form = new Form($db);
$fuserstatic = new User($db);
$form = new Form($db);
$formfile = new FormFile($db);

if ($action == 'create')
{
    print load_fiche_titre($langs->trans("NewGroup"));

    print dol_set_focus('#nom');

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="add">';

    dol_fiche_head('', '', '', 0, '');

    print '<table class="border centpercent">';

	print "<tr>";
	print '<td class="fieldrequired titlefield">'.$langs->trans("Name").'</td>';
	print '<td><input type="text" id="nom" name="nom" value="'.dol_escape_htmltag(GETPOST('nom', 'nohtml')).'"></td></tr>';

	// Multicompany
	if (!empty($conf->multicompany->enabled) && is_object($mc))
	{
		if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && !$user->entity)
		{
			print "<tr>".'<td class="tdtop">'.$langs->trans("Entity").'</td>';
			print "<td>".$mc->select_entities($conf->entity);
			print "</td></tr>\n";
		}
		else
		{
			print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
		}
	}

    print "<tr>".'<td class="tdtop">'.$langs->trans("Description").'</td><td>';
    require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor = new DolEditor('note', '', '', 240, 'dolibarr_notes', '', false, true, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_8, '90%');
    $doleditor->Create();
    print "</td></tr>\n";

	// Other attributes
    $parameters = array('object' => $object);
    $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
    print $hookmanager->resPrint;
    if (empty($reshook))
    {
		print $object->showOptionals($extrafields, 'edit');
    }

    print "</table>\n";

    dol_fiche_end();

    print '<div class="center">';
    print '<input class="button" value="'.$langs->trans("CreateGroup").'" type="submit">';
    print ' &nbsp; ';
    print '<input class="button" value="'.$langs->trans("Cancel").'" name="cancel" type="submit">';
    print '</div>';

    print "</form>";
}


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
    if ($id)
    {
        $head = group_prepare_head($object);
        $title = $langs->trans("Group");

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id, $langs->trans("DeleteAGroup"), $langs->trans("ConfirmDeleteGroup", $object->name), "confirm_delete", '', 0, 1);
		}

		/*
		 * Fiche en mode visu
		 */

		if ($action != 'edit')
		{
			dol_fiche_head($head, 'group', $title, -1, 'group');

			$linkback = '<a href="'.DOL_URL_ROOT.'/user/group/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

			dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

			print '<div class="fichecenter">';
			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent tableforfield">';

            // Name (already in dol_banner, we keep it to have the GlobalGroup picto, but we should move it in dol_banner)
            if (!empty($conf->mutlicompany->enabled))
            {
    			print '<tr><td class="titlefield">'.$langs->trans("Name").'</td>';
    			print '<td class="valeur">'.dol_escape_htmltag($object->name);
    			if (empty($object->entity))
    			{
    				print img_picto($langs->trans("GlobalGroup"), 'redstar');
    			}
    			print "</td></tr>\n";
            }

			// Multicompany
			if (!empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && !$user->entity)
			{
				$mc->getInfo($object->entity);
				print "<tr>".'<td class="titlefield">'.$langs->trans("Entity").'</td>';
				print '<td class="valeur">'.dol_escape_htmltag($mc->label);
				print "</td></tr>\n";
			}

			// Note
			print '<tr><td class="titlefield tdtop">'.$langs->trans("Description").'</td>';
			print '<td class="valeur sensiblehtmlcontent"">';
			print dol_string_onlythesehtmltags(dol_htmlentitiesbr($object->note));
			print '</td>';
			print "</tr>\n";

			// Other attributes
            $parameters = array('colspan' => ' colspan="2"');
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

			print "</table>\n";
            print '</div>';

			dol_fiche_end();


			/*
			 * Barre d'actions
			 */
			print '<div class="tabsAction">';

			if ($caneditperms)
			{
				print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
			}

			if ($candisableperms)
			{
				print '<a class="butActionDelete" href="'.$_SERVER['PHP_SELF'].'?action=delete&amp;id='.$object->id.'">'.$langs->trans("DeleteGroup").'</a>';
			}

			print "</div>\n";

            // List users in group

            print load_fiche_titre($langs->trans("ListOfUsersInGroup"), '', '');

            // On selectionne les users qui ne sont pas deja dans le groupe
            $exclude = array();

			if (!empty($object->members))
			{
				foreach ($object->members as $useringroup)
				{
					$exclude[] = $useringroup->id;
				}
			}

			// Other form for add user to group
			$parameters = array('caneditperms' => $caneditperms, 'exclude' => $exclude);
			$reshook = $hookmanager->executeHooks('formAddUserToGroup', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			if (empty($reshook))
			{
				if ($caneditperms)
				{
					print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
					print '<input type="hidden" name="token" value="'.newToken().'">';
					print '<input type="hidden" name="action" value="adduser">';
					print '<table class="noborder centpercent">'."\n";
					print '<tr class="liste_titre"><td class="titlefield liste_titre">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
					print '<td class="liste_titre">';
					print $form->select_dolusers('', 'user', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'maxwidth300');
					print ' &nbsp; ';
					print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
					print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
					print '</td></tr>'."\n";
					print '</table></form>'."\n";
					print '<br>';
				}

				/*
				 * Group members
				 */
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';
				print '<td class="liste_titre">'.$langs->trans("Login").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
				print '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
				print '<td class="liste_titre center" width="5">'.$langs->trans("Status").'</td>';
				print '<td class="liste_titre right" width="5">&nbsp;</td>';
				print "</tr>\n";

				if (!empty($object->members))
				{
					foreach ($object->members as $useringroup)
					{
						print '<tr class="oddeven">';
						print '<td>';
						print $useringroup->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
						if ($useringroup->admin && !$useringroup->entity) {
                            print img_picto($langs->trans("SuperAdministrator"), 'redstar');
                        } elseif ($useringroup->admin) {
                            print img_picto($langs->trans("Administrator"), 'star');
                        }
						print '</td>';
						print '<td>'.$useringroup->lastname.'</td>';
						print '<td>'.$useringroup->firstname.'</td>';
						print '<td class="center">'.$useringroup->getLibStatut(3).'</td>';
						print '<td class="right">';
						if (!empty($user->admin)) {
							print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeuser&amp;user='.$useringroup->id.'">';
							print img_picto($langs->trans("RemoveFromGroup"), 'unlink');
							print '</a>';
						}
						else
						{
							print "-";
						}
						print "</td></tr>\n";
					}
				}
				else
				{
					print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
				}
				print "</table>";
			}

			print "<br>";

			print '<div class="fichecenter"><div class="fichehalfleft">';

			/*
	         * Documents generes
	         */
	        $filename = dol_sanitizeFileName($object->ref);
	        $filedir = $conf->usergroup->dir_output."/".dol_sanitizeFileName($object->ref);
	        $urlsource = $_SERVER["PHP_SELF"]."?id=".$object->id;
	        $genallowed = $user->rights->user->user->creer;
	        $delallowed = $user->rights->user->user->supprimer;

	        $somethingshown = $formfile->showdocuments('usergroup', $filename, $filedir, $urlsource, $genallowed, $delallowed, $object->modelpdf, 1, 0, 0, 28, 0, '', 0, '', $soc->default_lang);

	        // Show links to link elements
	        $linktoelem = $form->showLinkToObjectBlock($object, null, null);
	        $somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

	        print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// List of actions on element
			/*include_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';
			$formactions = new FormActions($db);
			$somethingshown = $formactions->showactions($object, 'usergroup', $socid, 1);*/


	        print '</div></div></div>';
        }

        /*
         * Fiche en mode edition
         */
        if ($action == 'edit' && $caneditperms)
        {
            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post" name="updategroup" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.newToken().'">';
            print '<input type="hidden" name="action" value="update">';

            dol_fiche_head($head, 'group', $title, 0, 'group');

            print '<table class="border centpercent">';
            print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Name").'</td>';
            print '<td class="valeur"><input class="minwidth300" type="text" name="group" value="'.dol_escape_htmltag($object->name).'">';
            print "</td></tr>\n";

            // Multicompany
            if (!empty($conf->multicompany->enabled) && is_object($mc))
            {
                if (empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE) && $conf->entity == 1 && $user->admin && !$user->entity)
                {
                    print "<tr>".'<td class="tdtop">'.$langs->trans("Entity").'</td>';
                    print "<td>".$mc->select_entities($object->entity);
                    print "</td></tr>\n";
                }
                else
                {
					print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
				}
            }

            print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
            print '<td class="valeur">';
            require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
            $doleditor = new DolEditor('note', $object->note, '', 240, 'dolibarr_notes', '', true, false, $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_8, '90%');
            $doleditor->Create();
            print '</td>';
            print "</tr>\n";
			// Other attributes
            $parameters = array();
            $reshook = $hookmanager->executeHooks('formObjectOptions', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
            print $hookmanager->resPrint;
            if (empty($reshook))
            {
				print $object->showOptionals($extrafields, 'edit');
            }

            print "</table>\n";

            dol_fiche_end();

            print '<div class="center"><input class="button" value="'.$langs->trans("Save").'" type="submit"></div>';

            print '</form>';
        }
    }
}

// End of page
llxFooter();
$db->close();
