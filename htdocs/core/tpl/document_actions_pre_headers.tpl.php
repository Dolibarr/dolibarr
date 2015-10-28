<?php
/* Copyright (C)    2013    Cédric Salvador    <csalvador@gpcsolutions.fr>
 * Copyright (C)    2015    Marcos García      <marcosgdf@gmail.com>
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


// TODO This is an action include, not a presentation template.
// Move this file into htdocs/core/actions_document.inc.php


// Variable $upload_dir must be defined when entering here

// Send file/link
if (GETPOST('sendit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    if ($object->id)
    {
        dol_add_file_process($upload_dir, 0, 1, 'userfile', GETPOST('savingdocmask'));
    }
}
elseif (GETPOST('linkit') && ! empty($conf->global->MAIN_UPLOAD_DOC))
{
    if ($object->id)
    {
        $link = GETPOST('link', 'alpha');
        if ($link)
        {
            if (substr($link, 0, 7) != 'http://' && substr($link, 0, 8) != 'https://') {
                $link = 'http://' . $link;
            }
            dol_add_file_process($upload_dir, 0, 1, 'userfile', null, $link);
        }
    }
}


// Delete file/link
if ($action == 'confirm_deletefile' && $confirm == 'yes')
{
    if ($object->id)
    {
        $urlfile = GETPOST('urlfile', 'alpha');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
        if (GETPOST('section')) $file = $upload_dir . "/" . $urlfile;	// For a delete of GED module urlfile contains full path from upload_dir
        else															// For documents pages, upload_dir contains already path to file from module dir, so we clean path into urlfile.
		{
       		$urlfile=basename($urlfile);
			$file = $upload_dir . "/" . $urlfile;
		}
        $linkid = GETPOST('linkid', 'int');	// Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).

        if ($urlfile)
        {
	        $dir = dirname($file).'/'; // Chemin du dossier contenant l'image d'origine
	        $dirthumb = $dir.'/thumbs/'; // Chemin du dossier contenant la vignette

            $ret = dol_delete_file($file, 0, 0, 0, $object);

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

            if ($ret) setEventMessage($langs->trans("FileWasRemoved", $urlfile));
            else setEventMessage($langs->trans("ErrorFailToDeleteFile", $urlfile), 'errors');
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
                setEventMessage($langs->trans("LinkRemoved", $link->label));
            } else {
                if (count($link->errors)) {
                    setEventMessages('', $link->errors, 'errors');
                } else {
                    setEventMessage($langs->trans("ErrorFailedToDeleteLink", $link->label), 'errors');
                }
            }
        }
        header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $object->id.(!empty($withproject)?'&withproject=1':''));
        exit;
    }
}
elseif ($action == 'confirm_updateline' && GETPOST('save') && GETPOST('link', 'alpha'))
{
    require_once DOL_DOCUMENT_ROOT . '/core/class/link.class.php';
    $langs->load('link');
    $link = new Link($db);
    $link->id = GETPOST('linkid', 'int');
    $f = $link->fetch();
    if ($f)
    {
        $link->url = GETPOST('link', 'alpha');
        if (substr($link->url, 0, 7) != 'http://' && substr($link->url, 0, 8) != 'https://')
        {
            $link->url = 'http://' . $link->url;
        }
        $link->label = GETPOST('label', 'alpha');
        $res = $link->update($user);
        if (!$res)
        {
            setEventMessage($langs->trans("ErrorFailedToUpdateLink", $link->label));
        }
    }
    else
    {
        //error fetching
    }
}

