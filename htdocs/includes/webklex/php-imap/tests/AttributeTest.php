<?php
/*
* File: AttributeTest.php
* Category: -
* Author: M.Goldenbaum
* Created: 28.12.22 18:11
* Updated: -
*
* Description:
*  -
*/

namespace Tests;

use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use Webklex\PHPIMAP\Attribute;

class AttributeTest extends TestCase {

    /**
     * String Attribute test
     *
     * @return void
     */
    public function testStringAttribute(): void {
        $attribute = new Attribute("foo", "bar");

        self::assertSame("bar", $attribute->toString());
        self::assertSame("foo", $attribute->getName());
        self::assertSame("foos", $attribute->setName("foos")->getName());
    }

    /**
     * Date Attribute test
     *
     * @return void
     */
    public function testDateAttribute(): void {
        $attribute = new Attribute("foo", "2022-12-26 08:07:14 GMT-0800");

        self::assertInstanceOf(Carbon::class, $attribute->toDate());
        self::assertSame("2022-12-26 08:07:14 GMT-0800", $attribute->toDate()->format("Y-m-d H:i:s T"));
    }

    /**
     * Array Attribute test
     *
     * @return void
     */
    public function testArrayAttribute(): void {
        $attribute = new Attribute("foo", ["bar"]);

        self::assertSame("bar", $attribute->toString());

        $attribute->add("bars");
        self::assertSame(true, $attribute->has(1));
        self::assertSame("bars", $attribute->get(1));
        self::assertSame(true, $attribute->contains("bars"));
        self::assertSame("foo, bars", $attribute->set("foo", 0)->toString());

        $attribute->remove(0);
        self::assertSame("bars", $attribute->toString());

        self::assertSame("bars, foos", $attribute->merge(["foos", "bars"], true)->toString());
        self::assertSame("bars, foos, foos, donk", $attribute->merge(["foos", "donk"], false)->toString());

        self::assertSame(4, $attribute->count());

        self::assertSame("donk", $attribute->last());
        self::assertSame("bars", $attribute->first());

        self::assertSame(["bars", "foos", "foos", "donk"], array_values($attribute->all()));
    }
}