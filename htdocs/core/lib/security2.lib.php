<?php
/* Copyright (C) 2008-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2017  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2024-2026	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024-2026  Frédéric France			<frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/security2.lib.php
 *  \ingroup    core
 *  \brief		Set of function used for dolibarr security (not common functions).
 *  			Warning, this file must not depends on other library files, except function.lib.php
 *  			because it is used at low code level.
 */


/**
 *  Return user/group account of web server
 *
 *  @param	string	$mode       'user' or 'group'
 *  @return string				Return user or group of web server
 */
function dol_getwebuser($mode)
{
	$t = '?';
	if ($mode == 'user') {
		$t = getenv('APACHE_RUN_USER'); // $_ENV['APACHE_RUN_USER'] is empty
	}
	if ($mode == 'group') {
		$t = getenv('APACHE_RUN_GROUP');
	}
	return $t;
}

/**
 *  Return a login if login/pass was successful
 *
 *	@param	string		$usertotest		Login value to test
 *	@param	string		$passwordtotest	Password value to test
 *	@param	int|string	$entitytotest	Instance of data we must check
 *	@param	string[]	$authmode		Array list of selected authentication mode array('http', 'dolibarr', 'xxx'...)
 *	@param	'api'|'dav'|'ws'|''	$context	Context checkLoginPassEntity was created for ('api', 'dav', 'ws', '')
 *  @return	string						Login or '' or '--bad-login-validity--'
 */
function checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $authmode, $context = '')
{
	global $conf, $langs;

	// Check parameters
	if ($entitytotest == '') {
		$entitytotest = 1;
	}

	dol_syslog("checkLoginPassEntity usertotest=".$usertotest." entitytotest=".$entitytotest." authmode=".implode(',', $authmode));
	$login = '';

	// Validation of login/pass/entity with standard modules
	if (empty($login)) {
		$test = true;
		foreach ($authmode as $mode) {
			if ($test && $mode && !$login) {
				// Validation of login/pass/entity for mode $mode
				$mode = trim($mode);
				$authfile = 'functions_'.$mode.'.php';
				$fullauthfile = '';

				$dirlogin = array_merge(array("/core/login"), (array) $conf->modules_parts['login']);
				foreach ($dirlogin as $reldir) {
					$dir = dol_buildpath($reldir, 0);
					$newdir = dol_osencode($dir);

					// Check if file found (do not use dol_is_file to avoid loading files.lib.php)
					$tmpnewauthfile = $newdir.(preg_match('/\/$/', $newdir) ? '' : '/').$authfile;
					if (is_file($tmpnewauthfile)) {
						$fullauthfile = $tmpnewauthfile;
					}
				}

				$result = false;
				if ($fullauthfile) {
					$result = include_once $fullauthfile;
				}
				if ($fullauthfile && $result) {
					// Call function to check user/password
					$function = 'check_user_password_'.$mode;
					$login = call_user_func($function, $usertotest, $passwordtotest, $entitytotest, $context);
					if ($login && $login != '--bad-login-validity--') {
						// Login is successful with this method
						$test = false; // To stop once at first login success
						$conf->authmode = $mode; // This properties is defined only when logged to say what mode was successfully used
						/*$dol_tz = GETPOST('tz');
						$dol_dst = GETPOST('dst');
						$dol_screenwidth = GETPOST('screenwidth');
						$dol_screenheight = GETPOST('screenheight');*/
					}
				} else {
					dol_syslog("Authentication KO - failed to load file '".$authfile."'", LOG_ERR);
					sleep(1);
					// Load translation files required by the page
					$langs->loadLangs(array('other', 'main', 'errors'));

					$_SESSION["dol_loginmesg"] = (empty($_SESSION["dol_loginmesg"]) ? '' : $_SESSION["dol_loginmesg"].', ').$langs->transnoentitiesnoconv("ErrorFailedToLoadLoginFileForMode", $mode);
				}
			}
		}
	}

	return $login;
}


