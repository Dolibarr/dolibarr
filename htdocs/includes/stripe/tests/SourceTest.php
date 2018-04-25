<?php

namespace Stripe;

class SourceTest extends TestCase
{
    public function testRetrieve()
    {
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            array(),
            array(
                'id' => 'src_foo',
                'object' => 'source',
            )
        );
        $source = Source::retrieve('src_foo');
        $this->assertSame($source->id, 'src_foo');
    }

    public function testCreate()
    {
        $this->mockRequest(
            'POST',
            '/v1/sources',
            array(
                'type' => 'bitcoin',
                'amount' => 1000,
                'currency' => 'usd',
                'owner' => array('email' => 'jenny.rosen@example.com'),
            ),
            array(
                'id' => 'src_foo',
                'object' => 'source'
            )
        );
        $source = Source::create(array(
            'type' => 'bitcoin',
            'amount' => 1000,
            'currency' => 'usd',
            'owner' => array('email' => 'jenny.rosen@example.com'),
        ));
        $this->assertSame($source->id, 'src_foo');
    }

    public function testSave()
    {
        $response = array(
            'id' => 'src_foo',
            'object' => 'source',
            'metadata' => array(),
        );
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            array(),
            $response
        );

        $response['metadata'] = array('foo' => 'bar');
        $this->mockRequest(
            'POST',
            '/v1/sources/src_foo',
            array(
                'metadata' => array('foo' => 'bar'),
            ),
            $response
        );

        $source = Source::retrieve('src_foo');
        $source->metadata['foo'] = 'bar';
        $source->save();
        $this->assertSame($source->metadata['foo'], 'bar');
    }

    public function testSaveOwner()
    {
        $response = array(
            'id' => 'src_foo',
            'object' => 'source',
            'owner' => array(
                'name' => null,
                'address' => null,
            ),
        );
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            array(),
            $response
        );

        $response['owner'] = array(
            'name' => "Stripey McStripe",
            'address' => array(
                'line1' => "Test Address",
                'city' => "Test City",
                'postal_code' => "12345",
                'state' => "Test State",
                'country' => "Test Country",
            )
        );
        $this->mockRequest(
            'POST',
            '/v1/sources/src_foo',
            array(
                'owner' => array(
                    'name' => "Stripey McStripe",
                    'address' => array(
                        'line1' => "Test Address",
                        'city' => "Test City",
                        'postal_code' => "12345",
                        'state' => "Test State",
                        'country' => "Test Country",
                    ),
                ),
            ),
            $response
        );

        $source = Source::retrieve('src_foo');
        $source->owner['name'] = "Stripey McStripe";
        $source->owner['address'] = array(
            'line1' => "Test Address",
            'city' => "Test City",
            'postal_code' => "12345",
            'state' => "Test State",
            'country' => "Test Country",
        );
        $source->save();
        $this->assertSame($source->owner['name'], "Stripey McStripe");
        $this->assertSame($source->owner['address']['line1'], "Test Address");
        $this->assertSame($source->owner['address']['city'], "Test City");
        $this->assertSame($source->owner['address']['postal_code'], "12345");
        $this->assertSame($source->owner['address']['state'], "Test State");
        $this->assertSame($source->owner['address']['country'], "Test Country");
    }

    public function testDeleteAttached()
    {
        $response = array(
            'id' => 'src_foo',
            'object' => 'source',
            'customer' => 'cus_bar',
        );
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            array(),
            $response
        );

        unset($response['customer']);
        $this->mockRequest(
            'DELETE',
            '/v1/customers/cus_bar/sources/src_foo',
            array(),
            $response
        );

        $source = Source::retrieve('src_foo');
        $source->delete();
        $this->assertFalse(array_key_exists('customer', $source));
    }

    /**
     * @expectedException Stripe\Error\Api
     */
    public function testDeleteUnattached()
    {
        $response = array(
            'id' => 'src_foo',
            'object' => 'source',
        );
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            array(),
            $response
        );

        $source = Source::retrieve('src_foo');
        $source->delete();
    }

    public function testVerify()
    {
        $response = array(
            'id' => 'src_foo',
            'object' => 'source',
            'verification' => array('status' => 'pending'),
        );
        $this->mockRequest(
            'GET',
            '/v1/sources/src_foo',
            array(),
            $response
        );

        $response['verification']['status'] = 'succeeded';
        $this->mockRequest(
            'POST',
            '/v1/sources/src_foo/verify',
            array(
                'values' => array(32, 45),
            ),
            $response
        );

        $source = Source::retrieve('src_foo');
        $this->assertSame($source->verification->status, 'pending');
        $source->verify(array(
            'values' => array(32, 45),
        ));
        $this->assertSame($source->verification->status, 'succeeded');
    }
}
