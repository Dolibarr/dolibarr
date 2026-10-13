<?php
/* Copyright (C) 2021		Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France         <frederic.france@free.fr>
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
 *  \file	   	htdocs/product/admin/product_lot.php
 *  \ingroup	product
 *  \brief	  	Setup page of product lot module
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "products", "productbatch"));

// Security check
if (!$user->admin || (!isModEnabled('productbatch'))) {
	accessforbidden();
}

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'product_batch';

$error = 0;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMaskLot') {
	$maskconstbatch = GETPOST('maskconstLot', 'aZ09');
	$maskbatch = GETPOST('maskLot', 'alpha');

	if ($maskconstbatch && preg_match('/_MASK$/', $maskconstbatch)) {
		$res = dolibarr_set_const($db, $maskconstbatch, $maskbatch, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'updateMaskSN') {
	$maskconstbatch = GETPOST('maskconstSN', 'aZ09');
	$maskbatch = GETPOST('maskSN', 'alpha');

	if ($maskconstbatch && preg_match('/_MASK$/', $maskconstbatch)) {
		$res = dolibarr_set_const($db, $maskconstbatch, $maskbatch, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'setmodlot') {
	dolibarr_set_const($db, "PRODUCTBATCH_LOT_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setmodsn') {
	dolibarr_set_const($db, "PRODUCTBATCH_SN_ADDON", $value, 'chaine', 0, '', $conf->entity);
} elseif ($action == 'setmaskslot') {
	dolibarr_set_const($db, "PRODUCTBATCH_LOT_USE_PRODUCT_MASKS", $value, 'bool', 0, '', $conf->entity);
	if ($value == '1' && getDolGlobalString('PRODUCTBATCH_LOT_ADDONS') !== 'mod_lot_advanced') {
		dolibarr_set_const($db, "PRODUCTBATCH_LOT_ADDON", 'mod_lot_advanced', 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'setmaskssn') {
	dolibarr_set_const($db, "PRODUCTBATCH_SN_USE_PRODUCT_MASKS", $value, 'bool', 0, '', $conf->entity);
	if ($value == '1' && getDolGlobalString('PRODUCTBATCH_SN_ADDONS') !== 'mod_sn_advanced') {
		dolibarr_set_const($db, "PRODUCTBATCH_SN_ADDON", 'mod_sn_advanced', 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		if (getDolGlobalString('FACTURE_ADDON_PDF') == "$value") {
			dolibarr_del_const($db, 'FACTURE_ADDON_PDF', $conf->entity);
		}
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');

	$product_batch = new Productlot($db);
	$product_batch->initAsSpecimen();

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir . "core/modules/product_batch/doc/pdf_" . $modele . ".modules.php", 0);
		if (file_exists($file)) {
			$classname = "pdf_" . $modele;
			break;
		}
	}

	if ($classname !== '') {
		require_once $file;

		$module = new $classname($db);

		'@phan-var-force ModelePDFProductBatch $module';

		if ($module->write_file($product_batch, $langs) > 0) {
			header("Location: " . DOL_URL_ROOT . "/document.php?modulepart=product_batch&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, $module->errors, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setdoc') {
	// Set default model
	if (dolibarr_set_const($db, "PRODUCT_BATCH_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity)) {
		// The constant that was read before the new set
		// so we go through a variable to get a consistent display
		$conf->global->PRODUCT_BATCH_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}

/*
 * View
 */

$form = new Form($db);

$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

llxHeader("", $langs->trans("ProductLotSetup"), '', '', 0, 0, '', '', '', 'mod-product page-admin_product_lot');

$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
print load_fiche_titre($langs->trans("ProductLotSetup"), $linkback, 'title_setup');

$head = product_lot_admin_prepare_head();

print dol_get_fiche_head($head, 'settings', $langs->trans("Batch"), -1, 'lot');


if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	/*
	 * Lot Numbering models
	 */

	$batch = new Productlot($db);
	$batch->initAsSpecimen();

	printNumberingModuleList('product_batch', 'mod_lot_', 'PRODUCTBATCH_LOT_ADDON', $langs->trans("BatchLotNumberingModules"), $batch, 'setmodlot');

	print '<br>';

	/*
	 * Serials Numbering models
	 */

	$batch = new Productlot($db);
	$batch->initAsSpecimen();

	printNumberingModuleList('product_batch', 'mod_sn_', 'PRODUCTBATCH_SN_ADDON', $langs->trans("BatchSerialNumberingModules"), $batch, 'setmodsn');

	print '<br>';
}

// Module to build doc
$def = array();
// TODO Replace with $def = getListOfModels($db, $type);
$sql = "SELECT nom";
$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
$sql .= " WHERE type = '" . $db->escape($type) . "'";
$sql .= " AND entity = " . ((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		if (is_array($array)) {
			array_push($def, $array[0]);
		}
		$i++;
	}
} else {
	dol_print_error($db);
}


if (!empty($def)) {
	print '<br>';

	printDocumentModelList($type, 'product_batch', 'PRODUCT_BATCH_ADDON_PDF', $langs->trans("ProductBatchDocumentTemplates"), array(
		'Logo' => 'option_logo',
		'MultiLanguage' => 'option_multilang',
	));
}


if (empty($def) && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
	// The feature to define the numbering module of lot or serial is no enabled because it is not used anywhere in Dolibarr code: You can set it
	// but the numbering module is not used.
	// TODO Use it on lot creation page, when you create a lot and when the lot number is kept empty to define the lot according
	// to the selected product.
	print $langs->trans("NothingToSetup");
}


// End of page
llxFooter();
$db->close();
