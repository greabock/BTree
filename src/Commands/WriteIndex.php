<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree\Commands;

use Greabock\RerumCzBtree\BTreeNode;
use Greabock\RerumCzBtree\Field;

class WriteIndex
{
    private string $databasePath;

    /**
     * @var array|Field[]
     */
    private array $fields;

    public function __construct(array $fields, string $databasePath)
    {
        $this->databasePath = $databasePath;
        $this->fields = $fields;
    }

    /**
     * @param string $fieldName
     * @param iterable|BTreeNode[] $nodes
     * @return void
     */
    public function __invoke(string $fieldName, iterable $nodes)
    {
        $path = $this->databasePath . 'index/' . $fieldName . '.idx';
        file_put_contents($path, '');
        $index = fopen($path, 'w');

        $field = $this->fields[$fieldName];

        foreach ($nodes as $node) {
            $value = $field->toDatabaseValue($node->key);
            foreach ($node->getValue() as $v) {
                fwrite($index, $value, $field->getSize());
                fwrite($index, pack('q', $v));
            }
        }
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

        return new static($fields, $config['database_path']);
    }
}