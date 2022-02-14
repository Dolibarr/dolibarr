<?php

namespace ParseCsv;

use Illuminate\Support\Collection;
use ParseCsv\enums\FileProcessingModeEnum;
use ParseCsv\enums\SortEnum;
use ParseCsv\extensions\DatatypeTrait;

class Csv {

    /*
    https://github.com/parsecsv/parsecsv-for-php

    Fully conforms to the specifications lined out on Wikipedia:
    - http://en.wikipedia.org/wiki/Comma-separated_values

    Based on the concept of Ming Hong Ng's CsvFileParser class:
    - http://minghong.blogspot.com/2006/07/csv-parser-for-php.html

    (The MIT license)

    Copyright (c) 2014 Jim Myhrberg.

    Permission is hereby granted, free of charge, to any person obtaining a copy
    of this software and associated documentation files (the "Software"), to deal
    in the Software without restriction, including without limitation the rights
    to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
    copies of the Software, and to permit persons to whom the Software is
    furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in
    all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
    IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
    FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
    AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
    LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
    OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
    THE SOFTWARE.

    For code examples, please read the files within the 'examples' dir.
     */

    /**
     * Configuration
     * - set these options with $object->var_name = 'value';
     */

    /**
     * Header row:
     * Use first line/entry as field names
     *
     * @var bool
     */
    public $heading = true;

    /**
     * Override field names
     *
     * @var array
     */
    public $fields = array();

    /**
     * Sort CSV by this field
     *
     * @var string|null
     */
    public $sort_by = null;

    /**
     * Reverse the sort direction
     *
     * @var bool
     */
    public $sort_reverse = false;

    /**
     * Sort behavior passed to sort methods
     *
     * regular = SORT_REGULAR
     * numeric = SORT_NUMERIC
     * string  = SORT_STRING
     *
     * @var string|null
     */
    public $sort_type = SortEnum::SORT_TYPE_REGULAR;

    /**
     * Field delimiter character
     *
     * @var string
     */
    public $delimiter = ',';

    /**
     * Enclosure character
     *
     * This is useful for cell values that are either multi-line
     * or contain the field delimiter character.
     *
     * @var string
     */
    public $enclosure = '"';

    /**
     * Force enclosing all columns.
     *
     * If false, only cells that are either multi-line or
     * contain the field delimiter character are enclosed
     * in the $enclosure char.
     *
     * @var bool
     */
    public $enclose_all = false;

    /**
     * Basic SQL-Like conditions for row matching
     *
     * @var string|null
     */
    public $conditions = null;

    /**
     * Number of rows to ignore from beginning of data. If present, the heading
     * row is also counted (if $this->heading == true). In other words,
     * $offset == 1 and $offset == 0 have the same meaning in that situation.
     *
     * @var int|null
     */
    public $offset = null;

    /**
     * Limits the number of returned rows to the specified amount
     *
     * @var int|null
     */
    public $limit = null;

    /**
     * Number of rows to analyze when attempting to auto-detect delimiter
     *
     * @var int
     */
    public $auto_depth = 15;

    /**
     * Characters that should be ignored when attempting to auto-detect delimiter
     *
     * @var string
     */
    public $auto_non_chars = "a-zA-Z0-9\n\r";

    /**
     * preferred delimiter characters, only used when all filtering method
     * returns multiple possible delimiters (happens very rarely)
     *
     * @var string
     */
    public $auto_preferred = ",;\t.:|";

    /**
     * Should we convert the CSV character encoding?
     * Used for both parse and unparse operations.
     *
     * @var bool
     */
    public $convert_encoding = false;

    /**
     * Set the input encoding
     *
     * @var string
     */
    public $input_encoding = 'ISO-8859-1';

    /**
     * Set the output encoding
     *
     * @var string
     */
    public $output_encoding = 'ISO-8859-1';

    /**
     * Whether to use mb_convert_encoding() instead of iconv().
     *
     * The former is platform-independent whereas the latter is the traditional
     * default go-to solution.
     *
     * @var bool (if false, iconv() is used)
     */
    public $use_mb_convert_encoding = false;

    /**
     * Line feed characters used by unparse, save, and output methods
     * Popular choices are "\r\n" and "\n".
     *
     * @var string
     */
    public $linefeed = "\r";

    /**
     * Sets the output delimiter used by the output method
     *
     * @var string
     */
    public $output_delimiter = ',';

    /**
     * Sets the output filename
     *
     * @var string
     */
    public $output_filename = 'data.csv';

    /**
     * keep raw file data in memory after successful parsing (useful for debugging)
     *
     * @var bool
     */
    public $keep_file_data = false;

    /**
     * Internal variables
     */

    /**
     * File
     * Current Filename
     *
     * @var string
     */
    public $file;

    /**
     * File Data
     * Current file data
     *
     * @var string
     */
    public $file_data;

    /**
     * Error
     * Contains the error code if one occurred
     *
     * 0 = No errors found. Everything should be fine :)
     * 1 = Hopefully correctable syntax error was found.
     * 2 = Enclosure character (double quote by default)
     *     was found in non-enclosed field. This means
     *     the file is either corrupt, or does not
     *     standard CSV formatting. Please validate
     *     the parsed data yourself.
     *
     * @var int
     */
    public $error = 0;

