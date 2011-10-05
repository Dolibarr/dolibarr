<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2011      Juanjo Menent        <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * 	\file       htdocs/fourn/class/fournisseur.product.class.php
 * 	\ingroup    produit
 * 	\brief      File of class to manage predefined suppliers products
 */

require_once DOL_DOCUMENT_ROOT."/product/class/product.class.php";
require_once DOL_DOCUMENT_ROOT."/fourn/class/fournisseur.class.php";


/**
 * 	\class      ProductFournisseur
 * 	\brief      Class to manage predefined suppliers products
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
    var $product_fourn_id;        // supplier id
    var $fk_availability;         // availability delay
    var $fourn_unitprice;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$DB      Database handler
     */
    function ProductFournisseur($db)
    {
        $this->db = $db;
    }



    /**
     *    Remove all prices for this couple supplier-product
     *
     *    @param    id_fourn    Supplier Id
     *    @return   int         < 0 if error, > 0 if ok
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
     * 	@param		rowid	Line id of price
     *	@return		int		<0 if KO, >0 if OK
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
     *    @param  	int			$qty             	Min quantity for which price is valid
     *    @param  	float		$buyprice        	Purchase price for the quantity min
     *    @param  	User		$user            	Object user user made changes
     *    @param  	string		$price_base_type	HT or TTC
     *    @param  	Societe		$fourn				Supplier
     *    @param  	int			$availability		Product availability
     *    @param	string		$ref_fourn			Supplier ref
     *    @return	int								>0 if KO, >0 if OK
     */
    function update_buyprice($qty, $buyprice, $user, $price_base_type, $fourn, $availability, $ref_fourn)
    {
        global $conf,$mysoc;

        // Clean parameter
        $buyprice=price2num($buyprice);
        $qty=price2num($qty);
        if (empty($availability)) $availability=0;

        $error=0;
        $this->db->begin();

        // Supprime prix courant du fournisseur pour cette quantite
        $sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price";
        if ($this->product_fourn_price_id)
        {
            $sql.= " WHERE rowid = ".$this->product_fourn_price_id;
        }

        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($price_base_type == 'TTC')
            {
                $ttx = get_default_tva($fourn,$mysoc,$this->id);
                $buyprice = $buyprice/(1+($ttx/100));
            }
            $unitBuyPrice = price2num($buyprice/$qty,'MU');

            $now=dol_now();

            // Ajoute prix courant du fournisseur pour cette quantite
            $sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
            $sql.= "datec, fk_product, fk_soc, ref_fourn, fk_user, price, quantity, unitprice, fk_availability, entity)";
            $sql.= " values('".$this->db->idate($now)."',";
            $sql.= " ".$this->id.",";
            $sql.= " ".$fourn->id.",";
            $sql.= " '".$this->db->escape($ref_fourn)."',";
            $sql.= " ".$user->id.",";
            $sql.= " ".price2num($buyprice).",";
            $sql.= " ".$qty.",";
            $sql.= " ".$unitBuyPrice.",";
            $sql.= " ".$availability.",";
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


    /**
     *    Load information about a provider
     *
     *    @param      fournid         Supplier ID
     *    @return     int             < 0 if error, > 0 if ok
     */
    /*function fetch_fourn_data($fournid)
    {
        global $conf;

        // Check parameters
        if (empty($fournid)) return -1;

        $sql = "SELECT rowid, ref_fourn";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
        $sql.= " WHERE fk_product = ".$this->id;
        $sql.= " AND fk_soc = ".$fournid;
        $sql.= " AND entity = ".$conf->entity;

        dol_syslog(get_class($this)."::fetch_fourn_data sql=".$sql);
        $result = $this->db->query($sql);
        if ($result)
        {
            $result = $this->db->fetch_array($result);
            $this->ref_fourn = $result["ref_fourn"];
            $this->product_fourn_price_id = $result["rowid"];
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            dol_syslog(get_class($this)."::fetch_fourn_data error=".$this->error, LOG_ERR);
            return -1;
        }
    }*/

    /**
     *    Loads the price information of a provider
     *
     *    @param      rowid	         line id
     *    @return     int             < 0 if KO, 0 if OK but not found, > 0 if OK
     */
    function fetch_product_fournisseur_price($rowid)
    {
        $sql = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice, pfp.fk_availability,";
        $sql.= " pfp.fk_soc, pfp.ref_fourn, pfp.fk_product";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " WHERE pfp.rowid = ".$rowid;

        dol_syslog(get_class($this)."::fetch_product_fournisseur_price sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
                $this->product_fourn_price_id = $rowid;
                $this->fourn_ref              = $obj->ref_fourn;
                $this->fourn_price            = $obj->price;
                $this->fourn_qty              = $obj->quantity;
                $this->fourn_unitprice        = $obj->unitprice;
                $this->product_id             = $obj->fk_product;	// deprecated
                $this->fk_product             = $obj->fk_product;
                $this->fk_availability		  = $obj->fk_availability;
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
     *    @param    int		$rowid	    id du produit
     *    @return	array				Array of ProductFournisseur
     */
    function list_product_fournisseur_price($prodid)
    {
        global $conf;

        // Suppliers list
        $sql = "SELECT s.nom as supplier_name,";
        $sql.= " s.rowid as fourn_id,";
        $sql.= " pfp.ref_fourn,";
        $sql.= " pfp.rowid as product_fourn_pri_id,";
        $sql.= " pfp.price, pfp.quantity, pfp.unitprice, pfp.fk_availability";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
        $sql.= " INNER JOIN ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " ON pfp.fk_soc = s.rowid";
        $sql.= " WHERE s.entity = ".$conf->entity;
        $sql.= " AND pfp.fk_product = ".$prodid;
        $sql.= " ORDER BY s.nom, pfp.quantity, pfp.price";

        dol_syslog(get_class($this)."::list_product_fournisseur_price sql=".$sql, LOG_DEBUG);

        $resql = $this->db->query($sql);

        if ($resql)
        {
            $prod_fourn = array();

            while ($record = $this->db->fetch_array($resql))
            {
                //define base attribute
                $prodfourn = new ProductFournisseur($this->db);

                $prodfourn->product_fourn_price_id = $record["product_fourn_pri_id"];
                $prodfourn->product_fourn_id       = $record["product_fourn_id"];
                $prodfourn->fourn_ref              = $record["ref_fourn"];
                $prodfourn->fourn_price            = $record["price"];
                $prodfourn->fourn_qty              = $record["quantity"];
                $prodfourn->fourn_unitprice        = $record["unitprice"];
                $prodfourn->fourn_id			   = $record["fourn_id"];
                $prodfourn->fourn_name			   = $record["supplier_name"];
                $prodfourn->fk_availability    	   = $record["fk_availability"];
                $prodfourn->id					   = $prodid;

                if (!isset($prodfourn->fourn_unitprice))
                {
                    if ($prodfourn->fourn_qty!=0)
                    {
                        $prodfourn->fourn_unitprice = $prodfourn->fourn_price/$prodfourn->fourn_qty;
                    }
                    else
                    {
                        $prodfourn->fourn_unitprice="";
                    }
                }

                $prod_fourn[]=$prodfourn;
            }

            $this->db->free($resql);
            return $prod_fourn;
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
     *  @param      rowid	        Product id
     *  @return     int				<0 if KO, >0 if OK
     */
    function find_min_price_product_fournisseur($prodid)
    {
        global $conf;

        $this->product_fourn_price_id = '';
        $this->product_fourn_id       = '';
        $this->fourn_ref              = '';
        $this->fourn_price            = '';
        $this->fourn_qty              = '';
        $this->fourn_unitprice        = '';
        $this->fourn_id			      = '';
        $this->fourn_name			  = '';
        $this->id					  = '';

        $sql = "SELECT s.nom as supplier_name,";
        $sql.= " s.rowid as fourn_id,";
        $sql.= " pfp.ref_fourn,";
        $sql.= " pfp.rowid as product_fourn_price_id,";
        $sql.= " pfp.price, pfp.quantity, pfp.unitprice";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " WHERE s.entity = ".$conf->entity;
        $sql.= " AND pfp.fk_product = ".$prodid;
        $sql.= " AND pfp.fk_soc = s.rowid";
        $sql.= " ORDER BY pfp.unitprice";
        $sql.= $this->db->plimit(1);

        dol_syslog(get_class($this)."::find_min_price_product_fournisseur sql=".$sql, LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $record = $this->db->fetch_array($resql);
            $this->product_fourn_price_id = $record["product_fourn_price_id"];
            $this->fourn_ref              = $record["ref_fourn"];
            $this->fourn_price            = $record["price"];
            $this->fourn_qty              = $record["quantity"];
            $this->fourn_unitprice        = $record["unitprice"];
            $this->fourn_id			      = $record["fourn_id"];
            $this->fourn_name			  = $record["supplier_name"];
            $this->id					  = $prodid;
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
     *
     */
    function getSocNomUrl($withpicto=0)
    {
        $cust = new Fournisseur($this->db);
        $cust->fetch($this->fourn_id);

        return $cust->getNomUrl($withpicto);
    }

    /**
     *
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