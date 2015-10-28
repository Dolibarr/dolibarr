<?php
/* Copyright (C) 2015 		Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file 	htdocs/hrm/admin/admin_hrm.php
 * \ingroup HRM
 * \brief 	HRM module setup page
 */
require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/hrm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");
$langs->load('hrm');

if (! $user->admin)
	accessforbidden();

$action = GETPOST('action', 'alpha');

/*
 * Actions
 */

/*
 * View
 */
$page_name = "Parameters";
llxHeader('', $langs->trans($page_name));

$form = new Form($db);

dol_htmloutput_mesg($mesg);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("HRMSetup"), $linkback);

// Configuration header
$head = hrm_admin_prepare_head();
dol_fiche_head($head, 'parameters', $langs->trans("HRM"), 0, "user");

llxFooter();
$db->close();
