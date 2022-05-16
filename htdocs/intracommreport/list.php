<?php
/* Copyright (C) 2015       ATM Consulting          <support@atm-consulting.fr>
 * Copyright (C) 2019-2020  Open-DSI                <support@open-dsi.fr>
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
 *  \file       htdocs/intracommreport/list.php
 *  \ingroup    Intracomm Report
 *  \brief      Page to list intracomm report
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/intracommreport/class/intracommreport.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('intracommreport'));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');

$sall = trim((GETPOST('search_all', 'alphanohtml') != '') ?GETPOST('search_all', 'alphanohtml') : GETPOST('sall', 'alphanohtml'));
$search_ref = GETPOST("search_ref", 'alpha');
$search_type = GETPOST("search_type", 'int');
$optioncss = GETPOST('optioncss', 'alpha');
$type = GETPOST("type", "int");

$diroutputmassaction = $conf->product->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = (GETPOST("page", 'int') ?GETPOST("page", 'int') : 0);
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "i.ref";
}
if (!$sortorder) {
	$sortorder = "ASC";
}

// Initialize context for list
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'intracommreportlist';
if ((string) $type == '1') {
	$contextpage = 'DESlist'; if ($search_type == '') {
		$search_type = '1';
	}
}
if ((string) $type == '0') {
	$contextpage = 'DEBlist'; if ($search_type == '') {
		$search_type = '0';
	}
}

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array of hooks
$object = new IntracommReport($db);
$hookmanager->initHooks(array('intracommreportlist'));
$extrafields = new ExtraFields($db);
$form = new Form($db);

/*
// fetch optionals attributes and labels
$extralabels = $extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options=$extrafields->getOptionalsFromPost($object->table_element, '', 'search_');
*/

if (empty($action)) {
	$action = 'list';
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$canvas = GETPOST("canvas");
$objcanvas = null;
if (!empty($canvas)) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/canvas.class.php';
	$objcanvas = new Canvas($db, $action);
	$objcanvas->getCanvas('product', 'list', $canvas);
}

// Security check
/*
if ($search_type=='0') $result=restrictedArea($user, 'produit', '', '', '', '', '', $objcanvas);
elseif ($search_type=='1') $result=restrictedArea($user, 'service', '', '', '', '', '', $objcanvas);
else $result=restrictedArea($user, 'produit|service', '', '', '', '', '', $objcanvas);
*/

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
	'i.ref'=>"Ref",
	'pfi.ref_fourn'=>"RefSupplier",
	'i.label'=>"ProductLabel",
	'i.description'=>"Description",
	"i.note"=>"Note",
);

$isInEEC = isInEEC($mysoc);

// Definition of fields for lists
$arrayfields = array(
	'i.ref' => array('label'=>$langs->trans("Ref"), 'checked'=>1),
	'i.label' => array('label'=>$langs->trans("Label"), 'checked'=>1),
	'i.fk_product_type'=>array('label'=>$langs->trans("Type"), 'checked'=>0, 'enabled'=>(!empty($conf->produit->enabled) && !empty($conf->service->enabled))),
);
/*
// Extra fields
if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']))
{
	foreach($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (! empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key]=array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key]<0)?0:1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs((int) $extrafields->attributes[$object->table_element]['list'][$key])!=3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
*/
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Security check
if ($search_type == '0') {
	$result = restrictedArea($user, 'produit', '', '', '', '', '', 0);
} elseif ($search_type == '1') {
	$result = restrictedArea($user, 'service', '', '', '', '', '', 0);
} else {
	$result = restrictedArea($user, 'produit|service', '', '', '', '', '', 0);
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list'; $massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$sall = "";
		$search_ref = "";
		$search_label = "";
		//$search_type='';						// There is 2 types of list: a list of product and a list of services. No list with both. So when we clear search criteria, we must keep the filter on type.

		$show_childproducts = '';
		$search_array_options = array();
	}

	// Mass actions
	$objectclass = 'Product';
	if ((string) $search_type == '1') {
		$objectlabel = 'Services';
	}
	if ((string) $search_type == '0') {
		$objectlabel = 'Products';
	}

	$permtoread = $user->rights->produit->lire;
	$permtodelete = $user->rights->produit->supprimer;
	$uploaddir = $conf->product->dir_output;
	include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';
}


