<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
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
        \file       htdocs/user/fiche.php
        \brief      Onglet user et permissions de la fiche utilisateur
        \version    $Revision$
*/


require("./pre.inc.php");

$langs->load("users");


$form = new Form($db);

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];


/**
 * Actions
 */
if ($_GET["subaction"] == 'addrights' && $user->admin)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->addrights($_GET["rights"]);
}

if ($_GET["subaction"] == 'delrights' && $user->admin)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->delrights($_GET["rights"]);
}

if ($_POST["action"] == 'confirm_disable' && $_POST["confirm"] == "yes")
{
    if ($_GET["id"] <> $user->id)
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->fetch($_GET["id"]);
        $edituser->disable();
        Header("Location: index.php");
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes")
{
    if ($_GET["id"] <> $user->id)
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->fetch($_GET["id"]);
        $edituser->delete();
        Header("Location: index.php");
    }
}

/**
 *  Action ajout user
 */
if ($_POST["action"] == 'add' && $user->admin)
{
    $message="";
    if (! $_POST["nom"]) {
        $message='<div class="error">'.$langs->trans("NameNotDefined").'</div>';
        $action="create";       // Go back to create page
    }
    if (! $_POST["login"]) {
        $message='<div class="error">'.$langs->trans("LoginNotDefined").'</div>';
        $action="create";       // Go back to create page
    }

    if (! $message)
    {
        $edituser = new User($db,0);

        $edituser->nom           = trim($_POST["nom"]);
        $edituser->note          = trim($_POST["note"]);
        $edituser->prenom        = trim($_POST["prenom"]);
        $edituser->login         = trim($_POST["login"]);
        $edituser->email         = trim($_POST["email"]);
        $edituser->admin         = trim($_POST["admin"]);
        $edituser->webcal_login  = trim($_POST["webcal_login"]);

        $db->begin();

        $id = $edituser->create();

        if ($id > 0)
        {
            if (isset($_POST['password']) && trim($_POST['password']))
            {
                $edituser->password($user,trim($_POST['password']),$conf->password_encrypted);
            }

            $db->commit();

            Header("Location: fiche.php?id=$id");
        }
        else
        {
            $db->rollback();

            $message='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$edituser->login).'</div>';
            $action="create";       // Go back to create page
        }

    }
}

