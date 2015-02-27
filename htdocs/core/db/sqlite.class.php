<?php
/* Copyright (C) 2001      Fabien Seisen        <seisen@linuxfr.org>
 * Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *	\file       htdocs/core/db/sqlite.class.php
 *	\brief      Class file to manage Dolibarr database access for a Sqlite database
 */

require_once DOL_DOCUMENT_ROOT .'/core/db/DoliDB.class.php';

/**
 *	Class to manage Dolibarr database access for a Sqlite database
 */
class DoliDBSqlite extends DoliDB
{
    //! Database type
    public $type='sqlite';
    //! Database label
    const LABEL='Sqlite3';
    //! Version min database
    const VERSIONMIN='3.0.0';
	//! Resultset of last query
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
        global $conf,$langs;

        // Note that having "static" property for "$forcecharset" and "$forcecollate" will make error here in strict mode, so they are not static
        if (! empty($conf->db->character_set)) $this->forcecharset=$conf->db->character_set;
        if (! empty($conf->db->dolibarr_main_db_collation)) $this->forcecollate=$conf->db->dolibarr_main_db_collation;

        $this->database_user=$user;
        $this->database_host=$host;
        $this->database_port=$port;

        $this->transaction_opened=0;

        //print "Name DB: $host,$user,$pass,$name<br>";

        /*if (! function_exists("sqlite_query"))
        {
            $this->connected = 0;
            $this->ok = 0;
            $this->error="Sqlite PHP functions for using Sqlite driver are not available in this version of PHP. Try to use another driver.";
            dol_syslog(get_class($this)."::DoliDBSqlite : Sqlite PHP functions for using Sqlite driver are not available in this version of PHP. Try to use another driver.",LOG_ERR);
            return $this->ok;
        }*/

        /*if (! $host)
        {
            $this->connected = 0;
            $this->ok = 0;
            $this->error=$langs->trans("ErrorWrongHostParameter");
            dol_syslog(get_class($this)."::DoliDBSqlite : Erreur Connect, wrong host parameters",LOG_ERR);
            return $this->ok;
        }*/

        // Essai connexion serveur
        // We do not try to connect to database, only to server. Connect to database is done later in constrcutor
        $this->db = $this->connect($host, $user, $pass, $name, $port);

