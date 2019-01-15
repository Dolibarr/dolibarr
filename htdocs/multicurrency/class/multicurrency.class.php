<?php
/* Copyright (C) 2007-2012  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
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
 * \file    htdocs/multicurrency/class/multicurrency.class.php
 * \ingroup multicurrency
 * \brief   This file is a CRUD class file (Create/Read/Update/Delete) for multicurrency
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/commonobjectline.class.php';

/**
 * Class Currency
 *
 * Put here description of your class
 * @see CommonObject
 */
class MultiCurrency extends CommonObject
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'multicurrency';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'multicurrency';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element_line="multicurrency_rate";

	/**
	 * @var CurrencyRate[] rates
	 */
	public $rates = array();

	/**
	 * @var mixed Sample property 1
	 */
	public $id;

	/**
	 * @var mixed Sample property 1
	 */
	public $code;

	/**
	 * @var mixed Sample property 2
	 */
	public $name;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var mixed Sample property 2
	 */
	public $date_create;

	/**
	 * @var mixed Sample property 2
	 */
	public $fk_user;

	/**
	 * @var mixed Sample property 2
	 */
	public $rate;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = &$db;

		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      User that creates
	 * @param  bool $trigger true=launch triggers after, false=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $trigger = true)
	{
		global $conf,$langs;

		dol_syslog('Currency::create', LOG_DEBUG);

		$error = 0;

		if (self::checkCodeAlreadyExists($this->code))
		{
			$error++;
			$this->errors[] = $langs->trans('multicurrency_code_already_added');
			return -1;
		}

		if (empty($this->entity) || $this->entity <= 0) $this->entity = $conf->entity;
		$now=date('Y-m-d H:i:s');

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql .= ' code,';
		$sql .= ' name,';
		$sql .= ' entity,';
		$sql .= ' date_create,';
		$sql .= ' fk_user';
		$sql .= ') VALUES (';
		$sql .= ' \'' . $this->db->escape($this->code) . '\',';
		$sql .= ' \'' . $this->db->escape($this->name) . '\',';
		$sql .= ' \'' . $this->entity . '\',';
		$sql .= ' \'' . $now . '\',';
		$sql .= ' \'' . $user->id . '\'';
		$sql .= ')';

		$this->db->begin();

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('Currency::create ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			$this->date_create = $now;
			$this->fk_user = $user->id;

			if ($trigger) {
				$result=$this->call_trigger('CURRENCY_CREATE', $user);
				if ($result < 0) $error++;
			}
		}

		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 * @param string $code code
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $code = null)
	{
		dol_syslog('Currency::fetch', LOG_DEBUG);

		global $conf;

		$sql = 'SELECT';
		$sql .= ' c.rowid, c.name, c.code, c.entity, c.date_create, c.fk_user';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' AS c';
		if (!empty($code)) $sql .= ' WHERE c.code = \''.$this->db->escape($code).'\' AND c.entity = '.$conf->entity;
		else $sql .= ' WHERE c.rowid = ' . $id;

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->name = $obj->name;
				$this->code = $obj->code;
				$this->entity = $obj->entity;
				$this->date_create = $obj->date_create;
				$this->fk_user = $obj->fk_user;

				$this->fetchAllCurrencyRate();
				$this->getRate();
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('Currency::fetch ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load all rates in object from the database
	 *
	 * @return int <0 if KO, >=0 if OK
	 */
	public function fetchAllCurrencyRate()
	{
		$sql = 'SELECT cr.rowid';
		$sql.= ' FROM ' . MAIN_DB_PREFIX . $this->table_element_line. ' as cr';
		$sql.= ' WHERE cr.fk_multicurrency = '.$this->id;
		$sql.= ' ORDER BY cr.date_sync DESC';

		$this->rates = array();

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			while ($obj = $this->db->fetch_object($resql)) {
				$rate = new CurrencyRate($this->db);
				$rate->fetch($obj->rowid);

				$this->rates[] = $rate;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('Currency::fetchAllCurrencyRate ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      User that modifies
	 * @param  bool $trigger true=launch triggers after, false=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update(User $user, $trigger = true)
	{
		$error = 0;

		dol_syslog('Currency::update', LOG_DEBUG);

		// Clean parameters
		$this->name = trim($this->name);
		$this->code = trim($this->code);

		// Check parameters
		if (empty($this->code)) {
			$error++;
			dol_syslog('Currency::update $this->code can not be empty', LOG_ERR);

			return -1;
		}

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= ' name=\''.$this->db->escape($this->name).'\'';
		$sql .= ' code=\''.$this->db->escape($this->code).'\'';
		$sql .= ' WHERE rowid=' . $this->id;

		dol_syslog(__METHOD__,LOG_DEBUG);
		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('Currency::update ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error && $trigger) {
			$result=$this->call_trigger('CURRENCY_MODIFY',$user);
			if ($result < 0) $error++;
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param bool $trigger true=launch triggers after, false=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($trigger = true)
	{
		global $user;

		dol_syslog('Currency::delete', LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if ($trigger) {
			$result=$this->call_trigger('CURRENCY_DELETE',$user);
			if ($result < 0) $error++;
		}

		if (!$error) {
			// Delete all rates before
			if (!$this->deleteRates()) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog('Currency::delete  ' . join(',', $this->errors), LOG_ERR);
			}

			$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . $this->table_element;
			$sql .= ' WHERE rowid=' . $this->id;

			dol_syslog(__METHOD__,LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog('Currency::delete ' . join(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete rates in database
	 *
	 * @return bool false if KO, true if OK
	 */
	public function deleteRates()
	{
		foreach ($this->rates as &$rate)
		{
			if ($rate->delete() <= 0)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Delete rate in database
	 *
	 * @param double	$rate	rate value
	 *
	 * @return int -1 if KO, 1 if OK
	 */
	public function addRate($rate)
	{
	 	$currencyRate = new CurrencyRate($this->db);
		$currencyRate->rate = $rate;

		if ($currencyRate->create($this->id) > 0)
		{
			$this->rate = $currencyRate;
			return 1;
		}
		else
		{
			$this->rate = null;
			return -1;
		}
	 }

	/**
	 * Try get label of code in llx_currency then add rate.
	 *
	 * @param	string	$code	currency code
	 * @param	double	$rate	new rate
	 *
	 * @return int -1 if KO, 1 if OK, 2 if label found and OK
	 */
	function addRateFromDolibarr($code, $rate)
	{
	 	global $db, $user;

		$currency = new MultiCurrency($db);
		$currency->code = $code;
		$currency->name = $code;

	 	$sql = 'SELECT label FROM '.MAIN_DB_PREFIX.'c_currencies WHERE code_iso = \''.$db->escape($code).'\'';

	 	dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql && ($line = $db->fetch_object($resql)))
		{
			$currency->name = $line->label;
		}

		if ($currency->create($user) > 0)
		{
			$currency->addRate($rate);

			if (!empty($line)) return 2;
			else return 1;
		}

		return -1;
	}

	 /**
	 * Add new entry into llx_multicurrency_rate to historise
	 *
	 * @param double	$rate	rate value
	  *
	 * @return int <0 if KO, >0 if OK
	 */
	public function updateRate($rate)
	{
	 	return $this->addRate($rate);
	 }

	/**
	 * Fetch CurrencyRate object in $this->rate
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function getRate()
	{
	 	$sql = 'SELECT cr.rowid';
		$sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element_line.' as cr';
		$sql.= ' WHERE cr.fk_multicurrency = '.$this->id;
		$sql.= ' AND cr.date_sync = (SELECT MAX(cr2.date_sync) FROM '.MAIN_DB_PREFIX.$this->table_element_line.' AS cr2 WHERE cr2.fk_multicurrency = '.$this->id.')';

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql && ($obj = $this->db->fetch_object($resql))) {
			$this->rate = new CurrencyRate($this->db);
			return $this->rate->fetch($obj->rowid);
		}
	 }

	 /**
	 * Get id of currency from code
	 *
	 * @param DoliDB	$db		object db
	 * @param string	$code	code value search
	 *
	 * @return 0 if not found, >0 if OK
	 */
	public static function getIdFromCode(&$db, $code)
	{
	 	global $conf;

	 	$sql = 'SELECT rowid FROM '.MAIN_DB_PREFIX.'multicurrency WHERE code = \''.$db->escape($code).'\' AND entity = '.$conf->entity;

	 	dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql && $obj = $db->fetch_object($resql)) return $obj->rowid;
		else return 0;
	 }

	 /**
	 * Get id and rate of currency from code
	 *
	 * @param DoliDB	$db		object db
	 * @param string	$code	code value search
	 * @param date		$date_document	date from document (propal, order, invoice, ...)
	 *
	 * @return 	array	[0] => id currency
	 *					[1] => rate
	 */
	public static function getIdAndTxFromCode(&$db, $code, $date_document='')
	{
		global $conf;

	 	$sql1 = 'SELECT m.rowid, mc.rate FROM '.MAIN_DB_PREFIX.'multicurrency m';
		$sql1.= ' LEFT JOIN '.MAIN_DB_PREFIX.'multicurrency_rate mc ON (m.rowid = mc.fk_multicurrency)';
		$sql1.= ' WHERE m.code = \''.$db->escape($code).'\'';
		$sql1.= " AND m.entity IN (".getEntity('multicurrency').")";
		$sql2= '';
		if (!empty($conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE) && !empty($date_document)) $sql2.= ' AND DATE_FORMAT(mc.date_sync, "%Y-%m-%d") = "'.date('Y-m-d', $date_document).'"';
		$sql3.= ' ORDER BY mc.date_sync DESC LIMIT 1';

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $db->query($sql1.$sql2.$sql3);

		if ($resql && $obj = $db->fetch_object($resql)) return array($obj->rowid, $obj->rate);
		else
		{
			if (!empty($conf->global->MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE))
			{
				$resql = $db->query($sql1.$sql3);
				if ($resql && $obj = $db->fetch_object($resql)) return array($obj->rowid, $obj->rate);
			}

			return array(0, 1);
		}
	 }

	 /**
	  * Get the conversion of amount with invoice rate
	  *
	  * @param	int		$fk_facture		id of facture
	  * @param	double	$amount			amount to convert
	  * @param	string	$way			dolibarr mean the amount is in dolibarr currency
	  * @param	string	$table			facture or facture_fourn
	  * @return	double					amount converted
	  */
	  public static function getAmountConversionFromInvoiceRate($fk_facture, $amount, $way='dolibarr', $table='facture')
	  {
		 global $db;

		 $multicurrency_tx = self::getInvoiceRate($fk_facture, $table);

		 if ($multicurrency_tx)
		 {
		 	if ($way == 'dolibarr') return $amount * $multicurrency_tx;
			else return $amount / $multicurrency_tx;
		 }
		 else return $amount;
	  }

	/**
	 *  Get current invoite rate
	 *
	 *  @param	int 	$fk_facture 	id of facture
	 *  @param 	string 	$table 			facture or facture_fourn
     *  @return bool
	 */
	public static function getInvoiceRate($fk_facture, $table='facture')
	{
		global $db;

		$sql = 'SELECT multicurrency_tx FROM '.MAIN_DB_PREFIX.$table.' WHERE rowid = '.$fk_facture;

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql && ($line = $db->fetch_object($resql)))
		{
			return $line->multicurrency_tx;
		}

		return false;
	}

	/**
	 * With free account we can't set source then recalcul all rates to force another source
	 *
	 * @param   stdClass	$TRate	Object containing all currencies rates
	 * @return	-1 if KO, 0 if nothing, 1 if OK
	 */
	public static function recalculRates(&$TRate)
	{
		global $conf;

		if (!empty($conf->global->MULTICURRENCY_ALTERNATE_SOURCE))
		{
			$alternate_source = 'USD'.$conf->global->MULTICURRENCY_ALTERNATE_SOURCE;
			if (!empty($TRate->{$alternate_source}))
			{
				$coef = $TRate->USDUSD / $TRate->{$alternate_source};
				foreach ($TRate as $attr => &$rate)
				{
					$rate *= $coef;
				}

				return 1;
			}

			return -1; // Alternate souce not found
		}

		return 0; // Nothing to do
	}

	/**
	 *  Sync rates from api
	 *
	 *  @param 	array 	$response 	array of reponse from api to sync dolibarr rates
     * @return void
	 */
	public static function syncRates($response)
	{
		global $db,$conf;

		$ch = curl_init('http://apilayer.net/api/live?access_key='.$key.'');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = curl_exec($ch);
                curl_close($ch);
                $response = json_decode($response);

                if ($response->success)
                {

			$TRate = $response->quotes;
			$timestamp = $response->timestamp;

			if (self::recalculRates($TRate) >= 0)
			{
				foreach ($TRate as $currency_code => $rate)
				{
					$code = substr($currency_code, 3, 3);
					$obj = new MultiCurrency($db);
					if ($obj->fetch(null, $code) > 0)
					{
						$obj->updateRate($rate);
					}
					else
					{
						self::addRateFromDolibarr($code, $rate);
					}
				}
			}
		}
		else
		{
			setEventMessages($langs->trans('multicurrency_syncronize_error', $response->error->info), null, 'errors');
		}
	}

	/**
	 * Check in database if the current code already exists
	 *
	 * @param	string	$code 	current code to search
	 * @return	boolean         True if exists, false if not exists
	 */
	public static function checkCodeAlreadyExists($code)
	{
	 	global $db;

	 	$currency = new MultiCurrency($db);
		if ($currency->fetch('', $code) > 0) return true;
		else return false;
	}
}

/**
 * Class CurrencyRate
 */
class CurrencyRate extends CommonObjectLine
{
	/**
	 * @var string Id to identify managed objects
	 */
	public $element = 'multicurrency_rate';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'multicurrency_rate';

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var double Rate
	 */
	public $rate;

	/**
	 * @var date Date synchronisation
	 */
	public $date_sync;

	/**
	 * @var int Id of currency
	 */
	public $fk_multicurrency;

	/**
	 * @var int Id of entity
	 */
	public $entity;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = &$db;

		return 1;
	}

	/**
	 * Create object into database
	 *
	 * @param  int	$fk_multicurrency	Id of currency
	 * @param  bool	$trigger			true=launch triggers after, false=disable triggers
	 *
	 * @return int <0 if KO, Id of created object if OK
	 */
	public function create($fk_multicurrency, $trigger = true)
	{
		global $conf, $user;

		dol_syslog('CurrencyRate::create', LOG_DEBUG);

		$error = 0;
		$this->rate = price2num($this->rate);
		if (empty($this->entity) || $this->entity <= 0) $this->entity = $conf->entity;
		$now=date('Y-m-d H:i:s');

		// Insert request
		$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . $this->table_element . '(';
		$sql .= ' rate,';
		$sql .= ' date_sync,';
		$sql .= ' fk_multicurrency,';
		$sql .= ' entity';
		$sql .= ') VALUES (';
		$sql .= ' '.$this->rate.',';
		$sql .= ' \'' . $now . '\',';
		$sql .= ' \'' . $fk_multicurrency . '\',';
		$sql .= ' \'' . $this->entity . '\'';
		$sql .= ')';

		$this->db->begin();

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('CurrencyRate::create ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
			$this->fk_multicurrency = $fk_multicurrency;
			$this->date_sync = $now;

			if ($trigger) {
				$result=$this->call_trigger('CURRENCYRATE_CREATE', $user);
				if ($result < 0) $error++;
			}
		}

		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  Id object
	 *
	 * @return int <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		dol_syslog('CurrencyRate::fetch', LOG_DEBUG);

		$sql = 'SELECT cr.rowid, cr.rate, cr.date_sync, cr.fk_multicurrency, cr.entity';
		$sql .= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' AS cr';
		$sql .= ' WHERE cr.rowid = ' . $id;

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$numrows = $this->db->num_rows($resql);
			if ($numrows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->rate = $obj->rate;
				$this->date_sync = $obj->date_sync;
				$this->fk_multicurrency = $obj->fk_multicurrency;
				$this->entity = $obj->entity;
			}
			$this->db->free($resql);

			if ($numrows) {
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('CurrencyRate::fetch ' . join(',', $this->errors), LOG_ERR);

			return - 1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  bool $trigger true=launch triggers after, false=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function update($trigger = true)
	{
		global $user;

		$error = 0;

		dol_syslog('CurrencyRate::update', LOG_DEBUG);

		$this->rate = price2num($this->rate);

		// Update request
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . $this->table_element . ' SET';
		$sql .= ' rate='.$this->rate;
		$sql .= ' WHERE rowid=' . $this->id;

		$this->db->begin();

		dol_syslog(__METHOD__,LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error ++;
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog('CurrencyRate::update ' . join(',', $this->errors), LOG_ERR);
		}

		if (!$error && $trigger) {
			$result=$this->call_trigger('CURRENCYRATE_MODIFY',$user);
			if ($result < 0) $error++;
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}

	/**
	 * Delete object in database
	 *
	 * @param bool $trigger true=launch triggers after, false=disable triggers
	 *
	 * @return int <0 if KO, >0 if OK
	 */
	public function delete($trigger = true)
	{
		global $user;

		dol_syslog('CurrencyRate::delete', LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if ($trigger) {
			$result=$this->call_trigger('CURRENCYRATE_DELETE',$user);
			if ($result < 0) $error++;
		}

		if (!$error) {
			$sql = 'DELETE FROM '.MAIN_DB_PREFIX.$this->table_element;
			$sql .= ' WHERE rowid='.$this->id;

			dol_syslog(__METHOD__,LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error ++;
				$this->errors[] = 'Error ' . $this->db->lasterror();
				dol_syslog('CurrencyRate::delete ' . join(',', $this->errors), LOG_ERR);
			}
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();

			return - 1 * $error;
		} else {
			$this->db->commit();

			return 1;
		}
	}
}
