<?php
/* Copyright (C) 2013-2018	Jean-François FERRY	<hello@librethic.io>
 * Copyright (C) 2016		Christophe Battarel	<christophe@altairis.fr>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *    \file       core/lib/ticketsup.lib.php
 *    \ingroup    ticketsup
 *    \brief        This file is a library for TicketSup module
 */

/**
 * Build tabs for admin page
 *
 * @return array
 */
function ticketsupAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("ticketsup");

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticketsup.php';
    $head[$h][1] = $langs->trans("TicketSupSettings");
    $head[$h][2] = 'settings';
    $h++;
    $head[$h][0] = DOL_URL_ROOT.'/admin/ticketsup_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsTicketSup");
    $head[$h][2] = 'attributes';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //    'entity:+tabname:Title:@ticketsup:/ticketsup/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //    'entity:-tabname:Title:@ticketsup:/ticketsup/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticketsupadmin');

    return $head;
}

/**
 *  Build tabs for a Ticketsup object
 *
 *  @param	Ticketsup	  $object		Object Ticket
 *  @return array				          Array of tabs
 */
function ticketsup_prepare_head($object)
{
    global $db, $langs, $conf, $user;

    $h = 0;
    $head = array();
    $head[$h][0] = DOL_URL_ROOT.'/ticketsup/card.php?action=view&track_id=' . $object->track_id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'tabTicketsup';
    $h++;


    if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && empty($user->socid))
    {
    	$nbContact = count($object->liste_contact(-1,'internal')) + count($object->liste_contact(-1,'external'));
    	$head[$h][0] = DOL_URL_ROOT.'/ticketsup/contact.php?track_id='.$object->track_id;
    	$head[$h][1] = $langs->trans('ContactsAddresses');
    	if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
    	$head[$h][2] = 'contact';
    	$h++;
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticketsup');

    // Attached files
    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    $upload_dir = $conf->ticketsup->dir_output . "/" . $object->track_id;
    $nbFiles = count(dol_dir_list($upload_dir, 'files'));
    $head[$h][0] = dol_buildpath('/ticketsup/document.php', 1) . '?track_id=' . $object->track_id;
    $head[$h][1] = $langs->trans("Documents");
    if ($nbFiles > 0) {
        $head[$h][1] .= ' <span class="badge">' . $nbFiles . '</span>';
    }

    $head[$h][2] = 'tabTicketDocument';
    $h++;


    // History
    $head[$h][0] = DOL_URL_ROOT.'/ticketsup/history.php?track_id=' . $object->track_id;
    $head[$h][1] = $langs->trans('Events');
    $head[$h][2] = 'tabTicketLogs';
    $h++;


    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticketsup','remove');


    return $head;
}

/**
 *     Generate a random id
 *
 *    @param  string $car Char to generate key
 *     @return void
 */
function generate_random_id($car=16)
{
    $string = "";
    $chaine = "abcdefghijklmnopqrstuvwxyz123456789";
    srand((double) microtime() * 1000000);
    for ($i = 0; $i < $car; $i++) {
        $string .= $chaine[rand() % strlen($chaine)];
    }
    return $string;
}

/**
 * Show header for public pages
 *
 * @param  string $title       Title
 * @param  string $head        Head array
 * @param  int    $disablejs   More content into html header
 * @param  int    $disablehead More content into html header
 * @param  array  $arrayofjs   Array of complementary js files
 * @param  array  $arrayofcss  Array of complementary css files
 * @return void
 */
function llxHeaderTicket($title, $head = "", $disablejs = 0, $disablehead = 0, $arrayofjs = '', $arrayofcss = '')
{
    global $user, $conf, $langs, $mysoc;

    top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss); // Show html headers
    print '<body id="mainbody" class="publicnewticketform" style="margin-top: 10px;">';

    if (! empty($conf->global->TICKETS_SHOW_COMPANY_LOGO)) {
    	showlogo();
    }

    print '<div style="margin-left: 50px; margin-right: 50px;">';
}

/**
 * Show footer for new member
 *
 * @return void
 */
function llxFooterTicket()
{
    print '</div>';

    printCommonFooter('public');

    dol_htmloutput_events();

    print "</body>\n";
    print "</html>\n";
}

/**
 * Show logo
 *
 * @return void
 */
function showlogo()
{
    global $conf, $langs, $mysoc;

    // Print logo
    $urllogo = DOL_URL_ROOT . '/theme/login_logo.png';

    if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
        $urllogo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file=' . urlencode('thumbs/' . $mysoc->logo_small);
    } elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
        $urllogo = DOL_URL_ROOT . '/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file=' . urlencode($mysoc->logo);
        $width = 128;
    } elseif (is_readable(DOL_DOCUMENT_ROOT . '/theme/dolibarr_logo.png')) {
        $urllogo = DOL_URL_ROOT . '/theme/dolibarr_logo.png';
    }
    print '<center>';
    print '<a href="' . ($conf->global->TICKETS_URL_PUBLIC_INTERFACE ? $conf->global->TICKETS_URL_PUBLIC_INTERFACE : dol_buildpath('/ticketsup/public/index.php', 1)) . '"><img alt="Logo" id="logosubscribe" title="" src="' . $urllogo . '" style="max-width: 440px" /></a><br>';
    print '<strong>' . ($conf->global->TICKETS_PUBLIC_INTERFACE_TOPIC ? $conf->global->TICKETS_PUBLIC_INTERFACE_TOPIC : $langs->trans("TicketSystem")) . '</strong>';
    print '</center><br>';
}
