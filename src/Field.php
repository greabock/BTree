<?php

namespace Greabock\RerumCzBtree;

use Greabock\RerumCzBtree\Contracts\DataType;
use Greabock\RerumCzBtree\DataTypes\StringType;

class Field implements DataType
{
    private DataType $type;
    private string $name;
    private int $offset;

    public function __construct(string $name, DataType $type, int $offset)
    {
        $this->type = $type;
        $this->name = $name;
        $this->offset = $offset;
    }

    public function toDatabaseValue(mixed $value): string
    {
        return $this->type->toDatabaseValue($value);
    }

    public function fromDatabaseValue(string $value): mixed
    {
        return $this->type->fromDatabaseValue($value);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return $this->type->getSize();
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function isVariadicLength(): bool
    {
        return $this->type instanceof StringType;
    }

    public function getType(): string
    {
        return get_class($this->type);
    }

}