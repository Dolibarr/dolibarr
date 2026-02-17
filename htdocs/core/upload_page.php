<?php
/* Copyright (C) 2005-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 *
 * This file is a modified version of datepicker.php from phpBSM to fix some
 * bugs, to add new features and to dramatically increase speed.
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
 *       \file       htdocs/core/upload_page.php
 *       \brief      Page to show a generic upload file feature
 */

require_once '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';


if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->loadLangs(array("main", "other"));

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');

// users/temp/import
$upload_dir = $conf->user->dir_temp.'/import';
dol_mkdir($upload_dir);

$file = GETPOST('file');

$originalfilename = $file;
$uid = $thiid = $pid = $erid = $salid = 0;
$reg = array();
if (preg_match('/-uid([\d+])/', $file, $reg)) {
	$uid = $reg[1];
	$originalfilename = preg_replace('/-uid\d+/', '', $originalfilename);
}
if (preg_match('/-thiid([\d+])/', $file, $reg)) {
	$thiid = $reg[1];
	$originalfilename = preg_replace('/-thiid\d+/', '', $originalfilename);
}
if (preg_match('/-pid([\d+])/', $file, $reg)) {
	$pid = $reg[1];
	$originalfilename = preg_replace('/-pid\d+/', '', $originalfilename);
}
if (preg_match('/-erid([\d+])/', $file, $reg)) {
	$erid = $reg[1];
	$originalfilename = preg_replace('/-erid\d+/', '', $originalfilename);
}
if (preg_match('/-salid([\d+])/', $file, $reg)) {
	$salid = $reg[1];
	$originalfilename = preg_replace('/-salid\d+/', '', $originalfilename);
}
$originalfilename = preg_replace('/^upload_page-[a-z_]+-/', '', $originalfilename);

$error = 0;

$ai = new Ai($db);


/*
 * Actions
 */

if (getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE') && !is_numeric(getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE'))) {
	$urlforuploadpage = getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE');

	header("Location: ".$urlforuploadpage);
	exit(1);
}

if ($action == 'uploadfile') {	// Test on permission not required here. Done later
	if (!$modulepart) {			// Should not happen
		print 'Error, modulepart param is empty';
		exit(1);
	}

	// $modulepart can be 'invoice_supplier', ...
	$arrayobject = getElementProperties($modulepart);

	$module = $arrayobject['module'];
	$element = $arrayobject['element'];
	$dir_output = $arrayobject['dir_output'];
	$dir_temp = $arrayobject['dir_temp'];

	$permlevel1 = 'read';
	$permlevel2 = '';
	$fileprefix = 'unknown';
	if (in_array($modulepart, array('fournisseur', 'invoice_supplier'))) {
		$permlevel1 = 'facture';
		$permlevel2 = 'read';
		$fileprefix = 'upload_page-'.$modulepart.'-uid'.$user->id.'-thiid'.(GETPOSTINT('socid') > 0 ? GETPOSTINT('socid') : 0).'-pid'.(GETPOSTINT('search_prodid') > 0 ? GETPOSTINT('search_prodid') : 0);
	} elseif ($modulepart == 'expensereport') {
		$fileprefix = 'upload_page-'.$modulepart.'-uid'.$user->id.'-erid'.(GETPOSTINT('userexpensereportid') > 0 ? GETPOSTINT('userexpensereportid') : 0).'-pid'.(GETPOSTINT('search_prodid') > 0 ? GETPOSTINT('search_prodid') : 0);
	} elseif ($modulepart == 'salaries') {
		$fileprefix = 'upload_page-'.$modulepart.'-uid'.$user->id.'-salid'.(GETPOSTINT('usersalaryid') > 0 ? GETPOSTINT('usersalaryid') : 0);
	}

	if ($permlevel2) {
		$permissiontoadd = $user->hasRight($module, $permlevel1, $permlevel2);	// Used by actions_linkedfiles
	} else {
		$permissiontoadd = $user->hasRight($module, $permlevel1);				// Used by actions_linkedfiles
	}
	$forceFullTextIndexation = '0';												// Used by actions_linkedfiles


	if (!empty($_FILES['userfile']['name'])) {
		$fullnewname = $fileprefix.'-'.$_FILES['userfile']['name'];
		$_FILES['userfile']['name'] = $fullnewname;

		// $dir_output = output dir of object
		// $dir_temp = temp dir of object
		// $upload_dir is "users/temp/import"
		include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';

		// TODO Add a js call of ajax service and show instead a message
		// @phpstan-ignore-next-line $error may have been modified by actions_linkedfiles.inc.php
		if (!$error) {
			header("Location: ".DOL_URL_ROOT.'/core/ajax/ajaxuploadpage.php?file='.urlencode($fullnewname));
			exit;
		}
	}
} else {
	// Delete the temporary files that are used when uploading files
	dol_delete_file($upload_dir.'/upload_page-by'.$user->id.'-*');
}


