<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree;

use Greabock\RerumCzBtree\Contracts\DataProvider;
use http\Exception\InvalidArgumentException;

class Database
{
    private DataProvider $provider;

    public function __construct(DataProvider $provider)
    {
        $this->provider = $provider;
    }

    public function import(DataProvider $provider)
    {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('Argument $stream should be resource of type "stream"');
        }
    }
}