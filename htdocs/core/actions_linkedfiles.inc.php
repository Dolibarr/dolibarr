<?php
/* Copyright (C)    2013    Cédric Salvador    <csalvador@gpcsolutions.fr>
 * Copyright (C)    2015    Marcos García      <marcosgdf@gmail.com>
 * Copyright (C)    2015    Ferran Marcet      <fmarcet@2byte.es>
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

// Variable $upload_dir must be defined when entering here.
// Variable $upload_dirold may also exists.
// Variable $confirm must be defined.

//var_dump($upload_dir);
//var_dump($upload_dirold);


// Submit file/link
if (GETPOST('sendit','none') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
	if (! empty($_FILES))
	{
		if (is_array($_FILES['userfile']['tmp_name'])) $userfiles=$_FILES['userfile']['tmp_name'];
		else $userfiles=array($_FILES['userfile']['tmp_name']);

		foreach($userfiles as $key => $userfile)
		{
			if (empty($_FILES['userfile']['tmp_name'][$key]))
			{
				$error++;
				if ($_FILES['userfile']['error'][$key] == 1 || $_FILES['userfile']['error'][$key] == 2){
					setEventMessages($langs->trans('ErrorFileSizeTooLarge'), null, 'errors');
				}
				else {
					setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("File")), null, 'errors');
				}
			}
		}

		if (! $error)
		{
			if (! empty($upload_dirold) && ! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
			{
				$result = dol_add_file_process($upload_dirold, 0, 1, 'userfile', GETPOST('savingdocmask', 'alpha'));
			}
			elseif (! empty($upload_dir))
			{
				$result = dol_add_file_process($upload_dir, 0, 1, 'userfile', GETPOST('savingdocmask', 'alpha'));
			}
		}
	}
}
elseif (GETPOST('linkit','none') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    $link = GETPOST('link', 'alpha');
    if ($link)
    {
        if (substr($link, 0, 7) != 'http://' && substr($link, 0, 8) != 'https://' && substr($link, 0, 7) != 'file://') {
            $link = 'http://' . $link;
        }
        dol_add_file_process($upload_dir, 0, 1, 'userfile', null, $link);
    }
}


// Delete file/link
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
        $urlfile = GETPOST('urlfile', 'alpha', 0, null, null, 1);	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
        if (GETPOST('section', 'alpha')) $file = $upload_dir . "/" . $urlfile;	// For a delete of GED module urlfile contains full path from upload_dir
        else															// For documents pages, upload_dir contains already path to file from module dir, so we clean path into urlfile.
		{
       		$urlfile=basename($urlfile);
			$file = $upload_dir . "/" . $urlfile;
			if (! empty($upload_dirold)) $fileold = $upload_dirold . "/" . $urlfile;
		}
        $linkid = GETPOST('linkid', 'int');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

        if ($urlfile)
        {
	        $dir = dirname($file).'/';     // Chemin du dossier contenant l'image d'origine
	        $dirthumb = $dir.'/thumbs/';   // Chemin du dossier contenant la vignette

	        $ret = dol_delete_file($file, 0, 0, 0, (is_object($object)?$object:null));
            if (! empty($fileold)) dol_delete_file($fileold, 0, 0, 0, (is_object($object)?$object:null));     // Delete file using old path

	        // Si elle existe, on efface la vignette
	        if (preg_match('/(\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff)$/i',$file,$regs))
	        {
		        $photo_vignette=basename(preg_replace('/'.$regs[0].'/i','',$file).'_small'.$regs[0]);
		        if (file_exists(dol_osencode($dirthumb.$photo_vignette)))
		        {
			        dol_delete_file($dirthumb.$photo_vignette);
		        }

		        $photo_vignette=basename(preg_replace('/'.$regs[0].'/i','',$file).'_mini'.$regs[0]);
		        if (file_exists(dol_osencode($dirthumb.$photo_vignette)))
		        {
			        dol_delete_file($dirthumb.$photo_vignette);
		        }
	        }

            if ($ret) setEventMessages($langs->trans("FileWasRemoved", $urlfile), null, 'mesgs');
            else setEventMessages($langs->trans("ErrorFailToDeleteFile", $urlfile), null, 'errors');
        }
        elseif ($linkid)
        {
            require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
            $link = new Link($db);
            $link->id = $linkid;
            $link->fetch();
            $res = $link->delete($user);

            $langs->load('link');
            if ($res > 0) {
                setEventMessages($langs->trans("LinkRemoved", $link->label), null, 'mesgs');
            } else {
                if (count($link->errors)) {
                    setEventMessages('', $link->errors, 'errors');
                } else {
                    setEventMessages($langs->trans("ErrorFailedToDeleteLink", $link->label), null, 'errors');
                }
            }
        }

        if (is_object($object) && $object->id > 0)
        {
        	if ($backtopage)
        	{
        		header('Location: ' . $backtopage);
        		exit;
        	}
        	else
        	{
        		header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id.(!empty($withproject)?'&withproject=1':''));
        		exit;
        	}
        }
}
elseif ($action == 'confirm_updateline' && GETPOST('save','alpha') && GETPOST('link', 'alpha'))
{
    require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
    $langs->load('link');
    $link = new Link($db);
    $link->id = GETPOST('linkid', 'int');
    $f = $link->fetch();
    if ($f)
    {
        $link->url = GETPOST('link', 'alpha');
        if (substr($link->url, 0, 7) != 'http://' && substr($link->url, 0, 8) != 'https://' && substr($link->url, 0, 7) != 'file://')
        {
            $link->url = 'http://' . $link->url;
        }
        $link->label = GETPOST('label', 'alpha');
        $res = $link->update($user);
        if (!$res)
        {
            setEventMessages($langs->trans("ErrorFailedToUpdateLink", $link->label), null, 'mesgs');
        }
    }
    else
    {
        //error fetching
    }
}
elseif ($action == 'renamefile' && GETPOST('renamefilesave','alpha'))
{
	// For documents pages, upload_dir contains already path to file from module dir, so we clean path into urlfile.
	if (! empty($upload_dir))
	{
		$filenamefrom=dol_sanitizeFileName(GETPOST('renamefilefrom','alpha'), '_', 0);	// Do not remove accents
		$filenameto=dol_sanitizeFileName(GETPOST('renamefileto','alpha'), '_', 0);		// Do not remove accents

        if ($filenamefrom != $filenameto)
        {
	        // Security:
	        // Disallow file with some extensions. We rename them.
	        // Because if we put the documents directory into a directory inside web root (very bad), this allows to execute on demand arbitrary code.
	        if (preg_match('/\.htm|\.html|\.php|\.pl|\.cgi$/i',$filenameto) && empty($conf->global->MAIN_DOCUMENT_IS_OUTSIDE_WEBROOT_SO_NOEXE_NOT_REQUIRED))
	        {
	            $filenameto.= '.noexe';
	        }

	        if ($filenamefrom && $filenameto)
	        {
	            $srcpath = $upload_dir.'/'.$filenamefrom;
	            $destpath = $upload_dir.'/'.$filenameto;

	            $reshook=$hookmanager->initHooks(array('actionlinkedfiles'));
	            $parameters=array('filenamefrom' => $filenamefrom, 'filenameto' => $filenameto, 'upload_dir' => $upload_dir);
	            $reshook=$hookmanager->executeHooks('renameUploadedFile', $parameters, $object);

	            if (empty($reshook))
	            {
	            	if (! file_exists($destpath))
	            	{
	            		$result = dol_move($srcpath, $destpath);
			            if ($result)
			            {
			            	if ($object->id)
			            	{
			                	$object->addThumbs($destpath);
			            	}

			                // TODO Add revert function of addThumbs to remove for old name
			                //$object->delThumbs($srcpath);

			                setEventMessages($langs->trans("FileRenamed"), null);
			            }
			            else
			            {
			                $langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
			                setEventMessages($langs->trans("ErrorFailToRenameFile", $filenamefrom, $filenameto), null, 'errors');
			            }
	            	}
	            	else
	            	{
	            		$langs->load("errors"); // key must be loaded because we can't rely on loading during output, we need var substitution to be done now.
	            		setEventMessages($langs->trans("ErrorDestinationAlreadyExists", $filenameto), null, 'errors');
	            	}
	            }
	        }
        }
    }

    // Update properties in ECM table
    if (GETPOST('ecmfileid', 'int') > 0)
    {
    	$shareenabled = GETPOST('shareenabled', 'alpha');

    	include_once DOL_DOCUMENT_ROOT.'/ecm/class/ecmfiles.class.php';
	    $ecmfile=new EcmFiles($db);
	    $result = $ecmfile->fetch(GETPOST('ecmfileid', 'int'));
	    if ($result > 0)
	    {
	    	if ($shareenabled)
		    {
		    	if (empty($ecmfile->share))
		    	{
		    		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
		    		$ecmfile->share = getRandomPassword(true);
		    	}
		    }
		    else
		    {
		    	$ecmfile->share = '';
		    }
		    $result = $ecmfile->update($user);
		    if ($result < 0)
		    {
		    	setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
		    }
	    }
    }
}
