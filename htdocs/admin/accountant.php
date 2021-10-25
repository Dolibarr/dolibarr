<?php
/* Copyright (C) 2018       Alexandre Spangaro      <aspangaro@open-dsi.fr>
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
 *	\file       htdocs/admin/accountant.php
 *	\ingroup    core
 *	\brief      Setup page to configure accountant / auditor
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'adminaccoutant'; // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array('admin', 'companies'));

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

$parameters = array();
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (($action == 'update' && !GETPOST("cancel", 'alpha'))
|| ($action == 'updateedit')) {
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_NAME", GETPOST("nom", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_ADDRESS", GETPOST("address", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_TOWN", GETPOST("town", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_ZIP", GETPOST("zipcode", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_STATE", GETPOST("state_id", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_REGION", GETPOST("region_code", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_COUNTRY", GETPOST('country_id', 'int'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_PHONE", GETPOST("tel", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_FAX", GETPOST("fax", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_MAIL", GETPOST("mail", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_WEB", GETPOST("web", 'alpha'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_CODE", GETPOST("code", 'nohtml'), 'chaine', 0, '', $conf->entity);
	dolibarr_set_const($db, "MAIN_INFO_ACCOUNTANT_NOTE", GETPOST("note", 'restricthtml'), 'chaine', 0, '', $conf->entity);

	if ($action != 'updateedit' && !$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	}
}

/*
 * View
 */

$help_url = '';
llxHeader('', $langs->trans("CompanyFoundation"), $help_url);

print load_fiche_titre($langs->trans("CompanyFoundation"), '', 'title_setup');

$head = company_admin_prepare_head();

print dol_get_fiche_head($head, 'accountant', $langs->trans("Company"), -1, 'company');

$form = new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

$countrynotdefined = '<font class="error">'.$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')</font>';

print '<span class="opacitymedium">'.$langs->trans("AccountantDesc")."</span><br>\n";
print "<br>\n";

/**
 * Edit parameters
 */
if (!empty($conf->use_javascript_ajax)) {
	print "\n".'<script type="text/javascript" language="javascript">';
	print '$(document).ready(function () {
		  $("#selectcountry_id").change(function() {
			document.form_index.action.value="updateedit";
			document.form_index.submit();
		  });
	  });';
	print '</script>'."\n";
}

print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'" name="form_index">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent editmode">';
print '<tr class="liste_titre"><th class="titlefieldcreate wordbreak">'.$langs->trans("CompanyInfo").'</th><th>'.$langs->trans("Value").'</th></tr>'."\n";

// Name
print '<tr class="oddeven"><td><label for="name">'.$langs->trans("CompanyName").'</label></td><td>';
print '<input name="nom" id="name" class="minwidth200" value="'.(GETPOSTISSET('nom') ? GETPOST('nom', 'nohtml') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_NAME) ? $conf->global->MAIN_INFO_ACCOUNTANT_NAME : '')).'"'.(empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) ? '' : ' autofocus="autofocus"').'></td></tr>'."\n";

// Address
print '<tr class="oddeven"><td><label for="address">'.$langs->trans("CompanyAddress").'</label></td><td>';
print '<textarea name="address" id="address" class="quatrevingtpercent" rows="'.ROWS_3.'">'.(GETPOSTISSET('address') ? GETPOST('address', 'nohtml') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_ADDRESS) ? $conf->global->MAIN_INFO_ACCOUNTANT_ADDRESS : '')).'</textarea></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="zipcode">'.$langs->trans("CompanyZip").'</label></td><td>';
print '<input class="minwidth100" name="zipcode" id="zipcode" value="'.(GETPOSTISSET('zipcode') ? GETPOST('zipcode', 'alpha') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_ZIP) ? $conf->global->MAIN_INFO_ACCOUNTANT_ZIP : '')).'"></td></tr>'."\n";

print '<tr class="oddeven"><td><label for="town">'.$langs->trans("CompanyTown").'</label></td><td>';
print '<input name="town" class="minwidth100" id="town" value="'.(GETPOSTISSET('town') ? GETPOST('town', 'nohtml') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_TOWN) ? $conf->global->MAIN_INFO_ACCOUNTANT_TOWN : '')).'"></td></tr>'."\n";

