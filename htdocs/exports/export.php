<?php
/* Copyright (C) 2005-2018	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2012		Charles-Fr BENKE	<charles.fr@benke.fr>
 * Copyright (C) 2015       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/exports/export.php
 *       \ingroup    export
 *       \brief      Pages of export Wizard
 */

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/exports/class/export.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/export/modules_export.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->loadlangs(array('admin', 'exports', 'other', 'users', 'companies', 'projects', 'suppliers', 'products', 'bank', 'bills'));

// Everybody should be able to go on this page
//if (! $user->admin)
//  accessforbidden();

// Map icons, array duplicated in import.php, was not synchronized, TODO put it somewhere only once
$entitytoicon = array(
	'invoice'      => 'bill',
	'invoice_line' => 'bill',
	'order'        => 'order',
	'order_line'   => 'order',
	'propal'       => 'propal',
	'propal_line'  => 'propal',
	'intervention' => 'intervention',
	'inter_line'   => 'intervention',
	'member'       => 'user',
	'member_type'  => 'group',
	'subscription' => 'payment',
	'payment'      => 'payment',
	'tax'          => 'generic',
	'tax_type'     => 'generic',
	'other'        => 'generic',
	'account'      => 'account',
	'product'      => 'product',
	'virtualproduct' => 'product',
	'subproduct'   => 'product',
	'product_supplier_ref'      => 'product',
	'stock'        => 'stock',
	'warehouse'    => 'stock',
	'batch'        => 'stock',
	'stockbatch'   => 'stock',
	'category'     => 'category',
	'securityevent' => 'generic',
	'shipment'     => 'sending',
	'shipment_line' => 'sending',
	'reception' => 'sending',
	'reception_line' => 'sending',
	'expensereport' => 'trip',
	'expensereport_line' => 'trip',
	'holiday'      => 'holiday',
	'contract_line' => 'contract',
	'translation'  => 'generic',
	'bomm'         => 'bom',
	'bomline'      => 'bom',
	'conferenceorboothattendee' => 'contact'
);

// Translation code, array duplicated in import.php, was not synchronized, TODO put it somewhere only once
$entitytolang = array(
	'user'         => 'User',
	'company'      => 'Company',
	'contact'      => 'Contact',
	'invoice'      => 'Bill',
	'invoice_line' => 'InvoiceLine',
	'order'        => 'Order',
	'order_line'   => 'OrderLine',
	'propal'       => 'Proposal',
	'propal_line'  => 'ProposalLine',
	'intervention' => 'Intervention',
	'inter_line'   => 'InterLine',
	'member'       => 'Member',
	'member_type'  => 'MemberType',
	'subscription' => 'Subscription',
	'tax'          => 'SocialContribution',
	'tax_type'     => 'DictionarySocialContributions',
	'account'      => 'BankTransactions',
	'payment'      => 'Payment',
	'product'      => 'Product',
	'virtualproduct'  => 'AssociatedProducts',
	'subproduct'      => 'SubProduct',
	'product_supplier_ref'      => 'SupplierPrices',
	'service'      => 'Service',
	'stock'        => 'Stock',
	'movement'	   => 'StockMovement',
	'batch'        => 'Batch',
	'stockbatch'   => 'StockDetailPerBatch',
	'warehouse'    => 'Warehouse',
	'category'     => 'Category',
	'other'        => 'Other',
	'trip'         => 'TripsAndExpenses',
	'securityevent' => 'SecurityEvent',
	'shipment'     => 'Shipments',
	'shipment_line' => 'ShipmentLine',
	'project'      => 'Projects',
	'projecttask'  => 'Tasks',
	'resource'     => 'Resource',
	'task_time'    => 'TaskTimeSpent',
	'action'       => 'Event',
	'expensereport' => 'ExpenseReport',
	'expensereport_line' => 'ExpenseReportLine',
	'holiday'      => 'TitreRequestCP',
	'contract'     => 'Contract',
	'contract_line' => 'ContractLine',
	'translation'  => 'Translation',
	'bom'          => 'BOM',
	'bomline'      => 'BOMLine',
	'conferenceorboothattendee' => 'Attendee',
	'inventory'   => 'Inventory'
);

$array_selected = isset($_SESSION["export_selected_fields"]) ? $_SESSION["export_selected_fields"] : array();
$array_filtervalue = isset($_SESSION["export_filtered_fields"]) ? $_SESSION["export_filtered_fields"] : array();
$datatoexport = GETPOST("datatoexport", "aZ09");
$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$step = GETPOSTINT("step") ? GETPOSTINT("step") : 1;
$export_name = GETPOST("export_name", "alphanohtml");
$hexa = GETPOST("hexa", "alpha");
$exportmodelid = GETPOSTINT("exportmodelid");
$field = GETPOST("field", "alpha");

$objexport = new Export($db);
$objexport->load_arrays($user, $datatoexport);

$objmodelexport = new ModeleExports($db);
$form = new Form($db);
$htmlother = new FormOther($db);
$formfile = new FormFile($db);
$sqlusedforexport = '';

$head = array();
$upload_dir = $conf->export->dir_temp.'/'.$user->id;

$usefilters = 1;

// Security check
$result = restrictedArea($user, 'export');


/*
 * Actions
 */

