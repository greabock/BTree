<?php

namespace Greabock\RerumCzBtree\DataTypes;

use Greabock\RerumCzBtree\Contracts\DataType;

class FloatType implements DataType
{
    public function toDatabaseValue(mixed $value): string
    {
        return pack('E', (float)$value);
    }

    public function fromDatabaseValue(string $value): mixed
    {
        return unpack('E', $value)[1];
    }
    
    public function getSize(): int
    {
        return 8;
    }
}