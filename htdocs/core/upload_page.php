<?php
/* Copyright (C) 2005-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024-2025  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
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
 * @var Societe $mysoc
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/ai.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';

$langs->loadLangs(array("main", "exports", "other"));

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');
$socid = GETPOSTINT('socid');
// The product selector returns 'idprod_X' (X = product id) or the id of a supplier price, so we convert it into a product id
$prodid = 0;
$reg = array();
if (preg_match('/^idprod_(\d+)$/', GETPOST('prodid', 'alpha'), $reg)) {
	$prodid = (int) $reg[1];
} elseif (GETPOSTINT('prodid') > 0) {
	$tmpproductfournisseur = new ProductFournisseur($db);
	if ($tmpproductfournisseur->fetch_product_fournisseur_price(GETPOSTINT('prodid')) > 0) {
		$prodid = (int) $tmpproductfournisseur->fk_product;
	}
}


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
$ajaxFileUrl = '';
$fullnewname = '';


/*
 * Actions
 */

if (getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE') && !is_numeric(getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE'))) {
	$urlforuploadpage = getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE');

	header("Location: ".$urlforuploadpage);
	exit(1);
}

if ($action == 'displayfile') {	// Return the uploaded file content for preview. Only PDF and images uploaded by the current user are allowed.
	$filetodisplay = dol_sanitizeFileName(GETPOST('originalfilename', 'alphanohtml'));
	$mimetodisplay = dol_mimetype($filetodisplay);
	if (!preg_match('/^upload_page-[a-z_]+-uid'.((int) $user->id).'-/', $filetodisplay)
		|| !preg_match('/^(application\/pdf|image\/(png|jpeg|gif|webp|bmp))$/', $mimetodisplay)
		|| !dol_is_file($upload_dir.'/'.$filetodisplay)) {
		httponly_accessforbidden('File not found or not allowed', 404);
	}

	top_httphead($mimetodisplay);
	header('Content-Disposition: inline; filename="'.dol_escape_htmltag($filetodisplay).'"');
	header('Content-Length: '.dol_filesize($upload_dir.'/'.$filetodisplay));
	readfileLowMemory($upload_dir.'/'.$filetodisplay);

	$db->close();
	exit(0);
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

		// Delete the JSON cache file of a previous import of the same file, so the AI service result is always refreshed
		dol_delete_file($upload_dir.'/'.dol_sanitizeFileName($fullnewname).'.json');

		// $dir_output = output dir of object
		// $dir_temp = temp dir of object
		// $upload_dir is "users/temp/import"
		include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';		// Save uploaded file into $upload_dir (with overwrite if file already exists)

		// Call AI to decrypt the file
		// Use AJAX call instead of redirect
		// @phpstan-ignore-next-line $error may have been modified by actions_linkedfiles.inc.php
		if (!$error) {
			// We will make the AJAX call from the bottom of the page
			$ajaxFileUrl = DOL_URL_ROOT.'/core/ajax/ajaxuploadpage.php?file='.urlencode($fullnewname);
		}
	}
} else {
	// Delete the temporary files that are used when uploading files
	dol_delete_file($upload_dir.'/upload_page-by'.$user->id.'-*');
}