        if ($this->db)
        {
            $this->connected = 1;
            $this->ok = 1;
            $this->database_selected = 1;
            $this->database_name = $name;

			$this->addCustomFunction('IF');
			$this->addCustomFunction('MONTH');
			$this->addCustomFunction('date_format');
            //$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        else
        {
            // host, login ou password incorrect
            $this->connected = 0;
            $this->ok = 0;
            $this->database_selected = 0;
            $this->database_name = '';
            //$this->error=sqlite_connect_error();
            dol_syslog(get_class($this)."::DoliDBSqlite : Erreur Connect ".$this->error,LOG_ERR);
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
		    if ($type == 'auto')
		    {
              if (preg_match('/ALTER TABLE/i',$line)) $type='dml';
              else if (preg_match('/CREATE TABLE/i',$line)) $type='dml';
              else if (preg_match('/DROP TABLE/i',$line)) $type='dml';
		    }

		    if ($type == 'dml')
		    {
                $line=preg_replace('/\s/',' ',$line);   // Replace tabulation with space

		        // we are inside create table statement so lets process datatypes
    			if (preg_match('/(ISAM|innodb)/i',$line)) { // end of create table sequence
    				$line=preg_replace('/\)[\s\t]*type[\s\t]*=[\s\t]*(MyISAM|innodb);/i',');',$line);
    				$line=preg_replace('/\)[\s\t]*engine[\s\t]*=[\s\t]*(MyISAM|innodb);/i',');',$line);
    				$line=preg_replace('/,$/','',$line);
    			}

    			// Process case: "CREATE TABLE llx_mytable(rowid integer NOT NULL AUTO_INCREMENT PRIMARY KEY,code..."
    			if (preg_match('/[\s\t\(]*(\w*)[\s\t]+int.*auto_increment/i',$line,$reg)) {
    				$newline=preg_replace('/([\s\t\(]*)([a-zA-Z_0-9]*)[\s\t]+int.*auto_increment[^,]*/i','\\1 \\2 integer PRIMARY KEY AUTOINCREMENT',$line);
                    //$line = "-- ".$line." replaced by --\n".$newline;
                    $line=$newline;
    			}

    			// tinyint type conversion
    			$line=str_replace('tinyint','smallint',$line);

    			// nuke unsigned
    			$line=preg_replace('/(int\w+|smallint)\s+unsigned/i','\\1',$line);

    			// blob -> text
    			$line=preg_replace('/\w*blob/i','text',$line);

    			// tinytext/mediumtext -> text
    			$line=preg_replace('/tinytext/i','text',$line);
    			$line=preg_replace('/mediumtext/i','text',$line);

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

    			// unique index(field1,field2)
    			if (preg_match('/unique index\s*\((\w+\s*,\s*\w+)\)/i',$line))
    			{
    				$line=preg_replace('/unique index\s*\((\w+\s*,\s*\w+)\)/i','UNIQUE\(\\1\)',$line);
    			}

    			// We remove end of requests "AFTER fieldxxx"
    			$line=preg_replace('/AFTER [a-z0-9_]+/i','',$line);

    			// We remove start of requests "ALTER TABLE tablexxx" if this is a DROP INDEX
    			$line=preg_replace('/ALTER TABLE [a-z0-9_]+ DROP INDEX/i','DROP INDEX',$line);

                // Translate order to rename fields
                if (preg_match('/ALTER TABLE ([a-z0-9_]+) CHANGE(?: COLUMN)? ([a-z0-9_]+) ([a-z0-9_]+)(.*)$/i',$line,$reg))
                {
                	$line = "-- ".$line." replaced by --\n";
                    $line.= "ALTER TABLE ".$reg[1]." RENAME COLUMN ".$reg[2]." TO ".$reg[3];
                }

                // Translate order to modify field format
                if (preg_match('/ALTER TABLE ([a-z0-9_]+) MODIFY(?: COLUMN)? ([a-z0-9_]+) (.*)$/i',$line,$reg))
                {
                    $line = "-- ".$line." replaced by --\n";
                    $newreg3=$reg[3];
                    $newreg3=preg_replace('/ DEFAULT NULL/i','',$newreg3);
                    $newreg3=preg_replace('/ NOT NULL/i','',$newreg3);
                    $newreg3=preg_replace('/ NULL/i','',$newreg3);
                    $newreg3=preg_replace('/ DEFAULT 0/i','',$newreg3);
                    $newreg3=preg_replace('/ DEFAULT \'[0-9a-zA-Z_@]*\'/i','',$newreg3);
                    $line.= "ALTER TABLE ".$reg[1]." ALTER COLUMN ".$reg[2]." TYPE ".$newreg3;
                    // TODO Add alter to set default value or null/not null if there is this in $reg[3]
                }

                // alter table add primary key (field1, field2 ...) -> We create a unique index instead as dynamic creation of primary key is not supported
    			// ALTER TABLE llx_dolibarr_modules ADD PRIMARY KEY pk_dolibarr_modules (numero, entity);
    			if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+PRIMARY\s+KEY\s*(.*)\s*\((.*)$/i',$line,$reg))
    			{
    				$line = "-- ".$line." replaced by --\n";
    				$line.= "CREATE UNIQUE INDEX ".$reg[2]." ON ".$reg[1]."(".$reg[3];
    			}

                // Translate order to drop foreign keys
                // ALTER TABLE llx_dolibarr_modules DROP FOREIGN KEY fk_xxx;
                if (preg_match('/ALTER\s+TABLE\s*(.*)\s*DROP\s+FOREIGN\s+KEY\s*(.*)$/i',$line,$reg))
                {
                    $line = "-- ".$line." replaced by --\n";
                    $line.= "ALTER TABLE ".$reg[1]." DROP CONSTRAINT ".$reg[2];
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
				if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+CONSTRAINT\s+(.*)\s*FOREIGN\s+KEY\s*\(([\w,\s]+)\)\s*REFERENCES\s+(\w+)\s*\(([\w,\s]+)\)/i',$line, $reg)) {
					// Pour l'instant les contraintes ne sont pas créées
					dol_syslog(get_class().'::query line emptied');
					$line = 'SELECT 0;';

				}

				//if (preg_match('/rowid\s+.*\s+PRIMARY\s+KEY,/i', $line)) {
					//preg_replace('/(rowid\s+.*\s+PRIMARY\s+KEY\s*,)/i', '/* \\1 */', $line);
				//}
            }

			// Delete using criteria on other table must not declare twice the deleted table
			// DELETE FROM tabletodelete USING tabletodelete, othertable -> DELETE FROM tabletodelete USING othertable
			if (preg_match('/DELETE FROM ([a-z_]+) USING ([a-z_]+), ([a-z_]+)/i',$line,$reg))
			{
				if ($reg[1] == $reg[2])	// If same table, we remove second one
				{
					$line=preg_replace('/DELETE FROM ([a-z_]+) USING ([a-z_]+), ([a-z_]+)/i','DELETE FROM \\1 USING \\3', $line);
				}
			}

			// Remove () in the tables in FROM if one table
			$line=preg_replace('/FROM\s*\((([a-z_]+)\s+as\s+([a-z_]+)\s*)\)/i','FROM \\1',$line);
			//print $line."\n";

			// Remove () in the tables in FROM if two table
			$line=preg_replace('/FROM\s*\(([a-z_]+\s+as\s+[a-z_]+)\s*,\s*([a-z_]+\s+as\s+[a-z_]+\s*)\)/i','FROM \\1, \\2',$line);
			//print $line."\n";

			// Remove () in the tables in FROM if two table
			$line=preg_replace('/FROM\s*\(([a-z_]+\s+as\s+[a-z_]+)\s*,\s*([a-z_]+\s+as\s+[a-z_]+\s*),\s*([a-z_]+\s+as\s+[a-z_]+\s*)\)/i','FROM \\1, \\2, \\3',$line);
			//print $line."\n";

			//print "type=".$type." newline=".$line."<br>\n";
		}

		return $line;
    }

	/**
	 *	Select a database
	 *
	 *	@param	    string	$database	Name of database
	 *	@return	    boolean  		    true if OK, false if KO
	 */
    function select_db($database)
    {
        dol_syslog(get_class($this)."::select_db database=".$database, LOG_DEBUG);
        return sqlite_select_db($this->db,$database);
    }


    /**
	 *	Connexion to server
	 *
	 *	@param	    string	$host		database server host
	 *	@param	    string	$login		login
	 *	@param	    string	$passwd		password
	 *	@param		string	$name		name of database (not used for mysql, used for pgsql)
	 *	@param		string	$port		Port of database server
	 *	@return		resource			Database access handler
	 *	@see		close
     */
    function connect($host, $login, $passwd, $name, $port=0)
    {
        global $conf,$main_data_dir;

        dol_syslog(get_class($this)."::connect name=".$name,LOG_DEBUG);

        $dir=$main_data_dir;
        if (empty($dir)) $dir=DOL_DATA_ROOT;
        // With sqlite, port must be in connect parameters
        //if (! $newport) $newport=3306;
		$database_name = $dir.'/database_'.$name.'.sdb';
        try {
            /*** connect to SQLite database ***/
            //$this->db = new PDO("sqlite:".$dir.'/database_'.$name.'.sdb');
			$this->db = new SQLite3($database_name);
            //$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        catch(Exception $e)
        {
            $this->error=  self::LABEL.' '.$e->getMessage().' current dir='.$database_name;
            return '';
        }

        //print "Resultat fonction connect: ".$this->db;
        return $this->db;
    }


    /**
	 *	Return version of database server
	 *
	 *	@return	        string      Version string
     */
    function getVersion()
    {
		return $this->db->version()['versionString'];
    }

    /**
     *	Return version of database client driver
     *
     *	@return	        string      Version string
     */
    function getDriverInfo()
    {
	    // FIXME: Dummy method
	    // TODO: Implement

    	return '';
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
            $this->connected=0;
			$this->db->close();
            $this->db=null;    // Clean this->db
            return true;
        }
        return false;
    }

    /**
     * 	Execute a SQL request and return the resultset
     *
     * 	@param	string	$query			SQL query string
     * 	@param	int		$usesavepoint	0=Default mode, 1=Run a savepoint before and a rollbock to savepoint if error (this allow to have some request with errors inside global transactions).
     * 									Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
     *  @param  string	$type           Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
     *	@return	resource    			Resultset of answer
     */
    function query($query,$usesavepoint=0,$type='auto')
    {
        $errmsg='';

        $ret='';
        $query = trim($query);
        $this->error = 0;

		// Convert MySQL syntax to SQLite syntax
		if (preg_match('/ALTER\s+TABLE\s*(.*)\s*ADD\s+CONSTRAINT\s+(.*)\s*FOREIGN\s+KEY\s*\(([\w,\s]+)\)\s*REFERENCES\s+(\w+)\s*\(([\w,\s]+)\)/i',$query, $reg)) {
			// Ajout d'une clef étrangère à la table
			// procédure de remplacement de la table pour ajouter la contrainte
			// Exemple : ALTER TABLE llx_adherent ADD CONSTRAINT adherent_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid)
			// -> CREATE TABLE ( ... ,CONSTRAINT adherent_fk_soc FOREIGN KEY (fk_soc) REFERENCES llx_societe (rowid))
			$foreignFields = $reg[5];
			$foreignTable = $reg[4];
			$localfields = $reg[3];
			$constraintname=trim($reg[2]);
			$tablename=trim($reg[1]);

			$descTable = $this->db->querySingle("SELECT sql FROM sqlite_master WHERE name='" . $tablename . "'");

			// 1- Renommer la table avec un nom temporaire
			$this->query('ALTER TABLE ' . $tablename . ' RENAME TO tmp_' . $tablename);

			// 2- Recréer la table avec la contrainte ajoutée

			// on bricole la requete pour ajouter la contrainte
			$descTable = substr($descTable, 0, strlen($descTable) - 1);
			$descTable .= ", CONSTRAINT " . $constraintname . " FOREIGN KEY (" . $localfields . ") REFERENCES " .$foreignTable . "(" . $foreignFields . ")";

			// fermeture de l'instruction
			$descTable .= ')';

			// Création proprement dite de la table
			$this->query($descTable);

			// 3- Transférer les données
			$this->query('INSERT INTO ' . $tablename . ' SELECT * FROM tmp_' . $tablename);

			// 4- Supprimer la table temporaire
			$this->query('DROP TABLE tmp_' . $tablename);

			// dummy statement
			$query="SELECT 0";

		} else {
			$query=$this->convertSQLFromMysql($query,$type);
		}
		//print "After convertSQLFromMysql:\n".$query."<br>\n";

	    dol_syslog('sql='.$query, LOG_DEBUG);

	    // Ordre SQL ne necessitant pas de connexion a une base (exemple: CREATE DATABASE)
        try {
            //$ret = $this->db->exec($query);
            $ret = $this->db->query($query);        // $ret is a PDO object
			if ($ret) {
				$ret->queryString = $query;
			}
        }
        catch(Exception $e)
        {
            $this->error=$this->db->lastErrorMsg();
        }

        if (! preg_match("/^COMMIT/i",$query) && ! preg_match("/^ROLLBACK/i",$query))
        {
            // Si requete utilisateur, on la sauvegarde ainsi que son resultset
            if (! is_object($ret) || $this->error)
            {
                $this->lastqueryerror = $query;
                $this->lasterror = $this->error();
                $this->lasterrno = $this->errno();

	            dol_syslog(get_class($this)."::query SQL Error query: ".$query, LOG_ERR);

	            $errormsg = get_class($this)."::query SQL Error message: ".$this->lasterror;

				if (preg_match('/[0-9]/',$this->lasterrno)) {
                    $errormsg .= ' ('.$this->lasterrno.')';
                }

	            dol_syslog($errormsg, LOG_ERR);
            }
            $this->lastquery=$query;
            $this->_results = $ret;
        }

        return $ret;
    }

    /**
     *	Renvoie la ligne courante (comme un objet) pour le curseur resultset
     *
     *	@param	Resultset	$resultset  Curseur de la requete voulue
     *	@return	Object					Object result line or false if KO or end of cursor
     */
    function fetch_object($resultset)
    {
        // Si le resultset n'est pas fourni, on prend le dernier utilise sur cette connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        //return $resultset->fetch(PDO::FETCH_OBJ);
		$ret = $resultset->fetchArray(SQLITE3_ASSOC);
		if ($ret) {
			return (object)$ret;
		}
    }


    /**
     *	Return datas as an array
     *
     *	@param	Resultset	$resultset  Resultset of request
     *	@return	array					Array
     */
    function fetch_array($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        //return $resultset->fetch(PDO::FETCH_ASSOC);
		$ret = $resultset->fetchArray(SQLITE3_ASSOC);
		if ($ret) {
			return (array)$ret;
		}
    }

    /**
     *	Return datas as an array
     *
     *	@param	Resultset	$resultset  Resultset of request
     *	@return	array					Array
     */
    function fetch_row($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_bool($resultset))
        {
            if (! is_object($resultset)) { $resultset=$this->_results; }
            return $resultset->fetchArray(SQLITE3_NUM);
        }
        else
        {
            // si le curseur est un booleen on retourne la valeur 0
            return 0;
        }
    }

