<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004          Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 */

/**
	    \file       htdocs/includes/menus/barre_top/esprit.php
		\brief      Gestionnaire du menu du haut spécialisé vente de CD/livres
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu a faire apparaitre dans la barre du 
        \remarks    du haut doit etre affichée par <a class="tmenu" href="lien">Nom</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionné.
*/

print '<a class="tmenu" href="/boutique/livre/">livres</a>';

print '<a class="tmenu" href="/boutique/client/">clients</a>';

print '<a class="tmenu" href="/product/critiques/">critiques</a>';

print '<a class="tmenu" href="/product/categorie/">catégories</a>';

?>