/*
 * View
 */

$formother = new FormOther($db);

$title = $langs->trans('IntracommReportList'.$type);

$sql = 'SELECT DISTINCT i.rowid, i.type_declaration, i.type_export, i.periods, i.mode, i.entity';
/*
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
}
*/
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= ' FROM '.MAIN_DB_PREFIX.'intracommreport as i';

// if (isset($extrafields->attributes[$object->table_element]['label']) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."intracommreport_extrafields as ef on (i.rowid = ef.fk_object)";

$sql .= ' WHERE i.entity IN ('.getEntity('intracommreport').')';

if ($sall) {
	$sql .= natural_search(array_keys($fieldstosearchall), $sall);
}
// if the type is not 1, we show all products (type = 0,2,3)
if (dol_strlen($search_type) && $search_type != '-1') {
	if ($search_type == 1) {
		$sql .= " AND i.type = 1";
	} else {
		$sql .= " AND i.type = 0";
	}
}

/*
if ($search_ref)     $sql .= natural_search('i.ref', $search_ref);
if ($search_label)   $sql .= natural_search('i.label', $search_label);
if ($search_barcode) $sql .= natural_search('i.barcode', $search_barcode);
if (isset($search_tosell) && dol_strlen($search_tosell) > 0  && $search_tosell!=-1) $sql.= " AND i.tosell = ".((int) $search_tosell);
if (isset($search_tobuy) && dol_strlen($search_tobuy) > 0  && $search_tobuy!=-1)   $sql.= " AND i.tobuy = ".((int) $search_tobuy);
if (dol_strlen($canvas) > 0)                    $sql.= " AND i.canvas = '".$db->escape($canvas)."'";
*/

/*
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
*/
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= " GROUP BY i.rowid, i.type_declaration, i.type_export, i.periods, i.mode, i.entity";

/*
// Add fields from extrafields
if (! empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql.=($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key : '');
}
*/

// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST)) {
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);

