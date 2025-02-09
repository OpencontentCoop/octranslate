<?php

$Module = [
    'name' => 'Translate',
    'variable_params' => true,
];

$ViewList = [];

$ViewList['content'] = [
    'functions' => ['translate'],
    'script' => 'content.php',
    'params' => ['Id', 'From', 'To'],
    'unordered_params' => [],
];

$ViewList['settings'] = [
    'functions' => ['settings'],
    'script' => 'settings.php',
    'params' => [],
    'unordered_params' => [],
];
$ViewList['set-mode'] = [
    'functions' => ['translate'],
    'script' => 'set-mode.php',
    'params' => ['Action', 'Parameter'],
    'unordered_params' => [],
];
$FunctionList['translate'] = [];
$FunctionList['settings'] = [];
