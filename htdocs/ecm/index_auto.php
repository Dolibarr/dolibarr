<?php
/* Copyright (C) 2008-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2016      Alexandre Spangaro   <aspangaro@open-dsi.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/ecm/index_auto.php
 *	\ingroup    ecm
 *	\brief      Main page for ECM section area
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ecm.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/treeview.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';

// Load translation files required by the page
$langs->loadLangs(array("ecm", "companies", "other", "users", "orders", "propal", "bills", "contracts"));

// Get parameters
$socid = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$section = GETPOSTINT('section') ? GETPOSTINT('section') : GETPOSTINT('section_id');
$module = GETPOST('module', 'alpha');
if (!$section) {
	$section = 0;
}
$section_dir = GETPOST('section_dir', 'alpha');

$search_doc_ref = GETPOST('search_doc_ref', 'alpha');

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
	$sortfield = "fullname";
}
if ($module == 'invoice_supplier' && $sortfield == "fullname") {
	$sortfield = "level1name";
}

$ecmdir = new EcmDirectory($db);
if ($section) {
	$result = $ecmdir->fetch($section);
	if (!($result > 0)) {
		dol_print_error($db, $ecmdir->error);
		exit;
	}
}

$form = new Form($db);
$ecmdirstatic = new EcmDirectory($db);
$userstatic = new User($db);

$error = 0;

// Security check
if ($user->socid) {
	$socid = $user->socid;
}

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('ecmautocard', 'globalcard'));

$result = restrictedArea($user, 'ecm', 0);


/*
 *	Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_doc_ref = '';
}

// Add directory
if ($action == 'add' && $user->hasRight('ecm', 'setup')) {
	$ecmdir->ref                = 'NOTUSEDYET';
	$ecmdir->label              = GETPOST("label");
	$ecmdir->description        = GETPOST("desc");

	$id = $ecmdir->create($user);
	if ($id > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		setEventMessages('Error '.$langs->trans($ecmdir->error), null, 'errors');
		$action = "create";
	}

	clearstatcache();
}

// Remove file
if ($action == 'confirm_deletefile' && $user->hasRight('ecm', 'upload')) {
	if (GETPOST('confirm') == 'yes') {
		$langs->load("other");
		if ($section) {
			$result = $ecmdir->fetch($section);
			if (!($result > 0)) {
				dol_print_error($db, $ecmdir->error);
				exit;
			}
			$relativepath = $ecmdir->getRelativePath();
		} else {
			$relativepath = '';
		}
		$upload_dir = $conf->ecm->dir_output.($relativepath ? '/'.$relativepath : '');
		$file = $upload_dir."/".GETPOST('urlfile');

		$ret = dol_delete_file($file);
		if ($ret) {
			setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
		}

		$result = $ecmdir->changeNbOfFiles('-');

		clearstatcache();
	}
	$action = 'file_manager';
}

// Remove directory
if ($action == 'confirm_deletesection' && GETPOST('confirm') == 'yes' && $user->hasRight('ecm', 'setup')) {
	$result = $ecmdir->delete($user);
	setEventMessages($langs->trans("ECMSectionWasRemoved", $ecmdir->label), null, 'mesgs');

	clearstatcache();
}

// Refresh directory view
// This refresh list of dirs, not list of files (for performance reason). List of files is refresh only if dir was not synchronized.
// To refresh content of dir with cache, just open the dir in edit mode.
if ($action == 'refreshmanual' && $user->hasRight('ecm', 'read')) {
	$ecmdirtmp = new EcmDirectory($db);

	// This part of code is same than into file ecm/ajax/ecmdatabase.php TODO Remove duplicate
	clearstatcache();

	$diroutputslash = str_replace('\\', '/', $conf->ecm->dir_output);
	$diroutputslash .= '/';

	// Scan directory tree on disk
	$disktree = dol_dir_list($conf->ecm->dir_output, 'directories', 1, '', '^temp$', '', 0, 0);

	// Scan directory tree in database
	$sqltree = $ecmdirstatic->get_full_arbo(0);

	$adirwascreated = 0;

	// Now we compare both trees to complete missing trees into database
	//var_dump($disktree);
	//var_dump($sqltree);
	foreach ($disktree as $dirdesc) {    // Loop on tree onto disk
		$dirisindatabase = 0;
		foreach ($sqltree as $dirsqldesc) {
			if ($conf->ecm->dir_output.'/'.$dirsqldesc['fullrelativename'] == $dirdesc['fullname']) {
				$dirisindatabase = 1;
				break;
			}
		}

		if (!$dirisindatabase) {
			$txt = "Directory found on disk ".$dirdesc['fullname'].", not found into database so we add it";
			dol_syslog($txt);
			//print $txt."<br>\n";

			// We must first find the fk_parent of directory to create $dirdesc['fullname']
			$fk_parent = -1;
			$relativepathmissing = str_replace($diroutputslash, '', $dirdesc['fullname']);
			$relativepathtosearchparent = $relativepathmissing;
			//dol_syslog("Try to find parent id for directory ".$relativepathtosearchparent);
			if (preg_match('/\//', $relativepathtosearchparent)) {
				//while (preg_match('/\//',$relativepathtosearchparent))
				$relativepathtosearchparent = preg_replace('/\/[^\/]*$/', '', $relativepathtosearchparent);
				$txt = "Is relative parent path ".$relativepathtosearchparent." for ".$relativepathmissing." found in sql tree ?";
				dol_syslog($txt);
				//print $txt." -> ";
				$parentdirisindatabase = 0;
				foreach ($sqltree as $dirsqldesc) {
					if ($dirsqldesc['fullrelativename'] == $relativepathtosearchparent) {
						$parentdirisindatabase = $dirsqldesc['id'];
						break;
					}
				}
				if ($parentdirisindatabase > 0) {
					dol_syslog("Yes with id ".$parentdirisindatabase);
					//print "Yes with id ".$parentdirisindatabase."<br>\n";
					$fk_parent = $parentdirisindatabase;
					//break;  // We found parent, we can stop the while loop
				} else {
					dol_syslog("No");
					//print "No<br>\n";
				}
			} else {
				dol_syslog("Parent is root");
				$fk_parent = 0; // Parent is root
			}

			if ($fk_parent >= 0) {
				$ecmdirtmp->ref                = 'NOTUSEDYET';
				$ecmdirtmp->label              = dol_basename($dirdesc['fullname']);
				$ecmdirtmp->description        = '';
				$ecmdirtmp->fk_parent          = $fk_parent;

				$txt = "We create directory ".$ecmdirtmp->label." with parent ".$fk_parent;
				dol_syslog($txt);
				//print $ecmdirtmp->cachenbofdoc."<br>\n";exit;
				$id = $ecmdirtmp->create($user);
				if ($id > 0) {
					$newdirsql = array('id' => $id,
									 'id_mere' => $ecmdirtmp->fk_parent,
									 'label' => $ecmdirtmp->label,
									 'description' => $ecmdirtmp->description,
									 'fullrelativename' => $relativepathmissing);
					$sqltree[] = $newdirsql; // We complete fulltree for following loops
					//var_dump($sqltree);
					$adirwascreated = 1;
				} else {
					dol_syslog("Failed to create directory ".$ecmdirtmp->label, LOG_ERR);
				}
			} else {
				$txt = "Parent of ".$dirdesc['fullname']." not found";
				dol_syslog($txt);
				//print $txt."<br>\n";
			}
		}
	}

	// Loop now on each sql tree to check if dir exists
	foreach ($sqltree as $dirdesc) {    // Loop on each sqltree to check dir is on disk
		$dirtotest = $conf->ecm->dir_output.'/'.$dirdesc['fullrelativename'];
		if (!dol_is_dir($dirtotest)) {
			$ecmdirtmp->id = $dirdesc['id'];
			$ecmdirtmp->delete($user, 'databaseonly');
			//exit;
		}
	}

	$sql = "UPDATE ".MAIN_DB_PREFIX."ecm_directories set cachenbofdoc = -1 WHERE cachenbofdoc < 0"; // If pb into cache counting, we set to value -1 = "unknown"
	dol_syslog("sql = ".$sql);
	$db->query($sql);

	// If a directory was added, the fulltree array is not correctly completed and sorted, so we clean
	// it to be sure that fulltree array is not used without reloading it.
	if ($adirwascreated) {
		$sqltree = null;
	}
}



/*
 *	View
 */

