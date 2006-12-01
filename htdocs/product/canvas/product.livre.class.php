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
 */

/**
   \file       htdocs/product/canvas/product.livre.class.php
   \ingroup    produit
   \brief      Fichier de la classe des produits specifiques de type livre
   \version    $Revision$
*/


/**
   \class      ProductLivre
   \brief      Classe permettant la gestion des livres, cette classe surcharge la classe produit
*/

class ProductLivre extends Product
{
  /**
   *    \brief      Constructeur de la classe
   *    \param      DB          Handler accès base de données
   *    \param      id          Id produit (0 par defaut)
   */
  function ProductLivre($DB, $id=0)
  {
    $this->db = $DB;
    $this->id = $id ;
    $this->canvas = "livre";
  }
  /**
   *    \brief      Lecture des donnees dans la base
   *    \param      id          Id livre ('' par defaut)
   *    \param      ref         Reference du livre ('' par defaut)
   */
  function FetchCanvas($id='', $ref='')
  {
    $result = $this->fetch($id,$ref);

    if ($result >= 0)
      {
	$sql = "SELECT rowid,isbn,ean,pages,px_feuillet";

	$sql.= " FROM ".MAIN_DB_PREFIX."product_cnv_livre";
	if ($id) $sql.= " WHERE rowid = '".$id."'";
	if ($ref) $sql.= " WHERE ref = '".addslashes($ref)."'";
	
	$result = $this->db->query($sql) ;

	if ( $result )
	  {
	    $result = $this->db->fetch_array();

	    $this->isbn               = $result["isbn"];
	    $this->ean                = $result["ean"];
	    $this->pages              = $result["pages"];
	    $this->px_feuillet        = $result["px_feuillet"];

	    $this->db->free();
	  }

      }

    return $result;
  }
  /**
   *    \brief      Mise a jour des donnees dans la base
   *    \param      datas        Tableau de donnees
   */
  function UpdateCanvas($datas)
  {
    $isbna = trim($datas["isbna"]);
    $isbnb = trim($datas["isbnb"]);

    $sp = 9 - (strlen($isbna) + strlen($isbnb) );
    $isbnc = substr( str_repeat('0',10) . $datas["isbnc"], -$sp , $sp); // on complete a 10

    $key = $this->calculate_isbn_key($isbna.$isbnb.$isbnc);

    $isbn = $isbna.'-'.$isbnb.'-'.$isbnc.'-'.$key;

    $ean = '978'.$isbna.$isbnb.$isbnc;

    $ean = $ean . $this->calculate_ean_key($ean);

    $pages = trim($datas["pages"]);
    $px_feuillet = trim($datas["px_feuillet"]);

    $sql = "UPDATE ".MAIN_DB_PREFIX."product_cnv_livre ";
    $sql .= " SET isbn = '$isbn'";
    $sql .= " , ean = '$ean'";
    $sql .= " , pages = '$pages'";
    $sql .= " , px_feuillet = '$px_feuillet'";
    $sql .= " WHERE rowid = " . $this->id;

    if ( $this->db->query($sql) )
      {

      }
  }
  /**
   *    \brief      Calcule la clef d'un numero ISBN
   *    \param      isbn        Clef International Standard Book Number
   *    \note       source http://fr.wikipedia.org/wiki/ISBN
   */
  function calculate_isbn_key($isbn)
  {
    $sum = 0;
    for ($i = 0 ; $i < 9 ; $i++)
      {
	$sum += $isbn{$i} * (10 - $i);
      }

    $key = 11 - ($sum % 11);

    if ($key == 0)
      $key = 1;

    if ($key == 11)
      $key = 'X';

    return $key;
  }
  /**
   *    \brief      Calcule la clef d'un numero EAN 13
   *    \param      ean        Clef EAN
   *    \note       source http://fr.wikipedia.org/wiki/ISBN
   */
  function calculate_ean_key($ean)
  {
    $sum = 0;
    for ($i = 0 ; $i < 12 ; $i = $i+2)
      {
	$sum += $ean{$i};
      }
    for ($i = 1 ; $i < 12 ; $i = $i+2)
      {
	$sum += 3 * $ean{$i};
      }

    $key = ($sum % 10);

    return $key;
  }
   /**
   *    \brief      Assigne les valeurs pour les templates Smarty
   *    \param      smarty     Instance de smarty
   */
  function assign_values(&$smarty)
  {
    $smarty->assign('prod_canvas',  'livre');
    $smarty->assign('prod_id',      $this->id);

    $smarty->assign('prod_isbn',    $this->isbn);

    $isbn_parts = explode('-',$this->isbn);
    
    $smarty->assign('prod_isbna',    $isbn_parts[0]);
    $smarty->assign('prod_isbnb',    $isbn_parts[1]);
    $smarty->assign('prod_isbnc',    $isbn_parts[2]);

    $smarty->assign('prod_ean',     $this->ean);

    $smarty->assign('prod_isbn13',  '978-'.substr($this->isbn,0,12).substr($this->ean,-1,1));

    $smarty->assign('prod_ref',     $this->ref);
    $smarty->assign('prod_pages',   $this->pages);
    $smarty->assign('prod_pxfeuil', $this->px_feuillet);
    $smarty->assign('prod_pxvente', price($this->price));

    $smarty->assign('prod_label', $this->libelle);
  }

}
?>
