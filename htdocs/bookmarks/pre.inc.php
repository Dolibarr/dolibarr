<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

/**
        \file       htdocs/bookmarks/pre.inc.php
        \ingroup    bookmark
        \brief      Fichier de gestion du menu gauche des bookmarks
        \version    $Revision$
*/

require("../main.inc.php");


function llxHeader($head = '', $title='', $help_url='')
{
    global $user, $conf, $langs;
    $langs->load("other");

    top_menu($head, $title);

    $menu = new Menu();

    // Bookmarks
    if ($conf->bookmark->enabled)
    {
        if ($user->rights->bookmark->lire) $menu->add(DOL_URL_ROOT."/bookmarks/liste.php", $langs->trans("Bookmarks"));
        if ($user->rights->bookmark->creer) $menu->add_submenu(DOL_URL_ROOT."/bookmarks/fiche.php?action=create", $langs->trans("NewBookmark"));
        if ($user->rights->bookmark->lire) $menu->add_submenu(DOL_URL_ROOT."/bookmarks/liste.php", $langs->trans("List"));
    }

    left_menu($menu->liste);
}
?>
