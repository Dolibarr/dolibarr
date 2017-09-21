<?php

namespace Stripe;

class SubscriptionItemTest extends TestCase
{
    public function testCreateUpdateRetrieveListCancel()
    {
        $plan0ID = 'gold-' . self::generateRandomString(20);
        self::retrieveOrCreatePlan($plan0ID);

        $customer = self::createTestCustomer();
        $sub = Subscription::create(array('plan' => $plan0ID, 'customer' => $customer->id));

        $plan1ID = 'gold-' . self::generateRandomString(20);
        self::retrieveOrCreatePlan($plan1ID);

        $subItem = SubscriptionItem::create(array('plan' => $plan1ID, 'subscription' => $sub->id));
        $this->assertSame($subItem->plan->id, $plan1ID);

        $subItem->quantity = 2;
        $subItem->save();

        $subItem = SubscriptionItem::retrieve($subItem->id);
        $this->assertSame($subItem->quantity, 2);

        // Update the quantity parameter one more time
        $subItem = SubscriptionItem::update($subItem->id, array('quantity' => 3));
        $this->assertSame($subItem->quantity, 3);

        $subItems = SubscriptionItem::all(array('subscription'=>$sub->id, 'limit'=>3));
        $this->assertSame(get_class($subItems->data[0]), 'Stripe\SubscriptionItem');
        $this->assertSame(2, count($subItems->data));

        $subItem->delete();
        $this->assertTrue($subItem->deleted);
    }
}
