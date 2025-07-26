<?php
/* Copyright (C) 2007-2020  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2015       Florian Henry       <florian.henry@open-concept.pro>
 * Copyright (C) 2015       Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2016       Pierre-Henry Favre  <phf@atm-consulting.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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
 * \file    htdocs/multicurrency/class/multicurrency.class.php
 * \ingroup multicurrency
 * \brief   This file is a CRUD class file (Create/Read/Update/Delete) for multicurrency
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/multicurrency/class/currencyrate.class.php';

/**
 * Class Currency
 *
 * Put here description of your class
 * @see CommonObject
 */
class MultiCurrency extends CommonObject
{
	/**
	 * @var string 			Id to identify managed objects
	 */
	public $element = 'multicurrency';

	/**
	 * @var string 			Name of table without prefix where object is stored
	 */
	public $table_element = 'multicurrency';

	/**
	 * @var string 			Name of table without prefix where object is stored
	 */
	public $table_element_line = "multicurrency_rate";

	/**
	 * @var CurrencyRate[]	Currency rates
	 */
	public $rates = array();

	/**
	 * @var int 			The environment ID when using a multicompany module
	 */
	public $id;

	/**
	 * @var string 			The currency code
	 */
	public $code;

	/**
	 * @var string 			The currency name
	 */
	public $name;

	/**
	 * @var int 			The environment ID when using a multicompany module
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
	 * @var ?CurrencyRate 	The currency rate
	 */
	public $rate;

	/**
	 * @var string			URL endpoint for update of currency
	 */
	public $urlendpoint;


	const MULTICURRENCY_APP_ENDPOINT_DEFAULT = 'https://api.currencylayer.com/live?access_key=__MULTICURRENCY_APP_KEY__&source=__MULTICURRENCY_APP_SOURCE__';


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;

		$key = getDolGlobalString("MULTICURRENCY_APP_KEY");
		$source = getDolGlobalString('MULTICURRENCY_APP_SOURCE', 'USD');
		$urlendpoint = getDolGlobalString("MULTICURRENCY_APP_ENDPOINT", self::MULTICURRENCY_APP_ENDPOINT_DEFAULT);

