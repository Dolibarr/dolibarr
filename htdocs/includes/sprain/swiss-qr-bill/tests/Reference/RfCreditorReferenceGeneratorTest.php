<?php declare(strict_types=1);

namespace Sprain\Tests\SwissQrBill\Reference;

use Sprain\SwissQrBill\Reference\RfCreditorReferenceGenerator;
use PHPUnit\Framework\TestCase;
use Sprain\SwissQrBill\String\StringModifier;

final class RfCreditorReferenceGeneratorTest extends TestCase
{
    /**
     * @dataProvider rfCreditorReferenceProvider
     */
    public function testMakesResultsViaConstructor(string $input): void
    {
        $generator = new RfCreditorReferenceGenerator($input);

        $output = $generator->doGenerate();

        $this->assertStringContainsStringIgnoringCase(
            StringModifier::stripWhitespace($input),
            StringModifier::stripWhitespace($output)
        );
    }

    /**
     * @dataProvider rfCreditorReferenceProvider
     */
    public function testMakesResultsViaFacade(string $input): void
    {
        $output = RfCreditorReferenceGenerator::generate($input);

        $this->assertStringContainsStringIgnoringCase(
            StringModifier::stripWhitespace($input),
            StringModifier::stripWhitespace($output)
        );
    }

    public function rfCreditorReferenceProvider(): array
    {
        return [
            ['1'],
            ['a'],
            ['B'],
            ['aBcD eFgH iJkL mNoP qR12 3'],
        ];
    }

    /**
     * @dataProvider invalidReferenceProvider
     */
    public function testInvalidReference(string $input): void
    {
        $generator = new RfCreditorReferenceGenerator($input);

        $this->assertGreaterThan(0, $generator->getViolations()->count());
    }

    public function invalidReferenceProvider(): array
    {
        return [
            ['aBcD eFgH iJkL mNoP qR12 34'], // to long
            [''], // to short
            ['123ä'], // invalid letter
            ['123.'], // invalid symbol
        ];
    }

}