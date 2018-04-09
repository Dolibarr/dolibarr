<?php
/* Copyright (C) 2013-2016  Jean-François FERRY <hello@librethic.io>
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
 *      \file       ticketsup/admin/ticketsup_extrafields.php
 *        \ingroup    ticketsup
 *        \brief      Page to setup extra fields of ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT."/core/lib/ticketsup.lib.php";
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/admin.lib.php';

$langs->load("ticketsup");
$langs->load("admin");

$extrafields = new ExtraFields($db);
$form = new Form($db);

// List of supported format
$tmptype2label = ExtraFields::$type2label;
$type2label = array('');
foreach ($tmptype2label as $key => $val) {
    $type2label[$key] = $langs->trans($val);
}

$action = GETPOST('action', 'alpha');
$attrname = GETPOST('attrname', 'alpha');
$elementtype = 'ticketsup'; //Must be the $table_element of the class that manage extrafield

if (!$user->admin) {
    accessforbidden();
}


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT . '/core/actions_extrafields.inc.php';



/*
 * View
 */

$textobject = $langs->transnoentitiesnoconv("TicketSup");

$help_url = "FR:Module_Ticket";
$page_name = "TicketsupSetup";
llxHeader('', $langs->trans($page_name), $help_url);

$linkback = '<a href="' . DOL_URL_ROOT . '/admin/modules.php">' . $langs->trans("BackToModuleList") . '</a>';
print load_fiche_titre($langs->trans("TicketsupSetup"), $linkback, 'title_setup');

$head = ticketsupAdminPrepareHead();

dol_fiche_head($head, 'attributes', $langs->trans("Module56000Name"), -1, "ticketsup");

require DOL_DOCUMENT_ROOT.'/core/tpl/admin_extrafields_view.tpl.php';

dol_fiche_end();

// Buttons
if ($action != 'create' && $action != 'edit') {
    print '<div class="tabsAction">';
    print '<div class="inline-block divButAction"><a class="butAction" href="' . $_SERVER["PHP_SELF"] . "?action=create\">" . $langs->trans("NewAttribute") . '</a></div>';
    print "</div>";
}

/* ************************************************************************** */
/*                                                                            */
/* Creation d'un champ optionnel                                              */
/*                                                                            */
/* ************************************************************************** */

if ($action == 'create') {
    print "<br>";
    print_titre($langs->trans('NewAttribute'));

    include DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_add.tpl.php';
}

/* ************************************************************************** */
/*                                                                            */
/* Edition d'un champ optionnel                                               */
/*                                                                            */
/* ************************************************************************** */
if ($action == 'edit' && !empty($attrname)) {
    print "<br>";
    print_titre($langs->trans("FieldEdition", $attrname));

    include DOL_DOCUMENT_ROOT . '/core/tpl/admin_extrafields_edit.tpl.php';
}

llxFooter();

$db->close();
