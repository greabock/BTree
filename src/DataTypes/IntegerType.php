<?php

namespace Greabock\RerumCzBtree\DataTypes;

use Greabock\RerumCzBtree\Contracts\DataType;

class IntegerType implements DataType
{
    public function toDatabaseValue(mixed $value): string
    {
        return pack('q', (int)$value);
    }

    public function fromDatabaseValue(string $value): int
    {
        return unpack('q', $value)[1];
    }

    public function getSize(): int
    {
        return 8;
    }
}