<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('var')
;

$config = new PhpCsFixer\Config();
return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => [
            'operators' => ['=>' => 'align', '=' => 'align']
        ],
        'no_unreachable_default_argument_value' => false,
        'braces' => [
            'allow_single_line_closure' => true,
        ],
        'heredoc_to_nowdoc' => false,
        'phpdoc_summary' => false,
        'declare_strict_types' => true,
    ])
    ->setFinder($finder)
;
