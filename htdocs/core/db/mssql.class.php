<?php
/* Copyright (C) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Simon Desee          <simon@dedisoft.com>
 * Copyright (C) 2015       Cedric GROSS            <c.gross@kreiz-it.fr>
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
 *	\file       	htdocs/core/db/mssql.class.php
 *	\brief			Fichier de la classe permettant de gerer une base MSSQL
 */

require_once DOL_DOCUMENT_ROOT .'/core/db/DoliDB.class.php';

/**
 *	Classe de gestion de la database de dolibarr
 */
class DoliDBMssql extends DoliDB
{
	//! Database type
	public $type='mssql';
	//! Database label
	const LABEL='MSSQL';
	//! Charset used to force charset when creating database
	var $forcecharset='latin1';      // Can't be static as it may be forced with a dynamic value
	//! Collate used to force collate when creating database
	var $forcecollate='latin1_swedish_ci';      // Can't be static as it may be forced with a dynamic value
	//! Version min database
	const VERSIONMIN='2000';
	/** @var resource Resultset of last query */
	private $_results;

    /**
	 *	Constructor.
	 *	This create an opened connexion to a database server and eventually to a database
	 *
	 *	@param      string	$type		Type of database (mysql, pgsql...)
	 *	@param	    string	$host		Address of database server
	 *	@param	    string	$user		Nom de l'utilisateur autorise
	 *	@param	    string	$pass		Mot de passe
	 *	@param	    string	$name		Nom de la database
	 *	@param	    int		$port		Port of database server
     */
	function __construct($type, $host, $user, $pass, $name='', $port=0)
	{
		global $langs;

		$this->database_user=$user;
        $this->database_host=$host;
        $this->database_port=$port;
		$this->transaction_opened=0;

		if (! function_exists("mssql_connect"))
		{
			$this->connected = false;
			$this->ok = false;
			$this->error="Mssql PHP functions for using MSSql driver are not available in this version of PHP";
			dol_syslog(get_class($this)."::DoliDBMssql : MSsql PHP functions for using MSsql driver are not available in this version of PHP",LOG_ERR);
			return $this->ok;
		}

		if (! $host)
		{
			$this->connected = false;
			$this->ok = false;
			$this->error=$langs->trans("ErrorWrongHostParameter");
			dol_syslog(get_class($this)."::DoliDBMssql : Erreur Connect, wrong host parameters",LOG_ERR);
			return $this->ok;
		}

		// Essai connexion serveur
		$this->db = $this->connect($host, $user, $pass, $name, $port);
		if ($this->db)
		{
			// Si client connecte avec charset different de celui de la base Dolibarr
			// (La base Dolibarr a ete forcee en this->forcecharset a l'install)
			$this->connected = true;
			$this->ok = true;
		}
		else
		{
			// host, login ou password incorrect
			$this->connected = false;
			$this->ok = false;
			$this->error=mssql_get_last_message();
			dol_syslog(get_class($this)."::DoliDBMssql : Erreur Connect mssql_get_last_message=".$this->error,LOG_ERR);
		}

		// Si connexion serveur ok et si connexion base demandee, on essaie connexion base
		if ($this->connected && $name)
		{
			if ($this->select_db($name))
			{
				$this->database_selected = true;
				$this->database_name = $name;
				$this->ok = true;
			}
			else
			{
				$this->database_selected = false;
				$this->database_name = '';
				$this->ok = false;
				$this->error=$this->error();
				dol_syslog(get_class($this)."::DoliDBMssql : Erreur Select_db ".$this->error,LOG_ERR);
			}
		}
		else
		{
			// Pas de selection de base demandee, ok ou ko
			$this->database_selected = false;
		}

		return $this->ok;
	}

