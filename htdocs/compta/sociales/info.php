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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
if (!empty($conf->projet->enabled))
{
	include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	include_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array('compta', 'bills'));

$id = GETPOST('id', 'int');
$action = GETPOST('action', 'aZ09');

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'tax', $id, 'chargesociales', 'charges');

$object = new ChargeSociales($db);


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

$form = new Form($db);

if (!empty($conf->projet->enabled)) { $formproject = new FormProjets($db); }

$title = $langs->trans("SocialContribution").' - '.$langs->trans("Info");
$help_url = 'EN:Module_Taxes_and_social_contributions|FR:Module Taxes et dividendes|ES:M&oacute;dulo Impuestos y cargas sociales (IVA, impuestos)';
llxHeader("", $title, $help_url);

$object->fetch($id);
$object->info($id);

$head = tax_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("SocialContribution"), -1, 'bill');

$morehtmlref = '<div class="refidno">';
// Label of social contribution
$morehtmlref .= $form->editfieldkey("Label", 'lib', $object->label, $object, $user->rights->tax->charges->creer, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("Label", 'lib', $object->label, $object, $user->rights->tax->charges->creer, 'string', '', null, null, '', 1);
// Project
if (!empty($conf->projet->enabled))
{
	$langs->load("projects");
	$morehtmlref .= '<br>'.$langs->trans('Project').' : ';
	if (!empty($object->fk_project)) {
		$proj = new Project($db);
		$proj->fetch($object->fk_project);
		$morehtmlref .= '<a href="'.DOL_URL_ROOT.'/projet/card.php?id='.$object->fk_project.'" title="'.$langs->trans('ShowProject').'">';
		$morehtmlref .= $proj->ref;
		$morehtmlref .= '</a>';
	} else {
		$morehtmlref .= '';
	}
}
$morehtmlref .= '</div>';

$linkback = '<a href="'.DOL_URL_ROOT.'/compta/sociales/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$object->totalpaye = $totalpaye; // To give a chance to dol_banner_tab to use already paid amount to show correct status

dol_banner_tab($object, 'id', $linkback, 1, 'rowid', 'ref', $morehtmlref, '', 0, '', $morehtmlright);

print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

print '<table width="100%"><tr><td>';
dol_print_object_info($object);
print '</td></tr></table>';

print '</div>';

// End of page
llxFooter();
$db->close();
