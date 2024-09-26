<?php
/* Copyright (C) 2013-2016  Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014-2024	Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2020		Nicolas ZABOURI      <info@inovea-conseil.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * \file        htdocs/admin/oauthlogintokens.php
 * \ingroup     oauth
 * \brief       Setup page to configure oauth access to login information
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/oauth.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';

use OAuth\Common\Storage\DoliStorage;
use OAuth\Common\Consumer\Credentials;

$supportedoauth2array = getSupportedOauth2Array();

// Load translation files required by the page
$langs->loadLangs(array('admin', 'printing', 'oauth'));

$action = GETPOST('action', 'aZ09');
$mode = GETPOST('mode', 'alpha');
$value = GETPOST('value', 'alpha');
$varname = GETPOST('varname', 'alpha');
$driver = GETPOST('driver', 'alpha');

if (!empty($driver)) {
	$langs->load($driver);
}

if (!$mode) {
	$mode = 'setup';
}

if (!$user->admin) {
	accessforbidden();
}


/*
 * Action
 */

/*if (($mode == 'test' || $mode == 'setup') && empty($driver))
{
	setEventMessages($langs->trans('PleaseSelectaDriverfromList'), null);
	header("Location: ".$_SERVER['PHP_SELF'].'?mode=config');
	exit;
}*/

if ($action == 'setconst' && $user->admin) {
	$error = 0;
	$db->begin();

	$setupconstarray = GETPOST('setupdriver', 'array');

	foreach ($setupconstarray as $setupconst) {
		//print '<pre>'.print_r($setupconst, true).'</pre>';

		$constname = dol_escape_htmltag($setupconst['varname']);
		$constvalue = dol_escape_htmltag($setupconst['value']);
		$consttype = dol_escape_htmltag($setupconst['type']);
		$constnote = dol_escape_htmltag($setupconst['note']);

		$result = dolibarr_set_const($db, $constname, $constvalue, $consttype, 0, $constnote, $conf->entity);
		if (!($result > 0)) {
			$error++;
		}
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		$db->rollback();
		dol_print_error($db);
	}
	$action = '';
}

if ($action == 'setvalue' && $user->admin) {
	$db->begin();

	$result = dolibarr_set_const($db, $varname, $value, 'chaine', 0, '', $conf->entity);
	if (!($result > 0)) {
		$error++;
	}

	if (!$error) {
		$db->commit();
		setEventMessages($langs->trans("SetupSaved"), null);
	} else {
		$db->rollback();
		dol_print_error($db);
	}
	$action = '';
}

// Test a refresh of a token using the refresh token
if ($action == 'refreshtoken' && $user->admin) {
	$keyforprovider = GETPOST('keyforprovider');
	$OAUTH_SERVICENAME = GETPOST('service');

	// Show value of token
	$tokenobj = null;
	// Load OAUth libraries
	require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
	// Dolibarr storage
	$storage = new DoliStorage($db, $conf, $keyforprovider);
	try {
		// $OAUTH_SERVICENAME is for example 'Google-keyforprovider'
		print '<!-- '.$OAUTH_SERVICENAME.' -->'."\n";

		dol_syslog("oauthlogintokens.php: Read token for service ".$OAUTH_SERVICENAME);
		$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);

		$expire = ($tokenobj->getEndOfLife() !== -9002 && $tokenobj->getEndOfLife() !== -9001 && time() > ($tokenobj->getEndOfLife() - 30));
		// We have to save the refresh token in a memory variable because Google give it only once
		$refreshtoken = $tokenobj->getRefreshToken();
		print '<!-- data stored into field token: '.$storage->token.' - expire '.((string) $expire).' -->';

		//print $tokenobj->getExtraParams()['id_token'].'<br>';
		//print $tokenobj->getAccessToken().'<br>';
		//print $tokenobj->getRefreshToken().'<br>';

		//var_dump($expire);

		// We do the refresh even if not expired, this is the goal of action.
		$oauthname = explode('-', $OAUTH_SERVICENAME);
		$keyforoauthservice = strtoupper($oauthname[0]).(empty($oauthname[1]) ? '' : '-'.$oauthname[1]);
		$credentials = new Credentials(
			getDolGlobalString('OAUTH_'.$keyforoauthservice.'_ID'),
			getDolGlobalString('OAUTH_'.$keyforoauthservice.'_SECRET'),
			getDolGlobalString('OAUTH_'.$keyforoauthservice.'_URLCALLBACK')
			);

		$serviceFactory = new \OAuth\ServiceFactory();
		$httpClient = new \OAuth\Common\Http\Client\CurlClient();
		// TODO Set options for proxy and timeout
		// $params=array('CURLXXX'=>value, ...)
		//$httpClient->setCurlParameters($params);
		$serviceFactory->setHttpClient($httpClient);

		// ex service is Google-Emails we need only the first part Google
		$apiService = $serviceFactory->createService($oauthname[0], $credentials, $storage, array());

		if ($apiService instanceof OAuth\OAuth2\Service\AbstractService || $apiService instanceof OAuth\OAuth1\Service\AbstractService) {
			// ServiceInterface does not provide refreshAccessToekn, AbstractService does
			dol_syslog("oauthlogintokens.php: call refreshAccessToken to get the new access token");
			$tokenobj = $apiService->refreshAccessToken($tokenobj);		// This call refresh and store the new token (but does not include the refresh token)

			dol_syslog("oauthlogintokens.php: call setRefreshToken");
			$tokenobj->setRefreshToken($refreshtoken);	// Restore the refresh token

			dol_syslog("oauthlogintokens.php: call storeAccessToken to save the new access token + the old refresh token");
			$storage->storeAccessToken($OAUTH_SERVICENAME, $tokenobj);	// This save the new token including the refresh token

			if ($expire) {
				setEventMessages($langs->trans("OldTokenWasExpiredItHasBeenRefresh"), null, 'mesgs');
			} else {
				setEventMessages($langs->trans("OldTokenWasNotExpiredButItHasBeenRefresh"), null, 'mesgs');
			}
		} else {
			dol_print_error($db, 'apiService is not a correct OAUTH2 Abstract service');
		}

		dol_syslog("oauthlogintokens.php: Read token again for service ".$OAUTH_SERVICENAME);
		$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);
	} catch (Exception $e) {
		// Return an error if token not found
		print $e->getMessage();
	}
}


