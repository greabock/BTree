<?php

namespace Greabock\RerumCzBtree\Commands;

use Generator;
use Greabock\RerumCzBtree\Field;
use http\Params;

class Resolve
{

    private string $databasePath;

    /**
     * @var array|Field[]
     */
    private array $fields;
    private int $chunkSize;

    public function __construct(array $fields, string $databasePath, int $chunkSize)
    {
        $this->databasePath = $databasePath;
        $this->fields = $fields;
        $this->chunkSize = $chunkSize;
    }

    public function __invoke(iterable $data): Generator
    {
        $fileIdx = [];

        foreach ($data as $offset) {
            [$fileName, $inFileOffset] = $this->resolveFileOffset($offset);
            if (!isset($fileIdx[$fileName])) {
                $fileIdx[$fileName] = [];
            }
            $fileIdx[$fileName][$inFileOffset] = $offset;
        }

        foreach ($fileIdx as $fileName => $offsets) {
            $stream = fopen($fileName, 'r');
            $index = 0;

            stream:
            while (!feof($stream)) {
                $row = [];
                foreach ($this->fields as $field) {
                    $dataBaseValue = fread($stream, $field->getSize());
                    if (empty($dataBaseValue)) {
                        goto stream;
                    }
                    $row[] = $field->fromDatabaseValue($dataBaseValue);
                }

                if (array_key_exists($index, $offsets)) {
                    $row[] = $offsets[$index];
                    $row[] = $index;
                    $row[] = $fileName;
                    yield $row;
                }

                $index++;
            }

            fclose($stream);
        }
    }

    public function headers(): array
    {
        $f = array_keys($this->fields);

        $f[] = 'real offset';
        $f[] = 'offset in file';
        $f[] = 'chunk';

        return $f;
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

        return new static($fields, $config['database_path'] . 'data/', $config['chunk_size']);
    }

    /**
     * @param mixed $offset
     * @return array{0: string, 1: int}
     */
    private function resolveFileOffset(mixed $offset): array
    {
        $chunk = intdiv($offset, $this->chunkSize) + 1;
        return [
            $this->databasePath . str_pad((string)$chunk, 5, '0', STR_PAD_LEFT) . '.db',
            // Вот тут я где-то потерялся на единицу,
            ($offset % $this->chunkSize) - $chunk,
        ];
    }
}