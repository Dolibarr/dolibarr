<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       htdocs/contrat/agenda.php
 *      \ingroup    contrat
 *      \brief      Page of contract events
 */

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/contract.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (!empty($conf->projet->enabled)) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("companies", "contracts"));

if (GETPOST('actioncode', 'array'))
{
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) $actioncode = '0';
}
else
{
	$actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

$action		= GETPOST('action', 'alpha');
$confirm	= GETPOST('confirm', 'alpha');
$id			= GETPOST('id', 'int');
$ref		= GETPOST('ref', 'alpha');

// Security check
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'contrat', $id, '');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'a.datep,a.id';
if (!$sortorder) $sortorder = 'DESC,DESC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('agendacontract', 'globalcard'));


/*
 * Actions
 */

$parameters = array('id'=>$id);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if (empty($reshook))
{
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
	{
		$actioncode = '';
		$search_agenda_label = '';
	}
}




/*
 * View
 */

$form = new Form($db);
$formfile = new FormFile($db);
if (!empty($conf->projet->enabled)) $formproject = new FormProjets($db);

if ($id > 0)
{
	// Load object modContract
	$module = (!empty($conf->global->CONTRACT_ADDON) ? $conf->global->CONTRACT_ADDON : 'mod_contract_serpis');
	if (substr($module, 0, 13) == 'mod_contract_' && substr($module, -3) == 'php')
	{
		$module = substr($module, 0, dol_strlen($module) - 4);
	}
	$result = dol_include_once('/core/modules/contract/'.$module.'.php');
	if ($result > 0)
	{
		$modCodeContract = new $module();
	}

	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';

	$object = new Contrat($db);
	$result = $object->fetch($id);
	$object->fetch_thirdparty();

	$title = $langs->trans("Agenda");
	if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/contractrefonly/', $conf->global->MAIN_HTML_TITLE) && $object->ref) $title = $object->ref." - ".$title;
	llxHeader('', $title);

	if (!empty($conf->notification->enabled)) $langs->load("mails");
	$head = contract_prepare_head($object);

	dol_fiche_head($head, 'agenda', $langs->trans("Contract"), -1, 'contract');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '';
	if (!empty($modCodeContract->code_auto)) {
		$morehtmlref .= $object->ref;
	} else {
		$morehtmlref .= $form->editfieldkey("", 'ref', $object->ref, $object, $user->rights->contrat->creer, 'string', '', 0, 3);
		$morehtmlref .= $form->editfieldval("", 'ref', $object->ref, $object, $user->rights->contrat->creer, 'string', '', 0, 2);
	}

	$permtoedit = 0;

	$morehtmlref .= '<div class="refidno">';
	// Ref customer
	$morehtmlref .= $form->editfieldkey("RefCustomer", 'ref_customer', $object->ref_customer, $object, $permtoedit, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefCustomer", 'ref_customer', $object->ref_customer, $object, $permtoedit, 'string', '', null, null, '', 1, 'getFormatedCustomerRef');
	// Ref supplier
	$morehtmlref .= '<br>';
	$morehtmlref .= $form->editfieldkey("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $permtoedit, 'string', '', 0, 1);
	$morehtmlref .= $form->editfieldval("RefSupplier", 'ref_supplier', $object->ref_supplier, $object, $permtoedit, 'string', '', null, null, '', 1, 'getFormatedSupplierRef');
	// Thirdparty
	$morehtmlref .= '<br>'.$langs->trans('ThirdParty').' : '.$object->thirdparty->getNomUrl(1);
	if (empty($conf->global->MAIN_DISABLE_OTHER_LINK) && $object->thirdparty->id > 0) $morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/contrat/list.php?socid='.$object->thirdparty->id.'&search_name='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherContracts").'</a>)';
	// Project
	if (!empty($conf->projet->enabled))
	{
		$langs->load("projects");
		$morehtmlref .= '<br>'.$langs->trans('Project').' ';
		if ($user->rights->contrat->creer)
		{
			if ($action != 'classify') {
				//$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&amp;id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a>';
				$morehtmlref .= ' : ';
			}
			if ($action == 'classify') {
				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
				$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
				$morehtmlref .= '<input type="hidden" name="action" value="classin">';
				$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
				$morehtmlref .= $formproject->select_projects($object->thirdparty->id, $object->fk_project, 'projectid', $maxlength, 0, 1, 0, 1, 0, 0, '', 1);
				$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
				$morehtmlref .= '</form>';
			} else {
				$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->thirdparty->id, $object->fk_project, 'none', 0, 0, 0, 1);
			}
		} else {
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
	}
	$morehtmlref .= '</div>';

	dol_banner_tab($object, 'id', $linkback, 1, 'ref', 'none', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	$object->info($id);
	dol_print_object_info($object, 1);

	print '</div>';

	dol_fiche_end();



	// Actions buttons

	/*$objthirdparty=$object;
	$objcon=new stdClass();

	$out='';
	$permok=$user->rights->agenda->myactions->create;
	if ((! empty($objthirdparty->id) || ! empty($objcon->id)) && $permok)
	{
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objthirdparty) == 'Societe') $out.='&amp;socid='.$objthirdparty->id;
		$out.=(! empty($objcon->id)?'&amp;contactid='.$objcon->id:'').'&amp;backtopage=1&amp;percentage=-1';
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}*/


	//print '<div class="tabsAction">';
	//print '</div>';


	$newcardbutton = '';
	if (!empty($conf->agenda->enabled))
	{
		if (!empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create))
		{
			$newcardbutton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out);
		}
	}

	if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read)))
	{
		print '<br>';

		$param = '&id='.$id;
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;

		print load_fiche_titre($langs->trans("ActionsOnContract"), $newcardbutton, '');
		//print_barre_liste($langs->trans("ActionsOnCompany"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $newcardbutton, '', 0, 1, 1);

		// List of all actions
		$filters = array();
		$filters['search_agenda_label'] = $search_agenda_label;

		// TODO Replace this with same code than into list.php
		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
	}
}

llxFooter();
$db->close();
