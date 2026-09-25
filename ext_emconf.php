<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title'       => 'schema',
    'description' => 'A simple utility class for implementing schema.org data from several types',
    'constraints' => [
        'depends' => [
            'typo3' => '13.0.0-14.4.99',
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'Supseven\\Schema\\' => 'Classes/',
        ],
    ],
];
