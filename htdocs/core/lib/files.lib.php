<?php
/* Copyright (C) 2008-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2015  Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2012-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/files.lib.php
 *  \brief		Library for file managing functions
 */

/**
 * Make a basename working with all page code (default PHP basenamed fails with cyrillic).
 * We supose dir separator for input is '/'.
 *
 * @param	string	$pathfile	String to find basename.
 * @return	string				Basename of input
 */
function dol_basename($pathfile)
{
	return preg_replace('/^.*\/([^\/]+)$/','$1',rtrim($pathfile,'/'));
}

/**
 *  Scan a directory and return a list of files/directories.
 *  Content for string is UTF8 and dir separator is "/".
 *
 *  @param	string		$path        	Starting path from which to search. This is a full path.
 *  @param	string		$types        	Can be "directories", "files", or "all"
 *  @param	int			$recursive		Determines whether subdirectories are searched
 *  @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/' by doing preg_quote($var,'/'), since this char is used for preg_match function,
 *                                      but must not contains the start and end '/'. Filter is checked into basename only.
 *  @param	array		$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). Exclude is checked both into fullpath and into basename (So '^xxx' may exclude 'xxx/dirscanned/...' and dirscanned/xxx').
 *  @param	string		$sortcriteria	Sort criteria ('','fullname','relativename','name','date','size')
 *  @param	string		$sortorder		Sort order (SORT_ASC, SORT_DESC)
 *	@param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
 *  @param	int			$nohook			Disable all hooks
 *  @param	string		$relativename	For recursive purpose only. Must be "" at first call.
 *  @param	string		$donotfollowsymlinks	Do not follow symbolic links
 *  @return	array						Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)
 *  @see dol_dir_list_indatabase
 */
function dol_dir_list($path, $types="all", $recursive=0, $filter="", $excludefilter=null, $sortcriteria="name", $sortorder=SORT_ASC, $mode=0, $nohook=0, $relativename="", $donotfollowsymlinks=0)
{
	global $db, $hookmanager;
	global $object;

	dol_syslog("files.lib.php::dol_dir_list path=".$path." types=".$types." recursive=".$recursive." filter=".$filter." excludefilter=".json_encode($excludefilter));
	//print 'xxx'."files.lib.php::dol_dir_list path=".$path." types=".$types." recursive=".$recursive." filter=".$filter." excludefilter=".json_encode($excludefilter);

	$loaddate=($mode==1||$mode==2)?true:false;
	$loadsize=($mode==1||$mode==3)?true:false;

	// Clean parameters
	$path=preg_replace('/([\\/]+)$/i','',$path);
	$newpath=dol_osencode($path);

	$reshook = 0;
	$file_list = array();

	if (is_object($hookmanager) && ! $nohook)
	{
		$hookmanager->resArray=array();

		$hookmanager->initHooks(array('fileslib'));

		$parameters=array(
				'path' => $newpath,
				'types'=> $types,
				'recursive' => $recursive,
				'filter' => $filter,
				'excludefilter' => $excludefilter,
				'sortcriteria' => $sortcriteria,
				'sortorder' => $sortorder,
				'loaddate' => $loaddate,
				'loadsize' => $loadsize,
				'mode' => $mode
		);
		$reshook=$hookmanager->executeHooks('getDirList', $parameters, $object);
	}

	// $hookmanager->resArray may contain array stacked by other modules
	if (empty($reshook))
	{
		if (! is_dir($newpath)) return array();

		if ($dir = opendir($newpath))
		{
			$filedate='';
			$filesize='';

			while (false !== ($file = readdir($dir)))        // $file is always a basename (into directory $newpath)
			{
				if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure data is stored in utf8 in memory
				$fullpathfile=($newpath?$newpath.'/':'').$file;

				$qualified=1;

				// Define excludefilterarray
				$excludefilterarray=array('^\.');
				if (is_array($excludefilter))
				{
					$excludefilterarray=array_merge($excludefilterarray,$excludefilter);
				}
				else if ($excludefilter) $excludefilterarray[]=$excludefilter;
				// Check if file is qualified
				foreach($excludefilterarray as $filt)
				{
					if (preg_match('/'.$filt.'/i', $file) || preg_match('/'.$filt.'/i', $fullpathfile)) {
						$qualified=0; break;
					}
				}
				//print $fullpathfile.' '.$file.' '.$qualified.'<br>';

				if ($qualified)
				{
					$isdir=is_dir(dol_osencode($path."/".$file));
					// Check whether this is a file or directory and whether we're interested in that type
					if ($isdir && (($types=="directories") || ($types=="all") || $recursive))
					{
						// Add entry into file_list array
						if (($types=="directories") || ($types=="all"))
						{
							if ($loaddate || $sortcriteria == 'date') $filedate=dol_filemtime($path."/".$file);
							if ($loadsize || $sortcriteria == 'size') $filesize=dol_filesize($path."/".$file);

							if (! $filter || preg_match('/'.$filter.'/i',$file))	// We do not search key $filter into all $path, only into $file part
							{
								preg_match('/([^\/]+)\/[^\/]+$/',$path.'/'.$file,$reg);
								$level1name=(isset($reg[1])?$reg[1]:'');
								$file_list[] = array(
										"name" => $file,
										"path" => $path,
										"level1name" => $level1name,
										"relativename" => ($relativename?$relativename.'/':'').$file,
										"fullname" => $path.'/'.$file,
										"date" => $filedate,
										"size" => $filesize,
										"type" => 'dir'
								);
							}
						}

						// if we're in a directory and we want recursive behavior, call this function again
						if ($recursive)
						{
							if (empty($donotfollowsymlinks) || ! is_link($path."/".$file))
							{
								//var_dump('eee '. $path."/".$file. ' '.is_dir($path."/".$file).' '.is_link($path."/".$file));
								$file_list = array_merge($file_list, dol_dir_list($path."/".$file, $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder, $mode, $nohook, ($relativename!=''?$relativename.'/':'').$file, $donotfollowsymlinks));
							}
						}
					}
					else if (! $isdir && (($types == "files") || ($types == "all")))
					{
						// Add file into file_list array
						if ($loaddate || $sortcriteria == 'date') $filedate=dol_filemtime($path."/".$file);
						if ($loadsize || $sortcriteria == 'size') $filesize=dol_filesize($path."/".$file);

						if (! $filter || preg_match('/'.$filter.'/i',$file))	// We do not search key $filter into $path, only into $file
						{
							preg_match('/([^\/]+)\/[^\/]+$/',$path.'/'.$file,$reg);
							$level1name=(isset($reg[1])?$reg[1]:'');
							$file_list[] = array(
									"name" => $file,
									"path" => $path,
									"level1name" => $level1name,
									"relativename" => ($relativename?$relativename.'/':'').$file,
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
			if (! empty($sortcriteria))
			{
				$myarray=array();
				foreach ($file_list as $key => $row)
				{
					$myarray[$key] = (isset($row[$sortcriteria])?$row[$sortcriteria]:'');
				}
				// Sort the data
				if ($sortorder) array_multisort($myarray, $sortorder, $file_list);
			}
		}
	}

	if (is_object($hookmanager) && is_array($hookmanager->resArray)) $file_list = array_merge($file_list, $hookmanager->resArray);

	return $file_list;
}


/**
 *  Scan a directory and return a list of files/directories.
 *  Content for string is UTF8 and dir separator is "/".
 *
 *  @param	string		$path        	Starting path from which to search. Example: 'produit/MYPROD'
 *  @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 *  @param	array|null	$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.'))
 *  @param	string		$sortcriteria	Sort criteria ("","fullname","name","date","size")
 *  @param	string		$sortorder		Sort order (SORT_ASC, SORT_DESC)
 *	@param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like description
 *  @return	array						Array of array('name'=>'xxx','fullname'=>'/abc/xxx','type'=>'dir|file',...)
 *  @see dol_dir_list
 */
function dol_dir_list_in_database($path, $filter="", $excludefilter=null, $sortcriteria="name", $sortorder=SORT_ASC, $mode=0)
{
	global $conf, $db;

	$sql =" SELECT rowid, label, entity, filename, filepath, fullpath_orig, keywords, cover, gen_or_uploaded, extraparams, date_c, date_m, fk_user_c, fk_user_m,";
	$sql.=" acl, position, share";
	if ($mode) $sql.=", description";
	$sql.=" FROM ".MAIN_DB_PREFIX."ecm_files";
	$sql.=" WHERE filepath = '".$db->escape($path)."'";
	$sql.=" AND entity = ".$conf->entity;

	$resql = $db->query($sql);
	if ($resql)
	{
		$file_list=array();
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			if ($obj)
			{
				preg_match('/([^\/]+)\/[^\/]+$/',DOL_DATA_ROOT.'/'.$obj->filepath.'/'.$obj->filename,$reg);
				$level1name=(isset($reg[1])?$reg[1]:'');
				$file_list[] = array(
					"rowid" => $obj->rowid,
					"label" => $obj->label,         // md5
					"name" => $obj->filename,
					"path" => DOL_DATA_ROOT.'/'.$obj->filepath,
					"level1name" => $level1name,
					"fullname" => DOL_DATA_ROOT.'/'.$obj->filepath.'/'.$obj->filename,
					"fullpath_orig" => $obj->fullpath_orig,
					"date_c" => $db->jdate($obj->date_c),
					"date_m" => $db->jdate($obj->date_m),
					"type" => 'file',
					"keywords" => $obj->keywords,
					"cover" => $obj->cover,
					"position" => (int) $obj->position,
					"acl" => $obj->acl,
					"share" => $obj->share
				);
			}
			$i++;
		}

		// Obtain a list of columns
		if (! empty($sortcriteria))
		{
			$myarray=array();
			foreach ($file_list as $key => $row)
			{
				$myarray[$key] = (isset($row[$sortcriteria])?$row[$sortcriteria]:'');
			}
			// Sort the data
			if ($sortorder) array_multisort($myarray, $sortorder, $file_list);
		}

		return $file_list;
	}
	else
	{
		dol_print_error($db);
		return array();
	}
}


/**
 * Complete $filearray with data from database.
 * This will call doldir_list_indatabase to complate filearray.
 *
 * @param	array	$filearray			Array of files get using dol_dir_list
 * @param	string	$relativedir		Relative dir from DOL_DATA_ROOT
 * @return	void
 */
function completeFileArrayWithDatabaseInfo(&$filearray, $relativedir)
{
	global $conf, $db, $user;

	$filearrayindatabase = dol_dir_list_in_database($relativedir, '', null, 'name', SORT_ASC);

	// TODO Remove this when PRODUCT_USE_OLD_PATH_FOR_PHOTO will be removed
	global $modulepart;
	if ($modulepart == 'produit' && ! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO)) {
		global $object;
		if (! empty($object->id))
		{
			if (! empty($conf->product->enabled)) $upload_dirold = $conf->product->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2),1,1).'/'.substr(substr("000".$object->id, -2),0,1).'/'.$object->id."/photos";
			else $upload_dirold = $conf->service->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2),1,1).'/'.substr(substr("000".$object->id, -2),0,1).'/'.$object->id."/photos";

			$relativedirold = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $upload_dirold);
			$relativedirold = preg_replace('/^[\\/]/','',$relativedirold);

			$filearrayindatabase = array_merge($filearrayindatabase, dol_dir_list_in_database($relativedirold, '', null, 'name', SORT_ASC));
		}
	}

	//var_dump($filearray);
	//var_dump($filearrayindatabase);

	// Complete filearray with properties found into $filearrayindatabase
	foreach($filearray as $key => $val)
	{
		$found=0;
		// Search if it exists into $filearrayindatabase
		foreach($filearrayindatabase as $key2 => $val2)
		{
			if ($filearrayindatabase[$key2]['name'] == $filearray[$key]['name'])
			{
				$filearray[$key]['position_name']=($filearrayindatabase[$key2]['position']?$filearrayindatabase[$key2]['position']:'0').'_'.$filearrayindatabase[$key2]['name'];
				$filearray[$key]['position']=$filearrayindatabase[$key2]['position'];
				$filearray[$key]['cover']=$filearrayindatabase[$key2]['cover'];
				$filearray[$key]['acl']=$filearrayindatabase[$key2]['acl'];
				$filearray[$key]['rowid']=$filearrayindatabase[$key2]['rowid'];
				$filearray[$key]['label']=$filearrayindatabase[$key2]['label'];
				$filearray[$key]['share']=$filearrayindatabase[$key2]['share'];
				$found=1;
				break;
			}
		}

		if (! $found)    // This happen in transition toward version 6, or if files were added manually into os dir.
		{
			$filearray[$key]['position']='999999';     // File not indexed are at end. So if we add a file, it will not replace an existing position
			$filearray[$key]['cover']=0;
			$filearray[$key]['acl']='';

			$rel_filename = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $filearray[$key]['fullname']);
			if (! preg_match('/([\\/]temp[\\/]|[\\/]thumbs|\.meta$)/', $rel_filetorenameafter))     // If not a tmp file
			{
				dol_syslog("list_of_documents We found a file called '".$filearray[$key]['name']."' not indexed into database. We add it");
				include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
				$ecmfile=new EcmFiles($db);

				// Add entry into database
				$filename = basename($rel_filename);
				$rel_dir = dirname($rel_filename);
				$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
				$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

				$ecmfile->filepath = $rel_dir;
				$ecmfile->filename = $filename;
				$ecmfile->label = md5_file(dol_osencode($filearray[$key]['fullname']));        // $destfile is a full path to file
				$ecmfile->fullpath_orig = $filearray[$key]['fullname'];
				$ecmfile->gen_or_uploaded = 'unknown';
				$ecmfile->description = '';    // indexed content
				$ecmfile->keyword = '';        // keyword content
				$result = $ecmfile->create($user);
				if ($result < 0)
				{
					setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
				}
				else
				{
					$filearray[$key]['rowid']=$result;
				}
			}
			else
			{
				$filearray[$key]['rowid']=0;     // Should not happened
			}
		}
	}

	/*var_dump($filearray);*/
}


