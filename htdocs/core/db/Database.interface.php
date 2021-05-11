<?php
/* Copyright (C) 2001		Fabien Seisen			<seisen@linuxfr.org>
 * Copyright (C) 2002-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2015  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
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
 *    Class to manage Dolibarr database access for an SQL database
 */
interface Database
{
	/**
	 * Format a SQL IF
	 *
	 * @param   string $test Test string (example: 'cd.statut=0', 'field IS NULL')
	 * @param   string $resok resultat si test egal
	 * @param   string $resko resultat si test non egal
	 * @return	string                SQL string
	 */
	function ifsql($test, $resok, $resko);

	/**
	 * Return datas as an array
	 *
	 * @param   resource $resultset Resultset of request
	 * @return  array                    Array
	 */
	function fetch_row($resultset);

	/**
	 * Convert (by PHP) a GM Timestamp date into a string date with PHP server TZ to insert into a date field.
	 * Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 * @param   int		$param 		Date TMS to convert
	 * @return  string            	Date in a string YYYYMMDDHHMMSS
	 */
	function idate($param);

	/**
	 * Return last error code
	 *
	 * @return  string    lasterrno
	 */
	function lasterrno();

	/**
	 * Start transaction
	 *
	 * @return  int         1 if transaction successfuly opened or already opened, 0 if error
	 */
	function begin();

	/**
	 * Create a new database
	 * Do not use function xxx_create_db (xxx=mysql, ...) as they are deprecated
	 * We force to create database with charset this->forcecharset and collate this->forcecollate
	 *
	 * @param   string 		$database 		Database name to create
	 * @param   string 		$charset 		Charset used to store data
	 * @param   string 		$collation 		Charset used to sort data
	 * @param   string 		$owner 			Username of database owner
	 * @return  resource                	resource defined if OK, null if KO
	 */
	function DDLCreateDb($database, $charset = '', $collation = '', $owner = '');

	/**
	 * Return version of database server into an array
	 *
	 * @return	array        Version array
	 */
	function getVersionArray();

	/**
	 *  Convert a SQL request in Mysql syntax to native syntax
	 *
	 * @param   string $line SQL request line to convert
	 * @param   string $type Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return  string        SQL request line converted
	 */
	static function convertSQLFromMysql($line, $type = 'ddl');

	/**
	 * Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
	 *
	 * @param   resource $resultset Curseur de la requete voulue
	 * @return 	int            Nombre de lignes
	 * @see    	num_rows
	 */
	function affected_rows($resultset);

	/**
	 * Return description of last error
	 *
	 * @return  string        Error text
	 */
	function error();

	/**
	 *  List tables into a database
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Nmae of table filter ('xxx%')
	 *  @return	array					List of tables in an array
	 */
	function DDLListTables($database, $table = '');

	/**
	 * Return last request executed with query()
	 *
	 * @return	string                    Last query
	 */
	function lastquery();

	/**
	 * Define sort criteria of request
	 *
	 * @param   string $sortfield List of sort fields
	 * @param   string $sortorder Sort order
	 * @return  string            String to provide syntax of a sort sql string
	 */
	function order($sortfield = null, $sortorder = null);

	/**
	 * Decrypt sensitive data in database
	 *
	 * @param    string $value Value to decrypt
	 * @return   string                    Decrypted value if used
	 */
	function decrypt($value);

	/**
	 *    Return datas as an array
	 *
	 * @param   resource $resultset Resultset of request
	 * @return  array                    Array
	 */
	function fetch_array($resultset);

	/**
	 * Return last error label
	 *
	 * @return	string    lasterror
	 */
	function lasterror();

	/**
	 * Escape a string to insert data
	 *
	 * @param   string $stringtoencode String to escape
	 * @return  string                        String escaped
	 */
	function escape($stringtoencode);

	/**
	 * Get last ID after an insert INSERT
	 *
	 * @param	string 	$tab 		Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param   string 	$fieldid 	Field name
	 * @return  int                	Id of row
	 */
	function last_insert_id($tab, $fieldid = 'rowid');

	/**
	 *    Return full path of restore program
	 *
	 * @return        string        Full path of restore program
	 */
	function getPathOfRestore();

