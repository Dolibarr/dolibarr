<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\file		admin/multicurrency.php
 * 	\ingroup	multicurrency
 * 	\brief		Page to setup multicurrency module
 */

// Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/multicurrency.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('admin', 'multicurrency'));

// Access control
if (!$user->admin || !isModEnabled('multicurrency')) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');

$multicurrency = new MultiCurrency($db);


/*
 * Actions
 */

$reg = array();
if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	$value = GETPOST($code, 'alpha');
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'add_currency') {			// Manual insertion of a rate
	$error = 0;

	$langs->loadCacheCurrencies('');

	$code = GETPOST('code', 'alpha');
	$rate = price2num(GETPOST('rate', 'alpha'));
	$currency = new MultiCurrency($db);
	$currency->code = $code;
	$currency->name = !empty($langs->cache_currencies[$code]['label']) ? $langs->cache_currencies[$code]['label'].' ('.$langs->getCurrencySymbol($code).')' : $code;

	if (empty($currency->code) || $currency->code == '-1') {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Currency")), null, 'errors');
		$error++;
	}
	if (empty($rate)) {
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Rate")), null, 'errors');
		$error++;
	}

	if (!$error) {
		if ($currency->create($user) > 0) {
			if ($currency->addRate((float) $rate)) {
				setEventMessages($langs->trans('RecordSaved'), array());
			} else {
				setEventMessages($langs->trans('ErrorAddRateFail'), array(), 'errors');
			}
		} else {
			setEventMessages($langs->trans('ErrorAddCurrencyFail'), $currency->errors, 'errors');
		}
	}
} elseif ($action == 'update_currency') {	// Manual update of rate
	$error = 0;

	if (GETPOST('updatecurrency', 'alpha')) {
		$fk_multicurrency = GETPOSTINT('fk_multicurrency');
		$rate = price2num(GETPOST('rate', 'alpha'));
		$currency = new MultiCurrency($db);

		if (empty($rate)) {
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Rate")), null, 'errors');
			$error++;
		}
		if (!$error) {
			if ($currency->fetch($fk_multicurrency) > 0) {
				$result = $currency->updateRate((float) $rate);
				if ($result < 0) {
					setEventMessages(null, $currency->errors, 'errors');
				}
			}
		}
	} elseif (GETPOST('deletecurrency', 'alpha')) {
		$fk_multicurrency = GETPOSTINT('fk_multicurrency');
		$currency = new MultiCurrency($db);

		if ($currency->fetch($fk_multicurrency) > 0) {
			if ($currency->delete($user) > 0) {
				setEventMessages($langs->trans('RecordDeleted'), array());
			} else {
				setEventMessages($langs->trans('ErrorDeleteCurrencyFail'), array(), 'errors');
			}
		}
	}
} elseif ($action == 'setapilayer') {		// Update rate from currencylayer
	if (GETPOSTISSET('modify_apilayer')) {
		// Save setup
		dolibarr_set_const($db, 'MULTICURRENCY_APP_KEY', GETPOST('MULTICURRENCY_APP_KEY', 'alpha'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MULTICURRENCY_APP_SOURCE', GETPOST('MULTICURRENCY_APP_SOURCE', 'alpha'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, 'MULTICURRENCY_APP_ENDPOINT', GETPOST('MULTICURRENCY_APP_ENDPOINT', 'alpha'), 'chaine', 0, '', $conf->entity);
		//dolibarr_set_const($db, 'MULTICURRENCY_ALTERNATE_SOURCE', GETPOST('MULTICURRENCY_ALTERNATE_SOURCE', 'alpha'), 'chaine', 0, '', $conf->entity);

		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		// Run the update o currency rate (this may updates database)
		$result = $multicurrency->syncRates(0, 0, '');
		if ($result > 0) {
			setEventMessages($langs->trans("CurrencyRateSyncSucceed"), null, "mesgs");
		} else {
			setEventMessages($multicurrency->output, null, 'errors');
		}
	}
}


$TAvailableCurrency = array();
$sql = "SELECT code_iso, label, unicode, active FROM ".MAIN_DB_PREFIX."c_currencies";
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$TAvailableCurrency[$obj->code_iso] = array('code' => $obj->code_iso, 'active' => $obj->active);
	}
}

$TCurrency = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."multicurrency WHERE entity = ".((int) $conf->entity);
$resql = $db->query($sql);
if ($resql) {
	while ($obj = $db->fetch_object($resql)) {
		$currency = new MultiCurrency($db);
		$currency->fetch($obj->rowid);
		$TCurrency[] = $currency;
	}
}


