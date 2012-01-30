<?php
/* Copyright (C) 2012   Stephen Larroque <lrq3000@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *      \file       htdocs/customfields/class/customfields.class.php
 *      \ingroup    customfields
 *      \brief      Core class file for the CustomFields module, all critical functions reside here
 *		\version    $Id: customfields.class.php, v1.2.6
 *		\author		Stephen Larroque
 */

// Include the config file (only used for $varprefix at this moment, so this class is pretty much self contained and independant)
include_once(DOL_DOCUMENT_ROOT."/customfields/conf/conf_customfields.lib.php");

// Put here all includes required by your class file
$langs->load('customfields@customfields'); // customfields standard language support
$langs->load('customfields-user@customfields'); // customfields language support for user's values (like enum, fields names, etc..)

/**
 *      \class      customfields
 *      \brief      Core class for the CustomFields module, all critical functions reside here
 */
class CustomFields // extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='customfields';			//!< Id that identify managed objects
	//var $table_element='customfields';	//!< Name of table without prefix where object is stored

	var $varprefix = 'cf_'; // prefix that will be prepended to the variables names for accessing the fields values

	var $id;


    /**
     *      Constructor
     *      @param      DB      Database handler
     *      @param      currentmodule        	Current module (facture/propal/etc.)
     */
    function CustomFields($DB, $currentmodule)
    {
	$this->db = $DB;
	$this->module = $currentmodule;
	$this->moduletable = MAIN_DB_PREFIX.$this->module."_customfields";

	global $fields_prefix;
	if (!empty($fields_prefix)) $this->varprefix = $fields_prefix;

	return 1;
    }


	// ============ FIELDS RECORDS MANAGEMENT ===========/

	//--------------- Lib Functions --------------------------

	/**
	 *	Similar to mysql_real_escape() but can be reversed and there's no need to be connected to the db
	 */
	function escape($str)
        {
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		return str_replace($search,$replace,$str);
        }

	/**
	 *	Reverse msql_real_escape() or the function above
	 *	UNUSED
	 */
	function reverse_escape($str)
	{
		$search=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');
		$replace=array("\\","\0","\n","\r","\x1a","'",'"');
		return str_replace($search,$replace,$str);
	}

	//--------------- Main Functions ---------------------

	/**
	 *      Fetch a record (or all records) from the database (meaning an instance of the custom fields, the values if you prefer)
	 *      @param	   id				id of the record to find (NOT rowid but fk_moduleid) - can be left empty if you want to fetch all the records
	 *      @param      notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int/null/obj/obj[]        	<0 if KO, null if no record is found, a record if only one is found, an array of records if OK
	 */
	function fetch($id=null, $notrigger=0)
	{
		// Get all the columns (custom fields), primary field included (that's why there's the true)
		$fields = $this->fetchAllCustomFields(true);

		// Forging the SQL statement - we set all the column_name to fetch (because Dolibarr wants to avoid SELECT *, so we must name the columns we fetch)
		foreach ($fields as $field) {
			$keys[] = $field->column_name;
		}
		$sqlfields = implode(',',$keys);

		$sql = "SELECT ".$sqlfields." FROM ".$this->moduletable;

		if ($id > 0) { // if we supplied an id, we fetch only this one record
			$sql .= " WHERE fk_".$this->module."=".$id." LIMIT 1";
		}

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHRECORD';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql, 'fetchRecord_CustomFields',$trigger);

		// Filling the record object
		if ($resql < 0) { // if there's an error
			return $resql; // we return the error code
		} else { // else we fill the record
			$num = $this->db->num_rows($resql); // number of results returned (number of records)
			// Several records returned = array() of objects
			if ($num > 1) {
				// Find the primary field (so that we can set the record's id)
				$prifield = $this->fetchPrimaryField($this->moduletable);
				$rowid = $prifield->column_name;

				$record = array();
				for ($i=0;$i < $num;$i++) {
					$obj = $this->db->fetch_object($resql);
					$obj->id = $obj->$prifield; // set the record's id
					$record[$obj->id] = $obj; // add the record to our records' array
				}
				$this->records = $record; // and we as well store the records as a property of the CustomFields class
			// Only one record returned = one object
			} elseif ($num == 1) {
				$record = $this->db->fetch_object($resql);

				// If we get only 1 result and $id is not set, this means that we are not looking for a particular record, we are fetching all records but we find only one. In this case, we must find the id by ourselves.
				if (!isset($id)) {
					$prifield = $this->fetchPrimaryField($this->moduletable); // find the primary field of the table
					$id = $record->$prifield; // set the id
				}

				$record->id = $id; // set the record's id
				$this->id = $id;
			// No record returned = null
			} else {
				$record = null;
			}
			$this->db->free($resql);

			// Return the field(s) or null
			return $record;
		}

	}

	/**	Fetch all the records from the database for the current module
	 *	there's no argument, and it's just an alias for fetch()
	 *	@return	int/null/obj[]		<0 if KO, null if no record found, an array of records if OK (even if only one record is found)
	 */
	function fetchAll($notrigger=0) {
		$records = $this->fetch(null, $notrigger);
		if ( !(is_array($records) or is_null($records) or is_integer($records)) ) { $records = array($records); } // we convert to an array if we've got only one field, and if it's not an error or null, functions relying on this one expect to get an array if OK
		return $records;
	}

	/**
	 *      Fetch any record in the database from any table (not just customfields)
	 *      @param	columns		one or several columns (separated by commas) to fetch
	 *      @param	table		table where to fetch from
	 *      @param	where		where clause (format: column='value'). Can put several where clause separated by AND or OR
	 *      @param	orderby	order by clause
	 *      @return     int or object or array of objects         	<0 if KO, object if one record found, array of objects if several records found
	 */
	function fetchAny($columns, $table, $where='', $orderby='', $limitby='', $notrigger=0)
	{

		$sql = "SELECT ".$columns." FROM ".$table;
		if (!empty($where)) {
			$sql.= " WHERE ".$where;
		}
		if (!empty($orderby)) {
			$sql.= " ORDER BY ".$orderby;
		}
		if (!empty($limitby)) {
			$sql.= " LIMIT ".$limitby;
		}

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHANY';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql, 'fetchAnyRecord_CustomFields',$trigger);

		// Filling the record object
		if ($resql < 0) { // if there's no error
			return $resql; // we return the error code
		} else { // else we fill the record
			$num = $this->db->num_rows($resql); // number of results returned (number of records)
			// Several records returned = array() of objects
			if ($num > 1) {
				$record = array();
				for ($i=0;$i < $num;$i++) {
					$record[] = $this->db->fetch_object($resql);
				}
			// Only one record returned = one object
			} elseif ($num == 1) {
				$record = $this->db->fetch_object($resql);
			// No record returned = null
			} else {
				$record = null;
			}
			$this->db->free($resql);

			// Return the record(s) or null
			return $record;
		}

	}


	/**
	 *      Insert/update a record in the database (meaning an instance of the custom fields, the values if you prefer)
	 *      @param	   object				Object containing all the form inputs to be processed to the database (so it mus contain the custom fields)
	 *      @param      notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int         	<0 if KO, >0 if OK
	 */
	function create($object, $notrigger=0)
	{
		// Get all the columns (custom fields)
		$fields = $this->fetchAllCustomFields();

		if (empty($fields)) return null;

		// Forging the SQL statement
		$sqlfields = '';
		foreach ($fields as $field) {
			$key = $this->varprefix.$field->column_name;
			if (!isset($object->$key)) {
				$key = $field->column_name;
			}

			//We need to fetch the correct value when we update a date field
			if($field->data_type == 'date') {
				$object->$key = $this->db->idate(dol_mktime(0, 0, 0, $object->{$key.'month'}, $object->{$key.'day'}, $object->{$key.'year'}));
		       }

			if ($object->$key) { // Only insert/update this field if it was submitted
				if ($sqlfields != '') { $sqlfields.=','; }
				$sqlfields.=$field->column_name."='".$this->escape($object->$key)."'";
			}
		}
		if ($sqlfields != '') { $sqlfields.=','; } // in the case that all fields are empty, this one can be the only one submitted, so we have to put the comma only if it's not alone (or else sql syntax error)
		$sqlfields.="fk_".$this->module."=".$object->id; // we add the object id (filtered by fetchAllCustomFields)

		$result = $this->fetch($object->id);

		if (!empty($result) and count($result) > 0) { // if the record already exists for this facture id, we update it
			$sql = "UPDATE ".$this->moduletable." SET ".$sqlfields." WHERE fk_".$this->module."=".$object->id;
		} else { // else we insert a new record
			$sql = "INSERT INTO ".$this->moduletable." SET ".$sqlfields;
		}

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_CREATEUPDATERECORD';
		}

		// Executing the SQL statement
		$rtncode = $this->executeSQL($sql, 'createOrUpdateRecord_CustomFields',$trigger);

		$this->id = $this->db->last_insert_id($this->moduletable);

		return $rtncode;
	}


	/**
	 *      Insert/update a record in the database (meaning an instance of the custom fields, the values if you prefer)
	 *      @param	   object				Object containing all the form inputs to be processed to the database (so it mus contain the custom fields)
	 *      @param      notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int         	<0 if KO, >0 if OK
	 */
	function update($object, $notrigger=0)
	{
		return $this->create($object,$notrigger);
	}

	/**
	 *      Delete a record in the database (meaning an instance of the custom fields, the values if you prefer)
	 *      @param	   id				id of the record to find (NOT rowid but fk_moduleid)
	 *      @param      notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int         	<0 if KO, >0 if OK
	 */
	function delete($id, $notrigger=0)
	{
		// Forging the SQL statement
		$sql = "DELETE FROM ".$this->moduletable." WHERE fk_".$this->module."=".$id;

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_DELETERECORD';
		}

		// Executing the SQL statement
		$rtncode = $this->executeSQL($sql, 'deleteRecord_CustomFields',$trigger);

		$this->id = $id;

		return $rtncode;
	}


	/**
	 *      Insert a record in the database from a clone, by duplicating an existing record (meaning an instance of the custom fields, the values if you prefer)
	 *      @param	   id				ID of the object to clone
	 *      @param	cloneid			ID of the new cloned object
	 *      @param      notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int         	<0 if KO, >0 if OK
	 */
	function createFromClone($id, $cloneid, $notrigger=0)
	{
		// Get all the columns (custom fields)
		$fields = $this->fetchAllCustomFields();

		$object = $this->fetch($id);

		$object->id = $cloneid; // Affecting the new id

		$rtncode = $this->create($object); // creating the new record

		return $rtncode;
	}


	// ============ FIELDS COLUMNS CONFIGURATION ===========/

	// ------------ Lib functions ---------------/

	/**
	*	Extract the size or value (if type is enum) from the column_type of the database field
	*  @param $column_type
	*  @return $size_or_value
	*/
       function getFieldSizeOrValue($column_type) {
	    preg_match('/[(]([^)]+)[)]/', $column_type, $matches);
	    return $matches[1];
       }

	/*	Execute an SQL statement, add it to the logfile and add an event trigger (or not)
	 *
	 *
	 *	@return -1 if error, object of the request if OK
	 */
	function executeSQL($sql, $eventname, $trigger=null) { // if $trigger is null, no trigger will be produced, else it will produce a trigger with the provided name
		// Executing the SQL statement
		dol_syslog(get_class($this)."::".$eventname." sql=".$sql, LOG_DEBUG); // Adding an event to the log
		$resql=$this->db->query($sql); // Issuing the sql statement to the db

		if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); } // Checking for errors

		// Managing trigger (if there's no error)
		if (! $error) {
			$id = $this->db->last_insert_id($this->moduletable);

			if (!empty($trigger)) {
				global $user, $langs, $conf; // required vars for the trigger
				//// Call triggers
				include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
				$interface=new Interfaces($this->db);
				$result=$interface->run_triggers($trigger,$this,$user,$langs,$conf);
				if ($result < 0) { $error++; $this->errors=$interface->errors; }
				//// End call triggers
			}
		}

		// Commit or rollback
		if ($error)  {
			foreach($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::".$eventname." ".$errmsg, LOG_ERR);
				$this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error; // error code : we return -1 multiplied by the number of errors (so if we have 5 errors we will get -5 as a return code)
		} else {
			$this->db->commit();
			return $resql;
		}
	}

	/*	Forge the sql command for createCustomFields and updateCustomFields (creation and update of a field's definition)
	*
	*
	*	@return $sql containing the forged sql statement
	*/
	function forgeSQLCustomField($fieldname, $type, $size, $nulloption, $defaultvalue = null, $customtype = null, $customdef, $id = null) {

		// Forging the SQL statement
		$sql = '';
		if (!empty($id)) { // if a field id was supplied, we forge an update sql statement, else we forge an add field sql statement
			$field = $this->fetchCustomField($id); // fetch the field by id (ordinal_position) so we can get the field name
			if ($fieldname != $field->column_name) { // if the name of the field changed, then we use the CHANGE keyword to rename the field and apply other statements
				$sql = "ALTER TABLE ".$this->moduletable." CHANGE ".$field->column_name." ".$fieldname." ";
			} else {
				$sql = "ALTER TABLE ".$this->moduletable." MODIFY ".$field->column_name." "; // else we just modify the field (no renaming with MODIFY keyword)
			}
		} else {
			$sql = "ALTER TABLE ".$this->moduletable." ADD ".$fieldname." ";
		}
		/*
		if (trim($size) == '') {
			$size = 0; // the default value for infinity is 0 (eg: text(0) equals unlimited text field)
		}*/
		if ($type == 'other' and !empty($customtype)) {
			$sql .= ' '.$customtype;
		} else {
			$sql .= ' '.$type;
		}
		if (!empty($size)) {
			$sql .= '('.$size.')'; // NOTE: $size can contain enum values too ! And some types (eg: text, boolean) do not need any size!
		} else {
			if ($type == 'varchar') $sql.= '(256)'; // One special case for the varchar : we define a specific default value of 256 chars (this is the only exception, non generic instruction in the  whole class! But it enhance a lot the ease of users who may forget to set a value)
		}
		if ($nulloption) {
			$sql .= ' null';
		} else {
			$sql .= ' not null';
		}
		if (!empty($defaultvalue)) {
			$defaultvalue = "'$defaultvalue'"; // we always quote the default value, for int the DBMS will automatically convert the string to an int value
			$sql .= ' default '.$defaultvalue;
		}
		if (!empty($customdef)) {
			$sql .= ' '.$customdef;
		}
		// Closing the SQL statement
		$sql .= ';';

		return $sql;
	}


	// ------------ Fields actions for management functions ---------------/

	/**
	 *      Initialize the customfields for this module (create the required table)
	 *
	 *
	 *	@return -1 if KO, 1 if OK
	 */
	function initCustomFields($notrigger = 0) {

		$reftable = MAIN_DB_PREFIX.$this->module; // the main module's table, we just add the dolibarr's prefix for db tables
		$prifield = $this->fetchPrimaryField($reftable); // we fetch the name of primary column of this module's table

		// Forging the SQL statement
		$sql = "CREATE TABLE ".$this->moduletable."(
		rowid                int(11) NOT NULL AUTO_INCREMENT,
		fk_".$this->module."       int(11) NOT NULL, -- id of the associated invoice/document
		PRIMARY KEY (rowid),
		KEY fk_".$this->module." (fk_".$this->module."),
		CONSTRAINT fk_".$this->module." FOREIGN KEY (fk_".$this->module.") REFERENCES ".$reftable." (".$prifield.") ON DELETE CASCADE ON UPDATE CASCADE
		) AUTO_INCREMENT=1 ;";

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_INITTABLE';
		}

		// Executing the SQL statement
		$rtncode = $this->executeSQL($sql, 'initCustomField',$trigger);

		// Good or bad returncode ?
		if ($rtncode < 0) {
			return $rtncode; // bad
		} else {
			return 1; // good
		}
	}

	/**
	 *      Check if the table exists
	 *
	 *	@return	< 0 if KO, false if false, true if OK
	 *
	 */
	function probeCustomFields($notrigger = 0) {

		// Forging the SQL statement
		$sql = "SELECT 1
		FROM INFORMATION_SCHEMA.TABLES
		WHERE TABLE_TYPE='BASE TABLE'
		AND TABLE_SCHEMA='".$this->db->database_name."'
		AND TABLE_NAME='".$this->moduletable."';";

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_PROBETABLE';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql, 'probeCustomField',$trigger);

		// Forging the result
		if ($resql < 0) { // if an error happened when executing the sql command, we return -1
			return $resql;
		} else { // else we check the result
			if ($this->db->num_rows($resql) > 0) { // if there is a result, then we return true (the table exists)
				return true;
			} else { // else it doesn't
				return false;
			}
		}
	}

	/**
	*    Fetch the field sql definition for a particular field or for all fields from the database (not the records! See fetch and fetchAll to fetch records) and return it as an array or as a single object, and populate the CustomFields class $fields property
	*    @param    id          			id of the field (ordinal_position of the sql column) OR string column_name of the field
	*    @param    nohide				defines if the system fields (primary field and foreign key) must be hidden in the fetched results
	*    @return     int/null/obj/obj[]         <0 if KO, null if no field found, one field object if only one field could be found, an array of fields objects if OK
	*/
       function fetchCustomField($id=null, $nohide=false, $notrigger=0) {

		// Forging the SQL statement
		$whereaddendum = '';
		if (isset($id)) {
			if (is_numeric($id) and $id > 0) { // if we supplied an id, we fetch only this one record
			$whereaddendum .= " AND c.ordinal_position = ".$id;
			} elseif (is_string($id) and !empty($id)) {
				$whereaddendum .= " AND c.column_name = '".$id."'";
			}
		}

		if (!$nohide) {
			$whereaddendum .= " AND c.column_name != 'rowid' AND c.column_name != 'fk_".$this->module."'";
		}

		$sql = "SELECT c.ordinal_position,c.column_name,c.column_default,c.is_nullable,c.data_type,c.column_type,c.character_maximum_length,
		k.referenced_table_name, k.referenced_column_name, k.constraint_name,
                s.index_name
		FROM information_schema.COLUMNS as c
		LEFT JOIN information_schema.key_column_usage as k
		ON (k.column_name=c.column_name AND k.table_name=c.table_name AND k.table_schema=c.table_schema)
                LEFT JOIN information_schema.statistics as s
                ON (s.column_name=c.column_name AND s.table_name=c.table_name AND s.table_schema=c.table_schema)
		WHERE c.table_schema = '".$this->db->database_name."' AND c.table_name = '".$this->moduletable."' ".$whereaddendum."
		ORDER BY c.ordinal_position;"; // We filter the reserved columns so that the user  cannot alter them, even by mistake and we get only the specified field by id

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHFIELD';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql,"fetchCustomField", $trigger);

		// Filling the field object
		if ($resql < 0) { // if there's no error
			return $resql; // we return the error code

		} else { // else we fill the field
			$num = $this->db->num_rows($resql); // number of lines returned as a result to our sql statement

			// Several fields columns returned = array() of field objects
			if ($num > 1) {
				$field = array();
				for ($i=0;$i < $num;$i++) {
					$obj = $this->db->fetch_object($resql); // we retrieve the data line
					$obj->size = $this->getFieldSizeOrValue($obj->column_type); // add the real size of the field (character_maximum_length is not reliable for that goal)
					$obj->id = $obj->ordinal_position; // set the id (ordinal position in the database's table)
					$field[$obj->id] = $obj; // we store the field object in an array

					$column_name = $obj->column_name; // we get the column name of the field
					$this->fields->$column_name = $obj; // and we as well store the field as a property of the CustomFields class
				}
			// Only one field returned = one field object
			} elseif ($num == 1) {
				$field = $this->db->fetch_object($resql);

				$field->size = $this->getFieldSizeOrValue($field->column_type); // add the real size of the field (character_maximum_length is not reliable for that goal)
				$field->id = $field->ordinal_position; // set the id (ordinal position in the database's table)

				$column_name = $field->column_name; // we get the column name of the field
				$this->fields->$column_name = $field; // and we as well store the field as a property of the CustomFields class
			// No field returned = null
			} else {
				$field = null;
			}

			$this->db->free($resql);

			// Return the field
			return $field;
		}
       }

       /**
	*    Fetch ALL the fields sql definitions from the database (not the records! See fetch and fetchAll to fetch records)
	*    @param     nohide	defines if the system fields (primary field and foreign key) must be hidden in the fetched results
	*    @return     int/null/obj[]         <0 if KO, null if no field found, an array of fields objects if OK (even if only one field is found)
	*/
       function fetchAllCustomFields($nohide=false, $notrigger=0) {
		$fields = $this->fetchCustomField(null, $nohide, $notrigger);
		if ( !(is_array($fields) or is_null($fields) or is_integer($fields)) ) { $fields = array($fields); } // we convert to an array if we've got only one field, functions relying on this one expect to get an array if OK
		return $fields;
       }

	/**	Fetch constraints and foreign keys
	 *	@return <0 if KO, constraints[] array of constrained fields if OK
	 *	== UNUSED ==
	 *
	 */
       function fetchConstraints($notrigger = 0) {

		// Forging the SQL statement
		$sql = "SELECT
				CONCAT(table_name, '.', column_name) as 'foreign key',
				CONCAT(referenced_table_name, '.', referenced_column_name) as 'references',
				table_name, column_name, referenced_table_name, referenced_column_name
				FROM information_schema.key_column_usage
				WHERE referenced_table_name is not null
					AND table_schema = '".$this->db->database_name."'
					AND table_name = '".$this->moduletable."';";

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHCONSTRAINTS';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql,"fetchConstraints", $trigger);

		// Filling in all the fetched fields into an array of fields objects
		if ($resql < 0) { // if there's an error in the SQL
			return $resql; // return the error code
		} else {
			$constraints = null;
			if ($this->db->num_rows($resql) > 0) {
				$num = $this->db->num_rows($resql);
				for ($i=0;$i < $num;$i++) {
					$obj = $this->db->fetch_object($resql); // we retrieve the data line
					$name = $obj->column_name;
					$constaints->$name = $obj; // we store the field object in an array
				}
			}
			$this->db->free($resql);

			return $constaints; // we return an array of constraints objects
		}
       }

       /**
	*    Load the sql informations about a field from the database
	*    @param	    table		table where to search in
	*    @param     name      column_name to search
	*    @return     obj         <-1 if KO, field if OK
	*/
       function fetchReferencedField($table='', $name='', $notrigger = 0) {

		if (!empty($table)) {
			$sqltable = $table;
		} else {
			$sqltable = $this->moduletable;
		}
		if (!empty($name)) {
			$sqlname = $name;
		} else {
			$sqlname = $this->fetchPrimaryField($sqltable); // if no referenced column name defined, we get the name of the primary field of the referenced table
		}
		// Forging the SQL statement
		$sql = "SELECT c.ordinal_position,c.column_name,c.column_default,c.is_nullable,c.data_type,c.column_type,c.character_maximum_length,
		k.referenced_table_name, k.referenced_column_name, k.constraint_name,
                s.index_name
		FROM information_schema.COLUMNS as c
		LEFT JOIN information_schema.key_column_usage as k
		ON (k.column_name=c.column_name AND k.table_name=c.table_name AND k.table_schema=c.table_schema)
                LEFT JOIN information_schema.statistics as s
                ON (s.column_name=c.column_name AND s.table_name=c.table_name AND s.table_schema=c.table_schema)
		WHERE c.table_schema ='".$this->db->database_name."' AND c.table_name = '".$sqltable."' AND c.column_name = '".$sqlname."'
		ORDER BY c.ordinal_position;";

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHREFFIELD';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql,"fetchReferencedField", $trigger);

		// Filling the field object
		if ($resql < 0) { // if there's no error
			return $resql; // we return the error code

		} else { // else we fill the field
			$obj = null;
			if ($this->db->num_rows($resql) > 0) {
			    $obj = $this->db->fetch_object($resql);

			    $obj->size = $this->getFieldSizeOrValue($obj->column_type); // add the real size of the field (character_maximum_length is not reliable for that goal)
			}
			$this->db->free($resql);

			// Return the field
			return $obj;
		}
       }

	/**
	 *	Check in the database if a field column name exists in a table
	 *	@param	$table	table name
	 *	@param	$name	column name
	 *	@return	false if KO, true if OK
	 */
	function checkIfIdenticalFieldExistsInRefTable($table, $name) {
		$fieldref = $this->fetchReferencedField($table, $name);
		if (isset($fieldref)) {
			if ( !($fieldref <= 0)) { // Special feature : if the customfield has a column name similar to one in the linked table, then we show the values of this field instead
				return true;
			 } else {
				return false;
			 }
		} else {
			return false;
		}
	}

       /**
	*    Load the records from a specified table and for the specified column name (plus another field with a column name identical to the $field->column_name)
	*    @param	   field		the constrained field (which contains a non-null referenced_column_name property)
	*    @return     obj[]         <-1 if KO, array of records if OK
	*/
       function fetchReferencedValuesList($field, $notrigger = 0) {

		// -- Forging the sql statement

		// First field : the referenced one
		$sqlfields = $field->referenced_column_name;
		$orderby = $field->referenced_column_name; // by default we order by this field (generally rowid)
		// Second field (if it exists) : one that has the same name as the customfield
		$realrefcolumn=explode('_', $field->column_name); // we take only the first part of the column name, before _ char (so you can name fkid_mylabel and it will look for the foreign fkid column)
		if ( $this->checkIfIdenticalFieldExistsInRefTable($field->referenced_table_name, $realrefcolumn[0]) ) { // Special feature : if the customfield has a column name similar to one in the linked table, then we show the values of this field instead
			$sqlfields.=','.$realrefcolumn[0];
			$orderby = $realrefcolumn[0]; // if a second field is found, we order by this one (eg: a list of name is better ordered alphabetically)
		}

		$sql = 'SELECT '.$sqlfields.' FROM '.$field->referenced_table_name.' ORDER BY '.$orderby.';';

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHREFVALUES';
		}

		// -- Executing the sql statement (fetching the referenced list)
		$resql = $this->executeSQL($sql,'fetchReferencedValuesList',$trigger);

		// -- Filling in all the fetched fields into an array of records objects
		if ($resql < 0) { // if there's an error in the SQL
			return $resql; // return the error code
		} else {
			$refarray = array();
			if ($this->db->num_rows($resql) > 0) {
				$num = $this->db->num_rows($resql);
				for ($i=0;$i < $num;$i++) {
					$obj = $this->db->fetch_object($resql); // we retrieve the data line
					$refarray[] = $obj; // we store the field object in an array
				}
			}
			$this->db->free($resql);

			return $refarray; // we return an array of records objects (for at least one field, maybe two because of the "special feature" - see above)
		}
	}

        /**
	*    Load a list of all the tables from dolibarr database
	*    @return     obj[]         <-1 if KO, array of tables if OK
	*/
       function fetchAllTables($notrigger = 0) {

		// Forging the SQL statement
		$sql = "SHOW TABLES;";

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHALLTABLES';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql,"fetchAllTables", $trigger);

		// Filling in all the fetched fields into an array of fields objects
		if ($resql < 0) { // if there's an error in the SQL
			return $resql; // return the error code
		} else {
			$tables = array();
			if ($this->db->num_rows($resql) > 0) {
				$num = $this->db->num_rows($resql);
				for ($i=0;$i < $num;$i++) {
					$obj = $this->db->fetch_array($resql);
					$tables[$obj[0]] = $obj[0]; // we store the first row (the column that contains all the table names)
				}
			}
			$this->db->free($resql);

			return $tables; // we return an array of tables
		}
       }

        /**
	*    Find the column that is the primary key of a table
	*    @param      id          id object
	*    @return     int or string         <-1 if KO, name of primary column if OK
	*/
       function fetchPrimaryField($table, $notrigger = 0) {

		// Forging the SQL statement
		$sql = "SELECT column_name
		FROM information_schema.COLUMNS
		WHERE TABLE_SCHEMA = '".$this->db->database_name."' AND TABLE_NAME = '".$table."' AND COLUMN_KEY = 'PRI';";

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_FETCHPRIMARYFIELD';
		}

		// Executing the SQL statement
		$resql = $this->executeSQL($sql,"fetchPrimaryField", $trigger);

		// Filling in all the fetched fields into an array of fields objects
		if ($resql < 0) { // if there's an error in the SQL
			return $resql; // return the error code
		} else {
			$tables = array();
			if ($this->db->num_rows($resql) > 0) {
				$obj = $this->db->fetch_array($resql);
			}
			$this->db->free($resql);

			return $obj[0]; // we return the string value of the column name of the primary field
		}
       }

	/*	Delete a custom field (and the associated foreign key and index if necessary)
	*	@param 	id	id of the customfield (ordinal position in sql database)
	*
	*	@return	< 0 if KO, > 0 if OK
	*/
	function deleteCustomField($id, $notrigger = 0) {

		// Fetch the customfield object (so that we get all required informations to proceed to deletion : column_name, index and foreign key constraints if any)
		$field = $this->fetchCustomField($id);
		// Get the column name from the id
		$fieldname = $field->column_name;

		// Delete the associated constraint if exists
		$this->deleteConstraint($id);

		// Forging the SQL statement
		$sql = "ALTER TABLE ".$this->moduletable." DROP COLUMN ".$fieldname;

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_DELETEFIELD';
		}

		// Executing the SQL statement
		$rtncode = $this->executeSQL($sql, 'deleteCustomField',$trigger);

		return $rtncode;
	}

	/**	Delete a constraint for a customfield
	 *	@param 	id	id of the customfield (ordinal position in sql database)
	 *
	 *	@return	-1 if KO, 1 if OK
	 */
	function deleteConstraint($id) {
		$rtncode1 = 1;
		$rtncode2 = 1;

		// Fetch customfield's informations
		$field = $this->fetchCustomField($id);

		// Delete the associated constraint if exists
		if (!empty($field->constraint_name)) {
			$sql = "ALTER TABLE ".$this->moduletable." DROP FOREIGN KEY ".$field->constraint_name;
			$rtncode1 = $this->executeSQL($sql, 'deleteCustomFieldConstsraint',null); // we need to execute this sql statement prior to any other one, because if we want to delete the column, we need first to delete the foreign key (this cannot be done with a single sql statement, you will get an error)
		}
		// Delete the associated index if exists
		if (!empty($field->index_name)) {
			$sql = "ALTER TABLE ".$this->moduletable." DROP INDEX ".$field->index_name;
			$rtncode2 = $this->executeSQL($sql, 'deleteCustomFieldIndex',null); // same as above for the constraint
		}

		// Return code : -1 error or 1 OK
		if ($rtncode1 < 0 or $rtncode2 < 0) {
			return -1;
		} else {
			return 1;
		}
	}

	/**	Create a field (column in the customfields table) (will update the field if it does not exists)
	 *	@param	fieldname	name of the custom field (column name)
	 *	@param	type		sql type of the custom field (column type)
	 *	@param	size		bits size of the custom field type (data type)
	 *	@param	nulloption	accepts null values?
	 *	@param	defaultvalue	default value for this field? (null by default)
	 *	@param	constraint	name of the table linked by foreign key (the referenced_table_name)
	 *	@param	customtype	custom sql definition for the type (replaces type and size or just type parameter depending if the size is supplied in the def, ie: int(11) )
	 *	@param	customdef	custom sql definition that will be appended to the definition generated automatically (so you can add sql parameters the author didn't foreseen)
	 *	@param	customsql	custom sql statement that will be executed after the creation/update of the custom field (so that you can make complex statements)
	 *	@param	fieldid		id of the field to update (ordinal position). Leave this null to create the custom field, supply it if you want to update (or just use updateCustomField which is a simpler alias)
	 *	@param	notrigger	do not activate triggers?
	 *
	 *	@return -1 if KO, 1 if OK
	 */
	function addCustomField($fieldname, $type, $size, $nulloption, $defaultvalue = null, $constraint = null, $customtype = null, $customdef = null, $customsql = null, $fieldid = null, $notrigger = 0) {

		// Cleaning input vars
		$defaultvalue = $this->db->escape(trim($defaultvalue));
		//$size = $this->db->escape(trim($size)); // NOTE: $size can contain enum values too !
		//$customtype = $this->db->escape(trim($customtype));
		//$customdef = $this->db->escape(trim($customdef));
		//$customsql = $this->db->escape(trim($customsql));

		if (!empty($fieldid)) {
			$mode = "update";
		} else {
			$mode = "add";
		}

		// Delete the associated constraint if exists
		if (!empty($fieldid)) {
			$this->deleteConstraint($fieldid);
		}

		// Automatically get the type of the field from constraint
		if (!empty($constraint)) {
			$prfieldname = $this->fetchPrimaryField($constraint);
			$prfield = $this->fetchReferencedField($constraint,$prfieldname);

			$type = $prfield->data_type;
			$nulloption = $prfield->is_nullable;
			$size = $prfield->size;
		}

		// Forging the SQL statement
		$sql = $this->forgeSQLCustomField($fieldname, $type, $size, $nulloption, $defaultvalue, $customtype, $customdef, $fieldid);

		// Trigger or not?
		if ($notrigger) {
			$trigger = null;
		} else {
			$trigger = strtoupper($this->module).'_CUSTOMFIELD_'.strtoupper($mode).'FIELD';
		}

		// Executing the SQL statement
		$rtncode1 = $this->executeSQL($sql, $mode.'CustomField',$trigger);

		// Executing the custom sql request if defined
		$rtncodec = 1;
		if (!empty($constraint)) {
			$sqlconstraint = 'ALTER TABLE '.$this->moduletable.' ADD CONSTRAINT fk_'.$fieldname.' FOREIGN KEY ('.$fieldname.') REFERENCES '.$constraint.'('.$prfield->column_name.');';
			$rtncodec = $this->executeSQL($sqlconstraint, $mode.'CustomField',$trigger);
		}

		$rtncode2 = 1;
		if (!empty($customsql)) {
			$rtncode2 = $this->executeSQL($customsql, $mode.'CustomField',$trigger);
		}

		// Return code : -1 error or 1 OK
		if ($rtncode1 < 0 or $rtncode2 < 0 or $rtncodec < 0) {
			return -1;
		} else {
			return 1;
		}
	}

	/*	Update a customfield's definition (will create the field if it does not exists)
	*	@param	fieldid	id of the field to edit (the ordinal position)
	*	@param	for the rest, see addCustomField
	*
	*	@return -1 if KO, 1 if OK
	*/
	function updateCustomField($fieldid, $fieldname, $type, $size, $nulloption, $defaultvalue, $constraint = null, $customtype = null, $customdef = null, $customsql = null, $notrigger = 0) {
		return $this->addCustomField($fieldname, $type, $size, $nulloption, $defaultvalue, $constraint, $customtype, $customdef, $customsql, $fieldid, $notrigger);
	}


	// ============ FIELDS PRINTING FUNCTIONS ===========/

	/**
	 *     Return HTML string to put an input field into a page
	 *     @param      field             Field object
	 *     @param      currentvalue           Current value of the parameter (will be filled in the value attribute of the HTML field)
	 *     @param      moreparam       To add more parametes on html input tag
	 *     @return       out			An html string ready to be printed
	 */
	function showInputField($field,$currentvalue=null,$moreparam='') {
		global $conf, $langs;

		$key=$field->column_name;
		$label=$langs->trans($key);
		$type=$field->data_type;
		if ($field->column_type == 'tinyint(1)') { $type = 'boolean'; }
		$size=$this->character_maximum_length;
		if (empty($currentvalue)) { $currentvalue = $field->column_default;}

		if ($type == 'date') {
		    $showsize=10;
		} elseif ($type == 'datetime') {
		    $showsize=19;
		} elseif ($type == 'int') {
		    $showsize=10;
		} else {
		    $showsize=round($size);
		    if ($showsize > 48) $showsize=48; // max show size limited to 48
		}

		$out = ''; // var containing the html output
		// Constrained field
		if (!empty($field->referenced_column_name)) {

			/*
			$tables = $this->fetchAllTables();
			$tables = array_merge(array('' => $langs->trans('None')), $tables); // Adding a none choice (to avoid choosing a constraint or just to delete one)
			$html=new Form($this->db);
			$out.=$html->selectarray($this->varprefix.$key,$tables,$field->referenced_table_name);
			*/

			// -- Fetch the records (list of values)
			$refarray = $this->fetchReferencedValuesList($field);

			// -- Print the list

			// Special feature : if the customfield has a column name similar to one in the linked table, then we show the values of this field instead
			$key1 = $field->referenced_column_name;
			if (count((array)$refarray[0]) > 1) {
				$realrefcolumn=explode('_', $field->column_name); // we take only the first part of the column name, before _ char (so you can name fkid_mylabel and it will look for the foreign fkid column)
				$key2 = $realrefcolumn[0];
			} else {
				$key2 = $field->referenced_column_name;
			}

			$out.='<select name="'.$this->varprefix.$key.'">';
			$out.='<option value=""></option>'; // Empty option
			foreach ($refarray as $ref) {
				if ($ref->$key1 == $currentvalue) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
				$out.='<option value="'.$ref->$key1.'" '.$selected.'>'.$ref->$key2.'</option>';
			}
			$out.='</select>';

		// Normal non-constrained fields
		} else {
			if ($type == 'varchar') {
				$out.='<input type="text" name="'.$this->varprefix.$key.'" size="'.$showsize.'" maxlength="'.$size.'" value="'.$currentvalue.'"'.($moreparam?$moreparam:'').'>';
			} elseif ($type == 'text') {
				require_once(DOL_DOCUMENT_ROOT."/core/class/doleditor.class.php");
				$doleditor=new DolEditor($this->varprefix.$key,$currentvalue,'',200,'dolibarr_notes','In',false,false,$conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_SOCIETE,5,100);
				$out.=$doleditor->Create(1);
			} elseif ($type == 'date') {
				//$out.=' (YYYY-MM-DD)';
				$html=new Form($db);
				$out.=$html->select_date($currentvalue,$this->varprefix.$key,0,0,1,$this->varprefix.$key,1,1,1);
			} elseif ($type == 'datetime') {
				//$out.=' (YYYY-MM-DD HH:MM:SS)';
				if (empty($currentvalue)) { $currentvalue = 'YYYY-MM-DD HH:MM:SS'; }
				$out.='<input type="text" name="'.$this->varprefix.$key.'" size="'.$showsize.'" maxlength="'.$size.'" value="'.$currentvalue.'"'.($moreparam?$moreparam:'').'>';
			} elseif ($type == 'enum') {
				$out.='<select name="'.$this->varprefix.$key.'">';
				// cleaning out the enum values and exploding them into an array
				$values = trim($field->size);
				$values = str_replace("'", "", $values); // stripping single quotes
				$values = str_replace('"', "", $values); // stripping double quotes
				$values = explode(',', $values); // values of an enum are stored at the same place as the size of the other types. We explode them into an array (easier to walk and process)
				foreach ($values as $value) {
					if ($value == $currentvalue) {
						$selected = 'selected="selected"';
					} else {
						$selected = '';
					}
					$out.='<option value="'.$value.'" '.$selected.'>'.$langs->trans($value).'</option>';
				}
				$out.='</select>';
			} elseif ($type == 'boolean') {
				$out.='<select name="'.$this->varprefix.$key.'">';
				$out.='<option value="1" '.($currentvalue=='1'?'selected="selected"':'').'>'.$langs->trans("True").'</option>';
				$out.='<option value="false" '.($currentvalue=='false'?'selected="selected"':'').'>'.$langs->trans("False").'</option>';
				$out.='</select>';

			// Any other field
			} else { // for all other types (custom types and other undefined), we use a basic text input
				$out.='<input type="text" name="'.$this->varprefix.$key.'" size="'.$showsize.'" maxlength="'.$size.'" value="'.$currentvalue.'"'.($moreparam?$moreparam:'').'>';
			}
		}

	    return $out;
	}

	/**
	 *	Draw an input form (same as showInputField but produce a full form with an edit button and an action)
	 *	@param	$field	field object
	 *	@param	$currentvalue	current value of the field (will be set in the value attribute of the HTML input field)
	 *	@param	$page	URL of the page that will process the action (by default, the same page)
	 *	@param	$moreparam	More parameters
	 *	@return	$out			An html form ready to be printed
	 */
	function showInputForm($field, $currentvalue=null, $page=null, $moreparam='') {
		global $langs;

		$out = '';

		if (empty($page)) { $page = $_SERVER["PHP_SELF"]; }
		$name = $this->varprefix.$field->column_name;
		$out.='<form method="post" action="'.$page.'" name="form_'.$name.'">';
		$out.='<input type="hidden" name="action" value="set_'.$name.'">';
		$out.='<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$out.='<table class="nobordernopadding" cellpadding="0" cellspacing="0">';
		$out.='<tr><td>';
		$out.=$this->showInputField($field, $currentvalue, $moreparam);
		$out.='</td>';
		$out.='<td align="left"><input type="submit" class="button" value="'.$langs->trans("Modify").'"></td>';
		$out.='</tr></table></form>';

		return $out;
	}

	/**
	 *     Return HTML string to print a record's data
	 *     @param	field	field object
	 *     @param      	value           Value to show
	 *     @param	outputlangs		the language to use to find the right translation
	 *     @param      	moreparam       To add more parametes on html input tags
	 *     @return	html				An html string ready to be printed (without input fields, just html text)
	 */
	function printField($field, $value, $outputlangs='', $moreparam='') {
		if ($outputlangs == '') {
			global $langs;
			$outputlangs = $langs;
		}

		$out = '';
		if (isset($value)) {
			// Constrained field
			if (!empty($field->referenced_column_name) and !empty($value)) {
				// Special feature : if the customfield has a column name similar to one in the linked table, then we show the values of this field instead
				$realrefcolumn=explode('_', $field->column_name); // we take only the first part of the column name, before _ char (so you can name fkid_mylabel and it will look for the foreign fkid column)
				if ( $this->checkIfIdenticalFieldExistsInRefTable($field->referenced_table_name, $realrefcolumn[0]) ) {
					// Constructing the sql statement
					$column = $realrefcolumn[0];
					$table = $field->referenced_table_name;
					$where = $field->referenced_column_name.'='.$value;

					// Fetching the record
					$record = $this->fetchAny($column, $table, $where);

					// Outputting the value
					$out.= $record->$column;
				// Else we just print out the value of the field
				} else {
					$out.=$value;
				}
			// Normal non-constrained field
			} else {
				// type enum (select box or yes/no box)
				if ($field->data_type == 'enum') {
					$out.=$outputlangs->trans($value);
				// type true/false
				} elseif ($field->column_type == 'tinyint(1)') {
					if ($value == '1') {
						$out.=$outputlangs->trans('True');
					} else {
						$out.=$outputlangs->trans('False');
					}
				// every other type
				} else {
					$out.=$value;
				}
			}
		}
		return $out;
	}

	/**
	 *     Return a non-HTML, simple text string ready to be printed into a PDF with the FPDF class or in ODT documents
	 *     @param	field	field object
	 *     @param      	value           Value to show
	 *	@param	outputlangs	for multilingual support
	 *     @param     	moreparam       To add more parameters on html input tags
	 *     @return	string				A text string ready to be printed (without input fields and without html entities, just simple text)
	 */
	function printFieldPDF($field, $value, $outputlangs='', $moreparam='') {
		$value=$this->printField($field, $value, $outputlangs, $moreparam);

		// Cleaning the html characters if the field contained some
		$value = preg_replace('/<br\s*\/?>/i', "", $value); // replace <br> into line breaks \n - fckeditor already outputs line returns, so we just remove the <br>
		$value = html_entity_decode($value, ENT_QUOTES, 'UTF-8'); // replace all html characters into text ones (accents, quotes, etc.) and directly into UTF8

		return $value;
	}

	/**
	 *	Simplify the printing of the value of a field by accepting a string field name instead of an object
	 *	@param	fieldname	string field name of the field to print
	 *	@param	value		value to show (current value of the field)
	 *	@param	outputlangs	for multilingual support
	 *	@param	moreparam	to add more parameters on html input tags
	 *	@return	html		An html string ready to be printed
	 */
	function simpleprintField($fieldname, $value, $outputlangs='', $moreparam='') {
		if (!is_string($fieldname)) {
			return -1;
		} else {
			if (!isset($this->fields->$fieldname)) {
				$field = $this->fetchCustomField($fieldname, true);
			} else {
				$field = $this->fields->$fieldname;
			}
			return $this->printField($field, $value, $outputlangs, $moreparam);
		}
	}

	/**
	 *	Same as simpleprintField but for PDF (without html entities)
	 *	@param	fieldname	string field name of the field to print
	 *	@param	value		value to show (current value of the field)
	 *	@param	outputlangs	for multilingual support
	 *	@param	moreparam	to add more parameters on html input tags
	 *     @return	string				A text string ready to be printed (without input fields and without html entities, just simple text)
	 */
	function simpleprintFieldPDF($fieldname, $value, $outputlangs='', $moreparam='') {
		if (!is_string($fieldname)) {
			return -1;
		} else {
			if (!isset($this->fields->$fieldname)) {
				$field = $this->fetchCustomField($fieldname, true);
			} else {
				$field = $this->fields->$fieldname;
			}
			return $this->printFieldPDF($field, $value, $outputlangs, $moreparam);
		}
	}

	/**
	 *	Take a field name and returns the right label for the field, either with the prefix or without. If none is found, we return the normal field name.
	 *	@param	fieldname	 a field name
	 *	@param	outputlangs	the language to use to show the right translation of the label
	 *	@return	string		a label for the field
	 *
	 */
	function findLabel($fieldname, $outputlangs = '') {
		if ($outputlangs == '') {
			global $langs;
			$outputlangs = $langs;
		}

		if ($outputlangs->trans($this->varprefix.$fieldname) != $this->varprefix.$fieldname) { // if we find a label for a code in the format : cf_something
		    return $outputlangs->trans($this->varprefix.$fieldname);
		} elseif ($outputlangs->trans($fieldname) != $fieldname) { // if we find a label for a code in the format : something
		    return $outputlangs->trans($fieldname);
		} else { // if no label could be found, we return the field name
		    return $fieldname;
		}
	}

	function findLabelPDF($fieldname, $outputlangs = '') {
		$fieldname = $this->findLabel($fieldname, $outputlangs); // or use transnoentities()?

		// Cleaning the html characters if the field contained some
		$fieldname = preg_replace('/<br\s*\/?>/i', "", $fieldname); // replace <br> into line breaks \n - fckeditor already outputs line returns, so we just remove the <br>
		$fieldname = html_entity_decode($fieldname, ENT_QUOTES, 'UTF-8'); // replace all html characters into text ones (accents, quotes, etc.) and directly into UTF8

		return $fieldname;
	}

}
?>
