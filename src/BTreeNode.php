<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree;

use Generator;
use Greabock\RerumCzBtree\Contracts\BTreeNode as BTreeNodeContract;
use IteratorAggregate;

class BTreeNode implements BTreeNodeContract, IteratorAggregate
{
    public readonly mixed $key;
    private LLNode $bucket;
    private ?BTreeNode $left = null;
    private ?BTreeNode $right = null;
    private static int $hits = 0;
    public static int $turns = 0;

    public function __construct(mixed $key, $value)
    {
        $this->key = $key;
        $this->bucket = new LLNode($value);
    }

    public function insert(mixed $key, int $value, $skipBalance = true): static
    {
        static::addHit();
        match (true) {
            ($key < $this->key) => $this->left = $this->left?->insert($key, $value, $skipBalance) ?? new static($key, $value),
            ($key > $this->key) => $this->right = $this->right?->insert($key, $value, $skipBalance) ?? new static($key, $value),
            ($key === $this->key) => $this->bucket = $this->bucket->addValue($value),
        };

        return $this;
    }

    public function search(mixed $key): ?static
    {
        static::addHit();
        if ($key < $this->key) {
            return $this->left?->search($key);
        }

        if ($key > $this->key) {
            return $this->right?->search($key);
        }

        return $this;
    }

    public function getValue(): LLNode
    {
        return $this->bucket;
    }

    public static function resetHits(): void
    {
        self::$hits = 0;
    }

    public static function resetTurns(): void
    {
        self::$turns = 0;
    }

    public function calculateBalance(): int
    {
        return ($this->right?->calculateHeight() ?? 0) - ($this->left?->calculateHeight() ?? 0);
    }

    public function calculateHeight(): int
    {
        return max($this->left?->calculateHeight() ?? 0, $this->right?->calculateHeight() ?? 0) + 1;
    }

    private static function treeToVine(BTreeNode $root): void
    {
        $tail = $root;
        $rest = $tail->right;
        while ($rest) {

            self::addTurn();

            if (!$rest->left) {
                $tail = $rest;
                $rest = $rest->right;
                continue;
            }

            $temp = $rest->left;
            $rest->left = $temp->right;
            $temp->right = $rest;
            $rest = $temp;
            $tail->right = $temp;
        }
    }

    /**
     * DSW algorithm to balance tree
     * @see https://en.wikipedia.org/wiki/Day%E2%80%93Stout%E2%80%93Warren_algorithm
     */
    public function rebalance(): static
    {
        $node = new BTreeNode(0, 0);
        $node->right = $this;
        $this->treeToVine($node);
        $this->vineToTree($node, $node->calculateHeight());
        return $node->right;
    }

    public function vineToTree($root, $size): void
    {
        $leaves = $size + 1 - pow(2, (int)log($size + 1, 2));
        $this->compress($root, $leaves);
        $size -= $leaves;
        while ($size > 1) {
            $this->compress($root, $size = intdiv($size, 2));
        }
    }

    public function getRight(): ?static
    {
        return $this->right;
    }

    public function getLeft(): ?static
    {
        return $this->left;
    }

    private static function addHit(): void
    {
        self::$hits++;
    }

    public static function hits(): int
    {
        return static::$hits;
    }

    private static function addTurn(): void
    {
        static::$turns++;
    }

    public static function turns(): int
    {
        return static::$turns;
    }

    /**
     * Analyze over nodes in balanced order
     *
     * @return Generator
     */
    public function getIterator(): Generator
    {
        yield $this;
        if ($this->left) {
            yield from $this->left;
        }
        if ($this->right) {
            yield from $this->right;
        }
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'left' => $this->left?->toArray(),
            'right' => $this->right?->toArray(),
        ];
    }

    private static function compress(BTreeNode $root, int $count): void
    {
        $scanner = $root;

        for ($i = 0; $i < $count; $i++) {
            self::addTurn();
            $child = $scanner->right;
            $scanner->right = $child->right;
            $scanner = $scanner->right;
            $child->right = $scanner->left;
            $scanner->left = $child;
        }
    }
}
