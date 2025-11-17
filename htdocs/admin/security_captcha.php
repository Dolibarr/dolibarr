<?php
/* Copyright (C) 2004-2013  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2013       Juanjo Menent 		    <jmenent@2byte.es>
 * Copyright (C) 2024-2025  Frédéric France         <frederic.france@free.fr>
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
 *	    \file       htdocs/admin/security_captcha.php
 *      \ingroup    core
 *      \brief      Security captcha options setup
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';

// Load translation files required by the page
$langs->loadLangs(array("users", "admin", "other"));

if (!$user->admin) {
	accessforbidden();
}

$action = GETPOST('action', 'aZ09');
$handler = GETPOST('handler', 'aZ09');


/*
 * Actions
 */

if (preg_match('/set_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	$value = (GETPOST($code, 'alpha') ? GETPOST($code, 'alpha') : 1);
	if (dolibarr_set_const($db, $code, $value, 'chaine', 0, '', $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif (preg_match('/del_([a-z0-9_\-]+)/i', $action, $reg)) {
	$code = $reg[1];
	if (dolibarr_del_const($db, $code, $conf->entity) > 0) {
		header("Location: ".$_SERVER["PHP_SELF"]);
		exit;
	} else {
		dol_print_error($db);
	}
} elseif ($action == 'setcaptchahandler') {
	if (!dolibarr_set_const($db, 'MAIN_SECURITY_ENABLECAPTCHA_HANDLER', GETPOST("value", "aZ09"), 'chaine', 0, '', $conf->entity)) {
		dol_print_error($db);
	}
}


/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Miscellaneous"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-security_other');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("CaptchaDesc")."</span><br>\n";
print "<br>\n";

$dirModCaptcha = array_merge(array('/core/modules/security/captcha/'), (isset($conf->modules_parts['captcha']) && is_array($conf->modules_parts['captcha'])) ? $conf->modules_parts['captcha'] : array());

// Load array with all captcha generation modules
$arrayhandler = array();

foreach ($dirModCaptcha as $dirroot) {
	$dir = dol_buildpath($dirroot, 0);

	$handle = @opendir($dir);

	$i = 1;
	if (is_resource($handle)) {
		while (($file = readdir($handle)) !== false) {
			$reg = array();
			if (preg_match('/(modCaptcha[a-z]+)\.class\.php$/i', $file, $reg)) {
				// Charging the numbering class
				$classname = $reg[1];
				require_once $dir.'/'.$file;

				$obj = new $classname($db, $conf, $langs, $user);
				'@phan-var-force ModeleCaptcha $obj';
				/** @var ModeleCaptcha $obj */
				$arrayhandler[$obj->id] = $obj;
				$i++;
			}
		}
		closedir($handle);
	}
}
$arrayhandler = dol_sort_array($arrayhandler, 'position');


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="updateform">';

$head = security_prepare_head();

print dol_get_fiche_head($head, 'captcha', '', -1);

print '<br>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Captcha").'</td>';
print '<td class="right" width="100">'.$langs->trans("Status").'</td>';
print '</tr>';

print '<tr class="oddeven"><td>' . $langs->trans("UseCaptchaCode").' - Login</td><td class="right" width="100">';
if (!empty($conf->use_javascript_ajax)) {
	print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA', array(), null, 0, 0, 1);
} else {
	if (!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
	} else {
		print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
	}
}
print '</td></tr>';

if (isModEnabled('societe')) {
	print '<tr class="oddeven"><td>' . $langs->trans("UseCaptchaCode").' - Thirdparty public contact form</td><td class="right" width="100">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA_THIRDPARTY', array(), null, 0, 0, 1);
	} else {
		if (!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_THIRDPARTY')) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA_THIRDPARTY&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA_THIRDPARTY&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
}
print '</td></tr>';

if (isModEnabled('ticket')) {
	print '<tr class="oddeven"><td>' . $langs->trans("UseCaptchaCode").' - Public ticket creation</td><td class="right" width="100">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA_TICKET', array(), null, 0, 0, 1);
	} else {
		if (!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_TICKET')) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA_TICKET&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA_TICKET&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
}
print '</td></tr>';

if (isModEnabled('member')) {
	print '<tr class="oddeven"><td>' . $langs->trans("UseCaptchaCode").' - Membership public subscription</td><td class="right" width="100">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA_MEMBER', array(), null, 0, 0, 1);
	} else {
		if (!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_MEMBER')) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA_MEMBER&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA_MEMBER&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
}
print '</td></tr>';

if (isModEnabled('don')) {
	print '<tr class="oddeven"><td>' . $langs->trans("UseCaptchaCode").' - Donation public form</td><td class="right" width="100">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA_DONATION', array(), null, 0, 0, 1);
	} else {
		if (!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_DONATION')) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA_DONATION&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA_DONATION&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
}
print '</td></tr>';

if (isModEnabled('recruitment')) {
	print '<tr class="oddeven"><td>' . $langs->trans("UseCaptchaCode").' - Recruitment public form</td><td class="right" width="100">';
	if (!empty($conf->use_javascript_ajax)) {
		print ajax_constantonoff('MAIN_SECURITY_ENABLECAPTCHA_RECRUITMENT', array(), null, 0, 0, 1);
	} else {
		if (!getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_RECRUITMENT')) {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=set_MAIN_SECURITY_ENABLECAPTCHA_RECRUITMENT&token='.newToken().'">'.img_picto($langs->trans("Disabled"), 'off').'</a>';
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=del_MAIN_SECURITY_ENABLECAPTCHA_RECRUITMENT&token='.newToken().'">'.img_picto($langs->trans("Enabled"), 'on').'</a>';
		}
	}
}
print '</td></tr>';

print '</table>';
print '</div>';

// Set if a captcha is used on at least one place
$showavailablecaptcha = 0;
if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
	$showavailablecaptcha = 1;
}
if (isModEnabled('societe') && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_THIRDPARTY')) {
	$showavailablecaptcha = 1;
}
if (isModEnabled('ticket') && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_TICKET')) {
	$showavailablecaptcha = 1;
}
if (isModEnabled('member') && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_MEMBER')) {
	$showavailablecaptcha = 1;
}
if (isModEnabled('don') && getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_DONATION')) {
	$showavailablecaptcha = 1;
}

$selectedcaptcha = getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_HANDLER', 'standard');

if ($showavailablecaptcha) {
	print '<br>';
	print '<br>';
	print '<br>';

	// List of all available captcha
	print '<div class="div-table-responsive">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans("Captcha").'</td>';
	print '<td>'.$langs->trans("Example").'</td>';
	print '<td class="right" width="100">'.$langs->trans("Status").'</td>';
	print '</tr>';

	// Loop on each available captcha
	foreach ($arrayhandler as $key => $module) {
		print '<tr class="oddeven">';
		print '<td style="width: 26px" class="center">';
		print img_picto('', $module->picto, 'class="width25 size15x"');
		print '</td>';
		print '<td>';
		print ucfirst($key);
		print '</td>';
		print '<td>';
		print $module->getDescription().'<br>';
		print '</td>';
		print '<td>';
		print $module->getExample().'<br>';
		print '</td>';
		print '<td class="right" width="100">';

		if (function_exists("imagecreatefrompng")) {
			if ($key != $selectedcaptcha) {
				print '<a href="'.$_SERVER['PHP_SELF'].'?action=setcaptchahandler&token='.newToken().'&value='.$key.'">';
				print img_picto($langs->trans("Disabled"), 'switch_off');
				print '</a>';
			} else {
				print img_picto($langs->trans("Enabled"), 'switch_on');
			}
		} else {
			$desc = $form->textwithpicto('', $langs->transnoentities("EnableGDLibraryDesc"), 1, 'warning');
			print $desc;
		}

		print '</td>';
		print '</tr>';
	}

	print '</table>';
	print '</div>';
	//print $form->buttonsSaveCancel("Modify", '');
}

print dol_get_fiche_end();

print '</form>';

// End of page
llxFooter();
$db->close();
