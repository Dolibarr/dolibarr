<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2017 Regis Houssin        <regis.houssin@inodbox.com>
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
 *  Return a login if login/pass was successfull
 *
 *	@param		string	$usertotest			Login value to test
 *	@param		string	$passwordtotest		Password value to test
 *	@param		string	$entitytotest		Instance of data we must check
 *	@param		array	$authmode			Array list of selected authentication mode array('http', 'dolibarr', 'xxx'...)
 *	@param		string	$context			Context checkLoginPassEntity was created for ('api', 'dav', 'ws', '')
 *  @return		string						Login or ''
 */
function checkLoginPassEntity($usertotest, $passwordtotest, $entitytotest, $authmode, $context = '')
{
	global $conf, $langs;
	//global $dolauthmode;    // To return authentication finally used

	// Check parameters
	if ($entitytotest == '') {
		$entitytotest = 1;
	}

	dol_syslog("checkLoginPassEntity usertotest=".$usertotest." entitytotest=".$entitytotest." authmode=".join(',', $authmode));
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
					if ($login && $login != '--bad-login-validity--') {	// Login is successfull
						$test = false; // To stop once at first login success
						$conf->authmode = $mode; // This properties is defined only when logged to say what mode was successfully used
						$dol_tz = GETPOST('tz');
						$dol_dst = GETPOST('dst');
						$dol_screenwidth = GETPOST('screenwidth');
						$dol_screenheight = GETPOST('screenheight');
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
		$title = $appli.' '.constant('DOL_VERSION');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE)) {
			$title = $conf->global->MAIN_APPLICATION_TITLE;
		}
		$titletruedolibarrversion = constant('DOL_VERSION'); // $title used by login template after the @ to inform of true Dolibarr version

		// Note: $conf->css looks like '/theme/eldy/style.css.php'
		/*
		$conf->css = "/theme/".(GETPOST('theme','aZ09')?GETPOST('theme','aZ09'):$conf->theme)."/style.css.php";
		$themepath=dol_buildpath($conf->css,1);
		if (! empty($conf->modules_parts['theme']))		// Using this feature slow down application
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
		if (!empty($conf->global->MAIN_SESSION_TIMEOUT)) {
			setcookie($sessiontimeout, $conf->global->MAIN_SESSION_TIMEOUT, 0, "/", null, (empty($dolibarr_main_force_https) ? false : true), true);
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
		$parameters = array('entity' => GETPOST('entity', 'int'), 'switchentity' => GETPOST('switchentity', 'int'));
		$reshook = $hookmanager->executeHooks('getLoginPageOptions', $parameters); // Note that $action and $object may have been modified by some hooks.
		$morelogincontent = $hookmanager->resPrint;

		// Execute hook getLoginPageExtraOptions (eg for js)
		$parameters = array('entity' => GETPOST('entity', 'int'), 'switchentity' => GETPOST('switchentity', 'int'));
		$reshook = $hookmanager->executeHooks('getLoginPageExtraOptions', $parameters); // Note that $action and $object may have been modified by some hooks.
		$moreloginextracontent = $hookmanager->resPrint;

		//Redirect after connection
		$parameters = array('entity' => GETPOST('entity', 'int'), 'switchentity' => GETPOST('switchentity', 'int'));
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
		$captcha = 0;
		$captcha_refresh = '';
		if (function_exists("imagecreatefrompng") && !empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA)) {
			$captcha = 1;
			$captcha_refresh = img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"');
		}

		// Extra link
		$forgetpasslink = 0;
		$helpcenterlink = 0;
		if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK) || empty($conf->global->MAIN_HELPCENTER_DISABLELINK)) {
			if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK)) {
				$forgetpasslink = 1;
			}

			if (empty($conf->global->MAIN_HELPCENTER_DISABLELINK)) {
				$helpcenterlink = 1;
			}
		}

		// Home message
		$main_home = '';
		if (!empty($conf->global->MAIN_HOME)) {
			$substitutionarray = getCommonSubstitutionArray($langs);
			complete_substitutions_array($substitutionarray, $langs);
			$texttoshow = make_substitutions($conf->global->MAIN_HOME, $substitutionarray, $langs);

			$main_home = dol_htmlcleanlastbr($texttoshow);
		}

		// Google AD
		$main_google_ad_client = ((!empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && !empty($conf->global->MAIN_GOOGLE_AD_SLOT)) ? 1 : 0);

		// Set jquery theme
		$dol_loginmesg = (!empty($_SESSION["dol_loginmesg"]) ? $_SESSION["dol_loginmesg"] : '');

		$favicon = DOL_URL_ROOT.'/theme/dolibarr_256x256_color.png';
		if (!empty($mysoc->logo_squarred_mini)) {
			$favicon = DOL_URL_ROOT.'/viewimage.php?cache=1&modulepart=mycompany&file='.urlencode('logos/thumbs/'.$mysoc->logo_squarred_mini);
		}
		if (!empty($conf->global->MAIN_FAVICON_URL)) {
			$favicon = $conf->global->MAIN_FAVICON_URL;
		}

		$jquerytheme = 'base';
		if (!empty($conf->global->MAIN_USE_JQUERY_THEME)) {
			$jquerytheme = $conf->global->MAIN_USE_JQUERY_THEME;
		}

		// Set dol_hide_topmenu, dol_hide_leftmenu, dol_optimize_smallscreen, dol_no_mouse_hover
		$dol_hide_topmenu = GETPOST('dol_hide_topmenu', 'int');
		$dol_hide_leftmenu = GETPOST('dol_hide_leftmenu', 'int');
		$dol_optimize_smallscreen = GETPOST('dol_optimize_smallscreen', 'int');
		$dol_no_mouse_hover = GETPOST('dol_no_mouse_hover', 'int');
		$dol_use_jmobile = GETPOST('dol_use_jmobile', 'int');

		// Include login page template
		include $template_dir.'login.tpl.php';

		// Global html output events ($mesgs, $errors, $warnings)
		dol_htmloutput_events(0);

		$_SESSION["dol_loginmesg"] = '';
	}
}

/**
 *  Fonction pour initialiser un salt pour la fonction crypt.
 *
 *  @param		int		$type		2=>renvoi un salt pour cryptage DES
 *									12=>renvoi un salt pour cryptage MD5
 *									non defini=>renvoi un salt pour cryptage par defaut
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
		case 8:		// 8 (Pour compatibilite, ne devrait pas etre utilise)
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
 *	@return		int					<0 if KO, >0 if OK
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

			if (preg_match('/^[^#]*dolibarr_main_db_encrypted_pass[\s]*=[\s]*(.*)/i', $buffer, $reg)) {	// Old way to save crypted value
				$val = trim($reg[1]); // This also remove CR/LF
				$val = preg_replace('/^["\']/', '', $val);
				$val = preg_replace('/["\'][\s;]*$/', '', $val);
				if (!empty($val)) {
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
					$lineofpass = 1;
				}
			} elseif (preg_match('/^[^#]*dolibarr_main_db_pass[\s]*=[\s]*(.*)/i', $buffer, $reg)) {
				$val = trim($reg[1]); // This also remove CR/LF
				$val = preg_replace('/^["\']/', '', $val);
				$val = preg_replace('/["\'][\s;]*$/', '', $val);
				if (preg_match('/crypted:/i', $buffer)) {
					$val = preg_replace('/crypted:/i', '', $val);
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
				} else {
					$passwd = $val;
					$val = dol_encode($val);
					$passwd_crypted = $val;
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
					$config .= '$dolibarr_main_db_pass=\'crypted:'.$passwd_crypted.'\';'."\n";
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
			fputs($fp, $config);
			fflush($fp);
			fclose($fp);
			clearstatcache();

			// It's config file, so we set read permission for creator only.
			// Should set permission to web user and groups for users used by batch
			//@chmod($file, octdec('0600'));

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
 * Return a generated password using default module
 *
 * @param		boolean		$generic				true=Create generic password (32 chars/numbers), false=Use the configured password generation module
 * @param		array		$replaceambiguouschars	Discard ambigous characters. For example array('I').
 * @param       int         $length                 Length of random string (Used only if $generic is true)
 * @return		string		    					New value for password
 * @see dol_hash()
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

			$generated_password = str_shuffle($randomCode);
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

			$generated_password = str_shuffle($randomCode);
		}
	} elseif (!empty($conf->global->USER_PASSWORD_GENERATED)) {
		$nomclass = "modGeneratePass".ucfirst($conf->global->USER_PASSWORD_GENERATED);
		$nomfichier = $nomclass.".class.php";
		//print DOL_DOCUMENT_ROOT."/core/modules/security/generate/".$nomclass;
		require_once DOL_DOCUMENT_ROOT."/core/modules/security/generate/".$nomfichier;
		$genhandler = new $nomclass($db, $conf, $langs, $user);
		$generated_password = $genhandler->getNewGeneratedPassword();
		unset($genhandler);
	}

	// Do we have to discard some alphabetic characters ?
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
