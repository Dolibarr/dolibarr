<?php

namespace Stripe;

class BaseTest extends TestCase
{
    public function createFixture($params = [])
    {
        return $this->getMockForAbstractClass('Stripe\\Error\\Base', [
            'message',
            200,
            '{"key": "value"}',
            ['key' => 'value'],
            [
                'Some-Header' => 'Some Value',
                'Request-Id' => 'req_test',
            ],
        ]);
    }

    public function testGetters()
    {
        $e = $this->createFixture();
        $this->assertSame(200, $e->getHttpStatus());
        $this->assertSame('{"key": "value"}', $e->getHttpBody());
        $this->assertSame(['key' => 'value'], $e->getJsonBody());
        $this->assertSame('Some Value', $e->getHttpHeaders()['Some-Header']);
        $this->assertSame('req_test', $e->getRequestId());
    }

    public function testToString()
    {
        $e = $this->createFixture();
        $this->assertContains("from API request 'req_test'", (string)$e);
    }
}
