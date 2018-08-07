<?php
/*  Copyright (C) - 2013-2016	Jean-François FERRY    <hello@librethic.io>
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
 *       \file       htdocs/public/ticket/index.php
 *       \ingroup    ticket
 *       \brief      Public file to add and manage ticket
 */

if (!defined('NOCSRFCHECK'))   define('NOCSRFCHECK', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined("NOLOGIN"))       define("NOLOGIN", '1');				// If this page is public (can be called outside logged session)

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket', 'errors'));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action = GETPOST('action', 'alpha');

/***************************************************
 * VIEW
 *
 ****************************************************/
$form = new Form($db);
$formticket = new FormTicket($db);

$arrayofjs = array();
$arrayofcss = array('/ticket/css/styles.css.php');
llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);

if (!$conf->global->TICKET_ENABLE_PUBLIC_INTERFACE) {
    print '<div class="error">' . $langs->trans('TicketPublicInterfaceForbidden') . '</div>';
} else {
    print '<div style="margin: 0 auto; width:60%">';
    print '<p style="text-align: center">' . ($conf->global->TICKET_PUBLIC_TEXT_HOME ? $conf->global->TICKET_PUBLIC_TEXT_HOME : $langs->trans("TicketPublicDesc")) . '</p>';
    print '<div class="corps">';
    print '<div class="index_create"><a href="create_ticket.php" class="button orange bigrounded"><strong>&nbsp;' . dol_escape_htmltag($langs->trans("CreateTicket")) . '</strong></a></div>';
    print '<div class="index_display"><a href="list.php" class="button blue bigrounded"><strong>&nbsp;' . dol_escape_htmltag($langs->trans("ShowListTicketWithTrackId")) . '</strong></a></div>';
    print '<div class="index_display"><a href="view.php" class="button blue bigrounded"><strong>&nbsp;' . dol_escape_htmltag($langs->trans("ShowTicketWithTrackId")) . '</strong></a></div>';
    print '<div style="clear:both;"></div>';
    print '</div>';
    print '</div>';
}

// End of page
llxFooter();
$db->close();