    /**
     *	Return number of lines for result of a SELECT
     *
     *	@param	Resultset	$resultset  Resulset of requests
     *	@return int		    			Nb of lines
     *	@see    affected_rows
     */
    function num_rows($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
		if (preg_match("/^SELECT/i", $resultset->queryString)) {
			return $this->db->querySingle("SELECT count(*) FROM (" . $resultset->queryString . ") q");
		}
		return 0;
    }

    /**
     *	Return number of lines for result of a SELECT
     *
     *	@param	Resultset	$resultset  Resulset of requests
     *	@return int		    			Nb of lines
     *	@see    affected_rows
     */
    function affected_rows($resultset)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
		if (preg_match("/^SELECT/i", $resultset->queryString)) {
			return $this->num_rows($resultset);
		}
        // mysql necessite un link de base pour cette fonction contrairement
        // a pqsql qui prend un resultset
        return $this->db->changes();
    }


	/**
	 *	Free last resultset used.
	 *
	 *	@param  resultset	$resultset   Curseur de la requete voulue
	 *	@return	void
	 */
    function free($resultset=0)
    {
        // If resultset not provided, we take the last used by connexion
        if (! is_object($resultset)) { $resultset=$this->_results; }
        // Si resultset en est un, on libere la memoire
        if ($resultset && is_object($resultset)) $resultset->finalize();
    }

	/**
	 *	Escape a string to insert data
	 *
	 *  @param	string	$stringtoencode		String to escape
	 *  @return	string						String escaped
	 */
    function escape($stringtoencode)
    {
		return Sqlite3::escapeString($stringtoencode);
    }

    /**
     *	Renvoie le code erreur generique de l'operation precedente.
     *
     *	@return	string		Error code (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
     */
    function errno()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connexion, $this->db n'est pas valide.
            return 'DB_ERROR_FAILED_TO_CONNECT';
        }
        else {
            // Constants to convert error code to a generic Dolibarr error code
            /*$errorcode_map = array(
            1004 => 'DB_ERROR_CANNOT_CREATE',
            1005 => 'DB_ERROR_CANNOT_CREATE',
            1006 => 'DB_ERROR_CANNOT_CREATE',
            1007 => 'DB_ERROR_ALREADY_EXISTS',
            1008 => 'DB_ERROR_CANNOT_DROP',
            1025 => 'DB_ERROR_NO_FOREIGN_KEY_TO_DROP',
            1044 => 'DB_ERROR_ACCESSDENIED',
            1046 => 'DB_ERROR_NODBSELECTED',
            1048 => 'DB_ERROR_CONSTRAINT',
            'HY000' => 'DB_ERROR_TABLE_ALREADY_EXISTS',
            1051 => 'DB_ERROR_NOSUCHTABLE',
            1054 => 'DB_ERROR_NOSUCHFIELD',
            1060 => 'DB_ERROR_COLUMN_ALREADY_EXISTS',
            1061 => 'DB_ERROR_KEY_NAME_ALREADY_EXISTS',
            1062 => 'DB_ERROR_RECORD_ALREADY_EXISTS',
            1064 => 'DB_ERROR_SYNTAX',
            1068 => 'DB_ERROR_PRIMARY_KEY_ALREADY_EXISTS',
            1075 => 'DB_ERROR_CANT_DROP_PRIMARY_KEY',
            1091 => 'DB_ERROR_NOSUCHFIELD',
            1100 => 'DB_ERROR_NOT_LOCKED',
            1136 => 'DB_ERROR_VALUE_COUNT_ON_ROW',
            1146 => 'DB_ERROR_NOSUCHTABLE',
            1216 => 'DB_ERROR_NO_PARENT',
            1217 => 'DB_ERROR_CHILD_EXISTS',
            1451 => 'DB_ERROR_CHILD_EXISTS'
            );

            if (isset($errorcode_map[$this->db->errorCode()]))
            {
                return $errorcode_map[$this->db->errorCode()];
            }*/
            $errno=$this->db->lastErrorCode();
			if ($errno=='HY000' || $errno == 0)
			{
                if (preg_match('/table.*already exists/i',$this->error))     return 'DB_ERROR_TABLE_ALREADY_EXISTS';
                elseif (preg_match('/index.*already exists/i',$this->error)) return 'DB_ERROR_KEY_NAME_ALREADY_EXISTS';
                elseif (preg_match('/syntax error/i',$this->error))          return 'DB_ERROR_SYNTAX';
			}
			if ($errno=='23000')
			{
                if (preg_match('/column.* not unique/i',$this->error))       return 'DB_ERROR_RECORD_ALREADY_EXISTS';
                elseif (preg_match('/PRIMARY KEY must be unique/i',$this->error)) return 'DB_ERROR_RECORD_ALREADY_EXISTS';
			}
			if ($errno > 1) {
				// TODO Voir la liste des messages d'erreur
			}

            return ($errno?'DB_ERROR_'.$errno:'0');
        }
    }

    /**
     *	Renvoie le texte de l'erreur mysql de l'operation precedente.
     *
     *	@return	string	Error text
     */
    function error()
    {
        if (! $this->connected) {
            // Si il y a eu echec de connexion, $this->db n'est pas valide pour sqlite_error.
            return 'Not connected. Check setup parameters in conf/conf.php file and your sqlite version';
        }
        else {
            return $this->error;
        }
    }

    /**
	 * Get last ID after an insert INSERT
	 *
	 * @param   string	$tab    	Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param	string	$fieldid	Field name
	 * @return  int     			Id of row
     */
    function last_insert_id($tab,$fieldid='rowid')
    {
        return $this->db->lastInsertRowId();
    }

    /**
     *  Encrypt sensitive data in database
     *  Warning: This function includes the escape, so it must use direct value
     *
     *  @param  string  $fieldorvalue   Field name or value to encrypt
     *  @param	int		$withQuotes     Return string with quotes
     *  @return return          		XXX(field) or XXX('value') or field or 'value'
     */
    function encrypt($fieldorvalue, $withQuotes=0)
    {
        global $conf;

        // Type of encryption (2: AES (recommended), 1: DES , 0: no encryption)
        $cryptType = ($conf->db->dolibarr_main_db_encryption?$conf->db->dolibarr_main_db_encryption:0);

        //Encryption key
        $cryptKey = (!empty($conf->db->dolibarr_main_db_cryptkey)?$conf->db->dolibarr_main_db_cryptkey:'');

        $return = ($withQuotes?"'":"").$this->escape($fieldorvalue).($withQuotes?"'":"");

        if ($cryptType && !empty($cryptKey))
        {
            if ($cryptType == 2)
            {
                $return = 'AES_ENCRYPT('.$return.',\''.$cryptKey.'\')';
            }
            else if ($cryptType == 1)
            {
                $return = 'DES_ENCRYPT('.$return.',\''.$cryptKey.'\')';
            }
        }

        return $return;
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

        if ($cryptType && !empty($cryptKey))
        {
            if ($cryptType == 2)
            {
                $return = 'AES_DECRYPT('.$value.',\''.$cryptKey.'\')';
            }
            else if ($cryptType == 1)
            {
                $return = 'DES_DECRYPT('.$value.',\''.$cryptKey.'\')';
            }
        }

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
	 * 	@return	resource				resource defined if OK, null if KO
     */
    function DDLCreateDb($database,$charset='',$collation='',$owner='')
    {
        if (empty($charset))   $charset=$this->forcecharset;
        if (empty($collation)) $collation=$this->forcecollate;

        // ALTER DATABASE dolibarr_db DEFAULT CHARACTER SET latin DEFAULT COLLATE latin1_swedish_ci
        $sql = 'CREATE DATABASE '.$database;
        $sql.= ' DEFAULT CHARACTER SET '.$charset.' DEFAULT COLLATE '.$collation;

        dol_syslog($sql,LOG_DEBUG);
        $ret=$this->query($sql);
        if (! $ret)
        {
            // We try again for compatibility with Mysql < 4.1.1
            $sql = 'CREATE DATABASE '.$database;
            $ret=$this->query($sql);
            dol_syslog($sql,LOG_DEBUG);
        }
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
        if ($table) $like = "LIKE '".$table."'";
        $sql="SHOW TABLES FROM ".$database." ".$like.";";
        //print $sql;
        $result = $this->query($sql);
        while($row = $this->fetch_row($result))
        {
            $listtables[] = $row[0];
        }
        return $listtables;
    }

    /**
	 *	List information of columns into a table.
	 *
	 *	@param	string	$table		Name of table
	 *	@return	array				Tableau des informations des champs de la table
	 *	TODO modify for sqlite
     */
    function DDLInfoTable($table)
    {
        $infotables=array();

        $sql="SHOW FULL COLUMNS FROM ".$table.";";

        dol_syslog($sql,LOG_DEBUG);
        $result = $this->query($sql);
        while($row = $this->fetch_row($result))
        {
            $infotables[] = $row;
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
	 *	@param	    string	$keys 			Tableau des champs cles noms => valeur
	 *	@return	    int						<0 if KO, >=0 if OK
     */
    function DDLCreateTable($table,$fields,$primary_key,$type,$unique_keys="",$fulltext_keys="",$keys="")
    {
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

        if($unique_keys != "")
        {
            $i = 0;
            foreach($unique_keys as $key => $value)
            {
                $sqluq[$i] = "UNIQUE KEY '".$key."' ('".$value."')";
                $i++;
            }
        }
        if($keys != "")
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
        if($unique_keys != "")
        $sql .= ",".implode(',',$sqluq);
        if($keys != "")
        $sql .= ",".implode(',',$sqlk);
        $sql .=") type=".$type;

        dol_syslog($sql,LOG_DEBUG);
        if(! $this -> query($sql))
        return -1;
        else
        return 1;
    }

    /**
	 *	Return a pointer of line with description of a table or field
	 *
	 *	@param	string		$table	Name of table
	 *	@param	string		$field	Optionnel : Name of field if we want description of field
	 *	@return	resource			Resource
     */
    function DDLDescTable($table,$field="")
    {
        $sql="DESC ".$table." ".$field;

        dol_syslog(get_class($this)."::DDLDescTable ".$sql,LOG_DEBUG);
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
        $sql.= $field_desc['type'];
        if(preg_match("/^[^\s]/i",$field_desc['value']))
        if (! in_array($field_desc['type'],array('date','datetime')))
        {
            $sql.= "(".$field_desc['value'].")";
        }
        if(preg_match("/^[^\s]/i",$field_desc['attribute']))
        $sql.= " ".$field_desc['attribute'];
        if(preg_match("/^[^\s]/i",$field_desc['null']))
        $sql.= " ".$field_desc['null'];
        if(preg_match("/^[^\s]/i",$field_desc['default']))
        {
            if(preg_match("/null/i",$field_desc['default']))
            $sql.= " default ".$field_desc['default'];
            else
            $sql.= " default '".$field_desc['default']."'";
        }
        if(preg_match("/^[^\s]/i",$field_desc['extra']))
        $sql.= " ".$field_desc['extra'];
        $sql.= " ".$field_position;

        dol_syslog(get_class($this)."::DDLAddField ".$sql,LOG_DEBUG);
        if(! $this->query($sql))
        {
            return -1;
        }
        else
        {
            return 1;
        }
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
        if ($field_desc['type'] == 'tinyint' || $field_desc['type'] == 'int' || $field_desc['type'] == 'varchar') {
        	$sql.="(".$field_desc['value'].")";
        }

        dol_syslog(get_class($this)."::DDLUpdateField ".$sql,LOG_DEBUG);
        if (! $this->query($sql))
        return -1;
        else
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
        $sql= "ALTER TABLE ".$table." DROP COLUMN `".$field_name."`";
        dol_syslog(get_class($this)."::DDLDropField ".$sql,LOG_DEBUG);
        if (! $this->query($sql))
        {
            $this->error=$this->lasterror();
            return -1;
        }
        else return 1;
    }


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
        $sql = "INSERT INTO user ";
        $sql.= "(Host,User,password,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv,Lock_tables_priv)";
        $sql.= " VALUES ('".$this->escape($dolibarr_main_db_host)."','".$this->escape($dolibarr_main_db_user)."',password('".addslashes($dolibarr_main_db_pass)."')";
        $sql.= ",'Y','Y','Y','Y','Y','Y','Y','Y','Y')";

        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);	// No sql to avoid password in log
        $resql=$this->query($sql);
        if (! $resql)
        {
            return -1;
        }

        $sql = "INSERT INTO db ";
        $sql.= "(Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Index_Priv,Alter_priv,Lock_tables_priv)";
        $sql.= " VALUES ('".$this->escape($dolibarr_main_db_host)."','".$this->escape($dolibarr_main_db_name)."','".addslashes($dolibarr_main_db_user)."'";
        $sql.= ",'Y','Y','Y','Y','Y','Y','Y','Y','Y')";

        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);
        $resql=$this->query($sql);
        if (! $resql)
        {
            return -1;
        }

        $sql="FLUSH Privileges";

        dol_syslog(get_class($this)."::DDLCreateUser", LOG_DEBUG);
        $resql=$this->query($sql);
        if (! $resql)
        {
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
        return 'UTF-8';
    }

	/**
	 *	Return list of available charset that can be used to store data in database
	 *
	 *	@return		array		List of Charset
	 */
    function getListOfCharacterSet()
    {
        $liste = array();
        $i=0;
        $liste[$i]['charset'] = 'UTF-8';
        $liste[$i]['description'] = 'UTF-8';
        return $liste;
    }

	/**
	 *	Return collation used in database
	 *
	 *	@return		string		Collation value
	 */
    function getDefaultCollationDatabase()
    {
        return 'UTF-8';
    }

	/**
	 *	Return list of available collation that can be used for database
	 *
	 *	@return		array		List of Collation
	 */
    function getListOfCollation()
    {
        $liste = array();
        $i=0;
        $liste[$i]['charset'] = 'UTF-8';
        $liste[$i]['description'] = 'UTF-8';
        return $liste;
    }

	/**
	 *	Return full path of dump program
	 *
	 *	@return		string		Full path of dump program
	 */
	function getPathOfDump()
    {
        $fullpathofdump='/pathtomysqldump/mysqldump';

        $resql=$this->query('SHOW VARIABLES LIKE \'basedir\'');
        if ($resql)
        {
            $liste=$this->fetch_array($resql);
            $basedir=$liste['Value'];
            $fullpathofdump=$basedir.(preg_match('/\/$/',$basedir)?'':'/').'bin/mysqldump';
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
        $fullpathofimport='/pathtomysql/mysql';

        $resql=$this->query('SHOW VARIABLES LIKE \'basedir\'');
        if ($resql)
        {
            $liste=$this->fetch_array($resql);
            $basedir=$liste['Value'];
            $fullpathofimport=$basedir.(preg_match('/\/$/',$basedir)?'':'/').'bin/mysql';
        }
        return $fullpathofimport;
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

        $sql='SHOW VARIABLES';
        if ($filter) $sql.=" LIKE '".$this->escape($filter)."'";
        $resql=$this->query($sql);
        if ($resql)
        {
            while ($obj=$this->fetch_object($resql)) $result[$obj->Variable_name]=$obj->Value;
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
        $result=array();

        $sql='SHOW STATUS';
        if ($filter) $sql.=" LIKE '".$this->escape($filter)."'";
        $resql=$this->query($sql);
        if ($resql)
        {
            while ($obj=$this->fetch_object($resql)) $result[$obj->Variable_name]=$obj->Value;
        }

        return $result;
    }

	/**
	 * Permet le chargement d'une fonction personnalisee dans le moteur de base de donnees.
	 * Note: le nom de la fonction personnalisee est prefixee par 'db_'. La fonction doit être
	 * statique et publique. Le nombre de parametres est determine automatiquement.
	 * @param string $name Le nom de la fonction a definir dans Sqlite
	 */
	private function addCustomFunction($name) {
		if ($this->db) {
			$localname = __CLASS__ . '::' . 'db_' . $name;
			$reflectClass = new ReflectionClass(__CLASS__);
			$reflectFunction = $reflectClass->getMethod('db_' . $name);
			$arg_count = $reflectFunction->getNumberOfParameters();
			if (!$this->db->createFunction($name, $localname , $arg_count)) {
				$this->error = "unable to create custom function '$name'";
			}
		}
	}

	/**
	 * Cette fonction est l'equivalent de la fonction MONTH de MySql.
	 * @param string $date
	 * @return integer
	 */
	public static function db_MONTH($date) {
		return date('n', strtotime($date));
	}

	/**
	 * Cette fonction est l'equivelent de la fonction date_format de MySQL.
	 * @staticvar string $mysql_replacement	Les symboles formatage a remplacer
	 * @param string $date la date dans un format ISO
	 * @param string $format la chaine de formatage
	 * @return string La date formatee.
	 */
	public static function db_date_format($date, $format) {
		static $mysql_replacement;
		if (! isset($mysql_replacement)) {
			$mysql_replacement = array(
				'%' => '%',
				'a' => 'D',
				'b' => 'M',
				'c' => 'n',
				'D' => 'jS',
				'd' => 'd',
				'e' => 'j',
				'f' => 'u',
				'H' => 'H',
				'h' => 'h',
				'I' => 'h',
				'i' => 'i',
				'j' => 'z',
				'k' => 'H',
				'l' => 'g',
				'M' => 'F',
				'm' => 'm',
				'p' => 'A',
				'r' => 'h:i:sA',
				'S' => 's',
				's' => 's',
				'T' => 'H:i:s',
				'U' => '',	//?
				'u' => '',	//?
				'V' => '',	//?
				'v' => '',	//?
				'W' => 'l',
				'w' => 'w',
				'X' => '',	//?
				'x' => '',	//?
				'Y' => 'Y',
				'y' => 'y',
			);
		}

		$fmt = '';
		$lg = strlen($format);
		$state = 0;
		for($idx = 0; $idx < $lg; ++$idx) {
			$char = $format[$idx];
			if ($state == 0) {
				if ($char == '%') {
					$state = 1;
				} else {
					$fmt .= $char;
				}
			}
			elseif ($state == 1) {
				if (array_key_exists($char, $mysql_replacement)) {
					$fmt .= $mysql_replacement[$char];
				} else {
					$fmt .= $char;
				}
				$state = 0;
			}
		}
		return date($fmt, strtotime($date));
	}

	/**
	 * Equivalent de la fonction MySQL IF
	 * @param boolean $test Le resultat du test
	 * @param mixed $true_part Partie a retourner si vrai
	 * @param mixed $false_part Partie a retourner si faux
	 * @return mixed Partie selectionnee en fonction du test
	 */
	public static function db_IF($test, $true_part, $false_part) {
		return ( $test ) ? $true_part : $false_part;
	}
}

