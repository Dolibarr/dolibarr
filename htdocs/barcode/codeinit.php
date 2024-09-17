<?php
/* Copyright (C) 2014-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2018  	   Ferran Marcet 		<fmarcet@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file 		htdocs/barcode/codeinit.php
 *	\ingroup    member
 *	\brief      Page to make mass init of barcode
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'members', 'errors', 'other'));

// Choice of print year or current year.
$now = dol_now();
$year = dol_print_date($now, '%Y');
$month = dol_print_date($now, '%m');
$day = dol_print_date($now, '%d');
$forbarcode = GETPOST('forbarcode');
$fk_barcode_type = GETPOST('fk_barcode_type');
$eraseallproductbarcode = GETPOST('eraseallproductbarcode');
$eraseallthirdpartybarcode = GETPOST('eraseallthirdpartybarcode');

$action = GETPOST('action', 'aZ09');

$producttmp = new Product($db);
$thirdpartytmp = new Societe($db);

$modBarCodeProduct = '';
$modBarCodeThirdparty = '';

$maxperinit = !getDolGlobalString('BARCODE_INIT_MAX') ? 1000 : $conf->global->BARCODE_INIT_MAX;

// Security check (enable the most restrictive one)
//if ($user->socid > 0) accessforbidden();
//if ($user->socid > 0) $socid = $user->socid;
if (!isModEnabled('barcode')) {
	accessforbidden('Module not enabled');
}
//restrictedArea($user, 'barcode');
if (empty($user->admin)) {
	accessforbidden('Must be admin');
}


/*
 * Actions
 */

// Define barcode template for third-party
if (getDolGlobalString('BARCODE_THIRDPARTY_ADDON_NUM')) {
	$dirbarcodenum = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);

	foreach ($dirbarcodenum as $dirroot) {
		$dir = dol_buildpath($dirroot, 0);

		$handle = @opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^mod_barcode_thirdparty_.*php$/', $file)) {
					$file = substr($file, 0, dol_strlen($file) - 4);

					try {
						dol_include_once($dirroot.$file.'.php');
					} catch (Exception $e) {
						dol_syslog($e->getMessage(), LOG_ERR);
					}

					$modBarCodeThirdparty = new $file();
					'@phan-var-force ModeleNumRefBarCode $modBarCodeThirdparty';
					break;
				}
			}
			closedir($handle);
		}
	}
}

if ($action == 'initbarcodethirdparties' && $user->hasRight('societe', 'lire')) {
	if (!is_object($modBarCodeThirdparty)) {
		$error++;
		setEventMessages($langs->trans("NoBarcodeNumberingTemplateDefined"), null, 'errors');
	}

	if (!$error) {
		$thirdpartystatic = new Societe($db);

		$db->begin();

		$nbok = 0;
		if (!empty($eraseallthirdpartybarcode)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."societe";
			$sql .= " AND entity IN (".getEntity('societe').")";
			$sql .= " SET barcode = NULL";
			$resql = $db->query($sql);
			if ($resql) {
				setEventMessages($langs->trans("AllBarcodeReset"), null, 'mesgs');
			} else {
				$error++;
				dol_print_error($db);
			}
		} else {
			$sql = "SELECT rowid";
			$sql .= " FROM ".MAIN_DB_PREFIX."societe";
			$sql .= " WHERE barcode IS NULL or barcode = ''";
			$sql .= " AND entity IN (".getEntity('societe').")";
			$sql .= $db->order("datec", "ASC");
			$sql .= $db->plimit($maxperinit);

			dol_syslog("codeinit", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);

				$i = 0;
				$nbok = $nbtry = 0;
				while ($i < min($num, $maxperinit)) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$thirdpartystatic->id = $obj->rowid;
						$nextvalue = $modBarCodeThirdparty->getNextValue($thirdpartystatic, '');

						$result = $thirdpartystatic->setValueFrom('barcode', $nextvalue, '', '', 'text', '', $user, 'THIRDPARTY_MODIFY');

						$nbtry++;
						if ($result > 0) {
							$nbok++;
						}
					}

					$i++;
				}
			} else {
				$error++;
				dol_print_error($db);
			}

			if (!$error) {
				setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
			}
		}

		if (!$error) {
			//$db->rollback();
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	$action = '';
}

// Define barcode template for products
if (getDolGlobalString('BARCODE_PRODUCT_ADDON_NUM')) {
	$dirbarcodenum = array_merge(array('/core/modules/barcode/'), $conf->modules_parts['barcode']);

	foreach ($dirbarcodenum as $dirroot) {
		$dir = dol_buildpath($dirroot, 0);

		$handle = @opendir($dir);
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				if (preg_match('/^mod_barcode_product_.*php$/', $file)) {
					$file = substr($file, 0, dol_strlen($file) - 4);

					if ($file == getDolGlobalString('BARCODE_PRODUCT_ADDON_NUM')) {
						try {
							dol_include_once($dirroot.$file.'.php');
						} catch (Exception $e) {
							dol_syslog($e->getMessage(), LOG_ERR);
						}

						$modBarCodeProduct = new $file();
						'@phan-var-force ModeleNumRefBarCode $modBarCodeProduct';
						break;
					}
				}
			}
			closedir($handle);
		}
	}
}