if (!function_exists('dol_loginfunction')) {
	/**
	 * Show Dolibarr default login page.
	 * Part of this code is also duplicated into main.inc.php::top_htmlhead
	 *
	 * @param       Translate   $langs      Lang object (must be initialized by a new).
	 * @param       Conf        $conf       Conf object
	 * @param       Societe     $mysoc      Company object
	 * @return      void
	 */
	function dol_loginfunction($langs, $conf, $mysoc)
	{
		global $dolibarr_main_demo, $dolibarr_main_force_https;
		global $db, $hookmanager;

		$langs->loadLangs(array("main", "other", "help", "admin"));

		// Instantiate hooks of thirdparty module only if not already define
		$hookmanager->initHooks(array('mainloginpage'));

		$main_authentication = $conf->file->main_authentication;

		$session_name = session_name(); // Get current session name

		$dol_url_root = DOL_URL_ROOT;

		// Title
		$appli = constant('DOL_APPLICATION_TITLE');
		$title = $appli.(getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '' : ' '.constant('DOL_VERSION'));

		$customapplication = getDolGlobalString('MAIN_APPLICATION_TITLE');
		if ($customapplication) {
			if (preg_match('/^\+/', $customapplication)) {
				$title .= $customapplication;
			} else {
				$title = $customapplication;
			}
		}
		$titletruedolibarrversion = constant('DOL_VERSION'); // $title used by login template after the @ to inform of true Dolibarr version

		// Note: $conf->css looks like '/theme/eldy/style.css.php'
		/*
		$conf->css = "/theme/".(GETPOST('theme','aZ09')?GETPOST('theme','aZ09'):$conf->theme)."/style.css.php";
		$themepath=dol_buildpath($conf->css,1);
		if (!empty($conf->modules_parts['theme']))		// Using this feature slow down application
		{
			foreach($conf->modules_parts['theme'] as $reldir)
			{
				if (file_exists(dol_buildpath($reldir.$conf->css, 0)))
				{
					$themepath=dol_buildpath($reldir.$conf->css, 1);
					break;
				}
			}
		}
		$conf_css = $themepath."?lang=".$langs->defaultlang;
		*/

		// Select templates dir
		$template_dir = '';
		if (!empty($conf->modules_parts['tpl'])) {	// Using this feature slow down application
			$dirtpls = array_merge($conf->modules_parts['tpl'], array('/core/tpl/'));
			foreach ($dirtpls as $reldir) {
				$tmp = dol_buildpath($reldir.'login.tpl.php');
				if (file_exists($tmp)) {
					$template_dir = preg_replace('/login\.tpl\.php$/', '', $tmp);
					break;
				}
			}
		} else {
			$template_dir = DOL_DOCUMENT_ROOT."/core/tpl/";
		}

		// Set cookie for timeout management. We set it as a cookie so we will be able to use it to set timeout on next page before the session start
		// and the conf file is loaded.
		$prefix = dol_getprefix('');
		$sessiontimeout = 'DOLSESSTIMEOUT_'.$prefix;

		if (getDolGlobalString('MAIN_SESSION_TIMEOUT')) {
			dolSetCookie($sessiontimeout, getDolGlobalString('MAIN_SESSION_TIMEOUT'), 0);
		}

		if (GETPOST('urlfrom', 'alpha')) {
			$_SESSION["urlfrom"] = GETPOST('urlfrom', 'alpha');
		} else {
			unset($_SESSION["urlfrom"]);
		}

		if (!GETPOST("username", 'alpha')) {
			$focus_element = 'username';
		} else {
			$focus_element = 'password';
		}

		$demologin = '';
		$demopassword = '';
		if (!empty($dolibarr_main_demo)) {
			$tab = explode(',', $dolibarr_main_demo);
			$demologin = $tab[0];
			$demopassword = $tab[1];
		}

		// Execute hook getLoginPageOptions (for table)
		$parameters = array('entity' => GETPOSTINT('entity'), 'switchentity' => GETPOSTINT('switchentity'));
		$reshook = $hookmanager->executeHooks('getLoginPageOptions', $parameters); // Note that $action and $object may have been modified by some hooks.
		$morelogincontent = $hookmanager->resPrint;

		// Execute hook getLoginPageExtraOptions (eg for js)
		$parameters = array('entity' => GETPOSTINT('entity'), 'switchentity' => GETPOSTINT('switchentity'));
		$reshook = $hookmanager->executeHooks('getLoginPageExtraOptions', $parameters); // Note that $action and $object may have been modified by some hooks.
		$moreloginextracontent = $hookmanager->resPrint;

		//Redirect after connection
		$parameters = array('entity' => GETPOSTINT('entity'), 'switchentity' => GETPOSTINT('switchentity'));
		$reshook = $hookmanager->executeHooks('redirectAfterConnection', $parameters); // Note that $action and $object may have been modified by some hooks.
		$php_self = $hookmanager->resPrint;

		// Login
		$login = (!empty($hookmanager->resArray['username']) ? $hookmanager->resArray['username'] : (GETPOST("username", "alpha") ? GETPOST("username", "alpha") : $demologin));
		$password = $demopassword;

		// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
		$width = 0;
		$urllogo = DOL_URL_ROOT.'/theme/common/login_logo.png';

		if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_small);
		} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/'.$mysoc->logo);
			$width = 128;
		} elseif (!empty($mysoc->logo_squarred_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_squarred_small)) {
			$urllogo = DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=mycompany&amp;file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_small);
		} elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.svg')) {
			$urllogo = DOL_URL_ROOT.'/theme/dolibarr_logo.svg';
		}

		// Security graphical code
		$captcha = '';
		if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA')) {
			$captcha = getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_HANDLER', 'standard');
		}

		// Extra link
		$forgetpasslink = 0;
		$helpcenterlink = 0;
		if (!getDolGlobalString('MAIN_SECURITY_DISABLEFORGETPASSLINK') || getDolGlobalString('MAIN_HELPCENTER_LINKTOUSE')) {
			if (!getDolGlobalString('MAIN_SECURITY_DISABLEFORGETPASSLINK')) {
				$forgetpasslink = 1;
			}

			if (getDolGlobalString('MAIN_HELPCENTER_LINKTOUSE')) {
				$helpcenterlink = 1;
			}
		}

		// Home message
		$main_home = '';
		if (getDolGlobalString('MAIN_HOME')) {
			$substitutionarray = getCommonSubstitutionArray($langs);
			complete_substitutions_array($substitutionarray, $langs);
			$texttoshow = make_substitutions(getDolGlobalString('MAIN_HOME'), $substitutionarray, $langs);

			$main_home = dol_htmlcleanlastbr($texttoshow);
		}

		// Google AD
		$main_google_ad_client = ((getDolGlobalString('MAIN_GOOGLE_AD_CLIENT') && getDolGlobalString('MAIN_GOOGLE_AD_SLOT')) ? 1 : 0);

		// Set jquery theme
		$dol_loginmesg = (!empty($_SESSION["dol_loginmesg"]) ? $_SESSION["dol_loginmesg"] : '');

		$favicon = DOL_URL_ROOT.'/theme/dolibarr_256x256_color.png';
		if (!empty($mysoc->logo_squarred_mini)) {
			$favicon = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
		}
		if (getDolGlobalString('MAIN_FAVICON_URL')) {
			$favicon = getDolGlobalString('MAIN_FAVICON_URL');
		}

		$jquerytheme = 'base';
		if (getDolGlobalString('MAIN_USE_JQUERY_THEME')) {
			$jquerytheme = getDolGlobalString('MAIN_USE_JQUERY_THEME');
		}

		// Set dol_hide_topmenu, dol_hide_leftmenu, dol_optimize_smallscreen, dol_no_mouse_hover
		$dol_hide_topmenu = GETPOSTINT('dol_hide_topmenu');
		$dol_hide_leftmenu = GETPOSTINT('dol_hide_leftmenu');
		$dol_optimize_smallscreen = GETPOSTINT('dol_optimize_smallscreen');
		$dol_no_mouse_hover = GETPOSTINT('dol_no_mouse_hover');
		$dol_use_jmobile = GETPOSTINT('dol_use_jmobile');

		// Include login page template
		include $template_dir.'login.tpl.php';

		// Global html output events ($mesgs, $errors, $warnings)
		dol_htmloutput_events(0);

		$_SESSION["dol_loginmesg"] = '';
	}
}

