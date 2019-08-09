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
 *      \file       htdocs/api/admin/index.php
 *		\ingroup    api
 *		\brief      Page to setup Webservices REST module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

// Load translation files required by the page
$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$action=GETPOST('action', 'aZ09');

//Activate ProfId
if ($action == 'setproductionmode')
{
	$status = GETPOST('status', 'alpha');

	if (dolibarr_set_const($db, 'API_PRODUCTION_MODE', $status, 'chaine', 0, '', 0) > 0)
	{
		$error=0;

		if ($status == 1)
		{
			$result = dol_mkdir($conf->api->dir_temp);
			if ($result < 0)
			{
				setEventMessages($langs->trans("ErrorFailedToCreateDir", $conf->api->dir_temp), null, 'errors');
				$error++;
			}
		}
		else
		{
			// Delete the cache file otherwise it does not update
			$result = dol_delete_file($conf->api->dir_temp.'/routes.php');
			if ($result < 0)
			{
				setEventMessages($langs->trans("ErrorFailedToDeleteFile", $conf->api->dir_temp.'/routes.php'), null, 'errors');
				$error++;
			}
		}

	    if (!$error)
	    {
    		header("Location: ".$_SERVER["PHP_SELF"]);
	   	    exit;
	    }
	}
	else
	{
		dol_print_error($db);
	}
}

dol_mkdir(DOL_DATA_ROOT.'/api/temp');		// May have been deleted by a purge


/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ApiSetup"), $linkback, 'title_setup');

print $langs->trans("ApiDesc")."<br>\n";
print "<br>\n";

//print '<form name="apisetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print '<td align="center">'.$langs->trans("Value")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="impair">';
print '<td>'.$langs->trans("ApiProductionMode").'</td>';
$production_mode=(empty($conf->global->API_PRODUCTION_MODE)?false:true);
if ($production_mode)
{
    print '<td align="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setproductionmode&value='.($i+1).'&status=0">';
    print img_picto($langs->trans("Activated"), 'switch_on');
    print '</a></td>';
}
else
{
    print '<td align="center"><a class="reposition" href="'.$_SERVER['PHP_SELF'].'?action=setproductionmode&value='.($i+1).'&status=1">';
    print img_picto($langs->trans("Disabled"), 'switch_off');
    print '</a></td>';
}
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';
print '<br><br>';

// Define $urlwithroot
$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// Show message
$message='';
$url=$urlwithroot.'/api/index.php/login?login=<strong>auserlogin</strong>&password=<strong>thepassword</strong>[&reset=1]';
$message.=$langs->trans("UrlToGetKeyToUseAPIs").':<br>';
$message.=img_picto('', 'object_globe.png').' '.$url;
print $message;
print '<br>';
print '<br>';

// Explorer
print '<u>'.$langs->trans("ApiExporerIs").':</u><br>';
if (dol_is_dir(DOL_DOCUMENT_ROOT.'/includes/restler/framework/Luracast/Restler/explorer'))
{
    $url=DOL_MAIN_URL_ROOT.'/api/index.php/explorer';
    print img_picto('', 'object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
else
{
    print $langs->trans("NotAvailableWithThisDistribution");
}

llxFooter();
$db->close();
