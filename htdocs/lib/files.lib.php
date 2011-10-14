<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/lib/files.lib.php
 *  \brief		Library for file managing functions
 */

/**
 *  Return user/group account of web server
 *  @param      $mode       'user' or 'group'
 *  @return     string
 */
function dol_getwebuser($mode)
{
    $t='?';
    if ($mode=='user')  $t=getenv('APACHE_RUN_USER');   // $_ENV['APACHE_RUN_USER'] is empty
    if ($mode=='group') $t=getenv('APACHE_RUN_GROUP');
    return $t;
}

/**
 *  Scan a directory and return a list of files/directories.
 *  Content for string is UTF8 and dir separator is "/".
 *  @param		$path        	Starting path from which to search
 *  @param		$types        	Can be "directories", "files", or "all"
 *  @param		$recursive		Determines whether subdirectories are searched
 *  @param		$filter        	Regex for include filter
 *  @param		$excludefilter  Array of Regex for exclude filter (example: array('\.meta$','^\.')
 *  @param		$sortcriteria	Sort criteria ("","name","date","size")
 *  @param		$sortorder		Sort order (SORT_ASC, SORT_DESC)
 *	@param		$mode			0=Return array minimum keys loaded (faster), 1=Force all keys like date and size to be loaded (slower), 2=Force load of date only, 3=Force load of size only
 *  @return		array			Array of array('name'=>'xxx','fullname'=>'/abc/xxx','date'=>'yyy','size'=>99,'type'=>'dir|file')
 */
function dol_dir_list($path, $types="all", $recursive=0, $filter="", $excludefilter="", $sortcriteria="name", $sortorder=SORT_ASC, $mode=0)
{
	dol_syslog("files.lib.php::dol_dir_list path=".$path." types=".$types." recursive=".$recursive." filter=".$filter." excludefilter=".$excludefilter);

	$loaddate=($mode==1||$mode==2)?true:false;
	$loadsize=($mode==1||$mode==3)?true:false;

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
		         if (preg_match('/'.$filt.'/i',$file)) { $qualified=0; break; }
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
                    }

					// if we're in a directory and we want recursive behavior, call this function again
					if ($recursive)
					{
						$file_list = array_merge($file_list,dol_dir_list($path."/".$file, $types, $recursive, $filter, $excludefilter, $sortcriteria, $sortorder, $mode));
					}
				}
				else if (! $isdir && (($types == "files") || ($types == "all")))
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
		if ($sortorder) array_multisort($myarray, $sortorder, $file_list);

		return $file_list;
	}
	else
	{
		return array();
	}
}

/**
 * Fast compare of 2 files identified by their properties ->name, ->date and ->size
 * @param 	$a		File 1
 * @param 	$b		File 2
 * @return 	int		1, 0, 1
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
 *	@param      file		Filename we looking for MIME type
 *  @param      default     Default mime type if extension not found in known list
 * 	@param		mode    	0=Return full mime, 1=otherwise short mime string, 2=image for mime type, 3=source language
 *	@return     string     	Return a mime type family
 *                          (text/xxx, application/xxx, image/xxx, audio, video, archive)
 *  @see        image_format_supported (images.lib.php)
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
	if (preg_match('/\.xls(b|m|x)?$/i',$tmpfile))			   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
	if (preg_match('/\.xlt(x)?$/i',$tmpfile))				   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
	if (preg_match('/\.xla(m)?$/i',$tmpfile))				   { $mime='application/vnd.ms-excel'; $imgmime='xls.png'; }
	if (preg_match('/\.xsl(b|m|x)?$/i',$tmpfile))			   { $mime='application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'; $imgmime='xls.png'; }
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
		return $tmp[1];
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
 *  Test if filename is a directory
 *
 *  @param      folder      Name of folder
 *  @return     boolean     True if it's a directory, False if not found
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
 * @param   $pathoffile
 * @return  boolean         True or false
 */
function dol_is_file($pathoffile)
{
    $newpathoffile=dol_osencode($pathoffile);
    return is_file($newpathoffile);
}

/**
 * Return if path is an URL
 *
 * @param   $url
 * @return  boolean         True or false
 */
function dol_is_url($url)
{
    $tmpprot=array('file','http','ftp','zlib','data','ssh2','ogg','expect');
    foreach($tmpprot as $prot)
    {
        if (preg_match('/^'.$prot.':/i',$url)) return true;
    }
    return false;
}


/**
 * 	Test if a folder is empty
 *
 * 	@param		folder		Name of folder
 * 	@return 	boolean		True if dir is empty or non-existing, False if it contains files
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
 * 	Count number of lines in a file
 * 	@param		file		Filename
 * 	@return 	int			<0 if KO, Number of lines in files if OK
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
 * @param 	$pathoffile
 * @return 	timestamp	Time of file
 */
