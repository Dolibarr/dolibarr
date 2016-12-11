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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \file       htdocs/compta/bank/info.php
 *     \ingroup    banque
 *     \brief      Onglet info d'une ecriture bancaire
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

$langs->load("banks");
$langs->load("categories");
$langs->load("companies");


/*
 * View
 */

llxHeader();

$line = new AccountLine($db);
$line->fetch($_GET["rowid"]);
$line->info($_GET["rowid"]);


$h=0;

$head[$h][0] = DOL_URL_ROOT.'/compta/bank/ligne.php?rowid='.$_GET["rowid"];
$head[$h][1] = $langs->trans("Card");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/compta/bank/info.php?rowid='.$_GET["rowid"];
$head[$h][1] = $langs->trans("Info");
$hselected = $h;
$h++;


dol_fiche_head($head, $hselected, $langs->trans("LineRecord"),0,'account');

print '<table width="100%"><tr><td>';
dol_print_object_info($line);
print '</td></tr></table>';

print '</div>';

llxFooter();
$db->close();
