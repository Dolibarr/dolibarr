<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2015	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
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

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "members"));

$action = GETPOST('action', 'aZ09');

if (!$user->admin) {
	accessforbidden();
}


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

	$res = dolibarr_set_const($db, "PROJECT_ENABLE_PUBLIC", $public, 'chaine', 0, '', $conf->entity);

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

$help_url = '';
llxHeader('', $langs->trans("ProjectsSetup"), $help_url);


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("ProjectsSetup"), $linkback, 'title_setup');

$head = project_admin_prepare_head();

$param = '';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print dol_get_fiche_head($head, 'website', $langs->trans("Projects"), -1, 'user');


print '<span class="opacitymedium">'.$langs->trans("LeadPublicFormDesc").'</span><br><br>';


$enabledisablehtml = $langs->trans("EnablePublicLeadForm").' ';
if (empty($conf->global->PROJECT_ENABLE_PUBLIC)) {
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
print '<input type="hidden" id="PROJECT_ENABLE_PUBLIC" name="PROJECT_ENABLE_PUBLIC" value="'.(empty($conf->global->PROJECT_ENABLE_PUBLIC) ? 0 : 1).'">';


print dol_get_fiche_end();

print '</form>';


if (!empty($conf->global->PROJECT_ENABLE_PUBLIC)) {
	print '<br>';
	//print $langs->trans('FollowingLinksArePublic').'<br>';
	print img_picto('', 'globe').' '.$langs->trans('BlankSubscriptionForm').':<br>';
	if ($conf->multicompany->enabled) {
		$entity_qr = '?entity='.$conf->entity;
	} else {
		$entity_qr = '';
	}

	// Define $urlwithroot
	$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
	$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
	//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

	print '<a target="_blank" rel="noopener noreferrer" href="'.$urlwithroot.'/public/project/new.php'.$entity_qr.'">'.$urlwithroot.'/public/project/new.php'.$entity_qr.'</a>';
}

// End of page
llxFooter();
$db->close();
