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
 *  \file		htdocs/lib/files.lib.php
 *  \brief		Library for file managing functions
 *  \version	$Id$
 */

/**
 *  \brief		Scan a directory and return a list of files/directories. Content for string is UTF8.
 *  \param		$path        	Starting path from which to search
 *  \param		$types        	Can be "directories", "files", or "all"
 *  \param		$recursive		Determines whether subdirectories are searched
 *  \param		$filter        	Regex for include filter
 *  \param		$exludefilter  	Regex for exclude filter (example: '\.meta$')
 *  \param		$sortcriteria	Sort criteria ("name","date","size")
 *  \param		$sortorder		Sort order (SORT_ASC, SORT_DESC)
 *	\param		$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower)
 *  \return		array			Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file')
 */
function dol_dir_list($path, $types="all", $recursive=0, $filter="", $excludefilter="", $sortcriteria="name", $sortorder=SORT_ASC, $mode=0)
{
	dol_syslog("files.lib.php::dol_dir_list path=".$path." types=".$types." recursive=".$recursive." filter=".$filter." excludefilter=".$excludefilter);

	$loaddate=$mode?true:false;
	$loadsize=$mode?true:false;

	// Clean parameters
	$path=preg_replace('/([\\/]+)$/i','',$path);
	$newpath=dol_osencode($path);

	if (! is_dir($newpath)) return array();

	if ($dir = opendir($newpath))
	{
		$file_list = array();
		while (false !== ($file = readdir($dir)))
		{
			if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure data is stored in utf8 in memory

			$qualified=1;

			// Check if file is qualified
			if (preg_match('/^\./',$file)) $qualified=0;
			if ($excludefilter && preg_match('/'.$excludefilter.'/i',$file)) $qualified=0;

			if ($qualified)
			{
				// Check whether this is a file or directory and whether we're interested in that type
				if (is_dir(dol_osencode($path."/".$file)) && (($types=="directories") || ($types=="all")))
				{
					// Add entry into file_list array
					if ($loaddate || $sortcriteria == 'date') $filedate=dol_filemtime($path."/".$file);
					if ($loadsize || $sortcriteria == 'size') $filesize=dol_filesize($path."/".$file);

					if (! $filter || preg_match('/'.$filter.'/i',$path.'/'.$file))
					{
						$file_list[] = array(
						"name" => $file,
						"fullname" => $path.'/'.$file,
						"date" => $filedate,
						"size" => $filesize,
						"type" => 'dir'
						);
					}

					// if we're in a directory and we want recursive behavior, call this function again
					if ($recursive)
					{
						$file_list = array_merge($file_list,dol_dir_list($path."/".$file."/", $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder));
					}
				}
				else if (! is_dir(dol_osencode($path."/".$file)) && (($types == "files") || ($types == "all")))
				{
					// Add file into file_list array
					if ($loaddate || $sortcriteria == 'date') $filedate=dol_filemtime($path."/".$file);
					if ($loadsize || $sortcriteria == 'size') $filesize=dol_filesize($path."/".$file);
					if (! $filter || preg_match('/'.$filter.'/i',$path.'/'.$file))
					{
						$file_list[] = array(
						"name" => $file,
						"fullname" => $path.'/'.$file,
						"date" => $filedate,
						"size" => $filesize,
						"type" => 'file'
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
		return array();
	}
}

/**
 * \brief	Fast compare of 2 files identified by their properties ->name, ->date and ->size
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

/**
 *	\brief      Return mime type of a file
 *	\param      file		Filename
 *	\return     string     	Return mime type
 */
function dol_mimetype($file)
{
	$mime='application/octet-stream';
	// Text files
	if (preg_match('/\.txt$/i',$file))						$mime='text/plain';
	if (preg_match('/\.csv$/i',$file))						$mime='text/csv';
	if (preg_match('/\.tsv$/i',$file))						$mime='text/tab-separated-values';
	// MS office
	if (preg_match('/\.mdb$/i',$file))						$mime='application/msaccess';
	if (preg_match('/\.doc(x|m)?$/i',$file))				$mime='application/msword';
	if (preg_match('/\.dot(x|m)?$/i',$file))				$mime='application/msword';
	if (preg_match('/\.xls(b|m|x)?$/i',$file))				$mime='application/vnd.ms-excel';
	if (preg_match('/\.xlt(x)?$/i',$file))					$mime='application/vnd.ms-excel';
	if (preg_match('/\.xla(m)?$/i',$file))					$mime='application/vnd.ms-excel';
	if (preg_match('/\.pps(m|x)?$/i',$file))				$mime='application/vnd.ms-powerpoint';
	if (preg_match('/\.ppt(m|x)?$/i',$file))				$mime='application/x-mspowerpoint';
	// Open office
	if (preg_match('/\.odp$/i',$file))						$mime='application/vnd.oasis.opendocument.presentation';
	if (preg_match('/\.ods$/i',$file))						$mime='application/vnd.oasis.opendocument.spreadsheet';
	if (preg_match('/\.odt$/i',$file))						$mime='application/vnd.oasis.opendocument.text';
	// Mix
	if (preg_match('/\.(html|htm)$/i',$file))				$mime='text/html';
	if (preg_match('/\.pdf$/i',$file))						$mime='application/pdf';
	if (preg_match('/\.sql$/i',$file))						$mime='text/plain';
	if (preg_match('/\.(sh|ksh|bash)$/i',$file))			$mime='text/plain';
	// Images
	if (preg_match('/\.jpg$/i',$file))						$mime='image/jpeg';
	if (preg_match('/\.jpeg$/i',$file))						$mime='image/jpeg';
	if (preg_match('/\.png$/i',$file))						$mime='image/png';
	if (preg_match('/\.gif$/i',$file))						$mime='image/gif';
	if (preg_match('/\.bmp$/i',$file))						$mime='image/bmp';
	if (preg_match('/\.tiff$/i',$file))						$mime='image/tiff';
	// Calendar
	if (preg_match('/\.vcs$/i',$file))						$mime='text/calendar';
	if (preg_match('/\.ics$/i',$file))						$mime='text/calendar';
	// Other
	if (preg_match('/\.torrent$/i',$file))					$mime='application/x-bittorrent';
	// Audio
	if (preg_match('/\.(mp3|ogg|au|wav|wma|mid)$/i',$file))			$mime='audio';
	// Video
	if (preg_match('/\.(avi|divx|xvid|wmv|mpg|mpeg)$/i',$file))		$mime='video';
	// Archive
	if (preg_match('/\.(zip|rar|gz|tgz|z|cab|bz2|7z)$/i',$file))	$mime='archive';
	return $mime;
}


/**
 * 	\brief		Test if a folder is empty
 * 	\param		folder		Name of folder
 * 	\return 	boolean		True if dir is empty or non-existing, False if it contains files
 */
function dol_dir_is_emtpy($folder)
{
	$newfolder=dol_osencode($folder);
	if (is_dir($newfolder))
	{
		$handle = opendir($newfolder);
		while ((gettype( $name = readdir($handle)) != "boolean"))
		{
			$name_array[] = $name;
		}
		foreach($name_array as $temp) $folder_content .= $temp;

		if ($folder_content == "...") return true;
		else return false;

		closedir($handle);
	}
	else
	return true; // Dir does not exists
}

/**
 * 	\brief		Count number of lines in a file
 * 	\param		file		Filename
 * 	\return 	int			<0 if KO, Number of lines in files if OK
 */
function dol_count_nb_of_line($file)
{
	$nb=0;

	$newfile=dol_osencode($file);
	//print 'x'.$file;
	$fp=fopen($newfile,'r');
	if ($fp)
	{
		while (!feof($fp))
		{
			$line=fgets($fp);
			$nb++;
		}
		fclose($fp);
	}
	else
	{
		$nb=-1;
	}

	return $nb;
}


/**
 * Return size of a file
 *
 * @param 	$pathoffile
 * @return 	string		File size
 */
function dol_filesize($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return filesize($newpathoffile);
}

/**
 * Return time of a file
 *
 * @param 	$pathoffile
 * @return 	timestamp	Time of file
 */
function dol_filemtime($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return filemtime($newpathoffile);
}

/**
 * Return if path is a file
 *
 * @param 	$pathoffile
 * @return 	boolean			True or false
 */
function dol_is_file($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return is_file($newpathoffile);
}

/**
 * Copy a file to another file
 * @param	$srcfile			Source file
 * @param	$destfile			Destination file
 * @param	$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK)
 * @param 	$overwriteifexists	Overwrite file if exists (1 by default)
 * @return	boolean				True if OK, false if KO
 */
function dol_copy($srcfile, $destfile, $newmask=0, $overwriteifexists=1)
{
	global $conf;
	$result=false;

	dol_syslog("files.lib.php::dol_copy srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask);
	if ($overwriteifexists || ! dol_is_file($destfile))
	{
		$result=@copy($srcfile, $destfile);

		if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
		@chmod($file, octdec($newmask));
	}

	return $result;
}

?>
