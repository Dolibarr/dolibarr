<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *       \file       htdocs/core/tools.php
 *       \brief      Home page for top menu tools
 */

require '../main.inc.php';

<<<<<<< HEAD
$langs->load("companies");
$langs->load("other");
=======
// Load translation files required by the page
$langs->loadLangs(array("companies","other"));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;



/*
 * View
 */

$socstatic=new Societe($db);

<<<<<<< HEAD
llxHeader("",$langs->trans("Tools"),"");
=======
llxHeader("", $langs->trans("Tools"), "");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

$text=$langs->trans("Tools");

print load_fiche_titre($text);

// Show description of content
print '<div class="justify">'.$langs->trans("ToolsDesc").'</div><br><br>';


// Show logo
print '<div class="center"><div class="logo_setup"></div></div>';


llxFooter();

$db->close();