/**
 * Fast compare of 2 files identified by their properties ->name, ->date and ->size
 *
 * @param	string 	$a		File 1
 * @param 	string	$b		File 2
 * @return 	int				1, 0, 1
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
 * Test if filename is a directory
 *
 * @param	string		$folder     Name of folder
 * @return	boolean     			True if it's a directory, False if not found
 */
function dol_is_dir($folder)
{
	$newfolder=dol_osencode($folder);
	if (is_dir($newfolder)) return true;
	else return false;
}

/**
 * Return if path is a file
 *
 * @param   string		$pathoffile		Path of file
 * @return  boolean     			    True or false
 */
function dol_is_file($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return is_file($newpathoffile);
}

/**
 * Return if path is an URL
 *
 * @param   string		$url	Url
 * @return  boolean      	   	True or false
 */
function dol_is_url($url)
{
	$tmpprot=array('file','http','https','ftp','zlib','data','ssh','ssh2','ogg','expect');
	foreach($tmpprot as $prot)
	{
		if (preg_match('/^'.$prot.':/i',$url)) return true;
	}
	return false;
}

/**
 * 	Test if a folder is empty
 *
 * 	@param	string	$folder		Name of folder
 * 	@return boolean				True if dir is empty or non-existing, False if it contains files
 */
function dol_dir_is_emtpy($folder)
{
	$newfolder=dol_osencode($folder);
	if (is_dir($newfolder))
	{
		$handle = opendir($newfolder);
		$folder_content = '';
		while ((gettype($name = readdir($handle)) != "boolean"))
		{
			$name_array[] = $name;
		}
		foreach($name_array as $temp) $folder_content .= $temp;

		closedir($handle);

		if ($folder_content == "...") return true;
		else return false;
	}
	else
	return true; // Dir does not exists
}

/**
 * 	Count number of lines in a file
 *
 * 	@param	string	$file		Filename
 * 	@return int					<0 if KO, Number of lines in files if OK
 *  @see dol_nboflines
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
			// We increase count only if read was success. We need test because feof return true only after fgets so we do n+1 fgets for a file with n lines.
			if (! $line === false) $nb++;
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
 * @param 	string		$pathoffile		Path of file
 * @return 	integer						File size
 */
function dol_filesize($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return filesize($newpathoffile);
}

/**
 * Return time of a file
 *
 * @param 	string		$pathoffile		Path of file
 * @return 	int					Time of file
 */
function dol_filemtime($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return @filemtime($newpathoffile); // @Is to avoid errors if files does not exists
}

/**
 * Make replacement of strings into a file.
 *
 * @param	string	$srcfile			Source file (can't be a directory)
 * @param	array	$arrayreplacement	Array with strings to replace. Example: array('valuebefore'=>'valueafter', ...)
 * @param	string	$destfile			Destination file (can't be a directory). If empty, will be same than source file.
 * @param	int		$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param	int		$indexdatabase		Index new file into database.
 * @return	int							<0 if error, 0 if nothing done (dest file already exists), >0 if OK
 * @see		dol_copy dolReplaceRegExInFile
 */
function dolReplaceInFile($srcfile, $arrayreplacement, $destfile='', $newmask=0, $indexdatabase=0)
{
	global $conf;

	dol_syslog("files.lib.php::dolReplaceInFile srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." indexdatabase=".$indexdatabase);

	if (empty($srcfile)) return -1;
	if (empty($destfile)) $destfile=$srcfile;

	$destexists=dol_is_file($destfile);
	if (($destfile != $srcfile) && $destexists) return 0;

	$tmpdestfile=$destfile.'.tmp';

	$newpathofsrcfile=dol_osencode($srcfile);
	$newpathoftmpdestfile=dol_osencode($tmpdestfile);
	$newpathofdestfile=dol_osencode($destfile);
	$newdirdestfile=dirname($newpathofdestfile);

	if ($destexists && ! is_writable($newpathofdestfile))
	{
		dol_syslog("files.lib.php::dolReplaceInFile failed Permission denied to overwrite target file", LOG_WARNING);
		return -1;
	}
	if (! is_writable($newdirdestfile))
	{
		dol_syslog("files.lib.php::dolReplaceInFile failed Permission denied to write into target directory ".$newdirdestfile, LOG_WARNING);
		return -2;
	}

	dol_delete_file($tmpdestfile);

	// Create $newpathoftmpdestfile from $newpathofsrcfile
	$content=file_get_contents($newpathofsrcfile, 'r');

	$content = make_substitutions($content, $arrayreplacement, null);

	file_put_contents($newpathoftmpdestfile, $content);
	@chmod($newpathoftmpdestfile, octdec($newmask));

	// Rename
	$result=dol_move($newpathoftmpdestfile, $newpathofdestfile, $newmask, (($destfile == $srcfile)?1:0), 0, $indexdatabase);
	if (! $result)
	{
		dol_syslog("files.lib.php::dolReplaceInFile failed to move tmp file to final dest", LOG_WARNING);
		return -3;
	}
	if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
	if (empty($newmask))	// This should no happen
	{
		dol_syslog("Warning: dolReplaceInFile called with empty value for newmask and no default value defined", LOG_WARNING);
		$newmask='0664';
	}

	@chmod($newpathofdestfile, octdec($newmask));

	return 1;
}

/**
 * Make replacement of strings into a file.
 *
 * @param	string	$srcfile			Source file (can't be a directory)
 * @param	array	$arrayreplacement	Array with strings to replace. Example: array('valuebefore'=>'valueafter', ...)
 * @param	string	$destfile			Destination file (can't be a directory). If empty, will be same than source file.
 * @param	int		$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param	int		$indexdatabase		Index new file into database.
 * @return	int							<0 if error, 0 if nothing done (dest file already exists), >0 if OK
 * @see		dol_copy dolReplaceInFile
 */
function dolReplaceRegExInFile($srcfile, $arrayreplacement, $destfile='', $newmask=0, $indexdatabase=0)
{
	// TODO

}

/**
 * Copy a file to another file.
 *
 * @param	string	$srcfile			Source file (can't be a directory)
 * @param	string	$destfile			Destination file (can't be a directory)
 * @param	int		$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param 	int		$overwriteifexists	Overwrite file if exists (1 by default)
 * @return	int							<0 if error, 0 if nothing done (dest file already exists and overwriteifexists=0), >0 if OK
 * @see		dol_delete_file
 */
function dol_copy($srcfile, $destfile, $newmask=0, $overwriteifexists=1)
{
	global $conf;

	dol_syslog("files.lib.php::dol_copy srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwriteifexists=".$overwriteifexists);

	if (empty($srcfile) || empty($destfile)) return -1;

	$destexists=dol_is_file($destfile);
	if (! $overwriteifexists && $destexists) return 0;

	$newpathofsrcfile=dol_osencode($srcfile);
	$newpathofdestfile=dol_osencode($destfile);
	$newdirdestfile=dirname($newpathofdestfile);

	if ($destexists && ! is_writable($newpathofdestfile))
	{
		dol_syslog("files.lib.php::dol_copy failed Permission denied to overwrite target file", LOG_WARNING);
		return -1;
	}
	if (! is_writable($newdirdestfile))
	{
		dol_syslog("files.lib.php::dol_copy failed Permission denied to write into target directory ".$newdirdestfile, LOG_WARNING);
		return -2;
	}
	// Copy with overwriting if exists
	$result=@copy($newpathofsrcfile, $newpathofdestfile);
	//$result=copy($newpathofsrcfile, $newpathofdestfile);	// To see errors, remove @
	if (! $result)
	{
		dol_syslog("files.lib.php::dol_copy failed to copy", LOG_WARNING);
		return -3;
	}
	if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
	if (empty($newmask))	// This should no happen
	{
		dol_syslog("Warning: dol_copy called with empty value for newmask and no default value defined", LOG_WARNING);
		$newmask='0664';
	}

	@chmod($newpathofdestfile, octdec($newmask));

	return 1;
}

/**
 * Copy a dir to another dir. This include recursive subdirectories.
 *
 * @param	string	$srcfile			Source file (a directory)
 * @param	string	$destfile			Destination file (a directory)
 * @param	int		$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param 	int		$overwriteifexists	Overwrite file if exists (1 by default)
 * @param	array	$arrayreplacement	Array to use to replace filenames with another one during the copy (works only on file names, not on directory names).
 * @return	int							<0 if error, 0 if nothing done (all files already exists and overwriteifexists=0), >0 if OK
 * @see		dol_copy
 */
function dolCopyDir($srcfile, $destfile, $newmask, $overwriteifexists, $arrayreplacement=null)
{
	global $conf;

	$result=0;

	dol_syslog("files.lib.php::dolCopyDir srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwriteifexists=".$overwriteifexists);

	if (empty($srcfile) || empty($destfile)) return -1;

	$destexists=dol_is_dir($destfile);
	//if (! $overwriteifexists && $destexists) return 0;	// The overwriteifexists is for files only, so propagated to dol_copy only.

	if (! $destexists)
	{
		// We must set mask just before creating dir, becaause it can be set differently by dol_copy
		umask(0);
		$dirmaskdec=octdec($newmask);
		if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $dirmaskdec=octdec($conf->global->MAIN_UMASK);
		$dirmaskdec |= octdec('0200');  // Set w bit required to be able to create content for recursive subdirs files
		dol_mkdir($destfile, '', decoct($dirmaskdec));
	}

	$ossrcfile=dol_osencode($srcfile);
	$osdestfile=dol_osencode($destfile);

	// Recursive function to copy all subdirectories and contents:
	if (is_dir($ossrcfile))
	{
		$dir_handle=opendir($ossrcfile);
		while ($file=readdir($dir_handle))
		{
			if ($file != "." && $file != ".." && ! is_link($ossrcfile."/".$file))
			{
				if (is_dir($ossrcfile."/".$file))
				{
					//var_dump("xxx dolCopyDir $srcfile/$file, $destfile/$file, $newmask, $overwriteifexists");
					$tmpresult=dolCopyDir($srcfile."/".$file, $destfile."/".$file, $newmask, $overwriteifexists, $arrayreplacement);
				}
				else
				{
					$newfile = $file;
					// Replace destination filename with a new one
					if (is_array($arrayreplacement))
					{
						foreach($arrayreplacement as $key => $val)
						{
							$newfile = str_replace($key, $val, $newfile);
						}
					}
					$tmpresult=dol_copy($srcfile."/".$file, $destfile."/".$newfile, $newmask, $overwriteifexists);
				}
				// Set result
				if ($result > 0 && $tmpresult >= 0)
				{
					// Do nothing, so we don't set result to 0 if tmpresult is 0 and result was success in a previous pass
				}
				else
				{
					$result=$tmpresult;
				}
				if ($result < 0) break;

			}
		}
		closedir($dir_handle);
	}
	else
	{
		// Source directory does not exists
		$result = -2;
	}

	return $result;
}


/**
 * Move a file into another name.
 * Note:
 *  - This function differs from dol_move_uploaded_file, because it can be called in any context.
 *  - Database indexes for files are updated.
 *  - Test on antivirus is done only if param testvirus is provided and an antivirus was set.
 *
 * @param	string  $srcfile            Source file (can't be a directory. use native php @rename() to move a directory)
 * @param   string	$destfile           Destination file (can't be a directory. use native php @rename() to move a directory)
 * @param   integer	$newmask            Mask in octal string for new file (0 by default means $conf->global->MAIN_UMASK)
 * @param   int		$overwriteifexists  Overwrite file if exists (1 by default)
 * @param   int     $testvirus          Do an antivirus test. Move is canceled if a virus is found.
 * @param	int		$indexdatabase		Index new file into database.
 * @return  boolean 		            True if OK, false if KO
 * @see dol_move_uploaded_file
 */
function dol_move($srcfile, $destfile, $newmask=0, $overwriteifexists=1, $testvirus=0, $indexdatabase=1)
{
	global $user, $db, $conf;
	$result=false;

	dol_syslog("files.lib.php::dol_move srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwritifexists=".$overwriteifexists);
	$srcexists=dol_is_file($srcfile);
	$destexists=dol_is_file($destfile);

	if (! $srcexists)
	{
		dol_syslog("files.lib.php::dol_move srcfile does not exists. we ignore the move request.");
		return false;
	}

	if ($overwriteifexists || ! $destexists)
	{
		$newpathofsrcfile=dol_osencode($srcfile);
		$newpathofdestfile=dol_osencode($destfile);

		// Check virus
		$testvirusarray=array();
		if ($testvirus)
		{
			$testvirusarray=dolCheckVirus($newpathofsrcfile);
			if (count($testvirusarray))
			{
				dol_syslog("files.lib.php::dol_move canceled because a virus was found into source file. we ignore the move request.", LOG_WARNING);
				return false;
			}
		}

		$result=@rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
		if (! $result)
		{
			if ($destexists)
			{
				dol_syslog("files.lib.php::dol_move Failed. We try to delete target first and move after.", LOG_WARNING);
				// We force delete and try again. Rename function sometimes fails to replace dest file with some windows NTFS partitions.
				dol_delete_file($destfile);
				$result=@rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
			}
			else dol_syslog("files.lib.php::dol_move Failed.", LOG_WARNING);
		}

		// Move ok
		if ($result && $indexdatabase)
		{
			// Rename entry into ecm database
			$rel_filetorenamebefore = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $srcfile);
			$rel_filetorenameafter = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $destfile);
			if (! preg_match('/([\\/]temp[\\/]|[\\/]thumbs|\.meta$)/', $rel_filetorenameafter))     // If not a tmp file
			{
				$rel_filetorenamebefore = preg_replace('/^[\\/]/', '', $rel_filetorenamebefore);
				$rel_filetorenameafter = preg_replace('/^[\\/]/', '', $rel_filetorenameafter);
				//var_dump($rel_filetorenamebefore.' - '.$rel_filetorenameafter);

				dol_syslog("Try to rename also entries in database for full relative path before = ".$rel_filetorenamebefore." after = ".$rel_filetorenameafter, LOG_DEBUG);
				include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

				$ecmfiletarget=new EcmFiles($db);
				$resultecmtarget = $ecmfiletarget->fetch(0, '', $rel_filetorenameafter);
				if ($resultecmtarget > 0)   // An entry for target name already exists for target, we delete it, a new one will be created.
				{
					$ecmfiletarget->delete($user);
				}

				$ecmfile=new EcmFiles($db);
				$resultecm = $ecmfile->fetch(0, '', $rel_filetorenamebefore);
				if ($resultecm > 0)   // If an entry was found for src file, we use it to move entry
				{
					$filename = basename($rel_filetorenameafter);
					$rel_dir = dirname($rel_filetorenameafter);
					$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
					$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

					$ecmfile->filepath = $rel_dir;
					$ecmfile->filename = $filename;
					$resultecm = $ecmfile->update($user);
				}
				elseif ($resultecm == 0)   // If no entry were found for src files, create/update target file
				{
					$filename = basename($rel_filetorenameafter);
					$rel_dir = dirname($rel_filetorenameafter);
					$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
					$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

					$ecmfile->filepath = $rel_dir;
					$ecmfile->filename = $filename;
					$ecmfile->label = md5_file(dol_osencode($destfile));        // $destfile is a full path to file
					$ecmfile->fullpath_orig = $srcfile;
					$ecmfile->gen_or_uploaded = 'unknown';
					$ecmfile->description = '';    // indexed content
					$ecmfile->keyword = '';        // keyword content
					$resultecm = $ecmfile->create($user);
					if ($resultecm < 0)
					{
						setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					}
				}
				elseif ($resultecm < 0)
				{
					setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
				}

				if ($resultecm > 0) $result=true;
				else $result = false;
			}
		}

		if (empty($newmask)) $newmask=empty($conf->global->MAIN_UMASK)?'0755':$conf->global->MAIN_UMASK;
		$newmaskdec=octdec($newmask);
		// Currently method is restricted to files (dol_delete_files previously used is for files, and mask usage if for files too)
		// to allow mask usage for dir, we shoul introduce a new param "isdir" to 1 to complete newmask like this
		// if ($isdir) $newmaskdec |= octdec('0111');  // Set x bit required for directories
		@chmod($newpathofdestfile, $newmaskdec);
	}

	return $result;
}

