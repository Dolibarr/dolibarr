<?PHP
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

require("./pre.inc.php");

$langs->load("users");


$form = new Form($db);

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];

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

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes")
{
  if ($id <> $user->id)
    {
      $edituser = new User($db, $_GET["id"]);
      $edituser->fetch($_GET["id"]);
      $edituser->delete();
      Header("Location: index.php");
    }
}

if ($_POST["action"] == 'add' && $user->admin)
{
    $edituser = new User($db,0);

    $edituser->nom           = $_POST["nom"];
    $edituser->note          = $_POST["note"];
    $edituser->prenom        = $_POST["prenom"];
    $edituser->login         = $_POST["login"];
    $edituser->email         = $_POST["email"];
    $edituser->admin         = $_POST["admin"];
    $edituser->webcal_login  = $_POST["webcal_login"];

    $id = $edituser->create();

    if ($id) {
        if (isset($_POST['password']) && $_POST['password']!='' )
        {
          $edituser->password($_POST['password'],$conf->password_encrypted);
        }

        Header("Location: fiche.php?id=$id");
    }           
    else {
        //dolibarr_print_error($db,$edituser->error());
        $message=$langs->trans("LoginAlreadyExists");
        $action="create";       // Go back to create page
    }
}

if ($_POST["action"] == 'update' && $user->admin)
{
  $edituser = new User($db, $_GET["id"]);
  $edituser->fetch();

  $edituser->nom           = $_POST["nom"];
  $edituser->note          = $_POST["note"];
  $edituser->prenom        = $_POST["prenom"];
  $edituser->login         = $_POST["login"];
  $edituser->email         = $_POST["email"];
  $edituser->admin         = $_POST["admin"];
  $edituser->webcal_login  = $_POST["webcal_login"];
  
  if (! $edituser->update())
    {
      print $edituser->error();
    }
  if (isset($password) && $password !='' )
    {
      $edituser->password($password,$conf->password_encrypted);
    }
}

if ($_GET["action"] == 'password' && $user->admin)
{
    $edituser = new User($db, $_GET["id"]);
    $edituser->fetch();

    if ($edituser->password('',$conf->password_encrypted))
    {
        $message = "Mot de passe changé et envoyé à $edituser->email";
    }
}


llxHeader();


