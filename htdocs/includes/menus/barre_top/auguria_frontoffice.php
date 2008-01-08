<?php
/* Copyright (C) 2007      Patrick Raguin        <patrick.raguin@gmail.com>
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
	    \file       htdocs/includes/menus/barre_top/auguria_backoffice.php
		\brief      Gestionnaire nommé Auguria du menu du haut
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu à faire apparaitre dans la barre du haut
        \remarks    doivent être affichées par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionnée.
*/


/**
        \class      MenuTop
	    \brief      Classe permettant la gestion du menu du haut Auguria
*/

class MenuTop {

    var $require_left=array("auguria_backoffice");     // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $atarget="";                                // Valeur du target a utiliser dans les liens

    
    /**
     *    \brief      Constructeur
     *    \param      db      Handler d'accès base de donnée
     */
    function MenuTop($db)
    {
        global $langs;
        $this->db=$db;
        
        $langs->setTransFromTab("Company",$langs->trans("ThirdParty"));
        $langs->setTransFromTab("NewCompany",$langs->trans("NewThirdParty"));
    }
    
    
    /**
     *    \brief      Affiche le menu
     */
    function showmenu()
    {
       	require_once(DOL_DOCUMENT_ROOT."/admin/menus/module_menudb.php");
       
        global $user,$conf,$langs,$dolibarr_main_db_name;;
        

        if (! session_id()) {
            session_name("DOLSESSID_".$dolibarr_main_db_name);
            session_start();
        }
        
        $user->getrights("");
        
        // On récupère mainmenu
        if (isset($_GET["mainmenu"])) {
            // On sauve en session le menu principal choisi
            $mainmenu=$_GET["mainmenu"];
            $_SESSION["mainmenu"]=$mainmenu;
            $_SESSION["leftmenuopened"]="";
        } else {
            // On va le chercher en session si non défini par le lien    
            $mainmenu=$_SESSION["mainmenu"];
        }

        
        $menuArbo = new menudb($this->db,'auguria','top');
 		$tabMenu = $menuArbo->menutopCharger(1,$_SESSION['mainmenu']);
        
        print '<ul>';
        
        for($i=0;$i<count($tabMenu);$i++)
        {
        	if ($tabMenu[$i]['right'] == true)
        	{
        		
        		print '<li><a '.$tabMenu[$i]['class'].' href="'.DOL_URL_ROOT.$tabMenu[$i]['url'].'"'.($this->atarget?" target=$tabMenu[$i]['atarget']":"").'>'.$tabMenu[$i]['titre'].'</a></li>';
        	}
        	else
        	{
        		print '<li><div class="tmenudisabled">'.$tabMenu[$i]['titre'].'</div></li>';
        	}
      	
        }
 
        print '</ul>';

    }
    
 
}

?>