/**
 *	Unescape a file submitted by upload.
 *  PHP escape char " (%22) or char ' (%27) into $FILES.
 *
 *	@param	string	$filename		Filename
 *	@return	string					Filename sanitized
 */
function dol_unescapefile($filename)
{
	// Remove path information and dots around the filename, to prevent uploading
	// into different directories or replacing hidden system files.
	// Also remove control characters and spaces (\x00..\x20) around the filename:
	return trim(basename($filename), ".\x00..\x20");
}


/**
 * Check virus into a file
 *
 * @param   string      $src_file       Source file to check
 * @return  array                       Array of errors or empty array if not virus found
 */
function dolCheckVirus($src_file)
{
	global $conf;

	if (! empty($conf->global->MAIN_ANTIVIRUS_COMMAND))
	{
		if (! class_exists('AntiVir')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/antivir.class.php';
		}
		$antivir=new AntiVir($db);
		$result = $antivir->dol_avscan_file($src_file);
		if ($result < 0)	// If virus or error, we stop here
		{
			$reterrors=$antivir->errors;
			return $reterrors;
		}
	}
	return array();
}


/**
 *	Make control on an uploaded file from an GUI page and move it to final destination.
 * 	If there is errors (virus found, antivir in error, bad filename), file is not moved.
 *  Note:
 *  - This function can be used only into a HTML page context. Use dol_move if you are outside.
 *  - Test on antivirus is always done (if antivirus set).
 *  - Database of files is NOT updated (this is done by dol_add_file_process() that calls this function).
 *
 *	@param	string	$src_file			Source full path filename ($_FILES['field']['tmp_name'])
 *	@param	string	$dest_file			Target full path filename  ($_FILES['field']['name'])
 * 	@param	int		$allowoverwrite		1=Overwrite target file if it already exists
 * 	@param	int		$disablevirusscan	1=Disable virus scan
 * 	@param	integer	$uploaderrorcode	Value of PHP upload error code ($_FILES['field']['error'])
 * 	@param	int		$nohook				Disable all hooks
 * 	@param	string	$varfiles			_FILES var name
 *	@return int       			  		>0 if OK, <0 or string if KO
 *  @see    dol_move
 */
function dol_move_uploaded_file($src_file, $dest_file, $allowoverwrite, $disablevirusscan=0, $uploaderrorcode=0, $nohook=0, $varfiles='addedfile')
{
	global $conf, $db, $user, $langs;
	global $object, $hookmanager;

	$reshook=0;
	$file_name = $dest_file;

	if (empty($nohook))
	{
		$reshook=$hookmanager->initHooks(array('fileslib'));

		$parameters=array('dest_file' => $dest_file, 'src_file' => $src_file, 'file_name' => $file_name, 'varfiles' => $varfiles, 'allowoverwrite' => $allowoverwrite);
		$reshook=$hookmanager->executeHooks('moveUploadedFile', $parameters, $object);
	}

	if (empty($reshook))
	{
		// If an upload error has been reported
		if ($uploaderrorcode)
		{
			switch($uploaderrorcode)
			{
				case UPLOAD_ERR_INI_SIZE:	// 1
					return 'ErrorFileSizeTooLarge';
					break;
				case UPLOAD_ERR_FORM_SIZE:	// 2
					return 'ErrorFileSizeTooLarge';
					break;
				case UPLOAD_ERR_PARTIAL:	// 3
					return 'ErrorPartialFile';
					break;
				case UPLOAD_ERR_NO_TMP_DIR:	//
					return 'ErrorNoTmpDir';
					break;
				case UPLOAD_ERR_CANT_WRITE:
					return 'ErrorFailedToWriteInDir';
					break;
				case UPLOAD_ERR_EXTENSION:
					return 'ErrorUploadBlockedByAddon';
					break;
				default:
					break;
			}
		}

		// If we need to make a virus scan
		if (empty($disablevirusscan) && file_exists($src_file))
		{
			$checkvirusarray=dolCheckVirus($src_file);
			if (count($checkvirusarray))
			{
			   dol_syslog('Files.lib::dol_move_uploaded_file File "'.$src_file.'" (target name "'.$dest_file.'") KO with antivirus: result='.$result.' errors='.join(',',$checkvirusarray), LOG_WARNING);
			   return 'ErrorFileIsInfectedWithAVirus: '.join(',',$checkvirusarray);
			}
		}

		// Security:
		// Disallow file with some extensions. We rename them.
		// Because if we put the documents directory into a directory inside web root (very bad), this allows to execute on demand arbitrary code.
		if (preg_match('/\.htm|\.html|\.php|\.pl|\.cgi$/i',$dest_file) && empty($conf->global->MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED))
		{
			$file_name.= '.noexe';
		}

		// Security:
		// We refuse cache files/dirs, upload using .. and pipes into filenames.
		if (preg_match('/^\./',$src_file) || preg_match('/\.\./',$src_file) || preg_match('/[<>|]/',$src_file))
		{
			dol_syslog("Refused to deliver file ".$src_file, LOG_WARNING);
			return -1;
		}

		// Security:
		// On interdit fichiers caches, remontees de repertoire ainsi que les pipe dans les noms de fichiers.
		if (preg_match('/^\./',$dest_file) || preg_match('/\.\./',$dest_file) || preg_match('/[<>|]/',$dest_file))
		{
			dol_syslog("Refused to deliver file ".$dest_file, LOG_WARNING);
			return -2;
		}
	}

	if ($reshook < 0)	// At least one blocking error returned by one hook
	{
		$errmsg = join(',', $hookmanager->errors);
		if (empty($errmsg)) $errmsg = 'ErrorReturnedBySomeHooks';	// Should not occurs. Added if hook is bugged and does not set ->errors when there is error.
		return $errmsg;
	}
	elseif (empty($reshook))
	{
		// The file functions must be in OS filesystem encoding.
		$src_file_osencoded=dol_osencode($src_file);
		$file_name_osencoded=dol_osencode($file_name);

		// Check if destination dir is writable
		if (! is_writable(dirname($file_name_osencoded)))
		{
			dol_syslog("Files.lib::dol_move_uploaded_file Dir ".dirname($file_name_osencoded)." is not writable. Return 'ErrorDirNotWritable'", LOG_WARNING);
			return 'ErrorDirNotWritable';
		}

		// Check if destination file already exists
		if (! $allowoverwrite)
		{
			if (file_exists($file_name_osencoded))
			{
				dol_syslog("Files.lib::dol_move_uploaded_file File ".$file_name." already exists. Return 'ErrorFileAlreadyExists'", LOG_WARNING);
				return 'ErrorFileAlreadyExists';
			}
		}

		// Move file
		$return=move_uploaded_file($src_file_osencoded, $file_name_osencoded);
		if ($return)
		{
			if (! empty($conf->global->MAIN_UMASK)) @chmod($file_name_osencoded, octdec($conf->global->MAIN_UMASK));
			dol_syslog("Files.lib::dol_move_uploaded_file Success to move ".$src_file." to ".$file_name." - Umask=".$conf->global->MAIN_UMASK, LOG_DEBUG);
			return 1;	// Success
		}
		else
		{
			dol_syslog("Files.lib::dol_move_uploaded_file Failed to move ".$src_file." to ".$file_name, LOG_ERR);
			return -3;	// Unknown error
		}
	}

	return 1;	// Success
}

