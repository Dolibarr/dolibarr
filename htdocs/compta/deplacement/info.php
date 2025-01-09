<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 * 	\file       htdocs/compta/deplacement/info.php
 * 	\ingroup    trip
 * 	\brief      Page to show a trip information
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/trip.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->load("trips");

// Security check
$id = GETPOSTINT('id');
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'deplacement', $id, '');


/*
 * View
 */

llxHeader();

if ($id) {
	$object = new Deplacement($db);
	$object->fetch($id);
	$object->info($id);

	$head = trip_prepare_head($object);

	print dol_get_fiche_head($head, 'info', $langs->trans("TripCard"), 0, 'trip');

	print '<table width="100%"><tr><td>';
	dol_print_object_info($object);
	print '</td></tr></table>';

	print '</div>';
}

// End of page
llxFooter();
$db->close();
