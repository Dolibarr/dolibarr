<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 *  \file       htdocs/product/agenda.php
 *  \ingroup    product
 *  \brief      Page of product events
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

// Load translation files required by the page
$langs->load("companies");

if (GETPOST('actioncode', 'array'))
{
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) $actioncode = '0';
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

// Security check
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
if ($user->socid) $id = $user->socid;
$result = restrictedArea($user, 'produit|service', $id, 'product&product');

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
$hookmanager->initHooks(array('agendathirdparty'));


/*
 *	Actions
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
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);

if ($id > 0 || $ref)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

	$langs->load("companies");


	$object = new Product($db);
	$result = $object->fetch($id, $ref);

	$title = $langs->trans("Agenda");
	if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/productnameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name." - ".$title;
	llxHeader('', $title);

	if (!empty($conf->notification->enabled)) $langs->load("mails");
	$type = $langs->trans('Product');
	if ($object->isService()) $type = $langs->trans('Service');

	$head = product_prepare_head($object);

	$titre = $langs->trans("CardProduct".$object->type);
	$picto = ($object->type == Product::TYPE_SERVICE ? 'service' : 'product');
	print dol_get_fiche_head($head, 'agenda', $titre, -1, $picto);

	$linkback = '<a href="'.DOL_URL_ROOT.'/product/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
	$object->next_prev_filter = " fk_product_type = ".$object->type;

	$shownav = 1;
	if ($user->socid && !in_array('product', explode(',', $conf->global->MAIN_MODULES_FOR_EXTERNAL))) $shownav = 0;

	dol_banner_tab($object, 'ref', $linkback, $shownav, 'ref');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	$object->info($object->id);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();



	// Actions buttons

	$objproduct = $object;
	$objcon = new stdClass();

	$out = '';
	$permok = $user->rights->agenda->myactions->create;
	if ((!empty($objproduct->id) || !empty($objcon->id)) && $permok)
	{
		//$out.='<a href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create';
		if (get_class($objproduct) == 'Product') $out .= '&amp;prodid='.$objproduct->id.'&origin=product&originid='.$id;
		$out .= (!empty($objcon->id) ? '&amp;contactid='.$objcon->id : '').'&amp;backtopage=1&amp;percentage=-1';
		//$out.=$langs->trans("AddAnAction").' ';
		//$out.=img_picto($langs->trans("AddAnAction"),'filenew');
		//$out.="</a>";
	}


	//print '<div class="tabsAction">';
	//print '</div>';


	$morehtmlcenter = '';
	if (!empty($conf->agenda->enabled))
	{
		$linktocreatetimeBtnStatus = !empty($user->rights->agenda->myactions->create) || !empty($user->rights->agenda->allactions->create);
		$morehtmlcenter = dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create'.$out, '', $linktocreatetimeBtnStatus);
	}

	if (!empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read)))
	{
		print '<br>';

		$param = '&id='.$id;
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;

		print_barre_liste($langs->trans("ActionsOnProduct"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlcenter, '', 0, 1, 1);

		// List of all actions
		$filters = array();
		$filters['search_agenda_label'] = $search_agenda_label;

		// TODO Replace this with same code than into list.php
		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
	}
}

// End of page
llxFooter();
$db->close();
