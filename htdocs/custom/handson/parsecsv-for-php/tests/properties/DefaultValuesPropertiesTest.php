<?php

namespace ParseCsv\tests\properties;

use ParseCsv\Csv;
use PHPUnit\Framework\TestCase;

class DefaultValuesPropertiesTest extends TestCase {

    /**
     * CSV
     * The parseCSV object
     *
     * @access protected
     * @var Csv
     */
    protected $csv = null;

    protected function setUp(): void {
        $this->csv = new Csv();
    }

    public function test_heading_default() {
        self::assertTrue(is_bool($this->csv->heading));
        self::assertTrue($this->csv->heading);
    }

    public function test_fields_default() {
        self::assertTrue(is_array($this->csv->fields));
        self::assertCount(0, $this->csv->fields);
    }

    public function test_sort_by_default() {
        self::assertNull($this->csv->sort_by);
    }

    public function test_sort_reverse_default() {
        self::assertTrue(is_bool($this->csv->sort_reverse));
        self::assertFalse($this->csv->sort_reverse);
    }

    public function test_sort_type_default() {
        self::assertEquals('regular', $this->csv->sort_type);
    }

    public function test_delimiter_default() {
        self::assertTrue(is_string($this->csv->delimiter));
        self::assertEquals(',', $this->csv->delimiter);
    }

    public function test_enclosure_default() {
        self::assertTrue(is_string($this->csv->enclosure));
        self::assertEquals('"', $this->csv->enclosure);
    }

    public function test_enclose_all_default() {
        self::assertTrue(is_bool($this->csv->enclose_all));
        self::assertFalse($this->csv->enclose_all);
    }

    public function test_conditions_default() {
        self::assertNull($this->csv->conditions);
    }

    public function test_offset_default() {
        self::assertNull($this->csv->offset);
    }

    public function test_limit_default() {
        self::assertNull($this->csv->limit);
    }

    public function test_auto_depth_default() {
        self::assertTrue(is_numeric($this->csv->auto_depth));
        self::assertEquals(15, $this->csv->auto_depth);
    }

    public function test_auto_non_chars_default() {
        self::assertTrue(is_string($this->csv->auto_non_chars));
        self::assertEquals("a-zA-Z0-9\n\r", $this->csv->auto_non_chars);
    }

    public function test_auto_preferred_default() {
        self::assertTrue(is_string($this->csv->auto_preferred));
        self::assertEquals(",;\t.:|", $this->csv->auto_preferred);
    }

    public function test_convert_encoding_default() {
        self::assertTrue(is_bool($this->csv->convert_encoding));
        self::assertFalse($this->csv->convert_encoding);
    }

    public function test_input_encoding_default() {
        self::assertTrue(is_string($this->csv->input_encoding));
        self::assertEquals('ISO-8859-1', $this->csv->input_encoding);
    }

    public function test_output_encoding_default() {
        self::assertTrue(is_string($this->csv->output_encoding));
        self::assertEquals('ISO-8859-1', $this->csv->output_encoding);
    }

    public function test_linefeed_default() {
        self::assertTrue(is_string($this->csv->linefeed));
        self::assertEquals("\r", $this->csv->linefeed);
    }

    public function test_output_delimiter_default() {
        self::assertTrue(is_string($this->csv->output_delimiter));
        self::assertEquals(',', $this->csv->output_delimiter);
    }

    public function test_output_filename_default() {
        self::assertTrue(is_string($this->csv->output_filename));
        self::assertEquals('data.csv', $this->csv->output_filename);
    }

    public function test_keep_file_data_default() {
        self::assertTrue(is_bool($this->csv->keep_file_data));
        self::assertFalse($this->csv->keep_file_data);
    }

    public function test_file_default() {
        self::assertNull($this->csv->file);
    }

    public function test_file_data_default() {
        self::assertNull($this->csv->file_data);
    }

    public function test_error_default() {
        self::assertTrue(is_numeric($this->csv->error));
        self::assertEquals(0, $this->csv->error);
    }

    public function test_error_info_default() {
        self::assertTrue(is_array($this->csv->error_info));
        self::assertCount(0, $this->csv->error_info);
    }

    public function test_titles_default() {
        self::assertTrue(is_array($this->csv->titles));
        self::assertCount(0, $this->csv->titles);
    }

    public function test_data_default() {
        self::assertTrue(is_array($this->csv->data));
        self::assertCount(0, $this->csv->data);
    }
}
