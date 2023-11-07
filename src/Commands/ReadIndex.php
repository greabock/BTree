<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree\Commands;

use Greabock\RerumCzBtree\BTreeNode;
use Greabock\RerumCzBtree\Field;

class ReadIndex
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

    public function __invoke(string $name): ?BTreeNode
    {
        $file = fopen($this->databasePath . 'index/' . $name . '.idx', 'r');

        $node = null;

        while (!feof($file)) {
            $a = $this->fields[$name]->fromDatabaseValue(fread($file, $this->fields[$name]->getSize()));
            if (empty($a)) {
                break;
            }
            $b = fread($file, 8);
            $b = unpack('q', $b)[1];

            if (!isset($node)) {
                $node = new BTreeNode(
                    $a,
                    $b,
                );
                continue;
            }

            $node->insert(
                $a,
                $b,
            );
        }

        return $node;
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