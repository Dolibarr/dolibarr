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
 * 	\ingroup	quickpriceupdate
 * 	\brief		This file is an example module setup page
 * 				Put some comments here
 */
// Dolibarr environment

require '../main.inc.php';

// Libraries
dol_include_once('/core/lib/admin.lib.php');
dol_include_once('/core/lib/multicurrency.lib.php');
dol_include_once('/multicurrency/class/multicurrency.class.php');

// Translations
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
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if ($action == 'add_currency')
{
	$code = GETPOST('code', 'alpha');
	$name = GETPOST('name', 'alpha');
	$rate = GETPOST('rate', 'alpha');
	
	$currency = new MultiCurrency($db);
	$currency->code = $code;
	$currency->name = $name;

	if ($currency->create($user) > 0)
	{
		if ($currency->addRate($rate)) setEventMessages($langs->trans('SuccessAddRate'), array());
		else setEventMessages($langs->trans('ErrorAddRateFail'), array(), 'errors');
	}
	else setEventMessages($langs->trans('ErrorAddCurrencyFail'), array());
}
elseif ($action == 'update_currency')
{
	$submit = GETPOST('submit', 'alpha');
	
	if ($submit == $langs->trans('Modify'))
	{
		$fk_multicurrency = GETPOST('fk_multicurrency', 'int');
		$rate = GETPOST('rate', 'float');
		$currency = new MultiCurrency($db);
		
		if ($currency->fetch($fk_multicurrency) > 0)
		{
			$currency->updateRate($rate);
		}	
	}
	elseif ($submit == $langs->trans('Delete'))
	{
		$fk_multicurrency = GETPOST('fk_multicurrency', 'int');
		$currency = new MultiCurrency($db);
		
		if ($currency->fetch($fk_multicurrency) > 0)
		{
			if ($currency->delete() > 0) setEventMessages($langs->trans('SuccessDeleteCurrency'), array());
			else setEventMessages($langs->trans('ErrorDeleteCurrencyFail'), array(), 'errors');
		}
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
$page_name = "multicurrency";
llxHeader('', $langs->trans($page_name));

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">'
    . $langs->trans("BackToModuleList") . '</a>';
print_fiche_titre($langs->trans($page_name), $linkback);

// Configuration header
$head = multicurrencyAdminPrepareHead();
dol_fiche_head(
    $head,
    'settings',
    $langs->trans("Module500000Name"),
    0,
    "multicurrency"
);

// Setup page goes here
$form=new Form($db);

$var=false;
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_useRateOnInvoiceDate").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_USE_RATE_ON_INVOICE_DATE">';
print $form->selectyesno("MULTICURRENCY_USE_RATE_ON_INVOICE_DATE",$conf->global->MULTICURRENCY_USE_RATE_ON_INVOICE_DATE,1);
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
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

$var=!$var;
print '<tr '.$bc[$var].'>';
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

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_modifyRateApplication").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_MODIFY_RATE_APPLICATION">';
print $form->selectarray('MULTICURRENCY_MODIFY_RATE_APPLICATION', array('PU_DOLIBARR' => 'PU_DOLIBARR', 'PU_CURRENCY' => 'PU_CURRENCY'));
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_appId").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_CURRENCY_APP_ID">';
print '<input type="text" name="CURRENCY_APP_ID" value="'.$conf->global->MULTICURRENCY_APP_ID.'" size="28" />&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<td>'.$langs->transnoentitiesnoconv("multicurrency_currencyFromToRate").'</td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="400">';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_MULTICURRENCY_FROM_TO_RATE">';
print '<input type="text" name="MULTICURRENCY_FROM_TO_RATE" value="'.$conf->global->MULTICURRENCY_FROM_TO_RATE.'" size="10" placeholder="USD-EUR-1" />&nbsp;'; // CURRENCY_BASE - CURRENCY_ENTITY - ID_ENTITY
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print '</form>';
print '</td></tr>';

print '</table>';

print '</form>';

print '<br />';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Currencies").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="center" width="100">'.$langs->trans("Rate").'</td>'."\n";

$var=!$var;
print '<tr '.$bc[$var].'>';
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="add_currency">';
print '<td><input type="text" name="code" value="" size="5" placeholder="'.$langs->trans('code').'" /> - <input type="text" name="name" value="" size="35" placeholder="'.$langs->trans('name').'" /></td>';
print '<td align="center" width="20">&nbsp;</td>';
print '<td align="right" width="300">';
print '<input type="text" name="rate" value="" size="13" placeholder="'.$langs->trans('rate').'" />&nbsp;';
print '<input type="submit" class="button" value="'.$langs->trans("Add").'">';
print '</td></form></tr>';

foreach ($TCurrency as &$currency)
{
	$var=!$var;
	print '<tr '.$bc[$var].'>';
	print '<td>'.$currency->code.' - '.$currency->name.'</td>';
	print '<td align="center" width="20">&nbsp;</td>';
	print '<td align="right" width="400">';
	print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update_currency">';
	print '<input type="hidden" name="fk_multicurrency" value="'.$currency->id.'">';
	print '<input type="text" name="rate" value="'.($currency->rate->rate ? $currency->rate->rate : '').'" size="13" />&nbsp;';
	print '<input type="submit" name="submit" class="button" value="'.$langs->trans("Modify").'">&nbsp;';
	print '<input type="submit" name="submit" class="button" value="'.$langs->trans("Delete").'">';
	print '</form>';
	print '</td></tr>';
}

print '</table>';

llxFooter();

$db->close();