		$this->urlendpoint = str_replace(array('__MULTICURRENCY_APP_KEY__', '__MULTICURRENCY_APP_SOURCE__'), array($key, $source), $urlendpoint);
	}

	/**
	 * Create object into database
	 *
	 * @param  User $user      	User that creates
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		dol_syslog('MultiCurrency::create', LOG_DEBUG);

		$error = 0;

		if (self::checkCodeAlreadyExists($this->code)) {
			$error++;
			$this->errors[] = $langs->trans('multicurrency_code_already_added');
			return -1;
		}

		if (empty($this->entity) || $this->entity <= 0) {
			$this->entity = $conf->entity;
		}
		$now = dol_now();

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		$sql .= ' code,';
		$sql .= ' name,';
		$sql .= ' entity,';
		$sql .= ' date_create,';
		$sql .= ' fk_user';
		$sql .= ') VALUES (';
		$sql .= " '".$this->db->escape($this->code)."',";
		$sql .= " '".$this->db->escape($this->name)."',";
		$sql .= " ".((int) $this->entity).",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " ".((int) $user->id);
		$sql .= ')';

		$this->db->begin();

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('MultiCurrency::create '.implode(',', $this->errors), LOG_ERR);
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$this->date_create = $now;
			$this->fk_user = $user->id;

			if (empty($notrigger)) {
				$result = $this->call_trigger('CURRENCY_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
			}
		}

		if ($error) {
			$this->db->rollback();

			return -1 * $error;
		} else {
			$this->db->commit();

			return $this->id;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int    $id  		Id object
	 * @param ?string $code 		code
	 * @return int 				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $code = null)
	{
		dol_syslog('MultiCurrency::fetch', LOG_DEBUG);

		$sql = "SELECT c.rowid, c.name, c.code, c.entity, c.date_create, c.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." AS c";
		if (!empty($code)) {
			$sql .= " WHERE c.code = '".$this->db->escape($code)."'";
			$sql .= " AND c.entity IN (".getEntity($this->element).")";
		} else {
			$sql .= ' WHERE c.rowid = '.((int) $id);
		}

		dol_syslog(__METHOD__, LOG_DEBUG);
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
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('MultiCurrency::fetch '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Load all rates in object from the database
	 *
	 * @return int Return integer <0 if KO, >=0 if OK
	 */
	public function fetchAllCurrencyRate()
	{
		$sql = "SELECT cr.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element_line." as cr";
		$sql .= " WHERE cr.entity IN (".getEntity($this->element).")";
		$sql .= " AND cr.fk_multicurrency = ".((int) $this->id);
		$sql .= " ORDER BY cr.date_sync DESC";

		$this->rates = array();

		dol_syslog(__METHOD__, LOG_DEBUG);
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
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('MultiCurrency::fetchAllCurrencyRate '.implode(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param  User $user      	User that modifies
	 * @param  int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return int 				Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog('MultiCurrency::update', LOG_DEBUG);

		// Clean parameters
		$this->name = trim($this->name);
		$this->code = trim($this->code);

		// Check parameters
		if (empty($this->code)) {
			$error++;
			dol_syslog('MultiCurrency::update $this->code can not be empty', LOG_ERR);

			return -1;
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " name = '".$this->db->escape($this->name)."',";
		$sql .= " code = '".$this->db->escape($this->code)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = 'Error '.$this->db->lasterror();
			dol_syslog('MultiCurrency::update '.implode(',', $this->errors), LOG_ERR);
		}

		if (!$error && empty($notrigger)) {
			$result = $this->call_trigger('CURRENCY_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
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
	 * @param	User	$user		User making the deletion
	 * @param  	int 	$notrigger 	0=launch triggers after, 1=disable triggers
	 * @return 	int 				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		dol_syslog('MultiCurrency::delete', LOG_DEBUG);

		$error = 0;

		$this->db->begin();

		if (empty($notrigger)) {
			$result = $this->call_trigger('CURRENCY_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error) {
			// Delete all rates before
			if (!$this->deleteRates()) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog('Currency::delete  '.implode(',', $this->errors), LOG_ERR);
			}

			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(__METHOD__, LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = 'Error '.$this->db->lasterror();
				dol_syslog('MultiCurrency::delete '.implode(',', $this->errors), LOG_ERR);
			}
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
	 * Delete rates in database
	 *
	 * @return bool false if KO, true if OK
	 */
	public function deleteRates()
	{
		global $user;

		foreach ($this->rates as &$rate) {
			if ($rate->delete($user) <= 0) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Add a Rate into database
	 *
	 * @param double	$rate	rate value
	 * @return int 				-1 if KO, 1 if OK
	 */
	public function addRate($rate)
	{
		global $user;

		$currencyRate = new CurrencyRate($this->db);
		$currencyRate->rate = (float) price2num($rate);

		if ($currencyRate->create($user, $this->id) > 0) {
			$this->rate = $currencyRate;
			return 1;
		} else {
			$this->rate = null;
			$this->errors = $currencyRate->errors;
			return -1;
		}
	}

	/**
	 * Try get label of code in llx_currency then add rate.
	 *
	 * @param	string	$code	currency code
	 * @param	double	$rate	new rate
	 * @return 	int 			-1 if KO, 1 if OK, 2 if label found and OK
	 */
	public function addRateFromDolibarr($code, $rate)
	{
		global $user;

		$currency = new MultiCurrency($this->db);
		$currency->code = $code;
		$currency->name = $code;

		$sql = "SELECT label FROM ".MAIN_DB_PREFIX."c_currencies WHERE code_iso = '".$this->db->escape($code)."'";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql && ($line = $this->db->fetch_object($resql))) {
			$currency->name = $line->label;
		}

		if ($currency->create($user) > 0) {
			$currency->addRate($rate);

			if (!empty($line)) {
				return 2;
			} else {
				return 1;
			}
		}

		return -1;
	}

	/**
	 * Add new entry into llx_multicurrency_rate
	 *
	 * @param double	$rate	rate value
	 * @return int Return integer <0 if KO, >0 if OK
	 */
	public function updateRate($rate)
	{
		return $this->addRate($rate);
	}

	/**
	 * Fetch CurrencyRate object in $this->rate
	 *
	 * @return int Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function getRate()
	{
		$sql = "SELECT cr.rowid";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element_line." as cr";
		$sql .= " WHERE cr.entity IN (".getEntity($this->element).")";
		$sql .= " AND cr.fk_multicurrency = ".((int) $this->id);
		$sql .= " AND cr.date_sync = (SELECT MAX(cr2.date_sync) FROM ".MAIN_DB_PREFIX.$this->table_element_line." AS cr2";
		$sql .= " WHERE cr2.entity IN (".getEntity($this->element).") AND cr2.fk_multicurrency = ".((int) $this->id).")";

		dol_syslog(__METHOD__, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql && ($obj = $this->db->fetch_object($resql))) {
			$this->rate = new CurrencyRate($this->db);
			return $this->rate->fetch($obj->rowid);
		}

		return -1;
	}

	/**
	 * Get id of currency from code
	 *
	 * @param  DoliDB	$dbs	    object db
	 * @param  string	$code	    code value search
	 *
	 * @return int                 0 if not found, >0 if OK
	 */
	public static function getIdFromCode($dbs, $code)
	{
		global $conf;

		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."multicurrency WHERE code = '".$dbs->escape($code)."' AND entity = ".((int) $conf->entity);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $dbs->query($sql);
		if ($resql && $obj = $dbs->fetch_object($resql)) {
			return $obj->rowid;
		} else {
			return 0;
		}
	}

	/**
	 * Get id and rate of currency from code
	 *
	 * @param DoliDB		$dbs	        Object db
	 * @param string		$code	        Code value search
	 * @param int			$date_document	Date from document (propal, order, invoice, ...)
	 *
	 * @return 	array{0:int,1:float}		[0] => id currency
	 *										[1] => rate
	 */
	public static function getIdAndTxFromCode($dbs, $code, $date_document = 0)
	{
		$sql1 = "SELECT m.rowid, mc.rate FROM ".MAIN_DB_PREFIX."multicurrency m";
		$sql1 .= ' LEFT JOIN '.MAIN_DB_PREFIX.'multicurrency_rate mc ON (m.rowid = mc.fk_multicurrency)';
		$sql1 .= " WHERE m.code = '".$dbs->escape($code)."'";
		$sql1 .= " AND m.entity IN (".getEntity('multicurrency').")";
		$sql2 = '';
		if (getDolGlobalString('MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE') && !empty($date_document)) {	// Use last known rate compared to document date
			$tmparray = dol_getdate($date_document);
			$sql2 .= " AND mc.date_sync <= '".$dbs->idate(dol_mktime(23, 59, 59, $tmparray['mon'], $tmparray['mday'], $tmparray['year'], true))."'";
		}
		$sql3 = " ORDER BY mc.date_sync DESC LIMIT 1";

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $dbs->query($sql1.$sql2.$sql3);

		if ($resql && $obj = $dbs->fetch_object($resql)) {
			return array($obj->rowid, $obj->rate);
		} else {
			if (getDolGlobalString('MULTICURRENCY_USE_RATE_ON_DOCUMENT_DATE')) {
				$resql = $dbs->query($sql1.$sql3);
				if ($resql && $obj = $dbs->fetch_object($resql)) {
					return array($obj->rowid, $obj->rate);
				}
			}

			return array(0, 1);
		}
	}

	/**
	 * Get the conversion of amount with invoice rate
	 *
	 * @param	int				$fk_facture				Id of invoice
	 * @param	double			$amount					amount to convert
	 * @param	string			$way					'dolibarr' mean the amount is in dolibarr currency
	 * @param	string			$table					'facture' or 'facture_fourn'
	 * @param	float|null		$invoice_rate			Invoice rate if known (to avoid to make the getInvoiceRate call)
	 * @return	float|false 							amount converted or false if conversion fails
	 */
	public static function getAmountConversionFromInvoiceRate($fk_facture, $amount, $way = 'dolibarr', $table = 'facture', $invoice_rate = null)
	{
		if (!is_null($invoice_rate)) {
			$multicurrency_tx = $invoice_rate;
		} else {
			$tmparray = self::getInvoiceRate($fk_facture, $table);
			$multicurrency_tx = $tmparray['invoice_multicurrency_tx'];
		}

		if ($multicurrency_tx) {
			if ($way == 'dolibarr') {
				return (float) price2num($amount * $multicurrency_tx, 'MU');
			} else {
				return (float) price2num($amount / $multicurrency_tx, 'MU');
			}
		} else {
			return false;
		}
	}

	/**
	 *  Get current invoite rate
	 *
	 *  @param	int 		$fk_facture 	id of facture
	 *  @param 	string 		$table 			facture or facture_fourn
	 *  @return array{invoice_multicurrency_tx: float,invoice_multicurrency_code: string}|bool	Rate and code of currency or false if error
	 */
	public static function getInvoiceRate($fk_facture, $table = 'facture')
	{
		global $db;

		$sql = "SELECT multicurrency_tx, multicurrency_code";
		$sql .= " FROM ".MAIN_DB_PREFIX.$db->sanitize($table);
		$sql .= " WHERE rowid = ".((int) $fk_facture);

		dol_syslog(__METHOD__, LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql && ($line = $db->fetch_object($resql))) {
			return array('invoice_multicurrency_tx' => $line->multicurrency_tx, 'invoice_multicurrency_code' => $line->multicurrency_code);
		}

		return false;
	}

	/**
	 * With free account we can't set source to something else than US, to we recalculate all rates to force another source.
	 * This modify the array &$TRate.
	 * It is called by the syncRates() method.
	 *
	 * @param   stdClass	$TRate	Object containing all currencies rates to recalculate
	 * @return	int					-1 if KO, 0 if nothing, 1 if OK
	 */
	public function recalculRates(&$TRate)
	{
		global $conf;

		if ($conf->currency != getDolGlobalString('MULTICURRENCY_APP_SOURCE')) {
			$alternate_source = 'USD'.$conf->currency;
			if (!empty($TRate->$alternate_source)) {
				$coef = 1 / $TRate->$alternate_source;
				foreach ($TRate as $attr => &$rate) {
					$rate *= $coef;
				}
				$TRate->USDUSD = $coef;
				return 1;
			}

			return -1; // Alternate source not found
		}

		return 0; // Nothing to do
	}

	/**
	 * Sync rates from API.
	 * This is called by the admin page and by the autoupdate cron job.
	 *
	 * @param 	int			$nu	                No more used
	 * @param   int 	    $addifnotfound      Add if not found
	 * @param   string  	$mode				"" for standard use, "cron" to use it in a cronjob
	 * @return  int								Return integer <0 if KO, >0 if OK, if mode = "cron" OK is 0
	 */
	public function syncRates($nu = 0, $addifnotfound = 0, $mode = "")
	{
		global $db, $langs;

		if (getDolGlobalString('MULTICURRENCY_DISABLE_SYNC_CURRENCYLAYER')) {
			if ($mode == "cron") {
				$this->output = $langs->trans('Use of API for currency update is disabled by option MULTICURRENCY_DISABLE_SYNC_CURRENCYLAYER');
			} else {
				setEventMessages($langs->trans('Use of API for currency update is disabled by option MULTICURRENCY_DISABLE_SYNC_CURRENCYLAYER'), null, 'errors');
			}
			return -1;
		}

		include_once DOL_DOCUMENT_ROOT.'/core/lib/geturl.lib.php';

		$urlendpoint = $this->urlendpoint;

		dol_syslog("Call url endpoint ".$urlendpoint);

		$addheaders = array('apikey: '.getDolGlobalString('MULTICURRENCY_APP_KEY'));

		$resget = getURLContent($urlendpoint, 'GET', '', 1, $addheaders);

		// Example of result with https://currencylayer.com/live and https://api.apilayer.com/currency_data/live
		// 'content' => string '{"success":true,"terms":"https:\/\/currencylayer.com\/terms","privacy":"https:\/\/currencylayer.com\/privacy","timestamp":1742562251,"source":"USD","quotes":{"USDAED":3.67302,"USDAFN":70.6213,"USDALL":91.042287,"USDAMD":390.984233,"USDANG":1.802039,"USDAOA":913.498241,"USDARS":1068.745088,"USDAUD":1.591824,"USDAWG":1.8,"USDAZN":1.699323,"USDBAM":1.80224,"USDBBD":2.018881,"USDBDT":121.488567,"USDBGN":1.802745,"USDBHD":0.376878,"USDBIF":2963.403228,"USDBMD":1,"USDBND":1.333573,"USDBOB":6.909262,"USDBRL":5.721'... (length=3337)
		//var_dump($urlendpoint);
		//var_dump($resget);

		if (!empty($resget['content'])) {
			$response = $resget['content'];
			$response = json_decode($response);

			if ($response->success) {
				$TRate = $response->quotes;
				//$timestamp = $response->timestamp;

				// Recalculate rate and update it (or add it) into database
				if ($this->recalculRates($TRate) >= 0) {
					foreach ($TRate as $currency_code => $rate) {
						$code = substr($currency_code, 3, 3);
						$obj = new MultiCurrency($db);
						if ($obj->fetch(0, $code) > 0) {
							$obj->updateRate($rate);
						} elseif ($addifnotfound) {
							$this->addRateFromDolibarr($code, $rate);
						}
					}
				}

				if ($mode == "cron") {
					return 0;
				}
				return 1;
			} else {
				if (isset($response->error->info)) {
					$error_info_syslog = $response->error->info;  // @phan-suppress-current-line PhanTypeExpectedObjectPropAccess
					$error_info = $error_info_syslog;
				} else {
					$error_info_syslog = json_encode($response);
					if (empty($resget['content'])) {
						$error_info = "No error information found (see syslog)";
					} else {
						$error_info = $resget['content'];
					}
				}

				dol_syslog("Failed to call endpoint ".$error_info_syslog, LOG_WARNING);

				$this->output = $langs->trans('multicurrency_syncronize_error', $error_info);

				return -1;
			}
		} else {
			$this->output = $resget['curl_error_msg'];

			return -1;
		}
	}

	/**
	 * Check in database if the current code already exists
	 *
	 * @param	string	$code	current code to search
	 * @return	bool			True if exists, false if not exists
	 */
	public function checkCodeAlreadyExists($code)
	{
		$currencytmp = new MultiCurrency($this->db);
		if ($currencytmp->fetch(0, $code) > 0) {
			return true;
		} else {
			return false;
		}
	}
}
