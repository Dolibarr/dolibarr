<?php
/* Copyright (C) 2003	   Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003	   Jean-Louis Bergamo	<jlb@j1b.org>
 * Copyright (C) 2006-2017 Laurent Destailleur	<eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	 See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file		htdocs/barcode/printsheet.php
 *	\ingroup	member
 *	\brief		Page to print sheets with barcodes using the document templates into core/modules/printsheets
 */

if (!empty($_POST['mode']) && $_POST['mode'] === 'label') {	// Page is called to build a PDF and output, we must not renew the token.
	if (!defined('NOTOKENRENEWAL')) {
		define('NOTOKENRENEWAL', '1'); // Do not roll the Anti CSRF token (used if MAIN_SECURITY_CSRF_WITH_TOKEN is on)
	}
}

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/format_cards.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/printsheet/modules_labels.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/genericobject.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'members', 'errors'));

// Choice of print year or current year.
$now = dol_now();
$year = dol_print_date($now, '%Y');
$month = dol_print_date($now, '%m');
$day = dol_print_date($now, '%d');
$forbarcode = GETPOST('forbarcode', 'alphanohtml');
$fk_barcode_type = GETPOST('fk_barcode_type', 'int');
$mode = GETPOST('mode', 'aZ09');
$modellabel = GETPOST("modellabel", 'aZ09'); // Doc template to use
$numberofsticker = GETPOST('numberofsticker', 'int');

$mesg = '';

$action = GETPOST('action', 'aZ09');

$producttmp = new Product($db);
$thirdpartytmp = new Societe($db);


/*
 * Actions
 */

if (GETPOST('submitproduct') && GETPOST('submitproduct')) {
	$action = ''; // We reset because we don't want to build doc
	if (GETPOST('productid', 'int') > 0) {
		$result = $producttmp->fetch(GETPOST('productid', 'int'));
		if ($result < 0) {
			setEventMessage($producttmp->error, 'errors');
		}
		$forbarcode = $producttmp->barcode;
		$fk_barcode_type = $producttmp->barcode_type;

		if (empty($fk_barcode_type) && !empty($conf->global->PRODUIT_DEFAULT_BARCODE_TYPE)) {
			$fk_barcode_type = $conf->global->PRODUIT_DEFAULT_BARCODE_TYPE;
		}

		if (empty($forbarcode) || empty($fk_barcode_type)) {
			setEventMessages($langs->trans("DefinitionOfBarCodeForProductNotComplete", $producttmp->getNomUrl()), null, 'warnings');
		}
	}
}
if (GETPOST('submitthirdparty') && GETPOST('submitthirdparty')) {
	$action = ''; // We reset because we don't want to build doc
	if (GETPOST('socid', 'int') > 0) {
		$thirdpartytmp->fetch(GETPOST('socid', 'int'));
		$forbarcode = $thirdpartytmp->barcode;
		$fk_barcode_type = $thirdpartytmp->barcode_type_code;

		if (empty($fk_barcode_type) && !empty($conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY)) {
			$fk_barcode_type = $conf->global->GENBARCODE_BARCODETYPE_THIRDPARTY;
		}

		if (empty($forbarcode) || empty($fk_barcode_type)) {
			setEventMessages($langs->trans("DefinitionOfBarCodeForThirdpartyNotComplete", $thirdpartytmp->getNomUrl()), null, 'warnings');
		}
	}
}