/*
 * View
 */

// Define $urlwithroot
$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

$form = new Form($db);

$title = $langs->trans("TokenManager");
$help_url = 'EN:Module_OAuth|FR:Module_OAuth_FR|ES:Módulo_OAuth_ES';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-admin page-oauthlogintokens');

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans('ConfigOAuth'), $linkback, 'title_setup');

$head = oauthadmin_prepare_head();

print dol_get_fiche_head($head, 'tokengeneration', '', -1, '');

if (GETPOST('error')) {
	setEventMessages(GETPOST('error'), null, 'errors');
}

if ($mode == 'setup' && $user->admin) {
	print '<span class="opacitymedium">'.$langs->trans("OAuthSetupForLogin")."</span><br><br>\n";

	// Define $listinsetup
	$listinsetup = array();
	foreach ($conf->global as $key => $val) {
		if (!empty($val) && preg_match('/^OAUTH_.*_ID$/', $key)) {
			$provider = preg_replace('/_ID$/', '', $key);
			$listinsetup[] = array(
				$provider.'_NAME',
				$provider.'_ID',
				$provider.'_SECRET',
				$provider.'_URL',			// For custom oauth links
				$provider.'_SCOPE'			// For custom oauth links
			);
		}
	}

	$oauthstateanticsrf = bin2hex(random_bytes(128 / 8));

	// $list is defined into oauth.lib.php to the list of supported OAuth providers.
	if (!empty($listinsetup)) {
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


			$OAUTH_SERVICENAME = (empty($supportedoauth2array[$keyforsupportedoauth2array]['name']) ? 'Unknown' : $supportedoauth2array[$keyforsupportedoauth2array]['name'].($keyforprovider ? '-'.$keyforprovider : ''));

			$shortscope = '';
			if (getDolGlobalString($key[4])) {
				$shortscope = getDolGlobalString($key[4]);
			}
			$state = $shortscope;	// TODO USe a better state

			$urltorefresh = $_SERVER["PHP_SELF"].'?action=refreshtoken&token='.newToken();

			// Define $urltorenew, $urltodelete, $urltocheckperms
			if ($keyforsupportedoauth2array == 'OAUTH_GITHUB_NAME') {
				// List of keys that will be converted into scopes (from constants 'SCOPE_state_in_uppercase' in file of service).
				// We pass this param list in to 'state' because we need it before and after the redirect.

				// Note: github does not accept csrf key inside the state parameter (only known values)
				$urltorenew = $urlwithroot.'/core/modules/oauth/github_oauthcallback.php?shortscope='.urlencode($shortscope).'&state='.urlencode($shortscope).'&backtourl='.urlencode(DOL_URL_ROOT.'/admin/oauthlogintokens.php');
				$urltodelete = $urlwithroot.'/core/modules/oauth/github_oauthcallback.php?action=delete&token='.newToken().'&backtourl='.urlencode(DOL_URL_ROOT.'/admin/oauthlogintokens.php');
				$urltocheckperms = 'https://github.com/settings/applications/';
			} elseif ($keyforsupportedoauth2array == 'OAUTH_GOOGLE_NAME') {
				// List of keys that will be converted into scopes (from constants 'SCOPE_state_in_uppercase' in file of service).
				// List of scopes for Google are here: https://developers.google.com/identity/protocols/oauth2/scopes
				// We pass this key list into the param 'state' because we need it before and after the redirect.
				$urltorenew = $urlwithroot.'/core/modules/oauth/google_oauthcallback.php?shortscope='.urlencode($shortscope).'&state='.urlencode($state).'-'.$oauthstateanticsrf.'&backtourl='.urlencode(DOL_URL_ROOT.'/admin/oauthlogintokens.php');
				$urltodelete = $urlwithroot.'/core/modules/oauth/google_oauthcallback.php?action=delete&token='.newToken().'&backtourl='.urlencode(DOL_URL_ROOT.'/admin/oauthlogintokens.php');
				$urltocheckperms = 'https://security.google.com/settings/security/permissions';
			} elseif (!empty($supportedoauth2array[$keyforsupportedoauth2array]['returnurl'])) {
				$urltorenew = $urlwithroot.$supportedoauth2array[$keyforsupportedoauth2array]['returnurl'].'?shortscope='.urlencode($shortscope).'&state='.urlencode($state).'&backtourl='.urlencode(DOL_URL_ROOT.'/admin/oauthlogintokens.php');
				$urltodelete = $urlwithroot.$supportedoauth2array[$keyforsupportedoauth2array]['returnurl'].'?action=delete&token='.newToken().'&backtourl='.urlencode(DOL_URL_ROOT.'/admin/oauthlogintokens.php');
				$urltocheckperms = '';
			} else {
				$urltorenew = '';
				$urltodelete = '';
				$urltocheckperms = '';
			}

			if ($urltorenew) {
				$urltorenew .= '&keyforprovider='.urlencode($keyforprovider);
			}
			if ($urltorefresh) {
				$urltorefresh .= '&keyforprovider='.urlencode($keyforprovider).'&service='.urlencode($OAUTH_SERVICENAME);
			}
			if ($urltodelete) {
				$urltodelete .= '&keyforprovider='.urlencode($keyforprovider);
			}

			// Show value of token
			$tokenobj = null;
			// Token
			require_once DOL_DOCUMENT_ROOT.'/includes/OAuth/bootstrap.php';
			// Dolibarr storage
			$storage = new DoliStorage($db, $conf, $keyforprovider);
			try {
				// $OAUTH_SERVICENAME is for example 'Google-keyforprovider'
				print '<!-- '.$OAUTH_SERVICENAME.' -->'."\n";
				$tokenobj = $storage->retrieveAccessToken($OAUTH_SERVICENAME);
				print '<!-- data stored into field token: '.$storage->token.' -->';
				//print $tokenobj->getExtraParams()['id_token'].'<br>';
				//print $tokenobj->getAccessToken().'<br>';
			} catch (Exception $e) {
				// Return an error if token not found
				//print $e->getMessage();
			}

			// Set other properties
			$refreshtoken = false;
			$expiredat = '';

			$expire = false;
			// Is token expired or will token expire in the next 30 seconds
			if (is_object($tokenobj)) {
				$expire = ($tokenobj->getEndOfLife() !== $tokenobj::EOL_NEVER_EXPIRES && $tokenobj->getEndOfLife() !== $tokenobj::EOL_UNKNOWN && time() > ($tokenobj->getEndOfLife() - 30));
			}
			if ($key[1] != '' && $key[2] != '') {
				if (is_object($tokenobj)) {
					$refreshtoken = $tokenobj->getRefreshToken();

					$endoflife = $tokenobj->getEndOfLife();
					if ($endoflife == $tokenobj::EOL_NEVER_EXPIRES) {
						$expiredat = $langs->trans("Never");
					} elseif ($endoflife == $tokenobj::EOL_UNKNOWN) {
						$expiredat = $langs->trans("Unknown");
					} else {
						$expiredat = dol_print_date($endoflife, "dayhour", 'tzuserrel');
					}
				}
			}

			$submit_enabled = 0;

			print '<form method="post" action="'.$_SERVER["PHP_SELF"].'?mode=setup&amp;driver='.$driver.'" autocomplete="off">';
			print '<input type="hidden" name="token" value="'.newToken().'">';
			print '<input type="hidden" name="action" value="setconst">';
			print '<input type="hidden" name="page_y" value="">';

			print '<div class="div-table-responsive-no-min">';
			print '<table class="noborder centpercent">'."\n";

			// Api Name
			$label = $langs->trans($keyforsupportedoauth2array);
			print '<tr class="liste_titre">';
			print '<th class="titlefieldcreate">';
			print img_picto('', $supportedoauth2array[$keyforsupportedoauth2array]['picto'], 'class="pictofixedwidth"');
			if ($label == $keyforsupportedoauth2array) {
				print $supportedoauth2array[$keyforsupportedoauth2array]['name'];
			} else {
				print $label;
			}
			if ($keyforprovider) {
				print ' (<b>'.$keyforprovider.'</b>)';
			} else {
				print ' (<b>'.$langs->trans("NoName").'</b>)';
			}
			print '</th>';
			print '<th></th>';
			print '<th></th>';
			print "</tr>\n";

			print '<tr class="oddeven">';
			print '<td>';
			//var_dump($key);
			print $langs->trans("OAuthIDSecret").'</td>';
			print '<td>';
			print '<span class="opacitymedium">'.$langs->trans("SeePreviousTab").'</span>';
			print '</td>';
			print '<td>';
			print '</td>';
			print '</tr>'."\n";

			// Scopes
			print '<tr class="oddeven">';
			print '<td>'.$langs->trans("Scopes").'</td>';
			print '<td colspan="2">';
			$currentscopes = getDolGlobalString($key[4]);
			print $currentscopes;
			print '</td></tr>';

			print '<tr class="oddeven">';
			print '<td>';
			//var_dump($key);
			print $langs->trans("IsTokenGenerated");
			print '</td>';
			print '<td>';
			if ($keyforprovider != 'Login') {
				if (is_object($tokenobj)) {
					print $form->textwithpicto(yn(1), $langs->trans("HasAccessToken").' : '.dol_print_date($storage->date_modification, 'dayhour').' state='.dol_escape_htmltag($storage->state));
				} else {
					print '<span class="opacitymedium">'.$langs->trans("NoAccessToken").'</span>';
				}
			} else {
				print '<span class="opacitymedium">'.$langs->trans("TokenNotRequiredForOAuthLogin").'</span>';
			}
			print '</td>';
			print '<td width="50%">';
			if ($keyforprovider != 'Login') {
				// Links to delete/checks token
				if (is_object($tokenobj)) {
					//test on $storage->hasAccessToken($OAUTH_SERVICENAME) ?
					if ($urltodelete) {
						print '<a class="button button-delete smallpaddingimp reposition marginright" href="'.$urltodelete.'">'.$langs->trans('DeleteAccess').'</a>';
					} else {
						print '<span class="opacitymedium marginright">'.$langs->trans('GoOnTokenProviderToDeleteToken').'</span>';
					}
				}
				// Request remote token
				if ($urltorenew) {
					print '<a class="button smallpaddingimp reposition classfortooltip marginright" href="'.$urltorenew.'" title="'.dolPrintHTMLForAttribute($langs->trans('RequestAccess')).'">'.$langs->trans('GetAccess').'</a>';
				}
				// Request remote token
				if ($urltorefresh && $refreshtoken) {
					print '<a class="button smallpaddingimp reposition classfortooltip marginright" href="'.$urltorefresh.'" title="'.dolPrintHTMLForAttribute($langs->trans('RefreshTokenHelp')).'">'.$langs->trans('RefreshToken').'</a>';
				}

				// Check remote access
				if ($urltocheckperms) {
					print '<br>'.$langs->trans("ToCheckDeleteTokenOnProvider", $OAUTH_SERVICENAME).': <a href="'.$urltocheckperms.'" target="_'.strtolower($OAUTH_SERVICENAME).'">'.$urltocheckperms.'</a>';
				}
			}
			print '</td>';
			print '</tr>';

			if (is_object($tokenobj)) {
				print '<tr class="oddeven">';
				print '<td>';
				//var_dump($key);
				print $langs->trans("TokenRawValue").'</td>';
				print '<td colspan="2">';
				if (is_object($tokenobj)) {
					print '<textarea class="quatrevingtpercent small" rows="'.ROWS_4.'">'.var_export($tokenobj, true).'</textarea><br>'."\n";
				}
				print '</td>';
				print '</tr>'."\n";

				print '<tr class="oddeven">';
				print '<td>';
				//var_dump($key);
				print $langs->trans("AccessToken").'</td>';
				print '<td colspan="2">';
				$tokentoshow = $tokenobj->getAccessToken();
				print '<span class="" title="'.dol_escape_htmltag($tokentoshow).'">'.showValueWithClipboardCPButton($tokentoshow, 1, dol_trunc($tokentoshow, 32)).'</span>';
				//print 'Refresh: '.$tokenobj->getRefreshToken().'<br>';
				//print 'EndOfLife: '.$tokenobj->getEndOfLife().'<br>';
				//var_dump($tokenobj->getExtraParams());
				/*print '<br>Extra: <br><textarea class="quatrevingtpercent">';
				 print ''.join(',',$tokenobj->getExtraParams());
				 print '</textarea>';*/

				print '<span class="opacitymedium"> &nbsp; - &nbsp; ';
				print $langs->trans("ExpirationDate").': ';
				print '</span>';
				print $expiredat;

				print $expire ? ' ('.$langs->trans("TokenExpired").')' : ' ('.$langs->trans("TokenNotExpired").')';

				print '</td>';
				print '</tr>'."\n";

				// Token refresh
				print '<tr class="oddeven">';
				print '<td>';
				//var_dump($key);
				print $langs->trans("TOKEN_REFRESH");
				print '</td>';
				print '<td colspan="2">';
				print '<span class="" title="'.dol_escape_htmltag($refreshtoken).'">'.showValueWithClipboardCPButton($refreshtoken, 1, dol_trunc($refreshtoken, 32)).'</span>';
				print '</td>';
				print '</tr>';
			}

			print '</table>';
			print '</div>';

			if (!empty($driver)) {
				if ($submit_enabled) {
					print $form->buttonsSaveCancel("Modify", '');
				}
			}

			print '</form>';
			print '<br>';
		}
	}
}

