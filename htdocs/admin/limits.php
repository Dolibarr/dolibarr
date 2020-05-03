<?php
/* Copyright (C) 2007-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2018	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent		<jmenent@2byte.es>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'products', 'admin'));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'alpha');
$currencycode = GETPOST('currencycode', 'alpha');

$mainmaxdecimalsunit = 'MAIN_MAX_DECIMALS_UNIT'.(!empty($currencycode) ? '_'.$currencycode : '');
$mainmaxdecimalstot = 'MAIN_MAX_DECIMALS_TOT'.(!empty($currencycode) ? '_'.$currencycode : '');
$mainmaxdecimalsshown = 'MAIN_MAX_DECIMALS_SHOWN'.(!empty($currencycode) ? '_'.$currencycode : '');
$mainroundingruletot = 'MAIN_ROUNDING_RULE_TOT'.(!empty($currencycode) ? '_'.$currencycode : '');

if ($action == 'update')
{
	$error = 0;
	$MAXDEC = 8;
	if ($_POST[$mainmaxdecimalsunit] > $MAXDEC
	|| $_POST[$mainmaxdecimalstot] > $MAXDEC
	|| $_POST[$mainmaxdecimalsshown] > $MAXDEC)
    {
        $error++;
	    setEventMessages($langs->trans("ErrorDecimalLargerThanAreForbidden", $MAXDEC), null, 'errors');
    }

    if ($_POST[$mainmaxdecimalsunit].(!empty($currencycode) ? '_'.$currencycode : '') < 0
    || $_POST[$mainmaxdecimalstot] < 0
    || $_POST[$mainmaxdecimalsshown] < 0)
    {
        $langs->load("errors");
        $error++;
	    setEventMessages($langs->trans("ErrorNegativeValueNotAllowed"), null, 'errors');
    }

    if ($_POST[$mainroundingruletot])
    {
        if ($_POST[$mainroundingruletot] * pow(10, $_POST[$mainmaxdecimalstot]) < 1)
        {
            $langs->load("errors");
            $error++;
	        setEventMessages($langs->trans("ErrorMAIN_ROUNDING_RULE_TOTCanMAIN_MAX_DECIMALS_TOT"), null, 'errors');
        }
    }

    if (!$error)
    {
    	dolibarr_set_const($db, $mainmaxdecimalsunit, $_POST[$mainmaxdecimalsunit], 'chaine', 0, '', $conf->entity);
    	dolibarr_set_const($db, $mainmaxdecimalstot, $_POST[$mainmaxdecimalstot], 'chaine', 0, '', $conf->entity);
    	dolibarr_set_const($db, $mainmaxdecimalsshown, $_POST[$mainmaxdecimalsshown], 'chaine', 0, '', $conf->entity);

    	dolibarr_set_const($db, $mainroundingruletot, $_POST[$mainroundingruletot], 'chaine', 0, '', $conf->entity);

        header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup".(!empty($currencycode) ? '&currencycode='.$currencycode : ''));
        exit;
    }
}


/*
 * View
 */

$form = new Form($db);

llxHeader();

print load_fiche_titre($langs->trans("LimitsSetup"), '', 'title_setup');

$currencycode = (!empty($currencycode) ? $currencycode : $conf->currency);
$aCurrencies = array($conf->currency); // Default currency always first position

if (!empty($conf->multicurrency->enabled) && !empty($conf->global->MULTICURRENCY_USE_LIMIT_BY_CURRENCY))
{
	require_once DOL_DOCUMENT_ROOT.'/core/lib/multicurrency.lib.php';

	$sql = 'SELECT rowid, code FROM '.MAIN_DB_PREFIX.'multicurrency';
	$sql .= ' WHERE entity = '.$conf->entity;
	$sql .= ' AND code != "'.$conf->currency.'"'; // Default currency always first position
	$resql = $db->query($sql);
	if ($resql)
	{
		while ($obj = $db->fetch_object($resql))
		{
			$aCurrencies[] = $obj->code;
		}
	}

	if (!empty($aCurrencies) && count($aCurrencies) > 1)
	{
		$head = multicurrencyLimitPrepareHead($aCurrencies);
		dol_fiche_head($head, $currencycode, '', -1, "multicurrency");
	}
}

print '<span class="opacitymedium">'.$langs->trans("LimitsDesc")."</span><br>\n";
print "<br>\n";