if ($action == 'builddoc') {
	$result = 0; $error = 0;

	if (empty($forbarcode)) {			// barcode value
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BarcodeValue")), null, 'errors');
		$error++;
	}
	if (empty($fk_barcode_type)) {		// barcode type = barcode encoding
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("BarcodeType")), null, 'errors');
		$error++;
	}

	if (!$error) {
		// Get encoder (barcode_type_coder) from barcode type id (barcode_type)
		$stdobject = new GenericObject($db);
		$stdobject->barcode_type = $fk_barcode_type;
		$result = $stdobject->fetch_barcode();
		if ($result <= 0) {
			$error++;
			setEventMessages('Failed to get bar code type information '.$stdobject->error, $stdobject->errors, 'errors');
		}
	}

	if (!$error) {
		$code = $forbarcode;
		$generator = $stdobject->barcode_type_coder; // coder (loaded by fetch_barcode). Engine.
		$encoding = strtoupper($stdobject->barcode_type_code); // code (loaded by fetch_barcode). Example 'ean', 'isbn', ...

		$diroutput = $conf->barcode->dir_temp;
		dol_mkdir($diroutput);

		// Generate barcode
		$dirbarcode = array_merge(array("/core/modules/barcode/doc/"), $conf->modules_parts['barcode']);

		foreach ($dirbarcode as $reldir) {
			$dir = dol_buildpath($reldir, 0);
			$newdir = dol_osencode($dir);

			// Check if directory exists (we do not use dol_is_dir to avoid loading files.lib.php)
			if (!is_dir($newdir)) {
				continue;
			}

			$result = @include_once $newdir.$generator.'.modules.php';
			if ($result) {
				break;
			}
		}

		// Load barcode class for generating barcode image
		$classname = "mod".ucfirst($generator);
		$module = new $classname($db);
		if ($generator != 'tcpdfbarcode') {
			// May be phpbarcode
			$template = 'standardlabel';
			$is2d = false;
			if ($module->encodingIsSupported($encoding)) {
				$barcodeimage = $conf->barcode->dir_temp.'/barcode_'.$code.'_'.$encoding.'.png';
				dol_delete_file($barcodeimage);
				// File is created with full name $barcodeimage = $conf->barcode->dir_temp.'/barcode_'.$code.'_'.$encoding.'.png';
				$result = $module->writeBarCode($code, $encoding, 'Y', 4, 1);
				if ($result <= 0 || !dol_is_file($barcodeimage)) {
					$error++;
					setEventMessages('Failed to generate image file of barcode for code='.$code.' encoding='.$encoding.' file='.basename($barcodeimage), null, 'errors');
					setEventMessages($module->error, null, 'errors');
				}
			} else {
				$error++;
				setEventMessages("Error, encoding ".$encoding." is not supported by encoder ".$generator.'. You must choose another barcode type or install a barcode generation engine that support '.$encoding, null, 'errors');
			}
		} else {
			$template = 'tcpdflabel';
			$encoding = $module->getTcpdfEncodingType($encoding); //convert to TCPDF compatible encoding types
			$is2d = $module->is2d;
		}
	}

	if (!$error) {
		// List of values to scan for a replacement
		$substitutionarray = array(
			'%LOGIN%' => $user->login,
			'%COMPANY%' => $mysoc->name,
			'%ADDRESS%' => $mysoc->address,
			'%ZIP%' => $mysoc->zip,
			'%TOWN%' => $mysoc->town,
			'%COUNTRY%' => $mysoc->country,
			'%COUNTRY_CODE%' => $mysoc->country_code,
			'%EMAIL%' => $mysoc->email,
			'%YEAR%' => $year,
			'%MONTH%' => $month,
			'%DAY%' => $day,
			'%DOL_MAIN_URL_ROOT%' => DOL_MAIN_URL_ROOT,
			'%SERVER%' => "http://".$_SERVER["SERVER_NAME"]."/",
		);
		complete_substitutions_array($substitutionarray, $langs);

		// For labels
		if ($mode == 'label') {
			$txtforsticker = "%PHOTO%"; // Photo will be barcode image, %BARCODE% posible when using TCPDF generator
			$textleft = make_substitutions((empty($conf->global->BARCODE_LABEL_LEFT_TEXT) ? $txtforsticker : $conf->global->BARCODE_LABEL_LEFT_TEXT), $substitutionarray);
			$textheader = make_substitutions((empty($conf->global->BARCODE_LABEL_HEADER_TEXT) ? '' : $conf->global->BARCODE_LABEL_HEADER_TEXT), $substitutionarray);
			$textfooter = make_substitutions((empty($conf->global->BARCODE_LABEL_FOOTER_TEXT) ? '' : $conf->global->BARCODE_LABEL_FOOTER_TEXT), $substitutionarray);
			$textright = make_substitutions((empty($conf->global->BARCODE_LABEL_RIGHT_TEXT) ? '' : $conf->global->BARCODE_LABEL_RIGHT_TEXT), $substitutionarray);
			$forceimgscalewidth = (empty($conf->global->BARCODE_FORCEIMGSCALEWIDTH) ? 1 : $conf->global->BARCODE_FORCEIMGSCALEWIDTH);
			$forceimgscaleheight = (empty($conf->global->BARCODE_FORCEIMGSCALEHEIGHT) ? 1 : $conf->global->BARCODE_FORCEIMGSCALEHEIGHT);

			$MAXSTICKERS = 1000;
			if ($numberofsticker <= $MAXSTICKERS) {
				for ($i = 0; $i < $numberofsticker; $i++) {
					$arrayofrecords[] = array(
						'textleft'=>$textleft,
						'textheader'=>$textheader,
						'textfooter'=>$textfooter,
						'textright'=>$textright,
						'code'=>$code,
						'encoding'=>$encoding,
						'is2d'=>$is2d,
						'photo'=>$barcodeimage	// Photo must be a file that exists with format supported by TCPDF
					);
				}
			} else {
				$mesg = $langs->trans("ErrorQuantityIsLimitedTo", $MAXSTICKERS);
				$error++;
			}
		}

		$i++;

		// Build and output PDF
		if (!$error && $mode == 'label') {
			if (!count($arrayofrecords)) {
				$mesg = $langs->trans("ErrorRecordNotFound");
			}
			if (empty($modellabel) || $modellabel == '-1') {
				$mesg = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("DescADHERENT_ETIQUETTE_TYPE"));
			}

			$outfile = $langs->trans("BarCode").'_sheets_'.dol_print_date(dol_now(), 'dayhourlog').'.pdf';

			if (!$mesg) {
				$outputlangs = $langs;

				// This generates and send PDF to output
				// TODO Move
				$result = doc_label_pdf_create($db, $arrayofrecords, $modellabel, $outputlangs, $diroutput, $template, dol_sanitizeFileName($outfile));
			}
		}

		if ($result <= 0 || $mesg || $error) {
			if (empty($mesg)) {
				$mesg = 'Error '.$result;
			}

			setEventMessages($mesg, null, 'errors');
		} else {
			$db->close();
			exit;
		}
	}
}


