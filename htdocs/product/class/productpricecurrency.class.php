<?php
/* Copyright (C) 2026		Quentin Vial-Gouteyron	<quentin.vial-gouteyron@atm-consulting.fr>
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
 *	\file       htdocs/product/class/productpricecurrency.class.php
 *	\ingroup    product
 *	\brief      File of class to manage fixed sell prices per currency for a product
 */

require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/multicurrency.class.php';


/**
 *	Class to manage fixed sell prices per currency (table product_price_currency).
 *
 *	A product can hold one fixed sell price per (price level, currency). This price is
 *	entered manually and is NOT derived from the exchange rate. On sale documents using a
 *	foreign currency, this price takes precedence over the company-currency catalog price.
 */
class ProductPriceCurrency
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error message.
	 */
	public $error = '';

	/**
	 * @var string[] Array of error messages.
	 */
	public $errors = array();

	/**
	 * @var int ID of the last fetched/written row.
	 */
	public $id;

	/**
	 * @var float Fixed price without tax, in the currency.
	 */
	public $price;

	/**
	 * @var float Fixed price including tax, in the currency.
	 */
	public $price_ttc;

	/**
	 * @var string Price base type ('HT' or 'TTC').
	 */
	public $price_base_type;

	/**
	 * @var float Exchange rate stored at input time (informative only).
	 */
	public $multicurrency_tx;

	/**
	 * @var int Customer id for a per-customer price, 0 for the catalog price.
	 */
	public $fk_soc;

	/**
	 *	Constructor.
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *	Create or update the fixed sell price of a product for a given currency and price level.
	 *
	 *	Computes the missing HT/TTC counterpart from the given VAT rate. Writes in the current entity.
	 *	Does NOT derive the company-currency price from the currency price (price stays independent
	 *	from the exchange rate).
	 *
	 *	@param	int		$fk_product				Product id
	 *	@param	string	$multicurrency_code		Currency code (e.g. 'USD')
	 *	@param	float	$price					Price value, in the currency, expressed in $price_base_type
	 *	@param	string	$price_base_type		'HT' or 'TTC'
	 *	@param	float	$vat_tx					VAT rate used to compute the HT/TTC counterpart
	 *	@param	User	$user					User doing the change
	 *	@param	int		$level					Price level (>=1)
	 *	@param	float	$multicurrency_tx		Exchange rate at input time (informative only)
	 *	@param	int		$socid					Customer id for a per-customer price, 0 for the catalog price
	 *	@param	int		$notrigger				1 to disable triggers
	 *	@return	int								Row id (>0) if OK, -1 if KO
	 */
	public function setPriceCurrency(int $fk_product, string $multicurrency_code, float $price, string $price_base_type, float $vat_tx, User $user, int $level = 1, float $multicurrency_tx = 1.0, int $socid = 0, int $notrigger = 0): int
	{
		global $conf;

		// Error-first sanitization
		if ($fk_product <= 0 || $multicurrency_code === '') {
			$this->error = 'BadParameterForSetPriceCurrency';
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}
		if ($price_base_type !== 'TTC') {
			$price_base_type = 'HT';
		}
		if ($level <= 0) {
			$level = 1;
		}
		if ($socid < 0) {
			$socid = 0;
		}

		// Compute HT/TTC pair
		if ($price_base_type === 'TTC') {
			$price_ttc = (float) price2num($price, 'MU');
			$price_ht  = (float) price2num($price_ttc / (1 + $vat_tx / 100), 'MU');
		} else {
			$price_ht  = (float) price2num($price, 'MU');
			$price_ttc = (float) price2num($price_ht * (1 + $vat_tx / 100), 'MU');
		}

		$fk_multicurrency = MultiCurrency::getIdFromCode($this->db, $multicurrency_code);

		$this->db->begin();

		// Existence is tested on the CURRENT entity only. With Multicompany product-price sharing,
		// fetchByKey() reads getEntity('productprice') (possibly several entities); using it here would let
		// an UPDATE hit a row owned by another entity, while the INSERT always writes $conf->entity. (issue #32379)
		$existing_rowid = 0;
		$sqlexist = "SELECT rowid FROM ".$this->db->prefix()."product_price_currency";
		$sqlexist .= " WHERE fk_product = ".((int) $fk_product);
		$sqlexist .= " AND fk_soc = ".((int) $socid);
		$sqlexist .= " AND price_level = ".((int) $level);
		$sqlexist .= " AND multicurrency_code = '".$this->db->escape($multicurrency_code)."'";
		$sqlexist .= " AND entity = ".((int) $conf->entity);
		$resexist = $this->db->query($sqlexist);
		if (!$resexist) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
		if ($objexist = $this->db->fetch_object($resexist)) {
			$existing_rowid = (int) $objexist->rowid;
		}
		$this->db->free($resexist);

		if ($existing_rowid > 0) {
			$this->id = $existing_rowid;
			$sql = "UPDATE ".$this->db->prefix()."product_price_currency SET";
			$sql .= " price = ".((float) $price_ht);
			$sql .= ", price_ttc = ".((float) $price_ttc);
			$sql .= ", price_base_type = '".$this->db->escape($price_base_type)."'";
			$sql .= ", multicurrency_tx = ".((float) $multicurrency_tx);
			$sql .= ", fk_multicurrency = ".($fk_multicurrency > 0 ? ((int) $fk_multicurrency) : "null");
			$sql .= ", date_price = '".$this->db->idate(dol_now())."'";
			$sql .= ", fk_user_author = ".((int) $user->id);
			$sql .= " WHERE rowid = ".((int) $this->id);
		} else {
			$sql = "INSERT INTO ".$this->db->prefix()."product_price_currency";
			$sql .= " (entity, fk_product, fk_soc, price_level, fk_multicurrency, multicurrency_code, multicurrency_tx, price, price_ttc, price_base_type, date_price, fk_user_author)";
			$sql .= " VALUES (".((int) $conf->entity);
			$sql .= ", ".((int) $fk_product);
			$sql .= ", ".((int) $socid);
			$sql .= ", ".((int) $level);
			$sql .= ", ".($fk_multicurrency > 0 ? ((int) $fk_multicurrency) : "null");
			$sql .= ", '".$this->db->escape($multicurrency_code)."'";
			$sql .= ", ".((float) $multicurrency_tx);
			$sql .= ", ".((float) $price_ht);
			$sql .= ", ".((float) $price_ttc);
			$sql .= ", '".$this->db->escape($price_base_type)."'";
			$sql .= ", '".$this->db->idate(dol_now())."'";
			$sql .= ", ".((int) $user->id).")";
		}

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}

		if ($existing_rowid == 0) {
			$this->id = (int) $this->db->last_insert_id($this->db->prefix()."product_price_currency");
		}
		$this->price = $price_ht;
		$this->price_ttc = $price_ttc;
		$this->price_base_type = $price_base_type;
		$this->multicurrency_tx = $multicurrency_tx;
		$this->fk_soc = $socid;

		$this->db->commit();

		return $this->id > 0 ? $this->id : 1;
	}

	/**
	 *	Load a fixed currency price by business key into the current object.
	 *
	 *	@param	int		$fk_product				Product id
	 *	@param	int		$level					Price level
	 *	@param	string	$multicurrency_code		Currency code
	 *	@param	int		$socid					Customer id for a per-customer price, 0 for the catalog price
	 *	@return	int								1 if found, 0 if not found, -1 if SQL error
	 */
	public function fetchByKey(int $fk_product, int $level, string $multicurrency_code, int $socid = 0): int
	{
		global $conf;

		$sql = "SELECT rowid, fk_soc, price, price_ttc, price_base_type, multicurrency_tx";
		$sql .= " FROM ".$this->db->prefix()."product_price_currency";
		$sql .= " WHERE fk_product = ".((int) $fk_product);
		$sql .= " AND fk_soc = ".((int) $socid);
		$sql .= " AND price_level = ".((int) $level);
		$sql .= " AND multicurrency_code = '".$this->db->escape($multicurrency_code)."'";
		$sql .= " AND entity IN (".getEntity('productprice').")";
		// On a product shared across entities, prefer the current entity's own price, then the most recent row, so the result is deterministic (issue #32379)
		$sql .= " ORDER BY CASE WHEN entity = ".((int) $conf->entity)." THEN 0 ELSE 1 END, rowid DESC";
		$sql .= " LIMIT 1";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			$this->db->free($resql);
			return 0;
		}

		$this->id = (int) $obj->rowid;
		$this->fk_soc = (int) $obj->fk_soc;
		$this->price = (float) $obj->price;
		$this->price_ttc = (float) $obj->price_ttc;
		$this->price_base_type = $obj->price_base_type;
		$this->multicurrency_tx = (float) $obj->multicurrency_tx;
		$this->db->free($resql);

		return 1;
	}

	/**
	 *	Load fixed currency prices of a product, grouped by price level then currency code.
	 *
	 *	By default only catalog prices (fk_soc = 0) are returned, which is what Product::fetch() preloads.
	 *	Pass a $socid to read that customer's own fixed prices instead.
	 *
	 *	@param	int		$fk_product		Product id
	 *	@param	int		$socid			Customer id for per-customer prices, 0 for the catalog prices
	 *	@return	array<int,array<string,array{price:float,price_ttc:float,price_base_type:string,multicurrency_tx:float}>>	Indexed by [price_level][currency_code]
	 */
	public function fetchAllForProduct(int $fk_product, int $socid = 0): array
	{
		$result = array();
		if ($fk_product <= 0) {
			return $result;
		}

		$sql = "SELECT price_level, multicurrency_code, price, price_ttc, price_base_type, multicurrency_tx";
		$sql .= " FROM ".$this->db->prefix()."product_price_currency";
		$sql .= " WHERE fk_product = ".((int) $fk_product);
		$sql .= " AND fk_soc = ".((int) $socid);
		$sql .= " AND entity IN (".getEntity('productprice').")";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return $result;
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$result[(int) $obj->price_level][$obj->multicurrency_code] = array(
				'price' => (float) $obj->price,
				'price_ttc' => (float) $obj->price_ttc,
				'price_base_type' => (string) $obj->price_base_type,
				'multicurrency_tx' => (float) $obj->multicurrency_tx,
			);
		}
		$this->db->free($resql);

		return $result;
	}

	/**
	 *	Delete a fixed currency price by business key (in the current entity).
	 *
	 *	@param	int		$fk_product				Product id
	 *	@param	int		$level					Price level
	 *	@param	string	$multicurrency_code		Currency code
	 *	@param	User	$user					User doing the change
	 *	@param	int		$socid					Customer id for a per-customer price, 0 for the catalog price
	 *	@return	int								>0 if OK, -1 if KO
	 */
	public function deleteCurrencyPrice(int $fk_product, int $level, string $multicurrency_code, User $user, int $socid = 0): int
	{
		global $conf;

		if ($fk_product <= 0 || $multicurrency_code === '') {
			$this->error = 'BadParameterForDeleteCurrencyPrice';
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}

		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."product_price_currency";
		$sql .= " WHERE fk_product = ".((int) $fk_product);
		$sql .= " AND fk_soc = ".((int) $socid);
		$sql .= " AND price_level = ".((int) $level);
		$sql .= " AND multicurrency_code = '".$this->db->escape($multicurrency_code)."'";
		$sql .= " AND entity = ".((int) $conf->entity);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
		$nbdeleted = $this->db->affected_rows($resql);

		$this->db->commit();

		// 1 if a row was actually removed, 0 if there was nothing to delete in this entity
		return $nbdeleted > 0 ? 1 : 0;
	}

	/**
	 *	Initialize the missing catalog (fk_soc = 0) per-currency sell prices in bulk.
	 *
	 *	For each priced product/level in the current entity and each enabled currency other than the
	 *	company currency, a fixed currency price equal to the company price converted at the current rate
	 *	is created WHEN NONE EXISTS YET. Existing per-currency prices are never modified (non-destructive),
	 *	so the tool is safe to run several times.
	 *
	 *	@param	User		$user		User running the initialization
	 *	@param	string[]	$codes		Currency codes to process; empty means all enabled currencies
	 *	@param	int			$level		Price level to process; 0 means all levels
	 *	@param	bool		$dryrun		When true, only count the prices that WOULD be created (no write)
	 *	@return	int						Number of created (or, in dry-run, would-be-created) prices, -1 on error
	 */
	public function initCatalogCurrencyPrices(User $user, array $codes = array(), int $level = 0, bool $dryrun = false): int
	{
		global $conf;

		// Resolve the list of currency codes to process (the company currency never needs a fixed price)
		if (empty($codes)) {
			foreach (MultiCurrency::getCurrencyList($this->db) as $currency) {
				if ($currency['code'] != $conf->currency) {
					$codes[] = $currency['code'];
				}
			}
		}
		if (empty($codes)) {
			return 0;
		}

		// Prefetch the current rate of each currency once
		$rates = array();
		foreach ($codes as $code) {
			$tmp = MultiCurrency::getIdAndTxFromCode($this->db, $code);
			$rates[$code] = (is_array($tmp) && !empty($tmp[1])) ? (float) $tmp[1] : 1.0;
		}

		// Latest company price per (product, level) in the current entity.
		// Source rows are read on $conf->entity only (not getEntity('productprice')) so init reads and
		// writes the same entity: setPriceCurrency() always inserts on $conf->entity. (issue #32379)
		$sql = "SELECT pr.fk_product, pr.price_level, pr.price, pr.price_ttc, pr.price_base_type, pr.tva_tx";
		$sql .= " FROM ".$this->db->prefix()."product_price as pr";
		$sql .= " WHERE pr.entity = ".((int) $conf->entity);
		if ($level > 0) {
			$sql .= " AND pr.price_level = ".((int) $level);
		}
		$sql .= " AND pr.date_price = (SELECT MAX(pr2.date_price) FROM ".$this->db->prefix()."product_price as pr2";
		$sql .= " WHERE pr2.fk_product = pr.fk_product AND pr2.price_level = pr.price_level AND pr2.entity = pr.entity)";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
			return -1;
		}

		$rows = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$rows[] = $obj;
		}
		$this->db->free($resql);

		// Wrap the whole batch so a mid-run failure leaves no partial state (issue #32379)
		if (!$dryrun) {
			$this->db->begin();
		}

		$count = 0;
		foreach ($rows as $obj) {
			$fk_product = (int) $obj->fk_product;
			$plevel = (int) $obj->price_level;
			$base = ($obj->price_base_type == 'TTC') ? 'TTC' : 'HT';
			$companyprice = ($base == 'TTC') ? (float) $obj->price_ttc : (float) $obj->price;
			$vat = (float) $obj->tva_tx;
			if ($companyprice == 0.0) {
				continue;
			}
			foreach ($codes as $code) {
				// Non-destructive: only create a currency price when none exists yet for this key
				// in the CURRENT entity (writes always target $conf->entity).
				$existsql = "SELECT rowid FROM ".$this->db->prefix()."product_price_currency";
				$existsql .= " WHERE fk_product = ".((int) $fk_product);
				$existsql .= " AND fk_soc = 0";
				$existsql .= " AND price_level = ".((int) $plevel);
				$existsql .= " AND multicurrency_code = '".$this->db->escape($code)."'";
				$existsql .= " AND entity = ".((int) $conf->entity);
				$resexist = $this->db->query($existsql);
				if (!$resexist) {
					$this->error = $this->db->lasterror();
					dol_syslog(__METHOD__.' '.$this->error, LOG_ERR);
					if (!$dryrun) {
						$this->db->rollback();
					}
					return -1;
				}
				$already = ($this->db->num_rows($resexist) > 0);
				$this->db->free($resexist);
				if ($already) {
					continue;
				}
				if ($dryrun) {
					$count++;
					continue;
				}
				$rate = $rates[$code];
				$res = $this->setPriceCurrency($fk_product, $code, $companyprice * $rate, $base, $vat, $user, $plevel, $rate, 0);
				if ($res <= 0) {
					dol_syslog(__METHOD__.' setPriceCurrency failed for product '.$fk_product.' '.$code.': '.$this->error, LOG_ERR);
					$this->db->rollback();
					return -1;
				}
				$count++;
			}
		}

		if (!$dryrun) {
			$this->db->commit();
		}

		dol_syslog(__METHOD__.' '.($dryrun ? 'would create ' : 'created ').$count.' currency prices', LOG_INFO);

		return $count;
	}
}
