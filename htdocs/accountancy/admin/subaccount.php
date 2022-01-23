<?php
/* Copyright (C) 2013-2016 Olivier Geffroy      <jeff@jeffinfo.com>
 * Copyright (C) 2013-2020 Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2016-2018 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * \file 		htdocs/accountancy/admin/subaccount.php
 * \ingroup     Accountancy (Double entries)
 * \brief		List of accounting sub-account (auxiliary accounts)
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("compta", "bills", "admin", "accountancy", "salaries", "hrm", "errors"));

$mesg = '';
$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'int');
$massaction = GETPOST('massaction', 'aZ09');
$optioncss = GETPOST('optioncss', 'alpha');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'accountingsubaccountlist'; // To manage different context of search

$search_subaccount = GETPOST('search_subaccount', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_type = GETPOST('search_type', 'int');

// Security check
if ($user->socid > 0) {
	accessforbidden();
}
if (empty($user->rights->accounting->chartofaccount)) {
	accessforbidden();
}

// Load variable for pagination
$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "label";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$arrayfields = array(
	'subaccount'=>array('label'=>$langs->trans("AccountNumber"), 'checked'=>1),
	'label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
	'type'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
	'reconcilable'=>array('label'=>$langs->trans("Reconcilable"), 'checked'=>1)
);

if ($conf->global->MAIN_FEATURES_LEVEL < 2) {
	unset($arrayfields['reconcilable']);
}

/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha')) {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	if (!empty($cancel)) {
		$action = '';
	}

	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$search_subaccount = "";
		$search_label = "";
		$search_type = "";
		$search_array_options = array();
	}
}


/*
 * View
 */

$form = new Form($db);

$help_url = '';
$title = $langs->trans('ChartOfIndividualAccountsOfSubsidiaryLedger');

llxHeader('', $title, $help_url);

// Customer
$sql = "SELECT sa.rowid, sa.nom as label, sa.code_compta as subaccount, '1' as type, sa.entity";
$sql .= " FROM ".MAIN_DB_PREFIX."societe sa";
$sql .= " WHERE sa.entity IN (".getEntity('societe').")";
$sql .= " AND sa.code_compta <> ''";
//print $sql;
if (strlen(trim($search_subaccount))) {
	$lengthpaddingaccount = 0;
	if ($conf->global->ACCOUNTING_LENGTH_AACCOUNT) {
		$lengthpaddingaccount = max($conf->global->ACCOUNTING_LENGTH_AACCOUNT);
	}
	$search_subaccount_tmp = $search_subaccount;
	$weremovedsomezero = 0;
	if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
		for ($i = 0; $i < $lengthpaddingaccount; $i++) {
			if (preg_match('/0$/', $search_subaccount_tmp)) {
				$weremovedsomezero++;
				$search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
			}
		}
	}

	//var_dump($search_subaccount); exit;
	if ($search_subaccount_tmp) {
		if ($weremovedsomezero) {
			$search_subaccount_tmp_clean = $search_subaccount_tmp;
			$search_subaccount_clean = $search_subaccount;
			$startchar = '%';
			if (strpos($search_subaccount_tmp, '^') === 0) {
				$startchar = '';
				$search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
				$search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
			}
			$sql .= " AND (sa.code_compta LIKE '".$db->escape($startchar.$search_subaccount_tmp_clean)."'";
			$sql .= " OR sa.code_compta LIKE '".$db->escape($startchar.$search_subaccount_clean)."%')";
		} else {
			$sql .= natural_search("sa.code_compta", $search_subaccount_tmp);
		}
	}
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("sa.nom", $search_label);
}
if (!empty($search_type) && $search_type >= 0) {
	$sql .= " HAVING type LIKE '".$db->escape($search_type)."'";
}