/*
 * View
 */

$form = new Form($db);

$page_name = "MultiCurrencySetup";
$help_url = '';

llxHeader('', $langs->trans($page_name), $help_url, '', 0, 0, '', '', '', 'mod-admin page-multicurrency');

// Subheader
$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = multicurrencyAdminPrepareHead();
print dol_get_fiche_head($head, 'settings', $langs->trans($page_name), -1, "multicurrency");


print '<br>';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td class="center">'.$langs->trans("Status").'</td>'."\n";
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE").'</td>';
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE');
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE", $arrval, $conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE);
}
print '</td></tr>';


print '<tr class="oddeven">';
$tooltip = $langs->trans("multicurrency_useOriginTxHelp");
print '<td>'.$form->textwithpicto($langs->transnoentitiesnoconv("multicurrency_useOriginTx"), $tooltip).'</td>';
print '<td class="center">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('MULTICURRENCY_USE_ORIGIN_TX', array(), null, 0, 0, 0, 2, 0, 1);
} else {
	$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
	print $form->selectarray("MULTICURRENCY_USE_ORIGIN_TX", $arrval, $conf->global->MULTICURRENCY_USE_ORIGIN_TX);
}
print '</td></tr>';

// Online payment with currency on document. This option should be on by default.
if (getDolGlobalInt('MAIN_FEATURES_LEVEL') >= 2) {
	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("MULTICURRENCY_USE_CURRENCY_ON_DOCUMENT").'</td>';
	print '<td class="center">';
	if ($conf->use_javascript_ajax) {
		print ajax_constantonoff('MULTICURRENCY_USE_CURRENCY_ON_DOCUMENT');
	} else {
		$arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
		print $form->selectarray("MULTICURRENCY_USE_CURRENCY_ON_DOCUMENT", $arrval, $conf->global->MULTICURRENCY_USE_CURRENCY_ON_DOCUMENT);
	}
	print '</td></tr>';
}

/* TODO uncomment when the functionality will integrated

print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_buyPriceInCurrency").'</td>';
print '<td class="right">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_BUY_PRICE_IN_CURRENCY">';
print $form->selectyesno("MULTICURRENCY_BUY_PRICE_IN_CURRENCY",$conf->global->MULTICURRENCY_BUY_PRICE_IN_CURRENCY,1);
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';
*/

/* TODO uncomment when the functionality will integrated

print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_modifyRateApplication").'</td>';
print '<td class="right">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_MODIFY_RATE_APPLICATION">';
print $form->selectarray('MULTICURRENCY_MODIFY_RATE_APPLICATION', array('PU_DOLIBARR' => 'PU_DOLIBARR', 'PU_CURRENCY' => 'PU_CURRENCY'), $conf->global->MULTICURRENCY_MODIFY_RATE_APPLICATION);
print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

*/

print '</table>';
print '</div>';

print '<br>';

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent nomarginbottom">';

print '<tr class="liste_titre">';
print '<td>'.$form->textwithpicto($langs->trans("CurrenciesUsed"), $langs->transnoentitiesnoconv("CurrenciesUsed_help_to_add")).'</td>'."\n";
print '<td class="right">'.$langs->trans("Rate").' / '.$langs->getCurrencySymbol($conf->currency).'</td>'."\n";
print '</tr>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add_currency">';

print '<tr class="oddeven">';
print '<td>'.$form->selectCurrency('', 'code', 1, '1').'</td>';
print '<td class="right">';
print '<input type="text" name="rate" value="" class="width75 right" placeholder="'.$langs->trans('Rate').'" />&nbsp;';
print '<input type="submit" class="button button-add smallpaddingimp" value="'.$langs->trans("Add").'">';
print '</td>';
print '</tr>';

print '</form>';

// Main currency
print '<tr class="oddeven">';
print '<td>'.$conf->currency;
print ' ('.$langs->getCurrencySymbol($conf->currency).')';
print $form->textwithpicto(' ', $langs->trans("BaseCurrency"));
if (!empty($TAvailableCurrency[$conf->currency]) && empty($TAvailableCurrency[$conf->currency]['active'])) {
	print img_warning('Warning: This code has been disabled into Home - Setup - Dictionaries - Currencies');
}
print '</td>';
print '<td class="right">1</td>';
print '</tr>';

