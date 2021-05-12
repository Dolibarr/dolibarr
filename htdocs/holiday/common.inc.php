<?php
/* Copyright (C) 2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011	Dimitri Mouillard	<dmouillard@teclib.com>
<<<<<<< HEAD
 * Copyright (C) 2012	Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2012	Regis Houssin		<regis.houssin@inodbox.com>
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
 *   	\file       htdocs/holiday/common.inc.php
 *		\ingroup    holiday
 *		\brief      Common load of data
 */

<<<<<<< HEAD
require_once realpath(dirname(__FILE__)).'/../main.inc.php';
=======
require_once realpath(__DIR__).'/../main.inc.php';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (! class_exists('Holiday')) {
	require_once DOL_DOCUMENT_ROOT. '/holiday/class/holiday.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('user', 'other', 'holiday'));

if (empty($conf->holiday->enabled))
{
<<<<<<< HEAD
    llxHeader('',$langs->trans('CPTitreMenu'));
=======
    llxHeader('', $langs->trans('CPTitreMenu'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    print '<div class="tabBar">';
    print '<span style="color: #FF0000;">'.$langs->trans('NotActiveModCP').'</span>';
    print '</div>';
    llxFooter();
    exit();
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
