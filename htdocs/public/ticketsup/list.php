<?php
/*  Copyright (C) 2013-2016    Jean-François FERRY    <jfefe@aternatik.fr>
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
 *       \file       ticketsup/public/index.php
 *        \ingroup    ticketsup
 *        \brief      Public file to add and manage ticket
 */

if (!defined('NOCSRFCHECK')) {
    define('NOCSRFCHECK', '1');
}
// Do not check anti CSRF attack test
if (!defined('NOREQUIREMENU')) {
    define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
    define("NOLOGIN", '1');
}
// If this page is public (can be called outside logged session)

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticketsup/class/actions_ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticketsup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticketsup.lib.php';

// Load traductions files requiredby by page
$langs->loadLangs(array("companies","other","ticketsup"));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action = GETPOST('action', 'alpha', 3);
$email = GETPOST('email', 'alpha');

if (GETPOST('btn_view_ticket_list')) {
    unset($_SESSION['track_id_customer']);
    unset($_SESSION['email_customer']);
}
if (isset($_SESSION['track_id_customer'])) {
    $track_id = $_SESSION['track_id_customer'];
}
if (isset($_SESSION['email_customer'])) {
    $email = $_SESSION['email_customer'];
}

$object = new ActionsTicketsup($db);

if ($action == "view_ticketlist") {
    $error = 0;
    $display_ticket_list = false;
    if (!strlen($track_id)) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("TicketTrackId")));
        $action = '';
    }

    if (!strlen($email)) {
        $error++;
        array_push($object->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Email")));
        $action = '';
    } else {
        if (!isValidEmail($email)) {
            $error++;
            array_push($object->errors, $langs->trans("ErrorEmailInvalid"));
            $action = '';
        }
    }

    if (!$error) {
        $ret = $object->fetch('', $track_id);
        if ($ret && $object->dao->id > 0) {
            // vérifie si l'adresse email est bien dans les contacts du ticket
            $contacts = $object->dao->liste_contact(-1, 'external');
            foreach ($contacts as $contact) {
                if ($contact['email'] == $email) {
                    $display_ticket_list = true;
                    $_SESSION['email_customer'] = $email;
                    $_SESSION['track_id_customer'] = $track_id;
                    break;
                } else {
                    $display_ticket_list = false;
                }
            }

            if ($object->dao->fk_soc > 0) {
                $object->dao->fetch_thirdparty();
            }

            if ($email == $object->dao->origin_email || $email == $object->dao->thirdparty->email) {
                $display_ticket_list = true;
                $_SESSION['email_customer'] = $email;
                $_SESSION['track_id_customer'] = $track_id;
            }
        } else {
            $error++;
            array_push($object->errors, $langs->trans("ErrorTicketNotFound", $track_id));
            $action = '';
        }
    }

    if ($error) {
        setEventMessage($object->errors, 'errors');
        $action = '';
    }
}
$object->doActions($action);



/*
 * View
 */

$form = new Form($db);
$user_assign = new User($db);
$user_create = new User($db);
$formticket = new FormTicketsup($db);

$arrayofjs = array();
$arrayofcss = array('/ticketsup/css/styles.css.php');
llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);

if (!$conf->global->TICKETS_ENABLE_PUBLIC_INTERFACE) {
    print '<div class="error">' . $langs->trans('TicketPublicInterfaceForbidden') . '</div>';
    $db->close();
    exit();
}

print '<div style="margin: 0 auto; width:60%">';

