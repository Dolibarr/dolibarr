<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/admin/index.php
 *		\brief      Page d'accueil de l'espace administration/configuration
 */

require '../main.inc.php';

$langs->load("admin");
$langs->load("companies");

if (!$user->admin) accessforbidden();

$mesg='';


/*
 * View
 */

$wikihelp='EN:First_setup|FR:Premiers_paramétrages|ES:Primeras_configuraciones';
llxHeader('',$langs->trans("Setup"),$wikihelp);

$form = new Form($db);


print_fiche_titre($langs->trans("SetupArea"),'','setup');

if ($mesg) print $mesg.'<br>';

print $langs->trans("SetupDescription1").' ';
print $langs->trans("AreaForAdminOnly").' ';


//print "<br>";
//print "<br>";
print $langs->trans("SetupDescription2")."<br><br>";

print "<br>";
print "<br>";
//print '<hr style="color: #DDDDDD;">';
print img_picto('','puce').' '.$langs->trans("SetupDescription3")."<br>";
print '<br>';
print "<br>";
//print '<hr style="color: #DDDDDD;">';
print img_picto('','puce').' '.$langs->trans("SetupDescription4")."<br>";
print "<br>";
print '<br>';
//print '<hr style="color: #DDDDDD;">';
print $langs->trans("SetupDescription5")."<br>";
//print '<hr style="color: #DDDDDD;">';
print "<br>";

/*
print '<table width="100%">';
print '<tr '.$bc[false].'><td '.$bc[false].'>'.img_picto('','puce').' '.$langs->trans("SetupDescription3")."</td></tr>";
print '<tr '.$bc[true].'><td '.$bc[true].'>'.img_picto('','puce').' '.$langs->trans("SetupDescription4")."</td></tr>";
print '<tr '.$bc[false].'><td '.$bc[false].'>'.img_picto('','puce').' '.$langs->trans("SetupDescription5")."</td></tr>";
print '</table>';
*/

//print '<br>';
//print info_admin($langs->trans("OnceSetupFinishedCreateUsers")).'<br>';


$db->close();

llxFooter();
?>