if ($action == 'selectfield' && $user->hasRight('export', 'creer')) {     // Selection of field at step 2
	$fieldsarray = $objexport->array_export_fields[0];
	$fieldsentitiesarray = $objexport->array_export_entities[0];
	$fieldsdependenciesarray = $objexport->array_export_dependencies[0];

	if ($field == 'all') {
		foreach ($fieldsarray as $key => $val) {
			if (!empty($array_selected[$key])) {
				continue; // If already selected, check next
			}
			$array_selected[$key] = count($array_selected) + 1;
			//print_r($array_selected);
			$_SESSION["export_selected_fields"] = $array_selected;
		}
	} else {
		$warnings = array();

		$array_selected[$field] = count($array_selected) + 1; // We tag the key $field as "selected"
		// We check if there is a dependency to activate
		/*var_dump($field);
		 var_dump($fieldsentitiesarray[$field]);
		 var_dump($fieldsdependenciesarray);*/
		$listofdependencies = array();
		if (!empty($fieldsentitiesarray[$field]) && !empty($fieldsdependenciesarray[$fieldsentitiesarray[$field]])) {
			// We found a dependency on the type of field
			$tmp = $fieldsdependenciesarray[$fieldsentitiesarray[$field]]; // $fieldsdependenciesarray=array('element'=>'fd.rowid') or array('element'=>array('fd.rowid','ab.rowid'))
			if (is_array($tmp)) {
				$listofdependencies = $tmp;
			} else {
				$listofdependencies = array($tmp);
			}
		} elseif (!empty($field) && !empty($fieldsdependenciesarray[$field])) {
			// We found a dependency on a dedicated field
			$tmp = $fieldsdependenciesarray[$field]; // $fieldsdependenciesarray=array('fd.fieldx'=>'fd.rowid') or array('fd.fieldx'=>array('fd.rowid','ab.rowid'))
			if (is_array($tmp)) {
				$listofdependencies = $tmp;
			} else {
				$listofdependencies = array($tmp);
			}
		}

		if (count($listofdependencies)) {
			foreach ($listofdependencies as $fieldid) {
				if (empty($array_selected[$fieldid])) {
					$array_selected[$fieldid] = count($array_selected) + 1; // We tag the key $fieldid as "selected"
					$warnings[] = $langs->trans("ExportFieldAutomaticallyAdded", $langs->transnoentitiesnoconv($fieldsarray[$fieldid]));
				}
			}
		}
		//print_r($array_selected);
		$_SESSION["export_selected_fields"] = $array_selected;

		setEventMessages(null, $warnings, 'warnings');
	}
}
if ($action == 'unselectfield' && $user->hasRight('export', 'creer')) {
	if (GETPOST("field") == 'all') {
		$array_selected = array();
		$_SESSION["export_selected_fields"] = $array_selected;
	} else {
		unset($array_selected[GETPOST("field")]);
		// Renumber fields of array_selected (from 1 to nb_elements)
		asort($array_selected);
		$i = 0;
		$array_selected_save = $array_selected;
		foreach ($array_selected as $code => $value) {
			$i++;
			$array_selected[$code] = $i;
			//print "x $code x $i y<br>";
		}
		$_SESSION["export_selected_fields"] = $array_selected;
	}
}

$newpos = -1;
if (($action == 'downfield' || $action == 'upfield') && $user->hasRight('export', 'creer')) {
	$pos = $array_selected[GETPOST("field")];
	if ($action == 'downfield') {	// Test on permission already done
		$newpos = $pos + 1;
	}
	if ($action == 'upfield') {		// Test on permission already done
		$newpos = $pos - 1;
	}
	// Lookup code to switch with
	$newcode = "";
	foreach ($array_selected as $code => $value) {
		if ($value == $newpos) {
			$newcode = $code;
			break;
		}
	}
	//print("Switch pos=$pos (code=".GETPOST("field").") and newpos=$newpos (code=$newcode)");
	if ($newcode) {   // Si newcode trouve (protection contre resoumission de page)
		$array_selected[GETPOST("field")] = $newpos;
		$array_selected[$newcode] = $pos;
		$_SESSION["export_selected_fields"] = $array_selected;
	}
}

if ($step == 1 || $action == 'cleanselect') {	// Test on permission here not required
	$_SESSION["export_selected_fields"] = array();
	$_SESSION["export_filtered_fields"] = array();
	$array_selected = array();
	$array_filtervalue = array();
}

if ($action == 'builddoc' && $user->hasRight('export', 'lire')) {
	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	$separator = GETPOST('delimiter', 'alpha');
	$max_execution_time_for_importexport = getDolGlobalInt('EXPORT_MAX_EXECUTION_TIME', 300); // 5mn if not defined
	$max_time = @ini_get("max_execution_time");
	if ($max_time && $max_time < $max_execution_time_for_importexport) {
		dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
		@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
	}

	// Build export file
	$result = $objexport->build_file($user, GETPOST('model', 'alpha'), $datatoexport, $array_selected, $array_filtervalue, '', $separator);
	if ($result < 0) {
		setEventMessages($objexport->error, $objexport->errors, 'errors');
		$sqlusedforexport = $objexport->sqlusedforexport;
	} else {
		setEventMessages($langs->trans("FileSuccessfullyBuilt"), null, 'mesgs');
		$sqlusedforexport = $objexport->sqlusedforexport;
	}
}

// Delete file
if ($step == 5 && $action == 'confirm_deletefile' && $confirm == 'yes' && $user->hasRight('export', 'lire')) {
	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	$file = $upload_dir."/".GETPOST('file');

	$ret = dol_delete_file($file);
	if ($ret) {
		setEventMessages($langs->trans("FileWasRemoved", GETPOST('file')), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('file')), null, 'errors');
	}
	header('Location: '.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport);
	exit;
}

if ($action == 'deleteprof' && $user->hasRight('export', 'lire')) {
	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	if (GETPOSTINT("id")) {
		$objexport->fetch(GETPOSTINT('id'));
		$result = $objexport->delete($user);
	}
}