    /**
     *  Convert a SQL request in Mysql syntax to native syntax
     *
     *  @param     string	$line   SQL request line to convert
     *  @param     string	$type	Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
     *  @return    string   		SQL request line converted
     */
	static function convertSQLFromMysql($line,$type='ddl')
	{
		return $line;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Select a database
	 *
	 *	@param	    string	$database	Name of database
	 *	@return	    boolean  		    true if OK, false if KO
	 */
	function select_db($database)
	{
        // phpcs:enable
		return @mssql_select_db($database, $this->db);
	}

	/**
	 *	Connexion to server
	 *
	 *	@param	    string	$host		database server host
	 *	@param	    string	$login		login
	 *	@param	    string	$passwd		password
	 *	@param		string	$name		name of database (not used for mysql, used for pgsql)
	 *	@param		int		$port		Port of database server
	 *	@return		false|resource|true	Database access handler
	 *	@see		close
	 */
	function connect($host, $login, $passwd, $name, $port=0)
	{
		dol_syslog(get_class($this)."::connect host=$host, port=$port, login=$login, passwd=--hidden--, name=$name");
		$newhost=$host;
		if ($port) $newhost.=':'.$port;
		$this->db  = @mssql_connect($newhost, $login, $passwd);
		//force les enregistrement en latin1 si la base est en utf8 par defaut
		// Supprime car plante sur mon PHP-Mysql. De plus, la base est forcement en latin1 avec
		// les nouvelles version de Dolibarr car force par l'install Dolibarr.
		//$this->query('SET NAMES '.$this->forcecharset);
		//print "Resultat fonction connect: ".$this->db;
		$set_options=array('SET ANSI_PADDING ON;',
		    "SET ANSI_NULLS ON;",
		    "SET ANSI_WARNINGS ON;",
		    "SET ARITHABORT ON;",
		    "SET CONCAT_NULL_YIELDS_NULL ON;",
		    "SET QUOTED_IDENTIFIER ON;"
		);
		mssql_query(implode(' ',$set_options),$this->db);

		return $this->db;
	}

	/**
	 *	Return version of database server
	 *
	 *	@return	        string      Version string
	 */
	function getVersion()
	{
		$resql=$this->query("SELECT @@VERSION");
		if ($resql)
		{
            $version=$this->fetch_array($resql);
            return $version['computed'];
		}
		else return '';
	}

	/**
	 *	Return version of database client driver
	 *
	 *	@return	        string      Version string
	 */
	function getDriverInfo()
	{
		return 'php mssql driver';
	}

    /**
     *  Close database connexion
     *
     *  @return     bool     True if disconnect successfull, false otherwise
     *  @see        connect
     */
    function close()
    {
        if ($this->db)
        {
          if ($this->transaction_opened > 0) dol_syslog(get_class($this)."::close Closing a connection with an opened transaction depth=".$this->transaction_opened,LOG_ERR);
          $this->connected=false;
          return mssql_close($this->db);
        }
        return false;
    }


	/**
	 * Start transaction
	 *
	 * @return	    bool         true if transaction successfuly opened or already opened, false if error
	 */
	function begin()
	{

	    $res=mssql_query('select @@TRANCOUNT');
	    $this->transaction_opened=mssql_result($res, 0, 0);

	    if ($this->transaction_opened == 0)
		{
		    //return 1; //There is a mess with auto_commit and 'SET IMPLICIT_TRANSACTIONS ON' generate also a mess
			$ret=mssql_query("SET IMPLICIT_TRANSACTIONS OFF;BEGIN TRANSACTION;",$this->db);
			if ($ret)
			{
				dol_syslog("BEGIN Transaction",LOG_DEBUG);
			}
			return $ret;
		}
		else
		{
			return true;
		}
	}

	/**
     * Validate a database transaction
     *
     * @param	string	$log        Add more log to default log line
     * @return  bool         		true if validation is OK or transaction level no started, false if ERROR
	 */
	function commit($log='')
	{
	    $res=mssql_query('select @@TRANCOUNT');
	    $this->transaction_opened=mssql_result($res, 0, 0);

		if ($this->transaction_opened == 1)
		{
		    //return 1; //There is a mess with auto_commit and 'SET IMPLICIT_TRANSACTION ON' generate also a mess
			$ret=mssql_query("COMMIT TRANSACTION",$this->db);
			if ($ret)
			{
				dol_syslog("COMMIT Transaction",LOG_DEBUG);
				return true;
			}
			else
			{
				return false;
			}
		}
		elseif ($this->transaction_opened > 1)
		{
			return true;
		}
		trigger_error("Commit requested but no transaction remain");
		return false;
	}

	/**
	 * Annulation d'une transaction et retour aux anciennes valeurs
	 *
	 * @param	string	$log	Add more log to default log line
	 * @return	bool             true si annulation ok ou transaction non ouverte, false en cas d'erreur
	 */
	function rollback($log='')
	{
	    $res=mssql_query('select @@TRANCOUNT');
	    $this->transaction_opened=mssql_result($res, 0, 0);

		if ($this->transaction_opened == 1)
		{
			$ret=mssql_query("ROLLBACK TRANSACTION",$this->db);
			dol_syslog("ROLLBACK Transaction".($log?' '.$log:''),LOG_DEBUG);
			return $ret;
		}
		elseif ($this->transaction_opened > 1)
		{
			return true;
		}
		trigger_error("Rollback requested but no transaction remain");
		return false;
	}

	/**
     *  Execute a SQL request and return the resultset
     *
     *  @param	string	$query          SQL query string
     *  @param  int		$usesavepoint	0=Default mode, 1=Run a savepoint before and a rollbock to savepoint if error (this allow to have some request with errors inside global transactions).
     *                   		 		Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
     *  @param  string	$type           Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
     *  @return false|resource|true		Resultset of answer
	 */
	function query($query,$usesavepoint=0,$type='auto')
	{
		$query = trim($query);

		if (preg_match('/^--/',$query)) return true;

		// Conversion syntaxe MySql vers MSDE.
		$query = str_ireplace("now()", "getdate()", $query);
		// Erreur SQL: cannot update timestamp field
		$query = str_ireplace(", tms = tms", "", $query);

		$query=preg_replace("/([. ,\t(])(percent|file|public)([. ,=\t)])/","$1[$2]$3",$query);

		if ($type=="auto" || $type='dml')
		{
    		$query=preg_replace('/AUTO_INCREMENT/i','IDENTITY',$query);
    		$query=preg_replace('/double/i','float',$query);
    		$query=preg_replace('/float\((.*)\)/','numeric($1)',$query);
    		$query=preg_replace('/([ \t])unsigned|IF NOT EXISTS[ \t]/i','$1',$query);
    		$query=preg_replace('/([ \t])(MEDIUM|TINY|LONG){0,1}TEXT([ \t,])/i',"$1VARCHAR(MAX)$3",$query);

    		$matches=array();
    		$original_query='';
    		if (preg_match('/ALTER TABLE\h+(\w+?)\h+ADD\h+(?:(UNIQUE)|INDEX)\h+(?:INDEX)?\h*(\w+?)\h*\((.+)\)/is', $query,$matches))
    		{
                $original_query=$query;
                $query="CREATE ".trim($matches[2])." INDEX [".trim($matches[3])."] ON [".trim($matches[1])."] (".trim($matches[4]).")";
                if ($matches[2]) {
                    //check if columun is nullable cause Sql server only allow 1 null value if unique index.
                    $fields=explode(",",trim($matches[4]));
                    $fields_clear=array_map('trim',$fields);
                    $infos=$this->GetFieldInformation(trim($matches[1]), $fields_clear);
                    $query_comp=array();
                    foreach($infos as $fld) {
                        if ($fld->IS_NULLABLE == 'YES') {
                            $query_comp[]=$fld->COLUMN_NAME." IS NOT NULL";
                        }
                    }
                    if (! empty($query_comp))
                        $query.=" WHERE ".implode(" AND ",$query_comp);
                }
    		}
    		else
    		{
    		    if (preg_match('/ALTER TABLE\h+(\w+?)\h+ADD\h+PRIMARY\h+KEY\h+(\w+?)\h*\((.+)\)/is', $query, $matches))
    		    {
                    $original_query=$query;
                    $query="ALTER TABLE [".$matches[1]."] ADD CONSTRAINT [".$matches[2]."] PRIMARY KEY CLUSTERED (".$matches[3].")";
    		    }
    		}
		}

		if ($type=="auto" || $type='ddl')
		{
    		$itemfound = stripos($query, " limit ");
    		if ($itemfound !== false) {
    			// Extraire le nombre limite
    			$number = stristr($query, " limit ");
    			$number = substr($number, 7);
    			// Inserer l'instruction TOP et le nombre limite
    			$query = str_ireplace("select ", "select top ".$number." ", $query);
    			// Supprimer l'instruction MySql
    			$query = str_ireplace(" limit ".$number, "", $query);
    		}

    		$itemfound = stripos($query, " week(");
    		if ($itemfound !== false) {
    			// Recreer une requete sans instruction Mysql
    			$positionMySql = stripos($query, " week(");
    			$newquery = substr($query, 0, $positionMySql);

    			// Recuperer la date passee en parametre
    			$extractvalue = stristr($query, " week(");
    			$extractvalue = substr($extractvalue, 6);
    			$positionMySql = stripos($extractvalue, ")");
    			// Conserver la fin de la requete
    			$endofquery = substr($extractvalue, $positionMySql);
    			$extractvalue = substr($extractvalue, 0, $positionMySql);

    			// Remplacer l'instruction MySql en Sql Server
    			// Inserer la date en parametre et le reste de la requete
    			$query = $newquery." DATEPART(week, ".$extractvalue.$endofquery;
    		}
    	   if (preg_match('/^insert\h+(?:INTO)?\h*(\w+?)\h*\(.*\b(?:row)?id\b.*\)\h+VALUES/i',$query,$matches))
    	   {
    	       //var_dump($query);
    	       //var_dump($matches);
    	       //if (stripos($query,'llx_c_departements') !== false) var_dump($query);
    	       $sql='SET IDENTITY_INSERT ['.trim($matches[1]).'] ON;';
    	       @mssql_query($sql, $this->db);
    	       $post_query='SET IDENTITY_INSERT ['.trim($matches[1]).'] OFF;';
    	   }
		}
		//print "<!--".$query."-->";

		if (! in_array($query,array('BEGIN','COMMIT','ROLLBACK'))) dol_syslog('sql='.$query, LOG_DEBUG);

		if (! $this->database_name)
		{
			// Ordre SQL ne necessitant pas de connexion a une base (exemple: CREATE DATABASE)
			$ret = mssql_query($query, $this->db);
		}
		else
		{
			$ret = mssql_query($query, $this->db);
		}

		if (!empty($post_query))
		{
		    @mssql_query($post_query, $this->db);
		}

		if (! preg_match("/^COMMIT/i",$query) && ! preg_match("/^ROLLBACK/i",$query))
		{
			// Si requete utilisateur, on la sauvegarde ainsi que son resultset
			if (! $ret)
			{
				$result = mssql_query("SELECT @@ERROR as code", $this->db);
				$row = mssql_fetch_array($result);

                $this->lastqueryerror = $query;
				$this->lasterror = $this->error();
				$this->lasterrno = $row["code"];

				dol_syslog(get_class($this)."::query SQL Error query: ".$query, LOG_ERR);
				if ($original_query) dol_syslog(get_class($this)."::query SQL Original query: ".$original_query, LOG_ERR);
				dol_syslog(get_class($this)."::query SQL Error message: ".$this->lasterror." (".$this->lasterrno.")", LOG_ERR);
			}
			$this->lastquery=$query;
			$this->_results = $ret;
		}

		return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Renvoie la ligne courante (comme un objet) pour le curseur resultset
	 *
	 *	@param	resource	$resultset  Curseur de la requete voulue
	 *	@return	object|false			Object result line or false if KO or end of cursor
	 */
	function fetch_object($resultset)
	{
        // phpcs:enable
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return mssql_fetch_object($resultset);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
     *	Return datas as an array
     *
     *	@param	resource	$resultset  Resultset of request
     *	@return	array|false				Array or false if KO or end of cursor
	 */
	function fetch_array($resultset)
	{
        // phpcs:enable
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return mssql_fetch_array($resultset);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
     *	Return datas as an array
     *
     *	@param	resource	$resultset  Resultset of request
     *	@return	array|false				Array or false if KO or end of cursor
	 */
	function fetch_row($resultset)
	{
        // phpcs:enable
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return @mssql_fetch_row($resultset);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
     *	Return number of lines for result of a SELECT
     *
     *	@param	resource	$resultset  Resulset of requests
     *	@return int		    			Nb of lines
     *	@see    affected_rows
	 */
	function num_rows($resultset)
	{
        // phpcs:enable
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return mssql_num_rows($resultset);
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
	 *
	 *	@param	resource	$resultset   Curseur de la requete voulue
	 *	@return int		    Nombre de lignes
	 *	@see    num_rows
	 */
	function affected_rows($resultset)
	{
        // phpcs:enable
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		// mssql necessite un link de base pour cette fonction contrairement
		// a pqsql qui prend un resultset
		$rsRows = mssql_query("select @@rowcount as rows", $this->db);
		return mssql_result($rsRows, 0, "rows");
		//return mssql_affected_rows($this->db);
	}


	/**
	 *	Free last resultset used.
	 *
	 *	@param  resource	$resultset   Curseur de la requete voulue
	 *	@return	bool
	 */
	function free($resultset=null)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		// Si resultset en est un, on libere la memoire
		if (is_resource($resultset)) mssql_free_result($resultset);
	}

	/**
	 *	Escape a string to insert data
	 *
	 *  @param	string	$stringtoencode		String to escape
	 *  @return	string						String escaped
	 */
	function escape($stringtoencode)
	{
		return addslashes($stringtoencode);
	}


	/**
	 *   Convert (by PHP) a GM Timestamp date into a PHP server TZ to insert into a date field.
	 *   Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 *   @param	    string	$param      Date TMS to convert
	 *   @return	string      		Date in a string YYYY-MM-DD HH:MM:SS
	 */
	function idate($param)
	{
		return dol_print_date($param,"%Y-%m-%d %H:%M:%S");
	}

	/**
     *	Return generic error code of last operation.
     *
     *	@return	string		Error code (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
	 */
	function errno()
	{
		if (! $this->connected)
		{
			// Si il y a eu echec de connexion, $this->db n'est pas valide.
			return 'DB_ERROR_FAILED_TO_CONNECT';
		}
		else
		{
			// Constants to convert a MSSql error code to a generic Dolibarr error code
			$errorcode_map = array(
			1004 => 'DB_ERROR_CANNOT_CREATE',
			1005 => 'DB_ERROR_CANNOT_CREATE',
			1006 => 'DB_ERROR_CANNOT_CREATE',
			1007 => 'DB_ERROR_ALREADY_EXISTS',
			1008 => 'DB_ERROR_CANNOT_DROP',
			1025 => 'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
			1046 => 'DB_ERROR_NODBSELECTED',
			1048 => 'DB_ERROR_CONSTRAINT',
			2714 => 'DB_ERROR_TABLE_ALREADY_EXISTS',
			1051 => 'DB_ERROR_NOSUCHTABLE',
			1054 => 'DB_ERROR_NOSUCHFIELD',
			1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
			1061 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
			2627 => 'DB_ERROR_RECORD_ALREADY_EXISTS',
			102  => 'DB_ERROR_SYNTAX',
			8120 => 'DB_ERROR_GROUP_BY_SYNTAX',
			1068 => 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
			1075 => 'DB_ERROR_CANT_DROP_PRIMARY_KEY',
			1091 => 'DB_ERROR_NOSUCHFIELD',
			1100 => 'DB_ERROR_NOT_LOCKED',
			1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
			1146 => 'DB_ERROR_NOSUCHTABLE',
			1216 => 'DB_ERROR_NO_PARENT',
			1217 => 'DB_ERROR_CHILD_EXISTS',
			1451 => 'DB_ERROR_CHILD_EXISTS',
			1913 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS'
			);

			if (isset($errorcode_map[$this->lasterrno]))
			{
				return $errorcode_map[$this->lasterrno];
			}
			$errno=$this->lasterrno;
			return ($errno?'DB_ERROR_'.$errno:'0');
		}
	}

	/**
	 *	Return description of last error
	 *
	 *	@return	string		Error text
	 */
	function error()
	{
		if (! $this->connected) {
			// Si il y a eu echec de connexion, $this->db n'est pas valide pour mssql_get_last_message.
			return 'Not connected. Check setup parameters in conf/conf.php file and your mssql client and server versions';
		}
		else {
			return mssql_get_last_message();
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * Get last ID after an insert INSERT
	 *
	 * @param   string	$tab    	Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param	string	$fieldid	Field name
	 * @return  int     			Id of row or -1 on error
	 */
	function last_insert_id($tab,$fieldid='rowid')
	{
        // phpcs:enable
		$res = $this->query("SELECT @@IDENTITY as id");
		if ($res && $data = $this->fetch_array($res))
		{
			return $data["id"];
		}
		else
		{
			return -1;
		}
	}

	/**
     *  Encrypt sensitive data in database
     *  Warning: This function includes the escape, so it must use direct value
     *
     *  @param  string  $fieldorvalue   Field name or value to encrypt
     *  @param	int		$withQuotes     Return string with quotes
     *  @return string          		XXX(field) or XXX('value') or field or 'value'
	 */
	function encrypt($fieldorvalue, $withQuotes=0)
	{
		global $conf;

		// Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
		$cryptType = ($conf->db->dolibarr_main_db_encryption?$conf->db->dolibarr_main_db_encryption:0);

		//Encryption key
		$cryptKey = (!empty($conf->db->dolibarr_main_db_cryptkey)?$conf->db->dolibarr_main_db_cryptkey:'');

		$return = $fieldorvalue;
		return ($withQuotes?"'":"").$this->escape($return).($withQuotes?"'":"");
	}

	/**
     *	Decrypt sensitive data in database
     *
     *	@param	string	$value			Value to decrypt
     * 	@return	string					Decrypted value if used
	 */
	function decrypt($value)
	{
		global $conf;

		// Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
		$cryptType = ($conf->db->dolibarr_main_db_encryption?$conf->db->dolibarr_main_db_encryption:0);

		//Encryption key
		$cryptKey = (!empty($conf->db->dolibarr_main_db_cryptkey)?$conf->db->dolibarr_main_db_cryptkey:'');

		$return = $value;
		return $return;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * Return connexion ID
	 *
	 * @return	        string      Id connexion
	 */
	function DDLGetConnectId()
	{
        // phpcs:enable
		$resql=$this->query('SELECT CONNECTION_ID()');
		if ($resql)
		{
            $row=$this->fetch_row($resql);
            return $row[0];
		}
		else return '?';
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Create a new database
	 *	Do not use function xxx_create_db (xxx=mysql, ...) as they are deprecated
	 *	We force to create database with charset this->forcecharset and collate this->forcecollate
	 *
	 *	@param	string	$database		Database name to create
	 * 	@param	string	$charset		Charset used to store data
	 * 	@param	string	$collation		Charset used to sort data
	 * 	@param	string	$owner			Username of database owner
	 * 	@return	false|resource|true		resource defined if OK, false if KO
	 */
	function DDLCreateDb($database,$charset='',$collation='',$owner='')
	{
        // phpcs:enable
        /*if (empty($charset))   $charset=$this->forcecharset;
        if (empty($collation)) $collation=$this->forcecollate;
        */

		$sql = 'CREATE DATABASE '.$this->EscapeFieldName($database);
        //TODO: Check if we need to force a charset
		//$sql.= ' DEFAULT CHARACTER SET '.$charset.' DEFAULT COLLATE '.$collation;
		$ret=$this->query($sql);

		$this->select_db($database);
		$sql="CREATE USER [$owner] FOR LOGIN [$owner]";
		mssql_query($sql,$this->db);
		$sql="ALTER ROLE [db_owner] ADD MEMBER [$owner]";
		mssql_query($sql,$this->db);

		$sql="ALTER DATABASE [$database] SET ANSI_NULL_DEFAULT ON;";
	    @mssql_query($sql,$this->db);
	    $sql="ALTER DATABASE [$database] SET ANSI_NULL ON;";
	    @mssql_query($sql,$this->db);

	    return $ret;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *  List tables into a database
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Nmae of table filter ('xxx%')
     *  @return	array					List of tables in an array
	 */
	function DDLListTables($database,$table='')
	{
        // phpcs:enable
		$this->_results = mssql_list_tables($database, $this->db);
		return $this->_results;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	List information of columns into a table.
	 *
	 *	@param	string	$table		Name of table
	 *	@return	array				Tableau des informations des champs de la table
	 */
	function DDLInfoTable($table)
	{
        // phpcs:enable

		// FIXME: Dummy method
		// TODO: Implement
		// May help: https://stackoverflow.com/questions/600446/sql-server-how-do-you-return-the-column-names-from-a-table

		$infotables=array();
		return $infotables;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Create a table into database
	 *
	 *	@param	    string	$table 			Nom de la table
	 *	@param	    array	$fields 		Tableau associatif [nom champ][tableau des descriptions]
	 *	@param	    string	$primary_key 	Nom du champ qui sera la clef primaire
	 *	@param	    string	$type 			Type de la table
	 *	@param	    array	$unique_keys 	Tableau associatifs Nom de champs qui seront clef unique => valeur
	 *	@param	    array	$fulltext_keys	Tableau des Nom de champs qui seront indexes en fulltext
	 *	@param	    array	$keys 			Tableau des champs cles noms => valeur
	 *	@return	    int						<0 if KO, >=0 if OK
	 */
	function DDLCreateTable($table,$fields,$primary_key,$type,$unique_keys=null,$fulltext_keys=null,$keys=null)
	{
        // phpcs:enable
		// FIXME: $fulltext_keys parameter is unused

		// cles recherchees dans le tableau des descriptions (fields) : type,value,attribute,null,default,extra
		// ex. : $fields['rowid'] = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
		$sql = "create table ".$table."(";
		$i=0;
		foreach($fields as $field_name => $field_desc)
		{
			$sqlfields[$i] = $field_name." ";
			$sqlfields[$i]  .= $field_desc['type'];
			if( preg_match("/^[^\s]/i",$field_desc['value']))
			$sqlfields[$i]  .= "(".$field_desc['value'].")";
			else if( preg_match("/^[^\s]/i",$field_desc['attribute']))
			$sqlfields[$i]  .= " ".$field_desc['attribute'];
			else if( preg_match("/^[^\s]/i",$field_desc['default']))
			{
				if(preg_match("/null/i",$field_desc['default']))
				$sqlfields[$i]  .= " default ".$field_desc['default'];
				else
				$sqlfields[$i]  .= " default '".$field_desc['default']."'";
			}
			else if( preg_match("/^[^\s]/i",$field_desc['null']))
			$sqlfields[$i]  .= " ".$field_desc['null'];

			else if( preg_match("/^[^\s]/i",$field_desc['extra']))
			$sqlfields[$i]  .= " ".$field_desc['extra'];
			$i++;
		}
		if($primary_key != "")
		$pk = "primary key(".$primary_key.")";

		if(is_array($unique_keys))
		{
			$i = 0;
			foreach($unique_keys as $key => $value)
			{
				$sqluq[$i] = "UNIQUE KEY '".$key."' ('".$value."')";
				$i++;
			}
		}
		if(is_array($keys))
		{
			$i = 0;
			foreach($keys as $key => $value)
			{
				$sqlk[$i] = "KEY ".$key." (".$value.")";
				$i++;
			}
		}
		$sql .= implode(',',$sqlfields);
		if($primary_key != "")
		$sql .= ",".$pk;
		if(is_array($unique_keys))
		$sql .= ",".implode(',',$sqluq);
		if(is_array($keys))
		$sql .= ",".implode(',',$sqlk);
		$sql .=") type=".$type;

		dol_syslog($sql);
		if(! $this -> query($sql))
		return -1;
		else
		return 1;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Drop a table into database
	 *
	 *	@param	    string	$table 			Name of table
	 *	@return	    int						<0 if KO, >=0 if OK
	 */
	function DDLDropTable($table)
	{
        // phpcs:enable
		$sql = "DROP TABLE ".$table;

		if (! $this->query($sql))
			return -1;
		else
			return 1;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Return a pointer of line with description of a table or field
	 *
	 *	@param	string		$table	Name of table
	 *	@param	string		$field	Optionnel : Name of field if we want description of field
	 *	@return	false|resource|true	Resource
	 */
	function DDLDescTable($table,$field="")
	{
        // phpcs:enable
		$sql="DESC ".$table." ".$field;

		dol_syslog($sql);
		$this->_results = $this->query($sql);
		return $this->_results;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Create a new field into table
	 *
	 *	@param	string	$table 				Name of table
	 *	@param	string	$field_name 		Name of field to add
	 *	@param	string	$field_desc 		Tableau associatif de description du champ a inserer[nom du parametre][valeur du parametre]
	 *	@param	string	$field_position 	Optionnel ex.: "after champtruc"
	 *	@return	int							<0 if KO, >0 if OK
	 */
	function DDLAddField($table,$field_name,$field_desc,$field_position="")
	{
        // phpcs:enable
		// cles recherchees dans le tableau des descriptions (field_desc) : type,value,attribute,null,default,extra
		// ex. : $field_desc = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
		$sql= "ALTER TABLE ".$table." ADD ".$field_name." ";
		$sql .= $field_desc['type'];
		if( preg_match("/^[^\s]/i",$field_desc['value']))
		$sql  .= "(".$field_desc['value'].")";
		if( preg_match("/^[^\s]/i",$field_desc['attribute']))
		$sql  .= " ".$field_desc['attribute'];
		if( preg_match("/^[^\s]/i",$field_desc['null']))
		$sql  .= " ".$field_desc['null'];
		if( preg_match("/^[^\s]/i",$field_desc['default']))
		if(preg_match("/null/i",$field_desc['default']))
		$sql  .= " default ".$field_desc['default'];
		else
		$sql  .= " default '".$field_desc['default']."'";
		if( preg_match("/^[^\s]/i",$field_desc['extra']))
		$sql  .= " ".$field_desc['extra'];
		$sql .= " ".$field_position;

		if(! $this -> query($sql))
		return -1;
		else
		return 1;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Update format of a field into a table
	 *
	 *	@param	string	$table 				Name of table
	 *	@param	string	$field_name 		Name of field to modify
	 *	@param	string	$field_desc 		Array with description of field format
	 *	@return	int							<0 if KO, >0 if OK
	 */
	function DDLUpdateField($table,$field_name,$field_desc)
	{
        // phpcs:enable
		$sql = "ALTER TABLE ".$table;
		$sql .= " MODIFY COLUMN ".$field_name." ".$field_desc['type'];
		if ($field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int' || $field_desc['type'] == 'varchar') {
			$sql.="(".$field_desc['value'].")";
		}

		dol_syslog($sql,LOG_DEBUG);
		if (! $this->query($sql))
		return -1;
		else
		return 1;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *	Drop a field from table
	 *
	 *	@param	string	$table 			Name of table
	 *	@param	string	$field_name 	Name of field to drop
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function DDLDropField($table,$field_name)
	{
        // phpcs:enable
		$sql= "ALTER TABLE ".$table." DROP COLUMN `".$field_name."`";
		dol_syslog($sql,LOG_DEBUG);
		if (! $this->query($sql))
		{
			$this->error=$this->lasterror();
			return -1;
		}
		else return 1;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * 	Create a user and privileges to connect to database (even if database does not exists yet)
	 *
	 *	@param	string	$dolibarr_main_db_host 		Ip serveur
	 *	@param	string	$dolibarr_main_db_user 		Nom user a creer
	 *	@param	string	$dolibarr_main_db_pass 		Mot de passe user a creer
	 *	@param	string	$dolibarr_main_db_name		Database name where user must be granted
	 *	@return	int									<0 if KO, >=0 if OK
	 */
	function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name)
	{
        // phpcs:enable
        $sql = "CREATE LOGIN ".$this->EscapeFieldName($dolibarr_main_db_user)." WITH PASSWORD='$dolibarr_main_db_pass'";
        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
        $resql=$this->query($sql);
        if (! $resql)
        {
            if ($this->lasterrno != '15025')
            {
	            return -1;
            }
            else
			{
            	// If user already exists, we continue to set permissions
            	dol_syslog(get_class($this)."::DDLCreateUser sql=".$sql, LOG_WARNING);
            }
        }
        $sql="SELECT name from sys.databases where name='".$dolibarr_main_db_name."'";
        $ressql=$this->query($sql);
        if (! $ressql)
        {
            dol_syslog(get_class($this)."::DDLCreateUser sql=".$sql, LOG_WARNING);
            return -1;
        }
        else
        {
            if ($num)
            {
                $this->select_db($dolibarr_main_db_name);
                $sql="CREATE USER [$dolibarr_main_db_user] FOR LOGIN [$dolibarr_main_db_user]";
                $this->query($sql);
                $sql="ALTER ROLE [db_owner] ADD MEMBER [$dolibarr_main_db_user]";
                $this->query($sql);
            }
        }
	    return 1;
	}

    /**
     *	Return charset used to store data in database
     *
     *	@return		string		Charset
     */
    function getDefaultCharacterSetDatabase()
	{
		// FIXME: Dummy method
		// TODO: Implement

		return '';
	}

	/**
	 *	Return list of available charset that can be used to store data in database
	 *
	 *	@return		array		List of Charset
	 */
	function getListOfCharacterSet()
	{
		// FIXME: Dummy method
		// TODO: Implement

		return '';
	}

	/**
	 *	Return collation used in database
	 *
	 *	@return		string		Collation value
	 */
	function getDefaultCollationDatabase()
	{
		$resql=$this->query("SELECT SERVERPROPERTY('collation')");
		if (!$resql)
		{
			return $this->forcecollate;
		}
		$liste=$this->fetch_array($resql);
		return $liste['computed'];
	}

	/**
	 *	Return list of available collation that can be used for database
	 *
	 *	@return		array		Liste of Collation
	 */
	function getListOfCollation()
	{
		// FIXME: Dummy method
		// TODO: Implement

		return array();
	}

	/**
	 *	Return full path of dump program
	 *
	 *	@return		string		Full path of dump program
	 */
	function getPathOfDump()
	{
		// FIXME: Dummy method
		// TODO: Implement

	    return '';
	}

	/**
	 *	Return full path of restore program
	 *
	 *	@return		string		Full path of restore program
	 */
	function getPathOfRestore()
	{
		// FIXME: Dummy method
		// TODO: Implement

	    return '';
	}

	/**
	 * Return value of server parameters
	 *
	 * @param	string	$filter		Filter list on a particular value
	 * @return	array				Array of key-values (key=>value)
	 */
	function getServerParametersValues($filter='')
	{
		// FIXME: Dummy method
		// TODO: Implement
		// May help: SELECT SERVERPROPERTY

		$result=array();
		return $result;
	}

	/**
	 * Return value of server status
	 *
	 * @param	string	$filter		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
	function getServerStatusValues($filter='')
	{
		// FIXME: Dummy method
		// TODO: Implement
		// May help: http://www.experts-exchange.com/Database/MS-SQL-Server/Q_20971756.html

		return array();
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 *    Escape a field name according to escape's syntax
	 *
	 * @param      string $fieldname   Field's name to escape
	 * @return     string              field's name escaped
	 */
    function EscapeFieldName($fieldname)
    {
        // phpcs:enable
	    return "[".$fieldname."]";
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
	/**
	 * Get information on field
	 *
	 * @param      string  $table      Table name which contains fields
	 * @param      mixed   $fields     String for one field or array of string for multiple field
	 * @return false|object
	 */
    function GetFieldInformation($table,$fields)
    {
        // phpcs:enable
	    $sql="SELECT * from INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='".$this->escape($table)."' AND COLUMN_NAME";
	    if (is_array($fields))
	    {
	        $where=" IN ('".implode("','",$fields)."')";
	    }
	    else
	    {
	        $where="='".$this->escape($fields)."'";
	    }
	    $result=array();
	    $ret=mssql_query($sql.$where,$this->db);
	    if ($ret)
	    {
	        while($obj=mssql_fetch_object($ret))
	        {
	            $result[]=$obj;
	        }
	    }
	    else
	        return false;

	    return $result;
	}
}
