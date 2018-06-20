<?php

namespace Stripe;

class SignatureVerificationTest extends TestCase
{
    public function testGetters()
    {
        $e = new Error\SignatureVerification('message', 'sig_header');
        $this->assertSame('sig_header', $e->getSigHeader());
    }
}
