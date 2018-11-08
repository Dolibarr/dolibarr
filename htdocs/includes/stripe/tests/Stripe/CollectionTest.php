<?php

namespace Stripe;

class CollectionTest extends TestCase
{
    /**
     * @before
     */
    public function setUpFixture()
    {
        $this->fixture = Collection::constructFrom([
            'data' => [['id' => 1]],
            'has_more' => true,
            'url' => '/things',
        ]);
    }

    public function testCanList()
    {
        $this->stubRequest(
            'GET',
            '/things',
            [],
            null,
            false,
            [
                'data' => [['id' => 1]],
                'has_more' => true,
                'url' => '/things',
            ]
        );

        $resources = $this->fixture->all();
        $this->assertTrue(is_array($resources->data));
    }

    public function testCanRetrieve()
    {
        $this->stubRequest(
            'GET',
            '/things/1',
            [],
            null,
            false,
            [
                'id' => 1,
            ]
        );

        $this->fixture->retrieve(1);
    }

    public function testCanCreate()
    {
        $this->stubRequest(
            'POST',
            '/things',
            [
                'foo' => 'bar',
            ],
            null,
            false,
            [
                'id' => 2,
            ]
        );

        $this->fixture->create([
            'foo' => 'bar',
        ]);
    }

    public function testProvidesAutoPagingIterator()
    {
        $this->stubRequest(
            'GET',
            '/things',
            [
                'starting_after' => 1,
            ],
            null,
            false,
            [
                'data' => [['id' => 2], ['id' => 3]],
                'has_more' => false,
            ]
        );

        $seen = [];
        foreach ($this->fixture->autoPagingIterator() as $item) {
            array_push($seen, $item['id']);
        }

        $this->assertSame([1, 2, 3], $seen);
    }

    public function testSupportsIteratorToArray()
    {
        $this->stubRequest(
            'GET',
            '/things',
            [
                'starting_after' => 1,
            ],
            null,
            false,
            [
                'data' => [['id' => 2], ['id' => 3]],
                'has_more' => false,
            ]
        );

        $seen = [];
        foreach (iterator_to_array($this->fixture->autoPagingIterator()) as $item) {
            array_push($seen, $item['id']);
        }

        $this->assertSame([1, 2, 3], $seen);
    }

    public function testHeaders()
    {
        $this->stubRequest(
            'POST',
            '/things',
            [
                'foo' => 'bar',
            ],
            [
                'Stripe-Account: acct_foo',
                'Idempotency-Key: qwertyuiop',
            ],
            false,
            [
                'id' => 2,
            ]
        );

        $this->fixture->create([
            'foo' => 'bar',
        ], [
            'stripe_account' => 'acct_foo',
            'idempotency_key' => 'qwertyuiop',
        ]);
    }
}
