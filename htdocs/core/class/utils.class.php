<?php
/* Copyright (C) 2016 Destailleur Laurent <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * any later version.
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
 *   	\file       htdocs/core/class/utils.class.php
 *      \ingroup    core
 *		\brief      File for Utils class
 */


/**
 *		Class to manage utility methods
 */
class Utils
{
	/**
     * @var DoliDB Database handler.
     */
    public $db;

    public $output; // Used by Cron method to return message
    public $result; // Used by Cron method to return data

	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Purge files into directory of data files.
	 *  CAN BE A CRON TASK
	 *
	 *  @param	string      $choice		   Choice of purge mode ('tempfiles', '' or 'tempfilesold' to purge temp older than $nbsecondsold seconds, 'allfiles', 'logfile')
	 *  @param  int         $nbsecondsold  Nb of seconds old to accept deletion of a directory if $choice is 'tempfilesold'
	 *  @return	int						   0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function purgeFiles($choice = 'tempfilesold', $nbsecondsold = 86400)
	{
		global $conf, $langs, $dolibarr_main_data_root;

		$langs->load("admin");

		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$filesarray = array();
		if (empty($choice)) $choice = 'tempfilesold';

		dol_syslog("Utils::purgeFiles choice=".$choice, LOG_DEBUG);

		if ($choice == 'tempfiles' || $choice == 'tempfilesold')
		{
			// Delete temporary files
			if ($dolibarr_main_data_root)
			{
			    $filesarray = dol_dir_list($dolibarr_main_data_root, "directories", 1, '^temp$', '', 'name', SORT_ASC, 2, 0, '', 1); // Do not follow symlinks

			    if ($choice == 'tempfilesold')
				{
					$now = dol_now();
					foreach ($filesarray as $key => $val)
					{
					    if ($val['date'] > ($now - ($nbsecondsold))) unset($filesarray[$key]); // Discard temp dir not older than $nbsecondsold
					}
				}
			}
		}

		if ($choice == 'allfiles')
		{
			// Delete all files (except install.lock, do not follow symbolic links)
			if ($dolibarr_main_data_root)
			{
				$filesarray = dol_dir_list($dolibarr_main_data_root, "all", 0, '', 'install\.lock$', 'name', SORT_ASC, 0, 0, '', 1);
			}
		}

		if ($choice == 'logfile')
		{
			// Define files log
			if ($dolibarr_main_data_root)
			{
				$filesarray = dol_dir_list($dolibarr_main_data_root, "files", 0, '.*\.log[\.0-9]*(\.gz)?$', 'install\.lock$', 'name', SORT_ASC, 0, 0, '', 1);
			}

			$filelog = '';
			if (!empty($conf->syslog->enabled))
			{
				$filelog = $conf->global->SYSLOG_FILE;
				$filelog = preg_replace('/DOL_DATA_ROOT/i', DOL_DATA_ROOT, $filelog);

				$alreadyincluded = false;
				foreach ($filesarray as $tmpcursor)
				{
					if ($tmpcursor['fullname'] == $filelog) { $alreadyincluded = true; }
				}
				if (!$alreadyincluded) $filesarray[] = array('fullname'=>$filelog, 'type'=>'file');
			}
		}

		$count = 0;
		$countdeleted = 0;
		$counterror = 0;
		if (count($filesarray))
		{
		    foreach ($filesarray as $key => $value)
			{
				//print "x ".$filesarray[$key]['fullname']."-".$filesarray[$key]['type']."<br>\n";
			    if ($filesarray[$key]['type'] == 'dir')
				{
					$startcount = 0;
					$tmpcountdeleted = 0;

					$result = dol_delete_dir_recursive($filesarray[$key]['fullname'], $startcount, 1, 0, $tmpcountdeleted);
					$count += $result;
					$countdeleted += $tmpcountdeleted;
				}
				elseif ($filesarray[$key]['type'] == 'file')
				{
					// If (file that is not logfile) or (if mode is logfile)
					if ($filesarray[$key]['fullname'] != $filelog || $choice == 'logfile')
					{
						$result = dol_delete_file($filesarray[$key]['fullname'], 1, 1);
						if ($result)
						{
							$count++;
							$countdeleted++;
						}
						else
						{
							$counterror++;
						}
					}
				}
			}

			// Update cachenbofdoc
			if (!empty($conf->ecm->enabled) && $choice == 'allfiles')
			{
				require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
				$ecmdirstatic = new EcmDirectory($this->db);
				$result = $ecmdirstatic->refreshcachenboffile(1);
			}
		}

		if ($count > 0)
		{
			$this->output = $langs->trans("PurgeNDirectoriesDeleted", $countdeleted);
			if ($count > $countdeleted) $this->output .= '<br>'.$langs->trans("PurgeNDirectoriesFailed", ($count - $countdeleted));
		}
		else $this->output = $langs->trans("PurgeNothingToDelete").($choice == 'tempfilesold' ? ' (older than 24h)' : '');

		// Recreate temp dir that are not automatically recreated by core code for performance purpose, we need them
		if (!empty($conf->api->enabled))
		{
		    dol_mkdir($conf->api->dir_temp);
		}
		dol_mkdir($conf->user->dir_temp);

		//return $count;
		return 0; // This function can be called by cron so must return 0 if OK
	}


