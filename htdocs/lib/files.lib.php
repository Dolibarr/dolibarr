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
   \file		htdocs/lib/files.lib.php
   \brief		Library for file managing functions
   \version		$Id$
*/

/**
   \brief		Scan a directory and return a list of files/directories
   \param		$path        	Starting path from which to search
   \param		$types        	Can be "directories", "files", or "all"
   \param		$recursive		Determines whether subdirectories are searched
   \param		$filter        	Regex for filter
   \param		$exludefilter  	Regex for exclude filter (example: '\.meta$')
   \param		$sortcriteria	Sort criteria ("name","date","size")
   \param		$sortorder		Sort order (SORT_ASC, SORT_DESC)
	\param		$mode			0=Return array of key need, 1=Force all key to be loaded
   \return		array			Array of array('name'=>xxx,'date'=>yyy,'size'=>zzz)
 */
function dol_dir_list($path, $types="all", $recursive=0, $filter="", $excludefilter="", $sortcriteria="name", $sortorder=SORT_ASC, $mode=0)
{
	dolibarr_syslog("files.lib.php::dol_dir_list $path");

	$loaddate=$mode?true:false;
	$loadsize=$mode?true:false;
	
	// Clean parameters
	$path=eregi_replace('[\\/]+$','',$path);
	
	if (! is_dir($path)) return array();

	if ($dir = opendir($path))
	{
		$file_list = array();
		while (false !== ($file = readdir($dir)))
		{
			$qualified=1;
			
			// Check if file is qualified
			if (eregi('^\.',$file)) $qualified=0;
			if ($excludefilter && eregi($excludefilter,$file)) $qualified=0;
			
			if ($qualified)
			{
				// Check whether this is a file or directory and whether we're interested in that type
				if (is_dir($path."/".$file) && (($types=="directories") || ($types=="all")))
				{
					// Add entry into file_list array
					if ($loaddate || $sortcriteria == 'date') $filedate=filemtime($path."/".$file);
					if ($loadsize || $sortcriteria == 'size') $filesize=filesize($path."/".$file);
					
					if (! $filter || eregi($filter,$path.'/'.$file))
					{
						$file_list[] = array(
						"name" => $file,
						"fullname" => $path.'/'.$file,
						"date" => $filedate,
						"size" => $filesize
						);
					}
					
					// if we're in a directory and we want recursive behavior, call this function again
					if ($recursive)
					{
						$file_list = array_merge($file_list, dol_dir_list($path."/".$file."/", $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder));
					}
				}
				else if (! is_dir($path."/".$file) && (($types == "files") || ($types == "all")))
				{
					// Add file into file_list array
					if ($loaddate || $sortcriteria == 'date') $filedate=filemtime($path."/".$file);
					if ($loadsize || $sortcriteria == 'size') $filesize=filesize($path."/".$file);
					if (! $filter || eregi($filter,$path.'/'.$file))
					{
						$file_list[] = array(
						"name" => $file,
						"fullname" => $path.'/'.$file,
						"date" => $filedate,
						"size" => $filesize
						);
					}
				}
			}
		}
		closedir($dir);
		
		// Obtain a list of columns
		$myarray=array();
		foreach ($file_list as $key => $row)
		{
			$myarray[$key]  = $row[$sortcriteria];
		}
		// Sort the data
		array_multisort($myarray, $sortorder, $file_list);
		
		return $file_list;
	}
	else
	{
		return false;
	}
}

/**
 * \brief	Compare 2 files
 * 
 * @param 	unknown_type $a		File 1
 * @param 	unknown_type $b		File 2
 * @return 	int					1, 0, 1
 */
function dol_compare_file($a, $b)
{
	global $sortorder;
	global $sortfield;
	
	$sortorder=strtoupper($sortorder);
	
	if ($sortorder == 'ASC') { $retup=-1; $retdown=1; }
	else { $retup=1; $retdown=-1; }
	
	if ($sortfield == 'name')
	{
		if ($a->name == $b->name) return 0;
		return ($a->name < $b->name) ? $retup : $retdown;
	}
	if ($sortfield == 'date')
	{
		if ($a->date == $b->date) return 0;
		return ($a->date < $b->date) ? $retup : $retdown;
	}
	if ($sortfield == 'size')
	{
		if ($a->size == $b->size) return 0;
		return ($a->size < $b->size) ? $retup : $retdown;
	}
}

?>
