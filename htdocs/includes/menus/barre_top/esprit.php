<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/includes/menus/barre_top/esprit.php
		\brief      Gestionnaire du menu du haut spécialisé vente de CD/livres
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu à faire apparaitre dans la barre du haut
        \remarks    doivent être affichées par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionnée.
*/


/**
        \class      MenuTop
	    \brief      Classe permettant la gestion du menu du haut Esprit
*/

class MenuTop {

    var $require_left=array();  // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $atarget="";            // Valeur du target a utiliser dans les liens

    
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
        
        print '<table class="tmenu"><tr class="tmenu">';

        // Entrée home
        print '<td class="tmenu"><a class="tmenu" href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Home").'</a></td>';

        // Autres entrées
        print '<td class="menu"><a class="tmenu" href="'.DOL_URL_ROOT.'/boutique/livre/"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Books").'</a></td>';
        print '<td class="menu"><a class="tmenu" href="'.DOL_URL_ROOT.'/boutique/client/"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Customers").'</a></td>';
        print '<td class="menu"><a class="tmenu" href="'.DOL_URL_ROOT.'/product/critiques/"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Criticals").'</a></td>';
        print '<td class="menu"><a class="tmenu" href="'.DOL_URL_ROOT.'/product/categorie/"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Categories").'</a></td>';

        print '</tr></table>';
    }

}

?>