if ($action == 'edit')
{
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.newToken().'">';
    print '<input type="hidden" name="action" value="update">';
    if (!empty($conf->multicurrency->enabled) && !empty($conf->global->MULTICURRENCY_USE_LIMIT_BY_CURRENCY)) {
    	print '<input type="hidden" name="currencycode" value="'.$currencycode.'">';
    }

    clearstatcache();

    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameters").'</td><td>'.$langs->trans("Value").'</td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"), $langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="'.$mainmaxdecimalsunit.'" size="3" value="'.(isset($conf->global->$mainmaxdecimalsunit) ? $conf->global->$mainmaxdecimalsunit : $conf->global->MAIN_MAX_DECIMALS_UNIT).'"></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"), $langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="'.$mainmaxdecimalstot.'" size="3" value="'.(isset($conf->global->$mainmaxdecimalstot) ? $conf->global->$mainmaxdecimalstot : $conf->global->MAIN_MAX_DECIMALS_TOT).'"></td></tr>';

    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td>';
    print '<td><input class="flat" name="'.$mainmaxdecimalsshown.'" size="3" value="'.(isset($conf->global->$mainmaxdecimalsshown) ? $conf->global->$mainmaxdecimalsshown : $conf->global->MAIN_MAX_DECIMALS_SHOWN).'"></td></tr>';

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"), $langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="'.$mainroundingruletot.'" size="3" value="'.(isset($conf->global->$mainroundingruletot) ? $conf->global->$mainroundingruletot : $conf->global->MAIN_ROUNDING_RULE_TOT).'"></td></tr>';

    print '</table>';

    print '<br>';
    print '<div class="center">';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</div>';
	print '<br>';

    print '</form>';
    print '<br>';
}
else
{
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

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
    print '</td><td align="right">'.(isset($conf->global->$mainroundingruletot) ? $conf->global->$mainroundingruletot : $conf->global->MAIN_ROUNDING_RULE_TOT).'</td></tr>';

    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit'.(!empty($currencycode) ? '&currencycode='.$currencycode : '').'">'.$langs->trans("Modify").'</a>';
    print '</div>';
}

if (!empty($conf->multicurrency->enabled) && !empty($conf->global->MULTICURRENCY_USE_LIMIT_BY_CURRENCY))
{
	if (!empty($aCurrencies) && count($aCurrencies) > 1)
	{
		dol_fiche_end();
	}
}

