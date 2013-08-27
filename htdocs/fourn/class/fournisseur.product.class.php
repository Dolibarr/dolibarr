<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/fourn/class/fournisseur.product.class.php
 * 	\ingroup    produit
 * 	\brief      File of class to manage predefined suppliers products
 */

require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';


/**
 * 	Class to manage predefined suppliers products
 */
class ProductFournisseur extends Product
{
    var $db;
    var $error;

    var $product_fourn_price_id;  // id of ligne product-supplier

    var $id;                      // product id
    var $fourn_ref;               // ref supplier
    var $fourn_qty;               // quantity for price
    var $fourn_price;             // price for quantity
    var $fourn_remise_percent;    // discount for quantity (percent)
    var $fourn_remise;            // discount for quantity (amount)
    var $product_fourn_id;        // supplier id
    var $fk_availability;         // availability delay
    var $fourn_unitprice;
    var $fourn_tva_npr;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
    }



    /**
     *    Remove all prices for this couple supplier-product
     *
     *    @param	int		$id_fourn   Supplier Id
     *    @return   int         		< 0 if error, > 0 if ok
     */
    function remove_fournisseur($id_fourn)
    {
        $ok=1;

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
        $sql.= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn;

        dol_syslog(get_class($this)."::remove_fournisseur sql=".$sql);
        $resql2=$this->db->query($sql);
        if (! $resql2)
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::remove_fournisseur ".$this->error, LOG_ERR);
            $ok=0;
        }

        if ($ok)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }


    /**
     * 	Remove a price for a couple supplier-product
     *
     * 	@param	int		$rowid		Line id of price
     *	@return	int					<0 if KO, >0 if OK
     */
    function remove_product_fournisseur_price($rowid)
    {
        global $conf;

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
        $sql.= " WHERE rowid = ".$rowid;

        dol_syslog(get_class($this)."::remove_product_fournisseur_price sql=".$sql);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::remove_product_fournisseur_price ".$this->error,LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }


    /**
     *    Modify the purchase price for a supplier
     *
     *    @param  	int			$qty				Min quantity for which price is valid
     *    @param  	float		$buyprice			Purchase price for the quantity min
     *    @param  	User		$user				Object user user made changes
     *    @param  	string		$price_base_type	HT or TTC
     *    @param  	Societe		$fourn				Supplier
     *    @param  	int			$availability		Product availability
     *    @param	string		$ref_fourn			Supplier ref
     *    @param	float		$tva_tx				VAT rate
     *    @param  	string		$charges			costs affering to product
	 *    @param  	float		$remise_percent		Discount  regarding qty (percent)
	 *    @param  	float		$remise				Discount  regarding qty (amount)
	 *    @param  	int			$newnpr				Set NPR or not
     *    @return	int								<0 if KO, >=0 if OK
     */
    function update_buyprice($qty, $buyprice, $user, $price_base_type, $fourn, $availability, $ref_fourn, $tva_tx, $charges=0, $remise_percent=0, $remise=0, $newnpr=0)
    {
        global $conf,$mysoc;

        // Clean parameter
        if (empty($qty)) $qty=0;
        if (empty($buyprice)) $buyprice=0;
        if (empty($charges)) $charges=0;
        if (empty($availability)) $availability=0;
        if (empty($remise_percent)) $remise_percent=0;
        if ($price_base_type == 'TTC')
		{
			//$ttx = get_default_tva($fourn,$mysoc,$this->id);	// We must use the VAT rate defined by user and not calculate it
			$ttx = $tva_tx;
			$buyprice = $buyprice/(1+($ttx/100));
		}
        $buyprice=price2num($buyprice,'MU');
		$charges=price2num($charges,'MU');
        $qty=price2num($qty);
 		$error=0;

		$unitBuyPrice = price2num($buyprice/$qty,'MU');
		$unitCharges = price2num($charges/$qty,'MU');

		$now=dol_now();

        $this->db->begin();

        if ($this->product_fourn_price_id)
        {
	  		$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql.= " SET fk_user = " . $user->id." ,";
            $sql.= " ref_fourn = \"" . $this->db->escape($ref_fourn) . "\",";
			$sql.= " price = ".price2num($buyprice).",";
			$sql.= " quantity = ".$qty.",";
			$sql.= " remise_percent = ".$remise_percent.",";
			$sql.= " remise = ".$remise.",";
			$sql.= " unitprice = ".$unitBuyPrice.",";
			$sql.= " unitcharges = ".$unitCharges.",";
			$sql.= " tva_tx = ".$tva_tx.",";
			$sql.= " fk_availability = ".$availability.",";
			$sql.= " entity = ".$conf->entity.",";
			$sql.= " info_bits = ".$newnpr.",";
			$sql.= " charges = ".$charges;
			$sql.= " WHERE rowid = ".$this->product_fourn_price_id;
			// TODO Add price_base_type and price_ttc

			dol_syslog(get_class($this).'::update_buyprice sql='.$sql);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				$this->db->commit();
				return 0;
			}
			else
			{
				$this->error=$this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -2;
			}
        }

        else
        {
	        	// Delete price for this quantity
	        	$sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price";
          		$sql.= " WHERE fk_soc = ".$fourn->id." AND ref_fourn = '".$this->db->escape($ref_fourn)."' AND quantity = ".$qty." AND entity = ".$conf->entity;
				dol_syslog(get_class($this).'::update_buyprice sql='.$sql);
	        	$resql=$this->db->query($sql);
				if ($resql)
		  		{
		            // Add price for this quantity to supplier
		            $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
		            $sql.= "datec, fk_product, fk_soc, ref_fourn, fk_user, price, quantity, remise_percent, remise, unitprice, tva_tx, charges, unitcharges, fk_availability, entity, info_bits)";
		            $sql.= " values('".$this->db->idate($now)."',";
		            $sql.= " ".$this->id.",";
		            $sql.= " ".$fourn->id.",";
		            $sql.= " '".$this->db->escape($ref_fourn)."',";
		            $sql.= " ".$user->id.",";
		            $sql.= " ".$buyprice.",";
		            $sql.= " ".$qty.",";
					$sql.= " ".$remise_percent.",";
					$sql.= " ".$remise.",";
		            $sql.= " ".$unitBuyPrice.",";
		            $sql.= " ".$tva_tx.",";
		            $sql.= " ".$charges.",";
		            $sql.= " ".$unitCharges.",";
		            $sql.= " ".$availability.",";
		            $sql.= " ".$newnpr.",";
		            $sql.= $conf->entity;
		            $sql.=")";

		            dol_syslog(get_class($this)."::update_buyprice sql=".$sql);
		            if (! $this->db->query($sql))
		            {
		                $error++;
		            }

		            /*if (! $error)
		            {
		                // Ajoute modif dans table log
		                $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_log(";
		                $sql.= "datec, fk_product_fournisseur,fk_user,price,quantity)";
		                $sql.= "values('".$this->db->idate($now)."',";
		                $sql.= " ".$this->product_fourn_id.",";
		                $sql.= " ".$user->id.",";
		                $sql.= " ".price2num($buyprice).",";
		                $sql.= " ".$qty;
		                $sql.=")";

		                $resql=$this->db->query($sql);
		                if (! $resql)
		                {
		                    $error++;
		                }
		            }
					*/

		            if (! $error)
		            {
		                $this->db->commit();
		                return 0;
		            }
		            else
		            {
		                $this->error=$this->db->error()." sql=".$sql;
		                $this->db->rollback();
		                return -2;
		            }
		        }
		        else
		        {
		            $this->error=$this->db->error()." sql=".$sql;
		            $this->db->rollback();
		            return -1;
		        }
		    }
    }

    /**
     *    Loads the price information of a provider
     *
     *    @param	int		$rowid	        Line id
     *    @return   int 					< 0 if KO, 0 if OK but not found, > 0 if OK
     */
    function fetch_product_fournisseur_price($rowid)
    {
        $sql = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability,";
        $sql.= " pfp.fk_soc, pfp.ref_fourn, pfp.fk_product, pfp.charges, pfp.unitcharges"; // , pfp.recuperableonly as fourn_tva_npr";  FIXME this field not exist in llx_product_fournisseur_price
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " WHERE pfp.rowid = ".$rowid;

        dol_syslog(get_class($this)."::fetch_product_fournisseur_price sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
            	$this->product_fourn_price_id	= $rowid;
            	$this->fourn_ref				= $obj->ref_fourn;
            	$this->fourn_price				= $obj->price;
            	$this->fourn_charges            = $obj->charges;
            	$this->fourn_qty                = $obj->quantity;
            	$this->fourn_remise_percent     = $obj->remise_percent;
            	$this->fourn_remise             = $obj->remise;
            	$this->fourn_unitprice          = $obj->unitprice;
            	$this->fourn_unitcharges        = $obj->unitcharges;
            	$this->tva_tx					= $obj->tva_tx;
            	$this->product_id				= $obj->fk_product;	// deprecated
            	$this->fk_product				= $obj->fk_product;
            	$this->fk_availability			= $obj->fk_availability;
            	//$this->fourn_tva_npr			= $obj->fourn_tva_npr; // FIXME this field not exist in llx_product_fournisseur_price
            	return 1;
            }
            else
            {
                return 0;
            }
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::fetch_product_fournisseur_price error=".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *    List all supplier prices of a product
     *
     *    @param    int		$prodid	    Id of product
     *    @param	string	$sortfield	Sort field
     *    @param	string	$sortorder	Sort order
     *    @return	array				Array of Products with new properties to define supplier price
     */
    function list_product_fournisseur_price($prodid, $sortfield='', $sortorder='')
    {
        global $conf;

        $sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id,";
        $sql.= " pfp.rowid as product_fourn_pri_id, pfp.ref_fourn, pfp.fk_product as product_fourn_id,";
        $sql.= " pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability, pfp.charges, pfp.unitcharges, pfp.info_bits";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE pfp.entity IN (".getEntity('product', 1).")";
        $sql.= " AND pfp.fk_soc = s.rowid";
        $sql.= " AND pfp.fk_product = ".$prodid;
        if (empty($sortfield)) $sql.= " ORDER BY s.nom, pfp.quantity, pfp.price";
        else $sql.= $this->db->order($sortfield,$sortorder);
        dol_syslog(get_class($this)."::list_product_fournisseur_price sql=".$sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $retarray = array();

            while ($record = $this->db->fetch_array($resql))
            {
                //define base attribute
                $prodfourn = new ProductFournisseur($this->db);

                $prodfourn->product_fourn_price_id	= $record["product_fourn_pri_id"];
                $prodfourn->product_fourn_id		= $record["product_fourn_id"];
                $prodfourn->fourn_ref				= $record["ref_fourn"];
                $prodfourn->fourn_price				= $record["price"];
                $prodfourn->fourn_qty				= $record["quantity"];
				$prodfourn->fourn_remise_percent	= $record["remise_percent"];
				$prodfourn->fourn_remise			= $record["remise"];
                $prodfourn->fourn_unitprice			= $record["unitprice"];
								$prodfourn->fourn_charges          = $record["charges"];
								$prodfourn->fourn_unitcharges      = $record["unitcharges"];
                $prodfourn->fourn_tva_tx			= $record["tva_tx"];
                $prodfourn->fourn_id				= $record["fourn_id"];
                $prodfourn->fourn_name				= $record["supplier_name"];
                $prodfourn->fk_availability			= $record["fk_availability"];
                $prodfourn->id						= $prodid;
                $prodfourn->fourn_tva_npr						= $record["info_bits"];

                if (!isset($prodfourn->fourn_unitprice))
                {
                    if ($prodfourn->fourn_qty!=0)
                    {
                        $prodfourn->fourn_unitprice = price2num($prodfourn->fourn_price/$prodfourn->fourn_qty,'MU');
                    }
                    else
                    {
                        $prodfourn->fourn_unitprice="";
                    }
                }

                $retarray[]=$prodfourn;
            }

            $this->db->free($resql);
            return $retarray;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::list_product_fournisseur_price error=".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     * 	Load properties for minimum price
     *
     *  @param	int		$prodid	    Product id
     *  @return int					<0 if KO, >0 if OK
     */
    function find_min_price_product_fournisseur($prodid)
    {
        global $conf;

        $this->product_fourn_price_id = '';
        $this->product_fourn_id       = '';
        $this->fourn_ref              = '';
        $this->fourn_price            = '';
        $this->fourn_qty              = '';
		$this->fourn_remise_percent   = '';
		$this->fourn_remise           = '';
        $this->fourn_unitprice        = '';
        $this->fourn_id			      = '';
        $this->fourn_name			  = '';
        $this->id					  = '';

        $sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id,";
        $sql.= " pfp.rowid as product_fourn_price_id, pfp.ref_fourn,";
        $sql.= " pfp.price, pfp.quantity, pfp.unitprice, pfp.tva_tx, pfp.charges, pfp.unitcharges";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " WHERE s.entity IN (".getEntity('societe', 1).")";
        $sql.= " AND pfp.fk_product = ".$prodid;
        $sql.= " AND pfp.fk_soc = s.rowid";
        $sql.= " ORDER BY pfp.unitprice";
        $sql.= $this->db->plimit(1);

        dol_syslog(get_class($this)."::find_min_price_product_fournisseur sql=".$sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $record = $this->db->fetch_array($resql);
            $this->product_fourn_price_id	= $record["product_fourn_price_id"];
            $this->fourn_ref				= $record["ref_fourn"];
            $this->fourn_price				= $record["price"];
            $this->fourn_qty				= $record["quantity"];
            $this->fourn_remise_percent		= $record["remise_percent"];
            $this->fourn_remise				= $record["remise"];
            $this->fourn_unitprice			= $record["unitprice"];
            $this->fourn_charges			= $record["charges"];
            $this->fourn_unitcharges		= $record["unitcharges"];
            $this->fourn_tva_tx				= $record["tva_tx"];
            $this->fourn_id					= $record["fourn_id"];
            $this->fourn_name				= $record["supplier_name"];
            $this->id						= $prodid;
            $this->db->free($resql);
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::find_min_price_product_fournisseur error=".$this->error, LOG_ERR);
            return -1;
        }
    }

    /**
     *	Display supplier of product
     *
     *	@param	int		$withpicto	Add picto
     *	@param	string	$option		Target of link ('', 'customer', 'prospect', 'supplier')
     *	@return	string				String with supplier price
	 *  TODO Remove this method. Use getNomUrl directly.
     */
    function getSocNomUrl($withpicto=0,$option='supplier')
    {
        $cust = new Fournisseur($this->db);
        $cust->fetch($this->fourn_id);

        return $cust->getNomUrl($withpicto,$option);
    }

    /**
     *	Display price of product
     *
     *	@return	string		String with supplier price
     */
    function display_price_product_fournisseur()
    {
        global $langs;
        $langs->load("suppliers");
        $out=price($this->fourn_unitprice).' '.$langs->trans("HT").' &nbsp; ('.$langs->trans("Supplier").': '.$this->getSocNomUrl(1).' / '.$langs->trans("SupplierRef").': '.$this->fourn_ref.')';
        return $out;
    }

}

?>