/*
 * View
 */

$form = new Form($db);

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
/*
if (empty($dolibarr_nocache) && GETPOSTINT('cache')) {
	header('Cache-Control: max-age='.GETPOSTINT('cache').', public');
	// For a .php, we must set an Expires to avoid to have it forced to an expired value by the web server
	header('Expires: '.gmdate('D, d M Y H:i:s', dol_now('gmt') + GETPOSTINT('cache')).' GMT');
	// HTTP/1.0
	header('Pragma: token=public');
} else {
	// HTTP/1.0
	header('Cache-Control: no-cache');
}
*/

$title = $langs->trans("UploadFile");
$help_url = '';

$arrayofjs = array();
$arrayofcss = array();

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss, '', 'mod-upload page-card');

print load_fiche_titre('', '', '', 0, '', '', '<h2>'.img_picto('', 'upload').' '.$title.'</h2>');


// Instantiate hooks of thirdparty module
$hookmanager->initHooks(array('uploadform'));

// Define $uploadform
$uploadform = '';


$uploadform = '<div class="display-flex">';

// Form to upload a supplier invoice
if (isModEnabled('supplier_invoice')) {
	$langs->load("bills");
	$uploadform .= '
	<div id="supplierinvoice" class="flex-item flex-item-uploadfile">'.img_picto('', 'bill', 'class="fa-2x"').'<br>
	<div>'.$langs->trans("SupplierInvoice").'<br><br>';

	$uploadform .= img_picto('', 'company', 'class="pictofixedwidth"');
	$uploadform .= $form->select_company(GETPOSTINT('socid'), 'socid', '(statut:=:0)', $langs->transnoentitiesnoconv("Supplier"), 0, 0, array(), 0, 'maxwidth200 disableautoopen');

	$uploadform .= '<br>';

	$prodid = GETPOSTINT('prodid');
	$prodtext = $langs->trans("RefOrLabel");

	//$uploadform .= $form->select_produits_fournisseurs(0, $prodid, 'prodid', '', 0, 0, 1, 2, $prodtext, 0, array(), GETPOSTINT('socid'), '1', 0, 'maxwidth200 disableautoopen', 0, '', null, 1);
	$uploadform .= img_picto('', 'product', 'class="pictofixedwidth"');
	$uploadform .= $form->select_produits_fournisseurs(0, $prodid, 'prodid', '', '', array(), 1, 1, 'maxwidth200 disableautoopen', $prodtext, 1);

	$uploadform .= '<br>';

	$uploadform .= '<br>
	<small class="opacitymedium">'.$langs->trans("OrClickToSelectAFile").'...</small>
	</div>
	</div>';
}

// Form to upload an expense report
if (isModEnabled('expensereport')) {
	$langs->load("expensereport");
	$uploadform .= '
	<div id="userexpensereport" class="flex-item flex-item-uploadfile">'.img_picto('', 'expensereport', 'class="fa-2x"').'<br>
	<div>'.$langs->trans("ExpenseReport").'<br><br>';

	$uploadform .= img_picto('', 'user', 'class="pictofixedwidth"');
	//$uploadform .= '<span class="disableautoopen">';
	$uploadform .= $form->select_dolusers(GETPOSTINT('userexpensereportid') > 0 ? GETPOSTINT('userexpensereportid') : $user->id, 'userexpensereportid', $langs->transnoentitiesnoconv("User"), null, 0, 'hierarchyme', '', '', 0, 0, '', 0, '', 'maxwidth200 disableautoopen', 1);
	//$uploadform .= '</span>';

	$uploadform .= '<br>';

	$uploadform .= '<br>
	<small class="opacitymedium">'.$langs->trans("OrClickToSelectAFile").'...</small>
	</div>
	</div>';
}