    /**
     * Detailed error information
     *
     * @var array
     */
    public $error_info = array();

    /**
     * $titles has 4 distinct tasks:
     * 1. After reading in CSV data, $titles will contain the column headers
     *    present in the data.
     *
     * 2. It defines which fields from the $data array to write e.g. when
     *    calling unparse(), and in which order. This lets you skip columns you
     *    don't want in your output, but are present in $data.
     *    See examples/save_to_file_without_header_row.php.
     *
     * 3. It lets you rename columns. See StreamTest::testWriteStream for an
     *    example.
     *
     * 4. When writing data and $header is true, then $titles is also used for
     *    the first row.
     *
     * @var array
     */
    public $titles = array();

    /**
     * Two-dimensional array of CSV data.
     * The first dimension are the line numbers. Each line is represented as an array with field names as keys.
     *
     * @var array<array>
     */
    public $data = array();

    use DatatypeTrait;

    /**
     * Class constructor
     *
     * @param string|null $data            The CSV string or a direct file path.
     *
     *                                     WARNING: Supplying file paths here is
     *                                     deprecated. Use parseFile() instead.
     *
     * @param int|null    $offset          Number of rows to ignore from the
     *                                     beginning of  the data
     * @param int|null    $limit           Limits the number of returned rows
     *                                     to specified amount
     * @param string|null $conditions      Basic SQL-like conditions for row
     *                                     matching
     * @param null|true   $keep_file_data  Keep raw file data in memory after
     *                                     successful parsing
     *                                     (useful for debugging)
     */
    public function __construct($data = null, $offset = null, $limit = null, $conditions = null, $keep_file_data = null) {
        $this->init($offset, $limit, $conditions, $keep_file_data);

        if (!empty($data)) {
            $this->parse($data);
        }
    }

    /**
     * @param int|null    $offset           Number of rows to ignore from the
     *                                      beginning of  the data
     * @param int|null    $limit            Limits the number of returned rows
     *                                      to specified amount
     * @param string|null $conditions       Basic SQL-like conditions for row
     *                                      matching
     * @param null|true   $keep_file_data   Keep raw file data in memory after
     *                                      successful parsing
     *                                      (useful for debugging)
     */
    public function init($offset = null, $limit = null, $conditions = null, $keep_file_data = null) {
        if (!is_null($offset)) {
            $this->offset = $offset;
        }

        if (!is_null($limit)) {
            $this->limit = $limit;
        }

        if (!is_null($conditions)) {
            $this->conditions = $conditions;
        }

        if (!is_null($keep_file_data)) {
            $this->keep_file_data = $keep_file_data;
        }
    }

    // ==============================================
    // ----- [ Main Functions ] ---------------------
    // ==============================================

    /**
     * Parse a CSV file or string
     *
     * @param string|null $dataString  The CSV string or a direct file path
     *                                 WARNING: Supplying file paths here is
     *                                 deprecated and will trigger an
     *                                 E_USER_DEPRECATED error.
     * @param int|null    $offset      Number of rows to ignore from the
     *                                 beginning of  the data
     * @param int|null    $limit       Limits the number of returned rows to
     *                                 specified amount
     * @param string|null $conditions  Basic SQL-like conditions for row
     *                                 matching
     *
     * @return bool True on success
     */
    public function parse($dataString = null, $offset = null, $limit = null, $conditions = null) {
        if (is_null($dataString)) {
            $this->data = $this->parseFile();
            return $this->data !== false;
        }

        if (empty($dataString)) {
            return false;
        }

        $this->init($offset, $limit, $conditions);

        if (strlen($dataString) <= PHP_MAXPATHLEN && is_readable($dataString)) {
            $this->file = $dataString;
            $this->data = $this->parseFile();
            trigger_error(
                'Supplying file paths to parse() will no longer ' .
                'be supported in a future version of ParseCsv. ' .
                'Use ->parseFile() instead.',
                E_USER_DEPRECATED
            );
        } else {
            $this->file = null;
            $this->file_data = &$dataString;
            $this->data = $this->_parse_string();
        }

        return $this->data !== false;
    }

    /**
     * Save changes, or write a new file and/or data.
     *
     * @param string $file    File location to save to
     * @param array  $data    2D array of data
     * @param bool   $append  Append current data to end of target CSV, if file
     *                        exists
     * @param array  $fields  Field names. Sets the header. If it is not set
     *                        $this->titles would be used instead.
     *
     * @return bool
     *   True on success
     */
    public function save($file = '', $data = array(), $append = FileProcessingModeEnum::MODE_FILE_OVERWRITE, $fields = array()) {
        if (empty($file)) {
            $file = &$this->file;
        }

        $mode = FileProcessingModeEnum::getAppendMode($append);
        $is_php = preg_match('/\.php$/i', $file) ? true : false;

        return $this->_wfile($file, $this->unparse($data, $fields, $append, $is_php), $mode);
    }

