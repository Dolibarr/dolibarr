<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * $Source$
 */

/**
        \file       htdocs/fourn/product/pre.inc.php
        \ingroup    product,service
        \brief      Fichier gestionnaire du menu gauche des produits et services fournisseurs
        \version    $Revision$
*/

require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.class.php");
require_once(DOL_DOCUMENT_ROOT."/categories/categorie.class.php");

$langs->load("suppliers");
$langs->load("products");


function llxHeader($head = "", $title="", $help_url='',$addons='')
{
    global $db, $user, $conf, $langs;


    top_menu($head, $title);

    $menu = new Menu();

    if (is_array($addons))
    {
        //$menu->add($url, $libelle);
        $menu->add($addons[0][0], $addons[0][1]);
    }

    if ($conf->produit->enabled)
    {
        $menu->add(DOL_URL_ROOT."/fourn/product/", $langs->trans("Products"));
        $menu->add_submenu(DOL_URL_ROOT."/fourn/product/liste.php?type=0", $langs->trans("List"));

        if ($user->societe_id == 0 && ($user->rights->produit->creer || $user->rights->service->creer))
        {
            $menu->add_submenu(DOL_URL_ROOT."/fourn/product/fiche.php?action=create&amp;type=0", $langs->trans("NewProduct"));
        }
    }

    if ($conf->categorie->enabled)
    {
        $menu->add(DOL_URL_ROOT."/categories/", $langs->trans("Categories"));
    }

    $menu->add('liste.php','Top');

    if (isset($_REQUEST['catid']) && $_REQUEST['catid'])
    {
        $catid = $_REQUEST['catid'];

        $c = new Categorie ($db, $catid);

        $menu->add('liste.php?catid='.$c->id, $c->label);

        $cats = $c->get_filles();

        if (sizeof ($cats) > 0)
        {
            foreach ($cats as $cat)
            {
                $menu->add_submenu('liste.php?catid='.$cat->id, $cat->label);
            }
        }
    }
    else
    {
        $c = new Categorie ($db);
        $cats = $c->get_main_categories();

        if (sizeof ($cats) > 0)
        {
            foreach ($cats as $cat)
            {
                $menu->add_submenu('liste.php?catid='.$cat->id, $cat->label);
            }
        }
    }

    left_menu($menu->liste, $help_url);
}

?>
