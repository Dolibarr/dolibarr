<?php

namespace Sabre\Xml\Deserializer;

use
    Sabre\Xml\Reader;

class KeyValueTest extends \PHPUnit_Framework_TestCase {

    function testKeyValue() {

        $input = <<<BLA
<?xml version="1.0"?>
<root xmlns="http://sabredav.org/ns">
  <struct>
    <elem1 />
    <elem2>hi</elem2>
    <elem3 xmlns="http://sabredav.org/another-ns">
       <elem4>foo</elem4>
       <elem5>foo &amp; bar</elem5>
    </elem3>
  </struct>
</root>
BLA;

        $reader = new Reader();
        $reader->elementMap = [
            '{http://sabredav.org/ns}struct' => function(Reader $reader) {
                return keyValue($reader, 'http://sabredav.org/ns');
            }
        ];
        $reader->xml($input);
        $output = $reader->parse();

        $this->assertEquals([
            'name'  => '{http://sabredav.org/ns}root',
            'value' => [
                [
                    'name'  => '{http://sabredav.org/ns}struct',
                    'value' => [
                        'elem1'                                 => null,
                        'elem2'                                 => 'hi',
                        '{http://sabredav.org/another-ns}elem3' => [
                            [
                                'name'       => '{http://sabredav.org/another-ns}elem4',
                                'value'      => 'foo',
                                'attributes' => [],
                            ],
                            [
                                'name'       => '{http://sabredav.org/another-ns}elem5',
                                'value'      => 'foo & bar',
                                'attributes' => [],
                            ],
                        ]
                    ],
                    'attributes' => [],
                ]
            ],
            'attributes' => [],
        ], $output);
    }

    /**
     * @expectedException \Sabre\Xml\LibXMLException
     */
    function testKeyValueLoop() {

        /**
         * This bug is a weird one, because it triggers an infinite loop, but
         * only if the XML document is a certain size (in bytes). Removing one
         * or two characters from the xml body here cause the infinite loop to
         * *not* get triggered, so to properly test this bug (Issue #94), don't
         * change the XML body.
         */
        $invalid_xml = '
        <foo ft="PRNTING" Ppt="YES" AutoClose="YES" SkipUnverified="NO" Test="NO">
            <Package ID="1">
                <MailClass>NONE</MailClass>
                <PackageType>ENVELOPE</PackageType>
                <WeightOz>1</WeightOz>
                <FleetType>DC</FleetType>
            <Package ID="2">
                <MailClass>NONE</MailClass>
                <PackageType>ENVELOPE</PackageType>
                <WeightOz>1</WeightOz>
                <FleetType>DC/FleetType>
            </Package>
        </foo>';
        $reader = new Reader();

        $reader->xml($invalid_xml);
        $reader->elementMap = [

            '{}Package' => function($reader) {
                $recipient = [];
                // Borrowing a parser from the KeyValue class.
                $keyValue = keyValue($reader);

                if (isset($keyValue['{}WeightOz'])){
                    $recipient['referenceId'] = $keyValue['{}WeightOz'];
                }

                return $recipient;
            },
        ];

        $reader->parse();


    }

}
