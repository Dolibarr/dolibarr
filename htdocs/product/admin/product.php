<?php
/* Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Auguria SARL         <info@auguria.org>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2012 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2016      Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2016	   Ferran Marcet		<fmarcet@2byte.es>
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
 *  \file       htdocs/product/admin/product.php
 *  \ingroup    produit
 *  \brief      Setup page of product module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formbarcode.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "products"));

// Security check
if (!$user->admin || (empty($conf->product->enabled) && empty($conf->service->enabled)))
	accessforbidden();

$action = GETPOST('action', 'aZ09');
$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'product';

// Pricing Rules
$select_pricing_rules = array(
	'PRODUCT_PRICE_UNIQ'=>$langs->trans('PriceCatalogue'), // Unique price
	'PRODUIT_MULTIPRICES'=>$langs->trans('MultiPricesAbility'), // Several prices according to a customer level
	'PRODUIT_CUSTOMER_PRICES'=>$langs->trans('PriceByCustomer'), // Different price for each customer
);
$keyforparam = 'PRODUIT_CUSTOMER_PRICES_BY_QTY';
if ($conf->global->MAIN_FEATURES_LEVEL >= 1 || !empty($conf->global->$keyforparam)) $select_pricing_rules['PRODUIT_CUSTOMER_PRICES_BY_QTY'] = $langs->trans('PriceByQuantity').' ('.$langs->trans("VersionExperimental").')'; // TODO If this is enabled, price must be hidden when price by qty is enabled, also price for quantity must be used when adding product into order/propal/invoice
$keyforparam = 'PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES';
if ($conf->global->MAIN_FEATURES_LEVEL >= 2 || !empty($conf->global->$keyforparam)) $select_pricing_rules['PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES'] = $langs->trans('MultiPricesAbility').'+'.$langs->trans('PriceByQuantity').' ('.$langs->trans("VersionExperimental").')';

// Clean param
if (!empty($conf->global->PRODUIT_MULTIPRICES) && empty($conf->global->PRODUIT_MULTIPRICES_LIMIT)) {
	dolibarr_set_const($db, 'PRODUIT_MULTIPRICES_LIMIT', 5, 'chaine', 0, '', $conf->entity);
}

$error = 0;


/*
 * Actions
 */

