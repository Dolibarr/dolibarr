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
		\brief      Gestionnaire par défaut du menu du gauche
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
        \remarks    définir la liste des entrées menu à faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est définir dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les définitions de menu des fichiers pre.inc.php
*/


$newmenu = new Menu();


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

$newmenu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));
$newmenu->add(DOL_URL_ROOT."/comm/clients.php", $langs->trans("Customers"));

*/

//$menu=$newmenu->liste;

?>
