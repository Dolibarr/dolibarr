<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *		\file       htdocs/includes/menus/barre_top/eldy_backoffice.php
 *		\brief      Gestionnaire nomme eldy du menu du haut
 *		\version    $Id$
 *
 *		\remarks    La construction d'un gestionnaire pour le menu du haut est simple:
 *		\remarks    Toutes les entrees de menu a faire apparaitre dans la barre du haut
 *		\remarks    doivent etre affichees par <a class="tmenu" href="...?mainmenu=...">...</a>
 *		\remarks    ou si menu selectionne <a class="tmenusel" href="...?mainmenu=...">...</a>
 */


/**
 *      \class      MenuTop
 *	    \brief      Class to manage top menu Eldy (for internal users)
 */
class MenuTop {

	var $require_left=array("eldy_backoffice");     // Si doit etre en phase avec un gestionnaire de menu gauche particulier
	var $hideifnotallowed=0;						// Put 0 for back office menu, 1 for front office menu
	var $atarget="";                                // Valeur du target a utiliser dans les liens


	/**
	 *    \brief      Constructeur
	 *    \param      db      Handler d'acces base de donnee
	 */
	function MenuTop($db)
	{
		$this->db=$db;
	}


	/**
	 *    \brief      Show menu
	 */
	function showmenu()
	{
		global $user,$conf,$langs,$dolibarr_main_db_name;;

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
		$idsel='id="mainmenu_home" ';
		print '<td class="tmenu">';
		print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/index.php?mainmenu=home&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Home").'</a>';
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

			$idsel='id="mainmenu_companies" ';
			if (($conf->societe->enabled && $user->rights->societe->lire)
			|| ($conf->fournisseur->enabled && $user->rights->fournisseur->lire))
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/index.php?mainmenu=companies&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("ThirdParties").'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$langs->trans("ThirdParties").'</a>';
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

			$idsel='id="mainmenu_products" ';
			if ($user->rights->produit->lire || $user->rights->service->lire)
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$chaine.'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$chaine.'</a>';
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

			$idsel='id="mainmenu_commercial" ';
			if($user->rights->societe->lire || $user->rights->societe->contact->lire)
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Commercial").'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$langs->trans("Commercial").'</a>';
					print '</td>';
				}
			}
		}

		// Financial
		if ($conf->compta->enabled || $conf->comptaexpert->enabled || $conf->banque->enabled
		|| $conf->facture->enabled)
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

			$idsel='id="mainmenu_accountancy" ';
			if ($user->rights->compta->resultat->lire || $user->rights->comptaexpert->plancompte->lire
			|| $user->rights->facture->lire || $user->rights->banque->lire)
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("MenuFinancial").'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$langs->trans("MenuFinancial").'</a>';
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

			$idsel='id="mainmenu_project" ';
			if ($user->rights->projet->lire)
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/projet/index.php?mainmenu=project&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Projects").'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$langs->trans("Projects").'</a>';
					print '</td>';
				}
			}
		}

		// Tools
		if ($conf->mailing->enabled || $conf->export->enabled || $conf->global->MAIN_MODULE_IMPORT || $conf->global->MAIN_MODULE_DOMAIN)
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

			$idsel='id="mainmenu_tools" ';
			if ($user->rights->mailing->lire || $user->rights->bookmark->lire || $user->rights->export->lire)
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/index.php?mainmenu=tools&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Tools").'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$langs->trans("Tools").'</a>';
					print '</td>';
				}
			}
		}

		// Telephonie
		if (! empty($conf->telephonie->enabled) && $user->rights->telephonie->lire)
		{
			$class="";
			if (ereg("^".DOL_URL_ROOT."\/telephonie\/",$_SERVER["PHP_SELF"]))
			{
				$class='class="tmenusel"'; $_SESSION['idmenu']='';
			}
			else
			{
				$class = 'class="tmenu"';
			}

			$idsel='id="mainmenu_telephonie" ';
			print '<td class="tmenu">';
			print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/telephonie/index.php?mainmenu=telephonie"'.($this->atarget?" target=$this->atarget":"").'>Telephonie</a>';
			print '</td>';
		}

		// Energie
		if (! empty($conf->energie->enabled))
		{
			$langs->load("energy");
			$class="";
			if (ereg("^".DOL_URL_ROOT."\/energie\/",$_SERVER["PHP_SELF"]))
			{
				$class='class="tmenusel"'; $_SESSION['idmenu']='';
			}
			else
			{
				$class = 'class="tmenu"';
			}

			$idsel='id="mainmenu_energie" ';
			print '<td class="tmenu">';
			print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/energie/index.php?mainmenu=energie"'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("Energy").'</a>';
			print '</td>';
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

			$idsel='id="mainmenu_shop" ';
			print '<td class="tmenu">';
			print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/boutique/index.php?mainmenu=shop&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("OSCommerce").'</a>';
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

			$idsel='id="mainmenu_shop" ';
			print '<td class="tmenu">';
			print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/oscommerce_ws/index.php?mainmenu=shop&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("OSCommerce").'</a>';
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

			$idsel='id="mainmenu_members" ';
			if ($user->rights->adherent->lire)
			{
				print '<td class="tmenu">';
				print '<a '.$class.' '.$idsel.'href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members&amp;leftmenu="'.($this->atarget?" target=$this->atarget":"").'>'.$langs->trans("MenuMembers").'</a>';
				print '</td>';
			}
			else
			{
				if (! $this->hideifnotallowed)
				{
					print '<td class="tmenu">';
					print '<a class="tmenudisabled" '.$idsel.'href="#">'.$langs->trans("MenuMembers").'</a>';
					print '</td>';
				}
			}
		}

		// Affichage des menus personnalises
		require_once(DOL_DOCUMENT_ROOT."/core/menubase.class.php");

		$menuArbo = new Menubase($this->db,'eldy','top');
		$tabMenu = $menuArbo->menuTopCharger($this->hideifnotallowed,$_SESSION['mainmenu'],'eldy');

		for($i=0; $i<count($tabMenu); $i++)
		{
			if ($tabMenu[$i]['enabled'] == true)
			{
				$idsel=(empty($tabMenu[$i]['mainmenu'])?'none':$tabMenu[$i]['mainmenu']);
				if ($tabMenu[$i]['right'] == true)	// Is allowed
				{
					if (eregi($tabMenu[$i]['url'],"^(http:\/\/|https:\/\/)"))
					{
						$url = $tabMenu[$i]['url'];
					}
					else
					{
						$url=DOL_URL_ROOT.$tabMenu[$i]['url'];
						if (! eregi('\?',$url)) $url.='?';
						else $url.='&';
						if (! eregi('mainmenu',$url) || ! eregi('leftmenu',$url))
						{
							$url.='mainmenu='.$tabMenu[$i]['mainmenu'].'&leftmenu=&';
						}
						$url.="idmenu=".$tabMenu[$i]['rowid'];
					}
					if (! empty($_SESSION['idmenu']) && $tabMenu[$i]['rowid'] == $_SESSION['idmenu']) $class='class="tmenusel"';
					else $class='class="tmenu"';
					print '<td class="tmenu" id="td_'.$idsel.'">';
					print '<a '.$class.' id="mainmenu_'.$idsel.'" href="'.$url.'"'.($tabMenu[$i]['atarget']?" target='".$tabMenu[$i]['atarget']."'":"").'>';
					print $tabMenu[$i]['titre'];
					print '</a>';
					print '</td>';
				}
				else
				{
					if (! $this->hideifnotallowed)
					{
						print '<td class="tmenu" id="td_'.$idsel.'">';
						print '<a class="tmenudisabled" id="mainmenu_'.$idsel.'" href="#">'.$tabMenu[$i]['titre'].'</a>';
						print '</td>';
					}
				}
			}
		}

		print '</tr></table>';
	}

}

?>