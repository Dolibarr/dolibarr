<?php
/* Copyright (C) 2002-2007      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010      Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010           Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2013-2016      Jean-François Ferry  <hello@librethic.io>
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
 */

/**
 *  \file       /ticketsup/document.php
 *  \ingroup    ticketsup
 *  \brief      files linked to a ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/ticketsup.lib.php';
require_once DOL_DOCUMENT_ROOT . '/ticketsup/class/ticketsup.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/images.lib.php';
require_once DOL_DOCUMENT_ROOT . "/core/lib/company.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formfile.class.php';

$langs->load("companies");
$langs->load('other');
$langs->load("ticketsup");

$action = GETPOST('action');
$confirm = GETPOST('confirm');
$id = GETPOST('id', 'int');
$track_id = GETPOST('track_id', 'alpha');
$ref = GETPOST('ref', 'alpha');

// Security check
if (!$user->rights->ticketsup->read) {
    accessforbidden();
}

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
if (!$sortorder) {
    $sortorder = "ASC";
}

if (!$sortfield) {
    $sortfield = "name";
}

$object = new Ticketsup($db);
$result = $object->fetch($id, $track_id, $ref);

// to match  document rules and compatibility
$old_ref = $object->ref;
$object->ref = $object->track_id;


if ($result < 0) {
    setEventMessage($object->error, 'errors');
} else {
    $upload_dir = $conf->ticketsup->dir_output . "/" . dol_sanitizeFileName($object->track_id);
}

/*
 * Actions
 */
// Included file moved into Dolibarr 4, keep it for compatibility
$res=@include_once DOL_DOCUMENT_ROOT . '/core/actions_linkedfiles.inc.php';
if (! $res) {
    include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_pre_headers.tpl.php';
}

$object->ref = $old_ref;

/*
 * View
 */

$form = new Form($db);

$help_url = '';
llxHeader('', $langs->trans("TicketDocumentsLinked") . ' - ' . $langs->trans("Files"), $help_url);

if ($object->id) {
    /*
     * Affichage onglets
     */
    if (!empty($conf->notification->enabled)) {
        $langs->load("mails");
    }

    $form = new Form($db);
    if ($object->fk_soc > 0) {
        $object->fetch_thirdparty();
        $head = societe_prepare_head($object->thirdparty);
        dol_fiche_head($head, 'ticketsup', $langs->trans("ThirdParty"), 0, 'company');
        dol_banner_tab($object->thirdparty, 'socid', '', ($user->societe_id ? 0 : 1), 'rowid', 'nom');
        dol_fiche_end();
    }

    if (!$user->societe_id && $conf->global->TICKETS_LIMIT_VIEW_ASSIGNED_ONLY) {
        $object->next_prev_filter = "te.fk_user_assign = '" . $user->id . "'";
    } elseif ($user->societe_id > 0) {
        $object->next_prev_filter = "te.fk_soc = '" . $user->societe_id . "'";
    }
    $head = ticketsup_prepare_head($object);
    dol_fiche_head($head, 'tabTicketDocument', $langs->trans("Ticket"), 0, 'ticketsup');
    $object->label = $object->ref;
    // Author
    if ($object->fk_user_create > 0) {
        $object->label .= ' - ' . $langs->trans("CreatedBy") . '  ';
        $langs->load("users");
        $fuser = new User($db);
        $fuser->fetch($object->fk_user_create);
        $object->label .= $fuser->getNomUrl(0);
    }
    $linkback = '<a href="' . dol_buildpath('/ticketsup/list.php', 1) . '"><strong>' . $langs->trans("BackToList") . '</strong></a> ';
    $object->ticketsupBannerTab('ref', '', ($user->societe_id ? 0 : 1), 'ref', 'subject', '', '', '', $morehtmlleft, $linkback);

    dol_fiche_end();

    // Construit liste des fichiers
    $filearray = dol_dir_list($upload_dir, "files", 0, '', '\.meta$', $sortfield, (strtolower($sortorder) == 'desc' ? SORT_DESC : SORT_ASC), 1);
    $totalsize = 0;
    foreach ($filearray as $key => $file) {
        $totalsize += $file['size'];
    }
    // For compatibility we use track ID for directory
    $object->ref = $object->track_id;
    $modulepart = 'ticketsup';
  	$permission = $user->rights->ticketsup->write;
  	include_once DOL_DOCUMENT_ROOT . '/core/tpl/document_actions_post_headers.tpl.php';


    print "<br><br>";
} else {
    accessforbidden('', 0, 0);
}

llxFooter();
$db->close();
