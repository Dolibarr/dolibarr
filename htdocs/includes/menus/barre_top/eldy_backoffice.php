<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Regis Houssin        <regis@dolibarr.fr>
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
	    \file       htdocs/includes/menus/barre_top/eldy_backoffice.php
		\brief      Gestionnaire nomm� eldy du menu du haut
		\version    $Id$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrees de menu a faire apparaitre dans la barre du haut
        \remarks    doivent etre affichees par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut eventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entree du menu qui est selectionnee.
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
        
        // On sauve en session le menu principal choisi
		if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
		if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
        $_SESSION["leftmenuopened"]="";

		
        print '<table class="tmenu"><tr class="tmenu">';

        // Home
        $class="";
        if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "home")
        {
            $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                print '<td class="tmenu"><font class="tmenudisabled">'.$chaine.'</font></td>';
        }

        // Suppliers
/*
        if ($conf->fournisseur->enabled)
        {
            $langs->load("suppliers");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "suppliers")
            {
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
            }
            else
            {
                $class = 'class="tmenu"';
            }
            
            if ($user->rights->fournisseur->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=suppliers&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Suppliers").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Suppliers").'</font></td>';
        }
*/

        // Commercial
        /*$showcommercial=0;
        if ($conf->societe->enabled)   $showcommercial=1;
        if ($conf->propal->enabled)    $showcommercial=1;
        if ($conf->commande->enabled)  $showcommercial=1;
        if ($conf->contrat->enabled)   $showcommercial=1;
        if ($conf->fichinter->enabled) $showcommercial=1;
        if ($showcommercial)*/
		if ($conf->commercial->enabled)
        {
	        $langs->load("commercial");
	    
	        $class="";
	        if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "commercial")
	        {
	            $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
	        }
	        else
	        {
	            $class = 'class="tmenu"';
	        }
        
        	print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Commercial").'</a></td>';
        }

        // Financial
        if ($conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled
        	|| $conf->facture->enabled)
        {
            $langs->load("compta");
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "accountancy")
            {
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
            }
            else
            {
                $class = 'class="tmenu"';
            }
            
            if ($user->rights->projet->lire)
            		print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Projects").'</a></td>';
            else
            		print '<td class="tmenu"><font class="tmenudisabled">'.$langs->trans("Projects").'</font></td>';
        }

        // Tools
        if ($conf->mailing->enabled || $conf->export->enabled || $conf->bookmark->enabled
				|| $conf->global->MAIN_MODULE_IMPORT || $conf->global->MAIN_MODULE_DOMAIN)
        {
            $langs->load("other");
            
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "tools")
            {
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
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
            // $langs->load("members"); Added in main file to increase
        
            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "members")
            {
                $class='class="tmenu" id="sel"'; $_SESSION['idmenu']='';
            }
            else
            {
                $class = 'class="tmenu"';
            }
        
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("MenuMembers").'</a></td>';
        }


		// Affichage des menus personnalises
       	require_once(DOL_DOCUMENT_ROOT."/lib/menubase.class.php");

        $menuArbo = new Menubase($this->db,'eldy','top');
 		$tabMenu = $menuArbo->menutopCharger(0,$_SESSION['mainmenu'],'eldy');
        for($i=0; $i<count($tabMenu); $i++)
        {
        	if ($tabMenu[$i]['right'] == true)
        	{
				$url=DOL_URL_ROOT.$tabMenu[$i]['url'];
				if (! eregi('\?',DOL_URL_ROOT.$tabMenu[$i]['url'])) $url.='?';
				else $url.='&';
				$url.='mainmenu='.$tabMenu[$i]['mainmenu'].'&leftmenu=';
				$url.="&idmenu=".$tabMenu[$i]['rowid'];
				// Define idsel
				if (! empty($_SESSION['idmenu']) && $tabMenu[$i]['rowid'] == $_SESSION['idmenu']) $idsel='id="sel" ';
				else $idsel='';
				print '<td class="tmenu"><a class="tmenu" '.$idsel.'href="'.$url.'"'.($this->atarget?" target=$this->atarget":"").'>';
				print $tabMenu[$i]['titre'];
				print '</a></td>';
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
