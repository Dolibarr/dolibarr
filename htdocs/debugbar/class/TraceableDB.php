<?php
/* Copyright (C) 2023	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 *	\file       htdocs/debugbar/class/DataCollector/TraceableDB.php
 *	\brief      Class for debugbar DB
 *	\ingroup    debugbar
 */

require_once DOL_DOCUMENT_ROOT.'/core/db/DoliDB.class.php';

/**
 * TraceableDB class
 *
 * Used to log queries into DebugBar
 */
class TraceableDB extends DoliDB
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db; // cannot be protected because of parent declaration
	/**
	 * @var array Queries array
	 */
	public $queries;
	/**
	 * @var float 	Request start time in second + microseconds as decimal part (Example: 1712305485.1104)
	 */
	protected $startTime;
	/**
	 * @var int 	Request start memory
	 */
	protected $startMemory;
	/**
	 * @var string type
	 */
	public $type;
	/**
	 * @const Database label
	 */
	const LABEL = ''; // TODO: the right value should be $this->db::LABEL (but this is a constant? o_O)
	/**
	 * @const Version min database
	 */
	const VERSIONMIN = ''; // TODO: the same thing here, $this->db::VERSIONMIN is the right value

	/**
	 * Constructor
	 *
	 * @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db      = $db;
		$this->type    = $this->db->type;
		$this->queries = array();
	}

	/**
	 * Format a SQL IF
	 *
	 * @param   string $test Test string (example: 'cd.statut=0', 'field IS NULL')
	 * @param   string $resok resultat si test equal
	 * @param   string $resko resultat si test non equal
	 * @return	string                SQL string
	 */
	public function ifsql($test, $resok, $resko)
	{
		return $this->db->ifsql($test, $resok, $resko);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return datas as an array
	 *
	 * @param   resource $resultset    Resultset of request
	 * @return  array                  Array
	 */
	public function fetch_row($resultset)
	{
		// phpcs:enable
		return $this->db->fetch_row($resultset);
	}

	/**
	 * Convert (by PHP) a GM Timestamp date into a string date with PHP server TZ to insert into a date field.
	 * Function to use to build INSERT, UPDATE or WHERE predica
	 *
	 *   @param	    int		$param      Date TMS to convert
	 *	 @param		mixed	$gm			'gmt'=Input information are GMT values, 'tzserver'=Local to server TZ
	 *   @return	string      		Date in a string YYYY-MM-DD HH:MM:SS
	 */
	public function idate($param, $gm = 'tzserver')
	{
		return $this->db->idate($param, $gm);
	}

	/**
	 * Return last error code
	 *
	 * @return  string    lasterrno
	 */
	public function lasterrno()
	{
		return $this->db->lasterrno();
	}

	/**
	 * Start transaction
	 *
	 * @param	string	$textinlog		Add a small text into log. '' by default.
	 * @return  int         			1 if transaction successfully opened or already opened, 0 if error
	 */
	public function begin($textinlog = '')
	{
		return $this->db->begin($textinlog);
	}

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
	public function DDLCreateDb($database, $charset = '', $collation = '', $owner = '')
	{
		return $this->db->DDLCreateDb($database, $charset, $collation, $owner);
	}

	/**
	 * Return version of database server into an array
	 *
	 * @return	array        Version array
	 */
	public function getVersionArray()
	{
		return $this->db->getVersionArray();
	}

	/**
	 *  Convert a SQL request in Mysql syntax to native syntax
	 *
	 * @param   string $line   SQL request line to convert
	 * @param   string $type   Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @return  string         SQL request line converted
	 */
	public function convertSQLFromMysql($line, $type = 'ddl')
	{
		return $this->db->convertSQLFromMysql($line);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the number o flines into the result of a request INSERT, DELETE or UPDATE
	 *
	 * @param   resource $resultset    Curseur de la requete voulue
	 * @return 	int                    Number of lines
	 * @see    	num_rows()
	 */
	public function affected_rows($resultset)
	{
		// phpcs:enable
		return $this->db->affected_rows($resultset);
	}

	/**
	 * Return description of last error
	 *
	 * @return  string        Error text
	 */
	public function error()
	{
		return $this->db->error();
	}

	/**
	 *  List tables into a database
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Name of table filter ('xxx%')
	 *  @return	array					List of tables in an array
	 */
	public function DDLListTables($database, $table = '')
	{
		return $this->db->DDLListTables($database, $table);
	}

	/**
	 *  List tables into a database with table info
	 *
	 *  @param	string		$database	Name of database
	 *  @param	string		$table		Name of table filter ('xxx%')
	 *  @return	array					List of tables in an array
	 */
	public function DDLListTablesFull($database, $table = '')
	{
		return $this->db->DDLListTablesFull($database, $table);
	}

	/**
	 * Return last request executed with query()
	 *
	 * @return	string                    Last query
	 */
	public function lastquery()
	{
		return $this->db->lastquery();
	}

	/**
	 * Define sort criteria of request
	 *
	 * @param   string $sortfield List of sort fields
	 * @param   string $sortorder Sort order
	 * @return  string            String to provide syntax of a sort sql string
	 */
	public function order($sortfield = null, $sortorder = null)
	{
		return $this->db->order($sortfield, $sortorder);
	}

	/**
	 * Decrypt sensitive data in database
	 *
	 * @param    string $value Value to decrypt
	 * @return   string                    Decrypted value if used
	 */
	public function decrypt($value)
	{
		return $this->db->decrypt($value);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return datas as an array
	 *
	 * @param   resource $resultset    Resultset of request
	 * @return  array                  Array
	 */
	public function fetch_array($resultset)
	{
		// phpcs:enable
		return $this->db->fetch_array($resultset);
	}

	/**
	 * Return last error label
	 *
	 * @return	string    lasterror
	 */
	public function lasterror()
	{
		return $this->db->lasterror();
	}

	/**
	 * Escape a string to insert data
	 *
	 * @param   string $stringtoencode String to escape
	 * @return  string                        String escaped
	 */
	public function escape($stringtoencode)
	{
		return $this->db->escape($stringtoencode);
	}

	/**
	 *	Escape a string to insert data into a like
	 *
	 *	@param	string	$stringtoencode		String to escape
	 *	@return	string						String escaped
	 */
	public function escapeforlike($stringtoencode)
	{
		return $this->db->escapeforlike($stringtoencode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Get last ID after an insert INSERT
	 *
	 * @param	string 	$tab 		Table name concerned by insert. Ne sert pas sous MySql mais requis pour compatibilite avec Postgresql
	 * @param   string 	$fieldid 	Field name
	 * @return  int                	Id of row
	 */
	public function last_insert_id($tab, $fieldid = 'rowid')
	{
		// phpcs:enable
		return $this->db->last_insert_id($tab, $fieldid);
	}

	/**
	 * Return full path of restore program
	 *
	 * @return        string        Full path of restore program
	 */
	public function getPathOfRestore()
	{
		return $this->db->getPathOfRestore();
	}

	/**
	 *	Cancel a transaction and go back to initial data values
	 *
	 * 	@param	string			$log		Add more log to default log line
	 * 	@return	resource|int         		1 if cancellation is ok or transaction not open, 0 if error
	 */
	public function rollback($log = '')
	{
		return $this->db->rollback($log);
	}

	/**
	 * Execute a SQL request and return the resultset
	 *
	 * @param   string 	$query          SQL query string
	 * @param   int    	$usesavepoint   0=Default mode, 1=Run a savepoint before and a rollback to savepoint if error (this allow to have some request with errors inside global transactions).
	 *                                 	Note that with Mysql, this parameter is not used as Myssql can already commit a transaction even if one request is in error, without using savepoints.
	 * @param   string 	$type           Type of SQL order ('ddl' for insert, update, select, delete or 'dml' for create, alter...)
	 * @param	int		$result_mode	Result mode
	 * @return  resource               	Resultset of answer
	 */
	public function query($query, $usesavepoint = 0, $type = 'auto', $result_mode = 0)
	{
		$this->startTracing();

		$resql = $this->db->query($query, $usesavepoint, $type, $result_mode);

		$this->endTracing($query, $resql);

		return $resql;
	}

	/**
	 * Start query tracing
	 *
	 * @return     void
	 */
	protected function startTracing()
	{
		$this->startTime   = microtime(true);
		$this->startMemory = memory_get_usage(true);
	}

	/**
	 * End query tracing
	 *
	 * @param      string   $sql       query string
	 * @param      string   $resql     query result
	 * @return     void
	 */
	protected function endTracing($sql, $resql)
	{
		$endTime     = microtime(true);
		$duration    = $endTime - $this->startTime;
		$endMemory   = memory_get_usage(true);
		$memoryDelta = $endMemory - $this->startMemory;

		$this->queries[] = array(
			'sql'           => $sql,
			'duration'      => $duration,
			'memory_usage'  => $memoryDelta,
			'is_success'    => $resql ? true : false,
			'error_code'    => $resql ? null : $this->db->lasterrno(),
			'error_message' => $resql ? null : $this->db->lasterror()
		);
	}

	/**
	 * Connection to server
	 *
	 * @param   string $host database server host
	 * @param   string $login login
	 * @param   string $passwd password
	 * @param   string $name name of database (not used for mysql, used for pgsql)
	 * @param   int    $port Port of database server
	 * @return  resource            Database access handler
	 * @see     close()
	 */
	public function connect($host, $login, $passwd, $name, $port = 0)
	{
		return $this->db->connect($host, $login, $passwd, $name, $port);
	}

	/**
	 *    Define limits and offset of request
	 *
	 * @param   int $limit Maximum number of lines returned (-1=conf->liste_limit, 0=no limit)
	 * @param   int $offset Numero of line from where starting fetch
	 * @return  string            String with SQL syntax to add a limit and offset
	 */
	public function plimit($limit = 0, $offset = 0)
	{
		return $this->db->plimit($limit, $offset);
	}

	/**
	 * Return value of server parameters
	 *
	 * @param   string	$filter		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
	public function getServerParametersValues($filter = '')
	{
		return $this->db->getServerParametersValues($filter);
	}

	/**
	 * Return value of server status
	 *
	 * @param   string $filter 		Filter list on a particular value
	 * @return  array				Array of key-values (key=>value)
	 */
	public function getServerStatusValues($filter = '')
	{
		return $this->db->getServerStatusValues($filter);
	}

	/**
	 * Return collation used in database
	 *
	 * @return  string        Collation value
	 */
	public function getDefaultCollationDatabase()
	{
		return $this->db->getDefaultCollationDatabase();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return number of lines for result of a SELECT
	 *
	 * @param   resource $resultset    Resulset of requests
	 * @return 	int                    Nb of lines
	 * @see    	affected_rows()
	 */
	public function num_rows($resultset)
	{
		// phpcs:enable
		return $this->db->num_rows($resultset);
	}

	/**
	 * Return full path of dump program
	 *
	 * @return        string        Full path of dump program
	 */
	public function getPathOfDump()
	{
		return $this->db->getPathOfDump();
	}

	/**
	 * Return version of database client driver
	 *
	 * @return            string      Version string
	 */
	public function getDriverInfo()
	{
		return $this->db->getDriverInfo();
	}

	/**
	 * Return generic error code of last operation.
	 *
	 * @return    string        Error code (Examples: DB_ERROR_TABLE_ALREADY_EXISTS, DB_ERROR_RECORD_ALREADY_EXISTS...)
	 */
	public function errno()
	{
		return $this->db->errno();
	}

	/**
	 * Create a table into database
	 *
	 * @param        string $table 			Name of table
	 * @param        array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}> 	$fields 		Associative table [field name][table of descriptions]
	 * @param        string $primary_key 	Nom du champ qui sera la clef primaire
	 * @param        string $type 			Type de la table
	 * @param        array 	$unique_keys 	Tableau associatifs Nom de champs qui seront clef unique => valeur
	 * @param        array 	$fulltext_keys 	Tableau des Nom de champs qui seront indexes en fulltext
	 * @param        array $keys 			Tableau des champs cles noms => valeur
	 * @return       int                    Return integer <0 if KO, >=0 if OK
	 */
	public function DDLCreateTable($table, $fields, $primary_key, $type, $unique_keys = null, $fulltext_keys = null, $keys = null)
	{
		return $this->db->DDLCreateTable($table, $fields, $primary_key, $type, $unique_keys, $fulltext_keys, $keys);
	}

	/**
	 * Drop a table into database
	 *
	 * @param        string $table 			Name of table
	 * @return       int                    Return integer <0 if KO, >=0 if OK
	 */
	public function DDLDropTable($table)
	{
		return $this->db->DDLDropTable($table);
	}

	/**
	 * Return list of available charset that can be used to store data in database
	 *
	 * @return        array        List of Charset
	 */
	public function getListOfCharacterSet()
	{
		return $this->db->getListOfCharacterSet();
	}

	/**
	 * Create a new field into table
	 *
	 * @param    string $table 				Name of table
	 * @param    string $field_name 		Name of field to add
	 * @param    array{type:string,label:string,enabled:int|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$field_desc 		Tableau associatif de description du champ a inserer[nom du parameter][valeur du parameter]
	 * @param    string $field_position 	Optionnel ex.: "after champtruc"
	 * @return   int                        Return integer <0 if KO, >0 if OK
	 */
	public function DDLAddField($table, $field_name, $field_desc, $field_position = "")
	{
		return $this->db->DDLAddField($table, $field_name, $field_desc, $field_position);
	}

	/**
	 * Drop a field from table
	 *
	 * @param    string $table 				Name of table
	 * @param    string $field_name 		Name of field to drop
	 * @return   int                        Return integer <0 if KO, >0 if OK
	 */
	public function DDLDropField($table, $field_name)
	{
		return $this->db->DDLDropField($table, $field_name);
	}

	/**
	 * Update format of a field into a table
	 *
	 * @param    string 	$table 			Name of table
	 * @param    string 	$field_name 	Name of field to modify
	 * @param    array{type:string,label:string,enabled:int|string,position:int,notnull?:int,visible:int,noteditable?:int,default?:string,index?:int,foreignkey?:string,searchall?:int,isameasure?:int,css?:string,csslist?:string,help?:string,showoncombobox?:int,disabled?:int,arrayofkeyval?:array<int,string>,comment?:string}	$field_desc 	Array with description of field format
	 * @return   int                        Return integer <0 if KO, >0 if OK
	 */
	public function DDLUpdateField($table, $field_name, $field_desc)
	{
		return $this->db->DDLUpdateField($table, $field_name, $field_desc);
	}

	/**
	 * Return list of available collation that can be used for database
	 *
	 * @return        array        			List of Collation
	 */
	public function getListOfCollation()
	{
		return $this->db->getListOfCollation();
	}

	/**
	 * Return a pointer of line with description of a table or field
	 *
	 * @param    string 	$table 			Name of table
	 * @param    string 	$field 			Optionnel : Name of field if we want description of field
	 * @return   resource            		Resource
	 */
	public function DDLDescTable($table, $field = "")
	{
		return $this->db->DDLDescTable($table, $field);
	}

	/**
	 * Return version of database server
	 *
	 * @return            string      		Version string
	 */
	public function getVersion()
	{
		return $this->db->getVersion();
	}

	/**
	 * Return charset used to store data in database
	 *
	 * @return        string        		Charset
	 */
	public function getDefaultCharacterSetDatabase()
	{
		return $this->db->getDefaultCharacterSetDatabase();
	}

	/**
	 * Create a user and privileges to connect to database (even if database does not exists yet)
	 *
	 * @param    string $dolibarr_main_db_host 	Ip serveur
	 * @param    string $dolibarr_main_db_user 	Nom user a creer
	 * @param    string $dolibarr_main_db_pass 	Password user a creer
	 * @param    string $dolibarr_main_db_name 	Database name where user must be granted
	 * @return   int                            Return integer <0 if KO, >=0 if OK
	 */
	public function DDLCreateUser($dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name)
	{
		return $this->db->DDLCreateUser($dolibarr_main_db_host, $dolibarr_main_db_user, $dolibarr_main_db_pass, $dolibarr_main_db_name);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Convert (by PHP) a PHP server TZ string date into a Timestamps date (GMT if gm=true)
	 * 19700101020000 -> 3600 with TZ+1 and gmt=0
	 * 19700101020000 -> 7200 whatever is TZ if gmt=1
	 *
	 * @param	string			$string		Date in a string (YYYYMMDDHHMMSS, YYYYMMDD, YYYY-MM-DD HH:MM:SS)
	 * @param	bool			$gm			1=Input information are GMT values, otherwise local to server TZ
	 * @return	int|''						Date TMS or ''
	 */
	public function jdate($string, $gm = false)
	{
		// phpcs:enable
		return $this->db->jdate($string, $gm);
	}

	/**
	 * Encrypt sensitive data in database
	 * Warning: This function includes the escape and add the SQL simple quotes on strings.
	 *
	 * @param	string	$fieldorvalue	Field name or value to encrypt
	 * @param	int		$withQuotes		Return string including the SQL simple quotes. This param must always be 1 (Value 0 is bugged and deprecated).
	 * @return	string					XXX(field) or XXX('value') or field or 'value'
	 */
	public function encrypt($fieldorvalue, $withQuotes = 1)
	{
		return $this->db->encrypt($fieldorvalue, $withQuotes);
	}

	/**
	 * Validate a database transaction
	 *
	 * @param   string 			$log 			Add more log to default log line
	 * @return	int                				1 if validation is OK or transaction level no started, 0 if ERROR
	 */
	public function commit($log = '')
	{
		return $this->db->commit($log);
	}

	/**
	 * List information of columns into a table.
	 *
	 * @param   string 			$table 			Name of table
	 * @return  array                			Array with information on table
	 */
	public function DDLInfoTable($table)
	{
		return $this->db->DDLInfoTable($table);
	}

	/**
	 * Free last resultset used.
	 *
	 * @param  	resource 		$resultset 		Fre cursor
	 * @return  void
	 */
	public function free($resultset = null)
	{
		$this->db->free($resultset);
	}

	/**
	 * Close database connection
	 *
	 * @return  boolean     					True if disconnect successful, false otherwise
	 * @see     connect()
	 */
	public function close()
	{
		return $this->db->close();
	}

	/**
	 * Return last query in error
	 *
	 * @return  string    lastqueryerror
	 */
	public function lastqueryerror()
	{
		return $this->db->lastqueryerror();
	}

	/**
	 * Return connection ID
	 *
	 * @return  string      Id connection
	 */
	public function DDLGetConnectId()
	{
		return $this->db->DDLGetConnectId();
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Returns the current line (as an object) for the resultset cursor
	 *
	 * @param   resource|PgSql\Connection	 	$resultset    	Handler of the desired SQL request
	 * @return  Object                 							Object result line or false if KO or end of cursor
	 */
	public function fetch_object($resultset)
	{
		// phpcs:enable
		return $this->db->fetch_object($resultset);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Select a database
	 *
	 * @param	string $database     Name of database
	 * @return  boolean              true if OK, false if KO
	 */
	public function select_db($database)
	{
		// phpcs:enable
		return $this->db->select_db($database);
	}
}
