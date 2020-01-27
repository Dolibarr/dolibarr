<?php

namespace Sabre\HTTP;

class FunctionsTest extends \PHPUnit_Framework_TestCase {

    /**
     * @dataProvider getHeaderValuesData
     */
    function testGetHeaderValues($input, $output) {

        $this->assertEquals(
            $output,
            getHeaderValues($input)
        );

    }

    function getHeaderValuesData() {

        return [
            [
                "a",
                ["a"]
            ],
            [
                "a,b",
                ["a", "b"]
            ],
            [
                "a, b",
                ["a", "b"]
            ],
            [
                ["a, b"],
                ["a", "b"]
            ],
            [
                ["a, b", "c", "d,e"],
                ["a", "b", "c", "d", "e"]
            ],
        ];

    }

    /**
     * @dataProvider preferData
     */
    function testPrefer($input, $output) {

        $this->assertEquals(
            $output,
            parsePrefer($input)
        );

    }

    function preferData() {

        return [
            [
                'foo; bar',
                ['foo' => true]
            ],
            [
                'foo; bar=""',
                ['foo' => true]
            ],
            [
                'foo=""; bar',
                ['foo' => true]
            ],
            [
                'FOO',
                ['foo' => true]
            ],
            [
                'respond-async',
                ['respond-async' => true]
            ],
            [

                ['respond-async, wait=100', 'handling=lenient'],
                ['respond-async' => true, 'wait' => 100, 'handling' => 'lenient']
            ],
            [

                ['respond-async, wait=100, handling=lenient'],
                ['respond-async' => true, 'wait' => 100, 'handling' => 'lenient']
            ],
            // Old values
            [

                'return-asynch, return-representation',
                ['respond-async' => true, 'return' => 'representation'],
            ],
            [

                'return-minimal',
                ['return' => 'minimal'],
            ],
            [

                'strict',
                ['handling' => 'strict'],
            ],
            [

                'lenient',
                ['handling' => 'lenient'],
            ],
            // Invalid token
            [
                ['foo=%bar%'],
                [],
            ]
        ];

    }

}
