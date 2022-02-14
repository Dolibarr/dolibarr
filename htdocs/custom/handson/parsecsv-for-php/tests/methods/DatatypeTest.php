<?php
/**
 * Created by PhpStorm.
 * User: sgorzaly
 * Date: 19.02.18
 * Time: 20:52
 */

namespace ParseCsv\tests\methods;

use ParseCsv\enums\DatatypeEnum;
use PHPUnit\Framework\TestCase;

class DatatypeTest extends TestCase {

    public function testSampleIsValidInteger() {
        $this->assertEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('1'));
        $this->assertEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('+1'));
        $this->assertEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('-1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('--1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('test'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('1.0'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('1,0'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('1,1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('0.1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('true'));
        $this->assertNotEquals(DatatypeEnum::TYPE_INT, DatatypeEnum::getValidTypeFromSample('2018-02-19'));
    }

    public function testSampleIsValidBool() {
        $this->assertEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('true'));
        $this->assertEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('TRUE'));
        $this->assertEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('false'));
        $this->assertEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('FALSE'));
        $this->assertNotEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('FALS'));
        $this->assertNotEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('test'));
        $this->assertNotEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('0'));
        $this->assertNotEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_BOOL, DatatypeEnum::getValidTypeFromSample('0.1'));
    }

    public function testSampleIsValidFloat() {
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1.0'));
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('-1.1'));
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('+1,1'));
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1,1'));
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1,1'));
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1e-03'));
        $this->assertEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1e+03'));
        $this->assertNotEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('test'));
        $this->assertNotEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1,,1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('1..1'));
        $this->assertNotEquals(DatatypeEnum::TYPE_FLOAT, DatatypeEnum::getValidTypeFromSample('2018-02-19'));
    }

    public function testSampleIsValidDate() {
        $this->assertEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('2018-02-19'));
        $this->assertEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('18-2-19'));
        $this->assertEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('01.02.2018'));
        $this->assertEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('1.2.18'));
        $this->assertEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('07/31/2018'));
        $this->assertNotEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('31/07/2018'));
        $this->assertNotEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('1-2'));
        $this->assertEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('1.2.'));
        $this->assertNotEquals(DatatypeEnum::TYPE_DATE, DatatypeEnum::getValidTypeFromSample('test'));
    }

}
