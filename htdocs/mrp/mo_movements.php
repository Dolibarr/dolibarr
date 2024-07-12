<?php
/* Copyright (C) 2019		Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2022		Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *    \file       mo_movements.php
 *    \ingroup    mrp
 *    \brief      Page to show stock movements of a MO
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/html.formproduct.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/mouvementstock.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';

require_once DOL_DOCUMENT_ROOT.'/mrp/class/mo.class.php';
require_once DOL_DOCUMENT_ROOT.'/mrp/lib/mrp_mo.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("mrp", "stocks", "other"));

// Get parameters
$id          = GETPOSTINT('id');
$ref         = GETPOST('ref', 'alpha');
$action      = GETPOST('action', 'aZ09');
$confirm     = GETPOST('confirm', 'alpha');
$cancel      = GETPOST('cancel', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'mostockmovement'; // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha');
$optioncss   = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$massaction  = GETPOST('massaction', 'aZ09');
$lineid      = GETPOSTINT('lineid');

$msid  = GETPOSTINT('msid');

$year  = GETPOST("year");		// TODO Rename into search_year
$month = GETPOST("month");		// TODO Rename into search_month

$search_ref = GETPOST('search_ref', 'alpha');
$search_movement = GETPOST("search_movement", 'alpha');
$search_product_ref = trim(GETPOST("search_product_ref", 'alpha'));
$search_product = trim(GETPOST("search_product", 'alpha'));
$search_warehouse = trim(GETPOST("search_warehouse", 'alpha'));
$search_inventorycode = trim(GETPOST("search_inventorycode", 'alpha'));
$search_user = trim(GETPOST("search_user", 'alpha'));
$search_batch = trim(GETPOST("search_batch", 'alpha'));
$search_qty = trim(GETPOST("search_qty", 'alpha'));
$search_type_mouvement = GETPOST('search_type_mouvement', "intcomma");

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$page  = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOSTINT("page");
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
if (!$sortfield) {
	$sortfield = "m.datem";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

// Initialize a technical objects
$object = new Mo($db);
$extrafields = new ExtraFields($db);
$diroutputmassaction = $conf->mrp->dir_output.'/temp/massgeneration/'.$user->id;
$hookmanager->initHooks(array('mocard', 'globalcard')); // Note that conf->hooks_modules contains array

// Fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);

$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// Initialize array of search criteria
$search_all = trim(GETPOST("search_all", 'alpha'));
$search = array();
foreach ($object->fields as $key => $val) {
	if (GETPOST('search_'.$key, 'alpha')) {
		$search[$key] = GETPOST('search_'.$key, 'alpha');
	}
}

if (empty($action) && empty($id) && empty($ref)) {
	$action = 'view';
}

// Load object
include DOL_DOCUMENT_ROOT.'/core/actions_fetchobject.inc.php'; // Must be 'include', not 'include_once'.

// Security check - Protection if external user
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
$isdraft = (($object->status == $object::STATUS_DRAFT) ? 1 : 0);
$result = restrictedArea($user, 'mrp', $object->id, 'mrp_mo', '', 'fk_soc', 'rowid', $isdraft);

$objectlist = new MouvementStock($db);

// Definition of fields for list
$arrayfields = array(
	'm.rowid' => array('label' => "Ref", 'checked' => 1, 'position' => 1),
	'm.datem' => array('label' => "Date", 'checked' => 1, 'position' => 2),
	'p.ref' => array('label' => "ProductRef", 'checked' => 1, 'css' => 'maxwidth100', 'position' => 3),
	'p.label' => array('label' => "ProductLabel", 'checked' => 0, 'position' => 5),
	'm.batch' => array('label' => "BatchNumberShort", 'checked' => 1, 'position' => 8, 'enabled' => (isModEnabled('productbatch'))),
	'pl.eatby' => array('label' => "EatByDate", 'checked' => 0, 'position' => 9, 'enabled' => (isModEnabled('productbatch'))),
	'pl.sellby' => array('label' => "SellByDate", 'checked' => 0, 'position' => 10, 'enabled' => (isModEnabled('productbatch'))),
	'e.ref' => array('label' => "Warehouse", 'checked' => 1, 'position' => 100, 'enabled' => (!($id > 0))), // If we are on specific warehouse, we hide it
	'm.fk_user_author' => array('label' => "Author", 'checked' => 0, 'position' => 120),
	'm.inventorycode' => array('label' => "InventoryCodeShort", 'checked' => 1, 'position' => 130),
	'm.label' => array('label' => "MovementLabel", 'checked' => 1, 'position' => 140),
	'm.type_mouvement' => array('label' => "TypeMovement", 'checked' => 0, 'position' => 150),
	'origin' => array('label' => "Origin", 'checked' => 1, 'position' => 155),
	'm.fk_projet' => array('label' => 'Project', 'checked' => 0, 'position' => 180),
	'm.value' => array('label' => "Qty", 'checked' => 1, 'position' => 200),
	'm.price' => array('label' => "UnitPurchaseValue", 'checked' => 0, 'position' => 210)
	//'m.datec'=>array('label'=>"DateCreation", 'checked'=>0, 'position'=>500),
	//'m.tms'=>array('label'=>"DateModificationShort", 'checked'=>0, 'position'=>500)
);
if (getDolGlobalString('PRODUCT_DISABLE_SELLBY')) {
	unset($arrayfields['pl.sellby']);
}
if (getDolGlobalString('PRODUCT_DISABLE_EATBY')) {
	unset($arrayfields['pl.eatby']);
}
$objectlist->fields = dol_sort_array($objectlist->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');

// Permissions
$permissionnote = $user->hasRight('mrp', 'write'); // Used by the include of actions_setnotes.inc.php
$permissiondellink = $user->hasRight('mrp', 'write'); // Used by the include of actions_dellink.inc.php
$permissiontoadd = $user->hasRight('mrp', 'write'); // Used by the include of actions_addupdatedelete.inc.php and actions_lineupdown.inc.php
$permissiontodelete = $user->hasRight('mrp', 'delete') || ($permissiontoadd && isset($object->status) && $object->status == $object::STATUS_DRAFT);
$upload_dir = $conf->mrp->multidir_output[isset($object->entity) ? $object->entity : 1];

$permissiontoproduce = $permissiontoadd;
$permissiontoupdatecost = $user->hasRight('bom', 'write'); // User who can define cost must have knowledge of pricing

if ($permissiontoupdatecost) {
	$arrayfields['m.price']['enabled'] = 1;
}

$arrayofselected = array();


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

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Do we click on purge search criteria ?
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // Both test are required to be compatible with all browsers
	$year = '';
	$month = '';
	$search_ref = '';
	$search_movement = "";
	$search_type_mouvement = "";
	$search_inventorycode = "";
	$search_product_ref = "";
	$search_product = "";
	$search_warehouse = "";
	$search_user = "";
	$search_batch = "";
	$search_qty = '';
	$search_all = "";
	$toselect = array();
	$search_array_options = array();
}

if (empty($reshook)) {
	$error = 0;

	$backurlforlist = dol_buildpath('/mrp/mo_list.php', 1);

	if (empty($backtopage) || ($cancel && empty($id))) {
		//var_dump($backurlforlist);exit;
		if (empty($id) && (($action != 'add' && $action != 'create') || $cancel)) {
			$backtopage = $backurlforlist;
		} else {
			$backtopage = DOL_URL_ROOT.'/mrp/mo_production.php?id='.($id > 0 ? $id : '__ID__');
		}
	}
	$triggermodname = 'MO_MODIFY'; // Name of trigger action code to execute when we modify record

	// Actions cancel, add, update, delete or clone
	include DOL_DOCUMENT_ROOT.'/core/actions_addupdatedelete.inc.php';

	// Actions when linking object each other
	include DOL_DOCUMENT_ROOT.'/core/actions_dellink.inc.php';

	// Actions when printing a doc from card
	include DOL_DOCUMENT_ROOT.'/core/actions_printing.inc.php';

	// Actions to send emails
	$triggersendname = 'MO_SENTBYMAIL';
	$autocopy = 'MAIN_MAIL_AUTOCOPY_MO_TO';
	$trackid = 'mo'.$object->id;
	include DOL_DOCUMENT_ROOT.'/core/actions_sendmails.inc.php';

	// Action to move up and down lines of object
	//include DOL_DOCUMENT_ROOT.'/core/actions_lineupdown.inc.php';	// Must be 'include', not 'include_once'

	if ($action == 'set_thirdparty' && $permissiontoadd) {
		$object->setValueFrom('fk_soc', GETPOSTINT('fk_soc'), '', '', 'date', '', $user, $triggermodname);
	}
	if ($action == 'classin' && $permissiontoadd) {
		$object->setProject(GETPOSTINT('projectid'));
	}

	if ($action == 'confirm_reopen') {
		$result = $object->setStatut($object::STATUS_INPROGRESS, 0, '', 'MRP_REOPEN');
	}
}



/*
 * View
 */