/**
 *  Initialise the salt for the crypt function.
 *
 *  @param		int		$type		2 =>Return a salt for DES encryption
 *									12=>Return a salt for MD5 encryption
 *									Undefined=>Return a salt for default encryption
 *	@return		string				Salt string
 */
function makesalt($type = CRYPT_SALT_LENGTH)
{
	dol_syslog("makesalt type=".$type);
	switch ($type) {
		case 12:	// 8 + 4
			$saltlen = 8;
			$saltprefix = '$1$';
			$saltsuffix = '$';
			break;
		case 8:		// 8 (For compatibility, do not use this)
			$saltlen = 8;
			$saltprefix = '$1$';
			$saltsuffix = '$';
			break;
		case 2:		// 2
		default: 	// by default, fall back on Standard DES (should work everywhere)
			$saltlen = 2;
			$saltprefix = '';
			$saltsuffix = '';
			break;
	}
	$salt = '';
	while (dol_strlen($salt) < $saltlen) {
		$salt .= chr(mt_rand(64, 126));
	}

	$result = $saltprefix.$salt.$saltsuffix;
	dol_syslog("makesalt return=".$result);
	return $result;
}

/**
 *  Encode or decode database password in config file
 *
 *  @param   	int		$level   	Encode level: 0 no encoding, 1 encoding
 *	@return		int					Return integer <0 if KO, >0 if OK
 */
function encodedecode_dbpassconf($level = 0)
{
	dol_syslog("encodedecode_dbpassconf level=".$level, LOG_DEBUG);
	$config = '';
	$passwd = '';
	$passwd_crypted = '';

	if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php', 'r')) {
		while (!feof($fp)) {
			$buffer = fgets($fp, 4096);

			$lineofpass = 0;

			$reg = array();
			$mode = '';
			if (preg_match('/^[^#]*dolibarr_main_db_encrypted_pass[\s]*=[\s]*(.*)/i', $buffer, $reg)) {	// Old way to save encrypted value
				$val = trim($reg[1]); // This also remove CR/LF
				$val = preg_replace('/^["\']/', '', $val);
				$val = preg_replace('/["\'][\s;]*$/', '', $val);
				if (!empty($val)) {
					$passwd_crypted = $val;
					// method dol_encode/dol_decode
					$val = dol_decode($val);
					//$val = dolEncrypt($val);
					$passwd = $val;
					$lineofpass = 1;
				}
			} elseif (preg_match('/^[^#]*dolibarr_main_db_pass[\s]*=[\s]*(.*)/i', $buffer, $reg)) {
				$val = trim($reg[1]); // This also remove CR/LF
				$val = preg_replace('/^["\']/', '', $val);
				$val = preg_replace('/["\'][\s;]*$/', '', $val);
				if (preg_match('/crypted:/i', $buffer)) {
					// method dol_encode/dol_decode
					$mode = 'crypted:';
					$val = preg_replace('/crypted:/i', '', $val);
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
				} elseif (preg_match('/^dolcrypt:([^:]+):(.*)$/i', $val, $reg)) {
					// method dolEncrypt/dolDecrypt
					$mode = 'dolcrypt:';
					//$val = preg_replace('/dolcrypt:/i', '', $val);
					$passwd_crypted = $reg[1].':'.$reg[2];
					$val = dolDecrypt($val);
					$passwd = $val;
				} else {
					$passwd = $val;
					/* old method
					$mode = 'crypted:';
					$val = dol_encode($val);
					*/
					$mode = 'dolcrypt:';
					$passwd_crypted = preg_replace('/^dolcrypt:/', '', dolEncrypt($val));
				}
				$lineofpass = 1;
			}

			// Output line
			if ($lineofpass) {
				// Add value at end of file
				if ($level == 0) {
					$config .= '$dolibarr_main_db_pass=\''.$passwd.'\';'."\n";
				}
				if ($level == 1) {
					$config .= '$dolibarr_main_db_pass=\''.$mode.$passwd_crypted.'\';'."\n";
				}

				//print 'passwd = '.$passwd.' - passwd_crypted = '.$passwd_crypted;
				//exit;
			} else {
				$config .= $buffer;
			}
		}
		fclose($fp);

		// Write new conf file
		$file = DOL_DOCUMENT_ROOT.'/conf/conf.php';
		if ($fp = @fopen($file, 'w')) {
			fwrite($fp, $config);
			fflush($fp);
			fclose($fp);
			clearstatcache();

			// It's config file, so we set read permission for creator only.
			// Should set permission to web user and groups for users used by batch
			//dolChmod($file, '0600');

			return 1;
		} else {
			dol_syslog("encodedecode_dbpassconf Failed to open conf.php file for writing", LOG_WARNING);
			return -1;
		}
	} else {
		dol_syslog("encodedecode_dbpassconf Failed to read conf.php", LOG_ERR);
		return -2;
	}
}

