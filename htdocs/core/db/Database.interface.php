<?php
/* Copyright (C) 2001		Fabien Seisen			<seisen@linuxfr.org>
 * Copyright (C) 2002-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Andre Cianfarani		<acianfa@free.fr>
<<<<<<< HEAD
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function ifsql($test, $resok, $resko);

=======
    public function ifsql($test, $resok, $resko);

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return datas as an array
	 *
	 * @param   resource $resultset Resultset of request
	 * @return  array                    Array
	 */
<<<<<<< HEAD
	function fetch_row($resultset);
=======
    public function fetch_row($resultset);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Convert (by PHP) a GM Timestamp date into a string date with PHP server TZ to insert into a date field.
	 * Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 * @param   int		$param 		Date TMS to convert
	 * @return  string            	Date in a string YYYYMMDDHHMMSS
	 */
<<<<<<< HEAD
	function idate($param);
=======
    public function idate($param);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return last error code
	 *
	 * @return  string    lasterrno
	 */
<<<<<<< HEAD
	function lasterrno();
=======
    public function lasterrno();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Start transaction
	 *
	 * @return  int         1 if transaction successfuly opened or already opened, 0 if error
	 */
<<<<<<< HEAD
	function begin();

=======
    public function begin();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function DDLCreateDb($database, $charset = '', $collation = '', $owner = '');
=======
    public function DDLCreateDb($database, $charset = '', $collation = '', $owner = '');
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return version of database server into an array
	 *
	 * @return	array        Version array
	 */
<<<<<<< HEAD
	function getVersionArray();
=======
    public function getVersionArray();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *  Convert a SQL request in Mysql syntax to native syntax
	 *
	 * @param   string $line SQL request line to convert
	 * @param   string $type Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return  string        SQL request line converted
	 */
<<<<<<< HEAD
	static function convertSQLFromMysql($line, $type = 'ddl');

	/**
	 * Renvoie le nombre de lignes dans le resultat d'une requete INSERT, DELETE ou UPDATE
	 *
	 * @param   resource $resultset Curseur de la requete voulue
	 * @return 	int            Nombre de lignes
	 * @see    	num_rows
	 */
	function affected_rows($resultset);
=======
	public static function convertSQLFromMysql($line, $type = 'ddl');

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the number of lines in the result of a request INSERT, DELETE or UPDATE
	 *
	 * @param   resource $resultset Curseur de la requete voulue
	 * @return 	int            Number of lines
	 * @see    	num_rows()
	 */
    public function affected_rows($resultset);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return description of last error
	 *
	 * @return  string        Error text
	 */
<<<<<<< HEAD
	function error();

=======
    public function error();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *  List tables into a database
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Nmae of table filter ('xxx%')
	 *  @return	array					List of tables in an array
	 */
<<<<<<< HEAD
	function DDLListTables($database, $table = '');
=======
    public function DDLListTables($database, $table = '');
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return last request executed with query()
	 *
	 * @return	string                    Last query
	 */
<<<<<<< HEAD
	function lastquery();
=======
    public function lastquery();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Define sort criteria of request
	 *
	 * @param   string $sortfield List of sort fields
	 * @param   string $sortorder Sort order
	 * @return  string            String to provide syntax of a sort sql string
	 */
<<<<<<< HEAD
	function order($sortfield = null, $sortorder = null);
=======
    public function order($sortfield = null, $sortorder = null);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Decrypt sensitive data in database
	 *
	 * @param    string $value Value to decrypt
	 * @return   string                    Decrypted value if used
	 */
<<<<<<< HEAD
	function decrypt($value);

=======
    public function decrypt($value);

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *    Return datas as an array
	 *
	 * @param   resource $resultset Resultset of request
	 * @return  array                    Array
	 */
<<<<<<< HEAD
	function fetch_array($resultset);
=======
    public function fetch_array($resultset);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return last error label
	 *
	 * @return	string    lasterror
	 */
<<<<<<< HEAD
	function lasterror();
=======
    public function lasterror();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Escape a string to insert data
	 *
	 * @param   string $stringtoencode String to escape
	 * @return  string                        String escaped
	 */
<<<<<<< HEAD
	function escape($stringtoencode);

=======
    public function escape($stringtoencode);

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Get last ID after an insert INSERT
	 *
	 * @param	string 	$tab 		Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param   string 	$fieldid 	Field name
	 * @return  int                	Id of row
	 */
<<<<<<< HEAD
	function last_insert_id($tab, $fieldid = 'rowid');
=======
    public function last_insert_id($tab, $fieldid = 'rowid');
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *    Return full path of restore program
	 *
	 * @return        string        Full path of restore program
	 */
<<<<<<< HEAD
	function getPathOfRestore();