	/**
	 *  Make a backup of database
	 *  CAN BE A CRON TASK
	 *
	 *  @param	string		$compression	   'gz' or 'bz' or 'none'
	 *  @param  string      $type              'mysql', 'postgresql', ...
	 *  @param  int         $usedefault        1=Use default backup profile (Set this to 1 when used as cron)
	 *  @param  string      $file              'auto' or filename to build
	 *  @param  int         $keeplastnfiles    Keep only last n files (not used yet)
	 *  @param	int		    $execmethod		   0=Use default method (that is 1 by default), 1=Use the PHP 'exec', 2=Use the 'popen' method
	 *  @return	int						       0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function dumpDatabase($compression = 'none', $type = 'auto', $usedefault = 1, $file = 'auto', $keeplastnfiles = 0, $execmethod = 0)
	{
		global $db, $conf, $langs, $dolibarr_main_data_root;
		global $dolibarr_main_db_name, $dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_port, $dolibarr_main_db_pass;

		$langs->load("admin");

		dol_syslog("Utils::dumpDatabase type=".$type." compression=".$compression." file=".$file, LOG_DEBUG);
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Check compression parameter
		if (!in_array($compression, array('none', 'gz', 'bz', 'zip')))
		{
			$langs->load("errors");
			$this->error = $langs->transnoentitiesnoconv("ErrorBadValueForParameter", $compression, "Compression");
			return -1;
		}

		// Check type parameter
		if ($type == 'auto') $type = $db->type;
		if (!in_array($type, array('postgresql', 'pgsql', 'mysql', 'mysqli', 'mysqlnobin')))
		{
			$langs->load("errors");
			$this->error = $langs->transnoentitiesnoconv("ErrorBadValueForParameter", $type, "Basetype");
			return -1;
		}

		// Check file parameter
		if ($file == 'auto')
		{
			$prefix = 'dump';
			$ext = 'sql';
			if (in_array($type, array('mysql', 'mysqli'))) { $prefix = 'mysqldump'; $ext = 'sql'; }
			//if ($label == 'PostgreSQL') { $prefix='pg_dump'; $ext='dump'; }
			if (in_array($type, array('pgsql'))) { $prefix = 'pg_dump'; $ext = 'sql'; }
			$file = $prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.strftime("%Y%m%d%H%M").'.'.$ext;
		}

		$outputdir = $conf->admin->dir_output.'/backup';
		$result = dol_mkdir($outputdir);
		$errormsg = '';

		// MYSQL
		if ($type == 'mysql' || $type == 'mysqli')
		{
			$cmddump = $conf->global->SYSTEMTOOLS_MYSQLDUMP;


			$outputfile = $outputdir.'/'.$file;
			// for compression format, we add extension
			$compression = $compression ? $compression : 'none';
			if ($compression == 'gz') $outputfile .= '.gz';
			if ($compression == 'bz') $outputfile .= '.bz2';
			$outputerror = $outputfile.'.err';
			dol_mkdir($conf->admin->dir_output.'/backup');

			// Parameteres execution
			$command = $cmddump;
			$command = preg_replace('/(\$|%)/', '', $command); // We removed chars that can be used to inject vars that contains space inside path of command without seeing there is a space to bypass the escapeshellarg.
			if (preg_match("/\s/", $command)) $command = escapeshellarg($command); // If there is spaces, we add quotes on command to be sure $command is only a program and not a program+parameters

			//$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
			$param = $dolibarr_main_db_name." -h ".$dolibarr_main_db_host;
			$param .= " -u ".$dolibarr_main_db_user;
			if (!empty($dolibarr_main_db_port)) $param .= " -P ".$dolibarr_main_db_port;
			if (!GETPOST("use_transaction", "alpha"))    $param .= " -l --single-transaction";
			if (GETPOST("disable_fk", "alpha") || $usedefault) $param .= " -K";
			if (GETPOST("sql_compat", "alpha") && GETPOST("sql_compat", "alpha") != 'NONE') $param .= " --compatible=".escapeshellarg(GETPOST("sql_compat", "alpha"));
			if (GETPOST("drop_database", "alpha"))        $param .= " --add-drop-database";
			if (GETPOST("use_mysql_quick_param", "alpha"))$param .= " --quick";
			if (GETPOST("sql_structure", "alpha") || $usedefault)
			{
				if (GETPOST("drop", "alpha") || $usedefault)	$param .= " --add-drop-table=TRUE";
				else 				       	         		    $param .= " --add-drop-table=FALSE";
			}
			else
			{
				$param .= " -t";
			}
			if (GETPOST("disable-add-locks", "alpha")) $param .= " --add-locks=FALSE";
			if (GETPOST("sql_data", "alpha") || $usedefault)
			{
				$param .= " --tables";
				if (GETPOST("showcolumns", "alpha") || $usedefault)	 $param .= " -c";
				if (GETPOST("extended_ins", "alpha") || $usedefault) $param .= " -e";
				else $param .= " --skip-extended-insert";
				if (GETPOST("delayed", "alpha"))	 	 $param .= " --delayed-insert";
				if (GETPOST("sql_ignore", "alpha"))	 $param .= " --insert-ignore";
				if (GETPOST("hexforbinary", "alpha") || $usedefault) $param .= " --hex-blob";
			}
			else
			{
				$param .= " -d"; // No row information (no data)
			}
			$param .= " --default-character-set=utf8"; // We always save output into utf8 charset
			$paramcrypted = $param;
			$paramclear = $param;
			if (!empty($dolibarr_main_db_pass))
			{
				$paramcrypted .= ' -p"'.preg_replace('/./i', '*', $dolibarr_main_db_pass).'"';
				$paramclear .= ' -p"'.str_replace(array('"', '`'), array('\"', '\`'), $dolibarr_main_db_pass).'"';
			}

			$handle = '';

			// Start call method to execute dump
			$fullcommandcrypted = $command." ".$paramcrypted." 2>&1";
			$fullcommandclear = $command." ".$paramclear." 2>&1";
			if ($compression == 'none') $handle = fopen($outputfile, 'w');
			if ($compression == 'gz')   $handle = gzopen($outputfile, 'w');
			if ($compression == 'bz')   $handle = bzopen($outputfile, 'w');

			$ok = 0;
			if ($handle)
			{
				if (!empty($conf->global->MAIN_EXEC_USE_POPEN)) $execmethod = $conf->global->MAIN_EXEC_USE_POPEN;
				if (empty($execmethod)) $execmethod = 1;

				dol_syslog("Utils::dumpDatabase execmethod=".$execmethod." command:".$fullcommandcrypted, LOG_DEBUG);

				// TODO Replace with executeCLI function
				if ($execmethod == 1)
				{
					$output_arr = array(); $retval = null;
					exec($fullcommandclear, $output_arr, $retval);

					if ($retval != 0)
					{
						$langs->load("errors");
						dol_syslog("Datadump retval after exec=".$retval, LOG_ERR);
						$errormsg = 'Error '.$retval;
						$ok = 0;
					}
					else
					{
						$i = 0;
						if (!empty($output_arr))
						{
							foreach ($output_arr as $key => $read)
							{
								$i++; // output line number
								if ($i == 1 && preg_match('/Warning.*Using a password/i', $read)) continue;
								fwrite($handle, $read.($execmethod == 2 ? '' : "\n"));
								if (preg_match('/'.preg_quote('-- Dump completed').'/i', $read)) $ok = 1;
								elseif (preg_match('/'.preg_quote('SET SQL_NOTES=@OLD_SQL_NOTES').'/i', $read)) $ok = 1;
							}
						}
					}
				}
				if ($execmethod == 2)	// With this method, there is no way to get the return code, only output
				{
					$handlein = popen($fullcommandclear, 'r');
					$i = 0;
					while (!feof($handlein))
					{
						$i++; // output line number
						$read = fgets($handlein);
						// Exclude warning line we don't want
						if ($i == 1 && preg_match('/Warning.*Using a password/i', $read)) continue;
						fwrite($handle, $read);
						if (preg_match('/'.preg_quote('-- Dump completed').'/i', $read)) $ok = 1;
						elseif (preg_match('/'.preg_quote('SET SQL_NOTES=@OLD_SQL_NOTES').'/i', $read)) $ok = 1;
					}
					pclose($handlein);
				}


				if ($compression == 'none') fclose($handle);
				if ($compression == 'gz')   gzclose($handle);
				if ($compression == 'bz')   bzclose($handle);

				if (!empty($conf->global->MAIN_UMASK))
					@chmod($outputfile, octdec($conf->global->MAIN_UMASK));
			}
			else
			{
				$langs->load("errors");
				dol_syslog("Failed to open file ".$outputfile, LOG_ERR);
				$errormsg = $langs->trans("ErrorFailedToWriteInDir");
			}

			// Get errorstring
			if ($compression == 'none') $handle = fopen($outputfile, 'r');
			if ($compression == 'gz')   $handle = gzopen($outputfile, 'r');
			if ($compression == 'bz')   $handle = bzopen($outputfile, 'r');
			if ($handle)
			{
				// Get 2048 first chars of error message.
				$errormsg = fgets($handle, 2048);
				//$ok=0;$errormsg='';  To force error

				// Close file
				if ($compression == 'none') fclose($handle);
				if ($compression == 'gz')   gzclose($handle);
				if ($compression == 'bz')   bzclose($handle);
				if ($ok && preg_match('/^-- (MySql|MariaDB)/i', $errormsg)) {	// No error
					$errormsg = '';
				}
				else
				{
					// Renommer fichier sortie en fichier erreur
					//print "$outputfile -> $outputerror";
					@dol_delete_file($outputerror, 1, 0, 0, null, false, 0);
					@rename($outputfile, $outputerror);
					// Si safe_mode on et command hors du parametre exec, on a un fichier out vide donc errormsg vide
					if (!$errormsg)
					{
						$langs->load("errors");
						$errormsg = $langs->trans("ErrorFailedToRunExternalCommand");
					}
				}
			}
			// Fin execution commande

			$this->output = $errormsg;
			$this->error = $errormsg;
			$this->result = array("commandbackuplastdone" => $command." ".$paramcrypted, "commandbackuptorun" => "");
			//if (empty($this->output)) $this->output=$this->result['commandbackuplastdone'];
		}

		// MYSQL NO BIN
		if ($type == 'mysqlnobin')
		{
			$outputfile = $outputdir.'/'.$file;
			$outputfiletemp = $outputfile.'-TMP.sql';
			// for compression format, we add extension
			$compression = $compression ? $compression : 'none';
			if ($compression == 'gz') $outputfile .= '.gz';
			if ($compression == 'bz') $outputfile .= '.bz2';
			$outputerror = $outputfile.'.err';
			dol_mkdir($conf->admin->dir_output.'/backup');

			if ($compression == 'gz' or $compression == 'bz')
			{
				$this->backupTables($outputfiletemp);
				dol_compress_file($outputfiletemp, $outputfile, $compression);
				unlink($outputfiletemp);
			}
			else
			{
				$this->backupTables($outputfile);
			}

			$this->output = "";
			$this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => "");
		}

		// POSTGRESQL
		if ($type == 'postgresql' || $type == 'pgsql')
		{
			$cmddump = $conf->global->SYSTEMTOOLS_POSTGRESQLDUMP;

			$outputfile = $outputdir.'/'.$file;
			// for compression format, we add extension
			$compression = $compression ? $compression : 'none';
			if ($compression == 'gz') $outputfile .= '.gz';
			if ($compression == 'bz') $outputfile .= '.bz2';
			$outputerror = $outputfile.'.err';
			dol_mkdir($conf->admin->dir_output.'/backup');

			// Parameteres execution
			$command = $cmddump;
			$command = preg_replace('/(\$|%)/', '', $command); // We removed chars that can be used to inject vars that contains space inside path of command without seeing there is a space to bypass the escapeshellarg.
			if (preg_match("/\s/", $command)) $command = escapeshellarg($command); // If there is spaces, we add quotes on command to be sure $command is only a program and not a program+parameters

			//$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
			//$param="-F c";
			$param = "-F p";
			$param .= " --no-tablespaces --inserts -h ".$dolibarr_main_db_host;
			$param .= " -U ".$dolibarr_main_db_user;
			if (!empty($dolibarr_main_db_port)) $param .= " -p ".$dolibarr_main_db_port;
			if (GETPOST("sql_compat") && GETPOST("sql_compat") == 'ANSI') $param .= "  --disable-dollar-quoting";
			if (GETPOST("drop_database"))        $param .= " -c -C";
			if (GETPOST("sql_structure"))
			{
				if (GETPOST("drop"))			 $param .= " --add-drop-table";
				if (!GETPOST("sql_data"))       $param .= " -s";
			}
			if (GETPOST("sql_data"))
			{
				if (!GETPOST("sql_structure"))	 $param .= " -a";
				if (GETPOST("showcolumns"))	     $param .= " -c";
			}
			$param .= ' -f "'.$outputfile.'"';
			//if ($compression == 'none')
			if ($compression == 'gz')   $param .= ' -Z 9';
			//if ($compression == 'bz')
			$paramcrypted = $param;
			$paramclear = $param;
			/*if (! empty($dolibarr_main_db_pass))
			 {
			 $paramcrypted.=" -W".preg_replace('/./i','*',$dolibarr_main_db_pass);
			 $paramclear.=" -W".$dolibarr_main_db_pass;
			 }*/
			$paramcrypted .= " -w ".$dolibarr_main_db_name;
			$paramclear .= " -w ".$dolibarr_main_db_name;

