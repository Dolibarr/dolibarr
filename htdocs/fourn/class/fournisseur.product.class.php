<?php
/* Copyright (C) 2005		Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011	Laurent Destailleur	  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2014	Regis Houssin		  <regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent		  <jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel	  <christophe.battarel@altairis.fr>
 * Copyright (C) 2015		Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke         <charlie@patas-monkey.com>
 * Copyright (C) 2019-2021  Frédéric France       <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Pierre Ardoin         <mapiolca@me.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	public $product_fourn_price_id; // id of ligne product-supplier

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @deprecated
	 * @see $ref_supplier
	 */
	public $fourn_ref;

	public $delivery_time_days;
	public $ref_supplier; // ref supplier (can be set by get_buyprice)
	public $desc_supplier;
	public $vatrate_supplier; // default vat rate for this supplier/qty/product (can be set by get_buyprice)

	public $product_id;
	public $product_ref;

	public $fourn_id; //supplier id
	public $fourn_qty; // quantity for price (can be set by get_buyprice)
	public $fourn_pu; // unit price for quantity (can be set by get_buyprice)

	public $fourn_price; // price for quantity
	public $fourn_remise_percent; // discount for quantity (percent)
	public $fourn_remise; // discount for quantity (amount)

	public $product_fourn_id; // product-supplier id
	public $product_fourn_entity;

	/**
	 * @var int ID user_id - user who created/updated supplier price
	 */
	public $user_id;

	/**
	 * @var int ID availability delay - visible/used if option FOURN_PRODUCT_AVAILABILITY is on (duplicate information compared to delivery delay)
	 */
	public $fk_availability;

	public $fourn_unitprice;
	public $fourn_tva_tx;
	public $fourn_tva_npr;

	/**
	 * @var int ID
	 */
	public $fk_supplier_price_expression;

	public $supplier_reputation; // reputation of supplier
	public $reputations = array(); // list of available supplier reputations

	// Multicurreny
	public $fourn_multicurrency_id;
	public $fourn_multicurrency_code;
	public $fourn_multicurrency_tx;
	public $fourn_multicurrency_price;
	public $fourn_multicurrency_unitprice;

	/**
	 * @deprecated
	 * @see $supplier_barcode
	 */
	public $fourn_barcode;

	/**
	 * @var string $supplier_barcode - Supplier barcode
	 */
	public $supplier_barcode;

	/**
	 * @deprecated
	 * @see $supplier_fk_barcode_type
	 */
	public $fourn_fk_barcode_type;

	/**
	 * @var string $supplier_fk_barcode_type - Supplier barcode type
	 */
	public $supplier_fk_barcode_type;

	public $packaging;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs;

		$this->db = $db;
		$langs->load("suppliers");
		$this->reputations = array('-1'=>'', 'FAVORITE'=>$langs->trans('Favorite'), 'NOTTHGOOD'=>$langs->trans('NotTheGoodQualitySupplier'), 'DONOTORDER'=>$langs->trans('DoNotOrderThisProductToThisSupplier'));
		$this->fields = array_merge($this->fields, array(
			'fk_product' => array('type'=>'integer:Product:product/class/product.class.php:1', 'label'=>'Fkproduct', 'enabled'=>'1', 'position'=>30, 'notnull'=>0, 'visible'=>-1,),
			'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'enabled'=>'1', 'position'=>35, 'notnull'=>0, 'visible'=>-1,),
			'ref_fourn' => array('type'=>'varchar(255)', 'label'=>'Reffourn', 'enabled'=>'1', 'position'=>40, 'notnull'=>0, 'visible'=>-1,),
			'desc_fourn' => array('type'=>'text', 'label'=>'Descfourn', 'enabled'=>'1', 'position'=>45, 'notnull'=>0, 'visible'=>-1,),
			'fk_availability' => array('type'=>'integer', 'label'=>'Fkavailability', 'enabled'=>'1', 'position'=>50, 'notnull'=>0, 'visible'=>-1,),
			'price' => array('type'=>'double(24,8)', 'label'=>'Price', 'enabled'=>'1', 'position'=>55, 'notnull'=>0, 'visible'=>-1,),
			'quantity' => array('type'=>'double', 'label'=>'Quantity', 'enabled'=>'1', 'position'=>60, 'notnull'=>0, 'visible'=>-1,),
			'remise_percent' => array('type'=>'double', 'label'=>'Remisepercent', 'enabled'=>'1', 'position'=>65, 'notnull'=>1, 'visible'=>-1,),
			'remise' => array('type'=>'double', 'label'=>'Remise', 'enabled'=>'1', 'position'=>70, 'notnull'=>1, 'visible'=>-1,),
			'unitprice' => array('type'=>'double(24,8)', 'label'=>'Unitprice', 'enabled'=>'1', 'position'=>75, 'notnull'=>0, 'visible'=>-1,),
			'charges' => array('type'=>'double(24,8)', 'label'=>'Charges', 'enabled'=>'1', 'position'=>80, 'notnull'=>0, 'visible'=>-1,),
			'default_vat_code' => array('type'=>'varchar(10)', 'label'=>'Defaultvatcode', 'enabled'=>'1', 'position'=>85, 'notnull'=>0, 'visible'=>-1,),
			'tva_tx' => array('type'=>'double(6,3)', 'label'=>'Tvatx', 'enabled'=>'1', 'position'=>90, 'notnull'=>1, 'visible'=>-1,),
			'info_bits' => array('type'=>'integer', 'label'=>'Infobits', 'enabled'=>'1', 'position'=>95, 'notnull'=>1, 'visible'=>-1,),
			'fk_user' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Fkuser', 'enabled'=>'1', 'position'=>100, 'notnull'=>0, 'visible'=>-1,),
			'fk_supplier_price_expression' => array('type'=>'integer', 'label'=>'Fksupplierpriceexpression', 'enabled'=>'1', 'position'=>105, 'notnull'=>0, 'visible'=>-1,),
			'delivery_time_days' => array('type'=>'integer', 'label'=>'Deliverytimedays', 'enabled'=>'1', 'position'=>115, 'notnull'=>0, 'visible'=>-1,),
			'supplier_reputation' => array('type'=>'varchar(10)', 'label'=>'Supplierreputation', 'enabled'=>'1', 'position'=>120, 'notnull'=>0, 'visible'=>-1,),
			'fk_multicurrency' => array('type'=>'integer', 'label'=>'Fkmulticurrency', 'enabled'=>'1', 'position'=>125, 'notnull'=>0, 'visible'=>-1,),
			'multicurrency_code' => array('type'=>'varchar(255)', 'label'=>'Multicurrencycode', 'enabled'=>'1', 'position'=>130, 'notnull'=>0, 'visible'=>-1,),
			'multicurrency_tx' => array('type'=>'double(24,8)', 'label'=>'Multicurrencytx', 'enabled'=>'1', 'position'=>135, 'notnull'=>0, 'visible'=>-1,),
			'multicurrency_price' => array('type'=>'double(24,8)', 'label'=>'Multicurrencyprice', 'enabled'=>'1', 'position'=>140, 'notnull'=>0, 'visible'=>-1,),
			'multicurrency_unitprice' => array('type'=>'double(24,8)', 'label'=>'Multicurrencyunitprice', 'enabled'=>'1', 'position'=>145, 'notnull'=>0, 'visible'=>-1,),
			'localtax1_tx' => array('type'=>'double(6,3)', 'label'=>'Localtax1tx', 'enabled'=>'1', 'position'=>150, 'notnull'=>0, 'visible'=>-1,),
			'localtax1_type' => array('type'=>'varchar(10)', 'label'=>'Localtax1type', 'enabled'=>'1', 'position'=>155, 'notnull'=>1, 'visible'=>-1,),
			'localtax2_tx' => array('type'=>'double(6,3)', 'label'=>'Localtax2tx', 'enabled'=>'1', 'position'=>160, 'notnull'=>0, 'visible'=>-1,),
			'localtax2_type' => array('type'=>'varchar(10)', 'label'=>'Localtax2type', 'enabled'=>'1', 'position'=>165, 'notnull'=>1, 'visible'=>-1,),
			'fk_barcode_type' => array('type'=>'integer', 'label'=>'Fkbarcodetype', 'enabled'=>'1', 'position'=>175, 'notnull'=>0, 'visible'=>-1,),
			'packaging' => array('type'=>'varchar(64)', 'label'=>'Packaging', 'enabled'=>'1', 'position'=>180, 'notnull'=>0, 'visible'=>-1,),
		));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Remove all prices for this couple supplier-product
	 *
	 *    @param	int		$id_fourn   Supplier Id
	 *    @return   int         		< 0 if error, > 0 if ok
	 */
	public function remove_fournisseur($id_fourn)
	{
		// phpcs:enable
		$ok = 1;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql .= " WHERE fk_product = ".$this->id." AND fk_soc = ".$id_fourn;

		dol_syslog(get_class($this)."::remove_fournisseur", LOG_DEBUG);
		$resql2 = $this->db->query($sql);
		if (!$resql2)
		{
			$this->error = $this->db->lasterror();
			$ok = 0;
		}

		if ($ok)
		{
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Remove a price for a couple supplier-product
	 *
	 * 	@param	int		$rowid		Line id of price
	 *	@return	int					<0 if KO, >0 if OK
	 */
	public function remove_product_fournisseur_price($rowid)
	{
		// phpcs:enable
		global $conf, $user;

		$error = 0;

		$this->db->begin();

		// Call trigger
		$result = $this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_DELETE', $user);
		if ($result < 0) $error++;
		// End call triggers

		if (empty($error))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql .= " WHERE rowid = ".$rowid;

			dol_syslog(get_class($this)."::remove_product_fournisseur_price", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Modify the purchase price for a supplier
	 *
	 *    @param  	float		$qty				            Min quantity for which price is valid
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
	 *    @param  	string		$desc_fourn     	            Custom description for product_fourn_price
	 *    @param  	string		$barcode     	                Barcode
	 *    @param  	int		    $fk_barcode_type     	        Barcode type
	 *    @return	int											<0 if KO, >=0 if OK
	 */
	public function update_buyprice($qty, $buyprice, $user, $price_base_type, $fourn, $availability, $ref_fourn, $tva_tx, $charges = 0, $remise_percent = 0, $remise = 0, $newnpr = 0, $delivery_time_days = 0, $supplier_reputation = '', $localtaxes_array = array(), $newdefaultvatcode = '', $multicurrency_buyprice = 0, $multicurrency_price_base_type = 'HT', $multicurrency_tx = 1, $multicurrency_code = '', $desc_fourn = '', $barcode = '', $fk_barcode_type = '')
	{
		// phpcs:enable
		global $conf, $langs;
		//global $mysoc;

		// Clean parameter
		if (empty($qty)) $qty = 0;
		if (empty($buyprice)) $buyprice = 0;
		if (empty($charges)) $charges = 0;
		if (empty($availability)) $availability = 0;
		if (empty($remise_percent)) $remise_percent = 0;
		if (empty($supplier_reputation) || $supplier_reputation == -1) $supplier_reputation = '';
		if ($delivery_time_days != '' && !is_numeric($delivery_time_days)) $delivery_time_days = '';
		if ($price_base_type == 'TTC')
		{
			$ttx = $tva_tx;
			$buyprice = $buyprice / (1 + ($ttx / 100));
		}

		// Multicurrency
		$multicurrency_unitBuyPrice = null;
		$fk_multicurrency = null;
		if (!empty($conf->multicurrency->enabled)) {
			if (empty($multicurrency_tx)) $multicurrency_tx = 1;
			if (empty($multicurrency_buyprice)) $multicurrency_buyprice = 0;
			if ($multicurrency_price_base_type == 'TTC')
			{
				$ttx = $tva_tx;
				$multicurrency_buyprice = $multicurrency_buyprice / (1 + ($ttx / 100));
			}
			$multicurrency_buyprice = price2num($multicurrency_buyprice, 'MU');
			$multicurrency_unitBuyPrice = price2num($multicurrency_buyprice / $qty, 'MU');

			$buyprice = $multicurrency_buyprice / $multicurrency_tx;
			$fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $multicurrency_code);
		}

		$buyprice = price2num($buyprice, 'MU');
		$charges = price2num($charges, 'MU');
		$qty = price2num($qty, 'MS');
		$unitBuyPrice = price2num($buyprice / $qty, 'MU');

		$packaging = price2num(((empty($this->packaging) || $this->packaging < $qty) ? $qty : $this->packaging), 'MS');

		$error = 0;
		$now = dol_now();

		$newvat = $tva_tx;

		if (count($localtaxes_array) > 0)
		{
			$localtaxtype1 = $localtaxes_array['0'];
			$localtax1 = $localtaxes_array['1'];
			$localtaxtype2 = $localtaxes_array['2'];
			$localtax2 = $localtaxes_array['3'];
		} else { // old method. deprecated because ot can't retrieve type
			$localtaxtype1 = '0';
			$localtax1 = get_localtax($newvat, 1);
			$localtaxtype2 = '0';
			$localtax2 = get_localtax($newvat, 2);
		}
		if (empty($localtax1)) $localtax1 = 0; // If = '' then = 0
		if (empty($localtax2)) $localtax2 = 0; // If = '' then = 0

		// Check parameters
		if ($buyprice != '' && !is_numeric($buyprice))
		{
		}

		$this->db->begin();

		if ($this->product_fourn_price_id > 0)
		{
			// check if price already logged, if not first log current price
			$logPrices = $this->listProductFournisseurPriceLog($this->product_fourn_price_id);
			if (is_array($logPrices) && count($logPrices) == 0)
			{
				$currentPfp = new self($this->db);
				$result = $currentPfp->fetch_product_fournisseur_price($this->product_fourn_price_id);
				if ($result > 0 && $currentPfp->fourn_price != 0)
				{
					$currentPfpUser = new User($this->db);
					$result = $currentPfpUser->fetch($currentPfp->user_id);
					if ($result > 0) {
						$currentPfp->logPrice(
							$currentPfpUser,
							$currentPfp->date_creation,
							$currentPfp->fourn_price,
							$currentPfp->fourn_qty,
							$currentPfp->fourn_multicurrency_price,
							$currentPfp->fourn_multicurrency_unitprice,
							$currentPfp->fourn_multicurrency_tx,
							$currentPfp->fourn_multicurrency_id,
							$currentPfp->fourn_multicurrency_code
						);
					}
				}
			}
			$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql .= " SET fk_user = ".$user->id." ,";
			$sql .= " ref_fourn = '".$this->db->escape($ref_fourn)."',";
			$sql .= " desc_fourn = '".$this->db->escape($desc_fourn)."',";
			$sql .= " price = ".$buyprice.",";
			$sql .= " quantity = ".$qty.",";
			$sql .= " remise_percent = ".$remise_percent.",";
			$sql .= " remise = ".$remise.",";
			$sql .= " unitprice = ".$unitBuyPrice.",";
			$sql .= " fk_availability = ".$availability.",";
			$sql .= " multicurrency_price = ".(isset($multicurrency_buyprice) ? "'".$this->db->escape(price2num($multicurrency_buyprice))."'" : 'null').",";
			$sql .= " multicurrency_unitprice = ".(isset($multicurrency_unitBuyPrice) ? "'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'" : 'null').",";
			$sql .= " multicurrency_tx = ".(isset($multicurrency_tx) ? "'".$this->db->escape($multicurrency_tx)."'" : '1').",";
			$sql .= " fk_multicurrency = ".(isset($fk_multicurrency) ? "'".$this->db->escape($fk_multicurrency)."'" : 'null').",";
			$sql .= " multicurrency_code = ".(isset($multicurrency_code) ? "'".$this->db->escape($multicurrency_code)."'" : 'null').",";
			$sql .= " entity = ".$conf->entity.",";
			$sql .= " tva_tx = ".price2num($tva_tx).",";
			// TODO Add localtax1 and localtax2
			//$sql.= " localtax1_tx=".($localtax1>=0?$localtax1:'NULL').",";
			//$sql.= " localtax2_tx=".($localtax2>=0?$localtax2:'NULL').",";
			//$sql.= " localtax1_type=".($localtaxtype1!=''?"'".$this->db->escape($localtaxtype1)."'":"'0'").",";
			//$sql.= " localtax2_type=".($localtaxtype2!=''?"'".$this->db->escape($localtaxtype2)."'":"'0'").",";
			$sql .= " default_vat_code=".($newdefaultvatcode ? "'".$this->db->escape($newdefaultvatcode)."'" : "null").",";
			$sql .= " info_bits = ".$newnpr.",";
			$sql .= " charges = ".$charges.","; // deprecated
			$sql .= " delivery_time_days = ".($delivery_time_days != '' ? $delivery_time_days : 'null').",";
			$sql .= " supplier_reputation = ".(empty($supplier_reputation) ? 'NULL' : "'".$this->db->escape($supplier_reputation)."'").",";
			$sql .= " barcode = ".(empty($barcode) ? 'NULL' : "'".$this->db->escape($barcode)."'").",";
			$sql .= " fk_barcode_type = ".(empty($fk_barcode_type) ? 'NULL' : "'".$this->db->escape($fk_barcode_type)."'");
			if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) $sql .= ", packaging = ".(empty($packaging) ? 1 : $packaging);
			$sql .= " WHERE rowid = ".$this->product_fourn_price_id;
			// TODO Add price_base_type and price_ttc

			dol_syslog(get_class($this).'::update_buyprice update knowing id of line = product_fourn_price_id = '.$this->product_fourn_price_id, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql)
			{
				// Call trigger
				$result = $this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_UPDATE', $user);
				if ($result < 0) $error++;
				// End call triggers
				if (!$error && empty($conf->global->PRODUCT_PRICE_SUPPLIER_NO_LOG))
				{
					$result = $this->logPrice($user, $now, $buyprice, $qty, $multicurrency_buyprice, $multicurrency_unitBuyPrice, $multicurrency_tx, $fk_multicurrency, $multicurrency_code);
					if ($result < 0) {
						$error++;
					}
				}
				if (empty($error))
				{
					$this->db->commit();
					return $this->product_fourn_price_id;
				} else {
					$this->db->rollback();
					return -1;
				}
			} else {
				$this->error = $this->db->error()." sql=".$sql;
				$this->db->rollback();
				return -2;
			}
		} else {
			dol_syslog(get_class($this).'::update_buyprice without knowing id of line, so we delete from company, quantity and supplier_ref and insert again', LOG_DEBUG);

			// Delete price for this quantity
			$sql = "DELETE FROM  ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql .= " WHERE fk_soc = ".$fourn->id." AND ref_fourn = '".$this->db->escape($ref_fourn)."' AND quantity = ".$qty." AND entity = ".$conf->entity;
			$resql = $this->db->query($sql);
			if ($resql) {
				// Add price for this quantity to supplier
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
				$sql .= " multicurrency_price, multicurrency_unitprice, multicurrency_tx, fk_multicurrency, multicurrency_code,";
				$sql .= "datec, fk_product, fk_soc, ref_fourn, desc_fourn, fk_user, price, quantity, remise_percent, remise, unitprice, tva_tx, charges, fk_availability, default_vat_code, info_bits, entity, delivery_time_days, supplier_reputation, barcode, fk_barcode_type)";
				if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) $sql .= ", packaging";
				$sql .= " values(";
				$sql .= (isset($multicurrency_buyprice) ? "'".$this->db->escape(price2num($multicurrency_buyprice))."'" : 'null').",";
				$sql .= (isset($multicurrency_unitBuyPrice) ? "'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'" : 'null').",";
				$sql .= (isset($multicurrency_tx) ? "'".$this->db->escape($multicurrency_tx)."'" : '1').",";
				$sql .= (isset($fk_multicurrency) ? "'".$this->db->escape($fk_multicurrency)."'" : 'null').",";
				$sql .= (isset($multicurrency_code) ? "'".$this->db->escape($multicurrency_code)."'" : 'null').",";
				$sql .= " '".$this->db->idate($now)."',";
				$sql .= " ".$this->id.",";
				$sql .= " ".$fourn->id.",";
				$sql .= " '".$this->db->escape($ref_fourn)."',";
				$sql .= " '".$this->db->escape($desc_fourn)."',";
				$sql .= " ".$user->id.",";
				$sql .= " ".$buyprice.",";
				$sql .= " ".$qty.",";
				$sql .= " ".$remise_percent.",";
				$sql .= " ".$remise.",";
				$sql .= " ".$unitBuyPrice.",";
				$sql .= " ".$tva_tx.",";
				$sql .= " ".$charges.",";
				$sql .= " ".$availability.",";
				$sql .= " ".($newdefaultvatcode ? "'".$this->db->escape($newdefaultvatcode)."'" : "null").",";
				$sql .= " ".$newnpr.",";
				$sql .= $conf->entity.",";
				$sql .= ($delivery_time_days != '' ? $delivery_time_days : 'null').",";
				$sql .= (empty($supplier_reputation) ? 'NULL' : "'".$this->db->escape($supplier_reputation)."'").",";
				$sql .= (empty($barcode) ? 'NULL' : "'".$this->db->escape($barcode)."'").",";
				$sql .= (empty($fk_barcode_type) ? 'NULL' : "'".$this->db->escape($fk_barcode_type)."'");
				if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) $sql .= ", ".(empty($this->packaging) ? 1 : $this->db->escape($this->packaging));
				$sql .= ")";

				$this->product_fourn_price_id = 0;

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->product_fourn_price_id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_fournisseur_price");
				} else {
					$error++;
				}

				if (!$error && empty($conf->global->PRODUCT_PRICE_SUPPLIER_NO_LOG)) {
					// Add record into log table
					// $this->product_fourn_price_id must be set
					$result = $this->logPrice($user, $now, $buyprice, $qty, $multicurrency_buyprice, $multicurrency_unitBuyPrice, $multicurrency_tx, $fk_multicurrency, $multicurrency_code);
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error) {
					// Call trigger
					$result = $this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_CREATE', $user);
					if ($result < 0)
						$error++;
						// End call triggers

					if (empty($error)) {
						$this->db->commit();
						return $this->product_fourn_price_id;
					} else {
						$this->db->rollback();
						return -1;
					}
				} else {
					$this->error = $this->db->lasterror()." sql=".$sql;
					$this->db->rollback();
					return -2;
				}
			} else {
				$this->error = $this->db->lasterror()." sql=".$sql;
				$this->db->rollback();
				return -1;
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Loads the price information of a provider
	 *
	 *    @param    int     $rowid              Line id
	 *    @param    int     $ignore_expression  Ignores the math expression for calculating price and uses the db value instead
	 *    @return   int 					    < 0 if KO, 0 if OK but not found, > 0 if OK
	 */
	public function fetch_product_fournisseur_price($rowid, $ignore_expression = 0)
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT pfp.rowid, pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.default_vat_code, pfp.info_bits as fourn_tva_npr, pfp.fk_availability,";
		$sql .= " pfp.fk_soc, pfp.ref_fourn, pfp.desc_fourn, pfp.fk_product, pfp.charges, pfp.fk_supplier_price_expression, pfp.delivery_time_days,";
		$sql .= " pfp.supplier_reputation, pfp.fk_user, pfp.datec,";
		$sql .= " pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code,";
		$sql .= " pfp.barcode, pfp.fk_barcode_type, pfp.packaging,";
		$sql .= " p.ref as product_ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product as p";
		$sql .= " WHERE pfp.rowid = ".(int) $rowid;
		$sql .= " AND pfp.fk_product = p.rowid";

		dol_syslog(get_class($this)."::fetch_product_fournisseur_price", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			if ($obj)
			{
				$this->product_fourn_price_id = $rowid;
				$this->id = $obj->fk_product;

				$this->fk_product				= $obj->fk_product;
				$this->product_id				= $obj->fk_product;
				$this->product_ref				= $obj->product_ref;

				$this->fourn_id					= $obj->fk_soc;
				$this->fourn_ref				= $obj->ref_fourn; // deprecated
				$this->ref_supplier             = $obj->ref_fourn;
				$this->desc_supplier            = $obj->desc_fourn;
				$this->fourn_price = $obj->price;
				$this->fourn_charges            = $obj->charges; // deprecated
				$this->fourn_qty                = $obj->quantity;
				$this->fourn_remise_percent     = $obj->remise_percent;
				$this->fourn_remise             = $obj->remise;
				$this->fourn_unitprice          = $obj->unitprice;
				$this->fourn_tva_tx				= $obj->tva_tx;
				$this->fourn_tva_npr			= $obj->fourn_tva_npr;
				// Add also localtaxes
				$this->fk_availability = $obj->fk_availability;
				$this->delivery_time_days = $obj->delivery_time_days;
				$this->fk_supplier_price_expression = $obj->fk_supplier_price_expression;
				$this->supplier_reputation      = $obj->supplier_reputation;
				$this->default_vat_code         = $obj->default_vat_code;
				$this->user_id                  = $obj->fk_user;
				$this->date_creation            = $this->db->jdate($obj->datec);
				$this->fourn_multicurrency_price       = $obj->multicurrency_price;
				$this->fourn_multicurrency_unitprice   = $obj->multicurrency_unitprice;
				$this->fourn_multicurrency_tx          = $obj->multicurrency_tx;
				$this->fourn_multicurrency_id          = $obj->fk_multicurrency;
				$this->fourn_multicurrency_code        = $obj->multicurrency_code;
				if (!empty($conf->barcode->enabled)) {
					$this->fourn_barcode = $obj->barcode; // deprecated
					$this->fourn_fk_barcode_type = $obj->fk_barcode_type; // deprecated
					$this->supplier_barcode = $obj->barcode;
					$this->supplier_fk_barcode_type = $obj->fk_barcode_type;
				}

				if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) {
					$this->packaging = $obj->packaging;
					if ($this->packaging < $this->fourn_qty) $this->packaging = $this->fourn_qty;
				}

				if (empty($ignore_expression) && !empty($this->fk_supplier_price_expression))
				{
					$priceparser = new PriceParser($this->db);
					$price_result = $priceparser->parseProductSupplier($this);
					if ($price_result >= 0) {
						$this->fourn_price = $price_result;
						//recalculation of unitprice, as probably the price changed...
						if ($this->fourn_qty != 0)
						{
							$this->fourn_unitprice = price2num($this->fourn_price / $this->fourn_qty, 'MU');
						} else {
							$this->fourn_unitprice = "";
						}
					}
				}

				return 1;
			} else {
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
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
	public function list_product_fournisseur_price($prodid, $sortfield = '', $sortorder = '', $limit = 0, $offset = 0)
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id, p.ref as product_ref,";
		$sql .= " pfp.rowid as product_fourn_pri_id, pfp.entity, pfp.ref_fourn, pfp.desc_fourn, pfp.fk_product as product_fourn_id, pfp.fk_supplier_price_expression,";
		$sql .= " pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability, pfp.charges, pfp.info_bits, pfp.delivery_time_days, pfp.supplier_reputation,";
		$sql .= " pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code, pfp.datec, pfp.tms,";
		$sql .= " pfp.barcode, pfp.fk_barcode_type";
		if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) $sql .= ", pfp.packaging";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE pfp.entity IN (".getEntity('productsupplierprice').")";
		$sql .= " AND pfp.fk_soc = s.rowid AND pfp.fk_product = p.rowid";
		$sql .= " AND s.status=1"; // only enabled company selected
		$sql .= " AND pfp.fk_product = ".$prodid;
		if (empty($sortfield)) $sql .= " ORDER BY s.nom, pfp.quantity, pfp.price";
		else $sql .= $this->db->order($sortfield, $sortorder);
		$sql .= $this->db->plimit($limit, $offset);
		dol_syslog(get_class($this)."::list_product_fournisseur_price", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$retarray = array();

			while ($record = $this->db->fetch_array($resql))
			{
				//define base attribute
				$prodfourn = new ProductFournisseur($this->db);

				$prodfourn->product_ref = $record["product_ref"];
				$prodfourn->product_fourn_price_id = $record["product_fourn_pri_id"];
				$prodfourn->product_fourn_id = $record["product_fourn_id"];
				$prodfourn->product_fourn_entity = $record["entity"];
				$prodfourn->ref_supplier			= $record["ref_fourn"];
				$prodfourn->fourn_ref = $record["ref_fourn"];
				$prodfourn->desc_supplier = $record["desc_fourn"];
				$prodfourn->fourn_price				= $record["price"];
				$prodfourn->fourn_qty = $record["quantity"];
				$prodfourn->fourn_remise_percent = $record["remise_percent"];
				$prodfourn->fourn_remise = $record["remise"];
				$prodfourn->fourn_unitprice = $record["unitprice"];
				$prodfourn->fourn_charges = $record["charges"]; // deprecated
				$prodfourn->fourn_tva_tx = $record["tva_tx"];
				$prodfourn->fourn_id = $record["fourn_id"];
				$prodfourn->fourn_name = $record["supplier_name"];
				$prodfourn->fk_availability			= $record["fk_availability"];
				$prodfourn->delivery_time_days = $record["delivery_time_days"];
				$prodfourn->id = $prodid;
				$prodfourn->fourn_tva_npr					= $record["info_bits"];
				$prodfourn->fk_supplier_price_expression = $record["fk_supplier_price_expression"];
				$prodfourn->supplier_reputation = $record["supplier_reputation"];
				$prodfourn->fourn_date_creation          = $this->db->jdate($record['datec']);
				$prodfourn->fourn_date_modification      = $this->db->jdate($record['tms']);

				$prodfourn->fourn_multicurrency_price       = $record["multicurrency_price"];
				$prodfourn->fourn_multicurrency_unitprice   = $record["multicurrency_unitprice"];
				$prodfourn->fourn_multicurrency_tx          = $record["multicurrency_tx"];
				$prodfourn->fourn_multicurrency_id          = $record["fk_multicurrency"];
				$prodfourn->fourn_multicurrency_code        = $record["multicurrency_code"];

				if (!empty($conf->global->PRODUCT_USE_SUPPLIER_PACKAGING)) {
					$prodfourn->packaging = $record["packaging"];
					if ($prodfourn->packaging < $prodfourn->fourn_qty) $prodfourn->packaging = $prodfourn->fourn_qty;
				}

				if (!empty($conf->barcode->enabled)) {
					$prodfourn->supplier_barcode = $record["barcode"];
					$prodfourn->supplier_fk_barcode_type = $record["fk_barcode_type"];
				}

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
					if ($prodfourn->fourn_qty != 0)
					{
						$prodfourn->fourn_unitprice = price2num($prodfourn->fourn_price / $prodfourn->fourn_qty, 'MU');
					} else {
						$prodfourn->fourn_unitprice = "";
					}
				}

				$retarray[] = $prodfourn;
			}

			$this->db->free($resql);
			return $retarray;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Load properties for minimum price
	 *
	 *  @param	int		$prodid	    Product id
	 *  @param	int		$qty		Minimum quantity
	 *  @param	int		$socid		get min price for specific supplier
	 *  @return int					<0 if KO, 0=Not found of no product id provided, >0 if OK
	 */
	public function find_min_price_product_fournisseur($prodid, $qty = 0, $socid = 0)
	{
		// phpcs:enable
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
		$this->delivery_time_days = '';
		$this->id                     = '';

		$this->fourn_multicurrency_price       = '';
		$this->fourn_multicurrency_unitprice   = '';
		$this->fourn_multicurrency_tx          = '';
		$this->fourn_multicurrency_id          = '';
		$this->fourn_multicurrency_code        = '';

		$sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id,";
		$sql .= " pfp.rowid as product_fourn_price_id, pfp.ref_fourn,";
		$sql .= " pfp.price, pfp.quantity, pfp.unitprice, pfp.tva_tx, pfp.charges,";
		$sql .= " pfp.remise, pfp.remise_percent, pfp.fk_supplier_price_expression, pfp.delivery_time_days";
		$sql .= " ,pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql .= " WHERE s.entity IN (".getEntity('societe').")";
		$sql .= " AND pfp.entity = ".$conf->entity; // only current entity
		$sql .= " AND pfp.fk_product = ".$prodid;
		$sql .= " AND pfp.fk_soc = s.rowid";
		$sql .= " AND s.status = 1"; // only enabled society
		if ($qty > 0) $sql .= " AND pfp.quantity <= ".$qty;
		if ($socid > 0) $sql .= ' AND pfp.fk_soc = '.$socid;

		dol_syslog(get_class($this)."::find_min_price_product_fournisseur", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$record_array = array();

			//Store each record to array for later search of min
			while ($record = $this->db->fetch_array($resql))
			{
				$record_array[] = $record;
			}

			if (count($record_array) == 0)
			{
				$this->db->free($resql);
				return 0;
			} else {
				$min = -1;
				foreach ($record_array as $record)
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
							$fourn_price = price2num($price_result, 'MU');
							if ($record["quantity"] != 0)
							{
								$fourn_unitprice = price2num($fourn_price / $record["quantity"], 'MU');
							} else {
								$fourn_unitprice = $fourn_price;
							}
						}
					}
					if ($fourn_unitprice < $min || $min == -1)
					{
						$this->product_fourn_price_id   = $record["product_fourn_price_id"];
						$this->ref_supplier             = $record["ref_fourn"];
						$this->ref_fourn                = $record["ref_fourn"]; // deprecated
						$this->fourn_ref                = $record["ref_fourn"]; // deprecated
						$this->fourn_price              = $fourn_price;
						$this->fourn_qty                = $record["quantity"];
						$this->fourn_remise_percent     = $record["remise_percent"];
						$this->fourn_remise             = $record["remise"];
						$this->fourn_unitprice          = $record["unitprice"];
						$this->fourn_charges            = $record["charges"]; // deprecated
						$this->fourn_tva_tx             = $record["tva_tx"];
						$this->fourn_id                 = $record["fourn_id"];
						$this->fourn_name               = $record["supplier_name"];
						$this->delivery_time_days = $record["delivery_time_days"];
						$this->fk_supplier_price_expression = $record["fk_supplier_price_expression"];
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
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *  Sets the supplier price expression
	 *
	 *  @param  int     $expression_id	Expression
	 *  @return int                 	<0 if KO, >0 if OK
	 */
	public function setSupplierPriceExpression($expression_id)
	{
		global $conf;

		// Clean parameters
		$this->db->begin();
		$expression_id = $expression_id != 0 ? $expression_id : 'NULL';

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql .= " SET fk_supplier_price_expression = ".$expression_id;
		$sql .= " WHERE rowid = ".$this->product_fourn_price_id;

		dol_syslog(get_class($this)."::setSupplierPriceExpression", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->db->commit();
			return 1;
		} else {
			$this->error = $this->db->error()." sql=".$sql;
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
	public function getSocNomUrl($withpicto = 0, $option = 'supplier', $maxlen = 0, $notooltip = 0)
	{
		$thirdparty = new Fournisseur($this->db);
		$thirdparty->fetch($this->fourn_id);

		return $thirdparty->getNomUrl($withpicto, $option, $maxlen, $notooltip);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Display price of product
	 *
	 *  @param  int     $showunitprice    Show "Unit price" into output string
	 *  @param  int     $showsuptitle     Show "Supplier" into output string
	 *  @param  int     $maxlen           Max length of name
	 *  @param  integer $notooltip        1=Disable tooltip
	 *  @param  array   $productFournList list of ProductFournisseur objects
	 *                                    to display in table format.
	 *  @return string                    String with supplier price
	 */
	public function display_price_product_fournisseur($showunitprice = 1, $showsuptitle = 1, $maxlen = 0, $notooltip = 0, $productFournList = array())
	{
		// phpcs:enable
		global $langs;

		$out = '';
		$langs->load("suppliers");
		if (count($productFournList) > 0) {
			$out .= '<table class="nobordernopadding" width="100%">';
			$out .= '<tr><td class="liste_titre right">'.($showunitprice ? $langs->trans("Price").' '.$langs->trans("HT") : '').'</td>';
			$out .= '<td class="liste_titre right">'.($showunitprice ? $langs->trans("QtyMin") : '').'</td>';
			$out .= '<td class="liste_titre">'.$langs->trans("Supplier").'</td>';
			$out .= '<td class="liste_titre">'.$langs->trans("SupplierRef").'</td></tr>';
			foreach ($productFournList as $productFourn) {
				$out .= '<tr><td class="right">'.($showunitprice ?price($productFourn->fourn_unitprice * (1 - $productFourn->fourn_remise_percent / 100) - $productFourn->fourn_remise) : '').'</td>';
				$out .= '<td class="right">'.($showunitprice ? $productFourn->fourn_qty : '').'</td>';
				$out .= '<td>'.$productFourn->getSocNomUrl(1, 'supplier', $maxlen, $notooltip).'</td>';
				$out .= '<td>'.$productFourn->fourn_ref.'<td></tr>';
			}
			$out .= '</table>';
		} else {
			$out = ($showunitprice ?price($this->fourn_unitprice * (1 - $this->fourn_remise_percent / 100) + $this->fourn_remise).' '.$langs->trans("HT").' &nbsp; (' : '').($showsuptitle ? $langs->trans("Supplier").': ' : '').$this->getSocNomUrl(1, 'supplier', $maxlen, $notooltip).' / '.$langs->trans("SupplierRef").': '.$this->fourn_ref.($showunitprice ? ')' : '');
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

	/**
	 *    List supplier prices log of a supplier price
	 *
	 *    @param    int     $product_fourn_price_id Id of supplier price
	 *    @param	string  $sortfield	            Sort field
	 *    @param	string  $sortorder              Sort order
	 *    @param	int     $limit                  Limit
	 *    @param	int     $offset                 Offset
	 *    @return	array   Array of Log prices
	 */
	public function listProductFournisseurPriceLog($product_fourn_price_id, $sortfield = '', $sortorder = '', $limit = 0, $offset = 0)
	{
		$sql = "SELECT";
		$sql .= " u.lastname,";
		$sql .= " pfpl.rowid, pfp.ref_fourn as supplier_ref, pfpl.datec,";
		$sql .= " pfpl.price, pfpl.quantity,";
		$sql .= " pfpl.fk_multicurrency, pfpl.multicurrency_code, pfpl.multicurrency_tx, pfpl.multicurrency_price, pfpl.multicurrency_unitprice";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price_log as pfpl,";
		$sql .= " ".MAIN_DB_PREFIX."product_fournisseur_price as pfp,";
		$sql .= " ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE pfp.entity IN (".getEntity('productprice').")";
		$sql .= " AND pfpl.fk_user = u.rowid";
		$sql .= " AND pfp.rowid = pfpl.fk_product_fournisseur";
		$sql .= " AND pfpl.fk_product_fournisseur = ".$product_fourn_price_id;
		if (empty($sortfield)) $sql .= " ORDER BY pfpl.datec";
		else $sql .= $this->db->order($sortfield, $sortorder);
		$sql .= $this->db->plimit($limit, $offset);
		dol_syslog(get_class($this)."::list_product_fournisseur_price_log", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql)
		{
			$retarray = array();

			while ($obj = $this->db->fetch_object($resql))
			{
				$tmparray = array();
				$tmparray['rowid'] = $obj->rowid;
				$tmparray['supplier_ref'] = $obj->supplier_ref;
				$tmparray['datec'] = $this->db->jdate($obj->datec);
				$tmparray['lastname'] = $obj->lastname;
				$tmparray['price'] = $obj->price;
				$tmparray['quantity'] = $obj->quantity;
				$tmparray['fk_multicurrency'] = $obj->fk_multicurrency;
				$tmparray['multicurrency_code'] = $obj->multicurrency_code;
				$tmparray['multicurrency_tx'] = $obj->multicurrency_tx;
				$tmparray['multicurrency_price'] = $obj->multicurrency_price;
				$tmparray['multicurrency_unitprice'] = $obj->multicurrency_unitprice;

				$retarray[] = $tmparray;
			}

			$this->db->free($resql);
			return $retarray;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	/**
	 *	Display log price of product supplier price
	 *
	 *  @param  array   $productFournLogList    list of ProductFournisseur price log objects
	 *                                          to display in table format.
	 *  @return string  HTML String with supplier price
	 */
	public function displayPriceProductFournisseurLog($productFournLogList = array())
	{
		global $conf, $langs;

		$out = '';
		$langs->load("suppliers");
		if (count($productFournLogList) > 0) {
			$out .= '<table class="noborder centpercent">';
			$out .= '<tr class="liste_titre"><td class="liste_titre">'.$langs->trans("Date").'</td>';
			$out .= '<td class="liste_titre right">'.$langs->trans("Price").'</td>';
			//$out .= '<td class="liste_titre right">'.$langs->trans("QtyMin").'</td>';
			$out .= '<td class="liste_titre">'.$langs->trans("User").'</td></tr>';
			foreach ($productFournLogList as $productFournLog) {
				$out .= '<tr><td class="right">'.dol_print_date($productFournLog['datec'], 'dayhour', 'tzuser').'</td>';
				$out .= '<td class="right">'.price($productFournLog['price'], 0, $langs, 1, -1, -1, $conf->currency);
				if ($productFournLog['multicurrency_code'] != $conf->currency) {
					$out .= ' ('.price($productFournLog['multicurrency_price'], 0, $langs, 1, -1, -1, $productFournLog['multicurrency_code']).')';
				}
				$out .= '</td>';
				//$out.= '<td class="right">'.$productFournLog['quantity'].'</td>';
				$out .= '<td>'.$productFournLog['lastname'].'</td></tr>';
			}
			$out .= '</table>';
		}
		return $out;
	}


	/**
	 *  Return a link to the object card (with optionaly the picto).
	 *  Used getNomUrl of ProductFournisseur if a specific supplier ref is loaded. Otherwise use Product->getNomUrl().
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$label = '';

		if (!empty($this->entity)) {
			$tmpphoto = $this->show_photos('product', $conf->product->multidir_output[$this->entity], 1, 1, 0, 0, 0, 80);
			if ($this->nbphoto > 0) {
				$label .= '<div class="photointooltip">';
				$label .= $tmpphoto;
				$label .= '</div><div style="clear: both;"></div>';
			}
		}

		if ($this->type == Product::TYPE_PRODUCT) {
			$label .= img_picto('', 'product').' <u class="paddingrightonly">'.$langs->trans("Product").'</u>';
		} elseif ($this->type == Product::TYPE_SERVICE) {
			$label .= img_picto('', 'service').' <u class="paddingrightonly">'.$langs->trans("Service").'</u>';
		}
		if (isset($this->status) && isset($this->status_buy)) {
			$label .= ' '.$this->getLibStatut(5, 0);
			$label .= ' '.$this->getLibStatut(5, 1);
		}

		if (!empty($this->ref)) {
			$label .= '<br><b>'.$langs->trans('ProductRef').':</b> '.($this->ref ? $this->ref : $this->product_ref);
		}
		if (!empty($this->label)) {
			$label .= '<br><b>'.$langs->trans('ProductLabel').':</b> '.$this->label;
		}
		$label .= '<br><b>'.$langs->trans('RefSupplier').':</b> '.$this->ref_supplier;

		if ($this->type == Product::TYPE_PRODUCT || !empty($conf->global->STOCK_SUPPORTS_SERVICES)) {
			if (!empty($conf->productbatch->enabled)) {
				$langs->load("productbatch");
				$label .= "<br><b>".$langs->trans("ManageLotSerial").'</b>: '.$this->getLibStatut(0, 2);
			}
		}
		if (!empty($conf->barcode->enabled)) {
			$label .= '<br><b>'.$langs->trans('BarCode').':</b> '.$this->barcode;
		}

		if ($this->type == Product::TYPE_PRODUCT) {
			if ($this->weight) {
				$label .= "<br><b>".$langs->trans("Weight").'</b>: '.$this->weight.' '.measuringUnitString(0, "weight", $this->weight_units);
			}
			$labelsize = "";
			if ($this->length) {
				$labelsize .= ($labelsize ? " - " : "")."<b>".$langs->trans("Length").'</b>: '.$this->length.' '.measuringUnitString(0, 'size', $this->length_units);
			}
			if ($this->width) {
				$labelsize .= ($labelsize ? " - " : "")."<b>".$langs->trans("Width").'</b>: '.$this->width.' '.measuringUnitString(0, 'size', $this->width_units);
			}
			if ($this->height) {
				$labelsize .= ($labelsize ? " - " : "")."<b>".$langs->trans("Height").'</b>: '.$this->height.' '.measuringUnitString(0, 'size', $this->height_units);
			}
			if ($labelsize) $label .= "<br>".$labelsize;

			$labelsurfacevolume = "";
			if ($this->surface) {
				$labelsurfacevolume .= ($labelsurfacevolume ? " - " : "")."<b>".$langs->trans("Surface").'</b>: '.$this->surface.' '.measuringUnitString(0, 'surface', $this->surface_units);
			}
			if ($this->volume) {
				$labelsurfacevolume .= ($labelsurfacevolume ? " - " : "")."<b>".$langs->trans("Volume").'</b>: '.$this->volume.' '.measuringUnitString(0, 'volume', $this->volume_units);
			}
			if ($labelsurfacevolume) $label .= "<br>".$labelsurfacevolume;
		}

		if (!empty($conf->accounting->enabled) && $this->status) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
			$label .= '<br><b>'.$langs->trans('ProductAccountancySellCode').':</b> '.length_accountg($this->accountancy_code_sell);
			$label .= '<br><b>'.$langs->trans('ProductAccountancySellIntraCode').':</b> '.length_accountg($this->accountancy_code_sell_intra);
			$label .= '<br><b>'.$langs->trans('ProductAccountancySellExportCode').':</b> '.length_accountg($this->accountancy_code_sell_export);
		}
		if (!empty($conf->accounting->enabled) && $this->status_buy) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
			$label .= '<br><b>'.$langs->trans('ProductAccountancyBuyCode').':</b> '.length_accountg($this->accountancy_code_buy);
			$label .= '<br><b>'.$langs->trans('ProductAccountancyBuyIntraCode').':</b> '.length_accountg($this->accountancy_code_buy_intra);
			$label .= '<br><b>'.$langs->trans('ProductAccountancyBuyExportCode').':</b> '.length_accountg($this->accountancy_code_buy_export);
		}

		$logPrices = $this->listProductFournisseurPriceLog($this->product_fourn_price_id, 'pfpl.datec', 'DESC'); // set sort order here
		if (is_array($logPrices) && count($logPrices) > 0) {
			$label .= '<br><br>';
			$label .= '<u>'.$langs->trans("History").'</u>';
			$label .= $this->displayPriceProductFournisseurLog($logPrices);
		}

		$url = dol_buildpath('/product/fournisseurs.php', 1).'?id='.$this->id.'&action=add_price&socid='.$this->fourn_id.'&rowid='.$this->product_fourn_price_id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("SupplierRef");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref.($this->ref_supplier ? ' ('.$this->ref_supplier.')' : '');
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}

	/**
	 * Private function to log price history
	 *
	 * @param User      $user                           Object user who adds/changes price
	 * @param integer   $datec                          date create
	 * @param float     $buyprice                       price for qty
	 * @param float     $qty                            qty for price
	 * @param float     $multicurrency_buyprice         Purchase price for the quantity min in currency
	 * @param float     $multicurrency_unitBuyPrice     Unit Purchase price in currency
	 * @param float     $multicurrency_tx               Rate currency
	 * @param int       $fk_multicurrency               key multi currency
	 * @param string    $multicurrency_code	            Currency code
	 *
	 * @return int < 0 NOK > 0 OK
	 */
	private function logPrice($user, $datec, $buyprice, $qty, $multicurrency_buyprice = null, $multicurrency_unitBuyPrice = null, $multicurrency_tx = null, $fk_multicurrency = null, $multicurrency_code = null)
	{
		// Add record into log table
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price_log(";
		$sql .= " multicurrency_price, multicurrency_unitprice, multicurrency_tx, fk_multicurrency, multicurrency_code,";
		$sql .= "datec, fk_product_fournisseur,fk_user,price,quantity)";
		$sql .= "values(";
		$sql .= (isset($multicurrency_buyprice) ? "'".$this->db->escape(price2num($multicurrency_buyprice))."'" : 'null').",";
		$sql .= (isset($multicurrency_unitBuyPrice) ? "'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'" : 'null').",";
		$sql .= (isset($multicurrency_tx) ? "'".$this->db->escape($multicurrency_tx)."'" : '1').",";
		$sql .= (isset($fk_multicurrency) ? "'".$this->db->escape($fk_multicurrency)."'" : 'null').",";
		$sql .= (isset($multicurrency_code) ? "'".$this->db->escape($multicurrency_code)."'" : 'null').",";
		$sql .= "'".$this->db->idate($datec)."',";
		$sql .= " ".$this->product_fourn_price_id.",";
		$sql .= " ".$user->id.",";
		$sql .= " ".price2num($buyprice).",";
		$sql .= " ".$qty;
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		} else {
			return 1;
		}
	}

	/**
     * Load list of objects in memory from the database.
     *
     * @param  string      $sortorder    Sort Order
     * @param  string      $sortfield    Sort field
     * @param  int         $limit        limit
     * @param  int         $offset       Offset
     * @param  array       $filter       Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
     * @param  string      $filtermode   Filter mode (AND or OR)
     * @return array|int                 int <0 if KO, array of pages if OK
     */
    public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
    {
        global $conf;

        dol_syslog(__METHOD__, LOG_DEBUG);

        $records = array();

        $sql = 'SELECT ';
        $sql .= $this->getFieldList();
        $sql .= ' FROM '.MAIN_DB_PREFIX.'product_fournisseur_price as t';
        $sql .= ' WHERE t.entity IN ('.getEntity('productsupplierprice').')';

        // Manage filter
        $sqlwhere = array();
        if (count($filter) > 0) {
            foreach ($filter as $key => $value) {
                if ($key == 't.rowid') {
                    $sqlwhere[] = $key.'='.$value;
                } elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
                    $sqlwhere[] = $key.' = \''.$this->db->idate($value).'\'';
                } elseif ($key == 'customsql') {
                    $sqlwhere[] = $value;
                } elseif (strpos($value, '%') === false) {
                    $sqlwhere[] = $key.' IN ('.$this->db->sanitize($this->db->escape($value)).')';
                } else {
                    $sqlwhere[] = $key.' LIKE \'%'.$this->db->escape($value).'%\'';
                }
            }
        }
        if (count($sqlwhere) > 0) {
            $sql .= ' AND ('.implode(' '.$filtermode.' ', $sqlwhere).')';
        }

        if (!empty($sortfield)) {
            $sql .= $this->db->order($sortfield, $sortorder);
        }
        if (!empty($limit)) {
            $sql .= ' '.$this->db->plimit($limit, $offset);
        }

        $resql = $this->db->query($sql);
        if ($resql) {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < ($limit ? min($limit, $num) : $num)) {
                $obj = $this->db->fetch_object($resql);

                $record = new self($this->db);
                $record->setVarsFromFetchObj($obj);

                $records[$record->id] = $record;

                $i++;
            }
            $this->db->free($resql);

            return $records;
        } else {
            $this->errors[] = 'Error '.$this->db->lasterror();
            dol_syslog(__METHOD__.' '.join(',', $this->errors), LOG_ERR);

            return -1;
        }
    }
	/**
	 * Function to concat keys of fields
	 *
	 * @return string
	 */
	protected function getFieldList()
	{
		$keys = array('rowid', 'entity', 'datec', 'tms', 'fk_product', 'fk_soc', 'ref_fourn', 'desc_fourn',  'fk_availability', 'price', 'quantity', 'remise_percent', 'remise', 'unitprice', 'charges', 'default_vat_code', 'tva_tx', 'info_bits', 'fk_user', 'fk_supplier_price_expression', 'import_key', 'delivery_time_days', 'supplier_reputation', 'fk_multicurrency', 'multicurrency_code', 'multicurrency_tx', 'multicurrency_price', 'multicurrency_unitprice', 'localtax1_tx', 'localtax1_type', 'localtax2_tx', 'localtax2_type', 'barcode', 'fk_barcode_type', 'packaging');
		return implode(',', $keys);
	}
}
