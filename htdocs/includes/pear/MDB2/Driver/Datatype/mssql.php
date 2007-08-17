<?php
// vim: set et ts=4 sw=4 fdm=marker:
// +----------------------------------------------------------------------+
// | PHP versions 4 and 5                                                 |
// +----------------------------------------------------------------------+
// | Copyright (c) 1998-2007 Manuel Lemos, Tomas V.V.Cox,                 |
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
// | Authors: Lukas Smith <smith@pooteeweet.org>                          |
// |          Daniel Convissor <danielc@php.net>                          |
// +----------------------------------------------------------------------+
//
// $Id$
//

require_once 'MDB2/Driver/Datatype/Common.php';

/**
 * MDB2 MS SQL driver
 *
 * @package MDB2
 * @category Database
 * @author  Lukas Smith <smith@pooteeweet.org>
 */
class MDB2_Driver_Datatype_mssql extends MDB2_Driver_Datatype_Common
{
    // {{{ _baseConvertResult()

    /**
     * general type conversion method
     *
     * @param mixed   $value refernce to a value to be converted
     * @param string  $type  specifies which type to convert to
     * @param boolean $rtrim [optional] when TRUE [default], apply rtrim() to text
     * @return object a MDB2 error on failure
     * @access protected
     */
    function _baseConvertResult($value, $type, $rtrim = true)
    {
        if (is_null($value)) {
            return null;
        }
        switch ($type) {
        case 'boolean':
            return $value == '1';
        case 'date':
            if (strlen($value) > 10) {
                $value = substr($value,0,10);
            }
            return $value;
        case 'time':
            if (strlen($value) > 8) {
                $value = substr($value,11,8);
            }
            return $value;
        }
        return parent::_baseConvertResult($value, $type, $rtrim);
    }
    // }}}
    // {{{ getTypeDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @param array $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access public
     */
    function getTypeDeclaration($field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        switch ($field['type']) {
        case 'text':
            $length = !empty($field['length'])
                ? $field['length'] : false;
            $fixed = !empty($field['fixed']) ? $field['fixed'] : false;
            return $fixed ? ($length ? 'CHAR('.$length.')' : 'CHAR('.$db->options['default_text_field_length'].')')
                : ($length ? 'VARCHAR('.$length.')' : 'TEXT');
        case 'clob':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 8000) {
                    return 'VARCHAR('.$length.')';
                }
             }
             return 'TEXT';
        case 'blob':
            if (!empty($field['length'])) {
                $length = $field['length'];
                if ($length <= 8000) {
                    return "VARBINARY($length)";
                }
            }
            return 'IMAGE';
        case 'integer':
            return 'INT';
        case 'boolean':
            return 'BIT';
        case 'date':
            return 'CHAR ('.strlen('YYYY-MM-DD').')';
        case 'time':
            return 'CHAR ('.strlen('HH:MM:SS').')';
        case 'timestamp':
            return 'CHAR ('.strlen('YYYY-MM-DD HH:MM:SS').')';
        case 'float':
            return 'FLOAT';
        case 'decimal':
            $length = !empty($field['length']) ? $field['length'] : 18;
            $scale = !empty($field['scale']) ? $field['scale'] : $db->options['decimal_places'];
            return 'DECIMAL('.$length.','.$scale.')';
        }
        return '';
    }

    // }}}
    // {{{ _getDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare a generic type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string $name   name the field to be declared.
     * @param array  $field  associative array with the name of the properties
     *      of the field being declared as array indexes. Currently, the types
     *      of supported field properties are as follows:
     *
     *      length
     *          Integer value that determines the maximum length of the text
     *          field. If this argument is missing the field should be
     *          declared to have the longest length allowed by the DBMS.
     *
     *      default
     *          Text value to be used as default for this field.
     *
     *      notnull
     *          Boolean flag that indicates whether this field is constrained
     *          to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     * @access protected
     */
    function _getDeclarationOptions($field)
    {
        $charset = empty($field['charset']) ? '' :
            ' '.$this->_getCharsetFieldDeclaration($field['charset']);

        $default = '';
        if (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $db =& $this->getDBInstance();
                if (PEAR::isError($db)) {
                    return $db;
                }
                $field['default'] = empty($field['notnull'])
                    ? null : $this->valid_default_values[$field['type']];
                if ($field['default'] === ''
                    && ($db->options['portability'] & MDB2_PORTABILITY_EMPTY_TO_NULL)
                ) {
                    $field['default'] = ' ';
                }
            }
            $default = ' DEFAULT '.$this->quote($field['default'], $field['type']);
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        if ($default == ' DEFAULT NULL' && $notnull == ' NULL') {
            $notnull = '';
        }

        $collation = empty($field['collation']) ? '' :
            ' '.$this->_getCollationFieldDeclaration($field['collation']);
        return $charset.$default.$notnull.$collation;
    }

    // }}}
    // {{{ _getIntegerDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @param string  $name   name the field to be declared.
     * @param string  $field  associative array with the name of the properties
     *                        of the field being declared as array indexes.
     *                        Currently, the types of supported field
     *                        properties are as follows:
     *
     *                       unsigned
     *                        Boolean flag that indicates whether the field
     *                        should be declared as unsigned integer if
     *                        possible.
     *
     *                       default
     *                        Integer value to be used as default for this
     *                        field.
     *
     *                       notnull
     *                        Boolean flag that indicates whether this field is
     *                        constrained to not be set to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    function _getIntegerDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $default = $autoinc = '';;
        if (!empty($field['autoincrement'])) {
            $autoinc = ' IDENTITY PRIMARY KEY';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }
            $default = ' DEFAULT '.$this->quote($field['default'], 'integer');
        } elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        if ($default == ' DEFAULT NULL' && $notnull == ' NULL') {
            $notnull = '';
        }
        if (!empty($field['unsigned'])) {
            $db->warnings[] = "unsigned integer field \"$name\" is being declared as signed integer";
        }
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$default.$notnull.$autoinc;
    }

    // }}}
    // {{{ _getCLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an character
     * large object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access public
     */
    function _getCLOBDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _getBLOBDeclaration()

    /**
     * Obtain DBMS specific SQL code portion needed to declare an binary large
     * object type field to be used in statements like CREATE TABLE.
     *
     * @param string $name name the field to be declared.
     * @param array $field associative array with the name of the properties
     *        of the field being declared as array indexes. Currently, the types
     *        of supported field properties are as follows:
     *
     *        length
     *            Integer value that determines the maximum length of the large
     *            object field. If this argument is missing the field should be
     *            declared to have the longest length allowed by the DBMS.
     *
     *        notnull
     *            Boolean flag that indicates whether this field is constrained
     *            to not be set to null.
     * @return string DBMS specific SQL code portion that should be used to
     *        declare the specified field.
     * @access protected
     */
    function _getBLOBDeclaration($name, $field)
    {
        $db =& $this->getDBInstance();
        if (PEAR::isError($db)) {
            return $db;
        }

        $notnull = empty($field['notnull']) ? ' NULL' : ' NOT NULL';
        $name = $db->quoteIdentifier($name, true);
        return $name.' '.$this->getTypeDeclaration($field).$notnull;
    }

    // }}}
    // {{{ _quoteBLOB()

    /**
     * Convert a text value into a DBMS specific format that is suitable to
     * compose query statements.
     *
     * @param string $value text string value that is intended to be converted.
     * @param bool $quote determines if the value should be quoted and escaped
     * @param bool $escape_wildcards if to escape escape wildcards
     * @return string  text string that represents the given argument value in
     *                 a DBMS specific format.
     * @access protected
     */
    function _quoteBLOB($value, $quote, $escape_wildcards)
    {
        if (!$quote) {
            return $value;
        }
        $value = bin2hex("0x".$this->_readFile($value));
        return $value;
    }

    // }}}
    // {{{ _mapNativeDatatype()

    /**
     * Maps a native array description of a field to a MDB2 datatype and length
     *
     * @param array  $field native field description
     * @return array containing the various possible types, length, sign, fixed
     * @access public
     */
    function _mapNativeDatatype($field)
    {
        // todo: handle length of various int variations
        $db_type = preg_replace('/\d/', '', strtolower($field['type']));
        $length = $field['length'];
        $type = array();
        // todo: unsigned handling seems to be missing
        $unsigned = $fixed = null;
        switch ($db_type) {
        case 'bit':
            $type[0] = 'boolean';
            break;
        case 'tinyint':
            $type[0] = 'integer';
            $length = 1;
            break;
        case 'smallint':
            $type[0] = 'integer';
            $length = 2;
            break;
        case 'int':
            $type[0] = 'integer';
            $length = 4;
            break;
        case 'bigint':
            $type[0] = 'integer';
            $length = 8;
            break;
        case 'datetime':
            $type[0] = 'timestamp';
            break;
        case 'float':
        case 'real':
        case 'numeric':
            $type[0] = 'float';
            break;
        case 'decimal':
        case 'money':
            $type[0] = 'decimal';
            $length = $field['numeric_precision'].','.$field['numeric_scale'];
            break;
        case 'text':
        case 'varchar':
            $fixed = false;
        case 'char':
            $type[0] = 'text';
            if ($length == '1') {
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
            } elseif (strstr($db_type, 'text')) {
                $type[] = 'clob';
            }
            if ($fixed !== false) {
                $fixed = true;
            }
            break;
        case 'image':
        case 'varbinary':
            $type[] = 'blob';
            $length = null;
            break;
        default:
            $db =& $this->getDBInstance();
            if (PEAR::isError($db)) {
                return $db;
            }
            return $db->raiseError(MDB2_ERROR_UNSUPPORTED, null, null,
                'unknown database attribute type: '.$db_type, __FUNCTION__);
        }

        if ((int)$length <= 0) {
            $length = null;
        }

        return array($type, $length, $unsigned, $fixed);
    }
    // }}}
}

?>