if ($action == 'initbarcodeproducts' && $user->hasRight('produit', 'lire')) {
	if (!is_object($modBarCodeProduct)) {
		$error++;
		setEventMessages($langs->trans("NoBarcodeNumberingTemplateDefined"), null, 'errors');
	}

	if (!$error) {
		$productstatic = new Product($db);

		$db->begin();

		$nbok = 0;
		if (!empty($eraseallproductbarcode)) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."product";
			$sql .= " SET barcode = NULL";
			$sql .= " WHERE entity IN (".getEntity('product').")";
			$resql = $db->query($sql);
			if ($resql) {
				setEventMessages($langs->trans("AllBarcodeReset"), null, 'mesgs');
			} else {
				$error++;
				dol_print_error($db);
			}
		} else {
			$sql = "SELECT rowid, ref, fk_product_type";
			$sql .= " FROM ".MAIN_DB_PREFIX."product";
			$sql .= " WHERE barcode IS NULL or barcode = ''";
			$sql .= " AND entity IN (".getEntity('product').")";
			$sql .= $db->order("datec", "ASC");
			$sql .= $db->plimit($maxperinit);

			dol_syslog("codeinit", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {
				$num = $db->num_rows($resql);

				$i = 0;
				$nbok = $nbtry = 0;
				while ($i < min($num, $maxperinit)) {
					$obj = $db->fetch_object($resql);
					if ($obj) {
						$productstatic->id = $obj->rowid;
						$productstatic->ref = $obj->ref;
						$productstatic->type = $obj->fk_product_type;
						$nextvalue = $modBarCodeProduct->getNextValue($productstatic, '');

						//print 'Set value '.$nextvalue.' to product '.$productstatic->id." ".$productstatic->ref." ".$productstatic->type."<br>\n";
						$result = $productstatic->setValueFrom('barcode', $nextvalue, '', '', 'text', '', $user, 'PRODUCT_MODIFY');

						$nbtry++;
						if ($result > 0) {
							$nbok++;
						}
					}

					$i++;
				}
			} else {
				$error++;
				dol_print_error($db);
			}

			if (!$error) {
				setEventMessages($langs->trans("RecordsModified", $nbok), null, 'mesgs');
			}
		}

		if (!$error) {
			//$db->rollback();
			$db->commit();
		} else {
			$db->rollback();
		}
	}

	$action = '';
}


/*
 * View
 */

llxHeader('', $langs->trans("MassBarcodeInit"), '', '', 0, 0, '', '', '', 'mod-barcode page-codeinit');

print load_fiche_titre($langs->trans("MassBarcodeInit"), '', 'title_setup.png');
print '<br>';

print '<span class="opacitymedium">'.$langs->trans("MassBarcodeInitDesc").'</span><br>';
print '<br>';

//print img_picto('','puce').' '.$langs->trans("PrintsheetForOneBarCode").'<br>';
//print '<br>';

print '<br>';



// Example 1 : Adding jquery code
print '<script type="text/javascript">
function confirm_erase() {
	return confirm("'.dol_escape_js($langs->trans("ConfirmEraseAllCurrentBarCode")).'");
}
</script>';


// For thirdparty
if (isModEnabled('societe')) {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="mode" value="label">';
	print '<input type="hidden" name="action" value="initbarcodethirdparties">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	$nbthirdpartyno = $nbthirdpartytotal = 0;

	print '<div class="divsection">';

	print load_fiche_titre($langs->trans("BarcodeInitForThirdparties"), '', 'company');

	$sql = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."societe where barcode IS NULL or barcode = ''";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$nbthirdpartyno = $obj->nb;
	} else {
		dol_print_error($db);
	}

	$sql = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."societe";
	$sql .= " WHERE entity IN (".getEntity('societe').")";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$nbthirdpartytotal = $obj->nb;
	} else {
		dol_print_error($db);
	}

	print $langs->trans("CurrentlyNWithoutBarCode", $nbthirdpartyno, $nbthirdpartytotal, $langs->transnoentitiesnoconv("ThirdParties"))."\n";

	$disabledthirdparty = $disabledthirdparty1 = 0;

	if (is_object($modBarCodeThirdparty)) {
		print '<br>'.$langs->trans("BarCodeNumberManager").": ";
		$objthirdparty = new Societe($db);
		print '<b>'.(isset($modBarCodeThirdparty->name) ? $modBarCodeThirdparty->name : $modBarCodeThirdparty->nom).'</b> - '.$langs->trans("NextValue").': <b>'.$modBarCodeThirdparty->getNextValue($objthirdparty).'</b><br>';
		$disabledthirdparty = 0;
		print '<br>';
	} else {
		$disabledthirdparty = 1;
		$titleno = $langs->trans("NoBarcodeNumberingTemplateDefined");
		print '<div class="warning">'.$langs->trans("NoBarcodeNumberingTemplateDefined");
		print '<br><a href="'.DOL_URL_ROOT.'/admin/barcode.php">'.$langs->trans("ToGenerateCodeDefineAutomaticRuleFirst").'</a>';
		print '</div>';
	}
	if (empty($nbthirdpartyno)) {
		$disabledthirdparty1 = 1;
	}

	$moretagsthirdparty1 = (($disabledthirdparty || $disabledthirdparty1) ? ' disabled title="'.dol_escape_htmltag($titleno).'"' : '');
	print '<input class="button button-add" type="submit" id="submitformbarcodethirdpartygen" value="'.$langs->trans("InitEmptyBarCode", $nbthirdpartyno).'"'.$moretagsthirdparty1.'>';
	$moretagsthirdparty2 = (($nbthirdpartyno == $nbthirdpartytotal) ? ' disabled' : '');
	print ' &nbsp; ';
	print '<input type="submit" class="button butActionDelete" name="eraseallthirdpartybarcode" id="eraseallthirdpartybarcode" value="'.$langs->trans("EraseAllCurrentBarCode").'"'.$moretagsthirdparty2.' onClick="return confirm_erase();">';
	print '<br><br>';
	print '</div>';
	print '<br>';
	print '</form>';
}


