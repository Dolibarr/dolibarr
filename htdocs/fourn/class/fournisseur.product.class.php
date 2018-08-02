<?php
/* Copyright (C) 2005		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2009-2014	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2011		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2015		Marcos García           <marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke           <charlie@patas-monkey.com>
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
require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';


/**
 * 	Class to manage predefined suppliers products
 */
class ProductFournisseur extends Product
{
    var $db;
    var $error;

    var $product_fourn_price_id;  // id of ligne product-supplier

    var $id;                      // product id
	/**
	 * @deprecated
	 * @see ref_supplier
	 */
    var $fourn_ref;
    var $delivery_time_days;
    var $ref_supplier;			  // ref supplier (can be set by get_buyprice)
    var $vatrate_supplier;		  // default vat rate for this supplier/qty/product (can be set by get_buyprice)

    var $fourn_id;                //supplier id
    var $fourn_qty;               // quantity for price (can be set by get_buyprice)
    var $fourn_pu;			       // unit price for quantity (can be set by get_buyprice)

    var $fourn_price;             // price for quantity
    var $fourn_remise_percent;    // discount for quantity (percent)
    var $fourn_remise;            // discount for quantity (amount)
    var $product_fourn_id;        // supplier id
    var $fk_availability;         // availability delay - visible/used if option FOURN_PRODUCT_AVAILABILITY is on (duplicate information compared to delivery delay)
    var $fourn_unitprice;
    var $fourn_tva_tx;
    var $fourn_tva_npr;

    var $fk_supplier_price_expression;
    var $supplier_reputation;     // reputation of supplier
    var $reputations=array();     // list of available supplier reputations

    // Multicurreny
    var $fourn_multicurrency_id;
    var $fourn_multicurrency_code;
    var $fourn_multicurrency_tx;
    var $fourn_multicurrency_price;
    var $fourn_multicurrency_unitprice;

    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        global $langs;

        $this->db = $db;
        $langs->load("suppliers");
        $this->reputations= array('-1'=>'', 'FAVORITE'=>$langs->trans('Favorite'),'NOTTHGOOD'=>$langs->trans('NotTheGoodQualitySupplier'), 'DONOTORDER'=>$langs->trans('DoNotOrderThisProductToThisSupplier'));
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