function dol_filemtime($pathoffile)
{
	$newpathoffile=dol_osencode($pathoffile);
	return @filemtime($newpathoffile); // @Is to avoid errors if files does not exists
}

/**
 * Copy a file to another file
 * @param	$srcfile			Source file (can't be a directory)
 * @param	$destfile			Destination file (can't be a directory)
 * @param	$newmask			Mask for new file (0 by default means $conf->global->MAIN_UMASK)
 * @param 	$overwriteifexists	Overwrite file if exists (1 by default)
 * @return	boolean				True if OK, false if KO
 */
function dol_copy($srcfile, $destfile, $newmask=0, $overwriteifexists=1)
{
	global $conf;
	$result=false;

	dol_syslog("files.lib.php::dol_copy srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwritifexists=".$overwriteifexists);
	if ($overwriteifexists || ! dol_is_file($destfile))
	{
        $newpathofsrcfile=dol_osencode($srcfile);
        $newpathofdestfile=dol_osencode($destfile);

        $result=@copy($newpathofsrcfile, $newpathofdestfile);
		//$result=copy($srcfile, $destfile);	// To see errors, remove @
		if (! $result) dol_syslog("files.lib.php::dol_copy failed", LOG_WARNING);
		if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
		@chmod($newpathofdestfile, octdec($newmask));
	}

	return $result;
}

/**
 * Move a file into another name
 * @param   $srcfile            Source file (can't be a directory)
 * @param   $destfile           Destination file (can't be a directory)
 * @param   $newmask            Mask for new file (0 by default means $conf->global->MAIN_UMASK)
 * @param   $overwriteifexists  Overwrite file if exists (1 by default)
 * @return  boolean             True if OK, false if KO
 */
function dol_move($srcfile, $destfile, $newmask=0, $overwriteifexists=1)
{
    global $conf;
    $result=false;

    dol_syslog("files.lib.php::dol_move srcfile=".$srcfile." destfile=".$destfile." newmask=".$newmask." overwritifexists=".$overwriteifexists);
    if ($overwriteifexists || ! dol_is_file($destfile))
    {
        $newpathofsrcfile=dol_osencode($srcfile);
        $newpathofdestfile=dol_osencode($destfile);

        $result=@rename($newpathofsrcfile, $newpathofdestfile); // To see errors, remove @
        if (! $result) dol_syslog("files.lib.php::dol_move failed", LOG_WARNING);
        if (empty($newmask) && ! empty($conf->global->MAIN_UMASK)) $newmask=$conf->global->MAIN_UMASK;
        @chmod($newpathofsrcfile, octdec($newmask));
    }

    return $result;
}

/**
 *	Move an uploaded file after some controls.
 * 	If there is errors (virus found, antivir in error, bad filename), file is not moved.
 *	@param	src_file			Source full path filename ($_FILES['field']['tmp_name'])
 *	@param	dest_file			Target full path filename
 * 	@param	allowoverwrite		1=Overwrite target file if it already exists
 * 	@param	disablevirusscan	1=Disable virus scan
 * 	@param	uploaderrorcode		Value of upload error code ($_FILES['field']['error'])
 * 	@param	notrigger			Disable all triggers
 *	@return int         		>0 if OK, <0 or string if KO
 */
