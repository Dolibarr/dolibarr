<?php
/* Copyright (C) 2019       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/admin/openinghours.php
 *	\ingroup    core
 *	\brief      Setup page to configure opening hours
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';

$action=GETPOST('action', 'aZ09');
$contextpage=GETPOST('contextpage', 'aZ')?GETPOST('contextpage', 'aZ'):'adminaccoutant';   // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (! $user->admin) accessforbidden();

$error=0;


/*
 * Actions
 */

$parameters=array();
$reshook=$hookmanager->executeHooks('doActions', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

if ( ($action == 'update' && ! GETPOST("cancel", 'alpha'))
|| ($action == 'updateedit') )
{
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_MONDAY", GETPOST("monday", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_TUESDAY", GETPOST("tuesday", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_WEDNESDAY", GETPOST("wednesday", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_THURSDAY", GETPOST("thursday", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_FRIDAY", GETPOST("friday", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_SATURDAY", GETPOST("saturday", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_OPENINGHOURS_SUNDAY", GETPOST('sunday', 'alpha'), 'chaine', 0, '', $conf->entity);

	if ($action != 'updateedit' && ! $error)
	{
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$help_url='';
llxHeader('', $langs->trans("CompanyFoundation"), $help_url);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

dol_fiche_head($head, 'openinghours', $langs->trans("Company"), -1, 'company');

$form=new Form($db);

print '<span class="opacitymedium">'.$langs->trans("OpeningHoursDesc")."</span><br>\n";
print "<br>\n";

if ($action == 'edit' || $action == 'updateedit')
{
	/**
	 * Edit parameters
	 */
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Day").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

	print '<tr class="oddeven"><td><label for="monday">'.$langs->trans("Monday").'</label></td><td>';
	print '<input name="monday" id="monday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_MONDAY?$conf->global->MAIN_INFO_OPENINGHOURS_MONDAY: GETPOST("monday", 'alpha')) . '" autofocus="autofocus"></td></tr>'."\n";

    print '<tr class="oddeven"><td><label for="tuesday">'.$langs->trans("Tuesday").'</label></td><td>';
    print '<input name="tuesday" id="tuesday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY?$conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY: GETPOST("tuesday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td><label for="wednesday">'.$langs->trans("Wednesday").'</label></td><td>';
    print '<input name="wednesday" id="wednesday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY?$conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY: GETPOST("wednesday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td><label for="thursday">'.$langs->trans("Thursday").'</label></td><td>';
    print '<input name="thursday" id="thursday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY?$conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY: GETPOST("thursday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td><label for="friday">'.$langs->trans("Friday").'</label></td><td>';
    print '<input name="friday" id="friday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY?$conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY: GETPOST("friday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td><label for="saturday">'.$langs->trans("Saturday").'</label></td><td>';
    print '<input name="saturday" id="saturday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY?$conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY: GETPOST("saturday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td><label for="sunday">'.$langs->trans("Sunday").'</label></td><td>';
    print '<input name="sunday" id="sunday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY?$conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY: GETPOST("sunday", 'alpha')) . '"></td></tr>'."\n";

    print '</table>';

	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
	print '</div>';
	print '<br>';

	print '</form>';
}
else
{
	/*
	 * Show parameters
	 */

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Day").'</td><td>'.$langs->trans("Value").'</td></tr>';

	print '<tr class="oddeven"><td>'.$langs->trans("Monday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_MONDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_MONDAY) . '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("Tuesday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY) . '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("Wednesday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY) . '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("Thursday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY) . '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("Friday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY) . '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("Saturday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY) . '</td></tr>';
	print '<tr class="oddeven"><td>'.$langs->trans("Sunday").'</td><td>' . (empty($conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY)?'':$conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY) . '</td></tr>';

	print '</table>';
	print "</div>";

	print '</form>';

	// Actions buttons
	print '<div class="tabsAction">';
	print '<div class="inline-block divButAction"><a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a></div>';
	print '</div>';
}

llxFooter();

$db->close();
