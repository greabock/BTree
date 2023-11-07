<?php declare(strict_types=1);

namespace Greabock\RerumCzBtree\Commands;

use Greabock\RerumCzBtree\Field;

class Import
{
    /**
     * @var array|Field[]
     */
    private array $fields;
    private string $dbPath;
    private int $chunkSize;

    public function __construct($fields, string $dbPath, int $chunkSize)
    {
        $this->fields = $fields;
        $this->dbPath = $dbPath;
        $this->chunkSize = $chunkSize;
    }

    public function __invoke(iterable $data): void
    {
        $chunk = 0;

        $stream = null;

        foreach ($data as $i => $row) {
            if ($i % $this->chunkSize === 0) {
                $chunk++;

                if ($stream) {
                    fclose($stream);
                }

                $fileName = $this->fileName($chunk);
                file_put_contents($fileName, null);
                $stream = fopen($fileName, 'w');
            }

            foreach ($this->fields as $name => $field) {
                $string = data_get($row, $name);
                $val = $field->toDatabaseValue($string);
                fwrite($stream, $val);
            }
        }

        if ($stream) {
            fclose($stream);
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

        return new static($fields, $config['database_path'] . '/data/', $config['chunk_size']);
    }

    private function fileName(int $chunk): string
    {
        return $this->dbPath . str_pad((string)$chunk, 5, '0', STR_PAD_LEFT) . '.db';
    }
}