<?php
declare(strict_types = 1);

namespace DASPRiD\EnumTest;

use DASPRiD\Enum\Exception\CloneNotSupportedException;
use DASPRiD\Enum\Exception\SerializeNotSupportedException;
use DASPRiD\Enum\Exception\UnserializeNotSupportedException;
use DASPRiD\Enum\NullValue;
use PHPUnit\Framework\TestCase;

final class NullValueTest extends TestCase
{
    public function testExceptionOnCloneAttempt() : void
    {
        $this->expectException(CloneNotSupportedException::class);
        clone NullValue::instance();
    }

    public function testExceptionOnSerializeAttempt() : void
    {
        $this->expectException(SerializeNotSupportedException::class);
        serialize(NullValue::instance());
    }

    public function testExceptionOnUnserializeAttempt() : void
    {
        $this->expectException(UnserializeNotSupportedException::class);
        unserialize('O:22:"DASPRiD\\Enum\\NullValue":0:{}');
    }
}
