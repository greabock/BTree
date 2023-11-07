<?php

namespace Greabock\RerumCzBtree\Contracts;

interface DataType
{
    public function toDatabaseValue(mixed $value): string;

    public function fromDatabaseValue(string $value): mixed;

    public function getSize(): int;
}