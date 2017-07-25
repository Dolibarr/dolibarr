<?php

namespace Stripe;

class PayoutTest extends TestCase
{
    private $managedAccount = null;

    /**
     * Create a managed account and put enough funds in the balance
     * to be able to create a payout afterwards. Also try to re-use
     * the managed account across the tests to avoid hitting the
     * rate limit for account creation.
     */
    private function createAccountWithBalance()
    {
        if ($this->managedAccount === null) {
            self::authorizeFromEnv();
            $account = self::createTestManagedAccount();

            $charge = \Stripe\Charge::create(array(
                'currency' => 'usd',
                'amount' => '10000',
                'source' => array(
                    'object' => 'card',
                    'number' => '4000000000000077',
                    'exp_month' => '09',
                    'exp_year' => date('Y') + 3,
                ),
                'destination' => array(
                    'account' => $account->id
                )
            ));

            $this->managedAccount = $account;
        }

        return $this->managedAccount;
    }

    private function createPayoutFromManagedAccount($accountId)
    {
        $payout = Payout::create(
            array(
                'amount' => 100,
                'currency' => 'usd',
            ),
            array(
                'stripe_account' => $accountId
            )
        );

        return $payout;
    }

    public function testCreate()
    {
        $account = self::createAccountWithBalance();
        $payout =  self::createPayoutFromManagedAccount($account->id);

        $this->assertSame('pending', $payout->status);
    }

    public function testRetrieve()
    {
        $account = self::createAccountWithBalance();
        $payout =  self::createPayoutFromManagedAccount($account->id);
        $reloaded = Payout::retrieve($payout->id, array('stripe_account' => $account->id));
        $this->assertSame($reloaded->id, $payout->id);
    }

    public function testPayoutUpdateMetadata()
    {
        $account = self::createAccountWithBalance();
        $payout =  self::createPayoutFromManagedAccount($account->id);
        $payout->metadata['test'] = 'foo bar';
        $payout->save();

        $updatedPayout = Payout::retrieve($payout->id, array('stripe_account' => $account->id));
        $this->assertSame('foo bar', $updatedPayout->metadata['test']);
    }

    public function testPayoutUpdateMetadataAll()
    {
        $account = self::createAccountWithBalance();
        $payout =  self::createPayoutFromManagedAccount($account->id);

        $payout->metadata = array('test' => 'foo bar');
        $payout->save();

        $updatedPayout = Payout::retrieve($payout->id, array('stripe_account' => $account->id));
        $this->assertSame('foo bar', $updatedPayout->metadata['test']);
    }
}