/**
 *  Remove a file or several files with a mask.
 *  This delete file physically but also database indexes.
 *
 *  @param	string	$file           File to delete or mask of files to delete
 *  @param  int		$disableglob    Disable usage of glob like * so function is an exact delete function that will return error if no file found
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @param	int		$nohook			Disable all hooks
 *  @param	object	$object			Current object in use
 *  @return boolean         		True if no error (file is deleted or if glob is used and there's nothing to delete), False if error
 *  @see dol_delete_dir
 */
function dol_delete_file($file,$disableglob=0,$nophperrors=0,$nohook=0,$object=null)
{
	global $db, $conf, $user, $langs;
	global $hookmanager;

	$langs->load("other");
	$langs->load("errors");

	dol_syslog("dol_delete_file file=".$file." disableglob=".$disableglob." nophperrors=".$nophperrors." nohook=".$nohook);

	// Security:
	// We refuse transversal using .. and pipes into filenames.
	if (preg_match('/\.\./',$file) || preg_match('/[<>|]/',$file))
	{
		dol_syslog("Refused to delete file ".$file, LOG_WARNING);
		return False;
	}

	if (empty($nohook))
	{
		$hookmanager->initHooks(array('fileslib'));

		$parameters=array(
				'GET' => $_GET,
				'file' => $file,
				'disableglob'=> $disableglob,
				'nophperrors' => $nophperrors
		);
		$reshook=$hookmanager->executeHooks('deleteFile', $parameters, $object);
	}

	if (empty($nohook) && $reshook != 0) // reshook = 0 to do standard actions, 1 = ok, -1 = ko
	{
		if ($reshook < 0) return false;
		return true;
	}
	else
	{
		$error=0;

		//print "x".$file." ".$disableglob;exit;
		$file_osencoded=dol_osencode($file);    // New filename encoded in OS filesystem encoding charset
		if (empty($disableglob) && ! empty($file_osencoded))
		{
			$ok=true;
			$globencoded=str_replace('[','\[',$file_osencoded);
			$globencoded=str_replace(']','\]',$globencoded);
			$listofdir=glob($globencoded);
			if (! empty($listofdir) && is_array($listofdir))
			{
				foreach ($listofdir as $filename)
				{
					if ($nophperrors) $ok=@unlink($filename);
					else $ok=unlink($filename);
					if ($ok)
					{
						dol_syslog("Removed file ".$filename, LOG_DEBUG);

						// Delete entry into ecm database
						$rel_filetodelete = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $filename);
						if (! preg_match('/(\/temp\/|\/thumbs\/|\.meta$)/', $rel_filetodelete))     // If not a tmp file
						{
							$rel_filetodelete = preg_replace('/^[\\/]/', '', $rel_filetodelete);

							dol_syslog("Try to remove also entries in database for full relative path = ".$rel_filetodelete, LOG_DEBUG);
							include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
							$ecmfile=new EcmFiles($db);
							$result = $ecmfile->fetch(0, '', $rel_filetodelete);
							if ($result >= 0 && $ecmfile->id > 0)
							{
								$result = $ecmfile->delete($user);
							}
							if ($result < 0)
							{
								setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
							}
						}
					}
					else dol_syslog("Failed to remove file ".$filename, LOG_WARNING);
					// TODO Failure to remove can be because file was already removed or because of permission
					// If error because of not exists, we must should return true and we should return false if this is a permission problem
				}
			}
			else dol_syslog("No files to delete found", LOG_DEBUG);
		}
		else
		{
			$ok=false;
			if ($nophperrors) $ok=@unlink($file_osencoded);
			else $ok=unlink($file_osencoded);
			if ($ok) dol_syslog("Removed file ".$file_osencoded, LOG_DEBUG);
			else dol_syslog("Failed to remove file ".$file_osencoded, LOG_WARNING);
		}

		return $ok;
	}
}

/**
 *  Remove a directory (not recursive, so content must be empty).
 *  If directory is not empty, return false
 *
 *  @param	string	$dir            Directory to delete
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @return boolean         		True if success, false if error
 *  @see dol_delete_file dol_copy
 */
function dol_delete_dir($dir,$nophperrors=0)
{
	// Security:
	// We refuse transversal using .. and pipes into filenames.
	if (preg_match('/\.\./',$dir) || preg_match('/[<>|]/',$dir))
	{
		dol_syslog("Refused to delete dir ".$dir, LOG_WARNING);
		return False;
	}

	$dir_osencoded=dol_osencode($dir);
	return ($nophperrors?@rmdir($dir_osencoded):rmdir($dir_osencoded));
}

/**
 *  Remove a directory $dir and its subdirectories (or only files and subdirectories)
 *
 *  @param	string	$dir            Dir to delete
 *  @param  int		$count          Counter to count nb of elements found to delete
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @param	int		$onlysub		Delete only files and subdir, not main directory
 *  @param  int		$countdeleted   Counter to count nb of elements found really deleted
 *  @return int             		Number of files and directory we try to remove. NB really removed is returned into $countdeleted.
 */
function dol_delete_dir_recursive($dir, $count=0, $nophperrors=0, $onlysub=0, &$countdeleted=0)
{
	dol_syslog("functions.lib:dol_delete_dir_recursive ".$dir,LOG_DEBUG);
	if (dol_is_dir($dir))
	{
		$dir_osencoded=dol_osencode($dir);
		if ($handle = opendir("$dir_osencoded"))
		{
			while (false !== ($item = readdir($handle)))
			{
				if (! utf8_check($item)) $item=utf8_encode($item);  // should be useless

				if ($item != "." && $item != "..")
				{
					if (is_dir(dol_osencode("$dir/$item")) && ! is_link(dol_osencode("$dir/$item")))
					{
						$count=dol_delete_dir_recursive("$dir/$item", $count, $nophperrors, 0, $countdeleted);
					}
					else
					{
						$result=dol_delete_file("$dir/$item", 1, $nophperrors);
						$count++;
						if ($result) $countdeleted++;
						//else print 'Error on '.$item."\n";
					}
				}
			}
			closedir($handle);

			if (empty($onlysub))
			{
				$result=dol_delete_dir($dir, $nophperrors);
				$count++;
				if ($result) $countdeleted++;
				//else print 'Error on '.$dir."\n";
			}
		}
	}

	return $count;
}


/**
 *  Delete all preview files linked to object instance.
 *  Note that preview image of PDF files is generated when required, by dol_banner_tab() for example.
 *
 *  @param	object	$object		Object to clean
 *  @return	int					0 if error, 1 if OK
 *  @see dol_convert_file
 */
function dol_delete_preview($object)
{
	global $langs,$conf;

	// Define parent dir of elements
	$element = $object->element;

	if ($object->element == 'order_supplier')		$dir = $conf->fournisseur->commande->dir_output;
	elseif ($object->element == 'invoice_supplier')	$dir = $conf->fournisseur->facture->dir_output;
	elseif ($object->element == 'project')			$dir = $conf->projet->dir_output;
	elseif ($object->element == 'shipping')			$dir = $conf->expedition->dir_output.'/sending';
	elseif ($object->element == 'delivery')			$dir = $conf->expedition->dir_output.'/receipt';
	elseif ($object->element == 'fichinter')		$dir = $conf->ficheinter->dir_output;
	else $dir=empty($conf->$element->dir_output)?'':$conf->$element->dir_output;

	if (empty($dir)) return 'ErrorObjectNoSupportedByFunction';

	$refsan = dol_sanitizeFileName($object->ref);
	$dir = $dir . "/" . $refsan ;
	$filepreviewnew = $dir . "/" . $refsan . ".pdf_preview.png";
	$filepreviewnewbis = $dir . "/" . $refsan . ".pdf_preview-0.png";
	$filepreviewold = $dir . "/" . $refsan . ".pdf.png";

	// For new preview files
	if (file_exists($filepreviewnew) && is_writable($filepreviewnew))
	{
		if (! dol_delete_file($filepreviewnew,1))
		{
			$object->error=$langs->trans("ErrorFailedToDeleteFile",$filepreviewnew);
			return 0;
		}
	}
	if (file_exists($filepreviewnewbis) && is_writable($filepreviewnewbis))
	{
		if (! dol_delete_file($filepreviewnewbis,1))
		{
			$object->error=$langs->trans("ErrorFailedToDeleteFile",$filepreviewnewbis);
			return 0;
		}
	}
	// For old preview files
	if (file_exists($filepreviewold) && is_writable($filepreviewold))
	{
		if (! dol_delete_file($filepreviewold,1))
		{
			$object->error=$langs->trans("ErrorFailedToDeleteFile",$filepreviewold);
			return 0;
		}
	}
	else
	{
		$multiple = $filepreviewold . ".";
		for ($i = 0; $i < 20; $i++)
		{
			$preview = $multiple.$i;

			if (file_exists($preview) && is_writable($preview))
			{
				if ( ! dol_delete_file($preview,1) )
				{
					$object->error=$langs->trans("ErrorFailedToOpenFile",$preview);
					return 0;
				}
			}
		}
	}

	return 1;
}

/**
 *	Create a meta file with document file into same directory.
 *	This make "grep" search possible.
 *  This feature to generate the meta file is enabled only if option MAIN_DOC_CREATE_METAFILE is set.
 *
 *	@param	CommonObject	$object		Object
 *	@return	int							0 if do nothing, >0 if we update meta file too, <0 if KO
 */
function dol_meta_create($object)
{
	global $conf;

	// Create meta file
	if (empty($conf->global->MAIN_DOC_CREATE_METAFILE)) return 0;	// By default, no metafile.

	// Define parent dir of elements
	$element=$object->element;

	if ($object->element == 'order_supplier')		$dir = $conf->fournisseur->dir_output.'/commande';
	elseif ($object->element == 'invoice_supplier')	$dir = $conf->fournisseur->dir_output.'/facture';
	elseif ($object->element == 'project')			$dir = $conf->projet->dir_output;
	elseif ($object->element == 'shipping')			$dir = $conf->expedition->dir_output.'/sending';
	elseif ($object->element == 'delivery')			$dir = $conf->expedition->dir_output.'/receipt';
	elseif ($object->element == 'fichinter')		$dir = $conf->ficheinter->dir_output;
	else $dir=empty($conf->$element->dir_output)?'':$conf->$element->dir_output;

	if ($dir)
	{
		$object->fetch_thirdparty();

		$objectref = dol_sanitizeFileName($object->ref);
		$dir = $dir . "/" . $objectref;
		$file = $dir . "/" . $objectref . ".meta";

		if (! is_dir($dir))
		{
			dol_mkdir($dir);
		}

		if (is_dir($dir))
		{
			$nblignes = count($object->lines);
			$client = $object->thirdparty->name . " " . $object->thirdparty->address . " " . $object->thirdparty->zip . " " . $object->thirdparty->town;
			$meta = "REFERENCE=\"" . $object->ref . "\"
			DATE=\"" . dol_print_date($object->date,'') . "\"
			NB_ITEMS=\"" . $nblignes . "\"
			CLIENT=\"" . $client . "\"
			AMOUNT_EXCL_TAX=\"" . $object->total_ht . "\"
			AMOUNT=\"" . $object->total_ttc . "\"\n";

			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				//Pour les articles
				$meta .= "ITEM_" . $i . "_QUANTITY=\"" . $object->lines[$i]->qty . "\"
				ITEM_" . $i . "_AMOUNT_WO_TAX=\"" . $object->lines[$i]->total_ht . "\"
				ITEM_" . $i . "_VAT=\"" .$object->lines[$i]->tva_tx . "\"
				ITEM_" . $i . "_DESCRIPTION=\"" . str_replace("\r\n","",nl2br($object->lines[$i]->desc)) . "\"
				";
			}
		}

		$fp = fopen($file,"w");
		fputs($fp,$meta);
		fclose($fp);
		if (! empty($conf->global->MAIN_UMASK))
		@chmod($file, octdec($conf->global->MAIN_UMASK));

		return 1;
	}
	else
	{
		dol_syslog('FailedToDetectDirInDolMetaCreateFor'.$object->element, LOG_WARNING);
	}

	return 0;
}



