<?php
/* Copyright (C) - 2013-2016 Jean-François FERRY    <hello@librethic.io>
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
 *		\file       htdocs/ticket/agenda.php
 *    	\ingroup	ticket
 *    	\brief		Page with events on ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket'));

// Get parameters
$id       = GETPOST('id', 'int');
$ref      = GETPOST('ref', 'alpha');
$track_id = GETPOST('track_id', 'alpha', 3);
$socid    = GETPOST('socid', 'int');
$action   = GETPOST('action', 'aZ09');

$limit = GETPOST('limit','int')?GETPOST('limit','int'):$conf->liste_limit;
$sortfield = GETPOST("sortfield","alpha");
$sortorder = GETPOST("sortorder");
$page = GETPOST("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
if (! $sortfield) $sortfield="a.datep,a.id";
if (! $sortorder) $sortorder="DESC";
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('actioncode','array'))
{
	$actioncode=GETPOST('actioncode','array',3);
	if (! count($actioncode)) $actioncode='0';
}
else
{
	$actioncode=GETPOST("actioncode","alpha",3)?GETPOST("actioncode","alpha",3):(GETPOST("actioncode")=='0'?'0':(empty($conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT)?'':$conf->global->AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT));
}
$search_agenda_label=GETPOST('search_agenda_label');

$object = new Ticket($db);
$object->fetch($id, $ref, $track_id);

$extrafields = new ExtraFields($db);
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);

if (!$action) {
	$action = 'view';
}


// Security check
$id = GETPOST("id",'int');
$socid=0;
//if ($user->societe_id > 0) $socid = $user->societe_id;    // For external user, no check is done on company because readability is managed by public status of project and assignement.
$result=restrictedArea($user,'ticket',$id,'');

if (!$user->rights->ticket->read) {
	accessforbidden();
}
// restrict access for externals users
if ($user->societe_id > 0 && ($object->fk_soc != $user->societe_id))
{
	accessforbidden('', 0);
}
// or for unauthorized internals users
if (!$user->societe_id && ($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY && $object->fk_user_assign != $user->id) && !$user->rights->ticket->manage) {
	accessforbidden('', 0);
}



/*
 * Actions
 */

$parameters=array('id'=>$socid);
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

// Purge search criteria
if (GETPOST('button_removefilter_x','alpha') || GETPOST('button_removefilter.x','alpha') || GETPOST('button_removefilter','alpha')) // All test are required to be compatible with all browsers
{
	$actioncode='';
	$search_agenda_label='';
}



/*
 * View
 */

$form = new Form($db);
$userstat = new User($db);
$formticket = new FormTicket($db);

$title=$langs->trans("Ticket").' - '.$object->ref.' '.$object->name;
if (! empty($conf->global->MAIN_HTML_TITLE) && preg_match('/ticketnameonly/',$conf->global->MAIN_HTML_TITLE) && $object->name) $title=$object->ref.' '.$object->name.' - '.$langs->trans("Info");
$help_url = 'FR:DocumentationModuleTicket';
llxHeader('', $title, $help_url);

	if ($socid > 0) {
            $object->fetch_thirdparty();
            $head = societe_prepare_head($object->thirdparty);

            dol_fiche_head($head, 'ticket', $langs->trans("ThirdParty"), 0, 'company');

            dol_banner_tab($object->thirdparty, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');

            dol_fiche_end();
    }

        if (!$user->societe_id && $conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) {
            $object->next_prev_filter = "te.fk_user_assign = '" . $user->id . "'";
        } elseif ($user->societe_id > 0) {
            $object->next_prev_filter = "te.fk_soc = '" . $user->societe_id . "'";
        }
        $head = ticket_prepare_head($object);

        dol_fiche_head($head, 'tabTicketLogs', $langs->trans("Ticket"), 0, 'ticket');

        $morehtmlref ='<div class="refidno">';
        $morehtmlref.= $object->subject;
        // Author
        if ($object->fk_user_create > 0) {
        	$morehtmlref .= '<br>' . $langs->trans("CreatedBy") . '  ';

        	$langs->load("users");
        	$fuser = new User($db);
        	$fuser->fetch($object->fk_user_create);
        	$morehtmlref .= $fuser->getNomUrl(0);
        }
        if (!empty($object->origin_email)) {
        	$morehtmlref .= '<br>' . $langs->trans("CreatedBy") . ' : ';
        	$morehtmlref .= $object->origin_email . ' <small>(' . $langs->trans("TicketEmailOriginIssuer") . ')</small>';
        }

        // Thirdparty
        if (! empty($conf->societe->enabled))
        {
        	$morehtmlref.='<br>'.$langs->trans('ThirdParty');
        	/*if ($action != 'editcustomer' && $object->fk_statut < 8 && !$user->societe_id && $user->rights->ticket->write) {
        		$morehtmlref.='<a href="' . $url_page_current . '?action=editcustomer&amp;track_id=' . $object->track_id . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a>';
        	}*/
        	$morehtmlref.=' : ';
        	if ($action == 'editcustomer') {
        		$morehtmlref.=$form->form_thirdparty($url_page_current . '?track_id=' . $object->track_id, $object->socid, 'editcustomer', '', 1, 0, 0, array(), 1);
        	} else {
        		$morehtmlref.=$form->form_thirdparty($url_page_current . '?track_id=' . $object->track_id, $object->socid, 'none', '', 1, 0, 0, array(), 1);
        	}
        }

        // Project
        if (! empty($conf->projet->enabled))
        {
        	$langs->load("projects");
        	$morehtmlref.='<br>'.$langs->trans('Project');
        	if ($user->rights->ticket->write)
        	{
        		if ($action != 'classify')
        			//$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
        			$morehtmlref.=' : ';
        			if ($action == 'classify') {
        				//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
        				$morehtmlref.='<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
        				$morehtmlref.='<input type="hidden" name="action" value="classin">';
        				$morehtmlref.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        				$morehtmlref.=$formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
        				$morehtmlref.='<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
        				$morehtmlref.='</form>';
        			} else {
        				$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
        			}
        	} else {
        		if (! empty($object->fk_project)) {
        			$proj = new Project($db);
        			$proj->fetch($object->fk_project);
        			$morehtmlref.=$proj->getNomUrl(1);
        		} else {
        			$morehtmlref.='';
        		}
        	}
        }

        $morehtmlref.='</div>';

        $linkback = '<a href="' . dol_buildpath('/ticket/list.php', 1) . '"><strong>' . $langs->trans("BackToList") . '</strong></a> ';

        dol_banner_tab($object, 'ref', $linkback, ($user->societe_id ? 0 : 1), 'ref', 'ref', $morehtmlref, '', 0, '', '', 1);

        dol_fiche_end();

    print '<br>';


if (!empty($object->id))
{
	print '<br>';
	$param='&id='.$object->id;
	if (! empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param.='&contextpage='.$contextpage;
	if ($limit > 0 && $limit != $conf->liste_limit) $param.='&limit='.$limit;

	print_barre_liste($langs->trans("ActionsOnTicket"), 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlcenter, '', 0, 1, 1);

	// List of all actions
	$filters=array();
	$filters['search_agenda_label']=$search_agenda_label;
	show_actions_done($conf,$langs,$db,$object,null,0,$actioncode, '', $filters, $sortfield, $sortorder);
}

// End of page
llxFooter();
$db->close();
