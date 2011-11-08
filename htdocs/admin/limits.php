<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *       \brief      Page de configuration des limites
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/price.lib.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
	$error=0;
	$MAXDEC=8;
	if ($_POST["MAIN_MAX_DECIMALS_UNIT"]  > $MAXDEC
	 || $_POST["MAIN_MAX_DECIMALS_TOT"]   > $MAXDEC
	 || $_POST["MAIN_MAX_DECIMALS_SHOWN"] > $MAXDEC)
	{
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorDecimalLargerThanAreForbidden",$MAXDEC).'</div>';
	}

	if ($_POST["MAIN_MAX_DECIMALS_UNIT"]  < 0
	 || $_POST["MAIN_MAX_DECIMALS_TOT"]   < 0
	 || $_POST["MAIN_MAX_DECIMALS_SHOWN"] < 0)
	{
		$langs->load("errors");
		$error++;
		$mesg='<div class="error">'.$langs->trans("ErrorNegativeValueNotAllowed").'</div>';
	}

	if ($_POST["MAIN_ROUNDING_RULE_TOT"])
	{
		if ($_POST["MAIN_ROUNDING_RULE_TOT"] * pow(10,$_POST["MAIN_MAX_DECIMALS_TOT"]) < 1)
		{
			$langs->load("errors");
			$error++;
			$mesg='<div class="error">'.$langs->trans("ErrorMAIN_ROUNDING_RULE_TOTCanMAIN_MAX_DECIMALS_TOT").'</div>';
		}
	}

	if (! $error)
	{
		dolibarr_set_const($db, "MAIN_MAX_DECIMALS_UNIT",   $_POST["MAIN_MAX_DECIMALS_UNIT"],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db, "MAIN_MAX_DECIMALS_TOT",    $_POST["MAIN_MAX_DECIMALS_TOT"],'chaine',0,'',$conf->entity);
		dolibarr_set_const($db, "MAIN_MAX_DECIMALS_SHOWN",  $_POST["MAIN_MAX_DECIMALS_SHOWN"],'chaine',0,'',$conf->entity);

		dolibarr_set_const($db, "MAIN_ROUNDING_RULE_TOT",   $_POST["MAIN_ROUNDING_RULE_TOT"],'chaine',0,'',$conf->entity);

		Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
		exit;
	}
}



/*
 * View
 */

$form=new Form($db);

llxHeader();

print_fiche_titre($langs->trans("LimitsSetup"),'','setup');


print $langs->trans("LimitsDesc")."<br>\n";
print "<br>\n";

if ($mesg) print $mesg.'<br>';

if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
	print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_MAX_DECIMALS_UNIT" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_UNIT . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
	print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_MAX_DECIMALS_TOT" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_TOT . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td><td><input class="flat" name="MAIN_MAX_DECIMALS_SHOWN" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_SHOWN . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
	print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_ROUNDING_RULE_TOT" size="3" value="' . $conf->global->MAIN_ROUNDING_RULE_TOT . '"></td></tr>';

    print '</table>';

    print '<br><center>';
    print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
    print '</center>';

    print '</form>';
    print '<br>';
}
else
{
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_UNIT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_UNIT.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $form->textwithpicto($langs->trans("MAIN_MAX_DECIMALS_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_TOT.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_SHOWN.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $form->textwithpicto($langs->trans("MAIN_ROUNDING_RULE_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_ROUNDING_RULE_TOT.'</td></tr>';

    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
}


// Show examples
print '<b>'.$langs->trans("ExamplesWithCurrentSetup").":</b><br>\n";

$s=2/7;$qty=1;$vat=0;
$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0);
print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
print " x ".$langs->trans("Quantity").": ".$qty;
print " - ".$langs->trans("VAT").": ".$vat.'%';
print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

$s=10/3;$qty=1;$vat=0;
$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0);
print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
print " x ".$langs->trans("Quantity").": ".$qty;
print " - ".$langs->trans("VAT").": ".$vat.'%';
print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

$s=10/3;$qty=2;$vat=0;
$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0);
print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
print " x ".$langs->trans("Quantity").": ".$qty;
print " - ".$langs->trans("VAT").": ".$vat.'%';
print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

$s=10/3;$qty=1;$vat=10;
$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0);
print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
print " x ".$langs->trans("Quantity").": ".$qty;
print " - ".$langs->trans("VAT").": ".$vat.'%';
print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

$s=10/3;$qty=2;$vat=10;
$tmparray=calcul_price_total(1,$qty*price2num($s,'MU'),0,$vat,0,0,0,'HT',0);
print $langs->trans("UnitPriceOfProduct").": ".price2num($s,'MU');
print " x ".$langs->trans("Quantity").": ".$qty;
print " - ".$langs->trans("VAT").": ".$vat.'%';
print " &nbsp; -> &nbsp; ".$langs->trans("TotalPriceAfterRounding").": ".$tmparray[0].' / '.$tmparray[1].' / '.$tmparray[2]."<br>\n";

$db->close();

llxFooter();
?>
