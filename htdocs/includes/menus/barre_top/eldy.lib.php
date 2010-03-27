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

	print '<table class="tmenu" summary="topmenu"><tr class="tmenu">';

	// Home
	$class="";
	if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "home")
	{
		$class='class="tmenusel"'; $_SESSION['idmenu']='';
	}
	else
	{
		$class = 'class="tmenu"';
	}
	$id='mainmenu_home';$idsel='home';
	print '<td class="tmenu" align="center" id="mainmenutd_'.$idsel.'">';
	print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
	print '<a '.$class.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("Home").'</a>';
	print '</td>';


	// Third parties
	if ($conf->societe->enabled || $conf->fournisseur->enabled)
	{
		$langs->load("companies");
		$langs->load("suppliers");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "companies")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_companies'; $idsel='company';
		if (($conf->societe->enabled && $user->rights->societe->lire)
		|| ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
		{
			print '<td class="tmenu" align="center" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$class.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("ThirdParties").'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">'.$langs->trans("ThirdParties").'</a>';
				print '</td>';
			}
		}
	}


	// Products-Services
	if ($conf->produit->enabled || $conf->service->enabled)
	{
		$langs->load("products");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "products")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}
		$chaine="";
		if ($conf->produit->enabled) { $chaine.=$langs->trans("Products"); }
		if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
		if ($conf->service->enabled) { $chaine.=$langs->trans("Services"); }

		$id='mainmenu_products'; $idsel='products';
		if ($user->rights->produit->lire || $user->rights->service->lire)
		{
			print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'"  id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$class.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$chaine.'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">'.$chaine.'</a>';
				print '</td>';
			}
		}
	}

	// Commercial
	if ($conf->societe->enabled)
	{
		$langs->load("commercial");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "commercial")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_commercial'; $idsel='commercial';
		if($user->rights->societe->lire || $user->rights->societe->contact->lire)
		{
			print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'" id="'.$id.'"></span></div>';
			print '<a '.$class.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("Commercial").'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">'.$langs->trans("Commercial").'</a>';
				print '</td>';
			}
		}
	}

	// Financial
	if ($conf->compta->enabled || $conf->accounting->enabled || $conf->banque->enabled
	|| $conf->facture->enabled || $conf->deplacement->enabled)
	{
		$langs->load("compta");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "accountancy")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_accountancy'; $idsel='accountancy';
		if ($user->rights->compta->resultat->lire || $user->rights->accounting->plancompte->lire
		|| $user->rights->facture->lire || $user->rights->banque->lire)
		{
			print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$class.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("MenuFinancial").'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">'.$langs->trans("MenuFinancial").'</a>';
				print '</td>';
			}
		}
	}

	// Projects
	if ($conf->projet->enabled)
	{
		$langs->load("projects");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "project")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_project'; $idsel='project';
		if ($user->rights->projet->lire)
		{
			print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$class.' id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("Projects").'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">'.$langs->trans("Projects").'</a>';
				print '</td>';
			}
		}
	}

	// Tools
	if ($conf->mailing->enabled || $conf->export->enabled || $conf->import->enabled || $conf->global->MAIN_MODULE_DOMAIN)
	{
		$langs->load("other");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "tools")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_tools'; $idsel='tools';
		if ($user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire || $user->rights->import->run)
		{
			print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$class.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("Tools").'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">'.$langs->trans("Tools").'</a>';
				print '</td>';
			}
		}
	}

	// OSCommerce 1
	if (! empty($conf->boutique->enabled))
	{
		$langs->load("shop");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "shop")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_shop'; $idsel='shop';
		print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
		print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a '.$class.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("OSCommerce").'</a>';
		print '</td>';
	}

	// OSCommerce WS
	if (! empty($conf->oscommercews->enabled))
	{
		$langs->load("shop");

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "shop")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_shop'; $idsel='shop';
		print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
		print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
		print '<a '.$class.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("OSCommerce").'</a>';
		print '</td>';
	}

	// Members
	if ($conf->adherent->enabled)
	{
		// $langs->load("members"); Added in main file

		$class="";
		if ($_SESSION["mainmenu"] && $_SESSION["mainmenu"] == "members")
		{
			$class='class="tmenusel"'; $_SESSION['idmenu']='';
		}
		else
		{
			$class = 'class="tmenu"';
		}

		$id='mainmenu_members'; $idsel='members';
		if ($user->rights->adherent->lire)
		{
			print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
			print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
			print '<a '.$class.'  id="mainmenua_'.$idsel.'" href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($atarget?" target=$atarget":"").'>'.$langs->trans("MenuMembers").'</a>';
			print '</td>';
		}
		else
		{
			if (! $hideifnotallowed)
			{
				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a class="tmenudisabled"  id="mainmenua_'.$idsel.'" href="#">'.$langs->trans("MenuMembers").'</a>';
				print '</td>';
			}
		}
	}


	// Show personalized menus
	require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");

	$menuArbo = new Menubase($db,'eldy','top');

	$tabMenu = $menuArbo->menuTopCharger($hideifnotallowed,$_SESSION['mainmenu'],'eldy');

	for($i=0; $i<count($tabMenu); $i++)
	{
		if ($tabMenu[$i]['enabled'] == true)
		{
			//var_dump($tabMenu[$i]);

			$idsel=(empty($tabMenu[$i]['mainmenu'])?'none':$tabMenu[$i]['mainmenu']);
			$id='mainmenu_'.$idsel;
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
				if (! empty($_SESSION['idmenu']) && $tabMenu[$i]['rowid'] == $_SESSION['idmenu']) $class='class="tmenusel"';
				else if (! empty($_SESSION['mainmenu']) && $tabMenu[$i]['mainmenu'] == $_SESSION['mainmenu']) $class='class="tmenusel"';
				else $class='class="tmenu"';

				print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
				print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
				print '<a '.$class.' id="mainmenua_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":($atarget?" target=$atarget":"")).'>';
				print $tabMenu[$i]['titre'];
				print '</a>';
				print '</td>';
			}
			else
			{
				if (! $hideifnotallowed)
				{
					print '<td class="tmenu" id="mainmenutd_'.$idsel.'">';
					print '<div class="'.$id.'"><span class="'.$id.'" id="mainmenuspan_'.$idsel.'"></span></div>';
					print '<a class="tmenudisabled" id="mainmenua_'.$idsel.'" href="#">'.$tabMenu[$i]['titre'].'</a>';
					print '</td>';
				}
			}
		}
	}

	print '</tr></table>';
	print "\n";
}

?>
