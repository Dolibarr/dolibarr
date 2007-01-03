<?php
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2006 Manuel Lemos, Tomas V.V.Cox,                 |
// | Stig. S. Bakken, Lukas Smith                                         |
// | All rights reserved.                                                 |
// +----------------------------------------------------------------------+
// | MDB2 is a merge of PEAR DB and Metabases that provides a unified DB  |
// | API as well as database abstraction for PHP applications.            |
// | This LICENSE is in the BSD license style.                            |
// |                                                                      |
// | Redistribution and use in source and binary forms, with or without   |
// | modification, are permitted provided that the following conditions   |
// | are met:                                                             |
// |                                                                      |
// | Redistributions of source code must retain the above copyright       |
// | notice, this list of conditions and the following disclaimer.        |
// |                                                                      |
// | Redistributions in binary form must reproduce the above copyright    |
// | notice, this list of conditions and the following disclaimer in the  |
// | documentation and/or other materials provided with the distribution. |
// |                                                                      |
// | Neither the name of Manuel Lemos, Tomas V.V.Cox, Stig. S. Bakken,    |
// | Lukas Smith nor the names of his contributors may be used to endorse |
// | or promote products derived from this software without specific prior|
// | written permission.                                                  |
// |                                                                      |
// | THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS  |
// | "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT    |
// | LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS    |
// | FOR A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE      |
// | REGENTS OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,          |
// | INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, |
// | BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS|
// |  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED  |
// | AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT          |
// | LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY|
// | WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE          |
// | POSSIBILITY OF SUCH DAMAGE.                                          |
// +----------------------------------------------------------------------+
// | Author: Frank M. Kromann <frank@kromann.info>                        |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'MDB2/Driver/Manager/Common.php';

// {{{ class MDB2_Driver_Manager_mssql
/**
 * MDB2 MSSQL driver for the management modules
 *
 * @package MDB2
 * @category Database
 * @author  Frank M. Kromann <frank@kromann.info>
 * @author  David Coallier <davidc@php.net>
 */
class MDB2_Driver_Manager_mssql extends MDB2_Driver_Manager_Common
{
    // {{{ createDatabase()
    /**
     * create a new database
     *
     * @param string $name name of the database that should be created
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createDatabase($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        $query = "CREATE DATABASE $name";
        if ($db->options['database_device']) {
            $query.= ' ON '.$db->options['database_device'];
            $query.= $db->options['database_size'] ? '=' .
                     $db->options['database_size'] : '';
        }
        return $db->standaloneQuery($query, null, true);
    }
    // }}}
    // {{{ dropDatabase()

    /**
     * drop an existing database
     *
     * @param string $name name of the database that should be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropDatabase($name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $name = $db->quoteIdentifier($name, true);
        return $db->standaloneQuery("DROP DATABASE $name", null, true);
    }

    // }}}
    // {{{ alterTable()

    /**
     * alter an existing table
     *
     * @param string $name         name of the table that is intended to be changed.
     * @param array $changes     associative array that contains the details of each type
     *                             of change that is intended to be performed. The types of
     *                             changes that are currently supported are defined as follows:
     *
     *                             name
     *
     *                                New name for the table.
     *
     *                            add
     *
     *                                Associative array with the names of fields to be added as
     *                                 indexes of the array. The value of each entry of the array
     *                                 should be set to another associative array with the properties
     *                                 of the fields to be added. The properties of the fields should
     *                                 be the same as defined by the Metabase parser.
     *
     *
     *                            remove
     *
     *                                Associative array with the names of fields to be removed as indexes
     *                                 of the array. Currently the values assigned to each entry are ignored.
     *                                 An empty array should be used for future compatibility.
     *
     *                            rename
     *
     *                                Associative array with the names of fields to be renamed as indexes
     *                                 of the array. The value of each entry of the array should be set to
     *                                 another associative array with the entry named name with the new
     *                                 field name and the entry named Declaration that is expected to contain
     *                                 the portion of the field declaration already in DBMS specific SQL code
     *                                 as it is used in the CREATE TABLE statement.
     *
     *                            change
     *
     *                                Associative array with the names of the fields to be changed as indexes
     *                                 of the array. Keep in mind that if it is intended to change either the
     *                                 name of a field and any other properties, the change array entries
     *                                 should have the new names of the fields as array indexes.
     *
     *                                The value of each entry of the array should be set to another associative
     *                                 array with the properties of the fields to that are meant to be changed as
     *                                 array entries. These entries should be assigned to the new values of the
     *                                 respective properties. The properties of the fields should be the same
     *                                 as defined by the Metabase parser.
     *
     *                            Example
     *                                array(
     *                                    'name' => 'userlist',
     *                                    'add' => array(
     *                                        'quota' => array(
     *                                            'type' => 'integer',
     *                                            'unsigned' => 1
     *                                        )
     *                                    ),
     *                                    'remove' => array(
     *                                        'file_limit' => array(),
     *                                        'time_limit' => array()
     *                                    ),
     *                                    'change' => array(
     *                                        'name' => array(
     *                                            'length' => '20',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 20,
     *                                            ),
     *                                        )
     *                                    ),
     *                                    'rename' => array(
     *                                        'sex' => array(
     *                                            'name' => 'gender',
     *                                            'definition' => array(
     *                                                'type' => 'text',
     *                                                'length' => 1,
     *                                                'default' => 'M',
     *                                            ),
     *                                        )
     *                                    )
     *                                )
     *
     * @param boolean $check     indicates whether the function should just check if the DBMS driver
     *                             can perform the requested table alterations if the value is true or
     *                             actually perform them otherwise.
     * @access public
     *
      * @return mixed MDB2_OK on success, a MDB2 error on failure
     */
    function alterTable($name, $changes, $check)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        foreach ($changes as $change_name => $change) {
            switch ($change_name) {
            case 'add':
                break;
            case 'remove':
                break;
            case 'name':
            case 'rename':
            case 'change':
            default:
                return $db->raiseError(MDB2_ERROR_CANNOT_ALTER, null, null,
                    'alterTable: change type "'.$change_name.'" not yet supported');
            }
        }

