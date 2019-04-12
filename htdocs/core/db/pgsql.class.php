<?php
/* Copyright (C) 2001		Fabien Seisen			<seisen@linuxfr.org>
 * Copyright (C) 2002-2005	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2014	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2012		Yann Droneaud			<yann@droneaud.fr>
 * Copyright (C) 2012		Florian Henry			<florian.henry@open-concept.pro>
 * Copyright (C) 2015       Marcos García           <marcosgdf@gmail.com>
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
 *	\file       htdocs/core/db/pgsql.class.php
 *	\brief      Fichier de la classe permettant de gerer une base pgsql
 */

require_once DOL_DOCUMENT_ROOT .'/core/db/DoliDB.class.php';

/**
 *	Class to drive a Postgresql database for Dolibarr
 */
class DoliDBPgsql extends DoliDB
{
    //! Database type
	public $type='pgsql';            // Name of manager
    //! Database label
	const LABEL='PostgreSQL';      // Label of manager
	//! Charset
	var $forcecharset='UTF8';       // Can't be static as it may be forced with a dynamic value
    //! Collate used to force collate when creating database
    var $forcecollate='';			// Can't be static as it may be forced with a dynamic value
	//! Version min database
	const VERSIONMIN='9.0.0';	// Version min database
	/** @var resource Resultset of last query */
	private $_results;

	public $unescapeslashquot;
	public $standard_conforming_strings;

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
		global $conf,$langs;

        // Note that having "static" property for "$forcecharset" and "$forcecollate" will make error here in strict mode, so they are not static
		if (! empty($conf->db->character_set)) $this->forcecharset=$conf->db->character_set;
		if (! empty($conf->db->dolibarr_main_db_collation))	$this->forcecollate=$conf->db->dolibarr_main_db_collation;

		$this->database_user=$user;
        $this->database_host=$host;
        $this->database_port=$port;

		$this->transaction_opened=0;

		//print "Name DB: $host,$user,$pass,$name<br>";

		if (! function_exists("pg_connect"))
		{
			$this->connected = false;
			$this->ok = false;
			$this->error="Pgsql PHP functions are not available in this version of PHP";
			dol_syslog(get_class($this)."::DoliDBPgsql : Pgsql PHP functions are not available in this version of PHP",LOG_ERR);
			return $this->ok;
		}

		if (! $host)
		{
			$this->connected = false;
			$this->ok = false;
			$this->error=$langs->trans("ErrorWrongHostParameter");
			dol_syslog(get_class($this)."::DoliDBPgsql : Erreur Connect, wrong host parameters",LOG_ERR);
			return $this->ok;
		}

		// Essai connexion serveur
		//print "$host, $user, $pass, $name, $port";
		$this->db = $this->connect($host, $user, $pass, $name, $port);

