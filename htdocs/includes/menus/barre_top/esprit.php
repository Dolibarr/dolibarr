<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004          Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**	    \file       htdocs/includes/menus/barre_top/esprit.php
		\brief      Gestionnaire du menu du haut spécialisé vente de CD/livres
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu à faire apparaitre dans la barre du haut
        \remarks    doivent être affichées par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionnée.
*/


/**     \class      MenuTop
	    \brief      Classe permettant la gestion du menu du haut Esprit
*/

class MenuTop {

    var $require_left=array();    // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $showhome=true;           // Faut-il afficher le menu Accueil par le main.inc.php

    
    /**
     *    \brief      Constructeur
     *    \param      db      Handler d'accès base de donnée
     */
    function MenuTop($db)
    {
        $this->db=$db;
    }
    
    
    /**
     *    \brief      Affiche le menu
     */
    function showmenu()
    {
        global $conf,$langs;
        $langs->load("commercial");
        $langs->load("other");
        
        print '<a class="tmenu" href="/boutique/livre/">'.$langs->trans("Books").'</a>';
        print '<a class="tmenu" href="/boutique/client/">'.$langs->trans("Customers").'</a>';
        print '<a class="tmenu" href="/product/critiques/">'.$langs->trans("Criticals").'</a>';
        print '<a class="tmenu" href="/product/categorie/">'.$langs->trans("Categories").'</a>';
    }

}

?>