if (empty($mysoc->country_code))
{
	$langs->load("errors");
	$warnpicto = img_warning($langs->trans("WarningMandatorySetupNotComplete"));
	print '<br><a href="'.DOL_URL_ROOT.'/admin/company.php?mainmenu=home">'.$warnpicto.' '.$langs->trans("WarningMandatorySetupNotComplete").'</a>';
}
else
{
	// Show examples
	print load_fiche_titre($langs->trans("ExamplesWithCurrentSetup"), '', '');

	print '<span class="opacitymedium">'.$langs->trans("Format").':</span> '.price(price2num(1234.56789, 'MT'), 0, $langs, 1, -1, -1, $currencycode)."<br>\n";

	// Always show vat rates with vat 0
	$s = 2 / 7; $qty = 1; $vat = 0;
	$tmparray = calcul_price_total(1, $qty * price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	print " x ".$langs->trans("Quantity").": ".$qty;
	print " - ".$langs->trans("VAT").": ".$vat.'%';
	print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	$s = 10 / 3; $qty = 1; $vat = 0;
	$tmparray = calcul_price_total(1, $qty * price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	print " x ".$langs->trans("Quantity").": ".$qty;
	print " - ".$langs->trans("VAT").": ".$vat.'%';
	print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	$s = 10 / 3; $qty = 2; $vat = 0;
	$tmparray = calcul_price_total(1, $qty * price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	print " x ".$langs->trans("Quantity").": ".$qty;
	print " - ".$langs->trans("VAT").": ".$vat.'%';
	print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	// Add vat rates examples specific to country
	$vat_rates = array();

	$sql = "SELECT taux as vat_rate";
	$sql .= " FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
	$sql .= " WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$mysoc->country_code."' AND t.taux <> 0";
	$sql .= " ORDER BY t.taux ASC";
	$resql = $db->query($sql);
	if ($resql)
	{
	    $num = $db->num_rows($resql);
	    if ($num)
	    {
	        for ($i = 0; $i < $num; $i++)
	        {
	            $obj = $db->fetch_object($resql);
	            $vat_rates[$i] = $obj->vat_rate;
	        }
	    }
	}
	else dol_print_error($db);

	if (count($vat_rates))
	{
	    foreach ($vat_rates as $vat)
	    {
	        for ($qty = 1; $qty <= 2; $qty++)
	        {
	            $s = 10 / 3;
	            $tmparray = calcul_price_total(1, $qty * price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	            print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	            print " x ".$langs->trans("Quantity").": ".$qty;
	            print " - ".$langs->trans("VAT").": ".$vat.'%';
	            print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";
	        }
	    }
	}
	else
	{
	    // More examples if not specific vat rate found
	    // This example must be kept for test purpose with current value because value used (2/7, 10/3, and vat 0, 10)
	    // were calculated to show all possible cases of rounding. If we change this, examples becomes useless or show the same rounding rule.

	    $s = 10 / 3; $qty = 1; $vat = 10;
	    $tmparray = calcul_price_total(1, $qty * price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	    print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	    print " x ".$langs->trans("Quantity").": ".$qty;
	    print " - ".$langs->trans("VAT").": ".$vat.'%';
	    print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	    $s = 10 / 3; $qty = 2; $vat = 10;
	    $tmparray = calcul_price_total(1, $qty * price2num($s, 'MU'), 0, $vat, 0, 0, 0, 'HT', 0, 0, $mysoc);
	    print '<span class="opacitymedium">'.$langs->trans("UnitPriceOfProduct").":</span> ".price2num($s, 'MU');
	    print " x ".$langs->trans("Quantity").": ".$qty;
	    print " - ".$langs->trans("VAT").": ".$vat.'%';
	    print ' &nbsp; -> &nbsp; <span class="opacitymedium">'.$langs->trans("TotalPriceAfterRounding").":</span> ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";
	}

	// Important: can debug rounding, to simulate the rounded total
	/*
	print '<br><b>'.$langs->trans("VATRoundedByLine").' ('.$langs->trans("DolibarrDefault").')</b><br>';

	foreach($vat_rates as $vat)
	{
		for ($qty=1; $qty<=2; $qty++)
		{
			$s1=10/3;
			$s2=2/7;

			// Round by line
			$tmparray1=calcul_price_total(1,$qty*price2num($s1,'MU'),0,$vat,0,0,0,'HT',0, 0,$mysoc);
			$tmparray2=calcul_price_total(1,$qty*price2num($s2,'MU'),0,$vat,0,0,0,'HT',0, 0,$mysoc);
			$total_ht = $tmparray1[0] + $tmparray2[0];
			$total_tva = $tmparray1[1] + $tmparray2[1];
			$total_ttc = $tmparray1[2] + $tmparray2[2];

			print $langs->trans("UnitPriceOfProduct").": ".(price2num($s1,'MU') + price2num($s2,'MU'));
			print " x ".$langs->trans("Quantity").": ".$qty;
			print " - ".$langs->trans("VAT").": ".$vat.'%';
			print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$total_ht.' / '.$total_tva.' / '.$total_ttc."<br>\n";
		}
	}

	print '<br><b>'.$langs->trans("VATRoundedOnTotal").'</b><br>';

	foreach($vat_rates as $vat)
	{
		for ($qty=1; $qty<=2; $qty++)
		{
			$s1=10/3;
			$s2=2/7;

			// Global round
			$subtotal_ht = (($qty*price2num($s1,'MU')) + ($qty*price2num($s2,'MU')));
			$tmparray3=calcul_price_total(1,$subtotal_ht,0,$vat,0,0,0,'HT',0, 0,$mysoc);
			$total_ht = $tmparray3[0];
			$total_tva = $tmparray3[1];
			$total_ttc = $tmparray3[2];

			print $langs->trans("UnitPriceOfProduct").": ".price2num($s1+$s2,'MU');
			print " x ".$langs->trans("Quantity").": ".$qty;
			print " - ".$langs->trans("VAT").": ".$vat.'%';
			print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$total_ht.' / '.$total_tva.' / '.$total_ttc."<br>\n";
		}
	}
	*/
}

// End of page
llxFooter();
$db->close();
