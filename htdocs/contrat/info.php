<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/contrat/info.php
 *      \ingroup    contrat
 *      \brief      Page des informations d'un contrat
 */

require ("../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

$langs->load("contracts");

// Security check
$contratid = GETPOST("id",'int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat',$contratid,'');


/*
* View
*/

llxHeader();

$contrat = new Contrat($db);
$contrat->fetch($contratid);
$contrat->info($contratid);

$head = contract_prepare_head($contrat);

dol_fiche_head($head, 'info', $langs->trans("Contract"), 0, 'contract');


print '<table width="100%"><tr><td>';
dol_print_object_info($contrat);
print '</td></tr></table>';

print '</div>';

llxFooter();
$db->close();
