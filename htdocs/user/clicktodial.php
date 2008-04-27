<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/user/clicktodial.php
        \brief      Page for Click to dial datas
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("users");
$langs->load("admin");

$form = new Form($db);


/*
 * Actions
 */
 
if ($_POST["action"] == 'update' && ! $_POST['cancel'])
{
	$edituser = new User($db, $_GET["id"]);
	
	$edituser->clicktodial_login    = $_POST["login"];
	$edituser->clicktodial_password = $_POST["password"];
	$edituser->clicktodial_poste    = $_POST["poste"];
	
	$result=$edituser->update_clicktodial();
}




llxHeader("","ClickToDial");


if ($_GET["id"])
{
    $fuser = new User($db, $_GET["id"]);
    $fuser->fetch();
    $fuser->fetch_clicktodial();


	/*
	 * Affichage onglets
	 */
	$head = user_prepare_head($fuser);

	dolibarr_fiche_head($head, 'clicktodial', $langs->trans("User"));

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

    // Nom
    print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
    print '<td colspan="2">'.$fuser->nom.'</td>';
    print "</tr>\n";

    // Prenom
    print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
    print '<td colspan="2">'.$fuser->prenom.'</td>';
    print "</tr>\n";
    
    print "</table>\n";
    print "<br>\n";

    print info_admin($langs->trans("ClickToDialUrlDesc"));
    
    if ($_GET["action"] == 'edit')
    {
        print '<form action="clicktodial.php?id='.$_GET["id"].'" method="post">';
        print '<input type="hidden" name="action" value="update">';
        print '<table class="border" width="100%">';
        
        if ($user->admin)
        {
        	print "<tr>".'<td width="25%" valign="top">ClickToDial URL</td>';
        	print '<td class="valeur">'.$conf->global->CLICKTODIAL_URL.'</td>';
        	print '</tr>';
        }
        
        print "<tr>".'<td width="25%" valign="top">ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">';
        print '<input name="login" value="'.$fuser->clicktodial_login.'"></td>';
		print '</tr>';
		
        print "<tr>".'<td width="25%" valign="top">ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">';
        print '<input name="password" value="'.$fuser->clicktodial_password.'"></td>';
        print "</tr>\n";

        print "<tr>".'<td width="25%" valign="top">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
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
        	print '<td class="valeur">'.$conf->global->CLICKTODIAL_URL.'</td>';
        	print '</tr>';
        }
        print "<tr>".'<td width="25%" valign="top">ClickToDial '.$langs->trans("Login").'</td>';
        print '<td class="valeur">'.$fuser->clicktodial_login.'</td>';
        print '</tr>';
        print "<tr>".'<td width="25%" valign="top">ClickToDial '.$langs->trans("Password").'</td>';
        print '<td class="valeur">'.$fuser->clicktodial_password.'</a></td>';
        print "</tr>\n";
        print "<tr>".'<td width="25%" valign="top">ClickToDial '.$langs->trans("IdPhoneCaller").'</td>';
        print '<td class="valeur">'.$fuser->clicktodial_poste.'</td>';
        print "</tr></table>\n";
    }

    print "</div>\n";

    /*
     * Barre d'actions
     */
    print '<div class="tabsAction">';

    if ($user->admin && $_GET["action"] <> 'edit')
    {
        print '<a class="butAction" href="clicktodial.php?id='.$fuser->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
    }

    print "</div>\n";
    print "<br>\n";

}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
