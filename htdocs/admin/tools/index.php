<?php
/* Copyright (C) 2001-2004	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *    	\file       htdocs/admin/tools/index.php
 * 		\brief      Page d'accueil de l'espace outils admin
 */

require '../../main.inc.php';

$langs->load("admin");
$langs->load("companies");

if (! $user->admin)
	accessforbidden();


/*
 * View
 */

$title=$langs->trans("SystemToolsArea");
if (GETPOST('leftmenu') == 'modulesadmintools') $title=$langs->trans("ModulesSystemTools");

llxHeader(array(),$title);

$form = new Form($db);

print_fiche_titre($title,'','setup');

print $langs->trans("SystemToolsAreaDesc").'<br>';
print "<br>";

print info_admin($langs->trans("SystemAreaForAdminOnly")).'<br>';

print '<br><br>';


// Show logo
print '<center><div class="logo_setup"></div></center>';

llxFooter();
$db->close();
?>
