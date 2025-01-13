<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
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
 *     	\file       htdocs/projet/admin/website.php
 *		\ingroup    member
 *		\brief      File of main public page for project module to catch lead
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formprojet.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_url_root
 */

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

$action = GETPOST('action', 'aZ09');

$defaultoppstatus = getDolGlobalInt('PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD');

$visibility = GETPOST('PROJET_VISIBILITY', 'alpha');

if (!$user->admin) {
	accessforbidden();
}

$error = 0;


/*
 * Actions
 */

if ($action == 'setPROJECT_ENABLE_PUBLIC') {
	if (GETPOST('value')) {
		dolibarr_set_const($db, 'PROJECT_ENABLE_PUBLIC', 1, 'chaine', 0, '', $conf->entity);
	} else {
		dolibarr_set_const($db, 'PROJECT_ENABLE_PUBLIC', 0, 'chaine', 0, '', $conf->entity);
	}
}

if ($action == 'update') {
	$public = GETPOST('PROJECT_ENABLE_PUBLIC');
	$defaultoppstatus = GETPOSTINT('PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD');
	$res = dolibarr_set_const($db, "PROJET_VISIBILITY", $visibility, 'chaine', 0, '', $conf->entity);

	$res = dolibarr_set_const($db, "PROJECT_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);
	$res = dolibarr_set_const($db, "PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD", $defaultoppstatus, 'chaine', 0, '', $conf->entity);

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
$formproject = new FormProjets($db);

$title = $langs->trans("ProjectsSetup");
$help_url = '';
llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-project page-admin_website');


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

$head = project_admin_prepare_head();

$param = '';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'website', $langs->trans("Projects"), -1, 'project');


print '<span class="opacitymedium">'.$langs->trans("LeadPublicFormDesc").'</span><br><br>';

$param = '';

$enabledisablehtml = $langs->trans("EnablePublicLeadForm").' ';
if (!getDolGlobalString('PROJECT_ENABLE_PUBLIC')) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setPROJECT_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setPROJECT_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="PROJECT_ENABLE_PUBLIC" name="PROJECT_ENABLE_PUBLIC" value="'.(!getDolGlobalString('PROJECT_ENABLE_PUBLIC') ? 0 : 1).'">';

print '<br>';

if (getDolGlobalString('PROJECT_ENABLE_PUBLIC')) {
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Parameter").'</td>';
	print '<td class="right">'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	// Default opportunity status
	print '<tr class="oddeven drag" id="trforcetype"><td>';
	print $langs->trans("DefaultOpportunityStatus");
	print '</td><td class="right">';
	print $formproject->selectOpportunityStatus('PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD', GETPOSTISSET('PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD') ? GETPOSTINT('PROJECT_DEFAULT_OPPORTUNITY_STATUS_FOR_ONLINE_LEAD') : $defaultoppstatus, 1, 0, 0, 0, '', 0, 1);
	print "</td></tr>\n";


	// project visibility
	$arrayofchoices = array('0' => $langs->trans("AssignedContacts"), '1' => $langs->trans("Everyone"));
	print '<tr class="oddeven drag"><td>';
	print $langs->trans("Visibility");
	print '</td><td class="right">';
	print $form->selectarray('PROJET_VISIBILITY', $arrayofchoices, getDolGlobalInt('PROJET_VISIBILITY'), 0);
	print "</td></tr>\n";

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print '<input type="submit" class="button button-edit" value="'.$langs->trans("Modify").'">';
	print '</div>';
}

print dol_get_fiche_end();

print '</form>';


if (getDolGlobalString('PROJECT_ENABLE_PUBLIC')) {
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
	print '<input type="text" id="publicurlmember" class="quatrevingtpercentminusx" value="'.$urlwithroot.'/public/project/new.php'.$entity_qr.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/project/new.php'.$entity_qr.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurlmember');
}

// End of page
llxFooter();
$db->close();
