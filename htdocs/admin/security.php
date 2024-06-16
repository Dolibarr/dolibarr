<?php
/* Copyright (C) 2004-2022 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013-2015 Juanjo Menent		<jmenent@2byte.es>
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
 *		\file       htdocs/admin/security.php
 *      \ingroup    setup
 *      \brief      Page of setup of security
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

$action = GETPOST('action', 'aZ09');

// Load translation files required by the page
$langs->loadLangs(array("users", "admin", "other"));

if (!$user->admin) {
	accessforbidden();
}

// Allow/Disallow change to clear passwords once passwords are encrypted
$allow_disable_encryption = false;


/*
 * Actions
 */

if ($action == 'setgeneraterule') {
	if (!dolibarr_set_const($db, 'USER_PASSWORD_GENERATED', GETPOST("value", "alphanohtml"), 'chaine', 0, '', $conf->entity)) {
		dol_print_error($db);
	}
}

if ($action == 'activate_encrypt') {
	$error = 0;

	$db->begin();

	// On old version, a bug created the constant into user entity, so we delete it to be sure such entry won't exists. We want it in entity 0 or nowhere.
	dolibarr_del_const($db, "DATABASE_PWD_ENCRYPTED", $conf->entity);
	// We set entity=0 (all) because DATABASE_PWD_ENCRYPTED is a setup into conf file, so always shared for everybody
	$entityforall = 0;
	dolibarr_set_const($db, "DATABASE_PWD_ENCRYPTED", "1", 'chaine', 0, '', $entityforall);

	$sql = "SELECT u.rowid, u.pass, u.pass_crypted";
	$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
	$sql .= " WHERE u.pass IS NOT NULL AND LENGTH(u.pass) < 32"; // Not a MD5 value

	$resql = $db->query($sql);
	if ($resql) {
		$numrows = $db->num_rows($resql);
		$i = 0;
		while ($i < $numrows) {
			$obj = $db->fetch_object($resql);
			if (dol_hash($obj->pass)) {
				$sql = "UPDATE ".MAIN_DB_PREFIX."user";
				$sql .= " SET pass_crypted = '".dol_hash($obj->pass)."', pass = NULL";
				$sql .= " WHERE rowid=".((int) $obj->rowid);
				//print $sql;

				$resql2 = $db->query($sql);
				if (!$resql2) {
					dol_print_error($db);
					$error++;
					break;
				}

				$i++;
			}
		}
	} else {
		dol_print_error($db);
	}

	//print $error." ".$sql;
	//exit;
	if (!$error) {
		$db->commit();
	} else {
		$db->rollback();
		dol_print_error($db, '');
	}
} elseif ($action == 'disable_encrypt') {
	// By default, $allow_disable_encryption is false we do not allow to disable encryption because passwords can't be decoded once encrypted.
	if ($allow_disable_encryption) {
		dolibarr_del_const($db, "DATABASE_PWD_ENCRYPTED", $conf->entity);
	}
}

if ($action == 'activate_encryptdbpassconf') {
	$result = encodedecode_dbpassconf(1);
	if ($result > 0) {
		sleep(3); // Don't know why but we need to wait file is completely saved before making the reload. Even with flush and clearstatcache, we need to wait.

		// database value not required
		//dolibarr_set_const($db, "MAIN_DATABASE_PWD_CONFIG_ENCRYPTED", "1");
		header("Location: security.php");
		exit;
	} else {
		setEventMessages($langs->trans('InstrucToEncodePass', dol_encode($dolibarr_main_db_pass)), null, 'warnings');
	}
} elseif ($action == 'disable_encryptdbpassconf') {
	$result = encodedecode_dbpassconf(0);
	if ($result > 0) {
		sleep(3); // Don't know why but we need to wait file is completely saved before making the reload. Even with flush and clearstatcache, we need to wait.

		// database value not required
		//dolibarr_del_const($db, "MAIN_DATABASE_PWD_CONFIG_ENCRYPTED",$conf->entity);
		header("Location: security.php");
		exit;
	} else {
		//setEventMessages($langs->trans('InstrucToClearPass', $dolibarr_main_db_pass), null, 'warnings');
		setEventMessages($langs->trans('InstrucToClearPass', $langs->transnoentitiesnoconv("DatabasePassword")), null, 'warnings');
	}
}

