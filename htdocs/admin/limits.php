<?php
/* Copyright (C) 2007-2022	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2018	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2023       Alexandre Spangaro  <aspangaro@open-dsi.fr>
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
 *       \file       htdocs/admin/limits.php
 *       \brief      Page to setup limits
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'admin'));

$action = GETPOST('action', 'aZ09');
$cancel = GETPOST('cancel', 'aZ09');
$currencycode = GETPOST('currencycode', 'alpha');

if (isModEnabled('multicompany') && getDolGlobalString('MULTICURRENCY_USE_LIMIT_BY_CURRENCY')) {
	// When MULTICURRENCY_USE_LIMIT_BY_CURRENCY is on, we use always a defined currency code instead of '' even for default.
	$currencycode = (!empty($currencycode) ? $currencycode : $conf->currency);
}

$mainmaxdecimalsunit = 'MAIN_MAX_DECIMALS_UNIT'.(!empty($currencycode) ? '_'.$currencycode : '');
$mainmaxdecimalstot = 'MAIN_MAX_DECIMALS_TOT'.(!empty($currencycode) ? '_'.$currencycode : '');
$mainmaxdecimalsshown = 'MAIN_MAX_DECIMALS_SHOWN'.(!empty($currencycode) ? '_'.$currencycode : '');
$mainroundingruletot = 'MAIN_ROUNDING_RULE_TOT'.(!empty($currencycode) ? '_'.$currencycode : '');

$valmainmaxdecimalsunit = GETPOSTINT($mainmaxdecimalsunit);
$valmainmaxdecimalstot = GETPOSTINT($mainmaxdecimalstot);
$valmainmaxdecimalsshown = GETPOST($mainmaxdecimalsshown, 'alpha');	// Can be 'x.y' but also 'x...'
$valmainroundingruletot = price2num(GETPOST($mainroundingruletot, 'alphanohtml'), '', 2);

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

if ($action == 'update' && !$cancel) {
	$error = 0;
	$MAXDEC = 8;
	if ($valmainmaxdecimalsunit > $MAXDEC
		|| $valmainmaxdecimalstot > $MAXDEC
		|| $valmainmaxdecimalsshown > $MAXDEC) {
		$error++;
		setEventMessages($langs->trans("ErrorDecimalLargerThanAreForbidden", $MAXDEC), null, 'errors');
		$action = 'edit';
	}

	if ($valmainmaxdecimalsunit < 0
		|| $valmainmaxdecimalstot < 0
		|| $valmainmaxdecimalsshown < 0) {
		$langs->load("errors");
		$error++;
		setEventMessages($langs->trans("ErrorNegativeValueNotAllowed"), null, 'errors');
		$action = 'edit';
	}

	if ($valmainroundingruletot) {
		if ((float) $valmainroundingruletot * pow(10, $valmainmaxdecimalstot) < 1) {
			$langs->load("errors");
			$error++;
			setEventMessages($langs->trans("ErrorMAIN_ROUNDING_RULE_TOTCanMAIN_MAX_DECIMALS_TOT"), null, 'errors');
			$action = 'edit';
		}
	}

	if ((float) $valmainmaxdecimalsshown == 0) {
		$langs->load("errors");
		$error++;
		setEventMessages($langs->trans("ErrorValueCantBeNull", dol_trunc(dol_string_nohtmltag($langs->transnoentitiesnoconv("MAIN_MAX_DECIMALS_SHOWN")), 40)), null, 'errors');
		$action = 'edit';
	}
	if (! $error && ((float) $valmainmaxdecimalsshown < $valmainmaxdecimalsunit || (float) $valmainmaxdecimalsshown < $valmainmaxdecimalstot)) {
		$langs->load("errors");
		$error++;
		setEventMessages($langs->trans("ErrorValueForTooLow", dol_trunc(dol_string_nohtmltag($langs->transnoentitiesnoconv("MAIN_MAX_DECIMALS_SHOWN")), 40)), null, 'errors');
		$action = 'edit';
	}

	if (!$error) {
		dolibarr_set_const($db, $mainmaxdecimalsunit, $valmainmaxdecimalsunit, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, $mainmaxdecimalstot, $valmainmaxdecimalstot, 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, $mainmaxdecimalsshown, $valmainmaxdecimalsshown, 'chaine', 0, '', $conf->entity);

		dolibarr_set_const($db, $mainroundingruletot, $valmainroundingruletot, 'chaine', 0, '', $conf->entity);

		header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup".(!empty($currencycode) ? '&currencycode='.$currencycode : ''));
		exit;
	}
}


/*
 * View
 */

$form = new Form($db);

$title = $langs->trans("LimitsSetup");
$help_url = '';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-admin page-limits');

print load_fiche_titre($title, '', 'title_setup');

$aCurrencies = array($conf->currency); // Default currency always first position

