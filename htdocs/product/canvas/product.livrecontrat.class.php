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
   \class      ProductLivreContrat
   \brief      Classe permettant la gestion des livres, cette classe surcharge la classe produit
*/

class ProductLivreContrat extends Product
{
  /**
   *    \brief      Constructeur de la classe
   *    \param      DB          Handler accès base de données
   *    \param      id          Id produit (0 par defaut)
   */
  function ProductLivreContrat($DB=0, $id=0)
  {
    $this->db = $DB;
    $this->id = $id ;
    $this->canvas = "livrecontrat";
    $this->name = "livrecontrat";
    $this->description = "Gestion des contrats des livres";
    $this->active = PRODUIT_SPECIAL_LIVRECONTRAT;
    $this->menu_new = '';
    $this->menu_add = 0;
    $this->menu_clear = 1;
  }
  /**
   *    \brief      Creation
   *    \param      id          Id livre
   */
  function CreateCanvas($user,$livre_id,$datas)
  {
    $this->db->begin();

    $id = $this->create($user);

    if ($id > 0)
      {
	$error = 0;
      }

    if ( $error === 0 )
      {
	$sql = " INSERT INTO ".MAIN_DB_PREFIX."product_cnv_livre_contrat (rowid,fk_cnv_livre)";
	$sql.= " VALUES ('".$id."','".$livre_id."');";
	
	$result = $this->db->query($sql) ;
	if ($result)
	  {
	    $error = 0;
	  }
	else
	  {
	    $error = -6;
	  }
      }

    if ( $error === 0 )
      {
	$error = $this->UpdateCanvas($datas);
      }

    if ( $error === 0 )
      {
	$this->db->commit();
	return $this->id;
      }
    else
      {
	$this->db->rollback();
	return -1;
      }
  }
  /**
   *    \brief      Supression
   *    \param      id          Id livre
   */
  function DeleteCanvas($id)
  {
    $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_cnv_livre_contrat ";
    $sql.= " WHERE rowid = '".$id."';";

    $result = $this->db->query($sql) ;

    return 0;
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
	$sql = "SELECT taux,quantite,duree";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_cnv_livre_contrat";
	if ($id) $sql.= " WHERE rowid = '".$id."'";
	if ($ref) $sql.= " WHERE ref = '".addslashes($ref)."'";
	
	$result = $this->db->query($sql) ;

	if ( $result )
	  {
	    $result = $this->db->fetch_array();

	    $this->taux               = $result["taux"];
	    $this->quantite           = $result["quantite"];
	    $this->duree              = $result["duree"];

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
    $taux   = str_replace(',','.',abs(trim($datas["contrat_taux"])));
    $quant  = trim($datas["contrat_quant"]);
    $duree  = trim($datas["contrat_duree"]);

    $sql = "UPDATE ".MAIN_DB_PREFIX."product_cnv_livre_contrat ";
    $sql .= " SET taux    = '$taux'";
    $sql .= " , quantite  = '$quant'";
    $sql .= " , duree     = '$duree'";
    $sql .= " , date_app  = '$date_app'";
    $sql .= " WHERE rowid = " . $this->id;

    if ( $this->db->query($sql) )
      {
	$error = 0;
      }
    else
      {
	$error = -1;
      }

    return $error;
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
    
    $smarty->assign('prod_isbna',     $isbn_parts[0]);
    $smarty->assign('prod_isbnb',     $isbn_parts[1]);
    $smarty->assign('prod_isbnc',     $isbn_parts[2]);

    $smarty->assign('prod_ean',       $this->ean);

    $smarty->assign('prod_isbn13',    '978-'.substr($this->isbn,0,12).substr($this->ean,-1,1));

    $smarty->assign('prod_contrat_taux',     $this->contrat->taux);
    $smarty->assign('prod_contrat_duree',    $this->contrat_duree);
    $smarty->assign('prod_contrat_quant',    $this->contrat_quantite);

    $smarty->assign('prod_stock_reel',        $this->stock_reel);
    $smarty->assign('prod_stock_dispo',       ($this->stock_reel - $this->stock_in_command));
    $smarty->assign('prod_stock_in_command',  $this->stock_in_command);
    $smarty->assign('prod_stock_alert',       $this->seuil_stock_alerte);

    if ($this->seuil_stock_alerte > ($this->stock_reel - $this->stock_in_command) && $this->status == 1)
      {
	$smarty->assign('smarty_stock_dispo_class', 'class="alerte"');
      }
  }

}
?>
