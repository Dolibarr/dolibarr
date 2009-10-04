<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *  \file			htdocs/lib/security.lib.php
 *  \brief			Set of function used for dolibarr security
 *  \version		$Id$
 */


/**
 *	\brief      Show Dolibarr default login page
 *	\param		langs		Lang object
 *	\param		conf		Conf object
 *	\param		mysoc		Company object
 *	\remarks    You must change HTML code in this page to change design of logon page.
 */
function dol_loginfunction($langs,$conf,$mysoc)
{
	$langcode=(empty($_GET["lang"])?'auto':$_GET["lang"]);
	$langs->setDefaultLang($langcode);

	$langs->load("main");
	$langs->load("other");

	$conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
	// Si feuille de style en php existe
	if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

	header('Cache-Control: Public, must-revalidate');
	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	// Set cookie for timeout management
	$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
	if (! empty($conf->global->MAIN_SESSION_TIMEOUT)) setcookie($sessiontimeout, $conf->global->MAIN_SESSION_TIMEOUT, 0, "/", '', 0);

	if (! empty($_REQUEST["urlfrom"])) $_SESSION["urlfrom"]=$_REQUEST["urlfrom"];
	else unset($_SESSION["urlfrom"]);

	// Ce DTD est KO car inhibe document.body.scrollTop
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	// Ce DTD est OK
	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";

	// En tete html
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
	print "<title>".$langs->trans("Login")."</title>\n";

	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/'.$conf->css.'?lang='.$langs->defaultlang.'">'."\n";

	print '<style type="text/css">'."\n";
	print '<!--'."\n";
	print '#login {';
	print '  margin-top: '.(empty($conf->browser->phone)?'70px;':'10px;');
	print '  margin-bottom: '.(empty($conf->browser->phone)?'30px;':'5px;');
	print '  text-align: center;';
	print '  font: 12px arial,helvetica;';
	print '}'."\n";
	print '#login table {';
	if (empty($conf->browser->phone)) print '  width: 498px;';
	print '  border: 1px solid #C0C0C0;';
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
	{
		print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png) repeat-x;';
	}
	else
	{
		print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/login_background.png) repeat-x;';
	}
	print 'font-size: 12px;';
	print '}'."\n";
	print '-->'."\n";
	print '</style>'."\n";
	print '<script type="text/javascript">'."\n";
	print "function donnefocus() {\n";
	if (! $_REQUEST["username"]) print "document.getElementById('username').focus();\n";
	else print "document.getElementById('password').focus();\n";
	print "}\n";
	print '</script>'."\n";
	print '<!-- HTTP_USER_AGENT = '.$_SERVER["HTTP_USER_AGENT"].' -->'."\n";
	print '</head>'."\n";

	// Body
	print '<body class="body" onload="donnefocus();">'."\n\n";
	// Start Form
	print '<form id="login" name="login" method="post" action="';
	print $_SERVER['PHP_SELF'];
	print $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';
	print '">'."\n";

	// Token field
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';

	// Table 1
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;
	print '<table class="login" summary="'.$title.'" cellpadding="0" cellspacing="0" border="0" align="center">'."\n";;
	print '<tr class="vmenu"><td align="center">'.$title.'</td></tr>'."\n";
	print '</table>'."\n";
	print '<br>'."\n\n";

	// Table 2
	print '<table class="login" summary="Login area" cellpadding="2" align="center">'."\n";

	print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";

	print '<tr>';

	$demologin='';
	$demopassword='';
	global $dolibarr_main_demo;
	if (! empty($dolibarr_main_demo))
	{
		$tab=split(',',$dolibarr_main_demo);
		$demologin=$tab[0];
		$demopassword=$tab[1];
	}

	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
	{
		$lastuser = '';
		$lastentity = '';

		if (! empty($conf->global->MAIN_MULTICOMPANY_COOKIE))
		{
			$entityCookieName = 'DOLENTITYID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
			if (isset($_COOKIE[$entityCookieName]))
			{
				include_once(DOL_DOCUMENT_ROOT . "/core/cookie.class.php");

				$cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

				$entityCookie = new DolCookie($cryptkey);
				$cookieValue = $entityCookie->_getCookie($entityCookieName);
				list($lastuser, $lastentity) = split('\|', $cookieValue);
			}
		}
	}

	// Login field
	print '<td valign="bottom"> &nbsp; <b>'.$langs->trans("Login").'</b> &nbsp; </td>'."\n";
	print '<td valign="bottom" nowrap="nowrap"><input type="text" id="username" name="username" class="flat" size="15" maxlength="25" value="';
	print (!empty($lastuser)?$lastuser:(isset($_REQUEST["username"])?$_REQUEST["username"]:$demologin));
	print '" tabindex="1" /></td>'."\n";

	// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
	$width=0;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
	if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=96;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}
	$rowspan = 2;
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $rowspan++;
	print '<td rowspan="'.$rowspan.'" align="center" valign="top">'."\n";
	if (empty($conf->browser->phone))
	{
		print '<img alt="Logo" title="" src="'.$urllogo.'"';
		if ($width) print ' width="'.$width.'"';
		print '>';
	}
	print '</td>';
	print '</tr>'."\n";

	// Password field
	print '<tr><td valign="top" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("Password").'</b> &nbsp; </td>'."\n";
	print '<td valign="top" nowrap="nowrap"><input id="password" name="password" class="flat" type="password" size="15" maxlength="30" value="';
	print $demopassword;
	print '" tabindex="2">';
	print '</td></tr>'."\n";

	// Entity field
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
	{
		$html = new Form($db);

		//TODO: creer class
		$entity = array('1'=>'company1','2'=>'company2');

		print '<tr><td valign="top" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("Entity").'</b> &nbsp; </td>'."\n";
		print '<td valign="top" nowrap="nowrap">';
		print $html->selectarray('entity',$entity,$lastentity,0,0,0,1,'tabindex="3"');
		print '</td></tr>'."\n";
	}

	// Security graphical code
	if (function_exists("imagecreatefrompng") && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
	{
		//print "Info session: ".session_name().session_id();print_r($_SESSION);
		print '<tr><td valign="middle" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("SecurityCode").'</b></td>'."\n";
		print '<td valign="top" nowrap="nowrap" align="left" class="e">'."\n";

		print '<table style="width: 100px;"><tr>'."\n";	// Force width to a small value
		print '<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="4"></td>'."\n";
		$width=128;$height=36;
		if (! empty($conf->browser->phone)) $width=64; $height=24;
		print '<td><img src="'.DOL_URL_ROOT.'/lib/antispamimage.php" border="0" width="'.$width.'" height="'.$height.'"></td>'."\n";
		print '<td><a href="'.$_SERVER["PHP_SELF"].'">'.img_refresh().'</a></td>'."\n";
		print '</tr></table>'."\n";

		print '</td>';
		print '</tr>'."\n";
	}
	
	print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";

	print '<tr><td colspan="3" style="text-align:center;"><br>';
	print '<input type="submit" class="button" value="&nbsp; '.$langs->trans("Connection").' &nbsp;" tabindex="5" />';
	print '</td></tr>';

	if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK) || empty($conf->global->MAIN_HELPCENTER_DISABLELINK))
	{
		print '<tr><td colspan="3" align="center">';
		if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
		{
			print '<a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/user/passwordforgotten.php">(';
			print $langs->trans("PasswordForgotten");
		}

		if (empty($conf->global->MAIN_HELPCENTER_DISABLELINK))
		{
			$langs->load("help");
			print '<a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/support/index.php" target="_blank">';
			if (! empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK)) print '(';
			else print '  -  ';
			print $langs->trans("NeedHelpCenter");
		}
		print ')</a>';
		print '</td></tr>';
	}

	print '</table>'."\n";

	// Hidden fields
	print '<input type="hidden" name="loginfunction" value="loginfunction" />'."\n";

	print '</form>'."\n";

	// Message
	if (! empty($_SESSION["dol_loginmesg"]))
	{
		print '<center><table width="60%"><tr><td align="center" class="small"><div class="error">';
		print $_SESSION["dol_loginmesg"];
		$_SESSION["dol_loginmesg"]="";
		print '</div></td></tr></table></center>'."\n";
	}
	if (! empty($conf->global->MAIN_HOME))
	{
		print '<center><table summary="info" cellpadding="0" cellspacing="0" border="0" align="center" width="750"><tr><td align="center">';
		$i=0;
		while (eregi('__\(([a-zA-Z]+)\)__',$conf->global->MAIN_HOME,$reg) && $i < 100)
		{
			$conf->global->MAIN_HOME=eregi_replace('__\('.$reg[1].'\)__',$langs->trans($reg[1]),$conf->global->MAIN_HOME);
			$i++;
		}
		print nl2br($conf->global->MAIN_HOME);
		print '</td></tr></table></center><br>'."\n";
	}

	// Google Adsense (ex: demo mode)
	if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
	{
		print '<div align="center">'."\n";
		print '<script type="text/javascript"><!--'."\n";
		print 'google_ad_client = "'.$conf->global->MAIN_GOOGLE_AD_CLIENT.'";'."\n";
		print '/* '.$conf->global->MAIN_GOOGLE_AD_WIDTH.'x'.$conf->global->MAIN_GOOGLE_AD_HEIGHT.', '.$conf->global->MAIN_GOOGLE_AD_NAME.' */'."\n";
		print 'google_ad_slot = "'.$conf->global->MAIN_GOOGLE_AD_SLOT.'";'."\n";
		print 'google_ad_width = '.$conf->global->MAIN_GOOGLE_AD_WIDTH.';'."\n";
		print 'google_ad_height = '.$conf->global->MAIN_GOOGLE_AD_HEIGHT.';'."\n";
		print '//-->'."\n";
		print '</script>'."\n";
		print '<script type="text/javascript"'."\n";
		print 'src="http://pagead2.googlesyndication.com/pagead/show_ads.js">'."\n";
		print '</script>'."\n";
		print '</div>'."\n";
	}

	print "\n";
	print '<!-- authentication mode = '.$conf->file->main_authentication.' -->'."\n";
	print '<!-- cookie name used for this session = '.session_name().' -->'."\n";
	print '<!-- urlfrom in this session = '.(isset($_SESSION["urlfrom"])?$_SESSION["urlfrom"]:'').' -->'."\n";

	// Fin entete html
	print "\n</body>\n</html>";
}


