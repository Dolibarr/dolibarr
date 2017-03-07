<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2012	   Florian Henry		<florian.henry@open-concept.pro>
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
 *       \file       htdocs/user/group/card.php
 *       \brief      Onglet groupes utilisateurs
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
if(! empty($conf->multicompany->enabled)) dol_include_once('/multicompany/class/actions_multicompany.class.php');

// Defini si peux lire/modifier utilisateurs et permisssions
$canreadperms=($user->admin || $user->rights->user->user->lire);
$caneditperms=($user->admin || $user->rights->user->user->creer);
$candisableperms=($user->admin || $user->rights->user->user->supprimer);
// Advanced permissions
if (! empty($conf->global->MAIN_USE_ADVANCED_PERMS))
{
    $canreadperms=($user->admin || $user->rights->user->group_advance->read);
    $caneditperms=($user->admin || $user->rights->user->group_advance->write);
    $candisableperms=($user->admin || $user->rights->user->group_advance->delete);
}

$langs->load("users");
$langs->load("other");

$id=GETPOST('id', 'int');
$action=GETPOST('action', 'alpha');
$confirm=GETPOST('confirm', 'alpha');
$userid=GETPOST('user', 'int');

// Security check
$result = restrictedArea($user, 'user', $id, 'usergroup&usergroup', 'user');

if (! empty($conf->multicompany->enabled) && $conf->entity > 1 && $conf->multicompany->transverse_mode)
{
    accessforbidden();
}

$object = new Usergroup($db);

$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);

$hookmanager->initHooks(array('groupcard','globalcard'));

/**
 *  Action remove group
 */
if ($action == 'confirm_delete' && $confirm == "yes")
{
    if ($caneditperms)
    {
        $object->fetch($id);
        $object->delete();
        header("Location: index.php");
        exit;
    }
    else
    {
    	$langs->load("errors");
        setEventMessages($langs->trans('ErrorForbidden'), null, 'errors');
    }
}

/**
 *  Action add group
 */