=======
    public function getPathOfRestore();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *    Annulation d'une transaction et retour aux anciennes valeurs
	 *
	 * @param	string $log Add more log to default log line
	 * @return  int                1 si annulation ok ou transaction non ouverte, 0 en cas d'erreur
	 */
<<<<<<< HEAD
	function rollback($log = '');
=======
    public function rollback($log = '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Execute a SQL request and return the resultset
	 *
	 * @param   string $query SQL query string
	 * @param   int $usesavepoint 0=Default mode, 1=Run a savepoint before and a rollback to savepoint if error (this allow to have some request with errors inside global transactions).
<<<<<<< HEAD
	 *                                    Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
	 * @param   string $type Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return  resource                Resultset of answer
	 */
	function query($query, $usesavepoint = 0, $type = 'auto');
=======
	 *                            Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
	 * @param   string $type Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return  resource                Resultset of answer
	 */
    public function query($query, $usesavepoint = 0, $type = 'auto');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
	function connect($host, $login, $passwd, $name, $port = 0);
=======
    public function connect($host, $login, $passwd, $name, $port = 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *    Define limits and offset of request
	 *
	 * @param   int $limit Maximum number of lines returned (-1=conf->liste_limit, 0=no limit)
	 * @param   int $offset Numero of line from where starting fetch
	 * @return  string            String with SQL syntax to add a limit and offset
	 */
<<<<<<< HEAD
	function plimit($limit = 0, $offset = 0);
=======
    public function plimit($limit = 0, $offset = 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return value of server parameters
	 *
	 * @param   string	$filter		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
<<<<<<< HEAD
	function getServerParametersValues($filter = '');
=======
    public function getServerParametersValues($filter = '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return value of server status
	 *
	 * @param   string $filter 		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
<<<<<<< HEAD
	function getServerStatusValues($filter = '');
=======
    public function getServerStatusValues($filter = '');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return collation used in database
	 *
	 * @return  string        Collation value
	 */
<<<<<<< HEAD
	function getDefaultCollationDatabase();

=======
    public function getDefaultCollationDatabase();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return number of lines for result of a SELECT
	 *
	 * @param   resource $resultset Resulset of requests
	 * @return 	int                        Nb of lines
	 * @see    	affected_rows
	 */
<<<<<<< HEAD
	function num_rows($resultset);
=======
    public function num_rows($resultset);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return full path of dump program
	 *
	 * @return        string        Full path of dump program
	 */
<<<<<<< HEAD
	function getPathOfDump();
=======
    public function getPathOfDump();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return version of database client driver
	 *
	 * @return            string      Version string
	 */
<<<<<<< HEAD
	function getDriverInfo();
=======
    public function getDriverInfo();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return generic error code of last operation.
	 *
	 * @return    string        Error code (Exemples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
	 */
<<<<<<< HEAD
	function errno();

=======
    public function errno();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
	function DDLCreateTable($table, $fields, $primary_key, $type, $unique_keys = null, $fulltext_keys = null, $keys = null);

=======
    public function DDLCreateTable($table, $fields, $primary_key, $type, $unique_keys = null, $fulltext_keys = null, $keys = null);
    // phpcs:enable

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Drop a table into database
	 *
	 * @param        string $table 			Name of table
	 * @return       int                    <0 if KO, >=0 if OK
	 */
<<<<<<< HEAD
	function DDLDropTable($table);
=======
    public function DDLDropTable($table);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return list of available charset that can be used to store data in database
	 *
	 * @return        array        List of Charset
	 */
<<<<<<< HEAD
	function getListOfCharacterSet();

=======
    public function getListOfCharacterSet();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Create a new field into table
	 *
	 * @param    string $table 				Name of table
	 * @param    string $field_name 		Name of field to add
	 * @param    string $field_desc 		Tableau associatif de description du champ a inserer[nom du parametre][valeur du parametre]
	 * @param    string $field_position 	Optionnel ex.: "after champtruc"
	 * @return   int                        <0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function DDLAddField($table, $field_name, $field_desc, $field_position = "");

=======
    public function DDLAddField($table, $field_name, $field_desc, $field_position = "");
    // phpcs:enable

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Drop a field from table
	 *
	 * @param    string $table 				Name of table
	 * @param    string $field_name 		Name of field to drop
	 * @return   int                        <0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function DDLDropField($table, $field_name);

=======
    public function DDLDropField($table, $field_name);
    // phpcs:enable

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Update format of a field into a table
	 *
	 * @param    string 	$table 			Name of table
	 * @param    string 	$field_name 	Name of field to modify
	 * @param    string 	$field_desc 	Array with description of field format
	 * @return   int                        <0 if KO, >0 if OK
	 */
<<<<<<< HEAD
	function DDLUpdateField($table, $field_name, $field_desc);
=======
    public function DDLUpdateField($table, $field_name, $field_desc);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return list of available collation that can be used for database
	 *
	 * @return        array        			List of Collation
	 */
<<<<<<< HEAD
	function getListOfCollation();

=======
    public function getListOfCollation();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return a pointer of line with description of a table or field
	 *
	 * @param    string 	$table 			Name of table
	 * @param    string 	$field 			Optionnel : Name of field if we want description of field
	 * @return   resource            		Resource
	 */
<<<<<<< HEAD
	function DDLDescTable($table, $field = "");
=======
    public function DDLDescTable($table, $field = "");
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return version of database server
	 *
	 * @return            string      		Version string
	 */
<<<<<<< HEAD
	function getVersion();
=======
    public function getVersion();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return charset used to store data in database
	 *
	 * @return        string        		Charset
	 */
<<<<<<< HEAD
	function getDefaultCharacterSetDatabase();

=======
    public function getDefaultCharacterSetDatabase();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Create a user and privileges to connect to database (even if database does not exists yet)
	 *
	 * @param    string $dolibarr_main_db_host 	Ip serveur
	 * @param    string $dolibarr_main_db_user 	Nom user a creer
	 * @param    string $dolibarr_main_db_pass 	Mot de passe user a creer
	 * @param    string $dolibarr_main_db_name 	Database name where user must be granted
<<<<<<< HEAD
	 * @return   int                            <0 if KO, >=0 if OK
	 */
	function DDLCreateUser(
		$dolibarr_main_db_host,
		$dolibarr_main_db_user,
		$dolibarr_main_db_pass,
		$dolibarr_main_db_name
	);
=======
     * @return   int                            <0 if KO, >=0 if OK
     */
    public function DDLCreateUser(
        $dolibarr_main_db_host,
        $dolibarr_main_db_user,
        $dolibarr_main_db_pass,
        $dolibarr_main_db_name
    );
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Convert (by PHP) a PHP server TZ string date into a Timestamps date (GMT if gm=true)
	 * 19700101020000 -> 3600 with TZ+1 and gmt=0
	 * 19700101020000 -> 7200 whaterver is TZ if gmt=1
	 *
	 * @param	string			$string		Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 * @param	bool			$gm			1=Input informations are GMT values, otherwise local to server TZ
	 * @return	int|string					Date TMS or ''
	 */
<<<<<<< HEAD
	function jdate($string, $gm=false);
=======
    public function jdate($string, $gm = false);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Encrypt sensitive data in database
	 * Warning: This function includes the escape, so it must use direct value
	 *
	 * @param   string 			$fieldorvalue 	Field name or value to encrypt
	 * @param  	int 			$withQuotes 	Return string with quotes
	 * @return 	string                     		XXX(field) or XXX('value') or field or 'value'
	 */
<<<<<<< HEAD
	function encrypt($fieldorvalue, $withQuotes = 0);
=======
    public function encrypt($fieldorvalue, $withQuotes = 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Validate a database transaction
	 *
	 * @param   string 			$log 			Add more log to default log line
	 * @return	int                				1 if validation is OK or transaction level no started, 0 if ERROR
	 */
<<<<<<< HEAD
	function commit($log = '');

=======
    public function commit($log = '');

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * List information of columns into a table.
	 *
	 * @param   string 			$table 			Name of table
<<<<<<< HEAD
	 * @return  array                			Array with inforation on table
	 */
	function DDLInfoTable($table);

	/**
=======
     * @return  array                			Array with inforation on table
     */
    public function DDLInfoTable($table);
    // phpcs:enable

    /**
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 * Free last resultset used.
	 *
	 * @param  	resource 		$resultset 		Fre cursor
	 * @return  void
	 */
<<<<<<< HEAD
	function free($resultset = null);
=======
    public function free($resultset = null);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Close database connexion
	 *
	 * @return  boolean     					True if disconnect successfull, false otherwise
	 * @see     connect
	 */
<<<<<<< HEAD
	function close();
=======
    public function close();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * Return last query in error
	 *
	 * @return  string    lastqueryerror
	 */
<<<<<<< HEAD
	function lastqueryerror();

=======
    public function lastqueryerror();

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Return connexion ID
	 *
	 * @return  string      Id connexion
	 */
<<<<<<< HEAD
	function DDLGetConnectId();

=======
    public function DDLGetConnectId();
    // phpcs:enable

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Renvoie la ligne courante (comme un objet) pour le curseur resultset
	 *
	 * @param   resource $resultset Curseur de la requete voulue
	 * @return  Object                    Object result line or false if KO or end of cursor
	 */
<<<<<<< HEAD
	function fetch_object($resultset);

=======
    public function fetch_object($resultset);
    // phpcs:enable

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 * Select a database
	 *
	 * @param	string $database Name of database
	 * @return  boolean            true if OK, false if KO
	 */
<<<<<<< HEAD
	function select_db($database);

=======
    public function select_db($database);
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
