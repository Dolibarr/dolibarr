<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2011 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file			htdocs/lib/security.lib.php
 *  \brief			Set of function used for dolibarr security
 *  \version		$Id: security.lib.php,v 1.125 2011/07/31 23:25:15 eldy Exp $
 */


/**
 *   Return a login if login/pass was successfull using an external login method
 *   @return	string		Login or ''
 * 	 TODO Provide usertotest, passwordtotest and entitytotest by parameters
 */
function getLoginMethod()
{
	global $conf,$langs;

	$login = '';

	foreach($conf->login_method_modules as $dir)
	{
		// Check if directory exists
		if (!is_dir($dir)) continue;

		$handle=opendir($dir);
		if (is_resource($handle))
		{
			while (($file = readdir($handle))!==false)
			{
				if (is_readable($dir.'/'.$file) && preg_match('/^functions_([^_]+)\.php/',$file,$reg))
				{
					$authfile = $dir.'/'.$file;
					$mode = $reg[1];

					$result=include_once($authfile);
					if ($result)
					{
						// Call function to check user/password
						$usertotest=$_POST["username"];
						$passwordtotest=$_POST["password"];
						$entitytotest=$_POST["entity"];
						$function='check_user_password_'.$mode;
						$login=$function($usertotest,$passwordtotest,$entitytotest);
						if ($login)
						{
							$conf->authmode=$mode;	// This properties is defined only when logged
						}
					}
					else
					{
						dol_syslog("Authentification ko - failed to load file '".$authfile."'",LOG_ERR);
						sleep(1);
						$langs->load('main');
						$langs->load('other');
						$_SESSION["dol_loginmesg"]=$langs->trans("ErrorFailedToLoadLoginFileForMode",$mode);
					}
				}
			}
		}
		closedir($handle);
	}
	return $login;
}

/**
 *	Show Dolibarr default login page
 *	@param		langs		Lang object (must be initialized by a new).
 *	@param		conf		Conf object
 *	@param		mysoc		Company object
 */
