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
        \file       htdocs/oscommerce_ws/produits/osc_product.class.php
        \ingroup    oscommerce_ws/produits/
        \brief      Fichier de la classe des produits issus de OSC
        \version    $Revision$
*/


/**
        \class      Osc_product
        \brief      Classe permettant la gestion des produits issus d'une base OSC
*/

class Osc_product
{
	var $db;
	
	var $osc_id;
	var $osc_ref;
	var $osc_name;
	var $osc_desc;
	var $osc_price;
	var $osc_tva;
	var $osc_stockmini;
	var $osc_stock;
	var $osc_four;
	var $osc_image;
	var $osc_catid;
	
	var $error;
	

    /**
     *    \brief      Constructeur de la classe
     *    \param      id          Id produit (0 par defaut)
     */	
	function Osc_product($DB, $id=0) {

        global $langs;
      
        $this->osc_id = $id ;

        /* les initialisations nécessaires */
        $this->db = $DB;
	}

/**
     *      \brief      Charge le produit OsC en mémoire
     *      \param      id      Id du produit dans OsC 
     *      \param      ref     Ref du produit dans OsC (doit être unique dans OsC)
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
		$client = new soapclient_nusoap(OSCWS_DIR."/ws_articles.php");

		// Call the WebSeclient->fault)rvice and store its result in $obj
		$obj = $client->call("get_article",$parameters );
		if ($client->fault) {
			$this->error="Fault detected";
			return -1;
		}
		elseif (!($err=$client->getError()) ) {
  			$this->osc_id = $obj['products_id'];
  			$this->osc_ref = $obj['products_model'];
  			$this->osc_name = $obj['products_name'];
  			$this->osc_desc = $obj['products_description'];
  			$this->osc_stock = $obj['products_quantity'];
  			$this->osc_four = $obj['manufacturers_id'];
			$this->osc_price = $obj['products_price'];
			$this->osc_image = $obj['image'];
			$this->osc_catid = $obj['categories_id'];
  			}
  		else {
		    $this->error = 'Erreur '.$client->getError();
			return -1;
		} 
		return 0;
	}

// renvoie un objet commande dolibarr
	function osc2dolibarr($osc_productid)
	{

	  $result = $this->fetch($osc_productid);
	  if ( !$result )
	  {
	  		$product = new Product($this->db);
	    	if ($this->error == 1)
	    	{
	      	print '<br>erreur 1</br>';
				return '';
	    	}
	    	/* initialisation */
	    		$product->ref = $this->osc_ref;
	    		$product->libelle = $this->osc_name;
	    		$product->description = $this->osc_desc;
	    		$product->price = convert_price($this->osc_price);
	    		$product->tva_tx = $this->osc_tva;
	    		$product->type = 0;
	    		$product->catid = $this->get_catid($this->osc_catid) ;
	    		$product->seuil_stock_alerte = 0; /* on force */
	/* on force */
				$product->status = 1; /* en vente */

		 return $product; 		  
	  }

	}
/**
*      \brief      Mise à jour de la table de transition
*      \param      oscid      Id du produit dans OsC 
*	   \param	   prodid	  champ référence 	
*      \return     int     <0 si ko, >0 si ok
*/
	function transcode($oscid, $prodid)
	{

		/* suppression et insertion */
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."osc_product WHERE rowid = ".$oscid.";";
		$result=$this->db->query($sql);
        if ($result)
        {
		}
        else
        {
            dolibarr_syslog("osc_product::transcode echec suppression");
//            $this->db->rollback();
//            return -1;
		}
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."osc_product VALUES (".$oscid." ,  now() , ".$prodid.") ;";

		$result=$this->db->query($sql);
        if ($result)
        {
		}
        else
        {
            dolibarr_syslog("osc_product::transcode echec insert");
//            $this->db->rollback();
//            return -1;
		}
	return 0;	
     }

// converti le produit osc en produit dolibarr

	function get_productid($osc_product)
	{
		$sql = "SELECT fk_product";
		$sql.= " FROM ".MAIN_DB_PREFIX."osc_product";
		$sql.= " WHERE rowid = ".$osc_product;
		$resql=$this->db->query($sql);
		$obj = $this->db->fetch_object($resql);
// test d'erreurs
		if ($obj) return $obj->fk_product;	
		else return '';	
	}
	
	function get_catid($osccatid)
	{
		require_once(DOL_DOCUMENT_ROOT."/oscommerce_ws/produits/osc_categories.class.php");
		$mycat=new Osc_categorie($this->db);		

		if ($mycat->fetch_osccat($osccatid) > 0) 
		{
			$x = $mycat->dolicatid;
			print'<p>'.$x.'</p>';			
			return $x ;
		}
		else return 0;
	}
	
	function get_osc_productid($productidp)
	{
		$sql = "SELECT rowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."osc_product";
		$sql.= " WHERE fk_product = ".$productidp;
		$result=$this->db->query($sql);
		$row = $this->db->fetch_row($result);
// test d'erreurs
		if ($row) return $row[0];	
		else return -1;	
	}
	
	function get_catid($osccatid)
	{
      require_once("./osc_categories.class.php");
		$mycat=new Osc_categorie($this->db);		

		if ($mycat->fetch_osccat($osccatid) > 0) 
		{
			$x = $mycat->dolicatid;
			print'<p>'.$x.'</p>';			
			return $x ;
		}
		else return 0;
		
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
