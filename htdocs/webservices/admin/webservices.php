<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011 	   Juanjo Menent		<jmenent@2byte.es>
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
 *      \file       htdocs/webservices/admin/webservices.php
 *		\ingroup    webservices
 *		\brief      Page to setup webservices module
 */

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

$actionsave=GETPOST("save");

// Sauvegardes parametres
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'WEBSERVICES_KEY',trim(GETPOST("WEBSERVICES_KEY")),'chaine',0,'',$conf->entity);

    if ($i >= 1)
    {
        $db->commit();
        $mesg = "<font class=\"ok\">".$langs->trans("SetupSaved")."</font>";
    }
    else
    {
        $db->rollback();
        $mesg = "<font class=\"error\">".$langs->trans("Error")."</font>";
    }
}


/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("WebServicesSetup"),$linkback,'setup');

print $langs->trans("WebServicesDesc")."<br>\n";
print "<br>\n";

print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print "<table class=\"noborder\" width=\"100%\">";

print "<tr class=\"liste_titre\">";
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print "<tr class=\"impair\">";
print '<td class="fieldrequired">'.$langs->trans("KeyForWebServicesAccess")."</td>";
print "<td><input type=\"text\" class=\"flat\" name=\"WEBSERVICES_KEY\" value=\"". ($_POST["WEBSERVICES_KEY"]?$_POST["WEBSERVICES_KEY"]:$conf->global->WEBSERVICES_KEY) . "\" size=\"20\"></td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '</table>';

print '<br><center>';
print "<input type=\"submit\" name=\"save\" class=\"button\" value=\"".$langs->trans("Save")."\">";
print "</center>";

print '</form>';

dol_htmloutput_mesg($mesg);

print '<br><br>';


// WSDL
print '<u>'.$langs->trans("WSDLCanBeDownloadedHere").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/webservices/server_other.php?wsdl';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
if ($conf->product->enabled || $conf->service->enabled)
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_productorservice.php?wsdl';
	print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
if ($conf->societe->enabled)
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_thirdparty.php?wsdl';
	print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
if ($conf->facture->enabled)
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php?wsdl';
	print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
if ($conf->fournisseur->enabled)
{
    $url=DOL_MAIN_URL_ROOT.'/webservices/server_supplier_invoice.php?wsdl';
    print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
print '<br>';


// Endpoint
print '<u>'.$langs->trans("EndPointIs").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/webservices/server_other.php';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
if ($conf->product->enabled || $conf->service->enabled)
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_productorservice.php';
	print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
if ($conf->societe->enabled)
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_thirdparty.php';
	print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
if ($conf->facture->enabled)
{
	$url=DOL_MAIN_URL_ROOT.'/webservices/server_invoice.php';
	print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
if ($conf->fournisseur->enabled)
{
    $url=DOL_MAIN_URL_ROOT.'/webservices/server_supplier_invoice.php';
    print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
print '<br>';


$db->close();

llxFooter();
?>