function dol_loginfunction($langs,$conf,$mysoc)
{
	global $dolibarr_main_demo,$db;
	global $smartphone;

	$langcode=(GETPOST('lang')?((is_object($langs)&&$langs->defaultlang)?$langs->defaultlang:'auto'):GETPOST('lang'));
	$langs->setDefaultLang($langcode);

	$langs->load("main");
	$langs->load("other");
	$langs->load("help");
	$langs->load("admin");

	$main_authentication=$conf->file->main_authentication;
	$session_name=session_name();

	$dol_url_root = DOL_URL_ROOT;

	$php_self = $_SERVER['PHP_SELF'];
	$php_self.= $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';

	// Title
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;

	// Select templates
	if (preg_match('/^smartphone/',$conf->smart_menu) && isset($conf->browser->phone))
	{
		$template_dir = DOL_DOCUMENT_ROOT.'/theme/phones/smartphone/tpl/';
	}
	else
	{
		if (file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/login.tpl.php"))
		{
			$template_dir = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/tpl/";
		}
		else
		{
			$template_dir = DOL_DOCUMENT_ROOT."/core/tpl/";
		}
	}

	$conf->css = "/theme/".$conf->theme."/style.css.php?lang=".$langs->defaultlang;
	$conf_css = DOL_URL_ROOT.$conf->css;

	// Set cookie for timeout management
	$prefix=dol_getprefix();
	$sessiontimeout='DOLSESSTIMEOUT_'.$prefix;
	if (! empty($conf->global->MAIN_SESSION_TIMEOUT)) setcookie($sessiontimeout, $conf->global->MAIN_SESSION_TIMEOUT, 0, "/", '', 0);

	if (GETPOST("urlfrom")) $_SESSION["urlfrom"]=GETPOST("urlfrom");
	else unset($_SESSION["urlfrom"]);

	if (! GETPOST("username")) $focus_element='username';
	else $focus_element='password';

	$login_background=DOL_URL_ROOT.'/theme/login_background.png';
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
	{
		$login_background=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png';
	}

	$demologin='';
	$demopassword='';
	if (! empty($dolibarr_main_demo))
	{
		$tab=explode(',',$dolibarr_main_demo);
		$demologin=$tab[0];
		$demopassword=$tab[1];
	}

	// Entity cookie
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
	{
		$lastuser = '';
		$lastentity = $_POST['entity'];

		if (! empty($conf->global->MAIN_MULTICOMPANY_COOKIE))
		{
			$prefix=dol_getprefix();
			$entityCookieName = 'DOLENTITYID_'.$prefix;
			if (isset($_COOKIE[$entityCookieName]))
			{
				include_once(DOL_DOCUMENT_ROOT . "/core/class/cookie.class.php");

				$cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

				$entityCookie = new DolCookie($cryptkey);
				$cookieValue = $entityCookie->_getCookie($entityCookieName);
				list($lastuser, $lastentity) = explode('|', $cookieValue);
			}
		}
	}

	// Login
	$login = (!empty($lastuser)?$lastuser:(GETPOST("username","alpha",2)?GETPOST("username","alpha",2):$demologin));
	$password = $demopassword;

	// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
	$width=0;
	$rowspan=2;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';

	if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=128;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}

	// Entity field
	$select_entity='';
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY) && empty($conf->global->MULTICOMPANY_HIDE_LOGIN_COMBOBOX))
	{
		$rowspan++;

		$res=dol_include_once('/multicompany/class/actions_multicompany.class.php');
		if ($res)
		{
			$mc = new ActionsMulticompany($db);

			$select_entity=$mc->select_entities($lastentity, 'tabindex="3"');
		}
	}

	// Security graphical code
	$captcha=0;
	$captcha_refresh='';
	if (function_exists("imagecreatefrompng") && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
	{
		$captcha=1;
		$captcha_refresh=img_refresh();
	}

	// Extra link
	$forgetpasslink=0;
	$helpcenterlink=0;
	if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK) || empty($conf->global->MAIN_HELPCENTER_DISABLELINK))
	{
		if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
		{
			$forgetpasslink=1;
		}

		if (empty($conf->global->MAIN_HELPCENTER_DISABLELINK))
		{
			$helpcenterlink=1;
		}
	}

	// Home message
	if (! empty($conf->global->MAIN_HOME))
	{
		$i=0;
		while (preg_match('/__\(([a-zA-Z]+)\)__/i',$conf->global->MAIN_HOME,$reg) && $i < 100)
		{
			$conf->global->MAIN_HOME=preg_replace('/__\('.$reg[1].'\)__/i',$langs->trans($reg[1]),$conf->global->MAIN_HOME);
			$i++;
		}
	}
	$main_home=dol_htmlcleanlastbr($conf->global->MAIN_HOME);

	// Google AD
	$main_google_ad_client = ((! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))?1:0);

	$dol_loginmesg = $_SESSION["dol_loginmesg"];

	include($template_dir.'login.tpl.php');	// To use native PHP

	$_SESSION["dol_loginmesg"] = '';
}

/**
 *  Fonction pour initialiser un salt pour la fonction crypt
 *  @param		$type		2=>renvoi un salt pour cryptage DES
 *							12=>renvoi un salt pour cryptage MD5
 *							non defini=>renvoi un salt pour cryptage par defaut
 *	@return		string		Chaine salt
 */
function makesalt($type=CRYPT_SALT_LENGTH)
{
	dol_syslog("security.lib.php::makesalt type=".$type);
	switch($type)
	{
		case 12:	// 8 + 4
			$saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
		case 8:		// 8 + 4 (Pour compatibilite, ne devrait pas etre utilise)
			$saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
		case 2:		// 2
		default: 	// by default, fall back on Standard DES (should work everywhere)
			$saltlen=2; $saltprefix=''; $saltsuffix=''; break;
	}
	$salt='';
	while(dol_strlen($salt) < $saltlen) $salt.=chr(mt_rand(64,126));

	$result=$saltprefix.$salt.$saltsuffix;
	dol_syslog("security.lib.php::makesalt return=".$result);
	return $result;
}

