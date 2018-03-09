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
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment

require '../main.inc.php';

// Libraries
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/multicurrency.lib.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';


// Translations
$langs->load("admin");
$langs->load("multicurrency");

// Access control
if (! $user->admin) {
    accessforbidden();
}

// Parameters
$action = GETPOST('action', 'alpha');
/*
 * Actions
 */


if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_set_const($db, $code, GETPOST($code), 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, 0) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
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

	$submit = GETPOST('submit', 'alpha');

	if ($submit == $langs->trans('Modify'))
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
				$currency->updateRate($rate);
			}
		}
	}
	elseif ($submit == $langs->trans('Delete'))
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
elseif ($action == 'synchronize')
{
	$response = GETPOST('response');
	$response = json_decode($response);

	if ($response->success)
	{
		MultiCurrency::syncRates($response);
	}
	else
	{
		setEventMessages($langs->trans('multicurrency_syncronize_error', $response->error->info), null, 'errors');
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

$page_name = "MultiCurrencySetup";

llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = multicurrencyAdminPrepareHead();
dol_fiche_head($head, 'settings', $langs->trans("ModuleSetup"), -1, "multicurrency");

// Setup page goes here
$form=new Form($db);

$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";


print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE">';
print $form->selectyesno("MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE",$conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';



print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_useOriginTx").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_USE_ORIGIN_TX">';
print $form->selectyesno("MULTICURRENCY_USE_ORIGIN_TX",$conf->global->MULTICURRENCY_USE_ORIGIN_TX,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

/* TODO uncomment when the functionality will integrated

print '<tr class="oddeven">';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_buyPriceInCurrency").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
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
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
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
	$var=false;
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td>'.$form->textwithpicto($langs->trans("CurrencyLayerAccount"), $langs->trans("CurrencyLayerAccount_help_to_synchronize")).'</td>'."\n";
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="100">';
	print '<form id="form_sync" action="" method="POST">';
	print '<input type="hidden" name="action" value="synchronize" />';
	print '<textarea id="response" class="hideobject" name="response"></textarea>';
	print $langs->trans("Value").'&nbsp;<input type="button" id="bt_sync" class="button" onclick="javascript:getRates();" value="'.$langs->trans('Synchronize').'" />';
	print '</form>';
	print '</td></tr>';



	print '<tr class="oddeven">';
	print '<td><a target="_blank" href="https://currencylayer.com">'.$langs->transnoentitiesnoconv("multicurrency_appId").'</a></td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="400">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_MULTICURRENCY_APP_ID">';
	print '<input type="text" name="MULTICURRENCY_APP_ID" value="'.$conf->global->MULTICURRENCY_APP_ID.'" size="28" />&nbsp;';
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("multicurrency_appCurrencySource").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="400">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_MULTICURRENCY_APP_SOURCE">';
	print '<input type="text" name="MULTICURRENCY_APP_SOURCE" value="'.$conf->global->MULTICURRENCY_APP_SOURCE.'" size="10" placeholder="USD" />&nbsp;'; // Default: USD
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	print '<tr class="oddeven">';
	print '<td>'.$langs->transnoentitiesnoconv("multicurrency_alternateCurrencySource").'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="400">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="set_MULTICURRENCY_ALTERNATE_SOURCE">';
	print '<input type="text" name="MULTICURRENCY_ALTERNATE_SOURCE" value="'.$conf->global->MULTICURRENCY_ALTERNATE_SOURCE.'" size="10" placeholder="EUR" />&nbsp;'; // Example: EUR
	print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
	print '</form>';
	print '</td></tr>';

	print '</table>';
	print '<br>';
}


print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$form->textwithpicto($langs->trans("CurrenciesUsed"), $langs->transnoentitiesnoconv("CurrenciesUsed_help_to_add")).'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Rate").'</td>'."\n";


print '<tr class="oddeven">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add_currency">';
print '<td>'.$form->selectCurrency('', 'code').'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<input type="text" name="rate" value="" size="13" placeholder="'.$langs->trans('Rate').'" />&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
print '</td></form></tr>';


print '<tr class="oddeven">';
print '<td>'.$conf->currency.$form->textwithpicto(' ', $langs->trans("BaseCurrency")).'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">1';
print '</td></form></tr>';

foreach ($TCurrency as &$currency)
{
	if ($currency->code == $conf->currency) continue;

	print '<tr class="oddeven">';
	print '<td>'.$currency->code.' - '.$currency->name.'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="400">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update_currency">';
	print '<input type="hidden" name="fk_multicurrency" value="'.$currency->id.'">';
	print '1 '.$conf->currency.' = ';
	print '<input type="text" name="rate" value="'.($currency->rate->rate ? $currency->rate->rate : '').'" size="13" />&nbsp;'.$currency->code.'&nbsp;';
	print '<input type="submit" name="submit" class="button" value="'.$langs->trans("Modify").'">&nbsp;';
	print '<input type="submit" name="submit" class="button" value="'.$langs->trans("Delete").'">';
	print '</form>';

	print '</td></tr>';
}

print '</table>';



print '
	<script type="text/javascript">
 		function getRates()
		{
			$("#bt_sync").attr("disabled", true);
			var url_sync = "http://apilayer.net/api/live?access_key='.$conf->global->MULTICURRENCY_APP_ID.'&format=1'.(!empty($conf->global->MULTICURRENCY_APP_SOURCE) ? '&source='.$conf->global->MULTICURRENCY_APP_SOURCE : '').'";

			$.ajax({
				url: url_sync,
				dataType: "jsonp"
			}).done(function(response) {
				$("#response").val(JSON.stringify(response));
				$("#form_sync").submit();
			});
		}
	</script>
';

llxFooter();

$db->close();
