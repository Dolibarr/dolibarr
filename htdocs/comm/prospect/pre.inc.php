<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/comm/prospect/pre.inc.php
        \ingroup    commercial
        \brief      Fichier de gestion du menu gauche de l'espace commercial
        \version    $Id$
*/

require("../../main.inc.php");

function llxHeader($head = "", $urlp = "")
{
    global $user, $conf, $langs;

    $langs->load("companies");
    $langs->load("commercial");

    top_menu($head);

    $menu = new Menu();

    $menu->add(DOL_URL_ROOT."/comm/prospect/", $langs->trans("Prospection"));

    $menu->add_submenu(DOL_URL_ROOT."/soc.php?action=create&amp;type=p", $langs->trans("MenuNewProspect"));

    $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php", $langs->trans("List"));

    $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=0", $langs->trans("LastProspectNeverContacted"));
    $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=1", $langs->trans("LastProspectToContact"));
    $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=2", $langs->trans("LastProspectContactInProcess"));
    $menu->add_submenu(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&sortorder=desc&begin=&stcomm=3", $langs->trans("LastProspectContactDone"));

    $menu->add_submenu(DOL_URL_ROOT."/comm/contact.php?type=p", $langs->trans("Contacts"));

    $menu->add(DOL_URL_ROOT."/comm/action/index.php", $langs->trans("Actions"));

    if ($conf->propal->enabled && $user->rights->propale->lire)
    {
        $langs->load("propal");
        $menu->add(DOL_URL_ROOT."/comm/propal.php", $langs->trans("Prop"));
    }

    $menu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));

    left_menu($menu->liste);
}
?>
