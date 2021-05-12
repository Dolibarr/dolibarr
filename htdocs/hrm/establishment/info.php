<?php
<<<<<<< HEAD
/* Copyright (C) 2015      Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
=======
/* Copyright (C) 2015      Alexandre Spangaro <aspangaro@open-dsi.fr>
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
 *  \file       	htdocs/hrm/establishment/info.php
 *  \brief      	Page to show info of an establishment
 */

<<<<<<< HEAD
require('../../main.inc.php');
=======
require '../../main.inc.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/establishment.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'hrm'));

// Security check
if (! $user->admin) accessforbidden();

<<<<<<< HEAD
$id = GETPOST('id','int');
=======
$id = GETPOST('id', 'int');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// View
llxHeader();

if ($id)
{
	$object = new Establishment($db);
	$object->fetch($id);
	$object->info($id);

	$head = establishment_prepare_head($object);

	dol_fiche_head($head, 'info', $langs->trans("Establishment"), -1, 'building');

    print '<table width="100%"><tr><td>';
    dol_print_object_info($object);
    print '</td></tr></table>';

    print '</div>';
}

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