$form = new Form($db);
$formproject = new FormProjets($db);
$formproduct = new FormProduct($db);
$productstatic = new Product($db);
$productlot = new Productlot($db);
$warehousestatic = new Entrepot($db);
$userstatic = new User($db);

$help_url = 'EN:Module_Manufacturing_Orders|FR:Module_Ordres_de_Fabrication|DE:Modul_Fertigungsauftrag';

llxHeader('', $langs->trans('Mo'), $help_url);

// Part to show record
if ($object->id > 0 && (empty($action) || ($action != 'edit' && $action != 'create'))) {
	$res = $object->fetch_thirdparty();
	$res = $object->fetch_optionals();

	$head = moPrepareHead($object);

	print dol_get_fiche_head($head, 'stockmovement', $langs->trans("ManufacturingOrder"), -1, $object->picto);

	$formconfirm = '';

	// Confirmation to delete
	if ($action == 'delete') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('DeleteMo'), $langs->trans('ConfirmDeleteMo'), 'confirm_delete', '', 0, 1);
	}
	// Confirmation to delete line
	if ($action == 'deleteline') {
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id.'&lineid='.$lineid, $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_deleteline', '', 0, 1);
	}
	// Clone confirmation
	if ($action == 'clone') {
		// Create an array for form
		$formquestion = array();
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('ToClone'), $langs->trans('ConfirmCloneMo', $object->ref), 'confirm_clone', $formquestion, 'yes', 1);
	}

	// Confirmation of action xxxx
	if ($action == 'xxx') {
		$formquestion = array();
		/*
		$forcecombo=0;
		if ($conf->browser->name == 'ie') $forcecombo = 1;	// There is a bug in IE10 that make combo inside popup crazy
		$formquestion = array(
			// 'text' => $langs->trans("ConfirmClone"),
			// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' => 1),
			// array('type' => 'checkbox', 'name' => 'update_prices', 'label' => $langs->trans("PuttingPricesUpToDate"), 'value' => 1),
			// array('type' => 'other',    'name' => 'idwarehouse',   'label' => $langs->trans("SelectWarehouseForStockDecrease"), 'value' => $formproduct->selectWarehouses(GETPOST('idwarehouse')?GETPOST('idwarehouse'):'ifone', 'idwarehouse', '', 1, 0, 0, '', 0, $forcecombo))
		);
		*/
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans('XXX'), $text, 'confirm_xxx', $formquestion, 0, 1, 220);
	}

	// Call Hook formConfirm
	$parameters = array('formConfirm' => $formconfirm, 'lineid' => $lineid);
	$reshook = $hookmanager->executeHooks('formConfirm', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$formconfirm .= $hookmanager->resPrint;
	} elseif ($reshook > 0) {
		$formconfirm = $hookmanager->resPrint;
	}

	// Print form confirm
	print $formconfirm;


	// Object card
	// ------------------------------------------------------------
	$linkback = '<a href="'.dol_buildpath('/mrp/mo_list.php', 1).'?restore_lastsearch_values=1'.(!empty($socid) ? '&socid='.$socid : '').'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<div class="refidno">';
	/*
	// Ref bis
	$morehtmlref.=$form->editfieldkey("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', 0, 1);
	$morehtmlref.=$form->editfieldval("RefBis", 'ref_client', $object->ref_client, $object, $user->rights->mrp->creer, 'string', '', null, null, '', 1);*/
	// Thirdparty
	if (is_object($object->thirdparty)) {
		$morehtmlref .= $object->thirdparty->getNomUrl(1, 'customer');
		if (!getDolGlobalString('MAIN_DISABLE_OTHER_LINK') && $object->thirdparty->id > 0) {
			$morehtmlref .= ' (<a href="'.DOL_URL_ROOT.'/commande/list.php?socid='.$object->thirdparty->id.'&search_societe='.urlencode($object->thirdparty->name).'">'.$langs->trans("OtherOrders").'</a>)';
		}
	}
	// Project
	if (isModEnabled('project')) {
		$langs->load("projects");
		if (is_object($object->thirdparty)) {
			$morehtmlref .= '<br>';
		}
		if ($permissiontoadd) {
			$morehtmlref .= img_picto($langs->trans("Project"), 'project', 'class="pictofixedwidth"');
			if ($action != 'classify') {
				$morehtmlref .= '<a class="editfielda" href="'.$_SERVER['PHP_SELF'].'?action=classify&token='.newToken().'&id='.$object->id.'">'.img_edit($langs->transnoentitiesnoconv('SetProject')).'</a> ';
			}
			$morehtmlref .= $form->form_project($_SERVER['PHP_SELF'].'?id='.$object->id, $object->socid, $object->fk_project, ($action == 'classify' ? 'projectid' : 'none'), 0, 0, 0, 1, '', 'maxwidth300');
		} else {
			if (!empty($object->fk_project)) {
				$proj = new Project($db);
				$proj->fetch($object->fk_project);
				$morehtmlref .= $proj->getNomUrl(1);
				if ($proj->title) {
					$morehtmlref .= '<span class="opacitymedium"> - '.dol_escape_htmltag($proj->title).'</span>';
				}
			}
		}
	}
	$morehtmlref .= '</div>';


	dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


	print '<div class="fichecenter">';
	print '<div class="fichehalfleft">';
	print '<div class="underbanner clearboth"></div>';
	print '<table class="border centpercent tableforfield">'."\n";

	// Common attributes
	$keyforbreak = 'fk_warehouse';
	unset($object->fields['fk_project']);
	unset($object->fields['fk_soc']);
	include DOL_DOCUMENT_ROOT.'/core/tpl/commonfields_view.tpl.php';

	// Other attributes
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_view.tpl.php';

	print '</table>';
	print '</div>';
	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();

	/*
		print '<div class="tabsAction">';

		$parameters = array();
		// Note that $action and $object may be modified by hook
		$reshook = $hookmanager->executeHooks('addMoreActionsButtons', $parameters, $object, $action);
		if (empty($reshook)) {
			// Cancel - Reopen
			if ($permissiontoadd)
			{
				if ($object->status == $object::STATUS_VALIDATED || $object->status == $object::STATUS_INPROGRESS)
				{
					print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_close&confirm=yes">'.$langs->trans("Cancel").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_CANCELED)
				{
					print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen&confirm=yes">'.$langs->trans("Re-Open").'</a>'."\n";
				}

				if ($object->status == $object::STATUS_PRODUCED) {
					if ($permissiontoproduce) {
						print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=confirm_reopen">'.$langs->trans('ReOpen').'</a>';
					} else {
						print '<a class="butActionRefused classfortooltip" href="#" title="'.$langs->trans("NotEnoughPermissions").'">'.$langs->trans('ReOpen').'</a>';
					}
				}
			}
		}

		print '</div>';
	*/


	$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.entity,";
	$sql .= " e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu,";
	$sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
	$sql .= " m.batch, m.price,";
	$sql .= " m.type_mouvement,";
	$sql .= " pl.rowid as lotid, pl.eatby, pl.sellby,";
	$sql .= " u.login, u.photo, u.lastname, u.firstname";
	// Add fields from extrafields
	if (!empty($extrafields->attributes[$objectlist->table_element]['label'])) {
		foreach ($extrafields->attributes[$objectlist->table_element]['label'] as $key => $val) {
			$sql .= ($extrafields->attributes[$objectlist->table_element]['type'][$key] != 'separate' ? ", ef.".$key." as options_".$key : '');
		}
	}
	// Add fields from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $objectlist may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
	$sql .= " ".MAIN_DB_PREFIX."product as p,";
	$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
	if (!empty($extrafields->attributes[$objectlist->table_element]) && is_array($extrafields->attributes[$objectlist->table_element]['label']) && count($extrafields->attributes[$objectlist->table_element]['label'])) {
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$objectlist->table_element."_extrafields as ef on (m.rowid = ef.fk_object)";
	}
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";
	$sql .= " WHERE m.fk_product = p.rowid";
	$sql .= " AND m.origintype = 'mo' AND m.fk_origin = ".(int) $object->id;
	if ($msid > 0) {
		$sql .= " AND m.rowid = ".((int) $msid);
	}
	$sql .= " AND m.fk_entrepot = e.rowid";
	$sql .= " AND e.entity IN (".getEntity('stock').")";
	if (!getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
		$sql .= " AND p.fk_product_type = 0";
	}
	$sql .= dolSqlDateFilter('m.datem', 0, $month, $year);
	if (!empty($search_ref)) {
		$sql .= natural_search('m.rowid', $search_ref, 1);
	}
	if (!empty($search_movement)) {
		$sql .= natural_search('m.label', $search_movement);
	}
	if (!empty($search_inventorycode)) {
		$sql .= natural_search('m.inventorycode', $search_inventorycode);
	}
	if (!empty($search_product_ref)) {
		$sql .= natural_search('p.ref', $search_product_ref);
	}
	if (!empty($search_product)) {
		$sql .= natural_search('p.label', $search_product);
	}
	if ($search_warehouse != '' && $search_warehouse != '-1') {
		$sql .= natural_search('e.rowid', $search_warehouse, 2);
	}
	if (!empty($search_user)) {
		$sql .= natural_search(array('u.lastname', 'u.firstname', 'u.login'), $search_user);
	}
	if (!empty($search_batch)) {
		$sql .= natural_search('m.batch', $search_batch);
	}
	if ($search_qty != '') {
		$sql .= natural_search('m.value', $search_qty, 1);
	}
	if ($search_type_mouvement != '' && $search_type_mouvement != '-1') {
		$sql .= natural_search('m.type_mouvement', $search_type_mouvement, 2);
	}
	// Add where from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
	// Add where from hooks
	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $objectlist may have been modified by hook
	$sql .= $hookmanager->resPrint;
	$sql .= $db->order($sortfield, $sortorder);

	$nbtotalofrecords = '';
	if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
		$result = $db->query($sql);
		$nbtotalofrecords = $db->num_rows($result);
		if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller then paging size (filtering), goto and load page 0
			$page = 0;
			$offset = 0;
		}
	}
	$sql .= $db->plimit($limit + 1, $offset);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_print_error($db);
	}
	$num = $db->num_rows($resql);

	$param = '';
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($id > 0) {
		$param .= '&id='.urlencode((string) ($id));
	}
	if ($search_movement) {
		$param .= '&search_movement='.urlencode($search_movement);
	}
	if ($search_inventorycode) {
		$param .= '&search_inventorycode='.urlencode($search_inventorycode);
	}
	if ($search_type_mouvement) {
		$param .= '&search_type_mouvement='.urlencode($search_type_mouvement);
	}
	if ($search_product_ref) {
		$param .= '&search_product_ref='.urlencode($search_product_ref);
	}
	if ($search_product) {
		$param .= '&search_product='.urlencode($search_product);
	}
	if ($search_batch) {
		$param .= '&search_batch='.urlencode($search_batch);
	}
	if ($search_warehouse > 0) {
		$param .= '&search_warehouse='.urlencode($search_warehouse);
	}
	if ($search_user) {
		$param .= '&search_user='.urlencode($search_user);
	}

	// Add $param from extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

	// List of mass actions available
	$arrayofmassactions = array(
		//    'presend'=>$langs->trans("SendByMail"),
		//    'builddoc'=>$langs->trans("PDFMerge"),
	);
	//if ($user->rights->stock->supprimer) $arrayofmassactions['predelete']='<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
	if (in_array($massaction, array('presend', 'predelete'))) {
		$arrayofmassactions = array();
	}
	$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	if ($optioncss != '') {
		print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
	}
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
	print '<input type="hidden" name="action" value="list">';
	print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
	print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
	print '<input type="hidden" name="page" value="'.$page.'">';
	print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
	if ($id > 0) {
		print '<input type="hidden" name="id" value="'.$id.'">';
	}

	if ($id > 0) {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, '', 0, '', '', $limit);
	} else {
		print_barre_liste('', $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, '', '', $limit);
	}

	$moreforfilter = '';

	$parameters = array();
	$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
	if (empty($reshook)) {
		$moreforfilter .= $hookmanager->resPrint;
	} else {
		$moreforfilter = $hookmanager->resPrint;
	}

	if (!empty($moreforfilter)) {
		print '<div class="liste_titre liste_titre_bydiv centpercent">';
		print $moreforfilter;
		print '</div>';
	}

	$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
	$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')); // This also change content of $arrayfields
	$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

	print '<div class="div-table-responsive">';
	print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

	// Fields title search
	print '<tr class="liste_titre_filter">';
	// Actions
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
	}
	if (!empty($arrayfields['m.rowid']['checked'])) {
		// Ref
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth25" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.datem']['checked'])) {
		print '<td class="liste_titre nowraponall">';
		print '<input class="flat" type="text" size="2" maxlength="2" placeholder="'.dol_escape_htmltag($langs->trans("Month")).'" name="month" value="'.$month.'">';
		if (!isModEnabled('productbatch')) {
			print '&nbsp;';
		}
		//else print '<br>';
		$syear = $year ? $year : -1;
		print '<input class="flat maxwidth50" type="text" maxlength="4" placeholder="'.dol_escape_htmltag($langs->trans("Year")).'" name="year" value="'.($syear > 0 ? $syear : '').'">';
		//print $formother->selectyear($syear,'year',1, 20, 5);
		print '</td>';
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		// Product Ref
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth75" type="text" name="search_product_ref" value="'.dol_escape_htmltag($search_product_ref).'">';
		print '</td>';
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		// Product label
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth100" type="text" name="search_product" value="'.dol_escape_htmltag($search_product).'">';
		print '</td>';
	}
	// Batch
	if (!empty($arrayfields['m.batch']['checked'])) {
		print '<td class="liste_titre center"><input class="flat maxwidth75" type="text" name="search_batch" value="'.dol_escape_htmltag($search_batch).'"></td>';
	}
	if (!empty($arrayfields['pl.eatby']['checked'])) {
		print '<td class="liste_titre left">';
		print '</td>';
	}
	if (!empty($arrayfields['pl.sellby']['checked'])) {
		print '<td class="liste_titre left">';
		print '</td>';
	}
	// Warehouse
	if (!empty($arrayfields['e.ref']['checked'])) {
		print '<td class="liste_titre maxwidthonsmartphone left">';
		//print '<input class="flat" type="text" size="8" name="search_warehouse" value="'.($search_warehouse).'">';
		print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	if (!empty($arrayfields['m.fk_user_author']['checked'])) {
		// Author
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="6" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.inventorycode']['checked'])) {
		// Inventory code
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="4" name="search_inventorycode" value="'.dol_escape_htmltag($search_inventorycode).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.label']['checked'])) {
		// Label of movement
		print '<td class="liste_titre left">';
		print '<input class="flat" type="text" size="8" name="search_movement" value="'.dol_escape_htmltag($search_movement).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		// Type of movement
		print '<td class="liste_titre center">';
		//print '<input class="flat" type="text" size="3" name="search_type_mouvement" value="'.dol_escape_htmltag($search_type_mouvement).'">';
		print '<select id="search_type_mouvement" name="search_type_mouvement" class="maxwidth150">';
		print '<option value="" '.(($search_type_mouvement == "") ? 'selected="selected"' : '').'>&nbsp;</option>';
		print '<option value="0" '.(($search_type_mouvement == "0") ? 'selected="selected"' : '').'>'.$langs->trans('StockIncreaseAfterCorrectTransfer').'</option>';
		print '<option value="1" '.(($search_type_mouvement == "1") ? 'selected="selected"' : '').'>'.$langs->trans('StockDecreaseAfterCorrectTransfer').'</option>';
		print '<option value="2" '.(($search_type_mouvement == "2") ? 'selected="selected"' : '').'>'.$langs->trans('StockDecrease').'</option>';
		print '<option value="3" '.(($search_type_mouvement == "3") ? 'selected="selected"' : '').'>'.$langs->trans('StockIncrease').'</option>';
		print '</select>';
		print ajax_combobox('search_type_mouvement');
		// TODO: add new function $formentrepot->selectTypeOfMovement(...) like
		// print $formproduct->selectWarehouses($search_warehouse, 'search_warehouse', 'warehouseopen,warehouseinternal', 1, 0, 0, '', 0, 0, null, 'maxwidth200');
		print '</td>';
	}
	if (!empty($arrayfields['origin']['checked'])) {
		// Origin of movement
		print '<td class="liste_titre left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.fk_projet']['checked'])) {
		// fk_project
		print '<td class="liste_titre" align="left">';
		print '&nbsp; ';
		print '</td>';
	}
	if (!empty($arrayfields['m.value']['checked'])) {
		// Qty
		print '<td class="liste_titre right">';
		print '<input class="flat" type="text" size="4" name="search_qty" value="'.dol_escape_htmltag($search_qty).'">';
		print '</td>';
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		// Price
		print '<td class="liste_titre left">';
		print '&nbsp; ';
		print '</td>';
	}


	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields);
	$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	// Date creation
	if (!empty($arrayfields['m.datec']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Date modification
	if (!empty($arrayfields['m.tms']['checked'])) {
		print '<td class="liste_titre">';
		print '</td>';
	}
	// Actions
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
	}
	print "</tr>\n";

	$totalarray = array();
	$totalarray['nbfield'] = 0;

	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.rowid']['checked'])) {
		print_liste_field_titre($arrayfields['m.rowid']['label'], $_SERVER["PHP_SELF"], 'm.rowid', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.datem']['checked'])) {
		print_liste_field_titre($arrayfields['m.datem']['label'], $_SERVER["PHP_SELF"], 'm.datem', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['p.ref']['checked'])) {
		print_liste_field_titre($arrayfields['p.ref']['label'], $_SERVER["PHP_SELF"], 'p.ref', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['p.label']['checked'])) {
		print_liste_field_titre($arrayfields['p.label']['label'], $_SERVER["PHP_SELF"], 'p.label', '', $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.batch']['checked'])) {
		print_liste_field_titre($arrayfields['m.batch']['label'], $_SERVER["PHP_SELF"], 'm.batch', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['pl.eatby']['checked'])) {
		print_liste_field_titre($arrayfields['pl.eatby']['label'], $_SERVER["PHP_SELF"], 'pl.eatby', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['pl.sellby']['checked'])) {
		print_liste_field_titre($arrayfields['pl.sellby']['label'], $_SERVER["PHP_SELF"], 'pl.sellby', '', $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['e.ref']['checked'])) {
		// We are on a specific warehouse card, no filter on other should be possible
		print_liste_field_titre($arrayfields['e.ref']['label'], $_SERVER["PHP_SELF"], "e.ref", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.fk_user_author']['checked'])) {
		print_liste_field_titre($arrayfields['m.fk_user_author']['label'], $_SERVER["PHP_SELF"], "m.fk_user_author", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.inventorycode']['checked'])) {
		print_liste_field_titre($arrayfields['m.inventorycode']['label'], $_SERVER["PHP_SELF"], "m.inventorycode", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.label']['checked'])) {
		print_liste_field_titre($arrayfields['m.label']['label'], $_SERVER["PHP_SELF"], "m.label", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.type_mouvement']['checked'])) {
		print_liste_field_titre($arrayfields['m.type_mouvement']['label'], $_SERVER["PHP_SELF"], "m.type_mouvement", "", $param, '', $sortfield, $sortorder, 'center ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['origin']['checked'])) {
		print_liste_field_titre($arrayfields['origin']['label'], $_SERVER["PHP_SELF"], "", "", $param, "", $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.fk_projet']['checked'])) {
		print_liste_field_titre($arrayfields['m.fk_projet']['label'], $_SERVER["PHP_SELF"], "m.fk_projet", "", $param, '', $sortfield, $sortorder);
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.value']['checked'])) {
		print_liste_field_titre($arrayfields['m.value']['label'], $_SERVER["PHP_SELF"], "m.value", "", $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.price']['checked'])) {
		print_liste_field_titre($arrayfields['m.price']['label'], $_SERVER["PHP_SELF"], "m.price", "", $param, '', $sortfield, $sortorder, 'right ');
		$totalarray['nbfield']++;
	}

	// Extra fields
	include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';

	// Hook fields
	$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder);
	$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (!empty($arrayfields['m.datec']['checked'])) {
		print_liste_field_titre($arrayfields['p.datec']['label'], $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
		$totalarray['nbfield']++;
	}
	if (!empty($arrayfields['m.tms']['checked'])) {
		print_liste_field_titre($arrayfields['p.tms']['label'], $_SERVER["PHP_SELF"], "p.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
		$totalarray['nbfield']++;
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
		$totalarray['nbfield']++;
	}
	print "</tr>\n";

	$i = 0;
	$savnbfield = $totalarray['nbfield'];
	$totalarray = array();
	$totalarray['nbfield'] = 0;
	$imaxinloop = ($limit ? min($num, $limit) : $num);
	while ($i < $imaxinloop) {
		$objp = $db->fetch_object($resql);

		// Multilangs
		if (getDolGlobalInt('MAIN_MULTILANGS')) { // If multilang is enabled
			// TODO Use a cache here
			$sql = "SELECT label";
			$sql .= " FROM ".MAIN_DB_PREFIX."product_lang";
			$sql .= " WHERE fk_product = ".((int) $objp->rowid);
			$sql .= " AND lang = '".$db->escape($langs->getDefaultLang())."'";
			$sql .= " LIMIT 1";

			$result = $db->query($sql);
			if ($result) {
				$objtp = $db->fetch_object($result);
				if (!empty($objtp->label)) {
					$objp->produit = $objtp->label;
				}
			}
		}

		$userstatic->id = $objp->fk_user_author;
		$userstatic->login = $objp->login;
		$userstatic->lastname = $objp->lastname;
		$userstatic->firstname = $objp->firstname;
		$userstatic->photo = $objp->photo;

		$productstatic->id = $objp->rowid;
		$productstatic->ref = $objp->product_ref;
		$productstatic->label = $objp->produit;
		$productstatic->type = $objp->type;
		$productstatic->entity = $objp->entity;
		$productstatic->status_batch = $objp->tobatch;

		$productlot->id = $objp->lotid;
		$productlot->batch = $objp->batch;
		$productlot->eatby = $objp->eatby;
		$productlot->sellby = $objp->sellby;

		$warehousestatic->id = $objp->entrepot_id;
		$warehousestatic->libelle = $objp->warehouse_ref; // deprecated
		$warehousestatic->label = $objp->warehouse_ref;
		$warehousestatic->lieu = $objp->lieu;

		if (!empty($objp->fk_origin)) {
			$origin = $objectlist->get_origin($objp->fk_origin, $objp->origintype);
		} else {
			$origin = '';
		}

		print '<tr class="oddeven">';
		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($objp->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$objp->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$objp->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}
		// Id movement
		if (!empty($arrayfields['m.rowid']['checked'])) {
			// This is primary not movement id
			print '<td>'.dol_escape_htmltag($objp->mid).'</td>';
		}
		if (!empty($arrayfields['m.datem']['checked'])) {
			// Date
			print '<td>'.dol_print_date($db->jdate($objp->datem), 'dayhour').'</td>';
		}
		if (!empty($arrayfields['p.ref']['checked'])) {
			// Product ref
			print '<td class="nowraponall">';
			print $productstatic->getNomUrl(1, 'stock', 16);
			print "</td>\n";
		}
		if (!empty($arrayfields['p.label']['checked'])) {
			// Product label
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($productstatic->label).'">';
			print $productstatic->label;
			print "</td>\n";
		}
		if (!empty($arrayfields['m.batch']['checked'])) {
			print '<td class="center nowraponall">';
			if ($productlot->id > 0) {
				print $productlot->getNomUrl(1);
			} else {
				print dol_escape_htmltag($productlot->batch); // the id may not be defined if movement was entered when lot was not saved or if lot was removed after movement.
			}
			print '</td>';
		}
		if (!empty($arrayfields['pl.eatby']['checked'])) {
			print '<td class="center">'.dol_print_date($objp->eatby, 'day').'</td>';
		}
		if (!empty($arrayfields['pl.sellby']['checked'])) {
			print '<td class="center">'.dol_print_date($objp->sellby, 'day').'</td>';
		}
		// Warehouse
		if (!empty($arrayfields['e.ref']['checked'])) {
			print '<td>';
			print $warehousestatic->getNomUrl(1);
			print "</td>\n";
		}
		// Author
		if (!empty($arrayfields['m.fk_user_author']['checked'])) {
			print '<td class="tdoverflowmax100">';
			print $userstatic->getNomUrl(-1);
			print "</td>\n";
		}
		// Inventory code
		if (!empty($arrayfields['m.inventorycode']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($objp->inventorycode).'">';
			//print '<a href="' . DOL_URL_ROOT . '/product/stock/movement_card.php' . '?id=' . $objp->entrepot_id . '&amp;search_inventorycode=' . $objp->inventorycode . '&amp;search_type_mouvement=' . $objp->type_mouvement . '">';
			print dol_escape_htmltag($objp->inventorycode);
			//print '</a>';
			print '</td>';
		}
		// Label of movement
		if (!empty($arrayfields['m.label']['checked'])) {
			print '<td class="tdoverflowmax300" title="'.dol_escape_htmltag($objp->label).'">'.dol_escape_htmltag($objp->label).'</td>';
		}
		// Type of movement
		if (!empty($arrayfields['m.type_mouvement']['checked'])) {
			switch ($objp->type_mouvement) {
				case "0":
					print '<td class="center">'.$langs->trans('StockIncreaseAfterCorrectTransfer').'</td>';
					break;
				case "1":
					print '<td class="center">'.$langs->trans('StockDecreaseAfterCorrectTransfer').'</td>';
					break;
				case "2":
					print '<td class="center">'.$langs->trans('StockDecrease').'</td>';
					break;
				case "3":
					print '<td class="center">'.$langs->trans('StockIncrease').'</td>';
					break;
			}
		}
		if (!empty($arrayfields['origin']['checked'])) {
			// Origin of movement
			print '<td class="nowraponall">'.$origin.'</td>';
		}
		if (!empty($arrayfields['m.fk_projet']['checked'])) {
			// fk_project
			print '<td>';
			if ($objp->fk_project != 0) {
				print $movement->get_origin($objp->fk_project, 'project');
			}
			print '</td>';
		}
		if (!empty($arrayfields['m.value']['checked'])) {
			// Qty
			print '<td class="right">';
			if ($objp->qty > 0) {
				print '<span class="stockmovemententry">+'.$objp->qty.'</span>';
			} else {
				print '<span class="stockmovementexit">'.$objp->qty.'<span>';
			}
			print '</td>';
		}
		if (!empty($arrayfields['m.price']['checked'])) {
			// Price
			print '<td class="right">';
			if ($objp->price != 0) {
				print '<span class="opacitymedium">'.price($objp->price).'</span>';
			}
			print '</td>';
		}
		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($objp->rowid, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$objp->rowid.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$objp->rowid.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print "</tr>\n";
		$i++;
	}
	if (empty($num)) {
		print '<tr><td colspan="'.$savnbfield.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	}

	$db->free($resql);

	print "</table>";
	print '</div>';
	print "</form>";
}

// End of page
llxFooter();
$db->close();
