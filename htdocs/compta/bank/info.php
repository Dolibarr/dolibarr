<?php
/* Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *     \file       htdocs/compta/bank/info.php
 *     \ingroup    banque
 *     \brief      Onglet info d'une ecriture bancaire
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'companies'));

$id = GETPOST("rowid");


/*
 * View
 */

llxHeader();

$object = new AccountLine($db);
$object->fetch($id);
$object->info($id);


$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/compta/bank/line.php?rowid='.$id;
$head[$h][1] = $langs->trans("BankTransaction");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$id;
$head[$h][1] = $langs->trans("Info");
$hselected = $h;
$h++;


dol_fiche_head($head, $hselected, $langs->trans("LineRecord"), -1, 'account');

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';


dol_banner_tab($object, 'rowid', $linkback);

print '<div class="underbanner clearboth"></div>';
print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

// End of page
llxFooter();
$db->close();
