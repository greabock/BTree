<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree\Commands;

use Greabock\RerumCzBtree\BTreeNode;
use Greabock\RerumCzBtree\Field;

class CalculateIndex
{
    /** @var array|Field[] */
    private array $fields;

    public function __construct($fields)
    {
        $this->fields = $fields;
    }

    public function __invoke(iterable $rows, string $fieldName): ?BTreeNode
    {
        $offset = 0;
        $node = null;

        foreach ($rows as $row) {
            foreach ($this->fields as $field) {
                $bytes = fread($row, $field->getSize());
                if (empty($bytes)) {
                    continue;
                }

                $value = $field->fromDatabaseValue($bytes);

                if ($field->getName() === $fieldName) {
                    if (!$node) {
                        $node = new BTreeNode($value, $offset);
                        continue;
                    }
                    $node->insert($value, $offset);
                }
            }

            $offset++;
        }

        return $node->rebalance();
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