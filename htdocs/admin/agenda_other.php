<?php
/* Copyright (C) 2008-2015	Laurent Destailleur             <eldy@users.sourceforge.net>
 * Copyright (C) 2011		Regis Houssin                   <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2013  Juanjo Menent                   <jmenent@2byte.es>
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
 *	    \file       htdocs/admin/agenda_other.php
 *      \ingroup    agenda
 *      \brief      Autocreate actions for agenda module setup page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formactions.class.php';

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

if ($action == 'set')
{
	dolibarr_set_const($db, 'AGENDA_USE_EVENT_TYPE_DEFAULT', GETPOST('AGENDA_USE_EVENT_TYPE_DEFAULT'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_TYPE', GETPOST('AGENDA_DEFAULT_FILTER_TYPE'), 'chaine', 0, '', $conf->entity);
    dolibarr_set_const($db, 'AGENDA_DEFAULT_FILTER_STATUS', GETPOST('AGENDA_DEFAULT_FILTER_STATUS'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, 'AGENDA_DEFAULT_VIEW', GETPOST('AGENDA_DEFAULT_VIEW'), 'chaine', 0, '', $conf->entity);
}


/**
 * View
 */

$formactions=new FormActions($db);

llxHeader();

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("AgendaSetup"),$linkback,'title_setup');
print "<br>\n";



print '<form action="'.$_SERVER["PHP_SELF"].'" name="agenda">';
print '<input type="hidden" name="action" value="set">';

$head=agenda_prepare_head();

dol_fiche_head($head, 'other', $langs->trans("Agenda"), 0, 'action');

$var=true;

print '<table class="noborder allwidth">'."\n";
print '<tr class="liste_titre">'."\n";
print '<td>'.$langs->trans("Parameters").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'.$langs->trans("Value").'</td>'."\n";
print '</tr>'."\n";

// Manual or automatic
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
//print ajax_constantonoff('AGENDA_USE_EVENT_TYPE');	Do not use ajax here, we need to reload page to change other combo list
if (empty($conf->global->AGENDA_USE_EVENT_TYPE))
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
}
else
{
	print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_AGENDA_USE_EVENT_TYPE">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
}
print '</td></tr>'."\n";

if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
{
    $var=!$var;
    print '<tr '.$bc[$var].'>'."\n";
    print '<td>'.$langs->trans("AGENDA_USE_EVENT_TYPE_DEFAULT").'</td>'."\n";
    print '<td align="center">&nbsp;</td>'."\n";
    print '<td align="right" class="nowrap">'."\n";
    $formactions->select_type_actions($conf->global->AGENDA_USE_EVENT_TYPE_DEFAULT, "AGENDA_USE_EVENT_TYPE_DEFAULT", '', 0, 1);
    print '</td></tr>'."\n";
}

// AGENDA_DEFAULT_FILTER_TYPE
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_FILTER_TYPE").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right" class="nowrap">'."\n";
$formactions->select_type_actions($conf->global->AGENDA_DEFAULT_FILTER_TYPE, "AGENDA_DEFAULT_FILTER_TYPE", '', (empty($conf->global->AGENDA_USE_EVENT_TYPE) ? 1 : 0), 1);
print '</td></tr>'."\n";

// AGENDA_DEFAULT_FILTER_STATUS
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_FILTER_STATUS").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
$formactions->form_select_status_action('agenda',$conf->global->AGENDA_DEFAULT_FILTER_STATUS,1,'AGENDA_DEFAULT_FILTER_STATUS',1,2);
print '</td></tr>'."\n";

// AGENDA_DEFAULT_VIEW
$var=!$var;
print '<tr '.$bc[$var].'>'."\n";
print '<td>'.$langs->trans("AGENDA_DEFAULT_VIEW").'</td>'."\n";
print '<td align="center">&nbsp;</td>'."\n";
print '<td align="right">'."\n";
$tmplist=array('show_month'=>$langs->trans("ViewCal"), 'show_week'=>$langs->trans("ViewWeek"), 'show_day'=>$langs->trans("ViewDay"), 'show_list'=>$langs->trans("ViewList"), 'show_peruser'=>$langs->trans("ViewPerUser"));
print $form->selectarray('AGENDA_DEFAULT_VIEW', $tmplist, $conf->global->AGENDA_DEFAULT_VIEW);
print '</td></tr>'."\n";

print '</table>';

dol_fiche_end();

print '<div class="center"><input class="button" type="submit" name="save" value="'.dol_escape_htmltag($langs->trans("Save")).'"></div>';

print '</form>';


print "<br>";

llxFooter();

$db->close();
