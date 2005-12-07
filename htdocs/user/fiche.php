<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005      Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2005      Lionel COUSTEIX      <etm_ltd@tiscali.co.uk>
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
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");


// Defini si peux lire/modifier utilisateurs et permisssions
$canreadperms=($user->admin || $user->rights->user->user->lire);
$caneditperms=($user->admin || $user->rights->user->user->creer);
$candisableperms=($user->admin || $user->rights->user->user->supprimer);

if ($user->id <> $_GET["id"])
{
    if (! $canreadperms)
    {
        accessforbidden();
    }
}

$langs->load("users");
$langs->load("companies");


$form = new Form($db);

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];


/**
 * Actions
 */
if ($_GET["subaction"] == 'addrights' && $caneditperms)
{
    $edituser = new User($db,$_GET["id"]);
    $edituser->addrights($_GET["rights"]);
}

if ($_GET["subaction"] == 'delrights' && $caneditperms)
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
        Header("Location: ".DOL_URL_ROOT.'/user/fiche.php?id='.$_GET["id"]);
        exit;
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
        exit;
    }
}

// Action ajout user
if ($_POST["action"] == 'add' && $caneditperms)
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
        $edituser->prenom        = trim($_POST["prenom"]);
        $edituser->login         = trim($_POST["login"]);
        $edituser->admin         = trim($_POST["admin"]);
 		$edituser->office_phone  = trim($_POST["office_phone"]);
 		$edituser->office_fax    = trim($_POST["office_fax"]);
 		$edituser->user_mobile   = trim($_POST["user_mobile"]);
        $edituser->email         = trim($_POST["email"]);
        $edituser->webcal_login  = trim($_POST["webcal_login"]);
        $edituser->note          = trim($_POST["note"]);

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
            exit;
        }
        else
        {
            $db->rollback();

            $message='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$edituser->login).'</div>';
            $action="create";       // Go back to create page
        }

    }
}

