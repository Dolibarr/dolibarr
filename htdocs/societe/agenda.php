<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/societe/agenda.php
 *  \ingroup    societe
 *  \brief      Page of third party events
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

$langs->loadLangs(array("companies", "bills", "propal", "orders"));

if (GETPOST('actioncode', 'array'))
{
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) $actioncode = '0';
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ?GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : (empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT) ? '' : $conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label = GETPOST('search_agenda_label');

// Security check
$socid = GETPOST('socid', 'int');
if ($user->socid) $socid = $user->socid;
$result = restrictedArea($user, 'societe', $socid, '&societe');

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

$parameters = array('id'=>$socid);
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

$form = new Form($db);

if ($socid > 0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$object = new Societe($db);
	$result = $object->fetch($socid);

	$title = $langs->trans("Agenda");
	if (!empty($conf->global->MAIN_HTML_TITLE) && preg_match('/thirdpartynameonly/', $conf->global->MAIN_HTML_TITLE) && $object->name) $title = $object->name." - ".$title;
	llxHeader('', $title);

	if (!empty($conf->notification->enabled)) $langs->load("mails");
	$head = societe_prepare_head($object);

	print dol_get_fiche_head($head, 'agenda', $langs->trans("ThirdParty"), -1, 'company');

	$linkback = '<a href="'.DOL_URL_ROOT.'/societe/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'socid', $linkback, ($user->socid ? 0 : 1), 'rowid', 'nom');

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	$object->info($socid);
	dol_print_object_info($object, 1);

	print '</div>';

	print dol_get_fiche_end();



	// Actions buttons

	$objthirdparty = $object;
	$objcon = new stdClass();

	$out = '';
	$permok = $user->rights->agenda->myactions->create;
	if ((!empty($objthirdparty->id) || !empty($objcon->id)) && $permok)
	{
		if (is_object($objthirdparty) && get_class($objthirdparty) == 'Societe') $out .= '&amp;originid='.$objthirdparty->id.($objthirdparty->id > 0 ? '&amp;socid='.$objthirdparty->id : '').'&amp;backtopage='.urlencode($_SERVER['PHP_SELF'].($objthirdparty->id > 0 ? '?socid='.$objthirdparty->id : ''));
		$out .= (!empty($objcon->id) ? '&amp;contactid='.$objcon->id : '').'&amp;percentage=-1';
		$out .= '&amp;datep='.dol_print_date(dol_now(), 'dayhourlog');
	}

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

		$param = '&socid='.$socid;
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
		if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;

		print load_fiche_titre($langs->trans("ActionsOnCompany"), $newcardbutton, '');
		//print_barre_liste($langs->trans("ActionsOnCompany"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $newcardbutton, '', 0, 1, 1);

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
