<?php
/* Copyright (C) 2008-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2021  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2023       Lenin Rivas         <lenin.rivas777@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *  \file		htdocs/core/lib/files.lib.php
 *  \brief		Library for file managing functions
 */

/**
 * Make a basename working with all page code (default PHP basenamed fails with cyrillic).
 * We suppose dir separator for input is '/'.
 *
 * @param	string	$pathfile	String to find basename.
 * @return	string				Basename of input
 */
function dol_basename($pathfile)
{
	return preg_replace('/^.*\/([^\/]+)$/', '$1', rtrim($pathfile, '/'));
}

/**
 * Scan a directory and return a list of files/directories.
 * Content for string is UTF8 and dir separator is "/".
 *
 * @param	string			$utf8_path     	Starting path from which to search. This is a full path.
 * @param	string			$types        	Can be "directories", "files", or "all"
 * @param	int				$recursive		Determines whether subdirectories are searched
 * @param	string			$filter        	Regex filter to restrict list. This regex value must be escaped for '/' by doing preg_quote($var,'/'), since this char is used for preg_match function,
 *                  	                    but must not contains the start and end '/'. Filter is checked into basename only.
 * @param	string|string[]	$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). Exclude is checked both into fullpath and into basename (So '^xxx' may exclude 'xxx/dirscanned/...' and dirscanned/xxx').
 * @param	string			$sortcriteria	Sort criteria ('','fullname','relativename','name','date','size')
 * @param	int 			$sortorder		Sort order (SORT_ASC, SORT_DESC)
 * @param	int				$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only, 4=Force load of perm
 * @param	int				$nohook			Disable all hooks
 * @param	string			$relativename	For recursive purpose only. Must be "" at first call.
 * @param	int 			$donotfollowsymlinks	Do not follow symbolic links
 * @param	int 			$nbsecondsold	Only files older than $nbsecondsold
 * @return	array<array{name:string,path:string,level1name:string,relativename:string,fullname:string,date:string,size:int,perm:int,type:string}> Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)>
 * @see dol_dir_list_in_database()
 */
function dol_dir_list($utf8_path, $types = "all", $recursive = 0, $filter = "", $excludefilter = null, $sortcriteria = "name", $sortorder = SORT_ASC, $mode = 0, $nohook = 0, $relativename = "", $donotfollowsymlinks = 0, $nbsecondsold = 0)
{
	global $db, $hookmanager;
	global $object;

	if ($recursive <= 1) {	// Avoid too verbose log
		// Verify filters (only on first call to function)
		$filters_ok = true;
		$error_info = "";
		// Ensure we have an array for the exclusions
		$exclude_array = ($excludefilter === null || $excludefilter === '') ? array() : (is_array($excludefilter) ? $excludefilter : array($excludefilter));
		foreach ((array($filter) + $exclude_array) as $f) {
			// Check that all '/' are escaped.
			if ((int) preg_match('/(?:^|[^\\\\])\//', $f) > 0) {
				$filters_ok = false;
				$error_info .= " error='$f unescaped_slash'";
				dol_syslog("'$f' has unescaped '/'", LOG_ERR);
			}
		}
		dol_syslog("files.lib.php::dol_dir_list path=".$utf8_path." types=".$types." recursive=".$recursive." filter=".$filter." excludefilter=".json_encode($excludefilter).$error_info);
		// print 'xxx'."files.lib.php::dol_dir_list path=".$utf8_path." types=".$types." recursive=".$recursive." filter=".$filter." excludefilter=".json_encode($exclude_array);
		if (!$filters_ok) {
			// Return empty array when filters are invalid
			return array();
		}
	} else {
		// Already computed before
		$exclude_array = ($excludefilter === null || $excludefilter === '') ? array() : (is_array($excludefilter) ? $excludefilter : array($excludefilter));
	}

	// Define excludefilterarray (before while, for speed)
	$excludefilterarray = array_merge(array('^\.'), $exclude_array);

	$loaddate = ($mode == 1 || $mode == 2 || $nbsecondsold != 0 || $sortcriteria == 'date');
	$loadsize = ($mode == 1 || $mode == 3 || $sortcriteria == 'size');
	$loadperm = ($mode == 1 || $mode == 4 || $sortcriteria == 'perm');

	// Clean parameters
	$utf8_path = preg_replace('/([\\/]+)$/', '', $utf8_path);
	$os_path = dol_osencode($utf8_path);
	$now = dol_now();

	$reshook = 0;
	$file_list = array();

	if (!$nohook && $hookmanager instanceof HookManager) {
		$hookmanager->resArray = array();

		$hookmanager->initHooks(array('fileslib'));

		$parameters = array(
			'path' => $os_path,
			'types' => $types,
			'recursive' => $recursive,
			'filter' => $filter,
			'excludefilter' => $exclude_array,  // Already converted to array.
			'sortcriteria' => $sortcriteria,
			'sortorder' => $sortorder,
			'loaddate' => $loaddate,
			'loadsize' => $loadsize,
			'mode' => $mode
		);
		$reshook = $hookmanager->executeHooks('getDirList', $parameters, $object);
	}

	// $hookmanager->resArray may contain array stacked by other modules
	if (empty($reshook)) {
		if (!is_dir($os_path)) {
			return array();
		}

		if (($dir = opendir($os_path)) === false) {
			return array();
		} else {
			$filedate = '';
			$filesize = '';
			$fileperm = '';

			while (false !== ($os_file = readdir($dir))) {        // $utf8_file is always a basename (in directory $os_path)
				$os_fullpathfile = ($os_path ? $os_path.'/' : '').$os_file;

				if (!utf8_check($os_file)) {
					$utf8_file = mb_convert_encoding($os_file, 'UTF-8', 'ISO-8859-1'); // Make sure data is stored in utf8 in memory
				} else {
					$utf8_file = $os_file;
				}

				$qualified = 1;

				$utf8_fullpathfile = "$utf8_path/$utf8_file";  // Temp variable for speed

				// Check if file is qualified
				foreach ($excludefilterarray as $filt) {
					if (preg_match('/'.$filt.'/i', $utf8_file) || preg_match('/'.$filt.'/i', $utf8_fullpathfile)) {
						$qualified = 0;
						break;
					}
				}
				//print $utf8_fullpathfile.' '.$utf8_file.' '.$qualified.'<br>';

				if ($qualified) {
					$isdir = is_dir($os_fullpathfile);
					// Check whether this is a file or directory and whether we're interested in that type
					if ($isdir) {
						// Add entry into file_list array
						if (($types == "directories") || ($types == "all")) {
							if ($loaddate || $sortcriteria == 'date') {
								$filedate = dol_filemtime($utf8_fullpathfile);
							}
							if ($loadsize || $sortcriteria == 'size') {
								$filesize = dol_filesize($utf8_fullpathfile);
							}
							if ($loadperm || $sortcriteria == 'perm') {
								$fileperm = dol_fileperm($utf8_fullpathfile);
							}

							if (!$filter || preg_match('/'.$filter.'/i', $utf8_file)) {	// We do not search key $filter into all $path, only into $file part
								$reg = array();
								preg_match('/([^\/]+)\/[^\/]+$/', $utf8_fullpathfile, $reg);
								$level1name = (isset($reg[1]) ? $reg[1] : '');
								$file_list[] = array(
									"name" => $utf8_file,
									"path" => $utf8_path,
									"level1name" => $level1name,
									"relativename" => ($relativename ? $relativename.'/' : '').$utf8_file,
									"fullname" => $utf8_fullpathfile,
									"date" => $filedate,
									"size" => $filesize,
									"perm" => $fileperm,
									"type" => 'dir'
								);
							}
						}

						// if we're in a directory and we want recursive behavior, call this function again
						if ($recursive > 0) {
							if (empty($donotfollowsymlinks) || !is_link($os_fullpathfile)) {
								//var_dump('eee '. $utf8_fullpathfile. ' '.is_dir($utf8_fullpathfile).' '.is_link($utf8_fullpathfile));
								$file_list = array_merge($file_list, dol_dir_list($utf8_fullpathfile, $types, $recursive + 1, $filter, $exclude_array, $sortcriteria, $sortorder, $mode, $nohook, ($relativename != '' ? $relativename.'/' : '').$utf8_file, $donotfollowsymlinks, $nbsecondsold));
							}
						}
					} elseif (in_array($types, array("files", "all"))) {
						// Add file into file_list array
						if ($loaddate || $sortcriteria == 'date') {
							$filedate = dol_filemtime($utf8_fullpathfile);
						}
						if ($loadsize || $sortcriteria == 'size') {
							$filesize = dol_filesize($utf8_fullpathfile);
						}

						if (!$filter || preg_match('/'.$filter.'/i', $utf8_file)) {	// We do not search key $filter into $utf8_path, only into $utf8_file
							if (empty($nbsecondsold) || $filedate <= ($now - $nbsecondsold)) {
								preg_match('/([^\/]+)\/[^\/]+$/', $utf8_fullpathfile, $reg);
								$level1name = (isset($reg[1]) ? $reg[1] : '');
								$file_list[] = array(
									"name" => $utf8_file,
									"path" => $utf8_path,
									"level1name" => $level1name,
									"relativename" => ($relativename ? $relativename.'/' : '').$utf8_file,
									"fullname" => $utf8_fullpathfile,
									"date" => $filedate,
									"size" => $filesize,
									"type" => 'file'
								);
							}
						}
					}
				}
			}
			closedir($dir);

			// Obtain a list of columns
			if (!empty($sortcriteria) && $sortorder) {
				$file_list = dol_sort_array($file_list, $sortcriteria, ($sortorder == SORT_ASC ? 'asc' : 'desc'));
			}
		}
	}

	if ($hookmanager instanceof HookManager && is_array($hookmanager->resArray)) {
		$file_list = array_merge($file_list, $hookmanager->resArray);
	}

	return $file_list;
}


/**
 * Scan a directory and return a list of files/directories.
 * Content for string is UTF8 and dir separator is "/".
 *
 * @param	string		$path        	Starting path from which to search. Example: 'produit/MYPROD'
 * @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	string[]|null	$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.'))
 * @param	string		$sortcriteria	Sort criteria ("","fullname","name","date","size")
 * @param	int			$sortorder		Sort order (SORT_ASC, SORT_DESC)
 * @param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like description
 * @return	array<array{rowid:string,label:string,name:string,path:string,level1name:string,fullname:string,fullpath_orig:string,date_c:string,date_m:string,type:string,keywords:string,cover:string,position:int,acl:string,share:string,description:string}> Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)
 * @see dol_dir_list()
 */
function dol_dir_list_in_database($path, $filter = "", $excludefilter = null, $sortcriteria = "name", $sortorder = SORT_ASC, $mode = 0)
{
	global $conf, $db;


	$sql = " SELECT rowid, label, entity, filename, filepath, fullpath_orig, keywords, cover, gen_or_uploaded, extraparams,";
	$sql .= " date_c, tms as date_m, fk_user_c, fk_user_m, acl, position, share";
	if ($mode) {
		$sql .= ", description";
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."ecm_files";
	$sql .= " WHERE entity = ".$conf->entity;
	if (preg_match('/%$/', $path)) {
		$sql .= " AND filepath LIKE '".$db->escape($path)."'";
	} else {
		$sql .= " AND filepath = '".$db->escape($path)."'";
	}

	$resql = $db->query($sql);
	if ($resql) {
		$file_list = array();
		$num = $db->num_rows($resql);
		$i = 0;
		while ($i < $num) {
			$obj = $db->fetch_object($resql);
			if ($obj) {
				$reg = array();
				preg_match('/([^\/]+)\/[^\/]+$/', DOL_DATA_ROOT.'/'.$obj->filepath.'/'.$obj->filename, $reg);
				$level1name = (isset($reg[1]) ? $reg[1] : '');
				$file_list[] = array(
					"rowid" => $obj->rowid,
					"label" => $obj->label, // md5
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
					"share" => $obj->share,
					"description" => ($mode ? $obj->description : '')
				);
			}
			$i++;
		}

		// Obtain a list of columns
		if (!empty($sortcriteria)) {
			$myarray = array();
			foreach ($file_list as $key => $row) {
				$myarray[$key] = (isset($row[$sortcriteria]) ? $row[$sortcriteria] : '');
			}
			// Sort the data
			if ($sortorder) {
				array_multisort($myarray, $sortorder, SORT_REGULAR, $file_list);
			}
		}

		return $file_list;
	} else {
		dol_print_error($db);
		return array();
	}
}


/**
 * Complete $filearray with data from database.
 * This will call doldir_list_indatabase to complete filearray.
 *
 * @param	array<array{name:string,path:string,level1name:string,relativename:string,fullname:string,date:string,size:int,perm:int,type:string}>	$filearray	Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...) Array of files obtained using dol_dir_list
 * @param	string	$relativedir		Relative dir from DOL_DATA_ROOT
 * @return	void
 */
function completeFileArrayWithDatabaseInfo(&$filearray, $relativedir)
{
	global $conf, $db, $user;

	$filearrayindatabase = dol_dir_list_in_database($relativedir, '', null, 'name', SORT_ASC);

	// TODO Remove this when PRODUCT_USE_OLD_PATH_FOR_PHOTO will be removed
	global $modulepart;
	if ($modulepart == 'produit' && getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
		global $object;
		if (!empty($object->id)) {
			if (isModEnabled("product")) {
				$upload_dirold = $conf->product->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
			} else {
				$upload_dirold = $conf->service->multidir_output[$object->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
			}

			$relativedirold = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dirold);
			$relativedirold = preg_replace('/^[\\/]/', '', $relativedirold);

			$filearrayindatabase = array_merge($filearrayindatabase, dol_dir_list_in_database($relativedirold, '', null, 'name', SORT_ASC));
		}
	} elseif ($modulepart == 'ticket') {
		foreach ($filearray as $key => $val) {
			$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filearray[$key]['path']);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);
			if ($rel_dir != $relativedir) {
				$filearrayindatabase = array_merge($filearrayindatabase, dol_dir_list_in_database($rel_dir, '', null, 'name', SORT_ASC));
			}
		}
	}

	//var_dump($relativedir);
	//var_dump($filearray);
	//var_dump($filearrayindatabase);

	// Complete filearray with properties found into $filearrayindatabase
	foreach ($filearray as $key => $val) {
		$tmpfilename = preg_replace('/\.noexe$/', '', $filearray[$key]['name']);
		$found = 0;
		// Search if it exists into $filearrayindatabase
		foreach ($filearrayindatabase as $key2 => $val2) {
			if (($filearrayindatabase[$key2]['path'] == $filearray[$key]['path']) && ($filearrayindatabase[$key2]['name'] == $tmpfilename)) {
				$filearray[$key]['position_name'] = ($filearrayindatabase[$key2]['position'] ? $filearrayindatabase[$key2]['position'] : '0').'_'.$filearrayindatabase[$key2]['name'];
				$filearray[$key]['position'] = $filearrayindatabase[$key2]['position'];
				$filearray[$key]['cover'] = $filearrayindatabase[$key2]['cover'];
				$filearray[$key]['keywords'] = $filearrayindatabase[$key2]['keywords'];
				$filearray[$key]['acl'] = $filearrayindatabase[$key2]['acl'];
				$filearray[$key]['rowid'] = $filearrayindatabase[$key2]['rowid'];
				$filearray[$key]['label'] = $filearrayindatabase[$key2]['label'];
				$filearray[$key]['share'] = $filearrayindatabase[$key2]['share'];
				$found = 1;
				break;
			}
		}

		if (!$found) {    // This happen in transition toward version 6, or if files were added manually into os dir.
			$filearray[$key]['position'] = '999999'; // File not indexed are at end. So if we add a file, it will not replace an existing position
			$filearray[$key]['cover'] = 0;
			$filearray[$key]['acl'] = '';
			$filearray[$key]['share'] = 0;

			$rel_filename = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filearray[$key]['fullname']);

			if (!preg_match('/([\\/]temp[\\/]|[\\/]thumbs|\.meta$)/', $rel_filename)) {     // If not a tmp file
				dol_syslog("list_of_documents We found a file called '".$filearray[$key]['name']."' not indexed into database. We add it");
				include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
				$ecmfile = new EcmFiles($db);

				// Add entry into database
				$filename = basename($rel_filename);
				$rel_dir = dirname($rel_filename);
				$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
				$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

				$ecmfile->filepath = $rel_dir;
				$ecmfile->filename = $filename;
				$ecmfile->label = md5_file(dol_osencode($filearray[$key]['fullname'])); // $destfile is a full path to file
				$ecmfile->fullpath_orig = $filearray[$key]['fullname'];
				$ecmfile->gen_or_uploaded = 'unknown';
				$ecmfile->description = ''; // indexed content
				$ecmfile->keywords = ''; // keyword content
				$result = $ecmfile->create($user);
				if ($result < 0) {
					setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
				} else {
					$filearray[$key]['rowid'] = $result;
				}
			} else {
				$filearray[$key]['rowid'] = 0; // Should not happened
			}
		}
	}
	//var_dump($filearray); var_dump($relativedir.' - tmpfilename='.$tmpfilename.' - found='.$found);
}


/**
 * Fast compare of 2 files identified by their properties ->name, ->date and ->size
 *
 * @param	object 	$a		File 1
 * @param 	object	$b		File 2
 * @return 	int				1, 0, 1
 */
function dol_compare_file($a, $b)
{
	global $sortorder, $sortfield;

	$sortorder = strtoupper($sortorder);

	if ($sortorder == 'ASC') {
		$retup = -1;
		$retdown = 1;
	} else {
		$retup = 1;
		$retdown = -1;
	}

	if ($sortfield == 'name') {
		if ($a->name == $b->name) {
			return 0;
		}
		return ($a->name < $b->name) ? $retup : $retdown;
	}
	if ($sortfield == 'date') {
		if ($a->date == $b->date) {
			return 0;
		}
		return ($a->date < $b->date) ? $retup : $retdown;
	}
	if ($sortfield == 'size') {
		if ($a->size == $b->size) {
			return 0;
		}
		return ($a->size < $b->size) ? $retup : $retdown;
	}

	return 0;
}


/**
 * Test if filename is a directory
 *
 * @param	string		$folder     Name of folder
 * @return	boolean     			True if it's a directory, False if not found
 */
