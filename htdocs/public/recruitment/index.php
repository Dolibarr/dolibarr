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
 *       \file       htdocs/public/recruitment/index.php
 *       \ingroup    recruitment
 *       \brief      Public file to list jobs
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
if (!defined('NOIPCHECK'))		define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip
if (!defined('NOBROWSERNOTIF')) define('NOBROWSERNOTIF', '1');
// If this page is public (can be called outside logged session)

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/class/recruitmentjobposition.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("companies", "other", "recruitment"));

// Get parameters
$action = GETPOST('action', 'aZ09');
$email = GETPOST('email', 'alpha');

$object = new RecruitmentJobPosition($db);




/*
 * Actions
 */

// None



/*
 * View
 */

$form = new Form($db);
$user_assign = new User($db);
$user_create = new User($db);

if (!$conf->global->RECRUITMENT_ENABLE_PUBLIC_INTERFACE) {
	$langs->load("errors");
	print '<div class="error">'.$langs->trans('ErrorPublicInterfaceNotEnabled').'</div>';
	$db->close();
	exit();
}

$arrayofjs = array();
$arrayofcss = array();

llxHeaderTicket($langs->trans("Jobs"), "", 0, 0, $arrayofjs, $arrayofcss);


print '<div class="ticketpublicarealist">';

$display_ticket_list = 1;

