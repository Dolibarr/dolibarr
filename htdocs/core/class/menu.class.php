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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/class/menu.class.php
 *  \ingroup    core
 *  \brief      Fichier de la classe de gestion du menu gauche
 */


/**
 *  \class      Menu
 *	\brief      Classe de gestion du menu gauche
 */
class Menu {

    var $liste;

    /**
	 *	Constructor
     */
    function Menu()
    {
      	$this->liste = array();
    }

    /**
     *  \brief      Vide l'objet menu de ces entrees
     */
    function clear()
    {
        $this->liste = array();
    }

    /**
     *  \brief      Add a menu entry
     *  \param      url         Url a suivre sur le clic
     *  \param      titre       Libelle menu a afficher
     *  \param      level       Niveau du menu a ajouter
     *  \param      enabled     Menu actif ou non
     *  \param      target		Target lien
     */
    function add($url, $titre, $level=0, $enabled=1, $target='',$mainmenu='')
    {
        $i = count($this->liste);
        $this->liste[$i]['url'] = $url;
        $this->liste[$i]['titre'] = $titre;
        $this->liste[$i]['level'] = $level;
        $this->liste[$i]['enabled'] = $enabled;
        $this->liste[$i]['target'] = $target;
        $this->liste[$i]['mainmenu'] = $mainmenu;
    }

    /**
     *  \brief   	Remove a menu entry
     */
    function remove_last()
    {
    	if (count($this->liste) > 1) array_pop($this->liste);
    }

}
