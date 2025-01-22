<?php
/* Copyright (C) 2004-2023 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2015 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/user/virtualcard.php
 *      \ingroup    core
 *		\brief      Page to setup a virtual card
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/usergroups.lib.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by page
$langs->loadLangs(array("users", "companies", "admin", "website"));

// Security check
$id = GETPOSTINT('id');
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'aZ09');
$dol_openinpopup = GETPOST('dol_openinpopup', 'aZ09');

if (empty($id) && empty($ref)) {
	$id = $user->id;
}

$expand = $_COOKIE['virtualcard_expand'];

$object = new User($db);
if ($id > 0 || !empty($ref)) {
	$result = $object->fetch($id, $ref, '', 1);
	$object->loadRights();
}

// Security check
$socid = 0;
if ($user->socid > 0) {
	$socid = $user->socid;
}
$feature2 = (($socid && $user->hasRight('user', 'self', 'creer')) ? '' : 'user');

$result = restrictedArea($user, 'user', $id, 'user&user', $feature2);

// If user is not the user that read and has no permission to read other users, we stop
if (($object->id != $user->id) && !$user->hasRight('user', 'user', 'lire')) {
	accessforbidden();
}

$permissiontoedit = ((($object->id == $user->id) && $user->hasRight('user', 'self', 'creer')) || $user->hasRight('user', 'user', 'creer'));


/*
 * Actions
 */

if ($action == 'update' && $permissiontoedit) {
	$tmparray = array();
	$tmparray['USER_PUBLIC_MORE'] = GETPOST('USER_PUBLIC_MORE', 'alphanohtml');

	dol_set_user_param($db, $conf, $object, array('USER_PUBLIC_MORE' => $tmparray['USER_PUBLIC_MORE']));
}

