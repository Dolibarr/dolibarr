<?php

namespace Stripe;

class UtilTest extends TestCase
{
    public function testIsList()
    {
        $list = [5, 'nstaoush', []];
        $this->assertTrue(Util\Util::isList($list));

        $notlist = [5, 'nstaoush', [], 'bar' => 'baz'];
        $this->assertFalse(Util\Util::isList($notlist));
    }

    public function testThatPHPHasValueSemanticsForArrays()
    {
        $original = ['php-arrays' => 'value-semantics'];
        $derived = $original;
        $derived['php-arrays'] = 'reference-semantics';

        $this->assertSame('value-semantics', $original['php-arrays']);
    }

    public function testConvertStripeObjectToArrayIncludesId()
    {
        $customer = Util\Util::convertToStripeObject([
            'id' => 'cus_123',
            'object' => 'customer',
        ], null);
        $this->assertTrue(array_key_exists("id", $customer->__toArray(true)));
    }

    public function testUtf8()
    {
        // UTF-8 string
        $x = "\xc3\xa9";
        $this->assertSame(Util\Util::utf8($x), $x);

        // Latin-1 string
        $x = "\xe9";
        $this->assertSame(Util\Util::utf8($x), "\xc3\xa9");

        // Not a string
        $x = true;
        $this->assertSame(Util\Util::utf8($x), $x);
    }

    public function testUrlEncode()
    {
        $a = [
            'my' => 'value',
            'that' => ['your' => 'example'],
            'bar' => 1,
            'baz' => null
        ];

        $enc = Util\Util::urlEncode($a);
        $this->assertSame('my=value&that%5Byour%5D=example&bar=1', $enc);

        $a = ['that' => ['your' => 'example', 'foo' => null]];
        $enc = Util\Util::urlEncode($a);
        $this->assertSame('that%5Byour%5D=example', $enc);

        $a = ['that' => 'example', 'foo' => ['bar', 'baz']];
        $enc = Util\Util::urlEncode($a);
        $this->assertSame('that=example&foo%5B%5D=bar&foo%5B%5D=baz', $enc);

        $a = [
            'my' => 'value',
            'that' => ['your' => ['cheese', 'whiz', null]],
            'bar' => 1,
            'baz' => null
        ];

        $enc = Util\Util::urlEncode($a);
        $expected = 'my=value&that%5Byour%5D%5B%5D=cheese'
              . '&that%5Byour%5D%5B%5D=whiz&bar=1';
        $this->assertSame($expected, $enc);

        // Ignores an empty array
        $enc = Util\Util::urlEncode(['foo' => [], 'bar' => 'baz']);
        $expected = 'bar=baz';
        $this->assertSame($expected, $enc);

        $a = ['foo' => [['bar' => 'baz'], ['bar' => 'bin']]];
        $enc = Util\Util::urlEncode($a);
        $this->assertSame('foo%5B0%5D%5Bbar%5D=baz&foo%5B1%5D%5Bbar%5D=bin', $enc);
    }
}
