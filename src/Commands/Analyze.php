<?php

namespace Greabock\RerumCzBtree\Commands;

use Greabock\RerumCzBtree\Field;

class Analyze
{
    /**
     * @var array|Field[]
     */
    private array $fields;

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    public function __invoke(iterable $data): array
    {
        $result = [];
        foreach ($data as $row) {
            foreach ($this->fields as $name => $field) {
                if ($field->isVariadicLength())
                    $result[$name] = max(strlen(data_get($row, $name)), $result[$name] ?? 0);
            }
        }

        return $result;
    }

    public static function fromConfig(array $config): static
    {
        $fields = [];
        $offset = 0;
        foreach ($config['fields'] as $name => ['type' => $type, 'params' => $params]) {
            $field = new Field($name, new $type(...$params), $offset);
            $fields[$name] = $field;
            $offset += $field->getOffset();
        }

        return new static($fields);
    }
}