if ($action == "view_ticketlist") {
    if ($display_ticket_list) {
        // Filters
        $search_fk_status = GETPOST("search_fk_status", 'alpha');
        $search_subject = GETPOST("search_subject");
        $search_type = GETPOST("search_type", 'alpha');
        $search_category = GETPOST("search_category", 'alpha');
        $search_severity = GETPOST("search_severity", 'alpha');
        $search_fk_user_create = GETPOST("search_fk_user_create", 'int');
        $search_fk_user_assign = GETPOST("search_fk_user_assign", 'int');

        // Store current page url
        $url_page_current = dol_buildpath('/ticketsup/public/list.php', 1);

        // Do we click on purge search criteria ?
        if (GETPOST("button_removefilter_x")) {
            $search_fk_status = '';
            $search_subject = '';
            $search_type = '';
            $search_category = '';
            $search_severity = '';
            $search_fk_user_create = '';
            $search_fk_user_assign = '';
        }

        // fetch optionals attributes and labels
        $extrafields = new ExtraFields($db);
        $extralabels = $extrafields->fetch_name_optionals_label('ticketsup');
        $search_array_options = $extrafields->getOptionalsFromPost($extralabels, '', 'search_');

        $filter = array();
        $param = '';

        // Definition of fields for list
        $arrayfields = array(
            't.datec' => array('label' => $langs->trans("Date"), 'checked' => 1),
            't.date_read' => array('label' => $langs->trans("TicketReadOn"), 'checked' => 0),
            't.date_close' => array('label' => $langs->trans("TicketCloseOn"), 'checked' => 0),
            't.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
            't.fk_statut' => array('label' => $langs->trans("Statut"), 'checked' => 1),
            't.subject' => array('label' => $langs->trans("Subject"), 'checked' => 1),
            'type.code' => array('label' => $langs->trans("Type"), 'checked' => 1),
            'category.code' => array('label' => $langs->trans("Category"), 'checked' => 1),
            'severity.code' => array('label' => $langs->trans("Severity"), 'checked' => 1),
            't.progress' => array('label' => $langs->trans("Progression"), 'checked' => 0),
            //'t.fk_contract' => array('label' => $langs->trans("Contract"), 'checked' => 0),
            't.fk_user_create' => array('label' => $langs->trans("Author"), 'checked' => 1),
            't.fk_user_assign' => array('label' => $langs->trans("AuthorAssign"), 'checked' => 0),

            //'t.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode))),
            //'t.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
            //'t.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 2)
            //'t.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
        );

        // Extra fields
        if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
        	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
        		if ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate') {
        			$arrayfields["ef." . $key] = array('label' => $extrafields->attributes[$object->table_element]['label'][$key], 'checked' => $extrafields->attributes[$object->table_element]['list'][$key], 'position' => $extrafields->attributes[$object->table_element]['pos'][$key], 'enabled' => $extrafields->attributes[$object->table_element]['perms'][$key]);
                }
            }
        }
        if (!empty($search_subject)) {
            $filter['t.subject'] = $search_subject;
            $param .= '&search_subject=' . $search_subject;
        }
        if (!empty($search_type)) {
            $filter['t.type_code'] = $search_type;
            $param .= '&search_type=' . $search_type;
        }
        if (!empty($search_category)) {
            $filter['t.category_code'] = $search_category;
            $param .= '&search_category=' . $search_category;
        }
        if (!empty($search_severity)) {
            $filter['t.severity_code'] = $search_severity;
            $param .= '&search_severity=' . $search_severity;
        }
        if (!empty($search_fk_user_assign)) {
            // -1 value = all so no filter
            if ($search_fk_user_assign > 0) {
                $filter['t.fk_user_assign'] = $search_fk_user_assign;
                $param .= '&search_fk_user_assign=' . $search_fk_user_assign;
            }
        }
        if (!empty($search_fk_user_create)) {
            // -1 value = all so no filter
            if ($search_fk_user_create > 0) {
                $filter['t.fk_user_create'] = $search_fk_user_create;
                $param .= '&search_fk_user_create=' . $search_fk_user_create;
            }
        }

        if ((isset($search_fk_status) && $search_fk_status != '') && $search_fk_status != '-1' && $search_fk_status != 'non_closed') {
            $filter['t.fk_statut'] = $search_fk_status;
            $param .= '&search_fk_status=' . $search_fk_status;
        }

        if (isset($search_fk_status) && $search_fk_status == 'non_closed') {
            $filter['t.fk_statut'] = array(0, 1, 3, 4, 5, 6);
            $param .= '&search_fk_status=non_closed';
        }

        require DOL_DOCUMENT_ROOT . '/core/actions_changeselectedfields.inc.php';

        $sortfield = GETPOST("sortfield", 'alpha');
        $sortorder = GETPOST("sortorder", 'alpha');

        if (!$sortfield) {
            $sortfield = 't.datec';
        }

        if (!$sortorder) {
            $sortorder = 'DESC';
        }

        $limit = $conf->liste_limit;

        $page = GETPOST("page", 'int');
        if ($page == -1) {
            $page = 0;
        }
        $offset = $limit * $page;
        $pageprev = $page - 1;
        $pagenext = $page + 1;

        // Request SQL
        $sql = "SELECT";
        $sql .= " t.rowid,";
        $sql .= " t.ref,";
        $sql .= " t.track_id,";
        $sql .= " t.fk_soc,";
        $sql .= " t.fk_project,";
        $sql .= " t.origin_email,";
        $sql .= " t.fk_user_create, uc.lastname as user_create_lastname, uc.firstname as user_create_firstname,";
        $sql .= " t.fk_user_assign, ua.lastname as user_assign_lastname, ua.firstname as user_assign_firstname,";
        $sql .= " t.subject,";
        $sql .= " t.message,";
        $sql .= " t.fk_statut,";
        $sql .= " t.resolution,";
        $sql .= " t.progress,";
        $sql .= " t.timing,";
        $sql .= " t.type_code,";
        $sql .= " t.category_code,";
        $sql .= " t.severity_code,";
        $sql .= " t.datec,";
        $sql .= " t.date_read,";
        $sql .= " t.date_close,";
        $sql .= " t.tms,";
        $sql .= " type.label as type_label, category.label as category_label, severity.label as severity_label";
        // Add fields for extrafields
        if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
        	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
        		$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef." . $key . ' as options_' . $key : '');
        }
        $sql .= " FROM " . MAIN_DB_PREFIX . "ticketsup as t";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticketsup_type as type ON type.code=t.type_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticketsup_category as category ON category.code=t.category_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticketsup_severity as severity ON severity.code=t.severity_code";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON s.rowid=t.fk_soc";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as uc ON uc.rowid=t.fk_user_create";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "user as ua ON ua.rowid=t.fk_user_assign";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "element_contact as ec ON ec.element_id=t.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_type_contact as tc ON ec.fk_c_type_contact=tc.rowid";
        $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "socpeople sp ON ec.fk_socpeople=sp.rowid";
        if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
            $sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "ticketsup_extrafields as ef on (t.rowid = ef.fk_object)";
        }
        $sql .= " WHERE t.entity IN (" . getEntity('ticketsup') . ")";
        $sql .= " AND tc.source = 'external'";
        $sql .= " AND tc.element='" . $object->dao->element . "'";
        $sql .= " AND tc.active=1";
        $sql .= " AND (sp.email='" . $db->escape($_SESSION['email_customer']) . "'";
        $sql .= " OR s.email='" . $db->escape($_SESSION['email_customer']) . "'";
        $sql .= " OR t.origin_email='" . $db->escape($_SESSION['email_customer']) . "')";
        // Manage filter
        if (!empty($filter)) {
            foreach ($filter as $key => $value) {
                if (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
                    $sql .= ' AND ' . $key . ' = \'' . $value . '\'';
                } elseif (($key == 't.fk_user_assign') || ($key == 't.type_code') || ($key == 't.category_code') || ($key == 't.severity_code')) {
                    $sql .= " AND " . $key . " = '" . $db->escape($value) ."'";
                } elseif ($key == 't.fk_statut') {
                    if (is_array($value) && count($value) > 0) {
                        $sql .= 'AND ' . $key . ' IN (' . implode(',', $value) . ')';
                    } else {
                        $sql .= ' AND ' . $key . ' = ' . $db->escape($value);
                    }
                } else {
                    $sql .= ' AND ' . $key . ' LIKE \'%' . $value . '%\'';
                }
            }
        }
        $sql .= " GROUP BY t.track_id";
        $sql .= " ORDER BY " . $sortfield . ' ' . $sortorder;

        $resql = $db->query($sql);
        if ($resql) {
            $num_total = $db->num_rows($resql);
            if (!empty($limit)) {
                $sql .= ' ' . $db->plimit($limit + 1, $offset);
            }

            $resql = $db->query($sql);
            if ($resql) {
                $num = $db->num_rows($resql);
                print_barre_liste($langs->trans('TicketList'), $page, 'public/list.php', $param, $sortfield, $sortorder, '', $num, $num_total, 'ticketsup-32@ticketsup');

                /*
                * Search bar
                */
                print '<form method="get" action="' . $url_form . '" id="searchFormList" >' . "\n";
                print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
                print '<input type="hidden" name="action" value="view_ticketlist">';
                print '<input type="hidden" name="sortfield" value="' . $sortfield . '">';
                print '<input type="hidden" name="sortorder" value="' . $sortorder . '">';

                $varpage = empty($contextpage) ? $url_page_current : $contextpage;
                $selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

                print '<table class="liste ' . ($moreforfilter ? "listwithfilterbefore" : "") . '">';

                print '<tr class="liste_titre">';
                if (!empty($arrayfields['t.datec']['checked'])) {
                    print_liste_field_titre($arrayfields['t.datec']['label'], $url_page_current, 't.datec', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.date_read']['checked'])) {
                    print_liste_field_titre($arrayfields['t.date_read']['label'], $url_page_current, 't.date_read', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.date_close']['checked'])) {
                    print_liste_field_titre($arrayfields['t.date_close']['label'], $url_page_current, 't.date_close', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.ref']['checked'])) {
                    print_liste_field_titre($arrayfields['t.ref']['label'], $url_page_current, 't.ref', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.fk_statut']['checked'])) {
                    print_liste_field_titre($arrayfields['t.fk_statut']['label'], $url_page_current, 't.fk_statut', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.subject']['checked'])) {
                    print_liste_field_titre($arrayfields['t.subject']['label']);
                }
                if (!empty($arrayfields['type.code']['checked'])) {
                    print_liste_field_titre($arrayfields['type.code']['label'], $url_page_current, 'type.code', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['category.code']['checked'])) {
                    print_liste_field_titre($arrayfields['category.code']['label'], $url_page_current, 'category.code', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['severity.code']['checked'])) {
                    print_liste_field_titre($arrayfields['severity.code']['label'], $url_page_current, 'severity.code', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.progress']['checked'])) {
                    print_liste_field_titre($arrayfields['t.progress']['label'], $url_page_current, 't.progress', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.fk_user_create']['checked'])) {
                    print_liste_field_titre($arrayfields['t.fk_user_create']['label'], $url_page_current, 't.fk_user_create', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.fk_user_assign']['checked'])) {
                    print_liste_field_titre($arrayfields['t.fk_user_assign']['label'], $url_page_current, 't.fk_user_assign', '', $param, '', $sortfield, $sortorder);
                }
                if (!empty($arrayfields['t.tms']['checked'])) {
                    print_liste_field_titre($arrayfields['t.tms']['label'], $url_page_current, 't.tms', '', $param, '', $sortfield, $sortorder);
                }
                // Extra fields
                if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
                	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                        if (!empty($arrayfields["ef." . $key]['checked'])) {
                            $align = $extrafields->getAlignFlag($key);
                            print_liste_field_titre($extralabels[$key], $url_page_current, "ef." . $key, "", $param, ($align ? 'align="' . $align . '"' : ''), $sortfield, $sortorder);
                        }
                    }
                }
                print_liste_field_titre($selectedfields, $url_page_current, "", '', '', 'align="right"', $sortfield, $sortorder, 'maxwidthsearch ');
                print '</tr>';

                /*
                 * Filter bar
                 */
                $formTicket = new FormTicketsup($db);

                print '<tr class="liste_titre">';

                if (!empty($arrayfields['t.datec']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                if (!empty($arrayfields['t.date_read']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }
                if (!empty($arrayfields['t.date_close']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                if (!empty($arrayfields['t.ref']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                // Status
                if (!empty($arrayfields['t.fk_statut']['checked'])) {
                    print '<td>';
                    $selected = ($search_fk_status != "non_closed" ? $search_fk_status : '');
                    $object->printSelectStatus($selected);
                    print '</td>';
                }

                if (!empty($arrayfields['t.subject']['checked'])) {
                    print '<td class="liste_titre">';
                    print '<input type="text" class="flat" name="search_subject" value="' . $search_subject . '" size="20">';
                    print '</td>';
                }

                if (!empty($arrayfields['type.code']['checked'])) {
                    print '<td class="liste_titre">';
                    $formTicket->selectTypesTickets($search_type, 'search_type', '', 2, 1, 1);
                    print '</td>';
                }

                if (!empty($arrayfields['category.code']['checked'])) {
                    print '<td class="liste_titre">';
                    $formTicket->selectCategoriesTickets($search_category, 'search_category', '', 2, 1, 1);
                    print '</td>';
                }

                if (!empty($arrayfields['severity.code']['checked'])) {
                    print '<td class="liste_titre">';
                    $formTicket->selectSeveritiesTickets($search_severity, 'search_severity', '', 2, 1, 1);
                    print '</td>';
                }

                if (!empty($arrayfields['t.progress']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                if (!empty($arrayfields['t.fk_user_create']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                if (!empty($arrayfields['t.fk_user_assign']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                if (!empty($arrayfields['t.tms']['checked'])) {
                    print '<td class="liste_titre"></td>';
                }

                // Extra fields
                if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
                	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                        if (!empty($arrayfields["ef." . $key]['checked'])) {
                            print '<td class="liste_titre"></td>';
                        }
                    }
                }

                print '<td class="liste_titre" align="right">';
                print '<input type="image" class="liste_titre" name="button_search" src="' . img_picto($langs->trans("Search"), 'search.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("Search")) . '" title="' . dol_escape_htmltag($langs->trans("Search")) . '">';
                print '<input type="image" class="liste_titre" name="button_removefilter" src="' . img_picto($langs->trans("Search"), 'searchclear.png', '', '', 1) . '" value="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '" title="' . dol_escape_htmltag($langs->trans("RemoveFilter")) . '">';
                print '</td>';
                print '</tr>';

                $var = true;
                while ($obj = $db->fetch_object($resql)) {
                    $var = !$var;
                    print "<tr " . $bc[$var] . ">";

                    // Date ticket
                    if (!empty($arrayfields['t.datec']['checked'])) {
                        print '<td>';
                        print dol_print_date($obj->datec, 'dayhour');
                        print '</td>';
                    }

                    // Date read
                    if (!empty($arrayfields['t.date_read']['checked'])) {
                        print '<td>';
                        print dol_print_date($obj->date_read, 'dayhour');
                        print '</td>';
                    }

                    // Date close
                    if (!empty($arrayfields['t.date_close']['checked'])) {
                        print '<td>';
                        print dol_print_date($obj->date_close, 'dayhour');
                        print '</td>';
                    }

                    // ref
                    if (!empty($arrayfields['t.ref']['checked'])) {
                        print '<td>';
                        print $obj->ref;
                        print '</td>';
                    }

                    // Statut
                    if (!empty($arrayfields['t.fk_statut']['checked'])) {
                        print '<td>';
                        $object->fk_statut = $obj->fk_statut;
                        print $object->getLibStatut(2);
                        print '</td>';
                    }

                    // Subject
                    if (!empty($arrayfields['t.subject']['checked'])) {
                        print '<td>';
                        print '<a href="javascript:viewticket(\'' . $obj->track_id . '\',\'' . $_SESSION['email_customer'] . '\');">' . $obj->subject . '</a>';
                        print '</td>';
                    }

                    // Type
                    if (!empty($arrayfields['type.code']['checked'])) {
                        print '<td>';
                        print $obj->type_label;
                        print '</td>';
                    }

                    // Category
                    if (!empty($arrayfields['category.code']['checked'])) {
                        print '<td>';
                        print $obj->category_label;
                        print '</td>';
                    }

                    // Severity
                    if (!empty($arrayfields['severity.code']['checked'])) {
                        print '<td>';
                        print $obj->severity_label;
                        print '</td>';
                    }

                    // Progression
                    if (!empty($arrayfields['t.progress']['checked'])) {
                        print '<td>';
                        print $obj->progress;
                        print '</td>';
                    }

                    // Message author
                    if (!empty($arrayfields['t.fk_user_create']['checked'])) {
                        print '<td>';
                        if ($obj->fk_user_create) {
                            $user_create->firstname = (!empty($obj->user_create_firstname) ? $obj->user_create_firstname : '');
                            $user_create->name = (!empty($obj->user_create_lastname) ? $obj->user_create_lastname : '');
                            $user_create->id = (!empty($obj->fk_user_create) ? $obj->fk_user_create : '');
                            print $user_create->getFullName();
                        } else {
                            print $langs->trans('Email');
                        }
                        print '</td>';
                    }

                    // Assigned author
                    if (!empty($arrayfields['t.fk_user_assign']['checked'])) {
                        print '<td>';
                        if ($obj->fk_user_assign) {
                            $user_assign->firstname = (!empty($obj->user_assign_firstname) ? $obj->user_assign_firstname : '');
                            $user_assign->lastname = (!empty($obj->user_assign_lastname) ? $obj->user_assign_lastname : '');
                            $user_assign->id = (!empty($obj->fk_user_assign) ? $obj->fk_user_assign : '');
                            print $user_assign->getFullName();
                        } else {
                            print $langs->trans('None');
                        }
                        print '</td>';
                    }

                    if (!empty($arrayfields['t.tms']['checked'])) {
                        print '<td>' . dol_print_date($obj->tms, 'dayhour') . '</td>';
                    }

                    // Extra fields
                    if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
                    	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
                            if (!empty($arrayfields["ef." . $key]['checked'])) {
                                print '<td';
                                $align = $extrafields->getAlignFlag($key);
                                if ($align) {
                                    print ' align="' . $align . '"';
                                }
                                print '>';
                                $tmpkey = 'options_' . $key;
                                print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
                                print '</td>';
                            }
                        }
                    }
                    print '<td></td>';
                    $i++;
                    print '</tr>';
                }

                print '</table>';
                print '</form>';

                print '<form method="post" id="form_view_ticket" name="form_view_ticket" enctype="multipart/form-data" action="' . dol_buildpath('/ticketsup/public/view.php', 1) . '" style="display:none;">';
                print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
                print '<input type="hidden" name="action" value="view_ticket">';
                print '<input type="hidden" name="btn_view_ticket_list" value="1">';
                print '<input type="hidden" name="track_id" value="">';
                print '<input type="hidden" name="email" value="">';
                print "</form>";
                print '<script type="text/javascript">
                    function viewticket(ticket_id, email) {
                        var form = $("#form_view_ticket");
                        form.find("input[name=\\"track_id\\"]").val(ticket_id);
                        form.find("input[name=\\"email\\"]").val(email);
                        form.submit();
                    }
                </script>';
            }
        }
    } else {
        print '<div class="error">Not Allowed<br><a href="' . $_SERVER['PHP_SELF'] . '?track_id=' . $object->dao->track_id . '">' . $langs->trans('Back') . '</a></div>';
    }
} else {
    print '<p style="text-align: center">' . $langs->trans("TicketPublicMsgViewLogIn") . '</p>';

    print '<div id="form_view_ticket">';
    print '<form method="post" name="form_view_ticketlist"  enctype="multipart/form-data" action="' . $_SERVER['PHP_SELF'] . '">';
    print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
    print '<input type="hidden" name="action" value="view_ticketlist">';
    print '<input type="hidden" name="search_fk_status" value="non_closed">';

    print '<p><label for="track_id" style="display: inline-block; width: 30%; "><span class="fieldrequired">' . $langs->trans("OneOfTicketTrackId") . '</span></label>';
    print '<input size="30" id="track_id" name="track_id" value="' . (GETPOST('track_id', 'alpha') ? GETPOST('track_id', 'alpha') : '') . '" />';
    print '</p>';

    print '<p><label for="email" style="display: inline-block; width: 30%; "><span class="fieldrequired">' . $langs->trans('Email') . '</span></label>';
    print '<input size="30" id="email" name="email" value="' . (GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : $_SESSION['customer_email']) . '" />';
    print '</p>';

    print '<p style="text-align: center; margin-top: 1.5em;">';
    print '<input class="button" type="submit" name="btn_view_ticket_list" value="' . $langs->trans('ViewMyTicketList') . '" />';
    print "</p>\n";

    print "</form>\n";
    print "</div>\n";
}

// End of page
llxFooter();
$db->close();
