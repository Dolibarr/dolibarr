<?php

namespace Stripe;

class ApplePayDomainTest extends TestCase
{
    public function testCreation()
    {
        $this->mockRequest(
            'POST',
            '/v1/apple_pay/domains',
            array('domain_name' => 'test.com'),
            array(
                'id' => 'apwc_create',
                'object' => 'apple_pay_domain'
            )
        );
        $d = ApplePayDomain::create(array(
            'domain_name' => 'test.com'
        ));
        $this->assertSame('apwc_create', $d->id);
        $this->assertInstanceOf('Stripe\\ApplePayDomain', $d);
    }

    public function testRetrieve()
    {
        $this->mockRequest(
            'GET',
            '/v1/apple_pay/domains/apwc_retrieve',
            array(),
            array(
                'id' => 'apwc_retrieve',
                'object' => 'apple_pay_domain'
            )
        );
        $d = ApplePayDomain::retrieve('apwc_retrieve');
        $this->assertSame('apwc_retrieve', $d->id);
        $this->assertInstanceOf('Stripe\\ApplePayDomain', $d);
    }

    public function testDeletion()
    {
        self::authorizeFromEnv();
        $d = ApplePayDomain::create(array(
            'domain_name' => 'jackshack.website'
        ));
        $this->assertInstanceOf('Stripe\\ApplePayDomain', $d);
        $this->mockRequest(
            'DELETE',
            '/v1/apple_pay/domains/' . $d->id,
            array(),
            array('deleted' => true)
        );
        $d->delete();
        $this->assertTrue($d->deleted);
    }

    public function testList()
    {
        $this->mockRequest(
            'GET',
            '/v1/apple_pay/domains',
            array(),
            array(
                'url' => '/v1/apple_pay/domains',
                'object' => 'list'
            )
        );
        $all = ApplePayDomain::all();
        $this->assertSame($all->url, '/v1/apple_pay/domains');
    }
}