			$this->output = "";
			$this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => $command." ".$paramcrypted);
		}

		// Clean old files
		if (!$errormsg && $keeplastnfiles > 0)
		{
			$tmpfiles = dol_dir_list($conf->admin->dir_output.'/backup', 'files', 0, '', '(\.err|\.old|\.sav)$', 'date', SORT_DESC);
			$i = 0;
			foreach ($tmpfiles as $key => $val)
			{
				$i++;
				if ($i <= $keeplastnfiles) continue;
				dol_delete_file($val['fullname'], 0, 0, 0, null, false, 0);
			}
		}

		return ($errormsg ? -1 : 0);
	}



	/**
	 * Execute a CLI command.
	 *
	 * @param 	string	$command		Command line to execute.
	 * @param 	string	$outputfile		Output file (used only when method is 2). For exemple $conf->admin->dir_temp.'/out.tmp';
	 * @param	int		$execmethod		0=Use default method (that is 1 by default), 1=Use the PHP 'exec', 2=Use the 'popen' method
	 * @return	array					array('result'=>...,'output'=>...,'error'=>...). result = 0 means OK.
	 */
	public function executeCLI($command, $outputfile, $execmethod = 0)
	{
		global $conf, $langs;

		$result = 0;
		$output = '';
		$error = '';

		$command = escapeshellcmd($command);
		$command .= " 2>&1";

		if (!empty($conf->global->MAIN_EXEC_USE_POPEN)) $execmethod = $conf->global->MAIN_EXEC_USE_POPEN;
		if (empty($execmethod)) $execmethod = 1;
		//$execmethod=1;

		dol_syslog("Utils::executeCLI execmethod=".$execmethod." system:".$command, LOG_DEBUG);
		$output_arr = array();

		if ($execmethod == 1)
		{
			$retval = null;
			exec($command, $output_arr, $retval);
			$result = $retval;
			if ($retval != 0)
			{
				$langs->load("errors");
				dol_syslog("Utils::executeCLI retval after exec=".$retval, LOG_ERR);
				$error = 'Error '.$retval;
			}
		}
		if ($execmethod == 2)	// With this method, there is no way to get the return code, only output
		{
			$handle = fopen($outputfile, 'w+b');
			if ($handle)
			{
				dol_syslog("Utils::executeCLI run command ".$command);
				$handlein = popen($command, 'r');
				while (!feof($handlein))
				{
					$read = fgets($handlein);
					fwrite($handle, $read);
					$output_arr[] = $read;
				}
				pclose($handlein);
				fclose($handle);
			}
			if (!empty($conf->global->MAIN_UMASK)) @chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		}

		// Update with result
		if (is_array($output_arr) && count($output_arr) > 0)
		{
			foreach ($output_arr as $val)
			{
				$output .= $val.($execmethod == 2 ? '' : "\n");
			}
		}

		dol_syslog("Utils::executeCLI result=".$result." output=".$output." error=".$error, LOG_DEBUG);

		return array('result'=>$result, 'output'=>$output, 'error'=>$error);
	}

	/**
	 * Generate documentation of a Module
	 *
	 * @param 	string	$module		Module name
	 * @return	int					<0 if KO, >0 if OK
	 */
	public function generateDoc($module)
	{
		global $conf, $langs, $user, $mysoc;
		global $dirins;

		$error = 0;

		$modulelowercase = strtolower($module);
		$now = dol_now();

		// Dir for module
		$dir = $dirins.'/'.$modulelowercase;
		// Zip file to build
		$FILENAMEDOC = '';

		// Load module
		dol_include_once($modulelowercase.'/core/modules/mod'.$module.'.class.php');
		$class = 'mod'.$module;

		if (class_exists($class))
		{
			try {
				$moduleobj = new $class($this->db);
			}
			catch (Exception $e)
			{
				$error++;
				dol_print_error($e->getMessage());
			}
		}
		else
		{
			$error++;
			$langs->load("errors");
			dol_print_error($langs->trans("ErrorFailedToLoadModuleDescriptorForXXX", $module));
			exit;
		}

		$arrayversion = explode('.', $moduleobj->version, 3);
		if (count($arrayversion))
		{
			$FILENAMEASCII = strtolower($module).'.asciidoc';
			$FILENAMEDOC = strtolower($module).'.html';
			$FILENAMEDOCPDF = strtolower($module).'.pdf';

			$dirofmodule = dol_buildpath(strtolower($module), 0);
			$dirofmoduledoc = dol_buildpath(strtolower($module), 0).'/doc';
			$dirofmoduletmp = dol_buildpath(strtolower($module), 0).'/doc/temp';
			$outputfiledoc = $dirofmoduledoc.'/'.$FILENAMEDOC;
			if ($dirofmoduledoc)
			{
				if (!dol_is_dir($dirofmoduledoc)) dol_mkdir($dirofmoduledoc);
				if (!dol_is_dir($dirofmoduletmp)) dol_mkdir($dirofmoduletmp);
				if (!is_writable($dirofmoduletmp))
				{
					$this->error = 'Dir '.$dirofmoduletmp.' does not exists or is not writable';
					return -1;
				}

				if (empty($conf->global->MODULEBUILDER_ASCIIDOCTOR) && empty($conf->global->MODULEBUILDER_ASCIIDOCTORPDF))
				{
				    $this->error = 'Setup of module ModuleBuilder not complete';
				    return -1;
				}

				// Copy some files into temp directory, so instruction include::ChangeLog.md[] will works inside the asciidoc file.
				dol_copy($dirofmodule.'/README.md', $dirofmoduletmp.'/README.md', 0, 1);
				dol_copy($dirofmodule.'/ChangeLog.md', $dirofmoduletmp.'/ChangeLog.md', 0, 1);

				// Replace into README.md and ChangeLog.md (in case they are included into documentation with tag __README__ or __CHANGELOG__)
				$arrayreplacement = array();
				$arrayreplacement['/^#\s.*/m'] = ''; // Remove first level of title into .md files
				$arrayreplacement['/^#/m'] = '##'; // Add on # to increase level

				dolReplaceInFile($dirofmoduletmp.'/README.md', $arrayreplacement, '', 0, 0, 1);
				dolReplaceInFile($dirofmoduletmp.'/ChangeLog.md', $arrayreplacement, '', 0, 0, 1);


				$destfile = $dirofmoduletmp.'/'.$FILENAMEASCII;

				$fhandle = fopen($destfile, 'w+');
				if ($fhandle)
				{
					$specs = dol_dir_list(dol_buildpath(strtolower($module).'/doc', 0), 'files', 1, '(\.md|\.asciidoc)$', array('\/temp\/'));

					$i = 0;
					foreach ($specs as $spec)
					{
						if (preg_match('/notindoc/', $spec['relativename'])) continue; // Discard file
						if (preg_match('/example/', $spec['relativename'])) continue; // Discard file
						if (preg_match('/disabled/', $spec['relativename'])) continue; // Discard file

						$pathtofile = strtolower($module).'/doc/'.$spec['relativename'];
						$format = 'asciidoc';
						if (preg_match('/\.md$/i', $spec['name'])) $format = 'markdown';

						$filecursor = @file_get_contents($spec['fullname']);
						if ($filecursor)
						{
							fwrite($fhandle, ($i ? "\n<<<\n\n" : "").$filecursor."\n");
						}
						else
						{
							$this->error = 'Failed to concat content of file '.$spec['fullname'];
							return -1;
						}

						$i++;
					}

					fclose($fhandle);

					$contentreadme = file_get_contents($dirofmoduletmp.'/README.md');
					$contentchangelog = file_get_contents($dirofmoduletmp.'/ChangeLog.md');

					include DOL_DOCUMENT_ROOT.'/core/lib/parsemd.lib.php';

					//var_dump($phpfileval['fullname']);
					$arrayreplacement = array(
					    'mymodule'=>strtolower($module),
					    'MyModule'=>$module,
					    'MYMODULE'=>strtoupper($module),
					    'My module'=>$module,
					    'my module'=>$module,
					    'Mon module'=>$module,
					    'mon module'=>$module,
					    'htdocs/modulebuilder/template'=>strtolower($module),
					    '__MYCOMPANY_NAME__'=>$mysoc->name,
					    '__KEYWORDS__'=>$module,
					    '__USER_FULLNAME__'=>$user->getFullName($langs),
					    '__USER_EMAIL__'=>$user->email,
					    '__YYYY-MM-DD__'=>dol_print_date($now, 'dayrfc'),
					    '---Put here your own copyright and developer email---'=>dol_print_date($now, 'dayrfc').' '.$user->getFullName($langs).($user->email ? ' <'.$user->email.'>' : ''),
					    '__DATA_SPECIFICATION__'=>'Not yet available',
					    '__README__'=>dolMd2Asciidoc($contentreadme),
					    '__CHANGELOG__'=>dolMd2Asciidoc($contentchangelog),
					);

					dolReplaceInFile($destfile, $arrayreplacement);
				}

				// Launch doc generation
                $currentdir = getcwd();
                chdir($dirofmodule);

                require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
                $utils = new Utils($this->db);

                // Build HTML doc
				$command = $conf->global->MODULEBUILDER_ASCIIDOCTOR.' '.$destfile.' -n -o '.$dirofmoduledoc.'/'.$FILENAMEDOC;
				$outfile = $dirofmoduletmp.'/out.tmp';

				$resarray = $utils->executeCLI($command, $outfile);
				if ($resarray['result'] != '0')
				{
					$this->error = $resarray['error'].' '.$resarray['output'];
				}
				$result = ($resarray['result'] == 0) ? 1 : 0;

				// Build PDF doc
				$command = $conf->global->MODULEBUILDER_ASCIIDOCTORPDF.' '.$destfile.' -n -o '.$dirofmoduledoc.'/'.$FILENAMEDOCPDF;
				$outfile = $dirofmoduletmp.'/outpdf.tmp';
				$resarray = $utils->executeCLI($command, $outfile);
				if ($resarray['result'] != '0')
				{
				    $this->error = $resarray['error'].' '.$resarray['output'];
				}
				$result = ($resarray['result'] == 0) ? 1 : 0;

				chdir($currentdir);
			}
			else
			{
				$result = 0;
			}

			if ($result > 0)
			{
				return 1;
			}
			else
			{
				$error++;
				$langs->load("errors");
				$this->error = $langs->trans("ErrorFailToGenerateFile", $outputfiledoc);
			}
		}
		else
		{
			$error++;
			$langs->load("errors");
			$this->error = $langs->trans("ErrorCheckVersionIsDefined");
		}

		return -1;
	}

	/**
	 * This saves syslog files and compresses older ones.
	 * Nb of archive to keep is defined into $conf->global->SYSLOG_FILE_SAVES
	 * CAN BE A CRON TASK
	 *
	 * @return	int						0 if OK, < 0 if KO
	 */
    public function compressSyslogs()
    {
		global $conf;

		if (empty($conf->loghandlers['mod_syslog_file'])) { // File Syslog disabled
			return 0;
		}

		if (!function_exists('gzopen')) {
			$this->error = 'Support for gzopen not available in this PHP';
			return -1;
		}

		dol_include_once('/core/lib/files.lib.php');

		$nbSaves = empty($conf->global->SYSLOG_FILE_SAVES) ? 10 : intval($conf->global->SYSLOG_FILE_SAVES);

		if (empty($conf->global->SYSLOG_FILE)) {
			$mainlogdir = DOL_DATA_ROOT;
			$mainlog = 'dolibarr.log';
		} else {
			$mainlogfull = str_replace('DOL_DATA_ROOT', DOL_DATA_ROOT, $conf->global->SYSLOG_FILE);
			$mainlogdir = dirname($mainlogfull);
			$mainlog = basename($mainlogfull);
		}

		$tabfiles = dol_dir_list(DOL_DATA_ROOT, 'files', 0, '^(dolibarr_.+|odt2pdf)\.log$'); // Also handle other log files like dolibarr_install.log
		$tabfiles[] = array('name' => $mainlog, 'path' => $mainlogdir);

		foreach ($tabfiles as $file) {
			$logname = $file['name'];
			$logpath = $file['path'];

			if (dol_is_file($logpath.'/'.$logname) && dol_filesize($logpath.'/'.$logname) > 0)	// If log file exists and is not empty
			{
				// Handle already compressed files to rename them and add +1

				$filter = '^'.preg_quote($logname, '/').'\.([0-9]+)\.gz$';

				$gzfilestmp = dol_dir_list($logpath, 'files', 0, $filter);
				$gzfiles = array();

				foreach ($gzfilestmp as $gzfile) {
					$tabmatches = array();
					preg_match('/'.$filter.'/i', $gzfile['name'], $tabmatches);

					$numsave = intval($tabmatches[1]);

					$gzfiles[$numsave] = $gzfile;
				}

				krsort($gzfiles, SORT_NUMERIC);

				foreach ($gzfiles as $numsave => $dummy) {
					if (dol_is_file($logpath.'/'.$logname.'.'.($numsave + 1).'.gz')) {
						return -2;
					}

					if ($numsave >= $nbSaves) {
						dol_delete_file($logpath.'/'.$logname.'.'.$numsave.'.gz', 0, 0, 0, null, false, 0);
					} else {
						dol_move($logpath.'/'.$logname.'.'.$numsave.'.gz', $logpath.'/'.$logname.'.'.($numsave + 1).'.gz', 0, 1, 0, 0);
					}
				}

				// Compress current file and recreate it

				if ($nbSaves > 0) {			// If $nbSaves is 1, we keep 1 archive .gz file, If 2, we keep 2 .gz files
					$gzfilehandle = gzopen($logpath.'/'.$logname.'.1.gz', 'wb9');

					if (empty($gzfilehandle)) {
						$this->error = 'Failted to open file '.$logpath.'/'.$logname.'.1.gz';
						return -3;
					}

					$sourcehandle = fopen($logpath.'/'.$logname, 'r');

					if (empty($sourcehandle)) {
						$this->error = 'Failed to open file '.$logpath.'/'.$logname;
						return -4;
					}

					while (!feof($sourcehandle)) {
						gzwrite($gzfilehandle, fread($sourcehandle, 512 * 1024)); // Read 512 kB at a time
					}

					fclose($sourcehandle);
					gzclose($gzfilehandle);

					@chmod($logpath.'/'.$logname.'.1.gz', octdec(empty($conf->global->MAIN_UMASK) ? '0664' : $conf->global->MAIN_UMASK));
				}

				dol_delete_file($logpath.'/'.$logname, 0, 0, 0, null, false, 0);

				// Create empty file
				$newlog = fopen($logpath.'/'.$logname, 'a+');
				fclose($newlog);

				//var_dump($logpath.'/'.$logname." - ".octdec(empty($conf->global->MAIN_UMASK)?'0664':$conf->global->MAIN_UMASK));
				@chmod($logpath.'/'.$logname, octdec(empty($conf->global->MAIN_UMASK) ? '0664' : $conf->global->MAIN_UMASK));
			}
		}

		$this->output = 'Archive log files (keeping last SYSLOG_FILE_SAVES='.$nbSaves.' files) done.';
		return 0;
    }

	/**	Backup the db OR just a table without mysqldump binary, with PHP only (does not require any exec permission)
	 *	Author: David Walsh (http://davidwalsh.name/backup-mysql-database-php)
	 *	Updated and enhanced by Stephen Larroque (lrq3000) and by the many commentators from the blog
	 *	Note about foreign keys constraints: for Dolibarr, since there are a lot of constraints and when imported the tables will be inserted in the dumped order, not in constraints order, then we ABSOLUTELY need to use SET FOREIGN_KEY_CHECKS=0; when importing the sql dump.
	 *	Note2: db2SQL by Howard Yeend can be an alternative, by using SHOW FIELDS FROM and SHOW KEYS FROM we could generate a more precise dump (eg: by getting the type of the field and then precisely outputting the right formatting - in quotes, numeric or null - instead of trying to guess like we are doing now).
	 *
	 *	@param	string	$outputfile		Output file name
	 *	@param	string	$tables			Table name or '*' for all
	 *	@return	int						<0 if KO, >0 if OK
	 */
	public function backupTables($outputfile, $tables = '*')
	{
		global $db, $langs;
		global $errormsg;

		// Set to UTF-8
		if (is_a($db, 'DoliDBMysqli')) {
			/** @var DoliDBMysqli $db */
			$db->db->set_charset('utf8');
		} else {
			/** @var DoliDB $db */
			$db->query('SET NAMES utf8');
			$db->query('SET CHARACTER SET utf8');
		}

		//get all of the tables
		if ($tables == '*')
		{
			$tables = array();
			$result = $db->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'');
			while ($row = $db->fetch_row($result))
			{
				$tables[] = $row[0];
			}
		}
		else
		{
			$tables = is_array($tables) ? $tables : explode(',', $tables);
		}

		//cycle through
		$handle = fopen($outputfile, 'w+');
		if (fwrite($handle, '') === false)
		{
			$langs->load("errors");
			dol_syslog("Failed to open file ".$outputfile, LOG_ERR);
			$errormsg = $langs->trans("ErrorFailedToWriteInDir");
			return -1;
		}

		// Print headers and global mysql config vars
		$sqlhead = '';
		$sqlhead .= "-- ".$db::LABEL." dump via php with Dolibarr ".DOL_VERSION."
