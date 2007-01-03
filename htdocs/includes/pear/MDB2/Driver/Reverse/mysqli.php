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
// | Author: Lukas Smith <smith@pooteeweet.org>                           |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'MDB2/Driver/Reverse/Common.php';

/**
 * MDB2 MySQLi driver for the schema reverse engineering module
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Reverse_mysqli extends MDB2_Driver_Reverse_Common
{
    /**
     * Array for converting MYSQLI_*_FLAG constants to text values
     * @var    array
     * @access public
     */
    var $flags = array(
        MYSQLI_NOT_NULL_FLAG        => 'not_null',
        MYSQLI_PRI_KEY_FLAG         => 'primary_key',
        MYSQLI_UNIQUE_KEY_FLAG      => 'unique_key',
        MYSQLI_MULTIPLE_KEY_FLAG    => 'multiple_key',
        MYSQLI_BLOB_FLAG            => 'blob',
        MYSQLI_UNSIGNED_FLAG        => 'unsigned',
        MYSQLI_ZEROFILL_FLAG        => 'zerofill',
        MYSQLI_AUTO_INCREMENT_FLAG  => 'auto_increment',
        MYSQLI_TIMESTAMP_FLAG       => 'timestamp',
        MYSQLI_SET_FLAG             => 'set',
        // MYSQLI_NUM_FLAG             => 'numeric',  // unnecessary
        // MYSQLI_PART_KEY_FLAG        => 'multiple_key',  // duplicatvie
        MYSQLI_GROUP_FLAG           => 'group_by'
    );

    /**
     * Array for converting MYSQLI_TYPE_* constants to text values
     * @var    array
     * @access public
     */
    var $types = array(
        MYSQLI_TYPE_DECIMAL     => 'decimal',
        246                     => 'decimal',
        MYSQLI_TYPE_TINY        => 'tinyint',
        MYSQLI_TYPE_SHORT       => 'int',
        MYSQLI_TYPE_LONG        => 'int',
        MYSQLI_TYPE_FLOAT       => 'float',
        MYSQLI_TYPE_DOUBLE      => 'double',
        // MYSQLI_TYPE_NULL        => 'DEFAULT NULL',  // let flags handle it
        MYSQLI_TYPE_TIMESTAMP   => 'timestamp',
        MYSQLI_TYPE_LONGLONG    => 'bigint',
        MYSQLI_TYPE_INT24       => 'mediumint',
        MYSQLI_TYPE_DATE        => 'date',
        MYSQLI_TYPE_TIME        => 'time',
        MYSQLI_TYPE_DATETIME    => 'datetime',
        MYSQLI_TYPE_YEAR        => 'year',
        MYSQLI_TYPE_NEWDATE     => 'date',
        MYSQLI_TYPE_ENUM        => 'enum',
        MYSQLI_TYPE_SET         => 'set',
        MYSQLI_TYPE_TINY_BLOB   => 'tinyblob',
        MYSQLI_TYPE_MEDIUM_BLOB => 'mediumblob',
        MYSQLI_TYPE_LONG_BLOB   => 'longblob',
        MYSQLI_TYPE_BLOB        => 'blob',
        MYSQLI_TYPE_VAR_STRING  => 'varchar',
        MYSQLI_TYPE_STRING      => 'char',
        MYSQLI_TYPE_GEOMETRY    => 'geometry',
    );

    // {{{ getTableFieldDefinition()

    /**
     * Get the stucture of a field into an array
     *
     * @param string    $table         name of table that should be used in method
     * @param string    $field_name     name of field that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableFieldDefinition($table, $field_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $result = $db->loadModule('Datatype', null, true);
        if (PEAR::isError($result)) {
            return $result;
        }
        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW COLUMNS FROM $table LIKE ".$db->quote($field_name);
        $columns = $db->queryAll($query, null, MDB2_FETCHMODE_ASSOC);
        if (PEAR::isError($columns)) {
            return $columns;
        }
        foreach ($columns as $column) {
            $column = array_change_key_case($column, CASE_LOWER);
            $column['name'] = $column['field'];
            unset($column['field']);
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $column['name'] = strtolower($column['name']);
                } else {
                    $column['name'] = strtoupper($column['name']);
                }
            } else {
                $column = array_change_key_case($column, $db->options['field_case']);
            }
            if ($field_name == $column['name']) {
                list($types, $length, $unsigned, $fixed) = $db->datatype->mapNativeDatatype($column);
                $notnull = false;
                if (empty($column['null']) || $column['null'] !== 'YES') {
                    $notnull = true;
                }
                $default = false;
                if (array_key_exists('default', $column)) {
                    $default = $column['default'];
                    if (is_null($default) && $notnull) {
                        $default = '';
                    }
                }
                $autoincrement = false;
                if (!empty($column['extra']) && $column['extra'] == 'auto_increment') {
                    $autoincrement = true;
                }

                $definition[0] = array(
                    'notnull' => $notnull,
                    'nativetype' => preg_replace('/^([a-z]+)[^a-z].*/i', '\\1', $column['type'])
                );
                if ($length > 0) {
                    $definition[0]['length'] = $length;
                }
                if (!is_null($unsigned)) {
                    $definition[0]['unsigned'] = $unsigned;
                }
                if (!is_null($fixed)) {
                    $definition[0]['fixed'] = $fixed;
                }
                if ($default !== false) {
                    $definition[0]['default'] = $default;
                }
                if ($autoincrement !== false) {
                    $definition[0]['autoincrement'] = $autoincrement;
                }
                foreach ($types as $key => $type) {
                    $definition[$key] = $definition[0];
                    if ($type == 'clob' || $type == 'blob') {
                        unset($definition[$key]['default']);
                    }
                    $definition[$key]['type'] = $type;
                    $definition[$key]['mdb2type'] = $type;
                }
                return $definition;
            }
        }

        return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
            'it was not specified an existing table column', __FUNCTION__);
    }

    // }}}
    // {{{ getTableIndexDefinition()

    /**
     * Get the stucture of an index into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableIndexDefinition($table, $index_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $index_name = $db->getIndexName($index_name);
        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table /*!50002 WHERE Key_name = ".$db->quote($index_name)." */";
        $result = $db->query($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        $definition = array();
        while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC))) {
            $row = array_change_key_case($row, CASE_LOWER);
            $key_name = $row['key_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $key_name = strtolower($key_name);
                } else {
                    $key_name = strtoupper($key_name);
                }
            }
            if ($index_name == $key_name) {
                if (!$row['non_unique']) {
                    return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'it was not specified an existing table index', __FUNCTION__);
                }
                $column_name = $row['column_name'];
                if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                    if ($db->options['field_case'] == CASE_LOWER) {
                        $column_name = strtolower($column_name);
                    } else {
                        $column_name = strtoupper($column_name);
                    }
                }
                $definition['fields'][$column_name] = array();
                if (!empty($row['collation'])) {
                    $definition['fields'][$column_name]['sorting'] = ($row['collation'] == 'A'
                        ? 'ascending' : 'descending');
                }
            }
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table index', __FUNCTION__);
        }
        return $definition;
    }

    // }}}
    // {{{ getTableConstraintDefinition()

    /**
     * Get the stucture of a constraint into an array
     *
     * @param string    $table      name of table that should be used in method
     * @param string    $index_name name of index that should be used in method
     * @return mixed data array on success, a MDB2 error on failure
     * @access public
     */
    function getTableConstraintDefinition($table, $index_name)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        if (strtolower($index_name) != 'primary') {
            $index_name = $db->getIndexName($index_name);
        }
        $table = $db->quoteIdentifier($table, true);
        $query = "SHOW INDEX FROM $table /*!50002 WHERE Key_name = ".$db->quote($index_name)." */";
        $result = $db->query($query);
        if (PEAR::isError($result)) {
            return $result;
        }
        $definition = array();
        while (is_array($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC))) {
            $row = array_change_key_case($row, CASE_LOWER);
            $key_name = $row['key_name'];
            if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                if ($db->options['field_case'] == CASE_LOWER) {
                    $key_name = strtolower($key_name);
                } else {
                    $key_name = strtoupper($key_name);
                }
            }
            if ($index_name == $key_name) {
                if ($row['non_unique']) {
                    return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                        'it was not specified an existing table constraint', __FUNCTION__);
                }
                if ($row['key_name'] == 'PRIMARY') {
                    $definition['primary'] = true;
                } else {
                    $definition['unique'] = true;
                }
                $column_name = $row['column_name'];
                if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
                    if ($db->options['field_case'] == CASE_LOWER) {
                        $column_name = strtolower($column_name);
                    } else {
                        $column_name = strtoupper($column_name);
                    }
                }
                $definition['fields'][$column_name] = array();
                if (!empty($row['collation'])) {
                    $definition['fields'][$column_name]['sorting'] = ($row['collation'] == 'A'
                        ? 'ascending' : 'descending');
                }
            }
        }
        $result->free();
        if (empty($definition['fields'])) {
            return $db->raiseError(MDB2_ERROR_NOT_FOUND, null, null,
                'it was not specified an existing table constraint', __FUNCTION__);
        }
        return $definition;
    }

    // }}}
    // {{{ tableInfo()

    /**
     * Returns information about a table or a result set
     *
     * @param object|string  $result  MDB2_result object from a query or a
     *                                 string containing the name of a table.
     *                                 While this also accepts a query result
     *                                 resource identifier, this behavior is
     *                                 deprecated.
     * @param int            $mode    a valid tableInfo mode
     *
     * @return array  an associative array with the information requested.
     *                 A MDB2_Error object on failure.
     *
     * @see MDB2_Driver_Common::setOption()
     */
    function tableInfo($result, $mode = null)
    {
        if (is_string($result)) {
           return parent::tableInfo($result, $mode);
        }

        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $resource = MDB2::isResultCommon($result) ? $result->getResource() : $result;
        if (!is_object($resource)) {
            return $db->raiseError(MDB2_ERROR_NEED_MORE_DATA, null, null,
                'Could not generate result resource', __FUNCTION__);
        }

        if ($db->options['portability'] & MDB2_PORTABILITY_FIX_CASE) {
            if ($db->options['field_case'] == CASE_LOWER) {
                $case_func = 'strtolower';
            } else {
                $case_func = 'strtoupper';
            }
        } else {
            $case_func = 'strval';
        }

        $count = @mysqli_num_fields($resource);
        $res = array();
        if ($mode) {
            $res['num_fields'] = $count;
        }

        $db->loadModule('Datatype', null, true);
        for ($i = 0; $i < $count; $i++) {
            $tmp = @mysqli_fetch_field($resource);

            $flags = '';
            foreach ($this->flags as $const => $means) {
                if ($tmp->flags & $const) {
                    $flags.= $means . ' ';
                }
            }
            if ($tmp->def) {
                $flags.= 'default_' . rawurlencode($tmp->def);
            }
            $flags = trim($flags);

            $res[$i] = array(
                'table'  => $case_func($tmp->table),
                'name'   => $case_func($tmp->name),
                'type'   => isset($this->types[$tmp->type])
                    ? $this->types[$tmp->type] : 'unknown',
                // http://bugs.php.net/?id=36579
                'length' => $tmp->length,
                'flags'  => $flags,
            );
            $mdb2type_info = $db->datatype->mapNativeDatatype($res[$i]);
            if (PEAR::isError($mdb2type_info)) {
               return $mdb2type_info;
            }
            $res[$i]['mdb2type'] = $mdb2type_info[0][0];
            if ($mode & MDB2_TABLEINFO_ORDER) {
                $res['order'][$res[$i]['name']] = $i;
            }
            if ($mode & MDB2_TABLEINFO_ORDERTABLE) {
                $res['ordertable'][$res[$i]['table']][$res[$i]['name']] = $i;
            }
        }

        return $res;
    }
}
?>