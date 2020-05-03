<?php

/* Copyright (C) 2003-2007 Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Marc Barilley / Ocebo <marc@ocebo.com>
 * Copyright (C) 2005-2017 Regis Houssin         <regis.houssin@inodbox.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/compta/bank/document.php
 * 	\ingroup    banque
 * 	\brief      Page de gestion des documents attaches a un compte bancaire
 */
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/bank.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/images.lib.php";
require_once DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php";
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'companies', 'other'));

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
if ($user->socid) {
    $action = '';
    $socid = $user->socid;
}
if ($user->socid)
    $socid = $user->socid;

// Get parameters
$limit = GETPOST('limit', 'int') ? GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder)
    $sortorder = "ASC";
if (!$sortfield)
    $sortfield = "name";

$object = new Account($db);
if ($id > 0 || !empty($ref)) $object->fetch($id, $ref);

$result = restrictedArea($user, 'banque', $object->id, 'bank_account', '', '');


/*
 * Actions
 */

if ($object->id > 0)
{
    $object->fetch_thirdparty();
    $upload_dir = $conf->bank->dir_output."/".dol_sanitizeFileName($object->ref);
}

include_once DOL_DOCUMENT_ROOT.'/core/actions_linkedfiles.inc.php';


/*
 * View
 */

$title = $langs->trans("FinancialAccount").' - '.$langs->trans("Documents");
$helpurl = "";
llxHeader('', $title, $helpurl);

$form = new Form($db);

if ($id > 0 || !empty($ref)) {
    if ($object->fetch($id, $ref)) {
        $upload_dir = $conf->bank->dir_output.'/'.$object->ref;

        // Onglets
        $head = bank_prepare_head($object);
        dol_fiche_head($head, 'document', $langs->trans("FinancialAccount"), -1, 'account');


        // Build file list
        $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
        $totalsize = 0;
        foreach ($filearray as $key => $file) {
            $totalsize += $file['size'];
        }

        $morehtmlref = '';

        $linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

        dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


        print '<div class="fichecenter">';
        print '<div class="underbanner clearboth"></div>';

        print '<table class="border tableforfield centpercent">';
        print '<tr><td class="titlefield">'.$langs->trans("NbOfAttachedFiles").'</td><td colspan="3">'.count($filearray).'</td></tr>';
        print '<tr><td>'.$langs->trans("TotalSizeOfAttachedFiles").'</td><td colspan="3">'.dol_print_size($totalsize, 1, 1).'</td></tr>';
        print "</table>\n";

        print '</div>';

        dol_fiche_end();


        $modulepart = 'bank';
        $permission = $user->rights->banque->modifier;
        $permtoedit = $user->rights->banque->modifier;
        $param = '&id='.$object->id;
        include_once DOL_DOCUMENT_ROOT.'/core/tpl/document_actions_post_headers.tpl.php';
    }
    else {
        dol_print_error($db);
    }
}
else {
    Header('Location: index.php');
    exit;
}

// End of page
llxFooter();
$db->close();
