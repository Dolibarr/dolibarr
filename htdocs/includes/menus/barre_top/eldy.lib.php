<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file		htdocs/includes/menus/barre_top/eldy.lib.php
 *  \brief		Library for file eldy menus
 *  \version	$Id$
 */


/**
 * Core function to output top menu eldy
 *
 * @param unknown_type $db
 * @param unknown_type $atarget
 * @param unknown_type $hideifnotallowed
 */
function print_eldy_menu($db,$atarget,$hideifnotallowed)
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
	print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("ThirdParties"));
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'"  id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($chaine);
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="'.$id.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("Commercial"));
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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
	if ($conf->compta->enabled || $conf->accounting->enabled || $conf->banque->enabled
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("MenuFinancial"));
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("Projects"));
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("Tools"));
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
		print_text_menu_entry($langs->trans("OSCommerce"));
		print '</a>';
		print_end_menu_entry();
	}

	// OSCommerce WS
	if (! empty($conf->oscommercews->enabled))
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
		print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
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
			print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$classname.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($atarget?" target=$atarget":"").'>';
			print_text_menu_entry($langs->trans("MenuMembers"));
			print '</a>';
			print_end_menu_entry();
		}
		else
		{
			if (! $hideifnotallowed)
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

	$tabMenu = $menuArbo->menuTopCharger($hideifnotallowed,$_SESSION['mainmenu'],'eldy');

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
				print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a '.$classname.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print_text_menu_entry($tabMenu[$i]['titre']);
				print '</a>';
				print_end_menu_entry();
			}
			else
			{
				if (! $hideifnotallowed)
				{
					print_start_menu_entry($idsel);
					print '<div class="'.$id.' '.$idsel.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
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
	if (preg_match('/eldy/',$conf->top_menu)) print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';
	else print '<ul class="tmenu">';
}

function print_start_menu_entry($idsel)
{
	global $conf;
	if (preg_match('/eldy/',$conf->top_menu)) print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
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
	if (preg_match('/eldy/',$conf->top_menu)) print '</td>';
	else print '</li>';
	print "\n";
}

function print_end_menu_array()
{
	global $conf;
	if (preg_match('/eldy/',$conf->top_menu)) print '</tr></table>';
	else print '</ul>';
	print "\n";
}

?>
