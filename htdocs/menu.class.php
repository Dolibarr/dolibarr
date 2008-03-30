<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/menu.class.php
   \brief      Fichier de la classe de gestion du menu gauche
   \version    $Id$
*/


/**
   \class      Menu
   \brief      Classe de gestion du menu gauche
*/

class Menu {

    var $liste;

    /**
     *  \brief      Constructeur classe menu
     */
    function Menu()
    {
      $this->liste = array();
    }

    /**
     *  \brief      Vide l'objet menu de ces entrées
     */
    function clear()
    {
        $this->liste = array();
    }

    /**
     *  \brief      Ajoute une entrée de menu
     *  \param      url         Url a suivre sur le clic
     *  \param      titre       Libelle menu à afficher
     *  \param      level       Niveau du menu à ajouter
     *  \param      enabled     Menu actif ou non
     *  \param      target		Target lien
     */
    function add($url, $titre, $level=0, $enabled=1, $target='')
    {
        $i = sizeof($this->liste);
        $this->liste[$i]['url'] = $url;
        $this->liste[$i]['titre'] = $titre;
        $this->liste[$i]['level'] = $level;
        $this->liste[$i]['enabled'] = $enabled;
        $this->liste[$i]['target'] = $target;
    }

    /**
     *  \brief   Supprime la derniere entree de menu
     */
    function remove_last()
    { 
      if (sizeof($this->liste) > 1)
	array_pop($this->liste);
    }

    /**
     *  \brief      Ajoute une entrée de menu de niveau inférieur
     *  \param      url         Url a suivre sur le clic
     *  \param      titre       Libelle menu à afficher
     *  \param      level       Niveau du menu à ajouter
     *  \param      enabled     Menu actif ou non
     *  \param      target		Target lien
     */
    function add_submenu($url, $titre, $level=1, $enabled=1, $target='')
    {
        $i = sizeof($this->liste) - 1;
        $this->add($url, $titre, $level, $enabled, $target);
    }

}
