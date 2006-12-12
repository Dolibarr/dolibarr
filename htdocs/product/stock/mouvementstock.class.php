<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005      Laurent Destailleur  <eldy@users.sourceforge.net>
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
   \file       htdocs/product/stock/mouvementstock.class.php
   \ingroup    stock
   \brief      Fichier de la classe de gestion des mouvements de stocks
   \version    $Revision$
*/


/**
   \class      MouvementStock
   \brief      Classe permettant la gestion des mouvements de stocks
*/

class MouvementStock
{

    function MouvementStock($DB)
    {
        $this->db = $DB;
    }

    /**
     *      \brief      Crée un mouvement en base
     *      \return     int     <0 si ko, >0 si ok
     */
    function _create($user, $fk_product, $entrepot_id, $qty, $type, $price=0)
    {
      $error = 0;
        dolibarr_syslog("mouvementstock.class.php::create $user, $fk_product, $entrepot_id, $qty, $type");
    
        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement";
	$sql.= " (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author, price)";
        $sql.= " VALUES (now(), $fk_product, $entrepot_id, $qty, $type, $user->id";
	$sql.= ",'".ereg_replace(",",".",$price)."');";

        if ($this->db->query($sql))
	  {
	    
	  }
        else
	  {	  
            dolibarr_syslog("MouvementStock::_Create echec insert ".$this->error);
	    $error = -1;
	  }

	$num = 0;

	if ($error === 0)
	  {
            $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."product_stock";
            $sql.= " WHERE fk_entrepot = $entrepot_id AND fk_product = $fk_product";

            if ($this->db->query($sql))
            {
	      $num = $this->db->num_rows($resql);
	      $this->db->free($resql);
            }
            else
            {
	      dolibarr_syslog("MouvementStock::_Create echec update ".$this->error);
	      $error = -2;
            }
	  }

	if ($error === 0)
	  {
	    if ($num > 0)
	      {
		$sql = "UPDATE ".MAIN_DB_PREFIX."product_stock SET reel = reel + $qty";
		$sql.= " WHERE fk_entrepot = $entrepot_id AND fk_product = $fk_product";
	      }
	    else
	      {
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_stock";
		$sql.= " (reel, fk_entrepot, fk_product) VALUES ";
		$sql.= " ($qty,$entrepot_id,$fk_product);";
	      }

            if ($this->db->query($sql))
            {

            }
            else
            {
	      dolibarr_syslog("MouvementStock::_Create echec update ".$this->error);
	      $error = -3;
            }
	  }


	if ($error === 0)
	  {
	    $this->db->commit();
	    return 1;
	  }
	else
	  {
	    $this->db->rollback();
	    $this->error=$this->db->error() . " - $sql";
	    dolibarr_syslog("MouvementStock::_Create ROLLBACK");
	    return -2;
	  }	       
    }
    /*
     *
     *
     */
    function livraison($user, $fk_product, $entrepot_id, $qty)
    {    
      return $this->_create($user, $fk_product, $entrepot_id, (0 - $qty), 2);    
    }
    /*
     *
     *
     */
    function reception($user, $fk_product, $entrepot_id, $qty, $price=0) 
    {    
      return $this->_create($user, $fk_product, $entrepot_id, $qty, 3, $price);
    }

}
?>
