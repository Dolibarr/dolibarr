<?php
/* Copyright (C) 2008-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012-2015  Regis Houssin       <regis.houssin@capnetworks.com>
 * Copyright (C) 2012       Juanjo Menent       <jmenent@2byte.es>
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
 *  @param	string		$path        	Starting path from which to search
 *  @param	string		$types        	Can be "directories", "files", or "all"
 *  @param	int			$recursive		Determines whether subdirectories are searched
 *  @param	string		$filter        	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 *  @param	array		$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview\.png)$','^\.'))
 *  @param	string		$sortcriteria	Sort criteria ("","fullname","name","date","size")
 *  @param	string		$sortorder		Sort order (SORT_ASC, SORT_DESC)
 *	@param	int			$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
 *  @param	int			$nohook			Disable all hooks
 *  @return	array						Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file')
 */
function dol_dir_list($path, $types="all", $recursive=0, $filter="", $excludefilter="", $sortcriteria="name", $sortorder=SORT_ASC, $mode=0, $nohook=false)
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

	if (! $nohook)
	{
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
		$reshook=$hookmanager->executeHooks('getNodesList', $parameters, $object);
	}

	// $reshook may contain returns stacked by other modules
	// $reshook is always empty with an array for can not lose returns stacked with other modules
	// $hookmanager->resArray may contain array stacked by other modules
	if (! $nohook && ! empty($hookmanager->resArray)) // forced to use $hookmanager->resArray even if $hookmanager->resArray['nodes'] is empty
	{
		return $hookmanager->resArray['nodes'];
	}
	else
	{
		if (! is_dir($newpath)) return array();

		if ($dir = opendir($newpath))
		{
			$filedate='';
			$filesize='';
			$file_list = array();

			while (false !== ($file = readdir($dir)))
			{
				if (! utf8_check($file)) $file=utf8_encode($file);	// To be sure data is stored in utf8 in memory

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
					if (preg_match('/'.$filt.'/i',$file)) {
						$qualified=0; break;
					}
				}

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

							if (! $filter || preg_match('/'.$filter.'/i',$file))	// We do not search key $filter into $path, only into $file
							{
								preg_match('/([^\/]+)\/[^\/]+$/',$path.'/'.$file,$reg);
								$level1name=(isset($reg[1])?$reg[1]:'');
								$file_list[] = array(
										"name" => $file,
										"path" => $path,
										"level1name" => $level1name,
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
							$file_list = array_merge($file_list,dol_dir_list($path."/".$file, $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder, $mode, $nohook));
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

			return $file_list;
		}
		else
		{
			return array();
		}
	}
}

/**
 *  Scan a directory and return a array of files/directories from a selection.
 *  Content for string is UTF8 and dir separator is "/".
 *
 *  @param	int	$fk_soc        	select socid - for your selection in array
 *  @param	string	$module_get     Starting path from which to search
 *  @param	string	$sortorder	SORT_ASC or SORT_DESC
 *  @param	array		$excludefiles   Array of Regex for exclude filter (example: array('(\.meta|_preview\.png)$','^\.'))
 *  @return	array		Array of array( filefolder=> array( filelabel=> array( file=> array('name'=>'xxx','date'=>'yyy','size'=>99,'type'=>'dir|file'))))
 */
function get_soc_file_array($fk_soc, $module_get = false, $sortorder = false, $excludefiles = false) 
{
	global $user, $conf, $db;

	$sortfield = "date";

	if(!$sortorder){
		$sorting = SORT_DESC;
	}else{
		$sorting = $sortorder;
	}

	$ar_modules_get = array();
	if (is_array($module_get)) $ar_modules_get = $module_get;
	elseif (strlen($module_get) > 0) $ar_modules_get[$module_get] = $module_get;
	else
	{
		$ar_modules_get['company']	= 'company';
		$ar_modules_get['dolimail']	= 'dolimail';
		$ar_modules_get['actions']	= 'actions';
		$ar_modules_get['invoice'] 	= 'invoice';
		$ar_modules_get['order']   	= 'order';
		$ar_modules_get['propal']  	= 'propal';
		$ar_modules_get['contract']	= 'contract';
		$ar_modules_get['project'] 	= 'project';
		$ar_modules_get['invoice_supplier']	= 'invoice_supplier';
		$ar_modules_get['order_supplier']	= 'order_supplier';
	}

	
	// rights
	if (count($ar_modules_get) > 0)
	foreach($ar_modules_get as $curmodule)
	{
		switch($curmodule)
		{
			case 'company':
			if (! empty($conf->societe->enabled))    // Recht Alle oder nur die Vertriebspartneradressen
				$ar_modules_secure['company']['outputdir'] = $conf->societe->dir_output;
			break;
			case 'dolimail':
			if (! empty($conf->dolimail->enabled) && ($user->rights->dolimail->read || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->dolimail->dir_output.'/attachments';
			break;
			case 'actions':
			if (! empty($conf->agenda->enabled) || ($user->rights->agenda->allactions->read || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->agenda->dir_output;
			break;
			case 'invoice':
			if (! empty($conf->facture->enabled) && ($user->rights->facture->lire || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->facture->dir_output;
			break;
			case 'order':
			if (!empty($conf->commande->enabled) && ($user->rights->commande->lire || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->commande->dir_output;
			break;
			case 'propal':
			if (!empty($conf->propal->enabled) && ($user->rights->propale->lire || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->propal->dir_output;
			break;
			case 'project':
			if (! empty($conf->projet->enabled) && ($user->rights->projet->lire || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->projet->dir_output;
			break;
			case 'invoice_supplier':
			if (! empty($conf->fournisseur->enabled) && ($user->rights->fournisseur->facture->lire || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->fournisseur->dir_output.'/facture';
			break;
			case 'order_supplier':
			if (! empty($conf->fournisseur->enabled) && ($user->rights->fournisseur->commande->lire || $user->admin))
				$ar_modules_secure[$curmodule]['outputdir']=$conf->fournisseur->dir_output.'/commande';
			break;
		}
	}
			/* TODO make a outputdir*/
			//unset($ar_modules_secure['dolimail']);
			unset($ar_modules_secure['project']); // project (list with project) is "ref"
			unset($ar_modules_secure['actions']);
			unset($ar_modules_secure['contract']);
			/* TODO make a outputdir*/
			unset($curmodule);

	if($fk_soc > 0)
	$ar_modules_get = $ar_modules_secure;
	
	$xy=0;
	if (count($ar_modules_get)>0)
	foreach($ar_modules_get as $curmodule => $myarray)
	{
		if($fk_soc > 0 && $curmodule != "company")
		{

			// SQL to find documents (ref number)

			if($curmodule == "invoice") 					$sql = "SELECT facnumber as refstr FROM ".MAIN_DB_PREFIX."facture";
			elseif($curmodule == "order") 					$sql = "SELECT ref as refstr FROM ".MAIN_DB_PREFIX."commande";
			elseif($curmodule == "invoice_supplier") 		$sql = "SELECT rowid as refstr FROM ".MAIN_DB_PREFIX."facture_fourn";
			elseif($curmodule == "order_supplier") 			$sql = "SELECT ref as refstr FROM ".MAIN_DB_PREFIX."commande_fournisseur";
			elseif($curmodule == "propal") 					$sql = "SELECT ref as refstr FROM ".MAIN_DB_PREFIX."propal";
			elseif($curmodule == "contract") 				$sql = "SELECT ref as refstr FROM ".MAIN_DB_PREFIX."contrat";
			elseif($curmodule == "dolimail")				$sql = "SELECT uid as refstr, subject FROM ".MAIN_DB_PREFIX."mails";

			$sql.= ' WHERE entity IN ('.getEntity('societe', 1).')';
			$sql.= " AND fk_soc = '".$fk_soc."'";
			

			$res = $db->query($sql);
			if ($res && $db->num_rows($res) > 0)
			{
				while($obj = $db->fetch_object($res))
				{
					$ar_modules_secure[$curmodule]['socref'][] = $obj->refstr; 
					if($curmodule == "dolimail") $ar_modules_secure['dolimail']['subject'][$obj->refstr] = $obj->subject; 						
				}
			}
			else
			{
				unset($ar_modules_secure[$curmodule]);
				continue;
				$errors[]="SQL Error: ".$sql;
				$error++;
			}

			
		}else{
			if($curmodule == "dolimail")				$sql = "SELECT uid as refstr, subject FROM ".MAIN_DB_PREFIX."mails";
			
			$res = $db->query($sql);
			if ($res && $db->num_rows($res) > 0)
			{
				while($obj = $db->fetch_object($res))
				{
					if($curmodule == "dolimail") $ar_modules_secure['dolimail']['subject'][$obj->refstr] = $obj->subject; 
				}
			}
		}

		// Data in Array
		// Get Array from ar_module

		$output[$curmodule]=dol_dir_list($myarray['outputdir'],"files",1,'', $excludefiles, $sortfield, $sorting,1);
		if($fk_soc > 0)
		{
			if($curmodule == "company")
			{
				foreach($output["company"] as $label => $filedata)
				{
					if($filedata['level1name'] != $fk_soc)
					{
						unset($output['company'][$label]);
					}
				}
			}
			
			elseif($curmodule == "invoice")
			{
				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]); continue; } // wenn no file exsit
				foreach($output["invoice"] as $label => $filedata)
				{
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref']))
					{
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($fac_supp_N_arr) array
					}

				}
			}
			elseif($curmodule == "invoice_supplier")
			{
				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]);  } // throw all ref number who are not in ($fac_invoice_arr) array
				foreach($output["invoice_supplier"] as $label => $filedata)
				{
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref'])) {
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($fac_supp_N_arr) array
					}
				}
			}
			elseif($curmodule == "order")
			{
				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]); continue; } // throw all ref number who are not in ($fac_invoice_arr) array
				foreach($output["order"] as $label => $filedata)
				{
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref'])) {
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($ref_order_arr) array
					}
				}
			}
			elseif($curmodule == "order_supplier")
			{
				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]); continue; } // throw all ref number who are not in ($fac_invoice_arr) array
				foreach($output["order_supplier"] as $label => $filedata)
				{
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref'])) {
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($ref_order_supp_arr) array
					}
				}
			}
			elseif($curmodule == "propal")
			{
				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]); continue; } // throw all ref number who are not in ($fac_invoice_arr) array
				foreach($output["propal"] as $label => $filedata)
				{
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref'])) {
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($ref_propal_arr) array
					}
				}
			}
			elseif($curmodule == "contract")
			{
				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]); continue; } // throw all ref number who are not in ($fac_invoice_arr) array
				foreach($output["contract"] as $label => $filedata)
				{
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref'])) {
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($ref_contract_arr) array
					}
				}
			}
			elseif($curmodule == "dolimail")
			{

				if (! is_array($ar_modules_secure[$curmodule]['socref'])) { unset($output[$curmodule]); continue; } // throw all ref number who are not in ($fac_invoice_arr) array
				foreach($output["dolimail"] as $label => $filedata)
				{
					if($filedata['name'] == "winmail.dat" || $filedata['name'] == "smime.p7s") unset($output[$curmodule][$label]);
					else
					if (! in_array($filedata['level1name'], $ar_modules_secure[$curmodule]['socref'])) {
						unset($output[$curmodule][$label]); // throw all ref number who are not in ($ref_propal_arr) array
					}
				}
			}
			// Error if ther isn't any File
			if(count($output[$curmodule]) == 0)
			{
				$error++;
				$errors[]="Error [404]: No File found for User: ".$fk_soc." in module: ".$curmodule;
				unset($output[$curmodule]);
			}
		}

		// Extra for Mail attachments
		if($curmodule == "dolimail" && count($output["dolimail"])>0)
		{
			foreach($output["dolimail"] as $label => $filedata)
			{
				$output[$curmodule][$label]['subject'] = $ar_modules_secure['dolimail']['subject'][$filedata['level1name']];
			}
		}

		// Extra for Mail attachments
		if($curmodule == "invoice" && count($output["invoice"])>0)
		{
			foreach($output["invoice"] as $label => $filedata)
			{
				$output[$curmodule][$label]['subject'] = $filedata['level1name'];
			}
		}

		// Extra for Mail attachments
		if($curmodule == "invoice_supplier" && count($output["invoice_supplier"])>0)
		{
			foreach($output["invoice_supplier"] as $label => $filedata)
			{
				$output[$curmodule][$label]['subject'] = $filedata['level1name'];
			}
		}

		// Extra for Mail attachments
		if($curmodule == "contract" && count($output["contract"])>0)
		{
			foreach($output["contract"] as $label => $filedata)
			{
				$output[$curmodule][$label]['subject'] = $filedata['level1name'];
			}
		}		

		$xy++;
	}

	if(count($output) > 0)
	{
		return $output;
	}
	else
	{
		return -1;
	}
}

