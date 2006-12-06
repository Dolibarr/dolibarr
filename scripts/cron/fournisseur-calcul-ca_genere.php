<?PHP
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Calcul le CA généré par chaque fournisseur
 *
 */
require ("../../htdocs/master.inc.php");

$verbose = 0;

for ($i = 1 ; $i < sizeof($argv) ; $i++)
{
  if ($argv[$i] == "-v")
    {
      $verbose = 1;
    }
  if ($argv[$i] == "-vv")
    {
      $verbose = 2;
    }
  if ($argv[$i] == "-vvv")
    {
      $verbose = 3;
    }
}

$now = time();
$year = strftime('%Y',$now);

$fournisseurs = array();
$products = array();
$real_products = array();
/*
 *
 *
 */
$sql  = "SELECT fk_product,fk_soc ";
$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur";

$resql = $db->query($sql) ;

if ($resql)
{
  while ($row = $db->fetch_row($resql))
    {
      $products[$row[0]] = $row[1];
    }
  $db->free($resql);
}
else
{
  print $sql;
}
/*
 * Recuperation des id produits en lieu et place
 * des id de sous-produits
 */
foreach($products as $key => $value)
{
  $sql = "SELECT fk_product ";
  $sql.= " FROM ".MAIN_DB_PREFIX."product_subproduct";
  $sql.= " WHERE fk_product_subproduct ='".$key."';";
  
  $resql = $db->query($sql) ;

  if ($resql)
    {
      if ($db->num_rows($resql) > 0)
	{
	  $row = $db->fetch_row($resql);
	  $real_products[$row[0]] = $value;
	}
      else
	{
   	  $real_products[$key] = $value;
	}
      $db->free($resql);
    }
  else
    {
      print $sql;
    }
}

/*
 * Recuperation des id produits en lieu et place
 * des id de sous-produits
 */
$ca_products = array();
$ca_fourns = array();
foreach($real_products as $key => $value)
{
  $sql = "SELECT sum(total_ht) ";
  $sql.= " FROM ".MAIN_DB_PREFIX."facturedet";
  $sql.= " WHERE fk_product ='".$key."';";
  
  $resql = $db->query($sql) ;

  if ($resql)
    {

      $row = $db->fetch_row($resql);
      $ca_products[$key] = $row[0];
      $ca_fourns[$value] += $row[0];

      $db->free($resql);
    }
  else
    {
      print $sql;
    }
}

foreach($ca_fourns as $key => $value)
{
  $sqld = "DELETE FROM ".MAIN_DB_PREFIX."fournisseur_ca";
  $sqld .= " WHERE year = $year AND fk_societe=$key;";
  
  $resqld = $db->query($sqld);

  $sqli = "INSERT INTO ".MAIN_DB_PREFIX."fournisseur_ca";
  $sqli .= " VALUES ($key,now(),$year,'".str_replace(',','.',$value)."');";
  
  $resqli = $db->query($sqli);
}

?>
