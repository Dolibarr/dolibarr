<?php
/* Copyright (C) 2007      Patrick Raguin  <patrick.raguin@gmail.com>
 * Copyright (C) 2007-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/includes/menus/barre_left/auguria_frontoffice.php
		\brief      Gestionnaire du menu du gauche Auguria
		\version    $Id$

        \remarks    La construction d'un gestionnaire pour le menu de gauche est simple:
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des méthode add et add_submenu,
        \remarks    définir la liste des entrées menu à faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est défini dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les définitions de menu des fichiers pre.inc.php
*/


/**
        \class      MenuLeft
	    \brief      Classe permettant la gestion du menu du gauche Auguria
*/

class MenuLeft {

    var $require_top=array("auguria_frontoffice");     // Si doit etre en phase avec un gestionnaire de menu du haut particulier
	var $newmenu;
	var $menuArbo;
    
	var $overwritemenufor = array();
    var $leftmenu;    
    
    /**
     *    \brief      Constructeur
     *    \param      db            Handler d'accï¿½s base de donnï¿½e
     *    \param      menu_array    Tableau des entrï¿½e de menu dï¿½fini dans les fichier pre.inc.php
     */
    function MenuLeft($db,&$menu_array)
    {
        $this->db=$db;
        $this->menu_array=$menu_array;
  
        $this->newmenu = new Menu();
    }
  
    
    /**
     *    \brief      Affiche le menu
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;
        
        if (! session_id()) {
            session_name("DOLSESSID_".$dolibarr_main_db_name);
            session_start();    // En mode authentification PEAR, la session a dï¿½jï¿½ ï¿½tï¿½ ouverte
        }

        // On rï¿½cupï¿½re mainmenu et leftmenu qui dï¿½finissent le menu ï¿½ afficher
        if (isset($_GET["mainmenu"])) {
            // On sauve en session le menu principal choisi
            $mainmenu=$_GET["mainmenu"];
            $_SESSION["mainmenu"]=$mainmenu;
            $_SESSION["leftmenuopened"]="";
        } else {
            // On va le chercher en session si non dï¿½fini par le lien    
            $mainmenu=$_SESSION["mainmenu"];
        }

        if (isset($_GET["leftmenu"])) {
            // On sauve en session le menu principal choisi
            $this->leftmenu=$_GET["leftmenu"];
            $_SESSION["leftmenu"]=$this->leftmenu;
            if ($_SESSION["leftmenuopened"]==$this->leftmenu) {
                //$leftmenu="";
                $_SESSION["leftmenuopened"]="";
            }
            else {
                $_SESSION["leftmenuopened"]=$this->leftmenu;
            }
        } else {
            // On va le chercher en session si non dï¿½fini par le lien    
            $this->leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
        }
        
        
        
        /**
         * On definit newmenu en fonction de mainmenu et leftmenu
         * ------------------------------------------------------
         */
        if ($mainmenu) 
        {
       		require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");
        	
       		$menuArbo = new Menubase($this->db,'auguria','left');
 			$this->overwritemenufor = $menuArbo->listeMainmenu();        
 			$this->newmenu = $menuArbo->menuLeftCharger($this->newmenu,$mainmenu,$this->leftmenu,1,'auguria');

            /*
             * Menu AUTRES (Pour les menus du haut qui ne serait pas gï¿½rï¿½s)
             */
            if ($mainmenu && ! in_array($mainmenu,$this->overwritemenufor)) { $mainmenu=""; }
        }


        
        /**
         *  Si on est sur un cas gï¿½rï¿½ de surcharge du menu, on ecrase celui par defaut
         */
        if ($mainmenu) {
            $this->menu_array=$this->newmenu->liste;
        }
		

				
		
        // Affichage du menu
        $alt=0;
        if (! sizeof($this->menu_array))
        {
            print '<div class="blockvmenuimpair">'."\n";
            print $langs->trans("NoMenu");
            print '</div>';        
        }
        else
        {
            $contenu = 0;
            for ($i = 0 ; $i < sizeof($this->menu_array) ; $i++) 
            {
                $alt++;
                if ($this->menu_array[$i]['level']==0)
				{
                    if (($alt%2==0))
                    {
                        print '<div class="blockvmenuimpair">'."\n";
                    }
                    else
                    {
                        print '<div class="blockvmenupair">'."\n";
                    }
                }
    
				// Place tabulation
				$tabstring='';
				$tabul=($this->menu_array[$i]['level'] - 1);
				if ($tabul > 0)
				{
					for ($j=0; $j < $tabul; $j++)
					{
						$tabstring.='&nbsp; &nbsp; ';
					}
				}

                // Menu niveau 0
				if ($this->menu_array[$i]['level']==0) 
                {
                    if ($contenu == 1) print '<div class="menu_fin"></div>'."\n";
                    if ($this->menu_array[$i]['enabled']) 
                    {
                        	
                        print '<div class="menu_titre">'.$tabstring.'<a class="menu_titre" href="'.$this->menu_array[$i]['url'].'"'.($this->menu_array[$i]['target']?' target="'.$this->menu_array[$i]['target'].'"':'').'>'.$this->menu_array[$i]['titre'].'</a></div>';	
                    }
                    else
                    { 
						print '<div class="menu_titre">'.$tabstring.'<font class="menu_titre_disabled">'.$this->menu_array[$i]['titre'].'</font></div>';
                    } 
                    $contenu = 0;  
                }
				// Menu niveau > 0
				if ($this->menu_array[$i]['level'] > 0)
				{
	                if ($this->menu_array[$i]['level']==1) $contenu = 1;

					if ($this->menu_array[$i]['enabled'])
					{
						print '<div class="menu_contenu">'.$tabstring.'<a class="vsmenu" href="'.$this->menu_array[$i]['url'].'"'.($this->menu_array[$i]['target']?' target="'.$this->menu_array[$i]['target'].'"':'').'>'.$this->menu_array[$i]['titre'].'</a></div>';
					}
					else 
					{
						print '<div class="menu_contenu">'.$tabstring.'<font class="vsmenudisabled">'.$this->menu_array[$i]['titre'].'</font></div>';
					}
                }
				
                if ($i == (sizeof($this->menu_array)-1) || $this->menu_array[$i+1]['level']==0)  {
                    print "</div>\n";
                }
                
            }
            if ($contenu == 1) print '<div class="menu_fin"></div>'."\n";
        }
    }
}

?>
