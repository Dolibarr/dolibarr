<?php
/*
 * Copyright (C) 2013-2015 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2015 Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * \file 		htdocs/core/db/DoliDB.class.php
 * \brief 		Class file to manage Dolibarr database access
 */

require_once DOL_DOCUMENT_ROOT.'/core/db/Database.interface.php';


/**
 * Class to manage Dolibarr database access
 */
abstract class DoliDB implements Database
{
	/** Force subclass to implement VERSIONMIN - required DB version */
	const VERSIONMIN = self::VERSIONMIN;
	/** Force subclass to implement LABEL - description of DB type */
	const LABEL = self::LABEL;

	/** @var false|resource|mysqli|mysqliDoli|SQLite3|PgSql\Connection|DoliDB Database handler */
	public $db;
	/** @var string Database type */
	public $type;
	/** @var string Charset used to force charset when creating database */
	public $forcecharset = 'utf8';
	/** @var string Collate used to force collate when creating database */
	public $forcecollate = 'utf8_unicode_ci';

	/** @var resource Resultset of last query */
	private $_results;

	/** @var bool true if connected, else false */
	public $connected;
	/** @var bool true if database selected, else false */
	public $database_selected;
	/** @var string Selected database name */
	public $database_name;
	/** @var string Database username */
	public $database_user;
	/** @var string Database host */
	public $database_host;
	/** @var int Database port */
	public $database_port;
	/** @var int >=1 if a transaction is opened, 0 otherwise */
	public $transaction_opened;
	/** @var string Last successful query */
	public $lastquery;
	/** @var string Last failed query */
	public $lastqueryerror;
	/** @var string Last error message */
	public $lasterror;
	/** @var string Last error number. For example: 'DB_ERROR_RECORD_ALREADY_EXISTS', '12345', ... */
	public $lasterrno;

	/** @var string If we need to set a prefix specific to the database so it can be reused (when defined instead of MAIN_DB_PREFIX) to forge requests */
	public $prefix_db;

	/** @var bool Status */
	public $ok;
	/** @var string */
	public $error;



	/**
	 *	Return the DB prefix found into prefix_db (if it was set manually by doing $dbhandler->prefix_db=...).
	 *  Otherwise return MAIN_DB_PREFIX (common use).
	 *
	 *	@return string		The DB prefix
	 */
	public function prefix()
	{
		return (empty($this->prefix_db) ? MAIN_DB_PREFIX : $this->prefix_db);
	}

	/**
	 *	Format a SQL IF
	 *
	 *	@param	string	$test           Test string (example: 'cd.statut=0', 'field IS NULL')
	 *	@param	string	$resok          resultat si test equal
	 *	@param	string	$resko          resultat si test non equal
	 *	@return	string          		SQL string
	 */
	public function ifsql($test, $resok, $resko)
	{
		//return 'IF('.$test.','.$resok.','.$resko.')';		// Not sql standard
		return '(CASE WHEN '.$test.' THEN '.$resok.' ELSE '.$resko.' END)';
	}

	/**
	 * Return SQL string to aggregate using the Standard Deviation of population
	 *
	 * @param	string	$nameoffield	Name of field
	 * @return	string					SQL string
	 */
	public function stddevpop($nameoffield)
	{
		return 'STDDEV_POP('.$nameoffield.')';
	}

	/**
	 * Return SQL string to force an index
	 *
	 * @param	string	$nameofindex	Name of index
	 * @return	string					SQL string
	 */
	public function hintindex($nameofindex)
	{
		return '';
	}


	/**
	 *	Format a SQL REGEXP
	 *
	 *	@param	string	$subject        Field name to test
	 *	@param	string  $pattern        SQL pattern to match
	 *	@param	int		$sqlstring      0=the string being tested is a hard coded string, 1=the string is a field
	 *	@return	string          		SQL string
	 */
	public function regexpsql($subject, $pattern, $sqlstring = 0)
	{
		if ($sqlstring) {
			return "(". $subject ." REGEXP '" . $this->escape($pattern) . "')";
		}

		return "('". $this->escape($subject) ."' REGEXP '" . $this->escape($pattern) . "')";
	}


	/**
	 *   Convert (by PHP) a GM Timestamp date into a string date with PHP server TZ to insert into a date field.
	 *   Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 *   @param	    int		$param      Date TMS to convert
	 *	 @param		mixed	$gm			'gmt'=Input information are GMT values, 'tzserver'=Local to server TZ
	 *   @return	string      		Date in a string YYYY-MM-DD HH:MM:SS
	 */
	public function idate($param, $gm = 'tzserver')
	{
		// TODO $param should be gmt, so we should have default $gm to 'gmt' instead of default 'tzserver'
		return dol_print_date($param, "%Y-%m-%d %H:%M:%S", $gm);
	}

