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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/public/ticket/list.php
 *       \ingroup    ticket
 *       \brief      Public file to list tickets
 */

if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
// If there is no need to load and show top and left menu
if (!defined("NOLOGIN")) {
	define("NOLOGIN", '1');
}
if (!defined('NOIPCHECK')) {
	define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
}
if (!defined('NOBROWSERNOTIF')) {
	define('NOBROWSERNOTIF', '1');
}
// If this page is public (can be called outside logged session)

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// Because 2 entities can have the same ref.
$entity = (!empty($_GET['entity']) ? (int) $_GET['entity'] : (!empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) {
	define("DOLENTITY", $entity);
}

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "ticket"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');

$track_id = GETPOST('track_id', 'alpha');
$email = strtolower(GETPOST('email', 'alpha'));
$suffix = "";
$moreforfilter = "";

if (GETPOST('btn_view_ticket_list')) {
	unset($_SESSION['track_id_customer']);
	unset($_SESSION['email_customer']);
}
if (isset($_SESSION['track_id_customer'])) {
	$track_id = $_SESSION['track_id_customer'];
}
if (isset($_SESSION['email_customer'])) {
	$email = strtolower($_SESSION['email_customer']);
}

$object = new Ticket($db);

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ticketpubliclist', 'globalcard'));

if (!isModEnabled('ticket')) {
	httponly_accessforbidden('Module Ticket not enabled');
}



/*
 * Actions
 */

if ($cancel) {
	$backtopage = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');

	header("Location: ".$backtopage);
	exit;
}

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
			array_push($object->errors, $langs->trans("ErrorEmailOrTrackingInvalid"));
			$action = '';
		}
	}

	if (!$error) {
		$ret = $object->fetch('', '', $track_id);

		if ($ret && $object->id > 0) {
			// vérifie si l'adresse email est bien dans les contacts du ticket
			$contacts = $object->liste_contact(-1, 'external');
			foreach ($contacts as $contact) {
				if (strtolower($contact['email']) == $email) {
					$display_ticket_list = true;
					$_SESSION['email_customer'] = $email;
					$_SESSION['track_id_customer'] = $track_id;
					break;
				} else {
					$display_ticket_list = false;
				}
			}
			if ($object->fk_soc > 0) {
				$object->fetch_thirdparty();
				if ($email == strtolower($object->thirdparty->email)) {
					$display_ticket_list = true;
					$_SESSION['email_customer'] = $email;
					$_SESSION['track_id_customer'] = $track_id;
				}
			}
			if ($object->fk_user_create > 0) {
				$tmpuser = new User($db);
				$tmpuser->fetch($object->fk_user_create);
				if ($email == strtolower($tmpuser->email)) {
					$display_ticket_list = true;
					$_SESSION['email_customer'] = $email;
					$_SESSION['track_id_customer'] = $track_id;
				}
			}

			$emailorigin = strtolower(CMailFile::getValidAddress($object->origin_email, 2));
			if ($email == $emailorigin) {
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

	if ($error || $errors) {
		setEventMessages($object->error, $object->errors, 'errors');
		$action = '';
	}
}

/*
 * View
 */

$form = new Form($db);
$user_assign = new User($db);
$user_create = new User($db);
$formTicket = new FormTicket($db);

if (!getDolGlobalString('TICKET_ENABLE_PUBLIC_INTERFACE')) {
	print '<div class="error">'.$langs->trans('TicketPublicInterfaceForbidden').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array(getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', '/public/ticket/').'css/styles.css.php');

llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);


if ($action == "view_ticketlist") {
	print '<div class="ticketpublicarealist ticketlargemargin centpercent">';

	print '<br>';
	if ($display_ticket_list) {
		// Filters
		$search_fk_status = GETPOST("search_fk_status", 'alpha');
		$search_subject = GETPOST("search_subject", 'alpha');
		$search_type = GETPOST("search_type", 'alpha');
		$search_category = GETPOST("search_category", 'alpha');
		$search_severity = GETPOST("search_severity", 'alpha');
		$search_fk_user_create = GETPOST("search_fk_user_create", "intcomma");
		$search_fk_user_assign = GETPOST("search_fk_user_assign", "intcomma");

		// Store current page url
		$url_page_current = dol_buildpath('/public/ticket/list.php', 1);

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
		$extrafields->fetch_name_optionals_label($object->table_element);

		$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

		$filter = array();

		$param = '&action=view_ticketlist';
		if (!empty($entity) && isModEnabled('multicompany')) {
			$param .= '&entity='.((int) $entity);
		}

		$param .= '&token='.newToken();

		// Definition of fields for list
		$arrayfields = array(
			't.datec' => array('label' => $langs->trans("Date"), 'checked' => 1),
			't.date_read' => array('label' => $langs->trans("TicketReadOn"), 'checked' => 0),
			't.date_close' => array('label' => $langs->trans("TicketCloseOn"), 'checked' => 0),
			't.ref' => array('label' => $langs->trans("Ref"), 'checked' => 1),
			//'t.track_id' => array('label' => $langs->trans("IDTracking"), 'checked' => 0),
			't.fk_statut' => array('label' => $langs->trans("Status"), 'checked' => 1),
			't.subject' => array('label' => $langs->trans("Subject"), 'checked' => 1),
			'type.code' => array('label' => $langs->trans("Type"), 'checked' => 1),
			'category.code' => array('label' => $langs->trans("Category"), 'checked' => 1),
			'severity.code' => array('label' => $langs->trans("Severity"), 'checked' => 1),
			't.progress' => array('label' => $langs->trans("Progression"), 'checked' => 0),
			't.duration' => array('label' => $langs->trans("TicketDurationAuto"), 'checked' => 1),
			//'t.fk_contract' => array('label' => $langs->trans("Contract"), 'checked' => 0),
			't.fk_user_create' => array('label' => $langs->trans("Author"), 'checked' => 1),
			't.fk_user_assign' => array('label' => $langs->trans("AssignedTo"), 'checked' => 0),

			//'t.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(isModEnabled('multicompany') && empty($conf->multicompany->transverse_mode))),
			//'t.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
			//'t.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 2)
			//'t.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
		);

		if (!getDolGlobalString('TICKET_SHOW_PROGRESSION')) {
			unset($arrayfields['t.progress']);
		}
		if (!getDolGlobalString('TICKET_SHOW_DURATION')) {
			unset($arrayfields['t.duration']);
		}

		// Extra fields
		if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
				if ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate') {
					$enabled = abs((int) dol_eval($extrafields->attributes[$object->table_element]['list'][$key], 1, 1, '2'));
					$enabled = (($enabled == 0 || $enabled == 3) ? 0 : $enabled);
					$arrayfields["ef.".$key] = array('label' => $extrafields->attributes[$object->table_element]['label'][$key], 'checked' => ($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1, 'position' => $extrafields->attributes[$object->table_element]['pos'][$key], 'enabled' => $enabled && $extrafields->attributes[$object->table_element]['perms'][$key]);
				}
			}
		}
		if (!empty($search_subject)) {
			$filter['t.subject'] = $search_subject;
			$param .= '&search_subject='.urlencode($search_subject);
		}
		if (!empty($search_type)) {
			$filter['t.type_code'] = $search_type;
			$param .= '&search_type='.urlencode($search_type);
		}
		if (!empty($search_category)) {
			$filter['t.category_code'] = $search_category;
			$param .= '&search_category='.urlencode($search_category);
		}
		if (!empty($search_severity)) {
			$filter['t.severity_code'] = $search_severity;
			$param .= '&search_severity='.urlencode($search_severity);
		}
		if (!empty($search_fk_user_assign)) {
			// -1 value = all so no filter
			if ($search_fk_user_assign > 0) {
				$filter['t.fk_user_assign'] = $search_fk_user_assign;
				$param .= '&search_fk_user_assign='.urlencode((string) ($search_fk_user_assign));
			}
		}
		if (!empty($search_fk_user_create)) {
			// -1 value = all so no filter
			if ($search_fk_user_create > 0) {
				$filter['t.fk_user_create'] = $search_fk_user_create;
				$param .= '&search_fk_user_create='.urlencode((string) ($search_fk_user_create));
			}
		}
		if ((isset($search_fk_status) && $search_fk_status != '') && $search_fk_status != '-1' && $search_fk_status != 'non_closed') {
			$filter['t.fk_statut'] = $search_fk_status;
			$param .= '&search_fk_status='.urlencode($search_fk_status);
		}
		if (isset($search_fk_status) && $search_fk_status == 'non_closed') {
			$filter['t.fk_statut'] = array(0, 1, 3, 4, 5, 6);
			$param .= '&search_fk_status=non_closed';
		}

		require DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

		$sortfield = GETPOST('sortfield', 'aZ09comma');
		$sortorder = GETPOST('sortorder', 'aZ09comma');

		if (!$sortfield) {
			$sortfield = 't.datec';
		}
		if (!$sortorder) {
			$sortorder = 'DESC';
		}

		$limit = $conf->liste_limit;

		$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
		if (empty($page) || $page == -1) {
			$page = 0;
		}     // If $page is not defined, or '' or -1
		$offset = $limit * $page;
		$pageprev = $page - 1;
		$pagenext = $page + 1;

		// Request SQL
		$sql = "SELECT DISTINCT";
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
		if (getDolGlobalString('TICKET_SHOW_PROGRESSION')) {
			$sql .= " t.progress,";
		}
		if (getDolGlobalString('TICKET_SHOW_DURATION')) {
			$sql .= " t.duration,";
		}
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
		if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
				$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
			}
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code = t.type_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code = t.category_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code = t.severity_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as uc ON uc.rowid = t.fk_user_create";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as ua ON ua.rowid = t.fk_user_assign";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.element_id = t.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_contact as tc ON ec.fk_c_type_contact = tc.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople sp ON ec.fk_socpeople = sp.rowid";
		if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."ticket_extrafields as ef on (t.rowid = ef.fk_object)";
		}
		$sql .= " WHERE t.entity IN (".getEntity('ticket').")";
		$sql .= " AND ((tc.source = 'external'";
		$sql .= " AND tc.element='".$db->escape($object->element)."'";
		$sql .= " AND tc.active=1";
		$sql .= " AND sp.email='".$db->escape($_SESSION['email_customer'])."')";		// email found into an external contact
		$sql .= " OR s.email='".$db->escape($_SESSION['email_customer'])."'";			// or email of the linked company
		$sql .= " OR t.origin_email='".$db->escape($_SESSION['email_customer'])."')";	// or email of the requester
		// Manage filter
		if (!empty($filter)) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
					$sql .= " AND ".$key." = '".$db->escape($value)."'";
				} elseif (($key == 't.fk_user_assign') || ($key == 't.type_code') || ($key == 't.category_code') || ($key == 't.severity_code')) {
					$sql .= " AND ".$key." = '".$db->escape($value)."'";
				} elseif ($key == 't.fk_statut') {
					if (is_array($value) && count($value) > 0) {
						$sql .= " AND ".$key." IN (".$db->sanitize(implode(',', $value)).")";
					} else {
						$sql .= " AND ".$key." = ".((int) $value);
					}
				} else {
					$sql .= " AND ".$key." LIKE '%".$db->escape($value)."%'";
				}
			}
		}
		//$sql .= " GROUP BY t.track_id";
		$sql .= $db->order($sortfield, $sortorder);

		$resql = $db->query($sql);
		if ($resql) {
			$num_total = $db->num_rows($resql);
			if (!empty($limit)) {
				$sql .= $db->plimit($limit + 1, $offset);
			}

			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);

				$baseurl = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', DOL_URL_ROOT.'/public/ticket/');

				$newcardbutton = '<a class="marginrightonly" href="'.$baseurl . 'create_ticket.php?action=create'.(!empty($entity) && isModEnabled('multicompany')?'&entity='.$entity:'').'&token='.newToken().'" rel="nofollow noopener"><span class="fa fa-15 fa-plus-circle valignmiddle btnTitle-icon" title="'.dol_escape_htmltag($langs->trans("CreateTicket")).'"></span></a>';

				print_barre_liste($langs->trans('TicketList'), $page, 'list.php', $param, $sortfield, $sortorder, '', $num, $num_total, 'ticket', 0, $newcardbutton);

				// Search bar
				print '<form method="POST" action="'.$_SERVER['PHP_SELF'].(!empty($entity) && isModEnabled('multicompany') ? '?entity='.$entity : '').'" id="searchFormList" >'."\n";
				print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
				print '<input type="hidden" name="action" value="view_ticketlist">';
				print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
				print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

				$varpage = empty($contextpage) ? $url_page_current : $contextpage;
				$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

				// allow to display information before list
				$parameters = array('arrayfields' => $arrayfields);
				$reshook = $hookmanager->executeHooks('printFieldListHeader', $parameters, $object, $action);    // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				print '<div class="div-table-responsive">';
				print '<table class="liste '.($moreforfilter ? "listwithfilterbefore" : "").'">';

				// Filter bar
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

				if (!empty($arrayfields['t.subject']['checked'])) {
					print '<td class="liste_titre">';
					print '<input type="text" class="flat maxwidth100" name="search_subject" value="'.$search_subject.'">';
					print '</td>';
				}

				if (!empty($arrayfields['type.code']['checked'])) {
					print '<td class="liste_titre">';
					$formTicket->selectTypesTickets($search_type, 'search_type', '', 2, 1, 1, 0, 'maxwidth150');
					print '</td>';
				}

				if (!empty($arrayfields['category.code']['checked'])) {
					print '<td class="liste_titre">';
					$formTicket->selectGroupTickets($search_category, 'search_category', '(public:=:1)', 2, 1, 1, 0, 'maxwidth150');
					print '</td>';
				}

				if (!empty($arrayfields['severity.code']['checked'])) {
					print '<td class="liste_titre">';
					$formTicket->selectSeveritiesTickets($search_severity, 'search_severity', '', 2, 1, 1, 0, 'maxwidth150');
					print '</td>';
				}

				if (getDolGlobalString('TICKET_SHOW_PROGRESSION') && !empty($arrayfields['t.progress']['checked'])) {
					print '<td class="liste_titre"></td>';
				}

				if (getDolGlobalString('TICKET_SHOW_DURATION') && !empty($arrayfields['t.duration']['checked'])) {
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
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

				// Fields from hook
				$parameters = array('arrayfields' => $arrayfields);
				$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				// Status ticket
				if (!empty($arrayfields['t.fk_statut']['checked'])) {
					print '<td class="liste_titre">';
					$selected = ($search_fk_status != "non_closed" ? $search_fk_status : '');
					//$object->printSelectStatus($selected);
					print '</td>';
				}

				// Action column
				print '<td class="liste_titre maxwidthsearch">';
				$searchpicto = $form->showFilterButtons();
				print $searchpicto;
				print '</td>';
				print '</tr>';

				// Field title
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
				if (getDolGlobalString('TICKET_SHOW_PROGRESSION') && !empty($arrayfields['t.progress']['checked'])) {
					print_liste_field_titre($arrayfields['t.progress']['label'], $url_page_current, 't.progress', '', $param, '', $sortfield, $sortorder);
				}
				if (getDolGlobalString('TICKET_SHOW_DURATION') && !empty($arrayfields['t.duration']['checked'])) {
					print_liste_field_titre($arrayfields['t.duration']['label'], $url_page_current, 't.duration', '', $param, '', $sortfield, $sortorder);
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
				include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

				// Hook fields
				$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
				$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
				print $hookmanager->resPrint;

				if (!empty($arrayfields['t.fk_statut']['checked'])) {
					print_liste_field_titre($arrayfields['t.fk_statut']['label'], $url_page_current, 't.fk_statut', '', $param, '', $sortfield, $sortorder);
				}
				print_liste_field_titre($selectedfields, $url_page_current, "", '', '', 'align="right"', $sortfield, $sortorder, 'center maxwidthsearch ');
				print '</tr>';

				while ($obj = $db->fetch_object($resql)) {
					print '<tr class="oddeven">';

					// Date ticket
					if (!empty($arrayfields['t.datec']['checked'])) {
						print '<td>';
						print dol_print_date($db->jdate($obj->datec), 'dayhour', 'tzuserrel');
						print '</td>';
					}

					// Date read
					if (!empty($arrayfields['t.date_read']['checked'])) {
						print '<td>';
						print dol_print_date($db->jdate($obj->date_read), 'dayhour', 'tzuserrel');
						print '</td>';
					}

					// Date close
					if (!empty($arrayfields['t.date_close']['checked'])) {
						print '<td>';
						print dol_print_date($db->jdate($obj->date_close), 'dayhour', 'tzuserrel');
						print '</td>';
					}

					// Ref
					if (!empty($arrayfields['t.ref']['checked'])) {
						print '<td class="nowraponall">';
						print '<a rel="nofollow" href="javascript:viewticket(\''.dol_escape_js($obj->track_id).'\',\''.dol_escape_js($_SESSION['email_customer']).'\');">';
						print img_picto('', 'ticket', 'class="paddingrightonly"');
						print $obj->ref;
						print '</a>';
						print '</td>';
					}

					// Subject
					if (!empty($arrayfields['t.subject']['checked'])) {
						print '<td>';
						print '<a rel="nofollow" href="javascript:viewticket(\''.dol_escape_js($obj->track_id).'\',\''.dol_escape_js($_SESSION['email_customer']).'\');">';
						print $obj->subject;
						print '</a>';
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
					if (getDolGlobalString('TICKET_SHOW_PROGRESSION') && !empty($arrayfields['t.progress']['checked'])) {
						print '<td>';
						print $obj->progress;
						print '</td>';
					}

					// Duration (Sum of linked fichinter)
					if (getDolGlobalString('TICKET_SHOW_DURATION') && !empty($arrayfields['t.duration']['checked'])) {
						print '<td>';
						print (isset($obj->duration) ? convertSecondToTime($obj->duration, 'all', getDolGlobalString('MAIN_DURATION_OF_WORKDAY')) : '');
						print '</td>';
					}

					// Message author
					if (!empty($arrayfields['t.fk_user_create']['checked'])) {
						print '<td title="'.dol_escape_htmltag($obj->origin_email).'">';
						if ($obj->fk_user_create > 0) {
							$user_create->firstname = (!empty($obj->user_create_firstname) ? $obj->user_create_firstname : '');
							$user_create->name = (!empty($obj->user_create_lastname) ? $obj->user_create_lastname : '');
							$user_create->id = (!empty($obj->fk_user_create) ? $obj->fk_user_create : '');
							print $user_create->getFullName($langs);
						} else {
							print img_picto('', 'email', 'class="paddingrightonly"');
							print $langs->trans('Email');
						}
						print '</td>';
					}

					// Assigned author
					if (!empty($arrayfields['t.fk_user_assign']['checked'])) {
						print '<td>';
						if ($obj->fk_user_assign > 0) {
							$user_assign->firstname = (!empty($obj->user_assign_firstname) ? $obj->user_assign_firstname : '');
							$user_assign->lastname = (!empty($obj->user_assign_lastname) ? $obj->user_assign_lastname : '');
							$user_assign->id = (!empty($obj->fk_user_assign) ? $obj->fk_user_assign : '');
							print img_picto('', 'user', 'class="paddingrightonly"');
							print $user_assign->getFullName($langs);
						}
						print '</td>';
					}

					if (!empty($arrayfields['t.tms']['checked'])) {
						print '<td>'.dol_print_date($db->jdate($obj->tms), 'dayhour').'</td>';
					}

					// Extra fields
					if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
						foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
							if (!empty($arrayfields["ef.".$key]['checked'])) {
								print '<td';
								$cssstring = $extrafields->getAlignFlag($key, $object->table_element);
								if ($cssstring) {
									print ' class="'.$cssstring.'"';
								}
								print '>';
								$tmpkey = 'options_'.$key;
								print $extrafields->showOutputField($key, $obj->$tmpkey, '', $object->table_element);
								print '</td>';
							}
						}
					}

					// Statut
					if (!empty($arrayfields['t.fk_statut']['checked'])) {
						print '<td class="nowraponall">';
						$object->fk_statut = $obj->fk_statut;
						print $object->getLibStatut(2);
						print '</td>';
					}

					print '<td></td>';

					$i++;
					print '</tr>';
				}

				print '</table>';
				print '</div>';

				print '</form>';

				$url_public_ticket = getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE', dol_buildpath('/public/ticket/', 1));

				print '<form method="POST" id="form_view_ticket" name="form_view_ticket" action="'.$url_public_ticket.'view.php'.(!empty($entity) && isModEnabled('multicompany')?'?entity='.$entity:'').'" style="display:none;">';
				print '<input type="hidden" name="token" value="'.newToken().'">';
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
		} else {
			dol_print_error($db);
		}
	} else {
		print '<div class="error">Not Allowed<br><a href="'.$_SERVER['PHP_SELF'].'?track_id='.$object->track_id.'">'.$langs->trans('Back').'</a></div>';
	}

	print '</div>';
} else {
	print '<div class="ticketpublicarea ticketlargemargin centpercent">';

	print '<p class="center opacitymedium">'.$langs->trans("TicketPublicMsgViewLogIn").'</p>';
	print '<br>';

	print '<div id="form_view_ticket">';
	print '<form method="POST" class="maxwidth1000 center" name="form_view_ticketlist" action="'.$_SERVER['PHP_SELF'].(!empty($entity) && isModEnabled('multicompany') ? '?entity='.$entity : '').'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="view_ticketlist">';
	//print '<input type="hidden" name="search_fk_status" value="non_closed">';

	print '<p><label for="track_id" style="display: inline-block" class="titlefieldcreate left"><span class="fieldrequired">';
	print img_picto($langs->trans("TicketTrackId"), 'generic', 'class="pictofixedwidth"');
	print $langs->trans("OneOfTicketTrackId");
	print '</span></label>';
	print '<br class="showonsmartphone hidden">';
	print '<input class="minwidth100" id="track_id" name="track_id" value="'.(GETPOST('track_id', 'alpha') ? GETPOST('track_id', 'alpha') : '').'" />';
	print '</p>';

	print '<p><label for="email" style="display: inline-block" class="titlefieldcreate left"><span class="fieldrequired">';
	print img_picto($langs->trans("Email"), 'email', 'class="pictofixedwidth"');
	print $langs->trans('Email').'</span></label>';
	print '<br class="showonsmartphone hidden">';
	print '<input class="minwidth100" id="email" name="email" value="'.(GETPOST('email', 'alpha') ? GETPOST('email', 'alpha') : (!empty($_SESSION['customer_email']) ? $_SESSION['customer_email'] : "")).'" />';
	print '</p>';

	print '<p style="text-align: center; margin-top: 1.5em;">';
	print '<input type="submit" class="button button-select" name="btn_view_ticket_list" value="'.$langs->trans('ViewMyTicketList').'" />';
	print ' &nbsp; ';
	print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
	print "</p>\n";

	print "</form>\n";
	print "</div>\n";

	print "</div>";
}

if (getDolGlobalInt('TICKET_SHOW_COMPANY_FOOTER')) {
	// End of page
	htmlPrintOnlineFooter($mysoc, $langs, 0, $suffix, $object);
}

llxFooter('', 'public');

$db->close();
