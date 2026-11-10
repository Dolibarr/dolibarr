<?php
/* Copyright (C) 2008-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2021  Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2016  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2016       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2019-2026  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2023-2026  Lenin Rivas         <lenin.rivas777@gmail.com>
 * Copyright (C) 2024-2026	MDW					<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025		William Mead		<william@m34d.com>
 * Copyright (C) 2026		Jose Martinez			<jose.martinez@pichinov.com>
 * Copyright (C) 2026		Joachim Kueter			<git-jk@bloxera.com>
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
 * @param	string			$utf8_path     			Starting path from which to search. This is a full path.
 * @param	string			$types        			Can be "directories", "files", or "all"
 * @param	int				$recursive				Determines whether subdirectories are searched
 * @param	string|string[]|null	$filter        	Regex or Array of Regex filter to restrict list. The regex value must be escaped for '/' by doing preg_quote($var,'/'), since this char is used for preg_match function,
 *                  	                    		but must NOT contains the start and end '/'. Filter is checked into the basename onlyof each entry (so '^xxx' include dirscanned/xxx and dirscanned/dirscanned2/xxx).
 * @param	string|string[]|null	$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.')). Exclude is checked both into fullpath and into basename (So '^xxx' may exclude 'xxx/dirscanned/...' and dirscanned/xxx').
 * @param	string			$sortcriteria			Sort criteria ('','fullname','relativename','name','date','size' or 'type,fullname')
 * @param	int 			$sortorder				Sort order (SORT_ASC, SORT_DESC)
 * @param	int				$mode					0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only, 4=Force load of perm
 * @param	int				$nohook					Disable all hooks
 * @param	string			$relativename			For recursive purpose only. Must be "" at first call.
 * @param	int 			$donotfollowsymlinks	Do not follow symbolic links
 * @param	int 			$nbsecondsold			Only files older than $nbsecondsold
 * @return	array<array{name:string,path:string,level1name:string,relativename:string,fullname:string,date:string,size:int,perm:int,type:string}> Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)>
 * @see dol_dir_list_in_database()
 */
