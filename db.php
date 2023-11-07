<?php declare(strict_types=1);


require __DIR__ . '/vendor/autoload.php';

use Greabock\RerumCzBtree\BTreeNode;
use Greabock\RerumCzBtree\Commands\Analyze;
use Greabock\RerumCzBtree\Commands\CalculateIndex;
use Greabock\RerumCzBtree\Commands\Import;
use Greabock\RerumCzBtree\Commands\ReadIndex;
use Greabock\RerumCzBtree\Commands\Resolve;
use Greabock\RerumCzBtree\Commands\WriteIndex;
use Greabock\RerumCzBtree\LLNode;
use JsonMachine\Items;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

$app = new Application();

$config = require __DIR__ . '/config/database.php';
// ... register commands

$app->register('import')
    ->setDescription('Import json file from stdin')
    ->addArgument('file path', InputArgument::REQUIRED)
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($config): int {
        $start = getrusage();
        $dStart = new DateTime();

        Import::fromConfig($config)(
            (function (iterable $items) use ($output): Generator {
                foreach ($items as $i => $value) {
                    yield $i => $value;
                    $output->write("Imported: $i   \r");
                }
            })(Items::fromFile($input->getArgument('file path')))
        );

        $output->writeln('Stats:');
        (new Table($output))
            ->setHeaders(['memory peak', 'CPU time', 'clock time'])
            ->setRows([[
                human_readable_bytes(memory_get_peak_usage()),
                runtime(getrusage(), $start) . 'ms',
                $dStart->diff(new DateTime())->format('%mm %ss'),
            ]])
            ->render();

        return Command::SUCCESS;
    });


$app->register('analyze')
    ->setDescription('Output max character length for optimizations')
    ->addArgument('file path', InputArgument::REQUIRED)
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($config): int {
        $result = Analyze::fromConfig($config)(
            (function (iterable $items) use ($output): Generator {
                foreach ($items as $i => $value) {
                    yield $i => $value;
                    $output->write("Analyzed: $i \r");
                }
            })(Items::fromFile($input->getArgument('file path')))
        );;

        (new Table($output))
            ->setHeaders(['field', 'max chars'])
            ->setRows(
                array_map(fn(string $key, int $value) => [$key, $value], array_keys($result), $result)
            )
            ->render();

        return Command::SUCCESS;
    });


$app->register('index')
    ->setDescription('Run index process over by field')
    ->addArgument('field name', InputArgument::REQUIRED)
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($config): int {
        $start = getrusage();
        $dStart = new DateTime();
        $output->writeln("Calculate index...");
        BTreeNode::resetHits();
        BTreeNode::resetTurns();
        $tree = (CalculateIndex::fromConfig($config))(
            (function () use ($output, $config): Generator {
                $dir = dir($config['database_path'] . '/data');
                while ($file = $dir->read()) {
                    $output->write("Calculated: $file \r");
                    if (str_ends_with($file, '.db')) {
                        $file = fopen($config['database_path'] . 'data/' . $file, 'r');
                        while (!feof($file)) {
                            yield $file;
                        }
                        fclose($file);
                    }
                }
            })(),

            $input->getArgument('field name')
        );

        $output->writeln("\nBalance: " . $tree->calculateBalance());

        $i = 0;

        $output->writeln("\nWriting index...");
        (WriteIndex::fromConfig($config))($input->getArgument('field name'), (function (iterable $tree) use ($output, $i): Generator {
            foreach ($tree as $node) {
                $i++;
                yield $node;
                $output->write("Wrote: $i \r");
            }
        })($tree));

        $output->writeln('Stats:');
        (new Table($output))
            ->setHeaders(['hits', 'turns', 'tree balance', 'memory peak', 'CPU time', 'clock time'])
            ->setRows([[
                BTreeNode::hits(),
                BTreeNode::turns(),
                $tree->calculateBalance(),
                human_readable_bytes(memory_get_peak_usage()),
                runtime(getrusage(), $start) . 'ms',
                $dStart->diff(new DateTime())->format('%mm %ss'),
            ]])
            ->render();

        return Command::SUCCESS;
    });

$app->register('search')
    ->setDescription('Search rows by field name')
    ->addArgument('field name', InputArgument::REQUIRED)
    ->addArgument('query', InputArgument::REQUIRED)
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($config) {
        $start = getrusage();
        $dStart = new DateTime();
        $node = (ReadIndex::fromConfig($config))($input->getArgument('field name'));
        $buildHits = BTreeNode::hits();
        BTreeNode::resetHits();
        $node = $node->search($input->getArgument('query'));
        $resolve = (Resolve::fromConfig($config));

        $data = iterator_to_array(
            $resolve((function (LLNode $node): Generator {
                $limit = 10;
                foreach ($node as $value) {
                    if (!$limit--) {
                        break;
                    }
                    yield $value;
                }
            })($node->getValue()))
        );

        $output->writeln('Search result:');
        (new Table($output))
            ->setHeaders($resolve->headers())
            ->setRows(
                $data
            )
            ->render();


        $output->writeln('Stats:');
        (new Table($output))
            ->setHeaders(['build hits', 'build turns', 'search hits', 'memory peak', 'CPU time', 'clock time'])
            ->setRows([[
                $buildHits,
                BTreeNode::turns(),
                BTreeNode::hits(),
                human_readable_bytes(memory_get_peak_usage()),
                runtime(getrusage(), $start) . 'ms',
                $dStart->diff(new DateTime())->format('%mm %ss'),
            ]])
            ->render();
    });


$app->register('bruteforce')
    ->setDescription('Search rows by field name')
    ->addArgument('file name', InputArgument::REQUIRED)
    ->addArgument('field', InputArgument::REQUIRED)
    ->addArgument('query', InputArgument::REQUIRED)
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($config) {
        $start = getrusage();
        $dStart = new DateTime();
        $data = [];
        foreach (Items::fromFile($input->getArgument('file name')) as $row) {
            $value = data_get($row, $input->getArgument('field'));
            if ($value === $input->getArgument('query')) {
                $match = [];
                foreach ($config['fields'] as $name => $field) {
                    $match[] = data_get($row, $name);
                }
                $data[] = $match;
            }
        }


        $output->writeln('Search results:');
        (new Table($output))
            ->setHeaders(array_keys($config['fields']))
            ->setRows(
                $data
            )
            ->render();

        $output->writeln('Stats:');
        (new Table($output))
            ->setHeaders(['memory peak', 'CPU time', 'clock time'])
            ->setRows([[
                human_readable_bytes(memory_get_peak_usage()),
                runtime(getrusage(), $start) . 'ms',
                $dStart->diff(new DateTime())->format('%mm %ss'),
            ]])
            ->render();
    });

$app->run();
