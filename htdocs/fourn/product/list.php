<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010	   Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel   <christophe.battarel@altairis.fr>
 * Copyright (C) 2013      Cédric Salvador       <csalvador@gpcsolutions.fr>
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
 *    \file       htdocs/fourn/product/list.php
 *    \ingroup    product
 *    \brief      Page to list supplier products and services
 */


// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT .'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT .'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT .'/fourn/class/fournisseur.class.php';


// Load translation files required by the page
$langs->loadLangs(array('products', 'suppliers'));

// Get Parameters
$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'create'/'add', 'edit'/'update', 'view', ...
$toselect   = GETPOST('toselect', 'array:int'); // Array of ids of elements selected into a list
$optioncss = GETPOST('optioncss', 'alpha');
$mode       = GETPOST('mode', 'aZ'); // The output mode ('list', 'kanban', 'hierarchy', 'calendar', ...)

$sref = GETPOST('sref', 'alphanohtml');
$sRefSupplier = GETPOST('srefsupplier');
$snom = GETPOST('snom', 'alphanohtml');
$type = GETPOST('type', 'alphanohtml');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) {
	$sortfield = "p.ref"; // Set here default search field
}
if (!$sortorder) {
	$sortorder = "ASC";
}

$fourn_id = GETPOST('fourn_id', 'intcomma');
if ($user->socid) {
	$fourn_id = $user->socid;
}

$catid = GETPOST('catid', 'intcomma');

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('supplierpricelist'));
$extrafields = new ExtraFields($db);

if (!$user->hasRight("produit", "lire") && !$user->hasRight("service", "lire")) {
	accessforbidden();
}

// Permissions
$permissiontoadd = ($user->hasRight('product', 'read') || $user->hasRight('service', 'read'));


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
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
		$sref = '';
		$sRefSupplier = '';
		$snom = '';
		$search_field1 = '';
		$search_field2 = '';
		$search_date_creation = '';
		$search_date_modification = '';
		$toselect = array();
		$search_array_options = array();
	}
}


/*
 * View
 */

$form = new Form($db);
$productstatic = new Product($db);
$companystatic = new Societe($db);

$title = $langs->trans('Supplier')." - ".$langs->trans('ProductsAndServices');

if ($fourn_id) {
	$supplier = new Fournisseur($db);
	$supplier->fetch($fourn_id);
}


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT p.rowid, p.label, p.ref, p.fk_product_type, p.entity, p.tosell, p.tobuy, p.barcode, p.fk_barcode_type,";
$sql .= " ppf.fk_soc, ppf.ref_fourn, ppf.price as price, ppf.quantity as qty, ppf.unitprice,";
$sql .= " s.rowid as socid, s.nom as name";
// Add fields to SELECT from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
$sql .= $hookmanager->resPrint;

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
if ($catid) {
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."categorie_product as cp ON cp.fk_product = p.rowid";
}
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as ppf ON p.rowid = ppf.fk_product AND p.entity = ppf.entity";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON ppf.fk_soc = s.rowid AND s.entity IN (".getEntity('societe').")";
$sql .= " WHERE p.entity IN (".getEntity('product').")";
if ($sRefSupplier) {
	$sql .= natural_search('ppf.ref_fourn', $sRefSupplier);
}
if (GETPOST('type')) {
	$sql .= " AND p.fk_product_type = ".GETPOSTINT('type');
}
if ($sref) {
	$sql .= natural_search('p.ref', $sref);
}
if ($snom) {
	$sql .= natural_search('p.label', $snom);
}
if ($catid) {
	$sql .= " AND cp.fk_categorie = ".((int) $catid);
}
if ($fourn_id > 0) {
	$sql .= " AND ppf.fk_soc = ".((int) $fourn_id);
}

// Add WHERE filters from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

dol_syslog("fourn/product/list.php:", LOG_DEBUG);
$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

$i = 0;

if ($num == 1 && (GETPOST("mode") == 'search')) {
	$objp = $db->fetch_object($resql);
	header("Location: ".DOL_URL_ROOT."/product/card.php?id=".$objp->rowid);
	exit;
}

if (!empty($supplier->id)) {
	$title = $langs->trans("ListOfSupplierProductForSupplier", $supplier->name);
} else {
	$title = $langs->trans("List");
}


// Output page
// --------------------------------------------------------------------

llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist');

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
$param = "&sref=".$sref."&snom=".$snom."&fourn_id=".$fourn_id.(isset($type) ? "&amp;type=".$type : "").(empty($sRefSupplier) ? "" : "&amp;srefsupplier=".$sRefSupplier);

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('New'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/product/list.php?action=create&backtopage='.urlencode($_SERVER['PHP_SELF']), '', $permissiontoadd);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'generic', 0, $newcardbutton);

if (!empty($catid)) {
	print "<div id='ways'>";
	$c = new Categorie($db);
	$ways = $c->print_all_ways(' &gt; ', 'fourn/product/list.php');
	print " &gt; ".$ways[0]."<br>\n";
	print "</div><br>";
}

$arrayofmassactions = array(
	//'generate_doc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("ReGeneratePDF"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($fourn_id > 0) {
	print '<input type="hidden" name="fourn_id" value="'.$fourn_id.'">';
}
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="type" value="'.$type.'">';

$topicmail = "Information";
$modelmail = "product";
$objecttmp = new Product($db);
$trackid = 'prod'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive-no-min">';
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
print '<td class="liste_titre">';
print '<input class="flat maxwidth100" type="text" name="sref" value="'.$sref.'">';
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat maxwidth100" type="text" name="srefsupplier" value="'.$sRefSupplier.'">';
print '</td>';
print '<td class="liste_titre">';
print '<input class="flat maxwidth100" type="text" name="snom" value="'.$snom.'">';
print '</td>';
print '<td></td>';
print '<td></td>';
print '<td></td>';
print '<td></td>';
// add filters from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print_liste_field_titre("Ref", $_SERVER["PHP_SELF"], "p.ref", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre("RefSupplierShort", $_SERVER["PHP_SELF"], "ppf.ref_fourn", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre("Label", $_SERVER["PHP_SELF"], "p.label", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre("Supplier", $_SERVER["PHP_SELF"], "ppf.fk_soc", $param, "", "", $sortfield, $sortorder);
print_liste_field_titre("BuyingPrice", $_SERVER["PHP_SELF"], "ppf.price", $param, "", '', $sortfield, $sortorder, 'right ');
print_liste_field_titre("QtyMin", $_SERVER["PHP_SELF"], "ppf.quantity", $param, "", '', $sortfield, $sortorder, 'right ');
print_liste_field_titre("UnitPrice", $_SERVER["PHP_SELF"], "ppf.unitprice", $param, "", '', $sortfield, $sortorder, 'right ');
// add header cells from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action);
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}
print $hookmanager->resPrint;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Loop on record
// --------------------------------------------------------------------
$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;
$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$objp = $db->fetch_object($resql);
	if (empty($objp)) {
		break; // Should not happen
	}

	$productstatic->id = $objp->rowid;
	$productstatic->ref = $objp->ref;
	$productstatic->type = $objp->fk_product_type;
	$productstatic->entity = $objp->entity;
	$productstatic->status = $objp->tosell;
	$productstatic->status_buy = $objp->tobuy;
	$productstatic->barcode = $objp->barcode;
	$productstatic->barcode_type = $objp->fk_barcode_type;

	print '<tr data-rowid="'.$object->id.'" class="oddeven">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	print '<td>';
	print $productstatic->getNomUrl(1, 'supplier');
	print '</td>';

	print '<td>'.$objp->ref_fourn.'</td>';

	print '<td>'.$objp->label.'</td>'."\n";

	$companystatic->name = $objp->name;
	$companystatic->id = $objp->socid;
	print '<td>';
	if ($companystatic->id > 0) {
		print $companystatic->getNomUrl(1, 'supplier');
	}
	print '</td>';

	print '<td class="right">'.(isset($objp->price) ? price($objp->price) : '').'</td>';

	print '<td class="right">'.$objp->qty.'</td>';

	print '<td class="right">'.(isset($objp->unitprice) ? price($objp->unitprice) : '').'</td>';

	// add additional columns from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objp, $action);
	if ($reshook < 0) {
		setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	}
	print $hookmanager->resPrint;

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	print '</tr>'."\n";
	$i++;
}

// Show total line
include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';

// If no record found
if ($num == 0) {
	$colspan = 1;
	foreach ($arrayfields as $key => $val) {
		if (!empty($val['checked'])) {
			$colspan++;
		}
	}
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
