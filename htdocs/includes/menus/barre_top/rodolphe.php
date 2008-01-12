<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/includes/menus/barre_top/default.php
        \brief      Gestionnaire par d�faut du menu du haut
        
        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrees de menu e faire apparaitre dans la barre du haut
        \remarks    doivent etre affichees par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut eventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entree du menu qui est selectionnee.
*/


/**
        \class      MenuTop
	    \brief      Classe permettant la gestion par defaut du menu du haut
*/

class MenuTop {

    var $require_left=array();  // Si doit etre en phase avec un gestionnaire de menu gauche particulier
    var $atarget="";            // Valeur du target a utiliser dans les liens
    
    /**
     *    \brief      Constructeur
     *    \param      db      Handler d'acces base de donnee
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
    
        global $user,$conf,$langs,$dolibarr_main_db_name;
    
        if (! session_id()) {
            session_name("DOLSESSID_".$dolibarr_main_db_name);
            session_start();
        }
    
        $user->getrights("");
    
        // On recupere mainmenu
        if (isset($_GET["mainmenu"]))
        {
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
        $id="";

        if ($_GET["mainmenu"] == "home" || ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "home"))
        {
            $id="sel";
        }

        if (! ereg("^".DOL_URL_ROOT."\/(adherents|comm|commande|compta|contrat|product|fichinter|fourn|telephonie|energie|boutique|oscommerce_ws|projet)\/",$_SERVER["PHP_SELF"])) {
            $id="sel";
        }
        else {
            $id="";
        }
        print '<td class="tmenu"><a class="tmenu" '.($id?'id="'.$id.'" ':'').'href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Home").'</a></td>';

        // Adherent
        if ($conf->adherent->enabled && $user->rights->adherent->lire)
        {
            $langs->load("members");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "members")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT."\/adherents\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Members").'</a></td>';
        }

        // Commercial
        if ($conf->commercial->enabled && $user->rights->commercial->main->lire)
        {
            $langs->load("commercial");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "commercial")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT."\/(comm|commande|contrat)\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Commercial").'</a></td>';

        }

        // Compta/treso (sert pour banque, tva, entites a facturer...)
        if ($conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled
        	|| $conf->commande->enabled || $conf->facture->enabled)
        {
//            if ($user->rights->compta->general->lire || $user->rights->comptaexpert->general->lire)
//            {
                $langs->load("compta");

                $class="";
                if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "compta")
                {
                    $class='class="tmenu" id="sel"';
                }
                elseif (ereg("^".DOL_URL_ROOT."\/compta\/",$_SERVER["PHP_SELF"]))
                {
                    $class='class="tmenu" id="sel"';
                }
                else
                {
                    $class = 'class="tmenu"';
                }

                print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("MenuFinancial").'</a></td>';
//            }
        }

        // Projects
        if ($conf->projet->enabled && $user->rights->projet->lire)
        {
            $langs->load("projects");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "product")
            {
                $class='class="tmenu" id="sel"';
            }
            if (ereg("^".DOL_URL_ROOT."\/projet\/[^w]",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            $chaine.=$langs->trans("Projects");
            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project">'.$chaine.'</a></td>';
        }

        // Produit/service
        if (($conf->produit->enabled || $conf->service->enabled)  && $user->rights->produit->lire)
        {
            $langs->load("products");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "product")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT."\/product\/",$_SERVER["PHP_SELF"]))
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

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products"'.($this->atarget?" target=$this->atarget":"").'>'.$chaine.'</a></td>';

        }

        // Supplier
        if ($conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire)
        {
            $langs->load("suppliers");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "suppliers")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT."\/fourn\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=suppliers"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Suppliers").'</a></td>';
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
            if (ereg("^".DOL_URL_ROOT."\/boutique\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=boutique"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("OSCommerce").'</a></td>';
        }

        // OSCommerce 2
        if ($conf->oscommerce2->enabled)
        {
            $langs->load("shop");
            $class="";
            if (ereg("^".DOL_URL_ROOT."\/oscommerce_ws\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/oscommerce_ws/index.php?mainmenu=oscommerce2"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("OSCommerce").'</a></td>';
        }

        // Webcal
        if ($conf->webcal->enabled)
        {
            $langs->load("other");

            $class="";
            if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "webcalendar")
            {
                $class='class="tmenu" id="sel"';
            }
            elseif (ereg("^".DOL_URL_ROOT.".*\/webcal",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/webcalendar\/",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/webcal/webcal.php?mainmenu=webcal"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a></td>';
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
            elseif (ereg("^".DOL_URL_ROOT.".*\/phenix",$_SERVER["PHP_SELF"]))
            {
                $class='class="tmenu" id="sel"';
            }
            else
            {
                $class = 'class="tmenu"';
            }

            print '<td class="tmenu"><a '.$class.' href="'.DOL_URL_ROOT.'/phenix/phenix.php?mainmenu=phenix"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Calendar").'</a></td>';
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

        $menuArbo = new Menubase($this->db,'rodolphe','top');
 		$tabMenu = $menuArbo->menutopCharger(0,$_SESSION['mainmenu']);
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