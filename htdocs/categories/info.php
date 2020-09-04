<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *      \file       htdocs/categories/info.php
 *      \ingroup    categories
 *		\brief      Category info page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/categories.lib.php';

if (!$user->rights->categorie->lire) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array('categories', 'sendings'));

$socid = 0;
$id = GETPOST('id', 'int');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'categorie', $id, '&category');

$object = new Categorie($db);
if (!$object->fetch($id) > 0) {
    dol_print_error($db);
    exit;
}
$type = $object->type;
if (is_numeric($type)) $type = Categorie::$MAP_ID_TO_CODE[$type]; // For backward compatibility

/*
 * View
 */

$form = new Form($db);

llxHeader('', $langs->trans('Categories'), '');

//$object->info($object->id);

$title = Categorie::$MAP_TYPE_TITLE_AREA[$type];

$head = categories_prepare_head($object, $type);

dol_fiche_head($head, 'info', $langs->trans($title), -1, 'category');
$backtolist = (GETPOST('backtolist') ? GETPOST('backtolist') : DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type);
$linkback = '<a href="'.$backtolist.'">'.$langs->trans("BackToList").'</a>';
$object->next_prev_filter = ' type = '.$type;
$object->ref = $object->label;
$morehtmlref = '<br><div class="refidno"><a href="'.DOL_URL_ROOT.'/categories/index.php?leftmenu=cat&type='.$type.'">'.$langs->trans("Root").'</a> >> ';
$ways = $object->print_all_ways(" &gt;&gt; ", '', 1);
foreach ($ways as $way) {
    $morehtmlref .= $way."<br>\n";
}
$morehtmlref .= '</div>';

dol_banner_tab($object, 'label', $linkback, ($user->socid ? 0 : 1), 'label', 'label', $morehtmlref, '&type='.$type, 0, '', '', 1);

print '<br>';

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table "border centpercent tableforfield">';

print '<tr><td>';
dol_print_object_info($object);
print '</td></tr>';

print '</table>';

print '</div>';

dol_fiche_end();

// End of page
llxFooter();
$db->close();
