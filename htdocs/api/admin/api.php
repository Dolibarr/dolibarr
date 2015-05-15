<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2015		Regis Houssin			<jfefe@aternatik.fr>
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
 *      \file       htdocs/api/admin/api.php
 *		\ingroup    api
 *		\brief      Page to setup api module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$actionsave=GETPOST("save");

// Sauvegardes parametres
if ($actionsave)
{
    $i=0;

    $db->begin();

    $i+=dolibarr_set_const($db,'API_KEY',trim(GETPOST("API_KEY")),'chaine',0,'',$conf->entity);

    if ($i >= 1)
    {
        $db->commit();
        setEventMessage($langs->trans("SetupSaved"));
    }
    else
    {
        $db->rollback();
        setEventMessage($langs->trans("Error"), 'errors');
    }
}


/*
 *	View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("ApiSetup"),$linkback,'title_setup');

print $langs->trans("ApiDesc")."<br>\n";
print "<br>\n";

print '<form name="apisetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="impair">';
print '<td class="fieldrequired">'.$langs->trans("KeyForApiAccess").'</td>';
print '<td><input type="text" class="flat" id="API_KEY" name="API_KEY" value="'. (GETPOST('API_KEY')?GETPOST('API_KEY'):(! empty($conf->global->API_KEY)?$conf->global->API_KEY:'')) . '" size="40">';
if (! empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';

print '<br><div class="center">';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print '<br><br>';

// API endpoint
print '<u>'.$langs->trans("ApiEndPointIs").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/public/api/';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
$url=DOL_MAIN_URL_ROOT.'/public/api/.json';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";

// Explorer
print '<u>'.$langs->trans("ApiExporerIs").':</u><br>';
$url=DOL_MAIN_URL_ROOT.'/public/api/explorer/index.html';
print img_picto('','object_globe.png').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";

print '<br>';


print '<br>';
print $langs->trans("OnlyActiveElementsAreExposed", DOL_URL_ROOT.'/admin/modules.php');

if (! empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#generate_token").click(function() {
            	$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: true
				},
				function(token) {
					$("#API_KEY").val(token);
				});
            });
    });';
	print '</script>';
}


llxFooter();
$db->close();
