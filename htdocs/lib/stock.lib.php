<?php
/* Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * or see http://www.gnu.org/
 */

/**
 *	    \file       htdocs/lib/stock.lib.php
 *		\brief      Library file with function for stock module
 *		\version    $Id$
 */

/**
 * Enter description here...
 *
 * @param unknown_type $contrat
 * @return unknown
 */
function stock_prepare_head($entrepot)
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche.php?id='.$entrepot->id;
	$head[$h][1] = $langs->trans("WarehouseCard");
	$head[$h][2] = 'card';
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/mouvement.php?id='.$entrepot->id;
	$head[$h][1] = $langs->trans("StockMovements");
	$head[$h][2] = 'movements';
	$h++;

	/*
	$head[$h][0] = DOL_URL_ROOT.'/product/stock/fiche-valo.php?id='.$entrepot->id;
	$head[$h][1] = $langs->trans("EnhancedValue");
	$head[$h][2] = 'value';
	$h++;
	*/

	if ($conf->global->STOCK_USE_WAREHOUSE_BY_USER)
	{
		// Add the constant STOCK_USE_WAREHOUSE_BY_USER in cont table to use this feature.
		// Should not be enabled by defaut because does not work yet correctly because
		// there is no way to add values in the table llx_user_entrepot
		$head[$h][0] = DOL_URL_ROOT.'/product/stock/user.php?id='.$entrepot->id;
		$head[$h][1] = $langs->trans("Users");
		$head[$h][2] = 'user';
		$h++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/product/stock/info.php?id='.$entrepot->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;

	return $head;
}

?>