<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * ATTENTION DE PAS EXECUTER CE SCRIPT SUR UNE INSTALLATION DE PRODUCTION
 *
 * Genere un nombre aleatoire de facture
 *
 */

require ("../htdocs/master.inc.php");
require_once ("../htdocs/facture.class.php");
require_once ("../htdocs/societe.class.php");

/*
 * Parametre
 */

define (GEN_NUMBER_FACTURE, 5);

/*
 *
 *
 */

$sql = "SELECT min(rowid) FROM ".MAIN_DB_PREFIX."user";
$resql = $db->query($sql);
if ($resql) 
{
  $row = $db->fetch_row($resql);
  $user = new User($db, $row[0]);
}

$socids = array();
$sql = "SELECT idp FROM ".MAIN_DB_PREFIX."societe WHERE client=1";
$resql = $db->query($sql);
if ($resql) 
{
  $num_socs = $db->num_rows($resql);
  $i = 0;
  while ($i < $num_socs)
    {
      $i++;
      
      $row = $db->fetch_row($resql);
      $socids[$i] = $row[0];
    }
}

$prodids = array();
$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product WHERE envente=1";
$resql = $db->query($sql);
if ($resql) 
{
  $num_prods = $db->num_rows($resql);
  $i = 0;
  while ($i < $num_prods)
    {
      $i++;
      
      $row = $db->fetch_row($resql);
      $prodids[$i] = $row[0];
    }
}

$i=0;
while ($i < GEN_NUMBER_FACTURE)
{
  $i++;
  $socid = rand(1, $num_socs);

  $facture = new Facture($db, $socids[$socid]);
  $facture->number = 'provisoire';
  $facture->date = time();
  $facture->cond_reglement = 3;
  $facture->mode_reglement = 3;

  $nbp = rand(1, 9);
  $xnbp = 0;

  while ($xnbp < $nbp)
    {
      $prodid = rand(1, $num_prods);
      $facture->add_product($prodids[$prodid], rand(1,5), 0);
      $xnbp++;
    }

  $facture->create($user);
  $facture->set_valid($facture->id,$user,$socid);
}


?>