    /**
     * Generate a CSV-based string for output.
     *
     * Useful for exports in web applications.
     *
     * @param string|null $filename    If a filename is specified here or in the
     *                                 object, headers and data will be output
     *                                 directly to browser as a downloadable
     *                                 file. This file doesn't have to exist on
     *                                 the server; the parameter only affects
     *                                 how the download is called to the
     *                                 browser.
     * @param array[]     $data        2D array with data
     * @param array       $fields      Field names
     * @param string|null $delimiter   character used to separate data
     *
     * @return string  The resulting CSV string
     */
    public function output($filename = null, $data = array(), $fields = array(), $delimiter = null) {
        if (empty($filename)) {
            $filename = $this->output_filename;
        }

        if ($delimiter === null) {
            $delimiter = $this->output_delimiter;
        }

        $flat_string = $this->unparse($data, $fields, null, null, $delimiter);

        if (!is_null($filename)) {
            $mime = $delimiter === "\t" ?
                'text/tab-separated-values' :
                'application/csv';
            header('Content-type: ' . $mime);
            header('Content-Length: ' . strlen($flat_string));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Content-Disposition: attachment; filename="' . $filename . '"; modification-date="' . date('r') . '";');

            echo $flat_string;
        }

        return $flat_string;
    }

    /**
     * Convert character encoding
     *
     * Specify the encoding to use for the next parsing or unparsing.
     * Calling this function will not change the data held in the object immediately.
     *
     * @param string|null $input  Input character encoding
     *   If the value null is passed, the existing input encoding remains set (default: ISO-8859-1).
     * @param string|null $output Output character encoding, uses default if left blank
     *   If the value null is passed, the existing input encoding remains set (default: ISO-8859-1).
     *
     * @return void
     */
    public function encoding($input = null, $output = null) {
        $this->convert_encoding = true;
        if (!is_null($input)) {
            $this->input_encoding = $input;
        }

        if (!is_null($output)) {
            $this->output_encoding = $output;
        }
    }

    /**
     * Auto-detect delimiter: Find delimiter by analyzing a specific number of
     * rows to determine most probable delimiter character
     *
     * @param string|null $file          Local CSV file
     *                                   Supplying CSV data (file content) here is deprecated.
     *                                   For CSV data, please use autoDetectionForDataString().
     *                                   Support for CSV data will be removed in v2.0.0.
     * @param bool        $parse         True/false parse file directly
     * @param int|null    $search_depth  Number of rows to analyze
     * @param string|null $preferred     Preferred delimiter characters
     * @param string|null $enclosure     Enclosure character, default is double quote (").
     *
     * @return string|false The detected field delimiter
     */
    public function auto($file = null, $parse = true, $search_depth = null, $preferred = null, $enclosure = null) {
        if (is_null($file)) {
            $file = $this->file;
        }

        if (empty($search_depth)) {
            $search_depth = $this->auto_depth;
        }

        if (is_null($enclosure)) {
            $enclosure = $this->enclosure;
        } else {
            $this->enclosure = $enclosure;
        }

        if (is_null($preferred)) {
            $preferred = $this->auto_preferred;
        }

        if (empty($this->file_data)) {
            if ($this->_check_data($file)) {
                $data = &$this->file_data;
            } else {
                return false;
            }
        } else {
            $data = &$this->file_data;
        }

        $this->autoDetectionForDataString($data, $parse, $search_depth, $preferred, $enclosure);

        return $this->delimiter;
    }

    public function autoDetectionForDataString($data, $parse = true, $search_depth = null, $preferred = null, $enclosure = null) {
        $this->file_data = &$data;
        if (!$this->_detect_and_remove_sep_row_from_data($data)) {
            $this->_guess_delimiter($search_depth, $preferred, $enclosure, $data);
        }

        // parse data
        if ($parse) {
            $this->data = $this->_parse_string();
        }

        return $this->delimiter;
    }

    /**
     * Get total number of data rows (exclusive heading line if present) in CSV
     * without parsing the whole data string.
     *
     * @return bool|int
     */
    public function getTotalDataRowCount() {
        if (empty($this->file_data)) {
            return false;
        }

        $data = $this->file_data;

        $this->_detect_and_remove_sep_row_from_data($data);

        $pattern = sprintf('/%1$s[^%1$s]*%1$s/i', $this->enclosure);
        preg_match_all($pattern, $data, $matches);

        /** @var array[] $matches */
        foreach ($matches[0] as $match) {
            if (empty($match) || (strpos($match, $this->enclosure) === false)) {
                continue;
            }

            $replace = str_replace(["\r", "\n"], '', $match);
            $data = str_replace($match, $replace, $data);
        }

        $headingRow = $this->heading ? 1 : 0;

        return substr_count($data, "\r")
            + substr_count($data, "\n")
            - substr_count($data, "\r\n")
            - $headingRow;
    }

    // ==============================================
    // ----- [ Core Functions ] ---------------------
    // ==============================================

    /**
     * Read file to string and call _parse_string()
     *
     * @param string|null $file  Path to a CSV file.
     *                           If configured in files such as php.ini,
     *                           the path may also contain a protocol:
     *                           https://example.org/some/file.csv
     *
     * @return array<array>|false
     */
    public function parseFile($file = null) {
        if (is_null($file)) {
            $file = $this->file;
        }

        /**
         * @see self::keep_file_data
         * Usually, _parse_string will clean this
         * Instead of leaving stale data for the next parseFile call behind.
         */
        if (empty($this->file_data) && !$this->loadFile($file)) {
            return false;
        }

        if (empty($this->file_data)) {
            return false;
        }
        return $this->data = $this->_parse_string();
    }