	/**
	 *	Return last error code
	 *
	 *	@return	    string	lasterrno
	 */
	public function lasterrno()
	{
		return $this->lasterrno;
	}

	/**
	 * Sanitize a string for SQL forging
	 *
	 * @param   string 	$stringtosanitize 	String to escape
	 * @param   int		$allowsimplequote 	1=Allow simple quotes in string. When string is used as a list of SQL string ('aa', 'bb', ...)
	 * @param	int		$allowsequals		1=Allow equals sign
	 * @param	int		$allowsspace		1=Allow space char
	 * @param	int		$allowschars		1=Allow a-z chars
	 * @return  string                      String escaped
	 */
	public function sanitize($stringtosanitize, $allowsimplequote = 0, $allowsequals = 0, $allowsspace = 0, $allowschars = 1)
	{
		return preg_replace('/[^0-9_\-\.,'.($allowschars ? 'a-z' : '').($allowsequals ? '=' : '').($allowsimplequote ? "\'" : '').($allowsspace ? ' ' : '').']/i', '', $stringtosanitize);
	}

	/**
	 * Start transaction
	 *
	 * @param	string	$textinlog		Add a small text into log. '' by default.
	 * @return	int         			1 if transaction successfully opened or already opened, 0 if error
	 */
	public function begin($textinlog = '')
	{
		if (!$this->transaction_opened) {
			$ret = $this->query("BEGIN");
			if ($ret) {
				$this->transaction_opened++;
				dol_syslog("BEGIN Transaction".($textinlog ? ' '.$textinlog : ''), LOG_DEBUG);
				dol_syslog('', 0, 1);
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->transaction_opened++;
			dol_syslog('', 0, 1);
			return 1;
		}
	}

	/**
	 * Validate a database transaction
	 *
	 * @param	string	$log		Add more log to default log line
	 * @return	int         		1 if validation is OK or transaction level no started, 0 if ERROR
	 */
	public function commit($log = '')
	{
		dol_syslog('', 0, -1);
		if ($this->transaction_opened <= 1) {
			$ret = $this->query("COMMIT");
			if ($ret) {
				$this->transaction_opened = 0;
				dol_syslog("COMMIT Transaction".($log ? ' '.$log : ''), LOG_DEBUG);
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->transaction_opened--;
			return 1;
		}
	}

	/**
	 *	Cancel a transaction and go back to initial data values
	 *
	 * 	@param	string			$log		Add more log to default log line
	 * 	@return	resource|int         		1 if cancellation is ok or transaction not open, 0 if error
	 */
	public function rollback($log = '')
	{
		dol_syslog('', 0, -1);
		if ($this->transaction_opened <= 1) {
			$ret = $this->query("ROLLBACK");
			$this->transaction_opened = 0;
			dol_syslog("ROLLBACK Transaction".($log ? ' '.$log : ''), LOG_DEBUG);
			return $ret;
		} else {
			$this->transaction_opened--;
			return 1;
		}
	}

	/**
	 *	Define limits and offset of request
	 *
	 *	@param	int		$limit      Maximum number of lines returned (-1=conf->liste_limit, 0=no limit)
	 *	@param	int		$offset     Numero of line from where starting fetch
	 *	@return	string      		String with SQL syntax to add a limit and offset
	 */
	public function plimit($limit = 0, $offset = 0)
	{
		global $conf;
		if (empty($limit)) {
			return "";
		}
		if ($limit < 0) {
			$limit = $conf->liste_limit;
		}
		if ($offset > 0) {
			return " LIMIT ".((int) $offset).",".((int) $limit)." ";
		} else {
			return " LIMIT ".((int) $limit)." ";
		}
	}

	/**
	 *	Return version of database server into an array
	 *
	 *	@return	        string[]  		Version array
	 */
	public function getVersionArray()
	{
		return preg_split("/[\.,-]/", $this->getVersion());
	}

	/**
	 *	Return last request executed with query()
	 *
	 *	@return	string					Last query
	 */
	public function lastquery()
	{
		return $this->lastquery;
	}

	/**
	 * Define sort criteria of request
	 *
	 * @param	string		$sortfield		List of sort fields, separated by comma. Example: 't1.fielda,t2.fieldb'
	 * @param	string		$sortorder		Sort order, separated by comma. Example: 'ASC,DESC'. Note: If the quantity for sortorder values is lower than sortfield, we used the last value for missing values.
	 * @return	string						String to provide syntax of a sort sql string
	 */
	public function order($sortfield = '', $sortorder = '')
	{
		if (!empty($sortfield)) {
			$oldsortorder = '';
			$return = '';
			$fields = explode(',', $sortfield);
			$orders = (!empty($sortorder) ? explode(',', $sortorder) : array());
			$i = 0;
			foreach ($fields as $val) {
				if (!$return) {
					$return .= ' ORDER BY ';
				} else {
					$return .= ', ';
				}

				$return .= preg_replace('/[^0-9a-z_\.]/i', '', $val); // Add field

				$tmpsortorder = (empty($orders[$i]) ? '' : trim($orders[$i]));

				// Only ASC and DESC values are valid SQL
				if (strtoupper($tmpsortorder) === 'ASC') {
					$oldsortorder = 'ASC';
					$return .= ' ASC';
				} elseif (strtoupper($tmpsortorder) === 'DESC') {
					$oldsortorder = 'DESC';
					$return .= ' DESC';
				} else {
					$return .= ' '.($oldsortorder ? $oldsortorder : 'ASC');
				}

				$i++;
			}
			return $return;
		} else {
			return '';
		}
	}

	/**
	 *	Return last error label
	 *
	 *	@return	    string		Last error
	 */
	public function lasterror()
	{
		return $this->lasterror;
	}

	/**
	 *	Convert (by PHP) a PHP server TZ string date into a Timestamps date (GMT if gm=true)
	 * 	19700101020000 -> 3600 with server TZ = +1 and $gm='tzserver'
	 * 	19700101020000 -> 7200 whaterver is server TZ if $gm='gmt'
	 *
	 * 	@param	string				$string		Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	@param	mixed				$gm			'gmt'=Input information are GMT values, 'tzserver'=Local to server TZ
	 *	@return	int|''							Date TMS or ''
	 */
	public function jdate($string, $gm = 'tzserver')
	{
		// TODO $string should be converted into a GMT timestamp, so param gm should be set to true by default instead of false
		if ($string == 0 || $string == "0000-00-00 00:00:00") {
			return '';
		}
		$string = preg_replace('/([^0-9])/i', '', $string);
		$tmp = $string.'000000';
		$date = dol_mktime((int) substr($tmp, 8, 2), (int) substr($tmp, 10, 2), (int) substr($tmp, 12, 2), (int) substr($tmp, 4, 2), (int) substr($tmp, 6, 2), (int) substr($tmp, 0, 4), $gm);
		return $date;
	}

	/**
	 *	Return last query in error
	 *
	 *	@return	    string	lastqueryerror
	 */
	public function lastqueryerror()
	{
		return $this->lastqueryerror;
	}

	/**
	 * Return first result from query as object
	 * Note : This method executes a given SQL query and retrieves the first row of results as an object. It should only be used with SELECT queries
	 * Don't add LIMIT to your query, it will be added by this method
	 *
	 * @param 	string 				$sql 	The sql query string
	 * @return 	bool|int|object    			False on failure, 0 on empty, object on success
	 */
	public function getRow($sql)
	{
		$sql .= ' LIMIT 1';

		$resql = $this->query($sql);
		if ($resql) {
			$obj = $this->fetch_object($resql);
			if ($obj) {
				$this->free($resql);
				return $obj;
			} else {
				return 0;
			}
		}

		return false;
	}

	/**
	 * Return all results from query as an array of objects. Using this is a bad practice and is discouraged.
	 * Note : It should only be used with SELECT queries and with a limit. If you are not able to defined/know what can be the limit, it
	 * just means this function is not what you need. Do not use it.
	 *
	 * @param 	string 			$sql 	The sql query string. Must end with "... LIMIT x"
	 * @return  bool|array              Result
	 */
	public function getRows($sql)
	{
		if (!preg_match('/LIMIT \d+(?:(?:,\ *\d*)|(?:\ +OFFSET\ +\d*))?\ *;?$/', $sql)) {
			trigger_error(__CLASS__ .'::'.__FUNCTION__.'() query must have a LIMIT clause', E_USER_ERROR);
		}

		$resql = $this->query($sql);
		if ($resql) {
			$results = array();
			if ($this->num_rows($resql) > 0) {
				while ($obj = $this->fetch_object($resql)) {
					$results[] = $obj;
				}
			}
			$this->free($resql);
			return $results;
		}

		return false;
	}
}
