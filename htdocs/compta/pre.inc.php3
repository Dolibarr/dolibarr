<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org> 
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
require("../main.inc.php3");

$strmonth[1] = "Janvier";
$strmonth[2] = "F&eacute;vrier";  
$strmonth[3] = "Mars";  
$strmonth[4] = "Avril";  
$strmonth[5] = "Mai"; 
$strmonth[6] = "Juin"; 
$strmonth[7] = "Juillet";  
$strmonth[8] = "Ao&ucirc;t"; 
$strmonth[9] = "Septembre";
$strmonth[10] = "Octobre";
$strmonth[11] = "Novembre";
$strmonth[12] = "D&eacute;cembre";

function llxHeader($head = "") {
  global $user, $conf;


  /*
   *
   *
   */
  top_menu($head);

  $menu = new Menu();

  $menu->add("/compta/facture.php3","Factures");
  $menu->add_submenu("paiement.php3","Paiements");

  $menu->add("charges/index.php3","Charges");
  $menu->add_submenu("sociales/","Prest. Sociales");

  $menu->add("ca.php3","Chiffres d'affaires");

  $menu->add_submenu("prev.php3","Prévisionnel");
  $menu->add_submenu("comp.php3","Comparatif");
  $menu->add_submenu("exercices.php3","Exercices");

  $menu->add_submenu("casoc.php3","Par société");
  //  $menu->add_submenu("pointmort.php3","Point mort");

  $menu->add("tva/index.php3","TVA");

  $menu->add("resultat/","Résultats");

  $menu->add("/compta/propal.php3","Propales");

  $menu->add("bank/index.php3","Bank");

  if ($conf->voyage) 
    {

      $menu->add("voyage/index.php3","Voyages");

      $menu->add_submenu("voyage/index.php3","Voyages");
      $menu->add_submenu("voyage/reduc.php3","Reduc");
    }

  $menu->add("ligne.php3","Compta");
  $menu->add_submenu("ligne.php3","Lignes");
  $menu->add_submenu("config.php3","Configuration");


  if ($user->compta > 0) 
    {

    } 
  else 
    {
      $menu->clear();
      $menu->add("/index.php3","Accueil");      
    }

  left_menu($menu->liste);

}

?>
