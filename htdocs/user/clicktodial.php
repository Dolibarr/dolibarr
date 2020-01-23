<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
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
 *       \file       htdocs/user/clicktodial.php
 *       \brief      Page for Click to dial datas
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';

// Load translation files required by page
$langs->loadLangs(array('users', 'admin'));

$action=GETPOST('action', 'alpha');
$id=GETPOST('id', 'int');

// Security check
$socid=0;
if ($user->socid > 0) $socid = $user->socid;
$feature2 = (($socid && $user->rights->user->self->creer)?'':'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('usercard','globalcard'));

/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook)) {
    if ($action == 'update' && !GETPOST('cancel', 'alpha')) {
        $edituser = new User($db);
        $edituser->fetch($id);

        $edituser->clicktodial_url = GETPOST("url");
        $edituser->clicktodial_login = GETPOST("login");
        $edituser->clicktodial_password = GETPOST("password");
        $edituser->clicktodial_poste = GETPOST("poste");

        $result = $edituser->update_clicktodial();
        if ($result < 0)
        {
            setEventMessages($edituser->error, $edituser->errors, 'errors');
        }
    }
}


/*
 * View
 */

$form = new Form($db);

llxHeader("", "ClickToDial");


if ($id > 0)
{
    $object = new User($db);
    $object->fetch($id, '', '', 1);
    $object->getrights();
    $object->fetch_clicktodial();


	$head = user_prepare_head($object);

	$title = $langs->trans("User");


	print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	dol_fiche_head($head, 'clicktodial', $title, -1, 'user');

	$linkback = '';

	if ($user->rights->user->user->lire || $user->admin) {
		$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	}

    dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin);

    print '<div class="fichecenter">';
    print '<div class="underbanner clearboth"></div>';

    // Edit mode
    if ($action == 'edit')
    {
        print '<table class="border centpercent">';

        if ($user->admin)
        {
            print '<tr><td width="25%" valign="top">ClickToDial URL</td>';
            print '<td class="valeur">';
            print '<input name="url" value="'.(!empty($object->clicktodial_url) ? $object->clicktodial_url : '').'" size="92">';
            if (empty($conf->global->CLICKTODIAL_URL) && empty($object->clicktodial_url))
            {
                $langs->load("errors");
                print '<font class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("ClickToDial")).'</font>';
            }
            else
            {
                print ' &nbsp; &nbsp; '.$form->textwithpicto($langs->trans("KeepEmptyToUseDefault").': '.$conf->global->CLICKTODIAL_URL, $langs->trans("ClickToDialUrlDesc"));
            }
            print '</td>';
            print '</tr>';
        }

        print '<tr><td class="titlefield">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">';
        print '<input name="poste" value="'.(!empty($object->clicktodial_poste) ? $object->clicktodial_poste : '').'"></td>';
        print "</tr>\n";

        print '<tr><td>ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">';
        print '<input name="login" value="'.(!empty($object->clicktodial_login) ? $object->clicktodial_login : '').'"></td>';
        print '</tr>';

        print '<tr><td>ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">';
        print '<input type="password" name="password" value="'.(!empty($object->clicktodial_password) ? $object->clicktodial_password : '').'"></td>';
        print "</tr>\n";

        print '</table>';
    }
    else	// View mode
    {
        print '<table class="border centpercent tableforfield">';

        if (!empty($user->admin))
        {
        	print '<tr><td class="titlefield fieldrequired">ClickToDial URL</td>';
        	print '<td class="valeur">';
        	$url = $conf->global->CLICKTODIAL_URL;
        	if (!empty($object->clicktodial_url)) $url = $object->clicktodial_url;
        	if (empty($url))
        	{
        	    $langs->load("errors");
        	    print '<font class="error">'.$langs->trans("ErrorModuleSetupNotComplete", $langs->transnoentitiesnoconv("ClickToDial")).'</font>';
        	}
        	else
        	{
        		print $form->textwithpicto((empty($object->clicktodial_url) ? $langs->trans("DefaultLink").': ' : '').$url, $langs->trans("ClickToDialUrlDesc"));
        	}
        	print '</td>';
        	print '</tr>';
        }

        print '<tr><td class="titlefield fieldrequired">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">'.(!empty($object->clicktodial_poste) ? $object->clicktodial_poste : '').'</td>';
        print "</tr>";

        print '<tr><td>ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">'.(!empty($object->clicktodial_login) ? $object->clicktodial_login : '').'</td>';
        print '</tr>';

        print '<tr><td>ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">'.preg_replace('/./', '*', (!empty($object->clicktodial_password) ? $object->clicktodial_password : '')).'</a></td>';
        print "</tr>\n";

        print "</table>\n";
    }

    dol_fiche_end();

    if ($action == 'edit')
    {
        print '<div class="center"><input class="button" type="submit" value="'.$langs->trans("Save").'">';
        print '&nbsp;&nbsp;&nbsp;&nbsp&nbsp;';
        print '<input class="button" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
        print '</div>';
    }

    print '</div>';
    print '</form>';

    /*
     * Barre d'actions
     */
    print '<div class="tabsAction">';

    if (!empty($user->admin) && $action <> 'edit')
    {
        print '<a class="butAction" href="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
    }

    print "</div>\n";
}

// End of page
llxFooter();
$db->close();
