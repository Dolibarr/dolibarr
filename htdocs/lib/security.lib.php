<?php
/* Copyright (C) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \brief			Ensemble de fonctions de securite de dolibarr sous forme de lib
 *  \version		$Id$
 */


/**
 *  \brief      Fonction pour initialiser un salt pour la fonction crypt
 *  \param		$type		2=>renvoi un salt pour cryptage DES
 *							12=>renvoi un salt pour cryptage MD5
 *							non defini=>renvoi un salt pour cryptage par defaut
 *	\return		string		Chaine salt
 */
function makesalt($type=CRYPT_SALT_LENGTH)
{
	dolibarr_syslog("security.lib.php::makesalt type=".$type);
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
	dolibarr_syslog("security.lib.php::makesalt return=".$result);
	return $result;
}

/**
 *  \brief   	Encode\decode database password in config file
 *  \param   	level   Encode level : 0 no enconding, 1 encoding
 *	\return		int		<0 if KO, >0 if OK	
 */
function encodedecode_dbpassconf($level=0)
{
	dolibarr_syslog("security.lib::encodedecode_dbpassconf level=".$level, LOG_DEBUG);
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
			dolibarr_syslog("security.lib::encodedecode_dbpassconf Failed to open conf.php file for writing", LOG_WARNING);
			return -1;
		}
	}
	else
	{
		dolibarr_syslog("security.lib::encodedecode_dbpassconf Failed to read conf.php", LOG_ERR);
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