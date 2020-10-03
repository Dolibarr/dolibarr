<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
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
 *    	\file       htdocs/admin/tools/index.php
 * 		\brief      Page d'accueil de l'espace outils admin
 */

require '../../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "admin"));

if (!$user->admin)
	accessforbidden();


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("AdminTools");
//if (GETPOST('leftmenu',"aZ09") == 'admintools') $title=$langs->trans("ModulesSystemTools");

llxHeader('', $title);

print load_fiche_titre($title, '', 'title_setup');

print $langs->trans("SystemToolsAreaDesc").'<br>';
print "<br>";

print info_admin($langs->trans("SystemAreaForAdminOnly")).'<br>';

print '<br><br>';


// Show logo
//print '<div class="center"><div class="logo_setup"></div></div>';
print '<center><div class="logo_setup"></div></center>'; // For a reason I don't know, the div class="center does not works, we must keep the <center>

// End of page
llxFooter();
$db->close();