if ($resql) {
	$num = $db->num_rows($resql);

	$arrayofselected = is_array($toselect) ? $toselect : array();

	$helpurl = 'EN:Module_IntracommReport|FR:Module_ProDouane';
	llxHeader('', $title, $helpurl, '');

	// Displays product removal confirmation
	if (GETPOST('delreport')) {
		setEventMessages($langs->trans("IntracommReportDeleted", GETPOST('delreport')), null, 'mesgs');
	}

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.urlencode($limit);
	}
	if ($sall) {
		$param .= "&sall=".urlencode($sall);
	}
	if ($search_ref) {
		$param = "&search_ref=".urlencode($search_ref);
	}
	if ($search_label) {
		$param .= "&search_label=".urlencode($search_label);
	}

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
		//'builddoc'=>$langs->trans("PDFMerge"),
		//'presend'=>$langs->trans("SendByMail"),
	);
	if ($user->rights->intracommreport->delete) {
		$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
	}
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	$newcardbutton = '';
	if ($user->rights->intracommreport->write) {
		$newcardbutton .= dolGetButtonTitle($langs->trans("NewDeclaration"), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/intracommreport/card.php?action=create&amp;type='.$type);
	}

	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post" name="formulaire">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="type" value="'.$type.'">';
	if (empty($arrayfields['i.fk_product_type']['checked'])) {
		print '<input type="hidden" name="search_type" value="'.dol_escape_htmltag($search_type).'">';
	}

	print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'intracommreport', 0, $newcardbutton, '', $limit);

	$topicmail = "Information";
	$modelmail = "product";
	$objecttmp = new IntracommReport($db);
	$trackid = 'prod'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

	if ($sall) {
		foreach ($fieldstosearchall as $key => $val) {
			$fieldstosearchall[$key] = $langs->trans($val);
		}
		print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
	}

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$moreforfilter .= $hookmanager->resPrint;
	} else {
		$moreforfilter = $hookmanager->resPrint;
	}

	if ($moreforfilter) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields
	if ($massactionbutton) {
		$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);
	}

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Lines with input filters
	print '<tr class="liste_titre_filter">';
	if (!empty($arrayfields['i.ref']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_ref" size="8" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['i.label']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" name="search_label" size="12" value="'.dol_escape_htmltag($search_label).'">';
		print '</td>';
	}
	// Type
	// Type (customer/prospect/supplier)
	if (!empty($arrayfields['customerorsupplier']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone center">';
		if ($type != '') {
			print '<input type="hidden" name="type" value="'.$type.'">';
		}
		print $formcompany->selectProspectCustomerType($search_type, 'search_type', 'search_type', 'list');
		print '</select></td>';
	}

	if (!empty($arrayfields['i.fk_product_type']['checked'])) {
		print '<td class="liste_titre left">';
		$array = array('-1'=>'&nbsp;', '0'=>$langs->trans('Product'), '1'=>$langs->trans('Service'));
		print $form->selectarray('search_type', $array, $search_type);
		print '</td>';
	}

	/*
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';
	*/
	// Fields from hook
	$parameters = array('arrayfields'=>$arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['i.datec']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['i.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';

	print '</tr>';

	print '<tr class="liste_titre">';
	if (!empty($arrayfields['i.ref']['checked'])) {
		print_liste_field_titre($arrayfields['i.ref']['label'], $_SERVER["PHP_SELF"], "i.ref", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['i.label']['checked'])) {
		print_liste_field_titre($arrayfields['i.label']['label'], $_SERVER["PHP_SELF"], "i.label", "", $param, "", $sortfield, $sortorder);
	}
	if (!empty($arrayfields['i.fk_product_type']['checked'])) {
		print_liste_field_titre($arrayfields['i.fk_product_type']['label'], $_SERVER["PHP_SELF"], "i.fk_product_type", "", $param, "", $sortfield, $sortorder);
	}

	/*
	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
	*/
	// Hook fields
	$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['i.datec']['checked'])) {
		print_liste_field_titre($arrayfields['i.datec']['label'], $_SERVER["PHP_SELF"], "i.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}
	if (!empty($arrayfields['i.tms']['checked'])) {
		print_liste_field_titre($arrayfields['i.tms']['label'], $_SERVER["PHP_SELF"], "i.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
	}

	print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
	print "</tr>\n";


	$intracommreport_static = new IntracommReport($db);

	$i = 0;
	$totalarray = array();
	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($resql);

		$intracommreport_static->id = $obj->rowid;
		$intracommreport_static->ref = $obj->ref;
		$intracommreport_static->ref_fourn = $obj->ref_supplier;
		$intracommreport_static->label = $obj->label;
		$intracommreport_static->type = $obj->fk_product_type;
		$intracommreport_static->status_buy = $obj->tobuy;
		$intracommreport_static->status     = $obj->tosell;
		$intracommreport_static->status_batch = $obj->tobatch;
		$intracommreport_static->entity = $obj->entity;

		print '<tr class="oddeven">';

		// Ref
		if (!empty($arrayfields['i.ref']['checked'])) {
			print '<td class="tdoverflowmax200">';
			print $intracommreport_static->getNomUrl(1);
			print "</td>\n";
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Label
		if (!empty($arrayfields['i.label']['checked'])) {
			print '<td class="tdoverflowmax200">'.dol_trunc($obj->label, 80).'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Type
		if (!empty($arrayfields['i.fk_product_type']['checked'])) {
			print '<td>'.$obj->fk_product_type.'</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Action
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) {   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$obj->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$obj->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}

		print "</tr>\n";
		$i++;
	}

	$db->free($resql);

	print "</table>";
	print "</div>";
	print '</form>';
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
