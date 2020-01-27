<?php

namespace Sabre\DAV;

class StringUtilTest extends \PHPUnit_Framework_TestCase {

    /**
     * @param string $haystack
     * @param string $needle
     * @param string $collation
     * @param string $matchType
     * @param string $result
     * @throws Exception\BadRequest
     *
     * @dataProvider dataset
     */
    function testTextMatch($haystack, $needle, $collation, $matchType, $result) {

        $this->assertEquals($result, StringUtil::textMatch($haystack, $needle, $collation, $matchType));

    }

    function dataset() {

        return [
            ['FOOBAR', 'FOO',    'i;octet', 'contains', true],
            ['FOOBAR', 'foo',    'i;octet', 'contains', false],
            ['FÖÖBAR', 'FÖÖ',    'i;octet', 'contains', true],
            ['FÖÖBAR', 'föö',    'i;octet', 'contains', false],
            ['FOOBAR', 'FOOBAR', 'i;octet', 'equals', true],
            ['FOOBAR', 'fooBAR', 'i;octet', 'equals', false],
            ['FOOBAR', 'FOO',    'i;octet', 'starts-with', true],
            ['FOOBAR', 'foo',    'i;octet', 'starts-with', false],
            ['FOOBAR', 'BAR',    'i;octet', 'starts-with', false],
            ['FOOBAR', 'bar',    'i;octet', 'starts-with', false],
            ['FOOBAR', 'FOO',    'i;octet', 'ends-with', false],
            ['FOOBAR', 'foo',    'i;octet', 'ends-with', false],
            ['FOOBAR', 'BAR',    'i;octet', 'ends-with', true],
            ['FOOBAR', 'bar',    'i;octet', 'ends-with', false],

            ['FOOBAR', 'FOO',    'i;ascii-casemap', 'contains', true],
            ['FOOBAR', 'foo',    'i;ascii-casemap', 'contains', true],
            ['FÖÖBAR', 'FÖÖ',    'i;ascii-casemap', 'contains', true],
            ['FÖÖBAR', 'föö',    'i;ascii-casemap', 'contains', false],
            ['FOOBAR', 'FOOBAR', 'i;ascii-casemap', 'equals', true],
            ['FOOBAR', 'fooBAR', 'i;ascii-casemap', 'equals', true],
            ['FOOBAR', 'FOO',    'i;ascii-casemap', 'starts-with', true],
            ['FOOBAR', 'foo',    'i;ascii-casemap', 'starts-with', true],
            ['FOOBAR', 'BAR',    'i;ascii-casemap', 'starts-with', false],
            ['FOOBAR', 'bar',    'i;ascii-casemap', 'starts-with', false],
            ['FOOBAR', 'FOO',    'i;ascii-casemap', 'ends-with', false],
            ['FOOBAR', 'foo',    'i;ascii-casemap', 'ends-with', false],
            ['FOOBAR', 'BAR',    'i;ascii-casemap', 'ends-with', true],
            ['FOOBAR', 'bar',    'i;ascii-casemap', 'ends-with', true],

            ['FOOBAR', 'FOO',    'i;unicode-casemap', 'contains', true],
            ['FOOBAR', 'foo',    'i;unicode-casemap', 'contains', true],
            ['FÖÖBAR', 'FÖÖ',    'i;unicode-casemap', 'contains', true],
            ['FÖÖBAR', 'föö',    'i;unicode-casemap', 'contains', true],
            ['FOOBAR', 'FOOBAR', 'i;unicode-casemap', 'equals', true],
            ['FOOBAR', 'fooBAR', 'i;unicode-casemap', 'equals', true],
            ['FOOBAR', 'FOO',    'i;unicode-casemap', 'starts-with', true],
            ['FOOBAR', 'foo',    'i;unicode-casemap', 'starts-with', true],
            ['FOOBAR', 'BAR',    'i;unicode-casemap', 'starts-with', false],
            ['FOOBAR', 'bar',    'i;unicode-casemap', 'starts-with', false],
            ['FOOBAR', 'FOO',    'i;unicode-casemap', 'ends-with', false],
            ['FOOBAR', 'foo',    'i;unicode-casemap', 'ends-with', false],
            ['FOOBAR', 'BAR',    'i;unicode-casemap', 'ends-with', true],
            ['FOOBAR', 'bar',    'i;unicode-casemap', 'ends-with', true],
        ];

    }

    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testBadCollation() {

        StringUtil::textMatch('foobar', 'foo', 'blabla', 'contains');

    }


    /**
     * @expectedException Sabre\DAV\Exception\BadRequest
     */
    function testBadMatchType() {

        StringUtil::textMatch('foobar', 'foo', 'i;octet', 'booh');

    }

    function testEnsureUTF8_ascii() {

        $inputString = "harkema";
        $outputString = "harkema";

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );

    }

    function testEnsureUTF8_latin1() {

        $inputString = "m\xfcnster";
        $outputString = "münster";

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );

    }

    function testEnsureUTF8_utf8() {

        $inputString = "m\xc3\xbcnster";
        $outputString = "münster";

        $this->assertEquals(
            $outputString,
            StringUtil::ensureUTF8($inputString)
        );

    }

}
