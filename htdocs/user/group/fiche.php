<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \file       htdocs/user/group/fiche.php
 *       \brief      Onglet groupes utilisateurs
 *       \version    $Id: fiche.php,v 1.69 2011/07/31 23:21:25 eldy Exp $
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

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

// Security check
$result = restrictedArea($user, 'user', $_GET["id"], 'usergroup', 'user');

$action=GETPOST("action");
$confirm=GETPOST("confirm");
$userid=GETPOST("user","int");

$object = new Usergroup($db);


/**
 *  Action remove group
 */
if ($action == 'confirm_delete' && $confirm == "yes")
{
    if ($caneditperms)
    {
        $object->fetch($_GET["id"]);
        $object->delete();
        Header("Location: index.php");
        exit;
    }
    else
    {
        $message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
    }
}

/**
 *  Action add group
 */
if ($_POST["action"] == 'add')
{
    if($caneditperms)
    {
        $message="";
        if (! $_POST["nom"])
        {
            $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
            $action="create";       // Go back to create page
        }

        if (! $message)
        {
            $object->nom			= trim($_POST["nom"]);
            $object->globalgroup	= $_POST["globalgroup"];
            $object->note		= trim($_POST["note"]);

            $db->begin();

            $id = $object->create();

            if ($id > 0)
            {
                $db->commit();

                Header("Location: fiche.php?id=".$object->id);
                exit;
            }
            else
            {
                $db->rollback();

                $langs->load("errors");
                $message='<div class="error">'.$langs->trans("ErrorGroupAlreadyExists",$object->nom).'</div>';
                $action="create";       // Go back to create page
            }
        }
    }
    else
    {
        $message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
    }
}

// Add/Remove user into group
if ($action == 'adduser' || $action =='removeuser')
{
    if ($caneditperms)
    {
        if ($userid)
        {
            $object->fetch($_GET["id"]);
            $object->oldcopy=dol_clone($object);

            $edituser = new User($db);
            $edituser->fetch($userid);
            if ($action == 'adduser')    $result=$edituser->SetInGroup($object->id,GETPOST('entity'));
            if ($action == 'removeuser') $result=$edituser->RemoveFromGroup($object->id,GETPOST('entity'));

            if ($result > 0)
            {
                header("Location: fiche.php?id=".$object->id);
                exit;
            }
            else
            {
                $message.=$edituser->error;
            }
        }
    }
    else
    {
        $message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
    }
}


