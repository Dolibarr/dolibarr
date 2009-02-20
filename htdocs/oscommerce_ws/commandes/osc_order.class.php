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
 */

/**
        \file       htdocs/oscommerce_ws/commandes/osc_order.class.php
        \ingroup    oscommerce_ws/orders
        \brief      Fichier de la classe des commandes issus de OsCommerce
        \version    $Revision$
*/


require("../clients/osc_customer.class.php");
require("../produits/osc_product.class.php");
require_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
require_once("../includes/configure.php");


/**
        \class      Osc_order
        \brief      Classe permettant la gestion des commandes issues d'une base OSC
*/

class Osc_order
{
	var $db;

	var $osc_orderid;
	var $osc_custid; //identifant du client osc
	var $osc_custname;
	var $osc_orderdate;
	var $osc_ordertotal;
	var $osc_orderpaymet;
	var $osc_orderport;
	var $osc_orderstatus;

	var $osc_lines = array();

	var $error;

    /**
     *    \brief      Constructeur de la classe
     *    \param      id          Id client (0 par defaut)
     */	
	function Osc_order($DB, $id=0) {

        global $langs;
      
        $this->osc_orderid = $id ;
		$this->db = $DB;
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
		dol_syslog("Osc_order::fetch $id=$id ");
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
		$client = new soapclient_nusoap(OSCWS_DIR."/ws_orders.php");

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
			$this->osc_ordertotal = $obj[0][total];
			$this->osc_orderpaymet = $obj[0][payment_method];
			$this->osc_orderport = $obj[0][port];


			for ($i=1;$i<count($obj);$i++) {
			// les lignes
				$this->osc_lines[$i-1][products_id] = $obj[$i][products_id];
				$this->osc_lines[$i-1][products_name] = $obj[$i][products_name];
				$this->osc_lines[$i-1][products_price] = $obj[$i][products_price];
				$this->osc_lines[$i-1][final_price] = $obj[$i][final_price];
				$this->osc_lines[$i-1][products_tax] = $obj[$i][products_tax];
				$this->osc_lines[$i-1][quantity] = $obj[$i][products_quantity];
				}
  			}
  		else {
		    $this->error = 'Erreur '.$err ;
			return -1;
		}
//		print_r($this); 
		return 0;
	}

// renvoie un objet commande dolibarr
	function osc2dolibarr($osc_orderid)
	{
	  $result = $this->fetch($osc_orderid);
	  if ( !$result )
	  {
			$commande = new Commande($this->db);
	    	if ($_error == 1)
	    	{
	       		print '<br>erreur 1</br>';
				exit;
	    	}
	    	/* initialisation */
			$oscclient = new Osc_Customer($this->db);
			$clientid = $oscclient->get_clientid($this->osc_custid);
			
			$oscproduct = new Osc_product($this->db);

			$commande->socid = $clientid;
			$commande->ref = $this->osc_orderid;
			$commande->date = $this->osc_orderdate;
			$commande->date_commande = $this->osc_orderdate;
			/* on force */
			$commande->statut = 0; //à voir
			$commande->source = 0; // à vérifier
 
			//les lignes

			for ($i = 0; $i < sizeof($this->osc_lines);$i++) {
				$commande->lines[$i]->libelle = $this->osc_lines[$i][products_id];
				$commande->lines[$i]->desc = $this->osc_lines[$i][products_name];
				$commande->lines[$i]->price = convert_price($this->osc_lines[$i][products_price]);
				$commande->lines[$i]->subprice = convert_price($this->osc_lines[$i][products_price]);
				$commande->lines[$i]->qty = $this->osc_lines[$i][quantity];
				$commande->lines[$i]->tva_tx = $this->osc_lines[$i][products_tax];
				$commande->lines[$i]->fk_product = $oscproduct->get_productid($this->osc_lines[$i][products_id]);
				$commande->lines[$i]->remise_percent = 0; // à calculer avec le finalprice
			}
			// les frais de port
			$fp = sizeof($this->osc_lines);
			$commande->lines[$fp]->libelle = "Frais de port";
			$commande->lines[$fp]->desc = "Frais de port";
			$commande->lines[$fp]->price = convert_price($this->osc_orderport);
			$commande->lines[$fp]->subprice = convert_price($this->osc_orderport);
			$commande->lines[$fp]->qty = 1;
			$commande->lines[$fp]->tva_tx = 0;
			$commande->lines[$fp]->fk_product = FK_PORT;
			$commande->lines[$fp]->remise_percent = 0;

		return $commande;
		} 

	}


/**
*      \brief      Mise à jour de la table de transition
*      \param      oscid      Id du produit dans OsC 
*	   \param	   prodid	  champ référence 	
*      \return     int     <0 si ko, >0 si ok
*/
	function transcode($osc_orderid, $doli_orderid)
	{

		/* suppression et insertion */
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."osc_order WHERE rowid = ".$osc_orderid.";";
		$result=$this->db->query($sql);
        if ($result)
        {
//			print "suppression ok ".$sql."  * ".$result;
		}
        else
        {
//			print "suppression rate ".$sql."  * ".$result;
            dol_syslog("osc_order::transcode echec suppression");
//            $this->db->rollback();
//            return -1;
		}
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."osc_order VALUES (".$osc_orderid.", ".$this->db->idate(mktime()).", ".$doli_orderid.") ;";

		$result=$this->db->query($sql);
        if ($result)
        {
//			print "insertion ok ". $sql."  ". $result;
		}
        else
        {
//			print "insertion rate ".$sql." , ".$result;
            dol_syslog("osc_product::transcode echec insert");
//            $this->db->rollback();
//            return -1;
		}
	return 0;	
     }
// converti le client osc en client dolibarr

	function get_orderid($osc_orderid)
	{
		$sql = "SELECT fk_commande";
		$sql.= " FROM ".MAIN_DB_PREFIX."osc_order";
		$sql.= " WHERE rowid = ".$osc_orderid;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
// test d'erreurs
		if ($obj) return $obj->fk_commande;	
		else return '';
	}

	}

?>
