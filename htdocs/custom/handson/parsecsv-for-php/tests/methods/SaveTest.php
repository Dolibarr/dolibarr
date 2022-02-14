<?php

namespace ParseCsv\tests\methods;

use ParseCsv\Csv;
use PHPUnit\Framework\TestCase;

class SaveTest extends TestCase {

    /** @var Csv */
    private $csv;

    private $temp_filename;

    /**
     * Setup our test environment objects; will be called before each test.
     */
    protected function setUp(): void {
        $this->csv = new Csv();
        $this->csv->auto(__DIR__ . '/../example_files/single_column.csv');

        // Remove last 2 lines to simplify comparison
        unset($this->csv->data[2], $this->csv->data[3]);

        $temp_dir = str_replace("\\", '/', sys_get_temp_dir());
        if (substr($temp_dir, -1) != '/') {
            // From the PHP.net documentation:
            // This function does not always add trailing slash. This behaviour
            // is inconsistent across systems.
            $temp_dir .= '/';
        }
        $this->temp_filename = $temp_dir . 'parsecsv_test_file.csv';
    }

    public function testSaveWithDefaultSettings() {
        $expected = "SMS\r0444\r5555\r";
        $this->saveAndCompare($expected);
    }

    public function testSaveWithDosLineEnding() {
        $this->csv->linefeed = "\r\n";
        $expected = "SMS\r\n0444\r\n5555\r\n";
        $this->saveAndCompare($expected);
    }

    public function testSaveWithUnixLineEnding() {
        $this->csv->linefeed = "\n";
        $expected = "SMS\n0444\n5555\n";
        $this->saveAndCompare($expected);
    }

    public function testSaveWithNewHeader() {
        $this->csv->linefeed = "\n";
        $this->csv->titles = array("NewTitle");
        $expected = "NewTitle\n0444\n5555\n";
        $this->saveAndCompare($expected);
    }

    public function testSaveWithoutHeader() {
        $this->csv->linefeed = "\n";
        $this->csv->heading = false;
        $expected = "0444\n5555\n";
        $this->saveAndCompare($expected);
    }

    public function testAllQuotes() {
        $this->csv->enclose_all = true;
        $expected = "\"SMS\"\r\"0444\"\r\"5555\"\r";
        $this->saveAndCompare($expected);
    }

    private function saveAndCompare($expected) {
        $this->csv->save($this->temp_filename);
        $content = file_get_contents($this->temp_filename);
        $this->assertEquals($expected, $content);
    }
}