if ($_POST["action"] == 'update')
{
    if($caneditperms)
    {
        $message="";

        $db->begin();

        $object->fetch($_GET["id"]);

        $object->oldcopy=dol_clone($object);

        $object->nom			= trim($_POST["group"]);
        $object->globalgroup	= $_POST["globalgroup"];
        $object->note		= dol_htmlcleanlastbr($_POST["note"]);

        $ret=$object->update();

        if ($ret >= 0 && ! count($object->errors))
        {
            $message.='<div class="ok">'.$langs->trans("GroupModified").'</div>';
            $db->commit();
        }
        else
        {
            $message.='<div class="error">'.$object->error.'</div>';
            $db->rollback();
        }
    }
    else
    {
        $message = '<div class="error">'.$langs->trans('ErrorForbidden').'</div>';
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
    print_fiche_titre($langs->trans("NewGroup"));

    if ($message) { print $message."<br>"; }

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>".'<td valign="top" class="fieldrequired">'.$langs->trans("Name").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="nom" value=""></td></tr>';

    // Global group
    if ($conf->multicompany->enabled)
    {
        if ($conf->entity == 1)
        {
            print "<tr>".'<td valign="top">'.$langs->trans("GlobalGroup").'</td>';
            $checked=(empty($_POST['globalgroup']) ? '' : ' checked');
            print '<td><input type="checkbox" name="globalgroup" value="1"'.$checked.' /></td>';
        }
        else
        {
            print '<input type="hidden" name="globalgroup" value="0" />';
        }
    }

    print "<tr>".'<td valign="top">'.$langs->trans("Note").'</td><td>';
    if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_USER)
    {
        require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
        $doleditor=new DolEditor('note','','',240,'dolibarr_notes','',false);
        $doleditor->Create();
    }
    else
    {
        print '<textarea class="flat" name="note" rows="'.ROWS_8.'" cols="90">';
        print '</textarea>';
    }
    print "</textarea></td></tr>\n";

    print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("CreateGroup").'" type="submit"></td></tr>';
    print "</table>\n";
    print "</form>";
}


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
    if ($_GET["id"] )
    {
        $object->fetch($_GET["id"]);

        /*
         * Affichage onglets
         */
        $head = group_prepare_head($object);
        $title = $langs->trans("Group");
        dol_fiche_head($head, 'group', $title, 0, 'group');

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $ret=$form->form_confirm($_SERVER['PHP_SELF']."?id=".$object->id,$langs->trans("DeleteAGroup"),$langs->trans("ConfirmDeleteGroup",$object->name),"confirm_delete", '',0,1);
            if ($ret == 'html') print '<br>';
        }

        /*
         * Fiche en mode visu
         */

        if ($action != 'edit')
        {
            print '<table class="border" width="100%">';

            // Ref
            print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
            print '<td colspan="2">';
            print $form->showrefnav($object,'id','',$user->rights->user->user->lire || $user->admin);
            print '</td>';
            print '</tr>';

            // Name
            print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
            print '<td width="75%" class="valeur">'.$object->nom;
            if (empty($object->entity))
            {
                print img_redstar($langs->trans("GlobalGroup"));
            }
            print "</td></tr>\n";

            // Note
            print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
            print '<td class="valeur">'.dol_htmlentitiesbr($object->note).'&nbsp;</td>';
            print "</tr>\n";
            print "</table>\n";

            print '</div>';

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


            dol_htmloutput_errors($message);

            /*
             * Liste des utilisateurs dans le groupe
             */

            print_fiche_titre($langs->trans("ListOfUsersInGroup"),'','');

            // On selectionne les users qui ne sont pas deja dans le groupe
            $exclude = array();

            $userslist = $object->listUsersForGroup();

            if (! empty($userslist))
            {
                foreach($userslist as $useringroup)
                {
                    $exclude[]=$useringroup->id;
                }
            }

            if ($caneditperms)
            {
                print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="POST">'."\n";
                print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                print '<input type="hidden" name="action" value="adduser">';
                print '<input type="hidden" name="entity" value="'.$conf->entity.'">';
                print '<table class="noborder" width="100%">'."\n";
                print '<tr class="liste_titre"><td class="liste_titre" width="25%">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
                print '<td>';
                print $form->select_users('','user',1,$exclude);
                print ' &nbsp; ';
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
            print '<td class="liste_titre" width="25%">'.$langs->trans("Login").'</td>';
            print '<td class="liste_titre" width="25%">'.$langs->trans("Lastname").'</td>';
            print '<td class="liste_titre" width="25%">'.$langs->trans("Firstname").'</td>';
            print '<td class="liste_titre" align="right">'.$langs->trans("Status").'</td>';
            print '<td>&nbsp;</td>';
            print "<td>&nbsp;</td>";
            print "</tr>\n";

            if (! empty($userslist))
            {
                $var=True;

                foreach($userslist as $useringroup)
                {
                    $var=!$var;

                    print "<tr $bc[$var]>";
                    print '<td>';
                    print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$useringroup->id.'">'.img_object($langs->trans("ShowUser"),"user").' '.$useringroup->login.'</a>';
                    if ($useringroup->admin  && ! $useringroup->entity) print img_redstar($langs->trans("SuperAdministrator"));
                    else if ($useringroup->admin) print img_picto($langs->trans("Administrator"),'star');
                    print '</td>';
                    print '<td>'.ucfirst(stripslashes($useringroup->lastname)).'</td>';
                    print '<td>'.ucfirst(stripslashes($useringroup->firstname)).'</td>';
                    print '<td align="right">'.$useringroup->getLibStatut(5).'</td>';
                    print '<td>&nbsp;</td>';
                    print '<td align="right">';
                    if ($user->admin)
                    {
                        print '<a href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=removeuser&amp;user='.$useringroup->id.'&amp;entity='.$useringroup->usergroup_entity.'">';
                        print img_delete($langs->trans("RemoveFromGroup"));
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
                print '<tr><td colspan=2>'.$langs->trans("None").'</td></tr>';
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

            print '<table class="border" width="100%">';
            print '<tr><td width="25%" valign="top" class="fieldrequired">'.$langs->trans("Name").'</td>';
            print '<td width="75%" class="valeur"><input size="15" type="text" name="group" value="'.$object->nom.'">';
            print "</td></tr>\n";

            // Global group
            if ($conf->multicompany->enabled)
            {
                if ($conf->entity == 1)
                {
                    print "<tr>".'<td valign="top">'.$langs->trans("GlobalGroup").'</td>';
                    $checked=(empty($object->entity) ? ' checked' : '');
                    print '<td><input type="checkbox" name="globalgroup" value="1"'.$checked.' /></td>';
                }
                else
                {
                    $value=(empty($object->entity) ? 1 : 0);
                    print '<input type="hidden" name="globalgroup" value="'.$value.'" />';
                }
            }

            print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
            print '<td class="valeur">';

            if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_USER)
            {
                require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
                $doleditor=new DolEditor('note',$object->note,'',240,'dolibarr_notes','',true);
                $doleditor->Create();
            }
            else
            {
                print '<textarea class="flat" name="note" rows="'.ROWS_8.'" cols="90">';
                print dol_htmlentitiesbr_decode($object->note);
                print '</textarea>';
            }
            print '</td>';
            print "</tr>\n";
            print '<tr><td align="center" colspan="2"><input class="button" value="'.$langs->trans("Save").'" type="submit"></td></tr>';
            print "</table>\n";
            print '</form>';

            print '</div>';
        }

    }
}

$db->close();

llxFooter('$Date: 2011/07/31 23:21:25 $ - $Revision: 1.69 $');
?>
