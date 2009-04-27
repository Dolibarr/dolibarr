<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	$langs->load("main");
	$langs->load("other");

	$conf->css  = "theme/".$conf->theme."/".$conf->theme.".css";
	// Si feuille de style en php existe
	if (file_exists(DOL_DOCUMENT_ROOT.'/'.$conf->css.".php")) $conf->css.=".php";

	header('Cache-Control: Public, must-revalidate');
	header("Content-type: text/html; charset=".$conf->character_set_client);

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

	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/'.$conf->css.'">'."\n";

	print '<style type="text/css">'."\n";
	print '<!--'."\n";
	print '#login {';
	print '  margin-top: 70px;';
	print '  margin-bottom: 30px;';
	print '  text-align: center;';
	print '  font: 12px arial,helvetica;';
	print '}'."\n";
	print '#login table {';
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
	print '</head>'."\n";

	// Body
	print '<body class="body" onload="donnefocus();">'."\n\n";

	// Start Form
	print '<form id="login" name="login" method="post" action="';
	print $_SERVER['PHP_SELF'];
	print $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';
	print '">'."\n";

	// Table 1
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;
	print '<table summary="'.$title.'" cellpadding="0" cellspacing="0" border="0" align="center" width="480">'."\n";;
	print '<tr class="vmenu"><td align="center">'.$title.'</td></tr>'."\n";
	print '</table>'."\n";
	print '<br>'."\n\n";

	// Table 2
	print '<table summary="Login area" cellpadding="2" align="center" width="480">'."\n";

	print '<tr><td colspan="3">&nbsp;</td></tr>';

	print '<tr>';

	// Login field
	print '<td align="left" valign="bottom"> &nbsp; <b>'.$langs->trans("Login").'</b>  &nbsp;</td>';
	print '<td valign="bottom"><input type="text" id="username" name="username" class="flat" size="15" maxlength="25" value="'.(isset($_REQUEST["username"])?$_REQUEST["username"]:'').'" tabindex="1" /></td>';

	// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
	$title='';
	$width=0;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
	if (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo_small) && is_readable($conf->societe->dir_logos.'/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=96;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}
	print '<td rowspan="2" align="center"><img alt="Logo" title="'.$title.'" src="'.$urllogo.'"';
	if ($width) print ' width="'.$width.'"';
	print '></td>';
	print '</tr>'."\n";

	// Password field
	print '<tr><td align="left" valign="top" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("Password").'</b> &nbsp; </td>';
	print '<td valign="top" nowrap="nowrap"><input id="password" name="password" class="flat" type="password" size="15" maxlength="30" tabindex="2">';
	print '</td></tr>';
	
	// Entity field
	if ($conf->multicompany->enabled)
	{
		$html = new Form($db);
		
		//TODO: creer class
		$entity = array('1'=>'company1','2'=>'company2');
		
		print '<tr><td align="left" valign="top" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("Entity").'</b> &nbsp; </td>';
		print '<td valign="top" nowrap="nowrap">';
		$html->select_array('entity',$entity);
		print '</td></tr>';
	}

	print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";

	// Security graphical code
	if (function_exists("imagecreatefrompng") && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
	{
		//print "Info session: ".session_name().session_id();print_r($_SESSION);
		print '<tr><td align="left" valign="middle" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("SecurityCode").'</b></td>';
		print '<td valign="top" nowrap="nowrap" align="left" class="e">';

		print '<table><tr>';
		print '<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="3"></td>';
		print '<td><img src="'.DOL_URL_ROOT.'/lib/antispamimage.php" border="0" width="128" height="36"></td>';
		print '<td><a href="'.$_SERVER["PHP_SELF"].'">'.img_refresh().'</a></td>';
		print '</tr></table>';

		print '</td>';
		print '</tr>';
	}

	print '<tr><td colspan="3" style="text-align:center;"><br>';
	print '<input type="submit" class="button" value="&nbsp; '.$langs->trans("Connection").' &nbsp;" tabindex="4" />';
	print '</td></tr>';

	if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
	{
		print '<tr><td colspan="3" align="center"><a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/user/passwordforgotten.php">('.$langs->trans("PasswordForgotten").')</a></td></tr>';
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

	print "\n";
	print '<!-- authentication mode = '.$conf->main_authentication.' -->'."\n";
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
	case 8:		// 8 + 4 (Pour compatibilite, ne devrait pas etre utilis�)
		$saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
	case 2:		// 2
	default: 	// by default, fall back on Standard DES (should work everywhere)
		$saltlen=2; $saltprefix=''; $saltsuffix=''; break;
	}
	$salt='';
	while(strlen($salt) < $saltlen) $salt.=chr(rand(64,126));

	$result=$saltprefix.$salt.$saltsuffix;
	dol_syslog("security.lib.php::makesalt return=".$result);
	return $result;
}

/**
 *  \brief   	Encode\decode database password in config file
 *  \param   	level   Encode level : 0 no enconding, 1 encoding
 *	\return		int		<0 if KO, >0 if OK
 */
function encodedecode_dbpassconf($level=0)
{
	dol_syslog("security.lib::encodedecode_dbpassconf level=".$level, LOG_DEBUG);
	$config = '';

	if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','r'))
	{
		while(!feof($fp))
		{
			$buffer = fgets($fp,4096);

			if (strstr($buffer,"\$dolibarr_main_db_encrypted_pass") && $level == 0)
			{
				$passwd = strstr($buffer,"$dolibarr_main_db_encrypted_pass=");
				$passwd = substr(substr($passwd,2),0,-3);
				$passwd = dol_decode($passwd);
				$config .= "\$dolibarr_main_db_pass=\"$passwd\";\n";
			}
			else if (strstr($buffer,"\$dolibarr_main_db_pass") && $level == 1)
			{
				$passwd = strstr($buffer,"$dolibarr_main_db_pass=");
				$passwd = substr(substr($passwd,2),0,-3);
				$passwd = dol_encode($passwd);
				$config .= "\$dolibarr_main_db_encrypted_pass=\"$passwd\";\n";
			}
			else
			{
				$config .= $buffer;
			}
		}
		fclose($fp);

		$file=DOL_DOCUMENT_ROOT.'/conf/conf.php';
		if ($fp = @fopen($file,'w'))
		{
			fputs($fp, $config, strlen($config));
			fclose($fp);
			// It's config file, so we set permission for creator only
			// @chmod($file, octdec('0600'));

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
 *	\brief   Encode une chaine de caract�re
 *	\param   chaine			chaine de caract�res a encoder
 *	\return  string_coded  	chaine de caract�res encod�e
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
 *	\brief   Decode une chaine de caract�re
 *	\param   chain    chaine de caract�res a decoder
 *	\return  string_coded  chaine de caract�res decod�e
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
 *	\return	 malware	Nom du virus si infect� sinon retourne "null"
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