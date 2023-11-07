<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree;

use Generator;
use IteratorAggregate;

class LLNode implements IteratorAggregate
{
    private int $value;

    private ?LLNode $next = null;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function addValue(int $value): static
    {
        $new = (new static($value));
        $new->next = $this;

        return $new;
    }

    public function next(): ?LLNode
    {
        return $this->next;
    }

    public function getIterator(): Generator
    {
        $next = $this;
        while ($next) {
            yield $next->value;
            $next = $next->next;
        }
    }

    public function search(int $value): ?static
    {
        foreach ($this as $next) {
            if ($next->value === $value) {
                return $next;
            }
        }

        return null;
    }

    public function value(): int|string
    {
        return $this->value;
    }

    public function length(): int
    {
        return ($this->next?->length() ?? 0) + 1;
    }
}