/**
 * Scan a directory and init $_SESSION to manage uploaded files with list of all found files.
 * Note: Only email module seems to use this. Other feature initialize the $_SESSION doing $formmail->clear_attached_files(); $formmail->add_attached_files()
 *
 * @param	string	$pathtoscan				Path to scan
 * @param   string  $trackid                Track id (used to prefix name of session vars to avoid conflict)
 * @return	void
 */
function dol_init_file_process($pathtoscan='', $trackid='')
{
	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();

	if ($pathtoscan)
	{
		$listoffiles=dol_dir_list($pathtoscan,'files');
		foreach($listoffiles as $key => $val)
		{
			$listofpaths[]=$val['fullname'];
			$listofnames[]=$val['name'];
			$listofmimes[]=dol_mimetype($val['name']);
		}
	}
	$keytoavoidconflict = empty($trackid)?'':'-'.$trackid;
	$_SESSION["listofpaths".$keytoavoidconflict]=join(';',$listofpaths);
	$_SESSION["listofnames".$keytoavoidconflict]=join(';',$listofnames);
	$_SESSION["listofmimes".$keytoavoidconflict]=join(';',$listofmimes);
}


/**
 * Get and save an upload file (for example after submitting a new file a mail form). Database index of file is also updated if donotupdatesession is set.
 * All information used are in db, conf, langs, user and _FILES.
 * Note: This function can be used only into a HTML page context.
 *
 * @param	string	$upload_dir				Directory where to store uploaded file (note: used to forge $destpath = $upload_dir + filename)
 * @param	int		$allowoverwrite			1=Allow overwrite existing file
 * @param	int		$donotupdatesession		1=Do no edit _SESSION variable but update database index. 0=Update _SESSION and not database index.
 * @param	string	$varfiles				_FILES var name
 * @param	string	$savingdocmask			Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
 * @param	string	$link					Link to add (to add a link instead of a file)
 * @param   string  $trackid                Track id (used to prefix name of session vars to avoid conflict)
 * @param	int		$generatethumbs			1=Generate also thumbs for uploaded image files
 * @return	int                             <=0 if KO, >0 if OK
 */
function dol_add_file_process($upload_dir, $allowoverwrite=0, $donotupdatesession=0, $varfiles='addedfile', $savingdocmask='', $link=null, $trackid='', $generatethumbs=1)
{
	global $db,$user,$conf,$langs;

	$res = 0;

	if (! empty($_FILES[$varfiles])) // For view $_FILES[$varfiles]['error']
	{
		dol_syslog('dol_add_file_process upload_dir='.$upload_dir.' allowoverwrite='.$allowoverwrite.' donotupdatesession='.$donotupdatesession.' savingdocmask='.$savingdocmask, LOG_DEBUG);
		if (dol_mkdir($upload_dir) >= 0)
		{
			$TFile = $_FILES[$varfiles];
			if (!is_array($TFile['name']))
			{
				foreach ($TFile as $key => &$val)
				{
					$val = array($val);
				}
			}

			$nbfile = count($TFile['name']);
			$nbok = 0;
			for ($i = 0; $i < $nbfile; $i++)
			{
				// Define $destfull (path to file including filename) and $destfile (only filename)
				$destfull=$upload_dir . "/" . $TFile['name'][$i];
				$destfile=$TFile['name'][$i];

				if ($savingdocmask)
				{
					$destfull=$upload_dir . "/" . preg_replace('/__file__/',$TFile['name'][$i],$savingdocmask);
					$destfile=preg_replace('/__file__/',$TFile['name'][$i],$savingdocmask);
				}

				// dol_sanitizeFileName the file name and lowercase extension
				$info = pathinfo($destfull);
				$destfull = $info['dirname'].'/'.dol_sanitizeFileName($info['filename'].'.'.strtolower($info['extension']));
				$info = pathinfo($destfile);
				$destfile = dol_sanitizeFileName($info['filename'].'.'.strtolower($info['extension']));

				$resupload = dol_move_uploaded_file($TFile['tmp_name'][$i], $destfull, $allowoverwrite, 0, $TFile['error'][$i], 0, $varfiles);

				if (is_numeric($resupload) && $resupload > 0)   // $resupload can be 'ErrorFileAlreadyExists'
				{
					global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;

					include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

					// Generate thumbs.
					if ($generatethumbs)
					{
						if (image_format_supported($destfull) == 1)
						{
							// Create thumbs
							// We can't use $object->addThumbs here because there is no $object known

							// Used on logon for example
							$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
							// Create mini thumbs for image (Ratio is near 16/9)
							// Used on menu or for setup page for example
							$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
						}
					}

					// Update session
					if (empty($donotupdatesession))
					{
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);
						$formmail->trackid = $trackid;
						$formmail->add_attached_files($destfull, $destfile, $TFile['type'][$i]);
					}

					// Update table of files
					if ($donotupdatesession)
					{
						$result = addFileIntoDatabaseIndex($upload_dir, basename($destfile), $TFile['name'][$i], 'uploaded', 0);
						if ($result < 0)
						{
							setEventMessages('FailedToAddFileIntoDatabaseIndex', '', 'warnings');
						}
					}

					$nbok++;
				}
				else
				{
					$langs->load("errors");
					if ($resupload < 0)	// Unknown error
					{
						setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
					}
					else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
					{
						setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
					}
					else	// Known error
					{
						setEventMessages($langs->trans($resupload), null, 'errors');
					}
				}
			}
			if ($nbok > 0)
			{
				$res = 1;
				setEventMessages($langs->trans("FileTransferComplete"), null, 'mesgs');
			}
		}
	} elseif ($link) {
		require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
		$linkObject = new Link($db);
		$linkObject->entity = $conf->entity;
		$linkObject->url = $link;
		$linkObject->objecttype = GETPOST('objecttype', 'alpha');
		$linkObject->objectid = GETPOST('objectid', 'int');
		$linkObject->label = GETPOST('label', 'alpha');
		$res = $linkObject->create($user);
		$langs->load('link');
		if ($res > 0) {
			setEventMessages($langs->trans("LinkComplete"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFileNotLinked"), null, 'errors');
		}
	}
	else
	{
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("File")), null, 'errors');
	}

	return $res;
}


/**
 * Remove an uploaded file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 *
 * @param	int		$filenb					File nb to delete
 * @param	int		$donotupdatesession		1=Do not edit _SESSION variable
 * @param   int		$donotdeletefile        1=Do not delete physically file
 * @param   string  $trackid                Track id (used to prefix name of session vars to avoid conflict)
 * @return	void
 */
function dol_remove_file_process($filenb,$donotupdatesession=0,$donotdeletefile=1,$trackid='')
{
	global $db,$user,$conf,$langs,$_FILES;

	$keytodelete=$filenb;
	$keytodelete--;

	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
	$keytoavoidconflict = empty($trackid)?'':'-'.$trackid;
	if (! empty($_SESSION["listofpaths".$keytoavoidconflict])) $listofpaths=explode(';',$_SESSION["listofpaths".$keytoavoidconflict]);
	if (! empty($_SESSION["listofnames".$keytoavoidconflict])) $listofnames=explode(';',$_SESSION["listofnames".$keytoavoidconflict]);
	if (! empty($_SESSION["listofmimes".$keytoavoidconflict])) $listofmimes=explode(';',$_SESSION["listofmimes".$keytoavoidconflict]);

	if ($keytodelete >= 0)
	{
		$pathtodelete=$listofpaths[$keytodelete];
		$filetodelete=$listofnames[$keytodelete];
		if (empty($donotdeletefile)) $result = dol_delete_file($pathtodelete,1);  // The delete of ecm database is inside the function dol_delete_file
		else $result=0;
		if ($result >= 0)
		{
			if (empty($donotdeletefile))
			{
				$langs->load("other");
				setEventMessages($langs->trans("FileWasRemoved",$filetodelete), null, 'mesgs');
			}
			if (empty($donotupdatesession))
			{
				include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
				$formmail = new FormMail($db);
				$formmail->trackid = $trackid;
				$formmail->remove_attached_files($keytodelete);
			}
		}
	}
}


/**
 *  Add a file into database index.
 *  Called by dol_add_file_process when uploading a file and on other cases.
 *  See also commonGenerateDocument that also add/update database index when a file is generated.
 *
 *  @param      string	$dir			Directory name (full real path without ending /)
 *  @param		string	$file			File name
 *  @param		string	$fullpathorig	Full path of origin for file (can be '')
 *  @param		string	$mode			How file was created ('uploaded', 'generated', ...)
 *  @param		int		$setsharekey	Set also the share key
 *	@return		int						<0 if KO, 0 if nothing done, >0 if OK
 */
function addFileIntoDatabaseIndex($dir, $file, $fullpathorig='', $mode='uploaded', $setsharekey=0)
{
	global $db, $user;

	$result = 0;

	$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $dir);

	if (! preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $rel_dir))     // If not a tmp dir
	{
		$filename = basename($file);
		$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
		$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile=new EcmFiles($db);
		$ecmfile->filepath = $rel_dir;
		$ecmfile->filename = $filename;
		$ecmfile->label = md5_file(dol_osencode($dir.'/'.$file));	// MD5 of file content
		$ecmfile->fullpath_orig = $fullpathorig;
		$ecmfile->gen_or_uploaded = $mode;
		$ecmfile->description = '';    // indexed content
		$ecmfile->keyword = '';        // keyword content
		if ($setsharekey)
		{
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$ecmfile->share = getRandomPassword(true);
		}

		$result = $ecmfile->create($user);
		if ($result < 0)
		{
			dol_syslog($ecmfile->error);
		}
	}

	return $result;
}


/**
 *  Delete files into database index using search criterias.
 *
 *  @param      string	$dir			Directory name (full real path without ending /)
 *  @param		string	$file			File name
 *  @param		string	$mode			How file was created ('uploaded', 'generated', ...)
 *	@return		int						<0 if KO, 0 if nothing done, >0 if OK
 */
function deleteFilesIntoDatabaseIndex($dir, $file, $mode='uploaded')
{
	global $conf, $db, $user;

	$error = 0;

	if (empty($dir))
	{
		dol_syslog("deleteFilesIntoDatabaseIndex: dir parameter can't be empty", LOG_ERR);
		return -1;
	}

	$db->begin();

	$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT,'/').'/', '', $dir);

	$filename = basename($file);
	$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
	$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

	if (! $error)
	{
		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'ecm_files';
		$sql.= ' WHERE entity = '.$conf->entity;
		$sql.= " AND filepath = '" . $db->escape($rel_dir) . "'";
		if ($file) $sql.= " AND filename = '" . $db->escape($file) . "'";
		if ($mode) $sql.= " AND gen_or_uploaded = '" . $db->escape($mode) . "'";

		$resql = $db->query($sql);
		if (!$resql)
		{
			$error++;
			dol_syslog(__METHOD__ . ' ' . $db->lasterror(), LOG_ERR);
		}
	}

	// Commit or rollback
	if ($error) {
		$db->rollback();
		return - 1 * $error;
	} else {
		$db->commit();
		return 1;
	}
}


/**
 * 	Convert an image file into another format.
 *  This need Imagick php extension.
 *
 *  @param	string	$fileinput  Input file name
 *  @param  string	$ext        Format of target file (It is also extension added to file if fileoutput is not provided).
 *  @param	string	$fileoutput	Output filename
 *  @return	int					<0 if KO, 0=Nothing done, >0 if OK
 */