function dol_move_uploaded_file($src_file, $dest_file, $allowoverwrite, $disablevirusscan=0, $uploaderrorcode=0, $notrigger=0)
{
	global $conf, $user, $langs, $db;
	global $object;

	$file_name = $dest_file;
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
		require_once(DOL_DOCUMENT_ROOT.'/lib/antivir.class.php');
		$antivir=new AntiVir($db);
		$result = $antivir->dol_avscan_file($src_file);
		if ($result < 0)	// If virus or error, we stop here
		{
			$reterrors=$antivir->errors;
			dol_syslog('Functions.lib::dol_move_uploaded_file File "'.$src_file.'" (target name "'.$file_name.'") KO with antivirus: result='.$result.' errors='.join(',',$antivir->errors), LOG_WARNING);
			return 'ErrorFileIsInfectedWithAVirus: '.join(',',$reterrors);
		}
	}

	// Security:
	// Disallow file with some extensions. We renamed them.
	// Car si on a mis le rep documents dans un rep de la racine web (pas bien), cela permet d'executer du code a la demande.
	if (preg_match('/\.htm|\.html|\.php|\.pl|\.cgi$/i',$file_name))
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
	// On interdit fichiers caches, remontees de repertoire ainsi que les pipe dans
	// les noms de fichiers.
	if (preg_match('/^\./',$dest_file) || preg_match('/\.\./',$dest_file) || preg_match('/[<>|]/',$dest_file))
	{
		dol_syslog("Refused to deliver file ".$dest_file, LOG_WARNING);
		return -2;
	}

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
			dol_syslog("Functions.lib::dol_move_uploaded_file File ".$file_name." already exists", LOG_WARNING);
			return 'ErrorFileAlreadyExists';
		}
	}

	// Move file
	$return=move_uploaded_file($src_file_osencoded, $file_name_osencoded);
	if ($return)
	{
		if (! empty($conf->global->MAIN_UMASK)) @chmod($file_name_osencoded, octdec($conf->global->MAIN_UMASK));
		dol_syslog("Functions.lib::dol_move_uploaded_file Success to move ".$src_file." to ".$file_name." - Umask=".$conf->global->MAIN_UMASK, LOG_DEBUG);

		if (! $notrigger && is_object($object))
		{
			$object->src_file=$dest_file;

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('FILE_UPLOAD',$object,$user,$langs,$conf);
			if ($result < 0) { $error++; $errors=$interface->errors; }
			// Fin appel triggers
		}

		return 1;	// Success
	}
	else
	{
		dol_syslog("Functions.lib::dol_move_uploaded_file Failed to move ".$src_file." to ".$file_name, LOG_ERR);
		return -3;	// Unknown error
	}

	return 1;
}

/**
 *  Remove a file or several files with a mask
 *
 *  @param      file            File to delete or mask of file to delete
 *  @param      disableglob     Disable usage of glob like *
 *  @param      nophperrors     Disable all PHP output errors
 *  @param		notrigger		Disable all triggers
 *  @param      triggercode     Code of trigger TODO ???? why ?
 *  @param      object          Object for trigger
 *  @return     boolean         True if file is deleted, False if error
 */
function dol_delete_file($file,$disableglob=0,$nophperrors=0,$notrigger=0,$triggercode='FILE_DELETE',$object=null)
{
	global $db, $conf, $user, $langs;

    //print "x".$file." ".$disableglob;
    $ok=true;
    $file_osencoded=dol_osencode($file);    // New filename encoded in OS filesystem encoding charset
    if (empty($disableglob))
    {
        foreach (glob($file_osencoded) as $filename)
        {
            if ($nophperrors) $ok=@unlink($filename);  // The unlink encapsulated by dolibarr
            else $ok=unlink($filename);  // The unlink encapsulated by dolibarr
            if ($ok)
            {
            	dol_syslog("Removed file ".$filename,LOG_DEBUG);
            	if (! $notrigger)
            	{
                    if (! is_object($object)) $object=(object) 'dummy';
            		$object->src_file=$file;

            		// Appel des triggers
            		include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
            		$interface=new Interfaces($db);
            		$result=$interface->run_triggers($triggercode,$object,$user,$langs,$conf);
            		if ($result < 0) { $error++; $errors=$interface->errors; }
            		// Fin appel triggers
            	}
            }
            else dol_syslog("Failed to remove file ".$filename,LOG_WARNING);
        }
    }
    else
    {
        if ($nophperrors) $ok=@unlink($file_osencoded);        // The unlink encapsulated by dolibarr
        else $ok=unlink($file_osencoded);        // The unlink encapsulated by dolibarr
        if ($ok) dol_syslog("Removed file ".$file_osencoded,LOG_DEBUG);
        else dol_syslog("Failed to remove file ".$file_osencoded,LOG_WARNING);
    }
    return $ok;
}

/**
 *  Remove a directory (not recursive, so content must be empty).
 *  If directory is not empty, return false
 *
 *  @param      dir             Directory to delete
 *  @param      nophperrors     Disable all PHP output errors
 *  @return     boolean         True if success, false if error
 */
function dol_delete_dir($dir,$nophperrors=0)
{
    $dir_osencoded=dol_osencode($dir);
    return ($nophperrors?@rmdir($dir_osencoded):rmdir($dir_osencoded));
}

/**
 *  Remove a directory $dir and its subdirectories
 *  @param      dir             Dir to delete
 *  @param      count           Counter to count nb of deleted elements
 *  @param      nophperrors     Disable all PHP output errors
 *  @return     int             Number of files and directory removed
 */
function dol_delete_dir_recursive($dir,$count=0,$nophperrors=0)
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
            dol_delete_dir($dir,$nophperrors);
            $count++;
            //echo "removing $dir<br>\n";
        }
    }

    //echo "return=".$count;
    return $count;
}


