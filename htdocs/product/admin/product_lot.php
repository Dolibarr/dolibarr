<?php
/* Copyright (C) 2021		Christophe Battarel  <christophe.battarel@altairis.fr>
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
 *  \file	   htdocs/product/admin/product_lot.php
 *  \ingroup	produit
 *  \brief	  Setup page of product lot module
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "products", "productbatch"));

// Security check
if (!$user->admin || (empty($conf->productbatch->enabled))) {
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
		if ($conf->global->FACTURE_ADDON_PDF == "$value") {
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
	$filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir . "core/modules/product_batch/doc/pdf_" . $modele . ".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_" . $modele;
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

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
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
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

llxHeader("", $langs->trans("ProductLotSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ProductLotSetup"), $linkback, 'title_setup');

$head = product_lot_admin_prepare_head();

print dol_get_fiche_head($head, 'settings', $langs->trans("Batch"), -1, 'lot');


if (getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
	// The feature to define the numbering module of lot or serial is no enabled bcause it is not used anywhere in Dolibarr code: You can set it
	// but the numbering module is not used.
	// TODO Use it on lot creation page, when you create a lot and when the lot number is kept empty to define the lot according
	// to the selected product.
	print $langs->trans("NothingToSetup");
} else {
	/*
	 * Lot Numbering models
	 */

	print load_fiche_titre($langs->trans("BatchLotNumberingModules"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td class="nowrap">'.$langs->trans("Example").'</td>';
	print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
	print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
	print '</tr>'."\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/product_batch/");

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (substr($file, 0, 8) == 'mod_lot_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
						$file = substr($file, 0, dol_strlen($file) - 4);

						require_once $dir.$file.'.php';

						$module = new $file($db);

						// Show modules according to features level
						if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
							continue;
						}
						if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
							continue;
						}

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
							print $module->info($langs);
							print '</td>';

							// Show example of numbering model
							print '<td class="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								print '<div class="error">'.$langs->trans($tmp).'</div>';
							} elseif ($tmp == 'NotConfigured') {
								print $langs->trans($tmp);
							} else {
								print $tmp;
							}
							print '</td>'."\n";

							print '<td class="center">';
							if ($conf->global->PRODUCTBATCH_LOT_ADDON == $file) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmodlot&token='.newToken().'&value='.urlencode($file).'">';
								print img_picto($langs->trans("Disabled"), 'switch_off');
								print '</a>';
							}
							print '</td>';

							$batch = new Productlot($db);
							$batch->initAsSpecimen();

							// Info
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($mysoc, $batch);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= ''.$langs->trans("NextValue").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
										$nextval = $langs->trans($nextval);
									}
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}

							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);
							print '</td>';

							print "</tr>\n";
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print "</table><br>\n";


	/*
	 * Serials Numbering models
	 */

	print load_fiche_titre($langs->trans("BatchSerialNumberingModules"), '', '');

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Name").'</td>';
	print '<td>'.$langs->trans("Description").'</td>';
	print '<td class="nowrap">'.$langs->trans("Example").'</td>';
	print '<td class="center" width="60">'.$langs->trans("Status").'</td>';
	print '<td class="center" width="16">'.$langs->trans("ShortInfo").'</td>';
	print '</tr>'."\n";

	clearstatcache();

	foreach ($dirmodels as $reldir) {
		$dir = dol_buildpath($reldir."core/modules/product_batch/");

		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (substr($file, 0, 7) == 'mod_sn_' && substr($file, dol_strlen($file) - 3, 3) == 'php') {
						$file = substr($file, 0, dol_strlen($file) - 4);

						require_once $dir.$file.'.php';

						$module = new $file($db);

						// Show modules according to features level
						if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
							continue;
						}
						if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
							continue;
						}

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
							print $module->info($langs);
							print '</td>';

							// Show example of numbering model
							print '<td class="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) {
								print '<div class="error">'.$langs->trans($tmp).'</div>';
							} elseif ($tmp == 'NotConfigured') {
								print $langs->trans($tmp);
							} else {
								print $tmp;
							}
							print '</td>'."\n";

							print '<td class="center">';
							if ($conf->global->PRODUCTBATCH_SN_ADDON == $file) {
								print img_picto($langs->trans("Activated"), 'switch_on');
							} else {
								print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=setmodsn&token='.newToken().'&value='.urlencode($file).'">';
								print img_picto($langs->trans("Disabled"), 'switch_off');
								print '</a>';
							}
							print '</td>';

							$batch = new Productlot($db);
							$batch->initAsSpecimen();

							// Info
							$htmltooltip = '';
							$htmltooltip .= ''.$langs->trans("Version").': <b>'.$module->getVersion().'</b><br>';
							$nextval = $module->getNextValue($mysoc, $batch);
							if ("$nextval" != $langs->trans("NotAvailable")) {  // Keep " on nextval
								$htmltooltip .= ''.$langs->trans("NextValue").': ';
								if ($nextval) {
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured') {
										$nextval = $langs->trans($nextval);
									}
									$htmltooltip .= $nextval.'<br>';
								} else {
									$htmltooltip .= $langs->trans($module->error).'<br>';
								}
							}

							print '<td class="center">';
							print $form->textwithpicto('', $htmltooltip, 1, 0);
							print '</td>';

							print "</tr>\n";
						}
					}
				}
				closedir($handle);
			}
		}
	}

	print "</table><br>\n";
}

