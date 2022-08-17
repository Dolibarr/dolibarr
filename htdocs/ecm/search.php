<?php
/* Copyright (C) 2008-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009 Regis Houssin        <regis.houssin@inodbox.com>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

// Load translation files required by the page
$langs->loadLangs(array("ecm", "companies", "other", "users", "orders", "propal", "bills", "contracts"));

// Security check
if ($user->socid) {
	$socid = $user->socid;
}
$result = restrictedArea($user, 'ecm', '');

// Load permissions
$user->getrights('ecm');

// Get parameters
$socid = GETPOST('socid', 'int');
$action = GETPOST('action', 'aZ09');
$section = GETPOST('section');
if (!$section) {
	$section = 0;
}

$module  = GETPOST('module', 'alpha');
$website = GETPOST('website', 'alpha');
$pageid  = GETPOST('pageid', 'int');
if (empty($module)) {
	$module = 'ecm';
}

$upload_dir = $conf->ecm->dir_output.'/'.$section;

$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
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

$permtoread = $user->rights->ecm->read;

if (!$permtoread) {
	accessforbidden();
}


/*
 * Actions
 */

// None



/*
 * View
 */

llxHeader();

$form = new Form($db);
$ecmdirstatic = new EcmDirectory($db);
$userstatic = new User($db);


// Ajout rubriques automatiques
$rowspan = 0;
$sectionauto = array();
if (!empty($conf->product->enabled) || !empty($conf->service->enabled)) {
	$langs->load("products"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'product', 'test'=>(!empty($conf->product->enabled) || !empty($conf->service->enabled)), 'label'=>$langs->trans("ProductsAndServices"), 'desc'=>$langs->trans("ECMDocsByProducts"));
}
if (!empty($conf->societe->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'company', 'test'=>$conf->societe->enabled, 'label'=>$langs->trans("ThirdParties"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ThirdParties")));
}
if (!empty($conf->propal->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'propal', 'test'=>$conf->propal->enabled, 'label'=>$langs->trans("Proposals"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Proposals")));
}
if (!empty($conf->contrat->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'contract', 'test'=>$conf->contrat->enabled, 'label'=>$langs->trans("Contracts"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Contracts")));
}
if (!empty($conf->commande->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'order', 'test'=>$conf->commande->enabled, 'label'=>$langs->trans("CustomersOrders"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Orders")));
}
if (isModEnabled('facture')) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'invoice', 'test'=>$conf->facture->enabled, 'label'=>$langs->trans("CustomersInvoices"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Invoices")));
}
if (!empty($conf->supplier_proposal->enabled)) {
	$langs->load("supplier_proposal"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'supplier_proposal', 'test'=>$conf->supplier_proposal->enabled, 'label'=>$langs->trans("SupplierProposals"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SupplierProposals")));
}
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'order_supplier', 'test'=>((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_order->enabled)), 'label'=>$langs->trans("SuppliersOrders"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("PurchaseOrders")));
}
if ((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_invoice->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'invoice_supplier', 'test'=>((!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD)) || !empty($conf->supplier_invoice->enabled)), 'label'=>$langs->trans("SuppliersInvoices"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SupplierInvoices")));
}
if (!empty($conf->tax->enabled)) {
	$langs->load("compta"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'tax', 'test'=>$conf->tax->enabled, 'label'=>$langs->trans("SocialContributions"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SocialContributions")));
}
if (!empty($conf->project->enabled)) {
	$rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'project', 'test'=>$conf->project->enabled, 'label'=>$langs->trans("Projects"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Projects")));
}
if (!empty($conf->ficheinter->enabled)) {
	$langs->load("interventions"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'fichinter', 'test'=>$conf->ficheinter->enabled, 'label'=>$langs->trans("Interventions"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Interventions")));
}
if (!empty($conf->expensereport->enabled)) {
	$langs->load("trips"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'expensereport', 'test'=>$conf->expensereport->enabled, 'label'=>$langs->trans("ExpenseReports"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ExpenseReports")));
}
if (!empty($conf->holiday->enabled)) {
	$langs->load("holiday"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'holiday', 'test'=>$conf->holiday->enabled, 'label'=>$langs->trans("Holidays"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Holidays")));
}
if (!empty($conf->banque->enabled)) {
	$langs->load("banks"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'banque', 'test'=>$conf->banque->enabled, 'label'=>$langs->trans("BankAccount"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("BankAccount")));
}
if (!empty($conf->mrp->enabled)) {
	$langs->load("mrp"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'mrp-mo', 'test'=>$conf->mrp->enabled, 'label'=>$langs->trans("MOs"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ManufacturingOrders")));
}
if (!empty($conf->recruitment->enabled)) {
	$langs->load("recruitment"); $rowspan++; $sectionauto[] = array('level'=>1, 'module'=>'recruitment-recruitmentcandidature', 'test'=>$conf->recruitment->enabled, 'label'=>$langs->trans("Candidatures"), 'desc'=>$langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("JobApplications")));
}


//***********************
// List
//***********************
print load_fiche_titre($langs->trans("ECMArea").' - '.$langs->trans("Search"));

//print $langs->trans("ECMAreaDesc")."<br>";
//print $langs->trans("ECMAreaDesc2")."<br>";
//print "<br>\n";
print $langs->trans("FeatureNotYetAvailable").'.<br><br>';

// Tool bar
$head = ecm_prepare_head_fm($ecmdir, $module, $section);
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
$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', $sortfield, (strtolower($sortorder) == 'desc' ?SORT_DESC:SORT_ASC), 1);

$formfile = new FormFile($db);
$param = '&section='.urlencode($section);
$textifempty = ($section ? $langs->trans("NoFileFound") : $langs->trans("ECMSelectASection"));
$formfile->list_of_documents($filearray, '', 'ecm', $param, 1, $relativepath, $user->rights->ecm->upload, 1, $textifempty);


print '</td></tr>';

print '</table>';


print '<br>';

// End of page
llxFooter();
$db->close();
