<?php

namespace ParseCsv\tests\methods;

use ParseCsv\Csv;
use PHPUnit\Framework\TestCase;

/**
 * Writes roughly 1 MB of data. This is useful because of a limit of 8 KB
 * encountered with stream operations in certain PHP versions.
 */
class StreamTest extends TestCase {

    protected function setUp(): void {
        static $done;
        if ($done) {
            // Make sure we register the stream just once - or PHP will scream.
            return;
        }

        stream_wrapper_register("example", ExampleStream::class)
        or die("Failed to register protocol");
        $done = 1;
    }

    public function testReadStream() {
        $csv = new Csv();

        // Write data to our stream:
        $filename = 'example://tmp';
        copy(__DIR__ . '/fixtures/datatype.csv', $filename);
        $many_dots = str_repeat('.', 1000 * 1000) . ";;;;;\n";
        file_put_contents($filename, $many_dots, FILE_APPEND);

        self::assertSame(';', $csv->auto(file_get_contents($filename)));
        self::assertCount(4, $csv->data);
        self::assertCount(6, reset($csv->data));
    }

    public function testWriteStream() {
        $csv = new Csv();
        $csv->linefeed = "\n";
        $many_dots = str_repeat('.', 1000 * 1000);
        $csv->data = [
            [
                'Name' => 'Rudolf',
                'Question' => 'Which color is his nose?',
            ],
            [
                'Name' => 'Sponge Bob',
                'Question' => 'Which shape are his pants?',
            ],
            [
                'Name' => $many_dots,
                'Question' => 'Can you count one million dots?',
            ],
        ];

        // Just export the first column, but with a new name
        $csv->titles = ['Name' => 'Character'];

        // Write data to our stream:
        $filename = 'example://data';
        copy(__DIR__ . '/fixtures/datatype.csv', $filename);

        self::assertSame(true, $csv->save($filename));
        $expected = "Character\nRudolf\nSponge Bob\n";
        $expected .= $many_dots . "\n";
        self::assertSame($expected, file_get_contents($filename));
    }
}
