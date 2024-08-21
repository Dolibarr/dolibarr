<?php
/* Copyright (C) 2005		Rodolphe Quiedeville  <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2011	Laurent Destailleur	  <eldy@users.sourceforge.net>
 * Copyright (C) 2009-2014	Regis Houssin		  <regis.houssin@inodbox.com>
 * Copyright (C) 2011		Juanjo Menent		  <jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel	  <christophe.battarel@altairis.fr>
 * Copyright (C) 2015		Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2016-2023	Charlene Benke         <charlene@patas-monkey.com>
 * Copyright (C) 2019-2024  Frédéric France       <frederic.france@free.fr>
 * Copyright (C) 2020       Pierre Ardoin         <mapiolca@me.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
require_once DOL_DOCUMENT_ROOT.'/product/class/productfournisseurprice.class.php';


/**
 * 	Class to manage predefined suppliers products
 */
class ProductFournisseur extends Product
{
	/**
	 * @var DoliDB		Database handler.
	 */
	public $db;

	/**
	 * @var string		Error code (or message)
	 */
	public $error = '';

	/**
	 * @var int			ID of ligne product-supplier
	 */
	public $product_fourn_price_id;

	/**
	 * @var int			ID
	 */
	public $id;

	/**
	 * @var string
	 * @deprecated
	 * @see $ref_supplier
	 */
	public $fourn_ref;

	/**
	 * @var int			The product lead time in days.
	 */
	public $delivery_time_days;

	/**
	 * @var string		The product reference of the supplier. Can be set by get_buyprice().
	 */
	public $ref_supplier;

	/**
	 * @var string		The product description of the supplier.
	 */
	public $desc_supplier;

	/**
	 * @var string		The VAT rate by default for this {supplier, qty, product}. Can be set by get_buyprice().
	 */
	public $vatrate_supplier;

	/**
	 * @var int
	 * @deprecated
	 * @see $product_id
	 */
	public $fk_product;

	/**
	 * @var int			The product ID.
	 */
	public $product_id;

	/**
	 * @var string		The product reference.
	 */
	public $product_ref;

	/**
	 * @var int			The supplier ID.
	 */
	public $fourn_id;

	/**
	 * @var string		The supplier name.
	 */
	public $fourn_name;

	/**
	 * @var float		The Minimum Order Quantity (MOQ) for a given unit price. Can be set by get_buyprice().
	 */
	public $fourn_qty;

	/**
	 * @var float		The unit price for a given Minimum Order Quantity (MOQ). Can be set by get_buyprice().
	 */
	public $fourn_pu;

	/**
	 * @var float		The total price for a given Minimum Order Quantity (MOQ).
	 */
	public $fourn_price;

	/**
	 * @var float		The discount in percentage for a given Minimum Order Quantity (MOQ).
	 */
	public $fourn_remise_percent;

	/**
	 * @var float		The discount in value for a given Minimum Order Quantity (MOQ).
	 */
	public $fourn_remise;

	public $fourn_charges;	// when getDolGlobalString('PRODUCT_CHARGES') is set

	/**
	 * @var int		product-supplier id
	 */
	public $product_fourn_id;

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
	public $fourn_unitprice_with_discount;	// not saved into database
	public $fourn_tva_tx;
	public $fourn_tva_npr;

	/**
	 * @var int ID
	 */
	public $fk_supplier_price_expression;

	/**
	 * @var string reputation of supplier
	 */
	public $supplier_reputation;

	/**
	 * @var string[] list of available supplier reputations
	 */
	public $reputations = array();

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

	public $labelStatusShort;
	public $labelStatus;