// Action ajout groupe utilisateur
if ($_POST["action"] == 'addgroup' && $caneditperms)
{
    if ($_POST["group"])
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->SetInGroup($_POST["group"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
        exit;
    }
}

if ($_GET["action"] == 'removegroup' && $caneditperms)
{
    if ($_GET["group"])
    {
        $edituser = new User($db, $_GET["id"]);
        $edituser->RemoveFromGroup($_GET["group"]);

        Header("Location: fiche.php?id=".$_GET["id"]);
        exit;
    }
}

if ($_POST["action"] == 'update' && $caneditperms)
{
    $message="";

    $db->begin();

    $edituser = new User($db, $_GET["id"]);
    $edituser->fetch();

    $edituser->nom           = $_POST["nom"];
    $edituser->prenom        = $_POST["prenom"];
    $edituser->login         = $_POST["login"];
    $edituser->pass          = $_POST["pass"];
    $edituser->admin         = $_POST["admin"];
    $edituser->office_phone  = $_POST["office_phone"];
 	$edituser->office_fax    = $_POST["office_fax"];
 	$edituser->user_mobile   = $_POST["user_mobile"];
    $edituser->email         = $_POST["email"];
    $edituser->note          = $_POST["note"];
    $edituser->webcal_login  = $_POST["webcal_login"];

    $ret=$edituser->update();
    if ($ret < 0)
    {
        if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
        {
            $message.='<div class="error">'.$langs->trans("ErrorLoginAlreadyExists",$edituser->login).'</div>';
        }
        else
        {
            $message.='<div class="error">'.$edituser->error.'</div>';
        }
    }
    if ($ret >= 0 && isset($_POST["password"]) && $_POST["password"] !='' )
    {
        $ret=$edituser->password($user,$password,$conf->password_encrypted);
        if ($ret < 0)
        {
            $message.='<div class="error">'.$edituser->error.'</div>';
        }
    }

    if (isset($_FILES['photo']['tmp_name']) && trim($_FILES['photo']['tmp_name']))
    {
        // Si une photo est fournie avec le formulaire
        if (! is_dir($conf->users->dir_output))
        {
            create_exdir($conf->users->dir_output);
        }
        if (is_dir($conf->users->dir_output))
        {
            $newfile=$conf->users->dir_output . "/" . $edituser->id . ".jpg";
            if (! doliMoveFileUpload($_FILES['photo']['tmp_name'],$newfile))
            {
                $message .= '<div class="error">'.$langs->trans("ErrorFailedToSaveFile").'</div>';
            }
        }
    }

    if ($ret >= 0)
    {
        $message.='<div class="ok">'.$langs->trans("UserModified").'</div>';
        $db->commit();
    } else
    {
        $db->rollback;
    }

}

// Action modif mot de passe
if ((($_POST["action"] == 'confirm_password' && $_POST["confirm"] == 'yes')
      || $_GET["action"] == 'confirm_passwordsend') && $caneditperms)
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
        if ($_GET["action"] == 'confirm_passwordsend')
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
    if ($message) { print $message.'<br>'; }

    print '<form action="fiche.php" method="post" name="createuser">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%">';

    print "<tr>".'<td valign="top">'.$langs->trans("Lastname").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="nom" value=""></td></tr>';

    print '<tr><td valign="top" width="20%">'.$langs->trans("Firstname").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="prenom" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Login").'</td>';
    print '<td class="valeur"><input size="20" maxsize="24" type="text" name="login" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Password").'</td>';
    print '<td class="valeur"><input size="30" maxsize="32" type="text" name="password" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Administrator").'</td>';
    print '<td class="valeur">';
    $form->selectyesnonum('admin',0);
    print "</td></tr>\n";

    print '<tr><td valign="top">'.$langs->trans("Phone").'</td>';
    print '<td class="valeur"><input size="20" type="text" name="office_phone" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Fax").'</td>';
    print '<td class="valeur"><input size="20" type="text" name="office_fax" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Mobile").'</td>';
    print '<td class="valeur"><input size="20" type="text" name="user_mobile" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("EMail").'</td>';
    print '<td class="valeur"><input size="40" type="text" name="email" value=""></td></tr>';

    print '<tr><td valign="top">'.$langs->trans("Note").'</td><td>';
    print "<textarea name=\"note\" rows=\"6\" cols=\"40\">";
    print "</textarea></td></tr>\n";

    // Autres caractéristiques issus des autres modules
    if ($conf->webcal->enabled)
    {
        print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
        print '<td class="valeur"><input size="30" type="text" name="webcal_login" value=""></td></tr>';
    }

    print "<tr>".'<td align="center" colspan="2"><input class="button" value="'.$langs->trans("CreateUser").'" type="submit"></td></tr>';
    print "</table>\n";
    print "</form>";
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

        $caneditpassword=( (($user->id == $fuser->id) && $user->rights->user->self->password)
                        || (($user->id != $fuser->id) && $user->rights->user->user->password) );

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

        $head[$h][0] = DOL_URL_ROOT.'/user/param_ihm.php?id='.$fuser->id;
        $head[$h][1] = $langs->trans("UserGUISetup");
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
         * Confirmation réinitialisation mot de passe
         */
        if ($action == 'password')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("ReinitPassword"),$langs->trans("ConfirmReinitPassword",$fuser->login),"confirm_password");
            print '<br>';
        }

        /*
         * Confirmation envoi mot de passe
         */
        if ($action == 'passwordsend')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("SendNewPassword"),$langs->trans("ConfirmSendNewPassword",$fuser->login),"confirm_passwordsend");
            print '<br>';
        }

        /*
         * Confirmation désactivation
         */
        if ($action == 'disable')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DisableAUser"),$langs->trans("ConfirmDisableUser",$fuser->login),"confirm_disable");
            print '<br>';
        }

        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id",$langs->trans("DeleteAUser"),$langs->trans("ConfirmDeleteUser",$fuser->login),"confirm_delete");
            print '<br>';
        }


        /*
         * Fiche en mode visu
         */
        if ($_GET["action"] != 'edit')
        {
            print '<table class="border" width="100%">';

            print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td width="50%" class="valeur">'.$fuser->nom.'</td>';
            print '<td align="center" valign="middle" width="25%" rowspan="14">';
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
            if ($fuser->login)
            {
            	print '<td width="50%" class="valeur">'.$fuser->login.'</td></tr>';
            }
            else
            {
            	print '<td width="50%" class="error">'.$langs->trans("LoginAccountDisable").'</td></tr>';
            }

            // Password
            print '<tr><td width="25%" valign="top">'.$langs->trans("Password").'</td>';
            print '<td width="50%" class="valeur">'.eregi_replace('.','*',$fuser->pass).'</td>';
            print "</tr>\n";

            // Administrateur
            print '<tr><td width="25%" valign="top">'.$langs->trans("Administrator").'</td>';
            print '<td class="valeur">'.yn($fuser->admin);
            if ($fuser->admin) print ' '.img_picto($langs->trans("Administrator"),"star");
            print '</td>';
            print "</tr>\n";

            // Source
            print '<tr><td width="25%" valign="top">'.$langs->trans("Source").'</td>';
            print '<td class="valeur">';
            if ($fuser->societe_id)
            {
                print $langs->trans("External");
            }
            else
            {
                print $langs->trans("Internal");
            }
            print '</td></tr>';

            // Company / Contact
            print '<tr><td width="25%" valign="top">'.$langs->trans("Company").' / '.$langs->trans("Contact").'</td>';
            print '<td class="valeur">';
            if ($fuser->societe_id > 0)
            {
                $societe = new Societe($db);
                $societe->fetch($fuser->societe_id);
                print '<a href="'.DOL_URL_ROOT.'/soc.php?socid='.$fuser->societe_id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.dolibarr_trunc($societe->nom,32).'</a>';
                if ($fuser->contact_id)
                {
                    $contact = new Contact($db);
                    $contact->fetch($fuser->contact_id);
                    print ' / '.'<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$fuser->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dolibarr_trunc($contact->fullname,32).'</a>';
                }
            }            
            else
            {
                print $langs->trans("ThisUserIsNot");
            }
            print '</td>';
            print "</tr>\n";

            // Tel, fax, portable
			print '<tr><td width="25%" valign="top">'.$langs->trans("Phone").'</td>';
 			print '<td width="50%" class="valeur">'.$fuser->office_phone.'</td>';
 			print '<tr><td width="25%" valign="top">'.$langs->trans("Fax").'</td>';
 			print '<td width="50%" class="valeur">'.$fuser->office_fax.'</td>';
 			print '<tr><td width="25%" valign="top">'.$langs->trans("Mobile").'</td>';
 			print '<td width="50%" class="valeur">'.$fuser->user_mobile.'</td>';

            print '<tr><td width="25%" valign="top">'.$langs->trans("EMail").'</td>';
            print '<td width="50%" class="valeur"><a href="mailto:'.$fuser->email.'">'.$fuser->email.'</a></td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("DateCreation").'</td>';
            print '<td class="valeur">'.dolibarr_print_date($fuser->datec).'</td>';
            print "</tr>\n";

            print '<tr><td width="25%" valign="top">'.$langs->trans("DateModification").'</td>';
            print '<td class="valeur">'.dolibarr_print_date($fuser->datem).'</td>';
            print "</tr>\n";

            print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Note").'</td>';
            print '<td class="valeur">'.nl2br($fuser->note).'&nbsp;</td>';
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

            print "</div>\n";

            if ($message) { print $message; }

            /*
             * Barre d'actions
             */
            print '<div class="tabsAction">';

            if ($caneditperms || ($user->id == $fuser->id))
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
            }

            if (($user->id != $_GET["id"] && $caneditpassword) && $fuser->login)
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=password">'.$langs->trans("ReinitPassword").'</a>';
            }

            if (($user->id != $_GET["id"] && $caneditpassword) && $fuser->email && $fuser->login)
            {
                print '<a class="butAction" href="fiche.php?id='.$fuser->id.'&amp;action=passwordsend">'.$langs->trans("SendNewPassword").'</a>';
            }

            if ($user->id <> $_GET["id"] && $candisableperms && $fuser->login)
            {
                print '<a class="butActionDelete" href="fiche.php?action=disable&amp;id='.$fuser->id.'">'.$langs->trans("DisableUser").'</a>';
            }

            if ($user->id <> $_GET["id"] && $candisableperms)
            {
                print '<a class="butActionDelete" href="fiche.php?action=delete&amp;id='.$fuser->id.'">'.$langs->trans("DeleteUser").'</a>';
            }

            print "</div>\n";
            print "<br>\n";



            /*
             * Liste des groupes dans lequel est l'utilisateur
             */

            print_fiche_titre($langs->trans("ListOfGroupsForUser"));

            // On sélectionne les groups
            $uss = array();

            $sql = "SELECT ug.rowid, ug.nom ";
            $sql .= " FROM ".MAIN_DB_PREFIX."usergroup as ug ";
            #      $sql .= " LEFT JOIN llx_usergroup_user ug ON u.rowid = ug.fk_user";
            #      $sql .= " WHERE ug.fk_usergroup IS NULL";
            $sql .= " ORDER BY ug.nom";

            $resql = $db->query($sql);
            if ($resql)
            {
                $num = $db->num_rows($resql);
                $i = 0;

                while ($i < $num)
                {
                    $obj = $db->fetch_object($resql);

                    $uss[$obj->rowid] = $obj->nom;
                    $i++;
                }
            }
            else {
                dolibarr_print_error($db);
            }

            if ($caneditperms)
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
                print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
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

                        if ($caneditperms)
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
        if ($_GET["action"] == 'edit' && ($caneditperms || ($user->id == $fuser->id)))
        {

            print '<form action="fiche.php?id='.$fuser->id.'" method="post" name="updateuser" enctype="multipart/form-data">';
            print '<input type="hidden" name="action" value="update">';
            print '<table width="100%" class="border">';
            
            $rowspan=12;

            print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
            print '<td width="50%" class="valeur"><input class="flat" size="30" type="text" name="nom" value="'.$fuser->nom.'"></td>';
            print '<td align="center" valign="middle" width="25%" rowspan="'.$rowspan.'">';
            if (file_exists($conf->users->dir_output."/".$fuser->id.".jpg"))
            {
                print '<img width="100" src="'.DOL_URL_ROOT.'/viewimage.php?modulepart=userphoto&file='.$fuser->id.'.jpg">';
            }
            else
            {
                print '<img src="'.DOL_URL_ROOT.'/theme/nophoto.jpg">';
            }
            print '<br><br><table class="noborder"><tr><td>'.$langs->trans("PhotoFile").'</td></tr><tr><td><input type="file" class="flat" name="photo"></td></tr></table>';
            print '</td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("Firstname").'</td>';
            print '<td><input size="30" type="text" class="flat" name="prenom" value="'.$fuser->prenom.'"></td></tr>';

            // Login
            print "<tr>".'<td valign="top">'.$langs->trans("Login").'</td>';
            print '<td>';
            if ($user->admin) print '<input size="12" maxlength="24" type="text" class="flat" name="login" value="'.$fuser->login.'">';
            else print $fuser->login.'<input type="hidden" name="login" value="'.$fuser->login.'">';
            print '</td></tr>';

            // Pass
            if ($caneditpassword) 
            {
                print "<tr>".'<td valign="top">'.$langs->trans("Password").'</td>';
                print '<td><input size="12" maxlength="32" type="password" class="flat" name="pass" value="'.$fuser->pass.'"></td></tr>';
            }
            else
            {
                print '<tr><td width="25%" valign="top">'.$langs->trans("Password").'</td>';
                print '<td width="50%" class="valeur">'.eregi_replace('.','*',$fuser->pass).'</td>';
                print "</tr>\n";
            }
            
            // Administrateur
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

            // Source
            print '<tr><td width="25%" valign="top">'.$langs->trans("Source").'</td>';
            print '<td class="valeur">';
            if ($fuser->societe_id)
            {
                print $langs->trans("External");
            }
            else
            {
                print $langs->trans("Internal");
            }
            print '</td></tr>';

            // Company / Contact
            print '<tr><td width="25%" valign="top">'.$langs->trans("Company").' / '.$langs->trans("Contact").'</td>';
            print '<td class="valeur">';
            if ($fuser->societe_id > 0)
            {
                $societe = new Societe($db);
                $societe->fetch($fuser->societe_id);
                print '<a href="'.DOL_URL_ROOT.'/soc.php?id='.$fuser->societe_id.'">'.img_object($langs->trans("ShowCompany"),'company').' '.dolibarr_trunc($societe->nom,32).'</a>';
                if ($fuser->contact_id)
                {
                    $contact = new Contact($db);
                    $contact->fetch($fuser->contact_id);
                    print ' / '.'<a href="'.DOL_URL_ROOT.'/contact/fiche.php?id='.$fuser->contact_id.'">'.img_object($langs->trans("ShowContact"),'contact').' '.dolibarr_trunc($contact->fullname,32).'</a>';
                }
            }            
            else
            {
                print $langs->trans("ThisUserIsNot");
            }
            print '</td>';
            print "</tr>\n";

            // Tel, fax, portable
 			print "<tr>".'<td valign="top">'.$langs->trans("Phone").'</td>';
			print '<td><input size="20" type="text" name="office_phone" class="flat" value="'.$fuser->office_phone.'"></td></tr>';
			
			print "<tr>".'<td valign="top">'.$langs->trans("Fax").'</td>';
 			print '<td><input size="20" type="text" name="office_fax" class="flat" value="'.$fuser->office_fax.'"></td></tr>';
			
			print "<tr>".'<td valign="top">'.$langs->trans("Mobile").'</td>';
			print '<td><input size="20" type="text" name="user_mobile" class="flat" value="'.$fuser->user_mobile.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("EMail").'</td>';
            print '<td><input size="40" type="text" name="email" class="flat" value="'.$fuser->email.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("Note").'</td><td>';
            print '<textarea class="flat" name="note" rows="'.ROWS_3.'" cols="70">';
            print $fuser->note;
            print "</textarea></td></tr>";

            // Autres caractéristiques issus des autres modules
            $langs->load("other");
            print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
            print '<td class="valeur" colspan="2"><input size="30" type="text" class="flat" name="webcal_login" value="'.$fuser->webcal_login.'"></td></tr>';
            
            print '<tr><td align="center" colspan="3"><input value="'.$langs->trans("Save").'" class="button" type="submit"></td></tr>';

            print '</table>';
            print '</form>';
        }

        print '</div>';
    }
}

$db->close();

llxFooter('$Date$ - $Revision$');
?>