/**
 * Return if the 'none' password generation model (the one that applies no rule at all) is
 * forbidden on this installation. It is locked by the conf.php variable
 * $dolibarr_main_restrict_password_generation_none (exposed as
 * $conf->file->restrict_password_generation_none), which can only be changed by editing the
 * config file on the server.
 * When forbidden, the model is hidden from the admin screen, cannot be selected, and any value
 * already set to 'none' falls back to the 'standard' model at generation/validation time.
 *
 * @return	int		1 if the 'none' model must not be used, 0 otherwise
 */
function isPasswordGenerationNoneForbidden()
{
	global $conf;

	return empty($conf->file->restrict_password_generation_none) ? 0 : 1;
}

/**
 * Return the lowest value allowed for the minimum length (first field of USER_PASSWORD_PATTERN) of
 * the 'Perso' password generation model. When the 'none' model is forbidden on this installation
 * (see isPasswordGenerationNoneForbidden()), the Perso model must not be tuned down to a weak
 * value either, so a floor of 10 characters is enforced in the admin screen, when saving the
 * pattern, and at generation/validation time.
 *
 * @return	int		Minimum allowed value for the Perso model minimum length
 */
function getPasswordPatternMinLength()
{
	return isPasswordGenerationNoneForbidden() ? 10 : 1;
}

/**
 * Return a generated password using default module
 *
 * @param		bool			$generic				true=Create a generic key (32 chars/numbers), false=Create a password using the configured password generation module.
 * @param		?array<string>	$replaceambiguouschars	Discard ambiguous characters. For example: array('I').
 * @param       int        		$length                	Length of random string (Used only if $generic is true)
 * @return		string		    						New value for password
 * @see dol_hash(), dolJSToSetRandomPassword()
 * @phan-suppress DolibarrForbiddenFunctionPlugin
 */
function getRandomPassword($generic = false, $replaceambiguouschars = null, $length = 32)
{
	global $db, $conf, $langs, $user;

	$generated_password = '';

	if ($generic) {
		$lowercase = "qwertyuiopasdfghjklzxcvbnm";
		$uppercase = "ASDFGHJKLZXCVBNMQWERTYUIOP";
		$numbers = "1234567890";
		$randomCode = "";
		$nbofchar = round($length / 3);
		$nbofcharlast = ($length - 2 * $nbofchar);
		//var_dump($nbofchar.'-'.$nbofcharlast);
		if (function_exists('random_int')) {	// Cryptographic random
			$max = strlen($lowercase) - 1;
			for ($x = 0; $x < $nbofchar; $x++) {
				$tmp = random_int(0, $max);
				$randomCode .= $lowercase[$tmp];
			}
			$max = strlen($uppercase) - 1;
			for ($x = 0; $x < $nbofchar; $x++) {
				$tmp = random_int(0, $max);
				$randomCode .= $uppercase[$tmp];
			}
			$max = strlen($numbers) - 1;
			for ($x = 0; $x < $nbofcharlast; $x++) {
				$tmp = random_int(0, $max);
				$randomCode .= $numbers[$tmp];
			}
		} else {
			// Old platform, non cryptographic random
			$max = strlen($lowercase) - 1;
			for ($x = 0; $x < $nbofchar; $x++) {
				$tmp = mt_rand(0, $max);
				$randomCode .= $lowercase[$tmp];
			}
			$max = strlen($uppercase) - 1;
			for ($x = 0; $x < $nbofchar; $x++) {
				$tmp = mt_rand(0, $max);
				$randomCode .= $uppercase[$tmp];
			}
			$max = strlen($numbers) - 1;
			for ($x = 0; $x < $nbofcharlast; $x++) {
				$tmp = mt_rand(0, $max);
				$randomCode .= $numbers[$tmp];
			}
		}

		// Use the Fisher-Yate to shake (this replace str_shuffle)
		$passwordArray = str_split($randomCode);
		for ($i = count($passwordArray) - 1; $i > 0; $i--) {
			$j = random_int(0, $i);
			$tmp = $passwordArray[$i];
			$passwordArray[$i] = $passwordArray[$j];
			$passwordArray[$j] = $tmp;
		}
		$generated_password = implode('', $passwordArray);
	} elseif (getDolGlobalString('USER_PASSWORD_GENERATED')) {
		require_once DOL_DOCUMENT_ROOT."/core/modules/security/generate/modules_genpassword.php";
		$genhandler = ModeleGenPassword::loadAndInstantiate(getDolGlobalString('USER_PASSWORD_GENERATED'), $db, $conf, $langs, $user);
		if (!$genhandler) {
			// Configured generator class could not be resolved (e.g. the module that provided it
			// was disabled/removed after being selected) — fall back to the always-available
			// 'standard' generator rather than silently generating and persisting an empty password.
			dol_syslog("getRandomPassword: generator class for USER_PASSWORD_GENERATED='".getDolGlobalString('USER_PASSWORD_GENERATED')."' not found, falling back to standard", LOG_WARNING);
			$genhandler = ModeleGenPassword::loadAndInstantiate('standard', $db, $conf, $langs, $user);
		}
		if ($genhandler) {
			'@phan-var-force ModeleGenPassword $genhandler';
			$generated_password = $genhandler->getNewGeneratedPassword();
			unset($genhandler);
		}
	}

	// Do we have to discard some alphabetic characters ? (usually $replaceambiguouschars is empty)
	if (is_array($replaceambiguouschars) && count($replaceambiguouschars) > 0) {
		$numbers = "ABCDEF";
		$max = strlen($numbers) - 1;
		if (function_exists('random_int')) {	// Cryptographic random
			$tmp = random_int(0, $max);
			$generated_password = str_replace($replaceambiguouschars, $numbers[$tmp], $generated_password);
		} else {
			$tmp = mt_rand(0, $max);
			$generated_password = str_replace($replaceambiguouschars, $numbers[$tmp], $generated_password);
		}
	}

	return $generated_password;
}