if ($action == 'addobject') {
	$mandatoryfieldsareko = 0;

	if ($modulepart == 'invoice_supplier') {
		// Check mandatory fields for supplier invoice
		if (GETPOSTINT('socid') <= 0 ) {
			$langs->load("companies");
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("ThirdParty")), null, 'errors');
			$mandatoryfieldsareko = 1;
		}
		if ($prodid <= 0) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Product")), null, 'errors');
			$mandatoryfieldsareko = 1;
		}
		if (GETPOSTFLOAT('amount_ttc') <= 0 ) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Amount")), null, 'errors');
			$mandatoryfieldsareko = 1;
		}
		if (!GETPOST('supplierref', 'alphanohtml')) {
			setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("RefSupplier")), null, 'errors');
			$mandatoryfieldsareko = 1;
		}
		if (GETPOSTINT('invoiceid') > 0) {
			$langs->load("errors");
			setEventMessages($langs->trans("ErrorRefAlreadyExists", GETPOST('supplierref', 'alphanohtml')), null, 'errors');
			$mandatoryfieldsareko = 1;
		} elseif (GETPOST('supplierref', 'alphanohtml') && GETPOSTINT('socid') > 0) {
			// Check the supplier ref entered by user is not already used by another invoice of the same supplier
			$sql = "SELECT rowid FROM ".$db->prefix()."facture_fourn";
			$sql .= " WHERE ref_supplier = '".$db->escape(trim(GETPOST('supplierref', 'alphanohtml')))."'";
			$sql .= " AND fk_soc = ".((int) GETPOSTINT('socid'));
			$sql .= " AND entity IN (".getEntity('facture_fourn').")";
			$sql .= $db->plimit(1);
			$resql = $db->query($sql);
			if (!$resql) {
				setEventMessages($db->lasterror(), null, 'errors');
				$mandatoryfieldsareko = 1;
			} elseif ($db->num_rows($resql) > 0) {
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorRefAlreadyExists", GETPOST('supplierref', 'alphanohtml')), null, 'errors');
				$mandatoryfieldsareko = 1;
			}
		}
		if (!$user->hasRight('fournisseur', 'facture', 'creer') && !$user->hasRight('supplier_invoice', 'creer')) {
			setEventMessages($langs->trans("NotEnoughPermissions"), null, 'errors');
			$mandatoryfieldsareko = 1;
		}
	}

	if ($mandatoryfieldsareko) {
		$action = 'showsummary';
	}
}

$createdobject = null;
if ($action == 'addobject' && $modulepart == 'invoice_supplier') {
	$error = 0;

	$tmpthirdparty = new Societe($db);
	$tmpthirdparty->fetch(GETPOSTINT('socid'));
	$tmpproduct = new Product($db);
	$tmpproduct->fetch($prodid);

	$amount_ttc = GETPOSTFLOAT('amount_ttc');
	$vat_rate = GETPOST('vat_rate', 'alphanohtml');
	if ($vat_rate === '') {
		$vat_rate = get_default_tva($tmpthirdparty, $mysoc, $tmpproduct->id);
	}
	$vat_rate = price2num($vat_rate);
	$currency_code = GETPOST('currency_code', 'aZ09');

	$db->begin();

	$createdobject = new FactureFournisseur($db);
	$createdobject->socid = $tmpthirdparty->id;
	$createdobject->type = FactureFournisseur::TYPE_STANDARD;
	$createdobject->ref_supplier = GETPOST('supplierref', 'alphanohtml');
	// Dates found in document are timestamps of the day in user timezone. Invoice dates are "date only" fields, so we convert them into the same day in server timezone (as in fourn/facture/card.php).
	$dateinvoice = dol_now();
	$datedue = 0;
	$reg = array();
	if (GETPOSTINT('issue_date') > 0 && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', dol_print_date(GETPOSTINT('issue_date'), '%Y-%m-%d', 'tzuserrel'), $reg)) {
		$dateinvoice = dol_mktime(0, 0, 0, (int) $reg[2], (int) $reg[3], (int) $reg[1], 'tzserver');
	}
	if (GETPOSTINT('due_date') > 0 && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', dol_print_date(GETPOSTINT('due_date'), '%Y-%m-%d', 'tzuserrel'), $reg)) {
		$datedue = dol_mktime(0, 0, 0, (int) $reg[2], (int) $reg[3], (int) $reg[1], 'tzserver');
	}
	$createdobject->date = $dateinvoice;
	$createdobject->label = GETPOST('invoice_label', 'alphanohtml');
	$createdobject->libelle = $createdobject->label;	// deprecated
	$createdobject->cond_reglement_id = $tmpthirdparty->cond_reglement_supplier_id;
	$createdobject->mode_reglement_id = $tmpthirdparty->mode_reglement_supplier_id;
	$createdobject->fk_account = $tmpthirdparty->fk_account;
	$pu_devise = 0;
	if (isModEnabled('multicurrency') && $currency_code && $currency_code != $conf->currency) {
		$createdobject->multicurrency_code = $currency_code;
		$pu_devise = $amount_ttc;
	}
	$createdobject->date_echeance = $datedue;
	if (empty($createdobject->date_echeance)) {
		$createdobject->date_echeance = $createdobject->calculate_date_lim_reglement();
	}

	$result = $createdobject->create($user);
	if ($result <= 0) {
		$error++;
		setEventMessages($createdobject->error, $createdobject->errors, 'errors');
	}

	// Add one line with the product and the total amount including tax
	if (!$error) {
		$localtax1 = get_localtax($vat_rate, 1, $mysoc, $tmpthirdparty);
		$localtax2 = get_localtax($vat_rate, 2, $mysoc, $tmpthirdparty);
		$result = $createdobject->addline('', $amount_ttc, $vat_rate, $localtax1, $localtax2, 1, $tmpproduct->id, 0, 0, 0, 0, 0, 'TTC', $tmpproduct->type, -1, 0, array(), null, 0, $pu_devise);
		if ($result <= 0) {
			$error++;
			setEventMessages($createdobject->error, $createdobject->errors, 'errors');
		}
	}

	// Move the uploaded file as an attached file of the invoice
	if (!$error) {
		$filetoattach = dol_sanitizeFileName(GETPOST('originalfilename', 'alphanohtml'));
		if (preg_match('/^upload_page-[a-z_]+-uid'.((int) $user->id).'-/', $filetoattach) && dol_is_file($upload_dir.'/'.$filetoattach)) {
			$destfilename = preg_replace('/^upload_page-[a-z_]+-uid\d+(-thiid\d+)?(-pid\d+)?(-erid\d+)?(-salid\d+)?-/', '', $filetoattach);
			$destdir = getMultidirOutput($createdobject).'/'.get_exdir($createdobject->id, 2, 0, 0, $createdobject, 'invoice_supplier').dol_sanitizeFileName($createdobject->ref);
			dol_mkdir($destdir);
			$result = dol_move($upload_dir.'/'.$filetoattach, $destdir.'/'.$destfilename, '0', 1, 0, 1, array('gen_or_uploaded' => 'uploaded'));
			if (!$result) {
				$error++;
				$langs->load("errors");
				setEventMessages($langs->trans("ErrorFailToRenameFile", $filetoattach, $destfilename), null, 'errors');
			} else {
				dol_delete_file($upload_dir.'/'.$filetoattach.'.json');
			}
		}
	}

	if (!$error) {
		$db->commit();
		$action = 'objectcreated';
	} else {
		$db->rollback();
		$createdobject = null;
		$action = 'showsummary';
	}
}


