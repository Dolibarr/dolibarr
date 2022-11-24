<?php

namespace MathPHP\Tests\Number;

use MathPHP\Number\ArbitraryInteger;
use MathPHP\Number\Rational;
use MathPHP\Exception;

class ArbitraryIntegerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test createZeroValue
     */
    public function testCreateZeroValue()
    {
        // Given
        $zero = ArbitraryInteger::createZeroValue();

        // Then
        $this->assertEquals(0, $zero->toInt());
    }

    /**
     * @test         String representation
     * @dataProvider dataProviderForIntToString
     * @dataProvider dataProviderForStringIntToString
     * @dataProvider dataProviderForBinaryToString
     * @dataProvider dataProviderForHexToString
     * @dataProvider dataProviderForOctalToString
     * @param        mixed $int
     * @param        string $expected
     * @throws       \Exception
     */
    public function testStringToString($int, string $expected)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $stringRepresentation = (string) $obj;

        // Then
        $this->assertSame($expected, $stringRepresentation);
    }

    /**
     * @return array (numberAsString, stringRepresentation)
     */
    public function dataProviderForIntToString(): array
    {
        return [
            [-1, '-1'],
            [0, '0'],
            [1, '1'],
            [2, '2'],
            [3, '3'],
            [4, '4'],
            [5, '5'],
            [6, '6'],
            [7, '7'],
            [8, '8'],
            [9, '9'],
            [10, '10'],
            [200, '200'],
            [123456789012, '123456789012'],
            [-200, '-200'],
            [-31415, '-31415'],
        ];
    }

    /**
     * @return array (numberAsString, stringRepresentation)
     */
    public function dataProviderForStringIntToString(): array
    {
        return [
            ['-1', '-1'],
            ['0', '0'],
            ['1', '1'],
            ['2', '2'],
            ['3', '3'],
            ['4', '4'],
            ['5', '5'],
            ['6', '6'],
            ['7', '7'],
            ['8', '8'],
            ['9', '9'],
            ['10', '10'],
            ['200', '200'],
            ['123456789012345678901234567890', '123456789012345678901234567890'],
            ['-200', '-200'],
            ['-31415', '-31415'],
        ];
    }

    /**
     * @return array (numberAsString, stringRepresentation)
     */
    public function dataProviderForBinaryToString(): array
    {
        return [
            ['0b0', '0'],
            ['0b1', '1'],
            ['0b10', '2'],
            ['0b11', '3'],
            ['0b100', '4'],
            ['0b101', '5'],
            ['0b110', '6'],
            ['0b111', '7'],
            ['0b1000', '8'],
            ['0b1101', '13'],
            ['0b10001111110000110000011101111111001101110111', '9879237948279'],
            ['-0b1', '-1'],
            ['-0b10', '-2'],
            ['-0b11', '-3'],
            ['-0b100', '-4'],
            ['-0b101', '-5'],
            ['-0b110', '-6'],
            ['-0b111', '-7'],
            ['-0b1000', '-8'],
            ['-0b1101', '-13'],
            ['-0b10001111110000110000011101111111001101110111', '-9879237948279'],
        ];
    }

    /**
     * @return array (numberAsString, stringRepresentation)
     */
    public function dataProviderForHexToString(): array
    {
        return [
            ['0x0', '0'],
            ['0x1', '1'],
            ['0x2', '2'],
            ['0x3', '3'],
            ['0x4', '4'],
            ['0x5', '5'],
            ['0x6', '6'],
            ['0x7', '7'],
            ['0x8', '8'],
            ['0x9', '9'],
            ['0xA', '10'],
            ['0xB', '11'],
            ['0xC', '12'],
            ['0xD', '13'],
            ['0xE', '14'],
            ['0xF', '15'],
            ['0x10', '16'],
            ['0x11', '17'],
            ['-0x1', '-1'],
            ['-0x2', '-2'],
            ['-0x3', '-3'],
            ['-0x4', '-4'],
            ['-0x5', '-5'],
            ['-0x6', '-6'],
            ['-0x7', '-7'],
            ['-0x8', '-8'],
            ['-0x9', '-9'],
            ['-0xA', '-10'],
            ['-0xB', '-11'],
            ['-0xC', '-12'],
            ['-0xD', '-13'],
            ['-0xE', '-14'],
            ['-0xF', '-15'],
            ['-0x10', '-16'],
            ['-0x11', '-17'],
            ['0xff', '255'],
            ['-0xff', '-255'],
            ['0x7fff', '32767'],
            ['-0x7fff', '-32767'],
            ['0x7FFF', '32767'],
            ['-0x7FFF', '-32767'],
            ['0x8FC3077F377', '9879237948279'],
            ['-0x8FC3077F377', '-9879237948279'],
        ];
    }

    /**
     * @return array (numberAsString, stringRepresentation)
     */
    public function dataProviderForOctalToString(): array
    {
        return [
            ['00', '0'],
            ['01', '1'],
            ['02', '2'],
            ['03', '3'],
            ['04', '4'],
            ['05', '5'],
            ['06', '6'],
            ['07', '7'],
            ['010', '8'],
            ['011', '9'],
            ['012', '10'],
            ['-01', '-1'],
            ['-02', '-2'],
            ['-03', '-3'],
            ['-04', '-4'],
            ['-05', '-5'],
            ['-06', '-6'],
            ['-07', '-7'],
            ['-010', '-8'],
            ['-011', '-9'],
            ['-012', '-10'],
            ['0127', '87'],
            ['-0127', '-87'],
            ['077777', '32767'],
            ['-077777', '-32767'],
        ];
    }

    /**
     * @test         Int representation
     * @dataProvider dataProviderForIntToInt
     * @param        int $int
     * @throws       \Exception
     */
    public function testIntToInt(int $int)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $intRepresentation = $obj->toInt();

        // Then
        $this->assertSame($int, $intRepresentation);
    }

    public function dataProviderForIntToInt(): array
    {
        return [
            [0],
            [1],
            [-1],
            [2],
            [-2],
            [200],
            [123456],
            [PHP_INT_MAX],
            [PHP_INT_MIN],
            [32767],
            [-32767],
            [31415],
            [-31415],
        ];
    }

    /**
     * @test         Float representation
     * @dataProvider dataProviderForStringToFloat
     * @param        string $int
     * @param        float $float
     * @throws       \Exception
     */
    public function testIntToFloat(string $int, float $float)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $floatRepresentation = $obj->toFloat();

        // Then
        $this->assertSame($float, $floatRepresentation);
    }

    public function dataProviderForStringToFloat(): array
    {
        return [
            ['0', 0.0],
            ['1', 1.0],
            ['-1', -1.0],
            ['2', 2.0],
            ['-2', -2.0],
            ['200', 200.0],
            ['123456', 123456.0],
            ['32767', 32767.0],
            ['-32767', -32767.0],
            ['9223372036854775807', 9.223372036854775807E+18],
            ['9223372036854775808', 9.223372036854775808E18],
            ['-9223372036854775808', -9.223372036854775808E18],
            ['-9223372036854775809', -9.223372036854775809E+18],
        ];
    }

    /**
     * @test         Binary representation
     * @dataProvider dataProviderForIntToInt
     * @param        int $int
     * @throws       \Exception
     */
    public function testIntToBinary(int $int)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $binaryRepresentation = $obj->toBinary();

        // Then
        $this->assertNotEmpty($binaryRepresentation);
    }

    /**
     * @test         isPositive
     * @dataProvider dataProviderForPositiveInt
     * @param        string|int $int
     * @throws       \Exception
     */
    public function testIsPositive($int)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $isPositive = $obj->isPositive();

        // Then
        $this->assertTrue($isPositive);
    }

    public function dataProviderForPositiveInt(): array
    {
        return [
            [0],
            [1],
            [2],
            [200],
            [123456],
            [PHP_INT_MAX],
            [32767],
            [31415],
            ['123456789012345678901234567890'],
        ];
    }


    /**
     * @test         isPositive negative numbers
     * @dataProvider dataProviderForNegativeInt
     * @param        string|int $int
     * @throws       \Exception
     */
    public function testIsNotPositive($int)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $isPositive = $obj->isPositive();

        // Then
        $this->assertFalse($isPositive);
    }

    public function dataProviderForNegativeInt(): array
    {
        return [
            [-1],
            [-2],
            [-200],
            [-123456],
            [PHP_INT_MIN],
            [-32767],
            [-31415],
            ['-123456789012345678901234567890'],
        ];
    }
    /**
     * @test         add
     * @dataProvider dataProviderForTestAddition
     * @param        string $int1
     * @param        string $int2
     * @param        string $expected
     * @throws       \Exception
     */
    public function testAddition(string $int1, string $int2, string $expected)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $sum = $int1->add($int2);

        // Then
        $this->assertEquals($expected, (string) $sum);
    }

    public function dataProviderForTestAddition(): array
    {
        return [
            ['1', '0', '1'],
            ['0', '1', '1'],
            ['0', '-1', '-1'],
            ['1', '-1', '0'],
            ['-1', '0', '-1'],
            ['-1', '-2', '-3'],
            ['-2', '-1', '-3'],
            ['32767', '48937', '81704'],
            ['98372985472983', '73468763846876', '171841749319859'],
            ['983759729375923795837849', '98734957979279759843798', '1082494687355203555681647'],
            ['983759729375923795837849', '-98734957979279759843798', '885024771396644035994051'],
        ];
    }

    /**
     * @test         subtract
     * @dataProvider dataProviderForTestSubtract
     * @param        string $int1
     * @param        string $int2
     * @param        string $expected
     * @throws       \Exception
     */
    public function testSubtract(string $int1, string $int2, string $expected)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $difference = $int1->subtract($int2);

        // Then
        $this->assertEquals($expected, (string) $difference);
    }

    public function dataProviderForTestSubtract(): array
    {
        return [
            ['1', '0', '1'],
            ['1', '1', '0'],
            ['0', '1', '-1'],
            ['0', '-1', '1'],
            ['-1', '-2', '1'],
            ['-2', '-1', '-1'],
            ['32767', '48937', '-16170'],
            ['98372985472983', '73468763846876', '24904221626107'],
            ['983759729375923795837849', '98734957979279759843798', '885024771396644035994051'],
        ];
    }

    /**
     * @test         multiply
     * @dataProvider dataProviderForTestMultiplication
     * @param        string $int1
     * @param        string $int2
     * @param        string $expected
     * @throws       \Exception
     */
    public function testMultiplication(string $int1, string $int2, string $expected)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $sum = $int1->multiply($int2);

        // Then
        $this->assertEquals($expected, (string) $sum);
    }

    public function dataProviderForTestMultiplication(): array
    {
        return [
            ['1', '1', '1'],
            ['-1', '1', '-1'],
            ['1', '-1', '-1'],
            ['2', '-1', '-2'],
            ['-1', '2', '-2'],
            ['-300', '-400', '120000'],
            ['983759729375923795837849', '10000000000', '9837597293759237958378490000000000'],
        ];
    }

    /**
     * @test         intdiv and mod calculate the correct whole and remainder
     * @dataProvider dataProviderForIntDivSmallDivisor
     * @param        string $dividend
     * @param        int    $divisor
     * @param        string $expectedQuotient
     * @param        string $expectedMod
     * @throws       \Exception
     */
    public function testIntDivAndModSmallDivisor(string $dividend, int $divisor, string $expectedQuotient, string $expectedMod)
    {
        // Given
        $obj = new ArbitraryInteger($dividend);

        // When
        $quotient = $obj->intdiv($divisor);
        $mod      = $obj->mod($divisor);

        // Then
        $this->assertEquals($expectedQuotient, (string) $quotient);
        $this->assertEquals($expectedMod, (string) $mod);
    }

    /**
     * @test         fullIntdiv calculates the correct whole and remainder
     * @dataProvider dataProviderForIntDivSmallDivisor
     * @param        string $dividend
     * @param        int    $divisor
     * @param        string $expectedQuotient
     * @param        string $expectedMod
     * @throws       \Exception
     */
    public function testFullIntDivSmallDivisor(string $dividend, int $divisor, string $expectedQuotient, string $expectedMod)
    {
        // Given
        $obj = new ArbitraryInteger($dividend);

        // When
        [$quotient, $mod] = $obj->fullIntdiv($divisor);

        // Then
        $this->assertEquals($expectedQuotient, (string) $quotient);
        $this->assertEquals($expectedMod, (string) $mod);
    }

    public function dataProviderForIntDivSmallDivisor(): array
    {
        return [
            ['5', 5, '1', '0'],
            ['10', 5, '2', '0'],
            ['11', 5, '2', '1'],
            ['12', 5, '2', '2'],
            ['2134567896543378631213', 2, '1067283948271689315606', '1'],
            ['2134567896543378631213', 100, '21345678965433786312', '13'],
            ['301', 300, '1', '1'],
            ['-1', 2, '-1', '1'],
            ['-3', 2, '-2', '1'],
            ['3', -2, '-1', '1'],
            ['-12', -5, '3', '3'],
            ['12', -5, '-2', '2'],
            ['-12', 5, '-3', '3'],
        ];
    }

    /**
     * @test         intdiv and mod calculate the correct whole and remainder
     * @dataProvider dataProviderForIntDivLargeDivisor
     * @param        string $dividend
     * @param        string $divisor
     * @param        string $expectedQuotient
     * @param        string $expectedMod
     * @throws       \Exception
     */
    public function testIntDivLargeDivisor(string $dividend, string $divisor, string $expectedQuotient, string $expectedMod)
    {
        // Given
        $obj     = new ArbitraryInteger($dividend);
        $divisor = new ArbitraryInteger($divisor);

        // When
        $quotient = $obj->intdiv($divisor);
        $mod      = $obj->mod($divisor);

        // Then
        $this->assertEquals($expectedQuotient, (string) $quotient);
        $this->assertEquals($expectedMod, (string) $mod);
    }

    /**
     * @test         fullIntdiv calculates the correct whole and remainder
     * @dataProvider dataProviderForIntDivLargeDivisor
     * @param        string $dividend
     * @param        string $divisor
     * @param        string $expectedQuotient
     * @param        string $expectedMod
     * @throws       \Exception
     */
    public function testFullIntDivLargeDivisor(string $dividend, string $divisor, string $expectedQuotient, string $expectedMod)
    {
        // Given
        $obj     = new ArbitraryInteger($dividend);
        $divisor = new ArbitraryInteger($divisor);

        // When
        [$quotient, $mod] = $obj->fullIntdiv($divisor);

        // Then
        $this->assertEquals($expectedQuotient, (string) $quotient);
        $this->assertEquals($expectedMod, (string) $mod);
    }

    public function dataProviderForIntDivLargeDivisor(): array
    {
        return [
            ['2134567896543378631213', '1067283948271689315606', '2', '1'],
            ['2134567896543378631213', '21345678965433786312', '100', '13'],
        ];
    }

    /**
     * @test         pow()
     * @dataProvider dataProviderForPow
     * @param        int    $int
     * @param        int    $exponent
     * @param        string $expected
     */
    public function testPow(int $int, int $exponent, string $expected)
    {
        // Given
        $int = new ArbitraryInteger($int);

        // When
        $pow = $int->pow($exponent);

        // Then
        $this->assertSame($expected, (string) $pow);
    }

    public function dataProviderForPow(): array
    {
        return [
            [1, 0, '1'],
            [1, 1, '1'],
            [1, -1, '1'],
            [-1, -1, '-1'],
            [1, 2, '1'],
            [2, 0, '1'],
            [2, 1, '2'],
            [2, 2, '4'],
            [
                1000000,
                0,
                '1',
            ],
            [
                1000000,
                1,
                '1000000',
            ],
            [
                1000000,
                2,
                '1000000000000',
            ],
            [
                1000000000,
                0,
                '1',
            ],
            [
                1000000000,
                1,
                '1000000000',
            ],
            [
                1000000000,
                6,
                '1000000000000000000000000000000000000000000000000000000',
            ],
            [
                2,
                151,
                '2854495385411919762116571938898990272765493248',
            ],
        ];
    }

    /**
     * @test         pow() return Rational
     * @dataProvider dataProviderForPowRational
     * @param        int      $int
     * @param        int      $exponent
     * @param        Rational $expected
     */
    public function testPowRational(int $int, int $exponent, Rational $expected)
    {
        // Given
        $int = new ArbitraryInteger($int);

        // When
        $pow = $int->pow($exponent);

        // Then
        $this->assertEquals($expected, $pow);
        $this->assertSame((string) $expected, (string) $pow);
    }

    public function dataProviderForPowRational(): array
    {
        return [
            [2, -1, new Rational(0, 1, 2)],
            [2, -2, new Rational(0, 1, 4)],
            [3, -3, new Rational(0, 1, 27)],
            [-2, -1, new Rational(0, -1, 2)],
            [-2, -2, new Rational(0, 1, 4)],
            [-2, -3, new Rational(0, -1, 8)],
            [-2, -4, new Rational(0, 1, 16)],
        ];
    }

    /**
     * @test         abs() returns the proper result
     * @dataProvider dataProviderForAbs
     * @param        mixed $int
     * @param        string $expected
     * @throws       \Exception
     */
    public function testAbs($int, string $expected)
    {
        // Given
        $int = new ArbitraryInteger($int);

        // When
        $abs = $int->abs();

        // Then
        $this->assertEquals($expected, (string) $abs);
    }

    public function dataProviderForAbs(): array
    {
        return [
            [0, '0'],
            [1, '1'],
            [-1, '1'],
            ['-12345678910', '12345678910'],
            ['12345678910', '12345678910'],
            ['-798273948792837498273948289', '798273948792837498273948289'],
            ['798273948792837498273948289', '798273948792837498273948289'],
        ];
    }

    /**
     * @test         negate()
     * @dataProvider dataProviderForNegate
     * @param        string $int
     * @param        string $expected
     * @throws       \Exception
     */
    public function testNegate(string $int, string $expected)
    {
        // Given
        $int = new ArbitraryInteger($int);

        // When
        $neg = $int->negate();

        // Then
        $this->assertEquals($expected, (string) $neg);
    }

    public function dataProviderForNegate(): array
    {
        return [
            ['1', '-1'],
            ['-1', '1'],
            ['2', '-2'],
            ['-2', '2'],
            ['10', '-10'],
            ['-10', '10'],
            ['-123456789101112', '123456789101112'],
            ['123456789101112', '-123456789101112'],
            ['798273948792837498273948289', '-798273948792837498273948289'],
            ['-798273948792837498273948289', '798273948792837498273948289'],
        ];
    }

    /**
     * @test         fact()
     * @dataProvider dataProviderForFact
     * @param        int    $int
     * @param        string $expected
     * @throws       \Exception
     */
    public function testFact(int $int, string $expected)
    {
        // Given
        $int         = new ArbitraryInteger($int);
        $expectedInt = new ArbitraryInteger($expected);

        // When
        $fact = $int->fact();

        // Then
        $this->assertEquals($expectedInt->toBinary(), $fact->toBinary());
        $this->assertTrue($expectedInt->equals($fact));
        $this->assertSame($expected, (string) $fact);
    }

    public function dataProviderForFact(): array
    {
        return [
            [-1, '1'],
            [0, '1'],
            [1, '1'],
            [2, '2'],
            [3, '6'],
            [4, '24'],
            [5, '120'],
            [20, '2432902008176640000'],
            [
                1000,
                '402387260077093773543702433923003985719374864210714632543799910429938512398629020592044208486969404800479988610197196058631666872994808558901323829669944590997424504087073759918823627727188732519779505950995276120874975462497043601418278094646496291056393887437886487337119181045825783647849977012476632889835955735432513185323958463075557409114262417474349347553428646576611667797396668820291207379143853719588249808126867838374559731746136085379534524221586593201928090878297308431392844403281231558611036976801357304216168747609675871348312025478589320767169132448426236131412508780208000261683151027341827977704784635868170164365024153691398281264810213092761244896359928705114964975419909342221566832572080821333186116811553615836546984046708975602900950537616475847728421889679646244945160765353408198901385442487984959953319101723355556602139450399736280750137837615307127761926849034352625200015888535147331611702103968175921510907788019393178114194545257223865541461062892187960223838971476088506276862967146674697562911234082439208160153780889893964518263243671616762179168909779911903754031274622289988005195444414282012187361745992642956581746628302955570299024324153181617210465832036786906117260158783520751516284225540265170483304226143974286933061690897968482590125458327168226458066526769958652682272807075781391858178889652208164348344825993266043367660176999612831860788386150279465955131156552036093988180612138558600301435694527224206344631797460594682573103790084024432438465657245014402821885252470935190620929023136493273497565513958720559654228749774011413346962715422845862377387538230483865688976461927383814900140767310446640259899490222221765904339901886018566526485061799702356193897017860040811889729918311021171229845901641921068884387121855646124960798722908519296819372388642614839657382291123125024186649353143970137428531926649875337218940694281434118520158014123344828015051399694290153483077644569099073152433278288269864602789864321139083506217095002597389863554277196742822248757586765752344220207573630569498825087968928162753848863396909959826280956121450994871701244516461260379029309120889086942028510640182154399457156805941872748998094254742173582401063677404595741785160829230135358081840096996372524230560855903700624271243416909004153690105933983835777939410970027753472000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000'
            ],
        ];
    }

    /**
     * @test         isqrt
     * @dataProvider dataProviderForIsqrt
     * @param        string $number
     * @param        string $expected
     * @throws       \Exception
     */
    public function testIsqrt(string $number, string $expected)
    {
        // Given
        $obj = new ArbitraryInteger($number);

        // When
        $isqrt = $obj->isqrt();

        // Then
        $this->assertEquals($expected, (string) $isqrt);
    }

    public function dataProviderForIsqrt(): array
    {
        return [
            ['0', '0'],
            ['1', '1'],
            ['2', '1'],
            ['3', '1'],
            ['4', '2'],
            ['5', '2'],
            ['6', '2'],
            ['7', '2'],
            ['8', '2'],
            ['9', '3'],
            ['10', '3'],
            ['11', '3'],
            ['12', '3'],
            ['13', '3'],
            ['14', '3'],
            ['15', '3'],
            ['16', '4'],
            ['17', '4'],
            ['18', '4'],
            ['19', '4'],
            ['20', '4'],
            ['110', '10'],
            ['64000', '252'],
            ['33600000', '5796'],
            ['123456789101112', '11111111'],
            ['152399025', '12345'],
            ['152399026', '12345'],
            ['152399024', '12344'],
        ];
    }

    /**
     * @test   isqrt error for negative numbers
     * @throws \Exception
     */
    public function testIsqrtOutOfBoundsError()
    {
        // Given
        $int = new ArbitraryInteger(-1);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $isqrt = $int->isqrt();
    }

    /**
     * @test     leftShift
     * @dataProvider dataProviderForLeftShift
     * @param string $number
     * @param int $bits
     * @param string $expected
     * @throws Exception\BadParameterException
     * @throws Exception\IncorrectTypeException
     */
    public function testLeftShift(string $number, int $bits, string $expected)
    {
        // Given
        $int = new ArbitraryInteger($number);

        // When
        $shiftedInt = $int->leftShift($bits);

        // Then
        $this->assertEquals($expected, (string) $shiftedInt);
    }

    /**
     * @return array (number, bits, expected number)
     */
    public function dataProviderForLeftShift(): array
    {
        return [
            ['0', 0, '0'],
            ['0', 1, '0'],
            ['0', 2, '0'],
            ['1', 1, '2'],
            ['1', 2, '4'],
            ['1', 3, '8'],
            ['1', 4, '16'],
            ['1', 5, '32'],
            ['1', 20, '1048576'],
            ['1', 32, '4294967296'],
            ['1', 62, '4611686018427387904'],
            ['2', 0, '2'],
            ['2', 1, '4'],
            ['2', 2, '8'],
            ['2', 3, '16'],
            ['2', 4, '32'],
            ['2', 49, '1125899906842624'],
            ['2', 99, '1267650600228229401496703205376'],
            ['2', 150, '2854495385411919762116571938898990272765493248'],
        ];
    }

    /**
     * @test         greaterThan
     * @dataProvider dataProviderForGreaterThan
     * @param        string $int1
     * @param        string $int2
     * @throws       \Exception
     */
    public function testGreaterThan(string $int1, string $int2)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $greaterThan = $int1->greaterThan($int2);

        // Then
        $this->assertTrue($greaterThan);
    }

    public function dataProviderForGreaterThan(): array
    {
        return [
            ['0', '-1'],
            ['1', '0'],
            ['1', '-1'],
            ['2', '1'],
            ['10', '4'],
            ['10', '9'],
            ['9839224', '8739'],
            ['2432902008176640000', '123456789101112'],
        ];
    }

    /**
     * @test         not greaterThan
     * @dataProvider dataProviderForNotGreaterThan
     * @param        string $int1
     * @param        string $int2
     * @throws       \Exception
     */
    public function testNotGreaterThan(string $int1, string $int2)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $greaterThan = $int1->greaterThan($int2);

        // Then
        $this->assertFalse($greaterThan);
    }

    public function dataProviderForNotGreaterThan(): array
    {
        return [
            ['-1', '0'],
            ['0', '1'],
            ['2', '3'],
            ['9', '10'],
            ['8739', '9839224'],
            ['123456789101112', '2432902008176640000']
        ];
    }

    /**
     * @test         lessThan
     * @dataProvider dataProviderForNotGreaterThan
     * @param        string $int1
     * @param        string $int2
     * @throws       \Exception
     */
    public function testLessThan(string $int1, string $int2)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $lessThan = $int1->lessThan($int2);

        // Then
        $this->assertTrue($lessThan);
    }

    /**
     * @test         not lessThan
     * @dataProvider dataProviderForGreaterThan
     * @param        string $int1
     * @param        string $int2
     * @throws       \Exception
     */
    public function testNotLessThan(string $int1, string $int2)
    {
        // Given
        $int1 = new ArbitraryInteger($int1);
        $int2 = new ArbitraryInteger($int2);

        // When
        $lessThan = $int1->lessThan($int2);

        // Then
        $this->assertFalse($lessThan);
    }

    /**
     * @test         equals
     * @dataProvider dataProviderForIntToString
     * @dataProvider dataProviderForStringIntToString
     * @dataProvider dataProviderForBinaryToString
     * @dataProvider dataProviderForHexToString
     * @dataProvider dataProviderForOctalToString
     * @param        mixed $int
     * @throws       \Exception
     */
    public function testEquals($int)
    {
        // Given
        $obj = new ArbitraryInteger($int);

        // When
        $equals = $obj->equals($obj);

        // Then
        $this->assertTrue($equals);
    }

    /**
     * @test         not equals
     * @dataProvider dataProviderForIntToString
     * @dataProvider dataProviderForStringIntToString
     * @dataProvider dataProviderForBinaryToString
     * @dataProvider dataProviderForHexToString
     * @dataProvider dataProviderForOctalToString
     * @param        mixed $int
     * @throws       \Exception
     */
    public function testNotEquals($int)
    {
        // Given
        $obj1 = new ArbitraryInteger($int);
        $obj2 = $obj1->add(1);

        // When
        $equals = $obj1->equals($obj2);

        // Then
        $this->assertFalse($equals);
    }

    /**
     * @test   Constructor throws an exception when given an empty string
     * @throws \Exception
     */
    public function testEmptyStringException()
    {
        // Given
        $number = '';

        // Then
        $this->expectException(Exception\BadParameterException::class);

        // When
        $int = new ArbitraryInteger($number);
    }

    /**
     * @test         Constructor throws an exception when given a float
     * @dataProvider dataProviderForTestIncorrectTypeException
     * @param        mixed $number
     * @throws       \Exception
     */
    public function testIncorrectTypeException($number)
    {
        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $int = new ArbitraryInteger($number);
    }

    public function dataProviderForTestIncorrectTypeException(): array
    {
        return [
            'float'  => [3.14],
            'array'  => [['123', '456']],
            'bool'   => [true],
            'object' => [new \stdClass()],
        ];
    }

    /**
     * @test   prepareParameter throws an exception when an object is provided
     * @throws \Exception
     */
    public function testIncorrectTypeExceptionPrepareParameter()
    {
        // Given
        $number = new ArbitraryInteger(0);
        $class  = new \stdClass();

        // Then
        $this->expectException(Exception\IncorrectTypeException::class);

        // When
        $int = $number->add($class);
    }

    /**
     * @test   pow throws an exception when exponent is negative and int is large.
     * @throws \Exception
     */
    public function testPowException()
    {
        // Given
        $int = new ArbitraryInteger(\PHP_INT_MAX);

        // Then
        $this->expectException(Exception\OutOfBoundsException::class);

        // When
        $pow = $int->add(1)->pow(-1);
    }
}
