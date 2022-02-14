<?php

namespace ParseCsv\tests\properties;

/**
 * Tests related to the $offset property
 */
class OffsetTest extends BaseClass {

    public function testOffsetOfOne() {
        $this->csv->offset = 1;
        $this->csv->auto(__DIR__ . '/../methods/fixtures/datatype.csv');
        $this->assertCount(3, $this->csv->data);

        if (!function_exists('array_column')) {
            // function only available in PHP >= 5.5
            return;
        }
        $expected = [
            'Красивая кулинария',
            'The Wine Connoisseurs',
            'Weißwein',
        ];
        $actual = array_column($this->csv->data, 'title');
        $this->assertEquals($expected, $actual);
    }

    public function numberRangeZeroToFourProvider() {
        return array_map(function ($number) {
            return [$number];
        }, range(0, 4));
    }

    /**
     * @dataProvider numberRangeZeroToFourProvider
     *
     * @param int $offset
     */
    public function testOffsetOfOneNoHeader($offset) {
        $this->csv->offset = $offset;
        $this->csv->heading = false;
        $this->csv->auto(__DIR__ . '/../methods/fixtures/datatype.csv');
        $this->assertCount(4 - $offset, $this->csv->data);
    }

    public function testDataArrayKeysWhenSettingOffsetWithHeading() {
        $this->csv->offset = 2;
        $this->csv->auto(__DIR__ . '/../methods/fixtures/datatype.csv');
        $expected = [
            [
                'title' => 'The Wine Connoisseurs',
                'isbn' => '2547-8548-2541',
                'publishedAt' => '12.12.2011',
                'published' => 'TRUE',
                'count' => '',
                'price' => 20.33,
            ],
            [
                'title' => 'Weißwein',
                'isbn' => '1313-4545-8875',
                'publishedAt' => '23.02.2012',
                'published' => 'false',
                'count' => 10,
                'price' => 10,
            ],
        ];

        $this->assertEquals($expected, $this->csv->data);
    }

    public function testDataArrayKeysWhenSettingOffsetWithoutHeading() {
        $this->csv->heading = false;
        $this->csv->offset = 2;
        $this->csv->auto(__DIR__ . '/../methods/fixtures/datatype.csv');
        $expected = range(0, 5, 1);

        $this->assertEquals($expected, array_keys($this->csv->data[0]));
    }
}
