<?php
/* Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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

session_start();

$newmenu = new Menu();


/**
 * On récupère mainmenu qui définit le menu à afficher
 */
if (isset($_GET["mainmenu"])) {
    // On sauve en session le menu principal choisi
    $mainmenu=$_GET["mainmenu"];
    $_SESSION["mainmenu"]=$mainmenu;
} else {
    // On va le chercher en session si non défini par le lien    
    $mainmenu=$_SESSION["mainmenu"];
}


/**
 * On definit newmenu en fonction de mainmenu
 */
if ($mainmenu) {
   
    // Menu HOME
    if ($mainmenu == 'home') {
        $newmenu->add(DOL_URL_ROOT."/user/index.php", $langs->trans("Users"));
        
        if($user->admin)
        {
          $langs->load("users");
          $langs->load("admin");
          $newmenu->add_submenu(DOL_URL_ROOT."/user/fiche.php?action=create", $langs->trans("NewUser"));
          $newmenu->add(DOL_URL_ROOT."/admin/index.php?", $langs->trans("Setup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/index.php", $langs->trans("GlobalSetup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/perms.php", $langs->trans("DefaultRights"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"));
          $newmenu->add_submenu(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"));
          $newmenu->add(DOL_URL_ROOT."/admin/system/?mainmenu=", $langs->trans("System"));

        }
    }

    // En attendant que les autres principaux soit gérés
    if ($mainmenu != 'home') { $mainmenu=""; }
}

/**
 *  Si on est sur un cas géré de surcharge du menu, on ecrase celui par defaut
 */
if ($mainmenu) {
    $menu=$newmenu->liste;
}

?>
