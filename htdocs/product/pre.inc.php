<?php
/* Copyright (C) 2001-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2007      Auguria SARL <info@auguria.org>
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
   \file       htdocs/product/pre.inc.php
   \ingroup    product,service
   \brief      Fichier gestionnaire du menu gauche des produits et services
   \version    $Revision$
   \todo       Rodo - Gere les menus depuis les canvas
*/
require("../main.inc.php");

$langs->load("products");

$user->getrights('produit');
$user->getrights('propale');
$user->getrights('facture');

function llxHeader($head = "", $urlp = "", $title="")
{
	global $user, $conf, $langs;

	$user->getrights("produit");

	top_menu($head, $title);

	$menu = new Menu();

	if ($conf->produit->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/index.php?type=0", $langs->trans("Products"));
		$menu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=0", $langs->trans("List"));
		
		if ($user->societe_id == 0 && $user->rights->produit->creer)
		{
			$menu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=0", $langs->trans("NewProduct"));
		}
	}

	// Produit specifique
	$dir = DOL_DOCUMENT_ROOT . "/product/canvas/";
	if(is_dir($dir) && $conf->global->PRODUCT_CANVAS_ABILITY)
	{
		if ($handle = opendir($dir))
		{
			while (($file = readdir($handle))!==false)
			{
				if (substr($file, strlen($file) -10) == '.class.php' && substr($file,0,8) == 'product.')
				{
					$parts = explode('.',$file);
					$classname = 'Product'.ucfirst($parts[1]);		  
					require_once($dir.$file);		  
					$module = new $classname();
					
					if ($module->active === '1' && $module->menu_add === 1)
					{
						$module->PersonnalizeMenu($menu);
						$langs->load("products_".$module->canvas);
						for ($j = 0 ; $j < sizeof($module->menus) ; $j++)
						{
							$menu->add_submenu($module->menus[$j][0], $langs->trans($module->menus[$j][1]));
						}
					}
				}
			}
			closedir($handle);
		}
	}

	$menu->add_submenu(DOL_URL_ROOT."/product/reassort.php?type=0", $langs->trans("Restock"));

	if ($conf->service->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/index.php?type=1", $langs->trans("Services"));
		$menu->add_submenu(DOL_URL_ROOT."/product/liste.php?type=1", $langs->trans("List"));
		if ($user->societe_id == 0  && $user->rights->produit->creer)
		{
			$menu->add_submenu(DOL_URL_ROOT."/product/fiche.php?action=create&amp;type=1", $langs->trans("NewService"));
		}
	}

	if ($conf->fournisseur->enabled) {
		$langs->load("suppliers");
		$menu->add(DOL_URL_ROOT."/fourn/index.php", $langs->trans("Suppliers"));
	}

	$menu->add(DOL_URL_ROOT."/product/stats/", $langs->trans("Statistics"));
	if ($conf->propal->enabled)
	{
		$menu->add_submenu(DOL_URL_ROOT."/product/popuprop.php", $langs->trans("Popularity"));
	}

	if ($conf->stock->enabled)
	{
		$menu->add(DOL_URL_ROOT."/product/stock/", $langs->trans("Stock"));
	}

	if ($conf->categorie->enabled)
	{
		$langs->load("categories");
		$menu->add(DOL_URL_ROOT."/categories/index.php?type=0", $langs->trans("ProductsCategoriesShort"));
	}

	left_menu($menu->liste);
}
?>