		if ($this->db)
		{
			$this->connected = true;
			$this->ok = true;
		}
		else
		{
			// host, login ou password incorrect
			$this->connected = false;
			$this->ok = false;
			$this->error='Host, login or password incorrect';
			dol_syslog(get_class($this)."::DoliDBPgsql : Erreur Connect ".$this->error,LOG_ERR);
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
				dol_syslog(get_class($this)."::DoliDBPgsql : Erreur Select_db ".$this->error,LOG_ERR);
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
     *  @param  string	$line   			SQL request line to convert
     *  @param  string	$type				Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
     *  @param	bool	$unescapeslashquot	Unescape slash quote with quote quote
     *  @return string   					SQL request line converted
     */
	static function convertSQLFromMysql($line,$type='auto',$unescapeslashquot=false)
	{
		global $conf;
		// Removed empty line if this is a comment line for SVN tagging
		if (preg_match('/^--\s\$Id/i',$line)) {
			return '';
		}
		// Return line if this is a comment
		if (preg_match('/^#/i',$line) || preg_match('/^$/i',$line) || preg_match('/^--/i',$line))
		{
			return $line;
		}
		if ($line != "")
		{
			// group_concat support (PgSQL >= 9.0)
			// Replace group_concat(x) or group_concat(x SEPARATOR ',') with string_agg(x, ',')
		    $line = preg_replace('/GROUP_CONCAT/i', 'STRING_AGG', $line);
			$line = preg_replace('/ SEPARATOR/i', ',', $line);
			$line = preg_replace('/STRING_AGG\(([^,\)]+)\)/i', 'STRING_AGG(\\1, \',\')', $line);
			//print $line."\n";

		    if ($type == 'auto')
		    {
              if (preg_match('/ALTER TABLE/i',$line)) $type='dml';
              else if (preg_match('/CREATE TABLE/i',$line)) $type='dml';
              else if (preg_match('/DROP TABLE/i',$line)) $type='dml';
		    }

    		$line=preg_replace('/ as signed\)/i',' as integer)',$line);

		    if ($type == 'dml')
		    {
                $line=preg_replace('/\s/',' ',$line);   // Replace tabulation with space

		        // we are inside create table statement so lets process datatypes
    			if (preg_match('/(ISAM|innodb)/i',$line)) { // end of create table sequence
    				$line=preg_replace('/\)[\s\t]*type[\s\t]*=[\s\t]*(MyISAM|innodb).*;/i',');',$line);
    				$line=preg_replace('/\)[\s\t]*engine[\s\t]*=[\s\t]*(MyISAM|innodb).*;/i',');',$line);
    				$line=preg_replace('/,$/','',$line);
    			}

    			// Process case: "CREATE TABLE llx_mytable(rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,code..."
    			if (preg_match('/[\s\t\(]*(\w*)[\s\t]+int.*auto_increment/i',$line,$reg)) {
    				$newline=preg_replace('/([\s\t\(]*)([a-zA-Z_0-9]*)[\s\t]+int.*auto_increment[^,]*/i','\\1 \\2 SERIAL PRIMARY KEY',$line);
                    //$line = "-- ".$line." replaced by --\n".$newline;
                    $line=$newline;
    			}

    			// tinyint type conversion
    			$line=preg_replace('/tinyint\(?[0-9]*\)?/','smallint',$line);
    			$line=preg_replace('/tinyint/i','smallint',$line);

    			// nuke unsigned
    			$line=preg_replace('/(int\w+|smallint)\s+unsigned/i','\\1',$line);

    			// blob -> text
    			$line=preg_replace('/\w*blob/i','text',$line);

    			// tinytext/mediumtext -> text
    			$line=preg_replace('/tinytext/i','text',$line);
    			$line=preg_replace('/mediumtext/i','text',$line);
    			$line=preg_replace('/longtext/i','text',$line);

    			$line=preg_replace('/text\([0-9]+\)/i','text',$line);

    			// change not null datetime field to null valid ones
    			// (to support remapping of "zero time" to null
    			$line=preg_replace('/datetime not null/i','datetime',$line);
    			$line=preg_replace('/datetime/i','timestamp',$line);

    			// double -> numeric
    			$line=preg_replace('/^double/i','numeric',$line);
    			$line=preg_replace('/(\s*)double/i','\\1numeric',$line);
    			// float -> numeric
    			$line=preg_replace('/^float/i','numeric',$line);
    			$line=preg_replace('/(\s*)float/i','\\1numeric',$line);

    			//Check tms timestamp field case (in Mysql this field is defautled to now and
    			// on update defaulted by now
    			$line=preg_replace('/(\s*)tms(\s*)timestamp/i','\\1tms timestamp without time zone DEFAULT now() NOT NULL',$line);

    			// nuke ON UPDATE CURRENT_TIMESTAMP
    			$line=preg_replace('/(\s*)on(\s*)update(\s*)CURRENT_TIMESTAMP/i','\\1',$line);

    			// unique index(field1,field2)
    			if (preg_match('/unique index\s*\((\w+\s*,\s*\w+)\)/i',$line))
    			{
    				$line=preg_replace('/unique index\s*\((\w+\s*,\s*\w+)\)/i','UNIQUE\(\\1\)',$line);
    			}

    			// We remove end of requests "AFTER fieldxxx"
    			$line=preg_replace('/\sAFTER [a-z0-9_]+/i','',$line);

    			// We remove start of requests "ALTER TABLE tablexxx" if this is a DROP INDEX
    			$line=preg_replace('/ALTER TABLE [a-z0-9_]+\s+DROP INDEX/i','DROP INDEX',$line);

                // Translate order to rename fields
                if (preg_match('/ALTER TABLE ([a-z0-9_]+)\s+CHANGE(?: COLUMN)? ([a-z0-9_]+) ([a-z0-9_]+)(.*)$/i',$line,$reg))
                {
                	$line = "-- ".$line." replaced by --\n";
                    $line.= "ALTER TABLE ".$reg[1]." RENAME COLUMN ".$reg[2]." TO ".$reg[3];
                }

                // Translate order to modify field format
                if (preg_match('/ALTER TABLE ([a-z0-9_]+)\s+MODIFY(?: COLUMN)? ([a-z0-9_]+) (.*)$/i',$line,$reg))
                {
                    $line = "-- ".$line." replaced by --\n";
                    $newreg3=$reg[3];
                    $newreg3=preg_replace('/ DEFAULT NULL/i','',$newreg3);
                    $newreg3=preg_replace('/ NOT NULL/i','',$newreg3);
                    $newreg3=preg_replace('/ NULL/i','',$newreg3);
                    $newreg3=preg_replace('/ DEFAULT 0/i','',$newreg3);
                    $newreg3=preg_replace('/ DEFAULT \'?[0-9a-zA-Z_@]*\'?/i','',$newreg3);
                    $line.= "ALTER TABLE ".$reg[1]." ALTER COLUMN ".$reg[2]." TYPE ".$newreg3;
                    // TODO Add alter to set default value or null/not null if there is this in $reg[3]
                }

                // alter table add primary key (field1, field2 ...) -> We remove the primary key name not accepted by PostGreSQL
    			// ALTER TABLE llx_dolibarr_modules ADD PRIMARY KEY pk_dolibarr_modules (numero, entity)
    			if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+PRIMARY\s+KEY\s*(.*)\s*\((.*)$/i',$line,$reg))
    			{
    				$line = "-- ".$line." replaced by --\n";
    				$line.= "ALTER TABLE ".$reg[1]." ADD PRIMARY KEY (".$reg[3];
    			}

                // Translate order to drop primary keys
                // ALTER TABLE llx_dolibarr_modules DROP PRIMARY KEY pk_xxx
                if (preg_match('/ALTER\s+TABLE\s*(.*)\s*DROP\s+PRIMARY\s+KEY\s*([^;]+)$/i',$line,$reg))
                {
                    $line = "-- ".$line." replaced by --\n";
                    $line.= "ALTER TABLE ".$reg[1]." DROP CONSTRAINT ".$reg[2];
                }

		        // Translate order to drop foreign keys
                // ALTER TABLE llx_dolibarr_modules DROP FOREIGN KEY fk_xxx
                if (preg_match('/ALTER\s+TABLE\s*(.*)\s*DROP\s+FOREIGN\s+KEY\s*(.*)$/i',$line,$reg))
                {
                    $line = "-- ".$line." replaced by --\n";
                    $line.= "ALTER TABLE ".$reg[1]." DROP CONSTRAINT ".$reg[2];
                }

                // Translate order to add foreign keys
                // ALTER TABLE llx_tablechild ADD CONSTRAINT fk_tablechild_fk_fieldparent FOREIGN KEY (fk_fieldparent) REFERENCES llx_tableparent (rowid)
                if (preg_match('/ALTER\s+TABLE\s+(.*)\s*ADD CONSTRAINT\s+(.*)\s*FOREIGN\s+KEY\s*(.*)$/i',$line,$reg))
                {
                    $line=preg_replace('/;$/','',$line);
                    $line.=" DEFERRABLE INITIALLY IMMEDIATE;";
                }

                // alter table add [unique] [index] (field1, field2 ...)
    			// ALTER TABLE llx_accountingaccount ADD INDEX idx_accountingaccount_fk_pcg_version (fk_pcg_version)
    			if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+(UNIQUE INDEX|INDEX|UNIQUE)\s+(.*)\s*\(([\w,\s]+)\)/i',$line,$reg))
    			{
    				$fieldlist=$reg[4];
    				$idxname=$reg[3];
    				$tablename=$reg[1];
    				$line = "-- ".$line." replaced by --\n";
    				$line.= "CREATE ".(preg_match('/UNIQUE/',$reg[2])?'UNIQUE ':'')."INDEX ".$idxname." ON ".$tablename." (".$fieldlist.")";
    			}
            }

            // To have postgresql case sensitive
			$count_like=0;
			$line=str_replace(' LIKE \'',' ILIKE \'',$line,$count_like);
			if (!empty($conf->global->PSQL_USE_UNACCENT) && $count_like > 0)
			{
				// @see https://docs.postgresql.fr/11/unaccent.html : 'unaccent()' function must be installed before
				$line=preg_replace('/\s+(\(+\s*)([a-zA-Z0-9\-\_\.]+) ILIKE /', ' \1unaccent(\2) ILIKE ', $line);
			}

            $line=str_replace(' LIKE BINARY \'',' LIKE \'',$line);

            // Replace INSERT IGNORE into INSERT
            $line=preg_replace('/^INSERT IGNORE/','INSERT',$line);

			// Delete using criteria on other table must not declare twice the deleted table
			// DELETE FROM tabletodelete USING tabletodelete, othertable -> DELETE FROM tabletodelete USING othertable
			if (preg_match('/DELETE FROM ([a-z_]+) USING ([a-z_]+), ([a-z_]+)/i',$line,$reg))
			{
				if ($reg[1] == $reg[2])	// If same table, we remove second one
				{
					$line=preg_replace('/DELETE FROM ([a-z_]+) USING ([a-z_]+), ([a-z_]+)/i','DELETE FROM \\1 USING \\3', $line);
				}
			}

			// Remove () in the tables in FROM if 1 table
			$line=preg_replace('/FROM\s*\((([a-z_]+)\s+as\s+([a-z_]+)\s*)\)/i','FROM \\1',$line);
			//print $line."\n";

			// Remove () in the tables in FROM if 2 table
			$line=preg_replace('/FROM\s*\(([a-z_]+\s+as\s+[a-z_]+)\s*,\s*([a-z_]+\s+as\s+[a-z_]+\s*)\)/i','FROM \\1, \\2',$line);
			//print $line."\n";

			// Remove () in the tables in FROM if 3 table
			$line=preg_replace('/FROM\s*\(([a-z_]+\s+as\s+[a-z_]+)\s*,\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*)\)/i','FROM \\1, \\2, \\3',$line);
			//print $line."\n";

			// Remove () in the tables in FROM if 4 table
			$line=preg_replace('/FROM\s*\(([a-z_]+\s+as\s+[a-z_]+)\s*,\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*)\)/i','FROM \\1, \\2, \\3, \\4',$line);
			//print $line."\n";

			// Remove () in the tables in FROM if 5 table
			$line=preg_replace('/FROM\s*\(([a-z_]+\s+as\s+[a-z_]+)\s*,\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*)\)/i','FROM \\1, \\2, \\3, \\4, \\5',$line);
			//print $line."\n";

			// Replace espacing \' by ''.
			// By default we do not (should be already done by db->escape function if required
			// except for sql insert in data file that are mysql escaped so we removed them to
			// be compatible with standard_conforming_strings=on that considers \ as ordinary character).
			if ($unescapeslashquot) $line=preg_replace("/\\\'/","''",$line);

			//print "type=".$type." newline=".$line."<br>\n";
		}

		return $line;
	}

	/**
	 *	Select a database
     *  Ici postgresql n'a aucune fonction equivalente de mysql_select_db
     *  On compare juste manuellement si la database choisie est bien celle activee par la connexion
	 *
	 *	@param	    string	$database	Name of database
	 *	@return	    bool				true if OK, false if KO
	 */
	function select_db($database)
	{
		if ($database == $this->database_name) return true;
		else return false;
	}

	/**
	 *	Connexion to server
	 *
	 *	@param	    string		$host		Database server host
	 *	@param	    string		$login		Login
	 *	@param	    string		$passwd		Password
	 *	@param		string		$name		Name of database (not used for mysql, used for pgsql)
	 *	@param		integer		$port		Port of database server
	 *	@return		false|resource			Database access handler
	 *	@see		close
	 */
	function connect($host, $login, $passwd, $name, $port=0)
	{
		// use pg_pconnect() instead of pg_connect() if you want to use persistent connection costing 1ms, instead of 30ms for non persistent

		$this->db = false;

		// connections parameters must be protected (only \ and ' according to pg_connect() manual)
		$host = str_replace(array("\\", "'"), array("\\\\", "\\'"), $host);
		$login = str_replace(array("\\", "'"), array("\\\\", "\\'"), $login);
		$passwd = str_replace(array("\\", "'"), array("\\\\", "\\'"), $passwd);
		$name = str_replace(array("\\", "'"), array("\\\\", "\\'"), $name);
		$port = str_replace(array("\\", "'"), array("\\\\", "\\'"), $port);

		if (! $name) $name="postgres";    // When try to connect using admin user

		// try first Unix domain socket (local)
		if ((! empty($host) && $host == "socket") && ! defined('NOLOCALSOCKETPGCONNECT'))
		{
			$con_string = "dbname='".$name."' user='".$login."' password='".$passwd."'";    // $name may be empty
			$this->db = @pg_connect($con_string);
		}

		// if local connection failed or not requested, use TCP/IP
		if (! $this->db)
		{
		    if (! $host) $host = "localhost";
			if (! $port) $port = 5432;

			$con_string = "host='".$host."' port='".$port."' dbname='".$name."' user='".$login."' password='".$passwd."'";
			$this->db = @pg_connect($con_string);
		}

		// now we test if at least one connect method was a success
		if ($this->db)
		{
			$this->database_name = $name;
			pg_set_error_verbosity($this->db, PGSQL_ERRORS_VERBOSE);	// Set verbosity to max
			pg_query($this->db, "set datestyle = 'ISO, YMD';");
		}

		return $this->db;
	}

	/**
	 *	Return version of database server
	 *
	 *	@return	        string      Version string
	 */
	function getVersion()
	{
		$resql=$this->query('SHOW server_version');
		if ($resql)
		{
		  $liste=$this->fetch_array($resql);
		  return $liste['server_version'];
		}
		return '';
	}

	/**
	 *	Return version of database client driver
	 *
	 *	@return	        string      Version string
	 */
	function getDriverInfo()
	{
		return 'pgsql php driver';
	}

    /**
     *  Close database connexion
     *
     *  @return     boolean     True if disconnect successfull, false otherwise
     *  @see        connect
     */
    function close()
    {
        if ($this->db)
        {
          if ($this->transaction_opened > 0) dol_syslog(get_class($this)."::close Closing a connection with an opened transaction depth=".$this->transaction_opened,LOG_ERR);
          $this->connected=false;
          return pg_close($this->db);
        }
        return false;
    }

	/**
	 * Convert request to PostgreSQL syntax, execute it and return the resultset
	 *
	 * @param	string	$query			SQL query string
	 * @param	int		$usesavepoint	0=Default mode, 1=Run a savepoint before and a rollback to savepoint if error (this allow to have some request with errors inside global transactions).
     * @param   string	$type           Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return	false|resource			Resultset of answer
	 */
	function query($query,$usesavepoint=0,$type='auto')
	{
		global $conf;

		$query = trim($query);

		// Convert MySQL syntax to PostgresSQL syntax
		$query=$this->convertSQLFromMysql($query,$type,($this->unescapeslashquot && $this->standard_conforming_strings));
		//print "After convertSQLFromMysql:\n".$query."<br>\n";

		if (! empty($conf->global->MAIN_DB_AUTOFIX_BAD_SQL_REQUEST))
		{
			// Fix bad formed requests. If request contains a date without quotes, we fix this but this should not occurs.
			$loop=true;
			while ($loop)
			{
				if (preg_match('/([^\'])([0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9])/',$query))
				{
					$query=preg_replace('/([^\'])([0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9] [0-9][0-9]:[0-9][0-9]:[0-9][0-9])/','\\1\'\\2\'',$query);
					dol_syslog("Warning: Bad formed request converted into ".$query,LOG_WARNING);
				}
				else $loop=false;
			}
		}

		if ($usesavepoint && $this->transaction_opened)
		{
			@pg_query($this->db, 'SAVEPOINT mysavepoint');
		}

		if (! in_array($query,array('BEGIN','COMMIT','ROLLBACK'))) dol_syslog('sql='.$query, LOG_DEBUG);

		$ret = @pg_query($this->db, $query);

		//print $query;
		if (! preg_match("/^COMMIT/i",$query) && ! preg_match("/^ROLLBACK/i",$query)) // Si requete utilisateur, on la sauvegarde ainsi que son resultset
		{
			if (! $ret)
			{
			    if ($this->errno() != 'DB_ERROR_25P02')	// Do not overwrite errors if this is a consecutive error
			    {
    				$this->lastqueryerror = $query;
    				$this->lasterror = $this->error();
    				$this->lasterrno = $this->errno();

    				if ($conf->global->SYSLOG_LEVEL < LOG_DEBUG) dol_syslog(get_class($this)."::query SQL Error query: ".$query, LOG_ERR);	// Log of request was not yet done previously
					dol_syslog(get_class($this)."::query SQL Error message: ".$this->lasterror." (".$this->lasterrno.")", LOG_ERR);
					dol_syslog(get_class($this)."::query SQL Error usesavepoint = ".$usesavepoint, LOG_ERR);
			    }

				if ($usesavepoint && $this->transaction_opened)	// Warning, after that errno will be erased
				{
					@pg_query($this->db, 'ROLLBACK TO SAVEPOINT mysavepoint');
				}
			}
			$this->lastquery=$query;
			$this->_results = $ret;
		}

		return $ret;
	}

	/**
	 *	Renvoie la ligne courante (comme un objet) pour le curseur resultset
	 *
	 *	@param	resource	$resultset  Curseur de la requete voulue
	 *	@return	false|object			Object result line or false if KO or end of cursor
	 */
	function fetch_object($resultset)
	{
        // If resultset not provided, we take the last used by connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return pg_fetch_object($resultset);
	}

	/**
     *	Return datas as an array
     *
     *	@param	resource	$resultset  Resultset of request
     *	@return	false|array				Array
	 */
	function fetch_array($resultset)
	{
        // If resultset not provided, we take the last used by connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return pg_fetch_array($resultset);
	}

	/**
     *	Return datas as an array
     *
     *	@param	resource	$resultset  Resultset of request
     *	@return	false|array				Array
	 */
	function fetch_row($resultset)
	{
		// Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return pg_fetch_row($resultset);
	}

	/**
     *	Return number of lines for result of a SELECT
     *
     *	@param	resourse	$resultset  Resulset of requests
     *	@return int		    			Nb of lines, -1 on error
     *	@see    affected_rows
	 */
	function num_rows($resultset)
	{
        // If resultset not provided, we take the last used by connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		return pg_num_rows($resultset);
	}

	/**
	 * Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
	 *
	 * @param	resource	$resultset  Result set of request
	 * @return  int		    			Nb of lines
	 * @see 	num_rows
	 */
	function affected_rows($resultset)
	{
        // If resultset not provided, we take the last used by connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		// pgsql necessite un resultset pour cette fonction contrairement
		// a mysql qui prend un link de base
		return pg_affected_rows($resultset);
	}


	/**
	 * Libere le dernier resultset utilise sur cette connexion
	 *
	 * @param	resource	$resultset  Result set of request
	 * @return	void
	 */
	function free($resultset=null)
	{
        // If resultset not provided, we take the last used by connexion
		if (! is_resource($resultset)) { $resultset=$this->_results; }
		// Si resultset en est un, on libere la memoire
		if (is_resource($resultset)) pg_free_result($resultset);
	}


	/**
     *	Define limits and offset of request
     *
     *	@param	int		$limit      Maximum number of lines returned (-1=conf->liste_limit, 0=no limit)
     *	@param	int		$offset     Numero of line from where starting fetch
     *	@return	string      		String with SQL syntax to add a limit and offset
	 */
	function plimit($limit=0,$offset=0)
	{
		global $conf;
        if (empty($limit)) return "";
		if ($limit < 0) $limit=$conf->liste_limit;
		if ($offset > 0) return " LIMIT ".$limit." OFFSET ".$offset." ";
		else return " LIMIT $limit ";
	}


	/**
	 *   Escape a string to insert data
	 *
	 *   @param		string	$stringtoencode		String to escape
	 *   @return	string						String escaped
	 */
	function escape($stringtoencode)
	{
		return pg_escape_string($stringtoencode);
	}

    /**
	 *   Convert (by PHP) a GM Timestamp date into a GM string date to insert into a date field.
	 *   Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 *   @param	    string	$param      Date TMS to convert
	 *   @return	string   			Date in a string YYYY-MM-DD HH:MM:SS
	 */
	function idate($param)
	{
		return dol_print_date($param,"%Y-%m-%d %H:%M:%S");
	}

	/**
     *  Format a SQL IF
     *
	 *  @param	string	$test           Test string (example: 'cd.statut=0', 'field IS NULL')
	 *  @param	string	$resok          resultat si test egal
	 *  @param	string	$resko          resultat si test non egal
	 *  @return	string          		chaine formate SQL
	 */
	function ifsql($test,$resok,$resko)
	{
		return '(CASE WHEN '.$test.' THEN '.$resok.' ELSE '.$resko.' END)';
	}

	/**
	 * Renvoie le code erreur generique de l'operation precedente.
	 *
	 * @return	string		Error code (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
	 */
	function errno()
	{
		if (! $this->connected) {
			// Si il y a eu echec de connexion, $this->db n'est pas valide.
			return 'DB_ERROR_FAILED_TO_CONNECT';
		}
		else {
			// Constants to convert error code to a generic Dolibarr error code
			$errorcode_map = array(
			1004 => 'DB_ERROR_CANNOT_CREATE',
			1005 => 'DB_ERROR_CANNOT_CREATE',
			1006 => 'DB_ERROR_CANNOT_CREATE',
			1007 => 'DB_ERROR_ALREADY_EXISTS',
			1008 => 'DB_ERROR_CANNOT_DROP',
			1025 => 'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
			1044 => 'DB_ERROR_ACCESSDENIED',
			1046 => 'DB_ERROR_NODBSELECTED',
			1048 => 'DB_ERROR_CONSTRAINT',
			'42P07' => 'DB_ERROR_TABLE_OR_KEY_ALREADY_EXISTS',
			'42703' => 'DB_ERROR_NOSUCHFIELD',
			1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
			42701=> 'DB_ERROR_COLUMN_ALREADY_EXISTS',
			'42710' => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
			'23505' => 'DB_ERROR_RECORD_ALREADY_EXISTS',
			'42704' => 'DB_ERROR_NO_INDEX_TO_DROP',		// May also be Type xxx does not exists
			'42601' => 'DB_ERROR_SYNTAX',
			'42P16' => 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
			1075 => 'DB_ERROR_CANT_DROP_PRIMARY_KEY',
			1091 => 'DB_ERROR_NOSUCHFIELD',
			1100 => 'DB_ERROR_NOT_LOCKED',
			1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
			'42P01' => 'DB_ERROR_NOSUCHTABLE',
			'23503' => 'DB_ERROR_NO_PARENT',
			1217 => 'DB_ERROR_CHILD_EXISTS',
			1451 => 'DB_ERROR_CHILD_EXISTS',
			'42P04' => 'DB_DATABASE_ALREADY_EXISTS'
			);

			$errorlabel=pg_last_error($this->db);
			$errorcode='';
			if (preg_match('/: *([0-9P]+):/',$errorlabel,$reg))
			{
				$errorcode=$reg[1];
				if (isset($errorcode_map[$errorcode]))
				{
					return $errorcode_map[$errorcode];
				}
			}
			$errno=$errorcode?$errorcode:$errorlabel;
			return ($errno?'DB_ERROR_'.$errno:'0');
		}
		//                '/(Table does not exist\.|Relation [\"\'].*[\"\'] does not exist|sequence does not exist|class ".+" not found)$/' => 'DB_ERROR_NOSUCHTABLE',
		//                '/table [\"\'].*[\"\'] does not exist/' => 'DB_ERROR_NOSUCHTABLE',
		//                '/Relation [\"\'].*[\"\'] already exists|Cannot insert a duplicate key into (a )?unique index.*/'      => 'DB_ERROR_RECORD_ALREADY_EXISTS',
		//                '/divide by zero$/'                     => 'DB_ERROR_DIVZERO',
		//                '/pg_atoi: error in .*: can\'t parse /' => 'DB_ERROR_INVALID_NUMBER',
		//                '/ttribute [\"\'].*[\"\'] not found$|Relation [\"\'].*[\"\'] does not have attribute [\"\'].*[\"\']/' => 'DB_ERROR_NOSUCHFIELD',
		//                '/parser: parse error at or near \"/'   => 'DB_ERROR_SYNTAX',
		//                '/referential integrity violation/'     => 'DB_ERROR_CONSTRAINT'
	}

	/**
	 * Renvoie le texte de l'erreur pgsql de l'operation precedente
	 *
	 * @return	string		Error text
	 */
	function error()
	{
		return pg_last_error($this->db);
	}

	/**
	 * Get last ID after an insert INSERT
	 *
	 * @param   string	$tab    	Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param	string	$fieldid	Field name
	 * @return  string     			Id of row
	 */
	function last_insert_id($tab,$fieldid='rowid')
	{
		//$result = pg_query($this->db,"SELECT MAX(".$fieldid.") FROM ".$tab);
		$result = pg_query($this->db,"SELECT currval('".$tab."_".$fieldid."_seq')");
		if (! $result)
		{
			print pg_last_error($this->db);
			exit;
		}
		//$nbre = pg_num_rows($result);
		$row = pg_fetch_result($result,0,0);
		return $row;
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
	 *	@param	int		$value			Value to decrypt
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


	/**
	 * Return connexion ID
	 *
	 * @return	        string      Id connexion
	 */
	function DDLGetConnectId()
	{
		return '?';
	}



	/**
	 *	Create a new database
	 *	Do not use function xxx_create_db (xxx=mysql, ...) as they are deprecated
	 *	We force to create database with charset this->forcecharset and collate this->forcecollate
	 *
	 *	@param	string	$database		Database name to create
	 * 	@param	string	$charset		Charset used to store data
	 * 	@param	string	$collation		Charset used to sort data
	 * 	@param	string	$owner			Username of database owner
	 * 	@return	false|resource				resource defined if OK, null if KO
	 */
	function DDLCreateDb($database,$charset='',$collation='',$owner='')
	{
	    if (empty($charset))   $charset=$this->forcecharset;
		if (empty($collation)) $collation=$this->forcecollate;

		// Test charset match LC_TYPE (pgsql error otherwise)
		//print $charset.' '.setlocale(LC_CTYPE,'0'); exit;

		$sql='CREATE DATABASE "'.$database.'" OWNER "'.$owner.'" ENCODING \''.$charset.'\'';
		dol_syslog($sql,LOG_DEBUG);
		$ret=$this->query($sql);
		return $ret;
	}

	/**
	 *  List tables into a database
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Name of table filter ('xxx%')
	 *  @return	array					List of tables in an array
	 */
	function DDLListTables($database, $table='')
	{
		$listtables=array();

		$like = '';
		if ($table) $like = " AND table_name LIKE '".$table."'";
		$result = pg_query($this->db, "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'".$like." ORDER BY table_name");
        if ($result)
        {
    		while($row = $this->fetch_row($result))
    		{
    			$listtables[] = $row[0];
    		}
        }
		return $listtables;
	}

	/**
	 *	List information of columns into a table.
	 *
	 *	@param	string	$table		Name of table
	 *	@return	array				Tableau des informations des champs de la table
	 *
	 */
	function DDLInfoTable($table)
	{
		$infotables=array();

		$sql="SELECT ";
		$sql.="	infcol.column_name as \"Column\",";
		$sql.="	CASE WHEN infcol.character_maximum_length IS NOT NULL THEN infcol.udt_name || '('||infcol.character_maximum_length||')'";
		$sql.="		ELSE infcol.udt_name";
		$sql.="	END as \"Type\",";
		$sql.="	infcol.collation_name as \"Collation\",";
		$sql.="	infcol.is_nullable as \"Null\",";
		$sql.="	'' as \"Key\",";
		$sql.="	infcol.column_default as \"Default\",";
		$sql.="	'' as \"Extra\",";
		$sql.="	'' as \"Privileges\"";
		$sql.="	FROM information_schema.columns infcol";
		$sql.="	WHERE table_schema='public' ";
		$sql.="	AND table_name='".$table."'";
		$sql.="	ORDER BY ordinal_position;";

		dol_syslog($sql,LOG_DEBUG);
		$result = $this->query($sql);
		if ($result)
		{
    		 while($row = $this->fetch_row($result))
    		 {
    			$infotables[] = $row;
    		 }
		}
        return $infotables;
	}


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

		dol_syslog($sql,LOG_DEBUG);
		if(! $this->query($sql))
		return -1;
		else
		return 1;
	}

	/**
	 *	Drop a table into database
	 *
	 *	@param	    string	$table 			Name of table
	 *	@return	    int						<0 if KO, >=0 if OK
	 */
	function DDLDropTable($table)
	{
		$sql = "DROP TABLE ".$table;

		if (! $this->query($sql))
			return -1;
		else
			return 1;
	}

	/**
	 * 	Create a user to connect to database
	 *
	 *	@param	string	$dolibarr_main_db_host 		Ip server
	 *	@param	string	$dolibarr_main_db_user 		Name of user to create
	 *	@param	string	$dolibarr_main_db_pass 		Password of user to create
	 *	@param	string	$dolibarr_main_db_name		Database name where user must be granted
	 *	@return	int									<0 if KO, >=0 if OK
	 */
	function DDLCreateUser($dolibarr_main_db_host,$dolibarr_main_db_user,$dolibarr_main_db_pass,$dolibarr_main_db_name)
	{
		// Note: using ' on user does not works with pgsql
		$sql = "CREATE USER ".$this->escape($dolibarr_main_db_user)." with password '".$this->escape($dolibarr_main_db_pass)."'";

		dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
		$resql=$this->query($sql);
		if (! $resql)
		{
			return -1;
		}

		return 1;
	}

	/**
	 *	Return a pointer of line with description of a table or field
	 *
	 *	@param	string		$table	Name of table
	 *	@param	string		$field	Optionnel : Name of field if we want description of field
	 *	@return	false|resource		Resultset x (x->attname)
	 */
	function DDLDescTable($table,$field="")
	{
		$sql ="SELECT attname FROM pg_attribute, pg_type WHERE typname = '".$table."' AND attrelid = typrelid";
		$sql.=" AND attname NOT IN ('cmin', 'cmax', 'ctid', 'oid', 'tableoid', 'xmin', 'xmax')";
		if ($field) $sql.= " AND attname = '".$field."'";

		dol_syslog($sql,LOG_DEBUG);
		$this->_results = $this->query($sql);
		return $this->_results;
	}

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
		// cles recherchees dans le tableau des descriptions (field_desc) : type,value,attribute,null,default,extra
		// ex. : $field_desc = array('type'=>'int','value'=>'11','null'=>'not null','extra'=> 'auto_increment');
		$sql= "ALTER TABLE ".$table." ADD ".$field_name." ";
		$sql .= $field_desc['type'];
		if(preg_match("/^[^\s]/i",$field_desc['value']))
		    if (! in_array($field_desc['type'],array('int','date','datetime')))
		    {
		        $sql.= "(".$field_desc['value'].")";
		    }
		if (preg_match("/^[^\s]/i",$field_desc['attribute']))
		$sql .= " ".$field_desc['attribute'];
		if (preg_match("/^[^\s]/i",$field_desc['null']))
		$sql .= " ".$field_desc['null'];
		if (preg_match("/^[^\s]/i",$field_desc['default']))
		if (preg_match("/null/i",$field_desc['default']))
		$sql .= " default ".$field_desc['default'];
		else
		$sql .= " default '".$field_desc['default']."'";
		if (preg_match("/^[^\s]/i",$field_desc['extra']))
		$sql .= " ".$field_desc['extra'];
		$sql .= " ".$field_position;

		dol_syslog($sql,LOG_DEBUG);
		if(! $this -> query($sql))
			return -1;
		return 1;
	}

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
		$sql = "ALTER TABLE ".$table;
		$sql .= " MODIFY COLUMN ".$field_name." ".$field_desc['type'];
		if ($field_desc['type'] == 'double' || $field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int' || $field_desc['type'] == 'varchar') {
			$sql.="(".$field_desc['value'].")";
		}

		if ($field_desc['null'] == 'not null' || $field_desc['null'] == 'NOT NULL')
		{
        	// We will try to change format of column to NOT NULL. To be sure the ALTER works, we try to update fields that are NULL
        	if ($field_desc['type'] == 'varchar' || $field_desc['type'] == 'text')
        	{
        		$sqlbis="UPDATE ".$table." SET ".$field_name." = '".$this->escape($field_desc['default'] ? $field_desc['default'] : '')."' WHERE ".$field_name." IS NULL";
        		$this->query($sqlbis);
        	}
        	elseif ($field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int')
        	{
        		$sqlbis="UPDATE ".$table." SET ".$field_name." = ".((int) $this->escape($field_desc['default'] ? $field_desc['default'] : 0))." WHERE ".$field_name." IS NULL";
        		$this->query($sqlbis);
        	}
		}

		if ($field_desc['default'] != '')
		{
			if ($field_desc['type'] == 'double' || $field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int') $sql.=" DEFAULT ".$this->escape($field_desc['default']);
        	elseif ($field_desc['type'] != 'text') $sql.=" DEFAULT '".$this->escape($field_desc['default'])."'";							// Default not supported on text fields
		}

		dol_syslog($sql,LOG_DEBUG);
		if (! $this->query($sql))
			return -1;
		return 1;
	}

	/**
	 *	Drop a field from table
	 *
	 *	@param	string	$table 			Name of table
	 *	@param	string	$field_name 	Name of field to drop
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function DDLDropField($table,$field_name)
	{
		$sql= "ALTER TABLE ".$table." DROP COLUMN ".$field_name;
		dol_syslog($sql,LOG_DEBUG);
		if (! $this->query($sql))
		{
			$this->error=$this->lasterror();
			return -1;
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
		$resql=$this->query('SHOW SERVER_ENCODING');
		if ($resql)
		{
            $liste=$this->fetch_array($resql);
		    return $liste['server_encoding'];
		}
		else return '';
	}

	/**
	 *	Return list of available charset that can be used to store data in database
	 *
	 *	@return		array		List of Charset
	 */
	function getListOfCharacterSet()
	{
		$resql=$this->query('SHOW SERVER_ENCODING');
		$liste = array();
		if ($resql)
		{
			$i = 0;
			while ($obj = $this->fetch_object($resql))
			{
				$liste[$i]['charset'] = $obj->server_encoding;
				$liste[$i]['description'] = 'Default database charset';
				$i++;
			}
			$this->free($resql);
		} else {
			return null;
		}
		return $liste;
	}

	/**
	 *	Return collation used in database
	 *
	 *	@return		string		Collation value
	 */
	function getDefaultCollationDatabase()
	{
		$resql=$this->query('SHOW LC_COLLATE');
		if ($resql)
		{
		    $liste=$this->fetch_array($resql);
			return $liste['lc_collate'];
		}
		else return '';
	}

	/**
	 *	Return list of available collation that can be used for database
	 *
	 *	@return		array		Liste of Collation
	 */
	function getListOfCollation()
	{
		$resql=$this->query('SHOW LC_COLLATE');
		$liste = array();
		if ($resql)
		{
			$i = 0;
			while ($obj = $this->fetch_object($resql) )
			{
				$liste[$i]['collation'] = $obj->lc_collate;
				$i++;
			}
			$this->free($resql);
		} else {
			return null;
		}
		return $liste;
	}

	/**
	 *	Return full path of dump program
	 *
	 *	@return		string		Full path of dump program
	 */
	function getPathOfDump()
	{
		$fullpathofdump='/pathtopgdump/pg_dump';

		if (file_exists('/usr/bin/pg_dump'))
		{
		    $fullpathofdump='/usr/bin/pg_dump';
		}
		else
		{
            // TODO L'utilisateur de la base doit etre un superadmin pour lancer cette commande
		    $resql=$this->query('SHOW data_directory');
    		if ($resql)
    		{
    			$liste=$this->fetch_array($resql);
    			$basedir=$liste['data_directory'];
    			$fullpathofdump=preg_replace('/data$/','bin',$basedir).'/pg_dump';
    		}
		}

		return $fullpathofdump;
	}

    /**
     *	Return full path of restore program
     *
     *	@return		string		Full path of restore program
     */
	function getPathOfRestore()
	{
		//$tool='pg_restore';
		$tool='psql';

		$fullpathofdump='/pathtopgrestore/'.$tool;

        if (file_exists('/usr/bin/'.$tool))
        {
            $fullpathofdump='/usr/bin/'.$tool;
        }
        else
        {
            // TODO L'utilisateur de la base doit etre un superadmin pour lancer cette commande
            $resql=$this->query('SHOW data_directory');
            if ($resql)
            {
                $liste=$this->fetch_array($resql);
                $basedir=$liste['data_directory'];
                $fullpathofdump=preg_replace('/data$/','bin',$basedir).'/'.$tool;
            }
        }

		return $fullpathofdump;
	}

	/**
	 * Return value of server parameters
	 *
	 * @param	string	$filter		Filter list on a particular value
	 * @return	array				Array of key-values (key=>value)
	 */
	function getServerParametersValues($filter='')
	{
		$result=array();

		$resql='select name,setting from pg_settings';
		if ($filter) $resql.=" WHERE name = '".$this->escape($filter)."'";
		$resql=$this->query($resql);
		if ($resql)
		{
			while ($obj=$this->fetch_object($resql)) $result[$obj->name]=$obj->setting;
		}

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
		/* This is to return current running requests.
		$sql='SELECT datname,procpid,current_query FROM pg_stat_activity ORDER BY procpid';
        if ($filter) $sql.=" LIKE '".$this->escape($filter)."'";
        $resql=$this->query($sql);
        if ($resql)
        {
            $obj=$this->fetch_object($resql);
            $result[$obj->Variable_name]=$obj->Value;
        }
		*/

		return array();
	}
}
