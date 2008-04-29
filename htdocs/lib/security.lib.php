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
   \file		htdocs/lib/security.lib.php
   \brief		Ensemble de fonctions de securite de dolibarr sous forme de lib
   \version		$Id$
*/


/**
   \brief      	Fonction pour initialiser un salt pour la fonction crypt
   \param		$type		2=>renvoi un salt pour cryptage DES
							12=>renvoi un salt pour cryptage MD5
							non defini=>renvoi un salt pour cryptage par defaut
   \return		string		Chaine salt
*/
function makesalt($type=CRYPT_SALT_LENGTH)
{
	dolibarr_syslog("security.lib.php::makesalt type=".$type);
	switch($type)
	{
	case 12:	// 8 + 4
		$saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
	case 8:		// 8 + 4 (Pour compatibilite, ne devrait pas etre utilisé)
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
   \brief   Encode\decode database password in config file
   \param   level    Encode level : 0 no enconding, 1 encoding
*/
function encodedecode_dbpassconf($level=0)
{
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
		
		if ($fp = @fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','w'))
		{
			fputs($fp, $config, strlen($config));
			fclose($fp);
			return 1;
		}
		else
		{
			return -1;
		}
	}
	else
	{
		return -2;
	}
}

/**
 \brief   Encode une chaine de caractére
 \param   chain    chaine de caractéres a encoder
 \return  string_coded  chaine de caractéres encodée
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
 \brief   Decode une chaine de caractére
 \param   chain    chaine de caractéres a decoder
 \return  string_coded  chaine de caractéres decodée
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

?>