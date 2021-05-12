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
<<<<<<< HEAD
	function ifsql($test,$resok,$resko)
=======
    public function ifsql($test, $resok, $resko)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function idate($param)
	{
		// TODO GMT $param should be gmt, so we should add tzouptut to 'gmt'
		return dol_print_date($param,"%Y-%m-%d %H:%M:%S");
=======
    public function idate($param)
	{
		// TODO GMT $param should be gmt, so we should add tzouptut to 'gmt'
		return dol_print_date($param, "%Y-%m-%d %H:%M:%S");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 *	Return last error code
	 *
	 *	@return	    string	lasterrno
	 */
<<<<<<< HEAD
	function lasterrno()
=======
    public function lasterrno()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->lasterrno;
	}

	/**
	 * Start transaction
	 *
	 * @return	    int         1 if transaction successfuly opened or already opened, 0 if error
	 */
<<<<<<< HEAD
	function begin()
=======
    public function begin()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		if (! $this->transaction_opened)
		{
			$ret=$this->query("BEGIN");
			if ($ret)
			{
				$this->transaction_opened++;
<<<<<<< HEAD
				dol_syslog("BEGIN Transaction",LOG_DEBUG);
				dol_syslog('',0,1);
=======
				dol_syslog("BEGIN Transaction", LOG_DEBUG);
				dol_syslog('', 0, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}
			return $ret;
		}
		else
		{
			$this->transaction_opened++;
<<<<<<< HEAD
			dol_syslog('',0,1);
=======
			dol_syslog('', 0, 1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return 1;
		}
	}

	/**
	 * Validate a database transaction
	 *
	 * @param	string	$log		Add more log to default log line
	 * @return	int         		1 if validation is OK or transaction level no started, 0 if ERROR
	 */
<<<<<<< HEAD
	function commit($log='')
	{
		dol_syslog('',0,-1);
=======
    public function commit($log = '')
	{
		dol_syslog('', 0, -1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("COMMIT");
			if ($ret)
			{
				$this->transaction_opened=0;
<<<<<<< HEAD
				dol_syslog("COMMIT Transaction".($log?' '.$log:''),LOG_DEBUG);
=======
				dol_syslog("COMMIT Transaction".($log?' '.$log:''), LOG_DEBUG);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	 *	Annulation d'une transaction et retour aux anciennes valeurs
	 *
	 * 	@param	string			$log		Add more log to default log line
	 * 	@return	resource|int         		1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
	 */
	function rollback($log='')
	{
		dol_syslog('',0,-1);
=======
	 *	Cancel a transaction and go back to initial data values
	 *
	 * 	@param	string			$log		Add more log to default log line
	 * 	@return	resource|int         		1 if cancelation is ok or transaction not open, 0 if error
	 */
    public function rollback($log = '')
	{
		dol_syslog('', 0, -1);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		if ($this->transaction_opened<=1)
		{
			$ret=$this->query("ROLLBACK");
			$this->transaction_opened=0;
<<<<<<< HEAD
			dol_syslog("ROLLBACK Transaction".($log?' '.$log:''),LOG_DEBUG);
=======
			dol_syslog("ROLLBACK Transaction".($log?' '.$log:''), LOG_DEBUG);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function plimit($limit=0,$offset=0)
=======
    public function plimit($limit = 0, $offset = 0)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function getVersionArray()
	{
		return preg_split("/[\.,-]/",$this->getVersion());
=======
    public function getVersionArray()
	{
		return preg_split("/[\.,-]/", $this->getVersion());
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 *	Return last request executed with query()
	 *
	 *	@return	string					Last query
	 */
<<<<<<< HEAD
	function lastquery()
=======
    public function lastquery()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function order($sortfield=null,$sortorder=null)
=======
    public function order($sortfield = null, $sortorder = null)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		if (! empty($sortfield))
		{
			$return='';
<<<<<<< HEAD
			$fields=explode(',',$sortfield);
			$orders=explode(',',$sortorder);
=======
			$fields=explode(',', $sortfield);
			$orders=explode(',', $sortorder);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			$i=0;
			foreach($fields as $val)
			{
				if (! $return) $return.=' ORDER BY ';
				else $return.=', ';

<<<<<<< HEAD
				$return.=preg_replace('/[^0-9a-z_\.]/i','',$val);
=======
				$return.=preg_replace('/[^0-9a-z_\.]/i', '', $val);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
	function lasterror()
=======
    public function lasterror()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function jdate($string, $gm=false)
	{
		// TODO GMT must set param gm to true by default
		if ($string==0 || $string=="0000-00-00 00:00:00") return '';
		$string=preg_replace('/([^0-9])/i','',$string);
		$tmp=$string.'000000';
		$date=dol_mktime(substr($tmp,8,2),substr($tmp,10,2),substr($tmp,12,2),substr($tmp,4,2),substr($tmp,6,2),substr($tmp,0,4),$gm);
=======
    public function jdate($string, $gm = false)
	{
		// TODO GMT must set param gm to true by default
		if ($string==0 || $string=="0000-00-00 00:00:00") return '';
		$string=preg_replace('/([^0-9])/i', '', $string);
		$tmp=$string.'000000';
		$date=dol_mktime((int) substr($tmp, 8, 2), (int) substr($tmp, 10, 2), (int) substr($tmp, 12, 2), (int) substr($tmp, 4, 2), (int) substr($tmp, 6, 2), (int) substr($tmp, 0, 4), $gm);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		return $date;
	}

	/**
	 *	Return last query in error
	 *
	 *	@return	    string	lastqueryerror
	 */
<<<<<<< HEAD
	function lastqueryerror()
=======
    public function lastqueryerror()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->lastqueryerror;
	}
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
