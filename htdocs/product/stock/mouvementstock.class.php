<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

class MouvementStock
{

  function MouvementStock($DB)
    {
      $this->db = $DB;
    }
  /*
   *
   *
   */
  function _create($user, $product_id, $entrepot_id, $qty, $type, $transaction=1) 
    {

      if ($this->db->begin($transaction) )
	{
	  
	  $sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author)";
	  $sql .= " VALUES (now(), $product_id, $entrepot_id, $qty, $type, $user->id)";
	  
	  if ($this->db->query($sql))
	    {

	      $sql = "UPDATE ".MAIN_DB_PREFIX."product_stock SET reel = reel + $qty WHERE fk_entrepot = $entrepot_id AND fk_product = $product_id";

	      if ($this->db->query($sql))
		{
		  return 1;
		}
	      else
		{
		  print $this->db->error() . "<br>$sql<br>";
		  return 0;
		}
	    }
	  else
	    {
	      print $this->db->error() . "<br>$sql<br>";
	      return 0;
	    }
	}
      else
	{
	  return 0;
	}
    }
  /*
   *
   *
   */
  function livraison($user, $product_id, $entrepot_id, $qty, $transaction=1) 
    {

      return $this->_create($user, $product_id, $entrepot_id, (0 - $qty), 2, $transaction);

    }
}
?>
