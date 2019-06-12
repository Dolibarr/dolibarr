<?php
/* <one line to give the program's name and a brief idea of what it does.>
 * Copyright (C) 2015 ATM Consulting <support@atm-consulting.fr>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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


// Load translation files required by the page
$langs->loadLangs(array('admin', 'multicurrency'));

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');


/*
 * Actions
 */


if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg))
{
	$code=$reg[1];
	$value=GETPOST($code, 'alpha');
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
        setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
        setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else
	{
        setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'add_currency')
{
	$error=0;

	$langs->loadCacheCurrencies('');

	$code = GETPOST('code', 'alpha');
	$rate = price2num(GETPOST('rate', 'alpha'));
	$currency = new MultiCurrency($db);
	$currency->code = $code;
	$currency->name = !empty($langs->cache_currencies[$code]['label']) ? $langs->cache_currencies[$code]['label'].' ('.$langs->getCurrencySymbol($code).')' : $code;

	if (empty($rate))
	{
		setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Rate")), null, 'errors');
		$error++;
	}
	if (! $error)
	{
		if ($currency->create($user) > 0)
		{
			if ($currency->addRate($rate)) setEventMessages($langs->trans('RecordSaved'), array());
			else setEventMessages($langs->trans('ErrorAddRateFail'), array(), 'errors');
		}
		else setEventMessages($langs->trans('ErrorAddCurrencyFail'), $currency->errors, 'errors');
	}
}
elseif ($action == 'update_currency')
{
	$error = 0;

	if (GETPOST('updatecurrency', 'alpha'))
	{
		$fk_multicurrency = GETPOST('fk_multicurrency', 'int');
		$rate = price2num(GETPOST('rate', 'alpha'));
		$currency = new MultiCurrency($db);

		if (empty($rate))
		{
			setEventMessages($langs->trans('ErrorFieldRequired', $langs->transnoentitiesnoconv("Rate")), null, 'errors');
			$error++;
		}
		if (! $error)
		{
			if ($currency->fetch($fk_multicurrency) > 0)
			{
				$result=$currency->updateRate($rate);
				if ($result<0) {
					setEventMessages(null, $currency->errors, 'errors');
				}
			}
		}
	}
	elseif (GETPOST('deletecurrency', 'alpha'))
	{
		$fk_multicurrency = GETPOST('fk_multicurrency', 'int');
		$currency = new MultiCurrency($db);

		if ($currency->fetch($fk_multicurrency) > 0)
		{
			if ($currency->delete() > 0) setEventMessages($langs->trans('RecordDeleted'), array());
			else setEventMessages($langs->trans('ErrorDeleteCurrencyFail'), array(), 'errors');
		}
	}
}
elseif ($action == 'setapilayer')
{
    if (GETPOSTISSET('modify_apilayer'))
    {
        dolibarr_set_const($db, 'MULTICURRENCY_APP_ID', GETPOST('MULTICURRENCY_APP_ID', 'alpha'));
        dolibarr_set_const($db, 'MULTICURRENCY_APP_SOURCE', GETPOST('MULTICURRENCY_APP_SOURCE', 'alpha'));
        //dolibarr_set_const($db, 'MULTICURRENCY_ALTERNATE_SOURCE', GETPOST('MULTICURRENCY_ALTERNATE_SOURCE', 'alpha'));
    }
    else
    {
        MultiCurrency::syncRates($conf->global->MULTICURRENCY_APP_ID);
    }
}


$TCurrency = array();
$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'multicurrency WHERE entity = '.$conf->entity;
$resql = $db->query($sql);
if ($resql)
{
	while ($obj = $db->fetch_object($resql))
	{
		$currency = new MultiCurrency($db);
		$currency->fetch($obj->rowid);
		$TCurrency[] = $currency;
	}
}


/*
 * View
 */

$form=new Form($db);

$page_name = "MultiCurrencySetup";

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = multicurrencyAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("ModuleSetup"), -1, "multicurrency");


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center">'.$langs->trans("Status").'</td>'."\n";
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE").'</td>';
print '<td align="center">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE", $arrval, $conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE);
}
print '</td></tr>';


print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_useOriginTx").'</td>';
print '<td align="center">';
if ($conf->use_javascript_ajax) {
    print ajax_constantonoff('MULTICURRENCY_USE_ORIGIN_TX');
} else {
    $arrval = array('0' => $langs->trans("No"), '1' => $langs->trans("Yes"));
    print $form->selectarray("MULTICURRENCY_USE_ORIGIN_TX", $arrval, $conf->global->MULTICURRENCY_USE_ORIGIN_TX);
}
print '</td></tr>';

