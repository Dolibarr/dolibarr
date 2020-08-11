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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
$langs->loadLangs(array('admin', 'companies', 'other'));

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

$form = new Form($db);

$help_url='';
llxHeader('', $langs->trans("CompanyFoundation"), $help_url);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

dol_fiche_head($head, 'openinghours', $langs->trans("Company"), -1, 'company');

print '<span class="opacitymedium">'.$langs->trans("OpeningHoursDesc")."</span><br>\n";
print "<br>\n";

if (empty($action) || $action == 'edit' || $action == 'updateedit')
{
	/**
	 * Edit parameters
	 */
	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th class="titlefield wordbreak">'.$langs->trans("Day").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

	print '<tr class="oddeven"><td>';
	print $form->textwithpicto($langs->trans("Monday"), $langs->trans("OpeningHoursFormatDesc"));
	print '</td><td>';
	print '<input name="monday" id="monday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_MONDAY?$conf->global->MAIN_INFO_OPENINGHOURS_MONDAY: GETPOST("monday", 'alpha')) . '"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("Tuesday"), $langs->trans("OpeningHoursFormatDesc"));
    print '</td><td>';
    print '<input name="tuesday" id="tuesday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY?$conf->global->MAIN_INFO_OPENINGHOURS_TUESDAY: GETPOST("tuesday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("Wednesday"), $langs->trans("OpeningHoursFormatDesc"));
    print '</td><td>';
    print '<input name="wednesday" id="wednesday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY?$conf->global->MAIN_INFO_OPENINGHOURS_WEDNESDAY: GETPOST("wednesday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("Thursday"), $langs->trans("OpeningHoursFormatDesc"));
    print '</td><td>';
    print '<input name="thursday" id="thursday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY?$conf->global->MAIN_INFO_OPENINGHOURS_THURSDAY: GETPOST("thursday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("Friday"), $langs->trans("OpeningHoursFormatDesc"));
    print '</td><td>';
    print '<input name="friday" id="friday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY?$conf->global->MAIN_INFO_OPENINGHOURS_FRIDAY: GETPOST("friday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("Saturday"), $langs->trans("OpeningHoursFormatDesc"));
    print '</td><td>';
    print '<input name="saturday" id="saturday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY?$conf->global->MAIN_INFO_OPENINGHOURS_SATURDAY: GETPOST("saturday", 'alpha')) . '"></td></tr>'."\n";

    print '<tr class="oddeven"><td>';
    print $form->textwithpicto($langs->trans("Sunday"), $langs->trans("OpeningHoursFormatDesc"));
    print '</td><td>';
    print '<input name="sunday" id="sunday" class="minwidth100" value="'. ($conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY?$conf->global->MAIN_INFO_OPENINGHOURS_SUNDAY: GETPOST("sunday", 'alpha')) . '"></td></tr>'."\n";

    print '</table>';

	print '<br><div class="center">';
	print '<input type="submit" class="button" name="save" value="'.$langs->trans("Save").'">';
	print '</div>';
	print '<br>';

	print '</form>';
}

llxFooter();

$db->close();