/**
 *  \brief      Fonction pour initialiser un salt pour la fonction crypt
 *  \param		$type		2=>renvoi un salt pour cryptage DES
 *							12=>renvoi un salt pour cryptage MD5
 *							non defini=>renvoi un salt pour cryptage par defaut
 *	\return		string		Chaine salt
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
	while(strlen($salt) < $saltlen) $salt.=chr(mt_rand(64,126));

	$result=$saltprefix.$salt.$saltsuffix;
	dol_syslog("security.lib.php::makesalt return=".$result);
	return $result;
}

/**
 *  \brief   	Encode\decode database password in config file
 *  \param   	level   	Encode level: 0 no encoding, 1 encoding
 *	\return		int			<0 if KO, >0 if OK
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

			if (eregi('^[^#]*dolibarr_main_db_encrypted_pass[ ]*=[ ]*(.*)',$buffer,$reg))	// Old way to save crypted value
			{
				$val = trim($reg[1]);	// This also remove CR/LF
				$val=eregi_replace('^["\']','',$val);
				$val=eregi_replace('["\'][ ;]*$','',$val);
				if (! empty($val))
				{
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
					$lineofpass=1;
				}
			}
			elseif (eregi('^[^#]*dolibarr_main_db_pass[ ]*=[ ]*(.*)',$buffer,$reg))
			{
				$val = trim($reg[1]);	// This also remove CR/LF
				$val=eregi_replace('^["\']','',$val);
				$val=eregi_replace('["\'][ ;]*$','',$val);
				if (eregi('crypted:',$buffer))
				{
					$val = eregi_replace('crypted:','',$val);
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
			fputs($fp, $config, strlen($config));
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
 *	\brief   Encode une chaine de caractere
 *	\param   chaine			chaine de caracteres a encoder
 *	\return  string_coded  	chaine de caracteres encodee
 */