        if ($check) {
            return MDB2_OK;
        }

        $query = '';
        if (!empty($changes['add']) && is_array($changes['add'])) {
            foreach ($changes['add'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $query.= 'ADD ' . $db->getDeclaration($field['type'], $field_name, $field);
            }
        }

        if (!empty($changes['remove']) && is_array($changes['remove'])) {
            foreach ($changes['remove'] as $field_name => $field) {
                if ($query) {
                    $query.= ', ';
                }
                $field_name = $db->quoteIdentifier($field_name, true);
                $query.= 'DROP COLUMN ' . $field_name;
            }
        }

        if (!$query) {
            return MDB2_OK;
        }

        $name = $db->quoteIdentifier($name, true);
        return $db->exec("ALTER TABLE $name $query");
    }
    // }}}
    // {{{ listTables()
    /**
     * list all tables in the current database
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTables()
    {
        $db =& $this->getDBInstance();

        if (PEAR::isError($db)) {
            return $db;
        }

        $query = 'EXEC sp_tables @table_type = "\'TABLE\'"';
        $table_names = $db->queryCol($query, null, 2);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }
        $result = array();
        foreach ($table_names as $table_name) {
            if (!$this->_fixSequenceName($table_name, true)) {
                $result[] = $table_name;
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ?
                        'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
    // }}}
    // {{{ listTableFields()
    /**
     * list all fields in a tables in the current database
     *
     * @param string $table name of table that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTableFields($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quoteIdentifier($table, true);
        $db->setLimit(1);
        $result2 = $db->query("SELECT * FROM $table");
        if (PEAR::isError($result2)) {
            return $result2;
        }
        $result = $result2->getColumnNames();
        $result2->free();
        if (PEAR::isError($result)) {
            return $result;
        }
        return array_flip($result);
    }
    // }}}
    // {{{ listTableIndexes()

    /**
     * list all indexes in a table
     *
     * @param string    $table     name of table that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listTableIndexes($table)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $key_name = 'INDEX_NAME';
        $pk_name = 'PK_NAME';
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $key_name = strtolower($key_name);
                $pk_name  = strtolower($pk_name);
            } else {
                $key_name = strtoupper($key_name);
                $pk_name  = strtoupper($pk_name);
            }
        }
        $table = $db->quote($table, 'text');
        $query = "EXEC sp_statistics @table_name=$table";
        $indexes = $db->queryCol($query, 'text', $key_name);
        if (PEAR::isError($indexes)) {
            return $indexes;
        }
        $query = "EXEC sp_pkeys @table_name=$table";
        $pk_all = $db->queryCol($query, 'text', $pk_name);
        $result = array();
        foreach ($indexes as $index) {
            if (!in_array($index, $pk_all) && $index != null) {
                $result[$this->_fixIndexName($index)] = true;
            }
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_change_key_case($result, $db->options['field_case']);
        }
        return array_keys($result);
    }

    // }}}
    // {{{ listTableTriggers()
    /**
     * This function will be called to
     * display all the triggers from the current
     * database ($db->getDatabase()).
     *
     * @access public
     * @param  string $table      The name of the table from the
     *                            previous database to query against.
     * @return mixed Array of the triggers if the query
     *               is successful, otherwise, false which
     *               could be a db error if the db is not
     *               instantiated or could be the results
     *               of the error that occured during the
     *               query'ing of the sysobject module.
     */
    function listTableTriggers($table = null)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $table = $db->quote($table, 'text');
        $query = "SELECT name FROM sysobjects WHERE xtype = 'TR'";
        if (!is_null($table)) {
            $query .= "AND object_name(parent_obj) = $table";
        }