if ($action == 'add')
{
    if ($caneditperms)
    {
        if (! $_POST["nom"]) {
            setEventMessages($langs->trans("NameNotDefined"), null, 'errors');
            $action="create";       // Go back to create page
        } else {
			$object->nom	= trim($_POST["nom"]);	// For backward compatibility
			$object->name	= trim($_POST["nom"]);
			$object->note	= trim($_POST["note"]);

			// Fill array 'array_options' with data from add form
      		$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
			if ($ret < 0) $error++;

      		if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)) $object->entity = 0;
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
                setEventMessages($langs->trans("ErrorGroupAlreadyExists",$object->name), null, 'errors');
                $action="create";       // Go back to create page
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
if ($action == 'adduser' || $action =='removeuser')
{
    if ($caneditperms)
    {
        if ($userid > 0)
        {
            $object->fetch($id);
			$object->oldcopy = clone $object;

			$edituser = new User($db);
			$edituser->fetch($userid);
			if ($action == 'adduser')    $result=$edituser->SetInGroup($object->id,(! empty($conf->multicompany->transverse_mode)?GETPOST('entity','int'):$object->entity));
			if ($action == 'removeuser') $result=$edituser->RemoveFromGroup($object->id,(! empty($conf->multicompany->transverse_mode)?GETPOST('entity','int'):$object->entity));

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

		$object->name	= trim($_POST["group"]);
		$object->nom	= $object->name;			// For backward compatibility
		$object->note	= dol_htmlcleanlastbr($_POST["note"]);

		// Fill array 'array_options' with data from add form
      	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);
		if ($ret < 0) $error++;

		if (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)) $object->entity = 0;
		else $object->entity = $_POST["entity"];

        $ret=$object->update();

        if ($ret >= 0 && ! count($object->errors))
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



/*
 * View
 */

llxHeader('',$langs->trans("GroupCard"));

$form = new Form($db);
$fuserstatic = new User($db);

if ($action == 'create')
{
    print load_fiche_titre($langs->trans("NewGroup"));

    print dol_set_focus('#nom');

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    dol_fiche_head('', '', '', 0, '');

    print '<table class="border" width="100%">';

	print "<tr>";
	print '<td class="fieldrequired" width="15%">'.$langs->trans("Name").'</td>';
	print '<td class="valeur"><input size="30" type="text" id="nom" name="nom" value=""></td></tr>';

	// Multicompany
	if (! empty($conf->multicompany->enabled) && is_object($mc))
	{
		if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
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
    $doleditor=new DolEditor('note','','',240,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_8,'90%');
    $doleditor->Create();
    print "</td></tr>\n";

	// Other attributes
    $parameters=array('object' => $object, 'colspan' => ' colspan="2"');
    $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
    if (empty($reshook) && ! empty($extrafields->attribute_label))
    {
    	print $object->showOptionals($extrafields,'edit');
    }

    print "</table>\n";

    dol_fiche_end();

    print '<div class="center"><input class="button" value="'.$langs->trans("CreateGroup").'" type="submit"></div>';

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
        $object->fetch($id);

        $head = group_prepare_head($object);
        $title = $langs->trans("Group");

		/*
		 * Confirmation suppression
		 */
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("DeleteAGroup"),$langs->trans("ConfirmDeleteGroup",$object->name),"confirm_delete", '',0,1);
		}

		/*
		 * Fiche en mode visu
		 */

		if ($action != 'edit')
		{
        	dol_fiche_head($head, 'group', $title, 0, 'group');

			print '<table class="border" width="100%">';

			// Ref
			print '<tr><td class="titlefield">'.$langs->trans("Ref").'</td>';
			print '<td>';
			print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
			print '</td>';
			print '</tr>';

			// Name
			print '<tr><td>'.$langs->trans("Name").'</td>';
			print '<td class="valeur">'.$object->name;
			if (empty($object->entity))
			{
				print img_picto($langs->trans("GlobalGroup"),'redstar');
			}
			print "</td></tr>\n";

			// Multicompany
			if (! empty($conf->multicompany->enabled) && is_object($mc) && empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
			{
				$mc->getInfo($object->entity);
				print "<tr>".'<td class="tdtop">'.$langs->trans("Entity").'</td>';
				print '<td class="valeur">'.$mc->label;
				print "</td></tr>\n";
			}

			// Note
			print '<tr><td class="tdtop">'.$langs->trans("Description").'</td>';
			print '<td class="valeur">'.dol_htmlentitiesbr($object->note).'&nbsp;</td>';
			print "</tr>\n";

			// Other attributes
            $parameters=array('colspan' => ' colspan="2"');
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
            	print $object->showOptionals($extrafields);
            }

			print "</table>\n";

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
			print "<br>\n";

            /*
             * Liste des utilisateurs dans le groupe
             */

            print load_fiche_titre($langs->trans("ListOfUsersInGroup"),'','');

            // On selectionne les users qui ne sont pas deja dans le groupe
            $exclude = array();

            if (! empty($object->members))
            {
                if (! (! empty($conf->multicompany->enabled) && ! empty($conf->multicompany->transverse_mode)))
                {
                    foreach($object->members as $useringroup)
                    {
                        $exclude[]=$useringroup->id;
                    }
                }
            }

            if ($caneditperms)
            {
                print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="adduser">';
                print '<table class="noborder" width="100%">'."\n";
                print '<tr class="liste_titre"><td class="titlefield liste_titre">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
                print '<td class="liste_titre">';
                print $form->select_dolusers('', 'user', 1, $exclude, 0, '', '', $object->entity, 0, 0, '', 0, '', 'maxwidth300');
                print ' &nbsp; ';
                // Multicompany
                if (! empty($conf->multicompany->enabled) && is_object($mc))
                {
                    if ($conf->entity == 1 && $conf->multicompany->transverse_mode)
                    {
                        print '</td><td class="tdtop">'.$langs->trans("Entity").'</td>';
                        print "<td>".$mc->select_entities($conf->entity);
                    }
                    else
                    {
                    	print '<input type="hidden" name="entity" value="'.$conf->entity.'" />';
                    }
                }
                else
                {
                	print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
                }
                print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
                print '</td></tr>'."\n";
                print '</table></form>'."\n";
                print '<br>';
            }

            /*
             * Group members
             */
            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td class="liste_titre">'.$langs->trans("Login").'</td>';
            print '<td class="liste_titre">'.$langs->trans("Lastname").'</td>';
            print '<td class="liste_titre">'.$langs->trans("Firstname").'</td>';
			if (! empty($conf->multicompany->enabled) && $conf->entity == 1)
            {
            	print '<td class="liste_titre">'.$langs->trans("Entity").'</td>';
            }
            print '<td class="liste_titre" width="5" align="center">'.$langs->trans("Status").'</td>';
            print '<td class="liste_titre" width="5" align="right">&nbsp;</td>';
            print "</tr>\n";

            if (! empty($object->members))
            {
            	$var=True;

            	foreach($object->members as $useringroup)
            	{
            		$var=!$var;

            		print "<tr ".$bc[$var].">";
            		print '<td>';
            		print $useringroup->getNomUrl(-1, '', 0, 0, 24, 0, 'login');
            		if ($useringroup->admin  && ! $useringroup->entity) print img_picto($langs->trans("SuperAdministrator"),'redstar');
            		else if ($useringroup->admin) print img_picto($langs->trans("Administrator"),'star');
            		print '</td>';
            		print '<td>'.$useringroup->lastname.'</td>';
            		print '<td>'.$useringroup->firstname.'</td>';
            		if (! empty($conf->multicompany->enabled)  && is_object($mc) && ! empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
            		{
            			print '<td class="valeur">';
            			if (! empty($useringroup->usergroup_entity))
            			{
            				$nb=0;
            				foreach($useringroup->usergroup_entity as $group_entity)
            				{
            					$mc->getInfo($group_entity);
            					print ($nb > 0 ? ', ' : '').$mc->label;
            					print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeuser&amp;user='.$useringroup->id.'&amp;entity='.$group_entity.'">';
            					print img_delete($langs->trans("RemoveFromGroup"));
            					print '</a>';
            					$nb++;
            				}
            			}
            			print '</td>';
            		}
            		print '<td align="center">'.$useringroup->getLibStatut(3).'</td>';
            		print '<td align="right">';
            		if (! empty($user->admin) && empty($conf->multicompany->enabled))
            		{
            			print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeuser&amp;user='.$useringroup->id.'">';
            			print img_delete($langs->trans("RemoveFromGroup"));
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
            print "<br>";
        }

        /*
         * Fiche en mode edition
         */
        if ($action == 'edit' && $caneditperms)
        {
            print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post" name="updategroup" enctype="multipart/form-data">';
            print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
            print '<input type="hidden" name="action" value="update">';

            dol_fiche_head($head, 'group', $title, 0, 'group');

            print '<table class="border" width="100%">';
            print '<tr><td class="titlefield fieldrequired">'.$langs->trans("Name").'</td>';
            print '<td class="valeur"><input size="15" type="text" name="group" value="'.$object->name.'">';
            print "</td></tr>\n";

            // Multicompany
            if (! empty($conf->multicompany->enabled) && is_object($mc))
            {
                if (empty($conf->multicompany->transverse_mode) && $conf->entity == 1 && $user->admin && ! $user->entity)
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
            $doleditor=new DolEditor('note',$object->note,'',240,'dolibarr_notes','',true,false,$conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_8,'90%');
            $doleditor->Create();
            print '</td>';
            print "</tr>\n";
        	// Other attributes
            $parameters=array();
            $reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
            if (empty($reshook) && ! empty($extrafields->attribute_label))
            {
            	print $object->showOptionals($extrafields,'edit');
            }

            print "</table>\n";

            dol_fiche_end();

            print '<div class="center"><input class="button" value="'.$langs->trans("Save").'" type="submit"></div>';

            print '</form>';
        }

    }
}

llxFooter();
$db->close();
