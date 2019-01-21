<?php
/* Copyright (C) 2013-2016 Jean-François FERRY <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2018      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *    \file     htdocs/ticket/card.php
 *    \ingroup 	ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';
if (!empty($conf->projet->enabled)) {
    include_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formprojet.class.php';
    include_once DOL_DOCUMENT_ROOT . '/core/lib/project.lib.php';
}
if (!empty($conf->contrat->enabled)) {
    include_once DOL_DOCUMENT_ROOT . '/core/lib/contract.lib.php';
    include_once DOL_DOCUMENT_ROOT . '/contrat/class/contrat.class.php';
    include_once DOL_DOCUMENT_ROOT . '/core/class/html.formcontract.class.php';
}

// Load translation files required by the page
$langs->loadLangs(array("companies","other","ticket"));

// Get parameters
$id        = GETPOST('id', 'int');
$track_id  = GETPOST('track_id', 'alpha', 3);
$ref       = GETPOST('ref', 'alpha');
$projectid = GETPOST('projectid', 'int');
$action    = GETPOST('action', 'aZ09');

// Initialize technical object to manage hooks of ticket. Note that conf->hooks_modules contains array array
$hookmanager->initHooks(array('ticketcard','globalcard'));

$object = new Ticket($db);
$extrafields = new ExtraFields($db);
// Fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element,'','search_');

// Initialize array of search criterias
$search_all=trim(GETPOST("search_all",'alpha'));
$search=array();
foreach($object->fields as $key => $val)
{
	if (GETPOST('search_'.$key,'alpha')) $search[$key]=GETPOST('search_'.$key,'alpha');
}

if (empty($action) && empty($id) && empty($ref)) $action='view';

//Select mail models is same action as add_message
if (GETPOST('modelselected','alpha')) {
    $action = 'add_message';
}

// Load object
//include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php';  // Must be include, not include_once  // Must be include, not include_once. Include fetch and fetch_thirdparty but not fetch_optionals
if ($id || $track_id || $ref) {
	$res = $object->fetch($id, $ref, $track_id);
	if ($res >= 0)
	{
		$id = $object->id;
		$track_id = $object->track_id;
	}
}

// Store current page url
$url_page_current = DOL_URL_ROOT.'/ticket/card.php';

// Security check - Protection if external user
//if ($user->societe_id > 0) access_forbidden();
//if ($user->societe_id > 0) $socid = $user->societe_id;
$result = restrictedArea($user, 'ticket', $object->id);

$triggermodname = 'TICKETSUP_MODIFY';
$permissiontoadd = $user->rights->ticket->write;

$actionobject = new ActionsTicket($db);

$now = dol_now();


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ($cancel)
{
	if (! empty($backtopage))
	{
		header("Location: ".$backtopage);
		exit;
	}
	$action='view';
}

// Do action
$actionobject->doActions($action, $object);

// Action to update one extrafield
if ($action == "update_extras" && ! empty($permissiontoadd))
{
	$object->fetch(GETPOST('id','int'), '', GETPOST('track_id','alpha'));
	$attributekey = GETPOST('attribute','alpha');
	$attributekeylong = 'options_'.$attributekey;
	$object->array_options['options_'.$attributekey] = GETPOST($attributekeylong,' alpha');

	$result = $object->insertExtraFields(empty($triggermodname)?'':$triggermodname, $user);
	if ($result > 0)
	{
		setEventMessages($langs->trans('RecordSaved'), null, 'mesgs');
		$action = 'view';
	}
	else
	{
		setEventMessages($object->error, $object->errors, 'errors');
		$action = 'edit_extras';
	}
}

if ($action == "change_property" && GETPOST('btn_update_ticket_prop','alpha') && $user->rights->ticket->write)
{
	$object->fetch(GETPOST('id','int'), '', GETPOST('track_id','alpha'));

	$object->type_code = GETPOST('update_value_type','az09');
	$object->category_code = GETPOST('update_value_category','az09');
	$object->severity_code = GETPOST('update_value_severity','az09');

	$ret = $object->update($user);
	if ($ret > 0) {
		$log_action = $langs->trans('TicketLogPropertyChanged', $oldvalue_label, $newvalue_label);
		$ret = $object->createTicketLog($user, $log_action);
		if ($ret > 0) {
			setEventMessages($langs->trans('TicketUpdated'), null, 'mesgs');
		}
	}
	$action = 'view';
}

$permissiondellink = $user->rights->ticket->write;
include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';        // Must be include, not include_once




/*
 * View
 */

