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
 * Actions
 */

if ($action == 'setlib' && $user->rights->tax->charges->creer)
{
    $object->fetch($id);
    $result = $object->setValueFrom('libelle', GETPOST('lib'), '', '', 'text', '', $user, 'TAX_MODIFY');
    if ($result < 0)
        setEventMessages($object->error, $object->errors, 'errors');
}

/*
 * View
 */
$title = $langs->trans("SocialContribution") . ' - ' . $langs->trans("Info");
$help_url = 'EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("",$title,$help_url);

$object = new ChargeSociales($db);
$object->fetch($id);
$object->info($id);

$head = tax_prepare_head($object);

dol_fiche_head($head, 'info', $langs->trans("SocialContribution"), 0, 'bill');

$morehtmlref='<div class="refidno">';
// Label of social contribution
$morehtmlref.=$form->editfieldkey("Label", 'lib', $object->lib, $object, $user->rights->tax->charges->creer, 'string', '', 0, 1);
$morehtmlref.=$form->editfieldval("Label", 'lib', $object->lib, $object, $user->rights->tax->charges->creer, 'string', '', null, null, '', 1);
$morehtmlref.='</div>';

$linkback = '<a href="' . DOL_URL_ROOT . '/compta/sociales/index.php">' . $langs->trans("BackToList") . '</a>';

$object->totalpaye = $totalpaye;   // To give a chance to dol_banner_tab to use already paid amount to show correct status

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

llxFooter();

$db->close();
