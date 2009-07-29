<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/product/stock/info.php
 *	\ingroup    stock
 *	\brief      Page des informations d'un entrepot
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/stock.lib.php");

$langs->load("stocks");

/*
 * View
 */

llxHeader();

$entrepot = new Entrepot($db);
$entrepot->fetch($_GET["id"]);
$entrepot->info($_GET["id"]);

$head = stock_prepare_head($entrepot);

dol_fiche_head($head, 'info', $langs->trans("Warehouse"), 0, 'stock');


print '<table width="100%"><tr><td>';
dol_print_object_info($entrepot);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
