<?php
/* Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/includes/menus/barre_left/eldy.php
		\brief      Gestionnaire du menu de gauche
		\version    $Revision$
*/

// Ce gestionnaire de menu écrase le tableau $menu pour le définir selon
// ces propres règles prioritairement aux définitions des fichiers pre.inc.php

$newmenu = new Menu();

$newmenu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));

/*
$class="";
if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "commercial")
    {
      $class='class="tmenu" id="sel"'; 
    }
elseif (ereg("^".DOL_URL_ROOT."\/fourn\/",$_SERVER["PHP_SELF"]))
    {
      $class='class="tmenu" id="sel"';
    }
else
    {
      $class = 'class="tmenu"';
    }

print '<a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php"'.($target?" target=$target":"").'>'.$langs->trans("Fournisseur").'</a>';
*/

//$menu=$newmenu->liste;

?>
