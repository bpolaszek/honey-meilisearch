<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var');

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'declare_strict_types' => true,
        'final_class' => true,
        'concat_space' => ['spacing' => 'one'],
        'no_unused_imports' => true,
        'phpdoc_align' => ['align' => 'left'],
    ])
    ->setFinder($finder);
