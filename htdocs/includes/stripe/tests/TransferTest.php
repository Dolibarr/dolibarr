<?php

namespace Stripe;

class TransferTest extends TestCase
{
    // The resource that was traditionally called "transfer" became a "payout"
    // in API version 2017-04-06. We're testing traditional transfers here, so
    // we force the API version just prior anywhere that we need to.
    private $opts = array('stripe_version' => '2017-02-14');

    public function testCreate()
    {
        $transfer = self::createTestTransfer(array(), $this->opts);
        $this->assertSame('transfer', $transfer->object);
    }

    public function testRetrieve()
    {
        $transfer = self::createTestTransfer(array(), $this->opts);
        $reloaded = Transfer::retrieve($transfer->id, $this->opts);
        $this->assertSame($reloaded->id, $transfer->id);
    }

    public function testTransferUpdateMetadata()
    {
        $transfer = self::createTestTransfer(array(), $this->opts);

        $transfer->metadata['test'] = 'foo bar';
        $transfer->save();

        $updatedTransfer = Transfer::retrieve($transfer->id, $this->opts);
        $this->assertSame('foo bar', $updatedTransfer->metadata['test']);
    }

    public function testTransferUpdateMetadataAll()
    {
        $transfer = self::createTestTransfer(array(), $this->opts);

        $transfer->metadata = array('test' => 'foo bar');
        $transfer->save();

        $updatedTransfer = Transfer::retrieve($transfer->id, $this->opts);
        $this->assertSame('foo bar', $updatedTransfer->metadata['test']);
    }
}
