<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *      \file       htdocs/fourn/facture/info.php
 *      \ingroup    facture, fournisseur
 *		\brief      Page des informations d'une facture fournisseur
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';

$langs->load('bills');

$facid = isset($_GET["facid"])?$_GET["facid"]:'';

// Security check
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'fournisseur', $facid, 'facture_fourn', 'facture');



/*
 * View
 */

llxHeader();

$fac = new FactureFournisseur($db);
$fac->fetch($_GET["facid"]);
$fac->info($_GET["facid"]);
$soc = new Societe($db);
$soc->fetch($fac->socid);

$head = facturefourn_prepare_head($fac);
$titre=$langs->trans('SupplierInvoice');
dol_fiche_head($head, 'info', $langs->trans('SupplierInvoice'), 0, 'bill');

print '<table width="100%"><tr><td>';
dol_print_object_info($fac);
print '</td></tr></table>';

print '</div>';

$db->close();

llxFooter();
?>