/**
 *  Encode or decode database password in config file
 *  @param   	level   	Encode level: 0 no encoding, 1 encoding
 *	@return		int			<0 if KO, >0 if OK
 */
function encodedecode_dbpassconf($level=0)
{
	dol_syslog("security.lib::encodedecode_dbpassconf level=".$level, LOG_DEBUG);
	$config = '';
	$passwd='';
	$passwd_crypted='';

	if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','r'))
	{
		while(!feof($fp))
		{
			$buffer = fgets($fp,4096);

			$lineofpass=0;

			if (preg_match('/^[^#]*dolibarr_main_db_encrypted_pass[\s]*=[\s]*(.*)/i',$buffer,$reg))	// Old way to save crypted value
			{
				$val = trim($reg[1]);	// This also remove CR/LF
				$val=preg_replace('/^["\']/','',$val);
				$val=preg_replace('/["\'][\s;]*$/','',$val);
				if (! empty($val))
				{
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
					$lineofpass=1;
				}
			}
			elseif (preg_match('/^[^#]*dolibarr_main_db_pass[\s]*=[\s]*(.*)/i',$buffer,$reg))
			{
				$val = trim($reg[1]);	// This also remove CR/LF
				$val=preg_replace('/^["\']/','',$val);
				$val=preg_replace('/["\'][\s;]*$/','',$val);
				if (preg_match('/crypted:/i',$buffer))
				{
					$val = preg_replace('/crypted:/i','',$val);
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
				}
				else
				{
					$passwd = $val;
					$val = dol_encode($val);
					$passwd_crypted = $val;
				}
				$lineofpass=1;
			}

			// Output line
			if ($lineofpass)
			{
				// Add value at end of file
				if ($level == 0)
				{
					$config .= '$dolibarr_main_db_pass="'.$passwd.'";'."\n";
				}
				if ($level == 1)
				{
					$config .= '$dolibarr_main_db_pass="crypted:'.$passwd_crypted.'";'."\n";
				}

				//print 'passwd = '.$passwd.' - passwd_crypted = '.$passwd_crypted;
				//exit;
			}
			else
			{
				$config .= $buffer;
			}
		}
		fclose($fp);

		// Write new conf file
		$file=DOL_DOCUMENT_ROOT.'/conf/conf.php';
		if ($fp = @fopen($file,'w'))
		{
			fputs($fp, $config, dol_strlen($config));
			fclose($fp);
			// It's config file, so we set read permission for creator only.
			// Should set permission to web user and groups for users used by batch
			//@chmod($file, octdec('0600'));

			return 1;
		}
		else
		{
			dol_syslog("security.lib::encodedecode_dbpassconf Failed to open conf.php file for writing", LOG_WARNING);
			return -1;
		}
	}
	else
	{
		dol_syslog("security.lib::encodedecode_dbpassconf Failed to read conf.php", LOG_ERR);
		return -2;
	}
}

/**
 *	Encode a string
 *	@param   chain			chaine de caracteres a encoder
 *	@return  string_coded  	chaine de caracteres encodee
 */
function dol_encode($chain)
{
	for($i=0;$i<dol_strlen($chain);$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))+17);
	}

	$string_coded = base64_encode(implode("",$output_tab));
	return $string_coded;
}

/**
 *	Decode a string
 *	@param   chain    chaine de caracteres a decoder
 *	@return  string_coded  chaine de caracteres decodee
 */
function dol_decode($chain)
{
	$chain = base64_decode($chain);

	for($i=0;$i<dol_strlen($chain);$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))-17);
	}

	$string_decoded = implode("",$output_tab);
	return $string_decoded;
}


/**
 * Return array of ciphers mode available
 * @return strAv	Configuration file content
 */
