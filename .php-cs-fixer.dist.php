<?php

$finder = PhpCsFixer\Finder::create()->in(__DIR__ . '/src/');

$rules = [
    '@Symfony' => true,
    '@Symfony:risky' => true,
    'align_multiline_comment' => true,
    'array_indentation' => true,
    'array_syntax' => ['syntax' => 'short'],
    'concat_space' => ['spacing' => 'one'],
    'increment_style' => ['style' => 'post'],
    'is_null' => true,
    'native_constant_invocation' => false,
    'native_function_invocation' => false,
    'no_useless_else' => true,
    'no_useless_return' => true,
    'ordered_imports' => true,
    'php_unit_construct' => true,
    'php_unit_dedicate_assert' => true,
    'php_unit_expectation' => true,
    'php_unit_test_case_static_method_calls' => true,
    'phpdoc_order' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'return_assignment' => true,
    'yoda_style' => false,
];

$config = (new PhpCsFixer\Config())
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder($finder);

return $config;
