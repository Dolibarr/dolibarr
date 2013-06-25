<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010      Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012      Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/menus/standard/eldy.lib.php
 *  \brief		Library for file eldy menus
 */


/**
 * Core function to output top menu eldy
 *
 * @param 	DoliDB	$db				Database handler
 * @param 	string	$atarget		Target
 * @param 	int		$type_user     	0=Menu for backoffice, 1=Menu for front office
 * @return	void
 */
function print_eldy_menu($db,$atarget,$type_user)
{
	global $user,$conf,$langs,$dolibarr_main_db_name;

	// On sauve en session le menu principal choisi
	if (isset($_GET["mainmenu"])) $_SESSION["mainmenu"]=$_GET["mainmenu"];
	if (isset($_GET["idmenu"]))   $_SESSION["idmenu"]=$_GET["idmenu"];
	$_SESSION["leftmenuopened"]="";

	$id='mainmenu';
	$listofmodulesforexternal=explode(',',$conf->global->MAIN_MODULES_FOR_EXTERNAL);

	print_start_menu_array();

	// Home
	$classname="";
	if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "home")
	{
		$classname='class="tmenusel"'; $_SESSION['idmenu']='';
	}
	else
	{
		$classname = 'class="tmenu"';
	}
	$idsel='home';
	print_start_menu_entry($idsel,$classname);
	print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
	print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
	print '</a>';
	print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
	print_text_menu_entry($langs->trans("Home"));
	print '</a>';
	print_end_menu_entry();


	// Third parties
	$tmpentry=array('enabled'=>($conf->societe->enabled || $conf->fournisseur->enabled), 'perms'=>($user->rights->societe->lire || $user->rights->fournisseur->lire), 'module'=>'societe|fournisseur');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
	{
		$langs->load("companies");
		$langs->load("suppliers");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "companies")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='companies';

		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/societe/index.php?mainmenu=companies&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/societe/index.php?mainmenu=companies&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($langs->trans("ThirdParties"));
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($langs->trans("ThirdParties"));
			print '</a>';
			print_end_menu_entry();
		}
	}

	// Products-Services
	$tmpentry=array('enabled'=>($conf->product->enabled || $conf->service->enabled), 'perms'=>($user->rights->produit->lire || $user->rights->service->lire), 'module'=>'product|service');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
	{
		$langs->load("products");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "products")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}
		$chaine="";
		if (! empty($conf->product->enabled)) { $chaine.=$langs->trans("Products"); }
		if (! empty($conf->product->enabled) && ! empty($conf->service->enabled)) { $chaine.="/"; }
		if (! empty($conf->service->enabled)) { $chaine.=$langs->trans("Services"); }

		$idsel='products';
		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage"  id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($chaine);
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($chaine);
			print '</a>';
			print_end_menu_entry();
		}
	}

	// Commercial
	$menuqualified=0;
    if (! empty($conf->propal->enabled)) $menuqualified++;
    if (! empty($conf->commande->enabled)) $menuqualified++;
    if (! empty($conf->fournisseur->enabled)) $menuqualified++;
    if (! empty($conf->contrat->enabled)) $menuqualified++;
    if (! empty($conf->ficheinter->enabled)) $menuqualified++;
	$tmpentry=array('enabled'=>$menuqualified, 'perms'=>($user->rights->societe->lire || $user->rights->societe->contact->lire), 'module'=>'propal|commande|fournisseur|contrat|ficheinter');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
	{
		$langs->load("commercial");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "commercial")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='commercial';
		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="'.$id.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($langs->trans("Commercial"));
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print print_text_menu_entry($langs->trans("Commercial"));
			print '</a>';
			print_end_menu_entry();
		}
	}

	// Financial
	$tmpentry=array('enabled'=>(! empty($conf->comptabilite->enabled) || ! empty($conf->accounting->enabled) || ! empty($conf->facture->enabled) || ! empty($conf->deplacement->enabled) || ! empty($conf->don->enabled) || ! empty($conf->tax->enabled)),
		 	 		'perms'=>($user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire || $user->rights->facture->lire || $user->rights->deplacement->lire || $user->rights->don->lire || $user->rights->tax->charges->lire),
					'module'=>'comptabilite|accounting|facture|deplacement|don|tax');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
	{
		$langs->load("compta");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "accountancy")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='accountancy';
		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($langs->trans("MenuFinancial"));
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($langs->trans("MenuFinancial"));
			print '</a>';
			print_end_menu_entry();
		}
	}

    // Bank
	$tmpentry=array('enabled'=>(! empty($conf->banque->enabled) || ! empty($conf->prelevement->enabled)),
		 	 		'perms'=>($user->rights->banque->lire || $user->rights->prelevement->lire),
					'module'=>'banque|prelevement');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
    {
        $langs->load("compta");
        $langs->load("banks");

        $classname="";
        if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "bank")
        {
            $classname='class="tmenusel"'; $_SESSION['idmenu']='';
        }
        else
        {
            $classname = 'class="tmenu"';
        }

        $idsel='bank';
        if ($showmode == 1)
        {
            print_start_menu_entry($idsel,$classname);
            print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/compta/bank/index.php?mainmenu=bank&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
            print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
            print '</a>';
            print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/compta/bank/index.php?mainmenu=bank&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
            print_text_menu_entry($langs->trans("MenuBankCash"));
            print '</a>';
            print_end_menu_entry();
        }
        else if ($showmode == 2)
        {
        	print_start_menu_entry($idsel,$classname);
        	print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
        	print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
        	print_text_menu_entry($langs->trans("MenuBankCash"));
        	print '</a>';
        	print_end_menu_entry();
        }
    }

	// Projects
	$tmpentry=array('enabled'=>(! empty($conf->projet->enabled)),
		 	 		'perms'=>($user->rights->projet->lire),
					'module'=>'projet');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
    {
		$langs->load("projects");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "project")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='project';
		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($langs->trans("Projects"));
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($langs->trans("Projects"));
			print '</a>';
			print_end_menu_entry();
		}
	}

	// Tools
	$tmpentry=array('enabled'=>(! empty($conf->mailing->enabled) || ! empty($conf->export->enabled) || ! empty($conf->import->enabled)),
		 	 		'perms'=>($user->rights->mailing->lire || $user->rights->export->lire || $user->rights->import->run),
					'module'=>'mailing|export|import');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
    {
		$langs->load("other");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "tools")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='tools';
		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/core/tools.php?mainmenu=tools&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/core/tools.php?mainmenu=tools&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($langs->trans("Tools"));
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($langs->trans("Tools"));
			print '</a>';
			print_end_menu_entry();
		}
	}

	// OSCommerce 1
	$tmpentry=array('enabled'=>(! empty($conf->boutique->enabled)),
		 	 		'perms'=>1,
					'module'=>'boutique');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
    {
		$langs->load("shop");

		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "shop")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='shop';
		print_start_menu_entry($idsel,$classname);
		print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '</a>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
		print_text_menu_entry($langs->trans("OSCommerce"));
		print '</a>';
		print_end_menu_entry();
	}

	// Members
	$tmpentry=array('enabled'=>(! empty($conf->adherent->enabled)),
		 	 		'perms'=>($user->rights->adherent->lire),
					'module'=>'adherent');
	$showmode=dol_eldy_showmenu($type_user, $tmpentry, $listofmodulesforexternal);
	if ($showmode)
	{
		$classname="";
		if (isset($_SESSION["mainmenu"]) && $_SESSION["mainmenu"] == "members")
		{
			$classname='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$classname = 'class="tmenu"';
		}

		$idsel='members';
		if ($showmode == 1)
		{
			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($atarget?' target="'.$atarget.'"':'').'>';
			print_text_menu_entry($langs->trans("MenuMembers"));
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,$classname);
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($langs->trans("MenuMembers"));
			print '</a>';
			print_end_menu_entry();
		}
	}


	// Show personalized menus
	require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

    $tabMenu=array();
	$menuArbo = new Menubase($db,'eldy','top');
	$newTabMenu = $menuArbo->menuTopCharger('','',$type_user,'eldy',$tabMenu);	// Return tabMenu with only top entries

	$num = count($newTabMenu);
	for($i = 0; $i < $num; $i++)
	{
		$idsel=(empty($newTabMenu[$i]['mainmenu'])?'none':$newTabMenu[$i]['mainmenu']);

		$showmode=dol_eldy_showmenu($type_user,$newTabMenu[$i],$listofmodulesforexternal);

		if ($showmode == 1)
		{
			if (preg_match("/^(http:\/\/|https:\/\/)/i",$newTabMenu[$i]['url']))
			{
				$url = $newTabMenu[$i]['url'];
			}
			else
			{
				$url=dol_buildpath($newTabMenu[$i]['url'],1);
				if (! preg_match('/mainmenu/i',$url) || ! preg_match('/leftmenu/i',$url))
				{
					if (! preg_match('/\?/',$url)) $url.='?';
					else $url.='&';
					$url.='mainmenu='.$newTabMenu[$i]['mainmenu'].'&leftmenu=';
				}
				//$url.="idmenu=".$newTabMenu[$i]['rowid'];    // Already done by menuLoad
			}
			$url=preg_replace('/__LOGIN__/',$user->login,$url);

			// Define the class (top menu selected or not)
			if (! empty($_SESSION['idmenu']) && $newTabMenu[$i]['rowid'] == $_SESSION['idmenu']) $classname='class="tmenusel"';
			else if (! empty($_SESSION["mainmenu"]) && $newTabMenu[$i]['mainmenu'] == $_SESSION["mainmenu"]) $classname='class="tmenusel"';
			else $classname='class="tmenu"';

			print_start_menu_entry($idsel,$classname);
			print '<a class="tmenuimage" href="'.$url.'"'.($newTabMenu[$i]['target']?" target='".$newTabMenu[$i]['target']."'":($atarget?' target="'.$atarget.'"':'')).'>';
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '</a>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($newTabMenu[$i]['target']?" target='".$newTabMenu[$i]['target']."'":($atarget?' target="'.$atarget.'"':'')).'>';
			print_text_menu_entry($newTabMenu[$i]['titre']);
			print '</a>';
			print_end_menu_entry();
		}
		else if ($showmode == 2)
		{
			print_start_menu_entry($idsel,'class="tmenu"');
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.' tmenuimage" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#" title="'.dol_escape_htmltag($langs->trans("NotAllowed")).'">';
			print_text_menu_entry($newTabMenu[$i]['titre']);
			print '</a>';
			print_end_menu_entry();
		}
	}

	print_end_menu_array();
}


