<?php
/* Copyright (C) 2015 		Alexandre Spangaro <aspangaro.dolibarr@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *     \file 	htdocs/admin/skype.php
 *     \ingroup Skype
 *     \brief 	Skype module setup page
 */
require('../main.inc.php');
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';

$langs->load("admin");

if (! $user->admin)
	accessforbidden();

$action = GETPOST('action', 'alpha');

/*
 * Actions
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

else if (preg_match('/del_(.*)/',$action,$reg))
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

/*
 * View
 */
llxHeader('', $langs->trans('Parameters'));

$form = new Form($db);

dol_htmloutput_mesg($mesg);

// Subheader
$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("SkypeSetup"), $linkback);

// Configuration header
$h = 0;
$head = array ();

$head[$h][0] = dol_buildpath('/admin/skype.php', 1);
$head[$h][1] = $langs->trans("Configuration");
$head[$h][2] = 'parameters';
$h ++;

dol_fiche_head($head, 'parameters', $langs->trans("Skype"), 0, "skype");

if (! $conf->use_javascript_ajax) {
	print '<form method="post" action="' . $_SERVER["PHP_SELF"] . '" enctype="multipart/form-data">';
	print '<input type="hidden" name="token" value="' . $_SESSION['newtoken'] . '">';
	print '<input type="hidden" name="action" value="setvar">';
}

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="3">' . $langs->trans('Parameters') . '</td>';
print "</tr>\n";

$var = ! $var;

print '<tr ' . $bc[$var] . '><td>' . $langs->trans("SkypeDeactivateInUser") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('SKYPE_DEACTIVATE_IN_USER');
}
else
{
	if (empty($conf->global->SKYPE_DEACTIVATE_IN_USER))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_SKYPE_DEACTIVATE_IN_USER">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_SKYPE_DEACTIVATE_IN_USER">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
	}
}
print '</td>';
print '</tr>';

print '<tr ' . $bc[$var] . '><td>' . $langs->trans("SkypeDeactivateInContact") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('SKYPE_DEACTIVATE_IN_CONTACT');
}
else
{
	if (empty($conf->global->SKYPE_DEACTIVATE_IN_CONTACT))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_SKYPE_DEACTIVATE_IN_CONTACT">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_SKYPE_DEACTIVATE_IN_CONTACT">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
	}
}
print '</td>';
print '</tr>';

print '<tr ' . $bc[$var] . '><td>' . $langs->trans("SkypeDeactivateInMember") . '</td>';
print '<td align="left">';
if ($conf->use_javascript_ajax) {
	print ajax_constantonoff('SKYPE_DEACTIVATE_IN_MEMBER');
}
else
{
	if (empty($conf->global->SKYPE_DEACTIVATE_IN_MEMBER))
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_SKYPE_DEACTIVATE_IN_MEMBER">'.img_picto($langs->trans("Disabled"),'switch_off').'</a>';
	}
	else
	{
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_SKYPE_DEACTIVATE_IN_MEMBER">'.img_picto($langs->trans("Enabled"),'switch_on').'</a>';
	}
}
print '</td>';
print '</tr>';

print "</table>\n";

dol_fiche_end();

print '</form>';

llxFooter();
$db->close();
