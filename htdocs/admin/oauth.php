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
 */

/**
 * \file        htdocs/admin/oauth.php
 * \ingroup     oauth
 * \brief       Setup page to configure oauth access api
 */


// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';

$supportedoauth2array = getSupportedOauth2Array();

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

$servicetoeditname = GETPOST('servicetoeditname', 'aZ09');

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
			dolibarr_set_const($db, $constname, $langs->trans('ToComplete'), 'chaine', 0, '', $conf->entity);
			setEventMessages($langs->trans("OAuthProviderAdded"), null);
		}
	}
}
if ($action == 'update') {
	foreach ($conf->global as $key => $val) {
		if (!empty($val) && preg_match('/^OAUTH_.+_ID$/', $key)) {
			$constvalue = str_replace('_ID', '', $key);
			$newconstvalue = $constvalue;
			if (GETPOSTISSET($constvalue.'_NAME')) {
				$newconstvalue = preg_replace('/-.*$/', '', $constvalue).'-'.GETPOST($constvalue.'_NAME');
			}

			if (GETPOSTISSET($constvalue.'_ID')) {
				if (!dolibarr_set_const($db, $newconstvalue.'_ID', GETPOST($constvalue.'_ID'), 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			}
			// If we reset this provider, we also remove the secret
			if (GETPOSTISSET($constvalue.'_SECRET')) {
				if (!dolibarr_set_const($db, $newconstvalue.'_SECRET', GETPOST($constvalue.'_ID') ? GETPOST($constvalue.'_SECRET') : '', 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			}
			if (GETPOSTISSET($constvalue.'_URLAUTHORIZE')) {
				if (!dolibarr_set_const($db, $newconstvalue.'_URLAUTHORIZE', GETPOST($constvalue.'_URLAUTHORIZE'), 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			}
			if (GETPOSTISSET($constvalue.'_TENANT')) {
				if (!dolibarr_set_const($db, $constvalue.'_TENANT', GETPOST($constvalue.'_TENANT'), 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			}
			if (GETPOSTISSET($constvalue.'_SCOPE')) {
				if (is_array(GETPOST($constvalue.'_SCOPE'))) {
					$scopestring = implode(',', GETPOST($constvalue.'_SCOPE'));
				} else {
					$scopestring = GETPOST($constvalue.'_SCOPE');
				}
				if (!dolibarr_set_const($db, $newconstvalue.'_SCOPE', $scopestring, 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			} elseif ($newconstvalue !== $constvalue) {
				if (!dolibarr_set_const($db, $newconstvalue.'_SCOPE', '', 'chaine', 0, '', $conf->entity)) {
					$error++;
				}
			}

			// If name changed, we have to delete old const and proceed few other changes
			if ($constvalue !== $newconstvalue) {
				dolibarr_del_const($db, $constvalue.'_ID', $conf->entity);
				dolibarr_del_const($db, $constvalue.'_SECRET', $conf->entity);
				dolibarr_del_const($db, $constvalue.'_URLAUTHORIZE', $conf->entity);
				dolibarr_del_const($db, $constvalue.'_SCOPE', $conf->entity);

				// Update name of token
				$oldname = preg_replace('/^OAUTH_/', '', $constvalue);
				$oldprovider = ucfirst(strtolower(preg_replace('/-.*$/', '', $oldname)));
				$oldlabel = preg_replace('/^.*-/', '', $oldname);
				$newlabel = preg_replace('/^.*-/', '', $newconstvalue);


				$sql = "UPDATE ".MAIN_DB_PREFIX."oauth_token";
				$sql.= " SET service = '".$db->escape($oldprovider."-".$newlabel)."'";
				$sql.= " WHERE  service = '".$db->escape($oldprovider."-".$oldlabel)."'";


				$resql = $db->query($sql);
				if (!$resql) {
					$error++;
				}

				// Update other const that was using the renamed key as token (might not be exhaustive)
				if (getDolGlobalString('MAIN_MAIL_SMTPS_OAUTH_SERVICE') == $oldname) {
					if (!dolibarr_set_const($db, 'MAIN_MAIL_SMTPS_OAUTH_SERVICE', strtoupper($oldprovider).'-'.$newlabel, 'chaine', 0, '', $conf->entity)) {
						$error++;
					}
				}
			}
		}
	}


	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
}

if ($action == 'confirm_delete') {
	$provider = GETPOST('provider', 'aZ09');
	$label = GETPOST('label');

	$globalkey = empty($provider) ? $label : $label.'-'.$provider;

	if (getDolGlobalString($globalkey.'_ID') && getDolGlobalString($globalkey.'_SECRET')) { // If ID and secret exist, we delete first the token
		$backtourl = DOL_URL_ROOT.'/admin/oauth.php?action=delete_entry&provider='.$provider.'&label='.$label.'&token='.newToken();
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT;
		$callbacktodel = $urlwithroot;
		if ($label == 'OAUTH_GOOGLE') {
			$callbacktodel .= '/core/modules/oauth/google_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		} elseif ($label == 'OAUTH_GITHUB') {
			$callbacktodel .= '/core/modules/oauth/github_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		} elseif ($label == 'OAUTH_STRIPE_LIVE') {
			$callbacktodel .= '/core/modules/oauth/stripelive_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		} elseif ($label == 'OAUTH_STRIPE_TEST') {
			$callbacktodel .= '/core/modules/oauth/stripetest_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		} elseif ($label == 'OAUTH_MICROSOFT') {
			$callbacktodel .= '/core/modules/oauth/microsoft_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		} elseif ($label == 'OAUTH_MICROSOFT2') {
			$callbacktodel .= '/core/modules/oauth/microsoft2_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		} elseif ($label == 'OAUTH_OTHER') {
			$callbacktodel .= '/core/modules/oauth/generic_oauthcallback.php?action=delete&keyforprovider='.$provider.'&token='.newToken().'&backtourl='.urlencode($backtourl);
		}
		header("Location: ".$callbacktodel);
		exit;
	} else {
		$action = 'delete_entry';
	}
}

if ($action == 'delete_entry') {
	$provider = GETPOST('provider', 'aZ09');
	$label = GETPOST('label');

	$globalkey = empty($provider) ? $label : $label.'-'.$provider;

	if (!dolibarr_del_const($db, $globalkey.'_NAME', $conf->entity) || !dolibarr_del_const($db, $globalkey.'_ID', $conf->entity) || !dolibarr_del_const($db, $globalkey.'_SECRET', $conf->entity) ||  !dolibarr_del_const($db, $globalkey.'_URLAUTHORIZE', $conf->entity) || !dolibarr_del_const($db, $globalkey.'_SCOPE', $conf->entity)) {
		setEventMessages($langs->trans("ErrorInEntryDeletion"), null, 'errors');
		$error++;
	} else {
		setEventMessages($langs->trans("EntryDeleted"), null);
	}
}

/*
 * View
 */

$form = new Form($db);

$title = $langs->trans('ConfigOAuth');
$help_url = 'EN:Module_OAuth|FR:Module_OAuth_FR|ES:Módulo_OAuth_ES';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-admin page-oauth');

// Confirmation of action process
if ($action == 'delete') {
	$formquestion = array();
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?provider='.GETPOST('provider').'&label='.GETPOST('label'), $langs->trans('OAuthServiceConfirmDeleteTitle'), $langs->trans('OAuthServiceConfirmDeleteMessage'), 'confirm_delete', $formquestion, 0, 1, 220);
	print $formconfirm;
}


$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($title, $linkback, 'title_setup');

print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="add">';

$head = oauthadmin_prepare_head();

print dol_get_fiche_head($head, 'services', '', -1, '');


print '<span class="opacitymedium">'.$langs->trans("ListOfSupportedOauthProviders").'</span><br><br>';


print '<select name="provider" id="provider" class="minwidth150">';
print '<option name="-1" value="-1">'.$langs->trans("OAuthProvider").'</option>';
$list = getAllOauth2Array();
// TODO Make a loop directly on getSupportedOauth2Array() and remove getAllOauth2Array()
foreach ($list as $key) {
	$supported = 0;
	$keyforsupportedoauth2array = $key[0];

	if (in_array($keyforsupportedoauth2array, array_keys($supportedoauth2array))) {
		$supported = 1;
	}
	if (!$supported) {
		continue; // show only supported
	}

	print '<option name="'.$keyforsupportedoauth2array.'" value="'.str_replace('_NAME', '', $keyforsupportedoauth2array).'">'.$supportedoauth2array[$keyforsupportedoauth2array]['name'].'</option>'."\n";
}
print '</select>';
print ajax_combobox('provider');
print ' <input type="text" name="label" value="" placeholder="'.$langs->trans("Label").'" pattern="^\S+$" title="'.$langs->trans("SpaceOrSpecialCharAreNotAllowed").'">';
print ' <input type="submit" class="button small" name="add" value="'.$langs->trans("Add").'">';

print '<br>';
print '<br>';

print dol_get_fiche_end();

print '</form>';

$listinsetup = [];
// Define $listinsetup
foreach ($conf->global as $key => $val) {
	if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key)) {
		$provider = preg_replace('/_ID$/', '', $key);
		$listinsetup[] = array(
			$provider.'_NAME',
			$provider.'_ID',
			$provider.'_SECRET',
			$provider.'_URLAUTHORIZE',	// For custom oauth links
			$provider.'_SCOPE'			// For custom oauth links
		);
	}
}


if (count($listinsetup) > 0) {
	print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
	print '<input type="hidden" name="token" value="'.newToken().'">';
	print '<input type="hidden" name="action" value="update">';

	print '<div class="div-table-responsive-no-min">';

	$i = 0;

	// $list is defined into oauth.lib.php to the list of supporter OAuth providers.
	foreach ($listinsetup as $key) {
		$supported = 0;
		$keyforsupportedoauth2array = $key[0];						// May be OAUTH_GOOGLE_NAME or OAUTH_GOOGLE_xxx_NAME
		$keyforsupportedoauth2array = preg_replace('/^OAUTH_/', '', $keyforsupportedoauth2array);
		$keyforsupportedoauth2array = preg_replace('/_NAME$/', '', $keyforsupportedoauth2array);
		if (preg_match('/^.*-/', $keyforsupportedoauth2array)) {
			$keybeforeprovider = preg_replace('/-.*$/', '', $keyforsupportedoauth2array);
			$keyforprovider = preg_replace('/^.*-/', '', $keyforsupportedoauth2array);
		} else {
			$keybeforeprovider = $keyforsupportedoauth2array;
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

		print '<table class="noborder centpercent">';

		// OAUTH service name
		$label = $langs->trans($keyforsupportedoauth2array);
		print '<tr class="liste_titre'.($i > 1 ? ' liste_titre_add' : '').'">';
		print '<td class="titlefieldcreate">';
		print img_picto('', $supportedoauth2array[$keyforsupportedoauth2array]['picto'], 'class="pictofixedwidth"');
		if ($label == $keyforsupportedoauth2array) {
			print $supportedoauth2array[$keyforsupportedoauth2array]['name'];
		} else {
			print $label;
		}
		if ($servicetoeditname == $key[0]) {
			print ' (<input style="width: 20%" type="text" name="'.$key[0].'" value="'.$keyforprovider.'" >)';
		} elseif ($keyforprovider) {
			print ' (<b>'.$keyforprovider.'</b>)';
		} else {
			print ' (<b>'.$langs->trans("NoName").'</b>)';
		}
		if (!($servicetoeditname == $key[0])) {
			print '<a class="editfielda reposition" href="'.$_SERVER["PHP_SELF"].'?token='.newToken().'&servicetoeditname='.urlencode($key[0]).'">'.img_edit($langs->transnoentitiesnoconv('Edit'), 1).'</a>';
		}
		print '</td>';
		print '<td>';
		if (!empty($supportedoauth2array[$keyforsupportedoauth2array]['urlforcredentials'])) {
			print $langs->trans("OAUTH_URL_FOR_CREDENTIAL", $supportedoauth2array[$keyforsupportedoauth2array]['urlforcredentials']);
		}
		print '</td>';

		// Delete
		print '<td>';
		$label = preg_replace('/_NAME$/', '', $keyforsupportedoauth2array);
		print '<a href="'.$_SERVER["PHP_SELF"].'?action=delete&token='.newToken().'&provider='.urlencode($keyforprovider).'&label='.urlencode($label).'">';
		print img_picto('', 'delete');
		print '</a>';

		print '</form>';
		print '</td>';

		print '</tr>';

		if ($supported) {
			$redirect_uri = $urlwithroot.'/core/modules/oauth/'.$supportedoauth2array[$keyforsupportedoauth2array]['callbackfile'].'_oauthcallback.php';
			print '<tr class="oddeven value">';
			print '<td>'.$form->textwithpicto($langs->trans("RedirectURL"), $langs->trans("UseTheFollowingUrlAsRedirectURI")).'</td>';
			print '<td><input style="width: 80%" type="text" name="uri'.$keyforsupportedoauth2array.'" id="uri'.$keyforsupportedoauth2array.$keyforprovider.'" value="'.$redirect_uri.'" disabled>';
			print ajax_autoselect('uri'.$keyforsupportedoauth2array.$keyforprovider);
			print '</td>';
			print '<td></td>';
			print '</tr>';

			if ($keyforsupportedoauth2array == 'OAUTH_OTHER_NAME') {
				print '<tr class="oddeven value">';
				print '<td>'.$langs->trans("URLOfServiceForAuthorization").'</td>';
				print '<td><input style="width: 80%" type="text" name="'.$key[3].'" value="'.getDolGlobalString($key[3]).'" >';
				print '</td>';
				print '<td></td>';
				print '</tr>';
			}
		} else {
			print '<tr class="oddeven value">';
			print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
			print '<td>'.$langs->trans("FeatureNotYetSupported").'</td>';
			print '</td>';
			print '<td></td>';
			print '</tr>';
		}

		// Api Id
		print '<tr class="oddeven value">';
		print '<td><label for="'.$key[1].'">'.$langs->trans("OAUTH_ID").'</label></td>';
		print '<td><input type="text" size="100" id="'.$key[1].'" name="'.$key[1].'" value="'.getDolGlobalString($key[1]).'">';
		print '</td>';
		print '<td></td>';
		print '</tr>';

		// Api Secret
		print '<tr class="oddeven value">';
		print '<td><label for="'.$key[2].'">'.$langs->trans("OAUTH_SECRET").'</label></td>';
		print '<td><input type="password" size="100" id="'.$key[2].'" name="'.$key[2].'" value="'.getDolGlobalString($key[2]).'">';
		print '</td>';
		print '<td></td>';
		print '</tr>';

		// Tenant
		if ($keybeforeprovider == 'MICROSOFT') {
			print '<tr class="oddeven value">';
			print '<td><label for="'.$key[2].'">'.$langs->trans("OAUTH_TENANT").'</label></td>';
			print '<td><input type="text" size="100" id="OAUTH_'.$keybeforeprovider.($keyforprovider ? '-'.$keyforprovider : '').'_TENANT" name="OAUTH_'.$keybeforeprovider.($keyforprovider ? '-'.$keyforprovider : '').'_TENANT" value="'.getDolGlobalString('OAUTH_'.$keybeforeprovider.($keyforprovider ? '-'.$keyforprovider : '').'_TENANT').'">';
			print '</td>';
			print '<td></td>';
			print '</tr>';
		}

		// TODO Move this into token generation ?
		if ($supported) {
			if ($keyforsupportedoauth2array == 'OAUTH_OTHER_NAME') {
				print '<tr class="oddeven value">';
				print '<td>'.$langs->trans("Scopes").'</td>';
				print '<td>';
				print '<input style="width: 80%" type"text" name="'.$key[4].'" value="'.getDolGlobalString($key[4]).'" >';
				print '</td>';
				print '<td></td>';
				print '</tr>';
			} else {
				$availablescopes = array_flip(explode(',', $supportedoauth2array[$keyforsupportedoauth2array]['availablescopes']));
				$currentscopes = explode(',', getDolGlobalString($key[4]));
				$scopestodispay = array();
				foreach ($availablescopes as $keyscope => $valscope) {
					if (in_array($keyscope, $currentscopes)) {
						$scopestodispay[$keyscope] = 1;
					} else {
						$scopestodispay[$keyscope] = 0;
					}
				}
				// Api Scope
				print '<tr class="oddeven value">';
				print '<td>'.$langs->trans("Scopes").'</td>';
				print '<td>';
				foreach ($scopestodispay as $scope => $val) {
					print '<input type="checkbox" id="'.$keyforprovider.$scope.'" name="'.$key[4].'[]" value="'.$scope.'"'.($val ? ' checked' : '').'>';
					print '<label style="margin-right: 10px" for="'.$keyforprovider.$scope.'">'.$scope.'</label>';
				}
				print '</td>';
				print '<td></td>';
				print '</tr>';
			}
		} else {
			print '<tr class="oddeven value">';
			print '<td>'.$langs->trans("UseTheFollowingUrlAsRedirectURI").'</td>';
			print '<td>'.$langs->trans("FeatureNotYetSupported").'</td>';
			print '</td>';
			print '<td></td>';
			print '</tr>';
		}

		print '</table>'."\n";

		print '<br>';
	}

	print '</div>';

	print $form->buttonsSaveCancel("Save", '');

	print '</form>';
}

// End of page
llxFooter();
$db->close();