function dol_convert_file($fileinput, $ext='png', $fileoutput='')
{
	global $langs;

	if (class_exists('Imagick'))
	{
		$image=new Imagick();
		try {
			$ret = $image->readImage($fileinput);
		} catch(Exception $e) {
			dol_syslog("Failed to read image using Imagick. Try to install package 'apt-get install ghostscript'.", LOG_WARNING);
			return 0;
		}
		if ($ret)
		{
			$ret = $image->setImageFormat($ext);
			if ($ret)
			{
				if (empty($fileoutput)) $fileoutput=$fileinput.".".$ext;

				$count = $image->getNumberImages();

				if (! dol_is_file($fileoutput) || is_writeable($fileoutput))
				{
					$ret = $image->writeImages($fileoutput, true);
				}
				else
				{
					dol_syslog("Warning: Failed to write cache preview file '.$fileoutput.'. Check permission on file/dir", LOG_ERR);
				}
				if ($ret) return $count;
				else return -3;
			}
			else
			{
				return -2;
			}
		}
		else
		{
			return -1;
		}
	}
	else
	{
		return 0;
	}
}


/**
 * Compress a file
 *
 * @param 	string	$inputfile		Source file name
 * @param 	string	$outputfile		Target file name
 * @param 	string	$mode			'gz' or 'bz' or 'zip'
 * @return	int						<0 if KO, >0 if OK
 */
function dol_compress_file($inputfile, $outputfile, $mode="gz")
{
	$foundhandler=0;

	try
	{
		$data = implode("", file(dol_osencode($inputfile)));
		if ($mode == 'gz')     { $foundhandler=1; $compressdata = gzencode($data, 9); }
		elseif ($mode == 'bz') { $foundhandler=1; $compressdata = bzcompress($data, 9); }
		elseif ($mode == 'zip')
		{
			if (defined('ODTPHP_PATHTOPCLZIP'))
			{
				$foundhandler=1;

				include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
				$archive = new PclZip($outputfile);
				$archive->add($inputfile, PCLZIP_OPT_REMOVE_PATH, dirname($inputfile));
				//$archive->add($inputfile);
				return 1;
			}
		}

		if ($foundhandler)
		{
			$fp = fopen($outputfile, "w");
			fwrite($fp, $compressdata);
			fclose($fp);
			return 1;
		}
		else
		{
			dol_syslog("Try to zip with format ".$mode." with no handler for this format",LOG_ERR);
			return -2;
		}
	}
	catch (Exception $e)
	{
		global $langs, $errormsg;
		$langs->load("errors");
		dol_syslog("Failed to open file ".$outputfile,LOG_ERR);
		$errormsg=$langs->trans("ErrorFailedToWriteInDir");
		return -1;
	}
}

/**
 * Uncompress a file
 *
 * @param 	string 	$inputfile		File to uncompress
 * @param 	string	$outputdir		Target dir name
 * @return 	array					array('error'=>'Error code') or array() if no error
 */
function dol_uncompress($inputfile,$outputdir)
{
	global $langs;

	if (defined('ODTPHP_PATHTOPCLZIP'))
	{
		dol_syslog("Constant ODTPHP_PATHTOPCLZIP for pclzip library is set to ".ODTPHP_PATHTOPCLZIP.", so we use Pclzip to unzip into ".$outputdir);
		include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
		$archive = new PclZip($inputfile);
		$result=$archive->extract(PCLZIP_OPT_PATH, $outputdir);
		//var_dump($result);
		if (! is_array($result) && $result <= 0) return array('error'=>$archive->errorInfo(true));
		else
		{
			$ok=1; $errmsg='';
			// Loop on each file to check result for unzipping file
			foreach($result as $key => $val)
			{
				if ($val['status'] == 'path_creation_fail')
				{
					$langs->load("errors");
					$ok=0;
					$errmsg=$langs->trans("ErrorFailToCreateDir", $val['filename']);
					break;
				}
			}

			if ($ok) return array();
			else return array('error'=>$errmsg);
		}
	}

	if (class_exists('ZipArchive'))
	{
		dol_syslog("Class ZipArchive is set so we unzip using ZipArchive to unzip into ".$outputdir);
		$zip = new ZipArchive;
		$res = $zip->open($inputfile);
		if ($res === TRUE)
		{
			$zip->extractTo($outputdir.'/');
			$zip->close();
			return array();
		}
		else
		{
			return array('error'=>'ErrUnzipFails');
		}
	}

	return array('error'=>'ErrNoZipEngine');
}


/**
 * Compress a directory and subdirectories into a package file.
 *
 * @param 	string	$inputdir		Source dir name
 * @param 	string	$outputfile		Target file name (output directory must exists and be writable)
 * @param 	string	$mode			'zip'
 * @return	int						<0 if KO, >0 if OK
 */
function dol_compress_dir($inputdir, $outputfile, $mode="zip")
{
	$foundhandler=0;

	dol_syslog("Try to zip dir ".$inputdir." into ".$outputdir." mode=".$mode);

	if (! dol_is_dir(dirname($outputfile)) || ! is_writable(dirname($outputfile)))
	{
		global $langs, $errormsg;
		$langs->load("errors");
		$errormsg=$langs->trans("ErrorFailedToWriteInDir",$outputfile);
		return -3;
	}

	try
	{
		if ($mode == 'gz')     { $foundhandler=0; }
		elseif ($mode == 'bz') { $foundhandler=0; }
		elseif ($mode == 'zip')
		{
			/*if (defined('ODTPHP_PATHTOPCLZIP'))
            {
                $foundhandler=0;        // TODO implement this

                include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
                $archive = new PclZip($outputfile);
                $archive->add($inputfile, PCLZIP_OPT_REMOVE_PATH, dirname($inputfile));
                //$archive->add($inputfile);
                return 1;
            }
            else*/
			if (class_exists('ZipArchive'))
			{
				$foundhandler=1;

				// Initialize archive object
				$zip = new ZipArchive();
				$result = $zip->open($outputfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

				// Create recursive directory iterator
				/** @var SplFileInfo[] $files */
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($inputdir),
					RecursiveIteratorIterator::LEAVES_ONLY
					);

				foreach ($files as $name => $file)
				{
					// Skip directories (they would be added automatically)
					if (!$file->isDir())
					{
						// Get real and relative path for current file
						$filePath = $file->getRealPath();
						$relativePath = substr($filePath, strlen($inputdir) + 1);

						// Add current file to archive
						$zip->addFile($filePath, $relativePath);
					}
				}

				// Zip archive will be created only after closing object
				$zip->close();

				return 1;
			}
		}

		if (! $foundhandler)
		{
			dol_syslog("Try to zip with format ".$mode." with no handler for this format",LOG_ERR);
			return -2;
		}
		else
		{
			return 0;
		}
	}
	catch (Exception $e)
	{
		global $langs, $errormsg;
		$langs->load("errors");
		dol_syslog("Failed to open file ".$outputfile, LOG_ERR);
		dol_syslog($e->getMessage(), LOG_ERR);
		$errormsg=$langs->trans("ErrorFailedToWriteInDir",$outputfile);
		return -1;
	}
}



/**
 * Return file(s) into a directory (by default most recent)
 *
 * @param 	string		$dir			Directory to scan
 * @param	string		$regexfilter	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	array		$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	int			$nohook			Disable all hooks
 * @param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
 * @return	string						Full path to most recent file
 */
function dol_most_recent_file($dir,$regexfilter='',$excludefilter=array('(\.meta|_preview.*\.png)$','^\.'),$nohook=false,$mode='')
{
	$tmparray=dol_dir_list($dir,'files',0,$regexfilter,$excludefilter,'date',SORT_DESC,$mode,$nohook);
	return $tmparray[0];
}

/**
 * Security check when accessing to a document (used by document.php, viewimage.php and webservices)
 *
 * @param	string	$modulepart			Module of document ('module', 'module_user_temp', 'module_user' or 'module_temp')
 * @param	string	$original_file		Relative path with filename, relative to modulepart.
 * @param	string	$entity				Restrict onto entity (0=no restriction)
 * @param  	User	$fuser				User object (forced)
 * @param	string	$refname			Ref of object to check permission for external users (autodetect if not provided)
 * @param   string  $mode               Check permission for 'read' or 'write'
 * @return	mixed						Array with access information : 'accessallowed' & 'sqlprotectagainstexternals' & 'original_file' (as a full path name)
 * @see restrictedArea
 */
