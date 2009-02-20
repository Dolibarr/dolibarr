<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/user/addon.php
		\brief      Onglet addon de la fiche utilisateur
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("users");

$form = new Form($db);


llxHeader("","Addon Utilisateur");


/* ************************************************************************** */
/*                                                                            */
/*                                                                            */
/* ************************************************************************** */


if ($_GET["id"])
{
    $fuser = new User($db, $_GET["id"]);
    $fuser->fetch();


	/*
	 * Affichage onglets
	 */
	$head = user_prepare_head($fuser);

	dol_fiche_head($head, 'other', $langs->trans("User"));


    /*
    * Fiche en mode visu
    */

    print '<table class="border" width="100%">';

    print '<tr><td width="25%" valign="top">'.$langs->trans("Lastname").'</td>';
    print '<td class="valeur">'.$fuser->nom.'</td></tr>';
    print '<tr><td width="25%" valign="top">'.$langs->trans("Firstname").'</td>';
    print '<td class="valeur">'.$fuser->prenom.'</td>';
    print "</tr>\n";

    print '<tr><td width="25%" valign="top">'.$langs->trans("Login").'</td>';
    print '<td class="valeur">'.$fuser->login.'</td></tr>';
    print '<tr><td width="25%" valign="top">'.$langs->trans("EMail").'</td>';
    print '<td class="valeur"><a href="mailto:'.$fuser->email.'">'.$fuser->email.'</a></td>';
    print "</tr>\n";

	//...
	
    print "</table>\n";

    print "</div>\n";


    /*
    * Barre d'actions
    *
    */
    print '<div class="tabsAction">';

    if ($user->admin)
    {
//        print '<a class="butAction" href="addon.php?id='.$fuser->id.'&amp;action=create_bk4u_login">'.$langs->trans("Créer login Bookmark4u").'</a>';
    }

    print "</div>\n";
    print "<br>\n";

}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