function dol_is_dir($folder)
{
	$newfolder = dol_osencode($folder);
	if (is_dir($newfolder)) {
		return true;
	} else {
		return false;
	}
}

/**
 * Return if path is empty
 *
 * @param   string		$dir		Path of Directory
 * @return  boolean     		    True or false
 */
function dol_is_dir_empty($dir)
{
	if (!is_readable($dir)) {
		return false;
	}
	return (count(scandir($dir)) == 2);
}

/**
 * Return if path is a file
 *
 * @param   string		$pathoffile		Path of file
 * @return  boolean     			    True or false
 */
function dol_is_file($pathoffile)
{
	$newpathoffile = dol_osencode($pathoffile);
	return is_file($newpathoffile);
}

/**
 * Return if path is a symbolic link
 *
 * @param   string		$pathoffile		Path of file
 * @return  boolean     			    True or false
 */
function dol_is_link($pathoffile)
{
	$newpathoffile = dol_osencode($pathoffile);
	return is_link($newpathoffile);
}

/**
 * Test if directory or filename is writable
 *
 * @param	string		$folderorfile   Name of folder or filename
 * @return	boolean     				True if it's writable, False if not found
 */
function dol_is_writable($folderorfile)
{
	$newfolderorfile = dol_osencode($folderorfile);
	return is_writable($newfolderorfile);
}

/**
 * Return if path is an URI (the name of the method is misleading).
 *
 * URLs are addresses for websites, URI refer to online resources.
 *
 * @param   string		$uri	URI to test
 * @return  boolean      	   	True if the path looks like a URI, else false.
 */
function dol_is_url($uri)
{
	$prots = array('file', 'http', 'https', 'ftp', 'zlib', 'data', 'ssh', 'ssh2', 'ogg', 'expect');
	return false !== preg_match('/^('.implode('|', $prots).'):/i', $uri);
}

/**
 * 	Test if a folder is empty
 *
 * 	@param	string	$folder		Name of folder
 * 	@return boolean				True if dir is empty or non-existing, False if it contains files
 */
function dol_dir_is_emtpy($folder)
{
	$newfolder = dol_osencode($folder);
	if (is_dir($newfolder)) {
		$handle = opendir($newfolder);
		$folder_content = '';
		$name_array = [];
		while ((gettype($name = readdir($handle)) != "boolean")) {
			$name_array[] = $name;
		}
		foreach ($name_array as $temp) {
			$folder_content .= $temp;
		}

		closedir($handle);

		if ($folder_content == "...") {
			return true;
		} else {
			return false;
		}
	} else {
		return true; // Dir does not exists
	}
}

/**
 * 	Count number of lines in a file
 *
 * 	@param	string	$file		Filename
 * 	@return int					Return integer <0 if KO, Number of lines in files if OK
 *  @see dol_nboflines()
 */
function dol_count_nb_of_line($file)
{
	$nb = 0;

	$newfile = dol_osencode($file);
	//print 'x'.$file;
	$fp = fopen($newfile, 'r');
	if ($fp) {
		while (!feof($fp)) {
			$line = fgets($fp);
			// Increase count only if read was success.
			// Test needed  because feof returns true only after fgets
			//   so we do n+1 fgets for a file with n lines.
			if ($line !== false) {
				$nb++;
			}
		}
		fclose($fp);
	} else {
		$nb = -1;
	}

	return $nb;
}


/**
 * Return size of a file
 *
 * @param 	string		$pathoffile		Path of file
 * @return 	integer						File size
 * @see dol_print_size()
 */
function dol_filesize($pathoffile)
{
	$newpathoffile = dol_osencode($pathoffile);
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
	$newpathoffile = dol_osencode($pathoffile);
	return @filemtime($newpathoffile); // @Is to avoid errors if files does not exists
}

/**
 * Return permissions of a file
 *
 * @param 	string		$pathoffile		Path of file
 * @return 	integer						File permissions
 */
function dol_fileperm($pathoffile)
{
	$newpathoffile = dol_osencode($pathoffile);
	return fileperms($newpathoffile);
}

/**
 * Make replacement of strings into a file.
 *
 * @param	string					$srcfile			       Source file (can't be a directory)
 * @param	array<string,string>	$arrayreplacement	       Array with strings to replace. Example: array('valuebefore'=>'valueafter', ...)
 * @param	string					$destfile			       Destination file (can't be a directory). If empty, will be same than source file.
 * @param	string					$newmask			       Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param	int						$indexdatabase		       1=index new file into database.
 * @param   int     				$arrayreplacementisregex   1=Array of replacement is already an array with key that is a regex. Warning: the key must be escaped with preg_quote for '/'
 * @return	int											       Return integer <0 if error, 0 if nothing done (dest file already exists), >0 if OK
 * @see		dol_copy(), dolCopyDir()
 */
function dolReplaceInFile($srcfile, $arrayreplacement, $destfile = '', $newmask = '0', $indexdatabase = 0, $arrayreplacementisregex = 0)
{
	dol_syslog("files.lib.php::dolReplaceInFile srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." indexdatabase=".$indexdatabase." arrayreplacementisregex=".$arrayreplacementisregex);

	if (empty($srcfile)) {
		return -1;
	}
	if (empty($destfile)) {
		$destfile = $srcfile;
	}

	// Clean the aa/bb/../cc into aa/cc
	$srcfile = preg_replace('/\.\.\/?/', '', $srcfile);
	$destfile = preg_replace('/\.\.\/?/', '', $destfile);

	$destexists = dol_is_file($destfile);
	if (($destfile != $srcfile) && $destexists) {
		return 0;
	}

	$srcexists = dol_is_file($srcfile);
	if (!$srcexists) {
		dol_syslog("files.lib.php::dolReplaceInFile failed to read src file", LOG_WARNING);
		return -3;
	}

	$tmpdestfile = $destfile.'.tmp';

	$newpathofsrcfile = dol_osencode($srcfile);
	$newpathoftmpdestfile = dol_osencode($tmpdestfile);
	$newpathofdestfile = dol_osencode($destfile);
	$newdirdestfile = dirname($newpathofdestfile);

	if ($destexists && !is_writable($newpathofdestfile)) {
		dol_syslog("files.lib.php::dolReplaceInFile failed Permission denied to overwrite target file", LOG_WARNING);
		return -1;
	}
	if (!is_writable($newdirdestfile)) {
		dol_syslog("files.lib.php::dolReplaceInFile failed Permission denied to write into target directory ".$newdirdestfile, LOG_WARNING);
		return -2;
	}

	dol_delete_file($tmpdestfile);

	// Create $newpathoftmpdestfile from $newpathofsrcfile
	$content = file_get_contents($newpathofsrcfile);

	if (empty($arrayreplacementisregex)) {
		$content = make_substitutions($content, $arrayreplacement, null);
	} else {
		foreach ($arrayreplacement as $key => $value) {
			$content = preg_replace($key, $value, $content);
		}
	}

	file_put_contents($newpathoftmpdestfile, $content);
	dolChmod($newpathoftmpdestfile, $newmask);

	// Rename
	$result = dol_move($newpathoftmpdestfile, $newpathofdestfile, $newmask, (($destfile == $srcfile) ? 1 : 0), 0, $indexdatabase);
	if (!$result) {
		dol_syslog("files.lib.php::dolReplaceInFile failed to move tmp file to final dest", LOG_WARNING);
		return -3;
	}
	if (empty($newmask) && getDolGlobalString('MAIN_UMASK')) {
		$newmask = getDolGlobalString('MAIN_UMASK');
	}
	if (empty($newmask)) {	// This should no happen
		dol_syslog("Warning: dolReplaceInFile called with empty value for newmask and no default value defined", LOG_WARNING);
		$newmask = '0664';
	}

	dolChmod($newpathofdestfile, $newmask);

	return 1;
}


/**
 * Copy a file to another file.
 *
 * @param	string	$srcfile			Source file (can't be a directory)
 * @param	string	$destfile			Destination file (can't be a directory)
 * @param	string	$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param 	int		$overwriteifexists	Overwrite file if exists (1 by default)
 * @param   int     $testvirus          Do an antivirus test. Move is canceled if a virus is found.
 * @param	int		$indexdatabase		Index new file into database.
 * @return	int							Return integer <0 if error, 0 if nothing done (dest file already exists and overwriteifexists=0), >0 if OK
 * @see		dol_delete_file(), dolCopyDir()
 */
function dol_copy($srcfile, $destfile, $newmask = '0', $overwriteifexists = 1, $testvirus = 0, $indexdatabase = 0)
{
	global $db, $user;

	dol_syslog("files.lib.php::dol_copy srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwriteifexists=".$overwriteifexists);

	if (empty($srcfile) || empty($destfile)) {
		return -1;
	}

	$destexists = dol_is_file($destfile);
	if (!$overwriteifexists && $destexists) {
		return 0;
	}

	$newpathofsrcfile = dol_osencode($srcfile);
	$newpathofdestfile = dol_osencode($destfile);
	$newdirdestfile = dirname($newpathofdestfile);

	if ($destexists && !is_writable($newpathofdestfile)) {
		dol_syslog("files.lib.php::dol_copy failed Permission denied to overwrite target file", LOG_WARNING);
		return -1;
	}
	if (!is_writable($newdirdestfile)) {
		dol_syslog("files.lib.php::dol_copy failed Permission denied to write into target directory ".$newdirdestfile, LOG_WARNING);
		return -2;
	}

	// Check virus
	$testvirusarray = array();
	if ($testvirus) {
		$testvirusarray = dolCheckVirus($srcfile, $destfile);
		if (count($testvirusarray)) {
			dol_syslog("files.lib.php::dol_copy canceled because a virus was found into source file. we ignore the copy request.", LOG_WARNING);
			return -3;
		}
	}

	// Copy with overwriting if exists
	$result = @copy($newpathofsrcfile, $newpathofdestfile);
	//$result=copy($newpathofsrcfile, $newpathofdestfile);	// To see errors, remove @
	if (!$result) {
		dol_syslog("files.lib.php::dol_copy failed to copy", LOG_WARNING);
		return -3;
	}
	if (empty($newmask) && getDolGlobalString('MAIN_UMASK')) {
		$newmask = getDolGlobalString('MAIN_UMASK');
	}
	if (empty($newmask)) {	// This should no happen
		dol_syslog("Warning: dol_copy called with empty value for newmask and no default value defined", LOG_WARNING);
		$newmask = '0664';
	}

	dolChmod($newpathofdestfile, $newmask);

	if ($result && $indexdatabase) {
		// Add entry into ecm database
		$rel_filetocopyafter = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $newpathofdestfile);
		if (!preg_match('/([\\/]temp[\\/]|[\\/]thumbs|\.meta$)/', $rel_filetocopyafter)) {     // If not a tmp file
			$rel_filetocopyafter = preg_replace('/^[\\/]/', '', $rel_filetocopyafter);
			//var_dump($rel_filetorenamebefore.' - '.$rel_filetocopyafter);exit;

			dol_syslog("Try to copy also entries in database for: ".$rel_filetocopyafter, LOG_DEBUG);
			include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

			$ecmfiletarget = new EcmFiles($db);
			$resultecmtarget = $ecmfiletarget->fetch(0, '', $rel_filetocopyafter);
			if ($resultecmtarget > 0) {   // An entry for target name already exists for target, we delete it, a new one will be created.
				dol_syslog("ECM dest file found, remove it", LOG_DEBUG);
				$ecmfiletarget->delete($user);
			} else {
				dol_syslog("ECM dest file not found, create it", LOG_DEBUG);
			}

			$ecmSrcfile = new EcmFiles($db);
			$resultecm  = $ecmSrcfile->fetch(0, '', $srcfile);
			if ($resultecm) {
				dol_syslog("Fetch src file ok", LOG_DEBUG);
			} else {
				dol_syslog("Fetch src file error", LOG_DEBUG);
			}

			$ecmfile = new EcmFiles($db);
			$filename = basename($rel_filetocopyafter);
			$rel_dir = dirname($rel_filetocopyafter);
			$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
			$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

			$ecmfile->filepath = $rel_dir;
			$ecmfile->filename = $filename;
			$ecmfile->label = md5_file(dol_osencode($destfile)); // $destfile is a full path to file
			$ecmfile->fullpath_orig = $srcfile;
			$ecmfile->gen_or_uploaded = 'copy';
			$ecmfile->description = $ecmSrcfile->description;
			$ecmfile->keywords = $ecmSrcfile->keywords;
			$resultecm = $ecmfile->create($user);
			if ($resultecm < 0) {
				dol_syslog("Create ECM file ok", LOG_DEBUG);
				setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
			} else {
				dol_syslog("Create ECM file error", LOG_DEBUG);
				setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
			}

			if ($resultecm > 0) {
				$result = 1;
			} else {
				$result = -1;
			}
		}
	}

	return (int) $result;
}

/**
 * Copy a dir to another dir. This include recursive subdirectories.
 *
 * @param	string					$srcfile				Source file (a directory)
 * @param	string					$destfile				Destination file (a directory)
 * @param	string					$newmask				Mask for new file ('0' by default means getDolGlobalString('MAIN_UMASK')). Example: '0666'
 * @param 	int						$overwriteifexists		Overwrite file if exists (1 by default)
 * @param	array<string,string>	$arrayreplacement		Array to use to replace filenames with another one during the copy (works only on file names, not on directory names).
 * @param	int						$excludesubdir			0=Do not exclude subdirectories, 1=Exclude subdirectories, 2=Exclude subdirectories if name is not a 2 chars (used for country codes subdirectories).
 * @param	string[]				$excludefileext			Exclude some file extensions
 * @param	int						$excludearchivefiles	Exclude archive files that begin with v+timestamp or d+timestamp (0 by default)
 * @return	int												Return integer <0 if error, 0 if nothing done (all files already exists and overwriteifexists=0), >0 if OK
 * @see		dol_copy()
 */
function dolCopyDir($srcfile, $destfile, $newmask, $overwriteifexists, $arrayreplacement = null, $excludesubdir = 0, $excludefileext = null, $excludearchivefiles = 0)
{
	$result = 0;

	dol_syslog("files.lib.php::dolCopyDir srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwriteifexists=".$overwriteifexists);

	if (empty($srcfile) || empty($destfile)) {
		return -1;
	}

	$destexists = dol_is_dir($destfile);

	//if (! $overwriteifexists && $destexists) return 0;	// The overwriteifexists is for files only, so propagated to dol_copy only.

	if (!$destexists) {
		// We must set mask just before creating dir, because it can be set differently by dol_copy
		umask(0);
		$dirmaskdec = octdec($newmask);
		if (empty($newmask) && getDolGlobalString('MAIN_UMASK')) {
			$dirmaskdec = octdec(getDolGlobalString('MAIN_UMASK'));
		}
		$dirmaskdec |= octdec('0200'); // Set w bit required to be able to create content for recursive subdirs files

		$result = dol_mkdir($destfile, '', decoct($dirmaskdec));

		if (!dol_is_dir($destfile)) {
			// The output directory does not exists and we failed to create it. So we stop here.
			return -1;
		}
	}

	$ossrcfile = dol_osencode($srcfile);
	$osdestfile = dol_osencode($destfile);

	// Recursive function to copy all subdirectories and contents:
	if (is_dir($ossrcfile)) {
		$dir_handle = opendir($ossrcfile);
		while ($file = readdir($dir_handle)) {
			if ($file != "." && $file != ".." && !is_link($ossrcfile."/".$file)) {
				if (is_dir($ossrcfile."/".$file)) {
					if (empty($excludesubdir) || ($excludesubdir == 2 && strlen($file) == 2)) {
						$newfile = $file;
						// Replace destination filename with a new one
						if (is_array($arrayreplacement)) {
							foreach ($arrayreplacement as $key => $val) {
								$newfile = str_replace($key, $val, $newfile);
							}
						}
						//var_dump("xxx dolCopyDir $srcfile/$file, $destfile/$file, $newmask, $overwriteifexists");
						$tmpresult = dolCopyDir($srcfile."/".$file, $destfile."/".$newfile, $newmask, $overwriteifexists, $arrayreplacement, $excludesubdir, $excludefileext, $excludearchivefiles);
					}
				} else {
					$newfile = $file;

					if (is_array($excludefileext)) {
						$extension = pathinfo($file, PATHINFO_EXTENSION);
						if (in_array($extension, $excludefileext)) {
							//print "We exclude the file ".$file." because its extension is inside list ".join(', ', $excludefileext); exit;
							continue;
						}
					}

					if ($excludearchivefiles == 1) {
						$extension = pathinfo($file, PATHINFO_EXTENSION);
						if (preg_match('/^[v|d]\d+$/', $extension)) {
							continue;
						}
					}

					// Replace destination filename with a new one
					if (is_array($arrayreplacement)) {
						foreach ($arrayreplacement as $key => $val) {
							$newfile = str_replace($key, $val, $newfile);
						}
					}
					$tmpresult = dol_copy($srcfile."/".$file, $destfile."/".$newfile, $newmask, $overwriteifexists);
				}
				// Set result
				if ($result > 0 && $tmpresult >= 0) {
					// Do nothing, so we don't set result to 0 if tmpresult is 0 and result was success in a previous pass
				} else {
					$result = $tmpresult;
				}
				if ($result < 0) {
					break;
				}
			}
		}
		closedir($dir_handle);
	} else {
		// Source directory does not exists
		$result = -2;
	}

	return (int) $result;
}


/**
 * Move a file into another name.
 * Note:
 *  - This function differs from dol_move_uploaded_file, because it can be called in any context.
 *  - Database indexes for files are updated.
 *  - Test on virus is done only if param testvirus is provided and an antivirus was set.
 *
 * @param	string  $srcfile            Source file (can't be a directory. use native php @rename() to move a directory)
 * @param   string	$destfile           Destination file (can't be a directory. use native php @rename() to move a directory)
 * @param   string	$newmask            Mask in octal string for new file ('0' by default means $conf->global->MAIN_UMASK)
 * @param   int		$overwriteifexists  Overwrite file if exists (1 by default)
 * @param   int     $testvirus          Do an antivirus test. Move is canceled if a virus is found.
 * @param	int		$indexdatabase		Index new file into database.
 * @param	array	$moreinfo			Array with more information to set in index table
 * @return  boolean 		            True if OK, false if KO
 * @see dol_move_uploaded_file()
 */
