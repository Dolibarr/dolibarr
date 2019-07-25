<?php
/*
 * Copyright (C) 2013-2015 Raphaël Doursenaud <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2014-2015 Laurent Destailleur <eldy@users.sourceforge.net>
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
 * \file 		htdocs/core/db/DoliDB.class.php
 * \brief 		Class file to manage Dolibarr database access
 */

require_once DOL_DOCUMENT_ROOT .'/core/db/Database.interface.php';

/**
 * Class to manage Dolibarr database access
 */
abstract class DoliDB implements Database
{
	/** @var resource Database handler */
	public $db;
	/** @var string Database type */
	public $type;
	/** @var string Charset used to force charset when creating database */
	public $forcecharset='utf8';
	/** @var string Collate used to force collate when creating database */
	public $forcecollate='utf8_unicode_ci';
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

	/** @var bool Status */
	public $ok;
	/** @var string */
	public $error;

	/**
	 *	Format a SQL IF
	 *
	 *	@param	string	$test           Test string (example: 'cd.statut=0', 'field IS NULL')
	 *	@param	string	$resok          resultat si test egal
	 *	@param	string	$resko          resultat si test non egal
	 *	@return	string          		SQL string
	 */
    public function ifsql($test, $resok, $resko)
	{
		return 'IF('.$test.','.$resok.','.$resko.')';
	}

	/**
	 *   Convert (by PHP) a GM Timestamp date into a string date with PHP server TZ to insert into a date field.
	 *   Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 *   @param	    int		$param      	Date TMS to convert
	 *   @return	string      			Date in a string YYYY-MM-DD HH:MM:SS
	 */
    public function idate($param)
	{
		// TODO GMT $param should be gmt, so we should add tzouptut to 'gmt'
		return dol_print_date($param, "%Y-%m-%d %H:%M:%S");
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
	 * Start transaction
	 *
	 * @return	    int         1 if transaction successfuly opened or already opened, 0 if error
	 */
    public function begin()
	{
		if (! $this->transaction_opened)
		{
			$ret=$this->query("BEGIN");
			if ($ret)
			{
				$this->transaction_opened++;
				dol_syslog("BEGIN Transaction", LOG_DEBUG);
				dol_syslog('', 0, 1);
			}
			return $ret;
		}
		else
		{
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
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("COMMIT");
			if ($ret)
			{
				$this->transaction_opened=0;
				dol_syslog("COMMIT Transaction".($log?' '.$log:''), LOG_DEBUG);
				return 1;
			}
			else
			{
				return 0;
			}
		}
		else
		{
			$this->transaction_opened--;
			return 1;
		}
	}

	/**
	 *	Cancel a transaction and go back to initial data values
	 *
	 * 	@param	string			$log		Add more log to default log line
	 * 	@return	resource|int         		1 if cancelation is ok or transaction not open, 0 if error
	 */
    public function rollback($log = '')
	{
		dol_syslog('', 0, -1);
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("ROLLBACK");
			$this->transaction_opened=0;
			dol_syslog("ROLLBACK Transaction".($log?' '.$log:''), LOG_DEBUG);
			return $ret;
		}
		else
		{
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
		if (empty($limit)) return "";
		if ($limit < 0) $limit=$conf->liste_limit;
		if ($offset > 0) return " LIMIT $offset,$limit ";
		else return " LIMIT $limit ";
	}

	/**
	 *	Return version of database server into an array
	 *
	 *	@return	        array  		Version array
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
	 * @param	string		$sortorder		Sort order, separated by comma. Example: 'ASC,DESC';
	 * @return	string						String to provide syntax of a sort sql string
	 */
    public function order($sortfield = null, $sortorder = null)
	{
		if (! empty($sortfield))
		{
			$return='';
			$fields=explode(',', $sortfield);
			$orders=explode(',', $sortorder);
			$i=0;
			foreach($fields as $val)
			{
				if (! $return) $return.=' ORDER BY ';
				else $return.=', ';

				$return.=preg_replace('/[^0-9a-z_\.]/i', '', $val);

				$tmpsortorder = trim($orders[$i]);

				// Only ASC and DESC values are valid SQL
				if (strtoupper($tmpsortorder) === 'ASC') {
					$return .= ' ASC';
				} elseif (strtoupper($tmpsortorder) === 'DESC') {
					$return .= ' DESC';
				}

				$i++;
			}
			return $return;
		}
		else
		{
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
	 * 	19700101020000 -> 3600 with TZ+1 and gmt=0
	 * 	19700101020000 -> 7200 whaterver is TZ if gmt=1
	 *
	 * 	@param	string				$string		Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 *	@param	bool				$gm			1=Input informations are GMT values, otherwise local to server TZ
	 *	@return	int|string						Date TMS or ''
	 */
    public function jdate($string, $gm = false)
	{
		// TODO GMT must set param gm to true by default
		if ($string==0 || $string=="0000-00-00 00:00:00") return '';
		$string=preg_replace('/([^0-9])/i', '', $string);
		$tmp=$string.'000000';
		$date=dol_mktime((int) substr($tmp, 8, 2), (int) substr($tmp, 10, 2), (int) substr($tmp, 12, 2), (int) substr($tmp, 4, 2), (int) substr($tmp, 6, 2), (int) substr($tmp, 0, 4), $gm);
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
}
