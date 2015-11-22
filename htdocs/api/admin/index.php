<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015		Jean-François Ferry     <jfefe@aternatik.fr>
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
 *      \file       htdocs/api/admin/index.php
 *		\ingroup    api
 *		\brief      Page to setup Webservices REST module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$action=GETPOST("action");

//Activate ProfId
if ($action == 'setproductionmode')
{
	$status = GETPOST('status','alpha');

	if (dolibarr_set_const($db, 'API_PRODUCTION_MODE', $status, 'chaine', 0, '', $conf->entity) > 0)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ApiSetup"),$linkback,'title_setup');

print $langs->trans("ApiDesc")."<br>\n";
print "<br>\n";

//print '<form name="apisetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="impair">';
print '<td>'.$langs->trans("ApiProductionMode").'</td>';
$production_mode=(empty($conf->global->API_PRODUCTION_MODE)?false:true);
if ($production_mode)
{
    print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setproductionmode&value='.($i+1).'&status=0">';
    print img_picto($langs->trans("Activated"),'switch_on');
    print '</a></td>';
}
else
{
    print '<td align="center"><a href="'.$_SERVER['PHP_SELF'].'?action=setproductionmode&value='.($i+1).'&status=1">';
    print img_picto($langs->trans("Disabled"),'switch_off');
    print '</a></td>';
}
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';
print '<br><br>';

// Explorer
print '<u>'.$langs->trans("ApiExporerIs").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/api/admin/explorer.php';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";

// API endpoint
/*print '<u>'.$langs->trans("ApiEndPointIs").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/api/index.php/xxx/list';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
$url=DOL_MAIN_URL_ROOT.'/api/xxx/list.json';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
*/

print '<br>';
print '<br>';
print $langs->trans("OnlyActiveElementsAreExposed", DOL_URL_ROOT.'/admin/modules.php');



llxFooter();
$db->close();