        $result = $db->queryCol($query);
        if (PEAR::isError($results)) {
            return $result;
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE &&
            $db->options['field_case'] == CASE_LOWER)
        {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ?
                'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
    // }}}
    // {{{ listViews()
    /**
     * This function is a simple method that lists
     * all the views that are set on a db instance
     * (The db connected to it)
     *
     * @access public
     * @return mixed Error on failure or array of views for a database.
     */
    function listViews()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT name
                   FROM sysobjects
                    WHERE xtype = 'V'";

        $result = $db->queryCol($query);
        if (PEAR::isError($results)) {
            return $result;
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE &&
            $db->options['field_case'] == CASE_LOWER)
        {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ?
                          'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
    // }}}
    // {{{ createSequence()
    /**
     * create sequence
     *
     * @param string    $seq_name     name of the sequence to be created
     * @param string    $start         start value of the sequence; default is 1
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function createSequence($seq_name, $start = 1)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        $seqcol_name = $db->quoteIdentifier($db->options['seqcol_name'], true);
        $query = "CREATE TABLE $sequence_name ($seqcol_name " .
                 "INT PRIMARY KEY CLUSTERED IDENTITY($start,1) NOT NULL)";

        $res = $db->exec($query);
        if (PEAR::isError($res)) {
            return $res;
        }

        if ($start == 1) {
            return MDB2_OK;
        }


        $query = "SET IDENTITY_INSERT $sequence_name ON ".
                 "INSERT INTO $sequence_name ($seqcol_name) VALUES ($start)";
        $res = $db->exec($query);

        if (!PEAR::isError($res)) {
            return MDB2_OK;
        }

        $result = $db->exec("DROP TABLE $sequence_name");
        if (PEAR::isError($result)) {
            return $db->raiseError($result, null, null,
                'createSequence: could not drop inconsistent sequence table');
        }

        return $db->raiseError($res, null, null,
            'createSequence: could not create sequence table');
    }
    // }}}
    // {{{ dropSequence()
    /**
     * This function drops an existing sequence
     *
     * @param string $seq_name name of the sequence to be dropped
     * @return mixed MDB2_OK on success, a MDB2 error on failure
     * @access public
     */
    function dropSequence($seq_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $sequence_name = $db->quoteIdentifier($db->getSequenceName($seq_name), true);
        return $db->exec("DROP TABLE $sequence_name");
    }
    // }}}
    // {{{ listSequences()
    /**
     * list all sequences in the current database
     *
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function listSequences()
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $query = "SELECT name FROM sysobjects WHERE xtype = 'U'";
        $table_names = $db->queryCol($query);
        if (PEAR::isError($table_names)) {
            return $table_names;
        }
        $result = array();
        foreach ($table_names as $table_name) {
            if ($sqn = $this->_fixSequenceName($table_name, true)) {
                $result[] = $sqn;
            }
        }
        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            $result = array_map(($db->options['field_case'] == CASE_LOWER ?
                          'strtolower' : 'strtoupper'), $result);
        }
        return $result;
    }
    // }}}
}
// }}}
?>