    /**
     * Internal function to parse CSV strings to arrays.
     *
     * If you need BOM detection or character encoding conversion, please call
     * $csv->load_data($your_data_string) first, followed by a call to
     * $csv->parse($csv->file_data).
     *
     * To detect field separators, please use auto() instead.
     *
     * @param string|null $data CSV data
     *
     * @return array<array>|false
     *   2D array with CSV data, or false on failure
     */
    protected function _parse_string($data = null) {
        if (empty($data)) {
            if ($this->_check_data()) {
                $data = &$this->file_data;
            } else {
                return false;
            }
        }

        $white_spaces = str_replace($this->delimiter, '', " \t\x0B\0");

        $rows = array();
        $row = array();
        $row_count = 0;
        $current = '';
        $head = !empty($this->fields) ? $this->fields : array();
        $col = 0;
        $enclosed = false;
        $was_enclosed = false;
        $strlen = strlen($data);

        // force the parser to process end of data as a character (false) when
        // data does not end with a line feed or carriage return character.
        $lch = $data[$strlen - 1];
        if ($lch != "\n" && $lch != "\r") {
            $data .= "\n";
            $strlen++;
        }

        // walk through each character
        for ($i = 0; $i < $strlen; $i++) {
            $ch = isset($data[$i]) ? $data[$i] : false;
            $nch = isset($data[$i + 1]) ? $data[$i + 1] : false;

            // open/close quotes, and inline quotes
            if ($ch == $this->enclosure) {
                if (!$enclosed) {
                    if (ltrim($current, $white_spaces) == '') {
                        $enclosed = true;
                        $was_enclosed = true;
                    } else {
                        $this->error = 2;
                        $error_row = count($rows) + 1;
                        $error_col = $col + 1;
                        $index = $error_row . '-' . $error_col;
                        if (!isset($this->error_info[$index])) {
                            $this->error_info[$index] = array(
                                'type' => 2,
                                'info' => 'Syntax error found on row ' . $error_row . '. Non-enclosed fields can not contain double-quotes.',
                                'row' => $error_row,
                                'field' => $error_col,
                                'field_name' => !empty($head[$col]) ? $head[$col] : null,
                            );
                        }

                        $current .= $ch;
                    }
                } elseif ($nch == $this->enclosure) {
                    $current .= $ch;
                    $i++;
                } elseif ($nch != $this->delimiter && $nch != "\r" && $nch != "\n") {
                    $x = $i + 1;
                    while (isset($data[$x]) && ltrim($data[$x], $white_spaces) == '') {
                        $x++;
                    }
                    if ($data[$x] == $this->delimiter) {
                        $enclosed = false;
                        $i = $x;
                    } else {
                        if ($this->error < 1) {
                            $this->error = 1;
                        }

                        $error_row = count($rows) + 1;
                        $error_col = $col + 1;
                        $index = $error_row . '-' . $error_col;
                        if (!isset($this->error_info[$index])) {
                            $this->error_info[$index] = array(
                                'type' => 1,
                                'info' =>
                                    'Syntax error found on row ' . (count($rows) + 1) . '. ' .
                                    'A single double-quote was found within an enclosed string. ' .
                                    'Enclosed double-quotes must be escaped with a second double-quote.',
                                'row' => count($rows) + 1,
                                'field' => $col + 1,
                                'field_name' => !empty($head[$col]) ? $head[$col] : null,
                            );
                        }

                        $current .= $ch;
                        $enclosed = false;
                    }
                } else {
                    $enclosed = false;
                }
                // end of field/row/csv
            } elseif ((in_array($ch, [$this->delimiter, "\n", "\r", false], true)) && !$enclosed) {
                $key = !empty($head[$col]) ? $head[$col] : $col;
                $row[$key] = $was_enclosed ? $current : trim($current);
                $current = '';
                $was_enclosed = false;
                $col++;

                // end of row
                if (in_array($ch, ["\n", "\r", false], true)) {
                    if ($this->_validate_offset($row_count) && $this->_validate_row_conditions($row, $this->conditions)) {
                        if ($this->heading && empty($head)) {
                            $head = $row;
                        } elseif (empty($this->fields) || (!empty($this->fields) && (($this->heading && $row_count > 0) || !$this->heading))) {
                            if (!empty($this->sort_by) && !empty($row[$this->sort_by])) {
                                $sort_field = $row[$this->sort_by];
                                if (isset($rows[$sort_field])) {
                                    $rows[$sort_field . '_0'] = &$rows[$sort_field];
                                    unset($rows[$sort_field]);
                                    $sn = 1;
                                    while (isset($rows[$sort_field . '_' . $sn])) {
                                        $sn++;
                                    }
                                    $rows[$sort_field . '_' . $sn] = $row;
                                } else {
                                    $rows[$sort_field] = $row;
                                }

                            } else {
                                $rows[] = $row;
                            }
                        }
                    }

                    $row = array();
                    $col = 0;
                    $row_count++;

                    if ($this->sort_by === null && $this->limit !== null && count($rows) == $this->limit) {
                        $i = $strlen;
                    }

                    if ($ch == "\r" && $nch == "\n") {
                        $i++;
                    }
                }

                // append character to current field
            } else {
                $current .= $ch;
            }
        }

        $this->titles = $head;
        if (!empty($this->sort_by)) {
            $sort_type = SortEnum::getSorting($this->sort_type);
            $this->sort_reverse ? krsort($rows, $sort_type) : ksort($rows, $sort_type);

            if ($this->offset !== null || $this->limit !== null) {
                $rows = array_slice($rows, ($this->offset === null ? 0 : $this->offset), $this->limit, true);
            }
        }

        if (!$this->keep_file_data) {
            $this->file_data = null;
        }

        return $rows;
    }

