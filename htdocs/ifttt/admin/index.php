<?php
/* Copyright (C) 2004		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012-2018	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
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
 *      \file       htdocs/ifttt/admin/index.php
 *		\ingroup    api
 *		\brief      Page to setup IFTTT module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action', 'aZ09');


if ($action == 'set')
{
    $res1 = dolibarr_set_const($db, 'IFTTT_SERVICE_KEY', GETPOST('IFTTT_SERVICE_KEY', 'alpha'), 'chaine', 0, '', 0);
    $res2 = dolibarr_set_const($db, 'IFTTT_DOLIBARR_ENDPOINT_SECUREKEY', GETPOST('IFTTT_DOLIBARR_ENDPOINT_SECUREKEY', 'alpha'), 'chaine', 0, '', 0);

	if ($res1 > 0 && $res2)
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

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("IFTTTSetup"), $linkback, 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("IFTTTDesc")."</span><br>\n";
print "<br>\n";

print '<form name="apisetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print '<td>'.$langs->trans("Value")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("IFTTT_SERVICE_KEY").'</td>';
print '<td>';
print '<input type="text" name="IFTTT_SERVICE_KEY" value="'.$conf->global->IFTTT_SERVICE_KEY.'">';
print '</td>';
print '<td>'.$langs->trans("YouWillFindItOnYourIFTTTAccount").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("IFTTT_DOLIBARR_ENDPOINT_SECUREKEY").'</td>';
print '<td>';
print '<input type="text" name="IFTTT_DOLIBARR_ENDPOINT_SECUREKEY" value="'.$conf->global->IFTTT_DOLIBARR_ENDPOINT_SECUREKEY.'">';
print '</td>';
print '<td></td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print '<br><br>';



// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// Show message
$message='';
$url=$urlwithroot.'/public/ifttt/index.php?securekey='.$conf->global->IFTTT_DOLIBARR_ENDPOINT_SECUREKEY;
$message.=$langs->trans("UrlForIFTTT").':<br>';
$message.=img_picto('', 'object_globe.png').' <input type="text" class="quatrevingtpercent" id="endpointforifttt" name="endpointforifttt" value="'.$url.'">';
print $message;
print ajax_autoselect("endpointforifttt");
print '<br>';
print '<br>';


llxFooter();
$db->close();