/**
 * Output javascript to autoset a generated password using default module into a HTML element.
 *
 * @param		string 		$htmlname			HTML name of element to insert key into
 * @param		string		$htmlnameofbutton	HTML name of button
 * @param		int			$generic			1=Return a generic pass, 0=Return a pass following setup rules
 * @return		string		    				HTML javascript code to set a password
 * @see getRandomPassword()
 */
function dolJSToSetRandomPassword($htmlname, $htmlnameofbutton = 'generate_token', $generic = 1)
{
	global $conf;

	$out = '';

	if (!empty($conf->use_javascript_ajax)) {
		$out .= "\n".'<!-- Js code to suggest a security key -->';
		$out .= '<script nonce="'.getNonce().'" type="text/javascript">';
		$out .= 'jQuery(document).ready(function () {
            jQuery(\'#'.dol_escape_js($htmlnameofbutton).'\').click(function() {
				var currenttoken = jQuery("meta[name=anti-csrf-currenttoken]").attr("content");
				console.log(\'dolJSToSetRandomPassword: We click on the button '.dol_escape_js($htmlnameofbutton).' to suggest a key. anti-csrf-currenttoken is \'+currenttoken+\'. We will fill '.dol_escape_js($htmlname).'\');
				jQuery.get( "'.DOL_URL_ROOT.'/core/ajax/security.php", {
            		action: \'getrandompassword\',
            		generic: '.($generic ? '1' : '0').',
					token: currenttoken
				},
				function(result) {
					if (jQuery(\'input#'.dol_escape_js($htmlname).'\').attr("type") == "password") {
						jQuery(\'input#'.dol_escape_js($htmlname).'\').attr("type", "text");
					}
					jQuery(\'#'.dol_escape_js($htmlname).'\').val(result);
				});
            });
		});'."\n";
		$out .= '</script>';
	}

	return $out;
}

/**
 * Output the eye picto to show/hide a password HTML field.
 *
 * @param		string 		$htmlname			HTML name of element to insert key into
 * @param		string		$htmlnameofinput	HTML id of input field
 * @return		string		    				HTML javascript code to set a password
 */
function showEyeForField($htmlname, $htmlnameofinput)
{
	return '<!-- code to manage the eye hide/show -->
<span id="'.$htmlname.'" tabindex="-1"><span class="fa fa-eye"></span></span>
<script nonce="'.getNonce().'">
	$(document).ready(function () {
		$(\'#'.$htmlname.'\').on(\'click\', function (e) {
			e.preventDefault();
			if (event.detail === 0) return false; // Ignore keyboard "clicks"
			console.log("We click on '.$htmlname.'");
			const $passwordInput = $(\'#'.$htmlnameofinput.'\');

			if ($passwordInput.is(\'[type=password]\')) {
				$passwordInput.attr(\'type\', \'text\');
				jQuery(\'#'.$htmlname.' .fa-eye\').attr(\'class\', \'fa fa-eye-slash\');
			} else {
				$passwordInput.attr(\'type\', \'password\');
				jQuery(\'#'.$htmlname.' .fa-eye-slash\').attr(\'class\', \'fa fa-eye\');
			}

			return false; // This prevents the click from reloading the page
		});
	});
</script>';
}

/**
 *  Generate a new secret for a TOTP enrollment (RFC 6238), in the base32 form expected by the authenticator applications.
 *
 *  @param		int			$nbbytes		Length of the secret in bytes (20 = 160 bits, the recommendation of the RFC 4226)
 *  @return		string						Secret in base32
 *  @see dolTotpCheckCode(), dolTotpGetUri()
 */
function dolTotpNewSecret($nbbytes = 20)
{
	return dolTotpBase32Encode(random_bytes($nbbytes));
}

/**
 *  Encode a binary string into base32 (alphabet of the RFC 4648, no padding).
 *
 *  @param		string		$bin			Binary string
 *  @return		string						Base32 string
 */
function dolTotpBase32Encode($bin)
{
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$bits = '';
	$out = '';
	$nboctets = strlen($bin);	// Byte length of a binary string, dol_strlen() would count UTF-8 chars
	for ($i = 0; $i < $nboctets; $i++) {
		$bits .= str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
	}
	foreach (str_split($bits, 5) as $chunk) {
		$out .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
	}
	return $out;
}

/**
 *  Decode a base32 string (alphabet of the RFC 4648). The chars out of the alphabet, like the spaces of a key typed by hand, are ignored.
 *
 *  @param		string		$base32			Base32 string
 *  @return		string						Binary string
 */
function dolTotpBase32Decode($base32)
{
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
	$clean = preg_replace('/[^A-Z2-7]/', '', dol_strtoupper($base32));
	$bits = '';
	$out = '';
	$nbchars = dol_strlen($clean);
	for ($i = 0; $i < $nbchars; $i++) {
		$bits .= str_pad(decbin((int) strpos($alphabet, $clean[$i])), 5, '0', STR_PAD_LEFT);
	}
	foreach (str_split($bits, 8) as $chunk) {
		if (dol_strlen($chunk) == 8) {
			$out .= chr(bindec($chunk));
		}
	}
	return $out;
}

/**
 *  Compute the TOTP code (RFC 6238) of a secret for one time step.
 *
 *  @param		string		$base32secret	Secret in base32
 *  @param		int			$timestep		Time step, that is the timestamp divided by the period
 *  @param		int			$digits			Number of digits of the code
 *  @param		string		$algo			Hash algorithm ('sha1', 'sha256' or 'sha512')
 *  @return		string						Code, left padded with zeros
 */
function dolTotpGetCode($base32secret, $timestep, $digits = 6, $algo = 'sha1')
{
	$hash = hash_hmac($algo, pack('J', $timestep), dolTotpBase32Decode($base32secret), true);
	$offset = ord($hash[strlen($hash) - 1]) & 0x0F;		// Dynamic truncation of the RFC 4226, on the bytes of the hash
	$value = unpack('N', substr($hash, $offset, 4))[1] & 0x7FFFFFFF;
	return str_pad((string) ($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
}

/**
 *  Check a code sent by a user against a secret, accepting a drift of some periods to tolerate a clock offset.
 *
 *  @param		string		$base32secret	Secret in base32
 *  @param		string		$code			Code sent by the user
 *  @param		int			$timestamp		Timestamp to check at (0 = now)
 *  @param		int			$period			Duration of a time step, in seconds
 *  @param		int			$digits			Number of digits of the code
 *  @param		int			$drift			Number of periods accepted before and after the current one
 *  @return		int							Accepted time step, to be stored to refuse a replay of the same code, or 0 if no step matches
 *  @see dolTotpNewSecret()
 */
function dolTotpCheckCode($base32secret, $code, $timestamp = 0, $period = 30, $digits = 6, $drift = 1)
{
	if (!is_string($code) && !is_int($code)) {		// An array posted as totpcode[] must be refused, not cast
		return 0;
	}
	$code = preg_replace('/[^0-9]/', '', (string) $code);
	if (dol_strlen($code) != $digits || empty($base32secret)) {
		return 0;
	}
	if (empty($timestamp)) {
		$timestamp = dol_now();
	}
	$currentstep = (int) floor($timestamp / $period);
	for ($i = -$drift; $i <= $drift; $i++) {
		if (hash_equals(dolTotpGetCode($base32secret, $currentstep + $i, $digits), $code)) {
			return $currentstep + $i;
		}
	}
	return 0;
}

/**
 *  Build the otpauth:// URI of an enrollment, to be shown as a QR code to the user.
 *
 *  @param		string		$login			Login of the user, shown as the account name in the application
 *  @param		string		$base32secret	Secret in base32
 *  @param		string		$issuer			Name of the issuer shown in the application ('' = name of the company)
 *  @param		int			$digits			Number of digits of the code
 *  @param		int			$period			Duration of a time step, in seconds
 *  @return		string						URI otpauth://totp/...
 */
function dolTotpGetUri($login, $base32secret, $issuer = '', $digits = 6, $period = 30)
{
	global $mysoc;

	if (empty($issuer)) {
		$issuer = (empty($mysoc->name) ? 'Dolibarr' : $mysoc->name);
	}
	$label = rawurlencode($issuer).':'.rawurlencode($login);
	$params = 'secret='.rawurlencode($base32secret).'&issuer='.rawurlencode($issuer).'&algorithm=SHA1&digits='.((int) $digits).'&period='.((int) $period);

	return 'otpauth://totp/'.$label.'?'.$params;
}

/**
 *  Load the TOTP enrollment of a user. The row is written into llx_oauth_token only once the enrollment has been confirmed by a valid code, so its presence means the 2FA is active for this user.
 *
 *  @param		DoliDB		$db				Database handler
 *  @param		int			$fk_user		Id of the user
 *  @return		array{rowid:int,secret:string,laststep:int,datec:int|string,lastaccess:int|string}|int|null		Enrollment, null if the user has no 2FA, -1 if the query failed or the row can't be read: a caller must then refuse the login, not treat it as no 2FA
 *  @see dolTotpSaveEnrollment(), dolTotpDeleteEnrollment()
 */
function dolTotpGetEnrollment($db, $fk_user)
{
	$sql = "SELECT rowid, tokenstring, state, datec, lastaccess FROM ".$db->prefix()."oauth_token";
	$sql .= " WHERE service = 'dolibarr_totp' AND fk_user = ".((int) $fk_user);

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("dolTotpGetEnrollment ".$db->lasterror(), LOG_ERR);
		return -1;
	}
	$num = $db->num_rows($resql);
	$obj = ($num > 0 ? $db->fetch_object($resql) : null);
	$db->free($resql);
	if ($num == 0) {
		return null;
	}
	if (empty($obj)) {	// A row exists but can't be fetched: not the same as no row
		dol_syslog("dolTotpGetEnrollment: row of user ".((int) $fk_user)." can't be fetched", LOG_ERR);
		return -1;
	}

	$data = json_decode((string) $obj->tokenstring, true);
	if (!is_array($data) || empty($data['secret'])) {
		dol_syslog("dolTotpGetEnrollment: unreadable tokenstring for user ".((int) $fk_user), LOG_ERR);
		return -1;
	}

	return array('rowid' => (int) $obj->rowid, 'secret' => dolDecrypt($data['secret']), 'laststep' => (int) $obj->state, 'datec' => $db->jdate($obj->datec), 'lastaccess' => $db->jdate($obj->lastaccess));
}

/**
 *  Save a TOTP enrollment confirmed by a valid code. The secret is encrypted at rest and written only once, the accepted time step goes into the 'state' field.
 *
 *  @param		DoliDB		$db				Database handler
 *  @param		int			$fk_user		Id of the user
 *  @param		string		$secret			Secret in base32
 *  @param		int			$laststep		Time step of the code that confirmed the enrollment
 *  @param		int			$entity			Entity of the user
 *  @return		int							1 if OK, -1 if KO, -2 if the secret can't be encrypted (no instance key or no openssl)
 */
function dolTotpSaveEnrollment($db, $fk_user, $secret, $laststep, $entity = 1)
{
	$encryptedsecret = dolEncrypt($secret);
	if (strpos($encryptedsecret, 'dolcrypt:') !== 0) {	// dolEncrypt() returns the clear value when there is no key or no openssl: we refuse to store a secret in clear
		dol_syslog("dolTotpSaveEnrollment: the instance has no encryption key or no openssl, the secret would be stored in clear", LOG_ERR);
		return -2;
	}
	$tokenstring = json_encode(array('secret' => $encryptedsecret));
	$now = dol_now();

	$sql = "INSERT INTO ".$db->prefix()."oauth_token(service, tokenstring, state, fk_user, entity, datec, lastaccess)";
	$sql .= " VALUES ('dolibarr_totp', '".$db->escape($tokenstring)."', '".$db->escape(dolTotpFormatStep($laststep))."', ".((int) $fk_user).", ".((int) $entity).", '".$db->idate($now)."', '".$db->idate($now)."')";

	if (!$db->query($sql)) {
		dol_syslog("dolTotpSaveEnrollment ".$db->lasterror(), LOG_ERR);
		return -1;
	}

	return 1;
}

/**
 *  Store the time step of the last code accepted, so the same code can't be played twice. The update is conditional: when two
 *  requests carry the same code at the same time, only the first one gets its row updated and the second one is refused.
 *
 *  @param		DoliDB		$db				Database handler
 *  @param		int			$rowid			Id of the row of the enrollment
 *  @param		int			$laststep		Time step just accepted
 *  @return		int							1 if OK, 0 if a code of this step or later was already accepted, -1 if KO
 */
function dolTotpSetLastStep($db, $rowid, $laststep)
{
	$newstate = dolTotpFormatStep($laststep);

	$sql = "UPDATE ".$db->prefix()."oauth_token SET state = '".$db->escape($newstate)."', lastaccess = '".$db->idate(dol_now())."'";
	$sql .= " WHERE rowid = ".((int) $rowid)." AND (state IS NULL OR state = '' OR state < '".$db->escape($newstate)."')";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("dolTotpSetLastStep ".$db->lasterror(), LOG_ERR);
		return -1;
	}
	if ($db->affected_rows($resql) < 1) {
		return 0;
	}

	return 1;
}

/**
 *  Format a time step for the 'state' field, at a fixed width so the string comparison done in SQL is a numeric one.
 *
 *  @param		int			$timestep		Time step
 *  @return		string						Time step left padded with zeros
 */
function dolTotpFormatStep($timestep)
{
	return str_pad((string) ((int) $timestep), 12, '0', STR_PAD_LEFT);
}

/**
 *  Delete the TOTP enrollment of a user, who then logs in with the password only again.
 *
 *  @param		DoliDB		$db				Database handler
 *  @param		int			$fk_user		Id of the user
 *  @return		int							1 if OK, -1 if KO
 */
function dolTotpDeleteEnrollment($db, $fk_user)
{
	$sql = "DELETE FROM ".$db->prefix()."oauth_token WHERE service = 'dolibarr_totp' AND fk_user = ".((int) $fk_user);

	if (!$db->query($sql)) {
		dol_syslog("dolTotpDeleteEnrollment ".$db->lasterror(), LOG_ERR);
		return -1;
	}

	return 1;
}

/**
 *  Start the second step of a two-factor authentication, just after a password has been validated.
 *  The session is not opened here: we only remember that the first step succeeded, so a code can be asked for in the next request.
 *
 *  @param		DoliDB		$db				Database handler
 *  @param		string		$login			Login validated by the first step
 *  @param		int			$entity			Entity the login was tested on
 *  @return		string						The login if this user has no 2FA, or '' to ask for a code first
 *  @see dolTotpCheckPendingLogin()
 */
function dolTotpStartSecondFactor($db, $login, $entity)
{
	global $conf, $langs;

	$tmpuser = new User($db);
	if ($tmpuser->fetch(0, $login, '', 0, ($entity > 0 ? $entity : -1)) <= 0) {	// Just validated but not loadable: we fail closed rather than skip the second factor
		$langs->loadLangs(array('main', 'errors'));
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("ErrorCantLoadUserFromDolibarrDatabase", $login);
		return '';
	}
	$enrollment = dolTotpGetEnrollment($db, $tmpuser->id);
	if ($enrollment === null) {
		return $login;		// No 2FA for this user, the password is enough
	}
	if (!is_array($enrollment)) {	// An enrollment exists but can't be read: we fail closed, an admin has to remove it from the user card
		$langs->load("users");
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("TOTPUnreadableEnrollment");
		return '';
	}

	if (session_status() === PHP_SESSION_ACTIVE && !session_regenerate_id(true)) {	// The password is verified: a fresh session id, so an id fixed before the login is worth nothing
		dol_syslog("dolTotpStartSecondFactor: session id could not be regenerated", LOG_WARNING);
	}
	// The entity and the authentication mode are the ones the password was checked with: the second step restores them and ignores what is posted then
	$_SESSION['dol_totp_pending'] = array('login' => $login, 'entity' => (int) $entity, 'authmode' => (empty($conf->authmode) ? '' : $conf->authmode), 'tries' => 0, 'expire' => dol_now() + 300);
	dol_syslog("Password accepted for ".$login.", now waiting for the two-factor authentication code", LOG_NOTICE);

	return '';
}

/**
 *  Check the code of the second step of a two-factor authentication. The login is read from the session and never from the request,
 *  so the first step can't be skipped by posting a code alone.
 *
 *  @param		DoliDB		$db				Database handler
 *  @return		string						The login if the code is valid, '' otherwise
 *  @see dolTotpStartSecondFactor()
 */
function dolTotpCheckPendingLogin($db)
{
	global $conf, $langs;

	$langs->load("users");

	$pending = empty($_SESSION['dol_totp_pending']) ? array() : $_SESSION['dol_totp_pending'];
	if (empty($pending['login']) || $pending['expire'] < dol_now()) {
		unset($_SESSION['dol_totp_pending']);
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("TOTPLoginExpired");
		return '';
	}

	// Same rule as the password check of check_user_password_dolibarr(): past the quota of failed authentications for this IP, no code is checked.
	// Without it, a login mode that has no quota of its own, like ldap, would let an attacker who knows the password try codes without limit.
	$nbfailedauth = dolTotpGetNbFailedAuthFromIp($db);
	if ($nbfailedauth < 0 || $nbfailedauth > getDolGlobalInt("MAIN_SECURITY_MAX_NUMBER_FAILED_AUTH", 100)) {	// A quota that can't be counted is treated as exceeded
		unset($_SESSION['dol_totp_pending']);
		dol_syslog("Two-factor authentication code refused for ".$pending['login'].": too many failed authentications from ".getUserRemoteIP(), LOG_WARNING);
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("TOTPBadCode");
		return '';
	}

	$tmpuser = new User($db);
	if ($tmpuser->fetch(0, $pending['login'], '', 0, ($pending['entity'] > 0 ? $pending['entity'] : -1)) <= 0) {
		unset($_SESSION['dol_totp_pending']);
		return '';
	}
	$enrollment = dolTotpGetEnrollment($db, $tmpuser->id);
	if (!is_array($enrollment)) {	// Removed meanwhile, or unreadable: refused either way
		unset($_SESSION['dol_totp_pending']);
		return '';
	}

	$laststep = dolTotpCheckCode($enrollment['secret'], GETPOST('totpcode', 'alphanohtml', 2));	// Read from POST only, so a code never ends up in an access log
	if ($laststep <= 0 || $laststep <= $enrollment['laststep']) {	// A code already accepted once is refused, so a captured code can't be played again
		$pending['tries'] = (empty($pending['tries']) ? 0 : (int) $pending['tries']) + 1;
		if ($pending['tries'] >= 3) {
			unset($_SESSION['dol_totp_pending']);		// Too many wrong codes, the password has to be entered again
		} else {
			$_SESSION['dol_totp_pending'] = $pending;
		}
		dol_syslog("Two-factor authentication code refused for ".$pending['login'], LOG_WARNING);
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("TOTPBadCode");
		return '';
	}

	unset($_SESSION['dol_totp_pending']);
	if (dolTotpSetLastStep($db, $enrollment['rowid'], $laststep) <= 0) {	// Not stored, or already stored by a concurrent request: the code would stay replayable, so we fail closed
		dol_syslog("Two-factor authentication code refused for ".$pending['login'].", its time step could not be recorded", LOG_WARNING);
		$_SESSION["dol_loginmesg"] = $langs->transnoentitiesnoconv("TOTPBadCode");
		return '';
	}
	$conf->authmode = $pending['authmode'];		// Same value checkLoginPassEntity() set on the first step
	if (session_status() === PHP_SESSION_ACTIVE && !session_regenerate_id(true)) {	// Fully authenticated: a fresh session id again
		dol_syslog("dolTotpCheckPendingLogin: session id could not be regenerated", LOG_WARNING);
	}

	return $pending['login'];
}

/**
 *  Count the failed authentications recorded for the IP of the current request over the last 24 hours.
 *  This is the count check_user_password_dolibarr() does before testing a password; it needs MAIN_LOGEVENTS_USER_LOGIN_FAILED to be enabled, like it.
 *
 *  @param		DoliDB		$db				Database handler
 *  @return		int							Number of USER_LOGIN_FAILED events, or -1 if they can't be counted
 */
function dolTotpGetNbFailedAuthFromIp($db)
{
	$sql = "SELECT COUNT(e.rowid) as nbevent";
	$sql .= " FROM ".$db->prefix()."events as e";
	$sql .= " WHERE e.type = 'USER_LOGIN_FAILED'";
	$sql .= " AND e.ip = '".$db->escape(getUserRemoteIP())."'";
	$sql .= " AND e.dateevent > '".$db->idate(dol_now() - 24 * 3600)."'";

	$resql = $db->query($sql);
	if (!$resql) {
		dol_syslog("dolTotpGetNbFailedAuthFromIp ".$db->lasterror(), LOG_ERR);
		return -1;
	}
	$obj = $db->fetch_object($resql);
	$db->free($resql);

	return (empty($obj) ? 0 : (int) $obj->nbevent);
}
