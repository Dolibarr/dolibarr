<?php
/* Copyright (C) 2008-2010	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2011		Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012  Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/agenda.php
 *      \ingroup    agenda
 *      \brief      Autocreate actions for agenda module setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';

if (!$user->admin)
    accessforbidden();

$langs->load("admin");
$langs->load("other");

$action = GETPOST('action','alpha');
$cancel = GETPOST('cancel','alpha');


/*
 *	Actions
 */

if (preg_match('/set_(.*)/',$action,$reg))
{
	$code=$reg[1];
	$value=(GETPOST($code) ? GETPOST($code) : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}

if (preg_match('/del_(.*)/',$action,$reg))
{
	$code=$reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0)
	{
		Header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
	else
	{
		dol_print_error($db);
	}
}


/**
 * View
 */

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($langs->trans("AgendaSetup"),$linkback,'setup');
print "<br>\n";


$head=agenda_prepare_head();

dol_fiche_head($head, 'other', $langs->trans("Agenda"));

print_titre($langs->trans("OtherOptions"));

$var=true;

print '<table class="noborder allwidth">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>'."\n";
print '<td align="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

// Manual or automatic
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE").'</td>'."\n";
print '<td align="center" width="20">&nbsp;</td>'."\n";

print '<td align="center" width="100">'."\n";
if ($conf->use_javascript_ajax)
{
	print ajax_constantonoff('AGENDA_USE_EVENT_TYPE');
}
else
{
	if($conf->global->AGENDA_USE_EVENT_TYPE == 0)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Disabled"),'off').'</a>';
	}
	else if($conf->global->BUSINESS_VISIBLE_TO_ALL_BY_DEFAULT == 1)
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Enabled"),'on').'</a>';
	}
}
print '</td></tr>'."\n";

print '</table>';

dol_fiche_end();

print "<br>";

dol_htmloutput_mesg($mesg);

llxFooter();

$db->close();
?>