// For products
if (isModEnabled('product') || isModEnabled('service')) {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="mode" value="label">';
	print '<input type="hidden" name="action" value="initbarcodeproducts">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	$nbproductno = $nbproducttotal = 0;

	print '<div class="divsection">';

	print load_fiche_titre($langs->trans("BarcodeInitForProductsOrServices"), '', 'product');

	$sql = "SELECT count(rowid) as nb, fk_product_type, datec";
	$sql .= " FROM ".MAIN_DB_PREFIX."product";
	$sql .= " WHERE barcode IS NULL OR barcode = ''";
	$sql .= " AND entity IN (".getEntity('product').")";
	$sql .= " GROUP BY fk_product_type, datec";
	$sql .= " ORDER BY datec";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);

		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			$nbproductno += $obj->nb;

			$i++;
		}
	} else {
		dol_print_error($db);
	}

	$sql = "SELECT count(rowid) as nb FROM ".MAIN_DB_PREFIX."product";
	$sql .= " WHERE entity IN (".getEntity('product').")";
	$resql = $db->query($sql);
	if ($resql) {
		$obj = $db->fetch_object($resql);
		$nbproducttotal = $obj->nb;
	} else {
		dol_print_error($db);
	}

	print $langs->trans("CurrentlyNWithoutBarCode", $nbproductno, $nbproducttotal, $langs->transnoentitiesnoconv("ProductsOrServices"))."\n";

	$disabledproduct = $disabledproduct1 = 0;

	if (is_object($modBarCodeProduct)) {
		print '<br>'.$langs->trans("BarCodeNumberManager").": ";
		$objproduct = new Product($db);
		print '<b>'.(isset($modBarCodeProduct->name) ? $modBarCodeProduct->name : $modBarCodeProduct->nom).'</b> - '.$langs->trans("NextValue").': <b>'.$modBarCodeProduct->getNextValue($objproduct).'</b><br>';
		$disabledproduct = 0;
		print '<br>';
	} else {
		$disabledproduct = 1;
		$titleno = $langs->trans("NoBarcodeNumberingTemplateDefined");
		print '<br><div class="warning">'.$langs->trans("NoBarcodeNumberingTemplateDefined");
		print '<br><a href="'.DOL_URL_ROOT.'/admin/barcode.php">'.$langs->trans("ToGenerateCodeDefineAutomaticRuleFirst").'</a>';
		print '</div>';
	}
	if (empty($nbproductno)) {
		$disabledproduct1 = 1;
	}

	//print '<input type="checkbox" id="erasealreadyset" name="erasealreadyset"> '.$langs->trans("ResetBarcodeForAllRecords").'<br>';
	$moretagsproduct1 = (($disabledproduct || $disabledproduct1) ? ' disabled title="'.dol_escape_htmltag($titleno).'"' : '');
	print '<input type="submit" class="button" name="submitformbarcodeproductgen" id="submitformbarcodeproductgen" value="'.$langs->trans("InitEmptyBarCode", min($maxperinit, $nbproductno)).'"'.$moretagsproduct1.'>';
	$moretagsproduct2 = (($nbproductno == $nbproducttotal) ? ' disabled' : '');
	print ' &nbsp; ';
	print '<input type="submit" class="button butActionDelete" name="eraseallproductbarcode" id="eraseallproductbarcode" value="'.$langs->trans("EraseAllCurrentBarCode").'"'.$moretagsproduct2.' onClick="return confirm_erase();">';
	print '<br><br>';
	print '</div>';
	print '<br>';
	print '</form>';
}


print '<div class="divsection">';

print load_fiche_titre($langs->trans("BarCodePrintsheet"), '', 'generic');
print $langs->trans("ClickHereToGoTo").' : <a href="'.DOL_URL_ROOT.'/barcode/printsheet.php">'.$langs->trans("BarCodePrintsheet").'</a>';
print '<br>'."\n";

print '<br>';

print '</div>';


// End of page
llxFooter();
$db->close();