// Supplier
$sql .= " UNION ";
$sql .= " SELECT sa.rowid, sa.nom as label, sa.code_compta_fournisseur as subaccount, '2' as type, sa.entity FROM ".MAIN_DB_PREFIX."societe sa";
$sql .= " WHERE sa.entity IN (".getEntity('societe').")";
$sql .= " AND sa.code_compta_fournisseur <> ''";
//print $sql;
if (strlen(trim($search_subaccount))) {
	$lengthpaddingaccount = 0;
	if ($conf->global->ACCOUNTING_LENGTH_AACCOUNT) {
		$lengthpaddingaccount = max($conf->global->ACCOUNTING_LENGTH_AACCOUNT);
	}
	$search_subaccount_tmp = $search_subaccount;
	$weremovedsomezero = 0;
	if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
		for ($i = 0; $i < $lengthpaddingaccount; $i++) {
			if (preg_match('/0$/', $search_subaccount_tmp)) {
				$weremovedsomezero++;
				$search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
			}
		}
	}

	//var_dump($search_subaccount); exit;
	if ($search_subaccount_tmp) {
		if ($weremovedsomezero) {
			$search_subaccount_tmp_clean = $search_subaccount_tmp;
			$search_subaccount_clean = $search_subaccount;
			$startchar = '%';
			if (strpos($search_subaccount_tmp, '^') === 0) {
				$startchar = '';
				$search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
				$search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
			}
			$sql .= " AND (sa.code_compta_fournisseur LIKE '".$db->escape($startchar.$search_subaccount_tmp_clean)."'";
			$sql .= " OR sa.code_compta_fournisseur LIKE '".$db->escape($startchar.$search_subaccount_clean)."%')";
		} else {
			$sql .= natural_search("sa.code_compta_fournisseur", $search_subaccount_tmp);
		}
	}
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("sa.nom", $search_label);
}
if (!empty($search_type) && $search_type >= 0) {
	$sql .= " HAVING type LIKE '".$db->escape($search_type)."'";
}

// User
$sql .= " UNION ";
$sql .= " SELECT u.rowid, u.lastname as label, u.accountancy_code as subaccount, '3' as type, u.entity FROM ".MAIN_DB_PREFIX."user u";
$sql .= " WHERE u.entity IN (".getEntity('user').")";
$sql .= " AND u.accountancy_code <> ''";
//print $sql;
if (strlen(trim($search_subaccount))) {
	$lengthpaddingaccount = 0;
	if ($conf->global->ACCOUNTING_LENGTH_AACCOUNT) {
		$lengthpaddingaccount = max($conf->global->ACCOUNTING_LENGTH_AACCOUNT);
	}
	$search_subaccount_tmp = $search_subaccount;
	$weremovedsomezero = 0;
	if (strlen($search_subaccount_tmp) <= $lengthpaddingaccount) {
		for ($i = 0; $i < $lengthpaddingaccount; $i++) {
			if (preg_match('/0$/', $search_subaccount_tmp)) {
				$weremovedsomezero++;
				$search_subaccount_tmp = preg_replace('/0$/', '', $search_subaccount_tmp);
			}
		}
	}

	//var_dump($search_subaccount); exit;
	if ($search_subaccount_tmp) {
		if ($weremovedsomezero) {
			$search_subaccount_tmp_clean = $search_subaccount_tmp;
			$search_subaccount_clean = $search_subaccount;
			$startchar = '%';
			if (strpos($search_subaccount_tmp, '^') === 0) {
				$startchar = '';
				$search_subaccount_tmp_clean = preg_replace('/^\^/', '', $search_subaccount_tmp);
				$search_subaccount_clean = preg_replace('/^\^/', '', $search_subaccount);
			}
			$sql .= " AND (u.accountancy_code LIKE '".$db->escape($startchar.$search_subaccount_tmp_clean)."'";
			$sql .= " OR u.accountancy_code LIKE '".$db->escape($startchar.$search_subaccount_clean)."%')";
		} else {
			$sql .= natural_search("u.accountancy_code", $search_subaccount_tmp);
		}
	}
}
if (strlen(trim($search_label))) {
	$sql .= natural_search("u.lastname", $search_label);
}
if (!empty($search_type) && $search_type >= 0) {
	$sql .= " HAVING type LIKE '".$db->escape($search_type)."'";
}

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$resql = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($resql);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

dol_syslog('accountancy/admin/subaccount.php:: $sql='.$sql);
$resql = $db->query($sql);