	/**
	 *    Annulation d'une transaction et retour aux anciennes valeurs
	 *
	 * @param	string $log Add more log to default log line
	 * @return  int                1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
	 */
	function rollback($log = '');

	/**
	 * Execute a SQL request and return the resultset
	 *
	 * @param   string $query SQL query string
	 * @param   int $usesavepoint 0=Default mode, 1=Run a savepoint before and a rollback to savepoint if error (this allow to have some request with errors inside global transactions).
	 *                                    Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
	 * @param   string $type Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return  resource                Resultset of answer
	 */
	function query($query, $usesavepoint = 0, $type = 'auto');

	/**
	 *    Connexion to server
	 *
	 * @param   string $host database server host
	 * @param   string $login login
	 * @param   string $passwd password
	 * @param   string $name name of database (not used for mysql, used for pgsql)
	 * @param   int    $port Port of database server
	 * @return  resource            Database access handler
	 * @see     close
	 */
	function connect($host, $login, $passwd, $name, $port = 0);

	/**
	 *    Define limits and offset of request
	 *
	 * @param   int $limit Maximum number of lines returned (-1=conf->liste_limit, 0=no limit)
	 * @param   int $offset Numero of line from where starting fetch
	 * @return  string            String with SQL syntax to add a limit and offset
	 */
	function plimit($limit = 0, $offset = 0);

	/**
	 * Return value of server parameters
	 *
	 * @param   string	$filter		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
	function getServerParametersValues($filter = '');

	/**
	 * Return value of server status
	 *
	 * @param   string $filter 		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
	function getServerStatusValues($filter = '');

	/**
	 * Return collation used in database
	 *
	 * @return  string        Collation value
	 */
	function getDefaultCollationDatabase();

	/**
	 * Return number of lines for result of a SELECT
	 *
	 * @param   resource $resultset Resulset of requests
	 * @return 	int                        Nb of lines
	 * @see    	affected_rows
	 */
	function num_rows($resultset);

	/**
	 * Return full path of dump program
	 *
	 * @return        string        Full path of dump program
	 */
	function getPathOfDump();

	/**
	 * Return version of database client driver
	 *
	 * @return            string      Version string
	 */
	function getDriverInfo();

	/**
	 * Return generic error code of last operation.
	 *
	 * @return    string        Error code (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
	 */
	function errno();

	/**
	 * Create a table into database
	 *
	 * @param        string $table 			Name of table
	 * @param        array 	$fields 		Tableau associatif [nom champ][tableau des descriptions]
	 * @param        string $primary_key 	Nom du champ qui sera la clef primaire
	 * @param        string $type 			Type de la table
	 * @param        array 	$unique_keys 	Tableau associatifs Nom de champs qui seront clef unique => valeur
	 * @param        array 	$fulltext_keys 	Tableau des Nom de champs qui seront indexes en fulltext
	 * @param        array $keys 			Tableau des champs cles noms => valeur
	 * @return       int                    <0 if KO, >=0 if OK
	 */
	function DDLCreateTable($table, $fields, $primary_key, $type, $unique_keys = null, $fulltext_keys = null, $keys = null);

	/**
	 * Drop a table into database
	 *
	 * @param        string $table 			Name of table
	 * @return       int                    <0 if KO, >=0 if OK
	 */
	function DDLDropTable($table);

	/**
	 * Return list of available charset that can be used to store data in database
	 *
	 * @return        array        List of Charset
	 */
	function getListOfCharacterSet();

	/**
	 * Create a new field into table
	 *
	 * @param    string $table 				Name of table
	 * @param    string $field_name 		Name of field to add
	 * @param    string $field_desc 		Tableau associatif de description du champ a inserer[nom du parametre][valeur du parametre]
	 * @param    string $field_position 	Optionnel ex.: "after champtruc"
	 * @return   int                        <0 if KO, >0 if OK
	 */
	function DDLAddField($table, $field_name, $field_desc, $field_position = "");

	/**
	 * Drop a field from table
	 *
	 * @param    string $table 				Name of table
	 * @param    string $field_name 		Name of field to drop
	 * @return   int                        <0 if KO, >0 if OK
	 */
	function DDLDropField($table, $field_name);