if (isModEnabled('multicompany') && getDolGlobalString('MULTICURRENCY_USE_LIMIT_BY_CURRENCY')) {
	require_once DOL_DOCUMENT_ROOT . '/core/lib/multicurrency.lib.php';

	$sql = "SELECT rowid, code FROM " . MAIN_DB_PREFIX . "multicurrency";
	$sql .= " WHERE entity = " . ((int) $conf->entity);
	$sql .= " AND code <> '" . $db->escape($conf->currency) . "'"; // Default currency always first position
	$resql = $db->query($sql);
	if ($resql) {
		while ($obj = $db->fetch_object($resql)) {
			$aCurrencies[] = $obj->code;
		}
	}

	if (!empty($aCurrencies) && count($aCurrencies) > 1) {
		$head = multicurrencyLimitPrepareHead($aCurrencies);

		print dol_get_fiche_head($head, $currencycode, '', -1, '');
	}
}

print '<span class="opacitymedium">'.$langs->trans("LimitsDesc")."</span><br>\n";
print "<br>\n";

if ($action == 'edit') {
	print '<form method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
	print '<input type="hidden" name="token" value="' . newToken() . '">';
	print '<input type="hidden" name="action" value="update">';
	if (isModEnabled('multicompany') && getDolGlobalString('MULTICURRENCY_USE_LIMIT_BY_CURRENCY')) {
		print '<input type="hidden" name="currencycode" value="' . $currencycode . '">';
	}

	clearstatcache();

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"), $langs->trans("ParameterActiveForNextInputOnly"));
	print '</td><td><input class="flat right" name="'.$mainmaxdecimalsunit.'" size="3" value="'.(GETPOSTISSET($mainmaxdecimalsunit) ? GETPOST($mainmaxdecimalsunit) : getDolGlobalInt('MAIN_MAX_DECIMALS_UNIT', 0)).'"></td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"), $langs->trans("ParameterActiveForNextInputOnly"));
	print '</td><td><input class="flat right" name="'.$mainmaxdecimalstot.'" size="3" value="'.(GETPOSTISSET($mainmaxdecimalstot) ? GETPOST($mainmaxdecimalstot) : getDolGlobalInt('MAIN_MAX_DECIMALS_TOT', 0)).'"></td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td>';
	print '<td><input class="flat right" name="'.$mainmaxdecimalsshown.'" size="3" value="'.(GETPOSTISSET($mainmaxdecimalsshown) ? GETPOST($mainmaxdecimalsshown) : getDolGlobalString('MAIN_MAX_DECIMALS_SHOWN')).'"></td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"), $langs->trans("ParameterActiveForNextInputOnly"));
	print '</td><td><input class="flat right" name="'.$mainroundingruletot.'" size="3" value="'.(GETPOSTISSET($mainroundingruletot) ? GETPOST($mainroundingruletot) : getDolGlobalString('MAIN_ROUNDING_RULE_TOT')).'"></td></tr>';

	print '</table>';

	print '<div class="center">';
	print '<input class="button button-save" type="submit" name="save" value="'.$langs->trans("Save").'">';
	print ' &nbsp; ';
	print '<input class="button button-cancel" type="submit" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '<br>';

	print '</form>';
	print '<br>';
} else {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td class="right">'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"), $langs->trans("ParameterActiveForNextInputOnly"));
	print '</td><td align="right">'.(isset($conf->global->$mainmaxdecimalsunit) ? $conf->global->$mainmaxdecimalsunit : $conf->global->MAIN_MAX_DECIMALS_UNIT).'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"), $langs->trans("ParameterActiveForNextInputOnly"));
	print '</td><td align="right">'.(isset($conf->global->$mainmaxdecimalstot) ? $conf->global->$mainmaxdecimalstot : $conf->global->MAIN_MAX_DECIMALS_TOT).'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td>';
	print '<td align="right">'.(isset($conf->global->$mainmaxdecimalsshown) ? $conf->global->$mainmaxdecimalsshown : $conf->global->MAIN_MAX_DECIMALS_SHOWN).'</td></tr>';

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"), $langs->trans("ParameterActiveForNextInputOnly"));
	print '</td><td align="right">'.(isset($conf->global->$mainroundingruletot) ? $conf->global->$mainroundingruletot : (getDolGlobalString('MAIN_ROUNDING_RULE_TOT') ? $conf->global->MAIN_ROUNDING_RULE_TOT : '')).'</td></tr>';

	print '</table>';
	print '</div>';

	print '<div class="tabsAction">';
	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&token='.newToken().(!empty($currencycode) ? '&currencycode='.$currencycode : '').'">'.$langs->trans("Modify").'</a>';
	print '</div>';
}

if (isModEnabled('multicompany') && getDolGlobalString('MULTICURRENCY_USE_LIMIT_BY_CURRENCY')) {
	if (!empty($aCurrencies) && count($aCurrencies) > 1) {
		print dol_get_fiche_end();
	}
}

