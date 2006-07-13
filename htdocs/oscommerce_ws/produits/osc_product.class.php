<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2006      Jean Heimburger     <jean@tiaris.info>
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
        \file       htdocs/product.class.php
        \ingroup    produit
        \brief      Fichier de la classe des produits prédéfinis
        \version    $Revision$
*/


/**
        \class      Osc_product
        \brief      Classe permettant la gestion des produits issus d'une base OSC
*/

class Osc_product
{
	var $osc_id;
	var $osc_ref;
	var $osc_name;
	var $osc_desc;
	var $osc_price;
	var $osc_tva;
	var $osc_stockmini;
	var $osc_stock;
	var $osc_four;
	
	var $error;
	
    /**
     *    \brief      Constructeur de la classe
     *    \param      id          Id produit (0 par defaut)
     */	
	function Osc_product($id=0) {

        global $langs;
      
        $this->osc_id = $id ;

        /* les initialisations nécessaires */
	}

/**
     *      \brief      Charge le produit/service en mémoire
     *      \param      id      Id du produit/service à charger
     *      \param      ref     Ref du produit/service à charger
     *      \return     int     <0 si ko, >0 si ok
     */
   function fetch($id='',$ref='')
    {
        global $langs;
		global $conf;
	
	$this->error = '';
		dolibarr_syslog("Osc_product::fetch $id=$id ref=$ref");
      // Verification parametres
      if (! $id && ! $ref)
        {
            $this->error=$langs->trans('ErrorWrongParameters');
            return -1;
        }

		set_magic_quotes_runtime(0);

		//WebService Client.
		require_once(NUSOAP_PATH."/nusoap.php");
		require_once("../includes/configure.php");

		// Set the parameters to send to the WebService
		$parameters = array("id"=>$id,"ref"=>$ref);

		// Set the WebService URL
		$client = new soapclient(OSCWS_DIR."/ws_articles.php");

		// Call the WebSeclient->fault)rvice and store its result in $obj
		$obj = $client->call("get_article",$parameters );
		if ($client->fault) {
			$this->error="Fault detected";
			return -1;
		}
		elseif (!($err=$client->getError()) ) {
  			$this->osc_id = $obj[products_id];
  			$this->osc_ref = $obj[products_model];
  			$this->osc_name = $obj[products_name];
  			$this->osc_desc = $obj[products_description];
  			$this->osc_stock = $obj[products_quantity];
  			$this->osc_four = $obj[manufacturers_id];
			$this->osc_price = $obj[products_price];
  			}
  		else {
		    $this->error = 'Erreur '.$err ;
			return -1;
		} 
		return 0;
	}

	
       
	  /**
     *    \brief      création d'un article dans base OSC
     *    \param      $user utilisateur
     */	
	function create($user)
    {
    /* non implémentée */
    }	

	  /**
     *    \brief      modification d'un article dans base OSC
     *    \param      $user utilisateur
     */	
	function update($id, $user)
    {
    /* non implémentée */
    }

    /**
     *    \brief      Suppression du produit en base OSC
     *    \param      id          id du produit
     */
   function delete($id)
    {
    /* non implémentée */
    }
}
?>