// TODO The export for filter is not yet implemented (old code created conflicts with step 2). We must use same way of working and same combo list of predefined export than step 2.
if ($action == 'add_export_model' && $user->hasRight('export', 'lire')) {
	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	if ($export_name) {
		asort($array_selected);

		// Set save string
		$hexa = '';
		foreach ($array_selected as $key => $val) {
			if ($hexa) {
				$hexa .= ',';
			}
			$hexa .= $key;
		}

		$hexafiltervalue = '';
		if (!empty($array_filtervalue) && is_array($array_filtervalue)) {
			foreach ($array_filtervalue as $key => $val) {
				if ($hexafiltervalue) {
					$hexafiltervalue .= ',';
				}
				$hexafiltervalue .= $key.'='.$val;
			}
		}

		$objexport->model_name = $export_name;
		$objexport->datatoexport = $datatoexport;
		$objexport->hexa = $hexa;
		$objexport->hexafiltervalue = $hexafiltervalue;
		$objexport->fk_user = (GETPOST('visibility', 'aZ09') == 'all' ? 0 : $user->id);

		$result = $objexport->create($user);
		if ($result >= 0) {
			setEventMessages($langs->trans("ExportModelSaved", $objexport->model_name), null, 'mesgs');
		} else {
			$langs->load("errors");
			if ($objexport->errno == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->trans("ErrorExportDuplicateProfil"), null, 'errors');
			} else {
				setEventMessages($objexport->error, $objexport->errors, 'errors');
			}
		}
	} else {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("ExportModelName")), null, 'errors');
	}
}

// Reload a predefined export model
if ($step == 2 && $action == 'select_model' && $user->hasRight('export', 'lire')) {
	$_SESSION["export_selected_fields"] = array();
	$_SESSION["export_filtered_fields"] = array();

	$array_selected = array();
	$array_filtervalue = array();

	$result = $objexport->fetch($exportmodelid);
	if ($result > 0) {
		$fieldsarray = preg_split("/,(?! [^(]*\))/", $objexport->hexa);
		$i = 1;
		foreach ($fieldsarray as $val) {
			$array_selected[$val] = $i;
			$i++;
		}
		$_SESSION["export_selected_fields"] = $array_selected;

		$fieldsarrayvalue = explode(',', $objexport->hexafiltervalue);
		$i = 1;
		foreach ($fieldsarrayvalue as $val) {
			$tmp = explode('=', $val);
			$array_filtervalue[$tmp[0]] = $tmp[1];
			$i++;
		}
		$_SESSION["export_filtered_fields"] = $array_filtervalue;
	}
}

// Get form with filters
if ($step == 4 && $action == 'submitFormField' && $user->hasRight('export', 'lire')) {
	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	// on boucle sur les champs selectionne pour recuperer la valeur
	if (is_array($objexport->array_export_TypeFields[0])) {
		$_SESSION["export_filtered_fields"] = array();
		foreach ($objexport->array_export_TypeFields[0] as $code => $type) {	// $code: s.fieldname $value: Text|Boolean|List:ccc
			$newcode = (string) preg_replace('/\./', '_', $code);
			//print 'xxx '.$code."=".$newcode."=".$type."=".GETPOST($newcode)."\n<br>";
			$check = 'alphanohtml';
			$filterqualified = 1;
			if (!GETPOSTISSET($newcode) || GETPOST($newcode, $check) == '') {
				$filterqualified = 0;
			} elseif (preg_match('/^List/', $type) && (is_numeric(GETPOST($newcode, $check)) && GETPOST($newcode, $check) <= 0)) {
				$filterqualified = 0;
			}
			if ($filterqualified) {
				$objexport->array_export_FilterValue[0][$code] = GETPOST($newcode, $check);
			}
		}
		$array_filtervalue = (!empty($objexport->array_export_FilterValue[0]) ? $objexport->array_export_FilterValue[0] : '');
		$_SESSION["export_filtered_fields"] = $array_filtervalue;
	}
}


/*
 * View
 */

if ($step == 1 || !$datatoexport) {
	llxHeader('', $langs->trans("NewExport"), 'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones', '', 0, 0, '', '', '', 'mod-exports page-export action-step1');

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -1);

	print '<div class="opacitymedium">'.$langs->trans("SelectExportDataSet").'</div><br>';

	// Affiche les modules d'exports
	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Module").'</td>';
	print '<td>'.$langs->trans("ExportableDatas").'</td>';
	print '<td>&nbsp;</td>';
	print '</tr>';

	if (count($objexport->array_export_module)) {
		asort($objexport->array_export_code_for_sort);
		//var_dump($objexport->array_export_code_for_sort);
		//$sortedarrayofmodules = dol_sort_array($objexport->array_export_module, 'module_position', 'asc', 0, 0, 1);
		foreach ($objexport->array_export_code_for_sort as $key => $value) {
			print '<tr class="oddeven"><td nospan="nospan">';
			//print img_object($objexport->array_export_module[$key]->getName(),$export->array_export_module[$key]->picto).' ';
			print $objexport->array_export_module[$key]->getName();
			print '</td><td>';
			$entity = preg_replace('/:.*$/', '', $objexport->array_export_icon[$key]);
			$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
			$label = $objexport->array_export_label[$key];
			//print $value.'-'.$icon.'-'.$label."<br>";
			print img_object($objexport->array_export_module[$key]->getName(), $entityicon).' ';
			print $label;
			print '</td><td class="right">';
			if ($objexport->array_export_perms[$key]) {
				print '<a href="'.DOL_URL_ROOT.'/exports/export.php?step=2&module_position='.$objexport->array_export_module[$key]->module_position.'&datatoexport='.$objexport->array_export_code[$key].'">'.img_picto($langs->trans("NewExport"), 'next', 'class="fa-15"').'</a>';
			} else {
				print '<span class="opacitymedium">'.$langs->trans("NotEnoughPermissions").'</span>';
			}
			print '</td></tr>';
		}
	} else {
		print '<tr><td class="oddeven" colspan="3">'.$langs->trans("NoExportableData").'</td></tr>';
	}
	print '</table>';
	print '</div>';

	print '</div>';
}

