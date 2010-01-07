<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *      \file       htdocs/lib/antivir.class.php
 *      \brief      File of class to scan viruses
 *		\version    $Id$
 *      \author	    Laurent Destailleur.
 */

/**
 *      \class      AntiVir
 *      \brief      Class to scan for virus
 */
class AntiVir
{
	var $error;
	var $output;
	var $db;

	/**
	 * Constructor
	 *
	 * @param unknown_type $db
	 * @return AntiVir
	 */
	function AntiVir($db)
	{
		$this->db=$db;
	}

	/**
	 *	\brief  	Scan a file with antivirus
	 *	\param	 	file			File to scan
	 *	\return	 	malware			Name of virus found or ''
	 */
	function dol_avscan_file($file)
	{
		global $conf;

		$return = 0;

		$maxreclevel = 5 ; 			// maximal recursion level
		$maxfiles = 1000; 			// maximal number of files to be scanned within archive
		$maxratio = 200; 			// maximal compression ratio
		$bz2archivememlim = 0; 		// limit memory usage for bzip2 (0/1)
		$maxfilesize = 10485760; 	// archived files larger than this value (in bytes) will not be scanned

		@set_time_limit($cfg['ExecTimeLimit']);
		$outputfile=$conf->admin->dir_temp.'/dol_avscan_file.out.'.session_id();

		$command=$conf->global->MAIN_ANTIVIRUS_COMMAND;
		$param=$conf->global->MAIN_ANTIVIRUS_PARAM;

		if (preg_match('/%file/',$conf->global->MAIN_ANTIVIRUS_PARAM)) $param=preg_replace('/%file/',trim($file),$param);
		else $param=trim($file);
		$param=preg_replace('/%maxreclevel/',$maxreclevel,$param);
		$param=preg_replace('/%maxfiles/',$maxfiles,$param);
		$param=preg_replace('/%maxratio/',$maxratiod,$param);
		$param=preg_replace('/%bz2archivememlim/',$bz2archivememlim,$param);
		$param=preg_replace('/%maxfilesize/',$maxfilesize,$param);

		// Create a clean fullcommand
		//print $command." ".$param;
		if (preg_match("/\s/",$command)) $command=escapeshellarg($command);	// Use quotes on command
		if (preg_match("/\s/",$param)) $param=escapeshellarg($param);		// Use quotes on param
		//print $command." ".$param;

		$output=array();
		$return_var=0;
		$fullcommand=$command.' '.$param.' 2>&1';
		dol_syslog("Run command=".$fullcommand);
		exec($fullcommand, $output, $return_var);

/*
		$handle = fopen($outputfile, 'w');
		if ($handle)
		{
			$handlein = popen($fullcommand, 'r');
			while (!feof($handlein))
			{
				$read = fgets($handlein);
				fwrite($handle,$read);
			}
			pclose($handlein);

			$errormsg = fgets($handle,2048);
			$this->output=$errormsg;

			fclose($handle);

			if (! empty($conf->global->MAIN_UMASK))
				@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}
		else
		{
			$langs->load("errors");
			dol_syslog("Failed to open file ".$outputfile,LOG_ERR);
			$this->error="ErrorFailedToWriteInDir";
			$return=-1;
		}
		*/

		dol_syslog("Result return_var=".$return_var." output=".join(',',$output));

		return $return;
	}

}

?>