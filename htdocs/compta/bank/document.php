<?php

/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2011 Regis Houssin         <regis@dolibarr.fr>
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
 */

/**
 * 	\file       htdocs/compta/bank/document.php
 * 	\ingroup    banque
 * 	\brief      Page de gestion des documents attaches a un compte bancaire
 */
require('../../main.inc.php');
require_once(DOL_DOCUMENT_ROOT . "/core/lib/bank.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/lib/images.lib.php");
require_once(DOL_DOCUMENT_ROOT . "/core/class/html.formfile.class.php");
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';

$langs->load("banks");


$langs->load('companies');
$langs->load('other');

$id = (GETPOST('id', 'int') ? GETPOST('id', 'int') : GETPOST('account', 'int'));
$ref = GETPOST('ref', 'alpha');
$action = GETPOST('action', 'alpha');
$confirm = GETPOST('confirm', 'alpha');

$mesg = '';
if (isset($_SESSION['DolMessage'])) {
    $mesg = $_SESSION['DolMessage'];
    unset($_SESSION['DolMessage']);
}

// Security check
if ($user->societe_id) {
    $action = '';
    $socid = $user->societe_id;
}
if ($user->societe_id)
    $socid = $user->societe_id;
$result = restrictedArea($user, 'banque', $fieldvalue, 'bank_account', '', '',
        $fieldtype);

// Get parameters
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if ($page == -1) {
    $page = 0;
}
$offset = $conf->liste_limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder)
    $sortorder = "ASC";
if (!$sortfield)
    $sortfield = "name";

$object = new Account($db);
if ($id)
    $object->fetch($id);

/*
 * Actions
 */

// Envoi fichier
if ($_POST["sendit"] && !empty($conf->global->MAIN_UPLOAD_DOC)) {
    if ($object->fetch($id)) {

        $upload_dir = $conf->bank->dir_output . "/" . $object->ref;

        if (dol_mkdir($upload_dir) >= 0) {
            $resupload = dol_move_uploaded_file($_FILES['userfile']['tmp_name'],
                    $upload_dir . "/" . dol_unescapefile($_FILES['userfile']['name']),
                    0, 0, $_FILES['userfile']['error']);
            if (is_numeric($resupload) && $resupload > 0) {
                if (image_format_supported($upload_dir . "/" . $_FILES['userfile']['name'])
                        == 1) {
                    // Create small thumbs for image (Ratio is near 16/9)
                    // Used on logon for example
                    $imgThumbSmall = vignette($upload_dir . "/" . $_FILES['userfile']['name'],
                            $maxwidthsmall, $maxheightsmall, '_small', $quality,
                            "thumbs");
                    // Create mini thumbs for image (Ratio is near 16/9)
                    // Used on menu or for setup page for example
                    $imgThumbMini = vignette($upload_dir . "/" . $_FILES['userfile']['name'],
                            $maxwidthmini, $maxheightmini, '_mini', $quality,
                            "thumbs");
                }
                $mesg = '<div class="ok">' . $langs->trans("FileTransferComplete") . '</div>';
            }
            else {
                $langs->load("errors");
                if ($resupload < 0) { // Unknown error
                    $mesg = '<div class="error">' . $langs->trans("ErrorFileNotUploaded") . '</div>';
                }
                else if (preg_match('/ErrorFileIsInfectedWithAVirus/',
                                $resupload)) { // Files infected by a virus
                    $mesg = '<div class="error">' . $langs->trans("ErrorFileIsInfectedWithAVirus") . '</div>';
                }
                else { // Known error
                    $mesg = '<div class="error">' . $langs->trans($resupload) . '</div>';
                }
            }
        }
    }
}

// Delete
else if ($action == 'confirm_deletefile' && $confirm == 'yes') {
    if ($object->fetch($id)) {

        $upload_dir = $conf->bank->dir_output;
        $file = $upload_dir . '/' . GETPOST('urlfile'); // Do not use urldecode here ($_GET and $_REQUEST are already decoded by PHP).
        
            $ret = dol_delete_file($file, 0, 0, 0, $object);
            if ($ret) {
                setEventMessages($langs->trans("FileWasRemoved", GETPOST('urlfile')), null, 'mesgs');
            } else {
                setEventMessages($langs->trans("ErrorFailToDeleteFile", GETPOST('urlfile')), null, 'errors');
            }
            
        Header('Location: ' . $_SERVER["PHP_SELF"] . '?id=' . $id);
        exit;
    }
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
    if ($object->fetch($id, $ref)) {

        $upload_dir = $conf->bank->dir_output . '/' . $object->ref;

        // Onglets
        $head = bank_prepare_head($object);
        dol_fiche_head($head, 'document', $langs->trans("FinancialAccount"), 0,
                'account');


        // Construit liste des fichiers
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$',
                $sortfield,
                (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
        $totalsize = 0;
        foreach ($filearray as $key => $file) {
            $totalsize+=$file['size'];
        }


        print '<table class="border"width="100%">';

        // Ref
        // Ref
        print '<tr><td valign="top" width="25%">' . $langs->trans("Ref") . '</td>';
        print '<td colspan="3">';
        print $form->showrefnav($object, 'ref', '', 1, 'ref');
        print '</td></tr>';

        // Label
        print '<tr><td valign="top">' . $langs->trans("Label") . '</td>';
        print '<td colspan="3">' . $object->label . '</td></tr>';

        // Status
        print '<tr><td valign="top">' . $langs->trans("Status") . '</td>';
        print '<td colspan="3">' . $object->getLibStatut(4) . '</td></tr>';
        print '<tr><td>' . $langs->trans("NbOfAttachedFiles") . '</td><td colspan="3">' . count($filearray) . '</td></tr>';
        print '<tr><td>' . $langs->trans("TotalSizeOfAttachedFiles") . '</td><td colspan="3">' . $totalsize . ' ' . $langs->trans("bytes") . '</td></tr>';
        print "</table>\n";
        print "</div>\n";

        dol_htmloutput_mesg($mesg, $mesgs);

        /*
         * Confirmation suppression fichier
         */
        if ($action == 'delete') {
            $ret = $form->form_confirm($_SERVER["PHP_SELF"] . '?id=' . $object->id . '&urlfile=' . urlencode($_GET["urlfile"]),
                    $langs->trans('DeleteFile'),
                    $langs->trans('ConfirmDeleteFile'), 'confirm_deletefile',
                    '', 0, 1);
            if ($ret == 'html')
                print '<br>';
        }

        // Affiche formulaire upload
        $formfile = new FormFile($db);
        $formfile->form_attach_new_file(DOL_URL_ROOT . '/compta/bank/document.php?id=' . $object->id,
                '', 0, 0, $user->rights->banque, 50, $object);


        // List of document
        $param = '&id=' . $object->id;
        $formfile->list_of_documents($filearray, $object, 'bank', $param);
    }
    else {
        dol_print_error($db);
    }
}
else {
    Header('Location: index.php');
}


llxFooter();

$db->close();
