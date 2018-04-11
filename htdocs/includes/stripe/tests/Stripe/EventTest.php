<?php

namespace Stripe;

class EventTest extends TestCase
{
    const TEST_RESOURCE_ID = 'evt_123';

    public function testIsListable()
    {
        $this->expectsRequest(
            'get',
            '/v1/events'
        );
        $resources = Event::all();
        $this->assertTrue(is_array($resources->data));
        $this->assertInstanceOf("Stripe\\Event", $resources->data[0]);
    }

    public function testIsRetrievable()
    {
        $this->expectsRequest(
            'get',
            '/v1/events/' . self::TEST_RESOURCE_ID
        );
        $resource = Event::retrieve(self::TEST_RESOURCE_ID);
        $this->assertInstanceOf("Stripe\\Event", $resource);
    }
}
