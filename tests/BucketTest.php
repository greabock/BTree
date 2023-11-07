<?php declare(strict_types=1);

namespace Tests;

use Greabock\RerumCzBtree\LLNode;

class BucketTest extends TestCase
{
    /**
     * @covers \Greabock\RerumCzBtree\LLNode
     */
    public function test_only_int_allowed_as_bucket_value()
    {
        $this->expectNotToPerformAssertions();
        new LLNode($this->faker()->randomDigit());
    }
}