/*
 * View
 */

if (empty($conf->barcode->enabled)) {
	accessforbidden();
}

$form = new Form($db);

llxHeader('', $langs->trans("BarCodePrintsheet"));

print load_fiche_titre($langs->trans("BarCodePrintsheet"), '', 'barcode');
print '<br>';

print '<span class="opacitymedium">'.$langs->trans("PageToGenerateBarCodeSheets", $langs->transnoentitiesnoconv("BuildPageToPrint")).'</span><br>';
print '<br>';

//print img_picto('','puce').' '.$langs->trans("PrintsheetForOneBarCode").'<br>';
//print '<br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">'; // The target is for brothers that open the file instead of downloading it
print '<input type="hidden" name="mode" value="label">';
print '<input type="hidden" name="action" value="builddoc">';
print '<input type="hidden" name="token" value="'.currentToken().'">'; // The page will not renew the token but force download of a file, so we must use here currentToken

print '<div class="tagtable">';

// Sheet format
print '	<div class="tagtr">';
print '	<div class="tagtd">';
print $langs->trans("DescADHERENT_ETIQUETTE_TYPE").' &nbsp; ';
print '</div><div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;">';
// List of possible labels (defined into $_Avery_Labels variable set into core/lib/format_cards.lib.php)
$arrayoflabels = array();
foreach (array_keys($_Avery_Labels) as $codecards) {
	$labeltoshow = $_Avery_Labels[$codecards]['name'];
	//$labeltoshow.=' ('.$_Avery_Labels[$row['code']]['paper-size'].')';
	$arrayoflabels[$codecards] = $labeltoshow;
}
asort($arrayoflabels);
print $form->selectarray('modellabel', $arrayoflabels, (GETPOST('modellabel') ?GETPOST('modellabel') : $conf->global->ADHERENT_ETIQUETTE_TYPE), 1, 0, 0, '', 0, 0, 0, '', '', 1);
print '</div></div>';

// Number of stickers to print
print '	<div class="tagtr">';
print '	<div class="tagtd">';
print $langs->trans("NumberOfStickers").' &nbsp; ';
print '</div><div class="tagtd maxwidthonsmartphone" style="overflow: hidden; white-space: nowrap;">';
print '<input size="4" type="text" name="numberofsticker" value="'.(GETPOST('numberofsticker') ?GETPOST('numberofsticker', 'int') : 10).'">';
print '</div></div>';

print '</div>';


print '<br>';


// Add javascript to make choice dynamic
print '<script type="text/javascript">
jQuery(document).ready(function() {
	function init_selectors()
	{
		if (jQuery("#fillmanually:checked").val() == "fillmanually")
		{
			jQuery("#submitproduct").prop("disabled", true);
			jQuery("#submitthirdparty").prop("disabled", true);
			jQuery("#search_productid").prop("disabled", true);
			jQuery("#socid").prop("disabled", true);
			jQuery(".showforproductselector").hide();
			jQuery(".showforthirdpartyselector").hide();
		}
		if (jQuery("#fillfromproduct:checked").val() == "fillfromproduct")
		{
			jQuery("#submitproduct").removeAttr("disabled");
			jQuery("#submitthirdparty").prop("disabled", true);
			jQuery("#search_productid").removeAttr("disabled");
			jQuery("#socid").prop("disabled", true);
			jQuery(".showforproductselector").show();
			jQuery(".showforthirdpartyselector").hide();
		}
		if (jQuery("#fillfromthirdparty:checked").val() == "fillfromthirdparty")
		{
			jQuery("#submitproduct").prop("disabled", true);
			jQuery("#submitthirdparty").removeAttr("disabled");
			jQuery("#search_productid").prop("disabled", true);
			jQuery("#socid").removeAttr("disabled");
			jQuery(".showforproductselector").hide();
			jQuery(".showforthirdpartyselector").show();
		}
	}
	init_selectors();
	jQuery(".radiobarcodeselect").click(function() {
		init_selectors();
	});

	function init_gendoc_button()
	{
		if (jQuery("#select_fk_barcode_type").val() > 0 && jQuery("#forbarcode").val())
		{
			jQuery("#submitformbarcodegen").removeAttr("disabled");
		}
		else
		{
			jQuery("#submitformbarcodegen").prop("disabled", true);
		}
	}
	init_gendoc_button();
	jQuery("#select_fk_barcode_type").change(function() {
		init_gendoc_button();
	});
	jQuery("#forbarcode").keyup(function() {
		init_gendoc_button()
	});
});
</script>';

