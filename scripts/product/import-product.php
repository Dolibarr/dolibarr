#!/usr/bin/php
<?php
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
 * Import des produits depuis un fichier XML
 * Ce script est un exemple et a pour vocation a servir de base pour le dev
 * de script personnalise, il utilise les donnes du catalogue de materiel.net
 *
 * Pour recupere les infos de materiel.net
 *
 * wget "http://materiel.net/partenaire/search.php3?format=xml&nobanner=1"
 */

$opt = getopt("f:u:");

$userid = $opt['u'];
$file = $opt['f'];

if (strlen(trim($file)) == 0 || strlen(trim($userid)) == 0)
{
  print "Usage :\n php import-product.php -f <filename> -i <id_fournisseur> -u <userid>\n";
  exit;
}
/*
 *
 *
 */
require("../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/product.class.php");

$user = new User($db);
$result = $user->fetch($userid);
if ($user->id == 0)
  die("Identifiant utilisateur incorrect : $userid\n");

$depth = array();
$index = 0;
$items = array();
$current = '';

/*
 * Parse le fichier XML et l'insère dans un tableau
 *
 */
$xml_parser = xml_parser_create();

xml_set_element_handler($xml_parser, "debutElement", "finElement");
xml_set_character_data_handler($xml_parser,"charData");

if (!($fp = fopen($file, "r"))) 
{
  die("Impossible d'ouvrir le fichier XML");
}

while ($data = fread($fp, 4096) ) 
{
  if (!xml_parse($xml_parser, $data, feof($fp))) 
    {
      die(sprintf("erreur XML : %s à la ligne %d",
		  xml_error_string(xml_get_error_code($xml_parser)),
		  xml_get_current_line_number($xml_parser)));
    }
}
xml_parser_free($xml_parser);
/*
 * Traite les données du tableau
 *
 */
if (sizeof($items) > 0)
{
  while ($item = array_pop($items) )
    {
      $product = new Product($db);
      $product->price_base_type = 'TTC';
      $product->price           = $item["price"];
      $product->ref             = $item["code"];
      $product->type            = 0;              // 0 produit, 1 service
      $product->libelle         = $item["code"];
      $product->description     = $item["code"];
      $product->status          = 1;              // 1 en vente, 0 hors vente
      $product->tva_tx          = '19.6';
      $product->Create($user);
    }
}

exit ;

/*
 * Fonctions
 *
 */

function charData($parser, $data)
{
  global $index, $current, $items;
  $char_data = trim($data);

  if($char_data)
    $char_data = preg_replace('/  */',' ',$data);

  if ($current <> '')
    $items[$index][$current] = $char_data;

}

function debutElement($parser, $name, $attrs)
{
  global $depth, $index, $items, $current;

  $depth[$parser]++;

  if ($name == 'ITEM')
    {
      $index++;
      $current = '';
    }
  elseif ($name == 'NAME')
    {
      $current = "name";
    }  
  elseif ($name == 'CODE')
    {
      $current = "code";
    }
  elseif ($name == 'PRICE')
    {
      $current = "price";
    }
  elseif ($name == 'GENRE')
    {
      $current = "genre";
    }
  else
    {
      $current = '';
    }
}

function finElement($parser, $name)
{
  global $depth;
  $depth[$parser]--;
}


?>
