<?php
/*  Copyright (C) 2006      Jean Heimburger     <jean@tiaris.info>
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
        \file       htdocs/oscommerce_ws/orders/osc_order.class.php
        \ingroup    oscommerce_ws/orders
        \brief      Fichier de la classe des commandes issus de OsCommerce
        \version    $Revision$
*/


/**
        \class      Osc_order
        \brief      Classe permettant la gestion des commandes issues d'une base OSC
*/

class Osc_order
{
	var $osc_orderid;
	var $osc_custid; //identifant du client osc
	var $osc_custname;
	var $osc_orderdate;
	var $osc_ordertotal;
	var $osc_orderpaymet;

	var $error;

    /**
     *    \brief      Constructeur de la classe
     *    \param      id          Id client (0 par defaut)
     */	
	function Osc_order($id=0) {

        global $langs;
      
        $this->osc_orderid = $id ;

        /* les initialisations nécessaires */
	}


/**
*      \brief      Charge la commande OsC en mémoire
*      \param      id      Id de la commande dans OsC 
*      \return     int     <0 si ko, >0 si ok
*/
   function fetch($id='')
    {
        global $langs;
		global $conf;
	
		$this->error = '';
		dolibarr_syslog("Osc_order::fetch $id=$id ");
      // Verification parametres
      if (! $id )
        {
            $this->error=$langs->trans('ErrorWrongParameters');
            return -1;
        }

		set_magic_quotes_runtime(0);

		//WebService Client.
		require_once(NUSOAP_PATH."/nusoap.php");
		require_once("../includes/configure.php");

		// Set the parameters to send to the WebService
		$parameters = array("orderid"=>$id);

		// Set the WebService URL
		$client = new soapclient(OSCWS_DIR."/ws_orders.php");

		// Call the WebSeclient->fault)rvice and store its result in $obj
		$obj = $client->call("get_Order",$parameters );

		if ($client->fault) {
			$this->error="Fault detected ".$client->getError();
			return -1;
		}
		elseif (!($err=$client->getError()) ) {
  			$this->osc_orderid = $obj[0][orders_id];
			$this->osc_custname = $obj[0][customers_name];
			$this->osc_custid = $obj[0][customers_id];
			$this->osc_orderdate = $obj[0][date_purchased];
			$this->osc_ordertotal = $obj[0][value];
			$this->osc_orderpaymet = $obj[0][payment_method];
  			}
  		else {
		    $this->error = 'Erreur '.$err ;
			return -1;
		} 
		return 0;
	}
	
	}

?>
