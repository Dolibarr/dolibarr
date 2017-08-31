<?php
/* Copyright (C) 2007-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin		<regis.houssin@capnetworks.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/admin/limits.php
 *       \brief      Page to setup limits
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';

$langs->load("companies");
$langs->load("products");
$langs->load("admin");

if (! $user->admin) accessforbidden();

$action = GETPOST('action','alpha');

if ($action == 'update')
{
    $error=0;
    $MAXDEC=8;
    if ($_POST["MAIN_MAX_DECIMALS_UNIT"]  > $MAXDEC
    || $_POST["MAIN_MAX_DECIMALS_TOT"]   > $MAXDEC
    || $_POST["MAIN_MAX_DECIMALS_SHOWN"] > $MAXDEC)
    {
        $error++;
	    setEventMessages($langs->trans("ErrorDecimalLargerThanAreForbidden",$MAXDEC), null, 'errors');
    }

    if ($_POST["MAIN_MAX_DECIMALS_UNIT"]  < 0
    || $_POST["MAIN_MAX_DECIMALS_TOT"]   < 0
    || $_POST["MAIN_MAX_DECIMALS_SHOWN"] < 0)
    {
        $langs->load("errors");
        $error++;
	    setEventMessages($langs->trans("ErrorNegativeValueNotAllowed"), null, 'errors');
    }

    if ($_POST["MAIN_ROUNDING_RULE_TOT"])
    {
        if ($_POST["MAIN_ROUNDING_RULE_TOT"] * pow(10,$_POST["MAIN_MAX_DECIMALS_TOT"]) < 1)
        {
            $langs->load("errors");
            $error++;
	        setEventMessages($langs->trans("ErrorMAIN_ROUNDING_RULE_TOTCanMAIN_MAX_DECIMALS_TOT"), null, 'errors');
        }
    }

    if (! $error)
    {
        dolibarr_set_const($db, "MAIN_MAX_DECIMALS_UNIT",   $_POST["MAIN_MAX_DECIMALS_UNIT"],'chaine',0,'',$conf->entity);
        dolibarr_set_const($db, "MAIN_MAX_DECIMALS_TOT",    $_POST["MAIN_MAX_DECIMALS_TOT"],'chaine',0,'',$conf->entity);
        dolibarr_set_const($db, "MAIN_MAX_DECIMALS_SHOWN",  $_POST["MAIN_MAX_DECIMALS_SHOWN"],'chaine',0,'',$conf->entity);

        dolibarr_set_const($db, "MAIN_ROUNDING_RULE_TOT",   $_POST["MAIN_ROUNDING_RULE_TOT"],'chaine',0,'',$conf->entity);

        header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
        exit;
    }
}



/*
 * View
*/

$form=new Form($db);

llxHeader();

print load_fiche_titre($langs->trans("LimitsSetup"),'','title_setup');


print $langs->trans("LimitsDesc")."<br>\n";
print "<br>\n";

if ($action == 'edit')
{
    print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';


    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_MAX_DECIMALS_UNIT" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_UNIT . '"></td></tr>';


    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_MAX_DECIMALS_TOT" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_TOT . '"></td></tr>';


    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td><td><input class="flat" name="MAIN_MAX_DECIMALS_SHOWN" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_SHOWN . '"></td></tr>';


    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_ROUNDING_RULE_TOT" size="3" value="' . $conf->global->MAIN_ROUNDING_RULE_TOT . '"></td></tr>';

    print '</table>';

    print '<br><div class="center">';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</div>';

    print '</form>';
    print '<br>';
}
else
{
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';


    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_UNIT.'</td></tr>';


    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_TOT.'</td></tr>';


    print '<tr class="oddeven"><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_SHOWN.'</td></tr>';


    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_ROUNDING_RULE_TOT.'</td></tr>';

    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
}


if (empty($mysoc->country_code))
{
	$langs->load("errors");
	$warnpicto=img_warning($langs->trans("WarningMandatorySetupNotComplete"));
	print '<br><a href="'.DOL_URL_ROOT.'/admin/company.php?mainmenu=home">'.$warnpicto.' '.$langs->trans("WarningMandatorySetupNotComplete").'</a>';
}
else
{

	// Show examples
	print '<b>'.$langs->trans("ExamplesWithCurrentSetup").":</b><br>\n";

	// Always show vat rates with vat 0
	$s=2/7;$qty=1;$vat=0;
	$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0,0,$mysoc);
	print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
	print " x ".$langs->trans("Quantity").": ".$qty;
	print " - ".$langs->trans("VAT").": ".$vat.'%';
	print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	$s=10/3;$qty=1;$vat=0;
	$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0,0,$mysoc);
	print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
	print " x ".$langs->trans("Quantity").": ".$qty;
	print " - ".$langs->trans("VAT").": ".$vat.'%';
	print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	$s=10/3;$qty=2;$vat=0;
	$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0, 0,$mysoc);
	print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
	print " x ".$langs->trans("Quantity").": ".$qty;
	print " - ".$langs->trans("VAT").": ".$vat.'%';
	print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";


	// Add vat rates examples specific to country
	$vat_rates=array();

	$sql="SELECT taux as vat_rate";
	$sql.=" FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_country as c";
	$sql.=" WHERE t.active=1 AND t.fk_pays = c.rowid AND c.code='".$mysoc->country_code."' AND t.taux <> 0";
	$sql.=" ORDER BY t.taux ASC";
	$resql=$db->query($sql);
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
	    foreach($vat_rates as $vat)
	    {
	        for ($qty=1; $qty<=2; $qty++)
	        {
	            $s=10/3;
	            $tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0, 0,$mysoc);
	            print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
	            print " x ".$langs->trans("Quantity").": ".$qty;
	            print " - ".$langs->trans("VAT").": ".$vat.'%';
	            print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";
	        }
	    }
	}
	else
	{
	    // More examples if not specific vat rate found
	    // This example must be kept for test purpose with current value because value used (2/7, 10/3, and vat 0, 10)
	    // were calculated to show all possible cases of rounding. If we change this, examples becomes useless or show the same rounding rule.

	    $s=10/3;$qty=1;$vat=10;
	    $tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0, 0,$mysoc);
	    print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
	    print " x ".$langs->trans("Quantity").": ".$qty;
	    print " - ".$langs->trans("VAT").": ".$vat.'%';
	    print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

	    $s=10/3;$qty=2;$vat=10;
	    $tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0, 0,$mysoc);
	    print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
	    print " x ".$langs->trans("Quantity").": ".$qty;
	    print " - ".$langs->trans("VAT").": ".$vat.'%';
	    print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

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


llxFooter();

$db->close();
