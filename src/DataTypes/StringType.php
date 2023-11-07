<?php

namespace Greabock\RerumCzBtree\DataTypes;

use Greabock\RerumCzBtree\Contracts\DataType;

class StringType implements DataType
{
    private int $length;

    public function __construct(int $length)
    {
        $this->length = $length;
    }

    public function toDatabaseValue(mixed $value): string
    {
        return str_pad((string)$value, $this->length, "\x00");
    }

    public function fromDatabaseValue(string $value): string
    {
        return rtrim($value, "\x00");
    }

    public function getSize(): int
    {
        return $this->length;
    }
}