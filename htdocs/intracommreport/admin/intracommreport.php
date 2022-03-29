<?php
/* Copyright (C) 2015      ATM Consulting       <support@atm-consulting.fr>
 * Copyright (C) 2019-2020 Open-DSI             <support@open-dsi.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file		htdocs/admin/intracommreport.php
 *      \ingroup	intracommreport
 *      \brief		Page to setup the module intracomm report
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/intracommreport.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "intracommreport"));

if (!$user->admin) accessforbidden();

$action = GETPOST('action', 'aZ09');

// Parameters INTRACOMMREPORT_* and others
$list_DEB = array(
	'INTRACOMMREPORT_NUM_AGREMENT',
);

$list_DES = array(
	'INTRACOMMREPORT_NUM_DECLARATION',
);

if ($action == 'update') {
	$error = 0;

	if (!$error)
	{
		foreach ($list_DEB as $constname)
		{
			$constvalue = GETPOST($constname, 'alpha');

			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}

		foreach ($list_DES as $constname)
		{
			$constvalue = GETPOST($constname, 'alpha');

			if (!dolibarr_set_const($db, $constname, $constvalue, 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}

		dolibarr_set_const($db, "INTRACOMMREPORT_TYPE_ACTEUR", GETPOST("INTRACOMMREPORT_TYPE_ACTEUR", 'alpha'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "INTRACOMMREPORT_ROLE_ACTEUR", GETPOST("INTRACOMMREPORT_ROLE_ACTEUR", 'alpha'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION", GETPOST("INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION", 'alpha'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION", GETPOST("INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION", 'alpha'), 'chaine', 0, '', $conf->entity);
		dolibarr_set_const($db, "INTRACOMMREPORT_CATEG_FRAISDEPORT", GETPOST("INTRACOMMREPORT_CATEG_FRAISDEPORT", 'alpha'), 'chaine', 0, '', $conf->entity);

		if ($error) {
			setEventMessages($langs->trans("Error"), null, 'errors');
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	}
}

/*
 * View
 */

$form = new Form($db);
$formother = new FormOther($db);

llxHeader('', $langs->trans("IntracommReportSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("IntracommReportSetup"), $linkback, 'title_setup');

$head = intracommReportAdminPrepareHead();

print dol_get_fiche_head($head, 'general', $langs->trans("IntracommReport"), -1, "intracommreport");

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print load_fiche_titre($langs->trans("Parameters").' (DEB)');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

foreach ($list_DEB as $key)
{
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="left">';
	print '<input type="text" class="maxwidth100" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	print '</td>';

	print '</tr>';
}

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_TYPE_ACTEUR").'</td>';
$arraychoices = array(''=>$langs->trans("None"), 'PSI'=>'Déclarant pour son compte', 'TDP'=>'Tiers déclarant');
print '<td>';
print $form->selectarray('INTRACOMMREPORT_TYPE_ACTEUR', $arraychoices, $conf->global->INTRACOMMREPORT_TYPE_ACTEUR, 0);
print '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_ROLE_ACTEUR").'</td>';
$arraychoices = array(''=>$langs->trans("None"), 'sender'=>'Emetteur', 'PSI'=>'Déclarant');
print '<td>';
print $form->selectarray('INTRACOMMREPORT_ROLE_ACTEUR', $arraychoices, $conf->global->INTRACOMMREPORT_ROLE_ACTEUR, 0);
print '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION").'</td>';
$arraychoices = array(1=>'Seuil de 460 000 €', 2=>'En dessous de 460 000 €');
print '<td>';
print $form->selectarray('INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION', $arraychoices, $conf->global->INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION, 0);
print '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION").'</td>';
$arraychoices = array(3=>'Seuil de 460 000 €', 4=>'En dessous de 460 000 €');
print '<td>';
print $form->selectarray('INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION', $arraychoices, $conf->global->INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION, 0);
print '</td>';
print "</tr>\n";

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_CATEG_FRAISDEPORT").'</td>';
print '<td>';
print $formother->select_categories('product', $conf->global->INTRACOMMREPORT_CATEG_FRAISDEPORT, 'INTRACOMMREPORT_CATEG_FRAISDEPORT');
print '</td>';
print "</tr>\n";

print '</table>';


print load_fiche_titre($langs->trans("Parameters").' (DES)');

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Description").'</td>';
print '<td>'.$langs->trans("Value").'</td>';
print '</tr>';

foreach ($list_DES as $key)
{
	print '<tr class="oddeven value">';

	// Param
	$label = $langs->trans($key);
	print '<td>'.$label.'</td>';
	// Value
	print '<td class="left">';
	print '<input type="text" class="maxwidth100" id="'.$key.'" name="'.$key.'" value="'.$conf->global->$key.'">';
	print '</td>';

	print '</tr>';
}

print '</table>';

print '<div class="center">';
print '<input type="submit" name="bt_save" class="butAction button-save" value="'.$langs->trans("Update").'" />';
print '</div>';

print '</form>';

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