if (empty($mysoc->country_code)) {
	$langs->load("errors");
	$warnpicto = img_warning($langs->trans("WarningMandatorySetupNotComplete"));
	print '<br><a href="'.DOL_URL_ROOT.'/admin/company.php?mainmenu=home">'.$warnpicto.' '.$langs->trans("WarningMandatorySetupNotComplete").'</a>';
} else {
	// Show examples
	print load_fiche_titre($langs->trans("ExamplesWithCurrentSetup"), '', '');

	print '<span class="opacitymedium">'.$langs->trans("Format").':</span> '.price(price2num(1234.56789, 'MT'), 0, $langs, 1, -1, -1, $currencycode)."<br>\n";

	// Always show vat rates with vat 0
	$s = 2 / 3;
	$qty = 1;
	$vat = 0;
	$tmparray = calcul_price_total(1, $qty * (float) price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	print ' x <span class="opacitymedium">'.$langs->trans("Quantity").":</span> ".$qty;
	print ' - <span class="opacitymedium">'.$langs->trans("VAT").":</span> ".$vat.'%';
	print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	$s = 10 / 3;
	$qty = 1;
	$vat = 0;
	$tmparray = calcul_price_total(1, $qty * (float) price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	print ' x <span class="opacitymedium">'.$langs->trans("Quantity").":</span> ".$qty;
	print ' - <span class="opacitymedium">'.$langs->trans("VAT").":</span> ".$vat.'%';
	print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	$s = 10 / 3;
	$qty = 2;
	$vat = 0;
	$tmparray = calcul_price_total(1, $qty * (float) price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	print ' x <span class="opacitymedium">'.$langs->trans("Quantity").":</span> ".$qty;
	print ' - <span class="opacitymedium">'.$langs->trans("VAT").":</span> ".$vat.'%';
	print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	// Add vat rates examples specific to country
	$vat_rates = array();

	$sql = "SELECT taux as vat_rate, t.code as vat_code, t.localtax1 as localtax_rate1, t.localtax2 as localtax_rate2";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
	$sql .= " WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$db->escape($mysoc->country_code)."' AND (t.taux <> 0 OR t.localtax1 <> '0' OR t.localtax2 <> '0')";
	$sql .= " AND t.entity IN (".getEntity('c_tva').")";
	$sql .= " ORDER BY t.taux ASC";
	$resql = $db->query($sql);
	if ($resql) {
		$num = $db->num_rows($resql);
		if ($num) {
			for ($i = 0; $i < $num; $i++) {
				$obj = $db->fetch_object($resql);
				$vat_rates[] = array('vat_rate' => $obj->vat_rate, 'code' => $obj->vat_code, 'localtax_rate1' => $obj->localtax_rate1, 'locltax_rate2' => $obj->localtax_rate2);
			}
		}
	} else {
		dol_print_error($db);
	}

	if (count($vat_rates)) {
		foreach ($vat_rates as $vatarray) {
			$vat = $vatarray['vat_rate'];
			for ($qty = 1; $qty <= 2; $qty++) {
				$vattxt = $vat.($vatarray['code'] ? ' ('.$vatarray['code'].')' : '');

				$localtax_array = getLocalTaxesFromRate($vattxt, 0, $mysoc, $mysoc);

				$s = 10 / 3;
				$tmparray = calcul_price_total($qty, price2num($s, 'MU'), 0, $vat, -1, -1, 0, 'HT', 0, 0, $mysoc, $localtax_array);
				print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
				print ' x <span class="opacitymedium">'.$langs->trans("Quantity").":</span> ".$qty;
				print ' - <span class="opacitymedium">'.$langs->trans("VAT").':</span> '.$vat.'%';
				print($vatarray['code'] ? ' ('.$vatarray['code'].')' : '');
				print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ";
				print $tmparray[0].' / '.$tmparray[1].($tmparray[9] ? '+'.$tmparray[9] : '').($tmparray[10] ? '+'.$tmparray[10] : '').' / '.$tmparray[2];
				print "<br>\n";
			}
		}
	} else {
		// More examples if not specific vat rate found
		// This example must be kept for test purpose with current value because value used (2/7, 10/3, and vat 0, 10)
		// were calculated to show all possible cases of rounding. If we change this, examples becomes useless or show the same rounding rule.

		$localtax_array = array();

		$s = 10 / 3;
		$qty = 1;
		$vat = 10;
		$tmparray = calcul_price_total($qty, price2num($s, 'MU'), 0, $vat, -1, -1, 0, 'HT', 0, 0, $mysoc, $localtax_array);
		print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
		print ' x <span class="opacitymedium">'.$langs->trans("Quantity").":</span> ".$qty;
		print ' - <span class="opacitymedium">'.$langs->trans("VAT").":</span> ".$vat.'%';
		print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

		$s = 10 / 3;
		$qty = 2;
		$vat = 10;
		$tmparray = calcul_price_total($qty, price2num($s, 'MU'), 0, $vat, -1, -1, 0, 'HT', 0, 0, $mysoc, $localtax_array);
		print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
		print ' x <span class="opacitymedium">'.$langs->trans("Quantity").":</span> ".$qty;
		print ' - <span class="opacitymedium">'.$langs->trans("VAT").":</span> ".$vat.'%';
		print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";
	}
}

// End of page
llxFooter();
$db->close();
