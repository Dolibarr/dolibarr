<?php

namespace ParseCsv\tests\properties;

use ParseCsv\Csv;
use ParseCsv\enums\SortEnum;
use PHPUnit\Framework\TestCase;

class PublicPropertiesTest extends TestCase {

    /**
     * CSV
     * The parseCSV object
     *
     * @access protected
     * @var Csv
     */
    protected $csv = null;

    /**
     * Reflection Object
     * The reflection class object
     *
     * @access protected
     * @var \ReflectionClass
     */
    protected $reflection = null;

    /**
     * Reflection Properties
     * The reflected class properties
     *
     * @var \ReflectionProperty[]
     * @access protected
     */
    protected $properties = null;

    /**
     * Setup
     * Setup our test environment objects
     *
     * @access public
     */
    protected function setUp(): void {
        //setup parse CSV
        $this->csv = new Csv();

        //setup the reflection class
        $this->reflection = new \ReflectionClass($this->csv);

        //setup the reflected class properties
        $this->properties = $this->reflection->getProperties();
    }

    /**
     * Tear down
     * Tear down our test environment objects
     *
     * @access public
     */
    protected function tearDown(): void {
        $this->csv = null;
        $this->reflection = null;
        $this->properties = null;
    }

    /**
     * test_propertiesCount
     * Counts the number of properties to make sure we didn't add or
     * subtract any without thinking
     *
     * @access public
     */
    public function test_propertiesCount() {
        $this->assertCount(29, $this->properties);
    }

    /**
     * test_property_names
     * We have an expected set of properties that should exists
     * Make sure our expected number of properties matches the real
     * count of properties and also check to make sure our expected
     * properties exists within the class
     *
     * @access public
     */
    public function test_property_names() {
        //set our expected properties name(s)
        $expected_names = array(
            'heading',
            'fields',
            'sort_by',
            'sort_reverse',
            'sort_type',
            'delimiter',
            'enclosure',
            'enclose_all',
            'conditions',
            'offset',
            'limit',
            'auto_depth',
            'auto_non_chars',
            'auto_preferred',
            'convert_encoding',
            'input_encoding',
            'output_encoding',
            'use_mb_convert_encoding',
            'linefeed',
            'output_delimiter',
            'output_filename',
            'keep_file_data',
            'file',
            'file_data',
            'error',
            'error_info',
            'titles',
            'data',
            'data_types',
        );

        // Find our real properties
        $real_properties = array_map(function (\ReflectionProperty $property) {
            return $property->getName();
        }, $this->properties);

        // Lets make sure our expected matches the number of real properties
        $this->assertEquals($expected_names, $real_properties);
    }

    /**
     * test_count_public_properties
     * We at this point only have public properties so
     * lets verify all properties are public
     *
     * @access public
     */
    public function test_count_public_properties() {
        $counter = 0;

        $propertiesCount = count($this->properties);
        for ($a = 0; $a < $propertiesCount; $a++) {
            if ($this->properties[$a]->isPublic() === true) {
                $counter++;
            }
        }

        $this->assertCount($counter, $this->properties);
    }

    public function testDefaultSortTypeIsRegular() {
        $this->assertEquals(SortEnum::SORT_TYPE_REGULAR, $this->csv->sort_type);
    }

    public function testSetSortType() {
        $this->csv->sort_type = 'numeric';
        $this->assertEquals(SortEnum::SORT_TYPE_NUMERIC, $this->csv->sort_type);

        $this->csv->sort_type = 'string';
        $this->assertEquals(SortEnum::SORT_TYPE_STRING, $this->csv->sort_type);
    }

    public function testGetSorting() {
        $this->csv->sort_type = 'numeric';
        $sorting = SortEnum::getSorting($this->csv->sort_type);
        $this->assertEquals(SORT_NUMERIC, $sorting);

        $this->csv->sort_type = 'string';
        $sorting = SortEnum::getSorting($this->csv->sort_type);
        $this->assertEquals(SORT_STRING, $sorting);
    }
}