// Define height of file area (depends on $_SESSION["dol_screenheight"])
//print $_SESSION["dol_screenheight"];
$maxheightwin = (isset($_SESSION["dol_screenheight"]) && $_SESSION["dol_screenheight"] > 466) ? ($_SESSION["dol_screenheight"] - 136) : 660; // Also into index.php file

$moreheadcss = '';
$moreheadjs = '';

//$morejs=array();
$morejs = array('includes/jquery/plugins/blockUI/jquery.blockUI.js', 'core/js/blockUI.js'); // Used by ecm/tpl/enabledfiletreeajax.tpl.pgp
if (!getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
	$morejs[] = "includes/jquery/plugins/jqueryFileTree/jqueryFileTree.js";
}

$moreheadjs .= '<script type="text/javascript">'."\n";
$moreheadjs .= 'var indicatorBlockUI = \''.DOL_URL_ROOT."/theme/".$conf->theme."/img/working.gif".'\';'."\n";
$moreheadjs .= '</script>'."\n";

llxHeader($moreheadcss.$moreheadjs, $langs->trans("ECMArea"), '', '', 0, 0, $morejs, '', '', 'mod-ecm page-index_auto');


// Add sections to manage
$rowspan = 0;
$sectionauto = array();
if (!getDolGlobalString('ECM_AUTO_TREE_HIDEN')) {
	if (isModEnabled("product") || isModEnabled("service")) {
		$langs->load("products");
		$rowspan++;
		$sectionauto[] = array('position' => 10, 'level' => 1, 'module' => 'product', 'test' => (isModEnabled("product") || isModEnabled("service")), 'label' => $langs->trans("ProductsAndServices"), 'desc' => $langs->trans("ECMDocsByProducts"));
	}
	if (isModEnabled("societe")) {
		$rowspan++;
		$sectionauto[] = array('position' => 20, 'level' => 1, 'module' => 'company', 'test' => isModEnabled('societe'), 'label' => $langs->trans("ThirdParties"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ThirdParties")));
	}
	if (isModEnabled("propal")) {
		$rowspan++;
		$sectionauto[] = array('position' => 30, 'level' => 1, 'module' => 'propal', 'test' => isModEnabled('propal'), 'label' => $langs->trans("Proposals"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Proposals")));
	}
	if (isModEnabled('contract')) {
		$rowspan++;
		$sectionauto[] = array('position' => 40, 'level' => 1, 'module' => 'contract', 'test' => isModEnabled('contract'), 'label' => $langs->trans("Contracts"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Contracts")));
	}
	if (isModEnabled('order')) {
		$rowspan++;
		$sectionauto[] = array('position' => 50, 'level' => 1, 'module' => 'order', 'test' => isModEnabled('order'), 'label' => $langs->trans("CustomersOrders"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Orders")));
	}
	if (isModEnabled('invoice')) {
		$rowspan++;
		$sectionauto[] = array('position' => 60, 'level' => 1, 'module' => 'invoice', 'test' => isModEnabled('invoice'), 'label' => $langs->trans("CustomersInvoices"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Invoices")));
	}
	if (isModEnabled('supplier_proposal')) {
		$langs->load("supplier_proposal");
		$rowspan++;
		$sectionauto[] = array('position' => 70, 'level' => 1, 'module' => 'supplier_proposal', 'test' => isModEnabled('supplier_proposal'), 'label' => $langs->trans("SupplierProposals"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SupplierProposals")));
	}
	if (isModEnabled("supplier_order")) {
		$rowspan++;
		$sectionauto[] = array('position' => 80, 'level' => 1, 'module' => 'order_supplier', 'test' => isModEnabled("supplier_order"), 'label' => $langs->trans("SuppliersOrders"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("PurchaseOrders")));
	}
	if (isModEnabled("supplier_invoice")) {
		$rowspan++;
		$sectionauto[] = array('position' => 90, 'level' => 1, 'module' => 'invoice_supplier', 'test' => isModEnabled("supplier_invoice"), 'label' => $langs->trans("SuppliersInvoices"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SupplierInvoices")));
	}
	if (isModEnabled('tax')) {
		$langs->load("compta");
		$rowspan++;
		$sectionauto[] = array('position' => 100, 'level' => 1, 'module' => 'tax', 'test' => isModEnabled('tax'), 'label' => $langs->trans("SocialContributions"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("SocialContributions")));
		$rowspan++;
		$sectionauto[] = array('position' => 110, 'level' => 1, 'module' => 'tax-vat', 'test' => isModEnabled('tax'), 'label' => $langs->trans("VAT"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("VAT")));
	}
	if (isModEnabled('salaries')) {
		$langs->load("compta");
		$rowspan++;
		$sectionauto[] = array('position' => 120, 'level' => 1, 'module' => 'salaries', 'test' => isModEnabled('salaries'), 'label' => $langs->trans("Salaries"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Salaries")));
	}
	if (isModEnabled('project')) {
		$rowspan++;
		$sectionauto[] = array('position' => 130, 'level' => 1, 'module' => 'project', 'test' => isModEnabled('project'), 'label' => $langs->trans("Projects"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Projects")));
		$rowspan++;
		$sectionauto[] = array('position' => 140, 'level' => 1, 'module' => 'project_task', 'test' => isModEnabled('project'), 'label' => $langs->trans("Tasks"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Tasks")));
	}
	if (isModEnabled('intervention')) {
		$langs->load("interventions");
		$rowspan++;
		$sectionauto[] = array('position' => 150, 'level' => 1, 'module' => 'fichinter', 'test' => isModEnabled('intervention'), 'label' => $langs->trans("Interventions"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Interventions")));
	}
	if (isModEnabled('expensereport')) {
		$langs->load("trips");
		$rowspan++;
		$sectionauto[] = array('position' => 160, 'level' => 1, 'module' => 'expensereport', 'test' => isModEnabled('expensereport'), 'label' => $langs->trans("ExpenseReports"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ExpenseReports")));
	}
	if (isModEnabled('holiday')) {
		$langs->load("holiday");
		$rowspan++;
		$sectionauto[] = array('position' => 170, 'level' => 1, 'module' => 'holiday', 'test' => isModEnabled('holiday'), 'label' => $langs->trans("Holidays"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Holidays")));
	}
	if (isModEnabled("bank")) {
		$langs->load("banks");
		$rowspan++;
		$sectionauto[] = array('position' => 180, 'level' => 1, 'module' => 'banque', 'test' => isModEnabled('bank'), 'label' => $langs->trans("BankAccount"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("BankAccount")));
		$rowspan++;
		$sectionauto[] = array('position' => 190, 'level' => 1, 'module' => 'chequereceipt', 'test' => isModEnabled('bank'), 'label' => $langs->trans("CheckReceipt"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("CheckReceipt")));
	}
	if (isModEnabled('mrp')) {
		$langs->load("mrp");
		$rowspan++;
		$sectionauto[] = array('position' => 200, 'level' => 1, 'module' => 'mrp-mo', 'test' => isModEnabled('mrp'), 'label' => $langs->trans("MOs"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("ManufacturingOrders")));
	}
	if (isModEnabled('recruitment')) {
		$langs->load("recruitment");
		$rowspan++;
		$sectionauto[] = array('position' => 210, 'level' => 1, 'module' => 'recruitment-recruitmentcandidature', 'test' => isModEnabled('recruitment'), 'label' => $langs->trans("Candidatures"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("JobApplications")));
	}
	$rowspan++;
	$sectionauto[] = array('position' => 220, 'level' => 1, 'module' => 'user', 'test' => 1, 'label' => $langs->trans("Users"), 'desc' => $langs->trans("ECMDocsBy", $langs->transnoentitiesnoconv("Users")));

	$parameters = array();
	$reshook = $hookmanager->executeHooks('addSectionECMAuto', $parameters);
	if ($reshook > 0 && is_array($hookmanager->resArray) && count($hookmanager->resArray) > 0) {
		$sectionauto[] = $hookmanager->resArray;
		$rowspan += count($hookmanager->resArray);
	}
}

$head = ecm_prepare_dasboard_head(null);
print dol_get_fiche_head($head, 'index_auto', '', -1, '');



// Confirm remove file (for non javascript users)
if ($action == 'deletefile' && empty($conf->use_javascript_ajax)) {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section.'&urlfile='.urlencode(GETPOST("urlfile")), $langs->trans('DeleteFile'), $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile', '', '', 1);
}

// Start container of all panels
?>
<!-- Begin div id="containerlayout" -->
<div id="containerlayout">
<div id="ecm-layout-north" class="toolbar largebutton">
<?php

// Start top panel, toolbar
print '<div class="inline-block toolbarbutton centpercent">';

// Toolbar
$url = ((!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_ECM_DISABLE_JS')) ? '#' : ($_SERVER["PHP_SELF"].'?action=refreshmanual'.($module ? '&amp;module='.$module : '').($section ? '&amp;section='.$section : '')));
print '<a href="'.$url.'" class="inline-block valignmiddle toolbarbutton paddingtop" title="'.dol_escape_htmltag($langs->trans('Refresh')).'">';
print img_picto('', 'refresh', 'id="refreshbutton"', 0, 0, 0, '', 'size15x marginrightonly');
print '</a>';

print '</div>';
// End top panel, toolbar

?>
</div>
<div id="ecm-layout-west" class="inline-block">
<?php
// Start left area


// Confirmation de la suppression d'une ligne categorie
if ($action == 'delete_section') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?section='.$section, $langs->trans('DeleteSection'), $langs->trans('ConfirmDeleteSection', $ecmdir->label), 'confirm_deletesection', '', '', 1);
}
// End confirm


if (empty($action) || $action == 'file_manager' || preg_match('/refresh/i', $action) || $action == 'deletefile') {
	print '<table class="liste centpercent">'."\n";

	print '<!-- Title for auto directories -->'."\n";
	print '<tr class="liste_titre">'."\n";
	print '<th class="liste_titre" align="left" colspan="6">';
	print '&nbsp;'.$langs->trans("ECMSections");
	print '</th></tr>';

	$showonrightsize = '';
	// Auto section
	if (count($sectionauto)) {
		$htmltooltip = $langs->trans("ECMAreaDesc2");
		$htmltooltip .= '<br>'.$langs->trans("ECMAreaDesc2b");

		$sectionauto = dol_sort_array($sectionauto, 'label', 'ASC', 1, 0);

		print '<tr>';
		print '<td colspan="6">';
		print '<div id="filetreeauto" class="ecmfiletree"><ul class="ecmjqft">';

		$nbofentries = 0;
		$oldvallevel = 0;
		foreach ($sectionauto as $key => $val) {
			if (empty($val['test'])) {
				continue; // If condition to show the ECM auto directory is ok
			}

			print '<li class="directory collapsed">';
			print '<a class="fmdirlia jqft ecmjqft" href="'.$_SERVER["PHP_SELF"].'?module='.urlencode($val['module']).'">';
			print $val['label'];
			print '</a>';

			print '<div class="ecmjqft">';
			// Info
			$htmltooltip = '<b>'.$langs->trans("ECMSection").'</b>: '.$val['label'].'<br>';
			$htmltooltip .= '<b>'.$langs->trans("Type").'</b>: '.$langs->trans("ECMSectionAuto").'<br>';
			$htmltooltip .= '<b>'.$langs->trans("ECMCreationUser").'</b>: '.$langs->trans("ECMTypeAuto").'<br>';
			$htmltooltip .= '<b>'.$langs->trans("Description").'</b>: '.$val['desc'];
			print $form->textwithpicto('', $htmltooltip, 1, 'info');
			print '</div>';

			print '</li>';

			$nbofentries++;
		}

		print '</ul></div></td></tr>';
	}

	print "</table>";
}


// End left panel
?>
</div>
<div id="ecm-layout-center" class="inline-block">
<div class="pane-in ecm-in-layout-center">
<div id="ecmfileview" class="ecmfileview">
<?php
// Start right panel

$mode = 'noajax';
$url = DOL_URL_ROOT.'/ecm/index_auto.php';
include_once DOL_DOCUMENT_ROOT.'/core/ajax/ajaxdirpreview.php';


// End right panel
?>
</div>
</div>

</div>
</div> <!-- End div id="containerlayout" -->
<?php
// End of page

if (!empty($conf->use_javascript_ajax) && !getDolGlobalString('MAIN_ECM_DISABLE_JS')) {
	include DOL_DOCUMENT_ROOT.'/ecm/tpl/enablefiletreeajax.tpl.php';
}


print dol_get_fiche_end();

llxFooter();

$db->close();
