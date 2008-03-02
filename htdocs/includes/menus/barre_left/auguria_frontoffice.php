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
        \remarks    A l'aide d'un objet $newmenu=new Menu() et des m�thode add et add_submenu,
        \remarks    d�finir la liste des entr�es menu � faire apparaitre.
        \remarks    En fin de code, mettre la ligne $menu=$newmenu->liste.
        \remarks    Ce qui est d�fini dans un tel gestionnaire sera alors prioritaire sur
        \remarks    les d�finitions de menu des fichiers pre.inc.php
*/


/**
        \class      MenuLeft
	    \brief      Classe permettant la gestion du menu du gauche Auguria
*/

class MenuLeft {

    var $require_top=array("auguria_backoffice");     // Si doit etre en phase avec un gestionnaire de menu du haut particulier
	var $newmenu;
	var $menuArbo;
    
	var $overwritemenufor = array();
    var $leftmenu;    
    
    /**
     *    \brief      Constructeur
     *    \param      db            Handler d'acc�s base de donn�e
     *    \param      menu_array    Tableau des entr�e de menu d�fini dans les fichier pre.inc.php
     */
    function MenuLeft($db,&$menu_array)
    {
       	require_once(DOL_DOCUMENT_ROOT."/lib/menubase.class.php");
        
        $this->db=$db;
        $this->menu_array=$menu_array;
  
        $this->newmenu = new Menu();
        
        $this->menuArbo = new Menubase($this->db,'auguria','left');
 		$this->overwritemenufor = $this->menuArbo->listeMainmenu();        
    }
  
    
    /**
     *    \brief      Affiche le menu
     */
    function showmenu()
    {
        global $user,$conf,$langs,$dolibarr_main_db_name;
        
        if (! session_id()) {
            session_name("DOLSESSID_".$dolibarr_main_db_name);
            session_start();    // En mode authentification PEAR, la session a d�j� �t� ouverte
        }

        // On r�cup�re mainmenu et leftmenu qui d�finissent le menu � afficher
        if (isset($_GET["mainmenu"])) {
            // On sauve en session le menu principal choisi
            $mainmenu=$_GET["mainmenu"];
            $_SESSION["mainmenu"]=$mainmenu;
            $_SESSION["leftmenuopened"]="";
        } else {
            // On va le chercher en session si non d�fini par le lien    
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
            // On va le chercher en session si non d�fini par le lien    
            $this->leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
        }
        
        
        
        /**
         * On definit newmenu en fonction de mainmenu et leftmenu
         * ------------------------------------------------------
         */
        if ($mainmenu) 
        {

			$this->newmenu = $this->menuArbo->menuCharger($mainmenu, $this->newmenu,1,$this->leftmenu);

            /*
             * Menu AUTRES (Pour les menus du haut qui ne serait pas g�r�s)
             */

            if ($mainmenu && ! in_array($mainmenu,$this->overwritemenufor)) { $mainmenu=""; }
        
        }


        
        /**
         *  Si on est sur un cas g�r� de surcharge du menu, on ecrase celui par defaut
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
                    if ($contenu == 1) print '<div class="menu_fin"></div>';
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
            if ($contenu == 1) print '<div class="menu_fin"></div>';

        }

    }
    
	function recur($tab,$pere,$rang) 
    {
	  $leftmenu = $this->leftmenu;
	  //ballayage du tableau
	  for ($x=0;$x<count($tab);$x++) {
	
	    //si un �l�ment a pour p�re : $pere
	    if ($tab[$x][1]==$pere) {

	       //on affiche le menu
			
			if ($this->verifConstraint($tab[$x][0],$tab[$x][6],$tab[$x][7]) != 0)
		    {
			
				
			    if ($tab[$x][6])
				{
					
					$leftmenuConstraint = false;
					$str = "if(".$tab[$x][6].") \$leftmenuConstraint = true;";

					
					eval($str);
					if ($leftmenuConstraint == true)
					{
						//echo $tab[$x][0].'-'.$tab[$x][6].'-'.$leftmenu.'<br>';
						$this->newmenu->add_submenu(DOL_URL_ROOT.$tab[$x][2], $tab[$x][3],$rang-1,$tab[$x][4],$tab[$x][5]);
						$this->recur($tab,$tab[$x][0],$rang+1);
					}
				}
				else
				{
					//echo $tab[$x][0].'-'.$tab[$x][3].'-'.$leftmenu.'<br>';
					$this->newmenu->add_submenu(DOL_URL_ROOT.$tab[$x][2], $tab[$x][3],$rang-1,$tab[$x][4],$tab[$x][5]);	
					$this->recur($tab,$tab[$x][0],$rang+1);
				}
			
				//$this->newmenu->add(DOL_URL_ROOT.$tab[$x][2], $tab[$x][3],$rang-1,$tab[$x][4],$tab[$x][5]);

		       	/*et on recherche ses fils
		       	  en rappelant la fonction recur()
		       	(+ incr�mentation du d�callage)*/
		       	
	       
		    }
	    }			
	  }
	} 
	
    
    function verifConstraint($rowid,$mainmenu,$leftmenu)
   	{
   		global $user,$conf,$user;

   		$constraint = true;
   		
   		$sql = "SELECT c.rowid, c.action, mc.user FROM ".MAIN_DB_PREFIX."menu_constraint as c, ".MAIN_DB_PREFIX."menu_const as mc WHERE mc.fk_constraint = c.rowid AND (mc.user = 0 OR mc.user = 2 ) AND mc.fk_menu = '".$rowid."'";
		$result = $this->db->query($sql);
		
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;	
			while (($i < $num) && $constraint == true)
			{

				$obj = $this->db->fetch_object($result);
				$strconstraint = "if(!(".$obj->action.")) { \$constraint = false;}";
				
				eval($strconstraint);
				$i++;
			}
		}	

		return $constraint; 		
   	}
    
    function verifRights($strRights)
    {
    	
    	global $user;
    	
    	if ($strRights != "") 
    	{	
    		$rights = true;

	    	$tab_rights = explode(" || ",$strRights);
	    	$i = 0;
	    	while(($i < count($tab_rights)) && ($rights == true))
	    	{
				$str = "if(!(".$strRights.")) { \$rights = false;}";
				eval($str);
	    		$i++;
	    	}
    	}
    	else $rights = true;

    	
    	return $rights;
    }
    
    

}

?>
