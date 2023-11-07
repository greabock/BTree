<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree\Contracts;

use Traversable;

interface DataProvider extends Traversable
{
    public function ready(): bool;

    /**
     * @return string[]
     */
    public function fields(): array;
}