foreach ($TCurrency as &$currency) {
	if ($currency->code == $conf->currency) {
		continue;
	}

	print '<tr class="oddeven">';
	print '<td>'.$currency->code.' - '.$currency->name;
	if (!empty($TAvailableCurrency[$currency->code]) && empty($TAvailableCurrency[$currency->code]['active'])) {
		print img_warning('Warning: The code '.$currency->code.' has been disabled into Home - Setup - Dictionaries - Currencies');
	}
	print '</td>';
	print '<td class="right">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update_currency">';
	print '<input type="hidden" name="fk_multicurrency" value="'.$currency->id.'">';
	print '1 '.$conf->currency.' = ';
	print '<input type="text" name="rate" class="width125 right" value="'.($currency->rate->rate ? $currency->rate->rate : '').'">&nbsp;'.$currency->code.'&nbsp;';
	print '<input type="submit" name="updatecurrency" class="button button-edit smallpaddingimp" value="'.$langs->trans("Modify").'">&nbsp;';
	print '<input type="submit" name="deletecurrency" class="button smallpaddingimp" value="'.$langs->trans("Delete").'">';
	print '</form>';
	print '</td></tr>';
}

print '</table>';
print '</div>';

print '
	<script type="text/javascript">
 		function getRates()
		{
			$("#bt_sync").attr("disabled", true);
            return true;
		}
	</script>
';


print '<br>';

if (!getDolGlobalString('MULTICURRENCY_DISABLE_SYNC_CURRENCYLAYER')) {
	print '<br>';

	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" id="form_sync">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="setapilayer">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	$urlforapilayer = 'https://currencylayer.com'; //https://apilayer.net

	$endpointdefault = 'https://api.currencylayer.com/live?access_key=__MULTICURRENCY_APP_KEY__&source=__MULTICURRENCY_APP_SOURCE__';
	$endpointdefault2 = 'https://api.apilayer.com/currency_data/live?base=__MULTICURRENCY_APP_SOURCE__';

	$tooltiptext = $langs->trans("CurrencyLayerAccount_help_to_synchronize", $urlforapilayer).'<br><span class="small">';
	$tooltiptext .= '<br>- Endpoint for currencylayer:<br>'.$endpointdefault;
	$tooltiptext .= '<br>- Endpoint for apilayer:<br>'.$endpointdefault2;
	$tooltiptext .= '</span><br>';

	print '<tr class="liste_titre">';
	print '<td>'.$form->textwithpicto($langs->trans("CurrencyLayerAccount"), $tooltiptext, 1, 'help', 'valignmiddle', 0, 3, 'tooltipcurrencylayer').'</td>'."\n";
	print '<td class="right">';
	print '<textarea id="response" class="hideobject" name="response"></textarea>';
	print '<input type="submit" name="modify_apilayer" class="button buttongen" value="'.$langs->trans("Modify").'">';
	print '<input type="submit" id="bt_sync" name="bt_sync_apilayer" class="button buttongen" value="'.$langs->trans('Synchronize').'"';
	if (!getDolGlobalString('MULTICURRENCY_APP_KEY')) {
		print ' disabled="disabled"';
	}
	print '/>';
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("multicurrency_appId").'</td>';
	print '<td class="right">';
	print '<input class="width300" type="text" name="MULTICURRENCY_APP_KEY" value="' . getDolGlobalString('MULTICURRENCY_APP_KEY').'" />&nbsp;';
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("multicurrency_appCurrencySource").'</td>';
	print '<td class="right">';
	print '<input type="text" name="MULTICURRENCY_APP_SOURCE" value="' . getDolGlobalString('MULTICURRENCY_APP_SOURCE').'" size="10" placeholder="USD" />&nbsp;'; // Default: USD
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("MULTICURRENCY_APP_ENDPOINT").'</td>';
	print '<td class="right">';
	print '<input class="width500" type="text" name="MULTICURRENCY_APP_ENDPOINT" value="' . getDolGlobalString('MULTICURRENCY_APP_ENDPOINT', MultiCurrency::MULTICURRENCY_APP_ENDPOINT_DEFAULT).'" />&nbsp;';
	print '</td></tr>';

	/*print '<tr class="oddeven">';
	 print '<td>'.$langs->transnoentitiesnoconv("multicurrency_alternateCurrencySource").'</td>';
	 print '<td class="right">';
	 print '<input type="text" name="MULTICURRENCY_ALTERNATE_SOURCE" value="'.$conf->global->MULTICURRENCY_ALTERNATE_SOURCE.'" size="10" placeholder="EUR" />&nbsp;'; // Example: EUR
	 print '</td></tr>';*/

	print '</table>';
	print '</div>';
	print '<br>';

	print '</form>';
}


// End of page
llxFooter();
$db->close();
