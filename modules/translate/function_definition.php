<?php

$FunctionList = [];
$FunctionList['is_auto_translated'] = [
    'name' => 'is_auto_translated',
    'operation_types' => ['read'],
    'call_method' => [
        'class' => 'TranslateFunctionCollection',
        'method' => 'isAutoTranslated',
    ],
    'parameter_type' => 'standard',
    'parameters' => [
        [
            'name' => 'version',
            'type' => 'object',
            'required' => true,
            'default' => false,
        ],
        [
            'name' => 'language_code',
            'type' => 'string',
            'required' => false,
            'default' => eZLocale::currentLocaleCode(),
        ],
    ],
];




