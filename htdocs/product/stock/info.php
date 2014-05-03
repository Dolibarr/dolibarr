<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/product/stock/info.php
 *	\ingroup    stock
 *	\brief      Page des informations d'un entrepot
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';

$langs->load("stocks");

// Security check
$result=restrictedArea($user,'stock');


/*
 * View
 */

$help_url='EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("",$langs->trans("Stocks"),$help_url);

$entrepot = new Entrepot($db);
$entrepot->fetch($_GET["id"]);
$entrepot->info($_GET["id"]);

$head = stock_prepare_head($entrepot);

dol_fiche_head($head, 'info', $langs->trans("Warehouse"), 0, 'stock');


print '<table width="100%"><tr><td>';
dol_print_object_info($entrepot);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
