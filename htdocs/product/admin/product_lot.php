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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/productlot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "products", "productbatch"));

// Security check
if (!$user->admin || (empty($conf->productbatch->enabled)))
	accessforbidden();

$action = GETPOST('action', 'alpha');
$value = GETPOST('value', 'alpha');

$error = 0;


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMaskLot') {
	$maskconstbatch = GETPOST('maskconstLot', 'alpha');
	$maskbatch = GETPOST('maskLot', 'alpha');

	if ($maskconstbatch) {
		$res = dolibarr_set_const($db, $maskconstbatch, $maskbatch, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) $error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'updateMaskSN') {
	$maskconstbatch = GETPOST('maskconstSN', 'alpha');
	$maskbatch = GETPOST('maskSN', 'alpha');

	if ($maskconstbatch) {
		$res = dolibarr_set_const($db, $maskconstbatch, $maskbatch, 'chaine', 0, '', $conf->entity);
		if ($res <= 0) $error++;
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
	if ($value == '1' && $conf->global->PRODUCTBATCH_LOT_ADDONS !== 'mod_lot_advanced') {
		dolibarr_set_const($db, "PRODUCTBATCH_LOT_ADDON", 'mod_lot_advanced', 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'setmaskssn') {
	dolibarr_set_const($db, "PRODUCTBATCH_SN_USE_PRODUCT_MASKS", $value, 'bool', 0, '', $conf->entity);
	if ($value == '1' && $conf->global->PRODUCTBATCH_SN_ADDONS !== 'mod_sn_advanced') {
		dolibarr_set_const($db, "PRODUCTBATCH_SN_ADDON", 'mod_sn_advanced', 'chaine', 0, '', $conf->entity);
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


if ($conf->global->MAIN_FEATURES_LEVEL < 2) {
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
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
							print $module->info();
							print '</td>';

							// Show example of numbering model
							print '<td class="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
							elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
							else print $tmp;
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
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
										$nextval = $langs->trans($nextval);
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
						if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
						if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

						if ($module->isEnabled()) {
							print '<tr class="oddeven"><td>'.$module->name."</td><td>\n";
							print $module->info();
							print '</td>';

							// Show example of numbering model
							print '<td class="nowrap">';
							$tmp = $module->getExample();
							if (preg_match('/^Error/', $tmp)) print '<div class="error">'.$langs->trans($tmp).'</div>';
							elseif ($tmp == 'NotConfigured') print $langs->trans($tmp);
							else print $tmp;
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
									if (preg_match('/^Error/', $nextval) || $nextval == 'NotConfigured')
										$nextval = $langs->trans($nextval);
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

// End of page
llxFooter();
$db->close();
