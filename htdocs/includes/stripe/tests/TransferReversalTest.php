<?php

namespace Stripe;

class TransferReversalTest extends TestCase
{
    // The resource that was traditionally called "transfer" became a "payout"
    // in API version 2017-04-06. We're testing traditional transfers here, so
    // we force the API version just prior anywhere that we need to.
    private $opts = array('stripe_version' => '2017-02-14');

    public function testList()
    {
        $transfer = self::createTestTransfer(array(), $this->opts);
        $all = $transfer->reversals->all();
        $this->assertSame(false, $all['has_more']);
        $this->assertSame(0, count($all->data));
    }
}