/* ************************************************************************** */
/*                                                                            */
/* Nouvel utilisateur                                                         */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create')
{

    print_titre($langs->trans("NewUser"));

    if ($message) { print $message."<br>"; }

    print '<form action="fiche.php" method="post">';
    print '<input type="hidden" name="action" value="add">';

    print '<table class="border" width="100%" cellpadding="3" cellspacing="0">';

    print '<tr><td valign="top" width="20%">'.$langs->trans("FirstName").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="prenom" value=""></td></tr>';

    print "<tr>".'<td valign="top">'.$langs->trans("LastName").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="nom" value=""></td></tr>';

    print "<tr>".'<td valign="top">'.$langs->trans("Login").'</td>';
    print '<td class="valeur"><input size="20" type="text" name="login" value=""></td></tr>';

    print "<tr>".'<td valign="top">'.$langs->trans("Password").'</td>';
    print '<td class="valeur"><input size="30" type="text" name="password" value=""></td></tr>';

    print "<tr>".'<td valign="top">'.$langs->trans("EMail").'</td>';
    print '<td class="valeur"><input size="40" type="text" name="email" value=""></td></tr>';

    print "<tr>".'<td valign="top">Admin</td>';
    print '<td class="valeur">';
    $form->selectyesnonum('admin',0);
    print "</td></tr>\n";

    print "<tr>".'<td valign="top">'.$langs->trans("Note").'</td><td>';
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


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
    if ($_GET["id"])
    {
        $fuser = new User($db, $_GET["id"]);
        $fuser->fetch();

        /*
         * Affichage onglets
         */
    
        $h = 0;
    
        $head[$h][0] = DOL_URL_ROOT.'/user/fiche.php?id='.$fuser->id;
        $head[$h][1] = $langs->trans("UserCard");
        if ($_GET["action"] != 'perms') { $hselected=$h; }
        $h++;
    
        if ($user->admin)
        {
            $head[$h][0] = DOL_URL_ROOT.'/user/fiche.php?action=perms&amp;id='.$fuser->id;
            $head[$h][1] = $langs->trans("Permissions");
            if ($_GET["action"] == 'perms') { $hselected=$h; }
            $h++;
        }
    
    
        dolibarr_fiche_head($head, $hselected, $fuser->nom." ".$fuser->prenom);


        /*
         * Confirmation suppression
         */
        if ($action == 'delete')
        {
            $html = new Form($db);
            $html->form_confirm("fiche.php?id=$fuser->id","Désactiver cet utilisateur","Etes-vous sûr de vouloir désactiver cet utilisateur ?","confirm_delete");
        }



        if ($_GET["action"] == 'perms')
        {
            if ($message) { print "$message<br>"; }

            /*
             * Ecran ajout/suppression permission
             */

            print '<table class="noborder" width="100%" border="0" cellpadding="3" cellspacing="0">';


            // Droits existant
            print "<tr>".'<td valign="top" colspan="2">';
            print '<table width="100%" class="noborder" cellpadding="2" cellspacing="0">';
            print '<tr class="liste_titre"><td>'.$langs->trans("AvailableRights").'</td><td>'.$langs->trans("Module").'</td><td>&nbsp</td></tr>';
            $sql = "SELECT r.id, r.libelle, r.module FROM ".MAIN_DB_PREFIX."rights_def as r ORDER BY r.module, r.id ASC";

            if ($db->query($sql))
            {
                $num = $db->num_rows();
                $i = 0;
                $var = True;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($i);
                    if ($oldmod <> $obj->module)
                    {
                        $oldmod = $obj->module;
                        $var = !$var;
                    }
                    print '<tr '. $bc[$var].'>';
                    print '<td>'.$obj->libelle . '</td><td>'.$obj->module . '</td>';
                    print '<td><a href="fiche.php?id='.$fuser->id.'&amp;action=perms&amp;subaction=addrights&amp;rights='.$obj->id.'">'.img_edit_add().'</a></td>';
                    print '</tr>';

                    $i++;
                }
            }
            print '</table>';

            print '</td><td colspan="2" valign="top">';

            // Droits possédés
            print '<table class="noborder" width="100%" cellpadding="2" cellspacing="0">';
            print '<tr class="liste_titre"><td>&nbsp</td><td>'.$langs->trans("OwnedRights").'</td><td>'.$langs->trans("Module").'</td></tr>';
            $sql = "SELECT r.id, r.libelle, r.module FROM ".MAIN_DB_PREFIX."rights_def as r, ".MAIN_DB_PREFIX."user_rights as ur";
            $sql .= " WHERE ur.fk_id = r.id AND ur.fk_user = ".$fuser->id. " ORDER BY r.module, r.id ASC";
            $var = True;
            if ($db->query($sql))
            {
                $num = $db->num_rows();
                $i = 0;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($i);
                    if ($oldmod <> $obj->module)
                    {
                        $oldmod = $obj->module;
                        $var = !$var;
                    }

                    print "<tr $bc[$var]>";
                    print '<td align="right"><a href="fiche.php?id='.$fuser->id.'&amp;action=perms&amp;subaction=delrights&amp;rights='.$obj->id.'">'.img_edit_remove().'</a></td>';
                    print "<td>".$obj->libelle . '</td><td>'.$obj->module . '</td>';
                    print '</tr>';
                    $i++;
                }
            }
            print '</table>';
            print '</td></tr>';
        }


        if ($_GET["action"] != 'perms' && $_GET["action"] != 'edit')
        {
            /*
             * Fiche en mode visu
             */

            print '<table class="border" width="100%" cellpadding="3" cellspacing="0">';

            print "<tr>".'<td width="25%" valign="top">'.$langs->trans("LastName").'</td>';
            print '<td width="25%" class="valeur">'.$fuser->nom.'</td>';
            print '<td width="25%" valign="top">'.$langs->trans("FirstName").'</td>';
            print '<td width="25%" class="valeur">'.$fuser->prenom.'</td>';
            print "</tr>\n";

            print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Login").'</td>';
            print '<td width="25%" class="valeur">'.$fuser->login.'</td>';
            print '<td width="25%" valign="top">'.$langs->trans("EMail").'</td>';
            print '<td width="25%" class="valeur"><a href="mailto:'.$fuser->email.'">'.$fuser->email.'</a></td>';
            print "</tr>\n";
            
            print "<tr>".'<td width="25%" valign="top">Administrateur</td>';
            print '<td colspan="3" class="valeur">'.yn($fuser->admin).'</td>';
            print "</tr>\n";

	    if ($fuser->societe_id > 0)
	      {
		$societe = new Societe($db);
		$societe->fetch($fuser->societe_id);
		print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Company").'</td>';
		print '<td colspan="3">'.$societe->nom.'&nbsp;</td>';
		print "</tr>\n";
	      }

            print "<tr>".'<td width="25%" valign="top">Fiche contact</td>';
            print '<td colspan="3" valign="top">';
            if ($fuser->contact_id)
            {
	      print '<a href="../contact/fiche.php?id='.$fuser->contact_id.'">Fiche contact</a>';
            }
            else
            {
                print "Pas de fiche parmi les Contacts";
            }
            print '</td>';
            print "</tr>\n";

            print "<tr>".'<td width="25%" valign="top">'.$langs->trans("Note").'</td>';
            print '<td colspan="3" class="valeur">'.nl2br($fuser->note).'&nbsp;</td>';
            print "</tr>\n";

            // Autres caractéristiques issus des autres modules
            if ($conf->webcal->enabled)
            {
                print "<tr>".'<td width="25%" valign="top">Webcal Login</td>';
                print '<td colspan="3">'.$fuser->webcal_login.'&nbsp;</td>';
                print "</tr>\n";
            }

            print "</table>\n";
            print "<br>\n";
            
            print "</div>\n";


            /*
             * Barre d'actions
             *
             */
            print '<div class="tabsAction">';

            if ($user->admin)
            {
                print '<a class="tabAction" href="fiche.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Edit").'</a>';
            }

            if ($user->id == $id or $user->admin)
            {
                print '<a class="tabAction" href="fiche.php?id='.$fuser->id.'&amp;action=password">'.$langs->trans("SendNewPassword").'</a>';
            }

            if ($user->admin && $user->id <> $id)
            {
                print '<a class="tabAction" href="fiche.php?action=delete&amp;id='.$fuser->id.'">'.$langs->trans("DisableUser").'</a>';
            }

            print "</div>\n";
            print "<br>\n";


            /*
             * Droits
             */
            print '<table width="100%" class="noborder" cellpadding="0" cellspacing="0">';
            print '<tr class="liste_titre"><td>'.$langs->trans("Permissions").'</td><td>'.$langs->trans("Module").'</td></tr>';
            $sql = "SELECT r.libelle, r.module FROM ".MAIN_DB_PREFIX."rights_def as r, ".MAIN_DB_PREFIX."user_rights as ur";
            $sql .= " WHERE ur.fk_id = r.id AND ur.fk_user = ".$fuser->id. " ORDER BY r.module, r.id ASC";
            $var = True;
            if ($db->query($sql))
            {
                $num = $db->num_rows();
                $i = 0;
                while ($i < $num)
                {
                    $obj = $db->fetch_object($i);
                    if ($oldmod <> $obj->module)
                    {
                        $oldmod = $obj->module;
                        $var = !$var;
                    }

                    print "<tr $bc[$var]><td>".$obj->libelle . '</td><td>'.$obj->module."</td></tr>\n";
                    $i++;
                }
            }
            print "</table>\n";
            print "<br>\n";

        }

        /* ************************************************************************** */
        /*                                                                            */
        /* Edition                                                                    */
        /*                                                                            */
        /* ************************************************************************** */
        if ($_GET["action"] == 'edit' && $user->admin)
        {
            print '<form action="fiche.php?id='.$fuser->id.'" method="post">';
            print '<input type="hidden" name="action" value="update">';
            print '<table wdith="100%" class="border" border="1" cellpadding="3" cellspacing="0">';

            print "<tr>".'<td valign="top">'.$langs->trans("LastName").'</td>';
            print '<td><input size="30" type="text" name="nom" value="'.$fuser->nom.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("FirstName").'</td>';
            print '<td><input size="20" type="text" name="prenom" value="'.$fuser->prenom.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("Login").'</td>';
            print '<td><input size="10" maxlength="8" type="text" name="login" value="'.$fuser->login.'"></td></tr>';

            print "<tr>".'<td valign="top">'.$langs->trans("EMail").'</td>';
            print '<td><input size="30" type="text" name="email" value="'.$fuser->email.'"></td></tr>';

    	    print "<tr>".'<td valign="top">'.$langs->trans("Admin").'</td>';
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
            print "<textarea name=\"note\" rows=\"10\" cols=\"40\">";
            print $fuser->note;
            print "</textarea></td></tr>";

            // Autres caractéristiques issus des autres modules
            print "<tr>".'<td valign="top">'.$langs->trans("LoginWebcal").'</td>';
            print '<td class="valeur"><input size="30" type="text" name="webcal_login" value="'.$fuser->webcal_login.'"></td></tr>';

            print "<tr>".'<td align="center" colspan="2"><input value="Enregistrer" type="submit"></td></tr>';

            print '</table>';
            print '</form>';
        }

    }

}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
