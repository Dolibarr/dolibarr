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

/**	    \file       htdocs/includes/menus/barre_top/eldy.php
		\brief      Gestionnaire nommé eldy du menu du haut
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu à faire apparaitre dans la barre du haut
        \remarks    doivent être affichées par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionnée.
*/


/**     \class      MenuTop
	    \brief      Classe permettant la gestion du menu du haut Eldy
*/

class MenuTop {

    var $require_left=array("eldy");    // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $showhome=false;                // Faut-il afficher le menu Accueil par le main

    
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
        
        session_start();
        
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
    
        print '<a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Home").'</a>';


        // Members
        if ($conf->adherent->enabled)
        {
            $langs->load("members");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "adherent")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Members").'</a>';
        }
        
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
            if ($conf->service->enabled) { $chaine.="Services"; }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&leftmenu="'.($target?" target=$target":"").'>'.$chaine.'</a>';
        
        }

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
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=suppliers&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Suppliers").'</a>';
        }

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
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Commercial").'</a>';
        
        }
        
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
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Accountancy")."/".$langs->trans("Treasury").'</a>';
        
        }
        
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
            
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/comm/mailing/index.php?mainmenu=tools&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Tools").'</a>';
        }
        
        if ($conf->webcal->enabled)
        {
            $langs->load("other");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "webcalendar")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT."\/projet\/webcal",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/webcalendar\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<a '.$class.' href="'.DOL_URL_ROOT.'/projet/webcal.php?mainmenu=webcal&leftmenu="'.($target?" target=$target":"").'>'.$langs->trans("Calendar").'</a>';
        };
       
    }

}

?>