// Checkbox to select from free text
print '<input id="fillmanually" type="radio" '.((!GETPOST("selectorforbarcode") || GETPOST("selectorforbarcode") == 'fillmanually') ? 'checked ' : '').'name="selectorforbarcode" value="fillmanually" class="radiobarcodeselect"><label for="fillmanually"> '.$langs->trans("FillBarCodeTypeAndValueManually").'</label>';
print '<br>';

if (!empty($user->rights->produit->lire) || !empty($user->rights->service->lire)) {
	print '<input id="fillfromproduct" type="radio" '.((GETPOST("selectorforbarcode") == 'fillfromproduct') ? 'checked ' : '').'name="selectorforbarcode" value="fillfromproduct" class="radiobarcodeselect"><label for="fillfromproduct"> '.$langs->trans("FillBarCodeTypeAndValueFromProduct").'</label>';
	print '<br>';
	print '<div class="showforproductselector">';
	$form->select_produits(GETPOST('productid', 'int'), 'productid', '', '', 0, -1, 2, '', 0, array(), 0, '1', 0, 'minwidth400imp', 1);
	print ' &nbsp; <input type="submit" class="button small" id="submitproduct" name="submitproduct" value="'.(dol_escape_htmltag($langs->trans("GetBarCode"))).'">';
	print '</div>';
}

if (!empty($user->rights->societe->lire)) {
	print '<input id="fillfromthirdparty" type="radio" '.((GETPOST("selectorforbarcode") == 'fillfromthirdparty') ? 'checked ' : '').'name="selectorforbarcode" value="fillfromthirdparty" class="radiobarcodeselect"><label for="fillfromthirdparty"> '.$langs->trans("FillBarCodeTypeAndValueFromThirdParty").'</label>';
	print '<br>';
	print '<div class="showforthirdpartyselector">';
	print $form->select_company(GETPOST('socid', 'int'), 'socid', '', 'SelectThirdParty', 0, 0, array(), 0, 'minwidth300');
	print ' &nbsp; <input type="submit" id="submitthirdparty" name="submitthirdparty" class="button showforthirdpartyselector small" value="'.(dol_escape_htmltag($langs->trans("GetBarCode"))).'">';
	print '</div>';
}

print '<br>';

if ($producttmp->id > 0) {
	print $langs->trans("BarCodeDataForProduct", '').' '.$producttmp->getNomUrl(1).'<br>';
}
if ($thirdpartytmp->id > 0) {
	print $langs->trans("BarCodeDataForThirdparty", '').' '.$thirdpartytmp->getNomUrl(1).'<br>';
}

print '<div class="tagtable">';

// Barcode type
print '	<div class="tagtr">';
print '	<div class="tagtd" style="overflow: hidden; white-space: nowrap; max-width: 300px;">';
print $langs->trans("BarcodeType").' &nbsp; ';
print '</div><div class="tagtd" style="overflow: hidden; white-space: nowrap; max-width: 300px;">';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';
$formbarcode = new FormBarCode($db);
print $formbarcode->selectBarcodeType($fk_barcode_type, 'fk_barcode_type', 1);
print '</div></div>';

// Barcode value
print '	<div class="tagtr">';
print '	<div class="tagtd" style="overflow: hidden; white-space: nowrap; max-width: 300px;">';
print $langs->trans("BarcodeValue").' &nbsp; ';
print '</div><div class="tagtd" style="overflow: hidden; white-space: nowrap; max-width: 300px;">';
print '<input size="16" type="text" name="forbarcode" id="forbarcode" value="'.$forbarcode.'">';
print '</div></div>';

/*
$barcodestickersmask=GETPOST('barcodestickersmask');
print '<br>'.$langs->trans("BarcodeStickersMask").':<br>';
print '<textarea cols="40" type="text" name="barcodestickersmask" value="'.GETPOST('barcodestickersmask').'">'.$barcodestickersmask.'</textarea>';
print '<br>';
*/

print '</div>';

print '<br><input type="submit" class="button" id="submitformbarcodegen" '.((GETPOST("selectorforbarcode") && GETPOST("selectorforbarcode")) ? '' : 'disabled ').'value="'.$langs->trans("BuildPageToPrint").'">';

print '</form>';
print '<br>';

// End of page
llxFooter();
$db->close();