/**
 * Output start menu array
 *
 * @return	void
 */
function print_start_menu_array()
{
	print '<div class="tmenudiv">';
	print '<ul class="tmenu">';
}

/**
 * Output start menu entry
 *
 * @param	string	$idsel		Text
 * @param	string	$classname	String to add a css class
 * @return	void
 */
function print_start_menu_entry($idsel,$classname)
{
	print '<li '.$classname.' id="mainmenutd_'.$idsel.'">';
	print '<div class="tmenuleft"></div><div class="tmenucenter">';
}

/**
 * Output menu entry
 *
 * @param	string	$text		Text
 * @return	void
 */
function print_text_menu_entry($text)
{
	print '<span class="mainmenuaspan">';
	print $text;
	print '</span>';
}

/**
 * Output end menu entry
 *
 * @return	void
 */
function print_end_menu_entry()
{
	print '</div></li>';
	print "\n";
}

/**
 * Output menu array
 *
 * @return	void
 */
function print_end_menu_array()
{
    print '</ul>';
    print '</div>';
	print "\n";
}



/**
 * Core function to output left menu eldy
 *
 * @param	DoliDB		$db                  Database handler
 * @param 	array		$menu_array_before   Table of menu entries to show before entries of menu handler
 * @param   array		$menu_array_after    Table of menu entries to show after entries of menu handler
 * @return	void
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
            $urllogo=DOL_URL_ROOT.'/viewimage.php?cache=1&amp;modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_mini);
            print "\n".'<!-- Show logo on menu -->'."\n";
            print '<div class="blockvmenuimpair">'."\n";
            print '<div class="menu_titre" id="menu_titre_logo"></div>';
            print '<div class="menu_top" id="menu_top_logo"></div>';
            print '<div class="menu_contenu" id="menu_contenu_logo">';
            print '<center><img title="" src="'.$urllogo.'"></center>'."\n";
            print '</div>';
            print '<div class="menu_end" id="menu_end_logo"></div>';
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

                // Setup
                $newmenu->add("/admin/index.php?mainmenu=home&leftmenu=setup", $langs->trans("Setup"), 0, 1, '', $mainmenu, 'setup');
                if ($leftmenu=="setup")
                {
                	$warnpicto='';
                	if (empty($conf->global->MAIN_INFO_SOCIETE_NOM) || empty($conf->global->MAIN_INFO_SOCIETE_PAYS))
                	{
                		$langs->load("errors");
                		$warnpicto=img_warning($langs->trans("WarningMandatorySetupNotComplete"));
                	}
                    $newmenu->add("/admin/company.php?mainmenu=home", $langs->trans("MenuCompanySetup").' '.$warnpicto,1);
                    $warnpicto='';
                	if (count($conf->modules) <= (empty($conf->global->MAIN_MINNB_MODULE)?1:$conf->global->MAIN_MINNB_MODULE))	// If only user module enabled
                	{
                		$langs->load("errors");
                		$warnpicto=img_warning($langs->trans("WarningMandatorySetupNotComplete"));
                	}
                    $newmenu->add("/admin/modules.php?mainmenu=home", $langs->trans("Modules").' '.$warnpicto,1);
                    $newmenu->add("/admin/menus.php?mainmenu=home", $langs->trans("Menus"),1);
                    $newmenu->add("/admin/ihm.php?mainmenu=home", $langs->trans("GUISetup"),1);
                    if (! in_array($langs->defaultlang,array('en_US','en_GB','en_NZ','en_AU','fr_FR','fr_BE','es_ES','ca_ES')))
                    {
                        if ($leftmenu=="setup") $newmenu->add("/admin/translation.php", $langs->trans("Translation"),1);
                    }
                    $newmenu->add("/admin/boxes.php?mainmenu=home", $langs->trans("Boxes"),1);
                    $newmenu->add("/admin/delais.php?mainmenu=home",$langs->trans("Alerts"),1);
                    $newmenu->add("/admin/proxy.php?mainmenu=home", $langs->trans("Security"),1);
                    $newmenu->add("/admin/limits.php?mainmenu=home", $langs->trans("MenuLimits"),1);
                    $newmenu->add("/admin/pdf.php?mainmenu=home", $langs->trans("PDF"),1);
                    $newmenu->add("/admin/mails.php?mainmenu=home", $langs->trans("Emails"),1);
                    $newmenu->add("/admin/sms.php?mainmenu=home", $langs->trans("SMS"),1);
                    $newmenu->add("/admin/dict.php?mainmenu=home", $langs->trans("DictionnarySetup"),1);
                    $newmenu->add("/admin/const.php?mainmenu=home", $langs->trans("OtherSetup"),1);
                }

                // System tools
                $newmenu->add("/admin/tools/index.php?mainmenu=home&leftmenu=admintools", $langs->trans("SystemTools"), 0, 1, '', $mainmenu, 'admintools');
                if (preg_match('/^admintools/',$leftmenu))
                {
                	$newmenu->add('/admin/system/dolibarr.php?mainmenu=home&leftmenu=admintools_info', $langs->trans('InfoDolibarr'), 1);
                	if ($leftmenu=='admintools_info') $newmenu->add('/admin/system/modules.php?mainmenu=home&leftmenu=admintools_info', $langs->trans('Modules'), 2);
                	if ($leftmenu=='admintools_info') $newmenu->add('/admin/triggers.php?mainmenu=home&leftmenu=admintools_info', $langs->trans('Triggers'), 2);
                	$newmenu->add('/admin/system/os.php?mainmenu=home&leftmenu=admintools', $langs->trans('InfoOS'), 1);
                	$newmenu->add('/admin/system/web.php?mainmenu=home&leftmenu=admintools', $langs->trans('InfoWebServer'), 1);
                	$newmenu->add('/admin/system/phpinfo.php?mainmenu=home&leftmenu=admintools', $langs->trans('InfoPHP'), 1);
                	//if (function_exists('xdebug_is_enabled')) $newmenu->add('/admin/system/xdebug.php', $langs->trans('XDebug'),1);
                	$newmenu->add('/admin/system/database.php?mainmenu=home&leftmenu=admintools', $langs->trans('InfoDatabase'), 1);

                    $newmenu->add("/admin/tools/dolibarr_export.php?mainmenu=home&leftmenu=admintools", $langs->trans("Backup"),1);
                    $newmenu->add("/admin/tools/dolibarr_import.php?mainmenu=home&leftmenu=admintools", $langs->trans("Restore"),1);
                    $newmenu->add("/admin/tools/update.php?mainmenu=home&leftmenu=admintools", $langs->trans("MenuUpgrade"),1);
                    if (function_exists('eaccelerator_info')) $newmenu->add("/admin/tools/eaccelerator.php?mainmenu=home&leftmenu=admintools", $langs->trans("EAccelerator"),1);
                    $newmenu->add("/admin/tools/listevents.php?mainmenu=home&leftmenu=admintools", $langs->trans("Audit"),1);
                    $newmenu->add("/admin/tools/listsessions.php?mainmenu=home&leftmenu=admintools", $langs->trans("Sessions"),1);
                    $newmenu->add("/admin/tools/purge.php?mainmenu=home&leftmenu=admintools", $langs->trans("Purge"),1);
                    $newmenu->add('/admin/system/about.php?mainmenu=home&leftmenu=admintools', $langs->trans('About'), 1);
                    $newmenu->add("/support/index.php?mainmenu=home&leftmenu=admintools", $langs->trans("HelpCenter"),1,1,'targethelp');
                }
				// Modules system tools
                if (! empty($conf->product->enabled) || ! empty($conf->service->enabled) || ! empty($conf->global->MAIN_MENU_ENABLE_MODULETOOLS))
	            {
			    	if (empty($user->societe_id))
			    	{
		            	$newmenu->add("/admin/tools/index.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ModulesSystemTools"), 0, 1, '', $mainmenu, 'modulesadmintools');
		            	if ($leftmenu=="modulesadmintools" && $user->admin)
		            	{
		            		$langs->load("products");
		            		$newmenu->add("/product/admin/product_tools.php?mainmenu=home&leftmenu=modulesadmintools", $langs->trans("ProductVatMassChange"), 1, $user->admin);
		            	}
			    	}
	            }
            }

            $newmenu->add("/user/home.php?leftmenu=users", $langs->trans("MenuUsersAndGroups"), 0, 1, '', $mainmenu, 'users');
            if ($leftmenu=="users")
            {
                $newmenu->add("/user/index.php", $langs->trans("Users"), 1, $user->rights->user->user->lire || $user->admin);
                $newmenu->add("/user/fiche.php?action=create", $langs->trans("NewUser"),2, $user->rights->user->user->creer || $user->admin);
                $newmenu->add("/user/group/index.php", $langs->trans("Groups"), 1, ($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->read:$user->rights->user->user->lire) || $user->admin);
                $newmenu->add("/user/group/fiche.php?action=create", $langs->trans("NewGroup"), 2, ($conf->global->MAIN_USE_ADVANCED_PERMS?$user->rights->user->group_advance->write:$user->rights->user->user->creer) || $user->admin);
            }
        }


        /*
         * Menu TIERS
         */
        if ($mainmenu == 'companies')
        {
            // Societes
            if (! empty($conf->societe->enabled))
            {
                $langs->load("companies");
                $newmenu->add("/societe/index.php?leftmenu=thirdparties", $langs->trans("ThirdParty"), 0, $user->rights->societe->lire, '', $mainmenu, 'thirdparties');

                if ($user->rights->societe->creer)
                {
                    $newmenu->add("/societe/soc.php?action=create", $langs->trans("MenuNewThirdParty"),1);
                    if (! $conf->use_javascript_ajax) $newmenu->add("/societe/soc.php?action=create&amp;private=1",$langs->trans("MenuNewPrivateIndividual"),1);
                }
            }

            // Prospects
            if (! empty($conf->societe->enabled) && empty($conf->global->SOCIETE_DISABLE_PROSPECTS))
            {
                $langs->load("commercial");
                $newmenu->add("/comm/prospect/list.php?leftmenu=prospects", $langs->trans("ListProspectsShort"), 1, $user->rights->societe->lire, '', $mainmenu, 'prospects');

                if ($leftmenu=="prospects") $newmenu->add("/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=-1", $langs->trans("LastProspectDoNotContact"), 2, $user->rights->societe->lire);
                if ($leftmenu=="prospects") $newmenu->add("/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=0", $langs->trans("LastProspectNeverContacted"), 2, $user->rights->societe->lire);
                if ($leftmenu=="prospects") $newmenu->add("/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=1", $langs->trans("LastProspectToContact"), 2, $user->rights->societe->lire);
                if ($leftmenu=="prospects") $newmenu->add("/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=2", $langs->trans("LastProspectContactInProcess"), 2, $user->rights->societe->lire);
                if ($leftmenu=="prospects") $newmenu->add("/comm/prospect/list.php?sortfield=s.datec&amp;sortorder=desc&amp;begin=&amp;stcomm=3", $langs->trans("LastProspectContactDone"), 2, $user->rights->societe->lire);

                $newmenu->add("/societe/soc.php?leftmenu=prospects&amp;action=create&amp;type=p", $langs->trans("MenuNewProspect"), 2, $user->rights->societe->creer);
                //$newmenu->add("/contact/list.php?leftmenu=customers&amp;type=p", $langs->trans("Contacts"), 2, $user->rights->societe->contact->lire);
            }

            // Clients
            if (! empty($conf->societe->enabled))
            {
                $langs->load("commercial");
                $newmenu->add("/comm/list.php?leftmenu=customers", $langs->trans("ListCustomersShort"), 1, $user->rights->societe->lire, '', $mainmenu, 'customers');

                $newmenu->add("/societe/soc.php?leftmenu=customers&amp;action=create&amp;type=c", $langs->trans("MenuNewCustomer"), 2, $user->rights->societe->creer);
                //$newmenu->add("/contact/list.php?leftmenu=customers&amp;type=c", $langs->trans("Contacts"), 2, $user->rights->societe->contact->lire);
            }

            // Fournisseurs
            if (! empty($conf->societe->enabled) && ! empty($conf->fournisseur->enabled))
            {
                $langs->load("suppliers");
                $newmenu->add("/fourn/liste.php?leftmenu=suppliers", $langs->trans("ListSuppliersShort"), 1, $user->rights->fournisseur->lire, '', $mainmenu, 'suppliers');

                if (empty($user->societe_id))
                {
                    $newmenu->add("/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("MenuNewSupplier"), 2, $user->rights->societe->creer && $user->rights->fournisseur->lire);
                }
                //$newmenu->add("/fourn/liste.php?leftmenu=suppliers", $langs->trans("List"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire);
                //$newmenu->add("/contact/list.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"), 2, $user->rights->societe->lire && $user->rights->fournisseur->lire && $user->rights->societe->contact->lire);
            }

            // Contacts
            $newmenu->add("/contact/list.php?leftmenu=contacts", (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("Contacts") : $langs->trans("ContactsAddresses")), 0, $user->rights->societe->contact->lire, '', $mainmenu, 'contacts');
            $newmenu->add("/contact/fiche.php?leftmenu=contacts&amp;action=create", (! empty($conf->global->SOCIETE_ADDRESSES_MANAGEMENT) ? $langs->trans("NewContact") : $langs->trans("NewContactAddress")), 1, $user->rights->societe->contact->creer);
            $newmenu->add("/contact/list.php?leftmenu=contacts", $langs->trans("List"), 1, $user->rights->societe->contact->lire);
            if (empty($conf->global->SOCIETE_DISABLE_PROSPECTS)) $newmenu->add("/contact/list.php?leftmenu=contacts&type=p", $langs->trans("Prospects"), 2, $user->rights->societe->contact->lire);
            $newmenu->add("/contact/list.php?leftmenu=contacts&type=c", $langs->trans("Customers"), 2, $user->rights->societe->contact->lire);
            if (! empty($conf->fournisseur->enabled)) $newmenu->add("/contact/list.php?leftmenu=contacts&type=f", $langs->trans("Suppliers"), 2, $user->rights->societe->contact->lire);
            $newmenu->add("/contact/list.php?leftmenu=contacts&type=o", $langs->trans("Others"), 2, $user->rights->societe->contact->lire);
            //$newmenu->add("/contact/list.php?userid=$user->id", $langs->trans("MyContacts"), 1, $user->rights->societe->contact->lire);

            // Categories
            if (! empty($conf->categorie->enabled))
            {
                $langs->load("categories");
                // Categories prospects/customers
                $newmenu->add("/categories/index.php?leftmenu=cat&amp;type=2", $langs->trans("CustomersProspectsCategoriesShort"), 0, $user->rights->categorie->lire, '', $mainmenu, 'cat');
                if (empty($user->societe_id))
                {
                    $newmenu->add("/categories/fiche.php?action=create&amp;type=2", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                }
                // Categories suppliers
                if (! empty($conf->fournisseur->enabled))
                {
                    $newmenu->add("/categories/index.php?leftmenu=cat&amp;type=1", $langs->trans("SuppliersCategoriesShort"), 0, $user->rights->categorie->lire);
                    if (empty($user->societe_id))
                    {
                        $newmenu->add("/categories/fiche.php?action=create&amp;type=1", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                    }
                }
                //if ($leftmenu=="cat") $newmenu->add("/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
            }

        }

        /*
         * Menu COMMERCIAL
         */
        if ($mainmenu == 'commercial')
        {
            $langs->load("companies");

            // Propal
            if (! empty($conf->propal->enabled))
            {
                $langs->load("propal");
                $newmenu->add("/comm/propal/index.php?leftmenu=propals", $langs->trans("Prop"), 0, $user->rights->propale->lire, '', $mainmenu, 'propals');
                $newmenu->add("/societe/societe.php?leftmenu=propals", $langs->trans("NewPropal"), 1, $user->rights->propale->creer);
                $newmenu->add("/comm/propal/list.php?leftmenu=propals", $langs->trans("List"), 1, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add("/comm/propal/list.php?leftmenu=propals&viewstatut=0", $langs->trans("PropalsDraft"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add("/comm/propal/list.php?leftmenu=propals&viewstatut=1", $langs->trans("PropalsOpened"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add("/comm/propal/list.php?leftmenu=propals&viewstatut=2", $langs->trans("PropalStatusSigned"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add("/comm/propal/list.php?leftmenu=propals&viewstatut=3", $langs->trans("PropalStatusNotSigned"), 2, $user->rights->propale->lire);
                if ($leftmenu=="propals") $newmenu->add("/comm/propal/list.php?leftmenu=propals&viewstatut=4", $langs->trans("PropalStatusBilled"), 2, $user->rights->propale->lire);
                //if ($leftmenu=="propals") $newmenu->add("/comm/propal/list.php?leftmenu=propals&viewstatut=2,3,4", $langs->trans("PropalStatusClosedShort"), 2, $user->rights->propale->lire);
                $newmenu->add("/comm/propal/stats/index.php?leftmenu=propals", $langs->trans("Statistics"), 1, $user->rights->propale->lire);
            }

            // Customers orders
            if (! empty($conf->commande->enabled))
            {
                $langs->load("orders");
                $newmenu->add("/commande/index.php?leftmenu=orders", $langs->trans("CustomersOrders"), 0, $user->rights->commande->lire, '', $mainmenu, 'orders');
                $newmenu->add("/societe/societe.php?leftmenu=orders", $langs->trans("NewOrder"), 1, $user->rights->commande->creer);
                $newmenu->add("/commande/liste.php?leftmenu=orders", $langs->trans("List"), 1, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add("/commande/liste.php?leftmenu=orders&viewstatut=0", $langs->trans("StatusOrderDraftShort"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add("/commande/liste.php?leftmenu=orders&viewstatut=1", $langs->trans("StatusOrderValidated"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders" && ! empty($conf->expedition->enabled)) $newmenu->add("/commande/liste.php?leftmenu=orders&viewstatut=2", $langs->trans("StatusOrderOnProcessShort"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add("/commande/liste.php?leftmenu=orders&viewstatut=3", $langs->trans("StatusOrderToBill"), 2, $user->rights->commande->lire);  // The translation key is StatusOrderToBill but it means StatusDelivered. TODO We should renamed this later
                if ($leftmenu=="orders") $newmenu->add("/commande/liste.php?leftmenu=orders&viewstatut=4", $langs->trans("StatusOrderProcessed"), 2, $user->rights->commande->lire);
                if ($leftmenu=="orders") $newmenu->add("/commande/liste.php?leftmenu=orders&viewstatut=-1", $langs->trans("StatusOrderCanceledShort"), 2, $user->rights->commande->lire);
                $newmenu->add("/commande/stats/index.php?leftmenu=orders", $langs->trans("Statistics"), 1, $user->rights->commande->lire);
            }

            // Suppliers orders
            if (! empty($conf->fournisseur->enabled))
            {
                $langs->load("orders");
                $newmenu->add("/fourn/commande/index.php?leftmenu=orders_suppliers",$langs->trans("SuppliersOrders"), 0, $user->rights->fournisseur->commande->lire, '', $mainmenu, 'orders_suppliers');
                $newmenu->add("/societe/societe.php?leftmenu=orders_suppliers", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
                $newmenu->add("/fourn/commande/liste.php?leftmenu=orders_suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
                $newmenu->add("/commande/stats/index.php?leftmenu=orders_suppliers&amp;mode=supplier", $langs->trans("Statistics"), 1, $user->rights->fournisseur->commande->lire);
            }

            // Contrat
            if (! empty($conf->contrat->enabled))
            {
                $langs->load("contracts");
                $newmenu->add("/contrat/index.php?leftmenu=contracts", $langs->trans("Contracts"), 0, $user->rights->contrat->lire, '', $mainmenu, 'contracts');
                $newmenu->add("/societe/societe.php?leftmenu=contracts", $langs->trans("NewContract"), 1, $user->rights->contrat->creer);
                $newmenu->add("/contrat/liste.php?leftmenu=contracts", $langs->trans("List"), 1, $user->rights->contrat->lire);
                $newmenu->add("/contrat/services.php?leftmenu=contracts", $langs->trans("MenuServices"), 1, $user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add("/contrat/services.php?leftmenu=contracts&amp;mode=0", $langs->trans("MenuInactiveServices"), 2, $user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add("/contrat/services.php?leftmenu=contracts&amp;mode=4", $langs->trans("MenuRunningServices"), 2, $user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add("/contrat/services.php?leftmenu=contracts&amp;mode=4&amp;filter=expired", $langs->trans("MenuExpiredServices"), 2, $user->rights->contrat->lire);
                if ($leftmenu=="contracts") $newmenu->add("/contrat/services.php?leftmenu=contracts&amp;mode=5", $langs->trans("MenuClosedServices"), 2, $user->rights->contrat->lire);
            }

            // Interventions
            if (! empty($conf->ficheinter->enabled))
            {
                $langs->load("interventions");
                $newmenu->add("/fichinter/list.php?leftmenu=ficheinter", $langs->trans("Interventions"), 0, $user->rights->ficheinter->lire, '', $mainmenu, 'ficheinter');
                $newmenu->add("/fichinter/fiche.php?action=create&leftmenu=ficheinter", $langs->trans("NewIntervention"), 1, $user->rights->ficheinter->creer);
                $newmenu->add("/fichinter/list.php?leftmenu=ficheinter", $langs->trans("List"), 1, $user->rights->ficheinter->lire);
            }

        }


        /*
         * Menu COMPTA-FINANCIAL
         */
        if ($mainmenu == 'accountancy')
        {
            $langs->load("companies");

            // Customers invoices
            if (! empty($conf->facture->enabled))
            {
                $langs->load("bills");
                $newmenu->add("/compta/facture/list.php?leftmenu=customers_bills",$langs->trans("BillsCustomers"),0,$user->rights->facture->lire, '', $mainmenu, 'customers_bills');
                if (empty($user->societe_id))
                {
                    $newmenu->add("/compta/clients.php?action=facturer&amp;leftmenu=customers_bills",$langs->trans("NewBill"),1,$user->rights->facture->creer);
                }
                $newmenu->add("/compta/facture/fiche-rec.php?leftmenu=customers_bills",$langs->trans("Repeatables"),1,$user->rights->facture->lire);

                $newmenu->add("/compta/facture/impayees.php?leftmenu=customers_bills",$langs->trans("Unpaid"),1,$user->rights->facture->lire);

                $newmenu->add("/compta/paiement/liste.php?leftmenu=customers_bills_payments",$langs->trans("Payments"),1,$user->rights->facture->lire);

                if (! empty($conf->global->BILL_ADD_PAYMENT_VALIDATION))
                {
                    $newmenu->add("/compta/paiement/avalider.php?leftmenu=customers_bills_payments",$langs->trans("MenuToValid"),2,$user->rights->facture->lire);
                }
                $newmenu->add("/compta/paiement/rapport.php?leftmenu=customers_bills_payments",$langs->trans("Reportings"),2,$user->rights->facture->lire);

                $newmenu->add("/compta/facture/stats/index.php?leftmenu=customers_bills", $langs->trans("Statistics"),1,$user->rights->facture->lire);
            }

            // Suppliers
            if (! empty($conf->societe->enabled) && ! empty($conf->fournisseur->enabled))
            {
                if (! empty($conf->facture->enabled))
                {
                    $langs->load("bills");
                    $newmenu->add("/fourn/facture/index.php?leftmenu=suppliers_bills", $langs->trans("BillsSuppliers"),0,$user->rights->fournisseur->facture->lire, '', $mainmenu, 'suppliers_bills');
                    if (empty($user->societe_id))
                    {
                        $newmenu->add("/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"),1,$user->rights->fournisseur->facture->creer);
                    }
                    $newmenu->add("/fourn/facture/impayees.php", $langs->trans("Unpaid"),1,$user->rights->fournisseur->facture->lire);
                    $newmenu->add("/fourn/facture/paiement.php", $langs->trans("Payments"),1,$user->rights->fournisseur->facture->lire);

                    $newmenu->add("/compta/facture/stats/index.php?leftmenu=suppliers_bills&mode=supplier", $langs->trans("Statistics"),1,$user->rights->fournisseur->facture->lire);
                }
            }

            // Orders
            if (! empty($conf->commande->enabled))
            {
                $langs->load("orders");
                if (! empty($conf->facture->enabled)) $newmenu->add("/commande/liste.php?leftmenu=orders&amp;viewstatut=-3", $langs->trans("MenuOrdersToBill2"), 0, $user->rights->commande->lire, '', $mainmenu, 'orders');
                //                  if ($leftmenu=="orders") $newmenu->add("/commande/", $langs->trans("StatusOrderToBill"), 1, $user->rights->commande->lire);
            }

            // Donations
            if (! empty($conf->don->enabled))
            {
                $langs->load("donations");
                $newmenu->add("/compta/dons/index.php?leftmenu=donations&amp;mainmenu=accountancy",$langs->trans("Donations"), 0, $user->rights->don->lire, '', $mainmenu, 'donations');
                if ($leftmenu=="donations") $newmenu->add("/compta/dons/fiche.php?action=create",$langs->trans("NewDonation"), 1, $user->rights->don->creer);
                if ($leftmenu=="donations") $newmenu->add("/compta/dons/liste.php",$langs->trans("List"), 1, $user->rights->don->lire);
                //if ($leftmenu=="donations") $newmenu->add("/compta/dons/stats.php",$langs->trans("Statistics"), 1, $user->rights->don->lire);
            }

            // Trips and expenses
            if (! empty($conf->deplacement->enabled))
            {
                $langs->load("trips");
                $newmenu->add("/compta/deplacement/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("TripsAndExpenses"), 0, $user->rights->deplacement->lire, '', $mainmenu, 'tripsandexpenses');
                if ($leftmenu=="tripsandexpenses") $newmenu->add("/compta/deplacement/fiche.php?action=create&amp;leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("New"), 1, $user->rights->deplacement->creer);
                if ($leftmenu=="tripsandexpenses") $newmenu->add("/compta/deplacement/list.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("List"), 1, $user->rights->deplacement->lire);
                if ($leftmenu=="tripsandexpenses") $newmenu->add("/compta/deplacement/stats/index.php?leftmenu=tripsandexpenses&amp;mainmenu=accountancy", $langs->trans("Statistics"), 1, $user->rights->deplacement->lire);
            }

            // Taxes and social contributions
            if (! empty($conf->tax->enabled))
            {
                $newmenu->add("/compta/charges/index.php?leftmenu=tax&amp;mainmenu=accountancy",$langs->trans("MenuTaxAndDividends"), 0, $user->rights->tax->charges->lire, '', $mainmenu, 'tax');
                if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/sociales/index.php?leftmenu=tax_social",$langs->trans("MenuSocialContributions"),1,$user->rights->tax->charges->lire);
                if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/sociales/charges.php?leftmenu=tax_social&action=create",$langs->trans("MenuNewSocialContribution"), 2, $user->rights->tax->charges->creer);
                if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/charges/index.php?leftmenu=tax_social&amp;mainmenu=accountancy&amp;mode=sconly",$langs->trans("Payments"), 2, $user->rights->tax->charges->lire);
                // VAT
                if (empty($conf->global->TAX_DISABLE_VAT_MENUS))
                {
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/tva/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy",$langs->trans("VAT"),1,$user->rights->tax->charges->lire, '', $mainmenu, 'tax_vat');
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/tva/fiche.php?leftmenu=tax_vat&action=create",$langs->trans("NewPayment"),2,$user->rights->tax->charges->creer);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/tva/reglement.php?leftmenu=tax_vat",$langs->trans("Payments"),2,$user->rights->tax->charges->lire);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/tva/clients.php?leftmenu=tax_vat", $langs->trans("ReportByCustomers"), 2, $user->rights->tax->charges->lire);
                    if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/tva/quadri_detail.php?leftmenu=tax_vat", $langs->trans("ReportByQuarter"), 2, $user->rights->tax->charges->lire);
                    global $mysoc;

                    //Local Taxes
                    if($mysoc->country_code=='ES' && (isset($mysoc->localtax2_assuj) && $mysoc->localtax2_assuj=="1"))
                    {
                    	if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/localtax/index.php?leftmenu=tax_vat&amp;mainmenu=accountancy",$langs->transcountry("LT2",$mysoc->country_code),1,$user->rights->tax->charges->lire);
                    	if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/localtax/fiche.php?leftmenu=tax_vat&action=create",$langs->trans("NewPayment"),2,$user->rights->tax->charges->creer);
                    	if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/localtax/reglement.php?leftmenu=tax_vat",$langs->trans("Payments"),2,$user->rights->tax->charges->lire);
                    	if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/localtax/clients.php?leftmenu=tax_vat", $langs->trans("ReportByCustomers"), 2, $user->rights->tax->charges->lire);
                    	//if (preg_match('/^tax/i',$leftmenu)) $newmenu->add("/compta/localtax/quadri_detail.php?leftmenu=tax_vat", $langs->trans("ReportByQuarter"), 2, $user->rights->tax->charges->lire);
                    }

                }

            }

            // Compta simple
            if (! empty($conf->comptabilite->enabled) && ! empty($conf->global->FACTURE_VENTILATION))
            {
                $newmenu->add("/compta/ventilation/index.php?leftmenu=ventil",$langs->trans("Dispatch"),0,$user->rights->compta->ventilation->lire, '', $mainmenu, 'ventil');
                if ($leftmenu=="ventil") $newmenu->add("/compta/ventilation/liste.php",$langs->trans("ToDispatch"),1,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add("/compta/ventilation/lignes.php",$langs->trans("Dispatched"),1,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add("/compta/param/",$langs->trans("Setup"),1,$user->rights->compta->ventilation->parametrer);
                if ($leftmenu=="ventil") $newmenu->add("/compta/param/comptes/fiche.php?action=create",$langs->trans("New"),2,$user->rights->compta->ventilation->parametrer);
                if ($leftmenu=="ventil") $newmenu->add("/compta/param/comptes/liste.php",$langs->trans("List"),2,$user->rights->compta->ventilation->parametrer);
                if ($leftmenu=="ventil") $newmenu->add("/compta/export/",$langs->trans("Export"),1,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add("/compta/export/index.php?action=export",$langs->trans("New"),2,$user->rights->compta->ventilation->lire);
                if ($leftmenu=="ventil") $newmenu->add("/compta/export/liste.php",$langs->trans("List"),2,$user->rights->compta->ventilation->lire);
            }

            // Compta expert
            if (! empty($conf->accounting->enabled))
            {

            }

            // Rapports
            if (! empty($conf->comptabilite->enabled) || ! empty($conf->accounting->enabled))
            {
                $langs->load("compta");

                // Bilan, resultats
                $newmenu->add("/compta/resultat/index.php?leftmenu=ca&amp;mainmenu=accountancy",$langs->trans("Reportings"),0,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire, '', $mainmenu, 'ca');

                if ($leftmenu=="ca") $newmenu->add("/compta/resultat/index.php?leftmenu=ca",$langs->trans("ReportInOut"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                if ($leftmenu=="ca") $newmenu->add("/compta/resultat/clientfourn.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                /* On verra ca avec module compabilite expert
                 if ($leftmenu=="ca") $newmenu->add("/compta/resultat/compteres.php?leftmenu=ca","Compte de resultat",2,$user->rights->compta->resultat->lire);
                 if ($leftmenu=="ca") $newmenu->add("/compta/resultat/bilan.php?leftmenu=ca","Bilan",2,$user->rights->compta->resultat->lire);
                 */
                if ($leftmenu=="ca") $newmenu->add("/compta/stats/index.php?leftmenu=ca",$langs->trans("ReportTurnover"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);

                /*
                 if ($leftmenu=="ca") $newmenu->add("/compta/stats/cumul.php?leftmenu=ca","Cumule",2,$user->rights->compta->resultat->lire);
                 if (! empty($conf->propal->enabled)) {
                 if ($leftmenu=="ca") $newmenu->add("/compta/stats/prev.php?leftmenu=ca","Previsionnel",2,$user->rights->compta->resultat->lire);
                 if ($leftmenu=="ca") $newmenu->add("/compta/stats/comp.php?leftmenu=ca","Transforme",2,$user->rights->compta->resultat->lire);
                 }
                 */
                if ($leftmenu=="ca") $newmenu->add("/compta/stats/casoc.php?leftmenu=ca",$langs->trans("ByCompanies"),2,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                if ($leftmenu=="ca") $newmenu->add("/compta/stats/cabyuser.php?leftmenu=ca",$langs->trans("ByUsers"),2,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);

                // Journaux
 				//if ($leftmenu=="ca") $newmenu->add("/compta/journaux/index.php?leftmenu=ca",$langs->trans("Journaux"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                //journaux
                if ($leftmenu=="ca") $newmenu->add("/compta/journal/sellsjournal.php?leftmenu=ca",$langs->trans("SellsJournal"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
                if ($leftmenu=="ca") $newmenu->add("/compta/journal/purchasesjournal.php?leftmenu=ca",$langs->trans("PurchasesJournal"),1,$user->rights->compta->resultat->lire||$user->rights->accounting->comptarapport->lire);
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
            if (! empty($conf->banque->enabled))
            {
                $newmenu->add("/compta/bank/index.php?leftmenu=bank&amp;mainmenu=bank",$langs->trans("MenuBankCash"),0,$user->rights->banque->lire, '', $mainmenu, 'bank');

                $newmenu->add("/compta/bank/fiche.php?action=create",$langs->trans("MenuNewFinancialAccount"),1,$user->rights->banque->configurer);
                $newmenu->add("/compta/bank/categ.php",$langs->trans("Rubriques"),1,$user->rights->banque->configurer);

                $newmenu->add("/compta/bank/search.php",$langs->trans("ListTransactions"),1,$user->rights->banque->lire);
                $newmenu->add("/compta/bank/budget.php",$langs->trans("ListTransactionsByCategory"),1,$user->rights->banque->lire);

                $newmenu->add("/compta/bank/virement.php",$langs->trans("BankTransfers"),1,$user->rights->banque->transfer);
            }

            // Prelevements
            if (! empty($conf->prelevement->enabled))
            {
                $newmenu->add("/compta/prelevement/index.php?leftmenu=withdraw&amp;mainmenu=bank",$langs->trans("StandingOrders"),0,$user->rights->prelevement->bons->lire, '', $mainmenu, 'withdraw');

                //if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/demandes.php?status=0&amp;mainmenu=bank",$langs->trans("StandingOrderToProcess"),1,$user->rights->prelevement->bons->lire);

                if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/create.php?mainmenu=bank",$langs->trans("NewStandingOrder"),1,$user->rights->prelevement->bons->creer);


                if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/bons.php?mainmenu=bank",$langs->trans("WithdrawalsReceipts"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/liste.php?mainmenu=bank",$langs->trans("WithdrawalsLines"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/rejets.php?mainmenu=bank",$langs->trans("Rejects"),1,$user->rights->prelevement->bons->lire);
                if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/stats.php?mainmenu=bank",$langs->trans("Statistics"),1,$user->rights->prelevement->bons->lire);

                //if ($leftmenu=="withdraw") $newmenu->add("/compta/prelevement/config.php",$langs->trans("Setup"),1,$user->rights->prelevement->bons->configurer);
            }

            // Gestion cheques
            if (! empty($conf->facture->enabled) && ! empty($conf->banque->enabled))
            {
                $newmenu->add("/compta/paiement/cheque/index.php?leftmenu=checks&amp;mainmenu=bank",$langs->trans("MenuChequeDeposits"),0,$user->rights->banque->cheque, '', $mainmenu, 'checks');
                $newmenu->add("/compta/paiement/cheque/fiche.php?leftmenu=checks&amp;action=new&amp;mainmenu=bank",$langs->trans("NewChequeDeposit"),1,$user->rights->banque->cheque);
                $newmenu->add("/compta/paiement/cheque/liste.php?leftmenu=checks&amp;mainmenu=bank",$langs->trans("List"),1,$user->rights->banque->cheque);
            }

       }

        /*
         * Menu PRODUCTS-SERVICES
         */
        if ($mainmenu == 'products')
        {
            // Products
            if (! empty($conf->product->enabled))
            {
                $newmenu->add("/product/index.php?leftmenu=product&amp;type=0", $langs->trans("Products"), 0, $user->rights->produit->lire, '', $mainmenu, 'product');
                if (empty($user->societe_id))
                {
                    $newmenu->add("/product/fiche.php?leftmenu=product&amp;action=create&amp;type=0", $langs->trans("NewProduct"), 1, $user->rights->produit->creer);
                    $newmenu->add("/product/liste.php?leftmenu=product&amp;type=0", $langs->trans("List"), 1, $user->rights->produit->lire);
                }
                if (! empty($conf->propal->enabled))
                {
                    $newmenu->add("/product/popuprop.php?leftmenu=stats&amp;type=0", $langs->trans("Statistics"), 1, $user->rights->produit->lire && $user->rights->propale->lire);
                }
                if (! empty($conf->stock->enabled))
                {
                    $newmenu->add("/product/reassort.php?type=0", $langs->trans("Stocks"), 1, $user->rights->produit->lire && $user->rights->stock->lire);
                }
            }

            // Services
            if (! empty($conf->service->enabled))
            {
                $newmenu->add("/product/index.php?leftmenu=service&amp;type=1", $langs->trans("Services"), 0, $user->rights->service->lire, '', $mainmenu, 'service');
                if (empty($user->societe_id))
                {
                    $newmenu->add("/product/fiche.php?leftmenu=service&amp;action=create&amp;type=1", $langs->trans("NewService"), 1, $user->rights->service->creer);
                }
                $newmenu->add("/product/liste.php?leftmenu=service&amp;type=1", $langs->trans("List"), 1, $user->rights->service->lire);
                if (! empty($conf->propal->enabled))
                {
                    $newmenu->add("/product/popuprop.php?leftmenu=stats&amp;type=1", $langs->trans("Statistics"), 1, $user->rights->service->lire && $user->rights->propale->lire);
                }
            }

            // Categories
            if (! empty($conf->categorie->enabled))
            {
                $langs->load("categories");
                $newmenu->add("/categories/index.php?leftmenu=cat&amp;type=0", $langs->trans("Categories"), 0, $user->rights->categorie->lire, '', $mainmenu, 'cat');
                if (empty($user->societe_id))
                {
                    $newmenu->add("/categories/fiche.php?action=create&amp;type=0", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                }
                //if ($leftmenu=="cat") $newmenu->add("/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
            }

            // Stocks
            if (! empty($conf->stock->enabled))
            {
                $langs->load("stocks");
                $newmenu->add("/product/stock/index.php?leftmenu=stock", $langs->trans("Stocks"), 0, $user->rights->stock->lire, '', $mainmenu, 'stock');
                if ($leftmenu=="stock") $newmenu->add("/product/stock/fiche.php?action=create", $langs->trans("MenuNewWarehouse"), 1, $user->rights->stock->creer);
                if ($leftmenu=="stock") $newmenu->add("/product/stock/liste.php", $langs->trans("List"), 1, $user->rights->stock->lire);
                if ($leftmenu=="stock") $newmenu->add("/product/stock/valo.php", $langs->trans("EnhancedValue"), 1, $user->rights->stock->lire);
                if ($leftmenu=="stock") $newmenu->add("/product/stock/mouvement.php", $langs->trans("Movements"), 1, $user->rights->stock->mouvement->lire);
            }

            // Expeditions
            if (! empty($conf->expedition->enabled))
            {
                $langs->load("sendings");
                $newmenu->add("/expedition/index.php?leftmenu=sendings", $langs->trans("Shipments"), 0, $user->rights->expedition->lire, '', $mainmenu, 'sendings');
                if ($leftmenu=="sendings") $newmenu->add("/expedition/fiche.php?action=create2&leftmenu=sendings", $langs->trans("NewSending"), 1, $user->rights->expedition->creer);
                if ($leftmenu=="sendings") $newmenu->add("/expedition/liste.php?leftmenu=sendings", $langs->trans("List"), 1, $user->rights->expedition->lire);
                if ($leftmenu=="sendings") $newmenu->add("/expedition/stats/index.php?leftmenu=sendings", $langs->trans("Statistics"), 1, $user->rights->expedition->lire);
            }

        }


        /*
         * Menu SUPPLIERS
         */
        if ($mainmenu == 'suppliers')
        {
            $langs->load("suppliers");

            if (! empty($conf->societe->enabled) && ! empty($conf->fournisseur->enabled))
            {
                $newmenu->add("/fourn/index.php?leftmenu=suppliers", $langs->trans("Suppliers"), 0, $user->rights->societe->lire && $user->rights->fournisseur->lire, '', $mainmenu, 'suppliers');

                // Security check
                if (empty($user->societe_id))
                {
                    $newmenu->add("/societe/soc.php?leftmenu=suppliers&amp;action=create&amp;type=f",$langs->trans("NewSupplier"), 1, $user->rights->societe->creer && $user->rights->fournisseur->lire);
                }
                $newmenu->add("/fourn/liste.php",$langs->trans("List"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);
                $newmenu->add("/contact/list.php?leftmenu=suppliers&amp;type=f",$langs->trans("Contacts"), 1, $user->rights->societe->contact->lire && $user->rights->fournisseur->lire);
                $newmenu->add("/fourn/stats.php",$langs->trans("Statistics"), 1, $user->rights->societe->lire && $user->rights->fournisseur->lire);
            }

            if (! empty($conf->facture->enabled))
            {
                $langs->load("bills");
                $newmenu->add("/fourn/facture/index.php?leftmenu=orders", $langs->trans("Bills"), 0, $user->rights->fournisseur->facture->lire, '', $mainmenu, 'orders');

                if (empty($user->societe_id))
                {
                    $newmenu->add("/fourn/facture/fiche.php?action=create",$langs->trans("NewBill"), 1, $user->rights->fournisseur->facture->creer);
                }

                $newmenu->add("/fourn/facture/paiement.php", $langs->trans("Payments"), 1, $user->rights->fournisseur->facture->lire);
            }

            if (! empty($conf->fournisseur->enabled))
            {
                $langs->load("orders");
                $newmenu->add("/fourn/commande/index.php?leftmenu=suppliers",$langs->trans("Orders"), 0, $user->rights->fournisseur->commande->lire, '', $mainmenu, 'suppliers');
                $newmenu->add("/societe/societe.php?leftmenu=supplier", $langs->trans("NewOrder"), 1, $user->rights->fournisseur->commande->creer);
                $newmenu->add("/fourn/commande/liste.php?leftmenu=suppliers", $langs->trans("List"), 1, $user->rights->fournisseur->commande->lire);
            }

            if (! empty($conf->categorie->enabled))
            {
                $langs->load("categories");
                $newmenu->add("/categories/index.php?leftmenu=cat&amp;type=1", $langs->trans("Categories"), 0, $user->rights->categorie->lire, '', $mainmenu, 'cat');
                if (empty($user->societe_id))
                {
                    $newmenu->add("/categories/fiche.php?action=create&amp;type=1", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                }
                //if ($leftmenu=="cat") $newmenu->add("/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
            }

        }

        /*
         * Menu PROJECTS
         */
        if ($mainmenu == 'project')
        {
            if (! empty($conf->projet->enabled))
            {
                $langs->load("projects");

                // Project affected to user
                $newmenu->add("/projet/index.php?leftmenu=projects&mode=mine", $langs->trans("MyProjects"), 0, $user->rights->projet->lire, '', $mainmenu, 'projects');
                $newmenu->add("/projet/fiche.php?leftmenu=projects&action=create&mode=mine", $langs->trans("NewProject"), 1, $user->rights->projet->creer);
                $newmenu->add("/projet/liste.php?leftmenu=projects&mode=mine", $langs->trans("List"), 1, $user->rights->projet->lire);

                // All project i have permission on
                $newmenu->add("/projet/index.php?leftmenu=projects", $langs->trans("Projects"), 0, $user->rights->projet->lire && $user->rights->projet->lire);
                $newmenu->add("/projet/fiche.php?leftmenu=projects&action=create", $langs->trans("NewProject"), 1, $user->rights->projet->creer && $user->rights->projet->creer);
                $newmenu->add("/projet/liste.php?leftmenu=projects", $langs->trans("List"), 1, $user->rights->projet->lire && $user->rights->projet->lire);

                // Project affected to user
                $newmenu->add("/projet/activity/index.php?mode=mine", $langs->trans("MyActivities"), 0, $user->rights->projet->lire);
                $newmenu->add("/projet/tasks.php?action=create&mode=mine", $langs->trans("NewTask"), 1, $user->rights->projet->creer);
                $newmenu->add("/projet/tasks/index.php?mode=mine", $langs->trans("List"), 1, $user->rights->projet->lire);
                $newmenu->add("/projet/activity/list.php?mode=mine", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer);

                // All project i have permission on
                $newmenu->add("/projet/activity/index.php", $langs->trans("Activities"), 0, $user->rights->projet->lire && $user->rights->projet->lire);
                $newmenu->add("/projet/tasks.php?action=create", $langs->trans("NewTask"), 1, $user->rights->projet->creer && $user->rights->projet->creer);
                $newmenu->add("/projet/tasks/index.php", $langs->trans("List"), 1, $user->rights->projet->lire && $user->rights->projet->lire);
                $newmenu->add("/projet/activity/list.php", $langs->trans("NewTimeSpent"), 1, $user->rights->projet->creer && $user->rights->projet->creer);
            }
        }


        /*
         * Menu TOOLS
         */
        if ($mainmenu == 'tools')
        {

            if (! empty($conf->mailing->enabled))
            {
                $langs->load("mails");

                $newmenu->add("/comm/mailing/index.php?leftmenu=mailing", $langs->trans("EMailings"), 0, $user->rights->mailing->lire, '', $mainmenu, 'mailing');
                $newmenu->add("/comm/mailing/fiche.php?leftmenu=mailing&amp;action=create", $langs->trans("NewMailing"), 1, $user->rights->mailing->creer);
                $newmenu->add("/comm/mailing/liste.php?leftmenu=mailing", $langs->trans("List"), 1, $user->rights->mailing->lire);
            }

            if (! empty($conf->export->enabled))
            {
                $langs->load("exports");
                $newmenu->add("/exports/index.php?leftmenu=export",$langs->trans("FormatedExport"),0, $user->rights->export->lire, '', $mainmenu, 'export');
                $newmenu->add("/exports/export.php?leftmenu=export",$langs->trans("NewExport"),1, $user->rights->export->creer);
                //$newmenu->add("/exports/export.php?leftmenu=export",$langs->trans("List"),1, $user->rights->export->lire);
            }

            if (! empty($conf->import->enabled))
            {
                $langs->load("exports");
                $newmenu->add("/imports/index.php?leftmenu=import",$langs->trans("FormatedImport"),0, $user->rights->import->run, '', $mainmenu, 'import');
                $newmenu->add("/imports/import.php?leftmenu=import",$langs->trans("NewImport"),1, $user->rights->import->run);
            }
        }

        /*
         * Menu MEMBERS
         */
        if ($mainmenu == 'members')
        {
            if (! empty($conf->adherent->enabled))
            {
                $langs->load("members");
                $langs->load("compta");

                $newmenu->add("/adherents/index.php?leftmenu=members&amp;mainmenu=members",$langs->trans("Members"),0,$user->rights->adherent->lire, '', $mainmenu, 'members');
                $newmenu->add("/adherents/fiche.php?leftmenu=members&amp;action=create",$langs->trans("NewMember"),1,$user->rights->adherent->creer);
                $newmenu->add("/adherents/liste.php?leftmenu=members",$langs->trans("List"),1,$user->rights->adherent->lire);
                $newmenu->add("/adherents/liste.php?leftmenu=members&amp;statut=-1",$langs->trans("MenuMembersToValidate"),2,$user->rights->adherent->lire);
                $newmenu->add("/adherents/liste.php?leftmenu=members&amp;statut=1",$langs->trans("MenuMembersValidated"),2,$user->rights->adherent->lire);
                $newmenu->add("/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=uptodate",$langs->trans("MenuMembersUpToDate"),2,$user->rights->adherent->lire);
                $newmenu->add("/adherents/liste.php?leftmenu=members&amp;statut=1&amp;filter=outofdate",$langs->trans("MenuMembersNotUpToDate"),2,$user->rights->adherent->lire);
                $newmenu->add("/adherents/liste.php?leftmenu=members&amp;statut=0",$langs->trans("MenuMembersResiliated"),2,$user->rights->adherent->lire);
                $newmenu->add("/adherents/stats/geo.php?leftmenu=members&mode=memberbycountry",$langs->trans("MenuMembersStats"),1,$user->rights->adherent->lire);

                $newmenu->add("/adherents/index.php?leftmenu=members&amp;mainmenu=members",$langs->trans("Subscriptions"),0,$user->rights->adherent->cotisation->lire);
                $newmenu->add("/adherents/liste.php?leftmenu=members&amp;statut=-1,1&amp;mainmenu=members",$langs->trans("NewSubscription"),1,$user->rights->adherent->cotisation->creer);
                $newmenu->add("/adherents/cotisations.php?leftmenu=members",$langs->trans("List"),1,$user->rights->adherent->cotisation->lire);
                $newmenu->add("/adherents/stats/index.php?leftmenu=members",$langs->trans("MenuMembersStats"),1,$user->rights->adherent->lire);


                if (! empty($conf->categorie->enabled))
                {
                    $langs->load("categories");
                    $newmenu->add("/categories/index.php?leftmenu=cat&amp;type=3", $langs->trans("Categories"), 0, $user->rights->categorie->lire, '', $mainmenu, 'cat');
                    if (empty($user->societe_id))
                    {
                        $newmenu->add("/categories/fiche.php?action=create&amp;type=3", $langs->trans("NewCategory"), 1, $user->rights->categorie->creer);
                    }
                    //if ($leftmenu=="cat") $newmenu->add("/categories/liste.php", $langs->trans("List"), 1, $user->rights->categorie->lire);
                }

                $newmenu->add("/adherents/index.php?leftmenu=export&amp;mainmenu=members",$langs->trans("Exports"),0,$user->rights->adherent->export, '', $mainmenu, 'export');
                if (! empty($conf->export->enabled) && $leftmenu=="export") $newmenu->add("/exports/index.php?leftmenu=export",$langs->trans("Datas"),1,$user->rights->adherent->export);
                if ($leftmenu=="export") $newmenu->add("/adherents/htpasswd.php?leftmenu=export",$langs->trans("Filehtpasswd"),1,$user->rights->adherent->export);
                if ($leftmenu=="export") $newmenu->add("/adherents/cartes/carte.php?leftmenu=export",$langs->trans("MembersCards"),1,$user->rights->adherent->export);

                // Type
                $newmenu->add("/adherents/type.php?leftmenu=setup&amp;mainmenu=members",$langs->trans("MembersTypes"),0,$user->rights->adherent->configurer, '', $mainmenu, 'setup');
                $newmenu->add("/adherents/type.php?leftmenu=setup&amp;mainmenu=members&amp;action=create",$langs->trans("New"),1,$user->rights->adherent->configurer);
                $newmenu->add("/adherents/type.php?leftmenu=setup&amp;mainmenu=members",$langs->trans("List"),1,$user->rights->adherent->configurer);
            }

        }

        // Add personalized menus and modules menus
        require_once DOL_DOCUMENT_ROOT.'/core/class/menubase.class.php';

        $tabMenu=array();
        $menuArbo = new Menubase($db,'eldy','left');
        $newmenu = $menuArbo->menuLeftCharger($newmenu,$mainmenu,$leftmenu,(empty($user->societe_id)?0:1),'eldy',$tabMenu);
    }


    //var_dump($menu_array_before);exit;
    //var_dump($menu_array_after);exit;
    $menu_array=$newmenu->liste;
    if (is_array($menu_array_before)) $menu_array=array_merge($menu_array_before, $menu_array);
    if (is_array($menu_array_after))  $menu_array=array_merge($menu_array, $menu_array_after);
    //var_dump($menu_array);exit;

    // Show menu
    $alt=0;
    if (is_array($menu_array))
    {
        $num=count($menu_array);
    	for ($i = 0; $i < $num; $i++)
        {
        	$showmenu=true;
        	if (! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED) && empty($menu_array[$i]['enabled'])) {
        		$showmenu=false;
        	}

            $alt++;
            if (empty($menu_array[$i]['level']) && $showmenu)
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

            // For external modules
            $url = dol_buildpath($menu_array[$i]['url'], 1);

            print '<!-- Add menu entry with mainmenu='.$menu_array[$i]['mainmenu'].', leftmenu='.$menu_array[$i]['leftmenu'].', level='.$menu_array[$i]['level'].' -->'."\n";

            // Menu niveau 0
            if ($menu_array[$i]['level'] == 0)
            {
                if ($menu_array[$i]['enabled'])
                {
                    print '<div class="menu_titre">'.$tabstring.'<a class="vmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>'.$menu_array[$i]['titre'].'</a></div>'."\n";
                }
                else if ($showmenu)
                {
                    print '<div class="menu_titre">'.$tabstring.'<font class="vmenudisabled">'.$menu_array[$i]['titre'].'</font></div>'."\n";
                }
                if ($showmenu)
                	print '<div class="menu_top"></div>'."\n";
            }
            // Menu niveau > 0
            if ($menu_array[$i]['level'] > 0)
            {
                if ($menu_array[$i]['enabled'])
                {
                	print '<div class="menu_contenu">'.$tabstring;
                    if ($menu_array[$i]['url']) print '<a class="vsmenu" href="'.$url.'"'.($menu_array[$i]['target']?' target="'.$menu_array[$i]['target'].'"':'').'>';
                    print $menu_array[$i]['titre'];
                    if ($menu_array[$i]['url']) print '</a>';
                    // If title is not pure text and contains a table, no carriage return added
                    if (! strstr($menu_array[$i]['titre'],'<table')) print '<br>';
                    print '</div>'."\n";
                }
                else if ($showmenu)
                {
                	print '<div class="menu_contenu">'.$tabstring.'<font class="vsmenudisabled">'.$menu_array[$i]['titre'].'</font><br></div>'."\n";
                }
            }

            // If next is a new block or end
            if (empty($menu_array[$i+1]['level']))
            {
                if ($showmenu)
                	print '<div class="menu_end"></div>'."\n";
                print "</div>\n";
            }
        }
    }

    return count($menu_array);
}


/**
 * Function to test if an entry is enabled or not
 *
 * @param	string		$type_user					0=We need backoffice menu, 1=We need frontoffice menu
 * @param	array		&$menuentry					Array for menu entry
 * @param	array		&$listofmodulesforexternal	Array with list of modules allowed to external users
 * @return	int										0=Hide, 1=Show, 2=Show gray
 */
function dol_eldy_showmenu($type_user, &$menuentry, &$listofmodulesforexternal)
{
	//print 'type_user='.$type_user.' module='.$menuentry['module'].' enabled='.$menuentry['enabled'].' perms='.$menuentry['perms'];
	//print 'ok='.in_array($menuentry['module'], $listofmodulesforexternal);

	if (empty($menuentry['enabled'])) return 0;	// Entry disabled by condition
	if ($type_user && $menuentry['module'])
	{
		$tmploops=explode('|',$menuentry['module']);
		$found=0;
		foreach($tmploops as $tmploop)
		{
			if (in_array($tmploop, $listofmodulesforexternal)) {
				$found++; break;
			}
		}
		if (! $found) return 0;	// Entry is for menus all excluded to external users
	}
	if (! $menuentry['perms'] && $type_user) return 0; 											// No permissions and user is external
	if (! $menuentry['perms'] && ! empty($conf->global->MAIN_MENU_HIDE_UNAUTHORIZED))	return 0;	// No permissions and option to hide when not allowed, even for internal user, is on
	if (! $menuentry['perms']) return 2;															// No permissions and user is external
	return 1;
}

?>
