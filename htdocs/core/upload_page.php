<?php
/* Copyright (C) 2005-2017  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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

//if (! defined('NOREQUIREUSER'))   define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');		// Not disabled cause need to do translations
/*
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
*/
//if (! defined('NOLOGIN')) define('NOLOGIN',1);					// Not disabled cause need to load personalized language
/*
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', 1);
}
*/
//if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML',1);

require_once '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.form.class.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

if (GETPOST('lang', 'aZ09')) {
	$langs->setDefaultLang(GETPOST('lang', 'aZ09')); // If language was forced on URL by the main.inc.php
}

$langs->loadLangs(array("main", "other"));

$action = GETPOST('action', 'aZ09');
$modulepart = GETPOST('modulepart', 'aZ09');


/*
 * Actions
 */

if (getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE') && !is_numeric(getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE'))) {
	$urlforuploadpage = getDolGlobalString('MAIN_USE_TOP_MENU_IMPORT_FILE');

	header("Location: ".$urlforuploadpage);
	exit(1);
}

if ($action == 'uploadfile') {	// Test on permission not required here. Done later
	$arrayobject = getElementProperties($modulepart);

	$module = $arrayobject['module'];
	$element = $arrayobject['element'];
	$dir_output = $arrayobject['dir_output'];
	$dir_temp = $arrayobject['dir_temp'];

	$permlevel1 = $element;
	if ($module == 'fournisseur') {
		$permlevel1 = 'facture';
	}

	$permissiontoadd = $user->hasRight($module, $permlevel1, 'read');
	$upload_dir = $dir_temp.'/import';
	$forceFullTextIndexation = '1';

	// Set $object so entry file will be linked to object.
	// TODO

	include DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';
}


/*
 * View
 */

$form = new Form($db);

// Important: Following code is to avoid page request by browser and PHP CPU at each Dolibarr page access.
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

$title = $langs->trans("UploadFile");
$help_url = '';

// URL http://mydolibarr/core/search_page?dol_use_jmobile=1 can be used for tests
$head = '<!-- Upload file -->'."\n";	// This is used by DoliDroid to know page is a search page
$arrayofjs = array();
$arrayofcss = array();

llxHeader('', $title, $help_url, '', 0, 0, $arrayofjs, $arrayofcss, '', 'mod-upload page-card');
//top_htmlhead($head, $title, 0, 0, $arrayofjs, $arrayofcss);

print load_fiche_titre('', '', '', 0, '', '', '<h2>'.$title.'</h2>');


// Instantiate hooks of thirdparty module
$hookmanager->initHooks(array('uploadform'));

// Define $uploadform
$uploadform = '';


$uploadform = '<div class="display-flex">';

if (isModEnabled('supplier_invoice')) {
	$langs->load("bills");
	$uploadform .= '
	<div id="supplierinvoice" class="flex-item flex-item-uploadfile">'.img_picto('', 'bill', 'class="fa-2x"').'<br>
	<div>'.$langs->trans("SupplierInvoice").'<br><br>';

	$uploadform .= img_picto('', 'company', 'class="pictofixedwidth"');
	$uploadform .= $form->select_company(GETPOSTINT('socid'), 'socid', 'statut=0', $langs->transnoentitiesnoconv("Supplier"));

	$uploadform .= '<br><br>
	<small>('.$langs->trans("OrClickToSelectAFile").')</small>
	</div>
	</div>';
}

if (isModEnabled('salaries')) {
	$langs->load("salaries");
	$uploadform .= '
	<div id="userpayroll" class="flex-item flex-item-uploadfile">'.img_picto('', 'salary', 'class="fa-2x"').'<br>
	<div>'.$langs->trans("UserPaySlip").'<br>
	<small>('.$langs->trans("OrClickToSelectAFile").')</small>
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


// Show all forms
print "\n";
print "<!-- Begin UploadForm -->\n";
print '<form id="uploadform" enctype="multipart/form-data" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="uploadfile">';
print '<input type="hidden" name="sendit" value="1">';
print '<input type="hidden" name="modulepart" id="modulepart" value="">';

print '<div class="center"><div class="center" style="padding: 30px;">';
print '<style>.menu_titre { padding-top: 7px; }</style>';
print '<div id="blockupload" class="center">'."\n";
//print '<input name="filenamePDF" id="filenamePDF" type="hideobject">';
print $uploadform;


$accept = '.pdf, image';
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
$out .= '<input class="hideobject" type="file" id="fileInput"';
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
	jQuery('#supplierinvoice').on('click', function(event) {
		console.log('Click on link to open input file');
		console.log(event);
		$('#modulepart').val('invoice_supplier');
		$('#fileInput').click();
	});

	jQuery('#userpayroll').on('click', function(event) {
		console.log('Click on link to open input file');
		console.log(event);
		$('#modulepart').val('salary');
		$('#fileInput').click();
	});

    jQuery('#search_socid').on('click', function(event) {
        event.stopPropagation();
		console.log('Avoid to open the input select');
    });

	jQuery('#fileInput').on('change', function() {
		console.log('A file was selected, we submit the form');
		$('#uploadform').submit();
	});
});
</script>";

print '</div>'."\n";
print '</div></div>';

print '</form>';
print "\n<!-- End UploadForm -->\n";



// End of page
llxFooter();
$db->close();
