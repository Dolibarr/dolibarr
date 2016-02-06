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
	 *
	 *  @param	string		$choice		Choice of purge mode ('tempfiles', 'tempfilesold' to purge temp older than 24h, 'allfiles', 'logfiles')
	 *  @return	int						0 if OK, < 0 if KO (this function is used also by cron so only 0 is OK) 
	 */
	function purgeFiles($choice='tempfilesold')
	{
		global $conf, $langs, $dolibarr_main_data_root;
		
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
}
