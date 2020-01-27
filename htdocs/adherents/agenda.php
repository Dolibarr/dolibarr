<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Brice Davoleau       <brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Patrick Raguin  		<patrick.raguin@gmail.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *  \file       htdocs/adherents/agenda.php
 *  \ingroup    member
 *  \brief      Page of members events
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "members"));

$id = GETPOST('id', 'int') ?GETPOST('id', 'int') : GETPOST('rowid', 'int');

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'a.datep,a.id';
if (!$sortorder) $sortorder = 'DESC';

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

// Security check
$result = restrictedArea($user, 'adherent', $id);

$object = new Adherent($db);
$result = $object->fetch($id);
if ($result > 0)
{
	$object->fetch_thirdparty();

    $adht = new AdherentType($db);
    $result = $adht->fetch($object->typeid);
}


/*
 *	Actions
 */

$parameters = array('id'=>$id, 'objcanvas'=>$objcanvas);
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
    if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All test are required to be compatible with all browsers
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

/*
 * Customer and/or supplier category sheet
 */
if ($object->id > 0)
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");

	$title = $langs->trans("Member")." - ".$langs->trans("Agenda");
	$helpurl = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros";
	llxHeader("", $title, $helpurl);

	if (!empty($conf->notification->enabled)) $langs->load("mails");
	$head = member_prepare_head($object);

	dol_fiche_head($head, 'agenda', $langs->trans("Member"), -1, 'user');

	$linkback = '<a href="'.DOL_URL_ROOT.'/adherents/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

	dol_banner_tab($object, 'rowid', $linkback);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	$object->info($id);
	dol_print_object_info($object, 1);

	print '</div>';

	dol_fiche_end();


    //print '<div class="tabsAction">';
    //print '</div>';


	$newcardbutton = '';
    if (! empty($conf->agenda->enabled))
    {
        $newcardbutton.= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/comm/action/card.php?action=create&backtopage=1&origin=member&originid='.$id);
    }

    if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
    {
    	print '<br>';

    	$param='&id='.$id;
    	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
    	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

    	print_barre_liste($langs->trans("ActionsOnMember"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', '', $newcardbutton, '', 0, 1, 1);

    	// List of all actions
    	$filters=array();
    	$filters['search_agenda_label']=$search_agenda_label;

    	// TODO Replace this with same code than into list.php
    	show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
    }
}

// End of page
llxFooter();
$db->close();
