<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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


/**     \class      MouvementStock
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
    function _create($user, $product_id, $entrepot_id, $qty, $type)
    {
        dolibarr_syslog("mouvementstock.class.php::create $user, $product_id, $entrepot_id, $qty, $type");
    
        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."stock_mouvement (datem, fk_product, fk_entrepot, value, type_mouvement, fk_user_author)";
        $sql .= " VALUES (now(), $product_id, $entrepot_id, $qty, $type, $user->id)";

        if ($this->db->query($sql))
        {

            $sql = "UPDATE ".MAIN_DB_PREFIX."product_stock SET reel = reel + $qty";
            $sql.= " WHERE fk_entrepot = $entrepot_id AND fk_product = $product_id";

            if ($this->db->query($sql))
            {
                $this->db->commit();
                //dolibarr_syslog("mouvementstock.class.php::create update ok");
                return 1;
            }
            else
            {
                $this->db->rollback();
                $this->error=$this->db->error() . " - $sql";
                dolibarr_syslog("mouvementstock.class.php::create echec update ".$this->error);
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->error() . " - $sql";
            dolibarr_syslog("mouvementstock.class.php::create echec insert ".$this->error);
            return -1;
        }

    }

    /*
     *
     *
     */
    function livraison($user, $product_id, $entrepot_id, $qty) 
    {
    
      return $this->_create($user, $product_id, $entrepot_id, (0 - $qty), 2);
    
    }

}
?>
