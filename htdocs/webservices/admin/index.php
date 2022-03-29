<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2010	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@inodbox.com>
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
 *      \file       htdocs/webservices/admin/index.php
 *		\ingroup    webservices
 *		\brief      Page to setup webservices module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");

if (!$user->admin)
	accessforbidden();

$actionsave = GETPOST("save");

// Sauvegardes parametres
if ($actionsave)
{
	$i = 0;

	$db->begin();

	$i += dolibarr_set_const($db, 'WEBSERVICES_KEY', GETPOST("WEBSERVICES_KEY"), 'chaine', 0, '', $conf->entity);

	if ($i >= 1)
	{
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
	else {
		$db->rollback();
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 *	View
 */

llxHeader();

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';

print load_fiche_titre($langs->trans("WebServicesSetup"), $linkback, 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("WebServicesDesc")."</span><br>\n";
print "<br>\n";

print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder centpercent">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
//print "<td>".$langs->trans("Examples")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="oddeven">';
print '<td class="fieldrequired">'.$langs->trans("KeyForWebServicesAccess").'</td>';
print '<td><input type="text" class="flat" id="WEBSERVICES_KEY" name="WEBSERVICES_KEY" value="'.(GETPOST('WEBSERVICES_KEY') ?GETPOST('WEBSERVICES_KEY') : (!empty($conf->global->WEBSERVICES_KEY) ? $conf->global->WEBSERVICES_KEY : '')).'" size="40">';
if (!empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';

print '<br><div class="center">';
print '<input type="submit" name="save" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

print '<br><br>';

// Webservices list
$webservices = array(
		'user'				=> '',
		'thirdparty'		=> '!empty($conf->societe->enabled)',
		'contact'			=> '!empty($conf->societe->enabled)',
		'productorservice'	=> '(!empty($conf->product->enabled) || !empty($conf->service->enabled))',
		'order'				=> '!empty($conf->commande->enabled)',
		'invoice'			=> '!empty($conf->facture->enabled)',
		'supplier_invoice'	=> '!empty($conf->fournisseur->enabled)',
		'actioncomm'		=> '!empty($conf->agenda->enabled)',
		'category'			=> '!empty($conf->categorie->enabled)',
		'project'			=> '!empty($conf->projet->enabled)',
		'other'				=> ''
);


// WSDL
print '<u>'.$langs->trans("WSDLCanBeDownloadedHere").':</u><br>';
foreach ($webservices as $name => $right)
{
	if (!empty($right) && !verifCond($right)) continue;
	$url = DOL_MAIN_URL_ROOT.'/webservices/server_'.$name.'.php?wsdl';
	print img_picto('', 'globe').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
print '<br>';


// Endpoint
print '<u>'.$langs->trans("EndPointIs").':</u><br>';
foreach ($webservices as $name => $right)
{
	if (!empty($right) && !verifCond($right)) continue;
	$url = DOL_MAIN_URL_ROOT.'/webservices/server_'.$name.'.php';
	print img_picto('', 'globe').' <a href="'.$url.'" target="_blank">'.$url."</a><br>\n";
}
print '<br>';


print '<br>';
print $langs->trans("OnlyActiveElementsAreShown", DOL_URL_ROOT.'/admin/modules.php');

if (!empty($conf->use_javascript_ajax))
{
	print "\n".'<script type="text/javascript">';
	print '$(document).ready(function () {
            $("#generate_token").click(function() {
            	$.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: true
				},
				function(token) {
					$("#WEBSERVICES_KEY").val(token);
				});
            });
    });';
	print '</script>';
}

// End of page
llxFooter();
$db->close();
