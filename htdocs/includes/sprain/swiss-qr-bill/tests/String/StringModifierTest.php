<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\String;

use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\String\StringModifier;

final class StringModifierTest extends TestCase
{
    /**
     * @dataProvider lineBreaksAndTabsProvider
     */
    public function testRemoveLineBreaksAndTabs(?string $string, string $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            StringModifier::replaceLineBreaksAndTabsWithSpaces($string)
        );
    }

    public function lineBreaksAndTabsProvider(): array
    {
        return [
            [null, ''],
            ["foo\nbar\rbaz\r\n", "foo bar baz  "],
            ["\n\nfoo\nbar\tbaz\n\n", "  foo bar baz  "],
            ["\n\nfoo\n\nbar\t\nbaz\n\n", "  foo  bar  baz  "],
        ];
    }

    /**
     * @dataProvider multipleSpacesProvider
     */
    public function testReplaceMultipleSpacesWithOne(?string $string, string $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            StringModifier::replaceMultipleSpacesWithOne($string)
        );
    }

    public function multipleSpacesProvider(): array
    {
        return [
            [null, ''],
            [" foo bar baz ", " foo bar baz "],
            ["  foo  bar  baz  ", " foo bar baz "],
            ["foo  bar  baz", "foo bar baz"],
        ];
    }

    /**
     * @dataProvider stripWhitespaceProvider
     */
    public function testStripWhitespace(?string $string, string $expectedResult): void
    {
        $this->assertSame(
            $expectedResult,
            StringModifier::stripWhitespace($string)
        );
    }

    public function stripWhitespaceProvider(): array
    {
        return [
            [null, ''],
            ['1 ', '1'],
            [' 2', '2'],
            [' foo ', 'foo'],
            ['   foo   ', 'foo'],
            ['   foo   bar   ', 'foobar'],
        ];
    }
}