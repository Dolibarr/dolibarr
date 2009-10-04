<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/compta/facture/info.php
 *      \ingroup    facture
 *		\brief      Page des informations d'une facture
 *		\version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/facture.class.php");
require_once(DOL_DOCUMENT_ROOT.'/discount.class.php');
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/invoice.lib.php');

$langs->load("bills");


/*
 * View
 */

llxHeader();

$fac = new Facture($db);
$fac->fetch($_GET["facid"]);
$fac->info($_GET["facid"]);

$soc = new Societe($db, $fac->socid);
$soc->fetch($fac->socid);

$head = facture_prepare_head($fac);
dol_fiche_head($head, 'info', $langs->trans("InvoiceCustomer"), 0, 'bill');


print '<table width="100%"><tr><td>';
dol_print_object_info($fac);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
