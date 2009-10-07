<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/livraison/pre.inc.php
        \ingroup    livraison
        \brief      Fichier de gestion du menu gauche du module livraison
        \version    $Id$
*/

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");

$langs->load("orders");
$langs->load("sendings");


function llxHeader($head = '', $title='', $help_url='')
{
  global $langs;

  top_menu($head, $title);

  $menu = new Menu();

  $menu->add(DOL_URL_ROOT."/commande/", $langs->trans("Orders"));

  $menu->add(DOL_URL_ROOT."/livraison/", $langs->trans("Sendings"));
  $menu->add_submenu(DOL_URL_ROOT."/livraison/liste.php", $langs->trans("List"));
  $menu->add_submenu(DOL_URL_ROOT."/livraison/stats/", $langs->trans("Statistics"));

  left_menu($menu->liste, $help_url);
}
?>
