<?php
/* Copyright (C) 2008-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *	\file       htdocs/ecm/search.php
 *	\ingroup    ecm
 *	\brief      Page to make advanced search into ECM
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("ecm", "companies", "other", "users", "orders", "propal", "bills", "contracts"));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'ecm', '');

// Load permissions
$user->loadRights('ecm');

// Get parameters
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$section = GETPOST('section');
if (!$section) {
	$section = 0;
}

$module  = GETPOST('module', 'alpha');
$website = GETPOST('website', 'alpha');
$pageid  = GETPOSTINT('pageid');
if (empty($module)) {
	$module = 'ecm';
}

$upload_dir = $conf->ecm->dir_output.'/'.$section;

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "ASC";
}
if (!$sortfield) {
	$sortfield = "label";
}

$ecmdir = new EcmDirectory($db);
if (!empty($section)) {
	$result = $ecmdir->fetch($section);
	if (!($result > 0)) {
		dol_print_error($db, $ecmdir->error);
		exit;
	}
}

$permissiontoread = $user->hasRight('ecm', 'read');

if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

// None



/*
 * View
 */

llxHeader('', '', '', '', 0, 0, '', '', '', 'mod-ecm page-search');

$form = new Form($db);
$ecmdirstatic = new EcmDirectory($db);
$userstatic = new User($db);


