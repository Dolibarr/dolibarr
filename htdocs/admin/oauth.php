<?php
/* Copyright (C) 2015-2018  Frederic France     <frederic.france@netlogic.fr>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2022       Laurent Destailleur <eldy@users.sourceforge.net>
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
 *
 */

/**
 * \file        htdocs/admin/oauth.php
 * \ingroup     oauth
 * \brief       Setup page to configure oauth access api
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';

// $supportedoauth2array is defined into oauth.lib.php

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

// Load translation files required by the page
$langs->loadLangs(array('admin', 'oauth', 'modulebuilder'));

// Security check
if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$provider = GETPOST('provider', 'aZ09');
$label = GETPOST('label', 'aZ09');

$error = 0;


/*
 * Actions
 */

if ($action == 'add') {		// $provider is OAUTH_XXX
	if ($provider && $provider != '-1') {
		$constname = strtoupper($provider).($label ? '-'.$label : '').'_ID';

		if (getDolGlobalString($constname)) {
			setEventMessages($langs->trans("AOAuthEntryForThisProviderAndLabelAlreadyHasAKey"), null, 'errors');
			$error++;
		} else {
			dolibarr_set_const($db, $constname, 'ToComplete', 'chaine', 0, '', $conf->entity);
			setEventMessages($langs->trans("OAuthProviderAdded"), null);
		}
	}
}
if ($action == 'update') {
	foreach ($conf->global as $key => $val) {
		if (!empty($val) && preg_match('/^OAUTH_.+_ID$/', $key)) {
			$constvalue = str_replace('_ID', '', $key);
			if (!dolibarr_set_const($db, $constvalue.'_ID', GETPOST($constvalue.'_ID'), 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
			// If we reset this provider, we also remove the secret
			if (!dolibarr_set_const($db, $constvalue.'_SECRET', GETPOST($constvalue.'_ID') ? GETPOST($constvalue.'_SECRET') : '', 'chaine', 0, '', $conf->entity)) {
				$error++;
			}
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigOAuth'), $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add">';

$head = oauthadmin_prepare_head();

print dol_get_fiche_head($head, 'services', '', -1, '');


print '<span class="opacitymedium">'.$langs->trans("ListOfSupportedOauthProviders").'</span><br><br>';


print '<select name="provider" id="provider" class="minwidth150">';
print '<option name="-1" value="-1">'.$langs->trans("OAuthProvider").'</option>';
foreach ($list as $key) {
	$supported = 0;
	$keyforsupportedoauth2array = $key[0];

	if (in_array($keyforsupportedoauth2array, array_keys($supportedoauth2array))) {
		$supported = 1;
	}
	if (!$supported) {
		continue; // show only supported
	}

	$i++;
	print '<option name="'.$keyforsupportedoauth2array.'" value="'.str_replace('_NAME', '', $keyforsupportedoauth2array).'">'.$supportedoauth2array[$keyforsupportedoauth2array]['name'].'</option>'."\n";
}
print '</select>';
print ajax_combobox('provider');
print ' <input type="text" name="label" value="" placeholder="'.$langs->trans("Label").'" pattern="^\S+$" title="'.$langs->trans("SpaceOrSpecialCharAreNotAllowed").'">';
print ' <input type="submit" class="button small" name="add" value="'.$langs->trans("Add").'">';
print '</form>';

print '<br>';
print '<br>';

print dol_get_fiche_end();


//var_dump($list);
foreach ($conf->global as $key => $val) {
	if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key)) {
		$provider = preg_replace('/_ID$/', '', $key);
		$listinsetup[] = array($provider.'_NAME', $provider.'_ID', $provider.'_SECRET', 'OAUTH Provider '.str_replace('OAUTH_', '', $provider));
	}
}


if (count($listinsetup) > 0) {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';

	$i = 0;

	// $list is defined into oauth.lib.php to the list of supporter OAuth providers.
	foreach ($listinsetup as $key) {
		$supported = 0;
		$keyforsupportedoauth2array = $key[0];						// May be OAUTH_GOOGLE_NAME or OAUTH_GOOGLE_xxx_NAME
		$keyforsupportedoauth2array = preg_replace('/^OAUTH_/', '', $keyforsupportedoauth2array);
		$keyforsupportedoauth2array = preg_replace('/_NAME$/', '', $keyforsupportedoauth2array);
		if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
			$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
		} else {
			$keyforprovider = '';
		}
		$keyforsupportedoauth2array = preg_replace('/-.*$/', '', $keyforsupportedoauth2array);
		$keyforsupportedoauth2array = 'OAUTH_'.$keyforsupportedoauth2array.'_NAME';

		if (in_array($keyforsupportedoauth2array, array_keys($supportedoauth2array))) {
			$supported = 1;
		}
		if (!$supported) {
			continue; // show only supported
		}

		$i++;

		print '<tr class="liste_titre'.($i > 1 ? ' liste_titre_add' : '').'">';
		// Api Name
		$label = $langs->trans($keyforsupportedoauth2array);
		print '<td>';
		print img_picto('', $supportedoauth2array[$keyforsupportedoauth2array]['picto'], 'class="pictofixedwidth"');
		print $label;
		if ($keyforprovider) {
			print ' (<b>'.$keyforprovider.'</b>)';
		} else {
			print ' (<b>'.$langs->trans("NoName").'</b>)';
		}
		print '</td>';
		print '<td>';
		if (!empty($supportedoauth2array[$keyforsupportedoauth2array]['urlforcredentials'])) {
			print $langs->trans("OAUTH_URL_FOR_CREDENTIAL", $supportedoauth2array[$keyforsupportedoauth2array]['urlforcredentials']);
		}
		print '</td>';
		print '</tr>';

		if ($supported) {
			$redirect_uri = $urlwithroot.'/core/modules/oauth/'.$supportedoauth2array[$keyforsupportedoauth2array]['callbackfile'].'_oauthcallback.php';
			print '<tr class="oddeven value">';
			print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
			print '<td><input style="width: 80%" type"text" name="uri'.$keyforsupportedoauth2array.'" value="'.$redirect_uri.'">';
			print '</td></tr>';
		} else {
			print '<tr class="oddeven value">';
			print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
			print '<td>'.$langs->trans("FeatureNotYetSupported").'</td>';
			print '</td></tr>';
		}

		// Api Id
		print '<tr class="oddeven value">';
		print '<td><label for="'.$key[1].'">'.$langs->trans("OAUTH_ID").'</label></td>';
		print '<td><input type="text" size="100" id="'.$key[1].'" name="'.$key[1].'" value="'.$conf->global->{$key[1]}.'">';
		print '</td></tr>';

		// Api Secret
		print '<tr class="oddeven value">';
		print '<td><label for="'.$key[2].'">'.$langs->trans("OAUTH_SECRET").'</label></td>';
		print '<td><input type="password" size="100" id="'.$key[2].'" name="'.$key[2].'" value="'.$conf->global->{$key[2]}.'">';
		print '</td></tr>';
	}

	print '</table>'."\n";
	print '</div>';

	print $form->buttonsSaveCancel("Modify", '');

	print '</form>';
}

// End of page
llxFooter();
$db->close();