        dol_syslog(get_class($this)."::remove_fournisseur", LOG_DEBUG);
        $resql2=$this->db->query($sql);
        if (! $resql2)
        {
            $this->error=$this->db->lasterror();
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
        global $conf, $user;

        $error=0;

        $this->db->begin();

        // Call trigger
        $result=$this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_DELETE',$user);
        if ($result < 0) $error++;
        // End call triggers

        if (empty($error))
        {

            $sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
            $sql.= " WHERE rowid = ".$rowid;

            dol_syslog(get_class($this)."::remove_product_fournisseur_price", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if (!$resql)
            {
                $this->error=$this->db->lasterror();
                $error++;
            }
        }

        if (empty($error)){
            $this->db->commit();
            return 1;
        }else{
            $this->db->rollback();
            return -1;
        }

    }


    /**
     *    Modify the purchase price for a supplier
     *
     *    @param  	int			$qty				            Min quantity for which price is valid
     *    @param  	float		$buyprice			            Purchase price for the quantity min
     *    @param  	User		$user				            Object user user made changes
     *    @param  	string		$price_base_type	            HT or TTC
     *    @param  	Societe		$fourn				            Supplier
     *    @param  	int			$availability		            Product availability
     *    @param	string		$ref_fourn			            Supplier ref
     *    @param	float		$tva_tx				            New VAT Rate (For example 8.5. Should not be a string)
     *    @param  	string		$charges			            costs affering to product
	 *    @param  	float		$remise_percent		            Discount  regarding qty (percent)
	 *    @param  	float		$remise				            Discount  regarding qty (amount)
	 *    @param  	int			$newnpr				            Set NPR or not
	 *    @param	int			$delivery_time_days	            Delay in days for delivery (max). May be '' if not defined.
	 * 	  @param    string      $supplier_reputation            Reputation with this product to the defined supplier (empty, FAVORITE, DONOTORDER)
     *	  @param    array		$localtaxes_array	            Array with localtaxes info array('0'=>type1,'1'=>rate1,'2'=>type2,'3'=>rate2) (loaded by getLocalTaxesFromRate(vatrate, 0, ...) function).
     *    @param    string  	$newdefaultvatcode              Default vat code
     *    @param  	float		$multicurrency_buyprice 	    Purchase price for the quantity min in currency
     *    @param  	string		$multicurrency_price_base_type	HT or TTC in currency
     *    @param  	float		$multicurrency_tx	            Rate currency
     *    @param  	string		$multicurrency_code	            Currency code
     *    @return	int								<0 if KO, >=0 if OK
     */
    function update_buyprice($qty, $buyprice, $user, $price_base_type, $fourn, $availability, $ref_fourn, $tva_tx, $charges=0, $remise_percent=0, $remise=0, $newnpr=0, $delivery_time_days=0, $supplier_reputation='', $localtaxes_array=array(), $newdefaultvatcode='', $multicurrency_buyprice=0, $multicurrency_price_base_type='HT',$multicurrency_tx=1,$multicurrency_code='')
    {
        global $conf, $langs;
        //global $mysoc;

        // Clean parameter
        if (empty($qty)) $qty=0;
        if (empty($buyprice)) $buyprice=0;
        if (empty($charges)) $charges=0;
        if (empty($availability)) $availability=0;
        if (empty($remise_percent)) $remise_percent=0;
	    if (empty($supplier_reputation) || $supplier_reputation == -1) $supplier_reputation='';
        if ($delivery_time_days != '' && ! is_numeric($delivery_time_days)) $delivery_time_days = '';
        if ($price_base_type == 'TTC')
		{
			$ttx = $tva_tx;
			$buyprice = $buyprice/(1+($ttx/100));
		}

		// Multicurrency
        if ($conf->multicurrency->enabled) {
            if (empty($multicurrency_tx)) $multicurrency_tx=1;
            if (empty($multicurrency_buyprice)) $multicurrency_buyprice=0;

            if (empty($multicurrency_buyprice)) $multicurrency_buyprice=0;
            if ($multicurrency_price_base_type == 'TTC')
    		{
    			$ttx = $tva_tx;
    			$multicurrency_buyprice = $multicurrency_buyprice/(1+($ttx/100));
    		}
            $multicurrency_buyprice=price2num($multicurrency_buyprice,'MU');
            $multicurrency_unitBuyPrice=price2num($multicurrency_buyprice/$qty,'MU');

            $buyprice=$multicurrency_buyprice/$multicurrency_tx;
            $fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $multicurrency_code);
        }

        $buyprice=price2num($buyprice,'MU');
		$charges=price2num($charges,'MU');
        $qty=price2num($qty);
 		$error=0;

		$unitBuyPrice = price2num($buyprice/$qty,'MU');

		$now=dol_now();

		$newvat = $tva_tx;

		if (count($localtaxes_array) > 0)
		{
			$localtaxtype1=$localtaxes_array['0'];
			$localtax1=$localtaxes_array['1'];
			$localtaxtype2=$localtaxes_array['2'];
			$localtax2=$localtaxes_array['3'];
		}
		else     // old method. deprecated because ot can't retreive type
		{
			$localtaxtype1='0';
			$localtax1=get_localtax($newvat,1);
			$localtaxtype2='0';
			$localtax2=get_localtax($newvat,2);
		}
		if (empty($localtax1)) $localtax1=0;	// If = '' then = 0
		if (empty($localtax2)) $localtax2=0;	// If = '' then = 0

        $this->db->begin();

        if ($this->product_fourn_price_id > 0)
        {
	  		$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql.= " SET fk_user = " . $user->id." ,";
            $sql.= " ref_fourn = '" . $this->db->escape($ref_fourn) . "',";
			$sql.= " price = ".price2num($buyprice).",";
			$sql.= " quantity = ".$qty.",";
			$sql.= " remise_percent = ".$remise_percent.",";
			$sql.= " remise = ".$remise.",";
			$sql.= " unitprice = ".$unitBuyPrice.",";
			$sql.= " fk_availability = ".$availability.",";
            $sql.= " multicurrency_price = ".(isset($multicurrency_buyprice)?"'".$this->db->escape(price2num($multicurrency_buyprice))."'":'null').",";
            $sql.= " multicurrency_unitprice = ".(isset($multicurrency_unitBuyPrice)?"'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'":'null').",";
            $sql.= " multicurrency_tx = ".(isset($multicurrency_tx)?"'".$this->db->escape($multicurrency_tx)."'":'1').",";
            $sql.= " fk_multicurrency = ".(isset($fk_multicurrency)?"'".$this->db->escape($fk_multicurrency)."'":'null').",";
            $sql.= " multicurrency_code = ".(isset($multicurrency_code)?"'".$this->db->escape($multicurrency_code)."'":'null').",";
			$sql.= " entity = ".$conf->entity.",";
			$sql.= " tva_tx = ".price2num($tva_tx).",";
			// TODO Add localtax1 and localtax2
			//$sql.= " localtax1_tx=".($localtax1>=0?$localtax1:'NULL').",";
			//$sql.= " localtax2_tx=".($localtax2>=0?$localtax2:'NULL').",";
			//$sql.= " localtax1_type=".($localtaxtype1!=''?"'".$localtaxtype1."'":"'0'").",";
			//$sql.= " localtax2_type=".($localtaxtype2!=''?"'".$localtaxtype2."'":"'0'").",";
			$sql.= " default_vat_code=".($newdefaultvatcode?"'".$this->db->escape($newdefaultvatcode)."'":"null").",";
			$sql.= " info_bits = ".$newnpr.",";
			$sql.= " charges = ".$charges.",";           // deprecated
			$sql.= " delivery_time_days = ".($delivery_time_days != '' ? $delivery_time_days : 'null').",";
			$sql.= " supplier_reputation = ".(empty($supplier_reputation) ? 'NULL' : "'".$this->db->escape($supplier_reputation)."'");
			$sql.= " WHERE rowid = ".$this->product_fourn_price_id;
			// TODO Add price_base_type and price_ttc

			dol_syslog(get_class($this).'::update_buyprice update knowing id of line = product_fourn_price_id = '.$this->product_fourn_price_id, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
                // Call trigger
                $result=$this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_UPDATE',$user);
                if ($result < 0) $error++;
                // End call triggers

				if (empty($error))
				{
					$this->db->commit();
					return $this->product_fourn_price_id;
				}
				else
				{
					$this->db->rollback();
					return -1;
				}
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
            dol_syslog(get_class($this) . '::update_buyprice without knowing id of line, so we delete from company, quantity and supplier_ref and insert again', LOG_DEBUG);

            // Delete price for this quantity
            $sql = "DELETE FROM  " . MAIN_DB_PREFIX . "product_fournisseur_price";
            $sql .= " WHERE fk_soc = " . $fourn->id . " AND ref_fourn = '" . $this->db->escape($ref_fourn) . "' AND quantity = " . $qty . " AND entity = " . $conf->entity;
            $resql = $this->db->query($sql);
            if ($resql) {
                // Add price for this quantity to supplier
                $sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_fournisseur_price(";
                $sql.= " multicurrency_price, multicurrency_unitprice, multicurrency_tx, fk_multicurrency, multicurrency_code,";
                $sql .= "datec, fk_product, fk_soc, ref_fourn, fk_user, price, quantity, remise_percent, remise, unitprice, tva_tx, charges, fk_availability, default_vat_code, info_bits, entity, delivery_time_days, supplier_reputation)";
                $sql .= " values(";
                $sql.= (isset($multicurrency_buyprice)?"'".$this->db->escape(price2num($multicurrency_buyprice))."'":'null').",";
                $sql.= (isset($multicurrency_unitBuyPrice)?"'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'":'null').",";
                $sql.= (isset($multicurrency_tx)?"'".$this->db->escape($multicurrency_tx)."'":'1').",";
                $sql.= (isset($fk_multicurrency)?"'".$this->db->escape($fk_multicurrency)."'":'null').",";
                $sql.= (isset($multicurrency_code)?"'".$this->db->escape($multicurrency_code)."'":'null').",";
                $sql .= " '" . $this->db->idate($now) . "',";
                $sql .= " " . $this->id . ",";
                $sql .= " " . $fourn->id . ",";
                $sql .= " '" . $this->db->escape($ref_fourn) . "',";
                $sql .= " " . $user->id . ",";
                $sql .= " " . $buyprice . ",";
                $sql .= " " . $qty . ",";
                $sql .= " " . $remise_percent . ",";
                $sql .= " " . $remise . ",";
                $sql .= " " . $unitBuyPrice . ",";
                $sql .= " " . $tva_tx . ",";
                $sql .= " " . $charges . ",";
                $sql .= " " . $availability . ",";
                $sql .= " ".($newdefaultvatcode?"'".$this->db->escape($newdefaultvatcode)."'":"null").",";
                $sql .= " " . $newnpr . ",";
                $sql .= $conf->entity . ",";
                $sql .= $delivery_time_days . ",";
                $sql .= (empty($supplier_reputation) ? 'NULL' : "'" . $this->db->escape($supplier_reputation) . "'");
                $sql .= ")";

                $idinserted = 0;

                $resql = $this->db->query($sql);
                if ($resql) {
                    $idinserted = $this->db->last_insert_id(MAIN_DB_PREFIX . "product_fournisseur_price");
                }
                else {
                    $error++;
                }

                if (! $error && empty($conf->global->PRODUCT_PRICE_SUPPLIER_NO_LOG)) {
                    // Add record into log table
                    $sql = "INSERT INTO " . MAIN_DB_PREFIX . "product_fournisseur_price_log(";
                    $sql.= " multicurrency_price, multicurrency_unitprice, multicurrency_tx, fk_multicurrency, multicurrency_code,";
                    $sql .= "datec, fk_product_fournisseur,fk_user,price,quantity)";
                    $sql .= "values(";
                    $sql.= (isset($multicurrency_buyprice)?"'".$this->db->escape(price2num($multicurrency_buyprice))."'":'null').",";
                    $sql.= (isset($multicurrency_unitBuyPrice)?"'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'":'null').",";
                    $sql.= (isset($multicurrency_tx)?"'".$this->db->escape($multicurrency_tx)."'":'1').",";
                    $sql.= (isset($fk_multicurrency)?"'".$this->db->escape($fk_multicurrency)."'":'null').",";
                    $sql.= (isset($multicurrency_code)?"'".$this->db->escape($multicurrency_code)."'":'null').",";
                    $sql .= " '" . $this->db->idate($now) . "',";
                    $sql .= " " . $this->product_fourn_id . ",";
                    $sql .= " " . $user->id . ",";
                    $sql .= " " . price2num($buyprice) . ",";
                    $sql .= " " . $qty;
                    $sql .= ")";

                    $resql = $this->db->query($sql);
                    if (! $resql) {
                        $error++;
                    }
                }

                if (! $error) {
                    // Call trigger
                    $result = $this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_CREATE', $user);
                    if ($result < 0)
                        $error++;
                        // End call triggers

                    if (empty($error)) {
                        $this->db->commit();
                        return $idinserted;
                    } else {
                        $this->db->rollback();
                        return -1;
                    }
                } else {
                    $this->error = $this->db->lasterror() . " sql=" . $sql;
                    $this->db->rollback();
                    return -2;
                }
            } else {
                $this->error = $this->db->lasterror() . " sql=" . $sql;
                $this->db->rollback();
                return - 1;
            }
        }
    }

    /**
     *    Loads the price information of a provider
     *
     *    @param    int     $rowid              Line id
     *    @param    int     $ignore_expression  Ignores the math expression for calculating price and uses the db value instead
     *    @return   int 					    < 0 if KO, 0 if OK but not found, > 0 if OK
     */
    function fetch_product_fournisseur_price($rowid, $ignore_expression = 0)
    {
        global $conf;

        $sql = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.default_vat_code, pfp.info_bits as fourn_tva_npr, pfp.fk_availability,";
        $sql.= " pfp.fk_soc, pfp.ref_fourn, pfp.fk_product, pfp.charges, pfp.fk_supplier_price_expression, pfp.delivery_time_days,";
        $sql.= " pfp.supplier_reputation";
        $sql.= " ,pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " WHERE pfp.rowid = ".$rowid;

        dol_syslog(get_class($this)."::fetch_product_fournisseur_price", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $obj = $this->db->fetch_object($resql);
            if ($obj)
            {
            	$this->product_fourn_price_id	= $rowid;
            	$this->id						= $obj->fk_product;
            	$this->fk_product				= $obj->fk_product;
            	$this->product_id				= $obj->fk_product;	// deprecated
            	$this->fourn_id					= $obj->fk_soc;
            	$this->fourn_ref				= $obj->ref_fourn; // deprecated
	            $this->ref_supplier             = $obj->ref_fourn;
            	$this->fourn_price				= $obj->price;
            	$this->fourn_charges            = $obj->charges;	// deprecated
            	$this->fourn_qty                = $obj->quantity;
            	$this->fourn_remise_percent     = $obj->remise_percent;
            	$this->fourn_remise             = $obj->remise;
            	$this->fourn_unitprice          = $obj->unitprice;
            	$this->fourn_tva_tx				= $obj->tva_tx;
            	$this->fourn_tva_npr			= $obj->fourn_tva_npr;
            	// Add also localtaxes
            	$this->fk_availability			= $obj->fk_availability;
				$this->delivery_time_days		= $obj->delivery_time_days;
                $this->fk_supplier_price_expression      = $obj->fk_supplier_price_expression;
                $this->supplier_reputation      = $obj->supplier_reputation;
                $this->default_vat_code         = $obj->default_vat_code;

                $this->fourn_multicurrency_price       = $obj->multicurrency_price;
                $this->fourn_multicurrency_unitprice   = $obj->multicurrency_unitprice;
                $this->fourn_multicurrency_tx          = $obj->multicurrency_tx;
                $this->fourn_multicurrency_id          = $obj->fk_multicurrency;
                $this->fourn_multicurrency_code        = $obj->multicurrency_code;

                if (empty($ignore_expression) && !empty($this->fk_supplier_price_expression))
                {
                    $priceparser = new PriceParser($this->db);
                    $price_result = $priceparser->parseProductSupplier($this);
                    if ($price_result >= 0) {
                    	$this->fourn_price = $price_result;
                    	//recalculation of unitprice, as probably the price changed...
	                    if ($this->fourn_qty!=0)
	                    {
	                        $this->fourn_unitprice = price2num($this->fourn_price/$this->fourn_qty,'MU');
	                    }
	                    else
	                    {
	                        $this->fourn_unitprice="";
	                    }
                    }
                }

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
            return -1;
        }
    }


    /**
     *    List all supplier prices of a product
     *
     *    @param    int		$prodid	    Id of product
     *    @param	string	$sortfield	Sort field
     *    @param	string	$sortorder	Sort order
     *    @param	int		$limit		Limit
     *    @param	int		$offset		Offset
     *    @return	array				Array of Products with new properties to define supplier price
     */
    function list_product_fournisseur_price($prodid, $sortfield='', $sortorder='', $limit=0, $offset=0)
    {
        global $conf;

        $sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id,";
        $sql.= " pfp.rowid as product_fourn_pri_id, pfp.ref_fourn, pfp.fk_product as product_fourn_id, pfp.fk_supplier_price_expression,";
        $sql.= " pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability, pfp.charges, pfp.info_bits, pfp.delivery_time_days, pfp.supplier_reputation";
        $sql.= " ,pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code";
        $sql.= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= ", ".MAIN_DB_PREFIX."societe as s";
        $sql.= " WHERE pfp.entity IN (".getEntity('productsupplierprice').")";
        $sql.= " AND pfp.fk_soc = s.rowid";
        $sql.= " AND s.status=1"; // only enabled company selected
        $sql.= " AND pfp.fk_product = ".$prodid;
        if (empty($sortfield)) $sql.= " ORDER BY s.nom, pfp.quantity, pfp.price";
        else $sql.= $this->db->order($sortfield, $sortorder);
        $sql.=$this->db->plimit($limit, $offset);
        dol_syslog(get_class($this)."::list_product_fournisseur_price", LOG_DEBUG);

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
                $prodfourn->ref_supplier			= $record["ref_fourn"];
                $prodfourn->fourn_price				= $record["price"];
                $prodfourn->fourn_qty				= $record["quantity"];
				$prodfourn->fourn_remise_percent	= $record["remise_percent"];
				$prodfourn->fourn_remise			= $record["remise"];
				$prodfourn->fourn_unitprice			= $record["unitprice"];
				$prodfourn->fourn_charges           = $record["charges"];		// deprecated
                $prodfourn->fourn_tva_tx			= $record["tva_tx"];
                $prodfourn->fourn_id				= $record["fourn_id"];
                $prodfourn->fourn_name				= $record["supplier_name"];
                $prodfourn->fk_availability			= $record["fk_availability"];
				$prodfourn->delivery_time_days		= $record["delivery_time_days"];
                $prodfourn->id						= $prodid;
                $prodfourn->fourn_tva_npr					= $record["info_bits"];
                $prodfourn->fk_supplier_price_expression    = $record["fk_supplier_price_expression"];
				$prodfourn->supplier_reputation    = $record["supplier_reputation"];

                $prodfourn->fourn_multicurrency_price       = $record["multicurrency_price"];
                $prodfourn->fourn_multicurrency_unitprice   = $record["multicurrency_unitprice"];
                $prodfourn->fourn_multicurrency_tx          = $record["multicurrency_tx"];
                $prodfourn->fourn_multicurrency_id          = $record["fk_multicurrency"];
                $prodfourn->fourn_multicurrency_code        = $record["multicurrency_code"];

                if (!empty($conf->dynamicprices->enabled) && !empty($prodfourn->fk_supplier_price_expression)) {
                    $priceparser = new PriceParser($this->db);
                    $price_result = $priceparser->parseProductSupplier($prodfourn);
                    if ($price_result >= 0) {
                    	$prodfourn->fourn_price = $price_result;
                    	$prodfourn->fourn_unitprice = null; //force recalculation of unitprice, as probably the price changed...
                    }
                }

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
            return -1;
        }
    }

    /**
     *  Load properties for minimum price
     *
     *  @param	int		$prodid	    Product id
     *  @param	int		$qty		Minimum quantity
     *  @param	int		$socid		get min price for specific supplier
     *  @return int					<0 if KO, 0=Not found of no product id provided, >0 if OK
     */
    function find_min_price_product_fournisseur($prodid, $qty=0, $socid=0)
    {
        global $conf;

        if (empty($prodid))
        {
        	dol_syslog("Warning function find_min_price_product_fournisseur were called with prodid empty. May be a bug.", LOG_WARNING);
        	return 0;
        }

        $this->product_fourn_price_id = '';
        $this->product_fourn_id       = '';
        $this->fourn_ref              = '';
        $this->fourn_price            = '';
        $this->fourn_qty              = '';
        $this->fourn_remise_percent   = '';
        $this->fourn_remise           = '';
        $this->fourn_unitprice        = '';
        $this->fourn_id               = '';
        $this->fourn_name             = '';
		$this->delivery_time_days  = '';
        $this->id                     = '';

        $this->fourn_multicurrency_price       = '';
        $this->fourn_multicurrency_unitprice   = '';
        $this->fourn_multicurrency_tx          = '';
        $this->fourn_multicurrency_id          = '';
        $this->fourn_multicurrency_code        = '';

        $sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id,";
        $sql.= " pfp.rowid as product_fourn_price_id, pfp.ref_fourn,";
        $sql.= " pfp.price, pfp.quantity, pfp.unitprice, pfp.tva_tx, pfp.charges,";
        $sql.= " pfp.remise, pfp.remise_percent, pfp.fk_supplier_price_expression, pfp.delivery_time_days";
        $sql.= " ,pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code";
        $sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
        $sql.= " WHERE s.entity IN (".getEntity('societe').")";
        $sql.= " AND pfp.fk_product = ".$prodid;
        $sql.= " AND pfp.fk_soc = s.rowid";
        $sql.= " AND s.status = 1"; // only enabled society
        if ($qty > 0) $sql.= " AND pfp.quantity <= ".$qty;
	if ($socid > 0) $sql.= ' AND pfp.fk_soc = '.$socid;

        dol_syslog(get_class($this)."::find_min_price_product_fournisseur", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $record_array = array();

            //Store each record to array for later search of min
            while ($record = $this->db->fetch_array($resql))
            {
                $record_array[]=$record;
            }

            if (count($record_array) == 0)
            {
                $this->db->free($resql);
                return 0;
            }
            else
            {
                $min = -1;
                foreach($record_array as $record)
                {
                    $fourn_price = $record["price"];
                    // discount calculated buy price
                    $fourn_unitprice = $record["unitprice"] * (1 - $record["remise_percent"] / 100) - $record["remise"];
                    if (!empty($conf->dynamicprices->enabled) && !empty($record["fk_supplier_price_expression"])) {
                        $prod_supplier = new ProductFournisseur($this->db);
                        $prod_supplier->product_fourn_price_id = $record["product_fourn_price_id"];
                        $prod_supplier->id = $prodid;
                        $prod_supplier->fourn_qty = $record["quantity"];
                        $prod_supplier->fourn_tva_tx = $record["tva_tx"];
                        $prod_supplier->fk_supplier_price_expression = $record["fk_supplier_price_expression"];
                        $priceparser = new PriceParser($this->db);
                        $price_result = $priceparser->parseProductSupplier($prod_supplier);
                        if ($price_result >= 0) {
                            $fourn_price = price2num($price_result,'MU');
                            if ($record["quantity"] != 0)
                            {
                                $fourn_unitprice = price2num($fourn_price/$record["quantity"],'MU');
                            }
                            else
                            {
                                $fourn_unitprice = $fourn_price;
                            }
                        }
                    }
                    if ($fourn_unitprice < $min || $min == -1)
                    {
                        $this->product_fourn_price_id   = $record["product_fourn_price_id"];
                        $this->ref_supplier             = $record["ref_fourn"];
                        $this->ref_fourn                = $record["ref_fourn"];     // deprecated
                        $this->fourn_ref                = $record["ref_fourn"];     // deprecated
                        $this->fourn_price              = $fourn_price;
                        $this->fourn_qty                = $record["quantity"];
                        $this->fourn_remise_percent     = $record["remise_percent"];
                        $this->fourn_remise             = $record["remise"];
                        $this->fourn_unitprice          = $record["unitprice"];
                        $this->fourn_charges            = $record["charges"];		// deprecated
                        $this->fourn_tva_tx             = $record["tva_tx"];
                        $this->fourn_id                 = $record["fourn_id"];
                        $this->fourn_name               = $record["supplier_name"];
						$this->delivery_time_days		= $record["delivery_time_days"];
                        $this->fk_supplier_price_expression      = $record["fk_supplier_price_expression"];
                        $this->id                       = $prodid;
                        $this->fourn_multicurrency_price       = $record["multicurrency_price"];
                        $this->fourn_multicurrency_unitprice   = $record["multicurrency_unitprice"];
                        $this->fourn_multicurrency_tx          = $record["multicurrency_tx"];
                        $this->fourn_multicurrency_id          = $record["fk_multicurrency"];
                        $this->fourn_multicurrency_code        = $record["multicurrency_code"];
                        $min = $fourn_unitprice;
                    }
                }
            }

            $this->db->free($resql);
            return 1;
        }
        else
		{
            $this->error=$this->db->error();
            return -1;
        }
    }

    /**
     *  Sets the supplier price expression
     *
     *  @param  int     $expression_id	Expression
     *  @return int                 	<0 if KO, >0 if OK
     */
    function setSupplierPriceExpression($expression_id)
    {
        global $conf;

        // Clean parameters
        $this->db->begin();
        $expression_id = $expression_id != 0 ? $expression_id : 'NULL';

        $sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
        $sql.= " SET fk_supplier_price_expression = ".$expression_id;
        $sql.= " WHERE rowid = ".$this->product_fourn_price_id;

        dol_syslog(get_class($this)."::setSupplierPriceExpression", LOG_DEBUG);

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->db->commit();
            return 1;
        }
        else
        {
            $this->error=$this->db->error()." sql=".$sql;
            $this->db->rollback();
            return -1;
        }
    }

    /**
     *	Display supplier of product
     *
     *	@param	int		$withpicto		Add picto
     *	@param	string	$option			Target of link ('', 'customer', 'prospect', 'supplier')
     *	@param	int		$maxlen			Max length of name
     *  @param	integer	$notooltip		1=Disable tooltip
     *	@return	string					String with supplier price
	 *  TODO Remove this method. Use getNomUrl directly.
     */
    function getSocNomUrl($withpicto=0,$option='supplier',$maxlen=0,$notooltip=0)
    {
        $thirdparty = new Fournisseur($this->db);
        $thirdparty->fetch($this->fourn_id);

        return $thirdparty->getNomUrl($withpicto,$option,$maxlen,$notooltip);
    }

    /**
     *	Display price of product
     *
     *  @param  int     $showunitprice    Show "Unit price" into output string
     *  @param  int     $showsuptitle     Show "Supplier" into output string
     *  @param  int     $maxlen           Max length of name
     *  @param  integer $notooltip        1=Disable tooltip
     *  @param  array   $productFournList  list of ProductFournisseur objects
     *                                    to display in table format.
     *  @return string                    String with supplier price
     */
    function display_price_product_fournisseur($showunitprice=1,$showsuptitle=1,$maxlen=0,$notooltip=0, $productFournList=array())
    {
        global $langs;

        $out = '';
        $langs->load("suppliers");
        if (count($productFournList) > 0) {
            $out .= '<table class="nobordernopadding" width="100%">';
            $out .= '<tr><td class="liste_titre" align="right">'.($showunitprice?$langs->trans("Price").' '.$langs->trans("HT"):'').'</td>';
            $out .= '<td class="liste_titre" align="right">'.($showunitprice?$langs->trans("QtyMin"):'').'</td>';
            $out .= '<td class="liste_titre">'.$langs->trans("Supplier").'</td>';
            $out .= '<td class="liste_titre">'.$langs->trans("SupplierRef").'</td></tr>';
            foreach ($productFournList as $productFourn) {
                $out.= '<tr><td align="right">'.($showunitprice?price($productFourn->fourn_unitprice * (1 -$productFourn->fourn_remise_percent/100) - $productFourn->fourn_remise):'').'</td>';
                $out.= '<td align="right">'.($showunitprice?$productFourn->fourn_qty:'').'</td>';
                $out.= '<td>'.$productFourn->getSocNomUrl(1, 'supplier', $maxlen, $notooltip).'</td>';
                $out.= '<td>'.$productFourn->fourn_ref.'<td></tr>';
            }
            $out .= '</table>';
        } else {
            $out=($showunitprice?price($this->fourn_unitprice * (1 - $this->fourn_remise_percent/100) + $this->fourn_remise).' '.$langs->trans("HT").' &nbsp; (':'').($showsuptitle?$langs->trans("Supplier").': ':'').$this->getSocNomUrl(1, 'supplier', $maxlen, $notooltip).' / '.$langs->trans("SupplierRef").': '.$this->fourn_ref.($showunitprice?')':'');
        }
        return $out;
    }

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'product_fournisseur_price'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}

}

