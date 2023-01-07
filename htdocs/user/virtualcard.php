<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/user/virtualcard.php
 *      \ingroup    core
 *		\brief      Page to setup a virtual card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

// Load translation files required by page
$langs->loadLangs(array("users", "companies"));

// Security check
$id = GETPOST('id', 'int');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->getrights();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->rights->user->self->creer) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// If user is not the user that read and has no permission to read other users, we stop
if (($object->id != $user->id) && empty($user->rights->user->user->lire)) {
	accessforbidden();
}

/*
 * Actions
 */

if ($action == 'update') {
	$tmparray = array();
	$tmparray['USER_PUBLIC_HIDE_PHOTO'] = (GETPOST('USER_PUBLIC_HIDE_PHOTO') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_JOBPOSITION'] = (GETPOST('USER_PUBLIC_HIDE_JOBPOSITION') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_EMAIL'] = (GETPOST('USER_PUBLIC_HIDE_EMAIL') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_OFFICE_PHONE'] = (GETPOST('USER_PUBLIC_HIDE_OFFICE_PHONE') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_OFFICE_FAX'] = (GETPOST('USER_PUBLIC_HIDE_OFFICE_FAX') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_USER_MOBILE'] = (GETPOST('USER_PUBLIC_HIDE_USER_MOBILE') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_SOCIALNETWORKS'] = (GETPOST('USER_PUBLIC_HIDE_SOCIALNETWORKS') ? 1 : 0);
	$tmparray['USER_PUBLIC_HIDE_COMPANY'] = (GETPOST('USER_PUBLIC_HIDE_COMPANY') ? 1 : 0);
	$tmparray['USER_PUBLIC_MORE'] = (GETPOST('USER_PUBLIC_MORE') ? GETPOST('USER_PUBLIC_MORE') : '');

	dol_set_user_param($db, $conf, $object, $tmparray);
}

if ($action == 'setUSER_ENABLE_PUBLIC') {
	if (GETPOST('value')) {
		$tmparray = array('USER_ENABLE_PUBLIC' => 1);
	} else {
		$tmparray = array('USER_ENABLE_PUBLIC' => 0);
	}
	dol_set_user_param($db, $conf, $object, $tmparray);
}


/*
 * View
 */

$form = new Form($db);

$person_name = !empty($object->firstname) ? $object->lastname.", ".$object->firstname : $object->lastname;
$title = $person_name." - ".$langs->trans('Info');
$help_url = '';
llxHeader('', $title, $help_url);

$head = user_prepare_head($object);

$title = $langs->trans("User");
//print dol_get_fiche_head($head, 'info', $title, -1, 'user');


$linkback = '';

if ($user->rights->user->user->lire || $user->admin) {
	$linkback = '<a href="'.DOL_URL_ROOT.'/user/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';
}

$morehtmlref = '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'" class="refid">';
$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
$morehtmlref .= '</a>';

//dol_banner_tab($object, 'id', $linkback, $user->rights->user->user->lire || $user->admin, 'rowid', 'ref', $morehtmlref);


print '<div class="fichecenter">';

print '<br>';

/*
 print '<span class="opacitymedium">'.$langs->trans("VCard").'</span><br>';

print '<a href="'.DOL_URL_ROOT.'/user/vcard.php?id='.$object->id.'" class="refid" rel="noopener">';
print img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard.png', 'class="valignmiddle marginleftonly paddingrightonly"');
print '</a>';


print '<hr>';
//print '<div class="underbanner clearboth"></div>';

print '<br>';
*/

print '<span class="opacitymedium">'.$langs->trans("UserPublicPageDesc").'</span><br><br>';

$param = '&id='.((int) $object->id);
$param .= '&dol_openinpopup=1';

$enabledisablehtml = $langs->trans("EnablePublicVirtualCard").' ';
if (!getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setUSER_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setUSER_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="USER_ENABLE_PUBLIC" name="USER_ENABLE_PUBLIC" value="'.(empty($conf->global->USER_ENABLE_PUBLIC) ? 0 : 1).'">';

print '<br><br>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="id" value="'.$object->id.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';

if (getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
	print '<br>';
	//print $langs->trans('FollowingLinksArePublic').'<br>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('PublicVirtualCardUrl').'</span><br>';

	$fullexternaleurltovirtualcard = $object->getOnlineVirtualCardUrl();

	print '<div class="urllink">';
	print '<input type="text" id="publicurluser" class="quatrevingtpercentminusx" value="'.$fullexternaleurltovirtualcard.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$fullexternaleurltovirtualcard.'">'.img_picto('', 'globe', 'class="paddingleft"').'</a>';
	print '</div>';
	print ajax_autoselect('publicurluser');

	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Options").'</td>';
	print '<td>'.$langs->trans("Value").'</td>';
	print "</tr>\n";

	// User photo
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Photo"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_PHOTO", (getDolUserInt('USER_PUBLIC_HIDE_PHOTO', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_PHOTO', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// Job position
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("PostOrFunction"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_JOBPOSITION", (getDolUserInt('USER_PUBLIC_HIDE_JOBPOSITION', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_JOBPOSITION', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// Email
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Email"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_EMAIL", (getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_EMAIL', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// Office phone
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("OfficePhone"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_OFFICE_PHONE", (getDolUserInt('USER_PUBLIC_HIDE_OFFICE_PHONE', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_OFFICE_PHONE', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// Office fax
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("OfficeFax"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_OFFICE_FAX", (getDolUserInt('USER_PUBLIC_HIDE_OFFICE_FAX', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_OFFICE_FAX', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// User mobile
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("UserMobile"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_USER_MOBILE", (getDolUserInt('USER_PUBLIC_HIDE_USER_MOBILE', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_USER_MOBILE', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// Social networks
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("SocialNetworks"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_SOCIALNETWORKS", (getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_SOCIALNETWORKS', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// Company name
	print '<tr class="oddeven" id="tramount"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("CompanySection"));
	print '</td><td>';
	print $form->selectyesno("USER_PUBLIC_HIDE_COMPANY", (getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object) ? getDolUserInt('USER_PUBLIC_HIDE_COMPANY', 0, $object) : 0), 1);
	print "</td></tr>\n";

	// More
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("Text");
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor = new DolEditor('USER_PUBLIC_MORE', getDolUserString('USER_PUBLIC_MORE', '', $object), '', 160, 'dolibarr_notes', '', false, false, isModEnabled('fckeditor'), ROWS_5, '90%');
	$doleditor->Create();
	print "</td></tr>\n";

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print $form->buttonsSaveCancel("Save", "Cancel", array(), 0, '', $dol_openinpopup);
	print '</div>';
}


print dol_get_fiche_end();

print '</form>';


print '</div>';


print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
