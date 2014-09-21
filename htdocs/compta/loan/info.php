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
 *	\file       htdocs/compta/sociales/info.php
 *	\ingroup    tax
 *	\brief      Page with info about social contribution
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/tax.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->load("compta");
$langs->load("bills");

$id=GETPOST('id','int');
$action=GETPOST("action");

// Security check
$socid = GETPOST('socid','int');
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'tax', $id, 'chargesociales','charges');


/*
 * View
 */

$help_url='EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("",$langs->trans("SocialContribution"),$help_url);

$chargesociales = new ChargeSociales($db);
$chargesociales->fetch($id);
$chargesociales->info($id);

$head = tax_prepare_head($chargesociales);

dol_fiche_head($head, 'info', $langs->trans("SocialContribution"), 0, 'bill');


print '<table width="100%"><tr><td>';
dol_print_object_info($chargesociales);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