    /**
     * Create CSV data string from array
     *
     * @param array[]     $data       2D array with data
     * @param array       $fields     field names
     * @param bool        $append     if true, field names will not be output
     * @param bool        $is_php     if a php die() call should be put on the
     *                                first line of the file, this is later
     *                                ignored when read.
     * @param string|null $delimiter  field delimiter to use
     *
     * @return string CSV data
     */
    public function unparse($data = array(), $fields = array(), $append = FileProcessingModeEnum::MODE_FILE_OVERWRITE, $is_php = false, $delimiter = null) {
        if (!is_array($data) || empty($data)) {
            $data = &$this->data;
        } else {
            /** @noinspection ReferenceMismatchInspection */
            $this->data = $data;
        }

        if (!is_array($fields) || empty($fields)) {
            $fields = &$this->titles;
        }

        if ($delimiter === null) {
            $delimiter = $this->delimiter;
        }

        $string = $is_php ? "<?php header('Status: 403'); die(' '); ?>" . $this->linefeed : '';
        $entry = array();

        // create heading
        /** @noinspection ReferenceMismatchInspection */
        $fieldOrder = $this->_validate_fields_for_unparse($fields);
        if (!$fieldOrder && !empty($data)) {
            $column_count = count($data[0]);
            $columns = range(0, $column_count - 1, 1);
            $fieldOrder = array_combine($columns, $columns);
        }

        if ($this->heading && !$append && !empty($fields)) {
            foreach ($fieldOrder as $column_name) {
                $entry[] = $this->_enclose_value($column_name, $delimiter);
            }

            $string .= implode($delimiter, $entry) . $this->linefeed;
            $entry = array();
        }

        // create data
        foreach ($data as $key => $row) {
            foreach (array_keys($fieldOrder) as $index) {
                $cell_value = $row[$index];
                $entry[] = $this->_enclose_value($cell_value, $delimiter);
            }

            $string .= implode($delimiter, $entry) . $this->linefeed;
            $entry = array();
        }

        if ($this->convert_encoding) {
            /** @noinspection PhpComposerExtensionStubsInspection
             *
             * If you receive an error at the following 3 lines, you must enable
             * the following PHP extension:
             *
             *  - if $use_mb_convert_encoding is true: mbstring
             *  - if $use_mb_convert_encoding is false: iconv
             */
            $string = $this->use_mb_convert_encoding ?
                mb_convert_encoding($string, $this->output_encoding, $this->input_encoding) :
                iconv($this->input_encoding, $this->output_encoding, $string);
        }

        return $string;
    }

    /**
     * @param array $fields
     *
     * @return array|false
     */
    private function _validate_fields_for_unparse(array $fields) {
        if (empty($fields)) {
            $fields = $this->titles;
        }

        if (empty($fields)) {
            return array();
        }

        // this is needed because sometime titles property is overwritten instead of using fields parameter!
        $titlesOnParse = !empty($this->data) ? array_keys(reset($this->data)) : array();

        // both are identical, also in ordering OR we have no data (only titles)
        if (empty($titlesOnParse) || array_values($fields) === array_values($titlesOnParse)) {
            return array_combine($fields, $fields);
        }

        // if renaming given by: $oldName => $newName (maybe with reorder and / or subset):
        // todo: this will only work if titles are unique
        $fieldOrder = array_intersect(array_flip($fields), $titlesOnParse);
        if (!empty($fieldOrder)) {
            return array_flip($fieldOrder);
        }

        $fieldOrder = array_intersect($fields, $titlesOnParse);
        if (!empty($fieldOrder)) {
            return array_combine($fieldOrder, $fieldOrder);
        }

        // original titles are not given in fields. that is okay if count is okay.
        if (count($fields) != count($titlesOnParse)) {
            throw new \UnexpectedValueException(
                "The specified fields do not match any titles and do not match column count.\n" .
                "\$fields was " . print_r($fields, true) .
                "\$titlesOnParse was " . print_r($titlesOnParse, true));
        }

        return array_combine($titlesOnParse, $fields);
    }