if ($resql) {
	$num = $db->num_rows($resql);

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($search_subaccount) {
		$param .= '&search_subaccount='.urlencode($search_subaccount);
	}
	if ($search_label) {
		$param .= '&search_label='.urlencode($search_label);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}

	print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'title_accountancy', 0, '', '', $limit, 0, 0, 1);

	print '<div class="info">'.$langs->trans("WarningCreateSubAccounts").'</div>';

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

	$moreforfilter = '';
	$massactionbutton = '';

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Line for search fields
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['subaccount']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat" size="10" name="search_subaccount" value="'.$search_subaccount.'"></td>';
	}
	if (!empty($arrayfields['label']['checked'])) {
		print '<td class="liste_titre"><input type="text" class="flat" size="20" name="search_label" value="'.$search_label.'"></td>';
	}
	if (!empty($arrayfields['type']['checked'])) {
		print '<td class="liste_titre center">'.$form->selectarray('search_type', array('1'=>$langs->trans('Customer'), '2'=>$langs->trans('Supplier'), '3'=>$langs->trans('Employee')), $search_type, 1).'</td>';
	}
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
		if (!empty($arrayfields['reconcilable']['checked'])) {
			print '<td class="liste_titre">&nbsp;</td>';
		}
	}
	print '<td class="liste_titre maxwidthsearch">';
	$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
	print $searchpicto;
	print '</td>';
	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['subaccount']['checked'])) {
		print_liste_field_titre($arrayfields['subaccount']['label'], $_SERVER["PHP_SELF"], "subaccount", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['label']['checked'])) {
		print_liste_field_titre($arrayfields['label']['label'], $_SERVER["PHP_SELF"], "label", "", $param, '', $sortfield, $sortorder);
	}
	if (!empty($arrayfields['type']['checked'])) {
		print_liste_field_titre($arrayfields['type']['label'], $_SERVER["PHP_SELF"], "type", "", $param, '', $sortfield, $sortorder, 'center ');
	}
	if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
		if (!empty($arrayfields['reconcilable']['checked'])) {
			print_liste_field_titre($arrayfields['reconcilable']['label'], $_SERVER["PHP_SELF"], 'reconcilable', '', $param, '', $sortfield, $sortorder, 'center ');
		}
	}
	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$i = 0;
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		// Account number
		if (!empty($arrayfields['subaccount']['checked'])) {
			print "<td>";
			print length_accounta($obj->subaccount);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Subaccount label
		if (!empty($arrayfields['label']['checked'])) {
			print "<td>";
			print $obj->label;
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		// Type
		if (!empty($arrayfields['type']['checked'])) {
			print '<td class="center">';
			$s = '';
			// Customer
			if ($obj->type == 1) {
				$s .= '<a class="customer-back" style="padding-left: 6px; padding-right: 6px" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->rowid.'">'.$langs->trans("Customer").'</a>';
			} elseif ($obj->type == 2) {
				// Supplier
				$s .= '<a class="vendor-back" style="padding-left: 6px; padding-right: 6px" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/fourn/card.php?socid='.$obj->rowid.'">'.$langs->trans("Supplier").'</a>';
			} elseif ($obj->type == 3) {
				// User
				$s .= '<a class="user-back" style="padding-left: 6px; padding-right: 6px" title="'.$langs->trans("Employee").'" href="'.DOL_URL_ROOT.'/user/card.php?id='.$obj->id.'">'.$langs->trans("Employee").'</a>';
			}
			print $s;
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		if ($conf->global->MAIN_FEATURES_LEVEL >= 2) {
			// Activated or not reconciliation on accounting account
			if (!empty($arrayfields['reconcilable']['checked'])) {
				print '<td class="center">';
				if (empty($obj->reconcilable)) {
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=enable&mode=1&token='.newToken().'">';
					print img_picto($langs->trans("Disabled"), 'switch_off');
					print '</a>';
				} else {
					print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?id='.$obj->rowid.'&action=disable&mode=1&token='.newToken().'">';
					print img_picto($langs->trans("Activated"), 'switch_on');
					print '</a>';
				}
				print '</td>';
				if (!$i) {
					$totalarray['nbfield']++;
				}
			}
		}

		// Action
		print '<td class="center">';
		$e = '';
		// Customer
		if ($obj->type == 1) {
			$e .= '<a class="editfielda" title="'.$langs->trans("Customer").'" href="'.DOL_URL_ROOT.'/societe/card.php?action=edit&token='.newToken().'&socid='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">'.img_edit().'</a>';
		} elseif ($obj->type == 2) {
			// Supplier
			$e .= '<a class="editfielda" title="'.$langs->trans("Supplier").'" href="'.DOL_URL_ROOT.'/societe/card.php?action=edit&token='.newToken().'&socid='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">'.img_edit().'</a>';
		} elseif ($obj->type == 3) {
			// User
			$e .= '<a class="editfielda" title="'.$langs->trans("Employee").'" href="'.DOL_URL_ROOT.'/user/card.php?action=edit&token='.newToken().'&id='.$obj->rowid.'&backtopage='.urlencode($_SERVER["PHP_SELF"]).'">'.img_edit().'</a>';
		}
		print $e;
		print '</td>'."\n";
		if (!$i) {
			$totalarray['nbfield']++;
		}

		print '</tr>'."\n";
		$i++;
	}

	$db->free($resql);

	$parameters = array('arrayfields'=>$arrayfields, 'sql'=>$sql);
	$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	print "</table>";
	print "</div>";

	print '</form>';
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
