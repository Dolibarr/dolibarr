<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
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
 *       \file       htdocs/core/tools.php
 *       \brief      Home page for top menu tools
 */

require '../main.inc.php';

// Load translation files required by the page
$langs->loadLangs(array("companies","other"));

// Security check
$socid=0;
if ($user->socid > 0) $socid=$user->socid;



/*
 * View
 */

$socstatic = new Societe($db);

llxHeader("", $langs->trans("Tools"), "");

$text = $langs->trans("Tools");

print load_fiche_titre($text, '', 'wrench');

// Show description of content
print '<div class="justify">'.$langs->trans("ToolsDesc").'</div><br><br>';


// Show logo
print '<div class="center"><div class="logo_setup"></div></div>';


llxFooter();

$db->close();
