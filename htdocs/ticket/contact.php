<?php
/* Copyright (C) 2011-2016 Jean-François Ferry    <hello@librethic.io>
 * Copyright (C) 2011      Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/ticket/contact.php
 *       \ingroup    ticket
 *       \brief      Contacts of tickets
 */

require '../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';

require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'ticket'));

// Get parameters
$socid = GETPOST("socid", 'int');
$action = GETPOST("action", 'alpha');
$track_id = GETPOST("track_id", 'alpha');
$id = GETPOST("id", 'int');
$ref = GETPOST('ref', 'alpha');

$type = GETPOST('type', 'alpha');
$source = GETPOST('source', 'alpha');

$ligne = GETPOST('ligne', 'int');
$lineid = GETPOST('lineid', 'int');

// Protection if external user
if ($user->socid > 0) {
    $socid = $user->socid;
    accessforbidden();
}

// Store current page url
$url_page_current = dol_buildpath('/ticket/contact.php', 1);

$object = new Ticket($db);


/*
 * Actions
 */

if ($action == 'addcontact' && $user->rights->ticket->write) {
    $result = $object->fetch($id, '', $track_id);

    if ($result > 0 && ($id > 0 || (!empty($track_id)))) {
        $contactid = (GETPOST('userid', 'int') ? GETPOST('userid', 'int') : GETPOST('contactid', 'int'));
        $result = $object->add_contact($contactid, $type, $source);
    }

    if ($result >= 0) {
        Header("Location: ".$url_page_current."?id=".$object->id);
        exit;
    } else {
        if ($object->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
            $langs->load("errors");
            setEventMessages($langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType"), null, 'errors');
        } else {
            setEventMessages($object->error, $object->errors, 'errors');
        }
    }
}

// bascule du statut d'un contact
if ($action == 'swapstatut' && $user->rights->ticket->write) {
    if ($object->fetch($id, '', $track_id)) {
        $result = $object->swapContactStatus($ligne);
    } else {
        dol_print_error($db, $object->error);
    }
}

// Efface un contact
if ($action == 'deletecontact' && $user->rights->ticket->write) {
    if ($object->fetch($id, '', $track_id)) {
        $result = $object->delete_contact($lineid);

        if ($result >= 0) {
            Header("Location: ".$url_page_current."?id=".$object->id);
            exit;
        }
    }
}



/*
 * View
 */

$help_url = 'FR:DocumentationModuleTicket';
llxHeader('', $langs->trans("TicketContacts"), $help_url);

$form = new Form($db);
$formcompany = new FormCompany($db);
$contactstatic = new Contact($db);
$userstatic = new User($db);

if ($id > 0 || !empty($track_id) || !empty($ref)) {
	if ($object->fetch($id, $ref, $track_id) > 0)
    {
        if ($socid > 0) {
            $object->fetch_thirdparty();
            $head = societe_prepare_head($object->thirdparty);
            dol_fiche_head($head, 'ticket', $langs->trans("ThirdParty"), 0, 'company');
            dol_banner_tab($object->thirdparty, 'socid', '', ($user->socid ? 0 : 1), 'rowid', 'nom');
            dol_fiche_end();
        }

        if (!$user->socid && $conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) {
            $object->next_prev_filter = "te.fk_user_assign = '".$user->id."'";
        } elseif ($user->socid > 0) {
            $object->next_prev_filter = "te.fk_soc = '".$user->socid."'";
        }

        $head = ticket_prepare_head($object);

        dol_fiche_head($head, 'contact', $langs->trans("Ticket"), -1, 'ticket');

        $morehtmlref = '<div class="refidno">';
        $morehtmlref .= $object->subject;
        // Author
        if ($object->fk_user_create > 0) {
        	$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';

        	$langs->load("users");
        	$fuser = new User($db);
        	$fuser->fetch($object->fk_user_create);
        	$morehtmlref .= $fuser->getNomUrl(0);
        }
        if (!empty($object->origin_email)) {
        	$morehtmlref .= '<br>'.$langs->trans("CreatedBy").' : ';
        	$morehtmlref .= $object->origin_email.' <small>('.$langs->trans("TicketEmailOriginIssuer").')</small>';
        }

        // Thirdparty
        if (!empty($conf->societe->enabled))
        {
        	$morehtmlref .= '<br>'.$langs->trans('ThirdParty');
        	/*if ($action != 'editcustomer' && $object->fk_statut < 8 && !$user->socid && $user->rights->ticket->write) {
        		$morehtmlref.='<a class="editfielda" href="' . $url_page_current . '?action=editcustomer&amp;track_id=' . $object->track_id . '">' . img_edit($langs->transnoentitiesnoconv('Edit'), 1) . '</a>';
        	}*/
        	$morehtmlref .= ' : ';
        	if ($action == 'editcustomer') {
        		$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, 'editcustomer', '', 1, 0, 0, array(), 1);
        	} else {
        		$morehtmlref .= $form->form_thirdparty($url_page_current.'?track_id='.$object->track_id, $object->socid, 'none', '', 1, 0, 0, array(), 1);
        	}
        }

        // Project
        if (!empty($conf->projet->enabled))
        {
        	$langs->load("projects");
        	$morehtmlref .= '<br>'.$langs->trans('Project').' ';
        	if ($user->rights->ticket->write)
        	{
        		if ($action != 'classify') {
        			//$morehtmlref.='<a class="editfielda" href="' . $_SERVER['PHP_SELF'] . '?action=classify&amp;id=' . $object->id . '">' . img_edit($langs->transnoentitiesnoconv('SetProject')) . '</a>';
					$morehtmlref .= ' : ';
				}
        		if ($action == 'classify') {
        			//$morehtmlref.=$form->form_project($_SERVER['PHP_SELF'] . '?id=' . $object->id, $object->socid, $object->fk_project, 'projectid', 0, 0, 1, 1);
        			$morehtmlref .= '<form method="post" action="'.$_SERVER['PHP_SELF'].'?id='.$object->id.'">';
        			$morehtmlref .= '<input type="hidden" name="action" value="classin">';
        			$morehtmlref .= '<input type="hidden" name="token" value="'.newToken().'">';
        			$morehtmlref .= $formproject->select_projects($object->socid, $object->fk_project, 'projectid', 0, 0, 1, 0, 1, 0, 0, '', 1);
        			$morehtmlref .= '<input type="submit" class="button valignmiddle" value="'.$langs->trans("Modify").'">';
        			$morehtmlref .= '</form>';
        		} else {
        			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, 'none', 0, 0, 0, 1);
        		}
        	} else {
        		if (!empty($object->fk_project)) {
        			$proj = new Project($db);
        			$proj->fetch($object->fk_project);
        			$morehtmlref .= $proj->getNomUrl(1);
        		} else {
        			$morehtmlref .= '';
        		}
        	}
        }

        $morehtmlref .= '</div>';

        $linkback = '<a href="'.dol_buildpath('/ticket/list.php', 1).'"><strong>'.$langs->trans("BackToList").'</strong></a> ';

        dol_banner_tab($object, 'ref', $linkback, ($user->socid ? 0 : 1), 'ref', 'ref', $morehtmlref, $param, 0, '', '', 1, '');

        dol_fiche_end();

        //print '<br>';

        $permission = $user->rights->ticket->write;

        // Contacts lines (modules that overwrite templates must declare this into descriptor)
        $dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl'));
        foreach ($dirtpls as $reldir) {
            $res = @include dol_buildpath($reldir.'/contacts.tpl.php');
            if ($res) {
            	break;
            }
        }
    } else {
        print "ErrorRecordNotFound";
    }
}

// End of page
llxFooter();
$db->close();
