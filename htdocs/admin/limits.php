<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/admin/limits.php
        \brief      Page de configuration des limites
        \version    $Id$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");

if (!$user->admin)
  accessforbidden();


if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
	$MAXDEC=8;
	if ($_POST["MAIN_MAX_DECIMALS_UNIT"]  > $MAXDEC
	 || $_POST["MAIN_MAX_DECIMALS_TOT"]   > $MAXDEC
	 || $_POST["MAIN_MAX_DECIMALS_SHOWN"] > $MAXDEC)
	{
		$mesg='<div class="error">'.$langs->trans("ErrorDecimalLargerThanAreForbidden",$MAXDEC).'</div>';
	}
	else
	{
		dolibarr_set_const($db, "MAIN_MAX_DECIMALS_UNIT",   $_POST["MAIN_MAX_DECIMALS_UNIT"]);
		dolibarr_set_const($db, "MAIN_MAX_DECIMALS_TOT",    $_POST["MAIN_MAX_DECIMALS_TOT"]);
		dolibarr_set_const($db, "MAIN_MAX_DECIMALS_SHOWN",  $_POST["MAIN_MAX_DECIMALS_SHOWN"]);
		dolibarr_set_const($db, "MAIN_DISABLE_PDF_COMPRESSION", $_POST["MAIN_DISABLE_PDF_COMPRESSION"]);

		Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
		exit;
	}
}


$html=new Form($db);

llxHeader();

print_fiche_titre($langs->trans("LimitsSetup"),'','setup');


print $langs->trans("LimitsDesc")."<br>\n";
print "<br>\n";

if ($mesg) print $mesg.'<br>';

if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
    print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
    print '<input type="hidden" name="action" value="update">';

    clearstatcache();
    $var=true;

    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
	print $html->textwithhelp($langs->trans("MAIN_MAX_DECIMALS_UNIT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_MAX_DECIMALS_UNIT" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_UNIT . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
	print $html->textwithhelp($langs->trans("MAIN_MAX_DECIMALS_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td><input class="flat" name="MAIN_MAX_DECIMALS_TOT" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_TOT . '"></td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td><td><input class="flat" name="MAIN_MAX_DECIMALS_SHOWN" size="3" value="' . $conf->global->MAIN_MAX_DECIMALS_SHOWN . '"></td></tr>';

    /*
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_PDF_COMPRESSION").'</td><td>';
	print $html->selectyesno('MAIN_DISABLE_PDF_COMPRESSION',$conf->global->MAIN_DISABLE_PDF_COMPRESSION);
    print '</td></tr>';
	*/

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
    print $html->textwithhelp($langs->trans("MAIN_MAX_DECIMALS_UNIT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_UNIT.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>';
    print $html->textwithhelp($langs->trans("MAIN_MAX_DECIMALS_TOT"),$langs->trans("ParameterActiveForNextInputOnly"));
    print '</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_TOT.'</td></tr>';

    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAX_DECIMALS_SHOWN").'</td><td align="right">'.$conf->global->MAIN_MAX_DECIMALS_SHOWN.'</td></tr>';

    /*
    $var=!$var;
    print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_PDF_COMPRESSION").'</td><td align="right">'.yn($conf->global->MAIN_DISABLE_PDF_COMPRESSION).'</td></tr>';
	*/

    print '</table>';

    print '<div class="tabsAction">';
    print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';
    print '</div>';
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
