<?php

namespace ParseCsv\tests\methods;

use ParseCsv\Csv;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ParseTest extends TestCase {

    /**
     * @var Csv object
     */
    protected $csv;

    /**
     * Setup
     * Setup our test environment objects
     *
     * @access public
     */
    protected function setUp(): void {
        $this->csv = new Csv();
    }

    public function testParse() {
        // can we trick 'is_readable' into whining? See #67.
        $this->parseRepetitiveString('c:/looks/like/a/path');
        $this->parseRepetitiveString('http://looks/like/an/url');
    }

    /**
     * @param string $content
     */
    private function parseRepetitiveString($content) {
        $this->csv->delimiter = ';';
        $this->csv->heading = false;
        $success = $this->csv->parse(str_repeat($content . ';', 500));
        self::assertEquals(true, $success);

        $row = array_pop($this->csv->data);
        $expected_data = array_fill(0, 500, $content);
        $expected_data[] = '';
        self::assertEquals($expected_data, $row);
    }

    /**
     * @depends      testParse
     *
     * @dataProvider autoDetectionProvider
     *
     * @param string $file
     */
    public function testSepRowAutoDetection($file) {
        // This file (parse_test.php) is encoded in UTF-8, hence comparison will
        // fail unless we to this:
        $this->csv->output_encoding = 'UTF-8';

        $this->csv->auto($file);
        self::assertEquals($this->_get_magazines_data(), $this->csv->data);
    }

    /**
     * @return array
     */
    public function autoDetectionProvider() {
        return [
            'UTF8_no_BOM' => [__DIR__ . '/../example_files/UTF-8_sep_row_but_no_BOM.csv'],
            'UTF8' => [__DIR__ . '/../example_files/UTF-8_with_BOM_and_sep_row.csv'],
            'UTF16' => [__DIR__ . '/../example_files/UTF-16LE_with_BOM_and_sep_row.csv'],
        ];
    }

    public function testSingleColumnWithZeros() {
        $this->csv->delimiter = null;
        $this->csv->parse(
            "URL\nhttp://www.amazon.com/ROX-Ice-Ball-Maker-Original/dp/B00MX59NMQ/ref=sr_1_1?ie=UTF8&qid=1435604374&sr=8-1&keywords=rox,+ice+molds"
        );
        $row = array_pop($this->csv->data);
        $expected_data = ['URL' => 'http://www.amazon.com/ROX-Ice-Ball-Maker-Original/dp/B00MX59NMQ/ref=sr_1_1?ie=UTF8&qid=1435604374&sr=8-1&keywords=rox,+ice+molds'];
        self::assertEquals($expected_data, $row);
    }

    public function testAllNumericalCsv() {
        $this->csv->heading = false;
        $sInput = "86545235689\r\n34365587654\r\n13469874576";
        self::assertEquals(false, $this->csv->autoDetectionForDataString($sInput));
        self::assertEquals(null, $this->csv->delimiter);
        $expected_data = explode("\r\n", $sInput);
        $actual_data = array_map('reset', $this->csv->data);
        self::assertEquals($expected_data, $actual_data);
    }

    public function testMissingEndingLineBreak() {
        $this->csv->heading = false;
        $this->csv->enclosure = '"';
        $sInput = "86545235689,a\r\n34365587654,b\r\n13469874576,\"c\r\nd\"";
        $expected_data = [86545235689, 34365587654, 13469874576];

        $actual_data = $this->invokeMethod($this->csv, '_parse_string', [$sInput]);
        $actual_column = array_map('reset', $actual_data);
        self::assertEquals($expected_data, $actual_column);
        self::assertEquals(
            [
                'a',
                'b',
                "c\r\nd",
            ],
            array_map('next', $actual_data)
        );
    }

    public function testSingleColumn() {
        $this->csv->auto(__DIR__ . '/../example_files/single_column.csv');
        $expected = [
            ['SMS' => '0444'],
            ['SMS' => '5555'],
            ['SMS' => '6606'],
            ['SMS' => '7777'],
        ];
        self::assertEquals($expected, $this->csv->data);
    }

    public function testSingleRow() {
        $this->csv->auto(__DIR__ . '/../example_files/single_row.csv');
        self::assertEquals([], $this->csv->data, 'Single row is detected as header');
        $this->csv->heading = false;
        $this->csv->auto(__DIR__ . '/../example_files/single_row.csv');
        $expected = [['C1', 'C2', 'C3']];
        self::assertEquals($expected, $this->csv->data);
    }

    public function testMatomoData() {
        // Matomo (Piwik) export cannot be read with
        $this->csv->use_mb_convert_encoding = true;
        $this->csv->output_encoding = 'UTF-8';
        $this->csv->auto(__DIR__ . '/../example_files/Piwik_API_download.csv');
        $aAction27 = array_column($this->csv->data, 'url (actionDetails 27)');
        self::assertEquals(
            [
                'http://application/_Main/_GraphicMeanSTD_MDI/btnConfBandOptions',
                '',
                '',
            ],
            $aAction27
        );

        $aCity = array_column($this->csv->data, 'city');
        self::assertEquals(
            [
                'São Paulo',
                'Johannesburg',
                '',
            ],
            $aCity
        );
    }

    /**
     * Tests if we can handle BOMs in string data, in contrast to loading files.
     */
    public function testStringWithLeadingBOM() {
        $string_with_bom = strtr(
            file_get_contents(__DIR__ . '/../example_files/UTF-8_with_BOM_and_sep_row.csv'),
            ["sep=;\n" => '']
        );

        // Is the BOM still there?
        self::assertSame(0xEF, ord($string_with_bom));

        $this->csv->output_encoding = 'UTF-8';
        $this->csv->delimiter = ';';
        self::assertTrue($this->csv->loadDataString($string_with_bom));
        self::assertTrue($this->csv->parse($this->csv->file_data));

        // This also tests if ::load_data removed the BOM from the data;
        // otherwise the 'title' column would have 3 extra bytes.
        $this->assertEquals(
            [
                'title',
                'isbn',
                'publishedAt',
            ],
            array_keys(reset($this->csv->data)));

        $titles = array_column($this->csv->data, 'title');
        $this->assertEquals(
            [
                'Красивая кулинария',
                'The Wine Connoisseurs',
                'Weißwein',
            ],
            $titles);
    }

    public function testWithMultipleNewlines() {
        $this->csv->auto(__DIR__ . '/../example_files/multiple_empty_lines.csv');
        $aElse9 = array_column($this->csv->data, 'else9');

        /** @noinspection SpellCheckingInspection */
        $this->assertEquals(
            [
                'Abweichung',
                'Abweichung',
                'Abweichung',
                'Alt',
                'Fehlt',
                'Neu',
                'OK',
                'Fehlt',
                'Fehlt',
                'Fehlt',
            ],
            $aElse9);
    }

    /**
     * @depends testSepRowAutoDetection
     */
    public function testGetColumnDatatypes() {
        $this->csv->auto(__DIR__ . '/fixtures/datatype.csv');
        $this->csv->getDatatypes();
        $expected = [
            'title' => 'string',
            'isbn' => 'string',
            'publishedAt' => 'date',
            'published' => 'boolean',
            'count' => 'integer',
            'price' => 'float',
        ];

        self::assertEquals($expected, $this->csv->data_types);
    }

    /**
     * @depends testSepRowAutoDetection
     */
    public function testAutoDetectFileHasHeading() {
        $this->csv->auto(__DIR__ . '/fixtures/datatype.csv');
        self::assertTrue($this->csv->autoDetectFileHasHeading());

        $this->csv->heading = false;
        $this->csv->auto(__DIR__ . '/fixtures/datatype.csv');
        self::assertTrue($this->csv->autoDetectFileHasHeading());

        $this->csv->heading = false;
        $sInput = "86545235689\r\n34365587654\r\n13469874576";
        $this->csv->autoDetectionForDataString($sInput);
        self::assertFalse($this->csv->autoDetectFileHasHeading());

        $this->csv->heading = true;
        $sInput = "86545235689\r\n34365587654\r\n13469874576";
        $this->csv->autoDetectionForDataString($sInput);
        self::assertFalse($this->csv->autoDetectFileHasHeading());
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testVeryLongNonExistingFile() {
        $this->csv->parse(str_repeat('long_string', PHP_MAXPATHLEN));
        $this->csv->auto(str_repeat('long_string', PHP_MAXPATHLEN));
    }

    /**
     * @return array
     */
    protected function _get_magazines_data() {
        return [
            [
                'title' => 'Красивая кулинария',
                'isbn' => '5454-5587-3210',
                'publishedAt' => '21.05.2011',
            ],
            [
                'title' => 'The Wine Connoisseurs',
                'isbn' => '2547-8548-2541',
                'publishedAt' => '12.12.2011',
            ],
            [
                'title' => 'Weißwein',
                'isbn' => '1313-4545-8875',
                'publishedAt' => '23.02.2012',
            ],
        ];
    }

    /**
     * @return array
     */
    public function autoQuotesDataProvider(): array {
        return array(
            array('auto-double-enclosure.csv', '"'),
            array('auto-single-enclosure.csv', "'"),
        );
    }

    /**
     * @depends      testSepRowAutoDetection
     *
     * @dataProvider autoQuotesDataProvider
     *
     * @param string $file
     * @param string $enclosure
     */
    public function testAutoQuotes($file, $enclosure) {
        $csv = new Csv();
        $csv->auto(__DIR__ . '/fixtures/' . $file, true, null, null, $enclosure);
        self::assertArrayHasKey('column1', $csv->data[0], 'Data parsed incorrectly with enclosure ' . $enclosure);
        self::assertEquals('value1', $csv->data[0]['column1'], 'Data parsed incorrectly with enclosure ' . $enclosure);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object $object      Instantiated object that we will run method on.
     * @param string $methodName  Method name to call
     * @param array  $parameters  Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    private function invokeMethod($object, $methodName, $parameters = []) {
        $reflection = new ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testWaiverFieldSeparator() {
        self::assertFalse($this->csv->auto(__DIR__ . '/../example_files/waiver_field_separator.csv'));
        $expected = [
            'liability waiver',
            'release of liability form',
            'release of liability',
            'sample waiver',
            'sample waiver form',
        ];
        $actual = array_column($this->csv->data, 'keyword');
        self::assertSame($expected, $actual);
    }

    public function testEmptyInput() {
        self::assertFalse($this->csv->parse(''));
        self::assertFalse($this->csv->parse(null));
        self::assertFalse($this->csv->parseFile(''));
        self::assertFalse($this->csv->parseFile(null));
    }

    public function testParseFile() {
        $data = $this->csv->parseFile(__DIR__ . '/fixtures/auto-double-enclosure.csv');
        self::assertCount(2, $data);
        self::assertEquals($data, $this->csv->data);
    }
}
