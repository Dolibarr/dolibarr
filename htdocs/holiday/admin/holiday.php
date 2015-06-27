<?php
/* Copyright (C) 2012-2015 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011	   Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012	   Regis Houssin		<regis.houssin@capnetworks.com>
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
 * 	Page module configuration paid holiday.
 *
 *  \file       holiday.php
 *	\ingroup    holiday
 *	\brief      Page module configuration paid holiday.
 */

require '../../main.inc.php';
require DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT. '/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT. '/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT. '/user/class/usergroup.class.php';

$action=GETPOST('action');
$optName=GETPOST('optName');
$optValue=GETPOST('optValue');

$langs->load("admin");
$langs->load("holiday");

// Si pas administrateur
if (! $user->admin) accessforbidden();


/*
 * View
 */

// Vérification si module activé
if (empty($conf->holiday->enabled)) print $langs->trans('NotActiveModCP');

llxheader('',$langs->trans('TitleAdminCP'));

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans('ConfCP'), $linkback, 'title_hrm.png');

$cp = new Holiday($db);

print '<br>'.$langs->trans("GoIntoDictionaryHolidayTypes").'<br><br>';

$var=!$var;
print '<div class="info">'.$langs->trans('LastUpdateCP').': '."\n";
if ($cp->getConfCP('lastUpdate')) print '<strong>'.dol_print_date($db->jdate($cp->getConfCP('lastUpdate')),'dayhour','tzuser').'</strong>';
else print $langs->trans('None');
print "</div><br>\n";


// Fin de page
llxFooter();

if (is_object($db)) $db->close();