function dol_move($srcfile, $destfile, $newmask = '0', $overwriteifexists = 1, $testvirus = 0, $indexdatabase = 1, $moreinfo = array())
{
	global $user, $db, $conf;
	$result = false;

	dol_syslog("files.lib.php::dol_move srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwritifexists=".$overwriteifexists);
	$srcexists = dol_is_file($srcfile);
	$destexists = dol_is_file($destfile);

	if (!$srcexists) {
		dol_syslog("files.lib.php::dol_move srcfile does not exists. we ignore the move request.");
		return false;
	}

	if ($overwriteifexists || !$destexists) {
		$newpathofsrcfile = dol_osencode($srcfile);
		$newpathofdestfile = dol_osencode($destfile);

		// Check on virus
		$testvirusarray = array();
		if ($testvirus) {
			// Check using filename + antivirus
			$testvirusarray = dolCheckVirus($newpathofsrcfile, $newpathofdestfile);
			if (count($testvirusarray)) {
				dol_syslog("files.lib.php::dol_move canceled because a virus was found into source file. We ignore the move request.", LOG_WARNING);
				return false;
			}
		} else {
			// Check using filename only
			$testvirusarray = dolCheckOnFileName($newpathofsrcfile, $newpathofdestfile);
			if (count($testvirusarray)) {
				dol_syslog("files.lib.php::dol_move canceled because a virus was found into source file. We ignore the move request.", LOG_WARNING);
				return false;
			}
		}

		global $dolibarr_main_restrict_os_commands;
		if (!empty($dolibarr_main_restrict_os_commands)) {
			$arrayofallowedcommand = explode(',', $dolibarr_main_restrict_os_commands);
			$arrayofallowedcommand = array_map('trim', $arrayofallowedcommand);
			if (in_array(basename($destfile), $arrayofallowedcommand)) {
				//$langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
				//setEventMessages($langs->trans("ErrorFilenameReserved", basename($destfile)), null, 'errors');
				dol_syslog("files.lib.php::dol_move canceled because target filename ".basename($destfile)." is using a reserved command name. we ignore the move request.", LOG_WARNING);
				return false;
			}
		}

		$result = @rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
		if (!$result) {
			if ($destexists) {
				dol_syslog("files.lib.php::dol_move Failed. We try to delete target first and move after.", LOG_WARNING);
				// We force delete and try again. Rename function sometimes fails to replace dest file with some windows NTFS partitions.
				dol_delete_file($destfile);
				$result = @rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
			} else {
				dol_syslog("files.lib.php::dol_move Failed.", LOG_WARNING);
			}
		}

		// Move ok
		if ($result && $indexdatabase) {
			// Rename entry into ecm database
			$rel_filetorenamebefore = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $srcfile);
			$rel_filetorenameafter = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $destfile);
			if (!preg_match('/([\\/]temp[\\/]|[\\/]thumbs|\.meta$)/', $rel_filetorenameafter)) {     // If not a tmp file
				$rel_filetorenamebefore = preg_replace('/^[\\/]/', '', $rel_filetorenamebefore);
				$rel_filetorenameafter = preg_replace('/^[\\/]/', '', $rel_filetorenameafter);
				//var_dump($rel_filetorenamebefore.' - '.$rel_filetorenameafter);exit;

				dol_syslog("Try to rename also entries in database for full relative path before = ".$rel_filetorenamebefore." after = ".$rel_filetorenameafter, LOG_DEBUG);
				include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';

				$ecmfiletarget = new EcmFiles($db);
				$resultecmtarget = $ecmfiletarget->fetch(0, '', $rel_filetorenameafter);
				if ($resultecmtarget > 0) {   // An entry for target name already exists for target, we delete it, a new one will be created.
					$ecmfiletarget->delete($user);
				}

				$ecmfile = new EcmFiles($db);
				$resultecm = $ecmfile->fetch(0, '', $rel_filetorenamebefore);
				if ($resultecm > 0) {   // If an entry was found for src file, we use it to move entry
					$filename = basename($rel_filetorenameafter);
					$rel_dir = dirname($rel_filetorenameafter);
					$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
					$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

					$ecmfile->filepath = $rel_dir;
					$ecmfile->filename = $filename;

					$resultecm = $ecmfile->update($user);
				} elseif ($resultecm == 0) {   // If no entry were found for src files, create/update target file
					$filename = basename($rel_filetorenameafter);
					$rel_dir = dirname($rel_filetorenameafter);
					$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
					$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

					$ecmfile->filepath = $rel_dir;
					$ecmfile->filename = $filename;
					$ecmfile->label = md5_file(dol_osencode($destfile)); // $destfile is a full path to file
					$ecmfile->fullpath_orig = basename($srcfile);
					$ecmfile->gen_or_uploaded = 'uploaded';
					if (!empty($moreinfo) && !empty($moreinfo['description'])) {
						$ecmfile->description = $moreinfo['description']; // indexed content
					} else {
						$ecmfile->description = ''; // indexed content
					}
					if (!empty($moreinfo) && !empty($moreinfo['keywords'])) {
						$ecmfile->keywords = $moreinfo['keywords']; // indexed content
					} else {
						$ecmfile->keywords = ''; // keyword content
					}
					if (!empty($moreinfo) && !empty($moreinfo['note_private'])) {
						$ecmfile->note_private = $moreinfo['note_private'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['note_public'])) {
						$ecmfile->note_public = $moreinfo['note_public'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['src_object_type'])) {
						$ecmfile->src_object_type = $moreinfo['src_object_type'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['src_object_id'])) {
						$ecmfile->src_object_id = $moreinfo['src_object_id'];
					}

					$resultecm = $ecmfile->create($user);
					if ($resultecm < 0) {
						setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					}
				} elseif ($resultecm < 0) {
					setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
				}

				if ($resultecm > 0) {
					$result = true;
				} else {
					$result = false;
				}
			}
		}

		if (empty($newmask)) {
			$newmask = getDolGlobalString('MAIN_UMASK', '0755');
		}

		// Currently method is restricted to files (dol_delete_files previously used is for files, and mask usage if for files too)
		// to allow mask usage for dir, we should introduce a new param "isdir" to 1 to complete newmask like this
		// if ($isdir) $newmaskdec |= octdec('0111');  // Set x bit required for directories
		dolChmod($newpathofdestfile, $newmask);
	}

	return $result;
}

/**
 * Move a directory into another name.
 *
 * @param	string	$srcdir 			Source directory
 * @param	string 	$destdir			Destination directory
 * @param	int		$overwriteifexists	Overwrite directory if it already exists (1 by default)
 * @param	int		$indexdatabase		Index new name of files into database.
 * @param	int		$renamedircontent	Also rename contents inside srcdir after the move to match new destination name.
 * @return  boolean 					True if OK, false if KO
 */