if ($_POST["action"] == 'addgroup' && $user->admin)
{
    if ($_POST["group"])
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->SetInGroup($_POST["group"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
    }
}

if ($_GET["action"] == 'removegroup' && $user->admin)
{
    if ($_GET["group"])
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->RemoveFromGroup($_GET["group"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
    }
}

if ($_POST["action"] == 'update' && $user->admin)
{
    $message="";

    $db->begin();

    $edituser = new User($db, $_GET["id"]);
    $edituser->fetch();

    $edituser->nom           = $_POST["nom"];
    $edituser->note          = $_POST["note"];
    $edituser->prenom        = $_POST["prenom"];
    $edituser->login         = $_POST["login"];
    $edituser->email         = $_POST["email"];
    $edituser->admin         = $_POST["admin"];
    $edituser->webcal_login  = $_POST["webcal_login"];

    $ret=$edituser->update();
    if ($ret < 0)
    {
        $message.='<div class="error">'.$edituser->error.'</div>';
    }
    if ($ret >= 0 && isset($_POST["password"]) && $_POST["password"] !='' )
    {
        $ret=$edituser->password($user,$password,$conf->password_encrypted);
        if ($ret < 0) {
            $message.='<div class="error">'.$edituser->error.'</div>';
        }
    }

    if ($_FILES['photo']['tmp_name']) {
        // Si une photo est fournie avec le formulaire
        if (! is_dir($conf->users->dir_output))
        {
            create_exdir($conf->users->dir_output);
        }
        if (is_dir($conf->users->dir_output)) {
            $newfile=$conf->users->dir_output . "/" . $edituser->id . ".jpg";
            if (! doliMoveFileUpload($_FILES['photo']['tmp_name'],$newfile))
            {
                $message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
            }
        }
    }

    if ($ret >= 0) {
        $message.='<div class="ok">'.$langs->trans("UserModified").'</div>';
        $db->commit();
    } else {
        $db->rollback;
    }

}

if (($_GET["action"] == 'password' || $_GET["action"] == 'passwordsend') && $user->admin)
{
    $edituser = new User($db, $_GET["id"]);
    $edituser->fetch();

    $newpassword=$edituser->password($user,'',$conf->password_encrypted);
    if ($newpassword < 0)
    {
        // Echec
        $message = '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
    }
    else 
    {
        // Succes
        if ($_GET["action"] == 'passwordsend')
        {
            if ($edituser->send_password($user,$newpassword) > 0)
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedAndSentTo",$edituser->email).'</div>';
                //$message.=$newpassword;
            }
            else
            {
                $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
                $message.= '<div class="error">'.$edituser->error.'</div>';
            }
        }
        else
        {
            $message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
        }
    }
}


llxHeader('',$langs->trans("UserCard"));

if ($action == 'create')
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Affichage fiche en mode création                                           */
    /*                                                                            */
    /* ************************************************************************** */

    print_titre($langs->trans("NewUser"));

    print "<br>";
    if ($message) { print $message."<br>"; }

    print '<form action="fiche.php" method="post" name="createuser">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>".'<td valign="top">'.$langs->trans("Lastname").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="nom" value=""></td></tr>';

    print '<tr><td valign="top" width="20%">'.$langs->trans("Firstname").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="prenom" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Login").'</td>';
    print '<td class="valeur"><input size="20" type="text" name="login" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="password" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("EMail").'</td>';
    print '<td class="valeur"><input size="40" type="text" name="email" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Administrator").'</td>';
    print '<td class="valeur">';
    $form->selectyesnonum('admin',0);
    print "</td></tr>\n";

    print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
    print "<textarea name=\"note\" rows=\"12\" cols=\"40\">";
    print "</textarea></td></tr>\n";

    // Autres caractéristiques issus des autres modules
    if ($conf->webcal->enabled)
    {
        print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
        print '<td class="valeur"><input size="30" type="text" name="webcal_login" value=""></td></tr>';
    }

    print "<tr>".'<td align="center" colspan="2"><input value="'.$langs->trans("CreateUser").'" type="submit"></td></tr>';
    print "</form>";
    print "</table>\n";
}
else
{
    /* ************************************************************************** */
    /*                                                                            */
    /* Visu et edition                                                            */
    /*                                                                            */
    /* ************************************************************************** */

    if ($_GET["id"])
    {
        $fuser = new User($db, $_GET["id"]);
        $fuser->fetch();
        $fuser->getrights();

        /*
         * Affichage onglets
         */

        $h = 0;

        $head[$h][0] = DOL_URL_ROOT.'/user/fiche.php?id='.$fuser->id;
        $head[$h][1] = $langs->trans("UserCard");
        $hselected=$h;
        $h++;

        $head[$h][0] = DOL_URL_ROOT.'/user/perms.php?id='.$fuser->id;
        $head[$h][1] = $langs->trans("UserRights");
        $h++;

        if ($conf->bookmark4u->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT.'/user/addon.php?id='.$fuser->id;
            $head[$h][1] = $langs->trans("Bookmark4u");
            $h++;
        }

        if ($conf->clicktodial->enabled)
        {
            $head[$h][0] = DOL_URL_ROOT.'/user/clicktodial.php?id='.$fuser->id;
            $head[$h][1] = $langs->trans("ClickToDial");
            $h++;
        }

        dolibarr_fiche_head($head, $hselected, $langs->trans("User").": ".$fuser->fullname);


        /*
         * Confirmation désactivation
         */
        if ($action == 'disable')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$fuser->login),"confirm_disable");
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$fuser->login),"confirm_delete");
        }


        /*
         * Fiche en mode visu
         */
        if ($_GET["action"] != 'edit')
        {
            print '<table class="border" width="100%">';

            print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td width="50%" class="valeur">'.$fuser->nom.'</td>';
            print '<td align="center" valign="middle" width="25%" rowspan="8">';
            if (file_exists($conf->users->dir_output."/".$fuser->id.".jpg"))
            {
                print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&file='.$fuser->id.'.jpg">';
            }
            else
            {
                print '<img src="'.DOL_URL_ROOT.'/theme/nophoto.jpg">';
            }
            print '</td></tr>';

            print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td width="50%" class="valeur">'.$fuser->prenom.'</td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("Login").'</td>';
            print '<td width="50%" class="valeur">'.$fuser->login.'</td></tr>';

            print '<tr><td width="25%" valign="top">'.$langs->trans("EMail").'</td>';
            print '<td width="50%" class="valeur"><a href="mailto:'.$fuser->email.'">'.$fuser->email.'</a></td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("Administrator").'</td>';
            print '<td class="valeur">'.yn($fuser->admin).'</td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("DateCreation").'</td>';
            print '<td class="valeur">'.dolibarr_print_date($fuser->datec).'</td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("DateModification").'</td>';
            print '<td class="valeur">'.dolibarr_print_date($fuser->datem).'</td>';
            print "</tr>\n";

            print "<tr>".'<td width="25%" valign="top">'.$langs->trans("ContactCard").'</td>';
            print '<td class="valeur">';
            if ($fuser->contact_id)
            {
                print '<a href="../contact/fiche.php?id='.$fuser->contact_id.'">'.$langs->trans("ContactCard").'</a>';
            }
            else
            {
                print $langs->trans("ThisUserIsNot");
            }
            print '</td>';
            print "</tr>\n";

            if ($fuser->societe_id > 0)
            {
                $societe = new Societe($db);
                $societe->fetch($fuser->societe_id);
                print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Company").'</td>';
                print '<td colspan="2">'.$societe->nom.'&nbsp;</td>';
                print "</tr>\n";
            }

            print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Note").'</td>';
            print '<td colspan="2" class="valeur">'.nl2br($fuser->note).'&nbsp;</td>';
            print "</tr>\n";

            // Autres caractéristiques issus des autres modules
            if ($conf->webcal->enabled)
            {
                $langs->load("other");
                print '<tr><td width="25%" valign="top">'.$langs->trans("LoginWebcal").'</td>';
                print '<td colspan="2">'.$fuser->webcal_login.'&nbsp;</td>';
                print "</tr>\n";
            }

            print "</table>\n";
            print "<br>\n";

            print "</div>\n";

            if ($message) { print $message; }

            /*
             * Barre d'actions
             */
            print '<div class="tabsAction">';

            if ($user->admin)
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
            }

            if ($user->id == $_GET["id"] or $user->admin)
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a>';
            }

            if ($user->id == $_GET["id"] or $user->admin && $fuser->email)
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a>';
            }

            if ($user->id <> $_GET["id"] && $user->admin)
            {
                print '<a class="butActionDelete" href="fiche.php?action=disable&amp;id='.$fuser->id.'">'.$langs->trans("DisableUser").'</a>';
            }

            if ($user->id <> $_GET["id"] && $user->admin)
            {
                print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$fuser->id.'">'.$langs->trans("DeleteUser").'</a>';
            }

            print "</div>\n";
            print "<br>\n";



            /*
             * Liste des groupes dans lequel est l'utilisateur
             */

            print_titre($langs->trans("ListOfGroupsForUser"));
            print "<br>\n";

            // On sélectionne les groups
            $uss = array();

            $sql = "SELECT ug.rowid, ug.nom ";
            $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as ug ";
            #      $sql .= " LEFT JOIN llx_usergroup_user ug ON u.rowid = ug.fk_user";
            #      $sql .= " WHERE ug.fk_usergroup IS NULL";
            $sql .= " ORDER BY ug.nom";

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows();
                $i = 0;

                while ($i < $num)
                {
                    $obj = $db->fetch_object();

                    $uss[$obj->rowid] = $obj->nom;
                    $i++;
                }
            }
            else {
                dolibarr_print_error($db);
            }

            if ($user->admin)
            {
                $form = new Form($db);
                print '<form action="fiche.php?id='.$_GET["id"].'" method="post">'."\n";
                print '<input type="hidden" name="action" value="addgroup">';
                print '<table class="noborder" width="100%">'."\n";
                //	  print '<tr class="liste_titre"><td width="25%">'.$langs->trans("NonAffectedUsers").'</td>'."\n";
                print '<tr class="liste_titre"><td width="25%">'.$langs->trans("GroupsToAdd").'</td>'."\n";
                print '<td>';
                print $form->select_array("group",$uss);
                print ' &nbsp; ';
                print '<input type="submit" class=button value="'.$langs->trans("Add").'">';
                print '</td></tr>'."\n";
                print '</table></form>'."\n";
            }

            /*
             * Groupes affectés
             */
            $sql = "SELECT g.rowid, g.nom ";
            $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g";
            $sql .= ",".MAIN_DB_PREFIX."usergroup_user as ug";
            $sql .= " WHERE ug.fk_usergroup = g.rowid";
            $sql .= " AND ug.fk_user = ".$_GET["id"];
            $sql .= " ORDER BY g.nom";

            $result = $db->query($sql);
            if ($result)
            {
                $num = $db->num_rows($result);
                $i = 0;

                print '<br>';

                print '<table class="noborder" width="100%">';
                print '<tr class="liste_titre">';
                print '<td width="25%">'.$langs->trans("Group").'</td>';
                print "<td>&nbsp;</td></tr>\n";

                if ($num) {
                    $var=True;
                    while ($i < $num)
                    {
                        $obj = $db->fetch_object($result);
                        $var=!$var;

                        print "<tr $bc[$var]>";
                        print '<td>';
                        print '<a href="'.DOL_URL_ROOT.'/user/group/fiche.php?id='.$obj->rowid.'">'.img_object($langs->trans("ShowGroup"),"group").' '.$obj->nom.'</a>';
                        print '</td>';
                        print '<td>';

                        if ($user->admin)
                        {

                            print '<a href="fiche.php?id='.$_GET["id"].'&amp;action=removegroup&amp;group='.$obj->rowid.'">';
                            print img_delete($langs->trans("RemoveFromGroup"));
                        }
                        else
                        {
                            print "-";
                        }
                        print "</td></tr>\n";
                        $i++;
                    }
                }
                else
                {
                    print '<tr><td colspan=2>'.$langs->trans("None").'</td></tr>';
                }
                print "</table>";
                print "<br>";
                $db->free($result);
            }
            else {
                dolibarr_print_error($db);
            }

        }

        /*
         * Fiche en mode edition
         */
        if ($_GET["action"] == 'edit' && $user->admin)
        {

            print '<form action="fiche.php?id='.$fuser->id.'" method="post" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="action" value="update">';
            print '<table width="100%" class="border">';

            print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td width="50%" class="valeur"><input size="30" type="text" name="nom" value="'.$fuser->nom.'"></td>';
            print '<td align="center" valign="middle" width="25%" rowspan="6">';
            if (file_exists($conf->users->dir_output."/".$fuser->id.".jpg"))
            {
                print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&file='.$fuser->id.'.jpg">';
            }
            else
            {
                print '<img src="'.DOL_URL_ROOT.'/theme/nophoto.jpg">';
            }
            print '<br><br><table class="noborder"><tr><td>'.$langs->trans("PhotoFile").'</td></tr><tr><td><input type="file" name="photo" class="flat"></td></tr></table>';
            print '</td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td><input size="30" type="text" name="prenom" value="'.$fuser->prenom.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("Login").'</td>';
            print '<td><input size="12" maxlength="8" type="text" name="login" value="'.$fuser->login.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("EMail").'</td>';
            print '<td><input size="30" type="text" name="email" value="'.$fuser->email.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("Administrator").'</td>';
            if ($fuser->societe_id > 0)
            {
                print '<td class="valeur">';
                print '<input type="hidden" name="admin" value="0">'.$langs->trans("No");
                print '</td></tr>';
            }
            else
            {
                print '<td class="valeur">';
                $form->selectyesnonum('admin',$fuser->admin);
                print '</td></tr>';
            }

            print "<tr>".'<td valign="top">'.$langs->trans("Note").'</td><td>';
            print '<textarea name="note" rows="10" cols="40">';
            print $fuser->note;
            print "</textarea></td></tr>";

            // Autres caractéristiques issus des autres modules
            $langs->load("other");
            print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
            print '<td class="valeur" colspan="2"><input size="30" type="text" name="webcal_login" value="'.$fuser->webcal_login.'"></td></tr>';

            print '<tr><td align="center" colspan="3"><input value="'.$langs->trans("Save").'" type="submit"></td></tr>';

            print '</table><br>';
            print '</form>';
        }

    }
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