$nomessageinsetmoduleoptions = 1;
include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'setcodeproduct')
{
	if (dolibarr_set_const($db, "PRODUCT_CODEPRODUCT_ADDON", $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
}

if ($action == 'other' && GETPOST('value_PRODUIT_LIMIT_SIZE') >= 0)
{
	$res = dolibarr_set_const($db, "PRODUIT_LIMIT_SIZE", GETPOST('value_PRODUIT_LIMIT_SIZE'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
}
if ($action == 'other' && GETPOST('value_PRODUIT_MULTIPRICES_LIMIT') > 0)
{
	$res = dolibarr_set_const($db, "PRODUIT_MULTIPRICES_LIMIT", GETPOST('value_PRODUIT_MULTIPRICES_LIMIT'), 'chaine', 0, '', $conf->entity);
	if (!($res > 0)) $error++;
}
if ($action == 'other')
{
	$princingrules = GETPOST('princingrule', 'alpha');
	foreach ($select_pricing_rules as $rule => $label) // Loop on each possible mode
	{
		if ($rule == $princingrules) // We are on selected rule, we enable it
		{
			if ($princingrules == 'PRODUCT_PRICE_UNIQ') // For this case, we disable entries manually
			{
				$res = dolibarr_set_const($db, 'PRODUIT_MULTIPRICES', 0, 'chaine', 0, '', $conf->entity);
				$res = dolibarr_set_const($db, 'PRODUIT_CUSTOMER_PRICES_BY_QTY', 0, 'chaine', 0, '', $conf->entity);
				$res = dolibarr_set_const($db, 'PRODUIT_CUSTOMER_PRICES', 0, 'chaine', 0, '', $conf->entity);
				dolibarr_set_const($db, 'PRODUCT_PRICE_UNIQ', 1, 'chaine', 0, '', $conf->entity);
			} else {
				$multirule = explode('&', $princingrules);
				foreach ($multirule as $rulesselected)
				{
					$res = dolibarr_set_const($db, $rulesselected, 1, 'chaine', 0, '', $conf->entity);
				}
			}
		} else // We clear this mode
		{
			if (strpos($rule, '&') === false) {
				$res = dolibarr_set_const($db, $rule, 0, 'chaine', 0, '', $conf->entity);
			}
		}
	}

	$value = GETPOST('price_base_type', 'alpha');
	$res = dolibarr_set_const($db, "PRODUCT_PRICE_BASE_TYPE", $value, 'chaine', 0, '', $conf->entity);

	/*$value = GETPOST('PRODUIT_SOUSPRODUITS', 'alpha');
	$res = dolibarr_set_const($db, "PRODUIT_SOUSPRODUITS", $value, 'chaine', 0, '', $conf->entity);*/

	$value = GETPOST('activate_viewProdDescInForm', 'alpha');
	$res = dolibarr_set_const($db, "PRODUIT_DESC_IN_FORM", $value, 'chaine', 0, '', $conf->entity);

	$value = GETPOST('activate_viewProdTextsInThirdpartyLanguage', 'alpha');
	$res = dolibarr_set_const($db, "PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE", $value, 'chaine', 0, '', $conf->entity);

	$value = GETPOST('activate_mergePropalProductCard', 'alpha');
	$res = dolibarr_set_const($db, "PRODUIT_PDF_MERGE_PROPAL", $value, 'chaine', 0, '', $conf->entity);

	$value = GETPOST('activate_usesearchtoselectproduct', 'alpha');
	$res = dolibarr_set_const($db, "PRODUIT_USE_SEARCH_TO_SELECT", $value, 'chaine', 0, '', $conf->entity);

	$value = GETPOST('activate_useProdFournDesc', 'alpha');
	$res = dolibarr_set_const($db, "PRODUIT_FOURN_TEXTS", $value, 'chaine', 0, '', $conf->entity);

	if ($value) {
		$sql_test = "SELECT count(desc_fourn) as cpt FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE 1";
		$resql = $db->query($sql_test);
		if (!$resql && $db->lasterrno == 'DB_ERROR_NOSUCHFIELD') // if the field does not exist, we create it
		{
			$sql_new = "ALTER TABLE ".MAIN_DB_PREFIX."product_fournisseur_price ADD COLUMN desc_fourn text";
			$resql_new = $db->query($sql_new);
		}
	}

	$value = GETPOST('activate_useProdSupplierPackaging', 'alpha');
	$res = dolibarr_set_const($db, "PRODUCT_USE_SUPPLIER_PACKAGING", $value, 'chaine', 0, '', $conf->entity);
	if ($value) {
		$sql_test = "SELECT count(packaging) as cpt FROM ".MAIN_DB_PREFIX."product_fournisseur_price WHERE 1";
		$resql = $db->query($sql_test);
		if (!$resql && $db->lasterrno == 'DB_ERROR_NOSUCHFIELD') // if the field does not exist, we create it
		{
			$sql_new = "ALTER TABLE ".MAIN_DB_PREFIX."product_fournisseur_price ADD COLUMN packaging double(24,8) DEFAULT 1";
			$resql_new = $db->query($sql_new);
		}
	}
}

if ($action == 'specimen') // For products
{
	$modele = GETPOST('module', 'alpha');

	$product = new Product($db);
	$product->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir)
	{
		$file = dol_buildpath($reldir."core/modules/product/doc/pdf_".$modele.".modules.php", 0);
		if (file_exists($file))
		{
			$filefound = 1;
			$classname = "pdf_".$modele;
			break;
		}
	}

	if ($filefound)
	{
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($product, $langs, '') > 0)
		{
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=product&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($obj->error, $obj->errors, 'errors');
			dol_syslog($obj->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
}

// Activate a model
if ($action == 'set')
{
	$ret = addDocumentModel($value, $type, $label, $scandir);
}

if ($action == 'del')
{
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		if ($conf->global->PRODUCT_ADDON_PDF == "$value") dolibarr_del_const($db, 'PRODUCT_ADDON_PDF', $conf->entity);
	}
}

// Set default model
if ($action == 'setdoc')
{
	if (dolibarr_set_const($db, "PRODUCT_ADDON_PDF", $value, 'chaine', 0, '', $conf->entity))
	{
		// La constante qui a ete lue en avant du nouveau set
		// on passe donc par une variable pour avoir un affichage coherent
		$conf->global->PRODUCT_ADDON_PDF = $value;
	}

	// On active le modele
	$ret = delDocumentModel($value, $type);
	if ($ret > 0)
	{
		$ret = addDocumentModel($value, $type, $label, $scandir);
	}
}


if ($action == 'set')
{
	$const = "PRODUCT_SPECIAL_".strtoupper(GETPOST('spe', 'alpha'));
	$value = GETPOST('value');
	if (GETPOST('value', 'alpha')) $res = dolibarr_set_const($db, $const, $value, 'chaine', 0, '', $conf->entity);
	else $res = dolibarr_del_const($db, $const, $conf->entity);
	if (!($res > 0)) $error++;
}

//if ($action == 'other')
//{
//    $value = GETPOST('activate_units', 'alpha');
//    $res = dolibarr_set_const($db, "PRODUCT_USE_UNITS", $value, 'chaine', 0, '', $conf->entity);
//	if (! $res > 0) $error++;
//}

if ($action)
{
	if (!$error)
	{
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("SetupNotError"), null, 'errors');
	}
}

/*
 * View
 */

$formbarcode = new FormBarCode($db);

$title = $langs->trans('ProductServiceSetup');
$tab = $langs->trans("ProductsAndServices");
if (empty($conf->product->enabled))
{
	$title = $langs->trans('ServiceSetup');
	$tab = $langs->trans('Services');
} elseif (empty($conf->service->enabled))
{
	$title = $langs->trans('ProductSetup');
	$tab = $langs->trans('Products');
}

llxHeader('', $title);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = product_admin_prepare_head();
print dol_get_fiche_head($head, 'general', $tab, -1, 'product');

$form = new Form($db);

// Module to manage product / services code
$dirproduct = array('/core/modules/product/');
$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);

print load_fiche_titre($langs->trans("ProductCodeChecker"), '', '');

print '<table class="noborder centpercent">'."\n";
print '<tr class="liste_titre">'."\n";
print '  <td>'.$langs->trans("Name").'</td>';
print '  <td>'.$langs->trans("Description").'</td>';
print '  <td>'.$langs->trans("Example").'</td>';
print '  <td class="center" width="80">'.$langs->trans("Status").'</td>';
print '  <td class="center" width="60">'.$langs->trans("ShortInfo").'</td>';
print "</tr>\n";

foreach ($dirproduct as $dirroot)
{
	$dir = dol_buildpath($dirroot, 0);

	$handle = @opendir($dir);
	if (is_resource($handle))
	{
		// Loop on each module find in opened directory
		while (($file = readdir($handle)) !== false)
		{
			if (substr($file, 0, 16) == 'mod_codeproduct_' && substr($file, -3) == 'php')
			{
				$file = substr($file, 0, dol_strlen($file) - 4);

				try {
					dol_include_once($dirroot.$file.'.php');
				} catch (Exception $e)
				{
					dol_syslog($e->getMessage(), LOG_ERR);
				}

				$modCodeProduct = new $file;

				// Show modules according to features level
				if ($modCodeProduct->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) continue;
				if ($modCodeProduct->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) continue;

				print '<tr class="oddeven">'."\n";
				print '<td width="140">'.$modCodeProduct->name.'</td>'."\n";
				print '<td>'.$modCodeProduct->info($langs).'</td>'."\n";
				print '<td class="nowrap">'.$modCodeProduct->getExample($langs).'</td>'."\n";

				if (!empty($conf->global->PRODUCT_CODEPRODUCT_ADDON) && $conf->global->PRODUCT_CODEPRODUCT_ADDON == $file)
				{
					print '<td class="center">'."\n";
					print img_picto($langs->trans("Activated"), 'switch_on');
					print "</td>\n";
				} else {
					$disabled = false;
					if (!empty($conf->multicompany->enabled) && (is_object($mc) && !empty($mc->sharings['referent']) && $mc->sharings['referent'] == $conf->entity) ? false : true);
					print '<td class="center">';
					if (!$disabled) print '<a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setcodeproduct&token='.newToken().'&value='.$file.'">';
					print img_picto($langs->trans("Disabled"), 'switch_off');
					if (!$disabled) print '</a>';
					print '</td>';
				}

				print '<td class="center">';
				$s = $modCodeProduct->getToolTip($langs, null, -1);
				print $form->textwithpicto('', $s, 1);
				print '</td>';

				print '</tr>';
			}
		}
		closedir($handle);
	}
}
print '</table>';

// Module to build doc
$def = array();
$sql = "SELECT nom";
$sql .= " FROM ".MAIN_DB_PREFIX."document_model";
$sql .= " WHERE type = '".$db->escape($type)."'";
$sql .= " AND entity = ".$conf->entity;
$resql = $db->query($sql);
if ($resql)
{
	$i = 0;
	$num_rows = $db->num_rows($resql);
	while ($i < $num_rows)
	{
		$array = $db->fetch_array($resql);
		array_push($def, $array[0]);
		$i++;
	}
} else {
	dol_print_error($db);
}

print '<br>';

print load_fiche_titre($langs->trans("ProductDocumentTemplates"), '', '');

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Name").'</td>';
print '<td>'.$langs->trans("Description").'</td>';
print '<td class="center" width="60">'.$langs->trans("Status")."</td>\n";
print '<td class="center" width="60">'.$langs->trans("Default")."</td>\n";
print '<td class="center" width="80">'.$langs->trans("ShortInfo").'</td>';
print '<td class="center" width="80">'.$langs->trans("Preview").'</td>';
print "</tr>\n";

clearstatcache();

foreach ($dirmodels as $reldir)
{
	foreach (array('', '/doc') as $valdir)
	{
		$dir = dol_buildpath($reldir."core/modules/product".$valdir);
		if (is_dir($dir))
		{
			$handle = opendir($dir);
			if (is_resource($handle))
			{
				while (($file = readdir($handle)) !== false)
				{
					$filelist[] = $file;
				}
				closedir($handle);
				arsort($filelist);

				foreach ($filelist as $file)
				{
					if (preg_match('/\.modules\.php$/i', $file) && preg_match('/^(pdf_|doc_)/', $file))
					{
						if (file_exists($dir.'/'.$file))
						{
							$name = substr($file, 4, dol_strlen($file) - 16);
							$classname = substr($file, 0, dol_strlen($file) - 12);

							require_once $dir.'/'.$file;
							$module = new $classname($db);

							$modulequalified = 1;
							if ($module->version == 'development' && $conf->global->MAIN_FEATURES_LEVEL < 2) $modulequalified = 0;
							if ($module->version == 'experimental' && $conf->global->MAIN_FEATURES_LEVEL < 1) $modulequalified = 0;

							if ($modulequalified)
							{
								print '<tr class="oddeven"><td width="100">';
								print (empty($module->name) ? $name : $module->name);
								print "</td><td>\n";
								if (method_exists($module, 'info')) print $module->info($langs);
								else print $module->description;
								print '</td>';

								// Active
								if (in_array($name, $def))
								{
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=del&value='.$name.'">';
									print img_picto($langs->trans("Enabled"), 'switch_on');
									print '</a>';
									print '</td>';
								} else {
									print '<td class="center">'."\n";
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;token='.newToken().'&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'">'.img_picto($langs->trans("Disabled"), 'switch_off').'</a>';
									print "</td>";
								}

								// Defaut
								print '<td class="center">';
								if ($conf->global->PRODUCT_ADDON_PDF == $name)
								{
									print img_picto($langs->trans("Default"), 'on');
								} else {
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=setdoc&amp;token='.newToken().'&amp;value='.$name.'&amp;scan_dir='.$module->scandir.'&amp;label='.urlencode($module->name).'" alt="'.$langs->trans("Default").'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
								}
								print '</td>';

								// Info
								$htmltooltip = ''.$langs->trans("Name").': '.$module->name;
								$htmltooltip .= '<br>'.$langs->trans("Type").': '.($module->type ? $module->type : $langs->trans("Unknown"));
								if ($module->type == 'pdf')
								{
									$htmltooltip .= '<br>'.$langs->trans("Width").'/'.$langs->trans("Height").': '.$module->page_largeur.'/'.$module->page_hauteur;
								}
								$htmltooltip .= '<br><br><u>'.$langs->trans("FeaturesSupported").':</u>';
								$htmltooltip .= '<br>'.$langs->trans("Logo").': '.yn($module->option_logo, 1, 1);
								$htmltooltip .= '<br>'.$langs->trans("MultiLanguage").': '.yn($module->option_multilang, 1, 1);


								print '<td class="center">';
								print $form->textwithpicto('', $htmltooltip, 1, 0);
								print '</td>';

								// Preview
								print '<td class="center">';
								if ($module->type == 'pdf')
								{
									print '<a href="'.$_SERVER["PHP_SELF"].'?action=specimen&module='.$name.'">'.img_object($langs->trans("Preview"), 'contract').'</a>';
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
print "<br>";

/*
 * Other conf
 */

print "<br>";

print load_fiche_titre($langs->trans("ProductOtherConf"), '', '');


print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="other">';
print '<input type="hidden" name="page_y" value="">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td class="right" width="60">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";


// Enable kits (subproducts)

print '<tr class="oddeven">';
print '<td>'.$langs->trans("AssociatedProductsAbility").'</td>';
print '<td class="right">';
print ajax_constantonoff("PRODUIT_SOUSPRODUITS", array(), $conf->entity, 0, 0, 1, 0);
//print $form->selectyesno("PRODUIT_SOUSPRODUITS", $conf->global->PRODUIT_SOUSPRODUITS, 1);
print '</td>';
print '</tr>';


// Enable variants

print '<tr class="oddeven">';
print '<td>'.$langs->trans("VariantsAbility").'</td>';
print '<td class="right">';
//print ajax_constantonoff("PRODUIT_SOUSPRODUITS", array(), $conf->entity, 0, 0, 1, 0);
//print $form->selectyesno("PRODUIT_SOUSPRODUITS", $conf->global->PRODUIT_SOUSPRODUITS, 1);
if (empty($conf->variants->enabled)) {
	print '<span class="opacitymedium">'.$langs->trans("ModuleMustBeEnabled", $langs->transnoentitiesnoconv("Module610Name")).'</span>';
} else {
	print yn(1).' <span class="opacitymedium">('.$langs->trans("ModuleIsEnabled", $langs->transnoentitiesnoconv("Module610Name")).')</span>';
}
print '</td>';
print '</tr>';


// Rule for price

print '<tr class="oddeven">';
if (empty($conf->multicompany->enabled))
{
	print '<td>'.$langs->trans("PricingRule").'</td>';
} else {
	print '<td>'.$form->textwithpicto($langs->trans("PricingRule"), $langs->trans("SamePriceAlsoForSharedCompanies"), 1).'</td>';
}
print '<td class="right">';
$current_rule = 'PRODUCT_PRICE_UNIQ';
if (!empty($conf->global->PRODUIT_MULTIPRICES)) $current_rule = 'PRODUIT_MULTIPRICES';
if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY)) $current_rule = 'PRODUIT_CUSTOMER_PRICES_BY_QTY';
if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES)) $current_rule = 'PRODUIT_CUSTOMER_PRICES';
if (!empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES)) $current_rule = 'PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES';
print $form->selectarray("princingrule", $select_pricing_rules, $current_rule);
print '</td>';
print '</tr>';


// multiprix nombre de prix a proposer
if (!empty($conf->global->PRODUIT_MULTIPRICES) || !empty($conf->global->PRODUIT_CUSTOMER_PRICES_BY_QTY_MULTIPRICES))
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("MultiPricesNumPrices").'</td>';
	print '<td class="right"><input size="3" type="text" class="flat" name="value_PRODUIT_MULTIPRICES_LIMIT" value="'.$conf->global->PRODUIT_MULTIPRICES_LIMIT.'"></td>';
	print '</tr>';
}

// Default product price base type
print '<tr class="oddeven">';
print '<td>'.$langs->trans("DefaultPriceType").'</td>';
print '<td class="right">';
print $form->selectPriceBaseType($conf->global->PRODUCT_PRICE_BASE_TYPE, "price_base_type");
print '</td>';
print '</tr>';

// Use Ajax form to select a product

print '<tr class="oddeven">';
print '<td>'.$form->textwithpicto($langs->trans("UseSearchToSelectProduct"), $langs->trans('UseSearchToSelectProductTooltip'), 1).'</td>';
if (empty($conf->use_javascript_ajax))
{
	print '<td class="nowrap right">';
	print $langs->trans("NotAvailableWhenAjaxDisabled");
	print '</td>';
} else {
	print '<td class="right">';
	$arrval = array(
		'0'=>$langs->trans("No"),
		'1'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 1).')',
		'2'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 2).')',
		'3'=>$langs->trans("Yes").' ('.$langs->trans("NumberOfKeyToSearch", 3).')',
	);
	print $form->selectarray("activate_usesearchtoselectproduct", $arrval, $conf->global->PRODUIT_USE_SEARCH_TO_SELECT);
	print '</td>';
}
print '</tr>';

if (empty($conf->global->PRODUIT_USE_SEARCH_TO_SELECT))
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("NumberOfProductShowInSelect").'</td>';
	print '<td class="right"><input size="3" type="text" class="flat" name="value_PRODUIT_LIMIT_SIZE" value="'.$conf->global->PRODUIT_LIMIT_SIZE.'"></td>';
	print '</tr>';
}

// Visualiser description produit dans les formulaires activation/desactivation
print '<tr class="oddeven">';
print '<td>'.$langs->trans("ViewProductDescInFormAbility").'</td>';
print '<td class="right">';
print $form->selectyesno("activate_viewProdDescInForm", $conf->global->PRODUIT_DESC_IN_FORM, 1);
print '</td>';
print '</tr>';

// Activate propal merge produt card
/* Kept as hidden feature only. PRODUIT_PDF_MERGE_PROPAL can be added manually. Still did not understand how this feature works.

print '<tr class="oddeven">';
print '<td>'.$langs->trans("MergePropalProductCard").'</td>';
print '<td class="right">';
print $form->selectyesno("activate_mergePropalProductCard",$conf->global->PRODUIT_PDF_MERGE_PROPAL,1);
print '</td>';
print '</tr>';
*/

// Use units
/* Kept as hidden feature only. PRODUCT_USE_UNITS is hidden for the moment. Because it seems to be a duplicated feature with already existing field to store unit of product

print '<tr class="oddeven">';
print '<td>'.$langs->trans("UseUnits").'</td>';
print '<td class="right">';
print $form->selectyesno("activate_units",$conf->global->PRODUCT_USE_UNITS,1);
print '</td>';
print '</tr>';
*/

// View product description in thirdparty language
if (!empty($conf->global->MAIN_MULTILANGS))
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("ViewProductDescInThirdpartyLanguageAbility").'</td>';
	print '<td class="right">';
	print $form->selectyesno("activate_viewProdTextsInThirdpartyLanguage", (!empty($conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE) ? $conf->global->PRODUIT_TEXTS_IN_THIRDPARTY_LANGUAGE : 0), 1);
	print '</td>';
	print '</tr>';
}

if (!empty($conf->fournisseur->enabled) && empty($conf->global->MAIN_USE_NEW_SUPPLIERMOD) || !empty($conf->supplier_order->enabled) || !empty($conf->supplier_invoice->enabled))
{
	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("UseProductFournDesc").'</td>';
	print '<td class="right">';
	print $form->selectyesno("activate_useProdFournDesc", (!empty($conf->global->PRODUIT_FOURN_TEXTS) ? $conf->global->PRODUIT_FOURN_TEXTS : 0), 1);
	print '</td>';
	print '</tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("UseProductSupplierPackaging").'</td>';
	print '<td align="right">';
	print $form->selectyesno("activate_useProdSupplierPackaging", (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING) ? $conf->global->PRODUCT_USE_SUPPLIER_PACKAGING : 0), 1);
	print '</td>';
	print '</tr>';
}


