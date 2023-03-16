<?php
declare(strict_types = 1);

namespace DASPRiD\EnumTest;

use DASPRiD\Enum\AbstractEnum;
use DASPRiD\Enum\Exception\CloneNotSupportedException;
use DASPRiD\Enum\Exception\IllegalArgumentException;
use DASPRiD\Enum\Exception\MismatchException;
use DASPRiD\Enum\Exception\SerializeNotSupportedException;
use DASPRiD\Enum\Exception\UnserializeNotSupportedException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class AbstractEnumTest extends TestCase
{
    public function setUp(): void
    {
        $reflectionClass = new ReflectionClass(AbstractEnum::class);

        $constantsProperty = $reflectionClass->getProperty('constants');
        $constantsProperty->setAccessible(true);
        $constantsProperty->setValue([]);

        $valuesProperty = $reflectionClass->getProperty('values');
        $valuesProperty->setAccessible(true);
        $valuesProperty->setValue([]);

        $allValuesLoadedProperty = $reflectionClass->getProperty('allValuesLoaded');
        $allValuesLoadedProperty->setAccessible(true);
        $allValuesLoadedProperty->setValue([]);
    }

    public function testToString() : void
    {
        $weekday = WeekDay::FRIDAY();
        self::assertSame('FRIDAY', (string) $weekday);
    }

    public function testName() : void
    {
        $this->assertSame('WEDNESDAY', WeekDay::WEDNESDAY()->name());
    }

    public function testOrdinal() : void
    {
        $this->assertSame(2, WeekDay::WEDNESDAY()->ordinal());
    }

    public function testSameInstanceIsReturned() : void
    {
        self::assertSame(WeekDay::FRIDAY(), WeekDay::FRIDAY());
    }

    public static function testValueOf() : void
    {
        self::assertSame(WeekDay::FRIDAY(), WeekDay::valueOf('FRIDAY'));
    }

    public function testValueOfInvalidConstant() : void
    {
        $this->expectException(IllegalArgumentException::class);
        WeekDay::valueOf('CATURDAY');
    }

    public function testExceptionOnCloneAttempt() : void
    {
        $this->expectException(CloneNotSupportedException::class);
        clone WeekDay::FRIDAY();
    }

    public function testExceptionOnSerializeAttempt() : void
    {
        $this->expectException(SerializeNotSupportedException::class);
        serialize(WeekDay::FRIDAY());
    }

    public function testExceptionOnUnserializeAttempt() : void
    {
        $this->expectException(UnserializeNotSupportedException::class);
        unserialize('O:24:"DASPRiD\\EnumTest\\WeekDay":0:{}');
    }

    public function testReturnValueOfValuesIsSortedByOrdinal() : void
    {
        // Initialize some week days out of order
        WeekDay::SATURDAY();
        WeekDay::TUESDAY();

        $ordinals = array_values(array_map(function (WeekDay $weekDay) : int {
            return $weekDay->ordinal();
        }, WeekDay::values()));

        self::assertSame([0, 1, 2, 3, 4, 5, 6], $ordinals);

        $cachedOrdinals = array_values(array_map(function (WeekDay $weekDay) : int {
            return $weekDay->ordinal();
        }, WeekDay::values()));
        $this->assertSame($ordinals, $cachedOrdinals);
    }

    public function testCompareTo() : void
    {
        $this->assertSame(-4, WeekDay::WEDNESDAY()->compareTo(WeekDay::SUNDAY()));
        $this->assertSame(4, WeekDay::SUNDAY()->compareTo(WeekDay::WEDNESDAY()));
        $this->assertSame(0, WeekDay::WEDNESDAY()->compareTo(WeekDay::WEDNESDAY()));
    }

    public function testCompareToWrongEnum() : void
    {
        $this->expectException(MismatchException::class);
        WeekDay::MONDAY()->compareTo(Planet::EARTH());
    }

    public function testParameterizedEnum() : void
    {
        $planet = Planet::EARTH();
        $this->assertSame(5.976e+24, $planet->mass());
        $this->assertSame(6.37814e6, $planet->radius());
    }
}