function dol_efc_config()
{
	// Make sure we can use mcrypt_generic_init
	if (!function_exists("mcrypt_generic_init"))
	{
		return -1;
	}

	// Set a temporary $key and $data for encryption tests
	$key = md5(time() . getmypid());
	$data = mt_rand();

	// Get and sort available cipher methods
	$ciphers = mcrypt_list_algorithms();
	natsort($ciphers);

	// Get and sort available cipher modes
	$modes = mcrypt_list_modes();
	natsort($modes);

	foreach ($ciphers as $cipher)
	{
		foreach ($modes as $mode)
		{
			// Not Compatible
			$result = 'false';

			// open encryption module
			$td = @mcrypt_module_open($cipher, '', $mode, '');

			// if we could open the cipher
			if ($td)
			{
				// try to generate the iv
				$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);

				// if we could generate the iv
				if ($iv)
				{
					// initialize encryption
					@mcrypt_generic_init ($td, $key, $iv);

					// encrypt data
					$encrypted_data = mcrypt_generic($td, $data);

					// cleanup
					mcrypt_generic_deinit($td);

					// No error issued
					$result = 'true';
				}

				// close
				@mcrypt_module_close($td);
			}

			if ($result == "true") $available["$cipher"][] = $mode;
		}
	}

	if (count($available) > 0)
	{
		// Content of configuration
		$strAv = "<?php\n";
		$strAv.= "/* Copyright (C) 2003 HumanEasy, Lda. <humaneasy@sitaar.com>\n";
		$strAv.= " * Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>\n";
		$strAv.= " *\n";
		$strAv.= " * All rights reserved.\n";
		$strAv.= " * This file is licensed under GNU GPL version 2 or above.\n";
		$strAv.= " * Please visit http://www.gnu.org to now more about it.\n";
		$strAv.= " */\n\n";
		$strAv.= "/**\n";
		$strAv.= " *  Name: EasyFileCrypt Extending Crypt Class\n";
		$strAv.= " *  Version: 1.0\n";
		$strAv.= " *  Created: ".date("r")."\n";
		$strAv.= " *  Ciphers Installed on this system: ".count($ciphers)."\n";
		$strAv.= " */\n\n";
		$strAv.= "    \$xfss = Array ( ";

		foreach ($ciphers as $avCipher) {

			$v = "";
			if (count($available["$avCipher"]) > 0) {
				foreach ($available["$avCipher"] as $avMode)
				$v .= " '".$avMode."', ";

				$i = dol_strlen($v) - 2;
				if ($v[$i] == ",")
				$v = substr($v, 2, $i - 3);
			}
			if (!empty($v)) $v = " '".$v."' ";
			$strAv .= "'".$avCipher."' => Array (".$v."),\n                    ";
		}
		$strAv = rtrim($strAv);
		if ($strAv[dol_strlen($strAv) - 1] == ",")
		$strAv = substr($strAv, 0, dol_strlen($strAv) - 1);
		$strAv .= " );\n\n";
		$strAv .= "?>";

		return $strAv;
	}
}

/**
 * Return a generated password using default module
 * @param		generic		Create generic password
 * @return		string		New value for password
 */
function getRandomPassword($generic=false)
{
	global $db,$conf,$langs,$user;

	$generated_password='';
	if ($generic) $generated_password=dol_hash(mt_rand());
	else if ($conf->global->USER_PASSWORD_GENERATED)
	{
		$nomclass="modGeneratePass".ucfirst($conf->global->USER_PASSWORD_GENERATED);
		$nomfichier=$nomclass.".class.php";
		//print DOL_DOCUMENT_ROOT."/includes/modules/security/generate/".$nomclass;
		require_once(DOL_DOCUMENT_ROOT."/includes/modules/security/generate/".$nomfichier);
		$genhandler=new $nomclass($db,$conf,$langs,$user);
		$generated_password=$genhandler->getNewGeneratedPassword();
		unset($genhandler);
	}

	return $generated_password;
}

/**
 * 	Returns a hash of a string
 * 	@param 	chain	String to hash
 * 	@param	type	Type of hash (0:md5, 1:sha1, 2:sha1+md5)
 * 	@return	hash	hash of string
 */
function dol_hash($chain,$type=0)
{
	if ($type == 1) return sha1($chain);
	else if ($type == 2) return sha1(md5($chain));
	else return md5($chain);
}

?>