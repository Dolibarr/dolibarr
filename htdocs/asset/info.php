<?php
<<<<<<< HEAD
/* Copyright (C) 2018      Alexandre Spangaro   <aspangaro@zendsi.com>
=======
/* Copyright (C) 2018      Alexandre Spangaro   <aspangaro@open-dsi.fr>
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
<<<<<<< HEAD
 *  \file       info.php
=======
 *  \file       htdocs/asset/info.php
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *  \ingroup    asset
 *  \brief      Page to show an asset information
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/asset.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/asset/class/asset.class.php';

// Load translation files required by the page
$langs->loadLangs(array("asset"));

<<<<<<< HEAD
$id = GETPOST('id','int');
$ref=GETPOST('ref','alpha');
$action=GETPOST('action','alpha');
=======
$id = GETPOST('id', 'int');
$ref=GETPOST('ref', 'alpha');
$action=GETPOST('action', 'alpha');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'asset', $id, '');

$object = new Asset($db);
$object->fetch($id);

/*
 * Actions
 */

/*
 * View
 */
$title = $langs->trans('Asset') . " - " . $langs->trans('Info');
$helpurl = "";
llxHeader('', $title, $helpurl);

$form = new Form($db);

$object->info($id);

<<<<<<< HEAD
$head = AssetsPrepareHead($object);
=======
$head = asset_prepare_head($object);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

dol_fiche_head($head, 'info', $langs->trans("Asset"), -1, 'generic');

$linkback = '<a href="'.DOL_URL_ROOT.'/don/list.php'.(! empty($socid)?'?socid='.$socid:'').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref='<div class="refidno">';
$morehtmlref.='</div>';

dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

dol_fiche_end();

<<<<<<< HEAD
=======
// End of page
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
llxFooter();
$db->close();
