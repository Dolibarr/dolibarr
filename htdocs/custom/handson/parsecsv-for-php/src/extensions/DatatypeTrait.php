<?php

namespace ParseCsv\extensions;

use ParseCsv\enums\DatatypeEnum;

trait DatatypeTrait {

    /**
     * Data Types
     * Data types of CSV data-columns, keyed by the column name. Possible values
     * are string, float, integer, boolean, date. See DatatypeEnum.
     *
     * @var array
     */
    public $data_types = [];

    /**
     * Check data type for one column.
     * Check for most commonly data type for one column.
     *
     * @param array $datatypes
     *
     * @return string|false
     */
    private function getMostFrequentDatatypeForColumn($datatypes) {
        // remove 'false' from array (can happen if CSV cell is empty)
        $typesFiltered = array_filter($datatypes);

        if (empty($typesFiltered)) {
            return false;
        }

        $typesFreq = array_count_values($typesFiltered);
        arsort($typesFreq);
        reset($typesFreq);

        return key($typesFreq);
    }

    /**
     * Check data type foreach Column
     * Check data type for each column and returns the most commonly.
     *
     * Requires PHP >= 5.5
     *
     * @uses   DatatypeEnum::getValidTypeFromSample
     *
     * @return array|bool
     */
    public function getDatatypes() {
        if (empty($this->data)) {
            $this->data = $this->_parse_string();
        }
        if (!is_array($this->data)) {
            throw new \UnexpectedValueException('No data set yet.');
        }

        $result = [];
        foreach ($this->titles as $cName) {
            $column = array_column($this->data, $cName);
            $cDatatypes = array_map(DatatypeEnum::class . '::getValidTypeFromSample', $column);

            $result[$cName] = $this->getMostFrequentDatatypeForColumn($cDatatypes);
        }

        $this->data_types = $result;

        return !empty($this->data_types) ? $this->data_types : [];
    }

    /**
     * Check data type of titles / first row for auto detecting if this could be
     * a heading line.
     *
     * Requires PHP >= 5.5
     *
     * @uses   DatatypeEnum::getValidTypeFromSample
     *
     * @return bool
     */
    public function autoDetectFileHasHeading() {
        if (empty($this->data)) {
            throw new \UnexpectedValueException('No data set yet.');
        }

        if ($this->heading) {
            $firstRow = $this->titles;
        } else {
            $firstRow = $this->data[0];
        }

        $firstRow = array_filter($firstRow);
        if (empty($firstRow)) {
            return false;
        }

        $firstRowDatatype = array_map(DatatypeEnum::class . '::getValidTypeFromSample', $firstRow);

        return $this->getMostFrequentDatatypeForColumn($firstRowDatatype) === DatatypeEnum::TYPE_STRING;
    }
}