    /**
     * Load local file or string.
     *
     * Only use this function if auto() and parse() don't handle your data well.
     *
     * This function load_data() is able to handle BOMs and encodings. The data
     * is stored within the $this->file_data class field.
     *
     * @param string|null $input  CSV file path or CSV data as a string
     *
     *                            Supplying CSV data (file content) here is deprecated.
     *                            For CSV data, please use loadDataString().
     *                            Support for CSV data will be removed in v2.0.0.
     *
     * @return bool  True on success
     * @deprecated Use loadDataString() or loadFile() instead.
     */
    public function load_data($input = null) {
        return $this->loadFile($input);
    }

    /**
     * Load a file, but don't parse it.
     *
     * Only use this function if auto() and parseFile() don't handle your data well.
     *
     * This function is able to handle BOMs and encodings. The data
     * is stored within the $this->file_data class field.
     *
     * @param string|null $file  CSV file path
     *
     * @return bool  True on success
     */
    public function loadFile($file = null) {
        $data = null;

        if (is_null($file)) {
            $data = $this->_rfile($this->file);
        } elseif (\strlen($file) <= PHP_MAXPATHLEN && file_exists($file)) {
            $data = $this->_rfile($file);
            if ($this->file != $file) {
                $this->file = $file;
            }
        } else {
            // It is CSV data as a string.

            // WARNING:
            // Supplying CSV data to load_data() will no longer
            // be supported in a future version of ParseCsv.
            // This function will return false for invalid paths from v2.0.0 onwards.

            // Use ->loadDataString() instead.

            $data = $file;
        }

        return $this->loadDataString($data);
    }

    /**
     * Load a data string, but don't parse it.
     *
     * Only use this function if autoDetectionForDataString() and parse() don't handle your data well.
     *
     * This function is able to handle BOMs and encodings. The data
     * is stored within the $this->file_data class field.
     *
     * @param string|null $file_path  CSV file path
     *
     * @return bool  True on success
     */
    public function loadDataString($data) {
        if (!empty($data)) {
            if (strpos($data, "\xef\xbb\xbf") === 0) {
                // strip off BOM (UTF-8)
                $data = substr($data, 3);
                $this->encoding('UTF-8');
            } elseif (strpos($data, "\xff\xfe") === 0) {
                // strip off BOM (UTF-16 little endian)
                $data = substr($data, 2);
                $this->encoding("UCS-2LE");
            } elseif (strpos($data, "\xfe\xff") === 0) {
                // strip off BOM (UTF-16 big endian)
                $data = substr($data, 2);
                $this->encoding("UTF-16");
            }

            if ($this->convert_encoding && $this->input_encoding !== $this->output_encoding) {
                /** @noinspection PhpComposerExtensionStubsInspection
                 *
                 * If you receive an error at the following 3 lines, you must enable
                 * the following PHP extension:
                 *
                 *  - if $use_mb_convert_encoding is true: mbstring
                 *  - if $use_mb_convert_encoding is false: iconv
                 */
                $data = $this->use_mb_convert_encoding ?
                    mb_convert_encoding($data, $this->output_encoding, $this->input_encoding) :
                    iconv($this->input_encoding, $this->output_encoding, $data);
            }

            if (substr($data, -1) != "\n") {
                $data .= "\n";
            }

            $this->file_data = &$data;
            return true;
        }

        return false;
    }

    // ==============================================
    // ----- [ Internal Functions ] -----------------
    // ==============================================

    /**
     * Validate a row against specified conditions
     *
     * @param array       $row        array with values from a row
     * @param string|null $conditions specified conditions that the row must match
     *
     * @return bool
     */
    protected function _validate_row_conditions($row = array(), $conditions = null) {
        if (!empty($row)) {
            if (!empty($conditions)) {
                $condition_array = (strpos($conditions, ' OR ') !== false) ?
                    explode(' OR ', $conditions) :
                    array($conditions);
                $or = '';
                foreach ($condition_array as $key => $value) {
                    if (strpos($value, ' AND ') !== false) {
                        $value = explode(' AND ', $value);
                        $and = '';

                        foreach ($value as $k => $v) {
                            $and .= $this->_validate_row_condition($row, $v);
                        }

                        $or .= (strpos($and, '0') !== false) ? '0' : '1';
                    } else {
                        $or .= $this->_validate_row_condition($row, $value);
                    }
                }

                return strpos($or, '1') !== false;
            }

            return true;
        }

        return false;
    }