function dol_dir_list($utf8_path, $types = "all", $recursive = 0, $filter = "", $excludefilter = null, $sortcriteria = "name", $sortorder = SORT_ASC, $mode = 0, $nohook = 0, $relativename = "", $donotfollowsymlinks = 0, $nbsecondsold = 0)
{
	global $hookmanager;
	global $object;

	if ($recursive <= 1) {	// Avoid too verbose log
		$error_info = "";

		// Verify filters (only on the first call of the function)
		$filter_ok = true;
		if (!empty($filter) && !is_array($filter)) {
			if (strlen($filter) > 25000) {	// Note that limit depends on syntax of filter
				dol_syslog("Value for filter is too large", LOG_ERR);
				$filter_ok = false;
			} else {
				// Check that all '/' are escaped.
				if ((int) preg_match('/(?:^|[^\\\\])\//', $filter) > 0) {
					$excludefilter_ok = false;
					$error_info .= " error='filter_has_unescaped_slash'";
					dol_syslog("'$filter' has unescaped '/'", LOG_ERR);
				}
			}
		}

		// Ensure we have an array for the exclusions
		$excludefilter_ok = true;
		$exclude_array = ($excludefilter === null || $excludefilter === '') ? array() : (is_array($excludefilter) ? $excludefilter : array($excludefilter));
		foreach ($exclude_array as $f) {
			// Check that all '/' are escaped.
			if ((int) preg_match('/(?:^|[^\\\\])\//', $f) > 0) {
				$excludefilter_ok = false;
				$error_info .= " error='excludefilter_has_unescaped_slash'";
				dol_syslog("'$f' has unescaped '/'", LOG_ERR);
			}
		}

		dol_syslog("files.lib.php::dol_dir_list path=".$utf8_path." types=".$types." recursive=".$recursive." filter=".json_encode($filter)." excludefilter=".json_encode($excludefilter).$error_info);
		// print 'xxx'."files.lib.php::dol_dir_list path=".$utf8_path." types=".$types." recursive=".$recursive." filter=".json_encode($filter)." excludefilter=".json_encode($exclude_array);
		if (!$filter_ok || !$excludefilter_ok) {
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

	$now = dol_now();
	$reshook = 0;
	$file_list = array();

	// Clean parameters
	$utf8_path = preg_replace('/([\\/]+)$/', '', $utf8_path);

	if (preg_match('/\*/', $utf8_path)) {
		$utf8_path_array = glob($utf8_path, GLOB_ONLYDIR);	// This scan dir for files. If file does not exists, return empty.
		//$os_path_array = dol_dir_list($utf8_path);
	} else {
		$utf8_path_array = array($utf8_path);
	}

	foreach ($utf8_path_array as $utf8_path_cursor) {
		$os_path = dol_osencode($utf8_path_cursor);
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
				continue;
			}

			if (($dir = opendir($os_path)) === false) {
				continue;
			}

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

				$utf8_fullpathfile = $utf8_path_cursor."/".$utf8_file;  // Temp variable for speed

				// Check if file is qualified
				$qualified = 1;
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

							$qualifiedforfilter = 0;
							if (empty($filter)) {
								$qualifiedforfilter = 1;
							} else {
								$testpregmatch = false;
								if (is_array($filter)) {
									$chunks = array_chunk($filter, 500);
									foreach ($chunks as $chunk) {
										$testpregmatch = preg_match('/'.implode('|', $chunk).'/i', $utf8_file);	// May failed if $filter too large
										if ($testpregmatch) {
											break;
										}
									}
								} else {
									$testpregmatch = preg_match('/'.$filter.'/i', $utf8_file);		// May failed if $filter too large
								}
								if ($testpregmatch) {
									$qualifiedforfilter = 1;
								}
							}

							if ($qualifiedforfilter) {	// We do not search key $filter into all $path, only into $file part
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

						$qualifiedforfilter = 0;
						if (empty($filter)) {
							$qualifiedforfilter = 1;
						} else {
							$testpregmatch = false;
							if (is_array($filter)) {
								$chunks = array_chunk($filter, 500);
								foreach ($chunks as $chunk) {
									$testpregmatch = preg_match('/'.implode('|', $chunk).'/i', $utf8_file);	// May failed if $filter too large
									if ($testpregmatch) {
										break;
									}
								}
							} else {
								$testpregmatch = preg_match('/'.$filter.'/i', $utf8_file);		// May failed if $filter too large
							}
							if ($testpregmatch) {
								$qualifiedforfilter = 1;
							}
						}

						if ($qualifiedforfilter) {	// We do not search key $filter into all $path, only into $file part
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
		}
	}

	// Obtain a list of columns
	if (!empty($sortcriteria) && $sortorder) {
		$file_list = dol_sort_array($file_list, $sortcriteria, ($sortorder == SORT_ASC ? 'asc' : 'desc'));
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
 * @param	string		$path        	Starting path from which to search. Example: 'produit/MYPROD' or 'produit/%'. Must not end with "/".
 * @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	string[]|null	$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview.*\.png)$','^\.'))
 * @param	string		$sortcriteria	Sort criteria ("","fullname","name","date","size")
 * @param	int			$sortorder		Sort order (SORT_ASC, SORT_DESC)
 * @param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like description
 * @param	string		$sqlfilters		Filter as an Universal Search string.
 * 										Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
 * @param	?Object		$object			Object used
 * @return	array<array{rowid:string,label:string,name:string,path:string,level1name:string,fullname:string,fullpath_orig:string,date_creation:string,date_m:string,type:string,keywords:string,cover:string,position:int,acl:string,share:string,description:string}> Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file',...)
 * @see dol_dir_list()
 */
function dol_dir_list_in_database($path, $filter = "", $excludefilter = null, $sortcriteria = "name", $sortorder = SORT_ASC, $mode = 0, $sqlfilters = "", $object = null)
{
	global $conf, $db;

	if (is_null($object)) {
		$object = new stdClass();
	}

	$sql = "SELECT rowid, label, entity, filename, filepath, fullpath_orig, keywords, cover, gen_or_uploaded, extraparams,";
	$sql .= " date_creation, tms as date_m, fk_user_c, fk_user_m, acl, position, share";
	if ($mode) {
		$sql .= ", description";
	}
	$sql .= " FROM ".MAIN_DB_PREFIX."ecm_files";
	if (!empty($object->entity)) {
		$sql .= " WHERE entity = ".((int) $object->entity);
	} else {
		$sql .= " WHERE entity = ".((int) $conf->entity);
	}
	if (preg_match('/%$/', $path)) {
		$sql .= " AND (filepath LIKE '".$db->escape($path)."' OR filepath = '".$db->escape(preg_replace('/\/%$/', '', $path))."')";
	} else {
		$sql .= " AND filepath = '".$db->escape($path)."'";
	}

	// Manage filter
	$errormessage = '';
	$sql .= forgeSQLFromUniversalSearchCriteria($sqlfilters, $errormessage);
	if ($errormessage) {
		dol_print_error(null, $errormessage);
		return array();
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
					"date_creation" => $db->jdate($obj->date_creation),
					"date_m" => $db->jdate($obj->date_m),
					"type" => 'file',
					"keywords" => $obj->keywords,
					"cover" => $obj->cover,
					"position" => (int) $obj->position,
					"acl" => $obj->acl,
					"share" => $obj->share,
					"description" => ($mode ? $obj->description : '')
					// TODO Add 'content' with $mode == 2 ?
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
 * @param	?Object	$object				Object used
 * @return	void
 */
function completeFileArrayWithDatabaseInfo(&$filearray, $relativedir, $object = null)
{
	global $conf, $db, $user;

	if (is_null($object)) {
		$object = new stdClass();
		$object->id = null;
		$object->element = null;
	}

	$filearrayindatabase = dol_dir_list_in_database(rtrim($relativedir, "/\\"), '', null, 'name', SORT_ASC, 0, '', $object);

	global $modulepart;
	// Note: $modulepart is 'product' when set by product/document.php, but 'produit' in some other contexts, so we accept both.
	if (in_array($modulepart, array('produit', 'product')) && getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
		// TODO Remove this when PRODUCT_USE_OLD_PATH_FOR_PHOTO will be removed
		global $object;
		'@phan-var-force CommonObject $object';
		if (!empty($object->id)) {
			if (isModEnabled("product")) {
				$upload_dirold = $conf->product->multidir_output[$object->entity ?? $conf->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
			} else {
				$upload_dirold = $conf->service->multidir_output[$object->entity ?? $conf->entity].'/'.substr(substr("000".$object->id, -2), 1, 1).'/'.substr(substr("000".$object->id, -2), 0, 1).'/'.$object->id."/photos";
			}

			$relativedirold = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $upload_dirold);
			$relativedirold = ltrim($relativedirold, "/\\");

			// Note: $object must be provided so the entity filter matches the one used to forge $upload_dirold (multicompany)
			$filearrayindatabase = array_merge($filearrayindatabase, dol_dir_list_in_database($relativedirold, '', null, 'name', SORT_ASC, 0, '', $object));
		}
	} elseif ($modulepart == 'ticket') {
		foreach ($filearray as $key => $val) {
			$rel_dir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $filearray[$key]['path']);
			$rel_dir = trim($rel_dir, "/\\");
			if ($rel_dir != $relativedir) {
				$filearrayindatabase = array_merge($filearrayindatabase, dol_dir_list_in_database($rel_dir, '', null, 'name', SORT_ASC));
			}
		}
	}

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
				if (is_object($object)) {
					$ecmfile->src_object_type = $object->element;
					$ecmfile->src_object_id = $object->id;
				}
				$ecmfile->description = ''; // indexed content
				$ecmfile->keywords = ''; // keyword content
				// When you scan file with dol_dir_list_in_database, you scan for files in entity of object (like with projects), even if you
				// are connected into another entity. So we must also create record that was not found into the entity scan, so the one of the object).
				$ecmfile->entity = empty($object->entity) ? $conf->entity : $object->entity;

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
 * @param	string						$srcfile			       Source file (can't be a directory)
 * @param	array<string,null|string|int> 	$arrayreplacement	       Array with strings to replace. Example: array('valuebefore'=>'valueafter', ...)
 * @param	string						$destfile			       Destination file (can't be a directory). If empty, will be same than source file.
 * @param	string						$newmask			       Mask for new file. '0' by default means getDolGlobalString('MAIN_UMASK'). Example: '0666'.
 * @param	int							$indexdatabase		       1=index new file into database.
 * @param   int     					$arrayreplacementisregex   1=Array of replacement is already an array with key that is a regex. Warning: the key must be escaped with preg_quote for '/' and include the starting/ending '/' in string.
 * @return	int												       Return integer <0 if error, 0 if nothing done (dest file already exists), >0 if OK
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
			$content = preg_replace($key, (string) $value, $content);
		}
	}

	file_put_contents($newpathoftmpdestfile, $content);
	dolChmod($newpathoftmpdestfile, $newmask);

	// Rename
	$moreinfo = array('gen_or_uploaded' => 'unknown');
	$result = dol_move($newpathoftmpdestfile, $newpathofdestfile, $newmask, (($destfile == $srcfile) ? 1 : 0), 0, $indexdatabase, $moreinfo);
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
 * Removes content from a file that matches a given pattern.
 *
 * @param string $filePath Path to the file to be processed.
 * @param string $pattern Regular expression pattern to identify the content to remove.
 * @return bool Returns true if the operation was successful, false otherwise.
 */
function removePatternFromFile(string $filePath, string $pattern): bool
{
	// Check if the file exists
	if (! file_exists($filePath)) {
		dol_syslog("files.lib.php::removePatternFromFile: File $filePath does not exist", LOG_WARNING);

		return false;
	}

	// Read the file content
	$content = file_get_contents($filePath);
	if ($content === false) {
		dol_syslog("files.lib.php::removePatternFromFile: Unable to read the file $filePath", LOG_WARNING);

		return false;
	}

	// Remove content matching the pattern
	$updatedContent = preg_replace($pattern, '', $content);
	if ($updatedContent === null) {
		dol_syslog("files.lib.php::removePatternFromFile: Error while processing the file $filePath", LOG_WARNING);

		return false;
	}

	// Write the updated content back to the file
	$result = file_put_contents($filePath, $updatedContent);
	if ($result === false) {
		dol_syslog("files.lib.php::removePatternFromFile: Permission denied to overwrite the target file $filePath", LOG_WARNING);

		return false;
	}

	dol_syslog("files.lib.php::removePatternFromFile: Content successfully removed in the file $filePath", LOG_INFO);

	return true;
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
 * @param	?array<string,string>	$arrayreplacement		Array to use to replace filenames with another one during the copy (works only on file names, not on directory names).
 * @param	int						$excludesubdir			0=Do not exclude subdirectories, 1=Exclude subdirectories, 2=Exclude subdirectories if name is not a 2 chars (used for country codes subdirectories).
 * @param	?string[]				$excludefileext			Exclude some file extensions
 * @param	int						$excludearchivefiles	Exclude archive files that start with v+timestamp or d+timestamp (0 by default)
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
		$tmpresult = 0;  // Initialised before loop to keep old behavior, may be needed inside loop
		while ($file = readdir($dir_handle)) {
			if ($file != "." && $file != ".." && !is_link($ossrcfile."/".$file)) {
				if (is_dir($ossrcfile."/".$file)) {
					if (empty($excludesubdir) || ($excludesubdir == 2 && dol_strlen($file) == 2)) {
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
 * @param	string				$srcfile			Source file (can't be a directory. use native php @rename() to move a directory)
 * @param   string				$destfile			Destination file (can't be a directory. use native php @rename() to move a directory)
 * @param   string				$newmask			Mask in octal string for new file ('0' by default means $conf->global->MAIN_UMASK)
 * @param   int<0,1>			$overwriteifexists	Overwrite file if exists (1 by default)
 * @param   int<0,1>			$testvirus			Do an antivirus test. Move is canceled if a virus is found.
 * @param	int<0,1>			$indexdatabase		Index new file into database.
 * @param	array<string,mixed>	$moreinfo			Array with more information to set in index table
 * @param	int|null			$entity				Entity (it's null by default to avoid problem with entity = 0)
 * @return  boolean									True if OK, false if KO
 * @see dol_move_uploaded_file()
 */
function dol_move($srcfile, $destfile, $newmask = '0', $overwriteifexists = 1, $testvirus = 0, $indexdatabase = 1, $moreinfo = array(), $entity = null)
{
	global $user, $db;
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
				$resultecmtarget = $ecmfiletarget->fetch(0, '', $rel_filetorenameafter, '', '', '', 0, $entity);
				if ($resultecmtarget > 0) {   // An entry for target name already exists for target, we delete it, a new one will be created.
					$ecmfiletarget->delete($user);
				}

				$ecmfile = new EcmFiles($db);
				$resultecm = $ecmfile->fetch(0, '', $rel_filetorenamebefore, '', '', '', 0, $entity);
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
					if (!empty($moreinfo) && !empty($moreinfo['gen_or_uploaded'])) {
						$ecmfile->gen_or_uploaded = $moreinfo['gen_or_uploaded'];
					} else {
						$ecmfile->gen_or_uploaded = 'unknown';	// 'generated', 'uploaded', 'api'
					}
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
						$ecmfile->src_object_type = $moreinfo['src_object_type'];		// Usually the $object->table_element
					}
					if (!empty($moreinfo) && !empty($moreinfo['src_object_id'])) {
						$ecmfile->src_object_id = $moreinfo['src_object_id'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['agenda_id'])) {
						$ecmfile->agenda_id = $moreinfo['agenda_id'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['position'])) {
						$ecmfile->position = $moreinfo['position'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['cover'])) {
						$ecmfile->cover = $moreinfo['cover'];
					}
					if (!empty($moreinfo) && !empty($moreinfo['share'])) {
						$ecmfile->share = $moreinfo['share'];
					}
					if (! empty($entity)) {
						$ecmfile->entity = $entity;
					}

					$resultecm = $ecmfile->create($user);
					if ($resultecm < 0) {
						setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
					} else {
						if (!empty($moreinfo) && !empty($moreinfo['array_options']) && is_array($moreinfo['array_options'])) {
							$ecmfile->array_options = $moreinfo['array_options'];
							$resultecm = $ecmfile->insertExtraFields();
							if ($resultecm < 0) {
								setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
							}
						}
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
								$moreinfo = array('gen_or_uploaded' => 'unknown');
								$res = dol_move($filepath.'/'.$oldname, $filepath.'/'.$newname, '0', $overwriteifexists, 0, $indexdatabase, $moreinfo);
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
 * @return  string[]                    Array of errors (the translation key of the error is used as array key when the check has one), or empty array if not virus found
 */
function dolCheckVirus($src_file, $dest_file = '')
{
	global $db;

	$reterrors = dolCheckOnFileName($src_file, $dest_file);
	if (!empty($reterrors)) {
		return $reterrors;
	}

	if (getDolGlobalString('MAIN_ANTIVIRUS_UPLOAD_ON')) {
		if (!class_exists('AntiVir')) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/antivir.class.php';
		}
		$antivir = new AntiVir($db);
		$result = $antivir->dol_avscan_file($src_file);
		if ($result < 0) {	// If virus or error, we stop here
			if ($result == -99) {
				$reterrors['ErrorFileIsInfectedKnownVirus'] = implode(', ', $antivir->errors);
			} else {  // -98, -97, etc.
				$reterrors['ErrorAntivirusNotAvailable'] = implode(', ', $antivir->errors);
			}
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
 * @return  string[]                    Array of errors (the translation key of the error is used as array key when the check has one), or empty array if not virus found
 */
function dolCheckOnFileName($src_file, $dest_file = '')
{
	if (preg_match('/\.pdf$/i', $dest_file)) {
		if (!getDolGlobalString('MAIN_ANTIVIRUS_ALLOW_JS_IN_PDF')) {
			dol_syslog("dolCheckOnFileName Check that pdf does not contains js code");

			$tmp = file_get_contents(trim($src_file));
			if (preg_match('/[\n\s]+\/JavaScript[\n\s]+/m', $tmp)) {
				return array('ErrorFileIsAnInfectedPDFWithJSInside' => 'File is a PDF with javascript inside');
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
 * 	@param	string	$keyforsourcefile	Key for source file in _FILES (not used)
 *  @param	string	$upload_dir			For information. Already included into $dest_file.
 *  @param	int		$mode				0=Default mode use to move a file from the default system upload dir (/tmp/...) into $upload_dir. 1=Mode to move an uploaded file with a simple rename (when uploaded dir is in same filesystem than $upload_dir).
 *	@return int|string       			1 if OK, 2 if OK and .noexe appended, <0 or string if KO
 *  @see    dol_move()
 */
function dol_move_uploaded_file($src_file, $dest_file, $allowoverwrite, $disablevirusscan = 0, $uploaderrorcode = 0, $nohook = 0, $keyforsourcefile = 'addedfile', $upload_dir = '', $mode = 0)
{
	global $conf;
	global $object, $hookmanager;

	$reshook = 0;
	$file_name = $dest_file;
	$successcode = 1;

	if (empty($nohook)) {
		$reshook = $hookmanager->initHooks(array('fileslib'));

		$parameters = array('dest_file' => $dest_file, 'src_file' => $src_file, 'file_name' => $file_name, 'varfiles' => $keyforsourcefile, 'allowoverwrite' => $allowoverwrite);
		$reshook = $hookmanager->executeHooks('moveUploadedFile', $parameters, $object);
	}

	if (empty($reshook)) {
		// If an upload error has been reported
		if ($uploaderrorcode) {
			switch ($uploaderrorcode) {
				case UPLOAD_ERR_INI_SIZE:	// 1
					return 'ErrorFileSizeTooLarge';
				case UPLOAD_ERR_FORM_SIZE:	// 2 - Exceed the MAX_FILE_SIZE specified into a field in form
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
				// We return a translation key alone, so the caller can translate it. The technical message of the
				// antivirus is not appended to it (that would break the translation), it is kept into the log only.
				foreach (array_keys($checkvirusarray) as $errorkey) {
					if (is_string($errorkey)) {	// A check that knows its own error key returns it as array key
						return $errorkey;
					}
				}
				return 'ErrorFileIsInfectedWithAVirus';
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

		// Move file using a simple system function
		if ($mode == 0) {
			$return = move_uploaded_file($src_file_osencoded, $file_name_osencoded);
		} else {
			$return	= rename($src_file_osencoded, $file_name_osencoded);
		}

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