/*
 * View
 */

$form = new Form($db);

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
/*
if (GETPOSTINT('cache')) {
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


// Form to upload a supplier invoice
if (empty($action)) {
	$uploadform = '<div class="display-flex">';

	if (isModEnabled('supplier_invoice')) {
		$prodtext = $langs->trans("RefOrLabel");

		$langs->load("bills");
		$uploadform .= '
		<div id="supplierinvoice" class="flex-item flex-item-uploadfile">'.img_picto('', 'bill', 'class="fa-2x"').'<br>
		<div>'.$langs->trans("SupplierInvoice").'<br><br>';

		//$uploadform .= '<div class="disableautoopen paddingbottom">';
		//$uploadform .= '<input type="checkbox" name="newsupplierinvoice" id="newsupplierinvoice" value="1" checked="checked" class="disableautoopen"><label for="newsupplierinvoice" class="disableautoopen">'.$langs->trans("NewInvoice").'</label><br>';
		//$uploadform .= '</div>';

		$uploadform .= img_picto('', 'company', 'class="pictofixedwidth"');
		$uploadform .= $form->select_company($socid, 'socid', '(statut:=:0)', $langs->transnoentitiesnoconv("Supplier"), 0, 0, array(), 0, 'maxwidth200 disableautoopen');

		$uploadform .= '<br>';

		//$uploadform .= $form->select_produits_fournisseurs(0, $prodid, 'prodid', '', '', 0, 1, 2, $prodtext, 0, array(), GETPOSTINT('socid'), '1', 0, 'maxwidth200 disableautoopen', 0, '', null, 1);
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

		$uploadform .= img_picto('', 'invoice', 'class="pictofixedwidth"');
		$uploadform .= $form->select_type_fees(GETPOSTINT('feetypeid') > 0 ? GETPOSTINT('feetypeid') : -1, 'feetypeid', $langs->transnoentitiesnoconv("TypeFees"), 'maxwidth200 disableautoopen', 1, 1);

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
		$uploadform .= $form->select_dolusers(GETPOSTINT('usersalaryid') > 0 ? GETPOSTINT('usersalaryid') : $user->id, 'usersalaryid', $langs->transnoentitiesnoconv("Employee"), null, 0, 'hierarchyme', '', '', 0, 0, '', 0, '', 'maxwidth200 disableautoopen', 1);

		$uploadform .= '<br>';

		$uploadform .= '<br>
		<small class="opacitymedium">'.$langs->trans("OrClickToSelectAFile").'...</small>
		</div>
		</div>';
	}

	// Form to upload a salary document
	if (isModEnabled('tax')) {
		$langs->load("taxes");
		$uploadform .= '
		<div id="userpayroll" class="flex-item flex-item-uploadfile">'.img_picto('', 'invoice', 'class="fa-2x"').'<br>
		<div>'.$langs->trans("SocialContribution").'<br><br>';

		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formsocialcontrib.class.php';
		$formsocialcontrib = new FormSocialContrib($db);

		$uploadform .= img_picto('', 'invoice', 'class="pictofixedwidth"');
		$uploadform .= $formsocialcontrib->select_type_socialcontrib(GETPOST('socialcontrib', 'alpha') ? GETPOSTINT('socialcontrib') : -1, 'socialcontrib', $langs->transnoentities("TypeContrib"), 0, 0, 'minwidth150 maxwidth200 disableautoopen', 0, 1);

		$uploadform .= '<br>';

		$uploadform .= img_picto('', 'user', 'class="pictofixedwidth"');
		$uploadform .= $form->select_dolusers(GETPOSTINT('usertaxid') > 0 ? GETPOSTINT('usertaxid') : -1, 'usertaxid', $langs->transnoentitiesnoconv("Employee"), null, 0, 'hierarchyme', '', '', 0, 0, '', 0, '', 'maxwidth200 disableautoopen', 1);

		$uploadform .= '<br>
		<small class="opacitymedium">'.$langs->trans("OrClickToSelectAFile").'...</small>
		</div>
		</div>';
	}

	$uploadform .= '</div>';
}



// Execute hook printSearchForm
$parameters = array('uploadform' => $uploadform);
$reshook = $hookmanager->executeHooks('printUploadForm', $parameters); // Note that $action and $object may have been modified by some hooks
if (empty($reshook)) {
	$uploadform .= $hookmanager->resPrint;
} else {
	$uploadform = $hookmanager->resPrint;
}

$uploadform .= '<br>';


if ($action == 'uploadfile' || $action == 'showsummary') {
	//print '<b>'.$langs->trans("ImportingFile", $originalfilename).'</b><br>';
	//print '<br>';

	print '<div class="'.($action == 'showsummary' ? 'green' : 'neutral').'" style="padding-left: 20px; padding-right: 20px;">';

	print '<div class="marginbottom">';
	if ($action == 'showsummary') {
		print $langs->trans("AIProcessingDone", $ai->getApiService());
	} else {
		print $langs->trans("AIProcessingPleaseWait", $ai->getApiService()).'...';
	}
	print '</div>';
	print '<br>';

	print '<div class="progress" title="80%">
	    <div class="progress-bar" role="progressbar" style="width: '.($action == 'showsummary' ? '100' : '0').'%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
	</div>';

	print '</div>';
}

if ($action == 'uploadfile') {
	// Add AJAX call script ($ajaxFileUrl is core/ajax/ajaxuploadpage.php?filename=...) where filename is into $conf->user->dir_temp.'/import';
	// ajaxuploadpage returns a JSON string also saved as filename.json.
	if (!empty($ajaxFileUrl)) {
		print '<script>
		$(document).ready(function() {
			console.log(\'Call ajaxFileUrl: '.dol_escape_js($ajaxFileUrl).'\');
			$.ajax({
				url: \''.dol_escape_js($ajaxFileUrl).'\',
				type: "GET",
				dataType: "json",
				beforeSend: function() {
					$(".progress-bar").css("width", "25%");
				},
				success: function(data, textStatus, jqXHR) {
					if (jqXHR.status === 200) {
						// Display the JSON content
						console.log("Fill the #ajax-result with json result");
						console.log(data);
						// Store the JSON encoded in base64 (UTF-8 safe) so it is not altered when posted
						$("#ajax-result").val(btoa(Array.from(new TextEncoder().encode(JSON.stringify(data, null, 2)), function (c) { return String.fromCharCode(c); }).join("")));
						$(".progress-bar").css("width", "100%");

						// Submit the form
						//$("#form-result").submit();

						// or show the button Next
						$("#ajax-result-message").html("<div class=\"ok\">Your file has been analyzed by the AI service.</div>");
						$("#form-result-submit").show();
					} else {
						console.log("We received an error");

						// Display error if status is not 200
						$("#ajax-result-message").html("<div class=\"error\">Error: HTTP status " + jqXHR.status + "</div>");
					}
				},
				error: function(jqXHR, textStatus, errorThrown) {
					// Display error
					console.log("We received an error");
					$("#ajax-result-message").html("<div class=\"error\">Error: " + (jqXHR.responseText || errorThrown || "Unknown error") + "</div>");
					$(".progress-bar").css("width", "100%");
				}
			});
		});
		</script>';
	}

	// Example of JSON result when importing a supplier PDF invoice
	/* {
		  "document_info": {
			"type": "Facture",
			"invoice_number": "MSTRL-API-1234-001",
			"invoice_reference": "Pro - Annual",
			"issue_date": "2026-08-21",
			"due_date": "2026-08-21",
			"payment_terms": {
			  "days": 0,
			  "description": "0 jours"
			},
			"organization": "MyBigCompany"
		  },
		  "vendor": {
			"name": "Mistral AI SAS",
			"address": {
			  "street": "15, rue des Halles",
			  "postal_code": "75001",
			  "city": "Paris",
			  "country": "France"
			},
			"vat_number": "FR95952418325",
			"professional_id": {
			  "siren": "952 418 325",
			  "capital": "19,189.04 €",
			  "rcs": "Paris"
			},
			"email": "no-reply@mistral.ai",
			"contact_url": "https://help.mistral.ai/en"
		  },
		  "recipient": {
			"name": "MyBigCompany",
			"address": {
			  "street": "1 rue de la paix",
			  "postal_code": "75000",
			  "city": "Paris",
			  "country": "France"
			},
			"vat_number": "FR012345678901",
			"email": "contact@mybigcompany.com"
		  },
		  "items": [
			{
			  "description": "Fee for use- August",
			  "service": "Pro - Annual",
			  "quantity": 1,
			  "unit_price": 143.9,
			  "vat_rate": 20,
			  "total_excluding_tax": 143.9,
			  "total_including_tax": 172.68,
			  "period_start": "2026-08-21",
			  "period_end": "2027-08-20"
			}
		  ],
		  "summary": {
			"subtotal_excluding_tax": 143.9,
			"tax": {
			  "rate": 20,
			  "amount": 28.78,
			  "description": "EU FR Standard (20.0% sur 143,90 €)"
			},
			"total_including_tax": 172.68
		  },
		  "payment_info": {
			"due_date": "2026-08-21",
			"amount_due": 172.68,
			"currency": "EUR"
		  },
		  "other": {
			"notes": [
			  {
				"type": "legal",
				"content": "Mistral AI - SAS au capital de 19,189.04€ - 952 418 325 R.C.S. Paris"
			  },
			  {
				"type": "contact",
				"content": "For any inquiry, please refer to https://help.mistral.ai/en"
			  }
			]
		  }
		}
	 */

	print '<form class="" id="form-result" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="showsummary">';
	print '<input type="hidden" name="modulepart" value="'.$modulepart.'">';
	print '<input type="hidden" name="socid" value="'.$socid.'">';
	print '<input type="hidden" name="prodid" value="'.($prodid > 0 ? 'idprod_'.$prodid : '').'">';
	print '<input type="hidden" name="originalfilename" value="'.$fullnewname.'">';
	print '<input type="hidden" name="jsonstring" id="ajax-result" value="">';	// Fill by the ajax answer with the JSON encoded in base64
	print '<div id="ajax-result-message"></div>';
	if ($action == 'uploadfile') {
		print '<br>';
		print '<input type="submit" name="form-result-submit" id="form-result-submit" class="button" value="'.$langs->trans("Next").'">';		// TODO Hide.
	}
	print '</form>'."\n";
	print "\n";
}