if ($mode == 'test' && $user->admin) {
	print $langs->trans('PrintTestDesc'.$driver)."<br><br>\n";

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	if (!empty($driver)) {
		require_once DOL_DOCUMENT_ROOT.'/core/modules/printing/'.$driver.'.modules.php';
		$classname = 'printing_'.$driver;
		$langs->load($driver);
		$printer = new $classname($db);

		'@phan-var-force PrintingDriver $printer';

		//print '<pre>'.print_r($printer, true).'</pre>';
		if (count($printer->getlistAvailablePrinters())) {
			if ($printer->listAvailablePrinters() == 0) {
				print $printer->resprint;
			} else {
				setEventMessages($printer->error, $printer->errors, 'errors');
			}
		} else {
			print $langs->trans('PleaseConfigureDriverfromList');
		}
	}

	print '</table>';
	print '</div>';
}

if ($mode == 'userconf' && $user->admin) {
	print $langs->trans('PrintUserConfDesc'.$driver)."<br><br>\n";

	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th>'.$langs->trans("User").'</th>';
	print '<th>'.$langs->trans("PrintModule").'</th>';
	print '<th>'.$langs->trans("PrintDriver").'</th>';
	print '<th>'.$langs->trans("Printer").'</th>';
	print '<th>'.$langs->trans("PrinterLocation").'</th>';
	print '<th>'.$langs->trans("PrinterId").'</th>';
	print '<th>'.$langs->trans("NumberOfCopy").'</th>';
	print '<th class="center">'.$langs->trans("Delete").'</th>';
	print "</tr>\n";
	$sql = "SELECT p.rowid, p.printer_name, p.printer_location, p.printer_id, p.copy, p.module, p.driver, p.userid, u.login";
	$sql .= " FROM ".MAIN_DB_PREFIX."printing as p, ".MAIN_DB_PREFIX."user as u WHERE p.userid = u.rowid";
	$resql = $db->query($sql);
	while ($obj = $db->fetch_object($resql)) {
		print '<tr class="oddeven">';
		print '<td>'.$obj->login.'</td>';
		print '<td>'.$obj->module.'</td>';
		print '<td>'.$obj->driver.'</td>';
		print '<td>'.$obj->printer_name.'</td>';
		print '<td>'.$obj->printer_location.'</td>';
		print '<td>'.$obj->printer_id.'</td>';
		print '<td>'.$obj->copy.'</td>';
		print '<td class="center">'.img_picto($langs->trans("Delete"), 'delete').'</td>';
		print "</tr>\n";
	}
	print '</table>';
	print '</div>';
}

print dol_get_fiche_end();

// End of page
llxFooter();
$db->close();
