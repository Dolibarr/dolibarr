<?php
/* Copyright (C) 2015      ATM Consulting       <support@atm-consulting.fr>
 * Copyright (C) 2019      Open-DSI             <support@open-dsi.fr>
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
$langs->loadLangs(array("admin","intracommreport"));

if (! $user->admin) accessforbidden();

$action=__get('action','');

if($action=='save') {
	
	foreach($_REQUEST['TParamProDeb'] as $name=>$param) {
		
		dolibarr_set_const($db, $name, $param);

	}
	
}

/*
 * View
 */

$form=new Form($db);
$formother=new FormOther($db);

llxHeader('', $langs->trans(IntracommReportSetup));

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php?restore_lastsearch_values=1">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("IntracommReportSetup"), $linkback, 'title_setup');

$head = intracommReportAdminPrepareHead();

dol_fiche_head($head, 'general', $langs->trans("IntracommReport"), 0, "intracommreport");

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").' (DEB)</td>'."\n";
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_NUM_AGREMENT").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $atmForm->texte('','TParamProDeb[INTRACOMMREPORT_NUM_AGREMENT]',$conf->global->INTRACOMMREPORT_NUM_AGREMENT,30,255);
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_TYPE_ACTEUR").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $atmForm->combo('','TParamProDeb[INTRACOMMREPORT_TYPE_ACTEUR]', array(''=>'', 'PSI'=>'Déclarant pour son compte', 'TDP'=>'Tiers déclarant'), $conf->global->INTRACOMMREPORT_TYPE_ACTEUR);
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_ROLE_ACTEUR").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $atmForm->combo('','TParamProDeb[INTRACOMMREPORT_ROLE_ACTEUR]', array(''=>'', 'sender'=>'Emetteur', 'PSI'=>'Déclarant'), $conf->global->INTRACOMMREPORT_ROLE_ACTEUR);
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $atmForm->combo('','TParamProDeb[INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION]', array(0=>'', 1=>'Seuil de 460 000 €', 2=>'En dessous de 460 000 €'), $conf->global->INTRACOMMREPORT_NIV_OBLIGATION_INTRODUCTION);
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $atmForm->combo('','TParamProDeb[INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION]', array(0=>'', 3=>'Seuil de 460 000 €', 4=>'En dessous de 460 000 €'), $conf->global->INTRACOMMREPORT_NIV_OBLIGATION_EXPEDITION);
print '</td></tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("INTRACOMMREPORT_CATEG_FRAISDEPORT").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $formother->select_categories(0, $conf->global->INTRACOMMREPORT_CATEG_FRAISDEPORT, 'TParamProDeb[INTRACOMMREPORT_CATEG_FRAISDEPORT]');
print '</td></tr>';

print '</table>';

print '<div class="tabsAction">';
print '<div class="inline-block divButAction">';
print '<input type="submit" name="bt_save" class="butAction" value="'.$langs->trans('Save').'" />';
print '</div>';
print '</div>';

print '</form>';
	
print '<form method="POST" action="'.$_SERVER['PHP_SELF'].'">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="save">';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameters").' (DES)</td>'."\n";
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="center" width="100">'.$langs->trans("Value").'</td>'."\n";
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("EXPORT_PRO_DES_NUM_DECLARATION").'</td>';
print '<td class="center" width="20">&nbsp;</td>';
print '<td class="right" width="300">';
print $atmForm->texte('','TParamProDeb[EXPORT_PRO_DES_NUM_DECLARATION]',$conf->global->EXPORT_PRO_DES_NUM_DECLARATION,30,255);
print '</td></tr>';
	
print '</table>';

print '<div class="tabsAction">';
print '<div class="inline-block divButAction">';
print '<input type="submit" name="bt_save" class="butAction" value="'.$langs->trans('Save').'" />';
print '</div>';
print '</div>';
	
print '</form>';
	
dol_fiche_end();

// End of page
llxFooter();
$db->close();