print '<br>';
if ($display_ticket_list) {
	// Filters
	$search_fk_status = GETPOST("search_fk_status", 'alpha');
	$search_subject = GETPOST("search_subject", 'alpha');
	$search_type = GETPOST("search_type", 'alpha');
	$search_category = GETPOST("search_category", 'alpha');
	$search_severity = GETPOST("search_severity", 'alpha');
	$search_fk_user_create = GETPOST("search_fk_user_create", 'int');
	$search_fk_user_assign = GETPOST("search_fk_user_assign", 'int');

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
	$param = 'action=viewlist';

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
		//'t.fk_contract' => array('label' => $langs->trans("Contract"), 'checked' => 0),
		't.fk_user_create' => array('label' => $langs->trans("Author"), 'checked' => 1),
		't.fk_user_assign' => array('label' => $langs->trans("AssignedTo"), 'checked' => 0),

		//'t.entity'=>array('label'=>$langs->trans("Entity"), 'checked'=>1, 'enabled'=>(! empty($conf->multicompany->enabled) && empty($conf->multicompany->transverse_mode))),
		//'t.datec' => array('label' => $langs->trans("DateCreation"), 'checked' => 0, 'position' => 500),
		//'t.tms' => array('label' => $langs->trans("DateModificationShort"), 'checked' => 0, 'position' => 2)
	//'t.statut'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
	);

	// Extra fields
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
		foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
			if ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate') {
				$arrayfields["ef.".$key] = array('label' => $extrafields->attributes[$object->table_element]['label'][$key], 'checked' => ($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1, 'position' => $extrafields->attributes[$object->table_element]['pos'][$key], 'enabled' =>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3) && $extrafields->attributes[$object->table_element]['perms'][$key]);
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
			$param .= '&search_fk_user_assign='.urlencode($search_fk_user_assign);
		}
	}
	if (!empty($search_fk_user_create)) {
		// -1 value = all so no filter
		if ($search_fk_user_create > 0) {
			$filter['t.fk_user_create'] = $search_fk_user_create;
			$param .= '&search_fk_user_create='.urlencode($search_fk_user_create);
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

	$sortfield = GETPOST("sortfield", 'alpha');
	$sortorder = GETPOST("sortorder", 'alpha');

	if (!$sortfield) {
		$sortfield = 't.datec';
	}
	if (!$sortorder) {
		$sortorder = 'DESC';
	}

	$limit = $conf->liste_limit;

	$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
	if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
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
			$sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=t.fk_soc";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as uc ON uc.rowid=t.fk_user_create";
	if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."recruitment_recruitmentjobposition_extrafields as ef on (t.rowid = ef.fk_object)";
	}
	$sql .= " WHERE t.entity IN (".getEntity('recruitmentjobposition').")";
	// Manage filter
	if (!empty($filter)) {
		foreach ($filter as $key => $value) {
			if (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
				$sql .= ' AND '.$key.' = \''.$value.'\'';
			} elseif ($key == 't.fk_statut') {
				if (is_array($value) && count($value) > 0) {
					$sql .= 'AND '.$key.' IN ('.implode(',', $value).')';
				} else {
					$sql .= ' AND '.$key.' = '.$db->escape($value);
				}
			} else {
				$sql .= ' AND '.$key.' LIKE \'%'.$value.'%\'';
			}
		}
	}
	$sql .= " ORDER BY ".$sortfield.' '.$sortorder;

	$resql = $db->query($sql);
	if ($resql) {
		$num_total = $db->num_rows($resql);
		if (!empty($limit)) {
			$sql .= ' '.$db->plimit($limit + 1, $offset);
		}

		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			print_barre_liste($langs->trans('JobList'), $page, 'public/recruitment/list.php', $param, $sortfield, $sortorder, '', $num, $num_total, 'ticket');

			// Search bar
			print '<form method="get" action="'.$url_form.'" id="searchFormList" >'."\n";
			print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
			print '<input type="hidden" name="action" value="viewlist">';
			print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
			print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';

			$varpage = empty($contextpage) ? $url_page_current : $contextpage;
			$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

			print '<table class="liste '.($moreforfilter ? "listwithfilterbefore" : "").'">';

			// Filter bar
			print '<tr class="liste_titre">';

			if (!empty($arrayfields['t.datec']['checked'])) {
				print '<td class="liste_titre"></td>';
			}

			if (!empty($arrayfields['t.ref']['checked'])) {
				print '<td class="liste_titre"></td>';
			}

			if (!empty($arrayfields['t.fk_user_create']['checked'])) {
				print '<td class="liste_titre"></td>';
			}

			if (!empty($arrayfields['t.tms']['checked'])) {
				print '<td class="liste_titre"></td>';
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

			// Fields from hook
			$parameters = array('arrayfields'=>$arrayfields);
			$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			// Status
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
			if (!empty($arrayfields['t.ref']['checked'])) {
				print_liste_field_titre($arrayfields['t.ref']['label'], $url_page_current, 't.ref', '', $param, '', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['t.fk_user_create']['checked'])) {
				print_liste_field_titre($arrayfields['t.fk_user_create']['label'], $url_page_current, 't.fk_user_create', '', $param, '', $sortfield, $sortorder);
			}
			if (!empty($arrayfields['t.tms']['checked'])) {
				print_liste_field_titre($arrayfields['t.tms']['label'], $url_page_current, 't.tms', '', $param, '', $sortfield, $sortorder);
			}

			// Extra fields
			include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

			// Hook fields
			$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
			$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object); // Note that $action and $object may have been modified by hook
			print $hookmanager->resPrint;

			if (!empty($arrayfields['t.fk_statut']['checked'])) {
				print_liste_field_titre($arrayfields['t.fk_statut']['label'], $url_page_current, 't.fk_statut', '', $param, '', $sortfield, $sortorder);
			}
			print_liste_field_titre($selectedfields, $url_page_current, "", '', '', 'align="right"', $sortfield, $sortorder, 'center maxwidthsearch ');
			print '</tr>';

			while ($obj = $db->fetch_object($resql))
			{
				print '<tr class="oddeven">';

				// Date ticket
				if (!empty($arrayfields['t.datec']['checked'])) {
					print '<td>';
					print dol_print_date($db->jdate($obj->datec), 'dayhour');
					print '</td>';
				}

				// Ref
				if (!empty($arrayfields['t.ref']['checked'])) {
					print '<td class="nowraponall">';
					print $obj->ref;
					print '</td>';
				}

				// Message author
				if (!empty($arrayfields['t.fk_user_create']['checked'])) {
					print '<td>';
					if ($obj->fk_user_create > 0) {
						$user_create->firstname = (!empty($obj->user_create_firstname) ? $obj->user_create_firstname : '');
						$user_create->name = (!empty($obj->user_create_lastname) ? $obj->user_create_lastname : '');
						$user_create->id = (!empty($obj->fk_user_create) ? $obj->fk_user_create : '');
						print $user_create->getFullName($langs);
					} else {
						print $langs->trans('Email');
					}
					print '</td>';
				}

				if (!empty($arrayfields['t.tms']['checked'])) {
					print '<td>'.dol_print_date($db->jdate($obj->tms), 'dayhour').'</td>';
				}

				// Extra fields
				if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) {
					foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) {
						if (!empty($arrayfields["ef.".$key]['checked'])) {
							print '<td';
							$align = $extrafields->getAlignFlag($key);
							if ($align) {
								print ' align="'.$align.'"';
							}
							print '>';
							$tmpkey = 'options_'.$key;
							print $extrafields->showOutputField($key, $obj->$tmpkey, '', 1);
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
			print '</form>';

			print '<form method="post" id="form_view" name="form_view" enctype="multipart/form-data" action="'.dol_buildpath('/public/recruitment/view.php', 1).'" style="display:none;">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="view">';
			print '<input type="hidden" name="btn_view_list" value="1">';
			print '<input type="hidden" name="track_id" value="">';
			print '<input type="hidden" name="email" value="">';
			print "</form>";
			print '<script type="text/javascript">
                    function viewticket(ticket_id, email) {
                        var form = $("#form_view");
                        form.submit();
                    }
                </script>';
		}
	}
} else {
	print '<div class="error">Not Allowed<br><a href="'.$_SERVER['PHP_SELF'].'?ref='.$object->ref.'">'.$langs->trans('Back').'</a></div>';
}


print "</div>";

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix, $object);

llxFooter('', 'public');

$db->close();
