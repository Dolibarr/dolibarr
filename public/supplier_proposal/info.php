<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
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
 *      \file       htdocs/comm/propal/info.php
 *      \ingroup    propal
 *      \brief      Page d'affichage des infos d'une proposition commerciale
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/supplier_proposal/class/supplier_proposal.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/supplier_proposal.lib.php';

$langs->load('supplier_proposal');
$langs->load('compta');

$id=GETPOST('id','int');
$socid=GETPOST('socid','int');

// Security check
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'supplier_proposal', $id);


/*
 *	View
 */

llxHeader('',$langs->trans('CommRequest'),'EN:Ask_Price_Supplier|FR:Demande_de_prix_fournisseur');

$object = new SupplierProposal($db);
$object->fetch($id);
$object->fetch_thirdparty();

$head = supplier_proposal_prepare_head($object);
dol_fiche_head($head, 'info', $langs->trans('CommRequest'), 0, 'supplier_proposal');

$object->info($object->id);

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';


llxFooter();
$db->close();
