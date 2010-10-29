<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/includes/menus/standard/eldy.lib.php
 *  \brief		Library for file eldy menus
 *  \version	$Id$
 */


/**
 * Core function to output top menu eldy
 *
 * @param $db
 * @param $atarget
 * @param $type_user     0=Internal,1=External,2=All
 */
function print_eldy_menu($db,$atarget,$type_user)
{
	global $user,$conf,$langs,$dolibarr_main_db_name;

	// On sauve en session le menu principal choisi
	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
	$_SESSION["leftmenuopened"]="";

	$id='mainmenu';

	print_start_menu_array();

	// Home
	$classname="";
	if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "home")
	{
		$classname='class="tmenusel"'; $_SESSION['idmenu']='';
	}
	else
	{
		$classname = 'class="tmenu"';
	}
	$idsel='home';
	print_start_menu_entry($idsel);
	print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
	print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
	print '</a>';
	print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
	print_text_menu_entry($langs->trans("Home"));
	print '</a>';
	print_end_menu_entry();


	// Third parties
	if ($conf->societe->enabled || $conf->fournisseur->enabled)
	{
		$langs->load("companies");
		$langs->load("suppliers");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "companies")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='companies';
		if (($conf->societe->enabled && $user->rights->societe->lire)
		|| ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("ThirdParties"));
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
				print_text_menu_entry($langs->trans("ThirdParties"));
				print '</a>';
				print_end_menu_entry();
			}
		}
	}


	// Products-Services
	if ($conf->product->enabled || $conf->service->enabled)
	{
		$langs->load("products");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "products")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}
		$chaine="";
		if ($conf->product->enabled) { $chaine.=$langs->trans("Products"); }
		if ($conf->product->enabled && $conf->service->enabled) { $chaine.="/"; }
		if ($conf->service->enabled) { $chaine.=$langs->trans("Services"); }

		$idsel='products';
		if ($user->rights->produit->lire || $user->rights->service->lire)
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage"  id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($chaine);
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
				print_text_menu_entry($chaine);
				print '</a>';
				print_end_menu_entry();
			}
		}
	}

	// Commercial
	if ($conf->societe->enabled)
	{
		$langs->load("commercial");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "commercial")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='commercial';
		if($user->rights->societe->lire || $user->rights->societe->contact->lire)
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="'.$id.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("Commercial"));
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
				print print_text_menu_entry($langs->trans("Commercial"));
				print '</a>';
				print_end_menu_entry();
			}
		}
	}

	// Financial
	if ($conf->compta->enabled || $conf->accounting->enabled
	|| $conf->facture->enabled || $conf->deplacement->enabled)
	{
		$langs->load("compta");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "accountancy")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='accountancy';
		if ($user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire
		|| $user->rights->facture->lire || $user->rights->banque->lire)
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("MenuFinancial"));
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
				print_text_menu_entry($langs->trans("MenuFinancial"));
				print '</a>';
				print_end_menu_entry();
			}
		}
	}

    // Bank
    if ($conf->banque->enabled || $conf->prelevement->enabled)
    {
        $langs->load("compta");
        $langs->load("banks");

        $classname="";
        if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "bank")
        {
            $classname='class="tmenusel"'; $_SESSION['idmenu']='';
        }
        else
        {
            $classname = 'class="tmenu"';
        }

        $idsel='bank';
        if ($user->rights->banque->lire)
        {
            print_start_menu_entry($idsel);
            print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/compta/bank/index.php?mainmenu=bank&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
            print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
            print '</a>';
            print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/compta/bank/index.php?mainmenu=bank&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
            print_text_menu_entry($langs->trans("MenuBankCash"));
            print '</a>';
            print_end_menu_entry();
        }
        else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
        {
            if (! $type_user)
            {
                print_start_menu_entry($idsel);
                print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
                print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
                print_text_menu_entry($langs->trans("MenuBankCash"));
                print '</a>';
                print_end_menu_entry();
            }
        }
    }

	// Projects
	if ($conf->projet->enabled)
	{
		$langs->load("projects");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "project")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='project';
		if ($user->rights->projet->lire)
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("Projects"));
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
				print_text_menu_entry($langs->trans("Projects"));
				print '</a>';
				print_end_menu_entry();
			}
		}
	}

	// Tools
	if ($conf->mailing->enabled || $conf->export->enabled || $conf->import->enabled || $conf->global->MAIN_MODULE_DOMAIN)
	{
		$langs->load("other");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "tools")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='tools';
		if ($user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire || $user->rights->import->run)
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("Tools"));
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">';
				print_text_menu_entry($langs->trans("Tools"));
				print '</a>';
				print_end_menu_entry();
			}
		}
	}

	// OSCommerce 1
	if (! empty($conf->boutique->enabled))
	{
		$langs->load("shop");

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "shop")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='shop';
		print_start_menu_entry($idsel);
		print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
		print_text_menu_entry($langs->trans("OSCommerce"));
		print '</a>';
		print_end_menu_entry();
	}

	// Members
	if ($conf->adherent->enabled)
	{
		// $langs->load("members"); Added in main file

		$classname="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "members")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='members';
		if ($user->rights->adherent->lire)
		{
			print_start_menu_entry($idsel);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("MenuMembers"));
			print '</a>';
			print_end_menu_entry();
		}
		else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
		{
			if (! $type_user)
			{
				print_start_menu_entry($idsel);
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
				print_text_menu_entry($langs->trans("MenuMembers"));
				print '</a>';
				print_end_menu_entry();
			}
		}
	}


	// Show personalized menus
	require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

	$menuArbo = new Menubase($db,'eldy','top');

	$tabMenu = $menuArbo->menuTopCharger($type_user,$_SESSION['mainmenu'],'eldy');

	for($i=0; $i<count($tabMenu); $i++)
	{
		if ($tabMenu[$i]['enabled'] == true)
		{
			//var_dump($tabMenu[$i]);

			$idsel=(empty($tabMenu[$i]['mainmenu'])?'none':$tabMenu[$i]['mainmenu']);
			if ($tabMenu[$i]['right'] == true)	// Is allowed
			{
				if (preg_match("/^(http:\/\/|https:\/\/)/i",$tabMenu[$i]['url']))
				{
					$url = $tabMenu[$i]['url'];
				}
				else
				{
					$url=DOL_URL_ROOT.$tabMenu[$i]['url'];
					if (! preg_match('/\?/',$url)) $url.='?';
					else $url.='&';
					if (! preg_match('/mainmenu/i',$url) || ! preg_match('/leftmenu/i',$url))
					{
						$url.='mainmenu='.$tabMenu[$i]['mainmenu'].'&leftmenu=&';
					}
					$url.="idmenu=".$tabMenu[$i]['rowid'];
				}

				// Define the class (top menu selected or not)
				if (! empty($_SESSION['idmenu']) && $tabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname='class="tmenusel"';
				else if (! empty($_SESSION['mainmenu']) && $tabMenu[$i]['mainmenu'] == $_SESSION['mainmenu']) $classname='class="tmenusel"';
				else $classname='class="tmenu"';

				print_start_menu_entry($idsel);
				print '<a class="tmenuimage" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '</a>';
				print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print_text_menu_entry($tabMenu[$i]['titre']);
				print '</a>';
				print_end_menu_entry();
			}
			else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
			{
				if (! $type_user)
				{
					print_start_menu_entry($idsel);
					print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
					print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">';
					print_text_menu_entry($tabMenu[$i]['titre']);
					print '</a>';
					print_end_menu_entry();
				}
			}
		}
	}

	print_end_menu_array();
}