/**
 *  Calculate Bytes to kb, mb and translate it to current language
 *
 *  @param	int	$byt        	Bytes
 *  @return	string	calculated string
 */
function calculate_byte($byt)
{
	global $langs;
		
	if ($byt < 1024) {
		$unit = '&nbsp;'.$langs->trans("b");
		$mailsize=$byt;
	} else if ($byt / 1024 > 1024) {
		$mailsize = $byt / 1024 / 1024;
		$unit = '&nbsp;'.$langs->trans("Mb");
	} else {
		$mailsize = $byt / 1024;
		$unit = '&nbsp;'.$langs->trans("Kb");
	}

	$val = number_format($mailsize, 2).$unit;

	return $val;
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
 *	Return mime type of a file
 *
 *	@param	string	$file		Filename we looking for MIME type
 *  @param  string	$default    Default mime type if extension not found in known list
 * 	@param	int		$mode    	0=Return full mime, 1=otherwise short mime string, 2=image for mime type, 3=source language
 *	@return string 		    	Return a mime type family (text/xxx, application/xxx, image/xxx, audio, video, archive)
 *  @see    image_format_supported (images.lib.php)
 */
function dol_mimetype($file,$default='application/octet-stream',$mode=0)
{
	$mime=$default;
    $imgmime='other.png';
    $srclang='';

    $tmpfile=preg_replace('/\.noexe$/','',$file);

	// Text files
	if (preg_match('/\.txt$/i',$tmpfile))         			   { $mime='text/plain'; $imgmime='text.png'; }
	if (preg_match('/\.rtx$/i',$tmpfile))                      { $mime='text/richtext'; $imgmime='text.png'; }
	if (preg_match('/\.csv$/i',$tmpfile))					   { $mime='text/csv'; $imgmime='text.png'; }
	if (preg_match('/\.tsv$/i',$tmpfile))					   { $mime='text/tab-separated-values'; $imgmime='text.png'; }
	if (preg_match('/\.(cf|conf|log)$/i',$tmpfile))            { $mime='text/plain'; $imgmime='text.png'; }
    if (preg_match('/\.ini$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='ini'; }
    if (preg_match('/\.css$/i',$tmpfile))                      { $mime='text/css'; $imgmime='css.png'; $srclang='css'; }
	// Certificate files
	if (preg_match('/\.(crt|cer|key|pub)$/i',$tmpfile))        { $mime='text/plain'; $imgmime='text.png'; }
	// HTML/XML
	if (preg_match('/\.(html|htm|shtml)$/i',$tmpfile))         { $mime='text/html'; $imgmime='html.png'; $srclang='html'; }
    if (preg_match('/\.(xml|xhtml)$/i',$tmpfile))              { $mime='text/xml'; $imgmime='other.png'; $srclang='xml'; }
	// Languages
	if (preg_match('/\.bas$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='bas'; }
	if (preg_match('/\.(c)$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='c'; }
    if (preg_match('/\.(cpp)$/i',$tmpfile))                    { $mime='text/plain'; $imgmime='text.png'; $srclang='cpp'; }
    if (preg_match('/\.(h)$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='h'; }
    if (preg_match('/\.(java|jsp)$/i',$tmpfile))               { $mime='text/plain'; $imgmime='text.png'; $srclang='java'; }
	if (preg_match('/\.php([0-9]{1})?$/i',$tmpfile))           { $mime='text/plain'; $imgmime='php.png'; $srclang='php'; }
	if (preg_match('/\.phtml$/i',$tmpfile))                    { $mime='text/plain'; $imgmime='php.png'; $srclang='php'; }
	if (preg_match('/\.(pl|pm)$/i',$tmpfile))                  { $mime='text/plain'; $imgmime='pl.png'; $srclang='perl'; }
	if (preg_match('/\.sql$/i',$tmpfile))                      { $mime='text/plain'; $imgmime='text.png'; $srclang='sql'; }
	if (preg_match('/\.js$/i',$tmpfile))                       { $mime='text/x-javascript'; $imgmime='jscript.png'; $srclang='js'; }
	// Open office
	if (preg_match('/\.odp$/i',$tmpfile))                      { $mime='application/vnd.oasis.opendocument.presentation'; $imgmime='ooffice.png'; }
	if (preg_match('/\.ods$/i',$tmpfile))                      { $mime='application/vnd.oasis.opendocument.spreadsheet'; $imgmime='ooffice.png'; }
	if (preg_match('/\.odt$/i',$tmpfile))                      { $mime='application/vnd.oasis.opendocument.text'; $imgmime='ooffice.png'; }
	// MS Office
	if (preg_match('/\.mdb$/i',$tmpfile))					   { $mime='application/msaccess'; $imgmime='mdb.png'; }
	if (preg_match('/\.doc(x|m)?$/i',$tmpfile))				   { $mime='application/msword'; $imgmime='doc.png'; }
	if (preg_match('/\.dot(x|m)?$/i',$tmpfile))				   { $mime='application/msword'; $imgmime='doc.png'; }
	if (preg_match('/\.xlt(x)?$/i',$tmpfile))				   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
	if (preg_match('/\.xla(m)?$/i',$tmpfile))				   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
	if (preg_match('/\.xls$/i',$tmpfile))			           { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
	if (preg_match('/\.xls(b|m|x)$/i',$tmpfile))			   { $mime='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; $imgmime='xls.png'; }
	if (preg_match('/\.pps(m|x)?$/i',$tmpfile))				   { $mime='application/vnd.ms-powerpoint'; $imgmime='ppt.png'; }
	if (preg_match('/\.ppt(m|x)?$/i',$tmpfile))				   { $mime='application/x-mspowerpoint'; $imgmime='ppt.png'; }
	// Other
	if (preg_match('/\.pdf$/i',$tmpfile))                      { $mime='application/pdf'; $imgmime='pdf.png'; }
	// Scripts
	if (preg_match('/\.bat$/i',$tmpfile))                      { $mime='text/x-bat'; $imgmime='script.png'; $srclang='dos'; }
	if (preg_match('/\.sh$/i',$tmpfile))                       { $mime='text/x-sh'; $imgmime='script.png'; $srclang='bash'; }
	if (preg_match('/\.ksh$/i',$tmpfile))                      { $mime='text/x-ksh'; $imgmime='script.png'; $srclang='bash'; }
	if (preg_match('/\.bash$/i',$tmpfile))                     { $mime='text/x-bash'; $imgmime='script.png'; $srclang='bash'; }
	// Images
	if (preg_match('/\.ico$/i',$tmpfile))                      { $mime='image/x-icon'; $imgmime='image.png'; }
	if (preg_match('/\.(jpg|jpeg)$/i',$tmpfile))			   { $mime='image/jpeg'; $imgmime='image.png'; }
	if (preg_match('/\.png$/i',$tmpfile))					   { $mime='image/png'; $imgmime='image.png'; }
	if (preg_match('/\.gif$/i',$tmpfile))					   { $mime='image/gif'; $imgmime='image.png'; }
	if (preg_match('/\.bmp$/i',$tmpfile))					   { $mime='image/bmp'; $imgmime='image.png'; }
	if (preg_match('/\.(tif|tiff)$/i',$tmpfile))			   { $mime='image/tiff'; $imgmime='image.png'; }
	// Calendar
	if (preg_match('/\.vcs$/i',$tmpfile))					   { $mime='text/calendar'; $imgmime='other.png'; }
	if (preg_match('/\.ics$/i',$tmpfile))					   { $mime='text/calendar'; $imgmime='other.png'; }
	// Other
	if (preg_match('/\.torrent$/i',$tmpfile))				   { $mime='application/x-bittorrent'; $imgmime='other.png'; }
	// Audio
	if (preg_match('/\.(mp3|ogg|au|wav|wma|mid)$/i',$tmpfile)) { $mime='audio'; $imgmime='audio.png'; }
	// Video
    if (preg_match('/\.ogv$/i',$tmpfile))                      { $mime='video/ogg'; $imgmime='video.png'; }
    if (preg_match('/\.webm$/i',$tmpfile))                     { $mime='video/webm'; $imgmime='video.png'; }
    if (preg_match('/\.avi$/i',$tmpfile))                      { $mime='video/x-msvideo'; $imgmime='video.png'; }
    if (preg_match('/\.divx$/i',$tmpfile))                     { $mime='video/divx'; $imgmime='video.png'; }
    if (preg_match('/\.xvid$/i',$tmpfile))                     { $mime='video/xvid'; $imgmime='video.png'; }
    if (preg_match('/\.(wmv|mpg|mpeg)$/i',$tmpfile))           { $mime='video'; $imgmime='video.png'; }
	// Archive
	if (preg_match('/\.(zip|rar|gz|tgz|z|cab|bz2|7z|tar|lzh)$/i',$tmpfile))   { $mime='archive'; $imgmime='archive.png'; }    // application/xxx where zzz is zip, ...
	// Exe
	if (preg_match('/\.(exe|com)$/i',$tmpfile))                { $mime='application/octet-stream'; $imgmime='other.png'; }
	// Lib
	if (preg_match('/\.(dll|lib|o|so|a)$/i',$tmpfile))         { $mime='library'; $imgmime='library.png'; }
    // Err
	if (preg_match('/\.err$/i',$tmpfile))                      { $mime='error'; $imgmime='error.png'; }

	// Return string
	if ($mode == 1)
	{
		$tmp=explode('/',$mime);
		return (! empty($tmp[1])?$tmp[1]:$tmp[0]);
	}
	if ($mode == 2)
	{
	    return $imgmime;
	}
    if ($mode == 3)
    {
        return $srclang;
    }

	return $mime;
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
 * Copy a file to another file.
 *
 * @param	string	$srcfile			Source file (can't be a directory)
 * @param	string	$destfile			Destination file (can't be a directory)
 * @param	int		$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param 	int		$overwriteifexists	Overwrite file if exists (1 by default)
 * @return	int							<0 if error, 0 if nothing done (dest file already exists and overwriteifexists=0), >0 if OK
 * @see		dolCopyr
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
 * Copy a dir to another dir.
 *
 * @param	string	$srcfile			Source file (a directory)
 * @param	string	$destfile			Destination file (a directory)
 * @param	int		$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK). Example: '0666'
 * @param 	int		$overwriteifexists	Overwrite file if exists (1 by default)
 * @return	int							<0 if error, 0 if nothing done (dest dir already exists and overwriteifexists=0), >0 if OK
 * @see		dol_copy
 */
function dolCopyDir($srcfile, $destfile, $newmask, $overwriteifexists)
{
	global $conf;

	$result=0;

	dol_syslog("files.lib.php::dolCopyr srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwriteifexists=".$overwriteifexists);

	if (empty($srcfile) || empty($destfile)) return -1;

	$destexists=dol_is_dir($destfile);
	if (! $overwriteifexists && $destexists) return 0;

	$srcfile=dol_osencode($srcfile);
	$destfile=dol_osencode($destfile);

    // recursive function to copy
    // all subdirectories and contents:
	if (is_dir($srcfile))
	{
        $dir_handle=opendir($srcfile);
        while ($file=readdir($dir_handle))
        {
            if ($file!="." && $file!="..")
            {
                if (is_dir($srcfile."/".$file))
                {
                    if (!is_dir($destfile."/".$file))
                    {
                    	umask(0);
						$dirmaskdec=octdec($newmask);
						if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $dirmaskdec=octdec($conf->global->MAIN_UMASK);
						$dirmaskdec |= octdec('0200');  // Set w bit required to be able to create content for recursive subdirs files
                    	dol_mkdir($destfile."/".$file, '', decoct($dirmaskdec));
                    }
                    $result=dolCopyDir($srcfile."/".$file, $destfile."/".$file, $newmask, $overwriteifexists);
                }
                else
				{
                    $result=dol_copy($srcfile."/".$file, $destfile."/".$file, $newmask, $overwriteifexists);
                }
                if ($result < 0) break;
            }
        }
        closedir($dir_handle);
    }
    else
	{
        $result=dol_copy($srcfile, $destfile, $newmask, $overwriteifexists);
    }

    return $result;
}


/**
 * Move a file into another name.
 * This function differs from dol_move_uploaded_file, because it can be called in any context.
 *
 * @param	string  $srcfile            Source file (can't be a directory. use native php @rename() to move a directory)
 * @param   string	$destfile           Destination file (can't be a directory. use native php @rename() to move a directory)
 * @param   integer	$newmask            Mask in octal string for new file (0 by default means $conf->global->MAIN_UMASK)
 * @param   int		$overwriteifexists  Overwrite file if exists (1 by default)
 * @return  boolean 		            True if OK, false if KO
 */
function dol_move($srcfile, $destfile, $newmask=0, $overwriteifexists=1)
{
    global $conf;
    $result=false;

    dol_syslog("files.lib.php::dol_move srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwritifexists=".$overwriteifexists);
    $destexists=dol_is_file($destfile);
    if ($overwriteifexists || ! $destexists)
    {
        $newpathofsrcfile=dol_osencode($srcfile);
        $newpathofdestfile=dol_osencode($destfile);

        $result=@rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
        if (! $result)
        {
        	if ($destexists)
        	{
        		dol_syslog("files.lib.php::dol_move failed. We try to delete first and move after.", LOG_WARNING);
        		// We force delete and try again. Rename function sometimes fails to replace dest file with some windows NTFS partitions.
        		dol_delete_file($destfile);
        		$result=@rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
        	}
        	else dol_syslog("files.lib.php::dol_move failed", LOG_WARNING);
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
 *	Make control on an uploaded file from an GUI page and move it to final destination.
 * 	If there is errors (virus found, antivir in error, bad filename), file is not moved.
 *  Note: This function can be used only into a HTML page context. Use dol_move if you are outside.
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
		if (empty($disablevirusscan) && file_exists($src_file) && ! empty($conf->global->MAIN_ANTIVIRUS_COMMAND))
		{
			if (! class_exists('AntiVir')) {
				require DOL_DOCUMENT_ROOT.'/core/class/antivir.class.php';
			}
			$antivir=new AntiVir($db);
			$result = $antivir->dol_avscan_file($src_file);
			if ($result < 0)	// If virus or error, we stop here
			{
				$reterrors=$antivir->errors;
				dol_syslog('Files.lib::dol_move_uploaded_file File "'.$src_file.'" (target name "'.$dest_file.'") KO with antivirus: result='.$result.' errors='.join(',',$antivir->errors), LOG_WARNING);
				return 'ErrorFileIsInfectedWithAVirus: '.join(',',$reterrors);
			}
		}

		// Security:
		// Disallow file with some extensions. We renamed them.
		// Car si on a mis le rep documents dans un rep de la racine web (pas bien), cela permet d'executer du code a la demande.
		if (preg_match('/\.htm|\.html|\.php|\.pl|\.cgi$/i',$dest_file))
		{
			$file_name.= '.noexe';
		}

		// Security:
		// On interdit fichiers caches, remontees de repertoire ainsi que les pipes dans les noms de fichiers.
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

		$reshook=$hookmanager->initHooks(array('fileslib'));

		$parameters=array('dest_file' => $dest_file, 'src_file' => $src_file, 'file_name' => $file_name, 'varfiles' => $varfiles, 'allowoverwrite' => $allowoverwrite);
		$reshook=$hookmanager->executeHooks('moveUploadedFile', $parameters, $object);
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
		// TODO

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
 *  Remove a file or several files with a mask
 *
 *  @param	string	$file           File to delete or mask of files to delete
 *  @param  int		$disableglob    Disable usage of glob like * so function is an exact delete function that will return error if no file found
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @param	int		$nohook			Disable all hooks
 *  @param	object	$object			Current object in use
 *  @return boolean         		True if no error (file is deleted or if glob is used and there's nothing to delete), False if error
 */
function dol_delete_file($file,$disableglob=0,$nophperrors=0,$nohook=0,$object=null)
{
	global $db, $conf, $user, $langs;
	global $hookmanager;

	$langs->load("other");
	$langs->load("errors");

	dol_syslog("dol_delete_file file=".$file." disableglob=".$disableglob." nophperrors=".$nophperrors." nohook=".$nohook);

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
					if ($ok) dol_syslog("Removed file ".$filename, LOG_DEBUG);
					else dol_syslog("Failed to remove file ".$filename, LOG_WARNING);
				}
			}
			else dol_syslog("No files to delete found", LOG_WARNING);
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
 */
function dol_delete_dir($dir,$nophperrors=0)
{
    $dir_osencoded=dol_osencode($dir);
    return ($nophperrors?@rmdir($dir_osencoded):rmdir($dir_osencoded));
}

/**
 *  Remove a directory $dir and its subdirectories (or only files and subdirectories)
 *
 *  @param	string	$dir            Dir to delete
 *  @param  int		$count          Counter to count nb of deleted elements
 *  @param  int		$nophperrors    Disable all PHP output errors
 *  @param	int		$onlysub		Delete only files and subdir, not main directory
 *  @return int             		Number of files and directory removed
 */
function dol_delete_dir_recursive($dir,$count=0,$nophperrors=0,$onlysub=0)
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
                    if (is_dir(dol_osencode("$dir/$item")))
                    {
                        $count=dol_delete_dir_recursive("$dir/$item",$count,$nophperrors);
                    }
                    else
                    {
                        dol_delete_file("$dir/$item",1,$nophperrors);
                        $count++;
                        //echo " removing $dir/$item<br>\n";
                    }
                }
            }
            closedir($handle);

            if (empty($onlysub))
            {
	            dol_delete_dir($dir,$nophperrors);
    	        $count++;
        	    //echo "removing $dir<br>\n";
            }
        }
    }

    //echo "return=".$count;
    return $count;
}


/**
 *  Delete all preview files linked to object instance
 *
 *  @param	object	$object		Object to clean
 *  @return	int					0 if error, 1 if OK
 */
function dol_delete_preview($object)
{
	global $langs,$conf;

	// Define parent dir of elements
	$element = $object->element;

    if ($object->element == 'order_supplier')		$dir = $conf->fournisseur->dir_output.'/commande';
    elseif ($object->element == 'invoice_supplier')	$dir = $conf->fournisseur->dir_output.'/facture';
    elseif ($object->element == 'project')			$dir = $conf->projet->dir_output;
    elseif ($object->element == 'shipping')			$dir = $conf->expedition->dir_output.'/sending';
    elseif ($object->element == 'delivery')			$dir = $conf->expedition->dir_output.'/receipt';
    elseif ($object->element == 'fichinter')		$dir = $conf->ficheinter->dir_output;
    else $dir=empty($conf->$element->dir_output)?'':$conf->$element->dir_output;

    if (empty($dir)) return 'ErrorObjectNoSupportedByFunction';

	$refsan = dol_sanitizeFileName($object->ref);
	$dir = $dir . "/" . $refsan ;
	$file = $dir . "/" . $refsan . ".pdf.png";
	$multiple = $file . ".";

	if (file_exists($file) && is_writable($file))
	{
		if (! dol_delete_file($file,1))
		{
			$object->error=$langs->trans("ErrorFailedToDeleteFile",$file);
			return 0;
		}
	}
	else
	{
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
 *	This should allow "grep" search.
 *  This feature is enabled only if option MAIN_DOC_CREATE_METAFILE is set.
 *
 *	@param	CommonObject	$object		Object
 *	@return	int					0 if we did nothing, >0 success, <0 error
 */
function dol_meta_create($object)
{
	global $conf;

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

		$facref = dol_sanitizeFileName($object->ref);
		$dir = $dir . "/" . $facref;
		$file = $dir . "/" . $facref . ".meta";

		if (! is_dir($dir))
		{
			dol_mkdir($dir);
		}

		if (is_dir($dir))
		{
			$nblignes = count($object->lines);
			$client = $object->client->name . " " . $object->client->address . " " . $object->client->zip . " " . $object->client->town;
			$meta = "REFERENCE=\"" . $object->ref . "\"
			DATE=\"" . dol_print_date($object->date,'') . "\"
			NB_ITEMS=\"" . $nblignes . "\"
			CLIENT=\"" . $client . "\"
			TOTAL_HT=\"" . $object->total_ht . "\"
			TOTAL_TTC=\"" . $object->total_ttc . "\"\n";

			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				//Pour les articles
				$meta .= "ITEM_" . $i . "_QUANTITY=\"" . $object->lines[$i]->qty . "\"
				ITEM_" . $i . "_TOTAL_HT=\"" . $object->lines[$i]->total_ht . "\"
				ITEM_" . $i . "_TVA=\"" .$object->lines[$i]->tva_tx . "\"
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

	return 0;
}



/**
 * Init $_SESSION with uploaded files
 *
 * @param	string	$pathtoscan				Path to scan
 * @return	void
 */
function dol_init_file_process($pathtoscan='')
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
	$_SESSION["listofpaths"]=join(';',$listofpaths);
	$_SESSION["listofnames"]=join(';',$listofnames);
	$_SESSION["listofmimes"]=join(';',$listofmimes);
}


/**
 * Get and save an upload file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 * Note: This function can be used only into a HTML page context.
 *
 * @param	string	$upload_dir				Directory where to store uploaded file (note: also find in first part of dest_file)
 * @param	int		$allowoverwrite			1=Allow overwrite existing file
 * @param	int		$donotupdatesession		1=Do no edit _SESSION variable
 * @param	string	$varfiles				_FILES var name
 * @param	string	$savingdocmask			Mask to use to define output filename. For example 'XXXXX-__YYYYMMDD__-__file__'
 * @param	string	$link					Link to add
 * @return	void
 */
function dol_add_file_process($upload_dir, $allowoverwrite=0, $donotupdatesession=0, $varfiles='addedfile', $savingdocmask='', $link=null)
{
	global $db,$user,$conf,$langs;

	if (! empty($_FILES[$varfiles])) // For view $_FILES[$varfiles]['error']
	{
		dol_syslog('dol_add_file_process upload_dir='.$upload_dir.' allowoverwrite='.$allowoverwrite.' donotupdatesession='.$donotupdatesession.' savingdocmask='.$savingdocmask, LOG_DEBUG);
		if (dol_mkdir($upload_dir) >= 0)
		{
			// Define $destpath (path to file including filename) and $destfile (only filename)
			$destpath=$upload_dir . "/" . $_FILES[$varfiles]['name'];
			$destfile=$_FILES[$varfiles]['name'];

			$savingdocmask = dol_sanitizeFileName($savingdocmask);

			if ($savingdocmask)
			{
				$destpath=$upload_dir . "/" . preg_replace('/__file__/',$_FILES[$varfiles]['name'],$savingdocmask);
				$destfile=preg_replace('/__file__/',$_FILES[$varfiles]['name'],$savingdocmask);
			}

			$resupload = dol_move_uploaded_file($_FILES[$varfiles]['tmp_name'], $destpath, $allowoverwrite, 0, $_FILES[$varfiles]['error'], 0, $varfiles);
			if (is_numeric($resupload) && $resupload > 0)
			{
				include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
				global $maxwidthsmall, $maxheightsmall, $maxwidthmini, $maxheightmini;
				
				if (empty($donotupdatesession))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
					$formmail = new FormMail($db);
					$formmail->add_attached_files($destpath, $destfile, $_FILES[$varfiles]['type']);
				}
				if (image_format_supported($destpath) == 1)
				{
					// Create small thumbs for image (Ratio is near 16/9)
					// Used on logon for example
					$imgThumbSmall = vignette($destpath, $maxwidthsmall, $maxheigthsmall, '_small', 50, "thumbs");
					// Create mini thumbs for image (Ratio is near 16/9)
					// Used on menu or for setup page for example
					$imgThumbMini = vignette($destpath, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
				}

				setEventMessages($langs->trans("FileTransferComplete"), null, 'mesgs');
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
	} elseif ($link) {
		if (dol_mkdir($upload_dir) >= 0) {
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
	}
	else
	{
		$langs->load("errors");
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentities("File")), null, 'errors');
	}
}


/**
 * Remove an uploaded file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 *
 * @param	int		$filenb					File nb to delete
 * @param	int		$donotupdatesession		1=Do not edit _SESSION variable
 * @param   int		$donotdeletefile        1=Do not delete physically file
 * @return	void
 */
function dol_remove_file_process($filenb,$donotupdatesession=0,$donotdeletefile=1)
{
	global $db,$user,$conf,$langs,$_FILES;

	$keytodelete=$filenb;
	$keytodelete--;

	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
	if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
	if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
	if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);

	if ($keytodelete >= 0)
	{
		$pathtodelete=$listofpaths[$keytodelete];
		$filetodelete=$listofnames[$keytodelete];
		if (empty($donotdeletefile)) $result = dol_delete_file($pathtodelete,1);
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
				$formmail->remove_attached_files($keytodelete);
			}
		}
	}
}

/**
 * 	Convert an image file into anoher format.
 *  This need Imagick php extension.
 *
 *  @param	string	$fileinput  Input file name
 *  @param  string	$ext        Format of target file (It is also extension added to file if fileoutput is not provided).
 *  @param	string	$fileoutput	Output filename
 *  @return	int					<0 if KO, >0 if OK
 */
function dol_convert_file($fileinput,$ext='png',$fileoutput='')
{
	global $langs;

	$image=new Imagick();
	$ret = $image->readImage($fileinput);
	if ($ret)
	{
		$ret = $image->setImageFormat($ext);
		if ($ret)
		{
			if (empty($fileoutput)) $fileoutput=$fileinput.".".$ext;

			$count = $image->getNumberImages();
			$ret = $image->writeImages($fileoutput, true);
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
    global $conf, $langs;

    if (defined('ODTPHP_PATHTOPCLZIP'))
    {
    	dol_syslog("Constant ODTPHP_PATHTOPCLZIP for pclzip library is set to ".constant('ODTPHP_PATHTOPCLZIP').", so we use Pclzip to unzip into ".$outputdir);
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
 * Return file(s) into a directory (by default most recent)
 *
 * @param 	string		$dir			Directory to scan
 * @param	string		$regexfilter	Regex filter to restrict list. This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	array		$excludefilter  Array of Regex for exclude filter (example: array('(\.meta|_preview\.png)$','^\.')). This regex value must be escaped for '/', since this char is used for preg_match function
 * @param	int			$nohook			Disable all hooks
 * @return	string						Full path to most recent file
 */
function dol_most_recent_file($dir,$regexfilter='',$excludefilter=array('(\.meta|_preview\.png)$','^\.'),$nohook=false)
{
    $tmparray=dol_dir_list($dir,'files',0,$regexfilter,$excludefilter,'date',SORT_DESC,'',$nohook);
    return $tmparray[0];
}

/**
 * Security check when accessing to a document (used by document.php, viewimage.php and webservices)
 *
 * @param	string	$modulepart			Module of document ('module', 'module_user_temp', 'module_user' or 'module_temp')
 * @param	string	$original_file		Relative path with filename
 * @param	string	$entity				Restrict onto entity
 * @param  	User	$fuser				User object (forced)
 * @param	string	$refname			Ref of object to check permission for external users (autodetect if not provided)
 * @return	mixed						Array with access information : accessallowed & sqlprotectagainstexternals & original_file (as full path name)
 */
function dol_check_secure_access_document($modulepart,$original_file,$entity,$fuser='',$refname='')
{
	global $user, $conf, $db;

	if (! is_object($fuser)) $fuser=$user;

	if (empty($modulepart)) return 'ErrorBadParameter';
	if (empty($entity)) $entity=0;
	dol_syslog('modulepart='.$modulepart.' original_file='.$original_file);
	// We define $accessallowed and $sqlprotectagainstexternals
	$accessallowed=0;
	$sqlprotectagainstexternals='';
	$ret=array();

	// find the subdirectory name as the reference
	if (empty($refname)) $refname=basename(dirname($original_file)."/");

	// Wrapping for some images
	if ($modulepart == 'companylogo')
	{
		$accessallowed=1;
		$original_file=$conf->mycompany->dir_output.'/logos/'.$original_file;
	}
	// Wrapping for users photos
	elseif ($modulepart == 'userphoto')
	{
		$accessallowed=1;
		$original_file=$conf->user->dir_output.'/'.$original_file;
	}
	// Wrapping for members photos
	elseif ($modulepart == 'memberphoto')
	{
		$accessallowed=1;
		$original_file=$conf->adherent->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu factures
	elseif ($modulepart == 'apercufacture')
	{
		if ($fuser->rights->facture->lire) $accessallowed=1;
		$original_file=$conf->facture->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu propal
	elseif ($modulepart == 'apercupropal')
	{
		if ($fuser->rights->propale->lire) $accessallowed=1;
		$original_file=$conf->propal->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu commande
	elseif ($modulepart == 'apercucommande')
	{
		if ($fuser->rights->commande->lire) $accessallowed=1;
		$original_file=$conf->commande->dir_output.'/'.$original_file;
	}
	// Wrapping pour les apercu intervention
	elseif ($modulepart == 'apercufichinter')
	{
		if ($fuser->rights->ficheinter->lire) $accessallowed=1;
		$original_file=$conf->ficheinter->dir_output.'/'.$original_file;
	}
	// Wrapping pour les images des stats propales
	elseif ($modulepart == 'propalstats')
	{
		if ($fuser->rights->propale->lire) $accessallowed=1;
		$original_file=$conf->propal->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats commandes
	elseif ($modulepart == 'orderstats')
	{
		if ($fuser->rights->commande->lire) $accessallowed=1;
		$original_file=$conf->commande->dir_temp.'/'.$original_file;
	}
	elseif ($modulepart == 'orderstatssupplier')
	{
		if ($fuser->rights->fournisseur->commande->lire) $accessallowed=1;
		$original_file=$conf->fournisseur->dir_output.'/commande/temp/'.$original_file;
	}
	// Wrapping pour les images des stats factures
	elseif ($modulepart == 'billstats')
	{
		if ($fuser->rights->facture->lire) $accessallowed=1;
		$original_file=$conf->facture->dir_temp.'/'.$original_file;
	}
	elseif ($modulepart == 'billstatssupplier')
	{
		if ($fuser->rights->fournisseur->facture->lire) $accessallowed=1;
		$original_file=$conf->fournisseur->dir_output.'/facture/temp/'.$original_file;
	}
	// Wrapping pour les images des stats expeditions
	elseif ($modulepart == 'expeditionstats')
	{
		if ($fuser->rights->expedition->lire) $accessallowed=1;
		$original_file=$conf->expedition->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats expeditions
	elseif ($modulepart == 'tripsexpensesstats')
	{
		if ($fuser->rights->deplacement->lire) $accessallowed=1;
		$original_file=$conf->deplacement->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats expeditions
	elseif ($modulepart == 'memberstats')
	{
		if ($fuser->rights->adherent->lire) $accessallowed=1;
		$original_file=$conf->adherent->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les images des stats produits
	elseif (preg_match('/^productstats_/i',$modulepart))
	{
		if ($fuser->rights->produit->lire || $fuser->rights->service->lire) $accessallowed=1;
		$original_file=(!empty($conf->product->multidir_temp[$entity])?$conf->product->multidir_temp[$entity]:$conf->service->multidir_temp[$entity]).'/'.$original_file;
	}
	// Wrapping for products or services
	elseif ($modulepart == 'tax')
	{
		if ($fuser->rights->tax->charges->lire) $accessallowed=1;
		$original_file=$conf->tax->dir_output.'/'.$original_file;
	}
	// Wrapping for products or services
	elseif ($modulepart == 'actions')
	{
		if ($fuser->rights->agenda->myactions->read) $accessallowed=1;
		$original_file=$conf->agenda->dir_output.'/'.$original_file;
	}
	// Wrapping for categories
	elseif ($modulepart == 'category')
	{
		if ($fuser->rights->categorie->lire) $accessallowed=1;
		$original_file=$conf->categorie->multidir_output[$entity].'/'.$original_file;
	}
	// Wrapping pour les prelevements
	elseif ($modulepart == 'prelevement')
	{
		if ($fuser->rights->prelevement->bons->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->prelevement->dir_output.'/'.$original_file;
	}
	// Wrapping pour les graph energie
	elseif ($modulepart == 'graph_stock')
	{
		$accessallowed=1;
		$original_file=$conf->stock->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les graph fournisseurs
	elseif ($modulepart == 'graph_fourn')
	{
		$accessallowed=1;
		$original_file=$conf->fournisseur->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les graph des produits
	elseif ($modulepart == 'graph_product')
	{
		$accessallowed=1;
		$original_file=$conf->product->multidir_temp[$entity].'/'.$original_file;
	}
	// Wrapping pour les code barre
	elseif ($modulepart == 'barcode')
	{
		$accessallowed=1;
		// If viewimage is called for barcode, we try to output an image on the fly,
		// with not build of file on disk.
		//$original_file=$conf->barcode->dir_temp.'/'.$original_file;
		$original_file='';
	}
	// Wrapping pour les icones de background des mailings
	elseif ($modulepart == 'iconmailing')
	{
		$accessallowed=1;
		$original_file=$conf->mailing->dir_temp.'/'.$original_file;
	}
	// Wrapping pour les icones de background des mailings
	elseif ($modulepart == 'scanner_user_temp')
	{
		$accessallowed=1;
		$original_file=$conf->scanner->dir_temp.'/'.$fuser->id.'/'.$original_file;
	}
	// Wrapping pour les images fckeditor
	elseif ($modulepart == 'fckeditor')
	{
		$accessallowed=1;
		$original_file=$conf->fckeditor->dir_output.'/'.$original_file;
	}

	// Wrapping for third parties
	else if ($modulepart == 'company' || $modulepart == 'societe')
	{
		if ($fuser->rights->societe->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->societe->multidir_output[$entity].'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT rowid as fk_soc FROM ".MAIN_DB_PREFIX."societe WHERE rowid='".$db->escape($refname)."' AND entity IN (".getEntity('societe', 1).")";
	}

	// Wrapping for contact
	else if ($modulepart == 'contact')
	{
		if ($fuser->rights->societe->lire)
		{
			$accessallowed=1;
		}
		$original_file=$conf->societe->multidir_output[$entity].'/contact/'.$original_file;
	}

	// Wrapping for invoices
	else if ($modulepart == 'facture' || $modulepart == 'invoice')
	{
		if ($fuser->rights->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	else if ($modulepart == 'unpaid')
	{
		if ($fuser->rights->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->facture->dir_output.'/unpaid/temp/'.$original_file;
	}

	// Wrapping pour les fiches intervention
	else if ($modulepart == 'ficheinter')
	{
		if ($fuser->rights->ficheinter->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->ficheinter->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les deplacements et notes de frais
	else if ($modulepart == 'deplacement')
	{
		if ($fuser->rights->deplacement->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->deplacement->dir_output.'/'.$original_file;
		//$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}
	// Wrapping pour les propales
	else if ($modulepart == 'propal')
	{
		if ($fuser->rights->propale->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}

		$original_file=$conf->propal->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."propal WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les commandes
	else if ($modulepart == 'commande' || $modulepart == 'order')
	{
		if ($fuser->rights->commande->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les projets
	else if ($modulepart == 'project')
	{
		if ($fuser->rights->projet->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->projet->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}
	else if ($modulepart == 'project_task')
	{
		if ($fuser->rights->projet->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->projet->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."projet WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}
	// Wrapping for interventions
	else if ($modulepart == 'fichinter')
	{
		if ($fuser->rights->ficheinter->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->ficheinter->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."fichinter WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les commandes fournisseurs
	else if ($modulepart == 'commande_fournisseur' || $modulepart == 'order_supplier')
	{
		if ($fuser->rights->fournisseur->commande->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->commande->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."commande_fournisseur WHERE ref='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les factures fournisseurs
	else if ($modulepart == 'facture_fournisseur' || $modulepart == 'invoice_supplier')
	{
		if ($fuser->rights->fournisseur->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->fournisseur->facture->dir_output.'/'.$original_file;
		$sqlprotectagainstexternals = "SELECT fk_soc as fk_soc FROM ".MAIN_DB_PREFIX."facture_fourn WHERE facnumber='".$db->escape($refname)."' AND entity=".$conf->entity;
	}

	// Wrapping pour les rapport de paiements
	else if ($modulepart == 'facture_paiement')
	{
		if ($fuser->rights->facture->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if ($fuser->societe_id > 0) $original_file=$conf->facture->dir_output.'/payments/private/'.$fuser->id.'/'.$original_file;
		else $original_file=$conf->facture->dir_output.'/payments/'.$original_file;
	}

	// Wrapping for accounting exports
	else if ($modulepart == 'export_compta')
	{
		if ($fuser->rights->accounting->ventilation->dispatch || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->accounting->dir_output.'/'.$original_file;
	}

	// Wrapping pour les expedition
	else if ($modulepart == 'expedition')
	{
		if ($fuser->rights->expedition->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expedition->dir_output."/sending/".$original_file;
	}

	// Wrapping pour les bons de livraison
	else if ($modulepart == 'livraison')
	{
		if ($fuser->rights->expedition->livraison->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->expedition->dir_output."/receipt/".$original_file;
	}

	// Wrapping pour les actions
	else if ($modulepart == 'actions')
	{
		if ($fuser->rights->agenda->myactions->read || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->agenda->dir_output.'/'.$original_file;
	}

	// Wrapping pour les actions
	else if ($modulepart == 'actionsreport')
	{
		if ($fuser->rights->agenda->allactions->read || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file = $conf->agenda->dir_temp."/".$original_file;
	}

	// Wrapping pour les produits et services
	else if ($modulepart == 'product' || $modulepart == 'produit' || $modulepart == 'service')
	{
		if (($fuser->rights->produit->lire || $fuser->rights->service->lire) || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		if (! empty($conf->product->enabled)) $original_file=$conf->product->multidir_output[$entity].'/'.$original_file;
		elseif (! empty($conf->service->enabled)) $original_file=$conf->service->multidir_output[$entity].'/'.$original_file;
	}

	// Wrapping pour les contrats
	else if ($modulepart == 'contract')
	{
		if ($fuser->rights->contrat->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->contrat->dir_output.'/'.$original_file;
	}

	// Wrapping pour les dons
	else if ($modulepart == 'donation')
	{
		if ($fuser->rights->don->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->don->dir_output.'/'.$original_file;
	}

	// Wrapping pour les remises de cheques
	else if ($modulepart == 'remisecheque')
	{
		if ($fuser->rights->banque->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}

		$original_file=$conf->banque->dir_output.'/bordereau/'.$original_file;		// original_file should contains relative path so include the get_exdir result
	}

	// Wrapping for bank
	else if ($modulepart == 'bank')
	{
		if ($fuser->rights->banque->lire)
		{
			$accessallowed=1;
		}
		$original_file=$conf->bank->dir_output.'/'.$original_file;
	}

	// Wrapping for export module
	else if ($modulepart == 'export')
	{
		// Aucun test necessaire car on force le rep de download sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->export->dir_temp.'/'.$fuser->id.'/'.$original_file;
	}

	// Wrapping for import module
	else if ($modulepart == 'import')
	{
		// Aucun test necessaire car on force le rep de download sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->import->dir_temp.'/'.$original_file;
	}

	// Wrapping pour l'editeur wysiwyg
	else if ($modulepart == 'editor')
	{
		// Aucun test necessaire car on force le rep de download sur
		// le rep export qui est propre a l'utilisateur
		$accessallowed=1;
		$original_file=$conf->fckeditor->dir_output.'/'.$original_file;
	}

	// Wrapping pour les backups
	else if ($modulepart == 'systemtools')
	{
		if ($fuser->admin)
		{
			$accessallowed=1;
		}
		$original_file=$conf->admin->dir_output.'/'.$original_file;
	}

	// Wrapping for upload file test
	else if ($modulepart == 'admin_temp')
	{
		if ($fuser->admin)
			$accessallowed=1;
		$original_file=$conf->admin->dir_temp.'/'.$original_file;
	}

	// Wrapping pour BitTorrent
	else if ($modulepart == 'bittorrent')
	{
		$accessallowed=1;
		$dir='files';
		if (dol_mimetype($original_file) == 'application/x-bittorrent') $dir='torrents';
		$original_file=$conf->bittorrent->dir_output.'/'.$dir.'/'.$original_file;
	}

	// Wrapping pour Foundation module
	else if ($modulepart == 'member')
	{
		if ($fuser->rights->adherent->lire || preg_match('/^specimen/i',$original_file))
		{
			$accessallowed=1;
		}
		$original_file=$conf->adherent->dir_output.'/'.$original_file;
	}

	// Wrapping for Scanner
	else if ($modulepart == 'scanner_user_temp')
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
		// Define $accessallowed
		if (preg_match('/^([a-z]+)_user_temp$/i',$modulepart,$reg))
		{
			if ($fuser->rights->{$reg[1]}->lire || $fuser->rights->{$reg[1]}->read || ($fuser->rights->{$reg[1]}->download)) $accessallowed=1;
			$original_file=$conf->{$reg[1]}->dir_temp.'/'.$fuser->id.'/'.$original_file;
		}
		else if (preg_match('/^([a-z]+)_temp$/i',$modulepart,$reg))
		{
			if ($fuser->rights->{$reg[1]}->lire || $fuser->rights->{$reg[1]}->read || ($fuser->rights->{$reg[1]}->download)) $accessallowed=1;
			$original_file=$conf->{$reg[1]}->dir_temp.'/'.$original_file;
		}
		else if (preg_match('/^([a-z]+)_user$/i',$modulepart,$reg))
		{
			if ($fuser->rights->{$reg[1]}->lire || $fuser->rights->{$reg[1]}->read || ($fuser->rights->{$reg[1]}->download)) $accessallowed=1;
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
				if ($fuser->rights->$modulepart->lire || $fuser->rights->$modulepart->read) $accessallowed=1;
				$original_file=$conf->$modulepart->dir_output.'/'.$original_file;
			}
		}
		if (preg_match('/^specimen/i',$original_file))	$accessallowed=1;    // If link to a specimen
		if ($fuser->admin) $accessallowed=1;    // If user is admin

		// For modules who wants to manage different levels of permissions for documents
		$subPermCategoryConstName = strtoupper($modulepart).'_SUBPERMCATEGORY_FOR_DOCUMENTS';
		if (! empty($conf->global->$subPermCategoryConstName))
		{
			$subPermCategory = $conf->global->$subPermCategoryConstName;
			if (! empty($subPermCategory) && (($fuser->rights->$modulepart->$subPermCategory->lire) || ($fuser->rights->$modulepart->$subPermCategory->read) || ($fuser->rights->$modulepart->$subPermCategory->download)))
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
 * Store object in file
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
 * Test if Refresh needed
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
 * Read object from cachefile
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
