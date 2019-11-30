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
 *    \file       core/lib/ticket.lib.php
 *    \ingroup    ticket
 *    \brief        This file is a library for Ticket module
 */

/**
 * Build tabs for admin page
 *
 * @return array
 */
function ticketAdminPrepareHead()
{
    global $langs, $conf;

    $langs->load("ticket");

    $h = 0;
    $head = array();

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticket.php';
    $head[$h][1] = $langs->trans("TicketSettings");
    $head[$h][2] = 'settings';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticket_extrafields.php';
    $head[$h][1] = $langs->trans("ExtraFieldsTicket");
    $head[$h][2] = 'attributes';
    $h++;

    $head[$h][0] = DOL_URL_ROOT.'/admin/ticket_public.php';
    $head[$h][1] = $langs->trans("PublicInterface");
    $head[$h][2] = 'public';
    $h++;

    // Show more tabs from modules
    // Entries must be declared in modules descriptor with line
    //$this->tabs = array(
    //    'entity:+tabname:Title:@ticket:/ticket/mypage.php?id=__ID__'
    //); // to add new tab
    //$this->tabs = array(
    //    'entity:-tabname:Title:@ticket:/ticket/mypage.php?id=__ID__'
    //); // to remove a tab
    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticketadmin');

    return $head;
}

/**
 *  Build tabs for a Ticket object
 *
 *  @param	Ticket	  $object		Object Ticket
 *  @return array				          Array of tabs
 */
function ticket_prepare_head($object)
{
    global $db, $langs, $conf, $user;

    $h = 0;
    $head = array();
    $head[$h][0] = DOL_URL_ROOT.'/ticket/card.php?action=view&track_id=' . $object->track_id;
    $head[$h][1] = $langs->trans("Card");
    $head[$h][2] = 'tabTicket';
    $h++;

    if (empty($conf->global->MAIN_DISABLE_CONTACTS_TAB) && empty($user->socid))
    {
    	$nbContact = count($object->liste_contact(-1, 'internal')) + count($object->liste_contact(-1, 'external'));
    	$head[$h][0] = DOL_URL_ROOT.'/ticket/contact.php?track_id='.$object->track_id;
    	$head[$h][1] = $langs->trans('ContactsAddresses');
    	if ($nbContact > 0) $head[$h][1].= ' <span class="badge">'.$nbContact.'</span>';
    	$head[$h][2] = 'contact';
    	$h++;
    }

    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket');

    // Attached files
    include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
    $upload_dir = $conf->ticket->dir_output . "/" . $object->ref;
    $nbFiles = count(dol_dir_list($upload_dir, 'files'));
    $head[$h][0] = dol_buildpath('/ticket/document.php', 1) . '?id=' . $object->id;
    $head[$h][1] = $langs->trans("Documents");
    if ($nbFiles > 0) {
        $head[$h][1] .= ' <span class="badge">' . $nbFiles . '</span>';
    }

    $head[$h][2] = 'tabTicketDocument';
    $h++;


    // History
    $head[$h][0] = DOL_URL_ROOT.'/ticket/agenda.php?track_id=' . $object->track_id;
    $head[$h][1] = $langs->trans('Events');
    if (! empty($conf->agenda->enabled) && (!empty($user->rights->agenda->myactions->read) || !empty($user->rights->agenda->allactions->read) ))
    {
    	$head[$h][1].= '/';
    	$head[$h][1].= $langs->trans("Agenda");
    }
    $head[$h][2] = 'tabTicketLogs';
    $h++;


    complete_head_from_modules($conf, $langs, $object, $head, $h, 'ticket', 'remove');


    return $head;
}

/**
 *     Generate a random id
 *
 *    @param  string $car Char to generate key
 *     @return void
 */
function generate_random_id($car = 16)
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

    print '<body id="mainbody" class="publicnewticketform">';

    if (! empty($conf->global->TICKET_SHOW_COMPANY_LOGO) || ! empty($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC)) {
        print '<center>';
        // Print logo
        if (! empty($conf->global->TICKET_SHOW_COMPANY_LOGO))
        {
        	$urllogo = DOL_URL_ROOT . '/theme/login_logo.png';

        	if (!empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output . '/logos/thumbs/' . $mysoc->logo_small)) {
        		$urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file=' . urlencode('logos/thumbs/'.$mysoc->logo_small);
        	} elseif (!empty($mysoc->logo) && is_readable($conf->mycompany->dir_output . '/logos/' . $mysoc->logo)) {
        		$urllogo = DOL_URL_ROOT . '/viewimage.php?modulepart=mycompany&amp;entity='.$conf->entity.'&amp;file=' . urlencode('logos/'.$mysoc->logo);
        		$width = 128;
        	} elseif (is_readable(DOL_DOCUMENT_ROOT . '/theme/dolibarr_logo.png')) {
        		$urllogo = DOL_URL_ROOT . '/theme/dolibarr_logo.png';
        	}
    	    print '<a href="' . ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE : dol_buildpath('/public/ticket/index.php', 1)) . '"><img alt="Logo" id="logosubscribe" title="" src="' . $urllogo . '" style="max-width: 440px" /></a><br>';
        }
        if (! empty($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC))
        {
    	   print '<strong>' . ($conf->global->TICKET_PUBLIC_INTERFACE_TOPIC ? $conf->global->TICKET_PUBLIC_INTERFACE_TOPIC : $langs->trans("TicketSystem")) . '</strong>';
        }
    	print '</center><br>';
    }

    print '<div class="ticketlargemargin">';
}
