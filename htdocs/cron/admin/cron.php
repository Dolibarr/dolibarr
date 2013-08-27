<?php
/* Copyright (C) 2004		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2013	Laurent Destailleur		<eldy@users.sourceforge.org>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2013		Florian Henry			<florian.henry@open-concept.pro>
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
 *      \file       cron/admin/cron.php
 *		\ingroup    cron
 */

// Dolibarr environment
$res = @include("../../main.inc.php"); // From htdocs directory
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/cron.lib.php';

$langs->load("admin");
$langs->load("cron");

if (! $user->admin)
	accessforbidden();

$actionsave=GETPOST("save");

// Sauvegardes parametres
if (!empty($actionsave))
{
	$i=0;

	$db->begin();

	$i+=dolibarr_set_const($db,'CRON_KEY',trim(GETPOST("CRON_KEY")),'chaine',0,'',0);

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
print_fiche_titre($langs->trans("CronSetup"),$linkback,'setup');

// Configuration header
$head = cronadmin_prepare_head();

dol_fiche_head($head,'setup',$langs->trans("Module2300Name"),0,'cron');

print "<br>\n";

print '<form name="agendasetupform" action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder" width="100%">';

print '<tr class="liste_titre">';
print "<td>".$langs->trans("Parameter")."</td>";
print "<td>".$langs->trans("Value")."</td>";
print "<td>&nbsp;</td>";
print "</tr>";

print '<tr class="impair">';
print '<td class="fieldrequired">'.$langs->trans("KeyForCronAccess").'</td>';
print '<td><input type="text" class="flat" id="CRON_KEY" name="CRON_KEY" value="'. (GETPOST('CRON_KEY')?GETPOST('CRON_KEY'):(! empty($conf->global->CRON_KEY)?$conf->global->CRON_KEY:'')) . '" size="40">';
if (! empty($conf->use_javascript_ajax))
	print '&nbsp;'.img_picto($langs->trans('Generate'), 'refresh', 'id="generate_token" class="linkobject"');
print '</td>';
print '<td>&nbsp;</td>';
print '</tr>';

print '</table>';

print '<br><center>';
print '<input type="submit" name="save" class="button" value="'.$langs->trans("Save").'">';
print '</center>';

print '</form>';

dol_fiche_end();

print '<br><br>';

print $langs->trans("UseMenuModuleToolsToAddCronJobs").'<br>';

print '<br><br>';

dol_print_cron_urls();


print '<br>';

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
			$("#CRON_KEY").val(token);
});
});
});';
	print '</script>';
}

llxFooter();
$db->close();