	/**
	 * Update format of a field into a table
	 *
	 * @param    string 	$table 			Name of table
	 * @param    string 	$field_name 	Name of field to modify
	 * @param    string 	$field_desc 	Array with description of field format
	 * @return   int                        <0 if KO, >0 if OK
	 */
	function DDLUpdateField($table, $field_name, $field_desc);

	/**
	 * Return list of available collation that can be used for database
	 *
	 * @return        array        			List of Collation
	 */
	function getListOfCollation();

	/**
	 * Return a pointer of line with description of a table or field
	 *
	 * @param    string 	$table 			Name of table
	 * @param    string 	$field 			Optionnel : Name of field if we want description of field
	 * @return   resource            		Resource
	 */
	function DDLDescTable($table, $field = "");

	/**
	 * Return version of database server
	 *
	 * @return            string      		Version string
	 */
	function getVersion();

	/**
	 * Return charset used to store data in database
	 *
	 * @return        string        		Charset
	 */
	function getDefaultCharacterSetDatabase();

	/**
	 * Create a user and privileges to connect to database (even if database does not exists yet)
	 *
	 * @param    string $dolibarr_main_db_host 	Ip serveur
	 * @param    string $dolibarr_main_db_user 	Nom user a creer
	 * @param    string $dolibarr_main_db_pass 	Mot de passe user a creer
	 * @param    string $dolibarr_main_db_name 	Database name where user must be granted
	 * @return   int                            <0 if KO, >=0 if OK
	 */
	function DDLCreateUser(
		$dolibarr_main_db_host,
		$dolibarr_main_db_user,
		$dolibarr_main_db_pass,
		$dolibarr_main_db_name
	);

	/**
	 * Convert (by PHP) a PHP server TZ string date into a Timestamps date (GMT if gm=true)
	 * 19700101020000 -> 3600 with TZ+1 and gmt=0
	 * 19700101020000 -> 7200 whaterver is TZ if gmt=1
	 *
	 * @param	string			$string		Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 * @param	bool			$gm			1=Input informations are GMT values, otherwise local to server TZ
	 * @return	int|string					Date TMS or ''
	 */
	function jdate($string, $gm=false);

	/**
	 * Encrypt sensitive data in database
	 * Warning: This function includes the escape, so it must use direct value
	 *
	 * @param   string 			$fieldorvalue 	Field name or value to encrypt
	 * @param  	int 			$withQuotes 	Return string with quotes
	 * @return 	string                     		XXX(field) or XXX('value') or field or 'value'
	 */
	function encrypt($fieldorvalue, $withQuotes = 0);

	/**
	 * Validate a database transaction
	 *
	 * @param   string 			$log 			Add more log to default log line
	 * @return	int                				1 if validation is OK or transaction level no started, 0 if ERROR
	 */
	function commit($log = '');

	/**
	 * List information of columns into a table.
	 *
	 * @param   string 			$table 			Name of table
	 * @return  array                			Array with inforation on table
	 */
	function DDLInfoTable($table);

	/**
	 * Free last resultset used.
	 *
	 * @param  	resource 		$resultset 		Fre cursor
	 * @return  void
	 */
	function free($resultset = null);

	/**
	 * Close database connexion
	 *
	 * @return  boolean     					True if disconnect successfull, false otherwise
	 * @see     connect
	 */
	function close();

	/**
	 * Return last query in error
	 *
	 * @return  string    lastqueryerror
	 */
	function lastqueryerror();

	/**
	 * Return connexion ID
	 *
	 * @return  string      Id connexion
	 */
	function DDLGetConnectId();

	/**
	 * Renvoie la ligne courante (comme un objet) pour le curseur resultset
	 *
	 * @param   resource $resultset Curseur de la requete voulue
	 * @return  Object                    Object result line or false if KO or end of cursor
	 */
	function fetch_object($resultset);

	/**
	 * Select a database
	 *
	 * @param	string $database Name of database
	 * @return  boolean            true if OK, false if KO
	 */
	function select_db($database);

}
