<?php

return [
    // Конфигурация полей бд
    'fields' => [
        'id' => [
            'type' => \Greabock\RerumCzBtree\DataTypes\IntegerType::class,
            'params' => [],
        ],
        'name' => [
            'type' => \Greabock\RerumCzBtree\DataTypes\StringType::class,
            'params' => [
                'length' => 30
            ]
        ],
        'nametype' => [
            'type' => \Greabock\RerumCzBtree\DataTypes\StringType::class,
            'params' => [
                'length' => 10
            ]
        ],
        'recclass' => [
            'type' => \Greabock\RerumCzBtree\DataTypes\StringType::class,
            'params' => [
                'length' => 40
            ]
        ],
        'mass' => [
            'type' => \Greabock\RerumCzBtree\DataTypes\FloatType::class,
            'params' => [],
        ],
    ],

    // Место хранения данных и индексовбд БД.
    'database_path' => __DIR__ . '/../resources/database/',

    // Размер чанка данных БД.
    'chunk_size' => 1024,
];