$userstat = new User($db);
$form = new Form($db);
$formticket = new FormTicket($db);
if (! empty($conf->projet->enabled)) $formproject = new FormProjets($db);

$help_url = 'FR:DocumentationModuleTicket';
$page_title = $actionobject->getTitle($action);

llxHeader('', $page_title, $help_url);


if (empty($action) || $action == 'view' || $action == 'addlink' || $action == 'dellink' || $action == 'add_message' || $action == 'close' || $action == 'delete' || $action == 'editcustomer' || $action == 'progression' || $action == 'reopen'
	|| $action == 'editsubject' || $action == 'edit_extras' || $action == 'update_extras' || $action == 'edit_extrafields' || $action == 'set_extrafields' || $action == 'classify' || $action == 'sel_contract' || $action == 'edit_message_init' || $action == 'set_status' || $action == 'dellink')
{

    if ($res > 0)
    {
        // or for unauthorized internals users
        if (!$user->societe_id && ($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY && $object->fk_user_assign != $user->id) && !$user->rights->ticket->manage) {
            accessforbidden('', 0);
        }

        // Confirmation close
        if ($action == 'close') {
            print $form->formconfirm($url_page_current . "?track_id=" . $object->track_id, $langs->trans("CloseATicket"), $langs->trans("ConfirmCloseAticket"), "confirm_close", '', '', 1);
            if ($ret == 'html') {
                print '<br>';
            }
        }
        // Confirmation delete
        if ($action == 'delete') {
        	print $form->formconfirm($url_page_current . "?track_id=" . $object->track_id, $langs->trans("Delete"), $langs->trans("ConfirmDeleteTicket"), "confirm_delete_ticket", '', '', 1);
        }
        // Confirm reopen
        if ($action == 'reopen') {
        	print $form->formconfirm($url_page_current . '?track_id=' . $object->track_id, $langs->trans('ReOpen'), $langs->trans('ConfirmReOpenTicket'), 'confirm_reopen', '', '', 1);
        }
        // Confirmation status change
        if ($action == 'set_status') {
            $new_status = GETPOST('new_status');
            //var_dump($url_page_current . "?track_id=" . $object->track_id);
            print $form->formconfirm($url_page_current . "?track_id=" . $object->track_id . "&new_status=" . GETPOST('new_status'), $langs->trans("TicketChangeStatus"), $langs->trans("TicketConfirmChangeStatus", $langs->transnoentities($object->statuts_short[$new_status])), "confirm_set_status", '', '', 1);
        }

        // project info
        if ($projectid) {
            $projectstat = new Project($db);
            if ($projectstat->fetch($projectid) > 0) {
                $projectstat->fetch_thirdparty();

                // To verify role of users
                //$userAccess = $object->restrictedProjectArea($user,'read');
                $userWrite = $projectstat->restrictedProjectArea($user, 'write');
                //$userDelete = $object->restrictedProjectArea($user,'delete');
                //print "userAccess=".$userAccess." userWrite=".$userWrite." userDelete=".$userDelete;

                $head = project_prepare_head($projectstat);
                dol_fiche_head($head, 'ticket', $langs->trans("Project"), 0, ($projectstat->public ? 'projectpub' : 'project'));

                /*
                 *   Projet synthese pour rappel
                 */
                print '<table class="border" width="100%">';

                $linkback = '<a href="' . DOL_URL_ROOT . '/projet/list.php">' . $langs->trans("BackToList") . '</a>';

                // Ref
                print '<tr><td width="30%">' . $langs->trans('Ref') . '</td><td colspan="3">';
                // Define a complementary filter for search of next/prev ref.
                if (!$user->rights->projet->all->lire) {
                    $objectsListId = $projectstat->getProjectsAuthorizedForUser($user, $mine, 0);
                    $projectstat->next_prev_filter = " rowid in (" . (count($objectsListId) ? join(',', array_keys($objectsListId)) : '0') . ")";
                }
                print $form->showrefnav($projectstat, 'ref', $linkback, 1, 'ref', 'ref', '');
                print '</td></tr>';

                // Label
                print '<tr><td>' . $langs->trans("Label") . '</td><td>' . $projectstat->title . '</td></tr>';

                // Customer
                print "<tr><td>" . $langs->trans("ThirdParty") . "</td>";
                print '<td colspan="3">';
                if ($projectstat->thirdparty->id > 0) {
                    print $projectstat->thirdparty->getNomUrl(1);
                } else {
                    print '&nbsp;';
                }

                print '</td></tr>';

                // Visibility
                print '<tr><td>' . $langs->trans("Visibility") . '</td><td>';
                if ($projectstat->public) {
                    print $langs->trans('SharedProject');
                } else {
                    print $langs->trans('PrivateProject');
                }

                print '</td></tr>';

                // Statut
                print '<tr><td>' . $langs->trans("Status") . '</td><td>' . $projectstat->getLibStatut(4) . '</td></tr>';

                print "</table>";

                print '</div>';
            } else {
                print "ErrorRecordNotFound";
            }
        } elseif ($socid > 0) {
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

        dol_fiche_head($head, 'tabTicket', $langs->trans("Ticket"), -1, 'ticket');

        $morehtmlref ='<div class="refidno">';
        $morehtmlref.= $object->subject;
        // Author
        if ($object->fk_user_create > 0) {
        	$morehtmlref .= '<br>' . $langs->trans("CreatedBy") . ' : ';

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
	        $morehtmlref.='<br>'.$langs->trans('ThirdParty') . ' ';
	        if ($action != 'editcustomer' && $object->fk_statut < 8 && !$user->societe_id && $user->rights->ticket->write) {
	        	$morehtmlref.='<a href="' . $url_page_current . '?action=editcustomer&amp;track_id=' . $object->track_id . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a> : ';
	        }
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
        			$morehtmlref.='<a href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
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

        dol_banner_tab($object, 'ref', $linkback, ($user->societe_id ? 0 : 1), 'ref', 'ref', $morehtmlref);

        print '<div class="fichecenter"><div class="fichehalfleft">';
        print '<div class="underbanner clearboth"></div>';
        print '<table class="border centpercent">';

        // Track ID
        print '<tr><td class="titlefield">' . $langs->trans("TicketTrackId") . '</td><td>';
        if (!empty($object->track_id)) {
            if (empty($object->ref)) {
                $object->ref = $object->id;
                print $form->showrefnav($object, 'id', $linkback, 1, 'rowid', 'track_id');
            } else {
                print $object->track_id;
            }
        } else {
            print $langs->trans('None');
        }
        print '</td></tr>';

        // Subject
        print '<tr><td>';
        print $form->editfieldkey("Subject", 'subject', $object->subject, $object, $user->rights->ticket->write && !$user->societe_id, 'string');
        print '</td><td>';
        print $form->editfieldval("Subject", 'subject', $object->subject, $object, $user->rights->ticket->write && !$user->societe_id, 'string');
        print '</td></tr>';

        // Creation date
        print '<tr><td>' . $langs->trans("DateCreation") . '</td><td>';
        print dol_print_date($object->datec, 'dayhour');
        print ' - '.$langs->trans("TimeElapsedSince").': '.'<i>'.convertSecondToTime(roundUpToNextMultiple($now - $object->datec, 60)).'</i>';
        print '</td></tr>';

        // Read date
        print '<tr><td>' . $langs->trans("TicketReadOn") . '</td><td>';
        if (!empty($object->date_read)) {
        	print dol_print_date($object->date_read, 'dayhour');
        	print ' - '.$langs->trans("TicketTimeToRead").': <i>'.convertSecondToTime(roundUpToNextMultiple($object->date_read - $object->datec, 60)).'</i>';
        	print ' - '.$langs->trans("TimeElapsedSince").': '.'<i>'.convertSecondToTime(roundUpToNextMultiple($now - $object->date_read, 60)).'</i>';
        }
        print '</td></tr>';

        // Close date
        print '<tr><td>' . $langs->trans("TicketCloseOn") . '</td><td>';
        if (!empty($object->date_close)) {
        	print dol_print_date($object->date_close, 'dayhour');
        }
        print '</td></tr>';

        // User assigned
        print '<tr><td>' . $langs->trans("AssignedTo") . '</td><td>';
        if ($object->fk_user_assign > 0) {
            $userstat->fetch($object->fk_user_assign);
            print $userstat->getNomUrl(1);
        } else {
            print $langs->trans('None');
        }

        // Show user list to assignate one if status is "read"
        if (GETPOST('set','alpha') == "assign_ticket" && $object->fk_statut < 8 && !$user->societe_id && $user->rights->ticket->write) {
            print '<form method="post" name="ticket" enctype="multipart/form-data" action="' . $url_page_current . '">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<input type="hidden" name="action" value="assign_user">';
            print '<input type="hidden" name="track_id" value="' . $object->track_id . '">';
            print '<label for="fk_user_assign">' . $langs->trans("AssignUser") . '</label> ';
            print $form->select_dolusers($user->id, 'fk_user_assign', 1);
            print ' <input class="button" type="submit" name="btn_assign_user" value="' . $langs->trans("Validate") . '" />';
            print '</form>';
        }
        if ($object->fk_statut < 8 && GETPOST('set','alpha') != "assign_ticket" && $user->rights->ticket->manage) {
            print '<a href="' . $url_page_current . '?track_id=' . $object->track_id . '&action=view&set=assign_ticket">' . img_picto('', 'edit') . ' ' . $langs->trans('Modify') . '</a>';
        }
        print '</td></tr>';

        // Progression
        print '<tr><td>';
        print '<table class="nobordernopadding" width="100%"><tr><td class="nowrap">';
        print $langs->trans('Progression') . '</td><td align="left">';
        print '</td>';
        if ($action != 'progression' && $object->fk_statut < 8 && !$user->societe_id) {
            print '<td align="right"><a href="' . $url_page_current . '?action=progression&amp;track_id=' . $object->track_id . '">' . img_edit($langs->trans('Modify')) . '</a></td>';
        }
        print '</tr></table>';
        print '</td><td colspan="5">';
        if ($user->rights->ticket->write && $action == 'progression') {
            print '<form action="' . $url_page_current . '" method="post">';
            print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
            print '<input type="hidden" name="track_id" value="' . $track_id . '">';
            print '<input type="hidden" name="action" value="set_progression">';
            print '<input type="text" class="flat" size="20" name="progress" value="' . $object->progress . '">';
            print ' <input type="submit" class="button" value="' . $langs->trans('Modify') . '">';
            print '</form>';
        } else {
            print($object->progress > 0 ? $object->progress : '0') . '%';
        }
        print '</td>';
        print '</tr>';

        // Timing (Duration sum of linked fichinter)
        if ($conf->fichinter->enabled)
        {
	        $object->fetchObjectLinked();
	        $num = count($object->linkedObjects);
	        $timing = 0;
	        if ($num) {
	            foreach ($object->linkedObjects as $objecttype => $objects) {
	                if ($objecttype = "fichinter") {
	                    foreach ($objects as $fichinter) {
	                        $timing += $fichinter->duration;
	                    }
	                }
	            }
	        }
	        print '<tr><td valign="top">';

	        print $form->textwithpicto($langs->trans("TicketDurationAuto"), $langs->trans("TicketDurationAutoInfos"), 1);
	        print '</td><td>';
	        print convertSecondToTime($timing, 'all', $conf->global->MAIN_DURATION_OF_WORKDAY);
	        print '</td></tr>';
        }

        // Other attributes
        include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

        print '</table>';


        // Fin colonne gauche et début colonne droite
        print '</div><div class="fichehalfright"><div class="ficheaddleft">';


        // View Original message
        $actionobject->viewTicketOriginalMessage($user, $action, $object);


        /***************************************************
         *
         *      Classification and actions on ticket
         *
         ***************************************************/

        print '<form method="post" name="formticketproperties" action="' . $url_page_current . '">';
        print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
        print '<input type="hidden" name="action" value="change_property">';
        print '<input type="hidden" name="property" value="' . $property['dict'] . '">';
        print '<input type="hidden" name="track_id" value="' . $track_id . '">';

        print '<div class="div-table-responsive-no-min">';		// You can use div-table-responsive-no-min if you dont need reserved height for your table
        print '<table class="border centpercent margintable">';
        print '<tr class="liste_titre">';
        print '<td>';
        print $langs->trans('Properties');
        print '</td>';
        print '<td>';
        if (GETPOST('set','alpha') == 'properties' && $user->rights->ticket->write) {
        	print '<input class="button" type="submit" name="btn_update_ticket_prop" value="' . $langs->trans("Modify") . '" />';
        }
        else {
        	//    Button to edit Properties
        	if ($object->fk_statut < 5 && $user->rights->ticket->write) {
        		print '<a href="card.php?track_id=' . $object->track_id . '&action=view&set=properties">' . img_edit($langs->trans('Modify')) . '</a>';
        	}
        }
        print '</td>';
        print '</tr>';
        if (GETPOST('set','alpha') == 'properties' && $user->rights->ticket->write) {
            print '<tr>';
            print '<td class="titlefield">';
            print $langs->trans('TicketChangeType');
            print '</td><td>';
            print $formticket->selectTypesTickets($object->type_code, 'update_value_type', '', 2);
            print '</td>';
            print '</tr>';
            print '<tr>';
            print '<td>';
            print $langs->trans('TicketChangeCategory');
            print '</td><td>';
            print $formticket->selectCategoriesTickets($object->category_code, 'update_value_category', '', 2);
            print '</td>';
            print '</tr>';
            print '<tr>';
            print '<td>';
            print $langs->trans('TicketChangeSeverity');
            print '</td><td>';
            print $formticket->selectSeveritiesTickets($object->severity_code, 'update_value_severity', '', 2);
            print '</td>';
            print '</tr>';
        } else {
            // Type
            print '<tr><td class="titlefield">' . $langs->trans("Type") . '</td><td>';
            print $langs->getLabelFromKey($db, $object->type_code, 'c_ticket_type', 'code', 'label');
            /*if ($user->admin && !$noadmininfo) {
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }*/
            print '</td></tr>';

            // Category
            print '<tr><td>' . $langs->trans("Category") . '</td><td>';
            print $langs->getLabelFromKey($db, $object->category_code, 'c_ticket_category', 'code', 'label');
            /*if ($user->admin && !$noadmininfo) {
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }*/
            print '</td></tr>';

            // Severity
            print '<tr><td>' . $langs->trans("TicketSeverity") . '</td><td>';
            print $langs->getLabelFromKey($db, $object->severity_code, 'c_ticket_severity', 'code', 'label');
            /*if ($user->admin && !$noadmininfo) {
                print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
            }*/
            print '</td></tr>';
        }
        print '</table>'; // End table actions

        print '</form>';
        print '</div>';

        // Display navbar with links to change ticket status
        print '<!-- navbar with status -->';
        if (!$user->societe_id && $user->rights->ticket->write && $object->fk_status < 8 && GETPOST('set') !== 'properties') {
        	$actionobject->viewStatusActions($object);
        }


        if (! empty($conf->global->MAIN_DISABLE_CONTACTS_TAB))
        {
	        print load_fiche_titre($langs->trans('Contacts'), '', 'title_companies.png');

	        print '<div class="div-table-responsive-no-min">';
	        print '<div class="tagtable centpercent noborder allwidth">';

	        print '<div class="tagtr liste_titre">';
	        print '<div class="tagtd">' . $langs->trans("Source") . '</div>
			<div class="tagtd">' . $langs->trans("Company") . '</div>
			<div class="tagtd">' . $langs->trans("Contacts") . '</div>
			<div class="tagtd">' . $langs->trans("ContactType") . '</div>
			<div class="tagtd">' . $langs->trans("Phone") . '</div>
			<div class="tagtd" align="center">' . $langs->trans("Status") . '</div>';
	        print '</div><!-- tagtr -->';

	        // Contact list
	        $companystatic = new Societe($db);
	        $contactstatic = new Contact($db);
	        $userstatic = new User($db);
	        foreach (array('internal', 'external') as $source) {
	            $tmpobject = $object;
	            $tab = $tmpobject->listeContact(-1, $source);
	            $num = count($tab);
	            $i = 0;
	            while ($i < $num) {
	                $var = !$var;
	                print '<div class="tagtr ' . ($var ? 'pair' : 'impair') . '">';

	                print '<div class="tagtd" align="left">';
	                if ($tab[$i]['source'] == 'internal') {
	                    echo $langs->trans("User");
	                }

	                if ($tab[$i]['source'] == 'external') {
	                    echo $langs->trans("ThirdPartyContact");
	                }

	                print '</div>';
	                print '<div class="tagtd" align="left">';

	                if ($tab[$i]['socid'] > 0) {
	                    $companystatic->fetch($tab[$i]['socid']);
	                    echo $companystatic->getNomUrl(1);
	                }
	                if ($tab[$i]['socid'] < 0) {
	                    echo $conf->global->MAIN_INFO_SOCIETE_NOM;
	                }
	                if (!$tab[$i]['socid']) {
	                    echo '&nbsp;';
	                }
	                print '</div>';

	                print '<div class="tagtd">';
	                if ($tab[$i]['source'] == 'internal') {
	                	if ($userstatic->fetch($tab[$i]['id'])) {
		                    print $userstatic->getNomUrl(1);
	                	}
	                }
	                if ($tab[$i]['source'] == 'external') {
	                	if ($contactstatic->fetch($tab[$i]['id'])) {
		                    print $contactstatic->getNomUrl(1);
	                	}
	                }
	                print ' </div>
					<div class="tagtd">' . $tab[$i]['libelle'] . '</div>';

	                print '<div class="tagtd">';

	                print dol_print_phone($tab[$i]['phone'], '', '', '', 'AC_TEL').'<br>';

	                if (! empty($tab[$i]['phone_perso'])) {
	                    //print img_picto($langs->trans('PhonePerso'),'object_phoning.png','',0,0,0).' ';
	                    print '<br>'.dol_print_phone($tab[$i]['phone_perso'], '', '', '', 'AC_TEL').'<br>';
	                }
	                if (! empty($tab[$i]['phone_mobile'])) {
	                    //print img_picto($langs->trans('PhoneMobile'),'object_phoning.png','',0,0,0).' ';
	                    print dol_print_phone($tab[$i]['phone_mobile'], '', '', '', 'AC_TEL').'<br>';
	                }
	                print '</div>';

	                print '<div class="tagtd" align="center">';
	                if ($object->statut >= 0) {
	                    echo '<a href="contact.php?track_id=' . $object->track_id . '&amp;action=swapstatut&amp;ligne=' . $tab[$i]['rowid'] . '">';
	                }

	                if ($tab[$i]['source'] == 'internal') {
	                    $userstatic->id = $tab[$i]['id'];
	                    $userstatic->lastname = $tab[$i]['lastname'];
	                    $userstatic->firstname = $tab[$i]['firstname'];
	                    echo $userstatic->LibStatut($tab[$i]['statuscontact'], 3);
	                }
	                if ($tab[$i]['source'] == 'external') {
	                    $contactstatic->id = $tab[$i]['id'];
	                    $contactstatic->lastname = $tab[$i]['lastname'];
	                    $contactstatic->firstname = $tab[$i]['firstname'];
	                    echo $contactstatic->LibStatut($tab[$i]['statuscontact'], 3);
	                }
	                if ($object->statut >= 0) {
	                    echo '</a>';
	                }

	                print '</div>';

	                print '</div><!-- tagtr -->';

	                $i++;
	            }
	        }

	        print '</div><!-- contact list -->';
			print '</div>';
        }

        print '</div></div></div>';
        print '<div style="clear:both"></div>';

		dol_fiche_end();


		// Buttons for actions
		if ($action != 'presend' && $action != 'editline') {
			print '<div class="tabsAction">'."\n";
			$parameters=array();
			$reshook=$hookmanager->executeHooks('addMoreActionsButtons',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
			if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

			if (empty($reshook))
			{
				// Show link to add a message (if read and not closed)
		        if ($object->fk_statut < 8 && $action != "add_message") {
		            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->track_id . '&action=add_message">' . $langs->trans('TicketAddMessage') . '</a></div>';
		        }

		        // Link to create an intervention
		        // socid is needed otherwise fichinter ask it and forgot origin after form submit :\
		        if (!$object->fk_soc && $user->rights->ficheinter->creer) {
		            print '<div class="inline-block divButAction"><a class="butActionRefused classfortooltip" href="#" title="' . $langs->trans('UnableToCreateInterIfNoSocid') . '">' . $langs->trans('TicketAddIntervention') . '</a></div>';
		        }
		        if ($object->fk_soc > 0 && $object->fk_statut < 8 && $user->rights->ficheinter->creer) {
		            print '<div class="inline-block divButAction"><a class="butAction" href="' . dol_buildpath('/fichinter/card.php', 1) . '?action=create&socid=' . $object->fk_soc . '&origin=ticket_ticket&originid=' . $object->id . '">' . $langs->trans('TicketAddIntervention') . '</a></div>';
		        }

		        // Close ticket if statut is read
		        if ($object->fk_statut > 0 && $object->fk_statut < 8 && $user->rights->ticket->write) {
		            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->track_id . '&action=close">' . $langs->trans('CloseTicket') . '</a></div>';
		        }

		        // Re-open ticket
		        if (!$user->socid && $object->fk_statut == 8 && !$user->societe_id) {
		            print '<div class="inline-block divButAction"><a class="butAction" href="card.php?track_id=' . $object->track_id . '&action=reopen">' . $langs->trans('ReOpen') . '</a></div>';
		        }

		        // Delete ticket
		        if ($user->rights->ticket->delete && !$user->societe_id) {
		            print '<div class="inline-block divButAction"><a class="butActionDelete" href="card.php?track_id=' . $object->track_id . '&action=delete">' . $langs->trans('Delete') . '</a></div>';
		        }
			}
	        print '</div>'."\n";
		}


		// Select mail models is same action as presend
		if (GETPOST('modelselected')) {
			$action = 'presend';
		}

		if (empty($action) || $action == 'view' || $action == 'addlink' || $action == 'dellink' || $action == 'edit_message_init')
		{
			print '<div class="fichecenter"><div class="fichehalfleft">';
			print '<a name="builddoc"></a>'; // ancre

			// Show links to link elements
			$linktoelem = $form->showLinkToObjectBlock($object, null, array('ticket'));
			$somethingshown = $form->showLinkedObjectBlock($object, $linktoelem);

			print '</div><div class="fichehalfright"><div class="ficheaddleft">';

			// Message list
			print load_fiche_titre($langs->trans('TicketMessagesList'), '', 'messages@ticket');
			$show_private_message = ($user->societe_id ? 0 : 1);
			$actionobject->viewTicketTimelineMessages($show_private_message, true, $object);

			print '</div></div>';
			print '</div><!-- fichecenter -->';
			print '<br style="clear: both">';
		}
		elseif ($action == 'add_message')
		{
			$action='new_message';
			$modelmail='ticket_send';

			print '<div>';
			print load_fiche_titre($langs->trans('TicketAddMessage'), '', 'messages@ticket');

			// Define output language
			$outputlangs = $langs;
			$newlang = '';
			if ($conf->global->MAIN_MULTILANGS && empty($newlang) && ! empty($_REQUEST['lang_id'])) {
				$newlang = $_REQUEST['lang_id'];
			}
			if ($conf->global->MAIN_MULTILANGS && empty($newlang)) {
				$newlang = $object->default_lang;
			}

			$formticket = new FormTicket($db);

			$formticket->action = $action;
			$formticket->track_id = $object->track_id;
			$formticket->id = $object->id;

			$formticket->withfile = 2;
			$formticket->param = array('fk_user_create' => $user->id);
			$formticket->param['langsmodels']=(empty($newlang)?$langs->defaultlang:$newlang);

			// Tableau des parametres complementaires du post
			$formticket->param['models']=$modelmail;
			$formticket->param['models_id']=GETPOST('modelmailselected', 'int');
			//$formticket->param['socid']=$object->fk_soc;
			$formticket->param['returnurl']=$_SERVER["PHP_SELF"].'?track_id='.$object->track_id;


			$formticket->withsubstit = 1;

			if ($object->fk_soc > 0) {
				$object->fetch_thirdparty();
				$formticket->substit['__THIRDPARTY_NAME__'] = $object->thirdparty->name;
			}
			$formticket->substit['__SIGNATURE__'] = $user->signature;
			$formticket->substit['__TICKETSUP_TRACKID__'] = $object->track_id;
			$formticket->substit['__TICKETSUP_REF__'] = $object->ref;
			$formticket->substit['__TICKETSUP_SUBJECT__'] = $object->subject;
			$formticket->substit['__TICKETSUP_TYPE__'] = $object->type_code;
			$formticket->substit['__TICKETSUP_CATEGORY__'] = $object->category_code;
			$formticket->substit['__TICKETSUP_SEVERITY__'] = $object->severity_code;
			$formticket->substit['__TICKETSUP_MESSAGE__'] = $object->message;
			$formticket->substit['__TICKETSUP_PROGRESSION__'] = $object->progress;
			if ($object->fk_user_assign > 0) {
				$userstat->fetch($object->fk_user_assign);
				$formticket->substit['__TICKETSUP_USER_ASSIGN__'] = dolGetFirstLastname($userstat->firstname, $userstat->lastname);
			}

			if ($object->fk_user_create > 0) {
				$userstat->fetch($object->fk_user_create);
				$formticket->substit['__TICKETSUP_USER_CREATE__'] = dolGetFirstLastname($userstat->firstname, $userstat->lastname);
			}


			$formticket->showMessageForm('100%');
			print '</div>';
	    }
	}
}

// End of page
llxFooter();
$db->close();
