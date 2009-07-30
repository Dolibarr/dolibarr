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
 */

/**
   \file       htdocs/product/canvas/product.livre.class.php
   \ingroup    produit
   \brief      Fichier de la classe des produits specifiques de type livre
   \version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT.'/product/canvas/product.livre.class.php');

/**
   \class      ProductLivreCouverture
   \brief      Classe permettant la gestion des livres, cette classe surcharge la classe produit
*/

class ProductLivreCouverture extends Product
{
  /**
   *    \brief      Constructeur de la classe
   *    \param      DB          Handler acces base de donnees
   *    \param      id          Id produit (0 par defaut)
   */
  function ProductLivreCouverture($DB=0, $id=0)
  {
    $this->db = $DB;
    $this->id = $id ;
    $this->canvas = "livrecouverture";
    $this->name = "livrecouverture";
    $this->description = "Gestion des couvertures des livres";
    $this->active = PRODUIT_SPECIAL_LIVRECOUVERTURE;
    $this->menu_new = '';
    $this->menu_add = 0;
    $this->menu_clear = 1;

    $this->no_button_copy = 1;
    $this->no_button_edit = 1;
    $this->no_button_delete = 1;

  }
  /**
   *    \brief      Creation
   *    \param      id          Id livre
   */
  function Create($user,$datas)
  {
    $this->db->begin();
    $id = parent::Create($user);

    return $this->id;
  }
  /**
   *    \brief      Supression
   *    \param      id          Id livre
   */
  function DeleteCanvas($id)
  {
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
	$sql = "SELECT p.rowid,p.ref,p.label";
	$sql.= " FROM ".MAIN_DB_PREFIX."product_cnv_livre as pl,".MAIN_DB_PREFIX."product as p";
	$sql.= " WHERE pl.rowid=p.rowid AND pl.fk_couverture = '".$id."'";

	$result = $this->db->query($sql) ;

	if ( $result )
	  {
	    $result = $this->db->fetch_array();

	    $this->livre_id           = $result["rowid"];
	    $this->livre_ref          = $result["ref"];
	    $this->livre_label        = stripslashes($result["label"]);
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
    $error = 0;
    return $error;
  }

  /**
   *    \brief      Assigne les valeurs pour les templates Smarty
   *    \param      smarty     Instance de smarty
   */
  function assign_smarty_values(&$smarty)
  {
    $smarty->assign('prod_id',           $this->id);
    $smarty->assign('livre_id',         $this->livre_id);
    $smarty->assign('livre_ref',         $this->livre_ref);
    $smarty->assign('livre_label',         $this->livre_label);

    $smarty->assign('prod_note',         $this->note);
    $smarty->assign('prod_description',  $this->description);
    $smarty->assign('prod_canvas',       $this->canvas);

    $this->stock_dispo = ($this->stock_reel - $this->stock_in_command);

    $smarty->assign('prod_stock_reel',       $this->stock_reel);
    $smarty->assign('prod_stock_dispo',      $this->stock_dispo);
    $smarty->assign('prod_stock_in_command', $this->stock_in_command);
    $smarty->assign('prod_stock_alert',      $this->seuil_stock_alerte);

    if ( ($this->seuil_stock_alerte > $this->stock_dispo) && ($this->status == 1) )
      {
	$smarty->assign('smarty_stock_dispo_class', 'class="warning"');
      }
  }
}
?>
