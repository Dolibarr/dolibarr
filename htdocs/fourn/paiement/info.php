<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013		Marcos García		<marcosgdf@gmail.com>
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
 *   	\file       htdocs/fourn/paiement/info.php
 *		\ingroup    facture
 *		\brief      Onglet info d'un paiement fournisseur
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/paiementfourn.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

$langs->loadLangs(array("bills", "suppliers", "companies"));

$id			= GETPOST('id', 'int');

$object = new PaiementFourn($db);
$object->fetch($id);
$object->info($id);


/*
 * View
 */

llxHeader();

$head = payment_supplier_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("SupplierPayment"), 0, 'payment');

dol_banner_tab($object, 'id', $linkback, -1, 'rowid', 'ref');

dol_fiche_end();

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

// End of page
llxFooter();
$db->close();