if ($action == 'showsummary') {
	print '<form class="" id="summary-result" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="addobject">';
	print '<input type="hidden" name="modulepart" value="'.$modulepart.'">';
	print '<input type="hidden" name="originalfilename" value="'.dolPrintHTMLForAttribute(GETPOST('originalfilename', 'alphanohtml')).'">';
	print '<div id="ajax-result-message"></div>';
	//print '<br>';

	print load_fiche_titre('Summary', '', 'upload');

	print '<div class="neutral">';

	print '<input type="hidden" name="token" value="'.newToken().'">';

	// Read the result of the AI service from the JSON cache file saved by ajaxuploadpage.php, or from the posted value as fallback
	$jsonstring = (string) base64_decode(GETPOST('jsonstring', 'alpha'), true);	// The posted JSON is encoded in base64. Strict mode returns false (so '') on invalid chars.
	$filejsoncache = dol_sanitizeFileName(GETPOST('originalfilename', 'alphanohtml'));
	if (preg_match('/^upload_page-[a-z_]+-uid'.((int) $user->id).'-/', $filejsoncache) && dol_is_file($upload_dir.'/'.$filejsoncache.'.json')) {
		$jsonstring = (string) file_get_contents($upload_dir.'/'.$filejsoncache.'.json');
	}

	$json = null;
	if (!empty($jsonstring)) {		// This can happen if we resubmit a page after doc was created, both jsonsting and cache file does not exists anymore.
		$json = json_decode($jsonstring, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			print '<!-- JSON string -->';
			// Insert a space between consecutive dashes so content can't close the HTML comment ("-->" or "--!>")
			print '<!-- '.preg_replace('/-(?=-)/', '- ', $jsonstring).' -->';
			//print '<!-- '.$jsonstring.' -->';
		} else {
			print 'Failed to decode the result of the AI service. Error: '.dolPrintHTML(json_last_error_msg());
			print '<br>';
			print 'Result: '.dolPrintHTML($jsonstring);
			print '<br>';
			print '<br>';
			$json = null;
		}
	}

	if (!is_array($json)) {
		$json = array();
	}

	if ($modulepart == 'invoice_supplier') {
		// Parse JSON to get information for an invoice
		$invoiceindoc = $ai->decodeJsonIntoArray($json, 'supplier_invoice');

		//var_dump($json, $invoiceindoc);

		print "\n\n";
		print '<div id="supplierinvoice" class="">';

		//print '<div class="inline-block">'.img_picto('', 'bill', '').' '.$langs->trans("SupplierInvoice").'</div>';
		//print '<br><br>';

		$nameindoc = (string) $invoiceindoc['vendor_name'];
		$addressindoc = (string) $invoiceindoc['vendor_address'];
		$idprof1indoc = (string) $invoiceindoc['vendor_profid1'];
		$idprof2indoc = (string) $invoiceindoc['vendor_profid2'];
		$emailindoc = (string) $invoiceindoc['vendor_email'];
		$vatnumberindoc = (string) $invoiceindoc['vendor_vat_number'];


		// Thirdparty
		$tmpthirdparty = new Societe($db);
		print img_picto('', 'company', 'class="pictofixedwidth"').$langs->trans("ThirdParty").'<br>';
		if (GETPOSTINT('socid')) {
			$tmpthirdparty->fetch(GETPOSTINT('socid'));
			// TODO We can show a warning if we find a diff with autodetected value $nameindoc
		} else {
			// Try to find thirdparty
			$result = $tmpthirdparty->findNearest(0, $nameindoc, '', '', $idprof1indoc, $idprof2indoc, '', '', '', '', $emailindoc, $nameindoc, 0, 0, $vatnumberindoc);

			if ($result > 0) {
				$tmpthirdparty->fetch($result);
			}
		}

		print '<span class="opacitymedium">'.$langs->trans("KeyDataFoundInDocument").' :</span> ';
		$s = $nameindoc;
		if (!empty($addressindoc)) {
			$s .= ', &nbsp;'.$addressindoc;
		}
		if (!$s) {
			print '<span class="warning">'.$langs->trans("NoDataRelatedTo").'</span>';
		}
		print $s;
		print '<br>';
		if (empty($tmpthirdparty->id)) {
			if ($s) {
				print '<span class="warning">'.$langs->trans("NotFoundInDatabase").'</span><br>';
			}
			print $langs->trans("ChooseTheThirdPartyTouse").' ';
			print $form->select_company($socid, 'socid', '(statut:=:0)', $langs->trans("Name"), 0, 0, array(), 0, 'maxwidth200 disableautoopen');
			//print ' &nbsp; '.$langs->trans("or").' &nbsp; <input type="checkbox" name="createthirdparty" id="createthirdparty" value="1" checked><label for="createthirdparty"> '.$langs->trans("CreateIt").'</label>';
		} else {
			print '<span class="valignmiddle">'.$langs->trans("WillUse").'</span> ';
			print $tmpthirdparty->getNomUrl(1);
			print '<input type="hidden" name="socid" value="'.$tmpthirdparty->id.'">';
		}


		print "\n";
		print '<br><br><hr><br>';
		print "\n";


		$nameprodindoc = '';
		if (!empty($invoiceindoc['lines']) && is_array($invoiceindoc['lines'])) {
			$firstlineindoc = reset($invoiceindoc['lines']);
			if (is_array($firstlineindoc)) {
				$nameprodindoc = $firstlineindoc['desc'] ?? '';
			}
		}
		$nbofitems = 0;
		if (!empty($invoiceindoc['lines']) && is_array($invoiceindoc['lines'])) {
			$nbofitems = count($invoiceindoc['lines']);
		}

		// Product ($prodid was already converted into a product id at top of page)
		$prodtext = $langs->trans("RefOrLabel");

		$tmpproduct = new Product($db);
		print img_picto('', 'product', 'class="pictofixedwidth"').$langs->trans("Product").'<br>';

		$amount_ttc = (GETPOSTFLOAT('amount_ttc', '', 2) ? price(GETPOSTFLOAT('amount_ttc', '', 2)) : ($invoiceindoc['total_ttc'] ?? ''));
		$vat_rate = (GETPOSTFLOAT('vat_rate', '', 2) ? price(GETPOSTFLOAT('vat_rate', '', 2)) : ($invoiceindoc['vat_rate'] ?? ''));

		if ($prodid) {
			$tmpproduct->fetch($prodid);
			if (empty($amount_ttc)) {
				// TODO get price of product
			}
		} else {
			//$tmpproduct->ref = $json['ref'];
			// Try to find product
			//$result = $tmpproduct->findNearest(...);
		}


		print '<span class="opacitymedium">'.$langs->trans("KeyDataFoundInDocument").' :</span> ';
		$s = $nameprodindoc;
		if (!empty($nbofitems)) {
			$s .= ($s ? ', &nbsp;' : '').$langs->trans("NbOfLines").': '.$nbofitems;
		}
		if (!$s) {
			print '<span class="warning">'.$langs->trans("NoDataRelatedTo").'</span>';
		}
		print $s;
		print '<br>';
		if (empty($tmpproduct->id)) {
			print $langs->trans("ChooseTheProductTouse").' ';
			print $form->select_produits_fournisseurs(0, $prodid, 'prodid', '', '', array(), 1, 1, 'maxwidth200 disableautoopen', $prodtext, 1);

			// TODO On selection of product, refresh next section with the product price ref
		} else {
			print $langs->trans("WillUse").' ';
			print $tmpproduct->getNomUrl(1);
			print '<input type="hidden" name="prodid" value="idprod_'.$tmpproduct->id.'">';
		}

		print '<br>';
		print $langs->trans("VATRate").' ';
		print '<input type="text" class="width75 right" name="vat_rate" id="vat_rate" value="'.$vat_rate.'">%';

		print '<br>';
		print $langs->trans("TotalTTC").' ';
		print '<input type="text" class="width100 right" name="amount_ttc" id="amount_ttc" value="'.$amount_ttc.'">';

		print "\n";
		print '<br><br><hr><br>';
		print "\n";


		// Supplier invoice
		$tmpinvoice = new FactureFournisseur($db);

		print img_picto('', 'supplier_invoice', 'class="pictofixedwidth"').$langs->trans("SupplierInvoice").'<br>';

		print '<span class="opacitymedium">'.$langs->trans("KeyDataFoundInDocument").' :</span> ';
		$tmpvalue = array();
		$invoiceindoc['supplierref'] ? $tmpvalue[] = $langs->transnoentitiesnoconv("InvoiceRef").': '.$invoiceindoc['supplierref'] : '';
		$invoiceindoc['issue_date'] ? $tmpvalue[] = $langs->transnoentitiesnoconv("DateInvoice").': '.dol_print_date($invoiceindoc['issue_date'], 'day', 'tzuserrel') : '';
		$invoiceindoc['due_date'] ? $tmpvalue[] = $langs->transnoentitiesnoconv("DateDue").': '.dol_print_date($invoiceindoc['due_date'], 'day', 'tzuserrel') : '';
		$invoiceindoc['currency_code'] ? $tmpvalue[] = $langs->transnoentitiesnoconv("Currency").': '.$invoiceindoc['currency_code'] : '';
		$invoiceindoc['invoice_label'] ? $tmpvalue[] = $langs->transnoentitiesnoconv("Label").': '.$invoiceindoc['invoice_label'] : '';

		$s = implode(', ', $tmpvalue);
		if (!$s) {
			print '<span class="warning">'.$langs->trans("NoDataRelatedTo").'</span>';
		}
		print $s;
		print '<br>';
		if (!empty($invoiceindoc['supplierref'])) {
			// Try to find supplier invoice

			// Exact match. Always tried first, and always enough on its own.
			$sql = "SELECT rowid, total_ttc FROM " . $db->prefix() . "facture_fourn";
			$sql .= " WHERE ref_supplier = '" . $db->escape($invoiceindoc['supplierref']) . "'";
			$sql .= " AND fk_soc = " . ((int) $tmpthirdparty->id);
			$sql .= " AND entity IN (" . getEntity('facture_fourn') . ")";
			$sql .= " LIMIT 1";
			$resql = $db->query($sql);
			if (!$resql) {
				dol_syslog("SQL Error " . $db->lasterror(), LOG_ERR);
				return -1;
			}
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$tmpinvoice->fetch($obj->rowid);
			}
		}

		print "\n";
		print "\n";
		if (empty($tmpinvoice->id)) {
			print $langs->trans("InvoiceWillBeCreated");
			// Supplier ref is editable, prefilled with the posted value (when back after an error) or with the value detected in document
			$supplierref = (GETPOSTISSET('supplierref') ? GETPOST('supplierref', 'alphanohtml') : (string) $invoiceindoc['supplierref']);

			print '<br>'.$langs->trans("RefSupplier").' ';
			print '<input type="text" class="minwidth150" name="supplierref" id="supplierref" value="'.dolPrintHTMLForAttribute($supplierref).'">';

			print '<input type="hidden" name="due_date" value="'.$invoiceindoc['due_date'].'">';
			print '<input type="hidden" name="issue_date" value="'.$invoiceindoc['issue_date'].'">';
			print '<input type="hidden" name="currency_code" value="'.$invoiceindoc['currency_code'].'">';
			print '<input type="hidden" name="invoice_label" value="'.$invoiceindoc['invoice_label'].'">';
		} else {
			print $tmpinvoice->getNomUrl(1);
			print '<input type="hidden" name="invoiceid" value="'.$tmpinvoice->id.'">';
		}

		print '<br>
		</div>
		</div>';
		print "\n";

		print '<center><input type="submit" name="submit" class="button" value="'.$langs->trans("Create").'"></center>';
		print "\n";
	}

	// Preview of the uploaded file (PDF is shown with the native viewer of the browser)
	$filetopreview = dol_sanitizeFileName(GETPOST('originalfilename', 'alphanohtml'));
	if ($filetopreview && dol_is_file($upload_dir.'/'.$filetopreview)) {
		$mimetopreview = dol_mimetype($filetopreview);
		$urltopreview = $_SERVER["PHP_SELF"].'?action=displayfile&originalfilename='.urlencode($filetopreview);
		print "\n";
		if ($mimetopreview == 'application/pdf') {
			print '<br><br><div class="center">';
			print '<iframe src="'.dolPrintHTMLForAttributeUrl($urltopreview).'" title="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("Preview")).'" style="width: 100%; height: 800px; border: none;"></iframe>';
			print '</div>';
		} elseif (preg_match('/^image\/(png|jpeg|gif|webp|bmp)$/', $mimetopreview)) {
			print '<br><br><div class="center">';
			print '<img src="'.dolPrintHTMLForAttributeUrl($urltopreview).'" alt="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv("Preview")).'" style="max-width: 100%;">';
			print '</div>';
		}
		print "\n";
	}

	print '<input type="hidden" name="jsonstring" value="'.base64_encode($jsonstring).'">';

	print '</div>';
	print '</form>';
	print "\n";
} elseif ($action == 'objectcreated' && is_object($createdobject)) {
	print load_fiche_titre('Summary', '', 'upload');

	print '<div class="neutral">';
	print '<br>';
	print $langs->trans("DraftInvoiceCreatedFromFile").' ';
	print $createdobject->getNomUrl(1);
	print '<br>';
	print '<br>';
	print '</div>';
	print "\n";
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
