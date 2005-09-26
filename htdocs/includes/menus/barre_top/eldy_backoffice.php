<?php
/* Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	    \file       htdocs/includes/menus/barre_top/eldy.php
		\brief      Gestionnaire nommé eldy du menu du haut
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu à faire apparaitre dans la barre du haut
        \remarks    doivent être affichées par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionnée.
*/


/**
        \class      MenuTop
	    \brief      Classe permettant la gestion du menu du haut Eldy
*/

class MenuTop {

    var $require_left=array("eldy_backoffice");     // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $atarget="";                                // Valeur du target a utiliser dans les liens

    
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
        global $user, $conf, $langs;
        
        if (! session_id()) session_start();    // En mode authentification PEAR, la session a déjà été ouverte
        
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


        // Home
        $class="";
        if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "home")
        {
            $class='class="tmenu" id="sel"';
        }
        else
        {
            $class = 'class="tmenu"';
        }
    
        print '<a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Home").'</a>';


        // Members
        if ($conf->adherent->enabled)
        {
            $langs->load("members");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "members")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Members").'</a>';
        }
        
        // Products-Services
        if ($conf->produit->enabled || $conf->service->enabled)
        {
            $langs->load("products");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "products")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
            $chaine="";
            if ($conf->produit->enabled) { $chaine.=$langs->trans("Products"); }
            if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
            if ($conf->service->enabled) { $chaine.=$langs->trans("Services"); }
        
            if ($user->rights->produit->lire)
                print '<a '.$class.' href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$chaine.'</a>';
            else
                print '<font class="tmenudisabled">'.$chaine.'</font>';
        }

        // Supplier
        if ($conf->fournisseur->enabled)
        {
            $langs->load("suppliers");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "suppliers")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=suppliers&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Suppliers").'</a>';
        }

        // Commercial
        if ($conf->commercial->enabled)
        {
            $langs->load("commercial");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "commercial")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Commercial").'</a>';
        
        }
        
        // Compta
        if ($conf->compta->enabled || $conf->banque->enabled || $conf->caisse->enabled)
        {
            $langs->load("compta");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "accountancy")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Accountancy")."/".$langs->trans("Treasury").'</a>';
        }

        // Projets
        if ($conf->projet->enabled)
        {
            $langs->load("projects");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "project")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Projects").'</a>';
        }

        // Tools
        if (1 == 1) {
            $langs->load("other");
            
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "tools")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
            
            //print '<a '.$class.' href="'.DOL_URL_ROOT.'/comm/mailing/index.php?mainmenu=tools&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a>';
            //print '<a '.$class.' href="'.DOL_URL_ROOT.'/societe.php?mainmenu=tools&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a>';
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a>';
        }
        
        // Webcal
        if ($conf->webcal->enabled)
        {
            $langs->load("other");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "webcal")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/projet/webcal.php?mainmenu=webcal&leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a>';
        }
       
    }

}

?>
