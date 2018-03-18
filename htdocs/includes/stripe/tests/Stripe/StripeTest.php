<?php

namespace Stripe;

class StripeTest extends TestCase
{
    /**
     * @before
     */
    public function saveOriginalValues()
    {
        $this->orig = [
            'caBundlePath' => Stripe::$caBundlePath,
        ];
    }

    /**
     * @after
     */
    public function restoreOriginalValues()
    {
        Stripe::$caBundlePath = $this->orig['caBundlePath'];
    }

    public function testCABundlePathAccessors()
    {
        Stripe::setCABundlePath('path/to/ca/bundle');
        $this->assertEquals('path/to/ca/bundle', Stripe::getCABundlePath());
    }
}
