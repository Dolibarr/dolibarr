<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024		Frédéric France			<frederic.france@free.fr>
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
 *     	\file       htdocs/recruitment/admin/public_interface.php
 *		\ingroup    recruitment
 *		\brief      File of main public page for open job position
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/recruitment/lib/recruitment.lib.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "recruitment"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setRECRUITMENT_ENABLE_PUBLIC_INTERFACE') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'RECRUITMENT_ENABLE_PUBLIC_INTERFACE', '1', 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'RECRUITMENT_ENABLE_PUBLIC_INTERFACE', '0', 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	$public = GETPOST('RECRUITMENT_ENABLE_PUBLIC_INTERFACE');

	$res = dolibarr_set_const($db, "RECRUITMENT_ENABLE_PUBLIC_INTERFACE", $public, 'chaine', 0, '', $conf->entity);

	if (!($res > 0)) {
		$error++;
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

$form = new Form($db);

//$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros';
$help_url = '';
llxHeader('', $langs->trans("RecruitmentSetup"), $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("RecruitmentSetup"), $linkback, 'title_setup');

$head = recruitmentAdminPrepareHead();



print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'publicurl', '', -1, '');


print '<span class="opacitymedium">'.$langs->trans("PublicInterfaceRecruitmentDesc").'</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicRecruitmentPages").' ';
if (!getDolGlobalString('RECRUITMENT_ENABLE_PUBLIC_INTERFACE')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setRECRUITMENT_ENABLE_PUBLIC_INTERFACE&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setRECRUITMENT_ENABLE_PUBLIC_INTERFACE&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="RECRUITMENT_ENABLE_PUBLIC_INTERFACE" name="RECRUITMENT_ENABLE_PUBLIC_INTERFACE" value="'.(!getDolGlobalString('RECRUITMENT_ENABLE_PUBLIC_INTERFACE') ? 0 : 1).'">';


print '<br>';

/*
if (!empty($conf->global->RECRUITMENT_ENABLE_PUBLIC_INTERFACE)) {
	print '<br>';

	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameter").'</td>';
	print '<td class="right">'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	print '</table>';

	print '<div class="center">';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</div>';
}
*/

print dol_get_fiche_end();

print '</form>';


if (getDolGlobalString('RECRUITMENT_ENABLE_PUBLIC_INTERFACE')) {
	print '<br>';
	//print $langs->trans('FollowingLinksArePublic').'<br>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('BlankSubscriptionForm').'</span><br>';
	if (isModEnabled('multicompany')) {
		$entity_qr = '?entity='.$conf->entity;
	} else {
		$entity_qr = '';
	}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	print '<div class="urllink">';
	print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/recruitment/index.php'.$entity_qr.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/recruitment/index.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurlmember');
}

// End of page
llxFooter();
$db->close();
