<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 */

/**
    \file       htdocs/user/group/fiche.php
    \brief      Onglet groupes utilisateurs
    \version    $Revision$
*/


require("./pre.inc.php");

$langs->load("users");

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];


/**
 *  Action suppression groupe
 */
if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes")
{
  $editgroup = new Usergroup($db, $_GET["id"]);
  $editgroup->fetch($_GET["id"]);
  $editgroup->delete();
  Header("Location: index.php");
}

/**
 *  Action ajout groupe
 */
if ($_POST["action"] == 'add' && $user->admin)
{
    $message="";
    if (! $_POST["nom"]) {
        $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
        $action="create";       // Go back to create page
    }

    if (! $message) {
        $editgroup = new UserGroup($db,0);

        $editgroup->nom    = trim($_POST["nom"]);
        $editgroup->note   = trim($_POST["note"]);

        $db->begin();

        $id = $editgroup->create();

        if ($id > 0)
        {
            $db->commit();

            Header("Location: fiche.php?id=".$editgroup->id);
        }
        else
        {
            $db->rollback();

            $message='<div class="error">'.$langs->trans("ErrorGroupAlreadyExists",$editgroup->nom).'</div>';
            $action="create";       // Go back to create page
        }
    }
}

if ($_POST["action"] == 'adduser' && $user->admin)
{
    if ($_POST["user"])
    {
        $edituser = new User($db, $_POST["user"]);
        $edituser->SetInGroup($_GET["id"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
    }
}

if ($_GET["action"] == 'removeuser' && $user->admin)
{
    if ($_GET["user"])
    {
        $edituser = new User($db, $_GET["user"]);
        $edituser->RemoveFromGroup($_GET["id"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
    }
}

llxHeader();


/* ************************************************************************** */
/*                                                                            */
/* Affichage fiche en mode création                                           */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{
    print_titre($langs->trans("NewGroup"));
    print "<br>";

    if ($message) { print $message."<br>"; }

    print '<form action="fiche.php" method="post">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>".'<td valign="top">'.$langs->trans("Name").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="nom" value=""></td></tr>';

    print "<tr>".'<td valign="top">'.$langs->trans("Note").'</td><td>';
    print "<textarea name=\"note\" rows=\"12\" cols=\"40\">";
    print "</textarea></td></tr>\n";

    print "<tr>".'<td align="center" colspan="2"><input value="'.$langs->trans("CreateGroup").'" type="submit"></td></tr>';
    print "</form>";
    print "</table>\n";
}


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
    if ($_GET["id"])
    {
        $group = new UserGroup($db);
        $group->fetch($_GET["id"]);

        /*
         * Affichage onglets
         */

        $h = 0;

        $head[$h][0] = DOL_URL_ROOT.'/user/group/fiche.php?id='.$group->id;
        $head[$h][1] = $langs->trans("GroupCard");
        $hselected=$h;
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/user/group/perms.php?id='.$group->id;
        $head[$h][1] = $langs->trans("GroupRights");
        $h++;

        dolibarr_fiche_head($head, $hselected, $langs->trans("Group").": ".$group->nom);


        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$group->id",$langs->trans("DeleteAGroup"),$langs->trans("ConfirmDeleteGroup",$group->name),"confirm_delete");
        }


        /*
         * Fiche en mode visu
         */

        print '<table class="border" width="100%">';
        print '<tr><td width="25%" valign="top">'.$langs->trans("Name").'</td>';
        print '<td width="75%" class="valeur">'.$group->nom.'</td>';
        print "</tr>\n";
        print '<tr><td width="25%" valign="top">'.$langs->trans("Note").'</td>';
        print '<td class="valeur">'.nl2br($group->note).'&nbsp;</td>';
        print "</tr>\n";
        print "</table>\n";
        print "<br>\n";


        print '</div>';

        if ($message) { print $message; }

        /*
         * Barre d'actions
         *
         */
        print '<div class="tabsAction">';

        if ($user->admin)
        {
            print '<a class="tabAction" href="fiche.php?id='.$group->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
        }

        if ($user->id <> $_GET["id"] && $user->admin)
        {
            print '<a class="butDelete" href="fiche.php?action=delete&amp;id='.$group->id.'">'.$langs->trans("DeleteGroup").'</a>';
        }

        print "</div>\n";
        print "<br>\n";

        print_titre($langs->trans("ListOfUsersInGroup"));
        print "<br>\n";
        
        // On sélectionne les users qui ne sont pas déjà dans le groupe
        $uss = array();

        $sql = "SELECT u.rowid, u.name, u.firstname, u.code ";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u ";
        #      $sql .= " LEFT JOIN llx_usergroup_user ug ON u.rowid = ug.fk_user";
        #      $sql .= " WHERE ug.fk_usergroup IS NULL";
        $sql .= " ORDER BY u.name";

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows();
            $i = 0;

            while ($i < $num)
            {
                $obj = $db->fetch_object();

                $uss[$obj->rowid] = ucfirst(stripslashes($obj->firstname)) . " ".ucfirst(stripslashes($obj->name));
                $i++;
            }
        }
        else {
            dolibarr_print_error($db);
        }

        if ($user->admin)
        {
            $form = new Form($db);
            print '<form action="fiche.php?id='.$group->id.'" method="post">'."\n";
            print '<input type="hidden" name="action" value="adduser">';
            print '<table class="noborder" width="100%">'."\n";
            //	  print '<tr class="liste_titre"><td width="25%">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
            print '<tr class="liste_titre"><td width="25%">'.$langs->trans("Users").'</td>'."\n";
            print '<td>';
            print $form->select_array("user",$uss);
            print ' &nbsp; ';
            print '<input type="submit" class=button value="'.$langs->trans("Add").'">';
            print '</td></tr>'."\n";
            print '</table></form>'."\n";
        }

        /*
         * Membres du groupe
         */
        $sql = "SELECT u.rowid, u.login, u.name, u.firstname, u.code ";
        $sql .= " FROM ".MAIN_DB_PREFIX."user as u";
        $sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
        $sql .= " WHERE ug.fk_user = u.rowid";
        $sql .= " AND ug.fk_usergroup = ".$group->id;
        $sql .= " ORDER BY u.name";

        $result = $db->query($sql);
        if ($result)
        {
            $num = $db->num_rows($result);
            $i = 0;

            print '<br>';

            print '<table class="noborder" width="100%">';
            print '<tr class="liste_titre">';
            print '<td width="25%">'.$langs->trans("Login").'</td>';
            print '<td width="25%">'.$langs->trans("Lastname").'</td>';
            print '<td width="25%">'.$langs->trans("Firstname").'</td>';
            print '<td>'.$langs->trans("Code").'</td>';
            print "<td>&nbsp;</td></tr>\n";
            $var=True;
            while ($i < $num)
            {
                $obj = $db->fetch_object($result);
                $var=!$var;

                print "<tr $bc[$var]>";
                print '<td>';
                print '<a href="'.DOL_URL_ROOT.'/user/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowUser"),"user").' '.$obj->login.'</a>';
                print '</td>';
                print '<td>'.ucfirst(stripslashes($obj->name)).'</td>';
                print '<td>'.ucfirst(stripslashes($obj->firstname)).'</td>';
                print '<td>'.$obj->code.'</td><td>';

                if ($user->admin)
                {

                    print '<a href="fiche.php?id='.$group->id.'&amp;action=removeuser&amp;user='.$obj->rowid.'">';
                    print img_delete($langs->trans("RemoveFromGroup"));
                }
                else
                {
                    print "-";
                }
                print "</td></tr>\n";
                $i++;
            }
            print "</table>";
            print "<br>";
            $db->free($result);
        }

    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
