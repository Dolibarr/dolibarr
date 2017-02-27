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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
	var $db;
	
	var $output;   // Used by Cron method to return message
	var $result;   // Used by Cron method to return data
	
	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *  Purge files into directory of data files.
	 *  CAN BE A CRON TASK
	 *
	 *  @param	string		$choice		Choice of purge mode ('tempfiles', 'tempfilesold' to purge temp older than 24h, 'allfiles', 'logfiles')
	 *  @return	int						0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK) 
	 */
	function purgeFiles($choice='tempfilesold')
	{
		global $conf, $langs, $dolibarr_main_data_root;
		
		$langs->load("admin");
		
		dol_syslog("Utils::purgeFiles choice=".$choice, LOG_DEBUG);
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		
		$filesarray=array();
		if (empty($choice)) $choice='tempfilesold';
		
		if ($choice=='tempfiles' || $choice=='tempfilesold')
		{
			// Delete temporary files
			if ($dolibarr_main_data_root)
			{
				$filesarray=dol_dir_list($dolibarr_main_data_root,"directories",1,'^temp$','','','',2);
				if ($choice == 'tempfilesold')
				{
					$now = dol_now();
					foreach($filesarray as $key => $val)
					{
						if ($val['date'] > ($now - (24 * 3600))) unset($filesarray[$key]);	// Discard files not older than 24h
					}
				}
			}
		}
	
		if ($choice=='allfiles')
		{
			// Delete all files
			if ($dolibarr_main_data_root)
			{
				$filesarray=dol_dir_list($dolibarr_main_data_root,"all",0,'','install\.lock$');
			}
		}
	
		if ($choice=='logfile')
		{
			// Define filelog to discard it from purge
			$filelog='';
			if (! empty($conf->syslog->enabled))
			{
				$filelog=SYSLOG_FILE;
				$filelog=preg_replace('/DOL_DATA_ROOT/i',DOL_DATA_ROOT,$filelog);
			}

			$filesarray[]=array('fullname'=>$filelog,'type'=>'file');
		}
	
		$count=0;
		if (count($filesarray))
		{
			foreach($filesarray as $key => $value)
			{
				//print "x ".$filesarray[$key]['fullname']."<br>\n";
				if ($filesarray[$key]['type'] == 'dir')
				{
					$count+=dol_delete_dir_recursive($filesarray[$key]['fullname']);
				}
				elseif ($filesarray[$key]['type'] == 'file')
				{
					// If (file that is not logfile) or (if logfile with option logfile)
					if ($filesarray[$key]['fullname'] != $filelog || $choice=='logfile')
					{
						$count+=(dol_delete_file($filesarray[$key]['fullname'])?1:0);
					}
				}
			}
	
			// Update cachenbofdoc
			if (! empty($conf->ecm->enabled) && $choice=='allfiles')
			{
				require_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmdirectory.class.php';
				$ecmdirstatic = new EcmDirectory($this->db);
				$result = $ecmdirstatic->refreshcachenboffile(1);
			}
		}
		
		if ($count > 0) $this->output=$langs->trans("PurgeNDirectoriesDeleted", $count);
		else $this->output=$langs->trans("PurgeNothingToDelete");

		//return $count;
		return 0;     // This function can be called by cron so must return 0 if OK
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
	 *  @return	int						       0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK) 
	 */
	function dumpDatabase($compression='none', $type='auto', $usedefault=1, $file='auto', $keeplastnfiles=0)
	{
		global $db, $conf, $langs, $dolibarr_main_data_root;
		global $dolibarr_main_db_name, $dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_port, $dolibarr_main_db_pass;
		
		$langs->load("admin");
		
		dol_syslog("Utils::dumpDatabase type=".$type." compression=".$compression." file=".$file, LOG_DEBUG);
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		// Check compression parameter
		if (! in_array($compression, array('none', 'gz', 'bz', 'zip')))
		{
		    $langs->load("errors");
		    $this->error=$langs->transnoentitiesnoconv("ErrorBadValueForParameter", $compression, "Compression");
		    return -1;
		}

		// Check type parameter
		if ($type == 'auto') $type = $db->type;
		if (! in_array($type, array('pgsql', 'mysql', 'mysqli','mysqlnobin')))
		{
		    $langs->load("errors");
		    $this->error=$langs->transnoentitiesnoconv("ErrorBadValueForParameter", $type, "Basetype");
		    return -1;
		}

		// Check file parameter
		if ($file == 'auto')
		{
		    $prefix='dump';
		    $ext='.sql';
		    if (in_array($type, array('mysql', 'mysqli')))  { $prefix='mysqldump'; $ext='sql'; }
		    //if ($label == 'PostgreSQL') { $prefix='pg_dump'; $ext='dump'; }
		    if (in_array($type, array('pgsql'))) { $prefix='pg_dump'; $ext='sql'; }
		    $file=$prefix.'_'.$dolibarr_main_db_name.'_'.dol_sanitizeFileName(DOL_VERSION).'_'.strftime("%Y%m%d%H%M").'.'.$ext;
		}

		$outputdir  = $conf->admin->dir_output.'/backup';
		$result=dol_mkdir($outputdir);
		

		// MYSQL
		if ($type == 'mysql' || $type == 'mysqli')
		{
		    $cmddump=$conf->global->SYSTEMTOOLS_MYSQLDUMP;
		
		
		    $outputfile = $outputdir.'/'.$file;
		    // for compression format, we add extension
		    $compression=$compression ? $compression : 'none';
		    if ($compression == 'gz') $outputfile.='.gz';
		    if ($compression == 'bz') $outputfile.='.bz2';
		    $outputerror = $outputfile.'.err';
		    dol_mkdir($conf->admin->dir_output.'/backup');
		
		    // Parameteres execution
		    $command=$cmddump;
		    if (preg_match("/\s/",$command)) $command=escapeshellarg($command);	// Use quotes on command
		
		    //$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
		    $param=$dolibarr_main_db_name." -h ".$dolibarr_main_db_host;
		    $param.=" -u ".$dolibarr_main_db_user;
		    if (! empty($dolibarr_main_db_port)) $param.=" -P ".$dolibarr_main_db_port;
		    if (! GETPOST("use_transaction"))    $param.=" -l --single-transaction";
		    if (GETPOST("disable_fk") || $usedefault) $param.=" -K";
		    if (GETPOST("sql_compat") && GETPOST("sql_compat") != 'NONE') $param.=" --compatible=".escapeshellarg(GETPOST("sql_compat","alpha"));
		    if (GETPOST("drop_database"))        $param.=" --add-drop-database";
		    if (GETPOST("sql_structure") || $usedefault)
		    {
		        if (GETPOST("drop") || $usedefault)	$param.=" --add-drop-table=TRUE";
		        else 							    $param.=" --add-drop-table=FALSE";
		    }
		    else
		    {
		        $param.=" -t";
		    }
		    if (GETPOST("disable-add-locks")) $param.=" --add-locks=FALSE";
		    if (GETPOST("sql_data") || $usedefault)
		    {
		        $param.=" --tables";
		        if (GETPOST("showcolumns") || $usedefault)	 $param.=" -c";
		        if (GETPOST("extended_ins") || $usedefault) $param.=" -e";
		        else $param.=" --skip-extended-insert";
		        if (GETPOST("delayed"))	 	 $param.=" --delayed-insert";
		        if (GETPOST("sql_ignore"))	 $param.=" --insert-ignore";
		        if (GETPOST("hexforbinary") || $usedefault) $param.=" --hex-blob";
		    }
		    else
		    {
		        $param.=" -d";    // No row information (no data)
		    }
		    $param.=" --default-character-set=utf8";    // We always save output into utf8 charset
		    $paramcrypted=$param;
		    $paramclear=$param;
		    if (! empty($dolibarr_main_db_pass))
		    {
		        $paramcrypted.=' -p"'.preg_replace('/./i','*',$dolibarr_main_db_pass).'"';
		        $paramclear.=' -p"'.str_replace(array('"','`'),array('\"','\`'),$dolibarr_main_db_pass).'"';
		    }
		
		    $errormsg='';
		
		    // Debut appel methode execution
		    $fullcommandcrypted=$command." ".$paramcrypted." 2>&1";
		    $fullcommandclear=$command." ".$paramclear." 2>&1";
		    if ($compression == 'none') $handle = fopen($outputfile, 'w');
		    if ($compression == 'gz')   $handle = gzopen($outputfile, 'w');
		    if ($compression == 'bz')   $handle = bzopen($outputfile, 'w');
		
		    if ($handle)
		    {
		        $ok=0;
		        dol_syslog("Run command ".$fullcommandcrypted);
		        $handlein = popen($fullcommandclear, 'r');
		        $i=0;
		        while (!feof($handlein))
		        {
		            $i++;   // output line number
		            $read = fgets($handlein);
		            // Exclude warning line we don't want
		            if ($i == 1 && preg_match('/Warning.*Using a password/i', $read)) continue;
		            fwrite($handle,$read);
		            if (preg_match('/'.preg_quote('-- Dump completed').'/i',$read)) $ok=1;
		            elseif (preg_match('/'.preg_quote('SET SQL_NOTES=@OLD_SQL_NOTES').'/i',$read)) $ok=1;
		        }
		        pclose($handlein);
		
		        if ($compression == 'none') fclose($handle);
		        if ($compression == 'gz')   gzclose($handle);
		        if ($compression == 'bz')   bzclose($handle);
		
		        if (! empty($conf->global->MAIN_UMASK))
		            @chmod($outputfile, octdec($conf->global->MAIN_UMASK));
		    }
		    else
		    {
		        $langs->load("errors");
		        dol_syslog("Failed to open file ".$outputfile,LOG_ERR);
		        $errormsg=$langs->trans("ErrorFailedToWriteInDir");
		    }
		
		    // Get errorstring
		    if ($compression == 'none') $handle = fopen($outputfile, 'r');
		    if ($compression == 'gz')   $handle = gzopen($outputfile, 'r');
		    if ($compression == 'bz')   $handle = bzopen($outputfile, 'r');
		    if ($handle)
		    {
		        // Get 2048 first chars of error message.
		        $errormsg = fgets($handle,2048);
		        // Close file
		        if ($compression == 'none') fclose($handle);
		        if ($compression == 'gz')   gzclose($handle);
		        if ($compression == 'bz')   bzclose($handle);
		        if ($ok && preg_match('/^-- MySql/i',$errormsg)) $errormsg='';	// Pas erreur
		        else
		        {
		            // Renommer fichier sortie en fichier erreur
		            //print "$outputfile -> $outputerror";
		            @dol_delete_file($outputerror,1);
		            @rename($outputfile,$outputerror);
		            // Si safe_mode on et command hors du parametre exec, on a un fichier out vide donc errormsg vide
		            if (! $errormsg)
		            {
		                $langs->load("errors");
		                $errormsg=$langs->trans("ErrorFailedToRunExternalCommand");
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
		    $compression=$compression ? $compression : 'none';
		    if ($compression == 'gz') $outputfile.='.gz';
		    if ($compression == 'bz') $outputfile.='.bz2';
		    $outputerror = $outputfile.'.err';
		    dol_mkdir($conf->admin->dir_output.'/backup');
		
		    if ($compression == 'gz' or $compression == 'bz')
		    {
		        backup_tables($outputfiletemp);
		        dol_compress_file($outputfiletemp, $outputfile, $compression);
		        unlink($outputfiletemp);
		    }
		    else
		    {
		        backup_tables($outputfile);
		    }
		
		    $this->output = "";
		    $this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => "");
		}
		
		// POSTGRESQL
		if ($type == 'postgresql')
		{
		    $cmddump=$conf->global->SYSTEMTOOLS_POSTGRESQLDUMP;
		    
		    $outputfile = $outputdir.'/'.$file;
		    // for compression format, we add extension
		    $compression=$compression ? $compression : 'none';
		    if ($compression == 'gz') $outputfile.='.gz';
		    if ($compression == 'bz') $outputfile.='.bz2';
		    $outputerror = $outputfile.'.err';
		    dol_mkdir($conf->admin->dir_output.'/backup');
		
		    // Parameteres execution
		    $command=$cmddump;
		    if (preg_match("/\s/",$command)) $command=escapeshellarg($command);	// Use quotes on command
		
		    //$param=escapeshellarg($dolibarr_main_db_name)." -h ".escapeshellarg($dolibarr_main_db_host)." -u ".escapeshellarg($dolibarr_main_db_user)." -p".escapeshellarg($dolibarr_main_db_pass);
		    //$param="-F c";
		    $param="-F p";
		    $param.=" --no-tablespaces --inserts -h ".$dolibarr_main_db_host;
		    $param.=" -U ".$dolibarr_main_db_user;
		    if (! empty($dolibarr_main_db_port)) $param.=" -p ".$dolibarr_main_db_port;
		    if (GETPOST("sql_compat") && GETPOST("sql_compat") == 'ANSI') $param.="  --disable-dollar-quoting";
		    if (GETPOST("drop_database"))        $param.=" -c -C";
		    if (GETPOST("sql_structure"))
		    {
		        if (GETPOST("drop"))			 $param.=" --add-drop-table";
		        if (! GETPOST("sql_data"))       $param.=" -s";
		    }
		    if (GETPOST("sql_data"))
		    {
		        if (! GETPOST("sql_structure"))	 $param.=" -a";
		        if (GETPOST("showcolumns"))	     $param.=" -c";
		    }
		    $param.=' -f "'.$outputfile.'"';
		    //if ($compression == 'none')
		    if ($compression == 'gz')   $param.=' -Z 9';
		    //if ($compression == 'bz')
		    $paramcrypted=$param;
		    $paramclear=$param;
		    /*if (! empty($dolibarr_main_db_pass))
		     {
		     $paramcrypted.=" -W".preg_replace('/./i','*',$dolibarr_main_db_pass);
		     $paramclear.=" -W".$dolibarr_main_db_pass;
		     }*/
		    $paramcrypted.=" -w ".$dolibarr_main_db_name;
		    $paramclear.=" -w ".$dolibarr_main_db_name;

		    $this->output = "";
		    $this->result = array("commandbackuplastdone" => "", "commandbackuptorun" => $command." ".$paramcrypted);
		}
		
		
		return 0;
	}
}
