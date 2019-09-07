<?php
/* Copyright (C) - 2013-2016	Jean-François FERRY    <hello@librethic.io>
 * Copyright (C) - 2019     	Laurent Destailleur    <eldy@users.sourceforge.net>
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
 *       \brief      Public page to add and manage ticket
 */

if (!defined('NOCSRFCHECK'))   define('NOCSRFCHECK', '1');
if (!defined('NOREQUIREMENU')) define('NOREQUIREMENU', '1');
if (!defined("NOLOGIN"))       define("NOLOGIN", '1');				// If this page is public (can be called outside logged session)

// For MultiCompany module.
// Do not use GETPOST here, function is not defined and define must be done before including main.inc.php
// TODO This should be useless. Because entity must be retreive from object ref and not from url.
$entity=(! empty($_GET['entity']) ? (int) $_GET['entity'] : (! empty($_POST['entity']) ? (int) $_POST['entity'] : 1));
if (is_numeric($entity)) define("DOLENTITY", $entity);

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket', 'errors'));

// Get parameters
$track_id = GETPOST('track_id', 'alpha');
$action = GETPOST('action', 'alpha');


/*
 * View
 */

$form = new Form($db);
$formticket = new FormTicket($db);

if (empty($conf->global->TICKET_ENABLE_PUBLIC_INTERFACE))
{
	print $langs->trans('TicketPublicInterfaceForbidden');
	exit;
}

$arrayofjs = array();
$arrayofcss = array('/ticket/css/styles.css.php');

llxHeaderTicket($langs->trans("Tickets"), "", 0, 0, $arrayofjs, $arrayofcss);

print '<div style="margin: 0 auto; width:60%" class="ticketpublicarea">';
print '<p style="text-align: center">' . ($conf->global->TICKET_PUBLIC_TEXT_HOME ? $conf->global->TICKET_PUBLIC_TEXT_HOME : $langs->trans("TicketPublicDesc")) . '</p>';
print '<div class="ticketform">';
print '<a href="create_ticket.php" class=""><div class="index_create orange bigrounded">' . dol_escape_htmltag($langs->trans("CreateTicket")) . '</div></a>';
print '<a href="list.php" class=""><div class="index_display blue bigrounded">' . dol_escape_htmltag($langs->trans("ShowListTicketWithTrackId")) . '</div></a>';
print '<a href="view.php" class=""><div class="index_display blue bigrounded">' . dol_escape_htmltag($langs->trans("ShowTicketWithTrackId")) . '</div></a>';
print '<div style="clear:both;"></div>';
print '</div>';
print '</div>';

// End of page
htmlPrintOnlinePaymentFooter($mysoc, $langs, 0, $suffix, $object);

llxFooter('', 'public');

$db->close();
