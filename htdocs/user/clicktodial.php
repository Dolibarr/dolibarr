<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
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
 *       \file       htdocs/user/clicktodial.php
 *       \brief      Page for Click to dial datas
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

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
$result = restrictedArea($user, 'user', $id, '&user', $feature2);


/*
 * Actions
 */

if ($action == 'update' && ! $_POST['cancel'])
{
	$edituser = new User($db);
	$edituser->fetch($id);

	$edituser->clicktodial_url      = GETPOST("url");
	$edituser->clicktodial_login    = GETPOST("login");
	$edituser->clicktodial_password = GETPOST("password");
	$edituser->clicktodial_poste    = GETPOST("poste");

	$result=$edituser->update_clicktodial();
	if ($result < 0) setEventMessage($edituser->error,'errors');
}



/*
 * View
 */

$form = new Form($db);

llxHeader("","ClickToDial");


if ($id > 0)
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

    // Firstname
    print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
    print '<td colspan="2">'.$fuser->firstname.'</td>';
    print "</tr>\n";

    print "</table>\n";
    print "<br>\n";

	// Edit mode
    if ($action == 'edit')
    {
        print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$fuser->id.'" method="post">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<table class="border" width="100%">';

        if ($user->admin)
        {
        	print '<tr><td width="25%" valign="top">ClickToDial URL</td>';
        	print '<td class="valeur">';
        	print '<input name="url" value="'.(! empty($fuser->clicktodial_url)?$fuser->clicktodial_url:'').'" size="92">';
        	if (empty($conf->global->CLICKTODIAL_URL) && empty($fuser->clicktodial_url))
            {
                $langs->load("errors");
                print '<font class="error">'.$langs->trans("ErrorModuleSetupNotComplete").'</font>';
            }
            else
           {
            	print ' &nbsp; &nbsp; '.$form->textwithpicto($langs->trans("KeepEmptyToUseDefault").': '.$conf->global->CLICKTODIAL_URL,$langs->trans("ClickToDialUrlDesc"));
           }
            print '</td>';
        	print '</tr>';
        }

        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">';
        print '<input name="login" value="'.(! empty($fuser->clicktodial_login)?$fuser->clicktodial_login:'').'"></td>';
		print '</tr>';

        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">';
        print '<input name="password" value="'.(! empty($fuser->clicktodial_password)?$fuser->clicktodial_password:'').'"></td>';
        print "</tr>\n";

        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">';
        print '<input name="poste" value="'.(! empty($fuser->clicktodial_poste)?$fuser->clicktodial_poste:'').'"></td>';
        print "</tr>\n";

        print '</table>';

        print '<br><center><input class="button" type="submit" value="'.$langs->trans("Save").'">';
        print ' &nbsp; &nbsp; ';
        print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</center>';

        print '</form>';
    }
    else	// View mode
    {

        print '<table class="border" width="100%">';

        if (! empty($user->admin))
        {
        	print "<tr>".'<td width="25%" valign="top">ClickToDial URL</td>';
        	print '<td class="valeur">';
        	$url=$conf->global->CLICKTODIAL_URL;
        	if (! empty($fuser->clicktodial_url)) $url=$fuser->clicktodial_url;
        	if (empty($url))
        	{
        	    $langs->load("errors");
        	    print '<font class="error">'.$langs->trans("ErrorModuleSetupNotComplete").'</font>';
        	}
        	else
        	{
        		print $form->textwithpicto((empty($fuser->clicktodial_url)?$langs->trans("DefaultLink").': ':'').$url,$langs->trans("ClickToDialUrlDesc"));
        	}
        	print '</td>';
        	print '</tr>';
        }
        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">'.(! empty($fuser->clicktodial_login)?$fuser->clicktodial_login:'').'</td>';
        print '</tr>';
        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">'.preg_replace('/./','*',(! empty($fuser->clicktodial_password)?$fuser->clicktodial_password:'')).'</a></td>';
        print "</tr>\n";
        print '<tr><td width="25%" valign="top">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">'.(! empty($fuser->clicktodial_poste)?$fuser->clicktodial_poste:'').'</td>';
        print "</tr></table>\n";
    }

    print "</div>\n";

    /*
     * Barre d'actions
     */
    print '<div class="tabsAction">';

    if (! empty($user->admin) && $action <> 'edit')
    {
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
    }

    print "</div>\n";
    print "<br>\n";

}


llxFooter();

$db->close();
?>
