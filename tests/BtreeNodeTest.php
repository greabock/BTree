<?php

namespace Tests;

use Greabock\RerumCzBtree\BTreeNode;
use Greabock\RerumCzBtree\LLNode;

class BtreeNodeTest extends TestCase
{
    public function tearDown(): void
    {
        BTreeNode::resetHits();
        BTreeNode::resetTurns();
    }

    /**
     * @covers \Greabock\RerumCzBtree\BTreeNode
     * @covers \Greabock\RerumCzBtree\LLNode
     */
    public function testGetBucket()
    {
        $node = new BTreeNode(
            $this->faker()->randomNumber(),
            $value = $this->faker()->randomNumber(),
        );

        $this->assertInstanceOf(
            LLNode::class,
            $bucket = $node->getValue(), 'Return value should be instance of Bucket class'
        );
        $this->assertEquals(
            $value,
            $bucket->getIterator()->current(),
            'Current value that Bucket contains should contain value BTreeNode created with'
        );
    }

    /**
     * @covers \Greabock\RerumCzBtree\BTreeNode
     * @covers \Greabock\RerumCzBtree\LLNode
     */
    public function testInsertAndSearch()
    {
        $node = new BTreeNode(
            $this->faker()->unique()->randomNumber(),
            $this->faker()->unique()->randomNumber(),
        );

        $this->assertSame(
            $node,
            $node->insert(
                $key2 = $this->faker()->unique()->randomNumber(),
                $value2 = $this->faker()->unique()->randomNumber(),
            ),
            'Insertion result should be chained'
        );

        $this->assertInstanceOf(
            BTreeNode::class,
            $result = $node->search($key2),
            'Search result should be instance of BTreeNode');

        $this->assertEquals($value2, $result->getValue()->value());

        $this->assertNull(
            $node->search($this->faker()->randomNumber()),
            'Search result should be null for non existing key'
        );
    }

    /**
     * @covers \Greabock\RerumCzBtree\BTreeNode
     * @covers \Greabock\RerumCzBtree\LLNode
     */
    public function testResetHits()
    {
        $node = new BTreeNode(
            $this->faker()->unique()->randomNumber(),
            $this->faker()->unique()->randomNumber(),
        );

        $node->search($this->faker()->unique()->randomNumber());

        $this->assertEquals(1, BTreeNode::hits(), 'Hits should be counted');

        BTreeNode::resetHits();

        $this->assertEquals(0, BTreeNode::hits(), 'Hits should be equal 0 after reset');
    }

    /**
     * @covers \Greabock\RerumCzBtree\BTreeNode
     * @covers \Greabock\RerumCzBtree\LLNode
     */
    public function testNodeShouldAccumulateValuesWhenKeysCollides()
    {
        $node = new BTreeNode(
            $collisionKey = $this->faker()->unique()->randomNumber(),
            $this->faker()->unique()->randomNumber(),
        );

        $this->assertCount(
            1,
            iterator_to_array($node->getValue()),
            'Node should contain only one value when keys doesnt collide'
        );

        $node->insert(
            $collisionKey,
            $this->faker()->unique()->randomNumber(),
        );

        $this->assertCount(
            2,
            iterator_to_array($node->getValue()),
            'Node should accumulate values when keys collide'
        );

    }

    /**
     * @covers \Greabock\RerumCzBtree\BTreeNode
     * @covers \Greabock\RerumCzBtree\LLNode
     */
    public function testBalancedInsertionsDoesNotIncrementTurns()
    {
        $node = new BTreeNode(
            16,
            $this->faker()->unique()->randomNumber(),
        );

        foreach ($this->balancedDataSet() as $key => $value) {
            $node->insert(
                $key,
                $this->faker()->unique()->randomNumber(),
            );
        }

        $this->assertEquals(0, BTreeNode::turns(), 'Data set shouldn`t cause node turns');
    }


    private function balancedDataSet(): array
    {
        return [
            8 => $this->faker()->unique()->randomNumber(),
            24 => $this->faker()->unique()->randomNumber(),
            4 => $this->faker()->unique()->randomNumber(),
            12 => $this->faker()->unique()->randomNumber(),
            20 => $this->faker()->unique()->randomNumber(),
            28 => $this->faker()->unique()->randomNumber(),
            2 => $this->faker()->unique()->randomNumber(),
            6 => $this->faker()->unique()->randomNumber(),
            10 => $this->faker()->unique()->randomNumber(),
            14 => $this->faker()->unique()->randomNumber(),
            18 => $this->faker()->unique()->randomNumber(),
            22 => $this->faker()->unique()->randomNumber(),
            26 => $this->faker()->unique()->randomNumber(),
            30 => $this->faker()->unique()->randomNumber(),
            1 => $this->faker()->unique()->randomNumber(),
            3 => $this->faker()->unique()->randomNumber(),
            5 => $this->faker()->unique()->randomNumber(),
            7 => $this->faker()->unique()->randomNumber(),
            9 => $this->faker()->unique()->randomNumber(),
            11 => $this->faker()->unique()->randomNumber(),
            13 => $this->faker()->unique()->randomNumber(),
            15 => $this->faker()->unique()->randomNumber(),
            17 => $this->faker()->unique()->randomNumber(),
            19 => $this->faker()->unique()->randomNumber(),
            21 => $this->faker()->unique()->randomNumber(),
            23 => $this->faker()->unique()->randomNumber(),
            25 => $this->faker()->unique()->randomNumber(),
            27 => $this->faker()->unique()->randomNumber(),
            29 => $this->faker()->unique()->randomNumber(),
            31 => $this->faker()->unique()->randomNumber(),
        ];
    }
}
