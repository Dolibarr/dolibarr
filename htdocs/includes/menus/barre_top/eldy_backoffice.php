<?php
/* Copyright (C) 2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Regis Houssin        <regis.houssin@cap-networks.com>
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
	    \file       htdocs/includes/menus/barre_top/eldy_backoffice.php
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
        global $user,$conf,$langs,$dolibarr_main_db_name;;
        
        if (! session_id()) {
            session_name("DOLSESSID_".$dolibarr_main_db_name);
            session_start();    // En mode authentification PEAR, la session a déjà été ouverte
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

        print '<table class="tmenu"><tr class="tmenu">';

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
    
        print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Home").'</a></td>';


        // Third parties
        if (($conf->societe->enabled && $user->rights->societe->lire)
        	|| ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
        {
            $langs->load("companies");
            $langs->load("suppliers");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "companies")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("ThirdParties").'</a></td>';
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
                print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$chaine.'</a></td>';
            else
                print '<td class="tmenu"><font class="tmenudisabled">'.$chaine.'</font>';
        }

        // Suppliers
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
            
            if ($user->rights->fournisseur->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=suppliers&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Suppliers").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Suppliers").'</font>';
        
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
            
            if ($user->rights->commercial->main->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Commercial").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Commercial").'</font>';
        
        }

        // Financial
        if ($conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled
        	|| $conf->commande->enabled || $conf->facture->enabled)
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
            
            if ($user->rights->compta->resultat->lire || $user->rights->comptaexpert->plancompte->lire
            	|| $user->rights->commande->lire || $user->rights->facture->lire || $user->rights->banque->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("MenuFinancial").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("MenuFinancial").'</font>';
        }

        // Projects
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
            
            if ($user->rights->projet->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Projects").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Projects").'</font>';
        }

        // Tools
        if ($conf->mailing->enabled || $conf->export->enabled || $conf->bookmark->enabled)
        {
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
            
            if ($user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire)
            		//print '<a '.$class.' href="'.DOL_URL_ROOT.'/comm/mailing/index.php?mainmenu=tools&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a>';
            		//print '<a '.$class.' href="'.DOL_URL_ROOT.'/societe.php?mainmenu=tools&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a>';
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Tools").'</font>';
        }
        
        // Telephonie
        if ($conf->telephonie->enabled && $user->rights->telephonie->lire)
        {
            $class="";
            if (ereg("^".DOL_URL_ROOT."\/telephonie\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/telephonie/index.php?mainmenu=telephonie"'.($this->atarget?" target=$this->atarget":"").'>Telephonie</a></td>';
        }

        // Energie
        if ($conf->energie->enabled)
        {
            $langs->load("energy");
            $class="";
            if (ereg("^".DOL_URL_ROOT."\/energie\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/energie/index.php?mainmenu=energie"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Energy").'</a></td>';
        }
 
		// OSCommerce 1
        if ($conf->boutique->enabled)
        {
            $langs->load("shop");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "shop")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("OSCommerce").'</a></td>';
        }
        
        // OSCommerce 2
        if ($conf->oscommerce2->enabled)
        {
            $langs->load("shop");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "shop")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("OSCommerce").'</a></td>';
        }
        
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
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Members").'</a></td>';
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
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/webcal/webcal.php?mainmenu=webcal&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a></td>';
        }
        
        // Phenix
        if ($conf->phenix->enabled)
        {
            $langs->load("other");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "phenix")
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/phenix/phenix.php?mainmenu=phenix&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a></td>';
        }

        // Mantis
        if ($conf->mantis->enabled)
        {
            $langs->load("other");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "mantis")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT.".*\/mantis",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/mantis\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/mantis/mantis.php?mainmenu=mantis"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("BugTracker").'</a></td>';
        }
        
        print '</tr></table>';

    }

}

?>
