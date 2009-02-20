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
        \file       htdocs/oscommerce_ws/clients/osc_customer.class.php
        \ingroup    oscommerce2
        \brief      Fichier de la classe des clients issus de OsCommerce
        \version    $Revision$
*/


/**
        \class      Osc_customer
        \brief      Classe permettant la gestion des clients/prospects issus d'une base OSC
*/


class Osc_customer
{
	var $db;

	var $osc_custid;
	var $osc_custsoc;
	var $osc_custfirstname;
	var $osc_custlastname;
	var $osc_custstreet;
	var $osc_custpostcode;
	var $osc_custcity;
	var $osc_custtel;
	var $osc_custfax;
	var $osc_custmail;
	var $osc_custidcountry;
	var $osc_custcodecountry;
	var $osc_custcountry;

	var $error;

    /**
     *    \brief      Constructeur de la classe
     *    \param      id          Id client (0 par defaut)
     */	
	function Osc_customer($DB, $id=0) {

        global $langs;
 		global $conf;
     
        $this->osc_custid = $id ;

        /* les initialisations nécessaires */
		$this->db = $DB;

	}


/**
*      \brief      Charge le client OsC en mémoire
*      \param      id      Id du client dans OsC 
*      \return     int     <0 si ko, >0 si ok
*/
   function fetch($id='')
    {
        global $langs;
		global $conf;
	
		$this->error = '';
		dol_syslog("Osc_customer::fetch $id=$id ref=$ref");
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
		$parameters = array("custid"=>$id);

		// Set the WebService URL
		$client = new soapclient_nusoap(OSCWS_DIR."/ws_customers.php");

		// Call the WebSeclient->fault)rvice and store its result in $obj
		$obj = $client->call("get_Client",$parameters );
// Attention c'est un tableau !!

		if ($client->fault) {
			$this->error="Fault detected ".$client->getError();
			return -1;
		}
		elseif (!($err=$client->getError()) ) {
  			$this->osc_custid = $obj[0][customers_id];
  			$this->osc_custsoc = $obj[0][entry_company];
  			$this->osc_custfirstname = $obj[0][entry_firstname];
  			$this->osc_custlastname = $obj[0][entry_lastname];
  			$this->osc_custstreet = $obj[0][entry_street_address];
  			$this->osc_custpostcode = $obj[0][entry_postcode];
  			$this->osc_custcity = $obj[0][entry_city];
			$this->osc_custtel = $obj[0][customers_telephone];
			$this->osc_custfax = $obj[0][customers_fax];
			$this->osc_custmail = $obj[0][customers_email_address];
			$this->osc_custidcountry = $obj[0][entry_country_id];
			$this->osc_custcodecountry = $obj[0][countries_iso_code_2];
			$this->osc_custcountry = $obj[0][countries_name];
  			}
  		else {
		    $this->error = 'Erreur '.$err ;
			return -1;
		} 
		return 0;
	}

/**
*      \brief      Mise à jour de la table de transition
*      \param      oscid      Id du client dans OsC 
*	   \param	   socid	  champ société.rowid 	
*      \return     int     <0 si ko, >0 si ok
*/
	function transcode($oscid, $socid)
	{

		/* suppression et insertion */
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."osc_customer WHERE rowid = ".$oscid.";";
		$result=$this->db->query($sql);
        if ($result)
        {
//			print "suppression ok ".$sql."  * ".$result;
		}
        else
        {
//			print "suppression rate ".$sql."  * ".$result;
            dol_syslog("osc_customer::transcode echec suppression");
//            $this->db->rollback();
//            return -1;
		}
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."osc_customer VALUES (".$oscid.", ".$this->db->idate(mktime()).", ".$socid.") ;";

		$result=$this->db->query($sql);
        if ($result)
        {
//			print "insertion ok ". $sql."  ". $result;
		}
        else
        {
//			print "insertion rate ".$sql." , ".$result;
            dol_syslog("osc_customer::transcode echec insert");
//            $this->db->rollback();
//            return -1;
		}
	return 0;	
     }
// converti le client osc en client dolibarr

	function get_clientid($osc_client)
	{
		$sql = "SELECT fk_soc";
		$sql.= " FROM ".MAIN_DB_PREFIX."osc_customer";
		$sql.= " WHERE rowid = ".$osc_client;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
// test d'erreurs
		if ($obj) return $obj->fk_soc[0];	
		else return '';
	}

	}
	
?>