    /**
     * Validate a row against a single condition
     *
     * @param array  $row       array with values from a row
     * @param string $condition specified condition that the row must match
     *
     * @return string single 0 or 1
     */
    protected function _validate_row_condition($row, $condition) {
        $operators = array(
            '=',
            'equals',
            'is',
            '!=',
            'is not',
            '<',
            'is less than',
            '>',
            'is greater than',
            '<=',
            'is less than or equals',
            '>=',
            'is greater than or equals',
            'contains',
            'does not contain',
        );

        $operators_regex = array();

        foreach ($operators as $value) {
            $operators_regex[] = preg_quote($value, '/');
        }

        $operators_regex = implode('|', $operators_regex);

        if (preg_match('/^(.+) (' . $operators_regex . ') (.+)$/i', trim($condition), $capture)) {
            $field = $capture[1];
            $op = strtolower($capture[2]);
            $value = $capture[3];
            if ($op == 'equals' && preg_match('/^(.+) is (less|greater) than or$/i', $field, $m)) {
                $field = $m[1];
                $op = strtolower($m[2]) == 'less' ? '<=' : '>=';
            }
            if ($op == 'is' && preg_match('/^(less|greater) than (.+)$/i', $value, $m)) {
                $value = $m[2];
                $op = strtolower($m[1]) == 'less' ? '<' : '>';
            }
            if ($op == 'is' && preg_match('/^not (.+)$/i', $value, $m)) {
                $value = $m[1];
                $op = '!=';
            }

            if (preg_match('/^([\'"])(.*)([\'"])$/', $value, $capture) && $capture[1] == $capture[3]) {
                $value = strtr($capture[2], array(
                    "\\n" => "\n",
                    "\\r" => "\r",
                    "\\t" => "\t",
                ));

                $value = stripslashes($value);
            }

            if (array_key_exists($field, $row)) {
                $op_equals = in_array($op, ['=', 'equals', 'is'], true);
                if ($op_equals && $row[$field] == $value) {
                    return '1';
                } elseif (($op == '!=' || $op == 'is not') && $row[$field] != $value) {
                    return '1';
                } elseif (($op == '<' || $op == 'is less than') && $row[$field] < $value) {
                    return '1';
                } elseif (($op == '>' || $op == 'is greater than') && $row[$field] > $value) {
                    return '1';
                } elseif (($op == '<=' || $op == 'is less than or equals') && $row[$field] <= $value) {
                    return '1';
                } elseif (($op == '>=' || $op == 'is greater than or equals') && $row[$field] >= $value) {
                    return '1';
                } elseif ($op == 'contains' && preg_match('/' . preg_quote($value, '/') . '/i', $row[$field])) {
                    return '1';
                } elseif ($op == 'does not contain' && !preg_match('/' . preg_quote($value, '/') . '/i', $row[$field])) {
                    return '1';
                } else {
                    return '0';
                }
            }
        }

        return '1';
    }

    /**
     * Validates if the row is within the offset or not if sorting is disabled
     *
     * @param int $current_row the current row number being processed
     *
     * @return bool
     */
    protected function _validate_offset($current_row) {
        return
            $this->sort_by !== null ||
            $this->offset === null ||
            $current_row >= $this->offset ||
            ($this->heading && $current_row == 0);
    }

    /**
     * Enclose values if needed
     *  - only used by unparse()
     *
     * @param string|null $value     Cell value to process
     * @param string      $delimiter Character to put between cells on the same row
     *
     * @return string Processed value
     */
    protected function _enclose_value($value, $delimiter) {
        if ($value !== null && $value != '') {
            $delimiter_quoted = $delimiter ?
                preg_quote($delimiter, '/') . "|"
                : '';
            $enclosure_quoted = preg_quote($this->enclosure, '/');
            $pattern = "/" . $delimiter_quoted . $enclosure_quoted . "|\n|\r/i";
            if ($this->enclose_all || preg_match($pattern, $value) || strpos($value, ' ') === 0 || substr($value, -1) == ' ') {
                $value = str_replace($this->enclosure, $this->enclosure . $this->enclosure, $value);
                $value = $this->enclosure . $value . $this->enclosure;
            }
        }

        return $value;
    }

    /**
     * Check file data
     *
     * @param string|null $file local filename
     *
     * @return bool
     */
    protected function _check_data($file = null) {
        if (empty($this->file_data)) {
            if (is_null($file)) {
                $file = $this->file;
            }

            return $this->loadFile($file);
        }

        return true;
    }

    /**
     * Check if passed info might be delimiter.
     * Only used by find_delimiter
     *
     * @param string $char      Potential field separating character
     * @param array  $array     Frequency
     * @param int    $depth     Number of analyzed rows
     * @param string $preferred Preferred delimiter characters
     *
     * @return string|false      special string used for delimiter selection, or false
     */
    protected function _check_count($char, $array, $depth, $preferred) {
        if ($depth === count($array)) {
            $first = null;
            $equal = null;
            $almost = false;
            foreach ($array as $value) {
                if ($first == null) {
                    $first = $value;
                } elseif ($value == $first && $equal !== false) {
                    $equal = true;
                } elseif ($value == $first + 1 && $equal !== false) {
                    $equal = true;
                    $almost = true;
                } else {
                    $equal = false;
                }
            }

            if ($equal || $depth === 1) {
                $match = $almost ? 2 : 1;
                $pref = strpos($preferred, $char);
                $pref = ($pref !== false) ? str_pad($pref, 3, '0', STR_PAD_LEFT) : '999';

                return $pref . $match . '.' . (99999 - str_pad($first, 5, '0', STR_PAD_LEFT));
            } else {
                return false;
            }
        }
        return false;
    }

    /**
     * Read local file.
     *
     * @param string $filePath  local filename
     *
     * @return string|false Data from file, or false on failure
     */
    protected function _rfile($filePath) {
        if (is_readable($filePath)) {
            $data = file_get_contents($filePath);
            if ($data === false) {
                return false;
            }

            if (preg_match('/\.php$/i', $filePath) && preg_match('/<\?.*?\?>(.*)/ms', $data, $strip)) {
                // Return section behind closing tags.
                // This parsing is deprecated and will be removed in v2.0.0.
                $data = ltrim($strip[1]);
            }

            return rtrim($data, "\r\n");
        }

        return false;
    }

