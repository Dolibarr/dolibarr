<?php
/* Copyright (C) 2000-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *      \file       htdocs/core/class/antivir.class.php
 *      \brief      File of class to scan viruses
 */

/**
 *      Class to scan for virus
 */
class AntiVir
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string Used to return message
	 */
	public $output;

	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Scan a file with antivirus.
	 *  This function runs the command defined in setup. This antivirus command must return 0 if OK.
	 *  Return also true (virus found) if file end with '.virus' (so we can make test safely).
	 *
	 *	@param	string	$file		File to scan
	 *	@return	int					Return integer <0 if KO (-98 if error, -99 if virus), 0 if OK
	 */
	public function dol_avscan_file($file)
	{
		// phpcs:enable
		global $conf;

		$return = 0;

		if (preg_match('/\.virus$/i', $file)) {
			$this->errors[] = 'File has an extension saying file is a virus';
			return -97;
		}

		$fullcommand = $this->getCliCommand($file);
		//$fullcommand="/usr/bin/clamdscan --fdpass '/tmp/phpuxoAEo'"
		//$fullcommand='"c:\Program Files (x86)\ClamWin\bin\clamscan.exe" --database="C:\Program Files (x86)\ClamWin\lib" "c:\temp\aaa.txt"';
		//var_dump($fullcommand);

		$safemode = ini_get("safe_mode");
		// Create a clean fullcommand
		dol_syslog("AntiVir::dol_avscan_file Run command=".$fullcommand." with safe_mode ".($safemode ? "on" : "off"));
		// Run CLI command.
		include_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
		$utils = new Utils($this->db);
		$outputfile = $conf->user->dir_temp.'/antivir.tmp';

		$result = $utils->executeCLI($fullcommand, $outputfile);

		$return_var = $result['result'];
		$output = $result['output'];
		$errorstring = $result['error'];

		if (is_null($output)) {
			$output = array();
		}

		dol_syslog("AntiVir::dol_avscan_file Result return_var=".$return_var." output=".$output);

		$returncodevirus = 1;
		if ($return_var == $returncodevirus) {	// Virus found
			$this->errors = array($errorstring, $output);
			return -99;
		}

		if ($return_var > 0) {					// If other error
			$this->errors = array($errorstring, $output);
			return -98;
		}

		// If return code = 0
		return 1;
	}



	/**
	 *	Get full Command Line to run
	 *
	 *	@param	string	$file		File to scan
	 *	@return	string				Full command line to run
	 */
	public function getCliCommand($file)
	{
		global $conf;

		$maxreclevel = 5; // maximal recursion level
		$maxfiles = 1000; // maximal number of files to be scanned within archive
		$maxratio = 200; // maximal compression ratio
		$bz2archivememlim = 0; // limit memory usage for bzip2 (0/1)
		$maxfilesize = 10485760; // archived files larger than this value (in bytes) will not be scanned

		$command = $conf->global->MAIN_ANTIVIRUS_COMMAND;
		$param = $conf->global->MAIN_ANTIVIRUS_PARAM;

		$param = preg_replace('/%maxreclevel/', $maxreclevel, $param);
		$param = preg_replace('/%maxfiles/', $maxfiles, $param);
		$param = preg_replace('/%maxratio/', $maxratio, $param);
		$param = preg_replace('/%bz2archivememlim/', $bz2archivememlim, $param);
		$param = preg_replace('/%maxfilesize/', $maxfilesize, $param);
		$param = preg_replace('/%file/', trim($file), $param);

		if (!preg_match('/%file/', $conf->global->MAIN_ANTIVIRUS_PARAM)) {
			$param = $param." ".escapeshellarg(trim($file));
		}

		if (preg_match("/\s/", $command)) {
			$command = escapeshellarg($command); // Force use of quotes on command. Using escapeshellcmd fails.
		}

		$forbidden_chars_to_replace = array("*", "?", "\"", "<", ">", "|", "[", "]", ";", '°', '$');
		$ret = dol_sanitizePathName($command).' '.dol_string_nospecial($param, '_', $forbidden_chars_to_replace);

		//$ret=$command.' '.$param.' 2>&1';
		//print "xx".$ret."xx";exit;

		return $ret;
	}
}