// Country
print '<tr class="oddeven"><td><label for="selectcountry_id">'.$langs->trans("Country").'</label></td><td class="maxwidthonsmartphone">';
print img_picto('', 'globe-americas', 'class="pictofixedwidth"');
print $form->select_country((GETPOSTISSET('country_id') ? GETPOST('country_id', 'int') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_COUNTRY) ? $conf->global->MAIN_INFO_ACCOUNTANT_COUNTRY : '')), 'country_id');
if ($user->admin) {
	print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"), 1);
}
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="state_id">'.$langs->trans("State").'</label></td><td class="maxwidthonsmartphone">';
print img_picto('', 'state', 'class="pictofixedwidth"');
print $formcompany->select_state((GETPOSTISSET('state_id') ? GETPOST('state_id', 'alpha') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_STATE) ? $conf->global->MAIN_INFO_ACCOUNTANT_STATE : '')), (GETPOSTISSET('country_id') ? GETPOST('country_id', 'int') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_COUNTRY) ? $conf->global->MAIN_INFO_ACCOUNTANT_COUNTRY : '')), 'state_id');
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="phone">'.$langs->trans("Phone").'</label></td><td>';
print img_picto('', 'object_phoning', '', false, 0, 0, '', 'pictofixedwidth');
print '<input name="tel" id="phone" class="maxwidth150 widthcentpercentminusx" value="'.(GETPOSTISSET('tel') ? GETPOST('tel', 'alpha') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_PHONE) ? $conf->global->MAIN_INFO_ACCOUNTANT_PHONE : '')).'"></td></tr>';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="fax">'.$langs->trans("Fax").'</label></td><td>';
print img_picto('', 'object_phoning_fax', '', false, 0, 0, '', 'pictofixedwidth');
print '<input name="fax" id="fax" class="maxwidth150 widthcentpercentminusx" value="'.(GETPOSTISSET('fax') ? GETPOST('fax', 'alpha') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_FAX) ? $conf->global->MAIN_INFO_ACCOUNTANT_FAX : '')).'"></td></tr>';
print '</td></tr>'."\n";

print '<tr class="oddeven"><td><label for="email">'.$langs->trans("EMail").'</label></td><td>';
print img_picto('', 'object_email', '', false, 0, 0, '', 'pictofixedwidth');
print '<input name="mail" id="email" class="maxwidth150 widthcentpercentminusx" value="'.(GETPOSTISSET('mail') ? GETPOST('mail', 'alpha') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_MAIL) ? $conf->global->MAIN_INFO_ACCOUNTANT_MAIL : '')).'"></td></tr>';
print '</td></tr>'."\n";

// Web
print '<tr class="oddeven"><td><label for="web">'.$langs->trans("Web").'</label></td><td>';
print img_picto('', 'globe', '', false, 0, 0, '', 'pictofixedwidth');
print '<input name="web" id="web" class="maxwidth300 widthcentpercentminusx" value="'.(GETPOSTISSET('web') ? GETPOST('web', 'alpha') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_WEB) ? $conf->global->MAIN_INFO_ACCOUNTANT_WEB : '')).'"></td></tr>';
print '</td></tr>'."\n";

// Code
print '<tr class="oddeven"><td><label for="code">'.$langs->trans("AccountantFileNumber").'</label></td><td>';
print '<input name="code" id="code" class="minwidth100" value="'.(GETPOSTISSET('code') ? GETPOST('code', 'nohtml') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_CODE) ? $conf->global->MAIN_INFO_ACCOUNTANT_CODE : '')).'"></td></tr>'."\n";

// Note
print '<tr class="oddeven"><td class="tdtop"><label for="note">'.$langs->trans("Note").'</label></td><td>';
print '<textarea class="flat quatrevingtpercent" name="note" id="note" rows="'.ROWS_5.'">'.(GETPOSTISSET('note') ? GETPOST('note', 'restricthtml') : (!empty($conf->global->MAIN_INFO_ACCOUNTANT_NOTE) ? $conf->global->MAIN_INFO_ACCOUNTANT_NOTE : '')).'</textarea></td></tr>';
print '</td></tr>';

print '</table>';

print '<br><div class="center">';
print '<input type="submit" class="button button-save" name="save" value="'.$langs->trans("Save").'">';
//print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
//print '<input type="submit" class="button button-cancel" name="cancel" value="'.$langs->trans("Cancel").'">';
print '</div>';
//print '<br>';

print '</form>';


llxFooter();

$db->close();