/**
 *  Delete all preview files linked to object instance
 *
 *  @param	Object	$object		Object to clean
 *  @return	int					0 if error, 1 if OK
 */
function dol_delete_preview($object)
{
	global $langs,$conf;
    require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");

    if ($object->element == 'commande') $dir = $conf->commande->dir_output;
    if (empty($dir)) return 'ErrorObjectNoSupportedByFunction';

	$refsan = dol_sanitizeFileName($object->ref);
	$dir = $dir . "/" . $refsan ;
	$file = $dir . "/" . $refsan . ".pdf.png";
	$multiple = $file . ".";

	if (file_exists($file) && is_writable($file))
	{
		if ( ! dol_delete_file($file,1) )
		{
			$this->error=$langs->trans("ErrorFailedToOpenFile",$file);
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
					$this->error=$langs->trans("ErrorFailedToOpenFile",$preview);
					return 0;
				}
			}
		}
	}

	return 1;
}


/**
 * Get and save an upload file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 *
 * @param	string	$upload_dir				Directory to store upload files
 * @param	int		$allowoverwrite			1=Allow overwrite existing file
 * @param	int		$donotupdatesession		1=Do no edit _SESSION variable
 * @return	string							Message with result of upload and store.
 */
function dol_add_file_process($upload_dir,$allowoverwrite=0,$donotupdatesession=0)
{
	global $db,$user,$conf,$langs,$_FILES;

	$mesg='';

	if (! empty($_FILES['addedfile']['tmp_name']))
	{
		if (dol_mkdir($upload_dir) >= 0)
		{
			$resupload = dol_move_uploaded_file($_FILES['addedfile']['tmp_name'], $upload_dir . "/" . $_FILES['addedfile']['name'],$allowoverwrite,0, $_FILES['addedfile']['error']);
			if (is_numeric($resupload) && $resupload > 0)
			{
				$mesg = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';

				if (empty($donotupdatesession))
				{
					include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
					$formmail = new FormMail($db);
					$formmail->add_attached_files($upload_dir . "/" . $_FILES['addedfile']['name'],$_FILES['addedfile']['name'],$_FILES['addedfile']['type']);
				}
			}
			else
			{
				$langs->load("errors");
				if ($resupload < 0)	// Unknown error
				{
					$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
				}
				else if (preg_match('/ErrorFileIsInfectedWithAVirus/',$resupload))	// Files infected by a virus
				{
					$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
				}
				else	// Known error
				{
					$mesg = '<div class="error">'.$langs->trans($resupload).'</div>';
				}
			}
		}
	}
	else
	{
		$langs->load("errors");
		$mesg = '<div class="warning">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("File")).'</div>';
	}

	return $mesg;
}


/**
 * Remove an uploaded file (for example after submitting a new file a mail form).
 * All information used are in db, conf, langs, user and _FILES.
 * @param	filenb					File nb to delete
 * @param	donotupdatesession		1=Do not edit _SESSION variable
 * @param   donotdeletefile         1=Do not delete physically file
 * @return	string					Message with result of upload and store.
 */
function dol_remove_file_process($filenb,$donotupdatesession=0,$donotdeletefile=0)
{
	global $db,$user,$conf,$langs,$_FILES;

	$mesg='';

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
			    $mesg = '<div class="ok">'.$langs->trans("FileWasRemoved",$filetodelete).'</div>';
    			//print_r($_FILES);
			}
			if (empty($donotupdatesession))
			{
				include_once(DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->remove_attached_files($keytodelete);
			}
		}
	}

	return $mesg;
}

/**
 * 	Convert an image file into antoher format.
 *  This need Imagick php extension.
 *
 *  @param	string	$file        Input file name
 *  @param  string	$ext         Extension of target file
 */
function dol_convert_file($file,$ext='png')
{
	global $langs;

	$image=new Imagick();
	$ret = $image->readImage($file);
	if ($ret)
	{
		$ret = $image->setImageFormat($ext);
		if ($ret)
		{
			$count = $image->getNumberImages();
			$ret = $image->writeImages($file . "." . $ext, true);
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

	return 1;
}


/**
 * Compress a file
 *
 * @param string	$inputfile		Source file name
 * @param string	$outputfile		Target file name
 * @param string	$mode			'gz' or 'bz'
 */
function dol_compress_file($inputfile, $outputfile, $mode="gz")
{
    try
    {
        $data = implode("", file($inputfile));
        if ($mode == 'gz') $compressdata = gzencode($data, 9);
        elseif ($mode == 'bz') $compressdata = bzcompress($data, 9);

        $fp = fopen($outputfile, "w");
        fwrite($fp, $compressdata);
        fclose($fp);
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

?>