// Ajout rubriques automatiques
$rowspan = 0;
$sectionauto = array();
if (isModEnabled("product") || isModEnabled("service")) {
	$langs->load("products");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'product', 'test' => (isModEnabled("product") || isModEnabled("service")), 'label' => $langs->trans("ProductsAndServices"), 'desc' => $langs->trans("ECMDocsByProducts"));
}
if (isModEnabled("societe")) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'company', 'test' => isModEnabled('societe'), 'label' => $langs->trans("ThirdParties"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ThirdParties")));
}
if (isModEnabled("propal")) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'propal', 'test' => isModEnabled('propal'), 'label' => $langs->trans("Proposals"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Proposals")));
}
if (isModEnabled('contract')) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'contract', 'test' => isModEnabled('contract'), 'label' => $langs->trans("Contracts"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Contracts")));
}
if (isModEnabled('order')) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'order', 'test' => isModEnabled('order'), 'label' => $langs->trans("CustomersOrders"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Orders")));
}
if (isModEnabled('invoice')) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'invoice', 'test' => isModEnabled('invoice'), 'label' => $langs->trans("CustomersInvoices"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Invoices")));
}
if (isModEnabled('supplier_proposal')) {
	$langs->load("supplier_proposal");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'supplier_proposal', 'test' => isModEnabled('supplier_proposal'), 'label' => $langs->trans("SupplierProposals"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SupplierProposals")));
}
if (isModEnabled("supplier_order")) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'order_supplier', 'test' => isModEnabled("supplier_order"), 'label' => $langs->trans("SuppliersOrders"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("PurchaseOrders")));
}
if (isModEnabled("supplier_invoice")) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'invoice_supplier', 'test' => isModEnabled("supplier_invoice"), 'label' => $langs->trans("SuppliersInvoices"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SupplierInvoices")));
}
if (isModEnabled('tax')) {
	$langs->load("compta");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'tax', 'test' => isModEnabled('tax'), 'label' => $langs->trans("SocialContributions"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SocialContributions")));
}
if (isModEnabled('project')) {
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'project', 'test' => isModEnabled('project'), 'label' => $langs->trans("Projects"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Projects")));
}
if (isModEnabled('intervention')) {
	$langs->load("interventions");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'fichinter', 'test' => isModEnabled('intervention'), 'label' => $langs->trans("Interventions"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Interventions")));
}
if (isModEnabled('expensereport')) {
	$langs->load("trips");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'expensereport', 'test' => isModEnabled('expensereport'), 'label' => $langs->trans("ExpenseReports"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ExpenseReports")));
}
if (isModEnabled('holiday')) {
	$langs->load("holiday");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'holiday', 'test' => isModEnabled('holiday'), 'label' => $langs->trans("Holidays"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Holidays")));
}
if (isModEnabled("bank")) {
	$langs->load("banks");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'banque', 'test' => isModEnabled('bank'), 'label' => $langs->trans("BankAccount"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("BankAccount")));
}
if (isModEnabled('mrp')) {
	$langs->load("mrp");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'mrp-mo', 'test' => isModEnabled('mrp'), 'label' => $langs->trans("MOs"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ManufacturingOrders")));
}
if (isModEnabled('recruitment')) {
	$langs->load("recruitment");
	$rowspan++;
	$sectionauto[] = array('level' => 1, 'module' => 'recruitment-recruitmentcandidature', 'test' => isModEnabled('recruitment'), 'label' => $langs->trans("Candidatures"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("JobApplications")));
}


//***********************
// List
//***********************
print load_fiche_titre($langs->trans("ECMArea").' - '.$langs->trans("Search"));

print $langs->trans("FeatureNotYetAvailable").'.<br><br>';

// Tool bar
$head = ecm_prepare_head_fm($ecmdir);
//print dol_get_fiche_head($head, 'search_form', '', 1);


print '<table class="border centpercent"><tr><td width="40%" valign="top">';

// Left area


//print load_fiche_titre($langs->trans("ECMSectionsManual"));

print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="nobordernopadding" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="2">'.$langs->trans("ECMSearchByKeywords").'</td></tr>';
print '<tr class="impair"><td>'.$langs->trans("Ref").':</td><td class="right"><input type="text" name="search_ref" class="flat" size="10"></td></tr>';
print '<tr class="impair"><td>'.$langs->trans("Title").':</td><td class="right"><input type="text" name="search_title" class="flat" size="10"></td></tr>';
print '<tr class="impair"><td>'.$langs->trans("Keyword").':</td><td class="right"><input type="text" name="search_keyword" class="flat" size="10"></td></tr>';
print '<tr class="impair"><td colspan="2" class="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "</table></form>";
//print $langs->trans("ECMSectionManualDesc");

//print load_fiche_titre($langs->trans("ECMSectionAuto"));

print '<form method="post" action="'.DOL_URL_ROOT.'/ecm/search.php">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<table class="nobordernopadding" width="100%">';
print "<tr class=\"liste_titre\">";
print '<td colspan="4">'.$langs->trans("ECMSearchByEntity").'</td></tr>';

$buthtml = '<td rowspan="'.$rowspan.'"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td>';
$butshown = 0;
foreach ($sectionauto as $sectioncur) {
	if (!$sectioncur['test']) {
		continue;
	}
	print '<tr class="impair">';
	print "<td>".$sectioncur['label'].':</td>';
	print '<td';
	print ' class="right"';
	print '>';
	print '<input type="text" name="search_'.$sectioncur['module'].'" class="flat" size="14">';
	print '</td>';
	print '</tr>';
	$butshown++;
}

print '<tr '.$bc[false].'><td colspan="4" class="center"><input type="submit" class="button" value="'.$langs->trans("Search").'"></td></tr>';
print "</table></form>";
//print $langs->trans("ECMSectionAutoDesc");



print '</td><td class="tdtop">';

// Right area
$relativepath = $ecmdir->getRelativePath();
$upload_dir = $conf->ecm->dir_output.'/'.$relativepath;
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);

$formfile = new FormFile($db);
$param = '&section='.urlencode($section);
$textifempty = ($section ? $langs->trans("NoFileFound") : $langs->trans("ECMSelectASection"));
$formfile->list_of_documents($filearray, null, 'ecm', $param, 1, $relativepath, $user->hasRight('ecm', 'upload'), 1, $textifempty);


print '</td></tr>';

print '</table>';


print '<br>';

// End of page
llxFooter();
$db->close();