if ($action == 'activate_MAIN_SECURITY_DISABLEFORGETPASSLINK') {
	dolibarr_set_const($db, "MAIN_SECURITY_DISABLEFORGETPASSLINK", '1', 'chaine', 0, '', $conf->entity);
} elseif ($action == 'disable_MAIN_SECURITY_DISABLEFORGETPASSLINK') {
	dolibarr_del_const($db, "MAIN_SECURITY_DISABLEFORGETPASSLINK", $conf->entity);
}

if ($action == 'updatepattern') {
	$pattern = GETPOST("pattern", "alpha");
	$explodePattern = explode(';', $pattern);  // List of ints separated with ';' containing counts

	$patternInError = false;
	if ((int) $explodePattern[0] < 1 || (int) $explodePattern[4] < 0) {
		$patternInError = true;
	}

	if ((int) $explodePattern[0] < (int) $explodePattern[1] + (int) $explodePattern[2] + (int) $explodePattern[3]) {
		$patternInError = true;
	}

	if (!$patternInError) {
		dolibarr_set_const($db, "USER_PASSWORD_PATTERN", $pattern, 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
		header("Location: security.php");
		exit;
	}
}



/*
 * View
 */

$form = new Form($db);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Passwords"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-security');

print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("GeneratedPasswordDesc")."</span><br>\n";
print "<br>\n";


$head = security_prepare_head();

print dol_get_fiche_head($head, 'passwords', '', -1);

print '<br>';

// Select manager to generate passwords
print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';
print '<input type="hidden" name="constname" value="USER_PASSWORD_GENERATED">';
print '<input type="hidden" name="consttype" value="yesno">';

// Charge tableau des modules generation
$dir = "../core/modules/security/generate";
clearstatcache();
$handle = opendir($dir);
$i = 1;
$arrayhandler = array();
if (is_resource($handle)) {
	while (($file = readdir($handle)) !== false) {
		if (preg_match('/(modGeneratePass[a-z]+)\.class\.php$/i', $file, $reg)) {
			// Charging the numbering class
			$classname = $reg[1];
			require_once $dir.'/'.$file;

			$obj = new $classname($db, $conf, $langs, $user);
			'@phan-var-force ModeleGenPassword $obj';
			$arrayhandler[$obj->id] = $obj;
			$i++;
		}
	}
	closedir($handle);
}
asort($arrayhandler);

print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("RuleForGeneratedPasswords").'</td>';
print '<td>'.$langs->trans("Example").'</td>';
print '<td class="center">'.$langs->trans("Activated").'</td>';
print '</tr>';

$tabConf = explode(";", getDolGlobalString('USER_PASSWORD_PATTERN'));

foreach ($arrayhandler as $key => $module) {
	// Show modules according to features level
	if (!empty($module->version) && $module->version == 'development' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 2) {
		continue;
	}
	if (!empty($module->version) && $module->version == 'experimental' && getDolGlobalInt('MAIN_FEATURES_LEVEL') < 1) {
		continue;
	}

	if ($module->isEnabled()) {
		print '<tr class="oddeven"><td>';
		print img_picto('', $module->picto, 'class="width25 size15x marginrightonly"').' ';
		print ucfirst($key);
		print "</td><td>\n";
		print $module->getDescription().'<br>';
		print $langs->trans("MinLength").': <span class="opacitymedium">'.$module->length.'</span>';
		print '</td>';

		// Show example of numbering module
		print '<td class="nowraponall">';
		$tmp = $module->getExample();
		if (preg_match('/^Error/', $tmp)) {
			$langs->load("errors");
			print '<div class="error">'.$langs->trans($tmp).'</div>';
		} elseif ($tmp == 'NotConfigured') {
			print '<span class="opacitymedium">'.$langs->trans($tmp).'</span>';
		} else {
			print '<span class="opacitymedium">'.$tmp.'</span>';
		}
		print '</td>'."\n";

		print '<td class="center">';
		if ($conf->global->USER_PASSWORD_GENERATED == $key) {
			//print img_picto('', 'tick');
			print img_picto($langs->trans("Enabled"), 'switch_on');
		} else {
			print '<a href="'.$_SERVER['PHP_SELF'].'?action=setgeneraterule&token='.newToken().'&value='.$key.'">';
			//print $langs->trans("Activate");
			print img_picto($langs->trans("Disabled"), 'switch_off');
			print '</a>';
		}
		print "</td></tr>\n";
	}
}
print '</table>';
print '</div>';

print '</form>';


// Pattern for Password Perso
if (getDolGlobalString('USER_PASSWORD_GENERATED') == "Perso") {
	print '<br>';

	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<td colspan="2"> '.$langs->trans("PasswordPatternDesc").'</td>';
	print '</tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("MinLength")."</td>";
	print '<td><input type="number" class="width50 right" value="'.$tabConf[0].'" id="minlength" min="1"></td>';
	print '</tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("NbMajMin")."</td>";
	print '<td><input type="number" class="width50 right" value="'.$tabConf[1].'" id="NbMajMin" min="0"></td>';
	print '</tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("NbNumMin")."</td>";
	print '<td><input type="number" class="width50 right" value="'.$tabConf[2].'" id="NbNumMin" min="0"></td>';
	print '</tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("NbSpeMin")."</td>";
	print '<td><input type="number" class="width50 right" value="'.$tabConf[3].'" id="NbSpeMin" min="0"></td>';
	print '</tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("NbIteConsecutive")."</td>";
	print '<td><input type="number" class="width50 right" value="'.$tabConf[4].'" id="NbIteConsecutive" min="0"></td>';
	print '</tr>';


	print '<tr class="oddeven">';
	print '<td>'.$langs->trans("NoAmbiCaracAutoGeneration")."</td>";
	print '<td><input type="checkbox" id="NoAmbiCaracAutoGeneration" '.($tabConf[5] ? "checked" : "").' min="0"> <label for="NoAmbiCaracAutoGeneration" id="textcheckbox">'.($tabConf[5] ? $langs->trans("Activated") : $langs->trans("Disabled")).'</label></td>';
	print '</tr>';

	print '</table>';

	print '<div class="center">';
	print '<a class="button button-save" id="linkChangePattern">'.$langs->trans("Save").'</a>';
	print '</div>';

	print '<br><br>';

	print '<script type="text/javascript">';
	print '	function getStringArg(){';
	print '		var pattern = "";';
	print '		pattern += $("#minlenght").val() + ";";';
	print '		pattern += $("#NbMajMin").val() + ";";';
	print '		pattern += $("#NbNumMin").val() + ";";';
	print '		pattern += $("#NbSpeMin").val() + ";";';
	print '		pattern += $("#NbIteConsecutive").val() + ";";';
	print '		pattern += $("#NoAmbiCaracAutoGeneration")[0].checked ? "1" : "0";';
	print '		return pattern;';
	print '	}';

	print '	function valuePossible(){';
	print '		var fields = ["#minlenght", "#NbMajMin", "#NbNumMin", "#NbSpeMin", "#NbIteConsecutive"];';
	print '		for(var i = 0 ; i < fields.length ; i++){';
	print '		    if($(fields[i]).val() < $(fields[i]).attr("min")){';
	print '		        return false;';
	print '		    }';
	print '		}';
	print '		';
	print '		var length = parseInt($("#minlenght").val());';
	print '		var length_mini = parseInt($("#NbMajMin").val()) + parseInt($("#NbNumMin").val()) + parseInt($("#NbSpeMin").val());';
	print '		return length >= length_mini;';
	print '	}';

	print '	function generatelink(){';
	print '		return "security.php?action=updatepattern&token='.newToken().'&pattern="+getStringArg();';
	print '	}';

	print '	function valuePatternChange(){';
	print '     console.log("valuePatternChange");';
	print '		var lang_save = "'.$langs->trans("Save").'";';
	print '		var lang_error = "'.$langs->trans("Error").'";';
	print '		var lang_Disabled = "'.$langs->trans("Disabled").'";';
	print '		var lang_Activated = "'.$langs->trans("Activated").'";';
	print '		$("#textcheckbox").html($("#NoAmbiCaracAutoGeneration")[0].checked ? unescape(lang_Activated) : unescape(lang_Disabled));';
	print '		if(valuePossible()){';
	print '			$("#linkChangePattern").attr("href",generatelink()).text(lang_save);';
	print '		}';
	print '		else{';
	print '			$("#linkChangePattern").attr("href", null).text(lang_error);';
	print '		}';
	print '	}';

	print '	$("#minlenght").change(function(){valuePatternChange();});';
	print '	$("#NbMajMin").change(function(){valuePatternChange();});';
	print '	$("#NbNumMin").change(function(){valuePatternChange();});';
	print '	$("#NbSpeMin").change(function(){valuePatternChange();});';
	print '	$("#NbIteConsecutive").change(function(){valuePatternChange();});';
	print '	$("#NoAmbiCaracAutoGeneration").change(function(){valuePatternChange();});';

	print '</script>';
}


// Crypt passwords in database

print '<br>';
print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="encrypt">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td colspan="3">'.$langs->trans("Parameters").'</td>';
print '<td class="center">'.$langs->trans("Activated").'</td>';
print '<td class="center"></td>';
print '</tr>';

// Disable clear password in database
print '<tr class="oddeven">';
print '<td colspan="3">'.$langs->trans("DoNotStoreClearPassword").'</td>';
print '<td class="center" width="60">';
if (getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {
	print img_picto($langs->trans("Active"), 'tick');
}
print '</td>';
if (!getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {
	print '<td class="center" width="100">';
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=activate_encrypt&token='.newToken().'">'.$langs->trans("Activate").'</a>';
	print "</td>";
} else {
	print '<td class="center" width="100">';
	if ($allow_disable_encryption) {
		//On n'autorise pas l'annulation de l'encryption car les mots de passe ne peuvent pas etre decodes
		//Do not allow "disable encryption" as passwords cannot be decrypted
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=disable_encrypt&token='.newToken().'">'.$langs->trans("Disable").'</a>';
	} else {
		print '<span class="opacitymedium">'.$langs->trans("Always").'</span>';
	}
	print "</td>";
}
print "</td>";
print '</tr>';


// Crypt password into config file conf.php

print '<tr class="oddeven">';
print '<td colspan="3">'.$langs->trans("MainDbPasswordFileConfEncrypted").'</td>';
print '<td align="center" width="60">';
if (preg_match('/crypted:/i', $dolibarr_main_db_pass) || !empty($dolibarr_main_db_encrypted_pass)) {
	print img_picto($langs->trans("Active"), 'tick');
}

print '</td>';

print '<td class="center" width="100">';
if (empty($dolibarr_main_db_pass) && empty($dolibarr_main_db_encrypted_pass)) {
	$langs->load("errors");
	print img_warning($langs->trans("WarningPassIsEmpty"));
} else {
	if (empty($dolibarr_main_db_encrypted_pass)) {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=activate_encryptdbpassconf&token='.newToken().'">'.$langs->trans("Activate").'</a>';
	}
	if (!empty($dolibarr_main_db_encrypted_pass)) {
		print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=disable_encryptdbpassconf&token='.newToken().'">'.$langs->trans("Disable").'</a>';
	}
}
print "</td>";

print "</td>";
print '</tr>';


// Disable link "Forget password" on logon

print '<tr class="oddeven">';
print '<td colspan="3">'.$langs->trans("DisableForgetPasswordLinkOnLogonPage").'</td>';
print '<td class="center" width="60">';
if (getDolGlobalString('MAIN_SECURITY_DISABLEFORGETPASSLINK')) {
	print img_picto($langs->trans("Active"), 'tick');
}
print '</td>';
if (!getDolGlobalString('MAIN_SECURITY_DISABLEFORGETPASSLINK')) {
	print '<td class="center" width="100">';
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=activate_MAIN_SECURITY_DISABLEFORGETPASSLINK&token='.newToken().'">'.$langs->trans("Activate").'</a>';
	print "</td>";
}
if (getDolGlobalString('MAIN_SECURITY_DISABLEFORGETPASSLINK')) {
	print '<td center="center" width="100">';
	print '<a class="reposition" href="'.$_SERVER["PHP_SELF"].'?action=disable_MAIN_SECURITY_DISABLEFORGETPASSLINK&token='.newToken().'">'.$langs->trans("Disable").'</a>';
	print "</td>";
}
print "</td>";
print '</tr>';


print '</table>';

print '</form>';

print '<br>';

if (GETPOSTINT('info') > 0) {
	if (function_exists('password_hash')) {
		print $langs->trans("Note: The function password_hash exists on your PHP")."<br>\n";
	} else {
		print $langs->trans("Note: The function password_hash does not exist on your PHP")."<br>\n";
	}
	print 'MAIN_SECURITY_HASH_ALGO = '.getDolGlobalString('MAIN_SECURITY_HASH_ALGO')."<br>\n";
	print 'MAIN_SECURITY_SALT = '.getDolGlobalString('MAIN_SECURITY_SALT')."<br>\n";
}

print '</div>';

// End of page
llxFooter();
$db->close();