    /**
     * Write to local file
     *
     * @param string $file    local filename
     * @param string $content data to write to file
     * @param string $mode    fopen() mode
     * @param int    $lock    flock() mode
     *
     * @return bool
     *   True on success
     *
     */
    protected function _wfile($file, $content = '', $mode = 'wb', $lock = LOCK_EX) {
        if ($fp = fopen($file, $mode)) {
            flock($fp, $lock);
            $re = fwrite($fp, $content);
            $re2 = fclose($fp);

            if ($re !== false && $re2 !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect separator using a nonstandard hack: such file starts with the
     * first line containing only "sep=;", where the last character is the
     * separator. Microsoft Excel is able to open such files.
     *
     * @param string $data file data
     *
     * @return string|false detected delimiter, or false if none found
     */
    protected function _get_delimiter_from_sep_row($data) {
        $sep = false;
        // 32 bytes should be quite enough data for our sniffing, chosen arbitrarily
        $sepPrefix = substr($data, 0, 32);
        if (preg_match('/^sep=(.)\\r?\\n/i', $sepPrefix, $sepMatch)) {
            // we get separator.
            $sep = $sepMatch[1];
        }
        return $sep;
    }

    /**
     * Support for Excel-compatible sep=? row.
     *
     * @param string $data_string file data to be updated
     *
     * @return bool TRUE if sep= line was found at the very beginning of the file
     */
    protected function _detect_and_remove_sep_row_from_data(&$data_string) {
        $sep = $this->_get_delimiter_from_sep_row($data_string);
        if ($sep === false) {
            return false;
        }

        $this->delimiter = $sep;

        // likely to be 5, but let's not assume we're always single-byte.
        $pos = 4 + strlen($sep);
        // the next characters should be a line-end
        if (substr($data_string, $pos, 1) === "\r") {
            $pos++;
        }
        if (substr($data_string, $pos, 1) === "\n") {
            $pos++;
        }

        // remove delimiter and its line-end (the data param is by-ref!)
        $data_string = substr($data_string, $pos);
        return true;
    }

    /**
     * @param int    $search_depth Number of rows to analyze
     * @param string $preferred    Preferred delimiter characters
     * @param string $enclosure    Enclosure character, default is double quote
     * @param string $data         The file content
     */
    protected function _guess_delimiter($search_depth, $preferred, $enclosure, $data) {
        $chars = [];
        $strlen = strlen($data);
        $enclosed = false;
        $current_row = 1;
        $to_end = true;

        // The dash is the only character we don't want quoted, as it would
        // prevent character ranges within $auto_non_chars:
        $quoted_auto_non_chars = preg_quote($this->auto_non_chars, '/');
        $quoted_auto_non_chars = str_replace('\-', '-', $quoted_auto_non_chars);
        $pattern = '/[' . $quoted_auto_non_chars . ']/i';

        // walk specific depth finding possible delimiter characters
        for ($i = 0; $i < $strlen; $i++) {
            $ch = $data[$i];
            $nch = isset($data[$i + 1]) ? $data[$i + 1] : false;
            $pch = isset($data[$i - 1]) ? $data[$i - 1] : false;

            // open and closing quotes
            $is_newline = ($ch == "\n" && $pch != "\r") || $ch == "\r";
            if ($ch == $enclosure) {
                if (!$enclosed || $nch != $enclosure) {
                    $enclosed = !$enclosed;
                } elseif ($enclosed) {
                    $i++;
                }

                // end of row
            } elseif ($is_newline && !$enclosed) {
                if ($current_row >= $search_depth) {
                    $strlen = 0;
                    $to_end = false;
                } else {
                    $current_row++;
                }

                // count character
            } elseif (!$enclosed) {
                if (!preg_match($pattern, $ch)) {
                    if (!isset($chars[$ch][$current_row])) {
                        $chars[$ch][$current_row] = 1;
                    } else {
                        $chars[$ch][$current_row]++;
                    }
                }
            }
        }

        // filtering
        $depth = $to_end ? $current_row - 1 : $current_row;
        $filtered = [];
        foreach ($chars as $char => $value) {
            if ($match = $this->_check_count($char, $value, $depth, $preferred)) {
                $filtered[$match] = $char;
            }
        }

        // capture most probable delimiter
        ksort($filtered);
        $this->delimiter = reset($filtered);
    }

    /**
     * getCollection
     * Returns a Illuminate/Collection object
     * This may prove to be helpful to people who want to
     * create macros, and or use map functions
     *
     * @access public
     * @link   https://laravel.com/docs/5.6/collections
     *
     * @throws \ErrorException - If the Illuminate\Support\Collection class is not found
     *
     * @return Collection
     */
    public function getCollection() {
        //does the Illuminate\Support\Collection class exists?
        //this uses the autoloader to try to determine
        //@see http://php.net/manual/en/function.class-exists.php
        if (class_exists('Illuminate\Support\Collection', true) == false) {
            throw new \ErrorException('It would appear you have not installed the illuminate/support package!');
        }

        //return the collection
        return new Collection($this->data);
    }
}
