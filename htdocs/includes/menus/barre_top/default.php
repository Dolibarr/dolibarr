<?PHP
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

$nbofentries=0;
if ($conf->commercial->enabled)   $nbofentries++;
if ($conf->adherent->enabled)     $nbofentries++;
if ($conf->compta->enabled || $conf->banque->enabled || $conf->caisse->enabled) $nbofentries++; 
if ($conf->produit->enabled || $conf->service->enabled) $nbofentries++; 
if ($conf->webcal->enabled)   $nbofentries++; 

print '<table cellpadding="0" cellspacing="0" width="100%"><tr>';

if (! $nbofentries) {
  print '<td>&nbsp;</td>';
}
else
{
  $widthtd=floor(100/$nbofentries);
  
  if ($conf->commercial->enabled)
    {
      $class="";
      if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "commercial") { $class="menusel"; }
      elseif (ereg("^".DOL_URL_ROOT."\/comm\/",$_SERVER["PHP_SELF"])) { $class="menusel"; }
      print '<td class="'.$class.'" width="'.$widthtd.'%" align=center>';
      print '<a class="'.$class.'" href="'.DOL_URL_ROOT.'/comm/index.php"'.($target?" target=$target":"").'>Commercial</A>';
      print '</td>';
    }
    
    if ($conf->adherent->enabled)
    {
      $class="";
      if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "adherent") { $class="menusel"; }
      elseif (ereg("^".DOL_URL_ROOT."\/adherents\/",$_SERVER["PHP_SELF"])) { $class="menusel"; }
      print '<td class="'.$class.'" width="'.$widthtd.'%" align=center>';
      print '<a class="'.$class.'" href="'.DOL_URL_ROOT.'/adherents/index.php"'.($target?" target=$target":"").'>Adhérents</A>';
      print '</td>';
    }
    
    if ($conf->compta->enabled || $conf->banque->enabled || $conf->caisse->enabled)
    {
      $class="";
      if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "compta") { $class="menusel"; }
      elseif (ereg("^".DOL_URL_ROOT."\/compta\/",$_SERVER["PHP_SELF"])) { $class="menusel"; }
      print '<td class="'.$class.'" width="'.$widthtd.'%" align=center>';
      print '<a class="'.$class.'" href="'.DOL_URL_ROOT.'/compta/index.php"'.($target?" target=$target":"").'>Compta/Tréso</A>';
      print '</td>';
    }
    
    if ($conf->produit->enabled || $conf->service->enabled) 
    {
      $class="";
      if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "product") { $class="menusel"; }
      elseif (ereg("^".DOL_URL_ROOT."\/product\/",$_SERVER["PHP_SELF"])) { $class="menusel"; }
      $chaine="";
      if ($conf->produit->enabled) { $chaine.="Produits"; }
      if ($conf->produit->enabled && $conf->service->enabled) { $chaine.="/"; }
      if ($conf->service->enabled) { $chaine.="Services"; }
      print '<td class="'.$class.'" width="'.$widthtd.'%" align=center>';
      print '<a class="'.$class.'" href="'.DOL_URL_ROOT.'/product/?type=0"'.($target?" target=$target":"").'>'.$chaine.'</a>';
      print '</td>';
    }
    
    if ($conf->webcal->enabled)
    {
      $class="";
      if ($_SESSION["topmenu"] && $_SESSION["topmenu"] == "webcalendar") { $class="menusel"; }
      elseif (ereg("^".DOL_URL_ROOT."\/projet\/",$_SERVER["PHP_SELF"]) || ereg("^".DOL_URL_ROOT."\/webcalendar\/",$_SERVER["PHP_SELF"])) { $class="menusel"; }
      print '<td class="'.$class.'" width="'.$widthtd.'%" align=center>';
//      print '<a class="'.$class.'" href="'. PHPWEBCALENDAR_URL .'">Calendrier</a>';
      print '<a class="'.$class.'" href="'.DOL_URL_ROOT.'/projet/webcal.php"'.($target?" target=$target":"").'>Calendrier</a>';
      print '</td>';
    };
    
}

print '</tr></table>';

?>
