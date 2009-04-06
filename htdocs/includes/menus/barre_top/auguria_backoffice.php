<?php
/* Copyright (C) 2007      Patrick Raguin        <patrick.raguin@gmail.com>
 * Copyright (C) 2009      Regis Houssin         <regis@dolibarr.fr>
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
	    \file       htdocs/includes/menus/barre_top/auguria_backoffice.php
		\brief      Gestionnaire nomme Auguria du menu du haut
		\version    $$Id$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrees de menu a faire apparaitre dans la barre du haut
        \remarks    doivent etre affichees par <a class="tmenu" href="...?mainmenu=...">...</a>
		\remarks    ou si menu selectionne <a class="tmenusel" href="...?mainmenu=...">...</a>
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
     *    \param      db      Handler d'acc�s base de donn�e
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
    	require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");

      global $user,$conf,$langs,$dolibarr_main_db_name;;


      // On sauve en session le menu principal choisi
      if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
      if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
      $_SESSION["leftmenuopened"]="";
      
      $menuArbo = new Menubase($this->db,'auguria','top');
      $tabMenu = $menuArbo->menuTopCharger(0,$_SESSION['mainmenu'], 'auguria');
      
      print '<ul>';
      
      for($i=0; $i<count($tabMenu); $i++)
      {
      	if ($tabMenu[$i]['enabled'] == true)
      	{
      		if ($tabMenu[$i]['right'] == true)
      		{
      			// Define url
      			if (eregi($tabMenu[$i]['url'],"^(http:\/\/|https:\/\/)"))
	        	{
	        		$url = $tabMenu[$i]['url'];
	        	}
	        	else
	        	{
	        		$url=DOL_URL_ROOT.$tabMenu[$i]['url'];
	        		if (! eregi('\?',DOL_URL_ROOT.$tabMenu[$i]['url'])) $url.='?';
	        		else $url.='&';
	        		$url.='mainmenu='.$tabMenu[$i]['mainmenu'].'&leftmenu=';
	        		$url.="&idmenu=".$tabMenu[$i]['rowid'];
	        	}
      			if (! empty($_GET["idmenu"]) && $tabMenu[$i]['rowid'] == $_GET["idmenu"]) $class='class="tmenusel"';
      			else  $class='class="tmenu"';
      			// Define idsel
      			$idsel='';
      			print '<li><a '.$class.' '.$idsel.'href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":"").'>'.$tabMenu[$i]['titre'].'</a></li>';
      		}
      		else
	        {
	        	print '<li><div class="tmenudisabled">'.$tabMenu[$i]['titre'].'</div></li>';
	        }
	      }
	    }
	    
	    print '</ul>';
    }

}
?>
