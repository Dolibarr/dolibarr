<?php
<<<<<<< HEAD
/* Copyright (C) 2014-2016	Alexandre Spangaro	<aspangaro@zendsi.com>
=======
/* Copyright (C) 2014-2016	Alexandre Spangaro	<aspangaro@open-dsi.fr>
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
<<<<<<< HEAD
 * along with this program. If not, seehttp://www.gnu.org/licenses/>.
=======
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */

/**
 * \file	    htdocs/accountancy/admin/fiscalyear_info.php
<<<<<<< HEAD
 * \ingroup     Advanced accountancy
=======
 * \ingroup     Accountancy (Double entries)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * \brief	    Page to show info of a fiscal year
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/fiscalyear.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/fiscalyear.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin","compta"));

// Security check
if ($user->societe_id > 0)
	accessforbidden();
if (! $user->rights->accounting->fiscalyear)
	accessforbidden();

$id = GETPOST('id', 'int');

// View
$title = $langs->trans("Fiscalyear") . " - " . $langs->trans("Info");
$helpurl = "";
<<<<<<< HEAD
llxHeader("",$title,$helpurl);
=======
llxHeader("", $title, $helpurl);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

if ($id) {
	$object = new Fiscalyear($db);
	$object->fetch($id);
	$object->info($id);

	$head = fiscalyear_prepare_head($object);

	dol_fiche_head($head, 'info', $langs->trans("Fiscalyear"), 0, 'cron');

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