--
-- Host: ".$db->db->host_info."    Database: ".$db->database_name."
-- ------------------------------------------------------
-- Server version	".$db->db->server_info."

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

";

		if (GETPOST("nobin_disable_fk")) $sqlhead .= "SET FOREIGN_KEY_CHECKS=0;\n";
		//$sqlhead .= "SET SQL_MODE=\"NO_AUTO_VALUE_ON_ZERO\";\n";
		if (GETPOST("nobin_use_transaction")) $sqlhead .= "SET AUTOCOMMIT=0;\nSTART TRANSACTION;\n";

		fwrite($handle, $sqlhead);

		$ignore = '';
		if (GETPOST("nobin_sql_ignore")) $ignore = 'IGNORE ';
		$delayed = '';
		if (GETPOST("nobin_delayed")) $delayed = 'DELAYED ';

		// Process each table and print their definition + their datas
		foreach ($tables as $table)
		{
			// Saving the table structure
			fwrite($handle, "\n--\n-- Table structure for table `".$table."`\n--\n");

			if (GETPOST("nobin_drop")) fwrite($handle, "DROP TABLE IF EXISTS `".$table."`;\n"); // Dropping table if exists prior to re create it
			fwrite($handle, "/*!40101 SET @saved_cs_client     = @@character_set_client */;\n");
			fwrite($handle, "/*!40101 SET character_set_client = utf8 */;\n");
			$resqldrop = $db->query('SHOW CREATE TABLE '.$table);
			$row2 = $db->fetch_row($resqldrop);
			if (empty($row2[1]))
			{
				fwrite($handle, "\n-- WARNING: Show create table ".$table." return empy string when it should not.\n");
			}
			else
			{
				fwrite($handle, $row2[1].";\n");
				//fwrite($handle,"/*!40101 SET character_set_client = @saved_cs_client */;\n\n");

				// Dumping the data (locking the table and disabling the keys check while doing the process)
				fwrite($handle, "\n--\n-- Dumping data for table `".$table."`\n--\n");
				if (!GETPOST("nobin_nolocks")) fwrite($handle, "LOCK TABLES `".$table."` WRITE;\n"); // Lock the table before inserting data (when the data will be imported back)
				if (GETPOST("nobin_disable_fk")) fwrite($handle, "ALTER TABLE `".$table."` DISABLE KEYS;\n");
				else fwrite($handle, "/*!40000 ALTER TABLE `".$table."` DISABLE KEYS */;\n");

				$sql = 'SELECT * FROM '.$table; // Here SELECT * is allowed because we don't have definition of columns to take
				$result = $db->query($sql);
				while ($row = $db->fetch_row($result))
				{
					// For each row of data we print a line of INSERT
					fwrite($handle, 'INSERT '.$delayed.$ignore.'INTO `'.$table.'` VALUES (');
					$columns = count($row);
					for ($j = 0; $j < $columns; $j++) {
						// Processing each columns of the row to ensure that we correctly save the value (eg: add quotes for string - in fact we add quotes for everything, it's easier)
						if ($row[$j] == null && !is_string($row[$j])) {
							// IMPORTANT: if the field is NULL we set it NULL
							$row[$j] = 'NULL';
						} elseif (is_string($row[$j]) && $row[$j] == '') {
							// if it's an empty string, we set it as an empty string
							$row[$j] = "''";
						} elseif (is_numeric($row[$j]) && !strcmp($row[$j], $row[$j] + 0)) { // test if it's a numeric type and the numeric version ($nb+0) == string version (eg: if we have 01, it's probably not a number but rather a string, else it would not have any leading 0)
							// if it's a number, we return it as-is
							//	                    $row[$j] = $row[$j];
						} else { // else for all other cases we escape the value and put quotes around
							$row[$j] = addslashes($row[$j]);
							$row[$j] = preg_replace("#\n#", "\\n", $row[$j]);
							$row[$j] = "'".$row[$j]."'";
						}
					}
					fwrite($handle, implode(',', $row).");\n");
				}
				if (GETPOST("nobin_disable_fk")) fwrite($handle, "ALTER TABLE `".$table."` ENABLE KEYS;\n"); // Enabling back the keys/index checking
				if (!GETPOST("nobin_nolocks")) fwrite($handle, "UNLOCK TABLES;\n"); // Unlocking the table
				fwrite($handle, "\n\n\n");
			}
		}

		/* Backup Procedure structure*/
		/*
		 $result = $db->query('SHOW PROCEDURE STATUS');
		 if ($db->num_rows($result) > 0)
		 {
		 while ($row = $db->fetch_row($result)) { $procedures[] = $row[1]; }
		 foreach($procedures as $proc)
		 {
		 fwrite($handle,"DELIMITER $$\n\n");
		 fwrite($handle,"DROP PROCEDURE IF EXISTS '$name'.'$proc'$$\n");
		 $resqlcreateproc=$db->query("SHOW CREATE PROCEDURE '$proc'");
		 $row2 = $db->fetch_row($resqlcreateproc);
		 fwrite($handle,"\n".$row2[2]."$$\n\n");
		 fwrite($handle,"DELIMITER ;\n\n");
		 }
		 }
		 */
		/* Backup Procedure structure*/

		// Write the footer (restore the previous database settings)
		$sqlfooter = "\n\n";
		if (GETPOST("nobin_use_transaction")) $sqlfooter .= "COMMIT;\n";
		if (GETPOST("nobin_disable_fk")) $sqlfooter .= "SET FOREIGN_KEY_CHECKS=1;\n";
		$sqlfooter .= "\n\n-- Dump completed on ".date('Y-m-d G-i-s');
		fwrite($handle, $sqlfooter);

		fclose($handle);

		return 1;
	}
}