if (!empty($conf->global->PRODUCT_CANVAS_ABILITY))
{
	// Add canvas feature
	$dir = DOL_DOCUMENT_ROOT."/product/canvas/";

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("ProductSpecial").'</td>'."\n";
	print '<td class="right">'.$langs->trans("Value").'</td>'."\n";
	print '</tr>'."\n";

	if (is_dir($dir))
	{
		require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';

		$handle = opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle)) !== false)
			{
				if (file_exists($dir.$file.'/product.'.$file.'.class.php'))
				{
					$classfile = $dir.$file.'/product.'.$file.'.class.php';
					$classname = 'Product'.ucfirst($file);

					require_once $classfile;
					$object = new $classname();

					$module = $object->module;

					if ($conf->$module->enabled)
					{
						print '<tr class="oddeven"><td>';

						print $object->description;

						print '</td><td class="right">';

						$const = "PRODUCT_SPECIAL_".strtoupper($file);

						if ($conf->global->$const)
						{
							print img_picto($langs->trans("Active"), 'tick');
							print '</td><td class="right">';
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;token='.newToken().'&amp;spe='.urlencode($file).'&amp;value=0">'.$langs->trans("Disable").'</a>';
						} else {
							print '&nbsp;</td><td class="right">';
							print '<a href="'.$_SERVER["PHP_SELF"].'?action=set&amp;token='.newToken().'&amp;spe='.urlencode($file).'&amp;value=1">'.$langs->trans("Activate").'</a>';
						}

						print '</td></tr>';
					}
				}
			}
			closedir($handle);
		}
	} else {
		setEventMessages($dir.' '.$langs->trans("IsNotADir"), null, 'errors');
	}
}

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button reposition" value="'.$langs->trans("Modify").'">';
print '</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