// Form to upload a salary document
if (isModEnabled('salaries')) {
	$langs->load("salaries");
	$uploadform .= '
	<div id="userpayroll" class="flex-item flex-item-uploadfile">'.img_picto('', 'salary', 'class="fa-2x"').'<br>
	<div>'.$langs->trans("UserPaySlip").'<br><br>';


	$uploadform .= img_picto('', 'user', 'class="pictofixedwidth"');
	//$uploadform .= '<span class="disableautoopen">';
	$uploadform .= $form->select_dolusers(GETPOSTINT('usersalaryid') > 0 ? GETPOSTINT('usersalaryid') : $user->id, 'usersalaryid', $langs->transnoentitiesnoconv("Employee"), null, 0, 'hierarchyme', '', '', 0, 0, '', 0, '', 'maxwidth200 disableautoopen', 1);
	//$uploadform .= '</span>';

	$uploadform .= '<br>';

	$uploadform .= '<br>
	<small class="opacitymedium">'.$langs->trans("OrClickToSelectAFile").'...</small>
	</div>
	</div>';
}



$uploadform .= '</div>';


// Execute hook printSearchForm
$parameters = array('uploadform' => $uploadform);
$reshook = $hookmanager->executeHooks('printUploadForm', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
	$uploadform .= $hookmanager->resPrint;
} else {
	$uploadform = $hookmanager->resPrint;
}

$uploadform .= '<br>';


if ($action == 'uploadfile') {
	print $langs->trans("ImportInProcess", $originalfilename).'<br>';
	print '<br>';

	print $langs->trans("AIProcessingPleaseWait", $ai->getApiService()).'...';
	print '<br>';

	print '<div class="progress" title="80%">
	    <div class="progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
	</div>';


	print '</form>';
	print "\n<!-- End Form -->\n";
} else {
	// Show all forms
	print "\n";
	print "<!-- Begin UploadForm -->\n";
	print '<form id="uploadform" enctype="multipart/form-data" method="POST" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="uploadfile">';
	print '<input type="hidden" name="sendit" value="1">';
	print '<input type="hidden" name="modulepart" id="modulepart" value="">';
	print '<input type="hidden" name="overwritefile" value="1">';

	print '<div class="center"><div class="center" style="padding: 10px;">';
	print '<style>.menu_titre { padding-top: 7px; }</style>';
	print '<div id="blockupload" class="center">'."\n";
	//print '<input name="filenamePDF" id="filenamePDF" type="hideobject">';
	print $uploadform;


	$accept = '.pdf,image/*';
	$disablemulti = 1;
	$perm = 1;
	$capture = 1;

	$maxfilesizearray = getMaxFileSizeArray();
	$max = $maxfilesizearray['max'];
	$maxmin = $maxfilesizearray['maxmin'];
	$maxphptoshow = $maxfilesizearray['maxphptoshow'];
	$maxphptoshowparam = $maxfilesizearray['maxphptoshowparam'];
	$out = '';
	if ($maxmin > 0) {
		$out .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
	}
	$out .= '<input class="hideobject" type="file" id="fileInput" value=""';
	// @phpstan-ignore-next-line
	$out .= ((getDolGlobalString('MAIN_DISABLE_MULTIPLE_FILEUPLOAD') || $disablemulti) ? ' name="userfile"' : ' name="userfile[]" multiple');
	// @phpstan-ignore-next-line
	$out .= (!getDolGlobalString('MAIN_UPLOAD_DOC') || empty($perm) ? ' disabled' : '');
	// @phpstan-ignore-next-line
	$out .= (!empty($accept) ? ' accept="'.$accept.'"' : ' accept=""');
	// @phpstan-ignore-next-line
	$out .= (!empty($capture) ? ' capture="capture"' : '');
	$out .= '>';

	print $out;


	print "<script>
	$(document).ready(function() {
		jQuery('#supplierinvoice:not(.disableautoopen)').on('click', function(event) {
			console.log('Click on link supplierinvoice to open input file');
			console.log(event);
			if (!event.target.closest('.disableautoopen')) {
				$('#modulepart').val('invoice_supplier');
				$('#fileInput').click();
			}
		});

		jQuery('#userexpensereport:not(.disableautoopen)').on('click', function(event) {
			console.log('Click on link userexpensereport to open input file');
			console.log(event);
			if (!event.target.closest('.disableautoopen')) {
				$('#modulepart').val('expensereport');
				$('#fileInput').click();
			}
		});

		jQuery('#userpayroll:not(.disableautoopen)').on('click', function(event) {
			console.log('Click on link userpayroll to open input file');
			console.log(event);
			if (!event.target.closest('.disableautoopen')) {
				$('#modulepart').val('salaries');
				$('#fileInput').click();
			}
		});

		jQuery('#fileInput').on('change', function(event) {
			console.log(event);
			console.log('A file was selected, we submit the form');
			$('#uploadform').submit();
		});
	});
	</script>";

	print '</div>'."\n";
	print '</div></div>';

	print '</form>';
	print "\n<!-- End UploadForm -->\n";
}


// End of page
llxFooter();
$db->close();