if ($step == 2 && $datatoexport) {
	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	llxHeader('', $langs->trans("NewExport"), 'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones', '', 0, 0, '', '', '', 'mod-exports page-export action-step2');

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield">'.$langs->trans("Module").'</td>';
	print '<td>';
	print $objexport->array_export_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	$entity = preg_replace('/:.*$/', '', $objexport->array_export_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	print img_object($objexport->array_export_module[0]->getName(), $entityicon).' ';
	print $objexport->array_export_label[0];
	print '</td></tr>';

	print '</table>';
	print '</div>';

	print dol_get_fiche_end();

	print '<br>';

	// Combo list of export models
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="select_model">';
	print '<input type="hidden" name="step" value="2">';
	print '<input type="hidden" name="datatoexport" value="'.$datatoexport.'">';
	print '<div class="valignmiddle marginbottomonly">';
	print '<span class="opacitymedium">'.$langs->trans("SelectExportFields").'</span> ';
	$htmlother->select_export_model($exportmodelid, 'exportmodelid', $datatoexport, 1, $user->id);
	print ' ';
	print '<input type="submit" class="button small" value="'.$langs->trans("Select").'">';
	print '</div>';
	print '</form>';


	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Object").'</td>';
	print '<td>'.$langs->trans("ExportableFields").'</td>';
	print '<td width="100" class="center">';
	print '<a class="liste_titre commonlink" title='.$langs->trans("All").' alt='.$langs->trans("All").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field=all&token='.newToken().'">'.$langs->trans("All")."</a>";
	print ' / ';
	print '<a class="liste_titre commonlink" title='.$langs->trans("None").' alt='.$langs->trans("None").' href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field=all&token='.newToken().'">'.$langs->trans("None")."</a>";
	print '</td>';
	print '<td width="44%">'.$langs->trans("ExportedFields").'</td>';
	print '</tr>';

	// Champs exportables
	$fieldsarray = $objexport->array_export_fields[0];
	// Select request if all fields are selected
	$sqlmaxforexport = $objexport->build_sql(0, array(), array());

	//    $this->array_export_module[0]=$module;
	//    $this->array_export_code[0]=$module->export_code[$r];
	//    $this->array_export_label[0]=$module->export_label[$r];
	//    $this->array_export_sql[0]=$module->export_sql[$r];
	//    $this->array_export_fields[0]=$module->export_fields_array[$r];
	//    $this->array_export_entities[0]=$module->export_fields_entities[$r];
	//    $this->array_export_alias[0]=$module->export_fields_alias[$r];

	$i = 0;

	foreach ($fieldsarray as $code => $label) {
		print '<tr class="oddeven">';

		$i++;

		$entity = (!empty($objexport->array_export_entities[0][$code]) ? $objexport->array_export_entities[0][$code] : $objexport->array_export_icon[0]);
		$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
		$entitylang = (!empty($entitytolang[$entity]) ? $entitytolang[$entity] : $entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		//print $code.'-'.$label.'-'.$entity;

		$tmparray = explode(':', $entityicon);
		if (count($tmparray) >= 2) {
			$entityicon = $tmparray[0];
			$entitylang = $tmparray[1];
		}
		print img_object('', $entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		$text = (empty($objexport->array_export_special[0][$code]) ? '' : '<i>').$langs->trans($label).(empty($objexport->array_export_special[0][$code]) ? '' : '</i>');

		$tablename = getablenamefromfield($code, $sqlmaxforexport);
		$htmltext = '<b>'.$langs->trans("Name").":</b> ".$text.'<br>';
		if (!empty($objexport->array_export_special[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("ComputedField")." -> ".$langs->trans("Method")." :</b> ".$objexport->array_export_special[0][$code]."<br>";
		} else {
			$htmltext .= '<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./', '', $code)."<br>";
		}
		if (!empty($objexport->array_export_examplevalues[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("SourceExample").':</b> '.$objexport->array_export_examplevalues[0][$code].'<br>';
		}
		if (!empty($objexport->array_export_TypeFields[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Type").':</b> '.$objexport->array_export_TypeFields[0][$code].'<br>';
		}
		if (!empty($objexport->array_export_help[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Help").':</b> '.$langs->trans($objexport->array_export_help[0][$code]).'<br>';
		}

		if (isset($array_selected[$code]) && $array_selected[$code]) {
			// Selected fields
			print '<td>&nbsp;</td>';
			print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=unselectfield&field='.$code.'">'.img_left('default', 0, 'style="max-width: 20px"').'</a></td>';
			print '<td>';
			//print $text.'-'.$htmltext."<br>";
			print $form->textwithpicto($text, $htmltext);
			//print ' ('.$code.')';
			print '</td>';
		} else {
			// Fields not selected
			print '<td>';
			//print $text.'-'.$htmltext."<br>";
			print $form->textwithpicto($text, $htmltext);
			//print ' ('.$code.')';
			print '</td>';
			print '<td class="center"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step=2&datatoexport='.$datatoexport.'&action=selectfield&field='.$code.'">'.img_right('default', 0, 'style="max-width: 20px"').'</a></td>';
			print '<td>&nbsp;</td>';
		}

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	/*
	 * Action bar
	 */
	print '<div class="tabsAction tabsActionNoBottom">';

	if (count($array_selected)) {
		// If filters exist
		if ($usefilters && isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0])) {
			print '<a class="butAction" href="export.php?step=3&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
		} else {
			print '<a class="butAction" href="export.php?step=4&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
		}
	} else {
		print '<a class="butActionRefused classfortooltip" href="#" title="'.dol_escape_htmltag($langs->trans("SelectAtLeastOneField")).'">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';
}

if ($step == 3 && $datatoexport) {
	if (count($array_selected) < 1) {      // This occurs when going back to page after sessecion expired
		// Switch to step 2
		header("Location: ".DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport);
		exit;
	}

	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	llxHeader('', $langs->trans("NewExport"), 'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones', '', 0, 0, '', '', '', 'mod-exports page-export action-step3');

	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 3";
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
	print $objexport->array_export_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	$entity = preg_replace('/:.*$/', '', $objexport->array_export_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	print img_object($objexport->array_export_module[0]->getName(), $entityicon).' ';
	print $objexport->array_export_label[0];
	print '</td></tr>';

	// List of exported fields
	print '<tr><td>'.$langs->trans("ExportedFields").'</td>';
	$list = '';
	foreach ($array_selected as $code => $value) {
		if (isset($objexport->array_export_fields[0][$code])) {
			$list .= (!empty($list) ? ', ' : '');
			$list .= $langs->trans($objexport->array_export_fields[0][$code]);
		}
	}
	print '<td>'.$list.'</td></tr>';

	print '</table>';
	print '</div>';

	print '<br>';

	// Combo list of export models
	print '<span class="opacitymedium">'.$langs->trans("SelectFilterFields").'</span><br><br>';


	// un formulaire en plus pour recuperer les filtres
	print '<form action="'.$_SERVER["PHP_SELF"].'?step=4&action=submitFormField&datatoexport='.$datatoexport.'" name="FilterField" method="post">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	//print '<td>'.$langs->trans("ExportableFields").'</td>';
	//print '<td class="center"></td>';
	print '<td>'.$langs->trans("ExportableFields").'</td>';
	print '<td width="25%">'.$langs->trans("FilteredFieldsValues").'</td>';
	print '</tr>';

	// Champs exportables
	$fieldsarray = $objexport->array_export_fields[0];
	// Champs filtrable
	$Typefieldsarray = $objexport->array_export_TypeFields[0];
	// valeur des filtres
	$ValueFiltersarray = (!empty($objexport->array_export_FilterValue[0]) ? $objexport->array_export_FilterValue[0] : '');
	// Select request if all fields are selected
	$sqlmaxforexport = $objexport->build_sql(0, array(), array());

	$i = 0;
	// on boucle sur les champs
	foreach ($fieldsarray as $code => $label) {
		print '<tr class="oddeven">';

		$i++;
		$entity = (!empty($objexport->array_export_entities[0][$code]) ? $objexport->array_export_entities[0][$code] : $objexport->array_export_icon[0]);
		$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
		$entitylang = (!empty($entitytolang[$entity]) ? $entitytolang[$entity] : $entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		$tmparray = explode(':', $entityicon);
		if (count($tmparray) >= 2) {
			$entityicon = $tmparray[0];
			$entitylang = $tmparray[1];
		}
		print img_object('', $entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		// Field name
		$labelName = (!empty($fieldsarray[$code]) ? $fieldsarray[$code] : '');
		$ValueFilter = (!empty($array_filtervalue[$code]) ? $array_filtervalue[$code] : '');
		$text = (empty($objexport->array_export_special[0][$code]) ? '' : '<i>').$langs->trans($labelName).(empty($objexport->array_export_special[0][$code]) ? '' : '</i>');

		$tablename = getablenamefromfield($code, $sqlmaxforexport);
		$htmltext = '<b>'.$langs->trans("Name").':</b> '.$text.'<br>';
		if (!empty($objexport->array_export_special[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("ComputedField")." -> ".$langs->trans("Method")." :</b> ".$objexport->array_export_special[0][$code]."<br>";
		} else {
			$htmltext .= '<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./', '', $code)."<br>";
		}
		if (!empty($objexport->array_export_examplevalues[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("SourceExample").':</b> '.$objexport->array_export_examplevalues[0][$code].'<br>';
		}
		if (!empty($objexport->array_export_TypeFields[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Type").':</b> '.$objexport->array_export_TypeFields[0][$code].'<br>';
		}
		if (!empty($objexport->array_export_help[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Help").':</b> '.$langs->trans($objexport->array_export_help[0][$code]).'<br>';
		}

		print '<td>';
		print $form->textwithpicto($text, $htmltext);
		print '</td>';

		// Filter value
		print '<td>';
		if (!empty($Typefieldsarray[$code])) {	// Example: Text, List:c_country:label:rowid, Number, Boolean
			$szInfoFiltre = $objexport->genDocFilter($Typefieldsarray[$code]);
			if ($szInfoFiltre) {	// Is there an info help for this filter ?
				$tmp = $objexport->build_filterField($Typefieldsarray[$code], $code, $ValueFilter);
				print $form->textwithpicto($tmp, $szInfoFiltre);
			} else {
				print $objexport->build_filterField($Typefieldsarray[$code], $code, $ValueFilter);
			}
		}
		print '</td>';

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '</div>';

	/*
	 * Action bar
	 */
	print '<div class="tabsAction tabsActionNoBottom">';
	// il n'est pas obligatoire de filtrer les champs
	print '<a class="butAction" href="javascript:FilterField.submit();">'.$langs->trans("NextStep").'</a>';
	print '</div>';
}

if ($step == 4 && $datatoexport) {
	if (count($array_selected) < 1) {     // This occurs when going back to page after sessecion expired
		// Switch to step 2
		header("Location: ".DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport);
		exit;
	}

	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	asort($array_selected);

	llxHeader('', $langs->trans("NewExport"), 'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones', '', 0, 0, '', '', '', 'mod-exports page-export action-step4');

	$stepoffset = 0;
	$h = 0;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	// If filters exist
	if ($usefilters && isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0])) {
		$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&datatoexport='.$datatoexport;
		$head[$h][1] = $langs->trans("Step")." 3";
		$h++;
		$stepoffset++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=4&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." ".(3 + $stepoffset);
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';
	print '<table width="100%" class="border tableforfield">';

	// Module
	print '<tr><td class="titlefield tableforfield">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
	print $objexport->array_export_module[0]->getName();
	print '</td></tr>';

	// Lot de donnees a exporter
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	$entity = preg_replace('/:.*$/', '', $objexport->array_export_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	print img_object($objexport->array_export_module[0]->getName(), $entityicon).' ';
	print $objexport->array_export_label[0];
	print '</td></tr>';

	// List of exported fields
	print '<tr><td>'.$langs->trans("ExportedFields").'</td>';
	$list = '';
	foreach ($array_selected as $code => $value) {
		if (isset($objexport->array_export_fields[0][$code])) {
			$list .= (!empty($list) ? ', ' : '');
			$list .= $langs->trans($objexport->array_export_fields[0][$code]);
		}
	}
	print '<td>'.$list.'</td>';
	print '</tr>';

	// List of filtered fields
	if (isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0])) {
		print '<tr><td>'.$langs->trans("FilteredFields").'</td>';
		$list = '';
		if (!empty($array_filtervalue)) {
			foreach ($array_filtervalue as $code => $value) {
				if (preg_match('/^FormSelect:/', $objexport->array_export_TypeFields[0][$code])) {
					// We discard this filter if it is a FromSelect field with a value of -1.
					if ($value == -1) {
						continue;
					}
				}
				if (isset($objexport->array_export_fields[0][$code])) {
					$list .= ($list ? ', ' : '');
					if (isset($array_filtervalue[$code]) && preg_match('/^\s*[<>]/', $array_filtervalue[$code])) {
						$list .= '<span class="opacitymedium">'.$langs->trans($objexport->array_export_fields[0][$code]).'</span>'.(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '');
					} else {
						$list .= '<span class="opacitymedium">'.$langs->trans($objexport->array_export_fields[0][$code])."</span>='".(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '')."'";
					}
				}
			}
		}
		print '<td>'.(!empty($list) ? $list : '<span class="opacitymedium">'.$langs->trans("None").'</span>').'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '<br>';

	// Select request if all fields are selected
	$sqlmaxforexport = $objexport->build_sql(0, array(), array());

	print '<div class="marginbottomonly"><span class="opacitymedium">'.$langs->trans("ChooseFieldsOrdersAndTitle").'</span></div>';

	print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Entities").'</td>';
	print '<td>'.$langs->trans("ExportedFields").'</td>';
	print '<td class="right" colspan="2">'.$langs->trans("Position").'</td>';
	//print '<td>&nbsp;</td>';
	//print '<td>'.$langs->trans("FieldsTitle").'</td>';
	print '</tr>';

	foreach ($array_selected as $code => $value) {
		if (!isset($objexport->array_export_fields[0][$code])) {	// For example when field was in predefined filter but not more active (localtax1 disabled by setup of country)
			continue;
		}

		print '<tr class="oddeven">';

		$entity = (!empty($objexport->array_export_entities[0][$code]) ? $objexport->array_export_entities[0][$code] : $objexport->array_export_icon[0]);
		$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
		$entitylang = (!empty($entitytolang[$entity]) ? $entitytolang[$entity] : $entity);

		print '<td class="nowrap">';
		// If value of entityicon=entitylang='icon:Label'
		$tmparray = explode(':', $entityicon);
		if (count($tmparray) >= 2) {
			$entityicon = $tmparray[0];
			$entitylang = $tmparray[1];
		}
		print img_object('', $entityicon).' '.$langs->trans($entitylang);
		print '</td>';

		$labelName = $objexport->array_export_fields[0][$code];

		$text = (empty($objexport->array_export_special[0][$code]) ? '' : '<i>').$langs->trans($labelName).(empty($objexport->array_export_special[0][$code]) ? '' : '</i>');

		$tablename = getablenamefromfield($code, $sqlmaxforexport);
		$htmltext = '<b>'.$langs->trans("Name").':</b> '.$text.'<br>';
		if (!empty($objexport->array_export_special[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("ComputedField")." -> ".$langs->trans("Method")." :</b> ".$objexport->array_export_special[0][$code]."<br>";
		} else {
			$htmltext .= '<b>'.$langs->trans("Table")." -> ".$langs->trans("Field").":</b> ".$tablename." -> ".preg_replace('/^.*\./', '', $code)."<br>";
		}
		if (!empty($objexport->array_export_examplevalues[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("SourceExample").':</b> '.$objexport->array_export_examplevalues[0][$code].'<br>';
		}
		if (!empty($objexport->array_export_TypeFields[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Type").':</b> '.$objexport->array_export_TypeFields[0][$code].'<br>';
		}
		if (!empty($objexport->array_export_help[0][$code])) {
			$htmltext .= '<b>'.$langs->trans("Help").':</b> '.$langs->trans($objexport->array_export_help[0][$code]).'<br>';
		}

		print '<td>';
		print $form->textwithpicto($text, $htmltext);
		//print ' ('.$code.')';
		print '</td>';

		print '<td class="right" width="100">';
		print $value.' ';
		print '</td><td class="center nowraponall" width="40">';
		if ($value < count($array_selected)) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=downfield&field='.$code.'">'.img_down().'</a>';
		}
		if ($value > 1) {
			print '<a href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=upfield&field='.$code.'">'.img_up().'</a>';
		}
		print '</td>';

		//print '<td>&nbsp;</td>';
		//print '<td>'.$langs->trans($objexport->array_export_fields[0][$code]).'</td>';

		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '</div>';

	/*
	 * Action bar
	 */
	print '<div class="tabsAction">';

	if (count($array_selected)) {
		print '<a class="butAction" href="export.php?step='.($step + 1).'&datatoexport='.$datatoexport.'">'.$langs->trans("NextStep").'</a>';
	}

	print '</div>';


	// Area for profils export
	if (count($array_selected)) {
		print '<br>';

		print '<div class="marginbottomonly">';
		print '<span class="opacitymedium">'.$langs->trans("SaveExportModel").'</span>';
		print '</div>';

		print '<form class="nocellnopadd" action="export.php" method="post">';
		print '<input type="hidden" name="token" value="'.newToken().'">';
		print '<input type="hidden" name="action" value="add_export_model">';
		print '<input type="hidden" name="step" value="'.$step.'">';
		print '<input type="hidden" name="datatoexport" value="'.$datatoexport.'">';
		print '<input type="hidden" name="hexa" value="'.$hexa.'">';

		print '<div class="div-table-responsive-no-min">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans("ExportModelName").'</td>';
		print '<td>'.$langs->trans("Visibility").'</td>';
		print '<td></td>';
		print '</tr>';

		print '<tr class="oddeven">';
		print '<td><input name="export_name" value=""></td>';
		print '<td>';
		$arrayvisibility = array('private' => $langs->trans("Private"), 'all' => $langs->trans("Everybody"));
		print $form->selectarray('visibility', $arrayvisibility, 'private');
		print '</td>';
		print '<td class="right">';
		print '<input type="submit" class="button reposition button-save small" value="'.$langs->trans("Save").'">';
		print '</td></tr>';

		$tmpuser = new User($db);

		// List of existing export profils
		$sql = "SELECT rowid, label, fk_user, entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."export_model";
		$sql .= " WHERE type = '".$db->escape($datatoexport)."'";
		if (!getDolGlobalString('EXPORTS_SHARE_MODELS')) {	// EXPORTS_SHARE_MODELS means all templates are visible, whatever is owner.
			$sql .= " AND fk_user IN (0, ".((int) $user->id).")";
		}
		$sql .= " ORDER BY rowid";
		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $db->fetch_object($resql);

				print '<tr class="oddeven"><td>';
				print $obj->label;
				print '</td>';
				print '<td>';
				if (empty($obj->fk_user)) {
					print $langs->trans("Everybody");
				} else {
					$tmpuser->fetch($obj->fk_user);
					print $tmpuser->getNomUrl(1);
				}
				print '</td>';
				print '<td class="right">';
				print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?step='.$step.'&datatoexport='.$datatoexport.'&action=deleteprof&token='.newToken().'&id='.$obj->rowid.'">';
				print img_delete();
				print '</a>';
				print '</tr>';
				$i++;
			}
		} else {
			dol_print_error($db);
		}

		print '</table>';
		print '</div>';

		print '</form>';
	}
}

if ($step == 5 && $datatoexport) {
	if (count($array_selected) < 1) {      // This occurs when going back to page after session expired
		// Switch to step 2
		header("Location: ".DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport);
		exit;
	}

	// Check permission
	if (empty($objexport->array_export_perms[0])) {
		accessforbidden();
	}

	asort($array_selected);

	llxHeader('', $langs->trans("NewExport"), 'EN:Module_Exports_En|FR:Module_Exports|ES:M&oacute;dulo_Exportaciones', '', 0, 0, '', '', '', 'mod-exports page-export action-step5');

	$h = 0;
	$stepoffset = 0;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=1';
	$head[$h][1] = $langs->trans("Step")." 1";
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=2&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." 2";
	$h++;

	// si le filtrage est parameter pour l'export ou pas
	if ($usefilters && isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0])) {
		$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=3&datatoexport='.$datatoexport;
		$head[$h][1] = $langs->trans("Step")." 3";
		$h++;
		$stepoffset++;
	}

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=4&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." ".(3 + $stepoffset);
	$h++;

	$head[$h][0] = DOL_URL_ROOT.'/exports/export.php?step=5&datatoexport='.$datatoexport;
	$head[$h][1] = $langs->trans("Step")." ".(4 + $stepoffset);
	$hselected = $h;
	$h++;

	print dol_get_fiche_head($head, $hselected, '', -2);

	/*
	 * Confirmation suppression fichier
	 */
	if ($action == 'remove_file') {
		print $form->formconfirm($_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport.'&file='.urlencode(GETPOST("file")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', 0, 1);
	}

	print '<div class="fichecenter">';
	print '<div class="underbanner clearboth"></div>';

	print '<table class="border tableforfield centpercent">';

	// Module
	print '<tr><td class="titlefield">'.$langs->trans("Module").'</td>';
	print '<td>';
	//print img_object($objexport->array_export_module[0]->getName(),$objexport->array_export_module[0]->picto).' ';
	print $objexport->array_export_module[0]->getName();
	print '</td></tr>';

	// Dataset to export
	print '<tr><td>'.$langs->trans("DatasetToExport").'</td>';
	print '<td>';
	$entity = preg_replace('/:.*$/', '', $objexport->array_export_icon[0]);
	$entityicon = strtolower(!empty($entitytoicon[$entity]) ? $entitytoicon[$entity] : $entity);
	print img_object($objexport->array_export_module[0]->getName(), $entityicon).' ';
	print $objexport->array_export_label[0];
	print '</td></tr>';

	// List of exported fields
	print '<tr><td>'.$langs->trans("ExportedFields").'</td>';
	$list = '';
	foreach ($array_selected as $code => $label) {
		if (isset($objexport->array_export_fields[0][$code])) {
			$list .= (!empty($list) ? ', ' : '');
			$list .= $langs->trans($objexport->array_export_fields[0][$code]);
		}
	}
	print '<td>'.$list.'</td></tr>';

	// List of filtered fields
	if (isset($objexport->array_export_TypeFields[0]) && is_array($objexport->array_export_TypeFields[0])) {
		print '<tr><td>'.$langs->trans("FilteredFields").'</td>';
		$list = '';
		if (!empty($array_filtervalue)) {
			foreach ($array_filtervalue as $code => $value) {
				if (preg_match('/^FormSelect:/', $objexport->array_export_TypeFields[0][$code])) {
					// We discard this filter if it is a FromSelect field with a value of -1.
					if ($value == -1) {
						continue;
					}
				}
				if (isset($objexport->array_export_fields[0][$code])) {
					$list .= ($list ? ', ' : '');
					if (isset($array_filtervalue[$code]) && preg_match('/^\s*[<>]/', $array_filtervalue[$code])) {
						$list .= '<span class="opacitymedium">'.$langs->trans($objexport->array_export_fields[0][$code]).'</span>'.(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '');
					} else {
						$list .= '<span class="opacitymedium">'.$langs->trans($objexport->array_export_fields[0][$code])."</span>='".(isset($array_filtervalue[$code]) ? $array_filtervalue[$code] : '')."'";
					}
				}
			}
		}
		print '<td>'.(!empty($list) ? $list : '<span class="opacitymedium">'.$langs->trans("None").'</span>').'</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';

	print '<br>';

	// List of available export formats
	$htmltabloflibs = '<!-- Table with available export formats --><br>';
	$htmltabloflibs .= '<table class="noborder centpercent nomarginbottom">';
	$htmltabloflibs .= '<tr class="liste_titre">';
	$htmltabloflibs .= '<td>'.$langs->trans("AvailableFormats").'</td>';
	$htmltabloflibs .= '<td>'.$langs->trans("LibraryUsed").'</td>';
	$htmltabloflibs .= '<td class="right">'.$langs->trans("LibraryVersion").'</td>';
	$htmltabloflibs .= '</tr>'."\n";

	$liste = $objmodelexport->listOfAvailableExportFormat($db);
	$listeall = $liste;
	foreach ($listeall as $key => $val) {
		if (preg_match('/__\(Disabled\)__/', $listeall[$key])) {
			$listeall[$key] = preg_replace('/__\(Disabled\)__/', '('.$langs->transnoentitiesnoconv("Disabled").')', $listeall[$key]);
			unset($liste[$key]);
		}

		$htmltabloflibs .= '<tr class="oddeven">';
		$htmltabloflibs .= '<td>'.img_picto_common($key, $objmodelexport->getPictoForKey($key)).' ';
		$text = $objmodelexport->getDriverDescForKey($key);
		$label = $listeall[$key];
		// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
		$htmltabloflibs .= $form->textwithpicto($label, $text).'</td>';
		$htmltabloflibs .= '<td>'.$objmodelexport->getLibLabelForKey($key).'</td>';
		$htmltabloflibs .= '<td class="right">'.$objmodelexport->getLibVersionForKey($key).'</td>';
		$htmltabloflibs .= '</tr>'."\n";
	}
	$htmltabloflibs .= '</table><br>';

	print '<span class="opacitymedium">'.$form->textwithpicto($langs->trans("NowClickToGenerateToBuildExportFile"), $htmltabloflibs, 1, 'help', '', 0, 2, 'helphonformat').'</span>';
	//print $htmltabloflibs;
	print '<br>';

	print '</div>';


	if ($sqlusedforexport && $user->admin) {
		print info_admin($langs->trans("SQLUsedForExport").':<br> '.$sqlusedforexport, 0, 0, '1', '', 'TechnicalInformation');
	}


	if (!is_dir($conf->export->dir_temp)) {
		dol_mkdir($conf->export->dir_temp);
	}

	// Show existing generated documents
	// NB: La fonction show_documents rescanne les modules qd genallowed=1, sinon prend $liste
	print $formfile->showdocuments('export', '', $upload_dir, $_SERVER["PHP_SELF"].'?step=5&datatoexport='.$datatoexport, $liste, 1, (GETPOST('model') ? GETPOST('model') : 'csv'), 1, 1, 0, 0, 0, '', 'none', '', '', '');
}

llxFooter();

$db->close();

exit; // don't know why but apache hangs with php 5.3.10-1ubuntu3.12 and apache 2.2.2 if i remove this exit or replace with return


/**
 * 	Return table name of an alias. For this, we look for the "tablename as alias" in sql string.
 *
 * 	@param	string	$code				Alias.Fieldname
 * 	@param	string	$sqlmaxforexport	SQL request to parse
 * 	@return	string						Table name of field
 */
function getablenamefromfield($code, $sqlmaxforexport)
{
	$alias = preg_replace('/\.(.*)$/i', '', $code); // Keep only 'Alias' and remove '.Fieldname'
	$regexstring = '/([a-zA-Z_]+) as '.preg_quote($alias).'[, \)]/i';

	$newsql = $sqlmaxforexport;
	$newsql = preg_replace('/^(.*) FROM /i', '', $newsql); // Remove part before the FROM
	$newsql = preg_replace('/WHERE (.*)$/i', '', $newsql); // Remove part after the WHERE so we have now only list of table aliases in a string. We must keep the ' ' before WHERE

	if (preg_match($regexstring, $newsql, $reg)) {
		return $reg[1]; // The tablename
	} else {
		return '';
	}
}
