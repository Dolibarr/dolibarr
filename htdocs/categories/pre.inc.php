<?php
/* Copyright (C) 2005 Matthieu Valleton <mv@seeschloss.org>
 * Copyright (C) 2005 Davoleau Brice <brice.davoleau@gmail.com>
 * Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
  \file       htdocs/categories/pre.inc.php
  \ingroup    product,service
  \brief      Fichier gestionnaire du menu gauche des produits et services
  \version    $Id$
*/

require "../main.inc.php";
require_once DOL_DOCUMENT_ROOT."/categories/categorie.class.php";

$langs->load("categories");

function llxHeader ($head = "", $urlp = "", $title="")
{
  global $user, $conf, $langs;

  
  top_menu($head, $title);
  
  $menu = new Menu();
  
	if ($conf->categorie->enabled)
	{
		$langs->load("customers");
		$langs->load("suppliers");
		$menu->add(DOL_URL_ROOT."/categories/index.php?type=0", $langs->trans("ProductsCategoriesShort"));
		$menu->add_submenu(DOL_URL_ROOT."/categories/liste.php?type=0", $langs->trans("List"));
		if ($user->rights->categorie->creer)
		{
			$menu->add_submenu(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=0", $langs->trans("NewCat"));
		}
		$menu->add(DOL_URL_ROOT."/categories/index.php?type=1", $langs->trans("SuppliersCategoriesShort"));
		$menu->add_submenu(DOL_URL_ROOT."/categories/liste.php?type=1", $langs->trans("List"));
		if ($user->rights->categorie->creer)
		{
			$menu->add_submenu(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=1", $langs->trans("NewCat"));
		}
		$menu->add(DOL_URL_ROOT."/categories/index.php?type=2", $langs->trans("CustomersCategoriesShort"));
		$menu->add_submenu(DOL_URL_ROOT."/categories/liste.php?type=2", $langs->trans("List"));
		if ($user->rights->categorie->creer)
		{
			$menu->add_submenu(DOL_URL_ROOT."/categories/fiche.php?action=create&amp;type=2", $langs->trans("NewCat"));
		}
	}
  
  left_menu($menu->liste);
}
?>
