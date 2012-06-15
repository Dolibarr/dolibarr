<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/user/clicktodial.php
 *       \brief      Page for Click to dial datas
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/usergroups.lib.php");

$langs->load("users");
$langs->load("admin");

$action=GETPOST('action','alpha');
$id=GETPOST('id','int');

// Security check
$socid=0;
if ($user->societe_id > 0) $socid = $user->societe_id;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');
if ($user->id == $id)	// A user can always read its own card
{
	$feature2='';
}
$result = restrictedArea($user, $feature, $id, '&user', $feature2);


/*
 * Actions
 */

if ($action == 'update' && ! $_POST['cancel'])
{
	$edituser = new User($db);
	$edituser->fetch($id);

	$edituser->clicktodial_login    = $_POST["login"];
	$edituser->clicktodial_password = $_POST["password"];
	$edituser->clicktodial_poste    = $_POST["poste"];

	$result=$edituser->update_clicktodial();
}



/*
 * View
 */

$form = new Form($db);

llxHeader("","ClickToDial");


if ($id)
{
    $fuser = new User($db);
    $fuser->fetch($id);
    $fuser->fetch_clicktodial();


	/*
	 * Affichage onglets
	 */
	$head = user_prepare_head($fuser);

	$title = $langs->trans("User");
	dol_fiche_head($head, 'clicktodial', $title, 0, 'user');

    /*
     * Fiche en mode visu
     */

    print '<table class="border" width="100%">';

    // Ref
    print '<tr><td width="25%" valign="top">'.$langs->trans("Ref").'</td>';
    print '<td colspan="2">';
	print $form->showrefnav($fuser,'id','',$user->rights->user->user->lire || $user->admin);
	print '</td>';
	print '</tr>';

    // Name
    print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
    print '<td colspan="2">'.$fuser->lastname.'</td>';
    print "</tr>\n";

    // Prenom
    print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
    print '<td colspan="2">'.$fuser->name.'</td>';
    print "</tr>\n";

    print "</table>\n";
    print "<br>\n";


    if ($action == 'edit')
    {
        print '<form action="clicktodial.php?id='.$_GET["id"].'" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<table class="border" width="100%">';

        if ($user->admin)
        {
        	print '<tr><td width="25%" valign="top">ClickToDial URL</td>';
        	print '<td class="valeur">';
            if (empty($conf->global->CLICKTODIAL_URL))
            {
                $langs->load("errors");
                print '<font class="error">'.$langs->trans("ErrorModuleSetupNotComplete").'</font>';
            }
            else print $form->textwithpicto($conf->global->CLICKTODIAL_URL,$langs->trans("ClickToDialUrlDesc"));
        	print '</td>';
        	print '</tr>';
        }

        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">';
        print '<input name="login" value="'.$fuser->clicktodial_login.'"></td>';
		print '</tr>';

        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">';
        print '<input name="password" value="'.$fuser->clicktodial_password.'"></td>';
        print "</tr>\n";

        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">';
        print '<input name="poste" value="'.$fuser->clicktodial_poste.'"></td>';
        print "</tr>\n";

		print '<tr><td colspan="2" align="center"><input class="button" type="submit" value="'.$langs->trans("Save").'">';
		print ' &nbsp; &nbsp; ';
		print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
		print '</td></tr>';

        print '</table></form>';
    }
    else
    {

        print '<table class="border" width="100%">';

        if ($user->admin)
        {
        	print "<tr>".'<td width="25%" valign="top">ClickToDial URL</td>';
        	print '<td class="valeur">';
        	if (empty($conf->global->CLICKTODIAL_URL))
        	{
        	    $langs->load("errors");
        	    print '<font class="error">'.$langs->trans("ErrorModuleSetupNotComplete").'</font>';
        	}
        	else print $form->textwithpicto($conf->global->CLICKTODIAL_URL,$langs->trans("ClickToDialUrlDesc"));
        	print '</td>';
        	print '</tr>';
        }
        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">'.$fuser->clicktodial_login.'</td>';
        print '</tr>';
        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">'.preg_replace('/./','*',$fuser->clicktodial_password).'</a></td>';
        print "</tr>\n";
        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">'.$fuser->clicktodial_poste.'</td>';
        print "</tr></table>\n";
    }

    print "</div>\n";

    /*
     * Barre d'actions
     */
    print '<div class="tabsAction">';

    if ($user->admin && $action <> 'edit')
    {
        print '<a class="butAction" href="clicktodial.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
    }

    print "</div>\n";
    print "<br>\n";

}


llxFooter();

$db->close();
?>