// Online payment with currency on document. This option should be on by default.
if ($conf->global->MAIN_FEATURES_LEVEL >= 2)
{
    print '<tr class="oddeven">';
    print '<td>'.$langs->transnoentitiesnoconv("MULTICURRENCY_USE_CURRENCY_ON_DOCUMENT").'</td>';
    print '<td align="center">';
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
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_BUY_PRICE_IN_CURRENCY">';
print $form->selectyesno("MULTICURRENCY_BUY_PRICE_IN_CURRENCY",$conf->global->MULTICURRENCY_BUY_PRICE_IN_CURRENCY,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';
*/

/* TODO uncomment when the functionality will integrated

print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_modifyRateApplication").'</td>';
print '<td class="right">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_MODIFY_RATE_APPLICATION">';
print $form->selectarray('MULTICURRENCY_MODIFY_RATE_APPLICATION', array('PU_DOLIBARR' => 'PU_DOLIBARR', 'PU_CURRENCY' => 'PU_CURRENCY'), $conf->global->MULTICURRENCY_MODIFY_RATE_APPLICATION);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

*/

print '</table>';

print '<br>';

if (!empty($conf->global->MAIN_MULTICURRENCY_ALLOW_SYNCHRONIZATION))
{
    print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'" id="form_sync">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="setapilayer">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';

	$urlforapilayer='https://currencylayer.com';   //https://apilayer.net

	print '<tr class="liste_titre">';
	print '<td>'.$form->textwithpicto($langs->trans("CurrencyLayerAccount"), $langs->trans("CurrencyLayerAccount_help_to_synchronize", $urlforapilayer)).'</td>'."\n";
	print '<td class="right">';
	print '<textarea id="response" class="hideobject" name="response"></textarea>';
	print '<input type="submit" name="modify_apilayer" class="button" value="'.$langs->trans("Modify").'">';
	print '<input type="submit" id="bt_sync" name="bt_sync_apilayer" class="button" value="'.$langs->trans('Synchronize').'" />';
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td class="fieldrequired"><a target="_blank" href="'.$urlforapilayer.'">'.$langs->transnoentitiesnoconv("multicurrency_appId").'</a></td>';
	print '<td class="right">';
	print '<input type="text" name="MULTICURRENCY_APP_ID" value="'.$conf->global->MULTICURRENCY_APP_ID.'" size="28" />&nbsp;';
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("multicurrency_appCurrencySource").'</td>';
	print '<td class="right">';
	print '<input type="text" name="MULTICURRENCY_APP_SOURCE" value="'.$conf->global->MULTICURRENCY_APP_SOURCE.'" size="10" placeholder="USD" />&nbsp;'; // Default: USD
	print '</form>';
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

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$form->textwithpicto($langs->trans("CurrenciesUsed"), $langs->transnoentitiesnoconv("CurrenciesUsed_help_to_add")).'</td>'."\n";
print '<td align="center">'.$langs->trans("Rate").'</td>'."\n";
print '</tr>';

print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add_currency">';

print '<tr class="oddeven">';
print '<td>'.$form->selectCurrency('', 'code', 1).'</td>';
print '<td class="right">';
print '<input type="text" name="rate" value="" size="13" placeholder="'.$langs->trans('Rate').'" />&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
print '</td>';
print '</tr>';

print '</form>';

print '<tr class="oddeven">';
print '<td>'.$conf->currency.$form->textwithpicto(' ', $langs->trans("BaseCurrency")).'</td>';
print '<td class="right">1</td>';
print '</tr>';

foreach ($TCurrency as &$currency)
{
	if ($currency->code == $conf->currency) continue;

	print '<tr class="oddeven">';
	print '<td>'.$currency->code.' - '.$currency->name.'</td>';
	print '<td class="right">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update_currency">';
	print '<input type="hidden" name="fk_multicurrency" value="'.$currency->id.'">';
	print '1 '.$conf->currency.' = ';
	print '<input type="text" name="rate" value="'.($currency->rate->rate ? $currency->rate->rate : '').'" size="13" />&nbsp;'.$currency->code.'&nbsp;';
	print '<input type="submit" name="updatecurrency" class="button" value="'.$langs->trans("Modify").'">&nbsp;';
	print '<input type="submit" name="deletecurrency" class="button" value="'.$langs->trans("Delete").'">';
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

// End of page
llxFooter();
$db->close();
