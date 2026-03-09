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
 *	\file       htdocs/product/stock/info.php
 *	\ingroup    stock
 *	\brief      Page des information d'un entrepot
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/stock.lib.php';

// Load translation files required by the page
$langs->load("stocks");

$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');

// Security check
//$result=restrictedArea($user,'stock', $id, 'entrepot&stock');
$result = restrictedArea($user, 'stock');
$usercancreate = $user->hasRight('stock', 'creer');

/*
 * View
 */

$help_url = 'EN:Module_Stocks_En|FR:Module_Stock|ES:M&oacute;dulo_Stocks';
llxHeader("", $langs->trans("Stocks"), $help_url, '', 0, 0, '', '', '', 'mod-product page-stock_info');

$object = new Entrepot($db);
$object->fetch($id, $ref);
$object->info($object->id);

$head = stock_prepare_head($object);

print dol_get_fiche_head($head, 'info', $langs->trans("Warehouse"), -1, 'stock');


$linkback = '<a href="'.DOL_URL_ROOT.'/product/stock/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
$morehtmlref .= $langs->trans("LocationSummary").' : '.$object->lieu;

// Project
if (isModEnabled('project')) {
	$langs->load("projects");
	$morehtmlref .= '<br>'.img_picto('', 'project').' '.$langs->trans('Project').' ';
	if ($usercancreate) {
		if ($action != 'classify') {
			$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> : ';
		}
		if ($action == 'classify') {
			$projectid = $object->fk_project;
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $formproject->select_projects(($socid > 0 ? $socid : -1), $projectid, 'projectid', 0, 0, 1, 1, 0, 0, 0, '', 1, 0, 'maxwidth500');
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, (!empty($object->socid) ? $object->socid : 0), $object->fk_project, 'none', 0, 0, 0, 1, '', 'maxwidth300');
		}
	} else {
		if (!empty($object->fk_project)) {
			$proj = new Project($db);
			$proj->fetch($object->fk_project);
			$morehtmlref .= ' : '.$proj->getNomUrl(1);
			if ($proj->title) {
				$morehtmlref .= ' - '.$proj->title;
			}
		} else {
			$morehtmlref .= '';
		}
	}
}
$morehtmlref .= '</div>';

$shownav = 1;
if ($user->socid && !in_array('stock', explode(',', getDolGlobalString('MAIN_MODULES_FOR_EXTERNAL')))) {
	$shownav = 0;
}

dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref', 'ref', $morehtmlref);


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

print '<br>';

dol_print_object_info($object);

print '</div>';

// End of page
llxFooter();
$db->close();
