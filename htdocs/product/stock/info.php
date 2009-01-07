<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 \file       htdocs/product/stock/info.php
 \ingroup    facture
 \brief      Page des informations d'un entrepot
 \version    $Id$
 */

require("./pre.inc.php");

$langs->load("stocks");

llxHeader();


/*
 * Visualisation de la fiche
 *
 */

$entrepot = new Entrepot($db);
$entrepot->fetch($_GET["id"]);
$entrepot->info($_GET["id"]);


/*
 * Affichage onglets
 */
$h = 0;

$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id;
$head[$h][1] = $langs->trans("WarehouseCard");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$entrepot->id;
$head[$h][1] = $langs->trans("StockMovements");
$h++;

$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$entrepot->id;
$head[$h][1] = $langs->trans("EnhancedValue");
$h++;

if ($conf->global->STOCK_USE_WAREHOUSE_BY_USER)
{
	// Add the constant STOCK_USE_WAREHOUSE_BY_USER in cont table to use this feature.
	// Should not be enabled by defaut because does not work yet correctly because
	// there is no way to add values in the table llx_user_entrepot
	$head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$entrepot->id;
	$head[$h][1] = $langs->trans("Users");
	$h++;
}

$head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$entrepot->id;
$head[$h][1] = $langs->trans("Info");
$hselected=$h;
$h++;

dolibarr_fiche_head($head, $hselected, $langs->trans("Warehouse"));


print '<table width="100%"><tr><td>';
dol_print_object_info($entrepot);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
