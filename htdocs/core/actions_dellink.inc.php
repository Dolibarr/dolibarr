<?php
/* Copyright (C) 2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file			htdocs/core/actions_dellink.inc.php
 *  \brief			Code for actions on deleting link between elements
 */


// $action must be defined
// $object must be defined
// $permissiondellink must be defined
// $uploaddir (example $conf->projet->dir_output . "/";)

$dellinkid = GETPOST('dellinkid','int');

// Set public note
if ($action == 'dellink' && ! empty($permissiondellink) && ! GETPOST('cancel') && $dellinkid > 0)
{
	$result=$object->deleteObjectLinked(0, '', 0, '', $dellinkid);
	if ($result < 0) setEventMessages($object->error,$object->errors,'errors');
}

// Build doc
/* TODO To mutualise code for builddoc and remove_file
if ($action == 'builddoc' && $permissiondellink)
{
	// Save last template used to generate document
	if (GETPOST('model')) $object->setDocModel($user, GETPOST('model','alpha'));

    $outputlangs = $langs;
    if (GETPOST('lang_id'))
    {
        $outputlangs = new Translate("",$conf);
        $outputlangs->setDefaultLang(GETPOST('lang_id'));
    }
    $result= $object->generateDocument($object->modelpdf, $outputlangs);
    if ($result <= 0)
    {
        setEventMessages($object->error, $object->errors, 'errors');
        $action='';
    }
}

// Delete file in doc form
if ($action == 'remove_file' && $permissiondellink)
{
    require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

    if ($object->id > 0)
    {
        $langs->load("other");
        $urlfile=GETPOST('urlfile','alpha');
        $file =	$upload_dir	. '/' .	$filetodelete;
        $ret=dol_delete_file($file);
        if ($ret) setEventMessage($langs->trans("FileWasRemoved", $urlfile));
        else setEventMessage($langs->trans("ErrorFailToDeleteFile", $urlfile), 'errors');
    }
}
*/

