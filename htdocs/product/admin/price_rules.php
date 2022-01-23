<?php
/**
 * Copyright (C) 2015 Marcos García	<marcosgdf@gmail.com
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
 *
 * Page to set how to autocalculate price for each level when option PRODUCT_MULTIPRICE is on.
 * This page is a tab in the setup of module Product if option PRODUIT_MULTIPRICES_ALLOW_AUTOCALC_PRICELEVEL is set.
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'products'));

$action = GETPOST('action', 'aZ09');

// Security check
if (!$user->admin || (empty($conf->product->enabled) && empty($conf->service->enabled))) {
	accessforbidden();
}

$error = 0;


/**
 * Actions
 */

if ($action == 'update') {
	$var_percent = GETPOST('var_percent', 'array');
	$var_min_percent = GETPOST('var_min_percent', 'array');
	$fk_level = GETPOST('fk_level', 'array');

	for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
		$check = isset($var_min_percent[$i]);

		if ($i != 1) {
			$check = $check && isset($var_percent[$i]) && isset($fk_level[$i]);
		}

		if (!$check) {
			continue;
		}

		$i_var_percent = 0;

		// Set $i_var_percent, the percent of price for level compared to an other level
		if ($i != 1) {
			$i_var_percent = (float) price2num($var_percent[$i]);
		}

		$i_var_min_percent = (float) price2num($var_min_percent[$i]);
		$i_fk_level = (int) $fk_level[$i];

		if ($i == 1) {
			$check1 = true;
			$check2 = $i_var_min_percent;
		} else {
			$check1 = $i_fk_level >= 1 && $i_fk_level <= $conf->global->PRODUIT_MULTIPRICES_LIMIT;
			$check2 = $i_var_percent && ($i_var_min_percent || (string) $i_var_min_percent === '0');
		}

		if (empty($i_var_percent) && empty($i_var_min_percent)) {
			//If the level is between range but percent fields are empty, then we ensure it does not exist in DB
			$db->query("DELETE FROM ".MAIN_DB_PREFIX."product_pricerules WHERE level = ".((int) $i));
			continue;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_pricerules (level, fk_level, var_percent, var_min_percent) VALUES (";
		$sql .= ((int) $i).", ".$db->escape($i_fk_level).", ".$i_var_percent.", ".$i_var_min_percent.")";

		if (!$db->query($sql)) {
			//If we could not create, then we try updating
			$sql = "UPDATE ".MAIN_DB_PREFIX."product_pricerules";
			$sql .= " SET fk_level = ".$db->escape($i_fk_level).", var_percent = ".$i_var_percent.", var_min_percent = ".$i_var_min_percent." WHERE level = ".((int) $i);

			if (!$db->query($sql)) {
				setEventMessages($langs->trans('ErrorSavingChanges'), null, 'errors');
				$error++;
			}
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	}
}


/*
 * View
 */

$sql = "SELECT rowid, level, fk_level, var_percent, var_min_percent";
$sql .= " FROM ".MAIN_DB_PREFIX."product_pricerules";
$query = $db->query($sql);

$rules = array();

while ($result = $db->fetch_object($query)) {
	$rules[$result->level] = $result;
}

$title = $langs->trans('ProductServiceSetup');
$tab = $langs->trans("ProductsAndServices");

if (empty($conf->product->enabled)) {
	$title = $langs->trans('ServiceSetup');
	$tab = $langs->trans('Services');
} elseif (empty($conf->service->enabled)) {
	$title = $langs->trans('ProductSetup');
	$tab = $langs->trans('Products');
}

llxHeader('', $langs->trans('MultipriceRules'));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');


print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

$head = product_admin_prepare_head();
print dol_get_fiche_head($head, 'generator', $tab, -1, 'product');

print '<span class="opacitymedium">'.$langs->trans("MultiPriceRuleDesc").'</span><br><br>';

// Array that contains the number of prices available
$price_options = array();

for ($i = 1; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) {
	$price_options[$i] = $langs->trans('SellingPrice').' '.$i;
}
?>

	<table class="noborder centpercent">
		<tr class="liste_titre">
			<td style="text-align: center"><?php echo $langs->trans('PriceLevel') ?></td>
			<td style="text-align: center"><?php echo $langs->trans('Price') ?></td>
			<td style="text-align: center"><?php echo $langs->trans('MinPrice') ?></td></tr>
		<tr>
			<td class="fieldrequired" style="text-align: center"><?php echo $langs->trans('SellingPrice') ?> 1</td>
			<td></td>
			<td style="text-align: center"><input type="text"  style="text-align: right" name="var_min_percent[1]" size="5" value="<?php echo price(isset($rules[1]) ? $rules[1]->var_min_percent : 0, 2) ?>"> <?php echo $langs->trans('PercentDiscountOver', $langs->trans('SellingPrice').' 1') ?></td>
		</tr>
		<?php for ($i = 2; $i <= $conf->global->PRODUIT_MULTIPRICES_LIMIT; $i++) : ?>
			<tr>
				<td class="fieldrequired" style="text-align: center"><?php
					echo $langs->trans('SellingPrice').' '.$i;
					// Label of price
					$keyforlabel = 'PRODUIT_MULTIPRICES_LABEL'.$i;
				if (!empty($conf->global->$keyforlabel)) {
					print ' - '.$langs->trans($conf->global->$keyforlabel);
				}
				?>
					</td>
				<td style="text-align: center">
					<input type="text" style="text-align: right" name="var_percent[<?php echo $i ?>]" size="5" value="<?php echo price(isset($rules[$i]) ? $rules[$i]->var_percent : 0, 2) ?>">
					<?php
					$return = array();
					for ($j = 1; $j < $i; $j++) {
						$return[$j] = $price_options[$j];
					}
					$texttoshow = $langs->trans('PercentVariationOver', '{s1}');
					$texttoshow = str_replace('{s1}', Form::selectarray("fk_level[$i]", $return, (isset($rules[$i]) ? $rules[$i]->fk_level : null)), $texttoshow);
					print $texttoshow;
					?>
				</td>
				<td style="text-align: center">
					<input type="text" style="text-align: right" name="var_min_percent[<?php echo $i ?>]" size="5" value="<?php echo price(isset($rules[$i]) ? $rules[$i]->var_min_percent : 0, 2) ?>">
					<?php echo $langs->trans('PercentDiscountOver', $langs->transnoentitiesnoconv('SellingPrice').' '.$i) ?>
				</td>
			</tr>
		<?php endfor ?>
	</table>

<?php

print dol_get_fiche_end();

print '<div style="text-align: center">
		<input type="submit" value="'.$langs->trans("Save").'" class="button button-save">
	</div>';

print '</form>';

// End of page
llxFooter();
$db->close();