if ($action == 'setUSER_ENABLE_PUBLIC' && $permissiontoedit) {
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

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-user page-virtualcard');


$title = $langs->trans("User");
//print dol_get_fiche_head($head, 'info', $title, -1, 'user');


print '<div class="fichecenter">';

print '<br>';

$param = '&id='.((int) $object->id);
if (GETPOSTISSET('dol_openinpopup')) {
	$param .= '&dol_openinpopup='.urlencode(GETPOST('dol_openinpopup', 'aZ09'));
}

$enabledisablehtml = $langs->trans("EnablePublicVirtualCard").' ';
if (!getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
	// Button off, click to enable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setUSER_ENABLE_PUBLIC&token='.newToken().'&value=1'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Disabled"), 'switch_off');
	$enabledisablehtml .= '</a>';

	$enabledisablehtml .= '<br><br><div class="opacitymedium justify">'.$langs->trans("UserPublicPageDesc").'</div>';
} else {
	// Button on, click to disable
	$enabledisablehtml .= '<a class="reposition valignmiddle" href="'.$_SERVER["PHP_SELF"].'?action=setUSER_ENABLE_PUBLIC&token='.newToken().'&value=0'.$param.'">';
	$enabledisablehtml .= img_picto($langs->trans("Activated"), 'switch_on');
	$enabledisablehtml .= '</a>';
}
print $enabledisablehtml;
print '<input type="hidden" id="USER_ENABLE_PUBLIC" name="USER_ENABLE_PUBLIC" value="'.(getDolUserInt('USER_ENABLE_PUBLIC') ? 1 : 0).'">';


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';

if (getDolUserInt('USER_ENABLE_PUBLIC', 0, $object)) {
	print '<br><br>';

	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$object->id.'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	//print $langs->trans('FollowingLinksArePublic').'<br>';
	print img_picto('', 'globe').' <span class="opacitymedium">'.$langs->trans('PublicVirtualCardUrl').'</span><br>';

	$fullexternaleurltovirtualcard = $object->getOnlineVirtualCardUrl('', 'external');
	$fullinternalurltovirtualcard = $object->getOnlineVirtualCardUrl('', 'internal');

	$showUserSocialNetworks = !getDolUserString('USER_PUBLIC_HIDE_SOCIALNETWORKS', '', $object);
	$showSocieteSocialNetworks = !getDolUserString('USER_PUBLIC_HIDE_SOCIALNETWORKS_BUSINESS', '', $object);

	print '<div class="urllink">';
	print '<input type="text" id="publicurluser" class="quatrevingtpercentminusx" value="'.$fullexternaleurltovirtualcard.'">';
	print '<a target="_blank" rel="noopener noreferrer" href="'.$fullexternaleurltovirtualcard.'">'.img_picto('', 'globe', 'class="paddingleft marginrightonly paddingright"').$langs->trans("GoTo").'...</a>';
	print '</div>';
	print ajax_autoselect('publicurluser');

	print '<br>';
	print '<br>';

	// Show/Hide options
	print '<div class="centpercent margintoponly marginbottomonly">';
	print img_picto('', 'setup', 'class="pictofixedwidth"').'<a id="lnk" href="#">'.$langs->trans("ShowAdvancedOptions").'...</a>';
	print '</div>';

	print '<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("#lnk").click(function(event) {
			event.preventDefault();
			console.log("We click on link to show virtual card options");
			hideoptions(this);
		});
	});

	function hideoptions(domelem) {
		const div = document.getElementById("div_container_sub_exportoptions");

	  	if (div.style.display === "none") {
	    	div.style.display = "block";
			domelem.innerText="'.dol_escape_js($langs->transnoentitiesnoconv("HideAdvancedoptions")).'";
			var date = new Date();
        	date.setTime(date.getTime() + (1 * 24 * 60 * 60 * 1000));
			document.cookie = "virtualcard_expand=1; expires=" + date.toUTCString() + "; path=/";
	  	} else {
	    	div.style.display = "none";
			domelem.innerText="'.dol_escape_js($langs->transnoentitiesnoconv("ShowAdvancedOptions")).'...";
			var date = new Date();
        	date.setTime(date.getTime() - (1 * 24 * 60 * 60 * 1000));
			document.cookie = "virtualcard_expand=0; expires=" + date.toUTCString() + "; path=/";
		}
	}
	</script>';

	// Start div hide/Show
	print '<div id="div_container_sub_exportoptions" style="'.($expand ? '' : 'display: none;').'">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("User").'</td>';
	print '<td></td>';
	print "</tr>\n";

	// User photo
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Photo"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_PHOTO", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Job position
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("PostOrFunction"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_JOBPOSITION", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Email
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Email"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_EMAIL", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Office phone
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("PhonePro"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_OFFICE_PHONE", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Office fax
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Fax"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_OFFICE_FAX", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// User mobile
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("PhoneMobile"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_USER_MOBILE", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Social networks
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("SocialNetworksInformation"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_SOCIALNETWORKS", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Show list of socialnetworks for user
	if ($showUserSocialNetworks) {
		$socialnetworks = $object->socialnetworks;
		if (!empty($socialnetworks)) {
			foreach ($socialnetworks as $key => $networkVal) {
				print '<tr class="oddeven">';
				print '<td> &nbsp; &nbsp; '.$langs->trans("Hide").' '.dol_escape_htmltag($key).'</td><td>';
				print ajax_constantonoff('USER_PUBLIC_HIDE_SOCIALNETWORKS_'.strtoupper($key), array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
				print '</td>';
				print "</tr>";
			}
		}
	}

	// Birth date
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("ShowOnVCard", $langs->transnoentitiesnoconv("Birthdate"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_SHOW_BIRTH", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	// Address
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("ShowOnVCard", $langs->transnoentitiesnoconv("Address"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_SHOW_ADDRESS", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Company").'</td>';
	print '<td></td>';
	print "</tr>\n";

	// Company section
	print '<tr class="oddeven" id="tramount"><td>';
	print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("CompanySection"));
	print '</td><td>';
	print ajax_constantonoff("USER_PUBLIC_HIDE_COMPANY", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
	print "</td></tr>\n";

	if (!getDolUserString('USER_PUBLIC_HIDE_COMPANY', '', $object)) {
		// Email
		print '<tr class="oddeven" id="tredit"><td>';
		print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Email"));
		print '</td><td>';
		print ajax_constantonoff("SOCIETE_PUBLIC_HIDE_EMAIL", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
		print "</td></tr>\n";

		// URL
		print '<tr class="oddeven" id="tredit"><td>';
		print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("URL"));
		print '</td><td>';
		print ajax_constantonoff("SOCIETE_PUBLIC_HIDE_URL", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
		print "</td></tr>\n";

		// Office phone
		print '<tr class="oddeven" id="tredit"><td>';
		print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Phone"));
		print '</td><td>';
		print ajax_constantonoff("SOCIETE_PUBLIC_HIDE_OFFICE_PHONE", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
		print "</td></tr>\n";

		// Office fax
		print '<tr class="oddeven" id="tredit"><td>';
		print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("Fax"));
		print '</td><td>';
		print ajax_constantonoff("SOCIETE_PUBLIC_HIDE_OFFICE_FAX", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
		print "</td></tr>\n";

		// Social networks
		print '<tr class="oddeven" id="tredit"><td>';
		print $langs->trans("HideOnVCard", $langs->transnoentitiesnoconv("SocialNetworksInformation"));
		print '</td><td>';
		print ajax_constantonoff("USER_PUBLIC_HIDE_SOCIALNETWORKS_BUSINESS", array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
		print "</td></tr>\n";

		// Show list of social networks for company
		if ($showSocieteSocialNetworks) {
			$listofnetworks = $mysoc->socialnetworks;

			if (!empty($listofnetworks)) {
				foreach ($listofnetworks as $key => $networkVal) {
					print '<tr class="oddeven">';
					print '<td> &nbsp; &nbsp; '.$langs->trans("Hide").' '.dol_escape_htmltag($key).'</td><td>';
					print ajax_constantonoff('SOCIETE_PUBLIC_HIDE_SOCIALNETWORKS_'.strtoupper($key), array(), null, 0, 0, 1, 2, 0, 0, '', '', 'reposition', $object);
					print '</td>';
					print "</tr>";
				}
			}
		}
	}

	print '<tr class="liste_titre">';
	print '<td>'.$langs->trans("Other").'</td>';
	print '<td></td>';
	print "</tr>\n";

	// More
	print '<tr class="oddeven" id="tredit"><td>';
	print $langs->trans("Text");
	print '</td><td>';
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$extendededitor = 0;	// We force no WYSIWYG editor
	$doleditor = new DolEditor('USER_PUBLIC_MORE', getDolUserString('USER_PUBLIC_MORE', '', $object), '', 160, 'dolibarr_notes', '', false, false, $extendededitor, ROWS_5, '90%');
	$doleditor->Create();
	print "</td></tr>\n";

	print '</table>';
	print '</div>';

	print '<div class="center">';
	print $form->buttonsSaveCancel("Save", '', array(), 0, '', $dol_openinpopup);
	print '</div>';

	print '<br>';

	print '</div>';	// End hide/show

	print '<br>';

	// Preview
	print '<div class="center">';
	print '<span class="opacitymedium">'.$langs->trans("Preview").'</span><br>';
	print '<div class="virtualcard-div">';
	print '<a target="_blank" rel="noopener noreferrer cursorpointer" href="'.$fullexternaleurltovirtualcard.'">'."\n";
	print '<iframe id="virtualcard-iframe" title="" class="center" src="'.$fullinternalurltovirtualcard.'&mode=preview">';
	print '</iframe>';
	print '</a>';
	print '</div>';
	print '</div>';

	print '<br>';
}


print '</form>';

print '</div>';


// End of page
llxFooter();
$db->close();