function dol_check_secure_access_document($modulepart, $original_file, $entity, $fuser='', $refname='', $mode='read')
{
	global $conf, $db, $user;
	global $dolibarr_main_data_root, $dolibarr_main_document_root_alt;

	if (! is_object($fuser)) $fuser=$user;

	if (empty($modulepart)) return 'ErrorBadParameter';
	if (empty($entity))
	{
		if (empty($conf->multicompany->enabled)) $entity=1;
		else $entity=0;
	}
	dol_syslog('modulepart='.$modulepart.' original_file='.$original_file.' entity='.$entity);
	// We define $accessallowed and $sqlprotectagainstexternals
	$accessallowed=0;
	$sqlprotectagainstexternals='';
	$ret=array();

	// Find the subdirectory name as the reference. For exemple original_file='10/myfile.pdf' -> refname='10'
	if (empty($refname)) $refname=basename(dirname($original_file)."/");

	$relative_original_file = $original_file;

	// Define possible keys to use for permission check
	$lire='lire'; $read='read'; $download='download';
	if ($mode == 'write')
	{
		$lire='creer'; $read='write'; $download='upload';
	}

	// Wrapping for miscellaneous medias files
	if ($modulepart == 'medias' && !empty($dolibarr_main_data_root))
	{
		if (empty($entity) || empty($conf->medias->multidir_output[$entity])) return array('accessallowed'=>0, 'error'=>'Value entity must be provided');
		$accessallowed=1;
		$original_file=$conf->medias->multidir_output[$entity].'/'.$original_file;
	}
	// Wrapping for *.log files, like when used with url http://.../document.php?modulepart=logs&file=dolibarr.log
	elseif ($modulepart == 'logs' && !empty($dolibarr_main_data_root))
	{
		$accessallowed=($user->admin && basename($original_file) == $original_file && preg_match('/^dolibarr.*\.log$/', basename($original_file)));
		$original_file=$dolibarr_main_data_root.'/'.$original_file;
	}
	// Wrapping for *.zip files, like when used with url http://.../document.php?modulepart=packages&file=module_myfile.zip
	elseif ($modulepart == 'packages' && !empty($dolibarr_main_data_root))
	{
		// Dir for custom dirs
		$tmp=explode(',', $dolibarr_main_document_root_alt);
		$dirins = $tmp[0];

		$accessallowed=($user->admin && preg_match('/^module_.*\.zip$/', basename($original_file)));
		$original_file=$dirins.'/'.$original_file;
	}
	// Wrapping for some images
	elseif (($modulepart == 'mycompany' || $modulepart == 'companylogo') && !empty($conf->mycompany->dir_output))
	{
		$accessallowed=1;
		$original_file=$conf->mycompany->dir_output.'/logos/'.$original_file;
	}
	// Wrapping for users photos
	elseif ($modulepart == 'userphoto' && !empty($conf->user->dir_output))
	{
		$accessallowed=1;
		$original_file=$conf->user->dir_output.'/'.$original_file;
	}
	// Wrapping for members photos
	elseif ($modulepart == 'memberphoto' && !empty($conf->adherent->dir_output))
	{
		$accessallowed=1;
		$original_file=$conf->adherent->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu factures
	elseif ($modulepart == 'apercufacture' && !empty($conf->facture->dir_output))
	{
		if ($fuser->rights->facture->{$lire}) $accessallowed=1;
		$original_file=$conf->facture->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu propal
	elseif ($modulepart == 'apercupropal' && !empty($conf->propal->multidir_output[$entity]))
	{
		if ($fuser->rights->propale->{$lire}) $accessallowed=1;
		$original_file=$conf->propal->multidir_output[$entity].'/'.$original_file;
	}
	// Wrapping pour les apercu commande
	elseif ($modulepart == 'apercucommande' && !empty($conf->commande->dir_output))
	{
		if ($fuser->rights->commande->{$lire}) $accessallowed=1;
		$original_file=$conf->commande->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu intervention
	elseif (($modulepart == 'apercufichinter' || $modulepart == 'apercuficheinter') && !empty($conf->ficheinter->dir_output))
	{
		if ($fuser->rights->ficheinter->{$lire}) $accessallowed=1;
		$original_file=$conf->ficheinter->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu conat
	elseif (($modulepart == 'apercucontract') && !empty($conf->contrat->dir_output))
	{
		if ($fuser->rights->contrat->{$lire}) $accessallowed=1;
		$original_file=$conf->contrat->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu supplier proposal
	elseif (($modulepart == 'apercusupplier_proposal' || $modulepart == 'apercusupplier_proposal') && !empty($conf->supplier_proposal->dir_output))
	{
		if ($fuser->rights->supplier_proposal->{$lire}) $accessallowed=1;
		$original_file=$conf->supplier_proposal->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu supplier order
	elseif (($modulepart == 'apercusupplier_order' || $modulepart == 'apercusupplier_order') && !empty($conf->fournisseur->commande->dir_output))
	{
		if ($fuser->rights->fournisseur->commande->{$lire}) $accessallowed=1;
		$original_file=$conf->fournisseur->commande->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu supplier invoice
	elseif (($modulepart == 'apercusupplier_invoice' || $modulepart == 'apercusupplier_invoice') && !empty($conf->fournisseur->facture->dir_output))
	{
		if ($fuser->rights->fournisseur->facture->{$lire}) $accessallowed=1;
		$original_file=$conf->fournisseur->facture->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu supplier invoice
	elseif (($modulepart == 'apercuexpensereport') && !empty($conf->expensereport->dir_output))
	{
		if ($fuser->rights->expensereport->{$lire}) $accessallowed=1;
		$original_file=$conf->expensereport->dir_output.'/'.$original_file;
	}
	// Wrapping pour les images des stats propales
	elseif ($modulepart == 'propalstats' && !empty($conf->propal->multidir_temp[$entity]))
	{
		if ($fuser->rights->propale->{$lire}) $accessallowed=1;
		$original_file=$conf->propal->multidir_temp[$entity].'/'.$original_file;
	}
	// Wrapping pour les images des stats commandes
	elseif ($modulepart == 'orderstats' && !empty($conf->commande->dir_temp))
	{
		if ($fuser->rights->commande->{$lire}) $accessallowed=1;
		$original_file=$conf->commande->dir_temp.'/'.$original_file;
	}
	elseif ($modulepart == 'orderstatssupplier' && !empty($conf->fournisseur->dir_output))
	{
		if ($fuser->rights->fournisseur->commande->{$lire}) $accessallowed=1;
		$original_file=$conf->fournisseur->commande->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats factures
	elseif ($modulepart == 'billstats' && !empty($conf->facture->dir_temp))
	{
		if ($fuser->rights->facture->{$lire}) $accessallowed=1;
		$original_file=$conf->facture->dir_temp.'/'.$original_file;
	}
	elseif ($modulepart == 'billstatssupplier' && !empty($conf->fournisseur->dir_output))
	{
		if ($fuser->rights->fournisseur->facture->{$lire}) $accessallowed=1;
		$original_file=$conf->fournisseur->facture->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats expeditions
	elseif ($modulepart == 'expeditionstats' && !empty($conf->expedition->dir_temp))
	{
		if ($fuser->rights->expedition->{$lire}) $accessallowed=1;
		$original_file=$conf->expedition->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats expeditions
	elseif ($modulepart == 'tripsexpensesstats' && !empty($conf->deplacement->dir_temp))
	{
		if ($fuser->rights->deplacement->{$lire}) $accessallowed=1;
		$original_file=$conf->deplacement->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats expeditions
	elseif ($modulepart == 'memberstats' && !empty($conf->adherent->dir_temp))
	{
		if ($fuser->rights->adherent->{$lire}) $accessallowed=1;
		$original_file=$conf->adherent->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats produits
	elseif (preg_match('/^productstats_/i',$modulepart) && !empty($conf->product->dir_temp))
	{
		if ($fuser->rights->produit->{$lire} || $fuser->rights->service->{$lire}) $accessallowed=1;
		$original_file=(!empty($conf->product->multidir_temp[$entity])?$conf->product->multidir_temp[$entity]:$conf->service->multidir_temp[$entity]).'/'.$original_file;
	}
	// Wrapping for taxes
	elseif ($modulepart == 'tax' && !empty($conf->tax->dir_output))
	{
		if ($fuser->rights->tax->charges->{$lire}) $accessallowed=1;
		$original_file=$conf->tax->dir_output.'/'.$original_file;
	}
	// Wrapping for events
	elseif ($modulepart == 'actions' && !empty($conf->agenda->dir_output))
	{
		if ($fuser->rights->agenda->myactions->{$read}) $accessallowed=1;
		$original_file=$conf->agenda->dir_output.'/'.$original_file;
	}
	// Wrapping for categories
	elseif ($modulepart == 'category' && !empty($conf->categorie->dir_output))
	{
		if (empty($entity) || empty($conf->categorie->multidir_output[$entity])) return array('accessallowed'=>0, 'error'=>'Value entity must be provided');
		if ($fuser->rights->categorie->{$lire}) $accessallowed=1;
		$original_file=$conf->categorie->multidir_output[$entity].'/'.$original_file;
	}
	// Wrapping pour les prelevements
	elseif ($modulepart == 'prelevement' && !empty($conf->prelevement->dir_output))
	{
		if ($fuser->rights->prelevement->bons->{$lire} || preg_match('/^specimen/i',$original_file)) $accessallowed=1;
		$original_file=$conf->prelevement->dir_output.'/'.$original_file;
	}
	// Wrapping pour les graph energie
	elseif ($modulepart == 'graph_stock' && !empty($conf->stock->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->stock->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les graph fournisseurs
	elseif ($modulepart == 'graph_fourn' && !empty($conf->fournisseur->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->fournisseur->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les graph des produits
	elseif ($modulepart == 'graph_product' && !empty($conf->product->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->product->multidir_temp[$entity].'/'.$original_file;
	}
	// Wrapping pour les code barre
	elseif ($modulepart == 'barcode')
	{
		$accessallowed=1;
		// If viewimage is called for barcode, we try to output an image on the fly, with no build of file on disk.
		//$original_file=$conf->barcode->dir_temp.'/'.$original_file;
		$original_file='';
	}
	// Wrapping pour les icones de background des mailings
	elseif ($modulepart == 'iconmailing' && !empty($conf->mailing->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->mailing->dir_temp.'/'.$original_file;
	}
	// Wrapping pour le scanner
	elseif ($modulepart == 'scanner_user_temp' && !empty($conf->scanner->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->scanner->dir_temp.'/'.$fuser->id.'/'.$original_file;
	}
	// Wrapping pour les images fckeditor
	elseif ($modulepart == 'fckeditor' && !empty($conf->fckeditor->dir_output))
	{
		$accessallowed=1;
		$original_file=$conf->fckeditor->dir_output.'/'.$original_file;
	}

	// Wrapping for users
	else if ($modulepart == 'user' && !empty($conf->user->dir_output))
	{
		$canreaduser=(! empty($fuser->admin) || $fuser->rights->user->user->{$lire});
		if ($fuser->id == (int) $refname) { $canreaduser=1; } // A user can always read its own card
		if ($canreaduser || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->user->dir_output.'/'.$original_file;
	}

	// Wrapping for third parties
	else if (($modulepart == 'company' || $modulepart == 'societe') && !empty($conf->societe->dir_output))
	{
		if (empty($entity) || empty($conf->societe->multidir_output[$entity])) return array('accessallowed'=>0, 'error'=>'Value entity must be provided');
		if ($fuser->rights->societe->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->societe->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT rowid as fk_soc FROM ".MAIN_DB_PREFIX."societe WHERE rowid='".$db->escape($refname)."' AND entity IN (".getEntity('societe').")";
	}

	// Wrapping for contact
	else if ($modulepart == 'contact' && !empty($conf->societe->dir_output))
	{
		if (empty($entity) || empty($conf->societe->multidir_output[$entity])) return array('accessallowed'=>0, 'error'=>'Value entity must be provided');
		if ($fuser->rights->societe->{$lire})
		{
			$accessallowed=1;
		}
		$original_file=$conf->societe->multidir_output[$entity].'/contact/'.$original_file;
	}

	// Wrapping for invoices
	else if (($modulepart == 'facture' || $modulepart == 'invoice') && !empty($conf->facture->dir_output))
	{
		if ($fuser->rights->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}
	// Wrapping for mass actions
	else if ($modulepart == 'massfilesarea_proposals' && !empty($conf->propal->multidir_output[$entity]))
	{
		if ($fuser->rights->propal->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->propal->multidir_output[$entity].'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_orders')
	{
		if ($fuser->rights->commande->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->commande->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_invoices')
	{
		if ($fuser->rights->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->facture->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_expensereport')
	{
		if ($fuser->rights->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expensereport->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_interventions')
	{
		if ($fuser->rights->ficheinter->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->ficheinter->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_supplier_proposal' && !empty($conf->supplier_proposal->dir_output))
	{
		if ($fuser->rights->supplier_proposal->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->supplier_proposal->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_supplier_order')
	{
		if ($fuser->rights->fournisseur->commande->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->commande->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_supplier_invoice')
	{
		if ($fuser->rights->fournisseur->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->facture->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}
	else if ($modulepart == 'massfilesarea_contract' && !empty($conf->contrat->dir_output))
	{
		if ($fuser->rights->contrat->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->contrat->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	}

	// Wrapping for interventions
	else if (($modulepart == 'fichinter' || $modulepart == 'ficheinter') && !empty($conf->ficheinter->dir_output))
	{
		if ($fuser->rights->ficheinter->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->ficheinter->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les deplacements et notes de frais
	else if ($modulepart == 'deplacement' && !empty($conf->deplacement->dir_output))
	{
		if ($fuser->rights->deplacement->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->deplacement->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}
	// Wrapping pour les propales
	else if ($modulepart == 'propal' && !empty($conf->propal->multidir_output[$entity]))
	{
		if ($fuser->rights->propale->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->propal->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les commandes
	else if (($modulepart == 'commande' || $modulepart == 'order') && !empty($conf->commande->dir_output))
	{
		if ($fuser->rights->commande->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les projets
	else if ($modulepart == 'project' && !empty($conf->projet->dir_output))
	{
		if ($fuser->rights->projet->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->projet->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('project').")";
	}
	else if ($modulepart == 'project_task' && !empty($conf->projet->dir_output))
	{
		if ($fuser->rights->projet->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->projet->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('project').")";
	}

	// Wrapping pour les commandes fournisseurs
	else if (($modulepart == 'commande_fournisseur' || $modulepart == 'order_supplier') && !empty($conf->fournisseur->commande->dir_output))
	{
		if ($fuser->rights->fournisseur->commande->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les factures fournisseurs
	else if (($modulepart == 'facture_fournisseur' || $modulepart == 'invoice_supplier') && !empty($conf->fournisseur->facture->dir_output))
	{
		if ($fuser->rights->fournisseur->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn WHERE facnumber='".$db->escape($refname)."' AND entity=".$conf->entity;
	}
	// Wrapping pour les rapport de paiements
	else if ($modulepart == 'supplier_payment')
	{
		if ($fuser->rights->fournisseur->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->payment->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."paiementfournisseur WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les rapport de paiements
	else if ($modulepart == 'facture_paiement' && !empty($conf->facture->dir_output))
	{
		if ($fuser->rights->facture->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if ($fuser->societe_id > 0) $original_file=$conf->facture->dir_output.'/payments/private/'.$fuser->id.'/'.$original_file;
		else $original_file=$conf->facture->dir_output.'/payments/'.$original_file;
	}

	// Wrapping for accounting exports
	else if ($modulepart == 'export_compta' && !empty($conf->accounting->dir_output))
	{
		if ($fuser->rights->accounting->bind->write || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->accounting->dir_output.'/'.$original_file;
	}

	// Wrapping pour les expedition
	else if ($modulepart == 'expedition' && !empty($conf->expedition->dir_output))
	{
		if ($fuser->rights->expedition->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expedition->dir_output."/sending/".$original_file;
	}
	// Wrapping pour les bons de livraison
	else if ($modulepart == 'livraison' && !empty($conf->expedition->dir_output))
	{
		if ($fuser->rights->expedition->livraison->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expedition->dir_output."/receipt/".$original_file;
	}

	// Wrapping pour les actions
	else if ($modulepart == 'actions' && !empty($conf->agenda->dir_output))
	{
		if ($fuser->rights->agenda->myactions->{$read} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->agenda->dir_output.'/'.$original_file;
	}

	// Wrapping pour les actions
	else if ($modulepart == 'actionsreport' && !empty($conf->agenda->dir_temp))
	{
		if ($fuser->rights->agenda->allactions->{$read} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file = $conf->agenda->dir_temp."/".$original_file;
	}

	// Wrapping pour les produits et services
	else if ($modulepart == 'product' || $modulepart == 'produit' || $modulepart == 'service' || $modulepart == 'produit|service')
	{
		if (empty($entity) || (empty($conf->product->multidir_output[$entity]) && empty($conf->service->multidir_output[$entity]))) return array('accessallowed'=>0, 'error'=>'Value entity must be provided');
		if (($fuser->rights->produit->{$lire} || $fuser->rights->service->{$lire}) || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if (! empty($conf->product->enabled)) $original_file=$conf->product->multidir_output[$entity].'/'.$original_file;
		elseif (! empty($conf->service->enabled)) $original_file=$conf->service->multidir_output[$entity].'/'.$original_file;
	}

	// Wrapping pour les lots produits
	else if ($modulepart == 'product_batch' || $modulepart == 'produitlot')
	{
		if (empty($entity) || (empty($conf->productbatch->multidir_output[$entity]))) return array('accessallowed'=>0, 'error'=>'Value entity must be provided');
		if (($fuser->rights->produit->{$lire} ) || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if (! empty($conf->productbatch->enabled)) $original_file=$conf->productbatch->multidir_output[$entity].'/'.$original_file;
	}

	// Wrapping pour les contrats
	else if ($modulepart == 'contract' && !empty($conf->contrat->dir_output))
	{
		if ($fuser->rights->contrat->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->contrat->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."contrat WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('contract').")";
	}

	// Wrapping pour les dons
	else if ($modulepart == 'donation' && !empty($conf->don->dir_output))
	{
		if ($fuser->rights->don->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->don->dir_output.'/'.$original_file;
	}

	// Wrapping pour les dons
	else if ($modulepart == 'dolresource' && !empty($conf->resource->dir_output))
	{
		if ($fuser->rights->resource->{$read} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->resource->dir_output.'/'.$original_file;
	}

	// Wrapping pour les remises de cheques
	else if ($modulepart == 'remisecheque' && !empty($conf->banque->dir_output))
	{
		if ($fuser->rights->banque->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}

		$original_file=$conf->bank->dir_output.'/checkdeposits/'.$original_file;		// original_file should contains relative path so include the get_exdir result
	}

	// Wrapping for bank
	else if ($modulepart == 'bank' && !empty($conf->bank->dir_output))
	{
		if ($fuser->rights->banque->{$lire})
		{
			$accessallowed=1;
		}
		$original_file=$conf->bank->dir_output.'/'.$original_file;
	}

	// Wrapping for export module
	else if ($modulepart == 'export' && !empty($conf->export->dir_temp))
	{
		// Aucun test necessaire car on force le rep de download sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->export->dir_temp.'/'.$fuser->id.'/'.$original_file;
	}

	// Wrapping for import module
	else if ($modulepart == 'import' && !empty($conf->import->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->import->dir_temp.'/'.$original_file;
	}

	// Wrapping pour l'editeur wysiwyg
	else if ($modulepart == 'editor' && !empty($conf->fckeditor->dir_output))
	{
		$accessallowed=1;
		$original_file=$conf->fckeditor->dir_output.'/'.$original_file;
	}

	// Wrapping for backups
	else if ($modulepart == 'systemtools' && !empty($conf->admin->dir_output))
	{
		if ($fuser->admin) $accessallowed=1;
		$original_file=$conf->admin->dir_output.'/'.$original_file;
	}

	// Wrapping for upload file test
	else if ($modulepart == 'admin_temp' && !empty($conf->admin->dir_temp))
	{
		if ($fuser->admin) $accessallowed=1;
		$original_file=$conf->admin->dir_temp.'/'.$original_file;
	}

	// Wrapping pour BitTorrent
	else if ($modulepart == 'bittorrent' && !empty($conf->bittorrent->dir_output))
	{
		$accessallowed=1;
		$dir='files';
		if (dol_mimetype($original_file) == 'application/x-bittorrent') $dir='torrents';
		$original_file=$conf->bittorrent->dir_output.'/'.$dir.'/'.$original_file;
	}

	// Wrapping pour Foundation module
	else if ($modulepart == 'member' && !empty($conf->adherent->dir_output))
	{
		if ($fuser->rights->adherent->{$lire} || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->adherent->dir_output.'/'.$original_file;
	}

	// Wrapping for Scanner
	else if ($modulepart == 'scanner_user_temp' && !empty($conf->scanner->dir_temp))
	{
		$accessallowed=1;
		$original_file=$conf->scanner->dir_temp.'/'.$fuser->id.'/'.$original_file;
	}

	// GENERIC Wrapping
	// If modulepart=module_user_temp	Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/temp/iduser
	// If modulepart=module_temp		Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/temp
	// If modulepart=module_user		Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/iduser
	// If modulepart=module				Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart
	else
	{
		if (preg_match('/^specimen/i',$original_file))	$accessallowed=1;    // If link to a file called specimen. Test must be done before changing $original_file int full path.
		if ($fuser->admin) $accessallowed=1;    // If user is admin

		// Define $accessallowed
		if (preg_match('/^([a-z]+)_user_temp$/i',$modulepart,$reg))
		{
			if (empty($conf->{$reg[1]}->dir_temp))	// modulepart not supported
			{
				dol_print_error('','Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->rights->{$reg[1]}->{$lire} || $fuser->rights->{$reg[1]}->{$read} || ($fuser->rights->{$reg[1]}->{$download})) $accessallowed=1;
			$original_file=$conf->{$reg[1]}->dir_temp.'/'.$fuser->id.'/'.$original_file;
		}
		else if (preg_match('/^([a-z]+)_temp$/i',$modulepart,$reg))
		{
			if (empty($conf->{$reg[1]}->dir_temp))	// modulepart not supported
			{
				dol_print_error('','Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->rights->{$reg[1]}->{$lire} || $fuser->rights->{$reg[1]}->{$read} || ($fuser->rights->{$reg[1]}->{$download})) $accessallowed=1;
			$original_file=$conf->{$reg[1]}->dir_temp.'/'.$original_file;
		}
		else if (preg_match('/^([a-z]+)_user$/i',$modulepart,$reg))
		{
			if (empty($conf->{$reg[1]}->dir_output))	// modulepart not supported
			{
				dol_print_error('','Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->rights->{$reg[1]}->{$lire} || $fuser->rights->{$reg[1]}->{$read} || ($fuser->rights->{$reg[1]}->{$download})) $accessallowed=1;
			$original_file=$conf->{$reg[1]}->dir_output.'/'.$fuser->id.'/'.$original_file;
		}
		else
		{
			if (empty($conf->$modulepart->dir_output))	// modulepart not supported
			{
				dol_print_error('','Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}

			$perm=GETPOST('perm');
			$subperm=GETPOST('subperm');
			if ($perm || $subperm)
			{
				if (($perm && ! $subperm && $fuser->rights->$modulepart->$perm) || ($perm && $subperm && $fuser->rights->$modulepart->$perm->$subperm)) $accessallowed=1;
				$original_file=$conf->$modulepart->dir_output.'/'.$original_file;
			}
			else
			{
				if ($fuser->rights->$modulepart->{$lire} || $fuser->rights->$modulepart->{$read}) $accessallowed=1;
				$original_file=$conf->$modulepart->dir_output.'/'.$original_file;
			}
		}

		// For modules who wants to manage different levels of permissions for documents
		$subPermCategoryConstName = strtoupper($modulepart).'_SUBPERMCATEGORY_FOR_DOCUMENTS';
		if (! empty($conf->global->$subPermCategoryConstName))
		{
			$subPermCategory = $conf->global->$subPermCategoryConstName;
			if (! empty($subPermCategory) && (($fuser->rights->$modulepart->$subPermCategory->{$lire}) || ($fuser->rights->$modulepart->$subPermCategory->{$read}) || ($fuser->rights->$modulepart->$subPermCategory->{$download})))
			{
				$accessallowed=1;
			}
		}

		// Define $sqlprotectagainstexternals for modules who want to protect access using a SQL query.
		$sqlProtectConstName = strtoupper($modulepart).'_SQLPROTECTAGAINSTEXTERNALS_FOR_DOCUMENTS';
		if (! empty($conf->global->$sqlProtectConstName))	// If module want to define its own $sqlprotectagainstexternals
		{
			// Example: mymodule__SQLPROTECTAGAINSTEXTERNALS_FOR_DOCUMENTS = "SELECT fk_soc FROM ".MAIN_DB_PREFIX.$modulepart." WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
			eval('$sqlprotectagainstexternals = "'.$conf->global->$sqlProtectConstName.'";');
		}
	}

	$ret = array(
		'accessallowed' => $accessallowed,
		'sqlprotectagainstexternals'=>$sqlprotectagainstexternals,
		'original_file'=>$original_file
	);

	return $ret;
}

/**
 * Store object in file.
 *
 * @param string $directory Directory of cache
 * @param string $filename Name of filecache
 * @param mixed $object Object to store in cachefile
 * @return void
 */
function dol_filecache($directory, $filename, $object)
{
	if (! dol_is_dir($directory)) dol_mkdir($directory);
	$cachefile = $directory . $filename;
	file_put_contents($cachefile, serialize($object), LOCK_EX);
	@chmod($cachefile, 0644);
}

/**
 * Test if Refresh needed.
 *
 * @param string $directory Directory of cache
 * @param string $filename Name of filecache
 * @param int $cachetime Cachetime delay
 * @return boolean 0 no refresh 1 if refresh needed
 */
function dol_cache_refresh($directory, $filename, $cachetime)
{
	$now = dol_now();
	$cachefile = $directory . $filename;
	$refresh = !file_exists($cachefile) || ($now-$cachetime) > dol_filemtime($cachefile);
	return $refresh;
}

/**
 * Read object from cachefile.
 *
 * @param string $directory Directory of cache
 * @param string $filename Name of filecache
 * @return mixed Unserialise from file
 */
function dol_readcachefile($directory, $filename)
{
	$cachefile = $directory . $filename;
	$object = unserialize(file_get_contents($cachefile));
	return $object;
}


/**
 * Function to get list of updated or modified files.
 * $file_list is used as global variable
 *
 * @param	array				$file_list	        Array for response
 * @param   SimpleXMLElement	$dir    	        SimpleXMLElement of files to test
 * @param   string   			$path   	        Path of files relative to $pathref. We start with ''. Used by recursive calls.
 * @param   string              $pathref            Path ref (DOL_DOCUMENT_ROOT)
 * @param   array               $checksumconcat     Array of checksum
 * @return  array               			        Array of filenames
 */
function getFilesUpdated(&$file_list, SimpleXMLElement $dir, $path = '', $pathref = '', &$checksumconcat = array())
{
	global $conffile;

	$exclude = 'install';

	foreach ($dir->md5file as $file)    // $file is a simpleXMLElement
	{
		$filename = $path.$file['name'];
		$file_list['insignature'][] = $filename;
		$expectedmd5 = (string) $file;

		//if (preg_match('#'.$exclude.'#', $filename)) continue;

		if (!file_exists($pathref.'/'.$filename))
		{
			$file_list['missing'][] = array('filename'=>$filename, 'expectedmd5'=>$expectedmd5);
		}
		else
		{
			$md5_local = md5_file($pathref.'/'.$filename);

			if ($conffile == '/etc/dolibarr/conf.php' && $filename == '/filefunc.inc.php')	// For install with deb or rpm, we ignore test on filefunc.inc.php that was modified by package
			{
				$checksumconcat[] = $expectedmd5;
			}
			else
			{
				if ($md5_local != $expectedmd5) $file_list['updated'][] = array('filename'=>$filename, 'expectedmd5'=>$expectedmd5, 'md5'=>(string) $md5_local);
				$checksumconcat[] = $md5_local;
			}
		}
	}

	foreach ($dir->dir as $subdir)			// $subdir['name'] is  '' or '/accountancy/admin' for example
	{
		getFilesUpdated($file_list, $subdir, $path.$subdir['name'].'/', $pathref, $checksumconcat);
	}

	return $file_list;
}

