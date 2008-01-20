<?php
/* Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**	    \file       htdocs/includes/menus/barre_left/rodolphe.php
		\brief      Gestionnaire par défaut du menu de gauche
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
        \remarks    définir la liste des entrées menu à faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est définir dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les définitions de menu des fichiers pre.inc.php
*/


/**     \class      MenuLeft
	    \brief      Classe permettant la gestion par défaut du menu du gauche
        \remarks    Le gestionnaire par defaut ne fait rien: C'est donc le menu défini dans les
        \remarks    fichiers pre.inc.php du répertoire de la page qui est utilisé.
*/

class MenuLeft {

    var $require_top=array("");     // Si doit etre en phase avec un gestionnaire de menu du haut particulier

    
    /**
     *    \brief      Constructeur
     *    \param      db      Handler d'accès base de donnée
     *    \param      menu_array    Tableau des entrée de menu défini dans les fichier pre.inc.php
     */
    function MenuLeft($db,&$menu_array)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
    }
  
    
    /**
     *    \brief      Affiche le menu
     */
    function showmenu()
    {
        global $user, $conf, $langs;

        $alt=0;
        for ($i = 0 ; $i < sizeof($this->menu_array) ; $i++) 
        {
            $alt++;
            if ($this->menu_array[$i]['level']==0) {
                if (($alt%2==0))
                {
                    print '<div class="blockvmenuimpair">'."\n";
                }
                else
                {
                    print '<div class="blockvmenupair">'."\n";
                }
            }

            if ($this->menu_array[$i]['level']==0) {
                print '<a class="vmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
            }
            if ($this->menu_array[$i]['level']==1) {
                print '<a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
            }
            if ($this->menu_array[$i]['level']==2) {
                print '&nbsp; &nbsp; <a class="vsmenu" href="'.$this->menu_array[$i]['url'].'">'.$this->menu_array[$i]['titre'].'</a><br>';
            }
            
            if ($i == (sizeof($this->menu_array)-1) || $this->menu_array[$i+1]['level']==0)  {
                print "</div>\n";
            }
        }

    }
    
}

?>