function print_start_menu_array()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';
	else print '<ul class="tmenu">';
}

function print_start_menu_entry($idsel)
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
	else print '<li class="tmenu" id="mainmenutd_'.$idsel.'">';
}

function print_text_menu_entry($text)
{
	global $conf;
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

function print_end_menu_entry()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</td>';
	else print '</li>';
	print "\n";
}

function print_end_menu_array()
{
	global $conf;
	if (preg_match('/bluelagoon|eldy|freelug|rodolphe|yellow|dev/',$conf->css)) print '</tr></table>';
	else print '</ul>';
	print "\n";
}



/**
 * Core function to output left menu eldy
 *
 * @param      db                  Database handler
 * @param      menu_array_before   Table of menu entries to show before entries of menu handler
 * @param      menu_array_after    Table of menu entries to show after entries of menu handler
 */
function print_left_eldy_menu($db,$menu_array_before,$menu_array_after)
{
    global $user,$conf,$langs,$dolibarr_main_db_name,$mysoc;

    // Read mainmenu and leftmenu that define which menu to show
    if (isset($_GET["mainmenu"]))
    {
        // On sauve en session le menu principal choisi
        $mainmenu=$_GET["mainmenu"];
        $_SESSION["mainmenu"]=$mainmenu;
        $_SESSION["leftmenuopened"]="";
    }
    else
    {
        // On va le chercher en session si non defini par le lien
        $mainmenu=isset($_SESSION["mainmenu"])?$_SESSION["mainmenu"]:'';
    }

    if (isset($_GET["leftmenu"]))
    {
        // On sauve en session le menu principal choisi
        $leftmenu=$_GET["leftmenu"];
        $_SESSION["leftmenu"]=$leftmenu;
        if ($_SESSION["leftmenuopened"]==$leftmenu)
        {
            //$leftmenu="";
            $_SESSION["leftmenuopened"]="";
        }
        else
        {
            $_SESSION["leftmenuopened"]=$leftmenu;
        }
    } else {
        // On va le chercher en session si non defini par le lien
        $leftmenu=isset($_SESSION["leftmenu"])?$_SESSION["leftmenu"]:'';
    }

    $newmenu = new Menu();

    // Show logo company
    if (! empty($conf->global->MAIN_SHOW_LOGO))
    {
        $mysoc->logo_mini=$conf->global->MAIN_INFO_SOCIETE_LOGO_MINI;
        if (! empty($mysoc->logo_mini) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_mini))
        {
            $urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_mini);
            print "\n".'<!-- Show logo on menu -->'."\n";
            print '<div class="blockvmenuimpair">'."\n";
            print '<center><img title="'.$title.'" src="'.$urllogo.'"></center>'."\n";
            print '</div>'."\n";
        }
    }

    /**
     * On definit newmenu en fonction de mainmenu et leftmenu
     * ------------------------------------------------------
     */
    if ($mainmenu)
    {

        /*
         * Menu HOME
         */
        if ($mainmenu == 'home')
        {
            $langs->load("users");

            if ($user->admin)
            {
                $langs->load("admin");
                $langs->load("help");

                $newmenu->add(DOL_URL_ROOT."/admin/index.php?leftmenu=setup", $langs->trans("Setup"));
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/company.php", $langs->trans("MenuCompanySetup"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/modules.php", $langs->trans("Modules"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/menus.php", $langs->trans("Menus"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/ihm.php", $langs->trans("GUISetup"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/boxes.php", $langs->trans("Boxes"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/delais.php",$langs->trans("Alerts"),1);

                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/perms.php", $langs->trans("Security"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/limits.php", $langs->trans("MenuLimits"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/mails.php", $langs->trans("Emails"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/dict.php", $langs->trans("DictionnarySetup"),1);
                if ($leftmenu=="setup") $newmenu->add(DOL_URL_ROOT."/admin/const.php", $langs->trans("OtherSetup"),1);

                $newmenu->add(DOL_URL_ROOT."/admin/system/index.php?leftmenu=system", $langs->trans("SystemInfo"));
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/dolibarr.php", $langs->trans("Dolibarr"),1);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/constall.php", $langs->trans("AllParameters"),2);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/modules.php", $langs->trans("Modules"),2);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/triggers.php", $langs->trans("Triggers"),2);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/about.php", $langs->trans("About"),2);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/os.php", $langs->trans("OS"),1);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/web.php", $langs->trans("WebServer"),1);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/phpinfo.php", $langs->trans("Php"),1);
                //if ($leftmenu=="system" && function_exists('xdebug_is_enabled')) $newmenu->add(DOL_URL_ROOT."/admin/system/xdebug.php", $langs->trans("XDebug"),1);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/database.php", $langs->trans("Database"),1);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/database-tables.php", $langs->trans("Tables"),2);
                if ($leftmenu=="system") $newmenu->add(DOL_URL_ROOT."/admin/system/database-tables-contraintes.php", $langs->trans("Constraints"),2);

                $newmenu->add(DOL_URL_ROOT."/admin/tools/index.php?leftmenu=admintools", $langs->trans("SystemTools"));
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/admin/tools/dolibarr_export.php", $langs->trans("Backup"),1);
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/admin/tools/dolibarr_import.php", $langs->trans("Restore"),1);
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/admin/tools/update.php", $langs->trans("MenuUpgrade"),1);
                if ($leftmenu=="admintools" && function_exists('eaccelerator_info')) $newmenu->add(DOL_URL_ROOT."/admin/tools/eaccelerator.php", $langs->trans("EAccelerator"),1);
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/admin/tools/listevents.php", $langs->trans("Audit"),1);
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/admin/tools/listsessions.php", $langs->trans("Sessions"),1);
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/admin/tools/purge.php", $langs->trans("Purge"),1);
                if ($leftmenu=="admintools") $newmenu->add(DOL_URL_ROOT."/support/index.php", $langs->trans("HelpCenter"),1,1,'targethelp');
            }

            $newmenu->add(DOL_URL_ROOT."/user/home.php?leftmenu=users", $langs->trans("MenuUsersAndGroups"));
            if ($leftmenu=="users") $newmenu->add(DOL_URL_ROOT."/user/index.php", $langs->trans("Users"), 1, $user->rights->user->user->lire || $user->admin);
            if ($leftmenu=="users") $newmenu->add(DOL_URL_ROOT."/user/fiche.php?action=create", $langs->trans("NewUser"),2, $user->rights->user->user->creer || $user->admin);
            if ($leftmenu=="users") $newmenu->add(DOL_URL_ROOT."/user/group/index.php", $langs->trans("Groups"), 1, $user->rights->user->group->read || $user->admin);
            if ($leftmenu=="users") $newmenu->add(DOL_URL_ROOT."/user/group/fiche.php?action=create", $langs->trans("NewGroup"), 2, $user->rights->user->group->write || $user->admin);
        }


        /*
         * Menu TIERS
         */
        if ($mainmenu == 'companies')
        {
            // Societes
            if ($conf->societe->enabled)
            {
                $langs->load("companies");
                $newmenu->add(DOL_URL_ROOT."/societe/societe.php", $langs->trans("ThirdParty"), 0, $user->rights->societe->lire);

                if ($user->rights->societe->creer)
                {
                    $newmenu->add(DOL_URL_ROOT."/societe/soc.php?action=create", $langs->trans("MenuNewThirdParty"),1);
                    if (! $conf->use_javascript_ajax) $newmenu->add(DOL_URL_ROOT."/societe/soc.php?action=create&amp;private=1",$langs->trans("MenuNewPrivateIndividual"),1);
                }

                if(is_dir("societe/groupe"))
                {
                    $newmenu->add(DOL_URL_ROOT."/societe/groupe/index.php", $langs->trans("MenuSocGroup"),1);
                }
            }

            // Prospects
            if ($conf->societe->enabled)
            {
                $langs->load("commercial");
                $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?leftmenu=prospects", $langs->trans("ListProspectsShort"), 1, $user->rights->societe->lire);

                $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p", $langs->trans("MenuNewProspect"), 2, $user->rights->societe->creer);
                //$newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=p", $langs->trans("Contacts"), 2, $user->rights->societe->contact->lire);
            }

            // Clients
            if ($conf->societe->enabled)
            {
                $langs->load("commercial");
                $newmenu->add(DOL_URL_ROOT."/comm/clients.php?leftmenu=customers", $langs->trans("ListCustomersShort"), 1, $user->rights->societe->lire);

                $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 2, $user->rights->societe->creer);
                //$newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"), 2, $user->rights->societe->contact->lire);
            }

            // Fournisseurs
            if ($conf->societe->enabled && $conf->fournisseur->enabled)
            {
                $langs->load("suppliers");
                $newmenu->add(DOL_URL_ROOT."/fourn/liste.php?leftmenu=suppliers", $langs->trans("ListSuppliersShort"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);

                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("MenuNewSupplier"), 2, $user->rights->societe->creer && $user->rights->fournisseur->lire);
                }
                //$newmenu->add(DOL_URL_ROOT."/fourn/liste.php?leftmenu=suppliers", $langs->trans("List"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire);
                //$newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire && $user->rights->societe->contact->lire);
            }

            // Contacts
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("Contacts"), 0, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/fiche.php?leftmenu=contacts&amp;action=create", $langs->trans("NewContact"), 1, $user->rights->societe->contact->creer);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("List"), 1, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=o", $langs->trans("Others"), 2, $user->rights->societe->contact->lire);
            //$newmenu->add(DOL_URL_ROOT."/contact/index.php?userid=$user->id", $langs->trans("MyContacts"), 1, $user->rights->societe->contact->lire);

            // Categories
            if ($conf->categorie->enabled)
            {
                $langs->load("categories");
                // Categories prospects/customers
                $newmenu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=2", $langs->trans("CustomersProspectsCategoriesShort"), 0, $user->rights->categorie->lire);
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=2", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                }
                // Categories suppliers
                if ($conf->fournisseur->enabled)
                {
                    $newmenu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=1", $langs->trans("SuppliersCategoriesShort"), 0, $user->rights->categorie->lire);
                    if ($user->societe_id == 0)
                    {
                        $newmenu->add(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=1", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                    }
                }
                //if ($leftmenu=="cat") $newmenu->add(DOL_URL_ROOT."/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
            }

        }

        /*
         * Menu COMMERCIAL
         */
        if ($mainmenu == 'commercial')
        {
            $langs->load("companies");

            // Suppliers
            if ($conf->fournisseur->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"), 0, $user->rights->societe->lire);

                $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f", $langs->trans("MenuNewSupplier"), 1, $user->rights->societe->creer);
                $newmenu->add(DOL_URL_ROOT."/fourn/liste.php?leftmenu=customers", $langs->trans("List"), 1, $user->rights->societe->lire);
                $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f", $langs->trans("Contacts"), 1, $user->rights->societe->contact->lire);
            }

            // Prospects
            $newmenu->add(DOL_URL_ROOT."/comm/prospect/index.php?leftmenu=prospects", $langs->trans("Prospects"), 0, $user->rights->societe->lire);

            $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p", $langs->trans("MenuNewProspect"), 1, $user->rights->societe->creer);
            $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?leftmenu=prospects", $langs->trans("List"), 1, $user->rights->societe->contact->lire);

            if ($leftmenu=="prospects") $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=-1", $langs->trans("LastProspectDoNotContact"), 2, $user->rights->societe->lire);
            if ($leftmenu=="prospects") $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=0", $langs->trans("LastProspectNeverContacted"), 2, $user->rights->societe->lire);
            if ($leftmenu=="prospects") $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=1", $langs->trans("LastProspectToContact"), 2, $user->rights->societe->lire);
            if ($leftmenu=="prospects") $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=2", $langs->trans("LastProspectContactInProcess"), 2, $user->rights->societe->lire);
            if ($leftmenu=="prospects") $newmenu->add(DOL_URL_ROOT."/comm/prospect/prospects.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=3", $langs->trans("LastProspectContactDone"), 2, $user->rights->societe->lire);

            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=prospects&amp;type=p", $langs->trans("Contacts"), 1, $user->rights->societe->contact->lire);

            // Customers
            $newmenu->add(DOL_URL_ROOT."/comm/index.php?leftmenu=customers", $langs->trans("Customers"), 0, $user->rights->societe->lire);

            $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 1, $user->rights->societe->creer);
            $newmenu->add(DOL_URL_ROOT."/comm/clients.php?leftmenu=customers", $langs->trans("List"), 1, $user->rights->societe->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"), 1, $user->rights->societe->contact->lire);

            // Contacts
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("Contacts"), 0, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/fiche.php?leftmenu=contacts&amp;action=create", $langs->trans("NewContact"), 1, $user->rights->societe->contact->creer);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts", $langs->trans("List"), 1, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->rights->societe->contact->lire);
            $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=contacts&type=o", $langs->trans("Others"), 2, $user->rights->societe->contact->lire);

            // Propal
            if (! empty($conf->propal->enabled))
            {
                $langs->load("propal");
                $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals", $langs->trans("Prop"), 0 ,$user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/societe/societe.php?leftmenu=propals", $langs->trans("NewPropal"), 1, $user->rights->propale->creer);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals", $langs->trans("List"), 1, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=0", $langs->trans("PropalsDraft"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=1", $langs->trans("PropalsOpened"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=2", $langs->trans("PropalStatusSigned"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=3", $langs->trans("PropalStatusNotSigned"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=4", $langs->trans("PropalStatusBilled"), 2, $user->rights->propale->lire);
                //if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal.php?leftmenu=propals&viewstatut=2,3,4", $langs->trans("PropalStatusClosedShort"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add(DOL_URL_ROOT."/comm/propal/stats/index.php?leftmenu=propals", $langs->trans("Statistics"), 1, $user->rights->propale->lire);
            }

            // Customers orders
            if (! empty($conf->commande->enabled))
            {
                $langs->load("orders");
                $newmenu->add(DOL_URL_ROOT."/commande/index.php?leftmenu=orders", $langs->trans("CustomersOrders"), 0 ,$user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/societe/societe.php?leftmenu=orders", $langs->trans("NewOrder"), 1, $user->rights->commande->creer);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders", $langs->trans("List"), 1, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=0", $langs->trans("StatusOrderDraftShort"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=1", $langs->trans("StatusOrderValidated"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=2", $langs->trans("StatusOrderOnProcessShort"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=3", $langs->trans("StatusOrderToBill"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=4", $langs->trans("StatusOrderProcessed"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/liste.php?leftmenu=orders&viewstatut=-1", $langs->trans("StatusOrderCanceledShort"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/stats/index.php?leftmenu=orders", $langs->trans("Statistics"), 1 ,$user->rights->commande->lire);
            }

            // Suppliers orders
            if (! empty($conf->fournisseur->enabled))
            {
                $langs->load("orders");
                $newmenu->add(DOL_URL_ROOT."/fourn/commande/index.php?leftmenu=orders_suppliers",$langs->trans("SuppliersOrders"), 0, $user->rights->fournisseur->commande->lire);
                if ($leftmenu=="orders_suppliers") $newmenu->add(DOL_URL_ROOT."/societe/societe.php?leftmenu=orders_suppliers", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
                if ($leftmenu=="orders_suppliers") $newmenu->add(DOL_URL_ROOT."/fourn/commande/liste.php?leftmenu=orders_suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
                if ($leftmenu=="orders_suppliers") $newmenu->add(DOL_URL_ROOT."/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier", $langs->trans("Statistics"), 1 ,$user->rights->fournisseur->commande->lire);
            }

            // Contrat
            if (! empty($conf->contrat->enabled))
            {
                $langs->load("contracts");
                $newmenu->add(DOL_URL_ROOT."/contrat/index.php?leftmenu=contracts", $langs->trans("Contracts"), 0 ,$user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/societe/societe.php?leftmenu=contracts", $langs->trans("NewContract"), 1, $user->rights->contrat->creer);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/contrat/liste.php?leftmenu=contracts", $langs->trans("List"), 1 ,$user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts", $langs->trans("MenuServices"), 1 ,$user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=0", $langs->trans("MenuInactiveServices"), 2 ,$user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=4", $langs->trans("MenuRunningServices"), 2 ,$user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=4&amp;filter=expired", $langs->trans("MenuExpiredServices"), 2 ,$user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add(DOL_URL_ROOT."/contrat/services.php?leftmenu=contracts&amp;mode=5", $langs->trans("MenuClosedServices"), 2 ,$user->rights->contrat->lire);
            }

            // Interventions
            if (! empty($conf->ficheinter->enabled))
            {
                $langs->load("interventions");
                $newmenu->add(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("Interventions"), 0, $user->rights->ficheinter->lire);
                if ($leftmenu=="ficheinter") $newmenu->add(DOL_URL_ROOT."/fichinter/fiche.php?action=create&leftmenu=ficheinter", $langs->trans("NewIntervention"), 1, $user->rights->ficheinter->creer);
                if ($leftmenu=="ficheinter") $newmenu->add(DOL_URL_ROOT."/fichinter/index.php?leftmenu=ficheinter", $langs->trans("List"), 1 ,$user->rights->ficheinter->lire);
            }

        }


        /*
         * Menu COMPTA-FINANCIAL
         */
        if ($mainmenu == 'accountancy')
        {
            $langs->load("companies");

            // Fournisseurs
            if ($conf->societe->enabled && $conf->fournisseur->enabled)
            {
                $langs->load("suppliers");
                $newmenu->add(DOL_URL_ROOT."/compta/index.php?leftmenu=suppliers", $langs->trans("Suppliers"),0,$user->rights->societe->lire && $user->rights->fournisseur->lire);

                // Security check
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("NewSupplier"),1,$user->rights->societe->creer && $user->rights->fournisseur->lire);
                }
                $newmenu->add(DOL_URL_ROOT."/fourn/liste.php?leftmenu=suppliers", $langs->trans("List"),1,$user->rights->societe->lire && $user->rights->fournisseur->lire);
                if ($conf->societe->enabled)
                {
                    $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"),1,$user->rights->societe->contact->lire && $user->rights->fournisseur->lire);
                }
                if ($conf->facture->enabled)
                {
                    $langs->load("bills");
                    $newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php?leftmenu=suppliers_bills", $langs->trans("BillsSuppliers"),1,$user->rights->fournisseur->facture->lire);
                    if ($user->societe_id == 0)
                    {
                        if ($leftmenu=="suppliers_bills") $newmenu->add(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"),2,$user->rights->fournisseur->facture->creer);
                    }
                    if ($leftmenu=="suppliers_bills") $newmenu->add(DOL_URL_ROOT."/fourn/facture/impayees.php", $langs->trans("Unpaid"),2,$user->rights->fournisseur->facture->lire);
                    if ($leftmenu=="suppliers_bills") $newmenu->add(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"),2,$user->rights->fournisseur->facture->lire);

                    if ($leftmenu=="suppliers_bills") $newmenu->add(DOL_URL_ROOT."/compta/facture/stats/index.php?leftmenu=suppliers_bills&mode=supplier", $langs->trans("Statistics"),2,$user->rights->fournisseur->facture->lire);
                }
            }

            // Customers
            if ($conf->societe->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/compta/index.php?leftmenu=customers", $langs->trans("Customers"),0,$user->rights->societe->lire);
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"),1,$user->rights->societe->creer);
                }
                $newmenu->add(DOL_URL_ROOT."/compta/clients.php?leftmenu=customers", $langs->trans("List"),1,$user->rights->societe->lire);
                $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"),1,$user->rights->societe->contact->lire);
            }

            // Invoices
            if ($conf->facture->enabled)
            {
                $langs->load("bills");
                $newmenu->add(DOL_URL_ROOT."/compta/facture.php?leftmenu=customers_bills",$langs->trans("BillsCustomers"),1,$user->rights->facture->lire);
                if ($user->societe_id == 0)
                {
                    if (preg_match("/customers_bills/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/clients.php?action=facturer&amp;leftmenu=customers_bills",$langs->trans("NewBill"),2,$user->rights->facture->creer);
                }
                if (preg_match("/customers_bills/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/facture/fiche-rec.php?leftmenu=customers_bills",$langs->trans("Repeatables"),2,$user->rights->facture->lire);

                if (preg_match("/customers_bills/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/facture/impayees.php?leftmenu=customers_bills",$langs->trans("Unpaid"),2,$user->rights->facture->lire);

                if (preg_match("/customers_bills/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/paiement/liste.php?leftmenu=customers_bills_payments",$langs->trans("Payments"),2,$user->rights->facture->lire);

                if ($conf->global->BILL_ADD_PAYMENT_VALIDATION)
                {
                    if (preg_match("/customers_bills_payments/i",$leftmenu))  $newmenu->add(DOL_URL_ROOT."/compta/paiement/avalider.php?leftmenu=customers_bills_payments",$langs->trans("MenuToValid"),3,$user->rights->facture->lire);
                }
                if (preg_match("/customers_bills_payments/i",$leftmenu))  $newmenu->add(DOL_URL_ROOT."/compta/paiement/rapport.php?leftmenu=customers_bills_payments",$langs->trans("Reportings"),3,$user->rights->facture->lire);

                if (preg_match("/customers_bills/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/facture/stats/index.php?leftmenu=customers_bills", $langs->trans("Statistics"),2,$user->rights->facture->lire);
            }

            // Proposals
            if ($conf->propal->enabled)
            {
                $langs->load("propal");
                $newmenu->add(DOL_URL_ROOT."/compta/propal.php",$langs->trans("Prop"),0,$user->rights->propale->lire);
            }

            // Orders
            if ($conf->commande->enabled)
            {
                $langs->load("orders");
                if ($conf->facture->enabled) $newmenu->add(DOL_URL_ROOT."/compta/commande/liste.php?leftmenu=orders&amp;status=3&amp;afacturer=1", $langs->trans("MenuOrdersToBill"), 0, $user->rights->commande->lire);
                //                  if ($leftmenu=="orders") $newmenu->add(DOL_URL_ROOT."/commande/", $langs->trans("StatusOrderToBill"), 1 ,$user->rights->commande->lire);
            }

            // Donations
            if ($conf->don->enabled)
            {
                $langs->load("donations");
                $newmenu->add(DOL_URL_ROOT."/compta/dons/index.php?leftmenu=donations&amp;mainmenu=accountancy",$langs->trans("Donations"), 0, $user->rights->don->lire);
                if ($leftmenu=="donations") $newmenu->add(DOL_URL_ROOT."/compta/dons/fiche.php?action=create",$langs->trans("NewDonation"), 1, $user->rights->don->creer);
                if ($leftmenu=="donations") $newmenu->add(DOL_URL_ROOT."/compta/dons/liste.php",$langs->trans("List"), 1, $user->rights->don->lire);
                if ($leftmenu=="donations") $newmenu->add(DOL_URL_ROOT."/compta/dons/stats.php",$langs->trans("Statistics"), 1, $user->rights->don->lire);
            }

            // Deplacements
            if ($conf->deplacement->enabled)
            {
                $langs->load("trips");
                $newmenu->add(DOL_URL_ROOT."/compta/deplacement/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("TripsAndExpenses"), 0, $user->rights->deplacement->lire);
                if ($leftmenu=="tripsandexpenses") $newmenu->add(DOL_URL_ROOT."/compta/deplacement/fiche.php?action=create&amp;leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("New"), 1, $user->rights->deplacement->creer);
                if ($leftmenu=="tripsandexpenses") $newmenu->add(DOL_URL_ROOT."/compta/deplacement/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("List"), 1, $user->rights->deplacement->lire);
                if ($leftmenu=="tripsandexpenses") $newmenu->add(DOL_URL_ROOT."/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("Statistics"), 1, $user->rights->deplacement->lire);
            }

            // Taxes and social contributions
            if ($conf->tax->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/compta/charges/index.php?leftmenu=tax&amp;mainmenu=accountancy",$langs->trans("MenuTaxAndDividends"), 0, $user->rights->tax->charges->lire);
                if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/sociales/index.php?leftmenu=tax_social",$langs->trans("MenuSocialContributions"),1,$user->rights->tax->charges->lire);
                if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/sociales/charges.php?leftmenu=tax_social&action=create",$langs->trans("MenuNewSocialContribution"), 2, $user->rights->tax->charges->creer);
                if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/charges/index.php?leftmenu=tax_social&amp;mainmenu=accountancy&amp;mode=sconly",$langs->trans("Payments"), 2, $user->rights->tax->charges->lire);
                // VAT
                if ($conf->compta->tva)
                {
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/tva/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy",$langs->trans("VAT"),1,$user->rights->tax->charges->lire);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/tva/fiche.php?leftmenu=tax_vat&action=create",$langs->trans("NewPayment"),2,$user->rights->tax->charges->creer);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/tva/reglement.php?leftmenu=tax_vat",$langs->trans("Payments"),2,$user->rights->tax->charges->lire);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/tva/clients.php?leftmenu=tax_vat", $langs->trans("ReportByCustomers"), 2, $user->rights->tax->charges->lire);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/tva/quadri_detail.php?leftmenu=tax_vat", $langs->trans("ReportByQuarter"), 2, $user->rights->tax->charges->lire);
                }
            }

            // Compta simple
            if ($conf->compta->enabled && $conf->global->FACTURE_VENTILATION)
            {
                $newmenu->add(DOL_URL_ROOT."/compta/ventilation/index.php?leftmenu=ventil",$langs->trans("Dispatch"),0,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/ventilation/liste.php",$langs->trans("ToDispatch"),1,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/ventilation/lignes.php",$langs->trans("Dispatched"),1,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/param/",$langs->trans("Setup"),1,$user->rights->compta->ventilation->parametrer);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/param/comptes/fiche.php?action=create",$langs->trans("New"),2,$user->rights->compta->ventilation->parametrer);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/param/comptes/liste.php",$langs->trans("List"),2,$user->rights->compta->ventilation->parametrer);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/export/",$langs->trans("Export"),1,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/export/index.php?action=export",$langs->trans("New"),2,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add(DOL_URL_ROOT."/compta/export/liste.php",$langs->trans("List"),2,$user->rights->compta->ventilation->lire);
            }

            // Compta expert
            if ($conf->accounting->enabled)
            {

            }

            // Rapports
            if ($conf->compta->enabled || $conf->accounting->enabled)
            {
                // Bilan, resultats
                $newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca&amp;mainmenu=accountancy",$langs->trans("Reportings"),0,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);

                if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/index.php?leftmenu=ca",$langs->trans("ReportInOut"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/clientfourn.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                /* On verra ca avec module compabilit� expert
                 if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/compteres.php?leftmenu=ca","Compte de r�sultat",2,$user->rights->compta->resultat->lire);
                 if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/resultat/bilan.php?leftmenu=ca","Bilan",2,$user->rights->compta->resultat->lire);
                 */
                if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/index.php?leftmenu=ca",$langs->trans("ReportTurnover"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);

                /*
                 if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/cumul.php?leftmenu=ca","Cumul�",2,$user->rights->compta->resultat->lire);
                 if ($conf->propal->enabled) {
                 if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/prev.php?leftmenu=ca","Pr�visionnel",2,$user->rights->compta->resultat->lire);
                 if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/comp.php?leftmenu=ca","Transform�",2,$user->rights->compta->resultat->lire);
                 }
                 */
                if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/casoc.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                if ($leftmenu=="ca") $newmenu->add(DOL_URL_ROOT."/compta/stats/cabyuser.php?leftmenu=ca",$langs->trans("ByUsers"),2,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
            }

        }


        /*
         * Menu BANK
         */
        if ($mainmenu == 'bank')
        {
            $langs->load("withdrawals");
            $langs->load("banks");
            $langs->load("bills");

            // Bank-Caisse
            if ($conf->banque->enabled)
            {
                $langs->load("banks");
                $newmenu->add(DOL_URL_ROOT."/compta/bank/index.php?leftmenu=bank&amp;mainmenu=bank",$langs->trans("MenuBankCash"),0,$user->rights->banque->lire);

                $newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/fiche.php?action=create",$langs->trans("MenuNewFinancialAccount"),1,$user->rights->banque->configurer);
                $newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/categ.php",$langs->trans("Rubriques"),1,$user->rights->banque->configurer);

                $newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/search.php",$langs->trans("ListTransactions"),1,$user->rights->banque->lire);
                $newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/budget.php",$langs->trans("ListTransactionsByCategory"),1,$user->rights->banque->lire);

                $newmenu->add_submenu(DOL_URL_ROOT."/compta/bank/virement.php",$langs->trans("BankTransfers"),1,$user->rights->banque->transfer);
            }

            // Prelevements
            if ($conf->prelevement->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/compta/prelevement/index.php?leftmenu=withdraw&amp;mainmenu=bank",$langs->trans("StandingOrders"),0,$user->rights->prelevement->bons->lire);

                if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/create.php?mainmenu=bank",$langs->trans("NewStandingOrder"),1,$user->rights->prelevement->bons->creer);

                //if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/demandes.php",$langs->trans("StandingOrder"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/demandes.php?status=0&amp;mainmenu=bank",$langs->trans("StandingOrderToProcess"),1,$user->rights->prelevement->bons->lire);
                //if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/demandes.php?status=1",$langs->trans("StandingOrderProcessed"),2,$user->rights->prelevement->bons->lire);

                if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/bons.php?mainmenu=bank",$langs->trans("WithdrawalsReceipts"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/liste.php?mainmenu=bank",$langs->trans("WithdrawalsLines"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/rejets.php?mainmenu=bank",$langs->trans("Rejects"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/stats.php?mainmenu=bank",$langs->trans("Statistics"),1,$user->rights->prelevement->bons->lire);

                //if ($leftmenu=="withdraw") $newmenu->add(DOL_URL_ROOT."/compta/prelevement/config.php",$langs->trans("Setup"),1,$user->rights->prelevement->bons->configurer);
            }

            // Gestion cheques
            if ($conf->facture->enabled && $conf->banque->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank",$langs->trans("MenuChequeDeposits"),0,$user->rights->banque->cheque);
                if (preg_match("/checks/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/paiement/cheque/fiche.php?leftmenu=checks&amp;action=new&amp;mainmenu=bank",$langs->trans("NewChequeDeposit"),1,$user->rights->banque->cheque);
                if (preg_match("/checks/i",$leftmenu)) $newmenu->add(DOL_URL_ROOT."/compta/paiement/cheque/liste.php?leftmenu=checks&amp;mainmenu=bank",$langs->trans("MenuChequesReceipts"),1,$user->rights->banque->cheque);
            }

       }

        /*
         * Menu PRODUITS-SERVICES
         */
        if ($mainmenu == 'products')
        {
            // Products
            if ($conf->product->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/product/index.php?leftmenu=product&amp;type=0", $langs->trans("Products"), 0, $user->rights->produit->lire);
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0", $langs->trans("NewProduct"), 1, $user->rights->produit->creer);
                    $newmenu->add(DOL_URL_ROOT."/product/liste.php?leftmenu=product&amp;type=0", $langs->trans("List"), 1, $user->rights->produit->lire);
                }
                if ($conf->propal->enabled)
                {
                    $newmenu->add(DOL_URL_ROOT."/product/popuprop.php?leftmenu=stats&amp;type=0", $langs->trans("Popularity"), 1, $user->rights->produit->lire && $user->rights->propale->lire);
                }
                if ($conf->stock->enabled)
                {
                    $newmenu->add(DOL_URL_ROOT."/product/reassort.php?type=0", $langs->trans("Stocks"), 1, $user->rights->produit->lire && $user->rights->stock->lire);
                }
            }

            // Services
            if ($conf->service->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/product/index.php?leftmenu=service&amp;type=1", $langs->trans("Services"), 0, $user->rights->service->lire);
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/product/fiche.php?leftmenu=service&amp;action=create&amp;type=1", $langs->trans("NewService"), 1, $user->rights->service->creer);
                }
                $newmenu->add(DOL_URL_ROOT."/product/liste.php?leftmenu=service&amp;type=1", $langs->trans("List"), 1, $user->rights->service->lire);
                if ($conf->propal->enabled)
                {
                    $newmenu->add(DOL_URL_ROOT."/product/popuprop.php?leftmenu=stats&amp;type=1", $langs->trans("Popularity"), 1, $user->rights->service->lire && $user->rights->propale->lire);
                }
            }

            // Categories
            if ($conf->categorie->enabled)
            {
                $langs->load("categories");
                $newmenu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=0", $langs->trans("Categories"), 0, $user->rights->categorie->lire);
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=0", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                }
                //if ($leftmenu=="cat") $newmenu->add(DOL_URL_ROOT."/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
            }

            // Stocks
            if ($conf->stock->enabled)
            {
                $langs->load("stocks");
                $newmenu->add(DOL_URL_ROOT."/product/stock/index.php?leftmenu=stock", $langs->trans("Stocks"), 0, $user->rights->stock->lire);
                if ($leftmenu=="stock") $newmenu->add(DOL_URL_ROOT."/product/stock/fiche.php?action=create", $langs->trans("MenuNewWarehouse"), 1, $user->rights->stock->creer);
                if ($leftmenu=="stock") $newmenu->add(DOL_URL_ROOT."/product/stock/liste.php", $langs->trans("List"), 1, $user->rights->stock->lire);
                if ($leftmenu=="stock") $newmenu->add(DOL_URL_ROOT."/product/stock/valo.php", $langs->trans("EnhancedValue"), 1, $user->rights->stock->lire);
                if ($leftmenu=="stock") $newmenu->add(DOL_URL_ROOT."/product/stock/mouvement.php", $langs->trans("Movements"), 1, $user->rights->stock->mouvement->lire);
            }

            // Expeditions
            if ($conf->expedition->enabled)
            {
                $langs->load("sendings");
                $newmenu->add(DOL_URL_ROOT."/expedition/index.php?leftmenu=sendings", $langs->trans("Shipments"), 0, $user->rights->expedition->lire);
                if ($leftmenu=="sendings") $newmenu->add(DOL_URL_ROOT."/expedition/liste.php?leftmenu=sendings", $langs->trans("List"), 1 ,$user->rights->expedition->lire);
                if ($leftmenu=="sendings") $newmenu->add(DOL_URL_ROOT."/expedition/stats/index.php?leftmenu=sendings", $langs->trans("Statistics"), 1 ,$user->rights->expedition->lire);
            }

        }


        /*
         * Menu FOURNISSEURS
         */
        if ($mainmenu == 'suppliers')
        {
            $langs->load("suppliers");

            if ($conf->societe->enabled && $conf->fournisseur->enabled)
            {
                $newmenu->add(DOL_URL_ROOT."/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"), 0, $user->rights->societe->lire && $user->rights->fournisseur->lire);

                // Security check
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("NewSupplier"), 1, $user->rights->societe->creer && $user->rights->fournisseur->lire);
                }
                $newmenu->add(DOL_URL_ROOT."/fourn/liste.php",$langs->trans("List"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);
                $newmenu->add(DOL_URL_ROOT."/contact/index.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"), 1, $user->rights->societe->contact->lire && $user->rights->fournisseur->lire);
                $newmenu->add(DOL_URL_ROOT."/fourn/stats.php",$langs->trans("Statistics"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);
            }

            if ($conf->facture->enabled)
            {
                $langs->load("bills");
                $newmenu->add(DOL_URL_ROOT."/fourn/facture/index.php", $langs->trans("Bills"), 0, $user->rights->fournisseur->facture->lire);

                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"), 1, $user->rights->fournisseur->facture->creer);
                }

                $newmenu->add(DOL_URL_ROOT."/fourn/facture/paiement.php", $langs->trans("Payments"), 1, $user->rights->fournisseur->facture->lire);
            }

            if ($conf->fournisseur->enabled)
            {
                $langs->load("orders");
                $newmenu->add(DOL_URL_ROOT."/fourn/commande/index.php?leftmenu=suppliers",$langs->trans("Orders"), 0, $user->rights->fournisseur->commande->lire);
                $newmenu->add(DOL_URL_ROOT."/societe/societe.php?leftmenu=supplier", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
                $newmenu->add(DOL_URL_ROOT."/fourn/commande/liste.php?leftmenu=suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
            }

            if ($conf->categorie->enabled)
            {
                $langs->load("categories");
                $newmenu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=1", $langs->trans("Categories"), 0, $user->rights->categorie->lire);
                if ($user->societe_id == 0)
                {
                    $newmenu->add(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=1", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                }
                //if ($leftmenu=="cat") $newmenu->add(DOL_URL_ROOT."/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
            }

        }

        /*
         * Menu PROJECTS
         */
        if ($mainmenu == 'project')
        {
            if ($conf->projet->enabled)
            {
                $langs->load("projects");

                // Project affected to user
                $newmenu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects&mode=mine", $langs->trans("MyProjects"), 0, $user->rights->projet->lire);
                $newmenu->add(DOL_URL_ROOT."/projet/fiche.php?leftmenu=projects&action=create&mode=mine", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
                $newmenu->add(DOL_URL_ROOT."/projet/liste.php?leftmenu=projects&mode=mine", $langs->trans("List"), 1, $user->rights->projet->lire);

                // All project i have permission on
                $newmenu->add(DOL_URL_ROOT."/projet/index.php?leftmenu=projects", $langs->trans("Projects"), 0, $user->rights->projet->lire && $user->rights->projet->lire);
                $newmenu->add(DOL_URL_ROOT."/projet/fiche.php?leftmenu=projects&action=create", $langs->trans("NewProject"), 1, $user->rights->projet->creer && $user->rights->projet->creer);
                $newmenu->add(DOL_URL_ROOT."/projet/liste.php?leftmenu=projects", $langs->trans("List"), 1, $user->rights->projet->lire && $user->rights->projet->lire);

                // Project affected to user
                $newmenu->add(DOL_URL_ROOT."/projet/activity/index.php?mode=mine", $langs->trans("MyActivities"), 0, $user->rights->projet->lire);
                $newmenu->add(DOL_URL_ROOT."/projet/tasks.php?action=create&mode=mine", $langs->trans("NewTask"), 1, $user->rights->projet->creer);
                $newmenu->add(DOL_URL_ROOT."/projet/tasks/index.php?mode=mine", $langs->trans("List"), 1, $user->rights->projet->lire);
                $newmenu->add(DOL_URL_ROOT."/projet/activity/list.php?mode=mine", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer);

                // All project i have permission on
                $newmenu->add(DOL_URL_ROOT."/projet/activity/index.php", $langs->trans("Activities"), 0, $user->rights->projet->lire && $user->rights->projet->lire);
                $newmenu->add(DOL_URL_ROOT."/projet/tasks.php?action=create", $langs->trans("NewTask"), 1, $user->rights->projet->creer && $user->rights->projet->creer);
                $newmenu->add(DOL_URL_ROOT."/projet/tasks/index.php", $langs->trans("List"), 1, $user->rights->projet->lire && $user->rights->projet->lire);
                $newmenu->add(DOL_URL_ROOT."/projet/activity/list.php", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer && $user->rights->projet->creer);
            }
        }


        /*
         * Menu OUTILS
         */
        if ($mainmenu == 'tools')
        {

            if (! empty($conf->mailing->enabled))
            {
                $langs->load("mails");

                $newmenu->add(DOL_URL_ROOT."/comm/mailing/index.php?leftmenu=mailing", $langs->trans("EMailings"), 0, $user->rights->mailing->lire);
                $newmenu->add(DOL_URL_ROOT."/comm/mailing/fiche.php?leftmenu=mailing&amp;action=create", $langs->trans("NewMailing"), 1, $user->rights->mailing->creer);
                $newmenu->add(DOL_URL_ROOT."/comm/mailing/liste.php?leftmenu=mailing", $langs->trans("List"), 1, $user->rights->mailing->lire);
            }

            if (! empty($conf->export->enabled))
            {
                $langs->load("exports");
                $newmenu->add(DOL_URL_ROOT."/exports/index.php?leftmenu=export",$langs->trans("FormatedExport"),0, $user->rights->export->lire);
                $newmenu->add(DOL_URL_ROOT."/exports/export.php?leftmenu=export",$langs->trans("NewExport"),1, $user->rights->export->creer);
                //$newmenu->add(DOL_URL_ROOT."/exports/export.php?leftmenu=export",$langs->trans("List"),1, $user->rights->export->lire);
            }

            if (! empty($conf->import->enabled))
            {
                $langs->load("exports");
                $newmenu->add(DOL_URL_ROOT."/imports/index.php?leftmenu=import",$langs->trans("FormatedImport"),0, $user->rights->import->run);
                $newmenu->add(DOL_URL_ROOT."/imports/import.php?leftmenu=import",$langs->trans("NewImport"),1, $user->rights->import->run);
            }

            if (! empty($conf->domain->enabled))
            {
                $langs->load("domains");
                $newmenu->add(DOL_URL_ROOT."/domain/index.php?leftmenu=export",$langs->trans("DomainNames"),0, $user->rights->domain->read);
                $newmenu->add(DOL_URL_ROOT."/domain/fiche.php?action=create&leftmenu=export",$langs->trans("NewDomain"),1, $user->rights->domain->create);
                $newmenu->add(DOL_URL_ROOT."/domain/index.php?leftmenu=export",$langs->trans("List"),1, $user->rights->domain->read);
            }
        }

        /*
         * Menu MEMBERS
         */
        if ($mainmenu == 'members')
        {
            if ($conf->adherent->enabled)
            {
                $langs->load("members");
                $langs->load("compta");

                $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=members&amp;mainmenu=members",$langs->trans("Members"),0,$user->rights->adherent->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/fiche.php?leftmenu=members&amp;action=create",$langs->trans("NewMember"),1,$user->rights->adherent->creer);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members",$langs->trans("List"),1,$user->rights->adherent->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=-1",$langs->trans("MenuMembersToValidate"),2,$user->rights->adherent->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=1",$langs->trans("MenuMembersValidated"),2,$user->rights->adherent->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=uptodate",$langs->trans("MenuMembersUpToDate"),2,$user->rights->adherent->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=outofdate",$langs->trans("MenuMembersNotUpToDate"),2,$user->rights->adherent->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=0",$langs->trans("MenuMembersResiliated"),2,$user->rights->adherent->lire);

                $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=members&amp;mainmenu=members",$langs->trans("Subscriptions"),0,$user->rights->adherent->cotisation->lire);
                $newmenu->add(DOL_URL_ROOT."/adherents/liste.php?leftmenu=members&amp;statut=-1,1&amp;mainmenu=members",$langs->trans("NewSubscription"),1,$user->rights->adherent->cotisation->creer);
                $newmenu->add(DOL_URL_ROOT."/adherents/cotisations.php?leftmenu=members",$langs->trans("List"),1,$user->rights->adherent->cotisation->lire);


                if ($conf->categorie->enabled)
                {
                    $langs->load("categories");
                    $newmenu->add(DOL_URL_ROOT."/categories/index.php?leftmenu=cat&amp;type=3", $langs->trans("Categories"), 0, $user->rights->categorie->lire);
                    if ($user->societe_id == 0)
                    {
                        $newmenu->add(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=3", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                    }
                    //if ($leftmenu=="cat") $newmenu->add(DOL_URL_ROOT."/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
                }

                $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=export&amp;mainmenu=members",$langs->trans("Exports"),0,$user->rights->adherent->export);
                if ($conf->export->enabled && $leftmenu=="export") $newmenu->add(DOL_URL_ROOT."/exports/index.php?leftmenu=export",$langs->trans("Datas"),1,$user->rights->adherent->export);
                if ($leftmenu=="export") $newmenu->add(DOL_URL_ROOT."/adherents/htpasswd.php?leftmenu=export",$langs->trans("Filehtpasswd"),1,$user->rights->adherent->export);
                if ($leftmenu=="export") $newmenu->add(DOL_URL_ROOT."/adherents/cartes/carte.php?leftmenu=export",$langs->trans("MembersCards"),1,$user->rights->adherent->export);

                $newmenu->add(DOL_URL_ROOT."/adherents/public.php?leftmenu=member_public",$langs->trans("MemberPublicLinks"));

                $newmenu->add(DOL_URL_ROOT."/adherents/index.php?leftmenu=setup&amp;mainmenu=members",$langs->trans("Setup"),0,$user->rights->adherent->configurer);
                $newmenu->add(DOL_URL_ROOT."/adherents/type.php?leftmenu=setup&amp;",$langs->trans("MembersTypes"),1,$user->rights->adherent->configurer);
                $newmenu->add(DOL_URL_ROOT."/adherents/options.php?leftmenu=setup&amp;",$langs->trans("MembersAttributes"),1,$user->rights->adherent->configurer);


            }

        }

        // Affichage des menus personnalises
        require_once(DOL_DOCUMENT_ROOT."/core/class/menubase.class.php");

        $menuArbo = new Menubase($db,'eldy','left');
        $overwritemenufor = $menuArbo->listeMainmenu();
        // Add other mainmenu to the list of menu to overwrite pre.inc.php
        $overwritemenumore=array('home','companies','members','products','suppliers','commercial','accountancy','agenda','project','tools','ecm');
        $overwritemenufor=array_merge($overwritemenumore, $overwritemenufor);
        $newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,($user->societe_id?1:0),'eldy');

        /*
         * Menu AUTRES (Pour les menus du haut qui ne serait pas geres)
         */
        if ($mainmenu && ! in_array($mainmenu,$overwritemenufor)) { $mainmenu=""; }

    }


    /**
     *  Si on est sur un cas gere de surcharge du menu, on ecrase celui par defaut
     */
    //var_dump($menu_array_before);exit;
    //var_dump($menu_array_after);exit;
    //if ($mainmenu) {
    $menu_array=$newmenu->liste;
    if (is_array($menu_array_before)) $menu_array=array_merge($menu_array_before, $menu_array);
    if (is_array($menu_array_after))  $menu_array=array_merge($menu_array, $menu_array_after);
    //}

    // Affichage du menu
    $alt=0;
    if (is_array($menu_array))
    {
        for ($i = 0 ; $i < sizeof($menu_array) ; $i++)
        {
            $alt++;
            if (empty($menu_array[$i]['level']))
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
            $tabul=($menu_array[$i]['level'] - 1);
            if ($tabul > 0)
            {
                for ($j=0; $j < $tabul; $j++)
                {
                    $tabstring.='&nbsp; &nbsp;';
                }
            }

            // Menu niveau 0
            if ($menu_array[$i]['level'] == 0)
            {
                if ($menu_array[$i]['enabled'])
                {
                    print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.$menu_array[$i]['url'].'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>'.$menu_array[$i]['titre'].'</a></div>'."\n";
                }
                else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
                {
                    print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$menu_array[$i]['titre'].'</font></div>'."\n";
                }
                print '<div class="menu_top"></div>'."\n";
            }
            // Menu niveau > 0
            if ($menu_array[$i]['level'] > 0)
            {
                if ($menu_array[$i]['enabled'])
                {
                	print '<div class="menu_contenu">'.$tabstring;
                    if ($menu_array[$i]['url']) print '<a class="vsmenu" href="'.$menu_array[$i]['url'].'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>';
                    print $menu_array[$i]['titre'];
                    if ($menu_array[$i]['url']) print '</a>';
                    // If title is not pure text and contains a table, no carriage return added
                    if (! strstr($menu_array[$i]['titre'],'<table')) print '<br>';
                    print '</div>'."\n";
                }
                else if (empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))
                {
                	print '<div class="menu_contenu">'.$tabstring.'<font class="vsmenudisabled">'.$menu_array[$i]['titre'].'</font><br></div>'."\n";
                }
            }

            // If next is a new block or end
            if (empty($menu_array[$i+1]['level']))
            {
                print '<div class="menu_end"></div>'."\n";
                print "</div>\n";
            }
        }
    }

    return sizeof($menu_array);
}


?>