function dol_encode($chain)
{
	for($i=0;$i<strlen($chain);$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))+17);
	}

	$string_coded = base64_encode(implode ("",$output_tab));
	return $string_coded;
}

/**
 *	\brief   Decode une chaine de caractere
 *	\param   chain    chaine de caracteres a decoder
 *	\return  string_coded  chaine de caracteres decodee
 */
function dol_decode($chain)
{
	$chain = base64_decode($chain);

	for($i=0;$i<strlen($chain);$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))-17);
	}

	$string_decoded = implode ("",$output_tab);
	return $string_decoded;
}


/**
 *	\brief  Scan les fichiers avec un anti-virus
 *	\param	 file			Fichier a scanner
 *	\return	 malware	Nom du virus si infecte sinon retourne "null"
 */
function dol_avscan_file($file)
{
	$malware = '';

	// Clamav
	if (function_exists("cl_scanfile"))
	{
		$maxreclevel = 5 ; // maximal recursion level
		$maxfiles = 1000; // maximal number of files to be scanned within archive
		$maxratio = 200; // maximal compression ratio
		$archivememlim = 0; // limit memory usage for bzip2 (0/1)
		$maxfilesize = 10485760; // archived files larger than this value (in bytes) will not be scanned

		cl_setlimits($maxreclevel, $maxfiles, $maxratio, $archivememlim, $maxfilesize);
		$malware = cl_scanfile($file);
	}

	return $malware;
}

?>