function dol_move_dir($srcdir, $destdir, $overwriteifexists = 1, $indexdatabase = 1, $renamedircontent = 1)
{
	$result = false;

	dol_syslog("files.lib.php::dol_move_dir srcdir=".$srcdir." destdir=".$destdir." overwritifexists=".$overwriteifexists." indexdatabase=".$indexdatabase." renamedircontent=".$renamedircontent);
	$srcexists = dol_is_dir($srcdir);
	$srcbasename = basename($srcdir);
	$destexists = dol_is_dir($destdir);

	if (!$srcexists) {
		dol_syslog("files.lib.php::dol_move_dir srcdir does not exists. Move fails");
		return false;
	}

	if ($overwriteifexists || !$destexists) {
		$newpathofsrcdir = dol_osencode($srcdir);
		$newpathofdestdir = dol_osencode($destdir);

		// On windows, if destination directory exists and is empty, command fails. So if overwrite is on, we first remove destination directory.
		// On linux, if destination directory exists and is empty, command succeed. So no need to delete di destination directory first.
		// Note: If dir exists and is not empty, it will and must fail on both linux and windows even, if option $overwriteifexists is on.
		if ($overwriteifexists) {
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
				if (is_dir($newpathofdestdir)) {
					@rmdir($newpathofdestdir);
				}
			}
		}

		$result = @rename($newpathofsrcdir, $newpathofdestdir);

		// Now rename contents in the directory after the move to match the new destination
		if ($result && $renamedircontent) {
			if (file_exists($newpathofdestdir)) {
				$destbasename = basename($newpathofdestdir);
				$files = dol_dir_list($newpathofdestdir);
				if (!empty($files) && is_array($files)) {
					foreach ($files as $key => $file) {
						if (!file_exists($file["fullname"])) {
							continue;
						}
						$filepath = $file["path"];
						$oldname = $file["name"];

						$newname = str_replace($srcbasename, $destbasename, $oldname);
						if (!empty($newname) && $newname !== $oldname) {
							if ($file["type"] == "dir") {
								$res = dol_move_dir($filepath.'/'.$oldname, $filepath.'/'.$newname, $overwriteifexists, $indexdatabase, $renamedircontent);
							} else {
								$res = dol_move($filepath.'/'.$oldname, $filepath.'/'.$newname, 0, $overwriteifexists, 0, $indexdatabase);
							}
							if (!$res) {
								return $result;
							}
						}
					}
					$result = true;
				}
			}
		}
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
 * @param   string      $dest_file      Destination file name (to know the expected type)
 * @return  string[]                    Array of errors, or empty array if not virus found
 */
function dolCheckVirus($src_file, $dest_file = '')
{
	global $db;

	$reterrors = dolCheckOnFileName($src_file, $dest_file);
	if (!empty($reterrors)) {
		return $reterrors;
	}

	if (getDolGlobalString('MAIN_ANTIVIRUS_COMMAND')) {
		if (!class_exists('AntiVir')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/antivir.class.php';
		}
		$antivir = new AntiVir($db);
		$result = $antivir->dol_avscan_file($src_file);
		if ($result < 0) {	// If virus or error, we stop here
			$reterrors = $antivir->errors;
			return $reterrors;
		}
	}
	return array();
}

/**
 * Check virus into a file
 *
 * @param   string      $src_file       Source file to check
 * @param   string      $dest_file      Destination file name (to know the expected type)
 * @return  string[]                    Array of errors, or empty array if not virus found
 */
function dolCheckOnFileName($src_file, $dest_file = '')
{
	if (preg_match('/\.pdf$/i', $dest_file)) {
		if (!getDolGlobalString('MAIN_ANTIVIRUS_ALLOW_JS_IN_PDF')) {
			dol_syslog("dolCheckOnFileName Check that pdf does not contains js code");

			$tmp = file_get_contents(trim($src_file));
			if (preg_match('/[\n\s]+\/JavaScript[\n\s]+/m', $tmp)) {
				return array('File is a PDF with javascript inside');
			}
		} else {
			dol_syslog("dolCheckOnFileName Check js into pdf disabled");
		}
	}

	return array();
}


/**
 *	Check validity of a file upload from an GUI page, and move it to its final destination.
 * 	If there is errors (virus found, antivir in error, bad filename), file is not moved.
 *  Note:
 *  - This function can be used only into a HTML page context. Use dol_move if you are outside.
 *  - Test on antivirus is always done (if antivirus set).
 *  - Database of files is NOT updated (this is done by dol_add_file_process() that calls this function).
 *  - Extension .noexe may be added if file is executable and MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED is not set.
 *
 *	@param	string	$src_file			Source full path filename ($_FILES['field']['tmp_name'])
 *	@param	string	$dest_file			Target full path filename  ($_FILES['field']['name'])
 * 	@param	int		$allowoverwrite		1=Overwrite target file if it already exists
 * 	@param	int		$disablevirusscan	1=Disable virus scan
 * 	@param	integer	$uploaderrorcode	Value of PHP upload error code ($_FILES['field']['error'])
 * 	@param	int		$nohook				Disable all hooks
 * 	@param	string	$varfiles			_FILES var name
 *  @param	string	$upload_dir			For information. Already included into $dest_file.
 *	@return int|string       			1 if OK, 2 if OK and .noexe appended, <0 or string if KO
 *  @see    dol_move()
 */
function dol_move_uploaded_file($src_file, $dest_file, $allowoverwrite, $disablevirusscan = 0, $uploaderrorcode = 0, $nohook = 0, $varfiles = 'addedfile', $upload_dir = '')
{
	global $conf;
	global $object, $hookmanager;

	$reshook = 0;
	$file_name = $dest_file;
	$successcode = 1;

	if (empty($nohook)) {
		$reshook = $hookmanager->initHooks(array('fileslib'));

		$parameters = array('dest_file' => $dest_file, 'src_file' => $src_file, 'file_name' => $file_name, 'varfiles' => $varfiles, 'allowoverwrite' => $allowoverwrite);
		$reshook = $hookmanager->executeHooks('moveUploadedFile', $parameters, $object);
	}

	if (empty($reshook)) {
		// If an upload error has been reported
		if ($uploaderrorcode) {
			switch ($uploaderrorcode) {
				case UPLOAD_ERR_INI_SIZE:	// 1
					return 'ErrorFileSizeTooLarge';
				case UPLOAD_ERR_FORM_SIZE:	// 2
					return 'ErrorFileSizeTooLarge';
				case UPLOAD_ERR_PARTIAL:	// 3
					return 'ErrorPartialFile';
				case UPLOAD_ERR_NO_TMP_DIR:	//
					return 'ErrorNoTmpDir';
				case UPLOAD_ERR_CANT_WRITE:
					return 'ErrorFailedToWriteInDir';
				case UPLOAD_ERR_EXTENSION:
					return 'ErrorUploadBlockedByAddon';
				default:
					break;
			}
		}

		// Security:
		// If we need to make a virus scan
		if (empty($disablevirusscan) && file_exists($src_file)) {
			$checkvirusarray = dolCheckVirus($src_file, $dest_file);
			if (count($checkvirusarray)) {
				dol_syslog('Files.lib::dol_move_uploaded_file File "'.$src_file.'" (target name "'.$dest_file.'") KO with antivirus: errors='.implode(',', $checkvirusarray), LOG_WARNING);
				return 'ErrorFileIsInfectedWithAVirus: '.implode(',', $checkvirusarray);
			}
		}

		// Security:
		// Disallow file with some extensions. We rename them.
		// Because if we put the documents directory into a directory inside web root (very bad), this allows to execute on demand arbitrary code.
		if (isAFileWithExecutableContent($dest_file) && !getDolGlobalString('MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED')) {
			// $upload_dir ends with a slash, so be must be sure the medias dir to compare to ends with slash too.
			$publicmediasdirwithslash = $conf->medias->multidir_output[$conf->entity];
			if (!preg_match('/\/$/', $publicmediasdirwithslash)) {
				$publicmediasdirwithslash .= '/';
			}

			if (strpos($upload_dir, $publicmediasdirwithslash) !== 0 || !getDolGlobalInt("MAIN_DOCUMENT_DISABLE_NOEXE_IN_MEDIAS_DIR")) {	// We never add .noexe on files into media directory
				$file_name .= '.noexe';
				$successcode = 2;
			}
		}

		// Security:
		// We refuse cache files/dirs, upload using .. and pipes into filenames.
		if (preg_match('/^\./', basename($src_file)) || preg_match('/\.\./', $src_file) || preg_match('/[<>|]/', $src_file)) {
			dol_syslog("Refused to deliver file ".$src_file, LOG_WARNING);
			return -1;
		}

		// Security:
		// We refuse cache files/dirs, upload using .. and pipes into filenames.
		if (preg_match('/^\./', basename($dest_file)) || preg_match('/\.\./', $dest_file) || preg_match('/[<>|]/', $dest_file)) {
			dol_syslog("Refused to deliver file ".$dest_file, LOG_WARNING);
			return -2;
		}
	}

	if ($reshook < 0) {	// At least one blocking error returned by one hook
		$errmsg = implode(',', $hookmanager->errors);
		if (empty($errmsg)) {
			$errmsg = 'ErrorReturnedBySomeHooks'; // Should not occurs. Added if hook is bugged and does not set ->errors when there is error.
		}
		return $errmsg;
	} elseif (empty($reshook)) {
		// The file functions must be in OS filesystem encoding.
		$src_file_osencoded = dol_osencode($src_file);
		$file_name_osencoded = dol_osencode($file_name);

		// Check if destination dir is writable
		if (!is_writable(dirname($file_name_osencoded))) {
			dol_syslog("Files.lib::dol_move_uploaded_file Dir ".dirname($file_name_osencoded)." is not writable. Return 'ErrorDirNotWritable'", LOG_WARNING);
			return 'ErrorDirNotWritable';
		}

		// Check if destination file already exists
		if (!$allowoverwrite) {
			if (file_exists($file_name_osencoded)) {
				dol_syslog("Files.lib::dol_move_uploaded_file File ".$file_name." already exists. Return 'ErrorFileAlreadyExists'", LOG_WARNING);
				return 'ErrorFileAlreadyExists';
			}
		} else {	// We are allowed to erase
			if (is_dir($file_name_osencoded)) {	// If there is a directory with name of file to create
				dol_syslog("Files.lib::dol_move_uploaded_file A directory with name ".$file_name." already exists. Return 'ErrorDirWithFileNameAlreadyExists'", LOG_WARNING);
				return 'ErrorDirWithFileNameAlreadyExists';
			}
		}

		// Move file
		$return = move_uploaded_file($src_file_osencoded, $file_name_osencoded);
		if ($return) {
			dolChmod($file_name_osencoded);
			dol_syslog("Files.lib::dol_move_uploaded_file Success to move ".$src_file." to ".$file_name." - Umask=" . getDolGlobalString('MAIN_UMASK'), LOG_DEBUG);
			return $successcode; // Success
		} else {
			dol_syslog("Files.lib::dol_move_uploaded_file Failed to move ".$src_file." to ".$file_name, LOG_ERR);
			return -3; // Unknown error
		}
	}

	return $successcode; // Success
}

/**
 *  Remove a file or several files with a mask.
 *  This delete file physically but also database indexes.
 *
 *  @param	string	$file           File to delete or mask of files to delete
 *  @param  int		$disableglob    Disable usage of glob like * so function is an exact delete function that will return error if no file found
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @param	int		$nohook			Disable all hooks
 *  @param	object|null	$object			Current object in use
 *  @param	boolean	$allowdotdot	Allow to delete file path with .. inside. Never use this, it is reserved for migration purpose.
 *  @param	int		$indexdatabase	Try to remove also index entries.
 *  @param	int		$nolog			Disable log file
 *  @return boolean         		True if no error (file is deleted or if glob is used and there's nothing to delete), False if error
 *  @see dol_delete_dir()
 */
function dol_delete_file($file, $disableglob = 0, $nophperrors = 0, $nohook = 0, $object = null, $allowdotdot = false, $indexdatabase = 1, $nolog = 0)
{
	global $db, $user;
	global $hookmanager;

	if (empty($nolog)) {
		dol_syslog("dol_delete_file file=".$file." disableglob=".$disableglob." nophperrors=".$nophperrors." nohook=".$nohook);
	}

	// Security:
	// We refuse transversal using .. and pipes into filenames.
	if ((!$allowdotdot && preg_match('/\.\./', $file)) || preg_match('/[<>|]/', $file)) {
		dol_syslog("Refused to delete file ".$file, LOG_WARNING);
		return false;
	}

	$reshook = 0;
	if (empty($nohook) && !empty($hookmanager)) {
		$hookmanager->initHooks(array('fileslib'));

		$parameters = array(
			'file' => $file,
			'disableglob' => $disableglob,
			'nophperrors' => $nophperrors
		);
		$reshook = $hookmanager->executeHooks('deleteFile', $parameters, $object);
	}

	if (empty($nohook) && $reshook != 0) { // reshook = 0 to do standard actions, 1 = ok and replace, -1 = ko
		dol_syslog("reshook=".$reshook);
		if ($reshook < 0) {
			return false;
		}
		return true;
	} else {
		$file_osencoded = dol_osencode($file); // New filename encoded in OS filesystem encoding charset
		if (empty($disableglob) && !empty($file_osencoded)) {
			$ok = true;
			$globencoded = str_replace('[', '\[', $file_osencoded);
			$globencoded = str_replace(']', '\]', $globencoded);
			$listofdir = glob($globencoded);
			if (!empty($listofdir) && is_array($listofdir)) {
				foreach ($listofdir as $filename) {
					if ($nophperrors) {
						$ok = @unlink($filename);
					} else {
						$ok = unlink($filename);
					}

					// If it fails and it is because of the missing write permission on parent dir
					if (!$ok && file_exists(dirname($filename)) && !(fileperms(dirname($filename)) & 0200)) {
						dol_syslog("Error in deletion, but parent directory exists with no permission to write, we try to change permission on parent directory and retry...", LOG_DEBUG);
						dolChmod(dirname($filename), decoct(fileperms(dirname($filename)) | 0200));
						// Now we retry deletion
						if ($nophperrors) {
							$ok = @unlink($filename);
						} else {
							$ok = unlink($filename);
						}
					}

					if ($ok) {
						if (empty($nolog)) {
							dol_syslog("Removed file ".$filename, LOG_DEBUG);
						}

						// Delete entry into ecm database
						$rel_filetodelete = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filename);
						if (!preg_match('/(\/temp\/|\/thumbs\/|\.meta$)/', $rel_filetodelete)) {     // If not a tmp file
							if (is_object($db) && $indexdatabase) {		// $db may not be defined when lib is in a context with define('NOREQUIREDB',1)
								$rel_filetodelete = preg_replace('/^[\\/]/', '', $rel_filetodelete);
								$rel_filetodelete = preg_replace('/\.noexe$/', '', $rel_filetodelete);

								dol_syslog("Try to remove also entries in database for full relative path = ".$rel_filetodelete, LOG_DEBUG);
								include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
								$ecmfile = new EcmFiles($db);
								$result = $ecmfile->fetch(0, '', $rel_filetodelete);
								if ($result >= 0 && $ecmfile->id > 0) {
									$result = $ecmfile->delete($user);
								}
								if ($result < 0) {
									setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
								}
							}
						}
					} else {
						dol_syslog("Failed to remove file ".$filename, LOG_WARNING);
						// TODO Failure to remove can be because file was already removed or because of permission
						// If error because it does not exists, we should return true, and we should return false if this is a permission problem
					}
				}
			} else {
				dol_syslog("No files to delete found", LOG_DEBUG);
			}
		} else {
			$ok = false;
			if ($nophperrors) {
				$ok = @unlink($file_osencoded);
			} else {
				$ok = unlink($file_osencoded);
			}
			if ($ok) {
				if (empty($nolog)) {
					dol_syslog("Removed file ".$file_osencoded, LOG_DEBUG);
				}
			} else {
				dol_syslog("Failed to remove file ".$file_osencoded, LOG_WARNING);
			}
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
 *  @see dol_delete_file() dolCopyDir()
 */
function dol_delete_dir($dir, $nophperrors = 0)
{
	// Security:
	// We refuse transversal using .. and pipes into filenames.
	if (preg_match('/\.\./', $dir) || preg_match('/[<>|]/', $dir)) {
		dol_syslog("Refused to delete dir ".$dir.' (contains invalid char sequence)', LOG_WARNING);
		return false;
	}

	$dir_osencoded = dol_osencode($dir);
	return ($nophperrors ? @rmdir($dir_osencoded) : rmdir($dir_osencoded));
}

/**
 *  Remove a directory $dir and its subdirectories (or only files and subdirectories)
 *
 *  @param	string	$dir            Dir to delete
 *  @param  int		$count          Counter to count nb of elements found to delete
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @param	int		$onlysub		Delete only files and subdir, not main directory
 *  @param  int		$countdeleted   Counter to count nb of elements found really deleted
 *  @param	int		$indexdatabase	Try to remove also index entries.
 *  @param	int		$nolog			Disable log files (too verbose when making recursive directories)
 *  @return int             		Number of files and directory we try to remove. NB really removed is returned into var by reference $countdeleted.
 */
function dol_delete_dir_recursive($dir, $count = 0, $nophperrors = 0, $onlysub = 0, &$countdeleted = 0, $indexdatabase = 1, $nolog = 0)
{
	if (empty($nolog)) {
		dol_syslog("functions.lib:dol_delete_dir_recursive ".$dir, LOG_DEBUG);
	}
	if (dol_is_dir($dir)) {
		$dir_osencoded = dol_osencode($dir);
		if ($handle = opendir("$dir_osencoded")) {
			while (false !== ($item = readdir($handle))) {
				if (!utf8_check($item)) {
					$item = mb_convert_encoding($item, 'UTF-8', 'ISO-8859-1'); // should be useless
				}

				if ($item != "." && $item != "..") {
					if (is_dir(dol_osencode("$dir/$item")) && !is_link(dol_osencode("$dir/$item"))) {
						$count = dol_delete_dir_recursive("$dir/$item", $count, $nophperrors, 0, $countdeleted, $indexdatabase, $nolog);
					} else {
						$result = dol_delete_file("$dir/$item", 1, $nophperrors, 0, null, false, $indexdatabase, $nolog);
						$count++;
						if ($result) {
							$countdeleted++;
						}
						//else print 'Error on '.$item."\n";
					}
				}
			}
			closedir($handle);

			// Delete also the main directory
			if (empty($onlysub)) {
				$result = dol_delete_dir($dir, $nophperrors);
				$count++;
				if ($result) {
					$countdeleted++;
				}
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
 *  @see dol_convert_file()
 */
function dol_delete_preview($object)
{
	global $langs, $conf;

	// Define parent dir of elements
	$element = $object->element;

	if ($object->element == 'order_supplier') {
		$dir = $conf->fournisseur->commande->dir_output;
	} elseif ($object->element == 'invoice_supplier') {
		$dir = $conf->fournisseur->facture->dir_output;
	} elseif ($object->element == 'project') {
		$dir = $conf->project->dir_output;
	} elseif ($object->element == 'shipping') {
		$dir = $conf->expedition->dir_output.'/sending';
	} elseif ($object->element == 'delivery') {
		$dir = $conf->expedition->dir_output.'/receipt';
	} elseif ($object->element == 'fichinter') {
		$dir = $conf->ficheinter->dir_output;
	} else {
		$dir = empty($conf->$element->dir_output) ? '' : $conf->$element->dir_output;
	}

	if (empty($dir)) {
		$object->error = $langs->trans('ErrorObjectNoSupportedByFunction');
		return 0;
	}

	$refsan = dol_sanitizeFileName($object->ref);
	$dir = $dir."/".$refsan;
	$filepreviewnew = $dir."/".$refsan.".pdf_preview.png";
	$filepreviewnewbis = $dir."/".$refsan.".pdf_preview-0.png";
	$filepreviewold = $dir."/".$refsan.".pdf.png";

	// For new preview files
	if (file_exists($filepreviewnew) && is_writable($filepreviewnew)) {
		if (!dol_delete_file($filepreviewnew, 1)) {
			$object->error = $langs->trans("ErrorFailedToDeleteFile", $filepreviewnew);
			return 0;
		}
	}
	if (file_exists($filepreviewnewbis) && is_writable($filepreviewnewbis)) {
		if (!dol_delete_file($filepreviewnewbis, 1)) {
			$object->error = $langs->trans("ErrorFailedToDeleteFile", $filepreviewnewbis);
			return 0;
		}
	}
	// For old preview files
	if (file_exists($filepreviewold) && is_writable($filepreviewold)) {
		if (!dol_delete_file($filepreviewold, 1)) {
			$object->error = $langs->trans("ErrorFailedToDeleteFile", $filepreviewold);
			return 0;
		}
	} else {
		$multiple = $filepreviewold.".";
		for ($i = 0; $i < 20; $i++) {
			$preview = $multiple.$i;

			if (file_exists($preview) && is_writable($preview)) {
				if (!dol_delete_file($preview, 1)) {
					$object->error = $langs->trans("ErrorFailedToOpenFile", $preview);
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
	if (!getDolGlobalString('MAIN_DOC_CREATE_METAFILE')) {
		return 0; // By default, no metafile.
	}

	// Define parent dir of elements
	$element = $object->element;

	if ($object->element == 'order_supplier') {
		$dir = $conf->fournisseur->dir_output.'/commande';
	} elseif ($object->element == 'invoice_supplier') {
		$dir = $conf->fournisseur->dir_output.'/facture';
	} elseif ($object->element == 'project') {
		$dir = $conf->project->dir_output;
	} elseif ($object->element == 'shipping') {
		$dir = $conf->expedition->dir_output.'/sending';
	} elseif ($object->element == 'delivery') {
		$dir = $conf->expedition->dir_output.'/receipt';
	} elseif ($object->element == 'fichinter') {
		$dir = $conf->ficheinter->dir_output;
	} else {
		$dir = empty($conf->$element->dir_output) ? '' : $conf->$element->dir_output;
	}

	if ($dir) {
		$object->fetch_thirdparty();

		$objectref = dol_sanitizeFileName($object->ref);
		$dir = $dir."/".$objectref;
		$file = $dir."/".$objectref.".meta";

		if (!is_dir($dir)) {
			dol_mkdir($dir);
		}

		if (is_dir($dir)) {
			if (is_countable($object->lines) && count($object->lines) > 0) {
				$nblines = count($object->lines);
			}
			$client = $object->thirdparty->name." ".$object->thirdparty->address." ".$object->thirdparty->zip." ".$object->thirdparty->town;
			$meta = "REFERENCE=\"".$object->ref."\"
			DATE=\"" . dol_print_date($object->date, '')."\"
			NB_ITEMS=\"" . $nblines."\"
			CLIENT=\"" . $client."\"
			AMOUNT_EXCL_TAX=\"" . $object->total_ht."\"
			AMOUNT=\"" . $object->total_ttc."\"\n";

			for ($i = 0; $i < $nblines; $i++) {
				//Pour les articles
				$meta .= "ITEM_".$i."_QUANTITY=\"".$object->lines[$i]->qty."\"
				ITEM_" . $i."_AMOUNT_WO_TAX=\"".$object->lines[$i]->total_ht."\"
				ITEM_" . $i."_VAT=\"".$object->lines[$i]->tva_tx."\"
				ITEM_" . $i."_DESCRIPTION=\"".str_replace("\r\n", "", nl2br($object->lines[$i]->desc))."\"
				";
			}
		}

		$fp = fopen($file, "w");
		fwrite($fp, $meta);
		fclose($fp);

		dolChmod($file);

		return 1;
	} else {
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
function dol_init_file_process($pathtoscan = '', $trackid = '')
{
	$listofpaths = array();
	$listofnames = array();
	$listofmimes = array();

	if ($pathtoscan) {
		$listoffiles = dol_dir_list($pathtoscan, 'files');
		foreach ($listoffiles as $key => $val) {
			$listofpaths[] = $val['fullname'];
			$listofnames[] = $val['name'];
			$listofmimes[] = dol_mimetype($val['name']);
		}
	}
	$keytoavoidconflict = empty($trackid) ? '' : '-'.$trackid;
	$_SESSION["listofpaths".$keytoavoidconflict] = implode(';', $listofpaths);
	$_SESSION["listofnames".$keytoavoidconflict] = implode(';', $listofnames);
	$_SESSION["listofmimes".$keytoavoidconflict] = implode(';', $listofmimes);
}


/**
 * Get and save an upload file (for example after submitting a new file a mail form). Database index of file is also updated if donotupdatesession is set.
 * All information used are in db, conf, langs, user and _FILES.
 * Note: This function can be used only into a HTML page context.
 *
 * @param	string	$upload_dir				Directory where to store uploaded file (note: used to forge $destpath = $upload_dir + filename)
 * @param	int		$allowoverwrite			1=Allow overwrite existing file
 * @param	int		$updatesessionordb		1=Do no edit _SESSION variable but update database index. 0=Update _SESSION and not database index. -1=Do not update SESSION neither db.
 * @param	string	$varfiles				_FILES var name
 * @param	string	$savingdocmask			Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
 * @param	string	$link					Link to add (to add a link instead of a file)
 * @param   string  $trackid                Track id (used to prefix name of session vars to avoid conflict)
 * @param	int		$generatethumbs			1=Generate also thumbs for uploaded image files
 * @param   Object  $object                 Object used to set 'src_object_*' fields
 * @return	int                             Return integer <=0 if KO, >0 if OK
 * @see dol_remove_file_process()
 */
function dol_add_file_process($upload_dir, $allowoverwrite = 0, $updatesessionordb = 0, $varfiles = 'addedfile', $savingdocmask = '', $link = null, $trackid = '', $generatethumbs = 1, $object = null)
{
	global $db, $user, $conf, $langs;

	$res = 0;

	if (!empty($_FILES[$varfiles])) { // For view $_FILES[$varfiles]['error']
		dol_syslog('dol_add_file_process upload_dir='.$upload_dir.' allowoverwrite='.$allowoverwrite.' donotupdatesession='.$updatesessionordb.' savingdocmask='.$savingdocmask, LOG_DEBUG);
		$maxfilesinform = getDolGlobalInt("MAIN_SECURITY_MAX_ATTACHMENT_ON_FORMS", 10);
		if (is_array($_FILES[$varfiles]["name"]) && count($_FILES[$varfiles]["name"]) > $maxfilesinform) {
			$langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
			setEventMessages($langs->trans("ErrorTooMuchFileInForm", $maxfilesinform), null, "errors");
			return -1;
		}
		$result = dol_mkdir($upload_dir);
		//      var_dump($result);exit;
		if ($result >= 0) {
			$TFile = $_FILES[$varfiles];
			// Convert value of $TFile
			if (!is_array($TFile['name'])) {
				foreach ($TFile as $key => &$val) {
					$val = array($val);
				}
			}

			$nbfile = count($TFile['name']);
			$nbok = 0;
			for ($i = 0; $i < $nbfile; $i++) {
				if (empty($TFile['name'][$i])) {
					continue; // For example, when submitting a form with no file name
				}

				// Define $destfull (path to file including filename) and $destfile (only filename)
				$destfile = trim($TFile['name'][$i]);
				$destfull = $upload_dir."/".$destfile;
				$destfilewithoutext = preg_replace('/\.[^\.]+$/', '', $destfile);

				if ($savingdocmask && strpos($savingdocmask, $destfilewithoutext) !== 0) {
					$destfile = trim(preg_replace('/__file__/', $TFile['name'][$i], $savingdocmask));
					$destfull = $upload_dir."/".$destfile;
				}

				$filenameto = basename($destfile);
				if (preg_match('/^\./', $filenameto)) {
					$langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
					setEventMessages($langs->trans("ErrorFilenameCantStartWithDot", $filenameto), null, 'errors');
					break;
				}
				// dol_sanitizeFileName the file name and lowercase extension
				$info = pathinfo($destfull);
				$destfull = $info['dirname'].'/'.dol_sanitizeFileName($info['filename'].($info['extension'] != '' ? ('.'.strtolower($info['extension'])) : ''));
				$info = pathinfo($destfile);
				$destfile = dol_sanitizeFileName($info['filename'].($info['extension'] != '' ? ('.'.strtolower($info['extension'])) : ''));

				// We apply dol_string_nohtmltag also to clean file names (this remove duplicate spaces) because
				// this function is also applied when we rename and when we make try to download file (by the GETPOST(filename, 'alphanohtml') call).
				$destfile = dol_string_nohtmltag($destfile);
				$destfull = dol_string_nohtmltag($destfull);

				// Check that filename is not the one of a reserved allowed CLI command
				global $dolibarr_main_restrict_os_commands;
				if (!empty($dolibarr_main_restrict_os_commands)) {
					$arrayofallowedcommand = explode(',', $dolibarr_main_restrict_os_commands);
					$arrayofallowedcommand = array_map('trim', $arrayofallowedcommand);
					if (in_array($destfile, $arrayofallowedcommand)) {
						$langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
						setEventMessages($langs->trans("ErrorFilenameReserved", $destfile), null, 'errors');
						return -1;
					}
				}

				// Move file from temp directory to final directory. A .noexe may also be appended on file name.
				$resupload = dol_move_uploaded_file($TFile['tmp_name'][$i], $destfull, $allowoverwrite, 0, $TFile['error'][$i], 0, $varfiles, $upload_dir);

				if (is_numeric($resupload) && $resupload > 0) {   // $resupload can be 'ErrorFileAlreadyExists'
					include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

					$tmparraysize = getDefaultImageSizes();
					$maxwidthsmall = $tmparraysize['maxwidthsmall'];
					$maxheightsmall = $tmparraysize['maxheightsmall'];
					$maxwidthmini = $tmparraysize['maxwidthmini'];
					$maxheightmini = $tmparraysize['maxheightmini'];
					//$quality = $tmparraysize['quality'];
					$quality = 50;	// For thumbs, we force quality to 50

					// Generate thumbs.
					if ($generatethumbs) {
						if (image_format_supported($destfull) == 1) {
							// Create thumbs
							// We can't use $object->addThumbs here because there is no $object known

							// Used on logon for example
							$imgThumbSmall = vignette($destfull, $maxwidthsmall, $maxheightsmall, '_small', $quality, "thumbs");
							// Create mini thumbs for image (Ratio is near 16/9)
							// Used on menu or for setup page for example
							$imgThumbMini = vignette($destfull, $maxwidthmini, $maxheightmini, '_mini', $quality, "thumbs");
						}
					}

					// Update session
					if (empty($updatesessionordb)) {
						include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
						$formmail = new FormMail($db);
						$formmail->trackid = $trackid;
						$formmail->add_attached_files($destfull, $destfile, $TFile['type'][$i]);
					}

					// Update index table of files (llx_ecm_files)
					if ($updatesessionordb == 1) {
						$sharefile = 0;
						if ($TFile['type'][$i] == 'application/pdf' && strpos($_SERVER["REQUEST_URI"], 'product') !== false && getDolGlobalString('PRODUCT_ALLOW_EXTERNAL_DOWNLOAD')) {
							$sharefile = 1;
						}
						$result = addFileIntoDatabaseIndex($upload_dir, basename($destfile).($resupload == 2 ? '.noexe' : ''), $TFile['name'][$i], 'uploaded', $sharefile, $object);
						if ($result < 0) {
							if ($allowoverwrite) {
								// Do not show error message. We can have an error due to DB_ERROR_RECORD_ALREADY_EXISTS
							} else {
								setEventMessages('WarningFailedToAddFileIntoDatabaseIndex', null, 'warnings');
							}
						}
					}

					$nbok++;
				} else {
					$langs->load("errors");
					if (is_numeric($resupload) && $resupload < 0) {	// Unknown error
						setEventMessages($langs->trans("ErrorFileNotUploaded"), null, 'errors');
					} elseif (preg_match('/ErrorFileIsInfectedWithAVirus/', $resupload)) {	// Files infected by a virus
						if (preg_match('/File is a PDF with javascript inside/', $resupload)) {
							setEventMessages($langs->trans("ErrorFileIsAnInfectedPDFWithJSInside"), null, 'errors');
						} else {
							setEventMessages($langs->trans("ErrorFileIsInfectedWithAVirus"), null, 'errors');
						}
					} else { // Known error
						setEventMessages($langs->trans($resupload), null, 'errors');
					}
				}
			}
			if ($nbok > 0) {
				$res = 1;
				setEventMessages($langs->trans("FileTransferComplete"), null, 'mesgs');
			}
		} else {
			setEventMessages($langs->trans("ErrorFailedToCreateDir", $upload_dir), null, 'errors');
		}
	} elseif ($link) {
		require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
		$linkObject = new Link($db);
		$linkObject->entity = $conf->entity;
		$linkObject->url = $link;
		$linkObject->objecttype = GETPOST('objecttype', 'alpha');
		$linkObject->objectid = GETPOSTINT('objectid');
		$linkObject->label = GETPOST('label', 'alpha');
		$res = $linkObject->create($user);

		if ($res > 0) {
			setEventMessages($langs->trans("LinkComplete"), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("ErrorFileNotLinked"), null, 'errors');
		}
	} else {
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
 * @param	int		$donotupdatesession		-1 or 1 = Do not update _SESSION variable
 * @param   int		$donotdeletefile        1=Do not delete physically file
 * @param   string  $trackid                Track id (used to prefix name of session vars to avoid conflict)
 * @return	void
 * @see dol_add_file_process()
 */
function dol_remove_file_process($filenb, $donotupdatesession = 0, $donotdeletefile = 1, $trackid = '')
{
	global $db, $user, $conf, $langs, $_FILES;

	$keytodelete = $filenb;
	$keytodelete--;

	$listofpaths = array();
	$listofnames = array();
	$listofmimes = array();
	$keytoavoidconflict = empty($trackid) ? '' : '-'.$trackid;
	if (!empty($_SESSION["listofpaths".$keytoavoidconflict])) {
		$listofpaths = explode(';', $_SESSION["listofpaths".$keytoavoidconflict]);
	}
	if (!empty($_SESSION["listofnames".$keytoavoidconflict])) {
		$listofnames = explode(';', $_SESSION["listofnames".$keytoavoidconflict]);
	}
	if (!empty($_SESSION["listofmimes".$keytoavoidconflict])) {
		$listofmimes = explode(';', $_SESSION["listofmimes".$keytoavoidconflict]);
	}

	if ($keytodelete >= 0) {
		$pathtodelete = $listofpaths[$keytodelete];
		$filetodelete = $listofnames[$keytodelete];
		if (empty($donotdeletefile)) {
			$result = dol_delete_file($pathtodelete, 1); // The delete of ecm database is inside the function dol_delete_file
		} else {
			$result = 0;
		}
		if ($result >= 0) {
			if (empty($donotdeletefile)) {
				$langs->load("other");
				setEventMessages($langs->trans("FileWasRemoved", $filetodelete), null, 'mesgs');
			}
			if (empty($donotupdatesession)) {
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
 *  @param		string	$file			File name (May end with '.noexe')
 *  @param		string	$fullpathorig	Full path of origin for file (can be '')
 *  @param		string	$mode			How file was created ('uploaded', 'generated', ...)
 *  @param		int		$setsharekey	Set also the share key
 *  @param      Object  $object         Object used to set 'src_object_*' fields
 *	@return		int						Return integer <0 if KO, 0 if nothing done, >0 if OK
 */
function addFileIntoDatabaseIndex($dir, $file, $fullpathorig = '', $mode = 'uploaded', $setsharekey = 0, $object = null)
{
	global $db, $user, $conf;

	$result = 0;

	$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);

	if (!preg_match('/[\\/]temp[\\/]|[\\/]thumbs|\.meta$/', $rel_dir)) {     // If not a tmp dir
		$filename = basename(preg_replace('/\.noexe$/', '', $file));
		$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
		$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

		include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
		$ecmfile = new EcmFiles($db);
		$ecmfile->filepath = $rel_dir;
		$ecmfile->filename = $filename;
		$ecmfile->label = md5_file(dol_osencode($dir.'/'.$file)); // MD5 of file content
		$ecmfile->fullpath_orig = $fullpathorig;
		$ecmfile->gen_or_uploaded = $mode;
		$ecmfile->description = ''; // indexed content
		$ecmfile->keywords = ''; // keyword content

		if (is_object($object) && $object->id > 0) {
			$ecmfile->src_object_id = $object->id;
			if (isset($object->table_element)) {
				$ecmfile->src_object_type = $object->table_element;
			} else {
				dol_syslog('Error: object ' . get_class($object) . ' has no table_element attribute.');
				return -1;
			}
			if (isset($object->src_object_description)) {
				$ecmfile->description = $object->src_object_description;
			}
			if (isset($object->src_object_keywords)) {
				$ecmfile->keywords = $object->src_object_keywords;
			}
		}

		if (getDolGlobalString('MAIN_FORCE_SHARING_ON_ANY_UPLOADED_FILE')) {
			$setsharekey = 1;
		}

		if ($setsharekey) {
			require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
			$ecmfile->share = getRandomPassword(true);
		}

		// Use a convertisser Doc to Text
		$useFullTextIndexation = getDolGlobalString('MAIN_USE_FULL_TEXT_INDEXATION');
		//$useFullTextIndexation = 1;
		if ($useFullTextIndexation) {
			$ecmfile->filepath = $rel_dir;
			$ecmfile->filename = $filename;

			$filetoprocess = $dir.'/'.$ecmfile->filename;

			$textforfulltextindex = '';
			$keywords = '';
			if (preg_match('/\.pdf/i', $filename)) {
				// TODO Move this into external submodule files

				// TODO Develop a native PHP parser using sample code in https://github.com/adeel/php-pdf-parser

				include_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';
				$utils = new Utils($db);
				$outputfile = $conf->admin->dir_temp.'/tmppdttotext.'.$user->id.'.out'; // File used with popen method

				// We also exclude '/temp/' dir and 'documents/admin/documents'
				// We make escapement here and call executeCLI without escapement because we don't want to have the '*.log' escaped.
				$cmd = getDolGlobalString('MAIN_USE_FULL_TEXT_INDEXATION_PDFTOTEXT', 'pdftotext')." -htmlmeta '".escapeshellcmd($filetoprocess)."' - ";
				$result = $utils->executeCLI($cmd, $outputfile, 0, null, 1);

				if (!$result['error']) {
					$txt = $result['output'];
					$matches = array();
					if (preg_match('/<meta name="Keywords" content="([^\/]+)"\s*\/>/i', $txt, $matches)) {
						$keywords = $matches[1];
					}
					if (preg_match('/<pre>(.*)<\/pre>/si', $txt, $matches)) {
						$textforfulltextindex = dol_string_nospecial($matches[1]);
					}
				}
			}

			$ecmfile->description = $textforfulltextindex;
			$ecmfile->keywords = $keywords;
		}

		$result = $ecmfile->create($user);
		if ($result < 0) {
			dol_syslog($ecmfile->error);
		}
	}

	return $result;
}

/**
 *  Delete files into database index using search criteria.
 *
 *  @param      string	$dir			Directory name (full real path without ending /)
 *  @param		string	$file			File name
 *  @param		string	$mode			How file was created ('uploaded', 'generated', ...)
 *	@return		int						Return integer <0 if KO, 0 if nothing done, >0 if OK
 */
function deleteFilesIntoDatabaseIndex($dir, $file, $mode = 'uploaded')
{
	global $conf, $db, $user;

	$error = 0;

	if (empty($dir)) {
		dol_syslog("deleteFilesIntoDatabaseIndex: dir parameter can't be empty", LOG_ERR);
		return -1;
	}

	$db->begin();

	$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $dir);

	$filename = basename($file);
	$rel_dir = preg_replace('/[\\/]$/', '', $rel_dir);
	$rel_dir = preg_replace('/^[\\/]/', '', $rel_dir);

	if (!$error) {
		$sql = 'DELETE FROM '.MAIN_DB_PREFIX.'ecm_files';
		$sql .= ' WHERE entity = '.$conf->entity;
		$sql .= " AND filepath = '".$db->escape($rel_dir)."'";
		if ($file) {
			$sql .= " AND filename = '".$db->escape($file)."'";
		}
		if ($mode) {
			$sql .= " AND gen_or_uploaded = '".$db->escape($mode)."'";
		}

		$resql = $db->query($sql);
		if (!$resql) {
			$error++;
			dol_syslog(__FUNCTION__.' '.$db->lasterror(), LOG_ERR);
		}
	}

	// Commit or rollback
	if ($error) {
		$db->rollback();
		return -1 * $error;
	} else {
		$db->commit();
		return 1;
	}
}


/**
 * 	Convert an image file or a PDF into another image format.
 *  This need Imagick php extension. You can use dol_imageResizeOrCrop() for a function that need GD.
 *
 *  @param	string	$fileinput  Input file name
 *  @param  string	$ext        Format of target file (It is also extension added to file if fileoutput is not provided).
 *  @param	string	$fileoutput	Output filename
 *  @param  string  $page       Page number if we convert a PDF into png
 *  @return	int					Return integer <0 if KO, 0=Nothing done, >0 if OK
 *  @see dol_imageResizeOrCrop()
 */
function dol_convert_file($fileinput, $ext = 'png', $fileoutput = '', $page = '')
{
	if (class_exists('Imagick')) {
		$image = new Imagick();
		try {
			$filetoconvert = $fileinput.(($page != '') ? '['.$page.']' : '');
			//var_dump($filetoconvert);
			$ret = $image->readImage($filetoconvert);
		} catch (Exception $e) {
			$ext = pathinfo($fileinput, PATHINFO_EXTENSION);
			dol_syslog("Failed to read image using Imagick (Try to install package 'apt-get install php-imagick ghostscript' and check there is no policy to disable ".$ext." conversion in /etc/ImageMagick*/policy.xml): ".$e->getMessage(), LOG_WARNING);
			return 0;
		}
		if ($ret) {
			$ret = $image->setImageFormat($ext);
			if ($ret) {
				if (empty($fileoutput)) {
					$fileoutput = $fileinput.".".$ext;
				}

				$count = $image->getNumberImages();

				if (!dol_is_file($fileoutput) || is_writable($fileoutput)) {
					try {
						$ret = $image->writeImages($fileoutput, true);
					} catch (Exception $e) {
						dol_syslog($e->getMessage(), LOG_WARNING);
					}
				} else {
					dol_syslog("Warning: Failed to write cache preview file '.$fileoutput.'. Check permission on file/dir", LOG_ERR);
				}
				if ($ret) {
					return $count;
				} else {
					return -3;
				}
			} else {
				return -2;
			}
		} else {
			return -1;
		}
	} else {
		return 0;
	}
}


/**
 * Compress a file.
 * An error string may be returned into parameters.
 *
 * @param 	string	$inputfile		Source file name
 * @param 	string	$outputfile		Target file name
 * @param 	string	$mode			'gz' or 'bz' or 'zip'
 * @param	string	$errorstring	Error string
 * @return	int						Return integer <0 if KO, >0 if OK
 * @see dol_uncompress(), dol_compress_dir()
 */
function dol_compress_file($inputfile, $outputfile, $mode = "gz", &$errorstring = null)
{
	$foundhandler = 0;
	//var_dump(basename($inputfile)); exit;

	try {
		dol_syslog("dol_compress_file mode=".$mode." inputfile=".$inputfile." outputfile=".$outputfile);

		$data = implode("", file(dol_osencode($inputfile)));
		if ($mode == 'gz' && function_exists('gzencode')) {
			$foundhandler = 1;
			$compressdata = gzencode($data, 9);
		} elseif ($mode == 'bz' && function_exists('bzcompress')) {
			$foundhandler = 1;
			$compressdata = bzcompress($data, 9);
		} elseif ($mode == 'zstd' && function_exists('zstd_compress')) {
			$foundhandler = 1;
			$compressdata = zstd_compress($data, 9);
		} elseif ($mode == 'zip') {
			if (class_exists('ZipArchive') && getDolGlobalString('MAIN_USE_ZIPARCHIVE_FOR_ZIP_COMPRESS')) {
				$foundhandler = 1;

				$rootPath = realpath($inputfile);

				dol_syslog("Class ZipArchive is set so we zip using ZipArchive to zip into ".$outputfile.' rootPath='.$rootPath);
				$zip = new ZipArchive();

				if ($zip->open($outputfile, ZipArchive::CREATE) !== true) {
					$errorstring = "dol_compress_file failure - Failed to open file ".$outputfile."\n";
					dol_syslog($errorstring, LOG_ERR);

					global $errormsg;
					$errormsg = $errorstring;

					return -6;
				}

				// Create recursive directory iterator
				/** @var SplFileInfo[] $files */
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($rootPath, FilesystemIterator::UNIX_PATHS),
					RecursiveIteratorIterator::LEAVES_ONLY
				);

				foreach ($files as $name => $file) {
					// Skip directories (they would be added automatically)
					if (!$file->isDir()) {
						// Get real and relative path for current file
						$filePath = $file->getPath();				// the full path with filename using the $inputdir root.
						$fileName = $file->getFilename();
						$fileFullRealPath = $file->getRealPath();	// the full path with name and transformed to use real path directory.

						//$relativePath = substr($fileFullRealPath, strlen($rootPath) + 1);
						$relativePath = substr(($filePath ? $filePath.'/' : '').$fileName, strlen($rootPath) + 1);

						// Add current file to archive
						$zip->addFile($fileFullRealPath, $relativePath);
					}
				}

				// Zip archive will be created only after closing object
				$zip->close();

				dol_syslog("dol_compress_file success - ".$zip->numFiles." files");
				return 1;
			}

			if (defined('ODTPHP_PATHTOPCLZIP')) {
				$foundhandler = 1;

				include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
				$archive = new PclZip($outputfile);

				$result = $archive->add($inputfile, PCLZIP_OPT_REMOVE_PATH, dirname($inputfile));

				if ($result === 0) {
					global $errormsg;
					$errormsg = $archive->errorInfo(true);

					if ($archive->errorCode() == PCLZIP_ERR_WRITE_OPEN_FAIL) {
						$errorstring = "PCLZIP_ERR_WRITE_OPEN_FAIL";
						dol_syslog("dol_compress_file error - archive->errorCode() = PCLZIP_ERR_WRITE_OPEN_FAIL", LOG_ERR);
						return -4;
					}

					$errorstring = "dol_compress_file error archive->errorCode = ".$archive->errorCode()." errormsg=".$errormsg;
					dol_syslog("dol_compress_file failure - ".$errormsg, LOG_ERR);
					return -3;
				} else {
					dol_syslog("dol_compress_file success - ".count($result)." files");
					return 1;
				}
			}
		}

		if ($foundhandler) {
			$fp = fopen($outputfile, "w");
			fwrite($fp, $compressdata);
			fclose($fp);
			return 1;
		} else {
			$errorstring = "Try to zip with format ".$mode." with no handler for this format";
			dol_syslog($errorstring, LOG_ERR);

			global $errormsg;
			$errormsg = $errorstring;
			return -2;
		}
	} catch (Exception $e) {
		global $langs, $errormsg;
		$langs->load("errors");
		$errormsg = $langs->trans("ErrorFailedToWriteInDir");

		$errorstring = "Failed to open file ".$outputfile;
		dol_syslog($errorstring, LOG_ERR);
		return -1;
	}
}

/**
 * Uncompress a file
 *
 * @param 	string 	$inputfile		File to uncompress
 * @param 	string	$outputdir		Target dir name
 * @return 	array					array('error'=>'Error code') or array() if no error
 * @see dol_compress_file(), dol_compress_dir()
 */
function dol_uncompress($inputfile, $outputdir)
{
	global $langs, $db;

	$fileinfo = pathinfo($inputfile);
	$fileinfo["extension"] = strtolower($fileinfo["extension"]);

	if ($fileinfo["extension"] == "zip") {
		if (defined('ODTPHP_PATHTOPCLZIP') && !getDolGlobalString('MAIN_USE_ZIPARCHIVE_FOR_ZIP_UNCOMPRESS')) {
			dol_syslog("Constant ODTPHP_PATHTOPCLZIP for pclzip library is set to ".ODTPHP_PATHTOPCLZIP.", so we use Pclzip to unzip into ".$outputdir);
			include_once ODTPHP_PATHTOPCLZIP.'/pclzip.lib.php';
			$archive = new PclZip($inputfile);

			// We create output dir manually, so it uses the correct permission (When created by the archive->extract, dir is rwx for everybody).
			dol_mkdir(dol_sanitizePathName($outputdir));

			try {
				// Extract into outputdir, but only files that match the regex '/^((?!\.\.).)*$/' that means "does not include .."
				$result = $archive->extract(PCLZIP_OPT_PATH, $outputdir, PCLZIP_OPT_BY_PREG, '/^((?!\.\.).)*$/');
			} catch (Exception $e) {
				return array('error' => $e->getMessage());
			}

			if (!is_array($result) && $result <= 0) {
				return array('error' => $archive->errorInfo(true));
			} else {
				$ok = 1;
				$errmsg = '';
				// Loop on each file to check result for unzipping file
				foreach ($result as $key => $val) {
					if ($val['status'] == 'path_creation_fail') {
						$langs->load("errors");
						$ok = 0;
						$errmsg = $langs->trans("ErrorFailToCreateDir", $val['filename']);
						break;
					}
					if ($val['status'] == 'write_protected') {
						$langs->load("errors");
						$ok = 0;
						$errmsg = $langs->trans("ErrorFailToCreateFile", $val['filename']);
						break;
					}
				}

				if ($ok) {
					return array();
				} else {
					return array('error' => $errmsg);
				}
			}
		}

		if (class_exists('ZipArchive')) {	// Must install php-zip to have it
			dol_syslog("Class ZipArchive is set so we unzip using ZipArchive to unzip into ".$outputdir);
			$zip = new ZipArchive();
			$res = $zip->open($inputfile);
			if ($res === true) {
				//$zip->extractTo($outputdir.'/');
				// We must extract one file at time so we can check that file name does not contain '..' to avoid transversal path of zip built for example using
				// python3 path_traversal_archiver.py <Created_file_name> test.zip -l 10 -p tmp/
				// with -l is the range of dot to go back in path.
				// and path_traversal_archiver.py found at https://github.com/Alamot/code-snippets/blob/master/path_traversal/path_traversal_archiver.py
				for ($i = 0; $i < $zip->numFiles; $i++) {
					if (preg_match('/\.\./', $zip->getNameIndex($i))) {
						dol_syslog("Warning: Try to unzip a file with a transversal path ".$zip->getNameIndex($i), LOG_WARNING);
						continue; // Discard the file
					}
					$zip->extractTo($outputdir.'/', array($zip->getNameIndex($i)));
				}

				$zip->close();
				return array();
			} else {
				return array('error' => 'ErrUnzipFails');
			}
		}

		return array('error' => 'ErrNoZipEngine');
	} elseif (in_array($fileinfo["extension"], array('gz', 'bz2', 'zst'))) {
		include_once DOL_DOCUMENT_ROOT."/core/class/utils.class.php";
		$utils = new Utils($db);

		dol_mkdir(dol_sanitizePathName($outputdir));
		$outputfilename = escapeshellcmd(dol_sanitizePathName($outputdir).'/'.dol_sanitizeFileName($fileinfo["filename"]));
		dol_delete_file($outputfilename.'.tmp');
		dol_delete_file($outputfilename.'.err');

		$extension = strtolower(pathinfo($fileinfo["filename"], PATHINFO_EXTENSION));
		if ($extension == "tar") {
			$cmd = 'tar -C '.escapeshellcmd(dol_sanitizePathName($outputdir)).' -xvf '.escapeshellcmd(dol_sanitizePathName($fileinfo["dirname"]).'/'.dol_sanitizeFileName($fileinfo["basename"]));

			$resarray = $utils->executeCLI($cmd, $outputfilename.'.tmp', 0, $outputfilename.'.err', 0);
			if ($resarray["result"] != 0) {
				$resarray["error"] .= file_get_contents($outputfilename.'.err');
			}
		} else {
			$program = "";
			if ($fileinfo["extension"] == "gz") {
				$program = 'gzip';
			} elseif ($fileinfo["extension"] == "bz2") {
				$program = 'bzip2';
			} elseif ($fileinfo["extension"] == "zst") {
				$program = 'zstd';
			} else {
				return array('error' => 'ErrorBadFileExtension');
			}
			$cmd = $program.' -dc '.escapeshellcmd(dol_sanitizePathName($fileinfo["dirname"]).'/'.dol_sanitizeFileName($fileinfo["basename"]));
			$cmd .= ' > '.$outputfilename;

			$resarray = $utils->executeCLI($cmd, $outputfilename.'.tmp', 0, null, 1, $outputfilename.'.err');
			if ($resarray["result"] != 0) {
				$errfilecontent = @file_get_contents($outputfilename.'.err');
				if ($errfilecontent) {
					$resarray["error"] .= " - ".$errfilecontent;
				}
			}
		}
		return $resarray["result"] != 0 ? array('error' => $resarray["error"]) : array();
	}

	return array('error' => 'ErrorBadFileExtension');
}


/**
 * Compress a directory and subdirectories into a package file.
 *
 * @param 	string	$inputdir		Source dir name
 * @param 	string	$outputfile		Target file name (output directory must exists and be writable)
 * @param 	string	$mode			'zip'
 * @param	string	$excludefiles   A regex pattern to exclude files. For example: '/\.log$|\/temp\//'
 * @param	string	$rootdirinzip	Add a root dir level in zip file
 * @param	string	$newmask		Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @return	int						Return integer <0 if KO, >0 if OK
 * @see dol_uncompress(), dol_compress_file()
 */
function dol_compress_dir($inputdir, $outputfile, $mode = "zip", $excludefiles = '', $rootdirinzip = '', $newmask = '0')
{
	$foundhandler = 0;

	dol_syslog("Try to zip dir ".$inputdir." into ".$outputfile." mode=".$mode);

	if (!dol_is_dir(dirname($outputfile)) || !is_writable(dirname($outputfile))) {
		global $langs, $errormsg;
		$langs->load("errors");
		$errormsg = $langs->trans("ErrorFailedToWriteInDir", $outputfile);
		return -3;
	}

	try {
		if ($mode == 'gz') {
			$foundhandler = 0;
		} elseif ($mode == 'bz') {
			$foundhandler = 0;
		} elseif ($mode == 'zip') {
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
			//if (class_exists('ZipArchive') && !empty($conf->global->MAIN_USE_ZIPARCHIVE_FOR_ZIP_COMPRESS))

			if (class_exists('ZipArchive')) {
				$foundhandler = 1;

				// Initialize archive object
				$zip = new ZipArchive();
				$result = $zip->open($outputfile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
				if ($result !== true) {
					global $langs, $errormsg;
					$langs->load("errors");
					$errormsg = $langs->trans("ErrorFailedToBuildArchive", $outputfile);
					return -4;
				}

				// Create recursive directory iterator
				// This does not return symbolic links
				/** @var SplFileInfo[] $files */
				$files = new RecursiveIteratorIterator(
					new RecursiveDirectoryIterator($inputdir, FilesystemIterator::UNIX_PATHS),
					RecursiveIteratorIterator::LEAVES_ONLY
				);

				//var_dump($inputdir);
				foreach ($files as $name => $file) {
					// Skip directories (they would be added automatically)
					if (!$file->isDir()) {
						// Get real and relative path for current file
						$filePath = $file->getPath();				// the full path with filename using the $inputdir root.
						$fileName = $file->getFilename();
						$fileFullRealPath = $file->getRealPath();	// the full path with name and transformed to use real path directory.

						//$relativePath = ($rootdirinzip ? $rootdirinzip.'/' : '').substr($fileFullRealPath, strlen($inputdir) + 1);
						$relativePath = ($rootdirinzip ? $rootdirinzip.'/' : '').substr(($filePath ? $filePath.'/' : '').$fileName, strlen($inputdir) + 1);

						//var_dump($filePath);var_dump($fileFullRealPath);var_dump($relativePath);
						if (empty($excludefiles) || !preg_match($excludefiles, $fileFullRealPath)) {
							// Add current file to archive
							$zip->addFile($fileFullRealPath, $relativePath);
						}
					}
				}

				// Zip archive will be created only after closing object
				$zip->close();

				if (empty($newmask) && getDolGlobalString('MAIN_UMASK')) {
					$newmask = getDolGlobalString('MAIN_UMASK');
				}
				if (empty($newmask)) {	// This should no happen
					dol_syslog("Warning: dol_copy called with empty value for newmask and no default value defined", LOG_WARNING);
					$newmask = '0664';
				}

				dolChmod($outputfile, $newmask);

				return 1;
			}
		}

		if (!$foundhandler) {
			dol_syslog("Try to zip with format ".$mode." with no handler for this format", LOG_ERR);
			return -2;
		} else {
			return 0;
		}
	} catch (Exception $e) {
		global $langs, $errormsg;
		$langs->load("errors");
		dol_syslog("Failed to open file ".$outputfile, LOG_ERR);
		dol_syslog($e->getMessage(), LOG_ERR);
		$errormsg = $langs->trans("ErrorFailedToBuildArchive", $outputfile).' - '.$e->getMessage();
		return -1;
	}
}



/**
 * Return file(s) into a directory (by default most recent)
 *
 * @param 	string		$dir			Directory to scan
 * @param	string		$regexfilter	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	string[]	$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	int<0,1>	$nohook			Disable all hooks
 * @param	int<0,3>	$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
 * @return	null|array{name:string,path:string,level1name:string,relativename:string,fullname:string,date:string,size:int,perm:int,type:string}	null if none or Array with properties (full path, date, ...) of the most recent file
 */
function dol_most_recent_file($dir, $regexfilter = '', $excludefilter = array('(\.meta|_preview.*\.png)$', '^\.'), $nohook = 0, $mode = 0)
{
	$tmparray = dol_dir_list($dir, 'files', 0, $regexfilter, $excludefilter, 'date', SORT_DESC, $mode, $nohook);
	return isset($tmparray[0]) ? $tmparray[0] : null;
}

/**
 * Security check when accessing to a document (used by document.php, viewimage.php and webservices to get documents).
 * TODO Replace code that set $accessallowed by a call to restrictedArea()
 *
 * @param	string		$modulepart			Module of document ('module', 'module_user_temp', 'module_user' or 'module_temp'). Example: 'medias', 'invoice', 'logs', 'tax-vat', ...
 * @param	string		$original_file		Relative path with filename, relative to modulepart.
 * @param	string		$entity				Restrict onto entity (0=no restriction)
 * @param  	User|null	$fuser				User object (forced)
 * @param	string		$refname			Ref of object to check permission for external users (autodetect if not provided by taking the dirname of $original_file) or for hierarchy
 * @param   string  	$mode               Check permission for 'read' or 'write'
 * @return	mixed							Array with access information : 'accessallowed' & 'sqlprotectagainstexternals' & 'original_file' (as a full path name)
 * @see restrictedArea()
 */
function dol_check_secure_access_document($modulepart, $original_file, $entity, $fuser = null, $refname = '', $mode = 'read')
{
	global $conf, $db, $user, $hookmanager;
	global $dolibarr_main_data_root, $dolibarr_main_document_root_alt;
	global $object;

	if (!is_object($fuser)) {
		$fuser = $user;
	}

	if (empty($modulepart)) {
		return 'ErrorBadParameter';
	}
	if (empty($entity)) {
		if (!isModEnabled('multicompany')) {
			$entity = 1;
		} else {
			$entity = 0;
		}
	}
	// Fix modulepart for backward compatibility
	if ($modulepart == 'facture') {
		$modulepart = 'invoice';
	} elseif ($modulepart == 'users') {
		$modulepart = 'user';
	} elseif ($modulepart == 'tva') {
		$modulepart = 'tax-vat';
	} elseif ($modulepart == 'expedition' && strpos($original_file, 'receipt/') === 0) {
		// Fix modulepart delivery
		$modulepart = 'delivery';
	}

	//print 'dol_check_secure_access_document modulepart='.$modulepart.' original_file='.$original_file.' entity='.$entity;
	dol_syslog('dol_check_secure_access_document modulepart='.$modulepart.' original_file='.$original_file.' entity='.$entity);

	// We define $accessallowed and $sqlprotectagainstexternals
	$accessallowed = 0;
	$sqlprotectagainstexternals = '';
	$ret = array();

	// Find the subdirectory name as the reference. For example original_file='10/myfile.pdf' -> refname='10'
	if (empty($refname)) {
		$refname = basename(dirname($original_file)."/");
		if ($refname == 'thumbs' || $refname == 'temp') {
			// If we get the thumbs directory, we must go one step higher. For example original_file='10/thumbs/myfile_small.jpg' -> refname='10'
			$refname = basename(dirname(dirname($original_file))."/");
		}
	}

	// Define possible keys to use for permission check
	$lire = 'lire';
	$read = 'read';
	$download = 'download';
	if ($mode == 'write') {
		$lire = 'creer';
		$read = 'write';
		$download = 'upload';
	}

	// Wrapping for miscellaneous medias files
	if ($modulepart == 'common') {
		// Wrapping for some images
		$accessallowed = 1;
		$original_file = DOL_DOCUMENT_ROOT.'/public/theme/common/'.$original_file;
	} elseif ($modulepart == 'medias' && !empty($dolibarr_main_data_root)) {
		if (empty($entity) || empty($conf->medias->multidir_output[$entity])) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		$accessallowed = 1;
		$original_file = $conf->medias->multidir_output[$entity].'/'.$original_file;
	} elseif ($modulepart == 'logs' && !empty($dolibarr_main_data_root)) {
		// Wrapping for *.log files, like when used with url http://.../document.php?modulepart=logs&file=dolibarr.log
		$accessallowed = ($user->admin && basename($original_file) == $original_file && preg_match('/^dolibarr.*\.(log|json)$/', basename($original_file)));
		$original_file = $dolibarr_main_data_root.'/'.$original_file;
	} elseif ($modulepart == 'doctemplates' && !empty($dolibarr_main_data_root)) {
		// Wrapping for doctemplates
		$accessallowed = $user->admin;
		$original_file = $dolibarr_main_data_root.'/doctemplates/'.$original_file;
	} elseif ($modulepart == 'doctemplateswebsite' && !empty($dolibarr_main_data_root)) {
		// Wrapping for doctemplates of websites
		$accessallowed = ($fuser->hasRight('website', 'write') && preg_match('/\.jpg$/i', basename($original_file)));
		$original_file = $dolibarr_main_data_root.'/doctemplates/websites/'.$original_file;
	} elseif ($modulepart == 'packages' && !empty($dolibarr_main_data_root)) {	// To download zip of modules
		// Wrapping for *.zip package files, like when used with url http://.../document.php?modulepart=packages&file=module_myfile.zip
		// Dir for custom dirs
		$tmp = explode(',', $dolibarr_main_document_root_alt);
		$dirins = $tmp[0];

		$accessallowed = ($user->admin && preg_match('/^module_.*\.zip$/', basename($original_file)));
		$original_file = $dirins.'/'.$original_file;
	} elseif ($modulepart == 'mycompany' && !empty($conf->mycompany->dir_output)) {
		// Wrapping for some images
		$accessallowed = 1;
		$original_file = $conf->mycompany->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'userphoto' && !empty($conf->user->dir_output)) {
		// Wrapping for users photos (user photos are allowed to any connected users)
		$accessallowed = 0;
		if (preg_match('/^\d+\/photos\//', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->user->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'userphotopublic' && !empty($conf->user->dir_output)) {
		// Wrapping for users photos that were set to public (for virtual credit card) by their owner (public user photos can be read
		// with the public link and securekey)
		$accessok = false;
		$reg = array();
		if (preg_match('/^(\d+)\/photos\//', $original_file, $reg)) {
			if ($reg[1]) {
				$tmpobject = new User($db);
				$tmpobject->fetch($reg[1], '', '', 1);
				if (getDolUserInt('USER_ENABLE_PUBLIC', 0, $tmpobject)) {
					$securekey = GETPOST('securekey', 'alpha', 1);
					// Security check
					global $dolibarr_main_cookie_cryptkey, $dolibarr_main_instance_unique_id;
					$valuetouse = $dolibarr_main_instance_unique_id ? $dolibarr_main_instance_unique_id : $dolibarr_main_cookie_cryptkey; // Use $dolibarr_main_instance_unique_id first then $dolibarr_main_cookie_cryptkey
					$encodedsecurekey = dol_hash($valuetouse.'uservirtualcard'.$tmpobject->id.'-'.$tmpobject->login, 'md5');
					if ($encodedsecurekey == $securekey) {
						$accessok = true;
					}
				}
			}
		}
		if ($accessok) {
			$accessallowed = 1;
		}
		$original_file = $conf->user->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'companylogo') && !empty($conf->mycompany->dir_output)) {
		// Wrapping for company logos (company logos are allowed to anyboby, they are public)
		$accessallowed = 1;
		$original_file = $conf->mycompany->dir_output.'/logos/'.$original_file;
	} elseif ($modulepart == 'memberphoto' && !empty($conf->member->dir_output)) {
		// Wrapping for members photos
		$accessallowed = 0;
		if (preg_match('/^\d+\/photos\//', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->member->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'apercufacture' && !empty($conf->invoice->multidir_output[$entity])) {
		// Wrapping for invoices (user need permission to read invoices)
		if ($fuser->hasRight('facture', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->invoice->multidir_output[$entity].'/'.$original_file;
	} elseif ($modulepart == 'apercupropal' && !empty($conf->propal->multidir_output[$entity])) {
		// Wrapping pour les apercu propal
		if ($fuser->hasRight('propal', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->propal->multidir_output[$entity].'/'.$original_file;
	} elseif ($modulepart == 'apercucommande' && !empty($conf->order->multidir_output[$entity])) {
		// Wrapping pour les apercu commande
		if ($fuser->hasRight('commande', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->order->multidir_output[$entity].'/'.$original_file;
	} elseif (($modulepart == 'apercufichinter' || $modulepart == 'apercuficheinter') && !empty($conf->ficheinter->dir_output)) {
		// Wrapping pour les apercu intervention
		if ($fuser->hasRight('ficheinter', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->ficheinter->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'apercucontract') && !empty($conf->contract->multidir_output[$entity])) {
		// Wrapping pour les apercu contrat
		if ($fuser->hasRight('contrat', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->contract->multidir_output[$entity].'/'.$original_file;
	} elseif (($modulepart == 'apercusupplier_proposal' || $modulepart == 'apercusupplier_proposal') && !empty($conf->supplier_proposal->dir_output)) {
		// Wrapping pour les apercu supplier proposal
		if ($fuser->hasRight('supplier_proposal', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->supplier_proposal->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'apercusupplier_order' || $modulepart == 'apercusupplier_order') && !empty($conf->fournisseur->commande->dir_output)) {
		// Wrapping pour les apercu supplier order
		if ($fuser->hasRight('fournisseur', 'commande', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->commande->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'apercusupplier_invoice' || $modulepart == 'apercusupplier_invoice') && !empty($conf->fournisseur->facture->dir_output)) {
		// Wrapping pour les apercu supplier invoice
		if ($fuser->hasRight('fournisseur', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->facture->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'holiday') && !empty($conf->holiday->dir_output)) {
		if ($fuser->hasRight('holiday', $read) || $fuser->hasRight('holiday', 'readall') || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
			// If we known $id of holiday, call checkUserAccessToObject to check permission on properties and hierarchy of leave request
			if ($refname && !$fuser->hasRight('holiday', 'readall') && !preg_match('/^specimen/i', $original_file)) {
				include_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
				$tmpholiday = new Holiday($db);
				$tmpholiday->fetch(0, $refname);
				$accessallowed = checkUserAccessToObject($user, array('holiday'), $tmpholiday, 'holiday', '', '', 'rowid', '');
			}
		}
		$original_file = $conf->holiday->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'expensereport') && !empty($conf->expensereport->dir_output)) {
		if ($fuser->hasRight('expensereport', $lire) || $fuser->hasRight('expensereport', 'readall') || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
			// If we known $id of expensereport, call checkUserAccessToObject to check permission on properties and hierarchy of expense report
			if ($refname && !$fuser->hasRight('expensereport', 'readall') && !preg_match('/^specimen/i', $original_file)) {
				include_once DOL_DOCUMENT_ROOT.'/expensereport/class/expensereport.class.php';
				$tmpexpensereport = new ExpenseReport($db);
				$tmpexpensereport->fetch(0, $refname);
				$accessallowed = checkUserAccessToObject($user, array('expensereport'), $tmpexpensereport, 'expensereport', '', '', 'rowid', '');
			}
		}
		$original_file = $conf->expensereport->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'apercuexpensereport') && !empty($conf->expensereport->dir_output)) {
		// Wrapping pour les apercu expense report
		if ($fuser->hasRight('expensereport', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->expensereport->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'propalstats' && !empty($conf->propal->multidir_temp[$entity])) {
		// Wrapping pour les images des stats propales
		if ($fuser->hasRight('propal', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->propal->multidir_temp[$entity].'/'.$original_file;
	} elseif ($modulepart == 'orderstats' && !empty($conf->order->dir_temp)) {
		// Wrapping pour les images des stats commandes
		if ($fuser->hasRight('commande', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->order->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'orderstatssupplier' && !empty($conf->fournisseur->dir_output)) {
		if ($fuser->hasRight('fournisseur', 'commande', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->commande->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'billstats' && !empty($conf->invoice->dir_temp)) {
		// Wrapping pour les images des stats factures
		if ($fuser->hasRight('facture', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->invoice->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'billstatssupplier' && !empty($conf->fournisseur->dir_output)) {
		if ($fuser->hasRight('fournisseur', 'facture', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->facture->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'expeditionstats' && !empty($conf->expedition->dir_temp)) {
		// Wrapping pour les images des stats expeditions
		if ($fuser->hasRight('expedition', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->expedition->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'tripsexpensesstats' && !empty($conf->deplacement->dir_temp)) {
		// Wrapping pour les images des stats expeditions
		if ($fuser->hasRight('deplacement', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->deplacement->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'memberstats' && !empty($conf->member->dir_temp)) {
		// Wrapping pour les images des stats expeditions
		if ($fuser->hasRight('adherent', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->member->dir_temp.'/'.$original_file;
	} elseif (preg_match('/^productstats_/i', $modulepart) && !empty($conf->product->dir_temp)) {
		// Wrapping pour les images des stats produits
		if ($fuser->hasRight('produit', $lire) || $fuser->hasRight('service', $lire)) {
			$accessallowed = 1;
		}
		$original_file = (!empty($conf->product->multidir_temp[$entity]) ? $conf->product->multidir_temp[$entity] : $conf->service->multidir_temp[$entity]).'/'.$original_file;
	} elseif (in_array($modulepart, array('tax', 'tax-vat', 'tva')) && !empty($conf->tax->dir_output)) {
		// Wrapping for taxes
		if ($fuser->hasRight('tax', 'charges', $lire)) {
			$accessallowed = 1;
		}
		$modulepartsuffix = str_replace('tax-', '', $modulepart);
		$original_file = $conf->tax->dir_output.'/'.($modulepartsuffix != 'tax' ? $modulepartsuffix.'/' : '').$original_file;
	} elseif ($modulepart == 'actions' && !empty($conf->agenda->dir_output)) {
		// Wrapping for events
		if ($fuser->hasRight('agenda', 'myactions', $read)) {
			$accessallowed = 1;
			// If we known $id of project, call checkUserAccessToObject to check permission on the given agenda event on properties and assigned users
			if ($refname && !preg_match('/^specimen/i', $original_file)) {
				include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
				$tmpobject = new ActionComm($db);
				$tmpobject->fetch((int) $refname);
				$accessallowed = checkUserAccessToObject($user, array('agenda'), $tmpobject->id, 'actioncomm&societe', 'myactions|allactions', 'fk_soc', 'id', '');
				if ($user->socid && $tmpobject->socid) {
					$accessallowed = checkUserAccessToObject($user, array('societe'), $tmpobject->socid);
				}
			}
		}
		$original_file = $conf->agenda->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'category' && !empty($conf->categorie->multidir_output[$entity])) {
		// Wrapping for categories (categories are allowed if user has permission to read categories or to work on TakePos)
		if (empty($entity) || empty($conf->categorie->multidir_output[$entity])) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		if ($fuser->hasRight("categorie", $lire) || $fuser->hasRight("takepos", "run")) {
			$accessallowed = 1;
		}
		$original_file = $conf->categorie->multidir_output[$entity].'/'.$original_file;
	} elseif ($modulepart == 'prelevement' && !empty($conf->prelevement->dir_output)) {
		// Wrapping pour les prelevements
		if ($fuser->hasRight('prelevement', 'bons', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->prelevement->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'graph_stock' && !empty($conf->stock->dir_temp)) {
		// Wrapping pour les graph energie
		$accessallowed = 1;
		$original_file = $conf->stock->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'graph_fourn' && !empty($conf->fournisseur->dir_temp)) {
		// Wrapping pour les graph fournisseurs
		$accessallowed = 1;
		$original_file = $conf->fournisseur->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'graph_product' && !empty($conf->product->dir_temp)) {
		// Wrapping pour les graph des produits
		$accessallowed = 1;
		$original_file = $conf->product->multidir_temp[$entity].'/'.$original_file;
	} elseif ($modulepart == 'barcode') {
		// Wrapping pour les code barre
		$accessallowed = 1;
		// If viewimage is called for barcode, we try to output an image on the fly, with no build of file on disk.
		//$original_file=$conf->barcode->dir_temp.'/'.$original_file;
		$original_file = '';
	} elseif ($modulepart == 'iconmailing' && !empty($conf->mailing->dir_temp)) {
		// Wrapping for icon of background of mailings
		$accessallowed = 1;
		$original_file = $conf->mailing->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'scanner_user_temp' && !empty($conf->scanner->dir_temp)) {
		// Wrapping pour le scanner
		$accessallowed = 1;
		$original_file = $conf->scanner->dir_temp.'/'.$fuser->id.'/'.$original_file;
	} elseif ($modulepart == 'fckeditor' && !empty($conf->fckeditor->dir_output)) {
		// Wrapping pour les images fckeditor
		$accessallowed = 1;
		$original_file = $conf->fckeditor->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'user' && !empty($conf->user->dir_output)) {
		// Wrapping for users
		$canreaduser = (!empty($fuser->admin) || $fuser->rights->user->user->{$lire});
		if ($fuser->id == (int) $refname) {
			$canreaduser = 1;
		} // A user can always read its own card
		if ($canreaduser || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->user->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'company' || $modulepart == 'societe' || $modulepart == 'thirdparty') && !empty($conf->societe->multidir_output[$entity])) {
		// Wrapping for third parties
		if (empty($entity) || empty($conf->societe->multidir_output[$entity])) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		if ($fuser->hasRight('societe', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->societe->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT rowid as fk_soc FROM ".MAIN_DB_PREFIX."societe WHERE rowid='".$db->escape($refname)."' AND entity IN (".getEntity('societe').")";
	} elseif ($modulepart == 'contact' && !empty($conf->societe->multidir_output[$entity])) {
		// Wrapping for contact
		if (empty($entity) || empty($conf->societe->multidir_output[$entity])) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		if ($fuser->hasRight('societe', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->societe->multidir_output[$entity].'/contact/'.$original_file;
	} elseif (($modulepart == 'facture' || $modulepart == 'invoice') && !empty($conf->invoice->multidir_output[$entity])) {
		// Wrapping for invoices
		if ($fuser->hasRight('facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->invoice->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('invoice').")";
	} elseif ($modulepart == 'massfilesarea_proposals' && !empty($conf->propal->multidir_output[$entity])) {
		// Wrapping for mass actions
		if ($fuser->hasRight('propal', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->propal->multidir_output[$entity].'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_orders') {
		if ($fuser->hasRight('commande', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->order->multidir_output[$entity].'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_sendings') {
		if ($fuser->hasRight('expedition', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->expedition->dir_output.'/sending/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_receipts') {
		if ($fuser->hasRight('reception', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->reception->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_invoices') {
		if ($fuser->hasRight('facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->invoice->multidir_output[$entity].'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_expensereport') {
		if ($fuser->hasRight('facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->expensereport->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_interventions') {
		if ($fuser->hasRight('ficheinter', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->ficheinter->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_supplier_proposal' && !empty($conf->supplier_proposal->dir_output)) {
		if ($fuser->hasRight('supplier_proposal', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->supplier_proposal->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_supplier_order') {
		if ($fuser->hasRight('fournisseur', 'commande', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->commande->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_supplier_invoice') {
		if ($fuser->hasRight('fournisseur', 'facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->facture->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif ($modulepart == 'massfilesarea_contract' && !empty($conf->contract->dir_output)) {
		if ($fuser->hasRight('contrat', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->contract->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
	} elseif (($modulepart == 'fichinter' || $modulepart == 'ficheinter') && !empty($conf->ficheinter->dir_output)) {
		// Wrapping for interventions
		if ($fuser->hasRight('ficheinter', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->ficheinter->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	} elseif ($modulepart == 'deplacement' && !empty($conf->deplacement->dir_output)) {
		// Wrapping pour les deplacements et notes de frais
		if ($fuser->hasRight('deplacement', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->deplacement->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	} elseif (($modulepart == 'propal' || $modulepart == 'propale') && isset($conf->propal->multidir_output[$entity])) {
		// Wrapping pour les propales
		if ($fuser->hasRight('propal', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->propal->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('propal').")";
	} elseif (($modulepart == 'commande' || $modulepart == 'order') && !empty($conf->order->multidir_output[$entity])) {
		// Wrapping pour les commandes
		if ($fuser->hasRight('commande', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->order->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('order').")";
	} elseif ($modulepart == 'project' && !empty($conf->project->multidir_output[$entity])) {
		// Wrapping pour les projects
		if ($fuser->hasRight('projet', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
			// If we known $id of project, call checkUserAccessToObject to check permission on properties and contact of project
			if ($refname && !preg_match('/^specimen/i', $original_file)) {
				include_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
				$tmpproject = new Project($db);
				$tmpproject->fetch(0, $refname);
				$accessallowed = checkUserAccessToObject($user, array('projet'), $tmpproject->id, 'projet&project', '', '', 'rowid', '');
			}
		}
		$original_file = $conf->project->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('project').")";
	} elseif ($modulepart == 'project_task' && !empty($conf->project->multidir_output[$entity])) {
		if ($fuser->hasRight('projet', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
			// If we known $id of project, call checkUserAccessToObject to check permission on properties and contact of project
			if ($refname && !preg_match('/^specimen/i', $original_file)) {
				include_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
				$tmptask = new Task($db);
				$tmptask->fetch(0, $refname);
				$accessallowed = checkUserAccessToObject($user, array('projet_task'), $tmptask->id, 'projet_task&project', '', '', 'rowid', '');
			}
		}
		$original_file = $conf->project->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('project').")";
	} elseif (($modulepart == 'commande_fournisseur' || $modulepart == 'order_supplier') && !empty($conf->fournisseur->commande->dir_output)) {
		// Wrapping pour les commandes fournisseurs
		if ($fuser->hasRight('fournisseur', 'commande', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	} elseif (($modulepart == 'facture_fournisseur' || $modulepart == 'invoice_supplier') && !empty($conf->fournisseur->facture->dir_output)) {
		// Wrapping pour les factures fournisseurs
		if ($fuser->hasRight('fournisseur', 'facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	} elseif ($modulepart == 'supplier_payment') {
		// Wrapping pour les rapport de paiements
		if ($fuser->hasRight('fournisseur', 'facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->fournisseur->payment->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."paiementfournisseur WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	} elseif ($modulepart == 'payment') {
			// Wrapping pour les rapport de paiements
		if ($fuser->rights->facture->{$lire} || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->compta->payment->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'facture_paiement' && !empty($conf->invoice->dir_output)) {
		// Wrapping pour les rapport de paiements
		if ($fuser->hasRight('facture', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		if ($fuser->socid > 0) {
			$original_file = $conf->invoice->dir_output.'/payments/private/'.$fuser->id.'/'.$original_file;
		} else {
			$original_file = $conf->invoice->dir_output.'/payments/'.$original_file;
		}
	} elseif ($modulepart == 'export_compta' && !empty($conf->accounting->dir_output)) {
		// Wrapping for accounting exports
		if ($fuser->hasRight('accounting', 'bind', 'write') || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->accounting->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'expedition' || $modulepart == 'shipment') && !empty($conf->expedition->dir_output)) {
		// Wrapping pour les expedition
		if ($fuser->hasRight('expedition', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->expedition->dir_output."/".(strpos($original_file, 'sending/') === 0 ? '' : 'sending/').$original_file;
		//$original_file = $conf->expedition->dir_output."/".$original_file;
	} elseif (($modulepart == 'livraison' || $modulepart == 'delivery') && !empty($conf->expedition->dir_output)) {
		// Delivery Note Wrapping
		if ($fuser->hasRight('expedition', 'delivery', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->expedition->dir_output."/".(strpos($original_file, 'receipt/') === 0 ? '' : 'receipt/').$original_file;
	} elseif ($modulepart == 'actionsreport' && !empty($conf->agenda->dir_temp)) {
		// Wrapping pour les actions
		if ($fuser->hasRight('agenda', 'allactions', $read) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->agenda->dir_temp."/".$original_file;
	} elseif ($modulepart == 'product' || $modulepart == 'produit' || $modulepart == 'service' || $modulepart == 'produit|service') {
		// Wrapping pour les produits et services
		if (empty($entity) || (empty($conf->product->multidir_output[$entity]) && empty($conf->service->multidir_output[$entity]))) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		if (($fuser->hasRight('produit', $lire) || $fuser->hasRight('service', $lire)) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		if (isModEnabled("product")) {
			$original_file = $conf->product->multidir_output[$entity].'/'.$original_file;
		} elseif (isModEnabled("service")) {
			$original_file = $conf->service->multidir_output[$entity].'/'.$original_file;
		}
	} elseif ($modulepart == 'product_batch' || $modulepart == 'produitlot') {
		// Wrapping pour les lots produits
		if (empty($entity) || (empty($conf->productbatch->multidir_output[$entity]))) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		if (($fuser->hasRight('produit', $lire)) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		if (isModEnabled('productbatch')) {
			$original_file = $conf->productbatch->multidir_output[$entity].'/'.$original_file;
		}
	} elseif ($modulepart == 'movement' || $modulepart == 'mouvement') {
		// Wrapping for stock movements
		if (empty($entity) || empty($conf->stock->multidir_output[$entity])) {
			return array('accessallowed' => 0, 'error' => 'Value entity must be provided');
		}
		if (($fuser->hasRight('stock', $lire) || $fuser->hasRight('stock', 'movement', $lire) || $fuser->hasRight('stock', 'mouvement', $lire)) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		if (isModEnabled('stock')) {
			$original_file = $conf->stock->multidir_output[$entity].'/movement/'.$original_file;
		}
	} elseif ($modulepart == 'contract' && !empty($conf->contract->multidir_output[$entity])) {
		// Wrapping pour les contrats
		if ($fuser->hasRight('contrat', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->contract->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."contrat WHERE ref='".$db->escape($refname)."' AND entity IN (".getEntity('contract').")";
	} elseif ($modulepart == 'donation' && !empty($conf->don->dir_output)) {
		// Wrapping pour les dons
		if ($fuser->hasRight('don', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->don->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'dolresource' && !empty($conf->resource->dir_output)) {
		// Wrapping pour les dons
		if ($fuser->hasRight('resource', $read) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->resource->dir_output.'/'.$original_file;
	} elseif (($modulepart == 'remisecheque' || $modulepart == 'chequereceipt') && !empty($conf->bank->dir_output)) {
		// Wrapping pour les remises de cheques
		if ($fuser->hasRight('banque', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->bank->dir_output.'/checkdeposits/'.$original_file; // original_file should contains relative path so include the get_exdir result
	} elseif (($modulepart == 'banque' || $modulepart == 'bank') && !empty($conf->bank->dir_output)) {
		// Wrapping for bank
		if ($fuser->hasRight('banque', $lire)) {
			$accessallowed = 1;
		}
		$original_file = $conf->bank->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'export' && !empty($conf->export->dir_temp)) {
		// Wrapping for export module
		// Note that a test may not be required because we force the dir of download on the directory of the user that export
		$accessallowed = $user->hasRight('export', 'lire');
		$original_file = $conf->export->dir_temp.'/'.$fuser->id.'/'.$original_file;
	} elseif ($modulepart == 'import' && !empty($conf->import->dir_temp)) {
		// Wrapping for import module
		$accessallowed = $user->hasRight('import', 'run');
		$original_file = $conf->import->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'recruitment' && !empty($conf->recruitment->dir_output)) {
		// Wrapping for recruitment module
		$accessallowed = $user->hasRight('recruitment', 'recruitmentjobposition', 'read');
		$original_file = $conf->recruitment->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'editor' && !empty($conf->fckeditor->dir_output)) {
		// Wrapping for wysiwyg editor
		$accessallowed = 1;
		$original_file = $conf->fckeditor->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'systemtools' && !empty($conf->admin->dir_output)) {
		// Wrapping for backups
		if ($fuser->admin) {
			$accessallowed = 1;
		}
		$original_file = $conf->admin->dir_output.'/'.$original_file;
	} elseif ($modulepart == 'admin_temp' && !empty($conf->admin->dir_temp)) {
		// Wrapping for upload file test
		if ($fuser->admin) {
			$accessallowed = 1;
		}
		$original_file = $conf->admin->dir_temp.'/'.$original_file;
	} elseif ($modulepart == 'bittorrent' && !empty($conf->bittorrent->dir_output)) {
		// Wrapping pour BitTorrent
		$accessallowed = 1;
		$dir = 'files';
		if (dol_mimetype($original_file) == 'application/x-bittorrent') {
			$dir = 'torrents';
		}
		$original_file = $conf->bittorrent->dir_output.'/'.$dir.'/'.$original_file;
	} elseif ($modulepart == 'member' && !empty($conf->member->dir_output)) {
		// Wrapping pour Foundation module
		if ($fuser->hasRight('adherent', $lire) || preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1;
		}
		$original_file = $conf->member->dir_output.'/'.$original_file;
		// If modulepart=module_user_temp	Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/temp/iduser
		// If modulepart=module_temp		Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/temp
		// If modulepart=module_user		Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart/iduser
		// If modulepart=module				Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart
		// If modulepart=module-abc			Allows any module to open a file if file is in directory called DOL_DATA_ROOT/modulepart
	} else {
		// GENERIC Wrapping
		//var_dump($modulepart);
		//var_dump($original_file);
		if (preg_match('/^specimen/i', $original_file)) {
			$accessallowed = 1; // If link to a file called specimen. Test must be done before changing $original_file int full path.
		}
		if ($fuser->admin) {
			$accessallowed = 1; // If user is admin
		}

		$tmpmodulepart = explode('-', $modulepart);
		if (!empty($tmpmodulepart[1])) {
			$modulepart = $tmpmodulepart[0];
			$original_file = $tmpmodulepart[1].'/'.$original_file;
		}

		// Define $accessallowed
		$reg = array();
		if (preg_match('/^([a-z]+)_user_temp$/i', $modulepart, $reg)) {
			$tmpmodule = $reg[1];
			if (empty($conf->$tmpmodule->dir_temp)) {	// modulepart not supported
				dol_print_error(null, 'Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->hasRight($tmpmodule, $lire) || $fuser->hasRight($tmpmodule, $read) || $fuser->hasRight($tmpmodule, $download)) {
				$accessallowed = 1;
			}
			$original_file = $conf->{$reg[1]}->dir_temp.'/'.$fuser->id.'/'.$original_file;
		} elseif (preg_match('/^([a-z]+)_temp$/i', $modulepart, $reg)) {
			$tmpmodule = $reg[1];
			if (empty($conf->$tmpmodule->dir_temp)) {	// modulepart not supported
				dol_print_error(null, 'Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->hasRight($tmpmodule, $lire) || $fuser->hasRight($tmpmodule, $read) || $fuser->hasRight($tmpmodule, $download)) {
				$accessallowed = 1;
			}
			$original_file = $conf->$tmpmodule->dir_temp.'/'.$original_file;
		} elseif (preg_match('/^([a-z]+)_user$/i', $modulepart, $reg)) {
			$tmpmodule = $reg[1];
			if (empty($conf->$tmpmodule->dir_output)) {	// modulepart not supported
				dol_print_error(null, 'Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->hasRight($tmpmodule, $lire) || $fuser->hasRight($tmpmodule, $read) || $fuser->hasRight($tmpmodule, $download)) {
				$accessallowed = 1;
			}
			$original_file = $conf->$tmpmodule->dir_output.'/'.$fuser->id.'/'.$original_file;
		} elseif (preg_match('/^massfilesarea_([a-z]+)$/i', $modulepart, $reg)) {
			$tmpmodule = $reg[1];
			if (empty($conf->$tmpmodule->dir_output)) {	// modulepart not supported
				dol_print_error(null, 'Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.')');
				exit;
			}
			if ($fuser->hasRight($tmpmodule, $lire) || preg_match('/^specimen/i', $original_file)) {
				$accessallowed = 1;
			}
			$original_file = $conf->$tmpmodule->dir_output.'/temp/massgeneration/'.$user->id.'/'.$original_file;
		} else {
			if (empty($conf->$modulepart->dir_output)) {	// modulepart not supported
				dol_print_error(null, 'Error call dol_check_secure_access_document with not supported value for modulepart parameter ('.$modulepart.'). The module for this modulepart value may not be activated.');
				exit;
			}

			// Check fuser->rights->modulepart->myobject->read and fuser->rights->modulepart->read
			$partsofdirinoriginalfile = explode('/', $original_file);
			if (!empty($partsofdirinoriginalfile[1])) {	// If original_file is xxx/filename (xxx is a part we will use)
				$partofdirinoriginalfile = $partsofdirinoriginalfile[0];
				if ($partofdirinoriginalfile && ($fuser->hasRight($modulepart, $partofdirinoriginalfile, 'lire') || $fuser->hasRight($modulepart, $partofdirinoriginalfile, 'read'))) {
					$accessallowed = 1;
				}
			}
			if ($fuser->hasRight($modulepart, $lire) || $fuser->hasRight($modulepart, $read)) {
				$accessallowed = 1;
			}

			if (is_array($conf->$modulepart->multidir_output) && !empty($conf->$modulepart->multidir_output[$entity])) {
				$original_file = $conf->$modulepart->multidir_output[$entity].'/'.$original_file;
			} else {
				$original_file = $conf->$modulepart->dir_output.'/'.$original_file;
			}
		}

		$parameters = array(
			'modulepart' => $modulepart,
			'original_file' => $original_file,
			'entity' => $entity,
			'fuser' => $fuser,
			'refname' => '',
			'mode' => $mode
		);
		$reshook = $hookmanager->executeHooks('checkSecureAccess', $parameters, $object);
		if ($reshook > 0) {
			if (!empty($hookmanager->resArray['original_file'])) {
				$original_file = $hookmanager->resArray['original_file'];
			}
			if (!empty($hookmanager->resArray['accessallowed'])) {
				$accessallowed = $hookmanager->resArray['accessallowed'];
			}
			if (!empty($hookmanager->resArray['sqlprotectagainstexternals'])) {
				$sqlprotectagainstexternals = $hookmanager->resArray['sqlprotectagainstexternals'];
			}
		}
	}

	$ret = array(
		'accessallowed' => ($accessallowed ? 1 : 0),
		'sqlprotectagainstexternals' => $sqlprotectagainstexternals,
		'original_file' => $original_file
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
	if (!dol_is_dir($directory)) {
		$result = dol_mkdir($directory);
		if ($result < -1) {
			dol_syslog("Failed to create the cache directory ".$directory, LOG_WARNING);
		}
	}
	$cachefile = $directory.$filename;

	file_put_contents($cachefile, serialize($object), LOCK_EX);

	dolChmod($cachefile, '0644');
}

/**
 * Test if Refresh needed.
 *
 * @param string 	$directory 		Directory of cache
 * @param string 	$filename 		Name of filecache
 * @param int 		$cachetime 		Cachetime delay
 * @return boolean 0 no refresh 1 if refresh needed
 */
function dol_cache_refresh($directory, $filename, $cachetime)
{
	$now = dol_now();
	$cachefile = $directory.$filename;
	$refresh = !file_exists($cachefile) || ($now - $cachetime) > dol_filemtime($cachefile);
	return $refresh;
}

/**
 * Read object from cachefile.
 *
 * @param string 	$directory 		Directory of cache
 * @param string 	$filename 		Name of filecache
 * @return mixed 					Unserialise from file
 */
function dol_readcachefile($directory, $filename)
{
	$cachefile = $directory.$filename;
	$object = unserialize(file_get_contents($cachefile));
	return $object;
}

/**
 * Return the relative dirname (relative to DOL_DATA_ROOT) of a full path string.
 *
 * @param 	string 	$pathfile		Full path of a file
 * @return 	string					Path of file relative to DOL_DATA_ROOT
 */
function dirbasename($pathfile)
{
	return preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'\//', '', $pathfile);
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

	foreach ($dir->md5file as $file) {    // $file is a simpleXMLElement
		$filename = $path.$file['name'];
		$file_list['insignature'][] = $filename;
		$expectedsize = (empty($file['size']) ? '' : $file['size']);
		$expectedmd5 = (string) $file;

		if (!file_exists($pathref.'/'.$filename)) {
			$file_list['missing'][] = array('filename' => $filename, 'expectedmd5' => $expectedmd5, 'expectedsize' => $expectedsize);
		} else {
			$md5_local = md5_file($pathref.'/'.$filename);

			if ($conffile == '/etc/dolibarr/conf.php' && $filename == '/filefunc.inc.php') {	// For install with deb or rpm, we ignore test on filefunc.inc.php that was modified by package
				$checksumconcat[] = $expectedmd5;
			} else {
				if ($md5_local != $expectedmd5) {
					$file_list['updated'][] = array('filename' => $filename, 'expectedmd5' => $expectedmd5, 'expectedsize' => $expectedsize, 'md5' => (string) $md5_local);
				}
				$checksumconcat[] = $md5_local;
			}
		}
	}

	foreach ($dir->dir as $subdir) {			// $subdir['name'] is  '' or '/accountancy/admin' for example
		getFilesUpdated($file_list, $subdir, $path.$subdir['name'].'/', $pathref, $checksumconcat);
	}

	return $file_list;
}

/**
 * Function to manage the drag and drop of a file.
 * We use global variable $object
 *
 * @param	string	$htmlname	The id of the component where we need to drag and drop
 * @return  string				Js script to display
 */
function dragAndDropFileUpload($htmlname)
{
	global $object, $langs;

	$out = "";
	$out .= '<div id="'.$htmlname.'Message" class="dragDropAreaMessage hidden"><span>'.img_picto("", 'download').'<br>'.$langs->trans("DropFileToAddItToObject").'</span></div>';
	$out .= "\n<!-- JS CODE TO ENABLE DRAG AND DROP OF FILE -->\n";
	$out .= "<script>";
	$out .= '
		jQuery(document).ready(function() {
			var enterTargetDragDrop = null;

			$("#'.$htmlname.'").addClass("cssDragDropArea");

			$(".cssDragDropArea").on("dragenter", function(ev, ui) {
				var dataTransfer = ev.originalEvent.dataTransfer;
				var dataTypes = dataTransfer.types;
				//console.log(dataTransfer);
				//console.log(dataTypes);

				if (!dataTypes || ($.inArray(\'Files\', dataTypes) === -1)) {
				    // The element dragged is not a file, so we avoid the "dragenter"
				    ev.preventDefault();
    				return false;
  				}

				// Entering drop area. Highlight area
				console.log("dragAndDropFileUpload: We add class highlightDragDropArea")
				enterTargetDragDrop = ev.target;
				$(this).addClass("highlightDragDropArea");
				$("#'.$htmlname.'Message").removeClass("hidden");
				ev.preventDefault();
			});

			$(".cssDragDropArea").on("dragleave", function(ev) {
				// Going out of drop area. Remove Highlight
				if (enterTargetDragDrop == ev.target){
					console.log("dragAndDropFileUpload: We remove class highlightDragDropArea")
					$("#'.$htmlname.'Message").addClass("hidden");
					$(this).removeClass("highlightDragDropArea");
				}
			});

			$(".cssDragDropArea").on("dragover", function(ev) {
				ev.preventDefault();
				return false;
			});

			$(".cssDragDropArea").on("drop", function(e) {
				console.log("Trigger event file dropped. fk_element='.dol_escape_js($object->id).' element='.dol_escape_js($object->element).'");
				e.preventDefault();
				fd = new FormData();
				fd.append("fk_element", "'.dol_escape_js($object->id).'");
				fd.append("element", "'.dol_escape_js($object->element).'");
				fd.append("token", "'.currentToken().'");
				fd.append("action", "linkit");

				var dataTransfer = e.originalEvent.dataTransfer;

				if (dataTransfer.files && dataTransfer.files.length){
					var droppedFiles = e.originalEvent.dataTransfer.files;
					$.each(droppedFiles, function(index,file){
						fd.append("files[]", file,file.name)
					});
				}
				$(".cssDragDropArea").removeClass("highlightDragDropArea");
				counterdragdrop = 0;
				$.ajax({
					url: "'.DOL_URL_ROOT.'/core/ajax/fileupload.php",
					type: "POST",
					processData: false,
					contentType: false,
					data: fd,
					success:function() {
						console.log("Uploaded.", arguments);
						/* arguments[0] is the json string of files */
						/* arguments[1] is the value for variable "success", can be 0 or 1 */
						let listoffiles = JSON.parse(arguments[0]);
						console.log(listoffiles);
						let nboferror = 0;
						for (let i = 0; i < listoffiles.length; i++) {
							console.log(listoffiles[i].error);
							if (listoffiles[i].error) {
								nboferror++;
							}
						}
						console.log(nboferror);
						if (nboferror > 0) {
							window.location.href = "'.$_SERVER["PHP_SELF"].'?id='.dol_escape_js($object->id).'&seteventmessages=ErrorOnAtLeastOneFileUpload:warnings";
						} else {
							window.location.href = "'.$_SERVER["PHP_SELF"].'?id='.dol_escape_js($object->id).'&seteventmessages=UploadFileDragDropSuccess:mesgs";
						}
					},
					error:function() {
						console.log("Error Uploading.", arguments)
						if (arguments[0].status == 403) {
							window.location.href = "'.$_SERVER["PHP_SELF"].'?id='.dol_escape_js($object->id).'&seteventmessages=ErrorUploadPermissionDenied:errors";
						}
						window.location.href = "'.$_SERVER["PHP_SELF"].'?id='.dol_escape_js($object->id).'&seteventmessages=ErrorUploadFileDragDropPermissionDenied:errors";
					},
				})
			});
		});
	';
	$out .= "</script>\n";
	return $out;
}

/**
 * Manage backup versions for a given file, ensuring only a maximum number of versions are kept.
 *
 * @param 	string 	$srcfile          	Full path of the source filename for the backups. Example /mydir/mydocuments/mymodule/filename.ext
 * @param 	int    	$max_versions     	The maximum number of backup versions to keep.
 * @param	string	$archivedir			Target directory of backups (without ending /). Keep empty to save into the same directory than source file.
 * @param	string	$suffix				'v' (versioned files) or 'd' (archived files after deletion)
 * @param	string	$moveorcopy			'move' or 'copy'
 * @return 	bool                    	Returns true if successful, false otherwise.
 */
function archiveOrBackupFile($srcfile, $max_versions = 5, $archivedir = '', $suffix = "v", $moveorcopy = 'move')
{
	$base_file_pattern = ($archivedir ? $archivedir : dirname($srcfile)).'/'.basename($srcfile).".".$suffix;
	$files_in_directory = glob($base_file_pattern . "*");

	// Extract the modification timestamps for each file
	$files_with_timestamps = [];
	foreach ($files_in_directory as $file) {
		$files_with_timestamps[] = [
			'file' => $file,
			'timestamp' => filemtime($file)
		];
	}

	// Sort the files by modification date
	$sorted_files = [];
	while (count($files_with_timestamps) > 0) {
		$latest_file = null;
		$latest_index = null;

		// Find the latest file by timestamp
		foreach ($files_with_timestamps as $index => $file_info) {
			if ($latest_file === null || (is_array($latest_file) && $file_info['timestamp'] > $latest_file['timestamp'])) {
				$latest_file = $file_info;
				$latest_index = $index;
			}
		}

		// Add the latest file to the sorted list and remove it from the original list
		if ($latest_file !== null) {
			$sorted_files[] = $latest_file['file'];
			unset($files_with_timestamps[$latest_index]);
		}
	}

	// Delete the oldest files to keep only the allowed number of versions
	if (count($sorted_files) >= $max_versions) {
		$oldest_files = array_slice($sorted_files, $max_versions - 1);
		foreach ($oldest_files as $oldest_file) {
			dol_delete_file($oldest_file);
		}
	}

	$timestamp = dol_now('gmt');
	$new_backup = $srcfile . ".v" . $timestamp;

	// Move or copy the original file to the new backup with the timestamp
	if ($moveorcopy == 'move') {
		$result = dol_move($srcfile, $new_backup, '0', 1, 0, 0);
	} else {
		$result = dol_copy($srcfile, $new_backup, '0', 1, 0, 0);
	}

	if (!$result) {
		return false;
	}

	return true;
}
