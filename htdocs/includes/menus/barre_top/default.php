<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
 *
 */

/**
	    \file       htdocs/includes/menus/barre_top/default.php
		\brief      Gestionnaire par défaut du menu du haut
		\version    $Revision$

        \remarks    La construction d'un gestionnaire pour le menu du haut est simple:
        \remarks    Toutes les entrées de menu à faire apparaitre dans la barre du haut
        \remarks    doivent être affichées par <a class="tmenu" href="...?mainmenu=...">...</a>
        \remarks    On peut éventuellement ajouter l'attribut id="sel" dans la balise <a>
        \remarks    quand il s'agit de l'entrée du menu qui est sélectionnée.
*/


if ($conf->adherent->enabled)
{
  $langs->load("members");
  
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "adherent")
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

  print '<a '.$class.' href="'.DOL_URL_ROOT.'/adherents/index.php?mainmenu=members"'.($target?" target=$target":"").'>'.$langs->trans("Members").'</a>';
}

if ($conf->commercial->enabled)
{
  $langs->load("commercial");
      
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "commercial")
    {
      $class='class="tmenu" id="sel"'; 
    }
  elseif (ereg("^".DOL_URL_ROOT."\/comm\/",$_SERVER["PHP_SELF"]))
    {
      $class='class="tmenu" id="sel"';
    }
  else
    {
      $class = 'class="tmenu"';
    }

  print '<a '.$class.' href="'.DOL_URL_ROOT.'/comm/index.php?mainmenu=commercial"'.($target?" target=$target":"").'>'.$langs->trans("Commercial").'</a>';

}

if ($conf->compta->enabled || $conf->banque->enabled || $conf->caisse->enabled)
{
  $langs->load("compta");
  
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "compta")
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

  print '<a '.$class.' href="'.DOL_URL_ROOT.'/compta/index.php?mainmenu=accountancy"'.($target?" target=$target":"").'>'.$langs->trans("Accountancy")."/".$langs->trans("Treasury").'</a>';

}

if ($conf->produit->enabled || $conf->service->enabled) 
{
  $langs->load("products");
  
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "product")
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
  if ($conf->service->enabled) { $chaine.="Services"; }
  

  print '<a '.$class.' href="'.DOL_URL_ROOT.'/product/index.php?mainmenu=products"'.($target?" target=$target":"").'>'.$chaine.'</a>';

}


if ($conf->fournisseur->enabled) 
{
  $langs->load("suppliers");
        
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "supplier")
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

  print '<a '.$class.' href="'.DOL_URL_ROOT.'/fourn/index.php?mainmenu=supplier"'.($target?" target=$target":"").'>'.$langs->trans("Suppliers").'</a>';
}


if ($conf->webcal->enabled)
{
  $langs->load("other");
  
  $class="";
  if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "webcalendar")
   {
      $class='class="tmenu" id="sel"';
   }
  elseif (ereg("^".DOL_URL_ROOT."\/projet\/",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/webcalendar\/",$_SERVER["PHP_SELF"]))
   {
      $class='class="tmenu" id="sel"';
   }
  else 
   {
      $class = 'class="tmenu"';
   }
  
  print '<a '.$class.' href="'.DOL_URL_ROOT.'/projet/webcal.php?mainmenu=webcal"'.($target?" target=$target":"").'>'.$langs->trans("Calendar").'</a>';
};





?>