// Module to build doc
$def = array();
$sql = "SELECT nom";
$sql .= " FROM " . MAIN_DB_PREFIX . "document_model";
$sql .= " WHERE type = '" . $db->escape($type) . "'";
$sql .= " AND entity = " . $conf->entity;
$resql = $db->query($sql);
if ($resql) {
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows) {
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
} else {
	dol_print_error($db);
}

print '<br>';

print load_fiche_titre($langs->trans("ProductBatchDocumentTemplates"), '', '');

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>' . $langs->trans("Name") . '</td>';
print '<td>' . $langs->trans("Description") . '</td>';
print '<td class="center" width="60">' . $langs->trans("Status") . "</td>\n";
print '<td class="center" width="60">' . $langs->trans("Default") . "</td>\n";
print '<td class="center"></td>';
print '<td class="center" width="80">' . $langs->trans("Preview") . '</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir) {
	foreach (array('', '/doc') as $valdir) {
		$dir = dol_buildpath($reldir . "core/modules/product_batch" . $valdir);
		if (is_dir($dir)) {
			$handle = opendir($dir);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file) {
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file)) {
						if (file_exists($dir . '/' . $file)) {
							$name = substr($file, 4, dol_strlen($file) - 16);
							$classname = substr($file, 0, dol_strlen($file) - 12);

							require_once $dir . '/' . $file;
							$module = new $classname($db);

							$modulequalified = 1;
							if ($module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
								$modulequalified = 0;
							}
							if ($module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
								$modulequalified = 0;
							}

							if ($modulequalified) {
								print '<tr class="oddeven"><td width="100">';
								print(empty($module->name) ? $name : $module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) {
									print $module->info($langs);
								} else {
									print $module->description;
								}
								print '</td>';

								// Active
								if (in_array($name, $def)) {
									print '<td class="center">' . "\n";
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=del&token=' . newToken() . '&value=' . urlencode($name) . '">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">' . "\n";
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=set&token=' . newToken() . '&value=' . urlencode($name) . '&scan_dir=' . urlencode($module->scandir) . '&label=' . urlencode($module->name) . '">' . img_picto($langs->trans("Disabled"), 'switch_off') . '</a>';
									print "</td>";
								}

								// Defaut
								print '<td class="center">';
								if (getDolGlobalString('PRODUCT_BATCH_ADDON_PDF') == $name) {
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=setdoc&token=' . newToken() . '&value=' . urlencode($name) . '&scan_dir=' . urlencode($module->scandir) . '&label=' . urlencode($module->name) . '" alt="' . $langs->trans("Default") . '">' . img_picto($langs->trans("Disabled"), 'off') . '</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = '' . $langs->trans("Name") . ': ' . $module->name;
								$htmltooltip .= '<br>' . $langs->trans("Type") . ': ' . ($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf') {
									$htmltooltip .= '<br>' . $langs->trans("Width") . '/' . $langs->trans("Height") . ': ' . $module->page_largeur . '/' . $module->page_hauteur;
								}
								$htmltooltip .= '<br><br><u>' . $langs->trans("FeaturesSupported") . ':</u>';
								$htmltooltip .= '<br>' . $langs->trans("Logo") . ': ' . yn($module->option_logo, 1, 1);
								$htmltooltip .= '<br>' . $langs->trans("MultiLanguage") . ': ' . yn($module->option_multilang, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf') {
									print '<a href="' . $_SERVER["PHP_SELF"] . '?action=specimen&module=' . $name . '">' . img_object($langs->trans("Preview"), 'contract') . '</a>';
								} else {
									print img_object($langs->trans("PreviewNotAvailable"), 'generic');
								}
								print '</td>';

								print "</tr>\n";
							}
						}
					}
				}
			}
		}
	}
}

print '</table>';
print '</div>';

// End of page
llxFooter();
$db->close();
