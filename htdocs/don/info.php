<?php
/* Copyright (C) 2015       Alexandre Spangaro	  	<alexandre.spangaro@gmail.com>
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
 * 	\file       htdocs/don/info.php
 * 	\ingroup    donations
 * 	\brief      Page to show a donation information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/donation.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';

$langs->load("donations");

// Security check
$id = GETPOST('id','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'don', $id, '');


/*
 * View
 */

llxHeader();

if ($id)
{
	$object = new Don($db);
	$object->fetch($id);
	$object->info($id);
	
	$head = donation_prepare_head($object);
	
	dol_fiche_head($head, 'info', $langs->trans("Donation"), 0, 'bill');

    print '<table width="100%"><tr><td>';
    dol_print_object_info($object);
    print '</td></tr></table>';
      
    print '</div>';
}

$db->close();

llxFooter();