	const STATUS_OPEN = 1;
	const STATUS_CANCELED = 0;


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
		$this->reputations = array('-1' => '', 'FAVORITE' => $langs->trans('Favorite'), 'NOTTHGOOD' => $langs->trans('NotTheGoodQualitySupplier'), 'DONOTORDER' => $langs->trans('DoNotOrderThisProductToThisSupplier'));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Remove all prices for this couple supplier-product
	 *
	 *    @param	int		$id_fourn   Supplier Id
	 *    @return   int         		Return integer < 0 if error, > 0 if ok
	 */
	public function remove_fournisseur($id_fourn)
	{
		// phpcs:enable
		$ok = 1;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql .= " WHERE fk_product = ".((int) $this->id)." AND fk_soc = ".((int) $id_fourn);

		dol_syslog(get_class($this)."::remove_fournisseur", LOG_DEBUG);
		$resql2 = $this->db->query($sql);
		if (!$resql2) {
			$this->error = $this->db->lasterror();
			$ok = 0;
		}

		if ($ok) {
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
	 *	@return	int					Return integer <0 if KO, >0 if OK
	 */
	public function remove_product_fournisseur_price($rowid)
	{
		// phpcs:enable
		global $conf, $user;

		$error = 0;

		$this->db->begin();

		// Call trigger
		$result = $this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_DELETE', $user);
		if ($result < 0) {
			$error++;
		}
		// End call triggers

		if (empty($error)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."product_fournisseur_price";
			$sql .= " WHERE rowid = ".((int) $rowid);

			dol_syslog(get_class($this)."::remove_product_fournisseur_price", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
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
	 *    @param  	string|float $charges			            costs affering to product
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
	 *    @param  	array		$options		     	       	Extrafields of product fourn price
	 *    @return	int											Return integer <0 if KO, >=0 if OK
	 */
	public function update_buyprice(
		$qty,
		$buyprice,
		$user,
		$price_base_type,
		$fourn,
		$availability,
		$ref_fourn,
		$tva_tx,
		$charges = 0,
		$remise_percent = 0,
		$remise = 0,
		$newnpr = 0,
		$delivery_time_days = 0,
		$supplier_reputation = '',
		$localtaxes_array = array(),
		$newdefaultvatcode = '',
		$multicurrency_buyprice = 0,
		$multicurrency_price_base_type = 'HT',
		$multicurrency_tx = 1,
		$multicurrency_code = '',
		$desc_fourn = '',
		$barcode = '',
		$fk_barcode_type = 0,
		$options = array()
	) {
		// phpcs:enable
		global $conf, $langs;
		//global $mysoc;

		// Clean parameter
		if (empty($qty)) {
			$qty = 0;
		}
		if (empty($buyprice)) {
			$buyprice = 0;
		}
		if (empty($charges)) {
			$charges = 0;
		}
		if (empty($availability)) {
			$availability = 0;
		}
		if (empty($remise_percent)) {
			$remise_percent = 0;
		}
		if (empty($supplier_reputation) || $supplier_reputation == -1) {
			$supplier_reputation = '';
		}
		if ($delivery_time_days != '' && !is_numeric($delivery_time_days)) {
			$delivery_time_days = 0;
		}
		if ($price_base_type == 'TTC') {
			$ttx = $tva_tx;
			$buyprice /= (1 + ($ttx / 100));
		}

		// Multicurrency
		$multicurrency_unitBuyPrice = null;
		$fk_multicurrency = null;
		if (isModEnabled("multicurrency")) {
			if (empty($multicurrency_tx)) {
				$multicurrency_tx = 1;
			}
			if (empty($multicurrency_buyprice)) {
				$multicurrency_buyprice = 0;
			}
			if ($multicurrency_price_base_type == 'TTC') {
				$ttx = $tva_tx;
				$multicurrency_buyprice /= (1 + ($ttx / 100));
			}
			$multicurrency_buyprice = price2num($multicurrency_buyprice, 'MU');
			$multicurrency_unitBuyPrice = price2num((float) $multicurrency_buyprice / $qty, 'MU');

			$buyprice = (float) $multicurrency_buyprice / $multicurrency_tx;
			$fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $multicurrency_code);
		}

		$buyprice = (float) price2num($buyprice, 'MU');
		$charges = (float) price2num($charges, 'MU');
		$qty = (float) price2num($qty, 'MS');
		$unitBuyPrice = (float) price2num($buyprice / $qty, 'MU');

		// We can have a purchase ref that need to buy 100 min for a given price and with a packaging of 50.
		//$packaging = price2num(((empty($this->packaging) || $this->packaging < $qty) ? $qty : $this->packaging), 'MS');
		$packaging = price2num((empty($this->packaging) ? $qty : $this->packaging), 'MS');

		$error = 0;
		$now = dol_now();

		$newvat = $tva_tx;

		if (count($localtaxes_array) > 0) {
			$localtaxtype1 = $localtaxes_array['0'];
			$localtax1 = $localtaxes_array['1'];
			$localtaxtype2 = $localtaxes_array['2'];
			$localtax2 = $localtaxes_array['3'];
		} else { // old method. deprecated because it can't retrieve type
			$localtaxtype1 = '0';
			$localtax1 = get_localtax($newvat, 1);
			$localtaxtype2 = '0';
			$localtax2 = get_localtax($newvat, 2);
		}
		if (empty($localtax1)) {
			$localtax1 = 0; // If = '' then = 0
		}
		if (empty($localtax2)) {
			$localtax2 = 0; // If = '' then = 0
		}

		$this->db->begin();

		if ($this->product_fourn_price_id > 0) {
			// check if price already logged, if not first log current price
			$logPrices = $this->listProductFournisseurPriceLog($this->product_fourn_price_id);
			if (is_array($logPrices) && count($logPrices) == 0) {
				$currentPfp = new self($this->db);
				$result = $currentPfp->fetch_product_fournisseur_price($this->product_fourn_price_id);
				if ($result > 0 && $currentPfp->fourn_price != 0) {
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
			$sql .= " SET fk_user = ".((int) $user->id)." ,";
			$sql .= " datec = '".$this->db->idate($now)."' ,";	// Note: Even if this is an update, we update the creation date as the log of each change is tracked into product_fournisseur_log.
			$sql .= " ref_fourn = '".$this->db->escape($ref_fourn)."',";
			$sql .= " desc_fourn = '".$this->db->escape($desc_fourn)."',";
			$sql .= " price = ".((float) $buyprice).",";
			$sql .= " quantity = ".((float) $qty).",";
			$sql .= " remise_percent = ".((float) $remise_percent).",";
			$sql .= " remise = ".((float) $remise).",";
			$sql .= " unitprice = ".((float) $unitBuyPrice).",";
			$sql .= " fk_availability = ".((int) $availability).",";
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
			$sql .= " info_bits = ".((int) $newnpr).",";
			$sql .= " charges = ".((float) $charges).","; // deprecated
			$sql .= " delivery_time_days = ".($delivery_time_days != '' ? ((int) $delivery_time_days) : 'null').",";
			$sql .= " supplier_reputation = ".(empty($supplier_reputation) ? 'NULL' : "'".$this->db->escape($supplier_reputation)."'").",";
			$sql .= " barcode = ".(empty($barcode) ? 'NULL' : "'".$this->db->escape($barcode)."'").",";
			$sql .= " fk_barcode_type = ".(empty($fk_barcode_type) ? 'NULL' : "'".$this->db->escape($fk_barcode_type)."'");
			if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
				$sql .= ", packaging = ".(empty($packaging) ? 1 : $packaging);
			}
			$sql .= " WHERE rowid = ".((int) $this->product_fourn_price_id);

			if (!$error) {
				if (!empty($options) && is_array($options)) {
					$productfournisseurprice = new ProductFournisseurPrice($this->db);
					$res = $productfournisseurprice->fetch($this->product_fourn_price_id);
					if ($res > 0) {
						foreach ($options as $key => $value) {
							$productfournisseurprice->array_options[$key] = $value;
						}
						$res = $productfournisseurprice->update($user);
						if ($res < 0) {
							$this->error = $productfournisseurprice->error;
							$this->errors = $productfournisseurprice->errors;
							$error++;
						}
					}
				}
			}

			// TODO Add price_base_type and price_ttc

			dol_syslog(get_class($this).'::update_buyprice update knowing id of line = product_fourn_price_id = '.$this->product_fourn_price_id, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				// Call trigger
				$result = $this->call_trigger('SUPPLIER_PRODUCT_BUYPRICE_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
				if (!$error && !getDolGlobalString('PRODUCT_PRICE_SUPPLIER_NO_LOG')) {
					$result = $this->logPrice($user, $now, $buyprice, $qty, $multicurrency_buyprice, $multicurrency_unitBuyPrice, $multicurrency_tx, $fk_multicurrency, $multicurrency_code);
					if ($result < 0) {
						$error++;
					}
				}
				if (empty($error)) {
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
			$sql .= " WHERE fk_soc = ".((int) $fourn->id)." AND ref_fourn = '".$this->db->escape($ref_fourn)."' AND quantity = ".((float) $qty)." AND entity = ".((int) $conf->entity);
			$resql = $this->db->query($sql);
			if ($resql) {
				// Add price for this quantity to supplier
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."product_fournisseur_price(";
				$sql .= " multicurrency_price, multicurrency_unitprice, multicurrency_tx, fk_multicurrency, multicurrency_code,";
				$sql .= "datec, fk_product, fk_soc, ref_fourn, desc_fourn, fk_user, price, quantity, remise_percent, remise, unitprice, tva_tx, charges, fk_availability, default_vat_code, info_bits, entity, delivery_time_days, supplier_reputation, barcode, fk_barcode_type";
				if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					$sql .= ", packaging";
				}
				$sql .= ") values(";
				$sql .= (isset($multicurrency_buyprice) ? "'".$this->db->escape(price2num($multicurrency_buyprice))."'" : 'null').",";
				$sql .= (isset($multicurrency_unitBuyPrice) ? "'".$this->db->escape(price2num($multicurrency_unitBuyPrice))."'" : 'null').",";
				$sql .= (isset($multicurrency_tx) ? "'".$this->db->escape($multicurrency_tx)."'" : '1').",";
				$sql .= (isset($fk_multicurrency) ? "'".$this->db->escape($fk_multicurrency)."'" : 'null').",";
				$sql .= (isset($multicurrency_code) ? "'".$this->db->escape($multicurrency_code)."'" : 'null').",";
				$sql .= " '".$this->db->idate($now)."',";
				$sql .= " ".((int) $this->id).",";
				$sql .= " ".((int) $fourn->id).",";
				$sql .= " '".$this->db->escape($ref_fourn)."',";
				$sql .= " '".$this->db->escape($desc_fourn)."',";
				$sql .= " ".((int) $user->id).",";
				$sql .= " ".price2num($buyprice).",";
				$sql .= " ".((float) $qty).",";
				$sql .= " ".((float) $remise_percent).",";
				$sql .= " ".((float) $remise).",";
				$sql .= " ".price2num($unitBuyPrice).",";
				$sql .= " ".price2num($tva_tx).",";
				$sql .= " ".price2num($charges).",";
				$sql .= " ".((int) $availability).",";
				$sql .= " ".($newdefaultvatcode ? "'".$this->db->escape($newdefaultvatcode)."'" : "null").",";
				$sql .= " ".((int) $newnpr).",";
				$sql .= $conf->entity.",";
				$sql .= ($delivery_time_days != '' ? ((int) $delivery_time_days) : 'null').",";
				$sql .= (empty($supplier_reputation) ? 'NULL' : "'".$this->db->escape($supplier_reputation)."'").",";
				$sql .= (empty($barcode) ? 'NULL' : "'".$this->db->escape($barcode)."'").",";
				$sql .= (empty($fk_barcode_type) ? 'NULL' : "'".$this->db->escape($fk_barcode_type)."'");
				if (getDolGlobalString('PRODUCT_USE_SUPPLIER_PACKAGING')) {
					$sql .= ", ".(empty($this->packaging) ? '1' : "'".$this->db->escape($this->packaging)."'");
				}
				$sql .= ")";

				$this->product_fourn_price_id = 0;

				$resql = $this->db->query($sql);
				if ($resql) {
					$this->product_fourn_price_id = $this->db->last_insert_id(MAIN_DB_PREFIX."product_fournisseur_price");
				} else {
					$this->error = $this->db->lasterror();
					$error++;
				}

				if (!$error) {
					if (!empty($options) && is_array($options)) {
						$productfournisseurprice = new ProductFournisseurPrice($this->db);
						$res = $productfournisseurprice->fetch($this->product_fourn_price_id);
						if ($res > 0) {
							foreach ($options as $key => $value) {
								$productfournisseurprice->array_options[$key] = $value;
							}
							$res = $productfournisseurprice->update($user);
							if ($res < 0) {
								$this->error = $productfournisseurprice->error;
								$this->errors = $productfournisseurprice->errors;
								$error++;
							}
						}
					}
				}

				if (!$error && !getDolGlobalString('PRODUCT_PRICE_SUPPLIER_NO_LOG')) {
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
					if ($result < 0) {
						$error++;
					}
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
	 *    @return   int 					    Return integer < 0 if KO, 0 if OK but not found, > 0 if OK
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
		$sql .= " p.ref as product_ref, p.tosell as status, p.tobuy as status_buy";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product as p";
		$sql .= " WHERE pfp.rowid = ".(int) $rowid;
		$sql .= " AND pfp.fk_product = p.rowid";

		dol_syslog(get_class($this)."::fetch_product_fournisseur_price", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$this->product_fourn_price_id = $rowid;
				$this->id = $obj->fk_product;

				$this->fk_product				= $obj->fk_product;
				$this->product_id				= $obj->fk_product;
				$this->product_ref				= $obj->product_ref;
				$this->status					= $obj->status;
				$this->status_buy				= $obj->status_buy;
				$this->fourn_id					= $obj->fk_soc;
				$this->fourn_ref				= $obj->ref_fourn; // deprecated
				$this->ref_supplier             = $obj->ref_fourn;
				$this->desc_supplier            = $obj->desc_fourn;
				$this->fourn_price = $obj->price;
				$this->fourn_charges            = $obj->charges; // when getDolGlobalString('PRODUCT_CHARGES') is set
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
				if (isModEnabled('barcode')) {
					$this->fourn_barcode = $obj->barcode; // deprecated
					$this->fourn_fk_barcode_type = $obj->fk_barcode_type; // deprecated
					$this->supplier_barcode = $obj->barcode;
					$this->supplier_fk_barcode_type = $obj->fk_barcode_type;
				}
				$this->packaging = $obj->packaging;

				if (isModEnabled('dynamicprices') && empty($ignore_expression) && !empty($this->fk_supplier_price_expression)) {
					require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
					$priceparser = new PriceParser($this->db);
					$price_result = $priceparser->parseProductSupplier($this);
					if ($price_result >= 0) {
						$this->fourn_price = $price_result;
						//recalculation of unitprice, as probably the price changed...
						if ($this->fourn_qty != 0) {
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
	 *    @param    int			$prodid	    Id of product
	 *    @param	string		$sortfield	Sort field
	 *    @param	string		$sortorder	Sort order
	 *    @param	int			$limit		Limit
	 *    @param	int			$offset		Offset
	 *    @param	int			$socid		Filter on a third party id
	 *    @return	ProductFournisseur[]|int<-1,-1>	Array of ProductFournisseur with new properties to define supplier price
	 *    @see find_min_price_product_fournisseur()
	 */
	public function list_product_fournisseur_price($prodid, $sortfield = '', $sortorder = '', $limit = 0, $offset = 0, $socid = 0)
	{
		// phpcs:enable
		global $conf;

		$sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id, p.ref as product_ref, p.tosell as status, p.tobuy as status_buy, ";
		$sql .= " pfp.rowid as product_fourn_pri_id, pfp.entity, pfp.ref_fourn, pfp.desc_fourn, pfp.fk_product as product_fourn_id, pfp.fk_supplier_price_expression,";
		$sql .= " pfp.price, pfp.quantity, pfp.unitprice, pfp.remise_percent, pfp.remise, pfp.tva_tx, pfp.fk_availability, pfp.charges, pfp.info_bits, pfp.delivery_time_days, pfp.supplier_reputation,";
		$sql .= " pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code, pfp.datec, pfp.tms,";
		$sql .= " pfp.barcode, pfp.fk_barcode_type, pfp.packaging, pfp.status as pfstatus";
		$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur_price as pfp, ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."societe as s";
		$sql .= " WHERE pfp.entity IN (".getEntity('productsupplierprice').")";
		$sql .= " AND pfp.fk_soc = s.rowid AND pfp.fk_product = p.rowid";
		$sql .= ($socid > 0 ? ' AND pfp.fk_soc = '.((int) $socid) : '');
		$sql .= " AND s.status = 1"; // only enabled company selected
		$sql .= " AND pfp.fk_product = ".((int) $prodid);
		if (empty($sortfield)) {
			$sql .= " ORDER BY s.nom, pfp.quantity, pfp.price";
		} else {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		$sql .= $this->db->plimit($limit, $offset);
		dol_syslog(get_class($this)."::list_product_fournisseur_price", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$retarray = array();

			while ($record = $this->db->fetch_array($resql)) {
				//define base attribute
				$prodfourn = new ProductFournisseur($this->db);

				$prodfourn->product_ref = $record["product_ref"];
				$prodfourn->product_fourn_price_id = $record["product_fourn_pri_id"];
				$prodfourn->status					= $record["status"];
				$prodfourn->status_buy				= $record["status_buy"];
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
				$prodfourn->fourn_charges = $record["charges"]; // when getDolGlobalString('PRODUCT_CHARGES') is set
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

				$prodfourn->packaging = $record["packaging"];
				$prodfourn->status = $record["pfstatus"];

				if (isModEnabled('barcode')) {
					$prodfourn->supplier_barcode = $record["barcode"];
					$prodfourn->supplier_fk_barcode_type = $record["fk_barcode_type"];
				}

				if (isModEnabled('dynamicprices') && !empty($prodfourn->fk_supplier_price_expression)) {
					require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
					$priceparser = new PriceParser($this->db);
					$price_result = $priceparser->parseProductSupplier($prodfourn);
					if ($price_result >= 0) {
						$prodfourn->fourn_price = $price_result;
						$prodfourn->fourn_unitprice = null; //force recalculation of unitprice, as probably the price changed...
					}
				}

				if (!isset($prodfourn->fourn_unitprice)) {
					if ($prodfourn->fourn_qty != 0) {
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
	 *  @param	float	$qty		Minimum quantity
	 *  @param	int		$socid		get min price for specific supplier
	 *  @return int					Return integer <0 if KO, 0=Not found of no product id provided, >0 if OK
	 *  @see list_product_fournisseur_price()
	 */
	public function find_min_price_product_fournisseur($prodid, $qty = 0, $socid = 0)
	{
		// phpcs:enable
		global $conf;

		if (empty($prodid)) {
			dol_syslog("Warning function find_min_price_product_fournisseur were called with prodid empty. May be a bug.", LOG_WARNING);
			return 0;
		}

		$this->product_fourn_price_id = 0;
		$this->product_fourn_id       = 0;
		$this->fourn_ref              = '';
		$this->fourn_price            = 0;
		$this->fourn_qty              = 0;
		$this->fourn_remise_percent   = 0;
		$this->fourn_remise           = 0;
		$this->fourn_unitprice        = 0;
		$this->fourn_id               = 0;
		$this->fourn_name             = '';
		$this->delivery_time_days     = 0;
		$this->id                     = 0;

		$this->fourn_multicurrency_price       = 0;
		$this->fourn_multicurrency_unitprice   = 0;
		$this->fourn_multicurrency_tx          = 0;
		$this->fourn_multicurrency_id          = '';
		$this->fourn_multicurrency_code        = '';

		$sql = "SELECT s.nom as supplier_name, s.rowid as fourn_id,";
		$sql .= " pfp.rowid as product_fourn_price_id, pfp.ref_fourn,";
		$sql .= " pfp.price, pfp.quantity, pfp.unitprice, pfp.tva_tx, pfp.charges,";
		$sql .= " pfp.remise, pfp.remise_percent, pfp.fk_supplier_price_expression, pfp.delivery_time_days";
		$sql .= " ,pfp.multicurrency_price, pfp.multicurrency_unitprice, pfp.multicurrency_tx, pfp.fk_multicurrency, pfp.multicurrency_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."product_fournisseur_price as pfp";
		$sql .= " WHERE s.entity IN (".getEntity('societe').")";
		$sql .= " AND pfp.entity IN (".getEntity('productsupplierprice').")";
		$sql .= " AND pfp.fk_product = ".((int) $prodid);
		$sql .= " AND pfp.fk_soc = s.rowid";
		$sql .= " AND s.status = 1"; // only enabled society
		if ($qty > 0) {
			$sql .= " AND pfp.quantity <= ".((float) $qty);
		}
		if ($socid > 0) {
			$sql .= ' AND pfp.fk_soc = '.((int) $socid);
		}

		dol_syslog(get_class($this)."::find_min_price_product_fournisseur", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$record_array = array();

			//Store each record to array for later search of min
			while ($record = $this->db->fetch_array($resql)) {
				$record_array[] = $record;
			}

			if (count($record_array) == 0) {
				$this->db->free($resql);
				return 0;
			} else {
				$min = -1;
				foreach ($record_array as $record) {
					$fourn_price = $record["price"];
					// calculate unit price for quantity 1
					$fourn_unitprice = $record["unitprice"];
					$fourn_unitprice_with_discount = $record["unitprice"] * (1 - $record["remise_percent"] / 100);

					if (isModEnabled('dynamicprices') && !empty($record["fk_supplier_price_expression"])) {
						$prod_supplier = new ProductFournisseur($this->db);
						$prod_supplier->product_fourn_price_id = $record["product_fourn_price_id"];
						$prod_supplier->id = $prodid;
						$prod_supplier->fourn_qty = $record["quantity"];
						$prod_supplier->fourn_tva_tx = $record["tva_tx"];
						$prod_supplier->fk_supplier_price_expression = $record["fk_supplier_price_expression"];

						require_once DOL_DOCUMENT_ROOT.'/product/dynamic_price/class/price_parser.class.php';
						$priceparser = new PriceParser($this->db);
						$price_result = $priceparser->parseProductSupplier($prod_supplier);
						if ($price_result >= 0) {
							$fourn_price = price2num($price_result, 'MU');
							if ($record["quantity"] != 0) {
								$fourn_unitprice = price2num((float) $fourn_price / $record["quantity"], 'MU');
							} else {
								$fourn_unitprice = $fourn_price;
							}
							$fourn_unitprice_with_discount = (float) $fourn_unitprice * (1 - $record["remise_percent"] / 100);
						}
					}
					if ($fourn_unitprice < $min || $min == -1) {
						$this->product_fourn_price_id   = $record["product_fourn_price_id"];
						$this->ref_supplier             = $record["ref_fourn"];
						$this->ref_fourn                = $record["ref_fourn"]; // deprecated
						$this->fourn_ref                = $record["ref_fourn"]; // deprecated
						$this->fourn_price              = $fourn_price;
						$this->fourn_qty                = $record["quantity"];
						$this->fourn_remise_percent     = $record["remise_percent"];
						$this->fourn_remise             = $record["remise"];
						$this->fourn_unitprice          = $fourn_unitprice;
						$this->fourn_unitprice_with_discount = $fourn_unitprice_with_discount;
						$this->fourn_charges            = $record["charges"]; // when getDolGlobalString('PRODUCT_CHARGES') is set
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
	 *  @return int                 	Return integer <0 if KO, >0 if OK
	 */
	public function setSupplierPriceExpression($expression_id)
	{
		global $conf;

		// Clean parameters
		$this->db->begin();
		$expression_id = $expression_id != 0 ? $expression_id : 'NULL';

		$sql = "UPDATE ".MAIN_DB_PREFIX."product_fournisseur_price";
		$sql .= " SET fk_supplier_price_expression = ".((int) $expression_id);
		$sql .= " WHERE rowid = ".((int) $this->product_fourn_price_id);

		dol_syslog(get_class($this)."::setSupplierPriceExpression", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
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
		global $conf, $langs;

		$out = '';
		$langs->load("suppliers");
		if (count($productFournList) > 0) {
			$out .= '<table class="nobordernopadding" width="100%">';
			$out .= '<tr><td class="liste_titre right">'.($showunitprice ? $langs->trans("Price").' '.$langs->trans("HT") : '').'</td>';
			$out .= '<td class="liste_titre right">'.($showunitprice ? $langs->trans("QtyMin") : '').'</td>';
			$out .= '<td class="liste_titre">'.$langs->trans("Supplier").'</td>';
			$out .= '<td class="liste_titre">'.$langs->trans("SupplierRef").'</td></tr>';
			foreach ($productFournList as $productFourn) {
				$out .= '<tr><td class="right">'.($showunitprice ? price($productFourn->fourn_unitprice * (1 - $productFourn->fourn_remise_percent / 100) - $productFourn->fourn_remise) : '').'</td>';
				$out .= '<td class="right">'.($showunitprice ? $productFourn->fourn_qty : '').'</td>';
				$out .= '<td>'.$productFourn->getSocNomUrl(1, 'supplier', $maxlen, $notooltip).'</td>';
				$out .= '<td>'.$productFourn->fourn_ref.'<td></tr>';
			}
			$out .= '</table>';
		} else {
			$out = ($showunitprice ? price($this->fourn_unitprice * (1 - $this->fourn_remise_percent / 100) + $this->fourn_remise, 0, $langs, 1, -1, -1, $conf->currency).' '.$langs->trans("HT").' &nbsp; <span class="opacitymedium">(</span>' : '');
			$out .= ($showsuptitle ? '<span class="opacitymedium">'.$langs->trans("Supplier").'</span>: ' : '').$this->getSocNomUrl(1, 'supplier', $maxlen, $notooltip).' / <span class="opacitymedium">'.$langs->trans("SupplierRef").'</span>: '.$this->ref_supplier;
			$out .= ($showunitprice ? '<span class="opacitymedium">)</span>' : '');
		}
		return $out;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'product_fournisseur_price'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 * Function used to replace a product id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceProduct(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'product_fournisseur_price'
		);

		return CommonObject::commonReplaceProduct($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 *    List supplier prices log of a supplier price
	 *
	 *    @param    int     $product_fourn_price_id Id of supplier price
	 *    @param	string  $sortfield	            Sort field
	 *    @param	string  $sortorder              Sort order
	 *    @param	int     $limit                  Limit
	 *    @param	int     $offset                 Offset
	 *    @return	array|int   Array of Log prices
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
		$sql .= " AND pfpl.fk_product_fournisseur = ".((int) $product_fourn_price_id);
		if (empty($sortfield)) {
			$sql .= " ORDER BY pfpl.datec";
		} else {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		$sql .= $this->db->plimit($limit, $offset);
		dol_syslog(get_class($this)."::list_product_fournisseur_price_log", LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$retarray = array();

			while ($obj = $this->db->fetch_object($resql)) {
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
				$out .= '<tr><td>'.dol_print_date($productFournLog['datec'], 'dayhour', 'tzuser').'</td>';
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
	 *  Return a link to the object card (with optionally the picto).
	 *  Used getNomUrl of ProductFournisseur if a specific supplier ref is loaded. Otherwise use Product->getNomUrl().
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int		$maxlength					Maxlength of ref
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param	int		$notooltip					No tooltip
	 *  @param  string  $morecss            		''=Add more css on link
	 *  @param	int		$add_label					0=Default, 1=Add label into string, >1=Add first chars into string
	 *  @param	string	$sep						' - '=Separator between ref and label if option 'add_label' is set
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $maxlength = 0, $save_lastsearch_value = -1, $notooltip = 0, $morecss = '', $add_label = 0, $sep = ' - ')
	{
		global $db, $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$label = '';

		$newref = $this->ref;
		if ($maxlength) {
			$newref = dol_trunc($newref, $maxlength, 'middle');
		}

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

		if ($this->type == Product::TYPE_PRODUCT || getDolGlobalString('STOCK_SUPPORTS_SERVICES')) {
			if (isModEnabled('productbatch')) {
				$langs->load("productbatch");
				$label .= "<br><b>".$langs->trans("ManageLotSerial").'</b>: '.$this->getLibStatut(0, 2);
			}
		}
		if (isModEnabled('barcode')) {
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
			if ($labelsize) {
				$label .= "<br>".$labelsize;
			}

			$labelsurfacevolume = "";
			if ($this->surface) {
				$labelsurfacevolume .= ($labelsurfacevolume ? " - " : "")."<b>".$langs->trans("Surface").'</b>: '.$this->surface.' '.measuringUnitString(0, 'surface', $this->surface_units);
			}
			if ($this->volume) {
				$labelsurfacevolume .= ($labelsurfacevolume ? " - " : "")."<b>".$langs->trans("Volume").'</b>: '.$this->volume.' '.measuringUnitString(0, 'volume', $this->volume_units);
			}
			if ($labelsurfacevolume) {
				$label .= "<br>".$labelsurfacevolume;
			}
		}

		if (isModEnabled('accounting') && $this->status) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
			$label .= '<br><b>'.$langs->trans('ProductAccountancySellCode').':</b> '.length_accountg($this->accountancy_code_sell);
			$label .= '<br><b>'.$langs->trans('ProductAccountancySellIntraCode').':</b> '.length_accountg($this->accountancy_code_sell_intra);
			$label .= '<br><b>'.$langs->trans('ProductAccountancySellExportCode').':</b> '.length_accountg($this->accountancy_code_sell_export);
		}
		if (isModEnabled('accounting') && $this->status_buy) {
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

		$url = DOL_URL_ROOT.'/product/price_suppliers.php?id='.((int) $this->id).'&action=create_price&token='.newToken().'&socid='.((int) $this->fourn_id).'&rowid='.((int) $this->product_fourn_price_id);

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
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
			$result .= $newref.($this->ref_supplier ? ' ('.$this->ref_supplier.')' : '');
		}
		$result .= $linkend;
		if ($withpicto != 2) {
			$result .= (($add_label && $this->label) ? $sep.dol_trunc($this->label, ($add_label > 1 ? $add_label : 0)) : '');
		}

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}
		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @param	int		$type			Type of product
	 *  @return	string 			       	Label of status
	 */
	public function getLibStatut($mode = 0, $type = 0)		// must be compatible with getLibStatut of inherited Product
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the status
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @param	int		$type			Type of product
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0, $type = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule@mymodule");
			$this->labelStatus[self::STATUS_OPEN] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_OPEN] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status4';
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
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
	 * @return int Return integer < 0 NOK > 0 OK
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
		$sql .= " ".((int) $this->product_fourn_price_id).",";
		$sql .= " ".$user->id.",";
		$sql .= " ".price2num($buyprice).",";
		$sql .= " ".price2num($qty);
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		} else {
			return 1;
		}
	}
}
