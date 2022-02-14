<?php

namespace ParseCsv\tests\methods;

use ParseCsv\Csv;
use PHPUnit\Framework\TestCase;


class UnparseTest extends Testcase {

    /** @var Csv */
    private $csv;

    /**
     * Setup our test environment objects; will be called before each test.
     */
    protected function setUp(): void {
        $this->csv = new Csv();
        $this->csv->auto(__DIR__ . '/fixtures/auto-double-enclosure.csv');
    }

    public function testUnparseWithParameters() {
        $fields = array('a' => 'AA', 'b' => 'BB');
        $data = [['a' => 'value1', 'b' => 'value2']];
        $csv_object = new Csv();
        $csv_string = $csv_object->unparse($data, $fields);
        $this->assertEquals("AA,BB\rvalue1,value2\r", $csv_string);

        $csv_object = new Csv();
        $csv_object->linefeed = "\n";
        $csv_string = $csv_object->unparse([[55, 66]]);
        $this->assertEquals("55,66\n", $csv_string);

        $csv_object = new Csv();
        $data2 = [['a' => "multi\rline", 'b' => 'value2']];
        $csv_object->enclosure = "'";
        $csv_string = $csv_object->unparse($data2, $fields);
        $this->assertEquals("AA,BB\r'multi\rline',value2\r", $csv_string);
    }

    public function testUnparseDefault() {
        $expected = "column1,column2\rvalue1,value2\rvalue3,value4\r";
        $this->unparseAndCompare($expected);
    }

    public function testUnparseDefaultWithoutHeading() {
        $this->csv->heading = false;
        $this->csv->auto(__DIR__ . '/fixtures/auto-double-enclosure.csv');
        $expected = "column1,column2\rvalue1,value2\rvalue3,value4\r";
        $this->unparseAndCompare($expected);
    }

    public function testUnparseRenameFields() {
        $expected = "C1,C2\rvalue1,value2\rvalue3,value4\r";
        $this->unparseAndCompare($expected, array("C1", "C2"));
    }

    public function testReorderFields() {
        $expected = "column2,column1\rvalue2,value1\rvalue4,value3\r";
        $this->unparseAndCompare($expected, array("column2", "column1"));
    }

    public function testSubsetFields() {
        $expected = "column1\rvalue1\rvalue3\r";
        $this->unparseAndCompare($expected, array("column1"));
    }

    public function testReorderAndRenameFields() {
        $fields = array(
            'column2' => 'C2',
            'column1' => 'C1',
        );
        $expected = "C2,C1\rvalue2,value1\rvalue4,value3\r";
        $this->unparseAndCompare($expected, $fields);
    }

    public function testUnparseDefaultFirstRowMissing() {
        unset($this->csv->data[0]);
        $expected = "column1,column2\rvalue3,value4\r";
        $this->unparseAndCompare($expected);
    }

    public function testUnparseDefaultWithoutData() {
        unset($this->csv->data[0]);
        unset($this->csv->data[1]);
        $expected = "column1,column2\r";
        $this->unparseAndCompare($expected);
    }

    public function testObjectCells() {
        $this->csv->data = [
            [
                'column1' => new ObjectThatHasToStringMethod(),
                'column2' => 'boring',
            ],
        ];
        $this->csv->linefeed = "\n";
        $expected = "column1,column2\nsome value,boring\n";
        $this->unparseAndCompare($expected);
    }

    private function unparseAndCompare($expected, $fields = array()) {
        $str = $this->csv->unparse($this->csv->data, $fields);
        $this->assertEquals($expected, $str);
    }

}
