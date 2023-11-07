<?php

namespace Greabock\RerumCzBtree\Contracts;

use Generator;
use IteratorAggregate;

class StreamDataProvider implements DataProvider, IteratorAggregate
{
    protected bool $ready = false;

    /**
     * @var array|Field[]
     */
    private array $fields;
    private iterable $data;

    public function __construct(array $fields, iterable $data)
    {
        $this->fields = $fields;
        $this->data = $data;
    }

    public function ready(): bool
    {
        return $this->ready;
    }

    public function fields(): array
    {
        return $this->fields();
    }

    public function getIterator(): Generator
    {
        foreach ($this->data as $row) {
            yield $row;
        }
    }

    public static function fromResource(object $resource)
    {

    }
}