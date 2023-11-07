<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree\Contracts;

use Greabock\RerumCzBtree\LLNode;
use Traversable;

interface BTreeNode extends Traversable
{
    public function insert(int $key, int $value): static;

    public function search(int $key): ?static;

    public function getValue(): LLNode;

    public function calculateHeight(): int;
}
