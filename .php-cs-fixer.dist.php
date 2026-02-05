<?php

$finder = (new PhpCsFixer\Finder())
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        'global_namespace_import' => [
            'import_functions' => true,
            'import_constants' => true,
        ],
    ])
    ->setFinder($finder);
