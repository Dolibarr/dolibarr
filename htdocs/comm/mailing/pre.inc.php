<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/comm/mailing/pre.inc.php
        \ingroup    mailing
        \brief      Fichier de gestion du menu gauche de l'espace mailing
        \version    $Id$
*/

require("../../main.inc.php");
require_once DOL_DOCUMENT_ROOT.'/comm/mailing/mailing.class.php';

$langs->load("companies");
$langs->load("mails");
$langs->load("exports");


function llxHeader($head = "", $title = "")
{
    global $user, $conf, $langs;
    
    top_menu($head, $title);
    
    $menu = new Menu();
    
    if ($user->rights->mailing->lire)
    {
        $menu->add(DOL_URL_ROOT."/comm/mailing/index.php", $langs->trans("Mailings"));
    }
    
    if ($user->rights->mailing->creer)
    {
        $menu->add_submenu(DOL_URL_ROOT."/comm/mailing/fiche.php?action=create", $langs->trans("NewMailing"));
    }
    
    if ($user->rights->mailing->lire)
    {
        $menu->add_submenu(DOL_URL_ROOT."/comm/mailing/liste.php", $langs->trans("List"));
    }
    
    left_menu($menu->liste);
}

?>
