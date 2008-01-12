<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
	    \file       htdocs/includes/menus/barre_top/eldy_frontoffice.php
		\brief      Gestionnaire nomm� eldy du menu du haut
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entr�es de menu � faire apparaitre dans la barre du haut
        \remarks    doivent �tre affich�es par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut �ventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entr�e du menu qui est s�lectionn�e.
*/


/**
        \class      MenuTop
	    \brief      Classe permettant la gestion du menu du haut Eldy
*/

class MenuTop {

    var $require_left=array("eldy_frontoffice");    // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $atarget="";                                // Valeur du target a utiliser dans les liens

    
    /**
     *    \brief      Constructeur
     *    \param      db      Handler d'acces base de donnee
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
            session_start();
        }
        
        $user->getrights("");
        
        // On r�cup�re mainmenu
        if (isset($_GET["mainmenu"])) {
            // On sauve en session le menu principal choisi
            $mainmenu=$_GET["mainmenu"];
            $_SESSION["mainmenu"]=$mainmenu;
            $_SESSION["leftmenuopened"]="";
        } else {
            // On va le chercher en session si non defini par le lien    
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
        if ($conf->societe->enabled || $conf->fournisseur->enabled)
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
        
			if (($conf->societe->enabled && $user->rights->societe->lire)
				|| ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("ThirdParties").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("ThirdParties").'</font></td>';
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
        
/*
            if ($user->rights->produit->lire)
                print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$chaine.'</a></td>';
            else
                print '<td class="tmenu"><font class="tmenudisabled">'.$chaine.'</font></td>';
*/
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
        
/*
           if ($user->rights->fournisseur->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=suppliers&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Suppliers").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Suppliers").'</font></td>';
*/
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
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Commercial").'</font></td>';
        }

        // Financial
        if ($conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled
        	|| $conf->facture->enabled)
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
            	|| $user->rights->facture->lire || $user->rights->banque->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("MenuFinancial").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("MenuFinancial").'</font></td>';
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
        
/*
            if ($user->rights->projet->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Projects").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Projects").'</font></td>';
*/
        }

        // Tools
        if ($conf->mailing->enabled || $conf->export->enabled || $conf->bookmark->enabled
				|| $conf->global->MAIN_MODULE_IMPORT || $conf->global->MAIN_MODULE_DOMAIN)
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
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Tools").'</font></td>';
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
        
//            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/webcal/webcal.php?mainmenu=webcal&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a></td>';
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
        
//            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/phenix/phenix.php?mainmenu=phenix&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a></td>';
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
       
        
		// Affichage des menus personnalises
       	require_once(DOL_DOCUMENT_ROOT."/lib/menubase.class.php");

        $menuArbo = new Menubase($this->db,'eldy','top');
 		$tabMenu = $menuArbo->menutopCharger(1,$_SESSION['mainmenu'],'eldy');
        for($i=0;$i<count($tabMenu);$i++)
        {
        	if ($tabMenu[$i]['right'] == true)
        	{
        		print '<td class="tmenu"><a class="tmenu" href="'.DOL_URL_ROOT.$tabMenu[$i]['url'].'"'.($this->atarget?" target=$this->atarget":"").'>'.$tabMenu[$i]['titre'].'</a></td>';
        	}
        	else
        	{
        		print '<td class="tmenu"><font class="tmenudisabled">'.$tabMenu[$i]['titre'].'</font></td>';
        	}
      	
        }
        
        print '</tr></table>';

    }
    
}

?>
