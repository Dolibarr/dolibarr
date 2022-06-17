<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2017      Ferran Marcet       	 <fmarcet@2byte.es>
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
 *       \file       htdocs/fourn/commande/info.php
 *       \ingroup    commande
 *       \brief      Fiche commande
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/fourn.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
if (!empty($conf->project->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("suppliers", "orders", "companies", "stocks"));

$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECTS) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECTS));
}
$search_agenda_label = GETPOST('search_agenda_label');

// Security check
$socid = 0;
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'fournisseur', $id, 'commande_fournisseur', 'commande');

if (empty($user->rights->fournisseur->commande->lire)) {
	accessforbidden();
}

$hookmanager->initHooks(array('ordersuppliercardinfo'));



/*
 *	Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$actioncode = '';
	$search_agenda_label = '';
}



/*
 * View
 */

$form = new	Form($db);
$object = new CommandeFournisseur($db);

if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
	$object->info($object->id);
}

$title = $langs->trans("SupplierOrder").' - '.$langs->trans('Info').' - '.$object->ref.' '.$object->name;
if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/projectnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) {
	$title = $object->ref.' '.$object->name.' - '.$langs->trans("Info");
}
$help_url = 'EN:Module_Suppliers_Orders|FR:CommandeFournisseur|ES:Módulo_Pedidos_a_proveedores';
llxHeader('', $title, $help_url);

$now = dol_now();

$head = ordersupplier_prepare_head($object);


print dol_get_fiche_head($head, 'info', $langs->trans("SupplierOrder"), -1, 'order');


// Supplier order card

$linkback = '<a href="'.DOL_URL_ROOT.'/fourn/commande/list.php'.(!empty($socid) ? '?socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

$morehtmlref = '<div class="refidno">';
// Ref supplier
$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', 0, 1);
$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, 0, 'string', '', null, null, '', 1);
// Thirdparty
$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
// Project
if (!empty($conf->project->enabled)) {
	$langs->load("projects");
	$morehtmlref .= '<br>'.$langs->trans('Project').' ';
	if ($user->rights->fournisseur->commande->creer || $user->rights->supplier_order->creer) {
		if ($action != 'classify') {
			//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&token='.newToken().'&id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a> : ';
			$morehtmlref .= ' : ';
		}
		if ($action == 'classify') {
			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
			$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
			$morehtmlref .= '</form>';
		} else {
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
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

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

dol_print_object_info($object, 1);

print '</div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();




// Actions buttons

$out = '';
$permok = $user->rights->agenda->myactions->create;
if ($permok) {
	$out .= '&originid='.$object->id.'&origin=order_supplier';
}


print '<div class="tabsAction">';

if (isModEnabled('agenda')) {
	if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create)) {
		print '<a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out.'&backtopage='.urlencode($_SERVER["PHP_SELF"].'?id='.$object->id).'">'.$langs->trans("AddAction").'</a>';
	} else {
		print '<a class="butActionRefused classfortooltip" href="#">'.$langs->trans("AddAction").'</a>';
	}
}

print '</div>';


if (!empty($object->id)) {
	$param = '&id='.$object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.$contextpage;
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.$limit;
	}

	print load_fiche_titre($langs->trans("ActionsOnOrder"), '', '');

	// List of actions on element
	/*include_once DOL_DOCUMENT_ROOT.'/core/class/html.formactions.class.php';
	$formactions=new FormActions($db);
	$somethingshown = $formactions->showactions($object,'project',0);*/

	// List of todo actions
	//show_actions_todo($conf,$langs,$db,$object,null,0,$actioncode);

	// List of done actions
	//show_actions_done($conf,$langs,$db,$object,null,0,$actioncode, '', $filters, $sortfield, $sortorder);

	// List of all actions
	$filters = array();
	$filters['search_agenda_label'] = $search_agenda_label;
	show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
}

// End of page
llxFooter();
$db->close();
